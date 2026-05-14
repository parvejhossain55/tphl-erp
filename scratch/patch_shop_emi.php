<?php
$clientFile = 'c:/xampp/htdocs/TPHL-ERP/app/models/AccountShopClient.php';
$content = file_get_contents($clientFile);

// Remove 'case \'emi\':' block from addTransaction
$emiBlockRegex = '/case \'emi\':.*?break;/s';
$content = preg_replace($emiBlockRegex, '', $content, 1);

// Add addTransactionWithEMI
$addTransactionWithEMI = <<< 'METHOD'

    /**
     * Add EMI transaction with automated outstanding settlement and splitting
     */
    public function addTransactionWithEMI($transactionData, $emiScheduleData = null)
    {
        $this->db->begin_transaction();

        try {
            $bookingId    = (int) $transactionData['booking_id'];
            $totalAmount  = (float) $transactionData['amount'];

            // 0. Get payment summary for validations
            $summary = $this->getPaymentSummaryByBookingId($bookingId);

            if (empty($summary['is_shop_service_charge_completed'])) {
                $svcTarget = (float) ($summary['shop_service_charge_target'] ?? 0);
                $svcPaid   = (float) ($summary['shop_service_charge_paid_total'] ?? 0);
                throw new Exception("Shop service charge must be fully paid before EMI. Paid: ৳" . number_format($svcPaid, 2) . " / Required: ৳" . number_format($svcTarget, 2));
            }

            // Service charge on FULL amount (user's answer: YES)
            $serviceChargeRate = (float) ($summary['service_charge_per_emi'] ?? 0);
            $totalServiceFee   = ($serviceChargeRate > 0) ? round($totalAmount * ($serviceChargeRate / 100)) : 0;

            // Construction fee cap check (accounts for existing bulk discounts)
            $constructionFee      = (float) ($summary['construction_fee'] ?? 0);
            $emiPaidSoFar         = (float) ($summary['emi_paid_total'] ?? 0);
            $perEmiAmount         = (float) ($summary['per_emi_amount'] ?? 0);
            $existingBulkDiscount = (float) ($summary['total_bulk_emi_discount'] ?? 0);
            $effectiveCF          = max(0, $constructionFee - $existingBulkDiscount);

            // Bulk discount validation
            $bulkEmiDiscount = (float) ($transactionData['bulk_emi_discount'] ?? 0);

            if ($bulkEmiDiscount > 0) {
                // Discount only allowed when paying >= 2 EMIs worth
                if ($perEmiAmount > 0 && $totalAmount < ($perEmiAmount * 2)) {
                    throw new Exception("Bulk EMI discount requires paying at least 2 EMI installments at once (minimum: ৳" . number_format($perEmiAmount * 2, 2) . ").");
                }
                // Discount cannot exceed remaining construction fee after this payment
                $remainingAfterPayment = $effectiveCF - $emiPaidSoFar - $totalAmount;
                if ($bulkEmiDiscount > $remainingAfterPayment) {
                    throw new Exception("Bulk discount (৳" . number_format($bulkEmiDiscount, 2) . ") exceeds remaining construction fee after payment (৳" . number_format(max(0, $remainingAfterPayment), 2) . "). Max discount: ৳" . number_format(max(0, $remainingAfterPayment), 2));
                }
            }

            if (($emiPaidSoFar + $totalAmount) > $effectiveCF) {
                $maxPayable = max(0, $effectiveCF - $emiPaidSoFar);
                throw new Exception("EMI payment exceeds remaining construction fee. Maximum payable: ৳" . number_format($maxPayable, 2));
            }

            // Common payment metadata
            $attachmentPathsJson = null;
            if (!empty($transactionData['attachment_paths'])) {
                $attachmentPathsJson = is_array($transactionData['attachment_paths'])
                    ? json_encode($transactionData['attachment_paths'])
                    : $transactionData['attachment_paths'];
            }
            $paymentMethod  = $transactionData['payment_method']   ?? 'cash';
            $paymentDate    = $transactionData['payment_date']     ?? date('Y-m-d');
            $transactionRef = $transactionData['transaction_ref']  ?? '';
            $bankName       = $transactionData['bank_name']        ?? '';
            $bankAccNo      = $transactionData['bank_account_no']  ?? '';
            $chequeNo       = $transactionData['cheque_no']        ?? '';
            $onlineType     = $transactionData['online_type']      ?? '';
            $createdBy      = (int) ($transactionData['created_by'] ?? 1);

            $pool = $totalAmount;
            $settledDues = 0;
            $newInstallments = 0;
            $settlementDetails = [];

            // ─── PHASE 1: Auto-settle outstanding dues (FIFO — oldest first) ───
            $outstandingDues = $this->getOutstandingEmiDues($bookingId);

            foreach ($outstandingDues as $due) {
                if ($pool <= 0) break;

                $dueAmount = (float) $due['due_emi_amount'];
                $settle = min($pool, $dueAmount);

                // Service fee for this settlement portion
                $settleSvcFee = ($serviceChargeRate > 0) ? round($settle * ($serviceChargeRate / 100)) : 0;

                // Create settlement audit record
                $settleStmt = $this->db->prepare("
                    INSERT INTO lead_payments (
                        booking_id, payment_type, amount, land_share_amount, service_fee,
                        payment_method, payment_status, payment_date, transaction_ref,
                        bank_name, bank_account_no, cheque_no, online_type,
                        attachment_paths, due_emi_amount, parent_payment_id, settlement_type, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if (!$settleStmt) throw new Exception("Settlement prepare failed: " . $this->db->error);

                $stType = 'emi_due_settlement';
                $stPayType = 'emi'; // Use 'emi' for DB ENUM consistency; settlement tracked via settlement_type
                $stStatus = 'paid';
                $stLandShare = 0.0;
                $stDue = 0.0;
                $parentId = (int) $due['id'];

                $settleStmt->bind_param(
                    "isdddsssssssssdisi",
                    $bookingId, $stPayType, $settle, $stLandShare, $settleSvcFee,
                    $paymentMethod, $stStatus, $paymentDate, $transactionRef,
                    $bankName, $bankAccNo, $chequeNo, $onlineType,
                    $attachmentPathsJson, $stDue, $parentId, $stType, $createdBy
                );

                if (!$settleStmt->execute()) throw new Exception("Settlement insert failed: " . $settleStmt->error);

                // Update parent's due_emi_amount in lead_payments
                $newDue = max(0, $dueAmount - $settle);
                $newParentStatus = ($newDue <= 0) ? 'paid' : 'partially_paid';
                $updStmt = $this->db->prepare("UPDATE lead_payments SET due_emi_amount = ?, payment_status = ? WHERE id = ?");
                $updStmt->bind_param('dsi', $newDue, $newParentStatus, $parentId);
                $updStmt->execute();

                // Also sync emi_schedules
                $schedSyncStmt = $this->db->prepare("UPDATE emi_schedules SET due_emi_amount = ?, status = ? WHERE lead_payment_id = ?");
                $schedSyncStmt->bind_param('dsi', $newDue, $newParentStatus, $parentId);
                $schedSyncStmt->execute();
                $schedSyncStmt->close();

                $pool -= $settle;
                $settledDues++;
                $settlementDetails[] = [
                    'emi_no' => $due['emi_no'],
                    'settled' => $settle,
                    'remaining_due' => $newDue
                ];
            }

            // ─── PHASE 2: Allocate remaining pool to new installment(s) ───
            $firstPaymentId = null;
            $totalAllocated = 0;  // Track total EMI allocated in this transaction

            while ($pool > 0) {
                // How much goes to this installment
                $thisEmi = ($perEmiAmount > 0) ? min($pool, $perEmiAmount) : $pool;

                // Calculate remaining CF after ALL installments so far (including this one)
                // If this payment fully covers the remaining CF, there's no real due
                $cfRemainingBeforeThis = max(0, $effectiveCF - $emiPaidSoFar - $totalAllocated);
                $cfRemainingAfterThis = max(0, $cfRemainingBeforeThis - $thisEmi);
                if ($cfRemainingAfterThis <= 0) {
                    // Last installment — no due even if less than per EMI
                    $thisDue = 0;
                } else {
                    // Expected for this installment is capped at what's actually remaining
                    $expectedForThis = ($perEmiAmount > 0) ? min($perEmiAmount, $cfRemainingBeforeThis) : 0;
                    $thisDue = ($expectedForThis > 0 && $thisEmi < $expectedForThis)
                               ? max(0, round($expectedForThis - $thisEmi))
                               : 0;
                }
                $thisStatus = ($thisDue > 0) ? 'partially_paid' : 'paid';

                // Service fee for this installment
                $thisSvcFee = ($serviceChargeRate > 0) ? round($thisEmi * ($serviceChargeRate / 100)) : 0;

                // Get next EMI number
                $nextEmiNo = $this->getNextAvailableEmiNo($bookingId);

                // Insert lead_payments row for this installment
                $emiStmt = $this->db->prepare("
                    INSERT INTO lead_payments (
                        booking_id, payment_type, amount, land_share_amount, service_fee,
                        payment_method, payment_status, payment_date, transaction_ref,
                        bank_name, bank_account_no, cheque_no, online_type,
                        attachment_paths, due_emi_amount, bulk_emi_discount, bulk_pay_amount, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if (!$emiStmt) throw new Exception("EMI payment prepare failed: " . $this->db->error);

                $emiPayType = 'emi';
                $emiLandShare = 0.0;
                // Attach discount + bulk pay amount only to the FIRST installment
                $thisDiscount = (!$firstPaymentId && $bulkEmiDiscount > 0) ? $bulkEmiDiscount : 0.0;
                $thisBulkPayAmount = (!$firstPaymentId && $newInstallments === 0) ? $totalAmount : null;

                $emiStmt->bind_param(
                    "isdddsssssssssdddi",
                    $bookingId, $emiPayType, $thisEmi, $emiLandShare, $thisSvcFee,
                    $paymentMethod, $thisStatus, $paymentDate, $transactionRef,
                    $bankName, $bankAccNo, $chequeNo, $onlineType,
                    $attachmentPathsJson, $thisDue, $thisDiscount, $thisBulkPayAmount, $createdBy
                );

                if (!$emiStmt->execute()) throw new Exception("EMI payment insert failed: " . $emiStmt->error);

                $paymentId = $this->db->insert_id;
                if (!$firstPaymentId) $firstPaymentId = $paymentId;

                // Insert emi_schedule row
                $schedStmt = $this->db->prepare("
                    INSERT INTO emi_schedules (
                        lead_booking_id, emi_no, emi_amount, service_fee,
                        due_emi_amount, due_date, status, lead_payment_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if (!$schedStmt) throw new Exception("EMI schedule prepare failed: " . $this->db->error);

                $schedStatus = ($thisDue > 0) ? 'paid' : 'paid'; // schedule row always 'paid' (payment was recorded)

                $schedStmt->bind_param(
                    "iidddssi",
                    $bookingId, $nextEmiNo, $thisEmi, $thisSvcFee,
                    $thisDue, $paymentDate, $schedStatus, $paymentId
                );

                if (!$schedStmt->execute()) throw new Exception("EMI schedule insert failed: " . $schedStmt->error);

                $pool -= $thisEmi;
                $totalAllocated += $thisEmi;
                $newInstallments++;
            }

            $this->db->commit();

            // Build result message
            $msgParts = [];
            if ($settledDues > 0) {
                $settledTotal = array_sum(array_column($settlementDetails, 'settled'));
                $msgParts[] = $settledDues . ' outstanding due(s) settled (৳' . number_format($settledTotal, 2) . ')';
            }
            if ($newInstallments > 0) {
                $msgParts[] = $newInstallments . ' new EMI installment(s) recorded';
            }
            if ($totalServiceFee > 0) {
                $msgParts[] = 'Service Fee deducted (৳' . number_format($totalServiceFee, 2) . ')';
            }
            
            $msg = implode(' | ', $msgParts);
            if (empty($msg)) $msg = 'Transaction processed successfully.';

            return [
                'success' => true,
                'message' => $msg,
                'payment_id' => $firstPaymentId // return first ID for reference
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'Transaction failed: ' . $e->getMessage()
            ];
        }
    }
METHOD;

$content = str_replace('public function addTransaction($transactionData, $emiScheduleData = null)', $addTransactionWithEMI . "\n    public function addTransaction(\$transactionData, \$emiScheduleData = null)", $content);

// Remove the createEMISchedule block from addTransaction
$createEmiBlockRegex = '/if \(\$paymentType === \'emi\' \&\& \$emiScheduleData\) \{.*?\}\n/s';
$content = preg_replace($createEmiBlockRegex, '', $content, 1);

file_put_contents($clientFile, $content);
echo "Patched AccountShopClient.php\n";

// Patch AccountShopClientController
$controllerFile = 'c:/xampp/htdocs/TPHL-ERP/app/controllers/AccountShopClientController.php';
$cContent = file_get_contents($controllerFile);
$cContent = str_replace(
"            if (\$transactionData['payment_type'] === 'emi_due_settlement') {
                \$result = \$this->clientModel->addTransaction(\$transactionData, null);
            } else {
                // Calling addTransactionWithEMI is not needed if we modified addTransaction to handle EMIs.
                // Wait, AccountClient has addTransactionWithEMI which calls addTransaction and createEMISchedule. 
                // We'll just call addTransaction because our AccountShopClient logic calls createEMISchedule internally.
                \$result = \$this->clientModel->addTransaction(\$transactionData, \$emiScheduleData);
            }",
"            if (\$transactionData['payment_type'] === 'emi_due_settlement') {
                \$result = \$this->clientModel->addTransaction(\$transactionData, null);
            } elseif (\$transactionData['payment_type'] === 'emi') {
                \$result = \$this->clientModel->addTransactionWithEMI(\$transactionData, \$emiScheduleData);
            } else {
                \$result = \$this->clientModel->addTransaction(\$transactionData, \$emiScheduleData);
            }", $cContent);

file_put_contents($controllerFile, $cContent);
echo "Patched AccountShopClientController.php\n";

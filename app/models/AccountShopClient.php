<?php

class AccountShopClient extends AccountClient
{
    public function __construct($db = null)
    {
        parent::__construct($db);
    }

    /**
     * Get client details by booking ID tailored for Commercial Shops
     */
    public function getClientByBookingId($bookingId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                lb.id as booking_id,
                lb.lead_id,
                lb.booking_date,
                lb.agreement_value,
                -- booking_money_total: all NON-REJECTED entries (pending + verified)
                COALESCE(
                 (SELECT SUM(lp.amount) 
                 FROM lead_payments lp 
                WHERE lp.booking_id = lb.id 
                 AND lp.payment_type = 'booking_money'
                 AND lp.verification_status != 'rejected'), 
                 0) AS booking_money_total,
                -- booking_money: only VERIFIED entries
                COALESCE(
                 (SELECT SUM(lp.amount) 
                 FROM lead_payments lp 
                WHERE lp.booking_id = lb.id 
                 AND lp.payment_type = 'booking_money'
                 AND lp.verification_status = 'verified'), 
                 0) AS booking_money,
                lb.discount_amount,
                lb.booking_status,
                lb.landshare_status,
                lb.service_fee_balance,
                l.id as lead_id,
                l.full_name as client_name,
                l.phone,
                l.email,
                l.designation,
                p.id as project_id,
                p.name as project_name,
                p.types as project_type,
                COALESCE(f.shop_service_charge, 0) as shop_service_charge,
                f.id as flat_id,
                f.floor_no as flat_no,
                f.sqft as flat_size,
                f.price_value as total_price,
                f.flat_name as unit_name,
                f.is_shop,
                f.unit_type,
                u.full_name as agent_name,
                -- Payment calculations
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status = 'verified'), 
                    0
                ) as total_paid,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status = 'verified'
                     AND lp.payment_type = 'registration'), 
                    0
                ) as registration_paid,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_type = 'booking_money'
                     AND lp.verification_status = 'verified'), 
                    0
                ) as booking_money_paid,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status = 'verified'
                     AND lp.payment_type IN ('emi', 'emi_due_settlement')), 
                    0
                ) as emi_paid,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status != 'rejected'
                     AND lp.payment_type IN ('emi', 'emi_due_settlement')), 
                    0
                ) as emi_paid_total,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status = 'verified'
                     AND lp.payment_type = 'shop_service_charge'), 
                    0
                ) as shop_service_charge_paid,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status != 'rejected'
                     AND lp.payment_type = 'shop_service_charge'), 
                    0
                ) as shop_service_charge_paid_total,
                COALESCE(
                    (SELECT SUM(lp.due_emi_amount)
                     FROM lead_payments lp
                     WHERE lp.booking_id = lb.id
                     AND lp.payment_type = 'emi'
                     AND lp.due_emi_amount > 0
                     AND lp.verification_status = 'verified'
                     AND (lp.settlement_type IS NULL OR lp.settlement_type != 'emi_due_settlement')
                     AND NOT EXISTS (
                         SELECT 1 FROM lead_payments child
                         WHERE child.parent_payment_id = lp.id
                         AND child.payment_type = 'emi_due_settlement'
                         AND child.verification_status IN ('pending', 'verified')
                     )),
                    0
                ) as total_emi_due,
                COALESCE(
                    (SELECT SUM(lp.bulk_emi_discount)
                     FROM lead_payments lp
                     WHERE lp.booking_id = lb.id
                     AND lp.verification_status = 'verified'
                     AND lp.bulk_emi_discount > 0),
                    0
                ) as total_bulk_emi_discount,
                COALESCE(
                    (SELECT MAX(lp.payment_date) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status = 'verified'), 
                    NULL
                ) as last_payment_date,
                lb.transfer_date,
                lb.transfer_note,
                lb.transfer_status,
                transfer_u.full_name as transfer_updated_by_name
            FROM lead_bookings lb
            INNER JOIN leads l ON lb.lead_id = l.id
            INNER JOIN projects p ON lb.project_id = p.id
            INNER JOIN flats f ON lb.flat_id = f.id
            LEFT JOIN users u ON l.assigned_id = u.id
            LEFT JOIN users transfer_u ON lb.transfer_updated_by = transfer_u.id
            WHERE lb.id = ?
        ");

        if (!$stmt) {
            error_log("Prepare statement failed: " . $this->db->error);
            return null;
        }

        $stmt->bind_param("i", $bookingId);

        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            return null;
        }

        $result = $stmt->get_result();
        $clientDetails = $result ? $result->fetch_assoc() : null;

        if ($clientDetails) {
            // Cast all amounts to float for calculations
            $totalPrice            = (float) $clientDetails['total_price'];
            $discountAmount        = (float) $clientDetails['discount_amount'];

            // Shop logic
            $totalLandShare        = $totalPrice * 0.30;
            $shopServiceChargePct  = (float) ($clientDetails['shop_service_charge'] ?? 0);
            $shopServiceChargeTarget = ($totalPrice * $shopServiceChargePct) / 100;

            $bookingMoney          = (float) $clientDetails['booking_money'];
            $bookingMoneyTotal     = (float) ($clientDetails['booking_money_total'] ?? 0);

            // Paid amounts
            $totalPaid             = (float) $clientDetails['total_paid'];
            $bookingMoneyPaid      = (float) $clientDetails['booking_money_paid'];
            $registrationPaid      = (float) $clientDetails['registration_paid'];
            $shopServiceChargePaid = (float) $clientDetails['shop_service_charge_paid'];
            $emiPaid               = (float) $clientDetails['emi_paid'];
            
            // Shops have no per-EMI service fee
            $totalServiceFee       = 0;

            $totalLandSharePaid = $bookingMoney + $registrationPaid;

            $landSharePaymentsOnly    = $bookingMoney + $registrationPaid;
            $effectiveLandShareTarget = max(0, $totalLandShare - $discountAmount);
            $remainingLandShare       = max(0, $effectiveLandShareTarget - $landSharePaymentsOnly);
            $isLandShareCompleted     = ($remainingLandShare <= 0);

            $constructionFee = max(0, $totalPrice - ($totalLandShare + $shopServiceChargeTarget));

            // Fixed Per-EMI Amount
            $emiStatsStmt = $this->db->prepare("SELECT total_emi_no FROM lead_payments WHERE booking_id = ? AND total_emi_no IS NOT NULL LIMIT 1");
            $emiStatsStmt->bind_param("i", $bookingId);
            $emiStatsStmt->execute();
            $emiRes = $emiStatsStmt->get_result()->fetch_assoc();
            $totalEmiCount = $emiRes && $emiRes['total_emi_no'] ? (int) $emiRes['total_emi_no'] : 0;

            $perEmiAmount  = ($totalEmiCount > 0) ? round($constructionFee / $totalEmiCount) : 0;

            $isShopServiceChargeCompleted = ($shopServiceChargeTarget > 0 && $shopServiceChargePaid >= $shopServiceChargeTarget);
            if ($shopServiceChargeTarget <= 0) {
                $isShopServiceChargeCompleted = true;
            }

            $constructionFeePaid         = $emiPaid;
            $totalBulkEmiDiscount        = (float) ($clientDetails['total_bulk_emi_discount'] ?? 0);
            $remainingConstructionFee    = max(0, round($constructionFee - $constructionFeePaid - $totalBulkEmiDiscount));

            $isRegistrationPaid = ($registrationPaid > 0);

            $totalDue     = max(0, $totalPrice - $discountAmount - $totalPaid - $totalBulkEmiDiscount);
            $netReceived  = $totalPaid - $totalServiceFee;
            $totalEmiDue  = (float) ($clientDetails['total_emi_due'] ?? 0);

            if ($remainingConstructionFee <= 0) {
                $totalEmiDue = 0;
            }

            // Get EMI arrays by calling parent private function logic via a duplicate here (since it's private in parent)
            $completedEmi = 0;
            $paidEmiNumbers = [];
            $paidEmiStmt = $this->db->prepare("SELECT es.emi_no FROM emi_schedules es INNER JOIN lead_payments lp ON lp.id = es.lead_payment_id WHERE lp.booking_id = ? AND lp.payment_status IN ('paid','partially_paid') AND lp.verification_status = 'verified' ORDER BY es.emi_no");
            $paidEmiStmt->bind_param("i", $bookingId);
            $paidEmiStmt->execute();
            $paidRes = $paidEmiStmt->get_result();
            while ($r = $paidRes->fetch_assoc()) {
                $paidEmiNumbers[] = (int) $r['emi_no'];
            }
            $completedEmi = count($paidEmiNumbers);
            $pendingEmi = max(0, $totalEmiCount - $completedEmi);
            $pendingEmiNumbers = [];
            if ($pendingEmi > 0) {
                for ($i = $completedEmi + 1; $i <= $totalEmiCount; $i++) {
                    $pendingEmiNumbers[] = $i;
                }
            }

            // Assign fields
            $clientDetails['total_land_share_paid']                = $totalLandSharePaid;
            $clientDetails['landshare_paid']                       = $totalLandSharePaid;
            $clientDetails['shop_service_charge_target']           = $shopServiceChargeTarget;
            $clientDetails['shop_service_charge_paid']             = $shopServiceChargePaid;
            $clientDetails['remaining_land_share']                 = $remainingLandShare;
            $clientDetails['is_land_share_completed']              = $isLandShareCompleted;
            $clientDetails['is_shop_service_charge_completed']     = $isShopServiceChargeCompleted;
            $clientDetails['is_registration_paid']                 = $isRegistrationPaid;
            $clientDetails['construction_fee']                     = $constructionFee;
            $clientDetails['per_emi_amount']                       = $perEmiAmount;
            $clientDetails['shop_service_charge_pct']              = $shopServiceChargePct;
            $clientDetails['construction_fee_paid']                = $constructionFeePaid;
            $clientDetails['construction_fee_paid_after_service']  = $constructionFeePaid;
            $clientDetails['remaining_construction_fee']           = $remainingConstructionFee;
            $clientDetails['total_due']                            = $totalDue;
            $clientDetails['net_received']                         = $netReceived;
            $clientDetails['total_emi_due']                        = $totalEmiDue;
            $clientDetails['total_bulk_emi_discount']              = $totalBulkEmiDiscount;
            
            $clientDetails['total_emi']                            = $totalEmiCount;
            $clientDetails['completed_emi']                        = $completedEmi;
            $clientDetails['pending_emi']                          = $pendingEmi;
            $clientDetails['paid_emi_numbers']                     = $paidEmiNumbers;
            $clientDetails['pending_emi_numbers']                  = $pendingEmiNumbers;
        }

        return $clientDetails;
    }

    public function getPaymentSummaryByBookingId($bookingId)
    {
        $clientDetails = $this->getClientByBookingId($bookingId);

        if (!$clientDetails) {
            return [
                'total_price'                          => 0,
                'agreement_value'                      => 0,
                'total_land_share'                     => 0,
                'shop_service_charge_target'           => 0,
                'shop_service_charge_pct'              => 0,
                'booking_money'                        => 0,
                'booking_money_paid'                   => 0,
                'is_booking_fully_paid'                => false,
                'total_land_share_paid'                => 0,
                'landshare_paid'                       => 0,
                'remaining_land_share'                 => 0,
                'discount_amount'                      => 0,
                'registration_paid'                    => 0,
                'is_registration_paid'                 => false,
                'is_land_share_completed'              => false,
                'is_shop_service_charge_completed'     => false,
                'shop_service_charge_paid'             => 0,
                'shop_service_charge_paid_total'       => 0,
                'construction_fee'                     => 0,
                'per_emi_amount'                       => 0,
                'construction_fee_paid'                => 0,
                'construction_fee_paid_after_service'  => 0,
                'remaining_construction_fee'           => 0,
                'total_paid'                           => 0,
                'total_due'                            => 0,
                'total_emi_due'                        => 0,
                'total_emi'                            => 0,
                'completed_emi'                        => 0,
                'pending_emi'                          => 0,
                'last_payment_date'                    => null,
                'total_service_fee'                    => 0,
                'net_received'                         => 0,
                'paid_emi_numbers'                     => [],
                'pending_emi_numbers'                  => []
            ];
        }

        $agreementValue       = (float) ($clientDetails['total_price'] * 0.30); // Agreement value conceptually is Land Share
        $discountForBooking   = (float) ($clientDetails['discount_amount'] ?? 0);
        $bookingMoneyTotal    = (float) ($clientDetails['booking_money_total'] ?? 0);
        $bookingMoneyPaid     = (float) ($clientDetails['booking_money_paid'] ?? 0);
        
        $effectiveBookingTarget = max(0, ($clientDetails['total_price'] * 0.15) - $discountForBooking);
        $isBookingFullyPaid  = ($effectiveBookingTarget > 0 && $bookingMoneyPaid >= $effectiveBookingTarget);
        if ($effectiveBookingTarget <= 0 && $clientDetails['total_price'] <= 0) {
            $isBookingFullyPaid = false; 
        }

        return [
            'total_price'                          => $clientDetails['total_price'],
            'agreement_value'                      => $agreementValue,
            'total_land_share'                     => $clientDetails['total_price'] * 0.30,
            'shop_service_charge_target'           => $clientDetails['shop_service_charge_target'] ?? 0,
            'shop_service_charge_pct'              => $clientDetails['shop_service_charge_pct'] ?? 0,
            'booking_money'                        => $clientDetails['booking_money'],
            'booking_money_total'                  => $bookingMoneyTotal,
            'booking_money_paid'                   => $bookingMoneyPaid,
            'effective_booking_target'             => $effectiveBookingTarget,
            'is_booking_fully_paid'                => $isBookingFullyPaid,
            'total_land_share_paid'                => $clientDetails['total_land_share_paid'],
            'landshare_paid'                       => $clientDetails['landshare_paid'],
            'remaining_land_share'                 => $clientDetails['remaining_land_share'],
            'discount_amount'                      => $clientDetails['discount_amount'],
            'registration_paid'                    => $clientDetails['registration_paid'],
            'registration_money'                   => $clientDetails['registration_paid'],
            'is_registration_paid'                 => $clientDetails['is_registration_paid'],
            'is_land_share_completed'              => $clientDetails['is_land_share_completed'],
            'is_shop_service_charge_completed'     => $clientDetails['is_shop_service_charge_completed'] ?? false,
            'shop_service_charge_paid'             => $clientDetails['shop_service_charge_paid'] ?? 0,
            'shop_service_charge_paid_total'       => $clientDetails['shop_service_charge_paid_total'] ?? 0,
            'construction_fee'                     => $clientDetails['construction_fee'],
            'per_emi_amount'                       => $clientDetails['per_emi_amount'],
            'construction_fee_paid'                => $clientDetails['construction_fee_paid'],
            'construction_fee_paid_after_service'  => $clientDetails['construction_fee_paid_after_service'],
            'remaining_construction_fee'           => $clientDetails['remaining_construction_fee'],
            'total_paid'                           => $clientDetails['total_paid'],
            'total_due'                            => $clientDetails['total_due'],
            'total_emi_due'                        => $clientDetails['total_emi_due'],
            'total_bulk_emi_discount'              => $clientDetails['total_bulk_emi_discount'],
            'total_emi'                            => $clientDetails['total_emi'],
            'completed_emi'                        => $clientDetails['completed_emi'],
            'pending_emi'                          => $clientDetails['pending_emi'],
            'last_payment_date'                    => $clientDetails['last_payment_date'],
            'total_service_fee'                    => 0, // No service fee on EMIs
            'net_received'                         => $clientDetails['net_received'],
            'paid_emi_numbers'                     => $clientDetails['paid_emi_numbers'],
            'pending_emi_numbers'                  => $clientDetails['pending_emi_numbers']
        ];
    }


    public function addTransaction($transactionData, $emiScheduleData = null)
    {
        $this->db->begin_transaction();

        try {
            // Validate required fields
            if (
                empty($transactionData['booking_id']) || empty($transactionData['payment_type']) ||
                empty($transactionData['amount']) || empty($transactionData['payment_status'])
            ) {
                throw new Exception("Missing required transaction data");
            }

            $bookingId = (int) $transactionData['booking_id'];
            $paymentType = $transactionData['payment_type'];
            $amount = (float) $transactionData['amount'];
            $landShareAmount = 0;
            $serviceFee = 0;

            // Get current payment summary for validations
            $summary = $this->getPaymentSummaryByBookingId($bookingId);

            // Validate based on payment type
            switch ($paymentType) {
                case 'booking_money':
                    if ($summary['is_booking_fully_paid']) {
                        throw new Exception("Booking money has already been fully paid for this booking.");
                    }
                    $remainingBooking = ($summary['total_price'] * 0.15) - $summary['booking_money_paid'];
                    if ($amount > $remainingBooking) {
                        throw new Exception("Amount exceeds outstanding booking balance: ৳" . number_format($remainingBooking, 2));
                    }
                    $landShareAmount = $amount;
                    break;

                case 'land_share':
                    // Block land_share until the full agreed booking money has been received
                    if (empty($summary['is_booking_fully_paid'])) {
                        throw new Exception("Booking money must be fully settled before land share payments can be recorded.");
                    }
                    if ($summary['remaining_land_share'] <= 0) {
                        throw new Exception("Land share payment is already completed!");
                    }
                    if ($amount > $summary['remaining_land_share']) {
                        throw new Exception("Land share amount cannot exceed remaining land share: ৳" . number_format($summary['remaining_land_share'], 2));
                    }
                    $landShareAmount = $amount;
                    break;

                case 'registration':
                    // Registration unlocks AND locks at the same time as Land Share
                    if (empty($summary['is_booking_fully_paid'])) {
                        throw new Exception("Booking money must be fully settled before registration payment can be recorded.");
                    }
                    if (!empty($summary['is_land_share_completed'])) {
                        throw new Exception("Registration is already completed (land share completion marks registration as complete).");
                    }
                    break;

                case 'shop_service_charge':
                    // Shop service charge requires registration to be fully completed
                    if (empty($summary['is_land_share_completed'])) {
                        $remaining = number_format($summary['remaining_land_share'] ?? 0, 2);
                        throw new Exception("Registration must be fully completed before shop service charge can be recorded. Remaining registration: ৳{$remaining}");
                    }
                    $svcTarget = (float) ($summary['shop_service_charge_target'] ?? 0);
                    $svcPaid   = (float) ($summary['shop_service_charge_paid_total'] ?? 0);
                    $svcRemaining = max(0, $svcTarget - $svcPaid);
                    if ($svcRemaining <= 0) {
                        throw new Exception("Shop service charge is already fully paid!");
                    }
                    if ($amount > $svcRemaining) {
                        throw new Exception("Amount exceeds remaining shop service charge: ৳" . number_format($svcRemaining, 2));
                    }
                    break;

                case 'emi':
                    // EMI requires shop service charge to be fully paid
                    if (empty($summary['is_shop_service_charge_completed'])) {
                        $svcTarget = (float) ($summary['shop_service_charge_target'] ?? 0);
                        $svcPaid   = (float) ($summary['shop_service_charge_paid_total'] ?? 0);
                        throw new Exception("Shop service charge must be fully paid before EMI can be recorded. Paid: ৳" . number_format($svcPaid, 2) . " / Required: ৳" . number_format($svcTarget, 2));
                    }
                    // Service fee = amount_paid × (service_charge_per_emi% / 100)
                    $serviceChargeRate = (float) ($summary['service_charge_per_emi'] ?? 0);
                    $serviceFee        = ($serviceChargeRate > 0) ? round($amount * ($serviceChargeRate / 100)) : 0;

                    // Compute due: shortfall from the suggested per-EMI amount
                    // BUT if this payment covers the entire remaining CF, there is NO real due
                    $perEmiSuggested   = (float) ($summary['per_emi_amount'] ?? 0);
                    $emiPaidSoFar      = (float) ($summary['emi_paid_total'] ?? 0);
                    $cfTotal           = (float) ($summary['construction_fee'] ?? 0);
                    $bulkDisc          = (float) ($summary['total_bulk_emi_discount'] ?? 0);
                    $effectiveCFRemaining = max(0, $cfTotal - $emiPaidSoFar - $bulkDisc);

                    if ($effectiveCFRemaining <= 0) {
                        throw new Exception("Construction fee is already fully paid! No more EMIs required.");
                    }
                    if ($amount > $effectiveCFRemaining) {
                        throw new Exception("Amount exceeds remaining construction fee: ৳" . number_format($effectiveCFRemaining, 2));
                    }

                    $remainingAfterThis   = max(0, $effectiveCFRemaining - $amount);

                    if ($remainingAfterThis <= 0) {
                        // This payment completes or exceeds the construction fee — NO due
                        $dueEmiAmount = 0;
                    } else {
                        // The "expected" amount for this installment is capped at actual remaining
                        $expectedForThisEmi = ($perEmiSuggested > 0) ? min($perEmiSuggested, $effectiveCFRemaining) : 0;
                        $dueEmiAmount = ($expectedForThisEmi > 0 && $amount < $expectedForThisEmi)
                                        ? round($expectedForThisEmi - $amount)
                                        : 0;
                    }

                    // Override payment_status if partial
                    if ($dueEmiAmount > 0) {
                        $transactionData['payment_status'] = 'partially_paid';
                    }
                    if (($emiPaidSoFar + $amount) > $effectiveCFRemaining + $emiPaidSoFar) {
                        throw new Exception("EMI payment cannot exceed total construction fee: ৳" . number_format($cfTotal, 2));
                    }
                    break;

                case 'emi_due_settlement':
                    // Validate parent EMI payment
                    if (empty($transactionData['parent_payment_id'])) {
                        throw new Exception("Parent EMI payment ID is required for EMI due settlement.");
                    }
                    $parentId  = (int) $transactionData['parent_payment_id'];
                    $parentRow = $this->db->query("
                        SELECT id, booking_id, due_emi_amount FROM lead_payments
                        WHERE id = {$parentId} AND payment_type = 'emi' AND booking_id = {$bookingId}
                    ");
                    if (!$parentRow || !($parentData = $parentRow->fetch_assoc())) {
                        throw new Exception("Invalid parent EMI payment reference.");
                    }
                    $remainingDue = (float) $parentData['due_emi_amount'];
                    if ($remainingDue <= 0) {
                        throw new Exception("This EMI has no outstanding due balance.");
                    }
                    if ($amount > $remainingDue) {
                        throw new Exception("Settle amount (৳" . number_format($amount, 2) . ") cannot exceed outstanding due (৳" . number_format($remainingDue, 2) . ").");
                    }
                    // Service fee applies on the settled amount at same rate
                    $serviceChargeRate = (float) ($summary['service_charge_per_emi'] ?? 0);
                    $serviceFee        = ($serviceChargeRate > 0) ? round($amount * ($serviceChargeRate / 100)) : 0;
                    $dueEmiAmount      = 0; // settlement itself has no new due
                    // Change $paymentType to 'emi' to satisfy ENUM constraint, settlement_type will track the difference
                    $paymentType = 'emi';
                    break;

                default:
                    break;
            }

            // Insert payment record (verification_status defaults to 'pending' via DB default)
            $bankId            = !empty($transactionData['bank_id']) ? (int)$transactionData['bank_id'] : null;
            $attachmentPaths   = !empty($transactionData['attachment_paths']) ? json_encode($transactionData['attachment_paths']) : null;
            $parentPaymentId   = !empty($transactionData['parent_payment_id']) ? (int)$transactionData['parent_payment_id'] : null;
            $settlementType    = !empty($transactionData['settlement_type']) ? $transactionData['settlement_type'] : null;

            $stmt = $this->db->prepare("
                INSERT INTO lead_payments (
                    booking_id, payment_type, amount, land_share_amount, service_fee,
                    payment_method, payment_status, payment_date, transaction_ref,
                    bank_id, bank_name, bank_account_no, cheque_no, online_type,
                    attachment_paths, due_emi_amount, parent_payment_id, settlement_type, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }

            $bankNameLookup = '';
            if ($bankId) {
                $bRow = $this->db->query("SELECT CONCAT(bank_name, ' - ', branch_name) as label FROM banks WHERE id = {$bankId}");
                if ($bRow) { $bData = $bRow->fetch_assoc(); $bankNameLookup = $bData['label'] ?? ''; }
            }

            // Extract values — bind_param requires pass-by-reference variables
            $p_method    = $transactionData['payment_method'] ?? 'cash';
            $p_status    = $transactionData['payment_status'];
            $p_date      = $transactionData['payment_date'] ?? date('Y-m-d');
            $p_txRef     = $transactionData['transaction_ref'] ?? '';
            $p_bankAccNo = $transactionData['bank_account_no'] ?? '';
            $p_chequeNo  = $transactionData['cheque_no'] ?? '';
            $p_onlineType= $transactionData['online_type'] ?? '';
            $p_createdBy = (int) ($transactionData['created_by'] ?? 0);

            $stmt->bind_param(
                "isdddssssisssssdisi",
                $bookingId,
                $paymentType,
                $amount,
                $landShareAmount,
                $serviceFee,
                $p_method,
                $p_status,
                $p_date,
                $p_txRef,
                $bankId,
                $bankNameLookup,
                $p_bankAccNo,
                $p_chequeNo,
                $p_onlineType,
                $attachmentPaths,
                $dueEmiAmount,
                $parentPaymentId,
                $settlementType,
                $p_createdBy
            );

            if (!$stmt->execute()) {
                throw new Exception("Transaction insert failed: " . $stmt->error);
            }

            $paymentId = $this->db->insert_id;

            // If this is an EMI due settlement, reduce the due on the original payment
            if (!empty($transactionData['settlement_type']) && $transactionData['settlement_type'] === 'emi_due_settlement' && $parentPaymentId) {
                $newDue = max(0, (float)$parentData['due_emi_amount'] - $amount);
                $newStatus = ($newDue <= 0) ? 'paid' : 'partially_paid';
                $updStmt = $this->db->prepare("
                    UPDATE lead_payments SET due_emi_amount = ?, payment_status = ? WHERE id = ?
                ");
                $updStmt->bind_param('dsi', $newDue, $newStatus, $parentPaymentId);
                $updStmt->execute();

                // Also sync emi_schedules
                $schedSyncStmt = $this->db->prepare("UPDATE emi_schedules SET due_emi_amount = ?, status = ? WHERE lead_payment_id = ?");
                $schedSyncStmt->bind_param('dsi', $newDue, $newStatus, $parentPaymentId);
                $schedSyncStmt->execute();
                $schedSyncStmt->close();
            }

            if ($paymentType === 'emi' && $emiScheduleData) {
                $this->createEMISchedule($bookingId, $paymentId, $amount, $serviceFee, $emiScheduleData);
            }

            $this->db->commit();

            return [
                'success'           => true,
                'message'           => 'Transaction added successfully!',
                'payment_id'        => $paymentId,
                'service_fee'       => $serviceFee,
                'land_share_amount' => $landShareAmount,
                'due_emi_amount'    => $dueEmiAmount
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'Transaction failed: ' . $e->getMessage()
            ];
        }
    }
}

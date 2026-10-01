<?php

class AutoPostingService
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Helper to get account_id by code (e.g. '1110-002', '2120-001', '4110-001')
     */
    public function getAccountIdByCode($code)
    {
        $stmt = $this->db->prepare("SELECT id FROM chart_of_accounts WHERE account_code = ? LIMIT 1");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if ($res) return (int)$res['id'];

        $like = substr($code, 0, 4) . '%';
        $stmt = $this->db->prepare("SELECT id FROM chart_of_accounts WHERE account_code LIKE ? ORDER BY account_code ASC LIMIT 1");
        $stmt->bind_param("s", $like);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if ($res) return (int)$res['id'];

        $fallback = (str_starts_with($code, '5') || str_starts_with($code, '6')) ? '5000%' : '1000%';
        $stmt = $this->db->prepare("SELECT id FROM chart_of_accounts WHERE account_code LIKE ? LIMIT 1");
        $stmt->bind_param("s", $fallback);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return $res ? (int)$res['id'] : 1;
    }

    /**
     * Dynamically match or create COA Bank/Cash Account
     */
    public function getBankOrCashAccountId($paymentMethod = '', $bankName = '')
    {
        $text = strtolower(trim($paymentMethod . ' ' . $bankName));

        if (strpos($text, 'cash') !== false || strpos($text, 'petty') !== false) {
            if (strpos($text, 'site') !== false) {
                return $this->getAccountIdByCode('114100-01'); // Site Office Cash in Hand
            } elseif (strpos($text, 'gulshan') !== false) {
                return $this->getAccountIdByCode('114100-02'); // Gulshan Office Cash in Hand
            }
            return $this->getAccountIdByCode('114100-03'); // Head Office Cash in Hand
        }

        // Default to General Cash at Bank (114400)
        return $this->getAccountIdByCode('114400');
    }

    /**
     * Generate unique journal voucher number
     */
    public function generateVoucherNumber($prefix = 'JV')
    {
        $query = "SELECT COALESCE(MAX(id), 0) as max_id FROM journals";
        $res = $this->db->query($query);
        $maxId = $res ? (int)$res->fetch_assoc()['max_id'] : 0;
        return $prefix . '-' . date('Ym') . '-' . str_pad($maxId + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * 1. ACCRUAL ENTRY (Management Approved - Liability Recognized)
     * Debit: Expense / WIP Asset Account
     * Credit: Accounts Payable Account (Vendor / Contractor / Payroll Payable)
     */
    public function postAccrualEntry($sourceModule, $sourceRefId, $expenseAccountId, $payableAccountId, $amount, $narration, $projectId = null, $partyType = null, $partyId = null, $createdBy = 1, $overrideOffice = null, $overrideDate = null)
    {
        $amount = (float)$amount;
        if ($amount <= 0 || !$expenseAccountId || !$payableAccountId) {
            return false;
        }

        // Prevent duplicate accrual entry for same module and ref ID
        if ($sourceModule && $sourceRefId) {
            $chk = $this->db->prepare("SELECT id FROM journals WHERE source_module = ? AND source_ref_id = ? AND entry_type = 'accrual' LIMIT 1");
            $chk->bind_param("si", $sourceModule, $sourceRefId);
            $chk->execute();
            $existing = $chk->get_result()->fetch_assoc();
            if ($existing) {
                return (int)$existing['id'];
            }
        }

        $voucherNo = $this->generateVoucherNumber('JV');
        $date = !empty($overrideDate) ? trim($overrideDate) : date('Y-m-d');
        $voucherType = 'Journal Voucher';
        $entryType = 'accrual';
        $status = 'approved';
        $office = !empty($overrideOffice) ? trim($overrideOffice) : $this->getUserWorkplaceOffice($createdBy);

        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO journals 
                (journal_number, date, voucher_type, narration, total_debit, total_credit, created_by, project_id, office, source_module, source_ref_id, entry_type, approval_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param(
                "ssssddiississ",
                $voucherNo,
                $date,
                $voucherType,
                $narration,
                $amount,
                $amount,
                $createdBy,
                $projectId,
                $office,
                $sourceModule,
                $sourceRefId,
                $entryType,
                $status
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to insert accrual journal header: " . $stmt->error);
            }

            $journalId = $stmt->insert_id;

            // Details Entry
            $stmtDet = $this->db->prepare("
                INSERT INTO journal_details 
                (journal_id, account_id, debit, credit, narration, project_id, party_type, party_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            // Row 1: Debit Expense/WIP
            $debitVal = $amount;
            $creditZero = 0.00;
            $stmtDet->bind_param("iiddsisi", $journalId, $expenseAccountId, $debitVal, $creditZero, $narration, $projectId, $partyType, $partyId);
            if (!$stmtDet->execute()) {
                throw new Exception("Failed to insert debit detail: " . $stmtDet->error);
            }

            // Row 2: Credit Payable
            $debitZero = 0.00;
            $creditVal = $amount;
            $stmtDet->bind_param("iiddsisi", $journalId, $payableAccountId, $debitZero, $creditVal, $narration, $projectId, $partyType, $partyId);
            if (!$stmtDet->execute()) {
                throw new Exception("Failed to insert credit detail: " . $stmtDet->error);
            }

            $this->db->commit();
            return $journalId;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("AutoPostingService Exception (postAccrualEntry): " . $e->getMessage());
            return false;
        }
    }

    /**
     * 2. DISBURSEMENT ENTRY (Accounts Paid - Cash/Bank Out)
     * Debit: Accounts Payable (Clears Liability)
     * Credit: Cash / Bank Account (Cash Out)
     */
    public function postDisbursementEntry($sourceModule, $sourceRefId, $payableAccountId, $cashBankAccountId, $amount, $narration, $projectId = null, $partyType = null, $partyId = null, $createdBy = 1, $overrideOffice = null)
    {
        $amount = (float)$amount;
        if ($amount <= 0 || !$payableAccountId || !$cashBankAccountId) {
            return false;
        }

        // Prevent duplicate disbursement entry for same module and ref ID
        if ($sourceModule && $sourceRefId) {
            $chk = $this->db->prepare("SELECT id FROM journals WHERE source_module = ? AND source_ref_id = ? AND entry_type = 'disbursement' LIMIT 1");
            $chk->bind_param("si", $sourceModule, $sourceRefId);
            $chk->execute();
            $existing = $chk->get_result()->fetch_assoc();
            if ($existing) {
                return (int)$existing['id'];
            }
        }

        $voucherNo = $this->generateVoucherNumber('PV');
        $date = date('Y-m-d');
        $voucherType = 'Payment Voucher';
        $entryType = 'disbursement';
        $status = 'approved';
        $office = !empty($overrideOffice) ? trim($overrideOffice) : $this->getUserWorkplaceOffice($createdBy);

        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO journals 
                (journal_number, date, voucher_type, narration, total_debit, total_credit, created_by, project_id, office, source_module, source_ref_id, entry_type, approval_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param(
                "ssssddiississ",
                $voucherNo,
                $date,
                $voucherType,
                $narration,
                $amount,
                $amount,
                $createdBy,
                $projectId,
                $office,
                $sourceModule,
                $sourceRefId,
                $entryType,
                $status
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to insert disbursement journal header: " . $stmt->error);
            }

            $journalId = $stmt->insert_id;

            // Details Entry
            $stmtDet = $this->db->prepare("
                INSERT INTO journal_details 
                (journal_id, account_id, debit, credit, narration, project_id, party_type, party_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            // Row 1: Debit Payable (Clears Liability)
            $debitVal = $amount;
            $creditZero = 0.00;
            $stmtDet->bind_param("iiddsisi", $journalId, $payableAccountId, $debitVal, $creditZero, $narration, $projectId, $partyType, $partyId);
            if (!$stmtDet->execute()) {
                throw new Exception("Failed to insert debit detail: " . $stmtDet->error);
            }

            // Row 2: Credit Cash/Bank
            $debitZero = 0.00;
            $creditVal = $amount;
            $stmtDet->bind_param("iiddsisi", $journalId, $cashBankAccountId, $debitZero, $creditVal, $narration, $projectId, $partyType, $partyId);
            if (!$stmtDet->execute()) {
                throw new Exception("Failed to insert credit detail: " . $stmtDet->error);
            }

            $this->db->commit();
            return $journalId;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("AutoPostingService Exception (postDisbursementEntry): " . $e->getMessage());
            return false;
        }
    }

    /**
     * 3. SALES PAYMENT ENTRY (Client Payment - Splits 10% Service Fee & 90% Deposit)
     * If EMI:
     *   Debit: Cash/Bank (100%)
     *   Credit: Company 10% Service Charge Revenue (10%)
     *   Credit: Client EMI Deposit Fund (90%)
     * If Booking/Reg:
     *   Debit: Cash/Bank (100%)
     *   Credit: Client Land Share Deposit Fund (100%)
     */
    public function postSalesPaymentEntry($sourceRefId, $cashBankAccountId, $amount, $paymentType, $narration, $projectId = null, $clientId = null, $createdBy = 1)
    {
        $amount = (float)$amount;
        if ($amount <= 0 || !$cashBankAccountId) {
            return false;
        }

        // Prevent duplicate journal entry for same sales payment ref ID
        if ($sourceRefId) {
            $chk = $this->db->prepare("SELECT id FROM journals WHERE source_module = 'sales' AND source_ref_id = ? LIMIT 1");
            $chk->bind_param("i", $sourceRefId);
            $chk->execute();
            $existing = $chk->get_result()->fetch_assoc();
            if ($existing) {
                return (int)$existing['id'];
            }
        }

        $voucherNo = $this->generateVoucherNumber('RV');
        $date = date('Y-m-d');
        $voucherType = 'Receipt Voucher';
        $entryType = 'disbursement';
        $status = 'approved';
        $office = $this->getUserWorkplaceOffice($createdBy);
        $sourceModule = 'sales';

        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO journals 
                (journal_number, date, voucher_type, narration, total_debit, total_credit, created_by, project_id, office, source_module, source_ref_id, entry_type, approval_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param(
                "ssssddiississ",
                $voucherNo,
                $date,
                $voucherType,
                $narration,
                $amount,
                $amount,
                $createdBy,
                $projectId,
                $office,
                $sourceModule,
                $sourceRefId,
                $entryType,
                $status
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to insert sales receipt journal header: " . $stmt->error);
            }

            $journalId = $stmt->insert_id;

            $stmtDet = $this->db->prepare("
                INSERT INTO journal_details 
                (journal_id, account_id, debit, credit, narration, project_id, party_type, party_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            // Row 1: Debit Cash/Bank (100% Amount)
            $partyType = 'client';
            $debitVal = $amount;
            $creditZero = 0.00;
            $stmtDet->bind_param("iiddsisi", $journalId, $cashBankAccountId, $debitVal, $creditZero, $narration, $projectId, $partyType, $clientId);
            if (!$stmtDet->execute()) {
                throw new Exception("Failed to insert cash debit detail: " . $stmtDet->error);
            }

            $typeLower = strtolower(trim($paymentType));

            if (in_array($typeLower, ['service_charge', 'shop_service_charge'])) {
                // 100% Service Revenue (400002)
                $serviceChargeAccId = $this->getAccountIdByCode('400002');
                $debitZero = 0.00;
                $creditVal = $amount;

                $stmtDet->bind_param("iiddsisi", $journalId, $serviceChargeAccId, $debitZero, $creditVal, $narration, $projectId, $partyType, $clientId);
                $stmtDet->execute();

            } elseif (in_array($typeLower, ['emi', 'emi_payment'])) {
                // 10% Service Revenue, 90% Deposit
                $serviceChargeAccId = $this->getAccountIdByCode('400002');
                $bookingFundAccId = $this->getAccountIdByCode('400000');
                
                $serviceAmount = round($amount * 0.10, 2);
                $depositAmount = $amount - $serviceAmount;
                
                $debitZero = 0.00;
                
                // 10% to service charge
                $stmtDet->bind_param("iiddsisi", $journalId, $serviceChargeAccId, $debitZero, $serviceAmount, $narration, $projectId, $partyType, $clientId);
                $stmtDet->execute();
                
                // 90% to deposit
                $stmtDet->bind_param("iiddsisi", $journalId, $bookingFundAccId, $debitZero, $depositAmount, $narration, $projectId, $partyType, $clientId);
                $stmtDet->execute();
            } else {
                // Booking, EMI or Land Share Deposit -> 100% Land Share Revenue (400000)
                $bookingFundAccId = $this->getAccountIdByCode('400000');
                $debitZero = 0.00;
                $creditVal = $amount;

                $stmtDet->bind_param("iiddsisi", $journalId, $bookingFundAccId, $debitZero, $creditVal, $narration, $projectId, $partyType, $clientId);
                $stmtDet->execute();
            }

            $this->db->commit();
            return $journalId;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("AutoPostingService Exception (postSalesPaymentEntry): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Helper to get employee workplace for office field, default to 'Site Office' if not found
     */
    public function getUserWorkplaceOffice($userId = null)
    {
        if (!$userId) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $userId = $_SESSION['user_id'] ?? null;
        }

        if ($userId) {
            $stmt = $this->db->prepare("
                SELECT COALESCE(
                    NULLIF(TRIM(ob.name), ''), 
                    NULLIF(TRIM(e.workplace), '')
                ) AS office_branch 
                FROM users u 
                LEFT JOIN employees e ON (u.employee_id = e.id OR e.user_id = u.id) 
                LEFT JOIN office_branches ob ON ob.id = e.office_branch_id
                WHERE u.id = ? 
                LIMIT 1
            ");
            if ($stmt) {
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $res = $stmt->get_result()->fetch_assoc();
                if (!empty($res['office_branch'])) {
                    return trim($res['office_branch']);
                }
            }
        }
        return 'Head Office';
    }

    /**
     * Auto-post Land Requisition / Bayana Payment Journal Entry
     */
    public function postLandRequisitionJournal($requisitionId)
    {
        $stmt = $this->db->prepare("
            SELECT lfr.*, la.land_owner_name, la.project_id 
            FROM land_fund_requisitions lfr 
            LEFT JOIN land_acquisitions la ON lfr.land_acquisition_id = la.id 
            WHERE lfr.id = ? AND lfr.accounts_status = 'Paid'
            LIMIT 1
        ");
        $stmt->bind_param("i", $requisitionId);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();

        if (!$r || (float)$r['requested_amount'] <= 0) return false;

        $amount = (float)$r['requested_amount'];
        $landOwnerName = trim($r['land_owner_name'] ?? 'Land Owner');
        $type = $r['requisition_type'] ?? 'Bayana';
        $txnNo = $r['transaction_no'] ?: $r['id'];

        $narration = "Land Acquisition " . $type . " Payment for " . $landOwnerName . " (Ref: " . $txnNo . ")";

        // Check if journal already posted
        $chk = $this->db->prepare("SELECT id FROM journals WHERE (source_module = 'land_requisition' AND source_ref_id = ?) OR narration LIKE ? LIMIT 1");
        $likeNarr = "%" . $narration . "%";
        $chk->bind_param("is", $requisitionId, $likeNarr);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            return true;
        }

        $bankAccountId = $this->getBankOrCashAccountId($r['payment_method'] ?? 'Bank', '');
        // 570001 Land Purchase (Expense - enters Income Statement / P&L) or fallback to 110005
        $landAccountId = $this->getAccountIdByCode('570001');
        if (!$landAccountId) {
            $landAccountId = $this->getAccountIdByCode('110005'); // Advance Against Land Purchase
        }

        if (!class_exists('GeneralJournal')) {
            require_once __DIR__ . '/../models/GeneralJournal.php';
        }

        $generalJournalModel = new GeneralJournal($this->db);
        $journalNo = $generalJournalModel->generateJournalNumber();

        $userId = $r['accounts_by'] ?: ($_SESSION['user']['id'] ?? 1);

        $headerData = [
            'journal_number' => $journalNo,
            'date' => date('Y-m-d', strtotime($r['accounts_paid_at'] ?: $r['created_at'])),
            'voucher_type' => 'Payment Voucher',
            'narration' => $narration,
            'total_debit' => $amount,
            'total_credit' => $amount,
            'project_id' => $r['project_id'],
            'office' => $this->getUserWorkplaceOffice($userId),
            'status' => 'approved',
            'source_module' => 'land_requisition',
            'source_ref_id' => (int)$r['id'],
            'entry_type' => 'disbursement'
        ];

        $details = [
            [
                'account_id' => $landAccountId,
                'debit' => $amount,
                'credit' => 0,
                'narration' => $narration,
                'project_id' => $r['project_id']
            ],
            [
                'account_id' => $bankAccountId,
                'debit' => 0,
                'credit' => $amount,
                'narration' => $narration,
                'project_id' => $r['project_id']
            ]
        ];

        return $generalJournalModel->storeJournal($headerData, $details, $userId);
    }

    /**
     * Auto-post Management Cash In / Investment Journal Entry (Receipt Voucher)
     */
    public function postManagementCashInJournal($transactionId)
    {
        $stmt = $this->db->prepare("
            SELECT mc.* 
            FROM management_cashin mc 
            WHERE mc.id = ? AND mc.payment_status = 'paid'
            LIMIT 1
        ");
        $stmt->bind_param("i", $transactionId);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();

        if (!$r || (float)$r['amount'] <= 0) return false;

        $amount = (float)$r['amount'];
        $roleName = trim($r['role_name'] ?? 'Management');
        $personName = trim($r['person_name'] ?? '');
        $source = trim($r['source'] ?? 'Investment');
        $txnRef = trim($r['transaction_ref'] ?? '') ?: $r['id'];

        $payMethod = ucfirst($r['payment_method'] ?? 'Cash');
        $bankInfo = trim($r['bank_name'] ?? '');
        $accNo = trim($r['bank_account_no'] ?? '');
        $chequeNo = trim($r['cheque_no'] ?? '');

        $payDetails = "Method: " . $payMethod;
        if (!empty($bankInfo)) $payDetails .= " | Bank: " . $bankInfo;
        if (!empty($accNo)) $payDetails .= " | Acc: " . $accNo;
        if (!empty($chequeNo)) $payDetails .= " | Cheque: " . $chequeNo;

        $narration = "Management Cash In / " . $source . " from " . $roleName . ($personName ? " ($personName)" : "") . " [" . $payDetails . "] (Ref: " . $txnRef . ")";

        // Check if journal already posted
        $chk = $this->db->prepare("SELECT id FROM journals WHERE narration LIKE ? LIMIT 1");
        $likeNarr = "%" . $narration . "%";
        $chk->bind_param("s", $likeNarr);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            return true;
        }

        $bankAccountId = $this->getBankOrCashAccountId($r['payment_method'] ?? 'cash', $r['bank_name'] ?? '');
        $equityAccountId = $this->getAccountIdByCode('3100-001'); // 3100-001 Management & Directors Investment

        if (!class_exists('GeneralJournal')) {
            require_once __DIR__ . '/../models/GeneralJournal.php';
        }

        $generalJournalModel = new GeneralJournal($this->db);
        $journalNo = $generalJournalModel->generateJournalNumber();

        $userId = $r['created_by'] ?: ($_SESSION['user']['id'] ?? 1);

        $headerData = [
            'journal_number' => $journalNo,
            'date' => date('Y-m-d', strtotime($r['transaction_date'] ?: date('Y-m-d'))),
            'voucher_type' => 'Receipt Voucher',
            'narration' => $narration,
            'total_debit' => $amount,
            'total_credit' => $amount,
            'project_id' => null,
            'office' => $this->getUserWorkplaceOffice($userId),
            'status' => 'approved'
        ];

        $details = [
            [
                'account_id' => $bankAccountId,
                'debit' => $amount,
                'credit' => 0,
                'narration' => $narration,
                'project_id' => null
            ],
            [
                'account_id' => $equityAccountId,
                'debit' => 0,
                'credit' => $amount,
                'narration' => $narration,
                'project_id' => null
            ]
        ];

        return $generalJournalModel->storeJournal($headerData, $details, $userId);
    }

    /**
     * Auto-sync any verified sales/client payments that do not yet have a journal entry
     */
    public function syncUnpostedSalesPayments($limit = 50)
    {
        $res = $this->db->query("
            SELECT lp.*, lb.project_id, lb.lead_id, l.full_name as client_name
            FROM lead_payments lp
            LEFT JOIN lead_bookings lb ON lp.booking_id = lb.id
            LEFT JOIN leads l ON lb.lead_id = l.id
            LEFT JOIN journals j ON (j.source_module = 'sales' AND j.source_ref_id = lp.id)
            WHERE (lp.verification_status = 'verified' OR lp.payment_status = 'verified') 
              AND lp.amount > 0 
              AND j.id IS NULL
            LIMIT " . (int)$limit
        );

        if (!$res || $res->num_rows === 0) {
            return 0;
        }

        $count = 0;
        while ($p = $res->fetch_assoc()) {
            $paymentId = (int)$p['id'];
            $methodLower = strtolower(trim($p['payment_method'] ?? 'cash'));
            $bankNameStr = $p['bank_name'] ?? ($p['bank'] ?? '');
            $bankAccId   = $this->getBankOrCashAccountId($methodLower, $bankNameStr);
            $clientName  = !empty($p['client_name']) ? $p['client_name'] : ("Lead #" . ($p['lead_id'] ?? 'N/A'));

            $narr = "Client Receipt (" . $p['payment_type'] . "): " . strtoupper(str_replace('_', ' ', $p['payment_type'])) . " from " . $clientName;
            if (!empty($p['transaction_ref'])) {
                $narr .= " (Ref: " . $p['transaction_ref'] . ")";
            }

            if ($this->postSalesPaymentEntry(
                $paymentId,
                $bankAccId,
                (float)$p['amount'],
                $p['payment_type'],
                $narr,
                $p['project_id'],
                $p['lead_id'],
                $p['created_by'] ?: 1
            )) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Dynamically fetch or create a COA for a specific expense module.
     * Searches by EXACT name. If not found, creates a new 6-digit code under 5000 (Expenses).
     */
    private function getModuleAccountId($coaName)
    {
        // 1. Check if it already exists exactly
        $stmt = $this->db->prepare("SELECT id FROM chart_of_accounts WHERE LOWER(account_name) = LOWER(?) AND (account_type = 'Expense' OR account_type = 'Asset') LIMIT 1");
        $stmt->bind_param('s', $coaName);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            return $res->fetch_assoc()['id'];
        }

        // 2. If not, auto-create it under '5000' (Expenses)
        $res = $this->db->query("SELECT id FROM chart_of_accounts WHERE account_code = '5000' LIMIT 1");
        $row = $res->fetch_assoc();
        $parentId = $row ? $row['id'] : null;
        if (!$parentId) {
            return $this->getAccountIdByCode('520000'); // Fallback
        }

        // Find max code starting with 599
        $res = $this->db->query("SELECT MAX(account_code) as max_code FROM chart_of_accounts WHERE account_code LIKE '599%' AND LENGTH(account_code) = 6");
        $maxCode = $res->fetch_assoc()['max_code'];
        if (!$maxCode) {
            $newCode = '599001';
        } else {
            $newCode = strval((int)$maxCode + 1);
        }

        // Insert new COA
        $stmt = $this->db->prepare("INSERT INTO chart_of_accounts (parent_id, account_code, account_name, account_type, level, root_type, status, is_system_account) VALUES (?, ?, ?, 'Expense', 3, 'Expense', 'active', 1)");
        $stmt->bind_param('iss', $parentId, $newCode, $coaName);
        if ($stmt->execute()) {
            return $stmt->insert_id;
        }

        return $this->getAccountIdByCode('520000'); // Fallback
    }

    /**
     * Auto-sync any verified expenses that do not yet have a journal entry
     */
    public function syncUnpostedExpenses($limit = 50)
    {
        $expenseModules = [
            ['table' => 'fund_requisitions', 'module' => 'fund_requisition', 'coa_name' => 'Fund Requisition', 'status_col' => 'status', 'paid_vals' => ['paid', 'completed'], 'amt_col' => 'm.amount', 'date_col' => 'm.updated_at', 'purpose_col' => 'm.purpose', 'method_col' => 'm.payment_method', 'project_col' => 'm.project_id', 'created_by' => 'm.requested_by'],
            ['table' => 'con_labor_requisitions', 'module' => 'labor_bill', 'coa_name' => 'Labour Requisition', 'status_col' => 'status', 'paid_vals' => ['paid', 'completed'], 'amt_col' => 'COALESCE(m.paid_amount, m.approved_amount, m.total_amount)', 'date_col' => 'm.updated_at', 'purpose_col' => 'm.purpose', 'method_col' => 'm.payment_method', 'project_col' => 'm.project_id', 'created_by' => 'm.created_by'],
            ['table' => 'vendor_payment_requests', 'module' => 'vendor_payment', 'coa_name' => 'Vendor Payment', 'status_col' => 'status', 'paid_vals' => ['paid', 'Paid', 'completed'], 'amt_col' => 'COALESCE(m.approved_amount, m.net_payable_amount, m.invoice_amount)', 'date_col' => 'm.updated_at', 'purpose_col' => 'm.invoice_number', 'method_col' => 'm.payment_type', 'project_col' => 'm.project_id', 'created_by' => 'm.created_by'],
            ['table' => 'contractor_bill_requests', 'module' => 'contractor_bill', 'coa_name' => 'Contractor Bills', 'status_col' => 'status', 'paid_vals' => ['paid', 'Paid', 'completed'], 'amt_col' => 'COALESCE(m.approved_amount, m.bill_amount)', 'date_col' => 'm.updated_at', 'purpose_col' => 'm.work_description', 'method_col' => 'm.bill_type', 'project_col' => 'm.project_id', 'created_by' => 'm.created_by'],
            ['table' => 'operational_fund_requests', 'module' => 'operational_fund', 'coa_name' => 'Operational Fund', 'status_col' => 'status', 'paid_vals' => ['paid', 'completed'], 'amt_col' => 'COALESCE(m.approved_amount, m.amount)', 'date_col' => 'm.updated_at', 'purpose_col' => 'm.purpose', 'method_col' => 'NULL', 'project_col' => 'm.project_id', 'created_by' => 'm.created_by'],
            ['table' => 'project_other_costs', 'module' => 'project_other_cost', 'coa_name' => 'Project Wise Other', 'status_col' => 'status', 'paid_vals' => ['paid', 'completed'], 'amt_col' => 'COALESCE(m.payment_amount, m.approved_amount, m.request_amount)', 'date_col' => 'COALESCE(m.payment_at, m.updated_at)', 'purpose_col' => 'm.purpose', 'method_col' => 'NULL', 'project_col' => 'm.project_id', 'created_by' => 'm.created_by_id'],
            ['table' => 'project_extra_assets', 'module' => 'office_procurement', 'coa_name' => 'Office Procurement', 'status_col' => 'payment_status', 'paid_vals' => ['paid', 'completed'], 'amt_col' => 'COALESCE(m.paid_amount, m.total_price)', 'date_col' => 'COALESCE(m.paid_at, m.updated_at)', 'purpose_col' => 'm.item_name', 'method_col' => 'm.payment_method', 'project_col' => 'm.project_id', 'created_by' => 'm.requested_by'],
            ['table' => 'payslips', 'module' => 'salary', 'coa_name' => 'Salaries & Wages', 'status_col' => 'payment_status', 'paid_vals' => ['paid', 'completed'], 'amt_col' => 'm.net_salary', 'date_col' => 'COALESCE(m.disbursed_at, m.updated_at)', 'purpose_col' => 'm.payslip_month', 'method_col' => 'm.payment_method', 'project_col' => 'm.payroll_project_id', 'created_by' => 'm.created_by'],
            ['table' => 'advance_salaries', 'module' => 'advance_salary', 'coa_name' => 'Advance to Employees against salary', 'status_col' => 'status', 'paid_vals' => ['paid', 'disbursed', 'completed', 'paid_accounts'], 'amt_col' => 'm.amount', 'date_col' => 'm.updated_at', 'purpose_col' => 'm.reason', 'method_col' => 'NULL', 'project_col' => 'NULL', 'created_by' => 'm.created_by'],
            ['table' => 'cashout_requests', 'module' => 'cashout_request', 'coa_name' => 'Cash Out', 'status_col' => 'status', 'paid_vals' => ['paid', 'completed'], 'amt_col' => 'COALESCE(m.approved_amount, m.amount)', 'date_col' => 'm.updated_at', 'purpose_col' => 'm.description', 'method_col' => 'm.payment_method', 'project_col' => 'm.project_id', 'created_by' => 'm.requested_by'],
            ['table' => 'client_refund_requests', 'module' => 'client_refund', 'coa_name' => 'Client Refund', 'status_col' => 'status', 'paid_vals' => ['paid', 'refunded', 'completed'], 'amt_col' => 'm.refund_amount', 'date_col' => 'COALESCE(m.refunded_at, m.updated_at)', 'purpose_col' => 'm.reason', 'method_col' => 'NULL', 'project_col' => 'm.project_id', 'created_by' => 'm.requested_by'],
            ['table' => 'project_incentive_claims', 'module' => 'sales_incentive', 'coa_name' => 'Sales Incentive', 'status_col' => 'claim_status', 'paid_vals' => ['paid', 'completed'], 'amt_col' => 'm.incentive_amount', 'date_col' => 'COALESCE(m.claimed_at, m.updated_at)', 'purpose_col' => 'm.remarks', 'method_col' => 'NULL', 'project_col' => 'm.project_id', 'created_by' => 'm.user_id'],
            ['table' => 'land_fund_requisitions', 'module' => 'land_purchase', 'coa_name' => 'Land Purchase', 'status_col' => 'accounts_status', 'paid_vals' => ['Paid', 'paid', 'completed'], 'amt_col' => 'm.requested_amount', 'date_col' => 'm.updated_at', 'purpose_col' => 'm.requisition_type', 'method_col' => 'm.payment_method', 'project_col' => 'NULL', 'created_by' => 'm.requested_by']
        ];

        $count = 0;
        
        foreach ($expenseModules as $mod) {
            $table = $mod['table'];
            $moduleName = $mod['module'];
            $statusCol = $mod['status_col'];
            $paidVals = implode("','", array_map([$this->db, 'real_escape_string'], $mod['paid_vals']));
            $amtCol = $mod['amt_col'];
            $dateCol = $mod['date_col'];
            $purposeCol = $mod['purpose_col'];
            $methodCol = $mod['method_col'] !== 'NULL' ? $mod['method_col'] : "'cash'";
            $projectCol = $mod['project_col'] !== 'NULL' ? $mod['project_col'] : 'NULL';
            $createdByCol = $mod['created_by'];

            $query = "
                SELECT m.id, 
                       {$amtCol} as amount, 
                       {$dateCol} as date, 
                       {$purposeCol} as purpose, 
                       {$methodCol} as payment_method, 
                       {$projectCol} as project_id,
                       {$createdByCol} as created_by
                FROM {$table} m
                LEFT JOIN journals j ON (j.source_module = '{$moduleName}' AND j.source_ref_id = m.id)
                WHERE m.{$statusCol} IN ('{$paidVals}') 
                AND j.id IS NULL
                LIMIT " . (int)$limit;

            try {
                $res = $this->db->query($query);
                if (!$res) {
                    echo "Query failed for $moduleName: " . $this->db->error . "\n";
                    continue;
                }
            } catch (\Exception $e) {
                echo "Exception for $moduleName: " . $e->getMessage() . "\n";
                continue;
            }
            if ($res && $res->num_rows > 0) {
                while ($r = $res->fetch_assoc()) {
                    $amount = (float)$r['amount'];
                    if ($amount <= 0) continue;

                    $method = $r['payment_method'] ?? 'cash';
                    $bankAccId = $this->getBankOrCashAccountId($method, '');
                    $expAccId = $this->getModuleAccountId($mod['coa_name']);
                    
                    $voucherNo = $this->generateVoucherNumber('PV');
                    $this->db->begin_transaction();
                    
                    try {
                        $stmt = $this->db->prepare("INSERT INTO journals (journal_number, date, voucher_type, narration, total_debit, total_credit, created_by, project_id, office, source_module, source_ref_id, entry_type, approval_status) VALUES (?, ?, 'Payment Voucher', ?, ?, ?, ?, ?, ?, ?, ?, 'disbursement', 'approved')");
                        $date = date('Y-m-d', strtotime($r['date'] ?: date('Y-m-d')));
                        $narr = ucwords(str_replace('_', ' ', $moduleName)) . " Paid: " . ($r['purpose'] ?? 'N/A');
                        $office = $this->getUserWorkplaceOffice($r['created_by']);
                        $stmt->bind_param("sssddiissi", $voucherNo, $date, $narr, $amount, $amount, $r['created_by'], $r['project_id'], $office, $moduleName, $r['id']);
                        $stmt->execute();
                        $journalId = $stmt->insert_id;
                        
                        $stmtDet = $this->db->prepare("INSERT INTO journal_details (journal_id, account_id, debit, credit, narration, project_id) VALUES (?, ?, ?, ?, ?, ?)");
                        $zero = 0;
                        $stmtDet->bind_param("iiddsi", $journalId, $expAccId, $amount, $zero, $narr, $r['project_id']);
                        $stmtDet->execute();
                        $stmtDet->bind_param("iiddsi", $journalId, $bankAccId, $zero, $amount, $narr, $r['project_id']);
                        $stmtDet->execute();
                        
                        $this->db->commit();
                        $count++;
                    } catch (Exception $e) {
                        $this->db->rollback();
                    }
                }
            }
        }
        return $count;
    }
}

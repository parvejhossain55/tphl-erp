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
     * Generate unique journal voucher number
     */
    public function generateVoucherNumber($prefix = 'JV')
    {
        $query = "SELECT COUNT(id) as cnt FROM journals";
        $res = $this->db->query($query);
        $count = $res ? (int)$res->fetch_assoc()['cnt'] : 0;
        return $prefix . '-' . date('Ym') . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * 1. ACCRUAL ENTRY (Management Approved - Liability Recognized)
     * Debit: Expense / WIP Asset Account
     * Credit: Accounts Payable Account (Vendor / Contractor / Payroll Payable)
     */
    public function postAccrualEntry($sourceModule, $sourceRefId, $expenseAccountId, $payableAccountId, $amount, $narration, $projectId = null, $partyType = null, $partyId = null, $createdBy = 1)
    {
        $amount = (float)$amount;
        if ($amount <= 0 || !$expenseAccountId || !$payableAccountId) {
            return false;
        }

        $voucherNo = $this->generateVoucherNumber('JV');
        $date = date('Y-m-d');
        $voucherType = 'Journal Voucher';
        $entryType = 'accrual';
        $status = 'approved';
        $office = 'Head Office';

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
    public function postDisbursementEntry($sourceModule, $sourceRefId, $payableAccountId, $cashBankAccountId, $amount, $narration, $projectId = null, $partyType = null, $partyId = null, $createdBy = 1)
    {
        $amount = (float)$amount;
        if ($amount <= 0 || !$payableAccountId || !$cashBankAccountId) {
            return false;
        }

        $voucherNo = $this->generateVoucherNumber('PV');
        $date = date('Y-m-d');
        $voucherType = 'Payment Voucher';
        $entryType = 'disbursement';
        $status = 'approved';
        $office = 'Head Office';

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

        $voucherNo = $this->generateVoucherNumber('RV');
        $date = date('Y-m-d');
        $voucherType = 'Receipt Voucher';
        $entryType = 'disbursement';
        $status = 'approved';
        $office = 'Head Office';
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

            if (in_array($typeLower, ['emi', 'installment'])) {
                // 10% Service Fee Income (4110-001)
                $serviceChargeAccId = $this->getAccountIdByCode('4110-001');
                // 90% Client EMI Fund (2220-001)
                $emiFundAccId = $this->getAccountIdByCode('2220-001');

                $serviceFeeAmount = round($amount * 0.10, 2);
                $depositAmount = round($amount - $serviceFeeAmount, 2);
                $debitZero = 0.00;

                // Credit 10% Service Fee Income
                $stmtDet->bind_param("iiddsisi", $journalId, $serviceChargeAccId, $debitZero, $serviceFeeAmount, $narration, $projectId, $partyType, $clientId);
                $stmtDet->execute();

                // Credit 90% EMI Deposit Fund
                $stmtDet->bind_param("iiddsisi", $journalId, $emiFundAccId, $debitZero, $depositAmount, $narration, $projectId, $partyType, $clientId);
                $stmtDet->execute();

            } elseif (in_array($typeLower, ['development_charge', 'flat_development_charge', 'dev_charge'])) {
                // 100% Client Development Charge Deposit Fund (2230-001)
                $devFundAccId = $this->getAccountIdByCode('2230-001');
                if (!$devFundAccId) $devFundAccId = $this->getAccountIdByCode('2210-001');
                $debitZero = 0.00;
                $creditVal = $amount;

                $stmtDet->bind_param("iiddsisi", $journalId, $devFundAccId, $debitZero, $creditVal, $narration, $projectId, $partyType, $clientId);
                $stmtDet->execute();

            } elseif (in_array($typeLower, ['service_charge', 'shop_service_charge'])) {
                // 100% Service Fee Income (4110-001)
                $serviceChargeAccId = $this->getAccountIdByCode('4110-001');
                $debitZero = 0.00;
                $creditVal = $amount;

                $stmtDet->bind_param("iiddsisi", $journalId, $serviceChargeAccId, $debitZero, $creditVal, $narration, $projectId, $partyType, $clientId);
                $stmtDet->execute();

            } else {
                // Booking or Registration Fee -> 100% Client Land Share Booking Fund (2210-001)
                $bookingFundAccId = $this->getAccountIdByCode('2210-001');
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
}

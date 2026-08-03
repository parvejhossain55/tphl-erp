<?php
namespace Services;

require_once __DIR__ . '/../models/GeneralJournal.php';

use GeneralJournal;

class JournalIntegrationService
{
    protected $db;
    protected $journalModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->journalModel = new \GeneralJournal($db);
    }

    /**
     * Hook 1: Accrual Journal (Liability Recognition)
     * Triggered when MD approves a Vendor Payment Request.
     */
    public function generateVendorAccrual($vprId, $userId)
    {
        // 1. Fetch Vendor Payment Request details
        $stmt = $this->db->prepare("
            SELECT vpr.*, v.name as vendor_name, p.name as project_name 
            FROM vendor_payment_requests vpr 
            LEFT JOIN vendors v ON vpr.vendor_id = v.id 
            LEFT JOIN projects p ON vpr.project_id = p.id
            WHERE vpr.id = ?
        ");
        $stmt->bind_param('i', $vprId);
        $stmt->execute();
        $vpr = $stmt->get_result()->fetch_assoc();
        
        if (!$vpr) return false;

        $projectId = (int)$vpr['project_id'];
        $projectName = $vpr['project_name'] ?? 'General';
        $netPayable = (float)$vpr['net_payable'];
        
        // 2. Determine AP Account (Liability)
        $apAccountId = $this->getAccountIdByCode('2110'); // Accounts Payable - Vendors
        if (!$apAccountId) {
            error_log("JournalIntegrationService: AP Vendor account (2110) not found.");
            return false;
        }

        $details = [];
        $totalDebit = 0;

        // 3. Trace back to PO items to find Material Categories & GL Accounts
        if (!empty($vpr['purchase_order_id'])) {
            $stmtItems = $this->db->prepare("
                SELECT 
                    cat.gl_account_id, 
                    cat.name as category_name,
                    SUM(poi.quantity * poi.unit_price) as category_total
                FROM purchase_order_items poi
                JOIN con_materials m ON poi.material_id = m.id
                JOIN con_material_categories cat ON m.category_id = cat.id
                WHERE poi.purchase_order_id = ?
                GROUP BY cat.id
            ");
            $stmtItems->bind_param('i', $vpr['purchase_order_id']);
            $stmtItems->execute();
            $items = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);

            // Calculate ratio if invoice amount differs from total PO amount (due to partial bills or penalties)
            $totalPoAmount = array_sum(array_column($items, 'category_total'));
            $ratio = $totalPoAmount > 0 ? ($netPayable / $totalPoAmount) : 1;

            foreach ($items as $item) {
                $glAccountId = $item['gl_account_id'] ?: $this->getAccountIdByCode('1250'); // Default to Site Expenses if unmapped
                $amount = round($item['category_total'] * $ratio, 2);
                
                $details[] = [
                    'account_id' => $glAccountId,
                    'debit' => $amount,
                    'credit' => 0,
                    'narration' => "Project: $projectName | Category: " . $item['category_name'] . " | Vendor: " . $vpr['vendor_name'],
                    'project_id' => $projectId
                ];
                $totalDebit += $amount;
            }
        } else {
            // No PO attached (Direct Bill)
            $glAccountId = $this->getAccountIdByCode('1250'); // Default to WIP - Site Expenses
            $details[] = [
                'account_id' => $glAccountId,
                'debit' => $netPayable,
                'credit' => 0,
                'narration' => "Project: $projectName | Category: Materials/Direct Purchase | Vendor: " . $vpr['vendor_name'],
                'project_id' => $projectId
            ];
            $totalDebit += $netPayable;
        }

        // 4. Add the AP Credit leg
        $details[] = [
            'account_id' => $apAccountId,
            'debit' => 0,
            'credit' => $totalDebit, // Use totalDebit to perfectly balance due to rounding
            'narration' => "Accounts Payable Accrual - " . $vpr['vendor_name'],
            'project_id' => $projectId
        ];

        // 5. Build Header and Save
        $headerData = [
            'journal_number' => $this->journalModel->generateJournalNumber(),
            'date' => date('Y-m-d'),
            'voucher_type' => 'Journal Voucher',
            'narration' => "Vendor Accrual for VPR #" . $vprId,
            'total_debit' => $totalDebit,
            'total_credit' => $totalDebit,
            'project_id' => $projectId,
            'office' => 'Site'
        ];

        return $this->journalModel->storeJournal($headerData, $details, $userId);
    }

    /**
     * Hook 2: Disbursement Journal (Payment Execution)
     * Triggered when Accounts processes a payment in Con_FinancialTransaction.
     */
    public function generateDisbursement($txnId, $userId)
    {
        // 1. Fetch transaction details
        $stmt = $this->db->prepare("SELECT * FROM con_financial_transactions WHERE id = ?");
        $stmt->bind_param('i', $txnId);
        $stmt->execute();
        $txn = $stmt->get_result()->fetch_assoc();
        
        if (!$txn) return false;

        $amount = (float)$txn['amount_paid'];
        $projectId = (int)$txn['project_id'];

        // 2. Determine AP / Liability Account based on ref_type
        $apAccountId = null;
        if ($txn['ref_type'] === 'vendor_payment') {
            $apAccountId = $this->getAccountIdByCode('2110'); // AP Vendors
        } elseif ($txn['ref_type'] === 'contractor_bill') {
            $apAccountId = $this->getAccountIdByCode('2120'); // AP Contractors
        } elseif ($txn['ref_type'] === 'operational_fund' || $txn['ref_type'] === 'cashout_request') {
            $apAccountId = $this->getAccountIdByCode('2130'); // AP Employees
        } else {
            $apAccountId = $this->getAccountIdByCode('2110'); // Fallback
        }

        // 3. Determine Bank/Cash Account (Asset)
        // Usually, the system requires mapped banks. For MVP, we'll map to 1110 (Cash and Bank)
        $cashAccountId = $this->getAccountIdByCode('1110'); 

        if (!$apAccountId || !$cashAccountId) {
            error_log("JournalIntegrationService: Account mapping missing for Disbursement.");
            return false;
        }

        $details = [];
        
        // Debit AP (Reducing Liability)
        $details[] = [
            'account_id' => $apAccountId,
            'debit' => $amount,
            'credit' => 0,
            'narration' => "Payment Disbursement for " . $txn['ref_type'] . " #" . $txn['ref_id'],
            'project_id' => $projectId
        ];

        // Credit Cash (Reducing Asset)
        $details[] = [
            'account_id' => $cashAccountId,
            'debit' => 0,
            'credit' => $amount,
            'narration' => "Cash/Bank Payment Output",
            'project_id' => $projectId
        ];

        // 4. Build Header and Save
        $headerData = [
            'journal_number' => $this->journalModel->generateJournalNumber(),
            'date' => $txn['transaction_date'] ?: date('Y-m-d'),
            'voucher_type' => 'Payment Voucher',
            'narration' => "Disbursement for Transaction #" . $txnId . " (" . $txn['payment_method'] . ")",
            'total_debit' => $amount,
            'total_credit' => $amount,
            'project_id' => $projectId,
            'office' => 'Site'
        ];

        return $this->journalModel->storeJournal($headerData, $details, $userId);
    }

    /**
     * Hook 3: Accrual Journal for Contractor Bills (Liability Recognition)
     */
    public function generateContractorAccrual($billId, $userId)
    {
        // 1. Fetch Contractor Bill details
        $stmt = $this->db->prepare("
            SELECT cb.*, c.name as contractor_name, p.name as project_name 
            FROM contractor_bill_requests cb 
            LEFT JOIN contractors c ON cb.operator_id = c.id 
            LEFT JOIN projects p ON cb.project_id = p.id
            WHERE cb.id = ?
        ");
        $stmt->bind_param('i', $billId);
        $stmt->execute();
        $bill = $stmt->get_result()->fetch_assoc();
        
        if (!$bill) return false;

        $projectId = (int)$bill['project_id'];
        $projectName = $bill['project_name'] ?? 'General';
        $approvedAmount = (float)$bill['approved_amount'];
        if ($approvedAmount <= 0) $approvedAmount = (float)$bill['bill_amount'];
        
        // 2. Determine AP Account (Liability)
        $apAccountId = $this->getAccountIdByCode('2120'); // Accounts Payable - Contractors
        if (!$apAccountId) {
            error_log("JournalIntegrationService: AP Contractor account (2120) not found.");
            return false;
        }

        $details = [];
        $totalDebit = 0;

        // 3. Trace back to work types & BOQ
        // For now, mapping Contractor Bills directly to WIP - Civil (1220) or MEP (1230)
        // Without complex BOQ-level tracing, we use work_types.gl_account_id or default to 1220
        $stmtItems = $this->db->prepare("
            SELECT 
                wt.gl_account_id, 
                wt.work_type_name as category_name,
                SUM(cbi.qty * cbi.rate) as category_total
            FROM contractor_bill_items cbi
            JOIN work_types wt ON cbi.work_type_id = wt.id
            WHERE cbi.bill_id = ?
            GROUP BY wt.id
        ");
        $stmtItems->bind_param('i', $billId);
        $stmtItems->execute();
        $items = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);

        if (!empty($items)) {
            $totalBillAmount = array_sum(array_column($items, 'category_total'));
            $ratio = $totalBillAmount > 0 ? ($approvedAmount / $totalBillAmount) : 1;

            foreach ($items as $item) {
                $glAccountId = $item['gl_account_id'] ?: $this->getAccountIdByCode('1220'); // Default to Civil Construction
                $amount = round($item['category_total'] * $ratio, 2);
                
                $details[] = [
                    'account_id' => $glAccountId,
                    'debit' => $amount,
                    'credit' => 0,
                    'narration' => "Project: $projectName | Category: " . $item['category_name'] . " | Contractor: " . $bill['contractor_name'],
                    'project_id' => $projectId
                ];
                $totalDebit += $amount;
            }
        } else {
            // Default directly
            $glAccountId = $this->getAccountIdByCode('1220'); // Default to Civil Construction
            $details[] = [
                'account_id' => $glAccountId,
                'debit' => $approvedAmount,
                'credit' => 0,
                'narration' => "Project: $projectName | Category: Construction Work/Civil | Contractor: " . $bill['contractor_name'],
                'project_id' => $projectId
            ];
            $totalDebit += $approvedAmount;
        }

        // 4. Add the AP Credit leg
        $details[] = [
            'account_id' => $apAccountId,
            'debit' => 0,
            'credit' => $totalDebit,
            'narration' => "Accounts Payable Accrual - " . $bill['contractor_name'],
            'project_id' => $projectId
        ];

        // 5. Build Header and Save
        $headerData = [
            'journal_number' => $this->journalModel->generateJournalNumber(),
            'date' => date('Y-m-d'),
            'voucher_type' => 'Journal Voucher',
            'narration' => "Contractor Accrual for Bill #" . $billId,
            'total_debit' => $totalDebit,
            'total_credit' => $totalDebit,
            'project_id' => $projectId,
            'office' => 'Site'
        ];

        return $this->journalModel->storeJournal($headerData, $details, $userId);
    }

    /**
     * Hook 4: Expense Adjustment Journal (Return or More Requisition)
     */
    public function generateAdjustmentJournal($txnId, $userId)
    {
        // 1. Fetch transaction and adjustment details
        $stmt = $this->db->prepare("
            SELECT t.*, ea.adjustment_type, ea.project_id, ea.title, u.full_name as employee_name
            FROM adjustment_transactions t
            JOIN expense_adjustments ea ON t.adjustment_id = ea.id
            JOIN users u ON ea.user_id = u.id
            WHERE t.id = ?
        ");
        $stmt->bind_param('i', $txnId);
        $stmt->execute();
        $txn = $stmt->get_result()->fetch_assoc();
        
        if (!$txn) return false;

        $amount = (float)$txn['amount'];
        $projectId = (int)$txn['project_id'];
        $adjType = $txn['adjustment_type']; // 'return' or 'more_requisition'
        
        $cashAccountId = $this->getAccountIdByCode('1110'); // Cash/Bank
        
        // Expense Account: if Project ID exists, use WIP Site Expenses, else use General Admin Expenses
        $expenseAccountId = $projectId > 0 ? $this->getAccountIdByCode('1250') : $this->getAccountIdByCode('6100');

        if (!$cashAccountId || !$expenseAccountId) {
            error_log("JournalIntegrationService: Adjustment accounts missing.");
            return false;
        }

        $details = [];

        if ($adjType === 'return') {
            // Company receives cash back, decreasing the expense
            $details[] = [
                'account_id' => $cashAccountId,
                'debit' => $amount,
                'credit' => 0,
                'narration' => "Cash Returned from " . $txn['employee_name'] . " - " . $txn['title'],
                'project_id' => $projectId
            ];
            $details[] = [
                'account_id' => $expenseAccountId,
                'debit' => 0,
                'credit' => $amount,
                'narration' => "Expense Reversal (Adjustment)",
                'project_id' => $projectId
            ];
        } else {
            // Company pays more cash out, increasing the expense
            $details[] = [
                'account_id' => $expenseAccountId,
                'debit' => $amount,
                'credit' => 0,
                'narration' => "Additional Expense Accrual - " . $txn['title'],
                'project_id' => $projectId
            ];
            $details[] = [
                'account_id' => $cashAccountId,
                'debit' => 0,
                'credit' => $amount,
                'narration' => "Additional Cash Disbursed to " . $txn['employee_name'],
                'project_id' => $projectId
            ];
        }

        // 4. Build Header and Save
        $headerData = [
            'journal_number' => $this->journalModel->generateJournalNumber(),
            'date' => $txn['payment_date'] ?: date('Y-m-d'),
            'voucher_type' => 'Journal Voucher',
            'narration' => ucfirst($adjType) . " Adjustment for Transaction #" . $txnId,
            'total_debit' => $amount,
            'total_credit' => $amount,
            'project_id' => $projectId,
            'office' => $this->getUserWorkplaceOffice($userId)
        ];

        return $this->journalModel->storeJournal($headerData, $details, $userId);
    }

    /**
     * Hook 5: Disbursement Journal (Payment Recognition)
     * Triggered when Accounts processes a payment via Con_FinancialTransaction.
     */
    public function generateDisbursementJournal($txnId, $userId)
    {
        $stmt = $this->db->prepare("
            SELECT t.*, p.name as project_name 
            FROM con_financial_transactions t
            LEFT JOIN projects p ON t.project_id = p.id
            WHERE t.id = ?
        ");
        $stmt->bind_param('i', $txnId);
        $stmt->execute();
        $txn = $stmt->get_result()->fetch_assoc();
        
        if (!$txn) return false;

        $amount = (float)$txn['amount_paid'];
        if ($amount <= 0) return false;

        $projectId = (int)$txn['project_id'];
        $projectName = $txn['project_name'] ?? 'General';
        
        // Fetch Vendor or Contractor Name if available
        $payeeName = '';
        $partyType = null;
        $partyId   = null;

        if ($txn['ref_type'] === 'vendor_payment') {
            $vStmt = $this->db->prepare("
                SELECT vpr.vendor_id, v.name as vendor_name, v.company_name
                FROM vendor_payment_requests vpr
                LEFT JOIN vendors v ON vpr.vendor_id = v.id
                WHERE vpr.id = ?
            ");
            if ($vStmt) {
                $vStmt->bind_param('i', $txn['ref_id']);
                $vStmt->execute();
                $vRes = $vStmt->get_result()->fetch_assoc();
                if ($vRes) {
                    $partyType = 'vendor';
                    $partyId   = (int)$vRes['vendor_id'];
                    if (!empty($vRes['vendor_name'])) {
                        $payeeName = $vRes['vendor_name'];
                    } elseif (!empty($vRes['company_name'])) {
                        $payeeName = $vRes['company_name'];
                    }
                }
            }
        } elseif ($txn['ref_type'] === 'contractor_bill') {
            $cStmt = $this->db->prepare("
                SELECT cbr.contractor_id, c.name as contractor_name
                FROM contractor_bill_requests cbr
                LEFT JOIN contractors c ON cbr.contractor_id = c.id
                WHERE cbr.id = ?
            ");
            if ($cStmt) {
                $cStmt->bind_param('i', $txn['ref_id']);
                $cStmt->execute();
                $cRes = $cStmt->get_result()->fetch_assoc();
                if ($cRes) {
                    $partyType = 'contractor';
                    $partyId   = (int)$cRes['contractor_id'];
                    if (!empty($cRes['contractor_name'])) {
                        $payeeName = $cRes['contractor_name'];
                    }
                }
            }
        }

        // Cash Account (Credit)
        $cashAccountId = $this->getAccountIdByCode('1110'); // Cash and Bank Equivalents

        $apAccountId = null;
        $narrationPrefix = "";

        if ($txn['ref_type'] === 'vendor_payment') {
            $apAccountId = $this->getAccountIdByCode('2110'); // Accounts Payable - Vendors
            $narrationPrefix = "Vendor Payment";
        } elseif ($txn['ref_type'] === 'contractor_bill') {
            $apAccountId = $this->getAccountIdByCode('2120'); // Accounts Payable - Contractors
            $narrationPrefix = "Contractor Payment";
        } elseif ($txn['ref_type'] === 'operational_fund') {
            $apAccountId = $this->getAccountIdByCode('1250'); // Site Expenses (WIP)
            $narrationPrefix = "Operational Fund Disbursement";
        }

        if (!$apAccountId || !$cashAccountId) return false;

        $payeeText = !empty($payeeName) ? "Payee: $payeeName" : ($txn['ref_type'] === 'vendor_payment' ? 'Payee: Vendor' : 'Payee: Contractor');
        $lineNarration = "$payeeText | Project: $projectName | $narrationPrefix | Ref: " . ($txn['reference_number'] ?: 'N/A');

        $details = [];
        
        // Debit AP or Expense
        $details[] = [
            'account_id' => $apAccountId,
            'debit' => $amount,
            'credit' => 0,
            'narration' => $lineNarration,
            'project_id' => $projectId,
            'party_type' => $partyType,
            'party_id' => $partyId
        ];

        // Credit Cash
        $details[] = [
            'account_id' => $cashAccountId,
            'debit' => 0,
            'credit' => $amount,
            'narration' => $lineNarration,
            'project_id' => $projectId,
            'party_type' => $partyType,
            'party_id' => $partyId
        ];

        $headerNarration = !empty($payeeName) ? "$narrationPrefix to $payeeName (Ref: #" . ($txn['reference_number'] ?: $txnId) . ")" : "$narrationPrefix for Transaction #$txnId";

        $headerData = [
            'journal_number' => $this->journalModel->generateJournalNumber(),
            'date' => $txn['transaction_date'] ?: date('Y-m-d'),
            'voucher_type' => 'Payment Voucher',
            'narration' => $headerNarration,
            'total_debit' => $amount,
            'total_credit' => $amount,
            'project_id' => $projectId,
            'office' => $this->getUserWorkplaceOffice($userId)
        ];

        return $this->journalModel->storeJournal($headerData, $details, $userId);
    }

    // Helper to find COA ID by standard code or fallback prefix
    private function getAccountIdByCode($code)
    {
        $stmt = $this->db->prepare("SELECT id FROM chart_of_accounts WHERE account_code = ? LIMIT 1");
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if ($res) return (int)$res['id'];

        $like = $code . '%';
        $stmt = $this->db->prepare("SELECT id FROM chart_of_accounts WHERE account_code LIKE ? ORDER BY account_code ASC LIMIT 1");
        $stmt->bind_param('s', $like);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return $res ? (int)$res['id'] : null;
    }

    /**
     * Helper to get employee workplace/office_branch for office field, default to 'Site Office' if not found
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
                    NULLIF(TRIM(e.office_branch), ''), 
                    NULLIF(TRIM(e.workplace), '')
                ) AS office_branch 
                FROM users u 
                LEFT JOIN employees e ON (u.employee_id = e.id OR e.user_id = u.id) 
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
        return 'Site Office';
    }
}

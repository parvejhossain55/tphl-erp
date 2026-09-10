<?php
/**
 * Con_FinancialTransaction Model
 * Table: con_financial_transactions
 *
 * Schema:
 * id, ref_type (contractor_bill|operational_fund|vendor_payment),
 * ref_id, project_id, amount_paid, payment_type (Advance|Partial|Full|Refund),
 * payment_method (Cash|Cheque|BankTransfer|MobileWallet|Other),
 * transaction_date, reference_number, notes, disbursement_proof,
 * processed_by, update_status, created_by, created_at, updated_at
 */
class Con_FinancialTransaction extends Model
{
    protected $table = 'con_financial_transactions';

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->db = $db ?? $GLOBALS['db'];
    }

    // =========================================================================
    // READ
    // =========================================================================

    /** All transactions with project and processor info */
    public function getAll()
    {
        $sql = "SELECT cft.*,
                       p.name          AS project_name,
                       u.full_name     AS processed_by_name
                FROM {$this->table} cft
                LEFT JOIN projects p ON p.id  = cft.project_id
                LEFT JOIN users    u ON u.id  = cft.processed_by
                ORDER BY cft.created_at DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Find a single transaction */
    public function find($id)
    {
        $stmt = $this->db->prepare(
            "SELECT cft.*,
                    p.name      AS project_name,
                    u.full_name AS processed_by_name
             FROM {$this->table} cft
             LEFT JOIN projects p ON p.id = cft.project_id
             LEFT JOIN users    u ON u.id = cft.processed_by
             WHERE cft.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /** Transactions for a specific referenced record */
    public function getByRef($refType, $refId)
    {
        $stmt = $this->db->prepare(
            "SELECT cft.*, u.full_name AS processed_by_name
             FROM {$this->table} cft
             LEFT JOIN users u ON u.id = cft.processed_by
             WHERE cft.ref_type = ? AND cft.ref_id = ?
             ORDER BY cft.created_at DESC"
        );
        $stmt->bind_param('si', $refType, $refId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Total amount already paid for a specific source record */
    public function getTotalPaidForSource($refType, $refId)
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(amount_paid), 0) AS total_paid
             FROM {$this->table}
             WHERE ref_type = ? AND ref_id = ?"
        );
        $stmt->bind_param('si', $refType, $refId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return (float)($row['total_paid'] ?? 0);
    }

    /**
     * Get approved (but not yet fully paid) records pending payment:
     * - contractor_bill_requests (status IN approved, partially_paid)
     * - operational_fund_requests (status IN approved, partially_paid)
     * - vendor_payment_requests (status IN Submitted, Approved, Partially_Paid)
     */
    public function getPendingApprovals()
    {
        $sql = "SELECT
                    'contractor_bill' AS ref_type,
                    cbr.id            AS ref_id,
                    cbr.project_id,
                    p.name            AS project_name,
                    CONCAT('Contractor Bill #', cbr.id) AS description,
                    COALESCE(cbr.approved_amount, cbr.bill_amount) AS amount,
                    cbr.due_amount,
                    'Full'            AS payment_type,
                    cbr.status        AS source_status,
                    cbr.status        AS vpr_status,
                    cbr.created_at,
                    NULL              AS po_number
                FROM contractor_bill_requests cbr
                LEFT JOIN projects p ON p.id = cbr.project_id
                WHERE cbr.status IN ('submitted', 'verified', 'qc_approved', 'approved', 'partially_paid')

                UNION ALL

                SELECT
                    'operational_fund' AS ref_type,
                    ofr.id             AS ref_id,
                    ofr.project_id,
                    p.name             AS project_name,
                    CONCAT('Fund Request - ', ofr.purpose) AS description,
                    COALESCE(ofr.approved_amount, ofr.amount) AS amount,
                    ofr.due_amount,
                    'Full'             AS payment_type,
                    ofr.status         AS source_status,
                    NULL               AS vpr_status,
                    ofr.created_at,
                    NULL               AS po_number
                FROM operational_fund_requests ofr
                LEFT JOIN projects p ON p.id = ofr.project_id
                WHERE ofr.status IN ('approved', 'partially_paid')

                UNION ALL

                SELECT
                    'vendor_payment'  AS ref_type,
                    vpr.id            AS ref_id,
                    vpr.project_id,
                    p.name            AS project_name,
                    CONCAT(vpr.payment_type, ' Payment — ', v.name) AS description,
                    vpr.net_payable   AS amount,
                    vpr.due_amount,
                    vpr.payment_type  AS payment_type,
                    vpr.status        AS source_status,
                    vpr.status        AS vpr_status,
                    vpr.created_at,
                    vpr.purchase_order_id AS po_number
                FROM vendor_payment_requests vpr
                LEFT JOIN projects p ON p.id  = vpr.project_id
                LEFT JOIN vendors  v ON v.id  = vpr.vendor_id
                WHERE vpr.status IN ('Submitted', 'Approved', 'Partially_Paid')

                ORDER BY created_at DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Project-wise payment summary */
    public function getSummaryByProject($projectId)
    {
        $stmt = $this->db->prepare(
            "SELECT ref_type,
                    COUNT(*) AS transaction_count,
                    SUM(amount_paid) AS total_paid
             FROM {$this->table}
             WHERE project_id = ?
             GROUP BY ref_type"
        );
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================================
    // WRITE
    // =========================================================================

    /**
     * Create a transaction and update source record with partial payment tracking.
     *
     * @param array $data [ref_type, ref_id, project_id, amount, payment_type,
     *                     payment_method, payment_date, reference_no, notes,
     *                     disbursement_proof, processed_by]
     */
    public function create($data)
    {
        $this->db->begin_transaction();
        try {
            $paymentType        = $data['payment_type']       ?? 'Full';
            $refType            = $data['ref_type']            ?? '';
            $refId              = (int)($data['ref_id']        ?? 0);
            $projectId          = (int)($data['project_id']   ?? 0);
            $amount             = (float)($data['amount']     ?? 0);
            $paymentMethod      = $data['payment_method']      ?? 'Cash';
            $paymentDate        = $data['payment_date']        ?? date('Y-m-d');
            $referenceNo        = $data['reference_no']        ?? '';
            $notes              = $data['notes']               ?? '';
            $disbursementProof  = $data['disbursement_proof']  ?? null;
            $processedBy        = (int)($data['processed_by'] ?? 0);

            // 1. Insert transaction record
            $sql = "INSERT INTO {$this->table}
                    (ref_type, ref_id, project_id, amount_paid, payment_type, payment_method,
                     transaction_date, reference_number, notes, disbursement_proof,
                     processed_by, update_status, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Completed', ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param(
                'siidssssssii',
                $refType,
                $refId,
                $projectId,
                $amount,
                $paymentType,
                $paymentMethod,
                $paymentDate,
                $referenceNo,
                $notes,
                $disbursementProof,
                $processedBy,
                $processedBy
            );
            if (!$stmt->execute()) {
                throw new \Exception("Insert failed: " . $stmt->error);
            }
            $txnId = $stmt->insert_id;

            // 2. Calculate total paid so far for this source
            $totalPaid = $this->getTotalPaidForSource($refType, $refId);

            // 3. Determine the approved/payable amount from the source
            $sourceAmount = $this->getSourceAmount($refType, $refId);

            // 4. Calculate due amount and determine new status
            $dueAmount = max(0, $sourceAmount - $totalPaid);

            if ($dueAmount <= 0) {
                $newStatus = $this->getFullyPaidStatus($refType);
            } else {
                $newStatus = $this->getPartiallyPaidStatus($refType);
            }

            // 5. Update source record with new status and due_amount
            $this->updateSourceRecord($refType, $refId, $newStatus, $dueAmount);

            // 6. Generate Disbursement Journal Entry
            require_once __DIR__ . '/../services/JournalIntegrationService.php';
            $journalService = new \Services\JournalIntegrationService($this->db);
            $journalService->generateDisbursementJournal($txnId, $processedBy);

            $this->db->commit();
            return $txnId;
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Financial transaction create failed: " . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // HELPERS (private)
    // =========================================================================

    /** Get the approved/payable amount from the source record */
    private function getSourceAmount($refType, $refId)
    {
        if ($refType === 'contractor_bill') {
            $stmt = $this->db->prepare(
                "SELECT COALESCE(approved_amount, bill_amount) AS payable FROM contractor_bill_requests WHERE id = ?"
            );
        } elseif ($refType === 'operational_fund') {
            $stmt = $this->db->prepare(
                "SELECT COALESCE(approved_amount, amount) AS payable FROM operational_fund_requests WHERE id = ?"
            );
        } elseif ($refType === 'vendor_payment') {
            $stmt = $this->db->prepare(
                "SELECT net_payable AS payable FROM vendor_payment_requests WHERE id = ?"
            );
        } else {
            return 0;
        }

        $stmt->bind_param('i', $refId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return (float)($row['payable'] ?? 0);
    }

    /** Get the 'fully_paid' status string for each source type */
    private function getFullyPaidStatus($refType)
    {
        return match ($refType) {
            'vendor_payment' => 'Fully_Paid',
            default          => 'fully_paid',
        };
    }

    /** Get the 'partially_paid' status string for each source type */
    private function getPartiallyPaidStatus($refType)
    {
        return match ($refType) {
            'vendor_payment' => 'Partially_Paid',
            default          => 'partially_paid',
        };
    }

    /** Update source record status and due_amount */
    private function updateSourceRecord($refType, $refId, $status, $dueAmount)
    {
        if ($refType === 'contractor_bill') {
            $stmt = $this->db->prepare(
                "UPDATE contractor_bill_requests SET status = ?, due_amount = ? WHERE id = ?"
            );
        } elseif ($refType === 'operational_fund') {
            $stmt = $this->db->prepare(
                "UPDATE operational_fund_requests SET status = ?, due_amount = ? WHERE id = ?"
            );
        } elseif ($refType === 'vendor_payment') {
            $stmt = $this->db->prepare(
                "UPDATE vendor_payment_requests SET status = ?, due_amount = ? WHERE id = ?"
            );
        } else {
            return;
        }

        $stmt->bind_param('sdi', $status, $dueAmount, $refId);
        $stmt->execute();
    }

    /**
     * Supported Expense Modules List
     */
    public function getExpenseModulesList()
    {
        return [
            'vendor_payment'      => ['label' => 'Vendor Payments', 'badge' => 'primary', 'icon' => 'bi bi-truck'],
            'contractor_bill'     => ['label' => 'Contractor Bills', 'badge' => 'warning', 'icon' => 'bi bi-hammer'],
            'operational_fund'    => ['label' => 'Operational Funds', 'badge' => 'info', 'icon' => 'bi bi-cash-stack'],
            'fund_requisition'    => ['label' => 'Fund Requisitions / Petty Cash', 'badge' => 'success', 'icon' => 'bi bi-wallet2'],
            'project_other_cost'  => ['label' => 'Project Other Costs', 'badge' => 'warning', 'icon' => 'bi bi-receipt'],
            'office_procurement'  => ['label' => 'Office Procurement Requests', 'badge' => 'purple', 'icon' => 'bi bi-cart3'],
            'labor_requisition'   => ['label' => 'SE Labor Bills', 'badge' => 'info', 'icon' => 'bi bi-people'],
            'cashout_request'     => ['label' => 'Cashout Requests', 'badge' => 'danger', 'icon' => 'bi bi-credit-card'],
            'advance_salary'      => ['label' => 'Advance Salary Requests', 'badge' => 'secondary', 'icon' => 'bi bi-cash'],
            'payslip'             => ['label' => 'Salary / Payslips', 'badge' => 'dark', 'icon' => 'bi bi-file-earmark-person'],
            'sales_incentive'     => ['label' => 'Sales Incentive Claims', 'badge' => 'success', 'icon' => 'bi bi-award'],
            'client_refund'       => ['label' => 'Client Refund Requests', 'badge' => 'danger', 'icon' => 'bi bi-arrow-counterclockwise']
        ];
    }

    /**
     * Unified Expense Transactions History
     * Gathers all company expense payouts across modules with requester, paid date, amount, and filters
     */
    public function getAllExpenseTransactions($filters = [])
    {
        $targetModule  = !empty($filters['module']) && $filters['module'] !== 'all' ? $filters['module'] : null;
        $projectId     = !empty($filters['project_id']) && $filters['project_id'] !== 'all' ? (int)$filters['project_id'] : null;
        $startDate     = !empty($filters['start_date']) ? $filters['start_date'] : null;
        $endDate       = !empty($filters['end_date']) ? $filters['end_date'] : null;
        $paymentMethod = !empty($filters['payment_method']) && $filters['payment_method'] !== 'all' ? $filters['payment_method'] : null;
        $keyword       = !empty($filters['search']) ? trim($filters['search']) : null;

        $allTxns = [];

        $runSafe = function($sql) {
            $res = $this->db->query($sql);
            return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        };

        // 1. Vendor Payments
        if (!$targetModule || $targetModule === 'vendor_payment') {
            $where = "cft.ref_type = 'vendor_payment'";
            if ($projectId) $where .= " AND cft.project_id = $projectId";
            if ($startDate) $where .= " AND DATE(cft.transaction_date) >= '{$this->db->real_escape_string($startDate)}'";
            if ($endDate)   $where .= " AND DATE(cft.transaction_date) <= '{$this->db->real_escape_string($endDate)}'";
            if ($paymentMethod) $where .= " AND cft.payment_method = '{$this->db->real_escape_string($paymentMethod)}'";

            $sql = "SELECT 
                        cft.id as txn_id,
                        'vendor_payment' as module_key,
                        'Vendor Payment' as module_label,
                        'primary' as module_badge,
                        'bi bi-truck' as module_icon,
                        cft.ref_id as source_id,
                        cft.project_id,
                        COALESCE(p.name, '—') as project_name,
                        cft.amount_paid,
                        DATE(cft.transaction_date) as payment_date,
                        COALESCE(cft.payment_method, 'Cash') as payment_method,
                        COALESCE(cft.reference_number, '—') as reference_number,
                        COALESCE(vpr.created_by, 0) as requested_by_id,
                        COALESCE(u_req.full_name, '—') as requested_by_name,
                        COALESCE(r_req.name, 'Requester') as requested_by_role,
                        COALESCE(cft.processed_by, 0) as processed_by_id,
                        COALESCE(u_proc.full_name, 'Accounts') as processed_by_name,
                        CONCAT('con-vendor-payment-show/', cft.ref_id) as view_url,
                        cft.notes
                    FROM con_financial_transactions cft
                    LEFT JOIN vendor_payment_requests vpr ON vpr.id = cft.ref_id AND cft.ref_type = 'vendor_payment'
                    LEFT JOIN projects p ON p.id = cft.project_id
                    LEFT JOIN users u_req ON u_req.id = vpr.created_by
                    LEFT JOIN roles r_req ON r_req.id = u_req.role_id
                    LEFT JOIN users u_proc ON u_proc.id = cft.processed_by
                    WHERE {$where}";
            $allTxns = array_merge($allTxns, $runSafe($sql));
        }

        // 2. Contractor Bills
        if (!$targetModule || $targetModule === 'contractor_bill') {
            $where = "cft.ref_type = 'contractor_bill'";
            if ($projectId) $where .= " AND cft.project_id = $projectId";
            if ($startDate) $where .= " AND DATE(cft.transaction_date) >= '{$this->db->real_escape_string($startDate)}'";
            if ($endDate)   $where .= " AND DATE(cft.transaction_date) <= '{$this->db->real_escape_string($endDate)}'";
            if ($paymentMethod) $where .= " AND cft.payment_method = '{$this->db->real_escape_string($paymentMethod)}'";

            $sql = "SELECT 
                        cft.id as txn_id,
                        'contractor_bill' as module_key,
                        'Contractor Bill' as module_label,
                        'warning' as module_badge,
                        'bi bi-hammer' as module_icon,
                        cft.ref_id as source_id,
                        cft.project_id,
                        COALESCE(p.name, '—') as project_name,
                        cft.amount_paid,
                        DATE(cft.transaction_date) as payment_date,
                        COALESCE(cft.payment_method, 'Cash') as payment_method,
                        COALESCE(cft.reference_number, '—') as reference_number,
                        COALESCE(cbr.created_by, 0) as requested_by_id,
                        COALESCE(u_req.full_name, '—') as requested_by_name,
                        COALESCE(r_req.name, 'Requester') as requested_by_role,
                        COALESCE(cft.processed_by, 0) as processed_by_id,
                        COALESCE(u_proc.full_name, 'Accounts') as processed_by_name,
                        CONCAT('con-contractor-bill/', cft.ref_id) as view_url,
                        cft.notes
                    FROM con_financial_transactions cft
                    LEFT JOIN contractor_bill_requests cbr ON cbr.id = cft.ref_id AND cft.ref_type = 'contractor_bill'
                    LEFT JOIN projects p ON p.id = cft.project_id
                    LEFT JOIN users u_req ON u_req.id = cbr.created_by
                    LEFT JOIN roles r_req ON r_req.id = u_req.role_id
                    LEFT JOIN users u_proc ON u_proc.id = cft.processed_by
                    WHERE {$where}";
            $allTxns = array_merge($allTxns, $runSafe($sql));
        }

        // 3. Operational Funds
        if (!$targetModule || $targetModule === 'operational_fund') {
            $where = "cft.ref_type = 'operational_fund'";
            if ($projectId) $where .= " AND cft.project_id = $projectId";
            if ($startDate) $where .= " AND DATE(cft.transaction_date) >= '{$this->db->real_escape_string($startDate)}'";
            if ($endDate)   $where .= " AND DATE(cft.transaction_date) <= '{$this->db->real_escape_string($endDate)}'";
            if ($paymentMethod) $where .= " AND cft.payment_method = '{$this->db->real_escape_string($paymentMethod)}'";

            $sql = "SELECT 
                        cft.id as txn_id,
                        'operational_fund' as module_key,
                        'Operational Fund' as module_label,
                        'info' as module_badge,
                        'bi bi-cash-stack' as module_icon,
                        cft.ref_id as source_id,
                        cft.project_id,
                        COALESCE(p.name, '—') as project_name,
                        cft.amount_paid,
                        DATE(cft.transaction_date) as payment_date,
                        COALESCE(cft.payment_method, 'Cash') as payment_method,
                        COALESCE(cft.reference_number, '—') as reference_number,
                        COALESCE(cft.created_by, 0) as requested_by_id,
                        COALESCE(u_req.full_name, '—') as requested_by_name,
                        COALESCE(r_req.name, 'Requester') as requested_by_role,
                        COALESCE(cft.processed_by, 0) as processed_by_id,
                        COALESCE(u_proc.full_name, 'Accounts') as processed_by_name,
                        CONCAT('con-transaction/', cft.id) as view_url,
                        cft.notes
                    FROM con_financial_transactions cft
                    LEFT JOIN projects p ON p.id = cft.project_id
                    LEFT JOIN users u_req ON u_req.id = cft.created_by
                    LEFT JOIN roles r_req ON r_req.id = u_req.role_id
                    LEFT JOIN users u_proc ON u_proc.id = cft.processed_by
                    WHERE {$where}";
            $allTxns = array_merge($allTxns, $runSafe($sql));
        }

        // 4. Fund Requisitions (petty cash + general)
        if (!$targetModule || $targetModule === 'fund_requisition') {
            $where = "fr.status IN ('paid', 'partially_paid')";
            if ($projectId) $where .= " AND fr.project_id = $projectId";
            if ($startDate) $where .= " AND DATE(COALESCE(fr.paid_at, fr.updated_at)) >= '{$this->db->real_escape_string($startDate)}'";
            if ($endDate)   $where .= " AND DATE(COALESCE(fr.paid_at, fr.updated_at)) <= '{$this->db->real_escape_string($endDate)}'";
            if ($paymentMethod) $where .= " AND fr.payment_method LIKE '%{$this->db->real_escape_string($paymentMethod)}%'";

            $sql = "SELECT 
                        fr.id as txn_id,
                        'fund_requisition' as module_key,
                        'Fund Requisition' as module_label,
                        'success' as module_badge,
                        'bi bi-wallet2' as module_icon,
                        fr.id as source_id,
                        fr.project_id,
                        COALESCE(p.name, 'Head Office') as project_name,
                        COALESCE(NULLIF(fr.paid_amount, 0), fr.amount) as amount_paid,
                        DATE(COALESCE(fr.paid_at, fr.updated_at)) as payment_date,
                        COALESCE(fr.payment_method, 'Cash') as payment_method,
                        COALESCE(NULLIF(fr.category, ''), 'Fund Req') as reference_number,
                        COALESCE(fr.requested_by, 0) as requested_by_id,
                        COALESCE(u_req.full_name, '—') as requested_by_name,
                        COALESCE(r_req.name, 'Requester') as requested_by_role,
                        COALESCE(fr.paid_by, 0) as processed_by_id,
                        COALESCE(u_proc.full_name, 'Accounts') as processed_by_name,
                        CONCAT('fund-requisition/', fr.id) as view_url,
                        fr.purpose as notes
                    FROM fund_requisitions fr
                    LEFT JOIN projects p ON p.id = fr.project_id
                    LEFT JOIN users u_req ON u_req.id = fr.requested_by
                    LEFT JOIN roles r_req ON r_req.id = u_req.role_id
                    LEFT JOIN users u_proc ON u_proc.id = fr.paid_by
                    WHERE {$where}";
            $allTxns = array_merge($allTxns, $runSafe($sql));
        }

        // 5. Project Other Costs
        if (!$targetModule || $targetModule === 'project_other_cost') {
            $where = "poc.status = 'paid'";
            if ($projectId) $where .= " AND poc.project_id = $projectId";
            if ($startDate) $where .= " AND DATE(COALESCE(poc.payment_at, poc.updated_at)) >= '{$this->db->real_escape_string($startDate)}'";
            if ($endDate)   $where .= " AND DATE(COALESCE(poc.payment_at, poc.updated_at)) <= '{$this->db->real_escape_string($endDate)}'";

            $sql = "SELECT 
                        poc.id as txn_id,
                        'project_other_cost' as module_key,
                        'Project Other Cost' as module_label,
                        'warning' as module_badge,
                        'bi bi-receipt' as module_icon,
                        poc.id as source_id,
                        poc.project_id,
                        COALESCE(p.name, '—') as project_name,
                        COALESCE(NULLIF(poc.payment_amount, 0), NULLIF(poc.approved_amount, 0), poc.request_amount) as amount_paid,
                        DATE(COALESCE(poc.payment_at, poc.updated_at)) as payment_date,
                        'Cash' as payment_method,
                        COALESCE(NULLIF(poc.rcost_type, ''), 'Other Cost') as reference_number,
                        COALESCE(poc.created_by_id, 0) as requested_by_id,
                        COALESCE(u_req.full_name, '—') as requested_by_name,
                        COALESCE(r_req.name, 'Site Engineer') as requested_by_role,
                        COALESCE(poc.payment_by_id, 0) as processed_by_id,
                        COALESCE(u_proc.full_name, 'Accounts') as processed_by_name,
                        'project-other-costs' as view_url,
                        poc.purpose as notes
                    FROM project_other_costs poc
                    LEFT JOIN projects p ON p.id = poc.project_id
                    LEFT JOIN users u_req ON u_req.id = poc.created_by_id
                    LEFT JOIN roles r_req ON r_req.id = u_req.role_id
                    LEFT JOIN users u_proc ON u_proc.id = poc.payment_by_id
                    WHERE {$where}";
            $allTxns = array_merge($allTxns, $runSafe($sql));
        }

        // 6. Office Procurement Requests
        if (!$targetModule || $targetModule === 'office_procurement') {
            $where = "(pea.payment_status = 'paid' OR pea.overall_status = 'paid')";
            if ($projectId) $where .= " AND pea.project_id = $projectId";
            if ($startDate) $where .= " AND DATE(COALESCE(pea.paid_at, pea.updated_at)) >= '{$this->db->real_escape_string($startDate)}'";
            if ($endDate)   $where .= " AND DATE(COALESCE(pea.paid_at, pea.updated_at)) <= '{$this->db->real_escape_string($endDate)}'";
            if ($paymentMethod) $where .= " AND pea.payment_method LIKE '%{$this->db->real_escape_string($paymentMethod)}%'";

            $sql = "SELECT 
                        pea.id as txn_id,
                        'office_procurement' as module_key,
                        'Office Procurement' as module_label,
                        'purple' as module_badge,
                        'bi bi-cart3' as module_icon,
                        pea.id as source_id,
                        pea.project_id,
                        COALESCE(p.name, 'Head Office') as project_name,
                        COALESCE(NULLIF(pea.paid_amount, 0), pea.total_price) as amount_paid,
                        DATE(COALESCE(pea.paid_at, pea.updated_at)) as payment_date,
                        COALESCE(pea.payment_method, 'Cash') as payment_method,
                        COALESCE(NULLIF(pea.invoice_no, ''), pea.item_name) as reference_number,
                        COALESCE(pea.requested_by, 0) as requested_by_id,
                        COALESCE(u_req.full_name, '—') as requested_by_name,
                        COALESCE(r_req.name, 'Requester') as requested_by_role,
                        COALESCE(pea.paid_by, 0) as processed_by_id,
                        COALESCE(u_proc.full_name, 'Accounts') as processed_by_name,
                        'asset-extra-purchases' as view_url,
                        pea.justification as notes
                    FROM project_extra_assets pea
                    LEFT JOIN projects p ON p.id = pea.project_id
                    LEFT JOIN users u_req ON u_req.id = pea.requested_by
                    LEFT JOIN roles r_req ON r_req.id = u_req.role_id
                    LEFT JOIN users u_proc ON u_proc.id = pea.paid_by
                    WHERE {$where}";
            $allTxns = array_merge($allTxns, $runSafe($sql));
        }

        // 7. SE Labor Bills
        if (!$targetModule || $targetModule === 'labor_requisition') {
            $where = "clr.status IN ('paid', 'partially_paid', 'labor_disbursed')";
            if ($projectId) $where .= " AND clr.project_id = $projectId";
            if ($startDate) $where .= " AND DATE(COALESCE(clr.payment_date, clr.labor_disbursed_at, clr.updated_at)) >= '{$this->db->real_escape_string($startDate)}'";
            if ($endDate)   $where .= " AND DATE(COALESCE(clr.payment_date, clr.labor_disbursed_at, clr.updated_at)) <= '{$this->db->real_escape_string($endDate)}'";
            if ($paymentMethod) $where .= " AND clr.payment_method LIKE '%{$this->db->real_escape_string($paymentMethod)}%'";

            $sql = "SELECT 
                        clr.id as txn_id,
                        'labor_requisition' as module_key,
                        'SE Labor Bill' as module_label,
                        'info' as module_badge,
                        'bi bi-people' as module_icon,
                        clr.id as source_id,
                        clr.project_id,
                        COALESCE(p.name, '—') as project_name,
                        COALESCE(NULLIF(clr.total_paid, 0), NULLIF(clr.paid_amount, 0), clr.approved_amount) as amount_paid,
                        DATE(COALESCE(clr.payment_date, clr.labor_disbursed_at, clr.updated_at)) as payment_date,
                        COALESCE(clr.payment_method, 'Cash') as payment_method,
                        COALESCE(NULLIF(clr.requisition_no, ''), 'Labor Bill') as reference_number,
                        COALESCE(clr.requested_by, 0) as requested_by_id,
                        COALESCE(u_req.full_name, '—') as requested_by_name,
                        COALESCE(r_req.name, 'Site Engineer') as requested_by_role,
                        COALESCE(clr.paid_by, clr.labor_disbursed_by, 0) as processed_by_id,
                        COALESCE(u_proc.full_name, 'Accounts') as processed_by_name,
                        'con-labor-requisitions' as view_url,
                        clr.purpose as notes
                    FROM con_labor_requisitions clr
                    LEFT JOIN projects p ON p.id = clr.project_id
                    LEFT JOIN users u_req ON u_req.id = clr.requested_by
                    LEFT JOIN roles r_req ON r_req.id = u_req.role_id
                    LEFT JOIN users u_proc ON u_proc.id = COALESCE(clr.paid_by, clr.labor_disbursed_by)
                    WHERE {$where}";
            $allTxns = array_merge($allTxns, $runSafe($sql));
        }

        // 8. Cashout Payments
        if (!$targetModule || $targetModule === 'cashout_request') {
            $where = "1=1";
            if ($projectId) $where .= " AND (cr.project_id = $projectId OR cp.project_id = '$projectId')";
            if ($startDate) $where .= " AND DATE(COALESCE(cp.payment_date, cp.created_at)) >= '{$this->db->real_escape_string($startDate)}'";
            if ($endDate)   $where .= " AND DATE(COALESCE(cp.payment_date, cp.created_at)) <= '{$this->db->real_escape_string($endDate)}'";
            if ($paymentMethod) $where .= " AND cp.payment_method LIKE '%{$this->db->real_escape_string($paymentMethod)}%'";

            $sql = "SELECT 
                        cp.id as txn_id,
                        'cashout_request' as module_key,
                        'Cashout Request' as module_label,
                        'danger' as module_badge,
                        'bi bi-credit-card' as module_icon,
                        cp.cashout_request_id as source_id,
                        cr.project_id,
                        COALESCE(p.name, cp.office, 'Head Office') as project_name,
                        cp.amount as amount_paid,
                        DATE(COALESCE(cp.payment_date, cp.created_at)) as payment_date,
                        COALESCE(cp.payment_method, 'Bank Transfer') as payment_method,
                        COALESCE(NULLIF(cp.cheque_no, ''), NULLIF(cp.transaction_ref, ''), 'Cashout') as reference_number,
                        COALESCE(cr.requested_by, 0) as requested_by_id,
                        COALESCE(u_req.full_name, '—') as requested_by_name,
                        COALESCE(r_req.name, 'Requester') as requested_by_role,
                        COALESCE(cp.created_by, 0) as processed_by_id,
                        COALESCE(u_proc.full_name, 'Accounts') as processed_by_name,
                        'cashout-requests' as view_url,
                        cp.purpose_paid_to as notes
                    FROM cashout_payments cp
                    LEFT JOIN cashout_requests cr ON cr.id = cp.cashout_request_id
                    LEFT JOIN projects p ON p.id = cr.project_id
                    LEFT JOIN users u_req ON u_req.id = cr.requested_by
                    LEFT JOIN roles r_req ON r_req.id = u_req.role_id
                    LEFT JOIN users u_proc ON u_proc.id = cp.created_by
                    WHERE {$where}";
            $allTxns = array_merge($allTxns, $runSafe($sql));
        }

        // 9. Advance Salary Requests
        if (!$targetModule || $targetModule === 'advance_salary') {
            if (!$projectId) { // Advance salaries belong to HQ / employees
                $where = "ads.status = 'paid'";
                if ($startDate) $where .= " AND DATE(COALESCE(ads.approved_date, ads.updated_at)) >= '{$this->db->real_escape_string($startDate)}'";
                if ($endDate)   $where .= " AND DATE(COALESCE(ads.approved_date, ads.updated_at)) <= '{$this->db->real_escape_string($endDate)}'";

                $sql = "SELECT 
                            ads.id as txn_id,
                            'advance_salary' as module_key,
                            'Advance Salary' as module_label,
                            'secondary' as module_badge,
                            'bi bi-cash' as module_icon,
                            ads.id as source_id,
                            NULL as project_id,
                            'Head Office' as project_name,
                            ads.amount as amount_paid,
                            DATE(COALESCE(ads.approved_date, ads.updated_at)) as payment_date,
                            'Bank Transfer' as payment_method,
                            CONCAT('Adv: ', COALESCE(ads.deduction_start_month, '')) as reference_number,
                            COALESCE(emp.user_id, ads.created_by, 0) as requested_by_id,
                            COALESCE(u_req.full_name, '—') as requested_by_name,
                            COALESCE(r_req.name, 'Employee') as requested_by_role,
                            0 as processed_by_id,
                            'Accounts' as processed_by_name,
                            'advance-salaries' as view_url,
                            ads.reason as notes
                        FROM advance_salaries ads
                        LEFT JOIN employees emp ON emp.id = ads.employee_id
                        LEFT JOIN users u_req ON u_req.id = emp.user_id
                        LEFT JOIN roles r_req ON r_req.id = u_req.role_id
                        WHERE {$where}";
                $allTxns = array_merge($allTxns, $runSafe($sql));
            }
        }

        // 10. Payslips & Salaries
        if (!$targetModule || $targetModule === 'payslip') {
            $where = "ps.status = 'paid'";
            if ($projectId) $where .= " AND ps.payroll_project_id = $projectId";
            if ($startDate) $where .= " AND DATE(COALESCE(ps.disbursed_at, ps.payslip_month)) >= '{$this->db->real_escape_string($startDate)}'";
            if ($endDate)   $where .= " AND DATE(COALESCE(ps.disbursed_at, ps.payslip_month)) <= '{$this->db->real_escape_string($endDate)}'";
            if ($paymentMethod) $where .= " AND ps.payment_method LIKE '%{$this->db->real_escape_string($paymentMethod)}%'";

            $sql = "SELECT 
                        ps.id as txn_id,
                        'payslip' as module_key,
                        'Salary / Payslip' as module_label,
                        'dark' as module_badge,
                        'bi bi-file-earmark-person' as module_icon,
                        ps.id as source_id,
                        ps.payroll_project_id as project_id,
                        COALESCE(p.name, 'Head Office') as project_name,
                        ps.net_salary as amount_paid,
                        DATE(COALESCE(ps.disbursed_at, ps.payslip_month)) as payment_date,
                        COALESCE(ps.payment_method, 'bank') as payment_method,
                        CONCAT('Month: ', DATE_FORMAT(ps.payslip_month, '%b %Y')) as reference_number,
                        COALESCE(emp.user_id, 0) as requested_by_id,
                        COALESCE(u_req.full_name, '—') as requested_by_name,
                        COALESCE(r_req.name, 'Staff') as requested_by_role,
                        COALESCE(ps.disbursed_by, 0) as processed_by_id,
                        COALESCE(u_proc.full_name, 'Accounts') as processed_by_name,
                        'payslip-generate' as view_url,
                        ps.remarks as notes
                    FROM payslips ps
                    LEFT JOIN employees emp ON emp.id = ps.employee_id
                    LEFT JOIN users u_req ON u_req.id = emp.user_id
                    LEFT JOIN roles r_req ON r_req.id = u_req.role_id
                    LEFT JOIN projects p ON p.id = ps.payroll_project_id
                    LEFT JOIN users u_proc ON u_proc.id = ps.disbursed_by
                    WHERE {$where}";
            $allTxns = array_merge($allTxns, $runSafe($sql));
        }

        // 11. Sales Incentive Claims
        if (!$targetModule || $targetModule === 'sales_incentive') {
            if (!$projectId) {
                $where = "1=1";
                if ($startDate) $where .= " AND DATE(COALESCE(pip.payment_date, pip.created_at)) >= '{$this->db->real_escape_string($startDate)}'";
                if ($endDate)   $where .= " AND DATE(COALESCE(pip.payment_date, pip.created_at)) <= '{$this->db->real_escape_string($endDate)}'";
                if ($paymentMethod) $where .= " AND pip.payment_method LIKE '%{$this->db->real_escape_string($paymentMethod)}%'";

                $sql = "SELECT 
                            pip.id as txn_id,
                            'sales_incentive' as module_key,
                            'Sales Incentive' as module_label,
                            'success' as module_badge,
                            'bi bi-award' as module_icon,
                            pip.claim_id as source_id,
                            NULL as project_id,
                            'Sales Department' as project_name,
                            pip.payment_amount as amount_paid,
                            DATE(COALESCE(pip.payment_date, pip.created_at)) as payment_date,
                            COALESCE(pip.payment_method, 'Bank Transfer') as payment_method,
                            COALESCE(NULLIF(pip.transaction_ref, ''), 'Incentive') as reference_number,
                            COALESCE(pip.user_id, 0) as requested_by_id,
                            COALESCE(u_req.full_name, '—') as requested_by_name,
                            COALESCE(r_req.name, 'Sales Executive') as requested_by_role,
                            COALESCE(pip.disbursed_by, 0) as processed_by_id,
                            COALESCE(u_proc.full_name, 'Accounts') as processed_by_name,
                            'incentive-management' as view_url,
                            pip.notes
                        FROM project_incentive_payments pip
                        LEFT JOIN users u_req ON u_req.id = pip.user_id
                        LEFT JOIN roles r_req ON r_req.id = u_req.role_id
                        LEFT JOIN users u_proc ON u_proc.id = pip.disbursed_by
                        WHERE {$where}";
                $allTxns = array_merge($allTxns, $runSafe($sql));
            }
        }

        // 12. Client Refund Requests
        if (!$targetModule || $targetModule === 'client_refund') {
            $where = "crr.status = 'refunded'";
            if ($projectId) $where .= " AND crr.project_id = $projectId";
            if ($startDate) $where .= " AND DATE(COALESCE(crr.refunded_at, crr.updated_at)) >= '{$this->db->real_escape_string($startDate)}'";
            if ($endDate)   $where .= " AND DATE(COALESCE(crr.refunded_at, crr.updated_at)) <= '{$this->db->real_escape_string($endDate)}'";

            $sql = "SELECT 
                        crr.id as txn_id,
                        'client_refund' as module_key,
                        'Client Refund' as module_label,
                        'danger' as module_badge,
                        'bi bi-arrow-counterclockwise' as module_icon,
                        crr.id as source_id,
                        crr.project_id,
                        COALESCE(p.name, '—') as project_name,
                        crr.refund_amount as amount_paid,
                        DATE(COALESCE(crr.refunded_at, crr.updated_at)) as payment_date,
                        'Bank Transfer' as payment_method,
                        CONCAT('Refund #', crr.id) as reference_number,
                        COALESCE(crr.requested_by, 0) as requested_by_id,
                        COALESCE(u_req.full_name, '—') as requested_by_name,
                        COALESCE(r_req.name, 'Requester') as requested_by_role,
                        COALESCE(crr.approved_by, 0) as processed_by_id,
                        COALESCE(u_proc.full_name, 'Accounts') as processed_by_name,
                        'client-refunds' as view_url,
                        crr.reason as notes
                    FROM client_refund_requests crr
                    LEFT JOIN projects p ON p.id = crr.project_id
                    LEFT JOIN users u_req ON u_req.id = crr.requested_by
                    LEFT JOIN roles r_req ON r_req.id = u_req.role_id
                    LEFT JOIN users u_proc ON u_proc.id = crr.approved_by
                    WHERE {$where}";
            $allTxns = array_merge($allTxns, $runSafe($sql));
        }

        // Filter by keyword if provided
        if (!empty($keyword)) {
            $kw = mb_strtolower($keyword);
            $allTxns = array_filter($allTxns, function($row) use ($kw) {
                return (
                    str_contains(mb_strtolower($row['requested_by_name'] ?? ''), $kw) ||
                    str_contains(mb_strtolower($row['project_name'] ?? ''), $kw) ||
                    str_contains(mb_strtolower($row['reference_number'] ?? ''), $kw) ||
                    str_contains(mb_strtolower($row['module_label'] ?? ''), $kw) ||
                    str_contains(mb_strtolower($row['notes'] ?? ''), $kw) ||
                    str_contains(mb_strtolower($row['processed_by_name'] ?? ''), $kw) ||
                    str_contains((string)($row['amount_paid'] ?? ''), $kw)
                );
            });
        }

        // Sort chronologically: newest payment date first
        usort($allTxns, function($a, $b) {
            $dateA = !empty($a['payment_date']) ? $a['payment_date'] : '1970-01-01';
            $dateB = !empty($b['payment_date']) ? $b['payment_date'] : '1970-01-01';
            return strcmp($dateB, $dateA);
        });

        return $allTxns;
    }
}


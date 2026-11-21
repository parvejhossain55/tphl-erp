<?php

class CentralStatementV2 extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Get active projects list
     */
    public function getProjects(): array
    {
        $rows = [];
        $res = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC");
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
        }
        return $rows;
    }

    /**
     * Get active office branches list
     */
    public function getBranches(): array
    {
        $rows = [];
        $res = $this->db->query("SELECT id, name FROM office_branches WHERE status = 'active' ORDER BY id ASC");
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
        }
        return $rows;
    }

    /**
     * Earliest and latest transaction dates across verified collections & paid cashouts
     */
    public function getDateBounds(): array
    {
        $minDate = date('Y-m-01');
        $maxDate = date('Y-m-d');

        $q = "
            SELECT MIN(d) AS min_d, MAX(d) AS max_d FROM (
                SELECT MIN(DATE(payment_date)) AS d FROM lead_payments WHERE is_trashed = 0 AND verification_status = 'verified'
                UNION ALL
                SELECT MAX(DATE(payment_date)) AS d FROM lead_payments WHERE is_trashed = 0 AND verification_status = 'verified'
                UNION ALL
                SELECT MIN(DATE(transaction_date)) AS d FROM con_financial_transactions
                UNION ALL
                SELECT MAX(DATE(transaction_date)) AS d FROM con_financial_transactions
                UNION ALL
                SELECT MIN(DATE(paid_at)) AS d FROM fund_requisitions WHERE status = 'paid'
                UNION ALL
                SELECT MAX(DATE(paid_at)) AS d FROM fund_requisitions WHERE status = 'paid'
            ) t WHERE d IS NOT NULL AND d >= '2020-01-01'
        ";
        $res = $this->db->query($q);
        if ($res && ($row = $res->fetch_assoc())) {
            if (!empty($row['min_d'])) $minDate = $row['min_d'];
            if (!empty($row['max_d'])) $maxDate = $row['max_d'];
        }
        return [$minDate, $maxDate];
    }

    /**
     * Category metadata for filters & display
     */
    public function getCategoriesMeta(): array
    {
        return [
            'booking_money' => ['label' => 'Booking Money', 'type' => 'cash_in'],
            'emi' => ['label' => 'EMI', 'type' => 'cash_in'],
            'registration' => ['label' => 'Registration Fee', 'type' => 'cash_in'],
            'development_charge' => ['label' => 'Development Charge', 'type' => 'cash_in'],
            'land_share' => ['label' => 'Land Share', 'type' => 'cash_in'],
            'shop_service_charge' => ['label' => 'Shop Service Charge', 'type' => 'cash_in'],
            'other_receipts' => ['label' => 'Other Receipts', 'type' => 'cash_in'],
            'management_cashin' => ['label' => 'Management Cash In', 'type' => 'cash_in'],
            'expense_adjustment_cashin' => ['label' => 'Expense Adjustment (Return)', 'type' => 'cash_in'],
            'vendor_payment' => ['label' => 'Vendor Payments', 'type' => 'cash_out'],
            'contractor_bill' => ['label' => 'Contractor Bills', 'type' => 'cash_out'],
            'office_procurement_assets' => ['label' => 'Procurement & Assets', 'type' => 'cash_out'],
            'fund_requisitions' => ['label' => 'Fund Requisitions', 'type' => 'cash_out'],
            'land_fund_requisitions' => ['label' => 'Land Fund Requisitions', 'type' => 'cash_out'],
            'payroll' => ['label' => 'Payroll & Salary', 'type' => 'cash_out'],
            'cashout' => ['label' => 'Cashout & Utilities', 'type' => 'cash_out'],
            'expense_adjustment' => ['label' => 'Expense Adjustments', 'type' => 'cash_out'],
        ];
    }

    /**
     * Normalize payment_method into 'cash' or 'bank'
     */
    private function normalizePaymentModeSql(string $col): string
    {
        return "CASE WHEN LOWER(COALESCE($col, 'cash')) = 'cash' THEN 'cash' ELSE 'bank' END";
    }

    /**
     * Build unified SQL UNION query of all verified Cash-In and paid Cash-Out transactions
     * within [$startDate, $endDate] with normalized columns:
     * txn_date, flow_type ('cash_in'|'cash_out'), category_key, category_label,
     * project_id (0 = General), project_name, branch_id (0 = General), branch_name,
     * payment_mode ('cash'|'bank'), amount, party_name, reference_no, source_table, source_id
     */
    private function buildNormalizedEventsSql(string $startDate, string $endDate): string
    {
        $s = $this->db->real_escape_string($startDate);
        $e = $this->db->real_escape_string($endDate);

        return "
            /* 1. Client Collections (lead_payments - verified only) */
            SELECT
                DATE(lp.payment_date) AS txn_date,
                'cash_in' AS flow_type,
                CASE
                    WHEN lp.payment_type = 'booking_money' THEN 'booking_money'
                    WHEN lp.payment_type = 'emi' OR lp.payment_type = 'emi_due_settlement' THEN 'emi'
                    WHEN lp.payment_type = 'registration' THEN 'registration'
                    WHEN lp.payment_type = 'development_charge' THEN 'development_charge'
                    WHEN lp.payment_type = 'land_share' THEN 'land_share'
                    WHEN lp.payment_type = 'shop_service_charge' THEN 'shop_service_charge'
                    ELSE 'other_receipts'
                END AS category_key,
                CASE
                    WHEN lp.payment_type = 'booking_money' THEN 'Booking Money'
                    WHEN lp.payment_type = 'emi' OR lp.payment_type = 'emi_due_settlement' THEN 'EMI'
                    WHEN lp.payment_type = 'registration' THEN 'Registration Fee'
                    WHEN lp.payment_type = 'development_charge' THEN 'Development Charge'
                    WHEN lp.payment_type = 'land_share' THEN 'Land Share'
                    WHEN lp.payment_type = 'shop_service_charge' THEN 'Shop Service Charge'
                    ELSE 'Other Receipts'
                END AS category_label,
                COALESCE(lb.project_id, 0) AS project_id,
                COALESCE(p.name, 'General') AS project_name,
                COALESCE(lp.office_branch_id, emp.office_branch_id, 0) AS branch_id,
                COALESCE(ob.name, 'General') AS branch_name,
                {$this->normalizePaymentModeSql('lp.payment_method')} AS payment_mode,
                CAST(lp.amount AS DECIMAL(16,2)) AS amount,
                COALESCE(l.full_name, 'Client') AS party_name,
                COALESCE(NULLIF(lp.transaction_ref, ''), NULLIF(lp.cheque_no, ''), CONCAT('RCPT-', lp.id)) AS reference_no,
                'lead_payments' AS source_table,
                lp.id AS source_id
            FROM lead_payments lp
            LEFT JOIN lead_bookings lb ON lp.booking_id = lb.id
            LEFT JOIN leads l ON lb.lead_id = l.id
            LEFT JOIN projects p ON lb.project_id = p.id
            LEFT JOIN users u ON lp.created_by = u.id
            LEFT JOIN employees emp ON u.employee_id = emp.id
            LEFT JOIN office_branches ob ON COALESCE(lp.office_branch_id, emp.office_branch_id) = ob.id
            WHERE lp.is_trashed = 0
              AND lp.verification_status = 'verified'
              AND DATE(lp.payment_date) BETWEEN '$s' AND '$e'

            UNION ALL

            /* 2. Management Cash In */
            SELECT
                DATE(mc.transaction_date) AS txn_date,
                'cash_in' AS flow_type,
                'management_cashin' AS category_key,
                'Management Cash In' AS category_label,
                0 AS project_id,
                'General' AS project_name,
                COALESCE(emp.office_branch_id, 0) AS branch_id,
                COALESCE(ob.name, 'General') AS branch_name,
                {$this->normalizePaymentModeSql('mc.payment_method')} AS payment_mode,
                CAST(mc.amount AS DECIMAL(16,2)) AS amount,
                COALESCE(mc.person_name, mc.source, 'Management') AS party_name,
                COALESCE(NULLIF(mc.transaction_ref, ''), CONCAT('MGMT-IN-', mc.id)) AS reference_no,
                'management_cashin' AS source_table,
                mc.id AS source_id
            FROM management_cashin mc
            LEFT JOIN users u ON mc.created_by = u.id
            LEFT JOIN employees emp ON u.employee_id = emp.id
            LEFT JOIN office_branches ob ON emp.office_branch_id = ob.id
            WHERE mc.payment_status = 'paid'
              AND DATE(mc.transaction_date) BETWEEN '$s' AND '$e'

            UNION ALL

            /* 3. Construction Financial Transactions (Vendor Payments, Contractor Bills, Operational Fund) */
            SELECT
                DATE(cft.transaction_date) AS txn_date,
                'cash_out' AS flow_type,
                CASE
                    WHEN cft.ref_type = 'vendor_payment' THEN 'vendor_payment'
                    WHEN cft.ref_type = 'contractor_bill' THEN 'contractor_bill'
                    ELSE 'cashout'
                END AS category_key,
                CASE
                    WHEN cft.ref_type = 'vendor_payment' THEN 'Vendor Payments'
                    WHEN cft.ref_type = 'contractor_bill' THEN 'Contractor Bills'
                    ELSE 'Operational Fund'
                END AS category_label,
                COALESCE(cft.project_id, 0) AS project_id,
                COALESCE(p.name, 'General') AS project_name,
                COALESCE(cft.office_branch_id, emp.office_branch_id, 0) AS branch_id,
                COALESCE(ob.name, 'General') AS branch_name,
                {$this->normalizePaymentModeSql('cft.payment_method')} AS payment_mode,
                CAST(cft.amount_paid AS DECIMAL(16,2)) AS amount,
                COALESCE(NULLIF(v.company_name, ''), v.name, c.name, 'Vendor / Contractor') AS party_name,
                COALESCE(NULLIF(cft.reference_number, ''), CONCAT('CFT-', cft.id)) AS reference_no,
                'con_financial_transactions' AS source_table,
                cft.id AS source_id
            FROM con_financial_transactions cft
            LEFT JOIN vendor_payment_requests vpr ON cft.ref_type = 'vendor_payment' AND cft.ref_id = vpr.id
            LEFT JOIN vendors v ON vpr.vendor_id = v.id
            LEFT JOIN contractor_bill_requests cbr ON cft.ref_type = 'contractor_bill' AND cft.ref_id = cbr.id
            LEFT JOIN contractors c ON cbr.target_id = c.id
            LEFT JOIN projects p ON cft.project_id = p.id
            LEFT JOIN users u ON COALESCE(cft.processed_by, cft.created_by) = u.id
            LEFT JOIN employees emp ON u.employee_id = emp.id
            LEFT JOIN office_branches ob ON COALESCE(cft.office_branch_id, emp.office_branch_id) = ob.id
            WHERE cft.ref_type IN ('vendor_payment', 'contractor_bill', 'operational_fund')
              AND DATE(cft.transaction_date) BETWEEN '$s' AND '$e'

            UNION ALL

            /* 4. Cashout Payments */
            SELECT
                DATE(cp.payment_date) AS txn_date,
                'cash_out' AS flow_type,
                'cashout' AS category_key,
                'Cashout & Utilities' AS category_label,
                COALESCE(NULLIF(cr.project_id, 0), NULLIF(cp.project_id, 0), 0) AS project_id,
                COALESCE(p.name, 'General') AS project_name,
                COALESCE(cp.office_branch_id, cr.office_branch_id, emp.office_branch_id, 0) AS branch_id,
                COALESCE(ob.name, 'General') AS branch_name,
                {$this->normalizePaymentModeSql('cp.payment_method')} AS payment_mode,
                CAST(cp.amount AS DECIMAL(16,2)) AS amount,
                COALESCE(NULLIF(cp.purpose_paid_to, ''), cr.purpose_paid_to, 'Cashout') AS party_name,
                COALESCE(NULLIF(cp.transaction_ref, ''), CONCAT('CO-', cp.id)) AS reference_no,
                'cashout_payments' AS source_table,
                cp.id AS source_id
            FROM cashout_payments cp
            LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
            LEFT JOIN projects p ON COALESCE(NULLIF(cr.project_id, 0), NULLIF(cp.project_id, 0)) = p.id
            LEFT JOIN users u ON COALESCE(cp.created_by, cr.requested_by) = u.id
            LEFT JOIN employees emp ON u.employee_id = emp.id
            LEFT JOIN office_branches ob ON COALESCE(cp.office_branch_id, cr.office_branch_id, emp.office_branch_id) = ob.id
            WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')
              AND DATE(cp.payment_date) BETWEEN '$s' AND '$e'

            UNION ALL

            /* 5. Payroll / Payslips (General / Head Office) */
            SELECT
                DATE(COALESCE(ps.disbursed_at, ps.payslip_month, ps.updated_at)) AS txn_date,
                'cash_out' AS flow_type,
                'payroll' AS category_key,
                'Payroll & Salary' AS category_label,
                0 AS project_id,
                'General' AS project_name,
                COALESCE(ps.office_branch_id, emp.office_branch_id, 0) AS branch_id,
                COALESCE(ob.name, 'General') AS branch_name,
                {$this->normalizePaymentModeSql('ps.payment_method')} AS payment_mode,
                CAST(ps.net_salary AS DECIMAL(16,2)) AS amount,
                COALESCE(emp.full_name, 'Employee') AS party_name,
                CONCAT('PAY-', ps.id) AS reference_no,
                'payslips' AS source_table,
                ps.id AS source_id
            FROM payslips ps
            LEFT JOIN employees emp ON ps.employee_id = emp.id
            LEFT JOIN office_branches ob ON COALESCE(ps.office_branch_id, emp.office_branch_id) = ob.id
            WHERE ps.status = 'paid'
              AND DATE(COALESCE(ps.disbursed_at, ps.payslip_month, ps.updated_at)) BETWEEN '$s' AND '$e'

            UNION ALL

            /* 6. Fund Requisitions */
            SELECT
                DATE(fr.paid_at) AS txn_date,
                'cash_out' AS flow_type,
                'fund_requisitions' AS category_key,
                'Fund Requisitions' AS category_label,
                COALESCE(fr.project_id, 0) AS project_id,
                COALESCE(p.name, 'General') AS project_name,
                COALESCE(fr.office_branch_id, emp.office_branch_id, 0) AS branch_id,
                COALESCE(ob.name, 'General') AS branch_name,
                {$this->normalizePaymentModeSql('fr.payment_method')} AS payment_mode,
                CAST(fr.amount AS DECIMAL(16,2)) AS amount,
                COALESCE(emp.full_name, fr.category, 'Fund Requisition') AS party_name,
                CONCAT('FR-', fr.id) AS reference_no,
                'fund_requisitions' AS source_table,
                fr.id AS source_id
            FROM fund_requisitions fr
            LEFT JOIN projects p ON fr.project_id = p.id
            LEFT JOIN users u ON fr.requested_by = u.id
            LEFT JOIN employees emp ON u.employee_id = emp.id
            LEFT JOIN office_branches ob ON COALESCE(fr.office_branch_id, emp.office_branch_id) = ob.id
            WHERE fr.status = 'paid'
              AND DATE(fr.paid_at) BETWEEN '$s' AND '$e'

            UNION ALL

            /* 7. Land Fund Requisitions */
            SELECT
                DATE(lfr.accounts_paid_at) AS txn_date,
                'cash_out' AS flow_type,
                'land_fund_requisitions' AS category_key,
                'Land Fund Requisitions' AS category_label,
                COALESCE(la.project_id, 0) AS project_id,
                COALESCE(p.name, 'General') AS project_name,
                COALESCE(lfr.office_branch_id, emp.office_branch_id, 0) AS branch_id,
                COALESCE(ob.name, 'General') AS branch_name,
                {$this->normalizePaymentModeSql('lfr.payment_method')} AS payment_mode,
                CAST(lfr.requested_amount AS DECIMAL(16,2)) AS amount,
                COALESCE(lfr.cost_purpose, lfr.requisition_type, 'Land Fund') AS party_name,
                COALESCE(NULLIF(lfr.transaction_no, ''), CONCAT('LFR-', lfr.id)) AS reference_no,
                'land_fund_requisitions' AS source_table,
                lfr.id AS source_id
            FROM land_fund_requisitions lfr
            LEFT JOIN land_acquisitions la ON lfr.land_acquisition_id = la.id
            LEFT JOIN projects p ON la.project_id = p.id
            LEFT JOIN users u ON lfr.requested_by = u.id
            LEFT JOIN employees emp ON u.employee_id = emp.id
            LEFT JOIN office_branches ob ON COALESCE(lfr.office_branch_id, emp.office_branch_id) = ob.id
            WHERE lfr.mgmt_status = 'Approved'
              AND lfr.accounts_status = 'Paid'
              AND DATE(lfr.accounts_paid_at) BETWEEN '$s' AND '$e'

            UNION ALL

            /* 8. Office Procurement & Assets (net of return_amount if purchased) */
            SELECT
                DATE(pea.paid_at) AS txn_date,
                'cash_out' AS flow_type,
                'office_procurement_assets' AS category_key,
                'Procurement & Assets' AS category_label,
                COALESCE(pea.project_id, 0) AS project_id,
                COALESCE(p.name, 'General') AS project_name,
                COALESCE(pea.office_branch_id, emp.office_branch_id, 0) AS branch_id,
                COALESCE(ob.name, 'General') AS branch_name,
                {$this->normalizePaymentModeSql('pea.payment_method')} AS payment_mode,
                CAST(GREATEST(0, COALESCE(pea.paid_amount, 0) - CASE WHEN pea.overall_status = 'purchased' THEN COALESCE(pea.return_amount, 0) ELSE 0 END) AS DECIMAL(16,2)) AS amount,
                COALESCE(NULLIF(pea.vendor_name, ''), pea.item_name, 'Procurement') AS party_name,
                COALESCE(NULLIF(pea.invoice_no, ''), CONCAT('PEA-', pea.id)) AS reference_no,
                'project_extra_assets' AS source_table,
                pea.id AS source_id
            FROM project_extra_assets pea
            LEFT JOIN projects p ON pea.project_id = p.id
            LEFT JOIN users u ON pea.requested_by = u.id
            LEFT JOIN employees emp ON u.employee_id = emp.id
            LEFT JOIN office_branches ob ON COALESCE(pea.office_branch_id, emp.office_branch_id) = ob.id
            WHERE pea.payment_status = 'paid'
              AND DATE(pea.paid_at) BETWEEN '$s' AND '$e'

            UNION ALL

            /* 9. Management Cash Out */
            SELECT
                DATE(mo.transaction_date) AS txn_date,
                'cash_out' AS flow_type,
                'cashout' AS category_key,
                'Management Cash Out' AS category_label,
                0 AS project_id,
                'General' AS project_name,
                COALESCE(emp.office_branch_id, 0) AS branch_id,
                COALESCE(ob.name, 'General') AS branch_name,
                {$this->normalizePaymentModeSql('mo.payment_method')} AS payment_mode,
                CAST(mo.amount AS DECIMAL(16,2)) AS amount,
                COALESCE(mo.paid_to, mo.person_name, 'Management') AS party_name,
                COALESCE(NULLIF(mo.transaction_ref, ''), CONCAT('MGMT-OUT-', mo.id)) AS reference_no,
                'management_cashout' AS source_table,
                mo.id AS source_id
            FROM management_cashout mo
            LEFT JOIN users u ON mo.created_by = u.id
            LEFT JOIN employees emp ON u.employee_id = emp.id
            LEFT JOIN office_branches ob ON emp.office_branch_id = ob.id
            WHERE mo.payment_status = 'paid'
              AND DATE(mo.transaction_date) BETWEEN '$s' AND '$e'

            UNION ALL

            /* 10. Expense Adjustments (Return = Cash In, Extra = Cash Out) */
            SELECT
                DATE(atx.payment_date) AS txn_date,
                CASE WHEN atx.transaction_type = 'cashin' THEN 'cash_in' ELSE 'cash_out' END AS flow_type,
                CASE WHEN atx.transaction_type = 'cashin' THEN 'expense_adjustment_cashin' ELSE 'expense_adjustment' END AS category_key,
                CASE WHEN atx.transaction_type = 'cashin' THEN 'Expense Adjustment (Return)' ELSE 'Expense Adjustments' END AS category_label,
                COALESCE(ea.project_id, 0) AS project_id,
                COALESCE(p.name, 'General') AS project_name,
                COALESCE(emp.office_branch_id, 0) AS branch_id,
                COALESCE(ob.name, 'General') AS branch_name,
                {$this->normalizePaymentModeSql('atx.payment_method')} AS payment_mode,
                CAST(atx.amount AS DECIMAL(16,2)) AS amount,
                COALESCE(NULLIF(ea.title, ''), 'Expense Adjustment') AS party_name,
                COALESCE(NULLIF(atx.transaction_ref, ''), CONCAT('ADJ-', atx.id)) AS reference_no,
                'adjustment_transactions' AS source_table,
                atx.id AS source_id
            FROM adjustment_transactions atx
            INNER JOIN expense_adjustments ea ON atx.adjustment_id = ea.id
            LEFT JOIN projects p ON ea.project_id = p.id
            LEFT JOIN users u ON COALESCE(atx.created_by, ea.user_id) = u.id
            LEFT JOIN employees emp ON u.employee_id = emp.id
            LEFT JOIN office_branches ob ON emp.office_branch_id = ob.id
            WHERE DATE(atx.payment_date) BETWEEN '$s' AND '$e'
        ";
    }

    /**
     * Build SQL WHERE clause from normalized filters
     */
    private function buildWhereClause(array $filters): string
    {
        $conds = ["1=1"];

        if (!empty($filters['branch_id'])) {
            if ($filters['branch_id'] === 'general') {
                $conds[] = "branch_id = 0";
            } elseif (is_numeric($filters['branch_id'])) {
                $conds[] = "branch_id = " . (int)$filters['branch_id'];
            }
        }

        $projectIds = [];
        if (!empty($filters['project_ids']) && is_array($filters['project_ids'])) {
            foreach ($filters['project_ids'] as $pid) {
                if ($pid === 'general' || $pid === 0 || $pid === '0') {
                    $projectIds[] = 0;
                } elseif (is_numeric($pid) && (int)$pid > 0) {
                    $projectIds[] = (int)$pid;
                }
            }
        } elseif (!empty($filters['project_id'])) {
            if ($filters['project_id'] === 'general') {
                $projectIds[] = 0;
            } elseif (is_numeric($filters['project_id'])) {
                $projectIds[] = (int)$filters['project_id'];
            }
        }
        $projectIds = array_values(array_unique($projectIds));
        if (!empty($projectIds)) {
            $conds[] = "project_id IN (" . implode(',', $projectIds) . ")";
        }

        if (!empty($filters['transaction_type']) && in_array($filters['transaction_type'], ['cash_in', 'cash_out'], true)) {
            $tt = $this->db->real_escape_string($filters['transaction_type']);
            $conds[] = "flow_type = '$tt'";
        }

        $categories = [];
        if (!empty($filters['categories']) && is_array($filters['categories'])) {
            foreach ($filters['categories'] as $c) {
                if ($c !== '' && $c !== 'all') {
                    $categories[] = "'" . $this->db->real_escape_string($c) . "'";
                }
            }
        } elseif (!empty($filters['category']) && $filters['category'] !== 'all') {
            $categories[] = "'" . $this->db->real_escape_string($filters['category']) . "'";
        }
        $categories = array_values(array_unique($categories));
        if (!empty($categories)) {
            $conds[] = "category_key IN (" . implode(',', $categories) . ")";
        }

        if (!empty($filters['payment_mode']) && in_array($filters['payment_mode'], ['cash', 'bank'], true)) {
            $pm = $this->db->real_escape_string($filters['payment_mode']);
            $conds[] = "payment_mode = '$pm'";
        }

        return implode(' AND ', $conds);
    }

    /**
     * Compute Opening Balance before $startDate (split by total, bank, cash)
     */
    public function getOpeningBalances(string $startDate, array $filters): array
    {
        $prevDate = date('Y-m-d', strtotime($startDate . ' -1 day'));
        if ($prevDate < '1970-01-01') {
            return ['total' => 0.0, 'bank' => 0.0, 'cash' => 0.0];
        }

        $unionSql = $this->buildNormalizedEventsSql('1970-01-01', $prevDate);
        $whereSql = $this->buildWhereClause($filters);

        $sql = "
            SELECT
                COALESCE(SUM(CASE WHEN flow_type = 'cash_in' THEN amount ELSE -amount END), 0) AS total_bal,
                COALESCE(SUM(CASE WHEN payment_mode = 'bank' THEN (CASE WHEN flow_type = 'cash_in' THEN amount ELSE -amount END) ELSE 0 END), 0) AS bank_bal,
                COALESCE(SUM(CASE WHEN payment_mode = 'cash' THEN (CASE WHEN flow_type = 'cash_in' THEN amount ELSE -amount END) ELSE 0 END), 0) AS cash_bal
            FROM ($unionSql) evt
            WHERE $whereSql
        ";
        $res = $this->db->query($sql);
        $row = $res ? $res->fetch_assoc() : null;
        return [
            'total' => (float)($row['total_bal'] ?? 0),
            'bank' => (float)($row['bank_bal'] ?? 0),
            'cash' => (float)($row['cash_bal'] ?? 0),
        ];
    }

    /**
     * Get Overview KPIs, Chart series, and Interval Table
     */
    public function getOverviewData(string $startDate, string $endDate, array $filters, string $intervalMode = 'auto'): array
    {
        $opening = $this->getOpeningBalances($startDate, $filters);
        $unionSql = $this->buildNormalizedEventsSql($startDate, $endDate);
        $whereSql = $this->buildWhereClause($filters);

        $daysCount = max(1, (int)round((strtotime($endDate) - strtotime($startDate)) / 86400) + 1);
        $useMonthly = ($intervalMode === 'monthly') || ($intervalMode === 'auto' && $daysCount > 45);
        $groupExpr = $useMonthly ? "DATE_FORMAT(txn_date, '%Y-%m')" : "txn_date";

        // Summary KPIs
        $sumSql = "
            SELECT
                COALESCE(SUM(CASE WHEN flow_type = 'cash_in' THEN amount ELSE 0 END), 0) AS cash_in,
                COALESCE(SUM(CASE WHEN flow_type = 'cash_in' AND project_id > 0 THEN amount ELSE 0 END), 0) AS project_cash_in,
                COALESCE(SUM(CASE WHEN flow_type = 'cash_in' AND project_id = 0 THEN amount ELSE 0 END), 0) AS general_cash_in,
                COALESCE(SUM(CASE WHEN flow_type = 'cash_out' THEN amount ELSE 0 END), 0) AS cash_out,
                COALESCE(SUM(CASE WHEN flow_type = 'cash_out' AND project_id > 0 THEN amount ELSE 0 END), 0) AS project_cash_out,
                COALESCE(SUM(CASE WHEN flow_type = 'cash_out' AND project_id = 0 THEN amount ELSE 0 END), 0) AS general_cash_out,
                COALESCE(SUM(CASE WHEN payment_mode = 'bank' THEN (CASE WHEN flow_type = 'cash_in' THEN amount ELSE -amount END) ELSE 0 END), 0) AS bank_net,
                COALESCE(SUM(CASE WHEN payment_mode = 'cash' THEN (CASE WHEN flow_type = 'cash_in' THEN amount ELSE -amount END) ELSE 0 END), 0) AS cash_net,
                COUNT(*) AS txn_count
            FROM ($unionSql) evt
            WHERE $whereSql
        ";
        $sumRes = $this->db->query($sumSql);
        $sumRow = $sumRes ? $sumRes->fetch_assoc() : [];

        $cashIn = (float)($sumRow['cash_in'] ?? 0);
        $cashOut = (float)($sumRow['cash_out'] ?? 0);
        $netMovement = $cashIn - $cashOut;

        // Interval rows
        $intSql = "
            SELECT
                $groupExpr AS period_key,
                COALESCE(SUM(CASE WHEN flow_type = 'cash_in' THEN amount ELSE 0 END), 0) AS cash_in,
                COALESCE(SUM(CASE WHEN flow_type = 'cash_out' THEN amount ELSE 0 END), 0) AS cash_out,
                COUNT(*) AS txn_count
            FROM ($unionSql) evt
            WHERE $whereSql
            GROUP BY $groupExpr
            ORDER BY period_key ASC
        ";
        $intRes = $this->db->query($intSql);
        $intervals = [];
        $running = $opening['total'];
        if ($intRes) {
            while ($r = $intRes->fetch_assoc()) {
                $in = (float)$r['cash_in'];
                $out = (float)$r['cash_out'];
                $net = $in - $out;
                $openRow = $running;
                $running += $net;
                $key = $r['period_key'];
                if ($useMonthly) {
                    $label = date('M Y', strtotime($key . '-01'));
                    $rowStart = $key . '-01';
                    $rowEnd = date('Y-m-t', strtotime($rowStart));
                } else {
                    $label = date('d M Y', strtotime($key));
                    $rowStart = $key;
                    $rowEnd = $key;
                }
                $intervals[] = [
                    'key' => $key,
                    'label' => $label,
                    'start_date' => max($startDate, $rowStart),
                    'end_date' => min($endDate, $rowEnd),
                    'opening' => $openRow,
                    'cash_in' => $in,
                    'cash_out' => $out,
                    'net' => $net,
                    'closing' => $running,
                    'txn_count' => (int)$r['txn_count'],
                ];
            }
        }

        return [
            'summary' => [
                'opening_balance' => $opening['total'],
                'opening_bank' => $opening['bank'],
                'opening_cash' => $opening['cash'],
                'cash_in' => $cashIn,
                'project_cash_in' => (float)($sumRow['project_cash_in'] ?? 0),
                'general_cash_in' => (float)($sumRow['general_cash_in'] ?? 0),
                'cash_out' => $cashOut,
                'project_cash_out' => (float)($sumRow['project_cash_out'] ?? 0),
                'general_cash_out' => (float)($sumRow['general_cash_out'] ?? 0),
                'net_movement' => $netMovement,
                'closing_balance' => $opening['total'] + $netMovement,
                'closing_bank' => $opening['bank'] + (float)($sumRow['bank_net'] ?? 0),
                'closing_cash' => $opening['cash'] + (float)($sumRow['cash_net'] ?? 0),
                'txn_count' => (int)($sumRow['txn_count'] ?? 0),
            ],
            'interval_type' => $useMonthly ? 'monthly' : 'daily',
            'intervals' => $intervals,
        ];
    }

    /**
     * Tab 2: Branch Performance
     */
    public function getBranchPerformance(string $startDate, string $endDate, array $filters): array
    {
        $unionSql = $this->buildNormalizedEventsSql($startDate, $endDate);
        // Remove branch filter inside Branch Performance comparison unless explicitly desired, or apply all other filters
        $f = $filters;
        $f['branch_id'] = null;
        $whereSql = $this->buildWhereClause($f);

        $sql = "
            SELECT
                branch_id,
                branch_name,
                COALESCE(SUM(CASE WHEN flow_type = 'cash_in' THEN amount ELSE 0 END), 0) AS collections,
                COALESCE(SUM(CASE WHEN flow_type = 'cash_out' THEN amount ELSE 0 END), 0) AS direct_cost,
                COUNT(*) AS txn_count
            FROM ($unionSql) evt
            WHERE $whereSql
            GROUP BY branch_id, branch_name
            ORDER BY collections DESC, direct_cost DESC
        ";
        $res = $this->db->query($sql);
        $rows = [];
        $totals = ['collections' => 0.0, 'direct_cost' => 0.0, 'net' => 0.0, 'txn_count' => 0];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $col = (float)$r['collections'];
                $cost = (float)$r['direct_cost'];
                $net = $col - $cost;
                $rows[] = [
                    'branch_id' => (int)$r['branch_id'],
                    'branch_name' => $r['branch_name'] ?: 'General',
                    'collections' => $col,
                    'direct_cost' => $cost,
                    'net' => $net,
                    'txn_count' => (int)$r['txn_count'],
                ];
                $totals['collections'] += $col;
                $totals['direct_cost'] += $cost;
                $totals['net'] += $net;
                $totals['txn_count'] += (int)$r['txn_count'];
            }
        }
        return ['rows' => $rows, 'totals' => $totals];
    }

    /**
     * Tab 3: Project & Category Accordion Breakdown
     */
    public function getProjectCategoryBreakdown(string $startDate, string $endDate, array $filters): array
    {
        $unionSql = $this->buildNormalizedEventsSql($startDate, $endDate);
        $whereSql = $this->buildWhereClause($filters);

        $sql = "
            SELECT
                project_id,
                project_name,
                flow_type,
                category_key,
                category_label,
                COUNT(*) AS txn_count,
                COALESCE(SUM(CASE WHEN flow_type = 'cash_in' THEN amount ELSE 0 END), 0) AS cash_in,
                COALESCE(SUM(CASE WHEN flow_type = 'cash_out' THEN amount ELSE 0 END), 0) AS cash_out
            FROM ($unionSql) evt
            WHERE $whereSql
            GROUP BY project_id, project_name, flow_type, category_key, category_label
            ORDER BY (CASE WHEN project_id = 0 THEN 1 ELSE 0 END), project_name ASC, flow_type ASC, SUM(amount) DESC
        ";
        $res = $this->db->query($sql);
        $projects = [];
        $totals = ['txn_count' => 0, 'cash_in' => 0.0, 'cash_out' => 0.0, 'net' => 0.0, 'project_count' => 0];

        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $pid = (int)$r['project_id'];
                if (!isset($projects[$pid])) {
                    $projects[$pid] = [
                        'project_id' => $pid,
                        'project_name' => $r['project_name'] ?: 'General',
                        'txn_count' => 0,
                        'cash_in' => 0.0,
                        'cash_out' => 0.0,
                        'net' => 0.0,
                        'categories' => [],
                    ];
                }
                $in = (float)$r['cash_in'];
                $out = (float)$r['cash_out'];
                $cnt = (int)$r['txn_count'];
                $projects[$pid]['txn_count'] += $cnt;
                $projects[$pid]['cash_in'] += $in;
                $projects[$pid]['cash_out'] += $out;
                $projects[$pid]['net'] += ($in - $out);
                $projects[$pid]['categories'][] = [
                    'category_key' => $r['category_key'],
                    'category_label' => $r['category_label'],
                    'flow_type' => $r['flow_type'],
                    'txn_count' => $cnt,
                    'cash_in' => $in,
                    'cash_out' => $out,
                    'net' => $in - $out,
                ];

                $totals['txn_count'] += $cnt;
                $totals['cash_in'] += $in;
                $totals['cash_out'] += $out;
                $totals['net'] += ($in - $out);
            }
        }
        $totals['project_count'] = count($projects);
        return ['projects' => array_values($projects), 'totals' => $totals];
    }

    /**
     * Tab 4: EMI vs Project Cost & Receivables/Payables
     */
    public function getEmiVsProjectCost(string $startDate, string $endDate, array $filters, string $scope = 'emi'): array
    {
        $unionSql = $this->buildNormalizedEventsSql($startDate, $endDate);
        $f = $filters;
        $f['transaction_type'] = null;
        $f['category'] = null;
        $f['categories'] = [];
        $whereSql = $this->buildWhereClause($f);

        $inCond = ($scope === 'all_project')
            ? "flow_type = 'cash_in'"
            : "category_key = 'emi'";

        $sql = "
            SELECT
                project_id,
                project_name,
                COALESCE(SUM(CASE WHEN $inCond THEN amount ELSE 0 END), 0) AS collected,
                COALESCE(SUM(CASE WHEN flow_type = 'cash_out' THEN amount ELSE 0 END), 0) AS cost_paid
            FROM ($unionSql) evt
            WHERE $whereSql AND project_id > 0
            GROUP BY project_id, project_name
            ORDER BY collected DESC, cost_paid DESC
        ";
        $res = $this->db->query($sql);

        // Overdue EMI per project (live from emi_schedules)
        $overdueMap = [];
        $ovRes = $this->db->query("
            SELECT COALESCE(lb.project_id, 0) AS pid, COUNT(*) AS cnt, COALESCE(SUM(es.emi_amount), 0) AS amt
            FROM emi_schedules es
            INNER JOIN lead_bookings lb ON es.lead_booking_id = lb.id
            WHERE es.status = 'pending' AND es.due_date < CURDATE()
            GROUP BY lb.project_id
        ");
        if ($ovRes) {
            while ($r = $ovRes->fetch_assoc()) {
                $overdueMap[(int)$r['pid']] = ['count' => (int)$r['cnt'], 'amount' => (float)$r['amt']];
            }
        }

        // Unpaid approved bills per project
        $unpaidMap = [];
        $upRes = $this->db->query("
            SELECT pid, SUM(due_amt) AS amt FROM (
                SELECT COALESCE(project_id, 0) AS pid, COALESCE(due_amount, 0) AS due_amt
                FROM vendor_payment_requests WHERE status IN ('Approved', 'Partially_Paid')
                UNION ALL
                SELECT COALESCE(project_id, 0) AS pid, COALESCE(due_amount, 0) AS due_amt
                FROM contractor_bill_requests WHERE status IN ('approved', 'partially_paid')
                UNION ALL
                SELECT COALESCE(project_id, 0) AS pid, COALESCE(due_amount, 0) AS due_amt
                FROM cashout_requests WHERE status IN ('approved', 'partially_paid')
            ) u GROUP BY pid
        ");
        if ($upRes) {
            while ($r = $upRes->fetch_assoc()) {
                $unpaidMap[(int)$r['pid']] = (float)$r['amt'];
            }
        }

        $rows = [];
        $totals = ['collected' => 0.0, 'cost_paid' => 0.0, 'surplus' => 0.0, 'overdue_emi' => 0.0, 'unpaid_bills' => 0.0];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $pid = (int)$r['project_id'];
                $col = (float)$r['collected'];
                $cost = (float)$r['cost_paid'];
                $cov = $cost > 0 ? round(($col / $cost) * 100, 1) : null;
                $surplus = $col - $cost;
                $ov = $overdueMap[$pid]['amount'] ?? 0.0;
                $up = $unpaidMap[$pid] ?? 0.0;

                $rows[] = [
                    'project_id' => $pid,
                    'project_name' => $r['project_name'],
                    'collected' => $col,
                    'cost_paid' => $cost,
                    'coverage_pct' => $cov,
                    'surplus' => $surplus,
                    'overdue_emi' => $ov,
                    'unpaid_bills' => $up,
                ];
                $totals['collected'] += $col;
                $totals['cost_paid'] += $cost;
                $totals['surplus'] += $surplus;
                $totals['overdue_emi'] += $ov;
                $totals['unpaid_bills'] += $up;
            }
        }
        $totals['coverage_pct'] = $totals['cost_paid'] > 0 ? round(($totals['collected'] / $totals['cost_paid']) * 100, 1) : null;

        return ['rows' => $rows, 'totals' => $totals];
    }

    /**
     * Real-time Executive Alerts: Pending Verification Receipts & Approved Unpaid Payables
     */
    public function getExecutiveAlerts(): array
    {
        $pendingVerify = ['count' => 0, 'amount' => 0.0];
        $res1 = $this->db->query("SELECT COUNT(*) AS cnt, COALESCE(SUM(amount), 0) AS amt FROM lead_payments WHERE is_trashed = 0 AND verification_status = 'pending'");
        if ($res1 && ($r = $res1->fetch_assoc())) {
            $pendingVerify = ['count' => (int)$r['cnt'], 'amount' => (float)$r['amt']];
        }

        $unpaidBills = ['count' => 0, 'amount' => 0.0, 'vendor' => 0.0, 'contractor' => 0.0, 'cashout' => 0.0];
        $res2 = $this->db->query("
            SELECT
                (SELECT COUNT(*) FROM vendor_payment_requests WHERE status IN ('Approved', 'Partially_Paid')) +
                (SELECT COUNT(*) FROM contractor_bill_requests WHERE status IN ('approved', 'partially_paid')) +
                (SELECT COUNT(*) FROM cashout_requests WHERE status IN ('approved', 'partially_paid')) AS cnt,
                (SELECT COALESCE(SUM(due_amount), 0) FROM vendor_payment_requests WHERE status IN ('Approved', 'Partially_Paid')) AS v_amt,
                (SELECT COALESCE(SUM(due_amount), 0) FROM contractor_bill_requests WHERE status IN ('approved', 'partially_paid')) AS c_amt,
                (SELECT COALESCE(SUM(due_amount), 0) FROM cashout_requests WHERE status IN ('approved', 'partially_paid')) AS co_amt
        ");
        if ($res2 && ($r = $res2->fetch_assoc())) {
            $v = (float)$r['v_amt'];
            $c = (float)$r['c_amt'];
            $co = (float)$r['co_amt'];
            $unpaidBills = [
                'count' => (int)$r['cnt'],
                'amount' => $v + $c + $co,
                'vendor' => $v,
                'contractor' => $c,
                'cashout' => $co,
            ];
        }

        $overdueEmi = ['count' => 0, 'amount' => 0.0];
        $res3 = $this->db->query("SELECT COUNT(*) AS cnt, COALESCE(SUM(emi_amount), 0) AS amt FROM emi_schedules WHERE status = 'pending' AND due_date < CURDATE()");
        if ($res3 && ($r = $res3->fetch_assoc())) {
            $overdueEmi = ['count' => (int)$r['cnt'], 'amount' => (float)$r['amt']];
        }

        return [
            'pending_verification' => $pendingVerify,
            'unpaid_payables' => $unpaidBills,
            'overdue_emi' => $overdueEmi,
        ];
    }

    /**
     * Drill-down itemized transactions for Slide-Over Drawer & CSV Export
     */
    public function getDrilldownTransactions(string $startDate, string $endDate, array $filters, int $limit = 250): array
    {
        $unionSql = $this->buildNormalizedEventsSql($startDate, $endDate);
        $whereSql = $this->buildWhereClause($filters);
        $limit = max(1, min(5000, $limit));

        $sql = "
            SELECT *
            FROM ($unionSql) evt
            WHERE $whereSql
            ORDER BY txn_date DESC, source_id DESC
            LIMIT $limit
        ";
        $res = $this->db->query($sql);
        $rows = [];
        $totalIn = 0.0;
        $totalOut = 0.0;
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $amt = (float)$r['amount'];
                if ($r['flow_type'] === 'cash_in') {
                    $totalIn += $amt;
                } else {
                    $totalOut += $amt;
                }
                $rows[] = $r;
            }
        }
        return [
            'rows' => $rows,
            'total_in' => $totalIn,
            'total_out' => $totalOut,
            'count' => count($rows),
        ];
    }
}

<?php

require_once __DIR__ . '/ApprovalRequest.php';

class ExpenseAnalytics
{
    protected $db;
    protected $approvalModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->approvalModel = new ApprovalRequest($this->db);
    }

    /**
     * Get module definitions and metadata
     */
    public function getModuleDefinitions()
    {
        return [
            'contractor_bill_requests' => [
                'key'         => 'contractor_bill_requests',
                'name'        => 'Contractor Bills',
                'dept'        => 'Construction',
                'icon'        => 'fa-solid fa-hard-hat',
                'badge_color' => 'warning',
                'accent_hex'  => '#f59e0b',
                'portal_url'  => 'contractor-bills'
            ],
            'vendor_payment_requests' => [
                'key'         => 'vendor_payment_requests',
                'name'        => 'Vendor Payments',
                'dept'        => 'Procurement',
                'icon'        => 'fa-solid fa-truck-ramp-box',
                'badge_color' => 'primary',
                'accent_hex'  => '#3b82f6',
                'portal_url'  => 'vendor-payment-requests'
            ],
            'fund_requisitions' => [
                'key'         => 'fund_requisitions',
                'name'        => 'Fund Requisitions',
                'dept'        => 'Accounts & Ops',
                'icon'        => 'fa-solid fa-hand-holding-dollar',
                'badge_color' => 'success',
                'accent_hex'  => '#10b981',
                'portal_url'  => 'fund-requisitions'
            ],
            'con_labor_requisitions' => [
                'key'         => 'con_labor_requisitions',
                'name'        => 'SE Labor Bills',
                'dept'        => 'Construction',
                'icon'        => 'fa-solid fa-users-gear',
                'badge_color' => 'info',
                'accent_hex'  => '#06b6d4',
                'portal_url'  => 'con-labor-requisitions'
            ],
            'project_extra_assets' => [
                'key'         => 'project_extra_assets',
                'name'        => 'Office Procurement',
                'dept'        => 'Procurement',
                'icon'        => 'fa-solid fa-boxes-stacked',
                'badge_color' => 'purple',
                'accent_hex'  => '#8b5cf6',
                'portal_url'  => 'office-procurement'
            ],
            'project_other_costs' => [
                'key'         => 'project_other_costs',
                'name'        => 'Project Other Costs',
                'dept'        => 'Construction',
                'icon'        => 'fa-solid fa-receipt',
                'badge_color' => 'warning',
                'accent_hex'  => '#d97706',
                'portal_url'  => 'project-other-costs'
            ],
            'cashout_requests' => [
                'key'         => 'cashout_requests',
                'name'        => 'Cashout Requests',
                'dept'        => 'Accounts',
                'icon'        => 'fa-solid fa-money-bill-transfer',
                'badge_color' => 'danger',
                'accent_hex'  => '#ef4444',
                'portal_url'  => 'cashout-requests'
            ],
            'payslips' => [
                'key'         => 'payslips',
                'name'        => 'Payslips & Salary',
                'dept'        => 'HR & Accounts',
                'icon'        => 'fa-solid fa-file-invoice-dollar',
                'badge_color' => 'dark',
                'accent_hex'  => '#374151',
                'portal_url'  => 'payslip-generate'
            ],
            'advance_salaries' => [
                'key'         => 'advance_salaries',
                'name'        => 'Advance Salary',
                'dept'        => 'HR & Accounts',
                'icon'        => 'fa-solid fa-wallet',
                'badge_color' => 'indigo',
                'accent_hex'  => '#6366f1',
                'portal_url'  => 'advance-salaries'
            ],
            'project_incentive_claims' => [
                'key'         => 'project_incentive_claims',
                'name'        => 'Sales Incentives',
                'dept'        => 'Sales',
                'icon'        => 'fa-solid fa-award',
                'badge_color' => 'secondary',
                'accent_hex'  => '#eab308',
                'portal_url'  => 'incentive-management'
            ],
        ];
    }

    /**
     * All projects list for filter dropdown
     */
    public function getAllProjectsList()
    {
        $projects = [];
        $res = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC");
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $projects[] = $r;
            }
        }
        return $projects;
    }

    /**
     * All active office branches list for filter dropdown & analytics
     */
    public function getOfficeBranchesList()
    {
        $branches = [];
        $res = $this->db->query("SELECT id, name, status FROM office_branches WHERE status = 'active' ORDER BY id ASC");
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $branches[] = $r;
            }
        }
        return $branches;
    }

    /**
     * Office Branch-wise Cost Summary for non-project / general overhead expenses
     * Only covers office overhead (Office Procurement, Fund Requisitions, Cashout Requests, Advance Salaries)
     * Strictly EXCLUDES project costs like project_other_costs, contractor bills, labor bills, vendor payments.
     */
    public function getOfficeBranchCostSummary($dateFrom = null, $dateTo = null, $branchId = null)
    {
        $branches = $this->getOfficeBranchesList();
        
        $dateCondition = function($col = 'created_at') use ($dateFrom, $dateTo) {
            if (!empty($dateFrom) && !empty($dateTo)) {
                $f = $this->db->real_escape_string($dateFrom . ' 00:00:00');
                $t = $this->db->real_escape_string($dateTo . ' 23:59:59');
                return " AND ({$col} BETWEEN '{$f}' AND '{$t}')";
            } elseif (!empty($dateFrom)) {
                $f = $this->db->real_escape_string($dateFrom . ' 00:00:00');
                return " AND ({$col} >= '{$f}')";
            } elseif (!empty($dateTo)) {
                $t = $this->db->real_escape_string($dateTo . ' 23:59:59');
                return " AND ({$col} <= '{$t}')";
            }
            return "";
        };

        $branchCondition = "";
        if (!empty($branchId) && is_numeric($branchId)) {
            $bId = (int)$branchId;
            $branchCondition = " AND t.branch_id = {$bId}";
        }

        $peaDate = $dateCondition('pea.created_at');
        $frDate  = $dateCondition('fr.created_at');
        $crDate  = $dateCondition('cr.created_at');
        $aslDate = $dateCondition('asl.created_at');

        $sql = "
            SELECT 
                t.branch_id,
                ob.name as branch_name,
                t.module_name,
                COUNT(*) as total_records,
                SUM(t.req_amount) as total_req_amount,
                SUM(t.paid_amount) as total_paid_amount,
                SUM(t.due_amount) as total_due_amount,
                SUM(t.pending_amount) as total_pending_amount
            FROM (
                -- 1. Office Procurement (Direct office_branch_id, fallback to requester's branch)
                SELECT 
                    COALESCE(pea.office_branch_id, e.office_branch_id) as branch_id,
                    'Office Procurement' as module_name,
                    pea.total_price as req_amount,
                    CASE WHEN pea.payment_status = 'paid' THEN IFNULL(pea.paid_amount, pea.total_price) ELSE 0 END as paid_amount,
                    CASE WHEN pea.mgmt_status = 'approved' AND pea.payment_status != 'paid' THEN pea.total_price ELSE 0 END as due_amount,
                    CASE WHEN (pea.mgmt_status = 'pending' OR pea.operations_status = 'pending') AND pea.overall_status != 'rejected' THEN pea.total_price ELSE 0 END as pending_amount
                FROM project_extra_assets pea
                LEFT JOIN employees e ON e.user_id = pea.requested_by
                WHERE (pea.project_id IS NULL OR pea.project_id = 0) {$peaDate}

                UNION ALL

                -- 2. Fund Requisitions without Project (Requester employee office_branch_id)
                SELECT 
                    e.office_branch_id as branch_id,
                    'Fund Requisitions' as module_name,
                    fr.amount as req_amount,
                    IFNULL(fr.paid_amount, 0) as paid_amount,
                    CASE WHEN fr.status IN ('approved', 'partially_approved') THEN (fr.amount - IFNULL(fr.paid_amount, 0)) ELSE 0 END as due_amount,
                    CASE WHEN fr.status IN ('pending', 'pending_dept', 'pending_qc') THEN fr.amount ELSE 0 END as pending_amount
                FROM fund_requisitions fr
                LEFT JOIN employees e ON e.user_id = fr.requested_by
                WHERE (fr.project_id IS NULL OR fr.project_id = 0) {$frDate}

                UNION ALL

                -- 3. Cashout Requests without Project (Requester employee office_branch_id)
                SELECT 
                    e.office_branch_id as branch_id,
                    'Cashout Requests' as module_name,
                    cr.amount as req_amount,
                    CASE 
                        WHEN cr.status IN ('fully_paid', 'paid') THEN COALESCE(cr.approved_amount, cr.amount) 
                        WHEN cr.status = 'partially_paid' THEN (COALESCE(cr.approved_amount, cr.amount) - cr.due_amount) 
                        ELSE 0 
                    END as paid_amount,
                    CASE 
                        WHEN cr.status IN ('approved', 'partially_paid') THEN cr.due_amount 
                        ELSE 0 
                    END as due_amount,
                    CASE WHEN cr.status IN ('pending', 'qc_approved') THEN cr.amount ELSE 0 END as pending_amount
                FROM cashout_requests cr
                LEFT JOIN employees e ON e.user_id = cr.requested_by
                WHERE (cr.project_id IS NULL OR cr.project_id = 0 OR cr.project_id = '') {$crDate}

                UNION ALL

                -- 4. Advance Salaries (Employee office_branch_id)
                SELECT 
                    e.office_branch_id as branch_id,
                    'Advance Salary' as module_name,
                    asl.amount as req_amount,
                    CASE WHEN asl.status IN ('paid', 'deducted') THEN asl.amount ELSE 0 END as paid_amount,
                    CASE WHEN asl.status = 'approved' THEN asl.amount ELSE 0 END as due_amount,
                    CASE WHEN asl.status IN ('pending', 'pending_hr', 'pending_accounts') THEN asl.amount ELSE 0 END as pending_amount
                FROM advance_salaries asl
                LEFT JOIN employees e ON e.id = asl.employee_id
                WHERE 1=1 {$aslDate}
            ) t
            JOIN office_branches ob ON ob.id = t.branch_id
            WHERE ob.status = 'active' {$branchCondition}
            GROUP BY t.branch_id, ob.name, t.module_name
        ";

        $summaryByBranch = [];
        // Pre-populate with canonical active branches
        foreach ($branches as $b) {
            $summaryByBranch[$b['id']] = [
                'branch_id'       => (int)$b['id'],
                'branch_name'     => $b['name'],
                'total_records'   => 0,
                'total_amount'    => 0,
                'total_paid'      => 0,
                'total_due'       => 0,
                'total_pending'   => 0,
                'modules'         => [],
                'percentage'      => 0
            ];
        }

        $res = $this->db->query($sql);
        $grandTotal = 0;
        $grandPaid = 0;
        $grandDue = 0;
        $grandRequests = 0;

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $bId = !empty($row['branch_id']) ? (int)$row['branch_id'] : 0;
                if (!isset($summaryByBranch[$bId])) {
                    continue; // Strictly skip unassigned or non-active branches
                }

                $amt = (float)$row['total_req_amount'];
                $paid = (float)$row['total_paid_amount'];
                $due = (float)$row['total_due_amount'];
                $pending = (float)$row['total_pending_amount'];
                $cnt = (int)$row['total_records'];

                $summaryByBranch[$bId]['total_records']   += $cnt;
                $summaryByBranch[$bId]['total_amount']    += $amt;
                $summaryByBranch[$bId]['total_paid']      += $paid;
                $summaryByBranch[$bId]['total_due']       += $due;
                $summaryByBranch[$bId]['total_pending']   += $pending;
                
                if (!isset($summaryByBranch[$bId]['modules'][$row['module_name']])) {
                    $summaryByBranch[$bId]['modules'][$row['module_name']] = [
                        'amount' => 0,
                        'count'  => 0
                    ];
                }
                $summaryByBranch[$bId]['modules'][$row['module_name']]['amount'] += $amt;
                $summaryByBranch[$bId]['modules'][$row['module_name']]['count']  += $cnt;

                $grandTotal    += $amt;
                $grandPaid     += $paid;
                $grandDue      += $due;
                $grandRequests += $cnt;
            }
        }

        // Calculate percentage share
        foreach ($summaryByBranch as &$bData) {
            $bData['percentage'] = ($grandTotal > 0) ? round(($bData['total_amount'] / $grandTotal) * 100, 1) : 0;
        }

        // Filter if specific branch requested
        if (!empty($branchId) && is_numeric($branchId)) {
            $filtered = [];
            if (isset($summaryByBranch[(int)$branchId])) {
                $filtered[] = $summaryByBranch[(int)$branchId];
            }
            $branchesList = $filtered;
        } else {
            $branchesList = array_values($summaryByBranch);
        }

        return [
            'branches'       => $branchesList,
            'grand_total'    => $grandTotal,
            'grand_paid'     => $grandPaid,
            'grand_due'      => $grandDue,
            'grand_requests' => $grandRequests
        ];
    }

    /**
     * Get Module Expense Cards (Total Request, Total Pending [Count & Amount], Total Amount, Total Paid, Total Due)
     */
    public function getModuleExpenseCards($dateFrom = null, $dateTo = null, $projectId = null)
    {
        // Keep approval requests table synced
        $this->approvalModel->syncMissingFundRequisitions();
        $this->approvalModel->syncMissingOfficeProcurements();
        $this->approvalModel->syncMissingLaborRequisitions();
        $this->approvalModel->syncMissingProjectOtherCosts();
        $this->approvalModel->syncMissingVendorPaymentRequests();
        $this->approvalModel->syncMissingAdvanceSalaries();
        $this->approvalModel->syncMissingCashoutRequests();

        $modules = $this->getModuleDefinitions();

        // 1. Module Cards Base Data
        $dateFilterSql = "";
        if (!empty($dateFrom) && !empty($dateTo)) {
            $from = $this->db->real_escape_string($dateFrom . ' 00:00:00');
            $to = $this->db->real_escape_string($dateTo . ' 23:59:59');
            $dateFilterSql = " AND (created_at BETWEEN '{$from}' AND '{$to}')";
        } elseif (!empty($dateFrom)) {
            $from = $this->db->real_escape_string($dateFrom . ' 00:00:00');
            $dateFilterSql = " AND (created_at >= '{$from}')";
        } elseif (!empty($dateTo)) {
            $to = $this->db->real_escape_string($dateTo . ' 23:59:59');
            $dateFilterSql = " AND (created_at <= '{$to}')";
        }

        // Project filter on approval_requests metadata or project tables
        $arProjCondition = "";
        if ($projectId === 'none' || $projectId === 'without_project') {
            $arProjCondition = " AND (meta_data NOT LIKE '%\"project_id\":%' OR meta_data LIKE '%\"project_id\":null%' OR meta_data LIKE '%\"project_id\":0%' OR meta_data LIKE '%\"project_id\":\"\"%')";
        } elseif (!empty($projectId) && is_numeric($projectId)) {
            $pId = (int)$projectId;
            $arProjCondition = " AND (meta_data LIKE '%\"project_id\":{$pId}%' OR meta_data LIKE '%\"project_id\":\"{$pId}\"%')";
        }

        // Base metrics from approval_requests
        $sqlAR = "SELECT 
                    CASE WHEN source_table = 'purchase_requests' THEN 'project_extra_assets' ELSE source_table END as mod_key,
                    COUNT(*) AS total_requests,
                    SUM(request_amount) AS total_amount,
                    SUM(CASE WHEN status IN ('pending', 'partially_approved', '') THEN 1 ELSE 0 END) AS pending_count,
                    SUM(CASE WHEN status IN ('pending', 'partially_approved', '') THEN IFNULL(request_amount, 0) ELSE 0 END) AS pending_amount,
                    SUM(CASE WHEN status IN ('approved', 'paid') THEN 1 ELSE 0 END) AS approved_count,
                    SUM(CASE WHEN status IN ('approved', 'paid') THEN IFNULL(approved_amount, request_amount) ELSE 0 END) AS approved_amount,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count
                FROM approval_requests
                WHERE 1=1 {$dateFilterSql} {$arProjCondition}
                GROUP BY mod_key";

        $resAR = $this->db->query($sqlAR);
        $arData = [];
        if ($resAR) {
            while ($r = $resAR->fetch_assoc()) {
                $arData[$r['mod_key']] = $r;
            }
        }

        // 2. Paid and Due calculations from underlying source tables
        $paidData = [];
        $dueData = [];

        // Helper for building source table WHERE clause
        $makeWhere = function($table, $projCol = 'project_id', $dateCol = 'created_at') use ($dateFrom, $dateTo, $projectId) {
            $w = " WHERE 1=1";
            if (!empty($dateFrom) && !empty($dateTo)) {
                $f = $this->db->real_escape_string($dateFrom . ' 00:00:00');
                $t = $this->db->real_escape_string($dateTo . ' 23:59:59');
                $w .= " AND ({$dateCol} BETWEEN '{$f}' AND '{$t}')";
            } elseif (!empty($dateFrom)) {
                $f = $this->db->real_escape_string($dateFrom . ' 00:00:00');
                $w .= " AND ({$dateCol} >= '{$f}')";
            } elseif (!empty($dateTo)) {
                $t = $this->db->real_escape_string($dateTo . ' 23:59:59');
                $w .= " AND ({$dateCol} <= '{$t}')";
            }

            if ($projCol !== null) {
                if ($projectId === 'none' || $projectId === 'without_project') {
                    $w .= " AND ({$projCol} IS NULL OR {$projCol} = 0)";
                } elseif (!empty($projectId) && is_numeric($projectId)) {
                    $p = (int)$projectId;
                    $w .= " AND ({$projCol} = {$p})";
                }
            }
            return $w;
        };

        // Vendor Payments
        $vWhere = $makeWhere('vendor_payment_requests');
        $vRes = $this->db->query("SELECT 
            SUM(CASE WHEN status IN ('Paid', 'Fully_Paid') THEN approved_amount WHEN status = 'Partially_Paid' THEN (approved_amount - due_amount) ELSE 0 END) as paid,
            SUM(CASE WHEN status IN ('Approved', 'QC Approved', 'Partially_Paid') THEN due_amount ELSE 0 END) as due
        FROM vendor_payment_requests {$vWhere}");
        if ($vRes && $r = $vRes->fetch_assoc()) {
            $paidData['vendor_payment_requests'] = (float)($r['paid'] ?? 0);
            $dueData['vendor_payment_requests'] = (float)($r['due'] ?? 0);
        }

        // Contractor Bills
        $cWhere = $makeWhere('contractor_bill_requests');
        $cRes = $this->db->query("SELECT 
            SUM(CASE WHEN status IN ('paid', 'fully_paid') THEN COALESCE(approved_amount, bill_amount) WHEN status = 'partially_paid' THEN (COALESCE(approved_amount, bill_amount) - due_amount) ELSE 0 END) as paid,
            SUM(CASE WHEN status = 'approved' THEN COALESCE(approved_amount, bill_amount) WHEN status = 'partially_paid' THEN due_amount ELSE 0 END) as due
        FROM contractor_bill_requests {$cWhere}");
        if ($cRes && $r = $cRes->fetch_assoc()) {
            $paidData['contractor_bill_requests'] = (float)($r['paid'] ?? 0);
            $dueData['contractor_bill_requests'] = (float)($r['due'] ?? 0);
        }

        // Fund Requisitions
        $frWhere = $makeWhere('fund_requisitions');
        $frRes = $this->db->query("SELECT 
            SUM(paid_amount) as paid,
            SUM(CASE WHEN status IN ('approved', 'partially_approved') THEN (amount - IFNULL(paid_amount, 0)) ELSE 0 END) as due
        FROM fund_requisitions {$frWhere}");
        if ($frRes && $r = $frRes->fetch_assoc()) {
            $paidData['fund_requisitions'] = (float)($r['paid'] ?? 0);
            $dueData['fund_requisitions'] = (float)($r['due'] ?? 0);
        }

        // Labor Requisitions
        $lrWhere = $makeWhere('con_labor_requisitions');
        $lrRes = $this->db->query("SELECT 
            SUM(total_paid) as paid,
            SUM(CASE WHEN status IN ('management_approved', 'partially_paid') THEN (COALESCE(approved_amount, total_amount) - IFNULL(total_paid, 0)) ELSE 0 END) as due
        FROM con_labor_requisitions {$lrWhere}");
        if ($lrRes && $r = $lrRes->fetch_assoc()) {
            $paidData['con_labor_requisitions'] = (float)($r['paid'] ?? 0);
            $dueData['con_labor_requisitions'] = (float)($r['due'] ?? 0);
        }

        // Project Extra Assets (Office Procurement)
        $peaWhere = $makeWhere('project_extra_assets');
        $peaRes = $this->db->query("SELECT 
            SUM(CASE WHEN payment_status = 'paid' THEN IFNULL(paid_amount, total_price) ELSE 0 END) as paid,
            SUM(CASE WHEN mgmt_status = 'approved' AND payment_status != 'paid' THEN total_price ELSE 0 END) as due
        FROM project_extra_assets {$peaWhere}");
        if ($peaRes && $r = $peaRes->fetch_assoc()) {
            $paidData['project_extra_assets'] = (float)($r['paid'] ?? 0);
            $dueData['project_extra_assets'] = (float)($r['due'] ?? 0);
        }

        // Project Other Costs
        $pocWhere = $makeWhere('project_other_costs');
        $pocRes = $this->db->query("SELECT 
            SUM(CASE WHEN status = 'paid' THEN COALESCE(payment_amount, approved_amount, request_amount) ELSE 0 END) as paid,
            SUM(CASE WHEN status = 'management_approved' THEN COALESCE(approved_amount, request_amount) ELSE 0 END) as due
        FROM project_other_costs {$pocWhere}");
        if ($pocRes && $r = $pocRes->fetch_assoc()) {
            $paidData['project_other_costs'] = (float)($r['paid'] ?? 0);
            $dueData['project_other_costs'] = (float)($r['due'] ?? 0);
        }

        // Cashout Requests
        $coWhere = $makeWhere('cashout_requests');
        $coRes = $this->db->query("SELECT 
            SUM(CASE WHEN status IN ('fully_paid', 'paid') THEN COALESCE(approved_amount, amount) WHEN status = 'partially_paid' THEN (COALESCE(approved_amount, amount) - due_amount) ELSE 0 END) as paid,
            SUM(CASE WHEN status IN ('approved', 'partially_paid') THEN due_amount ELSE 0 END) as due
        FROM cashout_requests {$coWhere}");
        if ($coRes && $r = $coRes->fetch_assoc()) {
            $paidData['cashout_requests'] = (float)($r['paid'] ?? 0);
            $dueData['cashout_requests'] = (float)($r['due'] ?? 0);
        }

        // Payslips
        $psWhere = $makeWhere('payslips', 'payroll_project_id');
        $psRes = $this->db->query("SELECT 
            SUM(CASE WHEN status = 'paid' OR payment_status = 'paid' THEN net_salary ELSE 0 END) as paid,
            SUM(CASE WHEN status = 'approved' AND (payment_status IS NULL OR payment_status != 'paid') THEN net_salary ELSE 0 END) as due
        FROM payslips {$psWhere}");
        if ($psRes && $r = $psRes->fetch_assoc()) {
            $paidData['payslips'] = (float)($r['paid'] ?? 0);
            $dueData['payslips'] = (float)($r['due'] ?? 0);
        }

        // Advance Salaries (general, no project)
        if (empty($projectId) || $projectId === 'none' || $projectId === 'without_project') {
            $asWhere = $makeWhere('advance_salaries', null);
            $asRes = $this->db->query("SELECT 
                SUM(CASE WHEN status IN ('paid', 'deducted') THEN amount ELSE 0 END) as paid,
                SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as due
            FROM advance_salaries {$asWhere}");
            if ($asRes && $r = $asRes->fetch_assoc()) {
                $paidData['advance_salaries'] = (float)($r['paid'] ?? 0);
                $dueData['advance_salaries'] = (float)($r['due'] ?? 0);
            }
        } else {
            $paidData['advance_salaries'] = 0;
            $dueData['advance_salaries'] = 0;
        }

        // Sales Incentive Claims
        $picWhere = $makeWhere('project_incentive_claims');
        $picRes = $this->db->query("SELECT 
            (SELECT IFNULL(SUM(payment_amount), 0) FROM project_incentive_payments) as paid,
            SUM(CASE WHEN claim_status = 'approved' THEN incentive_amount ELSE 0 END) as due
        FROM project_incentive_claims {$picWhere}");
        if ($picRes && $r = $picRes->fetch_assoc()) {
            $paidData['project_incentive_claims'] = (float)($r['paid'] ?? 0);
            $dueData['project_incentive_claims'] = (float)($r['due'] ?? 0);
        }

        // Assemble cards
        $cards = [];
        foreach ($modules as $modKey => $def) {
            $stats = $arData[$modKey] ?? [
                'total_requests'  => 0,
                'total_amount'    => 0,
                'pending_count'   => 0,
                'pending_amount'  => 0,
                'approved_count'  => 0,
                'approved_amount' => 0,
                'rejected_count'  => 0,
            ];

            $paid = $paidData[$modKey] ?? 0;
            $due  = $dueData[$modKey] ?? 0;

            $cards[$modKey] = array_merge($def, [
                'total_requests'  => (int)$stats['total_requests'],
                'total_amount'    => (float)$stats['total_amount'],
                'pending_count'   => (int)$stats['pending_count'],
                'pending_amount'  => (float)$stats['pending_amount'],
                'approved_count'  => (int)$stats['approved_count'],
                'approved_amount' => (float)$stats['approved_amount'],
                'rejected_count'  => (int)$stats['rejected_count'],
                'total_paid'      => $paid,
                'total_due'       => $due,
            ]);
        }

        return $cards;
    }

    /**
     * Get Project-Wise Cost Summary for a specific project or overall
     */
    public function getProjectCostSummary($projectId = null, $dateFrom = null, $dateTo = null)
    {
        $cards = $this->getModuleExpenseCards($dateFrom, $dateTo, $projectId);

        $summary = [
            'total_requests' => 0,
            'total_amount'   => 0,
            'pending_count'  => 0,
            'pending_amount' => 0,
            'total_paid'     => 0,
            'total_due'      => 0,
            'project_name'   => 'All Projects & Departments'
        ];

        foreach ($cards as $c) {
            $summary['total_requests'] += $c['total_requests'];
            $summary['total_amount']   += $c['total_amount'];
            $summary['pending_count']  += $c['pending_count'];
            $summary['pending_amount'] += $c['pending_amount'];
            $summary['total_paid']     += $c['total_paid'];
            $summary['total_due']      += $c['total_due'];
        }

        if ($projectId === 'none' || $projectId === 'without_project') {
            $summary['project_name'] = 'Without Project / General Overhead';
        } elseif (!empty($projectId) && is_numeric($projectId)) {
            $pRes = $this->db->query("SELECT name FROM projects WHERE id = " . (int)$projectId);
            if ($pRes && $pRow = $pRes->fetch_assoc()) {
                $summary['project_name'] = $pRow['name'];
            }
        }

        return $summary;
    }

    /**
     * Get Top Expense Projects (#1 highest cost project + ranked leaderboard)
     */
    public function getTopExpenseProjects($limit = 5, $dateFrom = null, $dateTo = null)
    {
        $dateCondition = "";
        if (!empty($dateFrom) && !empty($dateTo)) {
            $f = $this->db->real_escape_string($dateFrom . ' 00:00:00');
            $t = $this->db->real_escape_string($dateTo . ' 23:59:59');
            $dateCondition = " AND (created_at BETWEEN '{$f}' AND '{$t}')";
        } elseif (!empty($dateFrom)) {
            $f = $this->db->real_escape_string($dateFrom . ' 00:00:00');
            $dateCondition = " AND (created_at >= '{$f}')";
        } elseif (!empty($dateTo)) {
            $t = $this->db->real_escape_string($dateTo . ' 23:59:59');
            $dateCondition = " AND (created_at <= '{$t}')";
        }

        $sql = "
            SELECT 
                t.project_id,
                CASE 
                    WHEN t.project_id IS NULL OR t.project_id = 0 THEN 'Without Project / General'
                    ELSE IFNULL(p.name, CONCAT('Project #', t.project_id))
                END as project_name,
                COUNT(*) as total_records,
                SUM(t.req_amount) as total_req_amount,
                SUM(t.paid_amount) as total_paid_amount,
                SUM(t.due_amount) as total_due_amount
            FROM (
                -- 1. Vendor Payments
                SELECT 
                    vpr.project_id,
                    COALESCE(vpr.approved_amount, vpr.invoice_amount) as req_amount,
                    CASE 
                        WHEN vpr.status IN ('Paid', 'Fully_Paid') THEN vpr.approved_amount 
                        WHEN vpr.status = 'Partially_Paid' THEN (vpr.approved_amount - vpr.due_amount) 
                        ELSE 0 
                    END as paid_amount,
                    CASE 
                        WHEN vpr.status IN ('Approved', 'QC Approved', 'Partially_Paid') THEN vpr.due_amount 
                        ELSE 0 
                    END as due_amount,
                    vpr.created_at
                FROM vendor_payment_requests vpr
                WHERE 1=1 {$dateCondition}
                
                UNION ALL
                
                -- 2. Contractor Bills
                SELECT 
                    cbr.project_id,
                    cbr.bill_amount as req_amount,
                    CASE 
                        WHEN cbr.status IN ('paid', 'fully_paid') THEN COALESCE(cbr.approved_amount, cbr.bill_amount) 
                        WHEN cbr.status = 'partially_paid' THEN (COALESCE(cbr.approved_amount, cbr.bill_amount) - cbr.due_amount) 
                        ELSE 0 
                    END as paid_amount,
                    CASE 
                        WHEN cbr.status = 'approved' THEN COALESCE(cbr.approved_amount, cbr.bill_amount) 
                        WHEN cbr.status = 'partially_paid' THEN cbr.due_amount 
                        ELSE 0 
                    END as due_amount,
                    cbr.created_at
                FROM contractor_bill_requests cbr
                WHERE 1=1 {$dateCondition}

                UNION ALL

                -- 3. Project Other Costs
                SELECT 
                    poc.project_id,
                    poc.request_amount as req_amount,
                    CASE WHEN poc.status = 'paid' THEN COALESCE(poc.payment_amount, poc.approved_amount, poc.request_amount) ELSE 0 END as paid_amount,
                    CASE WHEN poc.status = 'management_approved' THEN COALESCE(poc.approved_amount, poc.request_amount) ELSE 0 END as due_amount,
                    poc.created_at
                FROM project_other_costs poc
                WHERE 1=1 {$dateCondition}

                UNION ALL

                -- 4. Cashout Requests
                SELECT 
                    cr.project_id,
                    cr.amount as req_amount,
                    CASE 
                        WHEN cr.status IN ('fully_paid', 'paid') THEN COALESCE(cr.approved_amount, cr.amount) 
                        WHEN cr.status = 'partially_paid' THEN (COALESCE(cr.approved_amount, cr.amount) - cr.due_amount) 
                        ELSE 0 
                    END as paid_amount,
                    CASE 
                        WHEN cr.status IN ('approved', 'partially_paid') THEN cr.due_amount 
                        ELSE 0 
                    END as due_amount,
                    cr.created_at
                FROM cashout_requests cr
                WHERE 1=1 {$dateCondition}

                UNION ALL

                -- 5. Fund Requisitions
                SELECT 
                    fr.project_id,
                    fr.amount as req_amount,
                    IFNULL(fr.paid_amount, 0) as paid_amount,
                    CASE WHEN fr.status IN ('approved', 'partially_approved') THEN (fr.amount - IFNULL(fr.paid_amount, 0)) ELSE 0 END as due_amount,
                    fr.created_at
                FROM fund_requisitions fr
                WHERE 1=1 {$dateCondition}

                UNION ALL

                -- 6. Labor Requisitions
                SELECT 
                    lr.project_id,
                    lr.total_amount as req_amount,
                    IFNULL(lr.total_paid, 0) as paid_amount,
                    CASE WHEN lr.status IN ('management_approved', 'partially_paid') THEN (COALESCE(lr.approved_amount, lr.total_amount) - IFNULL(lr.total_paid, 0)) ELSE 0 END as due_amount,
                    lr.created_at
                FROM con_labor_requisitions lr
                WHERE 1=1 {$dateCondition}

                UNION ALL

                -- 7. Office Procurement
                SELECT 
                    pea.project_id,
                    pea.total_price as req_amount,
                    CASE WHEN pea.payment_status = 'paid' THEN IFNULL(pea.paid_amount, pea.total_price) ELSE 0 END as paid_amount,
                    CASE WHEN pea.mgmt_status = 'approved' AND pea.payment_status != 'paid' THEN pea.total_price ELSE 0 END as due_amount,
                    pea.created_at
                FROM project_extra_assets pea
                WHERE 1=1 {$dateCondition}
            ) t
            LEFT JOIN projects p ON p.id = t.project_id
            GROUP BY t.project_id, p.name
            ORDER BY total_paid_amount DESC, total_req_amount DESC
            LIMIT " . (int)$limit;

        $res = $this->db->query($sql);
        $topProjects = [];
        $grandTotal = 0;
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $topProjects[] = $r;
                $grandTotal += (float)$r['total_req_amount'];
            }
        }

        // Add percentage
        foreach ($topProjects as &$tp) {
            $tp['percentage'] = ($grandTotal > 0) ? round(((float)$tp['total_req_amount'] / $grandTotal) * 100, 1) : 0;
        }

        $highestProject = !empty($topProjects) ? $topProjects[0] : null;

        return [
            'highest'      => $highestProject,
            'leaderboard'  => $topProjects,
            'top_total'    => $grandTotal
        ];
    }

    /**
     * Get Unified Expense Ledger Records across all modules with filters
     */
    public function getExpenseLedgerRecords($filters = [])
    {
        $projectId = $filters['project_id'] ?? '';
        $branchId  = $filters['branch_id'] ?? '';
        $dateFrom  = $filters['from_date'] ?? '';
        $dateTo    = $filters['to_date'] ?? '';
        $moduleKey = $filters['module'] ?? '';
        $search    = strtolower(trim($filters['search'] ?? ''));

        $makeDateFilter = function($col = 'created_at') use ($dateFrom, $dateTo) {
            if (!empty($dateFrom) && !empty($dateTo)) {
                $f = $this->db->real_escape_string($dateFrom . ' 00:00:00');
                $t = $this->db->real_escape_string($dateTo . ' 23:59:59');
                return " AND ({$col} BETWEEN '{$f}' AND '{$t}')";
            } elseif (!empty($dateFrom)) {
                $f = $this->db->real_escape_string($dateFrom . ' 00:00:00');
                return " AND ({$col} >= '{$f}')";
            } elseif (!empty($dateTo)) {
                $t = $this->db->real_escape_string($dateTo . ' 23:59:59');
                return " AND ({$col} <= '{$t}')";
            }
            return "";
        };

        $queries = [];

        // 1. Vendor Payments (Project only — excluded when filtering by branch)
        if ((empty($moduleKey) || $moduleKey === 'vendor_payment_requests') && empty($branchId)) {
            $dFilter = $makeDateFilter('vpr.created_at');
            $pFilter = "";
            if ($projectId === 'none' || $projectId === 'without_project') {
                $pFilter = " AND (vpr.project_id IS NULL OR vpr.project_id = 0)";
            } elseif (!empty($projectId) && is_numeric($projectId)) {
                $pFilter = " AND (vpr.project_id = " . (int)$projectId . ")";
            }

            $queries[] = "
                SELECT 
                    'vendor_payment_requests' as module_key,
                    'Vendor Payment' as module_name,
                    vpr.id as record_id,
                    vpr.project_id,
                    p.name as project_name,
                    NULL as branch_id,
                    NULL as branch_name,
                    IFNULL(v.name, 'Vendor') as payee_name,
                    'Vendor' as payee_type,
                    CONCAT('Vendor Payment — ', IFNULL(v.name, 'Vendor'), ' (Inv: ', IFNULL(vpr.invoice_number, '-'), ')') as purpose_title,
                    COALESCE(vpr.approved_amount, vpr.invoice_amount) as req_amount,
                    IFNULL(vpr.approved_amount, vpr.invoice_amount) as approved_amount,
                    CASE 
                        WHEN vpr.status IN ('Paid', 'Fully_Paid') THEN vpr.approved_amount 
                        WHEN vpr.status = 'Partially_Paid' THEN (vpr.approved_amount - vpr.due_amount) 
                        ELSE 0 
                    END as paid_amount,
                    CASE 
                        WHEN vpr.status IN ('Approved', 'QC Approved', 'Partially_Paid') THEN vpr.due_amount 
                        ELSE 0 
                    END as due_amount,
                    vpr.status,
                    vpr.created_at,
                    CONCAT('vendor-payment-requests?search=', vpr.id) as view_url
                FROM vendor_payment_requests vpr
                LEFT JOIN projects p ON p.id = vpr.project_id
                LEFT JOIN vendors v ON v.id = vpr.vendor_id
                WHERE 1=1 {$dFilter} {$pFilter}
            ";
        }

        // 2. Contractor Bills (Project only — excluded when filtering by branch)
        if ((empty($moduleKey) || $moduleKey === 'contractor_bill_requests') && empty($branchId)) {
            $dFilter = $makeDateFilter('cbr.created_at');
            $pFilter = "";
            if ($projectId === 'none' || $projectId === 'without_project') {
                $pFilter = " AND (cbr.project_id IS NULL OR cbr.project_id = 0)";
            } elseif (!empty($projectId) && is_numeric($projectId)) {
                $pFilter = " AND (cbr.project_id = " . (int)$projectId . ")";
            }

            $queries[] = "
                SELECT 
                    'contractor_bill_requests' as module_key,
                    'Contractor Bill' as module_name,
                    cbr.id as record_id,
                    cbr.project_id,
                    p.name as project_name,
                    NULL as branch_id,
                    NULL as branch_name,
                    IFNULL(c.name, 'Contractor') as payee_name,
                    'Contractor' as payee_type,
                    CONCAT('Contractor Bill — ', IFNULL(c.name, 'Contractor'), ' (', IFNULL(cbr.bill_type, 'Bill'), ')') as purpose_title,
                    cbr.bill_amount as req_amount,
                    IFNULL(cbr.approved_amount, cbr.bill_amount) as approved_amount,
                    CASE 
                        WHEN cbr.status IN ('paid', 'fully_paid') THEN COALESCE(cbr.approved_amount, cbr.bill_amount) 
                        WHEN cbr.status = 'partially_paid' THEN (COALESCE(cbr.approved_amount, cbr.bill_amount) - cbr.due_amount) 
                        ELSE 0 
                    END as paid_amount,
                    CASE 
                        WHEN cbr.status = 'approved' THEN COALESCE(cbr.approved_amount, cbr.bill_amount) 
                        WHEN cbr.status = 'partially_paid' THEN cbr.due_amount 
                        ELSE 0 
                    END as due_amount,
                    cbr.status,
                    cbr.created_at,
                    CONCAT('contractor-bills') as view_url
                FROM contractor_bill_requests cbr
                LEFT JOIN projects p ON p.id = cbr.project_id
                LEFT JOIN contractors c ON c.id = cbr.operator_id
                WHERE 1=1 {$dFilter} {$pFilter}
            ";
        }

        // 3. Project Other Costs (Strictly Project Cost — excluded when filtering by branch)
        if ((empty($moduleKey) || $moduleKey === 'project_other_costs') && empty($branchId)) {
            $dFilter = $makeDateFilter('poc.created_at');
            $pFilter = "";
            if ($projectId === 'none' || $projectId === 'without_project') {
                $pFilter = " AND (poc.project_id IS NULL OR poc.project_id = 0)";
            } elseif (!empty($projectId) && is_numeric($projectId)) {
                $pFilter = " AND (poc.project_id = " . (int)$projectId . ")";
            }

            $queries[] = "
                SELECT 
                    'project_other_costs' as module_key,
                    'Project Other Cost' as module_name,
                    poc.id as record_id,
                    poc.project_id,
                    p.name as project_name,
                    NULL as branch_id,
                    NULL as branch_name,
                    IFNULL(COALESCE(u.full_name, u.user_name), 'Staff') as payee_name,
                    'Staff / Project' as payee_type,
                    CONCAT('Other Cost: ', IFNULL(poc.purpose, poc.rcost_type)) as purpose_title,
                    poc.request_amount as req_amount,
                    IFNULL(poc.approved_amount, poc.request_amount) as approved_amount,
                    CASE WHEN poc.status = 'paid' THEN COALESCE(poc.payment_amount, poc.approved_amount, poc.request_amount) ELSE 0 END as paid_amount,
                    CASE WHEN poc.status = 'management_approved' THEN COALESCE(poc.approved_amount, poc.request_amount) ELSE 0 END as due_amount,
                    poc.status,
                    poc.created_at,
                    CONCAT('project-other-costs') as view_url
                FROM project_other_costs poc
                LEFT JOIN projects p ON p.id = poc.project_id
                LEFT JOIN users u ON u.id = poc.created_by_id
                WHERE 1=1 {$dFilter} {$pFilter}
            ";
        }

        // 4. Cashout Requests (Branch mapped from requester's employee record)
        if (empty($moduleKey) || $moduleKey === 'cashout_requests') {
            $dFilter = $makeDateFilter('cr.created_at');
            $pFilter = "";
            if ($projectId === 'none' || $projectId === 'without_project') {
                $pFilter = " AND (cr.project_id IS NULL OR cr.project_id = 0 OR cr.project_id = '')";
            } elseif (!empty($projectId) && is_numeric($projectId)) {
                $pFilter = " AND (cr.project_id = " . (int)$projectId . ")";
            }
            $bFilter = "";
            if (!empty($branchId) && is_numeric($branchId)) {
                $bFilter = " AND e_cr.office_branch_id = " . (int)$branchId;
            }

            $queries[] = "
                SELECT 
                    'cashout_requests' as module_key,
                    'Cashout Request' as module_name,
                    cr.id as record_id,
                    cr.project_id,
                    p.name as project_name,
                    e_cr.office_branch_id as branch_id,
                    ob_cr.name as branch_name,
                    IFNULL(cr.purpose_paid_to, IFNULL(COALESCE(u.full_name, u.user_name), 'Accounts')) as payee_name,
                    'Payee / Vendor' as payee_type,
                    CONCAT('Cashout: ', IFNULL(cr.purpose_paid_to, 'Office / Site')) as purpose_title,
                    cr.amount as req_amount,
                    IFNULL(cr.approved_amount, cr.amount) as approved_amount,
                    CASE 
                        WHEN cr.status IN ('fully_paid', 'paid') THEN COALESCE(cr.approved_amount, cr.amount) 
                        WHEN cr.status = 'partially_paid' THEN (COALESCE(cr.approved_amount, cr.amount) - cr.due_amount) 
                        ELSE 0 
                    END as paid_amount,
                    CASE 
                        WHEN cr.status IN ('approved', 'partially_paid') THEN cr.due_amount 
                        ELSE 0 
                    END as due_amount,
                    cr.status,
                    cr.created_at,
                    CONCAT('cashout-requests') as view_url
                FROM cashout_requests cr
                LEFT JOIN projects p ON p.id = cr.project_id
                LEFT JOIN users u ON u.id = cr.requested_by
                LEFT JOIN employees e_cr ON e_cr.user_id = cr.requested_by
                LEFT JOIN office_branches ob_cr ON ob_cr.id = e_cr.office_branch_id
                WHERE 1=1 {$dFilter} {$pFilter} {$bFilter}
            ";
        }

        // 5. Fund Requisitions (Branch mapped from requester's employee record)
        if (empty($moduleKey) || $moduleKey === 'fund_requisitions') {
            $dFilter = $makeDateFilter('fr.created_at');
            $pFilter = "";
            if ($projectId === 'none' || $projectId === 'without_project') {
                $pFilter = " AND (fr.project_id IS NULL OR fr.project_id = 0)";
            } elseif (!empty($projectId) && is_numeric($projectId)) {
                $pFilter = " AND (fr.project_id = " . (int)$projectId . ")";
            }
            $bFilter = "";
            if (!empty($branchId) && is_numeric($branchId)) {
                $bFilter = " AND e_fr.office_branch_id = " . (int)$branchId;
            }

            $queries[] = "
                SELECT 
                    'fund_requisitions' as module_key,
                    'Fund Requisition' as module_name,
                    fr.id as record_id,
                    fr.project_id,
                    p.name as project_name,
                    e_fr.office_branch_id as branch_id,
                    ob_fr.name as branch_name,
                    IFNULL(COALESCE(u.full_name, u.user_name), d.name) as payee_name,
                    'Employee / Dept' as payee_type,
                    CONCAT('Fund Req: ', IFNULL(fr.purpose, fr.requisition_type)) as purpose_title,
                    fr.amount as req_amount,
                    IFNULL(fr.approved_amount, fr.amount) as approved_amount,
                    IFNULL(fr.paid_amount, 0) as paid_amount,
                    CASE WHEN fr.status IN ('approved', 'partially_approved') THEN (fr.amount - IFNULL(fr.paid_amount, 0)) ELSE 0 END as due_amount,
                    fr.status,
                    fr.created_at,
                    CONCAT('fund-requisitions') as view_url
                FROM fund_requisitions fr
                LEFT JOIN projects p ON p.id = fr.project_id
                LEFT JOIN users u ON u.id = fr.requested_by
                LEFT JOIN departments d ON d.id = fr.department_id
                LEFT JOIN employees e_fr ON e_fr.user_id = fr.requested_by
                LEFT JOIN office_branches ob_fr ON ob_fr.id = e_fr.office_branch_id
                WHERE 1=1 {$dFilter} {$pFilter} {$bFilter}
            ";
        }

        // 6. Labor Bills (Project only — excluded when filtering by branch)
        if ((empty($moduleKey) || $moduleKey === 'con_labor_requisitions') && empty($branchId)) {
            $dFilter = $makeDateFilter('lr.created_at');
            $pFilter = "";
            if ($projectId === 'none' || $projectId === 'without_project') {
                $pFilter = " AND (lr.project_id IS NULL OR lr.project_id = 0)";
            } elseif (!empty($projectId) && is_numeric($projectId)) {
                $pFilter = " AND (lr.project_id = " . (int)$projectId . ")";
            }

            $queries[] = "
                SELECT 
                    'con_labor_requisitions' as module_key,
                    'SE Labor Bill' as module_name,
                    lr.id as record_id,
                    lr.project_id,
                    p.name as project_name,
                    NULL as branch_id,
                    NULL as branch_name,
                    IFNULL(COALESCE(u.full_name, u.user_name), 'Site Engineer') as payee_name,
                    'Labor Team / SE' as payee_type,
                    CONCAT('Labor Bill: ', IFNULL(lr.requisition_no, 'Bill'), ' (', lr.labor_count, ' Labors)') as purpose_title,
                    lr.total_amount as req_amount,
                    IFNULL(lr.approved_amount, lr.total_amount) as approved_amount,
                    IFNULL(lr.total_paid, 0) as paid_amount,
                    CASE WHEN lr.status IN ('management_approved', 'partially_paid') THEN (COALESCE(lr.approved_amount, lr.total_amount) - IFNULL(lr.total_paid, 0)) ELSE 0 END as due_amount,
                    lr.status,
                    lr.created_at,
                    CONCAT('con-labor-requisitions') as view_url
                FROM con_labor_requisitions lr
                LEFT JOIN projects p ON p.id = lr.project_id
                LEFT JOIN users u ON u.id = lr.requested_by
                WHERE 1=1 {$dFilter} {$pFilter}
            ";
        }

        // 7. Office Procurement (Direct office_branch_id from project_extra_assets)
        if (empty($moduleKey) || $moduleKey === 'project_extra_assets') {
            $dFilter = $makeDateFilter('pea.created_at');
            $pFilter = "";
            if ($projectId === 'none' || $projectId === 'without_project') {
                $pFilter = " AND (pea.project_id IS NULL OR pea.project_id = 0)";
            } elseif (!empty($projectId) && is_numeric($projectId)) {
                $pFilter = " AND (pea.project_id = " . (int)$projectId . ")";
            }
            $bFilter = "";
            if (!empty($branchId) && is_numeric($branchId)) {
                $bId = (int)$branchId;
                $bFilter = " AND (pea.office_branch_id = {$bId} OR (pea.office_branch_id IS NULL AND e_pea.office_branch_id = {$bId}))";
            }

            $queries[] = "
                SELECT 
                    'project_extra_assets' as module_key,
                    'Office Procurement' as module_name,
                    pea.id as record_id,
                    pea.project_id,
                    p.name as project_name,
                    COALESCE(pea.office_branch_id, e_pea.office_branch_id) as branch_id,
                    COALESCE(ob.name, ob_pea.name) as branch_name,
                    IFNULL(pea.vendor_name, IFNULL(COALESCE(u.full_name, u.user_name), 'Procurement')) as payee_name,
                    'Vendor / Staff' as payee_type,
                    CONCAT('Procurement: ', IFNULL(pea.item_name, 'Assets'), ' (Qty: ', pea.quantity, ')') as purpose_title,
                    pea.total_price as req_amount,
                    pea.total_price as approved_amount,
                    CASE WHEN pea.payment_status = 'paid' THEN IFNULL(pea.paid_amount, pea.total_price) ELSE 0 END as paid_amount,
                    CASE WHEN pea.mgmt_status = 'approved' AND pea.payment_status != 'paid' THEN pea.total_price ELSE 0 END as due_amount,
                    pea.overall_status as status,
                    pea.created_at,
                    CONCAT('office-procurement') as view_url
                FROM project_extra_assets pea
                LEFT JOIN projects p ON p.id = pea.project_id
                LEFT JOIN users u ON u.id = pea.requested_by
                LEFT JOIN office_branches ob ON ob.id = pea.office_branch_id
                LEFT JOIN employees e_pea ON e_pea.user_id = pea.requested_by
                LEFT JOIN office_branches ob_pea ON ob_pea.id = e_pea.office_branch_id
                WHERE 1=1 {$dFilter} {$pFilter} {$bFilter}
            ";
        }

        // 8. Advance Salaries (General office overhead)
        if ((empty($moduleKey) || $moduleKey === 'advance_salaries') && (empty($projectId) || $projectId === 'none' || $projectId === 'without_project')) {
            $dFilter = $makeDateFilter('asl.created_at');
            $bFilter = "";
            if (!empty($branchId) && is_numeric($branchId)) {
                $bFilter = " AND e_asl.office_branch_id = " . (int)$branchId;
            }
            $queries[] = "
                SELECT 
                    'advance_salaries' as module_key,
                    'Advance Salary' as module_name,
                    asl.id as record_id,
                    NULL as project_id,
                    'Without Project / General' as project_name,
                    e_asl.office_branch_id as branch_id,
                    ob_asl.name as branch_name,
                    IFNULL(COALESCE(u_asl.full_name, u_asl.user_name), 'Employee') as payee_name,
                    'Employee' as payee_type,
                    CONCAT('Advance Salary: ', IFNULL(asl.reason, 'Employee Advance')) as purpose_title,
                    asl.amount as req_amount,
                    asl.amount as approved_amount,
                    CASE WHEN asl.status IN ('paid', 'deducted') THEN asl.amount ELSE 0 END as paid_amount,
                    CASE WHEN asl.status = 'approved' THEN asl.amount ELSE 0 END as due_amount,
                    asl.status,
                    asl.created_at,
                    CONCAT('advance-salaries') as view_url
                FROM advance_salaries asl
                LEFT JOIN employees e_asl ON e_asl.id = asl.employee_id
                LEFT JOIN users u_asl ON u_asl.id = e_asl.user_id
                LEFT JOIN office_branches ob_asl ON ob_asl.id = e_asl.office_branch_id
                WHERE 1=1 {$dFilter} {$bFilter}
            ";
        }

        if (empty($queries)) {
            return [];
        }

        $records = [];
        foreach ($queries as $q) {
            $res = $this->db->query($q);
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    if (!empty($search)) {
                        $haystack = strtolower(
                            ($row['payee_name'] ?? '') . ' ' .
                            ($row['project_name'] ?? '') . ' ' .
                            ($row['purpose_title'] ?? '') . ' ' .
                            ($row['module_name'] ?? '') . ' ' .
                            ($row['status'] ?? '')
                        );
                        if (strpos($haystack, $search) === false) {
                            continue;
                        }
                    }
                    $records[] = $row;
                }
            }
        }

        // Sort descending by creation date
        usort($records, function($a, $b) {
            return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
        });

        // Limit to 1000 records for fast browser rendering
        if (count($records) > 1000) {
            $records = array_slice($records, 0, 1000);
        }

        return $records;
    }

    /**
     * Get ApexCharts Expense Distribution & Payout Data
     */
    public function getChartPaidVsDueData($dateFrom = null, $dateTo = null, $projectId = null)
    {
        $cards = $this->getModuleExpenseCards($dateFrom, $dateTo, $projectId);

        $labels = [];
        $paidSeries = [];
        $dueSeries = [];
        $totalPaid = 0;
        $totalDue = 0;
        $totalExpense = 0;

        $moduleData = [];
        foreach ($cards as $c) {
            $paid = (float)$c['total_paid'];
            $due  = (float)$c['total_due'];
            $exp  = (float)$c['total_amount'];
            $reqs = (int)$c['total_requests'];

            $totalPaid    += $paid;
            $totalDue     += $due;
            $totalExpense += $exp;

            if ($exp > 0 || $paid > 0 || $due > 0 || $reqs > 0) {
                $labels[]     = $c['name'];
                $paidSeries[] = $paid;
                $dueSeries[]  = $due;

                if ($exp > 0) {
                    $moduleData[] = [
                        'name'   => $c['name'],
                        'amount' => round($exp, 2),
                        'count'  => $reqs
                    ];
                }
            }
        }

        // Sort moduleData descending by expense amount
        usort($moduleData, function($a, $b) {
            return ($b['amount'] < $a['amount']) ? -1 : (($b['amount'] > $a['amount']) ? 1 : 0);
        });

        $moduleLabels = [];
        $moduleSeries = [];
        $moduleCounts = [];
        foreach ($moduleData as $md) {
            $moduleLabels[] = $md['name'];
            $moduleSeries[] = $md['amount'];
            $moduleCounts[] = $md['count'];
        }

        // Top projects breakdown for project-wise pie view (like sales-dashboard)
        $topProjects = $this->getTopExpenseProjects(10, $dateFrom, $dateTo);
        $projectLabels = [];
        $projectSeries = [];
        $projectCounts = [];

        if (!empty($topProjects['leaderboard'])) {
            foreach ($topProjects['leaderboard'] as $tp) {
                $pAmt = (float)$tp['total_paid_amount'] > 0 ? (float)$tp['total_paid_amount'] : (float)$tp['total_req_amount'];
                if ($pAmt > 0) {
                    $projectLabels[] = $tp['project_name'];
                    $projectSeries[] = round($pAmt, 2);
                    $projectCounts[] = (int)($tp['total_records'] ?? 0);
                }
            }
        }

        // Office Branch breakdown for non-project expenses
        $branchSummary = $this->getOfficeBranchCostSummary($dateFrom, $dateTo);
        $branchLabels = [];
        $branchSeries = [];
        $branchCounts = [];
        if (!empty($branchSummary['branches'])) {
            foreach ($branchSummary['branches'] as $b) {
                $bAmt = (float)$b['total_amount'];
                if ($bAmt > 0) {
                    $branchLabels[] = $b['branch_name'];
                    $branchSeries[] = round($bAmt, 2);
                    $branchCounts[] = (int)($b['total_records'] ?? 0);
                }
            }
        }

        return [
            'labels'         => $labels,
            'paid_series'    => $paidSeries,
            'due_series'     => $dueSeries,
            'total_paid'     => $totalPaid,
            'total_due'      => $totalDue,
            'total_expense'  => $totalExpense,
            'module_labels'  => $moduleLabels,
            'module_series'  => $moduleSeries,
            'module_counts'  => $moduleCounts,
            'project_labels' => $projectLabels,
            'project_series' => $projectSeries,
            'project_counts' => $projectCounts,
            'branch_labels'  => $branchLabels,
            'branch_series'  => $branchSeries,
            'branch_counts'  => $branchCounts,
        ];
    }
}

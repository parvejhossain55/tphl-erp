<?php
namespace Phase2;

use Controller;

class Con_DashboardController extends Controller
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // =========================================================================
    // DASHBOARD INDEX
    // =========================================================================

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // Reset active project context when returning to global dashboard
        unset($_SESSION['active_project_id']);
        unset($_SESSION['active_project_name']);
        unset($_SESSION['active_project_workflow_version']);

        $userRole = (int)($_SESSION['user']['role_id'] ?? 0);

        if ($userRole === 23) {
            return $this->purchaseExecutiveDashboard();
        }

        $stats      = $this->getDashboardStats();
        $projects   = $this->getProjectsWithProgress();
        $budgetUtil = $this->getBudgetUtilization();
        $stockAlerts   = $this->getLowStockItems();
        $pendingAlerts = $this->getPendingApprovalsList();
        $soilAlerts    = $this->getRecentSoilTests();

        $stats['total_contractors'] = $this->db
            ->query("SELECT COUNT(*) AS cnt FROM contractors WHERE status='active'")->fetch_assoc()['cnt'] ?? 0;

        $stats['pending_bills'] = $this->db
            ->query("SELECT COUNT(*) AS cnt FROM contractor_bill_requests WHERE status IN ('submitted','verified')")->fetch_assoc()['cnt'] ?? 0;

        $stats['pending_funds'] = $this->db
            ->query("SELECT COUNT(*) AS cnt FROM operational_fund_requests WHERE status='pending'")->fetch_assoc()['cnt'] ?? 0;

        $stats['total_vendors'] = $this->db
            ->query("SELECT COUNT(*) AS cnt FROM vendors WHERE status='active'")->fetch_assoc()['cnt'] ?? 0;

        // Recent contractor bills (last 5)
        $recentBills = $this->db->query("
            SELECT cbr.*, oo.name AS operator_name, p.name AS project_name
            FROM contractor_bill_requests cbr
            LEFT JOIN contractors oo ON oo.id = cbr.operator_id
            LEFT JOIN projects p ON p.id = cbr.project_id
            ORDER BY cbr.created_at DESC
            LIMIT 5
        ")->fetch_all(MYSQLI_ASSOC);

        // Recent fund requests (last 5)
        $recentFunds = $this->db->query("
            SELECT ofr.*, p.name AS project_name, u.full_name AS requester_name
            FROM operational_fund_requests ofr
            LEFT JOIN projects p ON p.id = ofr.project_id
            LEFT JOIN users u ON u.id = ofr.requester_id
            ORDER BY ofr.created_at DESC
            LIMIT 5
        ")->fetch_all(MYSQLI_ASSOC);

        // Pending Initial Estimate Budgets (for PM/SE approval panel)
        $rPendingBudgets = $this->db->query("
            SELECT b.id, b.total_estimated_cost, b.created_at,
                   p.name AS project_name,
                   u.full_name AS creator_name,
                   COALESCE(des.name, '') AS creator_designation
            FROM initial_estimate_budgets b
            LEFT JOIN projects p      ON p.id  = b.project_id
            LEFT JOIN users u         ON u.id  = b.created_by
            LEFT JOIN employees emp   ON emp.user_id = u.id
            LEFT JOIN designations des ON des.id = emp.designation_id
            WHERE b.status = 'pending'
            ORDER BY b.created_at DESC
            LIMIT 10");
        $pendingInitialBudgets = $rPendingBudgets ? $rPendingBudgets->fetch_all(MYSQLI_ASSOC) : [];

        // Pending BOQs (Draft + Submitted) — for PM approval panel
        $rPendingBoqs = $this->db->query("
            SELECT m.id, m.version, m.status, m.created_at,
                   p.name AS project_name,
                   u.full_name AS creator_name,
                   COALESCE(des.name, '') AS creator_designation,
                   COUNT(bi.id) AS item_count,
                   COALESCE(SUM(bi.total_cost), 0) AS grand_total,
                   MIN(bi.required_date) AS earliest_required_date
            FROM boq_master m
            LEFT JOIN projects     p   ON p.id  = m.project_id
            LEFT JOIN users        u   ON u.id  = m.created_by
            LEFT JOIN employees    emp ON emp.user_id = u.id
            LEFT JOIN designations des ON des.id = emp.designation_id
            LEFT JOIN boq_items    bi  ON bi.boq_id = m.id
            WHERE m.status IN ('Draft','Submitted')
            GROUP BY m.id, m.version, m.status, m.created_at,
                     p.name, u.full_name, des.name
            ORDER BY m.created_at DESC
            LIMIT 10");
        $pendingBoqs = $rPendingBoqs ? $rPendingBoqs->fetch_all(MYSQLI_ASSOC) : [];

        // Pending Targets awaiting PM approval (approved_by_manager = 0)
        $rPendingTargetsPM = $this->db->query("
            SELECT tf.id, tf.target_title, tf.target_percentage, tf.gain_percentage,
                   tf.year, tf.month, tf.week_number, tf.created_at,
                   p.name  AS project_name,
                   u.full_name AS creator_name,
                   COALESCE(des.name,'') AS creator_designation
            FROM target_fixed tf
            LEFT JOIN projects     p   ON p.id  = tf.project_id
            LEFT JOIN users        u   ON u.id  = tf.user_id
            LEFT JOIN employees    emp ON emp.user_id = u.id
            LEFT JOIN designations des ON des.id = emp.designation_id
            WHERE tf.approved_by_manager = 0
            ORDER BY tf.created_at DESC
            LIMIT 10");
        $pendingTargetsPM = $rPendingTargetsPM ? $rPendingTargetsPM->fetch_all(MYSQLI_ASSOC) : [];

        // ── Pending POs awaiting PM approval ──────────────────────────────
        $rPendingPOsPM = $this->db->query("
            SELECT po.id, po.status, po.order_date,
                   po.approved_by_se,
                   p.name AS project_name,
                   u.full_name AS created_by_name,
                   (SELECT MIN(bi.required_date)
                    FROM purchase_order_items poi
                    JOIN boq_items bi ON bi.boq_id = pr.bom_id AND bi.material_id = poi.material_id
                    WHERE poi.purchase_order_id = po.id) AS earliest_required_date
            FROM purchase_orders po
            JOIN purchase_requests pr ON pr.id = po.request_id
            LEFT JOIN projects p ON p.id = pr.project_id
            LEFT JOIN users u   ON u.id = po.created_by
            WHERE po.approved_by_pm = 0
              AND po.status NOT IN ('Cancelled','Fully Received','Approved')
            ORDER BY po.id DESC LIMIT 10");
        $pendingPOsForPM = $rPendingPOsPM ? $rPendingPOsPM->fetch_all(MYSQLI_ASSOC) : [];

        // ── Pending POs awaiting SE approval ──────────────────────────────
        $rPendingPOsSE = $this->db->query("
            SELECT po.id, po.status, po.order_date,
                   po.approved_by_pm,
                   p.name AS project_name,
                   u.full_name AS created_by_name,
                   (SELECT MIN(bi.required_date)
                    FROM purchase_order_items poi
                    JOIN boq_items bi ON bi.boq_id = pr.bom_id AND bi.material_id = poi.material_id
                    WHERE poi.purchase_order_id = po.id) AS earliest_required_date
            FROM purchase_orders po
            JOIN purchase_requests pr ON pr.id = po.request_id
            LEFT JOIN projects p ON p.id = pr.project_id
            LEFT JOIN users u   ON u.id = po.created_by
            WHERE po.approved_by_se = 0
              AND po.status NOT IN ('Cancelled','Fully Received','Approved')
            ORDER BY po.id DESC LIMIT 10");
        $pendingPOsForSE = $rPendingPOsSE ? $rPendingPOsSE->fetch_all(MYSQLI_ASSOC) : [];

        // ── Pending GRNs awaiting QC ──────────────────────────────
        $rPendingGRNs = $this->db->query("
            SELECT mr.id, mr.receiving_date,
                   p.name AS project_name,
                   u.full_name AS received_by_name
            FROM material_receiving mr
            JOIN purchase_orders po ON po.id = mr.purchase_order_id
            JOIN purchase_requests pr ON pr.id = po.request_id
            LEFT JOIN projects p ON p.id = pr.project_id
            LEFT JOIN users u ON u.id = mr.received_by
            WHERE mr.overall_status = 'Pending QC'
            ORDER BY mr.id DESC LIMIT 10");
        $pendingGRNs = $rPendingGRNs ? $rPendingGRNs->fetch_all(MYSQLI_ASSOC) : [];

        // ── Pending GRNs awaiting PM ──────────────────────────────
        $rPendingGRNsPM = $this->db->query("
            SELECT mr.id, mr.receiving_date,
                   p.name AS project_name,
                   u.full_name AS received_by_name
            FROM material_receiving mr
            JOIN purchase_orders po ON po.id = mr.purchase_order_id
            JOIN purchase_requests pr ON pr.id = po.request_id
            LEFT JOIN projects p ON p.id = pr.project_id
            LEFT JOIN users u ON u.id = mr.received_by
            WHERE mr.overall_status = 'Pending PM'
            ORDER BY mr.id DESC LIMIT 10");
        $pendingGRNsPM = $rPendingGRNsPM ? $rPendingGRNsPM->fetch_all(MYSQLI_ASSOC) : [];

        // ── Pending Weekly Reports awaiting PM ────────────────────────────
        $rPendingWeeklyPM = $this->db->query("
            SELECT pwp.id, 
                   COALESCE(pwp.submitted_at, pwp.week_end_date) AS report_date, 
                   pwp.week_number AS week_no,
                   p.name AS project_name,
                   u.full_name AS created_by_name,
                   pwp.target_id
            FROM phase_weekly_progress pwp
            LEFT JOIN projects p ON p.id = pwp.project_id
            LEFT JOIN users u ON u.id = pwp.submitted_by
            WHERE pwp.entry_status = 'Submitted' AND pwp.qc_approved_id IS NOT NULL AND pwp.pm_approved_id IS NULL
            ORDER BY pwp.submitted_at DESC, pwp.id DESC LIMIT 10");
        $pendingWeeklyReportsPM = $rPendingWeeklyPM ? $rPendingWeeklyPM->fetch_all(MYSQLI_ASSOC) : [];

        // ── Pending Security Issues ──────────────────────────────
        $rPendingIncidents = $this->db->query("
            SELECT i.id, i.issue_title, i.priority, i.reported_at, p.name AS project_name
            FROM con_security_incidents i
            LEFT JOIN projects p ON p.id = i.project_id
            WHERE i.status != 'Resolved'
            ORDER BY i.id DESC LIMIT 5");
        $pendingIncidents = $rPendingIncidents ? $rPendingIncidents->fetch_all(MYSQLI_ASSOC) : [];

        $rPendingLogs = $this->db->query("
            SELECT l.id, l.log_date, l.remarks, p.name AS project_name
            FROM con_security_daily_logs l
            LEFT JOIN projects p ON p.id = l.project_id
            WHERE l.condition_status = 'Issue Found' AND l.status != 'Resolved'
            ORDER BY l.id DESC LIMIT 5");
        $pendingLogs = $rPendingLogs ? $rPendingLogs->fetch_all(MYSQLI_ASSOC) : [];

        // ── Week-based Target Notifications (PM role=15, SE role=12) ──────
        // Show targets where current month+week matches the target's month+week
        $curYear  = (int)date('Y');
        $curMonth = (int)date('n');
        $curWeek  = (int)ceil(date('j') / 7);
        $curWeek  = min($curWeek, 4);

        $rWeekTargets = $this->db->prepare("
            SELECT tf.id, tf.target_title, tf.target_percentage, tf.gain_percentage,
                   tf.year, tf.month, tf.week_number,
                   tf.is_lab_test, tf.lab_test_date, tf.target_details,
                   tf.approved_by_qc, tf.approved_by_manager,
                   p.name AS project_name,
                   u.full_name AS creator_name
            FROM target_fixed tf
            LEFT JOIN projects p ON p.id = tf.project_id
            LEFT JOIN users    u ON u.id = tf.user_id
            WHERE tf.year  = ?
              AND tf.month = ?
              AND (tf.week_number = ? OR tf.week_number IS NULL)
            ORDER BY tf.id DESC
            LIMIT 20");
        $rWeekTargets->bind_param('iii', $curYear, $curMonth, $curWeek);
        $rWeekTargets->execute();
        $weekTargetNotifications = $rWeekTargets->get_result()->fetch_all(MYSQLI_ASSOC);

        // Lab test date notifications — lab test due within current week
        $daysInMonth = (int)date('t');
        $weekStartDay = ($curWeek - 1) * 7 + 1;
        $weekEndDay   = min($curWeek * 7, $daysInMonth);
        $weekStart    = date('Y-m-') . str_pad($weekStartDay, 2, '0', STR_PAD_LEFT);
        $weekEnd      = date('Y-m-') . str_pad($weekEndDay,   2, '0', STR_PAD_LEFT);

        $rLabDue = $this->db->prepare("
            SELECT tf.id, tf.target_title, tf.lab_test_date,
                   tf.year, tf.month, tf.week_number,
                   p.name AS project_name
            FROM target_fixed tf
            LEFT JOIN projects p ON p.id = tf.project_id
            WHERE tf.is_lab_test = 1
              AND tf.lab_test_date BETWEEN ? AND ?
            ORDER BY tf.lab_test_date ASC
            LIMIT 15");
        $rLabDue->bind_param('ss', $weekStart, $weekEnd);
        $rLabDue->execute();
        $labDueNotifications = $rLabDue->get_result()->fetch_all(MYSQLI_ASSOC);

        // ── Recent Material Issues (Stock OUT) ────────────────────────────
        $rRecentIssues = $this->db->query("
            SELECT mi.id, mi.quantity, mi.issue_date, mi.purpose,
                   p.name AS project_name,
                   m.name AS material_name, m.unit,
                   w.name AS warehouse_name,
                   u.full_name AS issued_by_name
            FROM con_material_issues mi
            LEFT JOIN projects p ON p.id = mi.project_id
            LEFT JOIN con_materials m ON m.id = mi.material_id
            LEFT JOIN con_warehouses w ON w.id = mi.warehouse_id
            LEFT JOIN users u ON u.id = mi.issued_by
            ORDER BY mi.issue_date DESC, mi.id DESC
            LIMIT 10
        ");
        $recentMaterialIssues = $rRecentIssues ? $rRecentIssues->fetch_all(MYSQLI_ASSOC) : [];

        // ── Pending Material Returns ────────────────────────────
        $rPendingReturns = $this->db->query("
            SELECT mr.*, p.name AS project_name, v.name AS vendor_name, u.full_name AS created_by_name
            FROM material_returns mr
            LEFT JOIN projects p ON p.id = mr.project_id
            LEFT JOIN vendors v ON v.id = mr.vendor_id
            LEFT JOIN users u ON u.id = mr.created_by
            WHERE mr.status = 'pending'
            ORDER BY mr.id DESC
            LIMIT 10
        ");
        $pendingReturns = $rPendingReturns ? $rPendingReturns->fetch_all(MYSQLI_ASSOC) : [];

        $pendingWorkOrders = [];
        if (in_array($userRole, [1, 3, 22])) {
            if ($userRole == 22) {
                $rWO = $this->db->query("
                    SELECT wo.id, wo.title, wo.total_amount, wo.created_at,
                           p.name AS project_name,
                           c.name AS contractor_name,
                           u.full_name AS creator_name
                    FROM con_work_orders wo
                    JOIN con_work_order_approvals wa_pm ON wa_pm.work_order_id = wo.id AND wa_pm.role_id = 22
                    JOIN con_work_order_approvals wa_mo ON wa_mo.work_order_id = wo.id AND wa_mo.role_id = 20
                    LEFT JOIN projects p ON p.id = wo.project_id
                    LEFT JOIN contractors c ON c.id = wo.contractor_id
                    LEFT JOIN users u ON u.id = wo.created_by
                    WHERE wo.status = 'draft'
                      AND wa_pm.status = 'pending'
                      AND wa_mo.status != 'pending'
                    ORDER BY wo.created_at DESC
                    LIMIT 10
                ");
            } else {
                $rWO = $this->db->query("
                    SELECT wo.id, wo.title, wo.total_amount, wo.created_at,
                           p.name AS project_name,
                           c.name AS contractor_name,
                           u.full_name AS creator_name
                    FROM con_work_orders wo
                    JOIN con_work_order_approvals wa_mgmt ON wa_mgmt.work_order_id = wo.id AND wa_mgmt.role_id = 3
                    JOIN con_work_order_approvals wa_pm ON wa_pm.work_order_id = wo.id AND wa_pm.role_id = 22
                    LEFT JOIN projects p ON p.id = wo.project_id
                    LEFT JOIN contractors c ON c.id = wo.contractor_id
                    LEFT JOIN users u ON u.id = wo.created_by
                    WHERE wo.status = 'draft'
                      AND wa_mgmt.status = 'pending'
                      AND wa_pm.status != 'pending'
                    ORDER BY wo.created_at DESC
                    LIMIT 10
                ");
            }
            $pendingWorkOrders = $rWO ? $rWO->fetch_all(MYSQLI_ASSOC) : [];
        }

        // ── Pending Budget Extensions ──────────────────────────────
        $pendingBudgetExtends = [];
        if (in_array($userRole, [1, 3, 15, 22])) {
            $rBE = $this->db->query("
                SELECT r.id, r.amount, r.title, r.reason, r.created_at,
                       p.name AS project_name,
                       u.full_name AS requester_name
                FROM project_budget_requests r
                LEFT JOIN projects p ON p.id = r.project_id
                LEFT JOIN users u ON u.id = r.requested_by
                WHERE r.status = 'Pending'
                ORDER BY r.created_at DESC
                LIMIT 10
            ");
            $pendingBudgetExtends = $rBE ? $rBE->fetch_all(MYSQLI_ASSOC) : [];
        }

        // ── PRs awaiting approval — for roles 3, 15, 22 ─────────────────────
        $prsForApproval = [];
        if (in_array($userRole, [3, 15, 22])) {
            $rPRApproval = $this->db->query("
                SELECT pr.id, pr.status, pr.created_at, pr.notes,
                       p.name  AS project_name,
                       u.full_name AS created_by_name,
                       r.name  AS created_by_role,
                       COUNT(pri.id) AS item_count,
                       (SELECT MIN(bi.required_date)
                        FROM purchase_request_items pri2
                        JOIN boq_items bi ON bi.boq_id = pr.bom_id AND bi.material_id = pri2.material_id
                        WHERE pri2.request_id = pr.id) AS earliest_required_date
                FROM purchase_requests pr
                LEFT JOIN projects p    ON p.id  = pr.project_id
                LEFT JOIN users u       ON u.id  = pr.created_by
                LEFT JOIN roles r       ON r.id  = u.role_id
                LEFT JOIN purchase_request_items pri ON pri.request_id = pr.id
                WHERE pr.status IN ('Draft', 'Submitted')
                GROUP BY pr.id, pr.status, pr.created_at, pr.notes,
                         p.name, u.full_name, r.name
                ORDER BY
                    CASE pr.status WHEN 'Submitted' THEN 0 WHEN 'Draft' THEN 1 ELSE 2 END,
                    pr.id DESC
                LIMIT 20");
            $prsForApproval = $rPRApproval ? $rPRApproval->fetch_all(MYSQLI_ASSOC) : [];
        }

        // Fetch pending Land requisitions and Petty Cash requests for Management / Admin
        $pendingLandRequisitions = [];
        $pendingPettyCash = [];
        if (in_array($userRole, [1, 3])) {
            $rLandReq = $this->db->query("
                SELECT lfr.*, la.land_owner_name, p.name as project_name, u.full_name as requester_name
                FROM land_fund_requisitions lfr
                LEFT JOIN land_acquisitions la ON lfr.land_acquisition_id = la.id
                LEFT JOIN projects p ON la.project_id = p.id
                LEFT JOIN users u ON lfr.requested_by = u.id
                WHERE lfr.mgmt_status = 'Pending'
                ORDER BY lfr.id DESC LIMIT 10
            ");
            $pendingLandRequisitions = $rLandReq ? $rLandReq->fetch_all(MYSQLI_ASSOC) : [];

            $rFundReq = $this->db->query("
                SELECT fr.*, p.name AS project_name, d.name AS department_name, u.full_name AS requester_name
                FROM fund_requisitions fr
                LEFT JOIN projects p ON fr.project_id = p.id
                LEFT JOIN departments d ON fr.department_id = d.id
                LEFT JOIN users u ON fr.requested_by = u.id
                WHERE fr.status = 'pending'
                ORDER BY fr.id DESC LIMIT 10
            ");
            $pendingPettyCash = $rFundReq ? $rFundReq->fetch_all(MYSQLI_ASSOC) : [];
        }

        $this->view('phase2/dashboard/cons-dashboard', [
            'current_route'           => 'con-dashboard',
            'pendingLandRequisitions' => $pendingLandRequisitions,
            'pendingPettyCash'        => $pendingPettyCash,
            'stats'                   => $stats,
            'projects'                => $projects,
            'budgetUtil'              => $budgetUtil,
            'stockAlerts'             => $stockAlerts,
            'pendingAlerts'           => $pendingAlerts,
            'soilAlerts'              => $soilAlerts,
            'pendingInitialBudgets'   => $pendingInitialBudgets,
            'pendingBoqs'             => $pendingBoqs,
            'pendingTargetsPM'        => $pendingTargetsPM,
            'pendingPOsForPM'         => $pendingPOsForPM,
            'pendingPOsForSE'         => $pendingPOsForSE,
            'pendingGRNs'             => $pendingGRNs,
            'pendingGRNsPM'           => $pendingGRNsPM,
            'pendingWeeklyReportsPM'  => $pendingWeeklyReportsPM,
            'pendingIncidents'        => $pendingIncidents,
            'pendingLogs'             => $pendingLogs,
            'pendingReturns'          => $pendingReturns,
            'pendingWorkOrders'       => $pendingWorkOrders,
            'pendingBudgetExtends'    => $pendingBudgetExtends,
            'weekTargetNotifications' => $weekTargetNotifications,
            'labDueNotifications'     => $labDueNotifications,
            'currentWeek'             => $curWeek,
            'currentMonth'            => $curMonth,
            'currentYear'             => $curYear,
            'recentBills'             => $recentBills,
            'recentFunds'             => $recentFunds,
            'recentMaterialIssues'    => $recentMaterialIssues,
            'prsForApproval'          => $prsForApproval,
        ]);
    }

    // ── KPI aggregates ──────────────────────────────────────────────────────

    
    private function getProjectRoleFilter($alias = 'p')
    {
        $userId = $_SESSION['user']['id'] ?? 0;
        $roleId = $_SESSION['user']['role_id'] ?? 0;
        if (in_array($roleId, [1, 3, 22])) return '1=1';
        return "({$alias}.pm_id = {$userId} OR {$alias}.site_engineer_id = {$userId} OR {$alias}.landman_id = {$userId} OR {$alias}.struct_engineer_id = {$userId} OR {$alias}.operation_head_id = {$userId} OR {$alias}.draftsman_id = {$userId} OR {$alias}.architect_id = {$userId} OR {$alias}.estimator_id = {$userId} OR {$alias}.qc_id = {$userId} OR {$alias}.operator_id = {$userId} OR {$alias}.created_by = {$userId})";
    }

    private function getDashboardStats(): array
    {
        $s = [];

        // 1. Total Projects
        $r = $this->db->query("SELECT COUNT(*) AS c FROM projects p WHERE p.deleted_at IS NULL AND " . $this->getProjectRoleFilter('p') . " AND " . $this->getProjectRoleFilter('p') . "");
        $s['total_projects'] = $r ? (int)$r->fetch_assoc()['c'] : 0;

        // 2-4. Budget totals (approved estimates only)
        $r2 = $this->db->query("
            SELECT COALESCE(SUM(total_estimated_cost),0) AS total_budget,
                   COALESCE(SUM(material_cost_total),0)  AS total_material,
                   COALESCE(SUM(labor_cost_total),0)     AS total_labour
            FROM initial_estimate_budgets
            WHERE status = 'approved'");
        if ($r2 && $row = $r2->fetch_assoc()) {
            $s['total_budget']   = (float)$row['total_budget'];
            $s['total_material'] = (float)$row['total_material'];
            $s['total_labour']   = (float)$row['total_labour'];
        } else {
            $s['total_budget'] = $s['total_material'] = $s['total_labour'] = 0.0;
        }

        // 5. Stock Alerts
        $r3 = $this->db->query("
            SELECT COUNT(*) AS c
            FROM con_stocks s
            JOIN con_materials m ON m.id = s.material_id
            WHERE s.current_quantity < m.min_stock_level"
        );
        $s['stock_alerts'] = $r3 ? (int)$r3->fetch_assoc()['c'] : 0;

        // 6. Pending Approvals (all approval queues combined)
        $r4 = $this->db->query("
            SELECT COUNT(*) AS c FROM (
                SELECT id FROM contractor_bill_requests    WHERE status = 'submitted'
                UNION ALL
                SELECT id FROM con_work_orders             WHERE status = 'draft'
                UNION ALL
                SELECT id FROM operational_fund_requests   WHERE status = 'pending'
                UNION ALL
                SELECT id FROM vendor_payment_requests     WHERE status = 'Submitted'
                UNION ALL
                SELECT id FROM fund_requisitions           WHERE status = 'pending'
                UNION ALL
                SELECT id FROM land_fund_requisitions      WHERE mgmt_status = 'Pending'
            ) t");
        $s['pending_approvals'] = $r4 ? (int)$r4->fetch_assoc()['c'] : 0;

        // 7. Budget Extension Requests
        $r5 = $this->db->query("SELECT COUNT(*) AS c FROM budget_extend_requests WHERE status = 'Pending'");
        $s['budget_requests'] = $r5 ? (int)$r5->fetch_assoc()['c'] : 0;

        // 8. Lab / Soil Test Alerts
        $r6 = $this->db->query("SELECT COUNT(*) AS c FROM soil_tests");
        $s['lab_alerts'] = $r6 ? (int)$r6->fetch_assoc()['c'] : 0;

        return $s;
    }

    private function getProjectsWithProgress(): array
    {
        $sql = "
            SELECT  p.id,
                    p.name,
                    p.project_status,
                    p.total_share        AS total_floors,
                    p.plot_size,
                    p.building_type,
                    COALESCE(l.name,'')  AS location_name,
                    COALESCE(prog.progress_pct, 0)    AS progress_pct,
                    COALESCE(bud.total_budget, 0)      AS total_budget,
                    COALESCE(spent.total_spent, 0)     AS total_spent
            FROM projects p
            LEFT JOIN locations l ON l.id = p.location_id
            LEFT JOIN (
                SELECT wp.project_id, SUM((COALESCE(tf.gain_percentage,0)/100)*(COALESCE(tf.target_percentage,0)/100)*(COALESCE(wp.weight_percentage,0)/100)*COALESCE(ib.weight_percentage,100)) AS progress_pct
                FROM project_working_plans wp
                LEFT JOIN target_fixed tf ON tf.work_plan_id = wp.id
                LEFT JOIN initial_estimate_budgets ib ON ib.id = wp.initial_budget_id
                GROUP BY wp.project_id
            ) prog ON prog.project_id = p.id
            LEFT JOIN (
                SELECT project_id, SUM(total_estimated_cost) AS total_budget
                FROM initial_estimate_budgets WHERE status = 'approved' GROUP BY project_id
            ) bud ON bud.project_id = p.id
            LEFT JOIN (
                SELECT project_id, SUM(amount_paid) AS total_spent
                FROM con_financial_transactions GROUP BY project_id
            ) spent ON spent.project_id = p.id
            WHERE p.deleted_at IS NULL AND " . $this->getProjectRoleFilter('p') . "
            ORDER BY p.id DESC";

        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    private function getBudgetUtilization(): array
    {
        $r1 = $this->db->query("SELECT COALESCE(SUM(total_estimated_cost),0) AS total FROM initial_estimate_budgets WHERE status='approved'");
        $total = $r1 ? (float)$r1->fetch_assoc()['total'] : 0.0;

        $r2 = $this->db->query("SELECT COALESCE(SUM(amount_paid),0) AS spent FROM con_financial_transactions");
        $spent = $r2 ? (float)$r2->fetch_assoc()['spent'] : 0.0;

        $pct = $total > 0 ? round(($spent / $total) * 100, 1) : 0.0;
        return ['total' => $total, 'spent' => $spent, 'pct' => $pct, 'remaining' => max(0, $total - $spent)];
    }

    private function getLowStockItems(): array
    {
        $sql = "
            SELECT s.current_quantity, m.name AS material_name, m.unit, m.min_stock_level,
                   w.name AS warehouse_name
            FROM con_stocks s
            JOIN con_materials  m ON m.id = s.material_id
            JOIN con_warehouses w ON w.id = s.warehouse_id
            WHERE s.current_quantity < m.min_stock_level
            ORDER BY (m.min_stock_level - s.current_quantity) DESC
            LIMIT 5";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    private function getPendingApprovalsList(): array
    {
        $sql = "
            SELECT 'Contractor Bill'   AS type,
                   CONCAT('Bill #', id) AS description,
                   bill_amount          AS amount,
                   project_id,
                   created_at,
                   CONCAT('/con-contractor-bill/', id) AS url
            FROM contractor_bill_requests WHERE status = 'submitted'
            UNION ALL
            SELECT 'Work Order',
                   CONCAT('Work Order: ', title),
                   total_amount,
                   project_id,
                   created_at,
                   CONCAT('/con-work-order/', id) AS url
            FROM con_work_orders WHERE status = 'draft'
            UNION ALL
            SELECT 'Fund Request',
                   CONCAT('Fund: ', purpose),
                   COALESCE(approved_amount, amount),
                   project_id, created_at,
                   CONCAT('/con-fund-request/', id) AS url
            FROM operational_fund_requests WHERE status = 'pending'
            UNION ALL
            SELECT 'Vendor Payment',
                   CONCAT(payment_type, ' Payment'),
                   COALESCE(net_payable, 0),
                   project_id, created_at,
                   CONCAT('/con-vendor-payment/', id) AS url
            FROM vendor_payment_requests WHERE status = 'Submitted'
            ORDER BY created_at DESC LIMIT 5";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    private function getRecentSoilTests(): array
    {
        $sql = "
            SELECT st.*, p.name AS project_name
            FROM soil_tests st
            LEFT JOIN projects p ON p.id = st.project_id
            ORDER BY st.id DESC LIMIT 5";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // =========================================================================
    // PROJECTS LIST PAGE  (card-based, con-projects)
    // =========================================================================

    public function projectsList()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Reset active project context when returning to projects list
        unset($_SESSION['active_project_id']);
        unset($_SESSION['active_project_name']);
        unset($_SESSION['active_project_workflow_version']);

        $sql = "
            SELECT  p.id,
                    p.name,
                    p.project_status,
                    p.building_type,
                    p.total_share        AS total_floors,
                    p.plot_size,
                    p.plot_no,
                    p.block_no,
                    p.workflow_version,
                    COALESCE(l.name,'')  AS location_name,
                    COALESCE(prog.progress_pct, 0)    AS progress_pct,
                    COALESCE(bud.total_budget, 0)      AS total_budget,
                    COALESCE(spent.total_spent, 0)     AS total_spent,
                    bud.start_date,
                    bud.end_date
            FROM projects p
            LEFT JOIN locations l ON l.id = p.location_id
            LEFT JOIN (
                SELECT wp.project_id, SUM((COALESCE(tf.gain_percentage,0)/100)*(COALESCE(tf.target_percentage,0)/100)*(COALESCE(wp.weight_percentage,0)/100)*COALESCE(ib.weight_percentage,100)) AS progress_pct
                FROM project_working_plans wp
                LEFT JOIN target_fixed tf ON tf.work_plan_id = wp.id
                LEFT JOIN initial_estimate_budgets ib ON ib.id = wp.initial_budget_id
                GROUP BY wp.project_id
            ) prog ON prog.project_id = p.id
            LEFT JOIN (
                SELECT project_id,
                       SUM(total_estimated_cost) AS total_budget,
                       MAX(start_date)           AS start_date,
                       MAX(end_date)             AS end_date
                FROM initial_estimate_budgets WHERE status = 'approved' GROUP BY project_id
            ) bud ON bud.project_id = p.id
            LEFT JOIN (
                SELECT project_id, SUM(amount_paid) AS total_spent
                FROM con_financial_transactions GROUP BY project_id
            ) spent ON spent.project_id = p.id
            WHERE p.deleted_at IS NULL AND " . $this->getProjectRoleFilter('p') . "
            ORDER BY p.id DESC";

        $result   = $this->db->query($sql);
        $projects = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        // Summary counts
        $statusCounts = [];
        foreach ($projects as $p) {
            $st = $p['project_status'] ?? 'Unknown';
            $statusCounts[$st] = ($statusCounts[$st] ?? 0) + 1;
        }

        return $this->view('phase2/dashboard/con-projects', [
            'current_route' => 'con-projects',
            'projects'      => $projects,
            'statusCounts'  => $statusCounts,
        ]);
    }

    // =========================================================================
    // PROJECT DETAIL PAGE  (Tabs 1-4 PHP, Tabs 5-7 AJAX)
    // =========================================================================

    public function projectDetail($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = (int)$id;

        // ── Tab 1: Overview ──────────────────────────────────────────────────
        $stmt = $this->db->prepare("
            SELECT p.*, l.name AS location_name, u.full_name AS creator_name
            FROM   projects p
            LEFT JOIN locations l ON l.id = p.location_id
            LEFT JOIN users     u ON u.id = p.created_by
            WHERE  p.id = ? AND p.deleted_at IS NULL");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $project = $stmt->get_result()->fetch_assoc();

        if (!$project) { die("Project not found."); }

        // Set active project context
        $_SESSION['active_project_id'] = $project['id'];
        $_SESSION['active_project_name'] = $project['name'];
        $_SESSION['active_project_workflow_version'] = $project['workflow_version'];

        // Budget header (start / end date)
        $stmt2 = $this->db->prepare("
            SELECT * FROM initial_estimate_budgets
            WHERE project_id = ? AND status = 'approved'
            ORDER BY id DESC LIMIT 1");
        $stmt2->bind_param('i', $id);
        $stmt2->execute();
        $budget = $stmt2->get_result()->fetch_assoc();

        if (!$budget) {
            $stmt2b = $this->db->prepare("SELECT * FROM initial_estimate_budgets WHERE project_id = ? ORDER BY id DESC LIMIT 1");
            $stmt2b->bind_param('i', $id);
            $stmt2b->execute();
            $budget = $stmt2b->get_result()->fetch_assoc();
        }

        // Progress % = Hierarchical Progress from target_fixed
        $stmt3 = $this->db->prepare("
            SELECT SUM((COALESCE(tf.gain_percentage,0)/100)*(COALESCE(tf.target_percentage,0)/100)*(COALESCE(wp.weight_percentage,0)/100)*COALESCE(ib.weight_percentage,100)) AS prog
            FROM project_working_plans wp
            LEFT JOIN target_fixed tf ON tf.work_plan_id = wp.id
            LEFT JOIN initial_estimate_budgets ib ON ib.id = wp.initial_budget_id
            WHERE wp.project_id = ?
        ");
        $stmt3->bind_param('i', $id);
        $stmt3->execute();
        $progress = round((float)$stmt3->get_result()->fetch_assoc()['prog'], 2);

        // ── Tab 2: Documents ────────────────────────────────────────────────
        $stmt4 = $this->db->prepare("SELECT * FROM land_visibility_study WHERE project_id = ? LIMIT 1");
        $stmt4->bind_param('i', $id);
        $stmt4->execute();
        $landStudy = $stmt4->get_result()->fetch_assoc();

        $stmt5 = $this->db->prepare("
            SELECT st.*, u.full_name AS engineer_name
            FROM soil_tests st
            LEFT JOIN users u ON u.id = st.site_engineer_id
            WHERE st.project_id = ? ORDER BY st.id DESC");
        $stmt5->bind_param('i', $id);
        $stmt5->execute();
        $soilTests = $stmt5->get_result()->fetch_all(MYSQLI_ASSOC);

        // ── Tab 3: Design Drawings ──────────────────────────────────────────
        $stmt6 = $this->db->prepare("
            SELECT d.*, dt.name AS design_type_name
            FROM   project_drawings d
            LEFT JOIN design_types dt ON dt.id = d.design_type_id
            WHERE  d.project_id = ?
            ORDER  BY dt.name ASC, d.id ASC");
        $stmt6->bind_param('i', $id);
        $stmt6->execute();
        $rawDrawings = $stmt6->get_result()->fetch_all(MYSQLI_ASSOC);

        $drawings = [];
        foreach ($rawDrawings as $d) {
            $key = $d['design_type_name'] ?? 'Others';
            $drawings[$key][] = $d;
        }

        // ── Tab 4: Initial Budget Breakdown ─────────────────────────────────
        $budgetBreakdown = [];
        if ($budget) {
            $estimateModel   = new \Con_InitialEstimate($this->db);
            $budgetBreakdown = $estimateModel->getFullBreakdownByDesignType($budget['id']);
        }

        return $this->view('phase2/dashboard/project-detail', [
            'current_route'   => 'con-dashboard',
            'project'         => $project,
            'budget'          => $budget,
            'progress'        => $progress,
            'landStudy'       => $landStudy,
            'soilTests'       => $soilTests,
            'drawings'        => $drawings,
            'budgetBreakdown' => $budgetBreakdown,
        ]);
    }

    // =========================================================================
    // AJAX — Tab 5: Work Plan
    // =========================================================================

    public function ajaxWorkPlan($id)
    {
        header('Content-Type: application/json');
        $id = (int)$id;

        $stmt = $this->db->prepare("
            SELECT
                pmti.*,
                pmt.target_month,
                pmt.target_month_number,
                pmt.target_year,
                pmt.planning_status,
                pmt.responsible_team AS group_responsible_team,
                pp.phase_name,
                wt.work_type_name,
                dt.name AS design_type_name
            FROM phase_monthly_target_items pmti
            INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
            INNER JOIN project_phases pp ON pp.id = pmt.phase_id
            LEFT JOIN work_types wt ON wt.id = pmti.work_type_id
            LEFT JOIN design_types dt ON dt.id = wt.design_type_id
            WHERE pmt.project_id = ?
            ORDER BY pmt.target_year ASC, pmt.target_month_number ASC, pp.phase_order ASC, pmti.sort_order ASC, pmti.id ASC
        ");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $grouped = [];
        $monthNames = ['', 'January','February','March','April','May','June',
                           'July','August','September','October','November','December'];

        foreach ($items as $item) {
            $targetId = $item['target_id'];
            if (!isset($grouped[$targetId])) {
                $grouped[$targetId] = [
                    'label' => $monthNames[(int)$item['target_month_number']] . ' ' . $item['target_year'],
                    'phase_name' => $item['phase_name'],
                    'planning_status' => $item['planning_status'],
                    'responsible_team' => $item['group_responsible_team'] ?: ($item['responsible_team'] ?: '—'),
                    'items' => []
                ];
            }
            $grouped[$targetId]['items'][] = $item;
        }

        echo json_encode(['success' => true, 'data' => array_values($grouped)]);
        exit;
    }

    // =========================================================================
    // AJAX — Tab 6: Project Targets (Deprecated, unified into Work Plan)
    // =========================================================================

    public function ajaxTargets($id)
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    // =========================================================================
    // AJAX — Tab 7: Weekly Report (month→week→3 sub-tabs)
    // =========================================================================

    public function ajaxWeeklyData($id)
    {
        header('Content-Type: application/json');
        $id = (int)$id;

        $monthNames = ['','January','February','March','April','May','June',
                          'July','August','September','October','November','December'];

        $activeMonths = [];

        // 1. Get months from phase_monthly_targets
        $stmtTargets = $this->db->prepare("SELECT DISTINCT target_month FROM phase_monthly_targets WHERE project_id = ?");
        $stmtTargets->bind_param('i', $id);
        $stmtTargets->execute();
        $targetRows = $stmtTargets->get_result()->fetch_all(MYSQLI_ASSOC);
        foreach ($targetRows as $row) {
            if (!$row['target_month']) continue;
            $mk = substr($row['target_month'], 0, 7); // "YYYY-MM"
            $activeMonths[$mk] = true;
        }

        // 2. Get months from financials
        $stmtFin = $this->db->prepare("SELECT DISTINCT YEAR(transaction_date) as yr, MONTH(transaction_date) as mo FROM con_financial_transactions WHERE project_id = ?");
        $stmtFin->bind_param('i', $id);
        $stmtFin->execute();
        $finRows = $stmtFin->get_result()->fetch_all(MYSQLI_ASSOC);
        foreach ($finRows as $row) {
            if (!$row['yr'] || !$row['mo']) continue;
            $mk = $row['yr'] . '-' . str_pad($row['mo'], 2, '0', STR_PAD_LEFT);
            $activeMonths[$mk] = true;
        }

        krsort($activeMonths); // sort descending

        $result = [];
        foreach (array_keys($activeMonths) as $mk) {
            [$yr, $mo] = explode('-', $mk);
            $yr = (int)$yr;
            $mo = (int)$mo;

            $monthEntry = [
                'month_key'   => $mk,
                'month_label' => $monthNames[$mo] . ' ' . $yr,
                'year'        => $yr,
                'month'       => $mo,
                'weeks'       => [],
            ];

            // Loop weeks 1 to 4
            for ($wNum = 1; $wNum <= 4; $wNum++) {
                [$dateFrom, $dateTo] = $this->getWeekStartEndDates($yr, $mo, $wNum);

                // Fetch targets (weekly progress items) for this week
                $stmtWeekTargets = $this->db->prepare("
                    SELECT 
                        pwpi.*,
                        pmti.work_item_name,
                        pmti.unit,
                        pmti.priority,
                        pp.phase_name,
                        wt.work_type_name,
                        dt.name AS design_type_name
                    FROM phase_weekly_progress_items pwpi
                    INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
                    INNER JOIN phase_monthly_targets pmt ON pmt.id = pwp.target_id
                    INNER JOIN project_phases pp ON pp.id = pmt.phase_id
                    INNER JOIN phase_monthly_target_items pmti ON pmti.id = pwpi.target_item_id
                    LEFT JOIN work_types wt ON wt.id = pmti.work_type_id
                    LEFT JOIN design_types dt ON dt.id = wt.design_type_id
                    WHERE pwp.project_id = ? 
                      AND pmt.target_year = ? 
                      AND pmt.target_month_number = ? 
                      AND pwp.week_number = ?
                    ORDER BY pmti.sort_order ASC, pwpi.id ASC
                ");
                $stmtWeekTargets->bind_param('iiii', $id, $yr, $mo, $wNum);
                $stmtWeekTargets->execute();
                $targets = $stmtWeekTargets->get_result()->fetch_all(MYSQLI_ASSOC);

                // Fetch daily progress logs for this week
                $stmtDprs = $this->db->prepare("
                    SELECT 
                        dpl.*,
                        u.full_name AS creator_name
                    FROM daily_progress_logs dpl
                    INNER JOIN phase_weekly_progress pwp ON pwp.id = dpl.weekly_progress_id
                    INNER JOIN phase_monthly_targets pmt ON pmt.id = pwp.target_id
                    LEFT JOIN users u ON u.id = dpl.created_by
                    WHERE pwp.project_id = ? 
                      AND pmt.target_year = ? 
                      AND pmt.target_month_number = ? 
                      AND pwp.week_number = ?
                    ORDER BY dpl.log_date ASC
                ");
                $stmtDprs->bind_param('iiii', $id, $yr, $mo, $wNum);
                $stmtDprs->execute();
                $dprs = $stmtDprs->get_result()->fetch_all(MYSQLI_ASSOC);

                // For each daily progress log, fetch nested work items
                if (!empty($dprs)) {
                    $logIds = array_column($dprs, 'id');
                    $logIdsPlaceholders = implode(',', array_fill(0, count($logIds), '?'));
                    
                    $stmtDprItems = $this->db->prepare("
                        SELECT 
                            dwil.*,
                            pmti.work_item_name,
                            wt.work_type_name
                        FROM daily_work_item_logs dwil
                        INNER JOIN phase_monthly_target_items pmti ON pmti.id = dwil.target_item_id
                        LEFT JOIN work_types wt ON wt.id = dwil.work_type_id
                        WHERE dwil.daily_log_id IN ($logIdsPlaceholders)
                        ORDER BY dwil.id ASC
                    ");
                    
                    $types = str_repeat('i', count($logIds));
                    $stmtDprItems->bind_param($types, ...$logIds);
                    $stmtDprItems->execute();
                    $allItems = $stmtDprItems->get_result()->fetch_all(MYSQLI_ASSOC);
                    
                    $itemsByLogId = [];
                    foreach ($allItems as $item) {
                        $itemsByLogId[$item['daily_log_id']][] = $item;
                    }
                    
                    foreach ($dprs as &$dpr) {
                        $dpr['items'] = $itemsByLogId[$dpr['id']] ?? [];
                    }
                    unset($dpr);
                }

                // Fetch financial transactions for this week
                $stmtTrans = $this->db->prepare("
                    SELECT cft.*,
                           cft.amount_paid AS amount,
                           cft.reference_number AS ref_no,
                           cft.transaction_date_str AS payment_date,
                           u.full_name AS processor_name,
                           cbr.bill_type AS cbr_bill_type,
                           cbr.target_id AS cbr_target_id,
                           cbr.work_description AS cbr_work_desc,
                           cbr.bill_amount AS cbr_bill_amount,
                           cbr.status AS cbr_status,
                           cbr.due_amount AS cbr_due_amount
                    FROM   con_financial_transactions cft
                    LEFT JOIN users u ON u.id = cft.processed_by
                    LEFT JOIN contractor_bill_requests cbr
                           ON cft.ref_type = 'contractor_bill' AND cbr.id = cft.ref_id
                    WHERE  cft.project_id = ?
                      AND (
                            (DATE(cft.transaction_date) BETWEEN ? AND ?)
                            OR cbr.target_id IN (
                                SELECT pmt.id 
                                FROM phase_monthly_targets pmt
                                WHERE pmt.project_id = ? AND pmt.target_year = ? AND pmt.target_month_number = ?
                            )
                          )
                    ORDER  BY cft.transaction_date ASC
                ");
                $stmtTrans->bind_param('isssii', $id, $dateFrom, $dateTo, $id, $yr, $mo);
                $stmtTrans->execute();
                $transactions = $stmtTrans->get_result()->fetch_all(MYSQLI_ASSOC);

                if (empty($targets) && empty($dprs) && empty($transactions)) {
                    continue; 
                }

                $monthEntry['weeks'][] = [
                    'week_num'     => $wNum,
                    'week_label'   => 'Week ' . $wNum,
                    'date_from'    => $dateFrom,
                    'date_to'      => $dateTo,
                    'targets'      => array_values($targets),
                    'dprs'         => $dprs,
                    'transactions' => $transactions,
                ];
            }

            if (!empty($monthEntry['weeks'])) {
                $result[] = $monthEntry;
            }
        }

        echo json_encode(['success' => true, 'data' => $result]);
        exit;
    }

    private function getWeekStartEndDates($yr, $mo, $weekNo)
    {
        $monthStart = new \DateTime(sprintf('%04d-%02d-01', $yr, $mo));
        $baseDate   = clone $monthStart;
        $monthEnd   = new \DateTime($monthStart->format('Y-m-t'));

        if ($weekNo === 1) {
            $weekStart = $baseDate->format('Y-m-d');
            $weekEnd   = (clone $baseDate)->modify('+6 days')->format('Y-m-d');
        } elseif ($weekNo === 2) {
            $weekStart = (clone $baseDate)->modify('+7 days')->format('Y-m-d');
            $weekEnd   = (clone $baseDate)->modify('+13 days')->format('Y-m-d');
        } elseif ($weekNo === 3) {
            $weekStart = (clone $baseDate)->modify('+14 days')->format('Y-m-d');
            $weekEnd   = (clone $baseDate)->modify('+20 days')->format('Y-m-d');
        } else {
            $weekStart = (clone $baseDate)->modify('+21 days')->format('Y-m-d');
            $weekEnd   = $monthEnd->format('Y-m-d');
            if ($weekEnd < $weekStart) {
                $weekEnd = (clone $baseDate)->modify('+27 days')->format('Y-m-d');
            }
        }
        return [$weekStart, $weekEnd];
    }

    // =========================================================================
    // AJAX — Tab 8: Work Orders
    // =========================================================================

    public function ajaxWorkOrders($id)
    {
        header('Content-Type: application/json');
        $id = (int)$id;

        $stmt = $this->db->prepare("
            SELECT wo.*, 
                   c.name AS contractor_name, 
                   c.phone AS contractor_phone,
                   u.full_name AS creator_name
            FROM con_work_orders wo
            LEFT JOIN contractors c ON c.id = wo.contractor_id
            LEFT JOIN users u ON u.id = wo.created_by
            WHERE wo.project_id = ?
            ORDER BY wo.created_at DESC
        ");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $workOrders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['success' => true, 'data' => $workOrders]);
        exit;
    }

    // =========================================================================
    // SITE ENGINEER DASHBOARD
    // Permissions: Material Receiving, Material Issue, Project View
    // =========================================================================
    public function siteEngineerDashboard()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        // KPI: Total Projects Assigned to Site Engineer
        $r1 = $this->db->query("SELECT COUNT(*) AS c FROM projects p WHERE p.deleted_at IS NULL AND p.site_engineer_id = {$userId}");
        $myProjectsCount = $r1 ? (int)$r1->fetch_assoc()['c'] : 0;

        // KPI: Material Received (GRN) By Me
        $r2 = $this->db->query("SELECT COUNT(*) AS c FROM material_receiving WHERE received_by = {$userId}");
        $totalReceived = $r2 ? (int)$r2->fetch_assoc()['c'] : 0;

        // KPI: Material Issued By Me
        $r3 = $this->db->query("SELECT COUNT(*) AS c FROM con_material_issues WHERE issued_by = {$userId}");
        $totalIssued = $r3 ? (int)$r3->fetch_assoc()['c'] : 0;

        // KPI: Weekly Reports Submitted By Me
        $r4 = $this->db->query("SELECT COUNT(*) AS c FROM weekly_progress_report WHERE site_engineer_id = {$userId}");
        $totalReports = $r4 ? (int)$r4->fetch_assoc()['c'] : 0;

        // My Assigned Projects List
        $rProjs = $this->db->query("
            SELECT p.id, p.name, p.project_status, COALESCE(l.name,'') AS location_name,
                   COALESCE(prog.progress_pct,0) AS progress_pct
            FROM projects p
            LEFT JOIN locations l ON l.id=p.location_id
            LEFT JOIN (
                SELECT wp.project_id, SUM((COALESCE(tf.gain_percentage,0)/100)*(COALESCE(tf.target_percentage,0)/100)*(COALESCE(wp.weight_percentage,0)/100)*COALESCE(ib.weight_percentage,100)) AS progress_pct
                FROM project_working_plans wp
                LEFT JOIN target_fixed tf ON tf.work_plan_id = wp.id
                LEFT JOIN initial_estimate_budgets ib ON ib.id = wp.initial_budget_id
                GROUP BY wp.project_id
            ) prog ON prog.project_id=p.id
            WHERE p.deleted_at IS NULL AND p.site_engineer_id = {$userId}
            ORDER BY p.id DESC
        ");
        $myProjects = $rProjs ? $rProjs->fetch_all(MYSQLI_ASSOC) : [];

        // Recent Material Receiving (GRN)
        $rMR = $this->db->query("
            SELECT mr.*, p.name AS project_name, u.full_name AS received_by_name
            FROM material_receiving mr
            LEFT JOIN purchase_orders po ON po.id = mr.purchase_order_id
            LEFT JOIN purchase_requests pr ON pr.id = po.request_id
            LEFT JOIN projects p ON p.id = pr.project_id
            LEFT JOIN users u ON u.id = mr.received_by
            WHERE mr.received_by = {$userId}
            ORDER BY mr.id DESC LIMIT 5
        ");
        $recentReceives = $rMR ? $rMR->fetch_all(MYSQLI_ASSOC) : [];

        // Recent Material Issues
        $rMI = $this->db->query("
            SELECT mi.*, p.name AS project_name, m.name AS material_name
            FROM con_material_issues mi
            LEFT JOIN projects p ON p.id = mi.project_id
            LEFT JOIN con_materials m ON m.id = mi.material_id
            WHERE mi.issued_by = {$userId}
            ORDER BY mi.id DESC LIMIT 5
        ");
        $recentIssues = $rMI ? $rMI->fetch_all(MYSQLI_ASSOC) : [];

        // Recent Weekly Reports
        $rWPR = $this->db->query("
            SELECT wpr.*, p.name AS project_name
            FROM weekly_progress_report wpr
            LEFT JOIN projects p ON p.id = wpr.project_id
            WHERE wpr.site_engineer_id = {$userId}
            ORDER BY wpr.id DESC LIMIT 5
        ");
        $recentReports = $rWPR ? $rWPR->fetch_all(MYSQLI_ASSOC) : [];

        // ── Approved Purchase Orders for SE's projects ─────────────────────────
        // POs where PM has approved (approved_by_pm != 0) on SE's assigned projects
        $rApprovedPOs = $this->db->query("
            SELECT po.id, po.status, po.order_date, po.created_at,
                   po.approved_by_pm, po.pm_approved_at,
                   pr.id   AS pr_id,
                   p.id    AS project_id,
                   p.name  AS project_name,
                   u.full_name  AS created_by_name,
                   pm.full_name AS approved_by_name,
                   COUNT(poi.id) AS item_count,
                   COALESCE(SUM(poi.quantity * poi.unit_price), 0) AS total_amount
            FROM purchase_orders po
            JOIN purchase_requests pr  ON pr.id  = po.request_id
            JOIN projects p            ON p.id   = pr.project_id
            LEFT JOIN users u          ON u.id   = po.created_by
            LEFT JOIN users pm         ON pm.id  = po.approved_by_pm
            LEFT JOIN purchase_order_items poi ON poi.purchase_order_id = po.id
            WHERE p.site_engineer_id = {$userId}
              AND po.approved_by_pm != 0
              AND po.approved_by_pm IS NOT NULL
            GROUP BY po.id, po.status, po.order_date, po.created_at,
                     po.approved_by_pm, po.pm_approved_at,
                     pr.id, p.id, p.name, u.full_name, pm.full_name
            ORDER BY po.pm_approved_at DESC
            LIMIT 15
        ");
        $approvedPOs = $rApprovedPOs ? $rApprovedPOs->fetch_all(MYSQLI_ASSOC) : [];

        return $this->view('phase2/dashboard/site-engineer-dashboard', [
            'current_route'   => 'con-site-engineer-dashboard',
            'myProjectsCount' => $myProjectsCount,
            'totalReceived'   => $totalReceived,
            'totalIssued'     => $totalIssued,
            'totalReports'    => $totalReports,
            'myProjects'      => $myProjects,
            'recentReceives'  => $recentReceives,
            'recentIssues'    => $recentIssues,
            'recentReports'   => $recentReports,
            'approvedPOs'     => $approvedPOs,
        ]);
    }

    // =========================================================================
    // OPERATIONS DASHBOARD
    // Permissions: ...
    // =========================================================================
    
    public function operationsDashboard()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $isAdmin = in_array($roleId, [1, 3, 15, 22]);

        $filter = $this->getProjectRoleFilter('p');

        $r1 = $this->db->query("SELECT COUNT(*) AS c FROM projects p WHERE p.deleted_at IS NULL AND $filter");
        $myProjectsCount = $r1 ? (int)$r1->fetch_assoc()['c'] : 0;

        $r2 = $this->db->query("SELECT COUNT(*) AS c FROM project_working_plans wp JOIN projects p ON p.id = wp.project_id WHERE p.deleted_at IS NULL AND $filter AND (wp.approved_status = 'Pending' OR wp.approved_status IS NULL)");
        $pendingWorkingPlans = $r2 ? (int)$r2->fetch_assoc()['c'] : 0;

        $r3 = $this->db->query("SELECT COUNT(*) AS c FROM con_material_issues mi JOIN projects p ON p.id = mi.project_id WHERE p.deleted_at IS NULL AND $filter");
        $totalMaterialIssues = $r3 ? (int)$r3->fetch_assoc()['c'] : 0;

        $r4 = $this->db->query("SELECT COUNT(*) AS c FROM vendor_payment_requests vpr JOIN projects p ON p.id = vpr.project_id WHERE p.deleted_at IS NULL AND $filter AND vpr.status IN ('Submitted', 'Pending')");
        $pendingVendorPayments = $r4 ? (int)$r4->fetch_assoc()['c'] : 0;

        $rProjs = $this->db->query("SELECT p.id, p.name, p.project_status, COALESCE(l.name,'') AS location_name FROM projects p LEFT JOIN locations l ON l.id = p.location_id WHERE p.deleted_at IS NULL AND $filter ORDER BY p.id DESC");
        $myProjects = $rProjs ? $rProjs->fetch_all(MYSQLI_ASSOC) : [];

        $rWP = $this->db->query("SELECT wp.*, p.name AS project_name FROM project_working_plans wp JOIN projects p ON p.id = wp.project_id WHERE p.deleted_at IS NULL AND $filter ORDER BY wp.id DESC LIMIT 5");
        $recentWorkingPlans = $rWP ? $rWP->fetch_all(MYSQLI_ASSOC) : [];

        $rMI = $this->db->query("SELECT mi.*, p.name AS project_name, m.name AS material_name FROM con_material_issues mi JOIN projects p ON p.id = mi.project_id LEFT JOIN con_materials m ON m.id = mi.material_id WHERE p.deleted_at IS NULL AND $filter ORDER BY mi.id DESC LIMIT 5");
        $recentMaterialIssues = $rMI ? $rMI->fetch_all(MYSQLI_ASSOC) : [];

        $rVP = $this->db->query("SELECT vpr.*, p.name AS project_name, v.name AS vendor_name FROM vendor_payment_requests vpr JOIN projects p ON p.id = vpr.project_id LEFT JOIN vendors v ON v.id = vpr.vendor_id WHERE p.deleted_at IS NULL AND $filter ORDER BY vpr.id DESC LIMIT 5");
        $recentVendorPayments = $rVP ? $rVP->fetch_all(MYSQLI_ASSOC) : [];

        $pendingWorkOrders = [];
        if ($roleId === 20 || $isAdmin) {
            $rWO = $this->db->query("
                SELECT wo.id, wo.title, wo.total_amount, wo.created_at,
                       p.name AS project_name,
                       c.name AS contractor_name,
                       u.full_name AS creator_name
                FROM con_work_orders wo
                JOIN con_work_order_approvals wa ON wa.work_order_id = wo.id AND wa.role_id = 20
                LEFT JOIN projects p ON p.id = wo.project_id
                LEFT JOIN contractors c ON c.id = wo.contractor_id
                LEFT JOIN users u ON u.id = wo.created_by
                WHERE wo.status = 'draft'
                  AND wa.status = 'pending'
                ORDER BY wo.created_at DESC
                LIMIT 10
            ");
            $pendingWorkOrders = $rWO ? $rWO->fetch_all(MYSQLI_ASSOC) : [];
        }

        return $this->view('phase2/dashboard/operations-dashboard', [
            'current_route'         => 'operations-dashboard',
            'isAdmin'               => $isAdmin,
            'myProjectsCount'       => $myProjectsCount,
            'pendingWorkingPlans'   => $pendingWorkingPlans,
            'totalMaterialIssues'   => $totalMaterialIssues,
            'pendingVendorPayments' => $pendingVendorPayments,
            'myProjects'            => $myProjects,
            'recentWorkingPlans'    => $recentWorkingPlans,
            'recentMaterialIssues'  => $recentMaterialIssues,
            'recentVendorPayments'  => $recentVendorPayments,
            'pendingWorkOrders'     => $pendingWorkOrders,
        ]);
    }

    public function qcDashboard()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // KPI: Total Material Issues
        $r1 = $this->db->query("SELECT COUNT(*) AS c FROM con_material_issues");
        $totalIssues = $r1 ? (int)$r1->fetch_assoc()['c'] : 0;

        // KPI: Total Purchase Orders
        $r2 = $this->db->query("SELECT COUNT(*) AS c FROM purchase_orders");
        $totalPOs = $r2 ? (int)$r2->fetch_assoc()['c'] : 0;

        // KPI: Pending Working Plans (not yet approved)
        $r3 = $this->db->query("SELECT COUNT(*) AS c FROM project_working_plans WHERE approved_status IN ('Pending','Draft') OR approved_status IS NULL");
        $pendingPlans = $r3 ? (int)$r3->fetch_assoc()['c'] : 0;

        // KPI: Total DPR (Project Reports)
        $r4 = $this->db->query("SELECT COUNT(*) AS c FROM weekly_progress_report");
        $totalDPRs = $r4 ? (int)$r4->fetch_assoc()['c'] : 0;

        // KPI: Low Stock alerts
        $r5 = $this->db->query("SELECT COUNT(*) AS c FROM con_stocks s JOIN con_materials m ON m.id=s.material_id WHERE s.current_quantity < m.min_stock_level");
        $lowStock = $r5 ? (int)$r5->fetch_assoc()['c'] : 0;

        // KPI: Initial Estimates
        $r6 = $this->db->query("SELECT COUNT(*) AS c FROM initial_estimate_budgets");
        $totalEstimates = $r6 ? (int)$r6->fetch_assoc()['c'] : 0;

        // Recent Project Progress
        $rProj = $this->db->query("
            SELECT p.id, p.name, p.project_status,
                   COALESCE(l.name,'') AS location_name,
                   COALESCE(prog.progress_pct,0) AS progress_pct,
                   COALESCE(bud.total_budget,0) AS total_budget
            FROM projects p
            LEFT JOIN locations l ON l.id=p.location_id
            LEFT JOIN (
                SELECT wp.project_id, SUM((COALESCE(tf.gain_percentage,0)/100)*(COALESCE(tf.target_percentage,0)/100)*(COALESCE(wp.weight_percentage,0)/100)*COALESCE(ib.weight_percentage,100)) AS progress_pct
                FROM project_working_plans wp
                LEFT JOIN target_fixed tf ON tf.work_plan_id = wp.id
                LEFT JOIN initial_estimate_budgets ib ON ib.id = wp.initial_budget_id
                GROUP BY wp.project_id
            ) prog ON prog.project_id=p.id
            LEFT JOIN (SELECT project_id, SUM(total_estimated_cost) AS total_budget FROM initial_estimate_budgets WHERE status='approved' GROUP BY project_id) bud ON bud.project_id=p.id
            WHERE p.deleted_at IS NULL AND " . $this->getProjectRoleFilter('p') . " ORDER BY p.id DESC LIMIT 6");
        $projects = $rProj ? $rProj->fetch_all(MYSQLI_ASSOC) : [];

        // Recent Material Issues
        $rIssues = $this->db->query("
            SELECT mi.*, m.name AS material_name, p.name AS project_name
            FROM con_material_issues mi
            LEFT JOIN con_materials m ON m.id=mi.material_id
            LEFT JOIN projects p ON p.id=mi.project_id
            ORDER BY mi.id DESC LIMIT 5");
        $recentIssues = $rIssues ? $rIssues->fetch_all(MYSQLI_ASSOC) : [];

        // Recent DPRs
        $rDPR = $this->db->query("
            SELECT dpr.*, p.name AS project_name
            FROM weekly_progress_report dpr
            LEFT JOIN projects p ON p.id=dpr.project_id
            ORDER BY dpr.id DESC LIMIT 5");
        $recentDPRs = $rDPR ? $rDPR->fetch_all(MYSQLI_ASSOC) : [];

        // Pending Targets awaiting QC approval
        $rPendingTargets = $this->db->query("
            SELECT tf.id, tf.target_title, tf.target_percentage, tf.gain_percentage,
                   tf.year, tf.month, tf.week_number, tf.created_at,
                   p.name  AS project_name,
                   u.full_name AS creator_name,
                   COALESCE(des.name,'') AS creator_designation
            FROM target_fixed tf
            LEFT JOIN projects     p   ON p.id  = tf.project_id
            LEFT JOIN users        u   ON u.id  = tf.user_id
            LEFT JOIN employees    emp ON emp.user_id = u.id
            LEFT JOIN designations des ON des.id = emp.designation_id
            WHERE tf.approved_by_qc = 0
            ORDER BY tf.created_at DESC
            LIMIT 10");
        $pendingTargets = $rPendingTargets ? $rPendingTargets->fetch_all(MYSQLI_ASSOC) : [];

        // Pending GRNs awaiting QC
        $rPendingGRNsQC = $this->db->query("
            SELECT mr.id, mr.receiving_date,
                   p.name AS project_name,
                   u.full_name AS received_by_name
            FROM material_receiving mr
            JOIN purchase_orders po ON po.id = mr.purchase_order_id
            JOIN purchase_requests pr ON pr.id = po.request_id
            LEFT JOIN projects p ON p.id = pr.project_id
            LEFT JOIN users u ON u.id = mr.received_by
            WHERE mr.overall_status = 'Pending QC'
            ORDER BY mr.id DESC LIMIT 10");
        $pendingGRNsQC = $rPendingGRNsQC ? $rPendingGRNsQC->fetch_all(MYSQLI_ASSOC) : [];

        // Pending Weekly Reports awaiting QC
        $rPendingWeeklyQC = $this->db->query("
            SELECT r.id, r.report_date, r.week_no,
                   p.name AS project_name,
                   u.full_name AS created_by_name
            FROM weekly_progress_report r
            LEFT JOIN projects p ON p.id = r.project_id
            LEFT JOIN users u ON u.id = r.created_by
            WHERE r.approved_by_qc = 0
            ORDER BY r.id DESC LIMIT 10");
        $pendingWeeklyReportsQC = $rPendingWeeklyQC ? $rPendingWeeklyQC->fetch_all(MYSQLI_ASSOC) : [];


        return $this->view('phase2/dashboard/qc-dashboard', [
            'current_route'  => 'qc-dashboard',
            'totalIssues'    => $totalIssues,
            'totalPOs'       => $totalPOs,
            'pendingPlans'   => $pendingPlans,
            'totalDPRs'      => $totalDPRs,
            'lowStock'       => $lowStock,
            'totalEstimates' => $totalEstimates,
            'projects'       => $projects,
            'recentIssues'   => $recentIssues,
            'recentDPRs'     => $recentDPRs,
            'pendingTargets' => $pendingTargets,
            'pendingGRNsQC'  => $pendingGRNsQC,
            'pendingWeeklyReportsQC' => $pendingWeeklyReportsQC,
        ]);
    }

    // =========================================================================
    // PURCHASE TEAM LEAD DASHBOARD
    // Permissions: Material Issue, Purchase order, Vendor Manage,
    //              Vendor Penalty, Material Manage
    // =========================================================================
    public function purchaseTeamLeadDashboard()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // KPI: Total Purchase Orders
        $r1 = $this->db->query("SELECT COUNT(*) AS c FROM purchase_orders");
        $totalPOs = $r1 ? (int)$r1->fetch_assoc()['c'] : 0;

        // KPI: Total Vendors
        $r2 = $this->db->query("SELECT COUNT(*) AS c FROM vendors");
        $totalVendors = $r2 ? (int)$r2->fetch_assoc()['c'] : 0;

        // KPI: Vendor Penalties (vendor_penalties table)
        $r3 = $this->db->query("SELECT COUNT(*) AS c FROM vendor_penalties");
        if (!$r3) { $r3_alt = $this->db->query("SELECT 0 AS c"); $totalPenalties = 0; } else { $totalPenalties = (int)$r3->fetch_assoc()['c']; }

        // KPI: Total Material Issues
        $r4 = $this->db->query("SELECT COUNT(*) AS c FROM con_material_issues");
        $totalIssues = $r4 ? (int)$r4->fetch_assoc()['c'] : 0;

        // KPI: Total Materials
        $r5 = $this->db->query("SELECT COUNT(*) AS c FROM con_materials");
        $totalMaterials = $r5 ? (int)$r5->fetch_assoc()['c'] : 0;

        // KPI: Pending Payment Requests
        $r6 = $this->db->query("SELECT COUNT(*) AS c FROM vendor_payment_requests WHERE status IN ('Submitted','Approved')");
        $pendingPayments = $r6 ? (int)$r6->fetch_assoc()['c'] : 0;

        // Recent Vendors
        $rVend = $this->db->query("SELECT * FROM vendors ORDER BY id DESC LIMIT 5");
        $recentVendors = $rVend ? $rVend->fetch_all(MYSQLI_ASSOC) : [];

        // Recent Purchase Orders (vendor comes from purchase_order_items.selected_vendor_id)
        $rPO = $this->db->query("
            SELECT po.*,
                   pr.project_id,
                   p.name  AS project_name,
                   v.name  AS vendor_name,
                   (SELECT COALESCE(SUM(poi2.total_price),0)
                    FROM purchase_order_items poi2
                    WHERE poi2.purchase_order_id = po.id) AS total_amount
            FROM purchase_orders po
            LEFT JOIN purchase_requests pr ON pr.id = po.request_id
            LEFT JOIN projects p  ON p.id  = pr.project_id
            LEFT JOIN (
                SELECT purchase_order_id, MIN(selected_vendor_id) AS vendor_id
                FROM purchase_order_items GROUP BY purchase_order_id
            ) vi ON vi.purchase_order_id = po.id
            LEFT JOIN vendors v ON v.id = vi.vendor_id
            ORDER BY po.id DESC LIMIT 5");
        $recentPOs = $rPO ? $rPO->fetch_all(MYSQLI_ASSOC) : [];

        // Material Stock Overview
        $rStock = $this->db->query("
            SELECT m.name AS material_name, m.unit, m.min_stock_level,
                   COALESCE(SUM(s.current_quantity),0) AS total_qty
            FROM con_materials m
            LEFT JOIN con_stocks s ON s.material_id=m.id
            GROUP BY m.id ORDER BY total_qty ASC LIMIT 8");
        $stockOverview = $rStock ? $rStock->fetch_all(MYSQLI_ASSOC) : [];

        // ── Approved BOQs that have NO purchase request yet ──────────────────
        // Procurement Head must create a PR for each of these
        $rPendingBOQs = $this->db->query("
            SELECT bm.id AS boq_id, bm.version, bm.approved_at,
                   p.id  AS project_id, p.name AS project_name,
                   u.full_name  AS creator_name,
                   COUNT(bi.id) AS item_count,
                   COALESCE(SUM(bi.total_cost), 0) AS grand_total
            FROM boq_master bm
            LEFT JOIN projects p   ON p.id  = bm.project_id
            LEFT JOIN users u      ON u.id  = bm.created_by
            LEFT JOIN boq_items bi ON bi.boq_id = bm.id
            WHERE bm.status = 'Approved'
              AND NOT EXISTS (
                    SELECT 1 FROM purchase_requests pr
                    WHERE pr.bom_id = bm.id
              )
            GROUP BY bm.id, bm.version, bm.approved_at,
                     p.id, p.name, u.full_name
            ORDER BY bm.approved_at DESC
            LIMIT 15");
        $pendingBOQsForPR = $rPendingBOQs ? $rPendingBOQs->fetch_all(MYSQLI_ASSOC) : [];

        // ── Pending Purchase Requests (not yet converted to PO) ───────────────
        $rPendingPRs = $this->db->query("
            SELECT pr.id, pr.status, pr.created_at, pr.notes,
                   p.name AS project_name,
                   u.full_name AS created_by_name,
                   COUNT(pri.id) AS item_count
            FROM purchase_requests pr
            LEFT JOIN projects p ON p.id = pr.project_id
            LEFT JOIN users u ON u.id = pr.created_by
            LEFT JOIN purchase_request_items pri ON pri.request_id = pr.id
            WHERE pr.status IN ('Draft','Submitted','Approved')
              AND NOT EXISTS (
                    SELECT 1 FROM purchase_orders po WHERE po.request_id = pr.id
              )
            GROUP BY pr.id, pr.status, pr.created_at, pr.notes, p.name, u.full_name
            ORDER BY pr.id DESC LIMIT 10");
        $pendingPRsForPO = $rPendingPRs ? $rPendingPRs->fetch_all(MYSQLI_ASSOC) : [];

        // ── ALL BOQs (any status) — full visibility for Team Lead ────────────
        $rAllBOQs = $this->db->query("
            SELECT bm.id AS boq_id, bm.version, bm.status, bm.created_at, bm.approved_at,
                   p.id  AS project_id, p.name AS project_name,
                   u.full_name  AS creator_name,
                   au.full_name AS approver_name,
                   COUNT(bi.id)                    AS item_count,
                   COALESCE(SUM(bi.total_cost), 0) AS grand_total
            FROM boq_master bm
            LEFT JOIN projects  p   ON p.id  = bm.project_id
            LEFT JOIN users     u   ON u.id  = bm.created_by
            LEFT JOIN users     au  ON au.id = bm.approved_by
            LEFT JOIN boq_items bi  ON bi.boq_id = bm.id
            GROUP BY bm.id, bm.version, bm.status, bm.created_at, bm.approved_at,
                     p.id, p.name, u.full_name, au.full_name
            ORDER BY bm.created_at DESC
            LIMIT 30");
        $allBOQs = $rAllBOQs ? $rAllBOQs->fetch_all(MYSQLI_ASSOC) : [];

        // ── PRs awaiting approval by Purchase Team Lead (role 17) ───────────
        $rPRsForApproval = $this->db->query("
            SELECT pr.id, pr.status, pr.created_at, pr.notes,
                   p.name  AS project_name,
                   u.full_name AS created_by_name,
                   r.name  AS created_by_role,
                   COUNT(pri.id) AS item_count
            FROM purchase_requests pr
            LEFT JOIN projects p    ON p.id  = pr.project_id
            LEFT JOIN users u       ON u.id  = pr.created_by
            LEFT JOIN roles r       ON r.id  = u.role_id
            LEFT JOIN purchase_request_items pri ON pri.request_id = pr.id
            WHERE pr.status IN ('Draft', 'Submitted')
            GROUP BY pr.id, pr.status, pr.created_at, pr.notes,
                     p.name, u.full_name, r.name
            ORDER BY
                CASE pr.status WHEN 'Submitted' THEN 0 WHEN 'Draft' THEN 1 ELSE 2 END,
                pr.id DESC
            LIMIT 20");
        $prsForApproval = $rPRsForApproval ? $rPRsForApproval->fetch_all(MYSQLI_ASSOC) : [];

        return $this->view('phase2/dashboard/purchase-team-lead-dashboard', [
            'current_route'    => 'purchase-team-lead-dashboard',
            'totalPOs'         => $totalPOs,
            'totalVendors'     => $totalVendors,
            'totalPenalties'   => $totalPenalties,
            'totalIssues'      => $totalIssues,
            'totalMaterials'   => $totalMaterials,
            'pendingPayments'  => $pendingPayments,
            'recentVendors'    => $recentVendors,
            'recentPOs'        => $recentPOs,
            'stockOverview'    => $stockOverview,
            'pendingBOQsForPR' => $pendingBOQsForPR,
            'pendingPRsForPO'  => $pendingPRsForPO,
            'allBOQs'          => $allBOQs,
            'prsForApproval'   => $prsForApproval,
        ]);
    }

    // =========================================================================
    // DRAFTSMAN DASHBOARD
    // Permissions: Draft Design (Project Drawings, Design Types, etc.)
    // =========================================================================
    public function draftDashboard()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = $_SESSION['user']['id']  ?? 0;
        $roleId = $_SESSION['user']['role_id'] ?? 0;

        // KPI: Total Project Drawings
        $r1 = $this->db->query("SELECT COUNT(*) AS c FROM project_drawings");
        $totalDrawings = $r1 ? (int)$r1->fetch_assoc()['c'] : 0;

        // KPI: Total Design Types
        $r2 = $this->db->query("SELECT COUNT(*) AS c FROM design_types");
        $totalDesignTypes = $r2 ? (int)$r2->fetch_assoc()['c'] : 0;

        // KPI: Total Projects with Drawings
        $r3 = $this->db->query("SELECT COUNT(DISTINCT project_id) AS c FROM project_drawings");
        $projectsWithDrawings = $r3 ? (int)$r3->fetch_assoc()['c'] : 0;

        // KPI: Total Architect & Engineering records
        $r4 = $this->db->query("SELECT COUNT(*) AS c FROM architect_engineering");
        $totalAE = $r4 ? (int)$r4->fetch_assoc()['c'] : 0;

        // Drawings by Design Type
        $rByType = $this->db->query("
            SELECT dt.name AS design_type, COUNT(pd.id) AS count
            FROM design_types dt
            LEFT JOIN project_drawings pd ON pd.design_type_id=dt.id
            GROUP BY dt.id ORDER BY count DESC");
        $drawingsByType = $rByType ? $rByType->fetch_all(MYSQLI_ASSOC) : [];

        // Recent Drawings
        $rDrawings = $this->db->query("
            SELECT pd.*, p.name AS project_name, dt.name AS design_type_name
            FROM project_drawings pd
            LEFT JOIN projects p ON p.id=pd.project_id
            LEFT JOIN design_types dt ON dt.id=pd.design_type_id
            ORDER BY pd.id DESC LIMIT 8");
        $recentDrawings = $rDrawings ? $rDrawings->fetch_all(MYSQLI_ASSOC) : [];

        // Projects needing drawings (no drawings yet) — only those where AE is 'fully' done
        $rNeedDraw = $this->db->query("
            SELECT p.id, p.name, p.project_status, COALESCE(l.name,'') AS location_name
            FROM projects p
            LEFT JOIN locations l ON l.id=p.location_id
            LEFT JOIN project_drawings pd ON pd.project_id=p.id
            INNER JOIN architect_engineering ae ON ae.project_id=p.id AND ae.status='fully'
            WHERE p.deleted_at IS NULL AND " . $this->getProjectRoleFilter('p') . " AND pd.id IS NULL
            ORDER BY p.id DESC LIMIT 5");
        $projectsNeedingDrawings = $rNeedDraw ? $rNeedDraw->fetch_all(MYSQLI_ASSOC) : [];

        // Fetch recent architect_engineering records to show details when clicked
        $rAE = $this->db->query("
            SELECT ae.*, p.name AS project_name, u.full_name AS internal_architect_name
            FROM architect_engineering ae
            LEFT JOIN projects p ON p.id = ae.project_id
            LEFT JOIN users u ON u.id = ae.user_id
            ORDER BY ae.id DESC
            LIMIT 15
        ");
        $architectEngineeringList = $rAE ? $rAE->fetch_all(MYSQLI_ASSOC) : [];

        // ── WORKFLOW GATE: Projects assigned to this draftsman but AE NOT yet 'fully' ──
        // Only relevant for actual draftsman role; admins/PM/QC skip this
        $projectsWaitingForAE = [];
        if (!in_array($roleId, [1, 2, 3, 13, 15, 22])) {
            $stmtWait = $this->db->prepare("
                SELECT p.id, p.name,
                       COALESCE(u.full_name, 'N/A') AS architect_name
                FROM projects p
                LEFT JOIN users u ON u.id = p.architect_id
                WHERE p.draftsman_id = ?
                  AND p.deleted_at IS NULL
                  AND p.id NOT IN (
                      SELECT project_id FROM architect_engineering WHERE status = 'fully'
                  )
                ORDER BY p.name ASC
            ");
            $stmtWait->bind_param('i', $userId);
            $stmtWait->execute();
            $projectsWaitingForAE = $stmtWait->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        return $this->view('phase2/dashboard/draft-dashboard', [
            'current_route'           => 'draft-dashboard',
            'totalDrawings'           => $totalDrawings,
            'totalDesignTypes'        => $totalDesignTypes,
            'projectsWithDrawings'    => $projectsWithDrawings,
            'totalAE'                 => $totalAE,
            'drawingsByType'          => $drawingsByType,
            'recentDrawings'          => $recentDrawings,
            'projectsNeedingDrawings' => $projectsNeedingDrawings,
            'projectsWaitingForAE'    => $projectsWaitingForAE,
            'architectEngineeringList'=> $architectEngineeringList,
        ]);
    }

    // =========================================================================
    // LAND MANAGER DASHBOARD
    // Permissions: Land Study
    // =========================================================================
    public function landDashboard()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // KPI: Total Land Studies
        $r1 = $this->db->query("SELECT COUNT(*) AS c FROM land_visibility_study");
        $totalStudies = $r1 ? (int)$r1->fetch_assoc()['c'] : 0;

        // KPI: Total Projects
        $r2 = $this->db->query("SELECT COUNT(*) AS c FROM projects p WHERE p.deleted_at IS NULL AND " . $this->getProjectRoleFilter('p') . " AND " . $this->getProjectRoleFilter('p') . "");
        $totalProjects = $r2 ? (int)$r2->fetch_assoc()['c'] : 0;

        // KPI: Projects with Land Study
        $r3 = $this->db->query("SELECT COUNT(DISTINCT project_id) AS c FROM land_visibility_study");
        $projectsWithStudy = $r3 ? (int)$r3->fetch_assoc()['c'] : 0;

        // KPI: Projects without Land Study
        $projectsNoStudy = max(0, $totalProjects - $projectsWithStudy);

        // Recent Land Studies
        $rStudies = $this->db->query("
            SELECT lvs.*, p.name AS project_name, p.project_status,
                   COALESCE(l.name,'') AS location_name
            FROM land_visibility_study lvs
            LEFT JOIN projects p ON p.id=lvs.project_id
            LEFT JOIN locations l ON l.id=p.location_id
            ORDER BY lvs.id DESC LIMIT 8");
        $recentStudies = $rStudies ? $rStudies->fetch_all(MYSQLI_ASSOC) : [];

        // Projects without land study
        $rPNoStudy = $this->db->query("
            SELECT p.id, p.name, p.project_status, COALESCE(l.name,'') AS location_name
            FROM projects p
            LEFT JOIN locations l ON l.id=p.location_id
            LEFT JOIN land_visibility_study lvs ON lvs.project_id=p.id
            WHERE p.deleted_at IS NULL AND " . $this->getProjectRoleFilter('p') . " AND lvs.id IS NULL
            ORDER BY p.id DESC LIMIT 6");
        $pendingStudyProjects = $rPNoStudy ? $rPNoStudy->fetch_all(MYSQLI_ASSOC) : [];

        return $this->view('phase2/dashboard/land-dashboard', [
            'current_route'        => 'land-dashboard',
            'totalStudies'         => $totalStudies,
            'totalProjects'        => $totalProjects,
            'projectsWithStudy'    => $projectsWithStudy,
            'projectsNoStudy'      => $projectsNoStudy,
            'recentStudies'        => $recentStudies,
            'pendingStudyProjects' => $pendingStudyProjects,
        ]);
    }

    // =========================================================================
    // PROJECT ARCHITECTURE DASHBOARD
    // Permissions: Architect & Engineering ONLY
    // =========================================================================
    public function projectArchitectureDashboard()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = (int)($_SESSION['user']['id']  ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $isAdmin = in_array($roleId, [1, 2, 3, 13, 15, 22]);

        // ── KPI 1: Total A&E records ──────────────────────────────────────────
        $r1 = $this->db->query("SELECT COUNT(*) AS c FROM architect_engineering");
        $totalAE = $r1 ? (int)$r1->fetch_assoc()['c'] : 0;

        // ── KPI 2: Projects with A&E ─────────────────────────────────────────
        $r2 = $this->db->query("SELECT COUNT(DISTINCT project_id) AS c FROM architect_engineering");
        $projectsWithAE = $r2 ? (int)$r2->fetch_assoc()['c'] : 0;

        // ── KPI 3: A&E status breakdown ──────────────────────────────────────
        $r3 = $this->db->query("SELECT COUNT(*) AS c FROM architect_engineering WHERE status = 'fully'");
        $aeFullyDone = $r3 ? (int)$r3->fetch_assoc()['c'] : 0;

        $r4 = $this->db->query("SELECT COUNT(*) AS c FROM architect_engineering WHERE status = 'partially'");
        $aePartial = $r4 ? (int)$r4->fetch_assoc()['c'] : 0;

        // ── KPI 4: Projects without any A&E record ────────────────────────────
        $filter = $this->getProjectRoleFilter('p');
        $rTotalProjects = $this->db->query("SELECT COUNT(*) AS c FROM projects p WHERE p.deleted_at IS NULL AND $filter");
        $totalProjects = $rTotalProjects ? (int)$rTotalProjects->fetch_assoc()['c'] : 0;
        $projectsNoAE = max(0, $totalProjects - $projectsWithAE);

        // ── Recent A&E records (last 10) ─────────────────────────────────────
        $rAE = $this->db->query("
            SELECT ae.*,
                   p.name AS project_name,
                   p.project_status,
                   COALESCE(l.name,'') AS location_name,
                   u.full_name AS internal_architect_name
            FROM architect_engineering ae
            LEFT JOIN projects  p ON p.id = ae.project_id
            LEFT JOIN locations l ON l.id = p.location_id
            LEFT JOIN users     u ON u.id = ae.user_id
            WHERE p.deleted_at IS NULL AND $filter
            ORDER BY ae.id DESC
            LIMIT 10
        ");
        $recentAE = $rAE ? $rAE->fetch_all(MYSQLI_ASSOC) : [];

        // ── Projects without A&E record yet ───────────────────────────────────
        $rNoAE = $this->db->query("
            SELECT p.id, p.name, p.project_status,
                   COALESCE(l.name,'') AS location_name
            FROM projects p
            LEFT JOIN locations l ON l.id = p.location_id
            LEFT JOIN architect_engineering ae ON ae.project_id = p.id
            WHERE p.deleted_at IS NULL AND $filter AND ae.id IS NULL
            ORDER BY p.id DESC LIMIT 8
        ");
        $projectsNeedingAE = $rNoAE ? $rNoAE->fetch_all(MYSQLI_ASSOC) : [];

        // ── Internal vs External architect breakdown ───────────────────────────
        $rInt = $this->db->query("SELECT COUNT(*) AS c FROM architect_engineering WHERE architect_type = 'internal'");
        $internalArchitects = $rInt ? (int)$rInt->fetch_assoc()['c'] : 0;

        $rExt = $this->db->query("SELECT COUNT(*) AS c FROM architect_engineering WHERE architect_type = 'external'");
        $externalArchitects = $rExt ? (int)$rExt->fetch_assoc()['c'] : 0;

        // ── Workflow Notifications: Land Visibility Completed ─────────────────
        $rLandDone = $this->db->query("
            SELECT p.id, p.name AS project_name, lvs.created_at AS land_completed_date
            FROM land_visibility_study lvs
            JOIN projects p ON p.id = lvs.project_id
            LEFT JOIN architect_engineering ae ON ae.project_id = p.id
            WHERE p.deleted_at IS NULL 
              AND " . $this->getProjectRoleFilter('p') . "
              AND ae.id IS NULL
            ORDER BY lvs.id DESC
            LIMIT 10
        ");
        $landDoneNotifications = $rLandDone ? $rLandDone->fetch_all(MYSQLI_ASSOC) : [];

        return $this->view('phase2/dashboard/project-architecture-dashboard', [
            'current_route'       => 'project-architecture-dashboard',
            'isAdmin'             => $isAdmin,
            'totalAE'             => $totalAE,
            'projectsWithAE'      => $projectsWithAE,
            'aeFullyDone'         => $aeFullyDone,
            'aePartial'           => $aePartial,
            'totalProjects'       => $totalProjects,
            'projectsNoAE'        => $projectsNoAE,
            'internalArchitects'  => $internalArchitects,
            'externalArchitects'  => $externalArchitects,
            'recentAE'            => $recentAE,
            'projectsNeedingAE'   => $projectsNeedingAE,
            'landDoneNotifications'=> $landDoneNotifications,
        ]);
    }

    // =========================================================================
    // ESTIMATOR DASHBOARD
    // =========================================================================

    public function estimatorDashboard()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // KPI 1: Total Project Drawings
        $r1 = $this->db->query("SELECT COUNT(*) AS c FROM project_drawings");
        $totalDrawings = $r1 ? (int)$r1->fetch_assoc()['c'] : 0;

        // KPI 2: Total Design Types
        $r2 = $this->db->query("SELECT COUNT(*) AS c FROM design_types");
        $totalDesignTypes = $r2 ? (int)$r2->fetch_assoc()['c'] : 0;

        // KPI 3: Pending Estimates (status = pending)
        $r3 = $this->db->query("SELECT COUNT(*) AS c FROM initial_estimate_budgets WHERE status = 'pending'");
        $pendingEstimates = $r3 ? (int)$r3->fetch_assoc()['c'] : 0;

        // KPI 4: Approved Estimates
        $r4 = $this->db->query("SELECT COUNT(*) AS c FROM initial_estimate_budgets WHERE status = 'approved'");
        $approvedEstimates = $r4 ? (int)$r4->fetch_assoc()['c'] : 0;

        // KPI 5: Total Approved Budget Value
        $r5 = $this->db->query("SELECT COALESCE(SUM(total_estimated_cost),0) AS total FROM initial_estimate_budgets WHERE status='approved'");
        $totalBudgetValue = $r5 ? (float)$r5->fetch_assoc()['total'] : 0.0;

        // Project Drawings by Design Type (for chart/breakdown)
        $rByType = $this->db->query("
            SELECT dt.name AS design_type_name, dt.id AS design_type_id,
                   COUNT(pd.id) AS drawing_count
            FROM design_types dt
            LEFT JOIN project_drawings pd ON pd.design_type_id = dt.id
            GROUP BY dt.id, dt.name
            ORDER BY drawing_count DESC");
        $drawingsByType = $rByType ? $rByType->fetch_all(MYSQLI_ASSOC) : [];

        // Recent Project Drawings with project & design type
        $rDrawings = $this->db->query("
            SELECT pd.*, p.name AS project_name, dt.name AS design_type_name,
                   u.full_name AS designer_full_name
            FROM project_drawings pd
            LEFT JOIN projects p      ON p.id  = pd.project_id
            LEFT JOIN design_types dt ON dt.id = pd.design_type_id
            LEFT JOIN users u         ON u.id  = pd.user_id
            ORDER BY pd.id DESC LIMIT 8");
        $recentDrawings = $rDrawings ? $rDrawings->fetch_all(MYSQLI_ASSOC) : [];

        // Projects without any estimate yet
        $rNoEst = $this->db->query("
            SELECT p.id, p.name, p.project_status
            FROM projects p
            LEFT JOIN initial_estimate_budgets b ON b.project_id = p.id
            WHERE p.deleted_at IS NULL AND " . $this->getProjectRoleFilter('p') . " AND b.id IS NULL
            ORDER BY p.id DESC LIMIT 6");
        $projectsWithoutEstimate = $rNoEst ? $rNoEst->fetch_all(MYSQLI_ASSOC) : [];

        // Recent Estimates
        $rEst = $this->db->query("
            SELECT b.*, p.name AS project_name,
                   u.full_name AS created_by_name,
                   ab.full_name AS approved_by_name
            FROM initial_estimate_budgets b
            LEFT JOIN projects p  ON p.id  = b.project_id
            LEFT JOIN users u     ON u.id  = b.created_by
            LEFT JOIN users ab    ON ab.id = b.approved_by
            ORDER BY b.id DESC LIMIT 8");
        $recentEstimates = $rEst ? $rEst->fetch_all(MYSQLI_ASSOC) : [];

        // Pending Budgets — detailed list with creator info (for estimator's own submitted list)
        $rPending = $this->db->query("
            SELECT b.id, b.total_estimated_cost, b.created_at, b.status,
                   p.name AS project_name,
                   u.full_name AS creator_name,
                   COALESCE(des.name, '') AS creator_designation
            FROM initial_estimate_budgets b
            LEFT JOIN projects p       ON p.id  = b.project_id
            LEFT JOIN users u          ON u.id  = b.created_by
            LEFT JOIN employees emp    ON emp.user_id = u.id
            LEFT JOIN designations des ON des.id = emp.designation_id
            WHERE b.status = 'pending'
            ORDER BY b.created_at DESC
            LIMIT 10");
        $pendingBudgetsList = $rPending ? $rPending->fetch_all(MYSQLI_ASSOC) : [];

        return $this->view('phase2/dashboard/estimator-dashboard', [
            'current_route'           => 'estimator-dashboard',
            'totalDrawings'           => $totalDrawings,
            'totalDesignTypes'        => $totalDesignTypes,
            'pendingEstimates'        => $pendingEstimates,
            'approvedEstimates'       => $approvedEstimates,
            'totalBudgetValue'        => $totalBudgetValue,
            'drawingsByType'          => $drawingsByType,
            'recentDrawings'          => $recentDrawings,
            'projectsWithoutEstimate' => $projectsWithoutEstimate,
            'recentEstimates'         => $recentEstimates,
            'pendingBudgetsList'      => $pendingBudgetsList,
        ]);
    }

    // =========================================================================
    // PROJECT STRUCTURAL DESIGN DASHBOARD
    // Permissions: Project structure design
    // =========================================================================
    public function projectStructuralDesignDashboard()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = (int)($_SESSION['user']['id']  ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        // Admins / PM / QC see all; otherwise filter by struct_engineer_id
        $isAdmin = in_array($roleId, [1, 2, 3, 13, 15, 22]);

        // ── KPI 1: Projects assigned to this structural engineer ──────────────
        if ($isAdmin) {
            $r1 = $this->db->query("SELECT COUNT(*) AS c FROM projects p WHERE p.deleted_at IS NULL AND " . $this->getProjectRoleFilter('p') . " AND " . $this->getProjectRoleFilter('p') . "");
        } else {
            $s1 = $this->db->prepare("SELECT COUNT(*) AS c FROM projects p WHERE p.deleted_at IS NULL AND " . $this->getProjectRoleFilter('p') . " AND " . $this->getProjectRoleFilter('p') . " AND struct_engineer_id = ?");
            $s1->bind_param('i', $userId);
            $s1->execute();
            $r1 = $s1->get_result();
        }
        $myProjectsCount = $r1 ? (int)$r1->fetch_assoc()['c'] : 0;

        // ── KPI 2: My projects that have structural design records ───────────
        if ($isAdmin) {
            $r2 = $this->db->query("SELECT COUNT(DISTINCT sd.project_id) AS c FROM project_structural_designs sd JOIN projects p ON p.id = sd.project_id WHERE p.deleted_at IS NULL AND " . $this->getProjectRoleFilter('p') . "");
        } else {
            $s2 = $this->db->prepare("SELECT COUNT(DISTINCT sd.project_id) AS c FROM project_structural_designs sd JOIN projects p ON p.id = sd.project_id WHERE p.struct_engineer_id = ? AND p.deleted_at IS NULL");
            $s2->bind_param('i', $userId);
            $s2->execute();
            $r2 = $s2->get_result();
        }
        $projectsWithDesign = $r2 ? (int)$r2->fetch_assoc()['c'] : 0;

        // ── KPI 3: Total design records (scoped) ─────────────────────────────
        if ($isAdmin) {
            $r3 = $this->db->query("SELECT COUNT(*) AS c FROM project_structural_designs");
        } else {
            $s3 = $this->db->prepare("SELECT COUNT(*) AS c FROM project_structural_designs sd JOIN projects p ON p.id = sd.project_id WHERE p.struct_engineer_id = ?");
            $s3->bind_param('i', $userId);
            $s3->execute();
            $r3 = $s3->get_result();
        }
        $totalDesigns = $r3 ? (int)$r3->fetch_assoc()['c'] : 0;

        // ── KPI 4: Projects without design (scoped) ──────────────────────────
        $projectsNoDesign = max(0, $myProjectsCount - $projectsWithDesign);

        // ── Internal / External breakdown (scoped) ───────────────────────────
        if ($isAdmin) {
            $r4 = $this->db->query("SELECT COUNT(*) AS c FROM project_structural_designs WHERE designer_type = 'internal'");
            $r5 = $this->db->query("SELECT COUNT(*) AS c FROM project_structural_designs WHERE designer_type = 'external'");
        } else {
            $s4 = $this->db->prepare("SELECT COUNT(*) AS c FROM project_structural_designs sd JOIN projects p ON p.id = sd.project_id WHERE p.struct_engineer_id = ? AND sd.designer_type = 'internal'");
            $s4->bind_param('i', $userId); $s4->execute(); $r4 = $s4->get_result();
            $s5 = $this->db->prepare("SELECT COUNT(*) AS c FROM project_structural_designs sd JOIN projects p ON p.id = sd.project_id WHERE p.struct_engineer_id = ? AND sd.designer_type = 'external'");
            $s5->bind_param('i', $userId); $s5->execute(); $r5 = $s5->get_result();
        }
        $internalDesigners = $r4 ? (int)$r4->fetch_assoc()['c'] : 0;
        $externalDesigners = $r5 ? (int)$r5->fetch_assoc()['c'] : 0;

        // ── Recent Structural Designs (scoped, last 8) ───────────────────────
        if ($isAdmin) {
            $rRecent = $this->db->query("
                SELECT sd.*, p.name AS project_name,
                       COALESCE(l.name,'') AS location_name,
                       p.project_status
                FROM project_structural_designs sd
                LEFT JOIN projects  p ON p.id = sd.project_id
                LEFT JOIN locations l ON l.id = p.location_id
                ORDER BY sd.id DESC LIMIT 8");
            $recentDesigns = $rRecent ? $rRecent->fetch_all(MYSQLI_ASSOC) : [];
        } else {
            $sRec = $this->db->prepare("
                SELECT sd.*, p.name AS project_name,
                       COALESCE(l.name,'') AS location_name,
                       p.project_status
                FROM project_structural_designs sd
                JOIN  projects  p ON p.id = sd.project_id
                LEFT JOIN locations l ON l.id = p.location_id
                WHERE p.struct_engineer_id = ?
                ORDER BY sd.id DESC LIMIT 8");
            $sRec->bind_param('i', $userId);
            $sRec->execute();
            $recentDesigns = $sRec->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        // ── My projects list (with/without design) ───────────────────────────
        if ($isAdmin) {
            $rProjs = $this->db->query("
                SELECT p.id, p.name, p.project_status,
                       COALESCE(l.name,'') AS location_name,
                       sd.id AS design_id, sd.designer_type, sd.designer_name, sd.design_link
                FROM projects p
                LEFT JOIN locations l ON l.id = p.location_id
                LEFT JOIN project_structural_designs sd ON sd.project_id = p.id
                WHERE p.deleted_at IS NULL AND " . $this->getProjectRoleFilter('p') . "
                ORDER BY p.id DESC");
        } else {
            $sPrj = $this->db->prepare("
                SELECT p.id, p.name, p.project_status,
                       COALESCE(l.name,'') AS location_name,
                       sd.id AS design_id, sd.designer_type, sd.designer_name, sd.design_link
                FROM projects p
                LEFT JOIN locations l ON l.id = p.location_id
                LEFT JOIN project_structural_designs sd ON sd.project_id = p.id
                WHERE p.deleted_at IS NULL AND " . $this->getProjectRoleFilter('p') . " AND p.struct_engineer_id = ?
                ORDER BY p.id DESC");
            $sPrj->bind_param('i', $userId);
            $sPrj->execute();
            $rProjs = $sPrj->get_result();
        }
        $myProjects = $rProjs ? $rProjs->fetch_all(MYSQLI_ASSOC) : [];

        // ── Architectural Designs (scoped) ──────────────────────────────────
        if ($isAdmin) {
            $rArch = $this->db->query("
                SELECT ae.*, p.name AS project_name,
                       COALESCE(l.name,'') AS location_name,
                       p.project_status,
                       u.full_name AS architect_name
                FROM architect_engineering ae
                LEFT JOIN projects  p ON p.id = ae.project_id
                LEFT JOIN locations l ON l.id = p.location_id
                LEFT JOIN users u ON u.id = ae.user_id
                WHERE p.deleted_at IS NULL
                ORDER BY ae.id DESC");
            $architecturalDesigns = $rArch ? $rArch->fetch_all(MYSQLI_ASSOC) : [];
        } else {
            $sArch = $this->db->prepare("
                SELECT ae.*, p.name AS project_name,
                       COALESCE(l.name,'') AS location_name,
                       p.project_status,
                       u.full_name AS architect_name
                FROM architect_engineering ae
                JOIN  projects  p ON p.id = ae.project_id
                LEFT JOIN locations l ON l.id = p.location_id
                LEFT JOIN users u ON u.id = ae.user_id
                WHERE p.deleted_at IS NULL AND p.struct_engineer_id = ?
                ORDER BY ae.id DESC");
            $sArch->bind_param('i', $userId);
            $sArch->execute();
            $architecturalDesigns = $sArch->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        // ── Projects needing design (scoped) ─────────────────────────────────
        $projectsNeedingDesign = array_filter($myProjects, fn($p) => empty($p['design_id']));

        return $this->view('phase2/dashboard/project-structural-design-dashboard', [
            'current_route'         => 'project-structural-design-dashboard',
            'isAdmin'               => $isAdmin,
            'myProjectsCount'       => $myProjectsCount,
            'totalDesigns'          => $totalDesigns,
            'projectsWithDesign'    => $projectsWithDesign,
            'projectsNoDesign'      => $projectsNoDesign,
            'internalDesigners'     => $internalDesigners,
            'externalDesigners'     => $externalDesigners,
            'recentDesigns'         => $recentDesigns,
            'myProjects'            => $myProjects,
            'projectsNeedingDesign' => array_values($projectsNeedingDesign),
            'architecturalDesigns'  => $architecturalDesigns,
        ]);
    }

    // =========================================================================
    // PURCHASE EXECUTIVE DASHBOARD (Role ID 23)
    // =========================================================================

    public function purchaseExecutiveDashboard()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);

        // Total Assigned Requests
        $r1 = $this->db->prepare("SELECT COUNT(*) AS c FROM purchase_requests WHERE assigned_executive_id = ?");
        $r1->bind_param('i', $userId);
        $r1->execute();
        $totalAssigned = (int)($r1->get_result()->fetch_assoc()['c'] ?? 0);

        // Completed CST (has an entry in quotation_comparisons)
        $r2 = $this->db->prepare("
            SELECT COUNT(*) AS c
            FROM purchase_requests pr
            JOIN quotation_comparisons qc ON qc.request_id = pr.id
            WHERE pr.assigned_executive_id = ?
        ");
        $r2->bind_param('i', $userId);
        $r2->execute();
        $completedCst = (int)($r2->get_result()->fetch_assoc()['c'] ?? 0);

        $pendingCst = max(0, $totalAssigned - $completedCst);

        // Total quotations submitted by this executive
        $r3 = $this->db->prepare("SELECT COUNT(*) AS c FROM quotations WHERE created_by = ?");
        $r3->bind_param('i', $userId);
        $r3->execute();
        $totalQuotations = (int)($r3->get_result()->fetch_assoc()['c'] ?? 0);

        // Recent Assigned Requests (for dashboard table)
        $r4 = $this->db->prepare("
            SELECT pr.*, p.name AS project_name, u.full_name AS created_by_name,
                   (SELECT COUNT(*) FROM purchase_request_items WHERE request_id = pr.id) AS item_count,
                   (SELECT COUNT(*) FROM quotations q WHERE q.request_id = pr.id AND q.created_by = ?) AS quote_count,
                   (SELECT COUNT(*) FROM quotation_comparisons qc WHERE qc.request_id = pr.id) AS has_cst
            FROM purchase_requests pr
            LEFT JOIN projects p ON p.id = pr.project_id
            LEFT JOIN users u ON u.id = pr.created_by
            WHERE pr.assigned_executive_id = ?
            ORDER BY pr.id DESC
            LIMIT 15
        ");
        $r4->bind_param('ii', $userId, $userId);
        $r4->execute();
        $recentAssigned = $r4->get_result()->fetch_all(MYSQLI_ASSOC);

        // Projects I am working on
        $r5 = $this->db->prepare("
            SELECT DISTINCT p.id, p.name,
                   COUNT(pr.id) AS request_count
            FROM purchase_requests pr
            JOIN projects p ON p.id = pr.project_id
            WHERE pr.assigned_executive_id = ?
            GROUP BY p.id, p.name
            ORDER BY request_count DESC
        ");
        $r5->bind_param('i', $userId);
        $r5->execute();
        $myProjects = $r5->get_result()->fetch_all(MYSQLI_ASSOC);

        // BOQ Notifications: projects assigned to this executive that have BOQs
        // A project is "assigned" when projects.purchase_user_id = $userId
        $r6 = $this->db->prepare("
            SELECT
                p.id        AS project_id,
                p.name      AS project_name,
                bm.id       AS boq_id,
                bm.version  AS boq_version,
                bm.status   AS boq_status,
                bm.created_at AS boq_created_at,
                u.full_name AS boq_created_by
            FROM projects p
            JOIN boq_master bm ON bm.project_id = p.id
            LEFT JOIN users u ON u.id = bm.created_by
            WHERE p.purchase_user_id = ?
              AND p.deleted_at IS NULL
            ORDER BY bm.created_at DESC
        ");
        $r6->bind_param('i', $userId);
        $r6->execute();
        $boqNotifications = $r6->get_result()->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/dashboard/purchase-executive-dashboard', [
            'current_route'    => 'con-dashboard',
            'page_title'       => 'My Purchase Dashboard',
            'totalAssigned'    => $totalAssigned,
            'completedCst'     => $completedCst,
            'pendingCst'       => $pendingCst,
            'totalQuotations'  => $totalQuotations,
            'recentAssigned'   => $recentAssigned,
            'myProjects'       => $myProjects,
            'boqNotifications' => $boqNotifications,
        ]);
    }

    // =========================================================================
    // PROJECT PROGRESS REPORT — Phase Hierarchy (Draft Plan)
    // Timeline: Phase → Phase Items (weight%) → Monthly Target actuals
    // =========================================================================
    public function projectProgressReport($projectId)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $projectId = (int)$projectId;

        // 1. Fetch project details
        $stmt = $this->db->prepare("SELECT * FROM projects WHERE id = ? AND deleted_at IS NULL");
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $project = $stmt->get_result()->fetch_assoc();
        if (!$project) {
            setError('Project not found.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        // 2. Fetch project timeline (start/end dates)
        $tStmt = $this->db->prepare("SELECT * FROM project_timelines WHERE project_id = ? LIMIT 1");
        $tStmt->bind_param('i', $projectId);
        $tStmt->execute();
        $timeline = $tStmt->get_result()->fetch_assoc() ?? [];

        // 3. Fetch ALL phases for this project (ordered)
        $pStmt = $this->db->prepare("
            SELECT pp.*
            FROM project_phases pp
            WHERE pp.project_id = ?
            ORDER BY pp.phase_order ASC
        ");
        $pStmt->bind_param('i', $projectId);
        $pStmt->execute();
        $phases = $pStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // 4. For each phase, fetch phase items + their actual progress from monthly targets
        $totalProjectProgress = 0.0;
        $phaseData = [];

        foreach ($phases as &$phase) {
            $phaseId     = (int)$phase['id'];
            $phaseWeight = (float)$phase['weight_percentage'];

            // Fetch phase sub-items
            $iStmt = $this->db->prepare("
                SELECT ppi.*
                FROM project_phase_items ppi
                WHERE ppi.phase_id = ?
                ORDER BY ppi.item_order ASC
            ");
            $iStmt->bind_param('i', $phaseId);
            $iStmt->execute();
            $items = $iStmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // For each item: get cumulative actual from monthly target items
            $phaseActualPct = 0.0;
            $totalWeight    = 0.0;

            foreach ($items as &$item) {
                $itemId     = (int)$item['id'];
                $itemWeight = (float)$item['weight_pct'];

                // Sum actual_progress_pct from phase_monthly_target_items linked to this phase_item
                $actStmt = $this->db->prepare("
                    SELECT COALESCE(AVG(pmti.actual_progress_pct), 0) AS avg_actual,
                           COALESCE(SUM(pmti.actual_qty), 0)          AS total_actual_qty,
                           COALESCE(SUM(pmti.monthly_target_qty), 0)  AS total_target_qty,
                           COUNT(pmti.id)                              AS month_count
                    FROM phase_monthly_target_items pmti
                    INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
                    WHERE pmt.phase_id = ? AND pmti.work_item_name LIKE ?
                ");
                $nameLike = '%' . $item['item_name'] . '%';
                $actStmt->bind_param('is', $phaseId, $nameLike);
                $actStmt->execute();
                $actRow = $actStmt->get_result()->fetch_assoc();

                // Use existing actual_progress if monthly target has data, else use ppi.actual_progress
                $monthlyActual = (float)($actRow['avg_actual'] ?? 0);
                $existingActual = (float)($item['actual_progress'] ?? 0);

                // Use the higher of the two (monthly target data takes priority if available)
                $itemActual = ($actRow['month_count'] > 0) ? $monthlyActual : $existingActual;
                $itemActual = min(100, $itemActual);

                $item['computed_actual'] = $itemActual;
                $item['monthly_actual_qty'] = (float)($actRow['total_actual_qty'] ?? 0);
                $item['monthly_target_qty'] = (float)($actRow['total_target_qty'] ?? 0);
                $item['monthly_count'] = (int)($actRow['month_count'] ?? 0);

                // Weighted contribution to phase
                $phaseActualPct += ($itemActual * $itemWeight / 100);
                $totalWeight    += $itemWeight;

                // Recovery flag: item has target but no progress
                $item['needs_recovery'] = ($itemActual < 50 && $item['monthly_count'] > 0);
            }
            unset($item);

            $phaseActualPct = min(100, $phaseActualPct);
            $phase['items'] = $items;
            $phase['computed_actual'] = $phaseActualPct;

            // Phase contribution to project
            $phaseContrib = ($phaseActualPct / 100) * $phaseWeight;
            $phase['contribution'] = $phaseContrib;
            $totalProjectProgress += $phaseContrib;

            // Monthly targets count for this phase
            $mtCount = $this->db->prepare("SELECT COUNT(*) AS c FROM phase_monthly_targets WHERE phase_id = ?");
            $mtCount->bind_param('i', $phaseId);
            $mtCount->execute();
            $phase['monthly_target_count'] = (int)($mtCount->get_result()->fetch_assoc()['c'] ?? 0);
        }
        unset($phase);

        $totalProjectProgress = min(100, $totalProjectProgress);

        $this->view('phase2/dashboard/project-progress-report', [
            'current_route'        => 'con-dashboard',
            'page_title'           => 'Project Progress — ' . $project['name'],
            'project'              => $project,
            'timeline'             => $timeline,
            'phases'               => $phases,
            'totalProjectProgress' => $totalProjectProgress,
        ]);
    }
}
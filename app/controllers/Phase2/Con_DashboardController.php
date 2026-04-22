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

        $stats      = $this->getDashboardStats();
        $projects   = $this->getProjectsWithProgress();
        $budgetUtil = $this->getBudgetUtilization();
        $stockAlerts   = $this->getLowStockItems();
        $pendingAlerts = $this->getPendingApprovalsList();
        $soilAlerts    = $this->getRecentSoilTests();

        return $this->view('phase2/dashboard/cons-dashboard', [
            'current_route' => 'con-dashboard',
            'stats'         => $stats,
            'projects'      => $projects,
            'budgetUtil'    => $budgetUtil,
            'stockAlerts'   => $stockAlerts,
            'pendingAlerts' => $pendingAlerts,
            'soilAlerts'    => $soilAlerts,
        ]);
    }

    // ── KPI aggregates ──────────────────────────────────────────────────────

    private function getDashboardStats(): array
    {
        $s = [];

        // 1. Total Projects
        $r = $this->db->query("SELECT COUNT(*) AS c FROM projects WHERE deleted_at IS NULL");
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
                SELECT id FROM contractor_bill_requests    WHERE status = 'approved'
                UNION ALL
                SELECT id FROM operational_fund_requests   WHERE status = 'approved'
                UNION ALL
                SELECT id FROM vendor_payment_requests     WHERE status IN ('Submitted','Approved')
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
                SELECT project_id, AVG(gain_percentage) AS progress_pct
                FROM target_fixed GROUP BY project_id
            ) prog ON prog.project_id = p.id
            LEFT JOIN (
                SELECT project_id, SUM(total_estimated_cost) AS total_budget
                FROM initial_estimate_budgets WHERE status = 'approved' GROUP BY project_id
            ) bud ON bud.project_id = p.id
            LEFT JOIN (
                SELECT project_id, SUM(amount_paid) AS total_spent
                FROM con_financial_transactions GROUP BY project_id
            ) spent ON spent.project_id = p.id
            WHERE p.deleted_at IS NULL
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
                   created_at
            FROM contractor_bill_requests WHERE status = 'approved'
            UNION ALL
            SELECT 'Fund Request',
                   CONCAT('Fund: ', purpose),
                   COALESCE(approved_amount, amount),
                   project_id, created_at
            FROM operational_fund_requests WHERE status = 'approved'
            UNION ALL
            SELECT 'Vendor Payment',
                   CONCAT(payment_type, ' Payment'),
                   COALESCE(net_payable, 0),
                   project_id, created_at
            FROM vendor_payment_requests WHERE status IN ('Submitted','Approved')
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

        $sql = "
            SELECT  p.id,
                    p.name,
                    p.project_status,
                    p.building_type,
                    p.total_share        AS total_floors,
                    p.plot_size,
                    p.plot_no,
                    p.block_no,
                    COALESCE(l.name,'')  AS location_name,
                    COALESCE(prog.progress_pct, 0)    AS progress_pct,
                    COALESCE(bud.total_budget, 0)      AS total_budget,
                    COALESCE(spent.total_spent, 0)     AS total_spent,
                    bud.start_date,
                    bud.end_date
            FROM projects p
            LEFT JOIN locations l ON l.id = p.location_id
            LEFT JOIN (
                SELECT project_id, AVG(gain_percentage) AS progress_pct
                FROM target_fixed GROUP BY project_id
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
            WHERE p.deleted_at IS NULL
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
            'current_route' => 'con-dashboard',
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

        // Progress % = AVG gain from target_fixed
        $stmt3 = $this->db->prepare("SELECT COALESCE(AVG(gain_percentage),0) AS prog FROM target_fixed WHERE project_id = ?");
        $stmt3->bind_param('i', $id);
        $stmt3->execute();
        $progress = round((float)$stmt3->get_result()->fetch_assoc()['prog'], 1);

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
            SELECT w.*, dt.name AS design_type_name,
                   ieb.total_estimated_cost AS budget_total
            FROM   project_working_plans w
            LEFT JOIN design_types             dt  ON dt.id  = w.design_type_id
            LEFT JOIN initial_estimate_budgets ieb ON ieb.id = w.initial_budget_id
            WHERE  w.project_id = ?
            ORDER  BY w.year ASC, w.month ASC, dt.name ASC");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $plans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $grouped = [];
        $monthNames = ['', 'January','February','March','April','May','June',
                           'July','August','September','October','November','December'];
        foreach ($plans as $p) {
            $key = $p['year'] . '-' . str_pad($p['month'], 2, '0', STR_PAD_LEFT);
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'label' => $monthNames[(int)$p['month']] . ' ' . $p['year'],
                    'year'  => $p['year'],
                    'month' => $p['month'],
                    'plans' => []
                ];
            }
            $grouped[$key]['plans'][] = $p;
        }

        echo json_encode(['success' => true, 'data' => array_values($grouped)]);
        exit;
    }

    // =========================================================================
    // AJAX — Tab 6: Project Targets
    // =========================================================================

    public function ajaxTargets($id)
    {
        header('Content-Type: application/json');
        $id = (int)$id;

        $stmt = $this->db->prepare("
            SELECT tf.*, wp.work_description AS plan_description,
                   dt.name AS design_type_name, u.full_name AS created_by_name
            FROM   target_fixed tf
            LEFT JOIN project_working_plans wp ON wp.id = tf.work_plan_id
            LEFT JOIN design_types          dt ON dt.id = wp.design_type_id
            LEFT JOIN users                 u  ON u.id  = tf.user_id
            WHERE  tf.project_id = ?
            ORDER  BY tf.year DESC, tf.month DESC, tf.week_number ASC");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $targets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['success' => true, 'data' => $targets]);
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

        // Get all target entries for this project
        $stmt = $this->db->prepare("
            SELECT tf.*, wp.work_description AS plan_description,
                   dt.name AS design_type_name, u.full_name AS created_by_name
            FROM   target_fixed tf
            LEFT JOIN project_working_plans wp ON wp.id = tf.work_plan_id
            LEFT JOIN design_types          dt ON dt.id = wp.design_type_id
            LEFT JOIN users                 u  ON u.id  = tf.user_id
            WHERE  tf.project_id = ?
            ORDER  BY tf.year ASC, tf.month ASC, tf.week_number ASC");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $allTargets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Build structure: monthKey → weekNum → targets[]
        $structure = [];
        foreach ($allTargets as $t) {
            $mk = $t['year'] . '-' . str_pad($t['month'], 2, '0', STR_PAD_LEFT);
            $wk = (int)$t['week_number'];
            $structure[$mk][$wk][] = $t;
        }

        $result = [];
        foreach ($structure as $mk => $weeks) {
            [$yr, $mo] = explode('-', $mk);
            $yr = (int)$yr;
            $mo = (int)$mo;
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $mo, $yr);

            $monthEntry = [
                'month_key'   => $mk,
                'month_label' => $monthNames[$mo] . ' ' . $yr,
                'year'        => $yr,
                'month'       => $mo,
                'weeks'       => [],
            ];

            foreach ($weeks as $wNum => $targets) {
                // Week date bounds
                $dayStart = ($wNum - 1) * 7 + 1;
                $dayEnd   = ($wNum === 4) ? $daysInMonth : $wNum * 7;
                $dateFrom = sprintf('%04d-%02d-%02d', $yr, $mo, $dayStart);
                $dateTo   = sprintf('%04d-%02d-%02d', $yr, $mo, $dayEnd);

                // DPR entries for this week
                $s2 = $this->db->prepare("
                    SELECT dpr.*, se.full_name AS engineer_name
                    FROM   daily_progress_reports dpr
                    LEFT JOIN users se ON se.id = dpr.site_engineer_id
                    WHERE  dpr.project_id = ? AND dpr.report_date BETWEEN ? AND ?
                    ORDER  BY dpr.report_date ASC");
                $s2->bind_param('iss', $id, $dateFrom, $dateTo);
                $s2->execute();
                $dprs = $s2->get_result()->fetch_all(MYSQLI_ASSOC);

                // Financial transactions for this week
                $s3 = $this->db->prepare("
                    SELECT cft.*, u.full_name AS processor_name
                    FROM   con_financial_transactions cft
                    LEFT JOIN users u ON u.id = cft.processed_by
                    WHERE  cft.project_id = ? AND DATE(cft.transaction_date) BETWEEN ? AND ?
                    ORDER  BY cft.transaction_date ASC");
                $s3->bind_param('iss', $id, $dateFrom, $dateTo);
                $s3->execute();
                $transactions = $s3->get_result()->fetch_all(MYSQLI_ASSOC);

                $monthEntry['weeks'][] = [
                    'week_num'     => $wNum,
                    'week_label'   => 'Week ' . $wNum,
                    'date_from'    => $dateFrom,
                    'date_to'      => $dateTo,
                    'targets'      => $targets,
                    'dprs'         => $dprs,
                    'transactions' => $transactions,
                ];
            }

            $result[] = $monthEntry;
        }

        echo json_encode(['success' => true, 'data' => $result]);
        exit;
    }

    // =========================================================================
    // QC DASHBOARD  (Quality Controller)
    // Permissions: Estimate Budget, Project Target Set, Material Issue,
    //              Purchase order, Working Plan, Project Report
    // =========================================================================
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
        $r4 = $this->db->query("SELECT COUNT(*) AS c FROM daily_progress_reports");
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
            LEFT JOIN (SELECT project_id, AVG(gain_percentage) AS progress_pct FROM target_fixed GROUP BY project_id) prog ON prog.project_id=p.id
            LEFT JOIN (SELECT project_id, SUM(total_estimated_cost) AS total_budget FROM initial_estimate_budgets WHERE status='approved' GROUP BY project_id) bud ON bud.project_id=p.id
            WHERE p.deleted_at IS NULL ORDER BY p.id DESC LIMIT 6");
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
            FROM daily_progress_reports dpr
            LEFT JOIN projects p ON p.id=dpr.project_id
            ORDER BY dpr.id DESC LIMIT 5");
        $recentDPRs = $rDPR ? $rDPR->fetch_all(MYSQLI_ASSOC) : [];

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

        return $this->view('phase2/dashboard/purchase-team-lead-dashboard', [
            'current_route'   => 'purchase-team-lead-dashboard',
            'totalPOs'        => $totalPOs,
            'totalVendors'    => $totalVendors,
            'totalPenalties'  => $totalPenalties,
            'totalIssues'     => $totalIssues,
            'totalMaterials'  => $totalMaterials,
            'pendingPayments' => $pendingPayments,
            'recentVendors'   => $recentVendors,
            'recentPOs'       => $recentPOs,
            'stockOverview'   => $stockOverview,
        ]);
    }

    // =========================================================================
    // DRAFTSMAN DASHBOARD
    // Permissions: Draft Design (Project Drawings, Design Types, etc.)
    // =========================================================================
    public function draftDashboard()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

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

        // Projects needing drawings (no drawings yet)
        $rNeedDraw = $this->db->query("
            SELECT p.id, p.name, p.project_status, COALESCE(l.name,'') AS location_name
            FROM projects p
            LEFT JOIN locations l ON l.id=p.location_id
            LEFT JOIN project_drawings pd ON pd.project_id=p.id
            WHERE p.deleted_at IS NULL AND pd.id IS NULL
            ORDER BY p.id DESC LIMIT 5");
        $projectsNeedingDrawings = $rNeedDraw ? $rNeedDraw->fetch_all(MYSQLI_ASSOC) : [];

        return $this->view('phase2/dashboard/draft-dashboard', [
            'current_route'          => 'draft-dashboard',
            'totalDrawings'          => $totalDrawings,
            'totalDesignTypes'       => $totalDesignTypes,
            'projectsWithDrawings'   => $projectsWithDrawings,
            'totalAE'                => $totalAE,
            'drawingsByType'         => $drawingsByType,
            'recentDrawings'         => $recentDrawings,
            'projectsNeedingDrawings'=> $projectsNeedingDrawings,
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
        $r2 = $this->db->query("SELECT COUNT(*) AS c FROM projects WHERE deleted_at IS NULL");
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
            WHERE p.deleted_at IS NULL AND lvs.id IS NULL
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
    // Permissions: Purchase order
    // =========================================================================
    public function projectArchitectureDashboard()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // KPI: Total Purchase Orders
        $r1 = $this->db->query("SELECT COUNT(*) AS c FROM purchase_orders");
        $totalPOs = $r1 ? (int)$r1->fetch_assoc()['c'] : 0;

        // KPI: Pending Purchase Requests
        $r2 = $this->db->query("SELECT COUNT(*) AS c FROM purchase_requests WHERE status='Pending'");
        $pendingPRs = $r2 ? (int)$r2->fetch_assoc()['c'] : 0;

        // KPI: Approved Purchase Requests
        $r3 = $this->db->query("SELECT COUNT(*) AS c FROM purchase_requests WHERE status='Approved'");
        $approvedPRs = $r3 ? (int)$r3->fetch_assoc()['c'] : 0;

        // KPI: Total spend on POs (sum from line items)
        $r4 = $this->db->query("SELECT COALESCE(SUM(total_price),0) AS total FROM purchase_order_items");
        $totalPOValue = $r4 ? (float)$r4->fetch_assoc()['total'] : 0.0;

        // Recent Purchase Orders (vendor from purchase_order_items.selected_vendor_id)
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
            ORDER BY po.id DESC LIMIT 8");
        $recentPOs = $rPO ? $rPO->fetch_all(MYSQLI_ASSOC) : [];

        // Purchase Requests breakdown by status
        $rStatus = $this->db->query("
            SELECT status, COUNT(*) AS cnt FROM purchase_requests GROUP BY status");
        $statusBreakdown = $rStatus ? $rStatus->fetch_all(MYSQLI_ASSOC) : [];

        return $this->view('phase2/dashboard/project-architecture-dashboard', [
            'current_route'   => 'project-architecture-dashboard',
            'totalPOs'        => $totalPOs,
            'pendingPRs'      => $pendingPRs,
            'approvedPRs'     => $approvedPRs,
            'totalPOValue'    => $totalPOValue,
            'recentPOs'       => $recentPOs,
            'statusBreakdown' => $statusBreakdown,
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
            WHERE p.deleted_at IS NULL AND b.id IS NULL
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
        ]);
    }
}

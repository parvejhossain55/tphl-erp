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
}

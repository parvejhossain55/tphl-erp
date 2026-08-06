<?php
namespace Phase2;

use Controller;

class Con_ProjectOverviewDashboardController extends Controller
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit;
        }

        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        if (!in_array($roleId, [3, 15, 22, 12, 20, 32])) {
            header("Location: " . $GLOBALS['base_url'] . "/home");
            exit;
        }
    }

    private function getProjectRoleFilter($alias = 'p')
    {
        $userId = $_SESSION['user']['id'] ?? 0;
        $roleId = $_SESSION['user']['role_id'] ?? 0;
        $baseFilter = "{$alias}.under_construction = 1";
        if (in_array($roleId, [1, 3, 20, 22, 32])) return $baseFilter;
        return "{$baseFilter} AND ({$alias}.pm_id = {$userId} OR {$alias}.site_engineer_id = {$userId} OR {$alias}.landman_id = {$userId} OR {$alias}.struct_engineer_id = {$userId} OR {$alias}.operation_head_id = {$userId} OR {$alias}.draftsman_id = {$userId} OR {$alias}.architect_id = {$userId} OR {$alias}.estimator_id = {$userId} OR {$alias}.qc_id = {$userId} OR {$alias}.operator_id = {$userId} OR {$alias}.created_by = {$userId})";
    }

    public function index()
    {
        $userRole = (int)($_SESSION['user']['role_id'] ?? 0);
        $userId = $_SESSION['user']['id'] ?? 0;

        // Reset active project context when returning to global dashboard
        unset($_SESSION['active_project_id']);
        unset($_SESSION['active_project_name']);
        unset($_SESSION['active_project_workflow_version']);

        // AJAX: Get Monthly Targets and Timeline Years for a project
        if (isset($_GET['ajax_get_targets'])) {
            header('Content-Type: application/json');
            $pid = (int)$_GET['project_id'];
            
            $targets = [];
            $years = [];
            $budgetCategoryData = [];
            $totalBudgetUsed = 0.0;
            
            if ($pid > 0) {
                $sql = "
                    SELECT pmt.id, pmt.target_month, pmt.target_year, pp.phase_name
                    FROM phase_monthly_targets pmt
                    JOIN project_phases pp ON pp.id = pmt.phase_id
                    WHERE pmt.project_id = ?
                    ORDER BY pmt.target_year ASC, pmt.target_month_number ASC
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('i', $pid);
                $stmt->execute();
                $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                foreach ($res as $row) {
                    $monthLabel = date('M Y', strtotime($row['target_month']));
                    $targets[] = [
                        'id' => $row['id'],
                        'label' => $monthLabel . ' — ' . $row['phase_name'],
                        'phase_name' => $row['phase_name'],
                        'year' => $row['target_year']
                    ];
                }

                // Fetch construction years from project_timelines
                $startYear = (int)date('Y');
                $endYear = (int)date('Y');
                
                $tlSql = "
                    SELECT 
                        YEAR(COALESCE(project_start_date, construction_start_date, NOW())) AS start_year,
                        YEAR(COALESCE(expected_handover_date, construction_end_date, NOW())) AS end_year
                    FROM project_timelines
                    WHERE project_id = ?
                    LIMIT 1
                ";
                $tlStmt = $this->db->prepare($tlSql);
                if ($tlStmt) {
                    $tlStmt->bind_param('i', $pid);
                    $tlStmt->execute();
                    $tlRes = $tlStmt->get_result()->fetch_assoc();
                    if ($tlRes) {
                        $startYear = (int)$tlRes['start_year'];
                        $endYear = (int)$tlRes['end_year'];
                        if ($startYear <= 1970) $startYear = (int)date('Y');
                        if ($endYear <= 1970) $endYear = (int)date('Y');
                        if ($endYear < $startYear) $endYear = $startYear;
                    }
                }
                
                for ($y = $startYear; $y <= $endYear; $y++) {
                    $years[] = $y;
                }
                
                $budgetCategoryData = $this->getProjectBudgetCategoryData($pid);
                
                // Specific project budget used
                $matRes = $this->db->query("
                    SELECT COALESCE(SUM(mri.received_quantity * poi.unit_price), 0) AS total
                    FROM material_receiving_items mri
                    INNER JOIN material_receiving mr ON mr.id = mri.receiving_id
                    INNER JOIN purchase_order_items poi ON poi.id = mri.purchase_order_item_id
                    INNER JOIN purchase_orders po ON po.id = mr.purchase_order_id
                    INNER JOIN purchase_requests pr ON pr.id = po.request_id
                    WHERE mr.overall_status = 'Accepted' AND pr.project_id = $pid
                ");
                $matCost = $matRes ? (float)$matRes->fetch_assoc()['total'] : 0.0;

                $conRes = $this->db->query("
                    SELECT COALESCE(SUM(amount_paid), 0) AS total
                    FROM con_financial_transactions
                    WHERE ref_type = 'contractor_bill' AND update_status = 'Completed' AND project_id = $pid
                ");
                $conCost = $conRes ? (float)$conRes->fetch_assoc()['total'] : 0.0;

                $totalBudgetUsed = $matCost + $conCost;
            } else {
                $roleFilter = $this->getProjectRoleFilter('p');
                $budgetCategoryData = $this->getPortfolioBudgetCategoryData($roleFilter);
                
                // Portfolio-wide budget used
                $matRes = $this->db->query("
                    SELECT COALESCE(SUM(mri.received_quantity * poi.unit_price), 0) AS total
                    FROM material_receiving_items mri
                    INNER JOIN material_receiving mr ON mr.id = mri.receiving_id
                    INNER JOIN purchase_order_items poi ON poi.id = mri.purchase_order_item_id
                    INNER JOIN purchase_orders po ON po.id = mr.purchase_order_id
                    INNER JOIN purchase_requests pr ON pr.id = po.request_id
                    INNER JOIN projects p ON p.id = pr.project_id
                    WHERE mr.overall_status = 'Accepted' AND p.deleted_at IS NULL AND {$roleFilter}
                ");
                $matCost = $matRes ? (float)$matRes->fetch_assoc()['total'] : 0.0;

                $conRes = $this->db->query("
                    SELECT COALESCE(SUM(txn.amount_paid), 0) AS total
                    FROM con_financial_transactions txn
                    INNER JOIN projects p ON p.id = txn.project_id
                    WHERE txn.ref_type = 'contractor_bill' AND txn.update_status = 'Completed' AND p.deleted_at IS NULL AND {$roleFilter}
                ");
                $conCost = $conRes ? (float)$conRes->fetch_assoc()['total'] : 0.0;

                $totalBudgetUsed = $matCost + $conCost;
                
                // Portfolio-wide years
                $allYearsSql = "
                    SELECT 
                        MIN(YEAR(COALESCE(project_start_date, construction_start_date))) AS min_year,
                        MAX(YEAR(COALESCE(expected_handover_date, construction_end_date))) AS max_year
                    FROM project_timelines
                ";
                $allYearsRes = $this->db->query($allYearsSql);
                $allYearsRow = $allYearsRes ? $allYearsRes->fetch_assoc() : null;
                $startYear = $allYearsRow && $allYearsRow['min_year'] ? (int)$allYearsRow['min_year'] : (int)date('Y') - 2;
                $endYear = $allYearsRow && $allYearsRow['max_year'] ? (int)$allYearsRow['max_year'] : (int)date('Y') + 2;
                if ($startYear <= 1970) $startYear = (int)date('Y') - 2;
                if ($endYear <= 1970) $endYear = (int)date('Y') + 2;
                if ($endYear < $startYear) $endYear = $startYear;
                
                for ($y = $startYear; $y <= $endYear; $y++) {
                    $years[] = $y;
                }
            }

            echo json_encode([
                'targets' => $targets,
                'years' => $years,
                'budgetCategoryData' => $budgetCategoryData,
                'totalBudgetUsed' => $totalBudgetUsed
            ]);
            exit;
        }

        // AJAX: Get dynamic progress trend data (Monthly, Yearly, All-Time)
        if (isset($_GET['ajax_get_trend_data'])) {
            header('Content-Type: application/json');
            $pid = (int)($_GET['project_id'] ?? 0);
            $mode = trim($_GET['mode'] ?? 'monthly');
            $year = (int)($_GET['year'] ?? date('Y'));
            $tid = (int)($_GET['target_id'] ?? 0);
            
            if ($mode === 'monthly') {
                // If target_id is provided, show week 1 to 4 of that target normalized to 100% of the target
                if ($tid > 0) {
                    // 1. Get phase and year
                    $pSql = "
                        SELECT pmt.target_year, pp.phase_name, pmt.target_month
                        FROM phase_monthly_targets pmt
                        JOIN project_phases pp ON pp.id = pmt.phase_id
                        WHERE pmt.id = ?
                    ";
                    $pStmt = $this->db->prepare($pSql);
                    $pStmt->bind_param('i', $tid);
                    $pStmt->execute();
                    $meta = $pStmt->get_result()->fetch_assoc();
                    $phaseName = $meta['phase_name'] ?? 'N/A';
                    $targetYear = $meta['target_year'] ?? 'N/A';
                    
                    // 2. Get target items
                    $itemsSql = "
                        SELECT 
                            pmti.id AS target_item_id,
                            pmti.monthly_target_qty,
                            pmti.week_1_qty,
                            pmti.week_2_qty,
                            pmti.week_3_qty,
                            pmti.week_4_qty
                        FROM phase_monthly_target_items pmti
                        WHERE pmti.target_id = ?
                    ";
                    $itemsStmt = $this->db->prepare($itemsSql);
                    $itemsStmt->bind_param('i', $tid);
                    $itemsStmt->execute();
                    $items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    
                    $totalTargetQty = 0.0;
                    foreach ($items as $item) {
                        $totalTargetQty += (float)$item['monthly_target_qty'];
                    }
                    if ($totalTargetQty <= 0) $totalTargetQty = 1.0;
                    
                    // 3. Get week-wise actual achieved quantities
                    $pwpSql = "
                        SELECT pwp.week_number, pwpi.target_item_id, pwpi.achieved_qty
                        FROM phase_weekly_progress_items pwpi
                        JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
                        WHERE pwp.target_id = ? AND (pwp.pm_status = 'Approved' OR pwp.entry_status = 'Approved')
                        ORDER BY pwp.week_number ASC
                    ";
                    $pwpStmt = $this->db->prepare($pwpSql);
                    $pwpStmt->bind_param('i', $tid);
                    $pwpStmt->execute();
                    $pwps = $pwpStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    
                    $maxApprovedWeek = 0;
                    foreach ($pwps as $pwp) {
                        if ((int)$pwp['week_number'] > $maxApprovedWeek) {
                            $maxApprovedWeek = (int)$pwp['week_number'];
                        }
                    }
                    
                    $itemActuals = [];
                    foreach ($pwps as $pwp) {
                        $itemActuals[(int)$pwp['target_item_id']][(int)$pwp['week_number']] = (float)$pwp['achieved_qty'];
                    }
                    
                    $w1Planned = 0.0; $w2Planned = 0.0; $w3Planned = 0.0; $w4Planned = 0.0;
                    $w1Actual = 0.0; $w2Actual = 0.0; $w3Actual = 0.0; $w4Actual = 0.0;
                    
                    foreach ($items as $item) {
                        $w1Planned += (float)$item['week_1_qty'];
                        $w2Planned += (float)$item['week_2_qty'];
                        $w3Planned += (float)$item['week_3_qty'];
                        $w4Planned += (float)$item['week_4_qty'];
                        
                        $w1Actual += (float)($itemActuals[$item['target_item_id']][1] ?? 0.0);
                        $w2Actual += (float)($itemActuals[$item['target_item_id']][2] ?? 0.0);
                        $w3Actual += (float)($itemActuals[$item['target_item_id']][3] ?? 0.0);
                        $w4Actual += (float)($itemActuals[$item['target_item_id']][4] ?? 0.0);
                    }
                    
                    $w1PlannedPct = ($w1Planned / $totalTargetQty) * 100.0;
                    $w2PlannedPct = ($w2Planned / $totalTargetQty) * 100.0;
                    $w3PlannedPct = ($w3Planned / $totalTargetQty) * 100.0;
                    $w4PlannedPct = ($w4Planned / $totalTargetQty) * 100.0;
                    
                    $targetMonthRep = date('Y-m-01', strtotime($meta['target_month']));
                    $currentMonthRep = date('Y-m-01');
                    $isFutureTarget = ($targetMonthRep > $currentMonthRep);
                    
                    if ($isFutureTarget) {
                        $w1Actual = null;
                        $w2Actual = null;
                        $w3Actual = null;
                        $w4Actual = null;
                    } else {
                        $w1Actual = ($w1Actual / $totalTargetQty) * 100.0;
                        $w2Actual = ($w2Actual / $totalTargetQty) * 100.0;
                        $w3Actual = ($w3Actual / $totalTargetQty) * 100.0;
                        $w4Actual = ($w4Actual / $totalTargetQty) * 100.0;
                    }
                    
                    echo json_encode([
                        'labels' => ["Week 1", "Week 2", "Week 3", "Week 4"],
                        'planned' => [round($w1PlannedPct, 1), round($w2PlannedPct, 1), round($w3PlannedPct, 1), round($w4PlannedPct, 1)],
                        'actual' => [
                            $w1Actual !== null ? round($w1Actual, 1) : null,
                            $w2Actual !== null ? round($w2Actual, 1) : null,
                            $w3Actual !== null ? round($w3Actual, 1) : null,
                            $w4Actual !== null ? round($w4Actual, 1) : null
                        ],
                        'title' => "Monthly Target Progress (Weekly Breakdown — 100% Scale)",
                        'phase_name' => $phaseName,
                        'year' => $targetYear
                    ]);
                    exit;
                } else {
                    // If no target selected, show monthly trend of past 6 months (default behavior)
                    $trendMonths = [];
                    $actualTrend = [];
                    $plannedTrend = [];
                    
                    $prevPlanned = 0.0;
                    $prevActual = 0.0;
                    
                    $preMonthDate = date('Y-m-t', strtotime("-6 months"));
                    $preProg = ($pid > 0) ? $this->getProgressAtDate($pid, $preMonthDate) : $this->getPortfolioProgressAtDate($preMonthDate);
                    $prevPlanned = $preProg['planned'];
                    $prevActual = $preProg['actual'];
                    
                    for ($i = 5; $i >= 0; $i--) {
                        $date = date('Y-m', strtotime("-$i months"));
                        $trendMonths[] = date('M Y', strtotime("-$i months"));
                        $lastDay = date('Y-m-t', strtotime("-$i months"));
                        
                        if ($pid > 0) {
                            $prog = $this->getProgressAtDate($pid, $lastDay);
                        } else {
                            $prog = $this->getPortfolioProgressAtDate($lastDay);
                        }
                        
                        $plannedTrend[] = round(max(0.0, $prog['planned'] - $prevPlanned), 4);
                        
                        // If it's a future month, actual is null
                        if ($lastDay > date('Y-m-d')) {
                            $actualTrend[] = null;
                        } else {
                            $actualTrend[] = round(max(0.0, $prog['actual'] - $prevActual), 4);
                        }
                        
                        $prevPlanned = $prog['planned'];
                        $prevActual = $prog['actual'];
                    }
                    
                    echo json_encode([
                        'labels' => $trendMonths,
                        'planned' => $plannedTrend,
                        'actual' => $actualTrend,
                        'title' => "Monthly Portfolio Progress (Overall Scale)"
                    ]);
                    exit;
                }
            } elseif ($mode === 'yearly') {
                // Shows Jan-Dec progress for the selected year scaled to the yearly target
                $labels = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                
                if ($pid > 0) {
                    $trend = $this->getYearlyProgressTrend($pid, $year);
                } else {
                    $trend = $this->getPortfolioYearlyProgressTrend($year);
                }
                
                echo json_encode([
                    'labels' => $labels,
                    'planned' => $trend['planned'],
                    'actual' => $trend['actual'],
                    'title' => "Yearly Progress Breakdown ($year — Yearly Target Scale)"
                ]);
                exit;
            } elseif ($mode === 'all_time') {
                $startYear = (int)date('Y') - 2;
                $endYear = (int)date('Y') + 2;
                
                if ($pid > 0) {
                    $tlSql = "
                        SELECT 
                            YEAR(COALESCE(project_start_date, construction_start_date, NOW())) AS start_year,
                            YEAR(COALESCE(expected_handover_date, construction_end_date, NOW())) AS end_year
                        FROM project_timelines
                        WHERE project_id = ?
                        LIMIT 1
                    ";
                    $tlStmt = $this->db->prepare($tlSql);
                    if ($tlStmt) {
                        $tlStmt->bind_param('i', $pid);
                        $tlStmt->execute();
                        $tlRes = $tlStmt->get_result()->fetch_assoc();
                        if ($tlRes) {
                            $startYear = (int)$tlRes['start_year'];
                            $endYear = (int)$tlRes['end_year'];
                            if ($startYear <= 1970) $startYear = (int)date('Y');
                            if ($endYear <= 1970) $endYear = (int)date('Y');
                            if ($endYear < $startYear) $endYear = $startYear;
                        }
                    }
                } else {
                    // Global range from all timelines
                    $allYearsSql = "
                        SELECT 
                            MIN(YEAR(COALESCE(project_start_date, construction_start_date))) AS min_year,
                            MAX(YEAR(COALESCE(expected_handover_date, construction_end_date))) AS max_year
                        FROM project_timelines
                    ";
                    $allYearsRes = $this->db->query($allYearsSql);
                    $allYearsRow = $allYearsRes ? $allYearsRes->fetch_assoc() : null;
                    if ($allYearsRow && $allYearsRow['min_year']) {
                        $startYear = (int)$allYearsRow['min_year'];
                        $endYear = (int)$allYearsRow['max_year'];
                        if ($startYear <= 1970) $startYear = (int)date('Y');
                        if ($endYear <= 1970) $endYear = (int)date('Y');
                        if ($endYear < $startYear) $endYear = $startYear;
                    }
                }
                
                $labels = [];
                $plannedTrend = [];
                $actualTrend = [];
                
                $currentYear = (int)date('Y');
                
                $prevPlanned = 0.0;
                $prevActual = 0.0;
                
                // Get progress for the year preceding startYear
                $preYearLastDay = ($startYear - 1) . "-12-31";
                $preProg = ($pid > 0) ? $this->getProgressAtDate($pid, $preYearLastDay) : $this->getPortfolioProgressAtDate($preYearLastDay);
                $prevPlanned = $preProg['planned'];
                $prevActual = $preProg['actual'];

                for ($y = $startYear; $y <= $endYear; $y++) {
                    $labels[] = (string)$y;
                    
                    $lastDay = "$y-12-31";
                    $prog = ($pid > 0) ? $this->getProgressAtDate($pid, $lastDay) : $this->getPortfolioProgressAtDate($lastDay);
                    
                    $yearlyPlanned = max(0.0, $prog['planned'] - $prevPlanned);
                    
                    if ($y > $currentYear) {
                        $yearlyActual = null;
                    } else {
                        $yearlyActual = max(0.0, $prog['actual'] - $prevActual);
                    }
                    
                    $plannedTrend[] = round($yearlyPlanned, 4);
                    $actualTrend[] = $yearlyActual !== null ? round($yearlyActual, 4) : null;
                    
                    $prevPlanned = $prog['planned'];
                    $prevActual = $prog['actual'];
                }
                
                echo json_encode([
                    'labels' => $labels,
                    'planned' => $plannedTrend,
                    'actual' => $actualTrend,
                    'title' => "All-Time Progress Trend (Yearly Breakdown — Overall Scale)"
                ]);
                exit;
            }
        }

        // Pagination parameters
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $perPage = 10;

        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $status = isset($_GET['status']) ? trim($_GET['status']) : '';
        $sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'name';

        // 1. Projects with Progress and Budget
        $roleFilter = $this->getProjectRoleFilter('p');
        $sql = "
            SELECT  p.id,
                    p.name,
                    p.image,
                    p.project_status,
                    p.building_type,
                    p.total_share        AS total_floors,
                    p.plot_size,
                    p.plot_no,
                    p.block_no,
                    p.workflow_version,
                    COALESCE(l.name,'')  AS location_name,
                    COALESCE(prog.progress_pct, 0)    AS progress_pct,
                    COALESCE(prog.planned_progress_pct, 0) AS planned_progress_pct,
                    COALESCE(bud.total_budget, 0)      AS total_budget,
                    COALESCE(spent.total_spent, 0)     AS total_spent,
                    timeline.expected_handover_date
            FROM projects p
            LEFT JOIN locations l ON l.id = p.location_id
            LEFT JOIN project_timelines timeline ON timeline.project_id = p.id
            LEFT JOIN (
                -- phase-based weighted progress calculated dynamically based on QC & PM approved daily logs
                SELECT 
                    pp.project_id,
                    SUM(
                        (
                            COALESCE(
                                (
                                    SELECT SUM(
                                        LEAST(1.0, (
                                            SELECT COALESCE(SUM(dwil.qty_done_today), 0)
                                            FROM daily_work_item_logs dwil
                                            INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
                                            WHERE dwil.target_item_id = pmti.id
                                              AND dpl.qc_status = 'Approved'
                                              AND dpl.pm_status = 'Approved'
                                        ) / NULLIF(pmti.monthly_target_qty, 0)) * pmti.planned_progress_pct
                                    )
                                    FROM phase_monthly_target_items pmti
                                    INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
                                    WHERE pmt.phase_id = pp.id
                                      AND (
                                          pmti.work_item_name = ppi.item_name COLLATE utf8mb4_general_ci
                                          OR pmti.work_item_name LIKE CONCAT(ppi.item_name COLLATE utf8mb4_general_ci, ' - %')
                                          OR pmti.work_item_name LIKE CONCAT(ppi.item_name COLLATE utf8mb4_general_ci, '%')
                                      )
                                ),
                                pp.actual_progress
                            ) / 100
                        ) * COALESCE(ppi.weight_pct, 100) / 100 * COALESCE(pp.weight_percentage, 0)
                    ) AS progress_pct,
                    
                    SUM(
                        (
                            COALESCE(
                                (
                                    SELECT SUM(pmti.planned_progress_pct)
                                    FROM phase_monthly_target_items pmti
                                    INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
                                    WHERE pmt.phase_id = pp.id
                                      AND (
                                          pmti.work_item_name = ppi.item_name COLLATE utf8mb4_general_ci
                                          OR pmti.work_item_name LIKE CONCAT(ppi.item_name COLLATE utf8mb4_general_ci, ' - %')
                                          OR pmti.work_item_name LIKE CONCAT(ppi.item_name COLLATE utf8mb4_general_ci, '%')
                                      )
                                ),
                                pp.planned_progress
                            ) / 100
                        ) * COALESCE(ppi.weight_pct, 100) / 100 * COALESCE(pp.weight_percentage, 0)
                    ) AS planned_progress_pct
                FROM project_phases pp
                LEFT JOIN project_phase_items ppi ON ppi.phase_id = pp.id
                GROUP BY pp.project_id
            ) prog ON prog.project_id = p.id
            LEFT JOIN (
                SELECT project_id,
                       SUM(total_estimated_cost) AS total_budget
                FROM initial_estimate_budgets WHERE status IN ('approved', 'pm_approved') GROUP BY project_id
            ) bud ON bud.project_id = p.id
            LEFT JOIN (
                SELECT project_id, SUM(amount_paid) AS total_spent
                FROM con_financial_transactions GROUP BY project_id
            ) spent ON spent.project_id = p.id
            WHERE p.deleted_at IS NULL AND {$roleFilter}
            ORDER BY p.id DESC";

        $result   = $this->db->query($sql);
        $projects = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        $projectIds = array_column($projects, 'id');
        $dynamicProgressMap = $this->calculatePhaseBasedProgress($projectIds);
        $budgetsAndSpentMap = $this->calculateProjectBudgetsAndSpent($projectIds);

        $allProjectsList = array_map(function($p) {
            return [
                'id' => $p['id'],
                'name' => $p['name']
            ];
        }, $projects);

        // Map status, active phases list, and calculate project status counts
        $totalProjectsCount = count($projects);
        $onTrackCount = 0;
        $atRiskCount = 0;
        $delayedCount = 0;
        $planningCount = 0;
        $completedCount = 0;

        $processedProjects = [];
        $totalActualProg = 0;
        $totalPlannedProg = 0;

        // Fetch open risks stats for all projects to determine status downgrades
        $projectRisksStats = [];
        $projectRisksDetails = [];
        if (!empty($projectIds)) {
            $riskIdsStr = implode(',', $projectIds);
            $riskQuery = $this->db->query("
                SELECT project_id, 
                       COUNT(*) AS total_open_risks,
                       SUM(CASE WHEN severity IN ('High', 'Critical') THEN 1 ELSE 0 END) AS critical_high_count
                FROM project_risks
                WHERE status = 'Open' AND project_id IN ($riskIdsStr)
                GROUP BY project_id
            ");
            if ($riskQuery) {
                while ($row = $riskQuery->fetch_assoc()) {
                    $projectRisksStats[(int)$row['project_id']] = [
                        'total_open' => (int)$row['total_open_risks'],
                        'critical_high' => (int)$row['critical_high_count']
                    ];
                }
            }

            $riskDetailsQuery = $this->db->query("
                SELECT project_id, risk_code, description, severity, status, missed_week
                FROM project_risks
                WHERE status = 'Open' AND project_id IN ($riskIdsStr)
                ORDER BY severity DESC, missed_week ASC
            ");
            if ($riskDetailsQuery) {
                while ($row = $riskDetailsQuery->fetch_assoc()) {
                    $projectRisksDetails[(int)$row['project_id']][] = $row;
                }
            }
        }

        foreach ($projects as $pr) {
            $pid = (int)$pr['id'];
            $actual = isset($dynamicProgressMap[$pid]) ? (float)$dynamicProgressMap[$pid]['actual'] : 0.0;
            $planned = isset($dynamicProgressMap[$pid]) ? (float)$dynamicProgressMap[$pid]['planned'] : 0.0;

            $pr['progress_pct'] = $actual;
            $pr['planned_progress_pct'] = $planned;
            $pr['total_budget'] = $budgetsAndSpentMap[$pid]['total_budget'] ?? 0.0;
            $pr['budget_id'] = $budgetsAndSpentMap[$pid]['budget_id'] ?? null;
            $pr['total_spent'] = $budgetsAndSpentMap[$pid]['total_spent'] ?? 0.0;

            $totalActualProg += $actual;
            $totalPlannedProg += $planned;

            // Determine status dynamically based on gap and active risks
            if ($pr['project_status'] === 'Completed') {
                $calcStatus = 'Completed';
                $completedCount++;
            } elseif ($pr['project_status'] === 'Planning' || ($actual == 0 && $planned == 0)) {
                $calcStatus = 'Planning';
                $planningCount++;
            } else {
                $gap = $planned - $actual;
                if ($gap <= 5) {
                    $calcStatus = 'On Track';
                } elseif ($gap <= 15) {
                    $calcStatus = 'At Risk';
                } else {
                    $calcStatus = 'Delayed';
                }

                // Downgrade status based on active risks
                $openRisks = $projectRisksStats[$pid] ?? ['total_open' => 0, 'critical_high' => 0];
                if ($openRisks['critical_high'] > 0) {
                    $calcStatus = 'Delayed';
                } elseif ($openRisks['total_open'] > 0) {
                    if ($calcStatus === 'On Track') {
                        $calcStatus = 'At Risk';
                    }
                }

                if ($calcStatus === 'On Track') {
                    $onTrackCount++;
                } elseif ($calcStatus === 'At Risk') {
                    $atRiskCount++;
                } else {
                    $calcStatus = 'Delayed'; // fallback/safeguard
                    $delayedCount++;
                }
            }

            // Calculate status explanation for popover on hover
            $statusExplanation = "";
            if ($calcStatus === 'On Track') {
                $statusExplanation = "<strong>ℹ️ প্রজেক্টটি সঠিক ট্র্যাকে রয়েছে:</strong><br/>";
                $statusExplanation .= "• প্রগ্রেস গ্যাপ ৫% এর কম এবং কোনো ক্রিটিক্যাল রিস্ক নেই।";
            } elseif ($calcStatus === 'Planning') {
                $statusExplanation = "<strong>ℹ️ প্রজেক্টটি প্ল্যানিং বা প্রি-কনস্ট্রাকশন ধাপে রয়েছে।</strong>";
            } elseif ($calcStatus === 'Completed') {
                $statusExplanation = "<strong>🎉 প্রজেক্টের কাজ সফলভাবে সম্পন্ন হয়েছে।</strong>";
            } else {
                $gap = $planned - $actual;
                $statusExplanation = $calcStatus === 'Delayed' 
                    ? "<strong>❌ প্রজেক্টটি বিলম্বে (Delayed) রয়েছে। কারণসমূহ:</strong><br/>" 
                    : "<strong>⚠️ প্রজেক্টটি ঝুঁকিতে (At Risk) রয়েছে। কারণসমূহ:</strong><br/>";

                $hasReasons = false;
                if ($gap > 5) {
                    $statusExplanation .= "• প্রগ্রেস ঘাটতি (Gap): " . number_format($gap, 1) . "% (পরিকল্পিত: " . number_format($planned, 1) . "%, প্রকৃত: " . number_format($actual, 1) . "%)<br/>";
                    $hasReasons = true;
                }

                $projRisks = $projectRisksDetails[$pid] ?? [];
                if (!empty($projRisks)) {
                    $nologCounts = [];
                    $otherRisks = [];
                    foreach ($projRisks as $risk) {
                        if (strpos($risk['risk_code'], 'AUTO-NOLOG-D') === 0) {
                            $wk = (int)$risk['missed_week'];
                            $nologCounts[$wk] = ($nologCounts[$wk] ?? 0) + 1;
                        } else {
                            $otherRisks[] = $risk;
                        }
                    }

                    // Format daily log warnings
                    foreach ($nologCounts as $wk => $cnt) {
                        $statusExplanation .= "• Week {$wk}: {$cnt}টি দিন কোনো Daily Work Log দেওয়া হয়নি।<br/>";
                        $hasReasons = true;
                    }

                    // Format other risks (e.g. target missed, manual risks)
                    foreach ($otherRisks as $risk) {
                        $sev = $risk['severity'];
                        $desc = $risk['description'];
                        $statusExplanation .= "• [{$sev}] {$desc}<br/>";
                        $hasReasons = true;
                    }
                }

                if (!$hasReasons) {
                    $statusExplanation .= "• প্রজেক্টের স্ট্যাটাস ম্যানুয়ালি বা অন্য কোনো কারণে আপডেট করা হয়েছে।";
                }
            }
            $pr['status_explanation'] = $statusExplanation;
            $pr['calculated_status'] = $calcStatus;

            // Check filters in PHP for pagination
            if ($search !== '') {
                $searchLower = strtolower($search);
                $nameMatch = strpos(strtolower($pr['name']), $searchLower) !== false;
                $locMatch = strpos(strtolower($pr['location_name'] ?? ''), $searchLower) !== false;
                if (!$nameMatch && !$locMatch) {
                    continue;
                }
            }
            if ($status !== '') {
                if ($pr['calculated_status'] !== $status) {
                    continue;
                }
            }

            $processedProjects[] = $pr;
        }

        $overallProgressPct = $totalProjectsCount > 0 ? ($totalActualProg / $totalProjectsCount) : 0.0;
        $overallPlannedProgressPct = $totalProjectsCount > 0 ? ($totalPlannedProg / $totalProjectsCount) : 0.0;
        $locationsCount = count(array_unique(array_filter(array_column($projects, 'location_name'))));
        if ($locationsCount === 0) $locationsCount = 1;

        // Sort projects in PHP
        if ($sort === 'name') {
            usort($processedProjects, function($a, $b) {
                return strcasecmp($a['name'], $b['name']);
            });
        } elseif ($sort === 'progress-desc') {
            usort($processedProjects, function($a, $b) {
                return (float)$b['progress_pct'] <=> (float)$a['progress_pct'];
            });
        } elseif ($sort === 'progress-asc') {
            usort($processedProjects, function($a, $b) {
                return (float)$a['progress_pct'] <=> (float)$b['progress_pct'];
            });
        } elseif ($sort === 'budget-desc') {
            usort($processedProjects, function($a, $b) {
                return (float)$b['total_budget'] <=> (float)$a['total_budget'];
            });
        }

        $filteredCount = count($processedProjects);
        $totalPages = ceil($filteredCount / $perPage);
        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;
        $pagedProjects = array_slice($processedProjects, $offset, $perPage);

        // Map Phases and Items using our dynamic progress map
        foreach ($pagedProjects as &$pr) {
            $pid = (int)$pr['id'];
            $pr['phases'] = $dynamicProgressMap[$pid]['phases'] ?? [];
        }
        unset($pr);

        // AJAX response payload
        if (isset($_GET['ajax'])) {
            header('Content-Type: application/json');
            
            // Capture rows HTML
            ob_start();
            $this->view('phase2/dashboard/overview-rows', [
                'projects' => $pagedProjects,
            ], false);
            $rows_html = ob_get_clean();

            // Capture pagination HTML
            ob_start();
            $this->view('phase2/dashboard/overview-pagination', [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalRecords' => $filteredCount,
                'perPage' => $perPage,
                'offset' => $offset,
            ], false);
            $pagination_html = ob_get_clean();

            echo json_encode([
                'rows_html' => $rows_html,
                'pagination_html' => $pagination_html,
                'total' => $filteredCount,
                'page' => $page,
                'pages' => $totalPages
            ]);
            exit;
        }

        $projects = $pagedProjects;

        // 3. approved budget total
        $abSql = "
            SELECT COALESCE(SUM(b.total_estimated_cost), 0) AS total
            FROM initial_estimate_budgets b
            JOIN projects p ON p.id = b.project_id
            WHERE b.status IN ('approved', 'pm_approved') AND p.deleted_at IS NULL AND {$roleFilter}
        ";
        $abRes = $this->db->query($abSql);
        $totalApprovedBudget = $abRes ? (float)$abRes->fetch_assoc()['total'] : 0.0;

        // 4. budget used total (Material cost + Contractor cost)
        $matSpentRes = $this->db->query("
            SELECT COALESCE(SUM(mri.received_quantity * poi.unit_price), 0) AS total
            FROM material_receiving_items mri
            INNER JOIN material_receiving mr ON mr.id = mri.receiving_id
            INNER JOIN purchase_order_items poi ON poi.id = mri.purchase_order_item_id
            INNER JOIN purchase_orders po ON po.id = mr.purchase_order_id
            INNER JOIN purchase_requests pr ON pr.id = po.request_id
            INNER JOIN projects p ON p.id = pr.project_id
            WHERE mr.overall_status = 'Accepted' AND p.deleted_at IS NULL AND {$roleFilter}
        ");
        $matSpentTotal = $matSpentRes ? (float)$matSpentRes->fetch_assoc()['total'] : 0.0;

        $conSpentRes = $this->db->query("
            SELECT COALESCE(SUM(txn.amount_paid), 0) AS total
            FROM con_financial_transactions txn
            INNER JOIN projects p ON p.id = txn.project_id
            WHERE txn.ref_type = 'contractor_bill' AND txn.update_status = 'Completed' AND p.deleted_at IS NULL AND {$roleFilter}
        ");
        $conSpentTotal = $conSpentRes ? (float)$conSpentRes->fetch_assoc()['total'] : 0.0;

        $totalBudgetUsed = $matSpentTotal + $conSpentTotal;

        // 5. pending approvals
        $paSql = "
            SELECT COUNT(*) AS cnt FROM (
                SELECT id, project_id FROM contractor_bill_requests WHERE status IN ('submitted', 'verified')
                UNION ALL
                SELECT id, project_id FROM con_work_orders WHERE status = 'draft'
                UNION ALL
                SELECT id, project_id FROM operational_fund_requests WHERE status = 'pending'
                UNION ALL
                SELECT id, project_id FROM vendor_payment_requests WHERE status = 'Submitted'
            ) t
            JOIN projects p ON p.id = t.project_id
            WHERE p.deleted_at IS NULL AND {$roleFilter}
        ";
        $paRes = $this->db->query($paSql);
        $pendingApprovalsCount = $paRes ? (int)$paRes->fetch_assoc()['cnt'] : 0;

        // 6. Portfolio Progress Trend (Actual vs Planned over past 6 months)
        $trendMonths = [];
        $actualTrend = [];
        $plannedTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = date('Y-m', strtotime("-$i months"));
            $trendMonths[] = date('M Y', strtotime("-$i months"));
            
            $tSql = "
                SELECT COALESCE(AVG(pwp.overall_progress_pct), 0) AS avg_pct
                FROM phase_weekly_progress pwp
                JOIN projects p ON p.id = pwp.project_id
                WHERE p.deleted_at IS NULL AND {$roleFilter} 
                  AND DATE_FORMAT(pwp.week_end_date, '%Y-%m') = '$date'
                  AND (pwp.pm_status = 'Approved' OR pwp.entry_status = 'Approved')
            ";
            $tRes = $this->db->query($tSql);
            $actualTrend[] = $tRes ? round((float)$tRes->fetch_assoc()['avg_pct'], 4) : 0.0;
            
            $plannedTrend[] = round(15 + (85 / 6) * (5 - $i + 1), 4);
        }

        // 7. Budget Utilization by Category
        $budgetCategoryData = $this->getPortfolioBudgetCategoryData($roleFilter);

        // 8. Top Delay / Risk Summary
        if (!empty($projectIds)) {
            require_once __DIR__ . '/Con_RiskDashboardController.php';
            $riskCtrl = new Con_RiskDashboardController($this->db, false);
            foreach ($projectIds as $pid) {
                $riskCtrl->syncRisksForProject($pid);
            }
        }

        $rSql = "
            SELECT pr.*, p.name AS project_name
            FROM project_risks pr
            JOIN projects p ON p.id = pr.project_id
            WHERE pr.status = 'Open' AND pr.severity IN ('High', 'Critical') AND p.deleted_at IS NULL AND {$roleFilter}
            ORDER BY pr.created_at DESC LIMIT 5
        ";
        $rRes = $this->db->query($rSql);
        $risks = $rRes ? $rRes->fetch_all(MYSQLI_ASSOC) : [];

        // 9. Upcoming Approvals List
        $approvals = [];
        $appSql = "
            SELECT 'Contractor Bill' AS type,
                   CONCAT('Bill #', cbr.id, ' — ', c.name) AS description,
                   cbr.bill_amount AS amount,
                   p.name AS project_name,
                   cbr.created_at,
                   CONCAT('/con-contractor-bill/', cbr.id) AS url
            FROM contractor_bill_requests cbr
            JOIN projects p ON p.id = cbr.project_id
            JOIN contractors c ON c.id = cbr.operator_id
            WHERE cbr.status IN ('submitted', 'verified') AND p.deleted_at IS NULL AND {$roleFilter}
            
            UNION ALL
            
            SELECT 'Work Order' AS type,
                   CONCAT('Work Order: ', wo.title, ' — ', c.name) AS description,
                   wo.total_amount AS amount,
                   p.name AS project_name,
                   wo.created_at,
                   CONCAT('/con-work-order/', wo.id) AS url
            FROM con_work_orders wo
            JOIN projects p ON p.id = wo.project_id
            JOIN contractors c ON c.id = wo.contractor_id
            WHERE wo.status = 'draft' AND p.deleted_at IS NULL AND {$roleFilter}
            
            UNION ALL
            
            SELECT 'Fund Request' AS type,
                   CONCAT('Fund: ', ofr.purpose) AS description,
                   ofr.amount AS amount,
                   p.name AS project_name,
                   ofr.created_at,
                   CONCAT('/con-fund-request/', ofr.id) AS url
            FROM operational_fund_requests ofr
            JOIN projects p ON p.id = ofr.project_id
            WHERE ofr.status = 'pending' AND p.deleted_at IS NULL AND {$roleFilter}
            
            UNION ALL
            
            SELECT 'Vendor Payment' AS type,
                   CONCAT('Vendor Invoice: ', vpr.invoice_number, ' — ', v.name) AS description,
                   vpr.invoice_amount AS amount,
                   p.name AS project_name,
                   vpr.created_at,
                   CONCAT('/con-vendor-payment/', vpr.id) AS url
            FROM vendor_payment_requests vpr
            JOIN projects p ON p.id = vpr.project_id
            JOIN vendors v ON v.id = vpr.vendor_id
            WHERE vpr.status = 'Submitted' AND p.deleted_at IS NULL AND {$roleFilter}
            ORDER BY created_at DESC LIMIT 5
        ";
        $appRes = $this->db->query($appSql);
        $approvals = $appRes ? $appRes->fetch_all(MYSQLI_ASSOC) : [];

        // 10. Alerts & Notifications
        $alerts = [];
        $lsSql = "
            SELECT m.name AS material_name, s.current_quantity, m.unit, m.min_stock_level, w.name AS warehouse_name
            FROM con_stocks s
            JOIN con_materials m ON m.id = s.material_id
            JOIN con_warehouses w ON w.id = s.warehouse_id
            JOIN projects p ON p.id = w.project_id
            WHERE s.current_quantity < m.min_stock_level AND p.deleted_at IS NULL AND {$roleFilter}
            LIMIT 3
        ";
        $lsRes = $this->db->query($lsSql);
        if ($lsRes) {
            while ($row = $lsRes->fetch_assoc()) {
                $alerts[] = [
                    'type' => 'Low Stock',
                    'message' => "Low stock for '{$row['material_name']}' at {$row['warehouse_name']} ({$row['current_quantity']} / {$row['min_stock_level']} {$row['unit']})",
                    'time' => '10 mins ago',
                    'severity' => 'Warning'
                ];
            }
        }
        
        $secSql = "
            SELECT i.issue_title, i.priority, i.reported_at, p.name AS project_name
            FROM con_security_incidents i
            JOIN projects p ON p.id = i.project_id
            WHERE i.status != 'Resolved' AND p.deleted_at IS NULL AND {$roleFilter}
            ORDER BY i.id DESC LIMIT 2
        ";
        $secRes = $this->db->query($secSql);
        if ($secRes) {
            while ($row = $secRes->fetch_assoc()) {
                $alerts[] = [
                    'type' => 'Security',
                    'message' => "Security Incident: {$row['issue_title']} at project {$row['project_name']}",
                    'time' => '1 hour ago',
                    'severity' => $row['priority']
                ];
            }
        }

        // Fetch all distinct timeline years for portfolio view
        $allYearsSql = "
            SELECT 
                MIN(YEAR(COALESCE(project_start_date, construction_start_date))) AS min_year,
                MAX(YEAR(COALESCE(expected_handover_date, construction_end_date))) AS max_year
            FROM project_timelines
        ";
        $allYearsRes = $this->db->query($allYearsSql);
        $allYearsRow = $allYearsRes ? $allYearsRes->fetch_assoc() : null;
        $globalStartYear = $allYearsRow && $allYearsRow['min_year'] ? (int)$allYearsRow['min_year'] : (int)date('Y') - 2;
        $globalEndYear = $allYearsRow && $allYearsRow['max_year'] ? (int)$allYearsRow['max_year'] : (int)date('Y') + 2;
        
        if ($globalStartYear <= 1970) $globalStartYear = (int)date('Y') - 2;
        if ($globalEndYear <= 1970) $globalEndYear = (int)date('Y') + 2;
        if ($globalEndYear < $globalStartYear) $globalEndYear = $globalStartYear;
        
        $globalYears = [];
        for ($y = $globalStartYear; $y <= $globalEndYear; $y++) {
            $globalYears[] = $y;
        }

        $this->view('phase2/dashboard/overview', [
            'current_route'        => 'all-projects-overview',
            'page_title'           => 'All Projects Overview',
            'projects'             => $projects,
            'totalProjects'        => $totalProjectsCount,
            'onTrackCount'         => $onTrackCount,
            'atRiskCount'          => $atRiskCount,
            'delayedCount'         => $delayedCount,
            'planningCount'        => $planningCount,
            'completedCount'       => $completedCount,
            'overallProgressPct'   => $overallProgressPct,
            'overallPlannedProgressPct' => $overallPlannedProgressPct,
            'locationsCount'       => $locationsCount,
            'totalApprovedBudget'  => $totalApprovedBudget,
            'totalBudgetUsed'      => $totalBudgetUsed,
            'pendingApprovals'     => $pendingApprovalsCount,
            'trendMonths'          => json_encode($trendMonths),
            'actualTrend'          => json_encode($actualTrend),
            'plannedTrend'         => json_encode($plannedTrend),
            'budgetCategoryData'   => json_encode($budgetCategoryData),
            'risks'                => $risks,
            'approvals'            => $approvals,
            'alerts'               => $alerts,
            'currentPage'          => $page,
            'totalPages'           => $totalPages,
            'totalRecords'         => $filteredCount,
            'perPage'              => $perPage,
            'offset'               => $offset,
            'allProjectsList'      => $allProjectsList,
            'globalYears'          => $globalYears,
        ]);
    }

    private function calculatePhaseBasedProgress(array $projectIds): array
    {
        if (empty($projectIds)) {
            return [];
        }

        $idsList = implode(',', array_map('intval', $projectIds));

        // Fetch received quantities by unit for these projects to display physical quantity usage
        $receivedQtyMap = [];
        $receivedQtyRes = $this->db->query("
            SELECT pr.project_id, m.unit, COALESCE(SUM(mri.received_quantity), 0) AS total_received
            FROM material_receiving_items mri
            INNER JOIN material_receiving mr ON mr.id = mri.receiving_id
            INNER JOIN purchase_order_items poi ON poi.id = mri.purchase_order_item_id
            INNER JOIN con_materials m ON m.id = poi.material_id
            INNER JOIN purchase_orders po ON po.id = mr.purchase_order_id
            INNER JOIN purchase_requests pr ON pr.id = po.request_id
            WHERE mr.overall_status = 'Accepted' AND pr.project_id IN ($idsList)
            GROUP BY pr.project_id, m.unit
        ");
        if ($receivedQtyRes) {
            while ($row = $receivedQtyRes->fetch_assoc()) {
                $pid = (int)$row['project_id'];
                $u = strtolower(trim($row['unit'] ?? ''));
                $receivedQtyMap[$pid][$u] = (float)$row['total_received'];
            }
        }

        // 1. Fetch weight_percentage for the phases of these projects
        $phasesRes = $this->db->query("
            SELECT id, project_id, phase_name, weight_percentage, phase_order
            FROM project_phases
            WHERE project_id IN ($idsList)
            ORDER BY project_id, phase_order ASC
        ");
        $phases = $phasesRes ? $phasesRes->fetch_all(MYSQLI_ASSOC) : [];

        // Group phases by project
        $projectPhases = [];
        foreach ($phases as $phase) {
            $projectPhases[(int)$phase['project_id']][] = $phase;
        }

        // 2. Fetch all phase items (work items)
        $itemsRes = $this->db->query("
            SELECT ppi.id AS item_id, ppi.phase_id, ppi.item_name, ppi.item_order, ppi.weight_pct AS item_weight,
                   pp.project_id
            FROM project_phase_items ppi
            INNER JOIN project_phases pp ON pp.id = ppi.phase_id
            WHERE pp.project_id IN ($idsList)
            ORDER BY ppi.phase_id, ppi.item_order ASC
        ");
        $items = $itemsRes ? $itemsRes->fetch_all(MYSQLI_ASSOC) : [];

        $phaseItems = [];
        foreach ($items as $item) {
            $phaseItems[(int)$item['phase_id']][] = $item;
        }

        // 3. Fetch all BOQ details for these projects' phases
        $dtRes = $this->db->query("
            SELECT ppi.phase_id, ppi.id AS phase_item_id, ppid.id AS detail_id,
                   ppid.work_type_id, ppid.total_boq_qty, ppid.weight_pct AS detail_weight,
                   pp.project_id, ppid.unit
            FROM project_phase_items ppi
            INNER JOIN project_phase_item_details ppid ON ppid.phase_item_id = ppi.id
            INNER JOIN project_phases pp ON pp.id = ppi.phase_id
            WHERE pp.project_id IN ($idsList)
        ");
        $details = $dtRes ? $dtRes->fetch_all(MYSQLI_ASSOC) : [];

        // Group details by project_id and phase_id
        $projectPhaseDetails = [];
        $itemDetails = [];
        foreach ($details as $d) {
            $pid = (int)$d['project_id'];
            $phaseId = (int)$d['phase_id'];
            $itemId = (int)$d['phase_item_id'];
            $projectPhaseDetails[$pid][$phaseId][] = $d;
            $itemDetails[$itemId][] = $d;
        }

        // 4. Fetch target months for phases
        $targetMonthsRes = $this->db->query("
            SELECT DISTINCT phase_id, target_month
            FROM phase_monthly_targets
            WHERE project_id IN ($idsList)
        ");
        $phaseTargetMonths = [];
        if ($targetMonthsRes) {
            while ($row = $targetMonthsRes->fetch_assoc()) {
                $phaseTargetMonths[(int)$row['phase_id']] = $row['target_month'];
            }
        }

        // 5. Fetch planned quantities from monthly targets
        $planRes = $this->db->query("
            SELECT pmti.phase_item_id, pmti.work_type_id, pmti.target_mode, pmt.phase_id,
                   SUM(CASE WHEN pmti.target_mode = 'pct' THEN pmti.monthly_target_pct ELSE pmti.monthly_target_qty END) AS total_target_qty,
                   pmt.project_id
            FROM phase_monthly_target_items pmti
            INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
            WHERE pmt.project_id IN ($idsList)
            GROUP BY pmt.project_id, pmt.phase_id, pmti.phase_item_id, pmti.work_type_id, pmti.target_mode
        ");
        $plannedQuantities = $planRes ? $planRes->fetch_all(MYSQLI_ASSOC) : [];

        // 6. Fetch actual completed quantities
        // 6a. Approved weekly progress items
        $weeklyActRes = $this->db->query("
            SELECT pmti.phase_item_id, pmti.work_type_id, pmt.project_id,
                   SUM(CASE WHEN pmti.target_mode = 'pct' THEN pwpi.achieved_pct ELSE pwpi.achieved_qty END) AS total_actual_qty
            FROM phase_weekly_progress_items pwpi
            INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
            INNER JOIN phase_monthly_target_items pmti ON pmti.id = pwpi.target_item_id
            INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
            WHERE pmt.project_id IN ($idsList)
              AND pwp.qc_status = 'Approved'
              AND pwp.pm_status = 'Approved'
            GROUP BY pmt.project_id, pmti.phase_item_id, pmti.work_type_id
        ");
        $weeklyActuals = $weeklyActRes ? $weeklyActRes->fetch_all(MYSQLI_ASSOC) : [];

        // 6b. Approved daily logs for pending/draft weeks (to avoid double counting, but capture intermediate work)
        $dailyActRes = $this->db->query("
            SELECT pmti.phase_item_id, pmti.work_type_id, dpl.project_id,
                   SUM(CASE WHEN pmti.target_mode = 'pct' THEN dwil.pct_done_today ELSE dwil.qty_done_today END) AS total_actual_qty
            FROM daily_work_item_logs dwil
            JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
            INNER JOIN phase_weekly_progress pwp ON pwp.id = dpl.weekly_progress_id
            LEFT JOIN phase_monthly_target_items pmti ON pmti.id = dwil.target_item_id
            WHERE dpl.project_id IN ($idsList)
              AND dpl.qc_status = 'Approved'
              AND dpl.pm_status = 'Approved'
              AND NOT (pwp.qc_status = 'Approved' AND pwp.pm_status = 'Approved')
            GROUP BY dpl.project_id, pmti.phase_item_id, pmti.work_type_id
        ");
        $dailyActuals = $dailyActRes ? $dailyActRes->fetch_all(MYSQLI_ASSOC) : [];

        // Map data in PHP
        $targetsMap = [];
        $phaseTargetsList = [];
        foreach ($plannedQuantities as $q) {
            $pid = (int)$q['project_id'];
            $phaseId = (int)$q['phase_id'];
            $key = $q['phase_item_id'] . '_' . $q['work_type_id'];
            $targetsMap[$pid][$key] = (float)$q['total_target_qty'];
            $phaseTargetsList[$pid][$phaseId][] = $q;
        }

        $actualsMap = [];
        foreach ($weeklyActuals as $q) {
            $pid = (int)$q['project_id'];
            $key = $q['phase_item_id'] . '_' . $q['work_type_id'];
            $actualsMap[$pid][$key] = ($actualsMap[$pid][$key] ?? 0.0) + (float)$q['total_actual_qty'];
        }
        foreach ($dailyActuals as $q) {
            $pid = (int)$q['project_id'];
            if ($q['phase_item_id'] !== null) {
                $key = $q['phase_item_id'] . '_' . $q['work_type_id'];
                $actualsMap[$pid][$key] = ($actualsMap[$pid][$key] ?? 0.0) + (float)$q['total_actual_qty'];
            }
        }

        // Synthesize detail rows for any planned targets not in project_phase_item_details
        foreach ($projectIds as $pid) {
            $phasesList = $projectPhases[$pid] ?? [];
            foreach ($phasesList as $phase) {
                $phaseId = (int)$phase['id'];
                $phaseDetails = $projectPhaseDetails[$pid][$phaseId] ?? [];
                
                $existingDetailKeys = [];
                foreach ($phaseDetails as $d) {
                    $existingDetailKeys[$d['phase_item_id'] . '_' . $d['work_type_id']] = true;
                }
                
                if (!empty($phaseTargetsList[$pid][$phaseId])) {
                    foreach ($phaseTargetsList[$pid][$phaseId] as $target) {
                        $key = $target['phase_item_id'] . '_' . $target['work_type_id'];
                        if (!isset($existingDetailKeys[$key])) {
                            $subItemWeight = 0.0;
                            $itemId = (int)$target['phase_item_id'];
                            if (!empty($phaseItems[$phaseId])) {
                                foreach ($phaseItems[$phaseId] as $pi) {
                                    if ((int)$pi['item_id'] === $itemId) {
                                        $subItemWeight = (float)$pi['item_weight'];
                                        break;
                                    }
                                }
                            }
                            
                            $synthesizedDetail = [
                                'phase_id' => $phaseId,
                                'phase_item_id' => $itemId,
                                'detail_id' => null,
                                'work_type_id' => $target['work_type_id'],
                                'total_boq_qty' => ($target['target_mode'] === 'pct') ? 100.0 : 0.0,
                                'detail_weight' => $subItemWeight,
                                'project_id' => $pid,
                                'unit' => ($target['target_mode'] === 'pct') ? '%' : ''
                            ];
                            
                            $phaseDetails[] = $synthesizedDetail;
                            $projectPhaseDetails[$pid][$phaseId] = $phaseDetails;
                            $itemDetails[$itemId][] = $synthesizedDetail;
                        }
                    }
                }
            }
        }

        $projectProgress = [];
        foreach ($projectIds as $pid) {
            $phasesList = $projectPhases[$pid] ?? [];
            $overallActual = 0.0;
            $overallPlanned = 0.0;
            $phasesData = [];

            foreach ($phasesList as $phase) {
                $phaseId = (int)$phase['id'];
                $phaseWeight = (float)$phase['weight_percentage'];

                $phaseDetails = $projectPhaseDetails[$pid][$phaseId] ?? [];
                $phasePlannedProgressWeighted = 0.0;
                $phaseActualProgressWeighted = 0.0;

                $totalBOQInPhase = 0.0;
                $totalApprovedInPhase = 0.0;
                $totalTargetInPhase = 0.0;

                foreach ($phaseDetails as $d) {
                    $boqQty  = (float)$d['total_boq_qty'];
                    $weight  = (float)$d['detail_weight'];
                    $key = $d['phase_item_id'] . '_' . $d['work_type_id'];

                    $targetQty = $targetsMap[$pid][$key] ?? 0.0;
                    $actualQty = $actualsMap[$pid][$key] ?? 0.0;

                    if ($boqQty > 0) {
                        // Planned
                        $plannedRatio = min(1.0, $targetQty / $boqQty);
                        $phasePlannedProgressWeighted += $plannedRatio * $weight;

                        // Actual
                        $actualRatio = min(1.0, $actualQty / $boqQty);
                        $phaseActualProgressWeighted += $actualRatio * $weight;
                    }

                    $totalBOQInPhase += $boqQty;
                    $totalApprovedInPhase += $actualQty;
                    $totalTargetInPhase += $targetQty;
                }

                // Volume-based Phase internal progress (e.g. 0.4% complete)
                $phaseActualVolumePct = $totalBOQInPhase > 0 ? ($totalApprovedInPhase / $totalBOQInPhase) * 100 : 0.0;
                $phasePlannedVolumePct = $totalBOQInPhase > 0 ? ($totalTargetInPhase / $totalBOQInPhase) * 100 : 0.0;

                // Project contribution (volume-based)
                $phaseContribution = $phaseActualVolumePct * ($phaseWeight / 100);

                // Add phase items
                $itemsData = [];
                $itemsInPhase = $phaseItems[$phaseId] ?? [];
                foreach ($itemsInPhase as $item) {
                    $itemId = (int)$item['item_id'];
                    $itemWeight = (float)$item['item_weight'];

                    $detailsOfItem = $itemDetails[$itemId] ?? [];
                    $itemPlannedProgressWeighted = 0.0;
                    $itemActualProgressWeighted = 0.0;

                    $qtyTexts = [];
                    foreach ($detailsOfItem as $d) {
                        $boqQty  = (float)$d['total_boq_qty'];
                        $weight  = (float)$d['detail_weight'];
                        $key = $d['phase_item_id'] . '_' . $d['work_type_id'];

                        $targetQty = $targetsMap[$pid][$key] ?? 0.0;
                        $actualQty = $actualsMap[$pid][$key] ?? 0.0;

                        if ($boqQty > 0) {
                            $plannedRatio = min(1.0, $targetQty / $boqQty);
                            $itemPlannedProgressWeighted += $plannedRatio * $weight;

                            $actualRatio = min(1.0, $actualQty / $boqQty);
                            $itemActualProgressWeighted += $actualRatio * $weight;
                        }

                        $u = trim($d['unit'] ?? '');
                        if ($boqQty > 0 && $u !== '') {
                            $uLower = strtolower($u);
                            $recQty = $receivedQtyMap[$pid][$uLower] ?? 0.0;
                            $qtyPct = round(($recQty / $boqQty) * 100, 1);
                            $qtyTexts[] = "(" . number_format($recQty) . " {$u} / " . number_format($boqQty) . " {$u} used) - {$qtyPct}% Qty Used";
                        }
                    }
                    $qtyUsageText = implode(', ', $qtyTexts);

                    $itemPlannedPct = $itemWeight > 0 ? ($itemPlannedProgressWeighted / $itemWeight) * 100 : 0.0;
                    $itemActualPct = $itemWeight > 0 ? ($itemActualProgressWeighted / $itemWeight) * 100 : 0.0;

                    $itemsData[] = [
                        'item_id' => $itemId,
                        'item_name' => $item['item_name'],
                        'item_order' => $item['item_order'],
                        'item_weight' => $itemWeight,
                        'item_planned_pct' => $itemPlannedPct,
                        'item_actual_pct' => $itemActualPct,
                        'qty_usage_text' => $qtyUsageText,
                    ];
                }

                $overallActual += $phaseActualProgressWeighted * ($phaseWeight / 100);
                $overallPlanned += $phasePlannedProgressWeighted * ($phaseWeight / 100);

                $phasesData[] = [
                    'phase_id' => $phaseId,
                    'phase_name' => $phase['phase_name'],
                    'phase_order' => $phase['phase_order'],
                    'phase_weight' => $phaseWeight,
                    'phase_actual_pct' => $phaseActualVolumePct,
                    'phase_planned_pct' => $phasePlannedVolumePct,
                    'phase_actual_weighted_pct' => $phaseActualProgressWeighted,
                    'phase_planned_weighted_pct' => $phasePlannedProgressWeighted,
                    'phase_contribution' => $phaseContribution,
                    'total_approved_qty' => $totalApprovedInPhase,
                    'total_boq' => $totalBOQInPhase,
                    'target_month' => $phaseTargetMonths[$phaseId] ?? null,
                    'items' => $itemsData,
                ];
            }

            $projectProgress[$pid] = [
                'actual'  => round($overallActual, 4),
                'planned' => round($overallPlanned, 4),
                'phases'  => $phasesData,
            ];
        }

        return $projectProgress;
    }

    private function getProgressAtDate(int $projectId, string $date)
    {
        // 1. Fetch weight_percentage for the phases of this project
        $phasesRes = $this->db->query("
            SELECT id, weight_percentage
            FROM project_phases
            WHERE project_id = $projectId
        ");
        $phases = $phasesRes ? $phasesRes->fetch_all(MYSQLI_ASSOC) : [];
        if (empty($phases)) {
            return ['planned' => 0.0, 'actual' => 0.0];
        }
        
        $phaseIds = array_column($phases, 'id');
        $phaseIdsList = implode(',', $phaseIds);

        // 2. Fetch all phase items
        $itemsRes = $this->db->query("
            SELECT id AS item_id, phase_id, weight_pct AS item_weight
            FROM project_phase_items
            WHERE phase_id IN ($phaseIdsList)
        ");
        $items = $itemsRes ? $itemsRes->fetch_all(MYSQLI_ASSOC) : [];
        
        // Group items by phase
        $phaseItems = [];
        foreach ($items as $item) {
            $phaseItems[(int)$item['phase_id']][] = $item;
        }

        // 3. Fetch all BOQ details
        $dtRes = $this->db->query("
            SELECT ppi.phase_id, ppi.id AS phase_item_id, ppid.id AS detail_id,
                   ppid.work_type_id, ppid.total_boq_qty, ppid.weight_pct AS detail_weight
            FROM project_phase_items ppi
            INNER JOIN project_phase_item_details ppid ON ppid.phase_item_id = ppi.id
            WHERE ppi.phase_id IN ($phaseIdsList)
        ");
        $details = $dtRes ? $dtRes->fetch_all(MYSQLI_ASSOC) : [];
        
        $itemDetails = [];
        foreach ($details as $d) {
            $itemId = (int)$d['phase_item_id'];
            $itemDetails[$itemId][] = $d;
        }

        // 4. Fetch planned quantities from monthly targets up to $date
        // We pro-rate target of the query month based on the day of $date
        $queryMonthStr = date('Y-m-01', strtotime($date));
        $dayOfQueryDate = (int)date('j', strtotime($date));
        
        $planRes = $this->db->query("
            SELECT pmti.phase_item_id, pmti.work_type_id, pmt.target_month, pmti.target_mode,
                   pmti.monthly_target_qty, pmti.monthly_target_pct,
                   pmti.week_1_qty, pmti.week_2_qty, pmti.week_3_qty, pmti.week_4_qty,
                   pmti.week_1_pct, pmti.week_2_pct, pmti.week_3_pct, pmti.week_4_pct
            FROM phase_monthly_target_items pmti
            INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
            WHERE pmt.project_id = $projectId
              AND pmt.target_month <= '$queryMonthStr'
        ");
        $plannedRows = $planRes ? $planRes->fetch_all(MYSQLI_ASSOC) : [];
        
        $targetsMap = [];
        foreach ($plannedRows as $r) {
            $key = $r['phase_item_id'] . '_' . $r['work_type_id'];
            $targetMonth = $r['target_month'];
            
            $plannedQty = 0.0;
            if ($r['target_mode'] === 'pct') {
                if ($targetMonth < $queryMonthStr) {
                    $plannedQty = (float)$r['monthly_target_pct'];
                } elseif ($targetMonth === $queryMonthStr) {
                    if ($dayOfQueryDate <= 7) {
                        $plannedQty = (float)$r['week_1_pct'];
                    } elseif ($dayOfQueryDate <= 14) {
                        $plannedQty = (float)$r['week_1_pct'] + (float)$r['week_2_pct'];
                    } elseif ($dayOfQueryDate <= 21) {
                        $plannedQty = (float)$r['week_1_pct'] + (float)$r['week_2_pct'] + (float)$r['week_3_pct'];
                    } else {
                        $plannedQty = (float)$r['week_1_pct'] + (float)$r['week_2_pct'] + (float)$r['week_3_pct'] + (float)$r['week_4_pct'];
                    }
                    $plannedQty = min((float)$r['monthly_target_pct'], $plannedQty);
                }
            } else {
                if ($targetMonth < $queryMonthStr) {
                    $plannedQty = (float)$r['monthly_target_qty'];
                } elseif ($targetMonth === $queryMonthStr) {
                    if ($dayOfQueryDate <= 7) {
                        $plannedQty = (float)$r['week_1_qty'];
                    } elseif ($dayOfQueryDate <= 14) {
                        $plannedQty = (float)$r['week_1_qty'] + (float)$r['week_2_qty'];
                    } elseif ($dayOfQueryDate <= 21) {
                        $plannedQty = (float)$r['week_1_qty'] + (float)$r['week_2_qty'] + (float)$r['week_3_qty'];
                    } else {
                        $plannedQty = (float)$r['week_1_qty'] + (float)$r['week_2_qty'] + (float)$r['week_3_qty'] + (float)$r['week_4_qty'];
                    }
                    $plannedQty = min((float)$r['monthly_target_qty'], $plannedQty);
                }
            }
            
            $targetsMap[$key] = ($targetsMap[$key] ?? 0.0) + $plannedQty;
        }

        // 5. Fetch actual completed quantities up to $date
        // 5a. Approved weekly progress items
        $weeklyActRes = $this->db->query("
            SELECT pmti.phase_item_id, pmti.work_type_id,
                   SUM(CASE WHEN pmti.target_mode = 'pct' THEN pwpi.achieved_pct ELSE pwpi.achieved_qty END) AS total_actual_qty
            FROM phase_weekly_progress_items pwpi
            INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
            INNER JOIN phase_monthly_target_items pmti ON pmti.id = pwpi.target_item_id
            INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
            WHERE pmt.project_id = $projectId
              AND pwp.week_end_date <= '$date'
              AND pwp.qc_status = 'Approved'
              AND pwp.pm_status = 'Approved'
            GROUP BY pmti.phase_item_id, pmti.work_type_id
        ");
        $weeklyActuals = $weeklyActRes ? $weeklyActRes->fetch_all(MYSQLI_ASSOC) : [];

        // 5b. Approved daily logs for pending/draft weeks (to avoid double counting, but capture intermediate work)
        $dailyActRes = $this->db->query("
            SELECT pmti.phase_item_id, pmti.work_type_id,
                   SUM(CASE WHEN pmti.target_mode = 'pct' THEN dwil.pct_done_today ELSE dwil.qty_done_today END) AS total_actual_qty
            FROM daily_work_item_logs dwil
            JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
            INNER JOIN phase_weekly_progress pwp ON pwp.id = dpl.weekly_progress_id
            LEFT JOIN phase_monthly_target_items pmti ON pmti.id = dwil.target_item_id
            WHERE dpl.project_id = $projectId
              AND dpl.log_date <= '$date'
              AND dpl.qc_status = 'Approved'
              AND dpl.pm_status = 'Approved'
              AND NOT (pwp.qc_status = 'Approved' AND pwp.pm_status = 'Approved')
            GROUP BY pmti.phase_item_id, pmti.work_type_id
        ");
        $dailyActuals = $dailyActRes ? $dailyActRes->fetch_all(MYSQLI_ASSOC) : [];

        $actualsMap = [];
        foreach ($weeklyActuals as $q) {
            $key = $q['phase_item_id'] . '_' . $q['work_type_id'];
            $actualsMap[$key] = ($actualsMap[$key] ?? 0.0) + (float)$q['total_actual_qty'];
        }
        foreach ($dailyActuals as $q) {
            if ($q['phase_item_id'] !== null) {
                $key = $q['phase_item_id'] . '_' . $q['work_type_id'];
                $actualsMap[$key] = ($actualsMap[$key] ?? 0.0) + (float)$q['total_actual_qty'];
            }
        }

        // Synthesize detail rows for any planned targets not in project_phase_item_details
        $existingDetailKeys = [];
        foreach ($itemDetails as $itemId => $dets) {
            foreach ($dets as $d) {
                $existingDetailKeys[$itemId . '_' . $d['work_type_id']] = true;
            }
        }
        
        // Find phase_id for each item_id from phaseItems
        $itemToPhaseMap = [];
        foreach ($phaseItems as $phId => $itms) {
            foreach ($itms as $it) {
                $itemToPhaseMap[(int)$it['item_id']] = $phId;
            }
        }
        
        foreach ($plannedRows as $r) {
            $itemId = (int)$r['phase_item_id'];
            $wtId = (int)$r['work_type_id'];
            $key = $itemId . '_' . $wtId;
            if (!isset($existingDetailKeys[$key])) {
                $phId = $itemToPhaseMap[$itemId] ?? null;
                if ($phId !== null) {
                    // Find the sub-item weight
                    $subItemWeight = 0.0;
                    if (!empty($phaseItems[$phId])) {
                        foreach ($phaseItems[$phId] as $pi) {
                            if ((int)$pi['item_id'] === $itemId) {
                                $subItemWeight = (float)$pi['item_weight'];
                                break;
                            }
                        }
                    }
                    
                    $synthesizedDetail = [
                        'phase_id' => $phId,
                        'phase_item_id' => $itemId,
                        'detail_id' => null,
                        'work_type_id' => $wtId,
                        'total_boq_qty' => ($r['target_mode'] === 'pct') ? 100.0 : 0.0,
                        'detail_weight' => $subItemWeight,
                        'project_id' => $projectId,
                        'unit' => ($r['target_mode'] === 'pct') ? '%' : ''
                    ];
                    
                    $itemDetails[$itemId][] = $synthesizedDetail;
                    $existingDetailKeys[$key] = true;
                }
            }
        }

        // Calculate progress
        $overallActual = 0.0;
        $overallPlanned = 0.0;

        foreach ($phases as $phase) {
            $phaseId = (int)$phase['id'];
            $phaseWeight = (float)$phase['weight_percentage'];

            $itemsList = $phaseItems[$phaseId] ?? [];
            $phasePlannedProgressWeighted = 0.0;
            $phaseActualProgressWeighted = 0.0;

            foreach ($itemsList as $item) {
                $itemId = (int)$item['item_id'];
                $itemWeight = (float)$item['item_weight'];

                $detailsOfItem = $itemDetails[$itemId] ?? [];
                $itemPlannedProgressWeighted = 0.0;
                $itemActualProgressWeighted = 0.0;

                foreach ($detailsOfItem as $d) {
                    $boqQty  = (float)$d['total_boq_qty'];
                    $weight  = (float)$d['detail_weight'];
                    $key = $d['phase_item_id'] . '_' . $d['work_type_id'];

                    $targetQty = $targetsMap[$key] ?? 0.0;
                    $actualQty = $actualsMap[$key] ?? 0.0;

                    if ($boqQty > 0) {
                        $plannedRatio = min(1.0, $targetQty / $boqQty);
                        $itemPlannedProgressWeighted += $plannedRatio * $weight;

                        $actualRatio = min(1.0, $actualQty / $boqQty);
                        $itemActualProgressWeighted += $actualRatio * $weight;
                    }
                }

                $itemPlannedPct = $itemWeight > 0 ? ($itemPlannedProgressWeighted / $itemWeight) * 100 : 0.0;
                $itemActualPct = $itemWeight > 0 ? ($itemActualProgressWeighted / $itemWeight) * 100 : 0.0;

                $phasePlannedProgressWeighted += ($itemPlannedPct / 100) * $itemWeight;
                $phaseActualProgressWeighted += ($itemActualPct / 100) * $itemWeight;
            }

            $overallActual += $phaseActualProgressWeighted * ($phaseWeight / 100);
            $overallPlanned += $phasePlannedProgressWeighted * ($phaseWeight / 100);
        }

        return [
            'planned' => round($overallPlanned, 4),
            'actual'  => round($overallActual, 4)
        ];
    }

    private function getPortfolioProgressAtDate(string $date)
    {
        $roleFilter = $this->getProjectRoleFilter('p');
        $res = $this->db->query("SELECT id FROM projects p WHERE p.deleted_at IS NULL AND {$roleFilter}");
        $projectIds = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $projectIds[] = (int)$row['id'];
            }
        }
        
        if (empty($projectIds)) {
            return ['planned' => 0.0, 'actual' => 0.0];
        }
        
        $totalPlanned = 0.0;
        $totalActual = 0.0;
        foreach ($projectIds as $pid) {
            $prog = $this->getProgressAtDate($pid, $date);
            $totalPlanned += $prog['planned'];
            $totalActual += $prog['actual'];
        }
        
        $count = count($projectIds);
        return [
            'planned' => round($totalPlanned / $count, 2),
            'actual' => round($totalActual / $count, 2)
        ];
    }

    private function calculateProjectBudgetsAndSpent(array $projectIds): array
    {
        if (empty($projectIds)) {
            return [];
        }

        $idsList = implode(',', array_map('intval', $projectIds));

        // 1. Fetch approved budget for these projects
        $budgetRes = $this->db->query("
            SELECT project_id, MAX(id) AS budget_id, COALESCE(SUM(total_estimated_cost), 0) AS total_budget
            FROM initial_estimate_budgets
            WHERE status IN ('approved', 'pm_approved') AND project_id IN ($idsList)
            GROUP BY project_id
        ");
        $budgets = [];
        $budgetIds = [];
        if ($budgetRes) {
            while ($row = $budgetRes->fetch_assoc()) {
                $budgets[(int)$row['project_id']] = (float)$row['total_budget'];
                $budgetIds[(int)$row['project_id']] = (int)$row['budget_id'];
            }
        }

        // 2. Fetch material spent for these projects
        $materialRes = $this->db->query("
            SELECT pr.project_id, COALESCE(SUM(mri.received_quantity * poi.unit_price), 0) AS material_spent
            FROM material_receiving_items mri
            INNER JOIN material_receiving mr ON mr.id = mri.receiving_id
            INNER JOIN purchase_order_items poi ON poi.id = mri.purchase_order_item_id
            INNER JOIN purchase_orders po ON po.id = mr.purchase_order_id
            INNER JOIN purchase_requests pr ON pr.id = po.request_id
            WHERE mr.overall_status = 'Accepted' AND pr.project_id IN ($idsList)
            GROUP BY pr.project_id
        ");
        $materialSpent = [];
        if ($materialRes) {
            while ($row = $materialRes->fetch_assoc()) {
                $materialSpent[(int)$row['project_id']] = (float)$row['material_spent'];
            }
        }

        // 3. Fetch contractor spent for these projects
        $contractorRes = $this->db->query("
            SELECT project_id, COALESCE(SUM(amount_paid), 0) AS contractor_spent
            FROM con_financial_transactions
            WHERE ref_type = 'contractor_bill' AND update_status = 'Completed' AND project_id IN ($idsList)
            GROUP BY project_id
        ");
        $contractorSpent = [];
        if ($contractorRes) {
            while ($row = $contractorRes->fetch_assoc()) {
                $contractorSpent[(int)$row['project_id']] = (float)$row['contractor_spent'];
            }
        }

        $result = [];
        foreach ($projectIds as $pid) {
            $budget = $budgets[$pid] ?? 0.0;
            $budgetId = $budgetIds[$pid] ?? null;
            $matCost = $materialSpent[$pid] ?? 0.0;
            $conCost = $contractorSpent[$pid] ?? 0.0;
            $spent = $matCost + $conCost;
            $result[$pid] = [
                'total_budget' => $budget,
                'budget_id' => $budgetId,
                'total_spent' => $spent
            ];
        }

        return $result;
    }

    private function getYearlyProgressTrend(int $projectId, int $year)
    {
        // 1. Fetch all monthly target items for this project in the selected year
        $targetItemsRes = $this->db->query("
            SELECT pmti.phase_item_id, pmti.work_type_id, pmt.target_month, pmti.monthly_target_qty
            FROM phase_monthly_target_items pmti
            INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
            WHERE pmt.project_id = $projectId AND pmt.target_year = $year
        ");
        $targetItems = $targetItemsRes ? $targetItemsRes->fetch_all(MYSQLI_ASSOC) : [];
        if (empty($targetItems)) {
            return [
                'planned' => array_fill(0, 12, 0.0),
                'actual'  => array_fill(0, 12, null)
            ];
        }

        // Group by key = phase_item_id . '_' . work_type_id
        $itemsMeta = [];
        $monthlyPlanned = []; // [key][month_num] => qty
        foreach ($targetItems as $ti) {
            $key = $ti['phase_item_id'] . '_' . $ti['work_type_id'];
            $monthNum = (int)date('n', strtotime($ti['target_month']));
            $monthlyPlanned[$key][$monthNum] = (float)$ti['monthly_target_qty'];
            
            if (!isset($itemsMeta[$key])) {
                $itemsMeta[$key] = [
                    'yearly_target' => 0.0
                ];
            }
            $itemsMeta[$key]['yearly_target'] += (float)$ti['monthly_target_qty'];
        }

        // Calculate total target quantity for the year
        $totalYearTargetQty = 0.0;
        foreach ($itemsMeta as $key => $meta) {
            $totalYearTargetQty += $meta['yearly_target'];
        }
        if ($totalYearTargetQty <= 0) $totalYearTargetQty = 1.0;

        // Fetch all approved weekly progress achievements for the year
        $weeklyActRes = $this->db->query("
            SELECT pmti.phase_item_id, pmti.work_type_id, pwp.week_end_date, pwpi.achieved_qty
            FROM phase_weekly_progress_items pwpi
            INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
            INNER JOIN phase_monthly_target_items pmti ON pmti.id = pwpi.target_item_id
            INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
            WHERE pmt.project_id = $projectId
              AND pmt.target_year = $year
              AND pwp.qc_status = 'Approved'
              AND pwp.pm_status = 'Approved'
        ");
        $weeklyActuals = $weeklyActRes ? $weeklyActRes->fetch_all(MYSQLI_ASSOC) : [];

        // Fetch approved daily logs for the year where the weekly log is not approved
        $dailyActRes = $this->db->query("
            SELECT pmti.phase_item_id, pmti.work_type_id, dpl.log_date, dwil.qty_done_today
            FROM daily_work_item_logs dwil
            JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
            INNER JOIN phase_weekly_progress pwp ON pwp.id = dpl.weekly_progress_id
            LEFT JOIN phase_monthly_target_items pmti ON pmti.id = dwil.target_item_id
            WHERE dpl.project_id = $projectId
              AND YEAR(dpl.log_date) = $year
              AND dpl.qc_status = 'Approved'
              AND dpl.pm_status = 'Approved'
              AND NOT (pwp.qc_status = 'Approved' AND pwp.pm_status = 'Approved')
        ");
        $dailyActuals = $dailyActRes ? $dailyActRes->fetch_all(MYSQLI_ASSOC) : [];

        $plannedTrend = [];
        $actualTrend = [];
        
        $currentYear = (int)date('Y');
        $currentMonth = (int)date('n');

        for ($m = 1; $m <= 12; $m++) {
            $isFutureMonth = ($year > $currentYear) || ($year === $currentYear && $m > $currentMonth);
            
            $monthPlannedQty = 0.0;
            $monthActualQty = 0.0;

            foreach ($itemsMeta as $key => $meta) {
                // Planned quantity specifically for month m
                $monthPlannedQty += ($monthlyPlanned[$key][$m] ?? 0.0);

                // Actual quantity specifically for month m
                foreach ($weeklyActuals as $wa) {
                    $waKey = $wa['phase_item_id'] . '_' . $wa['work_type_id'];
                    if ($waKey === $key) {
                        $waTime = strtotime($wa['week_end_date']);
                        if (date('Y', $waTime) == $year && date('n', $waTime) == $m) {
                            $monthActualQty += (float)$wa['achieved_qty'];
                        }
                    }
                }
                foreach ($dailyActuals as $da) {
                    $daKey = $da['phase_item_id'] . '_' . $da['work_type_id'];
                    if ($daKey === $key) {
                        $daTime = strtotime($da['log_date']);
                        if (date('Y', $daTime) == $year && date('n', $daTime) == $m) {
                            $monthActualQty += (float)$da['qty_done_today'];
                        }
                    }
                }
            }

            // Calculate overall percentages relative to the yearly target sum
            $plannedPct = ($monthPlannedQty / $totalYearTargetQty) * 100.0;
            $actualPct = ($monthActualQty / $totalYearTargetQty) * 100.0;

            $plannedTrend[] = round(min(100.0, $plannedPct), 4);
            if ($isFutureMonth) {
                $actualTrend[] = null;
            } else {
                $actualTrend[] = round(min(100.0, $actualPct), 4);
            }
        }

        return [
            'planned' => $plannedTrend,
            'actual'  => $actualTrend
        ];
    }

    private function getPortfolioYearlyProgressTrend(int $year)
    {
        $roleFilter = $this->getProjectRoleFilter('p');
        $res = $this->db->query("SELECT id FROM projects p WHERE p.deleted_at IS NULL AND {$roleFilter}");
        $projectIds = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $projectIds[] = (int)$row['id'];
            }
        }
        
        if (empty($projectIds)) {
            return [
                'planned' => array_fill(0, 12, 0.0),
                'actual'  => array_fill(0, 12, null)
            ];
        }

        $allPlanned = array_fill(0, 12, 0.0);
        $allActual = array_fill(0, 12, 0.0);
        $actualCounts = array_fill(0, 12, 0);
        $validProjectsPlannedCount = 0;

        foreach ($projectIds as $pid) {
            $hasTargetsRes = $this->db->query("SELECT 1 FROM phase_monthly_targets WHERE project_id = $pid AND target_year = $year LIMIT 1");
            $hasTargets = $hasTargetsRes && $hasTargetsRes->num_rows > 0;
            if ($hasTargets) {
                $trend = $this->getYearlyProgressTrend($pid, $year);
                $validProjectsPlannedCount++;
                for ($m = 0; $m < 12; $m++) {
                    $allPlanned[$m] += $trend['planned'][$m];
                    if ($trend['actual'][$m] !== null) {
                        $allActual[$m] += $trend['actual'][$m];
                        $actualCounts[$m]++;
                    }
                }
            }
        }

        $plannedTrend = [];
        $actualTrend = [];
        for ($m = 0; $m < 12; $m++) {
            $plannedTrend[] = $validProjectsPlannedCount > 0 ? round($allPlanned[$m] / $validProjectsPlannedCount, 4) : 0.0;
            if ($actualCounts[$m] > 0) {
                $actualTrend[] = round($allActual[$m] / $actualCounts[$m], 4);
            } else {
                $actualTrend[] = null;
            }
        }

        return [
            'planned' => $plannedTrend,
            'actual'  => $actualTrend
        ];
    }

    private function getPortfolioBudgetCategoryData($roleFilter)
    {
        $innerFilter = str_replace('p.', 'p2.', $roleFilter);
        $sql = "
            SELECT 
                pp.phase_name AS label,
                ROUND(AVG(pp.weight_percentage), 1) AS weight,
                (
                    COALESCE((
                        SELECT SUM(iei.estimated_qty * iei.unit_cost_approx)
                        FROM initial_estimate_items iei
                        INNER JOIN initial_estimate_budgets ieb ON ieb.id = iei.budget_id
                        INNER JOIN project_phase_items ppi ON ppi.id = iei.phase_item_id
                        INNER JOIN projects p2 ON p2.id = ieb.project_id
                        WHERE ieb.status IN ('approved', 'pm_approved') 
                          AND p2.deleted_at IS NULL AND {$innerFilter}
                          AND ppi.phase_id IN (
                              SELECT subpp.id FROM project_phases subpp WHERE subpp.phase_name = pp.phase_name
                          )
                    ), 0)
                    +
                    COALESCE((
                        SELECT SUM(iel.rate_per_sqft * iel.area_sqft)
                        FROM initial_estimate_labors iel
                        INNER JOIN initial_estimate_budgets ieb ON ieb.id = iel.budget_id
                        INNER JOIN project_phase_items ppi ON ppi.id = iel.phase_item_id
                        INNER JOIN projects p2 ON p2.id = ieb.project_id
                        WHERE ieb.status IN ('approved', 'pm_approved')
                          AND p2.deleted_at IS NULL AND {$innerFilter}
                          AND ppi.phase_id IN (
                              SELECT subpp.id FROM project_phases subpp WHERE subpp.phase_name = pp.phase_name
                          )
                    ), 0)
                ) AS value
            FROM project_phases pp
            INNER JOIN projects p ON p.id = pp.project_id
            WHERE p.deleted_at IS NULL AND {$roleFilter}
            GROUP BY pp.phase_name
            ORDER BY MIN(pp.phase_order) ASC
        ";
        $res = $this->db->query($sql);
        $data = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $data[] = [
                    'label' => $row['label'],
                    'value' => (float)$row['value'],
                    'weight' => (float)$row['weight']
                ];
            }
        }
        return $data;
    }

    private function getProjectBudgetCategoryData(int $projectId)
    {
        $sql = "
            SELECT 
                pp.phase_name AS label,
                pp.weight_percentage AS weight,
                (
                    COALESCE((
                        SELECT SUM(iei.estimated_qty * iei.unit_cost_approx)
                        FROM initial_estimate_items iei
                        INNER JOIN initial_estimate_budgets ieb ON ieb.id = iei.budget_id
                        INNER JOIN project_phase_items ppi ON ppi.id = iei.phase_item_id
                        WHERE ieb.status IN ('approved', 'pm_approved') 
                          AND ieb.project_id = pp.project_id
                          AND ppi.phase_id = pp.id
                    ), 0)
                    +
                    COALESCE((
                        SELECT SUM(iel.rate_per_sqft * iel.area_sqft)
                        FROM initial_estimate_labors iel
                        INNER JOIN initial_estimate_budgets ieb ON ieb.id = iel.budget_id
                        INNER JOIN project_phase_items ppi ON ppi.id = iel.phase_item_id
                        WHERE ieb.status IN ('approved', 'pm_approved')
                          AND ieb.project_id = pp.project_id
                          AND ppi.phase_id = pp.id
                    ), 0)
                ) AS value
            FROM project_phases pp
            WHERE pp.project_id = ?
            ORDER BY pp.phase_order ASC
        ";
        $stmt = $this->db->prepare($sql);
        $data = [];
        if ($stmt) {
            $stmt->bind_param('i', $projectId);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $data[] = [
                    'label' => $row['label'],
                    'value' => (float)$row['value'],
                    'weight' => (float)$row['weight']
                ];
            }
        }
        return $data;
    }
}

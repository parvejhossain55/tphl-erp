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
    }

    private function getProjectRoleFilter($alias = 'p')
    {
        $userId = $_SESSION['user']['id'] ?? 0;
        $roleId = $_SESSION['user']['role_id'] ?? 0;
        if (in_array($roleId, [1, 3, 22])) return '1=1';
        return "({$alias}.pm_id = {$userId} OR {$alias}.site_engineer_id = {$userId} OR {$alias}.landman_id = {$userId} OR {$alias}.struct_engineer_id = {$userId} OR {$alias}.operation_head_id = {$userId} OR {$alias}.draftsman_id = {$userId} OR {$alias}.architect_id = {$userId} OR {$alias}.estimator_id = {$userId} OR {$alias}.qc_id = {$userId} OR {$alias}.operator_id = {$userId} OR {$alias}.created_by = {$userId})";
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
            
            $targets = [];
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
            
            $years = [];
            for ($y = $startYear; $y <= $endYear; $y++) {
                $years[] = $y;
            }

            echo json_encode([
                'targets' => $targets,
                'years' => $years
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
                    
                    // 2. Get target items and weights in phase
                    $itemsSql = "
                        SELECT 
                            pmti.id AS target_item_id,
                            pmti.monthly_target_qty,
                            pmti.week_1_qty,
                            pmti.week_2_qty,
                            pmti.week_3_qty,
                            pmti.week_4_qty,
                            COALESCE(ppi.weight_pct, 100) AS item_weight_in_phase
                        FROM phase_monthly_target_items pmti
                        JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
                        LEFT JOIN project_phase_items ppi ON ppi.phase_id = pmt.phase_id 
                             AND (pmti.work_item_name = ppi.item_name COLLATE utf8mb4_general_ci
                                  OR pmti.work_item_name LIKE CONCAT(ppi.item_name COLLATE utf8mb4_general_ci, ' - %')
                                  OR pmti.work_item_name LIKE CONCAT(ppi.item_name COLLATE utf8mb4_general_ci, '%'))
                        WHERE pmti.target_id = ?
                        GROUP BY pmti.id
                    ";
                    $itemsStmt = $this->db->prepare($itemsSql);
                    $itemsStmt->bind_param('i', $tid);
                    $itemsStmt->execute();
                    $items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    
                    // Calculate total weight of items in this target
                    $totalTargetWeight = 0;
                    foreach ($items as $item) {
                        $totalTargetWeight += (float)$item['item_weight_in_phase'];
                    }
                    if ($totalTargetWeight <= 0) $totalTargetWeight = 100.0;
                    
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
                    
                    $w1Planned = 0; $w2Planned = 0; $w3Planned = 0; $w4Planned = 0;
                    $w1Actual = ($maxApprovedWeek >= 1) ? 0.0 : null;
                    $w2Actual = ($maxApprovedWeek >= 2) ? 0.0 : null;
                    $w3Actual = ($maxApprovedWeek >= 3) ? 0.0 : null;
                    $w4Actual = ($maxApprovedWeek >= 4) ? 0.0 : null;
                    
                    foreach ($items as $item) {
                        $mQty = (float)$item['monthly_target_qty'];
                        $weight = (float)$item['item_weight_in_phase'];
                        $normWeight = ($weight / $totalTargetWeight) * 100.0;
                        
                        if ($mQty > 0) {
                            // Planned cumulative quantities
                            $p1 = (float)$item['week_1_qty'];
                            $p2 = $p1 + (float)$item['week_2_qty'];
                            $p3 = $p2 + (float)$item['week_3_qty'];
                            $p4 = $p3 + (float)$item['week_4_qty'];
                            
                            $p1 = min($mQty, $p1); $p2 = min($mQty, $p2); $p3 = min($mQty, $p3); $p4 = min($mQty, $p4);
                            
                            $w1Planned += ($p1 / $mQty) * $normWeight;
                            $w2Planned += ($p2 / $mQty) * $normWeight;
                            $w3Planned += ($p3 / $mQty) * $normWeight;
                            $w4Planned += ($p4 / $mQty) * $normWeight;
                            
                            // Actual cumulative quantities
                            $act1 = (float)($itemActuals[$item['target_item_id']][1] ?? 0.0);
                            $act2 = $act1 + (float)($itemActuals[$item['target_item_id']][2] ?? 0.0);
                            $act3 = $act2 + (float)($itemActuals[$item['target_item_id']][3] ?? 0.0);
                            $act4 = $act3 + (float)($itemActuals[$item['target_item_id']][4] ?? 0.0);
                            
                            $act1 = min($mQty, $act1); $act2 = min($mQty, $act2); $act3 = min($mQty, $act3); $act4 = min($mQty, $act4);
                            
                            if ($w1Actual !== null) $w1Actual += ($act1 / $mQty) * $normWeight;
                            if ($w2Actual !== null) $w2Actual += ($act2 / $mQty) * $normWeight;
                            if ($w3Actual !== null) $w3Actual += ($act3 / $mQty) * $normWeight;
                            if ($w4Actual !== null) $w4Actual += ($act4 / $mQty) * $normWeight;
                        }
                    }
                    
                    echo json_encode([
                        'labels' => ["Week 1", "Week 2", "Week 3", "Week 4"],
                        'planned' => [round($w1Planned, 1), round($w2Planned, 1), round($w3Planned, 1), round($w4Planned, 1)],
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
                    
                    for ($i = 5; $i >= 0; $i--) {
                        $date = date('Y-m', strtotime("-$i months"));
                        $trendMonths[] = date('M Y', strtotime("-$i months"));
                        $lastDay = date('Y-m-t', strtotime("-$i months"));
                        
                        if ($pid > 0) {
                            $prog = $this->getProgressAtDate($pid, $lastDay);
                        } else {
                            $prog = $this->getPortfolioProgressAtDate($lastDay);
                        }
                        
                        $plannedTrend[] = $prog['planned'];
                        
                        // If it's a future month, actual is null
                        if ($lastDay > date('Y-m-d')) {
                            $actualTrend[] = null;
                        } else {
                            $actualTrend[] = $prog['actual'];
                        }
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
                // Shows Jan-Dec progress for the selected year
                $labels = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                $plannedTrend = [];
                $actualTrend = [];
                
                for ($m = 1; $m <= 12; $m++) {
                    $monthStr = str_pad($m, 2, '0', STR_PAD_LEFT);
                    $lastDay = date('Y-m-t', strtotime("$year-$monthStr-01"));
                    
                    if ($pid > 0) {
                        $prog = $this->getProgressAtDate($pid, $lastDay);
                    } else {
                        $prog = $this->getPortfolioProgressAtDate($lastDay);
                    }
                    
                    $plannedTrend[] = $prog['planned'];
                    
                    if ($lastDay > date('Y-m-d')) {
                        $actualTrend[] = null;
                    } else {
                        $actualTrend[] = $prog['actual'];
                    }
                }
                
                echo json_encode([
                    'labels' => $labels,
                    'planned' => $plannedTrend,
                    'actual' => $actualTrend,
                    'title' => "Yearly Progress Breakdown ($year — Overall Scale)"
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
                
                for ($y = $startYear; $y <= $endYear; $y++) {
                    $labels[] = (string)$y;
                    $lastDay = "$y-12-31";
                    
                    if ($pid > 0) {
                        $prog = $this->getProgressAtDate($pid, $lastDay);
                    } else {
                        $prog = $this->getPortfolioProgressAtDate($lastDay);
                    }
                    
                    $plannedTrend[] = $prog['planned'];
                    
                    if ($lastDay > date('Y-m-d')) {
                        $actualTrend[] = null;
                    } else {
                        $actualTrend[] = $prog['actual'];
                    }
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
                FROM initial_estimate_budgets WHERE status = 'approved' GROUP BY project_id
            ) bud ON bud.project_id = p.id
            LEFT JOIN (
                SELECT project_id, SUM(amount_paid) AS total_spent
                FROM con_financial_transactions GROUP BY project_id
            ) spent ON spent.project_id = p.id
            WHERE p.deleted_at IS NULL AND {$roleFilter}
            ORDER BY p.id DESC";

        $result   = $this->db->query($sql);
        $projects = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

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

        foreach ($projects as $pr) {
            $actual = (float)$pr['progress_pct'];
            $planned = (float)$pr['planned_progress_pct'];

            $totalActualProg += $actual;
            $totalPlannedProg += $planned;

            // Determine status dynamically based on gap
            if ($pr['project_status'] === 'Completed') {
                $status = 'Completed';
                $completedCount++;
            } elseif ($pr['project_status'] === 'Planning' || ($actual == 0 && $planned == 0)) {
                $status = 'Planning';
                $planningCount++;
            } else {
                $gap = $planned - $actual;
                if ($gap <= 5) {
                    $status = 'On Track';
                    $onTrackCount++;
                } elseif ($gap <= 15) {
                    $status = 'At Risk';
                    $atRiskCount++;
                } else {
                    $status = 'Delayed';
                    $delayedCount++;
                }
            }

            $pr['calculated_status'] = $status;

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

        $overallProgressPct = $totalProjectsCount > 0 ? round($totalActualProg / $totalProjectsCount, 1) : 0;
        $overallPlannedProgressPct = $totalProjectsCount > 0 ? round($totalPlannedProg / $totalProjectsCount, 1) : 0;
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

        // Fetch Phases with calculated progress ONLY for paged projects
        $pagedProjectIds = array_column($pagedProjects, 'id');
        $projectPhases = [];
        if (!empty($pagedProjectIds)) {
            $idsList = implode(',', $pagedProjectIds);
            $phaseSql = "
                SELECT 
                    pp.id AS phase_id,
                    pp.project_id,
                    pp.phase_name,
                    pp.phase_order,
                    pp.weight_percentage AS phase_weight,
                    -- Calculate phase planned progress % (out of 100% of the phase items)
                    COALESCE(
                        (
                            SELECT SUM(
                                COALESCE(
                                    (
                                        SELECT SUM(pmti.planned_progress_pct)
                                        FROM phase_monthly_target_items pmti
                                        INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
                                        WHERE pmt.phase_id = pp.id
                                          AND (
                                              pmti.work_item_name = ppi2.item_name COLLATE utf8mb4_general_ci
                                              OR pmti.work_item_name LIKE CONCAT(ppi2.item_name COLLATE utf8mb4_general_ci, ' - %')
                                              OR pmti.work_item_name LIKE CONCAT(ppi2.item_name COLLATE utf8mb4_general_ci, '%')
                                          )
                                    ),
                                    0
                                ) * COALESCE(ppi2.weight_pct, 100) / 100
                            )
                            FROM project_phase_items ppi2
                            WHERE ppi2.phase_id = pp.id
                        ),
                        pp.planned_progress
                    ) AS phase_planned_pct,
                    
                    -- Calculate phase actual progress % (out of 100% of the phase items)
                    COALESCE(
                        (
                            SELECT SUM(
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
                                              pmti.work_item_name = ppi2.item_name COLLATE utf8mb4_general_ci
                                              OR pmti.work_item_name LIKE CONCAT(ppi2.item_name COLLATE utf8mb4_general_ci, ' - %')
                                              OR pmti.work_item_name LIKE CONCAT(ppi2.item_name COLLATE utf8mb4_general_ci, '%')
                                          )
                                    ),
                                    0
                                ) * COALESCE(ppi2.weight_pct, 100) / 100
                            )
                            FROM project_phase_items ppi2
                            WHERE ppi2.phase_id = pp.id
                        ),
                        pp.actual_progress
                    ) AS phase_actual_pct
                FROM project_phases pp
                WHERE pp.project_id IN ($idsList)
                ORDER BY pp.project_id, pp.phase_order ASC
            ";
            
            $phaseRes = $this->db->query($phaseSql);
            $phaseIds = [];
            $phasesByProject = [];
            if ($phaseRes) {
                while ($row = $phaseRes->fetch_assoc()) {
                    $row['items'] = [];
                    $phasesByProject[$row['project_id']][] = $row;
                    $phaseIds[] = $row['phase_id'];
                }
            }

            if (!empty($phaseIds)) {
                $phaseIdsList = implode(',', $phaseIds);
                $itemSql = "
                    SELECT 
                        ppi.id AS item_id,
                        ppi.phase_id,
                        ppi.item_name,
                        ppi.item_order,
                        ppi.weight_pct AS item_weight,
                        COALESCE(
                            (
                                SELECT SUM(pmti.planned_progress_pct)
                                FROM phase_monthly_target_items pmti
                                INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
                                WHERE pmt.phase_id = ppi.phase_id
                                  AND (
                                      pmti.work_item_name = ppi.item_name COLLATE utf8mb4_general_ci
                                      OR pmti.work_item_name LIKE CONCAT(ppi.item_name COLLATE utf8mb4_general_ci, ' - %')
                                      OR pmti.work_item_name LIKE CONCAT(ppi.item_name COLLATE utf8mb4_general_ci, '%')
                                  )
                            ),
                            ppi.planned_progress
                        ) AS item_planned_pct,
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
                                WHERE pmt.phase_id = ppi.phase_id
                                  AND (
                                      pmti.work_item_name = ppi.item_name COLLATE utf8mb4_general_ci
                                      OR pmti.work_item_name LIKE CONCAT(ppi.item_name COLLATE utf8mb4_general_ci, ' - %')
                                      OR pmti.work_item_name LIKE CONCAT(ppi.item_name COLLATE utf8mb4_general_ci, '%')
                                  )
                            ),
                            ppi.actual_progress
                        ) AS item_actual_pct
                    FROM project_phase_items ppi
                    WHERE ppi.phase_id IN ($phaseIdsList)
                    ORDER BY ppi.phase_id, ppi.item_order ASC
                ";
                $itemRes = $this->db->query($itemSql);
                if ($itemRes) {
                    $phaseItems = [];
                    while ($itemRow = $itemRes->fetch_assoc()) {
                        $phaseItems[$itemRow['phase_id']][] = $itemRow;
                    }
                    foreach ($phasesByProject as $projectId => &$phases) {
                        foreach ($phases as &$ph) {
                            $ph['items'] = $phaseItems[$ph['phase_id']] ?? [];
                        }
                    }
                    unset($phases);
                }
            }
            $projectPhases = $phasesByProject;
        }

        foreach ($pagedProjects as &$pr) {
            $pid = $pr['id'];
            $pr['phases'] = $projectPhases[$pid] ?? [];
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
            WHERE b.status = 'approved' AND p.deleted_at IS NULL AND {$roleFilter}
        ";
        $abRes = $this->db->query($abSql);
        $totalApprovedBudget = $abRes ? (float)$abRes->fetch_assoc()['total'] : 0.0;

        // 4. budget used total
        $buSql = "
            SELECT COALESCE(SUM(spent.total_spent), 0) AS total
            FROM projects p
            JOIN (
                SELECT project_id, SUM(amount_paid) AS total_spent
                FROM con_financial_transactions GROUP BY project_id
            ) spent ON spent.project_id = p.id
            WHERE p.deleted_at IS NULL AND {$roleFilter}
        ";
        $buRes = $this->db->query($buSql);
        $totalBudgetUsed = $buRes ? (float)$buRes->fetch_assoc()['total'] : 0.0;

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
            $actualTrend[] = $tRes ? round((float)$tRes->fetch_assoc()['avg_pct'], 1) : 0;
            
            $plannedTrend[] = round(15 + (85 / 6) * (5 - $i + 1), 1);
        }

        // 7. Budget Utilization by Category
        $budgetCategoryData = [];
        $bcSql = "
            SELECT dt.name AS category_name, SUM(iei.estimated_qty * iei.unit_cost_approx) AS total_cost
            FROM initial_estimate_items iei
            JOIN initial_estimate_budgets ieb ON ieb.id = iei.budget_id
            JOIN design_types dt ON dt.id = iei.design_type_id
            JOIN projects p ON p.id = ieb.project_id
            WHERE ieb.status = 'approved' AND p.deleted_at IS NULL AND {$roleFilter}
            GROUP BY dt.id
        ";
        $bcRes = $this->db->query($bcSql);
        if ($bcRes) {
            while ($row = $bcRes->fetch_assoc()) {
                $budgetCategoryData[] = [
                    'label' => $row['category_name'],
                    'value' => (float)$row['total_cost']
                ];
            }
        }
        
        $blSql = "
            SELECT dt.name AS category_name, SUM(iel.rate_per_sqft * iel.area_sqft) AS total_cost
            FROM initial_estimate_labors iel
            JOIN initial_estimate_budgets ieb ON ieb.id = iel.budget_id
            JOIN design_types dt ON dt.id = iel.design_type_id
            JOIN projects p ON p.id = ieb.project_id
            WHERE ieb.status = 'approved' AND p.deleted_at IS NULL AND {$roleFilter}
            GROUP BY dt.id
        ";
        $blRes = $this->db->query($blSql);
        if ($blRes) {
            while ($row = $blRes->fetch_assoc()) {
                $found = false;
                foreach ($budgetCategoryData as &$bc) {
                    if ($bc['label'] === $row['category_name']) {
                        $bc['value'] += (float)$row['total_cost'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $budgetCategoryData[] = [
                        'label' => $row['category_name'],
                        'value' => (float)$row['total_cost']
                    ];
                }
            }
        }

        if (empty($budgetCategoryData)) {
            $budgetCategoryData = [
                ['label' => 'Structure', 'value' => 5000000],
                ['label' => 'Masonry', 'value' => 2000000],
                ['label' => 'Finishes', 'value' => 1500000],
                ['label' => 'MEP', 'value' => 1000000],
                ['label' => 'Others', 'value' => 500000],
            ];
        }

        // 8. Top Delay / Risk Summary
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

    private function getProgressAtDate(int $projectId, string $date)
    {
        $sql = "
            SELECT 
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
                                          AND dpl.log_date <= ?
                                    ) / NULLIF(pmti.monthly_target_qty, 0)) * pmti.planned_progress_pct
                                )
                                FROM phase_monthly_target_items pmti
                                INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
                                WHERE pmt.phase_id = pp.id
                                  AND pmt.target_month <= DATE_FORMAT(?, '%Y-%m-01')
                                  AND (
                                      pmti.work_item_name = ppi.item_name COLLATE utf8mb4_general_ci
                                      OR pmti.work_item_name LIKE CONCAT(ppi.item_name COLLATE utf8mb4_general_ci, ' - %')
                                      OR pmti.work_item_name LIKE CONCAT(ppi.item_name COLLATE utf8mb4_general_ci, '%')
                                  )
                            ),
                            pp.actual_progress
                        ) / 100
                    ) * COALESCE(ppi.weight_pct, 100) / 100 * COALESCE(pp.weight_percentage, 0)
                ) AS actual_progress_pct,
                
                SUM(
                    (
                        COALESCE(
                            (
                                SELECT SUM(
                                    CASE 
                                        WHEN pmt.target_month < DATE_FORMAT(?, '%Y-%m-01') THEN pmti.planned_progress_pct
                                        WHEN pmt.target_month = DATE_FORMAT(?, '%Y-%m-01') THEN 
                                            LEAST(1.0, (
                                                CASE 
                                                    WHEN DAY(?) <= 7 THEN pmti.week_1_qty
                                                    WHEN DAY(?) <= 14 THEN pmti.week_1_qty + pmti.week_2_qty
                                                    WHEN DAY(?) <= 21 THEN pmti.week_1_qty + pmti.week_2_qty + pmti.week_3_qty
                                                    ELSE pmti.week_1_qty + pmti.week_2_qty + pmti.week_3_qty + pmti.week_4_qty
                                                END
                                            ) / NULLIF(pmti.monthly_target_qty, 0)) * pmti.planned_progress_pct
                                        ELSE 0
                                    END
                                )
                                FROM phase_monthly_target_items pmti
                                INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
                                WHERE pmt.phase_id = pp.id
                                  AND pmt.target_month <= DATE_FORMAT(?, '%Y-%m-01')
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
            WHERE pp.project_id = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['planned' => 0.0, 'actual' => 0.0];
        }
        $stmt->bind_param('ssssssssi', $date, $date, $date, $date, $date, $date, $date, $date, $projectId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return [
            'planned' => round((float)($res['planned_progress_pct'] ?? 0.0), 2),
            'actual' => round((float)($res['actual_progress_pct'] ?? 0.0), 2),
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
}

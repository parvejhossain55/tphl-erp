<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Bypassing controller construct redirects, execute index logic directly
$_SESSION['user'] = ['id' => 1, 'role_id' => 1];

// Let's copy getProgressAtDate and getPortfolioProgressAtDate directly or instantiate the controller
class MockController {
    public $db;
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getProjectRoleFilter($alias = 'p') {
        return '1=1';
    }

    public function getProgressAtDate(int $projectId, string $date) {
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
            SELECT pmti.phase_item_id, pmti.work_type_id, pmt.target_month,
                   pmti.monthly_target_qty, pmti.week_1_qty, pmti.week_2_qty, pmti.week_3_qty, pmti.week_4_qty
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
            
            $targetsMap[$key] = ($targetsMap[$key] ?? 0.0) + $plannedQty;
        }

        // 5. Fetch actual completed quantities up to $date
        // 5a. Approved weekly progress items
        $weeklyActRes = $this->db->query("
            SELECT pmti.phase_item_id, pmti.work_type_id, SUM(pwpi.achieved_qty) AS total_actual_qty
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
            SELECT pmti.phase_item_id, pmti.work_type_id, SUM(dwil.qty_done_today) AS total_actual_qty
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
    
    public function getPortfolioProgressAtDate(string $date) {
        return ['planned' => 0.0, 'actual' => 0.0];
    }
}

$mock = new MockController($db);

$pid = 13;

// Test All-Time Mode
echo "=== ALL-TIME TREND FOR PROJECT 13 ===\n";
$startYear = 2026;
$endYear = 2029;
$labels = [];
$plannedTrend = [];
$actualTrend = [];

for ($y = $startYear; $y <= $endYear; $y++) {
    $labels[] = (string)$y;
    $currentYear = (int)date('Y');
    
    if ($y > $currentYear) {
        $lastDay = "$y-12-31";
        $prog = $mock->getProgressAtDate($pid, $lastDay);
        $plannedTrend[] = $prog['planned'];
        $actualTrend[] = null;
    } elseif ($y === $currentYear) {
        $today = date('Y-m-d');
        $prog = $mock->getProgressAtDate($pid, $today);
        $plannedTrend[] = $prog['planned'];
        $actualTrend[] = $prog['actual'];
    } else {
        $lastDay = "$y-12-31";
        $prog = $mock->getProgressAtDate($pid, $lastDay);
        $plannedTrend[] = $prog['planned'];
        $actualTrend[] = $prog['actual'];
    }
}

print_r([
    'labels' => $labels,
    'planned' => $plannedTrend,
    'actual' => $actualTrend
]);

<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Bypassing controller construct redirects, execute index logic directly
$_SESSION['user'] = ['id' => 1, 'role_id' => 1];

// We can run the index function logic by simulating a request or copying the controller code.
// Let's copy the code from Con_ProjectOverviewDashboardController.php's index method for ajax_get_trend_data.

class TestRunner {
    public $db;
    public function __construct($db) {
        $this->db = $db;
    }
    
    private function getProjectRoleFilter($alias = 'p') {
        return '1=1';
    }

    public function run($pid, $mode, $year = 2026, $tid = 0) {
        // Here is the trend data code from the controller
        if ($mode === 'monthly') {
            if ($tid > 0) {
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
                
                $totalTargetWeight = 0;
                foreach ($items as $item) {
                    $totalTargetWeight += (float)$item['item_weight_in_phase'];
                }
                if ($totalTargetWeight <= 0) $totalTargetWeight = 100.0;
                
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
                        $p1 = (float)$item['week_1_qty'];
                        $p2 = $p1 + (float)$item['week_2_qty'];
                        $p3 = $p2 + (float)$item['week_3_qty'];
                        $p4 = $p3 + (float)$item['week_4_qty'];
                        
                        $p1 = min($mQty, $p1); $p2 = min($mQty, $p2); $p3 = min($mQty, $p3); $p4 = min($mQty, $p4);
                        
                        $w1Planned += ($p1 / $mQty) * $normWeight;
                        $w2Planned += ($p2 / $mQty) * $normWeight;
                        $w3Planned += ($p3 / $mQty) * $normWeight;
                        $w4Planned += ($p4 / $mQty) * $normWeight;
                        
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
                
                return [
                    'labels' => ["Week 1", "Week 2", "Week 3", "Week 4"],
                    'planned' => [round($w1Planned, 1), round($w2Planned, 1), round($w3Planned, 1), round($w4Planned, 1)],
                    'actual' => [
                        $w1Actual !== null ? round($w1Actual, 1) : null,
                        $w2Actual !== null ? round($w2Actual, 1) : null,
                        $w3Actual !== null ? round($w3Actual, 1) : null,
                        $w4Actual !== null ? round($w4Actual, 1) : null
                    ]
                ];
            } else {
                return "tid is 0";
            }
        } elseif ($mode === 'yearly') {
            $labels = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            $plannedTrend = [];
            $actualTrend = [];
            
            for ($m = 1; $m <= 12; $m++) {
                $monthStr = str_pad($m, 2, '0', STR_PAD_LEFT);
                $lastDay = date('Y-m-t', strtotime("$year-$monthStr-01"));
                
                $monthRep = "$year-$monthStr";
                $currentMonthRep = date('Y-m', strtotime('2026-06-08')); // Simulated today
                
                $isFutureMonth = ($monthRep > $currentMonthRep);
                
                if ($isFutureMonth) {
                    $prog = $this->getProgressAtDate($pid, $lastDay);
                    $plannedTrend[] = $prog['planned'];
                    $actualTrend[] = null;
                } else {
                    $queryDate = ($monthRep === $currentMonthRep) ? '2026-06-08' : $lastDay;
                    $prog = $this->getProgressAtDate($pid, $queryDate);
                    $plannedTrend[] = $prog['planned'];
                    $actualTrend[] = $prog['actual'];
                }
            }
            return [
                'labels' => $labels,
                'planned' => $plannedTrend,
                'actual' => $actualTrend
            ];
        }
    }

    private function getProgressAtDate(int $projectId, string $date) {
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
}

$tr = new TestRunner($db);

echo "=== YEARLY FOR 2026 ===\n";
print_r($tr->run(13, 'yearly', 2026));

echo "=== MONTHLY FOR TARGET 1 ===\n";
print_r($tr->run(13, 'monthly', 2026, 1));

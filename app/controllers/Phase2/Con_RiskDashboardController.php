<?php
namespace Phase2;

use Controller;

class Con_RiskDashboardController extends Controller {

    protected $db;

    public function __construct($db, $checkRole = true) {
        $this->db = $db;
        if ($checkRole) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (!isset($_SESSION['user'])) {
                header("Location: " . $GLOBALS['base_url'] . "/login"); exit;
            }
            if (!in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 15, 22])) {
                setError("Access denied.");
                header("Location: " . $GLOBALS['base_url'] . "/con-dashboard"); exit;
            }
        }
    }

    // =========================================================================
    // INDEX — Risk Dashboard for a project
    // =========================================================================
    public function index($projectId) {
        $projectId = (int)$projectId;
        $_SESSION['active_project_id'] = $projectId;

        // Project info
        $pStmt = $this->db->prepare("SELECT id, name FROM projects WHERE id=?");
        $pStmt->bind_param('i', $projectId);
        $pStmt->execute();
        $project = $pStmt->get_result()->fetch_assoc();
        if (!$project) {
            setError("Project not found.");
            header("Location: " . $GLOBALS['base_url'] . "/con-projects"); exit;
        }
        $_SESSION['active_project_name'] = $project['name'];

        // Phases
        $phStmt = $this->db->prepare("SELECT id, phase_name, phase_order, planned_start_date, planned_end_date, actual_progress, planned_progress, status FROM project_phases WHERE project_id=? ORDER BY phase_order ASC");
        $phStmt->bind_param('i', $projectId);
        $phStmt->execute();
        $phases = $phStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // ── Auto-detect missed weekly targets across all months ──────────────
        // We scan all monthly targets, all weekly progress items, and find
        // weeks where achieved_qty < planned week_N_qty for any target item.
        $missedTargets = $this->getMissedWeeklyTargets($projectId);

        // Auto-generate risk records for newly detected misses (idempotent)
        $this->autoGenerateRisks($projectId, $missedTargets);

        // ── Fetch identified risks from project_risks ────────────────────────
        $rStmt = $this->db->prepare("
            SELECT pr.*,
                   pp.phase_name
            FROM project_risks pr
            LEFT JOIN project_phases pp ON pp.id = pr.phase_id
            WHERE pr.project_id = ?
            ORDER BY pr.created_at DESC
        ");
        $rStmt->bind_param('i', $projectId);
        $rStmt->execute();
        $risks = $rStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // ── KPI Metrics ──────────────────────────────────────────────────────
        $totalRisks    = count($risks);
        $criticalRisks = 0;
        $openRisks     = 0;
        $resolvedRisks = 0;
        foreach ($risks as $r) {
            if (in_array($r['severity'], ['High', 'Critical'])) $criticalRisks++;
            if ($r['status'] === 'Open')     $openRisks++;
            if (in_array($r['status'], ['Resolved', 'Closed'])) $resolvedRisks++;
        }
        $missedWeekCount = count($missedTargets);

        $this->view('phase2/risk-dashboard/index', [
            'current_route'   => 'con-risk-dashboard',
            'page_title'      => 'Risk Dashboard',
            'project'         => $project,
            'phases'          => $phases,
            'risks'           => $risks,
            'missedTargets'   => $missedTargets,
            'totalRisks'      => $totalRisks,
            'criticalRisks'   => $criticalRisks,
            'openRisks'       => $openRisks,
            'resolvedRisks'   => $resolvedRisks,
            'missedWeekCount' => $missedWeekCount,
        ]);
    }

    // =========================================================================
    // STORE RISK — manually log a risk
    // =========================================================================
    public function storeRisk() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $GLOBALS['base_url'] . "/con-projects"); exit;
        }

        $projectId   = (int)($_POST['project_id'] ?? 0);
        $phaseId     = !empty($_POST['phase_id']) ? (int)$_POST['phase_id'] : null;
        $category    = trim($_POST['category'] ?? 'Progress');
        $description = trim($_POST['description'] ?? '');
        $impact      = $_POST['impact'] ?? 'Medium';
        $likelihood  = $_POST['likelihood'] ?? 'Medium';
        $severity    = $_POST['severity'] ?? 'Medium';
        $owner       = trim($_POST['owner'] ?? '');
        $dueDate     = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

        if (!$projectId || empty($description)) {
            setError("Project and description are required.");
            header("Location: " . $GLOBALS['base_url'] . "/con-risk-dashboard/$projectId"); exit;
        }

        // Generate risk code
        $riskCode = 'RK-' . strtoupper(substr(md5(uniqid()), 0, 6));

        $stmt = $this->db->prepare("
            INSERT INTO project_risks
              (project_id, phase_id, risk_code, category, description, impact, likelihood, severity, owner, due_date, status, is_auto)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Open', 0)
        ");
        $stmt->bind_param('iissssssss', $projectId, $phaseId, $riskCode, $category, $description, $impact, $likelihood, $severity, $owner, $dueDate);
        $stmt->execute();

        setSuccess("Risk logged successfully.");
        header("Location: " . $GLOBALS['base_url'] . "/con-risk-dashboard/$projectId"); exit;
    }

    // =========================================================================
    // UPDATE RISK STATUS
    // =========================================================================
    public function updateRiskStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $GLOBALS['base_url'] . "/con-projects"); exit;
        }

        $riskId    = (int)($_POST['risk_id'] ?? 0);
        $status    = $_POST['status'] ?? 'Open';
        $projectId = (int)($_POST['project_id'] ?? 0);

        $allowed = ['Open', 'In Progress', 'Resolved', 'Closed'];
        if (!in_array($status, $allowed)) $status = 'Open';

        $stmt = $this->db->prepare("UPDATE project_risks SET status=? WHERE id=? AND project_id=?");
        $stmt->bind_param('sii', $status, $riskId, $projectId);
        $stmt->execute();

        setSuccess("Risk status updated.");
        header("Location: " . $GLOBALS['base_url'] . "/con-risk-dashboard/$projectId"); exit;
    }

    // =========================================================================
    // DELETE RISK
    // =========================================================================
    public function deleteRisk($riskId) {
        $riskId = (int)$riskId;
        $projectId = (int)($_GET['project_id'] ?? $_POST['project_id'] ?? 0);

        $stmt = $this->db->prepare("DELETE FROM project_risks WHERE id=? AND project_id=?");
        $stmt->bind_param('ii', $riskId, $projectId);
        $stmt->execute();

        setSuccess("Risk deleted.");
        header("Location: " . $GLOBALS['base_url'] . "/con-risk-dashboard/$projectId"); exit;
    }

    // =========================================================================
    // PUBLIC — sync risks for a project (idempotent, supports run-rate & daily log checks)
    // =========================================================================
    public function syncRisksForProject($projectId) {
        $projectId = (int)$projectId;
        
        // 1. Fetch all target items
        $stmt = $this->db->prepare("
            SELECT
                pmti.id                  AS target_item_id,
                pmti.work_item_name      AS item_name,
                pmti.work_type_id,
                pmti.start_date,
                pmti.end_date,
                wt.work_type_name,
                pmti.unit,
                pmti.monthly_target_qty,
                pmti.week_1_qty,
                pmti.week_2_qty,
                pmti.week_3_qty,
                pmti.week_4_qty,
                pmt.target_month,
                pmt.id                   AS target_id,
                pmt.phase_id,
                pp.phase_name
            FROM phase_monthly_target_items pmti
            JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
            JOIN project_phases pp ON pp.id = pmt.phase_id
            LEFT JOIN work_types wt ON wt.id = pmti.work_type_id
            WHERE pmt.project_id = ?
            ORDER BY pmt.target_month ASC, pmti.id ASC
        ");
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $targetItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // 2. Fetch existing weekly progress achievements and daily quantities
        $wpStmt = $this->db->prepare("
            SELECT 
                pwp.week_number, 
                pwp.week_start_date,
                pwpi.target_item_id, 
                pwpi.achieved_qty,
                pwpi.`1st_day_qty`,
                pwpi.`2nd_day_qty`,
                pwpi.`3rd_day_qty`,
                pwpi.`4th_day_qty`,
                pwpi.`5th_day_qty`,
                pwpi.`6_th_day_qty`,
                pwpi.`7th_day_qty`
            FROM phase_weekly_progress pwp
            INNER JOIN phase_weekly_progress_items pwpi ON pwpi.weekly_progress_id = pwp.id
            WHERE pwp.project_id = ?
        ");
        $wpStmt->bind_param('i', $projectId);
        $wpStmt->execute();
        $weeklyAchievements = $wpStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $achievedMap = [];
        $weeklyDailyProgress = [];
        foreach ($weeklyAchievements as $wa) {
            $itemId = (int)$wa['target_item_id'];
            $wk = (int)$wa['week_number'];
            $achievedMap[$itemId][$wk] = (float)$wa['achieved_qty'];

            // Map Day 1 to Day 7 column values to dates
            $weekStart = $wa['week_start_date'];
            if ($weekStart && $weekStart !== '0000-00-00') {
                $daysMapping = [
                    1 => '1st_day_qty',
                    2 => '2nd_day_qty',
                    3 => '3rd_day_qty',
                    4 => '4th_day_qty',
                    5 => '5th_day_qty',
                    6 => '6_th_day_qty',
                    7 => '7th_day_qty'
                ];
                foreach ($daysMapping as $dIdx => $colName) {
                    $dQty = (float)($wa[$colName] ?? 0.00);
                    if ($dQty > 0) {
                        $dDate = date('Y-m-d', strtotime($weekStart . " + " . ($dIdx - 1) . " days"));
                        $weeklyDailyProgress[$itemId][$dDate] = $dQty;
                    }
                }
            }
        }

        // 3. Fetch all daily work logs for run-time date checking
        $dpStmt = $this->db->prepare("
            SELECT dwil.target_item_id, dpl.log_date, SUM(dwil.qty_done_today) AS total_qty
            FROM daily_work_item_logs dwil
            INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
            WHERE dpl.project_id = ?
            GROUP BY dwil.target_item_id, dpl.log_date
        ");
        $dpStmt->bind_param('i', $projectId);
        $dpStmt->execute();
        $dailyLogs = $dpStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $dailyProgressMap = [];
        foreach ($dailyLogs as $dl) {
            $itemId = (int)$dl['target_item_id'];
            $dDate = $dl['log_date'];
            $dailyProgressMap[$itemId][$dDate] = (float)$dl['total_qty'];
        }

        // Merge/Overlay quantities from phase_weekly_progress_items columns
        foreach ($weeklyDailyProgress as $itemId => $dates) {
            foreach ($dates as $dDate => $qty) {
                if (!isset($dailyProgressMap[$itemId][$dDate]) || $dailyProgressMap[$itemId][$dDate] < $qty) {
                    $dailyProgressMap[$itemId][$dDate] = $qty;
                }
            }
        }

        $today = date('Y-m-d');
        $validRiskCodes = [];
        $risksToSync = [];

        foreach ($targetItems as $item) {
            $itemId = (int)$item['target_item_id'];
            $targetMonthStr = $item['target_month'];
            
            // Calculate week boundaries
            $monthStart = new \DateTime(substr($targetMonthStr, 0, 7) . '-01');
            $monthEnd = new \DateTime($monthStart->format('Y-m-t'));
            
            $weeks = [];
            for ($w = 1; $w <= 4; $w++) {
                $baseDate = clone $monthStart;
                if ($w === 1) {
                    $start = $baseDate->format('Y-m-d');
                    $end   = (clone $baseDate)->modify('+6 days')->format('Y-m-d');
                } elseif ($w === 2) {
                    $start = (clone $baseDate)->modify('+7 days')->format('Y-m-d');
                    $end   = (clone $baseDate)->modify('+13 days')->format('Y-m-d');
                } elseif ($w === 3) {
                    $start = (clone $baseDate)->modify('+14 days')->format('Y-m-d');
                    $end   = (clone $baseDate)->modify('+20 days')->format('Y-m-d');
                } else {
                    $start = (clone $baseDate)->modify('+21 days')->format('Y-m-d');
                    $end   = $monthEnd->format('Y-m-d');
                    if ($end < $start) {
                        $end = (clone $baseDate)->modify('+27 days')->format('Y-m-d');
                    }
                }
                $weeks[$w] = ['start' => $start, 'end' => $end];
            }

            // Loop weeks
            for ($w = 1; $w <= 4; $w++) {
                $plannedQty = (float)($item['week_' . $w . '_qty'] ?? 0);
                if ($plannedQty <= 0) continue;

                $achievedQty = $achievedMap[$itemId][$w] ?? 0.00;
                $weekStart = $weeks[$w]['start'];
                $weekEnd   = $weeks[$w]['end'];
                
                // Rule 1: Week Target Missed (deadline has passed)
                if ($today > $weekEnd) {
                    if ($achievedQty < $plannedQty) {
                        $code = "AUTO-MISSED-W{$w}-{$itemId}";
                        $validRiskCodes[] = $code;
                        
                        $missRatio = ($plannedQty - $achievedQty) / $plannedQty;
                        $severity = $missRatio >= 0.50 ? 'High' : ($missRatio >= 0.25 ? 'Medium' : 'Low');
                        
                        $desc = "Week {$w} target missed for '{$item['item_name']}' in " . $monthStart->format('F Y') . ". ";
                        if (!empty($item['work_type_name'])) $desc .= "Category: {$item['work_type_name']}. ";
                        $desc .= "Planned: {$plannedQty} {$item['unit']}, Achieved: {$achievedQty} {$item['unit']}, Shortfall: " . ($plannedQty - $achievedQty) . " {$item['unit']}.";
                        
                        $risksToSync[$code] = [
                            'phase_id' => $item['phase_id'],
                            'category' => 'Progress',
                            'description' => $desc,
                            'impact' => $severity,
                            'likelihood' => 'High',
                            'severity' => $severity,
                            'missed_week' => $w,
                            'target_item_id' => $itemId,
                            'missed_qty' => $plannedQty - $achievedQty,
                            'planned_qty' => $plannedQty,
                            'achieved_qty' => $achievedQty
                        ];
                    }
                } else {
                    // Week is active or has started
                    if ($today >= $weekStart) {
                        // Rule 2: Not Started Target
                        if ($achievedQty == 0) {
                            $code = "AUTO-NOTSTARTED-W{$w}-{$itemId}";
                            $validRiskCodes[] = $code;
                            
                            $desc = "Week {$w} target has started but no work has been initiated for '{$item['item_name']}' in " . $monthStart->format('F Y') . ". ";
                            if (!empty($item['work_type_name'])) $desc .= "Category: {$item['work_type_name']}. ";
                            $desc .= "Planned: {$plannedQty} {$item['unit']}, Achieved: 0 {$item['unit']}.";
                            
                            $risksToSync[$code] = [
                                'phase_id' => $item['phase_id'],
                                'category' => 'Progress',
                                'description' => $desc,
                                'impact' => 'High',
                                'likelihood' => 'High',
                                'severity' => 'High',
                                'missed_week' => $w,
                                'target_item_id' => $itemId,
                                'missed_qty' => $plannedQty,
                                'planned_qty' => $plannedQty,
                                'achieved_qty' => 0.00
                            ];
                        }
                        
                        // Rule 3: Slow Progress / Burn-rate
                        if ($achievedQty > 0 && $achievedQty < $plannedQty) {
                            $tDays = (strtotime($weekEnd) - strtotime($weekStart)) / 86400 + 1;
                            $pDays = (strtotime($today) - strtotime($weekStart)) / 86400 + 1;
                            if ($pDays >= 2) {
                                $expectedRatio = min(1.0, $pDays / $tDays);
                                $expectedQty = $plannedQty * $expectedRatio;
                                if ($achievedQty < 0.75 * $expectedQty) {
                                    $code = "AUTO-BURNRATE-W{$w}-{$itemId}";
                                    $validRiskCodes[] = $code;
                                    
                                    $severity = ($achievedQty < 0.40 * $expectedQty) ? 'High' : 'Medium';
                                    
                                    $desc = "Week {$w} progress is slower than expected (low burn-rate) for '{$item['item_name']}' in " . $monthStart->format('F Y') . ". ";
                                    $desc .= "Expected: " . round($expectedQty, 1) . " {$item['unit']} (for {$pDays} days passed), Achieved: {$achievedQty} {$item['unit']}.";
                                    
                                    $risksToSync[$code] = [
                                        'phase_id' => $item['phase_id'],
                                        'category' => 'Progress',
                                        'description' => $desc,
                                        'impact' => $severity,
                                        'likelihood' => 'High',
                                        'severity' => $severity,
                                        'missed_week' => $w,
                                        'target_item_id' => $itemId,
                                        'missed_qty' => $plannedQty - $achievedQty,
                                        'planned_qty' => $plannedQty,
                                        'achieved_qty' => $achievedQty
                                    ];
                                }
                            }
                        }
                    }
                }

                // Rule 4: Missing Daily Log on Passed Days
                $endCheckDate = min($today, $weekEnd);
                $startCheckDate = $weekStart;
                
                // Adjust if item has custom start/end dates
                if (!empty($item['start_date']) && $item['start_date'] !== '0000-00-00') {
                    $startCheckDate = max($startCheckDate, $item['start_date']);
                }
                if (!empty($item['end_date']) && $item['end_date'] !== '0000-00-00') {
                    $endCheckDate = min($endCheckDate, $item['end_date']);
                }

                $curr = new \DateTime($startCheckDate);
                $checkLimit = new \DateTime($endCheckDate);
                
                // We check days that are strictly less than today (passed days)
                $todayDt = new \DateTime($today);

                while ($curr < $todayDt && $curr <= $checkLimit) {
                    $dStr = $curr->format('Y-m-d');
                    $loggedQty = $dailyProgressMap[$itemId][$dStr] ?? 0.00;
                    
                    if ($loggedQty <= 0) {
                        $code = "AUTO-NOLOG-D{$dStr}-{$itemId}";
                        $validRiskCodes[] = $code;
                        
                        $desc = "No daily progress logged for '{$item['item_name']}' on " . date('d M Y', strtotime($dStr)) . " (scheduled day in Week {$w}).";
                        
                        $risksToSync[$code] = [
                            'phase_id' => $item['phase_id'],
                            'category' => 'Progress',
                            'description' => $desc,
                            'impact' => 'Medium',
                            'likelihood' => 'High',
                            'severity' => 'Medium',
                            'missed_week' => $w,
                            'target_item_id' => $itemId,
                            'missed_qty' => 0.00,
                            'planned_qty' => 0.00,
                            'achieved_qty' => 0.00
                        ];
                    }
                    $curr->modify('+1 day');
                }
            }
        }

        // 4. Database Sync
        // Clean up resolved auto risks (is_auto=1 and risk_code NOT IN the current run's valid codes)
        if (!empty($validRiskCodes)) {
            $placeholders = implode(',', array_fill(0, count($validRiskCodes), '?'));
            $delSql = "DELETE FROM project_risks WHERE project_id = ? AND is_auto = 1 AND risk_code NOT IN ($placeholders)";
            $delParams = array_merge([$projectId], $validRiskCodes);
            $types = 'i' . str_repeat('s', count($validRiskCodes));
            
            $delStmt = $this->db->prepare($delSql);
            $delStmt->bind_param($types, ...$delParams);
            $delStmt->execute();
        } else {
            // Delete all auto risks for this project
            $delStmt = $this->db->prepare("DELETE FROM project_risks WHERE project_id = ? AND is_auto = 1");
            $delStmt->bind_param('i', $projectId);
            $delStmt->execute();
        }

        // Insert or update active risks
        foreach ($risksToSync as $code => $r) {
            // Check if exists
            $chk = $this->db->prepare("SELECT id FROM project_risks WHERE project_id = ? AND risk_code = ?");
            $chk->bind_param('is', $projectId, $code);
            $chk->execute();
            $exist = $chk->get_result()->fetch_assoc();

            if ($exist) {
                // Update description, severity, impact, quantities
                $upd = $this->db->prepare("
                    UPDATE project_risks SET
                        description = ?,
                        impact = ?,
                        severity = ?,
                        missed_qty = ?,
                        planned_qty = ?,
                        achieved_qty = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $upd->bind_param('sssdddi', 
                    $r['description'], 
                    $r['impact'], 
                    $r['severity'], 
                    $r['missed_qty'], 
                    $r['planned_qty'], 
                    $r['achieved_qty'], 
                    $exist['id']
                );
                $upd->execute();
            } else {
                // Insert new
                $ins = $this->db->prepare("
                    INSERT INTO project_risks (
                        project_id, phase_id, risk_code, category, description, impact, likelihood, severity,
                        missed_week, target_item_id, missed_qty, planned_qty, achieved_qty, is_auto, status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'Open')
                ");
                $ins->bind_param('iissssssiiddd',
                    $projectId,
                    $r['phase_id'],
                    $code,
                    $r['category'],
                    $r['description'],
                    $r['impact'],
                    $r['likelihood'],
                    $r['severity'],
                    $r['missed_week'],
                    $r['target_item_id'],
                    $r['missed_qty'],
                    $r['planned_qty'],
                    $r['achieved_qty']
                );
                $ins->execute();
            }
        }
    }

    // =========================================================================
    // PRIVATE — get missed weekly targets
    // =========================================================================
    private function getMissedWeeklyTargets($projectId) {
        $projectId = (int)$projectId;
        
        // 1. Fetch all target items
        $stmt = $this->db->prepare("
            SELECT
                pmti.id                  AS target_item_id,
                pmti.work_item_name      AS item_name,
                pmti.work_type_id,
                pmti.start_date,
                pmti.end_date,
                wt.work_type_name,
                pmti.unit,
                pmti.monthly_target_qty,
                pmti.week_1_qty,
                pmti.week_2_qty,
                pmti.week_3_qty,
                pmti.week_4_qty,
                pmt.target_month,
                pmt.id                   AS target_id,
                pmt.phase_id,
                pp.phase_name
            FROM phase_monthly_target_items pmti
            JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
            JOIN project_phases pp ON pp.id = pmt.phase_id
            LEFT JOIN work_types wt ON wt.id = pmti.work_type_id
            WHERE pmt.project_id = ?
            ORDER BY pmt.target_month ASC, pmti.id ASC
        ");
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $targetItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // 2. Fetch existing weekly progress achievements
        $wpStmt = $this->db->prepare("
            SELECT pwp.week_number, pwpi.target_item_id, pwpi.achieved_qty
            FROM phase_weekly_progress pwp
            INNER JOIN phase_weekly_progress_items pwpi ON pwpi.weekly_progress_id = pwp.id
            WHERE pwp.project_id = ?
        ");
        $wpStmt->bind_param('i', $projectId);
        $wpStmt->execute();
        $weeklyAchievements = $wpStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $achievedMap = [];
        foreach ($weeklyAchievements as $wa) {
            $itemId = (int)$wa['target_item_id'];
            $wk = (int)$wa['week_number'];
            $achievedMap[$itemId][$wk] = (float)$wa['achieved_qty'];
        }

        $today = date('Y-m-d');
        $missed = [];

        foreach ($targetItems as $item) {
            $itemId = (int)$item['target_item_id'];
            $targetMonthStr = $item['target_month'];
            
            // Calculate week boundaries
            $monthStart = new \DateTime(substr($targetMonthStr, 0, 7) . '-01');
            $monthEnd = new \DateTime($monthStart->format('Y-m-t'));
            
            $weeks = [];
            for ($w = 1; $w <= 4; $w++) {
                $baseDate = clone $monthStart;
                if ($w === 1) {
                    $start = $baseDate->format('Y-m-d');
                    $end   = (clone $baseDate)->modify('+6 days')->format('Y-m-d');
                } elseif ($w === 2) {
                    $start = (clone $baseDate)->modify('+7 days')->format('Y-m-d');
                    $end   = (clone $baseDate)->modify('+13 days')->format('Y-m-d');
                } elseif ($w === 3) {
                    $start = (clone $baseDate)->modify('+14 days')->format('Y-m-d');
                    $end   = (clone $baseDate)->modify('+20 days')->format('Y-m-d');
                } else {
                    $start = (clone $baseDate)->modify('+21 days')->format('Y-m-d');
                    $end   = $monthEnd->format('Y-m-d');
                    if ($end < $start) {
                        $end = (clone $baseDate)->modify('+27 days')->format('Y-m-d');
                    }
                }
                $weeks[$w] = ['start' => $start, 'end' => $end];
            }

            // Loop weeks
            for ($w = 1; $w <= 4; $w++) {
                $plannedQty = (float)($item['week_' . $w . '_qty'] ?? 0);
                if ($plannedQty <= 0) continue;

                $achievedQty = $achievedMap[$itemId][$w] ?? 0.00;
                $weekStart = $weeks[$w]['start'];
                $weekEnd   = $weeks[$w]['end'];

                $isMissed = false;
                
                // Missed Target: deadline passed, achieved < planned
                if ($today > $weekEnd && $achievedQty < $plannedQty) {
                    $isMissed = true;
                }
                // Not Started Target: week started, achieved = 0
                elseif ($today >= $weekStart && $achievedQty == 0) {
                    $isMissed = true;
                }
                // Slow progress: week active, achieved > 0 but slow
                elseif ($today >= $weekStart && $today <= $weekEnd && $achievedQty > 0) {
                    $tDays = (strtotime($weekEnd) - strtotime($weekStart)) / 86400 + 1;
                    $pDays = (strtotime($today) - strtotime($weekStart)) / 86400 + 1;
                    if ($pDays >= 2) {
                        $expectedQty = $plannedQty * min(1.0, $pDays / $tDays);
                        if ($achievedQty < 0.75 * $expectedQty) {
                            $isMissed = true;
                        }
                    }
                }

                if ($isMissed) {
                    $missed[] = [
                        'target_item_id' => $itemId,
                        'item_name'      => $item['item_name'],
                        'work_type_name' => $item['work_type_name'],
                        'unit'           => $item['unit'],
                        'phase_id'       => $item['phase_id'],
                        'phase_name'     => $item['phase_name'],
                        'target_id'      => $item['target_id'],
                        'target_month'   => $item['target_month'],
                        'week_number'    => $w,
                        'week_start'     => $weekStart,
                        'week_end'       => $weekEnd,
                        'planned_qty'    => $plannedQty,
                        'achieved_qty'   => $achievedQty,
                        'missed_qty'     => $plannedQty - $achievedQty,
                    ];
                }
            }
        }
        return $missed;
    }

    // =========================================================================
    // PRIVATE — auto-generate risk records for missed targets (idempotent)
    // =========================================================================
    private function autoGenerateRisks($projectId, array $missedTargets) {
        $this->syncRisksForProject($projectId);
    }
}

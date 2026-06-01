<?php
namespace Phase2;

use Controller;

class Con_MonthlyReviewController extends Controller {

    protected $db;

    public function __construct($db) {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login"); exit;
        }
        if (!in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 15, 22])) {
            setError("Access denied.");
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard"); exit;
        }
    }

    // =========================================================================
    // INDEX — Phase list with month pills for a project
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

        // All phases for this project
        $phStmt = $this->db->prepare("
            SELECT id, phase_name, phase_order, weight_percentage,
                   planned_start_date, planned_end_date,
                   actual_start_date, actual_end_date,
                   status, actual_progress, planned_progress
            FROM project_phases
            WHERE project_id = ?
            ORDER BY phase_order ASC
        ");
        $phStmt->bind_param('i', $projectId);
        $phStmt->execute();
        $phases = $phStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Monthly targets for all phases (group by phase_id)
        $tmStmt = $this->db->prepare("
            SELECT pmt.id, pmt.phase_id, pmt.target_month, pmt.planning_status,
                   pmt.responsible_team,
                   COUNT(pmti.id)                        AS item_count,
                   SUM(pmti.monthly_target_qty)          AS total_target_qty,
                   SUM(pmti.actual_qty)                  AS total_actual_qty,
                   SUM(pmti.planned_progress_pct)        AS avg_planned_pct,
                   SUM(pmti.actual_progress_pct)         AS avg_actual_pct
            FROM phase_monthly_targets pmt
            LEFT JOIN phase_monthly_target_items pmti ON pmti.target_id = pmt.id
            WHERE pmt.project_id = ?
            GROUP BY pmt.id
            ORDER BY pmt.target_month ASC
        ");
        $tmStmt->bind_param('i', $projectId);
        $tmStmt->execute();
        $allTargets = $tmStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Group targets by phase_id
        $targetsByPhase = [];
        foreach ($allTargets as $t) {
            $targetsByPhase[(int)$t['phase_id']][] = $t;
        }

        $this->view('phase2/monthly_review/index', [
            'current_route'  => 'con-monthly-review',
            'page_title'     => 'Monthly Review',
            'project'        => $project,
            'phases'         => $phases,
            'targetsByPhase' => $targetsByPhase,
        ]);
    }

    // =========================================================================
    // SHOW — Full review dashboard for one phase + one month
    // =========================================================================
    public function show($projectId, $phaseId, $yearMonth) {
        $projectId = (int)$projectId;
        $phaseId   = (int)$phaseId;
        // yearMonth: YYYY-MM, normalise to first of month
        $monthDate = date('Y-m-01', strtotime($yearMonth . '-01'));

        // Project
        $pStmt = $this->db->prepare("SELECT id, name FROM projects WHERE id=?");
        $pStmt->bind_param('i', $projectId);
        $pStmt->execute();
        $project = $pStmt->get_result()->fetch_assoc();
        if (!$project) { setError("Project not found."); header("Location:" . $GLOBALS['base_url'] . "/con-monthly-review/$projectId"); exit; }
        $_SESSION['active_project_id']   = $projectId;
        $_SESSION['active_project_name'] = $project['name'];

        // Phase
        $phStmt = $this->db->prepare("SELECT * FROM project_phases WHERE id=? AND project_id=?");
        $phStmt->bind_param('ii', $phaseId, $projectId);
        $phStmt->execute();
        $phase = $phStmt->get_result()->fetch_assoc();
        if (!$phase) { setError("Phase not found."); header("Location:" . $GLOBALS['base_url'] . "/con-monthly-review/$projectId"); exit; }

        // Monthly target header
        $mtStmt = $this->db->prepare("
            SELECT pmt.*
            FROM phase_monthly_targets pmt
            WHERE pmt.project_id=? AND pmt.phase_id=?
              AND DATE_FORMAT(pmt.target_month,'%Y-%m') = ?
            LIMIT 1
        ");
        $ym = date('Y-m', strtotime($monthDate));
        $mtStmt->bind_param('iis', $projectId, $phaseId, $ym);
        $mtStmt->execute();
        $monthlyTarget = $mtStmt->get_result()->fetch_assoc();
        if (!$monthlyTarget) {
            setError("No monthly target found for this phase/month.");
            header("Location:" . $GLOBALS['base_url'] . "/con-monthly-review/$projectId"); exit;
        }
        $targetId = (int)$monthlyTarget['id'];

        // Work items (phase_monthly_target_items) with work type name
        $itemsStmt = $this->db->prepare("
            SELECT pmti.*,
                   wt.work_type_name,
                   dt.name AS design_type_name
            FROM phase_monthly_target_items pmti
            LEFT JOIN work_types wt ON wt.id = pmti.work_type_id
            LEFT JOIN design_types dt ON dt.id = wt.design_type_id
            WHERE pmti.target_id = ?
            ORDER BY pmti.sort_order ASC, pmti.id ASC
        ");
        $itemsStmt->bind_param('i', $targetId);
        $itemsStmt->execute();
        $workItems = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Weekly progress sessions for this target (weeks 1–4)
        $weeksStmt = $this->db->prepare("
            SELECT pwp.*
            FROM phase_weekly_progress pwp
            WHERE pwp.target_id = ? AND pwp.project_id = ?
            ORDER BY pwp.week_number ASC
        ");
        $weeksStmt->bind_param('ii', $targetId, $projectId);
        $weeksStmt->execute();
        $weeklySessions = $weeksStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Weekly progress items aggregated per target_item_id and week
        $wpiStmt = $this->db->prepare("
            SELECT pwpi.*,
                   pwp.week_number,
                   pwp.week_start_date,
                   pwp.week_end_date,
                   pwp.overall_progress_pct AS week_overall_pct,
                   pwp.entry_status         AS week_status
            FROM phase_weekly_progress_items pwpi
            JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
            WHERE pwp.target_id = ? AND pwp.project_id = ?
            ORDER BY pwp.week_number ASC
        ");
        $wpiStmt->bind_param('ii', $targetId, $projectId);
        $wpiStmt->execute();
        $allWPI = $wpiStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Build per-item aggregate (sum achieved across all weeks)
        $itemAgg = []; // [target_item_id => ['achieved'=>X, 'status'=>..., 'responsible_team'=>..., 'has_issue'=>...]]
        foreach ($allWPI as $wpi) {
            $tid = (int)$wpi['target_item_id'];
            if (!isset($itemAgg[$tid])) {
                $itemAgg[$tid] = [
                    'achieved_sum' => 0,
                    'status'       => $wpi['status'],
                    'responsible_team' => $wpi['responsible_team'] ?? '',
                    'has_issue'    => 0,
                    'delay_reason' => '',
                    'weeks'        => [],
                ];
            }
            $itemAgg[$tid]['achieved_sum']    += (float)$wpi['achieved_qty'];
            $itemAgg[$tid]['has_issue']       += (int)$wpi['has_issue_flag'];
            if (!empty($wpi['remarks_action']) && empty($itemAgg[$tid]['delay_reason'])) {
                $itemAgg[$tid]['delay_reason'] = $wpi['remarks_action'];
            }
            if (!empty($wpi['responsible_team']) && empty($itemAgg[$tid]['responsible_team'])) {
                $itemAgg[$tid]['responsible_team'] = $wpi['responsible_team'];
            }
            // Last week status wins
            $itemAgg[$tid]['status'] = $wpi['status'];
            $itemAgg[$tid]['weeks'][$wpi['week_number']] = (float)$wpi['achieved_qty'];
        }

        // Team performance aggregation
        $teamPerf = []; // [team => ['planned_sum'=>, 'achieved_sum'=>, 'item_count'=>]]
        foreach ($workItems as $wi) {
            $team = !empty($wi['responsible_team']) ? $wi['responsible_team'] : 'Unassigned';
            $tid  = (int)$wi['id'];
            $agg  = $itemAgg[$tid] ?? null;
            $teamName = (!empty($agg['responsible_team'])) ? $agg['responsible_team'] : $team;
            if (!isset($teamPerf[$teamName])) {
                $teamPerf[$teamName] = ['planned_sum' => 0, 'achieved_sum' => 0, 'item_count' => 0];
            }
            $teamPerf[$teamName]['planned_sum']  += (float)$wi['planned_progress_pct'];
            $teamPerf[$teamName]['achieved_sum'] += (float)$wi['actual_progress_pct'];
            $teamPerf[$teamName]['item_count']++;
        }

        // Delay reasons from remarks + issue flags
        $delayMap = ['Labour Shortage' => 0, 'Material Delay' => 0, 'Drawing Pending' => 0, 'Weather Issue' => 0, 'Contractor Delay' => 0];
        foreach ($allWPI as $wpi) {
            if ($wpi['has_issue_flag']) {
                $rem = strtolower($wpi['remarks_action'] ?? '');
                if (str_contains($rem, 'labour') || str_contains($rem, 'labor'))  $delayMap['Labour Shortage']++;
                elseif (str_contains($rem, 'material'))                            $delayMap['Material Delay']++;
                elseif (str_contains($rem, 'draw') || str_contains($rem,'design')) $delayMap['Drawing Pending']++;
                elseif (str_contains($rem, 'weather') || str_contains($rem,'rain'))$delayMap['Weather Issue']++;
                else                                                                $delayMap['Contractor Delay']++;
            }
        }

        // Aggregate totals
        // Each item's planned_progress_pct is its share of the monthly plan (e.g. 70% + 30% = 100% total planned).
        // Use SUM not AVG so that the KPI reflects the full monthly plan coverage.
        
        // Find active week (latest logged week in this session)
        $activeWeek = 1;
        foreach ($weeklySessions as $ws) {
            if ((float)$ws['overall_progress_pct'] > 0 || $ws['entry_status'] !== 'Draft') {
                $activeWeek = max($activeWeek, (int)$ws['week_number']);
            }
        }

        $totalPlannedPct = 0;
        $totalPlannedProRated = 0;
        foreach ($workItems as $wi) {
            $plannedPct = (float)$wi['planned_progress_pct'];
            $totalPlannedPct += $plannedPct;

            $mTargetQty = (float)($wi['monthly_target_qty'] ?? 0);
            $plannedQtyUpToActiveWeek = 0;
            for ($w = 1; $w <= $activeWeek; $w++) {
                $plannedQtyUpToActiveWeek += (float)($wi['week_' . $w . '_qty'] ?? 0);
            }

            if ($mTargetQty > 0) {
                $totalPlannedProRated += $plannedPct * ($plannedQtyUpToActiveWeek / $mTargetQty);
            }
        }
        $totalPlannedProRated = min($totalPlannedPct, $totalPlannedProRated);

        $totalActualPct   = array_sum(array_column($workItems, 'actual_progress_pct'));
        $delayGap         = $totalActualPct - $totalPlannedProRated;
        $perfScore        = $totalPlannedProRated > 0 ? min(100, round($totalActualPct / $totalPlannedProRated * 100)) : 0;
        $riskStatus       = abs($delayGap) > 15 ? 'High Risk' : (abs($delayGap) > 5 ? 'Medium Risk' : 'Low Risk');
        $riskColor        = abs($delayGap) > 15 ? '#ef4444' : (abs($delayGap) > 5 ? '#f59e0b' : '#10b981');

        $this->view('phase2/monthly_review/show', [
            'current_route'      => 'con-monthly-review',
            'page_title'         => 'Monthly Review — ' . date('F Y', strtotime($monthDate)),
            'project'            => $project,
            'phase'              => $phase,
            'monthlyTarget'      => $monthlyTarget,
            'monthDate'          => $monthDate,
            'workItems'          => $workItems,
            'itemAgg'            => $itemAgg,
            'allWPI'             => $allWPI,
            'weeklySessions'     => $weeklySessions,
            'teamPerf'           => $teamPerf,
            'delayMap'           => $delayMap,
            'totalPlannedPct'    => $totalPlannedPct,
            'totalPlannedProRated'=> $totalPlannedProRated,
            'totalActualPct'     => $totalActualPct,
            'delayGap'           => $delayGap,
            'perfScore'          => $perfScore,
            'riskStatus'         => $riskStatus,
            'riskColor'          => $riskColor,
            'targetId'           => $targetId,
        ]);
    }
}

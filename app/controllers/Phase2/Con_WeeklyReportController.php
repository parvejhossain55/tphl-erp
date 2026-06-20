<?php
namespace Phase2;

use Controller;
use Con_MonthlyTarget;

class Con_WeeklyReportController extends Controller
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login"); exit;
        }
    }

    // =========================================================================
    // INDEX — Project List
    // =========================================================================
    public function index()
    {
        $userId = (int)($_SESSION['user']['id']      ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        // Role-based project filter
        if (in_array($roleId, [1, 3, 22])) {
            $filter = "p.deleted_at IS NULL";
        } else {
            $filter = "p.deleted_at IS NULL AND (
                p.pm_id = $userId OR p.site_engineer_id = $userId OR
                p.qc_id = $userId OR p.operator_id = $userId OR
                p.landman_id = $userId OR p.struct_engineer_id = $userId OR
                p.draftsman_id = $userId OR p.architect_id = $userId OR
                p.estimator_id = $userId OR p.created_by = $userId
            )";
        }

        $result = $this->db->query("
            SELECT p.id, p.name, p.block_no, p.plot_no, p.project_status,
                   COUNT(DISTINCT pmt.id)  AS total_targets,
                   COUNT(DISTINCT pwp.id)  AS total_weekly_sessions,
                   MAX(pwp.updated_at)     AS last_activity
            FROM projects p
            LEFT JOIN phase_monthly_targets pmt ON pmt.project_id = p.id
            LEFT JOIN phase_weekly_progress pwp ON pwp.project_id = p.id
            WHERE $filter
            GROUP BY p.id
            ORDER BY p.name ASC
        ");
        $projects = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        $this->view('phase2/weekly-reports/report-lists', [
            'current_route' => 'con-weekly-reports',
            'page_title'    => 'Weekly Progress Reports',
            'projects'      => $projects,
        ]);
    }

    // =========================================================================
    // SHOW — Project Report (with target + week selection)
    // =========================================================================
    public function show($projectId)
    {
        $projectId = (int)$projectId;
        $target_id = isset($_GET['target_id']) ? (int)$_GET['target_id'] : 0;
        $activeWeek = isset($_GET['week'])      ? (int)$_GET['week']      : 1;
        if ($activeWeek < 1 || $activeWeek > 4) $activeWeek = 1;

        // ── Project info ──────────────────────────────────────────────────────
        $pStmt = $this->db->prepare("SELECT id, name, block_no, plot_no FROM projects WHERE id = ?");
        $pStmt->bind_param('i', $projectId);
        $pStmt->execute();
        $project = $pStmt->get_result()->fetch_assoc();
        if (!$project) {
            setError("Project not found.");
            header("Location: " . $GLOBALS['base_url'] . "/con-weekly-reports"); exit;
        }

        // ── Monthly Targets for this project ──────────────────────────────────
        $targets = [];
        $tStmt = $this->db->prepare("
            SELECT pmt.id, pmt.target_month, pmt.planning_status,
                   pp.phase_name, pp.weight_percentage AS phase_weight
            FROM phase_monthly_targets pmt
            INNER JOIN project_phases pp ON pp.id = pmt.phase_id
            WHERE pmt.project_id = ?
            ORDER BY pmt.target_month DESC
        ");
        if ($tStmt) {
            $tStmt->bind_param('i', $projectId);
            $tStmt->execute();
            $targets = $tStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        // ── If a target is selected: load full weekly data ─────────────────────
        $phaseTarget  = null;
        $targetItems  = [];
        $weekData     = [];   // indexed by week_number (1-4)
        $weekPlanQty  = [1 => 0.0, 2 => 0.0, 3 => 0.0, 4 => 0.0];

        if ($target_id) {
            // A. Target header
            $thStmt = $this->db->prepare("
                SELECT pmt.*,
                       pp.phase_name, pp.weight_percentage AS phase_weight,
                       pp.planned_start_date, pp.planned_end_date,
                       p.name AS project_name,
                       u.full_name AS created_by_name
                FROM phase_monthly_targets pmt
                INNER JOIN project_phases pp ON pp.id = pmt.phase_id
                INNER JOIN projects p        ON p.id  = pmt.project_id
                LEFT  JOIN users u           ON u.id  = pmt.created_by
                WHERE pmt.id = ? AND pmt.project_id = ?
            ");
            if ($thStmt) {
                $thStmt->bind_param('ii', $target_id, $projectId);
                $thStmt->execute();
                $phaseTarget = $thStmt->get_result()->fetch_assoc();
            }

            if ($phaseTarget) {
                // B. Target items
                $monthlyTargetModel = new Con_MonthlyTarget($this->db);
                $targetItems = $monthlyTargetModel->getItems($target_id);

                // Pre-calc planned qty per week from target items
                foreach ($targetItems as $ti) {
                    $weekPlanQty[1] += (float)($ti['week_1_qty'] ?? 0);
                    $weekPlanQty[2] += (float)($ti['week_2_qty'] ?? 0);
                    $weekPlanQty[3] += (float)($ti['week_3_qty'] ?? 0);
                    $weekPlanQty[4] += (float)($ti['week_4_qty'] ?? 0);
                }

                // C. All weekly sessions for this target
                $wStmt = $this->db->prepare("
                    SELECT pwp.*,
                           u_sub.full_name AS submitted_by_name,
                           u_qc.full_name  AS qc_approver_name,
                           u_pm.full_name  AS pm_approver_name
                    FROM phase_weekly_progress pwp
                    LEFT JOIN users u_sub ON u_sub.id = pwp.submitted_by
                    LEFT JOIN users u_qc  ON u_qc.id  = pwp.qc_approved_id
                    LEFT JOIN users u_pm  ON u_pm.id  = pwp.pm_approved_id
                    WHERE pwp.target_id = ?
                    ORDER BY pwp.week_number ASC
                ");
                $sessions = [];
                if ($wStmt) {
                    $wStmt->bind_param('i', $target_id);
                    $wStmt->execute();
                    $sessions = $wStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                }

                // D. For each session: items + daily logs
                foreach ($sessions as $session) {
                    $wNo       = (int)$session['week_number'];
                    $sessionId = (int)$session['id'];

                    // Weekly items — fetch week_1..4_qty/pct for fallback planned qty/pct
                    $wiStmt = $this->db->prepare("
                        SELECT pwpi.*,
                               pmti.target_mode, pmti.monthly_target_pct,
                               pmti.week_1_pct, pmti.week_2_pct, pmti.week_3_pct, pmti.week_4_pct,
                               pmti.work_item_name, pmti.unit, pmti.monthly_target_qty,
                               pmti.week_1_qty, pmti.week_2_qty, pmti.week_3_qty, pmti.week_4_qty,
                               wt.work_type_name,
                               c.name AS contractor_name
                        FROM phase_weekly_progress_items pwpi
                        INNER JOIN phase_monthly_target_items pmti ON pmti.id = pwpi.target_item_id
                        LEFT  JOIN work_types  wt ON wt.id = pmti.work_type_id
                        LEFT  JOIN contractors c  ON c.id  = pwpi.contractor_id
                        WHERE pwpi.weekly_progress_id = ?
                        ORDER BY pmti.sort_order ASC, pwpi.id ASC
                    ");
                    $weekItems = [];
                    if ($wiStmt) {
                        $wiStmt->bind_param('i', $sessionId);
                        $wiStmt->execute();
                        $weekItems = $wiStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        // Attach the correct week's planned qty/pct as 'planned_qty_from_target'
                        foreach ($weekItems as &$wi) {
                            if (($wi['target_mode'] ?? 'qty') === 'pct') {
                                $wi['planned_qty_from_target'] = (float)($wi["week_{$wNo}_pct"] ?? 0);
                            } else {
                                $wi['planned_qty_from_target'] = (float)($wi["week_{$wNo}_qty"] ?? 0);
                            }
                        }
                        unset($wi);
                    }

                    // Daily progress logs
                    $dlStmt = $this->db->prepare("
                        SELECT dpl.*,
                               u_qc.full_name AS qc_name,
                               u_pm.full_name AS pm_name,
                               u_se.full_name AS reporter_name
                        FROM daily_progress_logs dpl
                        LEFT JOIN users u_qc ON u_qc.id = dpl.qc_approved_id
                        LEFT JOIN users u_pm ON u_pm.id = dpl.pm_approved_id
                        LEFT JOIN users u_se ON u_se.id = dpl.created_by
                        WHERE dpl.weekly_progress_id = ?
                        ORDER BY dpl.log_date ASC
                    ");
                    $dailyLogs = [];
                    if ($dlStmt) {
                        $dlStmt->bind_param('i', $sessionId);
                        $dlStmt->execute();
                        $dailyLogs = $dlStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    }

                    // Daily work items per log_date
                    $dailyItemsMap = [];
                    foreach ($dailyLogs as $dl) {
                        $logId = (int)$dl['id'];
                        $dwStmt = $this->db->prepare("
                            SELECT dwil.*,
                                   pmti.target_mode,
                                   pmti.work_item_name, pmti.unit,
                                   wt.work_type_name
                            FROM daily_work_item_logs dwil
                            INNER JOIN phase_monthly_target_items pmti ON pmti.id = dwil.target_item_id
                            LEFT  JOIN work_types wt ON wt.id = dwil.work_type_id
                            WHERE dwil.daily_log_id = ?
                            ORDER BY dwil.id ASC
                        ");
                        if ($dwStmt) {
                            $dwStmt->bind_param('i', $logId);
                            $dwStmt->execute();
                            $dailyItemsMap[$dl['log_date']] = $dwStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        } else {
                            $dailyItemsMap[$dl['log_date']] = [];
                        }
                    }

                    // Site photos from weekly items
                    $photos = array_filter(
                        array_column($weekItems, 'photo_path'),
                        fn($p) => !empty($p)
                    );

                    $weekData[$wNo] = [
                        'session'       => $session,
                        'items'         => $weekItems,
                        'dailyLogs'     => $dailyLogs,
                        'dailyItemsMap' => $dailyItemsMap,
                        'photos'        => array_values($photos),
                    ];
                }
            }
        }

        $this->view('phase2/weekly-reports/show-report', [
            'current_route' => 'con-weekly-reports',
            'page_title'    => 'Weekly Progress Report — ' . ($project['name'] ?? ''),
            'project'       => $project,
            'targets'       => $targets,
            'target_id'     => $target_id,
            'phaseTarget'   => $phaseTarget,
            'targetItems'   => $targetItems,
            'weekData'      => $weekData,
            'weekPlanQty'   => $weekPlanQty,
            'activeWeek'    => $activeWeek,
        ]);
    }

    // =========================================================================
    // AJAX — Monthly Targets by Project (replaces old ajaxWorkPlans)
    // =========================================================================
    public function ajaxWorkPlans()
    {
        header('Content-Type: application/json');
        $projectId = (int)($_GET['project_id'] ?? 0);
        if (!$projectId) { echo json_encode([]); exit; }

        $stmt = $this->db->prepare("
            SELECT pmt.id, pmt.target_month, pmt.planning_status,
                   pp.phase_name
            FROM phase_monthly_targets pmt
            INNER JOIN project_phases pp ON pp.id = pmt.phase_id
            WHERE pmt.project_id = ?
            ORDER BY pmt.target_month DESC
        ");
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode($rows);
        exit;
    }

    // =========================================================================
    // AJAX — Weekly sessions by Target (replaces old ajaxTargets)
    // =========================================================================
    public function ajaxTargets()
    {
        header('Content-Type: application/json');
        $targetId = (int)($_GET['target_id'] ?? 0);
        if (!$targetId) { echo json_encode([]); exit; }

        $stmt = $this->db->prepare("
            SELECT id, week_number, entry_status, overall_progress_pct,
                   week_start_date, week_end_date
            FROM phase_weekly_progress
            WHERE target_id = ?
            ORDER BY week_number ASC
        ");
        $stmt->bind_param('i', $targetId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode($rows);
        exit;
    }

    // Deprecated: create/store/approve redirect to new system
    public function create() {
        header("Location: " . $GLOBALS['base_url'] . "/con-weekly-reports"); exit;
    }
    public function store() {
        header("Location: " . $GLOBALS['base_url'] . "/con-weekly-reports"); exit;
    }
    public function approve($id) {
        header("Location: " . $GLOBALS['base_url'] . "/con-weekly-reports"); exit;
    }
}

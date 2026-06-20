<?php
namespace Phase2;

use Controller;

class Con_RecoveryPlanController extends Controller {

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
    // INDEX — Recovery Plan Dashboard for a project
    // =========================================================================
    public function index($projectId) {
        $projectId = (int)$projectId;
        $_SESSION['active_project_id'] = $projectId;

        // Project
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
        $phStmt = $this->db->prepare("SELECT id, phase_name FROM project_phases WHERE project_id=? ORDER BY phase_order ASC");
        $phStmt->bind_param('i', $projectId);
        $phStmt->execute();
        $phases = $phStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // All recovery plans for this project (newest first)
        $rpStmt = $this->db->prepare("
            SELECT rp.*, pp.phase_name
            FROM recovery_plans rp
            LEFT JOIN project_phases pp ON pp.id = rp.phase_id
            WHERE rp.project_id = ?
            ORDER BY rp.plan_month DESC
        ");
        $rpStmt->bind_param('i', $projectId);
        $rpStmt->execute();
        $recoveryPlans = $rpStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Active plan = newest Active, else newest Draft
        $activePlan = null;
        foreach ($recoveryPlans as $rp) {
            if ($rp['status'] === 'Active') { $activePlan = $rp; break; }
        }
        if (!$activePlan) {
            foreach ($recoveryPlans as $rp) {
                if ($rp['status'] === 'Draft') { $activePlan = $rp; break; }
            }
        }

        // Action items for the active plan
        $actionItems = [];
        if ($activePlan) {
            $aiStmt = $this->db->prepare("
                SELECT * FROM recovery_action_items
                WHERE plan_id = ?
                ORDER BY FIELD(priority,'Critical','High','Medium','Low'), created_at ASC
            ");
            $aiStmt->bind_param('i', $activePlan['id']);
            $aiStmt->execute();
            $actionItems = $aiStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        // KPIs for active plan
        $totalActions     = count($actionItems);
        $completedActions = 0;
        $inProgressActions= 0;
        $blockedActions   = 0;
        foreach ($actionItems as $ai) {
            if ($ai['status'] === 'Completed')  $completedActions++;
            if ($ai['status'] === 'In Progress') $inProgressActions++;
            if ($ai['status'] === 'Blocked')     $blockedActions++;
        }
        $completionPct = $totalActions > 0 ? round($completedActions / $totalActions * 100) : 0;

        // ── Auto-sync risks to get the latest count ─────────────────────────
        require_once __DIR__ . '/Con_RiskDashboardController.php';
        $riskCtrl = new Con_RiskDashboardController($this->db, false);
        $riskCtrl->syncRisksForProject($projectId);

        // Open risks count (from project_risks)
        $rStmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM project_risks WHERE project_id=? AND status='Open'");
        $rStmt->bind_param('i', $projectId);
        $rStmt->execute();
        $openRisksCount = (int)($rStmt->get_result()->fetch_assoc()['cnt'] ?? 0);

        $this->view('phase2/recovery-plan/index', [
            'current_route'    => 'con-recovery-plan',
            'page_title'       => 'Recovery Plan',
            'project'          => $project,
            'phases'           => $phases,
            'recoveryPlans'    => $recoveryPlans,
            'activePlan'       => $activePlan,
            'actionItems'      => $actionItems,
            'totalActions'     => $totalActions,
            'completedActions' => $completedActions,
            'inProgressActions'=> $inProgressActions,
            'blockedActions'   => $blockedActions,
            'completionPct'    => $completionPct,
            'openRisksCount'   => $openRisksCount,
        ]);
    }

    // =========================================================================
    // CREATE PLAN — save a new recovery plan
    // =========================================================================
    public function createPlan() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $GLOBALS['base_url'] . "/con-projects"); exit;
        }

        $projectId       = (int)($_POST['project_id'] ?? 0);
        $phaseId         = !empty($_POST['phase_id']) ? (int)$_POST['phase_id'] : null;
        $planMonth       = !empty($_POST['plan_month']) ? date('Y-m-01', strtotime($_POST['plan_month'] . '-01')) : date('Y-m-01');
        $delayCarry      = (float)($_POST['delay_carryforward_pct'] ?? 0);
        $recoveryTarget  = (float)($_POST['recovery_target_pct'] ?? 0);
        $addLabour       = (int)($_POST['additional_labour'] ?? 0);
        $extraHours      = (float)($_POST['extra_working_hours'] ?? 0);
        $pmNotes         = trim($_POST['pm_notes'] ?? '');
        $status          = $_POST['status'] ?? 'Draft';

        if (!$projectId) {
            setError("Project is required.");
            header("Location: " . $GLOBALS['base_url'] . "/con-recovery-plan/$projectId"); exit;
        }

        $stmt = $this->db->prepare("
            INSERT INTO recovery_plans
              (project_id, phase_id, plan_month, delay_carryforward_pct, recovery_target_pct,
               additional_labour, extra_working_hours, pm_notes, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('iisddidss', $projectId, $phaseId, $planMonth, $delayCarry, $recoveryTarget, $addLabour, $extraHours, $pmNotes, $status);
        $stmt->execute();

        setSuccess("Recovery plan created.");
        header("Location: " . $GLOBALS['base_url'] . "/con-recovery-plan/$projectId"); exit;
    }

    // =========================================================================
    // STORE ACTION ITEM
    // =========================================================================
    public function storeActionItem() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $GLOBALS['base_url'] . "/con-projects"); exit;
        }

        $planId      = (int)($_POST['plan_id'] ?? 0);
        $projectId   = (int)($_POST['project_id'] ?? 0);
        $category    = trim($_POST['category'] ?? 'Execution');
        $description = trim($_POST['action_description'] ?? '');
        $priority    = $_POST['priority'] ?? 'Medium';
        $owner       = trim($_POST['owner'] ?? '');
        $startDate   = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $dueDate     = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

        if (!$planId || !$projectId || empty($description)) {
            setError("Plan, project and action description are required.");
            header("Location: " . $GLOBALS['base_url'] . "/con-recovery-plan/$projectId"); exit;
        }

        $stmt = $this->db->prepare("
            INSERT INTO recovery_action_items
              (plan_id, project_id, category, action_description, priority, owner, start_date, due_date, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Open')
        ");
        $stmt->bind_param('iissssss', $planId, $projectId, $category, $description, $priority, $owner, $startDate, $dueDate);
        $stmt->execute();

        setSuccess("Action item added.");
        header("Location: " . $GLOBALS['base_url'] . "/con-recovery-plan/$projectId"); exit;
    }

    // =========================================================================
    // UPDATE ACTION STATUS
    // =========================================================================
    public function updateActionStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $GLOBALS['base_url'] . "/con-projects"); exit;
        }

        $actionId  = (int)($_POST['action_id'] ?? 0);
        $status    = $_POST['status'] ?? 'Open';
        $projectId = (int)($_POST['project_id'] ?? 0);

        $allowed = ['Open', 'In Progress', 'Completed', 'Blocked'];
        if (!in_array($status, $allowed)) $status = 'Open';

        $stmt = $this->db->prepare("UPDATE recovery_action_items SET status=? WHERE id=? AND project_id=?");
        $stmt->bind_param('sii', $status, $actionId, $projectId);
        $stmt->execute();

        setSuccess("Action item status updated.");
        header("Location: " . $GLOBALS['base_url'] . "/con-recovery-plan/$projectId"); exit;
    }
}

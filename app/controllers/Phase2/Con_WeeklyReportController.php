<?php
namespace Phase2;

use Controller;
use Con_WeeklyReport;

class Con_WeeklyReportController extends Controller
{
    protected $db;
    protected $model;

    public function __construct($db)
    {
        $this->db    = $db;
        $this->model = new Con_WeeklyReport($this->db);
    }

    // =========================================================================
    // LIST
    // =========================================================================
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $reports = $this->model->getAll();

        $this->view('phase2/weekly-reports/report-lists', [
            'current_route' => 'con-weekly-reports',
            'page_title'    => 'Weekly Progress Reports',
            'reports'       => $reports,
        ]);
    }

    // =========================================================================
    // CREATE FORM
    // =========================================================================
    public function create()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        $filter = "1=1";
        if (!in_array($roleId, [1, 3, 22])) {
            $filter = "(pm_id = $userId OR site_engineer_id = $userId OR landman_id = $userId OR struct_engineer_id = $userId OR draftsman_id = $userId OR architect_id = $userId OR estimator_id = $userId OR qc_id = $userId OR operator_id = $userId OR created_by = $userId)";
        }

        $projects = $this->db->query(
            "SELECT id, name FROM projects WHERE deleted_at IS NULL AND $filter ORDER BY name ASC"
        )->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/weekly-reports/create-report', [
            'current_route' => 'con-weekly-reports',
            'page_title'    => 'New Weekly Progress Report',
            'projects'      => $projects,
        ]);
    }

    // =========================================================================
    // AJAX: Work Plans by Project
    // =========================================================================
    public function ajaxWorkPlans()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');

        $projectId = (int)($_GET['project_id'] ?? 0);
        if (!$projectId) {
            echo json_encode([]);
            exit;
        }
        $plans = $this->model->getWorkPlansByProject($projectId);
        echo json_encode($plans);
        exit;
    }

    // =========================================================================
    // AJAX: Targets by Work Plan
    // =========================================================================
    public function ajaxTargets()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');

        $workPlanId = (int)($_GET['work_plan_id'] ?? 0);
        if (!$workPlanId) {
            echo json_encode([]);
            exit;
        }
        $targets = $this->model->getTargetsByWorkPlan($workPlanId);
        echo json_encode($targets);
        exit;
    }

    // =========================================================================
    // STORE (POST)
    // =========================================================================
    public function store()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        // Handle image upload
        $imagePath = null;
        if (!empty($_FILES['progress_image']['tmp_name'])) {
            $uploadDir = dirname(__DIR__, 3) . '/public/uploads/weekly-reports/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext  = pathinfo($_FILES['progress_image']['name'], PATHINFO_EXTENSION);
            $name = 'wpr_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['progress_image']['tmp_name'], $uploadDir . $name)) {
                $imagePath = 'uploads/weekly-reports/' . $name;
            }
        }

        $data = [
            'project_id'          => (int)($_POST['project_id']   ?? 0),
            'site_engineer_id'    => (int)($_POST['site_engineer_id'] ?? ($_SESSION['user']['id'] ?? 0)),
            'work_plan_id'        => (int)($_POST['work_plan_id']  ?? 0),
            'week_no'             => (int)($_POST['week_no']       ?? 0),
            'target_id'           => !empty($_POST['target_id']) ? (int)$_POST['target_id'] : null,
            'work_done'           => trim($_POST['work_done']      ?? ''),
            'progress_percentage' => (float)($_POST['progress_percentage'] ?? 0),
            'report_date'         => $_POST['report_date']         ?? date('Y-m-d'),
            'progress_image'      => $imagePath,
            'weather_condition'   => trim($_POST['weather_condition'] ?? ''),
            'manpower_count'      => !empty($_POST['manpower_count']) ? (int)$_POST['manpower_count'] : null,
            'created_by'          => $_SESSION['user']['id']       ?? null,
        ];

        if (!$data['project_id'] || !$data['work_plan_id'] || !$data['week_no'] || empty($data['work_done'])) {
            setError('Project, Working Plan, Week No and Work Done are required.');
            header("Location: {$GLOBALS['base_url']}/con-weekly-report-create");
            exit;
        }

        $id = $this->model->create($data);

        if ($id) {
            setSuccess('Weekly progress report saved successfully!');
            header("Location: {$GLOBALS['base_url']}/con-weekly-report/{$id}");
        } else {
            setError('Failed to save report. Please try again.');
            header("Location: {$GLOBALS['base_url']}/con-weekly-report-create");
        }
        exit;
    }

    // =========================================================================
    // SHOW DETAIL
    // =========================================================================
    public function show($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $report = $this->model->find($id);
        if (!$report) {
            setError('Report not found.');
            header("Location: {$GLOBALS['base_url']}/con-weekly-reports");
            exit;
        }

        $currentUserId = (int)($_SESSION['user']['id']   ?? 0);
        $currentRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $createdBy     = (int)($report['created_by'] ?? 0);

        // Role guard for approval
        $roleStepMap = [13 => 'qc', 15 => 'pm', 3 => 'pm', 22 => 'pm']; // 13=QC, 15/3/22=PM
        $approverRole    = $roleStepMap[$currentRoleId] ?? null;
        $canApprove      = ($approverRole !== null);

        // Already approved this step? Check DB directly (independent of canApprove)
        $stepCol         = ($approverRole === 'qc') ? 'approved_by_qc' : 'approved_by_pm';
        $alreadyApproved = !empty($report[$stepCol]);

        $this->view('phase2/weekly-reports/show-report', [
            'current_route'   => 'con-weekly-reports',
            'page_title'      => 'Weekly Report Detail',
            'report'          => $report,
            'canApprove'      => $canApprove,
            'alreadyApproved' => $alreadyApproved,
            'approverRole'    => $approverRole,
            'currentRoleId'   => $currentRoleId,
        ]);
    }

    // =========================================================================
    // APPROVE A STEP (QC / PM)
    // =========================================================================
    public function approve($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = (int)($_SESSION['user']['id']   ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        // Role guard
        $roleStepMap = [13 => 'qc', 15 => 'pm', 3 => 'pm', 22 => 'pm'];
        if (!isset($roleStepMap[$roleId])) {
            setError('You do not have permission to approve reports.');
            header("Location: {$GLOBALS['base_url']}/con-weekly-report/{$id}");
            exit;
        }

        // QC can approve any report (no self-approve restriction for QC)
        // PM/Director cannot self-approve
        $step = $roleStepMap[$roleId];
        if ($step === 'pm') {
            $report = $this->model->find($id);
            if ($report && (int)($report['created_by'] ?? 0) === $userId) {
                setError('You cannot approve a report that you created.');
                header("Location: {$GLOBALS['base_url']}/con-weekly-report/{$id}");
                exit;
            }
        }

        if ($step === 'qc') {
            $photoPath    = null;
            $fieldDetails = trim($_POST['qc_field_details'] ?? '');
            $labResult    = trim($_POST['qc_lab_result']    ?? '');
            $gainPct      = (float)($_POST['qc_gain_pct']   ?? 0);

            if (!empty($_FILES['qc_photo']['name']) && $_FILES['qc_photo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = dirname(__DIR__, 3) . '/public/uploads/qc-reports/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = time() . '_' . basename($_FILES['qc_photo']['name']);
                if (move_uploaded_file($_FILES['qc_photo']['tmp_name'], $uploadDir . $filename)) {
                    $photoPath = 'uploads/qc-reports/' . $filename;
                }
            }

            // Update weekly_progress_report with QC details
            $upd = $this->db->prepare("UPDATE weekly_progress_report SET qc_gain_percentage = ?, qc_photo = ?, qc_field_test_details = ?, qc_lab_test_result = ? WHERE id = ?");
            if ($upd) {
                $upd->bind_param('dsssi', $gainPct, $photoPath, $fieldDetails, $labResult, $id);
                $upd->execute();
            }
        }

        if ($this->model->approveStep($id, $step, $userId)) {
            // Update target_fixed if QC approved
            if ($step === 'qc') {
                $report = $this->model->find($id);
                if ($report && !empty($report['target_id'])) {
                    $targetId = (int)$report['target_id'];
                    $sumQ = $this->db->query("SELECT SUM(qc_gain_percentage) AS total_gain FROM weekly_progress_report WHERE target_id = {$targetId} AND approved_by_qc IS NOT NULL");
                    $totalGain = $sumQ ? (float)$sumQ->fetch_assoc()['total_gain'] : 0;
                    
                    $updTarget = $this->db->prepare("UPDATE target_fixed SET gain_percentage = ? WHERE id = ?");
                    if ($updTarget) {
                        $updTarget->bind_param('di', $totalGain, $targetId);
                        $updTarget->execute();
                    }
                }
            }
            
            setSuccess('Approval recorded successfully!');
        } else {
            setError('Approval failed.');
        }
        header("Location: {$GLOBALS['base_url']}/con-weekly-report/{$id}");
        exit;
    }
}

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
        if (session_status() === PHP_SESSION_NONE) session_start();

        $projects = $this->db->query(
            "SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name"
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

        $this->view('phase2/weekly-reports/show-report', [
            'current_route' => 'con-weekly-reports',
            'page_title'    => 'Weekly Report Detail',
            'report'        => $report,
        ]);
    }
}

<?php
namespace Phase2;

use Controller;
use Con_ProjectTimeline;
use Project;

class Con_ProjectTimelineController extends Controller {
    protected $db;
    protected $model;
    protected $projectModel;

    public function __construct($db) {
        $this->db = $db;
        $this->model = new Con_ProjectTimeline($db);
        $this->projectModel = new Project($db);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit;
        }
    }

    /**
     * Display timeline setup form & preview.
     */
    public function index($project_id) {
        $project_id = (int)$project_id;
        $project = $this->projectModel->find($project_id);

        if (!$project) {
            setError("Project not found.");
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        // Set session variables for project-specific sidebar context
        $_SESSION['active_project_id'] = $project['id'];
        $_SESSION['active_project_name'] = $project['name'];
        $_SESSION['active_project_workflow_version'] = $project['workflow_version'];

        $timeline = $this->model->getByProject($project_id);

        $this->view('phase2/project_timeline/index', [
            'current_route' => 'con-project-timeline',
            'page_title' => 'Project Timeline Setup',
            'project' => $project,
            'timeline' => $timeline
        ]);
    }

    /**
     * Store new timeline setup.
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $project_id = (int)($_POST['project_id'] ?? 0);
        $project = $this->projectModel->find($project_id);

        if (!$project) {
            setError("Project not found.");
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        // Permissions check
        if (!hasPermission('Construction Management') && !in_array($_SESSION['user']['role_id'] ?? 0, [1, 3])) {
            setError("You do not have permission to manage timelines.");
            header("Location: " . $GLOBALS['base_url'] . "/con-project/" . $project_id);
            exit;
        }

        $handover_months = (int)($_POST['handover_months'] ?? 0);
        $construction_months = (int)($_POST['construction_months'] ?? 0);
        $post_construction_months = (int)($_POST['post_construction_months'] ?? 6);

        if ($handover_months <= 0 || $construction_months <= 0) {
            setError("Timeline months must be positive integers.");
            header("Location: " . $GLOBALS['base_url'] . "/con-project-timeline/" . $project_id);
            exit;
        }

        if ($handover_months < ($construction_months + $post_construction_months)) {
            setError("Handover Timeline cannot be shorter than Construction + Post Construction months.");
            header("Location: " . $GLOBALS['base_url'] . "/con-project-timeline/" . $project_id);
            exit;
        }

        $data = [
            'project_id' => $project_id,
            'project_code' => trim($_POST['project_code'] ?? ''),
            'project_type' => trim($_POST['project_type'] ?? ''),
            'project_category' => trim($_POST['project_category'] ?? ''),
            'handover_months' => $handover_months,
            'construction_months' => $construction_months,
            'post_construction_months' => $post_construction_months,
            'project_start_date' => $_POST['project_start_date'] ?? '',
            'construction_start_date' => $_POST['construction_start_date'] ?? '',
            'construction_end_date' => $_POST['construction_end_date'] ?? '',
            'expected_handover_date' => $_POST['expected_handover_date'] ?? '',
            'current_status' => $_POST['current_status'] ?? 'Planning',
            'description' => trim($_POST['description'] ?? ''),
            'created_by' => $_SESSION['user']['id']
        ];

        // Check required fields
        if (empty($data['project_start_date']) || empty($data['construction_start_date']) || empty($data['construction_end_date']) || empty($data['expected_handover_date'])) {
            setError("All timeline dates are required.");
            header("Location: " . $GLOBALS['base_url'] . "/con-project-timeline/" . $project_id);
            exit;
        }

        if ($this->model->create($data)) {
            $_SESSION['active_project_workflow_version'] = 'phase_based';
            setSuccess("Project Timeline initialized successfully. Project upgraded to Phase-based workflow.");
        } else {
            setError("Failed to initialize timeline.");
        }

        header("Location: " . $GLOBALS['base_url'] . "/con-project-timeline/" . $project_id);
        exit;
    }

    /**
     * Show edit form (unified with index).
     */
    public function edit($id) {
        $id = (int)$id;
        $timeline = $this->model->find($id);

        if (!$timeline) {
            setError("Timeline not found.");
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $project_id = $timeline['project_id'];
        header("Location: " . $GLOBALS['base_url'] . "/con-project-timeline/" . $project_id);
        exit;
    }

    /**
     * Update existing timeline.
     */
    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $id = (int)$id;
        $timeline = $this->model->find($id);

        if (!$timeline) {
            setError("Timeline not found.");
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $project_id = $timeline['project_id'];

        // Permissions check
        if (!hasPermission('Construction Management') && !in_array($_SESSION['user']['role_id'] ?? 0, [1, 3])) {
            setError("You do not have permission to manage timelines.");
            header("Location: " . $GLOBALS['base_url'] . "/con-project/" . $project_id);
            exit;
        }

        $handover_months = (int)($_POST['handover_months'] ?? 0);
        $construction_months = (int)($_POST['construction_months'] ?? 0);
        $post_construction_months = (int)($_POST['post_construction_months'] ?? 6);

        if ($handover_months <= 0 || $construction_months <= 0) {
            setError("Timeline months must be positive integers.");
            header("Location: " . $GLOBALS['base_url'] . "/con-project-timeline/" . $project_id);
            exit;
        }

        if ($handover_months < ($construction_months + $post_construction_months)) {
            setError("Handover Timeline cannot be shorter than Construction + Post Construction months.");
            header("Location: " . $GLOBALS['base_url'] . "/con-project-timeline/" . $project_id);
            exit;
        }

        $data = [
            'project_code' => trim($_POST['project_code'] ?? ''),
            'project_type' => trim($_POST['project_type'] ?? ''),
            'project_category' => trim($_POST['project_category'] ?? ''),
            'handover_months' => $handover_months,
            'construction_months' => $construction_months,
            'post_construction_months' => $post_construction_months,
            'project_start_date' => $_POST['project_start_date'] ?? '',
            'construction_start_date' => $_POST['construction_start_date'] ?? '',
            'construction_end_date' => $_POST['construction_end_date'] ?? '',
            'expected_handover_date' => $_POST['expected_handover_date'] ?? '',
            'current_status' => $_POST['current_status'] ?? 'Planning',
            'description' => trim($_POST['description'] ?? '')
        ];

        if (empty($data['project_start_date']) || empty($data['construction_start_date']) || empty($data['construction_end_date']) || empty($data['expected_handover_date'])) {
            setError("All timeline dates are required.");
            header("Location: " . $GLOBALS['base_url'] . "/con-project-timeline/" . $project_id);
            exit;
        }

        if ($this->model->update($id, $data)) {
            setSuccess("Project Timeline updated successfully.");
        } else {
            setError("Failed to update timeline.");
        }

        header("Location: " . $GLOBALS['base_url'] . "/con-project-timeline/" . $project_id);
        exit;
    }
}

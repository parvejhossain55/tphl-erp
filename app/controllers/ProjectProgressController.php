<?php

class ProjectProgressController extends Controller
{
    protected $db;
    protected $progressModel;
    protected $projectModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->progressModel = new ProjectProgress($this->db);
        $this->projectModel = new Project($this->db);
    }

    /**
     * List all project progress records
     */
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $selectedProjectId = !empty($_GET['project_id']) ? (int)$_GET['project_id'] : null;
        $progressList = $this->progressModel->getAll($selectedProjectId);
        $projects = $this->projectModel->getAll();

        $this->view("/sales-developments/project-progress/index", [
            "current_route"       => "project-progress-lists",
            "page_title"          => "Project Progress",
            "progress_list"       => $progressList,
            "projects"            => $projects,
            "selected_project_id" => $selectedProjectId,
        ]);
    }

    /**
     * Show form to add project progress
     */
    public function create()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $projects = $this->projectModel->getAll();
        $selectedProjectId = !empty($_GET['project_id']) ? (int)$_GET['project_id'] : null;

        $this->view("/sales-developments/project-progress/create", [
            "current_route"       => "create-project-progress",
            "page_title"          => "Add Project Progress",
            "projects"            => $projects,
            "selected_project_id" => $selectedProjectId,
        ]);
    }

    /**
     * Store new project progress record
     */
    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $projectId = intval($_POST['project_id'] ?? 0);
        if (!$projectId) {
            setError("Please select a Project!");
            header("Location: {$GLOBALS['base_url']}/create-project-progress");
            exit;
        }

        $project = $this->projectModel->find($projectId);
        $projectName = $project['name'] ?? 'Project';

        $data = [
            'project_id'          => $projectId,
            'last_updated_date'   => !empty($_POST['last_updated_date']) ? $_POST['last_updated_date'] : date('Y-m-d'),
            'last_month_target'   => floatval($_POST['last_month_target'] ?? 0),
            'last_month_achieved' => floatval($_POST['last_month_achieved'] ?? 0),
            'structural_work'     => floatval($_POST['structural_work'] ?? 0),
            'plaster_work'        => floatval($_POST['plaster_work'] ?? 0),
            'brick_work'          => floatval($_POST['brick_work'] ?? 0),
            'electrical_work'     => floatval($_POST['electrical_work'] ?? 0),
            'plumbing_work'       => floatval($_POST['plumbing_work'] ?? 0),
            'tiles_work'          => floatval($_POST['tiles_work'] ?? 0),
            'paint_work'          => floatval($_POST['paint_work'] ?? 0),
            'created_by'          => $_SESSION['user']['id'] ?? null,
        ];

        $insertId = $this->progressModel->create($data);

        if ($insertId) {
            setSuccess("Project progress updated for '{$projectName}' successfully!");
        } else {
            setError("Failed to save project progress. Please try again.");
        }

        header("Location: {$GLOBALS['base_url']}/project-progress-lists?project_id={$projectId}");
        exit;
    }

    /**
     * Show form to edit progress record
     */
    public function edit($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $progress = $this->progressModel->find((int)$id);
        if (!$progress) {
            setError("Progress record not found!");
            header("Location: {$GLOBALS['base_url']}/project-progress-lists");
            exit;
        }

        $projects = $this->projectModel->getAll();

        $this->view("/sales-developments/project-progress/edit", [
            "current_route" => "edit-project-progress",
            "page_title"    => "Edit Project Progress",
            "progress"      => $progress,
            "projects"      => $projects,
        ]);
    }

    /**
     * Update progress record
     */
    public function update($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $id = (int)$id;
        $progress = $this->progressModel->find($id);
        if (!$progress) {
            setError("Progress record not found!");
            header("Location: {$GLOBALS['base_url']}/project-progress-lists");
            exit;
        }

        $projectId = intval($_POST['project_id'] ?? $progress['project_id']);

        $data = [
            'project_id'          => $projectId,
            'last_updated_date'   => !empty($_POST['last_updated_date']) ? $_POST['last_updated_date'] : date('Y-m-d'),
            'last_month_target'   => floatval($_POST['last_month_target'] ?? 0),
            'last_month_achieved' => floatval($_POST['last_month_achieved'] ?? 0),
            'structural_work'     => floatval($_POST['structural_work'] ?? 0),
            'plaster_work'        => floatval($_POST['plaster_work'] ?? 0),
            'brick_work'          => floatval($_POST['brick_work'] ?? 0),
            'electrical_work'     => floatval($_POST['electrical_work'] ?? 0),
            'plumbing_work'       => floatval($_POST['plumbing_work'] ?? 0),
            'tiles_work'          => floatval($_POST['tiles_work'] ?? 0),
            'paint_work'          => floatval($_POST['paint_work'] ?? 0),
            'updated_by'          => $_SESSION['user']['id'] ?? null,
        ];

        $success = $this->progressModel->update($id, $data);

        if ($success) {
            setSuccess("Project progress updated successfully!");
        } else {
            setError("Failed to update project progress.");
        }

        header("Location: {$GLOBALS['base_url']}/project-progress-lists?project_id={$projectId}");
        exit;
    }

    /**
     * Delete progress record
     */
    public function delete($id = null)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $id = (int)($id ?: ($_POST['id'] ?? 0));
        if (!$id) {
            setError("Invalid request!");
            header("Location: {$GLOBALS['base_url']}/project-progress-lists");
            exit;
        }

        $progress = $this->progressModel->find($id);
        $projectId = $progress['project_id'] ?? null;

        $deleted = $this->progressModel->delete($id);

        if ($deleted) {
            setSuccess("Progress record removed successfully!");
        } else {
            setError("Failed to delete progress record.");
        }

        $redirect = "{$GLOBALS['base_url']}/project-progress-lists" . ($projectId ? "?project_id={$projectId}" : "");
        header("Location: {$redirect}");
        exit;
    }
}

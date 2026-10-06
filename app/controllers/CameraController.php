<?php

class CameraController extends Controller
{
    protected $db;
    protected $cameraModel;
    protected $projectModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->cameraModel = new Camera($this->db);
        $this->projectModel = new Project($this->db);
    }

    /**
     * List all cameras, with project-wise filtering and details
     */
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $selectedProjectId = !empty($_GET['project_id']) ? (int)$_GET['project_id'] : null;
        $cameras = $this->cameraModel->getAll($selectedProjectId);
        $projects = $this->projectModel->getAll();

        $this->view("/cameras/camera-lists", [
            "current_route"       => "camera-lists",
            "page_title"          => "Project Cameras",
            "cameras"             => $cameras,
            "projects"            => $projects,
            "selected_project_id" => $selectedProjectId,
        ]);
    }

    /**
     * Show form to add camera
     */
    public function create()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $projects = $this->projectModel->getAll();
        $selectedProjectId = !empty($_GET['project_id']) ? (int)$_GET['project_id'] : null;

        $this->view("/cameras/create-camera", [
            "current_route"       => "create-camera",
            "page_title"          => "Add Project Camera",
            "projects"            => $projects,
            "selected_project_id" => $selectedProjectId,
        ]);
    }

    /**
     * Store new camera (Supports both single and bulk camera additions)
     */
    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $projectId = intval($_POST['project_id'] ?? 0);
        if (!$projectId) {
            setError("Please select a Project!");
            header("Location: {$GLOBALS['base_url']}/create-camera");
            exit;
        }

        $project = $this->projectModel->find($projectId);
        $projectName = $project['name'] ?? 'Project';

        // Prepare camera rows (from bulk array or fallback single input)
        $cameraEntries = [];
        if (!empty($_POST['cameras']) && is_array($_POST['cameras'])) {
            $cameraEntries = $_POST['cameras'];
        } elseif (!empty($_POST['web_link'])) {
            $cameraEntries = [[
                'web_link'    => $_POST['web_link'] ?? '',
                'camera_name' => $_POST['camera_name'] ?? '',
                'rtsp_url'    => $_POST['rtsp_url'] ?? '',
                'status'      => $_POST['status'] ?? 'active',
            ]];
        }

        // Filter out entries with completely blank web_link
        $validEntries = [];
        foreach ($cameraEntries as $entry) {
            $webLink = trim($entry['web_link'] ?? '');
            if (!empty($webLink)) {
                $validEntries[] = $entry;
            }
        }

        if (empty($validEntries)) {
            setError("Please provide at least one valid Web Link!");
            header("Location: {$GLOBALS['base_url']}/create-camera?project_id={$projectId}");
            exit;
        }

        $existingCount = count($this->cameraModel->getByProjectId($projectId, false));
        $savedCount = 0;

        foreach ($validEntries as $index => $entry) {
            $webLink    = trim($entry['web_link'] ?? '');
            $cameraName = trim($entry['camera_name'] ?? '');
            $rtspUrl    = trim($entry['rtsp_url'] ?? '');
            $status     = trim($entry['status'] ?? 'active');

            if (!$cameraName) {
                $cameraName = $projectName . ' - Cam ' . ($existingCount + 1 + $savedCount);
            }

            $cameraSlug = strtolower(trim(preg_replace('/[^\w]+/', '_', $cameraName))) . '_' . time() . '_' . $index;

            $insertId = $this->cameraModel->create([
                'project_id'  => $projectId,
                'camera_name' => $cameraName,
                'camera_slug' => $cameraSlug,
                'rtsp_url'    => $rtspUrl ?: null,
                'web_link'    => $webLink,
                'status'      => in_array($status, ['active', 'inactive']) ? $status : 'active',
            ]);

            if ($insertId) {
                $savedCount++;
            }
        }

        if ($savedCount > 0) {
            setSuccess("Successfully added {$savedCount} camera(s) to '{$projectName}'!");
        } else {
            setError("Failed to add cameras. Please try again.");
        }

        header("Location: {$GLOBALS['base_url']}/camera-lists?project_id={$projectId}");
        exit;
    }

    /**
     * Show form to edit camera
     */
    public function edit($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $camera = $this->cameraModel->find((int)$id);
        if (!$camera) {
            setError("Camera not found!");
            header("Location: {$GLOBALS['base_url']}/camera-lists");
            exit;
        }

        $projects = $this->projectModel->getAll();

        $this->view("/cameras/edit-camera", [
            "current_route" => "edit-camera",
            "page_title"    => "Edit Project Camera",
            "camera"        => $camera,
            "projects"      => $projects,
        ]);
    }

    /**
     * Update existing camera
     */
    public function update($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $id     = (int)$id;
        $camera = $this->cameraModel->find($id);
        if (!$camera) {
            setError("Camera not found!");
            header("Location: {$GLOBALS['base_url']}/camera-lists");
            exit;
        }

        $projectId   = intval($_POST['project_id'] ?? 0);
        $webLink     = trim($_POST['web_link'] ?? '');
        $cameraName  = trim($_POST['camera_name'] ?? '');
        $rtspUrl     = trim($_POST['rtsp_url'] ?? '');
        $status      = trim($_POST['status'] ?? 'active');

        if (!$projectId) {
            setError("Please select a Project!");
            header("Location: {$GLOBALS['base_url']}/edit-camera/{$id}");
            exit;
        }

        if (!$webLink) {
            setError("Web Link is required!");
            header("Location: {$GLOBALS['base_url']}/edit-camera/{$id}");
            exit;
        }

        if (!$cameraName) {
            $project = $this->projectModel->find($projectId);
            $projectName = $project['name'] ?? 'Project';
            $cameraName = $projectName . ' - Cam';
        }

        $cameraSlug = $camera['camera_slug'] ?: (strtolower(trim(preg_replace('/[^\w]+/', '_', $cameraName))) . '_' . time());

        $ok = $this->cameraModel->update($id, [
            'project_id'  => $projectId,
            'camera_name' => $cameraName,
            'camera_slug' => $cameraSlug,
            'rtsp_url'    => $rtspUrl ?: null,
            'web_link'    => $webLink,
            'status'      => in_array($status, ['active', 'inactive']) ? $status : 'active',
        ]);

        if ($ok) {
            setSuccess("Project camera link updated successfully!");
        } else {
            setError("Failed to update camera link.");
        }

        header("Location: {$GLOBALS['base_url']}/camera-lists" . ($projectId ? "?project_id={$projectId}" : ''));
        exit;
    }

    /**
     * Delete camera
     */
    public function delete($id = null)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $deleteId = (int)($id ?: ($_POST['id'] ?? 0));
        if (!$deleteId) {
            setError("Invalid camera ID.");
            header("Location: {$GLOBALS['base_url']}/camera-lists");
            exit;
        }

        $camera = $this->cameraModel->find($deleteId);
        $projectId = $camera['project_id'] ?? null;

        if ($this->cameraModel->delete($deleteId)) {
            setSuccess("Project camera link deleted successfully!");
        } else {
            setError("Failed to delete camera link.");
        }

        header("Location: {$GLOBALS['base_url']}/camera-lists" . ($projectId ? "?project_id={$projectId}" : ''));
        exit;
    }
}

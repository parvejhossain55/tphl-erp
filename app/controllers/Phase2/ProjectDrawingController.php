<?php
namespace Phase2;

use Controller;
use Con_ProjectDrawing;

class ProjectDrawingController extends Controller {
    protected $db;
    protected $model;

    public function __construct($db) {
        $this->db    = $db;
        $this->model = new Con_ProjectDrawing($db);

        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit;
        }
    }

    public function index() {
        $records = $this->model->getAll();

        $this->view('phase2/project_drawings/project-drawings', [
            'current_route' => 'con-project-drawings',
            'records' => $records,
            'page_title'   => 'Project Drawings'
        ]);
    }

    public function create() {
        $projects     = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        $design_types = $this->db->query("SELECT id, name FROM design_types ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        $users        = $this->db->query("SELECT id, full_name FROM users ORDER BY full_name ASC")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/project_drawings/create-project-drawing', [
            'current_route' => 'con-project-drawings',
            'page_title'        => 'Add Project Drawing',
            'projects'     => $projects,
            'design_types' => $design_types,
            'users'        => $users,
        ]);
    }

    public function store() {
        $data = [
            'project_id'                    => $_POST['project_id'] ?? 0,
            'design_type_id'                => $_POST['design_type_id'] ?? null,
            'is_in_house'                   => $_POST['is_in_house'] ?? 1,
            'user_id'                       => $_POST['user_id'] ?? null,
            'designer_name'                 => $_POST['designer_name'] ?? null,
            'designer_designation'          => $_POST['designer_designation'] ?? null,
            'designer_company'              => $_POST['designer_company'] ?? null,
            'design_link'                   => $_POST['design_link'] ?? null,
            'version'                       => $_POST['version'] ?? '1.0',
            'status'                        => $_POST['status'] ?? 'Draft',
            'created_by'                    => $_SESSION['user']['id'],
        ];

        if ($this->model->create($data)) {
            setSuccess("Project drawing created successfully.");
        } else {
            setError("Failed to create drawing.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-project-drawings");
        exit;
    }

    public function edit() {
        $id     = $_GET['id'] ?? 0;
        $record = $this->model->find($id);

        if (!$record) {
            setError("Record not found.");
            header("Location: " . $GLOBALS['base_url'] . "/con-project-drawings");
            exit;
        }

        $projects     = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        $design_types = $this->db->query("SELECT id, name FROM design_types ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        $users        = $this->db->query("SELECT id, full_name FROM users ORDER BY full_name ASC")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/project_drawings/edit-project-drawing', [
            'current_route' => 'con-project-drawings',
            'page_title'        => 'Edit Project Drawing',
            'record'       => $record,
            'projects'     => $projects,
            'design_types' => $design_types,
            'users'        => $users,
        ]);
    }

    public function update() {
        $id   = $_POST['id'] ?? 0;
        $data = [
            'project_id'           => $_POST['project_id'] ?? 0,
            'design_type_id'       => $_POST['design_type_id'] ?? null,
            'is_in_house'          => $_POST['is_in_house'] ?? 1,
            'user_id'              => $_POST['user_id'] ?? null,
            'designer_name'        => $_POST['designer_name'] ?? null,
            'designer_designation' => $_POST['designer_designation'] ?? null,
            'designer_company'     => $_POST['designer_company'] ?? null,
            'design_link'          => $_POST['design_link'] ?? null,
            'version'              => $_POST['version'] ?? '1.0',
            'status'               => $_POST['status'] ?? 'Draft',
        ];

        if ($this->model->update($id, $data)) {
            setSuccess("Updated successfully.");
        } else {
            setError("Failed to update drawing.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-project-drawings");
        exit;
    }

    public function delete() {
        $id = $_POST['id'] ?? 0;
        if ($this->model->delete($id)) {
            setSuccess("Deleted successfully.");
        } else {
            setError("Failed to delete.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-project-drawings");
        exit;
    }
}

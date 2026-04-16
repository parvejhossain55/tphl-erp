<?php
namespace Phase2;

use Controller;
use Con_ArchitectEngineering;

class ArchitectEngineeringController extends Controller {
    protected $db;
    protected $model;

    public function __construct($db) {
        $this->db = $db;
        $this->model = new Con_ArchitectEngineering($db);

        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit;
        }
    }

    public function index() {
        $records = $this->model->getAll();

        $this->view('phase2/architect_engineering/architect-engineering', [
            'current_route' => 'con-architect-engineering',
            'records' => $records,
            'title'   => 'Architect & Engineering'
        ]);
    }

    public function create() {
        $projects = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        $users    = $this->db->query("SELECT id, full_name FROM users ORDER BY full_name ASC")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/architect_engineering/create-architect-engineering', [
            'current_route' => 'con-architect-engineering',
            'title'    => 'Add Architect & Engineering',
            'projects' => $projects,
            'users'    => $users,
        ]);
    }

    public function store() {
        $data = [
            'project_id'                    => $_POST['project_id'] ?? 0,
            'user_id'                       => $_POST['user_id'] ?? null,
            'full_architecture_design_link' => $_POST['full_architecture_design_link'] ?? null,
            'notes'                         => $_POST['notes'] ?? null,
            'created_by'                    => $_SESSION['user']['id'],
        ];

        if ($this->model->create($data)) {
            setSuccess("Architect & Engineering record created successfully.");
        } else {
            setError("Failed to create record.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-architect-engineering");
        exit;
    }

    public function edit() {
        $id     = $_GET['id'] ?? 0;
        $record = $this->model->find($id);

        if (!$record) {
            setError("Record not found.");
            header("Location: " . $GLOBALS['base_url'] . "/con-architect-engineering");
            exit;
        }

        $projects = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        $users    = $this->db->query("SELECT id, full_name FROM users ORDER BY full_name ASC")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/architect_engineering/edit-architect-engineering', [
            'current_route' => 'con-architect-engineering',
            'title'    => 'Edit Architect & Engineering',
            'record'   => $record,
            'projects' => $projects,
            'users'    => $users,
        ]);
    }

    public function update() {
        $id   = $_POST['id'] ?? 0;
        $data = [
            'project_id'                    => $_POST['project_id'] ?? 0,
            'user_id'                       => $_POST['user_id'] ?? null,
            'full_architecture_design_link' => $_POST['full_architecture_design_link'] ?? null,
            'notes'                         => $_POST['notes'] ?? null,
        ];

        if ($this->model->update($id, $data)) {
            setSuccess("Updated successfully.");
        } else {
            setError("Failed to update record.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-architect-engineering");
        exit;
    }

    public function delete() {
        $id = $_POST['id'] ?? 0;
        if ($this->model->delete($id)) {
            setSuccess("Deleted successfully.");
        } else {
            setError("Failed to delete record.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-architect-engineering");
        exit;
    }
}

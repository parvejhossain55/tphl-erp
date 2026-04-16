<?php
namespace Phase2;

use Controller;
use Con_DesignType;

class DesignTypeController extends Controller {
    protected $db;
    protected $model;

    public function __construct($db) {
        $this->db    = $db;
        $this->model = new Con_DesignType($db);

        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit;
        }
    }

    public function index() {
        $records = $this->model->getAll();

        $this->view('phase2/design_types/design-types', [
            'current_route' => 'con-design-types',
            'records' => $records,
            'title'   => 'Design Types'
        ]);
    }

    public function create() {
        $this->view('phase2/design_types/create-design-type', [
            'title' => 'Add Design Type'
        ]);
    }

    public function store() {
        $data = [
            'name'       => trim($_POST['name'] ?? ''),
            'status'     => $_POST['status'] ?? 'active',
            'created_by' => $_SESSION['user']['id'],
        ];

        if (empty($data['name'])) {
            setError("Design type name is required.");
            header("Location: " . $GLOBALS['base_url'] . "/con-design-types/create");
            exit;
        }

        if ($this->model->create($data)) {
            setSuccess("Design type created successfully.");
        } else {
            setError("Failed to create design type.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-design-types");
        exit;
    }

    public function edit() {
        $id     = $_GET['id'] ?? 0;
        $record = $this->model->find($id);

        if (!$record) {
            setError("Record not found.");
            header("Location: " . $GLOBALS['base_url'] . "/con-design-types");
            exit;
        }

        $this->view('phase2/design_types/edit-design-type', [
            'title'  => 'Edit Design Type',
            'record' => $record,
        ]);
    }

    public function update() {
        $id   = $_POST['id'] ?? 0;
        $data = [
            'name'   => trim($_POST['name'] ?? ''),
            'status' => $_POST['status'] ?? 'active',
        ];

        if ($this->model->update($id, $data)) {
            setSuccess("Updated successfully.");
        } else {
            setError("Failed to update.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-design-types");
        exit;
    }

    public function delete() {
        $id = $_POST['id'] ?? 0;
        if ($this->model->delete($id)) {
            setSuccess("Deleted successfully.");
        } else {
            setError("Failed to delete.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-design-types");
        exit;
    }
}

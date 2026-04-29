<?php
namespace Phase2;

use Controller;
use Con_WorkType;
use Con_DesignType;

class Con_WorkTypeController extends Controller {
    protected $db;
    protected $model;
    protected $designTypeModel;

    public function __construct($db) {
        $this->db    = $db;
        $this->model = new Con_WorkType($db);
        $this->designTypeModel = new Con_DesignType($db);

        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit;
        }
    }

    public function index() {
        $records = $this->model->getAll();

        $this->view('phase2/work_types/work-type-lists', [
            'current_route' => 'con-work-types',
            'records' => $records,
            'page_title'   => 'Work Types'
        ]);
    }

    public function create() {
        $designTypes = $this->designTypeModel->getAll();
        
        $this->view('phase2/work_types/create-work-type', [
            'current_route' => 'con-work-types',
            'page_title' => 'Add Work Type',
            'designTypes' => $designTypes
        ]);
    }

    public function store() {
        $data = [
            'design_type_id' => $_POST['design_type_id'] ?? null,
            'work_type_name' => trim($_POST['work_type_name'] ?? ''),
            'description'    => trim($_POST['description'] ?? ''),
            'created_by'     => $_SESSION['user']['id'],
        ];

        if (empty($data['work_type_name']) || empty($data['design_type_id'])) {
            setError("Design Type and Work Type name are required.");
            header("Location: " . $GLOBALS['base_url'] . "/con-work-type-create");
            exit;
        }

        if ($this->model->create($data)) {
            setSuccess("Work type created successfully.");
        } else {
            setError("Failed to create work type.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-work-types");
        exit;
    }

    public function edit($id) {
        $record = $this->model->find($id);

        if (!$record) {
            setError("Record not found.");
            header("Location: " . $GLOBALS['base_url'] . "/con-work-types");
            exit;
        }

        $designTypes = $this->designTypeModel->getAll();

        $this->view('phase2/work_types/edit-work-type', [
            'current_route' => 'con-work-types',
            'page_title'  => 'Edit Work Type',
            'record'      => $record,
            'designTypes' => $designTypes
        ]);
    }

    public function update($id) {
        $data = [
            'design_type_id' => $_POST['design_type_id'] ?? null,
            'work_type_name' => trim($_POST['work_type_name'] ?? ''),
            'description'    => trim($_POST['description'] ?? '')
        ];

        if (empty($data['work_type_name']) || empty($data['design_type_id'])) {
            setError("Design Type and Work Type name are required.");
            header("Location: " . $GLOBALS['base_url'] . "/con-work-type-edit/" . $id);
            exit;
        }

        if ($this->model->update($id, $data)) {
            setSuccess("Updated successfully.");
        } else {
            setError("Failed to update.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-work-types");
        exit;
    }

    public function delete() {
        $id = $_POST['id'] ?? 0;
        if ($this->model->delete($id)) {
            setSuccess("Deleted successfully.");
        } else {
            setError("Failed to delete.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-work-types");
        exit;
    }
}

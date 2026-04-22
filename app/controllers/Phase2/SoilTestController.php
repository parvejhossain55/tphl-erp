<?php
namespace Phase2;

use Controller;
use Con_SoilTest;

class SoilTestController extends Controller {
    protected $db;
    protected $model;

    public function __construct($db) {
        $this->db    = $db;
        $this->model = new Con_SoilTest($db);

        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit;
        }
    }

    public function index() {
        $records = $this->model->getAll();

        $this->view('phase2/soil_tests/soil-tests', [
            'current_route' => 'con-soil-tests',
            'records' => $records,
            'title'   => 'Soil Tests'
        ]);
    }

    public function create() {
        $projects  = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        $engineers = $this->db->query("SELECT id, full_name FROM users ORDER BY full_name ASC")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/soil_tests/create-soil-test', [
            'current_route' => 'con-soil-tests',
            'title'     => 'Add Soil Test',
            'projects'  => $projects,
            'engineers' => $engineers,
        ]);
    }

    public function store() {
        $data = [
            'project_id'              => $_POST['project_id'] ?? 0,
            'site_engineer_id'        => $_POST['site_engineer_id'] ?? null,
            'field_data'              => $_POST['field_data'] ?? null,
            'lab_test_document_link'  => $_POST['lab_test_document_link'] ?? null,
            'field_test_doc_link'     => $_POST['field_test_doc_link'] ?? null,
            'test_date'               => $_POST['test_date'] ?? null,
            'created_by'              => $_SESSION['user']['id'],
        ];

        if ($this->model->create($data)) {
            setSuccess("Soil test record created successfully.");
        } else {
            setError("Failed to create soil test record.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-soil-tests");
        exit;
    }

    public function edit() {
        $id     = $_GET['id'] ?? 0;
        $record = $this->model->find($id);

        if (!$record) {
            setError("Record not found.");
            header("Location: " . $GLOBALS['base_url'] . "/con-soil-tests");
            exit;
        }

        $projects  = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        $engineers = $this->db->query("SELECT id, full_name FROM users ORDER BY full_name ASC")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/soil_tests/edit-soil-test', [
            'current_route' => 'con-soil-tests',
            'title'     => 'Edit Soil Test',
            'record'    => $record,
            'projects'  => $projects,
            'engineers' => $engineers,
        ]);
    }

    public function update() {
        $id   = $_POST['id'] ?? 0;
        $data = [
            'project_id'             => $_POST['project_id'] ?? 0,
            'site_engineer_id'       => $_POST['site_engineer_id'] ?? null,
            'field_data'             => $_POST['field_data'] ?? null,
            'lab_test_document_link' => $_POST['lab_test_document_link'] ?? null,
            'field_test_doc_link'    => $_POST['field_test_doc_link'] ?? null,
            'test_date'              => $_POST['test_date'] ?? null,
        ];

        if ($this->model->update($id, $data)) {
            setSuccess("Updated successfully.");
        } else {
            setError("Failed to update soil test.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-soil-tests");
        exit;
    }

    public function delete() {
        $id = $_POST['id'] ?? 0;
        if ($this->model->delete($id)) {
            setSuccess("Deleted successfully.");
        } else {
            setError("Failed to delete.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-soil-tests");
        exit;
    }
}

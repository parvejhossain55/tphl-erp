<?php
namespace Phase2;

use Controller;
use Con_SoilTest;

require_once __DIR__ . '/../../core/ProjectWorkflowMailService.php';

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
        $userId = $_SESSION['user']['id']  ?? 0;
        $roleId = $_SESSION['user']['role_id'] ?? 0;

        if (in_array($roleId, [1, 2, 13, 15])) {
            $records = $this->model->getAll();
        } else {
            $stmt = $this->db->prepare("
                SELECT st.*, p.name AS project_name
                FROM soil_tests st
                LEFT JOIN projects p ON st.project_id = p.id
                WHERE p.site_engineer_id = ?
                ORDER BY st.id DESC
            ");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        $this->view('phase2/soil_tests/soil-tests', [
            'current_route' => 'con-soil-tests',
            'records' => $records,
            'title'   => 'Soil Tests'
        ]);
    }

    public function create() {
        $userId   = $_SESSION['user']['id']  ?? 0;
        $roleId   = $_SESSION['user']['role_id'] ?? 0;
        $projects = $this->getMyAssignedProjects($userId, $roleId, 'site_engineer_id');
        $currentUserName = $_SESSION['user']['full_name'] ?? 'N/A';

        $this->view('phase2/soil_tests/create-soil-test', [
            'current_route'   => 'con-soil-tests',
            'title'           => 'Add Soil Test',
            'projects'        => $projects,
            'currentUserName' => $currentUserName,
        ]);
    }

    public function store() {
        $data = [
            'project_id'             => $_POST['project_id'] ?? 0,
            'site_engineer_id'       => $_SESSION['user']['id'], // auto from session
            'field_data'             => $_POST['field_data'] ?? null,
            'lab_test_document_link' => $_POST['lab_test_document_link'] ?? null,
            'field_test_doc_link'    => $_POST['field_test_doc_link'] ?? null,
            'test_date'              => $_POST['test_date'] ?? null,
            'created_by'             => $_SESSION['user']['id'],
        ];

        if ($this->model->create($data)) {
            // ── Batch 6: Notify PM, Estimator, QC — Pre-Construction Complete ──
            $projectId   = (int)($data['project_id']);
            $projectName = $this->getProjectName($projectId);
            try {
                $wfMail = new \ProjectWorkflowMailService($this->db);
                $wfMail->notifyPreConstructionComplete($projectId, $projectName);
            } catch (\Throwable $e) {
                error_log('[SoilTest] Pre-construction complete email error: ' . $e->getMessage());
            }
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

    private function getProjectName(int $id): string
    {
        if ($id <= 0) return 'N/A';
        $row = $this->db->query("SELECT name FROM projects WHERE id = {$id} LIMIT 1")->fetch_assoc();
        return $row['name'] ?? 'N/A';
    }

    private function getMyAssignedProjects(int $userId, int $roleId, string $roleCol): array
    {
        if (in_array($roleId, [1, 2, 13, 15])) {
            return $this->db->query(
                "SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC"
            )->fetch_all(MYSQLI_ASSOC);
        }
        $stmt = $this->db->prepare(
            "SELECT id, name FROM projects WHERE {$roleCol} = ? AND deleted_at IS NULL ORDER BY name ASC"
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

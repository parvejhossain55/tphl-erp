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

        if (in_array($roleId, [1, 2, 3, 13, 15, 23])) {
            $records = $this->model->getAll();
        } else {
            $stmt = $this->db->prepare("
                SELECT st.*, p.name AS project_name,u.full_name as engineer_name
                FROM soil_tests st
                LEFT JOIN projects p ON st.project_id = p.id
                LEFT JOIN users u ON st.site_engineer_id = u.id
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
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

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
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

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
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

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
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

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
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

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
        if (in_array($roleId, [1, 2, 3, 13, 15, 23])) {
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

    // ─── CSV Import Method ───────────
    public function importCsv() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $GLOBALS['base_url'] . '/con-dashboard');
            exit;
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setError('Please upload a valid CSV file.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-soil-tests');
            exit;
        }

        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        if ($handle === FALSE) { setError('Failed to open CSV file.'); header('Location: ' . $GLOBALS['base_url'] . '/con-soil-tests'); exit; }

        $header = array_map('trim', fgetcsv($handle));
        $header[0] = ltrim($header[0], "\xEF\xBB\xBF"); // Strip UTF-8 BOM (Excel CSV)
        $successCount = 0; $errorCount = 0; $errors = [];
        $db = $this->db;
        $userId = $_SESSION['user']['id'] ?? 0;

        while (($data = fgetcsv($handle)) !== FALSE) {
            if (empty(array_filter($data))) continue;
            $row = array_combine($header, $data);
            
            $projectName = trim($row['Project_Name'] ?? '');
            $testingAgency = trim($row['Testing_Agency'] ?? '');
            $reportDate = trim($row['Report_Date'] ?? date('Y-m-d'));
            $bearingCapacity = trim($row['Bearing_Capacity'] ?? '');
            $reportLink = trim($row['Report_Link'] ?? '');

            if (empty($projectName)) { $errorCount++; $errors[] = 'Missing Project_Name'; continue; }

            $stmt = $db->prepare('SELECT id FROM projects WHERE name = ? AND deleted_at IS NULL LIMIT 1');
            $stmt->bind_param('s', $projectName); $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows === 0) { $errorCount++; $errors[] = "Project '$projectName' not found"; continue; }
            $projectId = $res->fetch_assoc()['id'];

            $insertData = [
                'project_id' => $projectId,
                'testing_agency' => $testingAgency,
                'report_date' => $reportDate,
                'bearing_capacity' => $bearingCapacity,
                'report_link' => $reportLink,
                'created_by' => $userId
            ];

            if ($this->model->create($insertData)) { $successCount++; } 
            else { $errorCount++; $errors[] = "Failed to save data for Project '$projectName'."; }
        }
        fclose($handle);
        if ($errorCount > 0) { setError("Imported $successCount. Failed $errorCount. Errors: " . implode(' | ', array_slice($errors, 0, 5))); } 
        else { setSuccess("Successfully imported $successCount records."); }
        header('Location: ' . $GLOBALS['base_url'] . '/con-soil-tests');
        exit;
    }

}

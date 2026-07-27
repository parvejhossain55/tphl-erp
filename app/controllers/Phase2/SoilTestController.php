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

        if (in_array($roleId, [1, 2, 3, 13, 15, 22])) {
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
            'page_title'    => 'Soil Tests',
            'records' => $records,
            'title'   => 'Soil Tests'
        ]);
    }

    public function create() {
        if ((int)($_SESSION['user']['role_id'] ?? 0) !== 12) {
            setError('Only Site Engineers can add Soil Tests.');
            header("Location: " . $GLOBALS['base_url'] . "/con-soil-tests");
            exit;
        }

        $userId   = $_SESSION['user']['id']  ?? 0;
        $roleId   = $_SESSION['user']['role_id'] ?? 0;
        $projects = $this->getMyAssignedProjects($userId, $roleId, 'site_engineer_id');
        $currentUserName = $_SESSION['user']['full_name'] ?? 'N/A';

        $this->view('phase2/soil_tests/create-soil-test', [
            'current_route'   => 'con-soil-tests',
            'page_title'      => 'Add Soil Test',
            'title'           => 'Add Soil Test',
            'projects'        => $projects,
            'currentUserName' => $currentUserName,
        ]);
    }

    public function store() {
        if ((int)($_SESSION['user']['role_id'] ?? 0) !== 12) {
            setError('Only Site Engineers can add Soil Tests.');
            header("Location: " . $GLOBALS['base_url'] . "/con-soil-tests");
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
        if ((int)($_SESSION['user']['role_id'] ?? 0) !== 12) {
            setError('Only Site Engineers can edit Soil Tests.');
            header("Location: " . $GLOBALS['base_url'] . "/con-soil-tests");
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
            'page_title'    => 'Edit Soil Test',
            'title'     => 'Edit Soil Test',
            'record'    => $record,
            'projects'  => $projects,
            'engineers' => $engineers,
        ]);
    }

    public function update() {
        if ((int)($_SESSION['user']['role_id'] ?? 0) !== 12) {
            setError('Only Site Engineers can edit Soil Tests.');
            header("Location: " . $GLOBALS['base_url'] . "/con-soil-tests");
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
        if ((int)($_SESSION['user']['role_id'] ?? 0) !== 12) {
            setError('Only Site Engineers can delete Soil Tests.');
            header("Location: " . $GLOBALS['base_url'] . "/con-soil-tests");
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
        if (in_array($roleId, [1, 2, 3, 13, 15, 20, 22])) {
            return $this->db->query(
                "SELECT id, name FROM projects WHERE deleted_at IS NULL AND under_construction = 1 ORDER BY name ASC"
            )->fetch_all(MYSQLI_ASSOC);
        }
        $stmt = $this->db->prepare(
            "SELECT id, name FROM projects 
             WHERE deleted_at IS NULL AND under_construction = 1
               AND (pm_id = ? OR site_engineer_id = ? OR landman_id = ? OR struct_engineer_id = ? 
                    OR operation_head_id = ? OR draftsman_id = ? OR architect_id = ? 
                    OR estimator_id = ? OR qc_id = ? OR operator_id = ? OR created_by = ?)
             ORDER BY name ASC"
        );
        $stmt->bind_param('iiiiiiiiiii', $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId);
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

        // Ensure UTF-8 Encoding (Fix for Bengali text)
        $fileContent = file_get_contents($file);
        $encoding = mb_detect_encoding($fileContent, 'UTF-8, UTF-16LE, Windows-1252, ISO-8859-1', true);
        if ($encoding && $encoding !== 'UTF-8') {
            $fileContent = mb_convert_encoding($fileContent, 'UTF-8', $encoding);
            file_put_contents($file, $fileContent);
        }

        $handle = fopen($file, 'r');
        if (!$handle) { setError('Cannot open file.'); header('Location: ' . $GLOBALS['base_url'] . '/con-soil-tests'); exit; }
        
        $header = array_map('trim', fgetcsv($handle));
        $header[0] = ltrim($header[0], "\xEF\xBB\xBF"); // Strip UTF-8 BOM
        
        $resolveKey = function($row, $targetKey) {
            $targetLower = strtolower($targetKey);
            foreach ($row as $key => $val) {
                $keyLower = strtolower(trim($key));
                if ($keyLower === $targetLower || strpos($keyLower, $targetLower) === 0) {
                    return $val;
                }
            }
            return '';
        };

        $successCount = 0; $errorCount = 0; $errors = [];
        $db = $this->db;
        $sessionUserId = $_SESSION['user']['id'] ?? 0;

        while (($data = fgetcsv($handle)) !== FALSE) {
            if (empty(array_filter($data))) continue;
            
            // Pad data array to match header length to prevent array_combine warnings
            $data = array_pad($data, count($header), '');
            $row = array_combine($header, $data);
            
            $projectName   = trim($resolveKey($row, 'Project_Name'));
            $testDate      = trim($resolveKey($row, 'Test_Date'));
            $fieldData     = trim($resolveKey($row, 'Field_Data'));
            $labTestDoc    = trim($resolveKey($row, 'Lab_Test_Document_Link'));
            $fieldTestDoc  = trim($resolveKey($row, 'Field_Test_Doc_Link'));

            if (empty($projectName)) { $errorCount++; $errors[] = 'Missing Project_Name'; continue; }

            $stmt = $db->prepare('SELECT id FROM projects WHERE name = ? AND deleted_at IS NULL LIMIT 1');
            $stmt->bind_param('s', $projectName); $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows === 0) { $errorCount++; $errors[] = "Project '$projectName' not found"; continue; }
            $projectId = $res->fetch_assoc()['id'];

            $insertData = [
                'project_id'             => $projectId,
                'site_engineer_id'       => $sessionUserId,
                'field_data'             => !empty($fieldData) ? $fieldData : null,
                'lab_test_document_link' => !empty($labTestDoc) ? $labTestDoc : null,
                'field_test_doc_link'    => !empty($fieldTestDoc) ? $fieldTestDoc : null,
                'test_date'              => !empty($testDate) ? $testDate : null,
                'created_by'             => $sessionUserId
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

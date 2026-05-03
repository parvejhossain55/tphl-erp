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
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $designTypes = $this->designTypeModel->getAll();
        
        $this->view('phase2/work_types/create-work-type', [
            'current_route' => 'con-work-types',
            'page_title' => 'Add Work Type',
            'designTypes' => $designTypes
        ]);
    }

    public function store() {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

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
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

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
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

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
        header("Location: " . $GLOBALS['base_url'] . "/con-work-types");
        exit;
    }

    // ─── CSV Import Method ────────────────────────────────────────────────
    public function importCsv() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $GLOBALS['base_url'] . '/con-work-types');
            exit;
        }
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setError('Please upload a valid CSV file.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-work-types');
            exit;
        }
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        if (!$handle) { setError('Cannot open file.'); header('Location: ' . $GLOBALS['base_url'] . '/con-work-types'); exit; }
        $header = array_map('trim', fgetcsv($handle));
        $header[0] = ltrim($header[0], "\xEF\xBB\xBF"); // Strip UTF-8 BOM (Excel CSV)
        $successCount = 0; $errorCount = 0; $errors = [];
        $db = $this->db;
        $userId = $_SESSION['user']['id'] ?? 0;

        while (($data = fgetcsv($handle)) !== FALSE) {
            if (empty(array_filter($data))) continue;
            if (count($header) !== count($data)) continue;
            $row = array_combine($header, $data);
            
            $name = trim($row['Name'] ?? '');
            $desc = trim($row['Description'] ?? '');
            if (empty($name)) { $errorCount++; $errors[] = "Missing Name"; continue; }
            $stmt = $db->prepare("SELECT id FROM work_types WHERE name = ? LIMIT 1");
            $stmt->bind_param("s", $name); $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) { $errors[] = "Work Type '$name' already exists"; $errorCount++; continue; }
            $ins = $db->prepare("INSERT INTO work_types (name, description, created_by) VALUES (?,?,?)");
            $ins->bind_param("ssi", $name, $desc, $userId);
            if ($ins->execute()) { $successCount++; } else { $errorCount++; $errors[] = "Failed to save '$name'"; }
        
        }
        fclose($handle);
        if ($errorCount > 0) {
            setError("Imported $successCount. Failed $errorCount. " . implode(' | ', array_slice($errors, 0, 5)));
        } else {
            setSuccess("Successfully imported $successCount records.");
        }
        header('Location: ' . $GLOBALS['base_url'] . '/con-work-types');
        exit;
    }
}

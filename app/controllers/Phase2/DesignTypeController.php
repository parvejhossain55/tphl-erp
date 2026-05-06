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
            'page_title'   => 'Design Types'
        ]);
    }

    public function create() {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $this->view('phase2/design_types/create-design-type', [
            'page_title' => 'Add Design Type'
        ]);
    }

    public function store() {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

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
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $id     = $_GET['id'] ?? 0;
        $record = $this->model->find($id);

        if (!$record) {
            setError("Record not found.");
            header("Location: " . $GLOBALS['base_url'] . "/con-design-types");
            exit;
        }

        $this->view('phase2/design_types/edit-design-type', [
            'page_title'  => 'Edit Design Type',
            'record' => $record,
        ]);
    }

    public function update() {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

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
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
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
        header("Location: " . $GLOBALS['base_url'] . "/con-design-types");
        exit;
    }

    // ─── CSV Import Method ────────────────────────────────────────────────
    public function importCsv() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $GLOBALS['base_url'] . '/con-design-types');
            exit;
        }
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setError('Please upload a valid CSV file.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-design-types');
            exit;
        }
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        if (!$handle) { setError('Cannot open file.'); header('Location: ' . $GLOBALS['base_url'] . '/con-design-types'); exit; }
        $header = array_map('trim', fgetcsv($handle));
        $header[0] = ltrim($header[0], "\xEF\xBB\xBF"); // Strip UTF-8 BOM (Excel CSV)
        $successCount = 0; $errorCount = 0; $errors = [];
        $db = $this->db;
        $userId = $_SESSION['user']['id'] ?? 0;

        while (($data = fgetcsv($handle)) !== FALSE) {
            if (empty(array_filter($data))) continue;
            if (count($header) !== count($data)) continue;
            $row = array_combine($header, $data);

            // Flexible key resolver — case-insensitive prefix match
            $resolveKey = function(array $row, string $prefix): string {
                if (array_key_exists($prefix, $row)) return $row[$prefix];
                foreach ($row as $k => $v) {
                    if (stripos(trim($k), $prefix) === 0) return $v;
                }
                return '';
            };

            $name = trim($resolveKey($row, 'Name'));
            
            if (empty($name)) { $errorCount++; $errors[] = "Missing Name"; continue; }
            $stmt = $db->prepare("SELECT id FROM design_types WHERE name = ? LIMIT 1");
            $stmt->bind_param("s", $name); $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) { $errors[] = "Design Type '$name' already exists"; $errorCount++; continue; }
            $ins = $db->prepare("INSERT INTO design_types (name, created_by) VALUES (?,?)");
            $ins->bind_param("si", $name, $userId);
            if ($ins->execute()) { $successCount++; } else { $errorCount++; $errors[] = "Failed to save '$name'"; }
        
        }
        fclose($handle);
        if ($errorCount > 0) {
            setError("Imported $successCount. Failed $errorCount. " . implode(' | ', array_slice($errors, 0, 5)));
        } else {
            setSuccess("Successfully imported $successCount records.");
        }
        header('Location: ' . $GLOBALS['base_url'] . '/con-design-types');
        exit;
    }
}

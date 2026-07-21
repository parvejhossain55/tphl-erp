<?php
namespace Phase2;

use Controller;
use Con_Warehouse;
use Project;

class WarehouseController extends Controller
{
    protected $db;
    protected $warehouseModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->warehouseModel = new Con_Warehouse($this->db);
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $warehouses = $this->warehouseModel->getAll();

        $this->view('phase2/warehouses/con-warehouses', [
            'current_route' => 'con-warehouses',
            'page_title'    => 'Warehouses',
            'warehouses'    => $warehouses,
        ]);
    }

    public function create()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $projects = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL AND under_construction = 1 ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/warehouses/create-warehouse', [
            'current_route' => 'con-warehouses',
            'page_title'    => 'Add Warehouse',
            'projects'      => $projects,
        ]);
    }

    public function store()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        $data = [
            'name'       => trim($_POST['name'] ?? ''),
            'project_id' => !empty($_POST['project_id']) ? $_POST['project_id'] : null,
            'location'   => trim($_POST['location'] ?? ''),
            'created_by' => $_SESSION['user']['id'] ?? null,
        ];

        if (empty($data['name'])) {
            setError('Name is required.');
            header("Location: {$GLOBALS['base_url']}/con-warehouse-create");
            exit;
        }

        if ($this->warehouseModel->create($data)) {
            setSuccess('Warehouse created successfully!');
            header("Location: {$GLOBALS['base_url']}/con-warehouses");
            exit;
        }

        setError('Failed to create warehouse.');
        header("Location: {$GLOBALS['base_url']}/con-warehouse-create");
        exit;
    }

    public function edit($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $warehouse = $this->warehouseModel->find($id);
        $projects = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL AND under_construction = 1 ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/warehouses/edit-warehouse', [
            'current_route' => 'con-warehouses',
            'page_title'    => 'Edit Warehouse',
            'warehouse'     => $warehouse,
            'projects'      => $projects,
        ]);
    }

    public function update($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $data = [
            'name'       => trim($_POST['name'] ?? ''),
            'project_id' => !empty($_POST['project_id']) ? $_POST['project_id'] : null,
            'location'   => trim($_POST['location'] ?? ''),
        ];

        if ($this->warehouseModel->update($id, $data)) {
            setSuccess('Warehouse updated!');
        } else {
            setError('Update failed.');
        }

        header("Location: {$GLOBALS['base_url']}/con-warehouses");
        exit;
    }

    // ─── CSV Import ────────────────────────────────────────────────────────
    public function importCsv() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $GLOBALS['base_url'] . '/con-warehouses');
            exit;
        }
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setError('Please upload a valid CSV file.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-warehouses');
            exit;
        }

        // Read as raw string — properly handles UTF-8 Bengali text
        $content = file_get_contents($_FILES['csv_file']['tmp_name']);
        $content = ltrim($content, "\xEF\xBB\xBF");
        $content = str_replace("\r\n", "\n", str_replace("\r", "\n", $content));
        $lines   = array_values(array_filter(explode("\n", $content), fn($l) => trim($l) !== ''));

        if (empty($lines)) {
            setError('CSV file is empty.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-warehouses');
            exit;
        }

        $header = array_map('trim', str_getcsv(array_shift($lines)));

        $resolveKey = function($row, $targetKey) {
            $tl = strtolower($targetKey);
            foreach ($row as $k => $v) {
                $kl = strtolower(trim($k));
                if ($kl === $tl || strpos($kl, $tl) === 0) return $v;
            }
            return '';
        };

        $successCount = 0; $errorCount = 0; $errors = [];
        $userId = $_SESSION['user']['id'] ?? 0;

        foreach ($lines as $line) {
            if (trim($line) === '') continue;
            $data = array_pad(str_getcsv($line), count($header), '');
            $row  = array_combine($header, $data);

            $whName     = trim($resolveKey($row, 'Warehouse_Name'));
            $projectName = trim($resolveKey($row, 'Project_Name'));
            $location   = trim($resolveKey($row, 'Location'));

            if (empty($whName)) {
                $errorCount++; $errors[] = "Missing Warehouse Name";
                continue;
            }

            // Resolve Project_Name → project_id
            $projectId = null;
            if (!empty($projectName)) {
                $stmtP = $this->db->prepare("SELECT id FROM projects WHERE name = ? AND deleted_at IS NULL LIMIT 1");
                $stmtP->bind_param('s', $projectName);
                $stmtP->execute();
                $resP = $stmtP->get_result()->fetch_assoc();
                if (!$resP) {
                    $errors[] = "Project '$projectName' not found (row: $whName)";
                    $errorCount++;
                    continue;
                }
                $projectId = $resP['id'];
            }

            // Duplicate check
            $stmtD = $this->db->prepare("SELECT id FROM con_warehouses WHERE name = ? LIMIT 1");
            $stmtD->bind_param('s', $whName);
            $stmtD->execute();
            if ($stmtD->get_result()->num_rows > 0) {
                $errors[] = "'$whName' already exists";
                $errorCount++;
                continue;
            }

            $insertData = [
                'name'       => $whName,
                'project_id' => $projectId,
                'location'   => !empty($location) ? $location : null,
                'created_by' => $userId,
            ];

            if ($this->warehouseModel->create($insertData)) {
                $successCount++;
            } else {
                $errorCount++; $errors[] = "Failed to save '$whName'";
            }
        }

        if ($errorCount > 0) {
            setError("Imported $successCount. Failed $errorCount. Errors: " . implode(' | ', array_slice($errors, 0, 5)));
        } else {
            setSuccess("Successfully imported $successCount warehouses.");
        }
        header('Location: ' . $GLOBALS['base_url'] . '/con-warehouses');
        exit;
    }
}

<?php
namespace Phase2;

use Controller;
use Con_Material;

class Con_MaterialController extends Controller
{
    protected $db;
    protected $materialModel;

    public function __construct($db)
    {
        $this->db            = $db;
        $this->materialModel = new Con_Material($this->db);
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $materials = $this->materialModel->getAll();

        $this->view('phase2/materials/con-materials', [
            'current_route' => 'con-materials',
            'page_title'    => 'Construction Materials',
            'materials'     => $materials,
        ]);
    }

    public function create()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $this->view('phase2/materials/create-material', [
            'current_route' => 'con-material-create',
            'page_title'    => 'Add Material',
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
            'name'            => trim($_POST['name'] ?? ''),
            'unit'            => trim($_POST['unit'] ?? ''),
            'min_stock_level' => $_POST['min_stock_level'] ?? 0,
            'created_by'      => $_SESSION['user']['id'] ?? null,
        ];

        if (empty($data['name']) || empty($data['unit'])) {
            setError('Name and Unit are required.');
            header("Location: {$GLOBALS['base_url']}/con-material-create");
            exit;
        }

        if ($this->materialModel->create($data)) {
            setSuccess('Material added successfully!');
            header("Location: {$GLOBALS['base_url']}/con-materials");
            exit;
        }

        setError('Failed to add material.');
        header("Location: {$GLOBALS['base_url']}/con-material-create");
        exit;
    }

    public function edit($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $material = $this->materialModel->find($id);
        $this->view('phase2/materials/edit-material', [
            'current_route' => 'con-materials',
            'page_title'    => 'Edit Material',
            'material'      => $material,
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
            'name'            => trim($_POST['name'] ?? ''),
            'unit'            => trim($_POST['unit'] ?? ''),
            'min_stock_level' => $_POST['min_stock_level'] ?? 0,
        ];

        if ($this->materialModel->update($id, $data)) {
            setSuccess('Material updated!');
        } else {
            setError('Update failed.');
        }

        header("Location: {$GLOBALS['base_url']}/con-materials");
        exit;
    }

    /* =====================
       Inventory Stocks View
    ====================== */
    public function stocks()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $stockModel     = new \Con_Stock($this->db);
        $stocks         = $stockModel->getCurrentStock();
        $stats          = $stockModel->getStockSummaryStats();
        $availableStock = $stockModel->getAvailableStock();
        $lowStockItems  = $stockModel->getLowStockItems();
        $byProject      = $stockModel->getStockByProject();
        $budgetVsReceived = $stockModel->getBudgetVsReceived();

        $this->view('phase2/stocks/con-stocks', [
            'current_route'  => 'con-stocks',
            'page_title'     => 'Inventory Stock Levels',
            'stocks'         => $stocks,
            'stats'          => $stats,
            'availableStock' => $availableStock,
            'lowStockItems'  => $lowStockItems,
            'byProject'      => $byProject,
            'budgetVsReceived'=> $budgetVsReceived,
        ]);
    }
    /* =====================
       CSV Import
    ====================== */
    public function importCsv() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $GLOBALS['base_url'] . '/con-materials');
            exit;
        }
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setError('Please upload a valid CSV file.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-materials');
            exit;
        }
        
        // Read the entire file as a string to properly handle UTF-8 Bengali text
        $content = file_get_contents($_FILES['csv_file']['tmp_name']);
        // Strip UTF-8 BOM if present
        $content = ltrim($content, "\xEF\xBB\xBF");
        // Normalize line endings
        $content = str_replace("\r\n", "\n", $content);
        $content = str_replace("\r", "\n", $content);
        
        $lines = array_filter(explode("\n", $content), fn($l) => trim($l) !== '');
        $lines = array_values($lines);
        
        if (empty($lines)) {
            setError('CSV file is empty.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-materials');
            exit;
        }
        
        $header = array_map('trim', str_getcsv(array_shift($lines)));
        
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
        $userId = $_SESSION['user']['id'] ?? 0;

        foreach ($lines as $line) {
            if (trim($line) === '') continue;
            
            $data = array_pad(str_getcsv($line), count($header), '');
            $row  = array_combine($header, $data);
            
            $name          = trim($resolveKey($row, 'Name'));
            $unit          = trim($resolveKey($row, 'Unit'));
            $minStockLevel = trim($resolveKey($row, 'Min_Stock_Level'));
            
            if (empty($name) || empty($unit)) { 
                $errorCount++; $errors[] = "Missing Name or Unit"; 
                continue; 
            }
            
            // Check if material already exists (by name)
            $stmt = $this->db->prepare("SELECT id FROM con_materials WHERE name = ? LIMIT 1");
            $stmt->bind_param("s", $name); 
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) { 
                $errors[] = "'$name' already exists"; 
                $errorCount++; 
                continue; 
            }
            
            $insertData = [
                'name'            => $name,
                'unit'            => $unit,
                'min_stock_level' => is_numeric($minStockLevel) ? (float)$minStockLevel : 0.000,
                'created_by'      => $userId
            ];

            if ($this->materialModel->create($insertData)) { 
                $successCount++; 
            } else { 
                $errorCount++; $errors[] = "Failed to save '$name'"; 
            }
        }
        
        if ($errorCount > 0) {
            setError("Imported $successCount. Failed $errorCount. Errors: " . implode(' | ', array_slice($errors, 0, 5)));
        } else {
            setSuccess("Successfully imported $successCount records.");
        }
        
        header('Location: ' . $GLOBALS['base_url'] . '/con-materials');
        exit;
    }
}

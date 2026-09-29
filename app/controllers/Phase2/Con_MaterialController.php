<?php
namespace Phase2;

use Controller;
use Con_Material;
use Con_MaterialCategory;

class Con_MaterialController extends Controller
{
    protected $db;
    protected $materialModel;
    protected $categoryModel;

    public function __construct($db)
    {
        $this->db            = $db;
        $this->materialModel = new Con_Material($this->db);
        $this->categoryModel = new Con_MaterialCategory($this->db);
    }

    // =========================================================================
    // LIST
    // =========================================================================
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $materials  = $this->materialModel->getAll();
        $categories = $this->categoryModel->getAll();

        $this->view('phase2/materials/con-materials', [
            'current_route' => 'con-materials',
            'page_title'    => 'Construction Materials',
            'materials'     => $materials,
            'categories'    => $categories,
        ]);
    }

    // =========================================================================
    // CREATE / STORE MATERIAL
    // =========================================================================
    public function create()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $categories = $this->categoryModel->getAll();
        $this->view('phase2/materials/create-material', [
            'current_route' => 'con-material-create',
            'page_title'    => 'Add Material',
            'categories'    => $categories,
        ]);
    }

    public function store()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;

        $data = [
            'category_id'     => $categoryId,
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

    public function ajaxStoreMaterial()
    {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 20, 22])) {
            echo json_encode(['success' => false, 'error' => 'You do not have permission to add records.']);
            exit;
        }

        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $name = trim($_POST['name'] ?? '');
        $unit = trim($_POST['unit'] ?? '');
        $minStock = !empty($_POST['min_stock_level']) ? (float)$_POST['min_stock_level'] : 0.00;

        if (!$name || !$unit) {
            echo json_encode(['success' => false, 'error' => 'Name and Unit are required.']);
            exit;
        }

        $data = [
            'category_id'     => $categoryId,
            'name'            => $name,
            'unit'            => $unit,
            'min_stock_level' => $minStock,
            'created_by'      => $_SESSION['user']['id'] ?? null,
        ];

        // Check if material name already exists
        $stmt = $this->db->prepare("SELECT id FROM con_materials WHERE name = ? LIMIT 1");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => "Material '{$name}' already exists."]);
            exit;
        }

        $id = $this->materialModel->create($data);
        if ($id) {
            $catName = 'Uncategorized';
            if ($categoryId) {
                $catStmt = $this->db->prepare("SELECT name FROM con_material_categories WHERE id = ? LIMIT 1");
                $catStmt->bind_param("i", $categoryId);
                $catStmt->execute();
                $catRow = $catStmt->get_result()->fetch_assoc();
                if ($catRow) {
                    $catName = $catRow['name'];
                }
            }

            echo json_encode([
                'success' => true,
                'id'      => $id,
                'name'    => $name,
                'unit'    => $unit,
                'category_name' => $catName
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save material.']);
        }
        exit;
    }

    // =========================================================================
    // EDIT / UPDATE MATERIAL
    // =========================================================================
    public function edit($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $material   = $this->materialModel->find($id);
        $categories = $this->categoryModel->getAll();
        $this->view('phase2/materials/edit-material', [
            'current_route' => 'con-materials',
            'page_title'    => 'Edit Material',
            'material'      => $material,
            'categories'    => $categories,
        ]);
    }

    public function update($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;

        $data = [
            'category_id'     => $categoryId,
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

    public function delete($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-materials");
            exit;
        }

        if ($this->materialModel->delete((int)$id)) {
            setSuccess('Material deleted successfully!');
        } else {
            setError('Failed to delete material. It might be in use by stocks, BOQs, or purchase requests/orders.');
        }

        header("Location: {$GLOBALS['base_url']}/con-materials");
        exit;
    }

    // =========================================================================
    // AJAX — Category CRUD
    // =========================================================================

    /** POST /con-material-category-store */
    public function storeCategory()
    {
        header('Content-Type: application/json');
        if (session_status() === PHP_SESSION_NONE) session_start();

        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');

        if (!$name) {
            echo json_encode(['success' => false, 'error' => 'Category name is required.']);
            exit;
        }

        $id = $this->categoryModel->create([
            'name'        => $name,
            'description' => $desc ?: null,
            'created_by'  => (int)($_SESSION['user']['id'] ?? 0),
        ]);

        if ($id) {
            echo json_encode(['success' => true, 'id' => $id, 'name' => $name]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save. Name may already exist.']);
        }
        exit;
    }

    /** POST /con-material-category-update/{id} */
    public function updateCategory($id)
    {
        header('Content-Type: application/json');

        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');

        if (!$name) {
            echo json_encode(['success' => false, 'error' => 'Category name is required.']);
            exit;
        }

        $ok = $this->categoryModel->update((int)$id, [
            'name'        => $name,
            'description' => $desc ?: null,
        ]);

        echo json_encode(['success' => (bool)$ok]);
        exit;
    }

    /** POST /con-material-category-lead-times/{id} */
    public function updateCategoryLeadTimes($id)
    {
        header('Content-Type: application/json');
        
        $itemType = $_POST['item_type'] ?? 'Local';
        $prToQtn = (int)($_POST['pr_to_qtn_days'] ?? 0);
        $csAnalysis = (int)($_POST['cs_analysis_days'] ?? 0);
        $approval = (int)($_POST['approval_days'] ?? 0);
        $woPo = (int)($_POST['wo_po_days'] ?? 0);
        $advPayment = (int)($_POST['adv_payment_days'] ?? 0);
        $transport = (int)($_POST['transport_days'] ?? 0);
        
        $total = $prToQtn + $csAnalysis + $approval + $woPo + $advPayment + $transport;

        $ok = $this->categoryModel->updateLeadTimes((int)$id, [
            'item_type'        => $itemType,
            'pr_to_qtn_days'   => $prToQtn,
            'cs_analysis_days' => $csAnalysis,
            'approval_days'    => $approval,
            'wo_po_days'       => $woPo,
            'adv_payment_days' => $advPayment,
            'transport_days'   => $transport,
            'total_lead_days'  => $total,
        ]);

        echo json_encode(['success' => (bool)$ok, 'total_lead_days' => $total]);
        exit;
    }

    /** POST /con-material-category-delete/{id} */
    public function deleteCategory($id)
    {
        header('Content-Type: application/json');

        $ok = $this->categoryModel->delete((int)$id);
        if ($ok) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Cannot delete — materials are linked to this category.']);
        }
        exit;
    }

    // =========================================================================
    // Inventory Stocks View
    // =========================================================================
    public function stocks()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $stockModel       = new \Con_Stock($this->db);
        $stocks           = $stockModel->getCurrentStock();
        $stats            = $stockModel->getStockSummaryStats();
        $availableStock   = $stockModel->getAvailableStock();
        $lowStockItems    = $stockModel->getLowStockItems();
        $byProject        = $stockModel->getStockByProject();
        $budgetVsReceived = $stockModel->getBudgetVsReceived();

        $this->view('phase2/stocks/con-stocks', [
            'current_route'    => 'con-stocks',
            'page_title'       => 'Inventory Stock Levels',
            'stocks'           => $stocks,
            'stats'            => $stats,
            'availableStock'   => $availableStock,
            'lowStockItems'    => $lowStockItems,
            'byProject'        => $byProject,
            'budgetVsReceived' => $budgetVsReceived,
        ]);
    }

    // =========================================================================
    // CSV Import
    // =========================================================================
    public function importCsv()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $GLOBALS['base_url'] . '/con-materials');
            exit;
        }
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setError('Please upload a valid CSV file.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-materials');
            exit;
        }

        $content = file_get_contents($_FILES['csv_file']['tmp_name']);
        $content = ltrim($content, "\xEF\xBB\xBF");
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

        $resolveKey = function ($row, $targetKey) {
            $targetLower = strtolower($targetKey);
            foreach ($row as $key => $val) {
                if (strtolower(trim($key)) === $targetLower) return $val;
            }
            return '';
        };

        $successCount = 0;
        $errorCount   = 0;
        $errors       = [];
        $userId       = $_SESSION['user']['id'] ?? 0;

        // Pre-load categories by name for fast lookup
        $catRows  = $this->categoryModel->getAll();
        $catByName = [];
        foreach ($catRows as $cat) {
            $catByName[strtolower(trim($cat['name']))] = $cat['id'];
        }

        foreach ($lines as $line) {
            if (trim($line) === '') continue;

            $data = array_pad(str_getcsv($line), count($header), '');
            $row  = array_combine($header, $data);

            $name          = trim($resolveKey($row, 'Name'));
            $unit          = trim($resolveKey($row, 'Unit'));
            $minStockLevel = trim($resolveKey($row, 'Min_Stock_Level'));
            $categoryName  = trim($resolveKey($row, 'Category'));

            if (empty($name) || empty($unit)) {
                $errorCount++;
                $errors[] = "Missing Name or Unit";
                continue;
            }

            // Resolve category_id from name
            $categoryId = null;
            if ($categoryName !== '') {
                $lowerCatName = strtolower($categoryName);
                if (isset($catByName[$lowerCatName])) {
                    $categoryId = $catByName[$lowerCatName];
                } else {
                    $newCatId = $this->categoryModel->create([
                        'name'        => $categoryName,
                        'description' => null,
                        'created_by'  => $userId,
                    ]);
                    if ($newCatId) {
                        $categoryId = $newCatId;
                        $catByName[$lowerCatName] = $newCatId;
                    }
                }
            }

            $stmt = $this->db->prepare("SELECT id FROM con_materials WHERE name = ? LIMIT 1");
            $stmt->bind_param("s", $name);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors[] = "'$name' already exists";
                $errorCount++;
                continue;
            }

            $insertData = [
                'category_id'     => $categoryId,
                'name'            => $name,
                'unit'            => $unit,
                'min_stock_level' => is_numeric($minStockLevel) ? (float)$minStockLevel : 0.000,
                'created_by'      => $userId,
            ];

            if ($this->materialModel->create($insertData)) {
                $successCount++;
            } else {
                $errorCount++;
                $errors[] = "Failed to save '$name'";
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

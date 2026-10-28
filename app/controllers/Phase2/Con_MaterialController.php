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
    // Project-wise Material Report
    // =========================================================================
    public function projectMaterialReport()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Check permission
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        if (!hasPermission('Project Material Report') && !hasPermission('Material Manage') && !in_array($roleId, [1, 3])) {
            if (!empty($_GET['ajax']) || isset($_GET['draw'])) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Unauthorized access.']);
                exit;
            }
            setError('You do not have permission to access the Project Material Report.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-dashboard');
            exit;
        }

        $filters = [
            'project_id'  => !empty($_GET['project_id']) ? (int)$_GET['project_id'] : null,
            'material_id' => !empty($_GET['material_id']) ? (int)$_GET['material_id'] : null,
            'category_id' => !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null,
            'start_date'  => !empty($_GET['start_date']) ? trim($_GET['start_date']) : null,
            'end_date'    => !empty($_GET['end_date']) ? trim($_GET['end_date']) : null,
        ];

        // Handle AJAX DataTables server-side request
        if (!empty($_GET['ajax']) || isset($_GET['draw'])) {
            header('Content-Type: application/json');

            $draw      = (int)($_GET['draw'] ?? 1);
            $start     = max(0, (int)($_GET['start'] ?? 0));
            $length    = (int)($_GET['length'] ?? 25);
            if ($length < 1 || $length > 200) $length = 25;
            $searchVal = trim($_GET['search']['value'] ?? '');
            $orderCol  = (int)($_GET['order'][0]['column'] ?? 0);
            $orderDir  = trim($_GET['order'][0]['dir'] ?? 'desc');

            $recordsTotal    = $this->materialModel->getProjectMaterialReportCount([]);
            $recordsFiltered = $this->materialModel->getProjectMaterialReportCount($filters, $searchVal);
            $rows            = $this->materialModel->getProjectMaterialReportPaginated($filters, $length, $start, $searchVal, $orderCol, $orderDir);
            $summaryStats    = $this->materialModel->getProjectMaterialSummaryStats($filters, $searchVal);

            $formattedData = [];
            $baseUrl = $GLOBALS['base_url'] ?? '';
            foreach ($rows as $i => $row) {
                $formattedData[] = $this->formatProjectMaterialRow($row, $start + $i + 1, $baseUrl);
            }

            $totalVal = (float)($summaryStats['total_order_value'] ?? 0);
            $totalValLakh = ($totalVal >= 100000) ? ('&#2547;' . number_format($totalVal / 100000, 2) . 'L') : (($totalVal >= 1000) ? ('&#2547;' . number_format($totalVal / 1000, 2) . 'K') : ('&#2547;' . number_format($totalVal, 2)));

            echo json_encode([
                'draw'            => $draw,
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data'            => $formattedData,
                'summaryStats'    => [
                    'totalOrders'               => (int)($summaryStats['total_orders'] ?? 0),
                    'totalOrdersFormatted'      => number_format((int)($summaryStats['total_orders'] ?? 0)),
                    'totalOrderValue'           => $totalVal,
                    'totalOrderValueFormatted'  => '&#2547;' . number_format($totalVal, 2),
                    'totalOrderValueLakh'       => $totalValLakh,
                    'receivedCount'             => (int)($summaryStats['received_count'] ?? 0),
                    'receivedCountFormatted'    => number_format((int)($summaryStats['received_count'] ?? 0)),
                    'notReceivedCount'          => (int)($summaryStats['not_received_count'] ?? 0),
                    'notReceivedCountFormatted' => number_format((int)($summaryStats['not_received_count'] ?? 0)),
                ]
            ]);
            exit;
        }

        // Fast initial KPI statistics computation (~0.01s)
        $summaryStats = $this->materialModel->getProjectMaterialSummaryStats($filters);
        $totalOrders      = (int)($summaryStats['total_orders'] ?? 0);
        $totalOrderValue  = (float)($summaryStats['total_order_value'] ?? 0);
        $receivedCount    = (int)($summaryStats['received_count'] ?? 0);
        $notReceivedCount = (int)($summaryStats['not_received_count'] ?? 0);

        $projects     = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        $categories   = $this->categoryModel->getAll();
        $allMaterials = $this->db->query("SELECT DISTINCT m.id, m.name, m.unit FROM con_materials m JOIN purchase_order_items poi ON poi.material_id = m.id ORDER BY m.name ASC")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/materials/project-material-report', [
            'current_route'     => 'con-project-materials-report',
            'current_page'      => 'con-project-materials-report',
            'page_title'        => 'Project-wise Material Report',
            'projects'          => $projects,
            'categories'        => $categories,
            'allMaterials'      => $allMaterials,
            'filters'           => $filters,
            'totalOrders'       => $totalOrders,
            'totalOrderValue'   => $totalOrderValue,
            'receivedCount'     => $receivedCount,
            'notReceivedCount'  => $notReceivedCount,
        ]);
    }

    /**
     * Format a single report row into clean HTML columns for DataTables
     */
    protected function formatProjectMaterialRow($row, $index, $baseUrl)
    {
        $isReceived = !empty($row['total_received_qty']) && (float)$row['total_received_qty'] > 0;
        $orderQty   = (float)$row['order_quantity'];
        $recvQty    = (float)($row['total_received_qty'] ?? 0);

        // Col 0: Index
        $colIndex = '<span class="text-muted fw-semibold small">' . $index . '</span>';

        // Col 1: Project
        $colProject = '<div class="fw-bold text-dark fs-6">' . htmlspecialchars($row['project_name']) . '</div>' .
                      '<small class="text-muted"><i class="fas fa-building text-primary me-1"></i>Project #' . (int)$row['project_id'] . '</small>';

        // Col 2: Material & Category
        $colMaterial = '<div class="fw-bold text-primary">' . htmlspecialchars($row['material_name']) . '</div>' .
                       '<div class="d-flex align-items-center gap-1 mt-1 flex-wrap">' .
                           '<span class="badge bg-light text-dark border small">' . htmlspecialchars($row['category_name']) . '</span>' .
                           '<span class="badge bg-secondary-subtle text-secondary small">' . htmlspecialchars($row['material_unit']) . '</span>' .
                       '</div>';

        // Col 3: CST Vendor
        $colVendor = '<div class="fw-semibold text-dark"><i class="fas fa-store text-muted me-1"></i>' . htmlspecialchars($row['vendor_name']) . '</div>';
        if (!empty($row['vendor_id'])) {
            $colVendor .= '<small class="text-muted">Vendor ID: #' . (int)$row['vendor_id'] . '</small>';
        }

        // Col 4: PO & PR No
        $colPoPr = '<div><a href="' . $baseUrl . '/con-purchase-order/' . (int)$row['po_id'] . '" target="_blank" class="badge bg-primary text-decoration-none">PO-' . (int)$row['po_id'] . '</a></div>' .
                   '<div class="mt-1"><a href="' . $baseUrl . '/con-purchase-request/' . (int)$row['pr_id'] . '" target="_blank" class="badge bg-secondary text-decoration-none">PR-' . (int)$row['pr_id'] . '</a></div>';

        // Col 5: Requester & Dates
        $colRequester = '<div class="fw-semibold text-dark"><i class="fas fa-user-circle text-muted me-1"></i>' . htmlspecialchars($row['requester_name'] ?? 'System') . '</div>';
        if (!empty($row['pr_created_at'])) {
            $colRequester .= '<div class="text-muted small mt-1"><span>Requested: ' . date('d M Y', strtotime($row['pr_created_at'])) . '</span></div>';
        }
        if (!empty($row['pr_required_date'])) {
            $colRequester .= '<div class="text-danger small fw-semibold">Required: ' . date('d M Y', strtotime($row['pr_required_date'])) . '</div>';
        }

        // Col 6: Order Qty
        $formattedOrderQty = ((float)$orderQty == floor((float)$orderQty)) ? number_format((float)$orderQty) : rtrim(rtrim(number_format((float)$orderQty, 2), '0'), '.');
        $colQty = '<div class="text-end fw-bold text-dark">' . $formattedOrderQty . '<div class="small text-muted fw-normal">' . htmlspecialchars($row['material_unit']) . '</div></div>';

        // Col 7: Unit Price
        $colUnitPrice = '<div class="text-end fw-semibold text-dark">&#2547;' . number_format((float)$row['unit_price'], 2) . '</div>';

        // Col 8: Total Amount
        $colTotal = '<div class="text-end fw-bold text-success">&#2547;' . number_format((float)$row['total_amount'], 2) . '</div>';

        // Col 9: Approvals
        $approvals = [];
        if (!empty($row['pm_approved_at'])) {
            $approvals[] = ['role' => 'PM', 'name' => $row['pm_approver_name'] ?? 'PM', 'date' => $row['pm_approved_at'], 'color' => 'bg-info'];
        }
        if (!empty($row['qc_approved_at'])) {
            $approvals[] = ['role' => 'QC', 'name' => $row['qc_approver_name'] ?? 'QC', 'date' => $row['qc_approved_at'], 'color' => 'bg-primary'];
        }
        if (!empty($row['mgmt_approved_at'])) {
            $approvals[] = ['role' => 'Mgmt', 'name' => $row['mgmt_approver_name'] ?? 'Management', 'date' => $row['mgmt_approved_at'], 'color' => 'bg-success'];
        }
        if (!empty($row['purchase_head_approved_at'])) {
            $approvals[] = ['role' => 'Purchase Head', 'name' => $row['ph_approver_name'] ?? 'Purchase Head', 'date' => $row['purchase_head_approved_at'], 'color' => 'bg-secondary'];
        }
        if (!empty($row['op_head_approved_at'])) {
            $approvals[] = ['role' => 'Op Head', 'name' => $row['op_approver_name'] ?? 'Operation Head', 'date' => $row['op_head_approved_at'], 'color' => 'bg-dark'];
        }
        if (!empty($row['se_approved_at'])) {
            $approvals[] = ['role' => 'Site Eng.', 'name' => $row['se_approver_name'] ?? 'Site Engineer', 'date' => $row['se_approved_at'], 'color' => 'bg-teal'];
        }

        if (!empty($approvals)) {
            $colApprovals = '<div class="d-flex flex-column gap-1">';
            foreach ($approvals as $appr) {
                $colApprovals .= '<div class="pm-approval-item"><span class="badge ' . $appr['color'] . ' px-1 py-0 me-1" style="font-size: 10px;">' . $appr['role'] . '</span><span class="fw-semibold text-dark small">' . htmlspecialchars($appr['name']) . '</span><span class="text-muted small ms-1">(' . date('d M Y', strtotime($appr['date'])) . ')</span></div>';
            }
            $colApprovals .= '</div>';
        } else {
            $colApprovals = '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Pending Approval</span>';
        }

        // Col 10: Received info
        if ($isReceived) {
            $formattedRecvQty = ((float)$recvQty == floor((float)$recvQty)) ? number_format((float)$recvQty) : rtrim(rtrim(number_format((float)$recvQty, 2), '0'), '.');
            $colReceived = '<div class="d-flex flex-column gap-1">' .
                           '<div><span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Received: ' . $formattedRecvQty . ' ' . htmlspecialchars($row['material_unit']) . '</span>';
            if ($recvQty < $orderQty) {
                $colReceived .= ' <span class="badge bg-warning text-dark small">Partial</span>';
            }
            $colReceived .= '</div>' .
                            '<div class="small fw-semibold text-dark mt-1"><i class="fas fa-user-check text-success me-1"></i>' . htmlspecialchars($row['receiver_names'] ?? 'Receiver') . '</div>';
            if (!empty($row['receiver_emp_ids']) && $row['receiver_emp_ids'] !== 'N/A') {
                $colReceived .= '<div class="small text-muted"><span>Employee ID: <strong>' . htmlspecialchars($row['receiver_emp_ids']) . '</strong></span></div>';
            }
            if (!empty($row['latest_received_date'])) {
                $colReceived .= '<div class="small text-muted"><span>Date: ' . date('d M Y', strtotime($row['latest_received_date'])) . '</span></div>';
            }
            $colReceived .= '</div>';
        } else {
            $colReceived = '<span class="badge bg-secondary-subtle text-secondary border px-2 py-1"><i class="fas fa-times-circle me-1 text-muted"></i>Not received yet</span>';
        }

        return [
            $colIndex,
            $colProject,
            $colMaterial,
            $colVendor,
            $colPoPr,
            $colRequester,
            $colQty,
            $colUnitPrice,
            $colTotal,
            $colApprovals,
            $colReceived
        ];
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

    public function updateBulkLeadTimes()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userRole = (int)($_SESSION['user']['role_id'] ?? 0);
        if (!in_array($userRole, [3, 17, 20, 31])) {
            setError("Unauthorized access.");
            header('Location: ' . $GLOBALS['base_url'] . '/con-purchase-requests');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leadtimes'])) {
            $stmt = $this->db->prepare("UPDATE con_material_categories SET pr_to_qtn_days=?, cs_analysis_days=?, approval_days=?, wo_po_days=?, adv_payment_days=?, transport_days=?, total_lead_days=? WHERE id=?");
            
            foreach ($_POST['leadtimes'] as $id => $times) {
                $pr = (int)($times['pr_to_qtn_days'] ?? 0);
                $cs = (int)($times['cs_analysis_days'] ?? 0);
                $ap = (int)($times['approval_days'] ?? 0);
                $wo = (int)($times['wo_po_days'] ?? 0);
                $adv = (int)($times['adv_payment_days'] ?? 0);
                $tr = (int)($times['transport_days'] ?? 0);
                $total = $pr + $cs + $ap + $wo + $adv + $tr;
                $catId = (int)$id;

                $stmt->bind_param("iiiiiiii", $pr, $cs, $ap, $wo, $adv, $tr, $total, $catId);
                $stmt->execute();
            }
            setSuccess("Lead times updated successfully.");
        }
        
        header('Location: ' . $GLOBALS['base_url'] . '/con-purchase-requests');
        exit;
    }
}

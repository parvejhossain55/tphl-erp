<?php
namespace Phase2;

use Controller;

require_once __DIR__ . '/../../models/AssetManagement.php';

class AssetManagementController extends Controller
{
    protected $db;
    protected $model;

    public function __construct($db)
    {
        $this->db = $db;
        $this->model = new \AssetManagement($db);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $baseUrl = $GLOBALS['base_url'] ?? '/TPHL-ERP';
        if (!isset($_SESSION['user'])) {
            header("Location: {$baseUrl}/login");
            exit;
        }
    }

    public function index()
    {
        $filters = [
            'search' => $_GET['search'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'asset_status' => $_GET['asset_status'] ?? '',
            'condition_status' => $_GET['condition_status'] ?? '',
            'project_id' => $_GET['project_id'] ?? '',
            'assigned_user_id' => $_GET['assigned_user_id'] ?? '',
        ];

        $assets = $this->model->getAllAssets($filters);
        $products = $this->model->getAllProducts(['status' => 'active']);
        $categories = $this->model->getProcurementCategories();
        $stats = $this->model->getDashboardStats();

        $projects = [];
        $pRes = $this->db->query("SELECT id, name FROM projects ORDER BY name ASC");
        if ($pRes) {
            $projects = $pRes->fetch_all(MYSQLI_ASSOC);
        }

        $users = [];
        $uRes = $this->db->query("SELECT id, full_name FROM users ORDER BY full_name ASC");
        if ($uRes) {
            $users = $uRes->fetch_all(MYSQLI_ASSOC);
        }

        $data = [
            'page_title' => 'Office Asset Management',
            'assets' => $assets,
            'products' => $products,
            'categories' => $categories,
            'projects' => $projects,
            'users' => $users,
            'stats' => $stats,
            'filters' => $filters
        ];

        $this->view('phase2/asset-management/assets', $data);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $data['created_by'] = $_SESSION['user']['id'] ?? 1;
            if ($this->model->createAsset($data)) {
                setSuccess("Asset added successfully.");
            } else {
                setError("Failed to add asset.");
            }
        }
        header("Location: " . $GLOBALS['base_url'] . "/office-assets");
        exit;
    }

    public function show($id)
    {
        $asset = $this->model->findAsset($id);
        if (!$asset) {
            setError("Asset not found.");
            header("Location: " . $GLOBALS['base_url'] . "/office-assets");
            exit;
        }
        $logs = $this->model->getMovementLog($id);
        $data = [
            'page_title' => 'Asset Details - ' . ($asset['asset_code'] ?? ''),
            'asset' => $asset,
            'logs' => $logs
        ];
        $this->view('phase2/asset-management/asset-detail', $data);
    }

    public function getLogs($id)
    {
        header('Content-Type: application/json');
        $logs = $this->model->getMovementLog($id);
        echo json_encode(['success' => true, 'logs' => $logs]);
        exit;
    }

    public function updateAssetCode($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code = $_POST['asset_code'] ?? '';
            if ($this->model->updateAsset($id, ['asset_code' => $code])) {
                setSuccess("Asset code updated.");
            } else {
                setError("Failed to update code.");
            }
        }
        header("Location: " . $GLOBALS['base_url'] . "/office-assets/show/" . $id);
        exit;
    }

    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($this->model->updateAsset($id, $_POST)) {
                setSuccess("Asset updated successfully.");
            } else {
                setError("Failed to update asset.");
            }
        }
        header("Location: " . $GLOBALS['base_url'] . "/office-assets/show/" . $id);
        exit;
    }

    public function transfer($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_SESSION['user']['id'] ?? 1;
            if ($this->model->logMovement($id, 'transferred', $_POST['note'] ?? '', $userId)) {
                setSuccess("Asset transferred successfully.");
            } else {
                setError("Failed to transfer asset.");
            }
        }
        header("Location: " . $GLOBALS['base_url'] . "/office-assets/show/" . $id);
        exit;
    }

    public function bulkTransfer()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $assetIds = $_POST['asset_ids'] ?? [];
            $userId = $_SESSION['user']['id'] ?? 1;
            foreach ($assetIds as $aid) {
                $this->model->logMovement($aid, 'transferred', $_POST['note'] ?? '', $userId);
            }
            setSuccess("Bulk transfer completed.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/office-assets");
        exit;
    }

    public function updateCondition($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_SESSION['user']['id'] ?? 1;
            if ($this->model->updateAsset($id, ['condition_status' => $_POST['condition_status'] ?? 'ok'])) {
                $this->model->logMovement($id, 'condition_change', $_POST['note'] ?? '', $userId);
                setSuccess("Condition updated.");
            } else {
                setError("Failed condition update.");
            }
        }
        header("Location: " . $GLOBALS['base_url'] . "/office-assets/show/" . $id);
        exit;
    }

    public function dispose($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_SESSION['user']['id'] ?? 1;
            if ($this->model->updateAsset($id, ['asset_status' => 'disposed'])) {
                $this->model->logMovement($id, 'disposed', $_POST['reason'] ?? '', $userId);
                setSuccess("Asset disposed.");
            } else {
                setError("Failed asset disposal.");
            }
        }
        header("Location: " . $GLOBALS['base_url'] . "/office-assets");
        exit;
    }

    public function products()
    {
        $products = $this->model->getAllProducts();
        $categories = $this->model->getProcurementCategories();
        $stockCounts = $this->model->getProductStockCounts();

        $data = [
            'page_title' => 'Asset Products Catalog',
            'products' => $products,
            'categories' => $categories,
            'stockCounts' => $stockCounts
        ];
        $this->view('phase2/asset-management/products', $data);
    }

    public function storeProduct()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $data['created_by'] = $_SESSION['user']['id'] ?? 1;
            if ($this->model->createProduct($data)) {
                setSuccess("Product added to catalog.");
            } else {
                setError("Failed to add product.");
            }
        }
        header("Location: " . $GLOBALS['base_url'] . "/asset-products");
        exit;
    }

    public function updateProduct()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)$_POST['id'];
            if ($this->model->updateProduct($id, $_POST)) {
                setSuccess("Product updated.");
            } else {
                setError("Failed product update.");
            }
        }
        header("Location: " . $GLOBALS['base_url'] . "/asset-products");
        exit;
    }

    public function deleteProduct()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)$_POST['id'];
            if ($this->model->deleteProduct($id)) {
                setSuccess("Product deleted.");
            } else {
                setError("Failed product deletion.");
            }
        }
        header("Location: " . $GLOBALS['base_url'] . "/asset-products");
        exit;
    }

    public function storeRepairRequisition($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_SESSION['user']['id'] ?? 1;
            $this->model->logMovement($id, 'repair_requested', $_POST['note'] ?? '', $userId);
            setSuccess("Repair requisition submitted.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/office-assets/show/" . $id);
        exit;
    }

    public function extraPurchases()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $userId = (int)($_SESSION['user']['id'] ?? 0);

        // Approver / Admin / Procurement / Management / Accounts roles see all requests by default
        $isApprover = in_array($roleId, [1, 2, 3, 6, 17, 20, 22, 23, 24, 25, 30, 31]);
        $activeTab  = $_GET['tab'] ?? ($isApprover ? 'all_requests' : 'my_requests');

        $filters = [
            'department_id'  => $_GET['department_id'] ?? '',
            'workplace'      => $_GET['workplace'] ?? '',
            'project_id'     => $_GET['project_id'] ?? '',
            'overall_status' => $_GET['status'] ?? '',
        ];

        if (!$isApprover || $activeTab === 'my_requests') {
            $filters['created_by'] = $userId;
        }

        $requests = $this->model->getExtraPurchases($filters);
        $categories = $this->model->getProcurementCategories();

        $departments = [];
        $dRes = $this->db->query("SELECT id, name FROM departments ORDER BY name ASC");
        if ($dRes) {
            $departments = $dRes->fetch_all(MYSQLI_ASSOC);
        }

        $workplaces = [];
        $wRes = $this->db->query("SELECT DISTINCT workplace FROM employees WHERE workplace IS NOT NULL AND workplace != '' ORDER BY workplace ASC");
        if ($wRes) {
            $workplaces = $wRes->fetch_all(MYSQLI_ASSOC);
        }

        $projects = [];
        $pRes = $this->db->query("SELECT id, name FROM projects ORDER BY name ASC");
        if ($pRes) {
            $projects = $pRes->fetch_all(MYSQLI_ASSOC);
        }

        $users = [];
        $uRes = $this->db->query("SELECT id, full_name FROM users ORDER BY full_name ASC");
        if ($uRes) {
            $users = $uRes->fetch_all(MYSQLI_ASSOC);
        }

        $bankAccounts = [];
        $bRes = $this->db->query("SELECT id, account_code, account_name FROM chart_of_accounts WHERE account_code LIKE '1110%' AND account_code != '1110' AND status = 'active' ORDER BY account_code ASC");
        if ($bRes) {
            $bankAccounts = $bRes->fetch_all(MYSQLI_ASSOC);
        }

        $data = [
            'page_title'   => 'Asset Procurement & Extra Purchases',
            'requests'     => $requests,
            'categories'   => $categories,
            'departments'  => $departments,
            'workplaces'   => $workplaces,
            'projects'     => $projects,
            'users'        => $users,
            'bankAccounts' => $bankAccounts,
            'isApprover'   => $isApprover,
            'activeTab'    => $activeTab
        ];
        $this->view('phase2/asset-management/extra-purchases', $data);
    }

    public function storeExtraPurchase()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $data['requested_by'] = $_SESSION['user']['id'] ?? 1;
            if ($this->model->createExtraPurchase($data)) {
                setSuccess("Extra purchase request submitted.");
            } else {
                setError("Failed extra purchase submission.");
            }
        }
        header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
        exit;
    }

    public function approveExtraPurchase($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = (int)($_SESSION['user']['id'] ?? 1);
            $step = $_POST['step'] ?? 'operations';
            if ($step === 'management') {
                $step = 'mgmt';
            }
            $status = $_POST['approval_status'] ?? $_POST['status'] ?? 'approved';
            $note = $_POST['approval_note'] ?? $_POST['note'] ?? '';

            $document = null;
            if (isset($_FILES['purchase_head_document']) && $_FILES['purchase_head_document']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = dirname(dirname(dirname(__DIR__))) . '/public/uploads/procurement/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $filename = 'doc_' . time() . '_' . rand(1000, 9999) . '.' . pathinfo($_FILES['purchase_head_document']['name'], PATHINFO_EXTENSION);
                if (move_uploaded_file($_FILES['purchase_head_document']['tmp_name'], $uploadDir . $filename)) {
                    $document = 'uploads/procurement/' . $filename;
                }
            }

            if ($this->model->approveStep($id, $step, $status, $userId, $note, $document)) {
                // ====== AUTO POSTING GENERAL JOURNAL (Batch 2/3) ======
                if ($status === 'approved') {
                    try {
                        require_once dirname(dirname(__DIR__)) . '/services/AutoPostingService.php';
                        $autoPosting = new \AutoPostingService($this->db);
                        $assetExpenseAccId = $autoPosting->getAccountIdByCode('5220'); // Office & Asset Expense
                        $vendorPayableAccId = $autoPosting->getAccountIdByCode('2110'); // Accounts Payable - Vendors
                        
                        $req = $this->model->findExtraPurchase($id);
                        if ($req && (float)$req['total_price'] > 0) {
                            $autoPosting->postAccrualEntry(
                                'asset_procurement',
                                $id,
                                $assetExpenseAccId,
                                $vendorPayableAccId,
                                $req['total_price'],
                                "Office Asset Procurement (PR-{$id}): " . $req['item_name'] . " (Qty: {$req['quantity']})",
                                $req['project_id'],
                                'employee',
                                $req['assigned_to'] ?? $req['requested_by'],
                                $userId
                            );
                        }
                    } catch (\Exception $e) {
                        error_log("AutoPosting Exception (Procurement Approval): " . $e->getMessage());
                    }
                }
                // ======================================================

                setSuccess("Purchase request status updated successfully to: " . ucfirst($status));
            } else {
                setError("Failed request approval.");
            }
        }
        header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
        exit;
    }

    public function markPurchased($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = (int)($_SESSION['user']['id'] ?? 1);
            $paymentMethod = $_POST['payment_method'] ?? 'Cash';
            $paidAmount = (float)($_POST['paid_amount'] ?? 0);

            $attachment = null;
            if (isset($_FILES['payment_attachment']) && $_FILES['payment_attachment']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = dirname(dirname(dirname(__DIR__))) . '/public/uploads/procurement/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $filename = 'pay_' . time() . '_' . rand(1000, 9999) . '.' . pathinfo($_FILES['payment_attachment']['name'], PATHINFO_EXTENSION);
                if (move_uploaded_file($_FILES['payment_attachment']['tmp_name'], $uploadDir . $filename)) {
                    $attachment = 'uploads/procurement/' . $filename;
                }
            }

            if ($this->model->processPayment($id, $userId, $paymentMethod, $paidAmount, $attachment)) {
                // ====== AUTO POSTING PAYMENT VOUCHER (Batch 2/3) ======
                try {
                    require_once dirname(dirname(__DIR__)) . '/services/AutoPostingService.php';
                    $autoPosting = new \AutoPostingService($this->db);
                    $vendorPayableAccId = $autoPosting->getAccountIdByCode('2110'); // Accounts Payable - Vendors
                    
                    $paymentAccountId = !empty($_POST['payment_account_id']) ? (int)$_POST['payment_account_id'] : null;

                    if ($paymentAccountId) {
                        $bankAccId = $paymentAccountId;
                    } else {
                        $payAccCode = '1110-002'; // Default DBBL Bank
                        $methodLower = strtolower($paymentMethod);
                        if (strpos($methodLower, 'cash') !== false) {
                            $payAccCode = '1110-001'; // Petty Cash Account
                        } elseif (strpos($methodLower, 'islami') !== false) {
                            $payAccCode = '1110-003'; // Islami Bank Main Account
                        }
                        $bankAccId = $autoPosting->getAccountIdByCode($payAccCode);
                    }

                    $req = $this->model->findExtraPurchase($id);
                    $amountToPay = $paidAmount > 0 ? $paidAmount : ($req['total_price'] ?? 0);

                    if ($req && $amountToPay > 0) {
                        $autoPosting->postDisbursementEntry(
                            'asset_procurement',
                            $id,
                            $vendorPayableAccId,
                            $bankAccId,
                            $amountToPay,
                            "Payment Disbursement for Office Asset Procurement (PR-{$id}): " . ($req['item_name'] ?? ''),
                            $req['project_id'] ?? null,
                            'employee',
                            $req['assigned_to'] ?? $req['requested_by'] ?? 1,
                            $userId
                        );
                    }
                } catch (\Exception $e) {
                    error_log("AutoPosting Exception (Procurement Payment): " . $e->getMessage());
                }
                // =====================================================

                setSuccess("Procurement marked as paid.");
            } else {
                setError("Failed procurement payment.");
            }
        }
        header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
        exit;
    }

    public function confirmPurchased($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = (int)($_SESSION['user']['id'] ?? 1);
            $note = $_POST['purchase_note'] ?? $_POST['note'] ?? '';

            $document = null;
            if (isset($_FILES['purchase_document']) && $_FILES['purchase_document']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = dirname(dirname(dirname(__DIR__))) . '/public/uploads/procurement/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $filename = 'purch_' . time() . '_' . rand(1000, 9999) . '.' . pathinfo($_FILES['purchase_document']['name'], PATHINFO_EXTENSION);
                if (move_uploaded_file($_FILES['purchase_document']['tmp_name'], $uploadDir . $filename)) {
                    $document = 'uploads/procurement/' . $filename;
                }
            }

            if ($this->model->confirmPurchased($id, $userId, $document, $note)) {
                setSuccess("Procurement confirmed & added to Asset Inventory.");
            } else {
                setError("Failed procurement confirmation.");
            }
        }
        header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
        exit;
    }

    public function storeProcurementCategory()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            if ($name && $this->model->addProcurementCategory($name)) {
                setSuccess("Category added.");
            } else {
                setError("Failed to add category.");
            }
        }
        header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
        exit;
    }

    public function updateProcurementCategory()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)$_POST['id'];
            $name = trim($_POST['name'] ?? '');
            if ($name && $this->model->updateProcurementCategory($id, $name)) {
                setSuccess("Category updated.");
            } else {
                setError("Failed category update.");
            }
        }
        header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
        exit;
    }

    public function deleteProcurementCategory()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)$_POST['id'];
            if ($this->model->deleteProcurementCategory($id)) {
                setSuccess("Category deleted.");
            } else {
                setError("Failed category deletion.");
            }
        }
        header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
        exit;
    }
}
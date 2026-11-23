<?php
namespace Phase2;

use Controller;

require_once __DIR__ . '/../../models/AssetManagement.php';
require_once __DIR__ . '/../../views/accounts/AccountMailService.php';

class AssetManagementController extends Controller
{
    protected $db;
    protected $model;
    protected $mailService;

    public function __construct($db)
    {
        $this->db = $db;
        $this->model = new \AssetManagement($db);
        $this->mailService = new \AccountMailService($db);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $baseUrl = $GLOBALS['base_url'] ?? '/TPHL-ERP';
        if (!isset($_SESSION['user'])) {
            header("Location: {$baseUrl}/login");
            exit;
        }
    }

    private function canUserEdit()
    {
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        if (function_exists('hasPermission')) {
            if (hasPermission('Asset Management') || hasPermission('QC & Internal Audit')) {
                return true;
            }
        }
        return in_array($roleId, [1, 3, 33, 6, 17, 20, 22, 23, 24, 25, 30, 31, 32]);
    }

    public function index()
    {
        $filters = [
            'search' => $_GET['search'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'asset_status' => $_GET['asset_status'] ?? '',
            'condition_status' => $_GET['condition_status'] ?? '',
            'project_id' => $_GET['project_id'] ?? '',
            'office_branch_id' => $_GET['office_branch_id'] ?? '',
            'assigned_user_id' => $_GET['assigned_user_id'] ?? '',
        ];

        // Ensure all registered assets with purchase price are synchronized to the Balance Sheet under Non-Current Assets
        $this->model->syncAllUnpostedAssetsToBalanceSheet((int)($_SESSION['user']['id'] ?? 1));

        $assets = $this->model->getAllAssets($filters);
        $products = $this->model->getAllProducts(['status' => 'active']);
        $categories = $this->model->getProcurementCategories();
        $branches = $this->model->getAllOfficeBranches();
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

        $empBranchMap = [];
        $ebRes = $this->db->query("SELECT user_id, office_branch_id FROM employees WHERE user_id IS NOT NULL AND office_branch_id IS NOT NULL");
        if ($ebRes) {
            while ($row = $ebRes->fetch_assoc()) {
                $empBranchMap[(int)$row['user_id']] = (int)$row['office_branch_id'];
            }
        }

        $data = [
            'page_title' => 'Office Asset Management',
            'assets' => $assets,
            'products' => $products,
            'categories' => $categories,
            'branches' => $branches,
            'projects' => $projects,
            'users' => $users,
            'empBranchMap' => $empBranchMap,
            'stats' => $stats,
            'filters' => $filters,
            'canEdit' => $this->canUserEdit(),
            'coaList' => getCOAExpenseList($this->db)
        ];

        $this->view('phase2/asset-management/assets', $data);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $data['created_by'] = $_SESSION['user']['id'] ?? 1;
            if ($assetId = $this->model->createAsset($data)) {
                // Auto-post to double-entry accounting ledger so purchase value reflects in Balance Sheet (Non-Current Assets)
                $this->model->syncAssetToBalanceSheet($assetId, (int)($_SESSION['user']['id'] ?? 1));
                setSuccess("Asset added successfully & synchronized to Balance Sheet.");
            } else {
                setError("Failed to add asset.");
            }
        }
        header("Location: " . $GLOBALS['base_url'] . "/office-assets");
        exit;
    }

    public function storeProductQuick()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $data['created_by'] = $_SESSION['user']['id'] ?? 1;

            if (empty($data['product_code'])) {
                $data['product_code'] = 'PRD-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            }

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = dirname(dirname(dirname(__DIR__))) . '/public/uploads/products/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $filename = 'prod_' . time() . '_' . rand(1000, 9999) . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                    $data['image_path'] = 'uploads/products/' . $filename;
                }
            }

            if ($this->model->createProduct($data)) {
                setSuccess("Product catalog item created successfully.");
            } else {
                setError("Failed to create product catalog item.");
            }
        }
        header("Location: " . $GLOBALS['base_url'] . "/office-assets");
        exit;
    }

    public function storeCategoryQuick()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            if ($name && $this->model->addProcurementCategory($name)) {
                setSuccess("Product category added successfully.");
            } else {
                setError("Failed to add product category.");
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
            'logs' => $logs,
            'canEdit' => $this->canUserEdit()
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
                $this->model->syncAssetToBalanceSheet($id, (int)($_SESSION['user']['id'] ?? 1));
                setSuccess("Asset updated successfully & synchronized to Balance Sheet.");
            } else {
                setError("Failed to update asset.");
            }
        }
        $redirectUrl = $_SERVER['HTTP_REFERER'] ?? ($GLOBALS['base_url'] . "/office-assets");
        header("Location: " . $redirectUrl);
        exit;
    }

    public function transfer($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_SESSION['user']['id'] ?? 1;
            $oldAsset = $this->model->findAsset($id);

            $toProjectId = isset($_POST['project_id']) && $_POST['project_id'] !== '' ? (int)$_POST['project_id'] : null;
            $toBranchId = isset($_POST['office_branch_id']) && $_POST['office_branch_id'] !== '' ? (int)$_POST['office_branch_id'] : null;
            $toAssignedTo = isset($_POST['assigned_to']) && $_POST['assigned_to'] !== '' ? (int)$_POST['assigned_to'] : null;
            $newCondition = $_POST['new_condition'] ?? ($oldAsset['condition_status'] ?? 'good');
            $toLocation = $_POST['location'] ?? ($oldAsset['location'] ?? 'Head Office');
            $notes = $_POST['notes'] ?? ($_POST['note'] ?? 'Asset Transferred');

            $updateData = [
                'item_name' => $oldAsset['item_name'] ?? 'Office Asset',
                'project_id' => $toProjectId,
                'office_branch_id' => $toBranchId,
                'assigned_to' => $toAssignedTo,
                'condition_status' => $newCondition,
                'location' => $toLocation,
                'asset_status' => 'transferred'
            ];

            // 1. Update asset current record
            $this->model->updateAsset($id, $updateData);

            // 2. Log movement (From -> To)
            $logData = [
                'asset_id' => $id,
                'action' => 'transferred',
                'from_location' => $oldAsset['location'] ?? 'Head Office',
                'to_location' => $toLocation,
                'from_user_id' => $oldAsset['assigned_to'] ?? null,
                'to_user_id' => $toAssignedTo,
                'from_project_id' => $oldAsset['project_id'] ?? null,
                'to_project_id' => $toProjectId,
                'old_condition' => $oldAsset['condition_status'] ?? 'good',
                'new_condition' => $newCondition,
                'notes' => $notes,
                'performed_by' => $userId
            ];

            if ($this->model->logMovement($logData)) {
                setSuccess("Asset transferred successfully.");
            } else {
                setError("Failed to log transfer.");
            }
        }
        $redirectUrl = $_SERVER['HTTP_REFERER'] ?? ($GLOBALS['base_url'] . "/office-assets");
        header("Location: " . $redirectUrl);
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
            'stockCounts' => $stockCounts,
            'canEdit' => $this->canUserEdit()
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

        $roleNameLower  = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
        $isChiefOfficer = (in_array($roleId, [33, 34, 35], true) || in_array($roleNameLower, ['chief officer', 'management viewer', 'seal ceo'], true));
        $isSuperAdmin   = (in_array($roleId, [1, 3, 38], true) || in_array($roleNameLower, ['super admin', 'admin', 'management', 'board of director', 'board of directors'], true) || strpos($roleNameLower, 'director') !== false || strpos($roleNameLower, 'dceo') !== false) && !$isChiefOfficer; // Admin (1), Super Admin (3), Board/DCEO (38)
        $isOperations   = ($isSuperAdmin || in_array((int)$roleId, [20, 32], true) || strpos($roleNameLower, 'operation head') !== false || strpos($roleNameLower, 'admin & operation') !== false) && !$isChiefOfficer;
        $isOpHead       = (in_array((int)$roleId, [20, 32], true) || strpos($roleNameLower, 'operation head') !== false || strpos($roleNameLower, 'admin & operation') !== false) && !$isChiefOfficer;
        $isPurchaseHead = ($isSuperAdmin || in_array($roleId, [17, 31])) && !$isChiefOfficer;
        $isQcAudit      = ($isSuperAdmin || in_array($roleId, [24, 25])) && !$isChiefOfficer;
        $isManagement   = $isSuperAdmin && !$isChiefOfficer;
        $isAccounts     = ($isSuperAdmin || in_array($roleId, [6, 30]) || strpos($roleNameLower, 'account') !== false) && !$isChiefOfficer;
        $isGlobalViewer = in_array($roleId, [1, 3, 38, 33, 34, 35, 6, 17, 20, 24, 25, 30, 31, 32]);
        $isApprover     = ($isGlobalViewer || $userId === 242) && !$isChiefOfficer;

        $activeTab  = $_GET['tab'] ?? ($isApprover ? 'pending_approval' : 'all_requests');

        $filters = [
            'department_id'    => $_GET['department_id'] ?? '',
            'office_branch_id' => $_GET['office_branch_id'] ?? '',
            'workplace'        => $_GET['workplace'] ?? '',
            'project_id'       => $_GET['project_id'] ?? '',
            'overall_status'   => $_GET['status'] ?? '',
            'user_id'          => $userId,
            'role_id'          => $roleId,
            'tab'              => $activeTab,
            'is_admin'         => $isSuperAdmin,
            'is_global_viewer' => $isGlobalViewer,
            'is_operations'    => $isOperations,
            'is_op_head'       => $isOpHead,
            'is_purchase_head' => $isPurchaseHead,
            'is_qc_audit'      => $isQcAudit,
            'is_management'    => $isManagement,
            'is_accounts'      => $isAccounts,
        ];

        if ($activeTab === 'my_requests') {
            $filters['created_by'] = $userId;
        } elseif (!$isGlobalViewer && !in_array($activeTab, ['all_requests', 'pending_approval'])) {
            $filters['restrict_user'] = true;
        }

        // Sync with central approval_requests so DB statuses and approve_roles stay fresh
        try {
            require_once dirname(dirname(__DIR__)) . '/models/ApprovalRequest.php';
            $appModel = new \ApprovalRequest($this->db);
            $appModel->syncMissingOfficeProcurements();
        } catch (\Throwable $e) {
            error_log('Sync office procurement to approval_requests failed: ' . $e->getMessage());
        }

        $requests = $this->model->getExtraPurchases($filters);

        $pendingCount = 0;
        if ($isApprover) {
            $pendingFilters = $filters;
            $pendingFilters['tab'] = 'pending_approval';
            unset($pendingFilters['created_by']);
            $pendingList = $this->model->getExtraPurchases($pendingFilters);
            $pendingCount = count($pendingList);
        }

        $categories = $this->model->getProcurementCategories();

        $departments = [];
        $dRes = $this->db->query("SELECT id, name FROM departments ORDER BY name ASC");
        if ($dRes) {
            $departments = $dRes->fetch_all(MYSQLI_ASSOC);
        }

        $officeBranches = $this->model->getAllOfficeBranches('active');
        $workplaces = [];
        foreach ($officeBranches as $ob) {
            $workplaces[] = ['workplace' => $ob['name'], 'id' => $ob['id']];
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

        $vendors = [];
        $vRes = $this->db->query("SELECT id, COALESCE(NULLIF(TRIM(company_name), ''), name) AS name, company_name FROM vendors WHERE LOWER(status) = 'active' ORDER BY name ASC");
        if ($vRes) {
            $vendors = $vRes->fetch_all(MYSQLI_ASSOC);
        }

        $autoOpenRequest = null;
        $viewId = (int)($_GET['view'] ?? $_GET['id'] ?? 0);
        if ($viewId > 0) {
            $autoOpenRequest = $this->model->findExtraPurchase($viewId);
            if ($autoOpenRequest) {
                $reqTotal = (float)($autoOpenRequest['total_price'] ?? 0);
                $isOver1Lac = ($reqTotal > 10000);
                $isOpHeadUser = (in_array((int)$roleId, [20, 32], true) || strpos(strtolower($_SESSION['user']['role_name'] ?? ''), 'operation head') !== false || strpos(strtolower($_SESSION['user']['role_name'] ?? ''), 'admin & operation') !== false);
                $isOpHeadApproved = (!empty($autoOpenRequest['op_head_status']) && strtolower($autoOpenRequest['op_head_status']) === 'approved');
                $isCreator = (int)$autoOpenRequest['requested_by'] === $userId;
                $isReporterBoss = !empty($autoOpenRequest['requester_reporting_boss_id']) && (int)$autoOpenRequest['requester_reporting_boss_id'] === $userId;

                $canApprove = false;
                $step = '';
                if (!$isCreator && !$isChiefOfficer && ($autoOpenRequest['overall_status'] ?? '') !== 'rejected') {
                    $dhStatus = $autoOpenRequest['dept_head_status'] ?? 'not_required';
                    $itStatus = $autoOpenRequest['it_head_status'] ?? 'not_required';
                    if (($isReporterBoss || $isSuperAdmin) && $dhStatus === 'pending') {
                        $canApprove = true; $step = 'dept_head';
                    } elseif ($userId === 242 && in_array($dhStatus, ['approved', 'not_required']) && $itStatus === 'pending') {
                        $canApprove = true; $step = 'it_head';
                    } elseif ($isOperations && in_array($dhStatus, ['approved', 'not_required']) && in_array($itStatus, ['approved', 'not_required']) && $autoOpenRequest['operations_status'] === 'pending') {
                        $canApprove = true; $step = 'operations';
                    } elseif ($isPurchaseHead && $autoOpenRequest['operations_status'] === 'approved' && $autoOpenRequest['purchase_head_status'] === 'pending') {
                        $canApprove = true; $step = 'purchase_head';
                    } elseif ($isQcAudit && $autoOpenRequest['purchase_head_status'] === 'approved' && $autoOpenRequest['qc_audit_status'] === 'pending') {
                        $canApprove = true; $step = 'qc_audit';
                    } elseif ($autoOpenRequest['qc_audit_status'] === 'approved' && $autoOpenRequest['mgmt_status'] === 'pending') {
                        $isAhApprovedAuto = (empty($autoOpenRequest['approval_request_id']) || strtolower($autoOpenRequest['accounts_head_status'] ?? '') === 'approved');
                        if ($isOver1Lac) {
                            if (!$isOpHeadApproved) {
                                if ($isOpHeadUser || $isManagement) {
                                    $canApprove = true; $step = 'mgmt';
                                }
                            } elseif ($isAhApprovedAuto && $isManagement) {
                                $canApprove = true; $step = 'mgmt';
                            }
                        } else {
                            if (!$isOpHeadApproved && ($isOpHeadUser || $isManagement)) {
                                $canApprove = true; $step = 'mgmt';
                            }
                        }
                    }
                }
                $isAhApprovedAuto = (empty($autoOpenRequest['approval_request_id']) || strtolower($autoOpenRequest['accounts_head_status'] ?? '') === 'approved');
                $isOpHeadPostQcAuto = $isOpHeadApproved || (!$isOver1Lac && ($autoOpenRequest['mgmt_status'] ?? '') === 'approved');
                $autoOpenRequest['can_approve'] = $canApprove;
                $autoOpenRequest['approval_step'] = $step;
                $autoOpenRequest['can_approve_ah'] = (in_array((int)$roleId, [30, 1, 3, 38], true) || strpos($roleNameLower, 'accounts head') !== false) && ($autoOpenRequest['qc_audit_status'] ?? '') === 'approved' && $isOpHeadPostQcAuto && ($autoOpenRequest['payment_status'] ?? '') === 'unpaid' && !$isAhApprovedAuto && !empty($autoOpenRequest['approval_request_id']);
                $autoOpenRequest['can_pay'] = !empty($isAccounts) && (($autoOpenRequest['mgmt_status'] ?? '') === 'approved' || ($autoOpenRequest['overall_status'] ?? '') === 'approved') && ($autoOpenRequest['payment_status'] ?? '') === 'unpaid' && $isAhApprovedAuto;
                $autoOpenRequest['is_waiting_ah'] = (($autoOpenRequest['qc_audit_status'] ?? '') === 'approved' && $isOpHeadPostQcAuto && ($autoOpenRequest['payment_status'] ?? '') === 'unpaid' && !$isAhApprovedAuto);
            }
        }

        $data = [
            'page_title'       => 'Asset Procurement & Extra Purchases',
            'requests'         => $requests,
            'categories'       => $categories,
            'departments'      => $departments,
            'officeBranches'   => $officeBranches,
            'workplaces'       => $workplaces,
            'projects'         => $projects,
            'users'            => $users,
            'bankAccounts'     => $bankAccounts,
            'isApprover'       => $isApprover,
            'activeTab'        => $activeTab,
            'pendingCount'     => $pendingCount,
            'autoOpenRequest'  => $autoOpenRequest,
            'coaList'          => getCOAExpenseList($this->db),
            'vendors'          => $vendors
        ];
        $this->view('phase2/asset-management/extra-purchases', $data);
    }

    public function storeExtraPurchase()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $data['requested_by'] = $_SESSION['user']['id'] ?? 1;

            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = dirname(dirname(dirname(__DIR__))) . '/public/uploads/procurement/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $filename = 'req_' . time() . '_' . rand(1000, 9999) . '.' . pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
                if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . $filename)) {
                    $data['attachment'] = 'uploads/procurement/' . $filename;
                }
            }

            if ($requestId = $this->model->createExtraPurchase($data)) {
                // Sync to central approval_requests
                try {
                    require_once dirname(dirname(__DIR__)) . '/models/ApprovalRequest.php';
                    $appModel = new \ApprovalRequest($this->db);
                    $appModel->syncMissingOfficeProcurements();
                } catch (\Throwable $e) {
                    error_log('Sync office procurement to approval_requests failed: ' . $e->getMessage());
                }

                try {
                    $request = $this->model->findExtraPurchase($requestId);
                    if ($request) {
                        if (($request['dept_head_status'] ?? 'not_required') === 'pending' && !empty($request['reporting_user_id'])) {
                            $this->mailService->sendOfficeProcurementToUser(
                                $request,
                                (int)$request['reporting_user_id'],
                                'Department Head / Reporting Boss'
                            );
                        } elseif (($request['it_head_status'] ?? 'not_required') === 'pending') {
                            $this->mailService->sendOfficeProcurementToUser(
                                $request,
                                242,
                                'IT Head'
                            );
                        } else {
                            $this->mailService->sendOfficeProcurementToApprovalRoles(
                                $request,
                                [32],
                                'Admin & Operation'
                            );
                        }
                    }
                } catch (\Throwable $e) {
                    error_log('Office Procurement email (submission) failed: ' . $e->getMessage());
                }
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
            $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
            $userId = (int)($_SESSION['user']['id'] ?? 1);
            $isChiefOfficer = (in_array($roleId, [33, 34, 35], true) || in_array(strtolower(trim($_SESSION['user']['role_name'] ?? '')), ['chief officer', 'management viewer', 'seal ceo'], true));
            if ($isChiefOfficer) {
                setError("Access denied: You have view-only access.");
                header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
                exit;
            }
            $req = $this->model->findExtraPurchase($id);

            if ($req && (int)$req['requested_by'] === $userId) {
                setError("You cannot approve or reject your own purchase request.");
                header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
                exit;
            }

            if ($req && (($req['overall_status'] ?? '') === 'rejected' || in_array('rejected', [
                $req['dept_head_status'] ?? '',
                $req['it_head_status'] ?? '',
                $req['operations_status'] ?? '',
                $req['purchase_head_status'] ?? '',
                $req['qc_audit_status'] ?? '',
                $req['mgmt_status'] ?? ''
            ], true))) {
                setError("This request has already been rejected and cannot be approved or processed further.");
                header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
                exit;
            }

            $step = $_POST['step'] ?? 'qc_audit';
            if ($step === 'management') {
                $step = 'mgmt';
            }

            $reqTotal = (float)($req['total_price'] ?? 0);
            $isOver1Lac = ($reqTotal > 10000);
            $roleNameLc = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
            $isOpHeadUser = (in_array((int)$roleId, [20, 32], true) || strpos($roleNameLc, 'operation head') !== false || strpos($roleNameLc, 'admin & operation') !== false);
            $isMgmtUser = in_array((int)$roleId, [1, 3, 38], true) || in_array($roleNameLc, ['super admin', 'admin', 'management', 'board of director', 'board of directors'], true) || strpos($roleNameLc, 'director') !== false || strpos($roleNameLc, 'dceo') !== false;
            $isRole20Eligible = ($isOpHeadUser && ($reqTotal <= 10000 || $step === 'mgmt'));

            // Guard: If non-Management user is trying to approve while earlier steps are still pending
            $dhStatus = $req['dept_head_status'] ?? 'not_required';
            $itStatus = $req['it_head_status'] ?? 'not_required';
            if (!$isMgmtUser) {
                if ($step === 'it_head' && $dhStatus === 'pending') {
                    setError("This request is currently pending Department Head approval.");
                    header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
                    exit;
                }
                if ($step === 'operations' && $dhStatus === 'pending') {
                    setError("This request is currently pending Department Head approval.");
                    header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
                    exit;
                }
                if ($step === 'operations' && $itStatus === 'pending') {
                    setError("This request is currently pending IT Head approval.");
                    header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
                    exit;
                }
                if ($step === 'purchase_head' && ($req['operations_status'] ?? 'pending') !== 'approved') {
                    setError("This request is currently pending Operations approval.");
                    header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
                    exit;
                }
                if ($step === 'qc_audit' && ($req['purchase_head_status'] ?? 'pending') !== 'approved') {
                    setError("This request is currently pending Purchase Head approval.");
                    header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
                    exit;
                }
                if ($step === 'mgmt' && ($req['qc_audit_status'] ?? 'pending') !== 'approved') {
                    setError("This request is currently pending QC & Audit approval.");
                    header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
                    exit;
                }
            }

            // Role permission validation for step approval
            $canApproveStep = false;

            if ($isMgmtUser) {
                $canApproveStep = true;
            } else {
                if ($step === 'dept_head') {
                    $reportingBossId = (int)($req['reporting_user_id'] ?? $req['requester_reporting_boss_id'] ?? 0);
                    if ($reportingBossId === $userId) {
                        $canApproveStep = true;
                    }
                } elseif ($step === 'it_head') {
                    if ($userId === 242) {
                        $canApproveStep = true;
                    }
                } elseif ($step === 'operations') {
                    if ((int)$roleId === 32 || $isOpHeadUser) {
                        $canApproveStep = true;
                    }
                } elseif ($step === 'purchase_head') {
                    if (in_array($roleId, [17, 31])) {
                        $canApproveStep = true;
                    }
                } elseif ($step === 'qc_audit') {
                    if (in_array($roleId, [24, 25]) || ($isOpHeadUser && $reqTotal <= 10000)) {
                        $canApproveStep = true;
                    }
                } elseif ($step === 'mgmt') {
                    if ($isMgmtUser || $isRole20Eligible) {
                        $canApproveStep = true;
                    }
                }
            }

            if (!$canApproveStep) {
                setError("You do not have permission to approve this step.");
                header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
                exit;
            }
            $status = $_POST['approval_status'] ?? $_POST['status'] ?? 'approved';
            $note = $_POST['approval_note'] ?? $_POST['note'] ?? '';
            $approveMode = $_POST['approve_mode'] ?? 'override';

            // Management Single-Stage Approval (approve ONLY current pending stage)
            if ($isMgmtUser && $status === 'approved' && $approveMode === 'single') {
                $arCheckEp = $this->db->query("SELECT id, op_head_status, accounts_head_status FROM approval_requests WHERE source_table IN ('project_extra_assets', 'purchase_requests') AND source_id = {$id} ORDER BY id DESC LIMIT 1");
                $arRowEp = $arCheckEp ? $arCheckEp->fetch_assoc() : null;
                $opHeadEp = strtolower($arRowEp['op_head_status'] ?? 'pending');
                $ahEp     = strtolower($arRowEp['accounts_head_status'] ?? 'pending');
                $escSingleNote = $this->db->real_escape_string($note ?: 'Approved by Management');

                if (($req['dept_head_status'] ?? '') === 'pending') {
                    $this->model->approveStep($id, 'dept_head', 'approved', $userId, ($note ?: 'Approved') . ' (On behalf of Dept. Head)');
                    setSuccess('Department Head stage approved by Management.');
                    header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
                    exit;
                } elseif (($req['it_head_status'] ?? '') === 'pending') {
                    $this->model->approveStep($id, 'it_head', 'approved', $userId, ($note ?: 'Approved') . ' (On behalf of IT Head)');
                    setSuccess('IT Head stage approved by Management.');
                    header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
                    exit;
                } elseif (($req['operations_status'] ?? '') === 'pending') {
                    $this->model->approveStep($id, 'operations', 'approved', $userId, ($note ?: 'Approved') . ' (On behalf of Admin & Ops)');
                    setSuccess('Admin & Operation stage approved by Management.');
                    header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
                    exit;
                } elseif (($req['purchase_head_status'] ?? '') === 'pending') {
                    $this->model->approveStep($id, 'purchase_head', 'approved', $userId, ($note ?: 'Approved') . ' (On behalf of Purchase Head)');
                    setSuccess('Purchase Head stage approved by Management.');
                    header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
                    exit;
                } elseif (($req['qc_audit_status'] ?? '') === 'pending') {
                    $this->model->approveStep($id, 'qc_audit', 'approved', $userId, ($note ?: 'Approved') . ' (On behalf of QC)');
                    setSuccess('QC & Audit stage approved by Management.');
                    header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
                    exit;
                } elseif ($opHeadEp !== 'approved') {
                    $this->db->query("UPDATE approval_requests SET op_head_status = 'approved', op_head_by = {$userId}, op_head_at = NOW(), op_head_note = '{$escSingleNote} (On behalf of Operation Head)', approve_role = 'accounts_head' WHERE source_table IN ('project_extra_assets', 'purchase_requests') AND source_id = {$id}");
                    setSuccess('Operation Head stage approved by Management. Forwarded to Accounts Head.');
                    header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
                    exit;
                } elseif ($ahEp !== 'approved') {
                    $this->db->query("UPDATE approval_requests SET accounts_head_status = 'approved', accounts_head_by = {$userId}, accounts_head_at = NOW(), accounts_head_note = '{$escSingleNote} (On behalf of Accounts Head)', approve_role = 'management' WHERE source_table IN ('project_extra_assets', 'purchase_requests') AND source_id = {$id}");
                    setSuccess('Accounts Head stage approved by Management. Ready for Final Management Approval.');
                    header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
                    exit;
                }
            }

            // When Management (DCEO / CEO / Director / Super Admin) approves at any stage, auto-fill all bypassed stages with Management's identity and perform final approval
            if ($isMgmtUser && $status === 'approved') {
                $step = 'mgmt';
                $escBypassNote = $this->db->real_escape_string(($note ?: 'Approved by Management') . ' (Management Override)');
                $this->db->query("
                    UPDATE project_extra_assets
                    SET dept_head_status = IF(dept_head_status = 'pending', 'approved', dept_head_status),
                        dept_head_by = IF(dept_head_status = 'pending' OR (dept_head_status = 'approved' AND dept_head_by IS NULL), {$userId}, dept_head_by),
                        dept_head_at = IF(dept_head_status = 'pending' OR (dept_head_status = 'approved' AND dept_head_at IS NULL), NOW(), dept_head_at),
                        dept_head_note = IF(dept_head_status = 'pending' OR (dept_head_status = 'approved' AND (dept_head_note IS NULL OR dept_head_note = '')), '{$escBypassNote}', dept_head_note),
                        it_head_status = IF(it_head_status = 'pending', 'approved', it_head_status),
                        it_head_by = IF(it_head_status = 'pending' OR (it_head_status = 'approved' AND it_head_by IS NULL), {$userId}, it_head_by),
                        it_head_at = IF(it_head_status = 'pending' OR (it_head_status = 'approved' AND it_head_at IS NULL), NOW(), it_head_at),
                        it_head_note = IF(it_head_status = 'pending' OR (it_head_status = 'approved' AND (it_head_note IS NULL OR it_head_note = '')), '{$escBypassNote}', it_head_note),
                        operations_status = 'approved',
                        operations_by = COALESCE(operations_by, {$userId}),
                        operations_at = COALESCE(operations_at, NOW()),
                        operations_note = COALESCE(NULLIF(operations_note, ''), '{$escBypassNote}'),
                        purchase_head_status = 'approved',
                        purchase_head_by = COALESCE(purchase_head_by, {$userId}),
                        purchase_head_at = COALESCE(purchase_head_at, NOW()),
                        purchase_head_note = COALESCE(NULLIF(purchase_head_note, ''), '{$escBypassNote}'),
                        qc_audit_status = 'approved',
                        qc_audit_by = COALESCE(qc_audit_by, {$userId}),
                        qc_audit_at = COALESCE(qc_audit_at, NOW()),
                        qc_audit_note = COALESCE(NULLIF(qc_audit_note, ''), '{$escBypassNote}')
                    WHERE id = {$id}
                ");
            }

            // Handle Post-QC flow for Operation Head (Role 20/32)
            if ($step === 'mgmt' && !$isMgmtUser && $isOpHeadUser) {
                if (!class_exists('ApprovalRequest')) {
                    require_once dirname(dirname(__DIR__)) . '/models/ApprovalRequest.php';
                }
                $approvalModel = new \ApprovalRequest($this->db);
                $approvalModel->syncMissingOfficeProcurements();

                $arCheck = $this->db->query("SELECT id, op_head_status, accounts_head_status FROM approval_requests WHERE source_table IN ('project_extra_assets', 'purchase_requests') AND source_id = {$id} ORDER BY id DESC LIMIT 1");
                $arRow = $arCheck ? $arCheck->fetch_assoc() : null;
                $arId = (int)($arRow['id'] ?? 0);
                $escNote = $this->db->real_escape_string($note);

                if ($status === 'approved') {
                    if ($arId > 0) {
                        $approvalModel->approveOperationHead($arId, $userId, $note);
                    } else {
                        $this->db->query("UPDATE approval_requests SET op_head_status = 'approved', op_head_by = {$userId}, op_head_at = NOW(), op_head_note = '{$escNote}', approve_role = 'accounts_head' WHERE source_table IN ('project_extra_assets', 'purchase_requests') AND source_id = {$id}");
                    }
                    // For <= 10,000, also mark mgmt_status = approved so once Accounts Head approves, payment unlocks
                    if (!$isOver1Lac) {
                        $this->model->approveStep($id, 'mgmt', 'approved', $userId, $note);
                    }
                    setSuccess("Request verified & approved by Admin & Operation Head (Role 20). Forwarded to Accounts Head (Role 30) for payment verification.");
                } else {
                    $this->db->query("UPDATE approval_requests SET op_head_status = 'rejected', op_head_by = {$userId}, op_head_at = NOW(), op_head_note = '{$escNote}', status = 'rejected', approve_role = 'rejected' WHERE source_table IN ('project_extra_assets', 'purchase_requests') AND source_id = {$id}");
                    $this->model->approveStep($id, 'mgmt', 'rejected', $userId, $note);
                    setSuccess("Request rejected by Admin & Operation Head.");
                }
                header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
                exit;
            }

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
                if ($status === 'approved') {
                    try {
                        $updatedRequest = $this->model->findExtraPurchase($id);
                        $nextStage = [
                            'dept_head'     => (($updatedRequest['it_head_status'] ?? '') === 'pending') ? [[242], 'IT Head', 'user'] : [[32], 'Admin & Operation'],
                            'it_head'       => [[32], 'Admin & Operation'],
                            'operations'    => [[17, 31], 'Purchase Head'],
                            'purchase_head' => [[24, 25], 'QC & Internal Audit'],
                            'qc_audit'      => [[1, 3, 33], 'Management'],
                            'mgmt'          => [[6, 30], 'Accounts / Accounts Head'],
                        ][$step] ?? null;

                        if ($updatedRequest && $nextStage) {
                            if (isset($nextStage[2]) && $nextStage[2] === 'user') {
                                $this->mailService->sendOfficeProcurementToUser(
                                    $updatedRequest,
                                    $nextStage[0][0],
                                    $nextStage[1]
                                );
                            } else {
                                $this->mailService->sendOfficeProcurementToApprovalRoles(
                                    $updatedRequest,
                                    $nextStage[0],
                                    $nextStage[1]
                                );
                            }
                        }
                    } catch (\Throwable $e) {
                        error_log('Office Procurement email (approval stage) failed: ' . $e->getMessage());
                    }
                }
                // ====== AUTO POSTING GENERAL JOURNAL (Batch 2/3) ======
                if ($status === 'approved' && $step === 'mgmt') {
                    try {
                        require_once dirname(dirname(__DIR__)) . '/services/AutoPostingService.php';
                        $autoPosting = new \AutoPostingService($this->db);
                        $vendorPayableAccId = $autoPosting->getAccountIdByCode('2110'); // Accounts Payable - Vendors
                        
                        $req = $this->model->findExtraPurchase($id);
                        if ($req && (float)$req['total_price'] > 0) {
                            $requestType = $req['request_type'] ?? 'Purchase';
                            if (strtolower($requestType) === 'purchase') {
                                // Capitalize to Fixed Asset (Property, Plant & Equipment)
                                $debitAccountId = !empty($req['account_id']) ? $req['account_id'] : $this->model->getFixedAssetAccountId($req['item_category_id'] ?? null, $req['item_name'] ?? '');
                                if (!$debitAccountId) {
                                    $debitAccountId = $autoPosting->getAccountIdByCode('100002'); // Office Equipment
                                }
                                $narrationDesc = "Office Fixed Asset Procurement (PR-{$id}): " . $req['item_name'] . " (Qty: {$req['quantity']})";
                            } else {
                                // Servicing / Maintenance is operating expense
                                $debitAccountId = !empty($req['account_id']) ? $req['account_id'] : $autoPosting->getAccountIdByCode('5220'); // Office & Asset Expense
                                $narrationDesc = "Office Asset Servicing (PR-{$id}): " . $req['item_name'];
                            }

                            $procurementOffice = !empty($req['asset_location']) ? $req['asset_location'] : null;
                            $autoPosting->postAccrualEntry(
                                'asset_procurement',
                                $id,
                                $debitAccountId,
                                $vendorPayableAccId,
                                $req['total_price'],
                                $narrationDesc,
                                $req['project_id'],
                                'employee',
                                $req['assigned_to'] ?? $req['requested_by'],
                                $userId,
                                $procurementOffice
                            );
                        }
                    } catch (\Exception $e) {
                        error_log("AutoPosting Exception (Procurement Approval): " . $e->getMessage());
                    }
                }
                // ======================================================

                // Sync with central approval_requests
                if ($status === 'rejected') {
                    $escNote = $this->db->real_escape_string($note);
                    $this->db->query("UPDATE approval_requests SET status = 'rejected', approve_role = 'rejected', approved_by = {$userId}, approved_at = NOW(), approval_note = '{$escNote}', op_head_status = IF('{$step}' IN ('operations', 'mgmt'), 'rejected', op_head_status), op_head_by = IF('{$step}' IN ('operations', 'mgmt'), {$userId}, op_head_by), op_head_at = IF('{$step}' IN ('operations', 'mgmt'), NOW(), op_head_at), op_head_note = IF('{$step}' IN ('operations', 'mgmt'), '{$escNote}', op_head_note) WHERE source_table IN ('project_extra_assets', 'purchase_requests') AND source_id = {$id}");

                    try {
                        $updatedRequest = $this->model->findExtraPurchase($id);
                        if ($updatedRequest && !empty($updatedRequest['requested_by'])) {
                            $this->mailService->sendOfficeProcurementToUser(
                                $updatedRequest,
                                (int)$updatedRequest['requested_by'],
                                'Rejected'
                            );
                        }
                    } catch (\Throwable $e) {
                        error_log('Procurement rejection email failed: ' . $e->getMessage());
                    }
                }

                try {
                    require_once dirname(dirname(__DIR__)) . '/models/ApprovalRequest.php';
                    $appModel = new \ApprovalRequest($this->db);
                    $appModel->syncMissingOfficeProcurements();
                    if ($step === 'mgmt' && $status === 'approved') {
                        $escMgtNote = $this->db->real_escape_string(($note ?: 'Approved by Management') . ' (Management Override)');
                        if ($isMgmtUser) {
                            $this->db->query("
                                UPDATE approval_requests
                                SET status = 'approved',
                                    approve_role = 'accounts',
                                    approved_by = {$userId},
                                    approved_at = NOW(),
                                    op_head_status = 'approved',
                                    op_head_by = COALESCE(op_head_by, {$userId}),
                                    op_head_at = COALESCE(op_head_at, NOW()),
                                    op_head_note = COALESCE(NULLIF(op_head_note, ''), '{$escMgtNote}'),
                                    accounts_head_status = 'approved',
                                    accounts_head_by = COALESCE(accounts_head_by, {$userId}),
                                    accounts_head_at = COALESCE(accounts_head_at, NOW()),
                                    accounts_head_note = COALESCE(NULLIF(accounts_head_note, ''), '{$escMgtNote}')
                                WHERE source_table IN ('project_extra_assets', 'purchase_requests') AND source_id = {$id}
                            ");
                        } else {
                            $this->db->query("UPDATE approval_requests SET op_head_status = 'approved', op_head_by = COALESCE(op_head_by, {$userId}), op_head_at = COALESCE(op_head_at, NOW()) WHERE source_table IN ('project_extra_assets', 'purchase_requests') AND source_id = {$id}");
                        }
                    }
                } catch (\Throwable $e) {
                    error_log("Failed syncing approval_requests on extra purchase approval: " . $e->getMessage());
                }

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

            $req = $this->model->findExtraPurchase($id);
            if (!$req) {
                setError("Procurement request not found.");
                header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
                exit;
            }

            // Mandatory Accounts Head Approval Guard
            if (!class_exists('ApprovalRequest')) {
                require_once dirname(dirname(__DIR__)) . '/models/ApprovalRequest.php';
            }
            $ahCheck = \ApprovalRequest::getAccountsHeadApprovalInfo($this->db, 'project_extra_assets', (int)$id);
            if ($ahCheck && !empty($ahCheck['has_request']) && empty($ahCheck['is_accounts_head_approved'])) {
                setError("Payment cannot be disbursed. Approval from Accounts Head (Md. Mamunoor Rashid [1309]) is pending.");
                header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
                exit;
            }

            $maxAllowed = (float)($req['total_price'] ?? 0);
            if ($paidAmount <= 0) {
                setError("Payment amount must be greater than 0.");
                header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
                exit;
            }

            if ($paidAmount > $maxAllowed + 0.01) {
                setError("Payment amount (৳" . number_format($paidAmount, 2) . ") cannot exceed the approved procurement price (৳" . number_format($maxAllowed, 2) . ").");
                header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
                exit;
            }

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
                    $procurementOffice = !empty($req['asset_location']) ? $req['asset_location'] : null;

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
                            $userId,
                            $procurementOffice
                        );
                    }
                } catch (\Exception $e) {
                    error_log("AutoPosting Exception (Procurement Payment): " . $e->getMessage());
                }
                // =====================================================

                // Sync with central approval_requests
                try {
                    require_once dirname(dirname(__DIR__)) . '/models/ApprovalRequest.php';
                    $appModel = new \ApprovalRequest($this->db);
                    $appModel->syncMissingOfficeProcurements();
                } catch (\Throwable $e) {
                    error_log("Failed syncing approval_requests on extra purchase payment: " . $e->getMessage());
                }

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
            $purchaseDate = !empty($_POST['purchase_date']) ? trim($_POST['purchase_date']) : null;
            $assignedTo = isset($_POST['assigned_to']) && $_POST['assigned_to'] !== '' ? (int)$_POST['assigned_to'] : null;
            $location = !empty($_POST['location']) ? trim($_POST['location']) : null;
            $returnAmount = (float)($_POST['return_amount'] ?? 0);
            $returnNote = $_POST['return_note'] ?? '';

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

            if ($this->model->confirmPurchased($id, $userId, $document, $note, $assignedTo, $location, $returnAmount, $returnNote, $purchaseDate)) {
                // Sync with central approval_requests
                try {
                    require_once dirname(dirname(__DIR__)) . '/models/ApprovalRequest.php';
                    $appModel = new \ApprovalRequest($this->db);
                    $appModel->syncMissingOfficeProcurements();
                } catch (\Throwable $e) {
                    error_log("Failed syncing approval_requests on extra purchase confirm: " . $e->getMessage());
                }

                setSuccess("Procurement confirmed & successfully registered in Asset Tracker with auto code and branch details!");
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

    public function deleteExtraPurchase($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = (int)($_SESSION['user']['id'] ?? 0);
            $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
            $isAdmin = in_array($roleId, [1, 2, 3, 33]);

            if ($this->model->deleteExtraPurchase($id, $userId, $isAdmin)) {
                setSuccess("Purchase request #{$id} deleted successfully.");
            } else {
                setError("Cannot delete purchase request #{$id}. It may have already been approved or you do not have permission.");
            }
        }
        header("Location: " . $GLOBALS['base_url'] . "/asset-extra-purchases");
        exit;
    }
}
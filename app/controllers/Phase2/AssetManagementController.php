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

        $data = [
            'page_title' => 'Office Asset Management',
            'assets' => $assets,
            'products' => $products,
            'categories' => $categories,
            'branches' => $branches,
            'projects' => $projects,
            'users' => $users,
            'stats' => $stats,
            'filters' => $filters,
            'canEdit' => $this->canUserEdit()
        ];

        $this->view('phase2/asset-management/assets', $data);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $data['created_by'] = $_SESSION['user']['id'] ?? 1;
            if ($assetId = $this->model->createAsset($data)) {
                // Auto-post to double-entry accounting ledger so purchase value reflects in Balance Sheet (Total Assets)
                if (!empty($data['purchase_price']) && (float)$data['purchase_price'] > 0) {
                    try {
                        require_once dirname(dirname(__DIR__)) . '/services/AutoPostingService.php';
                        $autoPosting = new \AutoPostingService($this->db);
                        $assetAccId = $autoPosting->getAccountIdByCode('1000') ?: $autoPosting->getAccountIdByCode('1610'); // Office Assets / Fixed Assets
                        $retainedAccId = $autoPosting->getAccountIdByCode('352000') ?: $autoPosting->getAccountIdByCode('29'); // Retained Earnings / Equity

                        $assetObj = $this->model->findAsset($assetId);
                        $assetCode = $assetObj['asset_code'] ?? ('AST-' . $assetId);
                        $itemName = $assetObj['item_name'] ?? 'Office Asset';
                        $branchName = $assetObj['branch_name'] ?? null;

                        $autoPosting->postAccrualEntry(
                            'office_assets',
                            $assetId,
                            $assetAccId,
                            $retainedAccId,
                            (float)$data['purchase_price'],
                            "Existing Company Asset Register (Opening Balance) ({$assetCode}): " . $itemName,
                            !empty($data['project_id']) ? (int)$data['project_id'] : null,
                            'employee',
                            !empty($data['assigned_to']) ? (int)$data['assigned_to'] : null,
                            $_SESSION['user']['id'] ?? 1,
                            $branchName
                        );
                    } catch (\Exception $e) {
                        error_log("AutoPosting Exception (Asset Direct Store): " . $e->getMessage());
                    }
                }
                setSuccess("Asset added successfully.");
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
                setSuccess("Asset updated successfully.");
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

        $isChiefOfficer = (in_array($roleId, [33, 34, 35], true) || in_array(strtolower(trim($_SESSION['user']['role_name'] ?? '')), ['chief officer', 'management viewer', 'seal ceo'], true));
        $isSuperAdmin   = in_array($roleId, [1, 3]) && !$isChiefOfficer; // Admin (1) and super Admin (3) ONLY. Role 2 is Employee!
        $isOperations   = ($isSuperAdmin || in_array($roleId, [20, 32])) && !$isChiefOfficer;
        $isPurchaseHead = ($isSuperAdmin || in_array($roleId, [17, 31])) && !$isChiefOfficer;
        $isQcAudit      = ($isSuperAdmin || in_array($roleId, [24, 25])) && !$isChiefOfficer;
        $isManagement   = $isSuperAdmin && !$isChiefOfficer;
        $isAccounts     = ($isSuperAdmin || in_array($roleId, [6, 30])) && !$isChiefOfficer;
        $isGlobalViewer = in_array($roleId, [1, 3, 33, 34, 35, 6, 17, 20, 24, 25, 30, 31, 32]);
        $isApprover     = $isGlobalViewer && !$isChiefOfficer;

        $activeTab  = $_GET['tab'] ?? ($isApprover ? 'all_requests' : 'my_requests');

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
            'is_purchase_head' => $isPurchaseHead,
            'is_qc_audit'      => $isQcAudit,
            'is_management'    => $isManagement,
            'is_accounts'      => $isAccounts,
        ];

        if ($activeTab === 'my_requests') {
            $filters['created_by'] = $userId;
        } elseif (!$isGlobalViewer) {
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

        $autoOpenRequest = null;
        $viewId = (int)($_GET['view'] ?? $_GET['id'] ?? 0);
        if ($viewId > 0) {
            $autoOpenRequest = $this->model->findExtraPurchase($viewId);
            if ($autoOpenRequest) {
                $reqTotal = (float)($autoOpenRequest['total_price'] ?? 0);
                $isRole20Eligible = (in_array($roleId, [20, 32]) && $reqTotal <= 100000);
                $isCreator = (int)$autoOpenRequest['requested_by'] === $userId;
                $isReporterBoss = !empty($autoOpenRequest['requester_reporting_boss_id']) && (int)$autoOpenRequest['requester_reporting_boss_id'] === $userId;

                $canApprove = false;
                $step = '';
                if (!$isCreator && !$isChiefOfficer) {
                    $dhStatus = $autoOpenRequest['dept_head_status'] ?? 'not_required';
                    if (($isReporterBoss || $isSuperAdmin) && $dhStatus === 'pending') {
                        $canApprove = true; $step = 'dept_head';
                    } elseif ($isOperations && in_array($dhStatus, ['approved', 'not_required']) && $autoOpenRequest['operations_status'] === 'pending') {
                        $canApprove = true; $step = 'operations';
                    } elseif ($isPurchaseHead && $autoOpenRequest['operations_status'] === 'approved' && $autoOpenRequest['purchase_head_status'] === 'pending') {
                        $canApprove = true; $step = 'purchase_head';
                    } elseif ($isQcAudit && $autoOpenRequest['purchase_head_status'] === 'approved' && $autoOpenRequest['qc_audit_status'] === 'pending') {
                        $canApprove = true; $step = 'qc_audit';
                    } elseif (($isManagement || $isRole20Eligible) && $autoOpenRequest['qc_audit_status'] === 'approved' && $autoOpenRequest['mgmt_status'] === 'pending') {
                        $canApprove = true; $step = 'mgmt';
                    }
                }
                $autoOpenRequest['can_approve'] = $canApprove;
                $autoOpenRequest['approval_step'] = $step;
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
            'autoOpenRequest'  => $autoOpenRequest
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
                        } else {
                            $this->mailService->sendOfficeProcurementToApprovalRoles(
                                $request,
                                [20, 32],
                                'Operations'
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

            $step = $_POST['step'] ?? 'qc_audit';
            if ($step === 'management') {
                $step = 'mgmt';
            }

            // Guard: If trying to approve while earlier steps are still pending
            $dhStatus = $req['dept_head_status'] ?? 'not_required';
            if ($step === 'operations' && $dhStatus === 'pending') {
                setError("This request is currently pending Department Head approval.");
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

            // Role permission validation for step approval
            $canApproveStep = false;
            $reqTotal = (float)($req['total_price'] ?? 0);
            $isRole20Eligible = (in_array($roleId, [20, 32]) && $reqTotal <= 100000);

            if (in_array($roleId, [1, 3])) {
                $canApproveStep = true;
            } else {
                if ($step === 'dept_head') {
                    $reportingBossId = (int)($req['reporting_user_id'] ?? $req['requester_reporting_boss_id'] ?? 0);
                    if ($reportingBossId === $userId) {
                        $canApproveStep = true;
                    }
                } elseif ($step === 'operations') {
                    if (in_array($roleId, [20, 32])) {
                        $canApproveStep = true;
                    }
                } elseif ($step === 'purchase_head') {
                    if (in_array($roleId, [17, 31])) {
                        $canApproveStep = true;
                    }
                } elseif ($step === 'qc_audit') {
                    if (in_array($roleId, [24, 25]) || $isRole20Eligible) {
                        $canApproveStep = true;
                    }
                } elseif ($step === 'mgmt') {
                    if (in_array($roleId, [1, 3]) || $isRole20Eligible) {
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
                            'dept_head'     => [[20, 32], 'Operations'],
                            'operations'    => [[17, 31], 'Purchase Head'],
                            'purchase_head' => [[24, 25], 'QC & Internal Audit'],
                            'qc_audit'      => [[1, 3, 33], 'Management'],
                            'mgmt'          => [[6, 30], 'Accounts / Accounts Head'],
                        ][$step] ?? null;

                        if ($updatedRequest && $nextStage) {
                            $this->mailService->sendOfficeProcurementToApprovalRoles(
                                $updatedRequest,
                                $nextStage[0],
                                $nextStage[1]
                            );
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
                        $assetExpenseAccId = $autoPosting->getAccountIdByCode('5220'); // Office & Asset Expense
                        $vendorPayableAccId = $autoPosting->getAccountIdByCode('2110'); // Accounts Payable - Vendors
                        
                        $req = $this->model->findExtraPurchase($id);
                        if ($req && (float)$req['total_price'] > 0) {
                            $procurementOffice = !empty($req['asset_location']) ? $req['asset_location'] : null;
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
                try {
                    require_once dirname(dirname(__DIR__)) . '/models/ApprovalRequest.php';
                    $appModel = new \ApprovalRequest($this->db);
                    $appModel->syncMissingOfficeProcurements();
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
                // Sync with central approval_requests
                try {
                    require_once dirname(dirname(__DIR__)) . '/models/ApprovalRequest.php';
                    $appModel = new \ApprovalRequest($this->db);
                    $appModel->syncMissingOfficeProcurements();
                } catch (\Throwable $e) {
                    error_log("Failed syncing approval_requests on extra purchase confirm: " . $e->getMessage());
                }

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
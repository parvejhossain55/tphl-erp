<?php
namespace Phase2;

use Controller;

class AssetManagementController extends Controller
{
    protected $db;
    protected $model;
    protected $userId;
    protected $roleId;
    protected $roleName;

    public function __construct($db)
    {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) session_start();
        checkLogin();

        $this->userId   = (int)($_SESSION['user']['id'] ?? 0);
        $this->roleId   = (int)($_SESSION['user']['role_id'] ?? 0);
        $this->roleName = strtolower($_SESSION['user']['role_name'] ?? '');

        // Permission check: Asset Management or QC & Internal Audit or Admin/Director
        if (!hasPermission('Asset Management') && !hasPermission('QC & Internal Audit') && !in_array($this->roleId, [1, 3, 20])) {
            setError("Unauthorized access.");
            header("Location: {$GLOBALS['base_url']}/home");
            exit;
        }

        require_once __DIR__ . '/../../models/AssetManagement.php';
        $this->model = new \AssetManagement($this->db);
    }

    /**
     * Helper: Check if current user can edit (Operations, Admin, Director)
     */
    private function canEdit()
    {
        return hasPermission('Asset Management') && in_array($this->roleId, [1, 3, 20]);
    }

    /**
     * Helper: Check if user is QC & Internal Audit (read-only oversight + approval)
     */
    private function isQcAudit()
    {
        return hasPermission('QC & Internal Audit');
    }

    /**
     * Helper: Check if user is Purchase Head
     */
    private function isPurchaseHead()
    {
        return $this->roleId == 17;
    }

    /**
     * Helper: Check if user is Management
     */
    private function isManagement()
    {
        return in_array($this->roleId, [1, 3]);
    }

    /**
     * Helper: Check if user is Accounts
     */
    private function isAccounts()
    {
        return $this->roleId == 6;
    }

    /**
     * Helper: Upload file
     */
    private function uploadFile($fileData, $uploadPath = 'uploads/assets/')
    {
        if (empty($fileData['name']) || $fileData['error'] !== 0) return null;

        $baseDir = __DIR__ . '/../../../../public/' . $uploadPath;
        if (!is_dir($baseDir)) mkdir($baseDir, 0777, true);

        $ext = pathinfo($fileData['name'], PATHINFO_EXTENSION);
        $newName = uniqid('asset_') . '_' . time() . '.' . $ext;

        if (move_uploaded_file($fileData['tmp_name'], $baseDir . $newName)) {
            return $uploadPath . $newName;
        }
        return null;
    }

    // =========================================================================
    // ASSET PRODUCTS — CRUD
    // =========================================================================

    /**
     * Product Master List page
     */
    public function products()
    {
        $filters = [
            'search'   => $_GET['search'] ?? '',
            'category' => $_GET['category'] ?? '',
            'status'   => $_GET['status'] ?? '',
        ];

        $products = $this->model->getAllProducts($filters);
        $stockCounts = $this->model->getProductStockCounts();

        // Index stock counts by product id for easy lookup
        $stockMap = [];
        foreach ($stockCounts as $sc) {
            $stockMap[$sc['id']] = $sc;
        }

        $this->view('phase2/asset-management/products', [
            'current_route' => 'asset-products',
            'page_title'    => 'Office Asset Products',
            'products'      => $products,
            'stockMap'      => $stockMap,
            'filters'       => $filters,
            'canEdit'       => $this->canEdit(),
            'isQcAudit'     => $this->isQcAudit(),
        ]);
    }

    /**
     * Store new product
     */
    public function storeProduct()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/asset-products");
            exit;
        }

        $productCode = trim($_POST['product_code'] ?? '');
        $name = trim($_POST['name'] ?? '');

        if (empty($productCode) || empty($name)) {
            setError('Product code and name are required.');
            header("Location: {$GLOBALS['base_url']}/asset-products");
            exit;
        }

        // Check duplicate code
        if ($this->model->productCodeExists($productCode)) {
            setError("Product code '{$productCode}' already exists.");
            header("Location: {$GLOBALS['base_url']}/asset-products");
            exit;
        }

        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $imagePath = $this->uploadFile($_FILES['image'], 'uploads/asset-products/');
        }

        $data = [
            'product_code' => $productCode,
            'name'         => $name,
            'category'     => $_POST['category'] ?? 'Other',
            'brand'        => $_POST['brand'] ?? null,
            'model'        => $_POST['model'] ?? null,
            'unit'         => $_POST['unit'] ?? 'Piece',
            'unit_price'   => (float)($_POST['unit_price'] ?? 0),
            'description'  => $_POST['description'] ?? null,
            'image_path'   => $imagePath,
            'status'       => $_POST['status'] ?? 'active',
            'created_by'   => $this->userId,
        ];

        if ($this->model->createProduct($data)) {
            setSuccess("Product '{$name}' created successfully.");
        } else {
            setError('Failed to create product.');
        }

        header("Location: {$GLOBALS['base_url']}/asset-products");
        exit;
    }

    /**
     * Update existing product
     */
    public function updateProduct()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/asset-products");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            setError('Invalid product ID.');
            header("Location: {$GLOBALS['base_url']}/asset-products");
            exit;
        }

        $productCode = trim($_POST['product_code'] ?? '');
        $name = trim($_POST['name'] ?? '');

        // Check duplicate code (exclude current)
        if ($this->model->productCodeExists($productCode, $id)) {
            setError("Product code '{$productCode}' already exists.");
            header("Location: {$GLOBALS['base_url']}/asset-products");
            exit;
        }

        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $imagePath = $this->uploadFile($_FILES['image'], 'uploads/asset-products/');
        }

        $data = [
            'product_code' => $productCode,
            'name'         => $name,
            'category'     => $_POST['category'] ?? 'Other',
            'brand'        => $_POST['brand'] ?? null,
            'model'        => $_POST['model'] ?? null,
            'unit'         => $_POST['unit'] ?? 'Piece',
            'unit_price'   => (float)($_POST['unit_price'] ?? 0),
            'description'  => $_POST['description'] ?? null,
            'image_path'   => $imagePath,
            'status'       => $_POST['status'] ?? 'active',
        ];

        if ($this->model->updateProduct($id, $data)) {
            setSuccess("Product updated successfully.");
        } else {
            setError('Failed to update product.');
        }

        header("Location: {$GLOBALS['base_url']}/asset-products");
        exit;
    }

    /**
     * Delete product
     */
    public function deleteProduct()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/asset-products");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $result = $this->model->deleteProduct($id);

        if ($result['success']) {
            setSuccess('Product deleted successfully.');
        } else {
            setError($result['message']);
        }

        header("Location: {$GLOBALS['base_url']}/asset-products");
        exit;
    }

    // =========================================================================
    // OFFICE ASSETS — CRUD
    // =========================================================================

    /**
     * All Assets list
     */
    public function index()
    {
        $filters = [
            'search'           => $_GET['search'] ?? '',
            'project_id'       => $_GET['project_id'] ?? '',
            'category'         => $_GET['category'] ?? '',
            'condition_status' => $_GET['condition_status'] ?? '',
            'asset_status'     => $_GET['asset_status'] ?? '',
        ];

        $assets = $this->model->getAllAssets($filters);
                $stats = $this->model->getDashboardStats();

        // Get projects list
        $projectsResult = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name");
        $projects = $projectsResult ? $projectsResult->fetch_all(MYSQLI_ASSOC) : [];

        // Get users list
        $usersResult = $this->db->query("SELECT id, full_name FROM users ORDER BY full_name");
        $users = $usersResult ? $usersResult->fetch_all(MYSQLI_ASSOC) : [];

        $products = $this->model->getAllProducts(['status' => 'active']);

        $this->view('phase2/asset-management/assets', [
            'current_route' => 'office-assets',
            'page_title'    => 'Asset Register',
            'assets'        => $assets,
            'projects'      => $projects,
            'users'         => $users,
            'products'      => $products,
            'stats'         => $stats,
            'filters'       => $filters,
            'canEdit'       => $this->canEdit(),
            'isQcAudit'     => $this->isQcAudit(),
        ]);
    }

    /**
     * Store new asset
     */
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/office-assets");
            exit;
        }

        $productId = (int)($_POST['product_id'] ?? 0);
        if (!$productId) {
            setError('Product is required.');
            header("Location: {$GLOBALS['base_url']}/office-assets");
            exit;
        }

        $assetCode = $this->model->generateAssetCode();

        $data = [
            'asset_code'       => $assetCode,
            'product_id'       => $productId,
            'item_name'        => trim($_POST['item_name'] ?? ''),
            'project_id'       => !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null,
            'department_id'    => !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null,
            'assigned_to'      => !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null,
            'location'         => $_POST['location'] ?? 'Head Office',
            'purchase_date'    => !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null,
            'purchase_price'   => (float)($_POST['purchase_price'] ?? 0),
            'warranty_expiry'  => !empty($_POST['warranty_expiry']) ? $_POST['warranty_expiry'] : null,
            'serial_no'        => $_POST['serial_no'] ?? null,
            'condition_status' => $_POST['condition_status'] ?? 'new',
            'asset_status'     => $_POST['asset_status'] ?? 'in_store',
            'remarks'          => $_POST['remarks'] ?? null,
            'created_by'       => $this->userId,
        ];

        $assetId = $this->model->createAsset($data);
        if ($assetId) {
            // Log the creation
            $this->model->logMovement([
                'asset_id'        => $assetId,
                'action'          => 'purchased',
                'from_location'   => null,
                'to_location'     => $data['location'],
                'from_user_id'    => null,
                'to_user_id'      => $data['assigned_to'],
                'from_project_id' => null,
                'to_project_id'   => $data['project_id'],
                'old_condition'   => null,
                'new_condition'   => $data['condition_status'],
                'notes'           => 'Asset registered: ' . $assetCode,
                'performed_by'    => $this->userId,
            ]);

            setSuccess("Asset '{$assetCode}' registered successfully.");
        } else {
            setError('Failed to register asset.');
        }

        header("Location: {$GLOBALS['base_url']}/office-assets");
        exit;
    }

    /**
     * Single asset detail
     */
    public function show($id)
    {
        $id = (int)$id;
        $asset = $this->model->findAsset($id);

        if (!$asset) {
            setError('Asset not found.');
            header("Location: {$GLOBALS['base_url']}/office-assets");
            exit;
        }

        $movements = $this->model->getMovementLog($id);

        // Get projects and users for transfer form
        $projectsResult = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name");
        $projects = $projectsResult ? $projectsResult->fetch_all(MYSQLI_ASSOC) : [];

        $usersResult = $this->db->query("SELECT id, full_name FROM users ORDER BY full_name");
        $users = $usersResult ? $usersResult->fetch_all(MYSQLI_ASSOC) : [];

        $this->view('phase2/asset-management/asset-detail', [
            'current_route' => 'office-assets',
            'page_title'    => 'Asset: ' . $asset['asset_code'],
            'asset'         => $asset,
            'movements'     => $movements,
            'projects'      => $projects,
            'users'         => $users,
            'canEdit'       => $this->canEdit(),
        ]);
    }

    // =========================================================================
    // ASSET TRANSFER + CONDITION
    // =========================================================================

    /**
     * Transfer asset to another project/user/location
     */
    public function transfer($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/office-assets/show/{$id}");
            exit;
        }

        $id = (int)$id;
        $asset = $this->model->findAsset($id);
        if (!$asset) {
            setError('Asset not found.');
            header("Location: {$GLOBALS['base_url']}/office-assets");
            exit;
        }

        $newProjectId = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
        $newAssignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
        $newLocation = $_POST['location'] ?? $asset['location'];
        $notes = $_POST['notes'] ?? '';

        // Update asset
        $this->db->query("UPDATE office_assets SET 
            project_id = " . ($newProjectId ? $newProjectId : 'NULL') . ",
            assigned_to = " . ($newAssignedTo ? $newAssignedTo : 'NULL') . ",
            location = '" . $this->db->real_escape_string($newLocation) . "',
            asset_status = 'transferred'
            WHERE id = {$id}");

        // Log movement
        $this->model->logMovement([
            'asset_id'        => $id,
            'action'          => 'transferred',
            'from_location'   => $asset['location'],
            'to_location'     => $newLocation,
            'from_user_id'    => $asset['assigned_to'],
            'to_user_id'      => $newAssignedTo,
            'from_project_id' => $asset['project_id'],
            'to_project_id'   => $newProjectId,
            'old_condition'   => $asset['condition_status'],
            'new_condition'   => $asset['condition_status'],
            'notes'           => $notes,
            'performed_by'    => $this->userId,
        ]);

        setSuccess('Asset transferred successfully.');
        header("Location: {$GLOBALS['base_url']}/office-assets/show/{$id}");
        exit;
    }

    /**
     * Update asset details (from edit modal) — Operations (role 20) only
     */
    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/office-assets");
            exit;
        }

        // Only Operations can edit
        if ($this->roleId !== 20) {
            setError('Only Operations can edit asset details.');
            header("Location: {$GLOBALS['base_url']}/office-assets");
            exit;
        }

        $id = (int)$id;
        $asset = $this->model->findAsset($id);
        if (!$asset) {
            setError('Asset not found.');
            header("Location: {$GLOBALS['base_url']}/office-assets");
            exit;
        }

        $itemName = trim($_POST['item_name'] ?? '');
        $projectId = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
        $location = trim($_POST['location'] ?? '');
        $serialNo = trim($_POST['serial_no'] ?? '');
        $assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
        $conditionStatus = $_POST['condition_status'] ?? $asset['condition_status'];
        $assetStatus = $_POST['asset_status'] ?? $asset['asset_status'];

        if (empty($itemName)) {
            setError('Item name is required.');
            header("Location: {$GLOBALS['base_url']}/office-assets");
            exit;
        }

        $sql = "UPDATE office_assets SET 
                    item_name = ?, project_id = ?, location = ?, serial_no = ?, 
                    assigned_to = ?, condition_status = ?, asset_status = ?
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sississi', 
            $itemName, $projectId, $location, $serialNo, 
            $assignedTo, $conditionStatus, $assetStatus, $id
        );

        if ($stmt->execute()) {
            setSuccess('Asset updated successfully.');
        } else {
            setError('Failed to update asset.');
        }

        header("Location: {$GLOBALS['base_url']}/office-assets");
        exit;
    }

    /**
     * Update asset condition (need_repair, repairing, repaired, ok, damaged, disposed)
     */
    public function updateCondition($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/office-assets/show/{$id}");
            exit;
        }

        $id = (int)$id;
        $asset = $this->model->findAsset($id);
        if (!$asset) {
            setError('Asset not found.');
            header("Location: {$GLOBALS['base_url']}/office-assets");
            exit;
        }

        $newCondition = $_POST['condition_status'] ?? $asset['condition_status'];
        $notes = $_POST['notes'] ?? '';

        // Determine asset_status based on condition
        $assetStatus = $asset['asset_status'];
        if ($newCondition === 'disposed') {
            $assetStatus = 'disposed';
        } elseif (in_array($newCondition, ['need_repair', 'repairing'])) {
            $assetStatus = 'maintenance';
        } elseif (in_array($newCondition, ['repaired', 'ok', 'new'])) {
            $assetStatus = $asset['assigned_to'] ? 'in_use' : 'in_store';
        }

        $this->db->query("UPDATE office_assets SET 
            condition_status = '" . $this->db->real_escape_string($newCondition) . "',
            asset_status = '" . $this->db->real_escape_string($assetStatus) . "'
            WHERE id = {$id}");

        // Determine action type for log
        $action = 'status_change';
        if ($newCondition === 'need_repair') $action = 'repair_requested';
        if ($newCondition === 'repaired') $action = 'repaired';
        if ($newCondition === 'disposed') $action = 'disposed';
        if (in_array($newCondition, ['repairing', 'need_repair'])) $action = 'maintenance';

        $this->model->logMovement([
            'asset_id'        => $id,
            'action'          => $action,
            'from_location'   => $asset['location'],
            'to_location'     => $asset['location'],
            'from_user_id'    => null,
            'to_user_id'      => null,
            'from_project_id' => null,
            'to_project_id'   => null,
            'old_condition'   => $asset['condition_status'],
            'new_condition'   => $newCondition,
            'notes'           => $notes,
            'performed_by'    => $this->userId,
        ]);

        setSuccess("Asset condition updated to '{$newCondition}'.");
        header("Location: {$GLOBALS['base_url']}/office-assets/show/{$id}");
        exit;
    }

    /**
     * Dispose asset
     */
    public function dispose($id)
    {
        $_POST['condition_status'] = 'disposed';
        $_POST['notes'] = $_POST['notes'] ?? 'Asset disposed';
        $this->updateCondition($id);
    }

    // =========================================================================
    // EXTRA PURCHASE REQUESTS (5-Step Approval)
    // =========================================================================

    /**
     * Extra Purchases list
     */
    public function extraPurchases()
    {
        $filters = [
            'project_id'     => $_GET['project_id'] ?? '',
            'overall_status' => $_GET['status'] ?? '',
        ];

        $requests = $this->model->getExtraPurchases($filters);
        
        // Get projects
        $projectsResult = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name");
        $projects = $projectsResult ? $projectsResult->fetch_all(MYSQLI_ASSOC) : [];

        $this->view('phase2/asset-management/extra-purchases', [
            'current_route'   => 'asset-extra-purchases',
            'page_title'      => 'Extra Asset Purchase Requests',
            'requests'        => $requests,
                        'projects'        => $projects,
            'canCreate'       => $this->roleId == 20 || $this->isManagement(),
            'isPurchaseHead'  => $this->isPurchaseHead(),
            'isQcAudit'       => $this->isQcAudit(),
            'isManagement'    => $this->isManagement(),
            'isAccounts'      => $this->isAccounts(),
            'currentUserId'   => $this->userId,
            'currentRoleId'   => $this->roleId,
        ]);
    }

    /**
     * Store new extra purchase request
     */
    public function storeExtraPurchase()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/asset-extra-purchases");
            exit;
        }

        $projectId = (int)($_POST['project_id'] ?? 0);
        $assetLocation = trim($_POST['asset_location'] ?? '');
        $itemName = trim($_POST['item_name'] ?? '');
        $justification = trim($_POST['justification'] ?? '');

        if (!$projectId || empty($itemName) || empty($justification)) {
            setError('Project, item name, and justification are required.');
            header("Location: {$GLOBALS['base_url']}/asset-extra-purchases");
            exit;
        }

        $attachment = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === 0) {
            $attachment = $this->uploadFile($_FILES['attachment'], 'uploads/asset-purchases/');
        }

        $data = [
            'project_id'    => $projectId,
            'asset_location' => $assetLocation,
            'item_name'     => $itemName,
            'quantity'      => (int)($_POST['quantity'] ?? 1),
            'unit_price'    => (float)($_POST['unit_price'] ?? 0),
            'justification' => $justification,
            'attachment'    => $attachment,
            'requested_by'  => $this->userId,
        ];

        if ($this->model->createExtraPurchase($data)) {
            setSuccess('Purchase request submitted successfully.');
        } else {
            setError('Failed to submit request.');
        }

        header("Location: {$GLOBALS['base_url']}/asset-extra-purchases");
        exit;
    }

    /**
     * Approve/reject at specific step
     */
    public function approveExtraPurchase($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/asset-extra-purchases");
            exit;
        }

        $id = (int)$id;
        $request = $this->model->findExtraPurchase($id);
        if (!$request) {
            setError('Request not found.');
            header("Location: {$GLOBALS['base_url']}/asset-extra-purchases");
            exit;
        }

        $status = $_POST['approval_status'] ?? 'approved'; // 'approved' or 'rejected'
        $note = $_POST['approval_note'] ?? '';

        // Determine which step based on current user role
        $step = null;
        if ($this->isPurchaseHead() && $request['purchase_head_status'] === 'pending') {
            $step = 'purchase_head';
        } elseif ($this->isQcAudit() && $request['purchase_head_status'] === 'approved' && $request['qc_audit_status'] === 'pending') {
            $step = 'qc_audit';
        } elseif ($this->isManagement() && $request['qc_audit_status'] === 'approved' && $request['mgmt_status'] === 'pending') {
            $step = 'mgmt';
        }

        if (!$step) {
            setError('You cannot approve this request at this stage.');
            header("Location: {$GLOBALS['base_url']}/asset-extra-purchases");
            exit;
        }

        if ($this->model->approveStep($id, $step, $status, $this->userId, $note)) {
            $actionText = $status === 'approved' ? 'approved' : 'rejected';
            setSuccess("Request #{$id} {$actionText} successfully.");
        } else {
            setError('Failed to process approval.');
        }

        header("Location: {$GLOBALS['base_url']}/asset-extra-purchases");
        exit;
    }

    /**
     * Process payment (Accounts)
     */
    public function markPurchased($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/asset-extra-purchases");
            exit;
        }

        $id = (int)$id;
        $request = $this->model->findExtraPurchase($id);
        if (!$request || $request['mgmt_status'] !== 'approved') {
            setError('Request not approved by management yet.');
            header("Location: {$GLOBALS['base_url']}/asset-extra-purchases");
            exit;
        }

        $paymentMethod = $_POST['payment_method'] ?? 'bank';
        $attachment = null;
        if (isset($_FILES['payment_attachment']) && $_FILES['payment_attachment']['error'] === 0) {
            $attachment = $this->uploadFile($_FILES['payment_attachment'], 'uploads/asset-payments/');
        }

        if ($this->model->processPayment($id, $this->userId, $paymentMethod, $attachment)) {
            setSuccess("Payment processed successfully. Waiting for Operations to confirm purchase.");
        } else {
            setError('Failed to process payment.');
        }

        header("Location: {$GLOBALS['base_url']}/asset-extra-purchases");
        exit;
    }

    /**
     * Operations confirms purchase received → assets registered
     */
    public function confirmPurchased($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/asset-extra-purchases");
            exit;
        }

        $id = (int)$id;
        $request = $this->model->findExtraPurchase($id);
        if (!$request || $request['payment_status'] !== 'paid' || $request['overall_status'] === 'purchased') {
            setError('This request cannot be marked as purchased.');
            header("Location: {$GLOBALS['base_url']}/asset-extra-purchases");
            exit;
        }

        // Handle document upload
        $document = null;
        if (isset($_FILES['purchase_document']) && $_FILES['purchase_document']['error'] === 0) {
            $document = $this->uploadFile($_FILES['purchase_document'], 'uploads/asset-purchases/');
        }

        $note = trim($_POST['purchase_note'] ?? '');

        if ($this->model->confirmPurchased($id, $this->userId, $document, $note)) {
            setSuccess("Purchase confirmed! Asset(s) have been registered in All Assets.");
        } else {
            setError('Failed to confirm purchase.');
        }

        header("Location: {$GLOBALS['base_url']}/asset-extra-purchases");
        exit;
    }
}

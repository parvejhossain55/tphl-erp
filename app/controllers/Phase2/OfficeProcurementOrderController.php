<?php
namespace Phase2;

use Controller;

require_once __DIR__ . '/../../models/AssetManagement.php';
require_once __DIR__ . '/../../models/OfficeProcurementOrder.php';

class OfficeProcurementOrderController extends Controller
{
    protected $db;
    protected $assetModel;
    protected $opoModel;

    public function __construct($db)
    {
        $this->db         = $db;
        $this->assetModel = new \AssetManagement($db);
        $this->opoModel   = new \OfficeProcurementOrder($db);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $baseUrl = $GLOBALS['base_url'] ?? '/TPHL-ERP';
        if (!isset($_SESSION['user'])) {
            header("Location: {$baseUrl}/login");
            exit;
        }
    }

    /**
     * OPO List Page: /office-procurement/orders
     */
    public function index()
    {
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        $activeTab = $_GET['tab'] ?? 'pending_my_action';
        $filters = [
            'tab'       => $activeTab,
            'status'    => $_GET['status'] ?? '',
            'vendor_id' => $_GET['vendor_id'] ?? '',
            'search'    => $_GET['search'] ?? '',
            'user_id'   => $userId,
            'role_id'   => $roleId,
        ];

        $orders  = $this->opoModel->getAllOrders($filters);
        $vendors = $this->opoModel->getActiveVendors();

        // Tab badge counts
        $pendingCount = $this->opoModel->getPendingOpoCountForUser($userId, $roleId);
        $revOrders    = $this->opoModel->getAllOrders(['tab' => 'revision_requested', 'user_id' => $userId, 'role_id' => $roleId]);
        $revCount     = count($revOrders);

        // Summary KPIs across all visible orders
        $allOrders = $this->opoModel->getAllOrders(['tab' => 'all', 'user_id' => $userId, 'role_id' => $roleId]);
        $kpis = [
            'total_opos'     => count($allOrders),
            'pending_review' => 0,
            'approved_paid'  => 0,
            'received_done'  => 0,
            'total_value'    => 0.0,
        ];
        foreach ($allOrders as $o) {
            $kpis['total_value'] += (float)($o['total_amount'] ?? 0);
            if (in_array($o['status'], ['pending_purchase_head', 'pending_qc', 'pending_op_head', 'revision_requested'], true)) {
                $kpis['pending_review']++;
            } elseif (in_array($o['status'], ['approved', 'paid', 'process_on_purchase', 'purchased'], true)) {
                $kpis['approved_paid']++;
            } elseif ($o['status'] === 'received') {
                $kpis['received_done']++;
            }
        }

        $this->view('phase2/asset-management/office-orders', [
            'current_route' => 'office-procurement-orders',
            'page_title'    => 'Office Procurement Orders (OPO)',
            'orders'        => $orders,
            'vendors'       => $vendors,
            'activeTab'     => $activeTab,
            'filters'       => $filters,
            'pendingCount'  => $pendingCount,
            'revCount'      => $revCount,
            'kpis'          => $kpis,
        ]);
    }

    /**
     * Multi-Vendor CST Creation / Revision Edit Page: /office-procurement/cst/{prId}
     */
    public function cstPage($prId)
    {
        $prId   = (int)$prId;
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        $req = $this->assetModel->findExtraPurchase($prId);
        if (!$req) {
            setError("Office Procurement Request #{$prId} not found.");
            header("Location: {$GLOBALS['base_url']}/asset-extra-purchases");
            exit;
        }

        $isAdmin = in_array($roleId, [1, 3, 38], true);
        $isAssignee = ((int)($req['assigned_cst_user_id'] ?? 0) === $userId);
        $isOpsOrPurchase = in_array($roleId, [17, 20, 31, 32], true);

        if (!$isAssignee && !$isAdmin && !$isOpsOrPurchase) {
            setError("Only the assigned employee can prepare or edit the CST for Request #{$prId}.");
            header("Location: {$GLOBALS['base_url']}/asset-extra-purchase/{$prId}");
            exit;
        }

        $existingOpo = $this->opoModel->findOrderByPr($prId);
        $canEditCst  = (!$existingOpo || $existingOpo['status'] === 'revision_requested' || $existingOpo['status'] === 'pending_purchase_head');
        if ($existingOpo && !$canEditCst && !$isAdmin) {
            setError("OPO {$existingOpo['opo_number']} is already in progress ({$existingOpo['status']}). CST can only be edited if sent back for review.");
            header("Location: {$GLOBALS['base_url']}/office-procurement/order/{$existingOpo['id']}");
            exit;
        }

        $quotations = $this->opoModel->getQuotationsByPr($prId);
        $vendors    = $this->opoModel->getActiveVendors();
        $logs       = $this->opoModel->getActivityLogs($prId);

        // Extract latest revision request note & suggested rate if any
        $latestRevision = null;
        foreach ($logs as $lg) {
            if ($lg['action_type'] === 'review_requested') {
                $latestRevision = $lg;
                break;
            }
        }

        $this->view('phase2/asset-management/office-cst', [
            'current_route'  => 'asset-extra-purchases',
            'page_title'     => 'Multi-Vendor CST — PR #' . $prId,
            'req'            => $req,
            'existingOpo'    => $existingOpo,
            'quotations'     => $quotations,
            'vendors'        => $vendors,
            'logs'           => $logs,
            'latestRevision' => $latestRevision,
            'canEditCst'     => $canEditCst || $isAdmin,
        ]);
    }

    /**
     * Save Multi-Vendor CST & Generate / Revise OPO: POST /office-procurement/cst/save/{prId}
     */
    public function saveCstAndGenerateOpo($prId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/office-procurement/cst/{$prId}");
            exit;
        }

        $prId   = (int)$prId;
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        $req = $this->assetModel->findExtraPurchase($prId);
        if (!$req) {
            setError("Office Procurement Request #{$prId} not found.");
            header("Location: {$GLOBALS['base_url']}/asset-extra-purchases");
            exit;
        }

        $isAdmin = in_array($roleId, [1, 3, 38], true);
        $isAssignee = ((int)($req['assigned_cst_user_id'] ?? 0) === $userId);
        $isOpsOrPurchase = in_array($roleId, [17, 20, 31, 32], true);

        if (!$isAssignee && !$isAdmin && !$isOpsOrPurchase) {
            setError("You do not have permission to submit CST for Request #{$prId}.");
            header("Location: {$GLOBALS['base_url']}/asset-extra-purchase/{$prId}");
            exit;
        }

        $vendorIds     = $_POST['vendor_id'] ?? [];
        $quantities    = $_POST['quantity'] ?? [];
        $unitPrices    = $_POST['unit_price'] ?? [];
        $validityDates = $_POST['validity_date'] ?? [];
        $driveLinks    = $_POST['drive_link'] ?? [];
        $notesArr      = $_POST['notes'] ?? [];
        $selectedRaw   = (int)($_POST['selected_index'] ?? -1);

        $uploadDir = dirname(dirname(dirname(__DIR__))) . '/public/uploads/office_cst/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        $quotations = [];
        $mappedSelectedIndex = -1;
        $seenVendors = [];

        foreach ($vendorIds as $i => $vIdRaw) {
            $vId = (int)$vIdRaw;
            $uPrice = (float)($unitPrices[$i] ?? 0);
            $qty = (float)($quantities[$i] ?? ($req['quantity'] ?? 1));
            if ($vId <= 0 || $uPrice <= 0) {
                continue;
            }
            if (in_array($vId, $seenVendors, true)) {
                setError("Duplicate vendor selected in CST. Each vendor can only be added once.");
                header("Location: {$GLOBALS['base_url']}/office-procurement/cst/{$prId}");
                exit;
            }
            $seenVendors[] = $vId;

            $attachPath = null;
            if (isset($_FILES['attachment']['name'][$i]) && $_FILES['attachment']['error'][$i] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['attachment']['name'][$i], PATHINFO_EXTENSION));
                $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'xls', 'xlsx'];
                if (in_array($ext, $allowed, true)) {
                    $fname = 'ocst_' . $prId . '_' . $vId . '_' . time() . '_' . rand(100, 999) . '.' . $ext;
                    if (move_uploaded_file($_FILES['attachment']['tmp_name'][$i], $uploadDir . $fname)) {
                        $attachPath = 'uploads/office_cst/' . $fname;
                    }
                }
            }

            $newIdx = count($quotations);
            if ($selectedRaw === (int)$i) {
                $mappedSelectedIndex = $newIdx;
            }

            $quotations[] = [
                'vendor_id'     => $vId,
                'quantity'      => $qty > 0 ? $qty : 1,
                'unit_price'    => $uPrice,
                'validity_date' => $validityDates[$i] ?? null,
                'attachment'    => $attachPath,
                'drive_link'    => $driveLinks[$i] ?? null,
                'notes'         => $notesArr[$i] ?? '',
            ];
        }

        if (empty($quotations)) {
            setError("Please enter at least one vendor with a valid unit price greater than 0.");
            header("Location: {$GLOBALS['base_url']}/office-procurement/cst/{$prId}");
            exit;
        }

        if ($mappedSelectedIndex < 0) {
            setError("Please explicitly select a winning vendor (Winner radio button) before generating the OPO.");
            header("Location: {$GLOBALS['base_url']}/office-procurement/cst/{$prId}");
            exit;
        }

        $res = $this->opoModel->saveCstAndGenerateOpo($prId, $req, $quotations, $mappedSelectedIndex, $userId);
        if ($res['success']) {
            setSuccess("CST saved & Office Procurement Order ({$res['opo_number']}) submitted for Purchase Department Head approval!");
            header("Location: {$GLOBALS['base_url']}/office-procurement/order/{$res['opo_id']}");
        } else {
            setError("Failed to generate OPO: " . ($res['error'] ?? 'Unknown error'));
            header("Location: {$GLOBALS['base_url']}/office-procurement/cst/{$prId}");
        }
        exit;
    }

    /**
     * View Single OPO Detail Page: /office-procurement/order/{opoId}
     */
    public function show($opoId)
    {
        $opoId  = (int)$opoId;
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $roleNameLower = strtolower(trim($_SESSION['user']['role_name'] ?? ''));

        $opo = $this->opoModel->findOrder($opoId);
        if (!$opo) {
            setError("Office Procurement Order #{$opoId} not found.");
            header("Location: {$GLOBALS['base_url']}/office-procurement/orders");
            exit;
        }

        $prId       = (int)$opo['extra_purchase_id'];
        $quotations = $this->opoModel->getQuotationsByPr($prId);
        $logs       = $this->opoModel->getActivityLogs($prId);

        // Role & action flags
        $isChiefOfficer = in_array($roleId, [33, 34, 35], true);
        $isAdmin        = in_array($roleId, [1, 3, 38], true) && !$isChiefOfficer;
        $isPurchaseHead = (in_array($roleId, [17, 31], true) || $isAdmin) && !$isChiefOfficer;
        $isQcHead       = ($roleId === 24 || $isAdmin) && !$isChiefOfficer;
        $isOpHead       = ($roleId === 20 || $isAdmin) && !$isChiefOfficer;
        $isAccountsHead = ($roleId === 30 || strpos($roleNameLower, 'accounts head') !== false || $isAdmin) && !$isChiefOfficer;
        $isAccounts     = (in_array($roleId, [6, 30], true) || strpos($roleNameLower, 'account') !== false || $isAdmin) && !$isChiefOfficer;

        $isAssignee  = ((int)($opo['assigned_cst_user_id'] ?? 0) === $userId);
        $isRequester = ((int)($opo['requested_by'] ?? 0) === $userId);
        $isOver10K   = ((float)$opo['total_amount'] > 10000.01);

        // Determine current actionable OPO approval stage
        $canActOpoStage = '';
        if ($opo['status'] === 'pending_purchase_head' && $isPurchaseHead) {
            $canActOpoStage = 'purchase_head';
        } elseif ($opo['status'] === 'pending_qc' && $isQcHead) {
            $canActOpoStage = 'qc';
        } elseif ($opo['status'] === 'pending_op_head' && $isOpHead) {
            $canActOpoStage = 'op_head';
        }

        $canReviseCst = ($opo['status'] === 'revision_requested' && ($isAssignee || $isAdmin));

        $isAhApproved = (strtolower($opo['accounts_head_status'] ?? '') === 'approved');
        $isMgmtApproved = (($opo['mgmt_status'] ?? '') === 'approved');
        $isPaid = (($opo['payment_status'] ?? '') === 'paid');

        $canApproveAh   = ($opo['status'] === 'approved' && !$isPaid && !$isAhApproved && !empty($opo['approval_request_id']) && $isAccountsHead);
        $canApproveMgmt = ($opo['status'] === 'approved' && $isOver10K && $isAhApproved && !$isMgmtApproved && !$isPaid && $isAdmin);
        $canDisburse    = ($opo['status'] === 'approved' && !$isPaid && $isAhApproved && (!$isOver10K || $isMgmtApproved) && $isAccounts);

        $canReceivePayment = ($isPaid && empty($opo['assignee_payment_received_at']) && ($isAssignee || $isAdmin) && !in_array($opo['status'], ['process_on_purchase', 'purchased', 'received'], true));
        $canMarkPurchased  = (($opo['status'] === 'process_on_purchase' || !empty($opo['assignee_payment_received_at'])) && empty($opo['purchase_confirmed_at']) && ($isAssignee || $isAdmin) && !in_array($opo['status'], ['purchased', 'received'], true));
        $canReceiveAsset   = ($opo['status'] === 'purchased' && empty($opo['asset_received_at']) && ($isRequester || $isAdmin));

        // Bank/Cash COA accounts for disbursement modal
        $bankAccounts = [];
        $bRes = $this->db->query("SELECT id, account_code, account_name FROM chart_of_accounts WHERE status = 'active' AND (account_code LIKE '1141%' OR account_code LIKE '1144%' OR (account_type = 'Asset' AND (account_name LIKE '%Cash%' OR account_name LIKE '%Bank%') AND account_code != '114')) ORDER BY account_code ASC");
        if ($bRes) {
            $bankAccounts = $bRes->fetch_all(MYSQLI_ASSOC);
        }

        // Registered assets if received
        $registeredAssets = [];
        $prRemark = "%PR-" . $prId . "%";
        $aStmt = $this->db->prepare("SELECT id, asset_code, item_name, purchase_price, asset_status, created_at FROM office_assets WHERE remarks LIKE ? ORDER BY id ASC");
        if ($aStmt) {
            $aStmt->bind_param('s', $prRemark);
            $aStmt->execute();
            $registeredAssets = $aStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $aStmt->close();
        }

        $this->view('phase2/asset-management/view-office-order', [
            'current_route'     => 'office-procurement-orders',
            'page_title'        => 'Office Procurement Order — ' . $opo['opo_number'],
            'opo'               => $opo,
            'quotations'        => $quotations,
            'logs'              => $logs,
            'isOver10K'         => $isOver10K,
            'canActOpoStage'    => $canActOpoStage,
            'canReviseCst'      => $canReviseCst,
            'canApproveAh'      => $canApproveAh,
            'canApproveMgmt'    => $canApproveMgmt,
            'canDisburse'       => $canDisburse,
            'canReceivePayment' => $canReceivePayment,
            'canMarkPurchased'  => $canMarkPurchased,
            'canReceiveAsset'   => $canReceiveAsset,
            'bankAccounts'      => $bankAccounts,
            'registeredAssets'  => $registeredAssets,
        ]);
    }

    /**
     * Handle OPO Approval / Send for Rate Review / Reject
     * POST /office-procurement/order/action/{opoId}
     */
    public function handleOpoAction($opoId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/office-procurement/order/{$opoId}");
            exit;
        }

        $opoId  = (int)$opoId;
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        $opo = $this->opoModel->findOrder($opoId);
        if (!$opo) {
            setError("OPO #{$opoId} not found.");
            header("Location: {$GLOBALS['base_url']}/office-procurement/orders");
            exit;
        }

        $stage  = $_POST['stage'] ?? '';
        $action = $_POST['action'] ?? ''; // 'approved' | 'revision_requested' | 'rejected'
        $note   = trim($_POST['note'] ?? '');
        $suggestedRate = isset($_POST['suggested_unit_price']) && $_POST['suggested_unit_price'] !== ''
            ? (float)$_POST['suggested_unit_price']
            : null;

        $isAdmin = in_array($roleId, [1, 3, 38], true);
        $authorized = false;
        if ($stage === 'purchase_head' && $opo['status'] === 'pending_purchase_head' && (in_array($roleId, [17, 31], true) || $isAdmin)) {
            $authorized = true;
        } elseif ($stage === 'qc' && $opo['status'] === 'pending_qc' && ($roleId === 24 || $isAdmin)) {
            $authorized = true;
        } elseif ($stage === 'op_head' && $opo['status'] === 'pending_op_head' && ($roleId === 20 || $isAdmin)) {
            $authorized = true;
        }

        if (!$authorized) {
            setError("You are not authorized to act on this OPO at the current stage.");
            header("Location: {$GLOBALS['base_url']}/office-procurement/order/{$opoId}");
            exit;
        }

        if (!in_array($action, ['approved', 'revision_requested', 'rejected'], true)) {
            setError("Invalid OPO action.");
            header("Location: {$GLOBALS['base_url']}/office-procurement/order/{$opoId}");
            exit;
        }

        if ($action === 'revision_requested' && $note === '' && ($suggestedRate === null || $suggestedRate <= 0)) {
            setError("Please enter review remarks or a suggested unit rate when sending back for revision.");
            header("Location: {$GLOBALS['base_url']}/office-procurement/order/{$opoId}");
            exit;
        }

        $res = $this->opoModel->handleOpoAction($opoId, $stage, $action, $userId, $note, $suggestedRate);
        if ($res['success']) {
            setSuccess($res['message']);
        } else {
            setError($res['error'] ?? 'Failed to process OPO action.');
        }

        header("Location: {$GLOBALS['base_url']}/office-procurement/order/{$opoId}");
        exit;
    }

    /**
     * CST Assignee clicks "Received Payment" -> status becomes Process on Purchase
     * POST /office-procurement/order/receive-payment/{prId}
     */
    public function markPaymentReceived($prId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/asset-extra-purchase/{$prId}");
            exit;
        }

        $prId   = (int)$prId;
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $note   = trim($_POST['note'] ?? '');

        $req = $this->assetModel->findExtraPurchase($prId);
        $opo = $this->opoModel->findOrderByPr($prId);
        $redirect = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : ($opo ? "{$GLOBALS['base_url']}/office-procurement/order/{$opo['id']}" : "{$GLOBALS['base_url']}/asset-extra-purchase/{$prId}");

        if (!$req || ($req['payment_status'] ?? '') !== 'paid') {
            setError("Payment has not been disbursed by Accounts yet.");
            header("Location: {$redirect}");
            exit;
        }

        $isAdmin = in_array($roleId, [1, 3, 38], true);
        if ((int)($req['assigned_cst_user_id'] ?? 0) !== $userId && !$isAdmin) {
            setError("Only the assigned CST employee can confirm payment receipt.");
            header("Location: {$redirect}");
            exit;
        }

        if ($this->opoModel->markPaymentReceived($prId, $userId, $note)) {
            setSuccess("Payment receipt confirmed! Status is now: Process on Purchase.");
        } else {
            setError("Failed to confirm payment receipt.");
        }

        header("Location: {$redirect}");
        exit;
    }

    /**
     * CST Assignee clicks "Purchased" (with invoice upload)
     * POST /office-procurement/order/mark-purchased/{prId}
     */
    public function markPurchasedByAssignee($prId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/asset-extra-purchase/{$prId}");
            exit;
        }

        $prId   = (int)$prId;
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        $req = $this->assetModel->findExtraPurchase($prId);
        $opo = $this->opoModel->findOrderByPr($prId);
        $redirect = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : ($opo ? "{$GLOBALS['base_url']}/office-procurement/order/{$opo['id']}" : "{$GLOBALS['base_url']}/asset-extra-purchase/{$prId}");

        $isAdmin = in_array($roleId, [1, 3, 38], true);
        if (!$req || ((int)($req['assigned_cst_user_id'] ?? 0) !== $userId && !$isAdmin)) {
            setError("Only the assigned CST employee can mark this procurement as Purchased.");
            header("Location: {$redirect}");
            exit;
        }

        $note         = trim($_POST['purchase_note'] ?? '');
        $purchaseDate = !empty($_POST['purchase_date']) ? trim($_POST['purchase_date']) : date('Y-m-d');
        $returnAmount = (float)($_POST['return_amount'] ?? 0);
        $returnNote   = trim($_POST['return_note'] ?? '');

        $document = null;
        if (isset($_FILES['purchase_document']) && $_FILES['purchase_document']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = dirname(dirname(dirname(__DIR__))) . '/public/uploads/procurement/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            $fname = 'purch_' . time() . '_' . rand(1000, 9999) . '.' . pathinfo($_FILES['purchase_document']['name'], PATHINFO_EXTENSION);
            if (move_uploaded_file($_FILES['purchase_document']['tmp_name'], $uploadDir . $fname)) {
                $document = 'uploads/procurement/' . $fname;
            }
        }

        if ($this->opoModel->markPurchasedByAssignee($prId, $userId, $document, $note, $purchaseDate, $returnAmount, $returnNote)) {
            setSuccess("Marked as Purchased! Requester can now confirm 'Received Asset' to register it in the Asset Tracker.");
        } else {
            setError("Failed to mark as Purchased.");
        }

        header("Location: {$redirect}");
        exit;
    }

    /**
     * Original Requester clicks "Received Asset"
     * -> Status = 'received'
     * -> Registers Asset Code in Asset Tracker + Syncs Financial Statements
     * POST /office-procurement/order/receive-asset/{prId}
     */
    public function markAssetReceived($prId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/asset-extra-purchase/{$prId}");
            exit;
        }

        $prId   = (int)$prId;
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $note   = trim($_POST['asset_received_note'] ?? '');

        $req = $this->assetModel->findExtraPurchase($prId);
        $opo = $this->opoModel->findOrderByPr($prId);
        $redirect = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : ($opo ? "{$GLOBALS['base_url']}/office-procurement/order/{$opo['id']}" : "{$GLOBALS['base_url']}/asset-extra-purchase/{$prId}");

        $isAdmin = in_array($roleId, [1, 3, 38], true);
        if (!$req || ((int)($req['requested_by'] ?? 0) !== $userId && !$isAdmin)) {
            setError("Only the original requester can confirm 'Received Asset'.");
            header("Location: {$redirect}");
            exit;
        }

        $res = $this->opoModel->markAssetReceived($prId, $userId, $note);
        if ($res['success']) {
            $codesStr = !empty($res['asset_codes']) ? (' Asset Code(s): ' . implode(', ', $res['asset_codes'])) : '';
            setSuccess("Asset receipt confirmed! Registered in Asset Tracker & synced with Accounts/Financial Statements.{$codesStr}");
        } else {
            setError($res['error'] ?? 'Failed to confirm asset receipt.');
        }

        header("Location: {$redirect}");
        exit;
    }
}

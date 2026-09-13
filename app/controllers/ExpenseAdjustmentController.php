<?php
require_once __DIR__ . '/../models/ExpenseAdjustment.php';
require_once __DIR__ . '/../models/CashoutRequest.php';
require_once __DIR__ . '/../views/accounts/AccountMailService.php';

class ExpenseAdjustmentController extends Controller
{
    protected $db;
    protected $model;
    protected $cashoutModel;
    protected $mailService;
    protected $userId;
    protected $userRole;

    public function __construct($db)
    {
        $this->db = $db;
        $this->model = new ExpenseAdjustment($db);
        $this->cashoutModel = new CashoutRequest($db);
        $this->mailService = new AccountMailService($db);

        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->userId = $_SESSION['user']['id'] ?? null;
        $this->userRole = isset($_SESSION['user']['role_id']) ? (int)$_SESSION['user']['role_id'] : null;

        if (!$this->userId) {
            setError("Please login first.");
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }
    }

    public function isAccounts()
    {
        $roleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
        return in_array($this->userRole, [1, 3, 6, 30]) || strpos($roleName, 'account') !== false;
    }

    public function canQcApprove()
    {
        $roleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
        return in_array($this->userRole, [1, 3, 13, 24, 25]) || strpos($roleName, 'qc') !== false || strpos($roleName, 'audit') !== false;
    }

    public function canMgmtApprove()
    {
        $roleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
        return in_array($this->userRole, [1, 3, 20, 22, 32, 33]) || strpos($roleName, 'operation head') !== false || strpos($roleName, 'admin & operation') !== false || strpos($roleName, 'management') !== false || strpos($roleName, 'chief') !== false;
    }

    public function canApprove($type = null)
    {
        return $this->canMgmtApprove();
    }

    public function index()
    {
        $isAccounts = $this->isAccounts();
        $canQc = $this->canQcApprove();
        $canMgmt = $this->canMgmtApprove();
        $isApprover = ($isAccounts || $canQc || $canMgmt);

        // Determine user's primary actionable role
        $roleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
        $isPureAccounts = in_array($this->userRole, [6, 30]) || (strpos($roleName, 'account') !== false && !in_array($this->userRole, [1, 3, 20, 32, 33]));
        $isPureQc = in_array($this->userRole, [13, 24, 25]) || ((strpos($roleName, 'qc') !== false || strpos($roleName, 'audit') !== false) && !in_array($this->userRole, [1, 3, 20, 32, 33]));
        $isMgmt = in_array($this->userRole, [1, 3, 20, 22, 32, 33]) || strpos($roleName, 'operation head') !== false || strpos($roleName, 'admin & operation') !== false || strpos($roleName, 'management') !== false || strpos($roleName, 'chief') !== false;

        $actionableRole = null;
        $actionableTabName = 'Action Required';
        if ($isPureAccounts) {
            $actionableRole = 'accounts';
            $actionableTabName = 'Pending Accounts Action';
        } elseif ($isPureQc) {
            $actionableRole = 'qc';
            $actionableTabName = 'Pending QC Audit';
        } elseif ($isMgmt) {
            $actionableRole = 'management';
            $actionableTabName = 'Pending Management Approval';
        }

        // Allow explicit stage override via ?stage= (for Admin/Management to toggle between QC / Mgmt / Accounts queues)
        if (isset($_GET['stage']) && in_array($_GET['stage'], ['qc', 'management', 'accounts'])) {
            $actionableRole = $_GET['stage'];
            if ($actionableRole === 'qc') $actionableTabName = 'Pending QC Audit';
            elseif ($actionableRole === 'management') $actionableTabName = 'Pending Management Approval';
            elseif ($actionableRole === 'accounts') $actionableTabName = 'Pending Accounts Action';
        }

        // Get live counts for tabs
        $counts = $this->model->getTabCounts($this->userId, $actionableRole);

        // Determine view
        if (isset($_GET['view'])) {
            $viewType = $_GET['view'];
        } else {
            if ($isApprover) {
                $viewType = ($counts['actionable'] > 0) ? 'actionable' : ($isAccounts ? 'all' : 'my');
            } else {
                $viewType = 'my';
            }
        }

        // Enforce restriction: non-approvers can only view their own
        if (!$isApprover && $viewType !== 'my') {
            $viewType = 'my';
        }

        // Fetch records based on active view
        if ($viewType === 'my') {
            $adjustments = $this->model->getAll($this->userId);
        } elseif ($viewType === 'actionable' && $actionableRole) {
            $adjustments = $this->model->getAll(null, $actionableRole);
        } else {
            $adjustments = $this->model->getAll(null, null);
        }

        $this->view('accounts/expense-adjustments/index', [
            'current_route' => 'expense-adjustments',
            'page_title' => 'Expense Adjustments',
            'adjustments' => $adjustments,
            'isAccounts' => $isAccounts,
            'canQc' => $canQc,
            'canMgmt' => $canMgmt,
            'isApprover' => $isApprover,
            'actionableRole' => $actionableRole,
            'actionableTabName' => $actionableTabName,
            'viewType' => $viewType,
            'counts' => $counts
        ]);
    }

    public function create()
    {
        $userCashouts = [];
        $isAccounts = $this->isAccounts();
        $userId = $this->userId;

        // 1. Cashout Requests (exclude ones linked to client refunds)
        $sql1 = $isAccounts ? 
            "SELECT id, purpose_paid_to as purpose, amount, status, 'cashout' as type, created_at FROM cashout_requests WHERE status IN ('approved', 'partially_paid', 'fully_paid') AND id NOT IN (SELECT cashout_request_id FROM client_refund_requests WHERE cashout_request_id IS NOT NULL) ORDER BY created_at DESC" :
            "SELECT id, purpose_paid_to as purpose, amount, status, 'cashout' as type, created_at FROM cashout_requests WHERE requested_by = ? AND status IN ('approved', 'partially_paid', 'fully_paid') AND id NOT IN (SELECT cashout_request_id FROM client_refund_requests WHERE cashout_request_id IS NOT NULL) ORDER BY created_at DESC";
        $stmt1 = $this->db->prepare($sql1);
        if (!$isAccounts) $stmt1->bind_param("i", $userId);
        $stmt1->execute();
        $res1 = $stmt1->get_result();
        while($r = $res1->fetch_assoc()) $userCashouts[] = $r;

        // 2. Vendor Payment Requests
        $sql2 = $isAccounts ? 
            "SELECT id, CONCAT('Vendor Payment ', IFNULL(invoice_number, '')) as purpose, net_payable_amount as amount, status, 'vendor' as type, created_at FROM vendor_payment_requests WHERE status IN ('Approved', 'Paid', 'Partially_Paid', 'Fully_Paid') ORDER BY created_at DESC" :
            "SELECT id, CONCAT('Vendor Payment ', IFNULL(invoice_number, '')) as purpose, net_payable_amount as amount, status, 'vendor' as type, created_at FROM vendor_payment_requests WHERE created_by = ? AND status IN ('Approved', 'Paid', 'Partially_Paid', 'Fully_Paid') ORDER BY created_at DESC";
        $stmt2 = $this->db->prepare($sql2);
        if (!$isAccounts) $stmt2->bind_param("i", $userId);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        while($r = $res2->fetch_assoc()) $userCashouts[] = $r;

        // 3. Contractor Bill Requests
        $sql3 = $isAccounts ? 
            "SELECT id, work_description as purpose, bill_amount as amount, status, 'contractor' as type, created_at FROM contractor_bill_requests WHERE status IN ('approved', 'paid', 'partially_paid', 'fully_paid') ORDER BY created_at DESC" :
            "SELECT id, work_description as purpose, bill_amount as amount, status, 'contractor' as type, created_at FROM contractor_bill_requests WHERE created_by = ? AND status IN ('approved', 'paid', 'partially_paid', 'fully_paid') ORDER BY created_at DESC";
        $stmt3 = $this->db->prepare($sql3);
        if (!$isAccounts) $stmt3->bind_param("i", $userId);
        $stmt3->execute();
        $res3 = $stmt3->get_result();
        while($r = $res3->fetch_assoc()) $userCashouts[] = $r;

        // 4. Purchase Requests
        $sql4 = $isAccounts ? 
            "SELECT id, notes as purpose, 0 as amount, status, 'purchase' as type, created_at FROM purchase_requests WHERE status IN ('Approved', 'PO Issued') ORDER BY created_at DESC" :
            "SELECT id, notes as purpose, 0 as amount, status, 'purchase' as type, created_at FROM purchase_requests WHERE created_by = ? AND status IN ('Approved', 'PO Issued') ORDER BY created_at DESC";
        $stmt4 = $this->db->prepare($sql4);
        if (!$isAccounts) $stmt4->bind_param("i", $userId);
        $stmt4->execute();
        $res4 = $stmt4->get_result();
        while($r = $res4->fetch_assoc()) $userCashouts[] = $r;

        // 5. Project Other Costs
        $sql5 = $isAccounts ? 
            "SELECT id, CONCAT('Other Cost: ', IFNULL(purpose, rcost_type)) as purpose, COALESCE(payment_amount, approved_amount, request_amount) as amount, status, 'other_cost' as type, created_at FROM project_other_costs WHERE status IN ('management_approved', 'paid') ORDER BY created_at DESC" :
            "SELECT id, CONCAT('Other Cost: ', IFNULL(purpose, rcost_type)) as purpose, COALESCE(payment_amount, approved_amount, request_amount) as amount, status, 'other_cost' as type, created_at FROM project_other_costs WHERE created_by_id = ? AND status IN ('management_approved', 'paid') ORDER BY created_at DESC";
        $stmt5 = $this->db->prepare($sql5);
        if (!$isAccounts) $stmt5->bind_param("i", $userId);
        $stmt5->execute();
        $res5 = $stmt5->get_result();
        while($r = $res5->fetch_assoc()) $userCashouts[] = $r;

        // 6. Extra Purchases / Office Procurement
        $sql6 = $isAccounts ? 
            "SELECT id, CONCAT('Extra Asset: ', IFNULL(item_name, 'Office Procurement')) as purpose, COALESCE(paid_amount, total_price) as amount, payment_status as status, 'extra_purchase' as type, created_at FROM project_extra_assets WHERE payment_status = 'paid' OR overall_status IN ('approved', 'paid', 'purchased') ORDER BY created_at DESC" :
            "SELECT id, CONCAT('Extra Asset: ', IFNULL(item_name, 'Office Procurement')) as purpose, COALESCE(paid_amount, total_price) as amount, payment_status as status, 'extra_purchase' as type, created_at FROM project_extra_assets WHERE requested_by = ? AND (payment_status = 'paid' OR overall_status IN ('approved', 'paid', 'purchased')) ORDER BY created_at DESC";
        $stmt6 = $this->db->prepare($sql6);
        if (!$isAccounts) $stmt6->bind_param("i", $userId);
        $stmt6->execute();
        $res6 = $stmt6->get_result();
        while($r = $res6->fetch_assoc()) $userCashouts[] = $r;

        // If a specific parent_type and cashout_request_id were requested via query params,
        // ensure it is present in the list even if creator filter omitted it
        $reqType = $_GET['parent_type'] ?? null;
        $reqId = (int)($_GET['cashout_request_id'] ?? 0);
        if ($reqType && $reqId > 0) {
            $alreadyPresent = false;
            foreach ($userCashouts as $co) {
                if ($co['type'] === $reqType && (int)$co['id'] === $reqId) {
                    $alreadyPresent = true;
                    break;
                }
            }
            if (!$alreadyPresent) {
                if ($reqType === 'other_cost') {
                    $s = $this->db->prepare("SELECT id, CONCAT('Other Cost: ', IFNULL(purpose, rcost_type)) as purpose, COALESCE(payment_amount, approved_amount, request_amount) as amount, status, 'other_cost' as type, created_at FROM project_other_costs WHERE id = ?");
                    if ($s) {
                        $s->bind_param("i", $reqId);
                        $s->execute();
                        $row = $s->get_result()->fetch_assoc();
                        if ($row) array_unshift($userCashouts, $row);
                    }
                } elseif ($reqType === 'extra_purchase') {
                    $s = $this->db->prepare("SELECT id, CONCAT('Extra Asset: ', IFNULL(item_name, 'Office Procurement')) as purpose, COALESCE(paid_amount, total_price) as amount, payment_status as status, 'extra_purchase' as type, created_at FROM project_extra_assets WHERE id = ?");
                    if ($s) {
                        $s->bind_param("i", $reqId);
                        $s->execute();
                        $row = $s->get_result()->fetch_assoc();
                        if ($row) array_unshift($userCashouts, $row);
                    }
                } elseif ($reqType === 'vendor') {
                    $s = $this->db->prepare("SELECT id, CONCAT('Vendor Payment ', IFNULL(invoice_number, '')) as purpose, net_payable_amount as amount, status, 'vendor' as type, created_at FROM vendor_payment_requests WHERE id = ?");
                    if ($s) {
                        $s->bind_param("i", $reqId);
                        $s->execute();
                        $row = $s->get_result()->fetch_assoc();
                        if ($row) array_unshift($userCashouts, $row);
                    }
                } elseif ($reqType === 'contractor') {
                    $s = $this->db->prepare("SELECT id, work_description as purpose, bill_amount as amount, status, 'contractor' as type, created_at FROM contractor_bill_requests WHERE id = ?");
                    if ($s) {
                        $s->bind_param("i", $reqId);
                        $s->execute();
                        $row = $s->get_result()->fetch_assoc();
                        if ($row) array_unshift($userCashouts, $row);
                    }
                } elseif ($reqType === 'cashout') {
                    $s = $this->db->prepare("SELECT id, purpose_paid_to as purpose, amount, status, 'cashout' as type, created_at FROM cashout_requests WHERE id = ?");
                    if ($s) {
                        $s->bind_param("i", $reqId);
                        $s->execute();
                        $row = $s->get_result()->fetch_assoc();
                        if ($row) array_unshift($userCashouts, $row);
                    }
                }
            }
        }

        // Sort overall
        usort($userCashouts, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        $this->view('accounts/expense-adjustments/create', [
            'current_route' => 'expense-adjustments',
            'page_title' => 'Create Adjustment Request',
            'userCashouts' => $userCashouts
        ]);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/expense-adjustments");
            exit;
        }

        $cashoutVal = $_POST['cashout_request_id'] ?? null;
        $type = $_POST['adjustment_type'] ?? null;
        $amount = $_POST['amount'] ?? 0;
        $title = $_POST['title'] ?? '';
        $desc = $_POST['description'] ?? '';
        $note = $_POST['note'] ?? '';

        if (!$cashoutVal || !$type || $amount <= 0 || empty($title)) {
            setError('Please fill in all required fields properly.');
            header("Location: {$GLOBALS['base_url']}/expense-adjustments/create");
            exit;
        }

        $parts = explode(':', $cashoutVal);
        $parentType = $parts[0] ?? 'cashout';
        $cashoutId = $parts[1] ?? 0;

        $docPaths = [];
        if (!empty($_FILES['document']['name'][0])) {
            $uploadDir = 'public/uploads/expense-adjustments/';
            if (!is_dir(__DIR__ . '/../../' . $uploadDir)) {
                mkdir(__DIR__ . '/../../' . $uploadDir, 0777, true);
            }
            foreach ($_FILES['document']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['document']['error'][$key] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['document']['name'][$key], PATHINFO_EXTENSION);
                    $fileName = 'doc_' . time() . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($tmpName, __DIR__ . '/../../' . $uploadDir . $fileName)) {
                        $docPaths[] = $uploadDir . $fileName;
                    }
                }
            }
        }

        // Fetch parent details to get project_id and office
        $projectId = null;
        $office = null;
        if ($parentType == 'cashout') {
            $stmt = $this->db->prepare("SELECT project_id, office FROM cashout_requests WHERE id = ?");
            $stmt->bind_param("i", $cashoutId);
            $stmt->execute();
            $pData = $stmt->get_result()->fetch_assoc();
            $projectId = $pData['project_id'] ?? null;
            $office = $pData['office'] ?? null;
        } elseif ($parentType == 'vendor') {
            $stmt = $this->db->prepare("SELECT project_id FROM vendor_payment_requests WHERE id = ?");
            $stmt->bind_param("i", $cashoutId);
            $stmt->execute();
            $pData = $stmt->get_result()->fetch_assoc();
            $projectId = $pData['project_id'] ?? null;
        } elseif ($parentType == 'contractor') {
            $stmt = $this->db->prepare("SELECT project_id FROM contractor_bill_requests WHERE id = ?");
            $stmt->bind_param("i", $cashoutId);
            $stmt->execute();
            $pData = $stmt->get_result()->fetch_assoc();
            $projectId = $pData['project_id'] ?? null;
        } elseif ($parentType == 'purchase') {
            $stmt = $this->db->prepare("SELECT project_id FROM purchase_requests WHERE id = ?");
            $stmt->bind_param("i", $cashoutId);
            $stmt->execute();
            $pData = $stmt->get_result()->fetch_assoc();
            $projectId = $pData['project_id'] ?? null;
        } elseif ($parentType == 'other_cost') {
            $stmt = $this->db->prepare("SELECT project_id FROM project_other_costs WHERE id = ?");
            $stmt->bind_param("i", $cashoutId);
            $stmt->execute();
            $pData = $stmt->get_result()->fetch_assoc();
            $projectId = $pData['project_id'] ?? null;
        } elseif ($parentType == 'extra_purchase') {
            $stmt = $this->db->prepare("SELECT project_id, asset_location as office FROM project_extra_assets WHERE id = ?");
            $stmt->bind_param("i", $cashoutId);
            $stmt->execute();
            $pData = $stmt->get_result()->fetch_assoc();
            $projectId = $pData['project_id'] ?? null;
            $office = $pData['office'] ?? null;
        }

        $data = [
            'user_id' => $this->userId,
            'cashout_request_id' => $cashoutId,
            'parent_type' => $parentType,
            'adjustment_type' => $type,
            'title' => $title,
            'description' => $desc,
            'amount' => $amount,
            'document' => json_encode($docPaths),
            'note' => $note,
            'project_id' => $projectId,
            'office' => $office
        ];

        $adjId = $this->model->create($data);
        if ($adjId) {
            // Auto sync to approval requests
            try {
                require_once __DIR__ . '/../models/ApprovalRequest.php';
                (new ApprovalRequest($this->db))->syncMissingExpenseAdjustments();
            } catch (\Throwable $e) {
                error_log("Approval sync error on adjustment submission: " . $e->getMessage());
            }

            // Send email notification
            try {
                $adjData = $this->model->getById($adjId);
                if ($adjData) {
                    $this->mailService->sendOnAdjustmentSubmission($adjData);
                }
            } catch (\Throwable $e) {
                error_log("Expense Adjustment Mail Error (Submission): " . $e->getMessage());
            }

            setSuccess('Adjustment request created successfully.');
            header("Location: {$GLOBALS['base_url']}/expense-adjustments");
        } else {
            setError('Failed to create adjustment request.');
            header("Location: {$GLOBALS['base_url']}/expense-adjustments/create");
        }
    }

    public function show()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            setError('Invalid adjustment ID.');
            header("Location: {$GLOBALS['base_url']}/expense-adjustments");
            exit;
        }

        $adj = $this->model->getById($id);
        if (!$adj) {
            setError('Adjustment not found.');
            header("Location: {$GLOBALS['base_url']}/expense-adjustments");
            exit;
        }

        $isApprover = ($this->isAccounts() || $this->canQcApprove() || $this->canMgmtApprove());
        if (!$isApprover && (int)$adj['user_id'] !== (int)$this->userId) {
            setError('You do not have permission to view this adjustment request.');
            header("Location: {$GLOBALS['base_url']}/expense-adjustments");
            exit;
        }

        $transactions = $this->model->getTransactions($id);
        
        $bankAccounts = [];
        $bRes = $this->db->query("SELECT id, account_code, account_name FROM chart_of_accounts WHERE account_code LIKE '1110%' AND account_code != '1110' AND status = 'active' ORDER BY account_code ASC");
        if ($bRes) {
            $bankAccounts = $bRes->fetch_all(MYSQLI_ASSOC);
        }

        $this->view('accounts/expense-adjustments/show', [
            'current_route' => 'expense-adjustments',
            'page_title' => 'Adjustment Details',
            'adj' => $adj,
            'transactions' => $transactions,
            'canApprove' => $this->canApprove($adj['adjustment_type']),
            'canQcApprove' => $this->canQcApprove(),
            'canMgmtApprove' => $this->canMgmtApprove(),
            'isAccounts' => $this->isAccounts(),
            'bankAccounts' => $bankAccounts
        ]);
    }

    public function qcApprove()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/expense-adjustments");
            exit;
        }

        $id = $_POST['id'] ?? null;
        $adj = $this->model->getById($id);
        if (!$adj || $adj['status'] !== 'pending' || $adj['adjustment_type'] !== 'extra') {
            setError('Invalid or already processed request.');
            header("Location: {$GLOBALS['base_url']}/expense-adjustments");
            exit;
        }

        if (!$this->canQcApprove()) {
            setError('You do not have permission to audit/approve this request as QC.');
            header("Location: {$GLOBALS['base_url']}/expense-adjustments/show?id=$id");
            exit;
        }

        $qcAmount = !empty($_POST['qc_approved_amount']) ? (float)$_POST['qc_approved_amount'] : (float)$adj['amount'];
        $qcNote = trim($_POST['qc_note'] ?? '');

        if ($qcAmount <= 0) {
            setError('Invalid approval amount.');
            header("Location: {$GLOBALS['base_url']}/expense-adjustments/show?id=$id");
            exit;
        }

        if ($this->model->qcApprove($id, $this->userId, $qcAmount, $qcNote)) {
            try {
                require_once __DIR__ . '/../models/ApprovalRequest.php';
                (new ApprovalRequest($this->db))->syncMissingExpenseAdjustments();
            } catch (\Throwable $e) {}

            setSuccess('Request successfully audited and approved by QC & Internal Audit. Forwarded to Management.');
        } else {
            setError('Failed to process QC approval.');
        }
        header("Location: {$GLOBALS['base_url']}/expense-adjustments/show?id=$id");
    }

    public function approve()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/expense-adjustments");
            exit;
        }

        $id = $_POST['id'] ?? null;
        $adj = $this->model->getById($id);
        if (!$adj || !in_array($adj['status'], ['pending', 'qc_approved'])) {
            setError('Invalid or already processed request.');
            header("Location: {$GLOBALS['base_url']}/expense-adjustments");
            exit;
        }

        if (!$this->canMgmtApprove()) {
            setError('You do not have permission to approve this request.');
            header("Location: {$GLOBALS['base_url']}/expense-adjustments");
            exit;
        }

        $approvedAmount = !empty($_POST['approved_amount']) ? (float)$_POST['approved_amount'] : (float)($adj['approved_amount'] ?: $adj['amount']);
        $mgmtNote = trim($_POST['mgmt_note'] ?? '');

        if ($approvedAmount <= 0) {
            setError('Invalid approval amount.');
            header("Location: {$GLOBALS['base_url']}/expense-adjustments/show?id=$id");
            exit;
        }

        if ($this->model->mgmtApprove($id, $this->userId, $approvedAmount, $mgmtNote)) {
            try {
                require_once __DIR__ . '/../models/ApprovalRequest.php';
                (new ApprovalRequest($this->db))->syncMissingExpenseAdjustments();
            } catch (\Throwable $e) {}

            // Send email notification
            try {
                $freshAdj = $this->model->getById($id);
                if ($freshAdj) {
                    $this->mailService->sendOnAdjustmentApproval($freshAdj, $this->userId);
                }
            } catch (\Throwable $e) {
                error_log("Expense Adjustment Mail Error (Approval): " . $e->getMessage());
            }

            setSuccess('Adjustment request approved successfully by Management. Forwarded to Accounts for disbursement.');
        } else {
            setError('Failed to approve request.');
        }
        header("Location: {$GLOBALS['base_url']}/expense-adjustments/show?id=$id");
    }

    public function reject()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/expense-adjustments");
            exit;
        }

        $id = $_POST['id'] ?? null;
        $adj = $this->model->getById($id);
        if (!$adj) {
            setError('Invalid request.');
            header("Location: {$GLOBALS['base_url']}/expense-adjustments");
            exit;
        }

        if (!$this->canQcApprove() && !$this->canMgmtApprove()) {
            setError('You do not have permission to reject this request.');
            header("Location: {$GLOBALS['base_url']}/expense-adjustments");
            exit;
        }

        $rejectionNote = trim($_POST['rejection_note'] ?? '');

        if ($this->model->reject($id, $this->userId, $rejectionNote)) {
            try {
                require_once __DIR__ . '/../models/ApprovalRequest.php';
                (new ApprovalRequest($this->db))->syncMissingExpenseAdjustments();
            } catch (\Throwable $e) {}

            // Send email notification
            try {
                $freshAdj = $this->model->getById($id);
                if ($freshAdj) {
                    $this->mailService->sendOnAdjustmentRejection($freshAdj, $this->userId);
                }
            } catch (\Throwable $e) {
                error_log("Expense Adjustment Mail Error (Rejection): " . $e->getMessage());
            }

            setSuccess('Adjustment request rejected.');
        } else {
            setError('Failed to reject request.');
        }
        header("Location: {$GLOBALS['base_url']}/expense-adjustments/show?id=$id");
    }

    public function addPayment()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->isAccounts()) {
            header("Location: {$GLOBALS['base_url']}/expense-adjustments");
            exit;
        }

        $id = $_POST['adjustment_id'] ?? null;
        $amount = (float)($_POST['amount'] ?? 0);
        
        $adj = $this->model->getById($id);
        if (!$adj) {
            setError('Invalid adjustment request.');
            header("Location: {$GLOBALS['base_url']}/expense-adjustments");
            exit;
        }

        $isReturn = ($adj['adjustment_type'] === 'return');

        if ($isReturn) {
            if (!in_array($adj['status'], ['pending', 'partially_paid'])) {
                setError('Return request has already been settled or is in invalid status.');
                header("Location: {$GLOBALS['base_url']}/expense-adjustments/show?id=$id");
                exit;
            }
            $targetDue = (!empty($adj['due_amount']) && (float)$adj['due_amount'] > 0) ? (float)$adj['due_amount'] : (float)$adj['amount'];
        } else {
            if (!in_array($adj['status'], ['approved', 'partially_paid'])) {
                setError('Extra request must be approved by Management before Accounts payout.');
                header("Location: {$GLOBALS['base_url']}/expense-adjustments/show?id=$id");
                exit;
            }
            $targetDue = (float)$adj['due_amount'];
        }

        if ($amount <= 0 || $amount > ($targetDue + 0.01)) {
            setError('Invalid amount entered. Cannot exceed remaining due (TK.' . number_format($targetDue, 2) . ').');
            header("Location: {$GLOBALS['base_url']}/expense-adjustments/show?id=$id");
            exit;
        }

        $proofPaths = [];
        if (!empty($_FILES['disbursement_proof']['name'][0])) {
            $uploadDir = 'public/uploads/adjustments_proof/';
            if (!is_dir(__DIR__ . '/../../' . $uploadDir)) {
                mkdir(__DIR__ . '/../../' . $uploadDir, 0777, true);
            }
            foreach ($_FILES['disbursement_proof']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['disbursement_proof']['error'][$key] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['disbursement_proof']['name'][$key], PATHINFO_EXTENSION);
                    $fileName = 'proof_' . time() . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($tmpName, __DIR__ . '/../../' . $uploadDir . $fileName)) {
                        $proofPaths[] = $uploadDir . $fileName;
                    }
                }
            }
        }

        $data = [
            'adjustment_id' => $id,
            'transaction_type' => $isReturn ? 'cashin' : 'cashout',
            'amount' => $amount,
            'payment_date' => $_POST['payment_date'] ?? date('Y-m-d'),
            'payment_method' => $_POST['payment_method'] ?? 'cash',
            'transaction_ref' => $_POST['transaction_ref'] ?? null,
            'bank_id' => !empty($_POST['bank_id']) ? (int)$_POST['bank_id'] : null,
            'bank_account_no' => $_POST['bank_account_no'] ?? null,
            'cheque_no' => $_POST['cheque_no'] ?? null,
            'online_type' => $_POST['online_type'] ?? null,
            'disbursement_proof' => json_encode($proofPaths),
            'created_by' => $this->userId
        ];

        $txnId = $this->model->addTransaction($data);
        if ($txnId) {
            // AUTO-GENERATE ADJUSTMENT JOURNAL
            try {
                require_once __DIR__ . '/../services/JournalIntegrationService.php';
                $journalService = new \Services\JournalIntegrationService($this->db);
                $journalService->generateAdjustmentJournal($txnId, $this->userId);
            } catch (\Exception $e) {
                error_log("Failed to auto-generate adjustment journal: " . $e->getMessage());
            }

            // Sync approval requests
            try {
                require_once __DIR__ . '/../models/ApprovalRequest.php';
                (new ApprovalRequest($this->db))->syncMissingExpenseAdjustments();
            } catch (\Throwable $e) {}

            $msg = $isReturn ? 'Return money verified and recorded into Cash-In successfully.' : 'Disbursement payout recorded successfully.';
            setSuccess($msg);
        } else {
            setError('Failed to record transaction.');
        }
        header("Location: {$GLOBALS['base_url']}/expense-adjustments/show?id=$id");
    }
}

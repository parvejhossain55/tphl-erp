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

    private function isAccounts()
    {
        // Typically Accounts role is 6, or Admins (1, 3)
        return in_array($this->userRole, [1, 3, 6, 30]);
    }

    private function canApprove($type = null)
    {
        // Only Management/Admin roles can approve
        return in_array($this->userRole, [1, 3]);
    }

    public function index()
    {
        // For accounts/admin, they can see all. Regular users see their own.
        // Wait, the user said "accounts there will be two pages my requestes all requests"
        // Let's implement a filter for `?view=all` vs `?view=my`
        $viewType = $_GET['view'] ?? ($this->isAccounts() ? 'all' : 'my');
        
        $filterUserId = null;
        if ($viewType === 'my') {
            $filterUserId = $this->userId;
        }

        $adjustments = $this->model->getAll($filterUserId);

        $this->view('accounts/expense-adjustments/index', [
            'current_route' => 'expense-adjustments',
            'page_title' => 'Expense Adjustments',
            'adjustments' => $adjustments,
            'isAccounts' => $this->isAccounts(),
            'viewType' => $viewType
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

        $transactions = $this->model->getTransactions($id);
        
        $this->view('accounts/expense-adjustments/show', [
            'current_route' => 'expense-adjustments',
            'page_title' => 'Adjustment Details',
            'adj' => $adj,
            'transactions' => $transactions,
            'canApprove' => $this->canApprove($adj['adjustment_type']),
            'isAccounts' => $this->isAccounts()
        ]);
    }

    public function approve()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/expense-adjustments");
            exit;
        }

        $id = $_POST['id'] ?? null;
        $adj = $this->model->getById($id);
        if (!$adj || $adj['status'] !== 'pending') {
            setError('Invalid or already processed request.');
            header("Location: {$GLOBALS['base_url']}/expense-adjustments");
            exit;
        }

        if (!$this->canApprove($adj['adjustment_type'])) {
            setError('You do not have permission to approve this request.');
            header("Location: {$GLOBALS['base_url']}/expense-adjustments");
            exit;
        }

        // Management can set the approved amount for any type
        $approvedAmount = $_POST['approved_amount'] ?? $adj['amount'];
        if ($approvedAmount <= 0) {
            setError('Invalid approval amount.');
            header("Location: {$GLOBALS['base_url']}/expense-adjustments/show?id=$id");
            exit;
        }

        if ($this->model->approve($id, $this->userId, $approvedAmount)) {
            // Send email notification
            try {
                $freshAdj = $this->model->getById($id);
                if ($freshAdj) {
                    $this->mailService->sendOnAdjustmentApproval($freshAdj, $this->userId);
                }
            } catch (\Throwable $e) {
                error_log("Expense Adjustment Mail Error (Approval): " . $e->getMessage());
            }

            setSuccess('Adjustment request approved successfully.');
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
        if (!$adj || !$this->canApprove($adj['adjustment_type'])) {
            setError('You do not have permission to reject this request.');
            header("Location: {$GLOBALS['base_url']}/expense-adjustments");
            exit;
        }

        if ($this->model->reject($id, $this->userId)) {
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
        $amount = $_POST['amount'] ?? 0;
        
        $adj = $this->model->getById($id);
        if (!$adj || !in_array($adj['status'], ['approved', 'partially_paid'])) {
            setError('Invalid adjustment request.');
            header("Location: {$GLOBALS['base_url']}/expense-adjustments");
            exit;
        }

        if ($amount <= 0 || $amount > $adj['due_amount']) {
            setError('Invalid payment amount.');
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
            'transaction_type' => ($adj['adjustment_type'] == 'return') ? 'cashin' : 'cashout',
            'amount' => $amount,
            'payment_date' => $_POST['payment_date'] ?? date('Y-m-d'),
            'payment_method' => $_POST['payment_method'] ?? 'cash',
            'transaction_ref' => $_POST['transaction_ref'] ?? null,
            'bank_id' => !empty($_POST['bank_id']) ? $_POST['bank_id'] : null,
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

            setSuccess('Transaction recorded successfully.');
        } else {
            setError('Failed to record transaction.');
        }
        header("Location: {$GLOBALS['base_url']}/expense-adjustments/show?id=$id");
    }
}

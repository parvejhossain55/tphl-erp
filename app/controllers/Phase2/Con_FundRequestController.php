<?php
namespace Phase2;

use Con_Contractor;
use Controller;
use Con_FundRequest;
use Con_Operator;
use Con_FinancialTransaction;
use ApprovalRequest;

class Con_FundRequestController extends Controller

{
    protected $db;
    protected $fundModel;
    protected $operatorModel;
    protected $approvalModel;
    protected $txnModel;
    protected $userRole;
    protected $userId;

    public function __construct($db)
    {
        $this->db            = $db;
        $this->fundModel     = new Con_FundRequest($this->db);
        $this->operatorModel = new Con_Contractor($this->db);
        $this->approvalModel = new ApprovalRequest($this->db);
        $this->txnModel      = new Con_FinancialTransaction($this->db);

        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->userId   = $_SESSION['user']['id'] ?? null;
        $this->userRole = isset($_SESSION['user']['role_id']) ? (int)$_SESSION['user']['role_id'] : null;
    }

    // =========================================================================
    // ROLE HELPERS
    // =========================================================================

    /** Roles 1 (Admin), 3 (Super Admin) can approve */
    private function canApprove()
    {
        return in_array($this->userRole, [1, 3]);
    }

    /** Roles 1, 3, 6 (Accounts) can process payments */
    private function canProcessPayment()
    {
        return in_array($this->userRole, [1, 3, 6]);
    }

    /* =====================
       List
    ====================== */
    public function index()
    {
        // Roles 1 (Admin), 3 (Super Admin), 6 (Accounts) see ALL requests
        // Other roles only see their own requests
        $filterUserId = null;
        if (!in_array($this->userRole, [1, 3, 6, 22])) {
            $filterUserId = $this->userId;
        }

        $requests = $this->fundModel->getAllWithRelations($filterUserId);

        $this->view('phase2/fund-requests/fund-lists', [
            'current_route' => 'con-fund-requests',
            'page_title'    => 'Operational Fund Requests',
            'requests'      => $requests,
            'userRole'      => $this->userRole,
        ]);
    }

    /* =====================
       Create Form
    ====================== */
    public function create()
    {
        $operators = $this->operatorModel->getAll();
        $projects  = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/fund-requests/create-fund', [
            'current_route' => 'con-fund-requests',
            'page_title'    => 'New Fund Request',
            'operators'     => $operators,
            'projects'      => $projects,
        ]);
    }

    /* =====================
       Store
       FLOW: pending → (auto create approval_request) → approved → paid
       NO verification step — goes directly to approval
    ====================== */
    public function store()
    {
        $data = [
            'project_id'   => $_POST['project_id']  ?? null,
            'work_plan_id' => !empty($_POST['work_plan_id']) ? (int)$_POST['work_plan_id'] : null,
            'requester_id' => $_SESSION['user']['id'] ?? null,
            'operator_id'  => !empty($_POST['operator_id']) ? $_POST['operator_id'] : null,
            'amount'       => $_POST['amount']       ?? 0,
            'purpose'      => trim($_POST['purpose'] ?? ''),
            'fund_date'    => $_POST['fund_date']    ?? date('Y-m-d'),
            'status'       => 'pending',
            'created_by'   => $_SESSION['user']['id'] ?? null,
        ];

        if (empty($data['project_id']) || empty($data['purpose']) || empty($data['amount'])) {
            setError('Project, Purpose and Amount are required.');
            header("Location: {$GLOBALS['base_url']}/con-fund-request-create");
            exit;
        }

        $fundId = $this->fundModel->create($data);

        if ($fundId) {
            $project = $this->db->query("SELECT name FROM projects WHERE id = " . (int)$data['project_id'])->fetch_assoc();

            // Auto-create approval_request — goes to management
            $this->approvalModel->create([
                'department'    => 'construction',
                'request_type'  => 'operational_fund',
                'title'         => 'Fund Request — ' . $data['purpose'] . ' | ' . ($project['name'] ?? ''),
                'source_table'  => 'operational_fund_requests',
                'source_id'     => $fundId,
                'requested_days'=> 0,
                'requested_by'  => $_SESSION['user']['id'] ?? null,
                'approve_role'  => 'management',
                'meta_data'     => null,
                'request_amount'=> $data['amount'],
            ]);

            setSuccess('Fund request submitted for approval!');
            header("Location: {$GLOBALS['base_url']}/con-fund-requests");
            exit;
        }

        setError('Failed to submit fund request.');
        header("Location: {$GLOBALS['base_url']}/con-fund-request-create");
        exit;
    }

    /* =====================
       Show Detail (Enhanced — like cashout view)
       Shows: request details + approval actions + payment form + payment history
    ====================== */
    public function show($id)
    {
        $request = $this->fundModel->findWithRelations($id);
        if (!$request) {
            setError('Fund request not found.');
            header("Location: {$GLOBALS['base_url']}/con-fund-requests");
            exit;
        }

        // Get payment history
        $payments     = $this->txnModel->getByRef('operational_fund', $id);
        $totalPaid    = $this->txnModel->getTotalPaidForSource('operational_fund', $id);
        $payableAmt   = (float)($request['approved_amount'] ?? $request['amount'] ?? 0);
        $dueAmount    = max(0, $payableAmt - $totalPaid);

        // Get linked approval_request for sync check
        $approvalReq = $this->getApprovalRequest($id);

        // Get banks for payment modal dropdown
        $banks = $this->db->query("SELECT id, bank_name, branch_name FROM banks WHERE status = 'active' ORDER BY bank_name")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/fund-requests/show-fund', [
            'current_route'     => 'con-fund-requests',
            'page_title'        => 'Fund Request Detail',
            'request'           => $request,
            'payments'          => $payments,
            'total_paid'        => $totalPaid,
            'due_amount'        => $dueAmount,
            'payable_amount'    => $payableAmt,
            'approval_request'  => $approvalReq,
            'canApprove'        => $this->canApprove(),
            'canProcessPayment' => $this->canProcessPayment(),
            'userRole'          => $this->userRole,
            'banks'             => $banks,
        ]);
    }

    /* =====================
       Approve Fund Request
       Management (roles 1,3) can approve directly from show page.
       Syncs with approval_requests table.
    ====================== */
    public function approve($id)
    {
        if (!$this->canApprove()) {
            setError('You are not authorized to approve fund requests.');
            header("Location: {$GLOBALS['base_url']}/con-fund-request/{$id}");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/con-fund-request/{$id}");
            exit;
        }

        $request = $this->fundModel->find($id);
        if (!$request || $request['status'] !== 'pending') {
            setError('Fund request is not in pending status.');
            header("Location: {$GLOBALS['base_url']}/con-fund-request/{$id}");
            exit;
        }

        $approvedAmount = (float)($_POST['approved_amount'] ?? $request['amount']);
        if ($approvedAmount <= 0) {
            $approvedAmount = (float)$request['amount'];
        }

        // Update fund request
        $this->fundModel->updateStatus($id, 'approved', $approvedAmount, $this->userId);

        // Set initial due_amount
        $stmt = $this->db->prepare("UPDATE operational_fund_requests SET due_amount = ? WHERE id = ?");
        $stmt->bind_param('di', $approvedAmount, $id);
        $stmt->execute();

        // Sync approval_requests table
        $this->syncApprovalRequest($id, 'approved');

        setSuccess('Fund request approved with amount ৳' . number_format($approvedAmount, 2));
        header("Location: {$GLOBALS['base_url']}/con-fund-request/{$id}");
        exit;
    }

    /* =====================
       Reject Fund Request
    ====================== */
    public function reject($id)
    {
        if (!$this->canApprove()) {
            setError('You are not authorized to reject fund requests.');
            header("Location: {$GLOBALS['base_url']}/con-fund-request/{$id}");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/con-fund-request/{$id}");
            exit;
        }

        $request = $this->fundModel->find($id);
        if (!$request || !in_array($request['status'], ['pending', 'approved'])) {
            setError('Fund request cannot be rejected in its current status.');
            header("Location: {$GLOBALS['base_url']}/con-fund-request/{$id}");
            exit;
        }

        $this->fundModel->updateStatus($id, 'rejected', null, $this->userId);

        // Sync approval_requests table
        $this->syncApprovalRequest($id, 'rejected');

        setSuccess('Fund request has been rejected.');
        header("Location: {$GLOBALS['base_url']}/con-fund-request/{$id}");
        exit;
    }

    /* =====================
       Add Payment (from show page — like cashout)
       Creates a con_financial_transaction entry.
    ====================== */
    public function addPayment($id)
    {
        if (!$this->canProcessPayment()) {
            setError('You are not authorized to process payments.');
            header("Location: {$GLOBALS['base_url']}/con-fund-request/{$id}");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/con-fund-request/{$id}");
            exit;
        }

        $request = $this->fundModel->find($id);
        if (!$request || !in_array($request['status'], ['approved', 'partially_paid'])) {
            setError('Fund request is not in a payable status.');
            header("Location: {$GLOBALS['base_url']}/con-fund-request/{$id}");
            exit;
        }

        // Handle file upload (mandatory)
        $proofPaths = [];
        if (!empty($_FILES['disbursement_proof']['name'][0])) {
            foreach ($_FILES['disbursement_proof']['tmp_name'] as $idx => $tmpName) {
                if ($_FILES['disbursement_proof']['error'][$idx] !== UPLOAD_ERR_OK) continue;
                $file = [
                    'name'     => $_FILES['disbursement_proof']['name'][$idx],
                    'tmp_name' => $tmpName,
                    'size'     => $_FILES['disbursement_proof']['size'][$idx],
                    'error'    => $_FILES['disbursement_proof']['error'][$idx],
                ];
                $res = $this->handleFileUpload($file);
                if ($res['success']) {
                    $proofPaths[] = $res['file_path'];
                } else {
                    setError($res['message']);
                    header("Location: {$GLOBALS['base_url']}/con-fund-request/{$id}");
                    exit;
                }
            }
        }

        if (empty($proofPaths)) {
            setError('Disbursement proof is required. Please upload at least one file.');
            header("Location: {$GLOBALS['base_url']}/con-fund-request/{$id}");
            exit;
        }

        $amount = (float)($_POST['amount'] ?? 0);
        if ($amount <= 0) {
            setError('Payment amount must be greater than zero.');
            header("Location: {$GLOBALS['base_url']}/con-fund-request/{$id}");
            exit;
        }

        // Validate against remaining due
        $totalPaidSoFar = $this->txnModel->getTotalPaidForSource('operational_fund', $id);
        $payableAmt     = (float)($request['approved_amount'] ?? $request['amount']);
        $remainingDue   = max(0, $payableAmt - $totalPaidSoFar);

        if ($amount > $remainingDue + 0.01) {
            setError("Payment amount (৳" . number_format($amount, 2) . ") exceeds remaining due (৳" . number_format($remainingDue, 2) . ").");
            header("Location: {$GLOBALS['base_url']}/con-fund-request/{$id}");
            exit;
        }

        // Determine payment type
        $paymentType = ($amount < $remainingDue - 0.01) ? 'Partial' : 'Full';

        $data = [
            'ref_type'           => 'operational_fund',
            'ref_id'             => (int)$id,
            'project_id'         => (int)($request['project_id'] ?? 0),
            'amount'             => $amount,
            'payment_type'       => $paymentType,
            'payment_method'     => $_POST['payment_method'] ?? 'Cash',
            'payment_date'       => $_POST['payment_date'] ?? date('Y-m-d'),
            'reference_no'       => trim($_POST['reference_no'] ?? ''),
            'notes'              => trim($_POST['notes'] ?? ''),
            'disbursement_proof' => json_encode($proofPaths),
            'processed_by'       => $this->userId,
        ];

        $txnId = $this->txnModel->create($data);

        if ($txnId) {
            setSuccess('Payment of ৳' . number_format($amount, 2) . ' recorded successfully! Transaction #' . $txnId);
        } else {
            setError('Failed to process payment.');
        }

        header("Location: {$GLOBALS['base_url']}/con-fund-request/{$id}");
        exit;
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /** Get the linked approval_request for this fund request */
    private function getApprovalRequest($fundId)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM approval_requests 
             WHERE source_table = 'operational_fund_requests' AND source_id = ? 
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->bind_param('i', $fundId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /** Sync approval_requests status when approving/rejecting from this page */
    private function syncApprovalRequest($fundId, $newStatus)
    {
        $arStatus = ($newStatus === 'approved') ? 'approved' : 'rejected';

        $stmt = $this->db->prepare(
            "UPDATE approval_requests 
             SET status = ?, approved_by = ?, approved_at = NOW() 
             WHERE source_table = 'operational_fund_requests' AND source_id = ? AND status = 'pending'"
        );
        $stmt->bind_param('sii', $arStatus, $this->userId, $fundId);
        $stmt->execute();
    }

    /** File upload handler */
    private function handleFileUpload($file)
    {
        $allowedTypes = ['doc', 'docx', 'pdf', 'jpg', 'jpeg', 'png'];
        $maxSize      = 5 * 1024 * 1024;

        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($fileExt, $allowedTypes)) {
            return ['success' => false, 'message' => "File '{$file['name']}': only DOC, DOCX, PDF, JPG, PNG allowed."];
        }

        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => "File '{$file['name']}' exceeds 5MB limit."];
        }

        $newFileName = 'con_fund_payment_' . time() . '_' . uniqid() . '.' . $fileExt;
        $uploadPath  = 'uploads/construction-payments/' . $newFileName;
        $fullPath    = __DIR__ . '/../../../public/' . $uploadPath;

        $dir = dirname($fullPath);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            return ['success' => true, 'file_path' => $uploadPath];
        }

        return ['success' => false, 'message' => "Failed to upload '{$file['name']}'."];
    }
}

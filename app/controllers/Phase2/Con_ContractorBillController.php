<?php
namespace Phase2;

use Controller;
use Con_ContractorBill;
use Con_Operator;
use Con_FinancialTransaction;
use ApprovalRequest;
use Con_Contractor;
use Con_TargetFixed;

class Con_ContractorBillController extends Controller
{
    protected $db;
    protected $billModel;
    protected $operatorModel;
    protected $approvalModel;
    protected $txnModel;
    protected $targetModel;
    protected $userRole;
    protected $userId;

    public function __construct($db)
    {
        $this->db            = $db;
        $this->billModel     = new Con_ContractorBill($this->db);
        $this->operatorModel = new Con_Contractor($this->db);
        $this->approvalModel = new ApprovalRequest($this->db);
        $this->txnModel      = new Con_FinancialTransaction($this->db);
        $this->targetModel   = new Con_TargetFixed($this->db);

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

    // =========================================================================
    // LIST — role-based filtering
    // =========================================================================

    public function index()
    {
        $filterUserId = null;
        if (!in_array($this->userRole, [1, 3, 6])) {
            $filterUserId = $this->userId;
        }

        $bills = $this->billModel->getAllWithRelations($filterUserId);

        $this->view('phase2/contractor-bills/bill-lists', [
            'current_route' => 'con-contractor-bills',
            'page_title'    => 'Contractor Bill Requests',
            'bills'         => $bills,
            'userRole'      => $this->userRole,
        ]);
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    public function create()
    {
        $operators = $this->operatorModel->getActiveContractors();
        $projects  = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name")->fetch_all(MYSQLI_ASSOC);
        $targets   = $this->targetModel->getAll();

        $this->view('phase2/contractor-bills/create-bill', [
            'current_route' => 'con-contractor-bills',
            'page_title'    => 'New Contractor Bill',
            'operators'     => $operators,
            'projects'      => $projects,
            'targets'       => $targets,
        ]);
    }

    // =========================================================================
    // STORE — auto-create approval_request
    // =========================================================================

    public function store()
    {
        $data = [
            'project_id'       => $_POST['project_id'] ?? null,
            'operator_id'      => $_POST['operator_id'] ?? null,
            'target_id'        => !empty($_POST['target_id']) ? $_POST['target_id'] : null,
            'bill_date'        => $_POST['bill_date'] ?? date('Y-m-d'),
            'work_description' => trim($_POST['work_description'] ?? ''),
            'bill_amount'      => $_POST['bill_amount'] ?? 0,
            'attachment_path'  => null,
            'status'           => 'submitted',
            'created_by'       => $this->userId,
        ];

        // Handle file upload
        if (!empty($_FILES['attachment']['name'])) {
            $uploadDir = __DIR__ . '/../../../public/uploads/contractor-bills/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename = time() . '_' . basename($_FILES['attachment']['name']);
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . $filename)) {
                $data['attachment_path'] = 'uploads/contractor-bills/' . $filename;
            }
        }

        if (empty($data['project_id']) || empty($data['operator_id']) || empty($data['work_description'])) {
            setError('Project, Contractor and Work Description are required.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill-create");
            exit;
        }

        $billId = $this->billModel->create($data);

        if ($billId) {
            $operator = $this->operatorModel->find($data['operator_id']);
            $project  = $this->db->query("SELECT name FROM projects WHERE id = " . (int)$data['project_id'])->fetch_assoc();

            $this->approvalModel->create([
                'department'    => 'construction',
                'request_type'  => 'contractor_bill',
                'title'         => 'Contractor Bill — ' . ($operator['name'] ?? '') . ' | ' . ($project['name'] ?? ''),
                'source_table'  => 'contractor_bill_requests',
                'source_id'     => $billId,
                'requested_days'=> 0,
                'requested_by'  => $this->userId,
                'approve_role'  => 'management',
                'meta_data'     => null,
                'request_amount'=> $data['bill_amount'],
            ]);

            setSuccess('Contractor bill submitted and sent for approval!');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$billId}");
            exit;
        }

        setError('Failed to submit contractor bill.');
        header("Location: {$GLOBALS['base_url']}/con-contractor-bill-create");
        exit;
    }

    // =========================================================================
    // SHOW — Enhanced single-page experience
    // =========================================================================

    public function show($id)
    {
        $bill = $this->billModel->findWithRelations($id);
        if (!$bill) {
            setError('Bill not found.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bills");
            exit;
        }

        // Get payment history (con_financial_transactions)
        $payments   = $this->txnModel->getByRef('contractor_bill', $id);
        $totalPaid  = $this->txnModel->getTotalPaidForSource('contractor_bill', $id);
        $payableAmt = (float)($bill['approved_amount'] ?? $bill['bill_amount'] ?? 0);
        $dueAmount  = max(0, $payableAmt - $totalPaid);

        // Get linked approval_request
        $approvalReq = $this->getApprovalRequest($id);

        // Get banks for payment modal
        $banks = $this->db->query("SELECT id, bank_name, branch_name FROM banks WHERE status = 'active' ORDER BY bank_name")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/contractor-bills/show-bill', [
            'current_route'     => 'con-contractor-bills',
            'page_title'        => 'Contractor Bill Detail',
            'bill'              => $bill,
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

    // =========================================================================
    // VERIFY (Site Engineer / Operation Head)
    // =========================================================================

    public function verify($id)
    {
        $data = [
            'status'            => 'verified',
            'verified_by'       => $this->userId,
            'verified_at'       => date('Y-m-d H:i:s'),
            'verification_note' => trim($_POST['verification_note'] ?? ''),
        ];

        if ($this->billModel->updateStatus($id, $data)) {
            setSuccess('Bill verified successfully!');
        } else {
            setError('Verification failed.');
        }

        header("Location: {$GLOBALS['base_url']}/con-contractor-bill/$id");
        exit;
    }

    // =========================================================================
    // APPROVE — Management (roles 1,3) with adjustable amount
    // =========================================================================

    public function approve($id)
    {
        if (!$this->canApprove()) {
            setError('You are not authorized to approve bills.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        $bill = $this->billModel->find($id);
        if (!$bill || !in_array($bill['status'], ['submitted', 'verified'])) {
            setError('Bill is not in an approvable status.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        $approvedAmount = (float)($_POST['approved_amount'] ?? $bill['bill_amount']);
        if ($approvedAmount <= 0) {
            $approvedAmount = (float)$bill['bill_amount'];
        }

        if ($this->billModel->approve($id, $this->userId, $approvedAmount)) {
            $this->syncApprovalRequest($id, 'approved');
            setSuccess('Bill approved with amount ৳' . number_format($approvedAmount, 2));
        } else {
            setError('Failed to approve or bill already processed.');
        }
        header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
        exit;
    }

    // =========================================================================
    // REJECT — Management (roles 1,3)
    // =========================================================================

    public function reject($id)
    {
        if (!$this->canApprove()) {
            setError('You are not authorized to reject bills.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        if ($this->billModel->reject($id, $this->userId)) {
            $this->syncApprovalRequest($id, 'rejected');
            setSuccess('Bill has been rejected.');
        } else {
            setError('Failed to reject bill.');
        }
        header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
        exit;
    }

    // =========================================================================
    // ADD PAYMENT — Accounts (roles 1,3,6)
    // =========================================================================

    public function addPayment($id)
    {
        if (!$this->canProcessPayment()) {
            setError('You are not authorized to process payments.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        $bill = $this->billModel->find($id);
        if (!$bill || !in_array($bill['status'], ['approved', 'partially_paid'])) {
            setError('Bill is not in a payable status.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
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
                    header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
                    exit;
                }
            }
        }

        if (empty($proofPaths)) {
            setError('Disbursement proof is required.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        $amount = (float)($_POST['amount'] ?? 0);
        if ($amount <= 0) {
            setError('Payment amount must be greater than zero.');
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        $totalPaidSoFar = $this->txnModel->getTotalPaidForSource('contractor_bill', $id);
        $payableAmt     = (float)($bill['approved_amount'] ?? $bill['bill_amount']);
        $remainingDue   = max(0, $payableAmt - $totalPaidSoFar);

        if ($amount > $remainingDue + 0.01) {
            setError("Payment (৳" . number_format($amount, 2) . ") exceeds remaining due (৳" . number_format($remainingDue, 2) . ").");
            header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
            exit;
        }

        $paymentType = ($amount < $remainingDue - 0.01) ? 'Partial' : 'Full';

        $data = [
            'ref_type'           => 'contractor_bill',
            'ref_id'             => (int)$id,
            'project_id'         => (int)($bill['project_id'] ?? 0),
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
            setSuccess('Payment of ৳' . number_format($amount, 2) . ' recorded! Transaction #' . $txnId);
        } else {
            setError('Failed to process payment.');
        }

        header("Location: {$GLOBALS['base_url']}/con-contractor-bill/{$id}");
        exit;
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    private function getApprovalRequest($billId)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM approval_requests
             WHERE source_table = 'contractor_bill_requests' AND source_id = ?
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->bind_param('i', $billId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function syncApprovalRequest($billId, $newStatus)
    {
        $arStatus = ($newStatus === 'approved') ? 'approved' : 'rejected';

        $stmt = $this->db->prepare(
            "UPDATE approval_requests
             SET status = ?, approved_by = ?, approved_at = NOW()
             WHERE source_table = 'contractor_bill_requests' AND source_id = ? AND status = 'pending'"
        );
        $stmt->bind_param('sii', $arStatus, $this->userId, $billId);
        $stmt->execute();
    }

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

        $newFileName = 'con_bill_payment_' . time() . '_' . uniqid() . '.' . $fileExt;
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

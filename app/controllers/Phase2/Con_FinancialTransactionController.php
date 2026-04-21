<?php
namespace Phase2;

use Controller;
use Con_FinancialTransaction;
use Con_VendorPaymentRequest;

class Con_FinancialTransactionController extends Controller
{
    protected $db;
    protected $txnModel;
    protected $vprModel;
    protected $userRole;
    protected $userId;

    public function __construct($db)
    {
        $this->db       = $db;
        $this->txnModel = new Con_FinancialTransaction($this->db);
        $this->vprModel = new Con_VendorPaymentRequest($this->db);

        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->userId   = $_SESSION['user']['id'] ?? null;
        $this->userRole = isset($_SESSION['user']['role_id']) ? (int)$_SESSION['user']['role_id'] : null;
    }

    // =========================================================================
    // ROLE HELPERS
    // =========================================================================

    /** Roles 1 (Admin), 3 (Super Admin) can approve vendor payments */
    private function canApprove()
    {
        return in_array($this->userRole, [1, 3]);
    }

    /** Roles 1, 3, 6 (Accounts) can process payments */
    private function canProcessPayment()
    {
        return in_array($this->userRole, [1, 3, 6]);
    }

    /** Admin-level viewing: roles 1, 3, 6 */
    private function isAdminUser()
    {
        return in_array($this->userRole, [1, 3, 6]);
    }

    /* =====================
       Ledger — All Transactions
    ====================== */
    public function index()
    {
        $transactions = $this->txnModel->getAll();

        $this->view('phase2/financial-transactions/transaction-lists', [
            'current_route' => 'con-transactions',
            'page_title'    => 'Financial Transactions Ledger',
            'transactions'  => $transactions,
        ]);
    }

    /* =====================
       Pending Payments List
       (Approved but not yet fully paid)
    ====================== */
    public function pendingList()
    {
        $pending = $this->txnModel->getPendingApprovals();

        $this->view('phase2/financial-transactions/pending-payments', [
            'current_route'     => 'con-pending-payments',
            'page_title'        => 'Pending Payments',
            'pending'           => $pending,
            'canProcessPayment' => $this->canProcessPayment(),
            'canApprove'        => $this->canApprove(),
            'userRole'          => $this->userRole,
        ]);
    }

    /* =====================
       Process Payment Form
       Handles: contractor_bill | operational_fund | vendor_payment
    ====================== */
    public function process($refType, $refId)
    {
        // Role check: only authorized users can process payments
        if (!$this->canProcessPayment()) {
            setError('You are not authorized to process payments.');
            header("Location: {$GLOBALS['base_url']}/con-pending-payments");
            exit;
        }

        $source      = null;
        $advancePaid = 0.00;
        $penalty     = 0.00;
        $netPayable  = 0.00;

        if ($refType === 'contractor_bill') {
          
            $stmt = $this->db->prepare(
                "SELECT cbr.*, p.name AS project_name, oo.name AS operator_name
                 FROM contractor_bill_requests cbr
                 LEFT JOIN projects p ON p.id = cbr.project_id
                 LEFT JOIN operations_operators oo ON oo.id = cbr.operator_id
                 WHERE cbr.id = ?"
            );
            $stmt->bind_param('i', $refId);
            $stmt->execute();
            $source = $stmt->get_result()->fetch_assoc();
            $netPayable = (float)($source['approved_amount'] ?? $source['bill_amount'] ?? 0);

        } elseif ($refType === 'operational_fund') {
            $stmt = $this->db->prepare(
                "SELECT ofr.*, p.name AS project_name
                 FROM operational_fund_requests ofr
                 LEFT JOIN projects p ON p.id = ofr.project_id
                 WHERE ofr.id = ?"
            );
            $stmt->bind_param('i', $refId);
            $stmt->execute();
            $source = $stmt->get_result()->fetch_assoc();
            $netPayable = (float)($source['approved_amount'] ?? $source['amount'] ?? 0);

        } elseif ($refType === 'vendor_payment') {
            $source = $this->vprModel->find($refId);

            if ($source) {
                $netPayable = (float)($source['net_payable'] ?? 0);
                $penalty    = (float)($source['penalty_deducted'] ?? 0);

                // For Final/Partial: check how much Advance was already paid for same PO
                if (!empty($source['purchase_order_id']) && $source['payment_type'] !== 'Advance') {
                    $advancePaid = $this->vprModel->getTotalAdvancePaid($source['purchase_order_id']);
                    $netPayable = max(0, $netPayable - $advancePaid);
                }
            }
        }

        if (!$source) {
            setError('Source record not found.');
            header("Location: {$GLOBALS['base_url']}/con-pending-payments");
            exit;
        }

        // Get previous payments for this source (for partial payment tracking)
        $previousPayments = $this->txnModel->getByRef($refType, $refId);
        $totalPaidSoFar   = $this->txnModel->getTotalPaidForSource($refType, $refId);
        $remainingDue     = max(0, $netPayable - $totalPaidSoFar);

        // Get banks for dropdown
        $banks = $this->db->query("SELECT id, bank_name, branch_name FROM banks WHERE status = 'active' ORDER BY bank_name")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/financial-transactions/process-payment', [
            'current_route'     => 'con-pending-payments',
            'page_title'        => 'Process Payment',
            'source'            => $source,
            'ref_type'          => $refType,
            'ref_id'            => $refId,
            'advance_paid'      => $advancePaid,
            'penalty'           => $penalty,
            'net_payable'       => $netPayable,
            'previous_payments' => $previousPayments,
            'total_paid_so_far' => $totalPaidSoFar,
            'remaining_due'     => $remainingDue,
            'banks'             => $banks,
        ]);
    }

    /* =====================
       Store Transaction
    ====================== */
    public function store()
    {
        // Role check
        if (!$this->canProcessPayment()) {
            setError('You are not authorized to process payments.');
            header("Location: {$GLOBALS['base_url']}/con-pending-payments");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setError('Invalid request method.');
            header("Location: {$GLOBALS['base_url']}/con-pending-payments");
            exit;
        }

        // --- Mandatory disbursement proof upload ---
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
                    $refType = $_POST['ref_type'] ?? '';
                    $refId   = $_POST['ref_id'] ?? 0;
                    header("Location: {$GLOBALS['base_url']}/con-process-payment/{$refType}/{$refId}");
                    exit;
                }
            }
        }

        if (empty($proofPaths)) {
            setError('Disbursement proof is required. Please upload at least one file.');
            $refType = $_POST['ref_type'] ?? '';
            $refId   = $_POST['ref_id'] ?? 0;
            header("Location: {$GLOBALS['base_url']}/con-process-payment/{$refType}/{$refId}");
            exit;
        }

        $refType     = $_POST['ref_type'] ?? '';
        $paymentType = $_POST['payment_type'] ?? 'Full';

        // For vendor_payment, map VPR payment_type to transaction payment_type
        if ($refType === 'vendor_payment') {
            $paymentType = match($_POST['vpr_payment_type'] ?? 'Final') {
                'Advance' => 'Advance',
                'Partial' => 'Partial',
                default   => 'Full',
            };
        }

        $amount = (float)($_POST['amount'] ?? 0);

        // Validate amount against remaining due
        $totalPaidSoFar = $this->txnModel->getTotalPaidForSource($refType, (int)($_POST['ref_id'] ?? 0));
        $sourceAmount   = $this->getSourceAmountFromPost($refType, (int)($_POST['ref_id'] ?? 0));
        $remainingDue   = max(0, $sourceAmount - $totalPaidSoFar);

        if ($amount > $remainingDue + 0.01) { // small float tolerance
            setError("Payment amount (৳" . number_format($amount, 2) . ") exceeds remaining due (৳" . number_format($remainingDue, 2) . ").");
            $refId = $_POST['ref_id'] ?? 0;
            header("Location: {$GLOBALS['base_url']}/con-process-payment/{$refType}/{$refId}");
            exit;
        }

        // Determine payment_type based on amount vs remaining
        if ($amount < $remainingDue - 0.01) {
            $paymentType = 'Partial';
        } else {
            $paymentType = 'Full';
        }

        $data = [
            'ref_type'           => $refType,
            'ref_id'             => (int)($_POST['ref_id'] ?? 0),
            'project_id'         => (int)($_POST['project_id'] ?? 0),
            'amount'             => $amount,
            'payment_type'       => $paymentType,
            'payment_method'     => $_POST['payment_method'] ?? 'Cash',
            'payment_date'       => $_POST['payment_date'] ?? date('Y-m-d'),
            'reference_no'       => trim($_POST['reference_no'] ?? ''),
            'notes'              => trim($_POST['notes'] ?? ''),
            'disbursement_proof' => json_encode($proofPaths),
            'processed_by'       => $this->userId,
        ];

        if (empty($data['ref_type']) || empty($data['ref_id']) || $data['amount'] <= 0) {
            setError('Invalid payment data. Please fill all required fields.');
            header("Location: {$GLOBALS['base_url']}/con-pending-payments");
            exit;
        }

        $txnId = $this->txnModel->create($data);

        if ($txnId) {
            setSuccess('Payment processed successfully! Transaction #' . $txnId);
            header("Location: {$GLOBALS['base_url']}/con-transaction/{$txnId}");
        } else {
            setError('Failed to process payment.');
            header("Location: {$GLOBALS['base_url']}/con-pending-payments");
        }
        exit;
    }

    /* =====================
       Show Transaction Detail
    ====================== */
    public function show($id)
    {
        $txn = $this->txnModel->find($id);

        if (!$txn) {
            setError('Transaction not found.');
            header("Location: {$GLOBALS['base_url']}/con-transactions");
            exit;
        }

        $this->view('phase2/financial-transactions/show-transaction', [
            'current_route' => 'con-transactions',
            'page_title'    => 'Transaction Receipt',
            'txn'           => $txn,
        ]);
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /** File upload handler for disbursement proof */
    private function handleFileUpload($file)
    {
        $allowedTypes = ['doc', 'docx', 'pdf', 'jpg', 'jpeg', 'png'];
        $maxSize      = 5 * 1024 * 1024; // 5MB per file

        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($fileExt, $allowedTypes)) {
            return ['success' => false, 'message' => "File '{$file['name']}': only DOC, DOCX, PDF, JPG, PNG allowed."];
        }

        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => "File '{$file['name']}' exceeds 5MB limit."];
        }

        $newFileName = 'con_payment_' . time() . '_' . uniqid() . '.' . $fileExt;
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

    /** Get source payable amount for validation */
    private function getSourceAmountFromPost($refType, $refId)
    {
        if ($refType === 'contractor_bill') {
            $stmt = $this->db->prepare("SELECT COALESCE(approved_amount, bill_amount) AS payable FROM contractor_bill_requests WHERE id = ?");
        } elseif ($refType === 'operational_fund') {
            $stmt = $this->db->prepare("SELECT COALESCE(approved_amount, amount) AS payable FROM operational_fund_requests WHERE id = ?");
        } elseif ($refType === 'vendor_payment') {
            $stmt = $this->db->prepare("SELECT net_payable AS payable FROM vendor_payment_requests WHERE id = ?");
        } else {
            return 0;
        }
        $stmt->bind_param('i', $refId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return (float)($row['payable'] ?? 0);
    }
}

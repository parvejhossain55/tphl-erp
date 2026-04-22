<?php
namespace Phase2;

use Controller;
use Con_VendorPaymentRequest;
use Con_Vendor;
use Con_PurchaseOrder;
use Con_FinancialTransaction;
use ApprovalRequest;
use Project;

class Con_VendorPaymentRequestController extends Controller
{
    protected $db;
    protected $model;
    protected $vendorModel;
    protected $projectModel;
    protected $poModel;
    protected $txnModel;
    protected $approvalModel;
    protected $userRole;
    protected $userId;

    public function __construct($db)
    {
        $this->db            = $db;
        $this->model         = new Con_VendorPaymentRequest($db);
        $this->vendorModel   = new Con_Vendor($db);
        $this->projectModel  = new Project($db);
        $this->poModel       = new Con_PurchaseOrder($db);
        $this->txnModel      = new Con_FinancialTransaction($db);
        $this->approvalModel = new ApprovalRequest($db);

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
        // Roles 1 (Admin), 3 (Super Admin), 6 (Accounts) see ALL requests
        // Other roles only see their own requests
        $filterUserId = null;
        if (!in_array($this->userRole, [1, 3, 6])) {
            $filterUserId = $this->userId;
        }

        $requests = $this->model->getAll($filterUserId);

        $this->view('phase2/vendor-payment-requests/payment-lists', [
            'current_route' => 'con-vendor-payment-requests',
            'page_title'    => 'Vendor Payment Requests',
            'requests'      => $requests,
            'userRole'      => $this->userRole,
        ]);
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    public function create()
    {
        $projects     = $this->projectModel->getAll();
        $vendors      = $this->vendorModel->getAll();
        $paymentTypes = ['Advance', 'Partial', 'Final'];

        // Fetch all purchase orders for initial load (AJAX will filter by project)
        $allPOs = $this->poModel->getAll();

        $this->view('phase2/vendor-payment-requests/create-payment', [
            'current_route' => 'con-vendor-payment-requests',
            'page_title'    => 'Create Payment Request',
            'projects'      => $projects,
            'vendors'       => $vendors,
            'payment_types' => $paymentTypes,
            'all_pos'       => $allPOs,
        ]);
    }

    // =========================================================================
    // STORE — auto-create approval_request
    // =========================================================================

    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId      = (int)($_SESSION['user']['id'] ?? 0);
        $vendorId    = (int)($_POST['vendor_id'] ?? 0);
        $paymentType = $_POST['payment_type'] ?? 'Final';
        $invoiceAmt  = (float)($_POST['invoice_amount'] ?? 0);
        // purchase_order_id is NOT NULL in DB — use 0 if not selected
        $poId        = (int)($_POST['purchase_order_id'] ?? 0);

        // Advance: no penalty deduction, net = invoice amount
        if ($paymentType === 'Advance') {
            $penaltyDeducted = 0.00;
            $netPayable      = $invoiceAmt;
        } else {
            $penaltyDeducted = $this->model->getPendingPenaltySum($vendorId);
            $netPayable      = max(0, $invoiceAmt - $penaltyDeducted);
        }

        $data = [
            'project_id'        => (int)($_POST['project_id'] ?? 0),
            'vendor_id'         => $vendorId,
            'purchase_order_id' => $poId,          // int (0 = no PO)
            'payment_type'      => $paymentType,
            'invoice_amount'    => $invoiceAmt,
            'penalty_deducted'  => $penaltyDeducted,
            'net_payable'       => $netPayable,
            'delivery_verified' => ($paymentType === 'Advance') ? 0 : (int)($_POST['delivery_verified'] ?? 0),
            'created_by'        => $userId,
        ];

        if (!$data['project_id'] || !$data['vendor_id'] || $data['invoice_amount'] <= 0) {
            setError('Project, Vendor, and a valid Amount are required.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-create");
            exit;
        }

        $id = $this->model->create($data);
        if ($id) {
            // Fetch project and vendor names for approval title
            $project = $this->db->query("SELECT name FROM projects WHERE id = " . (int)$data['project_id'])->fetch_assoc();
            $vendor  = $this->db->query("SELECT name FROM vendors WHERE id = " . (int)$vendorId)->fetch_assoc();

            // Auto-create approval_request — goes to management
            $this->approvalModel->create([
                'department'     => 'construction',
                'request_type'   => 'vendor_payment',
                'title'          => 'Vendor Payment — ' . ($vendor['name'] ?? '') . ' | ' . ($project['name'] ?? ''),
                'source_table'   => 'vendor_payment_requests',
                'source_id'      => $id,
                'requested_days' => 0,
                'requested_by'   => $userId,
                'approve_role'   => 'management',
                'meta_data'      => null,
                'request_amount' => $netPayable,
            ]);

            $msg = ($paymentType === 'Advance')
                ? "Advance Payment Request created. Amount: ৳ " . number_format($invoiceAmt, 2)
                : "Payment Request created. Penalty deducted: ৳ " . number_format($penaltyDeducted, 2);
            setSuccess($msg);
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
        } else {
            setError('Failed to create payment request.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-create");
        }
        exit;
    }

    // =========================================================================
    // SHOW — Enhanced single-page experience (like fund requests)
    // =========================================================================

    public function show($id)
    {
        $request = $this->model->find($id);
        if (!$request) {
            setError('Payment request not found.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-requests");
            exit;
        }

        // Fetch penalties linked to this request
        $penalties = [];
        $pStmt = $this->db->prepare("SELECT * FROM vendor_penalties WHERE payment_request_id = ?");
        $pStmt->bind_param('i', $id);
        $pStmt->execute();
        $res = $pStmt->get_result();
        if ($res) {
            $penalties = $res->fetch_all(MYSQLI_ASSOC);
        }

        // If Final/Partial: calculate total advance already paid for this PO
        $advancePaid = 0.00;
        if (!empty($request['purchase_order_id']) && $request['payment_type'] !== 'Advance') {
            $advancePaid = $this->model->getTotalAdvancePaid($request['purchase_order_id']);
        }

        // Fetch all payments for the same PO (for history)
        $poPayments = [];
        if (!empty($request['purchase_order_id'])) {
            $poPayments = $this->model->getByPurchaseOrder($request['purchase_order_id']);
        }

        // Get payment history (con_financial_transactions)
        $payments   = $this->txnModel->getByRef('vendor_payment', $id);
        $totalPaid  = $this->txnModel->getTotalPaidForSource('vendor_payment', $id);
        $payableAmt = (float)($request['approved_amount'] ?? $request['net_payable'] ?? 0);
        $dueAmount  = max(0, $payableAmt - $totalPaid);

        // Get linked approval_request for sync check
        $approvalReq = $this->getApprovalRequest($id);

        // Get banks for payment modal dropdown
        $banks = $this->db->query("SELECT id, bank_name, branch_name FROM banks WHERE status = 'active' ORDER BY bank_name")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/vendor-payment-requests/show-payment', [
            'current_route'     => 'con-vendor-payment-requests',
            'page_title'        => 'Payment Request Details',
            'request'           => $request,
            'penalties'         => $penalties,
            'advance_paid'      => $advancePaid,
            'po_payments'       => $poPayments,
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
    // APPROVE — Management (roles 1,3) with adjustable amount
    // =========================================================================

    public function approve($id)
    {
        if (!$this->canApprove()) {
            setError('You are not authorized to approve payment requests.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
            exit;
        }

        $request = $this->model->find($id);
        if (!$request || $request['status'] !== 'Submitted') {
            setError('Payment request is not in Submitted status.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
            exit;
        }

        $approvedAmount = (float)($_POST['approved_amount'] ?? $request['net_payable']);
        if ($approvedAmount <= 0) {
            $approvedAmount = (float)$request['net_payable'];
        }

        if ($this->model->approve($id, $this->userId, $approvedAmount)) {
            // Sync approval_requests table
            $this->syncApprovalRequest($id, 'approved');
            setSuccess('Payment Request Approved with amount ৳' . number_format($approvedAmount, 2));
        } else {
            setError('Failed to approve or request already processed.');
        }
        header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
        exit;
    }

    // =========================================================================
    // REJECT — Management (roles 1,3)
    // =========================================================================

    public function reject($id)
    {
        if (!$this->canApprove()) {
            setError('You are not authorized to reject payment requests.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
            exit;
        }

        if ($this->model->reject($id, $this->userId)) {
            // Sync approval_requests table
            $this->syncApprovalRequest($id, 'rejected');
            setSuccess('Payment Request Rejected.');
        } else {
            setError('Failed to reject request.');
        }
        header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
        exit;
    }

    // =========================================================================
    // ADD PAYMENT — Accounts (roles 1,3,6), creates con_financial_transaction
    // =========================================================================

    public function addPayment($id)
    {
        if (!$this->canProcessPayment()) {
            setError('You are not authorized to process payments.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
            exit;
        }

        $request = $this->model->find($id);
        if (!$request || !in_array($request['status'], ['Approved', 'Partially_Paid'])) {
            setError('Payment request is not in a payable status.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
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
                    header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
                    exit;
                }
            }
        }

        if (empty($proofPaths)) {
            setError('Disbursement proof is required. Please upload at least one file.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
            exit;
        }

        $amount = (float)($_POST['amount'] ?? 0);
        if ($amount <= 0) {
            setError('Payment amount must be greater than zero.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
            exit;
        }

        // Validate against remaining due
        $totalPaidSoFar = $this->txnModel->getTotalPaidForSource('vendor_payment', $id);
        $payableAmt     = (float)($request['approved_amount'] ?? $request['net_payable']);
        $remainingDue   = max(0, $payableAmt - $totalPaidSoFar);

        if ($amount > $remainingDue + 0.01) {
            setError("Payment amount (৳" . number_format($amount, 2) . ") exceeds remaining due (৳" . number_format($remainingDue, 2) . ").");
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
            exit;
        }

        // Determine payment type
        $paymentType = ($amount < $remainingDue - 0.01) ? 'Partial' : 'Full';

        $data = [
            'ref_type'           => 'vendor_payment',
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

        header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
        exit;
    }

    // =========================================================================
    // API: AJAX FETCH PENALTY
    // =========================================================================

    public function ajaxGetPenalty()
    {
        header('Content-Type: application/json');
        $vendorId = (int)($_GET['vendor_id'] ?? 0);
        $penalty  = $this->model->getPendingPenaltySum($vendorId);
        echo json_encode(['success' => true, 'penalty' => $penalty]);
        exit;
    }

    // =========================================================================
    // API: AJAX FETCH ADVANCE SUMMARY FOR A PO
    // =========================================================================

    public function ajaxGetAdvanceSummary()
    {
        header('Content-Type: application/json');

        $poId     = (int)($_GET['po_id'] ?? 0);
        $vendorId = (int)($_GET['vendor_id'] ?? 0);

        $advancePaid = $poId     ? $this->model->getTotalAdvancePaid($poId) : 0;
        $penalty     = $vendorId ? $this->model->getPendingPenaltySum($vendorId) : 0;

        echo json_encode([
            'success'      => true,
            'advance_paid' => $advancePaid,
            'penalty'      => $penalty,
        ]);
        exit;
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /** Get the linked approval_request for this vendor payment request */
    private function getApprovalRequest($requestId)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM approval_requests
             WHERE source_table = 'vendor_payment_requests' AND source_id = ?
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->bind_param('i', $requestId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /** Sync approval_requests status when approving/rejecting from this page */
    private function syncApprovalRequest($requestId, $newStatus)
    {
        $arStatus = ($newStatus === 'approved') ? 'approved' : 'rejected';

        $stmt = $this->db->prepare(
            "UPDATE approval_requests
             SET status = ?, approved_by = ?, approved_at = NOW()
             WHERE source_table = 'vendor_payment_requests' AND source_id = ? AND status = 'pending'"
        );
        $stmt->bind_param('sii', $arStatus, $this->userId, $requestId);
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

        $newFileName = 'con_vendor_payment_' . time() . '_' . uniqid() . '.' . $fileExt;
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

    // =========================================================================
    // AJAX — POs for a vendor (+project), with financial summary
    // GET: ?vendor_id=N&project_id=N
    // Returns JSON array of PO rows with received/due/advance info
    // =========================================================================

    public function ajaxGetPOsByVendor()
    {
        header('Content-Type: application/json');

        $vendorId  = (int)($_GET['vendor_id']  ?? 0);
        $projectId = (int)($_GET['project_id'] ?? 0);

        if (!$vendorId) {
            echo json_encode([]);
            exit;
        }

        // ── Step 1: Find all PO IDs that have at least one item from this vendor ─────
        if ($projectId) {
            $stmt = $this->db->prepare("
                SELECT DISTINCT po.id
                FROM purchase_orders po
                JOIN purchase_requests pr ON pr.id = po.request_id
                JOIN purchase_order_items poi ON poi.purchase_order_id = po.id
                WHERE poi.selected_vendor_id = ?
                  AND pr.project_id = ?
                ORDER BY po.id DESC
            ");
            $stmt->bind_param('ii', $vendorId, $projectId);
        } else {
            $stmt = $this->db->prepare("
                SELECT DISTINCT po.id
                FROM purchase_orders po
                JOIN purchase_order_items poi ON poi.purchase_order_id = po.id
                WHERE poi.selected_vendor_id = ?
                ORDER BY po.id DESC
            ");
            $stmt->bind_param('i', $vendorId);
        }

        if (!$stmt->execute()) {
            echo json_encode(['error' => 'Query failed: ' . $stmt->error]);
            exit;
        }

        $poIds = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'id');

        if (empty($poIds)) {
            echo json_encode([]);
            exit;
        }

        // ── Step 2: For each PO, fetch header + financial summary ─────────────────
        $result = [];
        foreach ($poIds as $poId) {
            // PO header info
            $hStmt = $this->db->prepare("
                SELECT po.id, po.status, po.created_at,
                       pr.project_id,
                       p.name AS project_name
                FROM purchase_orders po
                JOIN purchase_requests pr ON pr.id = po.request_id
                LEFT JOIN projects p ON p.id = pr.project_id
                WHERE po.id = ?
            ");
            $hStmt->bind_param('i', $poId);
            $hStmt->execute();
            $po = $hStmt->get_result()->fetch_assoc();
            if (!$po) continue;

            // Total order value (all items, not just this vendor)
            $vStmt = $this->db->prepare("
                SELECT COALESCE(SUM(quantity * unit_price), 0) AS total_order_value
                FROM purchase_order_items
                WHERE purchase_order_id = ?
            ");
            $vStmt->bind_param('i', $poId);
            $vStmt->execute();
            $totalValue = (float)$vStmt->get_result()->fetch_assoc()['total_order_value'];

            // Received amount (QC accepted items only)
            $rStmt = $this->db->prepare("
                SELECT COALESCE(SUM(mri.received_quantity * poi.unit_price), 0) AS received_amount
                FROM material_receiving_items mri
                JOIN material_receiving mr ON mr.id = mri.receiving_id
                JOIN purchase_order_items poi ON poi.id = mri.purchase_order_item_id
                WHERE mr.purchase_order_id = ?
                  AND mr.overall_status IN ('Accepted','Partially Accepted')
                  AND mri.status IN ('Accepted','Partially Accepted')
            ");
            $rStmt->bind_param('i', $poId);
            $rStmt->execute();
            $receivedAmount = (float)$rStmt->get_result()->fetch_assoc()['received_amount'];

            // Advance already paid for this PO
            $advancePaid = $this->model->getTotalAdvancePaid($poId);
            $due         = max(0, $receivedAmount - $advancePaid);

            $result[] = [
                'id'                => (int)$po['id'],
                'label'             => 'PO-' . $po['id'] . ' | ' . ($po['project_name'] ?? 'N/A') . ' | ' . $po['status'],
                'project_name'      => $po['project_name'] ?? '',
                'status'            => $po['status'] ?? '',
                'total_order_value' => round($totalValue,    2),
                'received_amount'   => round($receivedAmount, 2),
                'advance_paid'      => round($advancePaid,    2),
                'due_amount'        => round($due,            2),
            ];
        }

        echo json_encode($result);
        exit;
    }
}

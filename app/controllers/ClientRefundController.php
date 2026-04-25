<?php
require_once __DIR__ . '/../models/ClientRefund.php';
require_once __DIR__ . '/../models/CashoutRequest.php';
require_once __DIR__ . '/../models/ApprovalRequest.php';
require_once __DIR__ . '/../views/accounts/AccountMailService.php';

class ClientRefundController extends Controller
{
    protected $db;
    protected $model;
    protected $cashoutModel;
    protected $approvalModel;
    protected $mailService;
    protected $userRole;
    protected $userId;

    public function __construct($db)
    {
        $this->db            = $db;
        $this->model         = new ClientRefund($db);
        $this->cashoutModel  = new CashoutRequest($db);
        $this->approvalModel = new ApprovalRequest($db);
        $this->mailService   = new AccountMailService($db);

        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->userId   = $_SESSION['user']['id'] ?? null;
        $this->userRole = isset($_SESSION['user']['role_id']) ? (int)$_SESSION['user']['role_id'] : null;

        if (!$this->userId) {
            setError("Please login first.");
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }
    }

    /** Roles 1 (Admin), 3 (Super Admin) can approve */
    private function canApprove()
    {
        return in_array($this->userRole, [1, 3]);
    }

    /** Roles 1, 3, 6 (Accounts) can create refund requests */
    private function canCreateRefund()
    {
        return in_array($this->userRole, [1, 3, 6]);
    }

    // =========================================================================
    // LIST
    // =========================================================================

    public function index()
    {
        $filterUserId = null;
        if (!in_array($this->userRole, [1, 3, 6])) {
            $filterUserId = $this->userId;
        }

        $refunds = $this->model->getAll($filterUserId);

        $this->view('accounts/refunds/refund-list', [
            'current_route' => 'client-refunds',
            'page_title'    => 'Client Refund Requests',
            'refunds'       => $refunds,
            'userRole'      => $this->userRole,
            'canCreate'     => $this->canCreateRefund(),
        ]);
    }

    // =========================================================================
    // CREATE — show form pre-filled with booking data
    // =========================================================================

    public function create($bookingId)
    {
        if (!$this->canCreateRefund()) {
            setError('You are not authorized to create refund requests.');
            header("Location: {$GLOBALS['base_url']}/client-refunds");
            exit;
        }

        $booking = $this->model->getBookingDetails($bookingId);
        if (!$booking) {
            setError('Booking not found.');
            header("Location: {$GLOBALS['base_url']}/client-refunds");
            exit;
        }

        if ($booking['booking_status'] !== 'booked') {
            setError('Only active (booked) bookings can be refunded.');
            header("Location: {$GLOBALS['base_url']}/client-refunds");
            exit;
        }

        // Check if there's already a non-rejected refund for this booking
        if ($this->model->hasActiveRefund($bookingId)) {
            setError('A refund request already exists for this booking.');
            header("Location: {$GLOBALS['base_url']}/client-refunds");
            exit;
        }

        $totalPaid = $this->model->getTotalVerifiedPaid($bookingId);
        $payments  = $this->model->getPaymentsForBooking($bookingId);

        $this->view('accounts/refunds/create-refund', [
            'current_route' => 'client-refunds',
            'page_title'    => 'Create Refund Request',
            'booking'       => $booking,
            'total_paid'    => $totalPaid,
            'payments'      => $payments,
        ]);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store()
    {
        if (!$this->canCreateRefund()) {
            setError('You are not authorized.');
            header("Location: {$GLOBALS['base_url']}/client-refunds");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/client-refunds");
            exit;
        }

        $bookingId       = (int)($_POST['booking_id'] ?? 0);
        $refundAmount = (float)($_POST['refund_amount'] ?? 0);
        $reason          = trim($_POST['reason'] ?? '');

        $booking = $this->model->getBookingDetails($bookingId);
        if (!$booking || !in_array($booking['booking_status'], ['booked', 'completed'])) {
            setError('Invalid or inactive booking.');
            header("Location: {$GLOBALS['base_url']}/clients-cash-in-history/{$bookingId}");
            exit;
        }

        if ($this->model->hasActiveRefund($bookingId)) {
            setError('A refund request already exists for this booking.');
            header("Location: {$GLOBALS['base_url']}/clients-cash-in-history/{$bookingId}");
            exit;
        }

        $totalPaid    = $this->model->getTotalVerifiedPaid($bookingId);
        
        if ($totalPaid <= 0) {
            setError('No verified payments found for this booking.');
            header("Location: {$GLOBALS['base_url']}/clients-cash-in-history/{$bookingId}");
            exit;
        }

        if ($refundAmount <= 0 || $refundAmount > $totalPaid) {
            setError('Refund amount must be greater than zero and cannot exceed total paid.');
            header("Location: {$GLOBALS['base_url']}/clients-cash-in-history/{$bookingId}");
            exit;
        }
        
        $retentionAmount = max(0, $totalPaid - $refundAmount);

        // Handle document upload
        $documentPath = null;
        if (!empty($_FILES['document']['name'])) {
            $allowedTypes = ['doc', 'docx', 'pdf', 'jpg', 'jpeg', 'png'];
            $maxSize      = 5 * 1024 * 1024; // 5MB
            $fileExt      = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));

            if (!in_array($fileExt, $allowedTypes)) {
                setError('Only DOC, DOCX, PDF, JPG, PNG allowed for document.');
                header("Location: {$GLOBALS['base_url']}/clients-cash-in-history/{$bookingId}");
                exit;
            }
            if ($_FILES['document']['size'] > $maxSize) {
                setError('Document exceeds 5MB limit.');
                header("Location: {$GLOBALS['base_url']}/clients-cash-in-history/{$bookingId}");
                exit;
            }
            $newFileName = 'refund_' . time() . '_' . uniqid() . '.' . $fileExt;
            $uploadPath  = 'uploads/refunds/' . $newFileName;
            $fullPath    = __DIR__ . '/../../public/' . $uploadPath;

            if (!is_dir(dirname($fullPath))) {
                mkdir(dirname($fullPath), 0777, true);
            }

            if (move_uploaded_file($_FILES['document']['tmp_name'], $fullPath)) {
                $documentPath = $uploadPath;
            } else {
                setError('Failed to upload document.');
                header("Location: {$GLOBALS['base_url']}/clients-cash-in-history/{$bookingId}");
                exit;
            }
        }

        $data = [
            'booking_id'       => $bookingId,
            'lead_id'          => (int)$booking['lead_id'],
            'project_id'       => (int)$booking['project_id'],
            'flat_id'          => (int)$booking['flat_id'],
            'total_paid'       => $totalPaid,
            'retention_amount' => $retentionAmount,
            'refund_amount'    => $refundAmount,
            'reason'           => $reason,
            'document'         => $documentPath,
            'requested_by'     => $this->userId,
        ];

        $refundId = $this->model->create($data);

        if ($refundId) {
            // Create approval_request for management dashboard
            $this->approvalModel->create([
                'department'     => 'accounts',
                'request_type'   => 'client_refund',
                'title'          => 'Client Refund — ' . ($booking['client_name'] ?? '') . ' | ' . ($booking['flat_name'] ?? '') . ' | ' . ($booking['project_name'] ?? ''),
                'source_table'   => 'client_refund_requests',
                'source_id'      => $refundId,
                'requested_days' => 0,
                'requested_by'   => $this->userId,
                'approve_role'   => 'management',
                'meta_data'      => null,
                'request_amount' => $refundAmount,
            ]);

            // Send email notification
            try {
                $this->mailService->sendRefundOnSubmission([
                    'client_name'      => $booking['client_name'] ?? '',
                    'flat_name'        => $booking['flat_name'] ?? '',
                    'project_name'     => $booking['project_name'] ?? '',
                    'total_paid'       => $totalPaid,
                    'retention_amount' => $retentionAmount,
                    'refund_amount'    => $refundAmount,
                    'reason'           => $reason,
                    'requested_by'     => $this->userId,
                ]);
            } catch (\Exception $e) {
                error_log("Refund email (submission) failed: " . $e->getMessage());
            }

            setSuccess('Refund request submitted. Refund Amount: ৳' . number_format($refundAmount, 2));
            header("Location: {$GLOBALS['base_url']}/client-refund/{$refundId}");
        } else {
            setError('Failed to create refund request.');
            header("Location: {$GLOBALS['base_url']}/client-refund-create/{$bookingId}");
        }
        exit;
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    public function show($id)
    {
        $refund = $this->model->find($id);
        if (!$refund) {
            setError('Refund request not found.');
            header("Location: {$GLOBALS['base_url']}/client-refunds");
            exit;
        }

        $payments = $this->model->getPaymentsForBooking($refund['booking_id']);

        // Get linked cashout request if exists
        $cashoutRequest = null;
        if (!empty($refund['cashout_request_id'])) {
            $stmt = $this->db->prepare("SELECT * FROM cashout_requests WHERE id = ?");
            $stmt->bind_param('i', $refund['cashout_request_id']);
            $stmt->execute();
            $cashoutRequest = $stmt->get_result()->fetch_assoc();

            // Fetch total paid from cashout_payments
            if ($cashoutRequest) {
                $paidStmt = $this->db->prepare(
                    "SELECT COALESCE(SUM(amount), 0) AS paid_amount FROM cashout_payments WHERE cashout_request_id = ?"
                );
                $paidStmt->bind_param('i', $refund['cashout_request_id']);
                $paidStmt->execute();
                $paidRow = $paidStmt->get_result()->fetch_assoc();
                $cashoutRequest['paid_amount'] = (float)($paidRow['paid_amount'] ?? 0);
            }
        }

        // Get approval_request
        $approvalReq = null;
        $arStmt = $this->db->prepare(
            "SELECT * FROM approval_requests
             WHERE source_table = 'client_refund_requests' AND source_id = ?
             ORDER BY id DESC LIMIT 1"
        );
        $arStmt->bind_param('i', $id);
        $arStmt->execute();
        $approvalReq = $arStmt->get_result()->fetch_assoc();

        $this->view('accounts/refunds/show-refund', [
            'current_route'    => 'client-refunds',
            'page_title'       => 'Refund Request Details',
            'refund'           => $refund,
            'payments'         => $payments,
            'cashout_request'  => $cashoutRequest,
            'approval_request' => $approvalReq,
            'canApprove'       => $this->canApprove(),
            'userRole'         => $this->userRole,
        ]);
    }

    // =========================================================================
    // APPROVE — creates cashout_request automatically
    // =========================================================================

    public function approve($id)
    {
        if (!$this->canApprove()) {
            setError('You are not authorized to approve refund requests.');
            header("Location: {$GLOBALS['base_url']}/client-refund/{$id}");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/client-refund/{$id}");
            exit;
        }

        $refund = $this->model->find($id);
        if (!$refund || $refund['status'] !== 'pending') {
            setError('Refund request is not in pending status.');
            header("Location: {$GLOBALS['base_url']}/client-refund/{$id}");
            exit;
        }

        $approvedAmount = isset($_POST['approved_amount']) ? (float)$_POST['approved_amount'] : (float)$refund['refund_amount'];

        // Step 1: Approve refund (cancels booking, releases flat, cancels EMIs)
        if (!$this->model->approve($id, $this->userId, $approvedAmount)) {
            setError('Failed to approve refund request.');
            header("Location: {$GLOBALS['base_url']}/client-refund/{$id}");
            exit;
        }

        // Step 2: Auto-create a cashout_request for the refund amount
        $cashoutData = [
            'requested_by'    => $refund['requested_by'],
            'purpose_paid_to' => 'Client Refund — ' . ($refund['client_name'] ?? '') . ' | ' . ($refund['flat_name'] ?? '') . ' | ' . ($refund['project_name'] ?? ''),
            'amount'          => $approvedAmount,
            'description'     => 'Auto-generated from Client Refund #' . $id . '. Reason: ' . ($refund['reason'] ?? 'N/A'),
            'payment_method'  => 'cash',
            'project_id'      => $refund['project_id'],
        ];

        $cashoutResult = $this->cashoutModel->createRequest($cashoutData);

        if ($cashoutResult['success'] && !empty($cashoutResult['id'])) {
            $cashoutId = $cashoutResult['id'];

            // Link cashout to refund
            $this->model->linkCashoutRequest($id, $cashoutId);

            // Auto-approve the cashout (since management already approved the refund)
            $stmt = $this->db->prepare(
                "UPDATE cashout_requests
                 SET status = 'approved', approved_by = ?, approved_at = NOW(), approved_amount = amount
                 WHERE id = ?"
            );
            $stmt->bind_param('ii', $this->userId, $cashoutId);
            $stmt->execute();

            // Sync approval_requests for the refund
            $arStmt = $this->db->prepare(
                "UPDATE approval_requests
                 SET status = 'approved', approved_by = ?, approved_at = NOW()
                 WHERE source_table = 'client_refund_requests' AND source_id = ? AND status = 'pending'"
            );
            $arStmt->bind_param('ii', $this->userId, $id);
            $arStmt->execute();
        }

        // Step 3: Send email notification
        try {
            $this->mailService->sendRefundOnApproval([
                'id'               => $id,
                'client_name'      => $refund['client_name'] ?? '',
                'flat_name'        => $refund['flat_name'] ?? '',
                'project_name'     => $refund['project_name'] ?? '',
                'refund_amount'    => $refund['refund_amount'],
                'retention_amount' => $refund['retention_amount'],
                'requested_by'     => $refund['requested_by'],
            ], $this->userId);
        } catch (\Exception $e) {
            error_log("Refund email (approval) failed: " . $e->getMessage());
        }

        setSuccess('Refund approved! Cashout request created for ৳' . number_format($refund['refund_amount'], 2) . '. Booking cancelled, flat released.');
        header("Location: {$GLOBALS['base_url']}/client-refund/{$id}");
        exit;
    }

    // =========================================================================
    // REJECT
    // =========================================================================

    public function reject($id)
    {
        if (!$this->canApprove()) {
            setError('You are not authorized to reject refund requests.');
            header("Location: {$GLOBALS['base_url']}/client-refund/{$id}");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/client-refund/{$id}");
            exit;
        }

        if ($this->model->reject($id, $this->userId)) {
            // Sync approval_requests
            $arStmt = $this->db->prepare(
                "UPDATE approval_requests
                 SET status = 'rejected', approved_by = ?, approved_at = NOW()
                 WHERE source_table = 'client_refund_requests' AND source_id = ? AND status = 'pending'"
            );
            $arStmt->bind_param('ii', $this->userId, $id);
            $arStmt->execute();

            setSuccess('Refund request rejected.');
        } else {
            setError('Failed to reject refund request.');
        }

        header("Location: {$GLOBALS['base_url']}/client-refund/{$id}");
        exit;
    }
}

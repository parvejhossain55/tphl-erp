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

    /** Roles 1 (Admin), 3 (Super Admin), 9 (Sales Team Head), 20 (Admin & Operation head), 33 (Chief Officer) can approve */
    private function canApprove()
    {
        return in_array($this->userRole, [1, 3, 9, 20, 33]);
    }

    /** Roles 1 (Admin), 3 (Super Admin), 4 (Sales Exec), 5 (Sales Mgr), 6, 10, 30 (Accounts) can create refund requests */
    private function canCreateRefund()
    {
        return in_array($this->userRole, [1, 3, 4, 5, 6, 10, 30]);
    }

    // =========================================================================
    // LIST
    // =========================================================================

    public function index()
    {
        $filterUserId = null;
        if (!in_array($this->userRole, [1, 3, 6, 30])) {
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

        if (!in_array($booking['booking_status'], ['booked', 'completed', 'refunded'])) {
            setError('This booking cannot be refunded (status: ' . $booking['booking_status'] . ').');
            header("Location: {$GLOBALS['base_url']}/client-refunds");
            exit;
        }

        // Check if there's already a non-rejected refund for this booking
        $existingRefund = null;
        $rfStmt = $this->db->prepare("SELECT * FROM client_refund_requests WHERE booking_id = ? AND status != 'rejected' ORDER BY id DESC LIMIT 1");
        if ($rfStmt) {
            $rfStmt->bind_param("i", $bookingId);
            $rfStmt->execute();
            $existingRefund = $rfStmt->get_result()->fetch_assoc();
        }

        if ($existingRefund && $existingRefund['status'] === 'refunded') {
            setError('This refund has already been paid and completed.');
            header("Location: {$GLOBALS['base_url']}/clients-cash-in-history-view/{$bookingId}");
            exit;
        }

        $totalPaid = $this->model->getTotalVerifiedPaid($bookingId);
        $payments  = $this->model->getPaymentsForBooking($bookingId);

        $isAccountsPayout = in_array($this->userRole, [1, 3, 6, 10, 30]) && ($booking['booking_status'] === 'refunded' || ($existingRefund && in_array($existingRefund['status'], ['pending', 'approved'])));

        $this->view('accounts/refunds/create-refund', [
            'current_route'      => 'client-refunds',
            'page_title'         => $isAccountsPayout ? 'Process Refund Payout' : 'Create Refund Request',
            'booking'            => $booking,
            'total_paid'         => $totalPaid,
            'payments'           => $payments,
            'existing_refund'    => $existingRefund,
            'is_accounts_payout' => $isAccountsPayout,
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
        $refundAmount    = (float)($_POST['refund_amount'] ?? 0);
        $reason          = trim($_POST['reason'] ?? '');
        $paymentMethod   = trim($_POST['payment_method'] ?? 'cash');
        $chequeNo        = trim($_POST['cheque_no'] ?? '');

        $booking = $this->model->getBookingDetails($bookingId);
        if (!$booking || !in_array($booking['booking_status'], ['booked', 'completed', 'refunded'])) {
            setError('Invalid or inactive booking.');
            header("Location: {$GLOBALS['base_url']}/clients-cash-in-history-view/{$bookingId}");
            exit;
        }

        // Check if there is an existing refund record
        $existingRefund = null;
        $checkStmt = $this->db->prepare("SELECT * FROM client_refund_requests WHERE booking_id = ? AND status != 'rejected' ORDER BY id DESC LIMIT 1");
        if ($checkStmt) {
            $checkStmt->bind_param("i", $bookingId);
            $checkStmt->execute();
            $existingRefund = $checkStmt->get_result()->fetch_assoc();
        }

        if ($existingRefund && $existingRefund['status'] === 'refunded') {
            setError('This refund has already been paid and completed.');
            header("Location: {$GLOBALS['base_url']}/clients-cash-in-history-view/{$bookingId}");
            exit;
        }

        $totalPaid = $this->model->getTotalVerifiedPaid($bookingId);

        if ($refundAmount < 0 || $refundAmount > $totalPaid) {
            setError('Refund amount cannot be negative or exceed total paid.');
            header("Location: {$GLOBALS['base_url']}/client-refund-create/{$bookingId}");
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
                header("Location: {$GLOBALS['base_url']}/client-refund-create/{$bookingId}");
                exit;
            }
            if ($_FILES['document']['size'] > $maxSize) {
                setError('Document exceeds 5MB limit.');
                header("Location: {$GLOBALS['base_url']}/client-refund-create/{$bookingId}");
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
                header("Location: {$GLOBALS['base_url']}/client-refund-create/{$bookingId}");
                exit;
            }
        } elseif ($existingRefund && !empty($existingRefund['document'])) {
            $documentPath = $existingRefund['document'];
        }

        // Determine if Accounts is completing payout
        $isAccounts = in_array($this->userRole, [1, 3, 6, 10, 30]);

        $this->db->begin_transaction();
        try {
            $refundId = null;

            if ($existingRefund) {
                $refundId = (int)$existingRefund['id'];
                $newStatus = $isAccounts ? 'refunded' : 'pending';
                $refundedAt = $isAccounts ? date('Y-m-d H:i:s') : null;

                $upStmt = $this->db->prepare("UPDATE client_refund_requests 
                    SET total_paid = ?, retention_amount = ?, refund_amount = ?, reason = ?, document = ?, 
                        status = ?, refunded_at = ?, approved_by = IFNULL(approved_by, ?) 
                    WHERE id = ?");
                $upStmt->bind_param("dddssssii", $totalPaid, $retentionAmount, $refundAmount, $reason, $documentPath, $newStatus, $refundedAt, $this->userId, $refundId);
                $upStmt->execute();
            } else {
                $newStatus = $isAccounts ? 'refunded' : 'pending';
                $refundedAt = $isAccounts ? date('Y-m-d H:i:s') : null;

                $inStmt = $this->db->prepare("INSERT INTO client_refund_requests 
                    (booking_id, lead_id, project_id, flat_id, total_paid, retention_amount, refund_amount, reason, document, requested_by, status, refunded_at, approved_by, approved_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $leadId = (int)$booking['lead_id'];
                $projId = (int)$booking['project_id'];
                $flatId = (int)$booking['flat_id'];
                $inStmt->bind_param("iiiidddssissi", $bookingId, $leadId, $projId, $flatId, $totalPaid, $retentionAmount, $refundAmount, $reason, $documentPath, $this->userId, $newStatus, $refundedAt, $this->userId);
                $inStmt->execute();
                $refundId = $inStmt->insert_id;
            }

            // If Accounts processed the payout, record the refund deduction in lead_payments
            if ($isAccounts && $refundAmount > 0) {
                // Release flat if not yet released
                if (!empty($booking['flat_id'])) {
                    $flatId = (int)$booking['flat_id'];
                    $fStmt = $this->db->prepare("UPDATE flats SET status = 'available', is_booked = 0 WHERE id = ?");
                    $fStmt->bind_param("i", $flatId);
                    $fStmt->execute();
                }

                // Ensure booking is refunded
                $lbStmt = $this->db->prepare("UPDATE lead_bookings SET booking_status = 'refunded', landshare_status = 'cancelled' WHERE id = ?");
                $lbStmt->bind_param("i", $bookingId);
                $lbStmt->execute();

                // Cancel pending EMIs
                $emiStmt = $this->db->prepare("UPDATE emi_schedules SET status = 'cancelled' WHERE lead_booking_id = ? AND status IN ('pending', 'missed')");
                $emiStmt->bind_param("i", $bookingId);
                $emiStmt->execute();

                // Insert negative refund entry in lead_payments to reduce cash in
                $negAmount = -abs($refundAmount);
                $txnRef = 'Refund Payout #' . $refundId . ($chequeNo !== '' ? ' (Ref: ' . $chequeNo . ')' : '');

                $payStmt = $this->db->prepare("INSERT INTO lead_payments 
                    (booking_id, payment_type, amount, payment_method, cheque_no, payment_status, verification_status, verified_by, payment_date, transaction_ref, created_by) 
                    VALUES (?, 'refund', ?, ?, ?, 'paid', 'verified', ?, CURDATE(), ?, ?)");
                $payStmt->bind_param("idssisi", $bookingId, $negAmount, $paymentMethod, $chequeNo, $this->userId, $txnRef, $this->userId);
                $payStmt->execute();

                // Update approval_requests to approved
                $arStmt = $this->db->prepare("UPDATE approval_requests SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE source_table = 'client_refund_requests' AND source_id = ?");
                $arStmt->bind_param("ii", $this->userId, $refundId);
                $arStmt->execute();
            } else {
                // Create approval request if not exists
                $arCheck = $this->db->prepare("SELECT id FROM approval_requests WHERE source_table = 'client_refund_requests' AND source_id = ? LIMIT 1");
                $arCheck->bind_param("i", $refundId);
                $arCheck->execute();
                if (!$arCheck->get_result()->fetch_assoc()) {
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
                }
            }

            $this->db->commit();

            if ($isAccounts) {
                setSuccess('Refund payout completed! ৳' . number_format($refundAmount, 2) . ' disbursed to client and deducted from cash-in.');
                header("Location: {$GLOBALS['base_url']}/clients-cash-in-history-view/{$bookingId}");
            } else {
                setSuccess('Refund request submitted for approval. Refund Amount: ৳' . number_format($refundAmount, 2));
                header("Location: {$GLOBALS['base_url']}/client-refund/{$refundId}");
            }
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Refund store failed: " . $e->getMessage());
            setError('Failed to process refund: ' . $e->getMessage());
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

                // Fetch individual payments for the view
                $payStmt = $this->db->prepare(
                    "SELECT cp.*, u.full_name as added_by_name, r.name as added_by_role
                     FROM cashout_payments cp
                     LEFT JOIN users u ON cp.created_by = u.id
                     LEFT JOIN roles r ON u.role_id = r.id
                     WHERE cp.cashout_request_id = ?
                     ORDER BY cp.payment_date DESC, cp.id DESC"
                );
                $payStmt->bind_param('i', $refund['cashout_request_id']);
                $payStmt->execute();
                $cashoutPayments = $payStmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
            'cashout_payments' => $cashoutPayments ?? [],
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

            // Sync approval_requests for the cashout
            $arCashoutStmt = $this->db->prepare(
                "UPDATE approval_requests
                 SET status = 'approved', approved_by = ?, approved_at = NOW()
                 WHERE source_table = 'cashout_requests' AND source_id = ? AND status = 'pending'"
            );
            $arCashoutStmt->bind_param('ii', $this->userId, $cashoutId);
            $arCashoutStmt->execute();
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

    // =========================================================================
    // DELETE
    // =========================================================================

    public function delete($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/client-refunds");
            exit;
        }

        $refund = $this->model->find($id);
        if (!$refund) {
            setError('Refund request not found.');
            header("Location: {$GLOBALS['base_url']}/client-refunds");
            exit;
        }

        // Only the requester can delete it, and only if it's pending
        if ($refund['requested_by'] != $this->userId) {
            setError('You can only delete your own refund requests.');
            header("Location: {$GLOBALS['base_url']}/client-refunds");
            exit;
        }

        if ($refund['status'] !== 'pending') {
            setError('Only pending refund requests can be deleted.');
            header("Location: {$GLOBALS['base_url']}/client-refunds");
            exit;
        }

        if ($this->model->delete($id)) {
            // Also delete the approval request
            $arStmt = $this->db->prepare(
                "DELETE FROM approval_requests
                 WHERE source_table = 'client_refund_requests' AND source_id = ?"
            );
            $arStmt->bind_param('i', $id);
            $arStmt->execute();

            setSuccess('Refund request deleted successfully.');
        } else {
            setError('Failed to delete refund request.');
        }

        header("Location: {$GLOBALS['base_url']}/client-refunds");
        exit;
    }
}

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
        $userRoleId = (int)($this->userRole ?? 0);
        $userSecRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
        $userPriRoleId = (int)($_SESSION['user']['primary_role_id'] ?? 0);
        $allUserRoles = array_filter([$userRoleId, $userSecRoleId, $userPriRoleId]);
        $sessionRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));

        if (count(array_intersect($allUserRoles, [1, 3, 9, 20, 32, 33])) > 0) {
            return true;
        }
        if (strpos($sessionRoleName, 'admin') !== false || strpos($sessionRoleName, 'management') !== false || strpos($sessionRoleName, 'operation head') !== false) {
            return true;
        }
        return false;
    }

    /** Roles 1 (Admin), 3 (Super Admin), 4 (Sales Exec), 5 (Sales Mgr), 6, 7 (Clients Experiment), 10, 30 (Accounts), 37 (Credit Recovery) can create refund requests */
    private function canCreateRefund()
    {
        return in_array($this->userRole, [1, 3, 4, 5, 6, 7, 10, 30, 37]);
    }

    // =========================================================================
    // LIST
    // =========================================================================

    public function index()
    {
        $filterUserId = null;
        if (!in_array($this->userRole, [1, 3, 6, 7, 30, 37])) {
            $filterUserId = $this->userId;
        }

        $filters = [
            'search'     => trim($_GET['search'] ?? ''),
            'project_id' => trim($_GET['project_id'] ?? 'all'),
            'status'     => trim($_GET['status'] ?? 'all'),
            'date_from'  => trim($_GET['date_from'] ?? ''),
            'date_to'    => trim($_GET['date_to'] ?? ''),
        ];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(5, min(100, (int)($_GET['per_page'] ?? 15)));
        $offset = ($page - 1) * $perPage;

        // Check if AJAX request
        $isAjax = isset($_GET['ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        $paginated = $this->model->getFilteredRefunds($filterUserId, $filters, $perPage, $offset);
        $refunds = $paginated['data'];
        $totalRecords = $paginated['total'];
        $totalPages = (int)ceil($totalRecords / $perPage);

        $summaryStats = $this->model->getSummaryStats($filterUserId);

        // Fetch active projects for filter dropdown
        $projects = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

        // Fetch company bank accounts for quick payouts
        $companyBanks = $this->db->query("SELECT id, bank_name, account_name, account_number FROM company_bank_accounts WHERE status = 'active' ORDER BY is_default DESC, bank_name ASC")->fetch_all(MYSQLI_ASSOC);
        $allBangladeshBanks = function_exists('getBangladeshBanks') ? getBangladeshBanks() : [];

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success'       => true,
                'data'          => $refunds,
                'total'         => $totalRecords,
                'page'          => $page,
                'per_page'      => $perPage,
                'total_pages'   => $totalPages,
                'summary_stats' => $summaryStats,
                'user_role'     => $this->userRole,
                'user_id'       => $this->userId,
                'base_url'      => $GLOBALS['base_url'],
            ]);
            exit;
        }

        $this->view('accounts/refunds/refund-list', [
            'current_route'       => 'client-refunds',
            'page_title'          => 'Client Refund Requests & Statement Report',
            'refunds'             => $refunds,
            'totalRecords'        => $totalRecords,
            'page'                => $page,
            'perPage'             => $perPage,
            'totalPages'          => $totalPages,
            'filters'             => $filters,
            'summaryStats'        => $summaryStats,
            'projects'            => $projects,
            'companyBanks'        => $companyBanks,
            'allBangladeshBanks'  => $allBangladeshBanks,
            'userRole'            => $this->userRole,
            'canCreate'           => $this->canCreateRefund(),
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
            'coaList'            => getCOAExpenseList($this->db)
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
        $accountId       = !empty($_POST['account_id']) ? (int)$_POST['account_id'] : null;

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

        $this->db->begin_transaction();
        try {
            $refundId = null;

            if ($existingRefund) {
                $refundId = (int)$existingRefund['id'];

                $upStmt = $this->db->prepare("UPDATE client_refund_requests 
                    SET total_paid = ?, retention_amount = ?, refund_amount = ?, reason = ?, document = IFNULL(?, document), 
                        status = 'pending', approved_by = NULL, approved_at = NULL, refunded_at = NULL, account_id = ? 
                    WHERE id = ?");
                $upStmt->bind_param("dddssii", $totalPaid, $retentionAmount, $refundAmount, $reason, $documentPath, $accountId, $refundId);
                $upStmt->execute();
            } else {
                $inStmt = $this->db->prepare("INSERT INTO client_refund_requests 
                    (booking_id, lead_id, project_id, flat_id, total_paid, retention_amount, refund_amount, reason, document, requested_by, status, account_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
                $leadId = (int)$booking['lead_id'];
                $projId = (int)$booking['project_id'];
                $flatId = (int)$booking['flat_id'];
                $inStmt->bind_param("iiiidddssii", $bookingId, $leadId, $projId, $flatId, $totalPaid, $retentionAmount, $refundAmount, $reason, $documentPath, $this->userId, $accountId);
                $inStmt->execute();
                $refundId = $inStmt->insert_id;
            }

            // Ensure flat is available and booking is cancelled
            if (!empty($booking['flat_id'])) {
                $flatId = (int)$booking['flat_id'];
                $fStmt = $this->db->prepare("UPDATE flats SET status = 'available', is_booked = 0 WHERE id = ?");
                $fStmt->bind_param("i", $flatId);
                $fStmt->execute();
            }

            $lbStmt = $this->db->prepare("UPDATE lead_bookings SET booking_status = 'refunded', landshare_status = 'cancelled' WHERE id = ?");
            $lbStmt->bind_param("i", $bookingId);
            $lbStmt->execute();

            // Cancel pending EMIs
            $emiStmt = $this->db->prepare("UPDATE emi_schedules SET status = 'cancelled' WHERE lead_booking_id = ? AND status IN ('pending', 'missed')");
            $emiStmt->bind_param("i", $bookingId);
            $emiStmt->execute();

            // Create or update approval request for Management & Role 20
            $arCheck = $this->db->prepare("SELECT id FROM approval_requests WHERE source_table = 'client_refund_requests' AND source_id = ? LIMIT 1");
            $arCheck->bind_param("i", $refundId);
            $arCheck->execute();
            $existingAr = $arCheck->get_result()->fetch_assoc();

            if ($existingAr) {
                $arUpdate = $this->db->prepare("UPDATE approval_requests SET request_amount = ?, status = 'pending', approved_by = NULL, approved_at = NULL WHERE id = ?");
                $arUpdate->bind_param("di", $refundAmount, $existingAr['id']);
                $arUpdate->execute();
            } else {
                $this->approvalModel->create([
                    'department'     => 'accounts',
                    'request_type'   => 'client_refund',
                    'title'          => 'Client Refund — ' . ($booking['client_name'] ?? '') . ' | ' . ($booking['flat_name'] ?? '') . ' | ' . ($booking['project_name'] ?? ''),
                    'source_table'   => 'client_refund_requests',
                    'source_id'      => $refundId,
                    'requested_days' => 0,
                    'requested_by'   => $this->userId,
                    'approve_role'   => ($retentionAmount > 0) ? 'management' : 'accounts_retention',
                    'meta_data'      => null,
                    'request_amount' => $refundAmount,
                ]);
            }

            $this->db->commit();

            setSuccess('Refund payment request of ৳' . number_format($refundAmount, 2) . ' (Retention: ৳' . number_format($retentionAmount, 2) . ') submitted. Awaiting approval from Management and Role 20.');
            header("Location: {$GLOBALS['base_url']}/client-refund/{$refundId}");
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

        // Get company banks and Bangladesh banks for disbursement
        $companyBanks = $this->db->query("SELECT id, bank_name, account_name, account_number FROM company_bank_accounts WHERE status = 'active' ORDER BY is_default DESC, bank_name ASC")->fetch_all(MYSQLI_ASSOC);
        $allBangladeshBanks = function_exists('getBangladeshBanks') ? getBangladeshBanks() : [];

        $this->view('accounts/refunds/show-refund', [
            'current_route'       => 'client-refunds',
            'page_title'          => 'Refund Request Details',
            'refund'              => $refund,
            'payments'            => $payments,
            'cashout_request'     => $cashoutRequest,
            'cashout_payments'    => $cashoutPayments ?? [],
            'approval_request'    => $approvalReq,
            'companyBanks'        => $companyBanks,
            'allBangladeshBanks'  => $allBangladeshBanks,
            'canApprove'          => $this->canApprove(),
            'userRole'            => $this->userRole,
        ]);
    }

    // =========================================================================
    // APPROVE — creates cashout_request automatically
    // =========================================================================

    public function approve($id)
    {
        if (!$this->canApprove()) {
            setError('You are not authorized to approve refund requests.');
            $refQuery = (!empty($_GET['ref']) && $_GET['ref'] === 'all_approvals') ? '?ref=all_approvals' : '';
        header("Location: {$GLOBALS['base_url']}/client-refund/{$id}{$refQuery}");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $refQuery = (!empty($_GET['ref']) && $_GET['ref'] === 'all_approvals') ? '?ref=all_approvals' : '';
        header("Location: {$GLOBALS['base_url']}/client-refund/{$id}{$refQuery}");
            exit;
        }

        $refund = $this->model->find($id);
        if (!$refund || $refund['status'] !== 'pending') {
            setError('Refund request is not in pending status.');
            $refQuery = (!empty($_GET['ref']) && $_GET['ref'] === 'all_approvals') ? '?ref=all_approvals' : '';
        header("Location: {$GLOBALS['base_url']}/client-refund/{$id}{$refQuery}");
            exit;
        }

        $totalPaid = (float)($refund['total_paid'] ?? 0);
        if (isset($_POST['retention_percent']) && $_POST['retention_percent'] !== '' && (!isset($_POST['retention_amount']) || $_POST['retention_amount'] === '')) {
            $retPercent = max(0, min(100, (float)$_POST['retention_percent']));
            $retentionAmount = round(($totalPaid * $retPercent) / 100, 2);
            $approvedAmount  = max(0, $totalPaid - $retentionAmount);
        } elseif (isset($_POST['retention_amount']) && $_POST['retention_amount'] !== '') {
            $retentionAmount = max(0, min($totalPaid, (float)$_POST['retention_amount']));
            $approvedAmount  = max(0, $totalPaid - $retentionAmount);
        } else {
            $approvedAmount  = isset($_POST['approved_amount']) ? (float)$_POST['approved_amount'] : (float)$refund['refund_amount'];
            $retentionAmount = max(0, $totalPaid - $approvedAmount);
        }

        $isOver1Lac = ($approvedAmount > 100000);
        $userRole = (int)($this->userRole ?? 0);
        $roleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
        $isOpHeadUser = (in_array($userRole, [20, 32], true) || str_contains($roleName, 'operation head'));
        $isMgmtUser = in_array($userRole, [1, 3], true);

        // Fetch approval request record
        $arCheck = $this->db->query("SELECT id, op_head_status FROM approval_requests WHERE source_table = 'client_refund_requests' AND source_id = " . (int)$id . " ORDER BY id DESC LIMIT 1");
        $arRow = $arCheck ? $arCheck->fetch_assoc() : null;
        $opHeadStatus = strtolower($arRow['op_head_status'] ?? 'pending');

        if ($isOver1Lac) {
            if ($isOpHeadUser && !$isMgmtUser) {
                $arId = (int)($arRow['id'] ?? 0);
                $escNote = $this->db->real_escape_string('Verified and approved by Admin & Operation Head');
                if (!class_exists('ApprovalRequest')) {
                    require_once __DIR__ . '/../models/ApprovalRequest.php';
                }
                $approvalModel = new \ApprovalRequest($this->db);
                if ($arId > 0) {
                    $approvalModel->approveOperationHead($arId, $this->userId, $escNote);
                } else {
                    $this->db->query("UPDATE approval_requests SET op_head_status = 'approved', op_head_by = {$this->userId}, op_head_at = NOW(), op_head_note = '{$escNote}', approve_role = 'management' WHERE source_table = 'client_refund_requests' AND source_id = " . (int)$id);
                }
                setSuccess("Refund request verified & approved by Admin & Operation Head (Role 20). Forwarded to Management for sign-off.");
                $refQuery = (!empty($_GET['ref']) && $_GET['ref'] === 'all_approvals') ? '?ref=all_approvals' : '';
        header("Location: {$GLOBALS['base_url']}/client-refund/{$id}{$refQuery}");
                exit;
            }

            if ($isMgmtUser) {
                if ($opHeadStatus !== 'approved') {
                    setError("Access Denied: Refund requests exceeding ৳1,00,000 must be approved by Admin & Operation Head (Role 20) before Management can approve.");
                    $refQuery = (!empty($_GET['ref']) && $_GET['ref'] === 'all_approvals') ? '?ref=all_approvals' : '';
        header("Location: {$GLOBALS['base_url']}/client-refund/{$id}{$refQuery}");
                    exit;
                }
            }
        }

        // Step 1: Approve refund (cancels booking, releases flat, cancels EMIs)
        if (!$this->model->approve($id, $this->userId, $approvedAmount)) {
            setError('Failed to approve refund request.');
            $refQuery = (!empty($_GET['ref']) && $_GET['ref'] === 'all_approvals') ? '?ref=all_approvals' : '';
        header("Location: {$GLOBALS['base_url']}/client-refund/{$id}{$refQuery}");
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
                 SET status = 'approved', approved_by = ?, approved_at = NOW(), approved_amount = ?
                 WHERE id = ?"
            );
            $stmt->bind_param('idi', $this->userId, $approvedAmount, $cashoutId);
            $stmt->execute();

            // Sync approval_requests for the refund
            $arStmt = $this->db->prepare(
                "UPDATE approval_requests
                 SET op_head_status = 'approved', status = 'approved', approved_by = ?, approved_at = NOW(), approved_amount = ?, request_amount = ?, approve_role = 'accounts_head', accounts_head_status = 'pending'
                 WHERE source_table = 'client_refund_requests' AND source_id = ?"
            );
            $arStmt->bind_param('iddi', $this->userId, $approvedAmount, $approvedAmount, $id);
            $arStmt->execute();

            // Sync approval_requests for the cashout
            $arCashoutStmt = $this->db->prepare(
                "UPDATE approval_requests
                 SET op_head_status = 'approved', status = 'approved', approved_by = ?, approved_at = NOW(), approved_amount = ?, request_amount = ?, approve_role = 'accounts_head', accounts_head_status = 'pending'
                 WHERE source_table = 'cashout_requests' AND source_id = ?"
            );
            $arCashoutStmt->bind_param('iddi', $this->userId, $approvedAmount, $approvedAmount, $cashoutId);
            $arCashoutStmt->execute();
        }

        // Step 3: Send email notification
        try {
            $this->mailService->sendRefundOnApproval([
                'id'               => $id,
                'client_name'      => $refund['client_name'] ?? '',
                'flat_name'        => $refund['flat_name'] ?? '',
                'project_name'     => $refund['project_name'] ?? '',
                'refund_amount'    => $approvedAmount,
                'retention_amount' => $retentionAmount,
                'requested_by'     => $refund['requested_by'],
            ], $this->userId);
        } catch (\Exception $e) {
            error_log("Refund email (approval) failed: " . $e->getMessage());
        }

        setSuccess('Refund approved! Cashout request created for ৳' . number_format($approvedAmount, 2) . ' (Retention: ৳' . number_format($retentionAmount, 2) . '). Booking cancelled, flat released.');
        $refQuery = (!empty($_GET['ref']) && $_GET['ref'] === 'all_approvals') ? '?ref=all_approvals' : '';
        header("Location: {$GLOBALS['base_url']}/client-refund/{$id}{$refQuery}");
        exit;
    }

    // =========================================================================
    // ADJUST / CUT-OFF RETENTION & REFUND AMOUNT (Accounts & Management)
    // Synchronously updates:
    // 1. client_refund_requests (retention_amount, refund_amount)
    // 2. cashout_requests (amount, approved_amount, due_amount)
    // 3. approval_requests (both cashout and client_refund requests)
    // Supports both fixed amount (৳) and percentage (%) of Total Paid!
    // =========================================================================
    public function adjustRetention($id)
    {
        // Allow Accounts (6, 7, 10, 30, 37) and Management (1, 3, 9, 20, 33)
        if (!in_array($this->userRole, [1, 3, 6, 7, 9, 10, 20, 30, 33, 37])) {
            setError('You are not authorized to set or adjust refund retention.');
            $refQuery = (!empty($_GET['ref']) && $_GET['ref'] === 'all_approvals') ? '?ref=all_approvals' : '';
        header("Location: {$GLOBALS['base_url']}/client-refund/{$id}{$refQuery}");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $refQuery = (!empty($_GET['ref']) && $_GET['ref'] === 'all_approvals') ? '?ref=all_approvals' : '';
        header("Location: {$GLOBALS['base_url']}/client-refund/{$id}{$refQuery}");
            exit;
        }

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_POST['ajax']);

        $refund = $this->model->find($id);
        if (!$refund) {
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Refund request not found.']);
                exit;
            }
            setError('Refund request not found.');
            header("Location: {$GLOBALS['base_url']}/client-refunds");
            exit;
        }

        if ($refund['status'] === 'refunded') {
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'This refund payment has already been disbursed and completed. Retention cannot be adjusted now.']);
                exit;
            }
            setError('This refund payment has already been disbursed and completed. Retention cannot be adjusted now.');
            $refQuery = (!empty($_GET['ref']) && $_GET['ref'] === 'all_approvals') ? '?ref=all_approvals' : '';
        header("Location: {$GLOBALS['base_url']}/client-refund/{$id}{$refQuery}");
            exit;
        }

        if ($refund['status'] === 'rejected') {
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Cannot adjust a rejected refund request.']);
                exit;
            }
            setError('Cannot adjust a rejected refund request.');
            $refQuery = (!empty($_GET['ref']) && $_GET['ref'] === 'all_approvals') ? '?ref=all_approvals' : '';
        header("Location: {$GLOBALS['base_url']}/client-refund/{$id}{$refQuery}");
            exit;
        }

        $totalPaid = (float)$refund['total_paid'];
        
        // Calculate retention amount from % or fixed amount
        $retentionPercent = (isset($_POST['retention_percent']) && $_POST['retention_percent'] !== '') ? (float)$_POST['retention_percent'] : null;

        if ($retentionPercent !== null && (!isset($_POST['retention_amount']) || $_POST['retention_amount'] === '')) {
            $retentionPercent = max(0, min(100, $retentionPercent));
            $retentionAmount  = round(($totalPaid * $retentionPercent) / 100, 2);
        } else {
            $retentionAmount = isset($_POST['retention_amount']) ? (float)$_POST['retention_amount'] : 0.0;
        }

        // Validation
        if ($retentionAmount < 0 || $retentionAmount > $totalPaid) {
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Retention amount must be between 0 and total paid (৳' . number_format($totalPaid, 2) . ').']);
                exit;
            }
            setError('Retention amount must be between 0 and total paid (৳' . number_format($totalPaid, 2) . ').');
            $refQuery = (!empty($_GET['ref']) && $_GET['ref'] === 'all_approvals') ? '?ref=all_approvals' : '';
        header("Location: {$GLOBALS['base_url']}/client-refund/{$id}{$refQuery}");
            exit;
        }

        $netRefundAmount = max(0, $totalPaid - $retentionAmount);
        $retPercentCalc  = ($totalPaid > 0) ? round(($retentionAmount / $totalPaid) * 100, 2) : 0;

        // Check if cashout exists and has existing payments
        $totalPaidOut = 0.0;
        $cashoutId = !empty($refund['cashout_request_id']) ? (int)$refund['cashout_request_id'] : null;

        if ($cashoutId) {
            $paidStmt = $this->db->prepare("SELECT COALESCE(SUM(amount), 0) AS paid_sum FROM cashout_payments WHERE cashout_request_id = ?");
            if ($paidStmt) {
                $paidStmt->bind_param('i', $cashoutId);
                $paidStmt->execute();
                $totalPaidOut = (float)($paidStmt->get_result()->fetch_assoc()['paid_sum'] ?? 0);
            }

            if ($netRefundAmount < $totalPaidOut) {
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'message' => 'Cannot reduce refund amount below already disbursed amount (৳' . number_format($totalPaidOut, 2) . ').']);
                    exit;
                }
                setError('Cannot reduce refund amount below already disbursed amount (৳' . number_format($totalPaidOut, 2) . ').');
                $refQuery = (!empty($_GET['ref']) && $_GET['ref'] === 'all_approvals') ? '?ref=all_approvals' : '';
        header("Location: {$GLOBALS['base_url']}/client-refund/{$id}{$refQuery}");
                exit;
            }
        }

        $dueAmount = max(0, $netRefundAmount - $totalPaidOut);

        $this->db->begin_transaction();
        try {
            // 1. Update client_refund_requests
            $upRf = $this->db->prepare("UPDATE client_refund_requests SET retention_amount = ?, refund_amount = ?, updated_at = NOW() WHERE id = ?");
            $upRf->bind_param("ddi", $retentionAmount, $netRefundAmount, $id);
            $upRf->execute();

            // 2. Update cashout_requests (if exists)
            if ($cashoutId) {
                $upCashout = $this->db->prepare("UPDATE cashout_requests SET amount = ?, approved_amount = ?, due_amount = ?, updated_at = NOW() WHERE id = ?");
                $upCashout->bind_param("dddi", $netRefundAmount, $netRefundAmount, $dueAmount, $cashoutId);
                $upCashout->execute();

                // 3. Update approval_requests for cashout
                $upArCashout = $this->db->prepare("UPDATE approval_requests SET request_amount = ?, approved_amount = ?, updated_at = NOW() WHERE source_table = 'cashout_requests' AND source_id = ?");
                $upArCashout->bind_param("ddi", $netRefundAmount, $netRefundAmount, $cashoutId);
                $upArCashout->execute();
            }

            // 4. Update or create approval_requests for client_refund_requests
            $chkAr = $this->db->query("SELECT id FROM approval_requests WHERE source_table = 'client_refund_requests' AND source_id = {$id} LIMIT 1");
            if ($chkAr && $chkAr->num_rows > 0) {
                $arId = (int)$chkAr->fetch_assoc()['id'];
                $upArRf = $this->db->prepare("UPDATE approval_requests SET request_amount = ?, approved_amount = ?, approve_role = 'management', status = 'pending', op_head_status = 'pending', updated_at = NOW() WHERE id = ?");
                $upArRf->bind_param("ddi", $netRefundAmount, $netRefundAmount, $arId);
                $upArRf->execute();
            } else {
                $bData = $this->model->getBookingDetails((int)$refund['booking_id']);
                $rfTitle = 'Client Refund — ' . ($bData['client_name'] ?? 'Client') . ' | ' . ($bData['flat_name'] ?? '') . ' | ' . ($bData['project_name'] ?? '');
                $stmtAr = $this->db->prepare("
                    INSERT INTO approval_requests (department, request_type, title, source_table, source_id, requested_days, requested_by, approve_role, request_amount, approved_amount, status, op_head_status, accounts_head_status, created_at, updated_at)
                    VALUES ('accounts', 'client_refund', ?, 'client_refund_requests', ?, 0, ?, 'management', ?, ?, 'pending', 'pending', 'pending', NOW(), NOW())
                ");
                if ($stmtAr) {
                    $reqBy = (int)($refund['requested_by'] ?: $this->userId);
                    $stmtAr->bind_param('siidd', $rfTitle, $id, $reqBy, $netRefundAmount, $netRefundAmount);
                    $stmtAr->execute();
                    $stmtAr->close();
                }
            }

            // If refund is already approved but cashout request was missing (e.g. legacy/direct approval), auto-create and link it now
            if ($refund['status'] === 'approved' && !$cashoutId) {
                $bData = $this->model->getBookingDetails((int)$refund['booking_id']);
                $coTitle = 'Client Refund — ' . ($bData['client_name'] ?? 'Client') . ' | ' . ($bData['flat_name'] ?? '') . ' | ' . ($bData['project_name'] ?? '');
                $cashoutData = [
                    'requested_by'    => $refund['requested_by'] ?: $this->userId,
                    'purpose_paid_to' => $coTitle,
                    'amount'          => $netRefundAmount,
                    'description'     => 'Auto-generated from Client Refund #' . $id . '. Reason: ' . ($refund['reason'] ?? 'N/A'),
                    'payment_method'  => 'cash',
                    'project_id'      => $refund['project_id'],
                ];
                $cashoutResult = $this->cashoutModel->createRequest($cashoutData);
                if ($cashoutResult['success'] && !empty($cashoutResult['id'])) {
                    $newCashoutId = (int)$cashoutResult['id'];
                    $this->db->query("UPDATE client_refund_requests SET cashout_request_id = {$newCashoutId} WHERE id = {$id}");
                    $this->db->query("UPDATE cashout_requests SET status = 'approved', approved_amount = {$netRefundAmount}, due_amount = {$netRefundAmount} WHERE id = {$newCashoutId}");
                }
            }

            $this->db->commit();

            // If AJAX request, return JSON
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_POST['ajax']);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success'           => true,
                    'message'           => "Retention cut-off set to ৳" . number_format($retentionAmount, 2) . " ({$retPercentCalc}%). Net Refund: ৳" . number_format($netRefundAmount, 2),
                    'retention_amount'  => $retentionAmount,
                    'refund_amount'     => $netRefundAmount,
                    'retention_percent' => $retPercentCalc,
                ]);
                exit;
            }

            setSuccess("Retention cut-off successfully set to ৳" . number_format($retentionAmount, 2) . " ({$retPercentCalc}%). Net Refund to Client: ৳" . number_format($netRefundAmount, 2) . "!");
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Refund retention adjustment failed: " . $e->getMessage());

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => "Failed to adjust retention: " . $e->getMessage()]);
                exit;
            }

            setError("Failed to adjust retention: " . $e->getMessage());
        }

        $redirectTo = !empty($_GET['redirect']) && $_GET['redirect'] === 'client-refunds'
            ? "{$GLOBALS['base_url']}/client-refunds"
            : "{$GLOBALS['base_url']}/client-refund/{$id}";
        header("Location: {$redirectTo}");
        exit;
    }

    // =========================================================================
    // REJECT
    // =========================================================================

    public function reject($id)
    {
        if (!$this->canApprove()) {
            setError('You are not authorized to reject refund requests.');
            $refQuery = (!empty($_GET['ref']) && $_GET['ref'] === 'all_approvals') ? '?ref=all_approvals' : '';
        header("Location: {$GLOBALS['base_url']}/client-refund/{$id}{$refQuery}");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $refQuery = (!empty($_GET['ref']) && $_GET['ref'] === 'all_approvals') ? '?ref=all_approvals' : '';
        header("Location: {$GLOBALS['base_url']}/client-refund/{$id}{$refQuery}");
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

        $refQuery = (!empty($_GET['ref']) && $_GET['ref'] === 'all_approvals') ? '?ref=all_approvals' : '';
        header("Location: {$GLOBALS['base_url']}/client-refund/{$id}{$refQuery}");
        exit;
    }

    // =========================================================================
    // DISBURSE PAYMENT (Accounts pays after Management & Role 20 approval)
    // =========================================================================

    public function disbursePayment($id)
    {
        // Authorized: Accounts (6, 10, 30) or Admin (1, 3)
        if (!in_array($this->userRole, [1, 3, 6, 10, 30])) {
            setError('You are not authorized to disburse refund payments.');
            $refQuery = (!empty($_GET['ref']) && $_GET['ref'] === 'all_approvals') ? '?ref=all_approvals' : '';
        header("Location: {$GLOBALS['base_url']}/client-refund/{$id}{$refQuery}");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $refQuery = (!empty($_GET['ref']) && $_GET['ref'] === 'all_approvals') ? '?ref=all_approvals' : '';
        header("Location: {$GLOBALS['base_url']}/client-refund/{$id}{$refQuery}");
            exit;
        }

        $refund = $this->model->find($id);
        if (!$refund) {
            setError('Refund request not found.');
            header("Location: {$GLOBALS['base_url']}/client-refunds");
            exit;
        }

        if ($refund['status'] !== 'approved') {
            setError('Refund request is not approved yet.');
            $refQuery = (!empty($_GET['ref']) && $_GET['ref'] === 'all_approvals') ? '?ref=all_approvals' : '';
        header("Location: {$GLOBALS['base_url']}/client-refund/{$id}{$refQuery}");
            exit;
        }

        // Mandatory Accounts Head Approval Guard
        if (!class_exists('ApprovalRequest')) {
            require_once __DIR__ . '/../models/ApprovalRequest.php';
        }
        $ahCheck = ApprovalRequest::getAccountsHeadApprovalInfo($this->db, 'client_refund_requests', (int)$id);
        if ($ahCheck && !empty($ahCheck['has_request']) && empty($ahCheck['is_accounts_head_approved'])) {
            setError("Payment cannot be disbursed. Approval from Accounts Head (Md. Mamunoor Rashid [1309]) is pending.");
            $refQuery = (!empty($_GET['ref']) && $_GET['ref'] === 'all_approvals') ? '?ref=all_approvals' : '';
        header("Location: {$GLOBALS['base_url']}/client-refund/{$id}{$refQuery}");
            exit;
        }

        $paymentMethod  = trim($_POST['payment_method'] ?? 'cash');
        $companyBankId  = !empty($_POST['company_bank_id']) ? (int)$_POST['company_bank_id'] : (!empty($_POST['bank_id']) ? (int)$_POST['bank_id'] : null);
        $chequeNo       = trim($_POST['cheque_no'] ?? '');
        $paymentDate    = !empty($_POST['payment_date']) ? $_POST['payment_date'] : date('Y-m-d');
        $transactionRef = trim($_POST['transaction_ref'] ?? '');
        $clientBankName = trim($_POST['bank_name'] ?? '');
        $bankAccountNo  = trim($_POST['bank_account_no'] ?? '');
        $remarks        = trim($_POST['remarks'] ?? '');
        $refundAmount   = (float)$refund['refund_amount'];
        $bookingId      = (int)$refund['booking_id'];

        // Get company bank name if provided
        $companyBankName = '';
        if ($companyBankId) {
            $bRes = $this->db->query("SELECT CONCAT(bank_name, IF(account_name != '', CONCAT(' - ', account_name), ''), IF(account_number != '', CONCAT(' (', account_number, ')'), '')) AS label FROM company_bank_accounts WHERE id = {$companyBankId} LIMIT 1");
            if ($bRes && ($bRow = $bRes->fetch_assoc())) {
                $companyBankName = $bRow['label'];
            }
        }

        // Mandatory attachment / payment proof
        if (empty($_FILES['disbursement_proof']['name'])) {
            setError('Disbursement proof attachment (cheque copy / voucher / receipt) is required.');
            header("Location: {$GLOBALS['base_url']}/client-refund/{$id}?pay=1");
            exit;
        }

        $allowedTypes = ['doc', 'docx', 'pdf', 'jpg', 'jpeg', 'png'];
        $fileExt = strtolower(pathinfo($_FILES['disbursement_proof']['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExt, $allowedTypes)) {
            setError('Invalid attachment format. Allowed formats: JPG, PNG, PDF, DOC, DOCX.');
            header("Location: {$GLOBALS['base_url']}/client-refund/{$id}?pay=1");
            exit;
        }

        if ($_FILES['disbursement_proof']['size'] > 10 * 1024 * 1024) {
            setError('Attachment exceeds 10MB limit.');
            header("Location: {$GLOBALS['base_url']}/client-refund/{$id}?pay=1");
            exit;
        }

        $newFileName = 'refund_voucher_' . $id . '_' . time() . '.' . $fileExt;
        $uploadPath  = 'uploads/refunds/' . $newFileName;
        $fullPath    = __DIR__ . '/../../public/' . $uploadPath;
        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0777, true);
        }

        if (!move_uploaded_file($_FILES['disbursement_proof']['tmp_name'], $fullPath)) {
            setError('Failed to upload disbursement proof document.');
            header("Location: {$GLOBALS['base_url']}/client-refund/{$id}?pay=1");
            exit;
        }
        $proofPath = $uploadPath;

        $this->db->begin_transaction();
        try {
            // 1. Update client_refund_requests status to 'refunded'
            $upStmt = $this->db->prepare("UPDATE client_refund_requests 
                SET status = 'refunded', refunded_at = NOW(), document = ? 
                WHERE id = ?");
            $upStmt->bind_param("si", $proofPath, $id);
            $upStmt->execute();

            // 2. Insert negative payment entry in lead_payments to reduce cash-in balance
            $negAmount = -abs($refundAmount);
            $fullRef = 'Refund Payout #' . $id;
            if ($companyBankName !== '') {
                $fullRef .= ' [Co. Bank: ' . $companyBankName . ']';
            }
            if ($chequeNo !== '') {
                $fullRef .= ' (Cheque: ' . $chequeNo . ')';
            }
            if ($transactionRef !== '') {
                $fullRef .= ' (Ref: ' . $transactionRef . ')';
            }
            if ($remarks !== '') {
                $fullRef .= ' | ' . $remarks;
            }

            $payStmt = $this->db->prepare("INSERT INTO lead_payments 
                (booking_id, amount, payment_method, bank_id, bank_name, bank_account_no, cheque_no, payment_status, verification_status, verified_by, verified_at, payment_date, transaction_ref, attachment_image, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'paid', 'verified', ?, NOW(), ?, ?, ?, ?)");
            $payStmt->bind_param("idsisssissssi", $bookingId, $negAmount, $paymentMethod, $companyBankId, $clientBankName, $bankAccountNo, $chequeNo, $this->userId, $paymentDate, $fullRef, $proofPath, $this->userId);
            $payStmt->execute();

            // 3. If linked cashout request exists, record in cashout_payments and set fully_paid
            if (!empty($refund['cashout_request_id'])) {
                $cId = (int)$refund['cashout_request_id'];
                $cpStmt = $this->db->prepare("INSERT INTO cashout_payments 
                    (cashout_request_id, amount, payment_method, bank_id, bank_name, bank_account_no, cheque_no, payment_date, transaction_ref, disbursement_proof, created_by, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $proofJson = json_encode([$proofPath]);
                $cpStmt->bind_param("idsissssssi", $cId, $refundAmount, $paymentMethod, $companyBankId, $clientBankName, $bankAccountNo, $chequeNo, $paymentDate, $transactionRef, $proofJson, $this->userId);
                $cpStmt->execute();

                $this->db->query("UPDATE cashout_requests SET status = 'fully_paid', due_amount = 0 WHERE id = {$cId}");
            }

            $this->db->commit();
            setSuccess('Refund payment of ৳' . number_format($refundAmount, 2) . ' disbursed successfully with attachment! Client cash-in has been adjusted.');
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Refund disbursePayment failed: " . $e->getMessage());
            setError('Failed to disburse refund payment: ' . $e->getMessage());
        }

        $redirectTo = !empty($_GET['redirect']) && $_GET['redirect'] === 'client-refunds' 
            ? "{$GLOBALS['base_url']}/client-refunds" 
            : "{$GLOBALS['base_url']}/client-refund/{$id}";
        header("Location: {$redirectTo}");
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

<?php
require_once __DIR__ . '/../models/CashoutRequest.php';
require_once __DIR__ . '/../models/CashoutPayment.php';
require_once __DIR__ . '/../models/ApprovalRequest.php';

class CashoutRequestController extends Controller
{
    protected $db;
    protected $cashoutModel;
    protected $paymentModel;
    protected $approvalModel;
    protected $userRole;
    protected $userId;

    public function __construct($db)
    {
        $this->db = $db;
        $this->cashoutModel = new CashoutRequest($this->db);
        $this->paymentModel = new CashoutPayment($this->db);
        $this->approvalModel = new ApprovalRequest($this->db);

        // Ensure session is started FIRST
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Get user info from session with proper checks
        $this->userId = $_SESSION['user']['id'] ?? null;
        $this->userRole = isset($_SESSION['user']['role_id']) ? (int)$_SESSION['user']['role_id'] : null;

        // Debug logging
        error_log("CashoutRequestController Init - User ID: {$this->userId}, Role ID: {$this->userRole}");

        // Redirect to login if not authenticated
        if (!$this->userId) {
            setError("Please login first.");
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }
    }

    // Dashboard - User view (only their own requests)
    public function index()
    {
        // Regular users can only see their own requests
        if (!$this->isAdminUser()) {
            // Get only current user's requests
            $requests = $this->cashoutModel->getUserRequests($this->userId);
            $statistics = $this->cashoutModel->getStatistics($this->userId);

            $this->view("accounts/cashout/request-list", [
                "current_route" => "cashout-requests",
                "page_title" => "My Cashout Requests",
                "requests" => $requests,
                "statistics" => $statistics,
                "isAdmin" => false,
                "userRole" => $this->userRole
            ]);
        } else {
            // Admin users see all requests (redirect to admin view)
            header("Location: {$GLOBALS['base_url']}/admin-cashout-requests");
            exit;
        }
    }

    // Admin/Management view - sees all requests
    public function adminIndex()
    {
        // Check if user has admin rights
        if (!$this->isAdminUser()) {
            setError("You are not authorized to access this page.");
            header("Location: {$GLOBALS['base_url']}/cashout-requests");
            exit;
        }

        $filters = [
            'status' => $_GET['status'] ?? null,
            'user_id' => $_GET['user_id'] ?? null,
            'start_date' => $_GET['start_date'] ?? null,
            'end_date' => $_GET['end_date'] ?? null
        ];

        $requests = $this->cashoutModel->getAllRequests($filters);
        $statistics = $this->cashoutModel->getStatistics();

        $this->view("accounts/cashout/admin-request-list", [
            "current_route" => "admin-cashout-requests",
            "page_title" => "Cashout Requests Management",
            "requests" => $requests,
            "statistics" => $statistics,
            "filters" => $filters,
            "isAdmin" => true,
            "userRole" => $this->userRole
        ]);
    }

    // Show create form - available to all authenticated users
    public function create()
    {
        $this->view("accounts/cashout/create-request", [
            "current_route" => "create-cashout-request",
            "page_title" => "Request Cashout",
            "isAdmin" => $this->isAdminUser()
        ]);
    }

    // Store new request - available to all authenticated users
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setError("Invalid request method.");
            header("Location: {$GLOBALS['base_url']}/cashout-requests");
            exit;
        }

        // Handle file upload
        $attachmentPath = null;
        if (!empty($_FILES['attachment']['name'])) {
            $uploadResult = $this->handleFileUpload($_FILES['attachment']);
            if ($uploadResult['success']) {
                $attachmentPath = $uploadResult['file_path'];
            } else {
                setError($uploadResult['message']);
                header("Location: {$GLOBALS['base_url']}/create-cashout-request");
                exit;
            }
        }

        $data = [
            'requested_by' => $this->userId,
            'purpose_paid_to' => trim($_POST['purpose_paid_to'] ?? ''),
            'amount' => $_POST['amount'] ?? 0,
            'description' => trim($_POST['description'] ?? ''),
            'payment_method' => $_POST['payment_method'] ?? 'cash',
            'transaction_ref' => trim($_POST['transaction_ref'] ?? ''),
            'attachment_path' => $attachmentPath
        ];

        // Validation
        if (empty($data['purpose_paid_to']) || $data['amount'] <= 0) {
            setError("Please fill all required fields correctly.");
            header("Location: {$GLOBALS['base_url']}/create-cashout-request");
            exit;
        }

        // Create request
        $result = $this->cashoutModel->createRequest($data);

        if ($result['success']) {
            // Create approval request only if not admin creating for themselves
            if (!$this->isAdminUser()) {
                $this->createApprovalRequest($result['id'], $data);
            }

            setSuccess($result['message']);
            header("Location: {$GLOBALS['base_url']}/cashout-requests");
        } else {
            setError($result['message']);
            header("Location: {$GLOBALS['base_url']}/create-cashout-request");
        }
        exit;
    }

    // View single request - with proper authorization (Regular user view)
    public function show($id = null)
    {
        $id = $id ?? $_GET['id'] ?? null;
        if (!$id) {
            setError("Request ID missing.");
            header("Location: {$GLOBALS['base_url']}/cashout-requests");
            exit;
        }

        $request = $this->cashoutModel->getRequestById($id);
        if (!$request) {
            setError("Request not found.");
            header("Location: {$GLOBALS['base_url']}/cashout-requests");
            exit;
        }

        // Authorization check
        $canView = false;

        // Admins can view all requests
        if ($this->isAdminUser()) {
            $canView = true;
        }
        // Regular users can only view their own requests
        elseif ($request['requested_by'] == $this->userId) {
            $canView = true;
        }

        if (!$canView) {
            setError("You are not authorized to view this request.");
            header("Location: {$GLOBALS['base_url']}/cashout-requests");
            exit;
        }

        $payments = $this->paymentModel->getPaymentsByRequest($id);
        $totalPaid = $this->paymentModel->getTotalPaid($id);
        $isAdmin = $this->isAdminUser();

        $this->view("accounts/cashout/view-request", [
            "current_route" => "view-cashout-request",
            "page_title" => "Cashout Request Details",
            "request" => $request,
            "payments" => $payments,
            "total_paid" => $totalPaid,
            "isAdmin" => $isAdmin,
            "userRole" => $this->userRole,
            "userId" => $this->userId
        ]);
    }

    // Admin view single request - enhanced admin interface
    public function adminShow($id = null)
    {
        // Check if user has admin rights
        if (!$this->isAdminUser()) {
            setError("You are not authorized to access this page.");
            header("Location: {$GLOBALS['base_url']}/cashout-requests");
            exit;
        }

        $id = $id ?? $_GET['id'] ?? null;
        if (!$id) {
            setError("Request ID missing.");
            header("Location: {$GLOBALS['base_url']}/admin-cashout-requests");
            exit;
        }

        $request = $this->cashoutModel->getRequestById($id);
        if (!$request) {
            setError("Request not found.");
            header("Location: {$GLOBALS['base_url']}/admin-cashout-requests");
            exit;
        }

        $payments = $this->paymentModel->getPaymentsByRequest($id);
        $totalPaid = $this->paymentModel->getTotalPaid($id);

        $this->view("accounts/cashout/admin-view-request", [
            "current_route" => "admin-view-cashout-request",
            "page_title" => "Admin - Cashout Request Details",
            "request" => $request,
            "payments" => $payments,
            "total_paid" => $totalPaid,
            "isAdmin" => true,
            "userRole" => $this->userRole,
            "userId" => $this->userId
        ]);
    }

    // Add payment - only for admins
    public function addPayment()
    {
        if (!$this->isAdminUser()) {
            setError("You are not authorized to perform this action.");
            header("Location: {$GLOBALS['base_url']}/cashout-requests");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setError("Invalid request method.");
            header("Location: {$GLOBALS['base_url']}/cashout-requests");
            exit;
        }

        $requestId = $_POST['request_id'] ?? null;
        if (!$requestId) {
            setError("Request ID missing.");
            header("Location: {$GLOBALS['base_url']}/admin-cashout-requests");
            exit;
        }

        $data = [
            'cashout_request_id' => $requestId,
            'amount' => $_POST['amount'] ?? 0,
            'payment_method' => $_POST['payment_method'] ?? 'cash',
            'transaction_ref' => trim($_POST['transaction_ref'] ?? ''),
            'payment_date' => $_POST['payment_date'] ?? date('Y-m-d'),
            'bank_name' => trim($_POST['bank_name'] ?? ''),
            'bank_account_no' => trim($_POST['bank_account_no'] ?? ''),
            'cheque_no' => trim($_POST['cheque_no'] ?? ''),
            'online_type' => trim($_POST['online_type'] ?? ''),
            'created_by' => $this->userId
        ];

        if ($data['amount'] <= 0) {
            setError("Please enter a valid amount.");
            header("Location: {$GLOBALS['base_url']}/admin-view-cashout-request?id=" . $requestId);
            exit;
        }

        $result = $this->paymentModel->addPayment($data);

        if ($result['success']) {
            // Update request due amount and status
            $request = $this->cashoutModel->getRequestById($requestId);
            $totalPaid = $this->paymentModel->getTotalPaid($requestId);
            $dueAmount = $request['amount'] - $totalPaid;

            $this->cashoutModel->updateDueAmount($requestId, $dueAmount);

            // Update status based on payment
            if ($dueAmount <= 0) {
                $this->cashoutModel->updateStatus($requestId, 'fully_paid');
            } else {
                $this->cashoutModel->updateStatus($requestId, 'partially_paid');
            }

            setSuccess($result['message']);
        } else {
            setError($result['message']);
        }

        header("Location: {$GLOBALS['base_url']}/admin-view-cashout-request?id=" . $requestId);
        exit;
    }

    // ========== SIMPLIFIED APPROVAL - ANY ADMIN CAN APPROVE ==========

    /**
     * SINGLE APPROVAL METHOD
     * 
     * Any of these roles can approve:
     * - HR (Role 4)
     * - Admin (Role 1)
     * - Super Admin (Role 3)
     * 
     * Only ONE approval needed - first admin to approve wins!
     */
    public function approve()
    {
        // Check if user is an authorized approver (HR, Admin, or Super Admin)
        if (!in_array($this->userRole, [1, 3, 4])) {
            setError("Access Denied: Only HR, Admin, or Super Admin can approve requests.");
            header("Location: {$GLOBALS['base_url']}/cashout-requests");
            exit;
        }

        $requestId = $_POST['request_id'] ?? $_GET['id'] ?? null;
        if (!$requestId) {
            setError("Request ID missing.");
            header("Location: {$GLOBALS['base_url']}/admin-cashout-requests");
            exit;
        }

        // Get the request
        $request = $this->cashoutModel->getRequestById($requestId);
        if (!$request) {
            setError("Request not found.");
            header("Location: {$GLOBALS['base_url']}/admin-cashout-requests");
            exit;
        }

        // Check if request is in pending status
        if ($request['status'] != 'pending') {
            setError("This request cannot be approved. Current status: " . ucfirst(str_replace('_', ' ', $request['status'])));
            header("Location: {$GLOBALS['base_url']}/admin-view-cashout-request?id=" . $requestId);
            exit;
        }

        // Approve the request - set status to 'approved' and record who approved
        $result = $this->cashoutModel->updateStatus($requestId, 'approved', $this->userId);

        if ($result) {
            $roleName = $this->getUserRoleName();
            setSuccess("Request approved successfully by {$roleName}! Ready for payment processing.");
        } else {
            setError("Failed to approve request.");
        }

        header("Location: {$GLOBALS['base_url']}/admin-view-cashout-request?id=" . $requestId);
        exit;
    }

    // Reject request - only admins can reject
    public function reject()
    {
        if (!in_array($this->userRole, [1, 3, 4])) {
            setError("You are not authorized to reject requests.");
            header("Location: {$GLOBALS['base_url']}/cashout-requests");
            exit;
        }

        $requestId = $_POST['request_id'] ?? null;
        $reason = trim($_POST['rejection_reason'] ?? '');

        if (!$requestId) {
            setError("Request ID missing.");
            header("Location: {$GLOBALS['base_url']}/admin-cashout-requests");
            exit;
        }

        if (empty($reason)) {
            setError("Please provide a rejection reason.");
            header("Location: {$GLOBALS['base_url']}/admin-view-cashout-request?id=" . $requestId);
            exit;
        }

        $result = $this->cashoutModel->updateStatus($requestId, 'rejected', $this->userId, $reason);

        if ($result) {
            setSuccess("Request rejected successfully!");
        } else {
            setError("Failed to reject request.");
        }

        header("Location: {$GLOBALS['base_url']}/admin-view-cashout-request?id=" . $requestId);
        exit;
    }

    // Delete request - users can delete their own pending requests, admins can delete any
    public function delete()
    {
        $requestId = $_GET['id'] ?? null;
        if (!$requestId) {
            setError("Request ID missing.");
            header("Location: {$GLOBALS['base_url']}/cashout-requests");
            exit;
        }

        $request = $this->cashoutModel->getRequestById($requestId);
        if (!$request) {
            setError("Request not found.");
            header("Location: {$GLOBALS['base_url']}/cashout-requests");
            exit;
        }

        $canDelete = false;
        $redirectUrl = $this->isAdminUser() ? "/admin-cashout-requests" : "/cashout-requests";

        // Super Admin can delete any request
        if ($this->userRole == 3) {
            $canDelete = true;
        }
        // Users can only delete their own pending requests
        elseif ($request['requested_by'] == $this->userId && $request['status'] == 'pending') {
            $canDelete = true;
        }

        if (!$canDelete) {
            setError("You are not authorized to delete this request.");
            header("Location: {$GLOBALS['base_url']}" . $redirectUrl);
            exit;
        }

        $result = $this->cashoutModel->deleteRequest($requestId);

        if ($result) {
            setSuccess("Request deleted successfully!");
        } else {
            setError("Failed to delete request.");
        }

        header("Location: {$GLOBALS['base_url']}" . $redirectUrl);
        exit;
    }

    // Delete payment - only Super Admin
    public function deletePayment()
    {
        if ($this->userRole != 3) {
            setError("Only Super Admin can delete payments.");
            header("Location: {$GLOBALS['base_url']}/admin-cashout-requests");
            exit;
        }

        $paymentId = $_GET['id'] ?? null;
        $requestId = $_GET['request_id'] ?? null;

        if (!$paymentId || !$requestId) {
            setError("Payment ID or Request ID missing.");
            header("Location: {$GLOBALS['base_url']}/admin-cashout-requests");
            exit;
        }

        $result = $this->paymentModel->deletePayment($paymentId);

        if ($result) {
            // Recalculate due amount
            $request = $this->cashoutModel->getRequestById($requestId);
            $totalPaid = $this->paymentModel->getTotalPaid($requestId);
            $dueAmount = $request['amount'] - $totalPaid;

            $this->cashoutModel->updateDueAmount($requestId, $dueAmount);

            // Update status
            if ($dueAmount >= $request['amount']) {
                $this->cashoutModel->updateStatus($requestId, 'approved');
            } elseif ($dueAmount > 0) {
                $this->cashoutModel->updateStatus($requestId, 'partially_paid');
            }

            setSuccess("Payment deleted successfully!");
        } else {
            setError("Failed to delete payment.");
        }

        header("Location: {$GLOBALS['base_url']}/admin-view-cashout-request?id=" . $requestId);
        exit;
    }

    // ============ HELPER METHODS ============

    // Check if user is admin (HR, Admin, Super Admin, etc.)
    private function isAdminUser()
    {
        // Admin roles: 1=Admin, 3=Super Admin, 4=HR, 5=Sales Head, 6=Accounts
        $adminRoles = [1, 3, 4, 5, 6];
        return in_array($this->userRole, $adminRoles);
    }

    // Check specific role access
    private function checkRoleAccess($allowedRoles)
    {
        if (!in_array($this->userRole, $allowedRoles)) {
            $roleName = $this->getUserRoleName();
            setError("Access Denied: Your role ({$roleName}) is not authorized to perform this action.");
            header("Location: {$GLOBALS['base_url']}/cashout-requests");
            exit;
        }
    }

    // Get user's role name
    private function getUserRoleName()
    {
        $roleNames = [
            1 => 'Admin',
            2 => 'Employee',
            3 => 'Super Admin',
            4 => 'HR',
            5 => 'Sales Head',
            6 => 'Accounts',
            7 => 'Clients Experiment'
        ];

        return $roleNames[$this->userRole] ?? 'Unknown';
    }

    // Create approval request
    private function createApprovalRequest($requestId, $requestData)
    {
        $approvalData = [
            'department' => 'Accounts',
            'request_type' => 'cashout',
            'title' => 'Cashout Request: ' . $requestData['purpose_paid_to'],
            'source_table' => 'cashout_requests',
            'source_id' => $requestId,
            'request_amount' => $requestData['amount'],
            'requested_by' => $requestData['requested_by'],
            'approve_role' => 'management',
            'meta_data' => json_encode([
                'purpose' => $requestData['purpose_paid_to'],
                'payment_method' => $requestData['payment_method'],
                'requester_name' => $this->getUserName($requestData['requested_by'])
            ])
        ];

        return $this->approvalModel->create($approvalData);
    }

    // Get user name by ID
    private function getUserName($userId)
    {
        $sql = "SELECT full_name FROM users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        return $user['full_name'] ?? 'Unknown';
    }

    // Handle file upload
    private function handleFileUpload($file)
    {
        $allowedTypes = ['doc', 'docx', 'pdf', 'jpg', 'jpeg', 'png'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        $fileName = $file['name'];
        $fileSize = $file['size'];
        $fileTmp = $file['tmp_name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Validate file type
        if (!in_array($fileExt, $allowedTypes)) {
            return ['success' => false, 'message' => 'Only DOC, DOCX, PDF, JPG, PNG files are allowed.'];
        }

        // Validate file size
        if ($fileSize > $maxSize) {
            return ['success' => false, 'message' => 'File size must be less than 5MB.'];
        }

        // Generate unique filename
        $newFileName = 'cashout_' . time() . '_' . uniqid() . '.' . $fileExt;
        $uploadPath = 'uploads/cashout/' . $newFileName;

        // Create directory if not exists
        $fullPath = __DIR__ . '/../../public/' . $uploadPath;
        $dir = dirname($fullPath);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        // Move uploaded file
        if (move_uploaded_file($fileTmp, $fullPath)) {
            return ['success' => true, 'file_path' => $uploadPath];
        } else {
            return ['success' => false, 'message' => 'Failed to upload file.'];
        }
    }

    // Download attachment
    public function downloadAttachment()
    {
        $requestId = $_GET['id'] ?? null;
        if (!$requestId) {
            setError("Request ID missing.");
            header("Location: {$GLOBALS['base_url']}/cashout-requests");
            exit;
        }

        $request = $this->cashoutModel->getRequestById($requestId);
        if (!$request || empty($request['attachment_path'])) {
            setError("Attachment not found.");
            header("Location: {$GLOBALS['base_url']}/view-cashout-request?id=" . $requestId);
            exit;
        }

        // Authorization check
        $canDownload = false;
        if ($this->isAdminUser() || $request['requested_by'] == $this->userId) {
            $canDownload = true;
        }

        if (!$canDownload) {
            setError("You are not authorized to download this attachment.");
            header("Location: {$GLOBALS['base_url']}/view-cashout-request?id=" . $requestId);
            exit;
        }

        $filePath = __DIR__ . '/../../public/' . $request['attachment_path'];

        if (file_exists($filePath)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        } else {
            setError("File not found.");
            header("Location: {$GLOBALS['base_url']}/view-cashout-request?id=" . $requestId);
            exit;
        }
    }

    // Export requests - only for admins
    public function export()
    {
        if (!$this->isAdminUser()) {
            setError("You are not authorized to perform this action.");
            header("Location: {$GLOBALS['base_url']}/cashout-requests");
            exit;
        }

        $filters = [
            'status' => $_GET['status'] ?? null,
            'start_date' => $_GET['start_date'] ?? null,
            'end_date' => $_GET['end_date'] ?? null
        ];

        $requests = $this->cashoutModel->getAllRequests($filters);

        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="cashout_requests_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        // Add CSV headers
        fputcsv($output, [
            'ID',
            'Requester',
            'Purpose/Paid To',
            'Amount',
            'Due Amount',
            'Description',
            'Payment Method',
            'Transaction Ref',
            'Status',
            'Approved By',
            'Rejected By',
            'Rejection Reason',
            'Created At'
        ]);

        // Add data rows
        foreach ($requests as $request) {
            fputcsv($output, [
                $request['id'],
                $request['requester_name'],
                $request['purpose_paid_to'],
                $request['amount'],
                $request['due_amount'],
                $request['description'],
                $request['payment_method'],
                $request['transaction_ref'],
                $request['status'],
                $request['approved_by_name'] ?? 'N/A',
                $request['rejected_by_name'] ?? 'N/A',
                $request['rejection_reason'] ?? 'N/A',
                $request['created_at']
            ]);
        }

        fclose($output);
        exit;
    }
}
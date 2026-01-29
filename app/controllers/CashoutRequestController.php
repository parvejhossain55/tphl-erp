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

        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->userId = $_SESSION['user']['id'] ?? null;
        $this->userRole = isset($_SESSION['user']['role_id']) ? (int)$_SESSION['user']['role_id'] : null;

        if (!$this->userId) {
            setError("Please login first.");
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }
    }

    // Dashboard - User view
    public function index()
    {
        if (!$this->isAdminUser()) {
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
            header("Location: {$GLOBALS['base_url']}/admin-cashout-requests");
            exit;
        }
    }

    // Admin view
    public function adminIndex()
    {
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

    // Create form
    public function create()
    {
        $this->view("accounts/cashout/create-request", [
            "current_route" => "create-cashout-request",
            "page_title" => "Request Cashout",
            "isAdmin" => $this->isAdminUser()
        ]);
    }

    // Store new request
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

        // Create request - ALWAYS goes to pending
        $result = $this->cashoutModel->createRequest($data);

        if ($result['success']) {
            setSuccess($result['message']);
            header("Location: {$GLOBALS['base_url']}/cashout-requests");
        } else {
            setError($result['message']);
            header("Location: {$GLOBALS['base_url']}/create-cashout-request");
        }
        exit;
    }

    // View single request - FIXED: Proper ID handling
    public function show()
    {
        $id = $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            setError("Request ID missing or invalid.");
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
        if ($this->isAdminUser()) {
            $canView = true;
        } elseif ($request['requested_by'] == $this->userId) {
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

        // Get approval details
        $approvalRequest = $this->cashoutModel->getApprovalRequest($id);

        $this->view("accounts/cashout/view-request", [
            "current_route" => "view-cashout-request",
            "page_title" => "Cashout Request Details",
            "request" => $request,
            "payments" => $payments,
            "total_paid" => $totalPaid,
            "approval_request" => $approvalRequest,
            "isAdmin" => $isAdmin,
            "userRole" => $this->userRole,
            "userId" => $this->userId
        ]);
    }

    // Admin view single request
    public function adminShow()
    {
        if (!$this->isAdminUser()) {
            setError("You are not authorized to access this page.");
            header("Location: {$GLOBALS['base_url']}/cashout-requests");
            exit;
        }

        $id = $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            setError("Request ID missing or invalid.");
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
        $approvalRequest = $this->cashoutModel->getApprovalRequest($id);

        $this->view("accounts/cashout/admin-view-request", [
            "current_route" => "admin-view-cashout-request",
            "page_title" => "Admin - Cashout Request Details",
            "request" => $request,
            "payments" => $payments,
            "total_paid" => $totalPaid,
            "approval_request" => $approvalRequest,
            "isAdmin" => true,
            "userRole" => $this->userRole,
            "userId" => $this->userId
        ]);
    }

    // Add payment
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
            'created_by' => $this->userId
        ];

        if ($data['amount'] <= 0) {
            setError("Please enter a valid amount.");
            header("Location: {$GLOBALS['base_url']}/admin-view-cashout-request?id=" . $requestId);
            exit;
        }

        $result = $this->paymentModel->addPayment($data);

        if ($result['success']) {
            // Update due amount
            $request = $this->cashoutModel->getRequestById($requestId);
            $totalPaid = $this->paymentModel->getTotalPaid($requestId);
            $dueAmount = $request['amount'] - $totalPaid;

            $this->cashoutModel->updateDueAmount($requestId, $dueAmount);

            // Update status
            if ($dueAmount <= 0) {
                $this->cashoutModel->updateStatus($requestId, 'fully_paid', $this->userId);
            } else {
                $this->cashoutModel->updateStatus($requestId, 'partially_paid', $this->userId);
            }

            setSuccess($result['message']);
        } else {
            setError($result['message']);
        }

        header("Location: {$GLOBALS['base_url']}/admin-view-cashout-request?id=" . $requestId);
        exit;
    }

    // Approve request
    public function approve()
    {
        if (!in_array($this->userRole, [1, 3, 4, 6])) { // Admin, Super Admin, HR, Accounts
            setError("Access Denied: Only authorized approvers can approve requests.");
            header("Location: {$GLOBALS['base_url']}/cashout-requests");
            exit;
        }

        $requestId = $_POST['id'] ?? $_GET['id'] ?? null;
        if (!$requestId) {
            setError("Request ID missing.");
            header("Location: {$GLOBALS['base_url']}/admin-cashout-requests");
            exit;
        }

        $request = $this->cashoutModel->getRequestById($requestId);
        if (!$request) {
            setError("Request not found.");
            header("Location: {$GLOBALS['base_url']}/admin-cashout-requests");
            exit;
        }

        // Check if request is pending
        if (!in_array($request['display_status'], ['pending'])) {
            setError("This request cannot be approved. Current status: " . ucfirst(str_replace('_', ' ', $request['display_status'])));
            header("Location: {$GLOBALS['base_url']}/admin-view-cashout-request?id=" . $requestId);
            exit;
        }

        // Approve the request
        $result = $this->cashoutModel->updateStatus($requestId, 'approved', $this->userId);

        if ($result) {
            setSuccess("Request approved successfully! Ready for payment processing.");
        } else {
            setError("Failed to approve request.");
        }

        header("Location: {$GLOBALS['base_url']}/admin-view-cashout-request?id=" . $requestId);
        exit;
    }

    // Reject request
    public function reject()
    {
        if (!in_array($this->userRole, [1, 3, 4, 6])) {
            setError("You are not authorized to reject requests.");
            header("Location: {$GLOBALS['base_url']}/cashout-requests");
            exit;
        }

        $requestId = $_POST['id'] ?? null;
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

    // Delete request
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
        elseif ($request['requested_by'] == $this->userId && $request['display_status'] == 'pending') {
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

    // Helper methods (keep same as before)
    private function isAdminUser()
    {
        $adminRoles = [1, 3, 4, 5, 6];
        return in_array($this->userRole, $adminRoles);
    }

    private function handleFileUpload($file)
    {
        // Same as before
        $allowedTypes = ['doc', 'docx', 'pdf', 'jpg', 'jpeg', 'png'];
        $maxSize = 5 * 1024 * 1024;

        $fileName = $file['name'];
        $fileSize = $file['size'];
        $fileTmp = $file['tmp_name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExt, $allowedTypes)) {
            return ['success' => false, 'message' => 'Only DOC, DOCX, PDF, JPG, PNG files are allowed.'];
        }

        if ($fileSize > $maxSize) {
            return ['success' => false, 'message' => 'File size must be less than 5MB.'];
        }

        $newFileName = 'cashout_' . time() . '_' . uniqid() . '.' . $fileExt;
        $uploadPath = 'uploads/cashout/' . $newFileName;

        $fullPath = __DIR__ . '/../../public/' . $uploadPath;
        $dir = dirname($fullPath);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

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

    // Export CSV
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

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="cashout_requests_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

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
            'Approval Status',
            'Approved By',
            'Rejected By',
            'Rejection Reason',
            'Created At'
        ]);

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
                $request['display_status'],
                $request['approval_status'] ?? 'N/A',
                $request['approved_by_name'] ?? 'N/A',
                $request['rejected_by_name'] ?? 'N/A',
                $request['rejection_reason'] ?? 'N/A',
                $request['created_at']
            ]);
        }

        fclose($output);
        exit;
    }

    // NEW: Sync all requests with approval system (one-time fix)
    public function syncAllRequests()
    {
        if (!$this->isAdminUser()) {
            setError("Unauthorized access.");
            header("Location: {$GLOBALS['base_url']}/cashout-requests");
            exit;
        }

        $result = $this->cashoutModel->syncPendingRequests();
        
        if ($result) {
            setSuccess("All pending requests have been synced with the approval system.");
        } else {
            setError("Failed to sync requests.");
        }
        
        header("Location: {$GLOBALS['base_url']}/admin-cashout-requests");
        exit;
    }
}
?>
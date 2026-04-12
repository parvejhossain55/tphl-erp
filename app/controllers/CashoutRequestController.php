<?php
require_once __DIR__ . '/../models/CashoutRequest.php';
require_once __DIR__ . '/../models/CashoutPayment.php';
require_once __DIR__ . '/../models/ApprovalRequest.php';
require_once __DIR__ . '/../views/accounts/AccountMailService.php';

class CashoutRequestController extends Controller
{
    protected $db;
    protected $cashoutModel;
    protected $paymentModel;
    protected $approvalModel;
    protected $mailService;
    protected $userRole;
    protected $userId;

    public function __construct($db)
    {
        $this->db = $db;
        $this->cashoutModel = new CashoutRequest($this->db);
        $this->paymentModel = new CashoutPayment($this->db);
        $this->approvalModel = new ApprovalRequest($this->db);
        $this->mailService = new AccountMailService($this->db);

        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->userId = $_SESSION['user']['id'] ?? null;
        $this->userRole = isset($_SESSION['user']['role_id']) ? (int) $_SESSION['user']['role_id'] : null;

        if (!$this->userId) {
            setError("Please login first.");
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }
    }

    // Dashboard - User view
    public function index()
    {
        // Passively check and send any missing emails for requests recently approved via Home dashboard
        $this->syncApprovalsSilently();
        
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
        // Passively check and send any missing emails for requests recently approved via Home dashboard
        $this->syncApprovalsSilently();
        
        if (!$this->isAdminUser()) {
            setError("You are not authorized to access this page.");
            header("Location: {$GLOBALS['base_url']}/cashout-requests");
            exit;
        }

        $filters = [
            'status'     => $_GET['status'] ?? null,
            'user_id'    => $_GET['user_id'] ?? null,
            'project_id' => $_GET['project_id'] ?? null,
            'office'     => $_GET['office'] ?? null,
            'start_date' => $_GET['start_date'] ?? null,
            'end_date'   => $_GET['end_date'] ?? null
        ];

        $requests   = $this->cashoutModel->getAllRequests($filters);
        $statistics = $this->cashoutModel->getStatistics();
        $projects   = $this->cashoutModel->getAllProjects();
        $offices    = $this->getUniqueOffices();

        $this->view("accounts/cashout/admin-request-list", [
            "current_route" => "admin-cashout-requests",
            "page_title"    => "Cashout Requests Management",
            "requests"      => $requests,
            "statistics"    => $statistics,
            "projects"      => $projects,
            "offices"       => $offices,
            "filters"       => $filters,
            "isAdmin"       => true,
            "userRole"      => $this->userRole
        ]);
    }

    // Create form
    public function create()
    {
        $projects = $this->cashoutModel->getAllProjects();

        $officeOptions = [
            ''               => 'Select Office (Optional)',
            'Site Office'    => 'Site Office',
            'Site Office 2'  => 'Site Office 2 (Ati)',
            'Corporate Office' => 'Corporate Office',
            'Zonal Office '  => 'Zonal Office'
        ];

        $this->view("accounts/cashout/create-request", [
            "current_route" => "create-cashout-request",
            "page_title"    => "Request Cashout",
            "isAdmin"       => $this->isAdminUser(),
            "projects"      => $projects,
            "officeOptions" => $officeOptions
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

        // Handle multiple file uploads
        $attachmentPaths = [];
        if (!empty($_FILES['attachments']['name'][0])) {
            foreach ($_FILES['attachments']['tmp_name'] as $index => $tmpName) {
                if ($_FILES['attachments']['error'][$index] !== UPLOAD_ERR_OK)
                    continue;

                $file = [
                    'name'     => $_FILES['attachments']['name'][$index],
                    'tmp_name' => $_FILES['attachments']['tmp_name'][$index],
                    'size'     => $_FILES['attachments']['size'][$index],
                    'error'    => $_FILES['attachments']['error'][$index],
                ];

                $uploadResult = $this->handleFileUpload($file);
                if ($uploadResult['success']) {
                    $attachmentPaths[] = $uploadResult['file_path'];
                } else {
                    setError($uploadResult['message']);
                    header("Location: {$GLOBALS['base_url']}/create-cashout-request");
                    exit;
                }
            }
        }

        $data = [
            'requested_by'   => $this->userId,
            'purpose_paid_to' => trim($_POST['purpose_paid_to'] ?? ''),
            'amount'         => $_POST['amount'] ?? 0,
            'description'    => trim($_POST['description'] ?? ''),
            'payment_method' => $_POST['payment_method'] ?? 'cash',
            'transaction_ref' => trim($_POST['transaction_ref'] ?? ''),
            'attachment_path' => !empty($attachmentPaths) ? json_encode($attachmentPaths) : null,
            'project_id'     => !empty($_POST['project_id']) ? implode(',', array_map('intval', (array)$_POST['project_id'])) : null,
            'office'         => !empty($_POST['office']) ? implode(',', array_map('trim', (array)$_POST['office'])) : null,
        ];

        if (empty($data['purpose_paid_to']) || $data['amount'] <= 0) {
            setError("Please fill all required fields correctly.");
            header("Location: {$GLOBALS['base_url']}/create-cashout-request");
            exit;
        }

        $result = $this->cashoutModel->createRequest($data);

        if ($result['success']) {
            // Trigger Email Notification ON SUBMISSION
            $this->mailService->sendOnSubmission($data);
            
            setSuccess($result['message']);
            header("Location: {$GLOBALS['base_url']}/cashout-requests");
        } else {
            setError($result['message']);
            header("Location: {$GLOBALS['base_url']}/create-cashout-request");
        }
        exit;
    }

    // View single request
    public function show()
    {
        $id = $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            setError("Request ID missing or invalid.");
            header("Location: {$GLOBALS['base_url']}/cashout-requests");
            exit;
        }

        $this->syncWithApprovalSystem($id);

        $request = $this->cashoutModel->getRequestById($id);
        if (!$request) {
            setError("Request not found.");
            header("Location: {$GLOBALS['base_url']}/cashout-requests");
            exit;
        }

        // Authorization check — admin users AND the request owner can view
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

        $payments       = $this->paymentModel->getPaymentsByRequest($id);
        $totalPaid      = $this->paymentModel->getTotalPaid($id);
        $isAdmin        = $this->isAdminUser();
        $approvalRequest = $this->cashoutModel->getApprovalRequest($id);

        $this->view("accounts/cashout/view-request", [
            "current_route"    => "view-cashout-request",
            "page_title"       => "Cashout Request Details",
            "request"          => $request,
            "payments"         => $payments,
            "total_paid"       => $totalPaid,
            "approval_request" => $approvalRequest,
            "isAdmin"          => $isAdmin,
             "canApproveOrReject"   => $this->canApproveOrReject(), 
            "userRole"         => $this->userRole,
            "userId"           => $this->userId
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

        $this->syncWithApprovalSystem($id);

        $request = $this->cashoutModel->getRequestById($id);
        if (!$request) {
            setError("Request not found.");
            header("Location: {$GLOBALS['base_url']}/admin-cashout-requests");
            exit;
        }

        $payments        = $this->paymentModel->getPaymentsByRequest($id);
        $totalPaid       = $this->paymentModel->getTotalPaid($id);
        $approvalRequest = $this->cashoutModel->getApprovalRequest($id);

        $this->view("accounts/cashout/admin-view-request", [
            "current_route"    => "admin-view-cashout-request",
            "page_title"       => "Admin - Cashout Request Details",
            "request"          => $request,
            "payments"         => $payments,
            "total_paid"       => $totalPaid,
            "approval_request" => $approvalRequest,
            "isAdmin"          => true,
            "userRole"         => $this->userRole,
            "userId"           => $this->userId
        ]);
    }

    public function syncAllApprovals()
    {
        if (!$this->isAdminUser()) {
            setError("Unauthorized access.");
            header("Location: {$GLOBALS['base_url']}/cashout-requests");
            exit;
        }

        $sql = "SELECT cr.id FROM cashout_requests cr
                LEFT JOIN approval_requests ar ON ar.source_table = 'cashout_requests' AND ar.source_id = cr.id
                WHERE cr.status = 'pending' AND ar.status IN ('approved', 'partial', 'rejected')";

        $result = $this->db->query($sql);
        $synced = 0;

        while ($row = $result->fetch_assoc()) {
            if ($this->syncWithApprovalSystem($row['id'])) {
                $synced++;
            }
        }

        setSuccess("Successfully synced {$synced} requests with approval system.");
        header("Location: {$GLOBALS['base_url']}/admin-cashout-requests");
        exit;
    }
    // Add payment — roles 1, 3 (full admin) and 6, 10 (accounts, payment only)
    public function addPayment()
    {
        if (!$this->canAddPayment()) {
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
                    header("Location: {$GLOBALS['base_url']}/admin-view-cashout-request?id={$requestId}");
                    exit;
                }
            }
        }

        if (empty($proofPaths)) {
            setError("Disbursement proof document is required. Please upload at least one file.");
            header("Location: {$GLOBALS['base_url']}/admin-view-cashout-request?id={$requestId}");
            exit;
        }

        // Resolve bank name from Bank Registry if bank_id is provided
        $bankId   = !empty($_POST['bank_id']) ? (int)$_POST['bank_id'] : null;
        $bankName = $_POST['bank_name'] ?? null;
        if ($bankId) {
            $bRow = $this->db->query("SELECT CONCAT(bank_name, ' - ', branch_name) as label FROM banks WHERE id = {$bankId} LIMIT 1");
            if ($bRow && $bData = $bRow->fetch_assoc()) {
                $bankName = $bData['label'];
            }
        }

        $data = [
            'cashout_request_id' => $requestId,
            'amount'             => $_POST['amount'] ?? 0,
            'payment_method'     => $_POST['payment_method'] ?? 'cash',
            'transaction_ref'    => trim($_POST['transaction_ref'] ?? ''),
            'payment_date'       => $_POST['payment_date'] ?? date('Y-m-d'),
            'bank_id'            => $bankId,
            'bank_name'          => $bankName,
            'bank_account_no'    => $_POST['bank_account_no'] ?? null,
            'cheque_no'          => $_POST['cheque_no'] ?? null,
            'online_type'        => $_POST['online_type'] ?? null,
            'disbursement_proof' => json_encode($proofPaths),
            'created_by'         => $this->userId
        ];

        if ($data['amount'] <= 0) {
            setError("Please enter a valid amount.");
            header("Location: {$GLOBALS['base_url']}/admin-view-cashout-request?id=" . $requestId);
            exit;
        }

        $result = $this->paymentModel->addPayment($data);

        if ($result['success']) {
            $request   = $this->cashoutModel->getRequestById($requestId);
            $totalPaid = $this->paymentModel->getTotalPaid($requestId);
            $dueAmount = ($request['approved_amount'] ?? $request['amount']) - $totalPaid;

            $this->cashoutModel->updateDueAmount($requestId, $dueAmount);

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

    // Approve request — roles 1 and 3 only
    public function approve()
    {
        if (!$this->canApproveOrReject()) {
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

        if (!in_array($request['display_status'], ['pending'])) {
            setError("This request cannot be approved. Current status: " . ucfirst(str_replace('_', ' ', $request['display_status'])));
            header("Location: {$GLOBALS['base_url']}/view-cashout-request?id=" . $requestId);
            exit;
        }

        $result = $this->cashoutModel->updateStatus($requestId, 'approved', $this->userId);

        if ($result) {
            // Trigger Email Notification ON APPROVAL
            $freshRequest = $this->cashoutModel->getRequestById($requestId);
            $this->mailService->sendOnApproval($freshRequest, 'approved', $this->userId);
            
            setSuccess("Request approved successfully! Ready for payment processing.");
        } else {
            setError("Failed to approve request.");
        }

        header("Location: {$GLOBALS['base_url']}/view-cashout-request?id=" . $requestId);
        exit;
    }

    // Reject request — roles 1 and 3 only
    public function reject()
    {
        if (!$this->canApproveOrReject()) {
            setError("You are not authorized to reject requests.");
            header("Location: {$GLOBALS['base_url']}/cashout-requests");
            exit;
        }

        $requestId = $_POST['id'] ?? null;
        $reason    = trim($_POST['rejection_reason'] ?? '');

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

    private function syncWithApprovalSystem($cashoutId)
    {
        $approvalRequest = $this->cashoutModel->getApprovalRequest($cashoutId);

        if ($approvalRequest && in_array($approvalRequest['status'], ['approved', 'partial', 'rejected'])) {
            // Check old status to prevent duplicate sending if already synced
            $currentData = $this->cashoutModel->getRequestById($cashoutId);
            
            // If the Home module directly inserted 'partial' (an invalid ENUM string in the cashout_requests table), 
            // the database converted it to an empty string ''. We treat an empty string as a valid transition state
            // that hasn't fired an email yet, acting essentially exactly like 'pending'.
            $wasPending = ($currentData && in_array($currentData['status'], ['pending', '']));

            $cashoutData = [
                'status'        => $approvalRequest['status'] == 'partial' ? 'approved' : $approvalRequest['status'],
                'approved_amount' => $approvalRequest['approved_amount'] ?? null,
                'approval_note' => $approvalRequest['approval_note'] ?? null,
                'approved_by'   => $approvalRequest['approved_by'] ?? null,
                'approved_at'   => $approvalRequest['approved_at'] ?? null
            ];

            $sql = "UPDATE cashout_requests SET 
                status = ?,
                approved_amount = ?,
                approval_note = ?,
                approved_by = ?,
                approved_at = ?
                WHERE id = ?";

            $stmt   = $this->db->prepare($sql);
            $status = $cashoutData['status'];
            $stmt->bind_param(
                "sssisi",
                $status,
                $cashoutData['approved_amount'],
                $cashoutData['approval_note'],
                $cashoutData['approved_by'],
                $cashoutData['approved_at'],
                $cashoutId
            );

            $executed = $stmt->execute();
            
            // Only fire if we transitioned from pending successfully to an approved/partial state
            if ($executed && $wasPending && in_array($status, ['approved'])) {
                // Fetch fresh joined data for email context
                $freshRequest = $this->cashoutModel->getRequestById($cashoutId);
                if ($freshRequest && $this->mailService) {
                    $this->mailService->sendOnApproval($freshRequest, $approvalRequest['status'], $cashoutData['approved_by']);
                }
            }

            return $executed;
        }

        return false;
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

        $canDelete   = false;
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

    // Helper method to get unique offices for filter
    private function getUniqueOffices()
    {
        $sql    = "SELECT DISTINCT office FROM cashout_requests WHERE office IS NOT NULL AND office != '' ORDER BY office";
        $result = $this->db->query($sql);

        $offices = [];
        while ($row = $result->fetch_assoc()) {
            if (strpos($row['office'], ',') !== false) {
                $parts = explode(',', $row['office']);
                foreach ($parts as $part) {
                    $part = trim($part);
                    if ($part && !in_array($part, $offices)) {
                        $offices[] = $part;
                    }
                }
            } else {
                if (!in_array($row['office'], $offices)) {
                    $offices[] = $row['office'];
                }
            }
        }

        sort($offices);
        return $offices;
    }

    // -------------------------------------------------------
    // Role helper methods
    // -------------------------------------------------------

    /**
     * Full admin access: roles 1 (Admin) and 3 (Super Admin).
     * Also includes roles 6 and 10 for viewing admin pages
     * and adding payments.
     */
    private function isAdminUser()
    {
        return in_array($this->userRole, [1, 3, 6, 10]);
    }

    /**
     * Only roles 1 and 3 may approve or reject requests.
     */
    private function canApproveOrReject()
    {
        return in_array($this->userRole, [1, 3]);
    }

    /**
     * Roles 1, 3, 6, and 10 may add payments.
     */
    private function canAddPayment()
    {
        return in_array($this->userRole, [1, 3, 6, 10]);
    }

    // -------------------------------------------------------

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

        $newFileName = 'cashout_' . time() . '_' . uniqid() . '.' . $fileExt;
        $uploadPath  = 'uploads/cashout/' . $newFileName;
        $fullPath    = __DIR__ . '/../../public/' . $uploadPath;

        $dir = dirname($fullPath);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            return ['success' => true, 'file_path' => $uploadPath];
        }

        return ['success' => false, 'message' => "Failed to upload '{$file['name']}'."];
    }

    // Download attachment
    public function downloadAttachment()
    {
        $requestId = $_GET['id'] ?? null;
        $fileIndex = isset($_GET['file']) ? (int) $_GET['file'] : 0;

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

        if (!$this->isAdminUser() && $request['requested_by'] != $this->userId) {
            setError("Unauthorized.");
            header("Location: {$GLOBALS['base_url']}/view-cashout-request?id=" . $requestId);
            exit;
        }

        $paths = json_decode($request['attachment_path'], true);
        if (!is_array($paths)) {
            $paths = [$request['attachment_path']];
        }

        if (!isset($paths[$fileIndex])) {
            setError("File not found.");
            header("Location: {$GLOBALS['base_url']}/view-cashout-request?id=" . $requestId);
            exit;
        }

        $filePath = __DIR__ . '/../../public/' . $paths[$fileIndex];

        if (file_exists($filePath)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        }

        setError("File not found on server.");
        header("Location: {$GLOBALS['base_url']}/view-cashout-request?id=" . $requestId);
        exit;
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
            'status'     => $_GET['status'] ?? null,
            'start_date' => $_GET['start_date'] ?? null,
            'end_date'   => $_GET['end_date'] ?? null
        ];

        $requests = $this->cashoutModel->getAllRequests($filters);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="cashout_requests_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        fputcsv($output, [
            'ID', 'Requester', 'Project', 'Office', 'Purpose/Paid To',
            'Amount', 'Approved Amount', 'Due Amount', 'Description',
            'Payment Method', 'Transaction Ref', 'Status', 'Approval Status',
            'Approved By', 'Rejected By', 'Rejection Reason', 'Created At'
        ]);

        foreach ($requests as $request) {
            fputcsv($output, [
                $request['id'],
                $request['requester_name'],
                $request['project_name'] ?? 'N/A',
                $request['office'] ?? 'N/A',
                $request['purpose_paid_to'],
                $request['amount'],
                $request['approved_amount'] ?? $request['amount'],
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

    // Sync all requests with approval system
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

    // Passively run sync logic for un-synced requests without redirecting, so emails are correctly dispatched
    private function syncApprovalsSilently()
    {
        $sql = "SELECT cr.id FROM cashout_requests cr
                LEFT JOIN approval_requests ar ON ar.source_table = 'cashout_requests' AND ar.source_id = cr.id
                WHERE (cr.status = 'pending' OR cr.status = '') 
                AND ar.status IN ('approved', 'partial', 'rejected')";

        $result = $this->db->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $this->syncWithApprovalSystem($row['id']);
            }
        }
    }

    public function getAllProjects()
    {
        $sql    = "SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name";
        $result = $this->db->query($sql);

        $projects = [];
        while ($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }

        return $projects;
    }
}
?>
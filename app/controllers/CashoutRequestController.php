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

        // Fund Requisitions for Accounts — ordered by urgency
        $fundRequisitions = $this->db->query("
            SELECT fr.*,
                   p.name          AS project_name,
                   d.name          AS department_name,
                   u_req.full_name AS requester_name,
                   u_app.full_name AS approved_by_name,
                   u_paid.full_name AS paid_by_name,
                   oa.asset_code,
                   oa.item_name    AS asset_name
            FROM fund_requisitions fr
            LEFT JOIN projects p     ON fr.project_id    = p.id
            LEFT JOIN departments d  ON fr.department_id = d.id
            LEFT JOIN users u_req    ON fr.requested_by  = u_req.id
            LEFT JOIN users u_app    ON fr.approved_by   = u_app.id
            LEFT JOIN users u_paid   ON fr.paid_by       = u_paid.id
            LEFT JOIN office_assets oa ON fr.asset_id    = oa.id
            ORDER BY
                FIELD(fr.status, 'approved', 'pending', 'paid', 'rejected'),
                fr.id DESC
            LIMIT 30
        ")->fetch_all(MYSQLI_ASSOC);

        $labourQuery = $this->db->query("
            SELECT lr.*,
                   p.name            AS project_name,
                   ph.phase_name     AS phase_name,
                   u_se.full_name    AS requested_by_name,
                   u_acc.full_name   AS paid_by_name,
                   u_disb.full_name  AS labor_disbursed_by_name
            FROM con_labor_requisitions lr
            LEFT JOIN projects p      ON p.id   = lr.project_id
            LEFT JOIN project_phases ph ON ph.id = lr.phase_id
            LEFT JOIN users u_se      ON u_se.id  = lr.requested_by
            LEFT JOIN users u_acc     ON u_acc.id = lr.paid_by
            LEFT JOIN users u_disb    ON u_disb.id = lr.labor_disbursed_by
            WHERE lr.status IN ('paid','labor_disbursed','partially_paid','management_approved')
            ORDER BY lr.updated_at DESC
            LIMIT 50
        ");

        $this->view("accounts/cashout/admin-request-list", [
            "current_route"    => "admin-cashout-requests",
            "page_title"       => "Cashout Requests Management",
            "requests"         => $requests,
            "statistics"       => $statistics,
            "projects"         => $projects,
            "offices"          => $offices,
            "filters"          => $filters,
            "isAdmin"          => true,
            "userRole"         => $this->userRole,
            "fundRequisitions" => $fundRequisitions,
            "labourPayments"   => $labourQuery ? $labourQuery->fetch_all(MYSQLI_ASSOC) : [],
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

        // Fetch active banks for bank transfer dropdown
        $banks = [];
        $bankResult = $this->db->query("SELECT id, bank_name, account_name, account_number, CONCAT(account_name, IF(account_number != '', CONCAT(' (', account_number, ')'), '')) as branch_name FROM company_bank_accounts WHERE status = 'active' ORDER BY is_default DESC, bank_name ASC");
        if ($bankResult) {
            while ($row = $bankResult->fetch_assoc()) {
                $banks[] = $row;
            }
        }

        $this->view("accounts/cashout/create-request", [
            "current_route" => "create-cashout-request",
            "page_title"    => "Request Cashout",
            "isAdmin"       => $this->isAdminUser(),
            "projects"      => $projects,
            "officeOptions" => $officeOptions,
            "banks"         => $banks,
            "coaList"       => getCOAExpenseList($this->db)
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
            'bank_id'        => (!empty($_POST['bank_id']) && $_POST['payment_method'] === 'bank') ? (int)$_POST['bank_id'] : null,
            'account_id'     => !empty($_POST['account_id']) ? (int)$_POST['account_id'] : null,
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

        // Authorization check — admin users, QC users, AND the request owner can view
        $canView = false;
        if ($this->isAdminUser() || $this->canApproveQC()) {
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

        $reqAmount      = (float) ($request['amount'] ?? 0);

        $this->view("accounts/cashout/view-request", [
            "current_route"      => "view-cashout-request",
            "page_title"         => "Cashout Request Details",
            "request"            => $request,
            "payments"           => $payments,
            "total_paid"         => $totalPaid,
            "approval_request"   => $approvalRequest,
            "isAdmin"            => $isAdmin,
            "canApproveOrReject" => $this->canApproveOrReject($reqAmount), 
            "canApproveQC"       => $this->canApproveQC(),
            "isSiteOperator"     => $this->isSiteOperator(),
            "isManagement"       => $this->isManagementUser(),
            "userRole"           => $this->userRole,
            "userId"             => $this->userId
        ]);
    }

    // Admin view single request - redirect directly to canonical view-cashout-request
    public function adminShow()
    {
        $id = $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            setError("Request ID missing or invalid.");
            header("Location: {$GLOBALS['base_url']}/admin-cashout-requests");
            exit;
        }

        header("Location: {$GLOBALS['base_url']}/view-cashout-request?id=" . $id);
        exit;
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

        // Mandatory Accounts Head Approval Guard
        if (!class_exists('ApprovalRequest')) {
            require_once __DIR__ . '/../models/ApprovalRequest.php';
        }
        $ahCheck = ApprovalRequest::getAccountsHeadApprovalInfo($this->db, 'cashout_requests', (int)$requestId);
        if ($ahCheck && !empty($ahCheck['has_request']) && empty($ahCheck['is_accounts_head_approved'])) {
            setError("Payment cannot be disbursed. Approval from Accounts Head is pending.");
            header("Location: {$GLOBALS['base_url']}/view-cashout-request?id={$requestId}");
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
                    header("Location: {$GLOBALS['base_url']}/view-cashout-request?id={$requestId}");
                    exit;
                }
            }
        }

        if (empty($proofPaths)) {
            setError("Disbursement proof document is required. Please upload at least one file.");
            header("Location: {$GLOBALS['base_url']}/view-cashout-request?id={$requestId}");
            exit;
        }

        // Resolve bank name from company_bank_accounts if bank_id is provided
        $bankId   = !empty($_POST['bank_id']) ? (int)$_POST['bank_id'] : null;
        $bankName = $_POST['bank_name'] ?? null;
        if ($bankId) {
            $bRow = $this->db->query("SELECT CONCAT(bank_name, ' - ', account_name) as label FROM company_bank_accounts WHERE id = {$bankId} LIMIT 1");
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
            header("Location: {$GLOBALS['base_url']}/view-cashout-request?id=" . $requestId);
            exit;
        }

        $request = $this->cashoutModel->getRequestById($requestId);
        if (!$request) {
            setError("Request not found.");
            header("Location: {$GLOBALS['base_url']}/admin-cashout-requests");
            exit;
        }

        $maxApproved = (float)($request['approved_amount'] ?? $request['amount']);
        $totalPaidSoFar = (float)$this->paymentModel->getTotalPaid($requestId);
        $remainingDue = max(0, $maxApproved - $totalPaidSoFar);

        if ((float)$data['amount'] > $remainingDue + 0.01) {
            setError("Payment amount (৳" . number_format($data['amount'], 2) . ") cannot exceed the remaining approved due (৳" . number_format($remainingDue, 2) . ").");
            header("Location: {$GLOBALS['base_url']}/view-cashout-request?id=" . $requestId);
            exit;
        }

        $result = $this->paymentModel->addPayment($data);

        if ($result['success']) {
            $totalPaid = $this->paymentModel->getTotalPaid($requestId);
            $dueAmount = max(0, $maxApproved - $totalPaid);

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

        $redirectUrl = isset($_GET['redirect']) && $_GET['redirect'] === 'client-refunds' ? '/client-refunds' : '/admin-cashout-requests';
        header("Location: {$GLOBALS['base_url']}{$redirectUrl}");
        exit;
    }

    // Approve request — roles 1 and 3 only (Management), plus QC
    public function approve()
    {
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

        $statusAction = $_POST['status'] ?? 'approved';
        
        if ($statusAction === 'qc_approved') {
            if (!$this->canApproveQC()) {
                setError("Access Denied: You are not authorized for QC approval.");
                header("Location: {$GLOBALS['base_url']}/cashout-requests");
                exit;
            }

            if ($request['display_status'] !== 'pending') {
                setError("Request must be pending for QC approval.");
                header("Location: {$GLOBALS['base_url']}/view-cashout-request?id=" . $requestId);
                exit;
            }

            $qcNote = trim($_POST['approval_note'] ?? '');
            if ($this->cashoutModel->updateQCStatus($requestId, $this->userId, $qcNote)) {
                setSuccess("Request QC Approved successfully.");
            } else {
                setError("Failed to QC approve.");
            }

        } else {
            // Management / Operation Head approval
            $reqAmount = (float)($request['amount'] ?? 0);
            $isOver1Lac = ($reqAmount > 10000.01);
            $approvalRequest = $this->cashoutModel->getApprovalRequest($requestId);

            if (!$this->canApproveOrReject($reqAmount)) {
                setError("Access Denied: Only authorized approvers can approve requests.");
                header("Location: {$GLOBALS['base_url']}/view-cashout-request?id=" . $requestId);
                exit;
            }

            if ($isOver1Lac && $this->isSiteOperator() && !$this->isManagementUser()) {
                // Operation Head is approving the > 10K prerequisite step
                $approvalNote = trim($_POST['approval_note'] ?? 'Approved by Admin & Operation Head');
                $arId = (int)($approvalRequest['id'] ?? 0);
                if ($arId) {
                    $this->approvalModel->approveOperationHead($arId, $this->userId, $approvalNote);
                } else {
                    $this->db->query("UPDATE approval_requests SET op_head_status = 'approved', op_head_by = {$this->userId}, op_head_at = NOW(), op_head_note = '{$this->db->real_escape_string($approvalNote)}', approve_role = 'accounts_head' WHERE source_table = 'cashout_requests' AND source_id = {$requestId}");
                }
                setSuccess("Request approved by Admin & Operation Head (Role 20). Forwarded to Accounts Head (Role 30) for payment verification.");
                header("Location: {$GLOBALS['base_url']}/view-cashout-request?id=" . $requestId);
                exit;
            }

            if ($isOver1Lac && $this->isManagementUser()) {
                $opStatus = strtolower($approvalRequest['op_head_status'] ?? 'pending');
                $ahStatus = strtolower($approvalRequest['accounts_head_status'] ?? 'pending');
                if ($opStatus !== 'approved') {
                    setError("Access Denied: Requests exceeding ৳10,000 must be approved by Admin & Operation Head (Role 20) first.");
                    header("Location: {$GLOBALS['base_url']}/view-cashout-request?id=" . $requestId);
                    exit;
                }
                if ($ahStatus !== 'approved') {
                    setError("Access Denied: Requests exceeding ৳10,000 must be verified & approved by Accounts Head (Role 30) before Management can approve.");
                    header("Location: {$GLOBALS['base_url']}/view-cashout-request?id=" . $requestId);
                    exit;
                }
            }

            if ($request['display_status'] === 'approved' || ($request['status'] ?? '') === 'approved') {
                setSuccess("Request approved successfully! Ready for payment processing.");
                header("Location: {$GLOBALS['base_url']}/view-cashout-request?id=" . $requestId);
                exit;
            }

            if (!in_array($request['display_status'], ['qc_approved', 'pending'])) {
                setError("This request cannot be approved. Current status: " . ucfirst(str_replace('_', ' ', $request['display_status'])));
                header("Location: {$GLOBALS['base_url']}/view-cashout-request?id=" . $requestId);
                exit;
            }

            $approvedAmount = !empty($_POST['approved_amount']) ? (float)$_POST['approved_amount'] : $reqAmount;
            $approvalNote = trim($_POST['approval_note'] ?? '');

            if ($approvedAmount > 0) {
                $this->cashoutModel->updateApprovedAmount($requestId, $approvedAmount);
            }

            $result = $this->cashoutModel->updateStatus($requestId, 'approved', $this->userId, null, $approvedAmount, $approvalNote);
            if ($result) {
                // Trigger Email Notification ON APPROVAL
                $freshRequest = $this->cashoutModel->getRequestById($requestId);
                $this->mailService->sendOnApproval($freshRequest, 'approved', $this->userId);
                
                setSuccess("Request approved successfully! Ready for payment processing.");
            } else {
                setError("Failed to approve request.");
            }
        }

        header("Location: {$GLOBALS['base_url']}/view-cashout-request?id=" . $requestId);
        exit;
    }

    // Reject request
    public function reject()
    {
        $requestId = $_POST['id'] ?? null;
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

        $reqAmount = (float)($request['amount'] ?? 0);
        $isQC = ($request['display_status'] === 'pending');
        $isMgmt = in_array($request['display_status'], ['qc_approved', 'pending']);

        if ($isQC && !$this->canApproveQC() && !$this->canApproveOrReject($reqAmount)) {
            setError("You are not authorized to reject this request.");
            header("Location: {$GLOBALS['base_url']}/view-cashout-request?id=" . $requestId);
            exit;
        }

        if ($isMgmt && !$this->canApproveOrReject($reqAmount)) {
            setError("You are not authorized to reject this request.");
            header("Location: {$GLOBALS['base_url']}/view-cashout-request?id=" . $requestId);
            exit;
        }

        $reason    = trim($_POST['rejection_reason'] ?? '');

        if (!$requestId) {
            setError("Request ID missing.");
            header("Location: {$GLOBALS['base_url']}/admin-cashout-requests");
            exit;
        }

        if (empty($reason)) {
            setError("Please provide a rejection reason.");
            header("Location: {$GLOBALS['base_url']}/view-cashout-request?id=" . $requestId);
            exit;
        }

        $result = $this->cashoutModel->updateStatus($requestId, 'rejected', $this->userId, $reason);

        if ($result) {
            setSuccess("Request rejected successfully!");
        } else {
            setError("Failed to reject request.");
        }

        header("Location: {$GLOBALS['base_url']}/view-cashout-request?id=" . $requestId);
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

    public function isSiteOperator()
    {
        $roleId = (int) $this->userRole;
        $roleName = strtolower($_SESSION['user']['role_name'] ?? '');
        return in_array($roleId, [20, 32], true)
            || strpos($roleName, 'operation head') !== false
            || strpos($roleName, 'admin & operation') !== false;
    }

    public function isManagementUser()
    {
        $roleId = (int) $this->userRole;
        $roleName = strtolower($_SESSION['user']['role_name'] ?? '');
        if (in_array($roleId, [33, 34, 35], true) || in_array($roleName, ['chief officer', 'management viewer', 'seal ceo'], true)) {
            return false;
        }
        return in_array($roleId, [1, 3, 38], true)
            || in_array($roleName, ['super admin', 'admin', 'management', 'board of director', 'board of directors'], true);
    }

    /**
     * Full admin access: roles 1 (Admin), 3 (Super Admin), 33 (Chief Officer), 34 (Management Viewer), 35 (SEAL CEO),
     * Accounts (6, 30), Marketing (10), and Operation Head (20, 32).
     */
    private function isAdminUser()
    {
        $roleId = (int) $this->userRole;
        return in_array($roleId, [1, 3, 6, 10, 20, 30, 32, 33, 34, 35, 38], true)
            || $this->isManagementUser()
            || $this->isSiteOperator();
    }

    /**
     * Management (roles 1, 3) can approve any amount.
     * Operation Head (roles 20, 32) can approve requests <= 10,000.
     */
    public function canApproveOrReject($amount = null)
    {
        $roleId = (int) $this->userRole;
        $roleName = strtolower($_SESSION['user']['role_name'] ?? '');
        if (in_array($roleId, [33, 34, 35], true) || in_array($roleName, ['chief officer', 'management viewer', 'seal ceo'], true)) {
            return false;
        }

        if ($this->isManagementUser()) {
            return true;
        }

        if ($this->isSiteOperator()) {
            return true;
        }

        return false;
    }

    private function canApproveQC()
    {
        $roleId = (int) $this->userRole;
        $roleName = strtolower($_SESSION['user']['role_name'] ?? '');
        if (in_array($roleId, [33, 34, 35], true) || in_array($roleName, ['chief officer', 'management viewer', 'seal ceo'], true)) {
            return false;
        }
        if (in_array($this->userRole, [1, 24, 25])) return true;
        return (str_contains($roleName, 'qc & internal audit') || hasPermission('QC & Internal Audit'));
    }

    /**
     * Roles 1, 3, 6, and 10 may add payments.
     */
    private function canAddPayment()
    {
        return in_array($this->userRole, [1, 3, 6, 10, 30]);
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

        // Fund Requisitions
        $fundRequisitions = $this->db->query("
            SELECT fr.*,
                   p.name          AS project_name,
                   d.name          AS department_name,
                   u_req.full_name AS requester_name,
                   u_app.full_name AS approved_by_name,
                   u_paid.full_name AS paid_by_name
            FROM fund_requisitions fr
            LEFT JOIN projects p     ON fr.project_id    = p.id
            LEFT JOIN departments d  ON fr.department_id = d.id
            LEFT JOIN users u_req    ON fr.requested_by  = u_req.id
            LEFT JOIN users u_app    ON fr.approved_by   = u_app.id
            LEFT JOIN users u_paid   ON fr.paid_by       = u_paid.id
            ORDER BY fr.id DESC
        ")->fetch_all(MYSQLI_ASSOC);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="cashout_and_fund_requisitions_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        // UTF-8 BOM for Excel compatibility
        fputs($output, "\xEF\xBB\xBF");

        // ============================================================
        // SECTION 1: Cashout Requests
        // ============================================================
        fputcsv($output, ['=== CASHOUT REQUESTS ===']);
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

        // ============================================================
        // SECTION 2: Fund Requisitions
        // ============================================================
        fputcsv($output, []); // blank separator row
        fputcsv($output, ['=== FUND REQUISITIONS ===']);
        fputcsv($output, [
            'REQ ID', 'Date', 'Requester', 'Department', 'Project',
            'Type', 'Category', 'Amount (BDT)', 'Purpose',
            'Status', 'Approved By', 'Approved At',
            'Payment Method', 'Paid By', 'Paid At',
            'Settlement Status', 'Balance Returned', 'Accounts Verified',
            'Accounts Verified At'
        ]);

        foreach ($fundRequisitions as $fr) {
            fputcsv($output, [
                'REQ-' . $fr['id'],
                date('d M Y', strtotime($fr['created_at'])),
                $fr['requester_name'] ?? 'N/A',
                $fr['department_name'] ?? 'N/A',
                $fr['project_name'] ?? 'Office General',
                ucfirst(str_replace('_', ' ', $fr['requisition_type'] ?? '')),
                $fr['category'] ?? 'N/A',
                number_format((float)$fr['amount'], 2, '.', ''),
                $fr['purpose'],
                ucfirst($fr['status']),
                $fr['approved_by_name'] ?? 'N/A',
                !empty($fr['approved_at']) ? date('d M Y', strtotime($fr['approved_at'])) : 'N/A',
                $fr['payment_method'] ?? 'N/A',
                $fr['paid_by_name'] ?? 'N/A',
                !empty($fr['paid_at']) ? date('d M Y', strtotime($fr['paid_at'])) : 'N/A',
                ucfirst($fr['settlement_status'] ?? 'pending'),
                !empty($fr['balance_returned']) ? 'Yes' : 'No',
                !empty($fr['accounts_settlement_verified']) ? 'Yes' : 'No',
                !empty($fr['accounts_verified_at']) ? date('d M Y', strtotime($fr['accounts_verified_at'])) : 'N/A',
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
    // --- CSV BULK IMPORT ENDPOINTS ---
    
    public function showCSVImportForm()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->view("accounts/cashout/csv-import-form", [
            "current_route" => "cashout-csv-import-form",
            "page_title"    => "Cashout CSV Import",
            "preview_data"  => null,
            "errors"        => null,
            "summary"       => null,
            "isAdmin"       => $this->isAdminUser(),
            "userRole"      => $this->userRole
        ]);
    }

    public function previewCSVImport()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/cashout-csv-import-form");
            exit;
        }

        if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setError("Please select a valid CSV file to upload.");
            header("Location: {$GLOBALS['base_url']}/cashout-csv-import-form");
            exit;
        }

        $file = $_FILES['csv_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            setError("Only CSV files are allowed.");
            header("Location: {$GLOBALS['base_url']}/cashout-csv-import-form");
            exit;
        }

        $uploadDir = __DIR__ . '/../../public/uploads/csv_imports/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $tempName = 'cashout_import_' . date('YmdHis') . '_' . uniqid() . '.csv';
        $tempPath = $uploadDir . $tempName;

        if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
            setError("Failed to upload file. Please try again.");
            header("Location: {$GLOBALS['base_url']}/cashout-csv-import-form");
            exit;
        }

        $result = $this->cashoutModel->parseAndValidateCSV($tempPath);

        $_SESSION['cashout_csv_import_file'] = $tempPath;
        $_SESSION['cashout_csv_import_filename'] = $file['name'];

        $this->view("accounts/cashout/csv-import-form", [
            "current_route" => "cashout-csv-import-preview",
            "page_title"    => "CSV Import Preview - " . $file['name'],
            "preview_data"  => $result['valid_rows'],
            "errors"        => $result['errors'],
            "summary"       => $result['summary'],
            "filename"      => $file['name'],
            "isAdmin"       => $this->isAdminUser(),
            "userRole"      => $this->userRole
        ]);
    }

    public function executeCSVImport()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/cashout-csv-import-form");
            exit;
        }

        $tempFile = $_SESSION['cashout_csv_import_file'] ?? null;
        $filename = $_SESSION['cashout_csv_import_filename'] ?? 'unknown.csv';

        if (!$tempFile || !file_exists($tempFile)) {
            setError("Import session expired or file not found. Please re-upload the CSV file.");
            header("Location: {$GLOBALS['base_url']}/cashout-csv-import-form");
            exit;
        }

        $parseResult = $this->cashoutModel->parseAndValidateCSV($tempFile);
        $validRows = $parseResult['valid_rows'] ?? [];

        if (empty($validRows)) {
            setError("No valid rows to import. Please upload a corrected CSV file.");
            header("Location: {$GLOBALS['base_url']}/cashout-csv-import-form");
            exit;
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($validRows as $row) {
            $result = $this->cashoutModel->processCSVImportRow($row, $this->userId);
            if ($result['success']) {
                $successCount++;
            } else {
                $errorCount++;
                error_log("Cashout Import Error Row {$row['row_num']}: " . $result['message']);
            }
        }

        // Cleanup
        unlink($tempFile);
        unset($_SESSION['cashout_csv_import_file']);
        unset($_SESSION['cashout_csv_import_filename']);

        if ($errorCount === 0) {
            setSuccess("Successfully imported $successCount cashout records.");
        } else {
            setError("Import completed with $successCount successes and $errorCount failures. Check logs for details.");
        }

        header("Location: {$GLOBALS['base_url']}/admin-cashout-requests");
        exit;
    }

    public function downloadCSVTemplate()
    {
        $headers = [
            'Payment Type', 'Project Name', 'Amount', 'Date', 'Status',
            'Purpose / Description', 'Vendor Name', 'Vendor Phone', 'PO Number', 'Invoice Number',
            'Operator Name', 'File Attachment', 'Payment Method', 'Paid Amount'
        ];
        
        $sampleData = [
            ['General', 'Orchid Project', '5000', date('Y-m-d'), 'Fully Paid', 'Office Supplies', '', '', '', '', '', 'BankTransfer', '5000'],
            ['Vendor', 'Orchid Project', '15000', date('Y-m-d'), 'Paid', '', 'ABC Supplies', '01711000000', '', 'INV-001', '', '', 'Cheque', '15000'],
            ['Contractor', 'Orchid Project', '20000', date('Y-m-d'), 'Paid', 'Foundation Work', '', '', '', 'John Doe', '', 'Cash', '20000'],
        ];

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="cashouts_template.csv"');

        $fp = fopen('php://output', 'w');
        fputcsv($fp, $headers);
        foreach ($sampleData as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        exit;
    }
}
?>
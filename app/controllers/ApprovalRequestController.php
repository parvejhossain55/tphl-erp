<?php

require_once __DIR__ . '/../models/ApprovalRequest.php';

class ApprovalRequestController extends Controller
{
    protected $db;
    protected $approvalModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->approvalModel = new ApprovalRequest($this->db);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user']['id'])) {
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }
    }

    public function index()
    {
        $sessionRole = $_SESSION['user']['role_name'] ?? 'management';
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $sessionRoleClean = strtolower(trim($sessionRole));

        $roleMap = [
            'super admin'   => ['management'],
            'admin'         => ['management'],
            'hr'            => ['hr'],
            'accounts'      => ['accounts'],
            'accounts head' => ['accounts']
        ];

        // Also add the role name as it is for other roles like "QC & Internal Audit"
        $roles = $roleMap[$sessionRoleClean] ?? [$sessionRoleClean];

        // Force accounts approval role for Accounts (Role 6) and Accounts Head (Role 30)
        if (in_array($roleId, [6, 30], true) || strpos($sessionRoleClean, 'account') !== false) {
            $roles = ['accounts'];
        }
        
        // Let's add management for Admins and Site Operators / Operation Heads
        if(in_array($roleId, [1, 3, 20])) {
            $roles[] = 'management';
        }

        // Fetch pending requests for the role (increase limit for list page)
        $pendingRequests = $this->approvalModel->getPendingForRole($roles, 100);

        $this->view("approval-requests", [
            "current_route"   => "approval-requests",
            "page_title"      => "Pending Approval Requests",
            "pendingRequests" => $pendingRequests
        ]);
    }

    /**
     * All System Approval Requests & Payout Tracking Dashboard
     */
    public function allApprovalRequests()
    {
        $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $sessionRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
        $isChiefOfficer = (in_array($userRoleId, [33, 34, 35], true) || in_array($sessionRoleName, ['chief officer', 'management viewer', 'seal ceo'], true));

        $status = $_GET['status'] ?? '';
        if ($status === '' && $isChiefOfficer) {
            $status = 'all_pending';
        }
        $department = $_GET['department'] ?? '';
        $sourceTable = $_GET['source_table'] ?? '';
        $search = trim($_GET['search'] ?? $_GET['request_no'] ?? '');

        $filters = [
            'status'       => $status,
            'department'   => $department,
            'source_table' => $sourceTable,
            'search'       => $search,
            'user_id'      => $userId,
            'role_id'      => $userRoleId,
            'role_name'    => $sessionRoleName
        ];

        // Fetch dynamic list of departments from departments table
        $departmentsList = [];
        $deptRes = $this->db->query("SELECT DISTINCT name FROM departments ORDER BY name ASC");
        if ($deptRes) {
            while ($dRow = $deptRes->fetch_assoc()) {
                $departmentsList[] = $dRow['name'];
            }
        }

        // Fetch COA Expense list for Accounts Head modal mapping
        $coaList = [];
        $coaRes = $this->db->query("SELECT id, account_code, account_name, account_type FROM chart_of_accounts WHERE account_type IN ('Expense', 'Asset') ORDER BY account_type, account_code ASC");
        if ($coaRes) {
            while ($cRow = $coaRes->fetch_assoc()) {
                $coaList[] = $cRow;
            }
        }

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25;
        if (!in_array($perPage, [25, 50, 70, 100, 200], true)) {
            $perPage = 25;
        }
        $offset = ($page - 1) * $perPage;

        $totalRecords = $this->approvalModel->countAllApprovalHistory($filters);
        $totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $perPage) : 1;
        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        $metrics = $this->approvalModel->getApprovalDashboardMetrics();
        $moduleMetrics = $this->approvalModel->getModuleExpenseBreakdown($filters);
        $history = $this->approvalModel->getAllApprovalHistory($filters, $perPage, $offset);

        $this->view("approvals/all-approval-requests", [
            "current_route"   => "all-approval-requests",
            "page_title"      => "All System Approval Requests & Payout Tracking",
            "companyMetrics"  => $metrics,
            "moduleMetrics"   => $moduleMetrics,
            "approvalHistory" => $history,
            "departmentsList" => $departmentsList,
            "coaList"         => $coaList,
            "filters"         => $filters,
            "pagination"      => [
                "currentPage"  => $page,
                "perPage"      => $perPage,
                "totalRecords" => $totalRecords,
                "totalPages"   => $totalPages,
                "startRecord"  => $totalRecords > 0 ? $offset + 1 : 0,
                "endRecord"    => min($offset + $perPage, $totalRecords)
            ]
        ]);
    }

    /**
     * Office & Branch Disbursement Audit Dashboard
     * Accessible by: Accounts Head (Role 30), Accounts (Role 6), Management (Role 1, 3), Role 20 (Admin & Op Head), Role 24 (Audit)
     */
    public function branchDisbursements()
    {
        $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $userSecRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
        $userPriRoleId = (int)($_SESSION['user']['primary_role_id'] ?? 0);
        $allUserRoles = array_filter([$userRoleId, $userSecRoleId, $userPriRoleId]);
        $sessionRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));

        $isManagement = count(array_intersect($allUserRoles, [1, 3, 33, 34, 35])) > 0 || strpos($sessionRoleName, 'admin') !== false || strpos($sessionRoleName, 'management') !== false;
        $isAccounts = count(array_intersect($allUserRoles, [6, 30, 36])) > 0 || strpos($sessionRoleName, 'account') !== false;
        $isRole20 = in_array(20, $allUserRoles, true) || in_array(32, $allUserRoles, true) || strpos($sessionRoleName, 'operation') !== false;
        $isRole24 = count(array_intersect($allUserRoles, [24, 13, 25])) > 0 || strpos($sessionRoleName, 'audit') !== false || strpos($sessionRoleName, 'qc') !== false;

        if (!$isManagement && !$isAccounts && !$isRole20 && !$isRole24) {
            $_SESSION['error'] = "Access denied. Only Accounts Head, Accounts, Management, Operation Head (Role 20), and Audit (Role 24) can access this page.";
            header("Location: {$GLOBALS['base_url']}/home");
            exit;
        }

        $filters = [
            'branch_id'    => $_GET['branch_id'] ?? '',
            'disburser_id' => $_GET['disburser_id'] ?? '',
            'module'       => $_GET['module'] ?? '',
            'date_from'    => $_GET['date_from'] ?? '',
            'date_to'      => $_GET['date_to'] ?? '',
            'search'       => trim($_GET['search'] ?? '')
        ];

        // Fetch office branches list
        $branchesList = [];
        $bRes = $this->db->query("SELECT id, name FROM office_branches ORDER BY id ASC");
        if ($bRes) {
            while ($b = $bRes->fetch_assoc()) {
                $branchesList[] = $b;
            }
        }

        // Fetch summary overview (branch cards + company totals)
        $overview = $this->approvalModel->getBranchDisbursementSummary($filters);

        // Fetch all matching disbursement transactions
        $allTransactions = $this->approvalModel->getBranchDisbursementTransactions($filters);
        $totalTransactionsCount = count($allTransactions);

        // Pagination (50 items per page for snappy rendering)
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 50;
        $totalPages = max(1, (int)ceil($totalTransactionsCount / $perPage));
        if ($page > $totalPages && $totalTransactionsCount > 0) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;
        $transactions = array_slice($allTransactions, $offset, $perPage);

        // Fetch list of disbursing officers for the dropdown filter
        $disbursersList = $this->approvalModel->getDisbursingOfficersList();

        $this->view("approvals/branch-disbursements", [
            "current_route"          => "branch-disbursements",
            "page_title"             => "Office & Branch Disbursement Audit",
            "overview"               => $overview,
            "branchSummary"          => $overview,
            "transactions"           => $transactions,
            "totalTransactionsCount" => $totalTransactionsCount,
            "currentPage"            => $page,
            "totalPages"             => $totalPages,
            "perPage"                => $perPage,
            "branchesList"           => $branchesList,
            "disbursersList"         => $disbursersList,
            "officers"               => $disbursersList,
            "filters"                => $filters
        ]);
    }

    public function batchProcessApprovalRequests()
    {
        ob_start();
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $input = file_get_contents('php://input');
        $payload = json_decode($input, true);

        if (empty($payload) || empty($payload['request_ids']) || !is_array($payload['request_ids'])) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'No approval requests selected.']);
            exit;
        }

        $action = strtolower($payload['action'] ?? 'approve');
        $status = $action === 'reject' ? 'rejected' : 'approved';
        $note = trim($payload['note'] ?? ('Bulk ' . ucfirst($action) . ' by Management'));
        $requestIds = array_map('intval', $payload['request_ids']);

        $processedCount = 0;
        $failedCount = 0;

        $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $sessionRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));

        if (in_array($userRoleId, [33, 34, 35], true) || in_array($sessionRoleName, ['chief officer', 'management viewer', 'seal ceo'], true)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'This role is not authorized to approve or reject requests (View-Only Access).']);
            exit;
        }

        $isManagement = in_array($userRoleId, [1, 3], true) || in_array($sessionRoleName, ['super admin', 'admin', 'management'], true);
        $isSiteOperator = in_array($userRoleId, [20, 32], true) || strpos($sessionRoleName, 'operation head') !== false || strpos($sessionRoleName, 'admin & operation') !== false;
        $userName = strtolower(trim($_SESSION['user']['user_name'] ?? ''));
        $userFullName = strtolower(trim($_SESSION['user']['full_name'] ?? ''));
        $isDesignatedAh = ($userId === 247 || $userId === 1309 || strpos($userFullName, 'mamun') !== false) 
            && ($userId !== 207 && strpos($userName, 'accounts') === false && strpos($userFullName, 'accounts') === false);
        $isAccountsHead = ($userRoleId === 30 && $isDesignatedAh);
        $userId = (int)($_SESSION['user']['id'] ?? 0);

        foreach ($requestIds as $id) {
            try {
                $req = $this->approvalModel->find($id);
                if (!$req) {
                    $failedCount++;
                    continue;
                }
                $sourceTable = $req['source_table'] ?? '';
                $reqAppRole = strtolower($req['approve_role'] ?? 'management');
                $reqAhStatus = strtolower($req['accounts_head_status'] ?? 'pending');

                $isOver1Lac = ((float)($req['request_amount'] ?? 0) > 100000.01);
                $reqOpStatus = strtolower($req['op_head_status'] ?? 'pending');

                // If Accounts Head is approving:
                if ($isAccountsHead) {
                    if ($reqAhStatus === 'pending' || $reqAppRole === 'accounts_head' || ($req['status'] === 'approved' && $reqAhStatus !== 'approved')) {
                        if ($action === 'reject') {
                            $res = $this->approvalModel->rejectAccountsHead($id, $userId, $note);
                        } else {
                            $res = $this->approvalModel->approveAccountsHead($id, $userId, $note);
                        }
                        if ($res) {
                            $processedCount++;
                        } else {
                            $failedCount++;
                        }
                        continue;
                    }
                }

                // If Site Operator (Role 20 / 32) is approving:
                if ($isSiteOperator && !$isManagement) {
                    if ($action === 'reject') {
                        $res = $this->approvalModel->rejectOperationHead($id, $userId, $note);
                    } else {
                        $res = $this->approvalModel->approveOperationHead($id, $userId, $note);
                    }
                    if ($res) {
                        $processedCount++;
                    } else {
                        $failedCount++;
                    }
                    continue;
                }

                // If Management (Role 3 / 1) is approving:
                if ($isManagement) {
                    // For > 100,000, Operation Head must have approved first!
                    if ($isOver1Lac && $action === 'approve' && $reqOpStatus !== 'approved') {
                        $failedCount++;
                        continue; // Cannot approve > 1L request until Op Head approves
                    }
                } elseif ($req && $reqAppRole === 'management' && !$isManagement && !$isSiteOperator) {
                    $failedCount++;
                    continue; // Accounts/non-management users cannot approve Management requests
                }
                $res = $this->approvalModel->updateStatus($id, $status, null, null, $note);
                if ($res) {
                    $processedCount++;
                } else {
                    $failedCount++;
                }
            } catch (Throwable $e) {
                $failedCount++;
            }
        }

        $actionText = $action === 'reject' ? 'rejected' : 'approved';
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => "Successfully $actionText $processedCount request(s). " . ($failedCount > 0 ? "($failedCount failed/skipped)" : ""),
            'processed_count' => $processedCount,
            'failed_count' => $failedCount
        ]);
        exit;
    }

    /**
     * Single Accounts Head Approval API
     */
    public function approveAccountsHead()
    {
        ob_start();
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user']['id'])) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
            exit;
        }

        $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $sessionRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
        $userName = strtolower(trim($_SESSION['user']['user_name'] ?? ''));
        $userFullName = strtolower(trim($_SESSION['user']['full_name'] ?? ''));

        // Authorization: strictly designated Accounts Head (Md. Mamunoor Rashid) or Management Admin (1, 3)
        $isDesignatedAh = ($userId === 247 || $userId === 1309 || strpos($userFullName, 'mamun') !== false) 
            && ($userId !== 207 && strpos($userName, 'accounts') === false && strpos($userFullName, 'accounts') === false);

        $isAuthorized = ($userRoleId === 30 && $isDesignatedAh) 
            || in_array($userRoleId, [1, 3], true) 
            || strpos($sessionRoleName, 'super admin') !== false;

        if (!$isAuthorized) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Unauthorized: Only Accounts Head (Md. Mamunoor Rashid [1309]) or Management can approve this stage.']);
            exit;
        }

        $input = file_get_contents('php://input');
        $payload = json_decode($input, true) ?? [];
        if (empty($payload)) {
            $payload = $_POST;
        }

        $reqId = !empty($payload['approval_request_id']) ? (int)$payload['approval_request_id'] : 0;
        $sourceTable = !empty($payload['source_table']) ? trim($payload['source_table']) : '';
        $sourceId = !empty($payload['source_id']) ? (int)$payload['source_id'] : 0;
        $note = !empty($payload['note']) ? trim($payload['note']) : 'Approved by Accounts Head';
        $coaId = !empty($payload['coa_id']) ? (int)$payload['coa_id'] : null;

        if (!$reqId && (!empty($sourceTable) && !empty($sourceId))) {
            $findStmt = $this->db->prepare("SELECT id FROM approval_requests WHERE source_table = ? AND source_id = ? ORDER BY id DESC LIMIT 1");
            if ($findStmt) {
                $findStmt->bind_param('si', $sourceTable, $sourceId);
                $findStmt->execute();
                $res = $findStmt->get_result()->fetch_assoc();
                $findStmt->close();
                if ($res) {
                    $reqId = (int)$res['id'];
                }
            }
        }

        if (!$reqId) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Approval request not found.']);
            exit;
        }

        $res = $this->approvalModel->approveAccountsHead($reqId, $userId, $note, $coaId);
        ob_clean();
        if ($res) {
            echo json_encode([
                'success' => true, 
                'message' => 'Approved by Accounts Head (Md. Mamunoor Rashid [1309]) successfully. Payment can now be disbursed.',
                'accounts_head_name' => 'Md. Mamunoor Rashid',
                'accounts_head_code' => '1309',
                'approved_at' => date('d M, Y h:i A')
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to approve request.']);
        }
        exit;
    }

    /**
     * Single Accounts Head Rejection API
     */
    public function rejectAccountsHead()
    {
        ob_start();
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user']['id'])) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
            exit;
        }

        $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $sessionRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
        $userName = strtolower(trim($_SESSION['user']['user_name'] ?? ''));
        $userFullName = strtolower(trim($_SESSION['user']['full_name'] ?? ''));

        // Authorization: strictly designated Accounts Head (Md. Mamunoor Rashid) or Management Admin (1, 3)
        $isDesignatedAh = ($userId === 247 || $userId === 1309 || strpos($userFullName, 'mamun') !== false) 
            && ($userId !== 207 && strpos($userName, 'accounts') === false && strpos($userFullName, 'accounts') === false);

        $isAuthorized = ($userRoleId === 30 && $isDesignatedAh) 
            || in_array($userRoleId, [1, 3], true) 
            || strpos($sessionRoleName, 'super admin') !== false;

        if (!$isAuthorized) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Unauthorized: Only Accounts Head (Md. Mamunoor Rashid [1309]) or Management can reject this stage.']);
            exit;
        }

        $input = file_get_contents('php://input');
        $payload = json_decode($input, true) ?? [];
        if (empty($payload)) {
            $payload = $_POST;
        }

        $reqId = !empty($payload['approval_request_id']) ? (int)$payload['approval_request_id'] : 0;
        $sourceTable = !empty($payload['source_table']) ? trim($payload['source_table']) : '';
        $sourceId = !empty($payload['source_id']) ? (int)$payload['source_id'] : 0;
        $note = !empty($payload['note']) ? trim($payload['note']) : 'Rejected by Accounts Head';

        if (!$reqId && (!empty($sourceTable) && !empty($sourceId))) {
            $findStmt = $this->db->prepare("SELECT id FROM approval_requests WHERE source_table = ? AND source_id = ? ORDER BY id DESC LIMIT 1");
            if ($findStmt) {
                $findStmt->bind_param('si', $sourceTable, $sourceId);
                $findStmt->execute();
                $res = $findStmt->get_result()->fetch_assoc();
                $findStmt->close();
                if ($res) {
                    $reqId = (int)$res['id'];
                }
            }
        }

        if (!$reqId) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Approval request not found.']);
            exit;
        }

        $res = $this->approvalModel->rejectAccountsHead($reqId, $userId, $note);
        ob_clean();
        if ($res) {
            echo json_encode([
                'success' => true, 
                'message' => 'Rejected by Accounts Head successfully.'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to reject request.']);
        }
        exit;
    }

    /**
     * Single Admin & Operation Head (Role 20 / 32) Approval API
     */
    public function approveOperationHead()
    {
        ob_start();
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user']['id'])) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
            exit;
        }

        $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $sessionRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));

        $isAuthorized = in_array($userRoleId, [20, 32, 1, 3], true) 
            || strpos($sessionRoleName, 'operation head') !== false 
            || strpos($sessionRoleName, 'admin & operation') !== false 
            || strpos($sessionRoleName, 'super admin') !== false 
            || strpos($sessionRoleName, 'admin') !== false;

        if (!$isAuthorized) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Unauthorized: Only Admin & Operation Head (Role 20/32) or Management Admin can approve this stage.']);
            exit;
        }

        $input = file_get_contents('php://input');
        $payload = json_decode($input, true) ?? [];
        if (empty($payload)) {
            $payload = $_POST;
        }

        $reqId = !empty($payload['approval_request_id']) ? (int)$payload['approval_request_id'] : 0;
        $sourceTable = !empty($payload['source_table']) ? trim($payload['source_table']) : '';
        $sourceId = !empty($payload['source_id']) ? (int)$payload['source_id'] : 0;
        $note = !empty($payload['note']) ? trim($payload['note']) : 'Approved by Admin & Operation Head';

        if (!$reqId && (!empty($sourceTable) && !empty($sourceId))) {
            $findStmt = $this->db->prepare("SELECT id FROM approval_requests WHERE source_table = ? AND source_id = ? ORDER BY id DESC LIMIT 1");
            if ($findStmt) {
                $findStmt->bind_param('si', $sourceTable, $sourceId);
                $findStmt->execute();
                $res = $findStmt->get_result()->fetch_assoc();
                $findStmt->close();
                if ($res) {
                    $reqId = (int)$res['id'];
                }
            }
        }

        if (!$reqId) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Approval request not found.']);
            exit;
        }

        $res = $this->approvalModel->approveOperationHead($reqId, $userId, $note);
        ob_clean();
        if ($res) {
            echo json_encode([
                'success' => true, 
                'message' => 'Approved by Admin & Operation Head successfully.',
                'op_head_name' => $_SESSION['user']['full_name'] ?? 'Admin & Operation Head',
                'approved_at' => date('d M, Y h:i A')
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to approve request.']);
        }
        exit;
    }

    /**
     * Single Admin & Operation Head Rejection API
     */
    public function rejectOperationHead()
    {
        ob_start();
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user']['id'])) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
            exit;
        }

        $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $sessionRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));

        $isAuthorized = in_array($userRoleId, [20, 32, 1, 3], true) 
            || strpos($sessionRoleName, 'operation head') !== false 
            || strpos($sessionRoleName, 'admin & operation') !== false 
            || strpos($sessionRoleName, 'super admin') !== false 
            || strpos($sessionRoleName, 'admin') !== false;

        if (!$isAuthorized) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Unauthorized: Only Admin & Operation Head (Role 20/32) or Management Admin can reject this stage.']);
            exit;
        }

        $input = file_get_contents('php://input');
        $payload = json_decode($input, true) ?? [];
        if (empty($payload)) {
            $payload = $_POST;
        }

        $reqId = !empty($payload['approval_request_id']) ? (int)$payload['approval_request_id'] : 0;
        $sourceTable = !empty($payload['source_table']) ? trim($payload['source_table']) : '';
        $sourceId = !empty($payload['source_id']) ? (int)$payload['source_id'] : 0;
        $note = !empty($payload['note']) ? trim($payload['note']) : 'Rejected by Admin & Operation Head';

        if (!$reqId && (!empty($sourceTable) && !empty($sourceId))) {
            $findStmt = $this->db->prepare("SELECT id FROM approval_requests WHERE source_table = ? AND source_id = ? ORDER BY id DESC LIMIT 1");
            if ($findStmt) {
                $findStmt->bind_param('si', $sourceTable, $sourceId);
                $findStmt->execute();
                $res = $findStmt->get_result()->fetch_assoc();
                $findStmt->close();
                if ($res) {
                    $reqId = (int)$res['id'];
                }
            }
        }

        if (!$reqId) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Approval request not found.']);
            exit;
        }

        $res = $this->approvalModel->rejectOperationHead($reqId, $userId, $note);
        ob_clean();
        if ($res) {
            echo json_encode([
                'success' => true, 
                'message' => 'Rejected by Admin & Operation Head successfully.'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to reject request.']);
        }
        exit;
    }

    /**
     * Single Management Approval API (Tier 2 / Final sign-off)
     */
    public function approveManagement()
    {
        ob_start();
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user']['id'])) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
            exit;
        }

        $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $sessionRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));

        $isAuthorized = in_array($userRoleId, [1, 3], true) 
            || strpos($sessionRoleName, 'super admin') !== false 
            || strpos($sessionRoleName, 'admin') !== false 
            || strpos($sessionRoleName, 'management') !== false;

        if (!$isAuthorized) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Unauthorized: Only Top Management (Role 1/3) can perform this final approval.']);
            exit;
        }

        $input = file_get_contents('php://input');
        $payload = json_decode($input, true) ?? [];
        if (empty($payload)) {
            $payload = $_POST;
        }

        $reqId = !empty($payload['approval_request_id']) ? (int)$payload['approval_request_id'] : 0;
        $sourceTable = !empty($payload['source_table']) ? trim($payload['source_table']) : '';
        $sourceId = !empty($payload['source_id']) ? (int)$payload['source_id'] : 0;
        $note = !empty($payload['note']) ? trim($payload['note']) : 'Approved by Management';
        $approvedAmount = !empty($payload['approved_amount']) ? (float)$payload['approved_amount'] : null;

        if (!$reqId && (!empty($sourceTable) && !empty($sourceId))) {
            $findStmt = $this->db->prepare("SELECT id FROM approval_requests WHERE source_table = ? AND source_id = ? ORDER BY id DESC LIMIT 1");
            if ($findStmt) {
                $findStmt->bind_param('si', $sourceTable, $sourceId);
                $findStmt->execute();
                $res = $findStmt->get_result()->fetch_assoc();
                $findStmt->close();
                if ($res) {
                    $reqId = (int)$res['id'];
                }
            }
        }

        if (!$reqId) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Approval request not found.']);
            exit;
        }

        $req = $this->approvalModel->find($reqId);
        if (!$req) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Approval request record not found.']);
            exit;
        }

        // For > ৳1,00,000: Admin & Operation Head (Role 20 / 32) must approve first (same rule as bulk approve & module pages)
        $isOver1Lac = ((float)($req['request_amount'] ?? 0) > 100000.01);
        if ($isOver1Lac && strtolower($req['op_head_status'] ?? 'pending') !== 'approved') {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Requests exceeding ৳1,00,000 must be approved by Admin & Operation Head (Role 20) before Management can approve.']);
            exit;
        }

        $res = $this->approvalModel->updateStatus($reqId, 'approved', $approvedAmount, null, $note);
        ob_clean();
        if ($res) {
            echo json_encode([
                'success' => true, 
                'message' => 'Approved by Management successfully.',
                'approver_name' => $_SESSION['user']['full_name'] ?? 'Management',
                'approved_at' => date('d M, Y h:i A')
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to approve request.']);
        }
        exit;
    }

    /**
     * Single Management Rejection API
     */
    public function rejectManagement()
    {
        ob_start();
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user']['id'])) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
            exit;
        }

        $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $sessionRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));

        $isAuthorized = in_array($userRoleId, [1, 3], true) 
            || strpos($sessionRoleName, 'super admin') !== false 
            || strpos($sessionRoleName, 'admin') !== false 
            || strpos($sessionRoleName, 'management') !== false;

        if (!$isAuthorized) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Unauthorized: Only Top Management (Role 1/3) can reject this request.']);
            exit;
        }

        $input = file_get_contents('php://input');
        $payload = json_decode($input, true) ?? [];
        if (empty($payload)) {
            $payload = $_POST;
        }

        $reqId = !empty($payload['approval_request_id']) ? (int)$payload['approval_request_id'] : 0;
        $sourceTable = !empty($payload['source_table']) ? trim($payload['source_table']) : '';
        $sourceId = !empty($payload['source_id']) ? (int)$payload['source_id'] : 0;
        $note = !empty($payload['note']) ? trim($payload['note']) : 'Rejected by Management';

        if (!$reqId && (!empty($sourceTable) && !empty($sourceId))) {
            $findStmt = $this->db->prepare("SELECT id FROM approval_requests WHERE source_table = ? AND source_id = ? ORDER BY id DESC LIMIT 1");
            if ($findStmt) {
                $findStmt->bind_param('si', $sourceTable, $sourceId);
                $findStmt->execute();
                $res = $findStmt->get_result()->fetch_assoc();
                $findStmt->close();
                if ($res) {
                    $reqId = (int)$res['id'];
                }
            }
        }

        if (!$reqId) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Approval request not found.']);
            exit;
        }

        $res = $this->approvalModel->updateStatus($reqId, 'rejected', null, null, $note);
        ob_clean();
        if ($res) {
            echo json_encode([
                'success' => true, 
                'message' => 'Rejected by Management successfully.'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to reject request.']);
        }
        exit;
    }

    /**
     * Role-based Actionable Alerts API for Reminder Modal & Notification Counter
     */
    public function getActionableAlerts()
    {
        ob_start();
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user']['id'])) {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Unauthenticated', 'total_pending' => 0]);
            exit;
        }

        $userId = (int)$_SESSION['user']['id'];
        $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $userRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));

        // Chief Officer, Management Viewer & SEAL CEO have NO approval tasks: return 0 pending, employee category, interval 0
        if (in_array($userRoleId, [33, 34, 35], true) || in_array($userRoleName, ['chief officer', 'management viewer', 'seal ceo'], true)) {
            ob_clean();
            echo json_encode([
                'success'              => true,
                'user_id'              => $userId,
                'role_id'              => $userRoleId,
                'role_category'        => 'employee',
                'role_badge'           => 'VIEW ONLY DESK',
                'interval_hours'       => 0,
                'interval_ms'          => 0,
                'headline'             => '',
                'sub_headline'         => '',
                'quote'                => '',
                'total_pending'        => 0,
                'total_pending_amount' => 0,
                'groups'               => [],
                'view_all_url'         => $GLOBALS['base_url'] . '/all-approval-requests'
            ]);
            exit;
        }

        // Determine Interval & Role Metas
        $intervalHours = 1;
        $intervalMs = 3600 * 1000;
        $roleBadge = 'APPROVAL DESK';
        $headline = 'Accuracy today protects every decision tomorrow.';
        $subHeadline = 'Complete the final checks to keep every transaction clear and accountable.';
        $quote = 'Your prompt action keeps TPHL moving with confidence.';
        $roleCategory = 'general';

        if (in_array($userRoleId, [1, 3], true) || in_array($userRoleName, ['super admin', 'admin', 'management'], true)) {
            $intervalHours = 6;
            $intervalMs = 6 * 3600 * 1000;
            $roleBadge = 'MANAGEMENT EXECUTIVE DESK';
            $headline = 'Decisive leadership steers company success.';
            $subHeadline = 'Review pending strategic approvals to maintain operational & financial momentum.';
            $quote = 'Your swift approvals empower all departments to move ahead without delay.';
            $roleCategory = 'management';
        } elseif (in_array($userRoleId, [20, 32], true) || strpos($userRoleName, 'operation head') !== false || strpos($userRoleName, 'admin & operation') !== false) {
            $intervalHours = 2;
            $intervalMs = 2 * 3600 * 1000;
            $roleBadge = 'OPERATION & SITE DESK';
            $headline = 'Active oversight drives flawless site execution.';
            $subHeadline = 'Keep project supplies, site expenses, and labor moving without delay.';
            $quote = 'Unrecorded updates can delay site progress and material deliveries.';
            $roleCategory = 'operation_head';
        } elseif (in_array($userRoleId, [15, 22], true) || strpos($userRoleName, 'project manager') !== false) {
            $intervalHours = 1;
            $intervalMs = 3600 * 1000;
            $roleBadge = 'PROJECT MANAGEMENT DESK';
            $headline = 'Site verification ensures quality and cost control.';
            $subHeadline = 'Review material purchase requests and labor sheets for your assigned projects.';
            $quote = 'Timely PM approvals keep construction works strictly on schedule.';
            $roleCategory = 'project_manager';
        } elseif (in_array($userRoleId, [6, 30], true) || strpos($userRoleName, 'account') !== false) {
            $intervalHours = 1;
            $intervalMs = 3600 * 1000;
            $roleBadge = 'ACCOUNTS & FINANCE DESK';
            $headline = 'Accuracy today protects every decision tomorrow.';
            $subHeadline = 'Complete the final checks to keep every transaction clear and accountable.';
            $quote = 'Your precision keeps company finances healthy and transparent.';
            $roleCategory = 'accounts';
        } elseif (in_array($userRoleId, [13, 24, 25], true) || strpos($userRoleName, 'qc') !== false || strpos($userRoleName, 'audit') !== false) {
            $intervalHours = 1;
            $intervalMs = 3600 * 1000;
            $roleBadge = 'QC & INTERNAL AUDIT DESK';
            $headline = 'Thorough audit safeguards company resources.';
            $subHeadline = 'Audit vendor bills, contractor measurements, and requisitions before payment.';
            $quote = 'Every verified voucher maintains organizational integrity.';
            $roleCategory = 'qc_audit';
        } elseif (in_array($userRoleId, [17, 23, 31], true) || strpos($userRoleName, 'purchase') !== false) {
            $intervalHours = 1;
            $intervalMs = 3600 * 1000;
            $roleBadge = 'PROCUREMENT & PURCHASE DESK';
            $headline = 'Prompt sourcing ensures competitive pricing & supplies.';
            $subHeadline = 'Assign executives, collect quotations, and issue POs for approved requisitions.';
            $quote = 'Swift procurement keeps site operations running on time.';
            $roleCategory = 'purchase';
        } elseif ($userRoleId === 4 || strpos($userRoleName, 'hr') !== false) {
            $intervalHours = 1;
            $intervalMs = 3600 * 1000;
            $roleBadge = 'HR & PAYROLL DESK';
            $headline = 'Timely employee support boosts workplace morale.';
            $subHeadline = 'Review advance salaries, leave applications, and manpower requests.';
            $quote = 'Prompt HR action keeps employee satisfaction high.';
            $roleCategory = 'hr';
        } else {
            // Check if this user is a designated dept head or reporting boss with subordinates
            $isDesignatedDeptHead = in_array($userRoleId, [5, 9, 10, 14], true) || (strpos($userRoleName, 'head') !== false && !in_array($userRoleId, [8, 11, 12, 16, 18, 19, 21, 23, 25]));
            $subRes = $this->db->query("
                SELECT u.id 
                FROM users u 
                JOIN employees e ON (e.user_id = u.id OR e.id = u.employee_id)
                WHERE e.reporting_user_id = {$userId} AND e.deleted_at IS NULL
                LIMIT 1
            ");
            $hasSubordinates = ($subRes && $subRes->num_rows > 0);

            if ($isDesignatedDeptHead || $hasSubordinates) {
                $intervalHours = 1;
                $intervalMs = 3600 * 1000;
                $roleBadge = 'DEPARTMENTAL HEAD DESK';
                $headline = 'Team requisitions require your timely review.';
                $subHeadline = 'Review your team\'s operational fund and resource requests.';
                $quote = 'Prompt review empowers your team to deliver excellence.';
                $roleCategory = 'dept_head';
            } else {
                // General employee with NO approval authority (Draftsman, Site Engineer, Sales Executive, etc.)
                ob_clean();
                echo json_encode([
                    'success'              => true,
                    'user_id'              => $userId,
                    'role_id'              => $userRoleId,
                    'role_category'        => 'employee',
                    'role_badge'           => 'EMPLOYEE DESK',
                    'interval_hours'       => 0,
                    'interval_ms'          => 0,
                    'headline'             => '',
                    'sub_headline'         => '',
                    'quote'                => '',
                    'total_pending'        => 0,
                    'total_pending_amount' => 0,
                    'groups'               => [],
                    'view_all_url'         => $GLOBALS['base_url'] . '/all-approval-requests'
                ]);
                exit;
            }
        }

        // Fetch user actionable approval history
        $filters = [
            'status'    => 'my_pending',
            'user_id'   => $userId,
            'role_id'   => $userRoleId,
            'role_name' => $userRoleName
        ];
        $items = $this->approvalModel->getAllApprovalHistory($filters);

        // Group into actionable categories
        $groups = [];
        $totalPendingCount = 0;
        $totalPendingAmount = 0.0;

        // Extra check for Project Manager (Role 15) & PM Head (Role 22) for material purchase requests
        if (in_array($userRoleId, [15, 22], true) || strpos($userRoleName, 'project manager') !== false) {
            $pmWhere = ($userRoleId === 15) ? "AND p.pm_id = {$userId}" : "";
            $pmPrRes = $this->db->query("
                SELECT COUNT(pr.id) as c, IFNULL(SUM(pr.management_approved_amount), 0) as amt 
                FROM purchase_requests pr 
                JOIN projects p ON p.id = pr.project_id 
                WHERE p.deleted_at IS NULL AND pr.status = 'Submitted' AND pr.approved_by_id IS NULL {$pmWhere}
            ");
            if ($pmPrRes && $prRow = $pmPrRes->fetch_assoc()) {
                $c = (int)$prRow['c'];
                if ($c > 0) {
                    $groups['material_pr'] = [
                        'key'         => 'material_pr',
                        'title'       => 'Material Purchase Requests',
                        'subtitle'    => $c . ' request' . ($c > 1 ? 's' : '') . ' awaiting PM verification',
                        'count'       => $c,
                        'amount'      => (float)$prRow['amt'],
                        'action_text' => 'REVIEW PR',
                        'action_url'  => $GLOBALS['base_url'] . '/con-purchase-requests',
                        'icon'        => 'fa-solid fa-boxes-stacked',
                        'color'       => 'primary'
                    ];
                    $totalPendingCount += $c;
                    $totalPendingAmount += (float)$prRow['amt'];
                }
            }
        }

        // Extra check for Purchase Team (Role 17, 23, 31)
        if (in_array($userRoleId, [17, 23, 31], true)) {
            $purWhere = ($userRoleId === 23) 
                ? "pr.assigned_executive_id = {$userId}" 
                : "((pr.assigned_executive_id IS NULL OR pr.assigned_executive_id = 0) OR pr.assigned_executive_id = {$userId})";
            $purRes = $this->db->query("
                SELECT COUNT(pr.id) as c, IFNULL(SUM(pr.management_approved_amount), 0) as amt 
                FROM purchase_requests pr 
                JOIN projects p ON p.id = pr.project_id 
                WHERE p.deleted_at IS NULL AND pr.status = 'Approved' AND {$purWhere}
            ");
            if ($purRes && $purRow = $purRes->fetch_assoc()) {
                $c = (int)$purRow['c'];
                if ($c > 0) {
                    $groups['purchase_assignment'] = [
                        'key'         => 'purchase_assignment',
                        'title'       => 'Approved Purchase Requests',
                        'subtitle'    => $c . ' PR' . ($c > 1 ? 's' : '') . ' awaiting quotation & CST',
                        'count'       => $c,
                        'amount'      => (float)$purRow['amt'],
                        'action_text' => 'SOURCING',
                        'action_url'  => $GLOBALS['base_url'] . '/con-purchase-requests',
                        'icon'        => 'fa-solid fa-cart-flatbed',
                        'color'       => 'info'
                    ];
                    $totalPendingCount += $c;
                    $totalPendingAmount += (float)$purRow['amt'];
                }
            }
        }

        // Categorize standard central approval items
        foreach ($items as $item) {
            $sourceTable = $item['source_table'] ?? '';
            $st = strtolower($item['status'] ?? '');
            $pst = $item['payout_status'] ?? '';
            $amt = (float)($item['request_amount'] ?? 0);
            $appAmt = (float)($item['approved_amount'] ?? $amt);

            $totalPendingCount++;
            $totalPendingAmount += ($appAmt > 0 ? $appAmt : $amt);

            // 1. Accounts Payouts / Disbursements
            if ($roleCategory === 'accounts' || in_array($pst, ['Pending Accounts Payout', 'Partially Paid'])) {
                if ($sourceTable === 'expense_adjustments' && strpos(strtolower($item['title'] ?? ''), 'return') !== false) {
                    if (!isset($groups['adj_returns'])) {
                        $groups['adj_returns'] = [
                            'key'         => 'adj_returns',
                            'title'       => 'Cash Return Verifications',
                            'subtitle'    => '0 unused cash returns waiting',
                            'count'       => 0,
                            'amount'      => 0.0,
                            'action_text' => 'VERIFY',
                            'action_url'  => $GLOBALS['base_url'] . '/all-approval-requests?source_table=expense_adjustments&status=my_pending',
                            'icon'        => 'fa-solid fa-arrow-rotate-left',
                            'color'       => 'info'
                        ];
                    }
                    $groups['adj_returns']['count']++;
                    $groups['adj_returns']['amount'] += $amt;
                    $groups['adj_returns']['subtitle'] = $groups['adj_returns']['count'] . ' unused cash return' . ($groups['adj_returns']['count'] > 1 ? 's' : '') . ' to verify (Cash-In)';
                } else {
                    if (!isset($groups['disbursements'])) {
                        $groups['disbursements'] = [
                            'key'         => 'disbursements',
                            'title'       => 'Disbursement Entries',
                            'subtitle'    => '0 disbursements waiting',
                            'count'       => 0,
                            'amount'      => 0.0,
                            'action_text' => 'PAY NOW',
                            'action_url'  => $GLOBALS['base_url'] . '/all-approval-requests?status=my_pending',
                            'icon'        => 'fa-solid fa-money-bill-wave',
                            'color'       => 'success'
                        ];
                    }
                    $groups['disbursements']['count']++;
                    $groups['disbursements']['amount'] += ($appAmt > 0 ? $appAmt : $amt);
                    $groups['disbursements']['subtitle'] = $groups['disbursements']['count'] . ' payment' . ($groups['disbursements']['count'] > 1 ? 's' : '') . ' approved for disbursement';
                }
            } elseif ($roleCategory === 'qc_audit') {
                // QC & Audit
                if (!isset($groups['audit_verifications'])) {
                    $groups['audit_verifications'] = [
                        'key'         => 'audit_verifications',
                        'title'       => 'Audit & Technical Checks',
                        'subtitle'    => '0 items awaiting audit',
                        'count'       => 0,
                        'amount'      => 0.0,
                        'action_text' => 'AUDIT',
                        'action_url'  => $GLOBALS['base_url'] . '/all-approval-requests?status=my_pending',
                        'icon'        => 'fa-solid fa-clipboard-check',
                        'color'       => 'warning'
                    ];
                }
                $groups['audit_verifications']['count']++;
                $groups['audit_verifications']['amount'] += $amt;
                $groups['audit_verifications']['subtitle'] = $groups['audit_verifications']['count'] . ' bill' . ($groups['audit_verifications']['count'] > 1 ? 's' : '') . ' & requisitions waiting for audit review';
            } elseif ($roleCategory === 'project_manager') {
                // PM Labor bills
                if ($sourceTable === 'con_labor_requisitions') {
                    if (!isset($groups['pm_labor'])) {
                        $groups['pm_labor'] = [
                            'key'         => 'pm_labor',
                            'title'       => 'Labor Bill Requisitions',
                            'subtitle'    => '0 labor bills waiting for PM check',
                            'count'       => 0,
                            'amount'      => 0.0,
                            'action_text' => 'VERIFY',
                            'action_url'  => $GLOBALS['base_url'] . '/all-approval-requests?source_table=con_labor_requisitions&status=my_pending',
                            'icon'        => 'fa-solid fa-person-digging',
                            'color'       => 'warning'
                        ];
                    }
                    $groups['pm_labor']['count']++;
                    $groups['pm_labor']['amount'] += $amt;
                    $groups['pm_labor']['subtitle'] = $groups['pm_labor']['count'] . ' labor bill' . ($groups['pm_labor']['count'] > 1 ? 's' : '') . ' awaiting site verification';
                }
            } elseif ($roleCategory === 'management' || $roleCategory === 'operation_head') {
                // Management Approvals
                if (!isset($groups['management_approvals'])) {
                    $groups['management_approvals'] = [
                        'key'         => 'management_approvals',
                        'title'       => 'Pending Management Approvals',
                        'subtitle'    => '0 approvals waiting',
                        'count'       => 0,
                        'amount'      => 0.0,
                        'action_text' => 'REVIEW',
                        'action_url'  => $GLOBALS['base_url'] . '/all-approval-requests?status=my_pending',
                        'icon'        => 'fa-solid fa-stamp',
                        'color'       => 'primary'
                    ];
                }
                $groups['management_approvals']['count']++;
                $groups['management_approvals']['amount'] += $amt;
                $groups['management_approvals']['subtitle'] = $groups['management_approvals']['count'] . ' requisition' . ($groups['management_approvals']['count'] > 1 ? 's' : '') . ' awaiting your approval';
            } else {
                // Dept Head / HR / General
                if (!isset($groups['general_requisitions'])) {
                    $groups['general_requisitions'] = [
                        'key'         => 'general_requisitions',
                        'title'       => 'Department Requisitions',
                        'subtitle'    => '0 requisitions waiting',
                        'count'       => 0,
                        'amount'      => 0.0,
                        'action_text' => 'REVIEW',
                        'action_url'  => $GLOBALS['base_url'] . '/all-approval-requests?status=my_pending',
                        'icon'        => 'fa-solid fa-folder-open',
                        'color'       => 'info'
                    ];
                }
                $groups['general_requisitions']['count']++;
                $groups['general_requisitions']['amount'] += $amt;
                $groups['general_requisitions']['subtitle'] = $groups['general_requisitions']['count'] . ' request' . ($groups['general_requisitions']['count'] > 1 ? 's' : '') . ' awaiting your sign-off';
            }
        }

        ob_clean();
        echo json_encode([
            'success'              => true,
            'user_id'              => $userId,
            'role_id'              => $userRoleId,
            'role_category'        => $roleCategory,
            'role_badge'           => $roleBadge,
            'interval_hours'       => $intervalHours,
            'interval_ms'          => $intervalMs,
            'headline'             => $headline,
            'sub_headline'         => $subHeadline,
            'quote'                => $quote,
            'total_pending'        => $totalPendingCount,
            'total_pending_amount' => $totalPendingAmount,
            'groups'               => array_values($groups),
            'view_all_url'         => $GLOBALS['base_url'] . '/all-approval-requests?status=my_pending'
        ]);
        exit;
    }
}


<?php

require_once __DIR__ . '/../models/EnrollmentApproval.php';

class EnrollmentApprovalController extends Controller
{
    protected $db;
    protected $approvalModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->approvalModel = new EnrollmentApproval($this->db);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user']['id'])) {
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }
    }

    /**
     * Vendor Enrollment Requests Dashboard
     */
    public function vendorRequests()
    {
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $userSecRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);

        $counts = $this->approvalModel->getCountsSummary('vendor', $userId, $userRoleId, $userSecRoleId);

        $tab = $_GET['tab'] ?? '';
        if (empty($tab)) {
            $tab = ($counts['actionable'] > 0) ? 'action_required' : 'pending';
        }

        $search = trim($_GET['search'] ?? '');
        $currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPageParam = $_GET['per_page'] ?? '50';
        $isAll = ($perPageParam === 'all');
        $perPage = $isAll ? 0 : (intval($perPageParam) > 0 ? intval($perPageParam) : 50);

        $totalRecords = $this->approvalModel->getRequestsCount('vendor', $tab, $userId, $userRoleId, $userSecRoleId, $search);

        if ($isAll || $perPage <= 0) {
            $totalPages = 1;
            $offset = 0;
            $requests = $this->approvalModel->getRequestsList('vendor', $tab, $userId, $userRoleId, $userSecRoleId, $search, 0, 0);
        } else {
            $totalPages = max(1, (int)ceil($totalRecords / $perPage));
            if ($currentPage > $totalPages && $totalPages > 0) $currentPage = $totalPages;
            $offset = ($currentPage - 1) * $perPage;
            $requests = $this->approvalModel->getRequestsList('vendor', $tab, $userId, $userRoleId, $userSecRoleId, $search, $perPage, $offset);
        }

        $this->view('approvals/vendor-enrollment-requests', [
            'current_route' => 'vendor-enrollment-requests',
            'page_title'    => 'Vendor Enrollment Requests',
            'requests'      => $requests,
            'counts'        => $counts,
            'currentTab'    => $tab,
            'search'        => $search,
            'currentPage'   => $currentPage,
            'totalPages'    => $totalPages,
            'totalRecords'  => $totalRecords,
            'perPage'       => $perPageParam,
            'offset'        => $offset,
            'userId'        => $userId,
            'userRoleId'    => $userRoleId,
            'userSecRoleId' => $userSecRoleId
        ]);
    }

    /**
     * Contractor Enrollment Requests Dashboard
     */
    public function contractorRequests()
    {
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $userSecRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);

        $counts = $this->approvalModel->getCountsSummary('contractor', $userId, $userRoleId, $userSecRoleId);

        $tab = $_GET['tab'] ?? '';
        if (empty($tab)) {
            $tab = ($counts['actionable'] > 0) ? 'action_required' : 'pending';
        }

        $search = trim($_GET['search'] ?? '');
        $currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPageParam = $_GET['per_page'] ?? '50';
        $isAll = ($perPageParam === 'all');
        $perPage = $isAll ? 0 : (intval($perPageParam) > 0 ? intval($perPageParam) : 50);

        $totalRecords = $this->approvalModel->getRequestsCount('contractor', $tab, $userId, $userRoleId, $userSecRoleId, $search);

        if ($isAll || $perPage <= 0) {
            $totalPages = 1;
            $offset = 0;
            $requests = $this->approvalModel->getRequestsList('contractor', $tab, $userId, $userRoleId, $userSecRoleId, $search, 0, 0);
        } else {
            $totalPages = max(1, (int)ceil($totalRecords / $perPage));
            if ($currentPage > $totalPages && $totalPages > 0) $currentPage = $totalPages;
            $offset = ($currentPage - 1) * $perPage;
            $requests = $this->approvalModel->getRequestsList('contractor', $tab, $userId, $userRoleId, $userSecRoleId, $search, $perPage, $offset);
        }

        $this->view('approvals/contractor-enrollment-requests', [
            'current_route' => 'contractor-enrollment-requests',
            'page_title'    => 'Contractor Enrollment Requests',
            'requests'      => $requests,
            'counts'        => $counts,
            'currentTab'    => $tab,
            'search'        => $search,
            'currentPage'   => $currentPage,
            'totalPages'    => $totalPages,
            'totalRecords'  => $totalRecords,
            'perPage'       => $perPageParam,
            'offset'        => $offset,
            'userId'        => $userId,
            'userRoleId'    => $userRoleId,
            'userSecRoleId' => $userSecRoleId
        ]);
    }

    /**
     * Process Approve or Reject action
     */
    public function processAction()
    {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $userSecRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);

        $type = $_POST['type'] ?? 'vendor';
        if (!in_array($type, ['vendor', 'contractor'], true)) {
            $type = 'vendor';
        }

        $id = (int)($_POST['id'] ?? 0);
        $action = trim($_POST['action'] ?? '');
        $note = trim($_POST['note'] ?? '');

        if ($id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
            $err = 'Invalid action or request ID.';
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $err]);
                exit;
            }
            setError($err);
            header("Location: {$GLOBALS['base_url']}/{$type}-enrollment-requests");
            exit;
        }

        if ($action === 'approve') {
            $result = $this->approvalModel->approve($type, $id, $userId, $userRoleId, $userSecRoleId, $note);
        } else {
            if (empty($note)) {
                $err = 'Please provide a reason for rejection.';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $err]);
                    exit;
                }
                setError($err);
                header("Location: {$GLOBALS['base_url']}/{$type}-enrollment-requests");
                exit;
            }
            $result = $this->approvalModel->reject($type, $id, $userId, $userRoleId, $userSecRoleId, $note);
        }

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
        }

        if ($result['success']) {
            setSuccess($result['message']);
        } else {
            setError($result['message']);
        }

        header("Location: {$GLOBALS['base_url']}/{$type}-enrollment-requests");
        exit;
    }

    /**
     * AJAX endpoint to fetch full request details and approval history
     */
    public function getDetails()
    {
        header('Content-Type: application/json');

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $userSecRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);

        $type = $_GET['type'] ?? 'vendor';
        $id = (int)($_GET['id'] ?? 0);

        if (!in_array($type, ['vendor', 'contractor'], true) || $id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
            exit;
        }

        $record = $this->approvalModel->getRecordById($type, $id);
        if (!$record) {
            echo json_encode(['success' => false, 'message' => 'Record not found.']);
            exit;
        }

        $record['is_actionable'] = $this->approvalModel->isActionableForUser($record, $userId, $userRoleId, $userSecRoleId);
        $logs = $this->approvalModel->getApprovalLogs($type, $id);

        echo json_encode([
            'success' => true,
            'type'    => $type,
            'data'    => $record,
            'logs'    => $logs
        ]);
        exit;
    }
}


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

        $roleMap = [
            'super admin' => ['management'],
            'admin'       => ['management'],
            'hr'          => ['hr'],
            'accounts'    => ['accounts']
        ];

        // Also add the role name as it is for other roles like "QC & Internal Audit"
        $roles = $roleMap[strtolower($sessionRole)] ?? [strtolower($sessionRole)];
        
        // Let's add management for Admins and Site Operators
        if(in_array($_SESSION['user']['role_id'] ?? 0, [1, 3]) || strpos(strtolower($sessionRole), 'site operator') !== false) {
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
        $status = $_GET['status'] ?? '';
        $department = $_GET['department'] ?? '';
        $sourceTable = $_GET['source_table'] ?? '';

        $filters = [
            'status'       => $status,
            'department'   => $department,
            'source_table' => $sourceTable
        ];

        // Fetch dynamic list of departments from departments table
        $departmentsList = [];
        $deptRes = $this->db->query("SELECT DISTINCT name FROM departments ORDER BY name ASC");
        if ($deptRes) {
            while ($dRow = $deptRes->fetch_assoc()) {
                $departmentsList[] = $dRow['name'];
            }
        }

        $metrics = $this->approvalModel->getApprovalDashboardMetrics();
        $history = $this->approvalModel->getAllApprovalHistory($filters);

        $this->view("approvals/all-approval-requests", [
            "current_route"   => "all-approval-requests",
            "page_title"      => "All System Approval Requests & Payout Tracking",
            "companyMetrics"  => $metrics,
            "approvalHistory" => $history,
            "departmentsList" => $departmentsList,
            "filters"         => $filters
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
        $userRoleName = strtolower($_SESSION['user']['role_name'] ?? '');
        $isManagement = in_array($userRoleId, [1, 3], true);
        $isSiteOperator = strpos($userRoleName, 'site operator') !== false;

        foreach ($requestIds as $id) {
            try {
                $req = $this->approvalModel->find($id);
                if ($isSiteOperator && !$isManagement) {
                    if (($req['request_amount'] ?? 0) > 100000) {
                        $failedCount++;
                        continue; // Site operator cannot approve amounts > 100000
                    }
                } elseif ($req && ($req['approve_role'] ?? 'management') === 'management' && !$isManagement) {
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
}

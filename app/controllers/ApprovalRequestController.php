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
        
        // Let's add management for Admins
        if(in_array($_SESSION['user']['role_id'] ?? 0, [1, 3])) {
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

        $metrics = $this->approvalModel->getApprovalDashboardMetrics();
        $history = $this->approvalModel->getAllApprovalHistory($filters);

        $this->view("approvals/all-approval-requests", [
            "current_route"   => "all-approval-requests",
            "page_title"      => "All System Approval Requests & Payout Tracking",
            "companyMetrics"  => $metrics,
            "approvalHistory" => $history,
            "filters"         => $filters
        ]);
    }
}

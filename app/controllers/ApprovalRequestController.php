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
        $isChiefOfficer = ($userRoleId === 33 || $sessionRoleName === 'chief officer');

        $status = $_GET['status'] ?? '';
        if ($status === '' && $isChiefOfficer) {
            $status = 'all_pending';
        }
        $department = $_GET['department'] ?? '';
        $sourceTable = $_GET['source_table'] ?? '';

        $filters = [
            'status'       => $status,
            'department'   => $department,
            'source_table' => $sourceTable,
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

        $metrics = $this->approvalModel->getApprovalDashboardMetrics();
        $moduleMetrics = $this->approvalModel->getModuleExpenseBreakdown($filters);
        $history = $this->approvalModel->getAllApprovalHistory($filters);

        $this->view("approvals/all-approval-requests", [
            "current_route"   => "all-approval-requests",
            "page_title"      => "All System Approval Requests & Payout Tracking",
            "companyMetrics"  => $metrics,
            "moduleMetrics"   => $moduleMetrics,
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
        $sessionRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));

        if ($userRoleId === 33 || $sessionRoleName === 'chief officer') {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Chief Officer is not authorized to approve or reject requests.']);
            exit;
        }

        $isManagement = in_array($userRoleId, [1, 3], true) || in_array($sessionRoleName, ['super admin', 'admin', 'management'], true);
        $isSiteOperator = in_array($userRoleId, [20, 32], true) || strpos($sessionRoleName, 'operation head') !== false || strpos($sessionRoleName, 'admin & operation') !== false;

        foreach ($requestIds as $id) {
            try {
                $req = $this->approvalModel->find($id);
                $sourceTable = $req['source_table'] ?? '';
                if ($isSiteOperator && !$isManagement) {
                    if (($req['request_amount'] ?? 0) > 100000 && $sourceTable !== 'project_other_costs') {
                        $failedCount++;
                        continue; // Site operator cannot approve amounts > 100000 (except other costs which they manage)
                    }
                } elseif ($req && ($req['approve_role'] ?? 'management') === 'management' && !$isManagement && !$isSiteOperator) {
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

        // Chief Officer has NO approval tasks: return 0 pending, employee category, interval 0
        if ($userRoleId === 33 || $userRoleName === 'chief officer') {
            ob_clean();
            echo json_encode([
                'success'              => true,
                'user_id'              => $userId,
                'role_id'              => $userRoleId,
                'role_category'        => 'employee',
                'role_badge'           => 'CHIEF OFFICER DESK',
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


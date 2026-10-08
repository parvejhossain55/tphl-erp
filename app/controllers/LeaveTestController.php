<?php

class LeaveTestController extends Controller
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * HR Leave Management Control Panel (Test)
     */
    public function index()
    {
        // 1. Fetch Summary Stats
        $pendingCount = 0;
        $approvedCount = 0;
        $rejectedCount = 0;
        $totalCount = 0;
        $today = date('Y-m-d');

        $statRes = $this->db->query("
            SELECT approval_status, COUNT(*) as cnt 
            FROM employee_leaves 
            WHERE (supervisor_status = 'approved' OR reporting_user_id IS NULL)
              AND approval_status != 'cancelled'
            GROUP BY approval_status
        ");
        if ($statRes) {
            while ($row = $statRes->fetch_assoc()) {
                $status = strtolower($row['approval_status']);
                $cnt = (int)$row['cnt'];
                $totalCount += $cnt;
                if ($status === 'pending_hr' || $status === 'pending') $pendingCount += $cnt;
                elseif ($status === 'approved' || $status === 'partial') $approvedCount += $cnt;
                elseif ($status === 'rejected') $rejectedCount += $cnt;
            }
        }

        // Today on leave count
        $todayLeaveRes = $this->db->query("
            SELECT COUNT(DISTINCT employee_id) as cnt 
            FROM employee_leaves 
            WHERE start_date <= '{$today}' AND end_date >= '{$today}' AND approval_status = 'approved'
        ");
        $todayOnLeaveCount = $todayLeaveRes ? (int)$todayLeaveRes->fetch_assoc()['cnt'] : 0;

        // 2. Fetch All Leave Applications for HR (Only supervisor-approved or direct HR requests)
        $leaves = [];
        $leavesRes = $this->db->query("
            SELECT el.*, 
                   e.employee_id AS office_id, 
                   e.full_name AS employee_name, 
                   desg.name AS designation_name, 
                   dept.name AS department_name,
                   lt.name AS leave_type_name, 
                   lt.is_paid,
                   boss.full_name AS supervisor_name
            FROM employee_leaves el
            JOIN employees e ON el.employee_id = e.id
            JOIN leave_types lt ON el.leave_type_id = lt.id
            LEFT JOIN designations desg ON e.designation_id = desg.id
            LEFT JOIN departments dept ON e.department_id = dept.id
            LEFT JOIN users boss ON boss.id = el.reporting_user_id
            WHERE (el.supervisor_status = 'approved' OR el.reporting_user_id IS NULL)
              AND el.approval_status != 'cancelled'
            ORDER BY el.id DESC
        ");
        if ($leavesRes) {
            $leaves = $leavesRes->fetch_all(MYSQLI_ASSOC);
        }

        // 3. Fetch Active Employees for Assign Modal
        $employees = [];
        $empRes = $this->db->query("
            SELECT e.id, e.employee_id AS office_id, e.full_name, dept.name AS department_name
            FROM employees e
            LEFT JOIN departments dept ON e.department_id = dept.id
            WHERE e.deleted_at IS NULL
            ORDER BY e.full_name ASC
        ");
        if ($empRes) {
            $employees = $empRes->fetch_all(MYSQLI_ASSOC);
        }

        // 4. Fetch Active Leave Types
        $leaveTypes = [];
        $typesRes = $this->db->query("
            SELECT id, name, total_days, is_paid 
            FROM leave_types 
            WHERE is_active = 1
            ORDER BY id ASC
        ");
        if ($typesRes) {
            $leaveTypes = $typesRes->fetch_all(MYSQLI_ASSOC);
        }

        // 5. Fetch Leave Balances Overview
        $balances = [];
        $balRes = $this->db->query("
            SELECT elb.id, elb.employee_id, elb.leave_type_id, elb.remaining_days,
                   e.employee_id AS office_id, e.full_name AS employee_name,
                   lt.name AS leave_type_name, lt.total_days AS quota_days,
                   dept.name AS department_name
            FROM employee_leave_balances elb
            JOIN employees e ON elb.employee_id = e.id
            JOIN leave_types lt ON elb.leave_type_id = lt.id
            LEFT JOIN departments dept ON e.department_id = dept.id
            WHERE e.deleted_at IS NULL
            ORDER BY e.full_name ASC, lt.id ASC
        ");
        if ($balRes) {
            $balances = $balRes->fetch_all(MYSQLI_ASSOC);
        }

        return $this->view('hrm/leaves/leave-management-test', [
            'current_route'       => 'leave-management-test',
            'pending_count'       => $pendingCount,
            'approved_count'      => $approvedCount,
            'rejected_count'      => $rejectedCount,
            'total_count'         => $totalCount,
            'today_on_leave_count'=> $todayOnLeaveCount,
            'leaves'              => $leaves,
            'employees'           => $employees,
            'leave_types'         => $leaveTypes,
            'balances'            => $balances
        ]);
    }

    /**
     * Store a new leave application (from HR assignment or request)
     */
    public function storeLeave()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/leave-management-test");
            exit;
        }

        $employeeId  = (int)($_POST['employee_id'] ?? 0);
        $leaveTypeId = (int)($_POST['leave_type_id'] ?? 0);
        $startDate   = trim($_POST['start_date'] ?? '');
        $endDate     = trim($_POST['end_date'] ?? '');
        $reason      = trim($_POST['reason'] ?? '');
        $status      = trim($_POST['approval_status'] ?? 'approved'); // HR default is approved
        if (!in_array($status, ['approved', 'pending'])) {
            $status = 'approved';
        }

        // 1. Basic validation
        if ($employeeId <= 0 || $leaveTypeId <= 0 || empty($startDate) || empty($endDate)) {
            $_SESSION['error'] = "All required fields (Employee, Leave Type, Start & End Date) must be filled.";
            header("Location: {$GLOBALS['base_url']}/leave-management-test");
            exit;
        }

        if ($endDate < $startDate) {
            $_SESSION['error'] = "End Date cannot be earlier than Start Date.";
            header("Location: {$GLOBALS['base_url']}/leave-management-test");
            exit;
        }

        // 2. Corner Case: Overlapping Leaves Detection
        $overlapStmt = $this->db->prepare("
            SELECT id, start_date, end_date, approval_status 
            FROM employee_leaves 
            WHERE employee_id = ? 
              AND approval_status IN ('approved', 'pending')
              AND NOT (end_date < ? OR start_date > ?)
            LIMIT 1
        ");
        $overlapStmt->bind_param("iss", $employeeId, $startDate, $endDate);
        $overlapStmt->execute();
        $overlap = $overlapStmt->get_result()->fetch_assoc();
        $overlapStmt->close();

        if ($overlap) {
            $_SESSION['error'] = "This employee already has an active ({$overlap['approval_status']}) leave from {$overlap['start_date']} to {$overlap['end_date']}. Overlapping leaves are not allowed.";
            header("Location: {$GLOBALS['base_url']}/leave-management-test");
            exit;
        }

        // 3. Corner Case: Calculate working days (Excluding Friday/Day Off)
        $workingDays = $this->calculateWorkingDays($employeeId, $startDate, $endDate);
        if ($workingDays <= 0) {
            $_SESSION['error'] = "The selected date range only covers weekly off-days. No leave balance needs to be deducted.";
            header("Location: {$GLOBALS['base_url']}/leave-management-test");
            exit;
        }

        // 4. Corner Case: Check Leave Balance
        $balStmt = $this->db->prepare("
            SELECT remaining_days 
            FROM employee_leave_balances 
            WHERE employee_id = ? AND leave_type_id = ?
        ");
        $balStmt->bind_param("ii", $employeeId, $leaveTypeId);
        $balStmt->execute();
        $balRow = $balStmt->get_result()->fetch_assoc();
        $balStmt->close();

        $remainingDays = $balRow ? (int)$balRow['remaining_days'] : 0;
        if ($status === 'approved' && $balRow && $remainingDays < $workingDays) {
            $_SESSION['error'] = "Insufficient leave balance! Employee has only {$remainingDays} days remaining, but requested {$workingDays} days.";
            header("Location: {$GLOBALS['base_url']}/leave-management-test");
            exit;
        }

        // 5. Insert Leave Record
        $approvedDays = ($status === 'approved') ? $workingDays : 0;
        $insertStmt = $this->db->prepare("
            INSERT INTO employee_leaves 
            (employee_id, leave_type_id, start_date, end_date, requested_days, approved_days, approval_status, reason, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $insertStmt->bind_param(
            "iissiiss",
            $employeeId,
            $leaveTypeId,
            $startDate,
            $endDate,
            $workingDays,
            $approvedDays,
            $status,
            $reason
        );
        $success = $insertStmt->execute();
        $insertStmt->close();

        if ($success) {
            // Deduct balance if approved
            if ($status === 'approved') {
                $this->deductBalance($employeeId, $leaveTypeId, $workingDays);
            }
            $_SESSION['success'] = "Leave successfully assigned and {$status} for {$workingDays} working day(s).";
        } else {
            $_SESSION['error'] = "Failed to save leave record: " . $this->db->error;
        }

        header("Location: {$GLOBALS['base_url']}/leave-management-test");
        exit;
    }

    /**
     * 1-Click Approve Leave (HR Final Approval)
     */
    public function approveLeave($id)
    {
        $id = (int)$id;
        $leaveStmt = $this->db->prepare("SELECT * FROM employee_leaves WHERE id = ?");
        $leaveStmt->bind_param("i", $id);
        $leaveStmt->execute();
        $leave = $leaveStmt->get_result()->fetch_assoc();
        $leaveStmt->close();

        if (!$leave) {
            $_SESSION['error'] = "Leave record not found.";
            header("Location: {$GLOBALS['base_url']}/leave-management-test");
            exit;
        }

        if ($leave['approval_status'] === 'approved') {
            $_SESSION['error'] = "Leave is already approved.";
            header("Location: {$GLOBALS['base_url']}/leave-management-test");
            exit;
        }

        // Rule: 1st approval approve na korle 2nd Approvar approve korte parbe na
        if (!empty($leave['reporting_user_id']) && $leave['supervisor_status'] !== 'approved') {
            $_SESSION['error'] = "The 1st Approver (Reporting Boss) has not approved this application yet. HR cannot approve before supervisor approval.";
            header("Location: {$GLOBALS['base_url']}/leave-management-test");
            exit;
        }

        $days = (int)$leave['requested_days'];
        $empId = (int)$leave['employee_id'];
        $typeId = (int)$leave['leave_type_id'];

        // Deduct from balance
        $this->deductBalance($empId, $typeId, $days);

        if (session_status() === PHP_SESSION_NONE) session_start();
        $hrUserId = (int)($_SESSION['user']['id'] ?? 0);

        // Update status to approved
        $upStmt = $this->db->prepare("
            UPDATE employee_leaves 
            SET approval_status = 'approved', 
                hr_status = 'approved',
                hr_approved_by = ?,
                hr_approved_at = NOW(),
                approved_days = ?, 
                updated_at = NOW() 
            WHERE id = ?
        ");
        $upStmt->bind_param("iii", $hrUserId, $days, $id);
        $upStmt->execute();
        $upStmt->close();

        $_SESSION['success'] = "Leave #{$id} has been approved successfully for {$days} day(s).";
        header("Location: {$GLOBALS['base_url']}/leave-management-test");
        exit;
    }

    /**
     * 1-Click Reject Leave (with Balance Refund if was previously approved)
     */
    public function rejectLeave($id)
    {
        $id = (int)$id;
        $leaveStmt = $this->db->prepare("SELECT * FROM employee_leaves WHERE id = ?");
        $leaveStmt->bind_param("i", $id);
        $leaveStmt->execute();
        $leave = $leaveStmt->get_result()->fetch_assoc();
        $leaveStmt->close();

        if (!$leave) {
            $_SESSION['error'] = "Leave record not found.";
            header("Location: {$GLOBALS['base_url']}/leave-management-test");
            exit;
        }

        $empId = (int)$leave['employee_id'];
        $typeId = (int)$leave['leave_type_id'];
        $approvedDays = (int)$leave['approved_days'];

        // Corner Case: If it was previously approved, refund the balance!
        if ($leave['approval_status'] === 'approved' && $approvedDays > 0) {
            $this->refundBalance($empId, $typeId, $approvedDays);
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        $hrUserId = (int)($_SESSION['user']['id'] ?? 0);

        $upStmt = $this->db->prepare("
            UPDATE employee_leaves 
            SET approval_status = 'rejected', 
                hr_status = 'rejected',
                hr_approved_by = ?,
                hr_approved_at = NOW(),
                approved_days = 0, 
                updated_at = NOW() 
            WHERE id = ?
        ");
        $upStmt->bind_param("iii", $hrUserId, $id);
        $upStmt->execute();
        $upStmt->close();

        $_SESSION['success'] = "Leave #{$id} has been rejected" . ($approvedDays > 0 ? " and {$approvedDays} day(s) refunded to balance." : ".");
        header("Location: {$GLOBALS['base_url']}/leave-management-test");
        exit;
    }

    /**
     * AJAX: Get leave balances for an employee
     */
    public function getEmployeeBalances($employeeId)
    {
        header('Content-Type: application/json');
        $employeeId = (int)$employeeId;

        $stmt = $this->db->prepare("
            SELECT elb.leave_type_id, lt.name AS leave_type_name, lt.total_days AS quota_days, elb.remaining_days
            FROM employee_leave_balances elb
            JOIN leave_types lt ON elb.leave_type_id = lt.id
            WHERE elb.employee_id = ?
            ORDER BY lt.id ASC
        ");
        $stmt->bind_param("i", $employeeId);
        $stmt->execute();
        $res = $stmt->get_result();
        $balances = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        echo json_encode([
            'success' => true,
            'balances' => $balances
        ]);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper Methods
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Calculate working days between start and end date excluding employee's weekly day-off
     */
    protected function calculateWorkingDays($employeeId, $startDate, $endDate)
    {
        // 1. Get employee custom off-days from emp_weekly_day_off
        $offDays = [];
        $stmt = $this->db->prepare("SELECT day_off_name FROM emp_weekly_day_off WHERE employee_id = ?");
        $stmt->bind_param("i", $employeeId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $offDays[] = strtolower(trim($r['day_off_name']));
        }
        $stmt->close();

        // If employee has no custom off-days set, Friday is standard default
        if (empty($offDays)) {
            $offDays = ['friday'];
        }

        $start = new DateTime($startDate);
        $end   = new DateTime($endDate);
        $end->modify('+1 day'); // inclusive

        $workingDays = 0;
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end);

        foreach ($period as $dt) {
            $dayName = strtolower($dt->format('l'));
            if (!in_array($dayName, $offDays)) {
                $workingDays++;
            }
        }

        return $workingDays;
    }

    /**
     * Deduct leave days from employee balance
     */
    protected function deductBalance($employeeId, $leaveTypeId, $days)
    {
        // Check if balance record exists
        $checkStmt = $this->db->prepare("
            SELECT id, remaining_days 
            FROM employee_leave_balances 
            WHERE employee_id = ? AND leave_type_id = ?
        ");
        $checkStmt->bind_param("ii", $employeeId, $leaveTypeId);
        $checkStmt->execute();
        $bal = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if ($bal) {
            $upStmt = $this->db->prepare("
                UPDATE employee_leave_balances 
                SET remaining_days = GREATEST(0, remaining_days - ?), updated_at = NOW() 
                WHERE id = ?
            ");
            $upStmt->bind_param("ii", $days, $bal['id']);
            $upStmt->execute();
            $upStmt->close();
        } else {
            // Get default quota from leave_types
            $qStmt = $this->db->prepare("SELECT total_days FROM leave_types WHERE id = ?");
            $qStmt->bind_param("i", $leaveTypeId);
            $qStmt->execute();
            $totalDays = (int)($qStmt->get_result()->fetch_assoc()['total_days'] ?? 20);
            $qStmt->close();

            $newRemaining = max(0, $totalDays - $days);
            $inStmt = $this->db->prepare("
                INSERT INTO employee_leave_balances 
                (employee_id, leave_type_id, remaining_days, created_at, updated_at) 
                VALUES (?, ?, ?, NOW(), NOW())
            ");
            $inStmt->bind_param("iii", $employeeId, $leaveTypeId, $newRemaining);
            $inStmt->execute();
            $inStmt->close();
        }
    }

    /**
     * Refund leave days to employee balance
     */
    protected function refundBalance($employeeId, $leaveTypeId, $days)
    {
        $stmt = $this->db->prepare("
            UPDATE employee_leave_balances 
            SET remaining_days = remaining_days + ?, updated_at = NOW() 
            WHERE employee_id = ? AND leave_type_id = ?
        ");
        $stmt->bind_param("iii", $days, $employeeId, $leaveTypeId);
        $stmt->execute();
        $stmt->close();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2-Tier Supervisor Approvals
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Supervisor 1-Click Approve Leave
     */
    public function approveSupervisorLeave($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = (int)$id;
        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);

        $leaveStmt = $this->db->prepare("SELECT * FROM employee_leaves WHERE id = ?");
        $leaveStmt->bind_param("i", $id);
        $leaveStmt->execute();
        $leave = $leaveStmt->get_result()->fetch_assoc();
        $leaveStmt->close();

        if (!$leave) {
            $_SESSION['error'] = "Leave record not found.";
            header("Location: {$GLOBALS['base_url']}/my-leaves-test");
            exit;
        }

        $isAdminOrHr = (hasPermission("HRM Management") || in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 34, 35]));
        if ($leave['reporting_user_id'] != $currentUserId && !$isAdminOrHr) {
            $_SESSION['error'] = "Unauthorized: You are not the reporting supervisor for this employee.";
            header("Location: {$GLOBALS['base_url']}/my-leaves-test");
            exit;
        }

        $upStmt = $this->db->prepare("
            UPDATE employee_leaves 
            SET supervisor_status = 'approved', 
                supervisor_approved_at = NOW(), 
                approval_status = 'pending_hr', 
                updated_at = NOW() 
            WHERE id = ?
        ");
        $upStmt->bind_param("i", $id);
        $upStmt->execute();
        $upStmt->close();

        $_SESSION['success'] = "Leave application #{$id} has been recommended/approved by you and forwarded to HR for final approval.";
        header("Location: {$GLOBALS['base_url']}/my-leaves-test");
        exit;
    }

    /**
     * Supervisor 1-Click Reject Leave
     */
    public function rejectSupervisorLeave($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = (int)$id;
        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);

        $leaveStmt = $this->db->prepare("SELECT * FROM employee_leaves WHERE id = ?");
        $leaveStmt->bind_param("i", $id);
        $leaveStmt->execute();
        $leave = $leaveStmt->get_result()->fetch_assoc();
        $leaveStmt->close();

        if (!$leave) {
            $_SESSION['error'] = "Leave record not found.";
            header("Location: {$GLOBALS['base_url']}/my-leaves-test");
            exit;
        }

        $isAdminOrHr = (hasPermission("HRM Management") || in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 34, 35]));
        if ($leave['reporting_user_id'] != $currentUserId && !$isAdminOrHr) {
            $_SESSION['error'] = "Unauthorized: You are not the reporting supervisor for this employee.";
            header("Location: {$GLOBALS['base_url']}/my-leaves-test");
            exit;
        }

        $upStmt = $this->db->prepare("
            UPDATE employee_leaves 
            SET supervisor_status = 'rejected', 
                approval_status = 'rejected', 
                updated_at = NOW() 
            WHERE id = ?
        ");
        $upStmt->bind_param("i", $id);
        $upStmt->execute();
        $upStmt->close();

        $_SESSION['success'] = "Leave application #{$id} has been rejected.";
        header("Location: {$GLOBALS['base_url']}/my-leaves-test");
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Employee Self-Service: My Leaves, Apply & Cancel
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Employee Self-Service: My Leaves Portal
     */
    public function myLeavesTest()
    {
        $employee = $this->getCurrentEmployee();
        if (!$employee) {
            echo "Employee profile not found. Please contact HR.";
            return;
        }

        $employeeId = (int)$employee['id'];
        $balances = $this->getEmployeeCoreBalances($employeeId);

        // Fetch my leaves history with 2-tier approval details
        $histStmt = $this->db->prepare("
            SELECT el.*, 
                   lt.name AS leave_type_name, lt.is_paid,
                   boss.full_name AS supervisor_name,
                   hr_user.full_name AS hr_approver_name
            FROM employee_leaves el
            JOIN leave_types lt ON el.leave_type_id = lt.id
            LEFT JOIN users boss ON boss.id = el.reporting_user_id
            LEFT JOIN users hr_user ON hr_user.id = el.hr_approved_by
            WHERE el.employee_id = ?
            ORDER BY el.id DESC
        ");
        $histStmt->bind_param("i", $employeeId);
        $histStmt->execute();
        $myLeaves = $histStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $histStmt->close();

        // Fetch subordinates' leave requests pending supervisor approval (if current user is a supervisor)
        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);
        $teamRequests = [];
        $teamStmt = $this->db->prepare("
            SELECT el.*, 
                   e.full_name AS employee_name, e.employee_id AS office_id,
                   dept.name AS department_name, desg.name AS designation_name,
                   lt.name AS leave_type_name, lt.is_paid
            FROM employee_leaves el
            JOIN employees e ON el.employee_id = e.id
            JOIN leave_types lt ON el.leave_type_id = lt.id
            LEFT JOIN departments dept ON e.department_id = dept.id
            LEFT JOIN designations desg ON e.designation_id = desg.id
            WHERE el.reporting_user_id = ?
              AND el.supervisor_status = 'pending'
              AND el.approval_status = 'pending_supervisor'
            ORDER BY el.id DESC
        ");
        $teamStmt->bind_param("i", $currentUserId);
        $teamStmt->execute();
        $teamRequests = $teamStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $teamStmt->close();

        return $this->view('employee-modules/my-leaves-test', [
            'current_route'  => 'my-leaves-test',
            'employee'       => $employee,
            'balances'       => $balances,
            'my_leaves'      => $myLeaves,
            'team_requests'  => $teamRequests
        ]);
    }

    /**
     * Employee Self-Service: Apply for Leave
     */
    public function applyLeaveSelf()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/my-leaves-test");
            exit;
        }

        $employee = $this->getCurrentEmployee();
        if (!$employee) {
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        $employeeId  = (int)$employee['id'];
        $leaveTypeId = (int)($_POST['leave_type_id'] ?? 0);
        $startDate   = trim($_POST['start_date'] ?? '');
        $endDate     = trim($_POST['end_date'] ?? '');
        $reason      = trim($_POST['reason'] ?? '');

        if ($leaveTypeId <= 0 || empty($startDate) || empty($endDate)) {
            $_SESSION['error'] = "Please fill in all required fields (Leave Type, Start Date, End Date).";
            header("Location: {$GLOBALS['base_url']}/my-leaves-test");
            exit;
        }

        if ($endDate < $startDate) {
            $_SESSION['error'] = "End Date cannot be earlier than Start Date.";
            header("Location: {$GLOBALS['base_url']}/my-leaves-test");
            exit;
        }

        // Check overlapping leave
        $overlapStmt = $this->db->prepare("
            SELECT id, start_date, end_date, approval_status 
            FROM employee_leaves 
            WHERE employee_id = ? 
              AND approval_status IN ('pending_supervisor', 'pending_hr', 'approved', 'pending')
              AND NOT (end_date < ? OR start_date > ?)
            LIMIT 1
        ");
        $overlapStmt->bind_param("iss", $employeeId, $startDate, $endDate);
        $overlapStmt->execute();
        $overlap = $overlapStmt->get_result()->fetch_assoc();
        $overlapStmt->close();

        if ($overlap) {
            $_SESSION['error'] = "You already have an active leave ({$overlap['approval_status']}) from {$overlap['start_date']} to {$overlap['end_date']}. Overlapping leaves are not allowed.";
            header("Location: {$GLOBALS['base_url']}/my-leaves-test");
            exit;
        }

        // Calculate working days (excluding employee's day-off / Friday)
        $workingDays = $this->calculateWorkingDays($employeeId, $startDate, $endDate);
        if ($workingDays <= 0) {
            $_SESSION['error'] = "The selected date range only covers your weekly off-days. No leave balance needs to be deducted.";
            header("Location: {$GLOBALS['base_url']}/my-leaves-test");
            exit;
        }

        // Check Leave Balance
        $balStmt = $this->db->prepare("SELECT remaining_days FROM employee_leave_balances WHERE employee_id = ? AND leave_type_id = ?");
        $balStmt->bind_param("ii", $employeeId, $leaveTypeId);
        $balStmt->execute();
        $balRow = $balStmt->get_result()->fetch_assoc();
        $balStmt->close();

        $remainingDays = $balRow ? (int)$balRow['remaining_days'] : 0;

        // Check if selected leave type is LWP (is_paid = 0)
        $tStmt = $this->db->prepare("SELECT is_paid, name FROM leave_types WHERE id = ?");
        $tStmt->bind_param("i", $leaveTypeId);
        $tStmt->execute();
        $typeRow = $tStmt->get_result()->fetch_assoc();
        $tStmt->close();
        $isPaid = (int)($typeRow['is_paid'] ?? 1);

        $appliedLeaveTypeId = $leaveTypeId;
        if ($isPaid == 1 && $remainingDays < $workingDays) {
            // Balance insufficient! Fallback to LWP (Leave Without Pay)
            $lwpRes = $this->db->query("SELECT id FROM leave_types WHERE LOWER(name) LIKE '%lwp%' OR is_paid = 0 ORDER BY id ASC LIMIT 1");
            $lwpId = $lwpRes ? (int)($lwpRes->fetch_assoc()['id'] ?? 5) : 5;

            $appliedLeaveTypeId = $lwpId;
            $reason = "[Auto-routed to LWP: {$typeRow['name']} balance was {$remainingDays}d, needed {$workingDays}d] " . $reason;
            $_SESSION['warning'] = "Your {$typeRow['name']} balance was insufficient ({$remainingDays} days remaining, requested {$workingDays} days). Your application has been submitted as Leave Without Pay (LWP).";
        }

        // Determine 2-tier approval route
        $reportingUserId = (!empty($employee['reporting_user_id']) && empty($employee['is_dept_head'])) 
            ? (int)$employee['reporting_user_id'] 
            : null;

        if ($reportingUserId) {
            $supervisorStatus  = 'pending';
            $overallStatus     = 'pending_supervisor';
            $supervisorRemarks = null;
        } else {
            // Auto-bypass: employee has no supervisor or is dept head
            $supervisorStatus  = 'approved';
            $overallStatus     = 'pending_hr';
            $supervisorRemarks = 'Auto-bypassed: Direct submission to HR (No reporting boss assigned)';
        }

        $hrStatus = 'pending';

        $inStmt = $this->db->prepare("
            INSERT INTO employee_leaves 
            (employee_id, reporting_user_id, leave_type_id, start_date, end_date, requested_days, approved_days,
             approval_status, supervisor_status, supervisor_remarks, hr_status, reason, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $inStmt->bind_param(
            "iiississsss",
            $employeeId,
            $reportingUserId,
            $appliedLeaveTypeId,
            $startDate,
            $endDate,
            $workingDays,
            $overallStatus,
            $supervisorStatus,
            $supervisorRemarks,
            $hrStatus,
            $reason
        );
        $success = $inStmt->execute();
        $inStmt->close();

        if ($success) {
            if (empty($_SESSION['warning'])) {
                $_SESSION['success'] = "Leave application submitted successfully for {$workingDays} working day(s). Awaiting " . ($reportingUserId ? "Reporting Boss" : "HR") . " review.";
            }
        } else {
            $_SESSION['error'] = "Failed to submit leave application: " . $this->db->error;
        }

        header("Location: {$GLOBALS['base_url']}/my-leaves-test");
        exit;
    }

    /**
     * Employee Self-Service: Cancel Pending Leave Application
     */
    public function cancelLeaveSelf($id)
    {
        $id = (int)$id;
        $employee = $this->getCurrentEmployee();
        if (!$employee) {
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        $employeeId = (int)$employee['id'];

        $stmt = $this->db->prepare("SELECT * FROM employee_leaves WHERE id = ? AND employee_id = ?");
        $stmt->bind_param("ii", $id, $employeeId);
        $stmt->execute();
        $leave = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$leave) {
            $_SESSION['error'] = "Leave record not found or unauthorized.";
            header("Location: {$GLOBALS['base_url']}/my-leaves-test");
            exit;
        }

        // Only allow cancel if still pending
        if (!in_array($leave['approval_status'], ['pending_supervisor', 'pending_hr', 'pending'])) {
            $_SESSION['error'] = "This leave application cannot be cancelled because it has already been " . htmlspecialchars($leave['approval_status']) . ".";
            header("Location: {$GLOBALS['base_url']}/my-leaves-test");
            exit;
        }

        $upStmt = $this->db->prepare("
            UPDATE employee_leaves 
            SET approval_status = 'cancelled', cancelled_at = NOW(), updated_at = NOW() 
            WHERE id = ?
        ");
        $upStmt->bind_param("i", $id);
        $upStmt->execute();
        $upStmt->close();

        $_SESSION['success'] = "Your leave application #{$id} has been cancelled successfully.";
        header("Location: {$GLOBALS['base_url']}/my-leaves-test");
        exit;
    }

    /**
     * Helper: Resolve current logged-in employee record
     */
    protected function getCurrentEmployee()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']['id'])) {
            return null;
        }

        $employeeId = $_SESSION['user']['employee_id'] ?? null;
        $userId = (int)$_SESSION['user']['id'];

        $emp = null;
        if ($employeeId) {
            $stmt = $this->db->prepare("
                SELECT e.*, dept.name AS department_name, desg.name AS designation_name,
                       boss.full_name AS reporting_boss_name, boss.email AS reporting_boss_email
                FROM employees e
                LEFT JOIN departments dept ON e.department_id = dept.id
                LEFT JOIN designations desg ON e.designation_id = desg.id
                LEFT JOIN users boss ON boss.id = e.reporting_user_id
                WHERE e.id = ? LIMIT 1
            ");
            $stmt->bind_param("i", $employeeId);
            $stmt->execute();
            $emp = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }

        if (!$emp) {
            $stmt = $this->db->prepare("
                SELECT e.*, dept.name AS department_name, desg.name AS designation_name,
                       boss.full_name AS reporting_boss_name, boss.email AS reporting_boss_email
                FROM employees e
                LEFT JOIN departments dept ON e.department_id = dept.id
                LEFT JOIN designations desg ON e.designation_id = desg.id
                LEFT JOIN users boss ON boss.id = e.reporting_user_id
                WHERE e.user_id = ? LIMIT 1
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $emp = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }

        return $emp;
    }

    /**
     * Helper: Fetch and auto-initialize the 4 core leave balances for an employee
     */
    protected function getEmployeeCoreBalances($employeeId)
    {
        $typesRes = $this->db->query("
            SELECT id, name, total_days, is_paid 
            FROM leave_types 
            WHERE is_active = 1 
            ORDER BY id ASC
        ");
        $types = $typesRes ? $typesRes->fetch_all(MYSQLI_ASSOC) : [];

        $balances = [];
        foreach ($types as $t) {
            $typeId = (int)$t['id'];
            $bStmt = $this->db->prepare("
                SELECT remaining_days 
                FROM employee_leave_balances 
                WHERE employee_id = ? AND leave_type_id = ?
            ");
            $bStmt->bind_param("ii", $employeeId, $typeId);
            $bStmt->execute();
            $row = $bStmt->get_result()->fetch_assoc();
            $bStmt->close();

            if ($row) {
                $rem = (int)$row['remaining_days'];
            } else {
                // Initialize default quota from leave_types
                $rem = (int)$t['total_days'];
                $ins = $this->db->prepare("
                    INSERT INTO employee_leave_balances 
                    (employee_id, leave_type_id, remaining_days, created_at, updated_at) 
                    VALUES (?, ?, ?, NOW(), NOW())
                ");
                $ins->bind_param("iii", $employeeId, $typeId, $rem);
                $ins->execute();
                $ins->close();
            }

            // Calculate used days from approved leaves
            $uStmt = $this->db->prepare("
                SELECT COALESCE(SUM(approved_days), 0) AS used 
                FROM employee_leaves 
                WHERE employee_id = ? AND leave_type_id = ? AND approval_status = 'approved'
            ");
            $uStmt->bind_param("ii", $employeeId, $typeId);
            $uStmt->execute();
            $usedRow = $uStmt->get_result()->fetch_assoc();
            $usedDays = (int)($usedRow['used'] ?? 0);
            $uStmt->close();

            $balances[$typeId] = [
                'type_id'        => $typeId,
                'name'           => $t['name'],
                'total_days'     => (int)$t['total_days'],
                'remaining_days' => $rem,
                'used_days'      => $usedDays,
                'is_paid'        => (int)$t['is_paid']
            ];
        }

        return $balances;
    }
}

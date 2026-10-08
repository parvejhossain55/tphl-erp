<?php

class AttendanceTestController extends Controller
{
    protected $db;
    protected $employeeModel;
    protected $syncService;

    public function __construct($db = null)
    {
        $this->db = $db ?: db();
        require_once dirname(__DIR__) . '/models/Employee.php';
        require_once dirname(__DIR__) . '/services/AttendanceTestSyncService.php';

        $this->employeeModel = new Employee($this->db);
        $this->syncService = new AttendanceTestSyncService($this->db);
    }

    /**
     * HR Admin Module: Attendance (Test)
     * Route: /attendance-test
     */
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']['id'])) {
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        $selectedDate = $_GET['date'] ?? date('Y-m-d');
        $departmentId = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null;
        $search = trim($_GET['search'] ?? '');

        // Fetch all active departments for filter
        $departments = [];
        $deptRes = $this->db->query("SELECT id, name FROM departments ORDER BY name ASC");
        if ($deptRes) {
            $departments = $deptRes->fetch_all(MYSQLI_ASSOC);
        }

        // Query daily attendance for all employees
        $sql = "
            SELECT 
                emp.id AS local_emp_id,
                emp.employee_id AS office_id,
                emp.full_name AS employee_name,
                emp.phone,
                dept.name AS department_name,
                desg.name AS designation_name,
                att.id AS attendance_id,
                att.attendance_date,
                att.punch_in_time,
                att.punch_out_time,
                COALESCE(att.today_status, 'Absent') AS today_status,
                att.shift_name,
                att.shift_start_time,
                att.late_minutes,
                att.is_late_approved,
                att.late_approval_id,
                att.working_hours,
                att.overtime_minutes
            FROM employees emp
            LEFT JOIN departments dept ON emp.department_id = dept.id
            LEFT JOIN designations desg ON emp.designation_id = desg.id
            LEFT JOIN emp_attendances att ON att.employee_id = emp.id AND att.attendance_date = ?
            WHERE emp.deleted_at IS NULL
        ";

        $params = [$selectedDate];
        $types = "s";

        if ($departmentId) {
            $sql .= " AND emp.department_id = ?";
            $types .= "i";
            $params[] = $departmentId;
        }

        if ($search !== '') {
            $sql .= " AND (emp.full_name LIKE ? OR emp.employee_id LIKE ?)";
            $types .= "ss";
            $searchLike = "%$search%";
            $params[] = $searchLike;
            $params[] = $searchLike;
        }

        $sql .= " ORDER BY dept.name ASC, emp.full_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $attendances = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Calculate Summary Stats
        $totalEmployees = count($attendances);
        $presentCount = 0;
        $lateCount = 0;
        $approvedLateCount = 0;
        $absentCount = 0;
        $leaveCount = 0;
        $dayOffCount = 0;
        $holidayCount = 0;

        foreach ($attendances as $row) {
            $st = $row['today_status'];
            if ($st === 'Present') $presentCount++;
            elseif ($st === 'Late') { 
                $presentCount++; 
                $lateCount++; 
                if (!empty($row['is_late_approved'])) $approvedLateCount++;
            }
            elseif ($st === 'Half-Day') { $presentCount++; }
            elseif ($st === 'On Leave') $leaveCount++;
            elseif ($st === 'Day Off') $dayOffCount++;
            elseif ($st === 'Holiday') $holidayCount++;
            else $absentCount++;
        }

        // Fetch pending late waiver requests for HR approval
        $hrPendingLate = [];
        $hrLateRes = $this->db->query("
            SELECT la.*, 
                   e.full_name AS employee_name, e.employee_id AS office_id,
                   dept.name AS department_name, desg.name AS designation_name,
                   boss.full_name AS supervisor_name
            FROM emp_late_approvals la
            JOIN employees e ON la.employee_id = e.id
            LEFT JOIN departments dept ON e.department_id = dept.id
            LEFT JOIN designations desg ON e.designation_id = desg.id
            LEFT JOIN users boss ON boss.id = la.reporting_user_id
            WHERE (la.supervisor_status = 'approved' OR la.reporting_user_id IS NULL)
              AND la.hr_status = 'pending'
              AND la.approval_status != 'cancelled'
            ORDER BY la.id DESC
        ");
        if ($hrLateRes) {
            $hrPendingLate = $hrLateRes->fetch_all(MYSQLI_ASSOC);
        }

        $this->view('hrm/attendances/attendance-test', [
            'current_route'   => 'attendance-test',
            'page_title'      => 'Attendance (Test)',
            'selectedDate'    => $selectedDate,
            'departmentId'    => $departmentId,
            'search'          => $search,
            'departments'     => $departments,
            'attendances'     => $attendances,
            'hr_pending_late' => $hrPendingLate,
            'summary'         => [
                'total'           => $totalEmployees,
                'present'         => $presentCount,
                'late'            => $lateCount,
                'approved_late'   => $approvedLateCount,
                'unapproved_late' => ($lateCount - $approvedLateCount),
                'absent'          => $absentCount,
                'leave'           => $leaveCount,
                'day_off'         => $dayOffCount,
                'holiday'         => $holidayCount,
            ]
        ]);
    }

    /**
     * AJAX Endpoint to Sync Punches for a Date from Tipsoi
     * Route: /api/attendance-test/sync
     */
    public function syncPunches()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']['id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $date = $_POST['date'] ?? $_GET['date'] ?? date('Y-m-d');
        $result = $this->syncService->syncDate($date);

        echo json_encode($result);
        exit;
    }

    /**
     * Employee Self-Service Module: My Attendance (Test)
     * Route: /my-attendance-test
     */
    public function myAttendanceTest()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']['id'])) {
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        $employee = $this->getCurrentEmployee();
        if (!$employee) {
            die("Employee profile not linked to your account. Please contact HR.");
        }
        $employeeId = (int)$employee['id'];

        // Month Selection (defaults to current month YYYY-MM)
        $selectedMonth = $_GET['month'] ?? date('Y-m');
        $startDate = $selectedMonth . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        // Fetch attendance logs for this employee in the month
        $attStmt = $this->db->prepare("
            SELECT *
            FROM emp_attendances
            WHERE employee_id = ? 
              AND attendance_date BETWEEN ? AND ?
              AND deleted_at IS NULL
            ORDER BY attendance_date ASC
        ");
        $attStmt->bind_param("iss", $employeeId, $startDate, $endDate);
        $attStmt->execute();
        $rawRecords = $attStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $attStmt->close();

        // Index by attendance_date for quick lookup
        $attendanceByDate = [];
        foreach ($rawRecords as $r) {
            $attendanceByDate[$r['attendance_date']] = $r;
        }

        // Fetch late waiver applications for this employee in the month
        $lateStmt = $this->db->prepare("
            SELECT * FROM emp_late_approvals 
            WHERE employee_id = ? AND attendance_date BETWEEN ? AND ?
        ");
        $lateStmt->bind_param("iss", $employeeId, $startDate, $endDate);
        $lateStmt->execute();
        $rawLateApps = $lateStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $lateStmt->close();

        $lateAppsByDate = [];
        foreach ($rawLateApps as $la) {
            $lateAppsByDate[$la['attendance_date']] = $la;
        }

        // Build full calendar month records
        $calendarDays = [];
        $current = new DateTime($startDate);
        $end = new DateTime($endDate);
        $todayStr = date('Y-m-d');

        $presentDays = 0;
        $lateDays = 0;
        $approvedLateDays = 0;
        $absentDays = 0;
        $leaveDays = 0;
        $offDays = 0;

        while ($current <= $end) {
            $dStr = $current->format('Y-m-d');
            $dayName = $current->format('D'); // e.g. Mon, Fri

            $rec = $attendanceByDate[$dStr] ?? null;
            $status = $rec['today_status'] ?? ($dStr > $todayStr ? 'Upcoming' : 'Absent');

            $lateApp = $lateAppsByDate[$dStr] ?? null;
            $isLateApproved = ($rec && !empty($rec['is_late_approved'])) || ($lateApp && $lateApp['approval_status'] === 'approved');

            if ($rec) {
                if ($status === 'Present') {
                    $presentDays++;
                } elseif ($status === 'Late') {
                    $presentDays++;
                    $lateDays++;
                    if ($isLateApproved) {
                        $approvedLateDays++;
                    }
                } elseif ($status === 'Half-Day') {
                    $presentDays++;
                } elseif ($status === 'On Leave') {
                    $leaveDays++;
                } elseif ($status === 'Day Off' || $status === 'Holiday') {
                    $offDays++;
                } else {
                    $absentDays++;
                }
            } elseif ($dStr <= $todayStr) {
                $absentDays++;
            }

            $calendarDays[] = [
                'date'             => $dStr,
                'day'              => $dayName,
                'record'           => $rec,
                'status'           => $status,
                'late_approval'    => $lateApp,
                'is_late_approved' => $isLateApproved
            ];

            $current->modify('+1 day');
        }

        // Fetch full late applications history for this employee (last 30 requests)
        $allMyLateStmt = $this->db->prepare("
            SELECT la.*, boss.full_name AS supervisor_name, hr_u.full_name AS hr_approver_name
            FROM emp_late_approvals la
            LEFT JOIN users boss ON boss.id = la.reporting_user_id
            LEFT JOIN users hr_u ON hr_u.id = la.hr_approved_by
            WHERE la.employee_id = ?
            ORDER BY la.attendance_date DESC
            LIMIT 30
        ");
        $allMyLateStmt->bind_param("i", $employeeId);
        $allMyLateStmt->execute();
        $myLateApplications = $allMyLateStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $allMyLateStmt->close();

        // Subordinates' late approval requests pending supervisor approval (if current user is a supervisor)
        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);
        $teamLateRequests = [];
        $teamLateStmt = $this->db->prepare("
            SELECT la.*, e.full_name AS employee_name, e.employee_id AS office_id,
                   dept.name AS department_name, desg.name AS designation_name
            FROM emp_late_approvals la
            JOIN employees e ON la.employee_id = e.id
            LEFT JOIN departments dept ON e.department_id = dept.id
            LEFT JOIN designations desg ON e.designation_id = desg.id
            WHERE la.reporting_user_id = ?
              AND la.supervisor_status = 'pending'
              AND la.approval_status = 'pending_supervisor'
            ORDER BY la.id DESC
        ");
        $teamLateStmt->bind_param("i", $currentUserId);
        $teamLateStmt->execute();
        $teamLateRequests = $teamLateStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $teamLateStmt->close();

        $this->view('employee-modules/my-attendance-test', [
            'current_route'        => 'my-attendance-test',
            'page_title'           => 'My Attendance (Test)',
            'employee'             => $employee,
            'selectedMonth'        => $selectedMonth,
            'calendarDays'         => $calendarDays,
            'my_late_applications' => $myLateApplications,
            'team_late_requests'   => $teamLateRequests,
            'summary'              => [
                'total_days'      => count($calendarDays),
                'present'         => $presentDays,
                'late'            => $lateDays,
                'approved_late'   => $approvedLateDays,
                'unapproved_late' => ($lateDays - $approvedLateDays),
                'absent'          => $absentDays,
                'leave'           => $leaveDays,
                'off_days'        => $offDays,
            ]
        ]);
    }

    /**
     * Reporting Boss View: Team Attendance (Employees reporting to this user)
     * Route: /my-employees-test
     */
    public function myTeamAttendance()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']['id'])) {
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        $currentUserId = (int)$_SESSION['user']['id'];
        $selectedDate = trim($_GET['date'] ?? date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
            $selectedDate = date('Y-m-d');
        }

        $search = trim($_GET['search'] ?? '');

        // 1. Fetch subordinates reporting to this user
        $sql = "
            SELECT 
                emp.id AS employee_id,
                emp.employee_id AS office_id,
                emp.full_name AS employee_name,
                emp.phone,
                emp.email,
                emp.photo,
                dept.name AS department_name,
                desg.name AS designation_name,
                att.id AS attendance_id,
                att.attendance_date,
                att.punch_in_time,
                att.punch_out_time,
                COALESCE(att.today_status, 'Absent') AS today_status,
                att.shift_name,
                att.shift_start_time,
                att.late_minutes,
                att.is_late_approved,
                att.late_approval_id,
                att.working_hours
            FROM employees emp
            LEFT JOIN departments dept ON emp.department_id = dept.id
            LEFT JOIN designations desg ON emp.designation_id = desg.id
            LEFT JOIN emp_attendances att ON att.employee_id = emp.id AND att.attendance_date = ?
            WHERE emp.reporting_user_id = ? AND emp.deleted_at IS NULL
        ";

        $params = [$selectedDate, $currentUserId];
        $types = "si";

        if ($search !== '') {
            $sql .= " AND (emp.full_name LIKE ? OR emp.employee_id LIKE ?)";
            $types .= "ss";
            $searchLike = "%$search%";
            $params[] = $searchLike;
            $params[] = $searchLike;
        }

        $sql .= " ORDER BY dept.name ASC, emp.full_name ASC";

        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            $subordinates = $res->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            $subordinates = [];
        }

        // 2. Compute Summary Metrics
        $totalTeam = count($subordinates);
        $presentCount = 0;
        $lateCount = 0;
        $approvedLateCount = 0;
        $absentCount = 0;
        $leaveCount = 0;
        $offDayCount = 0;

        foreach ($subordinates as $sub) {
            $st = $sub['today_status'];
            if ($st === 'Present') {
                $presentCount++;
            } elseif ($st === 'Late') {
                $presentCount++;
                $lateCount++;
                if (!empty($sub['is_late_approved'])) {
                    $approvedLateCount++;
                }
            } elseif ($st === 'Half-Day') {
                $presentCount++;
            } elseif ($st === 'On Leave') {
                $leaveCount++;
            } elseif ($st === 'Day Off' || $st === 'Holiday') {
                $offDayCount++;
            } else {
                $absentCount++;
            }
        }

        // 3. Fetch subordinates' pending late approval requests
        $teamLateRequests = [];
        $teamLateStmt = $this->db->prepare("
            SELECT la.*, e.full_name AS employee_name, e.employee_id AS office_id,
                   dept.name AS department_name, desg.name AS designation_name
            FROM emp_late_approvals la
            JOIN employees e ON la.employee_id = e.id
            LEFT JOIN departments dept ON e.department_id = dept.id
            LEFT JOIN designations desg ON e.designation_id = desg.id
            WHERE la.reporting_user_id = ?
              AND la.supervisor_status = 'pending'
              AND la.approval_status = 'pending_supervisor'
            ORDER BY la.id DESC
        ");
        if ($teamLateStmt) {
            $teamLateStmt->bind_param("i", $currentUserId);
            $teamLateStmt->execute();
            $teamLateRequests = $teamLateStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $teamLateStmt->close();
        }

        $this->view('employee-modules/my-team-attendance', [
            'current_route'      => 'my-employees-test',
            'page_title'         => 'My Team Attendance',
            'selectedDate'       => $selectedDate,
            'search'             => $search,
            'subordinates'       => $subordinates,
            'team_late_requests' => $teamLateRequests,
            'summary'            => [
                'total_team'      => $totalTeam,
                'present'         => $presentCount,
                'late'            => $lateCount,
                'approved_late'   => $approvedLateCount,
                'unapproved_late' => ($lateCount - $approvedLateCount),
                'absent'          => $absentCount,
                'leave'           => $leaveCount,
                'off_days'        => $offDayCount
            ]
        ]);
    }

    // =========================================================================
    // Late Attendance Approval Actions
    // =========================================================================

    /**
     * Employee Action: Submit Late Approval Application
     * Route: POST /apply-late-approval-test
     */
    public function applyLateApprovalSelf()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']['id'])) {
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/my-attendance-test");
            exit;
        }

        $employee = $this->getCurrentEmployee();
        if (!$employee) {
            $_SESSION['error'] = "Employee profile not found.";
            header("Location: {$GLOBALS['base_url']}/my-attendance-test");
            exit;
        }

        $employeeId = (int)$employee['id'];
        $attendanceDate = trim($_POST['attendance_date'] ?? '');
        $reasonType = trim($_POST['reason_type'] ?? 'Official Duty');
        $reasonDesc = trim($_POST['reason_description'] ?? '');

        if (empty($attendanceDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $attendanceDate)) {
            $_SESSION['error'] = "Invalid attendance date.";
            header("Location: {$GLOBALS['base_url']}/my-attendance-test");
            exit;
        }

        // Fetch attendance record for this date
        $attStmt = $this->db->prepare("
            SELECT id, shift_start_time, punch_in_time, late_minutes, today_status, is_late_approved 
            FROM emp_attendances 
            WHERE employee_id = ? AND attendance_date = ? 
            LIMIT 1
        ");
        $attStmt->bind_param("is", $employeeId, $attendanceDate);
        $attStmt->execute();
        $att = $attStmt->get_result()->fetch_assoc();
        $attStmt->close();

        $attId = $att ? (int)$att['id'] : null;
        $shiftStartTime = $att['shift_start_time'] ?? null;
        $punchInTime = $att['punch_in_time'] ?? null;
        $lateMinutes = (int)($att['late_minutes'] ?? 0);

        // Check if already applied
        $chkStmt = $this->db->prepare("
            SELECT id, approval_status 
            FROM emp_late_approvals 
            WHERE employee_id = ? AND attendance_date = ? 
            LIMIT 1
        ");
        $chkStmt->bind_param("is", $employeeId, $attendanceDate);
        $chkStmt->execute();
        $existing = $chkStmt->get_result()->fetch_assoc();
        $chkStmt->close();

        if ($existing && $existing['approval_status'] !== 'cancelled') {
            $_SESSION['warning'] = "You have already submitted a late approval application for " . date('d M, Y', strtotime($attendanceDate)) . " (Status: " . ucfirst(str_replace('_', ' ', $existing['approval_status'])) . ").";
            header("Location: {$GLOBALS['base_url']}/my-attendance-test");
            exit;
        }

        $reportingUserId = !empty($employee['reporting_user_id']) ? (int)$employee['reporting_user_id'] : null;
        $supervisorStatus = $reportingUserId ? 'pending' : 'approved';
        $initialApprovalStatus = $reportingUserId ? 'pending_supervisor' : 'pending_hr';

        if ($existing && $existing['approval_status'] === 'cancelled') {
            // Re-activate cancelled application
            $upStmt = $this->db->prepare("
                UPDATE emp_late_approvals 
                SET attendance_id = ?, shift_start_time = ?, punch_in_time = ?, late_minutes = ?,
                    reason_type = ?, reason_description = ?, reporting_user_id = ?,
                    supervisor_status = ?, supervisor_remarks = NULL, supervisor_approved_at = NULL,
                    hr_status = 'pending', hr_approved_by = NULL, hr_approved_at = NULL, hr_remarks = NULL,
                    approval_status = ?, cancelled_at = NULL, updated_at = NOW()
                WHERE id = ?
            ");
            $upStmt->bind_param("issississi", $attId, $shiftStartTime, $punchInTime, $lateMinutes, $reasonType, $reasonDesc, $reportingUserId, $supervisorStatus, $initialApprovalStatus, $existing['id']);
            $upStmt->execute();
            $upStmt->close();
        } else {
            $inStmt = $this->db->prepare("
                INSERT INTO emp_late_approvals 
                (employee_id, attendance_id, attendance_date, shift_start_time, punch_in_time, late_minutes, 
                 reason_type, reason_description, reporting_user_id, supervisor_status, approval_status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $inStmt->bind_param("iisssisssss", $employeeId, $attId, $attendanceDate, $shiftStartTime, $punchInTime, $lateMinutes, $reasonType, $reasonDesc, $reportingUserId, $supervisorStatus, $initialApprovalStatus);
            $inStmt->execute();
            $inStmt->close();
        }

        $_SESSION['success'] = "Late approval request for " . date('d M, Y', strtotime($attendanceDate)) . " submitted successfully.";
        header("Location: {$GLOBALS['base_url']}/my-attendance-test");
        exit;
    }

    /**
     * Employee Action: Cancel Late Approval Application
     * Route: GET/POST /cancel-late-approval-test/{id}
     */
    public function cancelLateApprovalSelf($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $id = (int)$id;
        $employee = $this->getCurrentEmployee();
        if (!$employee) {
            $_SESSION['error'] = "Unauthorized.";
            header("Location: {$GLOBALS['base_url']}/my-attendance-test");
            exit;
        }

        $stmt = $this->db->prepare("SELECT * FROM emp_late_approvals WHERE id = ? AND employee_id = ?");
        $stmt->bind_param("ii", $id, $employee['id']);
        $stmt->execute();
        $la = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$la) {
            $_SESSION['error'] = "Application not found.";
            header("Location: {$GLOBALS['base_url']}/my-attendance-test");
            exit;
        }

        if ($la['approval_status'] === 'approved') {
            $_SESSION['error'] = "Cannot cancel an already approved application.";
            header("Location: {$GLOBALS['base_url']}/my-attendance-test");
            exit;
        }

        $upStmt = $this->db->prepare("
            UPDATE emp_late_approvals 
            SET approval_status = 'cancelled', cancelled_at = NOW(), updated_at = NOW() 
            WHERE id = ?
        ");
        $upStmt->bind_param("i", $id);
        $upStmt->execute();
        $upStmt->close();

        $_SESSION['success'] = "Late approval request has been cancelled.";
        header("Location: {$GLOBALS['base_url']}/my-attendance-test");
        exit;
    }

    /**
     * Supervisor 1-Click Approve Late Request
     * Route: POST/GET /supervisor-approve-late/{id}
     */
    public function approveSupervisorLate($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $id = (int)$id;
        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);

        $stmt = $this->db->prepare("SELECT * FROM emp_late_approvals WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $la = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$la) {
            $_SESSION['error'] = "Request not found.";
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/my-employees-test"));
            exit;
        }

        $isAdminOrHr = (function_exists('hasPermission') && hasPermission("HRM Management")) || in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 34, 35]);
        if ($la['reporting_user_id'] != $currentUserId && !$isAdminOrHr) {
            $_SESSION['error'] = "Unauthorized: You are not the reporting supervisor for this employee.";
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/my-employees-test"));
            exit;
        }

        $remarks = trim($_POST['remarks'] ?? '');

        $upStmt = $this->db->prepare("
            UPDATE emp_late_approvals 
            SET supervisor_status = 'approved',
                 supervisor_approved_at = NOW(),
                 supervisor_remarks = ?,
                 approval_status = 'pending_hr',
                 updated_at = NOW()
            WHERE id = ?
        ");
        $upStmt->bind_param("si", $remarks, $id);
        $upStmt->execute();
        $upStmt->close();

        $_SESSION['success'] = "Late approval request #{$id} recommended/approved and forwarded to HR.";
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/my-employees-test"));
        exit;
    }

    /**
     * Supervisor 1-Click Reject Late Request
     * Route: POST/GET /supervisor-reject-late/{id}
     */
    public function rejectSupervisorLate($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $id = (int)$id;
        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);

        $stmt = $this->db->prepare("SELECT * FROM emp_late_approvals WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $la = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$la) {
            $_SESSION['error'] = "Request not found.";
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/my-employees-test"));
            exit;
        }

        $isAdminOrHr = (function_exists('hasPermission') && hasPermission("HRM Management")) || in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 34, 35]);
        if ($la['reporting_user_id'] != $currentUserId && !$isAdminOrHr) {
            $_SESSION['error'] = "Unauthorized.";
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/my-employees-test"));
            exit;
        }

        $remarks = trim($_POST['remarks'] ?? '');

        $upStmt = $this->db->prepare("
            UPDATE emp_late_approvals 
            SET supervisor_status = 'rejected',
                 supervisor_remarks = ?,
                 approval_status = 'rejected',
                 updated_at = NOW()
            WHERE id = ?
        ");
        $upStmt->bind_param("si", $remarks, $id);
        $upStmt->execute();
        $upStmt->close();

        $_SESSION['success'] = "Late approval request #{$id} rejected.";
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/my-employees-test"));
        exit;
    }

    /**
     * HR Admin: Approve Late Request (Waiver Applied to emp_attendances)
     * Route: POST/GET /hr-approve-late/{id}
     */
    public function approveHrLate($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $id = (int)$id;
        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);

        $stmt = $this->db->prepare("SELECT * FROM emp_late_approvals WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $la = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$la) {
            $_SESSION['error'] = "Request not found.";
            header("Location: {$GLOBALS['base_url']}/attendance-test");
            exit;
        }

        $remarks = trim($_POST['remarks'] ?? '');

        // Update emp_late_approvals
        $upStmt = $this->db->prepare("
            UPDATE emp_late_approvals 
            SET hr_status = 'approved',
                 hr_approved_by = ?,
                 hr_approved_at = NOW(),
                 hr_remarks = ?,
                 approval_status = 'approved',
                 updated_at = NOW()
            WHERE id = ?
        ");
        $upStmt->bind_param("isi", $currentUserId, $remarks, $id);
        $upStmt->execute();
        $upStmt->close();

        // Update emp_attendances to mark late waived
        $empId = (int)$la['employee_id'];
        $attDate = $la['attendance_date'];
        $attUp = $this->db->prepare("
            UPDATE emp_attendances 
            SET is_late_approved = 1,
                 late_approval_id = ?,
                 updated_at = NOW()
            WHERE employee_id = ? AND attendance_date = ?
        ");
        $attUp->bind_param("iis", $id, $empId, $attDate);
        $attUp->execute();
        $attUp->close();

        $_SESSION['success'] = "Late approval #{$id} approved by HR. Late penalty has been waived.";
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/attendance-test"));
        exit;
    }

    /**
     * HR Admin: Reject Late Request
     * Route: POST/GET /hr-reject-late/{id}
     */
    public function rejectHrLate($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $id = (int)$id;
        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);

        $remarks = trim($_POST['remarks'] ?? '');

        $upStmt = $this->db->prepare("
            UPDATE emp_late_approvals 
            SET hr_status = 'rejected',
                 hr_approved_by = ?,
                 hr_approved_at = NOW(),
                 hr_remarks = ?,
                 approval_status = 'rejected',
                 updated_at = NOW()
            WHERE id = ?
        ");
        $upStmt->bind_param("isi", $currentUserId, $remarks, $id);
        $upStmt->execute();
        $upStmt->close();

        $_SESSION['success'] = "Late approval #{$id} rejected by HR.";
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "{$GLOBALS['base_url']}/attendance-test"));
        exit;
    }

    /**
     * Helper: Resolve currently logged-in employee profile
     */
    protected function getCurrentEmployee()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $employeeId = $_SESSION['user']['employee_id'] ?? null;
        $userId = (int)($_SESSION['user']['id'] ?? 0);
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

        if (!$emp && $userId > 0) {
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
}


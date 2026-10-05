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
        $absentCount = 0;
        $leaveCount = 0;
        $dayOffCount = 0;
        $holidayCount = 0;

        foreach ($attendances as $row) {
            $st = $row['today_status'];
            if ($st === 'Present') $presentCount++;
            elseif ($st === 'Late') { $presentCount++; $lateCount++; }
            elseif ($st === 'Half-Day') { $presentCount++; }
            elseif ($st === 'On Leave') $leaveCount++;
            elseif ($st === 'Day Off') $dayOffCount++;
            elseif ($st === 'Holiday') $holidayCount++;
            else $absentCount++;
        }

        $this->view('hrm/attendances/attendance-test', [
            'current_route'   => 'attendance-test',
            'page_title'      => 'Attendance (Test)',
            'selectedDate'    => $selectedDate,
            'departmentId'    => $departmentId,
            'search'          => $search,
            'departments'     => $departments,
            'attendances'     => $attendances,
            'summary'         => [
                'total'    => $totalEmployees,
                'present'  => $presentCount,
                'late'     => $lateCount,
                'absent'   => $absentCount,
                'leave'    => $leaveCount,
                'day_off'  => $dayOffCount,
                'holiday'  => $holidayCount,
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

        // Identify employee ID
        $employeeId = $_SESSION['user']['employee_id'] ?? null;

        // Fallback: lookup by user_id or email if employee_id not directly in session
        if (!$employeeId) {
            $userId = (int)$_SESSION['user']['id'];
            $res = $this->db->query("SELECT id FROM employees WHERE user_id = $userId LIMIT 1");
            if ($res && $row = $res->fetch_assoc()) {
                $employeeId = $row['id'];
            }
        }

        if (!$employeeId) {
            die("Employee profile not linked to your account. Please contact HR.");
        }

        // Fetch employee details
        $empStmt = $this->db->prepare("
            SELECT e.*, d.name AS designation_name, dept.name AS department_name
            FROM employees e
            LEFT JOIN designations d ON e.designation_id = d.id
            LEFT JOIN departments dept ON e.department_id = dept.id
            WHERE e.id = ?
        ");
        $empStmt->bind_param("i", $employeeId);
        $empStmt->execute();
        $employee = $empStmt->get_result()->fetch_assoc();
        $empStmt->close();

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

        // Build full calendar month records
        $calendarDays = [];
        $current = new DateTime($startDate);
        $end = new DateTime($endDate);
        $todayStr = date('Y-m-d');

        $presentDays = 0;
        $lateDays = 0;
        $absentDays = 0;
        $leaveDays = 0;
        $offDays = 0;

        while ($current <= $end) {
            $dStr = $current->format('Y-m-d');
            $dayName = $current->format('D'); // e.g. Mon, Fri

            $rec = $attendanceByDate[$dStr] ?? null;
            $status = $rec['today_status'] ?? ($dStr > $todayStr ? 'Upcoming' : 'Absent');

            if ($rec) {
                if ($status === 'Present') $presentDays++;
                elseif ($status === 'Late') { $presentDays++; $lateDays++; }
                elseif ($status === 'Half-Day') $presentDays++;
                elseif ($status === 'On Leave') $leaveDays++;
                elseif ($status === 'Day Off' || $status === 'Holiday') $offDays++;
                else $absentDays++;
            } elseif ($dStr <= $todayStr) {
                $absentDays++;
            }

            $calendarDays[] = [
                'date' => $dStr,
                'day' => $dayName,
                'record' => $rec,
                'status' => $status
            ];

            $current->modify('+1 day');
        }

        $this->view('employee-modules/my-attendance-test', [
            'current_route'   => 'my-attendance-test',
            'page_title'      => 'My Attendance (Test)',
            'employee'        => $employee,
            'selectedMonth'   => $selectedMonth,
            'calendarDays'    => $calendarDays,
            'summary'         => [
                'total_days' => count($calendarDays),
                'present'    => $presentDays,
                'late'       => $lateDays,
                'absent'     => $absentDays,
                'leave'      => $leaveDays,
                'off_days'   => $offDays,
            ]
        ]);
    }
}

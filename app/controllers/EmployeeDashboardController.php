<?php

class EmployeeDashboardController extends Controller
{
    protected $db;
    protected $employeeModel;
    protected $taskModel;
    protected $leaveModel;
    protected $attendanceModel;
    protected $leaveTypeModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->employeeModel   = new Employee($this->db);
        $this->taskModel       = new Task($this->db);
        $this->leaveModel      = new EmployeeLeave($this->db);
        $this->attendanceModel = new EmpAttentendance($this->db);
        $this->leaveTypeModel  = new LeaveType($this->db);
    }

    // ─── Helper to get employee + user_id from session ──────────────────────
    // Returns ['employee' => [...], 'user_id' => <int>] or null on failure.
    private function getSessionEmployee()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']['id'])) {
            return null;
        }

        $loggedUserId = (int) $_SESSION['user']['id'];

        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $loggedUserId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if (!$user) return null;

        $employeeId = (int) ($user['employee_id'] ?? 0);
        $employee = null;

        if ($employeeId > 0) {
            $stmt = $this->db->prepare("
                SELECT e.*,
                       d.name AS department_name,
                       des.name AS designation_name
                FROM employees e
                LEFT JOIN departments d    ON e.department_id  = d.id
                LEFT JOIN designations des ON e.designation_id = des.id
                WHERE e.id = ? LIMIT 1
            ");
            $stmt->bind_param("i", $employeeId);
            $stmt->execute();
            $employee = $stmt->get_result()->fetch_assoc();
        }

        // Fallback: If no employee record exists in the database, construct it from the users table and session
        if (!$employee) {
            $employee = [
                'id' => 0,
                'user_id' => $user['id'],
                'full_name' => $user['full_name'] ?? $_SESSION['user']['full_name'] ?? '',
                'phone' => !empty($user['phone']) ? $user['phone'] : 'N/A',
                'email' => !empty($user['email']) ? $user['email'] : ($_SESSION['user']['email'] ?? 'N/A'),
                'address' => !empty($user['address']) ? $user['address'] : 'N/A',
                'nid' => !empty($user['nid']) ? $user['nid'] : 'N/A',
                'dob' => null,
                'photo' => !empty($user['photo']) ? $user['photo'] : null,
                'employee_id' => 'USR-' . $user['id'],
                'employee_status' => 'Active',
                'employee_type' => 'Management',
                'joining_date' => $user['created_at'] ?? null,
                'workplace' => 'HQ',
                'department_name' => !empty($_SESSION['user']['department_name']) ? ucwords($_SESSION['user']['department_name']) : 'Management',
                'designation_name' => !empty($_SESSION['user']['designation_name']) ? ucwords($_SESSION['user']['designation_name']) : 'Administrator',
            ];
            $employeeId = 0;
        }

        // Return both so callers can use the correct ID per query:
        //  - $employeeId  → for emp_attendances, employee_leaves (FK = employees.id)
        //  - $loggedUserId → for tasks (FK = users.id)
        return [
            'employee'    => $employee,
            'user_id'     => $loggedUserId,
            'employee_id' => $employeeId,
        ];
    }

    // ─── Dashboard 
    public function index()
    {
        $data = $this->getSessionEmployee();
        if (!$data) { echo "Employee not found"; return; }

        $employee   = $data['employee'];
        $employeeId = $data['employee_id']; // employees.id -> attendance / leave FKs
        $userId     = $data['user_id'];     // users.id     -> tasks.user_id FK

        $notifications = $this->employeeModel->taskNotification();

        // ── Current month attendance summary 
        $currentMonth = date('Y-m');
        $monthStart   = date('Y-m-01');
        $monthEnd     = date('Y-m-t');

        $stmt = $this->db->prepare("
            SELECT
                SUM(CASE WHEN today_status = 'Present'  THEN 1 ELSE 0 END) AS present_days,
                SUM(CASE WHEN today_status = 'Late'     THEN 1 ELSE 0 END) AS late_days,
                SUM(CASE WHEN today_status = 'Absent'   THEN 1 ELSE 0 END) AS absent_days,
                SUM(CASE WHEN today_status = 'On Leave' THEN 1 ELSE 0 END) AS leave_days,
                COUNT(*) AS total_days
            FROM emp_attendances
            WHERE employee_id = ? AND attendance_date BETWEEN ? AND ? AND deleted_at IS NULL
        ");
        $stmt->bind_param("iss", $employeeId, $monthStart, $monthEnd);
        $stmt->execute();
        $attSummary = $stmt->get_result()->fetch_assoc();

        $presentDays  = (int)($attSummary['present_days'] ?? 0);
        $lateDays     = (int)($attSummary['late_days']    ?? 0);
        $absentDays   = (int)($attSummary['absent_days']  ?? 0);
        $leaveDays    = (int)($attSummary['leave_days']   ?? 0);
        $totalDays    = (int)($attSummary['total_days']   ?? 1);
        $attendancePct = $totalDays > 0 ? round(($presentDays / $totalDays) * 100) : 0;
        $latePct   = $totalDays > 0 ? round(($lateDays   / $totalDays) * 100) : 0;
        $absentPct = $totalDays > 0 ? round(($absentDays / $totalDays) * 100) : 0;
        $leavePct  = $totalDays > 0 ? round(($leaveDays  / $totalDays) * 100) : 0;

        // ── Leave balances 
        $leaveTypes    = $this->leaveTypeModel->getAll();
        $leaveBalances = [];
        $totalUsed = 0;
        $totalAllowed = 0;
        foreach ($leaveTypes as $lt) {
            if (!(int)$lt['is_paid']) continue; // only paid types
            $remaining = $this->leaveModel->getRemainingLeave($employeeId, $lt['id']);
            $used      = max(0, (int)$lt['total_days'] - $remaining);
            $totalUsed    += $used;
            $totalAllowed += (int)$lt['total_days'];
            $leaveBalances[] = [
                'name'       => $lt['name'],
                'total_days' => (int)$lt['total_days'],
                'used'       => $used,
                'remaining'  => $remaining,
                'pct'        => $lt['total_days'] > 0 ? round(($used / $lt['total_days']) * 100) : 0,
            ];
        }

        // ── Tasks this month 
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS total_tasks,
                SUM(CASE WHEN status = 'completed'   THEN 1 ELSE 0 END) AS done_count,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS prog_count,
                SUM(CASE WHEN status = 'pending'     THEN 1 ELSE 0 END) AS pend_count
            FROM tasks
            WHERE user_id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $taskSummary = $stmt->get_result()->fetch_assoc();
        $totalTasks = (int)($taskSummary['total_tasks'] ?? 0);
        $doneTasks  = (int)($taskSummary['done_count']  ?? 0);
        $progTasks  = (int)($taskSummary['prog_count']  ?? 0);
        $pendTasks  = (int)($taskSummary['pend_count']  ?? 0);
        $taskPct    = $totalTasks > 0 ? round(($doneTasks / $totalTasks) * 100) : 0;

        // ── Pending tasks (all, for task list) 
        $stmt = $this->db->prepare("
            SELECT t.* FROM tasks t
            WHERE t.user_id = ? AND t.status != 'completed'
            ORDER BY t.deadline ASC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $pendingTasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // ── All tasks (for all-tasks section) 
        $stmt = $this->db->prepare("SELECT t.* FROM tasks t WHERE t.user_id = ? ORDER BY t.deadline ASC");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $allTasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // ── This month's attendance records 
        $attendanceData = $this->attendanceModel->getByEmployeeAndMonth($employeeId, $currentMonth);

        $this->view("employee-dashboard/emp-dashboard", [
            "current_route"  => "employee-dashboard",
            "page_title"     => "Employee Dashboard",
            "employee"       => $employee,
            "notifications"  => $notifications,
            "pendingTasks"   => $pendingTasks,
            "allTasks"       => $allTasks,
            "currentMonth"   => date('F Y'),
            // Monthly attendance stats
            "presentDays"    => $presentDays,
            "lateDays"       => $lateDays,
            "absentDays"     => $absentDays,
            "leaveDays"      => $leaveDays,
            "attendancePct"  => $attendancePct,
            "latePct"        => $latePct,
            "absentPct"      => $absentPct,
            "leavePct"       => $leavePct,
            // Leave balances
            "leaveBalances"  => $leaveBalances,
            "totalUsed"      => $totalUsed,
            "totalAllowed"   => $totalAllowed,
            // Task stats
            "totalTasks"     => $totalTasks,
            "doneTasks"      => $doneTasks,
            "progTasks"      => $progTasks,
            "pendTasks"      => $pendTasks,
            "taskPct"        => $taskPct,
            // Attendance records
            "attendanceData" => $attendanceData,
        ]);
    }


    // ─── My Profile 
    public function myProfile()
    {
        $data = $this->getSessionEmployee();
        if (!$data) { echo "Employee not found"; return; }

        $employee   = $data['employee'];
        $employeeId = $data['employee_id'];
        $userId     = $data['user_id'];

        // Year-to-date stats
        $yearStart = date('Y') . '-01-01';
        $yearEnd   = date('Y-12-31');

        // Attendance percentage
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS total_days,
                SUM(CASE WHEN today_status = 'Present' THEN 1 ELSE 0 END) AS present_days
            FROM emp_attendances
            WHERE employee_id = ? AND attendance_date BETWEEN ? AND ? AND deleted_at IS NULL
        ");
        $stmt->bind_param("iss", $employeeId, $yearStart, $yearEnd);
        $stmt->execute();
        $attStats = $stmt->get_result()->fetch_assoc();
        $totalDays    = (int)($attStats['total_days']  ?? 1);
        $presentDays  = (int)($attStats['present_days'] ?? 0);
        $attendancePct = $totalDays > 0 ? round(($presentDays / $totalDays) * 100) : 0;

        // Task completion rate (use userId → tasks.user_id)
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS total_tasks,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_tasks
            FROM tasks
            WHERE user_id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $taskStats     = $stmt->get_result()->fetch_assoc();
        $totalTasks    = (int)($taskStats['total_tasks']    ?? 0);
        $completedTask = (int)($taskStats['completed_tasks'] ?? 0);
        $taskPct       = $totalTasks > 0 ? round(($completedTask / $totalTasks) * 100) : 0;

        // Leave utilization - total leave days used vs max allocation
        $stmt = $this->db->prepare("
            SELECT SUM(el.approved_days) AS used_days, SUM(lt.total_days) AS total_allowed
            FROM employee_leaves el
            JOIN leave_types lt ON el.leave_type_id = lt.id
            WHERE el.employee_id = ? AND el.approval_status = 'approved'
        ");
        $stmt->bind_param("i", $employeeId);
        $stmt->execute();
        $leaveStats   = $stmt->get_result()->fetch_assoc();
        $usedLeaveDays = (int)($leaveStats['used_days']     ?? 0);
        $maxLeaveDays  = (int)($leaveStats['total_allowed'] ?? 0);
        $leavePct      = $maxLeaveDays > 0 ? round(($usedLeaveDays / $maxLeaveDays) * 100) : 0;

        // Recent activity - last 5 leave applications and task completions combined
        $stmt = $this->db->prepare("
            SELECT 'leave' AS activity_type, el.created_at AS activity_date, 
                   lt.name AS title, el.approval_status AS status
            FROM employee_leaves el
            JOIN leave_types lt ON el.leave_type_id = lt.id
            WHERE el.employee_id = ?
            ORDER BY el.created_at DESC
            LIMIT 5
        ");
        $stmt->bind_param("i", $employeeId);
        $stmt->execute();
        $recentLeaves = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Recent task activity (use userId)
        $stmt = $this->db->prepare("
            SELECT 'task' AS activity_type, updated_at AS activity_date, 
                   title AS title, status AS status
            FROM tasks
            WHERE user_id = ?
            ORDER BY updated_at DESC
            LIMIT 5
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $recentTasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $recentActivity = array_merge($recentLeaves, $recentTasks);
        usort($recentActivity, fn($a, $b) => strtotime($b['activity_date']) - strtotime($a['activity_date']));
        $recentActivity = array_slice($recentActivity, 0, 5);

        // Calculate years of service
        $yearsOfService = '';
        if (!empty($employee['joining_date'])) {
            $joiningDate = new DateTime($employee['joining_date']);
            $now         = new DateTime();
            $diff        = $joiningDate->diff($now);
            $yearsOfService = $diff->y > 0
                ? $diff->y . ' Year' . ($diff->y > 1 ? 's' : '') . ' ' . $diff->m . ' Mo.'
                : $diff->m . ' Month' . ($diff->m > 1 ? 's' : '');
        }

        $this->view('employee-modules/my-profile', [
            'current_route'  => 'my-profile',
            'page_title'     => 'My Profile',
            'employee'       => $employee,
            'attendancePct'  => $attendancePct,
            'taskPct'        => $taskPct,
            'leavePct'       => $leavePct,
            'recentActivity' => $recentActivity,
            'yearsOfService' => $yearsOfService,
        ]);
    }

    // ─── My Leaves 
    public function myLeaves()
    {
        $data = $this->getSessionEmployee();
        if (!$data) { echo "Employee not found"; return; }

        $employee   = $data['employee'];
        $employeeId = $data['employee_id'];

        // Leave history
        $myLeaves  = $this->leaveModel->getMyLeaves($employeeId);

        // All leave types with balance
        $leaveTypes = $this->leaveTypeModel->getAll();

        // For each leave type, compute used & remaining
        $leaveBalances = [];
        foreach ($leaveTypes as $lt) {
            $remaining = $this->leaveModel->getRemainingLeave($employeeId, $lt['id']);
            $used      = max(0, (int)$lt['total_days'] - $remaining);
            $leaveBalances[] = [
                'type_name'  => $lt['name'],
                'total_days' => (int)$lt['total_days'],
                'used'       => $used,
                'remaining'  => $remaining,
                'pct'        => $lt['total_days'] > 0 ? round(($used / $lt['total_days']) * 100) : 0,
                'is_paid'    => (int)$lt['is_paid'],
            ];
        }

        $this->view('employee-modules/my-leaves', [
            'current_route'  => 'my-leaves',
            'page_title'     => 'My Leaves',
            'employee'       => $employee,
            'myLeaves'       => $myLeaves,
            'leaveBalances'  => $leaveBalances,
        ]);
    }

    // ─── My Attendance 
    public function myAttendance()
    {
        $data = $this->getSessionEmployee();
        if (!$data) { echo "Employee not found"; return; }

        $employee      = $data['employee'];
        $employeeId    = $data['employee_id'];
        $selectedMonth = $_GET['month'] ?? date('Y-m');

        $attendanceData = $this->attendanceModel->getByEmployeeAndMonth($employeeId, $selectedMonth);

        // Summary counts
        $summary = ['Present' => 0, 'Late' => 0, 'Absent' => 0, 'On Leave' => 0, 'Half-Day' => 0];
        foreach ($attendanceData as $row) {
            $status = $row['today_status'] ?? '';
            if (isset($summary[$status])) {
                $summary[$status]++;
            }
        }

        $this->view('employee-modules/my-attendance', [
            'current_route'  => 'my-attendance',
            'page_title'     => 'My Attendance',
            'employee'       => $employee,
            'attendanceData' => $attendanceData,
            'selectedMonth'  => $selectedMonth,
            'summary'        => $summary,
        ]);
    }

    // ─── Task updates 
    public function updateTaskStatus()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_POST['task_id'])) {
            echo "Invalid request";
            return;
        }

        $taskId  = (int)$_POST['task_id'];
        $success = $this->taskModel->acceptTask($taskId);

        if ($success) {
            header("Location: " . $GLOBALS['base_url'] . "/employee-dashboard");
            exit;
        } else {
            echo "<h3 style='color:red'>Failed to update task</h3>";
        }
    }

    public function updateTaskProgress()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_POST['task_id'], $_POST['progress'])) {
            echo "Invalid request";
            return;
        }

        $taskId   = (int)$_POST['task_id'];
        $progress = (int)$_POST['progress'];
        $remarks  = isset($_POST['remarks']) ? trim($_POST['remarks']) : null;
        $imageName = null;

        // Process File Upload
        if (isset($_FILES['attachment_image']) && $_FILES['attachment_image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['attachment_image']['tmp_name'];
            $originalName = $_FILES['attachment_image']['name'];
            $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($fileExtension, $allowedExtensions)) {
                $uploadDir = dirname(__DIR__, 2) . '/public/uploads/tasks/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $newFileName = time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $imageName = $newFileName;
                }
            }
        }

        $success  = $this->taskModel->updateProgress($taskId, $progress, $remarks, $imageName);

        if ($success) {
            header("Location: " . $GLOBALS['base_url'] . "/employee-dashboard");
            exit;
        } else {
            echo "<h3 style='color:red'>Failed to update progress</h3>";
        }
    }
}

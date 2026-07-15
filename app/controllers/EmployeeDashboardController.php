<?php

class EmployeeDashboardController extends Controller
{
    protected $db;
    protected $employeeModel;
    protected $taskModel;
    protected $leaveModel;
    protected $attendanceModel;
    protected $leaveTypeModel;
    protected $externalApiService;

    public function __construct($db)
    {
        $this->db = $db;
        $this->employeeModel   = new Employee($this->db);
        $this->taskModel       = new Task($this->db);
        $this->leaveModel      = new EmployeeLeave($this->db);
        $this->attendanceModel = new EmpAttentendance($this->db);
        $this->leaveTypeModel  = new LeaveType($this->db);
        $this->externalApiService = new ExternalApiService($this->db);
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
        if (isset($_SESSION['user']['employee_id']) && (int)$_SESSION['user']['employee_id'] > 0) {
            $employeeId = (int)$_SESSION['user']['employee_id'];
        }
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

    // ─── My Tipsoi Information View
    public function myTipsoiInfo()
    {
        $data = $this->getSessionEmployee();
        if (!$data) { echo "Employee not found"; return; }

        $employee = $data['employee'];

        $this->view('employee-modules/my-tipsoi-info', [
            'current_route' => 'my-tipsoi-info',
            'page_title'    => 'My Tipsoi Information',
            'employee'      => $employee,
        ]);
    }

    // ─── Get Tipsoi Data API
    public function getTipsoiData()
    {
        header('Content-Type: application/json');

        $data = $this->getSessionEmployee();
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Employee session not found.']);
            exit;
        }

        $employee = $data['employee'];
        $localEmail = strtolower(trim($employee['email'] ?? ''));
        $localOfficeId = trim($employee['employee_id'] ?? '');

        if ($localEmail === '' || $localOfficeId === '') {
            echo json_encode([
                'success' => true,
                'isRegistered' => false,
                'message' => 'Employee email or Office ID not found in ERP profile.'
            ]);
            exit;
        }

        $selectedMonth = $_GET['month'] ?? date('Y-m'); // e.g. 2026-07
        $year = date('Y', strtotime($selectedMonth . '-01'));

        try {
            $token = $this->externalApiService->getBearerToken();
            if (!$token) {
                echo json_encode(['success' => false, 'message' => 'Failed to authenticate with Tipsoi API.']);
                exit;
            }

            // 1. Fetch all Tipsoi employees to find the internal ID
            $empUrl = "https://app.tipsoi.ai/inovace-client/api/v1/employee?order=asc&pageNumber=0&perPage=2000&status=1";
            $ch = curl_init($empUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $res = curl_exec($ch);
            curl_close($ch);
            
            $empData = json_decode($res, true);
            $externalEmployees = $empData['data'] ?? $empData['employees'] ?? $empData['content'] ?? $empData ?? [];

            $matchedEmp = null;
            $normalizeIdPhp = function($id) {
                $str = trim((string)$id);
                return preg_match('/^\d+$/', $str) ? (string)intval($str) : $str;
            };
            $normOfficeId = $normalizeIdPhp($localOfficeId);

            foreach ($externalEmployees as $emp) {
                $empEmail = strtolower(trim($emp['email'] ?? ''));
                $empOid = $normalizeIdPhp($emp['employeeOfficeId'] ?? $emp['employeeId'] ?? '');

                if ($empEmail !== '' && $empEmail === $localEmail && $empOid === $normOfficeId) {
                    $matchedEmp = $emp;
                    break;
                }
            }

            if (!$matchedEmp) {
                echo json_encode([
                    'success' => true,
                    'isRegistered' => false,
                    'message' => 'Employee email ' . $employee['email'] . ' or Office ID ' . $localOfficeId . ' is not registered in the Tipsoi attendance system.'
                ]);
                exit;
            }

            $normOfficeId = $normalizeIdPhp($matchedEmp['employeeOfficeId'] ?? $matchedEmp['employeeId'] ?? '');

            $tipsoiId = $matchedEmp['id'] ?? $matchedEmp['employeeId'] ?? null;

            // 2. Fetch Basic Profile if Tipsoi ID is found
            $tipsoiProfile = null;
            if ($tipsoiId) {
                $profileUrl = "https://app.tipsoi.ai/inovace-client/api/v1/employee/basic-profile/" . $tipsoiId;
                $ch = curl_init($profileUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $token,
                    'Accept: application/json'
                ]);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                $profileRes = curl_exec($ch);
                curl_close($ch);
                $profileData = json_decode($profileRes, true);
                
                $empInfo = $profileData['employee'] ?? $profileData['data']['employee'] ?? null;
                if ($empInfo) {
                    $tipsoiProfile = [
                        'id' => $empInfo['id'] ?? $tipsoiId,
                        'employeeName' => $empInfo['name'] ?? '',
                        'employeeOfficeId' => $empInfo['employeeOfficeId'] ?? '',
                        'personId3rdPartyDevice' => $empInfo['personId3rdPartyDevice'] ?? '',
                        'email' => $empInfo['email'] ?? '',
                        'departmentName' => $empInfo['department']['name'] ?? '',
                        'subDepartmentName' => $empInfo['subDepartment']['name'] ?? '',
                        'designationName' => $empInfo['designation']['name'] ?? '',
                        'employmentTypeName' => $empInfo['employmentType']['name'] ?? '',
                        'shiftGroupName' => $empInfo['shiftGroupName'] ?? '',
                        'gender' => ($empInfo['gender'] == 1 ? 'Male' : ($empInfo['gender'] == 2 ? 'Female' : 'Other')),
                        'primaryDisplayText' => $empInfo['primaryDisplayText'] ?? 'Check in',
                        'secondaryDisplayText' => $empInfo['secondaryDisplayText'] ?? 'Check out',
                        'rfid' => $empInfo['rfid'] ?? '',
                        'reportPosition' => $empInfo['employeeReportPosition'] ?? '',
                        'leavePolicyName' => $empInfo['leavePolicyName'] ?? '',
                        'joiningDate' => $empInfo['dateOfJoin'] ?? null,
                        'endDate' => $empInfo['dateOfResignation'] ?? null,
                        'firstApproverName' => $empInfo['firstApproverName'] ?? '',
                        'secondApproverName' => $empInfo['secondApproverName'] ?? '',
                        'firstLateApproverName' => $empInfo['firstLateApproverName'] ?? '',
                        'secondLateApproverName' => $empInfo['secondLateApproverName'] ?? '',
                        'firstManualEntryApproverName' => $empInfo['firstManualEntryApproverName'] ?? '',
                        'secondManualEntryApproverName' => $empInfo['secondManualEntryApproverName'] ?? '',
                        'allowOvertime' => $empInfo['allowOvertime'] ?? 0,
                        'includeInAttendanceReport' => $empInfo['includeInAttendanceReport'] ?? 1,
                        'status' => $empInfo['status'] ?? 1,
                        'photo' => $empInfo['employeePictureUrl'] ?? '',
                    ];
                }
            }

            if (!$tipsoiProfile) {
                // Fallback using matched external employee info
                $tipsoiProfile = [
                    'id' => $tipsoiId,
                    'employeeName' => $matchedEmp['employeeName'] ?? '',
                    'employeeOfficeId' => $matchedEmp['employeeOfficeId'] ?? $matchedEmp['employeeId'] ?? '',
                    'personId3rdPartyDevice' => '',
                    'email' => $matchedEmp['email'] ?? '',
                    'departmentName' => $matchedEmp['departmentName'] ?? '',
                    'subDepartmentName' => '',
                    'designationName' => $matchedEmp['designationName'] ?? $matchedEmp['designation'] ?? '',
                    'employmentTypeName' => '',
                    'shiftGroupName' => $matchedEmp['shiftGroupName'] ?? '',
                    'gender' => 'Male',
                    'primaryDisplayText' => 'Check in',
                    'secondaryDisplayText' => 'Check out',
                    'rfid' => $matchedEmp['cardNo'] ?? '',
                    'reportPosition' => '',
                    'leavePolicyName' => '',
                    'joiningDate' => $matchedEmp['dateOfJoin'] ?? null,
                    'endDate' => null,
                    'firstApproverName' => '',
                    'secondApproverName' => '',
                    'firstLateApproverName' => '',
                    'secondLateApproverName' => '',
                    'firstManualEntryApproverName' => '',
                    'secondManualEntryApproverName' => '',
                    'allowOvertime' => 0,
                    'includeInAttendanceReport' => 1,
                    'status' => $matchedEmp['status'] ?? 1,
                    'photo' => '',
                ];
            }

            // 3. Fetch Leave Balances (current year)
            $fromTime = strtotime($year . '-01-01 00:00:00') * 1000;
            $toTime = strtotime($year . '-12-31 23:59:59') * 1000;

            $balanceUrl = "https://app.tipsoi.ai/inovace-client/api/v1/attendance/leave-balance?" . http_build_query([
                'officeId' => 4699,
                'order' => 'asc',
                'pageNumber' => 0,
                'perPage' => 2000,
                'sortKey' => 'reportPosition',
                'status' => 1,
                'from' => $fromTime,
                'to' => $toTime
            ]);

            $ch = curl_init($balanceUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $balanceRes = curl_exec($ch);
            curl_close($ch);
            $balanceData = json_decode($balanceRes, true);
            
            $myBalances = [];
            if ($balanceData && isset($balanceData['responseForAllEmployees'])) {
                foreach ($balanceData['responseForAllEmployees'] as $empBal) {
                    $empOid = $normalizeIdPhp($empBal['employeeOfficeId'] ?? $empBal['employeeId'] ?? '');
                    if ($empOid === $normOfficeId) {
                        $myBalances = $empBal['leaveLeaveInfo']['leaveDataList'] ?? [];
                        break;
                    }
                }
            }

            // 4. Fetch Leave History (current year)
            $historyUrl = "https://app.tipsoi.ai/inovace-client/api/v1/attendance/leave-history?" . http_build_query([
                'employeeBeforeEndTime' => 'true',
                'officeId' => 4699,
                'order' => 'asc',
                'pageNumber' => 0,
                'perPage' => 2000,
                'sortKey' => 'reportPosition',
                'status' => 1,
                'from' => $fromTime,
                'to' => $toTime
            ]);

            $ch = curl_init($historyUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $historyRes = curl_exec($ch);
            curl_close($ch);
            $historyData = json_decode($historyRes, true);

            $myHistory = [];
            if ($historyData && isset($historyData['responseForAllEmployees'])) {
                foreach ($historyData['responseForAllEmployees'] as $empHist) {
                    $empOid = $normalizeIdPhp($empHist['employeeOfficeId'] ?? $empHist['employeeId'] ?? '');
                    if ($empOid === $normOfficeId) {
                        $rawList = $empHist['leaveLeaveInfo']['leaveDataList'] ?? [];
                        foreach ($rawList as $item) {
                            if (!empty($item['from'])) {
                                $startDate = date('Y-m-d', strtotime($item['from']));
                                $endDate = date('Y-m-d', strtotime($item['to'] ?? $item['from']));
                                $myHistory[] = [
                                    'categoryName' => $item['leaveCategoryName'] ?? 'Leave',
                                    'startDate' => $startDate,
                                    'endDate' => $endDate,
                                    'approvalStatus' => $item['approvalStatus'] ?? 0,
                                    'reason' => $item['reason'] ?? '—',
                                    'duration' => !empty($item['days']) ? (float)$item['days'] : 1.0
                                ];
                            }
                        }
                        break;
                    }
                }
            }

            // 5. Fetch Attendance Logs for selected month
            $firstDay = date('Y-m-01', strtotime($selectedMonth . '-01'));
            $lastDay = date('Y-m-t', strtotime($selectedMonth . '-01'));

            // Store previous timezone to restore it later
            $oldTimezone = date_default_timezone_get();
            date_default_timezone_set('Asia/Dhaka');
            $fromTimeAtt = strtotime($firstDay . ' 00:00:00') * 1000;
            $toTimeAtt = strtotime($lastDay . ' 23:59:59') * 1000;
            date_default_timezone_set($oldTimezone);

            $attUrl = "https://app.tipsoi.ai/inovace-client/api/v1/attendance?" . http_build_query([
                'officeId' => 4699,
                'order' => 'asc',
                'pageNumber' => 0,
                'perPage' => 2000,
                'reportType' => 'department',
                'sortKey' => 'reportPosition',
                'status' => 1,
                'from' => $fromTimeAtt,
                'to' => $toTimeAtt
            ]);

            $ch = curl_init($attUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $attRes = curl_exec($ch);
            curl_close($ch);
            $attData = json_decode($attRes, true);

            $myAttendanceLogs = [];
            $datesList = $attData['dates'] ?? [];
            $mySummary = null;
            
            if ($attData && isset($attData['attendance'])) {
                foreach ($attData['attendance'] as $empAtt) {
                    $empOid = $normalizeIdPhp($empAtt['employeeOfficeId'] ?? $empAtt['employeeId'] ?? '');
                    if ($empOid === $normOfficeId) {
                        $mySummary = $empAtt['summary'] ?? null;
                        
                        $shiftGroupName = $empAtt['shiftGroupName'] ?? 'Regular';
                        $shiftStartTime = $empAtt['shiftStartTime'] ?? '09:30 AM';
                        $shiftEndTime = $empAtt['shiftEndTime'] ?? '06:30 PM';
                        
                        $logs = $empAtt['attendance'] ?? $empAtt['attendanceLogMap'] ?? $empAtt['attendanceLogList'] ?? [];
                        foreach ($logs as $log) {
                            $log['shiftGroupName'] = $shiftGroupName;
                            $log['shiftTimeRange'] = $shiftStartTime . '-' . $shiftEndTime;
                            $myAttendanceLogs[] = $log;
                        }
                        break;
                    }
                }
            }

            // 6. Fetch pending requests and filter for this employee
            // Fetch Global Pending Corrections (Type 1)
            $correctionUrl = "https://app.tipsoi.ai/inovace-client/api/v1/manual-entry?officeId=4699&order=asc&pageNumber=0&perPage=200&sortKey=reportPosition&manualEntryType=1&applicationStatus=0";
            $ch = curl_init($correctionUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $correctionRes = curl_exec($ch);
            curl_close($ch);
            $correctionData = json_decode($correctionRes, true);
            $corrections = $correctionData['manualEntries'] ?? [];

            // Fetch Global Pending Late Excuses (Type 2)
            $lateUrl = "https://app.tipsoi.ai/inovace-client/api/v1/manual-entry?officeId=4699&order=asc&pageNumber=0&perPage=200&sortKey=reportPosition&manualEntryType=2&applicationStatus=0";
            $ch = curl_init($lateUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $lateRes = curl_exec($ch);
            curl_close($ch);
            $lateData = json_decode($lateRes, true);
            $lates = $lateData['manualEntries'] ?? [];

            // Fetch Global Pending Field Visits (Type 3)
            $fieldUrl = "https://app.tipsoi.ai/inovace-client/api/v1/manual-entry?officeId=4699&order=asc&pageNumber=0&perPage=200&sortKey=reportPosition&manualEntryType=3&applicationStatus=0";
            $ch = curl_init($fieldUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $fieldRes = curl_exec($ch);
            curl_close($ch);
            $fieldData = json_decode($fieldRes, true);
            $fields = $fieldData['manualEntries'] ?? [];

            // Map tipsoiId -> officeId mapping to correctly filter manual requests
            $tipsoiIdToOfficeIdMap = [];
            foreach ($externalEmployees as $emp) {
                $tid = $emp['employeeId'] ?? $emp['id'] ?? null;
                $oid = $emp['employeeOfficeId'] ?? $emp['officeId'] ?? null;
                if ($tid !== null && $oid !== null) {
                    $tipsoiIdToOfficeIdMap[$tid] = $oid;
                }
            }

            $myPendingRequests = [];
            $allManual = array_merge($corrections, $lates, $fields);
            foreach ($allManual as $entry) {
                $entryOid = $normalizeIdPhp($entry['employeeOfficeId'] ?? '');
                $tipsoiEmpId = $entry['employeeId'] ?? null;
                if ($tipsoiEmpId && isset($tipsoiIdToOfficeIdMap[$tipsoiEmpId])) {
                    $entryOid = $normalizeIdPhp($tipsoiIdToOfficeIdMap[$tipsoiEmpId]);
                }

                if ($entryOid === $normOfficeId) {
                    $typeVal = $entry['manualEntryType'] ?? 1;
                    $reqType = ($typeVal == 1) ? 'Manual Entry' : (($typeVal == 2) ? 'Late Excuse' : 'Field Visit');
                    $myPendingRequests[] = [
                        'id' => $entry['manualEntryId'] ?? $entry['id'] ?? null,
                        'requestType' => $reqType,
                        'startDate' => isset($entry['startDate']) ? date('Y-m-d', (int)($entry['startDate'] / 1000)) : '—',
                        'reason' => $entry['reason'] ?? '—',
                        'firstApprover' => $entry['firstManualApproverName'] ?? '',
                        'firstApprovalStatus' => $entry['firstManualEntryApprovalStatus'] ?? 0,
                        'secondApprover' => $entry['secondManualApproverName'] ?? '',
                        'secondApprovalStatus' => $entry['secondManualEntryApprovalStatus'] ?? 0,
                        'status' => $entry['applicationStatus'] ?? 0
                    ];
                }
            }

            echo json_encode([
                'success' => true,
                'isRegistered' => true,
                'tipsoiProfile' => $tipsoiProfile,
                'erpProfile' => $employee,
                'leaveBalances' => $myBalances,
                'leaveHistory' => $myHistory,
                'attendance' => $myAttendanceLogs,
                'dates' => $datesList,
                'attendanceSummary' => $mySummary,
                'pendingRequests' => $myPendingRequests
            ]);

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Internal Server Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    // Helper method to get dates in range
    private function getDatesInRange($startDate, $endDate)
    {
        $dates = [];
        $current = new DateTime($startDate);
        $last = new DateTime($endDate);
        while ($current <= $last) {
            $dates[] = $current->format('Y-m-d');
            $current->modify('+1 day');
        }
        return $dates;
    }

    public function tipsoiMonthlyReport()
    {
        if (!hasPermission("HRM Management") && !in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 4])) {
            die("Unauthorized access.");
        }
        $this->view('employee-modules/tipsoi-monthly-report', [
            'current_route' => 'tipsoi-monthly-report',
            'page_title'    => 'Tipsoi Monthly Report'
        ]);
    }

    public function tipsoiDailyReport()
    {
        if (!hasPermission("HRM Management") && !in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 4])) {
            die("Unauthorized access.");
        }
        $this->view('employee-modules/tipsoi-daily-report', [
            'current_route' => 'tipsoi-daily-report',
            'page_title'    => 'Tipsoi Daily Report'
        ]);
    }

    public function getTipsoiMonthlyData()
    {
        header('Content-Type: application/json');
        if (!hasPermission("HRM Management") && !in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 4])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $dateRange = $_GET['range'] ?? '';
        if (empty($dateRange)) {
            $selectedMonth = $_GET['month'] ?? date('Y-m');
            $firstDay = date('Y-m-01', strtotime($selectedMonth . '-01'));
            $lastDay = date('Y-m-t', strtotime($selectedMonth . '-01'));
        } else {
            $parts = explode(' - ', $dateRange);
            $firstDay = date('Y-m-d', strtotime(trim($parts[0])));
            $lastDay = date('Y-m-d', strtotime(trim($parts[1])));
        }

        try {
            $token = $this->externalApiService->getBearerToken();
            if (!$token) {
                echo json_encode(['success' => false, 'message' => 'Failed to authenticate with Tipsoi API.']);
                exit;
            }

            $oldTimezone = date_default_timezone_get();
            date_default_timezone_set('Asia/Dhaka');
            $fromTime = strtotime($firstDay . ' 00:00:00') * 1000;
            $toTime = strtotime($lastDay . ' 23:59:59') * 1000;
            date_default_timezone_set($oldTimezone);

            $url = "https://app.tipsoi.ai/inovace-client/api/v1/attendance?" . http_build_query([
                'officeId' => 4699,
                'order' => 'asc',
                'pageNumber' => 0,
                'perPage' => 2000,
                'reportType' => 'department',
                'sortKey' => 'reportPosition',
                'status' => 1,
                'from' => $fromTime,
                'to' => $toTime
            ]);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $res = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($res, true);
            $attendance = $data['attendance'] ?? [];

            $totalAbsent = 0;
            $totalLeave = 0;
            $totalLate = 0;
            $totalOvertimeMins = 0;

            $rows = [];
            foreach ($attendance as $emp) {
                $summ = $emp['summary'] ?? [];
                
                $absent = (int)($summ['totalAbsent'] ?? 0);
                $leave = (float)($summ['totalLeave'] ?? 0);
                $late = (int)($summ['totalLateEntry'] ?? 0);
                $ot = (int)($summ['totalOvertimeInMinutes'] ?? 0);

                $totalAbsent += $absent;
                $totalLeave += $leave;
                $totalLate += $late;
                $totalOvertimeMins += $ot;

                $rows[] = [
                    'id' => $emp['employeeOfficeId'] ?? $emp['employeeId'] ?? '—',
                    'name' => $emp['employeeName'] ?? '—',
                    'designation' => $emp['designationName'] ?? '—',
                    'department' => $emp['departmentName'] ?? '—',
                    'workplace' => $emp['workplaceName'] ?? '—',
                    'employeeType' => $emp['employmentTypeName'] ?? '—',
                    'workingDays' => $summ['totalApplicableDays'] ?? 0,
                    'present' => $summ['totalPresent'] ?? 0,
                    'absent' => $absent,
                    'leave' => $leave,
                    'offDays' => $summ['totalWeeklyOffdays'] ?? 0,
                    'holidays' => $summ['totalHolidays'] ?? 0,
                    'specialDays' => $summ['totalSpecialDays'] ?? 0,
                    'late' => $late,
                    'approvedLates' => $summ['totalApprovedLateApplication'] ?? 0,
                    'fieldVisits' => $summ['totalFieldVisits'] ?? 0,
                    'expectedHours' => $summ['totalExpectedWorkingHour'] ?? '—',
                    'workingHours' => $summ['totalWorkingHour'] ?? '—',
                    'difference' => ($summ['workingHourSign'] ?? '') . ($summ['workingHourDifference'] ?? '—'),
                    'otHours' => $ot > 0 ? (floor($ot/60) . 'h ' . ($ot%60) . 'm') : '—',
                    'photo' => $emp['employeePictureUrl'] ?? ''
                ];
            }

            echo json_encode([
                'success' => true,
                'absentDays' => $totalAbsent,
                'leaveDays' => $totalLeave,
                'lateHours' => $totalLate,
                'overtimeHours' => $totalOvertimeMins > 0 ? (floor($totalOvertimeMins/60) . 'h ' . ($totalOvertimeMins%60) . 'm') : '0',
                'rows' => $rows
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function getTipsoiDailyData()
    {
        header('Content-Type: application/json');
        if (!hasPermission("HRM Management") && !in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 4])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $date = $_GET['date'] ?? date('Y-m-d');

        try {
            $token = $this->externalApiService->getBearerToken();
            if (!$token) {
                echo json_encode(['success' => false, 'message' => 'Failed to authenticate with Tipsoi API.']);
                exit;
            }

            $oldTimezone = date_default_timezone_get();
            date_default_timezone_set('Asia/Dhaka');
            $fromTime = strtotime($date . ' 00:00:00') * 1000;
            $toTime = strtotime($date . ' 23:59:59') * 1000;
            date_default_timezone_set($oldTimezone);

            $url = "https://app.tipsoi.ai/inovace-client/api/v1/attendance?" . http_build_query([
                'officeId' => 4699,
                'order' => 'asc',
                'pageNumber' => 0,
                'perPage' => 2000,
                'reportType' => 'department',
                'sortKey' => 'reportPosition',
                'status' => 1,
                'from' => $fromTime,
                'to' => $toTime
            ]);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $res = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($res, true);
            $attendance = $data['attendance'] ?? [];

            $totalEmployees = count($attendance);
            $presentCount = 0;
            $absentCount = 0;
            $lateCount = 0;
            $leaveCount = 0;
            $earlyExitCount = 0;
            $dayOffCount = 0;
            $noOutPunchCount = 0;

            $rows = [];
            foreach ($attendance as $emp) {
                $dailyLog = null;
                $empLogs = $emp['attendance'] ?? [];
                if (!empty($empLogs)) {
                    $dailyLog = reset($empLogs);
                }

                $status = 'Absent';
                $entry = '—';
                $exit = '—';
                $late = '—';
                $early = '—';
                $hours = '—';
                $ot = '—';

                if ($dailyLog) {
                    if ($dailyLog['isPresent'] == 1) {
                        $status = 'Present';
                        $presentCount++;

                        if ($dailyLog['isLate'] == 1) {
                            $status = 'Late';
                            $lateCount++;
                        }

                        $entry = $dailyLog['firstLoggedTime'] ?? '—';
                        $exit = $dailyLog['lastLoggedTime'] ?? '—';
                        if ($exit === '—' || empty($dailyLog['lastLoggedTime'])) {
                            $noOutPunchCount++;
                        }

                        $late = ($dailyLog['lateBy'] !== '-' && !empty($dailyLog['lateBy'])) ? $dailyLog['lateBy'] : '—';
                        $early = ($dailyLog['earlyExitBy'] !== '-' && !empty($dailyLog['earlyExitBy'])) ? $dailyLog['earlyExitBy'] : '—';
                        if ($early !== '—') {
                            $earlyExitCount++;
                        }
                        $hours = $dailyLog['totalHour'] ?? '—';
                        $otVal = (int)($dailyLog['overtimeInMinutes'] ?? 0);
                        $ot = $otVal > 0 ? (floor($otVal/60) . 'h ' . ($otVal%60) . 'm') : '—';
                    } elseif ($dailyLog['isLeave'] == 1) {
                        $status = 'Leave';
                        $leaveCount++;
                    } elseif ($dailyLog['isOffday'] == 1) {
                        $status = 'Off Day';
                        $dayOffCount++;
                    } elseif ($dailyLog['isHoliday'] == 1) {
                        $status = 'Holiday';
                    } else {
                        $absentCount++;
                    }
                } else {
                    $absentCount++;
                }

                $rows[] = [
                    'id' => $emp['employeeOfficeId'] ?? $emp['employeeId'] ?? '—',
                    'name' => $emp['employeeName'] ?? '—',
                    'designation' => $emp['designationName'] ?? '—',
                    'department' => $emp['departmentName'] ?? '—',
                    'workplace' => $emp['workplaceName'] ?? '—',
                    'employeeType' => $emp['employmentTypeName'] ?? '—',
                    'shift' => ($emp['shiftGroupName'] ?? '—') . '<br><small class="text-muted">' . ($emp['shiftStartTime'] ?? '') . ' - ' . ($emp['shiftEndTime'] ?? '') . '</small>',
                    'status' => $status,
                    'entry' => $entry,
                    'exit' => $exit,
                    'late' => $late,
                    'early' => $early,
                    'hours' => $hours,
                    'ot' => $ot,
                    'photo' => $emp['employeePictureUrl'] ?? ''
                ];
            }

            echo json_encode([
                'success' => true,
                'totalEmployees' => $totalEmployees,
                'presentCount' => $presentCount,
                'absentCount' => $absentCount,
                'lateCount' => $lateCount,
                'leaveCount' => $leaveCount,
                'earlyExitCount' => $earlyExitCount,
                'dayOffCount' => $dayOffCount,
                'noOutPunchCount' => $noOutPunchCount,
                'rows' => $rows
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function tipsoiDepartmentReport()
    {
        if (!hasPermission("HRM Management") && !in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 4])) {
            die("Unauthorized access.");
        }
        $this->view('employee-modules/tipsoi-department-report', [
            'current_route' => 'tipsoi-department-report',
            'page_title'    => 'Tipsoi Department Report'
        ]);
    }

    public function getTipsoiDepartmentData()
    {
        header('Content-Type: application/json');
        if (!hasPermission("HRM Management") && !in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 4])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $dateRange = $_GET['range'] ?? '';
        if (empty($dateRange)) {
            $firstDay = date('Y-m-d', strtotime('-6 days'));
            $lastDay = date('Y-m-d');
        } else {
            $parts = explode(' - ', $dateRange);
            $firstDay = date('Y-m-d', strtotime(trim($parts[0])));
            if (count($parts) > 1 && !empty(trim($parts[1]))) {
                $lastDay = date('Y-m-d', strtotime(trim($parts[1])));
            } else {
                $lastDay = $firstDay;
            }
        }

        $diff = (strtotime($lastDay) - strtotime($firstDay)) / 86400;
        if ($diff > 31) {
            echo json_encode(['success' => false, 'message' => 'Date range cannot exceed 31 days.']);
            exit;
        }

        $oldTimezone = date_default_timezone_get();
        try {
            $token = $this->externalApiService->getBearerToken();
            if (!$token) {
                echo json_encode(['success' => false, 'message' => 'Failed to authenticate with Tipsoi API.']);
                exit;
            }

            date_default_timezone_set('Asia/Dhaka');
            $fromTime = strtotime($firstDay . ' 00:00:00') * 1000;
            $toTime = strtotime($lastDay . ' 23:59:59') * 1000;

            $url = "https://app.tipsoi.ai/inovace-client/api/v1/attendance?" . http_build_query([
                'officeId' => 4699,
                'order' => 'asc',
                'pageNumber' => 0,
                'perPage' => 2000,
                'reportType' => 'department',
                'sortKey' => 'reportPosition',
                'status' => 1,
                'from' => $fromTime,
                'to' => $toTime
            ]);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $res = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($res, true);
            $attendance = $data['attendance'] ?? [];

            $dates = [];
            $current = strtotime($firstDay);
            $last = strtotime($lastDay);
            while ($current <= $last) {
                $dates[] = [
                    'db_date' => date('Y-m-d', $current),
                    'display' => date('d.m.Y (D)', $current)
                ];
                $current = strtotime('+1 day', $current);
            }

            $rows = [];
            foreach ($attendance as $emp) {
                $empLogs = $emp['attendance'] ?? [];
                
                $logsByDate = [];
                foreach ($empLogs as $timestamp => $log) {
                    $logDate = '';
                    if (isset($log['startTimeOfThisDayInMillis'])) {
                        $logDate = date('Y-m-d', $log['startTimeOfThisDayInMillis'] / 1000);
                    } else {
                        $dateParts = explode(' ', $log['date'] ?? '');
                        if (!empty($dateParts[0])) {
                            $dParts = explode('.', $dateParts[0]);
                            if (count($dParts) === 3) {
                                $logDate = $dParts[2] . '-' . $dParts[1] . '-' . $dParts[0];
                            }
                        }
                    }
                    if ($logDate !== '') {
                        $logsByDate[$logDate] = $log;
                    }
                }

                $dateLogs = [];
                foreach ($dates as $dObj) {
                    $d = $dObj['db_date'];
                    if (isset($logsByDate[$d])) {
                        $log = $logsByDate[$d];
                        
                        $status = 'Absent';
                        if ($log['isLeave'] == 1) {
                            $status = 'Leave';
                        } elseif ($log['isOffday'] == 1) {
                            $status = 'Off Day';
                        } elseif ($log['isHoliday'] == 1) {
                            $status = 'Holiday';
                        } elseif ($log['isPresent'] == 1) {
                            $status = $log['isLate'] == 1 ? 'Late' : 'Present';
                        }

                        $dateLogs[$d] = [
                            'status' => $status,
                            'entry' => ($log['firstLoggedTime'] !== '-' && !empty($log['firstLoggedTime'])) ? $log['firstLoggedTime'] : '—',
                            'exit' => ($log['lastLoggedTime'] !== '-' && !empty($log['lastLoggedTime']) && trim($log['lastLoggedTime']) !== '') ? $log['lastLoggedTime'] : '—',
                            'hours' => ($log['totalHour'] !== '-' && !empty($log['totalHour'])) ? $log['totalHour'] : '—'
                        ];
                    } else {
                        $dateLogs[$d] = [
                            'status' => 'Absent',
                            'entry' => '—',
                            'exit' => '—',
                            'hours' => '—'
                        ];
                    }
                }

                $rows[] = [
                    'id' => $emp['employeeOfficeId'] ?? $emp['employeeId'] ?? '—',
                    'name' => $emp['employeeName'] ?? '—',
                    'designation' => $emp['designationName'] ?? '—',
                    'department' => $emp['departmentName'] ?? '—',
                    'workplace' => $emp['workplaceName'] ?? '—',
                    'employeeType' => $emp['employmentTypeName'] ?? '—',
                    'shift' => $emp['shiftGroupName'] ?? '—',
                    'photo' => $emp['employeePictureUrl'] ?? '',
                    'attendance' => $dateLogs
                ];
            }

            date_default_timezone_set($oldTimezone);
            echo json_encode([
                'success' => true,
                'dates' => $dates,
                'rows' => $rows
            ]);

        } catch (Exception $e) {
            date_default_timezone_set($oldTimezone);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

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
            $token = $this->externalApiService->getUserBearerToken(false);
            if (!$token) {
                echo json_encode([
                    'success' => false,
                    'credentialsMismatch' => true,
                    'isRegistered' => false,
                    'message' => 'ERP এবং Tipsoi ক্রেডেনশিয়াল মিলছে না। অনুগ্রহ করে পাসওয়ার্ড যাচাই করুন অথবা পুনরায় লগইন করুন।'
                ]);
                exit;
            }
            $masterToken = $this->externalApiService->getBearerToken();

            $normalizeIdPhp = function($id) {
                $str = trim((string)$id);
                return preg_match('/^\d+$/', $str) ? (string)intval($str) : $str;
            };
            $normOfficeId = $normalizeIdPhp($localOfficeId);

            $tipsoiId = $_SESSION['user']['tipsoi_id'] ?? null;
            $tipsoiProfile = null;
            $matchedEmp = null;
            $externalEmployees = [];

            // 1. If we already have the tipsoi_id from session/JWT, fetch Basic Profile directly
            if ($tipsoiId) {
                $basicInfo = $this->externalApiService->getEmployeeBasicProfile($tipsoiId, $token ?: $masterToken);
                if ($basicInfo) {
                    $tipsoiProfile = [
                        'id' => $basicInfo['id'] ?? $tipsoiId,
                        'employeeName' => $basicInfo['name'] ?? '',
                        'employeeOfficeId' => $basicInfo['employeeOfficeId'] ?? '',
                        'personId3rdPartyDevice' => $basicInfo['personId3rdPartyDevice'] ?? '',
                        'email' => $basicInfo['email'] ?? '',
                        'departmentName' => $basicInfo['department']['name'] ?? '',
                        'subDepartmentName' => $basicInfo['subDepartment']['name'] ?? '',
                        'designationName' => $basicInfo['designation']['name'] ?? '',
                        'employmentTypeName' => $basicInfo['employmentType']['name'] ?? '',
                        'shiftGroupName' => $basicInfo['shiftGroupName'] ?? '',
                        'gender' => (($basicInfo['gender'] ?? 1) == 1 ? 'Male' : (($basicInfo['gender'] ?? 1) == 2 ? 'Female' : 'Other')),
                        'primaryDisplayText' => $basicInfo['primaryDisplayText'] ?? 'Check in',
                        'secondaryDisplayText' => $basicInfo['secondaryDisplayText'] ?? 'Check out',
                        'rfid' => $basicInfo['rfid'] ?? '',
                        'reportPosition' => $basicInfo['employeeReportPosition'] ?? '',
                        'leavePolicyName' => $basicInfo['leavePolicyName'] ?? '',
                        'joiningDate' => $basicInfo['dateOfJoin'] ?? null,
                        'endDate' => $basicInfo['dateOfResignation'] ?? null,
                        'firstApproverName' => $basicInfo['firstApproverName'] ?? '',
                        'secondApproverName' => $basicInfo['secondApproverName'] ?? '',
                        'firstLateApproverName' => $basicInfo['firstLateApproverName'] ?? '',
                        'secondLateApproverName' => $basicInfo['secondLateApproverName'] ?? '',
                        'firstManualEntryApproverName' => $basicInfo['firstManualEntryApproverName'] ?? '',
                        'secondManualEntryApproverName' => $basicInfo['secondManualEntryApproverName'] ?? '',
                        'allowOvertime' => $basicInfo['allowOvertime'] ?? 0,
                        'includeInAttendanceReport' => $basicInfo['includeInAttendanceReport'] ?? 1,
                        'status' => $basicInfo['status'] ?? 1,
                        'photo' => $basicInfo['employeePictureUrl'] ?? '',
                    ];
                    $normOfficeId = $normalizeIdPhp($basicInfo['employeeOfficeId'] ?? $localOfficeId);
                }
            }

            // Fallback: Fetch employees list from Tipsoi
            if (!$tipsoiProfile) {
                $empUrl = "https://app.tipsoi.ai/inovace-client/api/v1/employee?order=asc&pageNumber=0&perPage=2000&status=1";
                $ch = curl_init($empUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . ($masterToken ?: $token),
                    'Accept: application/json'
                ]);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                $res = curl_exec($ch);
                curl_close($ch);
                
                $empData = json_decode($res, true);
                $externalEmployees = $empData['data'] ?? $empData['employees'] ?? $empData['content'] ?? $empData ?? [];

                foreach ($externalEmployees as $emp) {
                    $empEmail = strtolower(trim($emp['email'] ?? ''));
                    $empOid = $normalizeIdPhp($emp['employeeOfficeId'] ?? $emp['employeeId'] ?? '');

                    if (($empEmail !== '' && $empEmail === $localEmail) || ($empOid !== '' && $empOid === $normOfficeId)) {
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

                if ($tipsoiId) {
                    $basicInfo = $this->externalApiService->getEmployeeBasicProfile($tipsoiId, $token ?: $masterToken);
                    if ($basicInfo) {
                        $tipsoiProfile = [
                            'id' => $basicInfo['id'] ?? $tipsoiId,
                            'employeeName' => $basicInfo['name'] ?? '',
                            'employeeOfficeId' => $basicInfo['employeeOfficeId'] ?? '',
                            'personId3rdPartyDevice' => $basicInfo['personId3rdPartyDevice'] ?? '',
                            'email' => $basicInfo['email'] ?? '',
                            'departmentName' => $basicInfo['department']['name'] ?? '',
                            'subDepartmentName' => $basicInfo['subDepartment']['name'] ?? '',
                            'designationName' => $basicInfo['designation']['name'] ?? '',
                            'employmentTypeName' => $basicInfo['employmentType']['name'] ?? '',
                            'shiftGroupName' => $basicInfo['shiftGroupName'] ?? '',
                            'gender' => (($basicInfo['gender'] ?? 1) == 1 ? 'Male' : (($basicInfo['gender'] ?? 1) == 2 ? 'Female' : 'Other')),
                            'primaryDisplayText' => $basicInfo['primaryDisplayText'] ?? 'Check in',
                            'secondaryDisplayText' => $basicInfo['secondaryDisplayText'] ?? 'Check out',
                            'rfid' => $basicInfo['rfid'] ?? '',
                            'reportPosition' => $basicInfo['employeeReportPosition'] ?? '',
                            'leavePolicyName' => $basicInfo['leavePolicyName'] ?? '',
                            'joiningDate' => $basicInfo['dateOfJoin'] ?? null,
                            'endDate' => $basicInfo['dateOfResignation'] ?? null,
                            'firstApproverName' => $basicInfo['firstApproverName'] ?? '',
                            'secondApproverName' => $basicInfo['secondApproverName'] ?? '',
                            'firstLateApproverName' => $basicInfo['firstLateApproverName'] ?? '',
                            'secondLateApproverName' => $basicInfo['secondLateApproverName'] ?? '',
                            'firstManualEntryApproverName' => $basicInfo['firstManualEntryApproverName'] ?? '',
                            'secondManualEntryApproverName' => $basicInfo['secondManualEntryApproverName'] ?? '',
                            'allowOvertime' => $basicInfo['allowOvertime'] ?? 0,
                            'includeInAttendanceReport' => $basicInfo['includeInAttendanceReport'] ?? 1,
                            'status' => $basicInfo['status'] ?? 1,
                            'photo' => $basicInfo['employeePictureUrl'] ?? '',
                        ];
                    }
                }

                if (!$tipsoiProfile) {
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
                        'joiningDate' => null,
                        'endDate' => null,
                        'firstApproverName' => '',
                        'secondApproverName' => '',
                        'firstLateApproverName' => '',
                        'secondLateApproverName' => '',
                        'firstManualEntryApproverName' => '',
                        'secondManualEntryApproverName' => '',
                        'allowOvertime' => 0,
                        'includeInAttendanceReport' => 1,
                        'status' => 1,
                        'photo' => '',
                    ];
                }
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
                'Authorization: Bearer ' . ($masterToken ?: $token),
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
                    $empTid = $empBal['employeeId'] ?? null;
                    if ($empOid === $normOfficeId || ($tipsoiId && (string)$empTid === (string)$tipsoiId)) {
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
                'Authorization: Bearer ' . ($masterToken ?: $token),
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
                    $empTid = $empHist['employeeId'] ?? null;
                    if ($empOid === $normOfficeId || ($tipsoiId && (string)$empTid === (string)$tipsoiId)) {
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
                    }
                }
            }

            // 5. Fetch Attendance Logs for selected month
            $firstDay = date('Y-m-01', strtotime($selectedMonth . '-01'));
            $lastDay = date('Y-m-t', strtotime($selectedMonth . '-01'));

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
                'Authorization: Bearer ' . ($masterToken ?: $token),
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
                    $empTid = $empAtt['employeeId'] ?? null;
                    if ($empOid === $normOfficeId || ($tipsoiId && (string)$empTid === (string)$tipsoiId)) {
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

            // 6. Aggregate pending requests for this employee
            $myPendingRequests = [];

            // A. Pending Leave Applications (with exact ID from leave-management/leave-history)
            $kw = $normOfficeId ?: ($tipsoiProfile['employeeName'] ?? ($employee['full_name'] ?? ''));
            $leaveHistUrl = "https://app.tipsoi.ai/inovace-client/api/v1/leave-management/leave-history?page=0&count=100&status=0" . ($kw ? "&keyword=" . urlencode($kw) : "");
            $ch = curl_init($leaveHistUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . ($masterToken ?: $token), 'Accept: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $leaveRes = curl_exec($ch);
            curl_close($ch);
            $leaveJson = json_decode($leaveRes, true);
            $leaveLogs = $leaveJson['leaveLogList'] ?? [];

            $foundPendingLeaves = false;
            if (is_array($leaveLogs) && count($leaveLogs) > 0) {
                foreach ($leaveLogs as $aLeave) {
                    $logEmpId = $aLeave['appliedBy'] ?? $aLeave['employeeId'] ?? null;
                    $logOfficeId = $normalizeIdPhp($aLeave['employeeOfficeId'] ?? '');
                    if (($tipsoiId && (string)$logEmpId === (string)$tipsoiId) || ($normOfficeId && $logOfficeId === $normOfficeId)) {
                        $foundPendingLeaves = true;
                        $sDate = !empty($aLeave['startDate']) ? (is_numeric($aLeave['startDate']) ? date('Y-m-d', (int)($aLeave['startDate'] / 1000)) : date('Y-m-d', strtotime($aLeave['startDate']))) : '—';
                        $eDate = !empty($aLeave['endDate']) ? (is_numeric($aLeave['endDate']) ? date('Y-m-d', (int)($aLeave['endDate'] / 1000)) : date('Y-m-d', strtotime($aLeave['endDate']))) : $sDate;
                        $dateStr = ($sDate === $eDate) ? $sDate : "$sDate to $eDate";
                        $catName = $aLeave['categoryName'] ?? 'Leave';
                        $myPendingRequests[] = [
                            'id' => $aLeave['id'] ?? null,
                            'rawType' => 'leave',
                            'requestType' => 'Leave Request (' . $catName . ')',
                            'startDate' => $dateStr,
                            'reason' => $aLeave['reason'] ?? '—',
                            'firstApprover' => $aLeave['firstApproverName'] ?? ($tipsoiProfile['firstApproverName'] ?? ''),
                            'firstApprovalStatus' => $aLeave['firstApprovalStatus'] ?? 0,
                            'secondApprover' => $aLeave['secondApproverName'] ?? ($tipsoiProfile['secondApproverName'] ?? ''),
                            'secondApprovalStatus' => $aLeave['secondApprovalStatus'] ?? 0,
                            'status' => 0
                        ];
                    }
                }
            }

            if (!$foundPendingLeaves) {
                // Fallback to myHistory
                foreach ($myHistory as $item) {
                    if ((int)($item['approvalStatus'] ?? 0) === 0) {
                        $myPendingRequests[] = [
                            'id' => $item['id'] ?? null,
                            'rawType' => 'leave',
                            'requestType' => 'Leave Request (' . ($item['categoryName'] ?? 'Leave') . ')',
                            'startDate' => $item['startDate'],
                            'reason' => $item['reason'] ?? '—',
                            'firstApprover' => $tipsoiProfile['firstApproverName'] ?? '',
                            'firstApprovalStatus' => 0,
                            'secondApprover' => $tipsoiProfile['secondApproverName'] ?? '',
                            'secondApprovalStatus' => 0,
                            'status' => 0
                        ];
                    }
                }
            }

            // B. Pending Late Applications
            if ($tipsoiId) {
                $lateUrl = "https://app.tipsoi.ai/inovace-client/api/v1/late-approval-management/approval-requests/" . urlencode($tipsoiId);
                $ch = curl_init($lateUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . ($token ?: $masterToken), 'Accept: application/json']);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                $lateRes = curl_exec($ch);
                curl_close($ch);
                $lateJson = json_decode($lateRes, true);
                $lateApps = $lateJson['lateApprovalApplications'] ?? $lateJson['data'] ?? [];
                if (is_array($lateApps)) {
                    foreach ($lateApps as $lateApp) {
                        $appStatus = $lateApp['approvalStatus'] ?? $lateApp['status'] ?? 0;
                        if ((int)$appStatus === 0) {
                            $rawLate = $lateApp['attendanceDate'] ?? $lateApp['date'] ?? null;
                            $attDate = '—';
                            if (!empty($rawLate)) {
                                $attDate = is_numeric($rawLate) ? date('Y-m-d', (int)($rawLate / 1000)) : date('Y-m-d', strtotime($rawLate));
                            }
                            $myPendingRequests[] = [
                                'id' => $lateApp['lateApprovalLogId'] ?? $lateApp['id'] ?? null,
                                'rawType' => 'late',
                                'requestType' => 'Late Excuse',
                                'startDate' => $attDate,
                                'reason' => $lateApp['reason'] ?? '—',
                                'firstApprover' => $lateApp['firstApproverName'] ?? ($tipsoiProfile['firstLateApproverName'] ?? ''),
                                'firstApprovalStatus' => $lateApp['firstApprovalStatus'] ?? 0,
                                'secondApprover' => $lateApp['secondApproverName'] ?? ($tipsoiProfile['secondLateApproverName'] ?? ''),
                                'secondApprovalStatus' => $lateApp['secondApprovalStatus'] ?? 0,
                                'status' => 0
                            ];
                        }
                    }
                }
            }

            // C. Pending Manual Entry / Missing Punch / Field Visit
            if ($tipsoiId) {
                $manualUrl = "https://app.tipsoi.ai/inovace-client/api/v1/employee/profile/manual/" . urlencode($tipsoiId);
                $ch = curl_init($manualUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . ($token ?: $masterToken), 'Accept: application/json']);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                $manRes = curl_exec($ch);
                curl_close($ch);
                $manJson = json_decode($manRes, true);
                $manApps = $manJson['manualEntryResponseList'] ?? $manJson['manualEntryApplications'] ?? [];
                if (is_array($manApps)) {
                    foreach ($manApps as $manApp) {
                        $appStatus = $manApp['applicationStatus'] ?? $manApp['status'] ?? 0;
                        if ((int)$appStatus === 0) {
                            $typeVal = $manApp['manualEntryType'] ?? $manApp['type'] ?? 1;
                            $reqType = ($typeVal == 1) ? 'Manual Entry' : (($typeVal == 2) ? 'Missing Punch' : 'Field Visit');
                            
                            $rawStart = $manApp['startDate'] ?? $manApp['entryTime'] ?? null;
                            $formattedDate = '—';
                            if (!empty($rawStart)) {
                                if (is_numeric($rawStart)) {
                                    $formattedDate = ($rawStart > 10000000000) ? date('Y-m-d', (int)($rawStart / 1000)) : date('Y-m-d', (int)$rawStart);
                                } else {
                                    $t = strtotime($rawStart);
                                    $formattedDate = ($t !== false && $t > 0) ? date('Y-m-d', $t) : '—';
                                }
                            }

                            $myPendingRequests[] = [
                                'id' => $manApp['manualEntryId'] ?? $manApp['id'] ?? null,
                                'rawType' => 'manual',
                                'requestType' => $reqType,
                                'startDate' => $formattedDate,
                                'reason' => $manApp['reason'] ?? '—',
                                'firstApprover' => $manApp['firstManualEntryApproverName'] ?? ($tipsoiProfile['firstManualEntryApproverName'] ?? ''),
                                'firstApprovalStatus' => (int)($manApp['firstManualEntryApprovalStatus'] ?? 0),
                                'secondApprover' => $manApp['secondManualEntryApproverName'] ?? ($tipsoiProfile['secondManualEntryApproverName'] ?? ''),
                                'secondApprovalStatus' => (int)($manApp['secondManualEntryApprovalStatus'] ?? 0),
                                'status' => 0
                            ];
                        }
                    }
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

    /**
     * GET /api/employee/tipsoi-history
     * Unified History endpoint for Leave, Late, Manual Entry, and Mobile / Selfie punches
     */
    public function getTipsoiHistory()
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

        $selectedMonth = $_GET['month'] ?? date('Y-m'); // e.g. 2026-08
        $year = date('Y', strtotime($selectedMonth . '-01'));

        try {
            $token = $this->externalApiService->getUserBearerToken(false);
            if (!$token) {
                echo json_encode([
                    'success' => false,
                    'credentialsMismatch' => true,
                    'message' => 'ERP এবং Tipsoi ক্রেডেনশিয়াল মিলছে না। অনুগ্রহ করে পাসওয়ার্ড যাচাই করুন অথবা পুনরায় লগইন করুন।'
                ]);
                exit;
            }
            $masterToken = $this->externalApiService->getBearerToken();

            $normalizeIdPhp = function($id) {
                $str = trim((string)$id);
                return preg_match('/^\d+$/', $str) ? (string)intval($str) : $str;
            };
            $normOfficeId = $normalizeIdPhp($localOfficeId);
            $tipsoiId = $_SESSION['user']['tipsoi_id'] ?? null;

            // Resolve Tipsoi ID if missing in session
            if (!$tipsoiId) {
                $empUrl = "https://app.tipsoi.ai/inovace-client/api/v1/employee?order=asc&pageNumber=0&perPage=2000&status=1";
                $ch = curl_init($empUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . ($masterToken ?: $token), 'Accept: application/json']);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                $res = curl_exec($ch);
                curl_close($ch);
                $empData = json_decode($res, true);
                $extEmps = $empData['data'] ?? $empData['employees'] ?? [];
                foreach ($extEmps as $emp) {
                    $empEmail = strtolower(trim($emp['email'] ?? ''));
                    $empOid = $normalizeIdPhp($emp['employeeOfficeId'] ?? $emp['employeeId'] ?? '');
                    if (($empEmail !== '' && $empEmail === $localEmail) || ($empOid !== '' && $empOid === $normOfficeId)) {
                        $tipsoiId = $emp['id'] ?? $emp['employeeId'] ?? null;
                        $normOfficeId = $normalizeIdPhp($emp['employeeOfficeId'] ?? $normOfficeId);
                        break;
                    }
                }
            }

            $firstDay = date('Y-m-01', strtotime($selectedMonth . '-01'));
            $lastDay = date('Y-m-t', strtotime($selectedMonth . '-01'));
            $fromMonthStart = strtotime($firstDay . ' 00:00:00') * 1000;
            $toMonthEnd = strtotime($lastDay . ' 23:59:59') * 1000;

            $fromYearStart = strtotime($year . '-01-01 00:00:00') * 1000;
            $toYearEnd = strtotime($year . '-12-31 23:59:59') * 1000;

            // 1. Fetch Leave History
            $historyUrl = "https://app.tipsoi.ai/inovace-client/api/v1/attendance/leave-history?" . http_build_query([
                'employeeBeforeEndTime' => 'true',
                'officeId' => 4699,
                'order' => 'desc',
                'pageNumber' => 0,
                'perPage' => 2000,
                'sortKey' => 'reportPosition',
                'status' => 1,
                'from' => $fromYearStart,
                'to' => $toYearEnd
            ]);
            $ch = curl_init($historyUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . ($masterToken ?: $token), 'Accept: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $historyRes = curl_exec($ch);
            curl_close($ch);
            $historyData = json_decode($historyRes, true);

            // Fetch pending leave IDs map from leave-management/leave-history
            $leaveIdMap = [];
            $kwHist = $normOfficeId ?: ($employee['full_name'] ?? '');
            if ($kwHist) {
                $lHistUrl = "https://app.tipsoi.ai/inovace-client/api/v1/leave-management/leave-history?page=0&count=100&status=0&keyword=" . urlencode($kwHist);
                $ch = curl_init($lHistUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . ($masterToken ?: $token), 'Accept: application/json']);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                $lRes = curl_exec($ch);
                curl_close($ch);
                $lJson = json_decode($lRes, true);
                foreach ($lJson['leaveLogList'] ?? [] as $lLog) {
                    $logId = $lLog['id'] ?? null;
                    if ($logId) {
                        $sD = !empty($lLog['startDate']) ? (is_numeric($lLog['startDate']) ? date('Y-m-d', (int)($lLog['startDate'] / 1000)) : date('Y-m-d', strtotime($lLog['startDate']))) : '';
                        $cName = strtolower(trim($lLog['categoryName'] ?? ''));
                        $leaveIdMap[$cName . '_' . $sD] = $logId;
                        $leaveIdMap[$sD] = $logId;
                    }
                }
            }

            $leavesList = [];
            if ($historyData && isset($historyData['responseForAllEmployees'])) {
                foreach ($historyData['responseForAllEmployees'] as $empHist) {
                    $empOid = $normalizeIdPhp($empHist['employeeOfficeId'] ?? $empHist['employeeId'] ?? '');
                    $empTid = $empHist['employeeId'] ?? null;
                    if ($empOid === $normOfficeId || ($tipsoiId && (string)$empTid === (string)$tipsoiId)) {
                        $rawList = $empHist['leaveLeaveInfo']['leaveDataList'] ?? [];
                        foreach ($rawList as $item) {
                            if (!empty($item['from'])) {
                                $startDate = date('Y-m-d', strtotime($item['from']));
                                $endDate = date('Y-m-d', strtotime($item['to'] ?? $item['from']));
                                $catName = $item['leaveCategoryName'] ?? 'Leave';
                                $catKey = strtolower(trim($catName)) . '_' . $startDate;
                                $resolvedId = $leaveIdMap[$catKey] ?? ($leaveIdMap[$startDate] ?? null);

                                $leavesList[] = [
                                    'id' => $resolvedId,
                                    'categoryName' => $catName,
                                    'startDate' => $startDate,
                                    'endDate' => $endDate,
                                    'approvalStatus' => (int)($item['approvalStatus'] ?? 0),
                                    'reason' => $item['reason'] ?? '—',
                                    'duration' => !empty($item['days']) ? (float)$item['days'] : 1.0,
                                    'appliedDate' => !empty($item['appliedDate']) ? (is_numeric($item['appliedDate']) ? date('Y-m-d', (int)($item['appliedDate'] / 1000)) : date('Y-m-d', strtotime($item['appliedDate']))) : '—',
                                    'firstApprover' => $item['firstApproverName'] ?? '',
                                    'secondApprover' => $item['secondApproverName'] ?? ''
                                ];
                            }
                        }
                    }
                }
            }

            // 2. Fetch Late Request History
            $latesList = [];
            if ($tipsoiId) {
                $lateUrl = "https://app.tipsoi.ai/inovace-client/api/v1/late-approval-management/late-approvals/" . urlencode($tipsoiId);
                $ch = curl_init($lateUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . ($token ?: $masterToken), 'Accept: application/json']);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 12);
                $lateRes = curl_exec($ch);
                curl_close($ch);
                $lateData = json_decode($lateRes, true);
                $rawLates = $lateData['lateLogList'] ?? [];
                foreach ($rawLates as $late) {
                    $rawLateDate = $late['attendanceDate'] ?? $late['date'] ?? null;
                    $logDate = '—';
                    if (!empty($rawLateDate)) {
                        $logDate = is_numeric($rawLateDate) ? date('Y-m-d', (int)($rawLateDate / 1000)) : date('Y-m-d', strtotime($rawLateDate));
                    }

                    $inTimeStr = '—';
                    if (isset($late['entryTime']) && is_numeric($late['entryTime'])) {
                        $inTimeStr = date('h:i A', (int)($late['entryTime'] / 1000));
                    } elseif (isset($late['inTime'])) {
                        $inTimeStr = $late['inTime'];
                    }

                    $latesList[] = [
                        'id' => $late['lateApprovalLogId'] ?? $late['id'] ?? null,
                        'date' => $logDate,
                        'inTime' => $inTimeStr,
                        'lateBy' => $late['lateBy'] ?? '—',
                        'reason' => $late['reason'] ?? '—',
                        'approvalStatus' => (int)($late['approvalStatus'] ?? $late['status'] ?? 0),
                        'approverComment' => $late['approverComment'] ?? '—',
                        'firstApprover' => $late['firstApproverName'] ?? $late['firstLateApproverName'] ?? '',
                        'secondApprover' => $late['secondApproverName'] ?? $late['secondLateApproverName'] ?? ''
                    ];
                }
            }

            // 3. Fetch Manual Entries & Field Visits History
            $manualList = [];
            $manUrl = "https://app.tipsoi.ai/inovace-client/api/v1/manual-entry?officeId=4699&order=desc&pageNumber=0&perPage=200";
            $ch = curl_init($manUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . ($masterToken ?: $token), 'Accept: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 12);
            $manRes = curl_exec($ch);
            curl_close($ch);
            $manData = json_decode($manRes, true);
            $rawManuals = $manData['manualEntries'] ?? [];
            foreach ($rawManuals as $entry) {
                $entryOid = $normalizeIdPhp($entry['employeeOfficeId'] ?? '');
                $entryTid = $entry['employeeId'] ?? null;
                if ($entryOid === $normOfficeId || ($tipsoiId && (string)$entryTid === (string)$tipsoiId)) {
                    $typeVal = (int)($entry['manualEntryType'] ?? 1);
                    $typeName = ($typeVal === 1) ? 'Manual Entry' : (($typeVal === 2) ? 'Late Excuse' : 'Field Visit');
                    
                    $startDate = !empty($entry['startDate']) ? (is_numeric($entry['startDate']) ? date('Y-m-d', (int)($entry['startDate'] / 1000)) : date('Y-m-d', strtotime($entry['startDate']))) : '—';
                    $endDate = !empty($entry['endDate']) ? (is_numeric($entry['endDate']) ? date('Y-m-d', (int)($entry['endDate'] / 1000)) : date('Y-m-d', strtotime($entry['endDate']))) : $startDate;
                    
                    $entryTime = !empty($entry['entryTime']) && is_numeric($entry['entryTime']) ? date('h:i A', (int)($entry['entryTime'] / 1000)) : '—';
                    $exitTime = !empty($entry['exitTime']) && is_numeric($entry['exitTime']) ? date('h:i A', (int)($entry['exitTime'] / 1000)) : '—';

                    $manualList[] = [
                        'id' => $entry['manualEntryId'] ?? $entry['id'] ?? null,
                        'type' => $typeVal,
                        'typeName' => $typeName,
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'entryTime' => $entryTime,
                        'exitTime' => $exitTime,
                        'area' => $entry['area'] ?? '',
                        'reason' => $entry['reason'] ?? '—',
                        'approvalStatus' => (int)($entry['applicationStatus'] ?? 0),
                        'firstApprover' => $entry['firstManualApproverName'] ?? '',
                        'secondApprover' => $entry['secondManualApproverName'] ?? ''
                    ];
                }
            }

            // 4. Fetch Mobile Punch & Selfie Attendance History
            $mobilePunchesList = [];
            $mobUrl = "https://app.tipsoi.ai/inovace-client/api/v1/mobile-punch?officeId=4699&order=desc&pageNumber=0&perPage=2000&status=1&from=$fromMonthStart&to=$toMonthEnd";
            $ch = curl_init($mobUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . ($masterToken ?: $token), 'Accept: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $mobRes = curl_exec($ch);
            curl_close($ch);
            $mobData = json_decode($mobRes, true);
            
            $dateWisePunches = $mobData['dateWiseMobilePunchResponses'] ?? [];
            foreach ($dateWisePunches as $dw) {
                $punchDate = $dw['date'] ?? '—';
                $pList = $dw['mobilePunchResponses'] ?? [];
                foreach ($pList as $p) {
                    $pOid = $normalizeIdPhp($p['employeeOfficeId'] ?? '');
                    $pTid = $p['employeeId'] ?? null;
                    if ($pOid === $normOfficeId || ($tipsoiId && (string)$pTid === (string)$tipsoiId)) {
                        $mobilePunchesList[] = [
                            'date' => $punchDate,
                            'checkInTime' => $p['checkInTime'] ?? '—',
                            'checkOutTime' => $p['checkOutTime'] ?? '—',
                            'siteName' => $p['siteName'] ?? '—',
                            'location' => $p['location'] ?? '—',
                            'photoUrl' => $p['photoUrl'] ?? $p['pictureUrl'] ?? '',
                            'voiceNoteUrl' => $p['voiceNoteUrl'] ?? '',
                            'remarks' => $p['remarks'] ?? '—',
                            'registeredSite' => !empty($p['registeredSite'])
                        ];
                    }
                }
            }

            // Also supplement from Attendance Report for GPS / Selfies if any
            $attUrl = "https://app.tipsoi.ai/inovace-client/api/v1/attendance?officeId=4699&order=desc&pageNumber=0&perPage=2000&reportType=department&status=1&from=$fromMonthStart&to=$toMonthEnd";
            $ch = curl_init($attUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . ($masterToken ?: $token), 'Accept: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $attRes = curl_exec($ch);
            curl_close($ch);
            $attData = json_decode($attRes, true);
            if (!empty($attData['attendance'])) {
                foreach ($attData['attendance'] as $empAtt) {
                    $attOid = $normalizeIdPhp($empAtt['employeeOfficeId'] ?? '');
                    $attTid = $empAtt['employeeId'] ?? null;
                    if ($attOid === $normOfficeId || ($tipsoiId && (string)$attTid === (string)$tipsoiId)) {
                        $logs = $empAtt['attendance'] ?? $empAtt['attendanceLogList'] ?? [];
                        foreach ($logs as $log) {
                            if (!empty($log['siteInPictureUrl']) || !empty($log['siteOutPictureUrl']) || !empty($log['siteLatitude'])) {
                                $mobilePunchesList[] = [
                                    'date' => $log['date'] ?? '—',
                                    'checkInTime' => $log['firstLoggedTime'] ?? '—',
                                    'checkOutTime' => $log['lastLoggedTime'] ?? '—',
                                    'siteName' => $log['siteName'] ?? 'Mobile Punch',
                                    'location' => (!empty($log['siteLatitude']) && !empty($log['siteLongitude'])) ? ($log['siteLatitude'] . ', ' . $log['siteLongitude']) : '—',
                                    'photoUrl' => $log['siteInPictureUrl'] ?? $log['siteOutPictureUrl'] ?? '',
                                    'voiceNoteUrl' => '',
                                    'remarks' => $log['remarks'] ?? 'Attendance Selfie',
                                    'latitude' => $log['siteLatitude'] ?? null,
                                    'longitude' => $log['siteLongitude'] ?? null,
                                    'registeredSite' => false
                                ];
                            }
                        }
                    }
                }
            }

            echo json_encode([
                'success' => true,
                'month' => $selectedMonth,
                'leaves' => $leavesList,
                'lates' => $latesList,
                'manualEntries' => $manualList,
                'mobilePunches' => $mobilePunchesList,
                'counts' => [
                    'leaves' => count($leavesList),
                    'lates' => count($latesList),
                    'manual' => count($manualList),
                    'mobile' => count($mobilePunchesList)
                ]
            ]);

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Internal Server Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * GET /api/hrm/devices
     * Fetch list of biometric devices from Tipsoi
     */
    public function getDevices()
    {
        header('Content-Type: application/json');
        try {
            $token = $this->externalApiService->getUserBearerToken(true) ?: $this->externalApiService->getBearerToken();
            if (!$token) {
                echo json_encode(['success' => false, 'message' => 'Failed to authenticate with Tipsoi API.']);
                exit;
            }

            $url = "https://app.tipsoi.ai/inovace-client/api/v1/devices";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $res = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($res, true);
            $devices = $data['devices'] ?? [];
            $formatted = [];
            foreach ($devices as $d) {
                if (($d['status'] ?? 0) == 1) {
                    $formatted[] = [
                        'id' => $d['id'],
                        'identifier' => $d['identifier'] ?? '',
                        'location' => $d['location'] ?? 'Device ' . $d['id'],
                        'isOnline' => !empty($d['isOnline'])
                    ];
                }
            }

            echo json_encode(['success' => true, 'devices' => $formatted]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * POST /api/employee/manual-entry/apply
     * Apply Manual Entry, Missing Punch (Device/Mobile), or Field Visit to Tipsoi
     */
    public function applyManualEntry()
    {
        header('Content-Type: application/json');
        try {
            $data = $this->getSessionEmployee();
            if (!$data) {
                echo json_encode(['success' => false, 'message' => 'Employee session not found.']);
                exit;
            }

            $token = $this->externalApiService->getUserBearerToken(false);
            if (!$token) {
                echo json_encode(['success' => false, 'message' => 'Tipsoi user login token not found. Please log in to ERP with your Tipsoi credentials.']);
                exit;
            }

            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true) ?: $_POST;

            $tipsoiId = $_SESSION['user']['tipsoi_id'] ?? null;
            if (!$tipsoiId) {
                $employee = $data['employee'];
                $localOfficeId = trim($employee['employee_id'] ?? '');
                $localEmail = strtolower(trim($employee['email'] ?? ''));
                $empUrl = "https://app.tipsoi.ai/inovace-client/api/v1/employee?order=asc&pageNumber=0&perPage=2000&status=1";
                $ch = curl_init($empUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                $res = curl_exec($ch);
                curl_close($ch);
                $empData = json_decode($res, true);
                $extEmps = $empData['data'] ?? $empData['employees'] ?? [];
                foreach ($extEmps as $emp) {
                    $empEmail = strtolower(trim($emp['email'] ?? ''));
                    $empOid = trim((string)($emp['employeeOfficeId'] ?? $emp['employeeId'] ?? ''));
                    if (($empEmail !== '' && $empEmail === $localEmail) || ($empOid !== '' && $empOid === $localOfficeId)) {
                        $tipsoiId = $emp['id'] ?? $emp['employeeId'] ?? null;
                        break;
                    }
                }
            }

            if (!$tipsoiId) {
                echo json_encode(['success' => false, 'message' => 'Tipsoi Employee ID could not be identified.']);
                exit;
            }

            $type = (int)($input['type'] ?? 1); // 1: Manual Entry, 2: Missing Punch, 3: Field Visit
            $date = !empty($input['date']) ? $input['date'] : date('Y-m-d');
            $reason = trim($input['reason'] ?? '');

            if (empty($reason)) {
                echo json_encode(['success' => false, 'message' => 'Please provide a reason or comment for this application.']);
                exit;
            }

            $payload = [
                'employeeId' => (int)$tipsoiId,
                'type' => $type,
                'reason' => $reason,
                'forceApproval' => false
            ];

            if ($type === 1) {
                // 1. Manual Entry
                $entryTimeStr = !empty($input['entryTime']) ? $input['entryTime'] : '09:30';
                $exitTimeStr = !empty($input['exitTime']) ? $input['exitTime'] : '18:30';

                $startDate = strtotime($date . ' 00:00:00') * 1000;
                $endDate = strtotime($date . ' 23:59:59') * 1000;
                $entryTimeMs = strtotime($date . ' ' . $entryTimeStr) * 1000;
                $exitTimeMs = strtotime($date . ' ' . $exitTimeStr) * 1000;

                $payload['startDate'] = $startDate;
                $payload['endDate'] = $endDate;
                $payload['entryTime'] = $entryTimeMs;
                $payload['exitTime'] = $exitTimeMs;

            } elseif ($type === 2) {
                // 2. Missing Punch
                $missingPunchType = ($input['missingPunchType'] ?? 'device') === 'mobile' ? 'mobile' : 'device';
                $entryExitType = (int)($input['entryExitType'] ?? 1); // 1: Entry, 2: Exit
                $punchTimeStr = !empty($input['punchTime']) ? $input['punchTime'] : '09:30';
                $punchTimeMs = strtotime($date . ' ' . $punchTimeStr) * 1000;

                $payload['missingPunchType'] = $missingPunchType;
                $payload['entryExitType'] = $entryExitType;

                if ($entryExitType === 1) {
                    $payload['entryTime'] = $punchTimeMs;
                } else {
                    $payload['exitTime'] = $punchTimeMs;
                }

                if ($missingPunchType === 'device') {
                    $deviceId = (int)($input['deviceId'] ?? 0);
                    if ($deviceId <= 0) {
                        echo json_encode(['success' => false, 'message' => 'Please select a valid device.']);
                        exit;
                    }
                    $payload['deviceId'] = $deviceId;
                } else {
                    $area = trim($input['area'] ?? $input['location'] ?? '');
                    if (empty($area)) {
                        echo json_encode(['success' => false, 'message' => 'Please provide a location/area for mobile punch.']);
                        exit;
                    }
                    $payload['area'] = $area;
                }

            } elseif ($type === 3) {
                // 3. Field Visit
                $entryTimeStr = !empty($input['entryTime']) ? $input['entryTime'] : '09:30';
                $exitTimeStr = !empty($input['exitTime']) ? $input['exitTime'] : '18:30';
                $area = trim($input['area'] ?? '');

                if (empty($area)) {
                    echo json_encode(['success' => false, 'message' => 'Please specify the visit destination or area.']);
                    exit;
                }

                $startDate = strtotime($date . ' 00:00:00') * 1000;
                $endDate = strtotime($date . ' 23:59:59') * 1000;
                $entryTimeMs = strtotime($date . ' ' . $entryTimeStr) * 1000;
                $exitTimeMs = strtotime($date . ' ' . $exitTimeStr) * 1000;

                $payload['startDate'] = $startDate;
                $payload['endDate'] = $endDate;
                $payload['entryTime'] = $entryTimeMs;
                $payload['exitTime'] = $exitTimeMs;
                $payload['area'] = $area;
            }

            // Submit to Tipsoi API
            $apiUrl = "https://app.tipsoi.ai/inovace-client/api/v1/attendance/manual-entry";
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $resData = json_decode($res, true);
            if ($httpCode >= 200 && $httpCode < 300) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Application submitted successfully!',
                    'data' => $resData
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $resData['message'] ?? 'Failed to submit application to Tipsoi.',
                    'details' => $resData
                ]);
            }

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
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
                    'shiftGroup' => $emp['shiftGroupName'] ?? '—',
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

    // ─── Tipsoi Pending Requests View
    public function tipsoiPendingRequests()
    {
        $data = $this->getSessionEmployee();
        if (!$data) {
            echo "Employee not found";
            return;
        }

        $employee = $data['employee'];

        $this->view('employee-modules/tipsoi-pending-requests', [
            'current_route' => 'tipsoi-pending-requests',
            'page_title'    => 'Tipsoi Pending Requests Filter',
            'employee'      => $employee,
        ]);
    }

    // ─── Get Tipsoi Pending Requests API
    public function getTipsoiPendingRequests()
    {
        header('Content-Type: application/json');

        try {
            $token = $this->externalApiService->getBearerToken();
            if (!$token) {
                echo json_encode(['success' => false, 'message' => 'Failed to authenticate with Tipsoi API.']);
                exit;
            }

            $config = require dirname(__DIR__) . '/config/config.php';
            $baseUrl = rtrim($config['tipsoi_base_url'] ?? 'https://app.tipsoi.ai/inovace-client/api/v1', '/');

            // Collect parameters
            $type = $_GET['type'] ?? '';
            $approverLevel = $_GET['approverLevel'] ?? '';
            $employeeId = $_GET['employeeId'] ?? '';
            $keyword = $_GET['keyword'] ?? '';
            $page = $_GET['page'] ?? '0';
            $count = $_GET['count'] ?? '100';
            $status = $_GET['status'] ?? '0';

            $queryParams = [
                'page' => $page,
                'count' => $count,
                'status' => $status
            ];
            
            if ($type !== '') $queryParams['type'] = $type;
            if ($approverLevel !== '') $queryParams['approverLevel'] = $approverLevel;
            if ($employeeId !== '') $queryParams['employeeId'] = $employeeId;
            if ($keyword !== '') $queryParams['keyword'] = $keyword;

            $queryString = http_build_query($queryParams);
            // Notice: API doc from image says endpoint is /application/pending-list
            $url = $baseUrl . '/application/pending-list?' . $queryString;

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $res = curl_exec($ch);
            
            if (curl_errno($ch)) {
                echo json_encode(['success' => false, 'message' => 'cURL Error: ' . curl_error($ch)]);
                curl_close($ch);
                exit;
            }
            
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $data = json_decode($res, true);
            if ($httpCode >= 200 && $httpCode < 300) {
                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                echo json_encode(['success' => false, 'message' => 'API Error: ' . $httpCode, 'response' => $data]);
            }

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ─── Get Tipsoi Employees API
    public function getTipsoiEmployees()
    {
        header('Content-Type: application/json');

        $data = $this->getSessionEmployee();
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Employee session not found.']);
            exit;
        }

        try {
            $token = $this->externalApiService->getBearerToken();
            if (!$token) {
                echo json_encode(['success' => false, 'message' => 'Failed to authenticate with Tipsoi API.']);
                exit;
            }

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
            
            if (curl_errno($ch)) {
                echo json_encode(['success' => false, 'message' => 'Network error while fetching employees.']);
                curl_close($ch);
                exit;
            }
            curl_close($ch);
            
            $empData = json_decode($res, true);
            $externalEmployees = $empData['data'] ?? $empData['employees'] ?? $empData['content'] ?? $empData ?? [];

            echo json_encode(['success' => true, 'employees' => $externalEmployees]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // ─── Get My Pending Approvals API (Exclusively using the logged-in user's token)
    public function getMyPendingApprovals()
    {
        header('Content-Type: application/json');

        $data = $this->getSessionEmployee();
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Employee session not found.']);
            exit;
        }

        $employee = $data['employee'];
        $localOfficeId = trim($employee['employee_id'] ?? '');
        $localEmail = strtolower(trim($employee['email'] ?? ''));

        try {
            // STRICT RULE: Only use user's personal token, NEVER fallback to master token
            $token = $this->externalApiService->getUserBearerToken(false);
            if (!$token) {
                echo json_encode([
                    'success' => false,
                    'credentialsMismatch' => true,
                    'message' => 'Tipsoi user token not found or session expired. Please re-login to synchronize your Tipsoi session.'
                ]);
                exit;
            }

            // Resolve calling user's own Tipsoi employee ID
            $tipsoiId = $_SESSION['user']['tipsoi_id'] ?? null;
            if (!$tipsoiId && $token) {
                $tokenParts = explode('.', $token);
                if (isset($tokenParts[1])) {
                    $payloadJson = base64_decode(str_pad(strtr($tokenParts[1], '-_', '+/'), strlen($tokenParts[1]) % 4, '=', STR_PAD_RIGHT));
                    $jwtPayload = json_decode($payloadJson, true);
                    if (!empty($jwtPayload['sub'])) {
                        $tipsoiId = $jwtPayload['sub'];
                        $_SESSION['user']['tipsoi_id'] = $tipsoiId;
                    }
                }
            }

            // Helper to make curl request using ONLY the user's token
            $callTipsoiWithUserToken = function($endpoint) use ($token) {
                $url = "https://app.tipsoi.ai/inovace-client/api/v1" . $endpoint;
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $token,
                    'Accept: application/json'
                ]);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                $res = curl_exec($ch);
                curl_close($ch);
                return json_decode($res, true) ?: [];
            };

            // If tipsoiId is still not found, try to resolve via user-scoped employee call
            if (!$tipsoiId) {
                $myInfo = $callTipsoiWithUserToken("/employee?order=asc&pageNumber=0&perPage=50&status=1&keyword=" . urlencode($localOfficeId));
                $empList = $myInfo['data'] ?? $myInfo['employees'] ?? $myInfo['content'] ?? [];
                foreach ($empList as $ext) {
                    $extOid = trim((string)($ext['employeeOfficeId'] ?? $ext['officeId'] ?? ''));
                    $extMail = strtolower(trim($ext['email'] ?? ''));
                    if (($localOfficeId !== '' && $extOid === $localOfficeId) || ($localEmail !== '' && $extMail === $localEmail)) {
                        $tipsoiId = $ext['employeeId'] ?? $ext['id'] ?? null;
                        $_SESSION['user']['tipsoi_id'] = $tipsoiId;
                        break;
                    }
                }
            }

            // Fetch local employee details to enrich missing office IDs / departments
            $localEmpMap = [];
            $q = $this->db->query("SELECT e.employee_id, e.full_name, d.name AS dept_name, deg.name AS desig_name FROM employees e LEFT JOIN departments d ON e.department_id = d.id LEFT JOIN designations deg ON e.designation_id = deg.id");
            if ($q) {
                while ($r = $q->fetch_assoc()) {
                    $cleanName = strtolower(trim($r['full_name']));
                    $localEmpMap[$cleanName] = [
                        'office_id' => trim($r['employee_id'] ?? ''),
                        'department' => trim($r['dept_name'] ?? '—'),
                        'designation' => trim($r['desig_name'] ?? '—')
                    ];
                }
            }

            // Helper to resolve 1st and 2nd approver names and statuses cleanly
            $resolveApprovers = function($item, $loggedUserName) {
                $firstApp = trim($item['firstApproverName'] ?? ($item['firstManualApproverName'] ?? ($item['firstLateApproverName'] ?? '')));
                $secondApp = trim($item['secondApproverName'] ?? ($item['secondManualApproverName'] ?? ($item['secondLateApproverName'] ?? '')));
                $otherApp = trim($item['otherApproverName'] ?? '');
                $otherStatus = $item['otherApproverStatus'] ?? null;
                $firstStatus = (int)($item['firstApprovalStatus'] ?? ($item['firstManualEntryApprovalStatus'] ?? ($item['firstLateApprovalStatus'] ?? 0)));
                $secondStatus = (int)($item['secondApprovalStatus'] ?? ($item['secondManualEntryApprovalStatus'] ?? ($item['secondLateApprovalStatus'] ?? 0)));

                if ($firstApp !== '' || $secondApp !== '') {
                    return [
                        'firstApproverName' => $firstApp ?: $loggedUserName,
                        'firstApprovalStatus' => $firstStatus,
                        'secondApproverName' => $secondApp ?: $otherApp,
                        'secondApprovalStatus' => $secondStatus,
                    ];
                }

                if (empty($otherApp)) {
                    return [
                        'firstApproverName' => $loggedUserName,
                        'firstApprovalStatus' => 0,
                        'secondApproverName' => '',
                        'secondApprovalStatus' => 0,
                    ];
                }

                $isOtherApproved = ($otherStatus === 'Accepted' || $otherStatus === 'Approved' || $otherStatus === 1 || $otherStatus === '1' || $firstStatus === 1);
                if ($isOtherApproved) {
                    return [
                        'firstApproverName' => $otherApp,
                        'firstApprovalStatus' => 1,
                        'secondApproverName' => $loggedUserName,
                        'secondApprovalStatus' => 0,
                    ];
                }

                return [
                    'firstApproverName' => $loggedUserName,
                    'firstApprovalStatus' => 0,
                    'secondApproverName' => $otherApp,
                    'secondApprovalStatus' => 0,
                ];
            };

            $pendingRequests = [];

            // 1. Fetch Manual Entry / Missing Punch / Field Visit (Personal Approver endpoint: GET /employee/profile/manual/approver)
            $manualRes = $callTipsoiWithUserToken("/employee/profile/manual/approver");
            $manualApps = $manualRes['manualEntryApplications'] ?? $manualRes['data'] ?? [];
            if (is_array($manualApps)) {
                foreach ($manualApps as $man) {
                    $id = $man['manualEntryId'] ?? $man['id'] ?? null;
                    if (!$id) continue;

                    $empName = trim($man['employeeName'] ?? '—');
                    $officeId = trim($man['employeeOfficeId'] ?? '');
                    $deptName = trim($man['departmentName'] ?? '');
                    $desigName = trim($man['designationName'] ?? '');

                    $lookupKey = strtolower($empName);
                    if (isset($localEmpMap[$lookupKey])) {
                        if (empty($officeId)) $officeId = $localEmpMap[$lookupKey]['office_id'];
                        if (empty($deptName) || $deptName === '—') $deptName = $localEmpMap[$lookupKey]['department'];
                        if (empty($desigName) || $desigName === '—') $desigName = $localEmpMap[$lookupKey]['designation'];
                    }

                    $typeVal = (int)($man['type'] ?? ($man['manualEntryType'] ?? 1));
                    $reqType = ($typeVal === 1) ? 'Manual Entry' : (($typeVal === 2) ? 'Missing Punch' : (($typeVal === 3) ? 'Field Visit' : 'Manual Entry'));
                    $subType = $man['typeText'] ?? $reqType;
                    if ($typeVal === 3 && !empty($man['area'])) {
                        $subType = 'Field Visit (' . $man['area'] . ')';
                    }

                    $sDateVal = $man['startDateLong'] ?? $man['startDate'] ?? $man['entryTimeLong'] ?? $man['entryTime'] ?? null;
                    $eDateVal = $man['endDateLong'] ?? $man['endDate'] ?? $man['exitTimeLong'] ?? $man['exitTime'] ?? $sDateVal;
                    $sDateStr = '—';
                    $eDateStr = '—';
                    if (!empty($sDateVal)) {
                        $sDateStr = is_numeric($sDateVal) ? date('Y-m-d', (int)($sDateVal / 1000)) : date('Y-m-d', strtotime($sDateVal));
                    }
                    if (!empty($eDateVal)) {
                        $eDateStr = is_numeric($eDateVal) ? date('Y-m-d', (int)($eDateVal / 1000)) : date('Y-m-d', strtotime($eDateVal));
                    } else {
                        $eDateStr = $sDateStr;
                    }

                    $inTimeVal = $man['entryTimeLong'] ?? $man['entryTime'] ?? null;
                    $outTimeVal = $man['exitTimeLong'] ?? $man['exitTime'] ?? null;
                    $inTimeStr = ($inTimeVal && is_numeric($inTimeVal)) ? date('h:i A', (int)($inTimeVal / 1000)) : '—';
                    $outTimeStr = ($outTimeVal && is_numeric($outTimeVal)) ? date('h:i A', (int)($outTimeVal / 1000)) : '—';

                    $appr = $resolveApprovers($man, $employee['full_name'] ?? 'Approver');

                    $pendingRequests[] = [
                        'id' => $id,
                        'employeeOfficeId' => $officeId,
                        'employeeName' => $empName,
                        'departmentName' => $deptName ?: '—',
                        'designationName' => $desigName ?: '—',
                        'requestType' => $reqType,
                        'subType' => $subType,
                        'startDate' => $sDateStr,
                        'endDate' => $eDateStr,
                        'entryTime' => $inTimeStr,
                        'exitTime' => $outTimeStr,
                        'reason' => $man['reason'] ?? '—',
                        'firstApproverName' => $appr['firstApproverName'],
                        'firstApprovalStatus' => $appr['firstApprovalStatus'],
                        'secondApproverName' => $appr['secondApproverName'],
                        'secondApprovalStatus' => $appr['secondApprovalStatus'],
                        'status' => 0
                    ];
                }
            }

            // 2. Fetch Late Request Applications (GET /late-approval-management/approval-requests/{yourOwnTipsoiEmployeeId})
            if ($tipsoiId) {
                $lateRes = $callTipsoiWithUserToken("/late-approval-management/approval-requests/" . urlencode($tipsoiId));
                $lateApps = $lateRes['lateApprovalApplications'] ?? $lateRes['data'] ?? [];
                if (is_array($lateApps)) {
                    foreach ($lateApps as $late) {
                        $id = $late['lateApprovalLogId'] ?? $late['id'] ?? null;
                        if (!$id) continue;

                        $empName = trim($late['employeeName'] ?? '—');
                        $officeId = trim($late['employeeOfficeId'] ?? '');
                        $deptName = trim($late['departmentName'] ?? '');
                        $desigName = trim($late['designationName'] ?? '');

                        $lookupKey = strtolower($empName);
                        if (isset($localEmpMap[$lookupKey])) {
                            if (empty($officeId)) $officeId = $localEmpMap[$lookupKey]['office_id'];
                            if (empty($deptName) || $deptName === '—') $deptName = $localEmpMap[$lookupKey]['department'];
                            if (empty($desigName) || $desigName === '—') $desigName = $localEmpMap[$lookupKey]['designation'];
                        }

                        $rawDate = $late['attendanceTime'] ?? $late['attendanceDate'] ?? $late['date'] ?? null;
                        $dateStr = '—';
                        if (!empty($rawDate)) {
                            $dateStr = is_numeric($rawDate) ? date('Y-m-d', (int)($rawDate / 1000)) : date('Y-m-d', strtotime($rawDate));
                        }

                        $subType = 'Late Excuse';
                        if (!empty($late['lateBy'])) {
                            $subType .= ' (' . $late['lateBy'] . ')';
                        }

                        $appr = $resolveApprovers($late, $employee['full_name'] ?? 'Approver');

                        $pendingRequests[] = [
                            'id' => $id,
                            'employeeOfficeId' => $officeId,
                            'employeeName' => $empName,
                            'departmentName' => $deptName ?: '—',
                            'designationName' => $desigName ?: '—',
                            'requestType' => 'Late Excuse',
                            'subType' => $subType,
                            'startDate' => $dateStr,
                            'endDate' => $dateStr,
                            'entryTime' => '—',
                            'exitTime' => '—',
                            'reason' => $late['reason'] ?? '—',
                            'firstApproverName' => $appr['firstApproverName'],
                            'firstApprovalStatus' => $appr['firstApprovalStatus'],
                            'secondApproverName' => $appr['secondApproverName'],
                            'secondApprovalStatus' => $appr['secondApprovalStatus'],
                            'status' => 0
                        ];
                    }
                }
            }

            // 3. Fetch Leave Applications (GET /remaining-leave/approvers/{approverEmployeeId})
            if ($tipsoiId) {
                $leaveRes = $callTipsoiWithUserToken("/remaining-leave/approvers/" . urlencode($tipsoiId));
                $leaveApps = $leaveRes['leaveApplications'] ?? $leaveRes['data'] ?? [];
                if (is_array($leaveApps)) {
                    foreach ($leaveApps as $leave) {
                        $id = $leave['id'] ?? $leave['leaveLogId'] ?? null;
                        if (!$id) continue;

                        $empName = trim($leave['employeeName'] ?? '—');
                        $officeId = trim($leave['employeeOfficeId'] ?? '');
                        $deptName = trim($leave['employeeDepartmentName'] ?? $leave['departmentName'] ?? '');
                        $desigName = trim($leave['employeeDesignationName'] ?? $leave['designationName'] ?? '');

                        $lookupKey = strtolower($empName);
                        if (isset($localEmpMap[$lookupKey])) {
                            if (empty($officeId)) $officeId = $localEmpMap[$lookupKey]['office_id'];
                            if (empty($deptName) || $deptName === '—') $deptName = $localEmpMap[$lookupKey]['department'];
                            if (empty($desigName) || $desigName === '—') $desigName = $localEmpMap[$lookupKey]['designation'];
                        }

                        $sDate = !empty($leave['startDate']) ? (is_numeric($leave['startDate']) ? date('Y-m-d', (int)($leave['startDate'] / 1000)) : date('Y-m-d', strtotime($leave['startDate']))) : '—';
                        $eDate = !empty($leave['endDate']) ? (is_numeric($leave['endDate']) ? date('Y-m-d', (int)($leave['endDate'] / 1000)) : date('Y-m-d', strtotime($leave['endDate']))) : $sDate;
                        $catName = $leave['categoryName'] ?? 'Leave Request';

                        $appr = $resolveApprovers($leave, $employee['full_name'] ?? 'Approver');

                        $pendingRequests[] = [
                            'id' => $id,
                            'employeeOfficeId' => $officeId,
                            'employeeName' => $empName,
                            'departmentName' => $deptName ?: '—',
                            'designationName' => $desigName ?: '—',
                            'requestType' => 'Leave Request',
                            'subType' => $catName,
                            'startDate' => $sDate,
                            'endDate' => $eDate,
                            'entryTime' => '—',
                            'exitTime' => '—',
                            'reason' => $leave['reason'] ?? '—',
                            'firstApproverName' => $appr['firstApproverName'],
                            'firstApprovalStatus' => $appr['firstApprovalStatus'],
                            'secondApproverName' => $appr['secondApproverName'],
                            'secondApprovalStatus' => $appr['secondApprovalStatus'],
                            'status' => 0
                        ];
                    }
                }
            }

            echo json_encode([
                'success' => true,
                'data' => $pendingRequests,
                'total' => count($pendingRequests),
                'tipsoi_id' => $tipsoiId
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}

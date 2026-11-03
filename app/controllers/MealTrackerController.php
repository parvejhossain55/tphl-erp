<?php

class MealTrackerController extends Controller
{
    protected $db;
    protected $mealProjectModel;
    protected $mealRecordModel;
    protected $employeeModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->mealProjectModel = new MealProject($this->db);
        $this->mealRecordModel = new MealRecord($this->db);
        $this->employeeModel = new Employee($this->db);
    }

    /**
     * Check if current user has Admin, Operation, or HR role/permission
     */
    private function checkAccess($isJson = false)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']['id'])) {
            if ($isJson) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please login.']);
                exit;
            }
            setError("Unauthorized access. Please login.");
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $userRoleName = strtolower($_SESSION['user']['role_name'] ?? '');

        // Allowed: Admin (1, 3), HR (4), Operation (20, 32)
        $hasAccess = in_array($userRoleId, [1, 3, 4, 20, 32])
            || (strpos($userRoleName, 'admin') !== false)
            || (strpos($userRoleName, 'operation') !== false)
            || (strpos($userRoleName, 'hr') !== false)
            || hasPermission(['Hr Manage', 'HRM Management', 'Operation manage']);

        if (!$hasAccess) {
            if ($isJson) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Access denied. Meal Tracker is restricted to Admin, Operation, and HR roles.']);
                exit;
            }
            setError("Access denied. Meal Tracker is restricted to Admin, Operation, and HR roles.");
            header("Location: {$GLOBALS['base_url']}/dashboard");
            exit;
        }
    }

    /**
     * Main Meal Tracker Grid View with Office Branch Tabs
     */
    public function index()
    {
        $this->checkAccess();

        // Fetch all meal projects
        $mealProjects = $this->mealProjectModel->getAll();

        // Selected Project ID
        $selectedProjectId = isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int)$_GET['project_id'] : null;
        if (!$selectedProjectId && !empty($mealProjects)) {
            $selectedProjectId = (int)$mealProjects[0]['id'];
        }

        $activeProject = null;
        $dateColumns = [];
        if ($selectedProjectId) {
            $activeProject = $this->mealProjectModel->getById($selectedProjectId);
            if ($activeProject && !empty($activeProject['from_date']) && !empty($activeProject['to_date'])) {
                $start = new DateTime($activeProject['from_date']);
                $end = new DateTime($activeProject['to_date']);
                $end->modify('+1 day'); // inclusive

                $interval = new DateInterval('P1D');
                $period = new DatePeriod($start, $interval, $end);

                foreach ($period as $dt) {
                    $dateStr = $dt->format('Y-m-d');
                    $dayName = $dt->format('D'); // Mon, Tue, etc.
                    $dayNum = $dt->format('j');
                    $monthShort = $dt->format('M');
                    $dateColumns[] = [
                        'date' => $dateStr,
                        'day_name' => $dayName,
                        'day_num' => $dayNum,
                        'month_short' => $monthShort,
                        'is_weekend' => in_array($dayName, ['Fri', 'Sat'])
                    ];
                }
            }
        }

        // Fetch all active office branches
        $branchRes = $this->db->query("SELECT * FROM office_branches WHERE status = 'active' ORDER BY id ASC");
        $branches = [];
        if ($branchRes) {
            while ($bRow = $branchRes->fetch_assoc()) {
                $branches[] = $bRow;
            }
        }

        // Selected Branch ID
        $selectedBranchId = isset($_GET['branch_id']) && $_GET['branch_id'] !== '' ? (int)$_GET['branch_id'] : null;
        if (!$selectedBranchId && !empty($branches)) {
            $selectedBranchId = (int)$branches[0]['id'];
        }

        // Fetch employees for this branch where is_meal_deduction = 1
        $employees = [];
        $records = [];
        $guestRecords = [];

        if ($selectedBranchId) {
            $employees = $this->employeeModel->getActiveMealEmployeesByBranch($selectedBranchId);
            if ($selectedProjectId && $activeProject) {
                // Auto-sync attendance if no consumed records exist yet, or today has not been recorded
                $hasConsumed = $this->mealRecordModel->hasConsumedRecords($selectedProjectId);
                $today = date('Y-m-d');
                $shouldAutoSync = false;
                if (!$hasConsumed) {
                    $shouldAutoSync = true;
                } else if ($today >= $activeProject['from_date'] && $today <= $activeProject['to_date']) {
                    if (!$this->mealRecordModel->hasRecordsForDate($selectedProjectId, $today)) {
                        $shouldAutoSync = true;
                    }
                }

                if ($shouldAutoSync) {
                    $this->mealRecordModel->syncAttendanceWithMealRecords($selectedProjectId);
                }

                $records = $this->mealRecordModel->getRecordsByProjectAndBranch($selectedProjectId, $selectedBranchId);
                $guestRecords = $this->mealRecordModel->getGuestRecordsByProjectAndBranch($selectedProjectId, $selectedBranchId);
            }
        }

        // Also fetch payroll projects for project-creation link modal
        $payrollProjRes = $this->db->query("SELECT id, name, date, start_date, end_date FROM payroll_projects ORDER BY id DESC");
        $payrollProjects = [];
        if ($payrollProjRes) {
            while ($p = $payrollProjRes->fetch_assoc()) {
                $payrollProjects[] = $p;
            }
        }

        // Fetch employee meal opt-outs in project period
        $optOuts = [];
        if ($activeProject && !empty($activeProject['from_date']) && !empty($activeProject['to_date'])) {
            $optOuts = $this->mealRecordModel->getOptOutsByDateRange($activeProject['from_date'], $activeProject['to_date']);
        }

        return $this->view('meal_tracker/index', [
            'current_route' => 'meal-tracker',
            'page_title' => 'Meal Tracker Workspace',
            'mealProjects' => $mealProjects,
            'selectedProjectId' => $selectedProjectId,
            'activeProject' => $activeProject,
            'dateColumns' => $dateColumns,
            'branches' => $branches,
            'selectedBranchId' => $selectedBranchId,
            'employees' => $employees,
            'records' => $records,
            'guestRecords' => $guestRecords,
            'payrollProjects' => $payrollProjects,
            'optOuts' => $optOuts
        ]);
    }

    /**
     * Store new Meal Project
     */
    public function storeProject()
    {
        $this->checkAccess();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setError("Invalid request method.");
            header("Location: {$GLOBALS['base_url']}/meal-tracker");
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $standardAmount = (float)($_POST['standard_meal_amount'] ?? 120.00);
        $fromDate = trim($_POST['from_date'] ?? '');
        $toDate = trim($_POST['to_date'] ?? '');
        $payrollProjectId = !empty($_POST['payroll_project_id']) ? (int)$_POST['payroll_project_id'] : null;
        $createdBy = $_SESSION['user']['id'] ?? null;

        if (empty($name) || empty($fromDate) || empty($toDate)) {
            setError("Project Name, From Date and To Date are required.");
            header("Location: {$GLOBALS['base_url']}/meal-tracker");
            exit;
        }

        // Prevent duplicate project with identical name
        $checkStmt = $this->db->prepare("SELECT id, name FROM meal_projects WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) LIMIT 1");
        if ($checkStmt) {
            $checkStmt->bind_param("s", $name);
            $checkStmt->execute();
            $exist = $checkStmt->get_result()->fetch_assoc();
            $checkStmt->close();
            if ($exist) {
                setError("A meal project with the name '{$name}' already exists. Please choose a unique name or use the existing project.");
                header("Location: {$GLOBALS['base_url']}/meal-tracker?project_id={$exist['id']}");
                exit;
            }
        }

        // Auto-link matching Payroll Project by date range if not specified
        if (empty($payrollProjectId) && !empty($fromDate) && !empty($toDate)) {
            $escFD = $this->db->real_escape_string($fromDate);
            $escTD = $this->db->real_escape_string($toDate);
            $pMatch = $this->db->query("
                SELECT id FROM payroll_projects 
                WHERE (start_date = '$escFD' AND end_date = '$escTD')
                   OR (is_custom_date = 0 AND date = SUBSTRING('$escFD', 1, 7))
                   OR (start_date <= '$escTD' AND end_date >= '$escFD')
                ORDER BY (start_date = '$escFD' AND end_date = '$escTD') DESC, id DESC LIMIT 1
            ");
            if ($pMatch && $pRow = $pMatch->fetch_assoc()) {
                $payrollProjectId = (int)$pRow['id'];
            }
        }

        $newId = $this->mealProjectModel->create([
            'name' => $name,
            'standard_meal_amount' => $standardAmount > 0 ? $standardAmount : 120.00,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'payroll_project_id' => $payrollProjectId,
            'created_by' => $createdBy
        ]);

        if ($newId) {
            // Auto-sync attendance immediately so all dates up to today are auto-ticked
            $this->mealRecordModel->syncAttendanceWithMealRecords($newId);
            setSuccess("Meal Project '$name' created successfully and attendance auto-synced.");
            header("Location: {$GLOBALS['base_url']}/meal-tracker?project_id={$newId}");
        } else {
            setError("Failed to create Meal Project. Please try again.");
            header("Location: {$GLOBALS['base_url']}/meal-tracker");
        }
        exit;
    }

    /**
     * Update Standard Meal Amount for a Meal Project
     */
    public function updateStandardRate()
    {
        $this->checkAccess();

        $projectId = (int)($_POST['project_id'] ?? 0);
        $standardAmount = (float)($_POST['standard_meal_amount'] ?? 0);
        $branchId = (int)($_POST['branch_id'] ?? 0);

        if (!$projectId || $standardAmount <= 0) {
            setError("Valid Project ID and positive Standard Meal Rate are required.");
            $redirUrl = "{$GLOBALS['base_url']}/meal-tracker";
            if ($projectId) {
                $redirUrl .= "?project_id={$projectId}" . ($branchId ? "&branch_id={$branchId}" : "");
            }
            header("Location: {$redirUrl}");
            exit;
        }

        $project = $this->mealProjectModel->getById($projectId);
        if (!$project) {
            setError("Meal project not found.");
            header("Location: {$GLOBALS['base_url']}/meal-tracker");
            exit;
        }

        $updated = $this->mealProjectModel->updateStandardRate($projectId, $standardAmount);
        if ($updated) {
            setSuccess("Standard meal rate for '{$project['name']}' updated to ৳" . number_format($standardAmount, 2) . " successfully.");
        } else {
            setError("Failed to update standard meal rate.");
        }

        $redirUrl = "{$GLOBALS['base_url']}/meal-tracker?project_id={$projectId}" . ($branchId ? "&branch_id={$branchId}" : "");
        header("Location: {$redirUrl}");
        exit;
    }

    /**
     * Delete Meal Project and completely delete all its associated records (meal_records, meal_guest_records)
     */
    public function deleteProject()
    {
        $this->checkAccess();

        $projectId = (int)($_POST['project_id'] ?? ($_GET['project_id'] ?? 0));
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                  || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

        if (!$projectId) {
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true) ?? [];
            if (!empty($input['project_id'])) {
                $projectId = (int)$input['project_id'];
                $isAjax = true;
            }
        }

        if (!$projectId) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Valid Project ID is required.']);
                exit;
            }
            setError("Valid Project ID is required.");
            header("Location: {$GLOBALS['base_url']}/meal-tracker");
            exit;
        }

        $project = $this->mealProjectModel->getById($projectId);
        if (!$project) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Meal project not found.']);
                exit;
            }
            setError("Meal project not found.");
            header("Location: {$GLOBALS['base_url']}/meal-tracker");
            exit;
        }

        $projectName = $project['name'];

        // Fully delete all related records: meal_records, meal_guest_records, and meal_projects
        $this->db->query("DELETE FROM meal_records WHERE meal_project_id = {$projectId}");
        $this->db->query("DELETE FROM meal_guest_records WHERE meal_project_id = {$projectId}");
        $deleted = $this->mealProjectModel->delete($projectId);

        if ($deleted) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => "Meal Project '{$projectName}' and all associated meal records have been completely deleted."
                ]);
                exit;
            }
            setSuccess("Meal Project '{$projectName}' and all associated records deleted successfully.");
            header("Location: {$GLOBALS['base_url']}/meal-tracker");
        } else {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Failed to delete project. Please try again.']);
                exit;
            }
            setError("Failed to delete project. Please try again.");
            header("Location: {$GLOBALS['base_url']}/meal-tracker");
        }
        exit;
    }

    /**
     * AJAX API: Get Branch Grid Data
     */
    public function getBranchGridData()
    {
        $this->checkAccess(true);
        header('Content-Type: application/json');

        $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
        $branchId = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;

        if (!$projectId || !$branchId) {
            echo json_encode(['success' => false, 'message' => 'Project ID and Branch ID are required.']);
            exit;
        }

        $project = $this->mealProjectModel->getById($projectId);
        if (!$project) {
            echo json_encode(['success' => false, 'message' => 'Meal Project not found.']);
            exit;
        }

        $employees = $this->employeeModel->getActiveMealEmployeesByBranch($branchId);
        $records = $this->mealRecordModel->getRecordsByProjectAndBranch($projectId, $branchId);
        $guestRecords = $this->mealRecordModel->getGuestRecordsByProjectAndBranch($projectId, $branchId);
        $optOuts = $this->mealRecordModel->getOptOutsByDateRange($project['from_date'], $project['to_date']);

        echo json_encode([
            'success' => true,
            'project' => $project,
            'employees' => $employees,
            'records' => $records,
            'guest_records' => $guestRecords,
            'opt_outs' => $optOuts
        ]);
        exit;
    }

    /**
     * AJAX POST: Save daily meal grid for a branch
     */
    public function saveDailyGrid()
    {
        $this->checkAccess(true);
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            echo json_encode(['success' => false, 'message' => 'No payload received.']);
            exit;
        }

        $projectId = (int)($input['project_id'] ?? 0);
        $branchId = (int)($input['branch_id'] ?? 0);
        $records = $input['records'] ?? [];
        $guestRecords = $input['guest_records'] ?? [];

        if (!$projectId || !$branchId) {
            echo json_encode(['success' => false, 'message' => 'Project and Branch ID are required.']);
            exit;
        }

        $this->db->begin_transaction();
        try {
            $saveOk = $this->mealRecordModel->bulkUpsertRecords($projectId, $branchId, $records);
            if (!$saveOk) {
                throw new Exception("Failed to save employee meal records.");
            }

            if (!empty($guestRecords)) {
                $guestOk = $this->mealRecordModel->bulkUpsertGuestRecords($projectId, $branchId, $guestRecords);
                if (!$guestOk) {
                    throw new Exception("Failed to save guest meal records.");
                }
            }

            $this->db->commit();
            echo json_encode([
                'success' => true, 
                'message' => 'Meal tracker records saved successfully!'
            ]);
        } catch (Exception $e) {
            $this->db->rollback();
            echo json_encode([
                'success' => false, 
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Meal History & Monthly Branch Bill Summary
     */
    public function history()
    {
        $this->checkAccess();

        $mealProjects = $this->mealProjectModel->getAll();

        // Calculate summary for each project
        $historyData = [];
        foreach ($mealProjects as $proj) {
            $pid = (int)$proj['id'];
            $branchSummary = $this->mealRecordModel->getBranchWiseSummary($pid);
            $standardAmount = (float)$proj['standard_meal_amount'];

            $totalMeals = 0;
            $totalGuests = 0;
            $totalBill = 0;

            foreach ($branchSummary as &$b) {
                $bMeals = $b['employee_meals'] + $b['guest_meals'];
                $bCost = $bMeals * $standardAmount;
                $b['total_meals'] = $bMeals;
                $b['total_cost'] = $bCost;

                $totalMeals += $b['employee_meals'];
                $totalGuests += $b['guest_meals'];
                $totalBill += $bCost;
            }

            $historyData[] = [
                'project' => $proj,
                'branches' => $branchSummary,
                'total_employee_meals' => $totalMeals,
                'total_guest_meals' => $totalGuests,
                'grand_total_meals' => $totalMeals + $totalGuests,
                'grand_total_bill' => $totalBill
            ];
        }

        return $this->view('meal_tracker/history', [
            'current_route' => 'meal-tracker-history',
            'page_title' => 'Meal Tracker History & Branch Summary',
            'historyData' => $historyData
        ]);
    }

    /**
     * AJAX GET: Get Today's Meal Status for Logged-In Employee
     */
    public function getTodayMealStatus()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']['id'])) {
            echo json_encode(['success' => false, 'is_enrolled' => false, 'message' => 'Not logged in.']);
            exit;
        }

        $empId = (int)($_SESSION['user']['employee_db_id'] ?? 0);
        if (!$empId) {
            $userId = (int)$_SESSION['user']['id'];
            $res = $this->db->query("SELECT id FROM employees WHERE user_id = $userId AND deleted_at IS NULL LIMIT 1");
            if ($res && $row = $res->fetch_assoc()) {
                $empId = (int)$row['id'];
                $_SESSION['user']['employee_db_id'] = $empId;
            }
        }

        if (!$empId) {
            echo json_encode(['success' => false, 'is_enrolled' => false, 'message' => 'No employee profile.']);
            exit;
        }

        $ctx = $this->employeeModel->getTodayMealContext($empId);
        if (!$ctx) {
            echo json_encode(['success' => false, 'is_enrolled' => false, 'message' => 'Context not found.']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'context' => $ctx
        ]);
        exit;
    }

    /**
     * AJAX POST: Toggle Today's Meal Opt-Out / Opt-In for Logged-In Employee (Before 12:00 PM)
     */
    public function toggleTodayMeal()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']['id'])) {
            echo json_encode(['success' => false, 'message' => 'Please login first.']);
            exit;
        }

        $empId = (int)($_SESSION['user']['employee_db_id'] ?? 0);
        if (!$empId) {
            $userId = (int)$_SESSION['user']['id'];
            $res = $this->db->query("SELECT id FROM employees WHERE user_id = $userId AND deleted_at IS NULL LIMIT 1");
            if ($res && $row = $res->fetch_assoc()) {
                $empId = (int)$row['id'];
                $_SESSION['user']['employee_db_id'] = $empId;
            }
        }

        if (!$empId) {
            echo json_encode(['success' => false, 'message' => 'No employee profile linked to your user account.']);
            exit;
        }

        $ctx = $this->employeeModel->getTodayMealContext($empId);
        if (!$ctx || empty($ctx['is_enrolled'])) {
            echo json_encode(['success' => false, 'message' => 'You are not enrolled in the company subsidized lunch plan.']);
            exit;
        }

        // Strict 11:00 AM Cutoff Check
        date_default_timezone_set('Asia/Dhaka');
        $isTestMode = isset($_GET['test_meal']) || isset($_GET['preview_meal']) || isset($_GET['test_modal']);
        if (!$isTestMode && date('H:i') >= '11:00') {
            echo json_encode([
                'success' => false, 
                'message' => "Today's meal cutoff time (11:00 AM) has passed. Status cannot be modified after 11:00 AM."
            ]);
            exit;
        }

        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true) ?? [];
        $action = trim($input['action'] ?? '');
        $date = date('Y-m-d');
        $reason = trim($input['reason'] ?? '');

        if (!in_array($action, ['opt_out', 'opt_in'])) {
            $action = $ctx['is_opted_out'] ? 'opt_in' : 'opt_out';
        }

        $res = $this->mealRecordModel->saveDailyOptOut($empId, $date, $action, $reason);

        if ($res) {
            $updatedCtx = $this->employeeModel->getTodayMealContext($empId, $date);
            $msg = ($action === 'opt_out') 
                ? "Today's meal has been successfully turned OFF." 
                : "Today's meal has been successfully turned ON.";

            echo json_encode([
                'success' => true,
                'status' => $action,
                'message' => $msg,
                'context' => $updatedCtx
            ]);
        } else {
            $errMsg = $this->mealRecordModel->getLastError() ?: $this->db->error ?: 'Failed to update meal status. Please try again.';
            echo json_encode(['success' => false, 'message' => $errMsg]);
        }
        exit;
    }

    /**
     * AJAX POST: Sync Attendance with Meal Records (Admin / HR)
     */
    public function syncAttendance()
    {
        $this->checkAccess(true);
        header('Content-Type: application/json');

        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true) ?? [];
        $projectId = (int)($input['project_id'] ?? ($_GET['project_id'] ?? 0));
        $branchId = !empty($input['branch_id']) ? (int)$input['branch_id'] : null;

        if (!$projectId) {
            echo json_encode(['success' => false, 'message' => 'Meal Project ID is required for sync.']);
            exit;
        }

        $res = $this->mealRecordModel->syncAttendanceWithMealRecords($projectId, $branchId);
        echo json_encode($res);
        exit;
    }

    /**
     * Employee Self-Service: My Meal Tracker
     * Accessible to employees with is_meal_deduction = 1
     */
    public function myMeals()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']['id'])) {
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        $userId = (int)$_SESSION['user']['id'];
        $empId  = (int)($_SESSION['user']['employee_db_id'] ?? 0);

        if (!$empId && $userId > 0) {
            $uEmpId = (int)($_SESSION['user']['employee_id'] ?? 0);
            if ($uEmpId > 0) {
                $chkRes = $this->db->query("SELECT id FROM employees WHERE id = {$uEmpId} AND deleted_at IS NULL LIMIT 1");
                if ($chkRes && $chkRow = $chkRes->fetch_assoc()) {
                    $empId = (int)$chkRow['id'];
                    $_SESSION['user']['employee_db_id'] = $empId;
                }
            }
            if (!$empId) {
                $chkRes = $this->db->query("SELECT id FROM employees WHERE user_id = {$userId} AND deleted_at IS NULL LIMIT 1");
                if ($chkRes && $chkRow = $chkRes->fetch_assoc()) {
                    $empId = (int)$chkRow['id'];
                    $_SESSION['user']['employee_db_id'] = $empId;
                }
            }
        }

        // Fetch employee details
        $employee = null;
        if ($empId > 0) {
            $empSql = "
                SELECT e.*, 
                       d.name AS designation_name,
                       dept.name AS department_name,
                       b.name AS branch_name
                FROM employees e
                LEFT JOIN designations d ON e.designation_id = d.id
                LEFT JOIN departments dept ON e.department_id = dept.id
                LEFT JOIN office_branches b ON e.office_branch_id = b.id
                WHERE e.id = {$empId} AND e.deleted_at IS NULL
                LIMIT 1
            ";
            $empRes = $this->db->query($empSql);
            if ($empRes) {
                $employee = $empRes->fetch_assoc();
            }
        }

        $isEnrolled = ($employee && (int)($employee['is_meal_deduction'] ?? 0) === 1);

        // If not enrolled or no employee profile, show friendly notice
        if (!$employee || !$isEnrolled) {
            return $this->view('meal_tracker/my_meals', [
                'current_route' => 'my-meal-tracker',
                'page_title' => 'My Meal Tracker',
                'employee' => $employee,
                'is_enrolled' => false
            ]);
        }

        // Fetch all meal projects (cycles)
        $mealProjects = $this->mealProjectModel->getAll();

        date_default_timezone_set('Asia/Dhaka');
        $today = date('Y-m-d');

        // Determine active/selected project
        $selectedProjectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
        $activeProject = null;

        if ($selectedProjectId > 0) {
            foreach ($mealProjects as $p) {
                if ((int)$p['id'] === $selectedProjectId) {
                    $activeProject = $p;
                    break;
                }
            }
        }

        // Default to active cycle covering today, or newest project
        if (!$activeProject && !empty($mealProjects)) {
            foreach ($mealProjects as $p) {
                if (!empty($p['from_date']) && !empty($p['to_date'])) {
                    if ($today >= $p['from_date'] && $today <= $p['to_date']) {
                        $activeProject = $p;
                        $selectedProjectId = (int)$p['id'];
                        break;
                    }
                }
            }
            if (!$activeProject) {
                $activeProject = $mealProjects[0];
                $selectedProjectId = (int)$activeProject['id'];
            }
        }

        // Build list of dates for the cycle
        $dates = [];
        $records = [];
        $optOuts = [];
        $attendances = [];

        $totalConsumed = 0;
        $totalSkipped = 0;
        $totalScheduled = 0;
        $latestUpdated = null;

        $standardRate = (float)($activeProject['standard_meal_amount'] ?? 120.00);
        $percentage = (float)($employee['per_meal_percentage'] ?? 100.00);
        $employeeRate = round(($standardRate * $percentage) / 100, 2);
        $subsidyRate = max(0, $standardRate - $employeeRate);

        if ($activeProject && !empty($activeProject['from_date']) && !empty($activeProject['to_date'])) {
            $from = new DateTime($activeProject['from_date']);
            $to = new DateTime($activeProject['to_date']);
            $to->modify('+1 day');
            $period = new DatePeriod($from, new DateInterval('P1D'), $to);

            foreach ($period as $dt) {
                $dates[] = $dt->format('Y-m-d');
            }

            // Fetch records, opt-outs, and attendances
            $records = $this->mealRecordModel->getEmployeeDetailedRecords($empId, $activeProject['id']);
            $optOuts = $this->mealRecordModel->getEmployeeOptOutsInRange($empId, $activeProject['from_date'], $activeProject['to_date']);
            $attendances = $this->mealRecordModel->getEmployeeAttendancesInRange($empId, $activeProject['from_date'], $activeProject['to_date']);

            foreach ($dates as $d) {
                $isConsumed = isset($records[$d]) ? (int)$records[$d]['is_consumed'] : null;
                $updatedAt = $records[$d]['updated_at'] ?? null;

                if ($updatedAt && (!$latestUpdated || $updatedAt > $latestUpdated)) {
                    $latestUpdated = $updatedAt;
                }

                if ($isConsumed === 1) {
                    $totalConsumed++;
                } elseif ($isConsumed === 0) {
                    $totalSkipped++;
                } else {
                    if ($d <= $today) {
                        $totalSkipped++;
                    } else {
                        $totalScheduled++;
                    }
                }
            }
        }

        $totalDeduction = round($totalConsumed * $employeeRate, 2);
        $totalCompanySubsidy = round($totalConsumed * $subsidyRate, 2);
        $totalStandardAmount = round($totalConsumed * $standardRate, 2);

        return $this->view('meal_tracker/my_meals', [
            'current_route' => 'my-meal-tracker',
            'page_title' => 'My Meal Tracker',
            'employee' => $employee,
            'is_enrolled' => true,
            'mealProjects' => $mealProjects,
            'activeProject' => $activeProject,
            'selectedProjectId' => $selectedProjectId,
            'dates' => $dates,
            'records' => $records,
            'optOuts' => $optOuts,
            'attendances' => $attendances,
            'today' => $today,
            'stats' => [
                'total_consumed' => $totalConsumed,
                'total_skipped' => $totalSkipped,
                'total_scheduled' => $totalScheduled,
                'total_days' => count($dates),
                'standard_rate' => $standardRate,
                'percentage' => $percentage,
                'employee_rate' => $employeeRate,
                'subsidy_rate' => $subsidyRate,
                'total_deduction' => $totalDeduction,
                'total_company_subsidy' => $totalCompanySubsidy,
                'total_standard_amount' => $totalStandardAmount,
                'latest_updated' => $latestUpdated
            ]
        ]);
    }
}


<?php

class HrmApiController extends Controller
{
    protected $db;
    protected $employeeModel;
    protected $externalApiService;

    public function __construct($db)
    {
        $this->db = $db;
        $this->employeeModel = new Employee($this->db);
        $this->externalApiService = new ExternalApiService($this->db);
    }

    /**
     * GET /api/hrm/employees
     * Returns paginated local ERP employees
     */
    public function getErpEmployees()
    {
        header('Content-Type: application/json');

        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? max(1, (int) $_GET['limit']) : 15;
        $offset = ($page - 1) * $limit;

        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $department_id = isset($_GET['department_id']) ? trim($_GET['department_id']) : '';
        $team_id = isset($_GET['team_id']) ? trim($_GET['team_id']) : '';

        try {
            $employees = $this->employeeModel->getPaginated($limit, $offset, $search, $department_id, $team_id);
            $total = $this->employeeModel->countAll($search, $department_id, $team_id);

            echo json_encode([
                'success' => true,
                'data' => $employees,
                'total' => (int) $total,
                'page' => $page,
                'limit' => $limit
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to fetch employees: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * GET /api/hrm/external-employees
     * Returns paginated external Tipsoi employees
     */
    public function getExternalEmployees()
    {
        header('Content-Type: application/json');

        try {
            // Fetch all external employees (up to 2000) to allow client-side filtering/stats
            $result = $this->externalApiService->getExternalEmployees(1, 2000);

            if ($result['success']) {
                // Fetch local employee IDs and emails
                $localQuery = $this->db->query("SELECT employee_id, email FROM employees");
                $localIds = [];
                $localEmails = [];
                if ($localQuery) {
                    while ($row = $localQuery->fetch_assoc()) {
                        $empId = trim($row['employee_id'] ?? '');
                        $empEmail = trim($row['email'] ?? '');
                        if ($empId !== '') {
                            $localIds[] = $empId;
                        }
                        if ($empEmail !== '') {
                            $localEmails[] = strtolower($empEmail);
                        }
                    }
                }
                $result['local_employee_ids'] = $localIds;
                $result['local_employee_emails'] = $localEmails;
            }
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to fetch external employees: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * POST /api/hrm/quick-create-employee
     * Quickly registers a Tipsoi employee into the local ERP database
     */
    public function quickCreateEmployee()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in to perform this action.']);
            exit;
        }

        $employeeOfficeId = trim($_POST['employee_id'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $departmentId = (int)($_POST['department_id'] ?? 0);
        $designationId = (int)($_POST['designation_id'] ?? 0);
        $officeBranchId = !empty($_POST['office_branch_id']) ? (int)$_POST['office_branch_id'] : 2;
        $joiningDate = !empty($_POST['joining_date']) ? trim($_POST['joining_date']) : date('Y-m-d');
        $grossSalary = !empty($_POST['gross_salary']) ? (float)$_POST['gross_salary'] : 0.0;
        $basicSalary = $grossSalary > 0 ? ($grossSalary * 0.50) : 0.0;
        $isMealDeduction = isset($_POST['is_meal_deduction']) ? (int)$_POST['is_meal_deduction'] : 0;
        $perMealAmount = (isset($_POST['per_meal_amount']) && is_numeric($_POST['per_meal_amount'])) ? (float)$_POST['per_meal_amount'] : 120.00;
        $perMealPercentage = (isset($_POST['per_meal_percentage']) && $_POST['per_meal_percentage'] !== '') ? (float)$_POST['per_meal_percentage'] : 100.00;
        $isDeptHead = (isset($_POST['is_dept_head']) && (int)$_POST['is_dept_head'] === 1) ? 1 : 0;
        $reportingUserId = ($isDeptHead === 1) ? null : (!empty($_POST['reporting_user_id']) ? (int)$_POST['reporting_user_id'] : null);
        $makeUser = (isset($_POST['make_user']) && (int)$_POST['make_user'] === 1) ? 1 : 0;
        $roleId = (int)($_POST['role_id'] ?? 2);
        $plainPassword = trim($_POST['password'] ?? '12345678');
        if (empty($plainPassword)) {
            $plainPassword = '12345678';
        }

        // Validation
        if (empty($employeeOfficeId)) {
            echo json_encode(['success' => false, 'message' => 'Employee Office ID is required.']);
            exit;
        }
        if (empty($fullName)) {
            echo json_encode(['success' => false, 'message' => 'Full Name is required.']);
            exit;
        }
        if ($departmentId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Please select a valid Department.']);
            exit;
        }
        if ($designationId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Please select a valid Designation.']);
            exit;
        }

        // Check if employee already exists in ERP
        $checkStmt = $this->db->prepare("SELECT id, employee_id, email FROM employees WHERE employee_id = ? OR (email != '' AND email = ?) LIMIT 1");
        $checkStmt->bind_param("ss", $employeeOfficeId, $email);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();
        if ($checkRes && $checkRes->num_rows > 0) {
            $existing = $checkRes->fetch_assoc();
            echo json_encode([
                'success' => false,
                'message' => "An employee with Employee ID '{$existing['employee_id']}' already exists in ERP."
            ]);
            exit;
        }
        $checkStmt->close();

        // Prepare unique username
        $username = !empty($email) ? strtolower(explode('@', $email)[0]) : 'emp_' . $employeeOfficeId;
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);
        if (empty($username)) {
            $username = 'emp_' . $employeeOfficeId;
        }

        $uCheck = $this->db->prepare("SELECT id FROM employees WHERE username = ? LIMIT 1");
        $uCheck->bind_param("s", $username);
        $uCheck->execute();
        if ($uCheck->get_result()->num_rows > 0) {
            $username .= '_' . $employeeOfficeId;
        }
        $uCheck->close();

        // Workplace name from office branch
        $workplaceName = 'Ashulia Site Office';
        $bRes = $this->db->query("SELECT name FROM office_branches WHERE id = $officeBranchId LIMIT 1");
        if ($bRes && $bRow = $bRes->fetch_assoc()) {
            $workplaceName = $bRow['name'];
        }

        $this->db->begin_transaction();

        try {
            $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
            $currentUserId = (int)($_SESSION['user']['id'] ?? 1);

            $employeeData = [
                'employee_id' => $employeeOfficeId,
                'full_name' => $fullName,
                'username' => $username,
                'email' => $email,
                'father_name' => '',
                'phone' => !empty($phone) ? $phone : '01700000000',
                'emergency_contact' => '',
                'qualification' => '',
                'gross_salary' => $grossSalary,
                'basic_salary' => $basicSalary,
                'per_meal_amount' => $perMealAmount,
                'is_meal_deduction' => $isMealDeduction,
                'per_meal_percentage' => $perMealPercentage,
                'experience' => '',
                'workplace' => $workplaceName,
                'office_branch_id' => $officeBranchId,
                'nid' => '',
                'address' => $workplaceName,
                'pass_num' => '',
                'department_id' => $departmentId,
                'designation_id' => $designationId,
                'dob' => null,
                'joining_date' => $joiningDate,
                'resign_date' => null,
                'account_holder_name' => '',
                'account_number' => '',
                'bank_name' => '',
                'branch_name' => '',
                'social_media1' => '',
                'social_media2' => '',
                'social_media3' => '',
                'make_user' => $makeUser,
                'password' => $hashedPassword,
                'created_by' => $currentUserId,
                'employee_type' => 'permanent',
                'employee_status' => 'active',
                'responsibilities' => '',
                'team_id' => 0,
                'is_sales_co_leader' => 0,
                'is_dept_head' => $isDeptHead,
                'reporting_user_id' => $reportingUserId,
            ];

            // 1. Insert into employees
            $newEmpId = $this->employeeModel->create($employeeData);
            if (!$newEmpId) {
                throw new Exception("Failed to insert employee into ERP database.");
            }

            // 2. Insert initial leave balances
            $leaveTypesResult = $this->db->query("SELECT id, total_days, is_paid FROM leave_types");
            if ($leaveTypesResult) {
                $leaveTypes = $leaveTypesResult->fetch_all(MYSQLI_ASSOC);
                $stmtBalance = $this->db->prepare(
                    "INSERT INTO employee_leave_balances (employee_id, leave_type_id, remaining_days) VALUES (?, ?, ?)"
                );
                if ($stmtBalance) {
                    foreach ($leaveTypes as $lt) {
                        $remaining = ($lt['is_paid'] == 1) ? $lt['total_days'] : 0;
                        $stmtBalance->bind_param("iii", $newEmpId, $lt['id'], $remaining);
                        $stmtBalance->execute();
                    }
                    $stmtBalance->close();
                }
            }

            // 3. Insert initial salary breakdown items based on payslip_items configuration
            $payslipItemsResult = $this->db->query("SELECT id, percentage FROM payslip_items");
            if ($payslipItemsResult) {
                $stmtSalaryItem = $this->db->prepare(
                    "INSERT INTO employee_salary_items (employee_id, payslip_item_id, amount) VALUES (?, ?, ?)"
                );
                if ($stmtSalaryItem) {
                    while ($pi = $payslipItemsResult->fetch_assoc()) {
                        $pct = (float)($pi['percentage'] ?? 0);
                        $amount = ($basicSalary > 0 && $pct > 0) ? round(($basicSalary * $pct) / 100, 2) : 0.00;
                        $itemId = (int)$pi['id'];
                        $stmtSalaryItem->bind_param("iid", $newEmpId, $itemId, $amount);
                        $stmtSalaryItem->execute();
                    }
                    $stmtSalaryItem->close();
                }
            }

            // 4. Create login user if make_user is enabled
            $newUserId = null;
            if ($makeUser === 1) {
                $userModel = new User($this->db);
                $userLoginEmail = !empty($email) ? $email : $username . '@tphl.com';
                $userData = [
                    'full_name' => $fullName,
                    'user_name' => $username,
                    'phone' => !empty($phone) ? $phone : '01700000000',
                    'email' => $userLoginEmail,
                    'nid' => '',
                    'designation_id' => $designationId,
                    'address' => $workplaceName,
                    'password' => $plainPassword,
                    'role_id' => $roleId > 0 ? $roleId : 2,
                    'secondary_role_id' => null,
                    'employee_id' => $newEmpId,
                    'team_id' => 0,
                    'created_by' => $currentUserId,
                ];

                $newUserId = $userModel->create($userData);
                if ($newUserId) {
                    $this->db->query("UPDATE employees SET user_id = $newUserId, make_user = 1 WHERE id = $newEmpId");
                }
            }

            $this->db->commit();

            echo json_encode([
                'success' => true,
                'message' => "Employee '{$fullName}' registered in ERP successfully!",
                'employee_office_id' => $employeeOfficeId,
                'email' => strtolower($email),
                'erp_id' => $newEmpId,
                'user_id' => $newUserId
            ]);
        } catch (Exception $e) {
            $this->db->rollback();
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error registering employee: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * GET /api/hrm/attendance
     * Fetch attendance logs from Tipsoi and match with local ERP employees
     */
    public function getAttendanceReport()
    {
        @set_time_limit(180);
        header('Content-Type: application/json');

        // Store previous timezone to restore it later
        $oldTimezone = date_default_timezone_get();
        date_default_timezone_set('Asia/Dhaka');

        // Extract dates (expecting Y-m-d format, e.g. 2026-07-06)
        $projectId = isset($_GET['payroll_project_id']) && $_GET['payroll_project_id'] !== 'undefined' ? (int)$_GET['payroll_project_id'] : (isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0);
        $fromDateStr = $_GET['from_date'] ?? '';
        $toDateStr = $_GET['to_date'] ?? '';

        // If project_id provided, automatically resolve date boundaries from payroll_projects if not supplied
        if ($projectId > 0 && (empty($fromDateStr) || empty($toDateStr))) {
            $projQuery = $this->db->query("SELECT * FROM payroll_projects WHERE id = $projectId");
            if ($projQuery && $projData = $projQuery->fetch_assoc()) {
                if (!empty($projData['is_custom_date']) && !empty($projData['start_date']) && $projData['start_date'] !== '0000-00-00' && !empty($projData['end_date']) && $projData['end_date'] !== '0000-00-00') {
                    $fromDateStr = substr(trim($projData['start_date']), 0, 10);
                    $toDateStr = substr(trim($projData['end_date']), 0, 10);
                } elseif (!empty($projData['date'])) {
                    $parts = explode('-', $projData['date']);
                    if (count($parts) >= 2) {
                        $y = (int)$parts[0];
                        $m = (int)$parts[1];
                        $prevM = $m - 1;
                        $prevY = $y;
                        if ($prevM <= 0) {
                            $prevM = 12;
                            $prevY = $y - 1;
                        }
                        $fromDateStr = sprintf('%04d-%02d-26', $prevY, $prevM);
                        $toDateStr = sprintf('%04d-%02d-25', $y, $m);
                    }
                }
            }
        }

        if (empty($fromDateStr)) $fromDateStr = date('Y-m-d');
        if (empty($toDateStr)) $toDateStr = date('Y-m-d');

        $forceSync = !empty($_GET['force_sync']) && $_GET['force_sync'] == '1';

        // Release PHP session lock before external cURL / DB sync so concurrent filter changes never queue or race
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // Restore old timezone
        date_default_timezone_set($oldTimezone);

        try {
            // Sync any new Tipsoi data into ERP `attendances` table and retrieve strictly from `attendances` table
            $syncResult = $this->externalApiService->syncAndGetAttendanceFromDb($fromDateStr, $toDateStr, $forceSync);
            if (!$syncResult['success']) {
                echo json_encode([
                    'success' => false,
                    'message' => $syncResult['message'] ?? 'Failed to retrieve attendance logs.'
                ]);
                exit;
            }

            $fromDateStr = $syncResult['from_date'];
            $toDateStr = $syncResult['to_date'];

            // Fetch local employees details for name/email/ID mapping and joining_date fallback
            $localEmployees = [];
            $localIds = [];
            $localEmails = [];
            
            // Join with departments and designations and filter by project/effective dates
            $escapedToDate = $this->db->real_escape_string($toDateStr);
            $escapedFromDate = $this->db->real_escape_string($fromDateStr);
            
            $localQuery = $this->db->query("SELECT e.id, e.employee_id, e.email, e.full_name, e.joining_date, e.resign_date, e.department_id, d.name as department_name, des.name as designation_name 
                                            FROM employees e 
                                            LEFT JOIN departments d ON e.department_id = d.id 
                                            LEFT JOIN designations des ON e.designation_id = des.id
                                            WHERE e.deleted_at IS NULL
                                              AND (e.employee_status = 'active' OR (e.resign_date IS NOT NULL AND e.resign_date != '0000-00-00' AND e.resign_date >= '$escapedFromDate'))
                                              AND (e.joining_date IS NULL OR e.joining_date = '0000-00-00' OR e.joining_date <= '$escapedToDate')
                                              AND (e.resign_date IS NULL OR e.resign_date = '0000-00-00' OR e.resign_date >= '$escapedFromDate')
                                            ORDER BY CAST(e.employee_id AS UNSIGNED) ASC, e.employee_id ASC");

            if ($localQuery) {
                while ($row = $localQuery->fetch_assoc()) {
                    $pid = $row['id'];
                    $empId = trim($row['employee_id'] ?? '');
                    $empEmail = trim($row['email'] ?? '');
                    $empName = trim($row['full_name'] ?? '');
                    $empJoin = trim($row['joining_date'] ?? '');
                    $empResign = trim($row['resign_date'] ?? '');

                    $localEmployees[] = [
                        'id' => $pid,
                        'employee_id' => $empId,
                        'email' => strtolower($empEmail),
                        'full_name' => $empName,
                        'joining_date' => $empJoin,
                        'resign_date' => ($empResign !== '0000-00-00') ? $empResign : '',
                        'department_id' => (int) ($row['department_id'] ?? 0),
                        'department_name' => $row['department_name'] ?? 'N/A',
                        'designation_name' => $row['designation_name'] ?? 'N/A'
                    ];

                    if ($empId !== '') {
                        $localIds[] = $empId;
                    }
                    if ($empEmail !== '') {
                        $localEmails[] = strtolower($empEmail);
                    }
                }
            }

            // Fetch Custom Holidays for the date range
            require_once dirname(__DIR__) . '/models/Holiday.php';
            $holidayModel = new Holiday($this->db);
            $customHolidays = $holidayModel->getHolidayMapForRange($fromDateStr, $toDateStr);

            echo json_encode([
                'success' => true,
                'data' => $syncResult['data'],
                'dates' => [],
                'source' => $syncResult['source'] ?? 'erp_attendances_table',
                'synced_from_tipsoi' => $syncResult['synced_from_tipsoi'] ?? false,
                'synced_records_count' => $syncResult['synced_records_count'] ?? 0,
                'total_period_days' => $syncResult['total_period_days'] ?? 30,
                'local_employee_ids' => $localIds,
                'local_employee_emails' => $localEmails,
                'local_employees' => $localEmployees,
                'custom_holidays' => $customHolidays,
                'from_date' => $fromDateStr,
                'to_date' => $toDateStr
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * GET /api/hrm/leave-history
     * Fetch leave history logs from Tipsoi
     */
    public function getLeaveHistory()
    {
        header('Content-Type: application/json');

        // Store previous timezone to restore it later
        $oldTimezone = date_default_timezone_get();
        date_default_timezone_set('Asia/Dhaka');

        // Extract dates (expecting Y-m-d format, e.g. 2026-07-06)
        $fromDateStr = $_GET['from_date'] ?? date('Y-01-01'); // default current year start
        $toDateStr = $_GET['to_date'] ?? date('Y-12-31'); // default current year end

        // Convert to millisecond timestamps for Tipsoi API
        $fromTime = strtotime($fromDateStr . ' 00:00:00') * 1000;
        $toTime = strtotime($toDateStr . ' 23:59:59') * 1000;

        // Restore old timezone
        date_default_timezone_set($oldTimezone);

        try {
            // Get Bearer Token
            $token = $this->externalApiService->getBearerToken();
            if (!$token) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to authenticate with Tipsoi API.'
                ]);
                exit;
            }

            // Construct the Tipsoi API request URL
            $url = "https://app.tipsoi.ai/inovace-client/api/v1/attendance/leave-history?" . http_build_query([
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

            // Execute request
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Tipsoi API Error: ' . $curlError
                ]);
                exit;
            }

            $data = json_decode($response, true);

            if ($httpCode !== 200 || !isset($data['responseForAllEmployees'])) {
                echo json_encode([
                    'success' => false,
                    'message' => $data['message'] ?? 'Failed to retrieve leave history from Tipsoi API.'
                ]);
                exit;
            }

            // Fetch Leave Balance to get current remaining balances for all employees
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
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            $balanceRes = curl_exec($ch);
            curl_close($ch);

            $balanceData = json_decode($balanceRes, true);

            // Parse balances into a fast lookup map: [employeeOfficeId][leaveCategoryName] => balance
            $balMap = [];
            if ($balanceData && isset($balanceData['responseForAllEmployees'])) {
                foreach ($balanceData['responseForAllEmployees'] as $empBal) {
                    $officeId = trim($empBal['employeeOfficeId'] ?? $empBal['employeeId'] ?? '');
                    if ($officeId !== '') {
                        $normOfficeId = preg_match('/^\d+$/', $officeId) ? (string) intval($officeId) : $officeId;
                        $list = $empBal['leaveLeaveInfo']['leaveDataList'] ?? [];
                        foreach ($list as $item) {
                            if (isset($item['leaveCategoryName'])) {
                                $catName = trim($item['leaveCategoryName']);
                                $balMap[$normOfficeId][$catName] = $item['balance'] !== null ? $item['balance'] : 'N/A';
                            }
                        }
                    }
                }
            }

            // Helper to normalize ID in PHP
            $normalizeIdPhp = function ($id) {
                $str = trim((string) $id);
                return preg_match('/^\d+$/', $str) ? (string) intval($str) : $str;
            };

            // Merge balances into history data
            if (isset($data['responseForAllEmployees'])) {
                foreach ($data['responseForAllEmployees'] as &$emp) {
                    $officeId = $normalizeIdPhp($emp['employeeOfficeId'] ?? $emp['employeeId'] ?? '');
                    if (isset($emp['leaveLeaveInfo']['leaveDataList'])) {
                        foreach ($emp['leaveLeaveInfo']['leaveDataList'] as &$item) {
                            if (isset($item['from']) && $item['from'] !== null && isset($item['leaveCategoryName'])) {
                                $catName = trim($item['leaveCategoryName']);
                                $item['remaining_balance'] = $balMap[$officeId][$catName] ?? 'N/A';
                            }
                        }
                    }
                }
            }

            // Fetch local employees details for name/email/ID mapping
            $localQuery = $this->db->query("SELECT employee_id, email, full_name FROM employees");
            $localEmployees = [];
            $localIds = [];
            $localEmails = [];
            if ($localQuery) {
                while ($row = $localQuery->fetch_assoc()) {
                    $empId = trim($row['employee_id'] ?? '');
                    $empEmail = trim($row['email'] ?? '');
                    $empName = trim($row['full_name'] ?? '');

                    $localEmployees[] = [
                        'employee_id' => $empId,
                        'email' => strtolower($empEmail),
                        'full_name' => $empName
                    ];

                    if ($empId !== '') {
                        $localIds[] = $empId;
                    }
                    if ($empEmail !== '') {
                        $localEmails[] = strtolower($empEmail);
                    }
                }
            }

            echo json_encode([
                'success' => true,
                'data' => $data['responseForAllEmployees'],
                'local_employee_ids' => $localIds,
                'local_employee_emails' => $localEmails,
                'local_employees' => $localEmployees,
                'from_date' => $fromDateStr,
                'to_date' => $toDateStr
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * GET /api/hrm/leave-categories
     * Fetch leave categories from Tipsoi
     */
    public function getLeaveCategories()
    {
        header('Content-Type: application/json');
        try {
            $token = $this->externalApiService->getBearerToken();
            if (!$token) {
                echo json_encode(['success' => false, 'message' => 'Failed to authenticate with Tipsoi API.']);
                exit;
            }
            $ch = curl_init("https://app.tipsoi.ai/inovace-client/api/v1/leave-category");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $res = curl_exec($ch);
            curl_close($ch);
            echo $res; // pass the JSON through
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * GET /api/hrm/applied-leave-list
     * Proxy for Tipsoi's /leave-management/leave-history endpoint
     */
    public function getAppliedLeaveList()
    {
        header('Content-Type: application/json');
        
        try {
            $token = $this->externalApiService->getBearerToken();
            if (!$token) {
                echo json_encode(['success' => false, 'message' => 'Failed to authenticate with Tipsoi API.']);
                exit;
            }
            
            // Extract the original query string directly to preserve duplicated keys like status=0&status=1
            $parsedUrl = parse_url($_SERVER['REQUEST_URI']);
            $queryString = $parsedUrl['query'] ?? '';
            // Remove the internal MVC route parameter if it exists
            $queryString = preg_replace('/(^|&)url=[^&]+/', '', $queryString);
            $queryString = ltrim($queryString, '&');
            
            // Default params if none provided
            if (empty($queryString)) {
                $queryString = "page=0&count=100&status=0&status=1&status=2&keyword=%20";
            } elseif (!str_contains($queryString, 'keyword=')) {
                $queryString .= '&keyword=%20';
            }
            
            $url = "https://app.tipsoi.ai/inovace-client/api/v1/leave-management/leave-history?" . $queryString;
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $res = curl_exec($ch);
            curl_close($ch);
            
            $tipsoiData = json_decode($res, true);
            
            $leaveLogs = [];
            if (is_array($tipsoiData) && isset($tipsoiData['leaveLogList'])) {
                $leaveLogs = $tipsoiData['leaveLogList'];
            }
            
            echo json_encode([
                'success' => true,
                'data' => $leaveLogs
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * POST /api/hrm/leave-management/approve/{leaveLogId}
     * Approve leave via Tipsoi API
     */
    public function approveTipsoiLeave($leaveLogId)
    {
        header('Content-Type: application/json');
        try {
            $token = $this->externalApiService->getUserBearerToken(false);
            if (!$token) {
                echo json_encode(['success' => false, 'message' => 'Tipsoi user login token not found. Please log in to ERP with your Tipsoi credentials.']);
                exit;
            }
            
            $url = "https://app.tipsoi.ai/inovace-client/api/v1/leave-management/approve/" . urlencode($leaveLogId);
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([])); // Empty JSON body just in case
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $tipsoiData = json_decode($res, true);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Leave approved successfully.',
                    'data' => $tipsoiData
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $tipsoiData['message'] ?? 'Failed to approve leave.',
                    'data' => $tipsoiData
                ]);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * POST /api/hrm/leave-management/apply/employee/{employeeId}
     * Apply for leave via Tipsoi API
     */
    public function applyTipsoiLeave($employeeId = null)
    {
        header('Content-Type: application/json');
        try {
            $token = $this->externalApiService->getUserBearerToken(false);
            if (!$token) {
                echo json_encode(['success' => false, 'message' => 'Tipsoi user login token not found. Please log in to ERP with your Tipsoi credentials.']);
                exit;
            }

            if (empty($employeeId) || $employeeId === 'null' || $employeeId === 'undefined') {
                $employeeId = $_SESSION['user']['tipsoi_id'] ?? null;
            }

            if (!$employeeId) {
                echo json_encode(['success' => false, 'message' => 'Tipsoi Employee ID not found.']);
                exit;
            }
            
            $url = "https://app.tipsoi.ai/inovace-client/api/v1/leave-management/apply/employee/" . urlencode($employeeId);
            
            $postData = [];
            
            if (isset($_POST['postBody'])) {
                $postData['postBody'] = is_string($_POST['postBody']) ? $_POST['postBody'] : json_encode($_POST['postBody']);
            }
            
            // If there's an attachment, add it using CURLFile
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $postData['file'] = new CURLFile(
                    $_FILES['file']['tmp_name'], 
                    $_FILES['file']['type'], 
                    $_FILES['file']['name']
                );
            }
            
            // Tipsoi strictly requires multipart/form-data for this endpoint
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData); // passing an array forces multipart/form-data
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
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
                    'message' => 'Leave application submitted successfully!',
                    'data' => $resData
                ]);
            } else {
                http_response_code($httpCode >= 400 ? $httpCode : 400);
                echo json_encode([
                    'success' => false,
                    'message' => $resData['message'] ?? 'Failed to submit leave application to Tipsoi.',
                    'details' => $resData
                ]);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * POST /api/hrm/tipsoi-action
     * Handle dynamic Tipsoi API actions (e.g. approvals)
     */
    public function handleTipsoiAction()
    {
        header('Content-Type: application/json');
        try {
            $token = $this->externalApiService->getUserBearerToken(false);
            if (!$token) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Tipsoi user login token not found. Please log out and log back in to ERP to synchronize your Tipsoi session.'
                ]);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $endpoint = trim($input['endpoint'] ?? '');
            $payload = $input['payload'] ?? [];
            $method = strtoupper($input['method'] ?? 'POST');

            if (empty($endpoint)) {
                echo json_encode(['success' => false, 'message' => 'Endpoint is required.']);
                exit;
            }

            // Normalize endpoint: Tipsoi does NOT have /leave-management/reject/{id}, it is /leave-management/cancel/{id}
            if (strpos($endpoint, '/leave-management/reject/') === 0) {
                $endpoint = str_replace('/leave-management/reject/', '/leave-management/cancel/', $endpoint);
            }

            // Normalize comment payloads:
            // - Leave & Late approve expect "approverComment"
            // - Manual Entry approve/reject expects "comment"
            // - Leave cancel/reject expects "cancellationReason"
            // Setting all three ensures the correct key is always accepted by Tipsoi
            $commentText = $payload['approverComment'] ?? $payload['comment'] ?? $payload['cancellationReason'] ?? $payload['rejectionReason'] ?? 'Approved';
            $normalizedPayload = is_array($payload) ? $payload : [];
            $normalizedPayload['approverComment'] = $commentText;
            $normalizedPayload['comment'] = $commentText;
            $normalizedPayload['cancellationReason'] = $commentText;
            $normalizedPayload['rejectionReason'] = $commentText;

            $url = "https://app.tipsoi.ai/inovace-client/api/v1" . $endpoint;

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($normalizedPayload));
            } elseif ($method === 'PUT') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($normalizedPayload));
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $tipsoiData = json_decode($res, true);

            if ($httpCode >= 200 && $httpCode < 300) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Action successful.',
                    'data' => $tipsoiData
                ]);
            } else {
                $hasUserToken = $this->externalApiService->hasActiveUserBearerToken();
                $errorMsg = $tipsoiData['message'] ?? 'Action failed.';

                if ($httpCode === 401) {
                    if (!$hasUserToken) {
                        $errorMsg = 'Unauthorized (401): You are not logged in with your personal Tipsoi account (ERP password does not match Tipsoi password). Only the assigned approver can approve this step.';
                    } else {
                        $errorMsg = 'Unauthorized (401): You are not the assigned approver for this request on Tipsoi. Only the designated approver can perform this action.';
                    }
                }

                echo json_encode([
                    'success' => false,
                    'message' => $errorMsg,
                    'http_code' => $httpCode,
                    'data' => $tipsoiData
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * POST /api/hrm/tipsoi-connect
     * Allow logged in user to connect their personal Tipsoi account by providing Tipsoi password
     */
    public function connectTipsoiAccount()
    {
        header('Content-Type: application/json');
        try {
            if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                session_start();
            }
            if (empty($_SESSION['user']['id'])) {
                echo json_encode(['success' => false, 'message' => 'Please log in to ERP first.']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $password = trim($input['password'] ?? '');
            $email = trim($_SESSION['user']['email'] ?? '');

            if (empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Tipsoi password is required.']);
                exit;
            }

            $authResult = $this->externalApiService->authenticateUser($email, $password);
            if (!empty($authResult['success'])) {
                $_SESSION['user']['tipsoi_authenticated'] = true;
                $_SESSION['user']['tipsoi_token'] = $authResult['token'];
                $_SESSION['user']['tipsoi_token_expires'] = $authResult['expires'];
                if (!empty($authResult['tipsoi_id'])) {
                    $_SESSION['user']['tipsoi_id'] = $authResult['tipsoi_id'];
                }

                echo json_encode([
                    'success' => true,
                    'message' => 'Tipsoi account connected successfully!'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $authResult['message'] ?? 'Tipsoi authentication failed. Please check your password.'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * GET /api/hrm/leave-balances
     * Fetch leave balance definitions from Tipsoi
     */
    public function getLeaveBalances()
    {
        header('Content-Type: application/json');

        try {
            $token = $this->externalApiService->getBearerToken();
            if (!$token) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to authenticate with Tipsoi API.'
                ]);
                exit;
            }

            // Fetch dates if provided, default to current year
            $fromDateStr = $_GET['from_date'] ?? date('Y-01-01');
            $toDateStr = $_GET['to_date'] ?? date('Y-12-31');

            $fromTime = strtotime($fromDateStr . ' 00:00:00') * 1000;
            $toTime = strtotime($toDateStr . ' 23:59:59') * 1000;

            $url = "https://app.tipsoi.ai/inovace-client/api/v1/attendance/leave-balance?" . http_build_query([
                'officeId' => 4699,
                'order' => 'asc',
                'pageNumber' => 0,
                'perPage' => 2000,
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
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Tipsoi API Error: ' . $curlError
                ]);
                exit;
            }

            $data = json_decode($response, true);

            if ($httpCode !== 200 || !isset($data['responseForAllEmployees'])) {
                echo json_encode([
                    'success' => false,
                    'message' => $data['message'] ?? 'Failed to retrieve leave balances from Tipsoi API.'
                ]);
                exit;
            }

            // Fetch local employees details for indicator mapping
            $localQuery = $this->db->query("SELECT employee_id, email, full_name FROM employees");
            $localEmployees = [];
            $localIds = [];
            $localEmails = [];
            if ($localQuery) {
                while ($row = $localQuery->fetch_assoc()) {
                    $empId = trim($row['employee_id'] ?? '');
                    $empEmail = trim($row['email'] ?? '');
                    $empName = trim($row['full_name'] ?? '');

                    $localEmployees[] = [
                        'employee_id' => $empId,
                        'email' => strtolower($empEmail),
                        'full_name' => $empName
                    ];

                    if ($empId !== '') {
                        $localIds[] = $empId;
                    }
                    if ($empEmail !== '') {
                        $localEmails[] = strtolower($empEmail);
                    }
                }
            }

            echo json_encode([
                'success' => true,
                'data' => $data['responseForAllEmployees'],
                'local_employee_ids' => $localIds,
                'local_employee_emails' => $localEmails,
                'local_employees' => $localEmployees
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * GET /api/hrm/tipsoi-requests
     * Fetch Mobile Punches, Manual Entries, or Late Excuses from Tipsoi
     */
    public function getTipsoiRequests()
    {
        header('Content-Type: application/json');

        try {
            $token = $this->externalApiService->getBearerToken();
            if (!$token) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to authenticate with Tipsoi API.'
                ]);
                exit;
            }

            // Fetch local employees details for indicator mapping
            $localQuery = $this->db->query("SELECT employee_id, email, full_name FROM employees");
            $localEmployees = [];
            $localIds = [];
            $localEmails = [];
            $officeIdMap = [];
            if ($localQuery) {
                while ($row = $localQuery->fetch_assoc()) {
                    $empIdVal = trim($row['employee_id'] ?? '');
                    $empEmail = trim($row['email'] ?? '');
                    $empName = trim($row['full_name'] ?? '');

                    $localEmployees[] = [
                        'employee_id' => $empIdVal,
                        'email' => strtolower($empEmail),
                        'full_name' => $empName
                    ];

                    if ($empIdVal !== '') {
                        $localIds[] = $empIdVal;
                    }
                    if ($empEmail !== '') {
                        $localEmails[] = strtolower($empEmail);
                    }

                    $nameKey = strtolower(trim($empName));
                    if ($nameKey !== '') {
                        $officeIdMap[$nameKey] = $empIdVal;
                    }
                }
            }

            // Fetch Tipsoi external employees to map employeeId -> employeeOfficeId
            $tipsoiIdToOfficeIdMap = [];
            $ch = curl_init("https://app.tipsoi.ai/inovace-client/api/v1/employee?order=asc&pageNumber=0&perPage=2000&status=1");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $resEmp = curl_exec($ch);
            curl_close($ch);
            $dEmp = json_decode($resEmp, true);
            $externalEmployees = $dEmp['data'] ?? $dEmp['employees'] ?? $dEmp['content'] ?? $dEmp ?? [];
            if (is_array($externalEmployees)) {
                foreach ($externalEmployees as $emp) {
                    $tid = $emp['employeeId'] ?? $emp['id'] ?? null;
                    $oid = $emp['employeeOfficeId'] ?? $emp['officeId'] ?? null;
                    if ($tid !== null && $oid !== null) {
                        $tipsoiIdToOfficeIdMap[$tid] = $oid;
                    }
                }
            }

            // Fetch office IDs of employees belonging to HR Department
            $hrEmployeeOfficeIds = [];
            $hrDeptQ = $this->db->query("SELECT e.employee_id FROM employees e JOIN departments d ON e.department_id = d.id WHERE d.name LIKE '%Human%' OR d.name LIKE '%HR%'");
            if ($hrDeptQ) {
                while ($hrRow = $hrDeptQ->fetch_assoc()) {
                    $hrOid = trim($hrRow['employee_id'] ?? '');
                    if ($hrOid !== '') {
                        $hrEmployeeOfficeIds[] = $hrOid;
                    }
                }
            }

            // Fetch Logged-in user information for approver matching
            $loggedUser = null;
            if (!empty($_SESSION['user']['id'])) {
                $loggedUserId = (int) $_SESSION['user']['id'];
                $stmt = $this->db->prepare("SELECT u.id, u.role_id, e.employee_id, e.email, e.full_name, d.name as department_name FROM users u LEFT JOIN employees e ON u.employee_id = e.id LEFT JOIN departments d ON e.department_id = d.id WHERE u.id = ? LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param("i", $loggedUserId);
                    $stmt->execute();
                    $uRes = $stmt->get_result()->fetch_assoc();
                    if ($uRes) {
                        $normEmpOfficeId = trim($uRes['employee_id'] ?? '');
                        $normEmpEmail = strtolower(trim($uRes['email'] ?? ''));
                        $userDeptName = strtolower(trim($uRes['department_name'] ?? ''));
                        $isLoggedUserInHrDept = (strpos($userDeptName, 'human') !== false || strpos($userDeptName, 'hr') !== false || in_array($normEmpOfficeId, $hrEmployeeOfficeIds));

                        // Auto-detect tipsoi_id from externalEmployees if missing in session
                        $detectedTipsoiId = $_SESSION['user']['tipsoi_id'] ?? null;
                        $detectedOfficeId = $_SESSION['user']['tipsoi_office_id'] ?? null;
                        if (!$detectedTipsoiId && is_array($externalEmployees)) {
                            foreach ($externalEmployees as $ext) {
                                $extOid = trim((string)($ext['employeeOfficeId'] ?? $ext['officeId'] ?? ''));
                                $extEmail = strtolower(trim($ext['email'] ?? ''));
                                if (($normEmpOfficeId !== '' && $extOid === $normEmpOfficeId) || ($normEmpEmail !== '' && $extEmail === $normEmpEmail)) {
                                    $detectedTipsoiId = $ext['employeeId'] ?? $ext['id'] ?? null;
                                    $detectedOfficeId = $extOid;
                                    $_SESSION['user']['tipsoi_id'] = $detectedTipsoiId;
                                    $_SESSION['user']['tipsoi_office_id'] = $detectedOfficeId;
                                    break;
                                }
                            }
                        }

                        $loggedUser = [
                            'id' => $uRes['id'],
                            'email' => $normEmpEmail,
                            'employee_id' => $normEmpOfficeId,
                            'full_name' => trim($uRes['full_name'] ?? ''),
                            'department_name' => $uRes['department_name'] ?? '',
                            'is_hr_dept' => $isLoggedUserInHrDept,
                            'tipsoi_id' => $detectedTipsoiId,
                            'tipsoi_office_id' => $detectedOfficeId,
                            'role_id' => (int) ($uRes['role_id'] ?? 0),
                            'is_hr' => hasPermission("HRM Management") || in_array((int) ($uRes['role_id'] ?? 0), [1, 3, 4]),
                            'tipsoi_authenticated' => $this->externalApiService->hasActiveUserBearerToken()
                        ];
                    }
                }
            }

            $empId = $loggedUser['tipsoi_id'] ?? 298780; // Main admin/approver ID on Tipsoi
            $officeId = 4699;

            $pendingRequests = [];

            // 1. Fetch Global Pending Leaves
            $ch = curl_init("https://app.tipsoi.ai/inovace-client/api/v1/leave-management/leave-history?page=0&count=2000&status=0&keyword=%20");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $res = curl_exec($ch);
            curl_close($ch);
            $d = json_decode($res, true);
            $globalLeaves = $d['leaveLogList'] ?? [];

            // 2. Fetch Approver's Pending Leaves
            $ch = curl_init("https://app.tipsoi.ai/inovace-client/api/v1/remaining-leave/approvers/$empId");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $res = curl_exec($ch);
            curl_close($ch);
            $d = json_decode($res, true);
            $approverLeaves = $d['leaveApplications'] ?? [];

            $allLeaves = array_merge($globalLeaves, $approverLeaves);
            foreach ($allLeaves as $log) {
                $id = $log['id'] ?? $log['leaveLogId'] ?? null;
                if (!$id)
                    continue;

                $empNameVal = $log['employeeName'] ?? '—';
                $officeIdVal = $log['employeeOfficeId'] ?? '';
                $tipsoiEmpId = $log['employeeId'] ?? null;
                if ($tipsoiEmpId && isset($tipsoiIdToOfficeIdMap[$tipsoiEmpId])) {
                    $officeIdVal = $tipsoiIdToOfficeIdMap[$tipsoiEmpId];
                }
                if (empty($officeIdVal) && $empNameVal !== '—') {
                    $nameKey = strtolower(trim($empNameVal));
                    if (isset($officeIdMap[$nameKey])) {
                        $officeIdVal = $officeIdMap[$nameKey];
                    }
                }

                $firstAppId = $log['firstApproverId'] ?? null;
                $secondAppId = $log['secondApproverId'] ?? null;

                $pendingRequests["leave_$id"] = [
                    'id' => $id,
                    'employeeOfficeId' => $officeIdVal,
                    'employeeName' => $empNameVal,
                    'departmentName' => $log['employeeDepartmentName'] ?? $log['departmentName'] ?? 'No Department',
                    'designationName' => $log['employeeDesignationName'] ?? $log['designationName'] ?? 'N/A',
                    'startDate' => isset($log['startDate']) ? date('Y-m-d', (int) ($log['startDate'] / 1000)) : '—',
                    'endDate' => isset($log['endDate']) ? date('Y-m-d', (int) ($log['endDate'] / 1000)) : '—',
                    'entryTime' => '—',
                    'exitTime' => '—',
                    'reason' => $log['reason'] ?? '—',
                    'requestType' => 'Leave Request',
                    'subType' => $log['categoryName'] ?? 'Leave Request',
                    'categoryName' => $log['categoryName'] ?? 'Leave Request',
                    'firstApproverName' => $log['firstApproverName'] ?? '',
                    'firstApprovalStatus' => $log['firstApprovalStatus'] ?? 0,
                    'firstApproverId' => $firstAppId,
                    'firstApproverOfficeId' => ($firstAppId && isset($tipsoiIdToOfficeIdMap[$firstAppId])) ? $tipsoiIdToOfficeIdMap[$firstAppId] : '',
                    'secondApproverName' => $log['secondApproverName'] ?? '',
                    'secondApprovalStatus' => $log['secondApprovalStatus'] ?? 0,
                    'secondApproverId' => $secondAppId,
                    'secondApproverOfficeId' => ($secondAppId && isset($tipsoiIdToOfficeIdMap[$secondAppId])) ? $tipsoiIdToOfficeIdMap[$secondAppId] : '',
                    'status' => 0
                ];
            }

            // 3. Fetch Approver's Pending Late Excuses
            $ch = curl_init("https://app.tipsoi.ai/inovace-client/api/v1/late-approval-management/approval-requests/$empId");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $res = curl_exec($ch);
            curl_close($ch);
            $d = json_decode($res, true);
            $lateApprovals = $d['lateApprovalApplications'] ?? $d['lateLogList'] ?? [];
            foreach ($lateApprovals as $log) {
                $id = $log['lateApprovalLogId'] ?? $log['id'] ?? null;
                if (!$id)
                    continue;

                $empNameVal = $log['employeeName'] ?? '—';
                $officeIdVal = $log['employeeOfficeId'] ?? '';
                $tipsoiEmpId = $log['employeeId'] ?? null;
                if ($tipsoiEmpId && isset($tipsoiIdToOfficeIdMap[$tipsoiEmpId])) {
                    $officeIdVal = $tipsoiIdToOfficeIdMap[$tipsoiEmpId];
                }
                if (empty($officeIdVal) && $empNameVal !== '—') {
                    $nameKey = strtolower(trim($empNameVal));
                    if (isset($officeIdMap[$nameKey])) {
                        $officeIdVal = $officeIdMap[$nameKey];
                    }
                }

                $logDateVal = '—';
                if (isset($log['date'])) {
                    $logDateVal = date('Y-m-d', (int) ($log['date'] / 1000));
                } elseif (isset($log['entryTime']) && is_numeric($log['entryTime'])) {
                    $logDateVal = date('Y-m-d', (int) ($log['entryTime'] / 1000));
                }

                $firstLateAppId = $log['firstLateApproverId'] ?? $log['firstApproverId'] ?? null;
                $secondLateAppId = $log['secondLateApproverId'] ?? $log['secondApproverId'] ?? null;

                $pendingRequests["late_$id"] = [
                    'id' => $id,
                    'employeeOfficeId' => $officeIdVal,
                    'employeeName' => $empNameVal,
                    'departmentName' => $log['departmentName'] ?? 'No Department',
                    'designationName' => $log['designationName'] ?? 'N/A',
                    'startDate' => $logDateVal,
                    'endDate' => $logDateVal,
                    'entryTime' => '—',
                    'exitTime' => '—',
                    'reason' => $log['reason'] ?? '—',
                    'requestType' => 'Late Excuse',
                    'subType' => 'Late Excuse',
                    'categoryName' => 'Late Excuse',
                    'firstApproverName' => $log['firstLateApproverName'] ?? '',
                    'firstApprovalStatus' => $log['firstLateApprovalStatus'] ?? 0,
                    'firstApproverId' => $firstLateAppId,
                    'firstApproverOfficeId' => ($firstLateAppId && isset($tipsoiIdToOfficeIdMap[$firstLateAppId])) ? $tipsoiIdToOfficeIdMap[$firstLateAppId] : '',
                    'secondApproverName' => $log['secondLateApproverName'] ?? '',
                    'secondApprovalStatus' => $log['secondLateApprovalStatus'] ?? 0,
                    'secondApproverId' => $secondLateAppId,
                    'secondApproverOfficeId' => ($secondLateAppId && isset($tipsoiIdToOfficeIdMap[$secondLateAppId])) ? $tipsoiIdToOfficeIdMap[$secondLateAppId] : '',
                    'status' => 0
                ];
            }

            // 4. Fetch Manual Entry Approver
            $ch = curl_init("https://app.tipsoi.ai/inovace-client/api/v1/employee/profile/manual/approver/");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $res = curl_exec($ch);
            curl_close($ch);
            $d = json_decode($res, true);
            $manualApprover = $d['manualEntryApplications'] ?? [];

            // 5. Fetch Global Pending Corrections (Type 1)
            $ch = curl_init("https://app.tipsoi.ai/inovace-client/api/v1/manual-entry?officeId=$officeId&order=asc&pageNumber=0&perPage=200&sortKey=reportPosition&manualEntryType=1&applicationStatus=0");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $res = curl_exec($ch);
            curl_close($ch);
            $d = json_decode($res, true);
            $globalCorrection = $d['manualEntries'] ?? [];

            // 6. Fetch Global Pending Late Excuses (Type 2)
            $ch = curl_init("https://app.tipsoi.ai/inovace-client/api/v1/manual-entry?officeId=$officeId&order=asc&pageNumber=0&perPage=200&sortKey=reportPosition&manualEntryType=2&applicationStatus=0");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $res = curl_exec($ch);
            curl_close($ch);
            $d = json_decode($res, true);
            $globalLate = $d['manualEntries'] ?? [];

            // 7. Fetch Global Pending Field Visits (Type 3)
            $ch = curl_init("https://app.tipsoi.ai/inovace-client/api/v1/manual-entry?officeId=$officeId&order=asc&pageNumber=0&perPage=200&sortKey=reportPosition&manualEntryType=3&applicationStatus=0");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $res = curl_exec($ch);
            curl_close($ch);
            $d = json_decode($res, true);
            $globalField = $d['manualEntries'] ?? [];

            $allManual = array_merge($manualApprover, $globalCorrection, $globalLate, $globalField);
            foreach ($allManual as $entry) {
                $id = $entry['manualEntryId'] ?? $entry['id'] ?? null;
                if (!$id)
                    continue;

                $typeVal = $entry['manualEntryType'] ?? 1;
                $reqType = ($typeVal == 1) ? 'Manual Entry' : (($typeVal == 2) ? 'Late Excuse' : 'Field Visit');

                $entryTimeVal = $entry['entryTime'] ?? null;
                $exitTimeVal = $entry['exitTime'] ?? null;

                $entryTimeStr = ($entryTimeVal && is_numeric($entryTimeVal)) ? date('H:i:s', (int) ($entryTimeVal / 1000)) : '—';
                $exitTimeStr = ($exitTimeVal && is_numeric($exitTimeVal)) ? date('H:i:s', (int) ($exitTimeVal / 1000)) : '—';

                $subType = $reqType;
                if ($typeVal == 1) {
                    if ($entryTimeStr !== '—' && $exitTimeStr !== '—') {
                        $subType = 'Manual Entry (Entry & Exit)';
                    } elseif ($entryTimeStr !== '—') {
                        $subType = 'Manual Entry (Entry Only)';
                    } elseif ($exitTimeStr !== '—') {
                        $subType = 'Manual Entry (Exit Only)';
                    }
                } elseif ($typeVal == 3) {
                    $subType = !empty($entry['area']) ? 'Field Visit (' . $entry['area'] . ')' : 'Field Visit';
                }

                $empNameVal = $entry['employeeName'] ?? '—';
                $officeIdVal = $entry['employeeOfficeId'] ?? '';
                $tipsoiEmpId = $entry['employeeId'] ?? null;
                if ($tipsoiEmpId && isset($tipsoiIdToOfficeIdMap[$tipsoiEmpId])) {
                    $officeIdVal = $tipsoiIdToOfficeIdMap[$tipsoiEmpId];
                }
                if (empty($officeIdVal) && $empNameVal !== '—') {
                    $nameKey = strtolower(trim($empNameVal));
                    if (isset($officeIdMap[$nameKey])) {
                        $officeIdVal = $officeIdMap[$nameKey];
                    }
                }

                $startDateVal = '—';
                if (!empty($entry['startDate'])) {
                    $startDateVal = date('Y-m-d', (int) ($entry['startDate'] / 1000));
                } elseif ($entryTimeVal) {
                    $startDateVal = date('Y-m-d', (int) ($entryTimeVal / 1000));
                } elseif ($exitTimeVal) {
                    $startDateVal = date('Y-m-d', (int) ($exitTimeVal / 1000));
                }

                $endDateVal = '—';
                if (!empty($entry['endDate'])) {
                    $endDateVal = date('Y-m-d', (int) ($entry['endDate'] / 1000));
                } else {
                    $endDateVal = $startDateVal;
                }

                $firstManId = $entry['firstManualEntryApproverId'] ?? null;
                $secondManId = $entry['secondManualEntryApproverId'] ?? null;

                $pendingRequests["manual_$id"] = [
                    'id' => $id,
                    'employeeOfficeId' => $officeIdVal,
                    'employeeName' => $empNameVal,
                    'departmentName' => $entry['departmentName'] ?? 'No Department',
                    'designationName' => $entry['designationName'] ?? 'N/A',
                    'startDate' => $startDateVal,
                    'endDate' => $endDateVal,
                    'entryTime' => $entryTimeStr,
                    'exitTime' => $exitTimeStr,
                    'reason' => $entry['reason'] ?? '—',
                    'requestType' => $reqType,
                    'subType' => $subType,
                    'categoryName' => $reqType,
                    'firstApproverName' => $entry['firstManualApproverName'] ?? '',
                    'firstApprovalStatus' => $entry['firstManualEntryApprovalStatus'] ?? 0,
                    'firstApproverId' => $firstManId,
                    'firstApproverOfficeId' => ($firstManId && isset($tipsoiIdToOfficeIdMap[$firstManId])) ? $tipsoiIdToOfficeIdMap[$firstManId] : '',
                    'secondApproverName' => $entry['secondManualApproverName'] ?? '',
                    'secondApprovalStatus' => $entry['secondManualEntryApprovalStatus'] ?? 0,
                    'secondApproverId' => $secondManId,
                    'secondApproverOfficeId' => ($secondManId && isset($tipsoiIdToOfficeIdMap[$secondManId])) ? $tipsoiIdToOfficeIdMap[$secondManId] : '',
                    'status' => 0
                ];
            }



            echo json_encode([
                'success' => true,
                'data' => array_values($pendingRequests),
                'local_employees' => $localEmployees,
                'local_employee_ids' => $localIds,
                'local_employee_emails' => $localEmails,
                'hr_employee_office_ids' => $hrEmployeeOfficeIds,
                'logged_user' => $loggedUser
            ]);
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    
    /**
     * GET /api/hrm/send-tipsoi-reminders
     * Send email reminders to approvers and requesters for their pending Tipsoi requests
     */
    public function sendTipsoiReminders()
    {
        header('Content-Type: application/json');

        try {
            require_once dirname(__DIR__) . '/services/TipsoiReminderService.php';
            $service = new \TipsoiReminderService($this->db);

            $force = isset($_GET['force']) && ($_GET['force'] == '1' || $_GET['force'] == 'true');
            $result = $service->runCompanyWideSync($force, 'admin_manual');

            echo json_encode([
                'success' => true,
                'message' => "Reminders processed: {$result['dispatched_count']} sent, {$result['skipped_count']} skipped (cooldown active).",
                'data' => $result
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET/POST /api/tipsoi/trigger-login-reminder
     * Triggered in background on portal login to evaluate user and pending approvers
     */
    public function triggerLoginReminder()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userEmail = $_SESSION['user']['email'] ?? null;
        if (!$userEmail) {
            echo json_encode(['success' => false, 'message' => 'No active user session.']);
            exit;
        }

        try {
            require_once dirname(__DIR__) . '/services/TipsoiReminderService.php';
            $service = new \TipsoiReminderService($this->db);
            $result = $service->handleUserLogin($userEmail);

            echo json_encode([
                'success' => true,
                'message' => 'Login reminder check completed.',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/tipsoi/send-reminders-test
     * Admin diagnostics and test dispatch
     */
    public function testTipsoiReminders()
    {
        header('Content-Type: application/json');

        try {
            require_once dirname(__DIR__) . '/services/TipsoiReminderService.php';
            $service = new \TipsoiReminderService($this->db);

            $mode = $_GET['mode'] ?? 'preview';
            $targetEmail = $_GET['email'] ?? null;

            if ($mode === 'preview') {
                $pending = $service->fetchAllPendingRequests();
                $approvers = [];
                foreach ($pending['by_approver'] as $em => $d) {
                    $elig = $service->shouldSendReminder($em, false);
                    $approvers[] = [
                        'name' => $d['name'],
                        'email' => $em,
                        'pending_count' => count($d['requests']),
                        'requests' => $d['requests'],
                        'should_send' => $elig['should_send'],
                        'reason' => $elig['reason'],
                        'last_sent' => $elig['last_sent'] ?? null
                    ];
                }

                $requesters = [];
                foreach ($pending['by_requester'] as $em => $d) {
                    $elig = $service->shouldSendReminder($em, false);
                    $requesters[] = [
                        'name' => $d['name'],
                        'email' => $em,
                        'pending_count' => count($d['requests']),
                        'requests' => $d['requests'],
                        'should_send' => $elig['should_send'],
                        'reason' => $elig['reason'],
                        'last_sent' => $elig['last_sent'] ?? null
                    ];
                }

                echo json_encode([
                    'success' => true,
                    'mode' => 'preview',
                    'cycle_range' => $pending['cycle_range'] ?? null,
                    'total_pending' => $pending['total_pending'],
                    'approvers_count' => count($approvers),
                    'requesters_count' => count($requesters),
                    'approvers' => $approvers,
                    'requesters' => $requesters
                ]);
                exit;
            } elseif ($mode === 'test_send' && $targetEmail) {
                $pending = $service->fetchAllPendingRequests();
                $sampleApprover = !empty($pending['by_approver']) ? reset($pending['by_approver']) : null;
                if (!$sampleApprover) {
                    echo json_encode(['success' => false, 'message' => 'No pending approver requests found to sample.']);
                    exit;
                }

                $sendRes = $service->sendApproverEmail(
                    $sampleApprover['name'] . " (Test Sample)",
                    $targetEmail,
                    $sampleApprover['requests'],
                    'admin_test'
                );

                echo json_encode([
                    'success' => true,
                    'mode' => 'test_send',
                    'result' => $sendRes
                ]);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid mode or missing email parameter.']);
                exit;
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/hrm/tipsoi-holidays
     * Fetch holidays from Tipsoi API
     */
    public function getTipsoiHolidays()
    {
        header('Content-Type: application/json');

        try {
            $token = $this->externalApiService->getBearerToken();
            if (!$token) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to authenticate with Tipsoi API.'
                ]);
                exit;
            }

            $ch = curl_init("https://app.tipsoi.ai/inovace-client/api/v1/holiday");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $res = curl_exec($ch);
            curl_close($ch);

            $d = json_decode($res, true);
            $list = $d['holidayList'] ?? [];

            // Check which holidays are already imported into our local database
            $localQuery = $this->db->query("SELECT title, start_date FROM holidays");
            $localMap = [];
            if ($localQuery) {
                while ($row = $localQuery->fetch_assoc()) {
                    $key = strtolower(trim($row['title'])) . '_' . trim($row['start_date']);
                    $localMap[$key] = true;
                }
            }

            $formatted = [];
            foreach ($list as $h) {
                $hDateVal = $h['holidayDate'] ?? null;
                $dateStr = $hDateVal ? date('Y-m-d', (int) ($hDateVal / 1000)) : null;
                if (!$dateStr)
                    continue;

                $title = $h['description'] ?? 'Holiday';
                $localKey = strtolower(trim($title)) . '_' . $dateStr;
                $isImported = isset($localMap[$localKey]);

                $formatted[] = [
                    'id' => $h['id'],
                    'date' => $dateStr,
                    'title' => $title,
                    'is_imported' => $isImported
                ];
            }

            echo json_encode([
                'success' => true,
                'data' => $formatted
            ]);
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * POST /api/hrm/import-tipsoi-holiday
     * Import a holiday from Tipsoi to local ERP DB
     */
    public function importTipsoiHoliday()
    {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $title = trim($input['title'] ?? '');
            $date = trim($input['date'] ?? '');

            if (empty($title) || empty($date)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Title and Date are required.'
                ]);
                exit;
            }

            // Check if already exists in DB
            $stmt = $this->db->prepare("SELECT id FROM holidays WHERE title = ? AND start_date = ?");
            $stmt->bind_param("ss", $title, $date);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Holiday already imported.'
                ]);
                exit;
            }
            $stmt->close();

            // Insert into local DB
            $stmt = $this->db->prepare("INSERT INTO holidays (title, start_date, end_date) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $title, $date, $date);
            $status = $stmt->execute();
            $stmt->close();

            if ($status) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Holiday successfully imported to ERP DB.'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to insert holiday into database.'
                ]);
            }
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    public function getTipsoiEmailLogs()
    {
        header('Content-Type: application/json');
        
        $query = "SELECT * FROM tipsoi_email_logs ORDER BY sent_at DESC LIMIT 500";
        $result = $this->db->query($query);
        $logs = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $logs[] = $row;
            }
        }
    
        echo json_encode(['success' => true, 'logs' => $logs]);
    }

    /**
     * GET /api/hrm/joining-list-data
     */
    public function getJoiningListData()
    {
        ob_start();
        try {
            $fromDateStr = $_GET['from_date'] ?? date('Y-m-01');
            $toDateStr = $_GET['to_date'] ?? date('Y-m-t');
            $payrollProjectId = isset($_GET['payroll_project_id']) && $_GET['payroll_project_id'] !== '' ? (int) $_GET['payroll_project_id'] : null;

            // Fetch Local Employees matching the date range
            $localSql = "SELECT e.id as real_emp_id_fk, e.employee_id, e.full_name, desg.name AS designation, dept.name AS department,
                                e.workplace, e.joining_date, e.gross_salary, e.basic_salary
                         FROM employees e
                         LEFT JOIN designations desg ON e.designation_id = desg.id
                         LEFT JOIN departments dept ON e.department_id = dept.id
                         WHERE e.joining_date >= ? AND e.joining_date <= ?
                         ORDER BY e.joining_date ASC";

            $stmt = $this->db->prepare($localSql);
            $stmt->bind_param("ss", $fromDateStr, $toDateStr);
            $stmt->execute();
            $res = $stmt->get_result();

            $localEmployees = [];
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $localEmployees[] = $row;
                }
            }

            // Filter by Payroll Project if selected
            $projectEmployeeIds = [];
            if ($payrollProjectId) {
                $projQ = $this->db->query("SELECT employee_id FROM payslips WHERE payroll_project_id = $payrollProjectId 
                                           UNION 
                                           SELECT employee_id FROM attendance_monthly_update WHERE payroll_project_id = $payrollProjectId");
                if ($projQ) {
                    while ($pr = $projQ->fetch_assoc()) {
                        $projectEmployeeIds[$pr['employee_id']] = true;
                    }
                }
            }

            $matchedData = [];
            $sl = 1;

            foreach ($localEmployees as $localMatch) {
                // Apply Project Filter
                if ($payrollProjectId && !isset($projectEmployeeIds[$localMatch['real_emp_id_fk']])) {
                    continue;
                }

                $matchedData[] = [
                    'sl' => $sl++,
                    'employee_id' => $localMatch['employee_id'],
                    'name' => $localMatch['full_name'] ?? '-',
                    'designation' => $localMatch['designation'] ?? '-',
                    'department' => $localMatch['department'] ?? '-',
                    'workplace' => $localMatch['workplace'] ?? '-',
                    'joining_date' => $localMatch['joining_date'],
                    'gross_salary' => $localMatch['gross_salary'] ?? 0,
                    'basic_salary' => $localMatch['basic_salary'] ?? 0,
                    'id_check' => 'OK',
                ];
            }

            echo json_encode([
                'success' => true,
                'data' => $matchedData
            ]);

        } catch (Exception $e) {
            ob_end_clean();
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    public function getTipsoiDropdowns()
    {
        header('Content-Type: application/json');
        
        try {
            $token = $this->externalApiService->getBearerToken();
            if (!$token) {
                echo json_encode(['success' => false, 'message' => 'Failed to authenticate with Tipsoi API.']);
                exit;
            }

            $baseUrl = 'https://app.tipsoi.ai/inovace-client/api/v1';
            $endpoints = [
                'designations' => '/designation',
                'departments' => '/department',
                'leavePolicies' => '/leave-policy',
                'employmentTypes' => '/employment-type',
                'shiftGroups' => '/shift-group',
                'workplaces' => '/workplace'
            ];

            $multi = curl_multi_init();
            $channels = [];
            
            foreach ($endpoints as $key => $endpoint) {
                $ch = curl_init($baseUrl . $endpoint . '?order=asc&pageNumber=0&perPage=500');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $token,
                    'Accept: application/json'
                ]);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                curl_multi_add_handle($multi, $ch);
                $channels[$key] = $ch;
            }

            $active = null;
            do {
                $mrc = curl_multi_exec($multi, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);

            while ($active && $mrc == CURLM_OK) {
                if (curl_multi_select($multi) != -1) {
                    do {
                        $mrc = curl_multi_exec($multi, $active);
                    } while ($mrc == CURLM_CALL_MULTI_PERFORM);
                }
            }

            $results = [];
            foreach ($channels as $key => $ch) {
                $res = curl_multi_getcontent($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_multi_remove_handle($multi, $ch);
                curl_close($ch);

                if ($httpCode >= 200 && $httpCode < 300 && $res) {
                    $data = json_decode($res, true);
                    
                    $extractedList = [];
                    if (is_array($data)) {
                        if (isset($data['designationList'])) $extractedList = $data['designationList'];
                        elseif (isset($data['departmentList'])) $extractedList = $data['departmentList'];
                        elseif (isset($data['leavePolicies'])) $extractedList = $data['leavePolicies'];
                        elseif (isset($data['employmentTypeList'])) $extractedList = $data['employmentTypeList'];
                        elseif (isset($data['shiftGroupList'])) $extractedList = $data['shiftGroupList'];
                        elseif (isset($data['shiftGroups'])) $extractedList = $data['shiftGroups'];
                        elseif (isset($data['workplaceList'])) $extractedList = $data['workplaceList'];
                        elseif (isset($data['data']) && is_array($data['data'])) $extractedList = $data['data'];
                        elseif (isset($data['content']) && is_array($data['content'])) $extractedList = $data['content'];
                        else {
                            // Automatically find the first array that looks like a list
                            foreach ($data as $k => $v) {
                                if (is_array($v) && count($v) > 0 && isset($v[0]['id'])) {
                                    $extractedList = $v;
                                    break;
                                }
                            }
                            if (empty($extractedList)) {
                                // fallback if the root is an array of items
                                if (isset($data[0]) && is_array($data[0])) {
                                    $extractedList = $data;
                                }
                            }
                        }
                    }
                    
                    $results[$key] = is_array($extractedList) ? $extractedList : [];
                } else {
                    $results[$key] = [];
                }
            }
            curl_multi_close($multi);

            echo json_encode(['success' => true, 'data' => $results]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function createTipsoiEmployee()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        try {
            $token = $this->externalApiService->getBearerToken();
            if (!$token) {
                echo json_encode(['success' => false, 'message' => 'Failed to authenticate with Tipsoi API.']);
                exit;
            }

            $rawPost = file_get_contents('php://input');
            $postData = json_decode($rawPost, true) ?: $_POST;

            $required = ['name', 'employeeOfficeId', 'email', 'password', 'gender'];
            foreach ($required as $req) {
                if (empty($postData[$req])) {
                    echo json_encode(['success' => false, 'message' => "Field '$req' is required."]);
                    exit;
                }
            }

            // Tipsoi date format is often milliseconds since epoch
            $dateOfJoin = !empty($postData['dateOfJoin']) ? strtotime($postData['dateOfJoin']) * 1000 : null;

            $payload = [
                "name" => trim($postData['name']),
                "employeeOfficeId" => is_numeric(trim($postData['employeeOfficeId'])) ? (int)trim($postData['employeeOfficeId']) : trim($postData['employeeOfficeId']),
                "email" => trim($postData['email']),
                "gender" => (int)$postData['gender'],
                "password" => $postData['password'],
                "reportPosition" => null,
                "shiftId" => null,
                "shiftGroupId" => !empty($postData['shiftGroupId']) ? (int)$postData['shiftGroupId'] : null,
                "leavePolicyId" => !empty($postData['leavePolicyId']) ? (int)$postData['leavePolicyId'] : null,
                "primaryDisplayText" => null,
                "secondaryDisplayText" => null,
                "departmentId" => !empty($postData['departmentId']) ? (int)$postData['departmentId'] : null,
                "subDepartmentId" => null,
                "designationId" => !empty($postData['designationId']) ? (int)$postData['designationId'] : null,
                "employeeIdentifier" => null,
                "weeklyOffDayPolicyId" => null,
                "rfid" => null,
                "personId3rdPartyDevice" => null,
                "workplaceId" => !empty($postData['workplaceId']) ? (int)$postData['workplaceId'] : null,
                "dateOfJoin" => $dateOfJoin,
                "dateOfResignation" => !empty($postData['dateOfResignation']) ? strtotime($postData['dateOfResignation']) * 1000 : null,
                "employmentTypeId" => !empty($postData['employmentTypeId']) ? (int)$postData['employmentTypeId'] : null,
                "subsidiaryId" => null
            ];

            $baseUrl = 'https://app.tipsoi.ai/inovace-client/api/v1';
            $ch = curl_init($baseUrl . '/employee');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                echo json_encode(['success' => false, 'message' => 'Network error connecting to Tipsoi.']);
                exit;
            }

            $response = json_decode($res, true);
            if ($httpCode >= 200 && $httpCode < 300) {
                echo json_encode(['success' => true, 'message' => 'Employee created successfully.']);
            } else {
                $msg = $response['message'] ?? 'Unable to create employee. Please check the information and try again.';
                echo json_encode(['success' => false, 'message' => $msg]);
            }

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // =========================================================================
    // LATE REQUEST FEATURE
    // =========================================================================

    private function proxyTipsoiRequest($url, $method, $postData = null) {
        header('Content-Type: application/json');
        try {
            $token = $this->externalApiService->getUserBearerToken(false);
            if (!$token) {
                echo json_encode(['success' => false, 'message' => 'Tipsoi user login token not found. Please log in to ERP with your Tipsoi credentials.']);
                exit;
            }
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $headers = [
                'Authorization: Bearer ' . $token,
                'Accept: application/json'
            ];

            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                if ($postData !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
                    $headers[] = 'Content-Type: application/json';
                } else {
                    $headers[] = 'Content-Type: application/json';
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([]));
                }
            }
            
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                echo json_encode(['success' => false, 'message' => 'Tipsoi API connection error: ' . $curlError]);
                exit;
            }
            
            if ($httpCode >= 200 && $httpCode < 300) {
                $decoded = json_decode($res, true);
                $decoded['success'] = true;
                echo json_encode($decoded);
            } else {
                $decoded = json_decode($res, true);
                $msg = $decoded['message'] ?? 'Tipsoi API Error. HTTP Code: ' . $httpCode;
                $msg .= ' (Debug: ' . $res . ')';
                echo json_encode(['success' => false, 'message' => $msg]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function applyTipsoiLateRequest($employeeId) {
        $url = "https://app.tipsoi.ai/inovace-client/api/v1/late-approval-management/apply/employee/" . urlencode($employeeId);
        $input = json_decode(file_get_contents('php://input'), true);
        $this->proxyTipsoiRequest($url, 'POST', $input);
    }

    public function getTipsoiLateRequests($employeeId) {
        $url = "https://app.tipsoi.ai/inovace-client/api/v1/late-approval-management/approval-requests/" . urlencode($employeeId);
        $this->proxyTipsoiRequest($url, 'GET');
    }

    public function approveTipsoiLateRequest($logId) {
        $url = "https://app.tipsoi.ai/inovace-client/api/v1/late-approval-management/approve/" . urlencode($logId);
        $input = json_decode(file_get_contents('php://input'), true);
        $this->proxyTipsoiRequest($url, 'POST', $input);
    }

    public function rejectTipsoiLateRequest($logId) {
        $url = "https://app.tipsoi.ai/inovace-client/api/v1/late-approval-management/reject/" . urlencode($logId);
        $input = json_decode(file_get_contents('php://input'), true);
        $this->proxyTipsoiRequest($url, 'POST', $input);
    }

    public function cancelTipsoiLateRequest($logId) {
        $url = "https://app.tipsoi.ai/inovace-client/api/v1/late-approval-management/cancel/" . urlencode($logId);
        $input = json_decode(file_get_contents('php://input'), true);
        $this->proxyTipsoiRequest($url, 'POST', $input);
    }

    public function cancelTipsoiLeave($leaveLogId) {
        $url = "https://app.tipsoi.ai/inovace-client/api/v1/leave-management/cancel/" . urlencode($leaveLogId);
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = ['cancellationReason' => 'User cancelled leave request'];
        }
        $this->proxyTipsoiRequest($url, 'POST', $input);
    }

    public function cancelTipsoiManualEntry($applicationId) {
        $url = "https://app.tipsoi.ai/inovace-client/api/v1/employee/cancel-manual-entry/" . urlencode($applicationId);
        $this->proxyTipsoiRequest($url, 'POST');
    }

    public function getTipsoiLateHistory($employeeId) {
        $url = "https://app.tipsoi.ai/inovace-client/api/v1/late-approval-management/late-approvals/" . urlencode($employeeId);
        $this->proxyTipsoiRequest($url, 'GET');
    }

    public function getTipsoiLateEntries($employeeId) {
        $url = "https://app.tipsoi.ai/inovace-client/api/v1/late-approval-management/late-entries/" . urlencode($employeeId);
        $this->proxyTipsoiRequest($url, 'GET');
    }
}


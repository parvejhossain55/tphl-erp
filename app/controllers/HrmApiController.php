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
     * GET /api/hrm/attendance
     * Fetch attendance logs from Tipsoi and match with local ERP employees
     */
    public function getAttendanceReport()
    {
        header('Content-Type: application/json');

        // Store previous timezone to restore it later
        $oldTimezone = date_default_timezone_get();
        date_default_timezone_set('Asia/Dhaka');

        // Extract dates (expecting Y-m-d format, e.g. 2026-07-06)
        $fromDateStr = $_GET['from_date'] ?? date('Y-m-d'); // default today
        $toDateStr = $_GET['to_date'] ?? date('Y-m-d');

        // Convert to millisecond timestamps for Tipsoi API (Tipsoi expects 'from' and 'to' in milliseconds)
        // The 'from' should start at 00:00:00 of from_date, and 'to' should end at 23:59:59 of to_date
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

            // Execute request
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);

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

            if ($httpCode !== 200 || !isset($data['attendance'])) {
                echo json_encode([
                    'success' => false,
                    'message' => $data['message'] ?? 'Failed to retrieve attendance logs from Tipsoi API.'
                ]);
                exit;
            }

            // Fetch local employees details for name/email/ID mapping and joining_date fallback
            $localQuery = $this->db->query("SELECT id, employee_id, email, full_name, joining_date, payroll_project_id, department_id, designation_id FROM employees WHERE deleted_at IS NULL");
            $localEmployees = [];
            $localIds = [];
            $localEmails = [];
            
            // For joining with departments and designations in JS (if needed, though workspace uses PHP)
            // Actually let's just get department and designation names too so JS can render them nicely
            $localQuery = $this->db->query("SELECT e.id, e.employee_id, e.email, e.full_name, e.joining_date, d.name as department_name, des.name as designation_name 
                                            FROM employees e 
                                            LEFT JOIN departments d ON e.department_id = d.id 
                                            LEFT JOIN designations des ON e.designation_id = des.id
                                            WHERE e.employee_status = 'active'");

            if ($localQuery) {
                while ($row = $localQuery->fetch_assoc()) {
                    $pid = $row['id'];
                    $empId = trim($row['employee_id'] ?? '');
                    $empEmail = trim($row['email'] ?? '');
                    $empName = trim($row['full_name'] ?? '');
                    $empJoin = trim($row['joining_date'] ?? '');

                    $localEmployees[] = [
                        'id' => $pid,
                        'employee_id' => $empId,
                        'email' => strtolower($empEmail),
                        'full_name' => $empName,
                        'joining_date' => $empJoin,
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

            echo json_encode([
                'success' => true,
                'data' => $data['attendance'],
                'dates' => $data['dates'] ?? [],
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
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);

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
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
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
                $queryString = "page=0&count=100&status=0&status=1&status=2";
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
            $token = $this->externalApiService->getBearerToken();
            if (!$token) {
                echo json_encode(['success' => false, 'message' => 'Failed to authenticate with Tipsoi API.']);
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
    public function applyTipsoiLeave($employeeId)
    {
        header('Content-Type: application/json');
        try {
            $token = $this->externalApiService->getBearerToken();
            if (!$token) {
                echo json_encode(['success' => false, 'message' => 'Failed to authenticate with Tipsoi API.']);
                exit;
            }
            
            $url = "https://app.tipsoi.ai/inovace-client/api/v1/leave-management/apply/employee/" . urlencode($employeeId);
            
            $postData = [];
            
            if (isset($_POST['postBody'])) {
                if (class_exists('CURLStringFile')) {
                    $postData['postBody'] = new CURLStringFile($_POST['postBody'], 'postBody', 'application/json');
                } else {
                    $postData['postBody'] = $_POST['postBody'];
                }
            }
            
            // If there's an attachment, add it using CURLFile
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $postData['file'] = new CURLFile(
                    $_FILES['file']['tmp_name'], 
                    $_FILES['file']['type'], 
                    $_FILES['file']['name']
                );
            }
            
            // Tipsoi strictly requires multipart/form-data for this endpoint, even without a file
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData); // passing an array forces multipart/form-data
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'Accept: application/json'
            ]);
            
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode >= 400) {
                http_response_code($httpCode);
            }
            echo $res;
            
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
            $token = $this->externalApiService->getBearerToken();
            if (!$token) {
                echo json_encode(['success' => false, 'message' => 'Failed to authenticate with Tipsoi API.']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $endpoint = $input['endpoint'] ?? '';
            $payload = $input['payload'] ?? [];
            $method = $input['method'] ?? 'POST';

            if (empty($endpoint)) {
                echo json_encode(['success' => false, 'message' => 'Endpoint is required.']);
                exit;
            }

            $url = "https://app.tipsoi.ai/inovace-client/api/v1" . $endpoint;

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            if (strtoupper($method) === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                if (!empty($payload)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                }
            } elseif (strtoupper($method) === 'PUT') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                if (!empty($payload)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                }
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
                echo json_encode([
                    'success' => false,
                    'message' => $tipsoiData['message'] ?? 'Action failed.',
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
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);

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

            // Fetch Logged-in user information for approver matching
            $loggedUser = null;
            if (!empty($_SESSION['user']['id'])) {
                $loggedUserId = (int) $_SESSION['user']['id'];
                $stmt = $this->db->prepare("SELECT u.id, u.role_id, e.employee_id, e.email, e.full_name FROM users u LEFT JOIN employees e ON u.employee_id = e.id WHERE u.id = ? LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param("i", $loggedUserId);
                    $stmt->execute();
                    $uRes = $stmt->get_result()->fetch_assoc();
                    if ($uRes) {
                        $loggedUser = [
                            'id' => $uRes['id'],
                            'email' => strtolower(trim($uRes['email'] ?? '')),
                            'employee_id' => trim($uRes['employee_id'] ?? ''),
                            'full_name' => trim($uRes['full_name'] ?? ''),
                            'tipsoi_id' => $_SESSION['user']['tipsoi_id'] ?? null,
                            'tipsoi_office_id' => $_SESSION['user']['tipsoi_office_id'] ?? null,
                            'role_id' => (int) ($uRes['role_id'] ?? 0),
                            'is_hr' => hasPermission("HRM Management") || in_array((int) ($uRes['role_id'] ?? 0), [1, 3, 4])
                        ];
                    }
                }
            }

            $empId = $loggedUser['tipsoi_id'] ?? 298780; // Main admin/approver ID on Tipsoi
            $officeId = 4699;

            $pendingRequests = [];

            // 1. Fetch Global Pending Leaves
            $ch = curl_init("https://app.tipsoi.ai/inovace-client/api/v1/leave-management/leave-history?page=0&count=2000&status=0");
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
                    'firstApproverId' => $log['firstApproverId'] ?? null,
                    'secondApproverName' => $log['secondApproverName'] ?? '',
                    'secondApprovalStatus' => $log['secondApprovalStatus'] ?? 0,
                    'secondApproverId' => $log['secondApproverId'] ?? null,
                    'status' => 0
                ];
            }

            // 3. Fetch Approver's Pending Late Excuses
            $ch = curl_init("https://app.tipsoi.ai/inovace-client/api/v1/late-approval-management/late-approvals/$empId");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $res = curl_exec($ch);
            curl_close($ch);
            $d = json_decode($res, true);
            $lateApprovals = $d['lateLogList'] ?? [];
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
                    'firstApproverId' => $log['firstLateApproverId'] ?? $log['firstApproverId'] ?? null,
                    'secondApproverName' => $log['secondLateApproverName'] ?? '',
                    'secondApprovalStatus' => $log['secondLateApprovalStatus'] ?? 0,
                    'secondApproverId' => $log['secondLateApproverId'] ?? $log['secondApproverId'] ?? null,
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
                    'firstApproverId' => $entry['firstManualEntryApproverId'] ?? null,
                    'secondApproverName' => $entry['secondManualApproverName'] ?? '',
                    'secondApprovalStatus' => $entry['secondManualEntryApprovalStatus'] ?? 0,
                    'secondApproverId' => $entry['secondManualEntryApproverId'] ?? null,
                    'status' => 0
                ];
            }



            echo json_encode([
                'success' => true,
                'data' => array_values($pendingRequests),
                'local_employees' => $localEmployees,
                'local_employee_ids' => $localIds,
                'local_employee_emails' => $localEmails,
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
     * Send email reminders to approvers for their pending Tipsoi requests
     */
    public function sendTipsoiReminders()
    {
        header('Content-Type: application/json');

        try {
            $token = $this->externalApiService->getBearerToken();
            if (!$token) {
                echo json_encode(['success' => false, 'message' => 'Failed to authenticate with Tipsoi API.']);
                exit;
            }

            // Fetch local employees emails
            $localQuery = $this->db->query("SELECT email, full_name FROM employees WHERE email IS NOT NULL AND email != ''");
            $emailMap = [];
            if ($localQuery) {
                while ($row = $localQuery->fetch_assoc()) {
                    $nameKey = strtolower(trim($row['full_name']));
                    if ($nameKey !== '') {
                        $emailMap[$nameKey] = trim($row['email']);
                    }
                }
            }

            $empId = 298780; 
            $officeId = 4699;

            $pendingRequests = [];

            // Helper to fetch data
            $fetchData = function($url) use ($token) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                $res = curl_exec($ch);
                curl_close($ch);
                return json_decode($res, true);
            };

            // 1. Leaves
            $d = $fetchData("https://app.tipsoi.ai/inovace-client/api/v1/leave-management/leave-history?page=0&count=2000&status=0");
            $globalLeaves = $d['leaveLogList'] ?? [];
            $d = $fetchData("https://app.tipsoi.ai/inovace-client/api/v1/remaining-leave/approvers/$empId");
            $approverLeaves = $d['leaveApplications'] ?? [];
            $allLeaves = array_merge($globalLeaves, $approverLeaves);

            foreach ($allLeaves as $log) {
                $id = $log['id'] ?? $log['leaveLogId'] ?? null;
                if (!$id) continue;
                $pendingRequests["leave_$id"] = [
                    'employeeName' => $log['employeeName'] ?? '—',
                    'requestType' => 'Leave Request',
                    'startDate' => isset($log['startDate']) ? date('Y-m-d', (int)($log['startDate'] / 1000)) : '—',
                    'firstApproverName' => trim($log['firstApproverName'] ?? ''),
                    'firstApprovalStatus' => $log['firstApprovalStatus'] ?? 0,
                    'secondApproverName' => trim($log['secondApproverName'] ?? ''),
                    'secondApprovalStatus' => $log['secondApprovalStatus'] ?? 0,
                ];
            }

            // 2. Late Excuses
            $d = $fetchData("https://app.tipsoi.ai/inovace-client/api/v1/late-approval-management/late-approvals/$empId");
            $lateApprovals = $d['lateLogList'] ?? [];
            foreach ($lateApprovals as $log) {
                $id = $log['lateApprovalLogId'] ?? $log['id'] ?? null;
                if (!$id) continue;
                $pendingRequests["late_$id"] = [
                    'employeeName' => $log['employeeName'] ?? '—',
                    'requestType' => 'Late Excuse',
                    'startDate' => isset($log['logDate']) ? date('Y-m-d', (int)($log['logDate'] / 1000)) : '—',
                    'firstApproverName' => trim($log['firstLateApproverName'] ?? ''),
                    'firstApprovalStatus' => $log['firstLateApprovalStatus'] ?? 0,
                    'secondApproverName' => trim($log['secondLateApproverName'] ?? ''),
                    'secondApprovalStatus' => $log['secondLateApprovalStatus'] ?? 0,
                ];
            }

            // 3. Manual Entries
            $d = $fetchData("https://app.tipsoi.ai/inovace-client/api/v1/employee/profile/manual/approver/");
            $manualApprover = $d['manualEntryApplications'] ?? [];
            $d = $fetchData("https://app.tipsoi.ai/inovace-client/api/v1/manual-entry?officeId=$officeId&order=asc&pageNumber=0&perPage=200&sortKey=reportPosition&manualEntryType=1&applicationStatus=0");
            $globalCorrection = $d['manualEntries'] ?? [];
            $d = $fetchData("https://app.tipsoi.ai/inovace-client/api/v1/manual-entry?officeId=$officeId&order=asc&pageNumber=0&perPage=200&sortKey=reportPosition&manualEntryType=2&applicationStatus=0");
            $globalLate = $d['manualEntries'] ?? [];
            $d = $fetchData("https://app.tipsoi.ai/inovace-client/api/v1/manual-entry?officeId=$officeId&order=asc&pageNumber=0&perPage=200&sortKey=reportPosition&manualEntryType=3&applicationStatus=0");
            $globalField = $d['manualEntries'] ?? [];

            $allManual = array_merge($manualApprover, $globalCorrection, $globalLate, $globalField);
            foreach ($allManual as $entry) {
                $id = $entry['manualEntryId'] ?? $entry['id'] ?? null;
                if (!$id) continue;
                $typeVal = $entry['manualEntryType'] ?? 1;
                $reqType = ($typeVal == 1) ? 'Manual Entry' : (($typeVal == 2) ? 'Late Excuse' : 'Field Visit');
                $startDateVal = '—';
                if (!empty($entry['startDate'])) $startDateVal = date('Y-m-d', (int)($entry['startDate'] / 1000));
                elseif (!empty($entry['entryTime'])) $startDateVal = date('Y-m-d', (int)($entry['entryTime'] / 1000));
                elseif (!empty($entry['exitTime'])) $startDateVal = date('Y-m-d', (int)($entry['exitTime'] / 1000));

                $pendingRequests["manual_$id"] = [
                    'employeeName' => $entry['employeeName'] ?? '—',
                    'requestType' => $reqType,
                    'startDate' => $startDateVal,
                    'firstApproverName' => trim($entry['firstManualApproverName'] ?? ''),
                    'firstApprovalStatus' => $entry['firstManualEntryApprovalStatus'] ?? 0,
                    'secondApproverName' => trim($entry['secondManualApproverName'] ?? ''),
                    'secondApprovalStatus' => $entry['secondManualEntryApprovalStatus'] ?? 0,
                ];
            }

            // Group by Pending Approver
            $approverQueues = [];
            foreach ($pendingRequests as $req) {
                $pendingFor = null;
                if ($req['firstApprovalStatus'] === 0 && !empty($req['firstApproverName'])) {
                    $pendingFor = $req['firstApproverName'];
                } elseif ($req['firstApprovalStatus'] !== 0 && $req['secondApprovalStatus'] === 0 && !empty($req['secondApproverName'])) {
                    $pendingFor = $req['secondApproverName'];
                }

                if ($pendingFor) {
                    if (!isset($approverQueues[$pendingFor])) {
                        $approverQueues[$pendingFor] = [];
                    }
                    $approverQueues[$pendingFor][] = $req;
                }
            }

            require_once __DIR__ . '/../core/MailServiceForPHPMailer.php';
            $mailService = new \MailService();
            $stmt = $this->db->prepare("INSERT INTO tipsoi_email_logs (sent_at, approver_name, approver_email, pending_level, request_details, status, error_message) VALUES (NOW(), ?, ?, ?, ?, ?, ?)");

            $results = [];

            foreach ($approverQueues as $approverName => $requests) {
                $nameKey = strtolower($approverName);
                if (!isset($emailMap[$nameKey])) {
                    $results[] = [
                        'approver' => $approverName,
                        'status' => 'Failed',
                        'error' => 'Email not found in employees table.'
                    ];
                    $details = count($requests) . " pending requests.";
                    $status = 'Failed';
                    $errorMsg = 'Email not found.';
                    $emailAddr = '';
                    $lvl = 'Multiple';
                    $stmt->bind_param("ssssss", $approverName, $emailAddr, $lvl, $details, $status, $errorMsg);
                    $stmt->execute();
                    continue;
                }

                $email = $emailMap[$nameKey];
                $reqCount = count($requests);
                
                // Build HTML Table for the email
                $html = "<p>Dear <strong>{$approverName}</strong>,</p>";
                $html .= "<p>You have <strong>{$reqCount}</strong> pending request(s) on Tipsoi waiting for your approval. Please log in to the HR module or Tipsoi portal to review them.</p>";
                $html .= "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%; max-width: 600px;'>";
                $html .= "<tr style='background-color: #f2f2f2;'><th>Employee Name</th><th>Request Type</th><th>Date</th></tr>";
                foreach ($requests as $r) {
                    $html .= "<tr>";
                    $html .= "<td>{$r['employeeName']}</td>";
                    $html .= "<td>{$r['requestType']}</td>";
                    $html .= "<td>{$r['startDate']}</td>";
                    $html .= "</tr>";
                }
                $html .= "</table>";
                $html .= "<br><p>Thank you,<br>HR & Admin Dept.<br>The Premium Homes Ltd.</p>";

                try {
                    $sent = $mailService->sendGenericMail(
                        $email,
                        "Pending Tipsoi Approvals ($reqCount)",
                        $html
                    );

                    $details = "$reqCount pending requests sent.";
                    $status = $sent ? 'Success' : 'Failed';
                    $errorMsg = $sent ? '' : 'Failed to send email via MailService.';
                    $lvl = 'Multiple';

                    $stmt->bind_param("ssssss", $approverName, $email, $lvl, $details, $status, $errorMsg);
                    $stmt->execute();

                    $results[] = [
                        'approver' => $approverName,
                        'email' => $email,
                        'status' => $status
                    ];
                } catch (\Exception $e) {
                    $status = 'Failed';
                    $errorMsg = substr($e->getMessage(), 0, 255);
                    $details = "$reqCount pending requests.";
                    $lvl = 'Multiple';
                    $stmt->bind_param("ssssss", $approverName, $email, $lvl, $details, $status, $errorMsg);
                    $stmt->execute();
                    $results[] = [
                        'approver' => $approverName,
                        'status' => 'Failed',
                        'error' => $errorMsg
                    ];
                }
            }

            $stmt->close();

            echo json_encode([
                'success' => true,
                'message' => 'Reminders processed.',
                'data' => $results
            ]);

        } catch (Exception $e) {
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
            $token = $this->externalApiService->getBearerToken();
            if (!$token) {
                echo json_encode(['success' => false, 'message' => 'Failed to authenticate with Tipsoi API.']);
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

    public function getTipsoiLateHistory($employeeId) {
        $url = "https://app.tipsoi.ai/inovace-client/api/v1/late-approval-management/late-approvals/" . urlencode($employeeId);
        $this->proxyTipsoiRequest($url, 'GET');
    }
}


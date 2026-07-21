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
        
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 15;
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
                'total' => (int)$total,
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
                        $normOfficeId = preg_match('/^\d+$/', $officeId) ? (string)intval($officeId) : $officeId;
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
            $normalizeIdPhp = function($id) {
                $str = trim((string)$id);
                return preg_match('/^\d+$/', $str) ? (string)intval($str) : $str;
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

            $empId = 298780; // Main admin/approver ID on Tipsoi
            $officeId = 4699;

            $pendingRequests = [];

            // 1. Fetch Global Pending Leaves
            $ch = curl_init("https://app.tipsoi.ai/inovace-client/api/v1/leave-management/leave-history?page=0&count=100&status=0");
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
                if (!$id) continue;
                
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
                    'startDate' => isset($log['startDate']) ? date('Y-m-d', (int)($log['startDate'] / 1000)) : '—',
                    'endDate' => isset($log['endDate']) ? date('Y-m-d', (int)($log['endDate'] / 1000)) : '—',
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
                if (!$id) continue;
                
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
                    $logDateVal = date('Y-m-d', (int)($log['date'] / 1000));
                } elseif (isset($log['entryTime']) && is_numeric($log['entryTime'])) {
                    $logDateVal = date('Y-m-d', (int)($log['entryTime'] / 1000));
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
                if (!$id) continue;
                
                $typeVal = $entry['manualEntryType'] ?? 1;
                $reqType = ($typeVal == 1) ? 'Manual Entry' : (($typeVal == 2) ? 'Late Excuse' : 'Field Visit');
                
                $entryTimeVal = $entry['entryTime'] ?? null;
                $exitTimeVal = $entry['exitTime'] ?? null;

                $entryTimeStr = ($entryTimeVal && is_numeric($entryTimeVal)) ? date('H:i:s', (int)($entryTimeVal / 1000)) : '—';
                $exitTimeStr = ($exitTimeVal && is_numeric($exitTimeVal)) ? date('H:i:s', (int)($exitTimeVal / 1000)) : '—';

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
                    $startDateVal = date('Y-m-d', (int)($entry['startDate'] / 1000));
                } elseif ($entryTimeVal) {
                    $startDateVal = date('Y-m-d', (int)($entryTimeVal / 1000));
                } elseif ($exitTimeVal) {
                    $startDateVal = date('Y-m-d', (int)($exitTimeVal / 1000));
                }

                $endDateVal = '—';
                if (!empty($entry['endDate'])) {
                    $endDateVal = date('Y-m-d', (int)($entry['endDate'] / 1000));
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

            // Fetch Logged-in user information for approver matching
            $loggedUser = null;
            if (!empty($_SESSION['user']['id'])) {
                $loggedUserId = (int)$_SESSION['user']['id'];
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
                            'role_id' => (int)($uRes['role_id'] ?? 0),
                            'is_hr' => hasPermission("HRM Management") || in_array((int)($uRes['role_id'] ?? 0), [1, 3, 4])
                        ];
                    }
                }
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
                $dateStr = $hDateVal ? date('Y-m-d', (int)($hDateVal / 1000)) : null;
                if (!$dateStr) continue;

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
}

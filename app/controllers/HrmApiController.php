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

        // Extract dates (expecting Y-m-d format, e.g. 2026-07-06)
        $fromDateStr = $_GET['from_date'] ?? date('Y-m-d', strtotime('-6 days')); // default last 7 days
        $toDateStr = $_GET['to_date'] ?? date('Y-m-d');

        // Convert to millisecond timestamps for Tipsoi API (Tipsoi expects 'from' and 'to' in milliseconds)
        // The 'from' should start at 00:00:00 of from_date, and 'to' should end at 23:59:59 of to_date
        $fromTime = strtotime($fromDateStr . ' 00:00:00') * 1000;
        $toTime = strtotime($toDateStr . ' 23:59:59') * 1000;

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

            // Fetch local employee IDs and emails for matching
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

            echo json_encode([
                'success' => true,
                'data' => $data['attendance'],
                'dates' => $data['dates'] ?? [],
                'local_employee_ids' => $localIds,
                'local_employee_emails' => $localEmails,
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
}

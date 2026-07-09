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
}

<?php
class CedReportController extends Controller
{
    protected $db;
    protected $followUpModel;
    protected $userModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->followUpModel = new FollowUp($db);
        $this->userModel = new User($db);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Permission check
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $isManagement = in_array($roleId, [1, 3, 33]); // Including Chief Officer
        
        if (!hasPermission("CED Manage") && !$isManagement && $roleId !== 7) {
            header("Location: " . $GLOBALS['base_url'] . "/error-403");
            exit;
        }
    }

    public function index()
    {
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $isManagement = in_array($roleId, [1, 3, 33]);

        // Default Agent ID (Self if CED role, otherwise null)
        $defaultAgentId = ($roleId === 7) ? $userId : null;
        
        $agentId = isset($_GET['agent_id']) && $_GET['agent_id'] !== '' ? (int)$_GET['agent_id'] : $defaultAgentId;

        // Report Mode Logic
        $reportMode = $_GET['report_mode'] ?? 'custom';
        $reportMonth = $_GET['report_month'] ?? date('m');
        $reportYear = $_GET['report_year'] ?? date('Y');

        if ($reportMode === 'daily') {
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d');
        } elseif ($reportMode === 'monthly') {
            // first day of selected month/year
            $startDate = date('Y-m-01', strtotime("$reportYear-$reportMonth-01"));
            $endDate = date('Y-m-t', strtotime("$reportYear-$reportMonth-01"));
        } elseif ($reportMode === 'yearly') {
            $startDate = date('Y-01-01', strtotime("$reportYear-01-01"));
            $endDate = date('Y-12-31', strtotime("$reportYear-01-01"));
        } else {
            // custom
            $defaultStartDate = date('Y-m-01');
            $defaultEndDate = date('Y-m-t');
            $startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : $defaultStartDate;
            $endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : $defaultEndDate;
        }

        // Fetch Data
        $cedEmployees = $this->userModel->getCedEmployees();
        $reportData = $this->followUpModel->getCedActivityReport($agentId, $startDate, $endDate);

        $summary = $reportData['summary'];
        $details = $reportData['details'];

        // Generate Breakdown
        $breakdownData = [];
        if ($reportMode === 'monthly') {
            // Daily breakdown for the month
            $daysInMonth = date('t', strtotime($startDate));
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $dayStr = sprintf("%02d", $i);
                $breakdownData[$dayStr] = ['label' => "$dayStr " . date('M', strtotime($startDate)), 'count' => 0];
            }
            foreach ($details as $row) {
                $day = date('d', strtotime($row['follow_up_date']));
                if (isset($breakdownData[$day])) {
                    $breakdownData[$day]['count']++;
                }
            }
        } elseif ($reportMode === 'yearly') {
            // Monthly breakdown for the year
            for ($i = 1; $i <= 12; $i++) {
                $monthNum = sprintf("%02d", $i);
                $monthName = date('F', mktime(0, 0, 0, $i, 10));
                $breakdownData[$monthNum] = ['label' => $monthName, 'count' => 0];
            }
            foreach ($details as $row) {
                $month = date('m', strtotime($row['follow_up_date']));
                if (isset($breakdownData[$month])) {
                    $breakdownData[$month]['count']++;
                }
            }
        }

        $agentInfo = null;
        if ($agentId) {
            $agentInfo = $this->db->query("
                SELECT 
                    e.full_name, 
                    e.employee_id, 
                    e.office_branch,
                    dep.name as department_name,
                    des.name as designation_name
                FROM employees e
                LEFT JOIN departments dep ON e.department_id = dep.id
                LEFT JOIN designations des ON e.designation_id = des.id
                WHERE e.user_id = $agentId
            ")->fetch_assoc();
        }

        $this->view("ced/ced-report", [
            "current_route" => "ced-report",
            "page_title" => "CED Activity Report",
            "isManagement" => $isManagement,
            "cedEmployees" => $cedEmployees,
            "agentId" => $agentId,
            "startDate" => $startDate,
            "endDate" => $endDate,
            "summary" => $summary,
            "details" => $details,
            "agentInfo" => $agentInfo,
            "reportMode" => $reportMode,
            "reportMonth" => $reportMonth,
            "reportYear" => $reportYear,
            "breakdownData" => $breakdownData
        ]);
    }
}

<?php

class DayOffController extends Controller
{
    protected $db;
    protected $employeeModel;
    protected $dayOffModel;

    public function __construct($db)
    {
        $this->db = $db;
        require_once dirname(__DIR__) . '/models/Employee.php';
        require_once dirname(__DIR__) . '/models/EmpWeeklyDayOff.php';
        $this->employeeModel = new Employee($this->db);
        $this->dayOffModel = new EmpWeeklyDayOff($this->db);
    }

    public function dayOffManage()
    {
        // Fetch all employees to populate the dropdown
        $employees = $this->employeeModel->getAll();
        
        // Fetch employees who have days off assigned for the table
        $employee_days_off = $this->dayOffModel->getEmployeeOffDaysList();

        return $this->view(
            'hrm/employee-day-off/day-off',
            [
                "current_route" => "day-off",
                "employees" => $employees,
                "employee_days_off" => $employee_days_off
            ]
        );
    }

    public function getEmployeeDaysOff($employeeId)
    {
        // For AJAX requests to populate the checkboxes
        // Clear any possible previous output that breaks JSON
        ob_start();
        $daysOff = $this->dayOffModel->getDaysOffByEmployeeId($employeeId);
        ob_clean(); // Clean the output buffer
        
        header('Content-Type: application/json');
        
        if (!$employeeId) {
            echo json_encode(['status' => 'error', 'message' => 'Employee ID is required']);
            exit;
        }

        echo json_encode([
            'status' => 'success',
            'days_off' => $daysOff
        ]);
        exit;
    }

    public function updateDayOff()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $employee_id = $_POST['employee_id'] ?? null;
            $days = $_POST['days_off'] ?? []; // Expected to be an array from checkboxes

            if (!$employee_id) {
                $_SESSION['error'] = "Please select an employee.";
                header("Location: {$GLOBALS['base_url']}/day-off");
                exit;
            }

            // Update in model
            $success = $this->dayOffModel->updateEmployeeDaysOff($employee_id, $days);

            if ($success) {
                $_SESSION['success'] = "Days off updated successfully for the selected employee.";
            } else {
                $_SESSION['error'] = "Failed to update days off.";
            }

            header("Location: {$GLOBALS['base_url']}/day-off");
            exit;
        }
    }

    public function hrmDayOffLists()
    {
        // 1. Fetch all external employees from Tipsoi API
        $externalApiService = new ExternalApiService($this->db);
        $result = $externalApiService->getExternalEmployees(1, 2000);
        
        $tipsoiEmployees = [];
        $fromMock = false;
        $apiError = '';

        if ($result && isset($result['success']) && $result['success']) {
            $tipsoiEmployees = $result['data'] ?? [];
            $fromMock = $result['from_mock'] ?? false;
            $apiError = $result['error_message'] ?? '';
        } else {
            $apiError = $result['message'] ?? 'Unable to connect to Tipsoi API.';
        }

        // 2. Fetch local day-off mapping
        $dayOffMap = [];
        $dayOffQuery = $this->db->query("
            SELECT e.employee_id, e.email, GROUP_CONCAT(w.day_off_name SEPARATOR ', ') AS days_off
            FROM employees e
            JOIN emp_weekly_day_off w ON e.id = w.employee_id
            GROUP BY e.id
        ");
        if ($dayOffQuery) {
            while ($row = $dayOffQuery->fetch_assoc()) {
                $empId = trim($row['employee_id'] ?? '');
                $email = strtolower(trim($row['email'] ?? ''));
                $daysOff = $row['days_off'] ?? '';
                if ($empId !== '') {
                    $dayOffMap[strval($empId)] = $daysOff;
                }
                if ($email !== '') {
                    $dayOffMap[$email] = $daysOff;
                }
            }
        }

        // Helper to normalize IDs (remove leading zeros for numeric IDs)
        $normalizeIdPhp = function($id) {
            $str = trim(strval($id));
            return preg_match('/^\d+$/', $str) ? strval(intval($str)) : $str;
        };

        // 3. Process Tipsoi employees and attach their day offs
        $employee_days_off = [];
        foreach ($tipsoiEmployees as $emp) {
            $officeId = $emp['employeeOfficeId'] ?? $emp['employee_id'] ?? '';
            $email = strtolower(trim($emp['email'] ?? ''));
            $shiftGroup = trim($emp['shiftGroupName'] ?? '');
            
            $daysOff = '';
            
            // A. Check if any week day names are explicitly mentioned in shiftGroupName
            $daysFound = [];
            $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            foreach ($weekdays as $day) {
                if (stripos($shiftGroup, $day) !== false) {
                    $daysFound[] = ucfirst($day);
                }
            }

            if (!empty($daysFound)) {
                $daysOff = implode(', ', $daysFound);
            } 
            // B. If shift group is "Regular", default to "Sunday" (verified from attendance API behavior)
            elseif (strcasecmp($shiftGroup, 'Regular') === 0) {
                $daysOff = 'Sunday';
            } 
            // C. Fallback to local day-off mapping
            else {
                $normOfficeId = $normalizeIdPhp($officeId);
                if ($normOfficeId !== '' && isset($dayOffMap[$normOfficeId])) {
                    $daysOff = $dayOffMap[$normOfficeId];
                } elseif ($email !== '' && isset($dayOffMap[$email])) {
                    $daysOff = $dayOffMap[$email];
                } else {
                    $daysOff = $shiftGroup !== '' ? $shiftGroup : 'None';
                }
            }

            $employee_days_off[] = [
                'employee_id' => $officeId,
                'full_name' => $emp['name'] ?? $emp['full_name'] ?? 'N/A',
                'designation_name' => $emp['designationName'] ?? $emp['designation_name'] ?? 'N/A',
                'department_name' => $emp['departmentName'] ?? $emp['department_name'] ?? 'N/A',
                'days_off' => $daysOff
            ];
        }

        // Fallback to local DB if Tipsoi list is completely empty
        if (empty($employee_days_off)) {
            $employee_days_off = $this->dayOffModel->getEmployeeOffDaysList();
        }

        return $this->view(
            'hrm/employee-day-off/day-off-lists',
            [
                "current_route" => "hrm-day-off-lists",
                "employee_days_off" => $employee_days_off,
                "from_mock" => $fromMock,
                "api_error" => $apiError
            ]
        );
    }
}

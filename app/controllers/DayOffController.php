<?php

class DayOffController extends Controller
{
    protected $db;
    protected $employeeModel;
    protected $dayOffModel;

    public function __construct($db)
    {
        $this->db = $db;
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
}

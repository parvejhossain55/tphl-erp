<?php

class EmployeeTaskController extends Controller
{
    protected $db;
    protected $taskModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->taskModel = new EmployeeTask($this->db);
    }

    protected function getLoggedInEmployee()
    {
        if (empty($_SESSION['user']['id'])) {
            return null;
        }

        $userId = (int)$_SESSION['user']['id'];
        $empDbId = !empty($_SESSION['user']['employee_db_id']) ? (int)$_SESSION['user']['employee_db_id'] : 0;
        $empCode = !empty($_SESSION['user']['employee_id']) ? (string)$_SESSION['user']['employee_id'] : '';

        // Query employees table by user_id, id, or employee_id
        $stmt = $this->db->prepare("
            SELECT id, employee_id, user_id, full_name, department_id, designation_id 
            FROM employees 
            WHERE user_id = ? 
               OR (id > 0 AND id = ?) 
               OR (employee_id != '' AND (employee_id = ? OR employee_id = ?))
            ORDER BY (user_id = ?) DESC, (id = ?) DESC
            LIMIT 1
        ");
        if ($stmt) {
            $stmt->bind_param("iisssi", $userId, $empDbId, $empCode, $empDbId, $userId, $empDbId);
            $stmt->execute();
            $employee = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($employee) {
                $_SESSION['user']['employee_db_id'] = (int)$employee['id'];
                return $employee;
            }
        }

        return null;
    }

    public function index()
    {
        $employee = $this->getLoggedInEmployee();

        $employeeIds = [];
        if ($employee) {
            $employeeIds[] = (int)$employee['id'];
            if (!empty($employee['employee_id'])) {
                $employeeIds[] = $employee['employee_id'];
            }
        }
        if (!empty($_SESSION['user']['employee_db_id'])) {
            $employeeIds[] = (int)$_SESSION['user']['employee_db_id'];
        }
        if (!empty($_SESSION['user']['employee_id'])) {
            $employeeIds[] = $_SESSION['user']['employee_id'];
        }
        $employeeIds = array_values(array_unique(array_filter($employeeIds)));

        $currentMonth = !empty($_GET['month']) ? trim($_GET['month']) : date('Y-m');

        // fetch tasks for this employee for the month
        $taskSheets = !empty($employeeIds) ? $this->taskModel->getTasksByEmployeeAndMonth($employeeIds, $currentMonth) : [];

        // summary
        $totalTasks = count($taskSheets);
        $totalHours = array_sum(array_column($taskSheets, 'hours_spent'));

        return $this->view('employee_tasks/task-sheets', [
            'current_route' => 'task-sheets',
            'taskSheets' => $taskSheets,
            'currentMonth' => $currentMonth,
            'totalTasks' => $totalTasks,
            'totalHours' => $totalHours,
            'employee' => $employee
        ]);
    }

    public function save()
    {
        $employee = $this->getLoggedInEmployee();
        $employee_id = $employee ? (int)$employee['id'] : (!empty($_SESSION['user']['employee_db_id']) ? (int)$_SESSION['user']['employee_db_id'] : (int)($_SESSION['user']['employee_id'] ?? 0)); 

        $data = [
            'employee_id' => $employee_id,
            'work_date' => $_POST['work_date'],
            'task_description' => $_POST['task_description'],
            'hours_spent' => $_POST['hours_spent'],
            'status' => $_POST['status'],
            'remarks' => $_POST['remarks'] ?? null
        ];

        if (!empty($_POST['id'])) {
            $this->taskModel->updateTask($_POST['id'], $data);
        } else {
            $this->taskModel->createTask($data);
        }

        setSuccess("Task Update Successfuly");
        $redirectMonth = !empty($_POST['work_date']) ? '?month=' . substr($_POST['work_date'], 0, 7) : '';
        header("Location: {$GLOBALS['base_url']}/task-sheets" . $redirectMonth);
        exit;
    }
}

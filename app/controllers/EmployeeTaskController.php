<?php

class EmployeeTaskController extends Controller
{

    protected $db;

    protected $taskModel;

    public function __construct($db)
    {
        $this->taskModel = new EmployeeTask($this->db);
    }

public function index()
{
    $employee_id = $_SESSION['user']['employee_id']; // logged-in employee
    $currentMonth = date('Y-m'); // default current month

    // fetch tasks for this employee for the month
    $taskSheets = $this->taskModel->getTasksByEmployeeAndMonth($employee_id, $currentMonth);

    // summary
    $totalTasks = count($taskSheets);
    $totalHours = array_sum(array_column($taskSheets, 'hours_spent'));

    return $this->view('employee_tasks/task-sheets', [
        'current_route' => 'task-sheets',
        'taskSheets' => $taskSheets,
        'currentMonth' => $currentMonth,
        'totalTasks' => $totalTasks,
        'totalHours' => $totalHours
    ]);
}
public function save()
{
    $employee_id = $_SESSION['user']['employee_id']; 

    $data = [
        'employee_id' => $employee_id,
        'work_date' => $_POST['work_date'],
        'task_description' => $_POST['task_description'],
        'hours_spent' => $_POST['hours_spent'],
        'status' => $_POST['status'],
        'remarks' => $_POST['remarks'] ?? null
    ];

    if(!empty($_POST['id'])) {
        $this->taskModel->updateTask($_POST['id'], $data);
    } else {
        $this->taskModel->createTask($data);
    }

        setSuccess("Task Update Successfuly");
        header("Location: {$GLOBALS['base_url']}/task-sheets");
        exit;
}



}

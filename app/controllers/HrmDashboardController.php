<?php

class HrmDashboardController extends Controller
{
    protected $db;
    protected $employeeModel;
    protected $departmetModel;
    protected $taskModel;
    public function __construct($db)
    {
        $this->db = $db;
        $this->employeeModel = new Employee($this->db);
        $this->departmetModel = new Department($this->db);
        $this->departmetModel = new Designation($this->db);
        $this->taskModel = new Task($this->db);
    }

public function index()
{
    $totalEmployees = $this->employeeModel->getTotalCount();
    $todayTasks      = $this->taskModel->getTaskCount("DATE(start_date) = CURDATE()");
    $pendingTasks    = $this->taskModel->getTaskCount("status = 'pending'");
    $inProgressTasks = $this->taskModel->getTaskCount("status = 'in_progress'");
    $completedTasks  = $this->taskModel->getTaskCount("status = 'completed'");
    $expiredTasks    = $this->taskModel->getTaskCount("status = 'expired'");
    $departmentEmpCount = $this->employeeModel->getDepartmentWiseEmp();
    $departmentCount = $this->departmetModel->getAll();

    $this->view("hrm/hrm-dashboard", [
        "current_route"   => "hrm-dashboard",
        "page_title"      => "HRM Dashboard",
        "departmentCount"=> $departmentCount,

        "todayTasks"      => $todayTasks,
        "pendingTasks"    => $pendingTasks,
        "inProgressTasks" => $inProgressTasks,
        "completedTasks"  => $completedTasks,
        "expiredTasks"    => $expiredTasks,
        "totalEmployees"    => $totalEmployees,
        "departmentEmpCount"    => $departmentEmpCount,
    ]);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

}

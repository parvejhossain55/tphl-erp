<?php

class HrmDashboardController extends Controller
{
    protected $db;
    protected $employeeModel;
    protected $departmetModel;
    public function __construct($db)
    {
        $this->db = $db;
        $this->employeeModel = new Employee($this->db);
        $this->departmetModel = new Department($this->db);
        $this->departmetModel = new Designation($this->db);
    }

    public function index()
    {
        $departmentCount = $this->departmetModel->getAll();
        $this->view("hrm/hrm-dashboard", [
            "current_route" => "hrm-dashboard",
            "page_title" => "HRM dashboard",
            "departmentCount" => $departmentCount,
            
        ]);



        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}

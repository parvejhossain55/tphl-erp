<?php

class PayrollController extends Controller
{
    protected $db;
    protected $employeeModel;
    protected $taskModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->employeeModel = new Employee($this->db);
        $this->taskModel = new Task($this->db);
    }

    public function index()
    {
      

        

        // 4️⃣ Pass all data to view
        $this->view("payrolls/payslips/payslip-lists", [
            "current_route" => "payslip-lists",
            "page_title"    => "Payroll Lists",
           
        ]);
    }
    public function create()
    {
      

        

        // 4️⃣ Pass all data to view
        $this->view("payrolls/payslips/create-payslip", [
            "current_route" => "create-payslip",
            "page_title"    => "Payroll Lists",
           
        ]);
    }
}
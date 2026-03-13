<?php

class HolidayController extends Controller
{
    protected $db;
    protected $employeeModel;
    protected $departmetModel;
    protected $taskModel;
    protected $holidayModel;
    protected $payslipModel;
    protected $approvalModel;
    protected $EmpAttendanceModel;
    protected $leaveModel;
    public function __construct($db)
    {
        $this->db = $db;
        $this->employeeModel = new Employee($this->db);
        $this->departmetModel = new Department($this->db);
        $this->departmetModel = new Designation($this->db);
        $this->EmpAttendanceModel = new EmpAttentendance($this->db);
        $this->taskModel = new Task($this->db);
        $this->holidayModel = new Holiday($this->db);
        $this->approvalModel = new ApprovalRequest($this->db);
        $this->payslipModel = new Payslip($this->db);
        $this->leaveModel = new EmployeeLeave($this->db);
    }



    public function index()
    {
        return $this->view(
            'hrm/holidays/holiday-lists',
            ["current_route" => "holiday-lists"]
        );
    }



    public function approveFull($id) {}

    // Partial approve
    public function approvePartial($id) {}
}

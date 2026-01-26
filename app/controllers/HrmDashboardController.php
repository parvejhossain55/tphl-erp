<?php

class HrmDashboardController extends Controller
{
    protected $db;
    protected $employeeModel;
    protected $departmetModel;
    protected $taskModel;
    protected $payslipModel;
    protected $EmpAttendanceModel;
    public function __construct($db)
    {
        $this->db = $db;
        $this->employeeModel = new Employee($this->db);
        $this->departmetModel = new Department($this->db);
        $this->departmetModel = new Designation($this->db);
        $this->EmpAttendanceModel = new EmpAttentendance($this->db);
        $this->taskModel = new Task($this->db);
        $this->payslipModel = new Payslip($this->db);
    }

    public function index()
    {
        $selectedDate = $_GET['date'] ?? date('Y-m-d');
        $todaySummary = $this->EmpAttendanceModel->getTodayAttendanceSummary();
        $totalEmployees = $this->employeeModel->getTotalCount();
        $presentCount = $todaySummary['Present'];
        $absentCount  = $todaySummary['Absent'];
        $persentPresents = $totalEmployees ? round(($presentCount / $totalEmployees) * 100, 1) : 0;
        $persentAbsents = $totalEmployees ? round(($absentCount / $totalEmployees) * 100, 1) : 0;
        // $halfDayCount = $todaySummary['Half-Day'];
        // $onLeaveCount = $todaySummary['On Leave'];
        // $holidayCount = $todaySummary['Holiday'];
        // $dayOffCount  = $todaySummary['Day Off'];
        // $lateCount    = $todaySummary['Late'];
        // $totalCount   = $todaySummary['Total'];
        $year  = $_GET['year'] ?? date('Y');
        $month = $_GET['month'] ?? date('n');

        // $totalPaidSalary   = $this->salaryModel->getPaidSalarySum($year, $month);
        // $totalUnpaidSalary = $this->salaryModel->getUnpaidSalarySum($year, $month);


        $attendances  = $this->EmpAttendanceModel->getByDate($selectedDate);
        $attendanceChart = [
            'week'  => $this->EmpAttendanceModel->getAttendanceSummaryByPeriod('week'),
            'month' => $this->EmpAttendanceModel->getAttendanceSummaryByPeriod('month'),
            'year'  => $this->EmpAttendanceModel->getAttendanceSummaryByPeriod('year'),
        ];

        $todayTasks      = $this->taskModel->getTaskCount("DATE(start_date) = CURDATE()");
        $pendingTasks    = $this->taskModel->getTaskCount("status = 'pending'");
        $inProgressTasks = $this->taskModel->getTaskCount("status = 'in_progress'");
        $completedTasks  = $this->taskModel->getTaskCount("status = 'completed'");
        $expiredTasks    = $this->taskModel->getTaskCount("status = 'expired'");
        $departmentEmpCount = $this->employeeModel->getDepartmentWiseEmp();
        $departmentCount = $this->departmetModel->getAll();
        //      $totalLeads = $this->leadModel->getAll($period);
        // $totalLeadsCount = count($totalLeads);
        $totalDeparttment = count($departmentCount);
        $totalPaidSalary = $this->payslipModel->getPaidSalarySum($year, $month);

        $totalUnpaidSalary = $this->payslipModel->getUnpaidSalarySum($year, $month);
        // Max employee for progress bar calculation

        $departmentCount2 = count($departmentEmpCount);
        $chartHeight = max(260, $departmentCount2 * 40); // minimum 260px, 1 dept ~ 40px
        // Max employee for progress bar
        $maxEmployee = 0;
        $deptLabels = [];
        $deptSeries = [];

        foreach ($departmentEmpCount as $dept) {
            $maxEmployee = max($maxEmployee, $dept['total_employee']);
            $deptLabels[] = $dept['dept_name'] . " = " . $dept['total_employee'] . " Employees";
            $deptSeries[] = (int)$dept['total_employee'];
        }

        $this->view("hrm/hrm-dashboard", [
            "current_route"   => "hrm-dashboard",
            "page_title"      => "HRM Dashboard",
            "departmentCount" => $departmentCount,

            "todayTasks"      => $todayTasks,
            "pendingTasks"    => $pendingTasks,
            "inProgressTasks" => $inProgressTasks,
            "completedTasks"  => $completedTasks,
            "expiredTasks"    => $expiredTasks,
            "totalEmployees"    => $totalEmployees,
            "departmentEmpCount"    => $departmentEmpCount,
            "totalPaidSalary"    => $totalPaidSalary,
            "totalUnpaidSalary"    => $totalUnpaidSalary,
            "deptLabels"    => $deptLabels,
            "maxEmployee"    => $maxEmployee,
            "deptSeries"    => $deptSeries,
            "chartHeight" => $chartHeight,
            'attendanceChart' => $attendanceChart,
            'attendances' => $attendances,
            'presentCount' => $presentCount,
            'absentCount' => $absentCount,
            'persentPresents' => $persentPresents,
            'persentAbsents' => $persentAbsents,
            'totalDeparttment' => $totalDeparttment,
        ]);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}

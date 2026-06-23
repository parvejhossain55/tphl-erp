<?php

class HrmDashboardController extends Controller
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
        $selectedDate = $_GET['date'] ?? date('Y-m-d');
        $todaySummary = $this->EmpAttendanceModel->getTodayAttendanceSummary();
        $totalEmployees = $this->employeeModel->getTotalCount();
        $presentCount = $todaySummary['Present'];
        $absentCount  = $todaySummary['Absent'];
        $lateCount  = $todaySummary['Late'];
        $persentPresents = $totalEmployees ? round(($presentCount / $totalEmployees) * 100, 1) : 0;
        $persentAbsents = $totalEmployees ? round(($absentCount / $totalEmployees) * 100, 1) : 0;
        $latePercent = $totalEmployees ? round(($lateCount / $totalEmployees) * 100, 1) : 0;
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
        $paidCount = $this->payslipModel->getPaidCount($year, $month);
        $unpaidEmployeeCount = $totalEmployees - $paidCount;
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
        $sessionRole = $_SESSION['user']['role_name'] ?? 'management';
        $roles = $roleMap[strtolower($sessionRole)] ?? [strtolower($sessionRole)];
        $pendingRequests = $this->approvalModel->getPendingForRole($roles, 10);

        $birthdays = $this->employeeModel->birthdayNotification();


        // ================= HOLIDAY CALENDAR LOGIC =================

        // Get month & year
        $month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
        $year  = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

        // Month start & end
        $startOfMonth = "$year-$month-01";
        $endOfMonth   = date("Y-m-t", strtotime($startOfMonth));

        // 🔥 Get ALL holidays (for right list)
        $stmt = $this->db->prepare("
    SELECT * FROM holidays 
    ORDER BY start_date ASC
");
        $stmt->execute();
        $allHolidays = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


        // 🔥 Get ONLY holidays that overlap current month
        $stmt = $this->db->prepare("
    SELECT * FROM holidays 
    WHERE start_date <= ? 
    AND end_date >= ?
    ORDER BY start_date ASC
");
        $stmt->bind_param("ss", $endOfMonth, $startOfMonth);
        $stmt->execute();
        $holidaysResult = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


        //  Map holidays by day (range support)
        $holidays = [];

        foreach ($holidaysResult as $h) {

            $start = strtotime($h['start_date']);
            $end   = strtotime($h['end_date']);

            for ($date = $start; $date <= $end; $date += 86400) {

                $currentDate = date('Y-m-d', $date);

                // only current month
                if (date('m', $date) == $month && date('Y', $date) == $year) {

                    $day = (int)date('d', $date);

                    // support multiple holidays same day
                    $holidays[$day][] = $h['title'];
                }
            }
        }


        // ================= CALENDAR INFO =================

        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $firstDayOfWeek = date('w', strtotime("$year-$month-01"));
        $today = date('Y-m-d');

        // prev/next month
        $prevMonth = $month - 1;
        $prevYear = $year;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear--;
        }

        $nextMonth = $month + 1;
        $nextYear = $year;
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear++;
        }

        // holiday count (month base)
        // $holidayCount = $this->holidayModel->holidayCount($year, $month);
        // year base
        $holidayCount = $this->holidayModel->holidayCount($year);

        // Month name
        $monthName = date('F', strtotime($startOfMonth));

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
            "unpaidEmployeeCount"  => $unpaidEmployeeCount,
            "paidEmployeeCount"    => $paidCount,
            "deptLabels"    => $deptLabels,
            "maxEmployee"    => $maxEmployee,
            "deptSeries"    => $deptSeries,
            "chartHeight" => $chartHeight,
            'attendanceChart' => $attendanceChart,
            'attendances' => $attendances,
            'presentCount' => $presentCount,
            'absentCount' => $absentCount,
            'lateCount' => $lateCount,
            'latePercent' => $latePercent,
            'persentPresents' => $persentPresents,
            'persentAbsents' => $persentAbsents,
            'totalDeparttment' => $totalDeparttment,
            'pendingRequests' => $pendingRequests,
            'birthdays' => $birthdays,
            'daysInMonth' => $daysInMonth,
            'firstDayOfWeek' => $firstDayOfWeek,
            'today' => $today,
            'monthName' => $monthName,
            'year' => $year,
            'month' => $month,
            'holidaysResult' => $allHolidays,
            'holidays' => $holidays,
            'holidayCount' => $holidayCount,

        ]);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }



    public function approveFull($id)
    {
        $this->db->begin_transaction();

        try {
            $request = $this->approvalModel->find($id);
            $approved_days = null;
            $approved_amount = $_POST['approved_amount'] ?? null;

            if ($request['source_table'] === 'employee_leaves') {
                $leave = $this->leaveModel->find($request['source_id']);
                if ($leave && $leave['leave_type_id']) {
                    $start = new DateTime($leave['start_date']);
                    $end   = new DateTime($leave['end_date']);
                    $approved_days = $start->diff($end)->days + 1;
                    $this->deductLeaveBalance($leave, $approved_days);
                }
            } else {
                // Full approval fallback
                if ($approved_amount === null || $approved_amount <= 0) {
                    $approved_amount = $request['request_amount']; // use requested amount
                }
            }

            $this->approvalModel->updateStatus($id, 'approved', $approved_amount, $approved_days);

            $this->db->commit();
            header("Location: " . $GLOBALS['base_url'] . "/hrm-dashboard");
            exit;
        } catch (Exception $e) {
            $this->db->rollback();
            die($e->getMessage());
        }
    }

    // Partial approve
    public function approvePartial($id)
    {
        $this->db->begin_transaction();

        try {
            $request = $this->approvalModel->find($id);

            $approved_amount = $_POST['approved_amount'] ?? null;
            $approved_days   = $_POST['approved_days'] ?? null;
            $approved_days   = $_POST['approved_days'] ?? null;
            $approvalNote   = $_POST['approval_note'] ?? null;

            //  Only require approved_days for leave requests
            if ($request['source_table'] === 'employee_leaves') {
                $approved_days = (int)$approved_days;

                if ($approved_days <= 0) {
                    throw new Exception('Approved days required');
                }

                $leave = $this->leaveModel->find($request['source_id']);

                if ($leave && $leave['leave_type_id']) {
                    $start = new DateTime($leave['start_date']);
                    $end   = new DateTime($leave['end_date']);
                    $total_days = $start->diff($end)->days + 1;

                    if ($approved_days > $total_days) {
                        throw new Exception('Approved days exceed requested days');
                    }

                    $this->deductLeaveBalance($leave, $approved_days);
                }
            } else {
                //  amount based partial approval
                if ($approved_amount === null || $approved_amount <= 0) {
                    throw new Exception('Approved amount required');
                }

                $approved_days = null; // important
            }

            $this->approvalModel->updateStatus(
                $id,
                'partial',
                $approved_amount,
                $approved_days,
                $approvalNote
            );

            $this->db->commit();
            header("Location: " . $GLOBALS['base_url'] . "/hrm-dashboard");
            exit;
        } catch (Exception $e) {
            $this->db->rollback();
            die($e->getMessage());
        }
    }
    private function deductLeaveBalance(array $leave, int $approved_days)
    {
        // 1 Lock row
        $stmt = $this->db->prepare("
        SELECT id, remaining_days
        FROM employee_leave_balances
        WHERE employee_id = ? AND leave_type_id = ?
        FOR UPDATE
    ");
        $stmt->bind_param("ii", $leave['employee_id'], $leave['leave_type_id']);
        $stmt->execute();
        $balance = $stmt->get_result()->fetch_assoc();

        // 2 Insert if missing
        if (!$balance) {
            $initial_days = 20;

            $stmt = $this->db->prepare("
            INSERT INTO employee_leave_balances
            (employee_id, leave_type_id, remaining_days)
            VALUES (?, ?, ?)
        ");
            $stmt->bind_param(
                "iii",
                $leave['employee_id'],
                $leave['leave_type_id'],
                $initial_days
            );
            $stmt->execute();

            $remaining_days = $initial_days;
        } else {
            $remaining_days = (int)$balance['remaining_days'];
        }

        // 3 Validate balance
        if ($remaining_days < $approved_days) {
            throw new Exception('Insufficient leave balance');
        }

        // 4 Deduct
        $stmt = $this->db->prepare("
        UPDATE employee_leave_balances
        SET remaining_days = remaining_days - ?
        WHERE employee_id = ? AND leave_type_id = ?
    ");
        $stmt->bind_param(
            "iii",
            $approved_days,
            $leave['employee_id'],
            $leave['leave_type_id']
        );
        $stmt->execute();
    }
}

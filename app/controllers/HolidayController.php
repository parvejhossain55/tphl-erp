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
        $holidays = $this->holidayModel->getHoliday();

        // Stat calculations
        $totalYearHoliday = count($holidays);

        $month = date('m');
        $thisMonthHoliday = 0;
        $weeklyHolidayCount = 0;
        $upcomingHolidayCount = 0;

        $today = date('Y-m-d');
        $next30Days = date('Y-m-d', strtotime('+30 days'));

        foreach ($holidays as $h) {
            $start = $h['start_date'];
            $end   = $h['end_date'];

            // this month holiday
            if (date('m', strtotime($start)) == $month || date('m', strtotime($end)) == $month) {
                $thisMonthHoliday++;
            }

            // weekly holiday (Friday)
            $s = strtotime($start);
            $e = strtotime($end);
            for ($d = $s; $d <= $e; $d += 86400) {
                if (date('w', $d) == 5) $weeklyHolidayCount++; // Friday
            }

            // upcoming 30 days
            if ($end >= $today && $start <= $next30Days) {
                $upcomingHolidayCount++;
            }
        }

        return $this->view(
            'hrm/holidays/holiday-lists',
            [
                "current_route" => "holiday-lists",
                "holidays" => $holidays,
                "totalYearHoliday" => $totalYearHoliday,
                "thisMonthHoliday" => $thisMonthHoliday,
                "weeklyHolidayCount" => $weeklyHolidayCount,
                "upcomingHolidayCount" => $upcomingHolidayCount
            ]
        );
    }
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $start = $_POST['start_date'];
            $end   = $_POST['end_date'];
            $title = trim($_POST['title']);

            // validation
            if (empty($start) || empty($end) || empty($title)) {
                setError("All required fields must be filled!");
                header("Location: " . $_SERVER['HTTP_REFERER']);
                exit;
            }

            // যদি end < start হয় → auto fix
            if ($end < $start) {
                $end = $start;
            }

            $data = [
                'title' => $title,
                'start_date' => $start,
                'end_date' => $end
            ];

            if ($this->holidayModel->create($data)) {
                setSuccess("Holiday created successfully!");
            } else {
                setError("Something went wrong!");
            }

            header("Location: {$GLOBALS['base_url']}/holiday-lists");
            exit;
        }
    }
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $id    = $_POST['id'] ?? null;
            $start = $_POST['start_date'] ?? null;
            $end   = $_POST['end_date'] ?? null;
            $title = trim($_POST['title'] ?? '');

            // validation
            if (!$id || empty($start) || empty($end) || empty($title)) {
                setError("All required fields must be filled!");
                header("Location: " . $_SERVER['HTTP_REFERER']);
                exit;
            }

            // end < start fix
            if ($end < $start) $end = $start;

            $data = [
                'title' => $title,
                'start_date' => $start,
                'end_date' => $end
            ];

            if ($this->holidayModel->update($id, $data)) {
                setSuccess("Holiday updated successfully!");
            } else {
                setError("Something went wrong!");
            }

            header("Location: {$GLOBALS['base_url']}/holiday-lists");
            exit;
        }
    }


    public function delete()
    {
        $id = $_POST['id'] ?? 0;

        if ($id && $this->holidayModel->delete($id)) {
            setSuccess("Holiday deleted successfully!");
            header("Location: {$GLOBALS['base_url']}/holiday-lists");
            exit;
        } else {
            setError("Failed To Delete Holidays!");
            header("Location: {$GLOBALS['base_url']}/holiday-lists");
            exit;
        }
    }
}

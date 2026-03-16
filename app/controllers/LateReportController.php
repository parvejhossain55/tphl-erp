<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LateReportController extends Controller
{
    protected $db;

    protected $EmpAttendanceModel;
    protected $employeeModel;
    public function __construct($db)
    {
        $this->db = $db;
        $this->EmpAttendanceModel = new EmpAttentendance($this->db);
        $this->employeeModel = new Employee($this->db);
    }
    public function lateReport()
    {
        if (!isset($_SESSION['user']['id'])) {
            setError("Unauthorized");
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        // Get selected date, default to today
        $date = $_GET['date'] ?? date('Y-m-d');
        $employeeCount = $this->employeeModel->getTotalCount();
        // Fetch department-wise daily report
        $reportData = $this->EmpAttendanceModel->getDailyLateReport($date);
 


        // Pass date to view as $reportDate
        return $this->view('hrm/reports/daily-late-report', [
            'current_route' => 'daily-late-report',
            'page_title' => 'Daily Late report',
            'reportData' => $reportData,
           
        ]);
    }

 
}

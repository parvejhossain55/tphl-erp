<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class EmpAttendanceController extends Controller
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
    public function index()
    {
        // Get filter params
       
        $selectedMonth = $_GET['month'] ?? date('m');
        $employeeId    = !empty($_GET['employee_id']) ? (int) $_GET['employee_id'] : null;
        $totalEmployee = $this->employeeModel->getTotalCount();
        $todaySummary = $this->EmpAttendanceModel->getTodaySummary();
        // Fetch filtered data
    $selectedDate = $_GET['date'] ?? date('Y-m-d');

    $attendances  = $this->EmpAttendanceModel->getByDate($selectedDate);
        $employees   = $this->employeeModel->getAll();

        // Pass to view
        $this->view("hrm/attendances/attendance-lists", [
            "current_route"       => "attendance-list",
            "page_title"          => "Attendance List",
            "employees"           => $employees,
            "attendances"         => $attendances,
            "selectedDate"        => $selectedDate,
            "selectedMonth"       => $selectedMonth,
            "selectedEmployeeId"  => $employeeId,
            "totalEmployee"  => $totalEmployee,
            'todaySummary' => $todaySummary,
        ]);
    }
    public function downloadExcel($month = null)
    {
        // Get month from GET or default current month
        $selectedYear  = $_GET['year'] ?? date('Y');
        $selectedMonth = $_GET['month'] ?? date('m');

        // Ensure month is 2-digit format
        $month = sprintf('%04d-%02d', $selectedYear, $selectedMonth);

        $data = $this->EmpAttendanceModel->getAllForReport($month);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $sheet->setCellValue('A1', 'Employee Name');
        $sheet->setCellValue('B1', 'Date');
        $sheet->setCellValue('C1', 'Punch In');
        $sheet->setCellValue('D1', 'Punch Out');
        $sheet->setCellValue('E1', 'Status');

        $row = 2;
        foreach ($data as $item) {
            $sheet->setCellValue("A$row", $item['employee_name']);
            $sheet->setCellValue("B$row", $item['attendance_date']);
            $sheet->setCellValue("C$row", $item['punch_in_time'] != '0000-00-00 00:00:00' ? date('H:i', strtotime($item['punch_in_time'])) : '-');
            $sheet->setCellValue("D$row", $item['punch_out_time'] != '0000-00-00 00:00:00' ? date('H:i', strtotime($item['punch_out_time'])) : '-');
            $sheet->setCellValue("E$row", $item['today_status']);
            $row++;
        }

        $filename = "Attendance_Report_{$month}.xlsx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function downloadPDF($month = null)
    {
        $selectedYear  = $_GET['year'] ?? date('Y');
        $selectedMonth = $_GET['month'] ?? date('m');

        $month = sprintf('%04d-%02d', $selectedYear, $selectedMonth);

        $data = $this->EmpAttendanceModel->getAllForReport($month);

        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);

        $html = '<h2>Attendance Report - ' . date('F Y', strtotime($month . '-01')) . '</h2>';
        $html .= '<table border="1" cellpadding="4">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Employee Name</th>
                        <th>Date</th>
                        <th>Punch In</th>
                        <th>Punch Out</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>';

        $serial = 1;
        foreach ($data as $item) {
            $html .= '<tr>';
            $html .= '<td>' . $serial++ . '</td>';
            $html .= '<td>' . $item['employee_name'] . '</td>';
            $html .= '<td>' . $item['attendance_date'] . '</td>';
            $html .= '<td>' . ($item['punch_in_time'] != '0000-00-00 00:00:00' ? date('H:i', strtotime($item['punch_in_time'])) : '-') . '</td>';
            $html .= '<td>' . ($item['punch_out_time'] != '0000-00-00 00:00:00' ? date('H:i', strtotime($item['punch_out_time'])) : '-') . '</td>';
            $html .= '<td>' . $item['today_status'] . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('Attendance_Report_' . $month . '.pdf', 'D');
        exit;
    }

    public function create()
    {
        $employees = $this->employeeModel->getAll();
        //         $flatCounts = $this->flatModel->getFlatStatusCount();
        $this->view("hrm/attendances/create-attendance", [
            "current_route"   => "create-attendance",
            "active"          => "create-attendance",
            "page_title"      => "Add Attendance",
            "show_breadcrumb" => true,
            "employees"       => $employees,

        ]);
    }


    // Handle form submission
    public function store()
    {
        $allowedStatus = [
            'Present',
            'Absent',
            'Late',
            'Half-Day',
            'On Leave',
            'Holiday',
            'Day Off'
        ];

        // Required validation
        if (
            empty($_POST['employee_id']) ||
            empty($_POST['attendance_date']) ||
            empty($_POST['today_status'])
        ) {
            setError('Required fields missing');
            header("Location: {$GLOBALS['base_url']}/create-attendance");
            exit;
        }

        // Status validation
        if (!in_array($_POST['today_status'], $allowedStatus)) {
            setError('Invalid attendance status');
            header("Location: {$GLOBALS['base_url']}/create-attendance");
            exit;
        }
        $punchIn = null;
        if (!empty($_POST['punch_in_time'])) {
            $punchIn = $_POST['attendance_date'] . ' ' . $_POST['punch_in_time'] . ':00';
        }

        $punchOut = null;
        if (!empty($_POST['punch_out_time'])) {
            $punchOut = $_POST['attendance_date'] . ' ' . $_POST['punch_out_time'] . ':00';
        }


        $data = [
            'employee_id'      => (int) $_POST['employee_id'],
            'attendance_date'  => $_POST['attendance_date'],
            'today_status'     => $_POST['today_status'],

            'punch_in_time'   => $punchIn,
            'punch_out_time'  => $punchOut,
            'created_by'       => $_SESSION['user']['id'] ?? null,
        ];

        if ($this->EmpAttendanceModel->create($data)) {
            setSuccess("Attendance created successfully.");
            header("Location: {$GLOBALS['base_url']}/attendance-list");
            exit;
        } else {
            setError("Failed to create attendance.");
            header("Location: {$GLOBALS['base_url']}/create-attendance");
            exit;
        }
    }



    public function edit($id)
    {
        $employees = $this->employeeModel->getAll();
        $attendance = $this->EmpAttendanceModel->find($id); // single record

        if (!$attendance) {
            setError("Attendance not found.");
            header("Location: {$GLOBALS['base_url']}/attendance-list");
            exit;
        }

        $this->view("hrm/attendances/edit-attendance", [
            "current_route"   => "edit-attendance",
            "active"          => "edit-attendance",
            "page_title"      => "Edit Attendance",
            "show_breadcrumb" => true,
            "employees"       => $employees,
            "attendance"      => $attendance, // single record
        ]);
    }

    public function update($id)
    {
        $allowedStatus = [
            'Present',
            'Absent',
            'Late',
            'Half-Day',
            'On Leave',
            'Holiday',
            'Day Off'
        ];

        // Required validation
        if (
            empty($_POST['employee_id']) ||
            empty($_POST['attendance_date']) ||
            empty($_POST['today_status'])
        ) {
            setError('Required fields missing');
            header("Location: {$GLOBALS['base_url']}/edit-attendance/$id");
            exit;
        }

        // Status validation
        if (!in_array($_POST['today_status'], $allowedStatus)) {
            setError('Invalid attendance status');
            header("Location: {$GLOBALS['base_url']}/edit-attendance/$id");
            exit;
        }

        $punchIn = !empty($_POST['punch_in_time'])
            ? $_POST['attendance_date'] . ' ' . $_POST['punch_in_time'] . ':00'
            : null;

        $punchOut = !empty($_POST['punch_out_time'])
            ? $_POST['attendance_date'] . ' ' . $_POST['punch_out_time'] . ':00'
            : null;

        $data = [
            'employee_id'     => (int) $_POST['employee_id'],
            'attendance_date' => $_POST['attendance_date'],
            'today_status'    => $_POST['today_status'],
            'punch_in_time'   => $punchIn,
            'punch_out_time'  => $punchOut,
            'created_by'      => $_SESSION['user']['id'] ?? null
        ];

        if ($this->EmpAttendanceModel->update($id, $data)) {
            setSuccess("Attendance updated successfully.");
            header("Location: {$GLOBALS['base_url']}/attendance-list");
            exit;
        } else {
            setError("Failed to update attendance.");
            header("Location: {$GLOBALS['base_url']}/edit-attendance/$id");
            exit;
        }
    }


    public function createMobilePuch(){
  
           if (!isset($_SESSION['user']['employee_id'])) {
            setError("Unauthorized");
            header("Location: {$GLOBALS['base_url']}/admin-login");
            exit;
        }


        $employee_id = (int)$_SESSION['user']['employee_id'];

        if ($employee_id <= 0) {
            setError("Employee ID not found");
            header("Location: {$GLOBALS['base_url']}/admin-login");
            exit;
        }

    

        $this->view("hrm/mobile-punch/mobile-punch", [
            "current_route" => 'mobile-punch',
            "page_title"    => "Punch Add"
        ]);
    }







    public function show($employeeId)
    {
        // Default: current month
        $selectedMonth = $_GET['month'] ?? date('Y-m');

        // Fetch attendance data
        $attendanceData = $this->EmpAttendanceModel->getByEmployeeAndMonth($employeeId, $selectedMonth);

        $this->view("hrm/attendances/show-attendance", [
            "current_route" => "show-attendance",
            "active" => "show-attendance",
            "page_title" => "Show Attendance",
            "show_breadcrumb" => true,
            "attendanceData" => $attendanceData,
            "employeeId" => $employeeId,
            "selectedMonth" => $selectedMonth
        ]);
    }

    public function delete()
    {
        $id = $_POST['id'] ?? 0;

        if ($id && $this->EmpAttendanceModel->delete($id)) {
            setSuccess("Attendance deleted successfully.");
            header("Location: {$GLOBALS['base_url']}/attendance-list");
            exit;
        } else {
            setError("Failed to delete attendance.");
            header("Location: {$GLOBALS['base_url']}/attendance-list");
            exit;
        }
    }
}

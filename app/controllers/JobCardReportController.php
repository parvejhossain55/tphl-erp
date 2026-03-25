<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class JobCardReportController extends Controller
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
        if (!isset($_SESSION['user']['id'])) {
            setError("Unauthorized");
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        // Filters
        $employee_id = $_GET['employee_id'] ?? null;
        $month = $_GET['month'] ?? date('Y-m');

        $employees = $this->employeeModel->getAll();
        
        $reportData = [];
        if ($employee_id && $month) {
            $reportData = $this->EmpAttendanceModel->getByEmployeeAndMonth($employee_id, $month);
        }

        return $this->view('hrm/reports/job-card-report', [
            'current_route' => 'job-card-report',
            'page_title' => 'Attendance (Job Card)',
            'employees' => $employees,
            'reportData' => $reportData,
            'selectedMonth' => $month,
            'selectedEmployeeId' => $employee_id,
        ]);
    }

    // Export CSV
    public function exportCSV()
    {
        if (!isset($_SESSION['user']['id'])) {
            setError("Unauthorized");
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        $employee_id = $_GET['employee_id'] ?? null;
        $month = $_GET['month'] ?? date('Y-m');

        if (!$employee_id || !$month) {
            setError("Please select an employee and month first.");
            header("Location: {$GLOBALS['base_url']}/job-card-report");
            exit;
        }

        $employee = $this->employeeModel->find($employee_id);
        $reportData = $this->EmpAttendanceModel->getByEmployeeAndMonth($employee_id, $month);

        $empName = $employee['full_name'] ?? 'Unknown';
        $filenameDate = date('F_Y', strtotime($month . '-01'));

        // Excel headers
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="Job_Card_'.$empName.'_' . $filenameDate . '.csv"');

        // UTF-8 BOM for Excel
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');

        // Headers
        fputcsv($output, ['Attendance (Job Card) for ' . $empName . ' - ' . date('F Y', strtotime($month . '-01'))]);
        fputcsv($output, []);
        fputcsv($output, ['Date', 'Punch In', 'Punch Out', 'Status']);

        foreach ($reportData as $row) {
            $punchIn = (!empty($row['punch_in_time']) && $row['punch_in_time'] != '0000-00-00 00:00:00') ? date('h:i A', strtotime($row['punch_in_time'])) : '--:--';
            $punchOut = (!empty($row['punch_out_time']) && $row['punch_out_time'] != '0000-00-00 00:00:00') ? date('h:i A', strtotime($row['punch_out_time'])) : '--:--';

            fputcsv($output, [
                date('d M Y', strtotime($row['attendance_date'])),
                $punchIn,
                $punchOut,
                $row['today_status']
            ]);
        }

        fclose($output);
        exit;
    }

    public function exportPDF()
    {
        if (!isset($_SESSION['user']['id'])) {
            setError("Unauthorized");
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        $employee_id = $_GET['employee_id'] ?? null;
        $month = $_GET['month'] ?? date('Y-m');

        if (!$employee_id || !$month) {
            setError("Please select an employee and month first.");
            header("Location: {$GLOBALS['base_url']}/job-card-report");
            exit;
        }

        $employee = $this->employeeModel->find($employee_id);
        $reportData = $this->EmpAttendanceModel->getByEmployeeAndMonth($employee_id, $month);
        $empName = $employee['full_name'] ?? 'Unknown';

        // Create new PDF document - Portrait
        $pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('HRM System');
        $pdf->SetTitle("Job Card - $empName - $month");
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage();

        // ── COLORS ──────────────────────────────────────────────
        $darkGreen   = array(0,  100,  0);    
        $lightGreen  = array(144, 238, 144);  
        $white       = array(255, 255, 255);  
        $headerText  = array(255, 255, 255);  
        $bodyText    = array(0,   0,   0);    

        // ── COMPANY TITLE ────────────────────────────────────────
        $pdf->SetFillColor($darkGreen[0], $darkGreen[1], $darkGreen[2]);
        $pdf->SetTextColor($headerText[0], $headerText[1], $headerText[2]);
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->Cell(0, 12, 'The Premium Homes Ltd.', 0, 1, 'C', true);

        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 7, 'Attendance (Job Card)', 0, 1, 'C', true);
        
        $pdf->Ln(2);
        
        $pdf->SetFillColor($white[0], $white[1], $white[2]);
        $pdf->SetTextColor($bodyText[0], $bodyText[1], $bodyText[2]);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, 'Employee: ' . $empName, 0, 1, 'L', true);
        $pdf->Cell(0, 6, 'Month: ' . date('F Y', strtotime($month . '-01')), 0, 1, 'L', true);

        $pdf->Ln(3);

        // ── TABLE HEADER ─────────────────────────────────────────
        $pdf->SetFillColor($darkGreen[0], $darkGreen[1], $darkGreen[2]);
        $pdf->SetTextColor($headerText[0], $headerText[1], $headerText[2]);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->SetLineWidth(0.3);

        $colWidths = [45, 45, 45, 45]; 
        $headers   = ['Date', 'Punch In', 'Punch Out', 'Status'];

        foreach ($headers as $i => $h) {
            $pdf->Cell($colWidths[$i], 10, $h, 1, 0, 'C', true);
        }
        $pdf->Ln();

        // ── TABLE ROWS ────────────────────────────────────────────
        $pdf->SetFont('helvetica', '', 9);
        $rowIndex = 0;

        foreach ($reportData as $row) {
            $rowIndex % 2 === 0
                ? $pdf->SetFillColor($lightGreen[0], $lightGreen[1], $lightGreen[2])
                : $pdf->SetFillColor($white[0], $white[1], $white[2]);

            $pdf->SetTextColor($bodyText[0], $bodyText[1], $bodyText[2]);

            // Date
            $pdf->Cell($colWidths[0], 8, date('d M Y', strtotime($row['attendance_date'])), 1, 0, 'C', true);

            // Punch In & Out
            $punchIn = (!empty($row['punch_in_time']) && $row['punch_in_time'] != '0000-00-00 00:00:00') ? date('h:i A', strtotime($row['punch_in_time'])) : '--:--';
            $punchOut = (!empty($row['punch_out_time']) && $row['punch_out_time'] != '0000-00-00 00:00:00') ? date('h:i A', strtotime($row['punch_out_time'])) : '--:--';
            
            $pdf->Cell($colWidths[1], 8, $punchIn, 1, 0, 'C', true);
            $pdf->Cell($colWidths[2], 8, $punchOut, 1, 0, 'C', true);

            // Status 
            if ($row['today_status'] === 'Absent') {
                $pdf->SetTextColor(220, 20, 60); // Red
            } elseif ($row['today_status'] === 'Late') {
                $pdf->SetTextColor(255, 165, 0); // Orange
            } elseif ($row['today_status'] === 'Present') {
                $pdf->SetTextColor(0, 128, 0); // Green
            }
            $pdf->Cell($colWidths[3], 8, $row['today_status'], 1, 0, 'C', true);
            $pdf->SetTextColor($bodyText[0], $bodyText[1], $bodyText[2]);

            $pdf->Ln();
            $rowIndex++;
        }

        $filename = "Job_Card_" . str_replace(' ', '_', $empName) . "_" . $month . ".pdf";
        $pdf->Output($filename, 'I');
    }
}

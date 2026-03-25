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

    // Export CSV
    public function exportLateReport()
    {
        if (!isset($_SESSION['user']['id'])) {
            setError("Unauthorized");
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        $date = $_GET['date'] ?? date('Y-m-d');
        $reportData = $this->EmpAttendanceModel->getDailyLateReport($date);

        // Excel headers
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="late_report_' . $date . '.csv"');

        // UTF-8 BOM for Excel
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');

        // Column headers
        fputcsv($output, ['Employee Name', 'Department', 'Designation', 'Workplace', 'Punch In Time', 'Status']);

        foreach ($reportData as $row) {
            fputcsv($output, [
                $row['emp_name'],
                $row['dept_name'],
                $row['designation_name'],
                $row['workplace'],
                $row['punch_in_time'],
                $row['today_status']
            ]);
        }

        fclose($output);
        exit;
    }

    public function exportLateReportPDF()
    {
        if (!isset($_SESSION['user']['id'])) {
            setError("Unauthorized");
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        $date = $_GET['date'] ?? date('Y-m-d');
        $formattedDate = date('d-m-Y', strtotime($date));

        // Get report data
        $reportData = $this->EmpAttendanceModel->getDailyLateReport($date);

        // Create new PDF document - Portrait
        $pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('HRM System');
        $pdf->SetTitle("Late Report - $date");
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
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
        $pdf->Cell(0, 7, 'Daily Late Report', 0, 1, 'C', true);
        $pdf->Cell(0, 7, 'Date: ' . $formattedDate, 0, 1, 'C', true);

        $pdf->Ln(3);

        // ── TABLE HEADER ─────────────────────────────────────────
        $pdf->SetFillColor($darkGreen[0], $darkGreen[1], $darkGreen[2]);
        $pdf->SetTextColor($headerText[0], $headerText[1], $headerText[2]);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->SetLineWidth(0.3);

        $colWidths = [50, 40, 40, 30, 30]; 
        $headers   = ['Employee Name', 'Department', 'Designation', 'Punch In', 'Status'];

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

            $pdf->Cell($colWidths[0], 8, $row['emp_name'], 1, 0, 'L', true);
            $pdf->Cell($colWidths[1], 8, $row['dept_name'], 1, 0, 'C', true);
            $pdf->Cell($colWidths[2], 8, $row['designation_name'], 1, 0, 'C', true);
            
            // Format punch in 
            $punchIn = $row['punch_in_time'] ? date('H:i', strtotime($row['punch_in_time'])) : '--';
            $pdf->Cell($colWidths[3], 8, $punchIn, 1, 0, 'C', true);

            // Status 
            $pdf->SetTextColor(220, 20, 60); // Red text since it's a late report
            $pdf->Cell($colWidths[4], 8, $row['today_status'], 1, 0, 'C', true);
            $pdf->SetTextColor($bodyText[0], $bodyText[1], $bodyText[2]);

            $pdf->Ln();
            $rowIndex++;
        }

        $pdf->Output("late_report_$date.pdf", 'I');
    }
}


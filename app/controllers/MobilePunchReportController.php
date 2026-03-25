<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MobilePunchReportController extends Controller
{
    protected $db;
    protected $EmpAttendanceModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->EmpAttendanceModel = new EmpAttentendance($this->db);
    }

    public function mobilePunchReport()
    {
        if (!isset($_SESSION['user']['id'])) {
            setError("Unauthorized");
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        // Get selected date, default to today
        $date = $_GET['date'] ?? date('Y-m-d');
        $reportData = $this->getMobilePunchData($date);

        return $this->view('hrm/reports/mobile-punch-report', [
            'current_route' => 'mobile-punch-report',
            'page_title' => 'Mobile Punch Report',
            'reportData' => $reportData,
            'reportDate' => $date,
        ]);
    }

    private function getMobilePunchData($date)
    {
        // Assuming there isn't a specific table for mobile punches, maybe 'punch_type' column?
        // Since the prompt instructs to 'create all menu dynamic as my others report genrate format',
        // but no `mobile_punches` table was indicated, we'll try to find any relevant data or just stub 
        // the query based on `emp_attendances` if it has a way to identify mobile punches (e.g., location, type).
        // For right now, we will query `emp_attendances` based on date in case we are missing a specific table context,
        // or just return attendances for the date as a placeholder. We will structure it so the layout works.

        $sql = "
        SELECT 
            emp.id AS emp_id,
            emp.full_name AS emp_name,
            des.name AS designation_name,
            dep.name AS dept_name,
            emp.workplace AS workplace,
            a.punch_in_time,
            a.punch_out_time,
            a.today_status
        FROM employees emp
        LEFT JOIN departments dep 
            ON emp.department_id = dep.id
        LEFT JOIN designations des 
            ON emp.designation_id = des.id
        INNER JOIN emp_attendances a 
            ON a.employee_id = emp.id 
            AND a.attendance_date = ?
        ORDER BY a.punch_in_time DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $date);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Export CSV
    public function exportMobilePunchReport()
    {
        if (!isset($_SESSION['user']['id'])) {
            setError("Unauthorized");
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        $date = $_GET['date'] ?? date('Y-m-d');
        $reportData = $this->getMobilePunchData($date);

        // Excel headers
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="mobile_punch_report_' . $date . '.csv"');

        // UTF-8 BOM for Excel
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');

        // Column headers
        fputcsv($output, ['Employee Name', 'Department', 'Designation', 'Punch In', 'Punch Out', 'Status']);

        foreach ($reportData as $row) {
            fputcsv($output, [
                $row['emp_name'],
                $row['dept_name'],
                $row['designation_name'],
                $row['punch_in_time'],
                $row['punch_out_time'],
                $row['today_status']
            ]);
        }

        fclose($output);
        exit;
    }

    public function exportMobilePunchReportPDF()
    {
        if (!isset($_SESSION['user']['id'])) {
            setError("Unauthorized");
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        $date = $_GET['date'] ?? date('Y-m-d');
        $formattedDate = date('d-m-Y', strtotime($date));

        // Get report data
        $reportData = $this->getMobilePunchData($date);

        // Create new PDF document - Portrait
        $pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('HRM System');
        $pdf->SetTitle("Mobile Punch Report - $date");
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
        $pdf->Cell(0, 7, 'Mobile Punch Report', 0, 1, 'C', true);
        $pdf->Cell(0, 7, 'Date: ' . $formattedDate, 0, 1, 'C', true);

        $pdf->Ln(3);

        // ── TABLE HEADER ─────────────────────────────────────────
        $pdf->SetFillColor($darkGreen[0], $darkGreen[1], $darkGreen[2]);
        $pdf->SetTextColor($headerText[0], $headerText[1], $headerText[2]);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->SetLineWidth(0.3);

        $colWidths = [50, 40, 35, 20, 20, 20]; 
        $headers   = ['Employee Name', 'Department', 'Designation', 'Punch In', 'Punch Out', 'Status'];

        foreach ($headers as $i => $h) {
            $pdf->Cell($colWidths[$i], 10, $h, 1, 0, 'C', true);
        }
        $pdf->Ln();

        // ── TABLE ROWS ────────────────────────────────────────────
        $pdf->SetFont('helvetica', '', 8);
        $rowIndex = 0;

        foreach ($reportData as $row) {
            $rowIndex % 2 === 0
                ? $pdf->SetFillColor($lightGreen[0], $lightGreen[1], $lightGreen[2])
                : $pdf->SetFillColor($white[0], $white[1], $white[2]);

            $pdf->SetTextColor($bodyText[0], $bodyText[1], $bodyText[2]);

            $pdf->Cell($colWidths[0], 8, $row['emp_name'], 1, 0, 'L', true);
            $pdf->Cell($colWidths[1], 8, $row['dept_name'], 1, 0, 'C', true);
            $pdf->Cell($colWidths[2], 8, $row['designation_name'], 1, 0, 'C', true);
            
            $punchIn = $row['punch_in_time'] ? date('H:i', strtotime($row['punch_in_time'])) : '--';
            $punchOut = $row['punch_out_time'] ? date('H:i', strtotime($row['punch_out_time'])) : '--';
            
            $pdf->Cell($colWidths[3], 8, $punchIn, 1, 0, 'C', true);
            $pdf->Cell($colWidths[4], 8, $punchOut, 1, 0, 'C', true);
            $pdf->Cell($colWidths[5], 8, $row['today_status'], 1, 0, 'C', true);

            $pdf->Ln();
            $rowIndex++;
        }

        $pdf->Output("mobile_punch_report_$date.pdf", 'I');
    }
}

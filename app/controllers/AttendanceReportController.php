<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AttendanceReportController extends Controller
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
    public function attendanceReport()
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
        $reportData = $this->EmpAttendanceModel->getDailyReport($date);
        // today summary
        $todaySummary = $this->EmpAttendanceModel->getTodaySummary($date);


        // Pass date to view as $reportDate
        return $this->view('hrm/reports/daily-attendance-report', [
            'current_route' => 'daily-attendance-report',
            'page_title' => 'Daily attendance report',
            'reportData' => $reportData,
            'reportDate' => $date,
            'employeeCount' => $employeeCount,
            'todaySummary' => $todaySummary,
        ]);
    }

    // Export CSV
    public function exportDailyReport()
    {
        $date = $_GET['date'] ?? date('Y-m-d');
        $reportData = $this->EmpAttendanceModel->getDailyReport($date);

        // Excel headers
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="daily_attendance_' . $date . '.csv"');

        // UTF-8 BOM for Excel
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');

        // Column headers
        fputcsv($output, ['Department', 'Total Employee', 'Present', 'Late', 'Absent', 'Leave', 'Day Off', 'Attendance %']);

        foreach ($reportData as $row) {
            $total = $row['total_employee'] ?? 0;
            $present = $row['present_count'] ?? 0;

            // Attendance % same as page → Present / Total
            $attendancePercent = ($total > 0) ? ($present / $total) * 100 : 0;

            fputcsv($output, [
                $row['department_name'],
                $total,
                $present,
                $row['late_count'] ?? 0,
                $row['absent_count'] ?? 0,
                $row['leave_count'] ?? 0,
                $row['day_off_count'] ?? 0,
                number_format($attendancePercent, 2) . '%'
            ]);
        }

        fclose($output);
        exit;
    }

    public function exportDailyReportPDF()
    {
        $date = $_GET['date'] ?? date('Y-m-d');
        $formattedDate = date('d-m-Y (l)', strtotime($date));

        // Get report data
        $reportData = $this->EmpAttendanceModel->getDailyReport($date);

        // Create new PDF document - Landscape
        $pdf = new TCPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('HRM System');
        $pdf->SetTitle("Daily Attendance Report - $date");
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage();

        // ── COLORS ──────────────────────────────────────────────
        $darkGreen   = array(0,  100,  0);    // header background
        $lightGreen  = array(144, 238, 144);  // odd rows / alt bg
        $white       = array(255, 255, 255);  // even rows
        $totalBg     = array(220, 240, 220);  // totals row
        $headerText  = array(255, 255, 255);  // white text on header
        $bodyText    = array(0,   0,   0);    // black body text
        $totalText   = array(0,  100,  0);    // green text on totals
        $lateRed     = array(220,  20,  60);  // crimson for late count
        $dayOffBlue  = array(30,  144, 255);  // dodger blue for day off

        // ── COMPANY TITLE ────────────────────────────────────────
        $pdf->SetFillColor($darkGreen[0], $darkGreen[1], $darkGreen[2]);
        $pdf->SetTextColor($headerText[0], $headerText[1], $headerText[2]);
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->Cell(0, 12, 'The Premium Homes Ltd.', 0, 1, 'C', true);

        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 7, 'Daily Attendance Report', 0, 1, 'C', true);
        $pdf->Cell(0, 7, $formattedDate, 0, 1, 'C', true);

        $pdf->Ln(3);

        // ── TABLE HEADER ─────────────────────────────────────────
        $pdf->SetFillColor($darkGreen[0], $darkGreen[1], $darkGreen[2]);
        $pdf->SetTextColor($headerText[0], $headerText[1], $headerText[2]);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->SetLineWidth(0.3);

        $colWidths = [70, 25, 22, 18, 20, 18, 20, 45]; // Department, Total, Present, Late, Absent, Leave, DayOff, Attendance%
        $headers   = ['Department', 'Total Manpower', 'Present', 'Late', 'Absent', 'Leave', 'Day off', 'Attendance Percentage'];

        foreach ($headers as $i => $h) {
            $pdf->Cell($colWidths[$i], 10, $h, 1, 0, 'C', true);
        }
        $pdf->Ln();

        // ── TABLE ROWS ────────────────────────────────────────────
        $pdf->SetFont('helvetica', 'B', 9);

        $totals = ['total' => 0, 'present' => 0, 'late' => 0, 'absent' => 0, 'leave' => 0, 'day_off' => 0];
        $rowIndex = 0;

        foreach ($reportData as $row) {
            // Alternate row color
            $rowIndex % 2 === 0
                ? $pdf->SetFillColor($lightGreen[0], $lightGreen[1], $lightGreen[2])
                : $pdf->SetFillColor($white[0], $white[1], $white[2]);

            $attendancePercent = ($row['present_count'] / max($row['total_employee'], 1)) * 100;

            // Accumulate totals
            $totals['total']   += $row['total_employee'];
            $totals['present'] += $row['present_count'];
            $totals['late']    += $row['late_count'];
            $totals['absent']  += $row['absent_count'];
            $totals['leave']   += $row['leave_count'];
            $totals['day_off'] += $row['day_off_count'];

            // Department name
            $pdf->SetTextColor($bodyText[0], $bodyText[1], $bodyText[2]);
            $pdf->Cell($colWidths[0], 8, $row['department_name'], 1, 0, 'L', true);

            // Total Manpower
            $pdf->Cell($colWidths[1], 8, $row['total_employee'], 1, 0, 'C', true);

            // Present
            $pdf->Cell($colWidths[2], 8, $row['present_count'], 1, 0, 'C', true);

            // Late — red if > 0, else normal
            if ($row['late_count'] > 0) {
                $pdf->SetTextColor($lateRed[0], $lateRed[1], $lateRed[2]);
            }
            $pdf->Cell($colWidths[3], 8, $row['late_count'], 1, 0, 'C', true);
            $pdf->SetTextColor($bodyText[0], $bodyText[1], $bodyText[2]);

            // Absent
            $pdf->Cell($colWidths[4], 8, $row['absent_count'], 1, 0, 'C', true);

            // Leave
            $pdf->Cell($colWidths[5], 8, $row['leave_count'], 1, 0, 'C', true);

            // Day Off — blue if > 0
            if ($row['day_off_count'] > 0) {
                $pdf->SetTextColor($dayOffBlue[0], $dayOffBlue[1], $dayOffBlue[2]);
            }
            $pdf->Cell($colWidths[6], 8, $row['day_off_count'], 1, 0, 'C', true);
            $pdf->SetTextColor($bodyText[0], $bodyText[1], $bodyText[2]);

            // Attendance %
            $pdf->Cell($colWidths[7], 8, number_format($attendancePercent, 0) . '%', 1, 0, 'C', true);

            $pdf->Ln();
            $rowIndex++;
        }

        // ── TOTALS ROW ────────────────────────────────────────────
        $pdf->SetFillColor($totalBg[0], $totalBg[1], $totalBg[2]);
        $pdf->SetTextColor($totalText[0], $totalText[1], $totalText[2]);
        $pdf->SetFont('helvetica', 'B', 9);

        $totalAttendance = ($totals['present'] / max($totals['total'], 1)) * 100;

        $pdf->Cell($colWidths[0], 8, 'Total', 1, 0, 'C', true);
        $pdf->Cell($colWidths[1], 8, $totals['total'],   1, 0, 'C', true);
        $pdf->Cell($colWidths[2], 8, $totals['present'], 1, 0, 'C', true);

        $pdf->SetTextColor($lateRed[0], $lateRed[1], $lateRed[2]);
        $pdf->Cell($colWidths[3], 8, $totals['late'],    1, 0, 'C', true);

        $pdf->SetTextColor($totalText[0], $totalText[1], $totalText[2]);
        $pdf->Cell($colWidths[4], 8, $totals['absent'],  1, 0, 'C', true);
        $pdf->Cell($colWidths[5], 8, $totals['leave'],   1, 0, 'C', true);

        $pdf->SetTextColor($dayOffBlue[0], $dayOffBlue[1], $dayOffBlue[2]);
        $pdf->Cell($colWidths[6], 8, $totals['day_off'], 1, 0, 'C', true);

        $pdf->SetTextColor($totalText[0], $totalText[1], $totalText[2]);
        $pdf->Cell($colWidths[7], 8, number_format($totalAttendance, 0) . '%', 1, 0, 'C', true);
        $pdf->Ln();

        // Output PDF
        $pdf->Output("daily_attendance_$date.pdf", 'I');
    }
}

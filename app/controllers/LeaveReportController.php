<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LeaveReportController extends Controller
{
    protected $db;
    protected $leaveModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->leaveModel = new EmployeeLeave($this->db);
    }

    public function leaveReport()
    {
        if (!isset($_SESSION['user']['id'])) {
            setError("Unauthorized");
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        // Fetch leave report data
        $reportData = $this->leaveModel->getAll();

        return $this->view('hrm/reports/leave-report', [
            'current_route' => 'leave-report',
            'page_title' => 'Leave Report',
            'reportData' => $reportData,
        ]);
    }

    // Export CSV
    public function exportLeaveReport()
    {
        if (!isset($_SESSION['user']['id'])) {
            setError("Unauthorized");
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        $reportData = $this->leaveModel->getAll();
        $date = date('Y-m-d');

        // Excel headers
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="leave_report_' . $date . '.csv"');

        // UTF-8 BOM for Excel
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');

        // Column headers
        fputcsv($output, ['Employee Name', 'Leave Type', 'Start Date', 'End Date', 'Requested Days', 'Approved Days', 'Reason', 'Status']);

        foreach ($reportData as $row) {
            fputcsv($output, [
                $row['employee_name'],
                $row['leave_type_name'],
                $row['start_date'],
                $row['end_date'],
                $row['requested_days'],
                $row['approved_days'] ?? 0,
                $row['reason'],
                ucfirst($row['approval_status'])
            ]);
        }

        fclose($output);
        exit;
    }

    public function exportLeaveReportPDF()
    {
        if (!isset($_SESSION['user']['id'])) {
            setError("Unauthorized");
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        $date = date('Y-m-d');
        $formattedDate = date('d-m-Y');

        // Get report data
        $reportData = $this->leaveModel->getAll();

        // Create new PDF document - Landscape
        $pdf = new TCPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('HRM System');
        $pdf->SetTitle("Leave Report - $date");
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage();

        // ── COLORS ──────────────────────────────────────────────
        $darkGreen   = array(0,  100,  0);    // header background
        $lightGreen  = array(144, 238, 144);  // odd rows / alt bg
        $white       = array(255, 255, 255);  // even rows
        $headerText  = array(255, 255, 255);  // white text on header
        $bodyText    = array(0,   0,   0);    // black body text

        // ── COMPANY TITLE ────────────────────────────────────────
        $pdf->SetFillColor($darkGreen[0], $darkGreen[1], $darkGreen[2]);
        $pdf->SetTextColor($headerText[0], $headerText[1], $headerText[2]);
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->Cell(0, 12, 'The Premium Homes Ltd.', 0, 1, 'C', true);

        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 7, 'Employee Leave Report', 0, 1, 'C', true);
        $pdf->Cell(0, 7, 'Generated on: ' . $formattedDate, 0, 1, 'C', true);

        $pdf->Ln(3);

        // ── TABLE HEADER ─────────────────────────────────────────
        $pdf->SetFillColor($darkGreen[0], $darkGreen[1], $darkGreen[2]);
        $pdf->SetTextColor($headerText[0], $headerText[1], $headerText[2]);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->SetLineWidth(0.3);

        $colWidths = [45, 30, 25, 25, 20, 20, 80, 25]; 
        $headers   = ['Employee Name', 'Leave Type', 'Start Date', 'End Date', 'Req. Days', 'App. Days', 'Reason', 'Status'];

        foreach ($headers as $i => $h) {
            $pdf->Cell($colWidths[$i], 10, $h, 1, 0, 'C', true);
        }
        $pdf->Ln();

        // ── TABLE ROWS ────────────────────────────────────────────
        $pdf->SetFont('helvetica', '', 9);
        $rowIndex = 0;

        foreach ($reportData as $row) {
            // Alternate row color
            $rowIndex % 2 === 0
                ? $pdf->SetFillColor($lightGreen[0], $lightGreen[1], $lightGreen[2])
                : $pdf->SetFillColor($white[0], $white[1], $white[2]);

            $pdf->SetTextColor($bodyText[0], $bodyText[1], $bodyText[2]);

            // Employee Name
            $pdf->Cell($colWidths[0], 8, $row['employee_name'], 1, 0, 'L', true);

            // Leave Type
            $pdf->Cell($colWidths[1], 8, $row['leave_type_name'], 1, 0, 'C', true);

            // Start Date
            $pdf->Cell($colWidths[2], 8, date('d-M-Y', strtotime($row['start_date'])), 1, 0, 'C', true);

            // End Date
            $pdf->Cell($colWidths[3], 8, date('d-M-Y', strtotime($row['end_date'])), 1, 0, 'C', true);

            // Requested Days
            $pdf->Cell($colWidths[4], 8, $row['requested_days'], 1, 0, 'C', true);

            // Approved Days
            $pdf->Cell($colWidths[5], 8, $row['approved_days'] ?? 0, 1, 0, 'C', true);
            
            // Reason (truncated if too long)
            $reason = strlen($row['reason']) > 50 ? substr($row['reason'], 0, 47) . '...' : $row['reason'];
            $pdf->Cell($colWidths[6], 8, $reason, 1, 0, 'L', true);

            // Status
            $status = ucfirst($row['approval_status']);
            $pdf->Cell($colWidths[7], 8, $status, 1, 0, 'C', true);

            $pdf->Ln();
            $rowIndex++;
        }

        // Output PDF
        $pdf->Output("leave_report_$date.pdf", 'I');
    }
}

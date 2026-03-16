<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class HrmDailyReportController extends Controller
{
    protected $db;
    protected $EmpAttendanceModel;
    protected $employeeModel;
    protected $departmentModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->EmpAttendanceModel = new EmpAttentendance($this->db);
        $this->employeeModel = new Employee($this->db);
        $this->departmentModel = new Department($this->db);
    }

    public function index()
    {
        if (!isset($_SESSION['user']['id'])) {
            setError("Unauthorized");
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        // Filters
        $date = $_GET['date'] ?? date('Y-m-d');
        $department_id = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null;
        $employee_id = !empty($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;

        $departments = $this->departmentModel->getAll();
        $employees = $this->employeeModel->getAll();

        $reportData = $this->getFilteredDailyData($date, $department_id, $employee_id);

        return $this->view('hrm/reports/hrm-daily-report', [
            'current_route' => 'hrm-daily-report',
            'page_title' => 'Daily Report',
            'departments' => $departments,
            'employees' => $employees,
            'reportData' => $reportData,
            'selectedDate' => $date,
            'selectedDepartmentId' => $department_id,
            'selectedEmployeeId' => $employee_id,
        ]);
    }

    private function getFilteredDailyData($date, $department_id = null, $employee_id = null)
    {
        $sql = "
        SELECT 
            emp.id AS emp_id,
            emp.full_name AS emp_name,
            des.name AS designation_name,
            dep.name AS dept_name,
            a.punch_in_time,
            a.punch_out_time,
            COALESCE(a.today_status, 'Absent') as today_status
        FROM employees emp
        LEFT JOIN departments dep ON emp.department_id = dep.id
        LEFT JOIN designations des ON emp.designation_id = des.id
        LEFT JOIN emp_attendances a ON a.employee_id = emp.id AND a.attendance_date = ?
        WHERE 1=1
        ";

        $types = "s";
        $params = [$date];

        if ($department_id) {
            $sql .= " AND emp.department_id = ?";
            $types .= "i";
            $params[] = $department_id;
        }

        if ($employee_id) {
            $sql .= " AND emp.id = ?";
            $types .= "i";
            $params[] = $employee_id;
        }

        $sql .= " ORDER BY dep.name ASC, emp.full_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Export CSV
    public function exportCSV()
    {
        if (!isset($_SESSION['user']['id'])) {
            setError("Unauthorized");
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        $date = $_GET['date'] ?? date('Y-m-d');
        $department_id = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null;
        $employee_id = !empty($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;

        $reportData = $this->getFilteredDailyData($date, $department_id, $employee_id);
        $filename = "Daily_Report_$date.csv";

        // Excel headers
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        // UTF-8 BOM for Excel
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');

        // Header info
        fputcsv($output, ['Daily Attendance Report for ' . date('d M Y', strtotime($date))]);
        if ($department_id) {
            $dept = $this->departmentModel->find($department_id);
            fputcsv($output, ['Department Filter: ' . ($dept['name'] ?? 'Unknown')]);
        }
        fputcsv($output, []);
        fputcsv($output, ['Employee Name', 'Department', 'Designation', 'Punch In', 'Punch Out', 'Status']);

        foreach ($reportData as $row) {
            $punchIn = (!empty($row['punch_in_time']) && $row['punch_in_time'] != '0000-00-00 00:00:00') ? date('h:i A', strtotime($row['punch_in_time'])) : '--:--';
            $punchOut = (!empty($row['punch_out_time']) && $row['punch_out_time'] != '0000-00-00 00:00:00') ? date('h:i A', strtotime($row['punch_out_time'])) : '--:--';
            
            fputcsv($output, [
                $row['emp_name'],
                $row['dept_name'],
                $row['designation_name'],
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

        $date = $_GET['date'] ?? date('Y-m-d');
        $department_id = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null;
        $employee_id = !empty($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;

        $reportData = $this->getFilteredDailyData($date, $department_id, $employee_id);
        $deptName = 'All Departments';
        if ($department_id) {
            $dept = $this->departmentModel->find($department_id);
            $deptName = $dept['name'] ?? 'Unknown';
        }

        // Create new PDF document - Portrait
        $pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('HRM System');
        $pdf->SetTitle("Daily Report - $date");
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
        $pdf->Cell(0, 7, 'Daily Attendance Report', 0, 1, 'C', true);
        
        $pdf->Ln(2);
        
        $pdf->SetFillColor($white[0], $white[1], $white[2]);
        $pdf->SetTextColor($bodyText[0], $bodyText[1], $bodyText[2]);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, 'Date: ' . date('d M Y', strtotime($date)), 0, 1, 'L', true);
        $pdf->Cell(0, 6, 'Department: ' . $deptName, 0, 1, 'L', true);

        $pdf->Ln(3);

        // ── TABLE HEADER ─────────────────────────────────────────
        $pdf->SetFillColor($darkGreen[0], $darkGreen[1], $darkGreen[2]);
        $pdf->SetTextColor($headerText[0], $headerText[1], $headerText[2]);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->SetLineWidth(0.3);

        $colWidths = [50, 40, 35, 25, 20, 20]; // Total 190
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
            $pdf->Cell($colWidths[1], 8, $row['dept_name'] ?? 'N/A', 1, 0, 'C', true);
            $pdf->Cell($colWidths[2], 8, $row['designation_name'] ?? 'N/A', 1, 0, 'C', true);

            // Punch In & Out
            $punchIn = (!empty($row['punch_in_time']) && $row['punch_in_time'] != '0000-00-00 00:00:00') ? date('h:i', strtotime($row['punch_in_time'])) : '--:--';
            $punchOut = (!empty($row['punch_out_time']) && $row['punch_out_time'] != '0000-00-00 00:00:00') ? date('h:i', strtotime($row['punch_out_time'])) : '--:--';
            
            $pdf->Cell($colWidths[3], 8, $punchIn, 1, 0, 'C', true);
            $pdf->Cell($colWidths[4], 8, $punchOut, 1, 0, 'C', true);

            // Status 
            if ($row['today_status'] === 'Absent') {
                $pdf->SetTextColor(220, 20, 60); // Red
            } elseif ($row['today_status'] === 'Late') {
                $pdf->SetTextColor(255, 165, 0); // Orange
            } elseif ($row['today_status'] === 'Present') {
                $pdf->SetTextColor(0, 128, 0); // Green
            }
            $pdf->Cell($colWidths[5], 8, $row['today_status'], 1, 0, 'C', true);
            $pdf->SetTextColor($bodyText[0], $bodyText[1], $bodyText[2]);

            $pdf->Ln();
            $rowIndex++;
        }

        $pdf->Output("Daily_Report_$date.pdf", 'I');
    }
}

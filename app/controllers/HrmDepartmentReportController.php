<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class HrmDepartmentReportController extends Controller
{
    protected $db;
    protected $departmentModel;

    public function __construct($db)
    {
        $this->db = $db;
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

        $departments = $this->departmentModel->getAll();

        $reportData = $this->getDepartmentAggregatedData($date, $department_id);

        return $this->view('hrm/reports/dept-wise-report', [
            'current_route' => 'dept-wise-report',
            'page_title' => 'Department Wise Report',
            'departments' => $departments,
            'reportData' => $reportData,
            'selectedDate' => $date,
            'selectedDepartmentId' => $department_id,
        ]);
    }

    private function getDepartmentAggregatedData($date, $department_id = null)
    {
        $sql = "
        SELECT 
            dep.id AS dept_id,
            dep.name AS dept_name,
            COUNT(emp.id) as total_employees,
            SUM(CASE WHEN a.today_status = 'Present' THEN 1 ELSE 0 END) as total_present,
            SUM(CASE WHEN a.today_status = 'Absent' THEN 1 ELSE 0 END) as total_absent,
            SUM(CASE WHEN a.today_status = 'Late' THEN 1 ELSE 0 END) as total_late,
            SUM(CASE WHEN a.today_status = 'On Leave' THEN 1 ELSE 0 END) as total_leave,
            SUM(CASE WHEN a.today_status = 'Day Off' THEN 1 ELSE 0 END) as total_off,
            SUM(CASE WHEN a.today_status = 'Half-Day' THEN 1 ELSE 0 END) as total_half
        FROM departments dep
        LEFT JOIN employees emp ON emp.department_id = dep.id
        LEFT JOIN emp_attendances a ON a.employee_id = emp.id AND a.attendance_date = ?
        WHERE 1=1
        ";

        $types = "s";
        $params = [$date];

        if ($department_id) {
            $sql .= " AND dep.id = ?";
            $types .= "i";
            $params[] = $department_id;
        }

        $sql .= " GROUP BY dep.id ORDER BY dep.name ASC";

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

        $reportData = $this->getDepartmentAggregatedData($date, $department_id);
        $filename = "Department_Wise_Report_$date.csv";

        // Excel headers
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');

        // Header info
        fputcsv($output, ['Department Wise Attendance Report for ' . date('d M Y', strtotime($date))]);
        fputcsv($output, []);
        fputcsv($output, ['Department', 'Total Employees', 'Present', 'Absent', 'Late', 'Half-Day', 'Leave', 'Day Off']);

        foreach ($reportData as $row) {
            fputcsv($output, [
                $row['dept_name'],
                $row['total_employees'] ?? 0,
                $row['total_present'] ?? 0,
                $row['total_absent'] ?? 0,
                $row['total_late'] ?? 0,
                $row['total_half'] ?? 0,
                $row['total_leave'] ?? 0,
                $row['total_off'] ?? 0,
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

        $reportData = $this->getDepartmentAggregatedData($date, $department_id);

        $pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('HRM System');
        $pdf->SetTitle("Department Wise Report - $date");
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage();

        $darkGreen   = array(0,  100,  0);    
        $lightGreen  = array(144, 238, 144);  
        $white       = array(255, 255, 255);  
        $headerText  = array(255, 255, 255);  
        $bodyText    = array(0,   0,   0);    

        $pdf->SetFillColor($darkGreen[0], $darkGreen[1], $darkGreen[2]);
        $pdf->SetTextColor($headerText[0], $headerText[1], $headerText[2]);
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->Cell(0, 12, 'The Premium Homes Ltd.', 0, 1, 'C', true);

        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 7, 'Department Wise Report', 0, 1, 'C', true);
        
        $pdf->Ln(2);
        
        $pdf->SetFillColor($white[0], $white[1], $white[2]);
        $pdf->SetTextColor($bodyText[0], $bodyText[1], $bodyText[2]);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, 'Date: ' . date('d M Y', strtotime($date)), 0, 1, 'L', true);

        $pdf->Ln(3);

        $pdf->SetFillColor($darkGreen[0], $darkGreen[1], $darkGreen[2]);
        $pdf->SetTextColor($headerText[0], $headerText[1], $headerText[2]);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->SetLineWidth(0.3);

        $colWidths = [40, 25, 20, 20, 20, 20, 20, 20]; 
        $headers   = ['Department', 'Total Emp.', 'Present', 'Absent', 'Late', 'Half-Day', 'Leave', 'Day Off'];

        foreach ($headers as $i => $h) {
            $pdf->Cell($colWidths[$i], 10, $h, 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetFont('helvetica', '', 9);
        $rowIndex = 0;

        foreach ($reportData as $row) {
            $rowIndex % 2 === 0
                ? $pdf->SetFillColor($lightGreen[0], $lightGreen[1], $lightGreen[2])
                : $pdf->SetFillColor($white[0], $white[1], $white[2]);

            $pdf->SetTextColor($bodyText[0], $bodyText[1], $bodyText[2]);

            $pdf->Cell($colWidths[0], 8, $row['dept_name'], 1, 0, 'L', true);
            $pdf->Cell($colWidths[1], 8, $row['total_employees'] ?? 0, 1, 0, 'C', true);
            $pdf->Cell($colWidths[2], 8, $row['total_present'] ?? 0, 1, 0, 'C', true);
            $pdf->Cell($colWidths[3], 8, $row['total_absent'] ?? 0, 1, 0, 'C', true);
            $pdf->Cell($colWidths[4], 8, $row['total_late'] ?? 0, 1, 0, 'C', true);
            $pdf->Cell($colWidths[5], 8, $row['total_half'] ?? 0, 1, 0, 'C', true);
            $pdf->Cell($colWidths[6], 8, $row['total_leave'] ?? 0, 1, 0, 'C', true);
            $pdf->Cell($colWidths[7], 8, $row['total_off'] ?? 0, 1, 0, 'C', true);

            $pdf->Ln();
            $rowIndex++;
        }

        $pdf->Output("Department_Wise_Report_$date.pdf", 'I');
    }
}

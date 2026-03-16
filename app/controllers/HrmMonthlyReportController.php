<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class HrmMonthlyReportController extends Controller
{
    protected $db;
    protected $employeeModel;
    protected $departmentModel;

    public function __construct($db)
    {
        $this->db = $db;
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
        $month = $_GET['month'] ?? date('Y-m');
        $department_id = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null;
        $employee_id = !empty($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;

        $departments = $this->departmentModel->getAll();
        $employees = $this->employeeModel->getAll();

        $reportData = $this->getMonthlyAggregatedData($month, $department_id, $employee_id);

        return $this->view('hrm/reports/hrm-monthly-report', [
            'current_route' => 'hrm-monthly-report',
            'page_title' => 'Monthly Report',
            'departments' => $departments,
            'employees' => $employees,
            'reportData' => $reportData,
            'selectedMonth' => $month,
            'selectedDepartmentId' => $department_id,
            'selectedEmployeeId' => $employee_id,
        ]);
    }

    private function getMonthlyAggregatedData($month, $department_id = null, $employee_id = null)
    {
        // Extract Year and Month mapping 
        $dateObj = DateTime::createFromFormat('Y-m', $month);
        $year = $dateObj->format('Y');
        $mnt = $dateObj->format('m');
        
        $sql = "
        SELECT 
            emp.id AS emp_id,
            emp.full_name AS emp_name,
            des.name AS designation_name,
            dep.name AS dept_name,
            SUM(CASE WHEN a.today_status = 'Present' THEN 1 ELSE 0 END) as total_present,
            SUM(CASE WHEN a.today_status = 'Absent' THEN 1 ELSE 0 END) as total_absent,
            SUM(CASE WHEN a.today_status = 'Late' THEN 1 ELSE 0 END) as total_late,
            SUM(CASE WHEN a.today_status = 'On Leave' THEN 1 ELSE 0 END) as total_leave,
            SUM(CASE WHEN a.today_status = 'Day Off' THEN 1 ELSE 0 END) as total_off,
            SUM(CASE WHEN a.today_status = 'Half-Day' THEN 1 ELSE 0 END) as total_half
        FROM employees emp
        LEFT JOIN departments dep ON emp.department_id = dep.id
        LEFT JOIN designations des ON emp.designation_id = des.id
        LEFT JOIN emp_attendances a ON a.employee_id = emp.id AND YEAR(a.attendance_date) = ? AND MONTH(a.attendance_date) = ?
        WHERE 1=1
        ";

        $types = "ss";
        $params = [$year, $mnt];

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

        $sql .= " GROUP BY emp.id ORDER BY dep.name ASC, emp.full_name ASC";

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

        $month = $_GET['month'] ?? date('Y-m');
        $department_id = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null;
        $employee_id = !empty($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;

        $reportData = $this->getMonthlyAggregatedData($month, $department_id, $employee_id);
        $filenameMonth = date('F_Y', strtotime($month . '-01'));
        $filename = "Monthly_Report_$filenameMonth.csv";

        // Excel headers
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');

        // Header info
        fputcsv($output, ['Monthly Attendance Report for ' . date('F Y', strtotime($month . '-01'))]);
        if ($department_id) {
            $dept = $this->departmentModel->find($department_id);
            fputcsv($output, ['Department Filter: ' . ($dept['name'] ?? 'Unknown')]);
        }
        fputcsv($output, []);
        fputcsv($output, ['Employee Name', 'Department', 'Designation', 'Present', 'Absent', 'Late', 'Half-Day', 'Leave', 'Day Off']);

        foreach ($reportData as $row) {
            fputcsv($output, [
                $row['emp_name'],
                $row['dept_name'],
                $row['designation_name'],
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

        $month = $_GET['month'] ?? date('Y-m');
        $department_id = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null;
        $employee_id = !empty($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;

        $reportData = $this->getMonthlyAggregatedData($month, $department_id, $employee_id);
        
        $deptName = 'All Departments';
        if ($department_id) {
            $dept = $this->departmentModel->find($department_id);
            $deptName = $dept['name'] ?? 'Unknown';
        }

        $pdf = new TCPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('HRM System');
        $pdf->SetTitle("Monthly Report - $month");
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
        $pdf->Cell(0, 7, 'Monthly Attendance Report', 0, 1, 'C', true);
        
        $pdf->Ln(2);
        
        $pdf->SetFillColor($white[0], $white[1], $white[2]);
        $pdf->SetTextColor($bodyText[0], $bodyText[1], $bodyText[2]);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, 'Month: ' . date('F Y', strtotime($month . '-01')), 0, 1, 'L', true);
        $pdf->Cell(0, 6, 'Department: ' . $deptName, 0, 1, 'L', true);

        $pdf->Ln(3);

        $pdf->SetFillColor($darkGreen[0], $darkGreen[1], $darkGreen[2]);
        $pdf->SetTextColor($headerText[0], $headerText[1], $headerText[2]);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->SetLineWidth(0.3);

        $colWidths = [45, 40, 35, 25, 25, 25, 25, 25, 25]; 
        $headers   = ['Employee Name', 'Department', 'Designation', 'Present', 'Absent', 'Late', 'Half-Day', 'Leave', 'Day Off'];

        foreach ($headers as $i => $h) {
            $pdf->Cell($colWidths[$i], 10, $h, 1, 0, 'C', true);
        }
        $pdf->Ln();

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
            
            $pdf->Cell($colWidths[3], 8, $row['total_present'] ?? 0, 1, 0, 'C', true);
            $pdf->Cell($colWidths[4], 8, $row['total_absent'] ?? 0, 1, 0, 'C', true);
            $pdf->Cell($colWidths[5], 8, $row['total_late'] ?? 0, 1, 0, 'C', true);
            $pdf->Cell($colWidths[6], 8, $row['total_half'] ?? 0, 1, 0, 'C', true);
            $pdf->Cell($colWidths[7], 8, $row['total_leave'] ?? 0, 1, 0, 'C', true);
            $pdf->Cell($colWidths[8], 8, $row['total_off'] ?? 0, 1, 0, 'C', true);

            $pdf->Ln();
            $rowIndex++;
        }

        $pdf->Output("Monthly_Report_$month.pdf", 'I');
    }
}

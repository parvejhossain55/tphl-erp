<?php

require_once __DIR__ . '/../models/ExpenseAnalytics.php';

class ExpenseAnalyticsController extends Controller
{
    protected $db;
    protected $model;

    public function __construct($db)
    {
        $this->db = $db;
        $this->model = new ExpenseAnalytics($this->db);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user']['id'])) {
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        // Enforce strict Role Permission: Admin (1), Super Admin (3), Accounts (6), Accounts Head (30), Role 20, Role 32, Purchase Head (17)
        // Explicitly exclude Role 33 (Chief Officer)
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $allowedRoles = [1, 3, 6, 30, 20, 32, 17];

        if ($roleId === 33) {
            $_SESSION['flash_error'] = "Access denied: Chief Officer is not authorized for Expense Analytics.";
            header("Location: {$GLOBALS['base_url']}/home");
            exit;
        }

        if (!in_array($roleId, $allowedRoles, true)) {
            $roleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
            $isAllowedName = (strpos($roleName, 'account') !== false || strpos($roleName, 'purchase team leader') !== false);
            if (!$isAllowedName) {
                $_SESSION['flash_error'] = "Access denied: You do not have permission to view Expense Analytics.";
                header("Location: {$GLOBALS['base_url']}/home");
                exit;
            }
        }
    }

    /**
     * Expense Analytics Dashboard Index
     */
    public function index()
    {
        $projectId = $_GET['project_id'] ?? '';
        $fromDate  = $_GET['from_date'] ?? '';
        $toDate    = $_GET['to_date'] ?? '';
        $moduleKey = $_GET['module'] ?? '';
        $search    = $_GET['search'] ?? '';

        $filters = [
            'project_id' => $projectId,
            'from_date'  => $fromDate,
            'to_date'    => $toDate,
            'module'     => $moduleKey,
            'search'     => $search
        ];

        // 1. Fetch 5-per-row Module Cards data
        $moduleCards = $this->model->getModuleExpenseCards($fromDate, $toDate, $projectId);

        // 2. Project-wise Cost Summary & KPIs
        $projectSummary = $this->model->getProjectCostSummary($projectId, $fromDate, $toDate);

        // 3. Highest Cost Project Highlight & Leaderboard
        $topProjectsData = $this->model->getTopExpenseProjects(5, $fromDate, $toDate);

        // 4. Detailed Expense Ledger Table
        $ledgerRecords = $this->model->getExpenseLedgerRecords($filters);

        // 5. Dropdown Projects List
        $projectsList = $this->model->getAllProjectsList();

        // 6. ApexCharts Paid vs Due Data
        $chartData = $this->model->getChartPaidVsDueData($fromDate, $toDate, $projectId);

        $this->view("analytics/expense-analytics", [
            "current_route"   => "expense-analytics",
            "page_title"      => "Expense Analytics Dashboard",
            "filters"         => $filters,
            "moduleCards"     => $moduleCards,
            "projectSummary"  => $projectSummary,
            "topProjectsData" => $topProjectsData,
            "ledgerRecords"   => $ledgerRecords,
            "projectsList"    => $projectsList,
            "chartData"       => $chartData
        ]);
    }

    /**
     * Export Statement to Excel (.xlsx)
     */
    public function exportExcel()
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        $projectId = $_GET['project_id'] ?? '';
        $fromDate  = $_GET['from_date'] ?? '';
        $toDate    = $_GET['to_date'] ?? '';
        $moduleKey = $_GET['module'] ?? '';
        $search    = $_GET['search'] ?? '';

        $filters = [
            'project_id' => $projectId,
            'from_date'  => $fromDate,
            'to_date'    => $toDate,
            'module'     => $moduleKey,
            'search'     => $search
        ];

        $records = $this->model->getExpenseLedgerRecords($filters);
        $projectSummary = $this->model->getProjectCostSummary($projectId, $fromDate, $toDate);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Expense Statement');

        // Company Header
        $sheet->setCellValue('A1', 'THE PREMIUM HOMES LTD');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('1E3A8A');

        $sheet->setCellValue('A2', 'EXPENSE ANALYTICS & REQUISITION AUDIT STATEMENT');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('475569');

        $periodText = (!empty($fromDate) || !empty($toDate))
            ? "Period: " . (!empty($fromDate) ? date('d M Y', strtotime($fromDate)) : 'Start') . " to " . (!empty($toDate) ? date('d M Y', strtotime($toDate)) : 'Current')
            : "Period: All Time";

        $projText = "Scope: " . $projectSummary['project_name'];
        if (!empty($moduleKey)) {
            $projText .= " | Module: " . ucwords(str_replace('_', ' ', $moduleKey));
        }

        $sheet->setCellValue('A3', "{$periodText} | {$projText} | Exported on: " . date('d M Y, h:i A'));
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10)->getColor()->setRGB('64748B');

        // Table Headers (Row 5)
        $headers = [
            'A5' => 'SL',
            'B5' => 'Date',
            'C5' => 'Expense Module',
            'D5' => 'Project / General',
            'E5' => 'Payee / Contractor / Vendor',
            'F5' => 'Payee Type',
            'G5' => 'Purpose / Invoice / Description',
            'H5' => 'Requisition Amount (৳)',
            'I5' => 'Approved Amount (৳)',
            'J5' => 'Paid Amount (৳)',
            'K5' => 'Due Amount (৳)',
            'L5' => 'Status'
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        $sheet->getStyle('A5:L5')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A5:L5')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('1E3A8A');
        $sheet->getStyle('A5:L5')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Data Rows
        $rowNum = 6;
        $sl = 1;
        $totReq = 0;
        $totApp = 0;
        $totPaid = 0;
        $totDue = 0;

        foreach ($records as $r) {
            $projLabel = !empty($r['project_name']) ? $r['project_name'] : 'Without Project / General';
            $reqAmt = (float)$r['req_amount'];
            $appAmt = (float)$r['approved_amount'];
            $paidAmt = (float)$r['paid_amount'];
            $dueAmt = (float)$r['due_amount'];

            $totReq += $reqAmt;
            $totApp += $appAmt;
            $totPaid += $paidAmt;
            $totDue += $dueAmt;

            $sheet->setCellValue('A' . $rowNum, $sl++);
            $sheet->setCellValue('B' . $rowNum, !empty($r['created_at']) ? date('d-m-Y', strtotime($r['created_at'])) : '-');
            $sheet->setCellValue('C' . $rowNum, $r['module_name'] ?? '');
            $sheet->setCellValue('D' . $rowNum, $projLabel);
            $sheet->setCellValue('E' . $rowNum, $r['payee_name'] ?? '-');
            $sheet->setCellValue('F' . $rowNum, $r['payee_type'] ?? '-');
            $sheet->setCellValue('G' . $rowNum, $r['purpose_title'] ?? '-');
            $sheet->setCellValue('H' . $rowNum, $reqAmt);
            $sheet->setCellValue('I' . $rowNum, $appAmt);
            $sheet->setCellValue('J' . $rowNum, $paidAmt);
            $sheet->setCellValue('K' . $rowNum, $dueAmt);
            $sheet->setCellValue('L' . $rowNum, ucfirst(str_replace('_', ' ', $r['status'] ?? '')));

            // Number formatting
            $sheet->getStyle("H{$rowNum}:K{$rowNum}")->getNumberFormat()->setFormatCode('#,##0.00');

            if ($rowNum % 2 === 0) {
                $sheet->getStyle("A{$rowNum}:L{$rowNum}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            }

            $rowNum++;
        }

        // Summary Total Row
        $sheet->setCellValue('A' . $rowNum, 'TOTAL');
        $sheet->mergeCells("A{$rowNum}:G{$rowNum}");
        $sheet->setCellValue('H' . $rowNum, "=SUM(H6:H" . ($rowNum - 1) . ")");
        $sheet->setCellValue('I' . $rowNum, "=SUM(I6:I" . ($rowNum - 1) . ")");
        $sheet->setCellValue('J' . $rowNum, "=SUM(J6:J" . ($rowNum - 1) . ")");
        $sheet->setCellValue('K' . $rowNum, "=SUM(K6:K" . ($rowNum - 1) . ")");
        $sheet->setCellValue('L' . $rowNum, count($records) . ' Records');

        $sheet->getStyle("A{$rowNum}:L{$rowNum}")->getFont()->setBold(true);
        $sheet->getStyle("A{$rowNum}:L{$rowNum}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E2E8F0');
        $sheet->getStyle("H{$rowNum}:K{$rowNum}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

        // Auto-fit Column Widths
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Output File Headers
        $filename = "Expense_Statement_" . date('Ymd_His') . ".xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}

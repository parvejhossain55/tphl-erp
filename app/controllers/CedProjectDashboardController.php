<?php
require_once __DIR__ . '/CedCollectionStatementController.php';

/**
 * CED Project-wise Dashboard Controller (Module 2: Under Due Tracking & Management)
 *
 * Provides the full-page project-wise dashboard with dynamic filtering (Project & CED Agent)
 * and AJAX client-level drill-down, aligned with Due Tracking & Management calculations
 * and Module 1 Regular vs. Advance EMI split logic.
 */
class CedProjectDashboardController extends CedCollectionStatementController
{
    /**
     * Override parent __construct to align authorization with Due Tracking & Management
     * (Accounts, Cash-In, Management, and CED roles).
     */
    public function __construct($db)
    {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userRoleId    = (int)($_SESSION['user']['role_id'] ?? 0);
        $userSecRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
        $userPriRoleId = (int)($_SESSION['user']['primary_role_id'] ?? 0);
        $userId        = (int)($_SESSION['user']['id'] ?? 0);
        $roleName      = strtolower(trim($_SESSION['user']['role_name'] ?? ''));

        $allowedRoles = [1, 3, 6, 7, 30, 33, 34, 35, 36, 37, 39];
        $isAuthorized = in_array($userRoleId, $allowedRoles, true)
            || in_array($userSecRoleId, $allowedRoles, true)
            || in_array($userPriRoleId, $allowedRoles, true)
            || $userId === 94
            || strpos($roleName, 'account') !== false
            || strpos($roleName, 'client experience') !== false
            || strpos($roleName, 'clients experiment') !== false
            || strpos($roleName, 'communicat') !== false
            || strpos($roleName, 'management') !== false
            || strpos($roleName, 'admin') !== false
            || (function_exists('hasPermission') && (hasPermission('CED Manage') || hasPermission('Cash In Verify')))
            || (function_exists('isClientsCommunicated') && isClientsCommunicated())
            || (function_exists('isCreditRecovery') && isCreditRecovery())
            || (function_exists('isAccountsHead') && isAccountsHead())
            || (function_exists('isAccountsExecutive') && isAccountsExecutive());

        if (!$isAuthorized) {
            http_response_code(403);
            die('Unauthorized access to Project-wise Dashboard.');
        }
    }

    /**
     * Mirror AccountClientController::getEffectiveCedProjectFilter() for CED agent scoping.
     * Unlocked: all authorized CED users can view and filter across all projects.
     *
     * @return int[]
     */
    protected function resolveScopedProjectIds(): array
    {
        return [];
    }

    /**
     * Parse and resolve dashboard filter parameters including period/date range.
     */
    protected function parseDashboardFilters(): array
    {
        $scopedProjectIds = $this->resolveScopedProjectIds();

        $periodType = strtolower(trim((string)($_GET['period_type'] ?? 'all_time')));
        $allowedPeriods = ['all_time', 'daily', 'weekly', 'monthly', 'custom'];
        if (!in_array($periodType, $allowedPeriods, true)) {
            $periodType = 'all_time';
        }

        $today = date('Y-m-d');
        $dateFrom = '';
        $dateTo = '';
        $periodLabel = 'All Time';

        if ($periodType === 'daily') {
            $dateFrom = $today;
            $dateTo = $today;
            $periodLabel = 'Daily (' . date('d M Y') . ')';
        } elseif ($periodType === 'weekly') {
            // Bangladesh business week: Saturday through today
            $dow = (int)date('w'); // 6 = Saturday
            $daysSinceSat = ($dow === 6) ? 0 : ($dow + 1);
            $dateFrom = date('Y-m-d', strtotime("-{$daysSinceSat} days"));
            $dateTo = $today;
            $periodLabel = 'Weekly (' . date('d M', strtotime($dateFrom)) . ' – ' . date('d M Y', strtotime($dateTo)) . ')';
        } elseif ($periodType === 'monthly') {
            // Company monthly cycle: 26th of month to 25th of next month
            $dayOfMonth = (int)date('j');
            $firstOfThisMonth = date('Y-m-01');
            if ($dayOfMonth >= 26) {
                $dateFrom = date('Y-m-26');
                $dateTo = date('Y-m-25', strtotime($firstOfThisMonth . ' +1 month'));
            } else {
                $dateFrom = date('Y-m-26', strtotime($firstOfThisMonth . ' -1 month'));
                $dateTo = date('Y-m-25');
            }
            $periodLabel = 'Monthly (' . date('d M Y', strtotime($dateFrom)) . ' – ' . date('d M Y', strtotime($dateTo)) . ')';
        } elseif ($periodType === 'custom') {
            $rawFrom = trim((string)($_GET['date_from'] ?? ''));
            $rawTo = trim((string)($_GET['date_to'] ?? ''));
            $dateFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-m-01');
            $dateTo = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo) ? $rawTo : $today;
            if ($dateFrom > $dateTo) {
                [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
            }
            $periodLabel = date('d M Y', strtotime($dateFrom)) . ' – ' . date('d M Y', strtotime($dateTo));
        }

        return [
            'project_id'         => isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int)$_GET['project_id'] : 0,
            'agent_id'           => isset($_GET['agent_id']) && $_GET['agent_id'] !== '' ? (int)$_GET['agent_id'] : 0,
            'search'             => trim($_GET['search'] ?? ''),
            'period_type'        => $periodType,
            'date_from'          => $dateFrom,
            'date_to'            => $dateTo,
            'period_label'       => $periodLabel,
            'scoped_project_ids' => $scopedProjectIds,
        ];
    }

    /**
     * Render the Project-wise Dashboard page under Due Tracking & Management.
     */
    public function index()
    {
        $filters = $this->parseDashboardFilters();
        $scopedProjectIds = $filters['scoped_project_ids'];

        $data = $this->fetchProjectSummary($filters);

        $projectsList = array_values(array_filter(
            $this->fetchProjects(),
            fn($p) => trim((string)($p['name'] ?? '')) !== ''
        ));
        if (!empty($scopedProjectIds)) {
            $projectsList = array_values(array_filter($projectsList, fn($p) => in_array((int)$p['id'], $scopedProjectIds, true)));
        }

        $rawAgents = $this->fetchCedCollectors('ced_only');
        $agentsList = [];
        $seenAgentIds = [];
        foreach ($rawAgents as $ag) {
            $uId = (int)($ag['user_id'] ?? 0);
            $name = trim((string)($ag['full_name'] ?? ''));
            if ($uId <= 0 || $name === '' || isset($seenAgentIds[$uId])) {
                continue;
            }
            $seenAgentIds[$uId] = true;
            $agentsList[] = $ag;
        }

        $projectAgentMap = [];
        $resMap = $this->db->query("
            SELECT project_id, user_id
            FROM ced_project_assignments
            WHERE status = 'active'
            ORDER BY id ASC
        ");
        if ($resMap) {
            while ($mr = $resMap->fetch_assoc()) {
                $pId = (int)$mr['project_id'];
                $uId = (int)$mr['user_id'];
                if ($pId > 0 && $uId > 0 && !isset($projectAgentMap[$pId])) {
                    $projectAgentMap[$pId] = $uId;
                }
            }
        }

        $selectedAgentId = $filters['agent_id'] > 0
            ? $filters['agent_id']
            : (!isset($_GET['agent_id']) && $filters['project_id'] > 0 && isset($projectAgentMap[$filters['project_id']])
                ? (int)$projectAgentMap[$filters['project_id']]
                : 0);

        $viewData = [
            'current_route'     => 'client-wise-due-tracking',
            'current_page'      => 'client-wise-due-tracking',
            'page_title'        => 'Project-wise Dashboard — Due Tracking & Management',
            'filters'           => $filters,
            'project_rows'      => $data['rows'],
            'grand_totals'      => $data['grand'],
            'agents_list'       => $agentsList,
            'projects_list'     => $projectsList,
            'project_agent_map' => $projectAgentMap,
            'selected_agent_id' => $selectedAgentId,
        ];

        $this->view('ced/ced-project-dashboard', $viewData);
    }

    /**
     * AJAX endpoint: Client-level drill-down for a specific project.
     */
    public function clientDrilldown()
    {
        header('Content-Type: application/json; charset=utf-8');

        $projectId = (int)($_GET['project_id'] ?? 0);
        if ($projectId <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid project ID.']);
            return;
        }

        $scopedProjectIds = $this->resolveScopedProjectIds();
        if (!empty($scopedProjectIds) && !in_array($projectId, $scopedProjectIds, true)) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'You are not assigned to this project.']);
            return;
        }

        $filters = $this->parseDashboardFilters();
        $dateRange = (!empty($filters['date_from']) && !empty($filters['date_to']))
            ? ['date_from' => $filters['date_from'], 'date_to' => $filters['date_to']]
            : null;

        $result = $this->fetchClientLevelRows($projectId, $dateRange);
        echo json_encode([
            'status'     => 'success',
            'project_id' => $projectId,
            'rows'       => $result['rows'],
            'totals'     => $result['totals'],
        ]);
    }

    /**
     * Export filtered Project-wise Dashboard report as a formatted Excel (.xlsx) workbook.
     */
    public function exportExcel()
    {
        $filters = $this->parseDashboardFilters();
        $data = $this->fetchProjectSummary($filters);
        $rows = $data['rows'];
        $grand = $data['grand'];

        $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
        }

        // Fallback to UTF-8 CSV if PhpSpreadsheet is unavailable
        if (!class_exists('\\PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            $filename = 'ced_project_dashboard_' . date('Ymd_His') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'Assigned Person', 'Project Name', 'Total Units / Flats', 'Registered', 'Remaining Share',
                'Total Land Share', 'Land Share Paid', 'Land Share Due',
                'Total DC Ask', 'Total DC Received', 'DC Due',
                'EMI Demand (#)', 'Regular EMI Amount', 'Regular EMI Received',
                'Advanced Received', 'Net Regular EMI', 'Regular EMI Due', 'Project Expenditure'
            ]);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['assigned_person'], $r['project_name'], (int)$r['total_client'], (int)$r['registered_client'], (int)$r['remaining_share'],
                    $r['total_land_share'], $r['land_share_paid'], $r['land_share_due'],
                    $r['total_dc_ask'], $r['total_dc_received'], $r['dc_due'],
                    (int)$r['regular_emi_demand_count'], $r['regular_emi_amount'], $r['regular_emi_received'],
                    $r['advanced_received'], $r['net_regular_emi_collected'], $r['regular_emi_due'], $r['project_expenditure']
                ]);
            }
            fclose($out);
            exit;
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Project-wise Dashboard');

        $fillSolid = \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID;
        $alignLeft = \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT;
        $alignCenter = \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER;
        $alignRight = \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT;
        $valignCenter = \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER;
        $borderThin = \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN;
        $borderMedium = \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM;

        // Row 1: Report Title
        $sheet->mergeCells('A1:R1');
        $sheet->setCellValue('A1', 'TPHL ERP — CED Project-wise Dashboard Report');
        $sheet->getStyle('A1:R1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => $fillSolid, 'startColor' => ['rgb' => '0F172A']],
            'alignment' => ['horizontal' => $alignLeft, 'vertical' => $valignCenter],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Row 2: Filter Metadata
        $metaParts = ['Period: ' . ($filters['period_label'] ?? 'All Time')];
        if (!empty($filters['search'])) {
            $metaParts[] = 'Search: ' . $filters['search'];
        }
        $metaParts[] = 'Projects: ' . (int)($grand['project_count'] ?? 0);
        $metaParts[] = 'Generated: ' . date('d M Y, h:i A');
        $sheet->mergeCells('A2:R2');
        $sheet->setCellValue('A2', implode('   |   ', $metaParts));
        $sheet->getStyle('A2:R2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '334155']],
            'fill' => ['fillType' => $fillSolid, 'startColor' => ['rgb' => 'F1F5F9']],
            'alignment' => ['horizontal' => $alignLeft, 'vertical' => $valignCenter],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(22);

        // Row 4: Grouped Section Headers
        $groups = [
            ['A4:E4', 'A. Client & Inventory Information', '1E293B'],
            ['F4:H4', 'B. Land Share Financials', '0F766E'],
            ['I4:K4', 'C. Development Charge Financials', '1D4ED8'],
            ['L4:Q4', 'D. EMI Financials', '6D28D9'],
            ['R4:R4', 'E. Expenditure', 'B45309'],
        ];
        foreach ($groups as [$range, $label, $bg]) {
            if (strpos($range, ':') !== false && explode(':', $range)[0] !== explode(':', $range)[1]) {
                $sheet->mergeCells($range);
            }
            $startCell = explode(':', $range)[0];
            $sheet->setCellValue($startCell, $label);
            $sheet->getStyle($range)->applyFromArray([
                'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => $fillSolid, 'startColor' => ['rgb' => $bg]],
                'alignment' => ['horizontal' => $alignCenter, 'vertical' => $valignCenter],
                'borders' => ['allBorders' => ['borderStyle' => $borderThin, 'color' => ['rgb' => 'FFFFFF']]],
            ]);
        }
        $sheet->getRowDimension(4)->setRowHeight(24);

        // Row 5: Column Headers
        $headers = [
            '1. Assigned Person',
            '2. Project Name',
            '3. Total Units / Flats',
            '4. Registered',
            '5. Remaining Share',
            '6. Total Land Share',
            '7. Land Share Paid',
            '8. Land Share Due',
            '9. Total DC Ask',
            '10. Total DC Received',
            '11. DC Due',
            '12. EMI Demand (#)',
            '13. Regular EMI Amount',
            '14. Regular EMI Received',
            '15. Advanced Received',
            '16. Net Regular EMI',
            '17. Regular EMI Due',
            '18. Project Expenditure',
        ];
        foreach ($headers as $idx => $hText) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->setCellValue($colLetter . '5', $hText);
        }
        $sheet->getStyle('A5:R5')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1E293B']],
            'fill' => ['fillType' => $fillSolid, 'startColor' => ['rgb' => 'E2E8F0']],
            'alignment' => ['horizontal' => $alignCenter, 'vertical' => $valignCenter, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => $borderThin, 'color' => ['rgb' => 'CBD5E1']]],
        ]);
        $sheet->getStyle('A5:B5')->getAlignment()->setHorizontal($alignLeft);
        $sheet->getRowDimension(5)->setRowHeight(28);

        // Data Rows
        $rowNum = 6;
        foreach ($rows as $i => $r) {
            $sheet->setCellValue("A{$rowNum}", (string)$r['assigned_person']);
            $sheet->setCellValue("B{$rowNum}", (string)$r['project_name']);
            $sheet->setCellValue("C{$rowNum}", (int)$r['total_client']);
            $sheet->setCellValue("D{$rowNum}", (int)$r['registered_client']);
            $sheet->setCellValue("E{$rowNum}", (int)$r['remaining_share']);
            $sheet->setCellValue("F{$rowNum}", round((float)$r['total_land_share'], 2));
            $sheet->setCellValue("G{$rowNum}", round((float)$r['land_share_paid'], 2));
            $sheet->setCellValue("H{$rowNum}", round((float)$r['land_share_due'], 2));
            $sheet->setCellValue("I{$rowNum}", round((float)$r['total_dc_ask'], 2));
            $sheet->setCellValue("J{$rowNum}", round((float)$r['total_dc_received'], 2));
            $sheet->setCellValue("K{$rowNum}", round((float)$r['dc_due'], 2));
            $sheet->setCellValue("L{$rowNum}", (int)$r['regular_emi_demand_count']);
            $sheet->setCellValue("M{$rowNum}", round((float)$r['regular_emi_amount'], 2));
            $sheet->setCellValue("N{$rowNum}", round((float)$r['regular_emi_received'], 2));
            $sheet->setCellValue("O{$rowNum}", round((float)$r['advanced_received'], 2));
            $sheet->setCellValue("P{$rowNum}", round((float)$r['net_regular_emi_collected'], 2));
            $sheet->setCellValue("Q{$rowNum}", round((float)$r['regular_emi_due'], 2));
            $sheet->setCellValue("R{$rowNum}", round((float)$r['project_expenditure'], 2));

            $rowBg = ($i % 2 === 1) ? 'F8FAFC' : 'FFFFFF';
            $sheet->getStyle("A{$rowNum}:R{$rowNum}")->applyFromArray([
                'fill' => ['fillType' => $fillSolid, 'startColor' => ['rgb' => $rowBg]],
                'alignment' => ['vertical' => $valignCenter],
                'borders' => ['allBorders' => ['borderStyle' => $borderThin, 'color' => ['rgb' => 'E2E8F0']]],
            ]);
            $sheet->getStyle("B{$rowNum}")->getFont()->setBold(true);
            $sheet->getStyle("C{$rowNum}:R{$rowNum}")->getAlignment()->setHorizontal($alignRight);

            // Highlight positive dues in red & paid/collected in green
            foreach (['H' => $r['land_share_due'], 'K' => $r['dc_due'], 'Q' => $r['regular_emi_due']] as $col => $dueVal) {
                if ((float)$dueVal > 0) {
                    $sheet->getStyle("{$col}{$rowNum}")->getFont()->setBold(true)->getColor()->setRGB('DC2626');
                }
            }
            foreach (['G', 'J', 'P'] as $paidCol) {
                $sheet->getStyle("{$paidCol}{$rowNum}")->getFont()->getColor()->setRGB('047857');
            }

            $sheet->getRowDimension($rowNum)->setRowHeight(20);
            $rowNum++;
        }

        // Grand Total Row
        $sheet->mergeCells("A{$rowNum}:B{$rowNum}");
        $sheet->setCellValue("A{$rowNum}", 'GRAND TOTAL (' . (int)($grand['project_count'] ?? 0) . ' Projects)');
        $sheet->setCellValue("C{$rowNum}", (int)($grand['total_client'] ?? 0));
        $sheet->setCellValue("D{$rowNum}", (int)($grand['registered_client'] ?? 0));
        $sheet->setCellValue("E{$rowNum}", (int)($grand['remaining_share'] ?? 0));
        $sheet->setCellValue("F{$rowNum}", round((float)($grand['total_land_share'] ?? 0), 2));
        $sheet->setCellValue("G{$rowNum}", round((float)($grand['land_share_paid'] ?? 0), 2));
        $sheet->setCellValue("H{$rowNum}", round((float)($grand['land_share_due'] ?? 0), 2));
        $sheet->setCellValue("I{$rowNum}", round((float)($grand['total_dc_ask'] ?? 0), 2));
        $sheet->setCellValue("J{$rowNum}", round((float)($grand['total_dc_received'] ?? 0), 2));
        $sheet->setCellValue("K{$rowNum}", round((float)($grand['dc_due'] ?? 0), 2));
        $sheet->setCellValue("L{$rowNum}", (int)($grand['regular_emi_demand_count'] ?? 0));
        $sheet->setCellValue("M{$rowNum}", round((float)($grand['regular_emi_amount'] ?? 0), 2));
        $sheet->setCellValue("N{$rowNum}", round((float)($grand['regular_emi_received'] ?? 0), 2));
        $sheet->setCellValue("O{$rowNum}", round((float)($grand['advanced_received'] ?? 0), 2));
        $sheet->setCellValue("P{$rowNum}", round((float)($grand['net_regular_emi_collected'] ?? 0), 2));
        $sheet->setCellValue("Q{$rowNum}", round((float)($grand['regular_emi_due'] ?? 0), 2));
        $sheet->setCellValue("R{$rowNum}", round((float)($grand['project_expenditure'] ?? 0), 2));

        $sheet->getStyle("A{$rowNum}:R{$rowNum}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '0F172A']],
            'fill' => ['fillType' => $fillSolid, 'startColor' => ['rgb' => 'E2E8F0']],
            'alignment' => ['vertical' => $valignCenter],
            'borders' => [
                'top' => ['borderStyle' => $borderMedium, 'color' => ['rgb' => '334155']],
                'bottom' => ['borderStyle' => $borderMedium, 'color' => ['rgb' => '334155']],
                'left' => ['borderStyle' => $borderThin, 'color' => ['rgb' => 'CBD5E1']],
                'right' => ['borderStyle' => $borderThin, 'color' => ['rgb' => 'CBD5E1']],
            ],
        ]);
        $sheet->getStyle("C{$rowNum}:R{$rowNum}")->getAlignment()->setHorizontal($alignRight);
        $sheet->getRowDimension($rowNum)->setRowHeight(24);

        // Number formatting across data + grand total rows
        $sheet->getStyle("C6:R{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');

        // Column widths & UX polish
        $widths = [
            'A' => 24, 'B' => 28, 'C' => 14, 'D' => 13, 'E' => 15,
            'F' => 18, 'G' => 18, 'H' => 18, 'I' => 17, 'J' => 18,
            'K' => 16, 'L' => 15, 'M' => 18, 'N' => 19, 'O' => 18,
            'P' => 18, 'Q' => 18, 'R' => 19,
        ];
        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }
        $sheet->freezePane('C6');
        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)
            ->setFitToHeight(0);

        $slugPeriod = preg_replace('/[^a-z0-9_-]+/i', '_', $filters['period_type'] ?? 'all_time');
        $filename = 'CED_Project_Dashboard_' . $slugPeriod . '_' . date('Ymd_His') . '.xlsx';

        if (ob_get_length()) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Shared SQL predicate for valid active bookings (matches CedCollectionStatementController).
     */
    private function activeBookingSql(string $alias = 'lb'): string
    {
        return "{$alias}.deleted_at IS NULL
            AND {$alias}.booking_status NOT IN ('refunded', 'cancelled')
            AND ({$alias}.landshare_status IS NULL OR {$alias}.landshare_status != 'cancelled')
            AND {$alias}.id NOT IN (
                SELECT crr.booking_id
                FROM client_refund_requests crr
                WHERE crr.booking_id IS NOT NULL
                  AND crr.status IN ('pending', 'approved', 'refunded')
            )";
    }

    /**
     * Shared SQL predicate for valid non-rejected, non-trashed payments.
     */
    private function validPaymentSql(string $alias = 'lp'): string
    {
        return "{$alias}.is_trashed = 0
            AND COALESCE({$alias}.is_emi_discount, 0) = 0
            AND ({$alias}.verification_status IS NULL OR {$alias}.verification_status != 'rejected')";
    }

    /**
     * Shared per-booking query returning Land Share & Development Charge financials
     * aligned with Due Tracking & Management (booking_money + registration + land_share),
     * ensuring project-level totals and client-level drill-down rows always reconcile.
     */
    private function fetchActiveBookingsFinancials(string $inProjectIds, bool $includeClientMeta = false, ?array $dateRange = null): array
    {
        $activeBookingCond = $this->activeBookingSql('lb');
        $validPaymentCond  = $this->validPaymentSql('lp');
        $paymentDateCond   = '';
        if (!empty($dateRange['date_from']) && !empty($dateRange['date_to'])) {
            $df = $this->db->real_escape_string($dateRange['date_from']);
            $dt = $this->db->real_escape_string($dateRange['date_to']);
            $paymentDateCond = " AND DATE(lp.payment_date) BETWEEN '{$df}' AND '{$dt}'";
        }

        $metaSelect = $includeClientMeta
            ? ",
                lb.lead_id,
                COALESCE(NULLIF(TRIM(l.lead_name), ''), NULLIF(TRIM(l.full_name), ''), CONCAT('Client #', lb.lead_id)) AS client_name,
                COALESCE(NULLIF(TRIM(l.phone), ''), NULLIF(TRIM(l.phone_number), ''), '') AS phone,
                COALESCE(f.flat_name, CONCAT('Flat #', lb.flat_id)) AS flat_name,
                lb.booking_status,
                lb.booking_date"
            : '';

        $metaJoin = $includeClientMeta ? "LEFT JOIN leads l ON l.id = lb.lead_id" : '';
        $orderBy  = $includeClientMeta ? "ORDER BY f.flat_name ASC, lb.id ASC" : "ORDER BY lb.project_id ASC, lb.id ASC";

        $sql = "
            SELECT
                lb.id AS booking_id,
                lb.project_id,
                COALESCE(f.land_share_value, 0) AS total_land_share,
                COALESCE(lb.discount_amount, 0) AS discount_amount,
                COALESCE(f.development_charges, 0) AS total_dc_ask,
                COALESCE(pay.land_share_paid, 0) AS land_share_paid,
                COALESCE(pay.total_dc_received, 0) AS total_dc_received
                {$metaSelect}
            FROM lead_bookings lb
            LEFT JOIN flats f ON f.id = lb.flat_id
            {$metaJoin}
            LEFT JOIN (
                SELECT
                    lp.booking_id,
                    COALESCE(SUM(CASE WHEN lp.payment_type IN ('booking_money', 'registration', 'land_share') THEN lp.amount ELSE 0 END), 0) AS land_share_paid,
                    COALESCE(SUM(CASE WHEN lp.payment_type = 'development_charge' THEN lp.amount ELSE 0 END), 0) AS total_dc_received
                FROM lead_payments lp
                INNER JOIN lead_bookings lb_p ON lb_p.id = lp.booking_id
                WHERE lb_p.project_id IN ({$inProjectIds})
                  AND {$validPaymentCond}
                  AND lp.payment_type IN ('booking_money', 'registration', 'land_share', 'development_charge')
                  {$paymentDateCond}
                GROUP BY lp.booking_id
            ) pay ON pay.booking_id = lb.id
            WHERE lb.project_id IN ({$inProjectIds})
              AND {$activeBookingCond}
            {$orderBy}
        ";

        $rows = [];
        $res = $this->db->query($sql);
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $totalLs  = round((float)$r['total_land_share'], 2);
                $discount = round((float)$r['discount_amount'], 2);
                $lsPaid   = round((float)$r['land_share_paid'], 2);
                $dcAsk    = round((float)$r['total_dc_ask'], 2);
                $dcRcvd   = round((float)$r['total_dc_received'], 2);

                $row = [
                    'booking_id'        => (int)$r['booking_id'],
                    'project_id'        => (int)$r['project_id'],
                    'total_land_share'  => $totalLs,
                    'discount_amount'   => $discount,
                    'land_share_paid'   => $lsPaid,
                    'land_share_due'    => round($totalLs - $lsPaid, 2),
                    'total_dc_ask'      => $dcAsk,
                    'total_dc_received' => $dcRcvd,
                    'dc_due'            => round($dcAsk - $dcRcvd, 2),
                    'is_registered'     => ($totalLs > 0 && round($totalLs - $discount - $lsPaid, 2) <= 0),
                ];

                if ($includeClientMeta) {
                    $row['lead_id']        = (int)$r['lead_id'];
                    $row['client_name']    = $r['client_name'];
                    $row['phone']          = $r['phone'];
                    $row['flat_name']      = $r['flat_name'];
                    $row['booking_status'] = $r['booking_status'];
                    $row['booking_date']   = $r['booking_date'];
                }

                $rows[(int)$r['booking_id']] = $row;
            }
        }

        return $rows;
    }

    /**
     * Build project-wise summary (all 18 columns) without multi-join fan-out.
     */
    protected function fetchProjectSummary(array $filters): array
    {
        $where = ["p.deleted_at IS NULL"];

        if (!empty($filters['scoped_project_ids'])) {
            $scoped = array_values(array_filter(array_map('intval', $filters['scoped_project_ids'])));
            if (!empty($scoped)) {
                $where[] = "p.id IN (" . implode(',', $scoped) . ")";
            }
        }
        if (!empty($filters['project_id'])) {
            $where[] = "p.id = " . (int)$filters['project_id'];
        }
        if (!empty($filters['agent_id'])) {
            $aId = (int)$filters['agent_id'];
            $where[] = "EXISTS (
                SELECT 1 FROM ced_project_assignments cpa_f
                WHERE cpa_f.project_id = p.id
                  AND cpa_f.status = 'active'
                  AND cpa_f.user_id = {$aId}
            )";
        }
        if (!empty($filters['search'])) {
            $q = $this->db->real_escape_string($filters['search']);
            $where[] = "(p.name LIKE '%{$q}%' OR EXISTS (
                SELECT 1
                FROM ced_project_assignments cpa_s
                INNER JOIN users u_s ON u_s.id = cpa_s.user_id
                LEFT JOIN employees e_s ON (u_s.employee_id = e_s.id OR e_s.user_id = u_s.id)
                WHERE cpa_s.project_id = p.id
                  AND cpa_s.status = 'active'
                  AND COALESCE(e_s.full_name, u_s.full_name, u_s.user_name) LIKE '%{$q}%'
            ))";
        }

        $whereSql = implode(' AND ', $where);

        // 1. Base projects + active assigned CED agent(s)
        $projects = [];
        $sqlProjects = "
            SELECT
                p.id AS project_id,
                p.name AS project_name,
                COALESCE(p.total_share, 0) AS total_share,
                COALESCE(ag.assigned_person, 'Unassigned') AS assigned_person
            FROM projects p
            LEFT JOIN (
                SELECT
                    cpa.project_id,
                    GROUP_CONCAT(DISTINCT COALESCE(e.full_name, u.full_name, u.user_name) ORDER BY u.id SEPARATOR ', ') AS assigned_person
                FROM ced_project_assignments cpa
                INNER JOIN users u ON u.id = cpa.user_id
                LEFT JOIN employees e ON (u.employee_id = e.id OR e.user_id = u.id)
                WHERE cpa.status = 'active'
                GROUP BY cpa.project_id
            ) ag ON ag.project_id = p.id
            WHERE {$whereSql}
            ORDER BY p.name ASC
        ";
        $resP = $this->db->query($sqlProjects);
        if ($resP) {
            while ($r = $resP->fetch_assoc()) {
                $pId = (int)$r['project_id'];
                $projects[$pId] = [
                    'project_id'                => $pId,
                    'assigned_person'           => $r['assigned_person'],
                    'project_name'              => $r['project_name'],
                    'total_share'               => (int)$r['total_share'],
                    'total_client'              => 0,
                    'active_bookings'           => 0,
                    'registered_client'         => 0,
                    'remaining_share'           => 0,
                    'total_land_share'          => 0.0,
                    'land_share_paid'           => 0.0,
                    'land_share_due'            => 0.0,
                    'total_dc_ask'              => 0.0,
                    'total_dc_received'         => 0.0,
                    'dc_due'                    => 0.0,
                    'regular_emi_demand_count'  => 0,
                    'regular_emi_amount'        => 0.0,
                    'regular_emi_received'      => 0.0,
                    'advanced_received'         => 0.0,
                    'net_regular_emi_collected' => 0.0,
                    'regular_emi_due'           => 0.0,
                    'project_expenditure'       => 0.0,
                ];
            }
        }

        $grand = [
            'project_count'             => count($projects),
            'total_client'              => 0,
            'registered_client'         => 0,
            'remaining_share'           => 0,
            'total_land_share'          => 0.0,
            'land_share_paid'           => 0.0,
            'land_share_due'            => 0.0,
            'total_dc_ask'              => 0.0,
            'total_dc_received'         => 0.0,
            'dc_due'                    => 0.0,
            'regular_emi_demand_count'  => 0,
            'regular_emi_amount'        => 0.0,
            'regular_emi_received'      => 0.0,
            'advanced_received'         => 0.0,
            'net_regular_emi_collected' => 0.0,
            'regular_emi_due'           => 0.0,
            'project_expenditure'       => 0.0,
        ];

        if (empty($projects)) {
            return ['rows' => [], 'grand' => $grand];
        }

        $inProjectIds = implode(',', array_keys($projects));

        // 2. Total units/clients per project (flats)
        $resFlats = $this->db->query("
            SELECT project_id, COUNT(id) AS total_units
            FROM flats
            WHERE deleted_at IS NULL AND project_id IN ({$inProjectIds})
            GROUP BY project_id
        ");
        if ($resFlats) {
            while ($r = $resFlats->fetch_assoc()) {
                $pId = (int)$r['project_id'];
                if (isset($projects[$pId])) {
                    $projects[$pId]['total_client'] = (int)$r['total_units'];
                }
            }
        }

        $dateRange = (!empty($filters['date_from']) && !empty($filters['date_to']))
            ? ['date_from' => $filters['date_from'], 'date_to' => $filters['date_to']]
            : null;

        // 3. Aggregate per-booking Land Share, Dev Charge, and Registered Client count
        $bookingRows = $this->fetchActiveBookingsFinancials($inProjectIds, false, $dateRange);
        foreach ($bookingRows as $b) {
            $pId = $b['project_id'];
            if (!isset($projects[$pId])) {
                continue;
            }
            $projects[$pId]['active_bookings']++;
            if ($b['is_registered']) {
                $projects[$pId]['registered_client']++;
            }
            $projects[$pId]['total_land_share']  += $b['total_land_share'];
            $projects[$pId]['land_share_paid']   += $b['land_share_paid'];
            $projects[$pId]['total_dc_ask']      += $b['total_dc_ask'];
            $projects[$pId]['total_dc_received'] += $b['total_dc_received'];
        }

        // 4. Per-booking EMI demand & payments (Module 1 Regular vs Advance split)
        $emiByBooking = $this->computeBookingEmiMetrics($inProjectIds, $dateRange);
        foreach ($emiByBooking as $em) {
            $pId = $em['project_id'];
            if (!isset($projects[$pId])) {
                continue;
            }
            $projects[$pId]['regular_emi_demand_count']  += $em['demand_count'];
            $projects[$pId]['regular_emi_amount']        += $em['asked_amount'];
            $projects[$pId]['regular_emi_received']      += $em['total_emi_paid'];
            $projects[$pId]['advanced_received']         += $em['advance_paid'];
            $projects[$pId]['net_regular_emi_collected'] += $em['net_regular_collected'];
            $projects[$pId]['regular_emi_due']           += $em['emi_due'];
        }

        // 5. Project Expenditure (paid rows in project_other_costs)
        $expDateCond = '';
        if ($dateRange !== null) {
            $df = $this->db->real_escape_string($dateRange['date_from']);
            $dt = $this->db->real_escape_string($dateRange['date_to']);
            $expDateCond = " AND DATE(COALESCE(payment_at, created_at)) BETWEEN '{$df}' AND '{$dt}'";
        }
        $resExp = $this->db->query("
            SELECT
                project_id,
                COALESCE(SUM(COALESCE(payment_amount, 0)), 0) AS project_expenditure
            FROM project_other_costs
            WHERE status = 'paid'
              AND project_id IN ({$inProjectIds})
              {$expDateCond}
            GROUP BY project_id
        ");
        if ($resExp) {
            while ($r = $resExp->fetch_assoc()) {
                $pId = (int)$r['project_id'];
                if (isset($projects[$pId])) {
                    $projects[$pId]['project_expenditure'] = round((float)$r['project_expenditure'], 2);
                }
            }
        }

        // 6. Final derived columns & grand totals
        $rows = [];
        foreach ($projects as $row) {
            $baseShares = $row['total_share'] > 0 ? $row['total_share'] : $row['total_client'];
            $row['remaining_share']           = max(0, $baseShares - $row['active_bookings']);
            $row['total_land_share']          = round($row['total_land_share'], 2);
            $row['land_share_paid']           = round($row['land_share_paid'], 2);
            $row['land_share_due']            = round($row['total_land_share'] - $row['land_share_paid'], 2);
            $row['total_dc_ask']              = round($row['total_dc_ask'], 2);
            $row['total_dc_received']         = round($row['total_dc_received'], 2);
            $row['dc_due']                    = round($row['total_dc_ask'] - $row['total_dc_received'], 2);
            $row['regular_emi_amount']        = round($row['regular_emi_amount'], 2);
            $row['regular_emi_received']      = round($row['regular_emi_received'], 2);
            $row['advanced_received']         = round($row['advanced_received'], 2);
            $row['net_regular_emi_collected'] = round($row['net_regular_emi_collected'], 2);
            $row['regular_emi_due']           = round($row['regular_emi_due'], 2);

            $grand['total_client']              += $row['total_client'];
            $grand['registered_client']         += $row['registered_client'];
            $grand['remaining_share']           += $row['remaining_share'];
            $grand['total_land_share']          += $row['total_land_share'];
            $grand['land_share_paid']           += $row['land_share_paid'];
            $grand['land_share_due']            += $row['land_share_due'];
            $grand['total_dc_ask']              += $row['total_dc_ask'];
            $grand['total_dc_received']         += $row['total_dc_received'];
            $grand['dc_due']                    += $row['dc_due'];
            $grand['regular_emi_demand_count']  += $row['regular_emi_demand_count'];
            $grand['regular_emi_amount']        += $row['regular_emi_amount'];
            $grand['regular_emi_received']      += $row['regular_emi_received'];
            $grand['advanced_received']         += $row['advanced_received'];
            $grand['net_regular_emi_collected'] += $row['net_regular_emi_collected'];
            $grand['regular_emi_due']           += $row['regular_emi_due'];
            $grand['project_expenditure']       += $row['project_expenditure'];

            $rows[] = $row;
        }

        return ['rows' => $rows, 'grand' => $grand];
    }

    /**
     * Compute per-booking EMI metrics strictly from Project-Level Active EMIs
     * (`project_emi_schedule_items.is_active = 1`).
     * If a project has 0 active EMIs, demand_count = 0 and asked_amount = 0.0
     * (any EMI paid is treated as Advance EMI).
     *
     * @param string $inProjectIds Comma-separated list of integer project IDs
     * @return array Keyed by booking_id
     */
    private function computeBookingEmiMetrics(string $inProjectIds, ?array $dateRange = null): array
    {
        $activeBookingCond = $this->activeBookingSql('lb');
        $validPaymentCond  = $this->validPaymentSql('lp');
        $paymentDateCond   = '';
        if (!empty($dateRange['date_from']) && !empty($dateRange['date_to'])) {
            $df = $this->db->real_escape_string($dateRange['date_from']);
            $dt = $this->db->real_escape_string($dateRange['date_to']);
            $paymentDateCond = " AND DATE(lp.payment_date) BETWEEN '{$df}' AND '{$dt}'";
        }

        $metrics = [];

        // 1. Active EMI count per project (strictly is_active = 1, no calendar fallback)
        $activeCountByProject = [];
        $qActive = $this->db->query("
            SELECT project_id, COUNT(*) AS active_emi_count
            FROM project_emi_schedule_items
            WHERE project_id IN ({$inProjectIds})
              AND is_active = 1
            GROUP BY project_id
        ");
        if ($qActive) {
            while ($ar = $qActive->fetch_assoc()) {
                $activeCountByProject[(int)$ar['project_id']] = (int)$ar['active_emi_count'];
            }
        }

        // 2. Per-booking Construction Fee & Per-EMI Amount -> Asked Amount = active_emi_count * per_emi_amount
        $qBookings = $this->db->query("
            SELECT
                lb.id AS booking_id,
                lb.project_id,
                COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0) AS total_price,
                COALESCE(f.land_share_value, 0) AS land_share,
                COALESCE(f.development_charges, 0) AS dev_charge,
                COALESCE(
                    NULLIF(lb.total_emi_no, 0),
                    NULLIF((SELECT MAX(lp_e.total_emi_no) FROM lead_payments lp_e WHERE lp_e.booking_id = lb.id AND lp_e.is_trashed = 0), 0),
                    NULLIF(pes.total_emi_no, 0),
                    36
                ) AS total_emi_no
            FROM lead_bookings lb
            LEFT JOIN flats f ON f.id = lb.flat_id
            LEFT JOIN project_emi_schedules pes ON pes.project_id = lb.project_id
            WHERE lb.project_id IN ({$inProjectIds})
              AND {$activeBookingCond}
        ");
        if ($qBookings) {
            while ($br = $qBookings->fetch_assoc()) {
                $bId            = (int)$br['booking_id'];
                $pId            = (int)$br['project_id'];
                $totalPrice     = (float)$br['total_price'];
                $landShare      = (float)$br['land_share'];
                $devCharge      = (float)$br['dev_charge'];
                $totalEmiNo     = max(1, (int)$br['total_emi_no']);
                if ($totalEmiNo > 120) {
                    $totalEmiNo = 42;
                }
                $constructionFee = max(0.0, round($totalPrice - ($landShare + $devCharge)));
                $perEmiAmount    = ($totalEmiNo > 0) ? round($constructionFee / $totalEmiNo) : 0.0;

                $projActiveCount = $activeCountByProject[$pId] ?? 0;
                $demandCount     = min($totalEmiNo, $projActiveCount);
                $askedAmount     = round($demandCount * $perEmiAmount, 2);

                $metrics[$bId] = [
                    'project_id'            => $pId,
                    'total_sched_rows'      => $demandCount,
                    'demand_count'          => $demandCount,
                    'asked_amount'          => $askedAmount,
                    'total_emi_paid'        => 0.0,
                    'advance_paid'          => 0.0,
                    'net_regular_collected' => 0.0,
                    'emi_due'               => $askedAmount,
                ];
            }
        }

        // 3. Valid EMI payments per active booking
        $qPaid = $this->db->query("
            SELECT
                lp.booking_id,
                lb.project_id,
                COALESCE(SUM(lp.amount), 0) AS total_emi_paid
            FROM lead_payments lp
            INNER JOIN lead_bookings lb ON lb.id = lp.booking_id
            WHERE lb.project_id IN ({$inProjectIds})
              AND {$activeBookingCond}
              AND {$validPaymentCond}
              AND lp.payment_type IN ('emi', 'emi_due_settlement')
              {$paymentDateCond}
            GROUP BY lp.booking_id, lb.project_id
        ");
        if ($qPaid) {
            while ($pr = $qPaid->fetch_assoc()) {
                $bId = (int)$pr['booking_id'];
                if (!isset($metrics[$bId])) {
                    $metrics[$bId] = [
                        'project_id'            => (int)$pr['project_id'],
                        'total_sched_rows'      => 0,
                        'demand_count'          => 0,
                        'asked_amount'          => 0.0,
                        'total_emi_paid'        => 0.0,
                        'advance_paid'          => 0.0,
                        'net_regular_collected' => 0.0,
                        'emi_due'               => 0.0,
                    ];
                }
                $metrics[$bId]['total_emi_paid'] = round((float)$pr['total_emi_paid'], 2);
            }
        }

        // 4. Split strictly against Project-Level Asked Amount:
        // Regular EMI Paid = min(paid, asked); Advance = max(0, paid - asked)
        // Regular EMI Due  = max(0, asked - Regular EMI Paid)
        foreach ($metrics as $bId => &$m) {
            $netReg = round(min($m['total_emi_paid'], $m['asked_amount']), 2);
            $adv    = round(max(0.0, $m['total_emi_paid'] - $netReg), 2);
            $m['net_regular_collected'] = $netReg;
            $m['advance_paid']          = $adv;
            $m['emi_due']               = round(max(0.0, $m['asked_amount'] - $netReg), 2);
        }
        unset($m);

        return $metrics;
    }

    /**
     * Build client-level rows for a single project (matching the project-level columns).
     */
    private function fetchClientLevelRows(int $projectId, ?array $dateRange = null): array
    {
        $clients = $this->fetchActiveBookingsFinancials((string)$projectId, true, $dateRange);

        $totals = [
            'client_count'              => count($clients),
            'registered_count'          => 0,
            'total_land_share'          => 0.0,
            'land_share_paid'           => 0.0,
            'land_share_due'            => 0.0,
            'total_dc_ask'              => 0.0,
            'total_dc_received'         => 0.0,
            'dc_due'                    => 0.0,
            'regular_emi_demand_count'  => 0,
            'regular_emi_amount'        => 0.0,
            'regular_emi_received'      => 0.0,
            'advanced_received'         => 0.0,
            'net_regular_emi_collected' => 0.0,
            'regular_emi_due'           => 0.0,
        ];

        if (empty($clients)) {
            return ['rows' => [], 'totals' => $totals];
        }

        // Per-booking EMI metrics (same Module 1 logic)
        $emiByBooking = $this->computeBookingEmiMetrics((string)$projectId, $dateRange);

        $rows = [];
        foreach ($clients as $bId => $c) {
            $em = $emiByBooking[$bId] ?? null;
            $c['regular_emi_demand_count']  = $em ? $em['demand_count'] : 0;
            $c['regular_emi_amount']        = $em ? $em['asked_amount'] : 0.0;
            $c['regular_emi_received']      = $em ? $em['total_emi_paid'] : 0.0;
            $c['advanced_received']         = $em ? $em['advance_paid'] : 0.0;
            $c['net_regular_emi_collected'] = $em ? $em['net_regular_collected'] : 0.0;
            $c['regular_emi_due']           = $em ? $em['emi_due'] : 0.0;

            if ($c['is_registered']) {
                $totals['registered_count']++;
            }
            $totals['total_land_share']          += $c['total_land_share'];
            $totals['land_share_paid']           += $c['land_share_paid'];
            $totals['land_share_due']            += $c['land_share_due'];
            $totals['total_dc_ask']              += $c['total_dc_ask'];
            $totals['total_dc_received']         += $c['total_dc_received'];
            $totals['dc_due']                    += $c['dc_due'];
            $totals['regular_emi_demand_count']  += $c['regular_emi_demand_count'];
            $totals['regular_emi_amount']        += $c['regular_emi_amount'];
            $totals['regular_emi_received']      += $c['regular_emi_received'];
            $totals['advanced_received']         += $c['advanced_received'];
            $totals['net_regular_emi_collected'] += $c['net_regular_emi_collected'];
            $totals['regular_emi_due']           += $c['regular_emi_due'];

            $rows[] = $c;
        }

        $totals['total_land_share']          = round($totals['total_land_share'], 2);
        $totals['land_share_paid']           = round($totals['land_share_paid'], 2);
        $totals['land_share_due']            = round($totals['total_land_share'] - $totals['land_share_paid'], 2);
        $totals['total_dc_ask']              = round($totals['total_dc_ask'], 2);
        $totals['total_dc_received']         = round($totals['total_dc_received'], 2);
        $totals['dc_due']                    = round($totals['total_dc_ask'] - $totals['total_dc_received'], 2);
        $totals['regular_emi_amount']        = round($totals['regular_emi_amount'], 2);
        $totals['regular_emi_received']      = round($totals['regular_emi_received'], 2);
        $totals['advanced_received']         = round($totals['advanced_received'], 2);
        $totals['net_regular_emi_collected'] = round($totals['net_regular_emi_collected'], 2);
        $totals['regular_emi_due']           = round($totals['regular_emi_due'], 2);

        return ['rows' => $rows, 'totals' => $totals];
    }
}

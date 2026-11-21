<?php
require_once __DIR__ . '/../models/CentralStatementV2.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class CentralStatementV2Controller extends Controller
{
    protected $db;
    protected $model;

    public function __construct($db)
    {
        $this->db = $db;
        $this->model = new CentralStatementV2($this->db);
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Resolve period preset or custom date range
     */
    private function resolvePeriod(array $input): array
    {
        $period = $input['period'] ?? 'this_month';
        $customStart = !empty($input['start_date']) ? $input['start_date'] : null;
        $customEnd = !empty($input['end_date']) ? $input['end_date'] : null;

        if ($period === 'custom' && $customStart && $customEnd) {
            $start = date('Y-m-d', strtotime($customStart));
            $end = date('Y-m-d', strtotime($customEnd));
            if ($start > $end) {
                [$start, $end] = [$end, $start];
            }
            $label = date('d M Y', strtotime($start)) . ' – ' . date('d M Y', strtotime($end));
            return [$period, $start, $end, $label];
        }

        if ($period === 'last_month') {
            $start = date('Y-m-01', strtotime('first day of last month'));
            $end = date('Y-m-t', strtotime('last day of last month'));
            return [$period, $start, $end, date('F Y', strtotime($start))];
        }

        if ($period === 'last_6_months') {
            $start = date('Y-m-01', strtotime('-5 months'));
            $end = date('Y-m-t');
            return [$period, $start, $end, 'Last 6 Months (' . date('M Y', strtotime($start)) . ' – ' . date('M Y', strtotime($end)) . ')'];
        }

        if ($period === 'this_year') {
            $start = date('Y-01-01');
            $end = date('Y-12-31');
            return [$period, $start, $end, 'Year ' . date('Y')];
        }

        if ($period === 'all_time') {
            [$minD, $maxD] = $this->model->getDateBounds();
            return [$period, $minD, $maxD, 'All Time (' . date('d M Y', strtotime($minD)) . ' – ' . date('d M Y', strtotime($maxD)) . ')'];
        }

        if (preg_match('/^\d{4}-\d{2}$/', $period)) {
            $start = $period . '-01';
            $end = date('Y-m-t', strtotime($start));
            return [$period, $start, $end, date('F Y', strtotime($start))];
        }

        // Default: this_month
        $start = date('Y-m-01');
        $end = date('Y-m-t');
        return ['this_month', $start, $end, date('F Y', strtotime($start))];
    }

    /**
     * Parse filters cleanly from GET parameters
     */
    private function parseFilters(array $input): array
    {
        $branchRaw = $input['branch_id'] ?? '';
        $branchId = null;
        if ($branchRaw === 'general') {
            $branchId = 'general';
        } elseif (is_numeric($branchRaw) && (int)$branchRaw > 0) {
            $branchId = (int)$branchRaw;
        }

        $projectIds = [];
        $rawProjects = $input['project_ids'] ?? ($input['project_id'] ?? []);
        if (!is_array($rawProjects)) {
            $rawProjects = ($rawProjects !== '') ? [$rawProjects] : [];
        }
        foreach ($rawProjects as $pVal) {
            if ($pVal === 'general' || $pVal === '0' || $pVal === 0) {
                $projectIds[] = 'general';
            } elseif (is_numeric($pVal) && (int)$pVal > 0) {
                $projectIds[] = (int)$pVal;
            }
        }
        $projectIds = array_values(array_unique($projectIds, SORT_REGULAR));

        $txType = $input['transaction_type'] ?? 'all';
        if (!in_array($txType, ['all', 'cash_in', 'cash_out'], true)) {
            $txType = 'all';
        }

        $validCats = array_keys($this->model->getCategoriesMeta());
        $categories = [];
        $rawCats = $input['categories'] ?? ($input['category'] ?? []);
        if (!is_array($rawCats)) {
            $rawCats = ($rawCats !== '' && $rawCats !== 'all') ? [$rawCats] : [];
        }
        foreach ($rawCats as $cVal) {
            if (in_array($cVal, $validCats, true)) {
                $categories[] = $cVal;
            }
        }
        $categories = array_values(array_unique($categories));

        $paymentMode = $input['payment_mode'] ?? 'all';
        if (!in_array($paymentMode, ['all', 'bank', 'cash'], true)) {
            $paymentMode = 'all';
        }

        return [
            'branch_id' => $branchId,
            'project_id' => count($projectIds) === 1 ? $projectIds[0] : null,
            'project_ids' => $projectIds,
            'transaction_type' => $txType,
            'category' => count($categories) === 1 ? $categories[0] : 'all',
            'categories' => $categories,
            'payment_mode' => $paymentMode,
        ];
    }

    /**
     * Build applied filter chips for UI display
     */
    private function buildAppliedChips(string $periodLabel, array $filters, array $branches, array $projects, array $categoriesMeta): array
    {
        $chips = [['label' => 'Period', 'value' => $periodLabel]];

        if ($filters['branch_id'] === 'general') {
            $chips[] = ['label' => 'Branch', 'value' => 'General'];
        } elseif (is_int($filters['branch_id'])) {
            foreach ($branches as $b) {
                if ((int)$b['id'] === $filters['branch_id']) {
                    $chips[] = ['label' => 'Branch', 'value' => $b['name']];
                    break;
                }
            }
        }

        if (!empty($filters['project_ids'])) {
            $pNames = [];
            $projMap = [];
            foreach ($projects as $p) {
                $projMap[(int)$p['id']] = $p['name'];
            }
            foreach ($filters['project_ids'] as $pid) {
                if ($pid === 'general') {
                    $pNames[] = 'General';
                } elseif (isset($projMap[(int)$pid])) {
                    $pNames[] = $projMap[(int)$pid];
                }
            }
            if (!empty($pNames)) {
                $valText = count($pNames) <= 2 ? implode(', ', $pNames) : count($pNames) . ' selected';
                $chips[] = ['label' => 'Project', 'value' => $valText];
            }
        }

        if ($filters['transaction_type'] !== 'all') {
            $chips[] = ['label' => 'Type', 'value' => $filters['transaction_type'] === 'cash_in' ? 'Cash In' : 'Cash Out'];
        }

        if (!empty($filters['categories'])) {
            $cNames = [];
            foreach ($filters['categories'] as $ck) {
                if (isset($categoriesMeta[$ck])) {
                    $cNames[] = $categoriesMeta[$ck]['label'];
                }
            }
            if (!empty($cNames)) {
                $valText = count($cNames) <= 2 ? implode(', ', $cNames) : count($cNames) . ' selected';
                $chips[] = ['label' => 'Category', 'value' => $valText];
            }
        }

        if ($filters['payment_mode'] !== 'all') {
            $chips[] = ['label' => 'Mode', 'value' => strtoupper($filters['payment_mode'])];
        }

        return $chips;
    }

    /**
     * Main Central Accounts V2 dashboard
     */
    public function index()
    {
        [$period, $startDate, $endDate, $periodLabel] = $this->resolvePeriod($_GET);
        $filters = $this->parseFilters($_GET);
        $activeTab = $_GET['tab'] ?? 'overview';
        if (!in_array($activeTab, ['overview', 'branch', 'project', 'emi_cost'], true)) {
            $activeTab = 'overview';
        }
        $emiScope = ($_GET['emi_scope'] ?? 'emi') === 'all_project' ? 'all_project' : 'emi';
        $intervalMode = ($_GET['interval'] ?? 'auto');
        if (!in_array($intervalMode, ['auto', 'daily', 'monthly'], true)) {
            $intervalMode = 'auto';
        }

        $branches = $this->model->getBranches();
        $projects = $this->model->getProjects();
        $categoriesMeta = $this->model->getCategoriesMeta();
        $alerts = $this->model->getExecutiveAlerts();

        $overview = $this->model->getOverviewData($startDate, $endDate, $filters, $intervalMode);
        $branchData = $this->model->getBranchPerformance($startDate, $endDate, $filters);
        $projectData = $this->model->getProjectCategoryBreakdown($startDate, $endDate, $filters);
        $emiCostData = $this->model->getEmiVsProjectCost($startDate, $endDate, $filters, $emiScope);

        $appliedChips = $this->buildAppliedChips($periodLabel, $filters, $branches, $projects, $categoriesMeta);

        $this->view('accounts/central-statement-v2', [
            'current_route' => 'central-statement-v2',
            'page_title' => 'Central Accounts V2',
            'period' => $period,
            'period_label' => $periodLabel,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'filters' => $filters,
            'active_tab' => $activeTab,
            'emi_scope' => $emiScope,
            'interval_mode' => $intervalMode,
            'branches' => $branches,
            'projects' => $projects,
            'categories_meta' => $categoriesMeta,
            'alerts' => $alerts,
            'overview' => $overview,
            'branch_data' => $branchData,
            'project_data' => $projectData,
            'emi_cost_data' => $emiCostData,
            'applied_chips' => $appliedChips,
        ]);
    }

    /**
     * AJAX JSON endpoint for Slide-Over Drawer ("View transactions" / "View receipts")
     */
    public function drilldown()
    {
        header('Content-Type: application/json; charset=utf-8');
        [$period, $startDate, $endDate] = $this->resolvePeriod($_GET);
        $filters = $this->parseFilters($_GET);

        $overrideStart = !empty($_GET['row_start']) ? $_GET['row_start'] : $startDate;
        $overrideEnd = !empty($_GET['row_end']) ? $_GET['row_end'] : $endDate;

        $result = $this->model->getDrilldownTransactions($overrideStart, $overrideEnd, $filters, 300);
        echo json_encode([
            'status' => 'success',
            'start_date' => $overrideStart,
            'end_date' => $overrideEnd,
            'data' => $result,
        ]);
        exit;
    }

    /**
     * Export styled multi-sheet Excel (.xlsx) workbook using PhpSpreadsheet
     */
    public function exportExcel()
    {
        [$period, $startDate, $endDate, $periodLabel] = $this->resolvePeriod($_GET);
        $filters = $this->parseFilters($_GET);
        $activeTab = $_GET['tab'] ?? 'overview';
        $emiScope = ($_GET['emi_scope'] ?? 'emi') === 'all_project' ? 'all_project' : 'emi';

        $overview = $this->model->getOverviewData($startDate, $endDate, $filters, $_GET['interval'] ?? 'auto');
        $branchData = $this->model->getBranchPerformance($startDate, $endDate, $filters);
        $projectData = $this->model->getProjectCategoryBreakdown($startDate, $endDate, $filters);
        $emiCostData = $this->model->getEmiVsProjectCost($startDate, $endDate, $filters, $emiScope);
        $alerts = $this->model->getExecutiveAlerts();

        $branches = $this->model->getBranches();
        $projects = $this->model->getProjects();
        $categoriesMeta = $this->model->getCategoriesMeta();
        $appliedChips = $this->buildAppliedChips($periodLabel, $filters, $branches, $projects, $categoriesMeta);
        $filterSummary = implode(' | ', array_map(fn($c) => $c['label'] . ': ' . $c['value'], $appliedChips));

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('TPHL-ERP')
            ->setTitle('Central Accounts V2 Statement - ' . $periodLabel);

        // Sheet 1: Overview
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Overview');
        $row = $this->initExcelBanner($sheet1, 'Central Accounts V2 — Executive Overview', $periodLabel, $startDate, $endDate, $filterSummary, 7);

        // Executive KPI Summary Block
        $s = $overview['summary'];
        $sheet1->setCellValue("A{$row}", 'EXECUTIVE SUMMARY');
        $sheet1->getStyle("A{$row}")->getFont()->setBold(true)->setSize(10)->getColor()->setARGB('FF14532D');
        $row++;

        $kpis = [
            ['Opening Balance (BDT)', $s['opening_balance'], 'Closing Balance (BDT)', $s['closing_balance']],
            ['Total Cash In - Verified (BDT)', $s['cash_in'], 'Closing Bank Balance (BDT)', $s['closing_bank']],
            ['Total Cash Out - Paid (BDT)', $s['cash_out'], 'Closing Cash in Hand (BDT)', $s['closing_cash']],
            ['Net Cash Movement (BDT)', $s['net_movement'], 'Pending Verification (BDT)', $alerts['pending_verification']['amount']],
            ['Overdue EMI (BDT)', $alerts['overdue_emi']['amount'], 'Unpaid Approved Bills (BDT)', $alerts['unpaid_payables']['amount']],
        ];
        foreach ($kpis as $k) {
            $sheet1->setCellValue("A{$row}", $k[0]);
            $sheet1->setCellValue("B{$row}", (float)$k[1]);
            $sheet1->setCellValue("D{$row}", $k[2]);
            $sheet1->setCellValue("E{$row}", (float)$k[3]);
            $sheet1->getStyle("A{$row}")->getFont()->setBold(true)->setSize(9);
            $sheet1->getStyle("D{$row}")->getFont()->setBold(true)->setSize(9);
            $sheet1->getStyle("B{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet1->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet1->getStyle("A{$row}:B{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
            $sheet1->getStyle("D{$row}:E{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
            $sheet1->getStyle("A{$row}:B{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE2E8F0');
            $sheet1->getStyle("D{$row}:E{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE2E8F0');
            $row++;
        }
        $row++;

        $headers1 = ['Date / Month', 'Opening Balance', 'Cash In', 'Cash Out', 'Net Movement', 'Closing Balance', 'Transactions'];
        $row = $this->writeExcelTableHeader($sheet1, $row, $headers1);
        $dataStart = $row;
        foreach ($overview['intervals'] as $idx => $r) {
            $sheet1->setCellValue("A{$row}", $r['label']);
            $sheet1->setCellValue("B{$row}", (float)$r['opening']);
            $sheet1->setCellValue("C{$row}", (float)$r['cash_in']);
            $sheet1->setCellValue("D{$row}", (float)$r['cash_out']);
            $sheet1->setCellValue("E{$row}", (float)$r['net']);
            $sheet1->setCellValue("F{$row}", (float)$r['closing']);
            $sheet1->setCellValue("G{$row}", (int)$r['txn_count']);
            $this->styleExcelDataRow($sheet1, $row, 7, [2, 3, 4, 5, 6], [7], $idx % 2 === 1, $r['net'] >= 0 ? 5 : -5);
            $row++;
        }
        $sheet1->setCellValue("A{$row}", 'TOTAL FOR APPLIED SCOPE');
        $sheet1->setCellValue("B{$row}", (float)$s['opening_balance']);
        $sheet1->setCellValue("C{$row}", (float)$s['cash_in']);
        $sheet1->setCellValue("D{$row}", (float)$s['cash_out']);
        $sheet1->setCellValue("E{$row}", (float)$s['net_movement']);
        $sheet1->setCellValue("F{$row}", (float)$s['closing_balance']);
        $sheet1->setCellValue("G{$row}", (int)$s['txn_count']);
        $this->styleExcelTotalRow($sheet1, $row, 7, [2, 3, 4, 5, 6], [7]);
        $this->addExcelFooter($sheet1, $row + 2, 7);
        $this->autoSizeExcelColumns($sheet1, 7);

        // Sheet 2: Branch Performance
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Branch Performance');
        $r2 = $this->initExcelBanner($sheet2, 'Central Accounts V2 — Branch Performance', $periodLabel, $startDate, $endDate, $filterSummary, 5);
        $r2 = $this->writeExcelTableHeader($sheet2, $r2, ['Branch / Office', 'Transactions', 'Collections Received', 'Direct Branch Cost', 'Net Movement']);
        foreach ($branchData['rows'] as $idx => $br) {
            $sheet2->setCellValue("A{$r2}", $br['branch_name']);
            $sheet2->setCellValue("B{$r2}", (int)$br['txn_count']);
            $sheet2->setCellValue("C{$r2}", (float)$br['collections']);
            $sheet2->setCellValue("D{$r2}", (float)$br['direct_cost']);
            $sheet2->setCellValue("E{$r2}", (float)$br['net']);
            $this->styleExcelDataRow($sheet2, $r2, 5, [3, 4, 5], [2], $idx % 2 === 1, $br['net'] >= 0 ? 5 : -5);
            $r2++;
        }
        $bt = $branchData['totals'];
        $sheet2->setCellValue("A{$r2}", 'TOTAL FOR APPLIED SCOPE');
        $sheet2->setCellValue("B{$r2}", (int)$bt['txn_count']);
        $sheet2->setCellValue("C{$r2}", (float)$bt['collections']);
        $sheet2->setCellValue("D{$r2}", (float)$bt['direct_cost']);
        $sheet2->setCellValue("E{$r2}", (float)$bt['net']);
        $this->styleExcelTotalRow($sheet2, $r2, 5, [3, 4, 5], [2]);
        $this->addExcelFooter($sheet2, $r2 + 2, 5);
        $this->autoSizeExcelColumns($sheet2, 5);

        // Sheet 3: Project & Category
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Project & Category');
        $r3 = $this->initExcelBanner($sheet3, 'Central Accounts V2 — Project & Category Breakdown', $periodLabel, $startDate, $endDate, $filterSummary, 6);
        $r3 = $this->writeExcelTableHeader($sheet3, $r3, ['Project / Category', 'Flow Type', 'Count', 'Cash In', 'Cash Out', 'Net Movement']);
        foreach ($projectData['projects'] as $p) {
            $sheet3->setCellValue("A{$r3}", $p['project_name']);
            $sheet3->setCellValue("B{$r3}", 'Project Summary');
            $sheet3->setCellValue("C{$r3}", (int)$p['txn_count']);
            $sheet3->setCellValue("D{$r3}", (float)$p['cash_in']);
            $sheet3->setCellValue("E{$r3}", (float)$p['cash_out']);
            $sheet3->setCellValue("F{$r3}", (float)$p['net']);
            $this->styleExcelDataRow($sheet3, $r3, 6, [4, 5, 6], [3], false, $p['net'] >= 0 ? 6 : -6);
            $sheet3->getStyle("A{$r3}:F{$r3}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFECFDF5');
            $sheet3->getStyle("A{$r3}:F{$r3}")->getFont()->setBold(true);
            $r3++;
            foreach ($p['categories'] as $c) {
                $sheet3->setCellValue("A{$r3}", '   • ' . $c['category_label']);
                $sheet3->setCellValue("B{$r3}", $c['flow_type'] === 'cash_in' ? 'Cash In' : 'Cash Out');
                $sheet3->setCellValue("C{$r3}", (int)$c['txn_count']);
                $sheet3->setCellValue("D{$r3}", (float)$c['cash_in']);
                $sheet3->setCellValue("E{$r3}", (float)$c['cash_out']);
                $sheet3->setCellValue("F{$r3}", (float)$c['net']);
                $this->styleExcelDataRow($sheet3, $r3, 6, [4, 5, 6], [3], false, $c['net'] >= 0 ? 6 : -6);
                $r3++;
            }
        }
        $pt = $projectData['totals'];
        $sheet3->setCellValue("A{$r3}", 'TOTAL FOR APPLIED SCOPE');
        $sheet3->setCellValue("B{$r3}", 'All');
        $sheet3->setCellValue("C{$r3}", (int)$pt['txn_count']);
        $sheet3->setCellValue("D{$r3}", (float)$pt['cash_in']);
        $sheet3->setCellValue("E{$r3}", (float)$pt['cash_out']);
        $sheet3->setCellValue("F{$r3}", (float)$pt['net']);
        $this->styleExcelTotalRow($sheet3, $r3, 6, [4, 5, 6], [3]);
        $this->addExcelFooter($sheet3, $r3 + 2, 6);
        $this->autoSizeExcelColumns($sheet3, 6);

        // Sheet 4: EMI vs Project Cost & Dues
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('EMI vs Project Cost');
        $r4 = $this->initExcelBanner($sheet4, 'Central Accounts V2 — EMI vs Project Cost & Pending Dues', $periodLabel, $startDate, $endDate, $filterSummary, 7);
        $colHeader = $emiScope === 'all_project' ? 'All Project Collections' : 'EMI Collected';
        $r4 = $this->writeExcelTableHeader($sheet4, $r4, ['Project', $colHeader, 'Project Cost Paid', 'Coverage %', 'Surplus / Shortfall', 'Overdue EMI (Due)', 'Unpaid Approved Bills']);
        foreach ($emiCostData['rows'] as $idx => $er) {
            $sheet4->setCellValue("A{$r4}", $er['project_name']);
            $sheet4->setCellValue("B{$r4}", (float)$er['collected']);
            $sheet4->setCellValue("C{$r4}", (float)$er['cost_paid']);
            $sheet4->setCellValue("D{$r4}", $er['coverage_pct'] !== null ? ($er['coverage_pct'] . '%') : '—');
            $sheet4->setCellValue("E{$r4}", (float)$er['surplus']);
            $sheet4->setCellValue("F{$r4}", (float)$er['overdue_emi']);
            $sheet4->setCellValue("G{$r4}", (float)$er['unpaid_bills']);
            $this->styleExcelDataRow($sheet4, $r4, 7, [2, 3, 5, 6, 7], [], $idx % 2 === 1, $er['surplus'] >= 0 ? 5 : -5);
            $sheet4->getStyle("D{$r4}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $r4++;
        }
        $et = $emiCostData['totals'];
        $sheet4->setCellValue("A{$r4}", 'TOTAL FOR APPLIED SCOPE');
        $sheet4->setCellValue("B{$r4}", (float)$et['collected']);
        $sheet4->setCellValue("C{$r4}", (float)$et['cost_paid']);
        $sheet4->setCellValue("D{$r4}", $et['coverage_pct'] !== null ? ($et['coverage_pct'] . '%') : '—');
        $sheet4->setCellValue("E{$r4}", (float)$et['surplus']);
        $sheet4->setCellValue("F{$r4}", (float)$et['overdue_emi']);
        $sheet4->setCellValue("G{$r4}", (float)$et['unpaid_bills']);
        $this->styleExcelTotalRow($sheet4, $r4, 7, [2, 3, 5, 6, 7], []);
        $sheet4->getStyle("D{$r4}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $this->addExcelFooter($sheet4, $r4 + 2, 7);
        $this->autoSizeExcelColumns($sheet4, 7);

        // Focus active sheet based on user's current tab
        $tabSheetIndex = ['overview' => 0, 'branch' => 1, 'project' => 2, 'emi_cost' => 3][$activeTab] ?? 0;
        $spreadsheet->setActiveSheetIndex($tabSheetIndex);

        if (ob_get_length()) {
            ob_end_clean();
        }
        $filename = 'Central_Accounts_V2_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function initExcelBanner($sheet, string $title, string $periodLabel, string $startDate, string $endDate, string $filterSummary, int $colCount): int
    {
        $lastCol = Coordinate::stringFromColumnIndex($colCount);

        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', 'THE PREMIUM HOMES LTD.');
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF14532D']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);

        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->setCellValue('A2', 'Land View Commercial Center, 9th Floor, 28 Gulshan North C/A, Gulshan Circle-2, Dhaka | Phone: +8801958253300 | Email: info@thepremiumhomes.com');
        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
            'font' => ['size' => 8.5, 'color' => ['argb' => 'FF475569']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0FDF4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(18);

        $sheet->mergeCells("A3:{$lastCol}3");
        $sheet->setCellValue('A3', 'CENTRAL FINANCIAL STATEMENT — ' . strtoupper(str_replace('Central Accounts V2 — ', '', $title)));
        $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FF14532D']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFECFDF5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF14532D']]],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(22);

        $sheet->mergeCells("A4:{$lastCol}4");
        $sheet->setCellValue('A4', "Period: {$periodLabel} ({$startDate} to {$endDate})  |  Generated: " . date('d M Y, H:i'));
        $sheet->getStyle("A4:{$lastCol}4")->getFont()->setSize(9.5)->setBold(true)->getColor()->setARGB('FF334155');
        $sheet->getRowDimension(4)->setRowHeight(18);

        $sheet->mergeCells("A5:{$lastCol}5");
        $sheet->setCellValue('A5', 'Applied Filters: ' . $filterSummary);
        $sheet->getStyle("A5:{$lastCol}5")->getFont()->setSize(9)->setItalic(true)->getColor()->setARGB('FF64748B');
        $sheet->getRowDimension(5)->setRowHeight(17);

        // Clean Landscape Print Setup
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);

        return 7;
    }

    private function addExcelFooter($sheet, int $row, int $colCount): void
    {
        $lastCol = Coordinate::stringFromColumnIndex($colCount);
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->setCellValue("A{$row}", 'CONFIDENTIAL — Internal Financial Statement | The Premium Homes Ltd. | Do not distribute externally');
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'font' => ['italic' => true, 'size' => 8.5, 'color' => ['argb' => 'FF64748B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(18);
    }

    private function writeExcelTableHeader($sheet, int $row, array $headers): int
    {
        $colCount = count($headers);
        $lastCol = Coordinate::stringFromColumnIndex($colCount);
        foreach ($headers as $i => $h) {
            $colLetter = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue("{$colLetter}{$row}", $h);
            if ($i > 0) {
                $sheet->getStyle("{$colLetter}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
        }
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF166534']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(22);
        return $row + 1;
    }

    private function styleExcelDataRow($sheet, int $row, int $colCount, array $moneyCols, array $intCols, bool $zebra, int $netColIndicator = 0): void
    {
        $lastCol = Coordinate::stringFromColumnIndex($colCount);
        if ($zebra) {
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
        }
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFE2E8F0');
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->setSize(9.5);

        foreach ($moneyCols as $cIdx) {
            $col = Coordinate::stringFromColumnIndex($cIdx);
            $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
        foreach ($intCols as $cIdx) {
            $col = Coordinate::stringFromColumnIndex($cIdx);
            $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
        if ($netColIndicator !== 0) {
            $col = Coordinate::stringFromColumnIndex(abs($netColIndicator));
            $argb = $netColIndicator > 0 ? 'FF166534' : 'FFB91C1C';
            $sheet->getStyle("{$col}{$row}")->getFont()->setBold(true)->getColor()->setARGB($argb);
        }
    }

    private function styleExcelTotalRow($sheet, int $row, int $colCount, array $moneyCols, array $intCols): void
    {
        $lastCol = Coordinate::stringFromColumnIndex($colCount);
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FF0F172A']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDCFCE7']],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF14532D']],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['argb' => 'FF14532D']],
            ],
        ]);
        foreach ($moneyCols as $cIdx) {
            $col = Coordinate::stringFromColumnIndex($cIdx);
            $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
        foreach ($intCols as $cIdx) {
            $col = Coordinate::stringFromColumnIndex($cIdx);
            $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
        $sheet->getRowDimension($row)->setRowHeight(22);
    }

    private function autoSizeExcelColumns($sheet, int $colCount): void
    {
        for ($i = 1; $i <= $colCount; $i++) {
            $col = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->calculateColumnWidths();
        for ($i = 1; $i <= $colCount; $i++) {
            $col = Coordinate::stringFromColumnIndex($i);
            $w = $sheet->getColumnDimension($col)->getWidth();
            if ($w > 0) {
                $sheet->getColumnDimension($col)->setAutoSize(false);
                $sheet->getColumnDimension($col)->setWidth(max(16, $w + 4));
            }
        }
    }

    /**
     * Clean PDF / Print view
     */
    public function printView()
    {
        [$period, $startDate, $endDate, $periodLabel] = $this->resolvePeriod($_GET);
        $filters = $this->parseFilters($_GET);
        $activeTab = $_GET['tab'] ?? 'overview';
        $emiScope = ($_GET['emi_scope'] ?? 'emi') === 'all_project' ? 'all_project' : 'emi';

        $overview = $this->model->getOverviewData($startDate, $endDate, $filters, $_GET['interval'] ?? 'auto');
        $branchData = $this->model->getBranchPerformance($startDate, $endDate, $filters);
        $projectData = $this->model->getProjectCategoryBreakdown($startDate, $endDate, $filters);
        $emiCostData = $this->model->getEmiVsProjectCost($startDate, $endDate, $filters, $emiScope);
        $alerts = $this->model->getExecutiveAlerts();

        $branches = $this->model->getBranches();
        $projects = $this->model->getProjects();
        $categoriesMeta = $this->model->getCategoriesMeta();
        $appliedChips = $this->buildAppliedChips($periodLabel, $filters, $branches, $projects, $categoriesMeta);

        $period_label = $periodLabel;
        $start_date = $startDate;
        $end_date = $endDate;

        include __DIR__ . '/../views/accounts/central-statement-v2-print.php';
    }
}

<?php
require_once __DIR__ . '/../models/CentralStatementV2.php';

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
     * Export CSV (Excel-compatible UTF-8 BOM)
     */
    public function exportExcel()
    {
        [$period, $startDate, $endDate, $periodLabel] = $this->resolvePeriod($_GET);
        $filters = $this->parseFilters($_GET);
        $tab = $_GET['tab'] ?? 'overview';

        $filename = 'central_accounts_v2_' . $tab . '_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, ['Central Accounts V2 Report', $periodLabel]);
        fputcsv($out, ['Date Range', "$startDate to $endDate"]);
        fputcsv($out, []);

        if ($tab === 'branch') {
            $data = $this->model->getBranchPerformance($startDate, $endDate, $filters);
            fputcsv($out, ['Branch / Office', 'Collections Received (BDT)', 'Direct Branch Cost (BDT)', 'Net Movement (BDT)', 'Transactions']);
            foreach ($data['rows'] as $r) {
                fputcsv($out, [$r['branch_name'], number_format($r['collections'], 2, '.', ''), number_format($r['direct_cost'], 2, '.', ''), number_format($r['net'], 2, '.', ''), $r['txn_count']]);
            }
            fputcsv($out, ['Total', number_format($data['totals']['collections'], 2, '.', ''), number_format($data['totals']['direct_cost'], 2, '.', ''), number_format($data['totals']['net'], 2, '.', ''), $data['totals']['txn_count']]);
        } elseif ($tab === 'project') {
            $data = $this->model->getProjectCategoryBreakdown($startDate, $endDate, $filters);
            fputcsv($out, ['Project', 'Category', 'Flow Type', 'Count', 'Cash In (BDT)', 'Cash Out (BDT)', 'Net Movement (BDT)']);
            foreach ($data['projects'] as $p) {
                fputcsv($out, [$p['project_name'], 'ALL CATEGORIES', 'summary', $p['txn_count'], number_format($p['cash_in'], 2, '.', ''), number_format($p['cash_out'], 2, '.', ''), number_format($p['net'], 2, '.', '')]);
                foreach ($p['categories'] as $c) {
                    fputcsv($out, [$p['project_name'], $c['category_label'], $c['flow_type'], $c['txn_count'], number_format($c['cash_in'], 2, '.', ''), number_format($c['cash_out'], 2, '.', ''), number_format($c['net'], 2, '.', '')]);
                }
            }
        } elseif ($tab === 'emi_cost') {
            $scope = ($_GET['emi_scope'] ?? 'emi') === 'all_project' ? 'all_project' : 'emi';
            $data = $this->model->getEmiVsProjectCost($startDate, $endDate, $filters, $scope);
            fputcsv($out, ['Project', $scope === 'all_project' ? 'All Collections (BDT)' : 'EMI Collected (BDT)', 'Project Cost Paid (BDT)', 'Coverage %', 'Surplus / Shortfall (BDT)', 'Overdue EMI (BDT)', 'Unpaid Approved Bills (BDT)']);
            foreach ($data['rows'] as $r) {
                fputcsv($out, [
                    $r['project_name'],
                    number_format($r['collected'], 2, '.', ''),
                    number_format($r['cost_paid'], 2, '.', ''),
                    $r['coverage_pct'] !== null ? $r['coverage_pct'] . '%' : '-',
                    number_format($r['surplus'], 2, '.', ''),
                    number_format($r['overdue_emi'], 2, '.', ''),
                    number_format($r['unpaid_bills'], 2, '.', ''),
                ]);
            }
        } else {
            $data = $this->model->getOverviewData($startDate, $endDate, $filters, $_GET['interval'] ?? 'auto');
            fputcsv($out, ['Period', 'Opening Balance (BDT)', 'Cash In (BDT)', 'Cash Out (BDT)', 'Net Movement (BDT)', 'Closing Balance (BDT)', 'Transactions']);
            foreach ($data['intervals'] as $r) {
                fputcsv($out, [
                    $r['label'],
                    number_format($r['opening'], 2, '.', ''),
                    number_format($r['cash_in'], 2, '.', ''),
                    number_format($r['cash_out'], 2, '.', ''),
                    number_format($r['net'], 2, '.', ''),
                    number_format($r['closing'], 2, '.', ''),
                    $r['txn_count'],
                ]);
            }
            $s = $data['summary'];
            fputcsv($out, ['TOTAL', number_format($s['opening_balance'], 2, '.', ''), number_format($s['cash_in'], 2, '.', ''), number_format($s['cash_out'], 2, '.', ''), number_format($s['net_movement'], 2, '.', ''), number_format($s['closing_balance'], 2, '.', ''), $s['txn_count']]);
        }

        fclose($out);
        exit;
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

<?php
require_once __DIR__ . '/../core/Controller.php';

class CedCollectionStatementController extends Controller
{
    protected $db;
    protected $emiSplitCache = [];

    public function __construct($db)
    {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Authorization Guard
        $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $roleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
        $userName = strtolower(trim($_SESSION['user']['user_name'] ?? ''));
        $isClientComm = function_exists('isCreditRecovery') ? isCreditRecovery() : in_array($userRoleId, [7, 37, 39], true);
        $isManagement = in_array($userRoleId, [1, 3, 33, 34, 35, 38], true)
            || strpos($roleName, 'management') !== false
            || strpos($roleName, 'admin') !== false
            || strpos($userName, 'management') !== false;

        $canAccess = hasPermission('CED Manage') 
            || $isManagement 
            || $isClientComm 
            || in_array($userRoleId, [7, 37, 39], true);

        if (!$canAccess) {
            http_response_code(403);
            die('Unauthorized access to CED Collection Statement.');
        }
    }

    /**
     * Main Index - CED Collection Statement Page
     */
    public function index()
    {
        if (isset($_GET['print']) && $_GET['print'] === 'true') {
            $this->printStatement();
            return;
        }

        $filters = $this->parseFilters();
        $dateInfo = $this->resolveDateRange($filters);
        $summary = $this->fetchSummary($dateInfo['start_date'], $dateInfo['end_date'], $filters);
        
        // Pagination for detailed transactions (Supports 25, 50, 100, 250, 500, or 'all')
        $limitParam = $_GET['limit'] ?? 50;
        $isAllRows = ($limitParam === 'all' || (int)$limitParam === -1);
        if ($isAllRows) {
            $limit = 10000;
            $page = 1;
            $offset = 0;
        } else {
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = (int)$limitParam;
            if ($limit <= 0) $limit = 50;
            if ($limit > 1000) $limit = 1000;
            $offset = ($page - 1) * $limit;
        }

        $totalRecords = $summary['total_count'] ?? 0;
        $totalPages = $isAllRows ? 1 : ceil(max(1, $totalRecords) / $limit);

        $collectorSummary = $this->fetchCollectorSummary($dateInfo['start_date'], $dateInfo['end_date'], $filters);
        $typeSummary      = $this->fetchPaymentTypeSummary($dateInfo['start_date'], $dateInfo['end_date'], $filters);
        $transactions     = $this->fetchTransactions($dateInfo['start_date'], $dateInfo['end_date'], $filters, $limit, $offset);
        $teamScope = $filters['team_scope'] ?? 'ced_only';
        $collectorsList   = $this->fetchCedCollectors($teamScope);
        $collectorsByScope = [
            'ced_only' => $this->fetchCedCollectors('ced_only'),
            'ced_mgmt' => $this->fetchCedCollectors('ced_mgmt'),
            'all'      => $this->fetchCedCollectors('all')
        ];
        $projectsList     = $this->fetchProjects();

        // Map of user_id => [project_id, ...] for active CED project assignments
        $collectorProjectsMap = [];
        $assignRes = $this->db->query("SELECT user_id, project_id FROM ced_project_assignments WHERE status = 'active'");
        if ($assignRes) {
            while ($aRow = $assignRes->fetch_assoc()) {
                $uId = (int)$aRow['user_id'];
                $pId = (int)$aRow['project_id'];
                if ($uId > 0 && $pId > 0) {
                    $collectorProjectsMap[$uId][] = $pId;
                }
            }
        }

        // Default view mode for CED: 'collectors' (Executive-wise Collection Summary)
        // If page parameter is explicitly present (> 1) and view_mode is not passed, default to 'transactions' because only transactions are paginated
        if (!isset($_GET['view_mode']) && isset($_GET['page']) && (int)$_GET['page'] > 1) {
            $viewMode = 'transactions';
        } else {
            $viewMode = $_GET['view_mode'] ?? 'collectors';
        }
        if (!in_array($viewMode, ['collectors', 'transactions', 'type_summary', 'all'], true)) {
            $viewMode = 'collectors';
        }

        $collectorHistoryNotice = null;
        if (!empty($filters['collector_id'])) {
            $cId = (int)$filters['collector_id'];
            $colTrxCount = 0;
            if (!empty($collectorSummary['rows'])) {
                foreach ($collectorSummary['rows'] as $r) {
                    if (($r['officer_id'] ?? $r['created_by']) == $cId) {
                        $colTrxCount += (int)$r['total_count'];
                    }
                }
            }
            if ($colTrxCount === 0) {
                // Check if they have collections in any month (historical or current assignment)
                $effAgentHist = $this->getEffectiveAgentSql('lb.project_id');
                $qHist = $this->db->query("
                    SELECT 
                        COUNT(lp.id) as total_life_trx,
                        COALESCE(SUM(lp.amount), 0) as total_life_amount,
                        MAX(lp.payment_date) as latest_payment_date,
                        DATE_FORMAT(MAX(lp.payment_date), '%Y') as latest_year,
                        DATE_FORMAT(MAX(lp.payment_date), '%m') as latest_month,
                        DATE_FORMAT(MAX(lp.payment_date), '%M %Y') as latest_month_name
                    FROM lead_payments lp
                    INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
                    WHERE ({$effAgentHist} = {$cId} OR ({$effAgentHist} = 0 AND lp.created_by = {$cId}))
                      AND lp.is_trashed = 0
                      AND COALESCE(lp.is_emi_discount, 0) = 0
                      AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected')
                      AND lb.deleted_at IS NULL
                      AND lb.booking_status NOT IN ('refunded', 'cancelled')
                      AND (lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')
                      AND lb.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))
                ");
                if ($qHist && $hRow = $qHist->fetch_assoc()) {
                    if ((int)$hRow['total_life_trx'] > 0) {
                        $cName = 'Selected Executive';
                        foreach ($collectorsList as $cl) {
                            if ((int)$cl['user_id'] === $cId) {
                                $cName = $cl['full_name'];
                                break;
                            }
                        }
                        $collectorHistoryNotice = [
                            'collector_id'        => $cId,
                            'collector_name'      => $cName,
                            'total_life_trx'      => (int)$hRow['total_life_trx'],
                            'total_life_amount'   => (float)$hRow['total_life_amount'],
                            'latest_payment_date' => $hRow['latest_payment_date'],
                            'latest_year'         => $hRow['latest_year'],
                            'latest_month'        => (int)$hRow['latest_month'],
                            'latest_month_name'   => $hRow['latest_month_name']
                        ];
                    }
                }
            }
        }

        $viewData = [
            'current_route'            => 'ced-collection-statement',
            'current_page'             => 'ced-collection-statement',
            'page_title'               => 'CED Collection Statement',
            'filters'                  => $filters,
            'date_info'                => $dateInfo,
            'summary'                  => $summary,
            'collector_summary'        => $collectorSummary,
            'collector_history_notice' => $collectorHistoryNotice,
            'type_summary'             => $typeSummary,
            'view_mode'                => $viewMode,
            'transactions'             => $transactions,
            'collectors_list'          => $collectorsList,
            'collectors_by_scope'      => $collectorsByScope,
            'projects_list'            => $projectsList,
            'collector_projects_map'   => $collectorProjectsMap,
            'pagination'          => [
                'current_page' => $page,
                'total_pages'  => $totalPages,
                'total_records'=> $totalRecords,
                'limit'        => $limit,
                'is_all'       => $isAllRows,
                'limit_param'  => $limitParam
            ]
        ];

        $this->view('ced/collection-statement', $viewData);
    }

    /**
     * Print View for Filtered CED Statement
     */
    public function printStatement()
    {
        $filters = $this->parseFilters();
        $dateInfo = $this->resolveDateRange($filters);
        $summary = $this->fetchSummary($dateInfo['start_date'], $dateInfo['end_date'], $filters);
        $collectorSummary = $this->fetchCollectorSummary($dateInfo['start_date'], $dateInfo['end_date'], $filters);
        $typeSummary = $this->fetchPaymentTypeSummary($dateInfo['start_date'], $dateInfo['end_date'], $filters);
        $transactions = $this->fetchTransactions($dateInfo['start_date'], $dateInfo['end_date'], $filters, 5000, 0);
        $projects = $this->fetchProjects();

        $projectName = 'All Projects';
        if (!empty($filters['project_id'])) {
            foreach ($projects as $p) {
                if ($p['id'] == $filters['project_id']) {
                    $projectName = $p['name'];
                    break;
                }
            }
        }

        $collectorName = 'All Collectors / Executives';
        if (!empty($filters['collector_id'])) {
            $colList = $this->fetchCedCollectors($filters['team_scope'] ?? 'ced_only');
            foreach ($colList as $c) {
                if ($c['user_id'] == $filters['collector_id']) {
                    $collectorName = $c['full_name'];
                    break;
                }
            }
        }

        $printMode = $_GET['print_mode'] ?? ($_GET['view_mode'] ?? 'collectors');

        $viewData = [
            'page_title'        => 'CED Collection Statement Print',
            'filters'           => $filters,
            'date_info'         => $dateInfo,
            'summary'           => $summary,
            'collector_summary' => $collectorSummary,
            'type_summary'      => $typeSummary,
            'print_mode'        => $printMode,
            'transactions'      => $transactions,
            'project_name'      => $projectName,
            'collector_name'    => $collectorName
        ];

        extract($viewData);
        include __DIR__ . '/../views/ced/collection-statement-print.php';
        exit;
    }

    /**
     * Export Statement as CSV
     */
    public function exportCSV()
    {
        $filters = $this->parseFilters();
        $dateInfo = $this->resolveDateRange($filters);
        $exportMode = $_GET['export_mode'] ?? ($_GET['view_mode'] ?? 'collectors');

        if ($exportMode === 'collectors') {
            $colSummary = $this->fetchCollectorSummary($dateInfo['start_date'], $dateInfo['end_date'], $filters);
            $filename = "ced_executive_collection_summary_" . date('Ymd_His') . ".csv";
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename);

            $output = fopen('php://output', 'w');
            fputs($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'SL',
                'Collector / Executive Name',
                'Employee ID',
                'Assigned Projects',
                'Voucher Creators in Software',
                'Land Share Trx',
                'Land Share (BDT)',
                'Dev Charge Trx',
                'Dev Charge Amount (BDT)',
                'Regular EMI Trx',
                'Regular EMI (BDT)',
                'Advance EMI Trx',
                'Advance EMI (BDT)',
                'Booking & Reg Trx',
                'Booking & Reg Amount (BDT)',
                'Penalty Trx',
                'Penalty (BDT)',
                'Other Trx',
                'Other Amount (BDT)',
                'Cash Inflow (BDT)',
                'Bank / Cheque Inflow (BDT)',
                'Online Inflow (BDT)',
                'Total Trx Count',
                'Total Collected Amount (BDT)',
                '% Share of Collection'
            ]);

            $sl = 1;
            foreach ($colSummary['rows'] as $r) {
                $assignedPrjs = $r['assigned_projects_names'] ?? (!empty($r['assigned_projects']) ? implode(', ', array_column($r['assigned_projects'], 'name')) : 'N/A');
                $creators = $r['creators_list'] ?? 'N/A';
                fputcsv($output, [
                    $sl++,
                    $r['collector_name'],
                    $r['emp_code'] ?? 'N/A',
                    $assignedPrjs,
                    $creators,
                    $r['land_share_count'] ?? 0,
                    number_format($r['land_share_amount'] ?? 0, 2, '.', ''),
                    $r['dev_charge_count'],
                    number_format($r['dev_charge_amount'], 2, '.', ''),
                    $r['emi_count'],
                    number_format($r['emi_amount'], 2, '.', ''),
                    $r['advance_emi_count'] ?? 0,
                    number_format($r['advance_emi_amount'] ?? 0, 2, '.', ''),
                    $r['booking_count'] + $r['registration_count'],
                    number_format($r['booking_amount'] + $r['registration_amount'], 2, '.', ''),
                    $r['penalty_count'] ?? 0,
                    number_format($r['penalty_amount'] ?? 0, 2, '.', ''),
                    $r['other_count'],
                    number_format($r['other_amount'], 2, '.', ''),
                    number_format($r['cash_amount'], 2, '.', ''),
                    number_format($r['bank_amount'] + $r['cheque_amount'], 2, '.', ''),
                    number_format($r['online_amount'], 2, '.', ''),
                    $r['total_count'],
                    number_format($r['total_amount'], 2, '.', ''),
                    $r['percentage'] . '%'
                ]);
            }

            // Grand Total Row
            $grand = $colSummary['grand'];
            fputcsv($output, [
                '',
                'GRAND TOTAL',
                '',
                '',
                '',
                $grand['land_share_count'] ?? 0,
                number_format($grand['land_share_amount'] ?? 0, 2, '.', ''),
                $grand['dev_charge_count'],
                number_format($grand['dev_charge_amount'], 2, '.', ''),
                $grand['emi_count'],
                number_format($grand['emi_amount'], 2, '.', ''),
                $grand['advance_emi_count'] ?? 0,
                number_format($grand['advance_emi_amount'] ?? 0, 2, '.', ''),
                $grand['booking_count'] + $grand['registration_count'],
                number_format($grand['booking_amount'] + $grand['registration_amount'], 2, '.', ''),
                $grand['penalty_count'] ?? 0,
                number_format($grand['penalty_amount'] ?? 0, 2, '.', ''),
                $grand['other_count'],
                number_format($grand['other_amount'], 2, '.', ''),
                number_format($grand['cash_amount'], 2, '.', ''),
                number_format($grand['bank_amount'] + $grand['cheque_amount'], 2, '.', ''),
                number_format($grand['online_amount'], 2, '.', ''),
                $grand['total_count'],
                number_format($grand['total_amount'], 2, '.', ''),
                '100.00%'
            ]);

            fclose($output);
            exit;
        }

        // Detailed Transactions CSV
        $transactions = $this->fetchTransactions($dateInfo['start_date'], $dateInfo['end_date'], $filters, 10000, 0);

        $filename = "ced_collection_detailed_" . date('Ymd_His') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF");

        fputcsv($output, [
            'SL',
            'Payment Date',
            'Client / Depositor',
            'Phone',
            'Project',
            'Assigned CED Officer',
            'Flat / Unit',
            'Payment Purpose / Head',
            'Payment Method',
            'Receiver Bank (Company)',
            'Sender Bank (Client)',
            'Cheque No',
            'Receipt / Voucher Ref',
            'Amount (BDT)',
            'Created By / Staff',
            'Verification Status',
            'Verified By',
            'Verified At'
        ]);

        $sl = 1;
        foreach ($transactions as $t) {
            $companyBank = !empty($t['company_bank_name']) 
                ? $t['company_bank_name'] . (!empty($t['company_account_number']) ? ' (A/C: ' . $t['company_account_number'] . ')' : '')
                : 'N/A';

            $clientBank = !empty($t['bank_name'])
                ? $t['bank_name'] . (!empty($t['bank_account_no']) && !in_array(strtolower(trim($t['bank_account_no'])), ['n/a', '0', '00', 'none', 'null'], true) ? ' (A/C: ' . $t['bank_account_no'] . ')' : '')
                : 'N/A';

            $chequeNo = !empty($t['cheque_no']) ? $t['cheque_no'] : 'N/A';

            $purposeLabel = $t['purpose_info']['full_label'] ?? $t['purpose_info']['label'] ?? ucwords(str_replace('_', ' ', $t['payment_type'] ?? ''));
            if ($t['payment_type'] === 'emi' && !empty($t['settlement_type']) && empty($t['purpose_info'])) {
                $purposeLabel .= ' (Due Settlement)';
            }

            fputcsv($output, [
                $sl++,
                $t['payment_date'],
                $t['client_name'] ?? 'N/A',
                $t['client_phone'] ?? 'N/A',
                $t['project_name'] ?? 'N/A',
                !empty($t['ced_assigned_name']) ? $t['ced_assigned_name'] : 'Unassigned',
                $t['flat_name'] ?? 'N/A',
                $purposeLabel,
                ucfirst($t['payment_method'] ?? ''),
                $companyBank,
                $clientBank,
                $chequeNo,
                $t['transaction_ref'] ?? '',
                number_format((float)$t['amount'], 2, '.', ''),
                $t['collector_name'] ?? 'N/A',
                ucfirst($t['verification_status'] ?? 'pending'),
                (($t['verification_status'] ?? '') === 'verified') ? (!empty($t['verifier_name']) ? $t['verifier_name'] : 'System Verified') : 'Pending',
                (!empty($t['verified_at']) && ($t['verification_status'] ?? '') === 'verified') ? date('d-m-Y h:i A', strtotime($t['verified_at'])) : '-'
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Parse incoming GET parameters
     */
    protected function parseFilters()
    {
        $periodType = trim($_GET['period_type'] ?? 'monthly');
        $allowedPeriods = ['monthly', 'custom', 'daily', 'today', 'weekly', 'this_week', 'yearly', 'all_time'];
        if (!in_array($periodType, $allowedPeriods, true)) {
            $periodType = 'monthly';
        }

        $year = isset($_GET['year']) && is_numeric($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $month = isset($_GET['month']) ? trim($_GET['month']) : date('m');
        if ($month !== 'all' && (!is_numeric($month) || (int)$month < 1 || (int)$month > 12)) {
            $month = date('m');
        }

        $startDate = !empty($_GET['start_date']) ? trim($_GET['start_date']) : null;
        $endDate   = !empty($_GET['end_date'])   ? trim($_GET['end_date'])   : null;

        $collectorId = !empty($_GET['collector_id']) && is_numeric($_GET['collector_id']) ? (int)$_GET['collector_id'] : null;
        $teamScope   = trim($_GET['team_scope'] ?? 'ced_only'); // 'ced_only', 'ced_mgmt', or 'all'
        if (!in_array($teamScope, ['ced_only', 'ced_mgmt', 'all'], true)) {
            $teamScope = 'ced_only';
        }

        $projectId = !empty($_GET['project_id']) && is_numeric($_GET['project_id']) ? (int)$_GET['project_id'] : null;

        $paymentMethod = trim($_GET['payment_method'] ?? '');
        $allowedMethods = ['cash', 'bank', 'cheque', 'online'];
        if (!in_array($paymentMethod, $allowedMethods, true)) {
            $paymentMethod = '';
        }

        $paymentType = trim($_GET['payment_type'] ?? '');
        $allowedTypes = ['development_charge', 'emi', 'advance_emi', 'emi_settlement', 'booking_money', 'registration', 'land_share', 'shop_service_charge', 'others'];
        if (!in_array($paymentType, $allowedTypes, true)) {
            $paymentType = '';
        }

        $verificationStatus = trim($_GET['verification_status'] ?? '');
        if (!in_array($verificationStatus, ['verified', 'pending', 'rejected'], true)) {
            $verificationStatus = '';
        }

        $search = trim($_GET['search'] ?? '');

        return [
            'period_type'         => $periodType,
            'year'                => $year,
            'month'               => $month,
            'start_date'          => $startDate,
            'end_date'            => $endDate,
            'collector_id'        => $collectorId,
            'team_scope'          => $teamScope,
            'project_id'          => $projectId,
            'payment_method'      => $paymentMethod,
            'payment_type'        => $paymentType,
            'verification_status' => $verificationStatus,
            'search'              => $search
        ];
    }

    /**
     * Resolve date range (start_date, end_date, label)
     */
    protected function resolveDateRange($filters)
    {
        $pt = $filters['period_type'];
        $today = date('Y-m-d');

        if ($pt === 'daily' || $pt === 'today') {
            $lbl = 'Today (' . date('d M, Y') . ')';
            return [
                'start_date'   => $today,
                'end_date'     => $today,
                'label'        => $lbl,
                'period_label' => $lbl
            ];
        }

        if ($pt === 'weekly' || $pt === 'this_week') {
            $s = date('Y-m-d', strtotime('monday this week'));
            $e = date('Y-m-d', strtotime('sunday this week'));
            $lbl = 'This Week (' . date('d M', strtotime($s)) . ' - ' . date('d M, Y', strtotime($e)) . ')';
            return [
                'start_date'   => $s,
                'end_date'     => $e,
                'label'        => $lbl,
                'period_label' => $lbl
            ];
        }

        if ($pt === 'custom' && (!empty($filters['start_date']) || !empty($filters['end_date']))) {
            $s = !empty($filters['start_date']) ? $filters['start_date'] : '2015-01-01';
            $e = !empty($filters['end_date'])   ? $filters['end_date']   : date('Y-m-d');
            $lbl = date('d M, Y', strtotime($s)) . ' to ' . date('d M, Y', strtotime($e));
            return [
                'start_date'   => $s,
                'end_date'     => $e,
                'label'        => $lbl,
                'period_label' => $lbl
            ];
        }

        if ($pt === 'all_time') {
            return [
                'start_date'   => '2015-01-01',
                'end_date'     => '2099-12-31',
                'label'        => 'All Time',
                'period_label' => 'All Time'
            ];
        }

        if ($pt === 'yearly' || $filters['month'] === 'all') {
            $y = $filters['year'];
            $lbl = "Whole Year {$y}";
            return [
                'start_date'   => "{$y}-01-01",
                'end_date'     => "{$y}-12-31",
                'label'        => $lbl,
                'period_label' => $lbl
            ];
        }

        // Default: Monthly
        if (!empty($filters['month']) && strpos($filters['month'], '-') !== false) {
            $parts = explode('-', $filters['month']);
            $y = (int)$parts[0];
            $m = sprintf('%02d', (int)$parts[1]);
        } else {
            $y = !empty($filters['year']) ? (int)$filters['year'] : (int)date('Y');
            $m = !empty($filters['month']) ? sprintf('%02d', (int)$filters['month']) : date('m');
        }
        $s = "{$y}-{$m}-01";
        $e = date('Y-m-t', strtotime($s));
        $lbl = date('F Y', strtotime($s));
        return [
            'start_date'   => $s,
            'end_date'     => $e,
            'label'        => $lbl,
            'period_label' => $lbl
        ];
    }

    /**
     * Build WHERE conditions for lead_payments
     */
    protected function buildLeadPaymentsConditions($startDate, $endDate, $filters)
    {
        $conds = [
            "lp.payment_date BETWEEN '{$startDate}' AND '{$endDate}'",
            "lp.is_trashed = 0",
            "COALESCE(lp.is_emi_discount, 0) = 0",
            "lb.deleted_at IS NULL",
            "lb.booking_status NOT IN ('refunded', 'cancelled')",
            "(lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')",
            "lb.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))"
        ];

        $effAgentExpr = $this->getEffectiveAgentSql('lb.project_id');

        // Team scope: CED Only vs CED & Management vs All
        if (!empty($filters['team_scope']) && empty($filters['collector_id'])) {
            if ($filters['team_scope'] === 'ced_only') {
                $conds[] = "(u_cr.role_id IN (7, 37, 39) 
                             OR r_cr.name LIKE '%client%' 
                             OR r_cr.name LIKE '%recovery%' 
                             OR d_cr.name LIKE '%client%' 
                             OR d_cr.name LIKE '%ced%' 
                             OR {$effAgentExpr} > 0)";
            } elseif ($filters['team_scope'] === 'ced_mgmt') {
                $conds[] = "(u_cr.role_id IN (7, 37, 39, 1, 3, 33, 34, 35, 38) 
                             OR r_cr.name LIKE '%client%' 
                             OR r_cr.name LIKE '%recovery%' 
                             OR r_cr.name LIKE '%management%' 
                             OR r_cr.name LIKE '%admin%' 
                             OR d_cr.name LIKE '%client%' 
                             OR d_cr.name LIKE '%ced%' 
                             OR u_cr.user_name LIKE '%management%' 
                             OR {$effAgentExpr} > 0)";
            }
        }

        if (!empty($filters['collector_id'])) {
            $cId = (int)$filters['collector_id'];
            $conds[] = "({$effAgentExpr} = {$cId} OR ({$effAgentExpr} = 0 AND lp.created_by = {$cId}))";
        }

        if (!empty($filters['project_id'])) {
            $pId = (int)$filters['project_id'];
            $conds[] = "lb.project_id = {$pId}";
        }

        if (!empty($filters['payment_method'])) {
            $pm = $this->db->real_escape_string($filters['payment_method']);
            $conds[] = "lp.payment_method = '{$pm}'";
        }

        if (!empty($filters['payment_type'])) {
            $pt = $filters['payment_type'];
            if ($pt === 'advance_emi' || $pt === 'emi_settlement' || $pt === 'emi') {
                $conds[] = "lp.payment_type = 'emi'";
            } else {
                $ptEsc = $this->db->real_escape_string($pt);
                $conds[] = "lp.payment_type = '{$ptEsc}'";
            }
        }

        if (!empty($filters['verification_status'])) {
            $vs = $this->db->real_escape_string($filters['verification_status']);
            $conds[] = "lp.verification_status = '{$vs}'";
        }

        if (!empty($filters['search'])) {
            $s = $this->db->real_escape_string($filters['search']);
            $conds[] = "(l.full_name LIKE '%{$s}%' OR l.lead_name LIKE '%{$s}%' OR l.phone LIKE '%{$s}%' OR l.phone_number LIKE '%{$s}%' OR p.name LIKE '%{$s}%' OR f.flat_name LIKE '%{$s}%' OR lp.transaction_ref LIKE '%{$s}%' OR lp.cheque_no LIKE '%{$s}%' OR u_cr.user_name LIKE '%{$s}%' OR u_cr.full_name LIKE '%{$s}%' OR e_cr.full_name LIKE '%{$s}%' OR u_cpa.full_name LIKE '%{$s}%' OR e_cpa.full_name LIKE '%{$s}%')";
        }

        return implode(" AND ", $conds);
    }

    /**
     * Resolve the assigned CED agent for a payment at the time it was logged.
     * Uses ced_project_assignment_history date ranges so historical collections stay
     * under the previous agent when a project is reassigned, and falls back to the
     * active assignment if no history range matches.
     */
    protected function getEffectiveAgentSql(string $projectExpr = 'lb.project_id'): string
    {
        return "COALESCE(
            (
                SELECT h.user_id
                FROM ced_project_assignment_history h
                WHERE h.project_id = {$projectExpr}
                  AND h.assigned_from <= COALESCE(lp.created_at, CONCAT(lp.payment_date, ' 23:59:59'))
                  AND (h.assigned_to IS NULL OR h.assigned_to > COALESCE(lp.created_at, CONCAT(lp.payment_date, ' 23:59:59')))
                ORDER BY h.id DESC
                LIMIT 1
            ),
            (
                SELECT cpa_a.user_id
                FROM ced_project_assignments cpa_a
                WHERE cpa_a.project_id = {$projectExpr} AND cpa_a.status = 'active'
                  AND NOT EXISTS (
                      SELECT 1 FROM ced_project_assignment_history h0
                      WHERE h0.project_id = {$projectExpr}
                  )
                ORDER BY cpa_a.id DESC
                LIMIT 1
            ),
            0
        )";
    }

    /**
     * Common JOINs for queries
     */
    protected function getBaseJoins()
    {
        $effAgentExpr = $this->getEffectiveAgentSql('lb.project_id');
        return "
            INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
            INNER JOIN leads l ON lb.lead_id = l.id
            LEFT JOIN projects p ON lb.project_id = p.id
            LEFT JOIN flats f ON lb.flat_id = f.id
            LEFT JOIN users u_cr ON lp.created_by = u_cr.id
            LEFT JOIN employees e_cr ON (u_cr.employee_id = e_cr.id OR e_cr.user_id = u_cr.id)
            LEFT JOIN roles r_cr ON u_cr.role_id = r_cr.id
            LEFT JOIN departments d_cr ON e_cr.department_id = d_cr.id
            LEFT JOIN users u_cpa ON u_cpa.id = {$effAgentExpr}
            LEFT JOIN employees e_cpa ON (u_cpa.employee_id = e_cpa.id OR e_cpa.user_id = u_cpa.id)
            LEFT JOIN roles r_cpa ON u_cpa.role_id = r_cpa.id
        ";
    }



    /**
     * Helper to format amounts cleanly into Cr / L / BDT
     */
    public static function formatCurrencyTk($v): string
    {
        $v = (float) $v;
        if ($v >= 10000000) {
            return '৳' . number_format($v / 10000000, 2) . ' Cr';
        }
        if ($v >= 100000) {
            return '৳' . number_format($v / 100000, 2) . ' L';
        }
        return '৳' . number_format($v, 2);
    }

    /**
     * Split EMI collections into Regular EMI (up to Asked Amount) and Advance EMI (surplus),
     * automatically adjusting any prior advance against new asks.
     */
    protected function calculateEmiSplit($startDate, $endDate, $filters)
    {
        $cacheKey = md5($startDate . '|' . $endDate . '|' . json_encode($filters));
        if (isset($this->emiSplitCache[$cacheKey])) {
            return $this->emiSplitCache[$cacheKey];
        }

        $where = $this->buildLeadPaymentsConditions($startDate, $endDate, $filters);
        $joins = $this->getBaseJoins();
        $isFilterRejected = (!empty($filters['verification_status']) && $filters['verification_status'] === 'rejected');
        $validCond = $isFilterRejected ? "1=1" : "(lp.verification_status IS NULL OR lp.verification_status != 'rejected')";
        $effAgentPrj = $this->getEffectiveAgentSql('lb.project_id');

        $sql = "
            SELECT
                lp.id,
                lp.booking_id,
                lb.project_id,
                lp.created_by,
                lp.amount,
                lp.payment_method,
                lp.payment_date,
                COALESCE(NULLIF({$effAgentPrj}, 0), CASE WHEN (u_cr.role_id IN (7, 37, 39) OR e_cr.department_id IN (4, 29)) THEN lp.created_by ELSE 0 END, 0) AS ced_officer_id
            FROM lead_payments lp
            {$joins}
            WHERE {$where}
              AND lp.payment_type = 'emi'
              AND {$validCond}
            ORDER BY lp.booking_id ASC, lp.payment_date ASC, lp.id ASC
        ";

        $payments = [];
        $bookingIds = [];
        $res = $this->db->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $bId = (int)$row['booking_id'];
                $payments[] = $row;
                $bookingIds[$bId] = true;
            }
        }

        $result = [
            'total' => [
                'regular_emi_amount' => 0.0,
                'regular_emi_count'  => 0,
                'advance_emi_amount' => 0.0,
                'advance_emi_count'  => 0,
                'regular_methods'    => ['cash' => 0.0, 'bank' => 0.0, 'cheque' => 0.0, 'online' => 0.0],
                'advance_methods'    => ['cash' => 0.0, 'bank' => 0.0, 'cheque' => 0.0, 'online' => 0.0],
            ],
            'by_creator'         => [],
            'by_project_officer' => [],
        ];

        if (empty($payments)) {
            $this->emiSplitCache[$cacheKey] = $result;
            return $result;
        }

        $inIds = implode(',', array_keys($bookingIds));
        $startDateEsc = $this->db->real_escape_string($startDate);
        $endDateEsc   = $this->db->real_escape_string($endDate);

        $schedulesByBooking = [];
        $qSched = $this->db->query("
            SELECT
                es.lead_booking_id,
                COUNT(*) AS sched_count,
                COALESCE(SUM(
                    CASE
                        WHEN es.status != 'cancelled'
                             AND (
                                 (EXISTS (SELECT 1 FROM project_emi_schedule_items px WHERE px.project_id = lb_es.project_id) AND pesi.is_active = 1)
                                 OR (NOT EXISTS (SELECT 1 FROM project_emi_schedule_items px WHERE px.project_id = lb_es.project_id) AND es.due_date <= '{$endDateEsc}')
                             )
                        THEN es.emi_amount
                        ELSE 0
                    END
                ), 0) AS asked_upto_end
            FROM emi_schedules es
            INNER JOIN lead_bookings lb_es ON lb_es.id = es.lead_booking_id
            LEFT JOIN project_emi_schedule_items pesi ON pesi.project_id = lb_es.project_id AND pesi.emi_no = es.emi_no
            WHERE es.lead_booking_id IN ({$inIds})
            GROUP BY es.lead_booking_id
        ");
        if ($qSched) {
            while ($sr = $qSched->fetch_assoc()) {
                $schedulesByBooking[(int)$sr['lead_booking_id']] = [
                    'sched_count'    => (int)$sr['sched_count'],
                    'asked_upto_end' => (float)$sr['asked_upto_end'],
                ];
            }
        }

        $paidBeforeByBooking = [];
        $qPrior = $this->db->query("
            SELECT
                booking_id,
                COALESCE(SUM(amount), 0) AS paid_before_start
            FROM lead_payments
            WHERE booking_id IN ({$inIds})
              AND payment_type = 'emi'
              AND is_trashed = 0
              AND COALESCE(is_emi_discount, 0) = 0
              AND (verification_status IS NULL OR verification_status != 'rejected')
              AND payment_date < '{$startDateEsc}'
            GROUP BY booking_id
        ");
        if ($qPrior) {
            while ($pr = $qPrior->fetch_assoc()) {
                $paidBeforeByBooking[(int)$pr['booking_id']] = (float)$pr['paid_before_start'];
            }
        }

        $remainingByBooking = [];
        foreach ($payments as $p) {
            $bId       = (int)$p['booking_id'];
            $pId       = (int)$p['project_id'];
            $creatorId = (int)$p['created_by'];
            $officerId = (int)$p['ced_officer_id'];
            $amt       = (float)$p['amount'];
            $method    = strtolower(trim($p['payment_method'] ?? ''));

            $schedInfo  = $schedulesByBooking[$bId] ?? ['sched_count' => 0, 'asked_upto_end' => 0.0];
            $schedCount = $schedInfo['sched_count'];

            if (!array_key_exists($bId, $remainingByBooking)) {
                $paidBefore = $paidBeforeByBooking[$bId] ?? 0.0;
                $remainingByBooking[$bId] = max(0.0, round($schedInfo['asked_upto_end'] - $paidBefore, 2));
            }

            if ($schedCount === 0) {
                $reg = round($amt, 2);
                $adv = 0.0;
            } else {
                $reg = round(min($amt, $remainingByBooking[$bId]), 2);
                $adv = round(max(0.0, $amt - $reg), 2);
                $remainingByBooking[$bId] = max(0.0, round($remainingByBooking[$bId] - $reg, 2));
            }

            // Filter by payment_type if user explicitly filtered for advance_emi or emi
            $ptFilter = $filters['payment_type'] ?? '';
            if ($ptFilter === 'advance_emi' && $adv <= 0) {
                continue;
            }

            $result['total']['regular_emi_amount'] += $reg;
            $result['total']['advance_emi_amount'] += $adv;
            if ($reg > 0) $result['total']['regular_emi_count']++;
            if ($adv > 0) $result['total']['advance_emi_count']++;

            if (isset($result['total']['regular_methods'][$method])) {
                $result['total']['regular_methods'][$method] += $reg;
                $result['total']['advance_methods'][$method] += $adv;
            }

            if (!isset($result['by_creator'][$creatorId])) {
                $result['by_creator'][$creatorId] = [
                    'regular_emi_amount' => 0.0,
                    'regular_emi_count'  => 0,
                    'advance_emi_amount' => 0.0,
                    'advance_emi_count'  => 0,
                ];
            }
            $result['by_creator'][$creatorId]['regular_emi_amount'] += $reg;
            $result['by_creator'][$creatorId]['advance_emi_amount'] += $adv;
            if ($reg > 0) $result['by_creator'][$creatorId]['regular_emi_count']++;
            if ($adv > 0) $result['by_creator'][$creatorId]['advance_emi_count']++;

            $poKey = $pId . '_' . $officerId;
            if (!isset($result['by_project_officer'][$poKey])) {
                $result['by_project_officer'][$poKey] = [
                    'regular_emi_amount' => 0.0,
                    'regular_emi_count'  => 0,
                    'advance_emi_amount' => 0.0,
                    'advance_emi_count'  => 0,
                ];
            }
            $result['by_project_officer'][$poKey]['regular_emi_amount'] += $reg;
            $result['by_project_officer'][$poKey]['advance_emi_amount'] += $adv;
            if ($reg > 0) $result['by_project_officer'][$poKey]['regular_emi_count']++;
            if ($adv > 0) $result['by_project_officer'][$poKey]['advance_emi_count']++;
        }

        $this->emiSplitCache[$cacheKey] = $result;
        return $result;
    }

    protected function fetchSummary($startDate, $endDate, $filters)
    {
        $where = $this->buildLeadPaymentsConditions($startDate, $endDate, $filters);
        $joins = $this->getBaseJoins();

        $isFilterRejected = (!empty($filters['verification_status']) && $filters['verification_status'] === 'rejected');
        $validCond = $isFilterRejected ? "1=1" : "(lp.verification_status IS NULL OR lp.verification_status != 'rejected')";

        $sql = "
            SELECT 
                COUNT(CASE WHEN {$validCond} THEN 1 ELSE NULL END) as total_count,
                COALESCE(SUM(CASE WHEN {$validCond} THEN lp.amount ELSE 0 END), 0) as total_amount,
                COALESCE(SUM(CASE WHEN lp.payment_type = 'land_share' AND {$validCond} THEN lp.amount ELSE 0 END), 0) as land_share_amount,
                COUNT(CASE WHEN lp.payment_type = 'land_share' AND {$validCond} THEN 1 ELSE NULL END) as land_share_count,
                COALESCE(SUM(CASE WHEN lp.payment_type = 'development_charge' AND {$validCond} THEN lp.amount ELSE 0 END), 0) as dev_charge_amount,
                COUNT(CASE WHEN lp.payment_type = 'development_charge' AND {$validCond} THEN 1 ELSE NULL END) as dev_charge_count,
                COALESCE(SUM(CASE WHEN lp.payment_type = 'emi' AND (lp.settlement_type IS NULL OR lp.settlement_type != 'emi_due_settlement') AND {$validCond} THEN lp.amount ELSE 0 END), 0) as emi_amount,
                COUNT(CASE WHEN lp.payment_type = 'emi' AND (lp.settlement_type IS NULL OR lp.settlement_type != 'emi_due_settlement') AND {$validCond} THEN 1 ELSE NULL END) as emi_count,
                COALESCE(SUM(CASE WHEN lp.payment_type = 'emi' AND lp.settlement_type = 'emi_due_settlement' AND {$validCond} THEN lp.amount ELSE 0 END), 0) as settlement_amount,
                COUNT(CASE WHEN lp.payment_type = 'emi' AND lp.settlement_type = 'emi_due_settlement' AND {$validCond} THEN 1 ELSE NULL END) as settlement_count,
                COALESCE(SUM(CASE WHEN lp.payment_type = 'booking_money' AND {$validCond} THEN lp.amount ELSE 0 END), 0) as booking_amount,
                COUNT(CASE WHEN lp.payment_type = 'booking_money' AND {$validCond} THEN 1 ELSE NULL END) as booking_count,
                COALESCE(SUM(CASE WHEN lp.payment_type = 'registration' AND {$validCond} THEN lp.amount ELSE 0 END), 0) as registration_amount,
                COUNT(CASE WHEN lp.payment_type = 'registration' AND {$validCond} THEN 1 ELSE NULL END) as registration_count,
                COALESCE(SUM(CASE WHEN {$validCond} THEN COALESCE(lp.penalty_paid, 0) ELSE 0 END), 0) as penalty_amount,
                COUNT(CASE WHEN {$validCond} AND lp.penalty_paid > 0 THEN 1 ELSE NULL END) as penalty_count,
                COALESCE(SUM(CASE WHEN lp.payment_method = 'cash' AND {$validCond} THEN lp.amount ELSE 0 END), 0) as cash_amount,
                COUNT(CASE WHEN lp.payment_method = 'cash' AND {$validCond} THEN 1 ELSE NULL END) as cash_count,
                COALESCE(SUM(CASE WHEN lp.payment_method = 'bank' AND {$validCond} THEN lp.amount ELSE 0 END), 0) as bank_amount,
                COUNT(CASE WHEN lp.payment_method = 'bank' AND {$validCond} THEN 1 ELSE NULL END) as bank_count,
                COALESCE(SUM(CASE WHEN lp.payment_method = 'cheque' AND {$validCond} THEN lp.amount ELSE 0 END), 0) as cheque_amount,
                COUNT(CASE WHEN lp.payment_method = 'cheque' AND {$validCond} THEN 1 ELSE NULL END) as cheque_count,
                COALESCE(SUM(CASE WHEN lp.payment_method = 'online' AND {$validCond} THEN lp.amount ELSE 0 END), 0) as online_amount,
                COUNT(CASE WHEN lp.payment_method = 'online' AND {$validCond} THEN 1 ELSE NULL END) as online_count,
                COALESCE(SUM(CASE WHEN lp.verification_status = 'verified' THEN lp.amount ELSE 0 END), 0) as verified_amount,
                COALESCE(SUM(CASE WHEN lp.verification_status = 'pending' THEN lp.amount ELSE 0 END), 0) as pending_amount,
                COUNT(DISTINCT CASE WHEN {$validCond} THEN lp.created_by ELSE NULL END) as active_collectors_count
            FROM lead_payments lp
            {$joins}
            WHERE {$where}
        ";

        $res = $this->db->query($sql);
        $emiSplit = $this->calculateEmiSplit($startDate, $endDate, $filters);

        $summary = [
            'total_count' => 0, 'total_amount' => 0, 'dev_charge_amount' => 0, 'dev_charge_count' => 0,
            'land_share_amount' => 0, 'land_share_count' => 0,
            'penalty_amount' => 0, 'penalty_count' => 0,
            'emi_amount' => 0, 'emi_count' => 0, 'advance_emi_amount' => 0, 'advance_emi_count' => 0,
            'total_emi_amount' => 0, 'total_emi_count' => 0, 'booking_amount' => 0, 'booking_count' => 0,
            'registration_amount' => 0, 'registration_count' => 0, 'cash_amount' => 0, 'cash_count' => 0,
            'bank_amount' => 0, 'bank_count' => 0, 'cheque_amount' => 0, 'cheque_count' => 0,
            'online_amount' => 0, 'online_count' => 0, 'verified_amount' => 0, 'pending_amount' => 0,
            'active_collectors_count' => 0
        ];

        if ($res && $row = $res->fetch_assoc()) {
            $regEmiAmt = $emiSplit['total']['regular_emi_amount'];
            $regEmiCnt = $emiSplit['total']['regular_emi_count'];
            $advEmiAmt = $emiSplit['total']['advance_emi_amount'];
            $advEmiCnt = $emiSplit['total']['advance_emi_count'];

            $summary = [
                'total_count'             => (int)$row['total_count'],
                'total_amount'            => (float)$row['total_amount'],
                'land_share_amount'       => (float)($row['land_share_amount'] ?? 0),
                'land_share_count'        => (int)($row['land_share_count'] ?? 0),
                'dev_charge_amount'       => (float)$row['dev_charge_amount'],
                'dev_charge_count'        => (int)$row['dev_charge_count'],
                'penalty_amount'          => (float)($row['penalty_amount'] ?? 0),
                'penalty_count'           => (int)($row['penalty_count'] ?? 0),
                'emi_amount'              => $regEmiAmt,
                'emi_count'               => $regEmiCnt,
                'advance_emi_amount'      => $advEmiAmt,
                'advance_emi_count'       => $advEmiCnt,
                'total_emi_amount'        => $regEmiAmt + $advEmiAmt,
                'total_emi_count'         => (int)$row['emi_count'] + (int)$row['settlement_count'],
                'booking_amount'          => (float)$row['booking_amount'],
                'booking_count'           => (int)$row['booking_count'],
                'registration_amount'     => (float)$row['registration_amount'],
                'registration_count'      => (int)$row['registration_count'],
                'cash_amount'             => (float)$row['cash_amount'],
                'cash_count'              => (int)$row['cash_count'],
                'bank_amount'             => (float)$row['bank_amount'],
                'bank_count'              => (int)$row['bank_count'],
                'cheque_amount'           => (float)$row['cheque_amount'],
                'cheque_count'            => (int)$row['cheque_count'],
                'online_amount'           => (float)$row['online_amount'],
                'online_count'            => (int)$row['online_count'],
                'verified_amount'         => (float)$row['verified_amount'],
                'pending_amount'          => (float)$row['pending_amount'],
                'active_collectors_count' => (int)$row['active_collectors_count']
            ];
        }

        // Total Sold Value & Bookings (from active bookings in lead_bookings)
        $condsSold = [
            "lb.deleted_at IS NULL",
            "lb.booking_status NOT IN ('refunded', 'cancelled')",
            "(lb.landshare_status IS NULL OR lb.landshare_status != 'cancelled')",
            "lb.id NOT IN (SELECT crr.booking_id FROM client_refund_requests crr WHERE crr.booking_id IS NOT NULL AND crr.status IN ('pending', 'approved', 'refunded'))"
        ];
        if (!empty($filters['project_id'])) {
            $pId = (int)$filters['project_id'];
            $condsSold[] = "lb.project_id = {$pId}";
        }
        $whereSold = implode(" AND ", $condsSold);
        $sqlSold = "
            SELECT 
                COUNT(DISTINCT lb.id) as total_sold_units,
                COUNT(DISTINCT lb.lead_id) as total_sold_clients,
                COALESCE(SUM(COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0)), 0) - COALESCE(SUM(lb.discount_amount), 0) as total_sold_value
            FROM lead_bookings lb
            LEFT JOIN flats f ON lb.flat_id = f.id
            WHERE {$whereSold}
        ";
        $resSold = $this->db->query($sqlSold);
        $summary['total_sold_units']   = 0;
        $summary['total_sold_clients'] = 0;
        $summary['total_sold_value']   = 0.0;
        if ($resSold && $rowSold = $resSold->fetch_assoc()) {
            $summary['total_sold_units']   = (int)$rowSold['total_sold_units'];
            $summary['total_sold_clients'] = (int)$rowSold['total_sold_clients'];
            $summary['total_sold_value']   = (float)$rowSold['total_sold_value'];
        }

        $summary['pending_due'] = max(0, $summary['total_sold_value'] - $summary['total_amount']);
        $summary['collection_rate'] = $summary['total_sold_value'] > 0
            ? round(($summary['total_amount'] / $summary['total_sold_value']) * 100, 1)
            : 0;

        $summary['total_sold_value_formatted'] = self::formatCurrencyTk($summary['total_sold_value']);
        $summary['total_collection_formatted'] = self::formatCurrencyTk($summary['total_amount']);
        $summary['pending_due_formatted']      = self::formatCurrencyTk($summary['pending_due']);

        return $summary;
    }

    /**
     * Fetch Executive-wise / Collector-wise Collection Summary Matrix
     */
    protected function fetchCollectorSummary($startDate, $endDate, $filters)
    {
        $where = $this->buildLeadPaymentsConditions($startDate, $endDate, $filters);
        $joins = $this->getBaseJoins();

        $isFilterRejected = (!empty($filters['verification_status']) && $filters['verification_status'] === 'rejected');
        $validCond = $isFilterRejected ? "1=1" : "(lp.verification_status IS NULL OR lp.verification_status != 'rejected')";

        $sql = " SELECT 
                lp.created_by,
                COALESCE(e_cr.full_name, u_cr.full_name, u_cr.user_name, CONCAT('User #', lp.created_by)) AS collector_name,
                COALESCE(r_cr.name, 'N/A') AS collector_role,
                COALESCE(r_cr.id, 0) AS role_id,
                e_cr.employee_id AS emp_code,
                d_cr.name AS dept_name,
                COUNT(CASE WHEN {$validCond} THEN 1 ELSE NULL END) AS total_count,
                COALESCE(SUM(CASE WHEN {$validCond} THEN lp.amount ELSE 0 END), 0) AS total_amount,
                COALESCE(SUM(CASE WHEN lp.payment_type = 'land_share' AND {$validCond} THEN lp.amount ELSE 0 END), 0) AS land_share_amount,
                COUNT(CASE WHEN lp.payment_type = 'land_share' AND {$validCond} THEN 1 ELSE NULL END) AS land_share_count,
                COALESCE(SUM(CASE WHEN lp.payment_type = 'development_charge' AND {$validCond} THEN lp.amount ELSE 0 END), 0) AS dev_charge_amount,
                COUNT(CASE WHEN lp.payment_type = 'development_charge' AND {$validCond} THEN 1 ELSE NULL END) AS dev_charge_count,
                COALESCE(SUM(CASE WHEN lp.payment_type = 'emi' AND (lp.settlement_type IS NULL OR lp.settlement_type != 'emi_due_settlement') AND {$validCond} THEN lp.amount ELSE 0 END), 0) AS emi_amount,
                COUNT(CASE WHEN lp.payment_type = 'emi' AND (lp.settlement_type IS NULL OR lp.settlement_type != 'emi_due_settlement') AND {$validCond} THEN 1 ELSE NULL END) AS emi_count,
                COALESCE(SUM(CASE WHEN lp.payment_type = 'emi' AND lp.settlement_type = 'emi_due_settlement' AND {$validCond} THEN lp.amount ELSE 0 END), 0) AS settlement_amount,
                COUNT(CASE WHEN lp.payment_type = 'emi' AND lp.settlement_type = 'emi_due_settlement' AND {$validCond} THEN 1 ELSE NULL END) AS settlement_count,
                COALESCE(SUM(CASE WHEN lp.payment_type = 'booking_money' AND {$validCond} THEN lp.amount ELSE 0 END), 0) AS booking_amount,
                COUNT(CASE WHEN lp.payment_type = 'booking_money' AND {$validCond} THEN 1 ELSE NULL END) AS booking_count,
                COALESCE(SUM(CASE WHEN lp.payment_type = 'registration' AND {$validCond} THEN lp.amount ELSE 0 END), 0) AS registration_amount,
                COUNT(CASE WHEN lp.payment_type = 'registration' AND {$validCond} THEN 1 ELSE NULL END) AS registration_count,
                COALESCE(SUM(CASE WHEN {$validCond} THEN COALESCE(lp.penalty_paid, 0) ELSE 0 END), 0) AS penalty_amount,
                COUNT(CASE WHEN {$validCond} AND lp.penalty_paid > 0 THEN 1 ELSE NULL END) AS penalty_count,
                COALESCE(SUM(CASE WHEN lp.payment_type NOT IN ('development_charge', 'emi', 'booking_money', 'registration', 'land_share') AND {$validCond} THEN lp.amount ELSE 0 END), 0) AS other_amount,
                COUNT(CASE WHEN lp.payment_type NOT IN ('development_charge', 'emi', 'booking_money', 'registration', 'land_share') AND {$validCond} THEN 1 ELSE NULL END) AS other_count,
                COALESCE(SUM(CASE WHEN lp.payment_method = 'cash' AND {$validCond} THEN lp.amount ELSE 0 END), 0) AS cash_amount,
                COALESCE(SUM(CASE WHEN lp.payment_method = 'bank' AND {$validCond} THEN lp.amount ELSE 0 END), 0) AS bank_amount,
                COALESCE(SUM(CASE WHEN lp.payment_method = 'cheque' AND {$validCond} THEN lp.amount ELSE 0 END), 0) AS cheque_amount,
                COALESCE(SUM(CASE WHEN lp.payment_method = 'online' AND {$validCond} THEN lp.amount ELSE 0 END), 0) AS online_amount,
                COALESCE(SUM(CASE WHEN lp.verification_status = 'verified' THEN lp.amount ELSE 0 END), 0) AS verified_amount,
                COALESCE(SUM(CASE WHEN lp.verification_status = 'pending' THEN lp.amount ELSE 0 END), 0) AS pending_amount
            FROM lead_payments lp
            {$joins}
            WHERE {$where}
            GROUP BY 
                lp.created_by,
                u_cr.full_name,
                u_cr.user_name,
                e_cr.full_name,
                e_cr.employee_id,
                r_cr.name,
                r_cr.id,
                d_cr.name
            HAVING total_count > 0 OR total_amount > 0
            ORDER BY total_amount DESC
        ";

        $emiSplit = $this->calculateEmiSplit($startDate, $endDate, $filters);

        $rows = [];
        $grand = [
            'total_count'        => 0,
            'total_amount'       => 0.0,
            'land_share_amount'  => 0.0,
            'land_share_count'   => 0,
            'dev_charge_amount'  => 0.0,
            'dev_charge_count'   => 0,
            'emi_amount'         => 0.0,
            'emi_count'          => 0,
            'advance_emi_amount' => 0.0,
            'advance_emi_count'  => 0,
            'booking_amount'     => 0.0,
            'booking_count'      => 0,
            'registration_amount'=> 0.0,
            'registration_count' => 0,
            'penalty_amount'     => 0.0,
            'penalty_count'      => 0,
            'other_amount'       => 0.0,
            'other_count'        => 0,
            'cash_amount'        => 0.0,
            'bank_amount'        => 0.0,
            'cheque_amount'      => 0.0,
            'online_amount'      => 0.0
        ];

        $res = $this->db->query($sql);
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $creatorId = (int)$r['created_by'];
                $creatorSplit = $emiSplit['by_creator'][$creatorId] ?? ['regular_emi_amount' => 0.0, 'regular_emi_count' => 0, 'advance_emi_amount' => 0.0, 'advance_emi_count' => 0];

                $totalAmt = (float)$r['total_amount'];
                $r['total_amount']       = $totalAmt;
                $r['total_count']        = (int)$r['total_count'];
                $r['land_share_amount']  = (float)($r['land_share_amount'] ?? 0);
                $r['land_share_count']   = (int)($r['land_share_count'] ?? 0);
                $r['dev_charge_amount']  = (float)$r['dev_charge_amount'];
                $r['dev_charge_count']   = (int)$r['dev_charge_count'];
                $r['emi_amount']         = $creatorSplit['regular_emi_amount'];
                $r['emi_count']          = $creatorSplit['regular_emi_count'];
                $r['advance_emi_amount'] = $creatorSplit['advance_emi_amount'];
                $r['advance_emi_count']  = $creatorSplit['advance_emi_count'];
                $r['booking_amount']     = (float)$r['booking_amount'];
                $r['booking_count']      = (int)$r['booking_count'];
                $r['registration_amount']= (float)$r['registration_amount'];
                $r['registration_count'] = (int)$r['registration_count'];
                $r['penalty_amount']     = (float)($r['penalty_amount'] ?? 0);
                $r['penalty_count']      = (int)($r['penalty_count'] ?? 0);
                $r['other_amount']       = (float)$r['other_amount'];
                $r['other_count']        = (int)$r['other_count'];
                $r['cash_amount']        = (float)$r['cash_amount'];
                $r['bank_amount']        = (float)$r['bank_amount'];
                $r['cheque_amount']      = (float)$r['cheque_amount'];
                $r['online_amount']      = (float)$r['online_amount'];
                $r['is_ced_member']      = in_array((int)$r['role_id'], [7, 37, 39], true) || stripos($r['collector_role'], 'client') !== false;
                $r['is_management']      = in_array((int)$r['role_id'], [1, 3, 33, 34, 35, 38], true) || stripos($r['collector_role'], 'admin') !== false || stripos($r['collector_role'], 'management') !== false || (isset($r['user_name']) && stripos($r['user_name'], 'management') !== false);

                $grand['total_count']        += $r['total_count'];
                $grand['total_amount']       += $r['total_amount'];
                $grand['land_share_amount']  += $r['land_share_amount'];
                $grand['land_share_count']   += $r['land_share_count'];
                $grand['dev_charge_amount']  += $r['dev_charge_amount'];
                $grand['dev_charge_count']   += $r['dev_charge_count'];
                $grand['emi_amount']         += $r['emi_amount'];
                $grand['emi_count']          += $r['emi_count'];
                $grand['advance_emi_amount'] += $r['advance_emi_amount'];
                $grand['advance_emi_count']  += $r['advance_emi_count'];
                $grand['booking_amount']     += $r['booking_amount'];
                $grand['booking_count']      += $r['booking_count'];
                $grand['registration_amount']+= $r['registration_amount'];
                $grand['registration_count'] += $r['registration_count'];
                $grand['penalty_amount']     += $r['penalty_amount'];
                $grand['penalty_count']      += $r['penalty_count'];
                $grand['other_amount']       += $r['other_amount'];
                $grand['other_count']        += $r['other_count'];
                $grand['cash_amount']        += $r['cash_amount'];
                $grand['bank_amount']        += $r['bank_amount'];
                $grand['cheque_amount']      += $r['cheque_amount'];
                $grand['online_amount']      += $r['online_amount'];

                $rows[] = $r;
            }
        }

        // Compute percentages for creator rows
        foreach ($rows as &$item) {
            $item['percentage'] = $grand['total_amount'] > 0 ? round(($item['total_amount'] / $grand['total_amount']) * 100, 2) : 0.0;
        }
        unset($item);

        // ── Portfolio & Project-wise mapping breakdown ───────────────────────
        $assignedProjectsByAgent = [];
        $qAssigned = $this->db->query("
            SELECT cpa.user_id, p.id AS project_id, p.name AS project_name
            FROM ced_project_assignments cpa
            INNER JOIN projects p ON cpa.project_id = p.id
            WHERE cpa.status = 'active'
            ORDER BY p.name ASC
        ");
        if ($qAssigned) {
            while ($r = $qAssigned->fetch_assoc()) {
                $assignedProjectsByAgent[$r['user_id']][] = [
                    'id'   => (int)$r['project_id'],
                    'name' => $r['project_name']
                ];
            }
        }

        $allCedOfficers = [];
        $qOfficers = $this->db->query("
            SELECT DISTINCT
                u.id AS user_id,
                COALESCE(e.full_name, u.full_name, u.user_name) AS full_name,
                e.employee_id AS emp_code,
                COALESCE(r.name, 'Client Experience') AS role_name,
                COALESCE(r.id, 0) AS role_id
            FROM users u
            LEFT JOIN employees e ON (u.employee_id = e.id OR e.user_id = u.id)
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN departments d ON e.department_id = d.id
            WHERE e.department_id IN (4, 29)
               OR u.role_id IN (7, 37, 39)
               OR u.id IN (SELECT DISTINCT user_id FROM ced_project_assignments)
               OR u.id IN (SELECT DISTINCT user_id FROM ced_project_assignment_history)
            ORDER BY full_name ASC
        ");
        if ($qOfficers) {
            while ($r = $qOfficers->fetch_assoc()) {
                $allCedOfficers[$r['user_id']] = $r;
            }
        }

        $effAgentPrj = $this->getEffectiveAgentSql('p.id');
        $sqlPrj = "
            SELECT 
                p.id AS project_id,
                p.name AS project_name,
                COALESCE(NULLIF({$effAgentPrj}, 0), CASE WHEN (u_cr.role_id IN (7, 37, 39) OR e_cr.department_id IN (4, 29)) THEN lp.created_by ELSE 0 END, 0) AS ced_officer_id,
                COALESCE(e_cpa.full_name, u_cpa.full_name, u_cpa.user_name, e_cr.full_name, u_cr.full_name, u_cr.user_name, 'Unassigned') AS ced_officer_name,
                COALESCE(r_cpa.name, r_cr.name, 'N/A') AS ced_officer_role,
                COALESCE(e_cpa.employee_id, e_cr.employee_id, '') AS ced_emp_code,
                COUNT(CASE WHEN {$validCond} THEN 1 ELSE NULL END) AS total_count,
                COALESCE(SUM(CASE WHEN {$validCond} THEN lp.amount ELSE 0 END), 0) AS total_amount,
                COALESCE(SUM(CASE WHEN lp.payment_type = 'land_share' AND {$validCond} THEN lp.amount ELSE 0 END), 0) AS land_share_amount,
                COUNT(CASE WHEN lp.payment_type = 'land_share' AND {$validCond} THEN 1 ELSE NULL END) AS land_share_count,
                COALESCE(SUM(CASE WHEN lp.payment_type = 'development_charge' AND {$validCond} THEN lp.amount ELSE 0 END), 0) AS dev_charge_amount,
                COUNT(CASE WHEN lp.payment_type = 'development_charge' AND {$validCond} THEN 1 ELSE NULL END) AS dev_charge_count,
                COALESCE(SUM(CASE WHEN lp.payment_type = 'emi' AND (lp.settlement_type IS NULL OR lp.settlement_type != 'emi_due_settlement') AND {$validCond} THEN lp.amount ELSE 0 END), 0) AS emi_amount,
                COUNT(CASE WHEN lp.payment_type = 'emi' AND (lp.settlement_type IS NULL OR lp.settlement_type != 'emi_due_settlement') AND {$validCond} THEN 1 ELSE NULL END) AS emi_count,
                COALESCE(SUM(CASE WHEN lp.payment_type = 'emi' AND lp.settlement_type = 'emi_due_settlement' AND {$validCond} THEN lp.amount ELSE 0 END), 0) AS settlement_amount,
                COUNT(CASE WHEN lp.payment_type = 'emi' AND lp.settlement_type = 'emi_due_settlement' AND {$validCond} THEN 1 ELSE NULL END) AS settlement_count,
                COALESCE(SUM(CASE WHEN lp.payment_type = 'booking_money' AND {$validCond} THEN lp.amount ELSE 0 END), 0) AS booking_amount,
                COUNT(CASE WHEN lp.payment_type = 'booking_money' AND {$validCond} THEN 1 ELSE NULL END) AS booking_count,
                COALESCE(SUM(CASE WHEN lp.payment_type = 'registration' AND {$validCond} THEN lp.amount ELSE 0 END), 0) AS registration_amount,
                COUNT(CASE WHEN lp.payment_type = 'registration' AND {$validCond} THEN 1 ELSE NULL END) AS registration_count,
                COALESCE(SUM(CASE WHEN {$validCond} THEN COALESCE(lp.penalty_paid, 0) ELSE 0 END), 0) AS penalty_amount,
                COUNT(CASE WHEN {$validCond} AND lp.penalty_paid > 0 THEN 1 ELSE NULL END) AS penalty_count,
                COALESCE(SUM(CASE WHEN lp.payment_type NOT IN ('development_charge', 'emi', 'booking_money', 'registration', 'land_share') AND {$validCond} THEN lp.amount ELSE 0 END), 0) AS other_amount,
                COUNT(CASE WHEN lp.payment_type NOT IN ('development_charge', 'emi', 'booking_money', 'registration', 'land_share') AND {$validCond} THEN 1 ELSE NULL END) AS other_count,
                COALESCE(SUM(CASE WHEN lp.payment_method = 'cash' AND {$validCond} THEN lp.amount ELSE 0 END), 0) AS cash_amount,
                COALESCE(SUM(CASE WHEN lp.payment_method = 'bank' AND {$validCond} THEN lp.amount ELSE 0 END), 0) AS bank_amount,
                COALESCE(SUM(CASE WHEN lp.payment_method = 'cheque' AND {$validCond} THEN lp.amount ELSE 0 END), 0) AS cheque_amount,
                COALESCE(SUM(CASE WHEN lp.payment_method = 'online' AND {$validCond} THEN lp.amount ELSE 0 END), 0) AS online_amount,
                GROUP_CONCAT(DISTINCT COALESCE(e_cr.full_name, u_cr.full_name, u_cr.user_name, CONCAT('User #', lp.created_by)) SEPARATOR ', ') AS creators_list
            FROM lead_payments lp
            INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
            INNER JOIN leads l ON lb.lead_id = l.id
            INNER JOIN projects p ON lb.project_id = p.id
            LEFT JOIN flats f ON lb.flat_id = f.id
            LEFT JOIN users u_cpa ON u_cpa.id = {$effAgentPrj}
            LEFT JOIN employees e_cpa ON (u_cpa.employee_id = e_cpa.id OR e_cpa.user_id = u_cpa.id)
            LEFT JOIN roles r_cpa ON u_cpa.role_id = r_cpa.id
            LEFT JOIN users u_cr ON lp.created_by = u_cr.id
            LEFT JOIN employees e_cr ON (u_cr.employee_id = e_cr.id OR e_cr.user_id = u_cr.id)
            LEFT JOIN roles r_cr ON u_cr.role_id = r_cr.id
            LEFT JOIN departments d_cr ON e_cr.department_id = d_cr.id
            WHERE {$where}
            GROUP BY p.id, ced_officer_id
            HAVING total_count > 0 OR total_amount > 0
            ORDER BY ced_officer_id DESC, total_amount DESC
        ";

        $projectRows = [];
        $officerSummary = [];

        $resPrj = $this->db->query($sqlPrj);
        if ($resPrj) {
            while ($r = $resPrj->fetch_assoc()) {
                $uId = (int)$r['ced_officer_id'];
                $poKey = (int)$r['project_id'] . '_' . $uId;
                $poSplit = $emiSplit['by_project_officer'][$poKey] ?? ['regular_emi_amount' => 0.0, 'regular_emi_count' => 0, 'advance_emi_amount' => 0.0, 'advance_emi_count' => 0];

                if (!empty($filters['collector_id'])) {
                    $targetCId = (int)$filters['collector_id'];
                    if ($uId === $targetCId || $uId === 0) {
                        $uId = $targetCId;
                        if (isset($allCedOfficers[$targetCId])) {
                            $r['ced_officer_name'] = $allCedOfficers[$targetCId]['full_name'];
                            $r['ced_officer_role'] = $allCedOfficers[$targetCId]['role_name'];
                            $r['ced_emp_code'] = $allCedOfficers[$targetCId]['emp_code'] ?? '';
                        }
                    }
                }
                $r['total_count']         = (int)$r['total_count'];
                $r['total_amount']        = (float)$r['total_amount'];
                $r['land_share_amount']   = (float)($r['land_share_amount'] ?? 0);
                $r['land_share_count']    = (int)($r['land_share_count'] ?? 0);
                $r['dev_charge_amount']   = (float)$r['dev_charge_amount'];
                $r['dev_charge_count']    = (int)$r['dev_charge_count'];
                $r['emi_amount']          = $poSplit['regular_emi_amount'];
                $r['emi_count']           = $poSplit['regular_emi_count'];
                $r['advance_emi_amount']  = $poSplit['advance_emi_amount'];
                $r['advance_emi_count']   = $poSplit['advance_emi_count'];
                $r['booking_amount']      = (float)$r['booking_amount'];
                $r['booking_count']       = (int)$r['booking_count'];
                $r['registration_amount'] = (float)$r['registration_amount'];
                $r['registration_count']  = (int)$r['registration_count'];
                $r['penalty_amount']      = (float)($r['penalty_amount'] ?? 0);
                $r['penalty_count']       = (int)($r['penalty_count'] ?? 0);
                $r['other_amount']        = (float)$r['other_amount'];
                $r['other_count']         = (int)$r['other_count'];
                $r['cash_amount']         = (float)$r['cash_amount'];
                $r['bank_amount']         = (float)$r['bank_amount'];
                $r['cheque_amount']       = (float)$r['cheque_amount'];
                $r['online_amount']       = (float)$r['online_amount'];
                $r['assigned_projects']   = $assignedProjectsByAgent[$uId] ?? [];
                $projectRows[] = $r;

                if (!isset($officerSummary[$uId])) {
                    $officerSummary[$uId] = [
                        'created_by'          => $uId,
                        'officer_id'          => $uId,
                        'collector_name'      => $r['ced_officer_name'],
                        'collector_role'      => $r['ced_officer_role'] ?: 'Client Experience',
                        'role_id'             => 7,
                        'emp_code'            => $r['ced_emp_code'] ?? '',
                        'dept_name'           => 'Client Experience',
                        'is_ced_member'       => ($uId > 0),
                        'is_management'       => false,
                        'total_count'         => 0,
                        'total_amount'        => 0.0,
                        'land_share_amount'   => 0.0,
                        'land_share_count'    => 0,
                        'dev_charge_amount'   => 0.0,
                        'dev_charge_count'    => 0,
                        'emi_amount'          => 0.0,
                        'emi_count'           => 0,
                        'advance_emi_amount'  => 0.0,
                        'advance_emi_count'   => 0,
                        'booking_amount'      => 0.0,
                        'booking_count'       => 0,
                        'registration_amount' => 0.0,
                        'registration_count'  => 0,
                        'penalty_amount'      => 0.0,
                        'penalty_count'       => 0,
                        'other_amount'        => 0.0,
                        'other_count'         => 0,
                        'cash_amount'         => 0.0,
                        'bank_amount'         => 0.0,
                        'cheque_amount'       => 0.0,
                        'online_amount'       => 0.0,
                        'projects_collected'  => [],
                        'creators'            => []
                    ];
                }
                $officerSummary[$uId]['total_count']         += $r['total_count'];
                $officerSummary[$uId]['total_amount']        += $r['total_amount'];
                $officerSummary[$uId]['land_share_amount']   += $r['land_share_amount'];
                $officerSummary[$uId]['land_share_count']    += $r['land_share_count'];
                $officerSummary[$uId]['dev_charge_amount']   += $r['dev_charge_amount'];
                $officerSummary[$uId]['dev_charge_count']    += $r['dev_charge_count'];
                $officerSummary[$uId]['emi_amount']          += $r['emi_amount'];
                $officerSummary[$uId]['emi_count']           += $r['emi_count'];
                $officerSummary[$uId]['advance_emi_amount']  += $r['advance_emi_amount'];
                $officerSummary[$uId]['advance_emi_count']   += $r['advance_emi_count'];
                $officerSummary[$uId]['booking_amount']      += $r['booking_amount'];
                $officerSummary[$uId]['booking_count']       += $r['booking_count'];
                $officerSummary[$uId]['registration_amount'] += $r['registration_amount'];
                $officerSummary[$uId]['registration_count']  += $r['registration_count'];
                $officerSummary[$uId]['penalty_amount']      += $r['penalty_amount'];
                $officerSummary[$uId]['penalty_count']       += $r['penalty_count'];
                $officerSummary[$uId]['other_amount']        += $r['other_amount'];
                $officerSummary[$uId]['other_count']         += $r['other_count'];
                $officerSummary[$uId]['cash_amount']         += $r['cash_amount'];
                $officerSummary[$uId]['bank_amount']         += $r['bank_amount'];
                $officerSummary[$uId]['cheque_amount']       += $r['cheque_amount'];
                $officerSummary[$uId]['online_amount']       += $r['online_amount'];
                $officerSummary[$uId]['projects_collected'][] = $r['project_name'];
                if (!empty($r['creators_list'])) {
                    foreach (explode(', ', $r['creators_list']) as $cName) {
                        $officerSummary[$uId]['creators'][$cName] = true;
                    }
                }
            }
        }

        // Ensure filtered collector is present even if 0 collections in this period
        if (!empty($filters['collector_id'])) {
            $filteredCId = (int)$filters['collector_id'];
            if (!isset($officerSummary[$filteredCId])) {
                $off = $allCedOfficers[$filteredCId] ?? null;
                if (!$off) {
                    $qUser = $this->db->query("SELECT u.id AS user_id, COALESCE(e.full_name, u.full_name, u.user_name) AS full_name, e.employee_id AS emp_code, COALESCE(r.name, 'Staff') AS role_name, COALESCE(r.id, 0) AS role_id FROM users u LEFT JOIN employees e ON (u.employee_id = e.id OR e.user_id = u.id) LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = {$filteredCId} LIMIT 1");
                    if ($qUser && $uRow = $qUser->fetch_assoc()) {
                        $off = $uRow;
                    }
                }
                $assigned = $assignedProjectsByAgent[$filteredCId] ?? [];
                $officerSummary[$filteredCId] = [
                    'created_by'          => $filteredCId,
                    'officer_id'          => $filteredCId,
                    'collector_name'      => $off ? $off['full_name'] : "User #{$filteredCId}",
                    'collector_role'      => $off ? $off['role_name'] : 'Client Experience',
                    'role_id'             => $off ? (int)$off['role_id'] : 7,
                    'emp_code'            => $off['emp_code'] ?? '',
                    'dept_name'           => 'Client Experience',
                    'is_ced_member'       => true,
                    'is_management'       => false,
                    'total_count'         => 0,
                    'total_amount'        => 0.0,
                    'land_share_amount'   => 0.0,
                    'land_share_count'    => 0,
                    'dev_charge_amount'   => 0.0,
                    'dev_charge_count'    => 0,
                    'emi_amount'          => 0.0,
                    'emi_count'           => 0,
                    'advance_emi_amount'  => 0.0,
                    'advance_emi_count'   => 0,
                    'booking_amount'      => 0.0,
                    'booking_count'       => 0,
                    'registration_amount' => 0.0,
                    'registration_count'  => 0,
                    'penalty_amount'      => 0.0,
                    'penalty_count'       => 0,
                    'other_amount'        => 0.0,
                    'other_count'         => 0,
                    'cash_amount'         => 0.0,
                    'bank_amount'         => 0.0,
                    'cheque_amount'       => 0.0,
                    'online_amount'       => 0.0,
                    'projects_collected'  => [],
                    'creators'            => []
                ];
            }
        }

        // Add CED officers who have 0 collections in this period
        if (($filters['team_scope'] ?? 'ced_only') === 'ced_only' && empty($filters['collector_id'])) {
            foreach ($allCedOfficers as $uId => $off) {
                if (!isset($officerSummary[$uId])) {
                    $assigned = $assignedProjectsByAgent[$uId] ?? [];
                    $officerSummary[$uId] = [
                        'created_by'          => $uId,
                        'officer_id'          => $uId,
                        'collector_name'      => $off['full_name'],
                        'collector_role'      => $off['role_name'],
                        'role_id'             => (int)$off['role_id'],
                        'emp_code'            => $off['emp_code'] ?? '',
                        'dept_name'           => 'Client Experience',
                        'is_ced_member'       => true,
                        'is_management'       => false,
                        'total_count'         => 0,
                        'total_amount'        => 0.0,
                        'land_share_amount'   => 0.0,
                        'land_share_count'    => 0,
                        'dev_charge_amount'   => 0.0,
                        'dev_charge_count'    => 0,
                        'emi_amount'          => 0.0,
                        'emi_count'           => 0,
                        'advance_emi_amount'  => 0.0,
                        'advance_emi_count'   => 0,
                        'booking_amount'      => 0.0,
                        'booking_count'       => 0,
                        'registration_amount' => 0.0,
                        'registration_count'  => 0,
                        'penalty_amount'      => 0.0,
                        'penalty_count'       => 0,
                        'other_amount'        => 0.0,
                        'other_count'         => 0,
                        'cash_amount'         => 0.0,
                        'bank_amount'         => 0.0,
                        'cheque_amount'       => 0.0,
                        'online_amount'       => 0.0,
                        'projects_collected'  => [],
                        'creators'            => []
                    ];
                }
            }
        }

        // Sort portfolio rows
        uasort($officerSummary, function($a, $b) use ($assignedProjectsByAgent) {
            if ($a['officer_id'] === 0) return 1;
            if ($b['officer_id'] === 0) return -1;
            if ($a['total_amount'] != $b['total_amount']) {
                return $b['total_amount'] <=> $a['total_amount'];
            }
            $aPrjs = count($assignedProjectsByAgent[$a['officer_id']] ?? []);
            $bPrjs = count($assignedProjectsByAgent[$b['officer_id']] ?? []);
            if ($aPrjs != $bPrjs) {
                return $bPrjs <=> $aPrjs;
            }
            return strcasecmp($a['collector_name'], $b['collector_name']);
        });

        $portfolioRows = [];
        foreach ($officerSummary as $uId => $row) {
            $row['percentage'] = $grand['total_amount'] > 0 ? round(($row['total_amount'] / $grand['total_amount']) * 100, 2) : 0.0;
            $assigned = $assignedProjectsByAgent[$uId] ?? [];
            $row['assigned_projects'] = $assigned;
            $row['assigned_projects_names'] = !empty($assigned) ? implode(', ', array_column($assigned, 'name')) : ($uId === 0 ? 'Company Unassigned Projects' : 'None');
            $row['creators_list'] = !empty($row['creators']) ? implode(', ', array_keys($row['creators'])) : 'N/A';
            $portfolioRows[] = $row;
        }

        // Default display rows: CED Portfolio rows if in CED scope or filtering by officer, otherwise creator rows
        $isCedScope = in_array(($filters['team_scope'] ?? 'ced_only'), ['ced_only', 'ced_mgmt'], true);
        $displayRows = $isCedScope ? $portfolioRows : $rows;

        return [
            'rows'           => $displayRows,
            'portfolio_rows' => $portfolioRows,
            'project_rows'   => $projectRows,
            'creator_rows'   => $rows,
            'grand'          => $grand
        ];
    }

    /**
     * Fetch Payment Heads / Type Summary Matrix
     */
    protected function fetchPaymentTypeSummary($startDate, $endDate, $filters)
    {
        $where = $this->buildLeadPaymentsConditions($startDate, $endDate, $filters);
        $joins = $this->getBaseJoins();

        $isFilterRejected = (!empty($filters['verification_status']) && $filters['verification_status'] === 'rejected');
        $validCond = $isFilterRejected ? "1=1" : "(lp.verification_status IS NULL OR lp.verification_status != 'rejected')";

        $sql = "
            SELECT 
                lp.payment_type as ptype,
                COUNT(CASE WHEN {$validCond} THEN 1 ELSE NULL END) as count,
                COALESCE(SUM(CASE WHEN {$validCond} THEN lp.amount ELSE 0 END), 0) as total_amount,
                COALESCE(SUM(CASE WHEN lp.payment_method = 'bank' AND {$validCond} THEN lp.amount ELSE 0 END), 0) as bank_amount,
                COALESCE(SUM(CASE WHEN lp.payment_method = 'cash' AND {$validCond} THEN lp.amount ELSE 0 END), 0) as cash_amount,
                COALESCE(SUM(CASE WHEN lp.payment_method = 'cheque' AND {$validCond} THEN lp.amount ELSE 0 END), 0) as cheque_amount,
                COALESCE(SUM(CASE WHEN lp.payment_method = 'online' AND {$validCond} THEN lp.amount ELSE 0 END), 0) as online_amount
            FROM lead_payments lp
            {$joins}
            WHERE {$where}
            GROUP BY ptype
            HAVING count > 0 OR total_amount > 0
            ORDER BY total_amount DESC
        ";

        $rows = [];
        $grand = [
            'count'        => 0,
            'total_amount' => 0.0,
            'bank_amount'  => 0.0,
            'cash_amount'  => 0.0,
            'cheque_amount'=> 0.0,
            'online_amount'=> 0.0
        ];

        $labels = [
            'development_charge'  => 'Development Charge',
            'emi'                 => 'Regular EMI',
            'advance_emi'         => 'Advance EMI',
            'booking_money'       => 'Booking Money (Rare)',
            'registration'        => 'Registration Money (Rare)',
            'land_share'          => 'Land Share Collection',
            'shop_service_charge' => 'Shop Service Charge',
            'others'              => 'Other Collections'
        ];

        $emiSplit = $this->calculateEmiSplit($startDate, $endDate, $filters);

        $res = $this->db->query($sql);
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $pt = $r['ptype'];
                if ($pt === 'emi') {
                    $regAmt = $emiSplit['total']['regular_emi_amount'];
                    $regCnt = $emiSplit['total']['regular_emi_count'];
                    $advAmt = $emiSplit['total']['advance_emi_amount'];
                    $advCnt = $emiSplit['total']['advance_emi_count'];

                    if ($regAmt > 0 || $regCnt > 0) {
                        $regRow = [
                            'ptype'         => 'emi',
                            'label'         => $labels['emi'],
                            'count'         => $regCnt,
                            'total_amount'  => $regAmt,
                            'bank_amount'   => $emiSplit['total']['regular_methods']['bank'],
                            'cash_amount'   => $emiSplit['total']['regular_methods']['cash'],
                            'cheque_amount' => $emiSplit['total']['regular_methods']['cheque'],
                            'online_amount' => $emiSplit['total']['regular_methods']['online'],
                        ];
                        $rows['emi'] = $regRow;
                    }
                    if ($advAmt > 0 || $advCnt > 0) {
                        $advRow = [
                            'ptype'         => 'advance_emi',
                            'label'         => $labels['advance_emi'],
                            'count'         => $advCnt,
                            'total_amount'  => $advAmt,
                            'bank_amount'   => $emiSplit['total']['advance_methods']['bank'],
                            'cash_amount'   => $emiSplit['total']['advance_methods']['cash'],
                            'cheque_amount' => $emiSplit['total']['advance_methods']['cheque'],
                            'online_amount' => $emiSplit['total']['advance_methods']['online'],
                        ];
                        $rows['advance_emi'] = $advRow;
                    }

                    $grand['count']        += (int)$r['count'];
                    $grand['total_amount'] += $regAmt + $advAmt;
                    $grand['bank_amount']  += $emiSplit['total']['regular_methods']['bank'] + $emiSplit['total']['advance_methods']['bank'];
                    $grand['cash_amount']  += $emiSplit['total']['regular_methods']['cash'] + $emiSplit['total']['advance_methods']['cash'];
                    $grand['cheque_amount']+= $emiSplit['total']['regular_methods']['cheque'] + $emiSplit['total']['advance_methods']['cheque'];
                    $grand['online_amount']+= $emiSplit['total']['regular_methods']['online'] + $emiSplit['total']['advance_methods']['online'];
                    continue;
                }

                $r['label'] = $labels[$pt] ?? ucwords(str_replace('_', ' ', $pt));
                $r['count'] = (int)$r['count'];
                $r['total_amount'] = (float)$r['total_amount'];
                $r['bank_amount'] = (float)$r['bank_amount'];
                $r['cash_amount'] = (float)$r['cash_amount'];
                $r['cheque_amount'] = (float)$r['cheque_amount'];
                $r['online_amount'] = (float)$r['online_amount'];

                $grand['count']        += $r['count'];
                $grand['total_amount'] += $r['total_amount'];
                $grand['bank_amount']  += $r['bank_amount'];
                $grand['cash_amount']  += $r['cash_amount'];
                $grand['cheque_amount']+= $r['cheque_amount'];
                $grand['online_amount']+= $r['online_amount'];

                $rows[$pt] = $r;
            }
        }

        foreach ($rows as &$item) {
            $item['percentage'] = $grand['total_amount'] > 0 ? round(($item['total_amount'] / $grand['total_amount']) * 100, 2) : 0.0;
        }
        unset($item);

        return [
            'rows'  => $rows,
            'grand' => $grand
        ];
    }

    /**
     * Fetch detailed collection transactions register
     */
    protected function fetchTransactions($startDate, $endDate, $filters, $limit = 50, $offset = 0)
    {
        $where = $this->buildLeadPaymentsConditions($startDate, $endDate, $filters);
        $joins = $this->getBaseJoins();

        $sql = "SELECT lp.id, lp.booking_id,
                lp.payment_date,
                lp.payment_type,
                lp.settlement_type,
                lp.payment_status,
                lp.amount,
                lp.payment_method,
                lp.bank_id,
                lp.bank_name,
                lp.bank_account_no,
                lp.cheque_no,
                lp.transaction_ref,
                lp.verification_status,
                lp.verified_by,
                lp.verified_at,
                lp.created_by,
                lp.created_at,
                cb.bank_name AS company_bank_name,
                cb.account_name AS company_account_name,
                cb.account_number AS company_account_number,
                COALESCE(es.emi_no, lp.emi_no) AS emi_no,
                COALESCE(parent_es.emi_no, p_parent.emi_no) AS settled_emi_no,
                es.due_date AS emi_due_date,
                parent_es.due_date AS settled_due_date,
                COALESCE(NULLIF(TRIM(l.full_name), ''), NULLIF(TRIM(l.lead_name), '')) AS client_name,
                COALESCE(NULLIF(TRIM(l.phone), ''), NULLIF(TRIM(l.phone_number), ''), NULLIF(TRIM(l.alt_phone_number), ''), NULLIF(TRIM(l.`2nd_owner_phone`), '')) AS client_phone,
                p.id AS project_id,
                p.name AS project_name,
                f.flat_name,
                COALESCE(e_cr.full_name, u_cr.full_name, u_cr.user_name, CONCAT('User #', lp.created_by)) AS collector_name,
                COALESCE(r_cr.name, 'N/A') AS collector_role,
                COALESCE(e_cpa.full_name, u_cpa.full_name, u_cpa.user_name, 'Unassigned') AS ced_assigned_name,
                COALESCE(r_cpa.name, '') AS ced_assigned_role,
                COALESCE(cpa.user_id, 0) AS ced_assigned_user_id,
                COALESCE(e_v.full_name, u_v.full_name, u_v.user_name, '') AS verifier_name,
                COALESCE(r_v.name, '') AS verifier_role
            FROM lead_payments lp
            {$joins}
            LEFT JOIN company_bank_accounts cb ON cb.id = lp.bank_id
            LEFT JOIN lead_payments p_parent ON lp.parent_payment_id = p_parent.id
            LEFT JOIN emi_schedules es ON (lp.id = es.lead_payment_id)
            LEFT JOIN emi_schedules parent_es ON (p_parent.id = parent_es.lead_payment_id)
            LEFT JOIN users u_v ON lp.verified_by = u_v.id
            LEFT JOIN employees e_v ON (u_v.employee_id = e_v.id OR e_v.user_id = u_v.id)
            LEFT JOIN roles r_v ON u_v.role_id = r_v.id
            WHERE {$where}
            ORDER BY lp.payment_date DESC, lp.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
      


        $list = [];
        $res = $this->db->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $row['purpose_info'] = self::formatPaymentPurpose($row);
                $list[] = $row;
            }
        }
        return $list;
    }

    /**
     * Format payment purpose details including exact EMI number, settlement installment number, and advance number
     */
    public static function formatPaymentPurpose(array $t): array
    {
        $pt = $t['payment_type'] ?? '';
        $st = $t['settlement_type'] ?? '';
        $ps = $t['payment_status'] ?? '';
        $emiNo = !empty($t['emi_no']) && is_numeric($t['emi_no']) ? (int)$t['emi_no'] : null;
        $settledEmiNo = !empty($t['settled_emi_no']) && is_numeric($t['settled_emi_no']) ? (int)$t['settled_emi_no'] : null;

        $getOrdinal = function($num) {
            if (!$num) return '';
            $mod100 = $num % 100;
            if ($mod100 >= 11 && $mod100 <= 13) return $num . 'th';
            switch ($num % 10) {
                case 1:  return $num . 'st';
                case 2:  return $num . 'nd';
                case 3:  return $num . 'rd';
                default: return $num . 'th';
            }
        };

        // 1. Due Settlement
        if ($st === 'emi_due_settlement' || ($pt === 'emi' && !empty($st))) {
            if ($settledEmiNo) {
                return [
                    'category'    => 'settlement',
                    'label'       => $getOrdinal($settledEmiNo) . ' Installment (Settlement)',
                    'full_label'  => 'EMI Due Settlement for ' . $getOrdinal($settledEmiNo) . ' Installment',
                    'badge_class' => 'badge-subtle-settlement',
                    'icon'        => 'fa-solid fa-handshake',
                    'number'      => $settledEmiNo,
                    'type_title'  => 'Settled ' . $getOrdinal($settledEmiNo) . ' Installment'
                ];
            }
            return [
                'category'    => 'settlement',
                'label'       => 'EMI Due Settlement',
                'full_label'  => 'EMI Due Settlement',
                'badge_class' => 'badge-subtle-settlement',
                'icon'        => 'fa-solid fa-handshake',
                'number'      => null,
                'type_title'  => 'EMI Due Settlement'
            ];
        }

        // 2. Advance EMI (partially_paid EMI)
        if ($pt === 'emi' && $ps === 'partially_paid') {
            if ($emiNo) {
                return [
                    'category'    => 'advance_emi',
                    'label'       => 'Advance ' . $getOrdinal($emiNo) . ' Installment',
                    'full_label'  => 'Advance ' . $getOrdinal($emiNo) . ' Installment',
                    'badge_class' => 'badge-subtle-advance',
                    'icon'        => 'fa-solid fa-forward',
                    'number'      => $emiNo,
                    'type_title'  => 'Advance ' . $getOrdinal($emiNo) . ' Installment'
                ];
            }
            return [
                'category'    => 'advance_emi',
                'label'       => 'Advance EMI',
                'full_label'  => 'Advance Installment',
                'badge_class' => 'badge-subtle-advance',
                'icon'        => 'fa-solid fa-forward',
                'number'      => null,
                'type_title'  => 'Advance Installment'
            ];
        }

        // 3. Regular EMI
        if ($pt === 'emi') {
            if ($emiNo) {
                return [
                    'category'    => 'emi',
                    'label'       => $getOrdinal($emiNo) . ' Installment',
                    'full_label'  => $getOrdinal($emiNo) . ' Monthly Installment',
                    'badge_class' => 'badge-subtle-emi',
                    'icon'        => 'fa-solid fa-calendar-check',
                    'number'      => $emiNo,
                    'type_title'  => $getOrdinal($emiNo) . ' Installment'
                ];
            }
            return [
                'category'    => 'emi',
                'label'       => 'EMI Payment',
                'full_label'  => 'Monthly Installment',
                'badge_class' => 'badge-subtle-emi',
                'icon'        => 'fa-solid fa-calendar-check',
                'number'      => null,
                'type_title'  => 'Monthly Installment'
            ];
        }

        // 4. Development Charge
        if ($pt === 'development_charge') {
            return [
                'category'    => 'dev_charge',
                'label'       => 'Development Charge',
                'full_label'  => 'Development Charge',
                'badge_class' => 'badge-subtle-dev',
                'icon'        => 'fa-solid fa-helmet-safety',
                'number'      => null,
                'type_title'  => 'Development Charge'
            ];
        }

        // 5. Booking Money
        if ($pt === 'booking_money') {
            return [
                'category'    => 'booking',
                'label'       => 'Booking Money',
                'full_label'  => 'Booking Money',
                'badge_class' => 'badge-subtle-booking',
                'icon'        => 'fa-solid fa-bookmark',
                'number'      => null,
                'type_title'  => 'Booking Money'
            ];
        }

        // 6. Registration
        if ($pt === 'registration') {
            return [
                'category'    => 'registration',
                'label'       => 'Registration Money',
                'full_label'  => 'Registration Money',
                'badge_class' => 'badge-subtle-registration',
                'icon'        => 'fa-solid fa-id-card',
                'number'      => null,
                'type_title'  => 'Registration Money'
            ];
        }

        // 7. Land Share
        if ($pt === 'land_share') {
            return [
                'category'    => 'land_share',
                'label'       => 'Land Share',
                'full_label'  => 'Land Share Collection',
                'badge_class' => 'bg-light text-dark border',
                'icon'        => 'fa-solid fa-map-location-dot',
                'number'      => null,
                'type_title'  => 'Land Share Collection'
            ];
        }

        // 8. Shop Service Charge
        if ($pt === 'shop_service_charge') {
            return [
                'category'    => 'shop_service_charge',
                'label'       => 'Shop Service Charge',
                'full_label'  => 'Shop Service Charge',
                'badge_class' => 'badge-subtle-other',
                'icon'        => 'fa-solid fa-store',
                'number'      => null,
                'type_title'  => 'Shop Service Charge'
            ];
        }

        // 9. Others
        if ($pt === 'others') {
            $otherPurpose = !empty($t['other_purpose']) ? trim($t['other_purpose']) : '';
            return [
                'category'    => 'other',
                'label'       => !empty($otherPurpose) ? 'Others (' . $otherPurpose . ')' : 'Others',
                'full_label'  => !empty($otherPurpose) ? 'Other: ' . $otherPurpose : 'Other Payment',
                'badge_class' => 'bg-light text-dark border',
                'icon'        => 'fa-solid fa-circle-dollar-to-slot',
                'number'      => null,
                'type_title'  => 'Other Collection'
            ];
        }

        // Fallback
        $formatted = ucwords(str_replace('_', ' ', $pt));
        return [
            'category'    => 'other',
            'label'       => !empty($formatted) ? $formatted : 'Other Collection',
            'full_label'  => !empty($formatted) ? $formatted : 'Other Collection',
            'badge_class' => 'bg-light text-dark border',
            'icon'        => 'fa-solid fa-circle-dollar-to-slot',
            'number'      => null,
            'type_title'  => $formatted
        ];
    }

        /**
     * Fetch list of CED collectors / executives for filter dropdown based on Department Scope
     */
    protected function fetchCedCollectors($teamScope = 'ced_only')
    {
        $whereDept = "";
        if ($teamScope === 'ced_only') {
            $whereDept = "WHERE (
                e.department_id IN (4, 29)
                OR u.role_id IN (7, 37, 39)
                OR r.name LIKE '%client%'
                OR r.name LIKE '%recovery%'
                OR d.name LIKE '%client%'
                OR d.name LIKE '%ced%'
                OR u.id IN (SELECT DISTINCT user_id FROM ced_project_assignments)
            )";
        } elseif ($teamScope === 'ced_mgmt') {
            $whereDept = "WHERE (
                e.department_id IN (4, 29)
                OR u.role_id IN (7, 37, 39, 1, 3, 33, 34, 35, 38)
                OR r.name LIKE '%client%'
                OR r.name LIKE '%recovery%'
                OR r.name LIKE '%management%'
                OR r.name LIKE '%admin%'
                OR d.name LIKE '%client%'
                OR d.name LIKE '%ced%'
                OR u.id IN (SELECT DISTINCT user_id FROM ced_project_assignments)
                OR u.user_name LIKE '%management%'
            )";
        } else { // 'all'
            $whereDept = "";
        }

        $sql = "
            SELECT DISTINCT 
                u.id AS user_id,
                COALESCE(e.full_name, u.full_name, u.user_name) AS full_name,
                e.employee_id AS emp_code,
                COALESCE(r.name, 'Staff') AS role_name,
                COALESCE(r.id, 0) AS role_id,
                COALESCE(d.name, 'General') AS dept_name,
                (SELECT COUNT(*) FROM ced_project_assignments cpa WHERE cpa.user_id = u.id AND cpa.status = 'active') AS assigned_projects_count,
                CASE 
                    WHEN (e.department_id IN (4, 29) OR u.role_id IN (7, 37, 39) OR r.name LIKE '%client%' OR r.name LIKE '%recovery%' OR d.name LIKE '%client%' OR d.name LIKE '%ced%' OR u.id IN (SELECT DISTINCT user_id FROM ced_project_assignments)) THEN 'ced_only'
                    WHEN (u.role_id IN (1, 3, 33, 34, 35, 38) OR r.name LIKE '%management%' OR r.name LIKE '%admin%' OR u.user_name LIKE '%management%') THEN 'ced_mgmt'
                    ELSE 'all'
                END AS scope_category
            FROM users u
            LEFT JOIN employees e ON (u.employee_id = e.id OR e.user_id = u.id)
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN departments d ON e.department_id = d.id
            {$whereDept}
            ORDER BY full_name ASC
        ";

        $list = [];
        $res = $this->db->query($sql);
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $list[] = $r;
            }
        }
        return $list;
    }

    /**
     * Fetch all active projects for filter dropdown
     */
    protected function fetchProjects()
    {
        $sql = "SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC";
        $list = [];
        $res = $this->db->query($sql);
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $list[] = $r;
            }
        }
        return $list;
    }
}

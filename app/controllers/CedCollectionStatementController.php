<?php
require_once __DIR__ . '/../core/Controller.php';

class CedCollectionStatementController extends Controller
{
    protected $db;

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
        $isClientComm = function_exists('isCreditRecovery') ? isCreditRecovery() : in_array($userRoleId, [7, 37], true);
        $isManagement = in_array($userRoleId, [1, 3, 33, 34, 35, 38], true)
            || strpos($roleName, 'management') !== false
            || strpos($roleName, 'admin') !== false
            || strpos($userName, 'management') !== false;

        $canAccess = hasPermission('CED Manage') 
            || $isManagement 
            || $isClientComm 
            || $userRoleId === 7;

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
        
        // Pagination for detailed transactions
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        if ($limit <= 0) $limit = 50;
        if ($limit > 500) $limit = 500;
        $offset = ($page - 1) * $limit;

        $totalRecords = $summary['total_count'] ?? 0;
        $totalPages = ceil(max(1, $totalRecords) / $limit);

        $collectorSummary = $this->fetchCollectorSummary($dateInfo['start_date'], $dateInfo['end_date'], $filters);
        $typeSummary      = $this->fetchPaymentTypeSummary($dateInfo['start_date'], $dateInfo['end_date'], $filters);
        $transactions     = $this->fetchTransactions($dateInfo['start_date'], $dateInfo['end_date'], $filters, $limit, $offset);
        $collectorsList   = $this->fetchCedCollectors();
        $projectsList     = $this->fetchProjects();

        // Default view mode for CED: 'collectors' (Executive-wise Collection Summary)
        $viewMode = $_GET['view_mode'] ?? 'collectors';
        if (!in_array($viewMode, ['collectors', 'transactions', 'type_summary', 'all'], true)) {
            $viewMode = 'collectors';
        }

        $viewData = [
            'current_route'       => 'ced-collection-statement',
            'current_page'        => 'ced-collection-statement',
            'page_title'          => 'CED Collection Statement',
            'filters'             => $filters,
            'date_info'           => $dateInfo,
            'summary'             => $summary,
            'collector_summary'   => $collectorSummary,
            'type_summary'        => $typeSummary,
            'view_mode'           => $viewMode,
            'transactions'        => $transactions,
            'collectors_list'     => $collectorsList,
            'projects_list'       => $projectsList,
            'pagination'          => [
                'current_page' => $page,
                'total_pages'  => $totalPages,
                'total_records'=> $totalRecords,
                'limit'        => $limit
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
            $colList = $this->fetchCedCollectors();
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
                'Dev Charge Trx',
                'Dev Charge Amount (BDT)',
                'EMI Trx',
                'EMI Amount (BDT)',
                'Due Settlement Trx',
                'Due Settlement Amount (BDT)',
                'Booking & Reg Trx',
                'Booking & Reg Amount (BDT)',
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
                fputcsv($output, [
                    $sl++,
                    $r['collector_name'],
                    $r['emp_code'] ?? 'N/A',
                    $r['dev_charge_count'],
                    number_format($r['dev_charge_amount'], 2, '.', ''),
                    $r['emi_count'],
                    number_format($r['emi_amount'], 2, '.', ''),
                    $r['settlement_count'],
                    number_format($r['settlement_amount'], 2, '.', ''),
                    $r['booking_count'] + $r['registration_count'],
                    number_format($r['booking_amount'] + $r['registration_amount'], 2, '.', ''),
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
                $grand['dev_charge_count'],
                number_format($grand['dev_charge_amount'], 2, '.', ''),
                $grand['emi_count'],
                number_format($grand['emi_amount'], 2, '.', ''),
                $grand['settlement_count'],
                number_format($grand['settlement_amount'], 2, '.', ''),
                $grand['booking_count'] + $grand['registration_count'],
                number_format($grand['booking_amount'] + $grand['registration_amount'], 2, '.', ''),
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
            'Flat / Unit',
            'Payment Purpose / Head',
            'Payment Method',
            'Receiver Bank (Company)',
            'Sender Bank (Client)',
            'Cheque No',
            'Receipt / Voucher Ref',
            'Amount (BDT)',
            'Collector / Created By',
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
            return [
                'start_date' => $today,
                'end_date'   => $today,
                'label'      => 'Today (' . date('d M, Y') . ')'
            ];
        }

        if ($pt === 'weekly' || $pt === 'this_week') {
            $s = date('Y-m-d', strtotime('monday this week'));
            $e = date('Y-m-d', strtotime('sunday this week'));
            return [
                'start_date' => $s,
                'end_date'   => $e,
                'label'      => 'This Week (' . date('d M', strtotime($s)) . ' - ' . date('d M, Y', strtotime($e)) . ')'
            ];
        }

        if ($pt === 'custom' && (!empty($filters['start_date']) || !empty($filters['end_date']))) {
            $s = !empty($filters['start_date']) ? $filters['start_date'] : '2015-01-01';
            $e = !empty($filters['end_date'])   ? $filters['end_date']   : date('Y-m-d');
            return [
                'start_date' => $s,
                'end_date'   => $e,
                'label'      => date('d M, Y', strtotime($s)) . ' to ' . date('d M, Y', strtotime($e))
            ];
        }

        if ($pt === 'all_time') {
            return [
                'start_date' => '2015-01-01',
                'end_date'   => '2099-12-31',
                'label'      => 'All Time'
            ];
        }

        if ($pt === 'yearly' || $filters['month'] === 'all') {
            $y = $filters['year'];
            return [
                'start_date' => "{$y}-01-01",
                'end_date'   => "{$y}-12-31",
                'label'      => "Whole Year {$y}"
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
        return [
            'start_date' => $s,
            'end_date'   => $e,
            'label'      => date('F Y', strtotime($s))
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
            "COALESCE(lp.is_emi_discount, 0) = 0"
        ];

        // Team scope: CED Only vs CED & Management vs All
        if (!empty($filters['team_scope']) && empty($filters['collector_id'])) {
            if ($filters['team_scope'] === 'ced_only') {
                $conds[] = "(u_cr.role_id IN (7, 37) OR r_cr.name LIKE '%client%' OR r_cr.name LIKE '%recovery%' OR d_cr.name LIKE '%client%' OR d_cr.name LIKE '%ced%')";
            } elseif ($filters['team_scope'] === 'ced_mgmt') {
                $conds[] = "(u_cr.role_id IN (7, 37, 1, 3, 33, 34, 35, 38) OR r_cr.name LIKE '%client%' OR r_cr.name LIKE '%recovery%' OR r_cr.name LIKE '%management%' OR r_cr.name LIKE '%admin%' OR d_cr.name LIKE '%client%' OR d_cr.name LIKE '%ced%' OR u_cr.user_name LIKE '%management%')";
            }
        }

        if (!empty($filters['collector_id'])) {
            $cId = (int)$filters['collector_id'];
            $conds[] = "lp.created_by = {$cId}";
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
            if ($pt === 'advance_emi') {
                $conds[] = "lp.payment_type = 'emi' AND lp.payment_status = 'partially_paid' AND (lp.settlement_type IS NULL OR lp.settlement_type != 'emi_due_settlement')";
            } elseif ($pt === 'emi_settlement') {
                $conds[] = "lp.payment_type = 'emi' AND lp.settlement_type = 'emi_due_settlement'";
            } elseif ($pt === 'emi') {
                $conds[] = "lp.payment_type = 'emi' AND (lp.settlement_type IS NULL OR lp.settlement_type != 'emi_due_settlement')";
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
            $conds[] = "(l.full_name LIKE '%{$s}%' OR l.lead_name LIKE '%{$s}%' OR l.phone LIKE '%{$s}%' OR l.phone_number LIKE '%{$s}%' OR p.name LIKE '%{$s}%' OR f.flat_name LIKE '%{$s}%' OR lp.transaction_ref LIKE '%{$s}%' OR lp.cheque_no LIKE '%{$s}%' OR u_cr.user_name LIKE '%{$s}%' OR e_cr.full_name LIKE '%{$s}%')";
        }

        return implode(" AND ", $conds);
    }

    /**
     * Common JOINs for queries
     */
    protected function getBaseJoins()
    {
        return "
            INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
            INNER JOIN leads l ON lb.lead_id = l.id
            LEFT JOIN projects p ON lb.project_id = p.id
            LEFT JOIN flats f ON lb.flat_id = f.id
            LEFT JOIN users u_cr ON lp.created_by = u_cr.id
            LEFT JOIN employees e_cr ON (u_cr.employee_id = e_cr.id OR e_cr.user_id = u_cr.id)
            LEFT JOIN roles r_cr ON u_cr.role_id = r_cr.id
            LEFT JOIN departments d_cr ON e_cr.department_id = d_cr.id
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
        $summary = [
            'total_count' => 0, 'total_amount' => 0, 'dev_charge_amount' => 0, 'dev_charge_count' => 0,
            'emi_amount' => 0, 'emi_count' => 0, 'settlement_amount' => 0, 'settlement_count' => 0,
            'total_emi_amount' => 0, 'total_emi_count' => 0, 'booking_amount' => 0, 'booking_count' => 0,
            'registration_amount' => 0, 'registration_count' => 0, 'cash_amount' => 0, 'cash_count' => 0,
            'bank_amount' => 0, 'bank_count' => 0, 'cheque_amount' => 0, 'cheque_count' => 0,
            'online_amount' => 0, 'online_count' => 0, 'verified_amount' => 0, 'pending_amount' => 0,
            'active_collectors_count' => 0
        ];

        if ($res && $row = $res->fetch_assoc()) {
            $summary = [
                'total_count'             => (int)$row['total_count'],
                'total_amount'            => (float)$row['total_amount'],
                'dev_charge_amount'       => (float)$row['dev_charge_amount'],
                'dev_charge_count'        => (int)$row['dev_charge_count'],
                'emi_amount'              => (float)$row['emi_amount'],
                'emi_count'               => (int)$row['emi_count'],
                'settlement_amount'       => (float)$row['settlement_amount'],
                'settlement_count'        => (int)$row['settlement_count'],
                'total_emi_amount'        => (float)$row['emi_amount'] + (float)$row['settlement_amount'],
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
            "lb.booking_status IN ('booked', 'completed')"
        ];
        if (!empty($filters['project_id'])) {
            $pId = (int)$filters['project_id'];
            $condsSold[] = "lb.project_id = {$pId}";
        }
        $whereSold = implode(" AND ", $condsSold);
        $sqlSold = "
            SELECT 
                COUNT(*) as total_sold_units,
                COUNT(DISTINCT lb.lead_id) as total_sold_clients,
                COALESCE(SUM(lb.agreement_value), 0) as total_sold_value
            FROM lead_bookings lb
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
                COALESCE(SUM(CASE WHEN lp.payment_type NOT IN ('development_charge', 'emi', 'booking_money', 'registration') AND {$validCond} THEN lp.amount ELSE 0 END), 0) AS other_amount,
                COUNT(CASE WHEN lp.payment_type NOT IN ('development_charge', 'emi', 'booking_money', 'registration') AND {$validCond} THEN 1 ELSE NULL END) AS other_count,
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

        $rows = [];
        $grand = [
            'total_count'        => 0,
            'total_amount'       => 0.0,
            'dev_charge_amount'  => 0.0,
            'dev_charge_count'   => 0,
            'emi_amount'         => 0.0,
            'emi_count'          => 0,
            'settlement_amount'  => 0.0,
            'settlement_count'   => 0,
            'booking_amount'     => 0.0,
            'booking_count'      => 0,
            'registration_amount'=> 0.0,
            'registration_count' => 0,
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
                $totalAmt = (float)$r['total_amount'];
                $r['total_amount']       = $totalAmt;
                $r['total_count']        = (int)$r['total_count'];
                $r['dev_charge_amount']  = (float)$r['dev_charge_amount'];
                $r['dev_charge_count']   = (int)$r['dev_charge_count'];
                $r['emi_amount']         = (float)$r['emi_amount'];
                $r['emi_count']          = (int)$r['emi_count'];
                $r['settlement_amount']  = (float)$r['settlement_amount'];
                $r['settlement_count']   = (int)$r['settlement_count'];
                $r['booking_amount']     = (float)$r['booking_amount'];
                $r['booking_count']      = (int)$r['booking_count'];
                $r['registration_amount']= (float)$r['registration_amount'];
                $r['registration_count'] = (int)$r['registration_count'];
                $r['other_amount']       = (float)$r['other_amount'];
                $r['other_count']        = (int)$r['other_count'];
                $r['cash_amount']        = (float)$r['cash_amount'];
                $r['bank_amount']        = (float)$r['bank_amount'];
                $r['cheque_amount']      = (float)$r['cheque_amount'];
                $r['online_amount']      = (float)$r['online_amount'];
                $r['is_ced_member']      = in_array((int)$r['role_id'], [7, 37], true) || stripos($r['collector_role'], 'client') !== false;
                $r['is_management']      = in_array((int)$r['role_id'], [1, 3, 33, 34, 35, 38], true) || stripos($r['collector_role'], 'admin') !== false || stripos($r['collector_role'], 'management') !== false || (isset($r['user_name']) && stripos($r['user_name'], 'management') !== false);

                $grand['total_count']        += $r['total_count'];
                $grand['total_amount']       += $r['total_amount'];
                $grand['dev_charge_amount']  += $r['dev_charge_amount'];
                $grand['dev_charge_count']   += $r['dev_charge_count'];
                $grand['emi_amount']         += $r['emi_amount'];
                $grand['emi_count']          += $r['emi_count'];
                $grand['settlement_amount']  += $r['settlement_amount'];
                $grand['settlement_count']   += $r['settlement_count'];
                $grand['booking_amount']     += $r['booking_amount'];
                $grand['booking_count']      += $r['booking_count'];
                $grand['registration_amount']+= $r['registration_amount'];
                $grand['registration_count'] += $r['registration_count'];
                $grand['other_amount']       += $r['other_amount'];
                $grand['other_count']        += $r['other_count'];
                $grand['cash_amount']        += $r['cash_amount'];
                $grand['bank_amount']        += $r['bank_amount'];
                $grand['cheque_amount']      += $r['cheque_amount'];
                $grand['online_amount']      += $r['online_amount'];

                $rows[] = $r;
            }
        }

        // Compute percentages
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
                CASE 
                    WHEN lp.payment_type = 'emi' AND lp.settlement_type = 'emi_due_settlement' THEN 'emi_due_settlement'
                    ELSE lp.payment_type
                END as ptype,
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
            'emi'                 => 'Monthly EMI Installment',
            'emi_due_settlement'  => 'EMI Due Settlement / Advance',
            'booking_money'       => 'Booking Money (Rare)',
            'registration'        => 'Registration Money (Rare)',
            'land_share'          => 'Land Share Collection',
            'shop_service_charge' => 'Shop Service Charge',
            'others'              => 'Other Collections'
        ];

        $res = $this->db->query($sql);
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $pt = $r['ptype'];
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
     * Fetch list of CED collectors / executives for filter dropdown
     */
    protected function fetchCedCollectors()
    {
        $sql = "
            SELECT DISTINCT 
                u.id AS user_id,
                COALESCE(e.full_name, u.full_name, u.user_name) AS full_name,
                e.employee_id AS emp_code,
                COALESCE(r.name, 'Staff') AS role_name,
                COALESCE(r.id, 0) AS role_id
            FROM users u
            LEFT JOIN employees e ON (u.employee_id = e.id OR e.user_id = u.id)
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN departments d ON e.department_id = d.id
            WHERE (
                u.role_id IN (7, 37) 
                OR r.name LIKE '%client%' 
                OR r.name LIKE '%recovery%'
                OR d.name LIKE '%client%'
                OR d.name LIKE '%ced%'
                OR u.id IN (SELECT DISTINCT created_by FROM lead_payments WHERE created_by IS NOT NULL AND is_trashed = 0)
            )
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

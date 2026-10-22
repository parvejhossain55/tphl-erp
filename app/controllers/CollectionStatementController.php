<?php
require_once __DIR__ . '/../core/Controller.php';

class CollectionStatementController extends Controller
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Main Index - Collection Statement Page
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
        
        // Pagination
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        if ($limit <= 0) $limit = 50;
        if ($limit > 500) $limit = 500;
        $offset = ($page - 1) * $limit;

        $totalRecords = $summary['total_count'] ?? 0;
        $totalPages = ceil(max(1, $totalRecords) / $limit);

        $typeSummary = $this->fetchPaymentTypeSummary($dateInfo['start_date'], $dateInfo['end_date'], $filters);
        $transactions = $this->fetchTransactions($dateInfo['start_date'], $dateInfo['end_date'], $filters, $limit, $offset);
        $projects = $this->fetchProjects();
        $bankNames = $this->fetchDistinctBankNames();

        $viewMode = $_GET['view_mode'] ?? 'type_summary';
        if (!in_array($viewMode, ['type_summary', 'bank_summary', 'transactions', 'both', 'all'], true)) {
            $viewMode = 'type_summary';
        }

        $viewData = [
            'current_route'       => 'collection-statement',
            'page_title'          => 'Total Collection Statement',
            'filters'             => $filters,
            'date_info'           => $dateInfo,
            'summary'             => $summary,
            'type_summary'        => $typeSummary,
            'view_mode'           => $viewMode,
            'transactions'        => $transactions,
            'projects'            => $projects,
            'bank_names'          => $bankNames,
            'pagination'          => [
                'current_page' => $page,
                'total_pages'  => $totalPages,
                'total_records'=> $totalRecords,
                'limit'        => $limit
            ]
        ];

        $this->view('accounts/cashIn/collection-statement', $viewData);
    }

    /**
     * Print View for Filtered Statement
     */
    public function printStatement()
    {
        $filters = $this->parseFilters();
        $dateInfo = $this->resolveDateRange($filters);
        $summary = $this->fetchSummary($dateInfo['start_date'], $dateInfo['end_date'], $filters);
        $typeSummary = $this->fetchPaymentTypeSummary($dateInfo['start_date'], $dateInfo['end_date'], $filters);
        // Print statement loads up to 5000 records
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

        $printMode = $_GET['print_mode'] ?? ($_GET['view_mode'] ?? 'full');

        $viewData = [
            'page_title'    => 'Collection Statement Print',
            'filters'       => $filters,
            'date_info'     => $dateInfo,
            'summary'       => $summary,
            'type_summary'  => $typeSummary,
            'print_mode'    => $printMode,
            'transactions'  => $transactions,
            'project_name'  => $projectName
        ];

        extract($viewData);
        include __DIR__ . '/../views/accounts/cashIn/collection-statement-print.php';
        exit;
    }

    /**
     * Export Statement as CSV
     */
    public function exportCSV()
    {
        $filters = $this->parseFilters();
        $dateInfo = $this->resolveDateRange($filters);
        $exportMode = $_GET['export_mode'] ?? ($_GET['view_mode'] ?? 'transactions');

        if ($exportMode === 'bank_summary') {
            $summary = $this->fetchSummary($dateInfo['start_date'], $dateInfo['end_date'], $filters);
            $filename = "bank_account_inflow_breakdown_" . date('Ymd_His') . ".csv";
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename);

            $output = fopen('php://output', 'w');
            fputs($output, "\xEF\xBB\xBF");
            fputcsv($output, ['SL', 'Bank Account / Deposited Account', 'Deposit Transactions Count', 'Total Inflow Amount (BDT)', '% Share of Bank Inflow']);

            $sl = 1;
            $bankList = $summary['by_bank'] ?? [];
            $totalBankAmt = (float)($summary['bank_amount'] ?? 0);
            $totalBankCount = 0;

            foreach ($bankList as $bName => $bData) {
                $bAmt = is_array($bData) ? (float)($bData['amount'] ?? 0) : (float)$bData;
                $bCount = is_array($bData) ? (int)($bData['count'] ?? 0) : 1;
                $totalBankCount += $bCount;
                $pct = $totalBankAmt > 0 ? round(($bAmt / $totalBankAmt) * 100, 2) : 0;

                fputcsv($output, [
                    $sl++,
                    $bName,
                    $bCount,
                    number_format($bAmt, 2, '.', ''),
                    $pct . '%'
                ]);
            }

            fputcsv($output, [
                'TOTAL',
                'GRAND TOTAL BANK INFLOW (' . count($bankList) . ' Accounts)',
                $totalBankCount,
                number_format($totalBankAmt, 2, '.', ''),
                '100.00%'
            ]);

            fclose($output);
            exit;
        }

        if ($exportMode === 'type_summary') {
            $typeData = $this->fetchPaymentTypeSummary($dateInfo['start_date'], $dateInfo['end_date'], $filters);
            $filename = "payment_type_collection_statement_" . date('Ymd_His') . ".csv";
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename);

            $output = fopen('php://output', 'w');
            fputs($output, "\xEF\xBB\xBF");
            fputcsv($output, ['SL', 'Payment Type / Purpose', 'Total Trx Count', 'Total Collection (BDT)', 'Bank (BDT)', 'Cash (BDT)', 'Cheque (BDT)', 'Online (BDT)', '% of Total']);

            $sl = 1;
            foreach ($typeData['rows'] as $r) {
                if ($r['total_amount'] > 0 || $r['count'] > 0) {
                    fputcsv($output, [
                        $sl++,
                        $r['name'],
                        $r['count'],
                        number_format($r['total_amount'], 2, '.', ''),
                        number_format($r['bank_amount'], 2, '.', ''),
                        number_format($r['cash_amount'], 2, '.', ''),
                        number_format($r['cheque_amount'], 2, '.', ''),
                        number_format($r['online_amount'], 2, '.', ''),
                        $r['percentage'] . '%'
                    ]);
                }
            }

            fputcsv($output, [
                'TOTAL',
                'GRAND TOTAL COLLECTION',
                $typeData['grand']['count'],
                number_format($typeData['grand']['total_amount'], 2, '.', ''),
                number_format($typeData['grand']['bank_amount'], 2, '.', ''),
                number_format($typeData['grand']['cash_amount'], 2, '.', ''),
                number_format($typeData['grand']['cheque_amount'], 2, '.', ''),
                number_format($typeData['grand']['online_amount'], 2, '.', ''),
                '100.00%'
            ]);

            fclose($output);
            exit;
        }

        $transactions = $this->fetchTransactions($dateInfo['start_date'], $dateInfo['end_date'], $filters, 10000, 0);

        $filename = "collection_statement_" . date('Ymd_His') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        // BOM for Excel UTF-8
        fputs($output, "\xEF\xBB\xBF");

        fputcsv($output, [
            'SL',
            'Date',
            'Source',
            'Client / Depositor',
            'Phone',
            'Project',
            'Flat / Unit',
            'Payment Type',
            'Payment Method',
            'Bank / Cheque Details',
            'Receipt / Voucher Ref',
            'Amount (BDT)',
            'Verification Status'
        ]);

        $sl = 1;
        foreach ($transactions as $t) {
            $bankDetail = '';
            if (!empty($t['bank_name'])) {
                $bankDetail .= $t['bank_name'];
            }
            if (!empty($t['cheque_no'])) {
                $bankDetail .= (!empty($bankDetail) ? ' | Chq: ' : 'Chq: ') . $t['cheque_no'];
            }

            fputcsv($output, [
                $sl++,
                $t['date'],
                ucfirst($t['source']),
                $t['client_name'] ?? 'N/A',
                $t['client_phone'] ?? 'N/A',
                $t['project_name'] ?? 'N/A',
                $t['flat_name'] ?? 'N/A',
                ucwords(str_replace('_', ' ', $t['payment_type'] ?? '')),
                ucfirst($t['payment_method'] ?? ''),
                $bankDetail,
                $t['transaction_ref'] ?? '',
                number_format((float)$t['amount'], 2, '.', ''),
                ucfirst($t['verification_status'] ?? '')
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
        $allowedPeriods = ['monthly', 'custom', 'daily', 'today', 'weekly', 'this_week', 'this_month', 'yearly', 'all_time'];
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

        $projectId = !empty($_GET['project_id']) && is_numeric($_GET['project_id']) ? (int)$_GET['project_id'] : null;
        $source    = trim($_GET['source'] ?? 'all');
        if (!in_array($source, ['all', 'client', 'management'], true)) {
            $source = 'all';
        }

        $paymentMethod = trim($_GET['payment_method'] ?? '');
        $allowedMethods = ['cash', 'bank', 'cheque', 'online'];
        if (!in_array($paymentMethod, $allowedMethods, true)) {
            $paymentMethod = '';
        }

        $bankName = trim($_GET['bank_name'] ?? '');
        $paymentType = trim($_GET['payment_type'] ?? '');
        $allowedTypes = ['booking_money', 'registration', 'land_share', 'emi', 'development_charge', 'shop_service_charge', 'management'];
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
            'project_id'          => $projectId,
            'source'              => $source,
            'payment_method'      => $paymentMethod,
            'bank_name'           => $bankName,
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

        if ($pt === 'custom' && !empty($filters['start_date']) && !empty($filters['end_date'])) {
            return [
                'start_date' => $filters['start_date'],
                'end_date'   => $filters['end_date'],
                'label'      => date('d M, Y', strtotime($filters['start_date'])) . ' to ' . date('d M, Y', strtotime($filters['end_date']))
            ];
        }

        if ($pt === 'all_time') {
            return [
                'start_date' => '2010-01-01',
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
     * Build WHERE conditions for lead_payments and management_cashin
     */
    protected function buildLeadPaymentsConditions($startDate, $endDate, $filters)
    {
        $conds = [
            "lp.payment_date BETWEEN '{$startDate}' AND '{$endDate}'",
            "lp.is_trashed = 0",
            "COALESCE(lp.is_emi_discount, 0) = 0"
        ];

        if (!empty($filters['project_id'])) {
            $pId = (int)$filters['project_id'];
            $conds[] = "lb.project_id = {$pId}";
        }

        if (!empty($filters['payment_method'])) {
            $pm = $this->db->real_escape_string($filters['payment_method']);
            $conds[] = "lp.payment_method = '{$pm}'";
        }

        if (!empty($filters['bank_name'])) {
            $bn = $this->db->real_escape_string($filters['bank_name']);
            $conds[] = "lp.bank_name LIKE '%{$bn}%'";
        }

        if (!empty($filters['payment_type'])) {
            $pt = $filters['payment_type'];
            if ($pt === 'emi') {
                $conds[] = "lp.payment_type IN ('emi', 'emi_due_settlement')";
            } elseif ($pt === 'management') {
                // If payment_type is 'management', client payments should not match
                $conds[] = "1 = 0";
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
            $conds[] = "(l.full_name LIKE '%{$s}%' OR l.phone LIKE '%{$s}%' OR p.name LIKE '%{$s}%' OR f.flat_name LIKE '%{$s}%' OR lp.transaction_ref LIKE '%{$s}%' OR lp.cheque_no LIKE '%{$s}%')";
        }

        return implode(" AND ", $conds);
    }

    protected function buildManagementConditions($startDate, $endDate, $filters)
    {
        // If specific project is filtered, management has no project -> returns 0 rows
        if (!empty($filters['project_id'])) {
            return "1 = 0";
        }

        // If client-only payment type is selected, management doesn't match
        if (!empty($filters['payment_type']) && $filters['payment_type'] !== 'management') {
            return "1 = 0";
        }

        $conds = [
            "mci.transaction_date BETWEEN '{$startDate}' AND '{$endDate}'",
            "mci.payment_status = 'paid'"
        ];

        if (!empty($filters['payment_method'])) {
            $pm = $this->db->real_escape_string($filters['payment_method']);
            $conds[] = "mci.payment_method = '{$pm}'";
        }

        if (!empty($filters['bank_name'])) {
            $bn = $this->db->real_escape_string($filters['bank_name']);
            $conds[] = "mci.bank_name LIKE '%{$bn}%'";
        }

        if (!empty($filters['verification_status']) && $filters['verification_status'] !== 'verified') {
            // Management payments are approved/verified upon recorded
            return "1 = 0";
        }

        if (!empty($filters['search'])) {
            $s = $this->db->real_escape_string($filters['search']);
            $conds[] = "(mci.person_name LIKE '%{$s}%' OR mci.role_name LIKE '%{$s}%' OR mci.source LIKE '%{$s}%' OR mci.description LIKE '%{$s}%' OR mci.transaction_ref LIKE '%{$s}%' OR mci.cheque_no LIKE '%{$s}%')";
        }

        return implode(" AND ", $conds);
    }

    /**
     * Fetch aggregated summary totals
     */
    protected function fetchSummary($startDate, $endDate, $filters)
    {
        $summary = [
            'total_amount'       => 0,
            'total_count'        => 0,
            'bank_amount'        => 0,
            'bank_count'         => 0,
            'cash_amount'        => 0,
            'cash_count'         => 0,
            'cheque_amount'      => 0,
            'cheque_count'       => 0,
            'online_amount'      => 0,
            'online_count'       => 0,
            'client_amount'      => 0,
            'client_count'       => 0,
            'management_amount'  => 0,
            'management_count'   => 0,
            'verified_amount'    => 0,
            'pending_amount'     => 0,
            'booking_money'      => 0,
            'registration'       => 0,
            'land_share'         => 0,
            'emi'                => 0,
            'development_charge' => 0,
            'shop_service_charge'=> 0,
            'by_bank'            => []
        ];

        // 1. Client Lead Payments
        if ($filters['source'] === 'all' || $filters['source'] === 'client') {
            $whereLp = $this->buildLeadPaymentsConditions($startDate, $endDate, $filters);
            $sqlLp = "
                SELECT 
                    COUNT(*) as total_count,
                    COALESCE(SUM(lp.amount), 0) as total_amount,
                    COALESCE(SUM(CASE WHEN lp.payment_method = 'bank' THEN lp.amount ELSE 0 END), 0) as bank_amount,
                    COUNT(CASE WHEN lp.payment_method = 'bank' THEN 1 ELSE NULL END) as bank_count,
                    COALESCE(SUM(CASE WHEN lp.payment_method = 'cash' THEN lp.amount ELSE 0 END), 0) as cash_amount,
                    COUNT(CASE WHEN lp.payment_method = 'cash' THEN 1 ELSE NULL END) as cash_count,
                    COALESCE(SUM(CASE WHEN lp.payment_method = 'cheque' THEN lp.amount ELSE 0 END), 0) as cheque_amount,
                    COUNT(CASE WHEN lp.payment_method = 'cheque' THEN 1 ELSE NULL END) as cheque_count,
                    COALESCE(SUM(CASE WHEN lp.payment_method = 'online' THEN lp.amount ELSE 0 END), 0) as online_amount,
                    COUNT(CASE WHEN lp.payment_method = 'online' THEN 1 ELSE NULL END) as online_count,
                    COALESCE(SUM(CASE WHEN lp.verification_status = 'verified' THEN lp.amount ELSE 0 END), 0) as verified_amount,
                    COALESCE(SUM(CASE WHEN lp.verification_status = 'pending' THEN lp.amount ELSE 0 END), 0) as pending_amount,
                    COALESCE(SUM(CASE WHEN lp.payment_type = 'booking_money' THEN lp.amount ELSE 0 END), 0) as booking_money,
                    COALESCE(SUM(CASE WHEN lp.payment_type = 'registration' THEN lp.amount ELSE 0 END), 0) as registration,
                    COALESCE(SUM(CASE WHEN lp.payment_type = 'land_share' THEN lp.amount ELSE 0 END), 0) as land_share,
                    COALESCE(SUM(CASE WHEN lp.payment_type IN ('emi', 'emi_due_settlement') THEN lp.amount ELSE 0 END), 0) as emi,
                    COALESCE(SUM(CASE WHEN lp.payment_type = 'development_charge' THEN lp.amount ELSE 0 END), 0) as development_charge,
                    COALESCE(SUM(CASE WHEN lp.payment_type = 'shop_service_charge' THEN lp.amount ELSE 0 END), 0) as shop_service_charge
                FROM lead_payments lp
                INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
                INNER JOIN leads l ON lb.lead_id = l.id
                LEFT JOIN projects p ON lb.project_id = p.id
                LEFT JOIN flats f ON lb.flat_id = f.id
                WHERE {$whereLp}
            ";

            $resLp = $this->db->query($sqlLp);
            if ($resLp && $row = $resLp->fetch_assoc()) {
                $summary['client_amount']      += (float)$row['total_amount'];
                $summary['client_count']       += (int)$row['total_count'];
                $summary['total_amount']       += (float)$row['total_amount'];
                $summary['total_count']        += (int)$row['total_count'];
                $summary['bank_amount']        += (float)$row['bank_amount'];
                $summary['bank_count']         += (int)$row['bank_count'];
                $summary['cash_amount']        += (float)$row['cash_amount'];
                $summary['cash_count']         += (int)$row['cash_count'];
                $summary['cheque_amount']      += (float)$row['cheque_amount'];
                $summary['cheque_count']       += (int)$row['cheque_count'];
                $summary['online_amount']      += (float)$row['online_amount'];
                $summary['online_count']       += (int)$row['online_count'];
                $summary['verified_amount']    += (float)$row['verified_amount'];
                $summary['pending_amount']     += (float)$row['pending_amount'];
                $summary['booking_money']      += (float)$row['booking_money'];
                $summary['registration']       += (float)$row['registration'];
                $summary['land_share']         += (float)$row['land_share'];
                $summary['emi']                += (float)$row['emi'];
                $summary['development_charge'] += (float)$row['development_charge'];
                $summary['shop_service_charge']+= (float)$row['shop_service_charge'];
            }

            // Bank breakdown for client payments
            $sqlBank = "
                SELECT 
                    COALESCE(NULLIF(TRIM(lp.bank_name), ''), 'Cash / Unassigned') as bname,
                    SUM(lp.amount) as btotal,
                    COUNT(*) as bcount
                FROM lead_payments lp
                INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
                INNER JOIN leads l ON lb.lead_id = l.id
                LEFT JOIN projects p ON lb.project_id = p.id
                LEFT JOIN flats f ON lb.flat_id = f.id
                WHERE {$whereLp} AND lp.payment_method IN ('bank', 'cheque')
                GROUP BY bname
                ORDER BY btotal DESC
            ";
            $resBank = $this->db->query($sqlBank);
            if ($resBank) {
                while ($brow = $resBank->fetch_assoc()) {
                    $summary['by_bank'][$brow['bname']] = [
                        'amount' => (float)$brow['btotal'],
                        'count'  => (int)$brow['bcount']
                    ];
                }
            }
        }

        // 2. Management Cash In
        if ($filters['source'] === 'all' || $filters['source'] === 'management') {
            $whereMci = $this->buildManagementConditions($startDate, $endDate, $filters);
            $sqlMci = "
                SELECT 
                    COUNT(*) as total_count,
                    COALESCE(SUM(mci.amount), 0) as total_amount,
                    COALESCE(SUM(CASE WHEN mci.payment_method = 'bank' THEN mci.amount ELSE 0 END), 0) as bank_amount,
                    COUNT(CASE WHEN mci.payment_method = 'bank' THEN 1 ELSE NULL END) as bank_count,
                    COALESCE(SUM(CASE WHEN mci.payment_method = 'cash' THEN mci.amount ELSE 0 END), 0) as cash_amount,
                    COUNT(CASE WHEN mci.payment_method = 'cash' THEN 1 ELSE NULL END) as cash_count,
                    COALESCE(SUM(CASE WHEN mci.payment_method = 'cheque' THEN mci.amount ELSE 0 END), 0) as cheque_amount,
                    COUNT(CASE WHEN mci.payment_method = 'cheque' THEN 1 ELSE NULL END) as cheque_count,
                    COALESCE(SUM(CASE WHEN mci.payment_method = 'online' THEN mci.amount ELSE 0 END), 0) as online_amount,
                    COUNT(CASE WHEN mci.payment_method = 'online' THEN 1 ELSE NULL END) as online_count
                FROM management_cashin mci
                WHERE {$whereMci}
            ";

            $resMci = $this->db->query($sqlMci);
            if ($resMci && $row = $resMci->fetch_assoc()) {
                $summary['management_amount'] += (float)$row['total_amount'];
                $summary['management_count']  += (int)$row['total_count'];
                $summary['total_amount']      += (float)$row['total_amount'];
                $summary['total_count']       += (int)$row['total_count'];
                $summary['bank_amount']       += (float)$row['bank_amount'];
                $summary['bank_count']        += (int)$row['bank_count'];
                $summary['cash_amount']       += (float)$row['cash_amount'];
                $summary['cash_count']        += (int)$row['cash_count'];
                $summary['cheque_amount']     += (float)$row['cheque_amount'];
                $summary['cheque_count']      += (int)$row['cheque_count'];
                $summary['online_amount']     += (float)$row['online_amount'];
                $summary['online_count']      += (int)$row['online_count'];
            }
        }

        // Add convenience aliases for print views and reports
        $summary['total_collection']      = $summary['total_amount'];
        $summary['bank_collection']       = $summary['bank_amount'];
        $summary['cash_collection']       = $summary['cash_amount'];
        $summary['cheque_collection']     = $summary['cheque_amount'];
        $summary['online_collection']     = $summary['online_amount'];
        $summary['client_collection']     = $summary['client_amount'];
        $summary['management_collection'] = $summary['management_amount'];
        $summary['type_breakdown'] = [
            'booking_money'       => $summary['booking_money'],
            'registration'        => $summary['registration'],
            'land_share'          => $summary['land_share'],
            'emi'                 => $summary['emi'],
            'development_charge'  => $summary['development_charge'],
            'shop_service_charge' => $summary['shop_service_charge'],
            'management'          => $summary['management_amount'],
        ];
        $summary['bank_breakdown'] = [];
        if (!empty($summary['by_bank'])) {
            foreach ($summary['by_bank'] as $bName => $bData) {
                $summary['bank_breakdown'][$bName] = is_array($bData) ? ($bData['amount'] ?? 0) : (float)$bData;
            }
        }

        return $summary;
    }

    /**
     * Fetch complete payment type matrix (Booking, Registration, Land Share, EMI, Dev Charge, Shop Service, Management)
     */
    protected function fetchPaymentTypeSummary($startDate, $endDate, $filters)
    {
        $types = [
            'booking_money' => [
                'key'         => 'booking_money',
                'name'        => 'Booking Money',
                'description' => 'Advance & Booking Money Deposits',
                'count'       => 0,
                'total_amount'=> 0.0,
                'bank_amount' => 0.0,
                'cash_amount' => 0.0,
                'cheque_amount'=> 0.0,
                'online_amount'=> 0.0,
                'percentage'  => 0.0,
                'badge_class' => 'bg-primary-subtle text-primary border border-primary-subtle'
            ],
            'registration' => [
                'key'         => 'registration',
                'name'        => 'Registration Fee',
                'description' => 'Deed & Flat Registration Charges',
                'count'       => 0,
                'total_amount'=> 0.0,
                'bank_amount' => 0.0,
                'cash_amount' => 0.0,
                'cheque_amount'=> 0.0,
                'online_amount'=> 0.0,
                'percentage'  => 0.0,
                'badge_class' => 'bg-info-subtle text-info border border-info-subtle'
            ],
            'land_share' => [
                'key'         => 'land_share',
                'name'        => 'Land Share',
                'description' => 'Land Share Cost Collections',
                'count'       => 0,
                'total_amount'=> 0.0,
                'bank_amount' => 0.0,
                'cash_amount' => 0.0,
                'cheque_amount'=> 0.0,
                'online_amount'=> 0.0,
                'percentage'  => 0.0,
                'badge_class' => 'bg-success-subtle text-success border border-success-subtle'
            ],
            'emi' => [
                'key'         => 'emi',
                'name'        => 'EMI / Monthly Installment',
                'description' => 'Monthly Installments & Construction Collections',
                'count'       => 0,
                'total_amount'=> 0.0,
                'bank_amount' => 0.0,
                'cash_amount' => 0.0,
                'cheque_amount'=> 0.0,
                'online_amount'=> 0.0,
                'percentage'  => 0.0,
                'badge_class' => 'bg-warning-subtle text-warning border border-warning-subtle'
            ],
            'development_charge' => [
                'key'         => 'development_charge',
                'name'        => 'Development Charge',
                'description' => 'Site & Utility Development Fees',
                'count'       => 0,
                'total_amount'=> 0.0,
                'bank_amount' => 0.0,
                'cash_amount' => 0.0,
                'cheque_amount'=> 0.0,
                'online_amount'=> 0.0,
                'percentage'  => 0.0,
                'badge_class' => 'bg-secondary-subtle text-secondary border border-secondary-subtle'
            ],
            'shop_service_charge' => [
                'key'         => 'shop_service_charge',
                'name'        => 'Shop Service Charge',
                'description' => 'Commercial Unit Service & Maintenance',
                'count'       => 0,
                'total_amount'=> 0.0,
                'bank_amount' => 0.0,
                'cash_amount' => 0.0,
                'cheque_amount'=> 0.0,
                'online_amount'=> 0.0,
                'percentage'  => 0.0,
                'badge_class' => 'bg-purple-subtle text-purple border border-purple-subtle'
            ],
            'management' => [
                'key'         => 'management',
                'name'        => 'Management Cash In',
                'description' => 'Company Internal Capital & Inflows',
                'count'       => 0,
                'total_amount'=> 0.0,
                'bank_amount' => 0.0,
                'cash_amount' => 0.0,
                'cheque_amount'=> 0.0,
                'online_amount'=> 0.0,
                'percentage'  => 0.0,
                'badge_class' => 'bg-dark-subtle text-dark border border-dark-subtle'
            ],
        ];

        $grand = [
            'count'        => 0,
            'total_amount' => 0.0,
            'bank_amount'  => 0.0,
            'cash_amount'  => 0.0,
            'cheque_amount'=> 0.0,
            'online_amount'=> 0.0
        ];

        // 1. Query Client Payments
        if ($filters['source'] === 'all' || $filters['source'] === 'client') {
            $matrixFilters = $filters;
            // In the type summary matrix, we want to see all types unless the user specifically filtered
            $whereLp = $this->buildLeadPaymentsConditions($startDate, $endDate, $matrixFilters);
            $sql = "
                SELECT 
                    CASE 
                        WHEN lp.payment_type IN ('emi', 'emi_due_settlement') THEN 'emi'
                        ELSE lp.payment_type 
                    END as ptype,
                    COUNT(*) as count,
                    COALESCE(SUM(lp.amount), 0) as total_amount,
                    COALESCE(SUM(CASE WHEN lp.payment_method = 'bank' THEN lp.amount ELSE 0 END), 0) as bank_amount,
                    COALESCE(SUM(CASE WHEN lp.payment_method = 'cash' THEN lp.amount ELSE 0 END), 0) as cash_amount,
                    COALESCE(SUM(CASE WHEN lp.payment_method = 'cheque' THEN lp.amount ELSE 0 END), 0) as cheque_amount,
                    COALESCE(SUM(CASE WHEN lp.payment_method = 'online' THEN lp.amount ELSE 0 END), 0) as online_amount
                FROM lead_payments lp
                INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
                INNER JOIN leads l ON lb.lead_id = l.id
                LEFT JOIN projects p ON lb.project_id = p.id
                LEFT JOIN flats f ON lb.flat_id = f.id
                WHERE {$whereLp}
                GROUP BY ptype
            ";
            $res = $this->db->query($sql);
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $pt = $row['ptype'];
                    if (!isset($types[$pt])) {
                        $types[$pt] = [
                            'key'         => $pt,
                            'name'        => ucwords(str_replace('_', ' ', $pt)),
                            'name_bn'     => ucwords(str_replace('_', ' ', $pt)),
                            'count'       => 0,
                            'total_amount'=> 0.0,
                            'bank_amount' => 0.0,
                            'cash_amount' => 0.0,
                            'cheque_amount'=> 0.0,
                            'online_amount'=> 0.0,
                            'percentage'  => 0.0,
                            'badge_class' => 'bg-light text-dark border'
                        ];
                    }
                    $types[$pt]['count']        += (int)$row['count'];
                    $types[$pt]['total_amount'] += (float)$row['total_amount'];
                    $types[$pt]['bank_amount']  += (float)$row['bank_amount'];
                    $types[$pt]['cash_amount']  += (float)$row['cash_amount'];
                    $types[$pt]['cheque_amount']+= (float)$row['cheque_amount'];
                    $types[$pt]['online_amount']+= (float)$row['online_amount'];

                    $grand['count']        += (int)$row['count'];
                    $grand['total_amount'] += (float)$row['total_amount'];
                    $grand['bank_amount']  += (float)$row['bank_amount'];
                    $grand['cash_amount']  += (float)$row['cash_amount'];
                    $grand['cheque_amount']+= (float)$row['cheque_amount'];
                    $grand['online_amount']+= (float)$row['online_amount'];
                }
            }
        }

        // 2. Query Management Cash In
        if ($filters['source'] === 'all' || $filters['source'] === 'management') {
            $whereMci = $this->buildManagementConditions($startDate, $endDate, $filters);
            $sqlMci = "
                SELECT 
                    COUNT(*) as count,
                    COALESCE(SUM(mci.amount), 0) as total_amount,
                    COALESCE(SUM(CASE WHEN mci.payment_method = 'bank' THEN mci.amount ELSE 0 END), 0) as bank_amount,
                    COALESCE(SUM(CASE WHEN mci.payment_method = 'cash' THEN mci.amount ELSE 0 END), 0) as cash_amount,
                    COALESCE(SUM(CASE WHEN mci.payment_method = 'cheque' THEN mci.amount ELSE 0 END), 0) as cheque_amount,
                    COALESCE(SUM(CASE WHEN mci.payment_method = 'online' THEN mci.amount ELSE 0 END), 0) as online_amount
                FROM management_cashin mci
                WHERE {$whereMci}
            ";
            $resMci = $this->db->query($sqlMci);
            if ($resMci && $row = $resMci->fetch_assoc()) {
                if ((float)$row['total_amount'] > 0 || (int)$row['count'] > 0) {
                    $types['management']['count']        += (int)$row['count'];
                    $types['management']['total_amount'] += (float)$row['total_amount'];
                    $types['management']['bank_amount']  += (float)$row['bank_amount'];
                    $types['management']['cash_amount']  += (float)$row['cash_amount'];
                    $types['management']['cheque_amount']+= (float)$row['cheque_amount'];
                    $types['management']['online_amount']+= (float)$row['online_amount'];

                    $grand['count']        += (int)$row['count'];
                    $grand['total_amount'] += (float)$row['total_amount'];
                    $grand['bank_amount']  += (float)$row['bank_amount'];
                    $grand['cash_amount']  += (float)$row['cash_amount'];
                    $grand['cheque_amount']+= (float)$row['cheque_amount'];
                    $grand['online_amount']+= (float)$row['online_amount'];
                }
            }
        }

        // Calculate percentages
        foreach ($types as $k => &$t) {
            $t['percentage'] = $grand['total_amount'] > 0 ? round(($t['total_amount'] / $grand['total_amount']) * 100, 2) : 0.0;
        }
        unset($t);

        return [
            'rows'  => $types,
            'grand' => $grand
        ];
    }

    /**
     * Fetch list of collection transactions with client & management union
     */
    protected function fetchTransactions($startDate, $endDate, $filters, $limit = 50, $offset = 0)
    {
        $queries = [];

        // Client Query
        if ($filters['source'] === 'all' || $filters['source'] === 'client') {
            $whereLp = $this->buildLeadPaymentsConditions($startDate, $endDate, $filters);
            $queries[] = "
                SELECT 
                    'client' AS source,
                    'client' AS source_type,
                    lp.id,
                    lp.booking_id,
                    lp.payment_date AS date,
                    lp.payment_date AS transaction_date,
                    lp.payment_type,
                    lp.amount,
                    lp.payment_method,
                    lp.bank_name,
                    lp.cheque_no,
                    lp.transaction_ref,
                    lp.transaction_ref AS money_receipt_no,
                    lp.payment_status,
                    lp.verification_status,
                    l.full_name AS client_name,
                    l.phone AS client_phone,
                    l.phone AS phone,
                    p.id AS project_id,
                    p.name AS project_name,
                    f.flat_name,
                    f.floor_no,
                    lp.created_at
                FROM lead_payments lp
                INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
                INNER JOIN leads l ON lb.lead_id = l.id
                LEFT JOIN projects p ON lb.project_id = p.id
                LEFT JOIN flats f ON lb.flat_id = f.id
                WHERE {$whereLp}
            ";
        }

        // Management Query
        if ($filters['source'] === 'all' || $filters['source'] === 'management') {
            $whereMci = $this->buildManagementConditions($startDate, $endDate, $filters);
            $queries[] = "
                SELECT 
                    'management' AS source,
                    'management' AS source_type,
                    mci.id,
                    0 AS booking_id,
                    mci.transaction_date AS date,
                    mci.transaction_date AS transaction_date,
                    'management' AS payment_type,
                    mci.amount,
                    mci.payment_method,
                    mci.bank_name,
                    mci.cheque_no,
                    mci.transaction_ref,
                    mci.transaction_ref AS money_receipt_no,
                    mci.payment_status,
                    'verified' AS verification_status,
                    CONCAT(mci.person_name, ' (', mci.role_name, ')') AS client_name,
                    NULL AS client_phone,
                    NULL AS phone,
                    NULL AS project_id,
                    'Management' AS project_name,
                    mci.source AS flat_name,
                    NULL AS floor_no,
                    mci.created_at
                FROM management_cashin mci
                WHERE {$whereMci}
            ";
        }

        if (empty($queries)) {
            return [];
        }

        $unionSql = implode(" UNION ALL ", $queries);
        $finalSql = "{$unionSql} ORDER BY date DESC, id DESC LIMIT {$limit} OFFSET {$offset}";

        $res = $this->db->query($finalSql);
        $rows = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
        }
        return $rows;
    }

    /**
     * Get list of projects for dropdown
     */
    protected function fetchProjects()
    {
        $res = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC");
        $projects = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $projects[] = $r;
            }
        }
        return $projects;
    }

    /**
     * Get distinct bank names present in lead_payments for filter dropdown
     */
    protected function fetchDistinctBankNames()
    {
        $res = $this->db->query("
            SELECT DISTINCT bank_name 
            FROM lead_payments 
            WHERE bank_name IS NOT NULL AND bank_name != '' AND bank_name != 'N/A' AND bank_name != 'Cash'
            ORDER BY bank_name ASC
        ");
        $banks = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $banks[] = trim($r['bank_name']);
            }
        }
        return array_unique($banks);
    }
}

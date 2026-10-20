<?php
require_once __DIR__ . '/../models/CentralStatement.php';

class CentralStatementController extends Controller
{
    protected $db;
    protected $statementModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->statementModel = new CentralStatement($this->db);
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    /**
     * Main index - Show central statement page
     */
public function index()
{
    $isPrint = isset($_GET['print']) && $_GET['print'] == 'true';
    if ($isPrint) {
        $this->printStatement();
        return;
    }

    $periodType = $_GET['period_type'] ?? 'monthly';
    $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
    $month = $_GET['month'] ?? date('m');
    $startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : null;
    $endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : null;
    $projectId = !empty($_GET['project_id']) ? (int)$_GET['project_id'] : null;
    $category = $_GET['category'] ?? 'all';

    if (empty($_GET['period_type']) && $startDate && $endDate) {
        $periodType = 'custom';
    }

    $statementData = null;
    $periodText = '';
    $statementStartDate = '';
    $statementEndDate = '';

    if ($periodType === 'all_time') {
        $statementData = $this->statementModel->getAllTimeStatement($projectId, $category);
        $periodText = 'All Time';
        $statementStartDate = $statementData['start_date'] ?? date('Y-01-01');
        $statementEndDate = $statementData['end_date'] ?? date('Y-12-31');
    } elseif ($periodType === 'custom') {
        if ($startDate && $endDate) {
            $statementData = $this->statementModel->getCustomRangeStatement($startDate, $endDate, $projectId, $category);
            $periodText = date('d M, Y', strtotime($startDate)) . ' to ' . date('d M, Y', strtotime($endDate));
            $statementStartDate = $startDate;
            $statementEndDate = $endDate;
        } else {
            $year = (int)date('Y');
            $mInt = (int)date('m');
            $statementData = $this->statementModel->getMonthlyStatement($year, $mInt, $projectId, $category);
            $periodText = date('F Y');
            $periodType = 'monthly';
            $statementStartDate = date('Y-m-01');
            $statementEndDate = date('Y-m-t');
        }
    } elseif ($periodType === '6months') {
        $mInt = ($month === 'all' || empty($month) || (int)$month < 1 || (int)$month > 12) ? (int)date('m') : (int)$month;
        $statementData = $this->statementModel->getSixMonthStatement($year, $mInt, $projectId, $category);
        $periodText = 'Last 6 Months';
        $statementStartDate = date('Y-m-01', strtotime("-5 months", strtotime("$year-" . sprintf('%02d', $mInt) . "-01")));
        $statementEndDate = date('Y-m-t', strtotime("$year-" . sprintf('%02d', $mInt) . "-01"));
    } elseif ($periodType === 'yearly') {
        $statementData = $this->statementModel->getYearlyStatement($year, $projectId, $category);
        $periodText = "Year $year";
        $statementStartDate = "$year-01-01";
        $statementEndDate = "$year-12-31";
    } elseif ($month === 'all' || $month === '0' || $month === 0) {
        $statementData = $this->statementModel->getYearlyStatement($year, $projectId, $category);
        $periodText = "All Months ($year)";
        $statementStartDate = "$year-01-01";
        $statementEndDate = "$year-12-31";
    } else {
        $mInt = (int)$month;
        $statementData = $this->statementModel->getMonthlyStatement($year, $mInt, $projectId, $category);
        $periodText = date('F Y', strtotime("$year-" . sprintf('%02d', $mInt) . "-01"));
        $statementStartDate = "$year-" . sprintf('%02d', $mInt) . "-01";
        $statementEndDate = date('Y-m-t', strtotime($statementStartDate));
    }

    $projects = $this->getProjects();

    $overallStats = [
        'total_agreement' => $statementData['summary']['total_agreement_value'] ?? 0,
        'total_service_fee' => $statementData['summary']['total_service_fee'] ?? 0,
        'total_management_cashin' => $statementData['summary']['total_management_cashin'] ?? 0,
        'total_cashin' => $statementData['summary']['total_cashin'] ?? 0,
        'total_revenue' => $statementData['summary']['total_revenue'] ?? 0,
        'total_cash_out' => $statementData['summary']['total_cash_out'] ?? 0,
        'net_balance' => $statementData['summary']['net_balance'] ?? 0,
        'final_balance' => $statementData['summary']['final_balance'] ?? 0
    ];

    $revenueData = null;
    if ($statementStartDate && $statementEndDate) {
        $revenueData = $this->statementModel->calculateRevenue($statementStartDate, $statementEndDate, $projectId);
    }

    // Fetch client payment details (includes Client Name, Project Name, Flat Name)
    $clientPaymentDetails = [];
    if ($statementStartDate && $statementEndDate) {
        $clientPaymentDetails = $this->statementModel->getClientPaymentDetails(
            $statementStartDate,
            $statementEndDate,
            $projectId
        );
    }

    // Fetch cashout payment details
    $cashoutPaymentDetails = [];
    if ($category === 'cashout' && $statementStartDate && $statementEndDate) {
        $cashoutPaymentDetails = $this->statementModel->getCashoutPaymentDetails(
            $statementStartDate,
            $statementEndDate,
            $projectId
        );
    }

    // Fetch vendor payment details
    $vendorPaymentDetails = [];
    if ($category === 'vendor_payment' && $statementStartDate && $statementEndDate) {
        $vendorPaymentDetails = $this->statementModel->getVendorPaymentDetails(
            $statementStartDate,
            $statementEndDate,
            $projectId
        );
    }

    // Fetch contractor bill details
    $contractorBillDetails = [];
    if ($category === 'contractor_bill' && $statementStartDate && $statementEndDate) {
        $contractorBillDetails = $this->statementModel->getContractorBillDetails(
            $statementStartDate,
            $statementEndDate,
            $projectId
        );
    }

    // Fetch bank transactions
    $bankTransactions = [];
    if (in_array($category, ['bank_cashin', 'bank_cashout']) && $statementStartDate && $statementEndDate) {
        $bankTransactions = $this->getBankTransactions($category, $statementStartDate, $statementEndDate, $projectId);
    }

    $viewData = [
        'current_route' => 'central-statement',
        'page_title' => 'Central Financial Statement',
        'period_type' => $periodType,
        'period_text' => $periodText,
        'year' => $year,
        'month' => $month === 'all' ? 'all' : sprintf('%02d', (int)$month),
        'start_date' => $startDate,
        'end_date' => $endDate,
        'project_id' => $projectId,
        'category' => $category,
        'projects' => $projects,
        'statement_data' => $statementData,
        'overall_stats' => $overallStats,
        'revenue_data' => $revenueData,
        'client_payment_details' => $clientPaymentDetails,
        'cashout_payment_details' => $cashoutPaymentDetails,
        'vendor_payment_details' => $vendorPaymentDetails,
        'contractor_bill_details' => $contractorBillDetails,
        'bank_transactions' => $bankTransactions,
    ];

    $this->view('accounts/central-statement', $viewData);
}

    /**
     * Get projects list
     */
    private function getProjects()
    {
        return $this->statementModel->getProjects();
    }

    /**
     * Print statement - separate method for print view
     */
public function printStatement()
{
    // Get period type from query params
    $periodType = $_GET['period_type'] ?? 'monthly';
    $year = $_GET['year'] ?? date('Y');
    $month = $_GET['month'] ?? date('m');
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    $projectId = $_GET['project_id'] ?? null;
    $category = $_GET['category'] ?? 'all';

    // Get statement data based on period type
    $statementData = null;
    $periodText = '';
    $statementStartDate = '';
    $statementEndDate = '';

    if ($periodType === 'all_time') {
        $statementData = $this->statementModel->getAllTimeStatement($projectId, $category);
        $periodText = 'All Time';
        $statementStartDate = $statementData['start_date'] ?? date('Y-01-01');
        $statementEndDate = $statementData['end_date'] ?? date('Y-12-31');
    } elseif ($periodType === 'custom') {
        if ($startDate && $endDate) {
            $statementData = $this->statementModel->getCustomRangeStatement($startDate, $endDate, $projectId, $category);
            $periodText = date('d M, Y', strtotime($startDate)) . ' to ' . date('d M, Y', strtotime($endDate));
            $statementStartDate = $startDate;
            $statementEndDate = $endDate;
        } else {
            $year = (int)date('Y');
            $mInt = (int)date('m');
            $statementData = $this->statementModel->getMonthlyStatement($year, $mInt, $projectId, $category);
            $periodText = date('F Y');
            $periodType = 'monthly';
            $statementStartDate = date('Y-m-01');
            $statementEndDate = date('Y-m-t');
        }
    } elseif ($periodType === '6months') {
        $mInt = ($month === 'all' || empty($month) || (int)$month < 1 || (int)$month > 12) ? (int)date('m') : (int)$month;
        $statementData = $this->statementModel->getSixMonthStatement($year, $mInt, $projectId, $category);
        $periodText = 'Last 6 Months';
        $statementStartDate = date('Y-m-01', strtotime("-5 months", strtotime("$year-" . sprintf('%02d', $mInt) . "-01")));
        $statementEndDate = date('Y-m-t', strtotime("$year-" . sprintf('%02d', $mInt) . "-01"));
    } elseif ($periodType === 'yearly') {
        $statementData = $this->statementModel->getYearlyStatement($year, $projectId, $category);
        $periodText = "Year $year";
        $statementStartDate = "$year-01-01";
        $statementEndDate = "$year-12-31";
    } elseif ($month === 'all' || $month === '0' || $month === 0) {
        $statementData = $this->statementModel->getYearlyStatement($year, $projectId, $category);
        $periodText = "All Months ($year)";
        $statementStartDate = "$year-01-01";
        $statementEndDate = "$year-12-31";
    } else {
        $mInt = (int)$month;
        $statementData = $this->statementModel->getMonthlyStatement($year, $mInt, $projectId, $category);
        $periodText = date('F Y', strtotime("$year-" . sprintf('%02d', $mInt) . "-01"));
        $statementStartDate = "$year-" . sprintf('%02d', $mInt) . "-01";
        $statementEndDate = date('Y-m-t', strtotime($statementStartDate));
    }

    // FIXED: Use consistent variable naming
    $statement_data = $statementData;
    
    // FIXED: Extract summary stats from the statement data
    $overall_stats = [
        'total_agreement' => $statement_data['summary']['total_agreement_value'] ?? 0,
        'total_service_fee' => $statement_data['summary']['total_service_fee'] ?? 0,
        'total_management_cashin' => $statement_data['summary']['total_management_cashin'] ?? 0,
        'total_cashin' => $statement_data['summary']['total_cashin'] ?? 0,
        'total_revenue' => $statement_data['summary']['total_revenue'] ?? 0,
        'total_cash_out' => $statement_data['summary']['total_cash_out'] ?? 0,
        'net_balance' => $statement_data['summary']['net_balance'] ?? 0,
        'final_balance' => $statement_data['summary']['final_balance'] ?? 0
    ];

    // Calculate revenue for the statement period
    $revenue_data = null;
    if ($statementStartDate && $statementEndDate) {
        $revenue_data = $this->statementModel->calculateRevenue($statementStartDate, $statementEndDate, $projectId);
    }

    // Get cashout payment details for the selected period (if cashout category is selected)
    $cashout_payment_details = [];
    if ($category === 'cashout' && $statementStartDate && $statementEndDate) {
        $cashout_payment_details = $this->getCashoutPaymentDetails($statementStartDate, $statementEndDate, $projectId);
    }

    // Company information
    $company_name = "THE PREMIUM HOMES LTD.";
    $company_address = "Land View Commercial Center, 9th Floor 28 Gulshan North C/A, Gulshan Circle-2, Dhaka";
    $company_phone = "+8801958253300";
    $company_email = "info@thepremiumhomes.com";

    // Get project name if filtered
    $projectName = '';
    if ($projectId) {
        $query = "SELECT name FROM projects WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $projectId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $projectName = $row['name'];
        }
    }

    // Get category name
    $categoryName = '';
    if ($category !== 'all') {
        $categoryName = ucfirst(str_replace('_', ' ', $category));
        // Special handling
        if ($category === 'cashout') {
            $categoryName = 'Cash Out';
        } elseif ($category === 'utilities') {
            $categoryName = 'Utilities (Bill/Electricity/Wifi/etc)';
        } elseif ($category === 'bank_cashin') {
            $categoryName = 'Bank Cash In';
        } elseif ($category === 'bank_cashout') {
            $categoryName = 'Bank Cash Out';
        } elseif ($category === 'vendor_payment') {
            $categoryName = 'Vendor Payments';
        } elseif ($category === 'contractor_bill') {
            $categoryName = 'Contractor Bills';
        }
    }

    // FIXED: Pass all variables needed by the print view
    $viewData = [
        'statement_data' => $statement_data,
        'overall_stats' => $overall_stats,
        'period_text' => $periodText,
        'start_date' => $statementStartDate,
        'end_date' => $statementEndDate,
        'category' => $category,
        'projectName' => $projectName,
        'categoryName' => $categoryName,
        'company_name' => $company_name,
        'company_address' => $company_address,
        'company_phone' => $company_phone,
        'company_email' => $company_email,
        'period_type' => $periodType,
        'year' => $year,
        'month' => $month,
        'cashout_payment_details' => $cashout_payment_details, // ADDED THIS LINE
    ];

    // FIXED: If utilities category, get transactions
    if ($category === 'utilities') {
        $utilitiesTransactions = $this->statementModel->getUtilitiesTransactions($statementStartDate, $statementEndDate);
        $viewData['utilitiesTransactions'] = $utilitiesTransactions;
    }

    // Fetch vendor payment details for print
    if ($category === 'vendor_payment' && $statementStartDate && $statementEndDate) {
        $viewData['vendor_payment_details'] = $this->statementModel->getVendorPaymentDetails(
            $statementStartDate, $statementEndDate, $projectId
        );
    }

    // Fetch contractor bill details for print
    if ($category === 'contractor_bill' && $statementStartDate && $statementEndDate) {
        $viewData['contractor_bill_details'] = $this->statementModel->getContractorBillDetails(
            $statementStartDate, $statementEndDate, $projectId
        );
    }

    // Fetch bank transactions for bank_cashin / bank_cashout
    if (in_array($category, ['bank_cashin', 'bank_cashout']) && $statementStartDate && $statementEndDate) {
        $viewData['bank_transactions'] = $this->getBankTransactions($category, $statementStartDate, $statementEndDate, $projectId);
    }

    // FIXED: Include the print template with all variables
    extract($viewData);
    include __DIR__ . '/../views/accounts/central-statement-print.php';
    exit;
}

/**
 * Get cashout payment details grouped by date
 */
private function getCashoutPaymentDetails($start_date, $end_date, $project_id = null)
{
    $sql = "SELECT 
                cp.*,
                cr.purpose_paid_to,
                cr.office,
                p.name as project_name,
                u.full_name as requester_name
            FROM cashout_payments cp
            LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
            LEFT JOIN projects p ON cp.project_id = p.id
            LEFT JOIN users u ON cr.requested_by = u.id
            WHERE cp.payment_date BETWEEN ? AND ?";
    
    $params = [$start_date, $end_date];
    $types = "ss";
    
    if ($project_id) {
        $sql .= " AND cp.project_id = ?";
        $params[] = $project_id;
        $types .= "i";
    }
    
    $sql .= " ORDER BY cp.payment_date DESC, cp.created_at DESC";
    
    $stmt = $this->db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $payments = [];
    while ($row = $result->fetch_assoc()) {
        $date = $row['payment_date'];
        if (!isset($payments[$date])) {
            $payments[$date] = [];
        }
        $payments[$date][] = $row;
    }
    
    $stmt->close();
    return $payments;
}

/**
 * Get bank transactions for bank_cashin or bank_cashout
 */
private function getBankTransactions($type, $startDate, $endDate, $projectId = null)
{
    $transactions = [];

    if ($type === 'bank_cashin') {
        // Bank cash-in: payments received via bank from lead_payments
        $sql = "SELECT 
                    lp.payment_date,
                    lp.amount,
                    b.bank_name,
                    b.account_name as branch_name,
                    '' as routing_number,
                    b.account_number,
                    CONCAT(l.full_name, ' — ', UPPER(REPLACE(lp.payment_type, '_', ' '))) as description,
                    lp.transaction_ref
                FROM lead_payments lp
                LEFT JOIN company_bank_accounts b ON b.id = lp.bank_id
                LEFT JOIN lead_bookings lb ON lb.id = lp.booking_id
                LEFT JOIN leads l ON l.id = lb.lead_id
                WHERE lp.payment_method = 'bank'
                AND lp.payment_date BETWEEN ? AND ?
                AND lp.verification_status = 'verified'";
        
        $params = [$startDate, $endDate];
        $types = "ss";
        
        if ($projectId) {
            $sql .= " AND lb.project_id = ?";
            $params[] = $projectId;
            $types .= "i";
        }
        
        $sql .= " ORDER BY lp.payment_date DESC, lp.id DESC";

    } else {
        // Bank cash-out: payments made via bank from cashout_payments
        $sql = "SELECT 
                    cp.payment_date,
                    cp.amount,
                    b.bank_name,
                    b.account_name as branch_name,
                    '' as routing_number,
                    b.account_number,
                    COALESCE(cr.purpose_paid_to, 'N/A') as description,
                    cp.transaction_ref
                FROM cashout_payments cp
                LEFT JOIN company_bank_accounts b ON b.id = cp.bank_id
                LEFT JOIN cashout_requests cr ON cr.id = cp.cashout_request_id
                WHERE cp.payment_method = 'bank'
                AND cp.payment_date BETWEEN ? AND ?";
        
        $params = [$startDate, $endDate];
        $types = "ss";
        
        if ($projectId) {
            $sql .= " AND cp.project_id = ?";
            $params[] = $projectId;
            $types .= "i";
        }
        
        $sql .= " ORDER BY cp.payment_date DESC, cp.id DESC";
    }

    $stmt = $this->db->prepare($sql);
    if (!$stmt) {
        error_log("getBankTransactions prepare error: " . $this->db->error);
        return [];
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    $stmt->close();

    return $transactions;
}

    /**
     * Export statement to CSV
     */
    public function exportCSV()
    {
        $periodType = $_GET['period_type'] ?? 'monthly';
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $month = $_GET['month'] ?? date('m');
        $startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : null;
        $endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : null;
        $projectId = !empty($_GET['project_id']) ? (int)$_GET['project_id'] : null;
        $category = $_GET['category'] ?? 'all';

        // Get project name if filtered
        $projectName = '';
        if ($projectId) {
            $stmt = $this->db->prepare("SELECT name FROM projects WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $projectId);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    $projectName = $row['name'];
                }
                $stmt->close();
            }
        }

        // Get category name
        $categoryName = '';
        if ($category !== 'all') {
            $categoryName = ucfirst(str_replace('_', ' ', $category));
            if ($category === 'cashout') $categoryName = 'Cash Out';
            elseif ($category === 'utilities') $categoryName = 'Utilities';
            elseif ($category === 'bank_cashin') $categoryName = 'Bank Cash In';
            elseif ($category === 'bank_cashout') $categoryName = 'Bank Cash Out';
            elseif ($category === 'vendor_payment') $categoryName = 'Vendor Payments';
            elseif ($category === 'contractor_bill') $categoryName = 'Contractor Bills';
        }

        // Get statement data
        $statementData = null;
        $filename = 'central_statement_' . date('Ymd_His') . '.csv';

        if ($periodType === 'all_time') {
            $statementData = $this->statementModel->getAllTimeStatement($projectId, $category);
            $filename = "all_time_statement_" . date('Ymd') . ".csv";
        } elseif ($periodType === 'custom') {
            if ($startDate && $endDate) {
                $statementData = $this->statementModel->getCustomRangeStatement($startDate, $endDate, $projectId, $category);
                $cleanStartDate = str_replace('-', '', $startDate);
                $cleanEndDate = str_replace('-', '', $endDate);
                $filename = "custom_statement_{$cleanStartDate}_to_{$cleanEndDate}.csv";
            } else {
                $statementData = $this->statementModel->getMonthlyStatement(date('Y'), (int)date('m'), $projectId, $category);
                $filename = "monthly_statement_" . date('Y_m') . ".csv";
            }
        } elseif ($periodType === '6months') {
            $mInt = ($month === 'all' || empty($month) || (int)$month < 1 || (int)$month > 12) ? (int)date('m') : (int)$month;
            $statementData = $this->statementModel->getSixMonthStatement($year, $mInt, $projectId, $category);
            $filename = "6months_statement_{$year}_{$mInt}.csv";
        } elseif ($periodType === 'yearly' || $month === 'all' || $month === '0' || $month === 0) {
            $statementData = $this->statementModel->getYearlyStatement($year, $projectId, $category);
            $filename = "yearly_statement_{$year}.csv";
        } else {
            $mInt = (int)$month;
            $statementData = $this->statementModel->getMonthlyStatement($year, $mInt, $projectId, $category);
            $filename = "monthly_statement_{$year}_" . sprintf('%02d', $mInt) . ".csv";
        }

        if (!$statementData) {
            setError('Unable to generate statement');
            header("Location: {$GLOBALS['base_url']}/central-statement");
            exit;
        }

        // Set CSV headers
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // Output UTF-8 BOM so Excel opens properly without character encoding issues
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Write header
        fputcsv($output, ['CENTRAL FINANCIAL STATEMENT']);
        fputcsv($output, ['Period', $statementData['period'] ?? '']);
        fputcsv($output, ['Start Date', $statementData['start_date'] ?? '']);
        fputcsv($output, ['End Date', $statementData['end_date'] ?? '']);
        if ($projectName) {
            fputcsv($output, ['Project', $projectName]);
        }
        if ($categoryName) {
            fputcsv($output, ['Category', $categoryName]);
        }
        fputcsv($output, ['Generated', date('d M, Y h:i A')]);
        fputcsv($output, []); // Empty row

        // Bank transactions export
        if (in_array($category, ['bank_cashin', 'bank_cashout'])) {
            $bankTransactions = $this->getBankTransactions($category, $statementData['start_date'] ?? null, $statementData['end_date'] ?? null, $projectId);
            fputcsv($output, [
                '#',
                'Date',
                'Bank Name',
                'Branch',
                'Routing No',
                'Account No',
                $category === 'bank_cashin' ? 'Client / Payment Type' : 'Purpose / Paid To',
                $category === 'bank_cashin' ? 'Cash In (TK.)' : 'Withdrawal (TK.)'
            ]);
            $bankTotal = 0;
            foreach ($bankTransactions as $idx => $bt) {
                $amt = (float)($bt['amount'] ?? 0);
                $bankTotal += $amt;
                fputcsv($output, [
                    $idx + 1,
                    $bt['payment_date'] ?? '',
                    $bt['bank_name'] ?? '-',
                    $bt['branch_name'] ?? '-',
                    $bt['routing_number'] ?? '-',
                    $bt['account_number'] ?? '-',
                    $bt['description'] ?? '-',
                    number_format($amt, 2, '.', '')
                ]);
            }
            fputcsv($output, []);
            fputcsv($output, ['Grand Total', '', '', '', '', '', '', number_format($bankTotal, 2, '.', '')]);
        }
        // Daily breakdown
        elseif (isset($statementData['daily_data']) && is_array($statementData['daily_data'])) {
            if ($category === 'all') {
                fputcsv($output, [
                    'Date',
                    'Day',
                    'Booking Money (TK.)',
                    'Service Fee (TK.)',
                    'EMI Payments (TK.)',
                    'Landshare Payments (TK.)',
                    'Registration (TK.)',
                    'Management Cashin (TK.)',
                    'Total Cash IN (TK.)',
                    'Cash Out (TK.)',
                    'Net Amount (TK.)',
                    'Running Balance (TK.)'
                ]);
                foreach ($statementData['daily_data'] as $row) {
                    fputcsv($output, [
                        $row['formatted_date'] ?? $row['date'] ?? '',
                        $row['day_name'] ?? '',
                        number_format((float)($row['booking_money'] ?? 0), 2, '.', ''),
                        number_format((float)($row['service_fee'] ?? 0), 2, '.', ''),
                        number_format((float)($row['emi_payments'] ?? 0), 2, '.', ''),
                        number_format((float)($row['landshare_payments'] ?? 0), 2, '.', ''),
                        number_format((float)($row['registration'] ?? $row['registration_payments'] ?? 0), 2, '.', ''),
                        number_format((float)($row['management_cashin'] ?? 0), 2, '.', ''),
                        number_format((float)($row['total_cashin'] ?? 0), 2, '.', ''),
                        number_format((float)($row['cash_out'] ?? 0), 2, '.', ''),
                        number_format((float)($row['net_amount'] ?? 0), 2, '.', ''),
                        number_format((float)($row['running_balance'] ?? 0), 2, '.', '')
                    ]);
                }
            } else {
                $catCol = ucfirst(str_replace('_', ' ', $category)) . ' (TK.)';
                fputcsv($output, ['Date', 'Day', $catCol, 'Amount (TK.)']);
                foreach ($statementData['daily_data'] as $row) {
                    $val = 0;
                    if ($category === 'total_cashin') $val = (float)($row['total_cashin'] ?? 0);
                    elseif ($category === 'emi') $val = (float)($row['emi_payments'] ?? 0);
                    elseif ($category === 'landshare') $val = (float)($row['landshare_payments'] ?? 0);
                    elseif ($category === 'service_fee') $val = (float)($row['service_fee'] ?? 0);
                    elseif ($category === 'registration') $val = (float)($row['registration'] ?? $row['registration_payments'] ?? 0);
                    elseif ($category === 'booking_money') $val = (float)($row['booking_money'] ?? 0);
                    elseif ($category === 'management_cashin') $val = (float)($row['management_cashin'] ?? 0);
                    elseif (in_array($category, ['cashout', 'utilities', 'other', 'vendor_payment', 'contractor_bill'])) $val = (float)($row['cash_out'] ?? 0);
                    fputcsv($output, [
                        $row['formatted_date'] ?? $row['date'] ?? '',
                        $row['day_name'] ?? '',
                        number_format($val, 2, '.', ''),
                        number_format($val, 2, '.', '')
                    ]);
                }
            }
        }
        // Monthly breakdown
        elseif (isset($statementData['monthly_data']) && is_array($statementData['monthly_data'])) {
            if ($category === 'all') {
                fputcsv($output, [
                    'Month',
                    'Booking Money (TK.)',
                    'Service Fee (TK.)',
                    'EMI Payments (TK.)',
                    'Landshare Payments (TK.)',
                    'Registration (TK.)',
                    'Management Cashin (TK.)',
                    'Total Cash IN (TK.)',
                    'Cash Out (TK.)',
                    'Net Amount (TK.)',
                    'Running Balance (TK.)'
                ]);
                foreach ($statementData['monthly_data'] as $row) {
                    fputcsv($output, [
                        $row['month'] ?? '',
                        number_format((float)($row['booking_money'] ?? 0), 2, '.', ''),
                        number_format((float)($row['service_fee'] ?? 0), 2, '.', ''),
                        number_format((float)($row['emi_payments'] ?? 0), 2, '.', ''),
                        number_format((float)($row['landshare_payments'] ?? 0), 2, '.', ''),
                        number_format((float)($row['registration_payments'] ?? $row['registration'] ?? 0), 2, '.', ''),
                        number_format((float)($row['management_cashin'] ?? 0), 2, '.', ''),
                        number_format((float)($row['total_cashin'] ?? 0), 2, '.', ''),
                        number_format((float)($row['cash_out'] ?? 0), 2, '.', ''),
                        number_format((float)($row['net_amount'] ?? 0), 2, '.', ''),
                        number_format((float)($row['running_balance'] ?? 0), 2, '.', '')
                    ]);
                }
            } else {
                $catCol = ucfirst(str_replace('_', ' ', $category)) . ' (TK.)';
                fputcsv($output, ['Month', $catCol, 'Amount (TK.)']);
                foreach ($statementData['monthly_data'] as $row) {
                    $val = 0;
                    if ($category === 'total_cashin') $val = (float)($row['total_cashin'] ?? 0);
                    elseif ($category === 'emi') $val = (float)($row['emi_payments'] ?? 0);
                    elseif ($category === 'landshare') $val = (float)($row['landshare_payments'] ?? 0);
                    elseif ($category === 'service_fee') $val = (float)($row['service_fee'] ?? 0);
                    elseif ($category === 'registration') $val = (float)($row['registration_payments'] ?? $row['registration'] ?? 0);
                    elseif ($category === 'booking_money') $val = (float)($row['booking_money'] ?? 0);
                    elseif ($category === 'management_cashin') $val = (float)($row['management_cashin'] ?? 0);
                    elseif (in_array($category, ['cashout', 'utilities', 'other', 'vendor_payment', 'contractor_bill'])) $val = (float)($row['cash_out'] ?? 0);
                    fputcsv($output, [
                        $row['month'] ?? '',
                        number_format($val, 2, '.', ''),
                        number_format($val, 2, '.', '')
                    ]);
                }
            }
        }

        // Compute summary totals matching the web view
        $summary_stats = $statementData['summary'] ?? [];

        $calc_booking_money = (float)($summary_stats['total_booking_money'] ?? 0);
        $calc_registration_fee = (float)($summary_stats['total_registration_payments'] ?? $summary_stats['total_registration'] ?? 0);
        $calc_landshare_money = (float)($summary_stats['total_landshare_payments'] ?? $summary_stats['total_landshare'] ?? 0);
        $calc_cashin = (float)($summary_stats['total_cashin'] ?? 0);
        $calc_cashout = (float)($summary_stats['total_cash_out'] ?? 0);

        if (isset($statementData['daily_data']) && is_array($statementData['daily_data'])) {
            $r_booking = 0; $r_reg = 0; $r_land = 0; $r_cashin = 0; $r_cashout = 0;
            foreach ($statementData['daily_data'] as $r) {
                $r_booking += (float)($r['booking_money'] ?? 0);
                $r_reg += (float)($r['registration'] ?? $r['registration_payments'] ?? 0);
                $r_land += (float)($r['landshare_payments'] ?? 0);
                $r_cashin += (float)($r['total_cashin'] ?? 0);
                $r_cashout += (float)($r['cash_out'] ?? 0);
            }
            if ($calc_booking_money == 0) $calc_booking_money = $r_booking;
            if ($calc_registration_fee == 0) $calc_registration_fee = $r_reg;
            if ($calc_landshare_money == 0) $calc_landshare_money = $r_land;
            if ($calc_cashin == 0) $calc_cashin = $r_cashin;
            if ($calc_cashout == 0) $calc_cashout = $r_cashout;
        } elseif (isset($statementData['monthly_data']) && is_array($statementData['monthly_data'])) {
            $r_booking = 0; $r_reg = 0; $r_land = 0; $r_cashin = 0; $r_cashout = 0;
            foreach ($statementData['monthly_data'] as $r) {
                $r_booking += (float)($r['booking_money'] ?? 0);
                $r_reg += (float)($r['registration_payments'] ?? $r['registration'] ?? 0);
                $r_land += (float)($r['landshare_payments'] ?? 0);
                $r_cashin += (float)($r['total_cashin'] ?? 0);
                $r_cashout += (float)($r['cash_out'] ?? 0);
            }
            if ($calc_booking_money == 0) $calc_booking_money = $r_booking;
            if ($calc_registration_fee == 0) $calc_registration_fee = $r_reg;
            if ($calc_landshare_money == 0) $calc_landshare_money = $r_land;
            if ($calc_cashin == 0) $calc_cashin = $r_cashin;
            if ($calc_cashout == 0) $calc_cashout = $r_cashout;
        }

        if ($calc_landshare_money == 0 && ($calc_booking_money > 0 || $calc_registration_fee > 0)) {
            $calc_landshare_money = $calc_booking_money + $calc_registration_fee;
        }

        $calc_net_balance = $calc_cashin - $calc_cashout;

        // Write summary
        fputcsv($output, []); // Empty row
        fputcsv($output, ['PERIOD SUMMARY']);
        fputcsv($output, ['Total Cash IN', number_format($calc_cashin, 2, '.', '')]);
        fputcsv($output, ['Total Land Share Money', number_format($calc_landshare_money, 2, '.', '')]);
        fputcsv($output, ['Total Booking Money', number_format($calc_booking_money, 2, '.', '')]);
        fputcsv($output, ['Total Registration Fee', number_format($calc_registration_fee, 2, '.', '')]);
        fputcsv($output, ['Total Cash Out', number_format($calc_cashout, 2, '.', '')]);
        fputcsv($output, ['Net Balance', number_format($calc_net_balance, 2, '.', '')]);

        fclose($output);
        exit;
    }

    /**
     * AJAX: Get quick stats
     */
    public function getQuickStats()
    {
        header('Content-Type: application/json');

        $stats = $this->statementModel->getOverallStatistics();

        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);
        exit;
    }

    public function getRevenueStatistics()
    {
        header('Content-Type: application/json');

        $periodType = $_GET['period_type'] ?? 'monthly';
        $year = isset($_GET['year']) ? (int)$_GET['year'] : null;
        $month = isset($_GET['month']) ? (int)$_GET['month'] : null;
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $projectId = $_GET['project_id'] ?? null;

        $revenueStats = $this->statementModel->getRevenueStatistics(
            $periodType,
            $year,
            $month,
            $startDate,
            $endDate,
            $projectId
        );

        echo json_encode([
            'success' => true,
            'data' => $revenueStats
        ]);
        exit;
    }
}

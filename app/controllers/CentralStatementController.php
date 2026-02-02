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
 /**
 * Main index - Show central statement page
 */
public function index()
{
    // Check if this is a print request
    $isPrint = isset($_GET['print']) && $_GET['print'] == 'true';

    // If it's a print request, use the print method
    if ($isPrint) {
        $this->printStatement();
        return;
    }

    // Get filters from query params with proper defaults
    $periodType = $_GET['period_type'] ?? 'monthly';
    $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
    $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    $projectId = $_GET['project_id'] ?? null;
    $category = $_GET['category'] ?? 'all';

    // Card period filter
    $cardPeriod = $_GET['card_period'] ?? 'all_time';
    $cardYear = isset($_GET['card_year']) ? (int)$_GET['card_year'] : (int)date('Y');
    $cardMonth = isset($_GET['card_month']) ? (int)$_GET['card_month'] : (int)date('m');
    $cardStartDate = $_GET['card_start_date'] ?? null;
    $cardEndDate = $_GET['card_end_date'] ?? null;

    // Debug log
    error_log("Central Statement Index - Period Type: $periodType, Year: $year, Month: $month");

    // Get statement data based on period type
    $statementData = null;
    $periodText = '';

    switch ($periodType) {
        case 'monthly':
            $statementData = $this->statementModel->getMonthlyStatement($year, $month, $projectId, $category);
            $periodText = date('F Y', strtotime("$year-" . sprintf('%02d', $month) . "-01"));
            break;

        case '6months':
            $statementData = $this->statementModel->getSixMonthStatement($year, $month, $projectId, $category);
            $periodText = 'Last 6 Months';
            break;

        case 'yearly':
            $statementData = $this->statementModel->getYearlyStatement($year, $projectId, $category);
            $periodText = "Year $year";
            break;

        case 'custom':
            if ($startDate && $endDate) {
                $statementData = $this->statementModel->getCustomRangeStatement($startDate, $endDate, $projectId, $category);
                $periodText = date('d M, Y', strtotime($startDate)) . ' to ' . date('d M, Y', strtotime($endDate));
            } else {
                // Default to current month if dates not provided
                $year = (int)date('Y');
                $month = (int)date('m');
                $statementData = $this->statementModel->getMonthlyStatement($year, $month, $projectId, $category);
                $periodText = date('F Y');
                $periodType = 'monthly';
            }
            break;

        default:
            $year = (int)date('Y');
            $month = (int)date('m');
            $statementData = $this->statementModel->getMonthlyStatement($year, $month, $projectId, $category);
            $periodText = date('F Y');
            $periodType = 'monthly';
    }

    // Get projects for dropdown
    $projects = $this->getProjects();

    // Get overall statistics with card period filter
    // IMPORTANT: For summary cards, always show ALL cashout regardless of selected category
    $overallStats = $this->statementModel->getOverallStatistics(
        $cardPeriod,
        $cardYear,
        $cardMonth,
        $cardStartDate,
        $cardEndDate,
        $projectId,
        'all'  // Always use 'all' for summary cards to show total cashout
    );

    // Prepare view data
    $viewData = [
        'current_route' => 'central-statement',
        'page_title' => 'Central Financial Statement',
        'period_type' => $periodType,
        'period_text' => $periodText,
        'year' => $year,
        'month' => sprintf('%02d', $month),
        'start_date' => $startDate,
        'end_date' => $endDate,
        'project_id' => $projectId,
        'category' => $category,
        'projects' => $projects,
        'statement_data' => $statementData,
        'overall_stats' => $overallStats,
        'card_period' => $cardPeriod,
        'card_year' => $cardYear,
        'card_month' => sprintf('%02d', $cardMonth),
        'card_start_date' => $cardStartDate,
        'card_end_date' => $cardEndDate
    ];

    $this->view('accounts/central-statement', $viewData);
}

    /**
     * Get projects list
     */
    private function getProjects()
    {
        $query = "SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name";
        $result = $this->db->query($query);
        $projects = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $projects[] = $row;
            }
        }
        return $projects;
    }

    /**
     * Print statement - separate method for print view
     */
    /**
     * Print statement - separate method for print view
     */
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

        switch ($periodType) {
            case 'monthly':
                $statementData = $this->statementModel->getMonthlyStatement($year, $month, $projectId, $category);
                $periodText = date('F Y', strtotime("$year-$month-01"));
                break;

            case '6months':
                $statementData = $this->statementModel->getSixMonthStatement($year, $month, $projectId, $category);
                $periodText = 'Last 6 Months';
                break;

            case 'yearly':
                $statementData = $this->statementModel->getYearlyStatement($year, $projectId, $category);
                $periodText = "Year $year";
                break;

            case 'custom':
                if ($startDate && $endDate) {
                    $statementData = $this->statementModel->getCustomRangeStatement($startDate, $endDate, $projectId, $category);
                    $periodText = date('d M, Y', strtotime($startDate)) . ' to ' . date('d M, Y', strtotime($endDate));
                } else {
                    // Default to current month if dates not provided
                    $statementData = $this->statementModel->getMonthlyStatement(date('Y'), date('m'), $projectId, $category);
                    $periodText = date('F Y');
                    $periodType = 'monthly';
                }
                break;

            default:
                $statementData = $this->statementModel->getMonthlyStatement(date('Y'), date('m'), $projectId, $category);
                $periodText = date('F Y');
                $periodType = 'monthly';
        }

        // Get overall statistics for print (all time) WITH PROJECT AND CATEGORY FILTERS
        $overallStats = $this->statementModel->getOverallStatistics('all_time', null, null, null, null, $projectId, $category);

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
        // Get category name
        $categoryName = '';
        if ($category !== 'all') {
            $categoryName = ucfirst(str_replace('_', ' ', $category));
            // Special handling for cashout and utilities
            if ($category === 'cashout') {
                $categoryName = 'Cash Out';
            } elseif ($category === 'utilities') {
                $categoryName = 'Utilities (Bill/Electricity/Wifi/etc)';
            }
        }

        // Render the print view directly
?>
        <!DOCTYPE html>
        <html lang="en">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Central Financial Statement</title>

            <style>
                @page {
                    size: A4;
                    margin: 15mm;
                }

                @media print {
                    body {
                        margin: 0 !important;
                        padding: 0 !important;
                        font-size: 10pt;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }

                    .no-print {
                        display: none !important;
                    }

                    .page-break {
                        page-break-before: always;
                        margin-top: 20mm;
                    }

                    .avoid-break {
                        page-break-inside: avoid;
                    }
                }

                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                body {
                    font-family: 'Arial', sans-serif;
                    background: #fff;
                    color: #000;
                    line-height: 1.3;
                    font-size: 10pt;
                    width: 210mm;
                    min-height: 297mm;
                    margin: 0 auto;
                    padding: 15mm;
                }

                .statement-header {
                    text-align: center;
                    margin-bottom: 12mm;
                    border-bottom: 1mm solid #000;
                }

                .company-name {
                    font-size: 18pt;
                    font-weight: bold;
                    text-transform: uppercase;
                    margin-bottom: 2mm;
                    letter-spacing: 1pt;
                }

                .company-address {
                    font-size: 9pt;
                    line-height: 1.2;
                    margin-bottom: 3mm;
                }

                .statement-title {
                    font-size: 16pt;
                    font-weight: bold;
                    text-transform: uppercase;
                    margin: 6mm 0;
                    letter-spacing: 1pt;
                }

                .statement-meta {
                    font-size: 9pt;
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 8mm;
                }

                .meta-item {
                    text-align: center;
                }

                .meta-label {
                    font-weight: bold;
                    display: block;
                    margin-bottom: 1mm;
                }

                .stats-container {
                    display: flex;
                    margin-bottom: 12mm;
                    gap: 8mm;
                }

                .stat-box {
                    flex: 1;
                    padding: 4mm;
                    border: 0.5mm solid #000;
                    border-radius: 2mm;
                    text-align: center;
                }

                .stat-label {
                    font-size: 8pt;
                    font-weight: bold;
                    text-transform: uppercase;
                    margin-bottom: 2mm;
                    color: #666;
                }

                .stat-value {
                    font-size: 14pt;
                    font-weight: bold;
                    font-family: 'Courier New', monospace;
                }

                .stat-breakdown {
                    font-size: 7pt;
                    margin-top: 2mm;
                    color: #666;
                }

                .period-title {
                    font-size: 12pt;
                    font-weight: bold;
                    text-transform: uppercase;
                    text-align: center;
                    margin: 8mm 0 4mm 0;
                    padding-bottom: 2mm;
                    border-bottom: 0.3mm solid #000;
                }

                .statement-table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 8pt;
                    margin-bottom: 8mm;
                }

                .statement-table th {
                    border: 0.3mm solid #000;
                    padding: 2mm 3mm;
                    text-align: left;
                    font-weight: bold;
                    text-transform: uppercase;
                    background: #f0f0f0;
                    vertical-align: middle;
                }

                .statement-table td {
                    border: 0.3mm solid #000;
                    padding: 2mm 3mm;
                    vertical-align: middle;
                }

                .text-right {
                    text-align: right;
                }

                .text-center {
                    text-align: center;
                }

                .summary-section {
                    margin-top: 10mm;
                    padding-top: 4mm;
                    border-top: 0.5mm solid #000;
                }

                .summary-title {
                    font-size: 11pt;
                    font-weight: bold;
                    text-transform: uppercase;
                    margin-bottom: 4mm;
                }

                .summary-grid {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 6mm;
                    border: 1px solid #000;
                }

                .summary-box {

                    padding: 3mm;
                }

                .summary-row {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 2mm;
                }

                .summary-label {
                    font-weight: bold;
                }

                .summary-value {
                    font-weight: bold;
                    font-family: 'Courier New', monospace;
                }

                .footer {
                    margin-top: 15mm;
                    padding-top: 3mm;
                    border-top: 0.3mm solid #000;
                    font-size: 7pt;
                    text-align: center;
                    color: #666;
                }

                .footer-note {
                    font-style: italic;
                    margin-bottom: 2mm;
                }

                .print-date {
                    font-weight: bold;
                }

                /* Colors for cash in/out */
                .revenue {
                    color: #28a745;
                }

                .cash-out {
                    color: #dc3545;
                }

                .net-balance {
                    color: #007bff;
                }

                .filter-info {
                    font-size: 9pt;
                    margin-bottom: 4mm;
                    text-align: center;
                    font-style: italic;
                }
            </style>
        </head>

        <body>
            <!-- Header -->
            <div class="statement-header">
                <div class="company-name"><?= $company_name ?></div>
                <div class="company-address">
                    <?= $company_address ?><br>
                    Phone: <?= $company_phone ?> | Email: <?= $company_email ?>
                </div>
                <div class="statement-title">CENTRAL FINANCIAL STATEMENT</div>
            </div>

            <!-- Meta Information -->
            <div class="statement-meta">
                <div class="meta-item">
                    <span class="meta-label">Date of Issue:</span>
                    <span><?= date('d-M-Y') ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Period:</span>
                    <span><?= htmlspecialchars($periodText) ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Generated:</span>
                    <span><?= date('d M, Y h:i A') ?></span>
                </div>
            </div>

            <!-- Filter Information -->
            <?php if ($projectName || $categoryName): ?>
                <div class="filter-info">
                    <?php if ($projectName): ?>
                        <strong>Project:</strong> <?= htmlspecialchars($projectName) ?>
                    <?php endif; ?>
                    <?php if ($categoryName): ?>
                        <?php if ($projectName): ?> | <?php endif; ?>
                        <strong>Category:</strong> <?= htmlspecialchars($categoryName) ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if ($category === 'utilities'): ?>
                <?php
                // Get utilities transactions for print view
                $utilitiesTransactions = $this->statementModel->getUtilitiesTransactions($startDate, $endDate);
                if (!empty($utilitiesTransactions)): ?>
                    <div style="margin: 8mm 0; padding: 4mm; border: 0.5mm solid #17a2b8; border-radius: 2mm; background: #f8f9fa;">
                        <h4 style="color: #17a2b8; margin-bottom: 4mm; font-size: 12pt;">Utilities Transactions Details</h4>
                        <p style="font-size: 9pt; color: #666; margin-bottom: 4mm; font-style: italic;">
                            Showing utility payments detected from cashout requests (Bill, Electricity, Wifi, etc.)
                        </p>
                        <table style="width: 100%; border-collapse: collapse; font-size: 8pt;">
                            <thead>
                                <tr style="background: #e9ecef;">
                                    <th style="border: 0.3mm solid #999; padding: 2mm;">Date</th>
                                    <th style="border: 0.3mm solid #999; padding: 2mm;">Purpose/Paid To</th>
                                    <th style="border: 0.3mm solid #999; padding: 2mm;">Amount (৳)</th>
                                    <th style="border: 0.3mm solid #999; padding: 2mm;">Requester</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($utilitiesTransactions as $transaction): ?>
                                    <tr>
                                        <td style="border: 0.3mm solid #999; padding: 2mm;"><?= date('d M, Y', strtotime($transaction['payment_date'])) ?></td>
                                        <td style="border: 0.3mm solid #999; padding: 2mm;"><?= htmlspecialchars($transaction['purpose_paid_to']) ?></td>
                                        <td style="border: 0.3mm solid #999; padding: 2mm; text-align: right; color: #dc3545;"><?= number_format($transaction['amount'], 2) ?></td>
                                        <td style="border: 0.3mm solid #999; padding: 2mm;"><?= htmlspecialchars($transaction['requester_name']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <!-- Statistics -->
            <div class="stats-container">
                <div class="stat-box">
                    <div class="stat-label">Total Cash IN</div>
                    <div class="stat-value revenue">৳<?= number_format($overallStats['total_revenue'] ?? 0, 2) ?></div>

                </div>
                <div class="stat-box">
                    <div class="stat-label">Total Cash Out</div>
                    <div class="stat-value cash-out">৳<?= number_format($overallStats['total_cash_out'] ?? 0, 2) ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Remmaining Balance</div>
                    <div class="stat-value net-balance">৳<?= number_format($overallStats['net_balance'] ?? 0, 2) ?></div>
                </div>
            </div>

            <!-- Statement Data -->
            <div class="period-title">
                <?= isset($statementData['daily_data']) ? 'DAILY STATEMENT - ' : 'MONTHLY STATEMENT - ' ?>
                <?= htmlspecialchars($periodText) ?>
            </div>

            <?php if (isset($statementData['daily_data'])): ?>
                <table class="statement-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Day</th>

                            <?php if ($category === 'all' || $category === 'landshare'): ?>
                                <th class="text-right">Landshare (৳)</th>
                            <?php endif; ?>

                            <?php if ($category === 'all' || $category === 'service_fee'): ?>
                                <th class="text-right">Service Fee (৳)</th>
                            <?php endif; ?>

                            <?php if ($category === 'all'): ?>
                                <th class="text-right">Total Revenue (৳)</th>
                            <?php endif; ?>

                            <?php if (in_array($category, ['all', 'cashout', 'utilities', 'other'])): ?>
                                <th class="text-right">Cash Out (৳)</th>
                            <?php endif; ?>

                            <?php if ($category === 'all'): ?>
                                <th class="text-right">Net Amount (৳)</th>
                                <th class="text-right">Running Balance (৳)</th>
                            <?php else: ?>
                                <th class="text-right">Amount (৳)</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($statementData['daily_data'] as $row): ?>
                            <tr class="avoid-break">
                                <td><?= htmlspecialchars($row['formatted_date']) ?></td>
                                <td><?= htmlspecialchars($row['day_name']) ?></td>

                                <?php if ($category === 'all' || $category === 'landshare'): ?>
                                    <td class="text-right revenue"><?= number_format($row['landshare'], 2) ?></td>
                                <?php endif; ?>

                                <?php if ($category === 'all' || $category === 'service_fee'): ?>
                                    <td class="text-right revenue"><?= number_format($row['service_fee'], 2) ?></td>
                                <?php endif; ?>

                                <?php if ($category === 'all'): ?>
                                    <td class="text-right revenue"><?= number_format($row['total_revenue'], 2) ?></td>
                                <?php endif; ?>

                                <?php if (in_array($category, ['all', 'cashout', 'utilities', 'other'])): ?>
                                    <td class="text-right cash-out"><?= number_format($row['cash_out'], 2) ?></td>
                                <?php endif; ?>

                                <?php if ($category === 'all'): ?>
                                    <td class="text-right"><?= number_format($row['net_amount'], 2) ?></td>
                                    <td class="text-right"><?= number_format($row['running_balance'], 2) ?></td>
                                <?php else: ?>
                                    <?php
                                    $amount = 0;
                                    if ($category === 'landshare') {
                                        $amount = $row['landshare'];
                                    } elseif ($category === 'service_fee') {
                                        $amount = $row['service_fee'];
                                    } else {
                                        $amount = $row['cash_out'];
                                    }
                                    $colorClass = in_array($category, ['landshare', 'service_fee']) ? 'revenue' : 'cash-out';
                                    ?>
                                    <td class="text-right <?= $colorClass ?>"><?= number_format($amount, 2) ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif (isset($statementData['monthly_data'])): ?>
                <table class="statement-table">
                    <thead>
                        <tr>
                            <th>Month</th>

                            <?php if ($category === 'all' || $category === 'landshare'): ?>
                                <th class="text-right">Landshare (৳)</th>
                            <?php endif; ?>

                            <?php if ($category === 'all' || $category === 'service_fee'): ?>
                                <th class="text-right">Service Fee (৳)</th>
                            <?php endif; ?>

                            <?php if ($category === 'all'): ?>
                                <th class="text-right">Total Revenue (৳)</th>
                            <?php endif; ?>

                            <?php if (in_array($category, ['all', 'cashout', 'utilities', 'other'])): ?>
                                <th class="text-right">Cash Out (৳)</th>
                            <?php endif; ?>

                            <?php if ($category === 'all'): ?>
                                <th class="text-right">Net Amount (৳)</th>
                                <th class="text-right">Running Balance (৳)</th>
                            <?php else: ?>
                                <th class="text-right">Amount (৳)</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($statementData['monthly_data'] as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['month']) ?></td>

                                <?php if ($category === 'all' || $category === 'landshare'): ?>
                                    <td class="text-right revenue"><?= number_format($row['landshare'], 2) ?></td>
                                <?php endif; ?>

                                <?php if ($category === 'all' || $category === 'service_fee'): ?>
                                    <td class="text-right revenue"><?= number_format($row['service_fee'], 2) ?></td>
                                <?php endif; ?>

                                <?php if ($category === 'all'): ?>
                                    <td class="text-right revenue"><?= number_format($row['total_revenue'], 2) ?></td>
                                <?php endif; ?>

                                <?php if (in_array($category, ['all', 'cashout', 'utilities', 'other'])): ?>
                                    <td class="text-right cash-out"><?= number_format($row['cash_out'], 2) ?></td>
                                <?php endif; ?>

                                <?php if ($category === 'all'): ?>
                                    <td class="text-right"><?= number_format($row['net_amount'], 2) ?></td>
                                    <td class="text-right"><?= number_format($row['running_balance'], 2) ?></td>
                                <?php else: ?>
                                    <?php
                                    $amount = 0;
                                    if ($category === 'landshare') {
                                        $amount = $row['landshare'];
                                    } elseif ($category === 'service_fee') {
                                        $amount = $row['service_fee'];
                                    } else {
                                        $amount = $row['cash_out'];
                                    }
                                    $colorClass = in_array($category, ['landshare', 'service_fee']) ? 'revenue' : 'cash-out';
                                    ?>
                                    <td class="text-right <?= $colorClass ?>"><?= number_format($amount, 2) ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="text-center" style="padding: 20mm; font-style: italic; color: #666;">
                    No statement data available for the selected period.
                </div>
            <?php endif; ?>

            <!-- Summary -->
            <?php if (isset($statementData['summary'])): ?>
                <div class="summary-section">
                    <div class="summary-title">PERIOD SUMMARY</div>
                    <div class="summary-grid">
                        <div class="summary-box">
                           
                            <?php if ($category === 'all' || $category === 'landshare'): ?>
                                <div class="summary-row">
                                    <span class="summary-label">Landshare:</span>
                                    <span class="summary-value revenue">৳<?= number_format($statementData['summary']['total_agreement'] ?? $statementData['summary']['total_landshare'] ?? 0, 2) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($category === 'all' || $category === 'service_fee'): ?>
                                <div class="summary-row">
                                    <span class="summary-label">Service Fee:</span>
                                    <span class="summary-value revenue">৳<?= number_format($statementData['summary']['total_service_fee'], 2) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if ($category === 'all'): ?>
                                <div class="summary-row">
                                    <span class="summary-label">Total Revenue:</span>
                                    <span class="summary-value revenue">৳<?= number_format($statementData['summary']['total_revenue'], 2) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="summary-box">
                            <?php if (in_array($category, ['all', 'cashout', 'utilities', 'other'])): ?>
                                <div class="summary-row">
                                    <span class="summary-label">Total Cash Out:</span>
                                    <span class="summary-value cash-out">৳<?= number_format($statementData['summary']['total_cash_out'], 2) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if ($category === 'all'): ?>

                                <div class="summary-row">
                                    <span class="summary-label">Available Balance:</span>
                                    <span class="summary-value">৳<?= number_format($statementData['summary']['final_balance'], 2) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Footer -->
            <div class="footer">
                <div class="footer-note">
                    This is a computer-generated central financial statement and does not require a signature.
                </div>
                <div class="print-date">
                    Printed on: <?= date('d M, Y h:i A') ?>
                </div>
            </div>

            <script>
                // Auto print when loaded
                window.onload = function() {
                    setTimeout(function() {
                        window.print();
                    }, 500);
                };
            </script>
        </body>

        </html>
<?php
        exit;
    }


    /**
     * Export statement to CSV
     */
    public function exportCSV()
    {
        $periodType = $_GET['period_type'] ?? 'monthly';
        $year = $_GET['year'] ?? date('Y');
        $month = $_GET['month'] ?? date('m');
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $projectId = $_GET['project_id'] ?? null;
        $category = $_GET['category'] ?? 'all';

        // Get statement data
        $statementData = null;
        $filename = 'central_statement_' . date('Ymd_His');

        switch ($periodType) {
            case 'monthly':
                $statementData = $this->statementModel->getMonthlyStatement($year, $month, $projectId, $category);
                $filename = "monthly_statement_{$year}_{$month}.csv";
                break;
            case '6months':
                $statementData = $this->statementModel->getSixMonthStatement($year, $month, $projectId, $category);
                $filename = "6months_statement_{$year}_{$month}.csv";
                break;
            case 'yearly':
                $statementData = $this->statementModel->getYearlyStatement($year, $projectId, $category);
                $filename = "yearly_statement_{$year}.csv";
                break;
            case 'custom':
                if ($startDate && $endDate) {
                    $statementData = $this->statementModel->getCustomRangeStatement($startDate, $endDate, $projectId, $category);
                    $cleanStartDate = str_replace('-', '', $startDate);
                    $cleanEndDate = str_replace('-', '', $endDate);
                    $filename = "custom_statement_{$cleanStartDate}_to_{$cleanEndDate}.csv";
                } else {
                    // Default to current month if dates not provided
                    $statementData = $this->statementModel->getMonthlyStatement(date('Y'), date('m'), $projectId, $category);
                    $filename = "monthly_statement_" . date('Y') . "_" . date('m') . ".csv";
                    $periodType = 'monthly';
                }
                break;
            default:
                $statementData = $this->statementModel->getMonthlyStatement(date('Y'), date('m'), $projectId, $category);
                $filename = "monthly_statement_" . date('Y') . "_" . date('m') . ".csv";
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

        // Write header
        fputcsv($output, ['Central Financial Statement']);
        fputcsv($output, ['Period: ' . $statementData['period']]);
        fputcsv($output, ['Start Date: ' . $statementData['start_date']]);
        fputcsv($output, ['End Date: ' . $statementData['end_date']]);
        fputcsv($output, ['Generated: ' . date('d M, Y h:i A')]);
        fputcsv($output, []); // Empty row

        // Write data based on type
        if (isset($statementData['daily_data'])) {
            // Daily breakdown
            fputcsv($output, ['Date', 'Day', 'Landshare (৳)', 'Service Fee (৳)', 'Total Revenue (৳)', 'Cash Out (৳)', 'Net Amount (৳)', 'Running Balance (৳)']);
            foreach ($statementData['daily_data'] as $row) {
                fputcsv($output, [
                    $row['formatted_date'],
                    $row['day_name'],
                    number_format($row['landshare'], 2),
                    number_format($row['service_fee'], 2),
                    number_format($row['total_revenue'], 2),
                    number_format($row['cash_out'], 2),
                    number_format($row['net_amount'], 2),
                    number_format($row['running_balance'], 2)
                ]);
            }
        } elseif (isset($statementData['monthly_data'])) {
            // Monthly breakdown
            fputcsv($output, ['Month', 'Landshare (৳)', 'Service Fee (৳)', 'Total Revenue (৳)', 'Cash Out (৳)', 'Net Amount (৳)', 'Running Balance (৳)']);
            foreach ($statementData['monthly_data'] as $row) {
                fputcsv($output, [
                    $row['month'],
                    number_format($row['landshare'], 2),
                    number_format($row['service_fee'], 2),
                    number_format($row['total_revenue'], 2),
                    number_format($row['cash_out'], 2),
                    number_format($row['net_amount'], 2),
                    number_format($row['running_balance'], 2)
                ]);
            }
        }

        // Write summary
        fputcsv($output, []); // Empty row
        fputcsv($output, ['SUMMARY']);
        fputcsv($output, ['Total Landshare', number_format($statementData['summary']['total_landshare'], 2)]);
        fputcsv($output, ['Total Service Fee', number_format($statementData['summary']['total_service_fee'], 2)]);
        fputcsv($output, ['Total Revenue', number_format($statementData['summary']['total_revenue'], 2)]);
        fputcsv($output, ['Total Cash Out', number_format($statementData['summary']['total_cash_out'], 2)]);
        fputcsv($output, ['Net Balance', number_format($statementData['summary']['net_balance'], 2)]);
        fputcsv($output, ['Final Balance', number_format($statementData['summary']['final_balance'], 2)]);

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
}

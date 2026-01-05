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
        // Check if this is a print request
        $isPrint = isset($_GET['print']) && $_GET['print'] == 'true';
        
        // If it's a print request, use the print method
        if ($isPrint) {
            $this->printStatement();
            return;
        }

        // Get period type from query params
        $periodType = $_GET['period_type'] ?? 'monthly';
        $year = $_GET['year'] ?? date('Y');
        $month = $_GET['month'] ?? date('m');
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;

        // Get statement data based on period type
        $statementData = null;
        $periodText = '';

        switch ($periodType) {
            case 'monthly':
                $statementData = $this->statementModel->getMonthlyStatement($year, $month);
                $periodText = date('F Y', strtotime("$year-$month-01"));
                break;

            case '6months':
                $statementData = $this->statementModel->getSixMonthStatement($year, $month);
                $periodText = 'Last 6 Months';
                break;

            case 'yearly':
                $statementData = $this->statementModel->getYearlyStatement($year);
                $periodText = "Year $year";
                break;

            case 'custom':
                if ($startDate && $endDate) {
                    $statementData = $this->statementModel->getCustomRangeStatement($startDate, $endDate);
                    $periodText = date('d M, Y', strtotime($startDate)) . ' to ' . date('d M, Y', strtotime($endDate));
                } else {
                    // Default to current month if dates not provided
                    $statementData = $this->statementModel->getMonthlyStatement(date('Y'), date('m'));
                    $periodText = date('F Y');
                    $periodType = 'monthly';
                }
                break;

            default:
                $statementData = $this->statementModel->getMonthlyStatement(date('Y'), date('m'));
                $periodText = date('F Y');
                $periodType = 'monthly';
        }

        // Get overall statistics
        $overallStats = $this->statementModel->getOverallStatistics();

        // Prepare view data
        $viewData = [
            'current_route' => 'central-statement',
            'page_title' => 'Central Financial Statement',
            'period_type' => $periodType,
            'period_text' => $periodText,
            'year' => $year,
            'month' => $month,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'statement_data' => $statementData,
            'overall_stats' => $overallStats
        ];

        $this->view('accounts/central-statement', $viewData);
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

        // Get statement data based on period type
        $statementData = null;
        $periodText = '';

        switch ($periodType) {
            case 'monthly':
                $statementData = $this->statementModel->getMonthlyStatement($year, $month);
                $periodText = date('F Y', strtotime("$year-$month-01"));
                break;

            case '6months':
                $statementData = $this->statementModel->getSixMonthStatement($year, $month);
                $periodText = 'Last 6 Months';
                break;

            case 'yearly':
                $statementData = $this->statementModel->getYearlyStatement($year);
                $periodText = "Year $year";
                break;

            case 'custom':
                if ($startDate && $endDate) {
                    $statementData = $this->statementModel->getCustomRangeStatement($startDate, $endDate);
                    $periodText = date('d M, Y', strtotime($startDate)) . ' to ' . date('d M, Y', strtotime($endDate));
                } else {
                    // Default to current month if dates not provided
                    $statementData = $this->statementModel->getMonthlyStatement(date('Y'), date('m'));
                    $periodText = date('F Y');
                    $periodType = 'monthly';
                }
                break;

            default:
                $statementData = $this->statementModel->getMonthlyStatement(date('Y'), date('m'));
                $periodText = date('F Y');
                $periodType = 'monthly';
        }

        // Get overall statistics
        $overallStats = $this->statementModel->getOverallStatistics();

        // Company information
        $company_name = "THE PREMIUM HOMES LTD.";
        $company_address = "Land View Commercial Center, 9th Floor 28 Gulshan North C/A, Gulshan Circle-2, Dhaka";
        $company_phone = "+8801958253300";
        $company_email = "info@thepremiumhomes.com";

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
                    padding-bottom: 6mm;
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
                    justify-content: space-between;
                    margin-bottom: 12mm;
                    gap: 10mm;
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
                }

                .summary-box {
                    border: 0.3mm solid #000;
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
                .cash-in {
                    color: #28a745;
                }

                .cash-out {
                    color: #dc3545;
                }

                .net-balance {
                    color: #007bff;
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

            <!-- Statistics -->
            <div class="stats-container">
                <div class="stat-box">
                    <div class="stat-label">Total Cash In (All Time)</div>
                    <div class="stat-value cash-in">৳<?= number_format($overallStats['total_cash_in'] ?? 0, 2) ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Total Cash Out (All Time)</div>
                    <div class="stat-value cash-out">৳<?= number_format($overallStats['total_cash_out'] ?? 0, 2) ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Overall Net Balance</div>
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
                            <th class="text-right">Cash In (৳)</th>
                            <th class="text-right">Cash Out (৳)</th>
                            <th class="text-right">Net Amount (৳)</th>
                            <th class="text-right">Running Balance (৳)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 0; ?>
                        <?php foreach ($statementData['daily_data'] as $row): ?>
                            <?php $counter++; ?>
                            <?php if ($counter > 20): ?>
                                </tbody>
                                </table>
                                <div class="page-break"></div>
                                <div class="period-title">
                                    DAILY STATEMENT - <?= htmlspecialchars($periodText) ?> (Continued)
                                </div>
                                <table class="statement-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Day</th>
                                        <th class="text-right">Cash In (৳)</th>
                                        <th class="text-right">Cash Out (৳)</th>
                                        <th class="text-right">Net Amount (৳)</th>
                                        <th class="text-right">Running Balance (৳)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php $counter = 1; ?>
                            <?php endif; ?>
                            <tr class="avoid-break">
                                <td><?= htmlspecialchars($row['formatted_date']) ?></td>
                                <td><?= htmlspecialchars($row['day_name']) ?></td>
                                <td class="text-right cash-in"><?= number_format($row['cash_in'], 2) ?></td>
                                <td class="text-right cash-out"><?= number_format($row['cash_out'], 2) ?></td>
                                <td class="text-right"><?= number_format($row['net_amount'], 2) ?></td>
                                <td class="text-right"><?= number_format($row['running_balance'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif (isset($statementData['monthly_data'])): ?>
                <table class="statement-table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th class="text-right">Cash In (৳)</th>
                            <th class="text-right">Cash Out (৳)</th>
                            <th class="text-right">Net Amount (৳)</th>
                            <th class="text-right">Running Balance (৳)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($statementData['monthly_data'] as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['month']) ?></td>
                                <td class="text-right cash-in"><?= number_format($row['cash_in'], 2) ?></td>
                                <td class="text-right cash-out"><?= number_format($row['cash_out'], 2) ?></td>
                                <td class="text-right"><?= number_format($row['net_amount'], 2) ?></td>
                                <td class="text-right"><?= number_format($row['running_balance'], 2) ?></td>
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
                        <div class="summary-row">
                            <span class="summary-label">Total Cash In:</span>
                            <span class="summary-value cash-in">৳<?= number_format($statementData['summary']['total_cash_in'], 2) ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Total Cash Out:</span>
                            <span class="summary-value cash-out">৳<?= number_format($statementData['summary']['total_cash_out'], 2) ?></span>
                        </div>
                    </div>
                    <div class="summary-box">
                        <div class="summary-row">
                            <span class="summary-label">Net Balance:</span>
                            <span class="summary-value">৳<?= number_format($statementData['summary']['net_balance'], 2) ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Final Balance:</span>
                            <span class="summary-value">৳<?= number_format($statementData['summary']['final_balance'], 2) ?></span>
                        </div>
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

        // Get statement data
        $statementData = null;
        $filename = 'central_statement_' . date('Ymd_His');

        switch ($periodType) {
            case 'monthly':
                $statementData = $this->statementModel->getMonthlyStatement($year, $month);
                $filename = "monthly_statement_{$year}_{$month}.csv";
                break;
            case '6months':
                $statementData = $this->statementModel->getSixMonthStatement($year, $month);
                $filename = "6months_statement_{$year}_{$month}.csv";
                break;
            case 'yearly':
                $statementData = $this->statementModel->getYearlyStatement($year);
                $filename = "yearly_statement_{$year}.csv";
                break;
            case 'custom':
                if ($startDate && $endDate) {
                    $statementData = $this->statementModel->getCustomRangeStatement($startDate, $endDate);
                    $cleanStartDate = str_replace('-', '', $startDate);
                    $cleanEndDate = str_replace('-', '', $endDate);
                    $filename = "custom_statement_{$cleanStartDate}_to_{$cleanEndDate}.csv";
                } else {
                    // Default to current month if dates not provided
                    $statementData = $this->statementModel->getMonthlyStatement(date('Y'), date('m'));
                    $filename = "monthly_statement_" . date('Y') . "_" . date('m') . ".csv";
                    $periodType = 'monthly';
                }
                break;
            default:
                $statementData = $this->statementModel->getMonthlyStatement(date('Y'), date('m'));
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
            fputcsv($output, ['Date', 'Day', 'Cash In (৳)', 'Cash Out (৳)', 'Net Amount (৳)', 'Running Balance (৳)']);
            foreach ($statementData['daily_data'] as $row) {
                fputcsv($output, [
                    $row['formatted_date'],
                    $row['day_name'],
                    number_format($row['cash_in'], 2),
                    number_format($row['cash_out'], 2),
                    number_format($row['net_amount'], 2),
                    number_format($row['running_balance'], 2)
                ]);
            }
        } elseif (isset($statementData['monthly_data'])) {
            // Monthly breakdown
            fputcsv($output, ['Month', 'Cash In (৳)', 'Cash Out (৳)', 'Net Amount (৳)', 'Running Balance (৳)']);
            foreach ($statementData['monthly_data'] as $row) {
                fputcsv($output, [
                    $row['month'],
                    number_format($row['cash_in'], 2),
                    number_format($row['cash_out'], 2),
                    number_format($row['net_amount'], 2),
                    number_format($row['running_balance'], 2)
                ]);
            }
        }

        // Write summary
        fputcsv($output, []); // Empty row
        fputcsv($output, ['SUMMARY']);
        fputcsv($output, ['Total Cash In', number_format($statementData['summary']['total_cash_in'], 2)]);
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
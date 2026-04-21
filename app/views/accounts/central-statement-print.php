<?php
// This is the main view file for the central statement print page
$page_title = "Central Financial Statement";
$page = 'Accounts';
$addPage = false;
$show_breadcrumb = true;

// FIXED: Use consistent variable names with main view
// The controller passes $statement_data, not $statementData
$statementData = $statement_data ?? [];
$overallStats = $overall_stats ?? [];

// FIXED: Use the correct summary values from the statement data
// Make sure we have the summary array
$summary = $statementData['summary'] ?? $overallStats;

// Use the actual summary values
$printTotalCashIn = $summary['total_cashin'] ?? 0;
$printTotalCashOut = $summary['total_cash_out'] ?? 0;
$printTotalRevenue = $summary['total_revenue'] ?? 0;
$printNetBalance = ($summary['total_cashin'] ?? 0) - ($summary['total_cash_out'] ?? 0);

// FIXED: Get start and end dates from statement data if available
$startDate = $statementData['start_date'] ?? ($start_date ?? '');
$endDate = $statementData['end_date'] ?? ($end_date ?? '');

// FIXED: Get period text from available variables
$periodText = $period_text ?? $statementData['period'] ?? '';

// FIXED: Get company info from available variables
$company_name = $company_name ?? "THE PREMIUM HOMES LTD.";
$company_address = $company_address ?? "Land View Commercial Center, 9th Floor 28 Gulshan North C/A, Gulshan Circle-2, Dhaka";
$company_phone = $company_phone ?? "+8801958253300";
$company_email = $company_email ?? "info@thepremiumhomes.com";

// FIXED: Get statement model instance if needed
$statementModel = $statementModel ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Print</title>
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
        border: 0.5mm solid #000;
        padding: 4mm;
        border-radius: 2mm;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 2mm;
        padding-bottom: 1mm;
        border-bottom: 0.1mm dotted #ccc;
    }

    .summary-row:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .summary-label {
        font-weight: bold;
        font-size: 9pt;
    }

    .summary-value {
        font-weight: bold;
        font-family: 'Courier New', monospace;
        font-size: 10pt;
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
        padding: 2mm;

    }
    
    /* Color classes */
    .positive {
        color: #28a745;
    }
    
    .negative {
        color: #dc3545;
    }
    </style>
</head>

<body>
    <!-- Header -->
    <div class="statement-header">
        <div class="company-name"><?= htmlspecialchars($company_name) ?></div>
        <div class="company-address">
            <?= htmlspecialchars($company_address) ?><br>
            Phone: <?= htmlspecialchars($company_phone) ?> | Email: <?= htmlspecialchars($company_email) ?>
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
    <?php if (isset($projectName) || isset($categoryName)): ?>
        <div class="filter-info">
            <?php if (isset($projectName) && $projectName): ?>
                <strong>Project:</strong> <?= htmlspecialchars($projectName) ?>
            <?php endif; ?>
            <?php if (isset($categoryName) && $categoryName): ?>
                <?php if (isset($projectName) && $projectName): ?> | <?php endif; ?>
                <strong>Category:</strong> <?= htmlspecialchars($categoryName) ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($category) && $category === 'utilities'): ?>
        <?php
        // Get utilities transactions for print view
        if (isset($utilitiesTransactions) && !empty($utilitiesTransactions)): ?>
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
                            <th style="border: 0.3mm solid #999; padding: 2mm;">Amount (TK.)</th>
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
            <div class="stat-label">
                <?php 
                $cashinLabel = "Total Cash IN";
                if (isset($category)) {
                    if ($category === 'emi') $cashinLabel = "Total EMI";
                    elseif ($category === 'landshare') $cashinLabel = "Total Landshare";
                    elseif ($category === 'service_fee') $cashinLabel = "Total Service Fee";
                    elseif ($category === 'registration') $cashinLabel = "Total Registration";
                    elseif ($category === 'agreement') $cashinLabel = "Total Booking";
                    elseif ($category === 'management_cashin') $cashinLabel = "Total Management Cashin";
                    elseif ($category === 'bank_cashin') $cashinLabel = "Total Bank Cash In";
                    elseif ($category === 'bank_cashout') $cashinLabel = "Total Bank Cash Out";
                    elseif (in_array($category, ['cashout', 'utilities', 'other'])) $cashinLabel = "Total " . ucfirst($category);
                    elseif ($category === 'vendor_payment') $cashinLabel = "Total Vendor Payments";
                    elseif ($category === 'contractor_bill') $cashinLabel = "Total Contractor Bills";
                }
                ?>
                <?= $cashinLabel ?>
            </div>
            <div class="stat-value <?= (isset($category) && in_array($category, ['cashout', 'utilities', 'other', 'bank_cashout', 'vendor_payment', 'contractor_bill'])) ? 'cash-out' : 'revenue' ?>">
                <?php
                // For bank categories calculate from bank_transactions
                if (isset($category) && in_array($category, ['bank_cashin', 'bank_cashout'])) {
                    $bankTotal = 0;
                    foreach ($bank_transactions ?? [] as $bt) {
                        $bankTotal += (float)($bt['amount'] ?? 0);
                    }
                    $printStatVal = $bankTotal;
                } elseif (isset($category) && in_array($category, ['cashout', 'utilities', 'other', 'vendor_payment', 'contractor_bill'])) {
                    $printStatVal = $printTotalCashOut;
                } else {
                    $printStatVal = $printTotalCashIn;
                }
                ?>
                TK.<?= number_format($printStatVal, 2) ?>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value <?= $printTotalRevenue >= 0 ? 'revenue' : 'cash-out' ?>">
                TK.<?= number_format($printTotalRevenue, 2) ?>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Total Cash Out</div>
            <div class="stat-value cash-out">
             TK.<?= number_format($printTotalCashOut, 2) ?>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Net Balance</div>
            <div class="stat-value net-balance">
                TK.<?= number_format($printNetBalance, 2) ?>
            </div>
        </div>
    </div>

    <!-- Statement Data -->
<!-- Statement Data -->
<div class="period-title">
    <?php
    if (isset($category) && in_array($category, ['bank_cashin', 'bank_cashout'])) {
        echo $category === 'bank_cashin' ? 'BANK CASH-IN STATEMENT' : 'BANK CASH-OUT STATEMENT';
    } else {
        echo (isset($statementData['daily_data']) && !empty($statementData['daily_data'])) ? 'DAILY STATEMENT' : 'MONTHLY STATEMENT';
    }
    ?>
</div>

<?php if (isset($category) && in_array($category, ['bank_cashin', 'bank_cashout']) && !empty($bank_transactions)): ?>
    <!-- Bank Transactions Print Table -->
    <table class="statement-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Bank Name</th>
                <th>Branch</th>
                <th>Routing No</th>
                <th>Account No</th>
                <th><?= $category === 'bank_cashin' ? 'Client / Payment Type' : 'Purpose / Paid To' ?></th>
                <th class="text-right"><?= $category === 'bank_cashin' ? 'Cash In (TK.)' : 'Withdrawal (TK.)' ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $printBankTotal = 0;
            foreach ($bank_transactions as $idx => $bt):
                $printBankTotal += (float)($bt['amount'] ?? 0);
            ?>
                <tr class="avoid-break">
                    <td><?= $idx + 1 ?></td>
                    <td><?= htmlspecialchars($bt['payment_date'] ?? '') ?></td>
                    <td><?= htmlspecialchars($bt['bank_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($bt['branch_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($bt['routing_number'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($bt['account_number'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($bt['description'] ?? '-') ?></td>
                    <td class="text-right <?= $category === 'bank_cashin' ? 'positive' : 'negative' ?>">
                        TK.<?= number_format($bt['amount'] ?? 0, 2) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="font-weight:700; border-top:1mm solid #000;">
                <td colspan="7" class="text-right"><strong>Grand Total:</strong></td>
                <td class="text-right <?= $category === 'bank_cashin' ? 'positive' : 'negative' ?>">
                    <strong>TK.<?= number_format($printBankTotal, 2) ?></strong>
                </td>
            </tr>
        </tfoot>
    </table>

<?php elseif (isset($category) && in_array($category, ['bank_cashin', 'bank_cashout']) && empty($bank_transactions ?? [])): ?>
    <div class="text-center" style="padding: 20mm; font-style: italic; color: #666;">
        No <?= $category === 'bank_cashin' ? 'bank cash-in' : 'bank cash-out' ?> transactions found for the selected period.
    </div>

<?php elseif (isset($statementData['daily_data']) && !empty($statementData['daily_data'])): ?>
    <table class="statement-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Day</th>

                <?php if (!isset($category) || $category === 'all'): ?>
                    <th class="text-right">Agreement Value (TK.)</th>
                    <th class="text-right">Booking Money (TK.)</th>
                    <th class="text-right">Service Fee (TK.)</th>
                    <th class="text-right">EMI Payments (TK.)</th>
                    <th class="text-right">Landshare (TK.)</th>
                    <th class="text-right">Registration (TK.)</th>
                    <th class="text-right">Management Cashin (TK.)</th>
                    <th class="text-right">Total Cash IN (TK.)</th>
                    <th class="text-right">Cash Out (TK.)</th>
                    <th class="text-right">Net Amount (TK.)</th>
                    <th class="text-right">Running Balance (TK.)</th>
                <?php elseif (isset($category)): ?>
                    <?php if ($category === 'total_cashin'): ?>
                        <th class="text-right">Total Cash IN (TK.)</th>
                        <th class="text-right">Amount (TK.)</th>
                    <?php elseif ($category === 'emi'): ?>
                        <th class="text-right">EMI Payments (TK.)</th>
                        <th class="text-right">Amount (TK.)</th>
                    <?php elseif ($category === 'landshare'): ?>
                        <th class="text-right">Landshare (TK.)</th>
                        <th class="text-right">Amount (TK.)</th>
                    <?php elseif ($category === 'service_fee'): ?>
                        <th class="text-right">Service Fee (TK.)</th>
                        <th class="text-right">Amount (TK.)</th>
                    <?php elseif ($category === 'registration'): ?>
                        <th class="text-right">Registration (TK.)</th>
                        <th class="text-right">Amount (TK.)</th>
                    <?php elseif ($category === 'booking_money'): ?>
                        <th class="text-right">Booking Money (TK.)</th>
                        <th class="text-right">Amount (TK.)</th>
                    <?php elseif ($category === 'agreement'): ?>
                        <th class="text-right">Agreement Value (TK.)</th>
                        <th class="text-right">Amount (TK.)</th>
                    <?php elseif ($category === 'management_cashin'): ?>
                        <th class="text-right">Management Cashin (TK.)</th>
                        <th class="text-right">Amount (TK.)</th>
                    <?php elseif (in_array($category, ['cashout', 'utilities', 'other'])): ?>
                        <th class="text-right">Cash Out (TK.)</th>
                        <th class="text-right">Amount (TK.)</th>
                        <?php if ($category === 'cashout'): ?>
                        <th>Purpose / Paid To</th>
                        <?php endif; ?>
                    <?php elseif ($category === 'vendor_payment'): ?>
                        <th class="text-right">Vendor Payment (TK.)</th>
                        <th class="text-right">Amount (TK.)</th>
                        <th>Vendor / Details</th>
                    <?php elseif ($category === 'contractor_bill'): ?>
                        <th class="text-right">Contractor Bill (TK.)</th>
                        <th class="text-right">Amount (TK.)</th>
                        <th>Contractor / Details</th>
                    <?php endif; ?>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($statementData['daily_data'] as $row): 
                $rowDate = $row['date'] ?? '';
            ?>
                <tr class="avoid-break">
                    <td><?= htmlspecialchars($row['formatted_date'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['day_name'] ?? '') ?></td>

                    <?php if (!isset($category) || $category === 'all'): ?>
                        <td class="text-right positive">TK.<?= number_format($row['agreement_value'] ?? 0, 2) ?></td>
                        <td class="text-right positive">TK.<?= number_format($row['booking_money'] ?? 0, 2) ?></td>
                        <td class="text-right positive">TK.<?= number_format($row['service_fee'] ?? 0, 2) ?></td>
                        <td class="text-right positive">TK.<?= number_format($row['emi_payments'] ?? 0, 2) ?></td>
                        <td class="text-right positive">TK.<?= number_format($row['landshare_payments'] ?? 0, 2) ?></td>
                        <td class="text-right positive">TK.<?= number_format($row['registration'] ?? 0, 2) ?></td>
                        <td class="text-right positive">TK.<?= number_format($row['management_cashin'] ?? 0, 2) ?></td>
                        <td class="text-right positive">TK.<?= number_format($row['total_cashin'] ?? 0, 2) ?></td>
                        <td class="text-right negative">TK.<?= number_format($row['cash_out'] ?? 0, 2) ?></td>
                        <td class="text-right <?= ($row['net_amount'] ?? 0) >= 0 ? 'positive' : 'negative' ?>">
                            TK.<?= number_format($row['net_amount'] ?? 0, 2) ?>
                        </td>
                        <td class="text-right <?= ($row['running_balance'] ?? 0) >= 0 ? 'positive' : 'negative' ?>">
                            TK.<?= number_format($row['running_balance'] ?? 0, 2) ?>
                        </td>
                    <?php elseif (isset($category)): ?>
                        <?php if ($category === 'total_cashin'): ?>
                            <td class="text-right positive">TK.<?= number_format($row['total_cashin'] ?? 0, 2) ?></td>
                            <td class="text-right positive">TK.<?= number_format($row['total_cashin'] ?? 0, 2) ?></td>
                        <?php elseif ($category === 'emi'): ?>
                            <td class="text-right positive">TK.<?= number_format($row['emi_payments'] ?? 0, 2) ?></td>
                            <td class="text-right positive">TK.<?= number_format($row['emi_payments'] ?? 0, 2) ?></td>
                        <?php elseif ($category === 'landshare'): ?>
                            <td class="text-right positive">TK.<?= number_format($row['landshare_payments'] ?? 0, 2) ?></td>
                            <td class="text-right positive">TK.<?= number_format($row['landshare_payments'] ?? 0, 2) ?></td>
                        <?php elseif ($category === 'service_fee'): ?>
                            <td class="text-right positive">TK.<?= number_format($row['service_fee'] ?? 0, 2) ?></td>
                            <td class="text-right positive">TK.<?= number_format($row['service_fee'] ?? 0, 2) ?></td>
                        <?php elseif ($category === 'registration'): ?>
                            <td class="text-right positive">TK.<?= number_format($row['registration'] ?? 0, 2) ?></td>
                            <td class="text-right positive">TK.<?= number_format($row['registration'] ?? 0, 2) ?></td>
                        <?php elseif ($category === 'booking_money'): ?>
                            <td class="text-right positive">TK.<?= number_format($row['booking_money'] ?? 0, 2) ?></td>
                            <td class="text-right positive">TK.<?= number_format($row['booking_money'] ?? 0, 2) ?></td>
                        <?php elseif ($category === 'agreement'): ?>
                            <td class="text-right positive">TK.<?= number_format($row['agreement_value'] ?? 0, 2) ?></td>
                            <td class="text-right positive">TK.<?= number_format($row['agreement_value'] ?? 0, 2) ?></td>
                        <?php elseif ($category === 'management_cashin'): ?>
                            <td class="text-right positive">TK.<?= number_format($row['management_cashin'] ?? 0, 2) ?></td>
                            <td class="text-right positive">TK.<?= number_format($row['management_cashin'] ?? 0, 2) ?></td>
                        <?php elseif (in_array($category, ['cashout', 'utilities', 'other'])): ?>
                            <td class="text-right negative">TK.<?= number_format($row['cash_out'] ?? 0, 2) ?></td>
                            <td class="text-right negative">TK.<?= number_format($row['cash_out'] ?? 0, 2) ?></td>
                            <?php if ($category === 'cashout'): ?>
                            <td>
                                <?php 
                                $hasPayments = false;
                                if (!empty($cashout_payment_details[$rowDate])): 
                                    foreach ($cashout_payment_details[$rowDate] as $payment):
                                        $hasPayments = true;
                                ?>
                                    <div style="font-size: 7.5pt; margin-bottom: 3px; line-height: 1.4;">
                                        <span style="color: #333;"><?= htmlspecialchars($payment['purpose_paid_to'] ?? '—') ?></span>
                                        <?php if (!empty($payment['project_name'])): ?>
                                            <span style="color: #007bff;">(<?= htmlspecialchars($payment['project_name']) ?>)</span>
                                        <?php endif; ?>
                                        <?php if (!empty($payment['office'])): ?>
                                            <span style="color: #666;">· <?= htmlspecialchars($payment['office']) ?></span>
                                        <?php endif; ?>
                                        <span style="font-size: 7pt; background: #f0f0f0; padding: 1px 3px; border-radius: 2px;">
                                            <?= ucfirst($payment['payment_method'] ?? 'cash') ?>
                                        </span>
                                        <?php if (!empty($payment['transaction_ref'])): ?>
                                            <span style="color: #666;">· Ref: <?= htmlspecialchars($payment['transaction_ref']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php 
                                    endforeach;
                                endif;
                                if (!$hasPayments): 
                                ?>
                                    <span style="color: #aaa;">—</span>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        <?php elseif ($category === 'vendor_payment'): ?>
                            <td class="text-right negative">TK.<?= number_format($row['cash_out'] ?? 0, 2) ?></td>
                            <td class="text-right negative">TK.<?= number_format($row['cash_out'] ?? 0, 2) ?></td>
                            <td>
                                <?php 
                                $vDetails = $vendor_payment_details ?? [];
                                $hasPayments = false;
                                if (!empty($vDetails[$rowDate])): 
                                    foreach ($vDetails[$rowDate] as $payment):
                                        $hasPayments = true;
                                ?>
                                    <div style="font-size: 7.5pt; margin-bottom: 3px; line-height: 1.4;">
                                        <span style="color: #333;"><?= htmlspecialchars($payment['vendor_name'] ?? '—') ?></span>
                                        <?php if (!empty($payment['project_name'])): ?>
                                            <span style="color: #007bff;">(<?= htmlspecialchars($payment['project_name']) ?>)</span>
                                        <?php endif; ?>
                                        <?php if (!empty($payment['vpr_payment_type'])): ?>
                                            <span style="color: #666;">· <?= htmlspecialchars($payment['vpr_payment_type']) ?></span>
                                        <?php endif; ?>
                                        <span style="font-size: 7pt; background: #f0f0f0; padding: 1px 3px; border-radius: 2px;">
                                            <?= ucfirst($payment['payment_method'] ?? 'cash') ?>
                                        </span>
                                        <strong class="negative">TK.<?= number_format($payment['amount'] ?? 0, 2) ?></strong>
                                    </div>
                                <?php 
                                    endforeach;
                                endif;
                                if (!$hasPayments): 
                                ?>
                                    <span style="color: #aaa;">—</span>
                                <?php endif; ?>
                            </td>
                        <?php elseif ($category === 'contractor_bill'): ?>
                            <td class="text-right negative">TK.<?= number_format($row['cash_out'] ?? 0, 2) ?></td>
                            <td class="text-right negative">TK.<?= number_format($row['cash_out'] ?? 0, 2) ?></td>
                            <td>
                                <?php 
                                $cDetails = $contractor_bill_details ?? [];
                                $hasPayments = false;
                                if (!empty($cDetails[$rowDate])): 
                                    foreach ($cDetails[$rowDate] as $payment):
                                        $hasPayments = true;
                                ?>
                                    <div style="font-size: 7.5pt; margin-bottom: 3px; line-height: 1.4;">
                                        <span style="color: #333;"><?= htmlspecialchars($payment['contractor_name'] ?? '—') ?></span>
                                        <?php if (!empty($payment['project_name'])): ?>
                                            <span style="color: #007bff;">(<?= htmlspecialchars($payment['project_name']) ?>)</span>
                                        <?php endif; ?>
                                        <?php if (!empty($payment['bill_type'])): ?>
                                            <span style="color: #666;">· <?= htmlspecialchars($payment['bill_type']) ?></span>
                                        <?php endif; ?>
                                        <span style="font-size: 7pt; background: #f0f0f0; padding: 1px 3px; border-radius: 2px;">
                                            <?= ucfirst($payment['payment_method'] ?? 'cash') ?>
                                        </span>
                                        <strong class="negative">TK.<?= number_format($payment['amount'] ?? 0, 2) ?></strong>
                                    </div>
                                <?php 
                                    endforeach;
                                endif;
                                if (!$hasPayments): 
                                ?>
                                    <span style="color: #aaa;">—</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php elseif (isset($statementData['monthly_data']) && !empty($statementData['monthly_data'])): ?>
    <table class="statement-table">
        <thead>
            <tr>
                <th>Month</th>

                <?php if (!isset($category) || $category === 'all'): ?>
                    <th class="text-right">Agreement Value (TK.)</th>
                    <th class="text-right">Booking Money (TK.)</th>
                    <th class="text-right">Service Fee (TK.)</th>
                    <th class="text-right">EMI Payments (TK.)</th>
                    <th class="text-right">Landshare (TK.)</th>
                    <th class="text-right">Registration (TK.)</th>
                    <th class="text-right">Management Cashin (TK.)</th>
                    <th class="text-right">Total Cash IN (TK.)</th>
                    <th class="text-right">Cash Out (TK.)</th>
                    <th class="text-right">Net Amount (TK.)</th>
                    <th class="text-right">Running Balance (TK.)</th>
                <?php elseif (isset($category)): ?>
                    <?php if ($category === 'total_cashin'): ?>
                        <th class="text-right">Total Cash IN (TK.)</th>
                        <th class="text-right">Amount (TK.)</th>
                    <?php elseif ($category === 'emi'): ?>
                        <th class="text-right">EMI Payments (TK.)</th>
                        <th class="text-right">Amount (TK.)</th>
                    <?php elseif ($category === 'landshare'): ?>
                        <th class="text-right">Landshare (TK.)</th>
                        <th class="text-right">Amount (TK.)</th>
                    <?php elseif ($category === 'service_fee'): ?>
                        <th class="text-right">Service Fee (TK.)</th>
                        <th class="text-right">Amount (TK.)</th>
                    <?php elseif ($category === 'registration'): ?>
                        <th class="text-right">Registration (TK.)</th>
                        <th class="text-right">Amount (TK.)</th>
                    <?php elseif ($category === 'booking_money'): ?>
                        <th class="text-right">Booking Money (TK.)</th>
                        <th class="text-right">Amount (TK.)</th>
                    <?php elseif ($category === 'agreement'): ?>
                        <th class="text-right">Agreement Value (TK.)</th>
                        <th class="text-right">Amount (TK.)</th>
                    <?php elseif ($category === 'management_cashin'): ?>
                        <th class="text-right">Management Cashin (TK.)</th>
                        <th class="text-right">Amount (TK.)</th>
                    <?php elseif (in_array($category, ['cashout', 'utilities', 'other'])): ?>
                        <th class="text-right">Cash Out (TK.)</th>
                        <th class="text-right">Amount (TK.)</th>
                        <?php if ($category === 'cashout'): ?>
                        <th>Purpose / Paid To</th>
                        <?php endif; ?>
                    <?php elseif ($category === 'vendor_payment'): ?>
                        <th class="text-right">Vendor Payment (TK.)</th>
                        <th class="text-right">Amount (TK.)</th>
                    <?php elseif ($category === 'contractor_bill'): ?>
                        <th class="text-right">Contractor Bill (TK.)</th>
                        <th class="text-right">Amount (TK.)</th>
                    <?php endif; ?>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($statementData['monthly_data'] as $row): 
                $monthPrefix = ($row['year'] ?? '') . '-' . sprintf('%02d', $row['month_num'] ?? 0);
            ?>
                <tr>
                    <td><?= htmlspecialchars($row['month'] ?? '') ?></td>

                    <?php if (!isset($category) || $category === 'all'): ?>
                        <td class="text-right positive">TK.<?= number_format($row['agreement_value'] ?? 0, 2) ?></td>
                        <td class="text-right positive">TK.<?= number_format($row['booking_money'] ?? 0, 2) ?></td>
                        <td class="text-right positive">TK.<?= number_format($row['service_fee'] ?? 0, 2) ?></td>
                        <td class="text-right positive">TK.<?= number_format($row['emi_payments'] ?? 0, 2) ?></td>
                        <td class="text-right positive">TK.<?= number_format($row['landshare_payments'] ?? 0, 2) ?></td>
                        <td class="text-right positive">TK.<?= number_format($row['registration_payments'] ?? 0, 2) ?></td>
                        <td class="text-right positive">TK.<?= number_format($row['management_cashin'] ?? 0, 2) ?></td>
                        <td class="text-right positive">TK.<?= number_format($row['total_cashin'] ?? 0, 2) ?></td>
                        <td class="text-right negative">TK.<?= number_format($row['cash_out'] ?? 0, 2) ?></td>
                        <td class="text-right <?= ($row['net_amount'] ?? 0) >= 0 ? 'positive' : 'negative' ?>">
                            TK.<?= number_format($row['net_amount'] ?? 0, 2) ?>
                        </td>
                        <td class="text-right <?= ($row['running_balance'] ?? 0) >= 0 ? 'positive' : 'negative' ?>">
                            TK.<?= number_format($row['running_balance'] ?? 0, 2) ?>
                        </td>
                    <?php elseif (isset($category)): ?>
                        <?php if ($category === 'total_cashin'): ?>
                            <td class="text-right positive">TK.<?= number_format($row['total_cashin'] ?? 0, 2) ?></td>
                            <td class="text-right positive">TK.<?= number_format($row['total_cashin'] ?? 0, 2) ?></td>
                        <?php elseif ($category === 'emi'): ?>
                            <td class="text-right positive">TK.<?= number_format($row['emi_payments'] ?? 0, 2) ?></td>
                            <td class="text-right positive">TK.<?= number_format($row['emi_payments'] ?? 0, 2) ?></td>
                        <?php elseif ($category === 'landshare'): ?>
                            <td class="text-right positive">TK.<?= number_format($row['landshare_payments'] ?? 0, 2) ?></td>
                            <td class="text-right positive">TK.<?= number_format($row['landshare_payments'] ?? 0, 2) ?></td>
                        <?php elseif ($category === 'service_fee'): ?>
                            <td class="text-right positive">TK.<?= number_format($row['service_fee'] ?? 0, 2) ?></td>
                            <td class="text-right positive">TK.<?= number_format($row['service_fee'] ?? 0, 2) ?></td>
                        <?php elseif ($category === 'registration'): ?>
                            <td class="text-right positive">TK.<?= number_format($row['registration_payments'] ?? 0, 2) ?></td>
                            <td class="text-right positive">TK.<?= number_format($row['registration_payments'] ?? 0, 2) ?></td>
                        <?php elseif ($category === 'booking_money'): ?>
                            <td class="text-right positive">TK.<?= number_format($row['booking_money'] ?? 0, 2) ?></td>
                            <td class="text-right positive">TK.<?= number_format($row['booking_money'] ?? 0, 2) ?></td>
                        <?php elseif ($category === 'agreement'): ?>
                            <td class="text-right positive">TK.<?= number_format($row['agreement_value'] ?? 0, 2) ?></td>
                            <td class="text-right positive">TK.<?= number_format($row['agreement_value'] ?? 0, 2) ?></td>
                        <?php elseif ($category === 'management_cashin'): ?>
                            <td class="text-right positive">TK.<?= number_format($row['management_cashin'] ?? 0, 2) ?></td>
                            <td class="text-right positive">TK.<?= number_format($row['management_cashin'] ?? 0, 2) ?></td>
                        <?php elseif (in_array($category, ['cashout', 'utilities', 'other'])): ?>
                            <td class="text-right negative">TK.<?= number_format($row['cash_out'] ?? 0, 2) ?></td>
                            <td class="text-right negative">TK.<?= number_format($row['cash_out'] ?? 0, 2) ?></td>
                            <?php if ($category === 'cashout'): ?>
                            <td>
                                <?php 
                                $hasAny = false;
                                foreach ($cashout_payment_details as $date => $payments):
                                    if (!str_starts_with($date, $monthPrefix)) continue;
                                    foreach ($payments as $payment):
                                        $hasAny = true;
                                ?>
                                    <div style="font-size: 7.5pt; margin-bottom: 3px; line-height: 1.4;">
                                        <span style="color: #333;"><?= htmlspecialchars($payment['purpose_paid_to'] ?? '—') ?></span>
                                        <?php if (!empty($payment['project_name'])): ?>
                                            <span style="color: #007bff;">(<?= htmlspecialchars($payment['project_name']) ?>)</span>
                                        <?php endif; ?>
                                        <?php if (!empty($payment['office'])): ?>
                                            <span style="color: #666;">· <?= htmlspecialchars($payment['office']) ?></span>
                                        <?php endif; ?>
                                        <span style="font-size: 7pt; background: #f0f0f0; padding: 1px 3px; border-radius: 2px;">
                                            <?= ucfirst($payment['payment_method'] ?? 'cash') ?>
                                        </span>
                                        <?php if (!empty($payment['transaction_ref'])): ?>
                                            <span style="color: #666;">· Ref: <?= htmlspecialchars($payment['transaction_ref']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php 
                                    endforeach;
                                endforeach;
                                if (!$hasAny): 
                                ?>
                                    <span style="color: #aaa;">—</span>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        <?php elseif ($category === 'vendor_payment'): ?>
                            <td class="text-right negative">TK.<?= number_format($row['cash_out'] ?? 0, 2) ?></td>
                            <td class="text-right negative">TK.<?= number_format($row['cash_out'] ?? 0, 2) ?></td>
                        <?php elseif ($category === 'contractor_bill'): ?>
                            <td class="text-right negative">TK.<?= number_format($row['cash_out'] ?? 0, 2) ?></td>
                            <td class="text-right negative">TK.<?= number_format($row['cash_out'] ?? 0, 2) ?></td>
                        <?php endif; ?>
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
    <div class="summary-section">
        <div class="summary-title">PERIOD SUMMARY</div>
        <div class="summary-grid">
            <div class="summary-box">
                <?php if (!isset($category) || in_array($category, ['all', 'emi', 'landshare', 'service_fee', 'registration', 'booking_money', 'agreement', 'management_cashin', 'total_cashin', 'bank_cashin', 'bank_cashout', 'vendor_payment', 'contractor_bill'])): ?>
                    <div class="summary-row">
                        <span class="summary-label">
                            <?php 
                            $summaryLabel = "Total Cash IN";
                            if (isset($category)) {
                                if ($category === 'emi') $summaryLabel = "Total EMI Payments";
                                elseif ($category === 'landshare') $summaryLabel = "Total Landshare";
                                elseif ($category === 'service_fee') $summaryLabel = "Total Service Fee";
                                elseif ($category === 'registration') $summaryLabel = "Total Registration";
                                elseif ($category === 'agreement') $summaryLabel = "Total Booking Money";
                                elseif ($category === 'management_cashin') $summaryLabel = "Total Management Cashin";
                                elseif ($category === 'bank_cashin') $summaryLabel = "Total Bank Cash In";
                                elseif ($category === 'bank_cashout') $summaryLabel = "Total Bank Cash Out";
                            }
                            ?>
                            <?= $summaryLabel ?>:
                        </span>
                        <span class="summary-value <?= (isset($category) && $category === 'bank_cashout') ? 'cash-out' : 'revenue' ?>">TK.<?= number_format($printStatVal ?? $printTotalCashIn, 2) ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!isset($category) || in_array($category, ['all', 'cashout', 'utilities', 'other', 'vendor_payment', 'contractor_bill'])): ?>
                    <div class="summary-row">
                        <span class="summary-label">
                            <?php 
                            $cashoutLabel = "Total Cash Out";
                            if (isset($category)) {
                                if ($category === 'utilities') $cashoutLabel = "Total Utilities";
                                elseif ($category === 'other') $cashoutLabel = "Total Other";
                            }
                            ?>
                            <?= $cashoutLabel ?>:
                        </span>
                        <span class="summary-value cash-out">TK.<?= number_format($printTotalCashOut, 2) ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="summary-box">
                <div class="summary-row">
                    <span class="summary-label">Total Revenue:</span>
                    <span class="summary-value <?= $printTotalRevenue >= 0 ? 'revenue' : 'cash-out' ?>">
                        TK.<?= number_format($printTotalRevenue, 2) ?>
                    </span>
                </div>
                
                <div class="summary-row">
                    <span class="summary-label">Net Balance:</span>
                    <span class="summary-value net-balance">TK.<?= number_format($printNetBalance, 2) ?></span>
                </div>
            </div>
        </div>
    </div>

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
<?php
// Disable layout for this page
$disable_layout = true;

/**
 * Convert number to words (for Bangladeshi currency)
 */
function numberToWords($number) {
    $number = (float)$number;
    
    if ($number == 0) {
        return 'Zero Taka Only';
    }
    
    $integerPart = floor($number);
    $decimalPart = round(($number - $integerPart) * 100);
    
    $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
    $teens = ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    
    $result = '';
    
    if ($integerPart >= 10000000) {
        $crore = floor($integerPart / 10000000);
        $result .= convertHelper($crore, $ones, $teens, $tens) . ' Crore ';
        $integerPart %= 10000000;
    }
    
    if ($integerPart >= 100000) {
        $lakh = floor($integerPart / 100000);
        $result .= convertHelper($lakh, $ones, $teens, $tens) . ' Lakh ';
        $integerPart %= 100000;
    }
    
    if ($integerPart >= 1000) {
        $thousand = floor($integerPart / 1000);
        $result .= convertHelper($thousand, $ones, $teens, $tens) . ' Thousand ';
        $integerPart %= 1000;
    }
    
    if ($integerPart >= 100) {
        $hundred = floor($integerPart / 100);
        $result .= $ones[$hundred] . ' Hundred ';
        $integerPart %= 100;
    }
    
    if ($integerPart > 0) {
        $result .= convertHelper($integerPart, $ones, $teens, $tens);
    }
    
    $result = trim($result) . ' Taka';
    
    if ($decimalPart > 0) {
        $result .= ' and ' . convertHelper($decimalPart, $ones, $teens, $tens) . ' Paisa';
    }
    
    return trim($result) . ' Only';
}

function convertHelper($number, $ones, $teens, $tens) {
    $result = '';
    
    if ($number < 10) {
        $result = $ones[$number];
    } elseif ($number < 20) {
        $result = $teens[$number - 10];
    } elseif ($number < 100) {
        $result = $tens[floor($number / 10)] . ' ' . $ones[$number % 10];
    } else {
        $result = $ones[floor($number / 100)] . ' Hundred ' . convertHelper($number % 100, $ones, $teens, $tens);
    }
    
    return trim($result);
}

/**
 * Calculate actual paid amount after deducting pending/due amounts
 */
function calculateActualPaidAmount(array $transactions): float
{
    $total = 0.0;

    foreach ($transactions as $t) {
        $status = strtolower($t['payment_status'] ?? '');

        // Skip failed & pending completely
        if (in_array($status, ['failed', 'pending'], true)) {
            continue;
        }

        $amount = (float)($t['amount'] ?? 0);
        $due    = (float)($t['due_emi_amount'] ?? 0);

        if ($status === 'partially_paid') {
            $total += max(0, $amount - $due);
        } else {
            $total += $amount;
        }
    }

    return round($total, 2);
}

$validTransactions = array_values(array_filter($transactions, function ($t) {
    $status = strtolower($t['payment_status'] ?? '');
    return !in_array($status, ['failed', 'pending'], true);
}));

$actualPaidAmount = calculateActualPaidAmount($validTransactions);
$amountInWords    = numberToWords($actualPaidAmount);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Statement - <?= htmlspecialchars($clientDetails['client_name'] ?? 'N/A') ?></title>
    <style>
        /* A4 Page Dimensions: 210mm x 297mm */
        @page {
            size: A4;
            margin: 15mm;
        }

        /* Print Styles */
        @media print {
            .no-print { 
                display: none !important; 
            }
            .no-pdf { 
                display: none !important; 
            }
            body { 
                margin: 0 !important;
                padding: 0 !important;
                width: 210mm;
                height: 297mm;
                font-size: 10pt;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .statement-container { 
                width: 210mm !important;
                min-height: 297mm !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
            }
            .page-break { 
                page-break-before: always;
                margin-top: 20mm;
            }
            .avoid-break {
                page-break-inside: avoid;
            }
            .period-filter,
            .action-buttons {
                display: none !important;
            }
        }

        /* Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #fff;
            color: #000;
            line-height: 1.3;
            font-size: 10pt;
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 0;
        }

        .statement-container {
            width: 210mm;
            min-height: 297mm;
            background: #fff;
            padding: 15mm;
            position: relative;
        }

        /* Header */
        .statement-header {
            margin-bottom: 12mm;
            border-bottom: 0.5mm solid #000;
        }

        .company-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 6mm;
        }

        .company-info {
            flex: 2;
        }

        .company-name {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 1mm;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
        }

        .company-details {
            font-size: 8pt;
            line-height: 1.2;
        }

        .statement-info {
            flex: 1;
            text-align: right;
            border-left: 0.2mm solid #ccc;
            padding-left: 4mm;
        }

        .statement-title {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2mm;
            letter-spacing: 1pt;
        }

        .statement-meta {
            font-size: 8pt;
        }

        .meta-row {
            margin-bottom: 0.5mm;
        }

        .meta-label {
            font-weight: bold;
            display: inline-block;
            width: 25mm;
        }

        /* Period Filter */
        .period-filter {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 14px 18px;
            margin-bottom: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
        }

        .period-filter .filter-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        .period-filter .filter-row span {
            font-weight: 600;
            color: #374151;
            font-size: 14px;
            white-space: nowrap;
        }

        .period-filter select,
        .period-filter input[type="date"] {
            padding: 8px 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            color: #111827;
            background-color: #fff;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .period-filter select:focus,
        .period-filter input[type="date"]:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
            outline: none;
        }

        #customDateRange {
            background: #f9fafb;
            padding: 6px 8px;
            border-radius: 6px;
            border: 1px dashed #d1d5db;
        }

        #customDateRange span {
            font-size: 13px;
            color: #6b7280;
            font-weight: 500;
        }

        .period-filter button {
            padding: 8px 16px;
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: #ffffff;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s, box-shadow 0.2s;
        }

        .period-filter button:hover {
            background: linear-gradient(135deg, #1e40af, #1e3a8a);
            box-shadow: 0 6px 14px rgba(37, 99, 235, 0.25);
            transform: translateY(-1px);
        }

        .period-filter button:active {
            transform: translateY(0);
            box-shadow: 0 3px 8px rgba(37, 99, 235, 0.2);
        }

        /* Client & Property Details */
        .details-section {
            margin-bottom: 8mm;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5mm;
            margin-bottom: 5mm;
        }

        .details-box {
            border: 0.2mm solid #000;
            padding: 3mm;
            font-size: 9pt;
        }

        .details-title {
            font-weight: bold;
            margin-bottom: 2mm;
            padding-bottom: 1mm;
            border-bottom: 0.2mm solid #000;
            text-transform: uppercase;
            font-size: 9pt;
            letter-spacing: 0.5pt;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1mm;
            page-break-inside: avoid;
        }

        .detail-label {
            font-weight: normal;
        }

        .detail-value {
            font-weight: bold;
        }

        /* Account Summary */
        .summary-section {
            margin-bottom: 8mm;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 5mm;
        }

        .summary-table th,
        .summary-table td {
            border: 0.2mm solid #000;
            padding: 2mm 3mm;
            vertical-align: middle;
        }

        .summary-table th {
            background: #f8f8f8;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8pt;
            letter-spacing: 0.3pt;
        }

        .summary-table .amount {
            text-align: right;
            font-weight: bold;
            font-family: 'Courier New', monospace;
        }

        /* Transaction History */
        .transaction-section {
            margin-bottom: 8mm;
        }

        .section-title {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2mm;
            padding-bottom: 1mm;
            border-bottom: 0.2mm solid #000;
            font-size: 10pt;
            letter-spacing: 0.5pt;
        }

        .transaction-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            page-break-inside: auto;
        }

        .transaction-table th {
            border: 0.2mm solid #000;
            padding: 1.5mm 2mm;
            text-align: left;
            font-weight: bold;
            text-transform: uppercase;
            background: #f8f8f8;
            font-size: 7pt;
            letter-spacing: 0.3pt;
            vertical-align: middle;
        }

        .transaction-table td {
            border: 0.2mm solid #000;
            padding: 1.5mm 2mm;
            vertical-align: middle;
            page-break-inside: avoid;
        }

        .transaction-table .amount {
            text-align: right;
            font-weight: bold;
            font-family: 'Courier New', monospace;
        }

        .transaction-table .date {
            white-space: nowrap;
            width: 18mm;
        }

        .transaction-table .type {
            text-transform: uppercase;
            width: 25mm;
        }

        .transaction-table .installment {
            width: 20mm;
        }

        .transaction-table .method {
            width: 20mm;
        }

        .transaction-table .status {
            width: 15mm;
            text-align: center;
        }

        .status-paid {
            font-weight: bold;
        }

        .status-pending {
            font-style: italic;
            color: #666;
        }

        /* Totals */
        .totals-section {
            margin-top: 5mm;
            padding-top: 3mm;
            border-top: 0.2mm solid #000;
        }

        .totals-table {
            width: 100%;
            font-size: 9pt;
            margin-bottom: 5mm;
        }

        .totals-table tr {
            page-break-inside: avoid;
        }

        .totals-table td {
            padding: 1mm 0;
        }

        .totals-label {
            text-align: right;
            padding-right: 5mm;
            font-weight: bold;
            width: 70%;
        }

        .totals-value {
            text-align: right;
            font-weight: bold;
            font-family: 'Courier New', monospace;
            border-bottom: 0.1mm solid #000;
            width: 30%;
            padding-left: 2mm;
        }

        .grand-total td {
            padding-top: 2mm;
            border-top: 0.2mm solid #000;
            font-size: 10pt;
        }

        .amount-in-words {
            padding-top: 3mm;
            font-style: italic;
            text-align: right;
            font-size: 8pt;
            color: #333;
        }

        .calculation-note {
            margin-top: 4mm;
            padding: 2mm;
            background: #f8f8f8;
            border: 0.2mm solid #ccc;
            font-size: 7pt;
        }

        /* Statement Footer */
        .statement-footer {
            position: absolute;
            bottom: 15mm;
            left: 15mm;
            right: 15mm;
            padding-top: 3mm;
            border-top: 0.2mm solid #000;
            font-size: 7pt;
            line-height: 1.2;
            color: #666;
        }

        .footer-disclaimer {
            margin-bottom: 2mm;
            font-style: italic;
            text-align: center;
        }

        .footer-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2mm;
        }

        .page-number {
            text-align: center;
            font-weight: bold;
            font-size: 8pt;
        }

        /* Action Buttons */
        .action-buttons {
            position: fixed;
            top: 5mm;
            right: 5mm;
            z-index: 1000;
            display: flex;
            gap: 2mm;
            background: #fff;
            padding: 2mm;
            border: 0.2mm solid #ccc;
            box-shadow: 0 1mm 3mm rgba(0,0,0,0.1);
        }

        /* No Data */
        .no-data {
            text-align: center;
            padding: 10mm;
            font-style: italic;
            color: #666;
            border: 0.2mm dashed #ccc;
        }

        /* Utility */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .monospace { font-family: 'Courier New', monospace; }
        .uppercase { text-transform: uppercase; }
        
        .keep-together {
            page-break-inside: avoid;
        }
        
        .no-pdf {
            display: block;
        }

        /* Button Styles */
        .btn {
            padding: 8px 16px;
            font-size: 9pt;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            line-height: 1;
            transition: background-color 0.2s ease, box-shadow 0.2s ease, transform 0.1s ease, opacity 0.2s ease;
        }

        .btn:hover {
            opacity: 0.95;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.15);
            transform: translateY(-1px);
        }

        .btn:active {
            transform: translateY(0);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
        }

        .btn:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.15);
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: #ffffff;
        }

        .btn-print {
            background: linear-gradient(135deg, #ff3c00, #cc2f00);
            color: #ffffff;
        }

        .btn-back {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: #ffffff;
            text-decoration: none;
        }

        a.btn, a.btn-back {
            text-decoration: none;
        }

        .btn:disabled, .btn[disabled] {
            opacity: 0.6;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }

        @media print {
            .btn, .btn-print, .btn-back {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <!-- Action Buttons -->
    <div class="action-buttons no-print no-pdf" style="margin-bottom: 66px;">
        <button onclick="window.print()" class="btn btn-print">
            Print Statement
        </button>
        <button onclick="downloadPDF()" class="btn btn-success">
            Download PDF
        </button>
        <a href="<?= $GLOBALS['base_url'] ?>/clients-cash-in-history?booking_id=<?= $clientDetails['booking_id'] ?>" class="btn btn btn-back">
            Back to History
        </a>
    </div>

    <div class="statement-container" id="statement-content">
        <!-- Header -->
        <div class="statement-header">
            <div class="company-header">
                <div class="company-info">
                    <div class="company-name">The Premium Homes Ltd.</div>
                    <div class="company-details">
                        Land View Commercial Center, 9th Floor 28 Gulshan North C/A, <br> Gulshan Circle-2, Dhaka<br>
                        Phone: +8801958253300 | Email: info@dpremiumhomes.com
                    </div>
                </div>
                
                <div class="statement-info">
                    <div class="statement-title">Account Statement</div>
                    <div class="statement-meta">
                        <div class="meta-row">
                            <span class="meta-label">Statement No:</span>
                            STMT-<?= str_pad($clientDetails['booking_id'], 6, '0', STR_PAD_LEFT) ?>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">Generated:</span>
                            <?= date('d-M-Y') ?>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">Period:</span>
                            <?= $periodText ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Period Filter -->
        <div class="period-filter no-print no-pdf">
            <div class="filter-row">
                <span>Statement Period:</span>
                <select id="periodSelect" onchange="filterByPeriod()">
                    <option value="all" <?= ($period === 'all') ? 'selected' : '' ?>>All Transactions</option>
                    <option value="3" <?= ($period === '3') ? 'selected' : '' ?>>Last 3 Months</option>
                    <option value="6" <?= ($period === '6') ? 'selected' : '' ?>>Last 6 Months</option>
                    <option value="12" <?= ($period === '12') ? 'selected' : '' ?>>Last 12 Months</option>
                    <option value="custom" <?= ($period === 'custom') ? 'selected' : '' ?>>Custom Range</option>
                </select>
                
                <div id="customDateRange" style="display: <?= ($period === 'custom') ? 'flex' : 'none' ?>; gap: 1mm; align-items: center;">
                    <input type="date" id="fromDate" value="<?= $fromDate ?? '' ?>">
                    <span>to</span>
                    <input type="date" id="toDate" value="<?= $toDate ?? '' ?>">
                </div>
                
                <button onclick="applyFilter()">Apply</button>
            </div>
        </div>

        <!-- Client & Property Details -->
        <div class="details-section">
            <div class="details-grid">
                <div class="details-box">
                    <div class="details-title">Client Information</div>
                    <div class="detail-row">
                        <span class="detail-label">Name:</span>
                        <span class="detail-value"><?= htmlspecialchars($clientDetails['client_name'] ?? 'N/A') ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Phone:</span>
                        <span class="detail-value"><?= htmlspecialchars($clientDetails['phone'] ?? 'N/A') ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value"><?= htmlspecialchars($clientDetails['email'] ?? 'N/A') ?></span>
                    </div>
                </div>

                <div class="details-box">
                    <div class="details-title">Property Details</div>
                    <div class="detail-row">
                        <span class="detail-label">Project:</span>
                        <span class="detail-value"><?= htmlspecialchars($clientDetails['project_name'] ?? 'N/A') ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Unit:</span>
                        <span class="detail-value"><?= htmlspecialchars($clientDetails['unit_name'] ?? 'N/A') ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Floor:</span>
                        <span class="detail-value"><?= htmlspecialchars($clientDetails['flat_no'] ?? 'N/A') ?></span>
                    </div>
                </div>
            </div>
        </div>


        <!-- Transaction History -->
        <div class="transaction-section">
            <div class="section-title">
                Transaction History
                <span style="float: right; font-weight: normal; font-size: 8pt;">
                    Total Transactions: <?= count($transactions) ?>
                </span>
            </div>
            
            <table class="transaction-table">
                <thead>
                    <tr>
                        <th class="date">Date</th>
                        <th class="type">Payment Type</th>
                        <th class="installment">Installment</th>
                        <th class="method">Method</th>
                        <th class="status">Status</th>
                        <th class="amount">Amount (৳)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($transactions)): ?>
                        <?php $counter = 0; ?>
                        <?php foreach ($transactions as $transaction): ?>
                            <?php $counter++; ?>
                            <?php if ($counter > 15): ?>
                                </tbody>
                                </table>
                                <div class="page-break"></div>
                                <table class="transaction-table">
                                <thead>
                                    <tr>
                                        <th class="date">Date</th>
                                        <th class="type">Payment Type</th>
                                        <th class="installment">Installment</th>
                                        <th class="method">Method</th>
                                        <th class="status">Status</th>
                                        <th class="amount">Amount (৳)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php $counter = 1; ?>
                            <?php endif; ?>
                            <tr class="keep-together">
                                <td class="date">
                                    <?php 
                                    if (isset($transaction['payment_date'])) {
                                        echo date('d-M-Y', strtotime($transaction['payment_date']));
                                    } elseif (isset($transaction['booking_date'])) {
                                        echo date('d-M-Y', strtotime($transaction['booking_date']));
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td class="type">
                                    <?= strtoupper(str_replace('_', ' ', $transaction['payment_type'])) ?>
                                </td>
                                <td class="installment">
                                    <?php if ($transaction['payment_type'] === 'emi'): ?>
                                        <?= $transaction['installment_label'] ?? 'EMI' ?>
                                    <?php elseif ($transaction['payment_type'] === 'booking_money'): ?>
                                        BOOKING
                                    <?php elseif ($transaction['payment_type'] === 'registration'): ?>
                                        REGISTRATION
                                    <?php else: ?>
                                        <?= ucfirst(str_replace('_', ' ', $transaction['payment_type'])) ?>
                                    <?php endif; ?>
                                </td>
                                <td class="method">
                                    <?= ucfirst($transaction['payment_method'] ?? 'N/A') ?>
                                </td>
                                <td class="status">
                                    <?php if (($transaction['payment_status'] ?? 'paid') == 'paid'): ?>
                                        <span class="status-paid">PAID</span>
                                    <?php elseif (($transaction['payment_status'] ?? '') == 'partially_paid'): ?>
                                        <span class="status-pending">PARTIAL</span>
                                    <?php else: ?>
                                        <span class="status-pending">PENDING</span>
                                    <?php endif; ?>
                                </td>
                                <td class="amount">
                                    <?= number_format($transaction['amount'] ?? 0, 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="no-data">
                                No transactions found for the selected period
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td class="totals-label">Subtotal (This Period):</td>
                    <td class="totals-value">৳<?= number_format($actualPaidAmount, 2) ?></td>
                </tr>
                <tr class="grand-total">
                    <td class="totals-label">TOTAL PAID THIS PERIOD:</td>
                    <td class="totals-value">৳<?= number_format($actualPaidAmount, 2) ?></td>
                </tr>
            </table>
            
            <div class="amount-in-words">
                <strong>Amount in Words:</strong> <?= $amountInWords ?>
            </div>
            
            <div class="calculation-note">
                <strong>Note:</strong> This total represents actual payments received. Pending transactions are excluded, 
                and for partially paid transactions, the due amount has been deducted from the total.
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function filterByPeriod() {
            const period = document.getElementById('periodSelect').value;
            const customRange = document.getElementById('customDateRange');
            
            if (period === 'custom') {
                customRange.style.display = 'flex';
            } else {
                customRange.style.display = 'none';
            }
        }

        function applyFilter() {
            const period = document.getElementById('periodSelect').value;
            const bookingId = <?= $clientDetails['booking_id'] ?>;
            let url = '<?= $GLOBALS['base_url'] ?>/client-statement?booking_id=' + bookingId + '&period=' + period;
            
            if (period === 'custom') {
                const fromDate = document.getElementById('fromDate').value;
                const toDate = document.getElementById('toDate').value;
                
                if (!fromDate || !toDate) {
                    alert('Please select both from and to dates');
                    return;
                }
                
                url += '&from=' + fromDate + '&to=' + toDate;
            }
            
            window.location.href = url;
        }

        function downloadPDF() {
            const noPdfElements = document.querySelectorAll('.no-pdf');
            noPdfElements.forEach(el => {
                el.style.display = 'none';
            });
            
            const element = document.getElementById('statement-content');
            const opt = {
                margin: [15, 15, 15, 15],
                filename: 'ACCOUNT_STATEMENT_<?= str_pad($clientDetails['booking_id'], 6, '0', STR_PAD_LEFT) ?>_<?= date('Ymd') ?>.pdf',
                image: { type: 'jpeg', quality: 1.0 },
                html2canvas: { 
                    scale: 2,
                    useCORS: true,
                    letterRendering: true,
                    width: 794,
                    height: 1123,
                    ignoreElements: function(element) {
                        return element.classList.contains('no-pdf');
                    }
                },
                jsPDF: { 
                    unit: 'mm', 
                    format: 'a4', 
                    orientation: 'portrait',
                    compress: true
                },
                pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
            };
            
            html2pdf().set(opt).from(element).save().then(() => {
                noPdfElements.forEach(el => {
                    el.style.display = '';
                });
            });
        }
    </script>
</body>
</html>
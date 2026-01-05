<?php
// Disable layout for this page
$disable_layout = true;

// Calculate totals excluding pending/failed transactions
$totalAmount = 0;
$paidTransactionCount = 0;

// Initialize summary if not set
if (!isset($statementSummary) || !is_array($statementSummary)) {
    $statementSummary = [
        'transaction_count' => 0,
        'total_amount' => 0,
        'paid_amount' => 0,
        'pending_amount' => 0
    ];
}

// Initialize transactions if not set
if (!isset($transactions) || !is_array($transactions)) {
    $transactions = [];
}

foreach ($transactions as $transaction) {
    $status = $transaction['payment_status'] ?? 'paid';
    if ($status === 'paid') {
        $totalAmount += $transaction['amount'] ?? 0;
        $paidTransactionCount++;
    }
}

// Function to convert number to English words
function convertNumberToWords($number)
{
    // If number is 0
    if ($number == 0) {
        return "Zero Taka";
    }

    // Remove commas and format
    $number = (float)str_replace(',', '', $number);

    // Split into taka and paisa
    $parts = explode('.', number_format($number, 2, '.', ''));
    $taka = $parts[0];
    $paisa = isset($parts[1]) ? $parts[1] : '00';

    // Word arrays
    $ones = array(
        '',
        'One',
        'Two',
        'Three',
        'Four',
        'Five',
        'Six',
        'Seven',
        'Eight',
        'Nine',
        'Ten',
        'Eleven',
        'Twelve',
        'Thirteen',
        'Fourteen',
        'Fifteen',
        'Sixteen',
        'Seventeen',
        'Eighteen',
        'Nineteen'
    );

    $tens = array(
        '',
        '',
        'Twenty',
        'Thirty',
        'Forty',
        'Fifty',
        'Sixty',
        'Seventy',
        'Eighty',
        'Ninety'
    );

    $thousands = array('', 'Thousand', 'Million', 'Billion');

    // Nested function to convert a number less than 1000
    $convertUnder1000 = function ($num) use ($ones, $tens) {
        $word = '';
        $num = (int)$num;

        if ($num >= 100) {
            $word .= $ones[floor($num / 100)] . ' Hundred';
            $num %= 100;
            if ($num > 0) $word .= ' ';
        }

        if ($num >= 20) {
            $word .= $tens[floor($num / 10)];
            $num %= 10;
            if ($num > 0) $word .= ' ' . $ones[$num];
        } elseif ($num > 0) {
            $word .= $ones[$num];
        }

        return $word;
    };

    // Convert taka
    $takaWords = '';
    $taka = (int)$taka;

    if ($taka == 0) {
        $takaWords = '';
    } else {
        $groups = array();
        $i = 0;

        while ($taka > 0) {
            $groups[$i] = $taka % 1000;
            $taka = (int)($taka / 1000);
            $i++;
        }

        for ($j = count($groups) - 1; $j >= 0; $j--) {
            if ($groups[$j] != 0) {
                $groupWords = $convertUnder1000($groups[$j]);
                if ($groupWords != '') {
                    $takaWords .= $groupWords . ' ' . $thousands[$j] . ' ';
                }
            }
        }

        $takaWords = trim($takaWords) . ' Taka';
    }

    // Convert paisa
    $paisaWords = '';
    $paisa = (int)$paisa;

    if ($paisa > 0) {
        if ($paisa >= 20) {
            $paisaWords = $tens[floor($paisa / 10)];
            $paisa %= 10;
            if ($paisa > 0) {
                $paisaWords .= ' ' . $ones[$paisa];
            }
        } else {
            $paisaWords = $ones[$paisa];
        }
        $paisaWords .= ' Paisa';
    }

    // Combine
    $result = '';
    if ($takaWords != '' && $paisaWords != '') {
        $result = $takaWords . ' and ' . $paisaWords;
    } elseif ($takaWords != '') {
        $result = $takaWords;
    } elseif ($paisaWords != '') {
        $result = $paisaWords;
    } else {
        $result = 'Zero Taka';
    }

    return ucfirst(trim($result));
}

// Convert total amount to words
$totalAmountWords = convertNumberToWords($totalAmount);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management Statement - All Roles</title>
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

        /* Details Section */
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

        /* Summary */
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

        .transaction-table .source {
            width: 25mm;
        }

        .transaction-table .role {
            width: 20mm;
        }

        .transaction-table .person {
            width: 20mm;
        }

        .transaction-table .method {
            width: 15mm;
        }

        .transaction-table .status {
            width: 15mm;
            text-align: center;
        }

        .status-paid {
            font-weight: bold;
        }

        .status-pending,
        .status-failed {
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

        /* Amount in Words */
        .amount-in-words {
            margin-top: 5mm;
            padding: 3mm;
            border: 0.2mm solid #000;
            background: #f8f8f8;
            font-size: 9pt;
            line-height: 1.4;
        }

        .amount-in-words .label {
            font-weight: bold;
            margin-bottom: 1mm;
            text-transform: uppercase;
        }

        .amount-in-words .words {
            font-style: italic;
            text-transform: uppercase;
        }

        /* Footer */
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
            box-shadow: 0 1mm 3mm rgba(0, 0, 0, 0.1);
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
        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-bold {
            font-weight: bold;
        }

        .monospace {
            font-family: 'Courier New', monospace;
        }

        .uppercase {
            text-transform: uppercase;
        }

        .keep-together {
            page-break-inside: avoid;
        }

        .no-pdf {
            display: block;
        }

        /* Buttons */
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
            transition: all 0.2s ease;
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

        a.btn {
            text-decoration: none;
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
        <a href="<?= $GLOBALS['base_url'] ?>/management-cash-in" class="btn btn-back">
            Back to Management
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
                    <div class="statement-title">Management Statement</div>
                    <div class="statement-meta">
                        <div class="meta-row">
                            <span class="meta-label">Date:</span>
                            <?= date('d-M-Y') ?>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">Period:</span>
                            <?= htmlspecialchars($periodText ?? 'All Transactions') ?>
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
                    <option value="all" <?= (($period ?? 'all') === 'all') ? 'selected' : '' ?>>All Transactions</option>
                    <option value="3" <?= (($period ?? '') === '3') ? 'selected' : '' ?>>Last 3 Months</option>
                    <option value="6" <?= (($period ?? '') === '6') ? 'selected' : '' ?>>Last 6 Months</option>
                    <option value="12" <?= (($period ?? '') === '12') ? 'selected' : '' ?>>Last 12 Months</option>
                    <option value="custom" <?= (($period ?? '') === 'custom') ? 'selected' : '' ?>>Custom Range</option>
                </select>

                <div id="customDateRange" style="display: <?= (($period ?? '') === 'custom') ? 'flex' : 'none' ?>; gap: 1mm; align-items: center;">
                    <input type="date" id="fromDate" value="<?= htmlspecialchars($fromDate ?? '') ?>">
                    <span>to</span>
                    <input type="date" id="toDate" value="<?= htmlspecialchars($toDate ?? '') ?>">
                </div>

                <button onclick="applyFilter()">Apply</button>
            </div>
        </div>

        <!-- Statement Summary -->
        <div class="details-section">
            <div class="details-grid">
                <div class="details-box">
                    <div class="details-title">Statement Summary</div>
                    <div class="detail-row">
                        <span class="detail-label">Type:</span>
                        <span class="detail-value">ALL ROLES STATEMENT</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Period:</span>
                        <span class="detail-value"><?= htmlspecialchars($periodText ?? 'All Transactions') ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Generated:</span>
                        <span class="detail-value"><?= date('d M, Y h:i A') ?></span>
                    </div>
                </div>

                <div class="details-box">
                    <div class="details-title">Financial Summary (Paid Only)</div>
                    <div class="detail-row">
                        <span class="detail-label">Total Transactions:</span>
                        <span class="detail-value"><?= $statementSummary['transaction_count'] ?? 0 ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Paid Transactions:</span>
                        <span class="detail-value"><?= $paidTransactionCount ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Non-Paid (Pending/Failed):</span>
                        <span class="detail-value"><?= ($statementSummary['transaction_count'] ?? 0) - $paidTransactionCount ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Total Amount (Paid Only):</span>
                        <span class="detail-value">৳<?= number_format($totalAmount, 2) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction History - Showing ALL transactions but marking status -->
        <div class="transaction-section">
            <div class="section-title">
                Transaction History - All Roles
                <span style="float: right; font-weight: normal; font-size: 8pt;">
                    Showing <?= count($transactions) ?> transactions (<?= $paidTransactionCount ?> paid)
                </span>
            </div>

            <table class="transaction-table">
                <thead>
                    <tr>
                        <th class="date">Date</th>
                        <th class="source">Source</th>
                        <th class="role">Role</th>
                        <th class="person">Person</th>
                        <th class="method">Method</th>
                        <th class="status">Status</th>
                        <th class="amount">Amount (৳)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($transactions)): ?>
                        <?php $counter = 0; ?>
                        <?php foreach ($transactions as $transaction): ?>
                            <?php
                            $counter++;
                            $status = $transaction['payment_status'] ?? 'paid';
                            $statusClass = ($status === 'paid') ? 'status-paid' : (($status === 'pending') ? 'status-pending' : 'status-failed');
                            $statusText = strtoupper($status);
                            ?>
                            <?php if ($counter > 15): ?>
                </tbody>
            </table>
            <div class="page-break"></div>
            <table class="transaction-table">
                <thead>
                    <tr>
                        <th class="date">Date</th>
                        <th class="source">Source</th>
                        <th class="role">Role</th>
                        <th class="person">Person</th>
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
                        <?= date('d-M-Y', strtotime($transaction['transaction_date'] ?? 'now')) ?>
                    </td>
                    <td class="source">
                        <?= htmlspecialchars($transaction['source'] ?? 'N/A') ?>
                    </td>
                    <td class="role">
                        <?= htmlspecialchars($transaction['role_name'] ?? 'N/A') ?>
                    </td>
                    <td class="person">
                        <?= htmlspecialchars($transaction['person_name'] ?? 'N/A') ?>
                    </td>
                    <td class="method">
                        <?= ucfirst($transaction['payment_method'] ?? 'N/A') ?>
                    </td>
                    <td class="status">
                        <span class="<?= $statusClass ?>"><?= $statusText ?></span>
                    </td>
                    <td class="amount">
                        <?= number_format($transaction['amount'] ?? 0, 2) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" class="no-data">
                    No transactions found for the selected period
                </td>
            </tr>
        <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Totals - Only showing PAID amounts -->
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td class="totals-label">Total Paid Transactions:</td>
                    <td class="totals-value"><?= $paidTransactionCount ?></td>
                </tr>
                <tr class="grand-total">
                    <td class="totals-label">TOTAL AMOUNT (PAID ONLY):</td>
                    <td class="totals-value">৳<?= number_format($totalAmount, 2) ?></td>
                </tr>
            </table>

            <!-- Amount in Words -->
            <div class="amount-in-words">
                <div class="label">Amount in Words:</div>
                <div class="words"><?= htmlspecialchars($totalAmountWords) ?></div>
            </div>

            <?php if (($statementSummary['transaction_count'] ?? 0) - $paidTransactionCount > 0): ?>
                <div style="font-size: 8pt; color: #666; margin-top: 3mm; font-style: italic;">
                    Note: <?= ($statementSummary['transaction_count'] ?? 0) - $paidTransactionCount ?> transaction(s) with pending/failed status excluded from total amount
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="statement-footer">
            <div class="footer-disclaimer">
                This is a computer-generated statement for ALL MANAGEMENT ROLES and does not require a signature.
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
                // Auto-fill dates if empty
                if (!document.getElementById('fromDate').value) {
                    const today = new Date();
                    const lastMonth = new Date();
                    lastMonth.setMonth(today.getMonth() - 1);

                    document.getElementById('fromDate').value = lastMonth.toISOString().split('T')[0];
                    document.getElementById('toDate').value = today.toISOString().split('T')[0];
                }
            } else {
                customRange.style.display = 'none';
            }
        }

        function applyFilter() {
            const period = document.getElementById('periodSelect').value;
            let url = '<?= $GLOBALS['base_url'] ?>/management-cash-in-statement?period=' + period;

            if (period === 'custom') {
                const fromDate = document.getElementById('fromDate').value;
                const toDate = document.getElementById('toDate').value;

                if (!fromDate || !toDate) {
                    alert('Please select both from and to dates');
                    return;
                }

                // Validate date order
                if (fromDate > toDate) {
                    alert('From date cannot be later than To date');
                    return;
                }

                url += '&from=' + fromDate + '&to=' + toDate;
            }

            // Show loading indicator
            const applyBtn = event?.target || document.querySelector('.period-filter button');
            if (applyBtn) {
                const originalText = applyBtn.textContent;
                applyBtn.textContent = 'Applying...';
                applyBtn.disabled = true;

                setTimeout(() => {
                    applyBtn.textContent = originalText;
                    applyBtn.disabled = false;
                    window.location.href = url;
                }, 500);
            } else {
                window.location.href = url;
            }
        }


        function downloadPDF() {
            const noPdfElements = document.querySelectorAll('.no-pdf');
            noPdfElements.forEach(el => el.style.display = 'none');

            const element = document.getElementById('statement-content');
            const opt = {
                margin: [15, 15, 15, 15],
                filename: 'MANAGEMENT_STATEMENT_ALL_ROLES_<?= date('Ymd_His') ?>.pdf',
                image: {
                    type: 'jpeg',
                    quality: 1.0
                },
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
                pagebreak: {
                    mode: ['avoid-all', 'css', 'legacy']
                }
            };

            // Fix: declare originalText here
            let originalText = '';
            const downloadBtn = document.querySelector('.btn-success');
            if (downloadBtn) {
                originalText = downloadBtn.textContent;
                downloadBtn.textContent = 'Generating PDF...';
                downloadBtn.disabled = true;
            }

            html2pdf().set(opt).from(element).save().then(() => {
                noPdfElements.forEach(el => el.style.display = '');
                if (downloadBtn) {
                    downloadBtn.textContent = originalText;
                    downloadBtn.disabled = false;
                }
            }).catch(error => {
                alert('Error generating PDF: ' + error.message);
                noPdfElements.forEach(el => el.style.display = '');
                if (downloadBtn) {
                    downloadBtn.textContent = originalText;
                    downloadBtn.disabled = false;
                }
            });
        }


        // Initialize date pickers
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];

            // Set max date for date inputs
            const dateInputs = document.querySelectorAll('input[type="date"]');
            dateInputs.forEach(input => {
                input.max = today;
            });

            // Trigger filterByPeriod on page load to ensure correct state
            filterByPeriod();
        });
    </script>
</body>

</html>
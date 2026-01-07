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
        /* ==================== PRINT & PDF STYLES ==================== */
        @page {
            size: A4;
            margin: 15mm;
        }

        @media print {

            .no-print,
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
                padding: 5mm !important;
                box-shadow: none !important;
                border: none !important;
    
            }

            .company-header {
                flex-direction: row !important;
            }

            .statement-info {
                text-align: right !important;
                border-left: 1px solid #ccc !important;
                border-top: none !important;
                padding-left: 15px !important;
                padding-top: 0 !important;
            }

            .details-grid {
                grid-template-columns: 1fr 1fr !important;
            }

            .transaction-table {
                font-size: 8pt !important;
            }
        }

        /* ==================== BASE STYLES (Responsive) ==================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f5f5;
            color: #000;
            line-height: 1.3;
            font-size: clamp(10pt, 2vw, 12pt);
            margin: 0;
            padding: clamp(10px, 3vw, 20px);
        }

        .statement-container {
            max-width: 210mm;
            background: #fff;
            padding: clamp(12px, 3vw, 20px);
            margin: 0 auto;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* ==================== HEADER (Responsive) ==================== */
        .statement-header {
            margin-bottom: clamp(15px, 5vw, 30px);
            border-bottom: 2px solid #000;
            padding-bottom: clamp(10px, 2vw, 15px);
        }

        .company-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 15px;
        }

        .company-info {
            flex: 2;
        }

        .company-name {
            font-size: clamp(14px, 3vw, 18px);
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .company-details {
            font-size: clamp(9px, 2vw, 11px);
            line-height: 1.4;
        }

        .statement-info {
            flex: 1;
            text-align: right;
            border-left: 1px solid #ccc;
            padding-left: 15px;
        }

        .statement-title {
            font-size: clamp(14px, 3vw, 18px);
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .statement-meta {
            font-size: clamp(9px, 2vw, 11px);
        }

        .meta-row {
            margin-bottom: 5px;
        }

        .meta-label {
            font-weight: bold;
            display: inline-block;
            min-width: 90px;
        }

        /* ==================== PERIOD FILTER (Responsive) ==================== */
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

        .period-filter select,
        .period-filter input[type="date"] {
            padding: 8px 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            background-color: #fff;
        }

        #customDateRange {
            background: #f9fafb;
            padding: 6px 8px;
            border-radius: 6px;
            border: 1px dashed #d1d5db;
            display: flex;
            gap: 8px;
            align-items: center;
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
        }

        /* ==================== DETAILS SECTION (Responsive) ==================== */
        .details-section {
            margin-bottom: clamp(15px, 3vw, 20px);
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: clamp(10px, 2vw, 15px);
            margin-bottom: 15px;
        }

        .details-box {
            border: 1px solid #000;
            padding: clamp(10px, 2vw, 15px);
            font-size: clamp(11px, 2vw, 12px);
        }

        .details-title {
            font-weight: bold;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #000;
            text-transform: uppercase;
            font-size: clamp(12px, 2vw, 13px);
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            gap: 10px;
        }

        .detail-value {
            font-weight: bold;
            text-align: right;
            word-break: break-word;
        }

        /* ==================== TRANSACTION TABLE (Responsive with Scroll) ==================== */
        .transaction-section {
            margin-bottom: 20px;
        }

        .section-title {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #000;
            font-size: clamp(13px, 2.5vw, 14px);
        }

        .transaction-table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 0 -5px;
            padding: 0 5px;
        }

        .transaction-table {
            min-width: 343px;
            width: 100%;
            border-collapse: collapse;
            font-size: clamp(9px, 2vw, 11px);

        }

        .transaction-table th,
        .transaction-table td {
            border: 1px solid #000;
            padding: clamp(6px, 1.5vw, 10px) clamp(4px, 1vw, 8px);
            vertical-align: middle;
        }

        .transaction-table th {
            background: #f8f8f8;
            font-weight: bold;
            text-transform: uppercase;
            font-size: clamp(8px, 1.8vw, 10px);
            white-space: nowrap;
        }

        .transaction-table .amount {
            text-align: right;
            font-weight: bold;
            font-family: 'Courier New', monospace;
        }

        .status-paid {
            font-weight: bold;
        }

        .status-pending,
        .status-failed {
            font-style: italic;
            color: #666;
        }

        /* ==================== TOTALS (Responsive) ==================== */
        .totals-section {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #000;
        }

        .totals-table {
            width: 100%;
            font-size: clamp(12px, 2.5vw, 13px);
            margin-bottom: 15px;
        }

        .totals-table td {
            padding: 8px 0;
        }

        .totals-label {
            text-align: right;
            padding-right: clamp(10px, 3vw, 20px);
            font-weight: bold;
            width: 70%;
        }

        .totals-value {
            text-align: right;
            font-weight: bold;
            font-family: 'Courier New', monospace;
            border-bottom: 1px solid #000;
            width: 30%;
            padding-left: 10px;
        }

        .grand-total td {
            padding-top: 10px;
            border-top: 2px solid #000;
            font-size: clamp(13px, 2.5vw, 14px);
        }

        .amount-in-words {
            padding: 15px;
            border: 1px solid #000;
            background: #f8f8f8;
            font-size: clamp(10px, 2vw, 11px);
            line-height: 1.4;
            margin-bottom: 15px;
        }

        .amount-in-words .label {
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .amount-in-words .words {
            font-style: italic;
            text-transform: uppercase;
        }

        /* ==================== ACTION BUTTONS (Responsive) ==================== */
        .action-buttons {
            position: sticky;
            top: 10px;
            z-index: 1000;
            display: flex;
            justify-content: end;
            gap: 10px;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            white-space: nowrap;
            text-decoration: none;
        }

        .btn:hover {
            opacity: 0.95;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.15);
            transform: translateY(-1px);
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: #fff;
        }

        .btn-print {
            background: linear-gradient(135deg, #ff3c00, #cc2f00);
            color: #fff;
        }

        .btn-back {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: #fff;
        }

        /* ==================== MOBILE RESPONSIVENESS ==================== */
        @media (max-width: 768px) {
            .company-header {
                flex-direction: column;
                gap: 15px;
            }

            .statement-info {
                text-align: left;
                border-left: none;
                border-top: 1px solid #ccc;
                padding-left: 0;
                padding-top: 15px;
            }

            .period-filter .filter-row {
                flex-direction: column;
                align-items: stretch;
            }

            .period-filter select,
            .period-filter input[type="date"],
            .period-filter button {
                width: 100%;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .meta-label {
                min-width: 70px;
            }
        }

        @media (max-width: 480px) {
            .section-title {
                padding-bottom: 30px;
                font-size: clamp(13px, 2.5vw, 14px);
            }

            .details-grid {
                grid-template-columns: 1fr;
            }


            .action-buttons {
                margin-bottom: 10px;
            }
        }

        /* ==================== PDF GENERATION CLASS ==================== */
        .pdf-generating .statement-container {
            width: 210mm !important;
            min-height: 297mm !important;
            padding: 15mm !important;
        }

        .pdf-generating .company-header {
            flex-direction: row !important;
        }

        .pdf-generating .statement-info {
            text-align: right !important;
            border-left: 1px solid #ccc !important;
            border-top: none !important;
            padding-left: 15px !important;
            padding-top: 0 !important;
        }

        .pdf-generating .details-grid {
            grid-template-columns: 1fr 1fr !important;
        }

        .pdf-generating .transaction-table {
            font-size: 8pt !important;
        }

        .no-data {
            text-align: center;
            padding: 20px;
            font-style: italic;
            color: #666;
        }

        .keep-together {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>
    <!-- Action Buttons -->
    <div class="action-buttons no-print no-pdf" style="margin-bottom: 66px;">
        <button onclick="window.print()" class="btn btn-print">
            Print Statement
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
                <span style="float: right; font-weight: normal; font-size: 8pt; margin-top: 10px;">
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
            const element = document.getElementById('invoice-content');

            // Add PDF mode
            document.body.classList.add('pdf-mode');

            const opt = {
                filename: 'Payment-Receipt-<?= str_pad($payment['id'] ?? '0', 6, '0', STR_PAD_LEFT) ?>-<?= date('Ymd') ?>.pdf',
                image: {
                    type: 'jpeg',
                    quality: 0.98
                },
                html2canvas: {
                    scale: 2,
                    logging: false
                },
                jsPDF: {
                    unit: 'mm',
                    format: 'a4',
                    orientation: 'portrait'
                }
            };

            html2pdf()
                .set(opt)
                .from(element)
                .save()
                .then(() => {
                    // Remove PDF mode after download
                    document.body.classList.remove('pdf-mode');
                });

        }
    </script>
</body>

</html>
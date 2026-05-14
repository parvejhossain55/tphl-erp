<?php
// Disable layout for this page
$disable_layout = true;

/**
 * Convert number to words (for Bangladeshi currency)
 */
function numberToWords($number)
{
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

function convertHelper($number, $ones, $teens, $tens)
{
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

// Filter out failed, pending, AND rejected (verification_status) transactions
$validTransactions = array_values(array_filter($transactions, function ($t) {
    $payStatus = strtolower($t['payment_status'] ?? '');
    $verStatus = strtolower($t['verification_status'] ?? 'pending');
    // Exclude failed/pending payment status AND rejected verification
    if (in_array($payStatus, ['failed', 'pending'], true)) return false;
    if ($verStatus === 'rejected') return false;
    return true;
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
        /* ==================== PRINT & PDF STYLES (Unchanged) ==================== */
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

            .transaction-table thead {
                display: table-header-group;
            }

            tr.keep-together {
                page-break-inside: avoid;
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
            width: 100%;
            border-collapse: collapse;
            font-size: clamp(9px, 2vw, 11px);
            min-width: 343px;
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

        .status-pending {
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
            padding-top: 15px;
            font-style: italic;
            text-align: right;
            font-size: clamp(10px, 2vw, 11px);
            color: #333;
        }

        .calculation-note {
            margin-top: 15px;
            padding: 10px;
            background: #f8f8f8;
            border: 1px solid #ccc;
            font-size: clamp(9px, 1.8vw, 10px);
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
            .details-grid {
                grid-template-columns: 1fr;
            }

            .transaction-table {
                min-width: 343px;
                width: 100%
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
    </style>
</head>

<body>

    <!-- Action Buttons -->
    <div class="action-buttons no-print no-pdf" style="margin-bottom: 66px; @media (max-width: 480px) {margin-bottom: 10px;}">
        <!-- Period Filter -->
        <div class="period-filter no-print no-pdf">
            <div class="filter-row">
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
        <button onclick="window.print()" class="btn btn-print">
            Print Statement
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
                    Total Transactions: <?= count($validTransactions) ?>
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
                        <th class="amount">Amount (TK.)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($validTransactions)): ?>
                        <?php foreach ($validTransactions as $transaction): ?>
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
            <?php
                $totalPrice        = (float)($paymentSummary['total_price'] ?? 0);
                $bookingDiscount   = (float)($paymentSummary['discount_amount'] ?? 0);
                $bulkEmiDiscount   = (float)($paymentSummary['total_bulk_emi_discount'] ?? 0);
                $totalDiscounts    = $bookingDiscount + $bulkEmiDiscount;
                // Remaining balance should match Total Price - Total Paid - Discounts
                $remainingBalance   = (float)($paymentSummary['total_due'] ?? 0);
            ?>
            <table class="totals-table">
                <tr>
                    <td class="totals-label">Total Paid (Verified):</td>
                    <td class="totals-value">৳<?= number_format($actualPaidAmount, 2) ?></td>
                </tr>
            </table>

            <!-- Balance Summary -->
            <table class="totals-table" style="margin-top:5px;border-top:1px dashed #333;padding-top:8px;">
                <tr>
                    <td class="totals-label" style="font-weight:normal;">Total Price :</td>
                    <td class="totals-value">৳<?= number_format($totalPrice, 2) ?></td>
                </tr>
                <tr>
                    <td class="totals-label" style="font-weight:normal;">Less: Total Paid</td>
                    <td class="totals-value">(-)  ৳<?= number_format($actualPaidAmount, 2) ?></td>
                </tr>
                <?php if ($bookingDiscount > 0): ?>
                <tr>
                    <td class="totals-label" style="font-weight:normal;">Less: Booking Discount</td>
                    <td class="totals-value">(-)  ৳<?= number_format($bookingDiscount, 2) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($bulkEmiDiscount > 0): ?>
                <?php
                    // Get the bulk pay amount from the first discounted installment
                    $bulkPayAmount = 0;
                    foreach ($validTransactions as $t) {
                        if (!empty($t['bulk_pay_amount']) && $t['bulk_pay_amount'] > 0) {
                            $bulkPayAmount += (float)$t['bulk_pay_amount'];
                        }
                    }
                ?>
                <tr>
                    <td class="totals-label" style="font-weight:normal;">Less: Bulk EMI Discount<?= $bulkPayAmount > 0 ? ' (against bulk payment of ৳'.number_format($bulkPayAmount, 2).')' : '' ?></td>
                    <td class="totals-value">(-)  ৳<?= number_format($bulkEmiDiscount, 2) ?></td>
                </tr>
                <?php endif; ?>
                <tr class="grand-total">
                    <td class="totals-label">REMAINING BALANCE:</td>
                    <td class="totals-value">
                        <?php if ($remainingBalance <= 0): ?>
                            FULLY PAID
                        <?php else: ?>
                            ৳<?= number_format($remainingBalance, 2) ?>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <div class="amount-in-words">
                <strong>Amount in Words:</strong> <?= $amountInWords ?>
            </div>

            <div class="calculation-note">
                <strong>Note:</strong> This statement includes only verified transactions. Rejected and pending transactions are excluded.
                <?php if ($totalDiscounts > 0): ?>
                Booking Discount<?= $bookingDiscount > 0 ? ' (৳'.number_format($bookingDiscount,2).')' : '' ?><?= ($bookingDiscount > 0 && $bulkEmiDiscount > 0) ? ' and' : '' ?><?= $bulkEmiDiscount > 0 ? ' Bulk EMI Discount (৳'.number_format($bulkEmiDiscount,2).')' : '' ?>
                have been deducted from the remaining balance.
                <?php endif; ?>
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
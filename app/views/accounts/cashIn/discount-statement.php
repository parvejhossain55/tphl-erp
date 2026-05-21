<?php
// Disable layout for this page (standalone printable page)
$disable_layout = true;

// Gather discount data
$bookingDiscount   = (float)($paymentSummary['discount_amount'] ?? 0);
$totalBulkDiscount = (float)($paymentSummary['total_bulk_emi_discount'] ?? 0);
$grandTotalDiscount = $bookingDiscount + $totalBulkDiscount;
$agreementValue     = (float)($paymentSummary['agreement_value'] ?? $paymentSummary['total_price'] ?? 0);
$effectivePrice     = max(0, $agreementValue - $bookingDiscount);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discount Statement - <?= htmlspecialchars($clientDetails['client_name'] ?? 'N/A') ?></title>
    <style>
        @page { size: A4; margin: 10mm; }

        @media print {
            .no-print { display: none !important; }

            html, body {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                font-size: 8pt;
                background: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .statement-container {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 auto !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
            }

            /* Header compact */
            .statement-header { margin-bottom: 3mm !important; padding-bottom: 2mm !important; }
            .company-header { flex-direction: row !important; gap: 10px !important; }
            .company-name { font-size: 12pt !important; }
            .company-details { font-size: 7pt !important; }
            .statement-info { text-align: right !important; border-left: 1px solid #ccc !important; border-top: none !important; padding-left: 8px !important; padding-top: 0 !important; }
            .statement-title { font-size: 12pt !important; margin-bottom: 4px !important; }
            .statement-meta { font-size: 7pt !important; }

            /* Info grid compact */
            .info-grid { grid-template-columns: 1fr 1fr !important; gap: 3mm !important; margin-bottom: 3mm !important; }
            .info-box { padding: 4px 6px !important; font-size: 7pt !important; }
            .info-title { margin-bottom: 3px !important; padding-bottom: 2px !important; font-size: 7pt !important; }
            .info-row { margin-bottom: 1px !important; }

            /* Summary strip – force single row */
            .summary-strip {
                margin-bottom: 3mm !important;
                page-break-inside: avoid;
            }
            .summary-cell { padding: 4px 5px !important; }
            .summary-cell .s-label { font-size: 6pt !important; }
            .summary-cell .s-value { font-size: 10pt !important; }

            /* Table fit to page */
            .table-wrapper { overflow: visible !important; }

            .discount-table {
                font-size: 7.5pt !important;
                min-width: 0 !important;
                width: 100% !important;
                table-layout: fixed;
            }

            .discount-table th, .discount-table td {
                padding: 4px 5px !important;
                word-wrap: break-word;
                overflow-wrap: break-word;
            }

            .discount-table th { font-size: 7pt !important; white-space: normal !important; }
            .discount-table .amount { font-size: 7.5pt !important; }

            /* Prevent page breaks inside table */
            .discount-table { page-break-inside: auto; }
            .discount-table tr { page-break-inside: avoid; }
            .discount-table thead { display: table-header-group; }

            /* Footer compact */
            .statement-footer { font-size: 7pt !important; padding: 3px 5px !important; margin-top: 2mm !important; }
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f0f2f5;
            color: #000;
            line-height: 1.3;
            font-size: 11px;
            padding: 10px;
        }

        .statement-container {
            max-width: 1000px;
            background: #fff;
            padding: 15px;
            margin: 0 auto;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* ─── Header ─── */
        .statement-header {
            margin-bottom: 12px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .company-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }

        .company-info { flex: 2; }
        .company-name { font-size: 16px; font-weight: bold; text-transform: uppercase; margin-bottom: 3px; }
        .company-details { font-size: 10px; line-height: 1.4; }
        .statement-info { flex: 1; text-align: right; border-left: 1px solid #ccc; padding-left: 15px; }
        .statement-title { font-size: 16px; font-weight: bold; text-transform: uppercase; margin-bottom: 8px; }
        .statement-meta { font-size: 10px; }
        .meta-row { margin-bottom: 3px; }
        .meta-label { font-weight: bold; display: inline-block; min-width: 80px; }

        /* ─── Client + Property Info ─── */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 12px;
        }

        .info-box { border: 1px solid #000; padding: 10px; font-size: 11px; }
        .info-title { font-weight: bold; margin-bottom: 6px; padding-bottom: 5px; border-bottom: 1px solid #000; text-transform: uppercase; font-size: 11px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .info-val { font-weight: bold; text-align: right; }

        /* ─── Financial Summary Strip (TOP) ─── */
        .summary-strip {
            display: flex;
            flex-wrap: nowrap;
            margin-bottom: 15px;
            border: 2px solid #000;
        }

        .summary-cell {
            flex: 1;
            padding: 10px 12px;
            text-align: center;
            border-right: 1px solid #000;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 0;
        }

        .summary-cell:last-child { border-right: none; }

        .summary-cell .s-label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #555;
            margin-bottom: 4px;
        }

        .summary-cell .s-value {
            font-size: 15px;
            font-weight: bold;
            font-family: 'Courier New', monospace;
        }

        .summary-cell.highlight {
            /* Kept class but removed black bg per user request */
        }

        .summary-cell.highlight .s-label { color: #555; }
        .summary-cell.highlight .s-value { color: #000; }

        /* ─── Tables ─── */
        .section-title {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #000;
            font-size: 12px;
        }

        .table-wrapper { overflow-x: auto; margin-bottom: 20px; }

        .discount-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .discount-table th, .discount-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            vertical-align: middle;
        }

        .discount-table th {
            background: #d9e2f3;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            font-size: 9px;
            white-space: nowrap;
        }

        .discount-table tbody tr:nth-child(even) { background: #fafafa; }

        .discount-table .amount {
            text-align: right;
            font-family: 'Courier New', monospace;
        }
        
        .discount-table .center { text-align: center; }

        .discount-table .total-row {
            background: #d9e2f3 !important;
            font-weight: bold;
        }

        .discount-table .total-row td { padding: 8px 6px; border-top: 2px solid #000; }

        /* ─── Totals Section ─── */
        .totals-section {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 2px solid #000;
        }

        .totals-table { width: 100%; font-size: 11px; margin-bottom: 15px; }
        .totals-table td { padding: 5px 0; }
        .totals-label { text-align: right; padding-right: 15px; font-weight: bold; width: 70%; }
        .totals-value { text-align: right; font-family: 'Courier New', monospace; font-weight: bold; min-width: 150px; border-bottom: 1px solid #999; width: 30%; }
        .grand-total td { border-top: 2px solid #000; padding-top: 8px; font-size: 12px; }

        /* ─── Footer Note ─── */
        .statement-footer {
            margin-top: 15px;
            padding: 8px;
            background: #f8f8f8;
            border: 1px solid #ccc;
            font-size: 9px;
            line-height: 1.5;
        }

        /* ─── Action Buttons ─── */
        .action-buttons {
            position: sticky; top: 5px; z-index: 1000;
            display: flex; justify-content: end; gap: 8px;
            padding: 8px; margin-bottom: 15px; flex-wrap: wrap;
        }

        .btn {
            padding: 8px 14px; font-size: 12px; font-weight: 600;
            border: none; border-radius: 5px; cursor: pointer;
            display: inline-flex; align-items: center; gap: 5px;
            text-decoration: none; white-space: nowrap;
        }

        .btn:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-print { background: linear-gradient(135deg, #ff3c00, #cc2f00); color: #fff; }
        .btn-back { background: linear-gradient(135deg, #007bff, #0056b3); color: #fff; }

        /* ─── Mobile ─── */
        @media (max-width: 768px) {
            .company-header { flex-direction: column; }
            .statement-info { text-align: left; border-left: none; border-top: 1px solid #ccc; padding-left: 0; padding-top: 10px; }
            .info-grid { grid-template-columns: 1fr; }
            .summary-strip { flex-wrap: wrap; }
            .summary-cell { flex: 1 1 50%; border-bottom: 1px solid #000; }
            .summary-cell:nth-child(even) { border-right: none; }
            .summary-cell:last-child { flex: 1 1 100%; border-right: none; border-bottom: none; }
            .action-buttons { flex-direction: column; }
            .btn { width: 100%; justify-content: center; }
        }
    </style>
</head>

<body>

    <!-- Action Buttons -->
    <div class="action-buttons no-print">
        <button onclick="window.print()" class="btn btn-print">Print Statement</button>
        <a href="<?= $GLOBALS['base_url'] ?>/clients-cash-in-history?booking_id=<?= $clientDetails['booking_id'] ?>" class="btn btn-back">Back to History</a>
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
                    <div class="statement-title">Discount Statement</div>
                    <div class="statement-meta">
                        <div class="meta-row">
                            <span class="meta-label">Statement No:</span>
                            DISC-<?= str_pad($clientDetails['booking_id'], 6, '0', STR_PAD_LEFT) ?>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">Generated:</span>
                            <?= date('d-M-Y') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Client & Property Details -->
        <div class="info-grid">
            <div class="info-box">
                <div class="info-title">Client Information</div>
                <div class="info-row"><span>Name:</span><span class="info-val"><?= htmlspecialchars($clientDetails['client_name'] ?? 'N/A') ?></span></div>
                <div class="info-row"><span>Phone:</span><span class="info-val"><?= htmlspecialchars($clientDetails['phone'] ?? 'N/A') ?></span></div>
                <div class="info-row"><span>Email:</span><span class="info-val"><?= htmlspecialchars($clientDetails['email'] ?? 'N/A') ?></span></div>
            </div>

            <div class="info-box">
                <div class="info-title">Property Details</div>
                <div class="info-row"><span>Project:</span><span class="info-val"><?= htmlspecialchars($clientDetails['project_name'] ?? 'N/A') ?></span></div>
                <div class="info-row"><span>Unit:</span><span class="info-val"><?= htmlspecialchars($clientDetails['unit_name'] ?? 'N/A') ?></span></div>
                <div class="info-row"><span>Floor:</span><span class="info-val"><?= htmlspecialchars($clientDetails['flat_no'] ?? 'N/A') ?></span></div>
            </div>
        </div>

        <!-- ═══ FINANCIAL SUMMARY (TOP) ═══ -->
        <div class="summary-strip">
            <div class="summary-cell">
                <div class="s-label">Booking Discount</div>
                <div class="s-value">৳<?= number_format($bookingDiscount, 2) ?></div>
            </div>
            <div class="summary-cell">
                <div class="s-label">Bulk EMI Discounts</div>
                <div class="s-value">৳<?= number_format($totalBulkDiscount, 2) ?></div>
            </div>
            <div class="summary-cell highlight" style="flex: 1.5;">
                <div class="s-label">Grand Total Discount</div>
                <div class="s-value">৳<?= number_format($grandTotalDiscount, 2) ?></div>
            </div>
        </div>

        <!-- Section 1: Booking-Level Discount -->
        <div style="margin-bottom: 20px;">
            <div class="section-title">Booking-Level Discount</div>

            <?php if ($bookingDiscount > 0): ?>
            <div class="table-wrapper">
                <table class="discount-table">
                    <thead>
                        <tr>
                            <th style="text-align: left;">Description</th>
                            <th class="amount" style="width: 20%;">Agreement Value</th>
                            <th class="amount" style="width: 20%;">Discount</th>
                            <th class="amount" style="width: 20%;">Effective Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                Flat booking discount on
                                <strong><?= htmlspecialchars($clientDetails['unit_name'] ?? '') ?></strong>
                                — <?= htmlspecialchars($clientDetails['project_name'] ?? '') ?>
                            </td>
                            <td class="amount">৳<?= number_format($agreementValue, 2) ?></td>
                            <td class="amount">(-) ৳<?= number_format($bookingDiscount, 2) ?></td>
                            <td class="amount">৳<?= number_format($effectivePrice, 2) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="statement-footer" style="text-align: center;">
                No booking-level discount applied for this client.
            </div>
            <?php endif; ?>
        </div>

        <!-- Section 2: Bulk EMI Payment Discounts -->
        <div style="margin-bottom: 20px;">
            <div class="section-title">
                Bulk EMI Payment Discounts
                <?php if (!empty($bulkDiscountTxns)): ?>
                <span style="float: right; font-weight: normal; font-size: 9px; margin-top: 2px;">
                    Total Entries: <?= count($bulkDiscountTxns) ?>
                </span>
                <?php endif; ?>
            </div>

            <?php if (!empty($bulkDiscountTxns)): ?>
            <div class="table-wrapper">
                <table class="discount-table">
                    <thead>
                        <tr>
                            <th class="center" style="width: 30px;">#</th>
                            <th class="center" style="width: 80px;">Date</th>
                            <th style="text-align: left;">EMI No. / Term</th>
                            <th class="amount">Payment Amount</th>
                            <th class="amount">Bulk Pay Against</th>
                            <th class="amount">Discount Given</th>
                            <th class="center">Added By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bulkDiscountTxns as $idx => $dtxn): ?>
                        <tr>
                            <td class="center"><?= $idx + 1 ?></td>
                            <td class="center"><?= date('d-M-Y', strtotime($dtxn['payment_date'])) ?></td>
                            <td>
                                <?php if (!empty($dtxn['emi_no'])): ?>
                                    EMI #<?= $dtxn['emi_no'] ?>
                                <?php else: ?>
                                    <?= strtoupper(str_replace('_', ' ', $dtxn['payment_type'])) ?>
                                <?php endif; ?>
                            </td>
                            <td class="amount">৳<?= number_format($dtxn['amount'], 2) ?></td>
                            <td class="amount">
                                <?php if ($dtxn['bulk_pay_amount'] > 0): ?>
                                    ৳<?= number_format($dtxn['bulk_pay_amount'], 2) ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="amount">৳<?= number_format($dtxn['bulk_emi_discount'], 2) ?></td>
                            <td class="center"><?= htmlspecialchars($dtxn['added_by_name'] ?? 'System') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="5" style="text-align: right;">TOTAL BULK EMI DISCOUNT</td>
                            <td class="amount">৳<?= number_format($totalBulkDiscount, 2) ?></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="statement-footer" style="text-align: center;">
                No bulk EMI discounts have been applied for this client.
            </div>
            <?php endif; ?>
        </div>

        <!-- Grand Totals -->
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td class="totals-label" style="font-weight:normal;">Agreement Value:</td>
                    <td class="totals-value">৳<?= number_format($agreementValue, 2) ?></td>
                </tr>
                <?php if ($bookingDiscount > 0): ?>
                <tr>
                    <td class="totals-label" style="font-weight:normal;">Less: Booking Discount</td>
                    <td class="totals-value">(-) ৳<?= number_format($bookingDiscount, 2) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($totalBulkDiscount > 0): ?>
                <tr>
                    <td class="totals-label" style="font-weight:normal;">Less: Bulk EMI Discount</td>
                    <td class="totals-value">(-) ৳<?= number_format($totalBulkDiscount, 2) ?></td>
                </tr>
                <?php endif; ?>
                <tr class="grand-total">
                    <td class="totals-label">TOTAL DISCOUNT:</td>
                    <td class="totals-value">৳<?= number_format($grandTotalDiscount, 2) ?></td>
                </tr>
            </table>

            <div class="statement-footer">
                <strong>Note:</strong> This statement reflects all discounts applied to this booking.
                <?php if ($bookingDiscount > 0): ?>
                    A booking-level discount of ৳<?= number_format($bookingDiscount, 2) ?> was applied to reduce the agreement value.
                <?php endif; ?>
                <?php if ($totalBulkDiscount > 0): ?>
                    Bulk EMI payment discounts totaling ৳<?= number_format($totalBulkDiscount, 2) ?> were given for advance multi-installment payments.
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>

</html>

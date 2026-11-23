<?php
// Disable layout for clean standalone printable view
$disable_layout = true;

if (!isset($summaryData) || empty($summaryData)) {
    if (!empty($bookingId)) {
        if (!class_exists('AccountClient')) {
            require_once dirname(dirname(dirname(__DIR__))) . '/models/AccountClient.php';
        }
        $accountModel = new AccountClient($GLOBALS['db'] ?? ($this->db ?? null));
        $summaryData = $accountModel->getDatewiseBatchPaymentSummary($bookingId);
    }
}

if (!$summaryData || empty($summaryData['client_details'])) {
    die("Booking details not found or invalid.");
}

$base_url = $GLOBALS['base_url'] ?? '';

$clientDetails       = $summaryData['client_details'];
$totalPrice          = (float)($summaryData['total_price'] ?? 0);
$totalPaid           = (float)($summaryData['total_paid'] ?? 0);
$totalDiscount       = (float)($summaryData['total_discount'] ?? 0);
$totalDue            = (float)($summaryData['total_due'] ?? 0);
$askedAmount         = (float)($summaryData['asked_amount'] ?? 0);
$activeEmiCount      = (int)($summaryData['active_emi_count'] ?? 0);
$currentDue          = (float)($summaryData['current_due'] ?? 0);
$batches             = $summaryData['batches'] ?? [];

$clientName  = !empty($clientDetails['client_name']) ? $clientDetails['client_name'] : 'N/A';
if (!empty($clientDetails['2nd_lead_owner_name'])) {
    $clientName .= ' & ' . trim($clientDetails['2nd_lead_owner_name']);
}
$clientPhone = !empty($clientDetails['phone']) ? $clientDetails['phone'] : (!empty($clientDetails['phone_number']) ? $clientDetails['phone_number'] : 'N/A');
if (!empty($clientDetails['2nd_owner_phone'])) {
    $clientPhone .= ', ' . trim($clientDetails['2nd_owner_phone']);
}
$clientEmail = !empty($clientDetails['email']) ? $clientDetails['email'] : 'N/A';
$projectName = !empty($clientDetails['project_name']) ? $clientDetails['project_name'] : 'N/A';
$unitNo      = !empty($clientDetails['flat_no']) ? $clientDetails['flat_no'] : (!empty($clientDetails['unit_name']) ? $clientDetails['unit_name'] : 'N/A');
$flatSize    = !empty($clientDetails['flat_size']) ? number_format($clientDetails['flat_size'], 0) . ' sft' : 'N/A';
$bookingDate = !empty($clientDetails['booking_date']) ? date('d M, Y', strtotime($clientDetails['booking_date'])) : 'N/A';
$agentName   = !empty($clientDetails['agent_name']) ? $clientDetails['agent_name'] : 'System';

// Helper for amount in words
$amountInWords = function_exists('amountInWords') ? amountInWords($totalPaid) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Date-Wise Payment Statement - <?= htmlspecialchars($clientName) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 12mm 12mm 12mm;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #1e293b;
            background: #f1f5f9;
            padding: 20px;
        }

        .statement-sheet {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        /* Top Action Bar (hidden in print) */
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 12px 18px;
            background: #ffffff;
            max-width: 900px;
            margin: 0 auto 16px auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: #2563eb;
            color: #ffffff;
        }
        .btn-primary:hover { background: #1d4ed8; }

        .btn-outline {
            background: #ffffff;
            color: #475569;
            border-color: #cbd5e1;
        }
        .btn-outline:hover { background: #f8fafc; color: #0f172a; }

        /* Company Header */
        .company-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 14px;
            margin-bottom: 16px;
        }

        .brand-title {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .brand-tagline {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }

        .header-doc-title {
            text-align: right;
        }

        .doc-badge {
            display: inline-block;
            background: #0f172a;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .doc-date {
            font-size: 10px;
            color: #64748b;
            margin-top: 4px;
        }

        /* Client Info Cards */
        .client-info-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        .info-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
        }

        .info-box-title {
            font-size: 10px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }

        .info-row {
            display: flex;
            margin-bottom: 4px;
            font-size: 11px;
        }

        .info-row:last-child { margin-bottom: 0; }

        .info-label {
            width: 110px;
            color: #64748b;
            font-weight: 600;
        }

        .info-value {
            flex: 1;
            color: #0f172a;
            font-weight: 600;
        }

        /* Financial Strip */
        .summary-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .summary-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px;
            text-align: center;
        }

        .summary-card.highlight-price {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .summary-card.highlight-paid {
            background: #ecfdf5;
            border-color: #a7f3d0;
        }

        .summary-card.highlight-due {
            background: #fff1f2;
            border-color: #fecdd3;
        }

        .summary-card.highlight-discount {
            background: #fefce8;
            border-color: #fef08a;
        }

        .summary-card.highlight-current-due {
            background: #fffbeb;
            border-color: #fde68a;
        }

        .sc-label {
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 3px;
        }

        .sc-value {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            font-family: monospace;
        }

        .highlight-paid .sc-value { color: #059669; }
        .highlight-due .sc-value { color: #dc2626; }
        .highlight-discount .sc-value { color: #d97706; }
        .highlight-current-due .sc-value { color: #b45309; }

        /* Main Table */
        .statement-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
            margin-bottom: 18px;
        }

        .statement-table th {
            background: #0f172a;
            color: #ffffff;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9.5px;
            letter-spacing: 0.4px;
            padding: 8px 8px;
            border: 1px solid #0f172a;
        }

        .statement-table td {
            padding: 7px 8px;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
        }

        .statement-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .statement-table tbody tr:hover {
            background: #f1f5f9;
        }

        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-nowrap { white-space: nowrap; }

        .amt-paid {
            font-weight: 700;
            color: #059669;
            font-family: monospace;
            font-size: 11px;
        }

        .amt-due {
            font-weight: 700;
            color: #dc2626;
            font-family: monospace;
            font-size: 11px;
        }

        .amt-ref {
            font-family: monospace;
            color: #475569;
        }

        /* Totals Row */
        .statement-table tfoot td {
            background: #f1f5f9;
            font-weight: 700;
            padding: 8px;
            border-top: 2px solid #0f172a;
            border-bottom: 2px solid #0f172a;
        }

        /* In words banner */
        .in-words-box {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 10.5px;
            color: #334155;
            margin-bottom: 24px;
        }

        .in-words-box strong { color: #0f172a; }

        /* Signatures */
        .signature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 50px;
            padding-top: 10px;
        }

        .sig-box {
            text-align: center;
        }

        .sig-line {
            width: 80%;
            margin: 0 auto 6px auto;
            border-top: 1px solid #94a3b8;
        }

        .sig-title {
            font-size: 10.5px;
            font-weight: 700;
            color: #0f172a;
        }

        .sig-sub {
            font-size: 9.5px;
            color: #64748b;
        }

        .statement-footer-note {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 9.5px;
            color: #64748b;
            display: flex;
            justify-content: space-between;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .action-bar {
                display: none !important;
            }
            .statement-sheet {
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
            .statement-table th {
                background: #0f172a !important;
                color: #ffffff !important;
            }
            .highlight-paid { background: #ecfdf5 !important; }
            .highlight-due { background: #fff1f2 !important; }
            .highlight-discount { background: #fefce8 !important; }
            .statement-table tbody tr:nth-child(even) {
                background: #f8fafc !important;
            }
        }
    </style>
</head>
<body>

    <!-- Non-printed Action Bar -->
    <div class="action-bar">
        <div>
            <a href="javascript:history.back()" class="btn btn-outline">
                <i class="fa-solid fa-arrow-left"></i> Back
            </a>
        </div>
        <div style="display:flex; gap:8px;">
            <a href="<?= $GLOBALS['base_url'] ?>/client-statement/<?= (int)$clientDetails['booking_id'] ?>" target="_blank" class="btn btn-outline">
                <i class="fa-solid fa-file-lines"></i> Detailed Statement
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fa-solid fa-print"></i> Print Date-Wise Statement
            </button>
        </div>
    </div>

    <!-- Main Printable Sheet -->
    <div class="statement-sheet">

        <!-- Company Header -->
        <div class="company-header">
            <div>
                <div class="brand-title">The Premium Homes Ltd.</div>
                <div class="brand-tagline">Land View Commercial Center, 9th Floor, 28 Gulshan North C/A, Gulshan Circle-2, Dhaka</div>
                <div class="brand-tagline">Phone: +880 9611 678 678 | Email: info@thepremiumhomes.com</div>
            </div>
            <div class="header-doc-title">
                <div class="doc-badge">Date-Wise Payment Statement</div>
                <div class="doc-date">Generated on: <strong><?= date('d M, Y • h:i A') ?></strong></div>
            </div>
        </div>

        <!-- Client & Project Details -->
        <div class="client-info-grid">
            <div class="info-box">
                <div class="info-box-title"><i class="fa-solid fa-user me-1"></i> Client Information</div>
                <div class="info-row">
                    <div class="info-label">Client Name:</div>
                    <div class="info-value"><?= htmlspecialchars($clientName) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Contact Phone:</div>
                    <div class="info-value"><?= htmlspecialchars($clientPhone) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email Address:</div>
                    <div class="info-value"><?= htmlspecialchars($clientEmail) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Booking Date:</div>
                    <div class="info-value"><?= $bookingDate ?></div>
                </div>
            </div>

            <div class="info-box">
                <div class="info-box-title"><i class="fa-solid fa-building me-1"></i> Property & Sales Details</div>
                <div class="info-row">
                    <div class="info-label">Project Name:</div>
                    <div class="info-value"><?= htmlspecialchars($projectName) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Unit / Flat No.:</div>
                    <div class="info-value"><?= htmlspecialchars($unitNo) ?> (<?= htmlspecialchars($flatSize) ?>)</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Sales Officer:</div>
                    <div class="info-value"><?= htmlspecialchars($agentName) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Booking ID:</div>
                    <div class="info-value">#<?= (int)$clientDetails['booking_id'] ?></div>
                </div>
            </div>
        </div>

        <!-- Financial Summary Strip -->
        <div class="summary-strip" style="<?= $totalDiscount > 0 ? 'grid-template-columns: repeat(5, 1fr);' : '' ?>">
            <div class="summary-card highlight-price">
                <div class="sc-label">Total Agreement Value</div>
                <div class="sc-value">৳<?= number_format($totalPrice, 2) ?></div>
            </div>
            <div class="summary-card highlight-paid">
                <div class="sc-label">Total Amount Paid</div>
                <div class="sc-value">৳<?= number_format($totalPaid, 2) ?></div>
            </div>
            <div class="summary-card highlight-due">
                <div class="sc-label">Total Due</div>
                <div class="sc-value">
                    <?php if ($totalDue <= 0.01): ?>
                        <span style="color:#059669;">FULLY PAID</span>
                    <?php else: ?>
                        ৳<?= number_format($totalDue, 2) ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($totalDiscount > 0): ?>
            <div class="summary-card highlight-discount">
                <div class="sc-label">Total Discount</div>
                <div class="sc-value">৳<?= number_format($totalDiscount, 2) ?></div>
            </div>
            <?php endif; ?>
            <div class="summary-card highlight-current-due">
                <div class="sc-label">Current Due</div>
                <div class="sc-value">
                    <?php if ($currentDue <= 0.01): ?>
                        <span style="color:#059669;">CLEAR</span>
                    <?php else: ?>
                        ৳<?= number_format($currentDue, 2) ?>
                    <?php endif; ?>
                </div>
                <div style="font-size: 9px; color: #64748b; margin-top: 2px;">Asked: ৳<?= number_format($askedAmount, 2) ?></div>
            </div>
        </div>

        <!-- Date-Wise Unified Payments Table -->
        <table class="statement-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 32px;">#</th>
                    <th style="width: 85px;">Payment Date</th>
                    <th style="width: 95px;">Receipt / Ref</th>
                    <th style="width: 75px;">Method</th>
                    <th>Bank &amp; Account Details</th>
                    <th style="width: 90px;">Cheque / Txn</th>
                    <th style="width: 105px;">Added By</th>
                    <th class="text-end" style="width: 110px;">Paid Amount (৳)</th>
                    <th class="text-end" style="width: 120px;">Remaining Due (৳)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($batches)): ?>
                    <tr>
                        <td colspan="9" class="text-center" style="padding: 25px; color: #94a3b8;">
                            <i class="fa-solid fa-receipt fa-2x mb-2 d-block"></i>
                            No payment transactions recorded for this client.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $sl = 1;
                    foreach ($batches as $b): 
                    ?>
                        <tr>
                            <td class="text-center text-muted fw-bold"><?= $sl++ ?></td>
                            <td class="text-nowrap fw-semibold">
                                <i class="fa-regular fa-calendar text-muted me-1" style="font-size:9.5px;"></i><?= $b['formatted_date'] ?>
                            </td>
                            <td class="text-nowrap font-monospace fw-bold" style="color: #4f46e5;">
                                <?= htmlspecialchars($b['receipt_no']) ?>
                            </td>
                            <td>
                                <span style="font-weight: 700; text-transform: uppercase; font-size: 9.5px;">
                                    <?= htmlspecialchars($b['payment_method']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($b['bank_name']) && strtolower($b['bank_name']) !== 'cash'): ?>
                                    <div class="fw-semibold text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($b['bank_name']) ?>">
                                        <?= htmlspecialchars($b['bank_name']) ?>
                                    </div>
                                    <?php if (!empty($b['bank_account_no'])): ?>
                                        <div style="font-size: 9px; color: #64748b;">A/C: <?= htmlspecialchars($b['bank_account_no']) ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #64748b;">Cash Office</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-nowrap">
                                <?php if (!empty($b['cheque_no'])): ?>
                                    <span title="Cheque No: <?= htmlspecialchars($b['cheque_no']) ?>">Chq: <?= htmlspecialchars($b['cheque_no']) ?></span>
                                <?php elseif (!empty($b['transaction_ref'])): ?>
                                    <span style="font-family:monospace; font-size:9.5px;"><?= htmlspecialchars($b['transaction_ref']) ?></span>
                                <?php else: ?>
                                    <span style="color: #cbd5e1;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark text-truncate" style="max-width: 105px;" title="<?= htmlspecialchars($b['created_by_name']) ?>">
                                    <?= htmlspecialchars($b['created_by_name']) ?>
                                </div>
                            </td>
                            <td class="text-end amt-paid">
                                ৳<?= number_format((float)$b['total_paid'], 2) ?>
                                <?php if ((float)$b['total_discount'] > 0): ?>
                                    <div style="font-size: 9px; color: #d97706; font-weight: normal;">
                                        + Disc: ৳<?= number_format((float)$b['total_discount'], 2) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end amt-due">
                                ৳<?= number_format((float)$b['remaining_due'], 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7" class="text-end" style="font-size: 10px; text-transform: uppercase;">
                        TOTAL PAID AMOUNT ACCROSS ALL DATES:
                    </td>
                    <td class="text-end" style="color: #059669; font-size: 12px; font-family: monospace;">
                        ৳<?= number_format($totalPaid, 2) ?>
                    </td>
                    <td class="text-end" style="color: <?= $totalDue > 0 ? '#dc2626' : '#059669' ?>; font-size: 12px; font-family: monospace;">
                        ৳<?= number_format($totalDue, 2) ?>
                    </td>
                </tr>
            </tfoot>
        </table>

        <!-- Amount In Words -->
        <?php if (!empty($amountInWords)): ?>
        <div class="in-words-box">
            <strong>In Words (Total Paid):</strong> <?= htmlspecialchars($amountInWords) ?> Only.
        </div>
        <?php endif; ?>

        <!-- Signatures -->
        <div class="signature-grid">
            <div class="sig-box">
                <div class="sig-line"></div>
                <div class="sig-title">Prepared By</div>
                <div class="sig-sub">Accounts Officer</div>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <div class="sig-title">Verified &amp; Checked By</div>
                <div class="sig-sub">Senior Accountant / Manager</div>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <div class="sig-title">Authorized Signature</div>
                <div class="sig-sub">The Premium Homes Ltd.</div>
            </div>
        </div>

        <div class="statement-footer-note">
            <span>* This is a computer generated date-wise payment summary statement. Each entry represents total collection made on that date/transaction.</span>
            <span>Page 1 of 1</span>
        </div>

    </div>

</body>
</html>

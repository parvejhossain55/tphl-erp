<?php
/**
 * Official Printable Client EMI Maturity Schedule
 * Route: /client-emi-schedule-print/{bookingId}
 */

$clientName   = htmlspecialchars($clientDetails['client_name'] ?? 'Client');
$projectName  = htmlspecialchars($clientDetails['project_name'] ?? 'Project');
$unitName     = htmlspecialchars($clientDetails['unit_name'] ?? 'Unit');
$flatNo       = htmlspecialchars($clientDetails['flat_no'] ?? ($clientDetails['shop_no'] ?? 'N/A'));
$phone        = htmlspecialchars($clientDetails['phone'] ?? 'N/A');
$bookingDate  = !empty($clientDetails['booking_date']) ? date('d M, Y', strtotime($clientDetails['booking_date'])) : 'N/A';
$salesAgent   = htmlspecialchars($clientDetails['agent_name'] ?? 'N/A');
$bookingId    = (int)($clientDetails['booking_id'] ?? $bookingId);

$totalPrice   = (float)($paymentSummary['total_price'] ?? 0);
$perEmiAmt    = (float)($paymentSummary['per_emi_amount'] ?? ($clientDetails['per_emi_amount'] ?? 0));
$totalEmi     = (int)($paymentSummary['total_emi'] ?? (count($virtualEmiSchedule) > 0 ? count($virtualEmiSchedule) : 36));

$totalScheduled = 0.0;
$totalPaid = 0.0;
$totalDue = 0.0;
$paidCount = 0;
$overdueCount = 0;

foreach ($virtualEmiSchedule as $item) {
    $totalScheduled += (float)($item['amount'] ?? 0);
    $totalPaid += (float)($item['paid_amount'] ?? 0);
    $totalDue += (float)($item['due_amount'] ?? 0);
    if (!empty($item['is_locked']) || ($item['status'] ?? '') === 'paid') {
        $paidCount++;
    }
    if (($item['maturity_status'] ?? '') === 'due') {
        $overdueCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EMI Schedule - <?= $clientName ?> (Booking #<?= $bookingId ?>)</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm 6mm;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            font-size: 11px;
            line-height: 1.35;
            padding: 15px;
        }

        .no-print-bar {
            background: #1e293b;
            color: #ffffff;
            padding: 10px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 8px;
            margin-bottom: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-print {
            background: #2563eb;
            color: #fff;
        }
        .btn-print:hover {
            background: #1d4ed8;
        }

        .btn-close-tab {
            background: #475569;
            color: #fff;
        }
        .btn-close-tab:hover {
            background: #334155;
        }

        .sheet-container {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }

        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 12px;
        }

        .company-info h2 {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .company-info p {
            font-size: 10.5px;
            color: #475569;
            margin-bottom: 1px;
        }

        .doc-title-badge {
            background: #0f172a;
            color: #ffffff;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            text-align: right;
        }

        .client-info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 12px;
        }

        .info-cell .label {
            font-size: 9.5px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 600;
            display: block;
            margin-bottom: 1px;
        }

        .info-cell .value {
            font-size: 11.5px;
            font-weight: 700;
            color: #0f172a;
        }

        .kpi-summary-strip {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 8px;
            margin-bottom: 14px;
        }

        .kpi-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 10px;
            text-align: center;
        }

        .kpi-card .lbl {
            font-size: 10px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .kpi-card .val {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
        }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
            margin-bottom: 16px;
        }

        .schedule-table th,
        .schedule-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 7px;
            text-align: left;
        }

        .schedule-table th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9.5px;
            letter-spacing: 0.3px;
        }

        .schedule-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .text-end { text-align: right !important; }
        .text-center { text-align: center !important; }
        .fw-bold { font-weight: 700; }

        .badge-status {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9.5px;
            font-weight: 700;
        }

        .badge-paid-on-time { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-paid-late    { background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; }
        .badge-matured      { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .badge-due          { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .badge-pending      { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-future       { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

        .signature-section {
            margin-top: 30px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            padding-top: 10px;
        }

        .sig-block {
            text-align: center;
        }

        .sig-line {
            border-top: 1px dashed #64748b;
            margin-top: 40px;
            margin-bottom: 4px;
        }

        .sig-title {
            font-size: 10px;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
        }

        .footer-note {
            margin-top: 16px;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            display: flex;
            justify-content: space-between;
            color: #64748b;
            font-size: 9px;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .no-print-bar {
                display: none !important;
            }
            .sheet-container {
                box-shadow: none;
                border: none;
                padding: 0;
                max-width: 100%;
            }
            .schedule-table tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <div>
            <strong><i class="fa-solid fa-file-invoice me-1"></i> Client EMI Maturity Schedule</strong>
            <span class="ms-2 opacity-75">Booking #<?= $bookingId ?> — <?= $clientName ?></span>
        </div>
        <div style="display: flex; gap: 8px;">
            <button class="btn btn-print" onclick="window.print()">
                <i class="fa-solid fa-print"></i> Print / Save as PDF
            </button>
            <button class="btn btn-close-tab" onclick="window.close()">
                <i class="fa-solid fa-xmark"></i> Close
            </button>
        </div>
    </div>

    <div class="sheet-container">

        <!-- Header -->
        <div class="doc-header">
            <div class="company-info">
                <h2>THE PREMIUM HOMES LIMITED</h2>
                <p>Land View Commercial Center, 9th Floor, 28 Gulshan North C/A, Gulshan Circle-2, Dhaka</p>
                <p>Phone: +8801958253300 | Email: info@thepremiumhomes.com | Web: www.thepremiumhomes.com</p>
            </div>
            <div class="doc-title-badge">
                <div>EMI Maturity Schedule</div>
                <div style="font-size: 10px; font-weight: normal; opacity: 0.85;">Booking #<?= $bookingId ?></div>
            </div>
        </div>

        <!-- Client Info Grid -->
        <div class="client-info-grid">
            <div class="info-cell">
                <span class="label">Client Name</span>
                <span class="value"><?= $clientName ?></span>
            </div>
            <div class="info-cell">
                <span class="label">Contact Phone</span>
                <span class="value"><?= $phone ?></span>
            </div>
            <div class="info-cell">
                <span class="label">Project Name</span>
                <span class="value"><?= $projectName ?></span>
            </div>
            <div class="info-cell">
                <span class="label">Flat / Unit No</span>
                <span class="value"><?= $unitName ?><?= $flatNo !== 'N/A' ? ' (' . $flatNo . ')' : '' ?></span>
            </div>
            <div class="info-cell">
                <span class="label">Booking Date</span>
                <span class="value"><?= $bookingDate ?></span>
            </div>
            <div class="info-cell">
                <span class="label">Sales Agent</span>
                <span class="value"><?= $salesAgent ?></span>
            </div>
            <div class="info-cell">
                <span class="label">Total Plan</span>
                <span class="value"><?= $totalEmi ?> Installments</span>
            </div>
            <div class="info-cell">
                <span class="label">Monthly Installment</span>
                <span class="value">TK. <?= number_format($perEmiAmt, 2) ?></span>
            </div>
        </div>

        <!-- Financial Summary KPI Strip -->
        <div class="kpi-summary-strip">
            <div class="kpi-card">
                <div class="lbl">Total Agreement</div>
                <div class="val">TK. <?= number_format($totalPrice, 2) ?></div>
            </div>
            <div class="kpi-card">
                <div class="lbl">Total Scheduled</div>
                <div class="val">TK. <?= number_format($totalScheduled, 2) ?></div>
            </div>
            <div class="kpi-card">
                <div class="lbl">Total Paid</div>
                <div class="val" style="color: #16a34a;">TK. <?= number_format($totalPaid, 2) ?></div>
            </div>
            <div class="kpi-card">
                <div class="lbl">Remaining Due</div>
                <div class="val" style="color: <?= $totalDue > 0 ? '#dc2626' : '#16a34a' ?>;">TK. <?= number_format($totalDue, 2) ?></div>
            </div>
            <div class="kpi-card">
                <div class="lbl">Paid / Overdue</div>
                <div class="val" style="font-size: 11.5px;">
                    <span style="color: #16a34a;"><?= $paidCount ?> Paid</span> / 
                    <span style="color: <?= $overdueCount > 0 ? '#dc2626' : '#64748b' ?>;"><?= $overdueCount ?> Due</span>
                </div>
            </div>
        </div>

        <!-- EMI Maturity Schedule Table -->
        <table class="schedule-table">
            <thead>
                <tr>
                    <th style="width: 60px;" class="text-center">EMI #</th>
                    <th style="width: 130px;">Scheduled Due Date</th>
                    <th class="text-end" style="width: 100px;">Amount (TK)</th>
                    <th class="text-end" style="width: 100px;">Paid (TK)</th>
                    <th class="text-end" style="width: 100px;">Due (TK)</th>
                    <th style="width: 80px;" class="text-center">Status</th>
                    <th style="width: 120px;" class="text-center">Maturity Status</th>
                    <th>Payment Date & Timing</th>
                    <th style="width: 110px;">Fixed By</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($virtualEmiSchedule as $emi): 
                    $matClass = 'badge-future';
                    $matLabel = ucfirst($emi['maturity_status'] ?? 'Future');

                    switch ($emi['maturity_status'] ?? '') {
                        case 'paid_on_time':
                            $matClass = 'badge-paid-on-time';
                            $matLabel = 'Paid On Time ✓';
                            break;
                        case 'paid_late':
                            $matClass = 'badge-paid-late';
                            $matLabel = 'Paid Late' . (!empty($emi['delay_days']) ? ' (' . $emi['delay_days'] . 'd delay)' : '');
                            break;
                        case 'matured':
                            $matClass = 'badge-matured';
                            $matLabel = 'Matured';
                            break;
                        case 'due':
                            $matClass = 'badge-due';
                            $matLabel = 'Overdue' . (!empty($emi['overdue_days']) ? ' (' . $emi['overdue_days'] . 'd)' : '');
                            break;
                        case 'pending':
                            $matClass = 'badge-pending';
                            $matLabel = 'Current Month';
                            break;
                        case 'unscheduled':
                            $matClass = 'badge-future';
                            $matLabel = 'Not Scheduled';
                            break;
                        case 'future':
                        default:
                            $matClass = 'badge-future';
                            $matLabel = 'Future';
                            break;
                    }
                ?>
                <tr>
                    <td class="text-center fw-bold">
                        #<?= $emi['emi_no'] ?>
                        <?php if (!empty($emi['is_virtual']) && !empty($emi['due_date'])): ?>
                            <span style="font-size: 8.5px; color: #94a3b8; font-weight: normal;">(Proj.)</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($emi['due_date'])): ?>
                            <strong><?= date('d M, Y', strtotime($emi['due_date'])) ?></strong>
                            <?php if (!empty($emi['is_date_custom'])): ?>
                                <span style="font-size: 8.5px; background: #e0f2fe; color: #0284c7; padding: 1px 4px; border-radius: 3px; font-weight: 600;">CED</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="font-size: 9.5px; color: #64748b; font-style: italic;">Not Scheduled</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end fw-bold"><?= number_format($emi['amount'], 2) ?></td>
                    <td class="text-end" style="color: #16a34a;"><?= number_format($emi['paid_amount'], 2) ?></td>
                    <td class="text-end" style="color: <?= $emi['due_amount'] > 0 ? '#dc2626' : '#16a34a' ?>;"><?= number_format($emi['due_amount'], 2) ?></td>
                    <td class="text-center">
                        <span style="text-transform: capitalize; font-weight: 600; color: <?= $emi['status'] === 'paid' ? '#16a34a' : ($emi['status'] === 'partially_paid' ? '#d97706' : '#64748b') ?>;">
                            <?= htmlspecialchars(str_replace('_', ' ', $emi['status'])) ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge-status <?= $matClass ?>"><?= $matLabel ?></span>
                    </td>
                    <td>
                        <?php if (!empty($emi['last_payment_date'])): ?>
                            <span>Cleared: <?= date('d M, Y', strtotime($emi['last_payment_date'])) ?></span>
                        <?php elseif (!empty($emi['notes'])): ?>
                            <span style="color: #64748b; font-style: italic;"><?= htmlspecialchars($emi['notes']) ?></span>
                        <?php else: ?>
                            <span style="color: #94a3b8;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($emi['fixed_by_name'])): ?>
                            <span title="Fixed on <?= !empty($emi['fixed_at']) ? date('d M, Y', strtotime($emi['fixed_at'])) : '' ?>">
                                <?= htmlspecialchars($emi['fixed_by_name']) ?>
                            </span>
                        <?php else: ?>
                            <span style="color: #94a3b8;">System (Auto)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: #f1f5f9; font-weight: 800;">
                    <td colspan="2" class="text-end">TOTAL:</td>
                    <td class="text-end">TK. <?= number_format($totalScheduled, 2) ?></td>
                    <td class="text-end" style="color: #16a34a;">TK. <?= number_format($totalPaid, 2) ?></td>
                    <td class="text-end" style="color: <?= $totalDue > 0 ? '#dc2626' : '#16a34a' ?>;">TK. <?= number_format($totalDue, 2) ?></td>
                    <td colspan="4"></td>
                </tr>
            </tfoot>
        </table>

        <!-- Signatures Section -->
        <div class="signature-section">
            <div class="sig-block">
                <div class="sig-line"></div>
                <div class="sig-title">Prepared By</div>
            </div>
            <div class="sig-block">
                <div class="sig-line"></div>
                <div class="sig-title">Customer Experience (CED)</div>
            </div>
            <div class="sig-block">
                <div class="sig-line"></div>
                <div class="sig-title">Verified By (Accounts)</div>
            </div>
            <div class="sig-block">
                <div class="sig-line"></div>
                <div class="sig-title">Client's Signature</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer-note">
            <span>TPHL-ERP • Official Client Statement System</span>
            <span>Printed on: <?= date('d M, Y h:i A') ?></span>
        </div>

    </div>

</body>
</html>


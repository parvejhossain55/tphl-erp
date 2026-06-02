<?php
/**
 * print-work-order.php
 * Standalone print/PDF page — no sidebar, no header
 * Variables: $wo, $linkedBills, $linkedWeeklyReports, $base_url, $page_title
 */

$statusColors = [
    'draft'          => ['bg' => '#374151', 'label' => 'Draft'],
    'approved'       => ['bg' => '#0369a1', 'label' => 'Approved'],
    'active'         => ['bg' => '#1d4ed8', 'label' => 'Active'],
    'work_completed' => ['bg' => '#b45309', 'label' => 'Work Completed — Awaiting QC'],
    'qc_approved'    => ['bg' => '#15803d', 'label' => 'QC Approved'],
    'closed'         => ['bg' => '#111827', 'label' => 'Closed'],
    'rejected'       => ['bg' => '#b91c1c', 'label' => 'Rejected'],
];

$status    = $wo['status'];
$sc        = $statusColors[$status] ?? $statusColors['draft'];
$statusLbl = $sc['label'];
$statusBg  = $sc['bg'];

$totalAmt  = (float)($wo['total_amount']     ?? 0);
$advAmt    = (float)($wo['advance_amount']   ?? 0);
if ($advAmt == 0 && ($wo['advance_percent'] ?? 0) > 0) {
    $advAmt = round($totalAmt * (float)$wo['advance_percent'] / 100, 2);
}
$retAmt    = (float)($wo['retention_amount'] ?? 0);
if ($retAmt == 0 && ($wo['retention_percent'] ?? 0) > 0) {
    $retAmt = round($totalAmt * (float)$wo['retention_percent'] / 100, 2);
}
$runAmt    = $totalAmt - $advAmt - $retAmt;

$printDate = date('d M Y, h:i A');
$isOverdue = $wo['end_date'] && strtotime($wo['end_date']) < time() && !in_array($status, ['closed','qc_approved','work_completed']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> — Print</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            color: #1a1a2e;
            background: #f0f4f8;
            line-height: 1.6;
        }

        .screen-wrapper { max-width: 960px; margin: 30px auto; padding: 0 16px; }

        /* ── Action Bar ──────────────────────────────── */
        .print-bar {
            background: linear-gradient(135deg, #1e3a5f 0%, #1e40af 100%);
            border-radius: 12px; padding: 16px 24px; margin-bottom: 24px;
            display: flex; justify-content: space-between; align-items: center;
            gap: 12px; box-shadow: 0 4px 20px rgba(30,58,95,.3);
        }
        .print-bar .doc-label { color: rgba(255,255,255,.6); font-size: 11px; font-weight: 500; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 2px; }
        .print-bar .doc-title { color: #fff; font-size: 15px; font-weight: 700; }
        .btn-print { background: #4ade80; color: #14532d; border: none; border-radius: 8px; padding: 10px 22px; font-size: 13px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; text-decoration: none; transition: background .2s; white-space: nowrap; }
        .btn-print:hover { background: #22c55e; }
        .btn-back { background: rgba(255,255,255,.12); color: rgba(255,255,255,.85); border: 1px solid rgba(255,255,255,.25); border-radius: 8px; padding: 10px 18px; font-size: 13px; font-weight: 500; text-decoration: none; display: flex; align-items: center; gap: 6px; transition: background .2s; }
        .btn-back:hover { background: rgba(255,255,255,.2); }

        /* ── Document ────────────────────────────────── */
        .document { background: #fff; border-radius: 14px; box-shadow: 0 4px 30px rgba(0,0,0,.12); overflow: hidden; }

        /* ── Header ──────────────────────────────────── */
        .doc-header {
            background: linear-gradient(135deg, #1e3a5f 0%, #1e40af 60%, #2563eb 100%);
            color: #fff; padding: 32px 40px; position: relative; overflow: hidden;
        }
        .doc-header::before { content:''; position:absolute; width:260px; height:260px; border-radius:50%; background:rgba(255,255,255,.04); top:-80px; right:-60px; }
        .doc-header .company { font-size: 10px; color: rgba(255,255,255,.55); text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 10px; }
        .doc-header h1 { font-size: 20px; font-weight: 900; margin-bottom: 4px; }
        .doc-header .wo-title { font-size: 16px; font-weight: 600; color: rgba(255,255,255,.85); margin-bottom: 4px; }
        .doc-header .sub { font-size: 12px; color: rgba(255,255,255,.6); }
        .doc-header .meta-row { display: flex; gap: 14px; flex-wrap: wrap; margin-top: 18px; }
        .meta-chip { background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.18); border-radius: 8px; padding: 8px 16px; }
        .meta-chip .mlabel { font-size: 9px; color: rgba(255,255,255,.5); text-transform: uppercase; letter-spacing: .5px; }
        .meta-chip .mvalue { font-size: 13px; font-weight: 700; color: #fff; margin-top: 2px; }
        .status-tag { display: inline-block; padding: 4px 14px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .5px; }

        /* ── Amount Row ──────────────────────────────── */
        .amount-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0; border-bottom: 2px solid #eff6ff; }
        .amount-cell { padding: 20px 24px; border-right: 1px solid #eff6ff; position: relative; }
        .amount-cell:last-child { border-right: none; }
        .amount-cell .al { font-size: 10px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: .8px; margin-bottom: 4px; }
        .amount-cell .av { font-size: 18px; font-weight: 900; }
        .amount-cell .accent { position: absolute; left: 0; top: 0; width: 3px; height: 100%; }
        .c-blue { color: #1d4ed8; } .c-teal { color: #0f766e; } .c-red { color: #b91c1c; } .c-dark { color: #111827; }

        /* ── Sections ────────────────────────────────── */
        .section-block { padding: 24px 40px; border-bottom: 1px solid #f1f5f9; }
        .section-block:last-child { border-bottom: none; }
        .section-title { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.2px; color: #4b5563; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .section-title::after { content: ''; flex: 1; height: 1px; background: #e5e7eb; }

        /* ── Two column grid ──────────────────────────── */
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; }
        .three-col { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
        .info-item .il { font-size: 10px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
        .info-item .iv { font-size: 13px; font-weight: 600; color: #111827; }
        .iv-muted { font-size: 13px; color: #4b5563; line-height: 1.5; }

        /* ── Payment cards ───────────────────────────── */
        .pay-card { border-radius: 10px; padding: 12px 16px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        .pay-card:last-child { margin-bottom: 0; }
        .pay-card .plabel { font-size: 12px; font-weight: 500; color: #4b5563; }
        .pay-card .pamount { font-size: 14px; font-weight: 800; }

        /* ── Tables ──────────────────────────────────── */
        .print-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .print-table thead tr { background: #1e3a5f; color: #fff; }
        .print-table thead th { padding: 9px 12px; text-align: left; font-size: 10px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; }
        .print-table thead th.right { text-align: right; }
        .print-table tbody tr:nth-child(even) { background: #f9fafb; }
        .print-table tbody td { padding: 9px 12px; border-bottom: 1px solid #f1f5f9; color: #374151; }
        .print-table tbody td.right { text-align: right; font-weight: 600; }

        .badge-pill { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; }
        .bp-blue   { background: rgba(37,99,235,.1);  color: #1d4ed8; }
        .bp-green  { background: rgba(16,185,129,.1); color: #059669; }
        .bp-amber  { background: rgba(217,119,6,.1);  color: #b45309; }
        .bp-red    { background: rgba(220,38,38,.1);  color: #b91c1c; }
        .bp-gray   { background: rgba(107,114,128,.1);color: #4b5563; }

        /* ── Footer ──────────────────────────────────── */
        .doc-footer { background: #f9fafb; padding: 18px 40px; display: flex; justify-content: space-between; align-items: flex-end; border-top: 2px solid #e5e7eb; font-size: 11px; color: #9ca3af; }
        .sig-line { width: 160px; border-top: 1px solid #d1d5db; margin-top: 32px; padding-top: 6px; text-align: center; font-size: 10px; color: #6b7280; }

        @media print {
            body { background: #fff; }
            .screen-wrapper { margin: 0; padding: 0; max-width: 100%; }
            .print-bar { display: none !important; }
            .document { box-shadow: none; border-radius: 0; }
            .doc-header, .amount-row, .print-table thead tr { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { margin: 10mm; size: A4; }
        }
    </style>
</head>
<body>
<div class="screen-wrapper">

    <!-- Action Bar -->
    <div class="print-bar">
        <div>
            <div class="doc-label">easy-nirmanX · Construction Management</div>
            <div class="doc-title"><?= htmlspecialchars($wo['wo_number'] ?? 'Work Order') ?> — <?= htmlspecialchars($wo['project_name'] ?? '') ?></div>
        </div>
        <div style="display:flex;gap:10px;">
            <a href="<?= $base_url ?>/con-work-order/<?= $wo['id'] ?>" class="btn-back">← Back to View</a>
            <button class="btn-print" onclick="window.print()">🖨 Print / Save as PDF</button>
        </div>
    </div>

    <!-- Document -->
    <div class="document">

        <!-- Header -->
        <div class="doc-header">
            <div class="company">easy-nirmanX · Work Order</div>
            <h1><?= htmlspecialchars($wo['wo_number'] ?? 'Work Order') ?></h1>
            <div class="wo-title"><?= htmlspecialchars($wo['title'] ?? '') ?></div>
            <div class="sub"><?= htmlspecialchars($wo['project_name'] ?? '') ?></div>
            <div class="meta-row">
                <div class="meta-chip">
                    <div class="mlabel">Status</div>
                    <div class="mvalue">
                        <span class="status-tag" style="background:<?= $statusBg ?>;color:#fff;"><?= $statusLbl ?></span>
                    </div>
                </div>
                <div class="meta-chip">
                    <div class="mlabel">Contractor</div>
                    <div class="mvalue"><?= htmlspecialchars($wo['contractor_name'] ?? '—') ?></div>
                </div>
                <?php if (!empty($wo['start_date'])): ?>
                <div class="meta-chip">
                    <div class="mlabel">Timeline</div>
                    <div class="mvalue"><?= date('d M Y', strtotime($wo['start_date'])) ?> → <?= $wo['end_date'] ? date('d M Y', strtotime($wo['end_date'])) : '?' ?></div>
                </div>
                <?php endif; ?>
                <div class="meta-chip">
                    <div class="mlabel">Print Date</div>
                    <div class="mvalue"><?= $printDate ?></div>
                </div>
            </div>
        </div>

        <!-- Amount Row -->
        <div class="amount-row">
            <div class="amount-cell">
                <div class="accent" style="background:#1d4ed8;"></div>
                <div class="al">Total Amount</div>
                <div class="av c-blue">৳<?= number_format($totalAmt, 2) ?></div>
            </div>
            <div class="amount-cell">
                <div class="accent" style="background:#0f766e;"></div>
                <div class="al">Advance (<?= $wo['advance_percent'] ?? 0 ?>%)</div>
                <div class="av c-teal">৳<?= number_format($advAmt, 2) ?></div>
            </div>
            <div class="amount-cell">
                <div class="accent" style="background:#7c3aed;"></div>
                <div class="al">Running (<?= $wo['running_percent'] ?? 70 ?>%)</div>
                <div class="av" style="color:#7c3aed;">৳<?= number_format($runAmt, 2) ?></div>
            </div>
            <div class="amount-cell">
                <div class="accent" style="background:#b91c1c;"></div>
                <div class="al">Retention (<?= $wo['retention_percent'] ?? 10 ?>%)</div>
                <div class="av c-red">৳<?= number_format($retAmt, 2) ?></div>
            </div>
        </div>

        <!-- Section 1: Work Details -->
        <div class="section-block">
            <div class="section-title">Work Details</div>
            <div class="two-col">
                <div>
                    <div class="three-col" style="margin-bottom:14px;">
                        <div class="info-item">
                            <div class="il">Project</div>
                            <div class="iv"><?= htmlspecialchars($wo['project_name'] ?? '—') ?></div>
                        </div>
                        <div class="info-item">
                            <div class="il">Work Type</div>
                            <div class="iv"><?= htmlspecialchars($wo['work_type_name'] ?? '—') ?></div>
                        </div>
                        <div class="info-item">
                            <div class="il">Design Type</div>
                            <div class="iv"><?= htmlspecialchars($wo['design_type_name'] ?? '—') ?></div>
                        </div>
                    </div>
                    <div class="three-col" style="margin-bottom:14px;">
                        <div class="info-item">
                            <div class="il">Unit</div>
                            <div class="iv"><?= strtoupper($wo['unit'] ?? '—') ?></div>
                        </div>
                        <div class="info-item">
                            <div class="il">Quantity</div>
                            <div class="iv"><?= number_format($wo['quantity'] ?? 0, 2) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="il">Unit Rate</div>
                            <div class="iv">৳<?= number_format($wo['unit_rate'] ?? 0, 2) ?></div>
                        </div>
                    </div>
                    <div class="three-col">
                        <div class="info-item">
                            <div class="il">Start Date</div>
                            <div class="iv"><?= $wo['start_date'] ? date('d M Y', strtotime($wo['start_date'])) : '—' ?></div>
                        </div>
                        <div class="info-item">
                            <div class="il">End Date</div>
                            <div class="iv <?= $isOverdue ? '' : '' ?>" style="<?= $isOverdue ? 'color:#b91c1c;' : '' ?>">
                                <?= $wo['end_date'] ? date('d M Y', strtotime($wo['end_date'])) : '—' ?>
                                <?= $isOverdue ? ' ⚠ Overdue' : '' ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="il">Created By</div>
                            <div class="iv"><?= htmlspecialchars($wo['created_by_name'] ?? '—') ?></div>
                        </div>
                    </div>
                </div>

                <div>
                    <!-- Contractor Info -->
                    <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;padding:16px;margin-bottom:14px;">
                        <div style="font-size:10px;font-weight:800;color:#0369a1;text-transform:uppercase;letter-spacing:.8px;margin-bottom:10px;">Contractor</div>
                        <div style="font-size:14px;font-weight:800;color:#0c4a6e;margin-bottom:4px;"><?= htmlspecialchars($wo['contractor_name'] ?? '—') ?></div>
                        <?php if (!empty($wo['contractor_trade'])): ?>
                        <div style="font-size:12px;color:#0369a1;">🔧 <?= htmlspecialchars($wo['contractor_trade']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($wo['contractor_phone'])): ?>
                        <div style="font-size:12px;color:#0369a1;margin-top:4px;">📞 <?= htmlspecialchars($wo['contractor_phone']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Approval info -->
                    <?php if ($status === 'approved' || $status === 'closed' || $status === 'qc_approved'): ?>
                    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:14px;">
                        <div style="font-size:10px;font-weight:800;color:#15803d;text-transform:uppercase;letter-spacing:.8px;margin-bottom:8px;">Approval Info</div>
                        <?php if (!empty($wo['approved_by_name'])): ?>
                        <div style="font-size:12px;color:#166534;margin-bottom:4px;">✔ Approved by <strong><?= htmlspecialchars($wo['approved_by_name']) ?></strong></div>
                        <?php if (!empty($wo['approved_at'])): ?>
                        <div style="font-size:11px;color:#4b5563;"><?= date('d M Y H:i', strtotime($wo['approved_at'])) ?></div>
                        <?php endif; ?>
                        <?php endif; ?>
                        <?php if (!empty($wo['qc_approved_by_name'])): ?>
                        <div style="font-size:12px;color:#166534;margin-top:6px;">✔ QC Approved by <strong><?= htmlspecialchars($wo['qc_approved_by_name']) ?></strong></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($wo['description']) || !empty($wo['scope_of_work']) || !empty($wo['notes'])): ?>
            <div style="margin-top:16px;display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:14px;">
                <?php if (!empty($wo['description'])): ?>
                <div>
                    <div class="il" style="font-size:10px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Description</div>
                    <div class="iv-muted"><?= nl2br(htmlspecialchars($wo['description'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($wo['scope_of_work'])): ?>
                <div>
                    <div class="il" style="font-size:10px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Scope of Work</div>
                    <div class="iv-muted"><?= nl2br(htmlspecialchars($wo['scope_of_work'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($wo['notes'])): ?>
                <div>
                    <div class="il" style="font-size:10px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Notes</div>
                    <div style="background:#fffbeb;border-left:3px solid #f59e0b;padding:10px 14px;border-radius:0 6px 6px 0;font-size:12px;color:#78350f;">
                        <?= nl2br(htmlspecialchars($wo['notes'])) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Section 2: Rate Schedule (CS Format) -->
        <?php if (!empty($woItems)): ?>
        <div class="section-block">
            <div class="section-title">কাজের বিবরণ ও রেট সিডিউল (Rate Schedule)</div>
            <table class="print-table">
                <thead>
                    <tr>
                        <th style="width:46px" class="center">নং</th>
                        <th>বিবরণ (Description)</th>
                        <th style="width:18%">অবস্থান (Location)</th>
                        <th style="width:6%" class="center">Unit</th>
                        <th style="width:7%" class="right">Qty</th>
                        <th style="width:9%" class="right">Rate (৳)</th>
                        <th style="width:11%" class="right">Amount (৳)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $rsGrand = 0;
                    foreach ($woItems as $wItem):
                        $wSub = (float)$wItem['quantity'] * (float)$wItem['rate'];
                        $rsGrand += $wSub;
                    ?>
                    <tr>
                        <td style="text-align:center;font-weight:700;color:#1d4ed8"><?= htmlspecialchars($wItem['sl_no']) ?></td>
                        <td style="white-space:pre-wrap;font-size:11px"><?= nl2br(htmlspecialchars($wItem['description'])) ?></td>
                        <td style="color:#6b7280;font-size:11px"><?= htmlspecialchars($wItem['location'] ?? '') ?></td>
                        <td style="text-align:center"><?= strtoupper(htmlspecialchars($wItem['unit'])) ?></td>
                        <td class="right"><?= number_format((float)$wItem['quantity'], 2) ?></td>
                        <td class="right">৳<?= number_format((float)$wItem['rate'], 2) ?></td>
                        <td class="right" style="color:#1d4ed8;font-weight:700">৳<?= number_format($wSub, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background:#dbeafe">
                        <td colspan="6" style="text-align:right;font-weight:800;color:#1e3a8a;padding:10px 12px">Grand Total:</td>
                        <td style="text-align:right;font-weight:900;color:#1e3a8a;font-size:14px;padding:10px 12px">৳<?= number_format($rsGrand, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>

        <!-- Section 3: Linked Weekly Reports -->
        <?php if (!empty($linkedWeeklyReports)): ?>
        <div class="section-block">
            <div class="section-title">Weekly Progress Reports (<?= count($linkedWeeklyReports) ?> entries)</div>
            <table class="print-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Report Date</th>
                        <th>Engineer</th>
                        <th>Work Done</th>
                        <th>Progress</th>
                        <th>Weather</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($linkedWeeklyReports as $ri => $wr): ?>
                    <tr>
                        <td style="color:#9ca3af;font-size:11px;"><?= $ri + 1 ?></td>
                        <td style="font-weight:600;"><?= date('d M Y', strtotime($wr['report_date'])) ?></td>
                        <td><?= htmlspecialchars($wr['engineer_name'] ?? '—') ?></td>
                        <td style="font-size:12px;"><?= htmlspecialchars(mb_strimwidth($wr['work_done'], 0, 60, '…')) ?></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:6px;">
                                <div style="width:80px;height:6px;background:#e5e7eb;border-radius:4px;overflow:hidden;">
                                    <div style="height:100%;width:<?= $wr['progress_percentage'] ?>%;background:#16a34a;border-radius:4px;"></div>
                                </div>
                                <span style="font-size:11px;font-weight:600;"><?= $wr['progress_percentage'] ?>%</span>
                            </div>
                        </td>
                        <td style="font-size:11px;"><?= htmlspecialchars($wr['weather_condition'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Section 3: Linked Bills -->
        <?php if (!empty($linkedBills)): ?>
        <div class="section-block">
            <div class="section-title">Contractor Bills (<?= count($linkedBills) ?> entries)</div>
            <table class="print-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Bill Date</th>
                        <th class="right">Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($linkedBills as $bi => $bill):
                        $bsc = $bill['status'] === 'paid' ? 'bp-green' : ($bill['status'] === 'rejected' ? 'bp-red' : 'bp-amber');
                    ?>
                    <tr>
                        <td style="color:#9ca3af;font-size:11px;"><?= $bi + 1 ?></td>
                        <td style="font-weight:600;"><?= date('d M Y', strtotime($bill['bill_date'])) ?></td>
                        <td class="right">৳<?= number_format($bill['bill_amount'], 2) ?></td>
                        <td><span class="badge-pill <?= $bsc ?>"><?= ucfirst($bill['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="doc-footer">
            <div>
                <div style="font-weight:700;color:#374151;margin-bottom:3px;">easy-nirmanX · Construction Management System</div>
                <div>Generated: <?= $printDate ?> · WO: <?= htmlspecialchars($wo['wo_number'] ?? '') ?></div>
            </div>
            <div style="display:flex;gap:40px;">
                <div class="sig-line">Contractor</div>
                <div class="sig-line">Authorized By</div>
            </div>
        </div>

    </div><!-- /.document -->
</div><!-- /.screen-wrapper -->
</body>
</html>

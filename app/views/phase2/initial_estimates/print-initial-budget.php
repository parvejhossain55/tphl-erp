<?php
/**
 * print-initial-budget.php
 * Standalone print/PDF page — no sidebar, no header
 * Variables: $budget, $items, $labors, $breakdown, $workingPlans, $base_url, $page_title
 */

function pib_fmt($n) {
    $n = (float)$n;
    if ($n >= 10000000) return '৳' . number_format($n/10000000, 2) . ' Cr';
    if ($n >= 100000)   return '৳' . number_format($n/100000,   2) . ' L';
    return '৳' . number_format($n, 2);
}

$monthNames = ['','January','February','March','April','May','June',
               'July','August','September','October','November','December'];

$st        = $budget['status'] ?? 'pending';
$statusLabel = ucfirst($st);
$printDate = date('d M Y, h:i A');

$matTotal   = $budget['material_cost_total'] ?? 0;
$labTotal   = $budget['labor_cost_total'] ?? 0;
$otherCost  = $budget['other_costs'] ?? 0;
$grandTotal = $budget['total_estimated_cost'] ?? 0;
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

        .screen-wrapper {
            max-width: 960px;
            margin: 30px auto;
            padding: 0 16px;
        }

        /* ── Action Bar ──────────────────────────────── */
        .print-bar {
            background: linear-gradient(135deg, #0f5132 0%, #166534 100%);
            border-radius: 12px;
            padding: 16px 24px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            box-shadow: 0 4px 20px rgba(15,81,50,0.3);
        }
        .print-bar .doc-label { color: rgba(255,255,255,.65); font-size: 11px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
        .print-bar .doc-title { color: #fff; font-size: 15px; font-weight: 700; }
        .btn-print {
            background: #4ade80; color: #14532d; border: none; border-radius: 8px;
            padding: 10px 22px; font-size: 13px; font-weight: 700; cursor: pointer;
            display: flex; align-items: center; gap: 8px; text-decoration: none;
            transition: background .2s; white-space: nowrap;
        }
        .btn-print:hover { background: #22c55e; }
        .btn-back {
            background: rgba(255,255,255,.12); color: rgba(255,255,255,.85);
            border: 1px solid rgba(255,255,255,.25); border-radius: 8px;
            padding: 10px 18px; font-size: 13px; font-weight: 500;
            text-decoration: none; display: flex; align-items: center; gap: 6px;
            transition: background .2s;
        }
        .btn-back:hover { background: rgba(255,255,255,.2); }

        /* ── Document ────────────────────────────────── */
        .document {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 30px rgba(0,0,0,.12);
            overflow: hidden;
        }

        /* ── Document Header ─────────────────────────── */
        .doc-header {
            background: linear-gradient(135deg, #0f5132 0%, #166534 60%, #15803d 100%);
            color: #fff;
            padding: 32px 40px;
            position: relative;
            overflow: hidden;
        }
        .doc-header::before { content:''; position:absolute; width:260px; height:260px; border-radius:50%; background:rgba(255,255,255,.04); top:-80px; right:-60px; }
        .doc-header .company { font-size: 10px; color: rgba(255,255,255,.55); text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 10px; }
        .doc-header h1 { font-size: 22px; font-weight: 900; margin-bottom: 4px; }
        .doc-header .sub { font-size: 12px; color: rgba(255,255,255,.65); }
        .doc-header .meta-row { display: flex; gap: 16px; flex-wrap: wrap; margin-top: 18px; }
        .meta-chip {
            background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.18);
            border-radius: 8px; padding: 8px 16px;
        }
        .meta-chip .mlabel { font-size: 9px; color: rgba(255,255,255,.5); text-transform: uppercase; letter-spacing: .5px; }
        .meta-chip .mvalue { font-size: 13px; font-weight: 700; color: #fff; margin-top: 2px; }
        .status-chip {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 14px; border-radius: 20px; font-size: 11px; font-weight: 800;
            text-transform: uppercase; letter-spacing: .5px;
        }
        .status-approved { background: rgba(74,222,128,.2); color: #4ade80; }
        .status-pending  { background: rgba(251,191,36,.2);  color: #fbbf24; }
        .status-cancelled{ background: rgba(239,68,68,.2);   color: #f87171; }

        /* ── KPI Row ─────────────────────────────────── */
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0;
            border-bottom: 2px solid #e8f5e9;
        }
        .kpi-cell {
            padding: 20px 24px;
            border-right: 1px solid #e8f5e9;
            position: relative;
        }
        .kpi-cell:last-child { border-right: none; }
        .kpi-cell .kpi-label { font-size: 10px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: .8px; margin-bottom: 4px; }
        .kpi-cell .kpi-value { font-size: 18px; font-weight: 900; }
        .kpi-cell .kpi-accent { position: absolute; left: 0; top: 0; width: 3px; height: 100%; border-radius: 0 2px 2px 0; }
        .c-green  { color: #16a34a; } .c-blue   { color: #2563eb; }
        .c-amber  { color: #d97706; } .c-purple { color: #7c3aed; }

        /* ── Section ──────────────────────────────────── */
        .section-block { padding: 24px 40px; border-bottom: 1px solid #f1f5f9; }
        .section-block:last-child { border-bottom: none; }
        .section-title {
            font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.2px;
            color: #4b5563; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;
        }
        .section-title::after { content: ''; flex: 1; height: 1px; background: #e5e7eb; }

        /* ── Info Grid ───────────────────────────────── */
        .info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
        .info-item .il { font-size: 10px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
        .info-item .iv { font-size: 13px; font-weight: 600; color: #111827; }

        /* ── Breakdown Cards ─────────────────────────── */
        .breakdown-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; }
        .bkd-card { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px 18px; }
        .bkd-title { font-size: 12px; font-weight: 800; color: #374151; margin-bottom: 10px; }
        .bkd-row { display: flex; justify-content: space-between; font-size: 12px; padding: 4px 0; border-bottom: 1px dashed #e5e7eb; }
        .bkd-row:last-child { border-bottom: none; font-weight: 800; font-size: 13px; color: #111827; }

        /* ── Tables ──────────────────────────────────── */
        .print-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .print-table thead tr { background: #0f5132; color: #fff; }
        .print-table thead th { padding: 9px 12px; text-align: left; font-size: 10px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; }
        .print-table thead th.right { text-align: right; }
        .print-table tbody tr:nth-child(even) { background: #f9fafb; }
        .print-table tbody td { padding: 9px 12px; border-bottom: 1px solid #f1f5f9; color: #374151; }
        .print-table tbody td.right { text-align: right; font-weight: 600; }
        .print-table tfoot tr { background: #0f5132; color: #fff; font-weight: 800; }
        .print-table tfoot td { padding: 10px 12px; }
        .print-table tfoot td.right { text-align: right; }

        .badge-pill { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; }
        .bp-purple { background: rgba(124,58,237,.1); color: #6d28d9; }
        .bp-blue   { background: rgba(37,99,235,.1);  color: #1d4ed8; }
        .bp-green  { background: rgba(16,185,129,.1); color: #059669; }
        .bp-amber  { background: rgba(217,119,6,.1);  color: #b45309; }
        .bp-red    { background: rgba(220,38,38,.1);  color: #b91c1c; }

        /* ── Footer ──────────────────────────────────── */
        .doc-footer {
            background: #f9fafb;
            padding: 18px 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-top: 2px solid #e5e7eb;
            font-size: 11px; color: #9ca3af;
        }
        .sig-line { width: 160px; border-top: 1px solid #d1d5db; margin-top: 32px; padding-top: 6px; text-align: center; font-size: 10px; color: #6b7280; }

        /* ── Print ───────────────────────────────────── */
        @media print {
            body { background: #fff; }
            .screen-wrapper { margin: 0; padding: 0; max-width: 100%; }
            .print-bar { display: none !important; }
            .document { box-shadow: none; border-radius: 0; }
            .doc-header, .kpi-row, .print-table thead tr, .print-table tfoot tr {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            @page { margin: 10mm; size: A4; }
        }
    </style>
</head>
<body>
<div class="screen-wrapper">

    <!-- Action Bar -->
    <div class="print-bar">
        <div>
            <div class="doc-label">TPHL-ERP · Construction Management</div>
            <div class="doc-title">Initial Estimate Budget — <?= htmlspecialchars($budget['project_name'] ?? '') ?></div>
        </div>
        <div style="display:flex;gap:10px;">
            <a href="<?= $base_url ?>/con-estimate-show/<?= $budget['id'] ?>" class="btn-back">← Back to View</a>
            <button class="btn-print" onclick="window.print()">🖨 Print / Save as PDF</button>
        </div>
    </div>

    <!-- Document -->
    <div class="document">

        <!-- Header -->
        <div class="doc-header">
            <div class="company">TPHL-ERP · Initial Estimate Budget</div>
            <h1><?= htmlspecialchars($budget['project_name'] ?? 'N/A') ?></h1>
            <div class="sub">
                <?= !empty($budget['start_date']) ? date('d M Y', strtotime($budget['start_date'])) : '—' ?>
                &nbsp;→&nbsp;
                <?= !empty($budget['end_date']) ? date('d M Y', strtotime($budget['end_date'])) : '—' ?>
                &nbsp;·&nbsp;
                <?= number_format($budget['total_area_sqft'] ?? 0, 2) ?> sqft
            </div>
            <div class="meta-row">
                <div class="meta-chip">
                    <div class="mlabel">Budget ID</div>
                    <div class="mvalue">#EST-<?= str_pad($budget['id'], 5, '0', STR_PAD_LEFT) ?></div>
                </div>
                <div class="meta-chip">
                    <div class="mlabel">Status</div>
                    <div class="mvalue">
                        <span class="status-chip <?= $st === 'approved' ? 'status-approved' : ($st === 'cancelled' ? 'status-cancelled' : 'status-pending') ?>">
                            <?= $statusLabel ?>
                        </span>
                    </div>
                </div>
                <?php if (!empty($budget['approved_by_name'])): ?>
                <div class="meta-chip">
                    <div class="mlabel">Approved By</div>
                    <div class="mvalue"><?= htmlspecialchars($budget['approved_by_name']) ?></div>
                </div>
                <?php endif; ?>
                <div class="meta-chip">
                    <div class="mlabel">Print Date</div>
                    <div class="mvalue"><?= $printDate ?></div>
                </div>
            </div>
        </div>

        <!-- KPI Row -->
        <div class="kpi-row">
            <div class="kpi-cell">
                <div class="kpi-accent" style="background:#16a34a;"></div>
                <div class="kpi-label">Material Cost</div>
                <div class="kpi-value c-green"><?= pib_fmt($matTotal) ?></div>
            </div>
            <div class="kpi-cell">
                <div class="kpi-accent" style="background:#2563eb;"></div>
                <div class="kpi-label">Labor Cost</div>
                <div class="kpi-value c-blue"><?= pib_fmt($labTotal) ?></div>
            </div>
            <div class="kpi-cell">
                <div class="kpi-accent" style="background:#d97706;"></div>
                <div class="kpi-label">Other Costs</div>
                <div class="kpi-value c-amber"><?= pib_fmt($otherCost) ?></div>
            </div>
            <div class="kpi-cell">
                <div class="kpi-accent" style="background:#7c3aed;"></div>
                <div class="kpi-label">Grand Total</div>
                <div class="kpi-value c-purple"><?= pib_fmt($grandTotal) ?></div>
            </div>
        </div>

        <!-- Section 1: Project Info -->
        <div class="section-block">
            <div class="section-title">Project Information</div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="il">Project</div>
                    <div class="iv"><?= htmlspecialchars($budget['project_name'] ?? '—') ?></div>
                </div>
                <div class="info-item">
                    <div class="il">Work Type</div>
                    <div class="iv"><?= htmlspecialchars($budget['work_type_name'] ?? '—') ?></div>
                </div>
                <div class="info-item">
                    <div class="il">Total Area</div>
                    <div class="iv"><?= number_format($budget['total_area_sqft'] ?? 0, 2) ?> sqft</div>
                </div>
                <div class="info-item">
                    <div class="il">Start Date</div>
                    <div class="iv"><?= !empty($budget['start_date']) ? date('d M Y', strtotime($budget['start_date'])) : '—' ?></div>
                </div>
                <div class="info-item">
                    <div class="il">End Date</div>
                    <div class="iv"><?= !empty($budget['end_date']) ? date('d M Y', strtotime($budget['end_date'])) : '—' ?></div>
                </div>
                <div class="info-item">
                    <div class="il">Weight</div>
                    <div class="iv"><?= number_format($budget['weight_percentage'] ?? 0, 2) ?>%</div>
                </div>
                <?php if (!empty($budget['work_details'])): ?>
                <div class="info-item" style="grid-column: span 3;">
                    <div class="il">Work Details</div>
                    <div class="iv" style="font-weight:400;color:#4b5563;"><?= htmlspecialchars($budget['work_details']) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($budget['other_cost_detail'])): ?>
                <div class="info-item" style="grid-column: span 3;">
                    <div class="il">Other Cost Details</div>
                    <div class="iv" style="font-weight:400;color:#4b5563;"><?= htmlspecialchars($budget['other_cost_detail']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Section 2: Design Type Breakdown -->
        <?php if (!empty($breakdown)): ?>
        <div class="section-block">
            <div class="section-title">Design Type Cost Breakdown</div>
            <div class="breakdown-grid">
                <?php foreach ($breakdown as $bk): ?>
                <div class="bkd-card">
                    <div class="bkd-title"><span class="badge-pill bp-purple"><?= htmlspecialchars($bk['design_type_name'] ?? 'N/A') ?></span></div>
                    <div class="bkd-row">
                        <span style="color:#6b7280;">Material (<?= $bk['item_count'] ?> items)</span>
                        <span style="color:#16a34a;font-weight:700;"><?= pib_fmt($bk['total_material']) ?></span>
                    </div>
                    <div class="bkd-row">
                        <span style="color:#6b7280;">Labor (<?= $bk['labor_count'] ?> rows)</span>
                        <span style="color:#2563eb;font-weight:700;"><?= pib_fmt($bk['total_labor']) ?></span>
                    </div>
                    <div class="bkd-row">
                        <span>Design Total</span>
                        <span style="color:#7c3aed;"><?= pib_fmt($bk['design_total']) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Section 3: Material Items -->
        <?php if (!empty($items)): ?>
        <div class="section-block">
            <div class="section-title">Material Items (<?= count($items) ?> rows)</div>
            <table class="print-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Work Type</th>
                        <th>Material</th>
                        <th>Description</th>
                        <th class="right">Unit</th>
                        <th class="right">Est. Qty</th>
                        <th class="right">Unit Cost</th>
                        <th class="right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $matGrand = 0; foreach ($items as $si => $item):
                        $sub = $item['estimated_qty'] * $item['unit_cost_approx'];
                        $matGrand += $sub;
                    ?>
                    <tr>
                        <td style="color:#9ca3af;font-size:11px;"><?= $si + 1 ?></td>
                        <td><span class="badge-pill bp-purple"><?= htmlspecialchars($item['work_type_label'] ?? $item['design_type_name'] ?? '—') ?></span></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($item['material_name'] ?? '—') ?></td>
                        <td style="font-size:11px;color:#6b7280;"><?= htmlspecialchars($item['remarks'] ?? '—') ?></td>
                        <td class="right" style="font-size:11px;color:#9ca3af;"><?= htmlspecialchars($item['material_unit'] ?? '') ?></td>
                        <td class="right"><?= number_format($item['estimated_qty'], 3) ?></td>
                        <td class="right"><?= pib_fmt($item['unit_cost_approx']) ?></td>
                        <td class="right" style="color:#16a34a;"><?= pib_fmt($sub) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="7" style="text-align:right;padding-right:14px;">Material Total:</td>
                        <td class="right"><?= pib_fmt($matGrand) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>

        <!-- Section 4: Labor -->
        <?php if (!empty($labors)): ?>
        <div class="section-block">
            <div class="section-title">Labor Records (<?= count($labors) ?> rows)</div>
            <table class="print-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Work Type</th>
                        <th>Labor Type</th>
                        <th>Description</th>
                        <th class="right">Rate/sqft</th>
                        <th class="right">Area (sqft)</th>
                        <th class="right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $labGrand = 0; foreach ($labors as $li => $lb):
                        $sub = $lb['rate_per_sqft'] * $lb['area_sqft'];
                        $labGrand += $sub;
                    ?>
                    <tr>
                        <td style="color:#9ca3af;font-size:11px;"><?= $li + 1 ?></td>
                        <td><span class="badge-pill bp-blue"><?= htmlspecialchars($lb['work_type_label'] ?? $lb['design_type_name'] ?? '—') ?></span></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($lb['labor_type_name']) ?></td>
                        <td style="font-size:11px;color:#6b7280;"><?= htmlspecialchars($lb['description'] ?? '—') ?></td>
                        <td class="right"><?= pib_fmt($lb['rate_per_sqft']) ?></td>
                        <td class="right"><?= number_format($lb['area_sqft'], 2) ?></td>
                        <td class="right" style="color:#2563eb;"><?= pib_fmt($sub) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="6" style="text-align:right;padding-right:14px;">Labor Total:</td>
                        <td class="right"><?= pib_fmt($labGrand) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>

        <!-- Section 5: Working Plans -->
        <?php if (!empty($workingPlans)): ?>
        <div class="section-block">
            <div class="section-title">Linked Working Plans (<?= count($workingPlans) ?> entries)</div>
            <table class="print-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Period</th>
                        <th>Design Type</th>
                        <th>Description</th>
                        <th class="right">Planned Qty</th>
                        <th class="right">Est. Cost</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($workingPlans as $wi => $wp):
                        $wps = $wp['approved_status'] ?? 'Pending';
                        $bpc = $wps === 'Approved' ? 'bp-green' : ($wps === 'Rejected' ? 'bp-red' : 'bp-amber');
                    ?>
                    <tr>
                        <td style="color:#9ca3af;font-size:11px;"><?= $wi + 1 ?></td>
                        <td style="font-weight:600;"><?= $monthNames[(int)($wp['month'] ?? 0)] ?? '?' ?> <?= $wp['year'] ?? '' ?></td>
                        <td><span class="badge-pill bp-purple"><?= htmlspecialchars($wp['design_type_name'] ?? '—') ?></span></td>
                        <td style="font-size:12px;"><?= htmlspecialchars(mb_strimwidth($wp['work_description'] ?? '', 0, 55, '…')) ?></td>
                        <td class="right"><?= number_format($wp['planned_quantity'] ?? 0, 2) ?></td>
                        <td class="right" style="color:#0f766e;"><?= pib_fmt($wp['estimated_cost'] ?? 0) ?></td>
                        <td><span class="badge-pill <?= $bpc ?>"><?= $wps ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" style="text-align:right;padding-right:14px;">Total Planned Cost:</td>
                        <td class="right"><?= pib_fmt(array_sum(array_column($workingPlans, 'estimated_cost'))) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="doc-footer">
            <div>
                <div style="font-weight:700;color:#374151;margin-bottom:3px;">TPHL-ERP · Construction Management System</div>
                <div>Generated: <?= $printDate ?> · Budget ID: #EST-<?= str_pad($budget['id'], 5, '0', STR_PAD_LEFT) ?></div>
            </div>
            <div style="display:flex;gap:40px;">
                <div class="sig-line">Prepared By</div>
                <div class="sig-line">Authorized By</div>
            </div>
        </div>

    </div><!-- /.document -->
</div><!-- /.screen-wrapper -->
</body>
</html>

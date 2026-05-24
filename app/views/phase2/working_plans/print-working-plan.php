<?php
/**
 * print-working-plan.php
 * Standalone print/PDF page — NO sidebar, NO header, NO footer
 * Variables available: $plan, $projectPlans, $budgetSummary, $base_url, $page_title
 */

$monthName = date('F', mktime(0, 0, 0, $plan['month'], 10));
$status = $plan['approved_status'] ?? 'Pending';
$initial = $budgetSummary['initial_budget'] ?? 0;
$revised = $budgetSummary['revised_total'] ?? 0;
$planned = $budgetSummary['total_planned'] ?? 0;
$remaining = $budgetSummary['remaining_to_plan'] ?? 0;
$pct = $revised > 0 ? min(100, ($planned / $revised) * 100) : 0;
$printDate = date('d M Y, h:i A');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> — Print</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ── Base ───────────────────────────────────────── */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            /* color: #1a1a2e; */
            background: #f0f4f8;
            line-height: 1.6;
        }

        /* ── Screen-only wrapper ────────────────────────── */
        .screen-wrapper {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 16px;
        }

        /* ── Print action bar (hidden on print) ─────────── */
        .print-bar {
            background: #166534;
            border-radius: 12px;
            padding: 16px 24px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            box-shadow: 0 4px 20px rgba(30, 58, 95, 0.25);
        }

        .print-bar h6 {
            color: rgba(255, 255, 255, 0.75);
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .print-bar .doc-title {
            color: #fff;
            font-size: 15px;
            font-weight: 600;
        }

        .btn-print {
            background: #4ade80;
            color: #14532d;
            border: none;
            border-radius: 8px;
            padding: 10px 22px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s, transform 0.15s;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-print:hover {
            background: #22c55e;
            transform: translateY(-1px);
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.12);
            color: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: background 0.2s;
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* ── Document ───────────────────────────────────── */
        .document {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        /* ── Document Header ────────────────────────────── */
        .doc-header {
            background: #166534;
            color: #fff;
            padding: 32px 40px;
            position: relative;
            overflow: hidden;
        }

        .doc-header::before {
            content: '';
            position: absolute;
            top: -40px;
            right: -40px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
        }
        .company-info{
            color:#fff
        }

        .doc-header .company-info {
            font-size: 11px;
            color:#fff
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .doc-header h1 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .doc-header .sub {
            color: rgba(255, 255, 255, 0.65);
            font-size: 12px;
        }

        .doc-header .doc-meta {
            margin-top: 16px;
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
        }

        .doc-header .meta-item {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            padding: 8px 16px;
        }

        .doc-header .meta-item .label {
            font-size: 10px;
            color: rgba(255, 255, 255, 0.5);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .doc-header .meta-item .value {
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            margin-top: 2px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-approved {
            background: #dcfce7;
            color: #166534;
        }

        .status-pending {
            background: #fef9c3;
            color: #713f12;
        }

        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        /* ── Sections ───────────────────────────────────── */
        .section-block {
            padding: 28px 40px;
            border-bottom: 1px solid #f1f5f9;
        }

        .section-block:last-child {
            border-bottom: none;
        }

        .section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #64748b;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }

        /* ── Grid ───────────────────────────────────────── */
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .detail-grid.cols-2 {
            grid-template-columns: repeat(2, 1fr);
        }

        .detail-grid.cols-4 {
            grid-template-columns: repeat(4, 1fr);
        }

        .detail-item .label {
            font-size: 10px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .detail-item .value {
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
        }

        .detail-item .value.primary {
            color: #1e3a8a;
            font-size: 15px;
        }

        .detail-item .value.success {
            color: #166534;
            font-size: 15px;
        }

        .description-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #1e3a8a;
            border-radius: 0 8px 8px 0;
            padding: 16px 20px;
            font-size: 13px;
            color: #334155;
            line-height: 1.7;
        }

        /* ── Budget Bar ─────────────────────────────────── */
        .budget-bar-wrapper {
            background: #f1f5f9;
            border-radius: 8px;
            padding: 20px;
            margin-top: 12px;
        }

        .budget-rows {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }

        .budget-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px dashed #cbd5e1;
        }

        .budget-row:last-child {
            border-bottom: none;
        }

        .budget-row .bl {
            font-size: 12px;
            color: #64748b;
        }

        .budget-row .bv {
            font-size: 13px;
            font-weight: 600;
        }

        .budget-row .bv.danger {
            color: #dc2626;
        }

        .budget-row .bv.success-c {
            color: #16a34a;
        }

        .progress-track {
            background: #e2e8f0;
            border-radius: 10px;
            height: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 10px;
            background: linear-gradient(90deg, #1e3a8a, #3b82f6);
            transition: width 0.5s ease;
        }

        .progress-fill.danger {
            background: linear-gradient(90deg, #dc2626, #f87171);
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-top: 6px;
            font-size: 11px;
            color: #64748b;
        }

        /* ── Table ──────────────────────────────────────── */
        .plan-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .plan-table thead tr {
            background: #1e3a5f;
            color: #fff;
        }

        .plan-table thead th {
            padding: 10px 14px;
            text-align: left;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .plan-table thead th.right {
            text-align: right;
        }

        .plan-table thead th.center {
            text-align: center;
        }

        .plan-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .plan-table tbody tr.current-row {
            background: #eff6ff;
            border-left: 3px solid #1e3a8a;
        }

        .plan-table tbody td {
            padding: 9px 14px;
            border-bottom: 1px solid #e2e8f0;
            color: #374151;
        }

        .plan-table tbody td.right {
            text-align: right;
            font-weight: 600;
        }

        .plan-table tbody td.center {
            text-align: center;
        }

        .plan-table tfoot tr {
            background: #1e293b;
            color: #fff;
            font-weight: 700;
        }

        .plan-table tfoot td {
            padding: 10px 14px;
        }

        .plan-table tfoot td.right {
            text-align: right;
        }

        .badge-pill {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
        }

        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-ok {
            background: #dcfce7;
            color: #166534;
        }

        .badge-warn {
            background: #fef9c3;
            color: #713f12;
        }

        .badge-err {
            background: #fee2e2;
            color: #991b1b;
        }

        /* ── Footer ─────────────────────────────────────── */
        .doc-footer {
            background: #f8fafc;
            padding: 18px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 2px solid #e2e8f0;
            font-size: 11px;
            color: #94a3b8;
        }

        .doc-footer .sig-line {
            width: 160px;
            border-top: 1px solid #cbd5e1;
            margin-top: 30px;
            padding-top: 4px;
            text-align: center;
            font-size: 10px;
            color: #64748b;
        }

        /* ── Print Media ────────────────────────────────── */
        @media print {
            body {
                background: #fff;
                font-size: 12px;
            }

            .screen-wrapper {
                margin: 0;
                padding: 0;
                max-width: 100%;
            }

            .print-bar {
                display: none !important;
            }

            .document {
                box-shadow: none;
                border-radius: 0;
            }

            .doc-header {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .plan-table thead tr {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .plan-table tfoot tr {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .current-row {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .progress-fill {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            @page {
                margin: 12mm;
                size: A4;
            }
        }

        /* ── Activities & Gantt Table ────────────────────── */
        .activities-table, .gantt-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-top: 10px;
        }

        .activities-table th, .gantt-table th {
            background: #f1f5f9;
            color: #475569;
            padding: 8px 10px;
            font-weight: 600;
            border: 1px solid #cbd5e1;
            text-align: left;
        }

        .activities-table td, .gantt-table td {
            padding: 7px 10px;
            border: 1px solid #cbd5e1;
            color: #334155;
        }

        .activities-table tr:nth-child(even) {
            background: #f8fafc;
        }

        .gantt-table td.gantt-cell-active {
            background: rgba(30, 58, 95, 0.03);
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .gantt-bar-print {
            background: rgba(30, 58, 95, 0.12) !important;
            border-top: 1.5px solid #1e3a5f;
            border-bottom: 1.5px solid #1e3a5f;
            color: #1e3a5f;
            font-weight: bold;
            font-size: 9px;
            text-align: center;
            padding: 3px 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        .gantt-bar-print.start-cap {
            border-left: 3px solid #1e3a5f;
            border-top-left-radius: 3px;
            border-bottom-left-radius: 3px;
        }

        .gantt-bar-print.end-cap {
            border-right: 3px solid #1e3a5f;
            border-top-right-radius: 3px;
            border-bottom-right-radius: 3px;
        }
    </style>
</head>

<body>

    <div class="screen-wrapper">

        <!-- ── Print Action Bar (hidden on print) ──────────── -->
        <div class="print-bar">
            <div>
                <div class="company-info">TPHL-ERP · Construction Management</div>
                <div class="doc-title">Working Plan — <?= htmlspecialchars($plan['project_name'] ?? '') ?></div>
            </div>
            <div class="d-flex gap-2" style="display:flex;gap:10px;">
                <a href="<?= $base_url ?>/con-working-plan-view/<?= $plan['id'] ?>" class="btn-back">
                    ← Back to View
                </a>
                <button class="btn-print" onclick="window.print()">
                    🖨 Print / Save as PDF
                </button>
            </div>
        </div>

        <!-- ── Document ───────────────────────────────────── -->
        <div class="document">

            <!-- Header -->
            <div class="doc-header">
                <div class="company-info">TPHL-ERP · Construction Phase 2</div>
                <h1>Project Working Plan</h1>
                <div class="sub"><?= htmlspecialchars($plan['project_name'] ?? '') ?> · <?= $monthName ?>
                    <?= $plan['year'] ?></div>

                <div class="doc-meta">
                    <div class="meta-item">
                        <div class="label">Plan ID</div>
                        <div class="value">#WP-<?= str_pad($plan['id'], 5, '0', STR_PAD_LEFT) ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="label">Budget Ref.</div>
                        <div class="value">Budget #<?= $plan['initial_budget_id'] ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="label">Print Date</div>
                        <div class="value"><?= $printDate ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="label">Approval Status</div>
                        <div class="value">
                            <?php if ($status === 'Approved'): ?>
                                <span class="status-badge status-approved">✔ Approved</span>
                            <?php elseif ($status === 'Rejected'): ?>
                                <span class="status-badge status-rejected">✖ Rejected</span>
                            <?php else: ?>
                                <span class="status-badge status-pending">⏳ Pending</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 1: Plan Details -->
            <div class="section-block">
                <div class="section-title">Plan Details</div>

                <div class="detail-grid cols-4" style="margin-bottom:18px;">
                    <div class="detail-item">
                        <div class="label">Project</div>
                        <div class="value"><?= htmlspecialchars($plan['project_name'] ?? '—') ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Period</div>
                        <div class="value"><?= $monthName ?> <?= $plan['year'] ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Work Type</div>
                        <div class="value"><?= htmlspecialchars($plan['work_type_name'] ?? '—') ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Design Type</div>
                        <div class="value"><?= htmlspecialchars($plan['design_type_name'] ?? '—') ?></div>
                    </div>
                </div>

                <div class="detail-item" style="margin-bottom:18px;">
                    <div class="label" style="margin-bottom:6px;">Work Description</div>
                    <div class="description-box"><?= nl2br(htmlspecialchars($plan['work_description'] ?? '—')) ?></div>
                </div>

                <div class="detail-grid cols-4">
                    <div class="detail-item">
                        <div class="label">Planned Quantity</div>
                        <div class="value"><?= number_format($plan['planned_quantity'] ?? 0, 2) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Estimated Cost</div>
                        <div class="value primary">৳ <?= number_format($plan['estimated_cost'], 2) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Project Weight</div>
                        <div class="value success"><?= number_format($plan['weight_percentage'] ?? 0, 2) ?>%</div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Created By</div>
                        <div class="value"><?= htmlspecialchars($plan['created_by_name'] ?? '—') ?></div>
                    </div>
                </div>

                <?php if (!empty($plan['approved_by_name'])): ?>
                    <div class="detail-grid cols-2" style="margin-top:14px;">
                        <div class="detail-item">
                            <div class="label">Approved By</div>
                            <div class="value"><?= htmlspecialchars($plan['approved_by_name']) ?>
                                <?php if (!empty($plan['approved_by_role'])): ?>
                                    <small style="color:#64748b;"> (<?= htmlspecialchars($plan['approved_by_role']) ?>)</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Section 1B: Activities List & Gantt Chart -->
            <div class="section-block" style="page-break-before: auto;">
                <div class="section-title">Schedule Activities / Phases (কাজ ও অ্যাক্টিভিটি শিডিউল)</div>
                
                <table class="activities-table">
                    <thead>
                        <tr>
                            <th style="width: 5%; text-align: center;">নং</th>
                            <th style="width: 35%;">কাজের বিবরণ ও অবস্থান (Activity & Location)</th>
                            <th style="width: 15%;">ফ্লোর (Floor)</th>
                            <th style="width: 10%; text-align: center;">পরিমাণ</th>
                            <th style="width: 11%; text-align: center;">শুরু</th>
                            <th style="width: 10%; text-align: center;">দিন</th>
                            <th style="width: 11%; text-align: center;">শেষ</th>
                            <th>মন্তব্য</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($planItems)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; color: #64748b; padding: 15px;">No activities registered for this plan.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($planItems as $idx => $item): ?>
                                <tr>
                                    <td style="text-align: center; font-weight: bold;"><?= htmlspecialchars($item['sl_no'] ?: ($idx + 1)) ?></td>
                                    <td style="font-weight: 500;"><?= htmlspecialchars($item['activity_name']) ?></td>
                                    <td><?= htmlspecialchars($item['floor_name'] ?: '—') ?></td>
                                    <td style="text-align: center;"><?= htmlspecialchars($item['quantity'] ?: '1 Job') ?></td>
                                    <td style="text-align: center;"><?= date('d M Y', strtotime($item['start_date'])) ?></td>
                                    <td style="text-align: center; font-weight: 600;"><?= (int)$item['req_days'] ?> দিন</td>
                                    <td style="text-align: center;"><?= date('d M Y', strtotime($item['end_date'])) ?></td>
                                    <td style="color: #64748b; font-size: 10px;"><?= htmlspecialchars($item['remarks'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="section-title" style="margin-top: 24px; margin-bottom: 12px;">Weekly Gantt Chart (সময়সূচী গ্যান্ট চার্ট)</div>
                
                <?php
                // Gantt calculation logic
                $minDate = null;
                $maxDate = null;
                if (!empty($planItems)) {
                    foreach ($planItems as $item) {
                        $sDate = $item['start_date'];
                        $eDate = $item['end_date'];
                        if (!$minDate || $sDate < $minDate) $minDate = $sDate;
                        if (!$maxDate || $eDate > $maxDate) $maxDate = $eDate;
                    }
                }

                if (!$minDate) {
                    $minDate = sprintf('%04d-%02d-01', $plan['year'], $plan['month']);
                    $maxDate = date('Y-m-t', strtotime($minDate));
                } else {
                    // Align to nearest Monday and Sunday of start/end weeks
                    $minTime = strtotime($minDate);
                    $maxTime = strtotime($maxDate);
                    
                    $startDayOfWeek = date('N', $minTime);
                    $minTime = $minTime - ($startDayOfWeek - 1) * 86400;
                    $minDate = date('Y-m-d', $minTime);
                    
                    $endDayOfWeek = date('N', $maxTime);
                    $maxTime = $maxTime + (7 - $endDayOfWeek) * 86400;
                    $maxDate = date('Y-m-d', $maxTime);
                }

                $weeks = [];
                $currTime = strtotime($minDate);
                $endTime = strtotime($maxDate);
                $wCount = 1;
                while ($currTime <= $endTime) {
                    $wStart = date('Y-m-d', $currTime);
                    $wEnd = date('Y-m-d', $currTime + 6 * 86400);
                    $weeks[] = [
                        'label' => 'W' . $wCount,
                        'start' => $wStart,
                        'end' => $wEnd,
                        'start_display' => date('d M', strtotime($wStart)),
                        'end_display' => date('d M', strtotime($wEnd))
                    ];
                    $currTime += 7 * 86400;
                    $wCount++;
                }
                ?>

                <table class="gantt-table" style="table-layout: fixed;">
                    <thead>
                        <tr>
                            <th style="width: 200px;">Activity / Phase</th>
                            <?php foreach ($weeks as $w): ?>
                                <th style="text-align: center; font-size: 10px; padding: 4px;">
                                    <div style="color: #1e3a8a; font-weight: bold;"><?= $w['label'] ?></div>
                                    <div style="color: #64748b; font-size: 8px; font-weight: normal;"><?= $w['start_display'] ?></div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($planItems)): ?>
                            <tr>
                                <td colspan="<?= count($weeks) + 1 ?>" style="color: #64748b; padding: 15px;">No schedule data available.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($planItems as $idx => $item): 
                                $itemStart = strtotime($item['start_date']);
                                $itemEnd = strtotime($item['end_date']);
                            ?>
                                <tr>
                                    <td style="font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($item['activity_name']) ?>">
                                        <?= htmlspecialchars($item['sl_no'] ?: ($idx+1)) ?>. <?= htmlspecialchars($item['activity_name']) ?>
                                    </td>
                                    <?php foreach ($weeks as $w): 
                                        $wStart = strtotime($w['start']);
                                        $wEnd = strtotime($w['end']);
                                        
                                        // Check intersection
                                        $intersects = ($itemStart <= $wEnd && $itemEnd >= $wStart);
                                        
                                        // Check if start week
                                        $isStartWeek = ($itemStart >= $wStart && $itemStart <= $wEnd);
                                        // Check if end week
                                        $isEndWeek = ($itemEnd >= $wStart && $itemEnd <= $wEnd);
                                        
                                        $barClasses = 'gantt-bar-print';
                                        $cellClass = '';
                                        if ($intersects) {
                                            $cellClass = 'gantt-cell-active';
                                            if ($isStartWeek) $barClasses .= ' start-cap';
                                            if ($isEndWeek) $barClasses .= ' end-cap';
                                        }
                                    ?>
                                        <td class="<?= $cellClass ?>" style="padding: 2px; text-align: center; height: 32px; vertical-align: middle;">
                                            <?php if ($intersects): ?>
                                                <div class="<?= $barClasses ?>">
                                                    <?= $item['req_days'] ?>d
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Section 2: Budget Summary -->
            <div class="section-block">
                <div class="section-title">Budget Summary</div>
                <div class="budget-bar-wrapper">
                    <div class="budget-rows">
                        <div>
                            <div class="budget-row">
                                <span class="bl">Initial Budget</span>
                                <span class="bv">৳ <?= number_format($initial, 2) ?></span>
                            </div>
                            <div class="budget-row">
                                <span class="bl">Revised Total</span>
                                <span class="bv">৳ <?= number_format($revised, 2) ?></span>
                            </div>
                        </div>
                        <div>
                            <div class="budget-row">
                                <span class="bl">Total Planned</span>
                                <span class="bv" style="color:#1e3a8a;">৳ <?= number_format($planned, 2) ?></span>
                            </div>
                            <div class="budget-row">
                                <span class="bl">Remaining</span>
                                <span class="bv <?= $remaining < 0 ? 'danger' : 'success-c' ?>">
                                    ৳ <?= number_format($remaining, 2) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill <?= $pct >= 100 ? 'danger' : '' ?>"
                            style="width:<?= min(100, $pct) ?>%"></div>
                    </div>
                    <div class="progress-label">
                        <span>0%</span>
                        <span style="font-weight:600;color:#1e293b;"><?= number_format($pct, 1) ?>% Budget
                            Utilized</span>
                        <span>100%</span>
                    </div>
                </div>
            </div>

            <!-- Section 3: Monthly Plan Table -->
            <div class="section-block">
                <div class="section-title">All Monthly Plans — <?= htmlspecialchars($plan['project_name'] ?? '') ?>
                </div>
                <table class="plan-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Period</th>
                            <th>Work Type</th>
                            <th>Description</th>
                            <th class="right">Cost (৳)</th>
                            <th class="right">Weight</th>
                            <th class="center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projectPlans as $i => $p):
                            $isCurrent = $p['id'] == $plan['id'];
                            $s = $p['approved_status'] ?? 'Pending';
                            $badgeClass = $s === 'Approved' ? 'badge-ok' : ($s === 'Rejected' ? 'badge-err' : 'badge-warn');
                            ?>
                            <tr class="<?= $isCurrent ? 'current-row' : '' ?>">
                                <td><?= $i + 1 ?><?= $isCurrent ? ' ★' : '' ?></td>
                                <td><?= date('M', mktime(0, 0, 0, $p['month'], 10)) ?>     <?= $p['year'] ?></td>
                                <td><span
                                        class="badge-pill badge-info"><?= htmlspecialchars($p['work_type_name'] ?? '—') ?></span>
                                </td>
                                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    <?= htmlspecialchars($p['work_description'] ?? '—') ?>
                                </td>
                                <td class="right">৳ <?= number_format($p['estimated_cost'], 2) ?></td>
                                <td class="right"><?= number_format($p['weight_percentage'] ?? 0, 2) ?>%</td>
                                <td class="center">
                                    <span class="badge-pill <?= $badgeClass ?>"><?= $s ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" style="text-align:right;padding-right:16px;">Grand Total:</td>
                            <td class="right">৳
                                <?= number_format(array_sum(array_column($projectPlans, 'estimated_cost')), 2) ?></td>
                            <td class="right">
                                <?= number_format(array_sum(array_column($projectPlans, 'weight_percentage')), 2) ?>%
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Document Footer -->
            <div class="doc-footer">
                <div>
                    <div style="font-weight:600;color:#475569;margin-bottom:2px;">TPHL-ERP · Construction Management
                        System</div>
                    <div>Generated: <?= $printDate ?> · Plan ID: #WP-<?= str_pad($plan['id'], 5, '0', STR_PAD_LEFT) ?>
                    </div>
                </div>
                <div style="display:flex;gap:40px;">
                    <div class="sig-line">Project Manager</div>
                    <div class="sig-line">Authorized By</div>
                </div>
            </div>

        </div><!-- /.document -->

    </div><!-- /.screen-wrapper -->

</body>

</html>
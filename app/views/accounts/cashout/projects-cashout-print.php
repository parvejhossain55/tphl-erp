<?php
/**
 * View: accounts/project-cashout-print.php
 * Standalone print page — no layout, no sidebar, renders directly as a clean document.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Cashout Statement — <?= htmlspecialchars($period_label ?? '') ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 13px;
            color: #1a1a1a;
            background: #fff;
            padding: 30px;
        }

        /* ── Header ── */
        .print-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #020711;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .print-header .company-name {
            font-size: 22px;
            font-weight: 800;
            color: #00040c;
            letter-spacing: 0.5px;
        }
        .print-header .report-title {
            font-size: 13px;
            color: #555;
            margin-top: 4px;
        }
        .print-header .meta {
            text-align: right;
            font-size: 12px;
            color: #555;
            line-height: 1.8;
        }
        .print-header .meta strong {
            color: #1a1a1a;
        }

        /* ── Stat Summary Bar ── */
        .stat-bar {
            display: flex;
            gap: 0;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .stat-item {
            flex: 1;
            padding: 12px 16px;
            border-right: 1px solid #dee2e6;
            text-align: center;
        }
        .stat-item:last-child { border-right: none; }
        .stat-item .s-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #888;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .stat-item .s-value {
            font-size: 18px;
            font-weight: 800;
            font-family: 'Courier New', monospace;
        }
        .stat-item.cashout  .s-value { color: #dc3545; }
        .stat-item.txn      .s-value { color: #0c0500; }
        .stat-item.avg      .s-value { color: #000000; }
        .stat-item.largest  .s-value { color: #000000; }

        /* ── Table ── */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12px;
        }
        thead th {
            background: #000000;
            color: #fff;
            padding: 9px 10px;
            text-align: left;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            white-space: nowrap;
        }
        tbody tr:nth-child(even) { background: #f8f9fa; }
        tbody tr:hover { background: #e8f0fe; }
        tbody td {
            padding: 8px 10px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: top;
        }
        tfoot td {
            padding: 9px 10px;
            background: #1a1a1a;
            color: #fff;
            font-weight: 700;
            font-size: 12px;
        }

        .negative { color: #dc3545; font-weight: 700; }
        .mono     { font-family: 'Courier New', monospace; }

        /* ── Detail rows inside cells ── */
        .detail-item {
            padding: 3px 0;
            border-bottom: 1px dashed #e0e0e0;
            line-height: 1.6;
            font-size: 11px;
        }
        .detail-item:last-child { border-bottom: none; }
        .detail-purpose { color: #1a1a1a; font-weight: 600; }
        .detail-project {
            display: inline-block;
            background: #e8f0fe;
            color: #1a56db;
            font-size: 10px;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 3px;
            margin-left: 4px;
        }
        .detail-method {
            display: inline-block;
            background: #eee;
            color: #555;
            font-size: 10px;
            padding: 1px 5px;
            border-radius: 3px;
            margin-left: 4px;
        }
        .detail-meta { color: #888; font-size: 10px; margin-left: 4px; }
        .detail-amount { color: #dc3545; font-weight: 700; float: right; font-family: 'Courier New', monospace; }

        /* ── Footer ── */
        .print-footer {
            border-top: 2px solid #dee2e6;
            padding-top: 12px;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #888;
            margin-top: 10px;
        }

        /* ── Print button (hidden when printing) ── */
        .no-print {
            text-align: center;
            margin-bottom: 24px;
        }
        .no-print button {
            padding: 10px 28px;
            background: #1a56db;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin: 0 6px;
        }
        .no-print button.close-btn {
            background: #6c757d;
        }

        @media print {
            body { padding: 15px; }
            .no-print { display: none !important; }
            thead th { background: #00030a !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            tfoot td  { background: #1a1a1a !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .stat-item { border-right: 1px solid #dee2e6 !important; }
            tbody tr:nth-child(even) { background: #f8f9fa !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .detail-project { background: #e8f0fe !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

    <!-- Print / Close buttons -->
    <div class="no-print">
        <button onclick="window.print()">🖨 Print / Save as PDF</button>
        <button class="close-btn" onclick="window.close()">✕ Close</button>
    </div>

    <!-- ── Header ── -->
    <div class="print-header">
        <div>
            <div class="company-name">The Premium Homes LTD</div>
            <div class="report-title">Project Cashout Statement</div>
        </div>
        <div class="meta">
            <div><strong>Period:</strong> <?= htmlspecialchars($period_label ?? '—') ?></div>
            <div><strong>Project:</strong> <?= htmlspecialchars($selected_project_name ?? 'All Projects') ?></div>
            <div><strong>From:</strong> <?= htmlspecialchars(date('d M Y', strtotime($filter_start ?? 'now'))) ?></div>
            <div><strong>To:</strong> <?= htmlspecialchars(date('d M Y', strtotime($filter_end ?? 'now'))) ?></div>
            <div><strong>Generated:</strong> <?= date('d M Y, h:i A') ?></div>
        </div>
    </div>

    <!-- ── Stat Summary Bar ── -->
    <?php
    $total_cashout      = $overall_stats['total_cashout']      ?? 0;
    $total_transactions = $overall_stats['total_transactions'] ?? 0;
    $avg_payment        = $overall_stats['avg_payment']        ?? 0;
    $max_payment        = $overall_stats['max_payment']        ?? 0;
    ?>
    <div class="stat-bar">
        <div class="stat-item cashout">
            <div class="s-label">Total Cash Out</div>
            <div class="s-value">TK.<?= number_format($total_cashout, 2) ?></div>
        </div>
        <div class="stat-item txn">
            <div class="s-label">Transactions</div>
            <div class="s-value"><?= number_format($total_transactions) ?></div>
        </div>
        <div class="stat-item avg">
            <div class="s-label">Avg. Payment</div>
            <div class="s-value">TK.<?= number_format($avg_payment, 2) ?></div>
        </div>
        <div class="stat-item largest">
            <div class="s-label">Largest Payment</div>
            <div class="s-value">TK.<?= number_format($max_payment, 2) ?></div>
        </div>
    </div>

    <!-- ── Table ── -->
    <?php if (empty($statement_data)): ?>
        <p style="text-align:center; color:#888; padding:40px 0;">No cashout records found for the selected period.</p>

    <?php elseif ($period_type === 'yearly'): ?>
        <!-- Monthly breakdown for Yearly view -->
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Project</th>
                    <th style="text-align:right;">Total Cash Out (TK.)</th>
                    <th style="text-align:center;">Transactions</th>
                    <th style="text-align:right;">Running Total (TK.)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($statement_data as $row):
                    $monthPrefix   = ($row['year'] ?? '') . '-' . sprintf('%02d', $row['month_num'] ?? 0);
                    $monthProjects = [];
                    foreach ($payment_details as $date => $payments) {
                        if (strncmp($date, $monthPrefix, 7) !== 0) continue;
                        foreach ($payments as $pd) {
                            if (!empty($pd['project_name']) && !in_array($pd['project_name'], $monthProjects)) {
                                $monthProjects[] = $pd['project_name'];
                            }
                        }
                    }
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['month']) ?></strong></td>
                    <td>
                        <?php foreach ($monthProjects as $pn): ?>
                            <span class="detail-project"><?= htmlspecialchars($pn) ?></span>
                        <?php endforeach; ?>
                        <?php if (empty($monthProjects)): ?><span style="color:#aaa;">—</span><?php endif; ?>
                    </td>
                    <td style="text-align:right;" class="negative mono">TK.<?= number_format($row['total_amount'], 2) ?></td>
                    <td style="text-align:center;"><?= number_format($row['transaction_count']) ?></td>
                    <td style="text-align:right;" class="negative mono">TK.<?= number_format($row['running'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2">TOTAL</td>
                    <td style="text-align:right;" class="mono">TK.<?= number_format($total_cashout, 2) ?></td>
                    <td style="text-align:center;"><?= number_format($total_transactions) ?></td>
                    <td style="text-align:right;" class="mono">TK.<?= number_format($total_cashout, 2) ?></td>
                </tr>
            </tfoot>
        </table>

    <?php else: ?>
        <!-- Daily breakdown for Monthly / 6months / Custom view -->
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Day</th>
                    <th>Project</th>
                    <th style="text-align:right;">Cash Out (TK.)</th>
                    <th style="text-align:center;">Txns</th>
                    <th style="text-align:right;">Running (TK.)</th>
                    <th>Details — Purpose / Paid To</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($statement_data as $row):
                    $rowDate = $row['date'] ?? '';
                    $details = $payment_details[$rowDate] ?? [];

                    $projectNames = [];
                    foreach ($details as $d) {
                        if (!empty($d['project_name']) && !in_array($d['project_name'], $projectNames)) {
                            $projectNames[] = $d['project_name'];
                        }
                    }
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['formatted_date'] ?? '') ?></strong></td>
                    <td style="color:#6c757d;"><?= htmlspecialchars($row['day_name'] ?? '') ?></td>
                    <td>
                        <?php foreach ($projectNames as $pn): ?>
                            <span class="detail-project"><?= htmlspecialchars($pn) ?></span>
                        <?php endforeach; ?>
                        <?php if (empty($projectNames)): ?><span style="color:#aaa;">—</span><?php endif; ?>
                    </td>
                    <td style="text-align:right;" class="negative mono">TK.<?= number_format($row['total_amount'], 2) ?></td>
                    <td style="text-align:center;"><?= number_format($row['transaction_count']) ?></td>
                    <td style="text-align:right;" class="negative mono">TK.<?= number_format($row['running'], 2) ?></td>
                    <td>
                        <?php if (!empty($details)): ?>
                            <?php foreach ($details as $d): ?>
                                <div class="detail-item">
                                    <span class="detail-amount">TK.<?= number_format($d['amount'] ?? 0, 2) ?></span>
                                    <span class="detail-purpose"><?= htmlspecialchars($d['purpose_paid_to'] ?? '—') ?></span>
                                    <span class="detail-method"><?= ucfirst(htmlspecialchars($d['payment_method'] ?? 'cash')) ?></span>
                                    <?php if (!empty($d['bank_name'])): ?>
                                        <span class="detail-meta">· <?= htmlspecialchars($d['bank_name']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($d['transaction_ref'])): ?>
                                        <span class="detail-meta">Ref: <?= htmlspecialchars($d['transaction_ref']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($d['cheque_no'])): ?>
                                        <span class="detail-meta">Chq: <?= htmlspecialchars($d['cheque_no']) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span style="color:#aaa;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">TOTAL</td>
                    <td style="text-align:right;" class="mono">TK.<?= number_format($total_cashout, 2) ?></td>
                    <td style="text-align:center;"><?= number_format($total_transactions) ?></td>
                    <td style="text-align:right;" class="mono">TK.<?= number_format($total_cashout, 2) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>

    <!-- ── Footer ── -->
    <div class="print-footer">
        <span>The Premium Homes Limited — Project Cashout Statement</span>
        <span><?= htmlspecialchars($period_label ?? '') ?> | <?= htmlspecialchars($selected_project_name ?? 'All Projects') ?></span>
        <span>Printed: <?= date('d M Y, h:i A') ?></span>
    </div>

</body>
</html>
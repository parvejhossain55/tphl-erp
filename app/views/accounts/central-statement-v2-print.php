<?php
$fmtBDT = function (float $val): string {
    $abs = abs($val);
    $sign = $val < 0 ? '−' : '';
    $parts = explode('.', number_format($abs, 2, '.', ''));
    $intPart = $parts[0];
    $decPart = $parts[1];
    $len = strlen($intPart);
    if ($len > 3) {
        $lastThree = substr($intPart, -3);
        $rest = substr($intPart, 0, $len - 3);
        $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
        $formatted = $rest . ',' . $lastThree;
    } else {
        $formatted = $intPart;
    }
    return $sign . '৳' . $formatted . '.' . $decPart;
};
$s = $overview['summary'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>The Premium Homes Ltd. — Central Financial Statement</title>
    <style>
        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #0f172a; margin: 0; padding: 20px 26px 56px 26px; }
        .company-pad { text-align: center; border-bottom: 2px solid #14532d; padding-bottom: 10px; margin-bottom: 12px; position: relative; }
        .company-name { font-size: 19px; font-weight: 800; letter-spacing: 1.5px; color: #0f172a; margin: 0; }
        .company-addr { font-size: 10.5px; color: #475569; margin-top: 3px; line-height: 1.4; }
        .report-heading { font-size: 14px; font-weight: 800; letter-spacing: 1.2px; color: #14532d; margin-top: 8px; text-transform: uppercase; }
        .meta-bar { display: flex; justify-content: space-between; align-items: center; font-size: 10.5px; color: #475569; margin-bottom: 10px; }
        .chips { margin-bottom: 12px; }
        .chip { display: inline-block; background: #ecfdf5; border: 1px solid #bbf7d0; color: #14532d; padding: 2px 8px; border-radius: 12px; font-size: 10px; margin-right: 6px; }
        .kpi-row { display: table; width: 100%; border-collapse: separate; border-spacing: 8px 0; margin-bottom: 14px; }
        .kpi-box { display: table-cell; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 10px; width: 20%; }
        .kpi-label { font-size: 10px; color: #64748b; font-weight: 700; }
        .kpi-val { font-size: 13.5px; font-weight: 700; margin-top: 4px; }
        table.rep { width: 100%; border-collapse: collapse; margin-top: 8px; margin-bottom: 18px; }
        table.rep th { background: #f1f5f9; border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; font-size: 11px; }
        table.rep td { border: 1px solid #e2e8f0; padding: 5px 8px; font-size: 11px; }
        .num { text-align: right; font-variant-numeric: tabular-nums; }
        .pos { color: #166534; }
        .neg { color: #b91c1c; }
        .tot td { background: #f0fdf4; font-weight: 700; border-top: 2px solid #14532d; }
        .print-footer {
            border-top: 1px solid #cbd5e1;
            padding-top: 6px;
            font-size: 9.5px;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 24px;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 12mm 13mm 18mm 13mm; }
            .print-footer {
                position: fixed;
                bottom: 7mm;
                left: 13mm;
                right: 13mm;
                background: #fff;
            }
        }
    </style>
</head>
<body>
    <div class="company-pad">
        <button class="no-print" onclick="window.print()" style="position:absolute; right:0; top:0; padding:7px 14px; background:#14532d; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:600;">Print / Save PDF</button>
        <div class="company-name">THE PREMIUM HOMES LTD.</div>
        <div class="company-addr">
            Land View Commercial Center, 9th Floor, 28 Gulshan North C/A, Gulshan Circle-2, Dhaka<br>
            Phone: +8801958253300 | Email: info@thepremiumhomes.com
        </div>
        <div class="report-heading">CENTRAL FINANCIAL STATEMENT</div>
    </div>
    <div class="meta-bar">
        <span><strong>Period:</strong> <?= htmlspecialchars($period_label) ?> (<?= htmlspecialchars($start_date) ?> to <?= htmlspecialchars($end_date) ?>)</span>
        <span><strong>Section:</strong> <?= htmlspecialchars(strtoupper($activeTab)) ?> &nbsp;|&nbsp; <strong>Generated:</strong> <?= date('d M Y, H:i') ?></span>
    </div>

    <div class="chips">
        <?php foreach ($appliedChips as $c): ?>
            <span class="chip"><?= htmlspecialchars($c['label']) ?>: <?= htmlspecialchars($c['value']) ?></span>
        <?php endforeach; ?>
    </div>

    <div class="kpi-row">
        <div class="kpi-box"><div class="kpi-label">Opening Balance</div><div class="kpi-val"><?= $fmtBDT($s['opening_balance']) ?></div></div>
        <div class="kpi-box"><div class="kpi-label">Cash In (Verified)</div><div class="kpi-val pos"><?= $fmtBDT($s['cash_in']) ?></div></div>
        <div class="kpi-box"><div class="kpi-label">Cash Out (Paid)</div><div class="kpi-val"><?= $fmtBDT($s['cash_out']) ?></div></div>
        <div class="kpi-box"><div class="kpi-label">Net Movement</div><div class="kpi-val <?= $s['net_movement'] >= 0 ? 'pos' : 'neg' ?>"><?= $fmtBDT($s['net_movement']) ?></div></div>
        <div class="kpi-box"><div class="kpi-label">Closing Balance</div><div class="kpi-val pos"><?= $fmtBDT($s['closing_balance']) ?></div></div>
    </div>

    <?php if ($activeTab === 'branch'): $bt = $branchData['totals']; ?>
        <h3>Branch Performance</h3>
        <table class="rep">
            <thead><tr><th>Branch / Office</th><th class="num">Count</th><th class="num">Collections</th><th class="num">Direct Cost</th><th class="num">Net Movement</th></tr></thead>
            <tbody>
                <?php foreach ($branchData['rows'] as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['branch_name']) ?></td>
                        <td class="num"><?= $r['txn_count'] ?></td>
                        <td class="num pos"><?= $fmtBDT($r['collections']) ?></td>
                        <td class="num"><?= $fmtBDT($r['direct_cost']) ?></td>
                        <td class="num <?= $r['net'] >= 0 ? 'pos' : 'neg' ?>"><?= $fmtBDT($r['net']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="tot"><td>Total</td><td class="num"><?= $bt['txn_count'] ?></td><td class="num pos"><?= $fmtBDT($bt['collections']) ?></td><td class="num"><?= $fmtBDT($bt['direct_cost']) ?></td><td class="num"><?= $fmtBDT($bt['net']) ?></td></tr>
            </tbody>
        </table>
    <?php elseif ($activeTab === 'project'): $pt = $projectData['totals']; ?>
        <h3>Project &amp; Category Breakdown</h3>
        <table class="rep">
            <thead><tr><th>Project / Category</th><th class="num">Count</th><th class="num">Cash In</th><th class="num">Cash Out</th><th class="num">Net Movement</th></tr></thead>
            <tbody>
                <?php foreach ($projectData['projects'] as $p): ?>
                    <tr style="background:#f8fafc; font-weight:700;">
                        <td><?= htmlspecialchars($p['project_name']) ?></td>
                        <td class="num"><?= $p['txn_count'] ?></td>
                        <td class="num pos"><?= $fmtBDT($p['cash_in']) ?></td>
                        <td class="num"><?= $fmtBDT($p['cash_out']) ?></td>
                        <td class="num <?= $p['net'] >= 0 ? 'pos' : 'neg' ?>"><?= $fmtBDT($p['net']) ?></td>
                    </tr>
                    <?php foreach ($p['categories'] as $c): ?>
                        <tr>
                            <td style="padding-left:24px;"><?= htmlspecialchars($c['category_label']) ?></td>
                            <td class="num"><?= $c['txn_count'] ?></td>
                            <td class="num pos"><?= $fmtBDT($c['cash_in']) ?></td>
                            <td class="num"><?= $fmtBDT($c['cash_out']) ?></td>
                            <td class="num <?= $c['net'] >= 0 ? 'pos' : 'neg' ?>"><?= $fmtBDT($c['net']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                <tr class="tot"><td>Total</td><td class="num"><?= $pt['txn_count'] ?></td><td class="num pos"><?= $fmtBDT($pt['cash_in']) ?></td><td class="num"><?= $fmtBDT($pt['cash_out']) ?></td><td class="num"><?= $fmtBDT($pt['net']) ?></td></tr>
            </tbody>
        </table>
    <?php elseif ($activeTab === 'emi_cost'): $et = $emiCostData['totals']; ?>
        <h3>EMI vs Project Cost &amp; Pending Dues</h3>
        <table class="rep">
            <thead><tr><th>Project</th><th class="num">Collected</th><th class="num">Project Cost Paid</th><th class="num">Coverage</th><th class="num">Surplus / Shortfall</th><th class="num">Overdue EMI</th><th class="num">Unpaid Bills</th></tr></thead>
            <tbody>
                <?php foreach ($emiCostData['rows'] as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['project_name']) ?></td>
                        <td class="num pos"><?= $fmtBDT($r['collected']) ?></td>
                        <td class="num"><?= $fmtBDT($r['cost_paid']) ?></td>
                        <td class="num"><?= $r['coverage_pct'] !== null ? $r['coverage_pct'] . '%' : '—' ?></td>
                        <td class="num <?= $r['surplus'] >= 0 ? 'pos' : 'neg' ?>"><?= $fmtBDT($r['surplus']) ?></td>
                        <td class="num"><?= $fmtBDT($r['overdue_emi']) ?></td>
                        <td class="num"><?= $fmtBDT($r['unpaid_bills']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="tot"><td>Total</td><td class="num pos"><?= $fmtBDT($et['collected']) ?></td><td class="num"><?= $fmtBDT($et['cost_paid']) ?></td><td class="num"><?= $et['coverage_pct'] !== null ? $et['coverage_pct'] . '%' : '—' ?></td><td class="num"><?= $fmtBDT($et['surplus']) ?></td><td class="num"><?= $fmtBDT($et['overdue_emi']) ?></td><td class="num"><?= $fmtBDT($et['unpaid_bills']) ?></td></tr>
            </tbody>
        </table>
    <?php else: ?>
        <h3>Central Statement Intervals</h3>
        <table class="rep">
            <thead><tr><th>Date / Month</th><th class="num">Opening</th><th class="num">Cash In</th><th class="num">Cash Out</th><th class="num">Net Movement</th><th class="num">Closing</th></tr></thead>
            <tbody>
                <?php foreach ($overview['intervals'] as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['label']) ?></td>
                        <td class="num"><?= $fmtBDT($r['opening']) ?></td>
                        <td class="num pos"><?= $fmtBDT($r['cash_in']) ?></td>
                        <td class="num"><?= $fmtBDT($r['cash_out']) ?></td>
                        <td class="num <?= $r['net'] >= 0 ? 'pos' : 'neg' ?>"><?= $fmtBDT($r['net']) ?></td>
                        <td class="num"><?= $fmtBDT($r['closing']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="tot"><td>Total</td><td class="num"><?= $fmtBDT($s['opening_balance']) ?></td><td class="num pos"><?= $fmtBDT($s['cash_in']) ?></td><td class="num"><?= $fmtBDT($s['cash_out']) ?></td><td class="num"><?= $fmtBDT($s['net_movement']) ?></td><td class="num pos"><?= $fmtBDT($s['closing_balance']) ?></td></tr>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="print-footer">
        <span><strong>CONFIDENTIAL</strong> — Internal Financial Statement | The Premium Homes Ltd.</span>
        <span>System Generated Report · Do not distribute externally</span>
    </div>
</body>
</html>

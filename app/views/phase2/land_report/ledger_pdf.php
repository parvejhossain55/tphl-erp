<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($page_title) ?></title>
    <style>
        body {
            font-family: 'Helvetica', Arial, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 18px;
            margin: 0 0 5px 0;
            color: #0d6efd;
        }
        .header h3 {
            font-size: 12px;
            margin: 0 0 5px 0;
            color: #555;
            font-weight: normal;
        }
        .header .meta {
            font-size: 9px;
            color: #777;
        }
        .filters {
            width: 100%;
            margin-bottom: 15px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .filters td {
            padding: 3px 0;
            font-size: 10px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .summary-table td {
            width: 25%;
            padding: 8px;
            border: 1px solid #dee2e6;
            background-color: #f8f9fa;
        }
        .summary-title {
            font-size: 9px;
            text-transform: uppercase;
            color: #6c757d;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .summary-val {
            font-size: 13px;
            font-weight: bold;
        }
        .text-primary { color: #0d6efd; }
        .text-success { color: #198754; }
        .text-danger { color: #dc3545; }
        .text-warning { color: #ffc107; }
        .text-muted { color: #6c757d; }
        
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
        }
        .ledger-table th {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 8px 6px;
            font-weight: bold;
            text-align: left;
        }
        .ledger-table td {
            border: 1px solid #dee2e6;
            padding: 6px;
            vertical-align: top;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 9px;
            font-weight: bold;
            border-radius: 3px;
            text-align: center;
        }
        .bg-success { background-color: #d1e7dd; color: #0f5132; }
        .bg-info { background-color: #cff4fc; color: #055160; }
        .bg-warning { background-color: #fff3cd; color: #664d03; }
        .bg-danger { background-color: #f8d7da; color: #842029; }
        .bg-secondary { background-color: #e2e3e5; color: #41464b; }
    </style>
</head>
<body>

    <div class="header">
        <h1>THE PREMIUM HOMES LIMITED</h1>
        <h3>Land Department Ledger Statement</h3>
        <div class="meta">Generated: <?= date('d M Y, h:i A') ?></div>
    </div>

    <table class="filters">
        <tr>
            <td width="50%">
                <strong>Project:</strong> 
                <?php 
                    $projName = '-- All Projects --';
                    if ($selectedProjectId) {
                        foreach ($projects as $p) {
                            if ($p['id'] == $selectedProjectId) {
                                $projName = $p['name'];
                                break;
                            }
                        }
                    }
                    echo htmlspecialchars($projName);
                ?>
            </td>
            <td width="50%" align="right">
                <strong>Date Range:</strong> 
                <?php 
                    if ($from_date || $to_date) {
                        echo ($from_date ? date('d M Y', strtotime($from_date)) : 'Beginning') . ' to ' . ($to_date ? date('d M Y', strtotime($to_date)) : 'Present');
                    } else {
                        echo 'All Dates';
                    }
                ?>
            </td>
        </tr>
        <tr>
            <td>
                <strong>Land Portfolio:</strong>
                <?php 
                    $acqName = '-- All Land Portfolios --';
                    if ($selectedAcqId) {
                        foreach ($acquisitions as $acq) {
                            if ($acq['id'] == $selectedAcqId) {
                                $acqName = $acq['land_owner_name'] . ' (' . $acq['project_name'] . ')';
                                break;
                            }
                        }
                    }
                    echo htmlspecialchars($acqName);
                ?>
            </td>
            <td></td>
        </tr>
    </table>

    <table class="summary-table">
        <tr>
            <td>
                <div class="summary-title">Total Valuation Budget</div>
                <div class="summary-val text-primary">Tk. <?= number_format($totalValuation, 2) ?></div>
            </td>
            <td>
                <div class="summary-title">Fund Received (Paid)</div>
                <div class="summary-val text-success">Tk. <?= number_format($totalReceived, 2) ?></div>
            </td>
            <td>
                <div class="summary-title">Total Actual Spent</div>
                <div class="summary-val text-danger">Tk. <?= number_format($totalSpent, 2) ?></div>
            </td>
            <td>
                <div class="summary-title">Cash / Budget Balance</div>
                <div class="summary-val text-warning">Tk. <?= number_format($balanceCash, 2) ?></div>
            </td>
        </tr>
    </table>

    <table class="ledger-table">
        <thead>
            <tr>
                <th width="12%">Date &amp; Time</th>
                <th width="42%">Description &amp; Project Info</th>
                <th width="10%">Type</th>
                <th width="8%" class="text-center">Status</th>
                <th width="9%" class="text-end">Inflow (Received)</th>
                <th width="9%" class="text-end">Outflow (Spent)</th>
                <th width="10%" class="text-end">Running Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($from_date): ?>
                <tr style="background-color: #f8f9fa;">
                    <td><strong><?= date('d M Y', strtotime($from_date)) ?></strong></td>
                    <td>
                        <strong>Opening Balance (Before <?= date('d M Y', strtotime($from_date)) ?>)</strong><br>
                        <span class="text-muted">Brought forward balance</span>
                    </td>
                    <td><span class="badge bg-secondary">Balance B/F</span></td>
                    <td class="text-center">—</td>
                    <td class="text-end">—</td>
                    <td class="text-end">—</td>
                    <td class="text-end fw-bold">Tk. <?= number_format($openingBalance, 2) ?></td>
                </tr>
            <?php endif; ?>

            <?php if (empty($ledgerEntries)): ?>
                <tr>
                    <td colspan="7" class="text-center" style="padding: 30px; color: #777;">
                        No transaction entries found for the selected criteria.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($ledgerEntries as $entry): ?>
                    <?php 
                        $isRequisition = ($entry['type'] === 'requisition');
                        $isPaid = ($entry['status'] === 'Paid');
                        
                        if ($isRequisition && !$isPaid) {
                            continue; // Skip unpaid requisitions in PDF export as they are not realized cash flows
                        }
                    ?>
                    <tr>
                        <td>
                            <?= date('d M Y', strtotime($entry['date'])) ?><br>
                            <span class="text-muted" style="font-size: 8px;"><?= date('h:i A', strtotime($entry['date'])) ?></span>
                        </td>
                        <td>
                            <div class="fw-bold"><?= $entry['description'] ?></div>
                            <div class="text-muted" style="font-size: 8px; margin-top: 3px;">
                                Landowner: <?= htmlspecialchars($entry['landowner']) ?> &nbsp;·&nbsp; Project: <?= htmlspecialchars($entry['project_name']) ?>
                            </div>
                            <?php if (!$isRequisition || $isPaid): ?>
                                <div class="text-muted" style="font-size: 8px; margin-top: 2px;">
                                    Method: <?= htmlspecialchars($entry['details']['pay_method']) ?> &nbsp;·&nbsp; Ref/Cheque/Txn: <?= htmlspecialchars($entry['details']['txn_no']) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-secondary"><?= htmlspecialchars($entry['requisition_type']) ?></span>
                        </td>
                        <td class="text-center">
                            <?php if ($isPaid): ?>
                                <span class="badge bg-success">Paid</span>
                            <?php elseif ($entry['status'] === 'Approved'): ?>
                                <span class="badge bg-info">Approved</span>
                            <?php elseif ($entry['status'] === 'Rejected'): ?>
                                <span class="badge bg-danger">Rejected</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-success">
                            <?= $entry['inflow'] > 0 ? 'Tk. ' . number_format($entry['inflow'], 2) : '—' ?>
                        </td>
                        <td class="text-end text-danger">
                            <?= $entry['outflow'] > 0 ? 'Tk. ' . number_format($entry['outflow'], 2) : '—' ?>
                        </td>
                        <td class="text-end fw-bold">
                            Tk. <?= number_format($entry['running_balance'], 2) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>

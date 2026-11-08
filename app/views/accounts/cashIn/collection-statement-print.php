<?php
// Collection Statement Printable Template
$company_name = "THE PREMIUM HOMES LTD.";
$company_address = "Land View Commercial Center, 9th Floor, 28 Gulshan North C/A, Gulshan Circle-2, Dhaka-1212";
$company_phone = "+8801958253300";
$company_email = "info@thepremiumhomes.com";

$paymentTypeNames = [
    'booking_money'       => 'Booking Money',
    'registration'        => 'Registration',
    'land_share'          => 'Land Share',
    'emi'                 => 'EMI (Monthly)',
    'development_charge'  => 'Development Charge',
    'shop_service_charge' => 'Shop Service Charge',
    'emi_due_settlement'  => 'EMI Due Settlement',
    'management'          => 'Management Cash In',
    'others'              => 'Other Receipt'
];

$methodNames = [
    'bank'   => 'Bank',
    'cash'   => 'Cash',
    'cheque' => 'Cheque',
    'online' => 'Online/Transfer'
];

$activePeriodLabel = $date_info['label'] ?? 'Custom Date Range';
$startDateFormatted = !empty($date_info['start_date']) ? date('d M Y', strtotime($date_info['start_date'])) : 'Beginning';
$endDateFormatted = !empty($date_info['end_date']) ? date('d M Y', strtotime($date_info['end_date'])) : 'Today';

$activeMethod = !empty($filters['payment_method']) ? ($methodNames[$filters['payment_method']] ?? ucfirst($filters['payment_method'])) : 'All Methods (Bank, Cash, Cheque, Online)';
$activeType = !empty($filters['payment_type']) ? ($paymentTypeNames[$filters['payment_type']] ?? ucfirst($filters['payment_type'])) : 'All Payment Types';
$activeSource = !empty($filters['source']) ? ucfirst($filters['source']) . ' Cash In' : 'All Sources (Client & Management)';
$activeBank = !empty($filters['bank_name']) ? $filters['bank_name'] : 'All Banks';
$activeStatus = !empty($filters['verification_status']) ? ucfirst($filters['verification_status']) : 'All (Verified, Pending, Rejected)';

$totalAmt = $summary['total_collection'] ?? 0;
$bankAmt  = $summary['bank_collection'] ?? 0;
$cashAmt  = $summary['cash_collection'] ?? 0;
$chequeAmt= $summary['cheque_collection'] ?? 0;
$onlineAmt= $summary['online_collection'] ?? 0;

$clientAmt = $summary['client_collection'] ?? 0;
$mgmtAmt   = $summary['management_collection'] ?? 0;

$typeBreakdown = $summary['type_breakdown'] ?? [];
$bankBreakdown = $summary['bank_breakdown'] ?? [];

$typeSummary = $type_summary ?? ['rows' => [], 'grand' => ['count' => 0, 'total_amount' => 0, 'bank_amount' => 0, 'cash_amount' => 0, 'cheque_amount' => 0, 'online_amount' => 0]];
$printMode   = $print_mode ?? ($_GET['print_mode'] ?? 'full');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Total Collection Statement - <?= htmlspecialchars($activePeriodLabel) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm 6mm;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            font-size: 11px;
            line-height: 1.4;
            padding: 20px;
        }

        .no-print-bar {
            background: #0f172a;
            color: #fff;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-primary {
            background-color: #2563eb;
            color: #fff;
        }
        .btn-primary:hover {
            background-color: #1d4ed8;
        }

        .btn-secondary {
            background-color: #475569;
            color: #fff;
        }
        .btn-secondary:hover {
            background-color: #334155;
        }

        .print-container {
            background: #fff;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }

        /* Company Header */
        .company-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }

        .company-title {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
        }

        .company-sub {
            font-size: 10px;
            color: #64748b;
            margin-top: 3px;
        }

        .statement-title-badge {
            text-align: right;
        }

        .statement-title {
            font-size: 17px;
            font-weight: 800;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .statement-bengali {
            font-size: 12px;
            color: #475569;
            font-weight: 600;
            margin-top: 2px;
        }

        .statement-meta {
            font-size: 10px;
            color: #64748b;
            margin-top: 4px;
        }

        /* Filter overview pill grid */
        .filter-badges {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 8px;
            margin-bottom: 14px;
            background: #f1f5f9;
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 10.5px;
            border: 1px solid #e2e8f0;
        }

        .filter-item {
            display: inline-flex;
            gap: 4px;
        }

        .filter-item strong {
            color: #334155;
        }

        /* Summary Stats Cards */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 10px;
            margin-bottom: 14px;
        }

        .summary-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 12px;
            border-left: 4px solid #3b82f6;
        }

        .summary-card.blue   { border-left-color: #2563eb; }
        .summary-card.cyan   { border-left-color: #0284c7; }
        .summary-card.green  { border-left-color: #16a34a; }
        .summary-card.amber  { border-left-color: #d97706; }
        .summary-card.purple { border-left-color: #7c3aed; }
        .summary-card.slate  { border-left-color: #475569; }

        .summary-card-label {
            font-size: 9.5px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
        }

        .summary-card-value {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            margin-top: 3px;
        }

        .summary-card-sub {
            font-size: 9px;
            color: #64748b;
            margin-top: 2px;
        }

        /* Mini Breakdowns */
        .breakdown-row {
            display: flex;
            gap: 12px;
            margin-bottom: 14px;
            font-size: 10px;
        }

        .breakdown-col {
            flex: 1;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 12px;
        }

        .breakdown-title {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 6px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
        }

        .breakdown-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px 12px;
        }

        .breakdown-item {
            display: inline-flex;
            gap: 4px;
        }

        .breakdown-item span.lbl {
            color: #64748b;
        }

        .breakdown-item span.val {
            font-weight: 700;
            color: #0f172a;
        }

        /* Data Table */
        table.statement-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-bottom: 16px;
        }

        table.statement-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: 700;
            text-align: left;
            padding: 6px 8px;
            font-size: 9.5px;
            letter-spacing: 0.3px;
            border: 1px solid #0f172a;
        }

        table.statement-table th.text-center,
        table.statement-table td.text-center {
            text-align: center;
        }

        table.statement-table th.text-end,
        table.statement-table td.text-end {
            text-align: right;
        }

        table.statement-table td {
            padding: 5px 8px;
            border: 1px solid #cbd5e1;
            color: #1e293b;
            vertical-align: middle;
        }

        table.statement-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        table.statement-table tfoot td {
            background-color: #e2e8f0;
            font-size: 10.5px;
            font-weight: 800;
            border-top: 2px solid #0f172a;
            padding: 7px 8px;
        }

        /* Payment Type Wise Statement Matrix Table */
        table.type-summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            margin-bottom: 14px;
            font-size: 10.5px;
        }
        table.type-summary-table th {
            background-color: #0f172a;
            color: #fff;
            padding: 7px 8px;
            font-weight: 700;
            border: 1px solid #0f172a;
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        table.type-summary-table td {
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            color: #1e293b;
            vertical-align: middle;
        }
        table.type-summary-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        table.type-summary-table tfoot td {
            background-color: #e2e8f0;
            font-size: 11px;
            font-weight: 800;
            border-top: 2px solid #0f172a;
            padding: 8px;
        }

        .badge-type {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .type-booking { background: #dbeafe; color: #1e40af; }
        .type-reg     { background: #e0e7ff; color: #3730a3; }
        .type-land    { background: #dcfce7; color: #15803d; }
        .type-emi     { background: #fef3c7; color: #92400e; }
        .type-dev     { background: #ffedd5; color: #9a3412; }
        .type-shop    { background: #ede9fe; color: #6b21a8; }
        .type-mgmt    { background: #f1f5f9; color: #0f172a; }

        .method-tag {
            font-weight: 600;
            color: #0f172a;
        }

        .bank-details {
            font-size: 8.5px;
            color: #475569;
        }

        /* Signatures */
        .signature-section {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 40px;
            padding-top: 10px;
            page-break-inside: avoid;
        }

        .signature-box {
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #0f172a;
            margin-bottom: 6px;
        }

        .signature-title {
            font-size: 10px;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .signature-sub {
            font-size: 8.5px;
            color: #64748b;
        }

        /* Screen preview responsive styling for mobile & tablets */
        @media screen and (max-width: 768px) {
            body {
                padding: 10px;
            }
            .no-print-bar {
                flex-direction: column;
                gap: 12px;
                text-align: center;
                padding: 12px;
            }
            .no-print-bar > div:last-child {
                display: flex;
                width: 100%;
                gap: 8px;
            }
            .no-print-bar .btn {
                flex: 1;
                justify-content: center;
            }
            .print-container {
                padding: 14px;
                overflow-x: auto;
            }
            .company-header {
                flex-direction: column;
                gap: 10px;
            }
            .statement-title-badge {
                text-align: left;
            }
            .stat-cards-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 8px;
            }
            table.statement-table,
            table.type-summary-table,
            table.bank-summary-table {
                min-width: 680px;
            }
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .no-print-bar {
                display: none !important;
            }
            .print-container {
                box-shadow: none;
                padding: 0;
            }
            table.statement-table th {
                background-color: #1e293b !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            table.statement-table tbody tr:nth-child(even) {
                background-color: #f1f5f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            table.statement-table tfoot td {
                background-color: #e2e8f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            table.type-summary-table th {
                background-color: #0f172a !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            table.type-summary-table tbody tr:nth-child(even) {
                background-color: #f1f5f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            table.type-summary-table tfoot td {
                background-color: #e2e8f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .summary-card {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

    <!-- Non-printable top action bar -->
    <div class="no-print-bar">
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <strong style="font-size: 13px;"><i class="fas fa-file-invoice-dollar"></i> Collection Statement Print</strong>
            <span style="font-size: 11px; color: #94a3b8;">
                Mode: <strong><?= strtoupper(htmlspecialchars(str_replace('_', ' ', $printMode))) ?></strong> | Total: ৳<?= number_format($totalAmt, 2) ?>
            </span>
            <div style="display: inline-flex; gap: 4px; background: #1e293b; padding: 3px; border-radius: 6px;">
                <a href="?<?= http_build_query(array_merge($filters, ['print_mode' => 'type_summary'])) ?>" class="btn <?= $printMode === 'type_summary' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 4px 10px; font-size: 11px;">
                    <i class="fas fa-chart-pie"></i> Payment Types
                </a>
                <a href="?<?= http_build_query(array_merge($filters, ['print_mode' => 'bank_summary'])) ?>" class="btn <?= $printMode === 'bank_summary' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 4px 10px; font-size: 11px;">
                    <i class="fas fa-university"></i> Bank Accounts
                </a>
                <a href="?<?= http_build_query(array_merge($filters, ['print_mode' => 'full'])) ?>" class="btn <?= $printMode === 'full' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 4px 10px; font-size: 11px;">
                    <i class="fas fa-file-invoice"></i> Combined Statement
                </a>
                <a href="?<?= http_build_query(array_merge($filters, ['print_mode' => 'transactions'])) ?>" class="btn <?= $printMode === 'transactions' ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 4px 10px; font-size: 11px;">
                    <i class="fas fa-list-ol"></i> Detailed Register
                </a>
            </div>
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print / Save as PDF
            </button>
            <a href="collection-statement?<?= http_build_query($filters) ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to ERP
            </a>
        </div>
    </div>

    <!-- Main Printable Paper Container -->
    <div class="print-container">

        <!-- Company Header -->
        <div class="company-header">
            <div>
                <div class="company-title"><?= htmlspecialchars($company_name) ?></div>
                <div class="company-sub"><?= htmlspecialchars($company_address) ?></div>
                <div class="company-sub">
                    <i class="fas fa-phone-alt"></i> <?= htmlspecialchars($company_phone) ?> | 
                    <i class="fas fa-envelope"></i> <?= htmlspecialchars($company_email) ?>
                </div>
            </div>
            <div class="statement-title-badge">
                <div class="statement-title">Total Collection Statement</div>
                <div class="statement-subtitle" style="font-size: 11px; font-weight: 600; color: #475569; margin-top: 2px;">Comprehensive Collection &amp; Inflow Summary Statement</div>
                <div class="statement-meta">
                    <strong>Period:</strong> <?= htmlspecialchars($activePeriodLabel) ?> (<?= $startDateFormatted ?> to <?= $endDateFormatted ?>)<br>
                    <strong>Printed On:</strong> <?= date('d M Y, h:i A') ?>
                </div>
            </div>
        </div>

        <!-- Filter Metadata Badges -->
        <div class="filter-badges">
            <div class="filter-item"><strong>Project:</strong> <?= htmlspecialchars($project_name) ?></div>
            <div class="filter-item"><strong>Source:</strong> <?= htmlspecialchars($activeSource) ?></div>
            <div class="filter-item"><strong>Payment Method:</strong> <?= htmlspecialchars($activeMethod) ?></div>
            <div class="filter-item"><strong>Payment Type:</strong> <?= htmlspecialchars($activeType) ?></div>
            <div class="filter-item"><strong>Bank Account:</strong> <?= htmlspecialchars($activeBank) ?></div>
            <div class="filter-item"><strong>Status:</strong> <?= htmlspecialchars($activeStatus) ?></div>
        </div>

        <!-- Executive Financial Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card blue">
                <div class="summary-card-label">Total Collection</div>
                <div class="summary-card-value">৳<?= number_format($totalAmt, 2) ?></div>
                <div class="summary-card-sub"><?= number_format($summary['total_count'] ?? count($transactions)) ?> transactions<?php if (!empty($summary['total_sold_value'])): ?> · Sold: <?= $summary['total_sold_value_formatted'] ?> (<?= $summary['collection_rate'] ?>% Realized)<?php endif; ?></div>
            </div>
            <div class="summary-card cyan">
                <div class="summary-card-label">Bank Collection</div>
                <div class="summary-card-value">৳<?= number_format($bankAmt, 2) ?></div>
                <div class="summary-card-sub"><?= $totalAmt > 0 ? round(($bankAmt / $totalAmt) * 100, 1) : 0 ?>% of total</div>
            </div>
            <div class="summary-card green">
                <div class="summary-card-label">Cash Collection</div>
                <div class="summary-card-value">৳<?= number_format($cashAmt, 2) ?></div>
                <div class="summary-card-sub"><?= $totalAmt > 0 ? round(($cashAmt / $totalAmt) * 100, 1) : 0 ?>% of total</div>
            </div>
            <div class="summary-card amber">
                <div class="summary-card-label">Cheque</div>
                <div class="summary-card-value">৳<?= number_format($chequeAmt, 2) ?></div>
                <div class="summary-card-sub"><?= $totalAmt > 0 ? round(($chequeAmt / $totalAmt) * 100, 1) : 0 ?>% of total</div>
            </div>
            <div class="summary-card purple">
                <div class="summary-card-label">Online / Transfer</div>
                <div class="summary-card-value">৳<?= number_format($onlineAmt, 2) ?></div>
                <div class="summary-card-sub"><?= $totalAmt > 0 ? round(($onlineAmt / $totalAmt) * 100, 1) : 0 ?>% of total</div>
            </div>
            <div class="summary-card slate">
                <div class="summary-card-label">Client vs Mgmt</div>
                <div class="summary-card-value" style="font-size: 11px;">
                    C: ৳<?= number_format($clientAmt, 0) ?><br>
                    M: ৳<?= number_format($mgmtAmt, 0) ?>
                </div>
            </div>
        </div>

        <?php if ($printMode === 'type_summary' || $printMode === 'full'): ?>
        <!-- Payment Type Wise Collection Statement Matrix Table -->
        <div style="margin-top: 14px; margin-bottom: 6px; font-weight: 700; font-size: 11.5px; color: #0f172a; border-bottom: 2px solid #0f172a; padding-bottom: 4px; display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fas fa-chart-pie"></i> Payment Type Wise Collection Statement</span>
            <span style="font-size: 9.5px; color: #475569; font-weight: 600;">Booking, Registration, Land Share, EMI, Dev Charge, Shop Service &amp; Management</span>
        </div>

        <table class="type-summary-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 32px;">SL</th>
                    <th>Payment Type / Purpose</th>
                    <th class="text-center" style="width: 75px;">Trx Count</th>
                    <th class="text-end" style="width: 105px;">Bank Inflow (৳)</th>
                    <th class="text-end" style="width: 105px;">Cash Collection (৳)</th>
                    <th class="text-end" style="width: 100px;">Cheque (৳)</th>
                    <th class="text-end" style="width: 100px;">Online / MFS (৳)</th>
                    <th class="text-end" style="width: 120px;">Total Collection (৳)</th>
                    <th class="text-end" style="width: 65px;">% Share</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $pSl = 1;
                $pTypes = $typeSummary['rows'] ?? [];
                $pGrand = $typeSummary['grand'] ?? ['count' => 0, 'total_amount' => 0, 'bank_amount' => 0, 'cash_amount' => 0, 'cheque_amount' => 0, 'online_amount' => 0];
                foreach ($pTypes as $tKey => $r):
                    $hasAmt = ($r['total_amount'] > 0 || $r['count'] > 0);
                ?>
                <tr style="<?= $hasAmt ? '' : 'color: #94a3b8;' ?>">
                    <td class="text-center"><?= $pSl++ ?></td>
                    <td>
                        <strong><?= htmlspecialchars($r['name']) ?></strong>
                    </td>
                    <td class="text-center font-monospace">
                        <?= number_format($r['count']) ?>
                    </td>
                    <td class="text-end font-monospace" style="color: #0369a1;">
                        <?= $r['bank_amount'] > 0 ? number_format($r['bank_amount'], 2) : '-' ?>
                    </td>
                    <td class="text-end font-monospace" style="color: #15803d;">
                        <?= $r['cash_amount'] > 0 ? number_format($r['cash_amount'], 2) : '-' ?>
                    </td>
                    <td class="text-end font-monospace" style="color: #b45309;">
                        <?= $r['cheque_amount'] > 0 ? number_format($r['cheque_amount'], 2) : '-' ?>
                    </td>
                    <td class="text-end font-monospace" style="color: #be185d;">
                        <?= $r['online_amount'] > 0 ? number_format($r['online_amount'], 2) : '-' ?>
                    </td>
                    <td class="text-end font-monospace" style="font-weight: 700; color: #0f172a;">
                        ৳<?= number_format($r['total_amount'], 2) ?>
                    </td>
                    <td class="text-end font-monospace">
                        <?= number_format((float)($r['percentage'] ?? 0), 1) ?>%
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" style="text-align: right; text-transform: uppercase;">
                        GRAND TOTAL COLLECTION:
                    </td>
                    <td class="text-center font-monospace"><?= number_format($pGrand['count']) ?></td>
                    <td class="text-end font-monospace" style="color: #0369a1;">৳<?= number_format($pGrand['bank_amount'], 2) ?></td>
                    <td class="text-end font-monospace" style="color: #15803d;">৳<?= number_format($pGrand['cash_amount'], 2) ?></td>
                    <td class="text-end font-monospace" style="color: #b45309;">৳<?= number_format($pGrand['cheque_amount'], 2) ?></td>
                    <td class="text-end font-monospace" style="color: #be185d;">৳<?= number_format($pGrand['online_amount'], 2) ?></td>
                    <td class="text-end font-monospace" style="color: #0f172a; font-size: 11.5px;">৳<?= number_format($pGrand['total_amount'], 2) ?></td>
                    <td class="text-end font-monospace">100.0%</td>
                </tr>
            </tfoot>
        </table>
        <?php endif; // End if printMode === 'type_summary' || 'full' ?>

        <?php if ($printMode === 'bank_summary' || $printMode === 'full'): 
            $bankList = $summary['by_bank'] ?? [];
            $totalBankInflow = (float)($summary['bank_amount'] ?? 0);
            $totalBankTrx = 0;
            if (!empty($bankList)) {
                foreach ($bankList as $b) {
                    $totalBankTrx += (int)($b['count'] ?? 0);
                }
            }
        ?>
        <!-- Bank Account Wise Inflow Breakdown Table Sheet -->
        <div style="margin-top: 14px; margin-bottom: 6px; font-weight: 700; font-size: 11.5px; color: #0f172a; border-bottom: 2px solid #0f172a; padding-bottom: 4px; display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fas fa-university"></i> Bank Account Wise Inflow Breakdown</span>
            <span style="font-size: 9.5px; color: #475569; font-weight: 600;">Total Recorded Bank Inflow: ৳<?= number_format($totalBankInflow, 2) ?></span>
        </div>

        <table class="type-summary-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 35px;">SL</th>
                    <th>Bank Account / Deposited Account</th>
                    <th class="text-center" style="width: 90px;">Deposit Count</th>
                    <th class="text-end" style="width: 140px;">Total Inflow (৳)</th>
                    <th class="text-end" style="width: 80px;">% Share</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (empty($bankList)): ?>
                    <tr>
                        <td colspan="5" class="text-center" style="padding: 15px; color: #94a3b8;">No bank deposits recorded for this period.</td>
                    </tr>
                <?php else:
                    $bSl = 1;
                    foreach ($bankList as $bName => $bData):
                        $bAmt = (float)($bData['amount'] ?? 0);
                        $bCount = (int)($bData['count'] ?? 0);
                        $bPct = ($totalBankInflow > 0) ? round(($bAmt / $totalBankInflow) * 100, 2) : 0;
                ?>
                <tr>
                    <td class="text-center"><?= $bSl++ ?></td>
                    <td>
                        <strong><?= htmlspecialchars($bName) ?></strong>
                    </td>
                    <td class="text-center font-monospace">
                        <?= number_format($bCount) ?>
                    </td>
                    <td class="text-end font-monospace" style="font-weight: 700; color: #0369a1;">
                        ৳<?= number_format($bAmt, 2) ?>
                    </td>
                    <td class="text-end font-monospace">
                        <?= number_format($bPct, 1) ?>%
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td class="text-center font-monospace"><?= count($bankList) ?></td>
                    <td style="text-align: right; text-transform: uppercase;">TOTAL BANK INFLOW:</td>
                    <td class="text-center font-monospace"><?= number_format($totalBankTrx) ?></td>
                    <td class="text-end font-monospace" style="color: #0369a1; font-size: 11.5px;">৳<?= number_format($totalBankInflow, 2) ?></td>
                    <td class="text-end font-monospace">100.0%</td>
                </tr>
            </tfoot>
        </table>
        <?php endif; // End if printMode === 'bank_summary' || 'full' ?>

        <?php if ($printMode === 'transactions' || $printMode === 'full'): ?>
        <!-- Section Header for Detailed Register -->
        <div style="margin-top: 14px; margin-bottom: 6px; font-weight: 700; font-size: 11.5px; color: #0f172a; border-bottom: 2px solid #0f172a; padding-bottom: 4px; display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fas fa-list-ol"></i> Detailed Collection Transactions Register</span>
            <span style="font-size: 9.5px; color: #475569; font-weight: 600;">Showing <?= number_format(count($transactions)) ?> Transactions</span>
        </div>

        <!-- Detail Table -->
        <table class="statement-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 32px;">SL</th>
                    <th style="width: 70px;">Date</th>
                    <th style="width: 140px;">Client / Depositor</th>
                    <th style="width: 85px;">Phone</th>
                    <th style="width: 130px;">Project & Unit</th>
                    <th style="width: 100px;">Payment Type</th>
                    <th>Payment Method & Bank</th>
                    <th style="width: 90px;">Receipt / Ref</th>
                    <th style="width: 95px;">Added By</th>
                    <th class="text-center" style="width: 60px;">Status</th>
                    <th class="text-end" style="width: 95px;">Amount (৳)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="11" class="text-center" style="padding: 24px; color: #94a3b8;">
                            <i class="fas fa-search" style="font-size: 20px; margin-bottom: 6px; display: block;"></i>
                            No collection transactions found matching the selected criteria.
                        </td>
                    </tr>
                <?php else: 
                    $sl = 1;
                    $pageSum = 0;
                    foreach ($transactions as $t): 
                        $pageSum += (float)$t['amount'];
                        $isMgmt = ($t['source_type'] ?? '') === 'management';
                        
                        $typeClass = 'type-mgmt';
                        if ($t['payment_type'] === 'booking_money') $typeClass = 'type-booking';
                        elseif ($t['payment_type'] === 'registration') $typeClass = 'type-reg';
                        elseif ($t['payment_type'] === 'land_share') $typeClass = 'type-land';
                        elseif ($t['payment_type'] === 'emi') $typeClass = 'type-emi';
                        elseif ($t['payment_type'] === 'development_charge') $typeClass = 'type-dev';
                        elseif ($t['payment_type'] === 'shop_service_charge') $typeClass = 'type-shop';

                        $typeLabel = $t['purpose_info']['label'] ?? ($paymentTypeNames[$t['payment_type']] ?? ucwords(str_replace('_', ' ', $t['payment_type'])));
                    ?>
                    <tr>
                        <td class="text-center"><?= $sl++ ?></td>
                        <td><?= !empty($t['transaction_date']) ? date('d-m-Y', strtotime($t['transaction_date'])) : '-' ?></td>
                        <td>
                            <strong><?= htmlspecialchars($t['client_name'] ?? 'N/A') ?></strong>
                            <?php if ($isMgmt): ?>
                                <span style="font-size: 8px; color: #6b21a8; font-weight: 700;">[MGMT]</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($t['phone'] ?? '-') ?></td>
                        <td>
                            <strong><?= htmlspecialchars($t['project_name'] ?? '-') ?></strong>
                            <?php if (!empty($t['flat_name'])): ?>
                                <div style="font-size: 8.5px; color: #475569;">Unit: <?= htmlspecialchars($t['flat_name']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge-type <?= $typeClass ?>"><?= htmlspecialchars($typeLabel) ?></span>
                        </td>
                        <td>
                            <span class="method-tag"><?= strtoupper(htmlspecialchars($t['payment_method'] ?? '-')) ?></span>
                            <?php if (!empty($t['bank_name']) && strtolower($t['bank_name']) !== 'cash'): ?>
                                <div class="bank-details">
                                    <i class="fas fa-university"></i> <?= htmlspecialchars($t['bank_name']) ?>
                                    <?php if (!empty($t['cheque_no'])): ?>
                                        | Chq #<?= htmlspecialchars($t['cheque_no']) ?>
                                    <?php endif; ?>
                                </div>
                            <?php elseif (!empty($t['cheque_no'])): ?>
                                <div class="bank-details">Chq #<?= htmlspecialchars($t['cheque_no']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($t['money_receipt_no'] ?? '-') ?>
                        </td>
                        <td>
                            <span style="font-size: 8.5px;"><?= htmlspecialchars($t['collector_name'] ?? 'System') ?></span>
                        </td>
                        <td class="text-center">
                            <?php 
                                $status = strtolower($t['verification_status'] ?? 'pending');
                                if ($status === 'verified'):
                            ?>
                                <span style="color: #15803d; font-weight: 700;">Verified</span>
                            <?php elseif ($status === 'rejected'): ?>
                                <span style="color: #b91c1c; font-weight: 700;">Rejected</span>
                            <?php else: ?>
                                <span style="color: #d97706; font-weight: 700;">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end" style="font-weight: 700; color: #0f172a;">
                            ৳<?= number_format((float)$t['amount'], 2) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="10" class="text-end">
                        <strong>TOTAL COLLECTION (<?= number_format(count($transactions)) ?> Transactions):</strong>
                    </td>
                    <td class="text-end" style="color: #15803d; font-size: 11px;">
                        <strong>৳<?= number_format($totalAmt, 2) ?></strong>
                    </td>
                </tr>
            </tfoot>
        </table>
        <?php endif; // End if printMode !== 'type_summary' ?>

        <!-- Signatures Section -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-title">Prepared By</div>
                <div class="signature-sub">Accounts Executive</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-title">Checked By</div>
                <div class="signature-sub">Senior Accountant</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-title">Accounts Officer</div>
                <div class="signature-sub">Manager (Accounts & Finance)</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-title">Authorized Signature</div>
                <div class="signature-sub">Managing Director / Director</div>
            </div>
        </div>

    </div>

</body>
</html>

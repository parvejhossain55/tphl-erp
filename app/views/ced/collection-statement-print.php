<?php
$company_name = "THE PREMIUM HOMES LTD.";
$company_address = "Land View Commercial Center, 9th Floor, 28 Gulshan North C/A, Gulshan Circle-2, Dhaka-1212";
$company_phone = "+8801958253300";
$company_email = "info@thepremiumhomes.com";

$activePeriodLabel = $date_info['label'] ?? 'Custom Date Range';
$collectorSummary = $collector_summary ?? ['rows' => [], 'grand' => []];
$typeSummary = $type_summary ?? ['rows' => [], 'grand' => []];
$transactions = $transactions ?? [];
$printMode = $print_mode ?? 'collectors';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CED Collection Statement - <?= htmlspecialchars($activePeriodLabel) ?></title>
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
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-secondary { background: #475569; color: #fff; }
        .print-container {
            background: #fff;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            border-bottom: 2px solid #0284c7;
            padding-bottom: 12px;
        }
        .company-title {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
        }
        .report-title {
            font-size: 15px;
            font-weight: 700;
            color: #0284c7;
            text-transform: uppercase;
            margin-top: 4px;
        }
        .info-pill {
            display: inline-block;
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            margin: 2px;
            border: 1px solid #e2e8f0;
        }
        .kpi-row {
            display: flex;
            gap: 10px;
            margin-bottom: 18px;
        }
        .kpi-box {
            flex: 1;
            padding: 10px 14px;
            border-radius: 6px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .kpi-label {
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 700;
            color: #64748b;
        }
        .kpi-value {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            margin-top: 2px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10.5px;
        }
        .data-table th, .data-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            vertical-align: middle;
        }
        .data-table th {
            background-color: #f1f5f9;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10px;
            color: #334155;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: 700; }
        .font-mono { font-family: monospace; }
        .sign-table {
            width: 100%;
            margin-top: 40px;
            border-collapse: collapse;
        }
        .sign-table td {
            width: 25%;
            text-align: center;
            padding-top: 40px;
            border: none;
        }
        .sign-line {
            border-top: 1px dashed #64748b;
            padding-top: 4px;
            display: inline-block;
            width: 80%;
            font-weight: 600;
            font-size: 11px;
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
            .header-table td {
                display: block;
                width: 100% !important;
                text-align: left !important;
                margin-bottom: 8px;
            }
            .kpi-row {
                flex-wrap: wrap;
                gap: 8px;
            }
            .kpi-box {
                flex: 1 1 calc(50% - 6px);
                min-width: 130px;
            }
            .data-table {
                min-width: 680px;
            }
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .no-print-bar { display: none !important; }
            .print-container {
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>

<div class="no-print-bar">
    <div>
        <strong>Print View: CED Collection Statement</strong>
        <span style="opacity: 0.8; margin-left: 10px;"><?= htmlspecialchars($activePeriodLabel) ?></span>
    </div>
    <div style="display: flex; gap: 8px;">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fa-solid fa-print"></i> Print Now
        </button>
        <a href="<?= $GLOBALS['base_url'] ?>/ced-collection-statement?<?= http_build_query($filters) ?>" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Return to Statement
        </a>
    </div>
</div>

<div class="print-container">
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <div class="company-title"><?= htmlspecialchars($company_name) ?></div>
                <div style="color: #64748b; font-size: 10.5px;"><?= htmlspecialchars($company_address) ?> &bull; Phone: <?= htmlspecialchars($company_phone) ?></div>
                <div class="report-title">Client Experience Department (CED) &bull; Collection Statement</div>
            </td>
            <td style="width: 40%; text-align: right; vertical-align: top;">
                <div class="info-pill"><strong>Period:</strong> <?= htmlspecialchars($activePeriodLabel) ?></div>
                <div class="info-pill"><strong>Project:</strong> <?= htmlspecialchars($project_name ?? 'All Projects') ?></div>
                <div class="info-pill"><strong>Printed On:</strong> <?= date('d M Y, h:i A') ?></div>
            </td>
        </tr>
    </table>

    <!-- KPI Metric Summary -->
    <div class="kpi-row">
        <div class="kpi-box">
            <div class="kpi-label">Total Collections</div>
            <div class="kpi-value font-mono">৳<?= number_format($summary['total_amount'] ?? 0, 2) ?></div>
            <small style="color: #64748b;"><?= number_format($summary['total_count'] ?? 0) ?> Total Trx</small>
        </div>
        <div class="kpi-box">
            <div class="kpi-label">Development Charge</div>
            <div class="kpi-value font-mono" style="color: #d97706;">৳<?= number_format($summary['dev_charge_amount'] ?? 0, 2) ?></div>
            <small style="color: #64748b;"><?= number_format($summary['dev_charge_count'] ?? 0) ?> Collections</small>
        </div>
        <div class="kpi-box">
            <div class="kpi-label">EMI &amp; Due Settled</div>
            <div class="kpi-value font-mono" style="color: #16a34a;">৳<?= number_format($summary['total_emi_amount'] ?? 0, 2) ?></div>
            <small style="color: #64748b;"><?= number_format($summary['total_emi_count'] ?? 0) ?> Installments</small>
        </div>
        <div class="kpi-box">
            <div class="kpi-label">Booking &amp; Reg (Rare)</div>
            <div class="kpi-value font-mono" style="color: #9333ea;">৳<?= number_format(($summary['booking_amount'] ?? 0) + ($summary['registration_amount'] ?? 0), 2) ?></div>
            <small style="color: #64748b;"><?= number_format(($summary['booking_count'] ?? 0) + ($summary['registration_count'] ?? 0)) ?> Trx</small>
        </div>
        <div class="kpi-box">
            <div class="kpi-label">Cash Collected</div>
            <div class="kpi-value font-mono" style="color: #059669;">৳<?= number_format($summary['cash_amount'] ?? 0, 2) ?></div>
            <small style="color: #64748b;"><?= number_format($summary['cash_count'] ?? 0) ?> Entries</small>
        </div>
        <div class="kpi-box">
            <div class="kpi-label">Bank &amp; Digital Transfer</div>
            <div class="kpi-value font-mono" style="color: #2563eb;">৳<?= number_format(($summary['bank_amount'] ?? 0) + ($summary['cheque_amount'] ?? 0) + ($summary['online_amount'] ?? 0), 2) ?></div>
            <small style="color: #64748b;"><?= number_format(($summary['bank_count'] ?? 0) + ($summary['cheque_count'] ?? 0) + ($summary['online_count'] ?? 0)) ?> Transfers</small>
        </div>
    </div>

    <!-- Mode 1: Executive Matrix -->
    <?php if ($printMode === 'collectors' || $printMode === 'all'): ?>
        <h4 style="margin: 14px 0 8px 0; color: #0f172a; font-size: 13px; text-transform: uppercase;">
            <i class="fa-solid fa-users-gear" style="color: #0284c7;"></i> Executive-wise Collection Summary Matrix
        </h4>
        <table class="data-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 30px;">#</th>
                    <th>Executive / Collector Name</th>
                    <th>Role / Dept</th>
                    <th class="text-center">Total Trx</th>
                    <th class="text-end">Dev Charge (BDT)</th>
                    <th class="text-end">Regular EMI (BDT)</th>
                    <th class="text-end">Due Settled (BDT)</th>
                    <th class="text-end">Booking &amp; Reg (BDT)</th>
                    <th class="text-end">Total Collected (BDT)</th>
                    <th class="text-center" style="width: 70px;">% Share</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $cSl = 1;
                foreach ($collectorSummary['rows'] as $r):
                    $bookReg = $r['booking_amount'] + $r['registration_amount'];
                ?>
                    <tr>
                        <td class="text-center"><?= $cSl++ ?></td>
                        <td><strong><?= htmlspecialchars($r['collector_name']) ?></strong></td>
                        <td><?= htmlspecialchars($r['collector_role']) ?></td>
                        <td class="text-center font-mono"><?= number_format($r['total_count']) ?></td>
                        <td class="text-end font-mono"><?= $r['dev_charge_amount'] > 0 ? '৳' . number_format($r['dev_charge_amount'], 2) : '-' ?></td>
                        <td class="text-end font-mono"><?= $r['emi_amount'] > 0 ? '৳' . number_format($r['emi_amount'], 2) : '-' ?></td>
                        <td class="text-end font-mono"><?= $r['settlement_amount'] > 0 ? '৳' . number_format($r['settlement_amount'], 2) : '-' ?></td>
                        <td class="text-end font-mono"><?= $bookReg > 0 ? '৳' . number_format($bookReg, 2) : '-' ?></td>
                        <td class="text-end font-mono fw-bold">৳<?= number_format($r['total_amount'], 2) ?></td>
                        <td class="text-center font-mono"><?= number_format($r['percentage'], 1) ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <?php if (!empty($collectorSummary['rows'])): 
                $cg = $collectorSummary['grand'];
            ?>
                <tfoot>
                    <tr style="background: #f1f5f9; font-weight: 700;">
                        <td colspan="3" class="text-uppercase">Total Collections:</td>
                        <td class="text-center font-mono"><?= number_format($cg['total_count']) ?></td>
                        <td class="text-end font-mono">৳<?= number_format($cg['dev_charge_amount'], 2) ?></td>
                        <td class="text-end font-mono">৳<?= number_format($cg['emi_amount'], 2) ?></td>
                        <td class="text-end font-mono">৳<?= number_format($cg['settlement_amount'], 2) ?></td>
                        <td class="text-end font-mono">৳<?= number_format($cg['booking_amount'] + $cg['registration_amount'], 2) ?></td>
                        <td class="text-end font-mono fw-bold" style="color: #0284c7;">৳<?= number_format($cg['total_amount'], 2) ?></td>
                        <td class="text-center font-mono">100.0%</td>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    <?php endif; ?>

    <!-- Mode 2: Detailed Transactions -->
    <?php if ($printMode === 'transactions' || $printMode === 'all'): ?>
        <h4 style="margin: 20px 0 8px 0; color: #0f172a; font-size: 13px; text-transform: uppercase;">
            <i class="fa-solid fa-list-check" style="color: #16a34a;"></i> Detailed Collection Records Register
        </h4>
        <table class="data-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 30px;">#</th>
                    <th>Date</th>
                    <th>Client Name</th>
                    <th>Phone</th>
                    <th>Project &amp; Unit</th>
                    <th>Purpose / Head</th>
                    <th>Method</th>
                    <th>Banking Details</th>
                    <th>Voucher Ref</th>
                    <th class="text-end">Amount (BDT)</th>
                    <th>Collector (Staff)</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $tSl = 1;
                $tSum = 0;
                foreach ($transactions as $t): 
                    $tSum += (float)$t['amount'];
                    $pLabel = $t['purpose_info']['label'] ?? ucwords(str_replace('_', ' ', $t['payment_type'] ?? ''));
                    if ($t['payment_type'] === 'emi' && !empty($t['settlement_type']) && empty($t['purpose_info'])) {
                        $pLabel .= ' (Settled)';
                    }
                    $hasCoBank  = !empty($t['company_bank_name']);
                    $hasCliBank = !empty($t['bank_name']);
                    $hasChq     = !empty($t['cheque_no']);
                ?>
                    <tr>
                        <td class="text-center"><?= $tSl++ ?></td>
                        <td class="font-mono"><?= date('d-m-Y', strtotime($t['payment_date'])) ?></td>
                        <td><strong><?= htmlspecialchars($t['client_name'] ?? 'N/A') ?></strong></td>
                        <td class="font-mono"><?= htmlspecialchars($t['client_phone'] ?? '') ?></td>
                        <td><?= htmlspecialchars($t['project_name'] ?? 'N/A') ?> (<?= htmlspecialchars($t['flat_name'] ?? 'N/A') ?>)</td>
                        <td><?= htmlspecialchars($pLabel) ?></td>
                        <td style="text-transform: uppercase;"><?= htmlspecialchars($t['payment_method'] ?? 'cash') ?></td>
                        <td style="font-size: 10px; line-height: 1.3;">
                            <?php if ($hasCoBank): ?>
                                <div>
                                    <strong>To:</strong> <?= htmlspecialchars($t['company_bank_name']) ?>
                                    <?php if (!empty($t['company_account_number'])): ?>
                                        <span style="font-family: monospace; color: #0284c7; font-size: 9.5px;">(A/C: <?= htmlspecialchars($t['company_account_number']) ?>)</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($hasCliBank): ?>
                                <div style="color: #475569;"><strong>From:</strong> <?= htmlspecialchars($t['bank_name']) ?></div>
                            <?php endif; ?>
                            <?php if ($hasChq): ?>
                                <div style="font-family: monospace;">Chq: <?= htmlspecialchars($t['cheque_no']) ?></div>
                            <?php endif; ?>
                            <?php if (!$hasCoBank && !$hasCliBank && !$hasChq): ?>
                                <span style="color: #94a3b8;"><?= strtolower($t['payment_method'] ?? '') === 'cash' ? 'Cash' : '-' ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="font-mono"><?= htmlspecialchars($t['transaction_ref'] ?? '-') ?></td>
                        <td class="text-end font-mono fw-bold">৳<?= number_format((float)$t['amount'], 2) ?></td>
                        <td><?= htmlspecialchars($t['collector_name']) ?></td>
                        <td class="text-center" style="text-transform: capitalize;"><?= htmlspecialchars($t['verification_status'] ?? 'pending') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: #f1f5f9; font-weight: 700;">
                    <td colspan="9" class="text-uppercase text-end">Total Register Amount:</td>
                    <td class="text-end font-mono fw-bold" style="color: #0284c7;">৳<?= number_format($tSum, 2) ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>

    <!-- Signature Section -->
    <table class="sign-table">
        <tr>
            <td><div class="sign-line">Prepared By</div></td>
            <td><div class="sign-line">Checked By</div></td>
            <td><div class="sign-line">Head of CED</div></td>
            <td><div class="sign-line">Director / Accounts</div></td>
        </tr>
    </table>
</div>

</body>
</html>

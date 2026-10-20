<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $vendor ? 'Vendor Statement - ' . htmlspecialchars($vendor['name']) : 'Vendor Ledger Summary Statement' ?> | TPHL ERP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background-color: #f8fafc;
            color: #1e293b;
            font-size: 12px;
            padding: 24px;
        }
        .statement-container {
            max-width: 1020px;
            margin: 0 auto;
            background: #ffffff;
            padding: 36px 40px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 18px;
            margin-bottom: 20px;
        }
        .company-info h2 {
            font-size: 22px;
            font-weight: 700;
            color: #1e3a8a;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
        }
        .company-info p {
            font-size: 11.5px;
            color: #64748b;
            line-height: 1.4;
        }
        .statement-title-box {
            text-align: right;
        }
        .statement-title-box h3 {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .statement-title-box .badge {
            display: inline-block;
            background-color: #eff6ff;
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 4px;
            margin-top: 4px;
            border: 1px solid #bfdbfe;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 16px;
            margin-bottom: 24px;
        }
        .info-block h4 {
            font-size: 11px;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }
        .info-block p {
            font-size: 12.5px;
            color: #0f172a;
            margin-bottom: 3px;
        }
        .info-block p strong {
            color: #334155;
            font-weight: 600;
        }
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }
        .kpi-box {
            padding: 12px 16px;
            border-radius: 6px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }
        .kpi-box.po {
            background: #eff6ff;
            border-color: #bfdbfe;
        }
        .kpi-box.paid {
            background: #f0fdf4;
            border-color: #bbf7d0;
        }
        .kpi-box.due {
            background: #fff7ed;
            border-color: #fed7aa;
        }
        .kpi-box .label {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 4px;
        }
        .kpi-box .value {
            font-size: 18px;
            font-weight: 700;
        }
        .kpi-box.po .value { color: #1d4ed8; }
        .kpi-box.paid .value { color: #15803d; }
        .kpi-box.due .value { color: #c2410c; }

        .section-title {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
            padding-bottom: 4px;
            border-bottom: 1px solid #cbd5e1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11.5px;
            margin-bottom: 24px;
        }
        table thead th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 600;
            text-align: left;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            text-transform: uppercase;
            font-size: 10.5px;
        }
        table tbody td {
            padding: 7px 10px;
            border: 1px solid #e2e8f0;
            color: #1e293b;
        }
        table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        table tfoot td {
            padding: 9px 10px;
            border: 1px solid #cbd5e1;
            font-weight: 700;
            background-color: #f1f5f9;
        }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: 700; }
        .text-danger { color: #dc2626; }
        .text-success { color: #16a34a; }
        .text-muted { color: #64748b; }

        .signature-block {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 60px;
            padding-top: 20px;
            page-break-inside: avoid;
        }
        .sig-box {
            text-align: center;
        }
        .sig-line {
            border-top: 1px dashed #64748b;
            margin-bottom: 6px;
        }
        .sig-title {
            font-size: 11px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
        }
        .print-actions {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 8px;
            z-index: 1000;
        }
        .btn-print {
            background: #2563eb;
            color: white;
            border: none;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.3);
        }
        .btn-close {
            background: #64748b;
            color: white;
            border: none;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .statement-container {
                box-shadow: none;
                padding: 0;
                width: 100%;
                max-width: 100%;
            }
            .print-actions {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="print-actions">
        <button class="btn-print" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Print Statement
        </button>
        <button class="btn-close" onclick="window.close()">
            <i class="fa-solid fa-xmark"></i> Close
        </button>
    </div>

    <div class="statement-container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <h2>The Peninsula Holdings Ltd.</h2>
                <p>Corporate Office: House # 12, Road # 16, Dhanmondi, Dhaka-1209, Bangladesh</p>
                <p>Phone: +880 2 9112233 | Email: accounts@peninsulabd.com | Web: www.peninsulabd.com</p>
            </div>
            <div class="statement-title-box">
                <h3>Vendor Ledger Statement</h3>
                <span class="badge">Official Financial Statement</span>
                <p class="text-muted" style="font-size: 11px; margin-top: 4px;">
                    Date: <?= date('d M, Y h:i A') ?>
                </p>
            </div>
        </div>

        <!-- Meta Details -->
        <div class="info-grid">
            <div class="info-block">
                <h4>Vendor Information</h4>
                <?php if ($vendor): ?>
                    <p><strong>Name:</strong> <?= htmlspecialchars($vendor['name']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($vendor['phone'] ?: 'N/A') ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($vendor['email'] ?: 'N/A') ?></p>
                    <p><strong>Address:</strong> <?= htmlspecialchars($vendor['address'] ?: 'N/A') ?></p>
                <?php else: ?>
                    <p><strong>Scope:</strong> All Registered Vendors (Consolidated Report)</p>
                    <p><strong>Active Vendors:</strong> <?= number_format($kpiMetrics['total_vendors'] ?? 0) ?></p>
                <?php endif; ?>
            </div>
            <div class="info-block">
                <h4>Statement Details</h4>
                <p><strong>Project Scope:</strong> <?= htmlspecialchars($projectName) ?></p>
                <p><strong>Date Filter:</strong> 
                    <?php if ($startDate || $endDate): ?>
                        <?= $startDate ? date('d M, Y', strtotime($startDate)) : 'Beginning' ?> to <?= $endDate ? date('d M, Y', strtotime($endDate)) : 'Today' ?>
                    <?php else: ?>
                        All Time / Lifetime Records
                    <?php endif; ?>
                </p>
                <p><strong>Currency:</strong> Bangladeshi Taka (BDT / ৳)</p>
                <p><strong>Printed By:</strong> <?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'System User') ?></p>
            </div>
        </div>

        <!-- Financial KPI Cards -->
        <div class="kpi-row">
            <div class="kpi-box po">
                <div class="label">Total Billed / PO Amount</div>
                <div class="value">৳ <?= number_format($kpiMetrics['total_po_amount'] ?? 0, 2) ?></div>
            </div>
            <div class="kpi-box paid">
                <div class="label">Total Paid Disbursed</div>
                <div class="value">৳ <?= number_format($kpiMetrics['total_paid_amount'] ?? 0, 2) ?></div>
            </div>
            <div class="kpi-box due">
                <div class="label">Outstanding Due Balance</div>
                <div class="value">৳ <?= number_format($kpiMetrics['total_due_amount'] ?? 0, 2) ?></div>
            </div>
        </div>

        <?php if (!empty($vendor)): ?>
            <!-- Project Breakdown -->
            <?php if (!empty($projectBreakdown)): ?>
                <div class="section-title">1. Project-Wise Financial Summary</div>
                <table>
                    <thead>
                        <tr>
                            <th>Project Name</th>
                            <th class="text-center" style="width: 100px;">POs</th>
                            <th class="text-end" style="width: 150px;">Total PO Amount (৳)</th>
                            <th class="text-end" style="width: 150px;">Total Paid (৳)</th>
                            <th class="text-end" style="width: 150px;">Balance Due (৳)</th>
                            <th class="text-center" style="width: 110px;">Last Paid</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projectBreakdown as $pb): ?>
                            <tr>
                                <td><?= htmlspecialchars($pb['project_name']) ?></td>
                                <td class="text-center"><?= $pb['po_count'] ?></td>
                                <td class="text-end">৳ <?= number_format($pb['total_po_amount'], 2) ?></td>
                                <td class="text-end text-success">৳ <?= number_format($pb['paid_amount'], 2) ?></td>
                                <td class="text-end <?= ($pb['due_amount'] > 0) ? 'text-danger' : 'text-muted' ?>">
                                    ৳ <?= number_format($pb['due_amount'], 2) ?>
                                </td>
                                <td class="text-center text-muted"><?= $pb['last_payment_date'] ? date('d M, Y', strtotime($pb['last_payment_date'])) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>Total Across Projects</td>
                            <td class="text-center"><?= array_sum(array_column($projectBreakdown, 'po_count')) ?></td>
                            <td class="text-end">৳ <?= number_format(array_sum(array_column($projectBreakdown, 'total_po_amount')), 2) ?></td>
                            <td class="text-end text-success">৳ <?= number_format(array_sum(array_column($projectBreakdown, 'paid_amount')), 2) ?></td>
                            <td class="text-end text-danger">৳ <?= number_format(array_sum(array_column($projectBreakdown, 'due_amount')), 2) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            <?php endif; ?>

            <!-- Chronological Running Balance Statement -->
            <div class="section-title">2. Chronological Running Balance Statement</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 85px;">Date</th>
                        <th style="width: 100px;">Type</th>
                        <th style="width: 90px;">Ref #</th>
                        <th>Project</th>
                        <th>Particulars / Description</th>
                        <th class="text-end" style="width: 115px;">Billed / Debit (৳)</th>
                        <th class="text-end" style="width: 115px;">Paid / Credit (৳)</th>
                        <th class="text-end" style="width: 120px;">Balance (৳)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($ledgerEntries)): ?>
                        <?php 
                        $totalBilled = 0;
                        $totalPaid = 0;
                        ?>
                        <?php foreach ($ledgerEntries as $le): ?>
                            <?php 
                            $totalBilled += (float)$le['debit_amount'];
                            $totalPaid += (float)$le['credit_amount'];
                            $isPo = ($le['entry_type'] === 'PURCHASE_ORDER');
                            ?>
                            <tr>
                                <td><?= $le['tx_date'] ? date('d-m-Y', strtotime($le['tx_date'])) : '—' ?></td>
                                <td><?= $isPo ? 'Purchase Order' : 'Payment Disbursed' ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($le['ref_no']) ?></td>
                                <td><?= htmlspecialchars($le['project_name']) ?></td>
                                <td><?= htmlspecialchars($le['description']) ?></td>
                                <td class="text-end"><?= ($le['debit_amount'] > 0) ? '৳ ' . number_format($le['debit_amount'], 2) : '—' ?></td>
                                <td class="text-end text-success"><?= ($le['credit_amount'] > 0) ? '৳ ' . number_format($le['credit_amount'], 2) : '—' ?></td>
                                <td class="text-end fw-bold <?= ($le['running_balance'] > 0) ? 'text-danger' : 'text-success' ?>">
                                    ৳ <?= number_format($le['running_balance'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tfoot>
                            <tr>
                                <td colspan="5">Net Total (Invoiced vs Paid)</td>
                                <td class="text-end">৳ <?= number_format($totalBilled, 2) ?></td>
                                <td class="text-end text-success">৳ <?= number_format($totalPaid, 2) ?></td>
                                <td class="text-end text-danger">৳ <?= number_format($kpiMetrics['total_due_amount'], 2) ?></td>
                            </tr>
                        </tfoot>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No transaction entries found for this vendor.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php else: ?>
            <!-- Master Vendors Summary Table -->
            <div class="section-title">Consolidated Vendors Financial Statement</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 35px;">#</th>
                        <th>Vendor Name</th>
                        <th>Contact</th>
                        <th class="text-center" style="width: 80px;">Total POs</th>
                        <th class="text-end" style="width: 140px;">Total PO Value (৳)</th>
                        <th class="text-end" style="width: 140px;">Total Paid (৳)</th>
                        <th class="text-end" style="width: 140px;">Outstanding Due (৳)</th>
                        <th class="text-center" style="width: 100px;">Last Payment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($vendorsSummary)): ?>
                        <?php 
                        $grandPo = 0;
                        $grandPaid = 0;
                        $grandDue = 0;
                        ?>
                        <?php foreach ($vendorsSummary as $i => $vs): ?>
                            <?php 
                            $grandPo += (float)$vs['total_po_amount'];
                            $grandPaid += (float)$vs['total_paid'];
                            $grandDue += (float)$vs['total_due'];
                            ?>
                            <tr>
                                <td class="text-muted"><?= $i + 1 ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($vs['name']) ?></td>
                                <td><?= htmlspecialchars($vs['phone'] ?: 'N/A') ?></td>
                                <td class="text-center"><?= $vs['total_pos'] ?></td>
                                <td class="text-end">৳ <?= number_format($vs['total_po_amount'], 2) ?></td>
                                <td class="text-end text-success">৳ <?= number_format($vs['total_paid'], 2) ?></td>
                                <td class="text-end <?= ($vs['total_due'] > 0) ? 'text-danger fw-bold' : 'text-muted' ?>">
                                    ৳ <?= number_format($vs['total_due'], 2) ?>
                                </td>
                                <td class="text-center text-muted"><?= $vs['last_payment_date'] ? date('d-m-Y', strtotime($vs['last_payment_date'])) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tfoot>
                            <tr>
                                <td colspan="4">Grand Total</td>
                                <td class="text-end">৳ <?= number_format($grandPo, 2) ?></td>
                                <td class="text-end text-success">৳ <?= number_format($grandPaid, 2) ?></td>
                                <td class="text-end text-danger">৳ <?= number_format($grandDue, 2) ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No vendors matching criteria.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Signature Authorization Block -->
        <div class="signature-block">
            <div class="sig-box">
                <div class="sig-line"></div>
                <div class="sig-title">Prepared By</div>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <div class="sig-title">Head of Procurement</div>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <div class="sig-title">Accounts Department</div>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <div class="sig-title">Management Approval</div>
            </div>
        </div>
    </div>

</body>
</html>

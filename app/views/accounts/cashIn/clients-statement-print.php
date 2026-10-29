<?php
// Printable Clients Statement Template
$paymentTypeLabels = [
    'full_payment' => 'Full Payment',
    'land_share' => 'Land Share',
    'development_charge' => 'Development Charge',
    'shop_service_charge' => 'Shop Service Charge',
    'construction_fee' => 'Construction Fee'
];

$activePaymentTypeLabel = !empty($paymentType) && isset($paymentTypeLabels[$paymentType]) ? $paymentTypeLabels[$paymentType] : 'All Payment Types';
$pageTitle = "Clients Payment Statement - " . $activePaymentTypeLabel;

$company_name = "THE PREMIUM HOMES LTD.";
$company_address = "Land View Commercial Center, 9th Floor, 28 Gulshan North C/A, Gulshan Circle-2, Dhaka";
$company_phone = "+8801958253300";
$company_email = "info@thepremiumhomes.com";

$targetColumnLabel = 'Total Value';
if ($paymentType === 'land_share') {
    $targetColumnLabel = 'Land Share Value';
} elseif ($paymentType === 'development_charge') {
    $targetColumnLabel = 'Dev Charge Total';
} elseif ($paymentType === 'shop_service_charge') {
    $targetColumnLabel = 'Shop Service Charge';
} elseif ($paymentType === 'construction_fee') {
    $targetColumnLabel = 'Construction Value';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Print</title>
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
            background: #1e293b;
            color: #fff;
            padding: 10px 20px;
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
            padding: 7px 16px;
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

        /* Header */
        .company-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 14px;
            margin-bottom: 16px;
        }

        .company-title {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
        }

        .company-sub {
            font-size: 10.5px;
            color: #64748b;
            margin-top: 3px;
        }

        .statement-title-badge {
            text-align: right;
        }

        .statement-title {
            font-size: 16px;
            font-weight: 700;
            color: #1e3a8a;
            text-transform: uppercase;
        }

        .statement-meta {
            font-size: 10.5px;
            color: #64748b;
            margin-top: 4px;
        }

        /* Filter overview pill grid */
        .filter-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 16px;
            background: #f1f5f9;
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 11px;
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
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .summary-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
            border-left: 4px solid #3b82f6;
        }

        .summary-card.green {
            border-left-color: #10b981;
        }

        .summary-card.red {
            border-left-color: #ef4444;
        }

        .summary-card.purple {
            border-left-color: #8b5cf6;
        }

        .summary-card.amber {
            border-left-color: #f59e0b;
        }

        .summary-card-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .summary-card-value {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
        }

        /* Table */
        table.statement-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-bottom: 20px;
        }

        table.statement-table th {
            background: #0f172a;
            color: #ffffff;
            font-weight: 600;
            padding: 7px 6px;
            text-align: left;
            border: 1px solid #334155;
            font-size: 10px;
            white-space: nowrap;
        }

        table.statement-table th.text-end {
            text-align: right;
        }

        table.statement-table th.text-center {
            text-align: center;
        }

        table.statement-table td {
            padding: 6px 6px;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
            color: #1e293b;
        }

        table.statement-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        table.statement-table td.text-end {
            text-align: right;
            white-space: nowrap;
        }

        table.statement-table td.text-center {
            text-align: center;
        }

        .badge-status {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .badge-verified {
            background: #dcfce7;
            color: #15803d;
        }

        .badge-pending {
            background: #fef3c7;
            color: #b45309;
        }

        .badge-rejected {
            background: #fee2e2;
            color: #b91c1c;
        }

        .unit-chip {
            display: inline-block;
            background: #e2e8f0;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 9.5px;
            margin-right: 3px;
            margin-bottom: 2px;
        }

        /* Table Footer */
        table.statement-table tfoot td {
            background: #e2e8f0;
            font-weight: 700;
            padding: 8px 6px;
            border: 1px solid #94a3b8;
            color: #0f172a;
            font-size: 10.5px;
        }

        /* Signatures */
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }

        .signature-box {
            text-align: center;
            width: 180px;
        }

        .signature-line {
            border-top: 1px solid #475569;
            margin-bottom: 6px;
        }

        .signature-title {
            font-size: 10.5px;
            font-weight: 600;
            color: #334155;
        }

        @media print {
            body {
                background: #fff !important;
                padding: 0 !important;
                font-size: 9pt;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print, .no-print-bar {
                display: none !important;
            }

            .print-container {
                padding: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }

            table.statement-table th {
                background: #0f172a !important;
                color: #ffffff !important;
            }

            table.statement-table tbody tr:nth-child(even) {
                background-color: #f8fafc !important;
            }

            table.statement-table tfoot td {
                background: #e2e8f0 !important;
            }
        }
    </style>
</head>
<body>

    <!-- Non-printable Toolbar -->
    <div class="no-print-bar no-print">
        <div>
            <strong><?= htmlspecialchars($pageTitle) ?></strong> &bull; Total <?= count($clients) ?> records
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fa-solid fa-print"></i> Print Statement
            </button>
            <button onclick="window.close()" class="btn btn-secondary">
                <i class="fa-solid fa-times"></i> Close
            </button>
        </div>
    </div>

    <div class="print-container">
        <!-- Header -->
        <div class="company-header">
            <div>
                <div class="company-title"><?= htmlspecialchars($company_name) ?></div>
                <div class="company-sub"><?= htmlspecialchars($company_address) ?></div>
                <div class="company-sub">Tel: <?= htmlspecialchars($company_phone) ?> | Email: <?= htmlspecialchars($company_email) ?></div>
            </div>
            <div class="statement-title-badge">
                <div class="statement-title"><?= htmlspecialchars($pageTitle) ?></div>
                <div class="statement-meta">Generated: <?= date('d M, Y h:i A') ?></div>
            </div>
        </div>

        <!-- Filter Details -->
        <div class="filter-badges">
            <div class="filter-item"><strong>Project:</strong> <?= htmlspecialchars($projectName ?? 'All Projects') ?></div>
            <span style="color:#cbd5e1;">|</span>
            <div class="filter-item"><strong>Payment Type:</strong> <?= htmlspecialchars($activePaymentTypeLabel) ?></div>
            <span style="color:#cbd5e1;">|</span>
            <div class="filter-item"><strong>Verification:</strong> <?= !empty($verificationStatus) ? ucfirst(htmlspecialchars($verificationStatus)) : 'All Statuses' ?></div>
            <?php if (!empty($startDate) || !empty($endDate)): ?>
                <span style="color:#cbd5e1;">|</span>
                <div class="filter-item"><strong>Date:</strong> <?= htmlspecialchars($startDate ?? 'Start') ?> to <?= htmlspecialchars($endDate ?? 'End') ?> (<?= htmlspecialchars($dateType ?? 'Booking') ?>)</div>
            <?php endif; ?>
            <span style="color:#cbd5e1;">|</span>
            <div class="filter-item"><strong>Total Records:</strong> <?= count($clients) ?></div>
        </div>

        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card purple">
                <div class="summary-card-label">Total Filtered Clients</div>
                <div class="summary-card-value"><?= number_format(count($clients)) ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-card-label"><?= htmlspecialchars($targetColumnLabel) ?></div>
                <div class="summary-card-value">৳<?= number_format($grandTarget, 2) ?></div>
            </div>
            <?php 
            $activeDiscount = 0;
            $discountCardLabel = 'Total Discount';
            if ($paymentType === 'construction_fee') {
                $activeDiscount = $grandEmiDiscount ?? 0;
                $discountCardLabel = 'Total EMI Discount';
            } elseif ($paymentType === 'land_share') {
                $activeDiscount = $grandDiscount ?? 0;
                $discountCardLabel = 'Total Discount';
            } else {
                $activeDiscount = ($grandDiscount ?? 0) + ($grandEmiDiscount ?? 0);
                $discountCardLabel = 'Total Discount';
            }
            if ($activeDiscount > 0): ?>
            <div class="summary-card amber">
                <div class="summary-card-label"><?= $discountCardLabel ?></div>
                <div class="summary-card-value" style="color: #d97706;">
                    ৳<?= number_format($activeDiscount, 2) ?>
                    <?php if (($paymentType === 'full_payment' || empty($paymentType)) && ($grandEmiDiscount ?? 0) > 0 && ($grandDiscount ?? 0) > 0): ?>
                        <div style="font-size: 8.5px; font-weight: normal; color: #b45309;">(Land: ৳<?= number_format($grandDiscount, 2) ?> | EMI: ৳<?= number_format($grandEmiDiscount, 2) ?>)</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            <div class="summary-card green">
                <div class="summary-card-label">Verified Paid</div>
                <div class="summary-card-value">৳<?= number_format($grandPaidVerified ?? $grandPaid, 2) ?></div>
            </div>
            <?php if (($grandPaidUnverified ?? 0) > 0): ?>
            <div class="summary-card amber">
                <div class="summary-card-label">Unverified (Pending) Paid</div>
                <div class="summary-card-value" style="color: #d97706;">৳<?= number_format($grandPaidUnverified, 2) ?></div>
            </div>
            <?php endif; ?>
            <div class="summary-card" style="border-left-color: #0d6efd;">
                <div class="summary-card-label">Total Collected (Combined)</div>
                <div class="summary-card-value" style="color: #0d6efd;">৳<?= number_format($grandPaid, 2) ?></div>
            </div>
            <div class="summary-card red">
                <div class="summary-card-label">Total Outstanding Due (Net)</div>
                <div class="summary-card-value">
                    ৳<?= number_format($grandDue, 2) ?>
                    <?php if (($grandDueVerified ?? 0) > 0 && ($grandPaidUnverified ?? 0) > 0): ?>
                        <div style="font-size: 8.5px; font-weight: normal; color: #991b1b; margin-top: 2px;">
                            (Verified Due: ৳<?= number_format($grandDueVerified, 2) ?> | Net after pending: ৳<?= number_format($grandDue, 2) ?>)
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Statement Table -->
        <table class="statement-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 25px;">#</th>
                    <th>Client Name</th>
                    <th>Contact Info</th>
                    <th>Project &amp; Unit(s)</th>
                    <th>Booking Date / Agent</th>
                    <?php if (!empty($paymentType)): ?>
                        <th class="text-end"><?= htmlspecialchars($targetColumnLabel) ?> (BDT)</th>
                        <th class="text-end">Paid (BDT)</th>
                        <th class="text-end">Due (BDT)</th>
                    <?php else: ?>
                        <th class="text-end">Total Paid (BDT)</th>
                    <?php endif; ?>
                    <th class="text-center">Last Payment</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clients)): ?>
                    <tr>
                        <td colspan="<?= !empty($paymentType) ? 10 : 8 ?>" class="text-center" style="padding: 25px; color: #64748b;">
                            No client records found matching the specified search or filter criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $sl = 1;
                    foreach ($clients as $c): 
                        $targetVal = 0;
                        $paidVal = 0;
                        $dueVal = 0;
                        if ($paymentType === 'full_payment' || empty($paymentType)) {
                            $targetVal = (float)($c['total_price_target'] ?? $c['total_agreement_value'] ?? 0);
                            $paidVal = (float)($c['total_paid'] ?? 0);
                            $dueVal = (float)($c['overall_due'] ?? 0);
                        } elseif ($paymentType === 'land_share') {
                            $targetVal = (float)($c['total_land_share_target'] ?? 0);
                            $paidVal = (float)($c['land_share_paid'] ?? 0);
                            $dueVal = (float)($c['land_share_due'] ?? 0);
                        } elseif ($paymentType === 'development_charge') {
                            $targetVal = (float)($c['total_dev_charge_target'] ?? 0);
                            $paidVal = (float)($c['dev_charge_paid'] ?? 0);
                            $dueVal = (float)($c['dev_charge_due'] ?? 0);
                        } elseif ($paymentType === 'shop_service_charge') {
                            $targetVal = (float)($c['total_shop_service_charge_target'] ?? 0);
                            $paidVal = (float)($c['shop_service_charge_paid'] ?? 0);
                            $dueVal = (float)($c['shop_service_charge_due'] ?? 0);
                        } elseif ($paymentType === 'construction_fee') {
                            $targetVal = (float)($c['total_construction_target'] ?? 0);
                            $paidVal = (float)($c['construction_paid'] ?? 0);
                            $dueVal = (float)($c['construction_due'] ?? 0);
                        }

                        $ver = $c['latest_verification_status'] ?? 'pending';
                        $statusClass = 'badge-pending';
                        if ($ver === 'verified') $statusClass = 'badge-verified';
                        elseif ($ver === 'rejected') $statusClass = 'badge-rejected';
                    ?>
                        <tr>
                            <td class="text-center"><?= $sl++ ?></td>
                            <td>
                                <strong><?= htmlspecialchars($c['client_name'] ?? 'N/A') ?></strong>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($c['phone'] ?? 'N/A') ?></div>
                                <?php if (!empty($c['email']) && $c['email'] !== 'N/A'): ?>
                                    <div style="color: #64748b; font-size: 9px;"><?= htmlspecialchars($c['email']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($c['project_flats_combined'])): 
                                    $props = explode(';;', $c['project_flats_combined']);
                                    foreach ($props as $pr):
                                        $parts = explode('||', $pr);
                                        $pn = $parts[0] ?? '';
                                        $fn = $parts[1] ?? '';
                                ?>
                                    <span class="unit-chip"><strong><?= htmlspecialchars($pn) ?>:</strong> <?= htmlspecialchars($fn) ?></span>
                                <?php endforeach; else: ?>
                                    <span style="color:#94a3b8;">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><?= !empty($c['booking_date']) ? date('d M, Y', strtotime($c['booking_date'])) : 'N/A' ?></div>
                                <?php if (!empty($c['agent_name'])): ?>
                                    <div style="color: #64748b; font-size: 9px;"><?= htmlspecialchars($c['agent_name']) ?></div>
                                <?php endif; ?>
                            </td>
                            <?php if (!empty($paymentType)): 
                                $rowDiscount = 0;
                                $discLabel = 'Disc';
                                if ($paymentType === 'full_payment') {
                                    $rowDiscount = (float)($c['total_discount'] ?? 0) + (float)($c['total_bulk_emi_discount'] ?? 0);
                                    $discLabel = 'Disc';
                                } elseif ($paymentType === 'land_share') {
                                    $rowDiscount = (float)($c['total_discount'] ?? 0);
                                    $discLabel = 'Disc';
                                } elseif ($paymentType === 'construction_fee') {
                                    $rowDiscount = (float)($c['total_bulk_emi_discount'] ?? 0);
                                    $discLabel = 'EMI Disc';
                                }
                            ?>
                                <td class="text-end">
                                    ৳<?= number_format($targetVal, 2) ?>
                                    <?php if ($rowDiscount > 0): ?>
                                        <div style="font-size: 8.5px; color: #d97706; font-weight: 600;">(Less <?= $discLabel ?>: ৳<?= number_format($rowDiscount, 2) ?>)</div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end" style="color: #15803d; font-weight: 600;">৳<?= number_format($paidVal, 2) ?></td>
                                <td class="text-end" style="color: <?= $dueVal > 0 ? '#b91c1c' : '#64748b' ?>; font-weight: 600;">৳<?= number_format($dueVal, 2) ?></td>
                            <?php else: ?>
                                <td class="text-end" style="color: #15803d; font-weight: 600;">৳<?= number_format($c['total_paid'] ?? 0, 2) ?></td>
                            <?php endif; ?>
                            <td class="text-center">
                                <?= !empty($c['last_payment_date']) ? date('d M, Y', strtotime($c['last_payment_date'])) : '<span style="color:#94a3b8;">None</span>' ?>
                            </td>
                            <td class="text-center">
                                <span class="badge-status <?= $statusClass ?>"><?= ucfirst(htmlspecialchars($ver)) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-end"><strong>GRAND TOTAL:</strong></td>
                    <?php if (!empty($paymentType)): 
                        $footDiscount = 0;
                        $footLabel = 'Total Disc';
                        if ($paymentType === 'construction_fee') {
                            $footDiscount = $grandEmiDiscount ?? 0;
                            $footLabel = 'Total EMI Disc';
                        } elseif ($paymentType === 'land_share') {
                            $footDiscount = $grandDiscount ?? 0;
                            $footLabel = 'Total Disc';
                        } else {
                            $footDiscount = ($grandDiscount ?? 0) + ($grandEmiDiscount ?? 0);
                            $footLabel = 'Total Disc';
                        }
                    ?>
                        <td class="text-end">
                            <strong>৳<?= number_format($grandTarget, 2) ?></strong>
                            <?php if ($footDiscount > 0): ?>
                                <div style="font-size: 8.5px; color: #d97706; font-weight: 600;">(<?= $footLabel ?>: ৳<?= number_format($footDiscount, 2) ?>)</div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end" style="color: #15803d;">
                            <strong>৳<?= number_format($grandPaid, 2) ?></strong>
                            <div style="font-size: 8.5px; color: #166534; font-weight: 600;">(Ver: ৳<?= number_format($grandPaidVerified ?? $grandPaid, 2) ?>)</div>
                            <?php if (($grandPaidUnverified ?? 0) > 0): ?>
                                <div style="font-size: 8.5px; color: #d97706; font-weight: 600;">(Pending: ৳<?= number_format($grandPaidUnverified, 2) ?>)</div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end" style="color: #b91c1c;">
                            <strong>৳<?= number_format($grandDue, 2) ?></strong>
                            <div style="font-size: 8.5px; color: #b91c1c; font-weight: 600;">Net Due</div>
                            <?php if (($grandDueVerified ?? 0) > 0 && ($grandPaidUnverified ?? 0) > 0): ?>
                                <div style="font-size: 8px; color: #64748b; font-weight: normal;">(Ver: ৳<?= number_format($grandDueVerified, 2) ?>)</div>
                            <?php endif; ?>
                        </td>
                    <?php else: ?>
                        <td class="text-end" style="color: #15803d;"><strong>৳<?= number_format($grandPaid, 2) ?></strong></td>
                    <?php endif; ?>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>

        <!-- Signatures -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-title">Prepared By</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-title">Checked By</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-title">Accounts Officer</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-title">Authorized Signature</div>
            </div>
        </div>
    </div>

</body>
</html>

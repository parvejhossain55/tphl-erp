<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= !empty($voucher['is_approval_voucher']) ? 'Approval' : 'Debit' ?> Voucher - <?= htmlspecialchars($voucher['voucher_no']) ?></title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 10px;
            color: #222;
            background: #f4f6f9;
        }
        .voucher-container {
            width: 100%;
            max-width: 820px;
            margin: 0 auto;
            border: 2px solid #222;
            padding: 12px 18px;
            position: relative;
            background: #fff;
            box-sizing: border-box;
            border-radius: 2px;
        }
        .header {
            text-align: center;
            border-bottom: 1.5px solid #222;
            padding-bottom: 3px;
            margin-bottom: 6px;
        }
        .header h2 {
            margin: 0;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 2px;
            background: #222;
            color: #fff;
            display: inline-block;
            padding: 3px 16px;
            border-radius: 3px;
        }
        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            line-height: 1.3;
        }
        .col {
            flex: 1;
        }
        .label {
            font-weight: bold;
            width: 85px;
            display: inline-block;
            font-size: 12px;
            color: #111;
        }
        .value {
            border-bottom: 1px dotted #888;
            display: inline-block;
            width: calc(100% - 95px);
            padding-left: 6px;
            font-size: 12px;
            color: #222;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            margin-bottom: 6px;
            font-size: 11.5px;
        }
        table, th, td {
            border: 1px solid #222;
        }
        th, td {
            padding: 4px 7px;
            text-align: left;
            line-height: 1.35;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            font-size: 11.5px;
        }
        .amount-row {
            font-weight: bold;
            text-align: right;
            font-size: 12px;
        }
        .amount-in-words {
            font-weight: bold;
            background: #f9f9f9;
            padding: 5px 10px;
            border: 1px solid #ccc;
            margin-top: 5px;
            font-size: 11.5px;
            line-height: 1.35;
        }
        .attachments-box {
            margin-top: 5px;
            padding: 4px 10px;
            background: #fafafa;
            border: 1px dashed #777;
            font-size: 11px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        /* Audit Trail Section */
        .audit-trail-section {
            margin-top: 6px;
            border: 1px solid #222;
            background: #fff;
            page-break-inside: avoid;
        }
        .audit-trail-title {
            background: #222;
            color: #fff;
            padding: 3px 8px;
            font-size: 10.5px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .audit-status-badge {
            background: #198754;
            color: #fff;
            padding: 1px 5px;
            border-radius: 2px;
            font-size: 9.5px;
        }
        .audit-grid {
            display: flex;
            border-top: 1px solid #222;
        }
        .audit-card {
            flex: 1;
            padding: 5px 8px;
            border-right: 1px solid #ddd;
            font-size: 11px;
        }
        .audit-card:last-child {
            border-right: none;
        }
        .audit-card-header {
            font-size: 10px;
            text-transform: uppercase;
            color: #333;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 2px;
            font-weight: bold;
        }
        .step-num {
            background: #222;
            color: #fff;
            width: 14px;
            height: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 9px;
            font-weight: bold;
        }
        .audit-user-name {
            font-size: 11px;
            font-weight: bold;
            color: #111;
            line-height: 1.25;
        }
        .audit-date {
            font-size: 10px;
            color: #555;
            margin-top: 1px;
        }
        .audit-stage-badge {
            display: inline-block;
            font-size: 8.5px;
            font-weight: bold;
            background: #e9ecef;
            color: #222;
            padding: 1px 4px;
            border-radius: 2px;
            margin-bottom: 1px;
            text-transform: uppercase;
        }
        .audit-approver-item {
            margin-bottom: 3px;
            padding-bottom: 2px;
            border-bottom: 1px dotted #e0e0e0;
        }
        .audit-approver-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .audit-approvers-container.is-multi {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px 6px;
        }
        .audit-note-box {
            font-size: 8.5px;
            color: #334155;
            background: #f8fafc;
            border-left: 2px solid #94a3b8;
            padding: 2px 4px;
            margin-top: 2px;
            border-radius: 2px;
            line-height: 1.25;
            word-break: break-word;
        }

        /* PRINT MEDIA QUERY: STRICT SINGLE-PAGE FIT WITH MINIMAL TOP/BOTTOM GAPS & FULL PAD PRESERVATION */
        @media print {
            @page {
                size: A4 portrait;
                margin: 0;
            }
            html, body {
                height: 100% !important;
                max-height: 100vh !important;
                overflow: hidden !important;
                margin: 0 !important;
                padding: 0 !important;
                background: transparent !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* FORCE UNIVERSAL PRINT PAD TO REMAIN VISIBLE AT ALL TIMES */
            .universal-print-pad {
                display: block !important;
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                height: 100vh !important;
                z-index: 0 !important;
                pointer-events: none !important;
                background: transparent !important;
            }

            /* Left Border Line (Wide Dark Green) */
            .upp-left-border {
                display: block !important;
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                width: 25px !important;
                height: 100vh !important;
                background-color: #11502e !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* Header (Logo): Position logo neatly at the top with minimal gap below */
            .upp-header {
                display: flex !important;
                position: absolute !important;
                top: 0 !important;
                left: 25px !important;
                right: 0 !important;
                height: 46px !important;
                padding: 2px 0 0 0 !important;
                background: transparent !important;
                border-bottom: none !important;
                align-items: center !important;
                justify-content: center !important;
            }
            .upp-header img {
                display: block !important;
                max-height: 42px !important;
                max-width: 280px !important;
                object-fit: contain !important;
                margin: 0 auto !important;
            }

            /* Footer: Keep exact official company pad design intact at bottom */
            .upp-footer {
                display: block !important;
                position: absolute !important;
                bottom: 0 !important;
                left: 25px !important;
                right: 0 !important;
                height: 86px !important;
                background-color: #11502e !important;
                color: #ffffff !important;
                padding: 8px 20px !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .upp-footer * {
                color: #ffffff !important;
            }

            /* Content Wrapper: Start right below logo (48px) with minimal gap, and extend down above footer (90px) */
            body .app__slide-wrapper, 
            html body .app__slide-wrapper,
            body .main-content {
                position: relative !important;
                z-index: 1 !important;
                margin-top: 48px !important;   /* sits immediately under the 46px logo header */
                margin-bottom: 90px !important; /* sits directly above the 86px footer pad */
                padding: 0 16px 0 34px !important; /* clears the 25px dark green left border stripe with clean breathing room */
                height: auto !important;
                min-height: auto !important;
                max-height: calc(100vh - 140px) !important;
                background: transparent !important;
                box-sizing: border-box !important;
            }

            /* Voucher Container: Strictly avoids page-break and fits 1 page */
            .voucher-container {
                margin: 0 auto !important;
                border: 1.5px solid #222 !important;
                padding: 5px 10px !important;
                width: 100% !important;
                max-width: 100% !important;
                background: #fff !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                page-break-after: avoid !important;
                break-after: avoid !important;
                page-break-before: avoid !important;
                break-before: avoid !important;
                box-sizing: border-box !important;
                box-shadow: none !important;
            }

            .voucher-container .header {
                border-bottom: 1.5px solid #222 !important;
                padding-bottom: 2px !important;
                margin-bottom: 3px !important;
                text-align: center !important;
            }
            .voucher-container .header h2 {
                font-size: 11px !important;
                padding: 2px 14px !important;
                letter-spacing: 1.5px !important;
                margin: 0 !important;
            }

            .voucher-container .row {
                margin-bottom: 2px !important;
                line-height: 1.2 !important;
            }
            .voucher-container .label {
                font-size: 10px !important;
                width: 72px !important;
            }
            .voucher-container .value {
                font-size: 10px !important;
                width: calc(100% - 80px) !important;
                padding-left: 4px !important;
            }

            /* Tighten table and rows so long descriptions naturally stay within 1 page */
            .voucher-container table {
                margin-top: 3px !important;
                margin-bottom: 3px !important;
                font-size: 9.5px !important;
                page-break-inside: avoid !important;
            }
            .voucher-container th, 
            .voucher-container td {
                padding: 2.5px 5px !important;
                line-height: 1.2 !important;
            }
            .voucher-container th {
                font-size: 9.5px !important;
            }
            .voucher-details-text {
                font-size: 9px !important;
                line-height: 1.2 !important;
                word-break: break-word !important;
            }
            .voucher-container .amount-row {
                font-size: 10px !important;
            }
            .voucher-container .amount-in-words {
                margin-top: 3px !important;
                padding: 2px 6px !important;
                font-size: 9.5px !important;
                line-height: 1.2 !important;
            }
            .voucher-container .attachments-box {
                margin-top: 3px !important;
                padding: 2px 6px !important;
                font-size: 9px !important;
                line-height: 1.2 !important;
            }

            /* Audit Trail Section */
            .voucher-container .audit-trail-section {
                margin-top: 4px !important;
                border: 1px solid #222 !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                page-break-after: avoid !important;
                break-after: avoid !important;
            }
            .voucher-container .audit-trail-title {
                padding: 2px 6px !important;
                font-size: 8.5px !important;
            }
            .voucher-container .audit-status-badge {
                font-size: 7.5px !important;
                padding: 0.5px 3px !important;
            }
            .voucher-container .audit-card {
                padding: 2.5px 4px !important;
                font-size: 8.5px !important;
            }
            .voucher-container .audit-card-header {
                font-size: 8px !important;
                margin-bottom: 1.5px !important;
                padding-bottom: 1px !important;
            }
            .voucher-container .step-num {
                width: 11px !important;
                height: 11px !important;
                font-size: 7px !important;
            }
            .voucher-container .audit-stage-badge {
                font-size: 6.5px !important;
                padding: 0.5px 2.5px !important;
                margin-bottom: 0.5px !important;
                line-height: 1 !important;
            }
            .voucher-container .audit-user-name {
                font-size: 8.5px !important;
                line-height: 1.1 !important;
            }
            .voucher-container .audit-date {
                font-size: 7.5px !important;
                margin-top: 0px !important;
            }
            .voucher-container .audit-approver-item {
                margin-bottom: 1.5px !important;
                padding-bottom: 1px !important;
            }
            .voucher-container .audit-note-box {
                font-size: 7px !important;
                padding: 1px 2px !important;
                margin-top: 1px !important;
                line-height: 1.1 !important;
                max-height: 24px !important;
                overflow: hidden !important;
            }

            .voucher-container .audit-approvers-container.is-multi {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 2px 4px !important;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body onload="<?php if (!isset($_GET['no_print'])): ?>window.print();<?php endif; ?>">

    <div class="no-print" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; max-width: 820px; margin-left: auto; margin-right: auto;">
        <div style="font-size: 14px; color: #555; font-weight: bold;">
            <i class="fa-solid fa-file-invoice-dollar"></i> Official <?= !empty($voucher['is_approval_voucher']) ? 'Approval' : 'Debit' ?> Voucher Preview
        </div>
        <div>
            <button onclick="window.print()" style="padding: 9px 18px; font-size: 14px; font-weight: bold; cursor: pointer; background: #000; color: #fff; border: none; border-radius: 4px;">
                🖨️ Print Voucher
            </button>
            <button onclick="window.print()" style="padding: 9px 18px; font-size: 14px; font-weight: bold; cursor: pointer; background: #198754; color: #fff; border: none; border-radius: 4px; margin-left: 8px;">
                📥 Save as PDF
            </button>
            <button onclick="window.close()" style="padding: 9px 18px; font-size: 14px; cursor: pointer; background: #6c757d; color: #fff; border: none; border-radius: 4px; margin-left: 8px;">
                ✕ Close
            </button>
        </div>
    </div>

    <div class="voucher-container">
        
        <div class="header">
            <h2><?= !empty($voucher['is_approval_voucher']) ? 'Approval Voucher' : 'Debit Voucher' ?></h2>
        </div>

        <div class="row">
            <div class="col" style="flex: 1.8;">
                <span class="label">Project:</span>
                <span class="value"><?= htmlspecialchars($voucher['project'] ?? 'Head Office / Operations') ?></span>
            </div>
            <div class="col" style="text-align: right; display: flex; justify-content: flex-end; align-items: baseline;">
                <span class="label" style="width: auto; margin-right: 8px; white-space: nowrap;">Voucher No:</span>
                <span class="value" style="width: auto; min-width: 130px; max-width: 250px; text-align: left; font-weight: bold; white-space: nowrap;"><?= htmlspecialchars($voucher['voucher_no']) ?></span>
            </div>
        </div>

        <div class="row">
            <div class="col" style="flex: 1.8;">
                <span class="label">Paid To:</span>
                <span class="value" style="font-weight: 600;"><?= htmlspecialchars($voucher['paid_to']) ?></span>
            </div>
            <div class="col" style="text-align: right; display: flex; justify-content: flex-end; align-items: baseline;">
                <span class="label" style="width: auto; margin-right: 8px; white-space: nowrap;">Date:</span>
                <span class="value" style="width: auto; min-width: 130px; max-width: 250px; text-align: left;"><?= htmlspecialchars(date('d-m-Y', strtotime($voucher['date']))) ?></span>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <span class="label">Purpose:</span>
                <span class="value"><?= htmlspecialchars($voucher['purpose']) ?></span>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 45px; text-align: center;">Sl</th>
                    <th style="width: 90px;">Date</th>
                    <th style="width: 130px;">Payment Method</th>
                    <th>Reference / Details</th>
                    <th style="text-align: right; width: 140px;">Amount (TK)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($voucher['transactions'])): ?>
                    <?php $i = 1; foreach ($voucher['transactions'] as $tx): ?>
                        <tr>
                            <td style="text-align: center;"><?= $i++ ?></td>
                            <td><?= htmlspecialchars(date('d-m-Y', strtotime($tx['transaction_date']))) ?></td>
                            <td><?= htmlspecialchars(ucfirst($tx['payment_method'])) ?></td>
                            <td>
                                <?php
                                    $details = [];
                                    if (!empty($tx['reference_number'])) $details[] = "Ref: " . $tx['reference_number'];
                                    if (!empty($tx['bank_name'])) $details[] = "Bank: " . $tx['bank_name'];
                                    if (!empty($tx['cheque_no'])) $details[] = "Cheque: " . $tx['cheque_no'];
                                    if (!empty($tx['online_type'])) $details[] = "Type: " . $tx['online_type'];
                                    $refText = implode(', ', $details);
                                    $descText = !empty($tx['description']) ? $tx['description'] : (!empty($voucher['expense_details']) ? $voucher['expense_details'] : '');
                                ?>
                                <?php if (!empty($refText)): ?>
                                    <div style="font-weight: 600; color: #111;"><?= htmlspecialchars($refText) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($descText)): ?>
                                    <div class="voucher-details-text" style="color: #333; margin-top: <?= !empty($refText) ? '4px' : '0' ?>; line-height: 1.4;">
                                        <strong>Details:</strong> <?= nl2br(htmlspecialchars($descText)) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right; font-weight: 600;"><?= number_format($tx['amount_paid'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">No transactions found.</td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td colspan="4" class="amount-row">Total Amount:</td>
                    <td class="amount-row"><?= number_format($voucher['amount'], 2) ?></td>
                </tr>
            </tbody>
        </table>

        <?php
            $displayWords = !empty($voucher['amount_words']) && trim($voucher['amount_words']) !== 'Taka Only'
                ? $voucher['amount_words']
                : (function_exists('amountInWords') ? amountInWords($voucher['amount'] ?? 0) : (class_exists('FormatHelper') ? FormatHelper::amountInWords($voucher['amount'] ?? 0) : ''));
        ?>
        <div class="amount-in-words">
            <strong>Amount in Words:</strong> <span style="font-weight: 600; text-transform: capitalize; color: #111;"><?= htmlspecialchars($displayWords) ?></span>
        </div>

        <?php if (!empty($voucher['approval_summary']) && (float)($voucher['approval_summary']['approved'] ?? 0) > 0): $aps = $voucher['approval_summary']; ?>
            <div class="amount-in-words" style="display:flex; flex-wrap:wrap; gap:14px; align-items:center; <?= !empty($aps['is_partial']) ? 'border-color:#0891b2; background:#ecfeff;' : '' ?>">
                <strong><?= !empty($aps['is_partial']) ? 'Partial Approval Summary:' : 'Approval Summary:' ?></strong>
                <span>Requested: <strong>&#2547; <?= number_format((float)$aps['requested'], 2) ?></strong></span>
                <?php if ((float)($aps['qc_approved'] ?? 0) > 0): ?>
                    <span>QC Approved: <strong>&#2547; <?= number_format((float)$aps['qc_approved'], 2) ?></strong></span>
                <?php endif; ?>
                <span style="color:#047857;">Management Approved: <strong>&#2547; <?= number_format((float)$aps['approved'], 2) ?></strong></span>
                <?php if ((float)($aps['remaining'] ?? 0) > 0): ?>
                    <span style="color:#b91c1c;">Remaining (Unapproved): <strong>&#2547; <?= number_format((float)$aps['remaining'], 2) ?></strong></span>
                <?php endif; ?>
                <?php if ((float)($aps['paid'] ?? 0) > 0): ?>
                    <span>Paid: <strong>&#2547; <?= number_format((float)$aps['paid'], 2) ?></strong></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="attachments-box">
            <span style="font-weight: bold; color: #222;">Attachments Verified:</span>
            <span><input type="checkbox" checked onclick="return false;" style="vertical-align: middle; margin-right: 4px;"> Bill / Invoice Attached</span>
            <span><input type="checkbox" checked onclick="return false;" style="vertical-align: middle; margin-right: 4px;"> Approval Requisition / Note</span>
            <span><input type="checkbox" checked onclick="return false;" style="vertical-align: middle; margin-right: 4px;"> Payment / Money Receipt</span>
        </div>

        <!-- Approval & Payment Audit Trail -->
        <div class="audit-trail-section">
            <div class="audit-trail-title">
                <span><i class="fa-solid fa-clipboard-check"></i> System Audit & Approval Trail</span>
                <?php if (!empty($voucher['audit_trail']['disburser']['is_disbursed'])): ?>
                    <span class="audit-status-badge">Settled & Verified</span>
                <?php else: ?>
                    <span class="audit-status-badge" style="background: #f59e0b;">Pending / In Progress</span>
                <?php endif; ?>
            </div>
            
            <div class="audit-grid">
                <!-- 1. Created By -->
                <div class="audit-card">
                    <div class="audit-card-header">
                        <span class="step-num">1</span>
                        <span>Created By</span>
                    </div>
                    <div class="audit-card-body">
                        <div class="audit-user-name">
                            <?= htmlspecialchars($voucher['audit_trail']['creator']['name'] ?? 'Authorized Staff') ?>
                        </div>
                        <div class="audit-date">
                            <i class="fa-regular fa-clock"></i>
                            <?= htmlspecialchars($voucher['audit_trail']['creator']['date'] ?? date('d M, Y', strtotime($voucher['date']))) ?>
                        </div>
                    </div>
                </div>

                <!-- 2. Approved By -->
                <div class="audit-card" style="flex: 2;">
                    <div class="audit-card-header">
                        <span class="step-num">2</span>
                        <span>Approved By (Hierarchy)</span>
                    </div>
                    <div class="audit-card-body">
                        <?php if (!empty($voucher['audit_trail']['approvers'])): ?>
                            <div class="audit-approvers-container <?= count($voucher['audit_trail']['approvers']) > 1 ? 'is-multi' : '' ?>">
                                <?php foreach ($voucher['audit_trail']['approvers'] as $appr): ?>
                                    <div class="audit-approver-item">
                                        <span class="audit-stage-badge"><?= htmlspecialchars($appr['stage']) ?></span>
                                        <div class="audit-user-name"><?= htmlspecialchars($appr['name']) ?></div>
                                        <?php if (!empty($appr['date'])): ?>
                                            <div class="audit-date"><i class="fa-regular fa-clock"></i> <?= htmlspecialchars($appr['date']) ?></div>
                                        <?php endif; ?>
                                        <?php if (isset($appr['amount']) && $appr['amount'] !== null): ?>
                                            <?php $amtColor = (($appr['amount_label'] ?? '') === 'Remaining') ? '#b91c1c' : ((($appr['amount_label'] ?? '') === 'Partially Approved') ? '#0e7490' : '#047857'); ?>
                                            <div style="font-size: 10px; font-weight: 700; color: <?= $amtColor ?>; margin-top: 2px;">
                                                <?= htmlspecialchars($appr['amount_label'] ?? 'Approved') ?>: &#2547; <?= number_format((float)$appr['amount'], 2) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($appr['note']) && strpos(strtolower($appr['note']), 'auto-') === false): ?>
                                            <div class="audit-note-box">
                                                <strong>Note:</strong> <?= nl2br(htmlspecialchars($appr['note'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="audit-user-name">Management / Authority</div>
                            <div class="audit-date"><i class="fa-solid fa-check"></i> Approved & Verified</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 3. Accounts Head Approval (Md. Mamunoor Rashid) -->
                <div class="audit-card" style="flex: 1.25;">
                    <div class="audit-card-header">
                        <span class="step-num">3</span>
                        <span>Accounts Head Approval</span>
                    </div>
                    <div class="audit-card-body">
                        <span class="audit-stage-badge" style="background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; font-size: 8.5px;">
                            <?= htmlspecialchars($voucher['audit_trail']['accounts_head']['designation'] ?? 'AGM - Finance & Accounts') ?>
                        </span>
                        <div class="audit-user-name" style="color: #0f172a; margin-top: 2px;">
                            <?= htmlspecialchars($voucher['audit_trail']['accounts_head']['name'] ?? 'Md. Mamunoor Rashid [1309]') ?>
                        </div>
                        <?php if (!empty($voucher['audit_trail']['accounts_head']['date'])): ?>
                            <div class="audit-date">
                                <i class="fa-regular fa-clock"></i> <?= htmlspecialchars($voucher['audit_trail']['accounts_head']['date']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($voucher['audit_trail']['accounts_head']['note'])): ?>
                            <div style="font-size: 9.5px; color: #0f172a; background: #f0fdf4; border-left: 2.5px solid #16a34a; padding: 2.5px 5px; margin-top: 3px; border-radius: 2px; line-height: 1.35; word-break: break-word;">
                                <strong style="color: #15803d;">Note:</strong> <?= nl2br(htmlspecialchars($voucher['audit_trail']['accounts_head']['note'])) ?>
                            </div>
                        <?php endif; ?>
                        <div style="font-size: 10px; color: <?= !empty($voucher['audit_trail']['accounts_head']['is_approved']) ? '#16a34a' : '#d97706' ?>; font-weight: bold; margin-top: 3px;">
                            ✓ <?= htmlspecialchars($voucher['audit_trail']['accounts_head']['status_label'] ?? 'Approved & Authorized') ?>
                        </div>
                    </div>
                </div>

                <!-- 4. Paid & Disbursed By -->
                <div class="audit-card">
                    <div class="audit-card-header">
                        <span class="step-num">4</span>
                        <span>Paid & Disbursed By</span>
                    </div>
                    <div class="audit-card-body">
                        <div class="audit-user-name">
                            <?= htmlspecialchars($voucher['audit_trail']['disburser']['name'] ?? 'Accounts & Finance') ?>
                        </div>
                        <?php if (!empty($voucher['audit_trail']['disburser']['date'])): ?>
                            <div class="audit-date">
                                <i class="fa-regular fa-clock"></i>
                                <?= htmlspecialchars($voucher['audit_trail']['disburser']['date']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($voucher['audit_trail']['disburser']['is_disbursed'])): ?>
                            <div style="font-size: 11px; color: #198754; font-weight: bold; margin-top: 4px;">
                                ✓ Disbursed (<?= htmlspecialchars($voucher['payment_method'] ?? 'Cash/Bank') ?>)
                            </div>
                        <?php else: ?>
                            <div style="font-size: 11px; color: #d97706; font-weight: bold; margin-top: 4px;">
                                ⏳ Pending Disbursement
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>

</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Debit Voucher - <?= htmlspecialchars($voucher['voucher_no']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .voucher-container {
            width: 100%;
            max-width: 820px;
            margin: 0 auto;
            border: 2px solid #222;
            padding: 24px;
            position: relative;
            background: #fff;
            box-sizing: border-box;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #222;
            padding-bottom: 8px;
            margin-bottom: 16px;
        }
        .header h2 {
            margin: 0;
            font-size: 17px;
            text-transform: uppercase;
            letter-spacing: 2px;
            background: #222;
            color: #fff;
            display: inline-block;
            padding: 4px 18px;
            border-radius: 4px;
        }
        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .col {
            flex: 1;
        }
        .label {
            font-weight: bold;
            width: 120px;
            display: inline-block;
            font-size: 13px;
        }
        .value {
            border-bottom: 1px dotted #888;
            display: inline-block;
            width: calc(100% - 130px);
            padding-left: 8px;
            font-size: 13px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            margin-bottom: 14px;
            font-size: 13px;
        }
        table, th, td {
            border: 1px solid #222;
        }
        th, td {
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .amount-row {
            font-weight: bold;
            text-align: right;
        }
        .amount-in-words {
            font-weight: bold;
            background: #f9f9f9;
            padding: 8px 12px;
            border: 1px solid #ccc;
            margin-top: 8px;
            font-size: 13px;
        }
        .attachments-box {
            margin-top: 12px;
            padding: 8px 12px;
            background: #fafafa;
            border: 1px dashed #777;
            font-size: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        /* Audit Trail Section */
        .audit-trail-section {
            margin-top: 14px;
            border: 1px solid #222;
            background: #fff;
            page-break-inside: avoid;
        }
        .audit-trail-title {
            background: #222;
            color: #fff;
            padding: 4px 10px;
            font-size: 11px;
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
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
        }
        .audit-grid {
            display: flex;
            border-top: 1px solid #222;
        }
        .audit-card {
            flex: 1;
            padding: 8px 10px;
            border-right: 1px solid #ddd;
            font-size: 12px;
        }
        .audit-card:last-child {
            border-right: none;
        }
        .audit-card-header {
            font-size: 11px;
            text-transform: uppercase;
            color: #333;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 3px;
            font-weight: bold;
        }
        .step-num {
            background: #222;
            color: #fff;
            width: 16px;
            height: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 10px;
            font-weight: bold;
        }
        .audit-user-name {
            font-size: 12px;
            font-weight: bold;
            color: #111;
            line-height: 1.3;
        }
        .audit-date {
            font-size: 11px;
            color: #555;
            margin-top: 2px;
        }
        .audit-stage-badge {
            display: inline-block;
            font-size: 9px;
            font-weight: bold;
            background: #e9ecef;
            color: #222;
            padding: 1px 5px;
            border-radius: 2px;
            margin-bottom: 2px;
            text-transform: uppercase;
        }
        .audit-approver-item {
            margin-bottom: 6px;
            padding-bottom: 5px;
            border-bottom: 1px dotted #e0e0e0;
        }
        .audit-approver-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        @media print {
            body {
                padding: 0;
                background: #fff;
            }
            .voucher-container {
                border: 2px solid #222 !important;
                padding: 16px !important;
                max-width: 100% !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; max-width: 820px; margin-left: auto; margin-right: auto;">
        <div style="font-size: 14px; color: #555; font-weight: bold;">
            <i class="fa-solid fa-file-invoice-dollar"></i> Official Debit Voucher Preview
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
            <h2>Debit Voucher</h2>
        </div>

        <div class="row">
            <div class="col" style="flex: 2;">
                <span class="label">Project:</span>
                <span class="value"><?= htmlspecialchars($voucher['project']) ?></span>
            </div>
            <div class="col" style="text-align: right;">
                <span class="label" style="width: auto; margin-right: 10px;">Voucher No:</span>
                <span class="value" style="width: 160px; text-align: left; font-weight: bold;"><?= htmlspecialchars($voucher['voucher_no']) ?></span>
            </div>
        </div>

        <div class="row">
            <div class="col" style="flex: 2;">
                <span class="label">Paid To:</span>
                <span class="value" style="font-weight: 600;"><?= htmlspecialchars($voucher['paid_to']) ?></span>
            </div>
            <div class="col" style="text-align: right;">
                <span class="label" style="width: auto; margin-right: 10px;">Date:</span>
                <span class="value" style="width: 160px; text-align: left;"><?= htmlspecialchars(date('d-m-Y', strtotime($voucher['date']))) ?></span>
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
                                    <div style="color: #333; font-size: 12px; margin-top: <?= !empty($refText) ? '4px' : '0' ?>; line-height: 1.4;">
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

        <div class="amount-in-words">
            Amount in Words: <span style="text-transform: capitalize; font-weight: normal;"><?= htmlspecialchars($voucher['amount_words']) ?></span>
        </div>

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
                <span class="audit-status-badge">Settled & Verified</span>
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
                <div class="audit-card" style="flex: 1.4;">
                    <div class="audit-card-header">
                        <span class="step-num">2</span>
                        <span>Approved By (Hierarchy)</span>
                    </div>
                    <div class="audit-card-body">
                        <?php if (!empty($voucher['audit_trail']['approvers'])): ?>
                            <?php foreach ($voucher['audit_trail']['approvers'] as $appr): ?>
                                <div class="audit-approver-item">
                                    <span class="audit-stage-badge"><?= htmlspecialchars($appr['stage']) ?></span>
                                    <div class="audit-user-name"><?= htmlspecialchars($appr['name']) ?></div>
                                    <?php if (!empty($appr['date'])): ?>
                                        <div class="audit-date"><i class="fa-regular fa-clock"></i> <?= htmlspecialchars($appr['date']) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="audit-user-name">Management / Authority</div>
                            <div class="audit-date"><i class="fa-solid fa-check"></i> Approved & Verified</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 3. Paid & Disbursed By -->
                <div class="audit-card">
                    <div class="audit-card-header">
                        <span class="step-num">3</span>
                        <span>Paid & Disbursed By</span>
                    </div>
                    <div class="audit-card-body">
                        <div class="audit-user-name">
                            <?= htmlspecialchars($voucher['audit_trail']['disburser']['name'] ?? 'Accounts & Finance') ?>
                        </div>
                        <div class="audit-date">
                            <i class="fa-regular fa-clock"></i>
                            <?= htmlspecialchars($voucher['audit_trail']['disburser']['date'] ?? date('d M, Y', strtotime($voucher['date']))) ?>
                        </div>
                        <div style="font-size: 11px; color: #198754; font-weight: bold; margin-top: 4px;">
                            ✓ Disbursed (<?= htmlspecialchars($voucher['payment_method'] ?? 'Cash/Bank') ?>)
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</body>
</html>

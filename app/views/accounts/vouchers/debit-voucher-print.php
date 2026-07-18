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
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #222;
            padding: 30px;
            position: relative;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #222;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 18px;
            text-transform: uppercase;
            letter-spacing: 2px;
            background: #222;
            color: #fff;
            display: inline-block;
            padding: 5px 15px;
            border-radius: 4px;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 14px;
        }
        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .col {
            flex: 1;
        }
        .label {
            font-weight: bold;
            width: 120px;
            display: inline-block;
        }
        .value {
            border-bottom: 1px dotted #999;
            display: inline-block;
            width: calc(100% - 130px);
            padding-left: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #222;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
        }
        .amount-row {
            font-weight: bold;
            text-align: right;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 80px;
        }
        .signature-box {
            text-align: center;
            width: 22%;
        }
        .signature-line {
            border-top: 1px solid #222;
            margin-bottom: 5px;
        }
        .amount-in-words {
            font-weight: bold;
            background: #f9f9f9;
            padding: 10px;
            border: 1px solid #ccc;
            margin-top: 10px;
        }
        @media print {
            body {
                padding: 0;
            }
            .voucher-container {
                border: none;
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer; background: #000; color: #fff; border: none; border-radius: 4px;">Print Voucher</button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 16px; cursor: pointer; background: #ccc; color: #333; border: none; border-radius: 4px; margin-left: 10px;">Close</button>
    </div>

    <div class="voucher-container">
        
        <div class="header">
            <h1>The Premium Homes Ltd</h1>
            <p>Corporate Office, Dhaka, Bangladesh</p>
            <h2>Debit Voucher</h2>
        </div>

        <div class="row">
            <div class="col" style="flex: 2;">
                <span class="label">Project:</span>
                <span class="value"><?= htmlspecialchars($voucher['project']) ?></span>
            </div>
            <div class="col" style="text-align: right;">
                <span class="label" style="width: auto; margin-right: 10px;">Voucher No:</span>
                <span class="value" style="width: 150px; text-align: left;"><?= htmlspecialchars($voucher['voucher_no']) ?></span>
            </div>
        </div>

        <div class="row">
            <div class="col" style="flex: 2;">
                <span class="label">Paid To:</span>
                <span class="value"><?= htmlspecialchars($voucher['paid_to']) ?></span>
            </div>
            <div class="col" style="text-align: right;">
                <span class="label" style="width: auto; margin-right: 10px;">Date:</span>
                <span class="value" style="width: 150px; text-align: left;"><?= htmlspecialchars(date('d-m-Y', strtotime($voucher['date']))) ?></span>
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
                    <th style="width: 50px;">Sl No.</th>
                    <th>Date</th>
                    <th>Payment Method</th>
                    <th>Reference / Details</th>
                    <th style="text-align: right; width: 150px;">Amount (TK)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($voucher['transactions'])): ?>
                    <?php $i = 1; foreach ($voucher['transactions'] as $tx): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars(date('d-m-Y', strtotime($tx['transaction_date']))) ?></td>
                            <td><?= htmlspecialchars(ucfirst($tx['payment_method'])) ?></td>
                            <td>
                                <?php
                                    $details = [];
                                    if (!empty($tx['reference_number'])) $details[] = "Ref: " . $tx['reference_number'];
                                    if (!empty($tx['bank_name'])) $details[] = "Bank: " . $tx['bank_name'];
                                    if (!empty($tx['cheque_no'])) $details[] = "Cheque: " . $tx['cheque_no'];
                                    if (!empty($tx['online_type'])) $details[] = "Type: " . $tx['online_type'];
                                    echo htmlspecialchars(implode(', ', $details));
                                ?>
                            </td>
                            <td style="text-align: right;"><?= number_format($tx['amount_paid'], 2) ?></td>
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

        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line"></div>
                Prepared By
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                Checked By
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                Authorized By
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                Received By
            </div>
        </div>

    </div>

</body>
</html>

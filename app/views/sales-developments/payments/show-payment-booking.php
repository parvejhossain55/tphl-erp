<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" type="image/x-icon" href="assets/images/logo/tphl_logo.png">
    <title>Invoice</title>

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 20px;
        }

        .invoice-wrapper {
            max-width: 850px;
            margin: auto;
            background: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .site-logo {
            text-align: center;
            /* margin-bottom: 20px; */
        }

        .site-logo img {
            max-height: 100px;
            width: 200px;
            object-fit: contain;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .company h2 {
            margin: 0;
            color: #2c3e50;
        }

        .company p {
            margin: 3px 0;
            font-size: 13px;
            color: #555;
        }

        .invoice-title h1 {
            margin: 0;
            font-size: 30px;
            color: #27ae60;
        }

        .invoice-title p {
            margin: 5px 0 0;
            font-size: 13px;
            color: #777;
        }

        .section {
            margin-bottom: 25px;
        }

        .section h3 {
            font-size: 16px;
            color: #2c3e50;
            border-left: 4px solid #27ae60;
            padding-left: 10px;
            margin-bottom: 12px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px 30px;
            font-size: 14px;
        }

        .info-grid span {
            color: #555;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        table th {
            background: #f1f3f6;
            padding: 12px;
            text-align: left;
            color: #333;
        }

        table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .text-right {
            text-align: right;
        }

        .total-box {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
        }

        .total {
            width: 320px;
            background: #f9fafb;
            border-radius: 8px;
            padding: 20px;
        }

        .total div {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .total .grand {
            font-size: 18px;
            font-weight: bold;
            color: #27ae60;
            border-top: 1px dashed #ccc;
            padding-top: 10px;
        }

        .footer {
            text-align: center;
            font-size: 13px;
            color: #777;
            margin-top: 40px;
        }

        .print-btn {
            margin-bottom: 20px;
            text-align: right;
        }

        .print-btn button {
            padding: 8px 16px;
            background: #27ae60;
            border: none;
            color: #fff;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .invoice-wrapper {
            max-width: 850px;
            margin: auto;
            background: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);

            display: flex;
            flex-direction: column;
            min-height: 100vh;
            /* pur page height */
        }

        .site-logo {
            text-align: center;
            margin-bottom: 20px;
            /* logo niche space */
        }

        .footer {
            font-size: 13px;
            color: #777;
            margin-top: auto;
            /* pushes footer to bottom */
            text-align: center;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
                margin: 0;
            }

            .invoice-wrapper {
                box-shadow: none;
                border-radius: 0;
                padding: 20px;
                min-height: auto;
                /* print e full vh na lagbe */
            }

            .print-btn {
                display: none;
            }

            .footer {
                margin-top: 40px;
                /* bottom e thakbe */
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>

    <div class="invoice-wrapper">

        <!-- LOGO CENTERED -->
        <div class="site-logo mt-3">
            <img src="<?= getImage('settings', getSetting('white_logo')) ?>" alt="<?= getSetting('website_title', 'Site Logo') ?>">
        </div>

        <div class="print-btn">
            <button onclick="window.print()">🖨 Print Invoice</button>
        </div>

        <!-- HEADER -->
        <div class="invoice-header">
            <div class="company">
                <h2><?= getSetting('website_title', '') ?></h2>
                <p>Real Estate & Property Developer</p>
                <p>Phone: <?= getSetting('phone', '') ?></p>
                <p>Email: <?= getSetting('email', '') ?></p>
            </div>

            <div class="invoice-title">
                <h1>INVOICE</h1>
                <p><strong>ID:</strong> INV-<?= date('Y') ?>-<?= str_pad($invoice['id'], 5, '0', STR_PAD_LEFT) ?></p>
                <p><strong>Date:</strong> <?= date('d M Y', strtotime($invoice['payment_date'])) ?></p>
            </div>
        </div>

        <!-- CUSTOMER INFO -->
        <div class="section">
            <h3>Client Information</h3>
            <div class="info-grid">
                <span><strong>Client Name:</strong> <?= $invoice['full_name'] ?></span>
                <span><strong>Project:</strong> <?= $invoice['project_name'] ?></span>
                <?php if (!empty($invoice['bank_name'])): ?>
                    <span><strong>Bank Info:</strong> <?= $invoice['bank_name'] ?></span>
                    <span><strong>Account No:</strong> <?= $invoice['bank_account_no'] ?></span>
                <?php endif; ?>
                <?php if (!empty($invoice['cheque_no'])): ?>
                    <span><strong>Check No:</strong> <?= $invoice['cheque_no'] ?></span>
                <?php endif ?>
                <?php if (!empty($invoice['online_type'])): ?>
                    <span><strong>Online Type:</strong> <?= $invoice['online_type'] ?></span>
                <?php endif ?>
                <span><strong>Unit:</strong> <?= $invoice['unit_name'] ?></span>
                <span><strong>Floor:</strong> <?= $invoice['floor_no'] ?></span>
            </div>
        </div>

        <!-- PAYMENT DETAILS -->
        <div class="section">
            <h3>Payment Details</h3>
            <table>
                <thead>
                    <tr>
                        <th>Total Share Price</th>
                        <th>Description</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>BDT <?= number_format($invoice['price_value'], 2) ?></td>
                        <td><?= ucfirst($invoice['payment_type']) ?> Payment</td>
                        <td><?= ucfirst($invoice['payment_method']) ?></td>
                        <td><?= ucfirst($invoice['payment_status']) ?></td>
                        <td class="text-right"><?= number_format($invoice['amount'], 2) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- TOTAL -->
        <div class="total-box">
            <div class="total">
                <div>
                    <span>Agreement Value/Booking Money</span>
                    <span><?= number_format($invoice['agreement_value'], 2) ?></span>
                </div>
                <div class="grand">
                    <span>Paid Amount</span>
                    <span><?= number_format($invoice['amount'], 2) ?></span>
                    <span></span>
                </div>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="footer mt-3">
            Payment received by <strong><?= $invoice['created_by_name'] ?? 'System' ?></strong><br>
            Thank you for doing business with us.
        </div>

    </div>

</body>

</html>
<?php
// Disable layout for this page
$disable_layout = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Invoice - #<?= str_pad($transaction['id'] ?? '0', 6, '0', STR_PAD_LEFT) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .no-print {
                display: none !important;
                margin: 0;
                padding: 0;
                background: transparent;
            }

            body {
                margin: 0;
                padding: 20px;
            }

        }



        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }

        body.pdf-mode .invoice-details {
    grid-template-columns: 1fr 1fr !important;
}

          body.pdf-mode .invoice-container {
            padding: 40px;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 0px;
            
        }
          body.pdf-mode .invoice-container {
            padding: 40px;
        }
        .invoice-header {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
            padding: 20px;
            position: relative;
            overflow: hidden;
            border-radius: 5px;
            margin-bottom: 5px;
        }

        .invoice-middle-header {
            padding: 40px 0;
            position: relative;
            overflow: hidden;
            border-radius: 5px;
            margin-bottom: 15px;
        }
         body.pdf-mode .invoice-middle-header {
            padding: 40px 0;
            position: relative;
            overflow: hidden;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .company-info {
            display: flex;
            justify-content: space-between;
            align-items: start;
        }

        .company-logo h1 {
            font-size: 24px;
        }

        .company-logo p {
            font-size: 14px;
            line-height: 1.6;
        }
         body.pdf-mode .company-logo h1 {
            font-size: 24px;
        }

         body.pdf-mode .company-logo p {
            font-size: 14px;
            line-height: 1.6;
        }

        .invoice-title {
            text-align: right;
        }

        .invoice-title h2 {
            font-size: 22px;
        }
         body.pdf-mode .invoice-title h2 {
            font-size: 22px;
        }

        .invoice-number {
            font-size: 14px;
        }
         body.pdf-mode .invoice-number {
            font-size: 14px;
        }

        .paid-stamp {
            display: inline-block;
            padding: 8px 20px;
            background: #27ae60;
            color: white;
            border-radius: 4px;
            font-weight: bold;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .pending-stamp {
            display: inline-block;
            padding: 8px 20px;
            background: #f39c12;
            color: white;
            border-radius: 4px;
            font-weight: bold;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .invoice-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        body.pdf-mode  .invoice-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .detail-section h3 {
            font-size: 16px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
         body.pdf-mode  .detail-section h3 {
            font-size: 16px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        body.pdf-mode  .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #ecf0f1;
        }

        .detail-label {
            font-weight: 500;
        }

        .detail-value {
            font-weight: 600;
        }

        .payment-details {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
       body.pdf-mode   .payment-details {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .payment-details h3 {
            margin-bottom: 20px;
            font-size: 18px;
        }

        .payment-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            font-size: 15px;
        }
        body.pdf-mode  .payment-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            font-size: 15px;
        }

        .payment-row.total {
            border-top: 2px solid #2c3e50;
            margin-top: 15px;
            padding-top: 15px;
            font-size: 20px;
            font-weight: bold;
        }

        .payment-method-details {
            background: #e8f4f8;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #3498db;
        }

        .payment-method-details h4 {
            margin-bottom: 15px;
            font-size: 16px;
        }

        .method-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .method-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }

        .invoice-footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #ecf0f1;
            text-align: center;
            font-size: 13px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            justify-content: flex-end;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #229954;
        }

        .amount-highlight {
            font-size: 24px;
            font-weight: bold;
            color: #000;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }

        .badge-emi {
            background: #cce5ff;
            color: #004085;
        }

        .badge-booking {
            background: #e2e3e5;
            color: #383d41;
        }


        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            padding-top: 20px;
        }

        .signature-box {
            text-align: center;
        }

        .signature-line {
            border-top: 2px solid #000;
            margin-top: 50px;
            padding-top: 10px;
            font-weight: 600;
        }

        .notes-section {
            background: #fff9e6;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
            border-left: 4px solid #f39c12;
        }

        .notes-section h4 {
            margin-bottom: 10px;
        }

        .notes-section p {
            line-height: 1.6;
            font-size: 14px;
            margin: 5px 0;
        }

        @media(max-width: 575px) {
            .action-buttons {
                gap: 10px;
                margin-bottom: 15px;
            }

            .btn {
                padding: 12px 12px;
                font-size: 12px;
            }

            .invoice-container {

                padding: 16px;
            }

            .invoice-header {
                padding: 14px;
                margin-bottom: 0px;
            }

            .invoice-middle-header {
                padding: 28px 0;
                margin-bottom: 10px;
            }

            .company-logo h1 {
                font-size: 14px;
            }

            .invoice-title h2 {
                font-size: 14px;
            }

            .company-logo p {
                font-size: 10px;
            }

            .invoice-number {
                font-size: 10px;
            }

            .paid-stamp {
                padding: 6px 12px;
                font-size: 12px;
            }

            .detail-section h3 {
                font-size: 14px;
                margin-bottom: 14px;
            }

            .detail-row {
                padding: 8px 0;
                font-size: 12px;
            }

            .invoice-details {
                display: grid;
                grid-template-columns: 1fr;
                gap: 30px;
                margin-bottom: 14px;
            }

            .payment-details {
                padding: 7px;
                margin-bottom: 5px;
            }

            .payment-details h3 {
                font-size: 14px
            }

            .payment-row {
                font-size: 12px;
            }

            .badge {
                padding: 0px 0px;
            }

            .payment-row.total {
                font-size: 16px;
            }

            .amount-highlight {
                font-size: 16px;
            }

            .signature-line {
                font-size: 11px;
            }
        }
    </style>
</head>
<body>
    <div class="action-buttons no-print">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Print Invoice
        </button>
        <button onclick="downloadPDF()" class="btn btn-success">
            <i class="fas fa-download"></i> Download PDF
        </button>
        <a href="<?= $GLOBALS['base_url'] ?>/management-cash-in" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <div class="invoice-container" id="invoice-content">
        <!-- Header -->
        <div class="invoice-header">
            <div class="company-info">
                <div class="company-logo">
                    <h1>The Premium Homes Ltd.</h1>
                </div>
                <div class="invoice-title">
                    <h2>TRANSACTION RECEIPT</h2>
                </div>
            </div>
        </div>
        <div class="invoice-middle-header">
            <div class="company-info">
                <div class="company-logo">
                    <p>Real Estate & Property Management</p>
                    <p>Land View Commercial Center, 9th Floor 28 Gulshan North C/A, <br> Gulshan Circle-2, Dhaka</p>
                    <p>Phone: +8801958253300</p>
                    <p>Email: info@thepremiumhomes.com</p>
                </div>
                <div class="invoice-title">
                    <h2>TRANSACTION RECEIPT</h2>
                    <p class="invoice-number">#MGT-REC-<?= str_pad($transaction['id'] ?? '0', 6, '0', STR_PAD_LEFT) ?></p>
                    <p class="invoice-number">Date: <?= date('d M, Y', strtotime($transaction['transaction_date'] ?? 'now')) ?></p>
                    <?php if (($transaction['payment_status'] ?? 'paid') === 'paid'): ?>
                        <span class="paid-stamp">PAID</span>
                    <?php else: ?>
                        <span class="pending-stamp">PENDING</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Role & Transaction Details -->
        <div class="invoice-details">
            <div class="detail-section">
                <h3>Role Information</h3>
                <div class="detail-row">
                    <span class="detail-label">Designation:</span>
                    <span class="detail-value"><?= htmlspecialchars($transaction['role_name'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"> Name:</span>
                    <span class="detail-value"><?= htmlspecialchars($transaction['person_name'] ?? 'N/A') ?></span>
                </div>

            </div>

            <div class="detail-section">
                <h3>Transaction Information</h3>
                <div class="detail-row">
                    <span class="detail-label">Source:</span>
                    <span class="detail-value"><?= htmlspecialchars($transaction['source'] ?? 'N/A') ?></span>
                </div>
                <!-- <div class="detail-row">
                    <span class="detail-label">Created By:</span>
                    <span class="detail-value"><?= htmlspecialchars($transaction['created_by_name'] ?? 'N/A') ?></span>
                </div> -->
            </div>
        </div>

        <!-- Payment Details -->
        <div class="payment-details">
            <h3>Payment Details</h3>
            <div class="payment-row">
                <span>Transaction Date:</span>
                <span style="font-weight: 600;"><?= date('d M, Y', strtotime($transaction['transaction_date'] ?? 'now')) ?></span>
            </div>
            <div class="payment-row">
                <span>Payment Method:</span>
                <span style="font-weight: 600;"><?= strtoupper($transaction['payment_method'] ?? 'CASH') ?></span>
            </div>
            <?php if (!empty($transaction['transaction_ref'])): ?>
            <div class="payment-row">
                <span>Transaction Reference:</span>
                <span style="font-weight: 600;"><?= htmlspecialchars($transaction['transaction_ref']) ?></span>
            </div>
            <?php endif; ?>
            <div class="payment-row total">
                <span>Amount:</span>
                <span class="amount-highlight">TK.<?= number_format($transaction['amount'] ?? 0, 2) ?></span>
            </div>
        </div>

        <!-- Payment Method Details -->
        <?php if ($transaction['payment_method'] !== 'cash'): ?>
            <div class="payment-method-details">
                <h4>Payment Method: <?= strtoupper($transaction['payment_method'] ?? 'N/A') ?></h4>
                <div class="method-info">
                    <?php if ($transaction['payment_method'] === 'bank'): ?>
                        <?php if (!empty($transaction['bank_name'])): ?>
                            <div class="method-item">
                                <span class="detail-label">Bank Name:</span>
                                <span class="detail-value"><?= htmlspecialchars($transaction['bank_name']) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($transaction['bank_account_no'])): ?>
                            <div class="method-item">
                                <span class="detail-label">Account Number:</span>
                                <span class="detail-value"><?= htmlspecialchars($transaction['bank_account_no']) ?></span>
                            </div>
                        <?php endif; ?>
                    <?php elseif ($transaction['payment_method'] === 'cheque'): ?>
                        <?php if (!empty($transaction['cheque_no'])): ?>
                            <div class="method-item">
                                <span class="detail-label">Cheque Number:</span>
                                <span class="detail-value"><?= htmlspecialchars($transaction['cheque_no']) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($transaction['bank_name'])): ?>
                            <div class="method-item">
                                <span class="detail-label">Bank Name:</span>
                                <span class="detail-value"><?= htmlspecialchars($transaction['bank_name']) ?></span>
                            </div>
                        <?php endif; ?>
                    <?php elseif ($transaction['payment_method'] === 'online'): ?>
                        <?php if (!empty($transaction['online_type'])): ?>
                            <div class="method-item">
                                <span class="detail-label">Online Service:</span>
                                <span class="detail-value"><?= htmlspecialchars($transaction['online_type']) ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Description -->
        <?php if (!empty($transaction['description'])): ?>
        <div class="notes-section">
            <h4>Transaction Description</h4>
            <p><?= nl2br(htmlspecialchars($transaction['description'])) ?></p>
        </div>
        <?php endif; ?>

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line">Authorized Signature</div>
                <p style="font-size: 12px; color: #7f8c8d; margin-top: 5px;">Company Representative</p>
            </div>
            <div class="signature-box">
                <div class="signature-line">Receiver Signature</div>
                <p style="font-size: 12px; color: #7f8c8d; margin-top: 5px;"><?= htmlspecialchars($transaction['person_name'] ?? 'Recipient') ?></p>
            </div>
        </div>

    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
      function downloadPDF() {
            const element = document.getElementById('invoice-content');

            // Add PDF mode
            document.body.classList.add('pdf-mode');

            const opt = {
                filename: 'Payment-Receipt-<?= str_pad($payment['id'] ?? '0', 6, '0', STR_PAD_LEFT) ?>-<?= date('Ymd') ?>.pdf',
                image: {
                    type: 'jpeg',
                    quality: 0.98
                },
                html2canvas: {
                    scale: 2,
                    logging: false
                },
                jsPDF: {
                    unit: 'mm',
                    format: 'a4',
                    orientation: 'portrait'
                }
            };

            html2pdf()
                .set(opt)
                .from(element)
                .save()
                .then(() => {
                    // Remove PDF mode after download
                    document.body.classList.remove('pdf-mode');
                });

          }
    </script>
</body>
</html>
<?php
// Disable layout for this page
$disable_layout = true;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Invoice - #<?= str_pad($payment['id'] ?? '0', 6, '0', STR_PAD_LEFT) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $GLOBALS['base_url'] ?>/public/assets/css/invoice.css">
</head>

<body>
    <div class="action-buttons no-print">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Print Invoice
        </button>
        <button onclick="downloadPDF()" class="btn btn-success">
            <i class="fas fa-download"></i> Download PDF
        </button>
        <a href="<?= $GLOBALS['base_url'] ?>/clients-cash-in-history?booking_id=<?= $clientDetails['booking_id'] ?>" class="btn btn-secondary">
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
                    <h2>PAYMENT RECEIPT</h2>
                </div>
            </div>
        </div>
        <div class="invoice-middle-header">
            <div class="company-info">
                <div class="company-logo">
                    <h1>The Premium Homes Ltd.</h1>
                    <p>Real Estate & Property Management</p>
                    <p>Land View Commercial Center, 9th Floor 28 Gulshan North C/A, <br> Gulshan Circle-2, Dhaka</p>
                    <p>Phone: +8801958253300</p>
                    <p>Email: info@thepremiumhomes.com</p>
                </div>
                <div class="invoice-title">
                    <h2>PAYMENT RECEIPT</h2>
                    <p class="invoice-number">#REC-<?= str_pad($payment['id'] ?? '0', 6, '0', STR_PAD_LEFT) ?></p>
                    <p class="invoice-number">Date: <?= date('d M, Y', strtotime($payment['payment_date'] ?? 'now')) ?></p>
                    <?php if (($payment['payment_status'] ?? 'paid') === 'paid'): ?>
                        <span class="paid-stamp">PAID</span>
                    <?php elseif (($payment['payment_status'] ?? 'partially_paid') === 'partially_paid'): ?>
                        <span class="pending-stamp">Partially Paid</span>
                    <?php else: ?>
                        <span class="pending-stamp">PENDING</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Client & Property Details -->
        <div class="invoice-details">
            <div class="detail-section">
                <h3>Received From</h3>
                <div class="detail-row">
                    <span class="detail-label">Client Name:</span>
                    <span class="detail-value"><?= htmlspecialchars($clientDetails['client_name'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value"><?= htmlspecialchars($clientDetails['phone'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value"><?= htmlspecialchars($clientDetails['email'] ?? 'N/A') ?></span>
                </div>
            </div>

            <div class="detail-section">
                <h3>Property Information</h3>
                <div class="detail-row">
                    <span class="detail-label">Project:</span>
                    <span class="detail-value"><?= htmlspecialchars($clientDetails['project_name'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Unit:</span>
                    <span class="detail-value">UNIT <?= htmlspecialchars($clientDetails['unit_name'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Flat:</span>
                    <span class="detail-value">FLOOR <?= htmlspecialchars($clientDetails['flat_no'] ?? 'N/A') ?></span>
                </div>
            </div>
        </div>

        <!-- Payment Details -->
        <div class="payment-details">
            <h3>Payment Details</h3>
            <div class="payment-row">
                <span>Payment Type:</span>
                <span style="font-weight: 600;">
                    <?php if ($payment['payment_type'] === 'emi'): ?>
                        <span class="badge badge-emi"><?= $payment['installment_label'] ?? 'EMI Payment' ?></span>
                    <?php elseif ($payment['payment_type'] === 'booking_money'): ?>
                        <span class="badge badge-booking">BOOKING MONEY</span>
                    <?php elseif ($payment['payment_type'] === 'registration'): ?>
                        <span class="badge badge-registration">REGISTRATION MONEY</span>
                    <?php else: ?>
                        <?= strtoupper(str_replace('_', ' ', $payment['payment_type'])) ?>
                    <?php endif; ?>
                </span>
            </div>
            <div class="payment-row">
                <span>Payment Date:</span>
                <span style="font-weight: 600;"><?= date('d M, Y', strtotime($payment['payment_date'] ?? 'now')) ?></span>
            </div>
            <?php if (!empty($payment['transaction_ref'])): ?>
                <div class="payment-row">
                    <span>Transaction Reference:</span>
                    <span style="font-weight: 600;"><?= htmlspecialchars($payment['transaction_ref']) ?></span>
                </div>
            <?php endif; ?>
            <div class="payment-row total">
                <span>Amount Paid:</span>
                <span class="amount-highlight">৳<?= number_format($payment['amount'] ?? 0, 2) ?></span>
            </div>
        </div>

        <!-- Payment Method Details -->
        <?php if ($payment['payment_method'] !== 'cash'): ?>
            <div class="payment-method-details">
                <h4>Payment Method: <?= strtoupper($payment['payment_method'] ?? 'N/A') ?></h4>
                <div class="method-info">
                    <?php if ($payment['payment_method'] === 'bank'): ?>
                        <?php if (!empty($payment['bank_name'])): ?>
                            <div class="method-item">
                                <span class="detail-label">Bank Name:</span>
                                <span class="detail-value"><?= htmlspecialchars($payment['bank_name']) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($payment['bank_account_no'])): ?>
                            <div class="method-item">
                                <span class="detail-label">Account Number:</span>
                                <span class="detail-value"><?= htmlspecialchars($payment['bank_account_no']) ?></span>
                            </div>
                        <?php endif; ?>
                    <?php elseif ($payment['payment_method'] === 'cheque'): ?>
                        <?php if (!empty($payment['cheque_no'])): ?>
                            <div class="method-item">
                                <span class="detail-label">Cheque Number:</span>
                                <span class="detail-value"><?= htmlspecialchars($payment['cheque_no']) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($payment['bank_name'])): ?>
                            <div class="method-item">
                                <span class="detail-label">Bank Name:</span>
                                <span class="detail-value"><?= htmlspecialchars($payment['bank_name']) ?></span>
                            </div>
                        <?php endif; ?>
                    <?php elseif ($payment['payment_method'] === 'online'): ?>
                        <?php if (!empty($payment['online_type'])): ?>
                            <div class="method-item">
                                <span class="detail-label">Online Service:</span>
                                <span class="detail-value"><?= htmlspecialchars($payment['online_type']) ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- EMI Schedule Info (if applicable) -->
        <?php if (!empty($emiSchedule)): ?>
            <div class="payment-method-details">
                <h4>EMI Schedule Information</h4>
                <div class="method-info">
                    <div class="method-item">
                        <span class="detail-label">Installment Number:</span>
                        <span class="detail-value"><?= $emiSchedule['emi_no'] ?? 'N/A' ?></span>
                    </div>
                    <div class="method-item">
                        <span class="detail-label">EMI Amount:</span>
                        <span class="detail-value">৳<?= number_format($emiSchedule['emi_amount'] ?? 0, 2) ?></span>
                    </div>
                    <?php if (!empty($emiSchedule['due_emi_amount'])): ?>
                        <div class="method-item">
                            <span class="detail-label">Due Amount:</span>
                            <span class="detail-value">৳<?= number_format($emiSchedule['due_emi_amount'], 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="method-item">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value"><?= strtoupper($payment['payment_status'] ?? 'N/A') ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line">Authorized Signature</div>
                <p style="font-size: 9px; color: #7f8c8d; margin-top: 5px;">Company Representative</p>
            </div>
            <div class="signature-box">
                <div class="signature-line">Client Signature</div>
                <p style="font-size: 8px; color: #7f8c8d; margin-top: 5px;">Acknowledgment of Receipt</p>
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
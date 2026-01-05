<?php
// Disable layout for this page
$disable_layout = true;

/**
 * Convert number to words (for Bangladeshi currency)
 */
function numberToWords($number) {
    $number = (float)$number;
    
    if ($number == 0) {
        return 'Zero Taka Only';
    }
    
    $integerPart = floor($number);
    $decimalPart = round(($number - $integerPart) * 100);
    
    $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
    $teens = ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    
    $result = '';
    
    if ($integerPart >= 10000000) {
        $crore = floor($integerPart / 10000000);
        $result .= convertHelper($crore, $ones, $teens, $tens) . ' Crore ';
        $integerPart %= 10000000;
    }
    
    if ($integerPart >= 100000) {
        $lakh = floor($integerPart / 100000);
        $result .= convertHelper($lakh, $ones, $teens, $tens) . ' Lakh ';
        $integerPart %= 100000;
    }
    
    if ($integerPart >= 1000) {
        $thousand = floor($integerPart / 1000);
        $result .= convertHelper($thousand, $ones, $teens, $tens) . ' Thousand ';
        $integerPart %= 1000;
    }
    
    if ($integerPart >= 100) {
        $hundred = floor($integerPart / 100);
        $result .= $ones[$hundred] . ' Hundred ';
        $integerPart %= 100;
    }
    
    if ($integerPart > 0) {
        $result .= convertHelper($integerPart, $ones, $teens, $tens);
    }
    
    $result = trim($result) . ' Taka';
    
    if ($decimalPart > 0) {
        $result .= ' and ' . convertHelper($decimalPart, $ones, $teens, $tens) . ' Paisa';
    }
    
    return trim($result) . ' Only';
}

function convertHelper($number, $ones, $teens, $tens) {
    $result = '';
    
    if ($number < 10) {
        $result = $ones[$number];
    } elseif ($number < 20) {
        $result = $teens[$number - 10];
    } elseif ($number < 100) {
        $result = $tens[floor($number / 10)] . ' ' . $ones[$number % 10];
    } else {
        $result = $ones[floor($number / 100)] . ' Hundred ' . convertHelper($number % 100, $ones, $teens, $tens);
    }
    
    return trim($result);
}

// Convert amount to words
$amountInWords = numberToWords($transaction['amount'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Out Invoice - #<?= str_pad($transaction['id'] ?? '0', 6, '0', STR_PAD_LEFT) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    @media print {
        .no-print { display: none !important; }
        body { margin: 0; padding: 15px; }
        .invoice-container { box-shadow: none !important; margin: 0 !important; }
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #f5f5f5;
        padding: 15px;
    }

    .invoice-container {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        box-shadow: 0 0 15px rgba(0,0,0,0.08);
        padding: 50px;
    }

    .invoice-header {
        border-bottom: 2px solid #2c3e50;
        padding-bottom: 15px;
        margin-bottom: 25px;
    }

    .company-info {
        display: flex;
        justify-content: space-between;
        align-items: start;
    }

    .company-logo h1 {
        color: #2c3e50;
        font-size: 24px;
        margin-bottom: 4px;
    }

    .company-logo p {
        color: #7f8c8d;
        font-size: 12px;
        line-height: 1.4;
    }

    .invoice-title {
        text-align: right;
    }

    .invoice-title h2 {
        color: #2c3e50;
        font-size: 24px;
        margin-bottom: 4px;
    }

    .invoice-number {
        color: #7f8c8d;
        font-size: 12px;
    }

    .paid-stamp {
        display: inline-block;
        padding: 6px 16px;
        background: #27ae60;
        color: white;
        border-radius: 3px;
        font-weight: bold;
        margin-top: 8px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        font-size: 12px;
    }

    .pending-stamp {
        display: inline-block;
        padding: 6px 16px;
        background: #f39c12;
        color: white;
        border-radius: 3px;
        font-weight: bold;
        margin-top: 8px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        font-size: 12px;
    }

    .failed-stamp {
        display: inline-block;
        padding: 6px 16px;
        background: #e74c3c;
        color: white;
        border-radius: 3px;
        font-weight: bold;
        margin-top: 8px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        font-size: 12px;
    }

    .invoice-details {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
        margin-bottom: 25px;
    }

    .detail-section h3 {
        color: #2c3e50;
        font-size: 14px;
        margin-bottom: 12px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 7px 0;
        border-bottom: 1px solid #ecf0f1;
        font-size: 13px;
    }

    .detail-label {
        color: #7f8c8d;
        font-weight: 500;
    }

    .detail-value {
        color: #2c3e50;
        font-weight: 600;
    }

    .payment-details {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 6px;
        margin-bottom: 25px;
    }

    .payment-details h3 {
        color: #2c3e50;
        margin-bottom: 16px;
        font-size: 15px;
    }

    .payment-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        font-size: 13px;
    }

    .payment-row.total {
        border-top: 2px solid #2c3e50;
        margin-top: 12px;
        padding-top: 12px;
        font-size: 18px;
        font-weight: bold;
        color: #2c3e50;
    }

    .payment-method-details {
        background: #e8f4f8;
        padding: 16px;
        border-radius: 6px;
        margin-bottom: 25px;
        border-left: 3px solid #3498db;
    }

    .payment-method-details h4 {
        color: #2c3e50;
        margin-bottom: 12px;
        font-size: 14px;
    }

    .method-info {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .method-item {
        display: flex;
        justify-content: space-between;
        padding: 7px 0;
        font-size: 13px;
    }

    .description-box {
        background: #fff9e6;
        padding: 16px;
        border-radius: 6px;
        margin-bottom: 25px;
        border-left: 3px solid #f39c12;
    }

    .description-box h4 {
        color: #2c3e50;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .description-box p {
        color: #555;
        line-height: 1.5;
        white-space: pre-wrap;
        font-size: 13px;
    }

    .invoice-footer {
        margin-top: 40px;
        padding-top: 15px;
        border-top: 2px solid #ecf0f1;
        text-align: center;
        color: #7f8c8d;
        font-size: 11px;
    }

    .action-buttons {
        display: flex;
        gap: 12px;
        margin-bottom: 25px;
        justify-content: flex-end;
    }

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
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
        font-size: 20px;
        font-weight: bold;
        color: #000;
    }

    .badge {
        padding: 5px 10px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        display: inline-block;
    }

    .badge-primary { background: #cce5ff; color: #004085; }
    .badge-success { background: #d4edda; color: #155724; }
    .badge-warning { background: #fff3cd; color: #856404; }

    .signature-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-top: 50px;
        padding-top: 15px;
    }

    .signature-box {
        text-align: center;
    }

    .signature-line {
        border-top: 2px solid #2c3e50;
        margin-top: 40px;
        padding-top: 8px;
        color: #7f8c8d;
        font-weight: 600;
        font-size: 12px;
    }

    .amount-in-words {
        background: #f8f9fa;
        padding: 16px;
        border-radius: 6px;
        margin: 12px 0;
        font-style: italic;
        color: #555;
        border-left: 3px solid #9b59b6;
        font-size: 13px;
    }

    .category-badge {
        display: inline-block;
        padding: 4px 10px;
        background: #e74c3c;
        color: white;
        border-radius: 3px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        margin-left: 8px;
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
        <a href="<?= $GLOBALS['base_url'] ?>/management-cash-out" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    <div class="invoice-container" id="invoice-content">
        <!-- Header -->
        <div class="invoice-header">
            <div class="company-info">
                <div class="company-logo">
                    <h1>The Premium Homes Ltd.</h1>
                    <p>Real Estate & Property Management</p>
                    <p>Land View Commercial Center, 9th Floor 28 Gulshan North C/A, <br> Gulshan Circle-2, Dhaka</p>
                    <p>Phone: +8801958253300</p>
                    <p>Email: info@thepremiumhomes.com</p>
                </div>
                <div class="invoice-title">
                    <h2>CASH OUT RECEIPT</h2>
                    <p class="invoice-number">#CO-<?= str_pad($transaction['id'] ?? '0', 6, '0', STR_PAD_LEFT) ?></p>
                    <p class="invoice-number">Date: <?= date('d M, Y', strtotime($transaction['transaction_date'] ?? 'now')) ?></p>
                    <?php 
                    $status = $transaction['payment_status'] ?? 'paid';
                    if ($status === 'paid'): ?>
                        <span class="paid-stamp">PAID</span>
                    <?php elseif ($status === 'pending'): ?>
                        <span class="pending-stamp">PENDING</span>
                    <?php else: ?>
                        <span class="failed-stamp"><?= strtoupper($status) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Category & Transaction Details -->
        <div class="invoice-details">
            <div class="detail-section">
                <h3>Category Information</h3>
                <div class="detail-row">
                    <span class="detail-label">Category:</span>
                    <span class="detail-value">
                        <?= htmlspecialchars($transaction['category_name'] ?? 'N/A') ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Paid To:</span>
                    <span class="detail-value"><?= htmlspecialchars($transaction['paid_to'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Transaction ID:</span>
                    <span class="detail-value">CO-<?= str_pad($transaction['id'] ?? '0', 6, '0', STR_PAD_LEFT) ?></span>
                </div>
            </div>

            <div class="detail-section">
                <h3>Transaction Information</h3>
                <div class="detail-row">
                    <span class="detail-label">Transaction Date:</span>
                    <span class="detail-value"><?= date('d M, Y', strtotime($transaction['transaction_date'] ?? 'now')) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Created By:</span>
                    <span class="detail-value"><?= htmlspecialchars($transaction['created_by_name'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Created At:</span>
                    <span class="detail-value"><?= date('d M, Y h:i A', strtotime($transaction['created_at'] ?? 'now')) ?></span>
                </div>
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
            <div class="payment-row">
                <span>Payment Status:</span>
                <span style="font-weight: 600;">
                    <?php 
                    $status = $transaction['payment_status'] ?? 'paid';
                    if ($status === 'paid'): ?>
                        <span style="color: #27ae60;">PAID</span>
                    <?php elseif ($status === 'pending'): ?>
                        <span style="color: #f39c12;">PENDING</span>
                    <?php else: ?>
                        <span style="color: #e74c3c;"><?= strtoupper($status) ?></span>
                    <?php endif; ?>
                </span>
            </div>
            <?php if (!empty($transaction['transaction_ref'])): ?>
            <div class="payment-row">
                <span>Transaction Reference:</span>
                <span style="font-weight: 600;"><?= htmlspecialchars($transaction['transaction_ref']) ?></span>
            </div>
            <?php endif; ?>
            <div class="payment-row total">
                <span>Amount:</span>
                <span class="amount-highlight">৳<?= number_format($transaction['amount'] ?? 0, 2) ?></span>
            </div>
        </div>

        <!-- Amount in Words -->
        <div class="amount-in-words">
            <strong>Amount in Words:</strong> <?= $amountInWords ?>
        </div>

        <!-- Payment Method Details - Only show if not cash -->
        <?php 
        $paymentMethod = $transaction['payment_method'] ?? 'cash';
        $showPaymentDetails = false;
        
        // Check if we need to show payment details based on payment method
        if ($paymentMethod !== 'cash') {
            $showPaymentDetails = true;
        }
        
        if ($showPaymentDetails && ($paymentMethod === 'bank' || $paymentMethod === 'cheque' || $paymentMethod === 'online')):
        ?>
        <div class="payment-method-details">
            <h4>Payment Method: <?= strtoupper($paymentMethod) ?></h4>
            <div class="method-info">
                <?php if ($paymentMethod === 'bank'): ?>
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
                <?php elseif ($paymentMethod === 'cheque'): ?>
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
                    <?php if (!empty($transaction['bank_account_no'])): ?>
                    <div class="method-item">
                        <span class="detail-label">Account Number:</span>
                        <span class="detail-value"><?= htmlspecialchars($transaction['bank_account_no']) ?></span>
                    </div>
                    <?php endif; ?>
                <?php elseif ($paymentMethod === 'online'): ?>
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
        <div class="description-box">
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
                <p style="font-size: 12px; color: #7f8c8d; margin-top: 5px;"><?= htmlspecialchars($transaction['paid_to'] ?? 'Recipient') ?></p>
            </div>
        </div>

        <!-- Footer -->
        <div class="invoice-footer">
            <p><strong>Thank you for your transaction!</strong></p>

        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadPDF() {
            const element = document.getElementById('invoice-content');
            const opt = {
                filename: 'CashOut-Receipt-<?= str_pad($transaction['id'] ?? '0', 6, '0', STR_PAD_LEFT) ?>-<?= date('Ymd') ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, logging: false },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>
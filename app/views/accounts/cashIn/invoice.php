<?php
// Disable layout for standalone printable invoice
$disable_layout = true;

// Retrieve Site Settings for Logo, Phone, Email & Address
$whiteLogo = function_exists('getSetting') ? getSetting('white_logo') : null;
$logoUrl = ($whiteLogo && function_exists('getImage')) ? getImage('settings', $whiteLogo) : '';
if (empty($logoUrl) || strpos($logoUrl, 'avatar.png') !== false) {
    $logoUrl = $GLOBALS['base_url'] . '/public/uploads/settings/' . ($whiteLogo ?: '6948f14a9fb8c_1766388042.png');
}

$companyTitle = function_exists('getSetting') ? getSetting('website_title', 'The Premium Homes Ltd.') : 'The Premium Homes Ltd.';
$companyPhone = function_exists('getSetting') ? getSetting('phone', '+8801958253300') : '+8801958253300';
$companyEmail = function_exists('getSetting') ? getSetting('email', 'info@thepremiumhomes.com') : 'info@thepremiumhomes.com';
$companyAddress = "Land View Commercial Center, 9th Floor, 28 Gulshan North C/A, Gulshan Circle-2, Dhaka";

// Payment & Verification Status
$isVerified = isset($payment['verification_status']) && strtolower(trim($payment['verification_status'])) === 'verified';
$createdByName = !empty($payment['created_by_name']) ? $payment['created_by_name'] : 'System';
$verifiedByName = !empty($payment['verified_by_name']) ? $payment['verified_by_name'] : ($isVerified ? ($createdByName ?: 'Authorized Officer') : 'Pending');

// Verification timestamp
$verifiedDateFormatted = 'Awaiting Verification';
if ($isVerified) {
    if (!empty($payment['verified_at'])) {
        $verifiedDateFormatted = date('d M, Y • h:i A', strtotime($payment['verified_at']));
    } elseif (!empty($payment['updated_at']) && $payment['updated_at'] !== '0000-00-00 00:00:00') {
        $verifiedDateFormatted = date('d M, Y • h:i A', strtotime($payment['updated_at']));
    } elseif (!empty($payment['created_at'])) {
        $verifiedDateFormatted = date('d M, Y • h:i A', strtotime($payment['created_at']));
    } else {
        $verifiedDateFormatted = date('d M, Y • h:i A');
    }
}

// Payment Type Label
$paymentTypeRaw = strtolower($payment['payment_type'] ?? '');
if ($paymentTypeRaw === 'emi') {
    $paymentTypeLabel = !empty($payment['installment_label']) ? strtoupper($payment['installment_label']) : 'EMI INSTALLMENT';
} elseif ($paymentTypeRaw === 'booking_money') {
    $paymentTypeLabel = 'BOOKING MONEY';
} elseif ($paymentTypeRaw === 'registration') {
    $paymentTypeLabel = 'REGISTRATION MONEY';
} elseif ($paymentTypeRaw === 'development_charge') {
    $paymentTypeLabel = 'DEVELOPMENT CHARGE';
} else {
    $paymentTypeLabel = strtoupper(str_replace('_', ' ', $payment['payment_type'] ?? 'PAYMENT'));
}

// Total amount calculation
$totalAmountPaid = !empty($isBulk) ? array_sum(array_map(fn($p) => (float)($p['amount'] ?? 0), $payments)) : (float)($payment['amount'] ?? 0);
$amountInWords = function_exists('amountInWords') ? amountInWords($totalAmountPaid) : (class_exists('FormatHelper') ? FormatHelper::amountInWords($totalAmountPaid) : '');

// Bank & Payment Method Display
$paymentMethod = strtoupper($payment['payment_method'] ?? 'CASH');
$displayBank = !empty($payment['company_bank_name']) 
    ? $payment['company_bank_name'] 
    : (!empty($payment['bank_name']) 
        ? $payment['bank_name'] 
        : ($paymentMethod === 'CASH' ? 'Cash in Hand / Office' : 'N/A'));

// Client and Property info
$clientName = !empty($clientDetails['client_name']) ? $clientDetails['client_name'] : (!empty($payment['client_name']) ? $payment['client_name'] : 'N/A');
$clientPhone = !empty($clientDetails['phone']) ? $clientDetails['phone'] : (!empty($clientDetails['phone_number']) ? $clientDetails['phone_number'] : 'N/A');
if ($clientPhone !== 'N/A' && strlen($clientPhone) === 10 && substr($clientPhone, 0, 1) === '1') {
    $clientPhone = '0' . $clientPhone;
}
$clientEmail = !empty($clientDetails['email']) ? $clientDetails['email'] : 'N/A';
$projectName = !empty($clientDetails['project_name']) ? $clientDetails['project_name'] : (!empty($payment['project_name']) ? $payment['project_name'] : 'N/A');
$unitName = !empty($clientDetails['unit_name']) ? $clientDetails['unit_name'] : (!empty($payment['unit_name']) ? $payment['unit_name'] : 'N/A');
$flatNo = !empty($clientDetails['flat_no']) ? $clientDetails['flat_no'] : (!empty($payment['flat_no']) ? $payment['flat_no'] : 'N/A');

$receiptNo = '#REC-' . str_pad($payment['id'] ?? '0', 6, '0', STR_PAD_LEFT);
$receiptDate = date('d M, Y', strtotime($payment['payment_date'] ?? 'now'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - <?= $receiptNo ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* CSS RESET & BOX SIZING */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
            padding: 30px 15px;
            -webkit-font-smoothing: antialiased;
        }

        /* ACTION TOOLBAR (NO-PRINT) */
        .action-toolbar {
            max-width: 820px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .action-btn-group {
            display: flex;
            gap: 10px;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 18px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s ease;
        }

        .btn-print {
            background-color: #0b6638;
            color: #ffffff;
        }
        .btn-print:hover {
            background-color: #084c2a;
            color: #ffffff;
        }

        .btn-download {
            background-color: #ffffff;
            color: #0b6638;
            border-color: #a3e0be;
        }
        .btn-download:hover {
            background-color: #f0fbf5;
        }

        .btn-back {
            background-color: #ffffff;
            color: #64748b;
            border-color: #cbd5e1;
        }
        .btn-back:hover {
            background-color: #f8fafc;
            color: #334155;
        }

        /* MAIN RECEIPT SHEET */
        .receipt-sheet {
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            padding: 44px 48px;
            border-radius: 12px;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.05);
            position: relative;
        }

        /* HEADER */
        .receipt-header {
            display: flex;
            justify-content: space-between;
            align-items: stretch;
            gap: 0;
        }

        .header-brand {
            flex: 1;
            padding-right: 24px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .brand-logo-wrap {
            margin-bottom: 6px;
        }

        .brand-logo-img {
            max-height: 52px;
            max-width: 250px;
            object-fit: contain;
            display: block;
        }

        .brand-name-text {
            font-size: 22px;
            font-weight: 800;
            color: #111827;
            letter-spacing: -0.3px;
            line-height: 1.2;
        }

        .brand-tagline {
            font-size: 13px;
            font-weight: 700;
            color: #0b6638;
            margin-top: 2px;
            margin-bottom: 8px;
            letter-spacing: 0.2px;
        }

        .brand-address {
            font-size: 11.5px;
            color: #4b5563;
            line-height: 1.5;
        }

        /* VERTICAL DIVIDER BETWEEN BRAND & RECEIPT INFO */
        .header-v-divider {
            width: 1px;
            background-color: #e5e7eb;
            margin: 2px 24px 2px 0;
            flex-shrink: 0;
        }

        /* RIGHT META COLUMN - EXACT BOUNDED WIDTH FOR PERFECT ALIGNMENT */
        .header-meta {
            width: 248px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: flex-start;
        }

        .receipt-main-title {
            font-size: 21.5px;
            font-weight: 900;
            letter-spacing: 1px;
            color: #111827;
            line-height: 1.15;
            margin-bottom: 4px;
            width: 100%;
            /* text-align: right; */
            white-space: nowrap;
        }

        .receipt-num-text {
            font-size: 13.5px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 2px;
            width: 100%;
            /* text-align: right; */
        }

        .receipt-date-text {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 12px;
            width: 100%;
            /* text-align: right; */
        }

        /* STATUS BADGE BOX */
        .status-badge-box {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 11px;
            border-radius: 8px;
            text-align: left;
            width: 100%;
            box-sizing: border-box;
        }

        .status-verified {
            background-color: #f0fbf5;
            border: 1.5px solid #a3e0be;
        }

        .status-pending {
            background-color: #fffdf5;
            border: 1.5px solid #fde68a;
        }

        .status-icon-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .verified-icon-wrap {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #16a34a;
            color: #ffffff;
            font-size: 14px;
        }

        .pending-icon-wrap {
            width: 26px;
            height: 26px;
            color: #d97706;
            font-size: 22px;
        }

        .status-meta-info {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 0;
        }

        .status-sub-title {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .verified-sub { color: #059669; }
        .pending-sub { color: #d97706; }

        .status-main-label {
            font-size: 15px;
            font-weight: 900;
            line-height: 1.2;
            margin-top: 2px;
        }
        .verified-main { color: #065f46; font-size: 15px; }
        .pending-main { color: #92400e; font-size: 12.5px; font-weight: 900; }

        .status-desc-text {
            font-size: 9.5px;
            line-height: 1.3;
            margin-top: 2px;
            font-weight: 500;
        }
        .verified-desc { color: #4b5563; }
        .pending-desc { color: #78350f; }

        /* HORIZONTAL GREEN DIVIDER */
        .green-divider {
            border: none;
            height: 2.5px;
            background: linear-gradient(90deg, #0b6638 0%, #16a34a 100%);
            margin: 20px 0 24px 0;
            border-radius: 2px;
        }

        /* SECTION TITLE */
        .section-header-title {
            font-size: 12.5px;
            font-weight: 800;
            color: #0b6638;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin-bottom: 14px;
            display: inline-flex;
            align-items: center;
            border-left: 3.5px solid #0b6638;
            padding-left: 9px;
            line-height: 1.2;
        }

        /* TWO-COLUMN INFO GRID */
        .two-column-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 36px;
            margin-bottom: 24px;
        }

        .col-info-card {
            display: flex;
            flex-direction: column;
        }

        .info-entry-row {
            display: grid;
            grid-template-columns: 105px 1fr;
            margin-bottom: 10px;
            align-items: baseline;
            font-size: 13.5px;
        }

        .info-label-cell {
            color: #64748b;
            font-weight: 600;
            font-size: 13px;
        }

        .info-value-cell {
            color: #0f172a;
            font-weight: 800;
            font-size: 14px;
            letter-spacing: -0.1px;
        }

        /* PAYMENT SUMMARY */
        .payment-summary-wrap {
            margin-bottom: 18px;
        }

        .summary-entry-row {
            display: grid;
            grid-template-columns: 125px 1fr;
            margin-bottom: 10px;
            align-items: baseline;
            font-size: 13.5px;
        }

        .highlight-type-badge {
            display: inline-block;
            background-color: #ecfdf5;
            color: #065f46;
            font-weight: 800;
            font-size: 13.5px;
            padding: 3px 10px;
            border-radius: 5px;
            border: 1px solid #a7f3d0;
            letter-spacing: 0.3px;
        }

        /* BULK TABLE */
        .bulk-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 16px;
            font-size: 13px;
        }
        .bulk-table th {
            text-align: left;
            padding: 9px 12px;
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            color: #475569;
            font-weight: 800;
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .bulk-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #f1f5f9;
            color: #0f172a;
            font-weight: 600;
        }

        /* TOTAL AMOUNT PAID CARD */
        .total-amount-banner {
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
            border: 1.5px solid #a7f3d0;
            border-radius: 10px;
            padding: 16px 26px;
            margin: 20px 0 24px 0;
            box-shadow: 0 2px 10px rgba(16, 185, 129, 0.08);
        }

        .total-banner-main {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .total-banner-label {
            font-size: 13.5px;
            font-weight: 900;
            color: #065f46;
            letter-spacing: 1.2px;
            text-transform: uppercase;
        }

        .total-banner-divider {
            width: 2px;
            height: 44px;
            background-color: #6ee7b7;
            margin: 0 26px;
        }

        .total-banner-amount {
            font-size: 34px;
            font-weight: 900;
            color: #047857;
            letter-spacing: -0.5px;
        }

        .total-banner-words {
            margin-top: 10px;
            padding-top: 9px;
            border-top: 1px dashed #a7f3d0;
            display: flex;
            align-items: baseline;
            gap: 8px;
            flex-wrap: wrap;
        }

        .words-label {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: #047857;
            background: rgba(16, 185, 129, 0.12);
            padding: 2px 8px;
            border-radius: 4px;
            border: 1px solid #a7f3d0;
            display: inline-block;
        }

        .words-value {
            font-size: 13px;
            font-weight: 700;
            color: #065f46;
            letter-spacing: 0.1px;
        }

        /* PROCESSING INFORMATION */
        .processing-info-wrap {
            margin-bottom: 24px;
        }

        .processing-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr 1fr;
            gap: 20px;
            background-color: #f8fafc;
            padding: 14px 20px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .proc-box {
            display: flex;
            flex-direction: column;
        }

        .proc-label {
            font-size: 11.5px;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .proc-value {
            font-size: 14.5px;
            font-weight: 800;
            color: #0f172a;
            word-break: break-word;
        }

        /* BOTTOM VERIFICATION CARD */
        .verification-card {
            border-radius: 12px;
            padding: 22px 26px;
            margin-bottom: 24px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        }

        .card-verified {
            background-color: #f4fbf7;
            border: 1.5px solid #a7f3d0;
        }

        .card-pending {
            background-color: #fffdf5;
            border: 1.5px solid #fde68a;
        }

        .card-top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .card-left-brand {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .card-doc-icon {
            font-size: 38px;
            flex-shrink: 0;
            line-height: 1;
        }
        .icon-verified { color: #16a34a; }
        .icon-pending { color: #d97706; }

        .card-title-group {
            display: flex;
            flex-direction: column;
        }

        .card-main-title {
            font-size: 15.5px;
            font-weight: 900;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }
        .title-verified { color: #065f46; }
        .title-pending { color: #92400e; }

        .card-sub-desc {
            font-size: 12px;
            color: #4b5563;
            font-weight: 500;
            margin-top: 3px;
        }

        /* CIRCULAR STAMP */
        .circular-stamp {
            width: 82px;
            height: 82px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3px;
            flex-shrink: 0;
        }

        .stamp-verified {
            border: 2.5px solid #0b6638;
            color: #0b6638;
        }

        .stamp-pending {
            border: 2.5px dashed #b45309;
            color: #b45309;
        }

        .stamp-inner-ring {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 1px solid currentColor;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 3px;
        }

        .stamp-org-code {
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 2px;
            line-height: 1;
        }

        .stamp-line {
            width: 40px;
            height: 1.5px;
            background-color: currentColor;
            margin: 3px 0;
        }

        .stamp-label-text {
            font-size: 8.5px;
            font-weight: 800;
            letter-spacing: 0.5px;
            line-height: 1.15;
            text-transform: uppercase;
        }

        .card-divider-line {
            height: 1px;
            margin: 16px 0 12px 0;
        }
        .divider-verified { background-color: #cbeed9; }
        .divider-pending { background-color: #fef08a; }

        .card-meta-row {
            display: grid;
            grid-template-columns: 1.2fr 1fr 1.3fr;
            gap: 14px;
            font-size: 12.5px;
            color: #334151;
            align-items: center;
        }

        .card-meta-item {
            display: flex;
            align-items: baseline;
            gap: 6px;
        }

        .meta-label {
            color: #64748b;
            font-weight: 600;
            font-size: 12px;
        }

        .meta-value {
            font-weight: 800;
            color: #0f172a;
            font-size: 13px;
        }

        .val-pending {
            color: #b45309;
        }

        .card-disclaimer {
            font-size: 10.5px;
            color: #64748b;
            font-style: italic;
            margin-top: 12px;
            font-weight: 500;
        }

        /* FOOTER */
        .receipt-footer {
            text-align: center;
            padding-top: 6px;
        }

        .footer-slogan {
            font-size: 12px;
            font-weight: 700;
            color: #374151;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        /* PRINT STYLES */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .action-toolbar {
                display: none !important;
            }
            .receipt-sheet {
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 24px 30px !important;
                max-width: 100% !important;
            }
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>

    <!-- ACTION TOOLBAR (VISIBLE ON SCREEN ONLY) -->
    <div class="action-toolbar">
        <a href="<?= $GLOBALS['base_url'] ?>/clients-cash-in-history?booking_id=<?= $clientDetails['booking_id'] ?? '' ?>" class="btn-action btn-back">
            <i class="fa-solid fa-arrow-left"></i> Back to History
        </a>
        <div class="action-btn-group">
            <button type="button" onclick="window.print()" class="btn-action btn-print">
                <i class="fa-solid fa-print"></i> Print Receipt
            </button>
            <button type="button" onclick="downloadPDF()" class="btn-action btn-download">
                <i class="fa-solid fa-download"></i> Download PDF
            </button>
        </div>
    </div>

    <!-- MAIN RECEIPT DOCUMENT -->
    <div class="receipt-sheet" id="receipt-content">

        <!-- HEADER SECTION -->
        <div class="receipt-header">
            <div class="header-brand">
                <div class="brand-logo-wrap">
                    <?php if (!empty($logoUrl)): ?>
                        <img src="<?= htmlspecialchars($logoUrl) ?>" alt="<?= htmlspecialchars($companyTitle) ?>" class="brand-logo-img">
                    <?php else: ?>
                        <div class="brand-name-text"><?= htmlspecialchars($companyTitle) ?></div>
                    <?php endif; ?>
                </div>
                <div class="brand-tagline">Real Estate &amp; Property Management</div>
                <div class="brand-address">
                    <?= $companyAddress ?><br>
                    <?= htmlspecialchars($companyPhone) ?> &bull;
                     <!-- < htmlspecialchars($companyEmail) ?> -->
                      accounts@dpremiumhomes.com
                </div>
            </div>

            <!-- VERTICAL DIVIDER -->
            <div class="header-v-divider"></div>

            <div class="header-meta">
                <div class="receipt-main-title">PAYMENT RECEIPT</div>
                <div class="receipt-num-text"><?= $receiptNo ?></div>
                <div class="receipt-date-text"><?= $receiptDate ?></div>

                <!-- PAYMENT STATUS BADGE -->
                <?php if ($isVerified): ?>
                    <div class="status-badge-box status-verified">
                        <div class="status-icon-wrap verified-icon-wrap">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <div class="status-meta-info">
                            <span class="status-sub-title verified-sub">PAYMENT STATUS</span>
                            <span class="status-main-label verified-main">Bank Verified</span>
                            <span class="status-desc-text verified-desc">Payment confirmation completed successfully.</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="status-badge-box status-pending">
                        <div class="status-icon-wrap pending-icon-wrap">
                            <i class="fa-solid fa-hourglass-half"></i>
                        </div>
                        <div class="status-meta-info">
                            <span class="status-sub-title pending-sub">PAYMENT STATUS</span>
                            <span class="status-main-label pending-main">Subject to Bank Verification</span>
                            <span class="status-desc-text pending-desc">Confirmation will be issued after verification.</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- GREEN DIVIDER -->
        <hr class="green-divider">

        <!-- TWO-COLUMN GRID: RECEIVED FROM & PROPERTY INFORMATION -->
        <div class="two-column-grid">
            <!-- RECEIVED FROM -->
            <div class="col-info-card">
                <div class="section-header-title">RECEIVED FROM</div>
                <div class="info-entry-row">
                    <span class="info-label-cell">Client Name:</span>
                    <span class="info-value-cell"><?= htmlspecialchars($clientName) ?></span>
                </div>
                <div class="info-entry-row">
                    <span class="info-label-cell">Phone:</span>
                    <span class="info-value-cell"><?= htmlspecialchars($clientPhone) ?></span>
                </div>
                <div class="info-entry-row">
                    <span class="info-label-cell">Email:</span>
                    <span class="info-value-cell"><?= htmlspecialchars($clientEmail) ?></span>
                </div>
            </div>

            <!-- PROPERTY INFORMATION -->
            <div class="col-info-card">
                <div class="section-header-title">PROPERTY INFORMATION</div>
                <div class="info-entry-row">
                    <span class="info-label-cell">Project:</span>
                    <span class="info-value-cell"><?= htmlspecialchars($projectName) ?></span>
                </div>
                <div class="info-entry-row">
                    <span class="info-label-cell">Unit:</span>
                    <span class="info-value-cell"><?= (!empty($unitName) && $unitName !== 'N/A' && stripos($unitName, 'UNIT') === false) ? 'UNIT ' : '' ?><?= htmlspecialchars($unitName) ?></span>
                </div>
                <div class="info-entry-row">
                    <span class="info-label-cell">Flat:</span>
                    <span class="info-value-cell"><?= (!empty($flatNo) && $flatNo !== 'N/A' && stripos($flatNo, 'FLOOR') === false) ? 'FLOOR ' : '' ?><?= htmlspecialchars($flatNo) ?></span>
                </div>
            </div>
        </div>

        <!-- PAYMENT SUMMARY -->
        <div class="payment-summary-wrap">
            <div class="section-header-title">PAYMENT SUMMARY</div>
            
            <?php if (!empty($isBulk)): ?>
                <!-- Bulk payments table -->
                <table class="bulk-table">
                    <thead>
                        <tr>
                            <th>Payment Date</th>
                            <th>Payment Type</th>
                            <th>Reference</th>
                            <th style="text-align: right;">Amount (BDT)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><?= date('d M, Y', strtotime($p['payment_date'] ?? 'now')) ?></td>
                            <td class="fw-semibold">
                                <?php 
                                if (($p['payment_type'] ?? '') === 'emi') echo htmlspecialchars($p['installment_label'] ?? 'EMI Payment');
                                elseif (($p['payment_type'] ?? '') === 'booking_money') echo 'BOOKING MONEY';
                                elseif (($p['payment_type'] ?? '') === 'registration') echo 'REGISTRATION MONEY';
                                elseif (($p['payment_type'] ?? '') === 'development_charge') echo 'DEVELOPMENT CHARGE';
                                else echo strtoupper(str_replace('_', ' ', $p['payment_type'] ?? 'PAYMENT'));
                                ?>
                            </td>
                            <td><?= htmlspecialchars($p['transaction_ref'] ?? '-') ?></td>
                            <td style="text-align: right; font-weight: 700;">TK. <?= number_format($p['amount'] ?? 0, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="summary-entry-row">
                    <span class="info-label-cell">Payment Type:</span>
                    <span class="info-value-cell"><span class="highlight-type-badge"><?= $paymentTypeLabel ?></span></span>
                </div>
                <div class="summary-entry-row">
                    <span class="info-label-cell">Payment Date:</span>
                    <span class="info-value-cell"><?= $receiptDate ?></span>
                </div>
                <?php if (!empty($payment['transaction_ref'])): ?>
                <div class="summary-entry-row">
                    <span class="info-label-cell">Transaction Ref:</span>
                    <span class="info-value-cell"><?= htmlspecialchars($payment['transaction_ref']) ?></span>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- TOTAL AMOUNT PAID BANNER -->
        <div class="total-amount-banner">
            <div class="total-banner-main">
                <div class="total-banner-label">TOTAL AMOUNT PAID</div>
                <div class="total-banner-divider"></div>
                <div class="total-banner-amount">TK. <?= number_format($totalAmountPaid, 2) ?></div>
            </div>
            <?php if (!empty($amountInWords)): ?>
            <div class="total-banner-words">
                <span class="words-label">In Words:</span>
                <span class="words-value"><?= htmlspecialchars($amountInWords) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- PROCESSING INFORMATION -->
        <div class="processing-info-wrap">
            <div class="section-header-title">PROCESSING INFORMATION</div>
            <div class="processing-grid">
                <div class="proc-box">
                    <span class="proc-label">Created By:</span>
                    <span class="proc-value"><?= htmlspecialchars($createdByName) ?></span>
                </div>
            </div>
        </div>

        <!-- BOTTOM VERIFICATION CARD -->
        <div class="verification-card <?= $isVerified ? 'card-verified' : 'card-pending' ?>">
            <div class="card-top-row">
                <div class="card-left-brand">
                    <div class="card-doc-icon <?= $isVerified ? 'icon-verified' : 'icon-pending' ?>">
                        <i class="fa-solid <?= $isVerified ? 'fa-file-circle-check' : 'fa-file-lines' ?>"></i>
                    </div>
                    <div class="card-title-group">
                        <div class="card-main-title <?= $isVerified ? 'title-verified' : 'title-pending' ?>">
                            <?= $isVerified ? 'BANK-VERIFIED PAYMENT RECEIPT' : 'SUBJECT TO BANK VERIFICATION' ?>
                        </div>
                        <div class="card-sub-desc">
                            <?= $isVerified 
                                ? 'This payment has been reviewed and confirmed through the system.' 
                                : 'This payment is subject to bank verification and confirmation.' ?>
                        </div>
                    </div>
                </div>

                <!-- CIRCULAR STAMP BADGE -->
                <div class="circular-stamp <?= $isVerified ? 'stamp-verified' : 'stamp-pending' ?>">
                    <div class="stamp-inner-ring">
                        <span class="stamp-org-code">TPHL</span>
                        <div class="stamp-line"></div>
                        <span class="stamp-label-text"><?= $isVerified ? "BANK<br>VERIFIED" : "SUBJECT TO<br>VERIFY" ?></span>
                    </div>
                </div>
            </div>

            <div class="card-divider-line <?= $isVerified ? 'divider-verified' : 'divider-pending' ?>"></div>

            <div class="card-meta-row">
                <div class="card-meta-item">
                    <span class="meta-label">Receipt ID:</span>
                    <span class="meta-value"><?= $receiptNo ?></span>
                </div>
                <div class="card-meta-item">
                    <span class="meta-label">Verified By:</span>
                    <span class="meta-value <?= $isVerified ? '' : 'val-pending' ?>"><?= htmlspecialchars($verifiedByName) ?></span>
                </div>
                <div class="card-meta-item">
                    <span class="meta-label">Verified On:</span>
                    <span class="meta-value <?= $isVerified ? '' : 'val-pending' ?>"><?= $verifiedDateFormatted ?></span>
                </div>
            </div>

            <div class="card-disclaimer">
                This system-generated receipt is valid without a physical signature.
            </div>
        </div>

        <!-- FOOTER SECTION -->
        <hr class="green-divider" style="margin-top: 0; margin-bottom: 12px;">
        <div class="receipt-footer">
            <span class="footer-slogan">Your Dream Homes Awaits</span>
        </div>

    </div>

    <!-- PDF GENERATION SCRIPT -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadPDF() {
            const element = document.getElementById('receipt-content');
            const opt = {
                margin:       [8, 8, 8, 8],
                filename:     'Payment-Receipt-<?= str_pad($payment['id'] ?? '0', 6, '0', STR_PAD_LEFT) ?>-<?= date('Ymd') ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true, logging: false },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>
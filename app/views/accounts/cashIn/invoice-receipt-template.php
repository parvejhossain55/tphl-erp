<?php
$paymentDateFormatted = !empty($paymentDateFormatted) ? $paymentDateFormatted : (!empty($receiptDate) ? $receiptDate : date('d M, Y', strtotime($payment['payment_date'] ?? 'now')));
$refNo = !empty($refNo) ? $refNo : (!empty($refNumber) ? $refNumber : (!empty($payment['transaction_ref']) ? $payment['transaction_ref'] : 'N/A'));
$floorNo = !empty($floorNo) ? $floorNo : (!empty($flatNo) ? $flatNo : (!empty($payment['flat_no']) ? $payment['flat_no'] : 'N/A'));

// Theme & Branding Tokens
$themePrimary   = !empty($themePrimary) ? $themePrimary : "#0b6638";
$themeDark      = !empty($themeDark) ? $themeDark : "#0b4022";
$themeDeep      = !empty($themeDeep) ? $themeDeep : "#0f3b25";
$themeLight     = !empty($themeLight) ? $themeLight : "#10b981";
$themeBgLight   = !empty($themeBgLight) ? $themeBgLight : "#f0fdf4";
$themeBadgeBg   = !empty($themeBadgeBg) ? $themeBadgeBg : "#ecfdf5";
$themeBorder    = !empty($themeBorder) ? $themeBorder : "#bbf7d0";
$brandDomain    = !empty($brandDomain) ? $brandDomain : "Real Estate & Property Management";
$companyAddress = !empty($companyAddress) ? $companyAddress : "Land View Commercial Center, 9th Floor, 28 Gulshan North C/A, Gulshan Circle-2, Dhaka";
$companyPhone   = !empty($companyPhone) ? $companyPhone : "+8801958253303";
$companyEmail   = !empty($companyEmail) ? $companyEmail : "accounts@dpremiumhomes.com";

if (empty($logoSrc)) {
    $whiteLogo = function_exists('getSetting') ? (getSetting('white_logo') ?: (getSetting('dark_logo') ?: getSetting('logo'))) : null;
    $logoFileName = !empty($whiteLogo) ? $whiteLogo : '6948f14a9fb8c_1766388042.png';
    $logoFilePath = dirname(dirname(dirname(dirname(__DIR__)))) . '/public/uploads/settings/' . $logoFileName;
    if (file_exists($logoFilePath)) {
        $ext = strtolower(pathinfo($logoFilePath, PATHINFO_EXTENSION));
        $mime = ($ext === 'svg') ? 'image/svg+xml' : 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext);
        $logoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoFilePath));
    } elseif (!empty($logoUrl)) {
        $logoSrc = $logoUrl;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - <?= $receiptNo ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* CSS RESET & BOX SIZING */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
            text-rendering: optimizeLegibility;
            font-variant-numeric: tabular-nums;
            -webkit-font-smoothing: subpixel-antialiased;
            -moz-osx-font-smoothing: auto;
            padding: 0;
            margin: 0;
        }

        /* TOP FLOATING ACTION BAR (Screen Only) */
        .top-action-bar {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .bar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        :root {
            --theme-primary: <?= $themePrimary ?>;
            --theme-dark: <?= $themeDark ?>;
            --theme-deep: <?= $themeDeep ?>;
            --theme-light: <?= $themeLight ?>;
            --theme-bg-light: <?= $themeBgLight ?>;
            --theme-badge-bg: <?= $themeBadgeBg ?>;
            --theme-border: <?= $themeBorder ?>;
        }

        .bar-title {
            font-size: 14.5px;
            font-weight: 800;
            color: #0f172a;
        }

        .bar-badge {
            background: <?= $isVerified ? $themeBadgeBg : '#fffbeb' ?>;
            color: <?= $isVerified ? $themePrimary : '#d97706' ?>;
            border: 1px solid <?= $isVerified ? $themeBorder : '#fef3c7' ?>;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 700;
        }

        .bar-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-act {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 6px;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
            border: 1px solid transparent;
        }

        .btn-primary {
            background: var(--theme-primary);
            color: #ffffff;
            border-color: var(--theme-primary);
        }
        .btn-primary:hover {
            background: var(--theme-dark);
        }

        .btn-outline {
            background: #ffffff;
            color: #334155;
            border-color: #cbd5e1;
        }
        .btn-outline:hover {
            background: #f8fafc;
            border-color: #94a3b8;
        }

        /* MAIN A4 WRAPPER */
        .receipt-container-wrap {
            max-width: 820px;
            margin: 20px auto 40px auto;
            padding: 0 10px;
        }

        #receipt-content {
            background: #ffffff;
            border-radius: 6px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            border: 1px solid #cbd5e1;
            overflow: hidden;
            position: relative;
        }

        /* EACH COPY (TOP: CUSTOMER, BOTTOM: AUTHORITY) */
        .receipt-copy {
            position: relative;
            padding: 16px 24px 14px 24px;
            background: #ffffff;
            overflow: hidden;
            page-break-inside: avoid;
        }

        /* GEOMETRIC CORNER ACCENTS (AS IN SCREENSHOT) */
        .corner-accent-tl {
            position: absolute;
            top: 0;
            left: 0;
            width: 64px;
            height: 64px;
            pointer-events: none;
            z-index: 1;
        }

        .corner-accent-br {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 78px;
            height: 78px;
            pointer-events: none;
            z-index: 1;
        }

        /* BACKGROUND SUBTLE WATERMARK */
        .copy-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 250px;
            height: 250px;
            pointer-events: none;
            opacity: 0.035;
            z-index: 0;
        }

        .copy-content-inner {
            position: relative;
            z-index: 2;
        }

        /* HEADER SECTION */
        .copy-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .company-brand-box {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-left: 32px;
            margin-bottom: 4px;
        }

        .brand-logo-img {
            height: 44px;
            width: auto;
            max-width: 250px;
            object-fit: contain;
            display: block;
        }

        .brand-logo-svg {
            width: 44px;
            height: 44px;
            flex-shrink: 0;
        }

        .brand-text-col {
            display: flex;
            flex-direction: column;
        }

        .brand-name {
            font-size: 15px;
            font-weight: 900;
            color: var(--theme-dark);
            letter-spacing: 0.5px;
            line-height: 1.1;
        }

        .brand-slogan {
            font-size: 8px;
            font-weight: 700;
            color: var(--theme-primary);
            letter-spacing: 1.2px;
            margin-top: 2px;
            text-transform: uppercase;
        }

        .brand-domain {
            font-size: 11px;
            font-weight: 800;
            color: var(--theme-dark);
            margin-top: 3px;
        }

        .brand-address {
            font-size: 9.5px;
            color: #334155;
            font-weight: 500;
            line-height: 1.35;
            margin-top: 2px;
            max-width: 420px;
        }

        .brand-contact-row {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-top: 3px;
            font-size: 9.5px;
            color: #1e293b;
            font-weight: 600;
        }
        .brand-contact-row i {
            color: var(--theme-primary);
            font-size: 8.5px;
        }

        .receipt-meta-box {
            text-align: right;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .receipt-title-line {
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: flex-end;
        }

        .receipt-heading {
            font-size: 17px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: 0.5px;
            line-height: 1;
        }

        .copy-pill {
            background: var(--theme-deep);
            color: #ffffff;
            font-size: 9.5px;
            font-weight: 800;
            padding: 3px 12px;
            border-radius: 4px;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }

        .receipt-no {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            margin-top: 3px;
        }

        .receipt-date {
            font-size: 11px;
            color: #334155;
            font-weight: 600;
            margin-top: 1px;
        }

        /* META STATUS & QR ROW */
        .meta-status-wrap {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 4px;
        }

        .header-qr-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3px 6px 4px 6px;
            background: #ffffff;
            border: 1px solid var(--theme-border);
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
            flex-shrink: 0;
        }

        .header-qr-card:hover {
            border-color: var(--theme-light);
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.12);
        }

        .hqr-img-box {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .header-qr-img {
            width: 42px;
            height: 42px;
            object-fit: contain;
            display: block;
        }

        .header-qr-img.qr-placeholder {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: var(--theme-primary);
            background: var(--theme-bg-light);
            border-radius: 3px;
        }

        .header-qr-label {
            font-size: 7.5px;
            font-weight: 800;
            color: var(--theme-primary);
            margin-top: 2px;
            text-align: center;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .qr-authority-hide {
            display: none;
        }

        /* STATUS BOX */
        .status-box {
            display: flex;
            align-items: center;
            gap: 8px;
            border-radius: 6px;
            padding: 4px 10px;
            margin-top: 0;
            text-align: left;
            width: fit-content;
        }

        .status-pending {
            background: #fffbeb;
            border: 1px solid #fef3c7;
        }
        .status-pending .sb-icon {
            color: #d97706;
            font-size: 16px;
        }
        .status-pending .sb-tag {
            color: #b45309;
        }
        .status-pending .sb-title {
            color: #92400e;
        }
        .status-pending .sb-sub {
            color: #78350f;
        }

        .status-verified {
            background: var(--theme-bg-light);
            border: 1px solid var(--theme-border);
        }
        .status-verified .sb-icon {
            color: var(--theme-primary);
            font-size: 16px;
        }
        .status-verified .sb-tag {
            color: var(--theme-dark);
        }
        .status-verified .sb-title {
            color: var(--theme-primary);
        }
        .status-verified .sb-sub {
            color: var(--theme-deep);
        }

        .sb-tag {
            font-size: 8px;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .sb-title {
            font-size: 10.5px;
            font-weight: 800;
            line-height: 1.15;
        }

        .sb-sub {
            font-size: 8px;
            line-height: 1.15;
            margin-top: 1px;
        }

        /* 2-COLUMN INFO CARDS */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 8px;
        }

        .info-card {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 6px 10px;
            background: #ffffff;
        }

        .card-header-row {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 5px;
        }

        .head-icon-box {
            background: var(--theme-deep);
            color: #ffffff;
            width: 18px;
            height: 18px;
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            flex-shrink: 0;
        }

        .head-title {
            font-size: 10px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .item-row {
            display: flex;
            align-items: baseline;
            margin-bottom: 3px;
            font-size: 10px;
            line-height: 1.35;
        }
        .item-row:last-child {
            margin-bottom: 0;
        }

        .ir-label {
            width: 82px;
            color: #334155;
            font-weight: 600;
            flex-shrink: 0;
        }

        .ir-val {
            font-weight: 800;
            color: #0f172a;
            font-size: 10.5px;
            word-break: break-word;
        }

        /* TOTAL AMOUNT BANNER */
        .amount-card {
            background: var(--theme-bg-light);
            border: 1px solid var(--theme-border);
            border-radius: 6px;
            padding: 6px 14px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .amt-left {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .amt-paid-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .amt-icon-box {
            background: var(--theme-deep);
            color: #ffffff;
            width: 18px;
            height: 18px;
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
        }

        .amt-paid-label {
            font-size: 10.5px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.4px;
        }

        .amt-method-pill {
            background: var(--theme-deep);
            color: #ffffff;
            font-size: 9px;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 3px;
            text-transform: uppercase;
        }

        .amt-words-row {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .words-pill {
            background: var(--theme-badge-bg);
            color: var(--theme-primary);
            font-size: 8px;
            font-weight: 800;
            padding: 1px 5px;
            border-radius: 3px;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }

        .words-val {
            font-size: 10.5px;
            font-weight: 800;
            color: #0f172a;
        }

        .amt-divider-line {
            width: 1.5px;
            height: 30px;
            background: #cbd5e1;
            margin: 0 16px;
        }

        .amt-right {
            text-align: right;
            white-space: nowrap;
        }

        .amt-number-val {
            font-size: 22px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.5px;
        }

        /* BANK & PROCESSING INFORMATION */
        .processing-box {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 6px 10px;
            margin-bottom: 8px;
            background: #ffffff;
        }

        .proc-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1.25fr 0.75fr;
            gap: 10px;
            margin-top: 3px;
        }

        .proc-grid.proc-grid-cash {
            grid-template-columns: 1.2fr 1fr 1fr;
        }

        .proc-grid.proc-grid-online {
            grid-template-columns: 1.2fr 1fr 1.2fr 0.8fr;
        }

        .proc-col {
            font-size: 9.5px;
            line-height: 1.35;
        }

        .proc-lbl {
            color: #334155;
            font-size: 9.5px;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .proc-main {
            font-weight: 800;
            color: #0f172a;
            font-size: 10.5px;
        }

        .proc-sub {
            color: #334155;
            font-size: 9px;
            font-weight: 600;
        }
        .proc-sub.bold {
            font-weight: 800;
            color: #0f172a;
        }

        /* VERIFICATION ROW */
        .verification-bar {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: #f8fafc;
            padding: 5px 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .v-left {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .v-shield-box {
            background: var(--theme-deep);
            color: #ffffff;
            width: 20px;
            height: 20px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            flex-shrink: 0;
        }

        .v-title {
            font-size: 10px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
            margin-right: 10px;
        }

        .v-meta-group {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .v-meta-item {
            display: flex;
            flex-direction: column;
            font-size: 8.5px;
        }

        .vm-tag {
            color: #334155;
            font-weight: 700;
        }

        .vm-txt {
            font-weight: 800;
            color: #0f172a;
            font-size: 10.5px;
        }

        .text-green {
            color: var(--theme-dark) !important;
            font-weight: 800 !important;
        }
        .text-amber {
            color: #92400e !important;
            font-weight: 800 !important;
        }

        /* VERIFICATION RIGHT (Authorized Signature) */
        .v-right-signature {
            text-align: right;
            min-width: 140px;
        }

        .sig-line {
            border-bottom: 1.5px dashed #cbd5e1;
            height: 32px;
            width: 130px;
            margin-left: auto;
        }

        .sig-label {
            font-size: 8.5px;
            font-weight: 700;
            color: #334155;
            margin-top: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* PERFORATED CUT LINE */
        .perforated-cut-divider {
            position: relative;
            padding: 4px 20px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            page-break-inside: avoid;
        }

        .cut-dashed-line {
            position: absolute;
            left: 24px;
            right: 24px;
            top: 50%;
            height: 1px;
            border-top: 1.5px dashed #94a3b8;
            z-index: 1;
        }

        .cut-badge-center {
            position: relative;
            z-index: 2;
            background: #ffffff;
            padding: 0 10px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #334155;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .cut-badge-center i {
            font-size: 10px;
            color: #334155;
        }

        /* RESPONSIVE ON MOBILE */
        @media screen and (max-width: 768px) {
            .top-action-bar {
                flex-direction: column;
                gap: 10px;
                align-items: stretch;
            }
            .bar-actions {
                justify-content: space-between;
            }
            .receipt-container-wrap {
                padding: 0 4px;
                margin-top: 10px;
            }
            .copy-header {
                flex-direction: column;
                gap: 8px;
            }
            .receipt-meta-box {
                align-items: flex-start;
                text-align: left;
            }
            .receipt-title-line {
                justify-content: flex-start;
            }
            .proc-grid {
                grid-template-columns: 1fr 1fr;
            }
            .verification-bar {
                flex-direction: column;
                gap: 8px;
                align-items: flex-start;
            }
            .v-right-customer {
                width: 100%;
                justify-content: space-around;
            }
        }

        /* PDF EXPORT RENDER CONTAINER - FIXED 800px DESKTOP LAYOUT (NEVER CUTS OFF) */
        #pdf-render-container {
            width: 800px !important;
            max-width: 800px !important;
            min-width: 800px !important;
            background: #ffffff !important;
        }

        #pdf-render-container #receipt-content {
            width: 800px !important;
            max-width: 800px !important;
            min-width: 800px !important;
            margin: 0 !important;
            padding: 0 !important;
            box-shadow: none !important;
            border: none !important;
            border-radius: 0 !important;
        }

        #pdf-render-container .receipt-copy {
            padding: 12px 22px 10px 22px !important;
            width: 800px !important;
            box-sizing: border-box !important;
        }

        #pdf-render-container .copy-header {
            display: flex !important;
            flex-direction: row !important;
            justify-content: space-between !important;
            align-items: flex-start !important;
        }

        #pdf-render-container .corner-accent-tl {
            width: 64px !important;
            height: 64px !important;
        }

        #pdf-render-container .company-brand-box {
            margin-left: 32px !important;
            margin-bottom: 4px !important;
        }

        #pdf-render-container .receipt-meta-box {
            align-items: flex-end !important;
            text-align: right !important;
        }

        #pdf-render-container .receipt-title-line {
            justify-content: flex-end !important;
        }

        #pdf-render-container .brand-logo-img {
            height: 44px !important;
            max-width: 250px !important;
        }

        #pdf-render-container .brand-address {
            font-size: 9.5px !important;
            color: #1e293b !important;
            line-height: 1.35 !important;
        }

        #pdf-render-container .brand-contact-row {
            font-size: 9.5px !important;
            color: #1e293b !important;
        }

        #pdf-render-container .info-grid {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 12px !important;
            margin-bottom: 8px !important;
        }

        #pdf-render-container .ir-label {
            color: #1e293b !important;
            font-weight: 600 !important;
        }

        #pdf-render-container .ir-val {
            font-weight: 800 !important;
            color: #0f172a !important;
            font-size: 10.5px !important;
        }

        #pdf-render-container .amount-card {
            display: flex !important;
            flex-direction: row !important;
            justify-content: space-between !important;
            align-items: center !important;
            padding: 8px 14px !important;
            margin-bottom: 8px !important;
        }

        #pdf-render-container .amount-number-val {
            font-size: 22px !important;
        }

        #pdf-render-container .processing-box {
            padding: 8px 14px !important;
            margin-bottom: 8px !important;
        }

        #pdf-render-container .proc-grid {
            display: grid !important;
            grid-template-columns: 1fr 1fr 1.25fr 0.8fr !important;
            gap: 8px !important;
        }

        #pdf-render-container .proc-grid.proc-grid-cash {
            grid-template-columns: 1.2fr 1fr 1fr !important;
        }

        #pdf-render-container .proc-grid.proc-grid-online {
            grid-template-columns: 1.2fr 1fr 1.2fr 0.8fr !important;
        }

        #pdf-render-container .proc-lbl {
            font-size: 9.5px !important;
            font-weight: 700 !important;
            color: #1e293b !important;
        }

        #pdf-render-container .proc-main {
            font-size: 10.5px !important;
            font-weight: 800 !important;
            color: #0f172a !important;
        }

        #pdf-render-container .proc-sub {
            font-size: 9px !important;
            font-weight: 600 !important;
            color: #1e293b !important;
        }

        #pdf-render-container .proc-sub.bold {
            font-weight: 800 !important;
            color: #0f172a !important;
        }

        #pdf-render-container .verification-bar {
            display: flex !important;
            flex-direction: row !important;
            justify-content: space-between !important;
            align-items: center !important;
            padding: 8px 14px !important;
            margin-top: 0 !important;
        }

        #pdf-render-container .vm-tag {
            font-size: 8.5px !important;
            font-weight: 700 !important;
            color: #1e293b !important;
        }

        #pdf-render-container .vm-txt {
            font-size: 10.5px !important;
            font-weight: 800 !important;
        }

        #pdf-render-container .text-green {
            color: #0b4022 !important;
        }

        #pdf-render-container .text-amber {
            color: #92400e !important;
        }

        #pdf-render-container .meta-status-wrap {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-end !important;
            gap: 7px !important;
            margin-top: 3px !important;
        }

        #pdf-render-container .header-qr-card {
            padding: 2px 5px 3px 5px !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            border: 1px solid var(--theme-border) !important;
        }

        #pdf-render-container .header-qr-img {
            width: 38px !important;
            height: 38px !important;
        }

        #pdf-render-container .header-qr-label {
            font-size: 7.5px !important;
            font-weight: 800 !important;
        }

        #pdf-render-container .v-right-signature {
            width: auto !important;
            justify-content: flex-end !important;
        }

        #pdf-render-container .sig-line {
            height: 32px !important;
            width: 130px !important;
            border-bottom: 1.5px dashed #64748b !important;
        }

        #pdf-render-container .sig-label {
            font-size: 8.5px !important;
            font-weight: 700 !important;
            color: #1e293b !important;
        }

        #pdf-render-container .perforated-cut-divider {
            padding: 4px 22px !important;
            display: block !important;
        }

        /* PRINT STYLES - NATURAL, TIGHT, CRISP */
        @media print {
            @page {
                size: A4 portrait;
                margin: 4mm 6mm;
            }

            html, body {
                background: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
                height: auto !important;
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                text-rendering: optimizeLegibility !important;
                -webkit-font-smoothing: subpixel-antialiased !important;
            }

            .no-print, .top-action-bar {
                display: none !important;
            }

            .receipt-container-wrap {
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            #receipt-content {
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                width: 100% !important;
                height: auto !important;
                min-height: 0 !important;
                page-break-inside: avoid !important;
            }

            /* BOTH COPIES: CONTINUOUS NATURAL FLOW */
            .receipt-copy {
                page-break-inside: avoid !important;
                padding: 8px 18px 6px 18px !important;
                box-sizing: border-box !important;
                height: auto !important;
                min-height: 0 !important;
            }

            .copy-content-inner {
                width: 100% !important;
            }

            .copy-header {
                margin-bottom: 6px !important;
            }

            .meta-status-wrap {
                margin-top: 3px !important;
                gap: 7px !important;
            }

            .header-qr-card {
                padding: 2px 5px 3px 5px !important;
                border: 1px solid var(--theme-border) !important;
                box-shadow: none !important;
            }

            .header-qr-img {
                width: 38px !important;
                height: 38px !important;
            }

            .header-qr-label {
                font-size: 7.5px !important;
                font-weight: 800 !important;
            }

            .brand-address {
                font-size: 9.5px !important;
                color: #1e293b !important;
                line-height: 1.35 !important;
            }

            .brand-contact-row {
                font-size: 9.5px !important;
                color: #1e293b !important;
            }

            .info-grid {
                margin-bottom: 6px !important;
                gap: 10px !important;
            }

            .info-card {
                padding: 5px 12px !important;
            }

            .item-row {
                margin-top: 3px !important;
                font-size: 10px !important;
            }

            .ir-label {
                color: #1e293b !important;
                font-weight: 600 !important;
            }

            .ir-val {
                color: #0f172a !important;
                font-weight: 800 !important;
            }

            .amount-card {
                padding: 6px 14px !important;
                margin-bottom: 6px !important;
            }

            .amt-paid-label {
                font-size: 10.5px !important;
                font-weight: 800 !important;
                color: #0f172a !important;
            }

            .words-val {
                font-size: 10.5px !important;
                font-weight: 800 !important;
                color: #0f172a !important;
            }

            .amount-number-val {
                font-size: 22px !important;
                font-weight: 900 !important;
            }

            .processing-box {
                padding: 6px 12px !important;
                margin-bottom: 6px !important;
            }

            .proc-lbl {
                font-size: 9.5px !important;
                font-weight: 700 !important;
                color: #1e293b !important;
            }

            .proc-main {
                font-size: 10.5px !important;
                font-weight: 800 !important;
                color: #0f172a !important;
            }

            .proc-sub {
                font-size: 9px !important;
                font-weight: 600 !important;
                color: #1e293b !important;
            }

            .proc-sub.bold {
                font-weight: 800 !important;
                color: #0f172a !important;
            }

            .verification-bar {
                padding: 5px 12px !important;
                margin-top: 0 !important;
            }

            .vm-tag {
                font-size: 8.5px !important;
                font-weight: 700 !important;
                color: #1e293b !important;
            }

            .vm-txt {
                font-size: 10.5px !important;
                font-weight: 800 !important;
            }

            .text-green {
                color: #0b4022 !important;
            }

            .text-amber {
                color: #92400e !important;
            }

            .sig-line {
                height: 30px !important;
                width: 130px !important;
                border-bottom: 1.5px dashed #64748b !important;
            }

            .sig-label {
                font-size: 8.5px !important;
                font-weight: 700 !important;
                color: #1e293b !important;
            }

            .perforated-cut-divider {
                padding: 3px 18px !important;
                margin: 3px 0 !important;
                display: flex !important;
                align-items: center !important;
                page-break-inside: avoid !important;
            }

            .corner-accent-tl {
                width: 58px !important;
                height: 58px !important;
            }

            .corner-accent-br {
                width: 58px !important;
                height: 58px !important;
            }

            .company-brand-box {
                margin-left: 28px !important;
                margin-bottom: 3px !important;
            }

            /* ========================================================
               PRINT CUSTOMER ONLY: PRINTS 2 CUSTOMER COPIES ON A4
               - Zero gaps inside the receipt (looks 100% like invoice)
               - Fills the entire page without blank paper at the bottom
               - Authority copy is converted into a 2nd Customer Copy
               ======================================================== */
            body.print-customer-only .copy-pill-authority .pill-authority-txt {
                display: none !important;
            }

            body.print-customer-only .copy-pill-authority .pill-customer-txt {
                display: inline !important;
            }

            body.print-customer-only .copy-authority .qr-authority-hide {
                display: flex !important;
            }
        }
    </style>
</head>
<body>

    <!-- SCREEN-ONLY ACTION BAR -->
    <div class="top-action-bar no-print">
        <div class="bar-left">
            <a href="<?= $GLOBALS['base_url'] ?>/clients-cash-in" class="btn-act btn-outline">
                <i class="fa-solid fa-arrow-left"></i> Back to History
            </a>
            <span class="bar-title">Payment Receipt #REC-<?= str_pad($payment['id'], 6, '0', STR_PAD_LEFT) ?></span>
            <span class="bar-badge"><?= $isVerified ? 'VERIFIED' : 'PENDING VERIFICATION' ?></span>
        </div>
        <div class="bar-actions">
            <button onclick="printBothCopies()" class="btn-act btn-outline" title="Print both Customer Copy & Authority Copy on 1 single A4 page">
                <i class="fa-solid fa-print"></i> Print Both (A4)
            </button>
            <button onclick="printCustomerOnly()" class="btn-act btn-outline" title="Print Customer Copy only">
                <i class="fa-solid fa-file-invoice"></i> Print Customer Copy
            </button>
            <!-- <button onclick="downloadPDF()" class="d-none btn-act btn-primary">
                <i class="fa-solid fa-file-arrow-down d-none "></i> Download PDF
            </button> -->
            <a href="<?= htmlspecialchars($verificationUrl) ?>" target="_blank" class="btn-act btn-outline" title="Public Verification Portal">
                <i class="fa-solid fa-shield-halved"></i> Verification Link
            </a>
        </div>
    </div>

    <!-- MAIN RECEIPT DOCUMENT (BOTH CUSTOMER & AUTHORITY COPIES) -->
    <div class="receipt-container-wrap">
        <div id="receipt-content">

            <?php
            // RENDER 2 COPIES: Customer Copy & Authority Copy
            $copies = [
                ['type' => 'CUSTOMER COPY', 'isCustomer' => true],
                ['type' => 'AUTHORITY COPY', 'isCustomer' => false]
            ];

            foreach ($copies as $idx => $cp):
                $copyType = $cp['type'];
                $isCustomer = $cp['isCustomer'];
            ?>

            <!-- COPY START -->
            <div class="receipt-copy <?= $isCustomer ? 'copy-customer' : 'copy-authority' ?>">
                
                <!-- CORNER GEOMETRIC ACCENTS -->
                <svg class="corner-accent-tl" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <polygon points="0,0 100,0 0,78" fill="<?= $themeDark ?>" />
                    <polygon points="0,78 100,0 100,12 18,78" fill="<?= $themeLight ?>" />
                </svg>
                <svg class="corner-accent-br" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <polygon points="100,100 0,100 100,22" fill="<?= $themeDark ?>" />
                    <polygon points="100,22 0,100 0,88 82,22" fill="<?= $themeLight ?>" />
                </svg>

                <!-- CENTER WATERMARK -->
                <svg class="copy-watermark" viewBox="0 0 200 200" fill="none">
                    <path d="M100 18 L175 80 V175 H25 V80 L100 18 Z" stroke="<?= $themePrimary ?>" stroke-width="4"/>
                    <path d="M100 36 L158 84 V158 H42 V84 L100 36 Z" stroke="<?= $themePrimary ?>" stroke-width="2.5"/>
                    <line x1="100" y1="36" x2="100" y2="158" stroke="<?= $themePrimary ?>" stroke-width="2"/>
                    <line x1="42" y1="98" x2="158" y2="98" stroke="<?= $themePrimary ?>" stroke-width="2"/>
                </svg>

                <div class="copy-content-inner">
                    <!-- HEADER -->
                    <div class="copy-header">
                        <div class="header-left">
                            <div class="company-brand-box">
                                <?php if (!empty($logoSrc)): ?>
                                    <img src="<?= $logoSrc ?>" alt="<?= htmlspecialchars($companyTitle ?? 'Company Logo') ?>" class="brand-logo-img" />
                                <?php else: ?>
                                    <div class="brand-text-col">
                                        <div class="brand-name"><?= htmlspecialchars(strtoupper($companyTitle ?? 'THE PREMIUM HOMES LTD')) ?></div>
                                        <div class="brand-slogan">YOUR DREAM HOME AWAITS</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="brand-domain"><?= htmlspecialchars($brandDomain ?? 'Real Estate & Property Management') ?></div>
                            <div class="brand-address"><?= htmlspecialchars($companyAddress) ?></div>
                            <div class="brand-contact-row">
                                <span><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($companyPhone ?? '+8801958253303') ?></span>
                                <span><i class="fa-solid fa-envelope"></i> <?= htmlspecialchars($companyEmail ?? 'accounts@dpremiumhomes.com') ?></span>
                            </div>
                        </div>

                        <div class="receipt-meta-box">
                            <div class="receipt-title-line">
                                <span class="receipt-heading">PAYMENT RECEIPT</span>
                                <span class="copy-pill <?= !$isCustomer ? 'copy-pill-authority' : '' ?>">
                                    <?php if (!$isCustomer): ?>
                                        <span class="pill-authority-txt">AUTHORITY COPY</span>
                                        <span class="pill-customer-txt" style="display:none;">CUSTOMER COPY</span>
                                    <?php else: ?>
                                        CUSTOMER COPY
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="receipt-no">#REC-<?= str_pad($payment['id'], 6, '0', STR_PAD_LEFT) ?></div>
                            <div class="receipt-date"><?= $paymentDateFormatted ?></div>
                            <div class="meta-status-wrap">
                                <!-- QR CODE SCANNER (Placed nicely next to Payment Status) -->
                                <a href="<?= htmlspecialchars($verificationUrl) ?>" target="_blank" class="header-qr-card <?= !$isCustomer ? 'qr-authority-hide' : '' ?>" title="Click to verify online">
                                    <div class="hqr-img-box">
                                        <?php if (!empty($qrImageSrc)): ?>
                                            <img src="<?= $qrImageSrc ?>" alt="Scan to Verify" class="header-qr-img">
                                        <?php else: ?>
                                            <div class="header-qr-img qr-placeholder"><i class="fa-solid fa-qrcode"></i></div>
                                        <?php endif; ?>
                                    </div>
                                    <span class="header-qr-label">Scan to Verify</span>
                                </a>

                                <!-- PAYMENT STATUS BOX -->
                                <div class="status-box <?= $isVerified ? 'status-verified' : 'status-pending' ?>">
                                    <div class="sb-icon">
                                        <?php if ($isVerified): ?>
                                            <i class="fa-solid fa-circle-check"></i>
                                        <?php else: ?>
                                            <i class="fa-solid fa-hourglass-half"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="sb-text">
                                        <div class="sb-tag">PAYMENT STATUS</div>
                                        <div class="sb-title"><?= $isVerified ? 'Verified &amp; Confirmed' : 'Subject to Bank Verification' ?></div>
                                        <div class="sb-sub"><?= $isVerified ? 'Payment verified and recorded officially.' : 'Confirmation will be issued after verification.' ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2-COLUMN INFO: RECEIVED FROM & PROPERTY INFO -->
                    <div class="info-grid">
                        <div class="info-card">
                            <div class="card-header-row">
                                <span class="head-icon-box"><i class="fa-solid fa-user"></i></span>
                                <span class="head-title">RECEIVED FROM</span>
                            </div>
                            <div class="item-row">
                                <span class="ir-label">Client Name</span>
                                <span class="ir-val"><?= htmlspecialchars($clientName) ?></span>
                            </div>
                            <div class="item-row">
                                <span class="ir-label">Phone</span>
                                <span class="ir-val"><?= htmlspecialchars($clientPhone) ?></span>
                            </div>
                            <div class="item-row">
                                <span class="ir-label">Email</span>
                                <span class="ir-val"><?= htmlspecialchars($clientEmail) ?></span>
                            </div>
                        </div>

                        <div class="info-card">
                            <div class="card-header-row">
                                <span class="head-icon-box"><i class="fa-solid fa-building"></i></span>
                                <span class="head-title">PROPERTY INFORMATION</span>
                            </div>
                            <div class="item-row">
                                <span class="ir-label">Project</span>
                                <span class="ir-val"><?= htmlspecialchars($projectName) ?></span>
                            </div>
                            <div class="item-row">
                                <span class="ir-label">Unit</span>
                                <span class="ir-val"><?= htmlspecialchars($unitName) ?></span>
                            </div>
                            <div class="item-row">
                                <span class="ir-label">Floor</span>
                                <span class="ir-val"><?= htmlspecialchars($floorNo) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- TOTAL AMOUNT PAID BANNER -->
                    <?php
                        if (empty($amountInWords)) {
                            $calcAmount = $totalAmountPaid ?? ($payment['amount'] ?? 0);
                            $amountInWords = class_exists('FormatHelper') ? FormatHelper::amountInWords($calcAmount) : (function_exists('amountInWords') ? amountInWords($calcAmount) : '');
                        }
                    ?>
                    <div class="amount-card">
                        <div class="amt-left">
                            <div class="amt-paid-row">
                                <span class="amt-icon-box"><i class="fa-solid fa-landmark"></i></span>
                                <span class="amt-paid-label">TOTAL AMOUNT PAID BY</span>
                                <span class="amt-method-pill"><?= htmlspecialchars($paymentMethod) ?></span>
                            </div>
                            <div class="amt-words-row">
                                <span class="words-pill">IN WORDS</span>
                                <span class="words-val"><?= htmlspecialchars($amountInWords) ?></span>
                            </div>
                        </div>
                        <div class="amt-divider-line"></div>
                        <div class="amt-right">
                            <div class="amt-number-val">TK. <?= number_format($totalAmountPaid, 2) ?></div>
                        </div>
                    </div>

                    <?php
                        $isCash = (strtoupper($paymentMethod ?? ($payment['payment_method'] ?? '')) === 'CASH');
                        $isOnline = (strtoupper($paymentMethod ?? ($payment['payment_method'] ?? '')) === 'ONLINE');
                    ?>

                    <!-- PAYMENT & PROCESSING INFORMATION -->
                    <div class="processing-box">
                        <div class="card-header-row">
                            <span class="head-icon-box"><i class="fa-solid <?= $isCash ? 'fa-money-bill-wave' : ($isOnline ? 'fa-mobile-screen-button' : 'fa-building-columns') ?>"></i></span>
                            <span class="head-title"><?= $isCash ? 'PAYMENT &amp; PROCESSING INFORMATION' : ($isOnline ? 'ONLINE PAYMENT &amp; PROCESSING INFORMATION' : 'BANK &amp; PROCESSING INFORMATION') ?></span>
                        </div>

                        <?php if ($isCash): ?>
                            <!-- CASH PAYMENT: NO BANK INFO AT ALL -->
                            <div class="proc-grid proc-grid-cash">
                                <div class="proc-col">
                                    <div class="proc-lbl">Created By:</div>
                                    <div class="proc-main"><?= htmlspecialchars($createdByName) ?></div>
                                    <?php if (!empty($createdByEmpId)): ?>
                                        <div class="proc-sub">(<?= htmlspecialchars($createdByEmpId) ?>)</div>
                                    <?php endif; ?>
                                </div>
                                <div class="proc-col">
                                    <div class="proc-lbl">Payment Mode</div>
                                    <div class="proc-main" style="color:#0b6638; font-weight:800;">
                                        <i class="fa-solid fa-money-bill-1-wave me-1"></i> Cash in Hand / Office
                                    </div>
                                    <div class="proc-sub">Direct Cash Collection</div>
                                </div>
                                <div class="proc-col">
                                    <div class="proc-lbl">Ref / Receipt Number</div>
                                    <div class="proc-main"><?= htmlspecialchars(!empty($refNo) && $refNo !== 'N/A' ? $refNo : ('REC-' . str_pad($payment['id'] ?? '0', 6, '0', STR_PAD_LEFT))) ?></div>
                                </div>
                            </div>
                        <?php elseif ($isOnline): ?>
                            <!-- ONLINE PAYMENT: ONLINE TYPE & RECEIVER BANK NAME & ACCOUNTS -->
                            <div class="proc-grid proc-grid-online">
                                <div class="proc-col">
                                    <div class="proc-lbl">Created By:</div>
                                    <div class="proc-main"><?= htmlspecialchars($createdByName) ?></div>
                                    <?php if (!empty($createdByEmpId)): ?>
                                        <div class="proc-sub">(<?= htmlspecialchars($createdByEmpId) ?>)</div>
                                    <?php endif; ?>
                                </div>
                                <div class="proc-col">
                                    <div class="proc-lbl">Online Type / Gateway</div>
                                    <div class="proc-main" style="color:#0284c7; font-weight:800;">
                                        <i class="fa-solid fa-mobile-screen me-1"></i>
                                        <?= !empty($onlineType) ? htmlspecialchars(strtoupper($onlineType)) : (!empty($payment['online_type']) ? htmlspecialchars(strtoupper($payment['online_type'])) : 'ONLINE') ?>
                                    </div>
                                    <?php if (!empty($clientAccountNo) && !in_array(strtolower($clientAccountNo), ['n/a', 'none', '0', '00', 'null'])): ?>
                                        <div class="proc-sub bold">• A/C: <?= htmlspecialchars($clientAccountNo) ?></div>
                                    <?php else: ?>
                                        <div class="proc-sub">Digital Channel</div>
                                    <?php endif; ?>
                                </div>
                                <div class="proc-col">
                                    <div class="proc-lbl">Receiver Bank</div>
                                    <div class="proc-main"><?= htmlspecialchars(!empty($receiverBankName) && $receiverBankName !== 'N/A' ? $receiverBankName : 'Company Bank') ?></div>
                                    <?php if (!empty($receiverHolderName)): ?>
                                        <div class="proc-sub"><?= htmlspecialchars($receiverHolderName) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($receiverAccountNo) && $receiverAccountNo !== 'N/A'): ?>
                                        <div class="proc-sub bold">• A/C: <?= htmlspecialchars($receiverAccountNo) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="proc-col">
                                    <div class="proc-lbl">Trx ID / Ref Number</div>
                                    <div class="proc-main"><?= htmlspecialchars(!empty($refNo) ? $refNo : 'N/A') ?></div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- BANK / CHEQUE PAYMENT: FULL BANK DETAILS -->
                            <div class="proc-grid">
                                <div class="proc-col">
                                    <div class="proc-lbl">Created By:</div>
                                    <div class="proc-main"><?= htmlspecialchars($createdByName) ?></div>
                                    <?php if (!empty($createdByEmpId)): ?>
                                        <div class="proc-sub">(<?= htmlspecialchars($createdByEmpId) ?>)</div>
                                    <?php endif; ?>
                                </div>
                                <div class="proc-col">
                                    <div class="proc-lbl">Sender Bank</div>
                                    <div class="proc-main"><?= htmlspecialchars($senderBankName ?: 'N/A') ?></div>
                                    <?php if (!empty($clientAccountNo) && !in_array(strtolower($clientAccountNo), ['n/a', 'none', '0', '00', 'null'])): ?>
                                        <div class="proc-sub bold">• A/C: <?= htmlspecialchars($clientAccountNo) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="proc-col">
                                    <div class="proc-lbl">Receiver Bank</div>
                                    <div class="proc-main"><?= htmlspecialchars($receiverBankName ?: 'N/A') ?></div>
                                    <?php if (!empty($receiverHolderName)): ?>
                                        <div class="proc-sub"><?= htmlspecialchars($receiverHolderName) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($receiverAccountNo) && $receiverAccountNo !== 'N/A'): ?>
                                        <div class="proc-sub bold">• A/C: <?= htmlspecialchars($receiverAccountNo) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="proc-col">
                                    <div class="proc-lbl"><?= htmlspecialchars($refLabel ?? 'Ref Number') ?></div>
                                    <div class="proc-main"><?= htmlspecialchars(!empty($refNo) ? $refNo : 'N/A') ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- VERIFICATION ROW -->
                    <div class="verification-bar">
                        <div class="v-left">
                            <div class="v-shield-box">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>
                            <div class="v-title">VERIFICATION</div>
                            <div class="v-meta-group">
                                <div class="v-meta-item">
                                    <span class="vm-tag">Receipt ID:</span>
                                    <span class="vm-txt">#REC-<?= str_pad($payment['id'], 6, '0', STR_PAD_LEFT) ?></span>
                                </div>
                                <div class="v-meta-item">
                                    <span class="vm-tag">Verified By:</span>
                                    <span class="vm-txt <?= $isVerified ? 'text-green' : 'text-amber' ?>"><?= htmlspecialchars($verifiedByDisplay) ?></span>
                                </div>
                                <div class="v-meta-item">
                                    <span class="vm-tag">Verified On:</span>
                                    <span class="vm-txt <?= $isVerified ? 'text-green' : 'text-amber' ?>"><?= htmlspecialchars($verifiedDateFormatted) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- SIGNATURE SPACE (BOTH COPIES) -->
                        <div class="v-right-signature">
                            <div class="sig-line"></div>
                            <div class="sig-label">Authorized Signature</div>
                        </div>

                    </div>
                </div>
            </div>
            <!-- COPY END -->

            <?php if ($idx === 0): ?>
            <!-- PERFORATED CUT DIVIDER -->
            <div class="perforated-cut-divider">
                <div class="cut-dashed-line"></div>
                <div class="cut-badge-center">
                    <i class="fa-solid fa-scissors"></i> CUT HERE
                </div>
            </div>
            <?php endif; ?>

            <?php endforeach; ?>

        </div>
    </div>

    <!-- PDF GENERATION SCRIPT -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function printBothCopies() {
            document.body.classList.remove('print-customer-only');
            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(function() {
                    window.print();
                });
            } else {
                window.print();
            }
        }

        function printCustomerOnly() {
            document.body.classList.add('print-customer-only');
            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(function() {
                    window.print();
                });
            } else {
                window.print();
            }
            setTimeout(function() {
                document.body.classList.remove('print-customer-only');
            }, 1000);
        }

        function downloadPDF() {
            const btn = document.querySelector('.bar-actions .btn-primary');
            const originalBtnHtml = btn ? btn.innerHTML : '';
            if (btn) {
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating PDF...';
                btn.disabled = true;
            }

            const runRender = function() {
                const original = document.getElementById('receipt-content');
                
                // 1. Create a clean isolated capture container with fixed 800px width at (0, 0)
                // This eliminates any screen-width / centering offset / scrolling cut-offs on all devices!
                const container = document.createElement('div');
                container.id = 'pdf-render-container';
                container.style.position = 'fixed';
                container.style.top = '0';
                container.style.left = '0';
                container.style.width = '800px';
                container.style.maxWidth = '800px';
                container.style.minWidth = '800px';
                container.style.margin = '0';
                container.style.padding = '0';
                container.style.backgroundColor = '#ffffff';
                container.style.zIndex = '-99999';
                container.style.overflow = 'visible';

                const clone = original.cloneNode(true);
                clone.style.width = '800px';
                clone.style.maxWidth = '800px';
                clone.style.minWidth = '800px';
                clone.style.margin = '0';
                clone.style.borderRadius = '0';
                clone.style.boxShadow = 'none';
                clone.style.border = 'none';

                container.appendChild(clone);
                document.body.appendChild(container);

                const opt = {
                    margin:       [3.5, 4, 3.5, 4],
                    filename:     'Payment-Receipt-<?= str_pad($payment['id'] ?? '0', 6, '0', STR_PAD_LEFT) ?>-<?= date('Ymd') ?>.pdf',
                    image:        { type: 'jpeg', quality: 0.98 },
                    html2canvas:  { 
                        scale: 2.5, 
                        useCORS: true, 
                        logging: false,
                        scrollX: 0,
                        scrollY: 0,
                        width: 800,
                        windowWidth: 800
                    },
                    jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
                };

                html2pdf().set(opt).from(clone).save().then(function() {
                    if (container.parentNode) container.parentNode.removeChild(container);
                    if (btn) {
                        btn.innerHTML = originalBtnHtml;
                        btn.disabled = false;
                    }
                }).catch(function(err) {
                    console.error('PDF Generation Error:', err);
                    if (container.parentNode) container.parentNode.removeChild(container);
                    if (btn) {
                        btn.innerHTML = originalBtnHtml;
                        btn.disabled = false;
                    }
                });
            };

            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(runRender);
            } else {
                runRender();
            }
        }
        
        <?php if (!empty($_GET['download']) && $_GET['download'] == '1'): ?>
        window.addEventListener('load', function() {
            setTimeout(downloadPDF, 600);
        });
        <?php endif; ?>
    </script>
</body>
</html>

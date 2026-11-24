<?php
/**
 * Refund Money Receipt & Requisition Settlement Slip
 * Standalone Printable View (Customer Copy + Office / Requisition Copy)
 */

$base_url = $GLOBALS['base_url'];
$refund = $refund ?? [];
$approval = $approval_request ?? [];
$disbursement = $disbursement ?? [];
$originalPayments = $originalPayments ?? [];
$requesterInfo = $requesterInfo ?? [];

// Theme & Branding Tokens
$currentProjectName  = trim($refund['project_name'] ?? '');
$currentCategoryId   = (int)($refund['category_id'] ?? 0);
$isOlympusProject    = ($currentCategoryId === 10) || (stripos($currentProjectName, 'Olympus') !== false);
$isNatureProject     = ($currentCategoryId === 9) || (stripos($currentProjectName, 'Nature') !== false);

if ($isOlympusProject) {
    $companyTitle   = "The Olympus Real Estate";
    $brandSlogan    = "REAL ESTATE";
    $companyAddress = "23/2, SEL HUQ Skypark, 4th floor, Opposite of Wonderland (Shyamoli), Dhaka";
    $companyPhone   = "01958-253302";
    $companyEmail   = "info@theolympusrealestate.com";
    $themePrimary   = "#c58b42";
    $themeDark      = "#3e3b42";
    $themeDeep      = "#2c2930";
    $themeLight     = "#d49b56";
    $themeBgLight   = "#fdfbf7";
    $themeBadgeBg   = "#f7efe4";
    $themeBorder    = "#ebd8c1";
} elseif ($isNatureProject) {
    $companyTitle   = "The Nature";
    $brandSlogan    = "A LUXURY RESORT COMMUNITY";
    $companyAddress = "Marine Drive, Cox's Bazar | Corporate Office: Land View Commercial Center, 9th Floor, 28 Gulshan North C/A, Gulshan Circle-2, Dhaka";
    $companyPhone   = "+8801958253303";
    $companyEmail   = "accounts@dpremiumhomes.com";
    $themePrimary   = "#0284c7";
    $themeDark      = "#0369a1";
    $themeDeep      = "#075985";
    $themeLight     = "#38bdf8";
    $themeBgLight   = "#f0f9ff";
    $themeBadgeBg   = "#e0f2fe";
    $themeBorder    = "#bae6fd";
} else {
    $companyTitle   = function_exists('getSetting') ? getSetting('website_title', 'The Premium Homes Ltd.') : 'The Premium Homes Ltd.';
    $brandSlogan    = "YOUR DREAM HOME AWAITS";
    $companyAddress = "Land View Commercial Center, 9th Floor, 28 Gulshan North C/A, Gulshan Circle-2, Dhaka";
    $companyPhone   = "+8801958253303";
    $companyEmail   = "accounts@dpremiumhomes.com";
    $themePrimary   = "#0b6638";
    $themeDark      = "#0b4022";
    $themeDeep      = "#0f3b25";
    $themeLight     = "#10b981";
    $themeBgLight   = "#f0fdf4";
    $themeBadgeBg   = "#ecfdf5";
    $themeBorder    = "#bbf7d0";
}

// Logo resolution
if (empty($logoSrc)) {
    $whiteLogo = function_exists('getSetting') ? (getSetting('white_logo') ?: (getSetting('dark_logo') ?: getSetting('logo'))) : null;
    $logoFileName = !empty($whiteLogo) ? $whiteLogo : '6948f14a9fb8c_1766388042.png';
    $logoFilePath = dirname(dirname(dirname(__DIR__))) . '/public/uploads/settings/' . $logoFileName;
    if (file_exists($logoFilePath)) {
        $ext = strtolower(pathinfo($logoFilePath, PATHINFO_EXTENSION));
        $mime = ($ext === 'svg') ? 'image/svg+xml' : 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext);
        $logoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoFilePath));
    } else {
        $logoSrc = $base_url . '/uploads/settings/' . $logoFileName;
    }
}

// Financial calculations
$refundAmount   = (float)($refund['refund_amount'] ?? 0);
$totalPaid      = (float)($refund['total_paid'] ?? 0);
$retentionAmount= (float)($refund['retention_amount'] ?? 0);
$retPct         = ($totalPaid > 0) ? round(($retentionAmount / $totalPaid) * 100, 2) : 0;
$amountInWords  = function_exists('amountInWords') ? amountInWords($refundAmount) : (class_exists('FormatHelper') ? FormatHelper::amountInWords($refundAmount) : '');

// Formatted Identifiers
$receiptNo = '#REF-' . str_pad($refund['id'], 6, '0', STR_PAD_LEFT);
$refundDateFormatted = !empty($refund['refunded_at']) 
    ? date('d M, Y', strtotime($refund['refunded_at'])) 
    : (!empty($disbursement['payment_date']) ? date('d M, Y', strtotime($disbursement['payment_date'])) : date('d M, Y'));
$refundTimeFormatted = !empty($refund['refunded_at']) 
    ? date('h:i A', strtotime($refund['refunded_at'])) 
    : (!empty($disbursement['created_at']) ? date('h:i A', strtotime($disbursement['created_at'])) : '');

// Payment method
$pMethod = strtoupper($disbursement['payment_method'] ?? ($refund['preferred_payment_method'] ?? 'BANK'));

// Original MR refs
$mrStrings = [];
foreach ($originalPayments as $op) {
    if (!empty($op['amount']) && (float)$op['amount'] > 0) {
        $mrNo = '#REC-' . str_pad($op['id'], 6, '0', STR_PAD_LEFT);
        $mrDate = !empty($op['payment_date']) ? date('d M Y', strtotime($op['payment_date'])) : '';
        $mrStrings[] = "{$mrNo}" . ($mrDate ? " ({$mrDate})" : "");
    }
}
$originalMrDisplay = !empty($mrStrings) ? implode(', ', $mrStrings) : 'N/A';

// Disbursed By User
$disbursedByName = !empty($disbursement['disbursed_by_name']) ? $disbursement['disbursed_by_name'] : 'Accounts Officer';
$disbursedByRole = !empty($disbursement['disbursed_by_role']) ? $disbursement['disbursed_by_role'] : 'Accounts Department';
$disbursedByEmpId= !empty($disbursement['disbursed_by_emp_id']) ? " ({$disbursement['disbursed_by_emp_id']})" : '';

// Company Bank
$coBankName = !empty($disbursement['company_bank_name']) ? $disbursement['company_bank_name'] : '';
$coAccNo    = !empty($disbursement['company_account_number']) ? $disbursement['company_account_number'] : '';
$coAccName  = !empty($disbursement['company_account_name']) ? $disbursement['company_account_name'] : '';

// Client Bank
$clientBankName = !empty($disbursement['bank_name']) ? $disbursement['bank_name'] : ($refund['client_bank_name'] ?? '—');
$clientBranch   = !empty($refund['client_bank_branch']) ? $refund['client_bank_branch'] : '—';
$clientAccName  = !empty($refund['client_bank_account_name']) ? $refund['client_bank_account_name'] : ($refund['client_name'] ?? '—');
$clientAccNo    = !empty($disbursement['bank_account_no']) ? $disbursement['bank_account_no'] : ($refund['client_bank_account_no'] ?? '—');
$clientRouting  = !empty($refund['client_bank_routing_no']) ? $refund['client_bank_routing_no'] : '—';

$chequeNo       = !empty($disbursement['cheque_no']) ? $disbursement['cheque_no'] : '';
$transRef       = !empty($disbursement['transaction_ref']) ? $disbursement['transaction_ref'] : '';

$isSettled = ($refund['status'] === 'refunded');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund Receipt - <?= $receiptNo ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
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

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
            padding: 0;
            margin: 0;
            font-size: 11px;
            line-height: 1.35;
        }

        /* Top Action Bar */
        .top-action-bar {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .bar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .bar-title {
            font-size: 14.5px;
            font-weight: 800;
            color: #0f172a;
        }

        .bar-badge {
            background: <?= $isSettled ? '#ecfdf5' : '#fffbeb' ?>;
            color: <?= $isSettled ? '#0b6638' : '#d97706' ?>;
            border: 1px solid <?= $isSettled ? '#bbf7d0' : '#fef3c7' ?>;
            padding: 3px 9px;
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
            font-size: 12px;
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

        .btn-toggle {
            background: #f8fafc;
            color: #475569;
            border-color: #cbd5e1;
        }
        .btn-toggle.active {
            background: #0f172a;
            color: #ffffff;
            border-color: #0f172a;
        }

        /* Container & A4 Layout */
        .receipt-container-wrap {
            max-width: 820px;
            margin: 18px auto 40px auto;
            padding: 0 10px;
        }

        #receipt-content {
            background: #ffffff;
            border-radius: 6px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            border: 1px solid #cbd5e1;
            overflow: hidden;
        }

        .receipt-copy {
            padding: 16px 22px 14px 22px;
            background: #ffffff;
            position: relative;
        }

        /* Header Elements */
        .copy-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }

        .brand-col {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .brand-logo-img {
            height: 42px;
            width: auto;
            max-width: 180px;
            object-fit: contain;
        }

        .brand-title {
            font-size: 15px;
            font-weight: 900;
            color: var(--theme-dark);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.15;
        }

        .brand-slogan {
            font-size: 7.5px;
            color: var(--theme-primary);
            font-weight: 800;
            letter-spacing: 1.8px;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .brand-contacts {
            font-size: 8.5px;
            color: #64748b;
            margin-top: 3px;
            line-height: 1.25;
        }

        .doc-title-col {
            text-align: right;
        }

        .doc-main-title {
            font-size: 16px;
            font-weight: 900;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .copy-type-pill {
            display: inline-block;
            background: var(--theme-deep);
            color: #ffffff;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 8.5px;
            font-weight: 800;
            text-transform: uppercase;
            margin-top: 3px;
        }

        .doc-no {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            margin-top: 3px;
            font-family: monospace;
        }

        .doc-date {
            font-size: 9.5px;
            color: #64748b;
            margin-top: 1px;
        }

        .divider-bar {
            height: 2.5px;
            background: var(--theme-primary);
            margin: 6px 0 10px 0;
            border-radius: 2px;
        }

        /* 2-Column Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 8px;
        }

        .info-box {
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 7px 10px;
            background: #fafbfc;
        }

        .info-box-title {
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .info-row {
            display: flex;
            margin-bottom: 3px;
            font-size: 10px;
            line-height: 1.3;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-lbl {
            width: 100px;
            flex-shrink: 0;
            color: #64748b;
            font-weight: 600;
            font-size: 9.5px;
        }

        .info-val {
            color: #0f172a;
            font-weight: 700;
            flex-grow: 1;
            word-break: break-word;
        }

        /* Amount Banner */
        .amount-banner {
            background: var(--theme-bg-light);
            border: 1.5px solid var(--theme-border);
            border-radius: 6px;
            padding: 8px 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .amt-left-title {
            font-size: 9.5px;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .amt-method-badge {
            background: var(--theme-deep);
            color: #ffffff;
            font-size: 8px;
            font-weight: 800;
            padding: 1px 6px;
            border-radius: 3px;
            text-transform: uppercase;
        }

        .amt-in-words {
            font-size: 9.5px;
            color: var(--theme-dark);
            margin-top: 3px;
            font-weight: 600;
        }

        .amt-big-num {
            font-size: 20px;
            font-weight: 900;
            color: #0f172a;
            text-align: right;
            font-family: monospace;
            white-space: nowrap;
        }

        /* Financial Breakdown Row */
        .breakdown-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
            margin-bottom: 8px;
        }

        .breakdown-card {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 5px 8px;
            background: #ffffff;
            text-align: center;
        }

        .breakdown-lbl {
            font-size: 8px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
        }

        .breakdown-val {
            font-size: 11px;
            font-weight: 800;
            color: #0f172a;
            margin-top: 1px;
            font-family: monospace;
        }

        /* Processing & Bank Details */
        .proc-box {
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 6px 10px;
            margin-bottom: 8px;
            background: #ffffff;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            font-size: 9.5px;
        }

        /* Approval Audit Table (Office Copy) */
        .audit-table-wrap {
            margin-bottom: 8px;
        }

        .audit-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
        }

        .audit-table th {
            background: #f8fafc;
            color: #334155;
            font-weight: 800;
            text-transform: uppercase;
            padding: 4px 6px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }

        .audit-table td {
            padding: 3.5px 6px;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .audit-step-badge {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .badge-done { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-pending { background: #fef9c3; color: #854d0e; border: 1px solid #fef08a; }

        /* Acknowledgment Statement */
        .ack-text {
            font-size: 8.5px;
            color: #475569;
            font-style: italic;
            border-left: 2.5px solid var(--theme-primary);
            padding: 3px 8px;
            background: #f8fafc;
            margin-bottom: 8px;
            line-height: 1.35;
        }

        /* Signatures Grid */
        .sig-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-top: 14px;
            padding-top: 4px;
        }

        .sig-col {
            text-align: center;
        }

        .sig-line {
            border-top: 1px dashed #94a3b8;
            margin-bottom: 4px;
        }

        .sig-role {
            font-size: 9px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
        }

        .sig-name {
            font-size: 8px;
            color: #64748b;
            margin-top: 1px;
        }

        /* Perforation Line Between Copies */
        .cut-divider {
            position: relative;
            text-align: center;
            border-top: 1.5px dashed #94a3b8;
            margin: 10px 0;
        }

        .cut-tag {
            position: absolute;
            top: -9px;
            left: 50%;
            transform: translateX(-50%);
            background: #ffffff;
            padding: 0 10px;
            color: #64748b;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        /* PRINT STYLING */
        @media print {
            body {
                background: #ffffff !important;
                font-size: 10px !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .top-action-bar {
                display: none !important;
            }

            .receipt-container-wrap {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            #receipt-content {
                box-shadow: none !important;
                border: none !important;
            }

            .receipt-copy {
                padding: 8px 12px !important;
                page-break-inside: avoid;
            }

            @page {
                size: A4 portrait;
                margin: 6mm 8mm;
            }

            /* View toggles hiding in print */
            body.print-customer-only .receipt-office-copy,
            body.print-customer-only .cut-divider {
                display: none !important;
            }

            body.print-office-only .receipt-customer-copy,
            body.print-office-only .cut-divider {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <!-- TOP FLOATING ACTION BAR -->
    <div class="top-action-bar">
        <div class="bar-left">
            <span class="bar-title">
                <i class="fa-solid fa-receipt text-success me-1"></i> Refund Money Receipt — <?= $receiptNo ?>
            </span>
            <span class="bar-badge">
                <i class="fa-solid <?= $isSettled ? 'fa-circle-check' : 'fa-hourglass-half' ?> me-1"></i>
                <?= $isSettled ? 'Refund Disbursed & Settled' : 'Approved (Awaiting Disbursement)' ?>
            </span>
        </div>
        <div class="bar-actions">
            <!-- View Mode Toggles -->
            <button type="button" class="btn-act btn-toggle active" id="btnShowBoth" onclick="setViewMode('both')">
                <i class="fa-solid fa-clone"></i> Both Copies (A4)
            </button>
            <button type="button" class="btn-act btn-toggle" id="btnShowCustomer" onclick="setViewMode('customer')">
                <i class="fa-solid fa-user"></i> Customer Copy
            </button>
            <button type="button" class="btn-act btn-toggle" id="btnShowOffice" onclick="setViewMode('office')">
                <i class="fa-solid fa-building"></i> Office Copy
            </button>
            
            <!-- Print & Back -->
            <button type="button" class="btn-act btn-primary" onclick="window.print()">
                <i class="fa-solid fa-print"></i> Print Receipt (Ctrl+P)
            </button>
            <a href="<?= $base_url ?>/client-refund/<?= $refund['id'] ?>" class="btn-act btn-outline">
                <i class="fa-solid fa-arrow-left"></i> Back to Refund Details
            </a>
        </div>
    </div>

    <div class="receipt-container-wrap">
        <div id="receipt-content">

            <!-- ============================================================== -->
            <!-- 1. CUSTOMER COPY (TOP)                                         -->
            <!-- ============================================================== -->
            <div class="receipt-copy receipt-customer-copy" id="copyCustomer">
                
                <!-- Header -->
                <div class="copy-header">
                    <div class="brand-col">
                        <?php if (!empty($logoSrc)): ?>
                            <img src="<?= $logoSrc ?>" alt="Company Logo" class="brand-logo-img">
                        <?php endif; ?>
                        <div>
                            <div class="brand-title"><?= htmlspecialchars($companyTitle) ?></div>
                            <div class="brand-slogan"><?= htmlspecialchars($brandSlogan) ?></div>
                            <div class="brand-contacts">
                                <?= htmlspecialchars($companyAddress) ?><br>
                                Phone: <?= htmlspecialchars($companyPhone) ?> | Email: <?= htmlspecialchars($companyEmail) ?>
                            </div>
                        </div>
                    </div>
                    <div class="doc-title-col">
                        <div class="doc-main-title">REFUND MONEY RECEIPT</div>
                        <span class="copy-type-pill">CUSTOMER COPY</span>
                        <div class="doc-no"><?= $receiptNo ?></div>
                        <div class="doc-date"><i class="fa-regular fa-calendar-check me-1"></i>Date: <?= $refundDateFormatted ?></div>
                    </div>
                </div>

                <div class="divider-bar"></div>

                <!-- 2-Column Info: Client & Property Details -->
                <div class="info-grid">
                    <!-- Client Information -->
                    <div class="info-box">
                        <div class="info-box-title">
                            <i class="fa-solid fa-user text-primary"></i> Client Information
                        </div>
                        <div class="info-row">
                            <span class="info-lbl">Client Name:</span>
                            <span class="info-val"><?= htmlspecialchars($refund['client_name'] ?? '—') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-lbl">Phone Number:</span>
                            <span class="info-val"><?= htmlspecialchars($refund['client_phone'] ?? '—') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-lbl">Email Address:</span>
                            <span class="info-val"><?= htmlspecialchars($refund['client_email'] ?? '—') ?></span>
                        </div>
                        <?php 
                        $clientAddr = !empty($refund['present_add_bn']) ? $refund['present_add_bn'] : (!empty($refund['permanent_address']) ? $refund['permanent_address'] : ($refund['lead_address'] ?? ''));
                        if ($clientAddr): ?>
                        <div class="info-row">
                            <span class="info-lbl">Address:</span>
                            <span class="info-val"><?= htmlspecialchars($clientAddr) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Property & Booking Information -->
                    <div class="info-box">
                        <div class="info-box-title">
                            <i class="fa-solid fa-city text-primary"></i> Property &amp; Booking Details
                        </div>
                        <div class="info-row">
                            <span class="info-lbl">Project Name:</span>
                            <span class="info-val"><?= htmlspecialchars($refund['project_name'] ?? '—') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-lbl">Unit / Flat / Shop:</span>
                            <span class="info-val">
                                <?= htmlspecialchars($refund['flat_name'] ?? '—') ?>
                                <?php if (!empty($refund['floor_no'])): ?>
                                    (Floor: <?= htmlspecialchars($refund['floor_no']) ?>)
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-lbl">Booking Reference:</span>
                            <span class="info-val">#BKG-<?= htmlspecialchars($refund['booking_id'] ?? '—') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-lbl">Original Money Receipt:</span>
                            <span class="info-val text-primary" style="font-size: 9px;"><?= htmlspecialchars($originalMrDisplay) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Settlement Amount Banner -->
                <div class="amount-banner">
                    <div>
                        <div class="amt-left-title">
                            <i class="fa-solid fa-money-bill-transfer text-success"></i>
                            <span>TOTAL REFUND AMOUNT PAID</span>
                            <span class="amt-method-badge"><?= $pMethod ?></span>
                        </div>
                        <div class="amt-in-words">
                            <strong>In Words:</strong> <?= !empty($amountInWords) ? htmlspecialchars($amountInWords) : 'Fifty Thousand Taka Only' ?>
                        </div>
                    </div>
                    <div>
                        <div class="amt-big-num">
                            Tk. <?= number_format($refundAmount, 2) ?>
                        </div>
                    </div>
                </div>

                <!-- Financial Breakdown -->
                <div class="breakdown-strip">
                    <div class="breakdown-card">
                        <div class="breakdown-lbl">Total Verified Paid</div>
                        <div class="breakdown-val">৳ <?= number_format($totalPaid, 2) ?></div>
                    </div>
                    <div class="breakdown-card" style="border-color: #fecaca; background: #fff5f5;">
                        <div class="breakdown-lbl" style="color: #b91c1c;">Company Retention (<?= $retPct ?>%)</div>
                        <div class="breakdown-val" style="color: #b91c1c;">৳ <?= number_format($retentionAmount, 2) ?></div>
                    </div>
                    <div class="breakdown-card" style="border-color: #bbf7d0; background: #f0fdf4;">
                        <div class="breakdown-lbl" style="color: #15803d;">Net Refund Disbursed</div>
                        <div class="breakdown-val" style="color: #15803d;">৳ <?= number_format($refundAmount, 2) ?></div>
                    </div>
                    <div class="breakdown-card">
                        <div class="breakdown-lbl">Settlement Status</div>
                        <div class="breakdown-val" style="color: <?= $isSettled ? '#15803d' : '#d97706' ?>; font-size: 10px;">
                            <?= $isSettled ? '✓ Disbursed & Paid' : 'Awaiting Payout' ?>
                        </div>
                    </div>
                </div>

                <!-- Payment Processing & Bank Information -->
                <div class="proc-box">
                    <div>
                        <strong style="color: #334155; font-size: 9px; text-transform: uppercase; display: block; margin-bottom: 2px;">
                            <i class="fa-solid fa-building-columns text-primary me-1"></i> Disbursed To (Customer Bank):
                        </strong>
                        <div><strong>Bank:</strong> <?= htmlspecialchars($clientBankName) ?> (<?= htmlspecialchars($clientBranch) ?>)</div>
                        <div><strong>A/C Name:</strong> <?= htmlspecialchars($clientAccName) ?></div>
                        <div><strong>A/C No:</strong> <?= htmlspecialchars($clientAccNo) ?> | <strong>Routing:</strong> <?= htmlspecialchars($clientRouting) ?></div>
                    </div>
                    <div>
                        <strong style="color: #334155; font-size: 9px; text-transform: uppercase; display: block; margin-bottom: 2px;">
                            <i class="fa-solid fa-shield-halved text-success me-1"></i> Payment Transaction Details:
                        </strong>
                        <?php if ($coBankName): ?>
                            <div><strong>Paid From:</strong> <?= htmlspecialchars($coBankName) ?> <?= $coAccNo ? "({$coAccNo})" : '' ?></div>
                        <?php endif; ?>
                        <?php if ($chequeNo): ?>
                            <div><strong>Cheque No:</strong> <?= htmlspecialchars($chequeNo) ?></div>
                        <?php endif; ?>
                        <?php if ($transRef): ?>
                            <div><strong>Transaction Ref:</strong> <?= htmlspecialchars($transRef) ?></div>
                        <?php endif; ?>
                        <div><strong>Disbursed Time:</strong> <?= $refundDateFormatted ?><?= $refundTimeFormatted ? " • {$refundTimeFormatted}" : '' ?></div>
                        <div><strong>Disbursed By:</strong> <?= htmlspecialchars($disbursedByName) ?><?= htmlspecialchars($disbursedByEmpId) ?></div>
                    </div>
                </div>

                <!-- Client Acknowledgment -->
                <div class="ack-text">
                    <strong>Client Acknowledgment:</strong> I hereby acknowledge the receipt of the refund settlement amount of <strong>Tk. <?= number_format($refundAmount, 2) ?></strong> (<?= !empty($amountInWords) ? htmlspecialchars($amountInWords) : '' ?>) as full and final settlement of all claims regarding the aforementioned property booking.
                </div>

                <!-- Signatures -->
                <div class="sig-grid">
                    <div class="sig-col">
                        <div class="sig-line"></div>
                        <div class="sig-role">Received By (Client)</div>
                        <div class="sig-name">Signature &amp; Date</div>
                    </div>
                    <div class="sig-col">
                        <div class="sig-line"></div>
                        <div class="sig-role">Prepared By (Sales)</div>
                        <div class="sig-name"><?= htmlspecialchars($refund['requested_by_name'] ?? 'Sales Executive') ?></div>
                    </div>
                    <div class="sig-col">
                        <div class="sig-line"></div>
                        <div class="sig-role">Disbursed By (Accounts)</div>
                        <div class="sig-name"><?= htmlspecialchars($disbursedByName) ?></div>
                    </div>
                    <div class="sig-col">
                        <div class="sig-line"></div>
                        <div class="sig-role">Authorized Signature</div>
                        <div class="sig-name">Head of Operations / Management</div>
                    </div>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- PERFORATION / CUT HERE LINE                                    -->
            <!-- ============================================================== -->
            <div class="cut-divider">
                <span class="cut-tag">
                    <i class="fa-solid fa-scissors"></i> CUT HERE <i class="fa-solid fa-scissors"></i>
                </span>
            </div>

            <!-- ============================================================== -->
            <!-- 2. OFFICE / REQUISITION COPY (BOTTOM)                          -->
            <!-- ============================================================== -->
            <div class="receipt-copy receipt-office-copy" id="copyOffice">
                
                <!-- Header -->
                <div class="copy-header">
                    <div class="brand-col">
                        <?php if (!empty($logoSrc)): ?>
                            <img src="<?= $logoSrc ?>" alt="Company Logo" class="brand-logo-img">
                        <?php endif; ?>
                        <div>
                            <div class="brand-title"><?= htmlspecialchars($companyTitle) ?></div>
                            <div class="brand-slogan"><?= htmlspecialchars($brandSlogan) ?></div>
                            <div class="brand-contacts">
                                <?= htmlspecialchars($companyAddress) ?><br>
                                Phone: <?= htmlspecialchars($companyPhone) ?> | Email: <?= htmlspecialchars($companyEmail) ?>
                            </div>
                        </div>
                    </div>
                    <div class="doc-title-col">
                        <div class="doc-main-title">REFUND REQUISITION &amp; SETTLEMENT SLIP</div>
                        <span class="copy-type-pill" style="background: #1e293b;">OFFICE / COMPANY COPY</span>
                        <div class="doc-no"><?= $receiptNo ?></div>
                        <div class="doc-date">
                            Requisition Date: <?= !empty($refund['created_at']) ? date('d M, Y', strtotime($refund['created_at'])) : $refundDateFormatted ?> 
                            | Expected: <strong class="text-danger">Urgent</strong>
                        </div>
                    </div>
                </div>

                <div class="divider-bar" style="background: #0f172a;"></div>

                <!-- Requisition Details Grid matching Image 1 -->
                <div class="info-grid">
                    <div class="info-box">
                        <div class="info-box-title">
                            <i class="fa-solid fa-file-signature text-primary"></i> Requisition &amp; Employee Details
                        </div>
                        <div class="info-row">
                            <span class="info-lbl">Requested By:</span>
                            <span class="info-val">
                                <?= htmlspecialchars($refund['requested_by_name'] ?? '—') ?>
                                <?= !empty($requesterInfo['employee_id']) ? ' (' . htmlspecialchars($requesterInfo['employee_id']) . ')' : '' ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-lbl">Designation / Role:</span>
                            <span class="info-val"><?= htmlspecialchars($refund['requested_by_role'] ?? 'Sales Executive') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-lbl">Department:</span>
                            <span class="info-val"><?= htmlspecialchars($requesterInfo['department_name'] ?? 'Sales') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-lbl">Requisition No:</span>
                            <span class="info-val font-monospace"><?= htmlspecialchars($refund['cashout_request_id'] ? '#' . $refund['cashout_request_id'] : $receiptNo) ?></span>
                        </div>
                    </div>

                    <div class="info-box">
                        <div class="info-box-title">
                            <i class="fa-solid fa-building-user text-primary"></i> Project &amp; Customer Details
                        </div>
                        <div class="info-row">
                            <span class="info-lbl">Project Name:</span>
                            <span class="info-val"><?= htmlspecialchars($refund['project_name'] ?? '—') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-lbl">Shop / Flat / Floor:</span>
                            <span class="info-val">
                                <?= htmlspecialchars($refund['flat_name'] ?? '—') ?>
                                <?= !empty($refund['floor_no']) ? ' (Floor: ' . htmlspecialchars($refund['floor_no']) . ')' : '' ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-lbl">Customer:</span>
                            <span class="info-val"><?= htmlspecialchars($refund['client_name'] ?? '—') ?> (<?= htmlspecialchars($refund['client_phone'] ?? '—') ?>)</span>
                        </div>
                        <div class="info-row">
                            <span class="info-lbl">Original MR Ref:</span>
                            <span class="info-val text-primary" style="font-size: 9px;"><?= htmlspecialchars($originalMrDisplay) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Refund Details Table matching Manual Requisition Slip (Image 1) -->
                <div class="proc-box" style="margin-bottom: 6px;">
                    <div>
                        <strong style="color: #0f172a; font-size: 9.5px; text-transform: uppercase; display: block; margin-bottom: 3px;">
                            <i class="fa-solid fa-list-check text-primary me-1"></i> Refund Itemization &amp; Customer Bank
                        </strong>
                        <div style="font-size: 9.5px; line-height: 1.45;">
                            <div><strong>1. Refund Details:</strong> <?= htmlspecialchars($refund['client_name'] ?? '—') ?> | <?= htmlspecialchars($refund['client_phone'] ?? '—') ?></div>
                            <div><strong>Original MR:</strong> <?= htmlspecialchars($originalMrDisplay) ?></div>
                            <div><strong>Bank Details:</strong> <?= htmlspecialchars($clientBankName) ?>, Branch: <?= htmlspecialchars($clientBranch) ?></div>
                            <div><strong>A/C Name:</strong> <?= htmlspecialchars($clientAccName) ?> | <strong>A/C No:</strong> <?= htmlspecialchars($clientAccNo) ?> | <strong>Routing:</strong> <?= htmlspecialchars($clientRouting) ?></div>
                        </div>
                    </div>
                    <div>
                        <strong style="color: #0f172a; font-size: 9.5px; text-transform: uppercase; display: block; margin-bottom: 3px;">
                            <i class="fa-solid fa-calculator text-success me-1"></i> Purpose, Reason &amp; Amount
                        </strong>
                        <div style="font-size: 9.5px; line-height: 1.45;">
                            <div><strong>Amount:</strong> <span class="font-monospace fw-bold" style="font-size: 11px;">Tk. <?= number_format($refundAmount, 2) ?></span> (In words: <?= !empty($amountInWords) ? htmlspecialchars($amountInWords) : '' ?>)</div>
                            <div><strong>Purpose / Reason:</strong> <?= htmlspecialchars($refund['reason'] ?? 'Family issue / Client requested refund') ?></div>
                            <div><strong>Registration:</strong> <?= !empty($refund['is_registration_completed']) ? 'Completed' : 'Not Completed' ?></div>
                            <div><strong>Payment Payout:</strong> <?= $pMethod ?> via <?= htmlspecialchars($coBankName ?: 'Official Account') ?><?= $chequeNo ? " (Chq: {$chequeNo})" : '' ?><?= $transRef ? " (Ref: {$transRef})" : '' ?></div>
                        </div>
                    </div>
                </div>

                <!-- Multi-Level Approval Audit Trail (Shober Approval with Name, Role & Timestamps) -->
                <div class="audit-table-wrap">
                    <div style="font-size: 9px; font-weight: 800; text-transform: uppercase; color: #334155; margin-bottom: 3px;">
                        <i class="fa-solid fa-clipboard-check text-success me-1"></i> Official Multi-Level Approval &amp; Verification Audit Log:
                    </div>
                    <table class="audit-table">
                        <thead>
                            <tr>
                                <th style="width: 14%;">Approval Stage</th>
                                <th style="width: 22%;">Officer Name &amp; Designation</th>
                                <th style="width: 16%;">Action Status</th>
                                <th style="width: 20%;">Date &amp; Timestamp</th>
                                <th style="width: 28%;">Remarks / Approval Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 1. Request Initiator -->
                            <tr>
                                <td><strong>1. Initiator</strong></td>
                                <td>
                                    <?= htmlspecialchars($refund['requested_by_name'] ?? '—') ?>
                                    <div style="font-size: 7.5px; color: #64748b;"><?= htmlspecialchars($refund['requested_by_role'] ?? 'Sales Executive') ?></div>
                                </td>
                                <td><span class="audit-step-badge badge-done">Submitted</span></td>
                                <td><?= !empty($refund['created_at']) ? date('d M Y, h:i A', strtotime($refund['created_at'])) : '—' ?></td>
                                <td>Refund request submitted from Sales</td>
                            </tr>

                            <!-- 2. Sales Manager -->
                            <tr>
                                <td><strong>2. Sales Manager</strong></td>
                                <td>
                                    <?= htmlspecialchars($approval['sales_head_name'] ?? 'Sales Team Head') ?>
                                    <div style="font-size: 7.5px; color: #64748b;"><?= htmlspecialchars($approval['sales_head_role'] ?? 'Sales Manager') ?></div>
                                </td>
                                <td>
                                    <?php $shStatus = strtolower($approval['sales_head_status'] ?? 'pending'); ?>
                                    <span class="audit-step-badge <?= $shStatus === 'approved' ? 'badge-done' : 'badge-pending' ?>">
                                        <?= ucfirst($shStatus) ?>
                                    </span>
                                </td>
                                <td><?= !empty($approval['sales_head_at']) ? date('d M Y, h:i A', strtotime($approval['sales_head_at'])) : '—' ?></td>
                                <td><?= htmlspecialchars($approval['sales_head_note'] ?? 'Primary sales verification completed') ?></td>
                            </tr>

                            <!-- 3. Quality Control / Audit -->
                            <tr>
                                <td><strong>3. Internal Audit (QC)</strong></td>
                                <td>
                                    <?= htmlspecialchars($approval['qc_name'] ?? 'QC Officer') ?>
                                    <div style="font-size: 7.5px; color: #64748b;"><?= htmlspecialchars($approval['qc_role'] ?? 'Quality Control') ?></div>
                                </td>
                                <td>
                                    <?php $qcStatus = strtolower($approval['qc_status'] ?? 'pending'); ?>
                                    <span class="audit-step-badge <?= $qcStatus === 'approved' ? 'badge-done' : 'badge-pending' ?>">
                                        <?= ucfirst($qcStatus) ?>
                                    </span>
                                </td>
                                <td><?= !empty($approval['qc_at']) ? date('d M Y, h:i A', strtotime($approval['qc_at'])) : '—' ?></td>
                                <td><?= htmlspecialchars($approval['qc_note'] ?? 'Client documents & payments verified') ?></td>
                            </tr>

                            <!-- 4. Operation Head -->
                            <tr>
                                <td><strong>4. Operation Head</strong></td>
                                <td>
                                    <?= htmlspecialchars($approval['op_head_name'] ?? 'Operation Head') ?>
                                    <div style="font-size: 7.5px; color: #64748b;"><?= htmlspecialchars($approval['op_head_role'] ?? 'Admin & Operation Head') ?></div>
                                </td>
                                <td>
                                    <?php $ohStatus = strtolower($approval['op_head_status'] ?? 'pending'); ?>
                                    <span class="audit-step-badge <?= $ohStatus === 'approved' ? 'badge-done' : 'badge-pending' ?>">
                                        <?= ucfirst($ohStatus) ?>
                                    </span>
                                </td>
                                <td><?= !empty($approval['op_head_at']) ? date('d M Y, h:i A', strtotime($approval['op_head_at'])) : '—' ?></td>
                                <td><?= htmlspecialchars($approval['op_head_note'] ?? 'Operational clearance verified') ?></td>
                            </tr>

                            <!-- 5. Accounts Head -->
                            <tr>
                                <td><strong>5. Accounts Head</strong></td>
                                <td>
                                    <?= htmlspecialchars($approval['accounts_head_name'] ?? 'Head of Accounts') ?>
                                    <div style="font-size: 7.5px; color: #64748b;"><?= htmlspecialchars($approval['accounts_head_role'] ?? 'Accounts Department') ?></div>
                                </td>
                                <td>
                                    <?php $ahStatus = strtolower($approval['accounts_head_status'] ?? 'pending'); ?>
                                    <span class="audit-step-badge <?= $ahStatus === 'approved' ? 'badge-done' : 'badge-pending' ?>">
                                        <?= ucfirst($ahStatus) ?>
                                    </span>
                                </td>
                                <td><?= !empty($approval['accounts_head_at']) ? date('d M Y, h:i A', strtotime($approval['accounts_head_at'])) : '—' ?></td>
                                <td><?= htmlspecialchars($approval['accounts_head_note'] ?? 'Accounts retention & financial audit verified') ?></td>
                            </tr>

                            <!-- 6. Management / Super Admin -->
                            <tr>
                                <td><strong>6. Management Approval</strong></td>
                                <td>
                                    <?= htmlspecialchars($approval['mgmt_name'] ?? ($refund['approved_by_name'] ?? 'Management')) ?>
                                    <div style="font-size: 7.5px; color: #64748b;"><?= htmlspecialchars($approval['mgmt_role'] ?? ($refund['approved_by_role'] ?? 'Super Admin')) ?></div>
                                </td>
                                <td>
                                    <?php $mgStatus = !empty($refund['approved_at']) || strtolower($approval['status'] ?? '') === 'approved' ? 'approved' : 'pending'; ?>
                                    <span class="audit-step-badge <?= $mgStatus === 'approved' ? 'badge-done' : 'badge-pending' ?>">
                                        <?= ucfirst($mgStatus) ?>
                                    </span>
                                </td>
                                <td><?= !empty($refund['approved_at']) ? date('d M Y, h:i A', strtotime($refund['approved_at'])) : (!empty($approval['approved_at']) ? date('d M Y, h:i A', strtotime($approval['approved_at'])) : '—') ?></td>
                                <td><?= htmlspecialchars($approval['approval_note'] ?? 'Final sanction approved for disbursement') ?></td>
                            </tr>

                            <!-- 7. Accounts Disbursement -->
                            <tr>
                                <td><strong>7. Accounts Payout</strong></td>
                                <td>
                                    <?= htmlspecialchars($disbursedByName) ?>
                                    <div style="font-size: 7.5px; color: #64748b;"><?= htmlspecialchars($disbursedByRole) ?></div>
                                </td>
                                <td>
                                    <span class="audit-step-badge <?= $isSettled ? 'badge-done' : 'badge-pending' ?>">
                                        <?= $isSettled ? 'Disbursed' : 'Awaiting' ?>
                                    </span>
                                </td>
                                <td><?= $refundDateFormatted ?><?= $refundTimeFormatted ? " • {$refundTimeFormatted}" : '' ?></td>
                                <td>Disbursed Tk. <?= number_format($refundAmount, 2) ?> via <?= $pMethod ?><?= $chequeNo ? " [Chq: {$chequeNo}]" : '' ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Office Signatures matching Requisition Slip (Image 1) -->
                <div class="sig-grid">
                    <div class="sig-col">
                        <div class="sig-line"></div>
                        <div class="sig-role">Requested By</div>
                        <div class="sig-name"><?= htmlspecialchars($refund['requested_by_name'] ?? 'Sales Executive') ?> (Sales)</div>
                    </div>
                    <div class="sig-col">
                        <div class="sig-line"></div>
                        <div class="sig-role">Received By</div>
                        <div class="sig-name">Client Signature &amp; Date</div>
                    </div>
                    <div class="sig-col">
                        <div class="sig-line"></div>
                        <div class="sig-role">F &amp; A Department</div>
                        <div class="sig-name"><?= htmlspecialchars($disbursedByName) ?> (Finance &amp; Accounts)</div>
                    </div>
                    <div class="sig-col">
                        <div class="sig-line"></div>
                        <div class="sig-role">Head of Operations</div>
                        <div class="sig-name">Management Authorization</div>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- Toggle View Mode Script -->
    <script>
    function setViewMode(mode) {
        document.getElementById('btnShowBoth').classList.remove('active');
        document.getElementById('btnShowCustomer').classList.remove('active');
        document.getElementById('btnShowOffice').classList.remove('active');

        const custCopy = document.getElementById('copyCustomer');
        const offCopy = document.getElementById('copyOffice');
        const divider = document.querySelector('.cut-divider');

        document.body.classList.remove('print-customer-only', 'print-office-only');

        if (mode === 'customer') {
            document.getElementById('btnShowCustomer').classList.add('active');
            custCopy.style.display = 'block';
            offCopy.style.display = 'none';
            if (divider) divider.style.display = 'none';
            document.body.classList.add('print-customer-only');
        } else if (mode === 'office') {
            document.getElementById('btnShowOffice').classList.add('active');
            custCopy.style.display = 'none';
            offCopy.style.display = 'block';
            if (divider) divider.style.display = 'none';
            document.body.classList.add('print-office-only');
        } else { // both
            document.getElementById('btnShowBoth').classList.add('active');
            custCopy.style.display = 'block';
            offCopy.style.display = 'block';
            if (divider) divider.style.display = 'block';
        }
    }
    </script>
</body>
</html>

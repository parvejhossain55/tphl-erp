<?php
// Standalone Public Verification Page (No ERP layout/auth required)
$disable_layout = true;

global $db;
if (!$db && isset($this) && isset($this->db)) {
    $db = $this->db;
}

// Site Settings
$whiteLogo = function_exists('getSetting') ? getSetting('white_logo') : null;
$logoUrl = ($whiteLogo && function_exists('getImage')) ? getImage('settings', $whiteLogo) : '';
if (empty($logoUrl) || strpos($logoUrl, 'avatar.png') !== false) {
    $logoUrl = $GLOBALS['base_url'] . '/public/uploads/settings/' . ($whiteLogo ?: '6948f14a9fb8c_1766388042.png');
}

$companyTitle = function_exists('getSetting') ? getSetting('website_title', 'The Premium Homes Ltd.') : 'The Premium Homes Ltd.';
$companyPhone = function_exists('getSetting') ? getSetting('phone', '+8801958253300') : '+8801958253300';
$companyEmail = function_exists('getSetting') ? getSetting('email', 'info@thepremiumhomes.com') : 'info@thepremiumhomes.com';
$companyAddress = "Land View Commercial Center, 9th Floor, 28 Gulshan North C/A, Gulshan Circle-2, Dhaka";

$hasPayment = !empty($payment) && is_array($payment);
$isVerified = $hasPayment && isset($payment['verification_status']) && strtolower(trim($payment['verification_status'])) === 'verified';
$isPending  = $hasPayment && !$isVerified && strtolower(trim($payment['verification_status'] ?? '')) !== 'rejected';
$isRejected = $hasPayment && strtolower(trim($payment['verification_status'] ?? '')) === 'rejected';

$receiptNo = $hasPayment ? '#REC-' . str_pad($payment['id'] ?? '0', 6, '0', STR_PAD_LEFT) : 'N/A';
$paymentDateFormatted = $hasPayment ? date('d M, Y', strtotime($payment['payment_date'] ?? 'now')) : 'N/A';
$amountPaid = $hasPayment ? (float)($payment['amount'] ?? 0) : 0;
$amountInWords = function_exists('amountInWords') ? amountInWords($amountPaid) : (class_exists('FormatHelper') ? FormatHelper::amountInWords($amountPaid) : '');

// Payment Type Label (e.g. 23rd Installment / Booking Money / etc.)
$paymentTypeRaw = strtolower($payment['payment_type'] ?? '');
if (!empty($payment['is_emi_discount']) && $payment['is_emi_discount'] == 1) {
    $paymentTypeLabel = 'Apply Previous Discount';
} elseif ($paymentTypeRaw === 'emi') {
    $paymentTypeLabel = !empty($payment['installment_label']) ? $payment['installment_label'] : 'EMI Installment';
} elseif ($paymentTypeRaw === 'booking_money') {
    $paymentTypeLabel = 'Booking Money';
} elseif ($paymentTypeRaw === 'registration') {
    $paymentTypeLabel = 'Registration Money';
} elseif ($paymentTypeRaw === 'development_charge') {
    $paymentTypeLabel = 'Development Charge';
} elseif ($paymentTypeRaw === 'others') {
    $otherPurpose = !empty($payment['other_purpose']) ? ucwords($payment['other_purpose']) : 'Extra Charges';
    $paymentTypeLabel = 'Others (' . $otherPurpose . ')';
} else {
    $paymentTypeLabel = ucwords(str_replace('_', ' ', $payment['payment_type'] ?? 'Payment'));
}

// Client details
$clientName = !empty($clientDetails['client_name']) ? $clientDetails['client_name'] : ($payment['client_name'] ?? 'N/A');
if (!empty($clientDetails['2nd_lead_owner_name'])) {
    $clientName .= ' & ' . trim($clientDetails['2nd_lead_owner_name']);
}

$rawPaymentId = (int)($payment['id'] ?? 0);
$verificationToken = $_GET['token'] ?? substr(hash('sha256', $rawPaymentId . '_' . ($payment['created_at'] ?? 'tphl') . '_TPHL_VERIFY_KEY_2026'), 0, 12);

$clientPhone = !empty($clientDetails['phone']) ? $clientDetails['phone'] : (!empty($clientDetails['phone_number']) ? $clientDetails['phone_number'] : (!empty($payment['phone']) ? $payment['phone'] : 'N/A'));
if (!empty($clientDetails['2nd_owner_phone'])) {
    $clientPhone .= ', ' . trim($clientDetails['2nd_owner_phone']);
}
if ($clientPhone !== 'N/A' && strlen($clientPhone) === 10 && substr($clientPhone, 0, 1) === '1') {
    $clientPhone = '0' . $clientPhone;
}

// Property details
$projectName = !empty($clientDetails['project_name']) ? $clientDetails['project_name'] : ($payment['project_name'] ?? 'N/A');
$unitName = !empty($clientDetails['unit_name']) ? trim(preg_replace('/^unit\s+/i', '', $clientDetails['unit_name'])) : (!empty($payment['unit_name']) ? trim(preg_replace('/^unit\s+/i', '', $payment['unit_name'])) : 'N/A');
$floorNo = !empty($clientDetails['flat_no']) ? trim(preg_replace('/^floor\s+/i', '', $clientDetails['flat_no'])) : (!empty($payment['flat_no']) ? trim(preg_replace('/^floor\s+/i', '', $payment['flat_no'])) : 'N/A');

// Verification Details
$vRoleId = (int)($payment['verified_by_role_id'] ?? 0);
$vRoleName = trim($payment['verified_by_role_name'] ?? '');
$vFullName = trim($payment['verified_by_name'] ?? '');
$vEmpId = trim($payment['verified_by_employee_id'] ?? '');

// If verifier details not populated, look up from DB
if ($isVerified && empty($vRoleName) && !empty($payment['verified_by']) && isset($db) && $db) {
    try {
        $vStmt = $db->prepare("
            SELECT u.id, u.full_name, u.role_id, r.name as role_name,
                   COALESCE(NULLIF(e1.employee_id, ''), NULLIF(e2.employee_id, ''), NULLIF(u.employee_id, '')) as employee_id
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN employees e1 ON e1.user_id = u.id
            LEFT JOIN employees e2 ON u.employee_id = e2.id
            WHERE u.id = ?
            LIMIT 1
        ");
        if ($vStmt) {
            $vStmt->bind_param("i", $payment['verified_by']);
            $vStmt->execute();
            $vRes = $vStmt->get_result();
            if ($vRow = $vRes->fetch_assoc()) {
                $vRoleId = (int)($vRow['role_id'] ?? 0);
                $vRoleName = trim($vRow['role_name'] ?? '');
                $vFullName = trim($vRow['full_name'] ?? '');
                $vEmpId = trim($vRow['employee_id'] ?? '');
            }
            $vStmt->close();
        }
    } catch (Exception $e) {}
}

if ($isVerified) {
    $isAccountsRole = in_array($vRoleId, [6, 30, 36]) || (stripos($vRoleName, 'account') !== false);
    $isManagementRole = in_array($vRoleId, [1, 3]) || (stripos($vFullName, 'management') !== false) || (stripos($vRoleName, 'admin') !== false);

    if ($isAccountsRole) {
        $verifiedByName = !empty($vFullName) ? $vFullName : 'Accounts Officer';
        if (!empty($vEmpId)) {
            $verifiedByName .= ' (' . $vEmpId . ')';
        }
    } elseif ($isManagementRole || strtolower($vFullName) === 'management') {
        $verifiedByName = 'Accounts';
    } else {
        $verifiedByName = !empty($vFullName) ? $vFullName : 'Accounts';
        if (!empty($vEmpId)) {
            $verifiedByName .= ' (' . $vEmpId . ')';
        }
    }
} else {
    $verifiedByName = 'Pending Verification';
}

$verifiedDateFormatted = 'Awaiting Verification';
if ($isVerified) {
    if (!empty($payment['verified_at'])) {
        $verifiedDateFormatted = date('d M, Y • h:i A', strtotime($payment['verified_at']));
    } elseif (!empty($payment['updated_at']) && $payment['updated_at'] !== '0000-00-00 00:00:00') {
        $verifiedDateFormatted = date('d M, Y • h:i A', strtotime($payment['updated_at']));
    } else {
        $verifiedDateFormatted = date('d M, Y • h:i A');
    }
}

// Payment Method & Reference
$paymentMethod = strtoupper($payment['payment_method'] ?? 'CASH');
$refNumber = !empty($payment['transaction_ref']) ? trim($payment['transaction_ref']) : (!empty($payment['cheque_no']) ? trim($payment['cheque_no']) : 'N/A');

if ($paymentMethod === 'CASH') {
    $bankDisplay = '';
} elseif ($paymentMethod === 'ONLINE') {
    $onlineType = !empty($payment['online_type']) ? strtoupper(trim($payment['online_type'])) : 'ONLINE GATEWAY';
    $companyBank = !empty($payment['company_bank_name']) ? trim($payment['company_bank_name']) : '';
    $bankDisplay = $companyBank ? ($onlineType . ' &bull; Receiver: ' . $companyBank) : $onlineType;
} else {
    $bankDisplay = !empty($payment['company_bank_name']) ? $payment['company_bank_name'] : (!empty($payment['bank_name']) ? $payment['bank_name'] : 'Official Accounts');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Official Receipt Verification - <?= htmlspecialchars($receiptNo) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --primary: #0b6638;
            --primary-dark: #064c2a;
            --primary-light: #ecfdf5;
            --primary-border: #a7f3d0;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --slate-900: #0f172a;
            --slate-800: #1e293b;
            --slate-700: #334155;
            --slate-600: #475569;
            --slate-500: #64748b;
            --slate-200: #e2e8f0;
            --slate-100: #f1f5f9;
            --slate-50: #f8fafc;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 300px, #f1f5f9 100%);
            color: var(--slate-900);
            min-height: 100vh;
            padding: 24px 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            -webkit-font-smoothing: antialiased;
        }

        .verify-wrapper {
            width: 100%;
            max-width: 580px;
            margin: 0 auto;
        }

        /* Top Brand Bar */
        .brand-bar {
            text-align: center;
            margin-bottom: 20px;
        }

        .brand-logo-img {
            max-height: 48px;
            max-width: 220px;
            object-fit: contain;
            margin-bottom: 6px;
        }

        .brand-title {
            font-size: 16px;
            font-weight: 800;
            color: var(--primary-dark);
            letter-spacing: -0.2px;
        }

        .brand-sub {
            font-size: 11px;
            color: var(--slate-500);
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* Main Verification Card */
        .verify-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 40px -10px rgba(11, 102, 56, 0.12), 0 4px 12px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--slate-200);
            overflow: hidden;
            position: relative;
        }

        /* Status Banner */
        .status-hero {
            padding: 26px 20px 22px 20px;
            text-align: center;
            position: relative;
        }

        .hero-verified {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border-bottom: 1.5px solid var(--primary-border);
        }

        .hero-pending {
            background: linear-gradient(135deg, #fffdf5 0%, #fef3c7 100%);
            border-bottom: 1.5px solid #fde68a;
        }

        .hero-rejected {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border-bottom: 1.5px solid #fecaca;
        }

        .status-icon-bubble {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            margin: 0 auto 12px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
            animation: pulse-ring 2.5s infinite;
        }

        @keyframes pulse-ring {
            0% { transform: scale(1); }
            50% { transform: scale(1.04); }
            100% { transform: scale(1); }
        }

        .bubble-verified {
            background: #10b981;
            color: #ffffff;
            border: 3px solid #ffffff;
        }

        .bubble-pending {
            background: #f59e0b;
            color: #ffffff;
            border: 3px solid #ffffff;
        }

        .bubble-rejected {
            background: #ef4444;
            color: #ffffff;
            border: 3px solid #ffffff;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .pill-verified {
            background: #047857;
            color: #ffffff;
        }

        .pill-pending {
            background: #b45309;
            color: #ffffff;
        }

        .pill-rejected {
            background: #b91c1c;
            color: #ffffff;
        }

        .status-hero-title {
            font-size: 20px;
            font-weight: 900;
            letter-spacing: -0.3px;
            margin-bottom: 4px;
        }

        .title-verified { color: #065f46; }
        .title-pending  { color: #92400e; }
        .title-rejected { color: #991b1b; }

        .status-hero-desc {
            font-size: 12.5px;
            color: var(--slate-600);
            max-width: 420px;
            margin: 0 auto;
            line-height: 1.4;
        }

        /* Card Body */
        .card-body-content {
            padding: 24px 22px;
        }

        /* Amount Display Box */
        .amount-display-box {
            background: #f8fafc;
            border: 1.5px solid var(--slate-200);
            border-radius: 14px;
            padding: 16px 18px;
            text-align: center;
            margin-bottom: 22px;
        }

        .amount-label {
            font-size: 11px;
            font-weight: 800;
            color: var(--slate-500);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .amount-value {
            font-size: 30px;
            font-weight: 900;
            color: var(--primary);
            letter-spacing: -0.5px;
        }

        .amount-words {
            font-size: 12px;
            font-weight: 600;
            color: var(--slate-600);
            margin-top: 6px;
            padding-top: 6px;
            border-top: 1px dashed var(--slate-200);
        }

        /* Information Grid */
        .info-section-title {
            font-size: 11px;
            font-weight: 800;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }

        .info-cell {
            background: var(--slate-50);
            border: 1px solid var(--slate-200);
            border-radius: 10px;
            padding: 10px 14px;
            display: flex;
            flex-direction: column;
        }

        .cell-full {
            grid-column: span 2;
        }

        .cell-label {
            font-size: 10.5px;
            font-weight: 700;
            color: var(--slate-500);
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 3px;
        }

        .cell-value {
            font-size: 13.5px;
            font-weight: 800;
            color: var(--slate-900);
            word-break: break-word;
        }

        .cell-tag {
            display: inline-block;
            background: #dcfce7;
            color: #065f46;
            font-size: 11px;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 4px;
            margin-top: 2px;
            width: fit-content;
        }

        /* Verification Authority Box */
        .authority-box {
            background: #f0fbf5;
            border: 1px solid var(--primary-border);
            border-radius: 12px;
            padding: 14px 16px;
            margin-top: 10px;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .authority-icon {
            font-size: 26px;
            color: var(--primary);
            flex-shrink: 0;
        }

        .authority-content {
            flex: 1;
        }

        .auth-label {
            font-size: 10.5px;
            font-weight: 700;
            color: #047857;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .auth-val {
            font-size: 13px;
            font-weight: 800;
            color: var(--slate-900);
            margin-top: 1px;
        }

        .auth-date {
            font-size: 11px;
            color: var(--slate-600);
            font-weight: 600;
            margin-top: 1px;
        }

        /* Action Buttons */
        .action-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn-full {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: 1px solid transparent;
        }

        .btn-primary-action {
            background-color: var(--primary);
            color: #ffffff;
        }
        .btn-primary-action:hover {
            background-color: var(--primary-dark);
            color: #ffffff;
        }

        .btn-secondary-action {
            background-color: #ffffff;
            color: var(--slate-700);
            border-color: var(--slate-200);
        }
        .btn-secondary-action:hover {
            background-color: var(--slate-50);
        }

        /* Footer */
        .footer-note {
            text-align: center;
            font-size: 11px;
            color: var(--slate-500);
            margin-top: 20px;
            line-height: 1.5;
        }

        .security-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 4px;
        }

        @media (max-width: 480px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            .cell-full {
                grid-column: span 1;
            }
            .amount-value {
                font-size: 26px;
            }
        }
    </style>
</head>
<body>

    <div class="verify-wrapper">

        <!-- Top Brand Bar -->
        <div class="brand-bar">
            <?php if (!empty($logoUrl)): ?>
                <img src="<?= htmlspecialchars($logoUrl) ?>" alt="<?= htmlspecialchars($companyTitle) ?>" class="brand-logo-img">
            <?php endif; ?>
            <div class="brand-title"><?= htmlspecialchars($companyTitle) ?></div>
            <div class="brand-sub">Central ERP Digital Verification Portal</div>
        </div>

        <?php if ($hasPayment): ?>
            <!-- Verification Card -->
            <div class="verify-card">

                <!-- Hero Banner -->
                <div class="status-hero <?= $isVerified ? 'hero-verified' : ($isRejected ? 'hero-rejected' : 'hero-pending') ?>">
                    <div class="status-icon-bubble <?= $isVerified ? 'bubble-verified' : ($isRejected ? 'bubble-rejected' : 'bubble-pending') ?>">
                        <i class="fa-solid <?= $isVerified ? 'fa-check' : ($isRejected ? 'fa-xmark' : 'fa-hourglass-half') ?>"></i>
                    </div>

                    <div class="status-pill <?= $isVerified ? 'pill-verified' : ($isRejected ? 'pill-rejected' : 'pill-pending') ?>">
                        <i class="fa-solid fa-shield-halved"></i>
                        <?= $isVerified ? 'Official Verified' : ($isRejected ? 'Payment Rejected' : 'Subject to Verification') ?>
                    </div>

                    <h1 class="status-hero-title <?= $isVerified ? 'title-verified' : ($isRejected ? 'title-rejected' : 'title-pending') ?>">
                        <?= $isVerified ? 'Payment Authenticated' : ($isRejected ? 'Payment Unconfirmed' : 'Verification Under Review') ?>
                    </h1>
                    <p class="status-hero-desc">
                        <?= $isVerified 
                            ? 'This payment record has been officially verified and settled in the system records of The Premium Homes Ltd.' 
                            : ($isRejected ? 'This payment entry has been marked as rejected or cancelled.' : 'This payment has been recorded and is currently awaiting official bank/accounts verification.') ?>
                    </p>
                </div>

                <!-- Body Details -->
                <div class="card-body-content">

                    <!-- Amount Box -->
                    <div class="amount-display-box">
                        <div class="amount-label">Verified Total Amount</div>
                        <div class="amount-value">TK. <?= number_format($amountPaid, 2) ?></div>
                        <?php if (!empty($amountInWords)): ?>
                            <div class="amount-words">In Words: <?= htmlspecialchars($amountInWords) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Payment & Property Info -->
                    <div class="info-section-title">
                        <i class="fa-solid fa-file-invoice-dollar"></i> Transaction Details
                    </div>
                    <div class="info-grid">
                        <div class="info-cell">
                            <span class="cell-label">Receipt Number</span>
                            <span class="cell-value" style="color:var(--primary); font-weight:900;"><?= htmlspecialchars($receiptNo) ?></span>
                        </div>
                        <div class="info-cell">
                            <span class="cell-label">Payment Date</span>
                            <span class="cell-value"><?= htmlspecialchars($paymentDateFormatted) ?></span>
                        </div>
                        <div class="info-cell">
                            <span class="cell-label">Payment Purpose</span>
                            <span class="cell-value"><span class="cell-tag"><?= htmlspecialchars($paymentTypeLabel) ?></span></span>
                        </div>
                        <div class="info-cell">
                            <span class="cell-label">Payment Method</span>
                            <span class="cell-value"><?= htmlspecialchars($paymentMethod) ?></span>
                        </div>
                        <?php if (!empty($refNumber) && $refNumber !== 'N/A'): ?>
                        <div class="info-cell cell-full">
                            <span class="cell-label">Transaction Ref / Cheque</span>
                            <span class="cell-value"><?= htmlspecialchars($refNumber) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($bankDisplay) && $bankDisplay !== 'N/A'): ?>
                        <div class="info-cell cell-full">
                            <span class="cell-label">Deposit Bank / Channel</span>
                            <span class="cell-value"><?= htmlspecialchars($bankDisplay) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Client & Property Information -->
                    <div class="info-section-title">
                        <i class="fa-solid fa-building"></i> Client &amp; Property Information
                    </div>
                    <div class="info-grid">
                        <div class="info-cell cell-full">
                            <span class="cell-label">Client Name</span>
                            <span class="cell-value"><?= htmlspecialchars($clientName) ?></span>
                        </div>
                        <div class="info-cell">
                            <span class="cell-label">Contact Phone</span>
                            <span class="cell-value"><?= htmlspecialchars($clientPhone) ?></span>
                        </div>
                        <div class="info-cell">
                            <span class="cell-label">Project</span>
                            <span class="cell-value"><?= htmlspecialchars($projectName) ?></span>
                        </div>
                        <div class="info-cell">
                            <span class="cell-label">Unit</span>
                            <span class="cell-value"><?= htmlspecialchars($unitName) ?></span>
                        </div>
                        <div class="info-cell">
                            <span class="cell-label">Floor</span>
                            <span class="cell-value"><?= htmlspecialchars($floorNo) ?></span>
                        </div>
                    </div>

                    <!-- System Verification Authority -->
                    <div class="authority-box">
                        <div class="authority-icon">
                            <i class="fa-solid fa-stamp"></i>
                        </div>
                        <div class="authority-content">
                            <div class="auth-label">Authentication Authority</div>
                            <div class="auth-val"><?= htmlspecialchars($verifiedByName) ?></div>
                            <div class="auth-date">Verified On: <?= htmlspecialchars($verifiedDateFormatted) ?></div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-group">
                        <a href="<?= $GLOBALS['base_url'] ?>/download-receipt/<?= $payment['id'] ?>?token=<?= $verificationToken ?>" class="btn-full btn-primary-action">
                            <i class="fa-solid fa-file-arrow-down"></i> Download Official Receipt (PDF)
                        </a>
                        <a href="<?= $GLOBALS['base_url'] ?>/payment-invoice?payment_id=<?= $payment['id'] ?>&token=<?= $verificationToken ?>" target="_blank" class="btn-full btn-secondary-action" style="background:#ffffff; color:var(--primary); border:1.5px solid var(--primary-border); font-weight:700;">
                            <i class="fa-solid fa-print"></i> View &amp; Print Full Receipt
                        </a>
                        <a href="tel:<?= preg_replace('/[^0-9+]/', '', $companyPhone) ?>" class="btn-full btn-secondary-action">
                            <i class="fa-solid fa-headset"></i> Contact TPHL Support (<?= htmlspecialchars($companyPhone) ?>)
                        </a>
                    </div>
                            <i class="fa-solid fa-headset"></i> Contact TPHL Support (<?= htmlspecialchars($companyPhone) ?>)
                        </a>
                    </div>

                </div>
            </div>

        <?php else: ?>
            <!-- Receipt Not Found Card -->
            <div class="verify-card" style="padding: 40px 24px; text-align: center;">
                <div class="status-icon-bubble bubble-rejected" style="margin-bottom: 16px;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h2 style="font-size: 20px; font-weight: 800; color: #991b1b; margin-bottom: 8px;">Receipt Not Found</h2>
                <p style="font-size: 13px; color: var(--slate-600); margin-bottom: 24px; line-height: 1.5;">
                    The requested receipt record could not be found or the link may be invalid. Please verify the receipt ID or contact The Premium Homes Ltd. accounts office.
                </p>
                <a href="tel:<?= preg_replace('/[^0-9+]/', '', $companyPhone) ?>" class="btn-full btn-primary-action">
                    <i class="fa-solid fa-phone"></i> Call Support: <?= htmlspecialchars($companyPhone) ?>
                </a>
            </div>
        <?php endif; ?>

        <!-- Footer Security Note -->
        <div class="footer-note">
            <div class="security-badge">
                <i class="fa-solid fa-shield-check"></i> Genuine Digital Verification
            </div>
            <div><?= htmlspecialchars($companyTitle) ?> &bull; Gulshan Circle-2, Dhaka</div>
            <div style="margin-top: 2px;">This electronic verification was generated directly by the secure ERP ledger.</div>
        </div>

    </div>

</body>
</html>

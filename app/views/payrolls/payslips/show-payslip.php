<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip of <?= !empty($payslip['payslip_month']) ? date('F Y', strtotime($payslip['payslip_month'])) : date('F Y', strtotime($payslip['created_at'])) ?> - <?= htmlspecialchars($payslip['employee_name']) ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .extra-small {
            font-size: 12px;
        }
        .fs-14 {
            font-size: 14px;
        }
        .fs-15 {
            font-size: 15px;
        }
        /* ================= BASE ================= */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box
        }

        body {
            font-family: Poppins, sans-serif;
            background: #f4f6f8;
            color: #111;
            padding: 20px;
            font-size: 14px;
        }

        .payslip-wrapper {
            max-width: 900px;
            margin: auto;
            background: #fff;
            border: 1px solid #d1d5db;
        }

        /* ================= HEADER ================= */
        .company-header {
            display: flex;
            justify-content: space-between;
            gap: 30px;
            padding: 24px;
            border-bottom: 3px solid #0f766e;
        }

        .header-block {
            max-width: 50%
        }

        .company-logo {
            max-height: 60px;
            margin-bottom: 10px;
        }

        .company-name {
            font-size: 18px;
            font-weight: 700;
            color: #0f766e;
        }

        .company-info {
            font-size: 14px;
            line-height: 1.6;
        }

        .header-right {
            text-align: right;
            font-size: 14px;
        }

        .employee-row {
            margin-bottom: 6px;
        }

        .employee-row span {
            display: inline-block;
            min-width: 100px;
            font-weight: 700;
            font-size: 16px;
            color: #171b22ff;
        }

        /* ================= TITLE ================= */
        .payslip-main-title {
            text-align: center;
            font-size: 26px;
            font-weight: 800;
            margin: 20px 0 12px;
        }

        .payslip-main-title::after {
            content: "";
            display: block;
            width: 90px;
            height: 3px;
            background: #0f766e;
            margin: 8px auto 0;
        }

        /* ================= PERIOD BAR ================= */
        .period-info-bar {
            margin: 0 24px 20px;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            display: flex;
            justify-content: space-between;
            font-size: 13px;
        }

        .period-label {
            font-weight: 700;
            color: #0f766e;
        }

        /* ================= CONTENT ================= */
        .payslip-content {
            padding: 24px
        }

        /* ================= SALARY ================= */
        .salary-breakdown {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .salary-col-left,
        .salary-col-right {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .breakdown-card {
            border: 1px solid #d1d5db;
            padding: 16px;
            background: #fff;
        }

        .breakdown-title {
            font-size: 14px;
            font-weight: 700;
            color: #0f766e;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .breakdown-item {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 13px;
        }

        .breakdown-total {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #d1d5db;
            font-weight: 700;
            display: flex;
            justify-content: space-between;
            font-size: 13px;
        }

        /* ================= NET SALARY ================= */
        .net-salary-section {
            margin: 24px 0;
            padding: 22px;
            border: 2px solid #0f766e;
            text-align: center;
        }

        .net-salary-label {
            font-size: 15px;
            font-weight: 700;
            color: #0f766e;
        }

        .net-salary-amount {
            font-size: 28px;
            font-weight: 800;
            margin: 6px 0;
        }

        .words-label {
            font-weight: 700;
            color: #0f766e;
        }

        /* ================= PAYMENT ================= */
        .payment-details-section {
            border: 1px solid #d1d5db;
            padding: 18px;
        }

        .payment-title {
            font-size: 16px;
            font-weight: 700;
            color: #0f766e;
            margin-bottom: 12px;
        }

        .payment-item {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
        }

        .payment-label {
            font-weight: 600;
        }

        /* ================= ACTION ================= */
        .action-section {
            padding: 20px;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .btn-action {
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 700;
            color: #fff;
            border: none;
            background: #0f766e;
        }

        .btn-download {
            background: #374151
        }

        /* ================= FOOTER ================= */
        .payslip-footer {
            border-top: 1px solid #d1d5db;
            padding: 20px;
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .signature-box {
            width: 45%;
            text-align: center
        }

        .signature-title {
            font-weight: 600;
            margin-bottom: 40px;
        }

        .signature-line {
            border-bottom: 1px solid #111
        }

        .payslip-status-wrapper {
            margin-top: 14px;
            text-align: center;
        }

        .status-btn {
            display: inline-block;
            padding: 8px 26px;
            font-size: 14px;
            font-weight: 700;
            border-radius: 20px;
            letter-spacing: 1px;
            border: 2px solid transparent;
        }

        .status-paid {
            background: #dcfce7;
            color: #166534;
            border-color: #22c55e;
        }

        .status-unpaid {
            background: #fee2e2;
            color: #991b1b;
            border-color: #ef4444;
        }

        /* ================= RESPONSIVE ================= */
        @media (max-width: 768px) and (min-width: 570px) {
            .company-header {
                display: flex;
                flex-direction: row;
                /* পাশাপাশি থাকবে */
                align-items: flex-start;
                justify-content: space-between;
                gap: 10px;
            }

            .header-block {
                width: 50%;
            }

            /* LOGO SMALLER */
            .company-logo {
                max-height: 40px;
                /* আগের চেয়ে ছোট */
                margin-bottom: 4px;
            }

            /* COMPANY INFO */
            .company-info {
                font-size: 11px;
                line-height: 1.2;
            }

            .company-name {
                font-size: 13px;
                font-weight: 700;
                margin-bottom: 2px;
            }

            /* EMPLOYEE INFO */
            .header-right {
                text-align: right;
            }

            .employee-row {
                font-size: 11px;
                line-height: 1.25;
                white-space: nowrap;
                /* ভাঙবে না */
            }




            .salary-breakdown {
                grid-template-columns: 1fr
            }

            .period-info-bar {
                flex-direction: column;
                gap: 8px
            }
        }



        /* ================= EXTRA SMALL DEVICES ================= */
        @media (max-width: 560px) {

            .company-header {
                flex-direction: column;
                align-items: flex-start;
                /* left align */
                gap: 6px;
            }

            .employee-row span {
                font-size: 12px;
            }

            .breakdown-item {
                font-size: 12px;
            }

            .header-block {
                width: 100%;
                /* পুরো width নেবে */
                display: flex;
                flex-direction: column;
                /* logo + company info vertical */
                align-items: flex-start;
            }

            .company-logo {
                max-height: 32px;
                margin-bottom: 4px;
            }

            .company-info,
            .employee-row {
                font-size: 10px;
                line-height: 1.2;
                white-space: normal;
                /* text wrap হবে */
            }

            .company-name {
                font-size: 12px;
                font-weight: 700;
                margin-bottom: 2px;
            }

            .header-right {
                width: 100%;
                text-align: left;
                display: flex;
                flex-direction: column;
                /* employee info vertical */
                gap: 2px;
                margin-top: 6px;
            }

            .period-info-bar {
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
                gap: 12px !important;
            }

            .period-info-bar>div {
                font-size: 10px;
            }

            .payslip-main-title {
                font-size: 18px;
            }

        }


        /* ================= PRINT ================= */
        @media print {

            @page {
                size: A4;
                margin: 8mm;
            }

            body {
                background: #fff !important;
                font-size: 11px;
                line-height: 1.3;
            }

            /* ================= HEADER FIX ================= */

            .company-header {
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
                align-items: flex-start !important;
                gap: 20px !important;
            }

            .header-block {
                width: 48% !important;
                max-width: 48% !important;
            }

            .header-right {
                text-align: right !important;
            }

            /* ================= TITLE ================= */

            .payslip-main-title {
                font-size: 22px !important;
                margin: 12px 0 !important;
            }

            /* ================= PERIOD / DATE FIX ================= */

            .period-info-bar {
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
                gap: 12px !important;
            }

            .period-info-bar>div {
                width: 32% !important;
                text-align: left;
                white-space: nowrap;
            }

            .company-logo {
                height: 40px;
            }

            /* ================= SALARY CARDS FIX ================= */

            .salary-breakdown {
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
                gap: 12px !important;
            }

            .salary-col-left,
            .salary-col-right {
                width: 48.5% !important;
                max-width: 48.5% !important;
                display: flex !important;
                flex-direction: column !important;
                gap: 8px !important;
            }

            .breakdown-card {
                width: 100% !important;
                max-width: 100% !important;
                border: 1px solid #999 !important;
                padding: 10px !important;
            }

            .status-btn {
                font-size: 12px;
                padding: 6px 20px;
            }

            /* ================= NET SALARY ================= */

            .net-salary-section {
                padding: 14px !important;
                margin: 14px 0 !important;
            }

            .net-salary-amount {
                font-size: 22px !important;
            }

            /* ================= PAYMENT ================= */

            .payment-details-section {
                padding: 12px !important;
                margin-top: 12px !important;
            }

            /* ================= FOOTER ================= */

            .signature-section {
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
                gap: 20px !important;
            }

            .signature-box {
                width: 45% !important;
            }

            /* ================= HIDE UI ================= */

            .action-section,
            .no-print {
                display: none !important;
            }

            * {
                box-shadow: none !important;
                animation: none !important;
                transition: none !important;
                page-break-inside: avoid !important;
            }
        }
    </style>
</head>

<body>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!class_exists('ApprovalRequest')) {
    require_once __DIR__ . '/../../../models/ApprovalRequest.php';
}
$dbConn = $GLOBALS['db'] ?? ($this->db ?? null);
$ahInfo = ApprovalRequest::getAccountsHeadApprovalInfo($dbConn, 'payslips', (int)$payslip['id']);
$isAhApproved = empty($ahInfo['has_request']) || !empty($ahInfo['is_accounts_head_approved']);
$isMgmtApproved = !empty($ahInfo['is_mgmt_approved']) || in_array(strtolower($payslip['status'] ?? ''), ['approved', 'paid'], true);
$isMgmtRejected = in_array(strtolower($ahInfo['status'] ?? ''), ['rejected', 'reject'], true) || in_array(strtolower($payslip['status'] ?? ''), ['rejected', 'reject'], true);

$currentUserRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
$currentUserSecRoleId = (int)($_SESSION['user']['secondary_role_id'] ?? 0);
$currentUserPriRoleId = (int)($_SESSION['user']['primary_role_id'] ?? 0);
$currentRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
$isChiefOfficer = in_array($currentUserRoleId, [33, 34, 35], true) || in_array($currentRoleName, ['chief officer', 'management viewer', 'seal ceo'], true);
$canMgmtApprovePayslip = (
    in_array($currentUserRoleId, [1, 3, 38], true) ||
    strpos($currentRoleName, 'super admin') !== false ||
    strpos($currentRoleName, 'admin') !== false ||
    strpos($currentRoleName, 'management') !== false ||
    strpos($currentRoleName, 'director') !== false
) && !$isChiefOfficer;
$isAccountsHeadUser = in_array(30, [$currentUserRoleId, $currentUserSecRoleId, $currentUserPriRoleId], true) || strpos($currentRoleName, 'accounts head') !== false;
$isAhRejected = ($ahInfo['accounts_head_status'] ?? '') === 'rejected';
$canDisbursePayslip = in_array($currentUserRoleId, [1, 3, 6, 30, 33, 34, 35, 36, 38], true)
    || in_array($currentUserSecRoleId, [1, 3, 6, 30, 33, 34, 35, 36, 38], true)
    || strpos($currentRoleName, 'account') !== false
    || strpos($currentRoleName, 'management') !== false
    || strpos($currentRoleName, 'admin') !== false
    || (function_exists('hasPermission') && hasPermission('accounts manage'));

$companyBanksForDisburse = [];
if ($dbConn && $canDisbursePayslip) {
    $cbRes = @$dbConn->query("SELECT id, bank_name, account_number FROM company_bank_accounts ORDER BY bank_name ASC");
    if ($cbRes) {
        while ($cbRow = $cbRes->fetch_assoc()) {
            $companyBanksForDisburse[] = $cbRow;
        }
    }
}

$mgmtApproverName = $ahInfo['mgmt_approver_name'] ?? 'Management';
$mgmtApprovedAt = !empty($ahInfo['mgmt_approved_at']) ? date('d M, Y h:i A', strtotime($ahInfo['mgmt_approved_at'])) : null;
$reqIdForJs = (int)($ahInfo['approval_request_id'] ?? 0);
$backUrl = (!empty($_GET['ref']) && $_GET['ref'] === 'all_approvals')
    ? ($GLOBALS['base_url'] . '/all-approval-requests')
    : ($GLOBALS['base_url'] . '/payslip-lists');

// Pre-compute Earnings, Deductions & Net Salary so top action bar & body stay 100% consistent
$coreEarnings = [];
$additionalEarnings = [];
$deductions = [];

$basicSalary = (float)($payslip['basic_salary'] ?? 0);
$coreEarnings[] = [
    'name' => 'Basic Salary',
    'amount' => $basicSalary
];

$coreEarningTotal = $basicSalary;
$additionalEarningTotal = 0;
$deductionTotal = 0;

foreach (($payslip['items'] ?? []) as $item) {
    $name = trim($item['item_name'] ?? '');
    $type = $item['item_type'] ?? 'earning';
    $amt = (float)($item['amount'] ?? 0);
    if ($amt <= 0) continue;

    $lower = strtolower($name);
    if ($type === 'earning') {
        if (strpos($lower, 'basic') !== false) {
            continue;
        } elseif (
            strpos($lower, 'house rent') !== false ||
            strpos($lower, 'medical') !== false ||
            strpos($lower, 'conveyance') !== false ||
            strpos($lower, 'recreation') !== false
        ) {
            $coreEarnings[] = ['name' => $name, 'amount' => $amt];
            $coreEarningTotal += $amt;
        } else {
            $additionalEarnings[] = ['name' => $name, 'amount' => $amt];
            $additionalEarningTotal += $amt;
        }
    } elseif ($type === 'deduction') {
        $deductions[] = ['name' => $name, 'amount' => $amt];
        $deductionTotal += $amt;
    }
}

$calculatedTotalEarnings = $coreEarningTotal + $additionalEarningTotal;
$displayTotalEarnings = isset($payslip['total_earning']) ? (float)$payslip['total_earning'] : $calculatedTotalEarnings;
$displayTotalDeduction = (isset($payslip['total_deduction']) && ((float)$payslip['total_deduction'] > 0 || $deductionTotal <= 0))
    ? (float)$payslip['total_deduction']
    : $deductionTotal;
$displayNetSalary = isset($payslip['net_salary'])
    ? (float)$payslip['net_salary']
    : max(0, $displayTotalEarnings - $displayTotalDeduction);
?>

<div class="no-print" style="max-width: 900px; margin: 10px auto 14px auto;">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 bg-white border px-3 py-2" style="border-radius: 8px;">
        <!-- Left: Back + Minimal Status Pills -->
        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="<?= $backUrl ?>" class="btn btn-light btn-sm border text-secondary px-2 py-1" style="font-size: 12px; font-weight: 600;">
                <i class="fa-solid fa-arrow-left me-1"></i>Back
            </a>

            <?php if ($payslip['status'] !== 'draft'): ?>
                <span class="text-muted opacity-50">|</span>

                <!-- Management Status Pill -->
                <span class="d-inline-flex align-items-center gap-1 px-2 py-1 rounded border" style="font-size: 12px; background: <?= $isMgmtApproved ? '#f0fdf4' : ($isMgmtRejected ? '#fef2f2' : '#fffbeb') ?>; border-color: <?= $isMgmtApproved ? '#bbf7d0' : ($isMgmtRejected ? '#fecaca' : '#fde68a') ?> !important; color: <?= $isMgmtApproved ? '#166534' : ($isMgmtRejected ? '#991b1b' : '#92400e') ?>;">
                    <i class="fa-solid <?= $isMgmtApproved ? 'fa-circle-check' : ($isMgmtRejected ? 'fa-circle-xmark' : 'fa-clock') ?>"></i>
                    <span>Management: <strong><?= $isMgmtApproved ? 'Approved' : ($isMgmtRejected ? 'Rejected' : 'Pending') ?></strong></span>
                </span>

                <!-- Accounts Head Status Pill -->
                <span class="d-inline-flex align-items-center gap-1 px-2 py-1 rounded border" style="font-size: 12px; background: <?= $isAhApproved ? '#f0fdf4' : ($isAhRejected ? '#fef2f2' : '#fffbeb') ?>; border-color: <?= $isAhApproved ? '#bbf7d0' : ($isAhRejected ? '#fecaca' : '#fde68a') ?> !important; color: <?= $isAhApproved ? '#166534' : ($isAhRejected ? '#991b1b' : '#92400e') ?>;">
                    <i class="fa-solid <?= $isAhApproved ? 'fa-circle-check' : ($isAhRejected ? 'fa-circle-xmark' : 'fa-clock') ?>"></i>
                    <span>Accounts Head: <strong><?= $isAhApproved ? 'Approved' : ($isAhRejected ? 'Rejected' : 'Pending') ?></strong></span>
                </span>

                <?php if ($payslip['status'] === 'paid'): ?>
                    <span class="d-inline-flex align-items-center gap-1 px-2 py-1 rounded border" style="font-size: 12px; background: #ecfdf5; border-color: #6ee7b7 !important; color: #065f46;">
                        <i class="fa-solid fa-money-check-dollar"></i>
                        <span>Payout: <strong>Disbursed</strong></span>
                    </span>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Right: Clean Action Buttons -->
        <div class="d-flex flex-wrap align-items-center gap-2">
            <?php if ($payslip['status'] !== 'draft' && $payslip['status'] !== 'paid'): ?>
                <?php if (!$isMgmtApproved && !$isMgmtRejected && $canMgmtApprovePayslip): ?>
                    <button type="button" class="btn btn-success btn-sm px-3 py-1 fw-semibold" style="font-size: 12px;" id="btnMgmtPayslipApprove" onclick="handleMgmtPayslipApprove(<?= $reqIdForJs ?>, <?= (int)$payslip['id'] ?>, <?= $isAhApproved ? 'true' : 'false' ?>)">
                        <i class="fa-solid fa-check me-1"></i>Approve
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm px-2.5 py-1 fw-semibold" style="font-size: 12px;" onclick="handleMgmtPayslipReject(<?= $reqIdForJs ?>, <?= (int)$payslip['id'] ?>)">
                        <i class="fa-solid fa-xmark me-1"></i>Reject
                    </button>
                <?php endif; ?>

                <?php if (!$isAhApproved && !$isAhRejected && $isAccountsHeadUser && !empty($ahInfo['has_request'])): ?>
                    <button type="button" class="btn btn-primary btn-sm px-3 py-1 fw-semibold" style="font-size: 12px;" onclick="triggerAhApprove(<?= $reqIdForJs ?>, 'Payslip PS-<?= (int)$payslip['id'] ?>', '<?= number_format((float)($payslip['net_salary'] ?? 0), 2) ?>')">
                        <i class="fa-solid fa-check me-1"></i>Approve (Accts Head)
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm px-2.5 py-1 fw-semibold" style="font-size: 12px;" onclick="triggerAhReject(<?= $reqIdForJs ?>)">
                        <i class="fa-solid fa-xmark me-1"></i>Reject
                    </button>
                <?php endif; ?>

                <?php if ($isMgmtApproved && $isAhApproved && $canDisbursePayslip): ?>
                    <button type="button" class="btn btn-success btn-sm px-3 py-1 fw-semibold" style="font-size: 12px;" onclick="disburseSinglePayslipModal(<?= (int)$payslip['id'] ?>, '<?= htmlspecialchars(addslashes($payslip['employee_name'] ?? 'Employee')) ?>', '<?= number_format((float)$displayNetSalary, 2) ?>')">
                        <i class="fa-solid fa-money-check-dollar me-1"></i>Disburse Payment
                    </button>
                    <a href="<?= $GLOBALS['base_url'] ?>/payslip-lists" class="btn btn-outline-success btn-sm px-2.5 py-1 fw-semibold" style="font-size: 12px;" title="Go to Bulk Payroll Disbursement">
                        <i class="fa-solid fa-list-check me-1"></i>Bulk Disburse List
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($payslip['status'] === 'paid'): ?>
                <button type="button" class="btn btn-outline-primary btn-sm px-3 py-1 fw-semibold" style="font-size: 12px;" onclick="sendPayslipEmailFromView(<?= (int)$payslip['id'] ?>, '<?= htmlspecialchars(addslashes($payslip['employee_name'] ?? 'Employee')) ?>', '<?= htmlspecialchars(addslashes($payslip['email'] ?? '')) ?>')">
                    <i class="fa-regular fa-paper-plane me-1"></i><?= !empty($payslip['email_sent_at']) ? 'Resend Email' : 'Send Email' ?>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php 
    if (!empty($ahInfo['has_request'])) {
        $sourceTable = 'payslips';
        $sourceId = (int)$payslip['id'];
        $hideAhTopCard = true;
        include __DIR__ . '/../../layouts/accounts_head_approval_card.php';
    }
    ?>
</div>

    <div class="payslip-wrapper" id="payslip">


        <!-- HEADER -->
        <div class="company-header">
            <div class="header-block">
                <?php if (getSetting('white_logo')): ?>
                    <img
                        src="<?= getImage('settings', getSetting('white_logo')) ?>"
                        class="company-logo"
                        alt="Company Logo">
                <?php endif; ?>

                <div class="company-info">
                    <div class="company-name"><?= getSetting('website_title') ?></div>
                    <div><?= getSetting('email') ?></div>
                    <div><?= getSetting('phone') ?></div>
                </div>
            </div>

            <div class="header-block header-right">
                <div class="employee-row"><span class="pe-2">Employee ID: </span><strong><?= htmlspecialchars($payslip['employee_office_id'] ?? 'N/A') ?></strong></div>
                <div class="employee-row"><span class="pe-2">Employee: </span><?= htmlspecialchars($payslip['employee_name']) ?></div>
                <div class="employee-row "><span class="pe-2">Designation: </span><?= htmlspecialchars($payslip['designation_name'] ?? "N/A") ?></div>
                <div class="employee-row"><span class="pe-2">Department: </span><?= htmlspecialchars($payslip['department_name'] ?? "N/A") ?></div>
                <?php if (!empty($payslip['joining_date'])): ?>
                    <div class="employee-row"><span class="pe-2">Joining Date: </span><?= date('d M Y', strtotime($payslip['joining_date'])) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TITLE -->
        <div class="payslip-main-title">
            Payslip of <?= !empty($payslip['payslip_month']) ? date('F Y', strtotime($payslip['payslip_month'])) : date('F Y', strtotime($payslip['created_at'])) ?>
        </div>

        <!-- PERIOD -->
        <div class="period-info-bar">
            <div><span class="period-label">Created:</span> <?= !empty($payslip['created_at']) ? date('d M Y', strtotime($payslip['created_at'])) : date('d M Y') ?></div>
            <div>
                <span class="period-label">Salary Month:</span> 
                <?= date('F Y', strtotime($payslip['payslip_month'] ?? $payslip['created_at'])) ?>
                <?= ($payslip['status'] === 'draft') ? ' <span class="text-muted" style="font-weight:normal;">(Draft Certificate)</span>' : '' ?>
            </div>
            <div><span class="period-label">Payslip No:</span> PS-<?= $payslip['id'] ?></div>
        </div>

        <div class="payslip-content">

            <!-- SALARY BREAKDOWN -->
            <div class="salary-breakdown">

                <!-- Left Column: Core Salary & Additional Earnings -->
                <div class="salary-col-left">

                    <!-- Segment 1: Core Salary Breakdown -->
                    <div class="breakdown-card">
                        <div class="breakdown-title">
                            <span><i class="fa-solid fa-wallet me-1"></i> Earnings (Salary Breakdown)</span>
                        </div>

                        <?php foreach ($coreEarnings as $item): ?>
                            <div class="breakdown-item">
                                <span><?= htmlspecialchars($item['name']) ?></span>
                                <span>৳ <?= number_format($item['amount'], 2) ?></span>
                            </div>
                        <?php endforeach; ?>

                        <div class="breakdown-total">
                            <span>Gross Salary (Core)</span>
                            <span>৳ <?= number_format($coreEarningTotal, 2) ?></span>
                        </div>
                        <?php
                        $empGrossCore = ((float)($payslip['gross_salary'] ?? 0) > 0) ? (float)$payslip['gross_salary'] : $coreEarningTotal;
                        $proRataServiceGross = max(0, round($displayTotalEarnings - $additionalEarningTotal, 2));
                        if (($empGrossCore - $proRataServiceGross) > 0.50):
                        ?>
                        <div class="breakdown-item mt-1 pt-1" style="border-top: 1px dashed #cbd5e1; color: #0f766e; font-weight: 600;">
                            <span>Pro-Rata Service Gross<?= !empty($payslip['cycle_days']) ? ' (' . (int)$payslip['cycle_days'] . 'd Cycle)' : '' ?></span>
                            <span>৳ <?= number_format($proRataServiceGross, 2) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Segment 2: Additional Earnings & Bonuses -->
                    <div class="breakdown-card">
                        <div class="breakdown-title" style="color: #2563eb;">
                            <span><i class="fa-solid fa-gift me-1"></i> Additional Earnings &amp; Bonuses</span>
                        </div>

                        <?php if (!empty($additionalEarnings)): ?>
                            <?php foreach ($additionalEarnings as $item): ?>
                                <div class="breakdown-item">
                                    <span><?= htmlspecialchars($item['name']) ?></span>
                                    <span>৳ <?= number_format($item['amount'], 2) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="breakdown-item text-muted">
                                <span>No Additional Benefits</span>
                                <span>৳ 0.00</span>
                            </div>
                        <?php endif; ?>

                        <div class="breakdown-total" style="color: #2563eb;">
                            <span>Total Additional Earnings</span>
                            <span>৳ <?= number_format($additionalEarningTotal, 2) ?></span>
                        </div>
                    </div>

                    <!-- Overall Total Earnings Bar -->
                    <div class="p-2 px-3 rounded d-flex justify-content-between align-items-center fw-bold" style="background: #f0fdf4; border: 1px solid #86efac; color: #166534; font-size: 13px;">
                        <span><i class="fa-solid fa-circle-plus me-1"></i> Total Earnings</span>
                        <span>৳ <?= number_format($displayTotalEarnings, 2) ?></span>
                    </div>

                </div>

                <!-- Right Column: Deductions -->
                <div class="salary-col-right">
                    <div class="breakdown-card h-100 d-flex flex-column">
                        <div class="breakdown-title" style="color: #dc2626;">
                            <span><i class="fa-solid fa-circle-minus me-1"></i> Deductions</span>
                        </div>

                        <?php if (!empty($deductions)): ?>
                            <?php foreach ($deductions as $item): ?>
                                <div class="breakdown-item">
                                    <span><?= htmlspecialchars($item['name']) ?></span>
                                    <span>৳ <?= number_format($item['amount'], 2) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="breakdown-item text-muted">
                                <span>No Deduction</span>
                                <span>৳ 0.00</span>
                            </div>
                        <?php endif; ?>

                        <div class="breakdown-total mt-auto pt-3" style="color: #dc2626;">
                            <span>Total Deductions</span>
                            <span>৳ <?= number_format($displayTotalDeduction, 2) ?></span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- NET -->
            <div class="net-salary-section">
                <div class="net-salary-label">Net Salary</div>

                <div class="net-salary-amount">
                    ৳ <?= number_format($displayNetSalary, 2) ?>
                </div>

                <div class="amount-words">
                    <span class="words-label">Amount in Words:</span>
                    <?= amountInWords($displayNetSalary) ?>
                </div>

                <!-- STATUS BUTTON -->
                <div class="payslip-status-wrapper">
                    <?php if ($payslip['status'] === 'paid'): ?>
                        <span class="status-btn status-paid">
                            ✔ PAID
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- PAYMENT -->
            <div class="payment-details-section">
                <?php if ($payslip['status'] === 'paid'): ?>
                    <div class="payment-title">Payment Details</div>

                    <div class="payment-item">
                        <span class="payment-label">Payment Method</span>
                        <span><?= ucfirst($payslip['payment_method']) ?></span>
                    </div>

                    <?php if ((float)($payslip['bank_amount'] ?? 0) > 0): ?>
                        <div class="payment-item"><span class="payment-label">Bank Transfer</span>৳ <?= number_format($payslip['bank_amount'], 2) ?></div>
                    <?php endif; ?>
                    <?php if ((float)($payslip['cash_amount'] ?? 0) > 0): ?>
                        <div class="payment-item"><span class="payment-label">Cash Payment</span>৳ <?= number_format($payslip['cash_amount'], 2) ?></div>
                    <?php endif; ?>

                    <?php 
                    $displayBankName = !empty($payslip['emp_bank_name']) ? $payslip['emp_bank_name'] : ($payslip['bank_name'] ?? '');
                    $displayAccountNo = !empty($payslip['emp_account_number']) ? $payslip['emp_account_number'] : ($payslip['account_number'] ?? '');
                    ?>
                    <?php if ($payslip['payment_method'] === 'bank' && !empty($displayBankName)): ?>
                        <div class="payment-item"><span class="payment-label">Bank Name</span><?= htmlspecialchars($displayBankName) ?></div>
                        <div class="payment-item"><span class="payment-label">Account Number</span><?= htmlspecialchars($displayAccountNo) ?></div>
                    <?php endif; ?>

                <?php elseif ($payslip['status'] === 'draft'): ?>
                    <!-- Status hidden for draft salary certificates -->
                <?php else: ?>
                    <div class="payslip-status-wrapper">
                        <p class="fw-semibold fs-6 mb-2">Payment Status</p>
                        <?php if ($isMgmtRejected): ?>
                            <span class="status-btn status-unpaid">
                                <i class="fa-solid fa-ban me-1"></i> REJECTED
                            </span>
                        <?php elseif (!$isMgmtApproved && !$isAhApproved): ?>
                            <span class="status-btn" style="background:#fff3cd; color:#856404; border:1px solid #ffeeba; font-size:11px; padding: 5px 12px;">
                                <i class="fa-solid fa-lock me-1"></i> PENDING MANAGEMENT &amp; ACCOUNTS HEAD
                            </span>
                        <?php elseif (!$isMgmtApproved): ?>
                            <span class="status-btn" style="background:#fff3cd; color:#856404; border:1px solid #ffeeba; font-size:11px; padding: 5px 12px;">
                                <i class="fa-solid fa-clock me-1"></i> PENDING MANAGEMENT APPROVAL
                            </span>
                        <?php elseif (!$isAhApproved): ?>
                            <span class="status-btn" style="background:#fff3cd; color:#856404; border:1px solid #ffeeba; font-size:11px; padding: 5px 12px;">
                                <i class="fa-solid fa-lock me-1"></i> MANAGEMENT APPROVED &bull; PENDING ACCOUNTS HEAD
                            </span>
                        <?php else: ?>
                            <span class="status-btn status-unpaid">
                                ✖ UNPAID (APPROVED FOR DISBURSEMENT)
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
        <!-- Footer -->
        <div class="payslip-footer">
            <!-- Signature Section -->
            <div class="signature-section">
                <div class="signature-box">
                    <span class="signature-title">Employee Signature</span>
                    <div class="signature-line mt-5"></div>
                </div>
                <div class="signature-box">
                    <span class="signature-title">Authorized Signature</span>
                    <div class="signature-line mt-5"></div>
                </div>
            </div>
        </div>

    </div>

    <!-- Action Buttons (OUTSIDE #payslip to prevent screenshot capture in PDF) -->
    <div class="action-section" style="max-width: 900px; margin: 15px auto; display: flex; justify-content: flex-end; gap: 12px;">
        <button type="button" class="btn-action btn-print" onclick="printPayslip()">
            <i class="fas fa-print me-1"></i> Print Payslip
        </button>
        <button type="button" class="btn-action btn-download" onclick="downloadPayslipPDF()">
            <i class="fas fa-file-pdf me-1"></i> Download PDF
        </button>
    </div>
    <script>
        function handleMgmtPayslipApprove(reqId, payslipId, isAhApproved) {
            const executeApproval = () => {
                const btn = document.getElementById('btnMgmtPayslipApprove');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Approving...';
                }

                fetch('<?= $GLOBALS['base_url'] ?>/approval-requests/approve-management', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        approval_request_id: reqId,
                        source_table: 'payslips',
                        source_id: payslipId,
                        force_approve: true,
                        note: 'Approved by Management'
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Approved!',
                            text: data.message || 'Payslip approved by Management successfully.',
                            timer: 1400,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fa-solid fa-check me-1"></i>Approve';
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Approval Failed',
                            text: data.message || 'Failed to approve payslip.'
                        });
                    }
                })
                .catch(err => {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa-solid fa-check me-1"></i>Approve';
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: err.toString()
                    });
                });
            };

            if (!isAhApproved) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Accounts Head Approval Pending',
                    html: 'Accounts Head এখনও এটি approve করেননি। আপনি কি এখনই Final Approval দিতে চাচ্ছেন?',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Approve',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeApproval();
                    }
                });
            } else {
                Swal.fire({
                    icon: 'question',
                    title: 'Confirm Final Approval',
                    text: 'Are you sure you want to approve this payslip as Management?',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Approve',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeApproval();
                    }
                });
            }
        }

        function handleMgmtPayslipReject(reqId, payslipId) {
            Swal.fire({
                icon: 'warning',
                title: 'Reject Payslip?',
                input: 'textarea',
                inputLabel: 'Rejection Reason (Optional)',
                inputPlaceholder: 'Enter reason for rejection...',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Reject',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('<?= $GLOBALS['base_url'] ?>/approval-requests/reject-management', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            approval_request_id: reqId,
                            source_table: 'payslips',
                            source_id: payslipId,
                            note: result.value || 'Rejected by Management'
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Rejected!',
                                text: data.message || 'Payslip rejected by Management.',
                                timer: 1400,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Rejection Failed',
                                text: data.message || 'Failed to reject payslip.'
                            });
                        }
                    })
                    .catch(err => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: err.toString()
                        });
                    });
                }
            });
        }

        const companyBanksOptions = <?= json_encode($companyBanksForDisburse ?? []) ?>;

        function disburseSinglePayslipModal(payslipId, empName, netAmount) {
            let optionsHtml = '<option value="0">Petty Cash / Cash Account</option>';
            if (Array.isArray(companyBanksOptions)) {
                companyBanksOptions.forEach(b => {
                    optionsHtml += `<option value="${b.id}">${b.bank_name} (${b.account_number})</option>`;
                });
            }

            Swal.fire({
                title: 'Disburse Salary Payment',
                html: `
                    <div class="text-start" style="font-size: 13px;">
                        <p class="mb-2">Disburse salary for <strong>${empName}</strong> (Net Payable: <strong>৳ ${netAmount}</strong>):</p>
                        <label class="form-label fw-semibold mb-1">Disburse From Account</label>
                        <select id="swal_disburse_bank_id" class="form-select form-select-sm">
                            ${optionsHtml}
                        </select>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Disburse',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#198754',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    const bankSelect = document.getElementById('swal_disburse_bank_id');
                    const bankId = bankSelect ? bankSelect.value : 0;
                    return fetch('<?= $GLOBALS['base_url'] ?>/disburse-batch-payslips', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            payslip_ids: [payslipId],
                            bank_account_id: bankId
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || 'Failed to disburse payment.');
                        }
                        return data;
                    })
                    .catch(err => {
                        Swal.showValidationMessage(err.message || err.toString());
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Disbursed Successfully!',
                        text: result.value.message || 'Salary payment has been disbursed.',
                        confirmButtonColor: '#198754'
                    }).then(() => {
                        window.location.reload();
                    });
                }
            });
        }

        function sendPayslipEmailFromView(payslipId, empName, empEmail) {
            if (!empEmail || empEmail.trim() === '') {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Email Configured',
                    text: `Employee "${empName}" does not have an email address in their profile.`,
                    confirmButtonColor: '#0f766e'
                });
                return;
            }

            Swal.fire({
                title: 'Send Payslip via Email?',
                html: `Send the official payslip and PDF attachment to:<br><br><b>${empName}</b><br><span class="text-primary font-monospace">&lt;${empEmail}&gt;</span>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="fa-regular fa-paper-plane me-1"></i> Yes, Send Email',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#0f766e',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return fetch('<?= $GLOBALS['base_url'] ?>/send-payslip-email', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ payslip_id: payslipId })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || 'Failed to send email.');
                        }
                        return data;
                    })
                    .catch(err => {
                        Swal.showValidationMessage(err.message || err.toString());
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then(result => {
                if (result.isConfirmed && result.value) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Email Sent!',
                        text: result.value.message || 'Payslip has been emailed successfully.',
                        confirmButtonColor: '#0f766e'
                    }).then(() => {
                        window.location.reload();
                    });
                }
            });
        }

        function printPayslip() {
            window.print();
        }
        // Auto-print if ?print=1 is in URL
        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('print') === '1') {
                printPayslip(); // triggers browser print dialog immediately
            }
        });
    </script>

    <script>
        function loadScript(src) {
            return new Promise((resolve, reject) => {
                const s = document.createElement('script');
                s.src = src;
                s.onload = resolve;
                s.onerror = reject;
                document.body.appendChild(s);
            });
        }

        async function downloadPayslipPDF() {

            if (typeof html2canvas === 'undefined') {
                await loadScript('https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js');
            }
            if (typeof window.jspdf === 'undefined') {
                await loadScript('https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js');
            }

            const {
                jsPDF
            } = window.jspdf;
            const payslip = document.getElementById('payslip');

            const canvas = await html2canvas(payslip, {
                scale: 2
            });
            const imgData = canvas.toDataURL('image/png');

            const pdf = new jsPDF('p', 'mm', 'a4');
            const width = 210;
            const height = (canvas.height * width) / canvas.width;

            pdf.addImage(imgData, 'PNG', 0, 0, width, height);
            pdf.save('Payslip.pdf');
        }
    </script>



</body>

</html>
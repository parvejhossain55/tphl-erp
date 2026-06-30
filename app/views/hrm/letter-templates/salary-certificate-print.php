<?php
$empTypeLabel = ['permanent'=>'Permanent','contract'=>'Contractual','director'=>'Director','intern'=>'Intern','remote'=>'Remote','probation'=>'Probationary','management'=>'Management'];
$empType   = $empTypeLabel[$letter['employee_type'] ?? 'permanent'] ?? 'Permanent';
$certMonth = !empty($letter['certificate_month']) ? date('F Y', strtotime($letter['certificate_month'].'-01')) : '';
$refNum    = $letter['ref_number'] ?? ('TPHL/HR/Salary Certificate/'.$certMonth);
$logoPath  = realpath(__DIR__.'/../../../../../public/assets/images/logo/tphl_logo.png');
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Salary Certificate — <?= htmlspecialchars($letter['full_name']) ?></title>
<style>
    /* ── Reset ── */
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

    /* ── Screen styles ── */
    html, body {
        width: 100%;
        background: #fff;
        color: #333;
        font-family: 'Segoe UI', Arial, sans-serif;
        font-size: 13px;
        line-height: 1.7;
    }

    /* Wrapper constrains width on screen */
    .page-wrapper {
        width: 210mm;
        min-height: 297mm;
        margin: 0 auto;
        padding: 20mm 18mm;
        background: #fff;
    }

    /* ── Common elements ── */
    .logo          { max-width: 110px; height: auto; display: block; }
    .company-name  { color: #006400; font-size: 18px; font-weight: 800; margin: 0 0 2px; }
    .company-info  { font-size: 10px; color: #555; line-height: 1.5; }
    .separator     { height: 2px; background: #006400; margin: 12px 0 20px; }

    .cert-title {
        text-align: center;
        font-size: 14px;
        font-weight: 700;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        text-decoration: underline;
        margin: 22px 0 16px;
    }

    p { margin-bottom: 8px; }

    .salary-table {
        width: 100%;
        border-collapse: collapse;
        margin: 12px 0 18px;
        font-size: 12px;
        table-layout: fixed;
    }
    .salary-table th,
    .salary-table td {
        border: 1px solid #aaa;
        padding: 7px 10px;
        word-wrap: break-word;
    }
    .salary-table thead tr { background: #eeeeee; }
    .salary-table th       { font-weight: 700; text-align: center; }
    .amount { text-align: right; }
    .total-row td  { background: #d4edda; font-weight: 700; }
    .deduct-row td { background: #fff3f3; }

    .sign-block { margin-top: 48px; }
    .sign-line  { border-top: 1px solid #555; width: 180px; margin-top: 44px; margin-bottom: 5px; }

    /* ── Print-specific ── */
    @media print {
        html, body {
            width: 100%;
            margin: 0;
            padding: 0;
        }
        .page-wrapper {
            width: 100%;
            margin: 0;
            padding: 0;   /* @page margin handles spacing */
            box-shadow: none;
        }
        @page {
            size: A4 portrait;
            margin: 18mm 16mm 18mm 16mm;
        }
    }
</style>
</head>
<body onload="window.print()">

<div class="page-wrapper">

    <!-- ── Header ── -->
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="38%" valign="middle">
                <?php if ($logoPath && file_exists($logoPath)): ?>
                <img src="<?= $logoPath ?>" class="logo" alt="Logo">
                <?php endif; ?>
            </td>
            <td width="62%" align="right" valign="middle">
                <p class="company-name"><?= htmlspecialchars(getSetting('website_title','The Premium Homes Ltd.')) ?></p>
                <p class="company-info"><?= htmlspecialchars(getSetting('address','Corporate Office, Dhaka, Bangladesh')) ?></p>
                <p class="company-info">Email: <?= htmlspecialchars(getSetting('email','hr@tphl.com.bd')) ?></p>
            </td>
        </tr>
    </table>

    <div class="separator"></div>

    <!-- ── Ref & Date ── -->
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:10px;">
        <tr>
            <td align="left"><strong>Ref:</strong> <?= htmlspecialchars($refNum) ?></td>
            <td align="right"><strong>Date:</strong> <?= date('d F, Y', strtotime($letter['letter_date'])) ?></td>
        </tr>
    </table>

    <!-- ── Title ── -->
    <div class="cert-title">To Whom It May Concern</div>

    <!-- ── Opening paragraph ── -->
    <p>
        This is to certify that <strong>Mr./Ms. <?= htmlspecialchars($letter['full_name']) ?></strong>
        (Employee ID: <?= htmlspecialchars($letter['employee_code']) ?>) is a
        <strong><?= $empType ?></strong> employee of
        <strong><?= htmlspecialchars(getSetting('website_title','The Premium Homes Ltd.')) ?></strong>
        and is employed as <strong><?= htmlspecialchars($letter['designation_name']??'') ?>
        – <?= htmlspecialchars($letter['department_name']??'') ?></strong>
        since <strong><?= !empty($letter['emp_joining_date']) ? date('d F, Y', strtotime($letter['emp_joining_date'])) : 'N/A' ?></strong>.
    </p>
    <br>
    <p>According to our records, his/her current monthly salary breakdown for <strong><?= $certMonth ?></strong> is as follows:</p>

    <!-- ── Earnings Table ── -->
    <table class="salary-table">
        <thead>
            <tr>
                <th width="62%" style="text-align:left;">Earning</th>
                <th width="38%" style="text-align:right;">Amount (BDT)</th>
            </tr>
        </thead>
        <tbody>
            <?php $totalEarning = 0; foreach ($salaryItems['earning'] as $item): $totalEarning += $item['amount']; ?>
            <tr>
                <td><?= htmlspecialchars($item['label']) ?></td>
                <td class="amount"><?= number_format($item['amount'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td><strong>Gross Salary</strong></td>
                <td class="amount"><strong><?= number_format($letter['gross_salary'] ?? $totalEarning, 2) ?> (BDT)</strong></td>
            </tr>
        </tbody>
    </table>

    <!-- ── Deductions Table ── -->
    <?php if (!empty($salaryItems['deduction'])): ?>
    <table class="salary-table">
        <thead>
            <tr>
                <th width="62%" style="text-align:left;">Deduction</th>
                <th width="38%" style="text-align:right;">Amount (BDT)</th>
            </tr>
        </thead>
        <tbody>
            <?php $totalDeduction = 0; foreach ($salaryItems['deduction'] as $item): $totalDeduction += $item['amount']; ?>
            <tr class="deduct-row">
                <td><?= htmlspecialchars($item['label']) ?></td>
                <td class="amount"><?= number_format($item['amount'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td><strong>Net Salary</strong></td>
                <td class="amount"><strong><?= number_format($letter['net_salary'] ?? ($totalEarning - $totalDeduction), 2) ?> (BDT)</strong></td>
            </tr>
        </tbody>
    </table>
    <?php endif; ?>

    <br>
    <p>
        This certificate is issued upon his/her request for the purpose of
        <strong><?= htmlspecialchars($letter['purpose'] ?? 'personal use') ?></strong>
        without any liability on the part of this company.
    </p>
    <br>
    <p>We wish him/her all the very best.</p>

    <!-- ── Signature ── -->
    <div class="sign-block">
        <div class="sign-line"></div>
        <strong><?= htmlspecialchars($letter['signatory_name']) ?></strong><br>
        <em><?= htmlspecialchars($letter['signatory_title']) ?></em><br>
        Human Resources Department<br>
        <?= htmlspecialchars(getSetting('website_title','The Premium Homes Limited')) ?>
    </div>

</div><!-- /.page-wrapper -->

</body>
</html>

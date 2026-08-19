<?php
$empTypeLabel = [
    'permanent' => 'Permanent',
    'contract' => 'Contractual',
    'director' => 'Director',
    'intern' => 'Intern',
    'remote' => 'Remote',
    'probation' => 'Probationary',
    'management' => 'Management',
];
$empType = $empTypeLabel[$letter['employee_type'] ?? 'permanent'] ?? 'Permanent';
$certMonth = '';
if (!empty($letter['certificate_month'])) {
    $certMonth = date('F Y', strtotime($letter['certificate_month'] . '-01'));
}
$refNum = $letter['ref_number'] ?? ('TPHL/HR/Salary Certificate/' . $certMonth);
?>
<style>
    body {
        font-family: helvetica;
        font-size: 11pt;
        color: #333;
        line-height: 1.6;
    }

    .cert-title {
        font-size: 13pt;
        font-weight: bold;
        text-align: center;
        text-decoration: underline;
        letter-spacing: 1px;
    }

    .salary-table {
        width: 100%;
        border-collapse: collapse;
        margin: 14px 0;
        font-size: 10.5pt;
    }

    .salary-table th,
    .salary-table td {
        border: 1px solid #aaa;
        padding: 7px 12px;
    }

    .salary-table th {
        background: #eeeeee;
        font-weight: bold;
        text-align: center;
    }

    .salary-table .amount {
        text-align: right;
    }

    .total-row td {
        background: #d4edda;
        font-weight: bold;
    }

    .deduct-row td {
        background: #fff3f3;
    }

    .sign-block {
        margin-top: 40px;
    }
</style>

<!-- Ref & Date -->
<table width="100%" style="margin-top:8px; margin-bottom:10px;">
    <tr>
        <td align="left"><strong>Ref:</strong> <?= htmlspecialchars($refNum) ?></td>
        <td align="right"><strong>Date:</strong> <?= date('d F, Y', strtotime($letter['letter_date'])) ?></td>
    </tr>
</table>

<!-- Title -->
<p class="cert-title">To Whom It May Concern</p>

<!-- Opening -->
<p>
    This is to certify that <strong>Mr./Ms. <?= htmlspecialchars($letter['full_name']) ?></strong>
    (Employee ID: <?= htmlspecialchars($letter['employee_code']) ?>) is a
    <strong><?= $empType ?></strong> employee of
    <strong><?= getSetting('website_title', 'The Premium Homes Ltd.') ?></strong>
    and is employed as <strong><?= htmlspecialchars($letter['designation_name'] ?? '') ?> –
        <?= htmlspecialchars($letter['department_name'] ?? '') ?></strong>
    since
    <strong><?= !empty($letter['emp_joining_date']) ? date('d F, Y', strtotime($letter['emp_joining_date'])) : 'N/A' ?></strong>.
</p>

<p>According to our records, his/her current monthly salary breakdown for <strong><?= $certMonth ?></strong> is as
    follows:</p>

<!-- Earnings Table -->
<table class="salary-table">
    <thead>
        <tr>
            <th width="60%" align="left">Earning</th>
            <th width="40%" align="right">Amount (BDT)</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $totalEarning = 0;
        foreach ($salaryItems['earning'] as $item):
            $totalEarning += $item['amount'];
            ?>
            <tr>
                <td><?= htmlspecialchars($item['label']) ?></td>
                <td class="amount"><?= number_format($item['amount'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr class="total-row">
            <td><strong>Gross Salary</strong></td>
            <td class="amount"><strong><?= number_format($letter['gross_salary'] ?? $totalEarning, 2) ?> (BDT)</strong>
            </td>
        </tr>
    </tbody>
</table>

<!-- Deductions (if any) -->
<?php if (!empty($salaryItems['deduction'])): ?>
    <table class="salary-table">
        <thead>
            <tr>
                <th width="60%" align="left">Deduction</th>
                <th width="40%" align="right">Amount (BDT)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $totalDeduction = 0;
            foreach ($salaryItems['deduction'] as $item):
                $totalDeduction += $item['amount'];
                ?>
                <tr class="deduct-row">
                    <td><?= htmlspecialchars($item['label']) ?></td>
                    <td class="amount"><?= number_format($item['amount'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td><strong>Net Salary</strong></td>
                <td class="amount">
                    <strong><?= number_format($letter['net_salary'] ?? ($totalEarning - $totalDeduction), 2) ?>
                        (BDT)</strong></td>
            </tr>
        </tbody>
    </table>
<?php endif; ?>

<!-- Closing -->
<p>
    This certificate is issued upon his/her request for the purpose of
    <strong><?= htmlspecialchars($letter['purpose'] ?? 'personal use') ?></strong>
    without any liability on the part of this company.
</p>
<p>We wish him/her all the very best.</p>

<!-- Signature -->
<div class="sign-block">
    <br><br>
    <strong><?= htmlspecialchars($letter['signatory_name']) ?></strong><br>
    <i><?= htmlspecialchars($letter['signatory_title']) ?></i><br>
    Human Resources Department<br>
    <?= getSetting('website_title', 'The Premium Homes Limited') ?>
</div>
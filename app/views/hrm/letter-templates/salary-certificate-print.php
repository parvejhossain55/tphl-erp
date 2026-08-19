<?php
$empTypeLabel = ['permanent' => 'Permanent', 'contract' => 'Contractual', 'director' => 'Director', 'intern' => 'Intern', 'remote' => 'Remote', 'probation' => 'Probationary', 'management' => 'Management'];
$empType = $empTypeLabel[$letter['employee_type'] ?? 'permanent'] ?? 'Permanent';
$certMonth = !empty($letter['certificate_month']) ? date('F Y', strtotime($letter['certificate_month'] . '-01')) : '';
$refNum = $letter['ref_number'] ?? ('TPHL/HR/Salary Certificate/' . $certMonth);
?><!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Certificate — <?= htmlspecialchars($letter['full_name']) ?></title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }

        *,
        *::before,
        *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            width: 210mm;
            height: 297mm;
            background: #fff;
            color: #333;
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.7;
        }

        /* Full-page letterhead background */
        .page-wrapper {
            width: 210mm;
            height: 297mm;
            position: relative;
            background-image: url('<?= $GLOBALS['base_url'] ?>/assets/email-template/new-letter-template.jpeg');
            background-size: 100% 100%;
            background-repeat: no-repeat;
            background-position: top left;
        }

        /* Content sits in the white area: header ~33mm, footer ~42mm */
        .letter-content {
            position: absolute;
            top: 33mm;
            left: 18mm;
            right: 18mm;
            bottom: 44mm;
            overflow: hidden;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10pt;
            font-size: 11px;
        }

        .cert-title {
            text-align: center;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            text-decoration: underline;
            margin: 16pt 0 12pt;
        }

        p {
            margin-bottom: 7px;
        }

        .salary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0 14px;
            font-size: 11px;
            table-layout: fixed;
        }

        .salary-table th,
        .salary-table td {
            border: 1px solid #aaa;
            padding: 6px 10px;
            word-wrap: break-word;
        }

        .salary-table thead tr {
            background: #eeeeee;
        }

        .salary-table th {
            font-weight: 700;
            text-align: center;
        }

        .amount {
            text-align: right;
        }

        .total-row td {
            background: #d4edda;
            font-weight: 700;
        }

        .deduct-row td {
            background: #fff3f3;
        }

        .sign-block {
            margin-top: 36pt;
        }

        .sign-line {
            border-top: 1px solid #555;
            width: 180px;
            margin-top: 36pt;
            margin-bottom: 5px;
        }

        @media print {

            html,
            body {
                width: 210mm;
                height: 297mm;
            }

            .page-wrapper {
                width: 210mm;
                height: 297mm;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body onload="window.print()">
    <div class="page-wrapper">
        <div class="letter-content">

            <!-- Ref & Date -->
            <div class="meta-row">
                <span><strong>Ref:</strong> <?= htmlspecialchars($refNum) ?></span>
                <span><strong>Date:</strong> <?= date('d F, Y', strtotime($letter['letter_date'])) ?></span>
            </div>

            <!-- Title -->
            <div class="cert-title">To Whom It May Concern</div>

            <!-- Opening paragraph -->
            <p>
                This is to certify that <strong>Mr./Ms. <?= htmlspecialchars($letter['full_name']) ?></strong>
                (Employee ID: <?= htmlspecialchars($letter['employee_code']) ?>) is a
                <strong><?= $empType ?></strong> employee of
                <strong><?= htmlspecialchars(getSetting('website_title', 'The Premium Homes Ltd.')) ?></strong>
                and is employed as <strong><?= htmlspecialchars($letter['designation_name'] ?? '') ?>
                    – <?= htmlspecialchars($letter['department_name'] ?? '') ?></strong>
                since
                <strong><?= !empty($letter['emp_joining_date']) ? date('d F, Y', strtotime($letter['emp_joining_date'])) : 'N/A' ?></strong>.
            </p>
            <p>According to our records, his/her current monthly salary breakdown for <strong><?= $certMonth ?></strong>
                is as follows:</p>

            <!-- Earnings Table -->
            <table class="salary-table">
                <thead>
                    <tr>
                        <th width="62%" style="text-align:left;">Earning</th>
                        <th width="38%" style="text-align:right;">Amount (BDT)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $totalEarning = 0;
                    foreach ($salaryItems['earning'] as $item):
                        $totalEarning += $item['amount']; ?>
                        <tr>
                            <td><?= htmlspecialchars($item['label']) ?></td>
                            <td class="amount"><?= number_format($item['amount'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td><strong>Gross Salary</strong></td>
                        <td class="amount"><strong><?= number_format($letter['gross_salary'] ?? $totalEarning, 2) ?>
                                (BDT)</strong></td>
                    </tr>
                </tbody>
            </table>

            <!-- Deductions Table -->
            <?php if (!empty($salaryItems['deduction'])): ?>
                <table class="salary-table">
                    <thead>
                        <tr>
                            <th width="62%" style="text-align:left;">Deduction</th>
                            <th width="38%" style="text-align:right;">Amount (BDT)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $totalDeduction = 0;
                        foreach ($salaryItems['deduction'] as $item):
                            $totalDeduction += $item['amount']; ?>
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

            <p>
                This certificate is issued upon his/her request for the purpose of
                <strong><?= htmlspecialchars($letter['purpose'] ?? 'personal use') ?></strong>
                without any liability on the part of this company.
            </p>
            <p>We wish him/her all the very best.</p>

            <!-- Signature -->
            <div class="sign-block">
                <div class="sign-line"></div>
                <strong><?= htmlspecialchars($letter['signatory_name']) ?></strong><br>
                <em><?= htmlspecialchars($letter['signatory_title']) ?></em><br>
                Human Resources Department<br>
                <?= htmlspecialchars(getSetting('website_title', 'The Premium Homes Limited')) ?>
            </div>

        </div><!-- /.letter-content -->
    </div><!-- /.page-wrapper -->
</body>

</html>
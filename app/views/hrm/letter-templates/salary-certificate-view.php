<?php
// Helper: map employee_type to readable label
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
$salutation = 'Mr./Ms.';
$certMonth = '';
if (!empty($letter['certificate_month'])) {
    $certMonth = date('F Y', strtotime($letter['certificate_month'] . '-01'));
}
$refNum = $letter['ref_number'] ?? ('TPHL/HR/Salary Certificate/' . $certMonth);
?>

<style>
    .letter-container {
        padding: 40px 20px;
        display: flex;
        justify-content: center;
    }

    .letter-paper {
        width: 210mm;
        min-height: 297mm;
        position: relative;
        background-image: url('<?= $GLOBALS['base_url'] ?>/assets/email-template/new-letter-template.jpeg');
        background-size: 100% 100%;
        background-repeat: no-repeat;
        background-position: top left;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .15);
        font-family: 'Inter', 'Segoe UI', serif;
        line-height: 1.7;
        font-size: 13px;
        color: #222;
    }

    /* Content sits inside the white area: header ~130px, footer ~170px at screen resolution */
    .letter-content-overlay {
        position: absolute;
        top: 130px;
        left: 30px;
        right: 30px;
        bottom: 180px;
        overflow: hidden;
    }

    .cert-title {
        text-align: center;
        font-size: 15px;
        font-weight: 700;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        margin: 20px 0 18px;
        text-decoration: underline;
    }

    .salary-table {
        width: 100%;
        border-collapse: collapse;
        margin: 12px 0 16px;
        font-size: 12.5px;
    }

    .salary-table th,
    .salary-table td {
        border: 1px solid #aaa;
        padding: 7px 12px;
        text-align: left;
    }

    .salary-table thead tr {
        background: #f0f0f0;
    }

    .salary-table th {
        font-weight: 700;
        text-align: center;
    }

    .salary-table .amount {
        text-align: right;
        font-weight: 500;
    }

    .salary-table .total-row td {
        background: #e8f5e9;
        font-weight: 700;
    }

    .salary-table .deduct-row td {
        background: #fff3f3;
    }

    .signature-block {
        margin-top: 40px;
    }

    .sign-line {
        border-top: 1px solid #555;
        width: 180px;
        margin-top: 40px;
        margin-bottom: 4px;
    }

    @media print {
        body * {
            visibility: hidden;
        }

        #printableArea,
        #printableArea * {
            visibility: visible;
        }

        #printableArea {
            position: absolute;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            padding: 0;
            box-shadow: none;
        }

        .card__wrapper {
            background: none;
            padding: 0 !important;
        }
    }
</style>

<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <!-- Action bar -->
            <div class="card__title-wrap mb-20 d-flex align-items-center justify-content-between">
                <h5 class="card__heading-title">Salary Certificate Preview</h5>
                <div class="d-flex gap-2">
                    <a href="<?= $GLOBALS['base_url'] ?>/generate-letter" class="btn btn-secondary btn-sm">
                        <i class="fa fa-arrow-left"></i> Back
                    </a>
                    <a href="<?= $GLOBALS['base_url'] ?>/edit-letter/<?= $letter['id'] ?>"
                        class="btn btn-primary btn-sm">
                        <i class="fa fa-edit"></i> Edit
                    </a>
                    <a href="<?= $GLOBALS['base_url'] ?>/pdf-letter/<?= $letter['id'] ?>" class="btn btn-danger btn-sm"
                        target="_blank">
                        <i class="fa fa-file-pdf"></i> Download PDF
                    </a>
                    <a href="<?= $GLOBALS['base_url'] ?>/print-letter/<?= $letter['id'] ?>"
                        class="btn btn-info btn-sm text-white" target="_blank">
                        <i class="fa fa-print"></i> Print
                    </a>
                </div>
            </div>

            <!-- Letter Paper with template background -->
            <div class="letter-container">
                <div class="letter-paper" id="printableArea">
                    <div class="letter-content-overlay">

                        <!-- Ref & Date -->
                        <div class="d-flex justify-content-between mb-3">
                            <div><strong>Ref:</strong> <?= htmlspecialchars($refNum) ?></div>
                            <div><strong>Date:</strong> <?= date('d F, Y', strtotime($letter['letter_date'])) ?></div>
                        </div>

                        <!-- Title -->
                        <div class="cert-title">To Whom It May Concern</div>

                        <!-- Opening paragraph -->
                        <p>
                            This is to certify that <strong><?= $salutation ?>
                                <?= htmlspecialchars($letter['full_name']) ?></strong>
                            (Employee ID: <?= htmlspecialchars($letter['employee_code']) ?>) is a
                            <strong><?= $empType ?></strong> employee of
                            <strong><?= getSetting('website_title', 'The Premium Homes Ltd.') ?></strong> and
                            is employed as <strong><?= htmlspecialchars($letter['designation_name'] ?? 'N/A') ?> –
                                <?= htmlspecialchars($letter['department_name'] ?? 'N/A') ?></strong>
                            since
                            <strong><?= !empty($letter['emp_joining_date']) ? date('d F, Y', strtotime($letter['emp_joining_date'])) : 'N/A' ?></strong>.
                        </p>

                        <p>According to our records, his/her current monthly salary breakdown for
                            <strong><?= $certMonth ?></strong> is as follows:</p>

                        <!-- Salary Table -->
                        <table class="salary-table">
                            <thead>
                                <tr>
                                    <th style="width:60%; text-align:left;">Earning</th>
                                    <th style="width:40%; text-align:right;">Amount (BDT)</th>
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
                                    <td class="amount">
                                        <strong><?= number_format($letter['gross_salary'] ?? $totalEarning, 2) ?>
                                            (BDT)</strong></td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Deductions (if any) -->
                        <?php if (!empty($salaryItems['deduction'])): ?>
                            <table class="salary-table">
                                <thead>
                                    <tr>
                                        <th style="width:60%; text-align:left;">Deduction</th>
                                        <th style="width:40%; text-align:right;">Amount (BDT)</th>
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

                        <!-- Closing paragraph -->
                        <p class="mt-3">
                            This certificate is issued upon his/her request for the purpose of
                            <strong><?= htmlspecialchars($letter['purpose'] ?? 'personal use') ?></strong>
                            without any liability on the part of this company.
                        </p>
                        <p>We wish him/her all the very best.</p>

                        <!-- Signature -->
                        <div class="signature-block">
                            <div class="sign-line"></div>
                            <strong><?= htmlspecialchars($letter['signatory_name']) ?></strong><br>
                            <span class="text-muted"><?= htmlspecialchars($letter['signatory_title']) ?></span><br>
                            <span>Human Resources Department</span><br>
                            <span><?= getSetting('website_title', 'The Premium Homes Limited') ?></span>
                        </div>

                    </div><!-- /.letter-content-overlay -->
                </div><!-- /.letter-paper -->
            </div><!-- /.letter-container -->
        </div>
    </div>
</div>
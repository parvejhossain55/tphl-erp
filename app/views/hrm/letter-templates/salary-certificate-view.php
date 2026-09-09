<?php
// Helper: map employee_type to readable label
$empTypeLabel = [
    'permanent' => 'permanent',
    'contract' => 'contractual',
    'director' => 'director',
    'intern' => 'intern',
    'remote' => 'remote',
    'probation' => 'probationary',
    'management' => 'management',
];
$empType = $empTypeLabel[$letter['employee_type'] ?? 'permanent'] ?? 'permanent';

$rawName = trim($letter['full_name'] ?? '');

// Gender pronoun detection
$isFemale = false;
$nameLower = strtolower($rawName);
$femaleKeywords = ['ms.', 'mrs.', 'miss', 'khatun', 'begum', 'akter', 'akther', 'sultana', 'jahan', 'nahar', 'parvin', 'nasrin', 'farzana', 'roksana', 'shammi', 'bornali'];
foreach ($femaleKeywords as $kw) {
    if (strpos($nameLower, $kw) !== false) {
        $isFemale = true;
        break;
    }
}
$herHis = $isFemale ? 'her' : 'his';
$sheHe = $isFemale ? 'she' : 'he';
$herHim = $isFemale ? 'her' : 'him';

$certMonth = '';
if (!empty($letter['certificate_month'])) {
    $certMonth = date('F-Y', strtotime($letter['certificate_month'] . '-01'));
}
$refNum = !empty($letter['ref_number']) ? $letter['ref_number'] : ('TPHL/HR/Salary Certificate/' . ($certMonth ?: date('F-Y')));

$companyName = function_exists('getSetting') ? getSetting('website_title', 'The Premium Homes Ltd.') : 'The Premium Homes Ltd.';
if (stripos($companyName, 'Homes') === false && stripos($companyName, 'Home') !== false) {
    $companyName = str_ireplace('Home', 'Homes', $companyName);
}
$companyFullName = $companyName;

$baseDir = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4);
$sigUrl = '';
if (!empty($letter['custom_signature']) && file_exists($baseDir . '/' . ltrim($letter['custom_signature'], '/\\'))) {
    $sigUrl = $GLOBALS['base_url'] . '/' . ltrim($letter['custom_signature'], '/\\');
} elseif (file_exists($baseDir . '/public/assets/images/hrm/hr_signature.png')) {
    $sigUrl = $GLOBALS['base_url'] . '/public/assets/images/hrm/hr_signature.png';
}
if (stripos($companyFullName, 'Limited') === false) {
    $companyFullName = str_ireplace('Ltd.', 'Limited', $companyFullName);
    if (stripos($companyFullName, 'Limited') === false) {
        $companyFullName .= ' Limited';
    }
}

// Dates
$startDate = !empty($letter['joining_date']) ? date('d F Y', strtotime($letter['joining_date'])) : (!empty($letter['emp_joining_date']) ? date('d F Y', strtotime($letter['emp_joining_date'])) : 'N/A');
$endDate = !empty($letter['effective_date']) ? date('d F Y', strtotime($letter['effective_date'])) : (!empty($letter['emp_resign_date']) ? date('d F Y', strtotime($letter['emp_resign_date'])) : '');

$periodText = $startDate;
if (!empty($endDate)) {
    $periodText = "from " . $startDate . " to " . $endDate;
} else {
    $periodText = "from " . $startDate . " to present";
}

$purposeText = !empty($letter['purpose']) ? $letter['purpose'] : 'final settlement';
if (strtolower($purposeText) === 'personal use') {
    $purposeText = 'personal use';
}
?>

<style>
    .letter-container {
        padding: 30px 15px;
        display: flex;
        justify-content: center;
        background-color: #f1f5f9;
    }

    .letter-paper {
        width: 210mm;
        min-height: 297mm;
        position: relative;
        background-color: #ffffff;
        background-image: url('<?= $GLOBALS['base_url'] ?>/assets/email-template/new-letter-template.jpeg');
        background-size: 100% 100%;
        background-repeat: no-repeat;
        background-position: top left;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
        color: #111827;
        box-sizing: border-box;
    }

    /* Content perfectly aligned within safe zone */
    .letter-content-overlay {
        position: absolute;
        top: 32mm;
        left: 20mm;
        right: 20mm;
        bottom: 35mm;
        overflow: hidden;
    }

    .sc-meta-stacked {
        font-size: 13px;
        line-height: 1.6;
        color: #000;
        margin-bottom: 24px;
    }

    .sc-meta-stacked .sc-meta-line {
        font-weight: 600;
    }

    .sc-title-centered {
        font-size: 15.5px;
        font-weight: 800;
        text-align: center;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        color: #000;
        margin: 20px 0 24px 0;
    }

    .sc-text {
        text-align: justify;
        text-justify: inter-word;
        font-size: 13.5px;
        line-height: 1.75;
        color: #000;
        margin-bottom: 16px;
    }

    .sc-table {
        width: 100%;
        border-collapse: collapse;
        margin: 16px 0 22px;
        font-size: 13px;
        border: 1px solid #000;
    }

    .sc-table th,
    .sc-table td {
        border: 1px solid #000;
        padding: 7px 12px;
        vertical-align: middle;
        color: #000;
    }

    .sc-table thead th {
        background-color: #ffffff;
        color: #000;
        font-weight: 700;
        font-size: 13px;
        text-align: center;
    }

    .sc-table tbody td.col-earning {
        padding-left: 20px;
        font-size: 13px;
        color: #000;
    }

    .sc-table tbody td.col-amount {
        text-align: center;
        font-size: 13px;
        color: #000;
        font-weight: 500;
    }

    .sc-table .total-row td {
        background-color: #ffffff;
        color: #000;
        font-weight: 700;
        border-top: 1px solid #000;
    }

    .sc-signature-block {
        margin-top: 36px;
        display: block;
    }

    .sc-signature-space {
        height: 40px;
    }

    .sc-sign-line {
        font-size: 13px;
        color: #000;
        margin-bottom: 5px;
        letter-spacing: -1px;
    }

    .sc-sign-dept {
        font-size: 13px;
        font-weight: 600;
        color: #000;
        margin-top: 2px;
    }

    .sc-sign-company {
        font-size: 13px;
        font-weight: 600;
        color: #000;
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
            width: 210mm;
            height: 297mm;
            box-shadow: none;
            background-color: #fff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
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
                        <i class="fa fa-arrow-left me-1"></i> Back to List
                    </a>
                    <a href="<?= $GLOBALS['base_url'] ?>/edit-letter/<?= $letter['id'] ?>"
                        class="btn btn-primary btn-sm">
                        <i class="fa fa-edit me-1"></i> Edit
                    </a>
                    <a href="<?= $GLOBALS['base_url'] ?>/pdf-letter/<?= $letter['id'] ?>" class="btn btn-danger btn-sm"
                        target="_blank">
                        <i class="fa fa-file-pdf me-1"></i> Download PDF
                    </a>
                    <a href="<?= $GLOBALS['base_url'] ?>/print-letter/<?= $letter['id'] ?>"
                        class="btn btn-info btn-sm text-white" target="_blank">
                        <i class="fa fa-print me-1"></i> Print
                    </a>
                </div>
            </div>

            <!-- Letter Paper with template background -->
            <div class="letter-container">
                <div class="letter-paper" id="printableArea">
                    <div class="letter-content-overlay">

                        <!-- Ref & Date (Stacked Left) -->
                        <div class="sc-meta-stacked">
                            <div class="sc-meta-line"><strong>Ref:</strong> <?= htmlspecialchars($refNum) ?></div>
                            <div class="sc-meta-line"><strong>Date:</strong> <?= date('d F, Y', strtotime($letter['letter_date'])) ?></div>
                        </div>

                        <!-- Title -->
                        <div class="sc-title-centered">TO WHOM IT MAY CONCERN</div>

                        <!-- Opening paragraph -->
                        <p class="sc-text">
                            This is to certify that <strong><?= htmlspecialchars($rawName) ?></strong> (Employee ID: <?= htmlspecialchars($letter['employee_code']) ?>) was a <?= htmlspecialchars($empType) ?> employee of <strong><?= htmlspecialchars($companyName) ?></strong> and <?= $sheHe ?> was employed as <strong><?= htmlspecialchars($letter['designation_name'] ?? 'N/A') ?></strong> <?= $periodText ?>.
                        </p>

                        <p class="sc-text">
                            According to our records, <?= $herHis ?> monthly salary breakdown was as follows:
                        </p>

                        <!-- Salary Table -->
                        <table class="sc-table">
                            <thead>
                                <tr>
                                    <th style="width:60%;">Earning</th>
                                    <th style="width:40%;">Amount (BDT)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $totalEarning = 0;
                                foreach ($salaryItems['earning'] as $item):
                                    $totalEarning += (float) $item['amount']; ?>
                                    <tr>
                                        <td class="col-earning"><?= htmlspecialchars($item['label']) ?></td>
                                        <td class="col-amount"><?= number_format((float) $item['amount']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="total-row">
                                    <td class="col-earning"><strong>Gross Salary</strong></td>
                                    <td class="col-amount">
                                        <strong><?= number_format((float) ($letter['gross_salary'] ?? $totalEarning)) ?> (BDT)</strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Closing paragraph -->
                        <p class="sc-text mt-3">
                            This certificate is issued upon <?= $herHis ?> request for <?= htmlspecialchars($purposeText) ?> without any liability on the part of this company.
                        </p>
                        <p class="sc-text">We wish <?= $herHim ?> all the very best.</p>

                        <!-- Signature Block -->
                        <div class="sc-signature-block">
                            <?php if ((!isset($letter['include_signature']) || $letter['include_signature'] == 1) && !empty($sigUrl)): ?>
                                <div style="height: 48px; margin-bottom: -15px;">
                                    <img src="<?= $sigUrl ?>" alt="Signature" style="height: 45px; max-width: 170px; object-fit: contain;">
                                </div>
                            <?php else: ?>
                                <div class="sc-signature-space"></div>
                            <?php endif; ?>
                            <div class="sc-sign-line">______________________________</div>
                            <div class="sc-sign-dept">Human Resources Department</div>
                            <div class="sc-sign-company"><?= htmlspecialchars($companyFullName) ?></div>
                        </div>

                    </div><!-- /.letter-content-overlay -->
                </div><!-- /.letter-paper -->
            </div><!-- /.letter-container -->

        </div>
    </div>
</div>
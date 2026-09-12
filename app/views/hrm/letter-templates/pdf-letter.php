<?php
$rawName = trim($letter['full_name'] ?? '');
if (preg_match('/^(Mr\.|Ms\.|Mrs\.|Dr\.|Engr\.)\s+/i', $rawName)) {
    $recipientName = htmlspecialchars($rawName);
} else {
    $recipientName = 'Mr./Ms. ' . htmlspecialchars($rawName);
}

$companyName = function_exists('getSetting') ? getSetting('website_title', 'The Premium Homes Limited') : 'The Premium Homes Limited';
if (stripos($companyName, 'Homes') === false && stripos($companyName, 'Home') !== false) {
    $companyName = str_ireplace('Home', 'Homes', $companyName);
}
if (stripos($companyName, 'Limited') === false && stripos($companyName, 'Ltd') === false) {
    $companyName .= ' Limited';
}

$isCaution = ($letter['letter_type'] === 'caution');
$isExperience = ($letter['letter_type'] === 'experience');

if ($isExperience) {
    $refNum = !empty($letter['ref_number']) ? $letter['ref_number'] : ('TPHL/HR/Experience Certificate/' . date('M-Y', strtotime($letter['letter_date'])));
} else {
    $refNum = !empty($letter['ref_number']) ? $letter['ref_number'] : ('TPHL/HR/' . date('Y', strtotime($letter['letter_date'])) . '/' . str_pad($letter['id'], 4, '0', STR_PAD_LEFT));
}

// Ensure non-salary-certificate letters never display salary certificate ref numbers
if ($letter['letter_type'] !== 'salary_certificate' && !empty($refNum) && stripos($refNum, 'Salary Certificate') !== false) {
    $refNum = 'TPHL/HR/' . date('Y', strtotime($letter['letter_date'])) . '/' . str_pad($letter['id'], 4, '0', STR_PAD_LEFT);
}

// Address sanitization: avoid literal 'N/A', 'NA', 'None', etc.
$cleanAddress = trim($letter['address'] ?? '');
$hasAddress = !empty($cleanAddress) && !in_array(strtoupper($cleanAddress), ['N/A', 'NA', 'NONE', 'NULL', '-']);

$pdfBody = $letter['body'] ?? '';
// Clean trailing empty paragraphs & excessive line breaks
$pdfBody = preg_replace('/(<p[^>]*>(\s|&nbsp;|<br\s*\/?>)*<\/p>\s*)+$/i', '', trim($pdfBody));

// Detect manual page breaks
$hasExplicitPageBreak = (bool) preg_match('/(<div[^>]*class=["\'][^"\']*page-break[^"\']*["\'][^>]*>|<!--\s*pagebreak\s*-->|<hr[^>]*class=["\'][^"\']*page-break[^"\']*["\'][^>]*>)/i', $pdfBody);

$fitParam = isset($_GET['fit']) ? trim($_GET['fit']) : ($fitMode ?? '');
$isCompact = false;
$isUltra = false;
if ($fitParam === 'ultra') {
    $isUltra = true;
    $isCompact = true;
} elseif ($fitParam === '1' || $fitParam === 'compact') {
    $isCompact = true;
} elseif ($fitParam !== '0' && $fitParam !== 'normal' && !$hasExplicitPageBreak) {
    // Single page letter without explicit page breaks: default to compact mode so signature stays on Page 1!
    $isCompact = true;
}

$plainLength = strlen(strip_tags($pdfBody));
if ($isCompact && !$isUltra && $plainLength > 1500) {
    // Letter has extensive multi-clause content; auto-upgrade to ultra compact so it fits strictly on 1 page
    $isUltra = true;
}


// Styling variables for Dompdf
$topMargin = $isUltra ? '29mm' : ($isCompact ? '31mm' : '33mm');
$bottomMargin = $isUltra ? '27mm' : ($isCompact ? '29mm' : '33mm');
$bodyFontSize = $isUltra ? '10.5px' : ($isCompact ? '11.5px' : '13px');
$bodyLineHeight = $isUltra ? '1.32' : ($isCompact ? '1.40' : '1.55');
$paraMargin = $isUltra ? '3px' : ($isCompact ? '4px' : '7px');
$metaMargin = $isUltra ? '6px' : ($isCompact ? '10px' : '18px');
$recipientMargin = $isUltra ? '6px' : ($isCompact ? '10px' : '18px');
$subjectMargin = $isUltra ? '6px 0' : ($isCompact ? '8px 0 10px' : '16px 0');
$extraBoxMargin = $isUltra ? '6px 0 8px' : ($isCompact ? '8px 0 10px' : '14px 0 18px');
$extraBoxPadding = $isUltra ? '4px 8px' : ($isCompact ? '6px 10px' : '8px 12px');
$sigSectionMargin = $isUltra ? '8px' : ($isCompact ? '10px' : '14px');
$sigBlockMargin = $isUltra ? '4px' : ($isCompact ? '6px' : '10px');
$sigImgHeight = $isUltra ? '30px' : ($isCompact ? '36px' : '45px');
$sigBoxHeight = $isUltra ? '32px' : ($isCompact ? '38px' : '48px');
$signLineWidth = $isUltra ? '140px' : ($isCompact ? '160px' : '180px');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Official Letter - <?= htmlspecialchars($letter['subject']) ?></title>
<style>
    @page {
        size: 210mm 297mm;
        margin-top: <?= $topMargin ?>;
        margin-bottom: <?= $bottomMargin ?>;
        margin-left: 20mm;
        margin-right: 20mm;
    }
    * {
        box-sizing: border-box;
    }
    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: <?= $bodyFontSize ?>;
        line-height: <?= $bodyLineHeight ?>;
        color: #111827;
        margin: 0;
        padding: 0;
    }
    .page-break {
        page-break-after: always;
        break-after: page;
        height: 0;
        line-height: 0;
        font-size: 0;
        margin: 0;
        padding: 0;
        border: none;
        display: block;
        clear: both;
    }
    .letter-content {
        width: 100%;
    }

    /* ═══ Prevent Awkward Page Splitting ═══ */
    .letter-meta,
    .letter-recipient,
    .letter-subject,
    .caution-header-title,
    .caution-date,
    .caution-recipient-block,
    .caution-subject,
    .caution-salutation,
    .caution-signoff,
    .cert-meta-stacked,
    .cert-title-centered,
    .experience-signoff,
    .letter-extra-box,
    .table-extra-details,
    .letter-signature-section,
    .signature-block {
        page-break-inside: avoid;
        break-inside: avoid;
    }

    /* ═══ Standard Letter Styling ═══ */
    .letter-meta {
        width: 100%;
        margin-bottom: <?= $metaMargin ?>;
        font-size: <?= $isCompact ? '11px' : '12.5px' ?>;
        color: #111827;
    }
    .letter-recipient {
        margin-bottom: <?= $recipientMargin ?>;
        font-size: <?= $isCompact ? '11.5px' : '13px' ?>;
        line-height: <?= $isCompact ? '1.35' : '1.45' ?>;
        color: #111827;
    }
    .letter-recipient .recipient-to {
        font-weight: bold;
        margin-bottom: 2px;
        color: #000;
    }
    .letter-recipient .recipient-name {
        font-size: <?= $isCompact ? '13px' : '14px' ?>;
        font-weight: bold;
        color: #000;
        margin-bottom: 2px;
    }
    .letter-recipient .recipient-detail {
        color: #111827;
    }
    .letter-recipient .recipient-id {
        margin-top: 1px;
        font-size: <?= $isCompact ? '11px' : '12.5px' ?>;
    }
    .letter-recipient .recipient-address {
        margin-top: 3px;
        font-size: <?= $isCompact ? '11px' : '12.5px' ?>;
        color: #4b5563;
    }
    .letter-subject {
        margin: <?= $subjectMargin ?>;
        text-align: left;
    }
    .letter-subject .subject-text {
        font-size: <?= $isCompact ? '12px' : '13px' ?>;
        font-weight: bold;
        text-decoration: underline;
        color: #000;
    }
    .letter-body {
        font-size: <?= $bodyFontSize ?>;
        line-height: <?= $bodyLineHeight ?>;
        color: #111827;
        text-align: justify;
    }
    .letter-body p {
        margin-top: 0;
        margin-bottom: <?= $paraMargin ?>;
    }
    .letter-body h1, .letter-body h2, .letter-body h3, .letter-body h4 {
        margin-top: <?= $isCompact ? '5px' : '8px' ?>;
        margin-bottom: 2px;
        font-size: <?= $isCompact ? '12px' : '13px' ?>;
        color: #0f172a;
        page-break-after: avoid;
    }
    .letter-body ul, .letter-body ol {
        margin-top: 0;
        margin-bottom: <?= $paraMargin ?>;
        padding-left: <?= $isCompact ? '16px' : '20px' ?>;
    }
    .letter-body li {
        margin-bottom: <?= $isCompact ? '2px' : '3px' ?>;
    }

    /* ═══ Caution Letter Specific Styling ═══ */
    .caution-header-title {
        font-size: <?= $isCompact ? '14.5px' : '16px' ?>;
        font-weight: bold;
        text-align: center;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        color: #000;
        margin-bottom: <?= $isCompact ? '14px' : '22px' ?>;
    }
    .caution-date {
        font-size: <?= $isCompact ? '11.5px' : '12.5px' ?>;
        font-weight: bold;
        color: #000;
        margin-bottom: <?= $isCompact ? '4px' : '8px' ?>;
    }
    .caution-recipient-block {
        font-size: <?= $isCompact ? '11.5px' : '12.5px' ?>;
        line-height: <?= $isCompact ? '1.35' : '1.45' ?>;
        color: #000;
        margin-bottom: <?= $recipientMargin ?>;
    }
    .caution-recipient-block .caution-to {
        font-weight: bold;
        margin-bottom: 2px;
    }
    .caution-recipient-block .caution-emp-name {
        font-weight: bold;
        font-size: <?= $isCompact ? '12.5px' : '13.5px' ?>;
    }
    .caution-recipient-block .caution-line {
        color: #111;
    }
    .caution-subject {
        font-size: <?= $isCompact ? '12px' : '13px' ?>;
        margin: <?= $subjectMargin ?>;
        color: #000;
    }
    .caution-subject strong {
        color: #000;
    }
    .caution-salutation {
        font-size: <?= $isCompact ? '12px' : '13px' ?>;
        font-weight: bold;
        color: #000;
        margin-bottom: <?= $isCompact ? '8px' : '12px' ?>;
    }
    .caution-body {
        font-size: <?= $bodyFontSize ?>;
        line-height: <?= $isCompact ? '1.45' : '1.72' ?>;
        color: #000;
    }
    .caution-signoff {
        margin-top: <?= $isCompact ? '20px' : '36px' ?>;
        font-size: <?= $isCompact ? '12px' : '13px' ?>;
        font-weight: bold;
        color: #000;
    }

    /* ═══ Experience Certificate Specific Styling ═══ */
    .cert-meta-stacked {
        font-size: <?= $isCompact ? '11.5px' : '12.5px' ?>;
        line-height: <?= $isCompact ? '1.4' : '1.6' ?>;
        color: #000;
        margin-bottom: <?= $isCompact ? '14px' : '22px' ?>;
    }
    .cert-meta-stacked .meta-line {
        font-weight: bold;
    }
    .cert-title-centered {
        font-size: <?= $isCompact ? '14px' : '15px' ?>;
        font-weight: bold;
        text-align: center;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        color: #000;
        margin: <?= $isCompact ? '12px 0 14px 0' : '18px 0 22px 0' ?>;
    }
    .experience-body {
        font-size: <?= $bodyFontSize ?>;
        line-height: <?= $isCompact ? '1.48' : '1.8' ?>;
        color: #000;
    }
    .experience-signoff {
        margin-top: <?= $isCompact ? '18px' : '30px' ?>;
        font-size: <?= $isCompact ? '11.5px' : '12.5px' ?>;
        line-height: 1.45;
        color: #000;
    }
    .experience-signoff .signoff-closing {
        font-weight: bold;
    }
    .experience-signoff .signoff-spacer {
        height: <?= $isUltra ? '24px' : ($isCompact ? '30px' : '45px') ?>;
    }
    .experience-signoff .signoff-dept {
        font-size: <?= $isCompact ? '11px' : '12.5px' ?>;
        color: #000;
    }
    .experience-signoff .signoff-company {
        font-size: <?= $isCompact ? '11px' : '12.5px' ?>;
        color: #000;
    }

    /* Extra Details */
    .letter-extra-box {
        margin: <?= $extraBoxMargin ?>;
        padding: <?= $extraBoxPadding ?>;
        background-color: #f8fafc;
        border: 1px solid #cbd5e1;
    }
    .table-extra-details {
        width: 100%;
        font-size: <?= $isCompact ? '11px' : '12px' ?>;
        border-collapse: collapse;
    }
    .table-extra-details td {
        padding: <?= $isCompact ? '2px 4px' : '4px 6px' ?>;
    }
    .table-extra-details .extra-label {
        width: 220px;
        color: #475569;
        font-weight: bold;
    }
    .table-extra-details .extra-sep {
        width: 15px;
        color: #64748b;
        font-weight: bold;
    }
    .table-extra-details .extra-val {
        color: #0f172a;
    }

    /* Signatory Section */
    .letter-signature-section {
        margin-top: <?= $sigSectionMargin ?>;
        font-size: <?= $isCompact ? '11.5px' : '12.5px' ?>;
        color: #1e293b;
        page-break-inside: avoid;
    }
    .company-sign-for {
        margin-bottom: 0;
        color: #334155;
    }
    .signature-block {
        margin-top: <?= $sigBlockMargin ?>;
        page-break-inside: avoid;
    }
    .sign-line {
        border-top: 1.5px solid #1e293b;
        width: <?= $signLineWidth ?>;
        margin-bottom: 4px;
    }
    .signatory-name {
        font-size: <?= $isCompact ? '12px' : '13px' ?>;
        font-weight: bold;
        color: #0f172a;
        display: block;
    }
    .signatory-title {
        font-size: <?= $isCompact ? '11px' : '12px' ?>;
        color: #475569;
        font-style: italic;
        display: block;
    }
    .signatory-dept {
        font-size: <?= $isCompact ? '10.5px' : '11.5px' ?>;
        color: #64748b;
        display: block;
    }
</style>
</head>
<body>
    <div class="letter-content">

        <?php if ($isCaution): ?>
            <!-- ═══════════════════ CAUTION LETTER FORMAT ═══════════════════ -->
            <div class="caution-header-title">CAUTION LETTER</div>

            <div class="caution-date">Date: <?= date('F d, Y', strtotime($letter['letter_date'])) ?></div>

            <div class="caution-recipient-block">
                <div class="caution-to">To,</div>
                <div class="caution-emp-name"><?= htmlspecialchars($rawName) ?></div>
                <div class="caution-line">Emp ID: <?= htmlspecialchars($letter['employee_code']) ?></div>
                <div class="caution-line">Designation: <?= htmlspecialchars($letter['designation_name'] ?? 'N/A') ?></div>
                <div class="caution-line">Department: <?= htmlspecialchars($letter['department_name'] ?? 'N/A') ?></div>
            </div>

            <div class="caution-subject">
                <strong>Subject:</strong> <?= htmlspecialchars($letter['subject']) ?>
            </div>

            <?php if (stripos(trim(strip_tags($letter['body'])), 'Dear ') !== 0): ?>
                <div class="caution-salutation">
                    Dear <?= htmlspecialchars($rawName) ?>,
                </div>
            <?php endif; ?>

            <div class="letter-body caution-body">
                <?= $pdfBody ?>
            </div>

            <?php if (stripos($letter['body'], 'Head of HR') === false): ?>
                <div class="caution-signoff">
                    Head of HR | <?= htmlspecialchars($companyName) ?>
                </div>
            <?php endif; ?>

        <?php elseif ($isExperience): ?>
            <!-- ═══════════════════ EXPERIENCE CERTIFICATE FORMAT ═══════════════════ -->
            <div class="cert-meta-stacked">
                <div class="meta-line"><strong>Ref:</strong> <?= htmlspecialchars($refNum) ?></div>
                <div class="meta-line"><strong>Date:</strong> <?= date('F d, Y', strtotime($letter['letter_date'])) ?></div>
            </div>

            <div class="cert-title-centered">TO WHOM IT MAY CONCERN</div>

            <div class="letter-body experience-body">
                <?= $pdfBody ?>
            </div>

            <?php if (stripos($letter['body'], 'Human Resources Department') === false): ?>
                <div class="experience-signoff" style="page-break-inside: avoid;">
                    <div class="signoff-closing">Sincerely,</div>
                    <div class="signoff-company" style="margin-bottom: 5px;">For <strong><?= htmlspecialchars($companyName) ?></strong></div>
                    <?php if ((!isset($letter['include_signature']) || $letter['include_signature'] == 1) && !empty($sigBase64)): ?>
                        <div style="height: <?= $sigBoxHeight ?>; margin-bottom: <?= $isCompact ? '3px' : '5px' ?>;">
                            <img src="<?= $sigBase64 ?>" style="height: <?= $sigImgHeight ?>; max-width: 170px;">
                        </div>
                    <?php else: ?>
                        <div class="signoff-spacer" style="height: <?= $isUltra ? '24px' : ($isCompact ? '30px' : '45px') ?>;"></div>
                    <?php endif; ?>
                    <?php if (!empty($letter['signatory_name'])): ?>
                        <div style="font-weight: bold;"><?= htmlspecialchars($letter['signatory_name']) ?></div>
                        <?php if (!empty($letter['signatory_title'])): ?>
                            <div style="color: #475569;"><?= htmlspecialchars($letter['signatory_title']) ?></div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <div class="signoff-dept">Human Resources Department</div>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- ═══════════════════ STANDARD / OTHER LETTER FORMAT ═══════════════════ -->
            <!-- Reference Number & Date -->
            <table class="letter-meta">
                <tr>
                    <td align="left"><strong>Ref:</strong> <?= htmlspecialchars($refNum) ?></td>
                    <td align="right"><strong>Date:</strong> <?= date('d F, Y', strtotime($letter['letter_date'])) ?></td>
                </tr>
            </table>

            <!-- Recipient Block -->
            <div class="letter-recipient">
                <div class="recipient-to">To,</div>
                <div class="recipient-name"><?= $recipientName ?></div>
                <div class="recipient-detail"><?= htmlspecialchars($letter['designation_name'] ?? 'N/A') ?></div>
                <div class="recipient-detail"><?= htmlspecialchars($letter['department_name'] ?? 'N/A') ?></div>
                <div class="recipient-id"><strong>Employee ID:</strong> <?= htmlspecialchars($letter['employee_code']) ?></div>
                <?php if ($hasAddress): ?>
                    <div class="recipient-address"><?= nl2br(htmlspecialchars($cleanAddress)) ?></div>
                <?php endif; ?>
            </div>

            <!-- Subject Line -->
            <div class="letter-subject">
                <span class="subject-text">
                    <strong>Subject:</strong> <?= htmlspecialchars($letter['subject']) ?>
                </span>
            </div>

            <!-- Main Letter Body -->
            <div class="letter-body">
                <?= $pdfBody ?>
            </div>

            <!-- Extra Details -->
            <?php
            $hasExtra = !empty($letter['effective_date']) || !empty($letter['new_designation']) || (!empty($letter['current_salary']) && (float)$letter['current_salary'] > 0) || (!empty($letter['revised_salary']) && (float)$letter['revised_salary'] > 0);
            ?>
            <?php if ($hasExtra): ?>
                <div class="letter-extra-box">
                    <table class="table-extra-details">
                        <?php if (!empty($letter['effective_date'])): ?>
                            <tr>
                                <td class="extra-label">Effective Date</td>
                                <td class="extra-sep">:</td>
                                <td class="extra-val"><?= date('d F, Y', strtotime($letter['effective_date'])) ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if (!empty($letter['new_designation'])): ?>
                            <tr>
                                <td class="extra-label">New Position / Designation</td>
                                <td class="extra-sep">:</td>
                                <td class="extra-val"><strong><?= htmlspecialchars($letter['new_designation']) ?></strong></td>
                            </tr>
                        <?php endif; ?>
                        <?php if (!empty($letter['current_salary']) && (float)$letter['current_salary'] > 0): ?>
                            <tr>
                                <td class="extra-label">Current Gross Salary</td>
                                <td class="extra-sep">:</td>
                                <td class="extra-val">BDT <?= number_format((float)$letter['current_salary'], 2) ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if (!empty($letter['revised_salary']) && (float)$letter['revised_salary'] > 0): ?>
                            <tr>
                                <td class="extra-label">Revised Gross Salary</td>
                                <td class="extra-sep">:</td>
                                <td class="extra-val"><strong>BDT <?= number_format((float)$letter['revised_salary'], 2) ?></strong></td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Official Signatory Block -->
            <div class="letter-signature-section" style="page-break-inside: avoid;">
                <p class="mb-1">Sincerely,</p>
                <p class="company-sign-for">For <strong><?= htmlspecialchars($companyName) ?></strong></p>
                <div class="signature-block">
                    <?php if ((!isset($letter['include_signature']) || $letter['include_signature'] == 1) && !empty($sigBase64)): ?>
                        <div style="height: <?= $sigBoxHeight ?>; margin-bottom: <?= $isCompact ? '3px' : '5px' ?>;">
                            <img src="<?= $sigBase64 ?>" style="height: <?= $sigImgHeight ?>; max-width: 170px;">
                        </div>
                    <?php else: ?>
                        <div style="height: <?= $sigBoxHeight ?>; margin-bottom: <?= $isCompact ? '3px' : '5px' ?>;"></div>
                    <?php endif; ?>
                    <div class="sign-line"></div>
                    <span class="signatory-name"><?= htmlspecialchars($letter['signatory_name']) ?></span>
                    <span class="signatory-title"><?= htmlspecialchars($letter['signatory_title']) ?></span>
                    <span class="signatory-dept">Human Resources Department</span>
                </div>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
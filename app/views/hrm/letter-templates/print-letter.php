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
    $refNum = !empty($letter['ref_number']) ? $letter['ref_number'] : ('TPHL/HR/' . date('Y') . '/' . str_pad($letter['id'], 4, '0', STR_PAD_LEFT));
}

$baseDir = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4);
$sigUrl = '';
if (!empty($letter['custom_signature']) && file_exists($baseDir . '/' . ltrim($letter['custom_signature'], '/\\'))) {
    $sigUrl = $GLOBALS['base_url'] . '/' . ltrim($letter['custom_signature'], '/\\');
} elseif (file_exists($baseDir . '/public/assets/images/hrm/hr_signature.png')) {
    $sigUrl = $GLOBALS['base_url'] . '/public/assets/images/hrm/hr_signature.png';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Official Letter - <?= htmlspecialchars($letter['subject']) ?></title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
            font-size: 13px;
            line-height: 1.7;
            color: #111827;
            width: 210mm;
            height: 297mm;
            background: #fff;
        }

        /* Full-page wrapper */
        .page-wrapper {
            width: 210mm;
            height: 297mm;
            position: relative;
        }

        /* Letterhead image — reliably prints in all browsers */
        .bg-letterhead {
            position: absolute;
            top: 0;
            left: 0;
            width: 210mm;
            height: 297mm;
            z-index: 0;
        }

        /* Letter content overlay within the safe zone */
        .letter-content {
            position: absolute;
            top: 32mm;
            left: 20mm;
            right: 20mm;
            bottom: 35mm;
            overflow: hidden;
            z-index: 1;
            display: flex;
            flex-direction: column;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none;
            }

            body,
            .page-wrapper {
                width: 210mm;
                height: 297mm;
            }
        }

        /* Floating print toolbar */
        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            gap: 10px;
            background: rgba(255, 255, 255, 0.95);
            padding: 10px 14px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .btn-print {
            padding: 8px 18px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
        }

        .btn-close {
            padding: 8px 14px;
            background: #64748b;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
        }

        /* ═══ Standard Letter Styling ═══ */
        .letter-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: #111827;
            margin-bottom: 18px;
        }

        .letter-recipient {
            margin-bottom: 18px;
            font-size: 13px;
            line-height: 1.5;
            color: #111827;
        }

        .letter-recipient .recipient-to {
            font-weight: 700;
            margin-bottom: 3px;
            color: #000;
        }

        .letter-recipient .recipient-name {
            font-size: 14.5px;
            font-weight: 700;
            color: #000;
            margin-bottom: 2px;
        }

        .letter-recipient .recipient-detail {
            color: #111827;
        }

        .letter-recipient .recipient-id {
            margin-top: 2px;
            font-size: 13px;
        }

        .letter-recipient .recipient-address {
            margin-top: 4px;
            font-size: 13px;
            color: #4b5563;
        }

        .letter-subject {
            margin: 16px 0;
            text-align: left;
        }

        .letter-subject .subject-text {
            font-size: 13.5px;
            font-weight: 700;
            text-decoration: underline;
            color: #000;
        }

        .letter-body {
            font-size: 13px;
            line-height: 1.75;
            color: #111827;
            text-align: justify;
            text-justify: inter-word;
            flex-grow: 1;
        }

        .letter-body p {
            margin-bottom: 12px;
        }

        .letter-body ul, .letter-body ol {
            margin-bottom: 12px;
            padding-left: 24px;
        }

        .letter-body li {
            margin-bottom: 5px;
        }

        /* ═══ Caution Letter Specific Styling ═══ */
        .caution-header-title {
            font-size: 16px;
            font-weight: 800;
            text-align: center;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #000;
            margin-bottom: 22px;
        }

        .caution-date {
            font-size: 13px;
            font-weight: 600;
            color: #000;
            margin-bottom: 6px;
        }

        .caution-recipient-block {
            font-size: 13px;
            line-height: 1.45;
            color: #000;
            margin-bottom: 16px;
        }

        .caution-recipient-block .caution-to {
            font-weight: 600;
            margin-bottom: 2px;
        }

        .caution-recipient-block .caution-emp-name {
            font-weight: 700;
            font-size: 14px;
        }

        .caution-recipient-block .caution-line {
            color: #111;
        }

        .caution-subject {
            font-size: 13.5px;
            margin: 14px 0 16px 0;
            color: #000;
        }

        .caution-salutation {
            font-size: 13.5px;
            font-weight: 700;
            color: #000;
            margin-bottom: 12px;
        }

        .caution-body {
            font-size: 13px;
            line-height: 1.72;
            color: #000;
        }

        .caution-signoff {
            margin-top: 36px;
            font-size: 13.5px;
            font-weight: 700;
            color: #000;
        }

        /* ═══ Experience Certificate Specific Styling ═══ */
        .cert-meta-stacked {
            font-size: 13px;
            line-height: 1.6;
            color: #000;
            margin-bottom: 24px;
        }

        .cert-meta-stacked .meta-line {
            font-weight: 600;
        }

        .cert-title-centered {
            font-size: 15.5px;
            font-weight: 800;
            text-align: center;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #000;
            margin: 20px 0 24px 0;
        }

        .experience-body {
            font-size: 13px;
            line-height: 1.8;
            color: #000;
        }

        .experience-signoff {
            margin-top: 30px;
            font-size: 13px;
            line-height: 1.45;
            color: #000;
        }

        .experience-signoff .signoff-closing {
            font-weight: 600;
            margin-bottom: 0;
        }

        .experience-signoff .signoff-spacer {
            height: 45px;
        }

        .experience-signoff .signoff-dept {
            font-size: 13px;
            color: #000;
        }

        .experience-signoff .signoff-company {
            font-size: 13px;
            color: #000;
        }

        /* Extra Details */
        .letter-extra-box {
            margin: 14px 0 18px;
            padding: 10px 14px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }

        .table-extra-details {
            width: 100%;
            font-size: 13px;
            border-collapse: collapse;
        }

        .table-extra-details td {
            padding: 4px 6px;
            vertical-align: middle;
        }

        .table-extra-details .extra-label {
            width: 220px;
            color: #475569;
            font-weight: 600;
        }

        .table-extra-details .extra-sep {
            width: 15px;
            color: #64748b;
            font-weight: 700;
        }

        .table-extra-details .extra-val {
            color: #0f172a;
        }

        /* Signatory Section */
        .letter-signature-section {
            margin-top: 24px;
            font-size: 13px;
            color: #1e293b;
        }

        .company-sign-for {
            margin-bottom: 0;
            color: #334155;
        }

        .signature-block {
            margin-top: 36px;
            display: inline-block;
        }

        .sign-line {
            border-top: 1.5px solid #334155;
            width: 180px;
            margin-bottom: 6px;
        }

        .signatory-name {
            font-size: 13.5px;
            font-weight: 700;
            color: #0f172a;
            display: block;
        }

        .signatory-title {
            font-size: 12.5px;
            color: #475569;
            font-style: italic;
            display: block;
            margin-top: 1px;
        }

        .signatory-dept {
            font-size: 12px;
            color: #64748b;
            display: block;
            margin-top: 1px;
        }
    </style>
</head>

<body>
    <!-- Print toolbar -->
    <div class="no-print">
        <button class="btn-print" onclick="window.print()">Print</button>
        <button class="btn-close" onclick="window.close()">Close</button>
    </div>

    <div class="page-wrapper">
        <!-- Letterhead background image -->
        <img src="<?= $GLOBALS['base_url'] ?>/assets/email-template/new-letter-template.jpeg" class="bg-letterhead"
            alt="Letterhead">

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
                    <?= $letter['body'] ?>
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
                    <?= $letter['body'] ?>
                </div>

                <?php if (stripos($letter['body'], 'Human Resources Department') === false): ?>
                    <div class="experience-signoff">
                        <div class="signoff-closing">Sincerely,</div>
                        <div class="signoff-company" style="margin-bottom: 5px;">For <strong><?= htmlspecialchars($companyName) ?></strong></div>
                        <?php if ((!isset($letter['include_signature']) || $letter['include_signature'] == 1) && !empty($sigUrl)): ?>
                            <div style="height: 50px; margin-bottom: -10px;">
                                <img src="<?= $sigUrl ?>" alt="Signature" style="height: 45px; max-width: 170px; object-fit: contain;">
                            </div>
                        <?php else: ?>
                            <div class="signoff-spacer"></div>
                        <?php endif; ?>
                        <?php if (!empty($letter['signatory_name'])): ?>
                            <div style="font-weight: 700;"><?= htmlspecialchars($letter['signatory_name']) ?></div>
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
                <div class="letter-meta">
                    <div class="ref-no">
                        <strong>Ref:</strong> <?= htmlspecialchars($refNum) ?>
                    </div>
                    <div class="date-issued">
                        <strong>Date:</strong> <?= date('d F, Y', strtotime($letter['letter_date'])) ?>
                    </div>
                </div>

                <!-- Recipient Address Block -->
                <div class="letter-recipient">
                    <div class="recipient-to">To,</div>
                    <div class="recipient-name"><?= $recipientName ?></div>
                    <div class="recipient-detail"><?= htmlspecialchars($letter['designation_name'] ?? 'N/A') ?></div>
                    <div class="recipient-detail"><?= htmlspecialchars($letter['department_name'] ?? 'N/A') ?></div>
                    <div class="recipient-id"><strong>Employee ID:</strong> <?= htmlspecialchars($letter['employee_code']) ?></div>
                    <?php if (!empty($letter['address'])): ?>
                        <div class="recipient-address"><?= nl2br(htmlspecialchars($letter['address'])) ?></div>
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
                    <?= $letter['body'] ?>
                </div>

                <!-- Extra Details (Promotion, Increment, Designation changes) -->
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
                <div class="letter-signature-section">
                    <p class="mb-1">Sincerely,</p>
                    <p class="company-sign-for">For <strong><?= htmlspecialchars($companyName) ?></strong></p>
                    <div class="signature-block">
                        <?php if ((!isset($letter['include_signature']) || $letter['include_signature'] == 1) && !empty($sigUrl)): ?>
                            <div style="height: 50px; margin-bottom: -15px;">
                                <img src="<?= $sigUrl ?>" alt="Signature" style="height: 45px; max-width: 170px; object-fit: contain;">
                            </div>
                        <?php endif; ?>
                        <div class="sign-line"></div>
                        <span class="signatory-name"><?= htmlspecialchars($letter['signatory_name']) ?></span>
                        <span class="signatory-title"><?= htmlspecialchars($letter['signatory_title']) ?></span>
                        <span class="signatory-dept">Human Resources Department</span>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <script>
        window.onload = function () {
            window.print();
        };
    </script>
</body>

</html>
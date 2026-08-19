<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Print Letter - <?= htmlspecialchars($letter['subject']) ?></title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #000;
            width: 210mm;
            height: 297mm;
        }

        /* Full-page wrapper */
        .page-wrapper {
            width: 210mm;
            height: 297mm;
            position: relative;
        }

        /* Letterhead image — always prints unlike CSS backgrounds */
        .bg-letterhead {
            position: absolute;
            top: 0;
            left: 0;
            width: 210mm;
            height: 297mm;
            z-index: 0;
        }

        /* Letter content sits in the white area of the template */
        /* Header ≈ 33mm, footer ≈ 42mm on A4 at full bleed */
        .letter-content {
            position: absolute;
            top: 33mm;
            left: 18mm;
            right: 18mm;
            bottom: 44mm;
            overflow: hidden;
            z-index: 1;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        .letter-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 14pt;
            font-size: 11pt;
        }

        .recipient {
            margin-bottom: 16pt;
            font-size: 11pt;
        }

        .recipient .emp-name {
            font-size: 13pt;
            font-weight: bold;
        }

        .subject {
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            margin-bottom: 16pt;
            text-align: center;
            font-size: 11pt;
        }

        .letter-body {
            text-align: justify;
            font-size: 11pt;
        }

        .letter-body p {
            margin-bottom: 10pt;
        }

        .signature-section {
            margin-top: 30pt;
            font-size: 11pt;
        }

        .signatory-name {
            font-weight: bold;
            display: block;
            margin-top: 30pt;
            text-decoration: underline;
        }

        .signatory-title {
            font-style: italic;
        }

        @media print {
            .no-print {
                display: none;
            }

            body,
            .page-wrapper {
                width: 210mm;
                height: 297mm;
            }
        }
    </style>
</head>

<body onload="window.print()">
    <div class="page-wrapper">
        <!-- Letterhead as img tag — prints in all browsers unlike CSS background-image -->
        <img src="<?= $GLOBALS['base_url'] ?>/assets/email-template/new-letter-template.jpeg" class="bg-letterhead"
            alt="">
        <div class="letter-content">

            <div class="letter-meta">
                <div><strong>Ref:</strong>
                    <?= htmlspecialchars($letter['ref_number'] ?? 'TPHL/HR/' . date('Y') . '/00' . $letter['id']) ?>
                </div>
                <div><strong>Date:</strong> <?= date('d F, Y', strtotime($letter['letter_date'])) ?></div>
            </div>

            <div class="recipient">
                <p><strong>To,</strong></p>
                <span class="emp-name"><?= htmlspecialchars($letter['full_name']) ?></span><br>
                <span><?= htmlspecialchars($letter['designation_name'] ?? 'N/A') ?></span><br>
                <span><?= htmlspecialchars($letter['department_name'] ?? 'N/A') ?></span><br>
                <span><strong>ID Code:</strong> <?= htmlspecialchars($letter['employee_code']) ?></span>
                <?php if (!empty($letter['address'])): ?><br>
                    <span style="margin-top:6pt; display:block;"><?= nl2br(htmlspecialchars($letter['address'])) ?></span>
                <?php endif; ?>
            </div>

            <div class="subject">
                Subject: <?= htmlspecialchars($letter['subject']) ?>
            </div>

            <div class="letter-body">
                <?= $letter['body'] ?>
            </div>

            <?php if ($letter['letter_type'] === 'promotion'): ?>
                <p style="margin-top:10pt;"><strong>Effective Date:</strong>
                    <?= $letter['effective_date'] ? date('d F, Y', strtotime($letter['effective_date'])) : 'N/A' ?>
                </p>
                <p><strong>Current Salary:</strong> <?= number_format($letter['current_salary'], 2) ?></p>
                <p><strong>Revised Salary:</strong> <?= number_format($letter['revised_salary'], 2) ?></p>
            <?php elseif (!empty($letter['new_designation'])): ?>
                <p style="margin-top:10pt;"><strong>New Position / Designation:</strong>
                    <?= htmlspecialchars($letter['new_designation']) ?>
                </p>
            <?php endif; ?>

            <div class="signature-section">
                <p>Sincerely,</p>
                <p>For <strong><?= getSetting('website_title', 'The Premium Homes Ltd.') ?></strong></p>
                <span class="signatory-name"><?= htmlspecialchars($letter['signatory_name']) ?></span>
                <span class="signatory-title"><?= htmlspecialchars($letter['signatory_title']) ?></span>
            </div>

        </div><!-- /.letter-content -->
    </div><!-- /.page-wrapper -->
</body>

</html>
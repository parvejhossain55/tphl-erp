<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <!-- Action Toolbar -->
            <div class="card__title-wrap mb-20 d-flex align-items-center justify-content-between">
                <h5 class="card__heading-title">Letter Preview</h5>
                <div class="d-flex gap-2">
                    <a href="<?= $GLOBALS['base_url'] ?>/generate-letter" class="btn btn-secondary btn-sm">
                        <i class="fa fa-arrow-left me-1"></i> Back to List
                    </a>
                    <a href="<?= $GLOBALS['base_url'] ?>/edit-letter/<?= $letter['id'] ?>" class="btn btn-primary btn-sm">
                        <i class="fa fa-edit me-1"></i> Edit Letter
                    </a>
                    <a href="<?= $GLOBALS['base_url'] ?>/pdf-letter/<?= $letter['id'] ?>" class="btn btn-danger btn-sm"
                        target="_blank">
                        <i class="fa fa-file-pdf me-1"></i> Download PDF
                    </a>
                    <a href="<?= $GLOBALS['base_url'] ?>/print-letter/<?= $letter['id'] ?>"
                        class="btn btn-info btn-sm text-white" target="_blank">
                        <i class="fa fa-print me-1"></i> Print Letter
                    </a>
                </div>
            </div>

            <?php
            $baseDir = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4);
            $sigUrl = '';
            if (!empty($letter['custom_signature']) && file_exists($baseDir . '/' . ltrim($letter['custom_signature'], '/\\'))) {
                $sigUrl = $GLOBALS['base_url'] . '/' . ltrim($letter['custom_signature'], '/\\');
            } elseif (file_exists($baseDir . '/public/assets/images/hrm/hr_signature.png')) {
                $sigUrl = $GLOBALS['base_url'] . '/public/assets/images/hrm/hr_signature.png';
            } else {
                $dbConn = $GLOBALS['db'] ?? ($this->db ?? null);
                if ($dbConn) {
                    $defRes = $dbConn->query("SELECT signature_path FROM hr_signatures WHERE status = 'active' ORDER BY is_default DESC, id DESC LIMIT 1");
                    if ($defRes && $row = $defRes->fetch_assoc()) {
                        if (file_exists($baseDir . '/' . ltrim($row['signature_path'], '/\\'))) {
                            $sigUrl = $GLOBALS['base_url'] . '/' . ltrim($row['signature_path'], '/\\');
                        }
                    }
                }
            }

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

            // Split letter body into page chunks if page breaks are present
            $pageBreakPattern = '/(<div[^>]*class=["\'][^"\']*page-break[^"\']*["\'][^>]*>.*?<\/div>|<!--\s*pagebreak\s*-->|<hr[^>]*class=["\'][^"\']*page-break[^"\']*["\'][^>]*>)/is';
            $rawChunks = preg_split($pageBreakPattern, (string)($letter['body'] ?? ''));
            $bodyChunks = [];
            if (is_array($rawChunks)) {
                foreach ($rawChunks as $chunk) {
                    if (trim(strip_tags($chunk)) !== '' || strpos($chunk, '<img') !== false || strpos($chunk, '<table') !== false) {
                        $bodyChunks[] = $chunk;
                    }
                }
            }
            if (empty($bodyChunks)) {
                $bodyChunks = [$letter['body'] ?? ''];
            }
            $totalPages = count($bodyChunks);
            ?>

            <!-- Professional Paper Layout with Letterhead Background -->
            <div class="letter-container">
                <?php foreach ($bodyChunks as $pageIdx => $chunkContent): ?>
                    <?php
                    $pageNumber = $pageIdx + 1;
                    $isFirstPage = ($pageNumber === 1);
                    $isLastPage = ($pageNumber === $totalPages);
                    ?>
                    <div class="letter-paper-wrapper">
                        <?php if ($totalPages > 1): ?>
                            <div class="page-sheet-badge">
                                <i class="fa-regular fa-file-lines me-1"></i> Page <?= $pageNumber ?> of <?= $totalPages ?>
                            </div>
                        <?php endif; ?>

                        <div class="letter-paper" <?= ($pageIdx === 0 ? 'id="printableArea"' : '') ?>>
                            <!-- Content overlay positioned precisely within the safe zone of the letterhead -->
                            <div class="letter-content-overlay">

                                <?php if ($isCaution): ?>
                                    <!-- ═══════════════════ CAUTION LETTER FORMAT ═══════════════════ -->
                                    <?php if ($isFirstPage): ?>
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
                                    <?php endif; ?>

                                    <div class="letter-body caution-body">
                                        <?= $chunkContent ?>
                                    </div>

                                    <?php if ($isLastPage): ?>
                                        <?php if (stripos($letter['body'], 'Head of HR') === false): ?>
                                            <div class="caution-signoff">
                                                Head of HR | <?= htmlspecialchars($companyName) ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                <?php elseif ($isExperience): ?>
                                    <!-- ═══════════════════ EXPERIENCE CERTIFICATE FORMAT ═══════════════════ -->
                                    <?php if ($isFirstPage): ?>
                                        <div class="cert-meta-stacked">
                                            <div class="meta-line"><strong>Ref:</strong> <?= htmlspecialchars($refNum) ?></div>
                                            <div class="meta-line"><strong>Date:</strong> <?= date('F d, Y', strtotime($letter['letter_date'])) ?></div>
                                        </div>

                                        <div class="cert-title-centered">TO WHOM IT MAY CONCERN</div>
                                    <?php endif; ?>

                                    <div class="letter-body experience-body">
                                        <?= $chunkContent ?>
                                    </div>

                                    <?php if ($isLastPage): ?>
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
                                    <?php endif; ?>

                                <?php else: ?>
                                    <!-- ═══════════════════ STANDARD / OTHER LETTER FORMAT ═══════════════════ -->
                                    <?php if ($isFirstPage): ?>
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
                                    <?php endif; ?>

                                    <!-- Main Letter Body Chunk -->
                                    <div class="letter-body">
                                        <?= $chunkContent ?>
                                    </div>

                                    <?php if ($isLastPage): ?>
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

                                <?php endif; ?>

                            </div><!-- /.letter-content-overlay -->
                        </div><!-- /.letter-paper -->
                    </div><!-- /.letter-paper-wrapper -->
                <?php endforeach; ?>
            </div><!-- /.letter-container -->

        </div>
    </div>
</div>

<style>
    /* Corporate Dashboard Container */
    .letter-container {
        padding: 30px 15px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 25px;
        background-color: #f1f5f9;
    }

    .letter-paper-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 210mm;
    }

    .page-sheet-badge {
        align-self: flex-end;
        background: #334155;
        color: #fff;
        font-size: 11px;
        font-weight: 600;
        padding: 4px 12px;
        border-radius: 6px;
        margin-bottom: 8px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        display: inline-flex;
        align-items: center;
        letter-spacing: 0.5px;
    }

    /* Standard A4 Paper Layout (210mm x 297mm) */
    .letter-paper {
        width: 210mm;
        height: 297mm;
        max-height: 297mm;
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
        overflow: hidden;
    }

    /* Safe-zone overlay: clears header graphic at 32mm, footer at 32mm, margins at 20mm */
    .letter-content-overlay {
        position: absolute;
        top: 32mm;
        left: 20mm;
        right: 20mm;
        bottom: 32mm;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    /* Reference Number & Date (Standard) */
    .letter-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 13px;
        color: #111827;
        margin-bottom: 18px;
    }

    /* Recipient Details (Standard) */
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

    /* Subject */
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

    /* Letter Body Typography */
    .letter-body {
        font-size: 13px;
        line-height: 1.55;
        color: #111827;
        text-align: justify;
        text-justify: inter-word;
        flex-grow: 1;
    }

    .letter-body p {
        margin-bottom: 7px;
    }

    .letter-body h1, .letter-body h2, .letter-body h3, .letter-body h4 {
        margin: 8px 0 3px 0;
        font-size: 13.5px;
        color: #0f172a;
    }

    .letter-body ul, .letter-body ol {
        margin-bottom: 8px;
        padding-left: 20px;
    }

    .letter-body li {
        margin-bottom: 3px;
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

    .caution-subject strong {
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

    /* Extra Details Table */
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

    /* Signature Section */
    .letter-signature-section {
        margin-top: 14px;
        font-size: 13px;
        color: #1e293b;
    }

    .company-sign-for {
        margin-bottom: 0;
        color: #334155;
    }

    .signature-block {
        margin-top: 12px;
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

    @media print {
        body * {
            visibility: hidden;
        }

        .letter-container,
        .letter-container * {
            visibility: visible;
        }

        .page-sheet-badge,
        .card__title-wrap,
        .btn {
            display: none !important;
        }

        .letter-container {
            position: absolute;
            left: 0;
            top: 0;
            width: 210mm;
            padding: 0;
            margin: 0;
            background: none;
            gap: 0;
        }

        .letter-paper-wrapper {
            width: 210mm;
            margin: 0;
            padding: 0;
            page-break-after: always;
            break-after: page;
        }

        .letter-paper {
            width: 210mm;
            height: 297mm;
            box-shadow: none;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .card__wrapper {
            background: none;
            padding: 0 !important;
        }
    }
</style>
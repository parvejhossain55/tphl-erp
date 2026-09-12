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
                    <a href="<?= $GLOBALS['base_url'] ?>/pdf-letter/<?= $letter['id'] ?>?fit=1" class="btn btn-danger btn-sm"
                        target="_blank" title="Download PDF formatted to strictly fit on 1 page">
                        <i class="fa fa-file-pdf me-1"></i> Download PDF
                    </a>
<style>
/* ═══ Solid, Premium Print & PDF Dropdown ═══ */
.print-dropdown-menu {
    min-width: 300px !important;
    background: #ffffff !important;
    background-color: #ffffff !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 12px !important;
    box-shadow: 0 14px 35px -4px rgba(15, 23, 42, 0.22), 0 4px 14px -2px rgba(15, 23, 42, 0.1) !important;
    padding: 8px !important;
    margin-top: 6px !important;
    z-index: 1060 !important;
    animation: printDropdownFadeIn 0.15s ease-out;
}

@keyframes printDropdownFadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}

[bd-theme="bd-theme-dark"] .print-dropdown-menu {
    background: #1e293b !important;
    background-color: #1e293b !important;
    border-color: #334155 !important;
    box-shadow: 0 14px 35px -4px rgba(0, 0, 0, 0.55) !important;
}

.print-dropdown-header {
    font-size: 10.5px !important;
    font-weight: 700 !important;
    color: #64748b !important;
    text-transform: uppercase !important;
    letter-spacing: 0.6px !important;
    padding: 6px 10px 4px 10px !important;
    display: flex !important;
    align-items: center !important;
    gap: 6px !important;
}

[bd-theme="bd-theme-dark"] .print-dropdown-header {
    color: #94a3b8 !important;
}

.print-dropdown-item {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding: 8px 10px !important;
    color: #1e293b !important;
    font-size: 13px !important;
    font-weight: 500 !important;
    border-radius: 8px !important;
    transition: all 0.15s ease !important;
    text-decoration: none !important;
    margin-bottom: 2px !important;
    background: transparent !important;
}

[bd-theme="bd-theme-dark"] .print-dropdown-item {
    color: #f1f5f9 !important;
}

.print-dropdown-item:hover, 
.print-dropdown-item:focus {
    background-color: #f1f5f9 !important;
    color: #0f172a !important;
    transform: translateX(3px) !important;
}

[bd-theme="bd-theme-dark"] .print-dropdown-item:hover,
[bd-theme="bd-theme-dark"] .print-dropdown-item:focus {
    background-color: #334155 !important;
    color: #ffffff !important;
}

.print-dropdown-item.active {
    background-color: #ecfdf5 !important;
    color: #065f46 !important;
}

[bd-theme="bd-theme-dark"] .print-dropdown-item.active {
    background-color: #064e3b !important;
    color: #a7f3d0 !important;
}

.print-dropdown-item .item-left {
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
}

.print-dropdown-item .item-icon {
    width: 28px !important;
    height: 28px !important;
    border-radius: 6px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 12px !important;
    flex-shrink: 0 !important;
}

.item-icon-compact {
    background: #ecfdf5 !important;
    color: #059669 !important;
}

.item-icon-normal {
    background: #eff6ff !important;
    color: #2563eb !important;
}

.item-icon-ultra {
    background: #fffbeb !important;
    color: #d97706 !important;
}

.item-icon-pdf {
    background: #fef2f2 !important;
    color: #dc2626 !important;
}

.item-icon-pdf-multi {
    background: #f1f5f9 !important;
    color: #64748b !important;
}

.print-dropdown-item .item-title {
    font-weight: 600 !important;
    line-height: 1.25 !important;
    font-size: 12.5px !important;
}

.print-dropdown-item .item-desc {
    color: #64748b !important;
    font-size: 11px !important;
    line-height: 1.2 !important;
    margin-top: 1px !important;
}

[bd-theme="bd-theme-dark"] .print-dropdown-item .item-desc {
    color: #94a3b8 !important;
}

.print-dropdown-badge {
    font-size: 10.5px !important;
    font-weight: 700 !important;
    padding: 2px 7px !important;
    border-radius: 6px !important;
    letter-spacing: 0.2px !important;
    flex-shrink: 0 !important;
}

.badge-compact {
    background-color: #d1fae5 !important;
    color: #065f46 !important;
}

.badge-normal {
    background-color: #f1f5f9 !important;
    color: #475569 !important;
}

.badge-ultra {
    background-color: #fef3c7 !important;
    color: #92400e !important;
}

.badge-pdf {
    background-color: #fee2e2 !important;
    color: #991b1b !important;
}

.print-dropdown-divider {
    height: 1px !important;
    background-color: #e2e8f0 !important;
    border: none !important;
    margin: 6px 4px !important;
}

[bd-theme="bd-theme-dark"] .print-dropdown-divider {
    background-color: #334155 !important;
}

/* Split Button Styling */
.btn-print-group {
    box-shadow: 0 1px 3px rgba(2, 132, 199, 0.35) !important;
    border-radius: 6px !important;
}

.btn-print-main {
    background-color: #0284c7 !important;
    border-color: #0284c7 !important;
    color: #ffffff !important;
    font-weight: 600 !important;
    font-size: 12.5px !important;
    padding: 6px 14px !important;
}

.btn-print-main:hover {
    background-color: #0369a1 !important;
    border-color: #0369a1 !important;
    color: #ffffff !important;
}

.btn-print-toggle {
    background-color: #0284c7 !important;
    border-color: #0284c7 !important;
    color: #ffffff !important;
    border-left: 1px solid rgba(255, 255, 255, 0.3) !important;
    padding-left: 9px !important;
    padding-right: 9px !important;
}

.btn-print-toggle:hover,
.btn-print-toggle:focus,
.btn-print-group.show .btn-print-toggle {
    background-color: #0369a1 !important;
    border-color: #0369a1 !important;
    color: #ffffff !important;
}
</style>

                    <div class="btn-group btn-print-group">
                        <a href="<?= $GLOBALS['base_url'] ?>/print-letter/<?= $letter['id'] ?>?fit=1"
                            class="btn btn-sm btn-print-main" target="_blank" title="Print Letter (Auto-scaled to 1 Page)">
                            <i class="fa fa-print me-1"></i> Print (Fit to 1 Page)
                        </a>
                        <button type="button" class="btn btn-sm btn-print-toggle dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false" title="More Print & PDF Options">
                            <span class="visually-hidden">Toggle Dropdown</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end print-dropdown-menu">
                            <div class="print-dropdown-header">
                                <i class="fa fa-sliders-h me-1"></i> PRINT MODES
                            </div>
                            <a class="dropdown-item print-dropdown-item active" href="<?= $GLOBALS['base_url'] ?>/print-letter/<?= $letter['id'] ?>?fit=1" target="_blank">
                                <div class="item-left">
                                    <span class="item-icon item-icon-compact"><i class="fa fa-compress-alt"></i></span>
                                    <div>
                                        <div class="item-title">Fit to 1 Page</div>
                                        <div class="item-desc">Compact & Auto-scaled (Recommended)</div>
                                    </div>
                                </div>
                                <span class="print-dropdown-badge badge-compact">1 Page</span>
                            </a>
                            <a class="dropdown-item print-dropdown-item" href="<?= $GLOBALS['base_url'] ?>/print-letter/<?= $letter['id'] ?>?fit=normal" target="_blank">
                                <div class="item-left">
                                    <span class="item-icon item-icon-normal"><i class="fa fa-file-alt"></i></span>
                                    <div>
                                        <div class="item-title">Standard Spacing</div>
                                        <div class="item-desc">Normal margins (Multi-page allowed)</div>
                                    </div>
                                </div>
                                <span class="print-dropdown-badge badge-normal">Original</span>
                            </a>
                            <a class="dropdown-item print-dropdown-item" href="<?= $GLOBALS['base_url'] ?>/print-letter/<?= $letter['id'] ?>?fit=ultra" target="_blank">
                                <div class="item-left">
                                    <span class="item-icon item-icon-ultra"><i class="fa fa-compress"></i></span>
                                    <div>
                                        <div class="item-title">Ultra Compact</div>
                                        <div class="item-desc">For extra long 5+ clause letters</div>
                                    </div>
                                </div>
                                <span class="print-dropdown-badge badge-ultra">Tight</span>
                            </a>

                            <div class="print-dropdown-divider"></div>

                            <div class="print-dropdown-header">
                                <i class="fa fa-file-pdf me-1"></i> PDF EXPORT
                            </div>
                            <a class="dropdown-item print-dropdown-item" href="<?= $GLOBALS['base_url'] ?>/pdf-letter/<?= $letter['id'] ?>?fit=1" target="_blank">
                                <div class="item-left">
                                    <span class="item-icon item-icon-pdf"><i class="fa fa-file-pdf"></i></span>
                                    <div>
                                        <div class="item-title">PDF (Fit to 1 Page)</div>
                                        <div class="item-desc">Single-page PDF download</div>
                                    </div>
                                </div>
                                <span class="print-dropdown-badge badge-pdf">1 Page</span>
                            </a>
                            <a class="dropdown-item print-dropdown-item" href="<?= $GLOBALS['base_url'] ?>/pdf-letter/<?= $letter['id'] ?>?fit=0" target="_blank">
                                <div class="item-left">
                                    <span class="item-icon item-icon-pdf-multi"><i class="fa fa-file-pdf"></i></span>
                                    <div>
                                        <div class="item-title">PDF (Standard)</div>
                                        <div class="item-desc">Multi-page standard PDF</div>
                                    </div>
                                </div>
                                <span class="print-dropdown-badge badge-normal">Multi</span>
                            </a>
                        </div>
                    </div>
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
                        <div class="page-sheet-badge" style="display: <?= $totalPages > 1 ? 'inline-flex' : 'none' ?>;">
                            <i class="fa-regular fa-file-lines me-1"></i> Page <?= $pageNumber ?> of <?= $totalPages ?>
                        </div>

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
                                                    <div style="height: 48px; margin-bottom: 5px;">
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
                                                    <div style="height: 48px; margin-bottom: 5px;">
                                                        <img src="<?= $sigUrl ?>" alt="Signature" style="height: 45px; max-width: 170px; object-fit: contain;">
                                                    </div>
                                                <?php else: ?>
                                                    <div style="height: 48px; margin-bottom: 5px;"></div>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(autoPaginateLetter, 50);
});
window.addEventListener('load', function () {
    setTimeout(autoPaginateLetter, 120);
});

function autoPaginateLetter() {
    const container = document.querySelector('.letter-container');
    if (!container) return;

    let wrappers = Array.from(container.querySelectorAll('.letter-paper-wrapper'));
    const maxPages = 15;
    let didPaginate = false;

    for (let i = 0; i < wrappers.length && wrappers.length < maxPages; i++) {
        const currentWrapper = wrappers[i];
        const overlay = currentWrapper.querySelector('.letter-content-overlay');
        if (!overlay) continue;

        const currentBody = overlay.querySelector('.letter-body');
        if (!currentBody) continue;

        // Tolerance of 4px for sub-pixel rendering differences
        if (overlay.scrollHeight <= overlay.clientHeight + 4) {
            continue;
        }

        didPaginate = true;

        let nextWrapper = wrappers[i + 1];
        let nextOverlay, nextBody;

        if (!nextWrapper) {
            nextWrapper = document.createElement('div');
            nextWrapper.className = 'letter-paper-wrapper';
            nextWrapper.innerHTML = `
                <div class="page-sheet-badge" style="display: inline-flex;">
                    <i class="fa-regular fa-file-lines me-1"></i> Page
                </div>
                <div class="letter-paper">
                    <div class="letter-content-overlay">
                        <div class="${currentBody.className}"></div>
                    </div>
                </div>
            `;
            container.appendChild(nextWrapper);
            wrappers.push(nextWrapper);
        }

        nextOverlay = nextWrapper.querySelector('.letter-content-overlay');
        nextBody = nextWrapper.querySelector('.letter-body');

        // Move trailing blocks to next page first (signature & extra details belong at the very end)
        const trailingElements = overlay.querySelectorAll('.letter-signature-section, .caution-signoff, .experience-signoff, .letter-extra-box');
        trailingElements.forEach(el => {
            nextOverlay.appendChild(el);
        });

        // Check overflowing body elements
        const overlayRect = overlay.getBoundingClientRect();
        const safeBottom = overlayRect.bottom - 4;

        let children = Array.from(currentBody.children);
        let splitIndex = -1;

        for (let c = 0; c < children.length; c++) {
            const child = children[c];
            const rect = child.getBoundingClientRect();
            if (rect.bottom > safeBottom) {
                splitIndex = c;
                break;
            }
        }

        // If no single child went past safeBottom, but overlay still overflows (due to margins)
        if (splitIndex === -1 && overlay.scrollHeight > overlay.clientHeight + 4) {
            splitIndex = Math.max(0, children.length - 1);
        }

        if (splitIndex !== -1 && splitIndex < children.length) {
            // Prevent orphan headings at the bottom of the page
            if (splitIndex > 0) {
                const prevChild = children[splitIndex - 1];
                if (/^H[1-6]$/i.test(prevChild.tagName)) {
                    splitIndex = splitIndex - 1;
                }
            }

            const toMove = children.slice(splitIndex);
            if (toMove.length > 0) {
                const frag = document.createDocumentFragment();
                toMove.forEach(el => frag.appendChild(el));
                if (nextBody.firstChild) {
                    nextBody.insertBefore(frag, nextBody.firstChild);
                } else {
                    nextBody.appendChild(frag);
                }
            }
        }
    }

    // Update badges
    const finalWrappers = Array.from(container.querySelectorAll('.letter-paper-wrapper'));
    const total = finalWrappers.length;
    finalWrappers.forEach((w, idx) => {
        const badge = w.querySelector('.page-sheet-badge');
        if (badge) {
            badge.innerHTML = `<i class="fa-regular fa-file-lines me-1"></i> Page ${idx + 1} of ${total}`;
            badge.style.display = total > 1 ? 'inline-flex' : 'none';
        }
    });

    // Show smart notice when auto-paginated
    if (didPaginate) {
        let alertBox = document.getElementById('autoPaginationNotice');
        if (!alertBox) {
            alertBox = document.createElement('div');
            alertBox.id = 'autoPaginationNotice';
            alertBox.className = 'alert alert-info alert-dismissible fade show';
            alertBox.setAttribute('role', 'alert');
            alertBox.style.maxWidth = '210mm';
            alertBox.style.margin = '0 auto 15px auto';
            alertBox.style.fontSize = '12.5px';
            alertBox.innerHTML = `
                <i class="fa-solid fa-wand-magic-sparkles me-2 text-primary"></i>
                <strong>স্মার্ট অটো-পেজিনেশন:</strong> লেটারটি দীর্ঘ হওয়ায় এবং কোনো ম্যানুয়াল পেজ ব্রেক না থাকায় সিস্টেম স্বয়ংক্রিয়ভাবে বিষয়বস্তু <strong>${total}টি পেজে</strong> সাজিয়ে দিয়েছে।
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            const cardWrap = document.querySelector('.card__wrapper');
            if (cardWrap) {
                const containerEl = document.querySelector('.letter-container');
                cardWrap.insertBefore(alertBox, containerEl);
            }
        }
    }
}
</script>
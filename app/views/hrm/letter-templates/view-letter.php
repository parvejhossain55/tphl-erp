<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card__title-wrap mb-20 d-flex align-items-center justify-content-between">
                <h5 class="card__heading-title">Letter Preview</h5>
                <div class="d-flex gap-2">
                    <a href="<?= $GLOBALS['base_url'] ?>/generate-letter" class="btn btn-secondary btn-sm">
                        <i class="fa fa-arrow-left"></i> Back to List
                    </a>
                    <a href="<?= $GLOBALS['base_url'] ?>/pdf-letter/<?= $letter['id'] ?>" class="btn btn-danger btn-sm"
                        target="_blank">
                        <i class="fa fa-file-pdf"></i> Download PDF
                    </a>
                    <a href="<?= $GLOBALS['base_url'] ?>/print-letter/<?= $letter['id'] ?>"
                        class="btn btn-primary btn-sm" target="_blank">
                        <i class="fa fa-print"></i> Print Letter
                    </a>
                </div>
            </div>

            <!-- Professional Paper Layout with Letterhead Background -->
            <div class="letter-container">
                <div class="letter-paper" id="printableArea">
                    <!-- Content overlay positioned within the white area of the letterhead template -->
                    <div class="letter-content-overlay">

                        <div class="letter-meta mb-4 d-flex justify-content-between">
                            <div class="ref-no">
                                <strong>Ref:</strong>
                                <?= htmlspecialchars($letter['ref_number'] ?? 'TPHL/HR/' . date('Y') . '/00' . $letter['id']) ?>
                            </div>
                            <div class="date-issued">
                                <strong>Date:</strong> <?= date('d F, Y', strtotime($letter['letter_date'])) ?>
                            </div>
                        </div>

                        <div class="letter-recipient mb-4">
                            <p class="mb-0"><strong>To,</strong></p>
                            <h5 class="mb-0 fw-bold"><?= htmlspecialchars($letter['full_name']) ?></h5>
                            <p class="mb-0"><?= htmlspecialchars($letter['designation_name'] ?? 'N/A') ?></p>
                            <p class="mb-0"><?= htmlspecialchars($letter['department_name'] ?? 'N/A') ?></p>
                            <p class="mb-0"><strong>ID Code:</strong> <?= htmlspecialchars($letter['employee_code']) ?>
                            </p>
                            <p class="mb-0 mt-2"><?= nl2br(htmlspecialchars($letter['address'] ?? '')) ?></p>
                        </div>

                        <div class="letter-subject mb-4 text-center">
                            <h5 class="fw-bold text-uppercase border-bottom d-inline-block pb-1">
                                Subject: <?= htmlspecialchars($letter['subject']) ?>
                            </h5>
                        </div>

                        <div class="letter-body">
                            <?= $letter['body'] ?>
                        </div>

                        <div class="letter-extra mt-3">
                            <?php if ($letter['letter_type'] === 'promotion'): ?>
                                <p><strong>Effective Date:</strong>
                                    <?= $letter['effective_date'] ? date('d F, Y', strtotime($letter['effective_date'])) : 'N/A' ?>
                                </p>
                                <p><strong>Current Salary:</strong> <?= number_format($letter['current_salary'], 2) ?></p>
                                <p><strong>Revised Salary:</strong> <?= number_format($letter['revised_salary'], 2) ?></p>
                            <?php else: ?>
                                <?php if (!empty($letter['new_designation'])): ?>
                                    <p><strong>New Position / Designation:</strong>
                                        <?= htmlspecialchars($letter['new_designation']) ?></p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <div class="letter-signature mt-5">
                            <p>Sincerely,</p>
                            <p class="mb-5">For <strong><?= getSetting('website_title', '') ?></strong></p>
                            <div class="signature-block" style="margin-top: 40px;">
                                <span
                                    class="signatory-name d-block fw-bold text-decoration-underline"><?= htmlspecialchars($letter['signatory_name']) ?></span>
                                <span
                                    class="signatory-title d-block text-muted italic"><?= htmlspecialchars($letter['signatory_title']) ?></span>
                            </div>
                        </div>

                    </div><!-- /.letter-content-overlay -->
                </div><!-- /.letter-paper -->
            </div><!-- /.letter-container -->

        </div>
    </div>
</div>


<style>
    /* Dashboard Styling for Letter Preview */
    .letter-container {
        padding: 40px 20px;
        display: flex;
        justify-content: center;
    }

    .letter-paper {
        width: 210mm;
        min-height: 297mm;
        position: relative;

        /* New letterhead template as background */
        background-image: url('<?= $GLOBALS['base_url'] ?>/assets/email-template/new-letter-template.jpeg');
        background-size: 100% 100%;
        background-repeat: no-repeat;
        background-position: top left;

        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18);
        font-family: 'Inter', 'Segoe UI', serif;
        line-height: 1.6;
        color: #222;
    }

    /* Position content inside the white content area of the template */
    /* The template has ~130px header and ~160px footer at A4 scale (~794px tall at 96dpi) */
    /* At 210mm wide ≈ 794px; header ≈ 130px, footer ≈ 160px */
    .letter-content-overlay {
        position: absolute;
        top: 130px;
        left: 30px;
        right: 30px;
        bottom: 170px;
        overflow: hidden;
        font-size: 13px;
    }

    .letter-body {
        font-size: 14px;
        text-align: justify;
    }

    .letter-body p {
        margin-bottom: 14px;
    }

    .italic {
        font-style: italic;
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
        }

        .card__wrapper {
            background: none;
            padding: 0 !important;
        }
    }
</style>
<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card__title-wrap mb-20 d-flex align-items-center justify-content-between">
                <h5 class="card__heading-title">Letter Preview</h5>
                <div class="d-flex gap-2">
                    <a href="<?= $GLOBALS['base_url'] ?>/generate-letter" class="btn btn-secondary btn-sm">
                        <i class="fa fa-arrow-left"></i> Back to List
                    </a>
                    <a href="<?= $GLOBALS['base_url'] ?>/pdf-letter/<?= $letter['id'] ?>" class="btn btn-danger btn-sm" target="_blank">
                        <i class="fa fa-file-pdf"></i> Download PDF
                    </a>
                    <button onclick="window.print()" class="btn btn-primary btn-sm">
                        <i class="fa fa-print"></i> Print Letter
                    </button>
                </div>
            </div>

            <!-- Professional Paper Layout -->
            <div class="letter-container">
                <div class="letter-paper" id="printableArea">
                    <div class="letter-header">
                        <div class="row align-items-center">
                            <div class="col-6">
                                <img src="<?= $GLOBALS['base_url'] ?>/assets/images/logo/tphl_logo.png" alt="TPHL Logo" class="letter-logo">
                            </div>
                            <div class="col-6 text-end company-details">
                                <h4 class="company-name"> <?= getSetting('website_title', '') ?></h4>
                                <p><?= getSetting('address', '') ?></p>
                                <p>Email: <?= getSetting('email', '') ?></p>
                            </div>
                        </div>
                        <div class="letter-separator"></div>
                    </div>

                    <div class="letter-meta mb-5 d-flex justify-content-between">
                        <div class="ref-no">
                            <strong>Ref:</strong> <?= htmlspecialchars($letter['ref_number'] ?? 'TPHL/HR/' . date('Y') . '/00' . $letter['id']) ?>
                        </div>
                        <div class="date-issued">
                            <strong>Date:</strong> <?= date('d F, Y', strtotime($letter['letter_date'])) ?>
                        </div>
                    </div>

                    <div class="letter-recipient mb-5">
                        <p class="mb-0"><strong>To,</strong></p>
                        <h5 class="mb-0 fw-bold">Full Name: <?= htmlspecialchars($letter['full_name']) ?></h5>
                        <p class="mb-0 "><?= htmlspecialchars($letter['designation_name'] ?? 'N/A') ?></p>
                        <p class="mb-0 "><?= htmlspecialchars($letter['department_name'] ?? 'N/A') ?></p>
                        <p class="mb-0"><strong>ID Code:</strong> <?= htmlspecialchars($letter['employee_code']) ?></p>
                        <p class="mb-0 mt-3"><?= nl2br(htmlspecialchars($letter['address'] ?? '')) ?></p>
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
                            <p><strong>Effective Date:</strong> <?= $letter['effective_date'] ? date('d F, Y', strtotime($letter['effective_date'])) : 'N/A' ?></p>
                            <p><strong>Current Salary:</strong> <?= number_format($letter['current_salary'], 2) ?></p>
                            <p><strong>Revised Salary:</strong> <?= number_format($letter['revised_salary'], 2) ?></p>
                        <?php else: ?>
                            <?php if (!empty($letter['new_designation'])): ?>
                                <p><strong>New Position / Designation:</strong> <?= htmlspecialchars($letter['new_designation']) ?></p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <div class="letter-signature mt-5">
                        <p>Sincerely,</p>
                        <p class="mb-5">For <strong> <?= getSetting('website_title', '') ?></strong></p>

                        <div class="signature-block">
                            <span class="signatory-name d-block fw-bold text-decoration-underline"><?= htmlspecialchars($letter['signatory_name']) ?></span>
                            <span class="signatory-title d-block text-muted italic"><?= htmlspecialchars($letter['signatory_title']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<style>
    /* Dashboard Styling for Letter Preview */
    .letter-container {
        padding: 60px 20px;
        display: flex;
        justify-content: center;
        border-radius: 8px;
    }

    .letter-paper {
        background: #fff;
        width: 210mm;
        /* A4 Width */
        min-height: 297mm;
        /* A4 Height */
        padding: 2cm;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        position: relative;
        color: #333;
        font-family: 'Inter', 'Segoe UI', serif;
        line-height: 1.6;
    }

    .letter-logo {
        max-width: 140px;
    }

    .company-name {
        color: #006400;
        /* Formal Dark Green */
        font-weight: 800;
        margin: 0;
    }

    .company-details p {
        margin: 0;
        font-size: 11px;
        color: #666;
    }

    .letter-separator {
        height: 2px;
        background: #006400;
        margin-top: 15px;
        margin-bottom: 40px;
    }

    .letter-body {
        font-size: 15px;
        text-align: justify;
    }

    .letter-body p {
        margin-bottom: 15px;
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
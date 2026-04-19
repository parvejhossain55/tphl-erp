<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-material-issues">Material Issues</a></li>
                <li class="breadcrumb-item active">ISS-<?= $issue['id'] ?></li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-material-issues" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card__wrapper shadow-sm">
                <div class="card__body p-4">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="small text-uppercase text-muted d-block">Issue #</label>
                            <span class="fw-bold fs-4 text-primary">ISS-<?= $issue['id'] ?></span>
                        </div>
                        <div class="col-md-3">
                            <label class="small text-uppercase text-muted d-block">Project</label>
                            <span class="fw-bold"><?= htmlspecialchars($issue['project_name'] ?? '—') ?></span>
                        </div>
                        <div class="col-md-3">
                            <label class="small text-uppercase text-muted d-block">Warehouse</label>
                            <span class="fw-bold"><?= htmlspecialchars($issue['warehouse_name'] ?? '—') ?></span>
                        </div>
                        <div class="col-md-4">
                            <label class="small text-uppercase text-muted d-block">Issued By / Date</label>
                            <span class="fw-bold">
                                <?= htmlspecialchars($issue['issued_by_name'] ?? '—') ?>
                                — <?= date('d M Y', strtotime($issue['issue_date'])) ?>
                            </span>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="small text-uppercase text-muted d-block">Material</label>
                            <span class="fw-bold fs-5"><?= htmlspecialchars($issue['material_name']) ?></span>
                            <span class="text-muted">(<?= $issue['unit'] ?>)</span>
                        </div>
                        <div class="col-md-3">
                            <label class="small text-uppercase text-muted d-block">Quantity Issued</label>
                            <span class="fw-bold fs-5 text-danger"><?= number_format($issue['quantity'], 3) ?></span>
                            <span class="text-muted"><?= $issue['unit'] ?></span>
                        </div>
                        <div class="col-md-5">
                            <label class="small text-uppercase text-muted d-block">Issued To</label>
                            <span class="fw-bold"><?= htmlspecialchars($issue['issued_to'] ?: '—') ?></span>
                        </div>
                    </div>

                    <?php if (!empty($issue['purpose'])): ?>
                    <div class="mt-3 alert alert-light border-start border-primary border-3">
                        <strong>Purpose:</strong> <?= htmlspecialchars($issue['purpose']) ?>
                    </div>
                    <?php endif; ?>

                    <div class="alert alert-success mt-4">
                        <i class="bi bi-check-circle me-1"></i>
                        Stock deducted from <strong><?= htmlspecialchars($issue['warehouse_name']) ?></strong>.
                        Movement logged in <code>con_stock_movements</code>.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

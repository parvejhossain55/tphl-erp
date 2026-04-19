<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-boq">BOQ</a></li>
                <li class="breadcrumb-item active">Details</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <?php if ($boq['status'] !== 'Approved'): ?>
            <a href="<?= $base_url ?>/con-boq-edit/<?= $boq['id'] ?>" class="btn btn-outline-warning btn-sm">
                <i class="bi bi-pencil-square me-1"></i> Edit BOQ
            </a>
        <?php endif; ?>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i> Print
        </button>
        <a href="<?= $base_url ?>/con-boq" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-lg-11">
            <div class="card__wrapper shadow-sm">
                <div class="card__body p-4">

                    <!-- Header Info -->
                    <div class="row mb-4 g-3">
                        <div class="col-md-3">
                            <label class="small text-uppercase text-muted d-block">Project</label>
                            <span class="fw-bold"><?= htmlspecialchars($boq['project_name']) ?></span>
                        </div>
                        <div class="col-md-2">
                            <label class="small text-uppercase text-muted d-block">Version</label>
                            <span class="fw-bold"><?= htmlspecialchars($boq['version']) ?></span>
                        </div>
                        <div class="col-md-2">
                            <label class="small text-uppercase text-muted d-block">Status</label>
                            <?php
                            $badge = match($boq['status']) {
                                'Approved'  => 'bg-success',
                                'Rejected'  => 'bg-danger',
                                'Submitted' => 'bg-info',
                                default     => 'bg-warning text-dark',
                            };
                            ?>
                            <span class="badge <?= $badge ?> px-3"><?= $boq['status'] ?></span>
                        </div>
                        <div class="col-md-2">
                            <label class="small text-uppercase text-muted d-block">Created By</label>
                            <span class="fw-bold"><?= htmlspecialchars($boq['created_by_name'] ?? '—') ?></span>
                        </div>
                        <div class="col-md-3">
                            <label class="small text-uppercase text-muted d-block">Created On</label>
                            <span class="fw-bold"><?= date('d M, Y', strtotime($boq['created_at'])) ?></span>
                            <?php if (!empty($boq['approved_by_name'])): ?>
                                <div class="small text-muted mt-1">
                                    Approved by: <?= htmlspecialchars($boq['approved_by_name']) ?>
                                    <br><?= $boq['approved_at'] ? date('d M Y', strtotime($boq['approved_at'])) : '' ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Material</th>
                                    <th>Unit</th>
                                    <th class="text-end">Quantity</th>
                                    <th class="text-end">Est. Unit Cost</th>
                                    <th class="text-end">Total Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $grandTotal = 0;
                                foreach ($items as $i => $item):
                                    $grandTotal += $item['total_cost'];
                                ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($item['material_name']) ?></td>
                                    <td><?= htmlspecialchars($item['unit']) ?></td>
                                    <td class="text-end"><?= number_format($item['quantity'], 3) ?></td>
                                    <td class="text-end"><?= number_format($item['unit_cost'], 2) ?></td>
                                    <td class="text-end fw-bold"><?= number_format($item['total_cost'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="5" class="text-end py-3">GRAND TOTAL ESTIMATED COST:</th>
                                    <th class="text-end py-3 fs-5 text-primary">
                                        ৳ <?= number_format($grandTotal, 2) ?>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Status Action Buttons -->
                    <?php if ($boq['status'] === 'Draft'): ?>
                        <div class="mt-4 p-3 bg-light rounded d-flex justify-content-between align-items-center">
                            <span class="text-muted small">
                                <i class="bi bi-info-circle me-1"></i>
                                This BOQ is a Draft. Submit it for review, or approve directly.
                            </span>
                            <div class="d-flex gap-2">
                                <form action="<?= $base_url ?>/con-boq-approve/<?= $boq['id'] ?>" method="POST"
                                      class="d-inline">
                                    <input type="hidden" name="status" value="Submitted">
                                    <button type="submit" class="btn btn-info btn-sm">
                                        <i class="bi bi-send me-1"></i> Submit for Review
                                    </button>
                                </form>
                                <form action="<?= $base_url ?>/con-boq-approve/<?= $boq['id'] ?>" method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('Approve this BOQ? This action cannot be undone.');">
                                    <input type="hidden" name="status" value="Approved">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bi bi-check-circle me-1"></i> Approve
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php elseif ($boq['status'] === 'Submitted'): ?>
                        <div class="mt-4 p-3 bg-light rounded d-flex justify-content-between align-items-center">
                            <span class="text-muted small">
                                <i class="bi bi-clock-history me-1"></i>
                                This BOQ is Submitted and awaiting approval.
                            </span>
                            <div class="d-flex gap-2">
                                <form action="<?= $base_url ?>/con-boq-approve/<?= $boq['id'] ?>" method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('Approve this BOQ?');">
                                    <input type="hidden" name="status" value="Approved">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bi bi-check-circle me-1"></i> Approve
                                    </button>
                                </form>
                                <form action="<?= $base_url ?>/con-boq-approve/<?= $boq['id'] ?>" method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('Reject this BOQ?');">
                                    <input type="hidden" name="status" value="Rejected">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="bi bi-x-circle me-1"></i> Reject
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</section>

<style>
@media print {
    .pagetitle .btn, .pagetitle .d-flex.gap-2,
    .sidebar, .main-header-container, .main-footer,
    .bg-light.rounded { display: none !important; }
    .main-content { margin: 0 !important; padding: 0 !important; }
    .card__wrapper { border: none !important; box-shadow: none !important; }
}
</style>

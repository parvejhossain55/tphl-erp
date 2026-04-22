<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-purchase-requests">Purchase Requests</a></li>
                <li class="breadcrumb-item active">PR-<?= $pr['id'] ?></li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $base_url ?>/con-purchase-requests" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-lg-11">
            <div class="card__wrapper shadow-sm mb-4">
                <div class="card__body p-4">

                    <!-- Header Info -->
                    <div class="row mb-4 g-3">
                        <div class="col-md-3">
                            <label class="small text-uppercase text-muted d-block">PR Number</label>
                            <span class="fw-bold fs-5">PR-<?= $pr['id'] ?></span>
                        </div>
                        <div class="col-md-3">
                            <label class="small text-uppercase text-muted d-block">Project</label>
                            <span class="fw-bold"><?= htmlspecialchars($pr['project_name']) ?></span>
                        </div>
                        <div class="col-md-2">
                            <label class="small text-uppercase text-muted d-block">Status</label>
                            <?php
                            $badge = match($pr['status']) {
                                'Approved'  => 'bg-success',
                                'PO Issued' => 'bg-primary',
                                'Rejected'  => 'bg-danger',
                                'Submitted' => 'bg-info',
                                default     => 'bg-warning text-dark',
                            };
                            ?>
                            <span class="badge <?= $badge ?> px-3"><?= $pr['status'] ?></span>
                        </div>
                        <div class="col-md-2">
                            <label class="small text-uppercase text-muted d-block">Created By</label>
                            <span class="fw-bold"><?= htmlspecialchars($pr['created_by_name'] ?? '—') ?></span>
                        </div>
                        <div class="col-md-2">
                            <label class="small text-uppercase text-muted d-block">Date</label>
                            <span class="fw-bold"><?= date('d M Y', strtotime($pr['created_at'])) ?></span>
                        </div>
                    </div>

                    <?php if (!empty($pr['notes'])): ?>
                    <div class="alert alert-light border-start border-primary border-3 mb-3">
                        <strong>Notes:</strong> <?= htmlspecialchars($pr['notes']) ?>
                    </div>
                    <?php endif; ?>

                    <!-- Items Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Material</th>
                                    <th>Unit</th>
                                    <th class="text-end">Requested Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $i => $item): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($item['material_name']) ?></td>
                                    <td><?= htmlspecialchars($item['unit']) ?></td>
                                    <td class="text-end"><?= number_format($item['quantity'], 3) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Status Actions -->
                    <?php if ($pr['status'] === 'Draft'): ?>
                        <div class="mt-4 p-3 bg-light rounded d-flex justify-content-between align-items-center">
                            <span class="text-muted small">
                                <i class="bi bi-info-circle me-1"></i> This PR is a Draft. Submit or approve it.
                            </span>
                            <div class="d-flex gap-2">
                                <form action="<?= $base_url ?>/con-purchase-request-approve/<?= $pr['id'] ?>" method="POST" class="d-inline">
                                    <input type="hidden" name="status" value="Submitted">
                                    <button type="submit" class="btn btn-info btn-sm">
                                        <i class="bi bi-send me-1"></i> Submit
                                    </button>
                                </form>
                                <form action="<?= $base_url ?>/con-purchase-request-approve/<?= $pr['id'] ?>" method="POST" class="d-inline"
                                      onsubmit="return confirm('Approve this Purchase Request?');">
                                    <input type="hidden" name="status" value="Approved">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bi bi-check-circle me-1"></i> Approve
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php elseif ($pr['status'] === 'Submitted'): ?>
                        <div class="mt-4 p-3 bg-light rounded d-flex justify-content-between align-items-center">
                            <span class="text-muted small">
                                <i class="bi bi-clock-history me-1"></i> Awaiting approval.
                            </span>
                            <div class="d-flex gap-2">
                                <form action="<?= $base_url ?>/con-purchase-request-approve/<?= $pr['id'] ?>" method="POST" class="d-inline"
                                      onsubmit="return confirm('Approve this PR?');">
                                    <input type="hidden" name="status" value="Approved">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bi bi-check-circle me-1"></i> Approve
                                    </button>
                                </form>
                                <form action="<?= $base_url ?>/con-purchase-request-approve/<?= $pr['id'] ?>" method="POST" class="d-inline"
                                      onsubmit="return confirm('Reject this PR?');">
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

            <!-- Procurement Pipeline Actions -->
            <?php if ($pr['status'] === 'Approved' || $pr['status'] === 'PO Issued'): ?>
            <div class="card__wrapper shadow-sm">
                <div class="card__header">
                    <h6 class="card__title mb-0"><i class="bi bi-diagram-3 me-2"></i>Procurement Pipeline</h6>
                </div>
                <div class="card__body p-4">
                    <div class="row">
                        <!-- Step 1: Quotations -->
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center h-100">
                                <div class="mb-2">
                                    <i class="bi bi-receipt fs-3 text-info"></i>
                                </div>
                                <h6>Step 1: Vendor Quotations</h6>
                                <p class="small text-muted mb-2">
                                    <?= count($quotations) ?> quotation(s) collected
                                </p>
                                <?php if ($pr['status'] === 'Approved'): ?>
                                    <a href="<?= $base_url ?>/con-quotation-create/<?= $pr['id'] ?>"
                                       class="btn btn-outline-info btn-sm">
                                        <i class="bi bi-plus me-1"></i> Add Quotation
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-success"><i class="bi bi-check"></i> Done</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Step 2: CST Compare -->
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center h-100">
                                <div class="mb-2">
                                    <i class="bi bi-layout-three-columns fs-3 text-warning"></i>
                                </div>
                                <h6>Step 2: CST Comparison</h6>
                                <p class="small text-muted mb-2">
                                    <?= $hasCST ? 'Winner selected' : 'Compare & select lowest' ?>
                                </p>
                                <?php if (count($quotations) >= 2): ?>
                                    <a href="<?= $base_url ?>/con-quotation-compare/<?= $pr['id'] ?>"
                                       class="btn btn-outline-warning btn-sm">
                                        <i class="bi bi-eye me-1"></i> <?= $hasCST ? 'View CST' : 'Compare Now' ?>
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Need ≥ 2 quotes</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Step 3: Generate PO -->
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center h-100">
                                <div class="mb-2">
                                    <i class="bi bi-file-earmark-check fs-3 text-success"></i>
                                </div>
                                <h6>Step 3: Purchase Order</h6>
                                <p class="small text-muted mb-2">
                                    <?= ($pr['status'] === 'PO Issued') ? 'PO Generated' : 'Auto-generate from CST' ?>
                                </p>
                                <?php if ($hasCST && $pr['status'] !== 'PO Issued'): ?>
                                    <form action="<?= $base_url ?>/con-purchase-order-generate/<?= $pr['id'] ?>" method="POST" class="d-inline"
                                          onsubmit="return confirm('Generate Purchase Order from CST selections?');">
                                        <button type="submit" class="btn btn-outline-success btn-sm">
                                            <i class="bi bi-lightning me-1"></i> Generate PO
                                        </button>
                                    </form>
                                <?php elseif ($pr['status'] === 'PO Issued'): ?>
                                    <span class="badge bg-success"><i class="bi bi-check"></i> PO Created</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Complete CST first</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

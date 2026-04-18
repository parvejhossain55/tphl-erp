<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-estimates">Initial Estimates</a></li>
                <li class="breadcrumb-item active">Detail</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (!$locked && $budget['status'] === 'pending'): ?>
            <a href="<?= $base_url ?>/con-estimate-edit/<?= $budget['id'] ?>" class="btn btn-warning btn-sm">
                <i class="bi bi-pencil-square me-1"></i> Edit Budget
            </a>
        <?php endif; ?>
        <a href="<?= $base_url ?>/con-estimates" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>
</div>

<section class="section">

    <!-- ================================================================ -->
    <!-- Summary Cards                                                      -->
    <!-- ================================================================ -->
    <div class="row  mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="p-3 border rounded bg-light text-center h-100">
                <div class="text-muted small mb-1">Project</div>
                <div class="fw-bold"><?= htmlspecialchars($budget['project_name']) ?></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="p-3 border rounded bg-light text-center h-100">
                <div class="text-muted small mb-1">Total Area</div>
                <div class="fw-bold"><?= number_format($budget['total_area_sqft'] ?? 0, 2) ?> sqft</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="p-3 border rounded bg-light text-center h-100">
                <div class="text-muted small mb-1">Grand Total Estimate</div>
                <div class="fw-bold fs-5 text-primary">৳ <?= number_format($budget['total_estimated_cost'], 2) ?></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <?php
            $sClass = $budget['status'] === 'approved'  ? 'bg-success text-white' :
                     ($budget['status'] === 'cancelled' ? 'bg-danger text-white'  : 'bg-warning');
            ?>
            <div class="p-3 border rounded text-center h-100 <?= $sClass ?>">
                <div class="small mb-1">Status</div>
                <div class="fw-bold fs-5"><?= ucfirst($budget['status']) ?></div>
                <?php if ($budget['status'] === 'approved'): ?>
                    <div class="small mt-1 opacity-75">
                        by <?= htmlspecialchars($budget['approved_by_name'] ?? '—') ?>
                        on <?= date('d M Y', strtotime($budget['approval_date'])) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- Cost Summary Bar                                                   -->
    <!-- ================================================================ -->
    <div class="card__wrapper shadow-sm mb-4">
        <div class="card__body p-4">
            <div class="row g-2 text-center">
                <div class="col-4">
                    <div class="small text-muted">Material Cost</div>
                    <div class="fw-bold text-success fs-5">৳ <?= number_format($budget['material_cost_total'], 2) ?></div>
                </div>
                <div class="col-4">
                    <div class="small text-muted">Labor Cost</div>
                    <div class="fw-bold text-info fs-5">৳ <?= number_format($budget['labor_cost_total'], 2) ?></div>
                </div>
                <div class="col-4">
                    <div class="small text-muted">Other Costs</div>
                    <div class="fw-bold text-warning fs-5">৳ <?= number_format($budget['other_costs'], 2) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- Material Items Table — direct foreach like edit page              -->
    <!-- ================================================================ -->
    <div class="card__wrapper shadow-sm mb-4">
        <div class="card__header d-flex justify-content-between align-items-center">
            <h6 class="card__title mb-0">
                <i class="bi bi-boxes me-2"></i>Material Items
                <span class="badge bg-success ms-1"><?= count($items) ?></span>
            </h6>
        </div>
        <div class="card__body p-0">
            <?php if (!empty($items)): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Design Type</th>
                            <th>Material</th>
                            <th class="text-end" style="width:80px">Unit</th>
                            <th class="text-end" style="width:110px">Qty</th>
                            <th class="text-end" style="width:130px">Unit Cost (৳)</th>
                            <th class="text-end" style="width:140px">Subtotal (৳)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $matGrandTotal = 0;
                        foreach ($items as $si => $item):
                            $sub = $item['estimated_qty'] * $item['unit_cost_approx'];
                            $matGrandTotal += $sub;
                        ?>
                        <tr>
                            <td class="text-muted"><?= $si + 1 ?></td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?= htmlspecialchars($item['design_type_name'] ?? '—') ?>
                                </span>
                            </td>
                            <td class="fw-semibold"><?= htmlspecialchars($item['material_name'] ?? '—') ?></td>
                            <td class="text-end text-muted small"><?= htmlspecialchars($item['material_unit'] ?? '') ?></td>
                            <td class="text-end"><?= number_format($item['estimated_qty'], 3) ?></td>
                            <td class="text-end"><?= number_format($item['unit_cost_approx'], 2) ?></td>
                            <td class="text-end fw-bold text-success"><?= number_format($sub, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="6" class="text-end fw-bold py-2">Material Total:</td>
                            <td class="text-end fw-bold text-success py-2">
                                <?= number_format($matGrandTotal, 2) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php else: ?>
                <div class="p-4 text-center text-muted">No material items added.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- Labor Table — direct foreach like edit page                       -->
    <!-- ================================================================ -->
    <div class="card__wrapper shadow-sm mb-4">
        <div class="card__header d-flex justify-content-between align-items-center">
            <h6 class="card__title mb-0">
                <i class="bi bi-people me-2"></i>Labor Costs
                <span class="badge bg-info ms-1"><?= count($labors) ?></span>
            </h6>
        </div>
        <div class="card__body p-0">
            <?php if (!empty($labors)): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Design Type</th>
                            <th>Labor Type</th>
                            <th class="text-end" style="width:150px">Rate / sqft (৳)</th>
                            <th class="text-end" style="width:130px">Area (sqft)</th>
                            <th class="text-end" style="width:140px">Subtotal (৳)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $labGrandTotal = 0;
                        foreach ($labors as $li => $lb):
                            $sub = $lb['rate_per_sqft'] * $lb['area_sqft'];
                            $labGrandTotal += $sub;
                        ?>
                        <tr>
                            <td class="text-muted"><?= $li + 1 ?></td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?= htmlspecialchars($lb['design_type_name'] ?? '—') ?>
                                </span>
                            </td>
                            <td class="fw-semibold"><?= htmlspecialchars($lb['labor_type_name']) ?></td>
                            <td class="text-end"><?= number_format($lb['rate_per_sqft'], 2) ?></td>
                            <td class="text-end"><?= number_format($lb['area_sqft'], 2) ?></td>
                            <td class="text-end fw-bold text-info"><?= number_format($sub, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="5" class="text-end fw-bold py-2">Labor Total:</td>
                            <td class="text-end fw-bold text-info py-2">
                                <?= number_format($labGrandTotal, 2) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php else: ?>
                <div class="p-4 text-center text-muted">No labor records added.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- Approval / Cancel Actions                                          -->
    <!-- ================================================================ -->
    <?php if (!$locked && $budget['status'] === 'pending'): ?>
    <div class="card__wrapper shadow-sm mb-4 border-warning">
        <div class="card__header">
            <h6 class="card__title mb-0">
                <i class="bi bi-shield-check me-2"></i>Approval Actions
            </h6>
        </div>
        <div class="card__body p-4 d-flex gap-3 flex-wrap align-items-center">

            <form action="<?= $base_url ?>/con-estimate-approve/<?= $budget['id'] ?>" method="POST">
                <button type="submit" class="btn btn-success px-4"
                        onclick="return confirm('Approve this budget? This cannot be undone.')">
                    <i class="bi bi-check-circle me-1"></i> Approve Budget
                </button>
            </form>

            <button type="button" class="btn btn-danger px-4"
                    data-bs-toggle="modal" data-bs-target="#cancelModal">
                <i class="bi bi-x-circle me-1"></i> Cancel Budget
            </button>
        </div>
    </div>

    <!-- Cancel Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="<?= $base_url ?>/con-estimate-cancel/<?= $budget['id'] ?>" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cancelModalLabel">
                            <i class="bi bi-exclamation-triangle text-danger me-2"></i>Cancel Budget
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label fw-bold">
                            Rejection Note <span class="text-danger">*</span>
                        </label>
                        <textarea name="rejection_note" class="form-control" rows="4" required
                                  placeholder="Explain why this budget is being cancelled…"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger px-4">Confirm Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

</section>

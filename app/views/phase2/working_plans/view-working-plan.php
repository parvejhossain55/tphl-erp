<?php
// Get approval badge
$statusBadge = match($plan['approved_status'] ?? 'Pending') {
    'Approved' => '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Approved</span>',
    'Rejected' => '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Rejected</span>',
    default    => '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Pending</span>',
};
$monthName = date('F', mktime(0, 0, 0, $plan['month'], 10));
?>

<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-working-plans">Working Plans</a></li>
                <li class="breadcrumb-item active">View Plan</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $base_url ?>/con-working-plan-print/<?= $plan['id'] ?>"
           target="_blank"
           class="btn btn-primary">
            <i class="bi bi-printer me-1"></i> Print / PDF
        </a>
        <a href="<?= $base_url ?>/con-working-plan-edit/<?= $plan['id'] ?>" class="btn btn-outline-warning">
            <i class="bi bi-pencil-square me-1"></i> Edit
        </a>
        <a href="<?= $base_url ?>/con-working-plans" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>
</div>

<section class="section">
    <div class="row g-4">

        <!-- ── Left: Plan Details Card ───────────────────── -->
        <div class="col-lg-8">
            <div class="card__wrapper shadow-sm h-100">
                <div class="card__body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <h5 class="fw-bold text-primary mb-0">
                            <i class="bi bi-calendar-check me-2"></i>Plan Details
                        </h5>
                        <?= $statusBadge ?>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase">Project</label>
                            <div class="fw-bold fs-6"><?= htmlspecialchars($plan['project_name'] ?? '—') ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase">Budget Reference</label>
                            <div>
                                <span class="badge border text-dark">Budget #<?= $plan['initial_budget_id'] ?></span>
                                <?php if ($plan['budget_total']): ?>
                                    <div class="small text-muted mt-1">৳ <?= number_format($plan['budget_total'], 2) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase">Work Type</label>
                            <div>
                                <span class="badge bg-info text-dark"><?= htmlspecialchars($plan['work_type_name'] ?? '—') ?></span>
                                <?php if (!empty($plan['design_type_name'])): ?>
                                    <span class="badge bg-light text-secondary ms-1"><?= htmlspecialchars($plan['design_type_name']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase">Period</label>
                            <div>
                                <span class="badge bg-secondary fs-6 px-3 py-1"><?= $monthName ?> <?= $plan['year'] ?></span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="text-muted small fw-semibold text-uppercase">Work Description</label>
                            <div class="bg-light rounded p-3 mt-1" style="min-height:60px;">
                                <?= nl2br(htmlspecialchars($plan['work_description'] ?? '—')) ?>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <label class="text-muted small fw-semibold text-uppercase">Planned Quantity</label>
                            <div class="fw-bold"><?= number_format($plan['planned_quantity'] ?? 0, 2) ?></div>
                        </div>
                        <div class="col-sm-4">
                            <label class="text-muted small fw-semibold text-uppercase">Estimated Cost</label>
                            <div class="fw-bold text-primary fs-5">৳ <?= number_format($plan['estimated_cost'], 2) ?></div>
                        </div>
                        <div class="col-sm-4">
                            <label class="text-muted small fw-semibold text-uppercase">Weight (%)</label>
                            <div class="fw-bold text-success fs-5"><?= number_format($plan['weight_percentage'] ?? 0, 2) ?>%</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase">Created By</label>
                            <div><?= htmlspecialchars($plan['created_by_name'] ?? '—') ?></div>
                        </div>
                        <?php if (!empty($plan['approved_by_name'])): ?>
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase">Approved By</label>
                            <div><?= htmlspecialchars($plan['approved_by_name']) ?>
                                <?php if (!empty($plan['approved_by_role'])): ?>
                                    <small class="text-muted">(<?= htmlspecialchars($plan['approved_by_role']) ?>)</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Right: Budget Summary ──────────────────────── -->
        <div class="col-lg-4">
            <div class="card__wrapper shadow-sm mb-4">
                <div class="card__body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-wallet2 me-2 text-primary"></i>Budget Summary</h6>
                    <?php
                    $initial   = $budgetSummary['initial_budget'] ?? 0;
                    $revised   = $budgetSummary['revised_total'] ?? 0;
                    $planned   = $budgetSummary['total_planned'] ?? 0;
                    $remaining = $budgetSummary['remaining_to_plan'] ?? 0;
                    $pct = $revised > 0 ? min(100, ($planned / $revised) * 100) : 0;
                    ?>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Initial Budget</span>
                        <span class="fw-semibold">৳ <?= number_format($initial, 2) ?></span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Revised Total</span>
                        <span class="fw-semibold">৳ <?= number_format($revised, 2) ?></span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">Total Planned</span>
                        <span class="fw-semibold text-primary">৳ <?= number_format($planned, 2) ?></span>
                    </div>
                    <div class="mb-3 d-flex justify-content-between">
                        <span class="text-muted small">Remaining</span>
                        <span class="fw-bold <?= $remaining < 0 ? 'text-danger' : 'text-success' ?>">
                            ৳ <?= number_format($remaining, 2) ?>
                        </span>
                    </div>
                    <div class="progress" style="height:8px;">
                        <div class="progress-bar <?= $pct >= 100 ? 'bg-danger' : 'bg-primary' ?>"
                             style="width:<?= $pct ?>%"></div>
                    </div>
                    <div class="small text-muted mt-1 text-end"><?= number_format($pct, 1) ?>% utilized</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card__wrapper shadow-sm">
                <div class="card__body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-lightning me-2 text-warning"></i>Quick Actions</h6>
                    <div class="d-grid gap-2">
                        <a href="<?= $base_url ?>/con-working-plan-print/<?= $plan['id'] ?>"
                           target="_blank"
                           class="btn btn-primary">
                            <i class="bi bi-printer me-2"></i>Print / Export PDF
                        </a>
                        <a href="<?= $base_url ?>/con-working-plan-edit/<?= $plan['id'] ?>"
                           class="btn btn-outline-warning">
                            <i class="bi bi-pencil-square me-2"></i>Edit This Plan
                        </a>
                        <a href="<?= $base_url ?>/con-working-plans"
                           class="btn btn-outline-secondary">
                            <i class="bi bi-list-ul me-2"></i>All Working Plans
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── All Plans for this Project ─────────────────── -->
        <div class="col-12">
            <div class="card__wrapper shadow-sm">
                <div class="card__body p-0">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-table me-2 text-primary"></i>
                            All Plans — <?= htmlspecialchars($plan['project_name'] ?? '') ?>
                        </h6>
                        <span class="badge bg-primary"><?= count($projectPlans) ?> entries</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Period</th>
                                    <th>Work Type</th>
                                    <th>Description</th>
                                    <th class="text-end">Cost (৳)</th>
                                    <th class="text-end">Weight</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projectPlans as $i => $p): ?>
                                <tr class="<?= $p['id'] == $plan['id'] ? 'table-primary fw-bold' : '' ?>">
                                    <td><?= $i + 1 ?></td>
                                    <td><?= date('M', mktime(0,0,0,$p['month'],10)) ?> <?= $p['year'] ?></td>
                                    <td><span class="badge bg-info text-dark"><?= htmlspecialchars($p['work_type_name'] ?? '—') ?></span></td>
                                    <td>
                                        <div class="text-truncate" style="max-width:200px;" title="<?= htmlspecialchars($p['work_description'] ?? '') ?>">
                                            <?= htmlspecialchars($p['work_description'] ?? '—') ?>
                                        </div>
                                    </td>
                                    <td class="text-end">৳ <?= number_format($p['estimated_cost'], 2) ?></td>
                                    <td class="text-end"><?= number_format($p['weight_percentage'] ?? 0, 2) ?>%</td>
                                    <td class="text-center">
                                        <?php $s = $p['approved_status'] ?? 'Pending'; ?>
                                        <span class="badge <?= $s === 'Approved' ? 'bg-success' : ($s === 'Rejected' ? 'bg-danger' : 'bg-warning text-dark') ?>">
                                            <?= $s ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Total:</td>
                                    <td class="text-end fw-bold text-primary">
                                        ৳ <?= number_format(array_sum(array_column($projectPlans, 'estimated_cost')), 2) ?>
                                    </td>
                                    <td class="text-end fw-bold text-success">
                                        <?= number_format(array_sum(array_column($projectPlans, 'weight_percentage')), 2) ?>%
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

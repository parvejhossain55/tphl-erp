<div class="pagetitle d-flex justify-content-between align-items-center">
    <div>
        <h1><?= htmlspecialchars($page_title) ?></h1>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Initial Estimates</li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-estimate-create" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i> New Estimate
    </a>
</div>

<section class="section">
    <div class="card__wrapper shadow-sm">
        <div class="card__body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped custom-table mb-0 multiple-table">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Project</th>
                            <th class="text-end">Material Cost (৳)</th>
                            <th class="text-end">Labor Cost (৳)</th>
                            <th class="text-end">Other Cost (৳)</th>
                            <th class="text-end">Grand Total (৳)</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th class="text-center" style="width:110px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($budgets)): ?>
                            <?php foreach ($budgets as $index => $b): ?>
                            <tr>
                                <td class="text-muted"><?= $index + 1 ?></td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($b['project_name']) ?></div>
                                    <small class="text-muted">
                                        Area: <?= number_format($b['total_area_sqft'] ?? 0, 0) ?> sqft
                                    </small>
                                </td>
                                <td class="text-end"><?= number_format($b['material_cost_total'], 2) ?></td>
                                <td class="text-end"><?= number_format($b['labor_cost_total'], 2) ?></td>
                                <td class="text-end"><?= number_format($b['other_costs'], 2) ?></td>
                                <td class="text-end fw-bold text-primary">
                                    <?= number_format($b['total_estimated_cost'], 2) ?>
                                </td>
                                <td>
                                    <?php
                                    $sc = match($b['status']) {
                                        'approved'  => 'bg-success',
                                        'cancelled' => 'bg-danger',
                                        default     => 'bg-warning text-dark',
                                    };
                                    ?>
                                    <span class="badge <?= $sc ?>"><?= ucfirst($b['status']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($b['created_by_name']) ?></td>
                                <td class="text-center">
                                    <a href="<?= $base_url ?>/con-estimate-show/<?= $b['id'] ?>"
                                       class="btn btn-sm btn-outline-info" title="View Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if ($b['status'] === 'pending'): ?>
                                        <a href="<?= $base_url ?>/con-estimate-edit/<?= $b['id'] ?>"
                                           class="btn btn-sm btn-outline-warning ms-1" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="btn btn-sm btn-outline-secondary ms-1 disabled"
                                              title="<?= ucfirst($b['status']) ?> — cannot edit">
                                            <i class="bi bi-lock"></i>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox me-2"></i>No estimate budgets yet.
                                    <a href="<?= $base_url ?>/con-estimate-create" class="ms-2">Create one →</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

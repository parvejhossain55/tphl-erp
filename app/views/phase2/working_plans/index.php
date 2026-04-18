<div class="container-fluid pt-4">
    <div class="row">
        <div class="col-12">
            <div class="card__wrapper">
                <div class="card__header d-flex justify-content-between align-items-center">
                    <h5 class="card__title mb-0">Project Working Plans (Monthly)</h5>
                    <div class="btn-group">
                        <a href="<?= $base_url ?>/con-working-plan-create" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-calendar-plus me-1"></i> New Plan Entry
                        </a>
                        <a href="<?= $base_url ?>/con-budget-extends" class="btn btn-outline-warning btn-sm">
                            <i class="fa-solid fa-coins me-1"></i> Budget Extends
                        </a>
                    </div>
                </div>
                <div class="card__body p-0">
                    <div class="table__wrapper">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Project</th>
                                    <th>Period</th>
                                    <th>Material</th>
                                    <th class="text-end">Planned Qty</th>
                                    <th class="text-end">Est. Cost</th>
                                    <th>Created By</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($plans)): ?>
                                    <?php foreach ($plans as $index => $plan): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><strong><?= htmlspecialchars($plan['project_name']) ?></strong></td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    <?= date('F', mktime(0, 0, 0, $plan['month'], 10)) ?> <?= $plan['year'] ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($plan['material_name']) ?> (<?= $plan['unit'] ?>)</td>
                                            <td class="text-end fw-bold"><?= number_format($plan['planned_quantity'], 3) ?></td>
                                            <td class="text-end"><?= number_format($plan['estimated_cost'], 2) ?></td>
                                            <td><?= htmlspecialchars($plan['created_by_name']) ?></td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-link text-primary p-0" title="Edit">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">No working plans defined yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

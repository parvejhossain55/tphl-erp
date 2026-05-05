<div class="mb-3 pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="">
        <h4><?= htmlspecialchars($page_title) ?></h4>
    </div>
    <div class="row justify-content-end mb-3 mt-3">
        <div class="col-12">
            <div class="card__wrapper border-0 p-0 shadow-none bg-transparent">
                <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end">
                    <a href="<?= $base_url ?>/con-estimate-create" class="btn btn-primary text-nowrap">
                        <i class="bi bi-plus-circle me-1"></i> New Estimate
                    </a>
                    <button type="button" class="btn btn-outline-success text-nowrap" data-bs-toggle="modal" data-bs-target="#importEstimateCsvModal">
                        <i class="bi bi-file-earmark-arrow-up me-1"></i> Import CSV
                    </button>
                </div>
            </div>
        </div>
    </div>
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

<!-- Import CSV Modal -->
<div class="modal fade" id="importEstimateCsvModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Initial Estimate Budgets</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal"><i class="fa-solid fa-xmark-large"></i></button>
            </div>
            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/con-estimate-import" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Upload CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>
                    <div class="mb-3" style="font-size:12px;">
                        <strong>Required CSV Columns:</strong><br>
                        <code>Project_Name (required), Work_Type_Name, Total_Area_Sqft, Material_Cost_Total, Labor_Cost_Total, Other_Costs, Total_Estimated_Cost, Start_Date (YYYY-MM-DD), End_Date (YYYY-MM-DD)</code>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm ms-2"><i class="bi bi-upload me-1"></i> Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

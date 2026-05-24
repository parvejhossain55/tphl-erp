<div class="mb-3 pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="">
        <h4><?= htmlspecialchars($page_title) ?></h4>
    </div>
    <div class="row justify-content-end mb-3 mt-3">
        <div class="col-12">
            <div class="card__wrapper border-0 p-0 shadow-none bg-transparent">
                <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end">
                    <?php if (!in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])): ?>
                    <a href="<?= $base_url ?>/con-estimate-create" class="btn btn-primary text-nowrap">
                        <i class="bi bi-plus-circle me-1"></i> New Estimate
                    </a>
                    <button type="button" class="btn btn-outline-success text-nowrap" data-bs-toggle="modal" data-bs-target="#importEstimateCsvModal">
                        <i class="bi bi-file-earmark-arrow-up me-1"></i> Import CSV
                    </button>
                    <?php endif; ?>
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
                                        'pending'   => 'bg-warning text-dark',
                                        'draft'     => 'bg-secondary',
                                        default     => 'bg-secondary',
                                    };
                                    ?>
                                    <span class="badge <?= $sc ?>"><?= ucfirst($b['status']) ?></span>
                                    <?php if ($b['status'] === 'approved' && !empty($b['approved_by_name'])): ?>
                                        <div style="font-size: 11px; margin-top: 4px; color: var(--text-muted); font-weight: 600;">
                                            by <?= htmlspecialchars($b['approved_by_name']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($b['created_by_name']) ?></td>
                                <td class="text-center">
                                    <a href="<?= $base_url ?>/con-estimate-show/<?= $b['id'] ?>"
                                       class="btn btn-sm btn-outline-info" title="View Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if (!in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])): ?>
                                        <?php if (in_array($b['status'], ['draft', 'pending'])): ?>
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
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-earmark-arrow-up me-2"></i>Import Initial Estimates (CSV)</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal"><i class="fa-solid fa-xmark-large"></i></button>
            </div>
            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/con-estimate-import" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Upload CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>

                    <!-- Format instructions -->
                    <div class="border rounded p-2 mb-2" style="font-size:12px;">
                        <div class="fw-semibold mb-2"><i class="bi bi-info-circle text-primary me-1"></i>CSV Format — একটি ফাইলে Budget + Items + Labors:</div>
                        <div class="mb-1">
                            <span class="badge bg-primary me-1">BUDGET</span>
                            <span class="text-danger fw-semibold">Project_Name</span>
                            <span class="text-muted ms-1">+ Total_Area_Sqft, Other_Costs, Other_Cost_Detail, Budget_Link, Start_Date, End_Date, Status, Approved_By_Email, Approval_Date</span>
                        </div>
                        <div class="mb-1">
                            <span class="badge bg-success me-1">ITEM</span>
                            <span class="text-danger fw-semibold">Material_Name, Estimated_Qty, Unit_Cost</span>
                            <span class="text-muted ms-1">+ Material_Unit, Item_Remarks, Work_Type_Name</span>
                        </div>
                        <div class="mb-2">
                            <span class="badge bg-warning text-dark me-1">LABOR</span>
                            <span class="text-danger fw-semibold">Labor_Type_Name, Rate_Per_Sqft, Area_Sqft</span>
                            <span class="text-muted ms-1">+ Work_Type_Name, Labor_Description</span>
                        </div>
                        <div class="text-muted" style="font-size:11px; border-top:1px solid #dee2e6; padding-top:6px;">
                            ⚠️ ITEM ও LABOR rows সবসময় তার উপরের BUDGET row-এর সাথে link হবে।<br>
                            ⚠️ Project_Name ও Material_Name অবশ্যই database-এর exact name হতে হবে।<br>
                            ⚠️ Status: <code>pending</code> / <code>approved</code> / <code>cancelled</code>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <a href="<?= $GLOBALS['base_url'] ?>/con-estimate-import?sample=1"
                           class="btn btn-outline-secondary btn-sm"
                           onclick="this.href='<?= $GLOBALS['base_url'] ?>/con-estimate-import?sample=1'">
                            <i class="bi bi-download me-1"></i> Download Sample CSV
                        </a>
                        <div>
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success btn-sm ms-2">
                                <i class="bi bi-upload me-1"></i> Import
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

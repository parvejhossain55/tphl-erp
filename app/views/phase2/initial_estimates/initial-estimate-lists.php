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
                    <button type="button" class="btn btn-outline-success text-nowrap" data-bs-toggle="modal" data-bs-target="#importEstimateExcelModal">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Import Excel
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($_SESSION['csv_import_errors'])): ?>
    <div class="alert alert-danger p-3 mb-4">
        <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>CSV Import Failed (No data was imported)</h6>
        <p class="mb-2 fs-12">Please fix the following errors in your CSV file and try again:</p>
        <div class="table-responsive" style="max-height: 250px;">
            <table class="table table-sm table-bordered bg-white text-dark mb-0 fs-11">
                <thead>
                    <tr class="bg-light">
                        <th class="py-1" style="width: 80px;">Row No</th>
                        <th class="py-1" style="width: 180px;">Column / Data</th>
                        <th class="py-1">Error Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['csv_import_errors'] as $err): ?>
                        <tr>
                            <td class="py-1 font-monospace"><?= htmlspecialchars($err['row'] ?? '—') ?></td>
                            <td class="py-1 text-danger font-monospace"><?= htmlspecialchars($err['column'] ?? '—') ?></td>
                            <td class="py-1 text-danger"><?= htmlspecialchars($err['message'] ?? $err) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php unset($_SESSION['csv_import_errors']); ?>
<?php endif; ?>

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
                                    <?php 
                                    $projId = (int)($b['project_id'] ?? 0);
                                    $spentTotal = $spentMap[$projId]['total_spent'] ?? 0.0;
                                    ?>
                                    <div class="text-muted" style="font-size: 11px; font-weight: normal; margin-top: 4px;">
                                        Spent: <span class="text-danger fw-semibold">৳<?= number_format($spentTotal, 2) ?></span>
                                    </div>
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
                                    <div class="d-flex justify-content-center align-items-center gap-1">
                                        <a href="<?= $base_url ?>/con-estimate-show/<?= $b['id'] ?>"
                                           class="btn btn-sm btn-outline-info" title="View Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (!in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])): ?>
                                            <?php if (in_array($b['status'], ['draft', 'pending'])): ?>
                                                <a href="<?= $base_url ?>/con-estimate-edit/<?= $b['id'] ?>"
                                                   class="btn btn-sm btn-outline-warning" title="Edit">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="btn btn-sm btn-outline-secondary disabled"
                                                      title="<?= ucfirst($b['status']) ?> — cannot edit">
                                                    <i class="bi bi-lock"></i>
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
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
                    <div class="mb-3">
                        <div class="fw-semibold mb-2 text-dark fs-12"><i class="bi bi-info-circle text-primary me-1"></i>CSV Column Structure:</div>
                        <div class="table-responsive border rounded">
                            <table class="table table-sm table-bordered bg-white mb-0 align-middle fs-11">
                                <thead class="table-light text-nowrap">
                                    <tr>
                                        <th class="py-2" style="width: 140px;">Row Type (Row_Type)</th>
                                        <th class="py-2 text-danger">Required Fields</th>
                                        <th class="py-2 text-muted">Optional Fields</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><span class="badge bg-primary px-2 py-1">BUDGET</span></td>
                                        <td class="fw-semibold text-danger">Project_Name, Estimator_Email, Total_Area_Sqft</td>
                                        <td class="text-muted">Approved_By_Email, Start_Date, End_Date, Drive_Link, Other_Costs, Other_Cost_Detail</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-success px-2 py-1">ITEM (Material)</span></td>
                                        <td class="fw-semibold text-danger">Material_Name, Material_Unit, Estimated_Qty, Unit_Cost, Work_Type_Name, Phase_Item_Name</td>
                                        <td class="text-muted">Floor_Type, Floor_No, Location, Item_Remarks</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-warning text-dark px-2 py-1">LABOR</span></td>
                                        <td class="fw-semibold text-danger">Labor_Type_Name, Rate_Per_Sqft, Area_Sqft, Work_Type_Name, Phase_Item_Name</td>
                                        <td class="text-muted">Labor_Description</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="alert alert-warning py-2 px-3 mb-2 fs-11" style="border: 1px solid rgba(255, 193, 7, 0.3); background-color: #fffdf5;">
                        <div class="fw-bold text-warning-emphasis mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Rules & Validations:</div>
                        <ul class="mb-0 ps-3">
                            <li><strong>Strict Project Match:</strong> Project name must match an existing project in the ERP or the import fails.</li>
                            <li><strong>Architect Engineering:</strong> The matched project must have an active Architect Engineering setup or the import fails.</li>
                            <li><strong>Estimator Email:</strong> Must belong to a valid registered ERP user.</li>
                            <li><strong>Dynamic Creation:</strong> Any Work Type, Material, or Phase Item not found in database will be dynamically created by name.</li>
                        </ul>
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

<!-- Import Excel Modal -->
<div class="modal fade" id="importEstimateExcelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Import Initial Estimates (Excel)</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal"><i class="fa-solid fa-xmark-large"></i></button>
            </div>
            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/con-estimate-import-excel" method="post" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Select Project <span class="text-danger">*</span></label>
                            <select name="project_id" class="form-select" required>
                                <option value="">-- Select Project --</option>
                                <?php if (!empty($projects)): ?>
                                    <?php foreach ($projects as $proj): ?>
                                        <option value="<?= $proj['id'] ?>"><?= htmlspecialchars($proj['name']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Upload Excel File (.xlsx, .xls) <span class="text-danger">*</span></label>
                            <input type="file" name="excel_file" class="form-control" accept=".xlsx, .xls" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Estimator Email <span class="text-danger">*</span></label>
                            <input type="email" name="estimator_email" class="form-control" value="<?= htmlspecialchars($_SESSION['user']['email'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Approved By (Manager) Email</label>
                            <input type="email" name="approved_by_email" class="form-control" placeholder="manager@example.com">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Start Date</label>
                            <input type="date" name="start_date" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">End Date</label>
                            <input type="date" name="end_date" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Drive Link</label>
                        <input type="url" name="budget_link" class="form-control" placeholder="https://drive.google.com/...">
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Other Costs (approx)</label>
                            <input type="number" name="other_costs" class="form-control" step="0.01" value="0.00">
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-semibold">Other Cost Details</label>
                            <textarea name="other_cost_detail" class="form-control" rows="2" placeholder="Fencing, temporary shed, site setup costs etc."></textarea>
                        </div>
                    </div>

                    <div class="alert alert-warning py-2 px-3 mb-2 fs-11" style="border: 1px solid rgba(255, 193, 7, 0.3); background-color: #fffdf5;">
                        <div class="fw-bold text-warning-emphasis mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Excel Import Rules:</div>
                        <ul class="mb-0 ps-3">
                            <li><strong>Design Type Mapping:</strong> BOQ sheets matching `Structural work`, `Finshing work`, `Electric item`, etc. will be imported automatically.</li>
                            <li><strong>Labor Cost:</strong> Labor costs are extracted from the `Sub-Contractor Bill` sheet.</li>
                            <li><strong>Architect Setup:</strong> The selected project must have an active Architect Engineering setup.</li>
                            <li><strong>Estimator & Manager Validation:</strong> Emails entered must belong to registered ERP users.</li>
                        </ul>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm ms-2">
                            <i class="bi bi-upload me-1"></i> Import Excel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


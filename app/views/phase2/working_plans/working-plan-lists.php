<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Working Plans</li>
            </ol>
        </nav>
    </div>
    <div class="row justify-content-end mb-3 mt-3">
        <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-6 col-12">
            <div class="card__wrapper border-0 p-0 shadow-none bg-transparent">
                <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end">
                    <a href="<?= $base_url ?>/con-budget-extends" class="btn btn-outline-warning text-nowrap">
                        <i class="bi bi-coin me-1"></i> Budget Extensions
                    </a>
                    <a href="<?= $base_url ?>/con-working-plan-create" class="btn btn-primary text-nowrap">
                        <i class="bi bi-plus-circle me-1"></i> New Plan Entry
                    </a>
                    <button type="button" class="btn btn-outline-success text-nowrap" data-bs-toggle="modal" data-bs-target="#importWorkingPlanCsvModal">
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
                            <th>#</th>
                            <th>Project</th>
                            <th>Budget Ref.</th>
                            <th>Work Type</th>
                            <th>Period</th>
                            <th>Work Description</th>
                            <th class="text-end">Planned Cost (৳)</th>
                            <th>Created By</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($plans)): ?>
                            <?php foreach ($plans as $i => $plan): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($plan['project_name']) ?></td>
                                <td>
                                    <span class="badge  text-dark border">
                                        Budget #<?= $plan['initial_budget_id'] ?>
                                    </span>
                                    <?php if ($plan['budget_total']): ?>
                                    <div class="small text-muted">৳ <?= number_format($plan['budget_total'], 0) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info text-dark">
                                        <?= htmlspecialchars($plan['work_type_name'] ?? '—') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= date('F', mktime(0, 0, 0, $plan['month'], 10)) ?>
                                        <?= $plan['year'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 220px;"
                                         title="<?= htmlspecialchars($plan['work_description'] ?? '') ?>">
                                        <?= htmlspecialchars($plan['work_description'] ?? '—') ?>
                                    </div>
                                </td>
                                <td class="text-end fw-bold text-primary">
                                    ৳ <?= number_format($plan['estimated_cost'], 2) ?>
                                </td>
                                <td><?= htmlspecialchars($plan['created_by_name'] ?? '—') ?></td>
                                <td class="text-center">
                                    <a href="<?= $base_url ?>/con-working-plan-edit/<?= $plan['id'] ?>"
                                       class="btn btn-sm btn-outline-warning" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="<?= $base_url ?>/con-working-plan-delete/<?= $plan['id'] ?>"
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this plan entry?');">
                                        <button type="submit" class="btn btn-sm btn-outline-danger ms-1" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="bi bi-calendar-x me-2"></i>No working plan entries yet.
                                    <a href="<?= $base_url ?>/con-working-plan-create" class="ms-2">Create one →</a>
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
<div class="modal fade" id="importWorkingPlanCsvModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Working Plans</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal"><i class="fa-solid fa-xmark-large"></i></button>
            </div>
            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/con-working-plan-import" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Upload CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>
                    
                    <div class="border rounded p-2 mb-3 " style="font-size:12px;">
                        <div class="fw-semibold mb-2"><i class="bi bi-info-circle text-primary me-1"></i>CSV Format — Required Columns:</div>
                        <code>Project_Name, Year, Month, Work_Description</code><br>
                        <strong>Optional Columns:</strong><br>
                        <code class="text-muted">Work_Type_Name, Planned_Quantity, Estimated_Cost, Created_By_Email, Approved_Status, Approved_By_Email</code>
                        
                        <div class="text-muted mt-2" style="font-size:11px; border-top:1px solid #dee2e6; padding-top:6px;">
                            ⚠️ <strong>Project_Name</strong> অবশ্যই ডাটাবেসে থাকা নামের সাথে হুবহু মিলতে হবে।<br>
                            ⚠️ Project_Name থেকে স্বয়ংক্রিয়ভাবে initial_budget_id লিঙ্ক করা হবে।
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="<?= $GLOBALS['base_url'] ?>/con-working-plan-import?sample=1" class="btn btn-outline-secondary btn-sm" onclick="this.href='<?= $GLOBALS['base_url'] ?>/con-working-plan-import?sample=1'">
                            <i class="bi bi-download me-1"></i> Download Sample CSV
                        </a>
                        <div>
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success btn-sm ms-2"><i class="bi bi-upload me-1"></i> Import</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

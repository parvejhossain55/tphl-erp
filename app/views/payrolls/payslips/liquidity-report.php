<?php
$page = "Liquidity Management";
$show_breadcrumb = true;
$selectedProjectId = $selected_project_id ?? null;
?>

<!-- Filter & Selection Section -->
<div class="row mb-3">
    <div class="col-12">
        <div class="filters card__wrapper p-3 bg-white rounded shadow-sm">
            <form method="GET" action="<?= $base_url ?>/payroll-liquidity-report" id="filterForm" class="row gx-3 gy-2 align-items-end">
                <div class="col-lg-8 col-md-8 col-sm-12">
                    <label class="form-label fw-semibold text-muted mb-1">
                        <i class="fa-solid fa-folder-tree text-primary me-1"></i> Select Payroll Workplace Project <span class="text-danger">*</span>
                    </label>
                    <select name="payroll_project_id" class="form-select select2" required onchange="this.form.submit()">
                        <option value="">-- Select Payroll Project --</option>
                        <?php foreach ($payroll_projects as $proj): ?>
                            <option value="<?= htmlspecialchars($proj['id']) ?>" <?= ($selectedProjectId == $proj['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($proj['name']) ?> 
                                <?= !empty($proj['date']) ? ' (' . htmlspecialchars($proj['date']) . ')' : (!empty($proj['start_date']) ? ' (' . htmlspecialchars($proj['start_date']) . ')' : '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-lg-4 col-md-4 col-sm-12 text-md-end mt-3 mt-md-0">
                    <button type="submit" class="btn btn-primary px-4 fw-semibold w-100 w-md-auto shadow-sm">
                        <i class="fa-solid fa-filter me-1"></i> Generate Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($selectedProjectId && !empty($selectedProject)): ?>
    <!-- 3 Top KPI Cards -->
    <div class="row g-3 mb-4">
        <!-- 1st Card: Total Payroll Requirement -->
        <div class="col-lg-4 col-md-6 col-sm-12">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-success h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="fs-12 text-uppercase text-muted fw-bold">Total Payroll Requirement</span>
                        <h3 class="fw-bold mb-0 text-success mt-1">৳ <?= number_format($totalRequirement, 2) ?></h3>
                        <small class="text-muted fs-12">Total <?= count($bankEmployees) + count($cashEmployees) ?> Employees</small>
                    </div>
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 text-success">
                        <i class="fa-solid fa-wallet fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2nd Card: Bank Transfer Requirement -->
        <div class="col-lg-4 col-md-6 col-sm-12">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-primary h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="fs-12 text-uppercase text-muted fw-bold">Bank Transfer Requirement</span>
                        <h3 class="fw-bold mb-0 text-primary mt-1">৳ <?= number_format($totalBankRequirement, 2) ?></h3>
                        <small class="text-muted fs-12"><?= count($bankEmployees) ?> Employees via Bank</small>
                    </div>
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 text-primary">
                        <i class="fa-solid fa-building-columns fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3rd Card: Cash in Hand Requirement -->
        <div class="col-lg-4 col-md-12 col-sm-12">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-warning h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="fs-12 text-uppercase text-muted fw-bold">Cash in Hand Requirement</span>
                        <h3 class="fw-bold mb-0 text-warning text-darken mt-1">৳ <?= number_format($totalCashRequirement, 2) ?></h3>
                        <small class="text-muted fs-12"><?= count($cashEmployees) ?> Employees via Cash</small>
                    </div>
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 text-warning">
                        <i class="fa-solid fa-money-bill-wave fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Box -->
    <div class="card__wrapper bg-white rounded shadow-sm p-4 mb-4">
        <!-- Title & Action Buttons -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 border-bottom pb-3 gap-2">
            <div>
                <h4 class="fw-bold text-dark mb-1">
                    <i class="fa-solid fa-chart-pie text-primary me-2"></i>Liquidity Management Report
                </h4>
                <p class="text-muted mb-0 fs-13">
                    Project: <strong class="text-dark"><?= htmlspecialchars($selectedProject['name']) ?></strong> 
                    <?= !empty($selectedProject['date']) ? '(' . htmlspecialchars($selectedProject['date']) . ')' : '' ?>
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= $base_url ?>/payroll-liquidity-report-csv?payroll_project_id=<?= $selectedProjectId ?>" 
                   class="btn btn-outline-success fw-semibold shadow-sm btn-sm">
                    <i class="fa-solid fa-file-csv me-1"></i> Export CSV
                </a>
                <a href="<?= $base_url ?>/payroll-liquidity-report-pdf?payroll_project_id=<?= $selectedProjectId ?>" 
                   target="_blank" class="btn btn-outline-danger fw-semibold shadow-sm btn-sm">
                    <i class="fa-solid fa-file-pdf me-1"></i> Export PDF
                </a>
                <button type="button" onclick="window.print()" class="btn btn-outline-secondary fw-semibold shadow-sm btn-sm">
                    <i class="fa-solid fa-print me-1"></i> Print
                </button>
            </div>
        </div>

        <!-- 1. Bank Transfer Table -->
        <div class="mb-5">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h5 class="fw-bold text-primary mb-0">
                    <i class="fa-solid fa-building-columns me-2"></i>1. Bank Transfer Disbursement List
                </h5>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 fs-12">
                    Total: <?= count($bankEmployees) ?> Staff
                </span>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-striped align-middle mb-0">
                    <thead class="table-primary text-uppercase fs-11">
                        <tr>
                            <th class="text-center" style="width: 50px;">SL</th>
                            <th class="text-center" style="width: 120px;">Employee ID</th>
                            <th>Employee Name</th>
                            <th>Department</th>
                            <th class="text-center" style="width: 110px;">Payment Type</th>
                            <th>Bank Name</th>
                            <th>Account Number</th>
                            <th class="text-end pe-3" style="width: 150px;">Net Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($bankEmployees)): ?>
                            <?php $bSl = 1; foreach ($bankEmployees as $bEmp): ?>
                                <tr>
                                    <td class="text-center fw-semibold text-muted"><?= $bSl++ ?></td>
                                    <td class="text-center fw-bold text-primary"><?= htmlspecialchars($bEmp['employee_id']) ?></td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($bEmp['full_name']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($bEmp['department_name']) ?></span></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                            <i class="fa-solid fa-building-columns me-1"></i><?= htmlspecialchars($bEmp['payment_type'] ?? 'Bank') ?>
                                        </span>
                                    </td>
                                    <td><?= !empty($bEmp['bank_name']) ? htmlspecialchars($bEmp['bank_name']) : '<span class="text-muted fst-italic">N/A</span>' ?></td>
                                    <td class="font-monospace fw-semibold"><?= !empty($bEmp['account_number']) ? htmlspecialchars($bEmp['account_number']) : '<span class="text-muted fst-italic">N/A</span>' ?></td>
                                    <td class="text-end pe-3 fw-bold text-primary">৳ <?= number_format($bEmp['disbursement_amount'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-inbox fa-2x mb-2 d-block"></i>
                                    No bank disbursement records found for this project.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-light border-top-2">
                        <tr class="fw-bold fs-13">
                            <td colspan="7" class="text-end pe-3 text-uppercase">Total Bank Transfer Amount:</td>
                            <td class="text-end pe-3 text-primary fs-14">৳ <?= number_format($totalBankRequirement, 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- 2. Cash in Hand Table -->
        <div>
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h5 class="fw-bold text-warning text-darken mb-0">
                    <i class="fa-solid fa-money-bill-wave me-2"></i>2. Cash in Hand Disbursement List
                </h5>
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-1 fs-12">
                    Total: <?= count($cashEmployees) ?> Staff
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-bordered table-striped align-middle mb-0">
                    <thead class="table-warning text-uppercase fs-11">
                        <tr>
                            <th class="text-center" style="width: 50px;">SL</th>
                            <th class="text-center" style="width: 120px;">Employee ID</th>
                            <th>Employee Name</th>
                            <th>Department</th>
                            <th class="text-center" style="width: 110px;">Payment Type</th>
                            <th class="text-end pe-3" style="width: 150px;">Net Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($cashEmployees)): ?>
                            <?php $cSl = 1; foreach ($cashEmployees as $cEmp): ?>
                                <tr>
                                    <td class="text-center fw-semibold text-muted"><?= $cSl++ ?></td>
                                    <td class="text-center fw-bold text-warning text-darken"><?= htmlspecialchars($cEmp['employee_id']) ?></td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($cEmp['full_name']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($cEmp['department_name']) ?></span></td>
                                    <td class="text-center">
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                            <i class="fa-solid fa-coins me-1"></i><?= htmlspecialchars($cEmp['payment_type'] ?? 'Cash') ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-3 fw-bold text-dark">৳ <?= number_format($cEmp['disbursement_amount'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-inbox fa-2x mb-2 d-block"></i>
                                    No cash disbursement records found for this project.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-light border-top-2">
                        <tr class="fw-bold fs-13">
                            <td colspan="5" class="text-end pe-3 text-uppercase">Total Cash in Hand Amount:</td>
                            <td class="text-end pe-3 text-dark fs-14">৳ <?= number_format($totalCashRequirement, 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>
<?php else: ?>
    <!-- Empty State -->
    <div class="card__wrapper bg-white rounded shadow-sm p-5 text-center my-4">
        <div class="mb-3 text-muted">
            <i class="fa-solid fa-file-invoice-dollar fa-4x"></i>
        </div>
        <h4 class="fw-bold text-dark">No Payroll Project Selected</h4>
        <p class="text-muted mb-0">Please select a Payroll Workplace Project from the dropdown above to view its liquidity breakdown.</p>
    </div>
<?php endif; ?>

<style>
@media print {
    .app-header, .app-sidebar, .filters, .btn, .breadcrumb, footer {
        display: none !important;
    }
    .card__wrapper {
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
    }
    body {
        background-color: #fff !important;
    }
}
</style>


<?php
$page = "Salary Report";
$show_breadcrumb = true;
?>

<!-- Filter Section -->
<div class="row mb-3">
    <div class="col-12">
        <div class="filters card__wrapper p-3 bg-white rounded shadow-sm">
            <form method="GET" action="<?= $base_url ?>/payroll-salary-report" id="filterForm"
                class="row gx-3 gy-2 align-items-end">

                <!-- Report Type -->
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label fw-semibold text-muted mb-2">Select Report Type</label>
                    <select name="report_type" class="form-select" onchange="this.form.submit()">
                        <option value="department" <?= $reportType === 'department' ? 'selected' : '' ?>>Department wise
                        </option>
                        <option value="office" <?= $reportType === 'office' ? 'selected' : '' ?>>Office wise</option>
                    </select>
                </div>

                <!-- Payroll Project -->
                <div class="col-lg-6 col-md-8 col-sm-12">
                    <label class="form-label fw-semibold text-muted mb-2">Payroll Project <span
                            class="text-danger">*</span></label>
                    <select name="payroll_project_id" class="form-select" required onchange="this.form.submit()">
                        <option value="">Select Project</option>
                        <?php foreach ($payroll_projects as $proj): ?>
                            <option value="<?= htmlspecialchars($proj['id']) ?>" <?= ($payroll_project_id == $proj['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($proj['name']) ?> (<?= htmlspecialchars($proj['date'] ?? '') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filter Button -->
                <div class="col-lg-3 col-md-12 col-sm-12 text-md-end mt-3 mt-lg-0">
                    <button type="submit" class="btn btn-primary px-4 fw-semibold w-100 w-md-auto shadow-sm">
                        <i class="fa-solid fa-filter me-1"></i> Generate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Main Table Card -->
<div class="row">
    <div class="col-12">
        <div class="card__wrapper bg-white rounded shadow-sm p-4">

            <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                <h4 class="fw-bold text-dark mb-0">
                    <i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i><?= ucfirst($reportType) ?> Wise
                    Salary Report
                </h4>
                <div>
                    <a href="<?= $base_url ?>/payroll-salary-report-csv?report_type=<?= $reportType ?>&payroll_project_id=<?= $payroll_project_id ?>"
                        class="btn btn-success fw-semibold shadow-sm me-2 <?= !$payroll_project_id ? 'disabled' : '' ?>">
                        <i class="fa-solid fa-file-csv me-1"></i> Export CSV
                    </a>
                    <a href="<?= $base_url ?>/payroll-salary-report-pdf?report_type=<?= $reportType ?>&payroll_project_id=<?= $payroll_project_id ?>" target="_blank"
                        class="btn btn-danger fw-semibold shadow-sm <?= !$payroll_project_id ? 'disabled' : '' ?>">
                        <i class="fa-solid fa-file-pdf me-1"></i> Export PDF
                    </a>
                </div>
            </div>

            <?php
            $totalEmpCount = 0;
            $grandTotalSpecial = 0;
            $grandTotalAmount = 0;
            $grandTotalBank = 0;
            $grandTotalCash = 0;
            if (!empty($data)) {
                foreach ($data as $r) {
                    $totalEmpCount += (int)($r['no_of_emp'] ?? 0);
                    $grandTotalSpecial += (float)($r['special_allowance'] ?? 0);
                    $grandTotalAmount += (float)($r['total_amount'] ?? 0);
                    $grandTotalBank += (float)($r['bank_amount'] ?? 0);
                    $grandTotalCash += (float)($r['cash_amount'] ?? 0);
                }
            }
            ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-striped align-middle" id="salaryReportTable">
                    <?php if ($reportType === 'department'): ?>
                        <thead class="bg-light text-muted text-uppercase fs-12">
                            <tr>
                                <th class="text-center" style="width: 80px;">Serial</th>
                                <th>Department Name</th>
                                <th class="text-center">No of employees</th>
                                <th class="text-end">Special Allowance</th>
                                <th class="text-end pe-4">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($data)): ?>
                                <?php $sl = 1;
                                foreach ($data as $row): ?>
                                    <tr>
                                        <td class="text-center fw-bold text-muted"><?= $sl++ ?></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($row['dept_name']) ?></td>
                                        <td class="text-center">
                                            <span
                                                class="badge bg-primary-subtle text-primary rounded-pill px-3 fs-13"><?= $row['no_of_emp'] ?></span>
                                        </td>
                                        <td class="text-end fw-semibold text-dark">
                                            ৳ <?= number_format($row['special_allowance'] ?? 0, 2) ?>
                                        </td>
                                        <td class="text-end fw-bold text-dark pe-4">
                                            ৳ <?= number_format($row['total_amount'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <?php if (!empty($data)): ?>
                        <tfoot class="table-light fw-bold">
                            <tr class="align-middle">
                                <td colspan="2" class="text-end text-dark fs-14">Grand Total:</td>
                                <td class="text-center">
                                    <span class="badge bg-primary text-white rounded-pill px-3 fs-13"><?= $totalEmpCount ?></span>
                                </td>
                                <td class="text-end fw-bold text-dark fs-14">৳ <?= number_format($grandTotalSpecial, 2) ?></td>
                                <td class="text-end fw-bold text-dark pe-4 fs-14">৳ <?= number_format($grandTotalAmount, 2) ?></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    <?php else: ?>
                        <thead class="bg-light text-muted text-uppercase fs-12">
                            <tr>
                                <th>Office name</th>
                                <th class="text-center">No of employees</th>
                                <th class="text-end">Special Allowance</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Bank</th>
                                <th class="text-end pe-4">Cash</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($data)): ?>
                                <?php foreach ($data as $row): ?>
                                    <tr>
                                        <td class="fw-semibold text-dark">
                                            <i
                                                class="fa-solid fa-building text-secondary me-2"></i><?= htmlspecialchars($row['office_name']) ?>
                                        </td>
                                        <td class="text-center">
                                            <span
                                                class="badge bg-primary-subtle text-primary rounded-pill px-3 fs-13"><?= $row['no_of_emp'] ?></span>
                                        </td>
                                        <td class="text-end fw-semibold text-dark">
                                            ৳ <?= number_format($row['special_allowance'] ?? 0, 2) ?>
                                        </td>
                                        <td class="text-end fw-bold text-dark">
                                            ৳ <?= number_format($row['total_amount'], 2) ?>
                                        </td>
                                        <td class="text-end text-success fw-semibold">
                                            ৳ <?= number_format($row['bank_amount'], 2) ?>
                                        </td>
                                        <td class="text-end text-warning fw-semibold pe-4">
                                            ৳ <?= number_format($row['cash_amount'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <?php if (!empty($data)): ?>
                        <tfoot class="table-light fw-bold">
                            <tr class="align-middle">
                                <td class="text-end text-dark fs-14">Grand Total:</td>
                                <td class="text-center">
                                    <span class="badge bg-primary text-white rounded-pill px-3 fs-13"><?= $totalEmpCount ?></span>
                                </td>
                                <td class="text-end fw-bold text-dark fs-14">৳ <?= number_format($grandTotalSpecial, 2) ?></td>
                                <td class="text-end fw-bold text-dark fs-14">৳ <?= number_format($grandTotalAmount, 2) ?></td>
                                <td class="text-end text-success fw-bold fs-14">৳ <?= number_format($grandTotalBank, 2) ?></td>
                                <td class="text-end text-warning fw-bold pe-4 fs-14">৳ <?= number_format($grandTotalCash, 2) ?></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    <?php endif; ?>
                </table>
            </div>

        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Initialize DataTable
        if (typeof $.fn.DataTable !== 'undefined') {
            $('#salaryReportTable').DataTable({
                pageLength: 25,
                lengthMenu: [[25, 50, 100, -1], [25, 50, 100, "All"]],
                ordering: false,
                dom: '<"d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search report..."
                }
            });
        }
    });
</script>
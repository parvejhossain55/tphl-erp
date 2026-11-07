<?php
$page = "Payroll Reports";
$show_breadcrumb = true;

$activeTab = $active_tab ?? 'detailed-summary';
$selectedProjectId = $payroll_project_id ?? null;

if (!function_exists('formatGrowthNode')) {
    function formatGrowthNode($val)
    {
        $str = number_format($val, 2) . '%';
        if ($val > 0)
            return '<span class="badge bg-success-subtle text-success fw-bold px-2 py-1">+' . $str . '</span>';
        if ($val < 0)
            return '<span class="badge bg-danger-subtle text-danger fw-bold px-2 py-1">' . $str . '</span>';
        return '<span class="text-muted fw-semibold">' . $str . '</span>';
    }
}

// Compute quick counts for tab badges
$detailedTotalEmp = 0;
$detailedGrandSpecial = 0;
$detailedGrandPayable = 0;
if (!empty($departmentsData)) {
    foreach ($departmentsData as $deptInfo) {
        $detailedTotalEmp += count($deptInfo['employees']);
        $detailedGrandSpecial += (float)($deptInfo['total_special_allowance'] ?? 0);
        $detailedGrandPayable += (float)$deptInfo['total_payable'];
    }
}
?>

<style>
/* Horizontal 4-Tab Navigation Grid */
.pr-tabs-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 20px;
}
@media (max-width: 1199px) {
    .pr-tabs-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 575px) {
    .pr-tabs-grid {
        grid-template-columns: 1fr;
    }
}

.pr-tab-card {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
    text-align: left;
    width: 100%;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
}
.pr-tab-card:hover {
    border-color: #cbd5e1;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
}
.pr-tab-card.active {
    background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%);
    border-color: #078134;
    box-shadow: 0 4px 14px rgba(7, 129, 52, 0.14);
}
.pr-tab-card.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3.5px;
    background: #078134;
}

.pr-tab-icon {
    width: 44px;
    height: 44px;
    min-width: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    background: #f1f5f9;
    color: #475569;
    transition: all 0.2s ease;
}
.pr-tab-card.active .pr-tab-icon {
    background: #078134;
    color: #ffffff;
    box-shadow: 0 4px 10px rgba(7, 129, 52, 0.25);
}

.pr-tab-content {
    flex: 1;
    min-width: 0;
}
.pr-tab-title {
    font-size: 14.5px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 2px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.pr-tab-card.active .pr-tab-title {
    color: #078134;
}
.pr-tab-sub {
    font-size: 12px;
    color: #64748b;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Report Pane Visibility */
.pr-report-pane {
    display: none;
    animation: prFadeIn 0.2s ease-in-out;
}
.pr-report-pane.active-pane {
    display: block;
}
@keyframes prFadeIn {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Unified Card & Toolbar Styling */
.pr-main-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    overflow: hidden;
}
.pr-toolbar {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 16px 22px;
}
.pr-toolbar-flex {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}
.pr-filter-form {
    display: flex;
    align-items: flex-end;
    gap: 12px;
    flex-wrap: wrap;
    flex: 1;
    margin: 0;
}
.pr-filter-group {
    min-width: 220px;
    flex: 0 1 280px;
}
.pr-export-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}
.pr-body {
    padding: 20px 22px;
}
.pr-section-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
    padding-bottom: 14px;
    border-bottom: 1px solid #f1f5f9;
}
.pr-section-title {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Clean Table Enhancements */
.pr-table thead th {
    background-color: #f1f5f9 !important;
    color: #334155 !important;
    font-weight: 700 !important;
    font-size: 12px !important;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    padding: 11px 12px !important;
    border-color: #e2e8f0 !important;
}
.pr-table tbody td {
    padding: 10px 12px !important;
    vertical-align: middle !important;
    border-color: #e2e8f0 !important;
    font-size: 13.5px;
}
.pr-table tfoot td {
    background-color: #f8fafc !important;
    padding: 12px !important;
    border-top: 2px solid #cbd5e1 !important;
}

/* 3-Column Horizontal KPI Grid */
.pr-kpi-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
@media (max-width: 991px) {
    .pr-kpi-grid {
        grid-template-columns: 1fr;
    }
}
.pr-kpi-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
}
.pr-kpi-icon {
    width: 48px;
    height: 48px;
    min-width: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}
.pr-kpi-icon.kpi-green {
    background-color: #dcfce7 !important;
    color: #15803d !important;
}
.pr-kpi-icon.kpi-blue {
    background-color: #dbeafe !important;
    color: #1d4ed8 !important;
}
.pr-kpi-icon.kpi-amber {
    background-color: #fef3c7 !important;
    color: #b45309 !important;
}

@media print {
    .app-header, .app-sidebar, .pr-tabs-grid, .pr-toolbar, .btn, .breadcrumb, footer, .no-print {
        display: none !important;
    }
    .pr-main-card {
        box-shadow: none !important;
        border: none !important;
    }
    body {
        background-color: #fff !important;
    }
}
</style>

<!-- ========================================================================= -->
<!-- HORIZONTAL 4-TAB NAVIGATION BAR                                           -->
<!-- ========================================================================= -->
<div class="pr-tabs-grid no-print" role="tablist">
    <button type="button" class="pr-tab-card <?= $activeTab === 'detailed-summary' ? 'active' : '' ?>"
        id="tab-btn-detailed-summary" onclick="switchReportTab('detailed-summary')">
        <div class="pr-tab-icon">
            <i class="fa-solid fa-list-check"></i>
        </div>
        <div class="pr-tab-content">
            <div class="pr-tab-title">Detailed Summary</div>
            <div class="pr-tab-sub">Department & Employee Breakdown</div>
        </div>
    </button>

    <button type="button" class="pr-tab-card <?= $activeTab === 'summary-report' ? 'active' : '' ?>"
        id="tab-btn-summary-report" onclick="switchReportTab('summary-report')">
        <div class="pr-tab-icon">
            <i class="fa-solid fa-chart-line"></i>
        </div>
        <div class="pr-tab-content">
            <div class="pr-tab-title">Payroll Summary Report</div>
            <div class="pr-tab-sub">Project Comparison & Growth (%)</div>
        </div>
    </button>

    <button type="button" class="pr-tab-card <?= $activeTab === 'salary-report' ? 'active' : '' ?>"
        id="tab-btn-salary-report" onclick="switchReportTab('salary-report')">
        <div class="pr-tab-icon">
            <i class="fa-solid fa-building-user"></i>
        </div>
        <div class="pr-tab-content">
            <div class="pr-tab-title">Salary Report</div>
            <div class="pr-tab-sub">Department & Office Wise Totals</div>
        </div>
    </button>

    <button type="button" class="pr-tab-card <?= $activeTab === 'liquidity-management' ? 'active' : '' ?>"
        id="tab-btn-liquidity-management" onclick="switchReportTab('liquidity-management')">
        <div class="pr-tab-icon">
            <i class="fa-solid fa-money-bill-transfer"></i>
        </div>
        <div class="pr-tab-content">
            <div class="pr-tab-title">Liquidity Management</div>
            <div class="pr-tab-sub">Bank & Cash Disbursement Lists</div>
        </div>
    </button>
</div>

<!-- ========================================================================= -->
<!-- PANE 1: DETAILED SUMMARY                                                  -->
<!-- ========================================================================= -->
<div class="pr-report-pane <?= $activeTab === 'detailed-summary' ? 'active-pane' : '' ?>" id="pane-detailed-summary">
    <div class="pr-main-card">
        <!-- Integrated Filter & Export Toolbar -->
        <div class="pr-toolbar no-print">
            <div class="pr-toolbar-flex">
                <form method="GET" action="<?= $base_url ?>/payroll-reports" id="detailedFilterForm" class="pr-filter-form">
                    <input type="hidden" name="tab" value="detailed-summary">
                    <div class="pr-filter-group">
                        <label for="detailedPayrollProject" class="form-label fw-semibold text-secondary fs-13 mb-1">
                            <i class="fa-solid fa-folder-open text-success me-1"></i> Select Payroll Project <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" name="payroll_project_id" id="detailedPayrollProject" required onchange="this.form.submit()">
                            <option value="">-- Choose a Project --</option>
                            <?php if (!empty($payroll_projects)): ?>
                                <?php foreach ($payroll_projects as $proj): ?>
                                    <option value="<?= htmlspecialchars($proj['id']) ?>" <?= ($selectedProjectId == $proj['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($proj['name']) ?><?= !empty($proj['date']) ? ' (' . htmlspecialchars($proj['date']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary fw-semibold px-3">
                            <i class="fa-solid fa-sync-alt me-1"></i> Load Report
                        </button>
                    </div>
                </form>

                <div class="pr-export-actions">
                    <button type="button" class="btn btn-outline-success fw-semibold"
                        onclick="$('#detailedSummaryTable').DataTable().button('.buttons-csv').trigger();">
                        <i class="fa-solid fa-file-csv me-1"></i> Export CSV
                    </button>
                    <button type="button" class="btn btn-danger fw-semibold shadow-sm"
                        onclick="$('#detailedSummaryTable').DataTable().button('.buttons-pdf').trigger();">
                        <i class="fa-solid fa-file-pdf me-1"></i> Export PDF
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Content -->
        <div class="pr-body">
            <div class="pr-section-header">
                <h5 class="pr-section-title">
                    <i class="fa-solid fa-file-invoice-dollar text-success"></i>
                    <span>Department-Wise Employee Detailed Summary</span>
                    <?php if (!empty($selectedProject['name'])): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle fs-12 ms-1">
                            <?= htmlspecialchars($selectedProject['name']) ?>
                        </span>
                    <?php endif; ?>
                </h5>
                <?php if ($detailedTotalEmp > 0): ?>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-light text-dark border px-3 py-2 fs-12">
                            Employees: <strong><?= $detailedTotalEmp ?></strong>
                        </span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fs-12">
                            Special Allowance: <strong>৳ <?= number_format($detailedGrandSpecial, 2) ?></strong>
                        </span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-12">
                            Total Payable: <strong>৳ <?= number_format($detailedGrandPayable, 2) ?></strong>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="table-responsive">
                <table id="detailedSummaryTable" class="table table-bordered pr-table mb-0 w-100">
                    <thead class="text-center text-nowrap align-middle">
                        <tr>
                            <th style="width: 15%">Department</th>
                            <th style="width: 5%">SL</th>
                            <th class="text-start" style="width: 22%">Employee Name</th>
                            <th style="width: 9%">ID</th>
                            <th class="text-start" style="width: 14%">Designation</th>
                            <th class="text-end" style="width: 11%">Special Allowance</th>
                            <th class="text-end" style="width: 12%">Net Payable</th>
                            <th class="text-end" style="width: 12%">Dept. Total Payable</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($departmentsData)): ?>
                            <?php foreach ($departmentsData as $deptName => $deptInfo): ?>
                                <?php
                                $empCount = count($deptInfo['employees']);
                                $totalPay = number_format($deptInfo['total_payable'], 2);
                                $sl = 1;
                                ?>
                                <?php foreach ($deptInfo['employees'] as $index => $emp): ?>
                                    <?php
                                    $isFirst = ($index === 0);
                                    $isLast = ($index === ($empCount - 1));
                                    $bStyle = '';
                                    if ($empCount > 1) {
                                        if ($isFirst) {
                                            $bStyle = 'border-bottom: 1px solid transparent !important;';
                                        } elseif ($isLast) {
                                            $bStyle = 'border-top: 1px solid transparent !important;';
                                        } else {
                                            $bStyle = 'border-top: 1px solid transparent !important; border-bottom: 1px solid transparent !important;';
                                        }
                                    }
                                    ?>
                                    <tr class="text-center align-middle">
                                        <td class="<?= $isFirst ? 'fw-bold text-dark' : 'text-transparent' ?> bg-white align-top" style="<?= $bStyle ?>">
                                            <?= $isFirst ? htmlspecialchars($deptName) : '' ?>
                                        </td>
                                        <td class="text-muted"><?= $sl++ ?></td>
                                        <td class="text-start fw-semibold text-dark"><?= htmlspecialchars($emp['full_name'] ?? '') ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($emp['emp_id'] ?? '') ?></span></td>
                                        <td class="text-start text-secondary"><?= htmlspecialchars($emp['designation_name'] ?? '-') ?></td>
                                        <td class="text-end text-nowrap"><?= number_format($emp['special_allowance'] ?? 0, 2) ?></td>
                                        <td class="text-end fw-semibold text-nowrap"><?= number_format($emp['net_salary'] ?? 0, 2) ?></td>
                                        <td class="<?= $isFirst ? 'fw-bold text-success' : 'text-transparent' ?> text-end bg-white align-top text-nowrap" style="<?= $bStyle ?>">
                                            <?= $isFirst ? '৳ ' . $totalPay : '' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <?php if ($detailedTotalEmp > 0): ?>
                    <tfoot class="fw-bold">
                        <tr class="align-middle">
                            <td colspan="5" class="text-end text-dark fs-14">Grand Total (<?= $detailedTotalEmp ?> Employees):</td>
                            <td class="text-end text-dark fs-14">৳ <?= number_format($detailedGrandSpecial, 2) ?></td>
                            <td class="text-end text-success fs-14">৳ <?= number_format($detailedGrandPayable, 2) ?></td>
                            <td class="text-end text-success fs-14">৳ <?= number_format($detailedGrandPayable, 2) ?></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- PANE 2: PAYROLL SUMMARY REPORT                                            -->
<!-- ========================================================================= -->
<div class="pr-report-pane <?= $activeTab === 'summary-report' ? 'active-pane' : '' ?>" id="pane-summary-report">
    <div class="pr-main-card">
        <!-- Integrated Filter & Export Toolbar -->
        <div class="pr-toolbar no-print">
            <div class="pr-toolbar-flex">
                <form method="GET" action="<?= $base_url ?>/payroll-reports" id="summaryFilterForm" class="pr-filter-form">
                    <input type="hidden" name="tab" value="summary-report">
                    <div class="pr-filter-group">
                        <label for="fromProject" class="form-label fw-semibold text-secondary fs-13 mb-1">
                            <i class="fa-solid fa-calendar-minus text-success me-1"></i> From Project <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" name="from_project_id" id="fromProject" required>
                            <option value="">Select From Project</option>
                            <?php foreach ($payroll_projects_asc as $proj): ?>
                                <option value="<?= htmlspecialchars($proj['id']) ?>" <?= ($from_project_id == $proj['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($proj['name']) ?> (<?= htmlspecialchars($proj['date'] ?? '') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="pr-filter-group">
                        <label for="toProject" class="form-label fw-semibold text-secondary fs-13 mb-1">
                            <i class="fa-solid fa-calendar-plus text-success me-1"></i> To Project <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" name="to_project_id" id="toProject" required>
                            <option value="">Select To Project</option>
                            <?php foreach ($payroll_projects_asc as $proj): ?>
                                <option value="<?= htmlspecialchars($proj['id']) ?>" <?= ($to_project_id == $proj['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($proj['name']) ?> (<?= htmlspecialchars($proj['date'] ?? '') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary fw-semibold px-3">
                            <i class="fa-solid fa-magnifying-glass me-1"></i> Compare
                        </button>
                    </div>
                </form>

                <div class="pr-export-actions">
                    <button type="button" class="btn btn-outline-success fw-semibold"
                        onclick="$('#summaryReportTable').DataTable().button('.buttons-csv').trigger();">
                        <i class="fa-solid fa-file-csv me-1"></i> Export CSV
                    </button>
                    <button type="button" class="btn btn-danger fw-semibold shadow-sm" onclick="exportSummaryPdf()">
                        <i class="fa-solid fa-file-pdf me-1"></i> Export PDF
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Content -->
        <div class="pr-body">
            <div class="pr-section-header">
                <h5 class="pr-section-title">
                    <i class="fa-solid fa-chart-column text-success"></i>
                    <span>Payroll Summary & Growth Comparison</span>
                </h5>
                <span class="text-muted fs-13">
                    Comparing <strong><?= count($targetProjects) ?></strong> Project(s)
                </span>
            </div>

            <div class="table-responsive">
                <table id="summaryReportTable" class="table table-bordered table-hover pr-table mb-0 w-100">
                    <thead class="text-center text-nowrap align-middle">
                        <tr>
                            <th class="text-start" style="min-width: 220px;">Particulars</th>
                            <?php foreach ($targetProjects as $proj): ?>
                                <th class="text-end" style="min-width: 140px;">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($proj['name']) ?></div>
                                    <small class="text-muted fw-normal"><?= htmlspecialchars($proj['date'] ?? '') ?></small>
                                </th>
                            <?php endforeach; ?>
                            <th class="text-end" style="min-width: 140px;">Growth (%)<br><small class="fw-normal text-muted">Before Last</small></th>
                            <th class="text-end" style="min-width: 140px;">Growth (%)<br><small class="fw-normal text-muted">Last</small></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($summaryData as $key => $monthlyValues): ?>
                            <tr class="align-middle text-nowrap">
                                <td class="fw-bold text-dark text-start bg-light">
                                    <?= htmlspecialchars($key) ?>
                                </td>
                                <?php foreach ($targetProjects as $proj): ?>
                                    <td class="text-end fw-semibold">
                                        <?php
                                        $pid = $proj['id'];
                                        $val = $monthlyValues[$pid] ?? 0;
                                        echo ($key === 'No of employees') ? (int) $val : '৳ ' . number_format($val, 2);
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                                <?php
                                $count = count($targetProjects);
                                $valLast = ($count >= 1) ? ($monthlyValues[$targetProjects[$count - 1]['id']] ?? 0) : 0;
                                $valSecondLast = ($count >= 2) ? ($monthlyValues[$targetProjects[$count - 2]['id']] ?? 0) : 0;
                                $valThirdLast = ($count >= 3) ? ($monthlyValues[$targetProjects[$count - 3]['id']] ?? 0) : 0;

                                $growthLast = 0;
                                if ($valSecondLast != 0) {
                                    $growthLast = ($valLast - $valSecondLast) / $valSecondLast * 100;
                                }

                                $growthSecondLast = 0;
                                if ($valThirdLast != 0) {
                                    $growthSecondLast = ($valSecondLast - $valThirdLast) / $valThirdLast * 100;
                                }
                                ?>
                                <td class="text-end">
                                    <?= formatGrowthNode($growthSecondLast) ?>
                                </td>
                                <td class="text-end">
                                    <?= formatGrowthNode($growthLast) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- PANE 3: SALARY REPORT                                                     -->
<!-- ========================================================================= -->
<div class="pr-report-pane <?= $activeTab === 'salary-report' ? 'active-pane' : '' ?>" id="pane-salary-report">
    <div class="pr-main-card">
        <!-- Integrated Filter & Export Toolbar -->
        <div class="pr-toolbar no-print">
            <div class="pr-toolbar-flex">
                <form method="GET" action="<?= $base_url ?>/payroll-reports" id="salaryFilterForm" class="pr-filter-form">
                    <input type="hidden" name="tab" value="salary-report">
                    <div class="pr-filter-group" style="flex: 0 1 200px; min-width: 180px;">
                        <label class="form-label fw-semibold text-secondary fs-13 mb-1">
                            <i class="fa-solid fa-layer-group text-success me-1"></i> Report Type
                        </label>
                        <select name="report_type" class="form-select" onchange="this.form.submit()">
                            <option value="department" <?= $reportType === 'department' ? 'selected' : '' ?>>Department Wise</option>
                            <option value="office" <?= $reportType === 'office' ? 'selected' : '' ?>>Office Wise</option>
                        </select>
                    </div>

                    <div class="pr-filter-group">
                        <label class="form-label fw-semibold text-secondary fs-13 mb-1">
                            <i class="fa-solid fa-folder-open text-success me-1"></i> Payroll Project <span class="text-danger">*</span>
                        </label>
                        <select name="payroll_project_id" class="form-select" required onchange="this.form.submit()">
                            <option value="">Select Project</option>
                            <?php foreach ($payroll_projects as $proj): ?>
                                <option value="<?= htmlspecialchars($proj['id']) ?>" <?= ($selectedProjectId == $proj['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($proj['name']) ?> (<?= htmlspecialchars($proj['date'] ?? '') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary fw-semibold px-3">
                            <i class="fa-solid fa-filter me-1"></i> Generate
                        </button>
                    </div>
                </form>

                <div class="pr-export-actions">
                    <a href="<?= $base_url ?>/payroll-salary-report-csv?report_type=<?= $reportType ?>&payroll_project_id=<?= $selectedProjectId ?>"
                        class="btn btn-outline-success fw-semibold <?= !$selectedProjectId ? 'disabled' : '' ?>">
                        <i class="fa-solid fa-file-csv me-1"></i> Export CSV
                    </a>
                    <a href="<?= $base_url ?>/payroll-salary-report-pdf?report_type=<?= $reportType ?>&payroll_project_id=<?= $selectedProjectId ?>" target="_blank"
                        class="btn btn-danger fw-semibold shadow-sm <?= !$selectedProjectId ? 'disabled' : '' ?>">
                        <i class="fa-solid fa-file-pdf me-1"></i> Export PDF
                    </a>
                </div>
            </div>
        </div>

        <!-- Table Content -->
        <div class="pr-body">
            <?php
            $salTotalEmpCount = 0;
            $salGrandTotalSpecial = 0;
            $salGrandTotalAmount = 0;
            $salGrandTotalBank = 0;
            $salGrandTotalCash = 0;
            if (!empty($salaryData)) {
                foreach ($salaryData as $r) {
                    $salTotalEmpCount += (int)($r['no_of_emp'] ?? 0);
                    $salGrandTotalSpecial += (float)($r['special_allowance'] ?? 0);
                    $salGrandTotalAmount += (float)($r['total_amount'] ?? 0);
                    $salGrandTotalBank += (float)($r['bank_amount'] ?? 0);
                    $salGrandTotalCash += (float)($r['cash_amount'] ?? 0);
                }
            }
            ?>
            <div class="pr-section-header">
                <h5 class="pr-section-title">
                    <i class="fa-solid fa-building-user text-success"></i>
                    <span><?= ucfirst($reportType) ?> Wise Salary Report</span>
                    <?php if (!empty($selectedProject['name'])): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle fs-12 ms-1">
                            <?= htmlspecialchars($selectedProject['name']) ?>
                        </span>
                    <?php endif; ?>
                </h5>
                <?php if ($salTotalEmpCount > 0): ?>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-light text-dark border px-3 py-2 fs-12">
                            Employees: <strong><?= $salTotalEmpCount ?></strong>
                        </span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-12">
                            Net Total: <strong>৳ <?= number_format($salGrandTotalAmount, 2) ?></strong>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-bordered pr-table align-middle mb-0 w-100" id="salaryReportTable">
                    <?php if ($reportType === 'department'): ?>
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 80px;">Serial</th>
                                <th>Department Name</th>
                                <th class="text-center" style="width: 160px;">No of Employees</th>
                                <th class="text-end" style="width: 200px;">Special Allowance</th>
                                <th class="text-end pe-4" style="width: 220px;">Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($salaryData)): ?>
                                <?php $sl = 1; foreach ($salaryData as $row): ?>
                                    <tr>
                                        <td class="text-center fw-bold text-muted"><?= $sl++ ?></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($row['dept_name']) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 fs-13"><?= $row['no_of_emp'] ?></span>
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
                        <?php if (!empty($salaryData)): ?>
                        <tfoot class="fw-bold">
                            <tr class="align-middle">
                                <td colspan="2" class="text-end text-dark fs-14">Grand Total:</td>
                                <td class="text-center">
                                    <span class="badge bg-primary text-white rounded-pill px-3 fs-13"><?= $salTotalEmpCount ?></span>
                                </td>
                                <td class="text-end fw-bold text-dark fs-14">৳ <?= number_format($salGrandTotalSpecial, 2) ?></td>
                                <td class="text-end fw-bold text-success pe-4 fs-14">৳ <?= number_format($salGrandTotalAmount, 2) ?></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    <?php else: ?>
                        <thead>
                            <tr>
                                <th>Office Name</th>
                                <th class="text-center">No of Employees</th>
                                <th class="text-end">Special Allowance</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Bank</th>
                                <th class="text-end pe-4">Cash</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($salaryData)): ?>
                                <?php foreach ($salaryData as $row): ?>
                                    <tr>
                                        <td class="fw-semibold text-dark">
                                            <i class="fa-solid fa-building text-secondary me-2"></i><?= htmlspecialchars($row['office_name']) ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 fs-13"><?= $row['no_of_emp'] ?></span>
                                        </td>
                                        <td class="text-end fw-semibold text-dark">
                                            ৳ <?= number_format($row['special_allowance'] ?? 0, 2) ?>
                                        </td>
                                        <td class="text-end fw-bold text-dark">
                                            ৳ <?= number_format($row['total_amount'], 2) ?>
                                        </td>
                                        <td class="text-end text-primary fw-semibold">
                                            ৳ <?= number_format($row['bank_amount'], 2) ?>
                                        </td>
                                        <td class="text-end text-warning text-darken fw-semibold pe-4">
                                            ৳ <?= number_format($row['cash_amount'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <?php if (!empty($salaryData)): ?>
                        <tfoot class="fw-bold">
                            <tr class="align-middle">
                                <td class="text-end text-dark fs-14">Grand Total:</td>
                                <td class="text-center">
                                    <span class="badge bg-primary text-white rounded-pill px-3 fs-13"><?= $salTotalEmpCount ?></span>
                                </td>
                                <td class="text-end fw-bold text-dark fs-14">৳ <?= number_format($salGrandTotalSpecial, 2) ?></td>
                                <td class="text-end fw-bold text-success fs-14">৳ <?= number_format($salGrandTotalAmount, 2) ?></td>
                                <td class="text-end text-primary fw-bold fs-14">৳ <?= number_format($salGrandTotalBank, 2) ?></td>
                                <td class="text-end text-warning text-darken fw-bold pe-4 fs-14">৳ <?= number_format($salGrandTotalCash, 2) ?></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- PANE 4: LIQUIDITY MANAGEMENT                                              -->
<!-- ========================================================================= -->
<div class="pr-report-pane <?= $activeTab === 'liquidity-management' ? 'active-pane' : '' ?>" id="pane-liquidity-management">
    <div class="pr-main-card">
        <!-- Integrated Filter & Export Toolbar -->
        <div class="pr-toolbar no-print">
            <div class="pr-toolbar-flex">
                <form method="GET" action="<?= $base_url ?>/payroll-reports" id="liquidityFilterForm" class="pr-filter-form">
                    <input type="hidden" name="tab" value="liquidity-management">
                    <div class="pr-filter-group">
                        <label class="form-label fw-semibold text-secondary fs-13 mb-1">
                            <i class="fa-solid fa-folder-tree text-success me-1"></i> Select Payroll Workplace Project <span class="text-danger">*</span>
                        </label>
                        <select name="payroll_project_id" class="form-select" required onchange="this.form.submit()">
                            <option value="">-- Select Payroll Project --</option>
                            <?php foreach ($payroll_projects as $proj): ?>
                                <option value="<?= htmlspecialchars($proj['id']) ?>" <?= ($selectedProjectId == $proj['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($proj['name']) ?>
                                    <?= !empty($proj['date']) ? ' (' . htmlspecialchars($proj['date']) . ')' : (!empty($proj['start_date']) ? ' (' . htmlspecialchars($proj['start_date']) . ')' : '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary fw-semibold px-3">
                            <i class="fa-solid fa-sync-alt me-1"></i> Load Report
                        </button>
                    </div>
                </form>

                <?php if ($selectedProjectId && !empty($selectedProject)): ?>
                <div class="pr-export-actions">
                    <a href="<?= $base_url ?>/payroll-liquidity-report-csv?payroll_project_id=<?= $selectedProjectId ?>"
                        class="btn btn-outline-success fw-semibold">
                        <i class="fa-solid fa-file-csv me-1"></i> Export CSV
                    </a>
                    <a href="<?= $base_url ?>/payroll-liquidity-report-pdf?payroll_project_id=<?= $selectedProjectId ?>"
                        target="_blank" class="btn btn-danger fw-semibold shadow-sm">
                        <i class="fa-solid fa-file-pdf me-1"></i> Export PDF
                    </a>
                    <button type="button" onclick="window.print()" class="btn btn-outline-secondary fw-semibold">
                        <i class="fa-solid fa-print me-1"></i> Print
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="pr-body">
            <?php if ($selectedProjectId && !empty($selectedProject)): ?>
                <!-- 3 Top KPI Cards (Single Horizontal Row) -->
                <div class="pr-kpi-grid">
                    <div class="pr-kpi-card" style="border-left: 4px solid #16a34a;">
                        <div>
                            <span class="fs-12 text-uppercase text-muted fw-bold d-block">Total Payroll Requirement</span>
                            <h3 class="fw-bold mb-1 text-success mt-1">৳ <?= number_format($totalRequirement, 2) ?></h3>
                            <small class="text-muted fs-12">Total <?= count($bankEmployees) + count($cashEmployees) ?> Employees</small>
                        </div>
                        <div class="pr-kpi-icon kpi-green">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                    </div>

                    <div class="pr-kpi-card" style="border-left: 4px solid #2563eb;">
                        <div>
                            <span class="fs-12 text-uppercase text-muted fw-bold d-block">Bank Transfer Requirement</span>
                            <h3 class="fw-bold mb-1 text-primary mt-1">৳ <?= number_format($totalBankRequirement, 2) ?></h3>
                            <small class="text-muted fs-12"><?= count($bankEmployees) ?> Employees via Bank</small>
                        </div>
                        <div class="pr-kpi-icon kpi-blue">
                            <i class="fa-solid fa-building-columns"></i>
                        </div>
                    </div>

                    <div class="pr-kpi-card" style="border-left: 4px solid #d97706;">
                        <div>
                            <span class="fs-12 text-uppercase text-muted fw-bold d-block">Cash in Hand Requirement</span>
                            <h3 class="fw-bold mb-1 text-warning text-darken mt-1">৳ <?= number_format($totalCashRequirement, 2) ?></h3>
                            <small class="text-muted fs-12"><?= count($cashEmployees) ?> Employees via Cash</small>
                        </div>
                        <div class="pr-kpi-icon kpi-amber">
                            <i class="fa-solid fa-money-bill-wave"></i>
                        </div>
                    </div>
                </div>

                <!-- 1. Bank Transfer Table -->
                <div class="mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="fw-bold text-primary mb-0">
                            <i class="fa-solid fa-building-columns me-2"></i>1. Bank Transfer Disbursement List
                        </h6>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 fs-12">
                            Total: <?= count($bankEmployees) ?> Staff
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-bordered pr-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 55px;">SL</th>
                                    <th class="text-center" style="width: 120px;">Employee ID</th>
                                    <th>Employee Name</th>
                                    <th>Department</th>
                                    <th class="text-center" style="width: 120px;">Payment Type</th>
                                    <th>Bank Name</th>
                                    <th>Account Number</th>
                                    <th class="text-end pe-3" style="width: 160px;">Net Amount</th>
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
                                            No bank disbursement records found for this project.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
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
                        <h6 class="fw-bold text-warning text-darken mb-0">
                            <i class="fa-solid fa-money-bill-wave me-2"></i>2. Cash in Hand Disbursement List
                        </h6>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-1 fs-12">
                            Total: <?= count($cashEmployees) ?> Staff
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-bordered pr-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 55px;">SL</th>
                                    <th class="text-center" style="width: 120px;">Employee ID</th>
                                    <th>Employee Name</th>
                                    <th>Department</th>
                                    <th class="text-center" style="width: 120px;">Payment Type</th>
                                    <th class="text-end pe-3" style="width: 160px;">Net Amount</th>
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
                                            No cash disbursement records found for this project.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold fs-13">
                                    <td colspan="5" class="text-end pe-3 text-uppercase">Total Cash in Hand Amount:</td>
                                    <td class="text-end pe-3 text-dark fs-14">৳ <?= number_format($totalCashRequirement, 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="p-5 text-center my-3">
                    <div class="mb-3 text-muted">
                        <i class="fa-solid fa-file-invoice-dollar fa-3x"></i>
                    </div>
                    <h5 class="fw-bold text-dark">No Payroll Project Selected</h5>
                    <p class="text-muted mb-0">Please select a Payroll Workplace Project from the toolbar above to view its liquidity breakdown.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Hidden Forms for PDF Export -->
<form id="exportDetailedPdfForm" action="<?= $GLOBALS['base_url'] ?>/payroll-detailed-summary-pdf" method="GET" target="_blank" style="display:none;">
    <input type="hidden" name="payroll_project_id" id="pdfDetailedPayrollProject">
</form>

<form id="exportSummaryPdfForm" action="<?= $GLOBALS['base_url'] ?>/payroll-summary-report-pdf" method="GET" target="_blank" style="display:none;">
    <input type="hidden" name="from_project_id" id="pdfFromProjectId">
    <input type="hidden" name="to_project_id" id="pdfToProjectId">
</form>

<?php ob_start(); ?>
window.switchReportTab = function(tabName) {
    // Toggle active class on horizontal tab buttons
    var allTabs = document.querySelectorAll('.pr-tab-card');
    allTabs.forEach(function(btn) {
        btn.classList.remove('active');
    });
    var activeBtn = document.getElementById('tab-btn-' + tabName);
    if (activeBtn) {
        activeBtn.classList.add('active');
    }

    // Toggle active pane
    var allPanes = document.querySelectorAll('.pr-report-pane');
    allPanes.forEach(function(pane) {
        pane.classList.remove('active-pane');
    });
    var targetPane = document.getElementById('pane-' + tabName);
    if (targetPane) {
        targetPane.classList.add('active-pane');
    }

    // Persist tab in URL
    if (window.history && window.history.replaceState) {
        var url = new URL(window.location.href);
        url.searchParams.set('tab', tabName);
        window.history.replaceState({}, '', url.toString());
    }

    // Recalculate DataTable column widths
    setTimeout(function() {
        if (typeof $.fn.DataTable !== 'undefined') {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        }
    }, 60);
};

window.exportSummaryPdf = function () {
    var fpid = $('#fromProject').val();
    var tpid = $('#toProject').val();
    if (!fpid || !tpid) {
        if (typeof Swal !== 'undefined') {
            Swal.fire('Error', 'Please select both From and To projects first.', 'error');
        } else {
            alert('Please select both From and To projects first.');
        }
        return;
    }
    document.getElementById('pdfFromProjectId').value = fpid;
    document.getElementById('pdfToProjectId').value = tpid;
    document.getElementById('exportSummaryPdfForm').submit();
};

document.addEventListener("DOMContentLoaded", function () {
    if (typeof $.fn.DataTable !== 'undefined') {
        // 1. Detailed Summary DataTable
        if ($.fn.DataTable.isDataTable('#detailedSummaryTable')) {
            $('#detailedSummaryTable').DataTable().destroy();
        }
        $('#detailedSummaryTable').DataTable({
            dom: '<"row align-items-center mb-3"<"col-sm-6 text-start"l><"col-sm-6 text-end"f><"d-none"B>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row align-items-center mt-3"<"col-sm-6 text-start"i><"col-sm-6 text-end"p>>',
            buttons: [
                {
                    extend: 'csvHtml5',
                    text: 'CSV',
                    className: 'buttons-csv',
                    title: 'Detailed_Summary_Report'
                },
                {
                    text: 'PDF',
                    className: 'buttons-pdf',
                    action: function () {
                        var projId = $('#detailedPayrollProject').val();
                        if (!projId) {
                            alert("Please select a project first.");
                            return;
                        }
                        document.getElementById('pdfDetailedPayrollProject').value = projId;
                        document.getElementById('exportDetailedPdfForm').submit();
                    }
                }
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search employee or department..."
            },
            pagingType: "simple_numbers",
            pageLength: 50,
            ordering: false
        });

        // 2. Summary Report DataTable
        if ($.fn.DataTable.isDataTable('#summaryReportTable')) {
            $('#summaryReportTable').DataTable().destroy();
        }
        $('#summaryReportTable').DataTable({
            dom: '<"row align-items-center mb-3"<"col-sm-6 text-start"l><"col-sm-6 text-end"f><"d-none"B>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row align-items-center mt-3"<"col-sm-6 text-start"i><"col-sm-6 text-end"p>>',
            buttons: [
                {
                    extend: 'csvHtml5',
                    text: 'CSV',
                    className: 'buttons-csv',
                    title: 'Payroll_Summary_Report_<?= time() ?>'
                },
                {
                    text: 'PDF',
                    className: 'buttons-pdf',
                    action: function () {
                        exportSummaryPdf();
                    }
                }
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search particulars..."
            },
            paging: false,
            ordering: false,
            info: false
        });

        // 3. Salary Report DataTable
        if ($.fn.DataTable.isDataTable('#salaryReportTable')) {
            $('#salaryReportTable').DataTable().destroy();
        }
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
<?php
$page_script = ob_get_clean();
?>

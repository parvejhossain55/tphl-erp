<?php
$page = "Payroll Workplace";
$show_breadcrumb = true;

$_filterType    = $filterType      ?? 'monthly';
$_fromDate      = $selectedFromDate ?? date('Y-m-01');
$_toDate        = $selectedToDate  ?? date('Y-m-t');
$_selectedYear  = $selectedYear    ?? date('Y');
$_selectedMonth = $selectedMonth   ?? date('m');
?>

<!-- Filter Section -->
<div class="row mb-3">
    <div class="col-12">
        <div class="filters card__wrapper p-3 bg-white rounded shadow-sm">
            <form method="GET" action="<?= $GLOBALS['base_url'] ?>/payroll-workspace" class="row gx-3 gy-2 align-items-end" id="workspaceFilterForm">

                <!-- Hidden: carries filter_type on submit -->
                <input type="hidden" name="filter_type" id="filterTypeInput" value="<?= htmlspecialchars($_filterType) ?>">

                <!-- Filter Type Toggle -->
                <div class="col-sm-2">
                    <label class="form-label fw-semibold text-muted mb-1">Filter Type</label>
                    <select class="form-select" id="filterTypeSelect">
                        <option value="monthly" <?= $_filterType === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                        <option value="custom"  <?= $_filterType === 'custom'  ? 'selected' : '' ?>>Custom Range</option>
                    </select>
                </div>

                <!-- Monthly: Year + Month -->
                <div class="col-sm-4" id="monthlyContainer" <?= $_filterType === 'custom' ? 'style="display:none;"' : '' ?>>
                    <div class="row gx-2">
                        <div class="col-6">
                            <label class="form-label fw-semibold text-muted mb-1">Year</label>
                            <select class="form-select" name="year" id="year">
                                <?php
                                $startYear = 2020;
                                $endYear   = 2035;
                                for ($y = $startYear; $y <= $endYear; $y++): ?>
                                    <option value="<?= $y ?>" <?= $y == $_selectedYear ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold text-muted mb-1">Month</label>
                            <select class="form-select" name="month" id="month">
                                <?php
                                for ($m = 1; $m <= 12; $m++):
                                    $monthNum  = str_pad($m, 2, "0", STR_PAD_LEFT);
                                    $monthName = date('F', mktime(0, 0, 0, $m, 1)); ?>
                                    <option value="<?= $monthNum ?>" <?= $monthNum == str_pad($_selectedMonth, 2, "0", STR_PAD_LEFT) ? 'selected' : '' ?>><?= $monthName ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Custom Range: From Date + To Date -->
                <div class="col-sm-4" id="customContainer" <?= $_filterType !== 'custom' ? 'style="display:none;"' : '' ?>>
                    <div class="row gx-2">
                        <div class="col-6">
                            <label class="form-label fw-semibold text-muted mb-1">From Date</label>
                            <input type="date" class="form-control" name="from_date" id="fromDate" value="<?= htmlspecialchars($_fromDate) ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold text-muted mb-1">To Date</label>
                            <input type="date" class="form-control" name="to_date" id="toDate" value="<?= htmlspecialchars($_toDate) ?>">
                        </div>
                    </div>
                </div>

                <!-- Payroll Project -->
                <div class="col-sm-3">
                    <label class="form-label fw-semibold text-muted mb-1" for="payrollProject">Payroll Project</label>
                    <div class="d-flex align-items-center gap-2">
                        <select class="form-select w-100" name="payroll_project_id" id="payrollProject">
                            <option value="">Select Project (Optional)</option>
                            <?php if (!empty($payroll_projects)): ?>
                                <?php foreach ($payroll_projects as $proj): ?>
                                    <option value="<?= htmlspecialchars($proj['id']) ?>"
                                        data-date="<?= htmlspecialchars($proj['date'] ?? '') ?>"
                                        data-iscustom="<?= htmlspecialchars($proj['is_custom_date'] ?? '0') ?>"
                                        data-start="<?= htmlspecialchars($proj['start_date'] ?? '') ?>"
                                        data-end="<?= htmlspecialchars($proj['end_date'] ?? '') ?>"
                                        <?= (isset($_GET['payroll_project_id']) && $_GET['payroll_project_id'] == $proj['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($proj['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <button type="button" class="btn btn-primary text-nowrap" data-bs-toggle="modal"
                            data-bs-target="#createProjectModal" style="height: 38px;">
                            <i class="fa-solid fa-plus me-1"></i> Create Project
                        </button>
                    </div>
                </div>

                <!-- Search Button -->
                <div class="col-sm-auto">
                    <button type="submit" class="btn btn-primary" style="height:38px;">
                        <i class="fa-solid fa-search me-1"></i> Search
                    </button>
                    <a href="<?= $GLOBALS['base_url'] ?>/payroll-workspace" class="btn btn-outline-secondary ms-1" style="height:38px;">
                        <i class="fa-solid fa-rotate-left me-1"></i> Reset
                    </a>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const filterTypeSelect  = document.getElementById('filterTypeSelect');
    const filterTypeInput   = document.getElementById('filterTypeInput');
    const monthlyContainer  = document.getElementById('monthlyContainer');
    const customContainer   = document.getElementById('customContainer');
    const yearSel           = document.getElementById('year');
    const monthSel          = document.getElementById('month');
    const fromDateInput     = document.getElementById('fromDate');
    const toDateInput       = document.getElementById('toDate');
    const projectSelect     = document.getElementById('payrollProject');

    function applyFilterType(type) {
        filterTypeInput.value = type;
        if (type === 'custom') {
            monthlyContainer.style.display = 'none';
            customContainer.style.display  = '';
            // disable monthly fields so they are not submitted
            if (yearSel)  yearSel.disabled  = true;
            if (monthSel) monthSel.disabled = true;
            // enable custom fields
            if (fromDateInput) fromDateInput.disabled = false;
            if (toDateInput)   toDateInput.disabled   = false;
        } else {
            monthlyContainer.style.display = '';
            customContainer.style.display  = 'none';
            if (yearSel)      yearSel.disabled      = false;
            if (monthSel)     monthSel.disabled      = false;
            if (fromDateInput) fromDateInput.disabled = true;
            if (toDateInput)   toDateInput.disabled   = true;
        }
    }

    // Init on load
    applyFilterType(filterTypeSelect.value);

    filterTypeSelect.addEventListener('change', function () {
        applyFilterType(this.value);
    });

    // Auto-sync Project Date (keep existing behaviour and handle custom projects)
    if (projectSelect) {
        projectSelect.addEventListener('change', function () {
            const selectedOpt = this.options[this.selectedIndex];
            if (selectedOpt && selectedOpt.value) {
                const isCustom = selectedOpt.getAttribute('data-iscustom') === '1';
                
                if (isCustom) {
                    filterTypeSelect.value = 'custom';
                    applyFilterType('custom');
                    if (fromDateInput) fromDateInput.value = selectedOpt.getAttribute('data-start');
                    if (toDateInput) toDateInput.value = selectedOpt.getAttribute('data-end');
                } else {
                    const dateVal = selectedOpt.getAttribute('data-date');
                    if (dateVal) {
                        filterTypeSelect.value = 'monthly';
                        applyFilterType('monthly');
                        const parts = dateVal.split('-');
                        if (parts.length >= 2) {
                            if (yearSel)  yearSel.value  = parts[0];
                            if (monthSel) monthSel.value = parts[1];
                        }
                    }
                }
            }
            setTimeout(() => document.getElementById('workspaceFilterForm').submit(), 50);
        });
    }
})();
</script>



<!-- Create Project Modal -->
<div class="modal fade" id="createProjectModal" tabindex="-1" aria-labelledby="createProjectModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= $GLOBALS['base_url'] ?>/payroll-project-store" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="createProjectModalLabel"><i
                            class="fa-solid fa-folder-plus me-2"></i>Create Payroll Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <div class="form__group mb-3">
                        <label class="form-label" style="font-weight: 600;">Project Name <span
                                class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Enter project name" required>
                    </div>
                    <div class="form__group mb-3">
                        <label class="form-label" style="font-weight: 600;">Project Type <span
                                class="text-danger">*</span></label>
                        <select name="project_type" id="createProjectType" class="form-select" required>
                            <option value="monthly">Monthly</option>
                            <option value="custom">Custom Date Range</option>
                        </select>
                    </div>

                    <!-- Monthly -->
                    <div class="form__group mb-3" id="createMonthlyContainer">
                        <label class="form-label" style="font-weight: 600;">Project Month/Year <span
                                class="text-danger">*</span></label>
                        <input type="month" name="date" id="createProjectDate" class="form-control">
                    </div>

                    <!-- Custom Range -->
                    <div class="row mb-3" id="createCustomContainer" style="display: none;">
                        <div class="col-6">
                            <label class="form-label" style="font-weight: 600;">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="createProjectStart" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-weight: 600;">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" id="createProjectEnd" class="form-control">
                        </div>
                    </div>

                    <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            const pType = document.getElementById('createProjectType');
                            const mCont = document.getElementById('createMonthlyContainer');
                            const cCont = document.getElementById('createCustomContainer');
                            const pDate = document.getElementById('createProjectDate');
                            const pStart = document.getElementById('createProjectStart');
                            const pEnd = document.getElementById('createProjectEnd');

                            pType.addEventListener('change', function() {
                                if(this.value === 'monthly') {
                                    mCont.style.display = 'block';
                                    cCont.style.display = 'none';
                                    pDate.required = true;
                                    pStart.required = false;
                                    pEnd.required = false;
                                } else {
                                    mCont.style.display = 'none';
                                    cCont.style.display = 'flex';
                                    pDate.required = false;
                                    pStart.required = true;
                                    pEnd.required = true;
                                }
                            });
                            
                            // Initialize
                            pType.dispatchEvent(new Event('change'));
                        });
                    </script>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary border" style="background:#fff; color:#000;"
                        data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary"
                        style="background-color: #0f766e; border-color: #0f766e;">
                        <i class="fa-solid fa-save me-2"></i> Save Project
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Workspace Table -->
<div class="row">
    <div class="col-xxl-12">

        <div class="card__wrapper border-0">
            <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                <h4 class="fw-bold text-dark mb-0">
                    <i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i>Payroll Workplace
                </h4>
                <div>
                    <button type="button" class="btn btn-info fw-semibold shadow-sm me-2 text-white"
                        style="background-color: #0f766e; border-color: #0f766e;"
                        onclick="saveWorkspaceData(this)">
                        <i class="fa-solid fa-save me-1"></i> Save Data
                    </button>
                    <button type="button" class="btn btn-success fw-semibold shadow-sm me-2"
                        onclick="$('#workspaceTable').DataTable().button('.buttons-csv').trigger();">
                        <i class="fa-solid fa-file-csv me-1"></i> Export CSV
                    </button>
                    <button type="button" class="btn btn-danger fw-semibold shadow-sm me-2" onclick="
                        var year = document.getElementById('year') ? document.getElementById('year').value : '';
                        var month = document.getElementById('month') ? document.getElementById('month').value : '';
                        window.location.href = '<?= $GLOBALS['base_url'] ?>/payroll-workspace-pdf?year=' + year + '&month=' + month;
                    ">
                        <i class="fa-solid fa-file-pdf me-1"></i> Export PDF
                    </button>
                </div>
            </div>
            


            <div class="table__wrapper" style="overflow: visible;">
                <table id="workspaceTable" class="table no-border-table mb-20">
                    <thead>
                        <tr class="table__title table__sort">
                            <th class="text-center" style="width: 40px;"><input type="checkbox" autocomplete="off" id="selectAllEmployees" class="form-check-input" checked style="cursor:pointer;" title="Select All"></th>
                            <th>SL</th>
                            <th>Department</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>ID</th>
                            <th>Workplace</th>
                            <th>Designation</th>
                            <th>Basic Salary</th>

                            <!-- Dynamic Earnings mapping as close to Excel as possible -->
                            <?php foreach ($items as $item): ?>
                                    <?php if ($item['type'] === 'earning'): ?>
                                            <th><?= htmlspecialchars($item['name']) ?></th>
                                    <?php endif; ?>
                            <?php endforeach; ?>

                            <th>Gross</th>
                            <th>Daily Salary</th>
                            <th>Advance on Salary</th>
                            <th>Meal Count</th>
                            <th>Subsidized Lunch</th>
                            <th>Late/Unpaid/Absent (Days)</th>
                            <th>Late/Unpaid/Absent (Amount)</th>
                            <th>Salary Hold</th>

                            <!-- Other Dynamic Deductions -->
                            <?php foreach ($items as $item): ?>
                                    <?php if ($item['type'] === 'deduction'): ?>
                                            <?php if (strpos(strtolower($item['name']), 'advance') === false && strpos(strtolower($item['name']), 'lunch') === false && strpos(strtolower($item['name']), 'absent') === false && strpos(strtolower($item['name']), 'lwp') === false && strpos(strtolower($item['name']), 'salary hold') === false): ?>
                                                    <th><?= htmlspecialchars($item['name']) ?></th>
                                            <?php endif; ?>
                                    <?php endif; ?>
                            <?php endforeach; ?>

                            <th>Punctuality Bonus</th>
                            <th>Overtime</th>
                            <th>Arrear</th>
                            <th>Duty/Food/Car Allowance</th>
                            <th>Joining Date</th>
                            <th>Resignation Date</th>
                            <th>Actual Effective Days</th>
                            <th>Net Salary</th>
                            <th>Tax</th>
                            <th>Net Payable</th>
                            <th>Bank</th>
                            <th>Cash</th>
                            <th>Salary Remarks</th>
                            <th>Attendance Remarks</th>
                            <th>Account Name</th>
                            <th>Account No</th>
                            <th>Amount</th>
                            <th>ID Check</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $itemMap = [];
                        foreach ($items as $x) {
                            $name = strtolower($x['name']);
                            if (strpos($name, 'advance') !== false)
                                $itemMap['advance'] = $x['id'];
                            elseif (strpos($name, 'lunch') !== false || strpos($name, 'meal') !== false)
                                $itemMap['lunch'] = $x['id'];
                            elseif (strpos($name, 'absent') !== false || strpos($name, 'lwp') !== false)
                                $itemMap['absent'] = $x['id'];
                            elseif (strpos($name, 'punctuality') !== false)
                                $itemMap['punctuality'] = $x['id'];
                            elseif (strpos($name, 'overtime') !== false)
                                $itemMap['overtime'] = $x['id'];
                            elseif (strpos($name, 'arrear') !== false)
                                $itemMap['arrear'] = $x['id'];
                            elseif (strpos($name, 'tax') !== false)
                                $itemMap['tax'] = $x['id'];
                            elseif (strpos($name, 'other') !== false)
                                $itemMap['other'] = $x['id'];
                        }

                        $sl = 1;
                        if (!empty($payslips)):
                            foreach ($payslips as $p):
                                $gross = (float) $p['emp_gross'];
                                $basic = (float) $p['emp_basic'];
                                $daily = $gross / 30;
                                $netSalary = (float) $p['net_salary'];
                                $paymentMethod = strtolower($p['payment_method'] ?? 'bank');
                                // Values from DB if exist, otherwise derived
                                $bank = isset($p['bank_amount']) ? (float)$p['bank_amount'] : (($paymentMethod == 'bank' || $paymentMethod == 'bank transfer') ? $netSalary : 0);
                                $cash = isset($p['cash_amount']) ? (float)$p['cash_amount'] : (($paymentMethod == 'cash') ? $netSalary : 0);

                                $advanceSalary = 0;
                                $mealCount = isset($p['tipsoi_present']) ? (float) $p['tipsoi_present'] : 0;
                                $subsidizeLunch = 0;
                                // Prioritize late_unpaid_absent_days for correct deduction logic
                                $unpaidAbsentsDays = isset($p['late_unpaid_absent_days']) ? (float)$p['late_unpaid_absent_days'] : (isset($p['tipsoi_absent']) ? (float) $p['tipsoi_absent'] : 0);
                                $unpaidAbsents = 0; // The amount
                                $punctBonus = 0;
                                $overtime = 0;
                                $arrear = 0;
                                $tax = 0;
                                $otherAllow = 0;

                                foreach ($items as $i) {
                                    $amt = isset($p['dynamic_details'][$i['id']]) ? (float) $p['dynamic_details'][$i['id']] : 0;
                                    $lname = strtolower($i['name']);
                                    if (strpos($lname, 'advance') !== false) {
                                        $advanceSalary += $amt;
                                    }
                                    if (strpos($lname, 'lunch') !== false || strpos($lname, 'meal') !== false) {
                                        $subsidizeLunch += $amt;
                                        // Attempting a pseudo meal count based on per_meal_amount if available
                                        $perMeal = (float) ($p['per_meal_amount'] ?? 0);
                                        if ($perMeal > 0 && $amt > 0) {
                                            $mealCount = $amt / $perMeal;
                                        }
                                    }
                                    if (strpos($lname, 'absent') !== false || strpos($lname, 'lwp') !== false) {
                                        $unpaidAbsents += $amt;
                                    }
                                    if (strpos($lname, 'punctuality') !== false) {
                                        $punctBonus += $amt;
                                    }
                                    if (strpos($lname, 'overtime') !== false) {
                                        $overtime += $amt;
                                    }
                                    if (strpos($lname, 'arrear') !== false) {
                                        $arrear += $amt;
                                    }
                                    if (strpos($lname, 'tax') !== false) {
                                        $tax += $amt;
                                    }
                                }
                                ?>
                                        <tr class="calc-row" data-payslip-id="<?= $p['id'] ?? 0 ?>" data-employee-id="<?= $p['real_emp_id_fk'] ?? $p['id'] ?? 0 ?>" data-employee-office-id="<?= htmlspecialchars($p['emp_id']) ?>" data-has-db="<?= isset($p['tipsoi_present']) ? 'true' : 'false' ?>">
                                            <?php $excludedState = (isset($p['is_excluded']) && $p['is_excluded'] == 1) ? "" : "checked"; ?>
                                            <td class="text-center"><input type="checkbox" autocomplete="off" class="form-check-input emp-checkbox" <?= $excludedState ?> style="cursor:pointer;"></td>
                                            <td><?= $sl++ ?></td>
                                            <td><?= htmlspecialchars($p['department_name'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($p['full_name']) ?></td>
                                            <td><?= htmlspecialchars($p['email'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($p['emp_id']) ?></td>
                                            <td><?= htmlspecialchars($p['workplace'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($p['designation_name'] ?? 'N/A') ?></td>
                                            <td class="cell-basic" data-val="<?= $basic ?>"><?= number_format($basic, 2) ?></td>

                                            <?php foreach ($items as $item): ?>
                                                    <?php if ($item['type'] === 'earning'): ?>
                                                            <?php
                                                                $isBasicAllow = false;
                                                                $nameStr = strtolower($item['name']);
                                                                if (strpos($nameStr, 'recreation') !== false) {
                                                                    $val = round($basic * 0.10, 2);
                                                                    $isBasicAllow = true;
                                                                } elseif (strpos($nameStr, 'conveyance') !== false) {
                                                                    $val = round($basic * 0.10, 2);
                                                                    $isBasicAllow = true;
                                                                } elseif (strpos($nameStr, 'medical') !== false) {
                                                                    $val = round($basic * 0.30, 2);
                                                                    $isBasicAllow = true;
                                                                } elseif (strpos($nameStr, 'house rent') !== false) {
                                                                    $val = round($basic * 0.50, 2);
                                                                    $isBasicAllow = true;
                                                                } else {
                                                                    if (isset($p['dynamic_details'][$item['id']])) {
                                                                        $val = round($p['dynamic_details'][$item['id']], 2);
                                                                    } else {
                                                                        $val = 0;
                                                                    }
                                                                }
                                                            ?>
                                                            <td><input type="number" step="0.01"
                                                                    class="form-control form-control-sm text-end in-earning <?= $isBasicAllow ? 'in-basic-allowance' : '' ?>"
                                                                    data-item-id="<?= $item['id'] ?>"
                                                                    value="<?= $val ?>"
                                                                    style="min-width: 80px;"></td>
                                                    <?php endif; ?>
                                            <?php endforeach; ?>

                                            <td class="cell-gross fw-bold text-nowrap" data-val="<?= $gross ?>"><?= number_format($gross, 2) ?></td>
                                            <td class="cell-daily text-nowrap" data-val="<?= $daily ?>"><?= number_format($daily, 2) ?>
                                            </td>
                                            <td><input type="number" step="0.01"
                                                    class="form-control form-control-sm text-end in-deduct in-advance"
                                                    <?= isset($itemMap['advance']) ? 'data-item-id="' . $itemMap['advance'] . '"' : '' ?>
                                                    value="<?= round($advanceSalary, 2) ?>" style="min-width: 80px;"></td>
                                            <td><input type="number" step="0.01"
                                                    class="form-control form-control-sm text-end in-mealcount"
                                                    data-permeal="<?= $p['per_meal_amount'] ?? 0 ?>" value="<?= $mealCount ?>"
                                                    style="width: 60px;"></td>
                                            <td><input type="number" step="0.01"
                                                    class="form-control form-control-sm text-end in-deduct in-sublunch"
                                                    <?= isset($itemMap['lunch']) ? 'data-item-id="' . $itemMap['lunch'] . '"' : '' ?>
                                                    value="<?= round($subsidizeLunch, 2) ?>" style="min-width: 80px;"></td>
                                            <td><input type="number" step="0.01"
                                                    class="form-control form-control-sm text-end in-absent-days" value="<?= $unpaidAbsentsDays ?>"
                                                    style="width: 60px;"></td>
                                            <td><input type="number" step="0.01"
                                                    class="form-control form-control-sm text-end in-deduct in-unpaid"
                                                    <?= isset($itemMap['absent']) ? 'data-item-id="' . $itemMap['absent'] . '"' : '' ?>
                                                    value="<?= round($unpaidAbsents, 2) ?>" style="min-width: 80px;"></td>
                                            <?php
                                            $isHold = strtolower(trim($p['salary_hold'] ?? '')) === 'hold';
                                            ?>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm shadow-sm btn-salary-hold <?= $isHold ? 'btn-danger' : 'btn-success' ?>" style="min-width: 80px;">
                                                    <?= $isHold ? 'Hold' : 'Unhold' ?>
                                                </button>
                                                <input type="hidden" class="in-salary-hold-string" value="<?= $isHold ? 'Hold' : '' ?>">
                                            </td>

                                            <?php foreach ($items as $item): ?>
                                                    <?php if ($item['type'] === 'deduction'): ?>
                                                            <?php if (strpos(strtolower($item['name']), 'advance') === false && strpos(strtolower($item['name']), 'lunch') === false && strpos(strtolower($item['name']), 'absent') === false && strpos(strtolower($item['name']), 'lwp') === false && strpos(strtolower($item['name']), 'salary hold') === false): ?>
                                                                    <?php
                                                                    $existingAmt = round($p['dynamic_details'][$item['id']] ?? 0, 2);
                                                                    ?>
                                                                    <td><input type="number" step="0.01"
                                                                            class="form-control form-control-sm text-end in-deduct in-other-deduct"
                                                                            data-item-id="<?= $item['id'] ?>"
                                                                            value="<?= $existingAmt ?>"
                                                                            style="min-width: 80px;"></td>
                                                            <?php endif; ?>
                                                    <?php endif; ?>
                                            <?php endforeach; ?>

                                            <td><input type="number" step="0.01"
                                                    class="form-control form-control-sm text-end in-earning in-punctuality"
                                                    <?= isset($itemMap['punctuality']) ? 'data-item-id="' . $itemMap['punctuality'] . '"' : '' ?>
                                                    value="<?= round($punctBonus, 2) ?>" style="min-width: 80px;"></td>
                                            <td><input type="number" step="0.01"
                                                    class="form-control form-control-sm text-end in-earning in-overtime"
                                                    <?= isset($itemMap['overtime']) ? 'data-item-id="' . $itemMap['overtime'] . '"' : '' ?>
                                                    value="<?= round($overtime, 2) ?>" style="min-width: 80px;"></td>
                                            <td><input type="number" step="0.01"
                                                    class="form-control form-control-sm text-end in-earning in-arrear"
                                                    <?= isset($itemMap['arrear']) ? 'data-item-id="' . $itemMap['arrear'] . '"' : '' ?>
                                                    value="<?= round($arrear, 2) ?>" style="min-width: 80px;"></td>
                                            <td><input type="number" step="0.01"
                                                    class="form-control form-control-sm text-end in-earning in-otherallow"
                                                    <?= isset($itemMap['other']) ? 'data-item-id="' . $itemMap['other'] . '"' : '' ?>
                                                    value="<?= round($otherAllow, 2) ?>" style="min-width: 80px;"></td>
                                            <td><?= !empty($p['joining_date']) ? date('d-M-Y', strtotime($p['joining_date'])) : '&nbsp;' ?>
                                            </td>
                                            <td><?= !empty($p['deleted_at']) ? date('d-M-Y', strtotime($p['deleted_at'])) : '&nbsp;' ?>
                                            </td>
                                            <td class="cell-effective-days text-center">30</td>
                                            <td class="cell-net fw-bold text-primary text-nowrap"
                                                data-payment-method="<?= htmlspecialchars($p['payment_method']) ?>">
                                                <?= number_format($netSalary, 2) ?>
                                            </td>
                                            <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-deduct in-tax"
                                                    <?= isset($itemMap['tax']) ? 'data-item-id="' . $itemMap['tax'] . '"' : '' ?>
                                                    value="<?= round($tax, 2) ?>" style="min-width: 80px;"></td>
                                            <td class="cell-netpayable fw-bold text-success text-nowrap">
                                                <?= number_format($netSalary, 2) ?>
                                            </td>
                                            <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-bank" value="<?= round($bank, 2) ?>" style="min-width: 80px;"></td>
                                            <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-cash" value="<?= round($cash, 2) ?>" style="min-width: 80px;"></td>
                                            <td><input type="text" class="form-control form-control-sm in-remarks"
                                                    value="<?= htmlspecialchars($p['remarks'] ?? '') ?>" style="min-width: 120px;"></td>
                                            <td><input type="text" class="form-control form-control-sm in-att-remarks"
                                                    value="<?= htmlspecialchars($p['attendance_remarks'] ?? '') ?>" style="min-width: 120px;"></td>
                                            <td><?= htmlspecialchars($p['account_holder_name'] ?? '&nbsp;') ?></td>
                                            <td><?= htmlspecialchars($p['account_number'] ?? '&nbsp;') ?></td>
                                            <td class="cell-amount text-nowrap"><?= number_format($bank, 2) ?></td>
                                            <td>&nbsp;</td>
                                        </tr>
                                <?php endforeach; endif; ?>
                    </tbody>
                    <tfoot>
    <tr class="fw-bold" style="background-color: #e6f2f1; color: #0f766e;">
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td class="text-end ps-3">Totals</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
</tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Floating Scroll to Bottom Anchor -->
<a href="#payroll-bottom-section" 
   class="shadow rounded-circle d-flex align-items-center justify-content-center" 
   style="position: fixed; top: 120px; right: 30px; width: 60px; height: 60px; z-index: 1050; background-color: #fff; border: 3px solid #0f766e; color: #0f766e; text-decoration: none; font-size: 28px;" 
   title="Scroll down">
   <i class="fa-solid fa-arrow-down"></i>
</a>
<!-- Target ID at bottom -->
<div id="payroll-bottom-section"></div>


<script>
    document.addEventListener("DOMContentLoaded", function () {
        // --- Live Calculations ---

        // Generate Footer Headers dynamically to perfectly match the columns
        let footData = '';
        $('#workspaceTable thead th').each(function(i) {
            if (i === 7) {
                footData += '<td class="text-end pe-3">Totals:</td>';
            } else if (i < 8) {
                footData += '<td></td>';
            } else {
                let isIgnore = $(this).text().toLowerCase().includes('date') || $(this).text().toLowerCase().includes('remark') || $(this).text().toLowerCase().includes('account') || $(this).text().toLowerCase().includes('id check') || $(this).text().toLowerCase().includes('salary hold');
                footData += '<td class="text-end ' + (isIgnore ? 'tf-ignore' : '') + '">0.00</td>';
            }
        });
        $('#workspaceTable tfoot tr').html(footData);

        function attachCalculations() {

            document.querySelectorAll('.calc-row').forEach(row => {
                const inputs = row.querySelectorAll('input');

                inputs.forEach(input => {
                    input.addEventListener('input', function (e) {
                        const basic = parseFloat(row.querySelector('.cell-basic').dataset.val) || 0;
                        const daily = parseFloat(row.querySelector('.cell-daily').dataset.val) || 0;
                        const perMeal = parseFloat(row.querySelector('.in-mealcount').dataset.permeal) || 0;

                        if (!e.target.classList.contains('in-sublunch')) {
                            const mealCount = parseFloat(row.querySelector('.in-mealcount').value) || 0;
                            row.querySelector('.in-sublunch').value = (mealCount * perMeal).toFixed(2);
                        } else {
                            if (perMeal > 0) {
                                const subLunch = parseFloat(row.querySelector('.in-sublunch').value) || 0;
                                row.querySelector('.in-mealcount').value = (subLunch / perMeal).toFixed(2);
                            }
                        }

                        const absentDays = parseFloat(row.querySelector('.in-absent-days').value) || 0;
                        row.querySelector('.in-unpaid').value = (absentDays * daily).toFixed(2);

                        // Use existing gross salary from database
                        let gross = parseFloat(row.querySelector('.cell-gross').dataset.val) || 0;
                        
                        let totalEarning = gross;
                        row.querySelectorAll('.in-earning').forEach(inp => {
                            if (!inp.classList.contains('in-basic-allowance')) {
                                totalEarning += (parseFloat(inp.value) || 0);
                            }
                        });
                        let totalDeduction = 0;
                        row.querySelectorAll('.in-deduct').forEach(inp => {
                            totalDeduction += (parseFloat(inp.value) || 0);
                        });

                        // Net Salary
                        let netSalary = totalEarning - totalDeduction;
                        row.querySelector('.cell-net').textContent = netSalary.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                        // Tax (already an input, but subtracts from net payable?)
                        // Wait, typically Net Payable is Net Salary - Tax in some systems, or Net Salary IS Net Payable. 
                        // In original PHP: Net Payable = Net Salary. Tax was separated. We'll set Net Payable = Net Salary - Tax. IF it was identical before, we just use Net Salary.
                        const tax = parseFloat(row.querySelector('.in-tax').value) || 0;
                        let netPayable = netSalary - tax; // In PHP it was identical to Net Salary, but tax wasn't subtracted. Assuming netPayable = netSalary here based on old code!
                        // Setting Net Payable = Net Salary 
                        netPayable = netSalary;

                        row.querySelector('.cell-netpayable').textContent = netPayable.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                        const payMethod = row.querySelector('.cell-net').dataset.paymentMethod || '';
                        let bankInput = row.querySelector('.in-bank');
                        let cashInput = row.querySelector('.in-cash');

                        if (e.target.classList.contains('in-bank')) {
                            cashInput.value = (netPayable - (parseFloat(bankInput.value) || 0)).toFixed(2);
                        } else if (e.target.classList.contains('in-cash')) {
                            bankInput.value = (netPayable - (parseFloat(cashInput.value) || 0)).toFixed(2);
                        } else {
                            if (payMethod.toLowerCase().includes('bank')) {
                                let cashVal = parseFloat(cashInput.value) || 0;
                                bankInput.value = (netPayable - cashVal).toFixed(2);
                            } else {
                                let bankVal = parseFloat(bankInput.value) || 0;
                                cashInput.value = (netPayable - bankVal).toFixed(2);
                            }
                        }

                        let dispBank = parseFloat(bankInput.value) || 0;
                        row.querySelector('.cell-amount').textContent = dispBank.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        calculateFooterTotals();
                    });
                });
            });
        }

        attachCalculations();
        
    
            // Dynamic Sticky Header Calculation
            setTimeout(function() {
                var nav = document.querySelector('.header') || document.querySelector('.navbar') || document.querySelector('header') || document.getElementById('header');
                var navHeight = nav ? nav.offsetHeight : 60;
                
                var style = document.createElement('style');
                style.innerHTML = `
                    .dataTables_scrollHead {
                        position: sticky !important;
                        top: ${navHeight}px !important;
                        z-index: 1020 !important;
                        background-color: #fff !important;
                        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
                    }
                    .dataTables_scrollHead table {
                        background-color: #fff !important;
                    }
                `;
                document.head.appendChild(style);
                
                // Force all wrappers to allow sticky inheritance
                var parent = document.querySelector('.dataTables_scrollHead');
                while(parent && parent.tagName !== 'BODY') {
                    if (parent.classList.contains('card-body') || parent.classList.contains('card') || parent.classList.contains('table__wrapper')) {
                        parent.style.overflow = 'visible';
                    }
                    parent = parent.parentElement;
                }
            }, 300);

    function calculateFooterTotals() {
        var nodes = [];
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#workspaceTable')) {
            nodes = $('#workspaceTable').DataTable().rows().nodes();
        } else {
            nodes = document.querySelectorAll('.calc-row');
        }
        
        let colCount = $('#workspaceTable thead th').length;
        let totals = new Array(colCount).fill(0);
        
        $(nodes).each(function() {
            var row = $(this);
            if (!row.find('.emp-checkbox').is(':checked')) return;

            row.find('td').each(function(index) {
                if (index < 7) return; // Skip strings
                
                let val = 0;
                let input = $(this).find('input[type="number"]');
                if (input.length > 0) {
                    val = parseFloat(input.val()) || 0;
                } else if ($(this).hasClass('text-center') || $(this).find('button').length > 0 || $(this).text().includes('-') && $(this).text().trim().length > 6) {
                    // Skip dates and buttons (Salary Hold, Joining Date, Resignation Date)
                    val = 0;
                } else {
                    val = parseFloat($(this).text().replace(/,/g, '')) || 0;
                }
                if (!isNaN(val)) {
                    totals[index] += val;
                }
            });
        });

        $('#workspaceTable tfoot').show();
        $('.dataTables_scrollFoot tfoot tr, #workspaceTable tfoot tr').each(function() {
            $(this).find('td').each(function(index) {
            if (index > 7) {
                // Formatting
                let valFormat = totals[index].toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                if (totals[index] === 0 && $('#workspaceTable tbody tr:first td').eq(index).find('input, button, .text-center').length === 0 && $('#workspaceTable tbody tr:first td').eq(index).text().trim() === '') {
                    // Skip empty columns like Remarks
                    $(this).text('');
                } else if ($(this).hasClass('tf-ignore')) {
                    $(this).text('');
                } else {
                    $(this).text(valFormat);
                }
            }
        });
        });
    }


        // Forcibly recalculate all rows on initial render so Net Salary and Net Payable are mapped
        document.querySelectorAll('.calc-row').forEach(row => {
            const firstInput = row.querySelector('input');
            if (firstInput) {
                firstInput.dispatchEvent(new Event('input'));
            }
        });

        // --- Date range from PHP (works for both Monthly and Custom Range modes) ---
        const fromDateStr = "<?= $selectedFromDate ?? date('Y-m-01') ?>";
        const toDateStr   = "<?= $selectedToDate   ?? date('Y-m-t')  ?>";

        // Calculate effective days in the range and update "Actual Effective Days" cells
        (function () {
            const from = new Date(fromDateStr);
            const to   = new Date(toDateStr);
            const diffMs   = to - from;
            const effectiveDays = Math.round(diffMs / (1000 * 60 * 60 * 24)) + 1; // inclusive
            document.querySelectorAll('.cell-effective-days').forEach(cell => {
                cell.textContent = effectiveDays;
            });
        })();

        // --- Tipsoi Auto-Fallback for Missing DB Data ---
        const missingRows = document.querySelectorAll('.calc-row[data-has-db="false"]');
        if (fromDateStr && toDateStr && missingRows.length > 0) {
            const params = new URLSearchParams({
                from_date: fromDateStr,
                to_date:   toDateStr
            });

            fetch(`<?= $GLOBALS['base_url'] ?>/api/hrm/attendance?` + params.toString())
            .then(r => r.json())
            .then(res => {
                if (res.success && res.data) {
                    const rawData = res.data;
                    const localEmployees = res.local_employees || [];

                    rawData.forEach(emp => {
                         const attMap = emp.attendance || {};
                         const officeId = String(emp.employeeOfficeId || emp.employeeId).toLowerCase().trim();
                         const nameNormalized = String(emp.employeeName || '').toLowerCase().trim();

                         const localMatch = localEmployees.find(le => {
                            const leId = String(le.employee_id || '').toLowerCase().trim();
                            const leName = String(le.full_name || '').toLowerCase().trim();
                            return (officeId && leId === officeId) || (nameNormalized && leName === nameNormalized);
                         });

                         if (localMatch) {
                             const matchedRow = document.querySelector(`.calc-row[data-has-db="false"][data-employee-id="${localMatch.id}"]`);
                             if (matchedRow) {
                                 let present = 0;
                                 let absent  = 0;
                                 
                                 Object.keys(attMap).forEach(timestamp => {
                                     const dayAtt = attMap[timestamp];
                                     if (dayAtt.isPresent) present++;
                                     else if (!dayAtt.isOffday && !dayAtt.isHoliday && !dayAtt.isLeave) absent++;
                                 });

                                 matchedRow.querySelector('.in-mealcount').value    = present;
                                 matchedRow.querySelector('.in-absent-days').value  = absent;
                                 
                                 matchedRow.querySelector('input').dispatchEvent(new Event('input'));
                             }
                         }
                    });
                }
            })
            .catch(err => console.error("Tipsoi Background Sync failed:", err));
        }



        // Auto-sync Project Date
        const projectSelect = document.getElementById('payrollProject');
        if (projectSelect) {
            projectSelect.addEventListener('change', function() {
                const selectedOpt = this.options[this.selectedIndex];
                if (selectedOpt && selectedOpt.value) {
                    const dateVal = selectedOpt.getAttribute('data-date');
                    if (dateVal) {
                        const parts = dateVal.split('-');
                        if (parts.length >= 2) {
                            const yearSelect = document.getElementById('year');
                            const monthSelect = document.getElementById('month');
                            if (yearSelect) yearSelect.value = parts[0];
                            if (monthSelect) monthSelect.value = parts[1];
                        }
                    }
                }
                setTimeout(() => this.form.submit(), 50);
            });
        }

        // Dynamically rename 'Save Data' button if data is already saved
        const hasSavedData = Array.from(document.querySelectorAll('.calc-row')).some(row => {
            const pid = parseInt(row.getAttribute('data-payslip-id') || '0', 10);
            return pid > 0;
        });

        const saveBtn = document.querySelector('button[onclick="saveWorkspaceData(this)"]');
        if (saveBtn) {
            if (hasSavedData) {
                saveBtn.innerHTML = '<i class="fa-solid fa-edit me-1"></i> Update Data';
            } else {
                saveBtn.innerHTML = '<i class="fa-solid fa-save me-1"></i> Save Data';
            }
        }

        // --- DataTables with CSV and PDF ---
        if (typeof $ !== 'undefined' && $.fn.DataTable) {
            var table = $('#workspaceTable').DataTable({
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                pageLength: 10,
                autoWidth: false,
                responsive: true,
                scrollX: true,
                
                language: {
                    search: "",
                    searchPlaceholder: "Search...",
                    lengthMenu: "_MENU_ entries"
                },
                dom: '<"row align-items-center mb-3"<"col-sm-6 text-start"l><"col-sm-6 text-end"f><"d-none"B>>' +
                    '<"row"<"col-sm-12"tr>>' +
                    '<"row align-items-center mt-3"<"col-sm-6 text-start"i><"col-sm-6 text-end"p>>',
                buttons: [
                    {
                        extend: 'csvHtml5',
                        text: 'CSV',
                        className: 'buttons-csv',
                        title: 'Payroll_Workplace_Export',
                          footer: true,
                          exportOptions: {
                            rows: function ( idx, data, node ) {
                                return $(node).find('.emp-checkbox').is(':checked');
                            },
                            columns: ':not(:first-child)',
                            format: {
                                  body: function (inner, rowidx, colidx, node) {
                                      if ($(node).find('input').length > 0) {
                                          return $(node).find('input').val();
                                      } else {
                                          return inner.replace(/(<([^>]+)>)/ig, "").trim();
                                      }
                                  },
                                  footer: function (inner, colidx, node) {
                                      return inner ? inner.toString().replace(/(<([^>]+)>)/ig, "").trim() : "";
                                  }
                              }
                        }
                    },
                    {
                        text: '<i class="fa-solid fa-file-pdf me-1"></i> PDF',
                        className: 'btn btn-danger btn-sm',
                        action: function (e, dt, node, config) {
                            exportDynamicPDF(table);
                        }
                    }
                ]
            });

            // Extract all rows from DataTable and POST to PDF endpoint
            function exportDynamicPDF(dt) {
                var year = document.getElementById('year') ? document.getElementById('year').value : '';
                var month = document.getElementById('month') ? document.getElementById('month').value : '';

                var rowData = [];
                var headerData = [];

                // Get header data
                $(dt.table().header()).find('th').each(function () {
                    headerData.push($(this).text().trim());
                });

                // Get data from all pages (not just current page)
                var dtNodes = dt.rows().nodes();

                $(dtNodes).each(function () {
                    var tr = $(this);
                    var isExcluded = tr.find('.emp-checkbox').is(':checked') ? 0 : 1;
                    var rowArr = [];
                    tr.find('td').each(function (index) {
                        if (index === 0) return;
                        var cell = $(this);
                        if (cell.find('input').length > 0) {
                            rowArr.push(cell.find('input').val());
                        } else {
                            rowArr.push(cell.text().trim());
                        }
                    });
                    rowData.push(rowArr);
                });

                var form = document.createElement("form");
                form.method = "POST";
                form.action = '<?= $GLOBALS['base_url'] ?? '/' ?>/payroll-workspace-pdf?year=' + year + '&month=' + month;
                form.target = "_blank"; // Open PDF in new tab

                var input = document.createElement("input");
                input.type = "hidden";
                input.name = "table_data";
                input.value = JSON.stringify(rowData);
                form.appendChild(input);

                var inputHeaders = document.createElement("input");
                inputHeaders.type = "hidden";
                inputHeaders.name = "table_headers";
                inputHeaders.value = JSON.stringify(headerData);
                form.appendChild(inputHeaders);

                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            }
            
            // Assign save function to window so the button can access it
            window.saveWorkspaceData = function(btn) {
                var projectId = $('#payrollProject').val();
                if (!projectId) {
                    Swal.fire('Error', 'Please select a Payroll Project before saving.', 'error');
                    return;
                }

                $(btn).prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');

                var rowData = [];
                var dtNodes = table.rows().nodes();

                $(dtNodes).each(function () {
                    var tr = $(this);
                    
                    var isExcluded = tr.find('.emp-checkbox').is(':checked') ? 0 : 1;
                    var payslipIdStr = tr.attr('data-payslip-id');
                    var payslipId = parseInt(payslipIdStr || '0', 10);
                    var employeeId = parseInt(tr.attr('data-employee-id') || '0', 10);
                    console.log('Sending row:', { payslipIdStr: payslipIdStr, parsedPayslipId: payslipId, employeeId: employeeId });
                    
                    var netSalary = parseFloat(tr.find('.cell-net').text().trim().replace(/,/g, '')) || 0;
                    var totalEarning = parseFloat(tr.find('.cell-gross').text().trim().replace(/,/g, '')) || 0;
                    var totalDeduction = 0;
                    var salaryHoldString = tr.find('.in-salary-hold-string').val() || '';
                    var bankAmount = parseFloat(tr.find('.in-bank').val()) || 0;
                    var cashAmount = parseFloat(tr.find('.in-cash').val()) || 0;
                    var lateUnpaidAbsentDays = parseFloat(tr.find('.in-absent-days').val()) || 0;
                    var remarks = tr.find('.in-remarks').val() || '';
                    var attRemarks = tr.find('.in-att-remarks').val() || '';
                    
                    var dynamicDetails = {};
                    
                    tr.find('.in-earning, .in-deduct').each(function() {
                        var itemId = $(this).attr('data-item-id');
                        var val = parseFloat($(this).val()) || 0;
                        if (itemId && val > 0) {
                            dynamicDetails[itemId] = val;
                        }
                        if ($(this).hasClass('in-deduct')) {
                            totalDeduction += val;
                        }
                    });

                    rowData.push({
                        payslip_id: payslipId,
                        employee_id: employeeId,
                        net_salary: netSalary,
                        total_earning: totalEarning,
                        total_deduction: totalDeduction,
                        salary_hold: salaryHoldString,
                        bank_amount: bankAmount,
                        cash_amount: cashAmount,
                        late_unpaid_absent_days: lateUnpaidAbsentDays,
                        remarks: remarks,
                        attendance_remarks: attRemarks,
                        is_excluded: isExcluded,
                        details: dynamicDetails
                    });
                });

                if (rowData.length === 0) {
                    Swal.fire('Warning', 'No payslips data to save.', 'warning');
                    $(btn).prop('disabled', false).html(hasSavedData ? '<i class="fa-solid fa-edit me-1"></i> Update Data' : '<i class="fa-solid fa-save me-1"></i> Save Data');
                    return;
                }

                $.ajax({
                    url: '<?= $GLOBALS['base_url'] ?>/save-workspace-payslips',
                    type: 'POST',
                    contentType: 'application/json',
                    dataType: 'json',
                    data: JSON.stringify({
                        project_id: projectId,
                        year: $('select[name="year"]').val(),
                        month: $('select[name="month"]').val(),
                        rows: rowData
                    }),
                    success: function (res) {
                        try {
                            if (typeof res === 'string') {
                                res = JSON.parse(res);
                            }
                        } catch(e) {}
                        
                        if (res.success) {
                            Swal.fire('Success', res.message, 'success').then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Error', res.message || 'Operation failed', 'error');
                            $(btn).prop('disabled', false).html(hasSavedData ? '<i class="fa-solid fa-edit me-1"></i> Update Data' : '<i class="fa-solid fa-save me-1"></i> Save Data');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'Server connection failed', 'error');
                        $(btn).prop('disabled', false).html(hasSavedData ? '<i class="fa-solid fa-edit me-1"></i> Update Data' : '<i class="fa-solid fa-save me-1"></i> Save Data');
                    }
                });
            };
        }
    });

    $(document).on('click', '.btn-salary-hold', function() {
        var btn = $(this);
        var hiddenInput = btn.siblings('.in-salary-hold-string');
        if (hiddenInput.val() === 'Hold') {
            hiddenInput.val('');
            btn.text('Unhold').removeClass('btn-danger').addClass('btn-success');
        } else {
            hiddenInput.val('Hold');
            btn.text('Hold').removeClass('btn-success').addClass('btn-danger');
        }
    });
    // --- Database-driven Employee Selection ---
    function updateMasterCheckbox() {
        let allChecked = true;
        let totalCount = 0;
        var nodes = [];
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#workspaceTable')) {
            nodes = $('#workspaceTable').DataTable().rows().nodes();
        } else {
            nodes = document.querySelectorAll('.calc-row');
        }
        $(nodes).each(function() {
            if ($(this).find('.emp-checkbox').length > 0) {
                totalCount++;
                if (!$(this).find('.emp-checkbox').is(':checked')) {
                    allChecked = false;
                }
            }
        });
        $('#selectAllEmployees').prop('checked', allChecked && totalCount > 0);
    }
    
    $(document).ready(function() {
        setTimeout(function() {
            updateMasterCheckbox();
            calculateFooterTotals();
        }, 200);
        if ($.fn.DataTable) {
            $('#workspaceTable').on('draw.dt', function() {
                updateMasterCheckbox();
                calculateFooterTotals();
            });
        }
    });

    $(document).on('change', '#selectAllEmployees', function() {
        var isChecked = $(this).is(':checked');
        var nodes = [];
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#workspaceTable')) {
            nodes = $('#workspaceTable').DataTable().rows().nodes();
        } else {
            nodes = document.querySelectorAll('.calc-row');
        }
        $(nodes).each(function() {
            $(this).find('.emp-checkbox').prop('checked', isChecked);
        });
        calculateFooterTotals();
    });
    
    $(document).on('change', '.emp-checkbox', function() {
        if (!$(this).is(':checked')) {
            $('#selectAllEmployees').prop('checked', false);
        } else {
            updateMasterCheckbox();
        }
        calculateFooterTotals();
    });

</script>


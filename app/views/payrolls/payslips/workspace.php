<?php
$page = "Payroll Workplace";
$show_breadcrumb = true;
?>

<!-- Filter Section -->
<div class="row mb-3">
    <div class="col-12">
        <div class="filters card__wrapper p-3 bg-white rounded shadow-sm">
            <form method="GET" action="<?= $GLOBALS['base_url'] ?>/payroll-workspace" class="row gx-3 gy-2 align-items-center">
                <div class="col-sm-3">
                    <label for="year">Year</label>
                    <select class="form-select" name="year" id="year" onchange="this.form.submit()">
                        <?php
                        $startYear = 2020;
                        $endYear = 2035;
                        $sysYear = $selectedYear ?: date('Y');
                        for ($y = $startYear; $y <= $endYear; $y++): ?>
                            <option value="<?= $y ?>" <?= $y == $sysYear ? 'selected' : '' ?>>
                                <?= $y ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-sm-3">
                    <label for="month">Month</label>
                    <select class="form-select" name="month" id="month" onchange="this.form.submit()">
                        <?php
                        $sysMonth = $selectedMonth ?: date('m');
                        for ($m = 1; $m <= 12; $m++):
                            $monthNum = str_pad($m, 2, "0", STR_PAD_LEFT);
                            $monthName = date('F', mktime(0, 0, 0, $m, 1));
                            ?>
                                <option value="<?= $monthNum ?>" <?= $monthNum == str_pad($sysMonth, 2, "0", STR_PAD_LEFT) ? 'selected' : '' ?>>
                                    <?= $monthName ?>
                                </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-sm-4">
                    <label for="payrollProject">Payroll Project</label>
                    <div class="d-flex align-items-center gap-2">
                        <select class="form-select w-100" name="payroll_project_id" id="payrollProject">
                            <option value="">Select Project (Optional)</option>
                            <?php if (!empty($payroll_projects)): ?>
                                    <?php foreach ($payroll_projects as $proj): ?>
                                            <option value="<?= htmlspecialchars($proj['id']) ?>" data-date="<?= htmlspecialchars($proj['date'] ?? '') ?>" <?= (isset($_GET['payroll_project_id']) && $_GET['payroll_project_id'] == $proj['id']) ? 'selected' : '' ?>>
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
                <div class="col-auto align-self-end mt-4 d-none">
                    <button type="submit" class="btn btn-primary d-none">Filter</button>
                </div>
            </form>
        </div>
    </div>
</div>

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
                        <label class="form-label" style="font-weight: 600;">Project Month/Year <span
                                class="text-danger">*</span></label>
                        <input type="month" name="date" class="form-control" required>
                    </div>
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
                    <button type="button" class="btn btn-danger fw-semibold shadow-sm" onclick="
                        var year = document.getElementById('year') ? document.getElementById('year').value : '';
                        var month = document.getElementById('month') ? document.getElementById('month').value : '';
                        window.location.href = '<?= $GLOBALS['base_url'] ?>/payroll-workspace-pdf?year=' + year + '&month=' + month;
                    ">
                        <i class="fa-solid fa-file-pdf me-1"></i> Export PDF
                    </button>
                </div>
            </div>
            <div class="table__wrapper table-responsive">
                <table id="workspaceTable" class="table no-border-table mb-20">
                    <thead>
                        <tr class="table__title table__sort">
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
                                                                // Calculate fallback percentage for specific allowances if no value is set
                                                                $val = 0;
                                                                if (isset($p['dynamic_details'][$item['id']])) {
                                                                    $val = round($p['dynamic_details'][$item['id']], 2);
                                                                } else {
                                                                    $nameStr = strtolower($item['name']);
                                                                    if (strpos($nameStr, 'recreation') !== false ||
                                                                        strpos($nameStr, 'conveyance') !== false ||
                                                                        strpos($nameStr, 'medical') !== false ||
                                                                        strpos($nameStr, 'house rent') !== false) {
                                                                        
                                                                        $percentage = (float)($item['percentage'] ?? 0);
                                                                        if ($percentage > 0) {
                                                                            $val = round(($gross / 2) * ($percentage / 100), 2);
                                                                        }
                                                                    }
                                                                }
                                                            ?>
                                                            <td><input type="number" step="0.01"
                                                                    class="form-control form-control-sm text-end in-earning"
                                                                    data-item-id="<?= $item['id'] ?>"
                                                                    value="<?= $val ?>"
                                                                    style="min-width: 80px;"></td>
                                                    <?php endif; ?>
                                            <?php endforeach; ?>

                                            <td class="cell-gross fw-bold text-nowrap"><?= number_format($gross, 2) ?></td>
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
                                            <td>30</td>
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
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // --- Live Calculations ---
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

                        // Re-sum gross salary
                        let totalEarning = basic;
                        row.querySelectorAll('.in-earning').forEach(inp => {
                            totalEarning += (parseFloat(inp.value) || 0);
                        });

                        row.querySelector('.cell-gross').textContent = totalEarning.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                        // Re-sum deductions
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
                    });
                });
            });
        }

        attachCalculations();

        // Forcibly recalculate all rows on initial render so Net Salary and Net Payable are mapped
        document.querySelectorAll('.calc-row').forEach(row => {
            const firstInput = row.querySelector('input');
            if (firstInput) {
                firstInput.dispatchEvent(new Event('input'));
            }
        });

        // --- Tipsoi Auto-Fallback for Missing DB Data ---
        const selectedYear = "<?= $selectedYear ?? '' ?>";
        const selectedMonth = "<?= $selectedMonth ?? '' ?>";

        if (selectedYear && selectedMonth) {
            const missingRows = document.querySelectorAll('.calc-row[data-has-db="false"]');
            if (missingRows.length > 0) {
                const toDateObj = new Date(selectedYear, selectedMonth, 0); 
                const fromDateStr = `${selectedYear}-${String(selectedMonth).padStart(2, '0')}-01`;
                const toDateStr = `${selectedYear}-${String(selectedMonth).padStart(2, '0')}-${String(toDateObj.getDate()).padStart(2, '0')}`;
                
                const params = new URLSearchParams({
                    from_date: fromDateStr,
                    to_date: toDateStr
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
                                     let absent = 0;
                                     
                                     Object.keys(attMap).forEach(timestamp => {
                                         const dayAtt = attMap[timestamp];
                                         if (dayAtt.isPresent) present++;
                                         else if (!dayAtt.isOffday && !dayAtt.isHoliday && !dayAtt.isLeave) absent++;
                                     });

                                     matchedRow.querySelector('.in-mealcount').value = present;
                                     matchedRow.querySelector('.in-absent-days').value = absent;
                                     
                                     matchedRow.querySelector('input').dispatchEvent(new Event('input'));
                                 }
                             }
                        });
                    }
                })
                .catch(err => console.error("Tipsoi Background Sync failed:", err));
            }
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
                        exportOptions: {
                            format: {
                                body: function (inner, rowidx, colidx, node) {
                                    if ($(node).find('input').length > 0) {
                                        return $(node).find('input').val();
                                    } else {
                                        return inner.replace(/(<([^>]+)>)/ig, "").trim();
                                    }
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
                    var rowArr = [];
                    tr.find('td').each(function () {
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
</script>
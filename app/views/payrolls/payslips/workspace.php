<?php
$page = "Payroll Workplace";
$show_breadcrumb = true;
?>

<!-- Filter Section -->
<div class="row mb-3">
    <div class="col-12">
        <div class="filters card__wrapper p-3 bg-white rounded shadow-sm">
            <form method="GET" action="<?= $base_url ?>/payroll-workspace" class="row gx-3 gy-2 align-items-center">
                <div class="col-sm-3">
                    <label for="year">Year</label>
                    <select class="form-select" name="year">
                        <?php
                        $startYear = 2025;
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
                    <select class="form-select" name="month" id="month">
                        <?php for ($m = 1; $m <= 12; $m++):
                            $monthNum = str_pad($m, 2, "0", STR_PAD_LEFT);
                            $monthName = date('F', mktime(0, 0, 0, $m, 1));
                            ?>
                            <option value="<?= $monthNum ?>" <?= $monthNum == str_pad($selectedMonth, 2, "0", STR_PAD_LEFT) ? 'selected' : '' ?>>
                                <?= $monthName ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-auto align-self-end mt-4">
                    <button type="submit" class="btn btn-primary">Filter</button>
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
                    <button type="button" class="btn btn-success fw-semibold shadow-sm me-2"
                        onclick="$('#workspaceTable').DataTable().button('.buttons-csv').trigger();">
                        <i class="fa-solid fa-file-csv me-1"></i> Export CSV
                    </button>
                    <button type="button" class="btn btn-danger fw-semibold shadow-sm" onclick="
                        var year = document.getElementById('year') ? document.getElementById('year').value : '';
                        var month = document.getElementById('month') ? document.getElementById('month').value : '';
                        window.location.href = '<?= $base_url ?>/payroll-workspace-pdf?year=' + year + '&month=' + month;
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

                            <!-- Other Dynamic Deductions -->
                            <?php foreach ($items as $item): ?>
                                <?php if ($item['type'] === 'deduction'): ?>
                                    <?php if (strpos(strtolower($item['name']), 'advance') === false && strpos(strtolower($item['name']), 'lunch') === false && strpos(strtolower($item['name']), 'absent') === false): ?>
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
                        $sl = 1;
                        if (!empty($payslips)):
                            foreach ($payslips as $p):
                                $gross = (float) $p['emp_gross'];
                                $basic = (float) $p['emp_basic'];
                                $daily = $gross / 30;
                                $netSalary = (float) $p['net_salary'];
                                $paymentMethod = strtolower($p['payment_method']);
                                $bank = ($paymentMethod == 'bank' || $paymentMethod == 'bank transfer') ? $netSalary : 0;
                                $cash = ($paymentMethod == 'cash') ? $netSalary : 0;

                                $advanceSalary = 0;
                                $mealCount = 0;
                                $subsidizeLunch = 0;
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
                                    if (strpos($lname, 'lwp') !== false || strpos($lname, 'absent') !== false) {
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
                                <tr>
                                    <td><?= $sl++ ?></td>
                                    <td><?= htmlspecialchars($p['department_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($p['full_name']) ?></td>
                                    <td><?= htmlspecialchars($p['email'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($p['emp_id']) ?></td>
                                    <td><?= htmlspecialchars($p['workplace'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($p['designation_name'] ?? 'N/A') ?></td>
                                    <td><?= number_format($basic, 2) ?></td>

                                    <?php foreach ($items as $item): ?>
                                        <?php if ($item['type'] === 'earning'): ?>
                                            <td><?= number_format($p['dynamic_details'][$item['id']] ?? 0, 2) ?></td>
                                        <?php endif; ?>
                                    <?php endforeach; ?>

                                    <td><?= number_format($gross, 2) ?></td>
                                    <td><?= number_format($daily, 2) ?></td>
                                    <td><?= number_format($advanceSalary, 2) ?></td>
                                    <td><?= $mealCount ?></td>
                                    <td><?= number_format($subsidizeLunch, 2) ?></td>
                                    <td>0</td> <!-- Absent Days (Not natively stored) -->
                                    <td><?= number_format($unpaidAbsents, 2) ?></td>

                                    <?php foreach ($items as $item): ?>
                                        <?php if ($item['type'] === 'deduction'): ?>
                                            <?php if (strpos(strtolower($item['name']), 'advance') === false && strpos(strtolower($item['name']), 'lunch') === false && strpos(strtolower($item['name']), 'absent') === false): ?>
                                                <td><?= number_format($p['dynamic_details'][$item['id']] ?? 0, 2) ?></td>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php endforeach; ?>

                                    <td><?= number_format($punctBonus, 2) ?></td>
                                    <td><?= number_format($overtime, 2) ?></td>
                                    <td><?= number_format($arrear, 2) ?></td>
                                    <td><?= number_format($otherAllow ?? 0, 2) ?></td>
                                    <td><?= !empty($p['joining_date']) ? date('d-M-Y', strtotime($p['joining_date'])) : 'N/A' ?>
                                    </td>
                                    <td><?= !empty($p['deleted_at']) ? date('d-M-Y', strtotime($p['deleted_at'])) : '' ?></td>
                                    <td>30</td>
                                    <td><?= number_format($netSalary, 2) ?></td>
                                    <td><?= number_format($tax, 2) ?></td>
                                    <td><?= number_format($netSalary, 2) ?></td> <!-- Net Payable = Net Salary -->
                                    <td><?= number_format($bank, 2) ?></td>
                                    <td><?= number_format($cash, 2) ?></td>
                                    <td><?= htmlspecialchars($p['remarks'] ?? '') ?></td>
                                    <td></td> <!-- Attendance Remarks -->
                                    <td><?= htmlspecialchars($p['account_holder_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($p['account_number'] ?? 'N/A') ?></td>
                                    <td><?= number_format($bank, 2) ?></td>
                                    <td></td> <!-- ID Check -->
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
        if (typeof $ !== 'undefined' && $.fn.DataTable) {
            $('#workspaceTable').DataTable({
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
                        extend: 'csvHtml5', text: 'CSV', className: 'buttons-csv',
                        title: 'Payroll_Workplace_Export'
                    },
                    {
                        text: '<i class="fa-solid fa-file-pdf me-1"></i> PDF',
                        className: 'btn btn-danger btn-sm',
                        action: function (e, dt, node, config) {
                            var year = document.getElementById('year') ? document.getElementById('year').value : '';
                            var month = document.getElementById('month') ? document.getElementById('month').value : '';
                            window.location.href = '<?= $GLOBALS['base_url'] ?? '/' ?>/payroll-workspace-pdf?year=' + year + '&month=' + month;
                        }
                    }
                ]
            });
        }
    });
</script>
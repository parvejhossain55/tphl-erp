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
                                <tr class="calc-row">
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
                                            <td><input type="number" step="0.01"
                                                    class="form-control form-control-sm text-end in-earning"
                                                    value="<?= round($p['dynamic_details'][$item['id']] ?? 0, 2) ?>"
                                                    style="min-width: 80px;"></td>
                                        <?php endif; ?>
                                    <?php endforeach; ?>

                                    <td class="cell-gross fw-bold text-nowrap"><?= number_format($gross, 2) ?></td>
                                    <td class="cell-daily text-nowrap" data-val="<?= $daily ?>"><?= number_format($daily, 2) ?>
                                    </td>
                                    <td><input type="number" step="0.01"
                                            class="form-control form-control-sm text-end in-deduct in-advance"
                                            value="<?= round($advanceSalary, 2) ?>" style="min-width: 80px;"></td>
                                    <td><input type="number" step="0.01"
                                            class="form-control form-control-sm text-end in-mealcount"
                                            data-permeal="<?= $p['per_meal_amount'] ?? 0 ?>" value="<?= $mealCount ?>"
                                            style="width: 60px;"></td>
                                    <td><input type="number" step="0.01"
                                            class="form-control form-control-sm text-end in-deduct in-sublunch"
                                            value="<?= round($subsidizeLunch, 2) ?>" style="min-width: 80px;"></td>
                                    <td><input type="number" step="0.01"
                                            class="form-control form-control-sm text-end in-absent-days" value="0"
                                            style="width: 60px;"></td>
                                    <td><input type="number" step="0.01"
                                            class="form-control form-control-sm text-end in-deduct in-unpaid"
                                            value="<?= round($unpaidAbsents, 2) ?>" style="min-width: 80px;"></td>

                                    <?php foreach ($items as $item): ?>
                                        <?php if ($item['type'] === 'deduction'): ?>
                                            <?php if (strpos(strtolower($item['name']), 'advance') === false && strpos(strtolower($item['name']), 'lunch') === false && strpos(strtolower($item['name']), 'absent') === false): ?>
                                                <td><input type="number" step="0.01"
                                                        class="form-control form-control-sm text-end in-deduct in-other-deduct"
                                                        value="<?= round($p['dynamic_details'][$item['id']] ?? 0, 2) ?>"
                                                        style="min-width: 80px;"></td>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php endforeach; ?>

                                    <td><input type="number" step="0.01"
                                            class="form-control form-control-sm text-end in-earning in-punctuality"
                                            value="<?= round($punctBonus, 2) ?>" style="min-width: 80px;"></td>
                                    <td><input type="number" step="0.01"
                                            class="form-control form-control-sm text-end in-earning in-overtime"
                                            value="<?= round($overtime, 2) ?>" style="min-width: 80px;"></td>
                                    <td><input type="number" step="0.01"
                                            class="form-control form-control-sm text-end in-earning in-arrear"
                                            value="<?= round($arrear, 2) ?>" style="min-width: 80px;"></td>
                                    <td><input type="number" step="0.01"
                                            class="form-control form-control-sm text-end in-earning in-otherallow"
                                            value="<?= round($otherAllow, 2) ?>" style="min-width: 80px;"></td>
                                    <td><?= !empty($p['joining_date']) ? date('d-M-Y', strtotime($p['joining_date'])) : '&nbsp;' ?>
                                    </td>
                                    <td><?= !empty($p['deleted_at']) ? date('d-M-Y', strtotime($p['deleted_at'])) : '&nbsp;' ?>
                                    </td>
                                    <td>30</td>
                                    <td class="cell-net fw-bold text-primary text-nowrap"
                                        data-payment-method="<?= htmlspecialchars($p['payment_method']) ?>">
                                        <?= number_format($netSalary, 2) ?></td>
                                    <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-tax"
                                            value="<?= round($tax, 2) ?>" style="min-width: 80px;"></td>
                                    <td class="cell-netpayable fw-bold text-success text-nowrap">
                                        <?= number_format($netSalary, 2) ?></td>
                                    <td class="cell-bank text-nowrap"><?= number_format($bank, 2) ?></td>
                                    <td class="cell-cash text-nowrap"><?= number_format($cash, 2) ?></td>
                                    <td><input type="text" class="form-control form-control-sm in-remarks"
                                            value="<?= htmlspecialchars($p['remarks'] ?? '') ?>" style="min-width: 120px;"></td>
                                    <td><input type="text" class="form-control form-control-sm in-att-remarks" value=""
                                            style="min-width: 120px;"></td>
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
                    input.addEventListener('input', function() {
                        const basic = parseFloat(row.querySelector('.cell-basic').dataset.val) || 0;
                        const daily = parseFloat(row.querySelector('.cell-daily').dataset.val) || 0;
                        const perMeal = parseFloat(row.querySelector('.in-mealcount').dataset.permeal) || 0;
                        
                        // Handle meal recalculation if meal count changed
                        if (this.classList.contains('in-mealcount')) {
                            const count = parseFloat(this.value) || 0;
                            row.querySelector('.in-sublunch').value = (count * perMeal).toFixed(2);
                        }
                        
                        // Handle absent recalculation if absent days changed
                        if (this.classList.contains('in-absent-days')) {
                            const days = parseFloat(this.value) || 0;
                            row.querySelector('.in-unpaid').value = (days * daily).toFixed(2);
                        }

                        // Re-sum gross salary
                        let totalEarning = basic;
                        row.querySelectorAll('.in-earning').forEach(inp => {
                            totalEarning += (parseFloat(inp.value) || 0);
                        });
                        
                        row.querySelector('.cell-gross').textContent = totalEarning.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

                        // Re-sum deductions
                        let totalDeduction = 0;
                        row.querySelectorAll('.in-deduct').forEach(inp => {
                            totalDeduction += (parseFloat(inp.value) || 0);
                        });

                        // Net Salary
                        let netSalary = totalEarning - totalDeduction;
                        row.querySelector('.cell-net').textContent = netSalary.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        
                        // Tax (already an input, but subtracts from net payable?)
                        // Wait, typically Net Payable is Net Salary - Tax in some systems, or Net Salary IS Net Payable. 
                        // In original PHP: Net Payable = Net Salary. Tax was separated. We'll set Net Payable = Net Salary - Tax. IF it was identical before, we just use Net Salary.
                        const tax = parseFloat(row.querySelector('.in-tax').value) || 0;
                        let netPayable = netSalary - tax; // In PHP it was identical to Net Salary, but tax wasn't subtracted. Assuming netPayable = netSalary here based on old code!
                        // Setting Net Payable = Net Salary 
                        netPayable = netSalary; 
                        
                        row.querySelector('.cell-netpayable').textContent = netPayable.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        
                        const payMethod = row.querySelector('.cell-net').dataset.paymentMethod || '';
                        let bank = 0, cash = 0;
                        if (payMethod.toLowerCase().includes('bank')) {
                            bank = netPayable;
                        } else if (payMethod.toLowerCase().includes('cash')) {
                            cash = netPayable;
                        }
                        
                        row.querySelector('.cell-bank').textContent = bank.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        row.querySelector('.cell-cash').textContent = cash.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        row.querySelector('.cell-amount').textContent = bank.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    });
                });
            });
        }
        
        attachCalculations();

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
                                body: function ( inner, rowidx, colidx, node ) {
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
                $(dt.table().header()).find('th').each(function() {
                    headerData.push($(this).text().trim());
                });

                // Get data from all pages (not just current page)
                var dtNodes = dt.rows().nodes(); 
                
                $(dtNodes).each(function() {
                    var tr = $(this);
                    var rowArr = [];
                    tr.find('td').each(function() {
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
        }
    });
</script>
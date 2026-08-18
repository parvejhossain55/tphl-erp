<?php
$page = "Salary Hold";
$show_breadcrumb = true;
?>

<!-- Filter Section -->
<div class="row mb-3">
    <div class="col-12">
        <div class="filters card__wrapper p-3 bg-white rounded shadow-sm">
            <form method="GET" action="<?= $base_url ?>/payroll-salary-hold" class="row gx-3 gy-2 align-items-center">
                <div class="col-sm-3">
                    <label for="year">Year</label>
                    <select class="form-select" name="year" id="yearx">
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

<!-- Salary Hold Table -->
<div class="row">
    <div class="col-xxl-12">

        <div class="card__wrapper border-0">
            <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                <h4 class="fw-bold text-dark mb-0">
                    <i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i>Salary Hold
                </h4>
                <div>
                    <button type="button" class="btn btn-success fw-semibold shadow-sm me-2"
                        onclick="$('#salaryHoldTable').DataTable().button('.buttons-csv').trigger();">
                        <i class="fa-solid fa-file-csv me-1"></i> Export CSV
                    </button>
                    <button type="button" class="btn btn-danger fw-semibold shadow-sm" onclick="
                        var year = document.getElementById('year') ? document.getElementById('year').value : '';
                        var month = document.getElementById('month') ? document.getElementById('month').value : '';
                        window.location.href = '<?= $base_url ?>/payroll-salary-hold-pdf?year=' + year + '&month=' + month;
                    ">
                        <i class="fa-solid fa-file-pdf me-1"></i> Export PDF
                    </button>
                </div>
            </div>
            <div class="table__wrapper table-responsive">
                <table id="salaryHoldTable" class="table no-border-table mb-20">
                    <thead>
                        <tr class="table__title table__sort">
                            <th>Serial</th>
                            <th>Name</th>
                            <th>Employee ID</th>
                            <th>Amount</th>
                            <th>Remark</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sl = 1;
                        if (!empty($holds)):
                            foreach ($holds as $hold):
                                ?>
                                <tr>
                                    <td>
                                        <?= $sl++ ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($hold['full_name'] ?? '') ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($hold['emp_id'] ?? '') ?>
                                    </td>
                                    <td>
                                        <?= number_format($hold['net_salary'] ?? 0, 2) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($hold['remarks'] ?? '') ?>
                                    </td>
                                </tr>
                                <?php
                            endforeach;
                        endif;
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- DataTables Configuration -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        if (typeof $ !== 'undefined' && $.fn.DataTable) {
            $('#salaryHoldTable').DataTable({
                dom: '<"row align-items-center mb-3"<"col-sm-6 text-start"l><"col-sm-6 text-end"f><"d-none"B>>' +
                    '<"row"<"col-sm-12"tr>>' +
                    '<"row align-items-center mt-3"<"col-sm-6 text-start"i><"col-sm-6 text-end"p>>',
                buttons: [
                    {
                        extend: 'csvHtml5', text: 'CSV', className: 'buttons-csv',
                        title: 'Salary_Hold_Export'
                    },
                    {
                        text: 'PDF', className: 'buttons-pdf',
                        action: function (e, dt, node, config) {
                            var year = document.getElementById('year') ? document.getElementById('year').value : '';
                            var month = document.getElementById('month') ? document.getElementById('month').value : '';
                            window.location.href = '<?= $base_url ?>/payroll-salary-hold-pdf?year=' + year + '&month=' + month;
                        }
                    }
                ],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search records..."
                },
                pagingType: "simple_numbers",
                pageLength: 50,
                ordering: true
            });
        }
    });
</script>
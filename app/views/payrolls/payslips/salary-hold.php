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
                    <select class="form-select custom-select2" name="year" id="year">
                        <?php
                        $startYear = date('Y') - 5;
                        $endYear = date('Y') + 5;
                        for ($y = $startYear; $y <= $endYear; $y++): ?>
                            <option value="<?= $y ?>" <?= $y == $selectedYear ? 'selected' : '' ?>>
                                <?= $y ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-sm-3">
                    <label for="month">Month</label>
                    <select class="form-select custom-select2" name="month" id="month">
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
        <div class="card__wrapper">
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
                dom: '<"row align-items-center mb-3"<"col-sm-6 text-start"l><"col-sm-6 text-end"Bf>>' +
                    '<"row"<"col-sm-12"tr>>' +
                    '<"row align-items-center mt-3"<"col-sm-6 text-start"i><"col-sm-6 text-end"p>>',
                buttons: [
                    {
                        extend: 'csvHtml5',
                        text: '<i class="fa-solid fa-file-csv me-1"></i> Export CSV',
                        className: 'btn btn-success btn-sm me-2',
                        title: 'Salary_Hold_Export'
                    },
                    {
                        text: '<i class="fa-solid fa-file-pdf me-1"></i> Export PDF',
                        className: 'btn btn-danger btn-sm me-3',
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
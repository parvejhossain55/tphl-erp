<?php
$page = "Salary Hold Management";
$show_breadcrumb = true;
?>

<!-- Content Wrapper -->
<div class="content flex-column-fluid" id="kt_content">
    <!-- Toolbar -->
    <div class="toolbar" id="kt_toolbar">
        <div id="kt_toolbar_container" class="container-fluid d-flex flex-stack">
            <div data-kt-swapper="true" data-kt-swapper-mode="prepend"
                data-kt-swapper-parent="{default: '#kt_content_container', 'lg': '#kt_toolbar_container'}"
                class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
                <h1 class="d-flex align-items-center text-dark fw-bolder fs-3 my-1">
                    <?= $page_title ?? 'Salary Hold Management' ?>
                </h1>
            </div>
        </div>
    </div>

    <!-- Main Content Container -->
    <div id="kt_content_container" class="container-xxl">
        <!-- Filter Card -->
        <div class="card mb-5 mb-xl-8 shadow-sm">
            <div class="card-body py-5">
                <form method="GET" action="<?= $GLOBALS['base_url'] ?>/payroll-salary-hold"
                    class="row gx-3 gy-2 align-items-center" id="holdFilterForm">
                    <div class="col-md-3">
                        <label class="form-label mb-1">Year</label>
                        <select class="form-select form-select-sm" name="year" id="filterYear" required>
                            <?php
                            $currentYear = date('Y');
                            for ($y = 2022; $y <= ($currentYear + 2); $y++) {
                                $sel = ($selectedYear == $y) ? 'selected' : '';
                                echo "<option value='$y' $sel>$y</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1">Month</label>
                        <select class="form-select form-select-sm" name="month" id="filterMonth" required>
                            <?php
                            for ($m = 1; $m <= 12; $m++) {
                                $monthName = date('F', mktime(0, 0, 0, $m, 1));
                                $mPad = str_pad($m, 2, "0", STR_PAD_LEFT);
                                $sel = ($selectedMonth == $mPad || $selectedMonth == $m) ? 'selected' : '';
                                echo "<option value='$mPad' $sel>$monthName</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label mb-1">Payroll Project</label>
                        <select class="form-select form-select-sm" name="payroll_project_id" id="filterProject" required>
                            <option value="">-- Select Project --</option>
                            <?php foreach ($payroll_projects as $prj): ?>
                                <option value="<?= $prj['id'] ?>" 
                                    data-date="<?= htmlspecialchars($prj['date']) ?>"
                                    <?= $selectedProject == $prj['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($prj['name']) ?> (<?= htmlspecialchars($prj['date']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mt-4 pt-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100 shadow-sm">
                            <i class="fa fa-search me-1"></i> Load
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($selectedProject && $selectedYear && $selectedMonth): ?>

            <!-- Add Hold Form -->
            <div class="card mb-5 shadow-sm border-top border-danger border-3">
                <div class="card-header min-h-40px bg-light-danger border-bottom">
                    <h3 class="card-title text-danger fw-bolder fs-5 m-0 p-3 pt-4"><i
                            class="fa fa-exclamation-triangle text-danger me-2"></i> Add Employee to Hold Status</h3>
                </div>
                <div class="card-body py-4">
                    <div class="row align-items-end">
                        <div class="col-md-5">
                            <label class="form-label mb-1">Select Employee</label>
                            <select id="employee_id_select" class="form-select form-select-sm" data-control="select2"
                                data-placeholder="Search Employee...">
                                <option value=""></option>
                                <?php foreach ($employeesList as $emp): ?>
                                    <option value="<?= $emp['id'] ?>">
                                        <?= htmlspecialchars($emp['employee_id']) ?> -
                                        <?= htmlspecialchars($emp['full_name']) ?> (
                                        <?= htmlspecialchars($emp['phone'] ?? '') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-sm btn-danger shadow-sm" id="btnAddHold">
                                <i class="fa fa-plus me-1"></i> Add Hold
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Holds Table -->
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom min-h-40px d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h3 class="card-title fw-bolder fs-5 m-0 py-3">Ongoing Holds (<?= count($holds) ?>)</h3>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-success fw-semibold shadow-sm" id="btnExportCSV"
                            onclick="$('#holdsTable').DataTable().button('.buttons-csv').trigger();">
                            <i class="fa fa-file-csv me-1"></i> Export CSV
                        </button>
                        <a href="<?= $GLOBALS['base_url'] ?>/payroll-salary-hold-pdf?payroll_project_id=<?= urlencode($selectedProject) ?>&year=<?= urlencode($selectedYear) ?>&month=<?= urlencode($selectedMonth) ?>" 
                           target="_blank" class="btn btn-sm btn-danger fw-semibold shadow-sm">
                            <i class="fa fa-file-pdf me-1"></i> Export PDF
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover align-middle gs-4 gy-3"
                            id="holdsTable">
                            <thead class="bg-light fw-bold text-muted text-uppercase">
                                <tr>
                                    <th class="w-50px text-center">SL</th>
                                    <th>Emp ID</th>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Designation</th>
                                    <th class="text-end">Held Amount</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalHeld = 0.0;
                                if (count($holds) > 0): 
                                ?>
                                    <?php foreach ($holds as $k => $h): 
                                        $amt = floatval($h['effective_amount'] ?? ($h['net_salary'] > 0 ? $h['net_salary'] : ($h['gross_salary'] ?? 0)));
                                        $totalHeld += $amt;
                                    ?>
                                        <tr>
                                            <td class="text-center">
                                                <?= $k + 1 ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($h['employee_id']) ?>
                                            </td>
                                            <td><span class="fw-bold text-dark">
                                                    <?= htmlspecialchars($h['full_name']) ?>
                                                </span></td>
                                            <td>
                                                <?= htmlspecialchars($h['department_name'] ?? '-') ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($h['designation_name'] ?? '-') ?>
                                            </td>
                                            <td class="text-end">
                                                <span class="fw-bold text-danger">৳ <?= number_format($amt, 2) ?></span>
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-light-success"
                                                    onclick="removeHold(<?= $h['payslip_id'] ?>)">
                                                    <i class="fa fa-undo"></i> Unhold
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="bg-light fw-bold">
                                <tr>
                                    <th colspan="5" class="text-end">Total Held Amount:</th>
                                    <th class="text-end text-danger fs-6">৳ <?= number_format($totalHeld, 2) ?></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

        <?php endif; ?>

    </div>
</div>

<?php ob_start(); ?>
$(document).ready(function () {
    // Project selection auto-syncs Year & Month
    $('#filterProject').on('change', function () {
        var selectedOpt = $(this).find('option:selected');
        var projectDate = selectedOpt.data('date');
        if (projectDate) {
            var parts = projectDate.split('-');
            if (parts.length >= 2) {
                var year = parts[0];
                var month = parts[1];
                $('#filterYear').val(year);
                $('#filterMonth').val(month);
            }
        }
    });

    if ($('#holdsTable').length && $.fn.DataTable) {
        $('#holdsTable').DataTable({
            "language": {
                "lengthMenu": "Show _MENU_",
            },
            "dom":
                "<'row'" + "<'col-sm-6 d-flex align-items-center justify-content-start'l>"
                + "<'col-sm-6 d-flex align-items-center justify-content-end'f><'d-none'B>>" + "<'table-responsive'tr>" + "<'row'"
                + "<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'i>"
                + "<'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>" + ">",
            buttons: [
                {
                    extend: 'csvHtml5',
                    text: 'CSV',
                    className: 'buttons-csv',
                    title: 'Salary_Hold_Report_<?= htmlspecialchars($selectedYear . '_' . $selectedMonth) ?>',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5]
                    }
                }
            ]
        });
    }

    $('#btnAddHold').on('click', function () {
        var empId = $('#employee_id_select').val();
        if (!empId) {
            Swal.fire('Error', 'Please select an employee first.', 'error');
            return;
        }
        var projectId = '<?= $selectedProject ?? '' ?>';
        var year = '<?= $selectedYear ?? '' ?>';
        var month = '<?= $selectedMonth ?? '' ?>';

        Swal.fire({
            title: 'Are you sure?',
            text: "This will pause the employee's salary payout for this project.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, Hold it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?= $GLOBALS['base_url'] ?? '' ?>/payroll-salary-hold-save',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        project_id: projectId,
                        year: year,
                        month: month,
                        employee_id: empId
                    }),
                    success: function (res) {
                        try {
                            if (typeof res === 'string') res = JSON.parse(res);
                        } catch (e) { }

                        if (res.success) {
                            Swal.fire('Success', res.message, 'success').then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Error', res.message || 'Operation failed.', 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'Server connection failed.', 'error');
                    }
                });
            }
        });
    });
});

window.removeHold = function(payslipId) {
    if (!payslipId) {
        Swal.fire('Error', 'Wait, Payslip ID is missing.', 'error');
        return;
    }

    Swal.fire({
        title: 'Remove Hold?',
        text: "This will unhold the salary for this employee.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1bc5bd',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Unhold'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '<?= $GLOBALS['base_url'] ?? '' ?>/payroll-salary-hold-remove',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ payslip_id: payslipId }),
                success: function (res) {
                    try {
                        if (typeof res === 'string') res = JSON.parse(res);
                    } catch (e) { }

                    if (res.success) {
                        Swal.fire('Success', res.message, 'success').then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('Error', res.message || 'Operation failed.', 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Server connection failed.', 'error');
                }
            });
        }
    });
};
<?php $page_script = ob_get_clean(); ?>
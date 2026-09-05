<?php
$page = "Resign List";
$show_breadcrumb = true;

// Default Month and Year limits for filter
$defaultYear = date('Y');
$defaultMonth = date('m');
?>

<!-- Filter Section -->
<div class="row mb-3">
    <div class="col-12">
        <div class="filters card__wrapper p-3 bg-white rounded shadow-sm">
            <form method="GET" action="<?= $base_url ?>/payroll-resign-list" id="filterForm"
                class="row gx-3 gy-2 align-items-end">
                <!-- Filter Type -->
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label for="filterType" class="form-label fw-semibold text-muted mb-2">Select Filter Type</label>
                    <select class="form-select custom-select2" name="filter_type" id="filterType">
                        <option value="monthly" <?= $filter_type == 'monthly' ? 'selected' : '' ?>>Monthly</option>
                        <option value="custom" <?= $filter_type == 'custom' ? 'selected' : '' ?>>Custom Range</option>
                    </select>
                </div>

                <!-- Monthly Filter Container -->
                <div class="col-lg-3 col-md-4 col-sm-6" id="monthlyContainer"
                    style="<?= $filter_type == 'monthly' ? '' : 'display: none;' ?>">
                    <div class="row gx-2">
                        <div class="col-6">
                            <label for="filterMonth" class="form-label fw-semibold text-muted mb-2">Month</label>
                            <select class="form-select custom-select2" name="month" id="filterMonth">
                                <?php for ($m = 1; $m <= 12; $m++):
                                    $monthNum = str_pad($m, 2, "0", STR_PAD_LEFT);
                                    $monthName = date('F', mktime(0, 0, 0, $m, 1));
                                    ?>
                                    <option value="<?= $monthNum ?>" <?= $monthNum == str_pad($selected_month, 2, "0", STR_PAD_LEFT) ? 'selected' : '' ?>>
                                        <?= $monthName ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="filterYear" class="form-label fw-semibold text-muted mb-2">Year</label>
                            <select class="form-select custom-select2" name="year" id="filterYear">
                                <?php
                                $startYear = 2025;
                                $endYear = 2035;
                                for ($y = $startYear; $y <= $endYear; $y++): ?>
                                    <option value="<?= $y ?>" <?= $y == $selected_year ? 'selected' : '' ?>>
                                        <?= $y ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Custom Range Filter Container -->
                <div class="col-lg-4 col-md-4 col-sm-6" id="customContainer"
                    style="<?= $filter_type == 'custom' ? '' : 'display: none;' ?>">
                    <div class="row gx-2">
                        <div class="col-6">
                            <label for="fromDate" class="form-label fw-semibold text-muted mb-2">From Date</label>
                            <input type="date" class="form-control" name="from_date" id="fromDate"
                                value="<?= $from_date ?>">
                        </div>
                        <div class="col-6">
                            <label for="toDate" class="form-label fw-semibold text-muted mb-2">To Date</label>
                            <input type="date" class="form-control" name="to_date" id="toDate" value="<?= $to_date ?>">
                        </div>
                    </div>
                </div>

                <!-- Payroll Project Filter -->
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label for="payrollProject" class="form-label fw-semibold text-muted mb-2">Project (Optional)</label>
                    <select class="form-select custom-select2" name="payroll_project_id" id="payrollProject">
                        <option value="">All Projects</option>
                        <?php if (!empty($payroll_projects)): ?>
                            <?php foreach ($payroll_projects as $proj): ?>
                                <option value="<?= htmlspecialchars($proj['id']) ?>"
                                    data-date="<?= htmlspecialchars($proj['date'] ?? '') ?>"
                                    <?= (isset($payroll_project_id) && $payroll_project_id == $proj['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($proj['name']) ?> (<?= htmlspecialchars($proj['date'] ?? '') ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-auto">
                    <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center">
                        <i class="bi bi-search me-1"></i> Retrieve Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Resign List Table -->
<div class="row">
    <div class="col-xxl-12">
        
        <div class="card__wrapper border-0">
            <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                <h4 class="fw-bold text-dark mb-0">
                    <i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i>Resign List
                </h4>
                <div>
                    <button type="button" class="btn btn-success fw-semibold shadow-sm me-2" onclick="$('#resignListTable').DataTable().button('.buttons-csv').trigger();">
                        <i class="fa-solid fa-file-csv me-1"></i> Export CSV
                    </button>
                    <button type="button" class="btn btn-danger fw-semibold shadow-sm" onclick="$('#resignListTable').DataTable().button('.buttons-pdf').trigger();">
                        <i class="fa-solid fa-file-pdf me-1"></i> Export PDF
                    </button>
                </div>
            </div>
            <div class="table__wrapper table-responsive">
                <table id="resignListTable" class="table no-border-table mb-20">
                    <thead class="text-center text-nowrap align-middle">
                        <tr>
                            <th style="width: 5%">SL</th>
                            <th style="width: 10%">ID</th>
                            <th class="text-start" style="width: 18%">Name</th>
                            <th class="text-start" style="width: 25%">Department - Designation</th>
                            <th style="width: 12%">Workplace</th>
                            <th style="width: 12%">Last Working Day</th>
                            <th style="width: 8%">Status</th>
                            <th style="width: 10%">Salary</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($employees)): ?>
                            <?php $sl = 1;
                            foreach ($employees as $row): ?>
                                <tr class="text-center align-middle">
                                    <td>
                                        <?= $sl++ ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($row['employee_id']) ?>
                                    </td>
                                    <td class="text-start">
                                        <?= htmlspecialchars($row['full_name']) ?>
                                    </td>
                                    <td class="text-start">
                                        <?= htmlspecialchars(($row['department'] ?? '-') . ' - ' . ($row['designation'] ?? '-')) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($row['workplace'] ?? '-') ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($row['resign_date'] ?? '-') ?>
                                    </td>
                                    <td>
                                        <?php if (($row['employee_status'] ?? '') == 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php elseif (($row['employee_status'] ?? '') == 'resigned'): ?>
                                            <span class="badge bg-danger">Resigned</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <?= htmlspecialchars(ucfirst($row['employee_status'] ?? '-')) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars(number_format($row['gross_salary'] ?? 0, 2)) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form for PDF Export to Backend -->
<form id="exportPdfForm" action="<?= $GLOBALS['base_url'] ?>/payroll-resign-list-pdf" method="GET" target="_blank"
    style="display:none;">
    <input type="hidden" name="filter_type" id="pdfFilterType">
    <input type="hidden" name="month" id="pdfMonth">
    <input type="hidden" name="year" id="pdfYear">
    <input type="hidden" name="from_date" id="pdfFromDate">
    <input type="hidden" name="to_date" id="pdfToDate">
    <input type="hidden" name="payroll_project_id" id="pdfPayrollProject">
</form>

<?php ob_start(); ?>
document.addEventListener("DOMContentLoaded", function () {
    $('body').on('change', '#filterType', function() {
        if ($(this).val() === 'monthly') {
            document.getElementById('monthlyContainer').style.display = 'block';
            document.getElementById('customContainer').style.display = 'none';
        } else {
            document.getElementById('monthlyContainer').style.display = 'none';
            document.getElementById('customContainer').style.display = 'block';
        }
    });

    // Auto-sync Month & Year when Project is selected
    $('#payrollProject').on('change', function() {
        var selectedOpt = this.options[this.selectedIndex];
        if (selectedOpt && selectedOpt.value) {
            var dateVal = selectedOpt.getAttribute('data-date');
            if (dateVal) {
                var parts = dateVal.split('-');
                if (parts.length >= 2) {
                    $('#filterYear').val(parts[0]).trigger('change');
                    $('#filterMonth').val(parts[1]).trigger('change');
                    $('#filterType').val('monthly').trigger('change');
                }
            }
        }
        $('#filterForm').submit();
    });

    if ($.fn.DataTable.isDataTable('#resignListTable')) {
        $('#resignListTable').DataTable().destroy();
    }

    $('#resignListTable').DataTable({
        dom: '<"row align-items-center mb-3"<"col-sm-6 text-start"l><"col-sm-6 text-end"f><"d-none"B>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row align-items-center mt-3"<"col-sm-6 text-start"i><"col-sm-6 text-end"p>>',
        buttons: [
            {
                extend: 'csvHtml5', text: 'CSV', className: 'buttons-csv',
                title: 'Resign_List_Report'
            },
            {
                text: 'PDF', className: 'buttons-pdf',
                action: function (e, dt, node, config) {
                    document.getElementById('pdfFilterType').value = $('#filterType').val();
                    document.getElementById('pdfMonth').value = $('#filterMonth').val();
                    document.getElementById('pdfYear').value = $('#filterYear').val();
                    document.getElementById('pdfFromDate').value = $('#fromDate').val();
                    document.getElementById('pdfToDate').value = $('#toDate').val();
                    document.getElementById('pdfPayrollProject').value = $('#payrollProject').val();
                    document.getElementById('exportPdfForm').submit();
                }
            }
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search list..."
        },
        pagingType: "simple_numbers",
        pageLength: 50,
        ordering: true
    });
});
<?php
$page_script = ob_get_clean();
?>
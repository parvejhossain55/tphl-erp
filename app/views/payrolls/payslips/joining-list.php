<?php
$page = "Joining List";
$show_breadcrumb = true;

// Default Month and Year limits for filter
$defaultYear = date('Y');
$defaultMonth = date('m');
?>

<!-- Filter Section -->
<div class="row mb-3">
    <div class="col-12">
        <div class="filters card__wrapper p-3 bg-white rounded shadow-sm">
            <form id="filterForm" class="row gx-3 gy-2 align-items-end">
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
                                    <option value="<?= $monthNum ?>" <?= $monthNum == str_pad($selected_month ?? $defaultMonth, 2, "0", STR_PAD_LEFT) ? 'selected' : '' ?>>
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
                                    <option value="<?= $y ?>" <?= $y == ($selected_year ?? $defaultYear) ? 'selected' : '' ?>>
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
                                value="<?= $from_date ?? date('Y-m-d') ?>">
                        </div>
                        <div class="col-6">
                            <label for="toDate" class="form-label fw-semibold text-muted mb-2">To Date</label>
                            <input type="date" class="form-control" name="to_date" id="toDate"
                                value="<?= $to_date ?? date('Y-m-d') ?>">
                        </div>
                    </div>
                </div>

                <!-- Payroll Project Filter -->
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label for="payrollProject" class="form-label fw-semibold text-muted mb-2">Project
                        (Optional)</label>
                    <select class="form-select custom-select2" name="payroll_project_id" id="payrollProject">
                        <option value="">All Projects</option>
                        <?php if (!empty($payroll_projects)): ?>
                            <?php foreach ($payroll_projects as $proj): ?>
                                <option value="<?= htmlspecialchars($proj['id']) ?>"
                                    data-date="<?= htmlspecialchars($proj['date'] ?? '') ?>">
                                    <?= htmlspecialchars($proj['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-auto">
                    <button type="button" id="retrieveBtn"
                        class="btn btn-primary d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-cloud-arrow-down me-1"></i> <span>Retrieve Data</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Joining List Table -->
<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper border-0">
            <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                <h4 class="fw-bold text-dark mb-0">
                    <i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i>Joining List
                </h4>
                <div>
                    <button type="button" class="btn btn-success fw-semibold shadow-sm me-2"
                        onclick="$('#joiningListTable').DataTable().button('.buttons-csv').trigger();">
                        <i class="fa-solid fa-file-csv me-1"></i> Export CSV
                    </button>
                    <button type="button" class="btn btn-danger fw-semibold shadow-sm" onclick="exportPdf()">
                        <i class="fa-solid fa-file-pdf me-1"></i> Export PDF
                    </button>
                </div>
            </div>

            <div id="loader" class="text-center py-5" style="display: none;">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-muted fw-semibold">Syncing with Tipsoi API, please wait...</p>
            </div>

            <div class="table__wrapper table-responsive" id="tableWrapper">
                <table id="joiningListTable" class="table no-border-table mb-20">
                    <thead class="text-center text-nowrap align-middle">
                        <tr>
                            <th style="width: 5%">SL</th>
                            <th style="width: 10%">ID</th>
                            <th class="text-start" style="width: 18%">Name</th>
                            <th class="text-start" style="width: 20%">Department - Designation</th>
                            <th style="width: 13%">Workplace</th>
                            <th style="width: 12%">Joining Date</th>
                            <th style="width: 10%">Gross Salary</th>
                            <th style="width: 10%">Basic Salary</th>
                            <th style="width: 2%">Status</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <!-- Data will be loaded here via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form for PDF Export to Backend -->
<form id="exportPdfForm" action="<?= $base_url ?>/payroll-joining-list-pdf" method="GET" target="_blank"
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
// UI logic for toggling filter modes
$('body').on('change', '#filterType', function() {
if ($(this).val() === 'monthly') {
document.getElementById('monthlyContainer').style.display = 'block';
document.getElementById('customContainer').style.display = 'none';
} else {
document.getElementById('monthlyContainer').style.display = 'none';
document.getElementById('customContainer').style.display = 'block';
}
});

// Handle project select auto-fill month/year
$('#payrollProject').on('change', function() {
var selectedOpt = this.options[this.selectedIndex];
if (selectedOpt && selectedOpt.value) {
var dateVal = selectedOpt.getAttribute('data-date');
if (dateVal) {
var parts = dateVal.split('-');
if (parts.length >= 2) {
$('#filterYear').val(parts[0]).trigger('change');
$('#filterMonth').val(parts[1]).trigger('change');
$('#filterType').val('monthly').trigger('change'); // Auto-switch to monthly
}
}
}
});

// Initialize Empty DataTable
var dataTable = $('#joiningListTable').DataTable({
dom: '<"row align-items-center mb-3"<"col-sm-6 text-start"l><"col-sm-6 text-end"f><"d-none"B>>' +
            '<"row"<"col-sm-12"tr>>' +
                '<"row align-items-center mt-3"<"col-sm-6 text-start"i><"col-sm-6 text-end"p>>',
                        buttons: [
                        {
                        extend: 'csvHtml5', text: 'CSV', className: 'buttons-csv',
                        title: 'Joining_List_Report'
                        }
                        ],
                        language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search list...",
                        emptyTable: "Submit the form to fetch data from Tipsoi"
                        },
                        pagingType: "simple_numbers",
                        pageLength: 50,
                        ordering: true
                        });

                        // Handle Form Submit (Fetch Data)
                        $('#retrieveBtn').on('click', function(e) {
                        e.preventDefault();
                        fetchData();
                        });

                        function fetchData() {
                        var filterType = $('#filterType').val();
                        var fromDate = '';
                        var toDate = '';

                        if (filterType === 'monthly') {
                        var m = $('#filterMonth').val();
                        var y = $('#filterYear').val();
                        var lastDay = new Date(y, m, 0).getDate();
                        fromDate = y + '-' + String(m).padStart(2, '0') + '-01';
                        toDate = y + '-' + String(m).padStart(2, '0') + '-' + String(lastDay).padStart(2, '0');
                        } else {
                        fromDate = $('#fromDate').val();
                        toDate = $('#toDate').val();
                        }

                        var projectId = $('#payrollProject').val();

                        var params = new URLSearchParams({
                        from_date: fromDate,
                        to_date: toDate,
                        payroll_project_id: projectId
                        });

                        // Show Loader
                        $('#tableWrapper').hide();
                        $('#loader').show();
                        var btn = $('#retrieveBtn');
                        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i>
                        <span>Syncing...</span>');

                        fetch(`<?= $base_url ?>/api/hrm/joining-list-data?` + params.toString())
                        .then(r => r.json())
                        .then(res => {
                        $('#loader').hide();
                        $('#tableWrapper').show();
                        btn.prop('disabled', false).html('<i class="fa-solid fa-cloud-arrow-down me-1"></i>
                        <span>Retrieve Data</span>');

                        if (res.success) {
                        dataTable.clear();

                        if (res.data && res.data.length > 0) {
                        res.data.forEach(function(row) {
                        var deptDesig = (row.department || '-') + ' - ' + (row.designation || '-');

                        var statusBadge = row.id_check === 'OK'
                        ? '<span class="badge bg-success p-1"><i class="fa-solid fa-check"></i></span>'
                        : '<span class="badge bg-warning p-1" title="Missing in local Employee Database"><i
                                class="fa-solid fa-exclamation"></i></span>';

                        var gross = parseFloat(row.gross_salary || 0).toLocaleString('en-US',
                        {minimumFractionDigits:2});
                        var basic = parseFloat(row.basic_salary || 0).toLocaleString('en-US',
                        {minimumFractionDigits:2});

                        dataTable.row.add([
                        '<div class="text-center">' + row.sl + '</div>',
                        '<div class="text-center">' + row.employee_id + '</div>',
                        row.name,
                        deptDesig,
                        '<div class="text-center">' + row.workplace + '</div>',
                        '<div class="text-center">' + row.joining_date + '</div>',
                        '<div class="text-end">' + gross + '</div>',
                        '<div class="text-end">' + basic + '</div>',
                        '<div class="text-center">' + statusBadge + '</div>'
                        ]);
                        });
                        dataTable.draw();
                        Swal.fire({
                        icon: 'success',
                        title: 'Sync Complete',
                        text: 'Found ' + res.data.length + ' active employee records.',
                        toast: true,
                        position: 'bottom-end',
                        showConfirmButton: false,
                        timer: 3000
                        });
                        } else {
                        dataTable.draw(); // Draws empty
                        Swal.fire({
                        icon: 'info',
                        title: 'No Data',
                        text: 'No active employees found matching the filters.',
                        toast: true,
                        position: 'bottom-end',
                        showConfirmButton: false,
                        timer: 3000
                        });
                        }
                        } else {
                        Swal.fire('Error', res.message || 'Failed to sync with Tipsoi', 'error');
                        }
                        })
                        .catch(err => {
                        $('#loader').hide();
                        $('#tableWrapper').show();
                        btn.prop('disabled', false).html('<i class="fa-solid fa-cloud-arrow-down me-1"></i>
                        <span>Retrieve Data</span>');
                        Swal.fire('Error', 'Server connection failed', 'error');
                        });
                        }

                        // Bind PDF export to use the correct data
                        window.exportPdf = function() {
                        document.getElementById('pdfFilterType').value = $('#filterType').val();
                        document.getElementById('pdfMonth').value = $('#filterMonth').val();
                        document.getElementById('pdfYear').value = $('#filterYear').val();
                        document.getElementById('pdfFromDate').value = $('#fromDate').val();
                        document.getElementById('pdfToDate').value = $('#toDate').val();
                        document.getElementById('pdfPayrollProject').value = $('#payrollProject').val();
                        document.getElementById('exportPdfForm').submit();
                        };

                        // Auto-fetch data on load
                        fetchData();
                        });
                        <?php
                        $page_script = ob_get_clean();
                        ?>
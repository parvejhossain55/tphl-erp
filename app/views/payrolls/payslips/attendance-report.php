<?php
$page_title = 'Payroll Attendance Report';
$page = 'Payroll Attendance';
$show_breadcrumb = true;

// Selected project id fallback from GET
$selected_project_id = isset($selected_project_id) ? $selected_project_id : (isset($_GET['payroll_project_id']) && !empty($_GET['payroll_project_id']) ? (int)$_GET['payroll_project_id'] : null);

// Default Month and Year for Payroll format or from GET
$defaultYear = isset($_GET['year']) && !empty($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$defaultMonth = isset($_GET['month']) && !empty($_GET['month']) ? str_pad($_GET['month'], 2, '0', STR_PAD_LEFT) : date('m');

// Filter Type
$filterType = isset($_GET['filter_type']) && in_array($_GET['filter_type'], ['monthly', 'custom']) ? $_GET['filter_type'] : 'monthly';

// Attendance Status Filter
$selectedStatus = isset($_GET['attendance_status']) ? $_GET['attendance_status'] : 'all';

// Default date range
$default_from = isset($_GET['from_date']) && !empty($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d');
$default_to = isset($_GET['to_date']) && !empty($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
?>

<!-- Info Indicator Card -->
<div class="card__wrapper mb-20" id="apiStatusCard" style="display: none;">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-15">
        <div class="d-flex align-items-center gap-10">
            <i class="bi bi-info-circle-fill text-warning fs-4" id="apiStatusIcon"></i>
            <div>
                <h6 class="m-0 fw-bold" id="apiStatusTitle">Tipsoi API Status</h6>
                <small class="text-muted" id="apiStatusDesc">Establishing connection...</small>
            </div>
        </div>
        <span class="badge bg-warning px-3 py-2" id="apiModeBadge">Checking...</span>
    </div>
</div>

<!-- Filter Section -->
<div class="row mb-3">
    <div class="col-12">
        <div class="filters card__wrapper p-3 bg-white rounded shadow-sm">
            <form id="filterForm" class="row gx-3 gy-2 align-items-end">
                <!-- Filter Type -->
                <div class="col-lg-2 col-md-3 col-sm-6">
                    <label for="filterType" class="form-label fw-semibold text-muted mb-2">Select Filter Type</label>
                    <select class="form-select custom-select2" id="filterType">
                        <option value="monthly" <?= $filterType === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                        <option value="custom" <?= $filterType === 'custom' ? 'selected' : '' ?>>Custom Range</option>
                    </select>
                </div>

                <!-- Payroll Project -->
                <div class="col-lg-3 col-md-3 col-sm-6">
                    <label for="payrollProject" class="form-label fw-semibold text-muted mb-2">Payroll Project <span
                            class="text-danger">*</span></label>
                    <select class="form-select custom-select2" id="payrollProject" required>
                        <option value="">Select Project</option>
                        <?php if (isset($payroll_projects)): ?>
                            <?php foreach ($payroll_projects as $proj): ?>
                                <option value="<?= htmlspecialchars($proj['id']) ?>"
                                    data-date="<?= htmlspecialchars($proj['date'] ?? '') ?>"
                                    data-iscustom="<?= htmlspecialchars($proj['is_custom_date'] ?? '0') ?>"
                                    data-start="<?= htmlspecialchars($proj['start_date'] ?? '') ?>"
                                    data-end="<?= htmlspecialchars($proj['end_date'] ?? '') ?>"
                                    <?= (isset($selected_project_id) && $selected_project_id == $proj['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($proj['name']) ?> (<?= htmlspecialchars($proj['date'] ?? '') ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Monthly Filter Container -->
                <div class="col-lg-4 col-md-3 col-sm-6" id="monthlyContainer" style="<?= $filterType === 'custom' ? 'display: none;' : '' ?>">
                    <div class="row gx-2">
                        <div class="col-6">
                            <label for="filterMonth" class="form-label fw-semibold text-muted mb-2">Month</label>
                            <select class="form-select custom-select2" name="month" id="filterMonth">
                                <?php
                                for ($m = 1; $m <= 12; $m++):
                                    $monthNum = str_pad($m, 2, '0', STR_PAD_LEFT);
                                    $monthName = date('F', mktime(0, 0, 0, $m, 1));
                                    ?>
                                    <option value="<?= $monthNum ?>" <?= $monthNum == str_pad($defaultMonth, 2, '0', STR_PAD_LEFT) ? 'selected' : '' ?>>
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
                                for ($y = $startYear; $y <= $endYear; $y++):
                                    ?>
                                    <option value="<?= $y ?>" <?= $y == $defaultYear ? 'selected' : '' ?>>
                                        <?= $y ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Custom Range Filter Container -->
                <div class="col-lg-4 col-md-3 col-sm-6" id="customContainer" style="<?= $filterType === 'custom' ? '' : 'display: none;' ?>">
                    <div class="row gx-2">
                        <!-- From Date -->
                        <div class="col-6">
                            <label for="fromDate" class="form-label fw-semibold text-muted mb-2">From Date</label>
                            <input type="date" class="form-control" id="fromDate" value="<?= $default_from ?>">
                        </div>
                        <!-- To Date -->
                        <div class="col-6">
                            <label for="toDate" class="form-label fw-semibold text-muted mb-2">To Date</label>
                            <input type="date" class="form-control" id="toDate" value="<?= $default_to ?>">
                        </div>
                    </div>
                </div>

                <!-- Attendance Status Filter -->
                <div class="col-lg-3 col-md-3 col-sm-6">
                    <label for="filterAttendanceStatus" class="form-label fw-semibold text-muted mb-2">Attendance Status</label>
                    <select class="form-select custom-select2" id="filterAttendanceStatus">
                        <option value="all" <?= $selectedStatus === 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="late" <?= $selectedStatus === 'late' ? 'selected' : '' ?>>Late</option>
                        <option value="present" <?= $selectedStatus === 'present' ? 'selected' : '' ?>>Present</option>
                        <option value="absent" <?= $selectedStatus === 'absent' ? 'selected' : '' ?>>Absent</option>
                        <option value="leave" <?= $selectedStatus === 'leave' ? 'selected' : '' ?>>On Leave</option>
                        <option value="deduction" <?= $selectedStatus === 'deduction' ? 'selected' : '' ?>>Has Deduction</option>
                    </select>
                </div>

                <div class="col-auto d-none">
                    <button type="button" id="btnFilterSearch"
                        class="btn btn-primary d-flex align-items-center justify-content-center">
                        <i class="bi bi-search me-1"></i> Retrieve Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Report Table -->
<div class="row">
    <div class="col-xxl-12">

        <div class="card__wrapper border-0">
            <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                <h4 class="fw-bold text-dark mb-0">
                    <i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i>Attendance Report
                </h4>
                <div>
                    <button type="button" class="btn btn-primary fw-semibold shadow-sm me-2" id="btnSaveAttendance">
                        <i class="fa-solid fa-save me-1"></i> Save Data
                    </button>
                    <button type="button" class="btn btn-success fw-semibold shadow-sm me-2"
                        onclick="$('#payrollAttendanceTable').DataTable().button('.buttons-csv').trigger();">
                        <i class="fa-solid fa-file-csv me-1"></i> Export CSV
                    </button>
                    <button type="button" class="btn btn-danger fw-semibold shadow-sm" onclick="exportPdf()">
                        <i class="fa-solid fa-file-pdf me-1"></i> Export PDF
                    </button>
                </div>
            </div>
            <div class="table__wrapper table-responsive">
                <!-- Using a specific ID for DataTables to bind correctly -->
                <table id="payrollAttendanceTable" class="table no-border-table mb-20">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th style="min-width: 150px;">Employee Name</th>
                            <th>ID</th>
                            <th>Designation</th>
                            <th>Department</th>
                            <th>Workplace</th>
                            <th>Employment Type</th>
                            <th>Working Days</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Leave</th>
                            <th>Day Off</th>
                            <th>Late</th>
                            <th>Approved Lates</th>
                            <th>Final Late</th>
                            <th>Deduction</th>
                            <th>Late / Unpaid/Absent (Days)</th>
                            <th>Joining Date</th>
                            <th>Resign Date</th>
                            <th>Effective Date</th>
                            <th>Remarks</th>
                            <th>ID Check</th>
                            <th>Name Check</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceReportBody">
                        <tr>
                            <td colspan="23" class="text-center py-4 text-muted">Please select Month/Year and click
                                Retrieve Data.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form for PDF Export to Backend -->
<form id="exportPdfForm" action="<?= $GLOBALS['base_url'] ?>/payroll-attendance-pdf" method="GET" target="_blank"
    style="display:none;">
    <input type="hidden" name="from_date" id="pdfFromDate">
    <input type="hidden" name="to_date" id="pdfToDate">
    <input type="hidden" name="payroll_project_id" id="pdfPayrollProject">
    <input type="hidden" name="attendance_status" id="pdfAttendanceStatus">
</form>

<?php ob_start(); ?>
document.addEventListener("DOMContentLoaded", function () {
    const baseUrl = "<?= $GLOBALS['base_url'] ?>";
    const filterType = document.getElementById("filterType");
    const monthlyContainer = document.getElementById("monthlyContainer");
    const customContainer = document.getElementById("customContainer");
    const filterMonthInput = document.getElementById("filterMonth");
    const filterYearInput = document.getElementById("filterYear");
    const fromDateInput = document.getElementById("fromDate");
    const toDateInput = document.getElementById("toDate");
    const tableBody = document.getElementById("attendanceReportBody");

    const statusCard = document.getElementById("apiStatusCard");
    const statusIcon = document.getElementById("apiStatusIcon");
    const statusTitle = document.getElementById("apiStatusTitle");
    const statusDesc = document.getElementById("apiStatusDesc");
    const modeBadge = document.getElementById("apiModeBadge");

    let dataTableInstance = null;
    let activeFromDate = "";
    let activeToDate = "";
    let isFetching = false;

    // Helper functions from tipsoi integration map
    const normalizeId = (id) => {
        const str = String(id || '').trim();
        return /^\d+$/.test(str) ? String(parseInt(str, 10)) : str;
    };

    const parseTimeToMinutes = (timeStr) => {
        if (!timeStr || timeStr === '-' || timeStr === ' ' || timeStr.trim() === '') return null;
        const match = timeStr.trim().match(/^(\d+):(\d+)\s*(AM|PM)$/i);
        if (!match) return null;
        let hours = parseInt(match[1], 10);
        const minutes = parseInt(match[2], 10);
        const ampm = match[3].toUpperCase();
        if (ampm === 'PM' && hours !== 12) hours += 12;
        else if (ampm === 'AM' && hours === 12) hours = 0;
        return hours * 60 + minutes;
    };

    const checkIfLate = (firstLogTime, shiftStartTime) => {
        const checkInMin = parseTimeToMinutes(firstLogTime);
        if (checkInMin === null) return false;
        const shiftStartMin = parseTimeToMinutes(shiftStartTime) ?? 570;
        const lateThreshold = shiftStartMin + 10;
        return checkInMin > lateThreshold;
    };

    function showErrorRow(msg) {
        statusCard.style.display = "flex";
        statusCard.className = "card__wrapper mb-20 border-start border-danger border-4";
        statusIcon.className = "bi bi-exclamation-circle-fill text-danger fs-4";
        statusTitle.textContent = "Tipsoi API Error";
        statusDesc.textContent = msg;
        modeBadge.className = "badge bg-danger text-light px-3 py-2";
        modeBadge.textContent = "Error";

        if (typeof $ !== 'undefined' && $.fn.DataTable && $.fn.DataTable.isDataTable('#payrollAttendanceTable')) {
            $('#payrollAttendanceTable').DataTable().clear().destroy();
            dataTableInstance = null;
        }

        tableBody.innerHTML = `
        <tr>
            <td colspan="23" class="text-center py-5 text-danger fw-bold">
                <i class="bi bi-exclamation-triangle-fill fs-2 mb-2 d-block"></i>
                ${msg}
            </td>
        </tr>
        `;
    }

    // Initialize datatables
    function initDataTable() {
        if (typeof $ !== 'undefined' && $.fn.DataTable) {
            if ($.fn.DataTable.isDataTable('#payrollAttendanceTable')) {
                $('#payrollAttendanceTable').DataTable().clear().destroy();
                dataTableInstance = null;
            }

            // Clean un-populated rows before init
            if (!tableBody.querySelector('tr[data-populated]')) {
                return;
            }

            dataTableInstance = $('#payrollAttendanceTable').DataTable({
                destroy: true,
                dom: '<"row align-items-center mb-3"<"col-sm-6 text-start"l><"col-sm-6 text-end"f><"d-none"B>>' +
                     '<"row"<"col-sm-12"tr>>' +
                     '<"row align-items-center mt-3"<"col-sm-6 text-start"i><"col-sm-6 text-end"p>>',
                buttons: [
                    {
                        extend: 'csvHtml5', text: 'CSV', className: 'buttons-csv',
                        title: 'Payroll_Attendance_Report_' + activeFromDate + '_to_' + activeToDate,
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
                        text: 'PDF', className: 'buttons-pdf',
                        action: function (e, dt, node, config) {
                            window.exportPdf();
                        }
                    }
                ],
                responsive: false,
                scrollX: true,
                autoWidth: false,
                pageLength: 50,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                columnDefs: [{
                    searchable: false,
                    orderable: false,
                    targets: 0
                }],
                order: [[1, 'asc']]
            });

            // Re-apply attendance status filter if one is selected
            dataTableInstance.draw();
        }
    }

    // Custom DataTables Filter for Attendance Status
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (!settings.nTable || settings.nTable.id !== 'payrollAttendanceTable') {
                return true;
            }
            const statusSelect = document.getElementById('filterAttendanceStatus');
            const filterStatus = statusSelect ? statusSelect.value : 'all';
            if (!filterStatus || filterStatus === 'all') {
                return true;
            }

            const tr = settings.aoData[dataIndex] ? settings.aoData[dataIndex].nTr : null;
            let present = 0, absent = 0, leave = 0, late = 0, approvedLates = 0, finalLate = 0, deduction = 0, lateUnpaid = 0;

            if (tr) {
                present = parseFloat(tr.querySelector('.in-present')?.value) || 0;
                absent = parseFloat(tr.querySelector('.in-absent')?.value) || 0;
                leave = parseFloat(tr.querySelector('.in-leave')?.value) || 0;
                late = parseFloat(tr.querySelector('.in-late')?.value) || 0;
                approvedLates = parseFloat(tr.querySelector('.in-approvedlates')?.value) || 0;
                finalLate = parseFloat(tr.querySelector('.in-finallate')?.value) || 0;
                deduction = parseFloat(tr.querySelector('.in-deduction')?.value) || 0;
                lateUnpaid = parseFloat(tr.querySelector('.in-lateunpaidabsent')?.value) || 0;
            } else if (data) {
                const parseVal = (str) => {
                    const m = String(str || '').match(/value=["']?([^"'>\s]+)/i);
                    return m ? parseFloat(m[1]) || 0 : parseFloat(str) || 0;
                };
                present = parseVal(data[8]);
                absent = parseVal(data[9]);
                leave = parseVal(data[10]);
                late = parseVal(data[12]);
                approvedLates = parseVal(data[13]);
                finalLate = parseVal(data[14]);
                deduction = parseVal(data[15]);
                lateUnpaid = parseVal(data[16]);
            }

            if (filterStatus === 'late') {
                return (late > 0 || approvedLates > 0 || finalLate > 0);
            }
            if (filterStatus === 'present') {
                return (present > 0 && absent === 0 && late === 0 && approvedLates === 0 && finalLate === 0);
            }
            if (filterStatus === 'absent') {
                return (absent > 0);
            }
            if (filterStatus === 'leave') {
                return (leave > 0);
            }
            if (filterStatus === 'deduction') {
                return (deduction > 0 || lateUnpaid > 0);
            }

            return true;
        });
    }

    // Attendance status change handler
    $('#filterAttendanceStatus').on('change', function() {
        if (dataTableInstance) {
            dataTableInstance.draw();
        }
        try {
            const currentUrl = new URL(window.location.href);
            const statusVal = this.value || 'all';
            if (statusVal !== 'all') {
                currentUrl.searchParams.set('attendance_status', statusVal);
            } else {
                currentUrl.searchParams.delete('attendance_status');
            }
            window.history.replaceState(null, '', currentUrl.toString());
        } catch (e) {}
    });

    // Toggle logic
    filterType.addEventListener("change", function () {
        if (this.value === 'monthly') {
            monthlyContainer.style.display = 'block';
            customContainer.style.display = 'none';
        } else {
            monthlyContainer.style.display = 'none';
            customContainer.style.display = 'block';
        }
        fetchAttendanceData();
    });

    // Trigger API fetch and compilation
    function fetchAttendanceData() {
        if (isFetching) return;
        isFetching = true;

        const projId = document.getElementById("payrollProject").value;

        // Determine boundaries
        if (filterType.value === 'monthly') {
            const m = filterMonthInput.value;
            const y = filterYearInput.value;
            const dateStrFrom = `${y}-${String(parseInt(m, 10)).padStart(2, '0')}-01`;
            const dateStrTo = `${y}-${String(parseInt(m, 10)).padStart(2, '0')}-${String(new Date(y, parseInt(m, 10), 0).getDate()).padStart(2, '0')}`;
            activeFromDate = dateStrFrom;
            activeToDate = dateStrTo;
        } else {
            activeFromDate = fromDateInput.value;
            activeToDate = toDateInput.value;
        }

        // Sync browser URL so refreshing (F5) keeps the active filters intact
        try {
            const currentUrl = new URL(window.location.href);
            if (projId) {
                currentUrl.searchParams.set('payroll_project_id', projId);
            } else {
                currentUrl.searchParams.delete('payroll_project_id');
            }
            currentUrl.searchParams.set('filter_type', filterType.value);
            if (filterType.value === 'monthly') {
                currentUrl.searchParams.set('month', filterMonthInput.value);
                currentUrl.searchParams.set('year', filterYearInput.value);
                currentUrl.searchParams.delete('from_date');
                currentUrl.searchParams.delete('to_date');
            } else {
                currentUrl.searchParams.set('from_date', activeFromDate);
                currentUrl.searchParams.set('to_date', activeToDate);
                currentUrl.searchParams.delete('month');
                currentUrl.searchParams.delete('year');
            }
            const statusVal = $('#filterAttendanceStatus').val() || 'all';
            if (statusVal !== 'all') {
                currentUrl.searchParams.set('attendance_status', statusVal);
            } else {
                currentUrl.searchParams.delete('attendance_status');
            }
            window.history.replaceState(null, '', currentUrl.toString());
            sessionStorage.setItem('payroll_att_filter_params', currentUrl.search);
        } catch (e) {
            console.warn('URL sync failed:', e);
        }

        // Show spinner safely
        if (typeof $ !== 'undefined' && $.fn.DataTable && $.fn.DataTable.isDataTable('#payrollAttendanceTable')) {
            $('#payrollAttendanceTable').DataTable().clear().destroy();
            dataTableInstance = null;
        }

        tableBody.innerHTML = `
        <tr>
            <td colspan="23" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="text-muted mt-2">Aggregating attendance over ${activeFromDate} to ${activeToDate} from Tipsoi...</div>
            </td>
        </tr>
        `;

        const params = new URLSearchParams({
            from_date: activeFromDate,
            to_date: activeToDate,
            payroll_project_id: projId
        });

        Promise.all([
            fetch(`${baseUrl}/api/hrm/attendance?${params.toString()}`).then(r => r.json()),
            fetch(`${baseUrl}/api/payroll/attendance-overrides?project_id=${projId}`).then(r => r.json())
        ])
        .then(([res, overridesRes]) => {
            isFetching = false;

            if (res && res.success) {
                statusCard.style.display = "flex";
                statusCard.className = "card__wrapper mb-20 border-start border-success border-4";
                statusIcon.className = "bi bi-check-circle-fill text-success fs-4";
                statusTitle.textContent = "API Synchronization Success";
                statusDesc.textContent = "Attendance mapped against Tipsoi accurately.";
                modeBadge.className = "badge bg-success text-light px-3 py-2";
                modeBadge.textContent = "Aggregated";

                const rawData = res.data || [];
                const localEmployees = res.local_employees || [];
                const dbOverrides = (overridesRes && overridesRes.success) ? overridesRes.data : {};
                const customHolidays = res.custom_holidays || { global: {}, department: {}, employee: {} };

                let aggregatedRows = "";
                let index = 1;

                let targetEmployees = localEmployees;

                targetEmployees.forEach(localMatch => {
                    const leId = normalizeId(localMatch.employee_id);
                    const leName = String(localMatch.full_name || '').toLowerCase().trim();

                    // Find matching emp in Tipsoi data
                    const emp = rawData.find(e => {
                        const officeId = normalizeId(e.employeeOfficeId || e.employeeId);
                        const nameNormalized = String(e.employeeName || '').toLowerCase().trim();
                        return (officeId && leId === officeId) || (nameNormalized && leName === nameNormalized);
                    });

                    const attMap = emp ? (emp.attendance || {}) : {};
                    const shiftStartTime = emp ? (emp.shiftStartTime || '') : '';
                    const summ = emp ? (emp.summary || {}) : {};

                    // Count approved lates directly from daily records where status is Accepted / Approved
                    let approvedLates = 0;
                    Object.keys(attMap).forEach(timestamp => {
                        const dayAtt = attMap[timestamp];
                        const lateStatus = String(dayAtt.lateApprovalStatus || '').toLowerCase().trim();
                        if (lateStatus === 'accepted' || lateStatus === 'approved') {
                            approvedLates++;
                        }
                    });

                    // Fallback from summary if daily status missing
                    if (approvedLates === 0 && summ.totalApprovedLateApplication) {
                        approvedLates = Math.round(parseInt(summ.totalApprovedLateApplication, 10) / 2);
                    }

                    // Use official Tipsoi summary figures if available, otherwise fallback to daily loop
                    let present = (summ.totalPresent !== undefined) ? parseInt(summ.totalPresent, 10) : 0;
                    let absent = (summ.totalAbsent !== undefined) ? parseInt(summ.totalAbsent, 10) : 0;
                    let leave = (summ.totalLeave !== undefined) ? parseFloat(summ.totalLeave) : 0;
                    let dayOff = (summ.totalWeeklyOffdays !== undefined) ? parseInt(summ.totalWeeklyOffdays, 10) : 0;
                    if (summ.totalHolidays) {
                        dayOff += parseInt(summ.totalHolidays, 10);
                    }

                    // Align Custom Holidays (Global, Department-wise, or Employee-wise)
                    const empIdNum = parseInt(localMatch.id, 10);
                    const empIdStr = String(localMatch.id || '');
                    const deptIdNum = parseInt(localMatch.department_id, 10);
                    const deptIdStr = String(localMatch.department_id || '');

                    const getCustomHolidayTitle = (dayDate) => {
                        if (!dayDate) return null;
                        if (customHolidays.employee) {
                            if (customHolidays.employee[empIdNum] && customHolidays.employee[empIdNum][dayDate]) return customHolidays.employee[empIdNum][dayDate];
                            if (customHolidays.employee[empIdStr] && customHolidays.employee[empIdStr][dayDate]) return customHolidays.employee[empIdStr][dayDate];
                        }
                        if (customHolidays.department) {
                            if (customHolidays.department[deptIdNum] && customHolidays.department[deptIdNum][dayDate]) return customHolidays.department[deptIdNum][dayDate];
                            if (customHolidays.department[deptIdStr] && customHolidays.department[deptIdStr][dayDate]) return customHolidays.department[deptIdStr][dayDate];
                        }
                        if (customHolidays.global && customHolidays.global[dayDate]) {
                            return customHolidays.global[dayDate];
                        }
                        return null;
                    };

                    const normalizeToYmd = (dateVal, tsKey) => {
                        if (dateVal) {
                            const s = String(dateVal).trim();
                            const m1 = s.match(/^(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{4})/);
                            if (m1) return `${m1[3]}-${m1[2].padStart(2, '0')}-${m1[1].padStart(2, '0')}`;
                            const m2 = s.match(/^(\d{4})[.\/-](\d{1,2})[.\/-](\d{1,2})/);
                            if (m2) return `${m2[1]}-${m2[2].padStart(2, '0')}-${m2[3].padStart(2, '0')}`;
                        }
                        if (tsKey) {
                            const n = parseInt(tsKey, 10);
                            if (!isNaN(n) && n > 0) {
                                const sec = n > 9999999999 ? Math.floor(n / 1000) : n;
                                const d = new Date((sec + 6 * 3600) * 1000);
                                return d.toISOString().substring(0, 10);
                            }
                        }
                        return '';
                    };

                    let appliedCustomHolidayCount = 0;

                    Object.keys(attMap).forEach(timestamp => {
                        const dayAtt = attMap[timestamp];
                        const dayDate = normalizeToYmd(dayAtt.date || dayAtt.attendanceDate, timestamp);
                        if (!dayDate) return;

                        const customTitle = getCustomHolidayTitle(dayDate);
                        if (customTitle) {
                            dayAtt.isCustomHoliday = true;
                            dayAtt.customHolidayTitle = customTitle;

                            const isTipsoiOff = (dayAtt.isOffday == 1 || dayAtt.isOffday === true || dayAtt.isHoliday == 1 || dayAtt.isHoliday === true);
                            const isPresent = (dayAtt.isPresent == 1 || dayAtt.isPresent === true || (dayAtt.firstLoggedTime && String(dayAtt.firstLoggedTime).trim() !== '-'));
                            const isLeave = (dayAtt.isLeave == 1 || dayAtt.isLeave === true);

                            if (!isTipsoiOff) {
                                if (!isPresent && !isLeave) {
                                    dayAtt.isHoliday = true;
                                    dayAtt.isAbsent = false;
                                    absent = Math.max(0, absent - 1);
                                    dayOff += 1;
                                    appliedCustomHolidayCount++;
                                } else if (isPresent) {
                                    dayAtt.isHolidayPresent = true;
                                }
                            }
                        }
                    });

                    const totalPeriodDays = Object.keys(attMap).length || 30;
                    let workingDays = Math.max(0, totalPeriodDays - dayOff);

                    // If summary is empty (e.g. offline/fallback), calculate manually from attMap
                    if (!emp || !emp.summary) {
                        workingDays = 0;
                        present = 0;
                        absent = 0;
                        leave = 0;
                        dayOff = 0;

                        Object.keys(attMap).forEach(timestamp => {
                            const dayAtt = attMap[timestamp];
                            workingDays++;

                            if (dayAtt.isLeave) {
                                leave += (dayAtt.isHalfDay ? 0.5 : 1);
                            } else if (dayAtt.isPresent) {
                                present++;
                            } else if (dayAtt.isOffday || dayAtt.isHoliday) {
                                dayOff++;
                                workingDays--;
                            } else {
                                absent++;
                            }
                        });
                    }

                    // For site staff who worked on scheduled off days, adjust workingDays and dayOff accordingly
                    if ((present + absent + leave) > workingDays) {
                        workingDays = Math.min(totalPeriodDays, Math.round(present + absent + leave));
                        dayOff = Math.max(0, totalPeriodDays - workingDays);
                    }

                    // Tipsoi's totalLateEntry is the official net unapproved late count
                    let finalLate = (summ.totalLateEntry !== undefined)
                        ? parseInt(summ.totalLateEntry, 10)
                        : Math.max(0, 0);

                    // Raw Late = Approved Lates + Final Unapproved Late
                    let late = approvedLates + finalLate;

                    let deduction = Math.floor(finalLate / 3);
                    let lateUnpaidAbsentDays = parseFloat((absent + deduction).toFixed(2));
                    let idCheck = emp ? "OK" : "Tipsoi Missing";
                    let nameCheck = emp ? "OK" : "Tipsoi Missing";

                    let empType = "Regular";
                    let rowEmpId = localMatch.id;

                    let displayJoinDate = localMatch.joining_date || (emp ? emp.dateOfJoin : '-');

                    // Apply DB Overrides if they exist
                    if (rowEmpId && dbOverrides[rowEmpId]) {
                        const override = dbOverrides[rowEmpId];
                        workingDays = override.working_days ?? workingDays;
                        present = override.present ?? present;
                        absent = override.absent ?? absent;
                        leave = override.leave_days ?? leave;
                        dayOff = override.dayoff ?? dayOff;
                        late = override.late ?? late;
                        approvedLates = override.approved_lates ?? approvedLates;
                        finalLate = override.final_late ?? finalLate;
                        deduction = override.deduction ?? deduction;
                        lateUnpaidAbsentDays = override.late_unpaid_absent_days ?? lateUnpaidAbsentDays;
                    }

                    aggregatedRows += `
                    <tr data-populated="true" class="att-record-row" data-employee-id="${rowEmpId}">
                        <td>${index++}</td>
                        <td class="fw-bold">${localMatch.full_name || (emp ? emp.employeeName : '-')}</td>
                        <td>${localMatch.employee_id || (emp ? emp.employeeOfficeId : '-')}</td>
                        <td>${localMatch.designation_name || (emp ? emp.designationName : '-')}</td>
                        <td>${localMatch.department_name || (emp ? emp.departmentName : '-')}</td>
                        <td>${emp ? emp.workplaceName : '-'}</td>
                        <td>${empType}</td>
                        <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-workingdays" value="${workingDays}" style="min-width:70px;"></td>
                        <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-present fw-bold text-success" value="${present}" style="min-width:70px;"></td>
                        <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-absent text-danger" value="${absent}" style="min-width:70px;"></td>
                        <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-leave" value="${leave}" style="min-width:70px;"></td>
                        <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-dayoff" value="${dayOff}" style="min-width:70px;"></td>
                        <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-late text-warning" value="${late}" style="min-width:70px;"></td>
                        <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-approvedlates" value="${approvedLates}" style="min-width:70px;"></td>
                        <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-finallate" value="${finalLate}" style="min-width:70px;"></td>
                        <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-deduction" value="${deduction}" style="min-width:70px;"></td>
                        <td><input type="number" step="0.01" class="form-control form-control-sm text-end in-lateunpaidabsent" value="${lateUnpaidAbsentDays}" style="min-width:70px;"></td>
                        <td>${displayJoinDate}</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td><span class="badge ${idCheck === 'OK' ? 'bg-success' : 'bg-danger'}">${idCheck}</span></td>
                        <td><span class="badge ${nameCheck === 'OK' ? 'bg-success' : 'bg-danger'}">${nameCheck}</span></td>
                    </tr>
                    `;
                });

                if (typeof $ !== 'undefined' && $.fn.DataTable && $.fn.DataTable.isDataTable('#payrollAttendanceTable')) {
                    $('#payrollAttendanceTable').DataTable().clear().destroy();
                    dataTableInstance = null;
                }

                if (targetEmployees.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="23" class="text-center py-4">No records found for this period.</td></tr>`;
                } else {
                    tableBody.innerHTML = aggregatedRows;
                    initDataTable();
                }

            } else {
                showErrorRow((res && res.message) || "Failed to fetch Tipsoi records.");
            }
        })
        .catch(err => {
            isFetching = false;
            console.error("API Fetch Error:", err);
            showErrorRow("Network or API error occurred while fetching attendance data.");
        });
    }

    // Bind auto-fetch triggers for custom dates
    fromDateInput.addEventListener('change', fetchAttendanceData);
    toDateInput.addEventListener('change', fetchAttendanceData);

    // Bind auto-fetch for manual month/year selection
    $('#filterYear, #filterMonth').on('change', function() {
        fetchAttendanceData();
    });

    // Save data logic
    document.getElementById('btnSaveAttendance').addEventListener('click', function() {
        const projectId = document.getElementById('payrollProject').value;
        if (!projectId) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Project',
                text: 'Please select a Payroll Project first.'
            });
            return;
        }

        if (!dataTableInstance) {
            Swal.fire({
                icon: 'info',
                title: 'No Data',
                text: 'No data available to save.'
            });
            return;
        }

        const rows = [];
        const nodes = dataTableInstance.rows().nodes();
        $(nodes).each(function() {
            const tr = $(this);
            const employeeId = tr.data('employee-id');
            if (employeeId) {
                rows.push({
                    employee_id: employeeId,
                    working_days: tr.find('.in-workingdays').val() || 0,
                    present: tr.find('.in-present').val() || 0,
                    absent: tr.find('.in-absent').val() || 0,
                    leave: tr.find('.in-leave').val() || 0,
                    dayoff: tr.find('.in-dayoff').val() || 0,
                    late: tr.find('.in-late').val() || 0,
                    approved_lates: tr.find('.in-approvedlates').val() || 0,
                    final_late: tr.find('.in-finallate').val() || 0,
                    deduction: tr.find('.in-deduction').val() || 0,
                    late_unpaid_absent_days: tr.find('.in-lateunpaidabsent').val() || 0
                });
            }
        });

        if (rows.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Match',
                text: 'No matched employees found in the table to save.'
            });
            return;
        }

        this.disabled = true;
        this.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...`;

        const tmDate = new Date(activeFromDate);
        const targetMonthStr = tmDate.getFullYear() + "-" + String(tmDate.getMonth() + 1).padStart(2, '0') + "-01";

        fetch(`${baseUrl}/save-attendance-modifications`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                project_id: projectId,
                target_month: targetMonthStr,
                rows: rows
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Saved!',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Save Failed',
                    text: data.message
                });
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire({
                icon: 'error',
                title: 'Network Error',
                text: 'A network error occurred while saving.'
            });
        })
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="fa-solid fa-save me-1"></i> Save Data';
        });
    });

    // Interactive Manual Helper for Attendance calculations
    document.addEventListener('input', function(e) {
        const tr = e.target.closest('tr');
        if (!tr) return;

        const lateVal = parseFloat(tr.querySelector('.in-late')?.value) || 0;
        const appLateInp = tr.querySelector('.in-approvedlates');
        const finalLateInp = tr.querySelector('.in-finallate');
        const deductInp = tr.querySelector('.in-deduction');
        const absentInp = tr.querySelector('.in-absent');
        const lateUnpaidInp = tr.querySelector('.in-lateunpaidabsent');

        if (e.target.classList.contains('in-approvedlates') || e.target.classList.contains('in-late')) {
            const appLateVal = parseFloat(appLateInp?.value) || 0;
            const finalLate = Math.max(0, lateVal - appLateVal);
            if (finalLateInp) {
                finalLateInp.value = finalLate;
            }
            const deduction = Math.floor(finalLate / 3);
            if (deductInp) {
                deductInp.value = deduction;
            }
            const absentVal = parseFloat(absentInp?.value) || 0;
            if (lateUnpaidInp) {
                lateUnpaidInp.value = (absentVal + deduction).toFixed(2);
            }
        } else if (e.target.classList.contains('in-finallate')) {
            const finalLate = parseFloat(finalLateInp?.value) || 0;
            const deduction = Math.floor(finalLate / 3);
            if (deductInp) {
                deductInp.value = deduction;
            }
            const absentVal = parseFloat(absentInp?.value) || 0;
            if (lateUnpaidInp) {
                lateUnpaidInp.value = (absentVal + deduction).toFixed(2);
            }
        } else if (e.target.classList.contains('in-deduction') || e.target.classList.contains('in-absent')) {
            const absentVal = parseFloat(absentInp?.value) || 0;
            const deductVal = parseFloat(deductInp?.value) || 0;
            if (lateUnpaidInp) {
                lateUnpaidInp.value = (absentVal + deductVal).toFixed(2);
            }
        }
    });

    // Project Select Change Handler
    const projectSelect = document.getElementById('payrollProject');
    if (projectSelect) {
        $(projectSelect).on('change', function() {
            const selectedOpt = this.options[this.selectedIndex];
            if (selectedOpt && selectedOpt.value) {
                const isCustom = selectedOpt.getAttribute('data-iscustom') === '1';

                if (isCustom) {
                    filterType.value = 'custom';
                    monthlyContainer.style.display = 'none';
                    customContainer.style.display = 'block';
                    fromDateInput.value = selectedOpt.getAttribute('data-start') || '';
                    toDateInput.value = selectedOpt.getAttribute('data-end') || '';
                } else {
                    filterType.value = 'monthly';
                    monthlyContainer.style.display = 'block';
                    customContainer.style.display = 'none';
                    const dateVal = selectedOpt.getAttribute('data-date');
                    if (dateVal) {
                        const parts = dateVal.split('-');
                        if (parts.length >= 2) {
                            filterYearInput.value = parts[0];
                            filterMonthInput.value = parts[1];
                        }
                    }
                }
                fetchAttendanceData();
            }
        });
    }

    // Global PDF export trigger
    window.exportPdf = function() {
        const projId = document.getElementById("payrollProject") ? document.getElementById("payrollProject").value : '';
        const statusVal = document.getElementById("filterAttendanceStatus") ? document.getElementById("filterAttendanceStatus").value : 'all';
        document.getElementById('pdfFromDate').value = activeFromDate || fromDateInput.value;
        document.getElementById('pdfToDate').value = activeToDate || toDateInput.value;
        document.getElementById('pdfPayrollProject').value = projId;
        document.getElementById('pdfAttendanceStatus').value = statusVal;
        document.getElementById('exportPdfForm').submit();
    };

    // Initial load
    const urlParams = new URLSearchParams(window.location.search);
    const hasExplicitUrlParams = urlParams.has('month') || urlParams.has('from_date');

    if (projectSelect && projectSelect.value) {
        if (!hasExplicitUrlParams) {
            $(projectSelect).trigger('change');
        } else {
            fetchAttendanceData();
        }
    } else {
        fetchAttendanceData();
    }
});
<?php
$page_script = ob_get_clean();
?>
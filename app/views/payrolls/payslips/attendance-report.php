<?php
$page_title = "Payroll Attendance Report";
$page = "Payroll Attendance";
$show_breadcrumb = true;

// Default Month and Year for Payroll format
$defaultYear = date('Y');
$defaultMonth = date('m');

// Default date range: today
$default_from = date('Y-m-d');
$default_to = date('Y-m-d');
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
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label for="filterType" class="form-label fw-semibold text-muted mb-2">Select Filter Type</label>
                    <select class="form-select custom-select2" id="filterType">
                        <option value="monthly">Monthly</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>

                <!-- Monthly Filter Container -->
                <div class="col-lg-6 col-md-6 col-sm-12" id="monthlyContainer">
                    <div class="row gx-2">
                        <div class="col-6">
                            <label for="filterMonth" class="form-label fw-semibold text-muted mb-2">Month</label>
                            <select class="form-select custom-select2" name="month" id="filterMonth">
                                <?php for ($m = 1; $m <= 12; $m++):
                                    $monthNum = str_pad($m, 2, "0", STR_PAD_LEFT);
                                    $monthName = date('F', mktime(0, 0, 0, $m, 1));
                                    ?>
                                    <option value="<?= $monthNum ?>" <?= $monthNum == str_pad($defaultMonth, 2, "0", STR_PAD_LEFT) ? 'selected' : '' ?>>
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
                                    <option value="<?= $y ?>" <?= $y == $defaultYear ? 'selected' : '' ?>>
                                        <?= $y ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Custom Range Filter Container -->
                <div class="col-lg-6 col-md-6 col-sm-12" id="customContainer" style="display: none;">
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

                <div class="col-auto">
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
                    <button type="button" class="btn btn-success fw-semibold shadow-sm me-2" onclick="$('#payrollAttendanceTable').DataTable().button('.buttons-csv').trigger();">
                        <i class="fa-solid fa-file-csv me-1"></i> Export CSV
                    </button>
                    <button type="button" class="btn btn-danger fw-semibold shadow-sm" onclick="$('#payrollAttendanceTable').DataTable().button('.buttons-pdf').trigger();">
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
const btnFilterSearch = document.getElementById("btnFilterSearch");
const tableBody = document.getElementById("attendanceReportBody");

const statusCard = document.getElementById("apiStatusCard");
const statusIcon = document.getElementById("apiStatusIcon");
const statusTitle = document.getElementById("apiStatusTitle");
const statusDesc = document.getElementById("apiStatusDesc");
const modeBadge = document.getElementById("apiModeBadge");

let dataTableInstance = null;
let activeFromDate = "";
let activeToDate = "";

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

if (dataTableInstance) {
dataTableInstance.clear().destroy();
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
if (dataTableInstance) {
dataTableInstance.destroy();
}

// Clean un-populated rows before init
if (!tableBody.querySelector('tr[data-populated]')) {
// don't init over empty
return;
}

dataTableInstance = $('#payrollAttendanceTable').DataTable({
dom: '<"row align-items-center mb-3"<"col-sm-6 text-start"l><"col-sm-6 text-end"f><"d-none"B>>' +
        '<"row"<"col-sm-12"tr>>' +
            '<"row align-items-center mt-3"<"col-sm-6 text-start"i><"col-sm-6 text-end"p>>',
                    buttons: [
                    {
                    extend: 'csvHtml5', text: 'CSV', className: 'buttons-csv',
                    title: 'Payroll_Attendance_Report_' + activeFromDate + '_to_' + activeToDate
                    },
                    {
                    text: 'PDF', className: 'buttons-pdf',
                    action: function (e, dt, node, config) {
                    document.getElementById('pdfFromDate').value = activeFromDate;
                    document.getElementById('pdfToDate').value = activeToDate;
                    document.getElementById('exportPdfForm').submit();
                    }
                    }
                    ],
                    responsive: false, // Turn off responsive collapsing for 23 columns
                    scrollX: true,
                    autoWidth: false,
                    pageLength: 50,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]]
                    });
                    }
                    }

                    // Toggle logic
                    filterType.addEventListener("change", function(e) {
                    if (this.value === 'monthly') {
                    monthlyContainer.style.display = 'block';
                    customContainer.style.display = 'none';
                    } else {
                    monthlyContainer.style.display = 'none';
                    customContainer.style.display = 'block';
                    }
                    });

                    // Trigger API fetch and compilation
                    btnFilterSearch.addEventListener("click", function (e) {
                    e.preventDefault();

                    // Determine boundaries
                    if (filterType.value === 'monthly') {
                    const m = filterMonthInput.value;
                    const y = filterYearInput.value;
                    const dateStrFrom = new Date(y, m - 1, 1).toISOString().substring(0, 10);
                    const dateStrTo = new Date(y, m, 0).toISOString().substring(0, 10);
                    activeFromDate = dateStrFrom;
                    activeToDate = dateStrTo;
                    } else {
                    activeFromDate = fromDateInput.value;
                    activeToDate = toDateInput.value;
                    }

                    // Show spinner
                    if (dataTableInstance) {
                    dataTableInstance.destroy();
                    dataTableInstance = null;
                    }

                    tableBody.innerHTML = `
                    <tr>
                        <td colspan="23" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="text-muted mt-2">Aggregating attendance over ${activeFromDate} to
                                ${activeToDate} from Tipsoi...</div>
                        </td>
                    </tr>
                    `;

                    const params = new URLSearchParams({
                    from_date: activeFromDate,
                    to_date: activeToDate
                    });

                    fetch(`${baseUrl}/api/hrm/attendance?${params.toString()}`)
                    .then(res => res.json())
                    .then(res => {
                    if (res.success) {
                    statusCard.style.display = "flex";
                    statusCard.className = "card__wrapper mb-20 border-start border-success border-4";
                    statusIcon.className = "bi bi-check-circle-fill text-success fs-4";
                    statusTitle.textContent = "API Synchronization Success";
                    statusDesc.textContent = "Attendance mapped against Tipsoi accurately.";
                    modeBadge.className = "badge bg-success text-light px-3 py-2";
                    modeBadge.textContent = "Aggregated";

                    const rawData = res.data || [];
                    const localEmployees = res.local_employees || [];

                    let aggregatedRows = "";
                    let index = 1;

                    rawData.forEach(emp => {
                    const attMap = emp.attendance || {};
                    const officeId = normalizeId(emp.employeeOfficeId || emp.employeeId);
                    const nameNormalized = String(emp.employeeName || '').toLowerCase().trim();

                    const localMatch = localEmployees.find(le => {
                    const leId = normalizeId(le.employee_id);
                    const leName = String(le.full_name || '').toLowerCase().trim();
                    return (officeId && leId === officeId) || (nameNormalized && leName === nameNormalized);
                    });

                    // Stats accumulation
                    let workingDays = 0;
                    let present = 0;
                    let absent = 0;
                    let leave = 0;
                    let dayOff = 0;
                    let late = 0;

                    const shiftStartTime = emp.shiftStartTime || '';

                    Object.keys(attMap).forEach(timestamp => {
                    const dayAtt = attMap[timestamp];
                    workingDays++; // Wait, what if it's a holiday/offday? Usually workingdays = present + absent +
                    leave

                    const isLateOverride = dayAtt.isPresent && checkIfLate(dayAtt.firstLoggedTime, shiftStartTime);

                    if (dayAtt.isLeave) {
                    leave++;
                    } else if (dayAtt.isPresent) {
                    present++;
                    if (isLateOverride) late++;
                    } else if (dayAtt.isOffday || dayAtt.isHoliday) {
                    dayOff++;
                    workingDays--; // Typically offdays don't strictly count as "Working Days" if unpresent.
                    } else {
                    absent++;
                    }
                    });

                    // Calculations
                    let finalLate = late; // Without HR overrides
                    let approvedLates = 0;
                    let deduction = 0;
                    let lateUnpaidAbsentDays = absent; // Custom logic
                    let idCheck = localMatch ? "OK" : "Mismatch";
                    let nameCheck = localMatch ? "OK" : "Mismatch";

                    // Employment Type (Pull from backend if linked later, here defaulting)
                    let empType = "Regular";

                    aggregatedRows += `
                    <tr data-populated="true">
                        <td>${index++}</td>
                        <td class="fw-bold">${emp.employeeName || '-'}</td>
                        <td>${emp.employeeOfficeId || '-'}</td>
                        <td>${emp.designationName || '-'}</td>
                        <td>${emp.departmentName || '-'}</td>
                        <td>${emp.workplaceName || '-'}</td>
                        <td>${empType}</td>
                        <td>${workingDays}</td>
                        <td class="fw-bold text-success">${present}</td>
                        <td class="text-danger">${absent}</td>
                        <td>${leave}</td>
                        <td>${dayOff}</td>
                        <td class="text-warning">${late}</td>
                        <td>${approvedLates}</td>
                        <td>${finalLate}</td>
                        <td>${deduction}</td>
                        <td>${lateUnpaidAbsentDays}</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td><span class="badge ${idCheck === 'OK' ? 'bg-success' : 'bg-danger'}">${idCheck}</span></td>
                        <td><span class="badge ${nameCheck === 'OK' ? 'bg-success' : 'bg-danger'}">${nameCheck}</span>
                        </td>
                    </tr>
                    `;
                    });

                    if(rawData.length === 0) {
                    tableBody.innerHTML = `<tr>
                        <td colspan="23" class="text-center py-4">No records found for this period.</td>
                    </tr>`;
                    } else {
                    tableBody.innerHTML = aggregatedRows;
                    initDataTable();
                    }

                    } else {
                    showErrorRow(res.message || "Failed to fetch Tipsoi records.");
                    }
                    })
                    .catch(err => {
                    console.error(err);
                    showErrorRow(err.message || "An unexpected network error occurred.");
                    });

                    });

                    // Trigger search automatically on load
                    btnFilterSearch.click();
                    });
                    <?php
                    $page_script = ob_get_clean();
                    ?>
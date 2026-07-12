<?php
$page_title = "Tipsoi Attendance Dashboard";
$page = 'Tipsoi Attendance';
$show_breadcrumb = true;

// Default date range: today
$default_from = date('Y-m-d');
$default_to = date('Y-m-d');
?>

<style>
.bg-light-primary { background-color: rgba(13, 110, 253, 0.08) !important; }
.bg-light-success { background-color: rgba(25, 135, 84, 0.08) !important; }
.bg-light-danger { background-color: rgba(220, 53, 69, 0.08) !important; }
.bg-light-warning { background-color: rgba(255, 193, 7, 0.08) !important; }
.bg-light-info { background-color: rgba(13, 202, 240, 0.08) !important; }

.text-primary-dark { color: #0d6efd !important; }
.text-success-dark { color: #198754 !important; }
.text-danger-dark { color: #dc3545 !important; }
.text-warning-dark { color: #ffc107 !important; }
.text-info-dark { color: #0dcaf0 !important; }

.stats-card {
    transition: all 0.25s ease;
    border: 1px solid rgba(0,0,0,0.05);
}
.stats-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.08) !important;
}
.stats-card.active-filter {
    border-width: 2px !important;
    transform: translateY(-3px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.12) !important;
}
</style>

<!-- Info Indicator Card -->
<div class="card__wrapper mb-20" id="apiStatusCard" style="display: none;">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-15">
        <div class="d-flex align-items-center gap-10">
            <i class="bi bi-info-circle-fill text-warning fs-4" id="apiStatusIcon"></i>
            <div>
                <h6 class="m-0 fw-bold " id="apiStatusTitle">Tipsoi API Status</h6>
                <small class="text-muted" id="apiStatusDesc">Establishing connection...</small>
            </div>
        </div>
        <span class="badge bg-warning  px-3 py-2" id="apiModeBadge">Checking...</span>
    </div>
</div>

<!-- Stats Cards Heading -->
<div class="d-flex align-items-center justify-content-between mb-15">
    <h6 class="text-muted text-uppercase fw-bold m-0" style="font-size: 0.8rem; letter-spacing: 0.5px;" id="statsHeaderLabel">
        Attendance Overview
    </h6>
    <span class="badge  text-dark border px-2 py-1" id="statsDateBadge">...</span>
</div>

<!-- Stats Cards Row -->
<div class="row g-20 mb-25">
    <!-- Total Present -->
    <div class="col-lg-3 col-md-6 col-12">
        <div class="card__wrapper d-flex align-items-center justify-content-between p-20 border-start border-success border-4 shadow-sm stats-card" style="cursor: pointer;" id="cardPresent">
            <div>
                <h6 class="text-muted text-uppercase mb-2 fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Total Present</h6>
                <h2 class="fw-bold text-success mb-0" id="statPresent">0</h2>
                <small class="text-muted">Click to view present</small>
            </div>
            <div class="bg-light-success p-3 rounded-circle text-success-dark d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                <i class="bi bi-person-check-fill fs-4"></i>
            </div>
        </div>
    </div>
    <!-- Total Late -->
    <div class="col-lg-3 col-md-6 col-12">
        <div class="card__wrapper d-flex align-items-center justify-content-between p-20 border-start border-warning border-4 shadow-sm stats-card" style="cursor: pointer;" id="cardLate">
            <div>
                <h6 class="text-muted text-uppercase mb-2 fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Total Late</h6>
                <h2 class="fw-bold text-warning mb-0" id="statLate">0</h2>
                <small class="text-muted">Click to view late</small>
            </div>
            <div class="bg-light-warning p-3 rounded-circle text-warning-dark d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                <i class="bi bi-clock-fill fs-4"></i>
            </div>
        </div>
    </div>
    <!-- Total Absent -->
    <div class="col-lg-3 col-md-6 col-12">
        <div class="card__wrapper d-flex align-items-center justify-content-between p-20 border-start border-danger border-4 shadow-sm stats-card" style="cursor: pointer;" id="cardAbsent">
            <div>
                <h6 class="text-muted text-uppercase mb-2 fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Total Absent</h6>
                <h2 class="fw-bold text-danger mb-0" id="statAbsent">0</h2>
                <small class="text-muted">Click to view absent</small>
            </div>
            <div class="bg-light-danger p-3 rounded-circle text-danger-dark d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                <i class="bi bi-person-x-fill fs-4"></i>
            </div>
        </div>
    </div>
    <!-- Total On Leave -->
    <div class="col-lg-3 col-md-6 col-12">
        <div class="card__wrapper d-flex align-items-center justify-content-between p-20 border-start border-info border-4 shadow-sm stats-card" style="cursor: pointer;" id="cardLeave">
            <div>
                <h6 class="text-muted text-uppercase mb-2 fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Total On Leave</h6>
                <h2 class="fw-bold text-info mb-0" id="statLeave">0</h2>
                <small class="text-muted">Click to view leaves</small>
            </div>
            <div class="bg-light-info p-3 rounded-circle text-info-dark d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                <i class="bi bi-calendar2-check-fill fs-4"></i>
            </div>
        </div>
    </div>
</div>

<!-- Main Dashboard Card -->
<div class="card__wrapper">
    <div class="d-flex align-items-center justify-content-between mb-20 flex-wrap gap-10">
        <h5 class="m-0 fw-bold text-dark">Tipsoi Daily Attendance Report</h5>
        <div class="d-flex align-items-center gap-10">
            <span class="badge bg-primary px-3 py-2 rounded-pill fs-7" id="totalCountBadge">Total Records: 0</span>
        </div>
    </div>

    <!-- Advanced Filter Bar -->
    <form id="filterForm" class="row g-15 mb-20 align-items-end">
        <!-- From Date -->
        <div class="col-lg-2 col-md-4 col-6">
            <div class="from__input-box">
                <label for="fromDate" class="form-label fw-semibold text-muted mb-2">From Date</label>
                <input type="date" class="form-control" id="fromDate" value="<?= $default_from ?>">
            </div>
        </div>
        <!-- To Date -->
        <div class="col-lg-2 col-md-4 col-6">
            <div class="from__input-box">
                <label for="toDate" class="form-label fw-semibold text-muted mb-2">To Date</label>
                <input type="date" class="form-control" id="toDate" value="<?= $default_to ?>">
            </div>
        </div>
        <!-- Search Query -->
        <div class="col-lg-3 col-md-4 col-12">
            <div class="from__input-box">
                <label for="searchQuery" class="form-label fw-semibold text-muted mb-2">Search Employee</label>
                <input type="text" class="form-control" id="searchQuery" placeholder="Search by name, email, ID...">
            </div>
        </div>
        <!-- Department Filter -->
        <div class="col-lg-2 col-md-4 col-6">
            <div class="from__input-box">
                <label for="departmentFilter" class="form-label fw-semibold text-muted mb-2">Department</label>
                <select class="form-control" id="departmentFilter">
                    <option value="">All Departments</option>
                </select>
            </div>
        </div>
        <!-- Status Filter -->
        <div class="col-lg-2 col-md-4 col-6">
            <div class="from__input-box">
                <label for="statusFilter" class="form-label fw-semibold text-muted mb-2">Attendance Status</label>
                <select class="form-control" id="statusFilter">
                    <option value="">All Statuses</option>
                    <option value="present">Present</option>
                    <option value="late">Late</option>
                    <option value="absent">Absent</option>
                    <option value="leave">On Leave</option>
                    <option value="offday">Weekend</option>
                    <option value="holiday">Holiday</option>
                </select>
            </div>
        </div>
        <!-- Search Button -->
        <div class="col-lg-1 col-md-12 col-12">
            <button type="button" id="btnFilterSearch" class="btn btn-primary w-100 py-2 d-flex align-items-center justify-content-center">
                <i class="bi bi-search me-1"></i> Search
            </button>
        </div>
    </form>

    <!-- Export Buttons -->
    <div class="d-flex justify-content-end align-items-center gap-2 mb-20">
        <button type="button" id="btnExportCSV" class="btn btn-sm btn-outline-success px-3 py-2 fw-semibold d-flex align-items-center justify-content-center">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
        </button>
        <button type="button" id="btnExportPDF" class="btn btn-sm btn-outline-danger px-3 py-2 fw-semibold d-flex align-items-center justify-content-center">
            <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
        </button>
    </div>

    <!-- Table Section -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="min-width: 1000px;">
            <thead class="table-light text-secondary">
                <tr>
                    <th style="width: 60px;">SN</th>
                    <th>Date</th>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th>Punch In</th>
                    <th>Punch Out</th>
                    <th>Late Duration</th>
                    <th>Work Hours</th>
                    <th>Location</th>
                    <th style="width: 140px;">Status</th>
                </tr>
            </thead>
            <tbody id="attendanceTableBody">
                <!-- Data will load dynamically -->
            </tbody>
        </table>
    </div>

    <!-- Pagination Section -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-15 mt-20 pt-20 border-top">
        <div class="text-muted fs-7" id="paginationSummary">
            Showing 0 to 0 of 0 entries
        </div>
        <nav aria-label="Page navigation">
            <ul class="pagination pagination-sm m-0" id="paginationControls">
                <!-- Pagination buttons will load dynamically -->
            </ul>
        </nav>
    </div>
</div>

<?php
// Custom javascript page script
ob_start();
?>
document.addEventListener("DOMContentLoaded", function () {
    const baseUrl = "<?= $GLOBALS['base_url'] ?>";
    let currentPage = 1;
    const limit = 15;

    // Data Holders
    let allRawAttendance = [];
    let allRecords = [];
    let filteredRecords = [];
    let localEmployeeIds = [];
    let localEmployeeEmails = [];
    let localEmployees = [];
    let normalizedLocalIds = [];
    let normalizedLocalEmails = [];
    let activeCardFilter = ""; // "present", "late", "absent", "leave"

    // DOM Elements
    const tableBody = document.getElementById("attendanceTableBody");
    const totalBadge = document.getElementById("totalCountBadge");
    const summaryText = document.getElementById("paginationSummary");
    const paginationUl = document.getElementById("paginationControls");

    const fromDateInput = document.getElementById("fromDate");
    const toDateInput = document.getElementById("toDate");
    const searchQuery = document.getElementById("searchQuery");
    const departmentFilter = document.getElementById("departmentFilter");
    const statusFilter = document.getElementById("statusFilter");
    const btnFilterSearch = document.getElementById("btnFilterSearch");

    // Stats Elements
    const statPresent = document.getElementById("statPresent");
    const statLate = document.getElementById("statLate");
    const statAbsent = document.getElementById("statAbsent");
    const statLeave = document.getElementById("statLeave");
    const statsDateBadge = document.getElementById("statsDateBadge");

    const cardPresent = document.getElementById("cardPresent");
    const cardLate = document.getElementById("cardLate");
    const cardAbsent = document.getElementById("cardAbsent");
    const cardLeave = document.getElementById("cardLeave");

    // Helper to normalize IDs (remove leading zeros for numeric IDs)
    const normalizeId = (id) => {
        const str = String(id || '').trim();
        return /^\d+$/.test(str) ? String(parseInt(str, 10)) : str;
    };

    // Helper to parse time string like "09:30 AM" into minutes since midnight
    const parseTimeToMinutes = (timeStr) => {
        if (!timeStr || timeStr === '-' || timeStr === ' ' || timeStr.trim() === '') return null;
        
        const match = timeStr.trim().match(/^(\d+):(\d+)\s*(AM|PM)$/i);
        if (!match) return null;
        
        let hours = parseInt(match[1], 10);
        const minutes = parseInt(match[2], 10);
        const ampm = match[3].toUpperCase();
        
        if (ampm === 'PM' && hours !== 12) {
            hours += 12;
        } else if (ampm === 'AM' && hours === 12) {
            hours = 0;
        }
        
        return hours * 60 + minutes;
    };

    // Helper to check if a punch-in time is late (after shiftStartTime + 10 minutes)
    const checkIfLate = (firstLogTime, shiftStartTime) => {
        const checkInMin = parseTimeToMinutes(firstLogTime);
        if (checkInMin === null) return false;
        
        // Default shift start time is 09:30 AM (570 minutes) if not specified
        const shiftStartMin = parseTimeToMinutes(shiftStartTime) ?? 570; 
        const lateThreshold = shiftStartMin + 10;
        
        return checkInMin > lateThreshold;
    };

    // Helper to calculate late duration relative to shiftStartTime + 10 minutes
    const calculateLateBy = (firstLogTime, shiftStartTime) => {
        const checkInMin = parseTimeToMinutes(firstLogTime);
        if (checkInMin === null) return '—';
        
        const shiftStartMin = parseTimeToMinutes(shiftStartTime) ?? 570;
        const lateThreshold = shiftStartMin + 10;
        
        if (checkInMin <= lateThreshold) {
            return '—';
        }
        
        const diff = checkInMin - lateThreshold;
        const diffHours = Math.floor(diff / 60);
        const diffMins = diff % 60;
        
        if (diffHours > 0) {
            return `${diffHours} hr ${diffMins} min`;
        } else {
            return `${diffMins} min`;
        }
    };

    // API Indicator Elements
    const statusCard = document.getElementById("apiStatusCard");
    const statusIcon = document.getElementById("apiStatusIcon");
    const statusTitle = document.getElementById("apiStatusTitle");
    const statusDesc = document.getElementById("apiStatusDesc");
    const modeBadge = document.getElementById("apiModeBadge");

    // Fetch and Load Data
    function fetchAttendanceData() {
        const fromDate = fromDateInput.value;
        const toDate = toDateInput.value;

        // Show Spinner
        tableBody.innerHTML = `
            <tr>
                <td colspan="11" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="text-muted mt-2">Fetching attendance logs from Tipsoi API...</div>
                </td>
            </tr>
        `;

        const params = new URLSearchParams({
            from_date: fromDate,
            to_date: toDate
        });

        fetch(`${baseUrl}/api/hrm/attendance?${params.toString()}`)
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                // Update Connection Stats Info Card
                statusCard.style.display = "block";
                statusCard.className = "card__wrapper mb-20 border-start border-success border-4";
                statusIcon.className = "bi bi-check-circle-fill text-success fs-4";
                statusTitle.textContent = "Tipsoi API Status: Connected";
                statusDesc.textContent = "Attendance data synchronized successfully with Bearer Token validation.";
                modeBadge.className = "badge bg-success text-light px-3 py-2";
                modeBadge.textContent = "Live Sync Mode";

                allRawAttendance = res.data || [];
                localEmployeeIds = res.local_employee_ids || [];
                localEmployeeEmails = res.local_employee_emails || [];
                localEmployees = res.local_employees || [];
                normalizedLocalIds = localEmployeeIds.map(id => normalizeId(id));
                normalizedLocalEmails = localEmployeeEmails.map(email => email.toLowerCase().trim());

                // Process raw attendance array into flat daily records
                processRecords(allRawAttendance);

                // Populate Department Dropdown
                populateDepartmentFilter(allRawAttendance);

                // Apply Filters and Render Table (this will automatically call calculateStats internally)
                applyFiltersAndRender(1);
            } else {
                showErrorRow(res.message || "Failed to fetch data.");
            }
        })
        .catch(err => {
            console.error(err);
            showErrorRow(err.message || "An unexpected network error occurred.");
        });
    }

    // Process nested response into flat daily records
    function processRecords(data) {
        allRecords = [];
        data.forEach(emp => {
            const attMap = emp.attendance || {};
            
            // Find local match to resolve email
            const officeId = normalizeId(emp.employeeOfficeId || emp.employeeId);
            const nameNormalized = String(emp.employeeName || '').toLowerCase().trim();
            const localMatch = localEmployees.find(le => {
                const leId = normalizeId(le.employee_id);
                const leName = String(le.full_name || '').toLowerCase().trim();
                return (officeId && leId === officeId) || (nameNormalized && leName === nameNormalized);
            });
            const email = localMatch ? localMatch.email : '';

            const shiftStartTime = emp.shiftStartTime || '';

            Object.keys(attMap).forEach(timestamp => {
                const dayAtt = attMap[timestamp];
                
                // Determine raw attendance status based on shiftStartTime + 10 minutes late override
                const isLateOverride = dayAtt.isPresent && checkIfLate(dayAtt.firstLoggedTime, shiftStartTime);
                let status = "absent";
                if (dayAtt.isLeave) {
                    status = "leave";
                } else if (dayAtt.isPresent) {
                    status = isLateOverride ? "late" : "present";
                } else if (dayAtt.isOffday || dayAtt.isHoliday) {
                    status = "offday";
                }

                allRecords.push({
                    employeeId: emp.employeeId,
                    employeeName: emp.employeeName,
                    employeeOfficeId: emp.employeeOfficeId,
                    email: email,
                    departmentName: emp.departmentName || 'No Department',
                    designationName: emp.designationName || 'N/A',
                    workplaceName: emp.workplaceName || 'N/A',
                    shiftStartTime: shiftStartTime,
                    siteLatitude: dayAtt.siteLatitude,
                    siteLongitude: dayAtt.siteLongitude,
                    siteInPictureUrl: dayAtt.siteInPictureUrl,
                    siteOutPictureUrl: dayAtt.siteOutPictureUrl,
                    timestamp: parseInt(timestamp, 10),
                    dateStr: dayAtt.date, // e.g. "06.07.2026 (Mon)"
                    firstLoggedTime: dayAtt.firstLoggedTime || '-',
                    lastLoggedTime: dayAtt.lastLoggedTime || '-',
                    totalHour: dayAtt.totalHour || '-',
                    isLate: isLateOverride,
                    isEarly: dayAtt.isEarly || false,
                    isHoliday: dayAtt.isHoliday || false,
                    isOffday: dayAtt.isOffday || false,
                    isLeave: dayAtt.isLeave || false,
                    isPresent: dayAtt.isPresent || false,
                    lateBy: isLateOverride ? calculateLateBy(dayAtt.firstLoggedTime, shiftStartTime) : '—',
                    status: status
                });
            });
        });

        // Sort: Latest date first, then by employee name
        allRecords.sort((a, b) => {
            if (b.timestamp !== a.timestamp) {
                return b.timestamp - a.timestamp;
            }
            return a.employeeName.localeCompare(b.employeeName);
        });
    }

    // Calculate Stats Cards for the selected range dynamically based on active filters
    function calculateStats() {
        const searchVal = searchQuery.value.toLowerCase().trim();
        const deptVal = departmentFilter.value;

        // Set date badge
        const fromVal = fromDateInput.value;
        const toVal = toDateInput.value;
        statsDateBadge.textContent = (fromVal === toVal) ? fromVal : `${fromVal} to ${toVal}`;

        let present = 0;
        let absent = 0;
        let late = 0;
        let leave = 0;

        allRecords.forEach(rec => {
            // Apply name search and department filter
            const name = String(rec.employeeName || '').toLowerCase();
            const officeId = String(rec.employeeOfficeId || '').toLowerCase();
            const email = String(rec.email || '').toLowerCase();

            const matchesSearch = !searchVal || 
                                  name.includes(searchVal) || 
                                  officeId.includes(searchVal) || 
                                  email.includes(searchVal);
            
            const matchesDept = !deptVal || rec.departmentName === deptVal;

            if (!matchesSearch || !matchesDept) {
                return; // Skip this record if it doesn't match active search or department
            }

            if (rec.isLeave) {
                leave++;
            } else if (rec.isPresent) {
                present++;
                if (rec.isLate) {
                    late++;
                }
            } else if (rec.status === 'absent') {
                absent++;
            }
        });

        statPresent.textContent = present;
        statLate.textContent = late;
        statAbsent.textContent = absent;
        statLeave.textContent = leave;
    }

    // Populate Department Filter Dropdown
    function populateDepartmentFilter(employees) {
        const depts = new Set();
        employees.forEach(emp => {
            if (emp.departmentName) {
                depts.add(emp.departmentName.trim());
            }
        });

        const sortedDepts = Array.from(depts).sort();
        departmentFilter.innerHTML = '<option value="">All Departments</option>';
        sortedDepts.forEach(dept => {
            const opt = document.createElement('option');
            opt.value = dept;
            opt.textContent = dept;
            departmentFilter.appendChild(opt);
        });
    }

    // Apply Filter Actions and Render Table
    function applyFiltersAndRender(page = 1) {
        currentPage = page;
        
        // Recalculate stats dynamically based on filters
        calculateStats();

        const searchVal = searchQuery.value.toLowerCase().trim();
        const deptVal = departmentFilter.value;
        const statusVal = statusFilter.value || activeCardFilter;

        filteredRecords = allRecords.filter(rec => {
            // 1. Search Query (matches name, email, office id)
            const name = String(rec.employeeName || '').toLowerCase();
            const officeId = String(rec.employeeOfficeId || '').toLowerCase();
            const email = String(rec.email || '').toLowerCase();
            const matchesSearch = !searchVal || 
                                  name.includes(searchVal) || 
                                  officeId.includes(searchVal) || 
                                  email.includes(searchVal);

            // 2. Department filter
            const matchesDept = !deptVal || rec.departmentName === deptVal;

            // 3. Status filter
            let matchesStatus = true;
            if (statusVal === 'present') {
                matchesStatus = rec.isPresent === true;
            } else if (statusVal === 'late') {
                matchesStatus = rec.isPresent === true && rec.isLate === true;
            } else if (statusVal === 'absent') {
                matchesStatus = rec.status === 'absent';
            } else if (statusVal === 'leave') {
                matchesStatus = rec.isLeave === true;
            } else if (statusVal === 'offday') {
                matchesStatus = rec.isOffday === true;
            } else if (statusVal === 'holiday') {
                matchesStatus = rec.isHoliday === true;
            }

            return matchesSearch && matchesDept && matchesStatus;
        });

        totalBadge.textContent = `Total Records: ${filteredRecords.length}`;
        renderTable(filteredRecords);
        renderPagination(filteredRecords.length, currentPage, limit);
    }

    // Render Table Rows
    function renderTable(records) {
        const offset = (currentPage - 1) * limit;
        const pageItems = records.slice(offset, offset + limit);

        if (pageItems.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="11" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-2 mb-2 d-block"></i>
                        No attendance records found matching filters.
                    </td>
                </tr>
            `;
            return;
        }

        let sn = offset + 1;
        tableBody.innerHTML = pageItems.map(rec => {
            let statusBadge = "";
            if (rec.isLeave) {
                statusBadge = '<span class="badge bg-info text-white px-2 py-1"><i class="bi bi-calendar2-check-fill me-1"></i>On Leave</span>';
            } else if (rec.isPresent) {
                let suffix = "";
                if (rec.isOffday) {
                    suffix = '<small class="text-secondary d-block mt-1 fw-semibold" style="font-size:0.68rem;"><i class="bi bi-calendar-event me-1"></i>Weekend Work</small>';
                } else if (rec.isHoliday) {
                    suffix = '<small class="text-secondary d-block mt-1 fw-semibold" style="font-size:0.68rem;"><i class="bi bi-calendar-check me-1"></i>Holiday Work</small>';
                }
                statusBadge = rec.isLate
                    ? '<span class="badge bg-warning  px-2 py-1"><i class="bi bi-exclamation-circle-fill me-1"></i>Late</span>' + suffix
                    : '<span class="badge bg-success text-white px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>Present</span>' + suffix;
            } else if (rec.isOffday) {
                statusBadge = '<span class="badge bg-warning text-light px-2 py-1"><i class="bi bi-calendar-event me-1"></i>Weekend</span>';
            } else if (rec.isHoliday) {
                statusBadge = '<span class="badge bg-secondary text-light px-2 py-1"><i class="bi bi-calendar-check me-1"></i>Holiday</span>';
            } else {
                statusBadge = '<span class="badge bg-danger text-light px-2 py-1"><i class="bi bi-person-x-fill me-1"></i>Absent</span>';
            }

            // ERP registered indicator
            const officeId = normalizeId(rec.employeeOfficeId);
            const empEmail = String(rec.email || '').toLowerCase().trim();
            const isRegistered = (officeId && normalizedLocalIds.includes(officeId)) ||
                                 (empEmail && normalizedLocalEmails.includes(empEmail));
            
            const localIndicator = isRegistered
                ? '<i class="bi bi-patch-check-fill text-success ms-1" title="Registered in ERP DB"></i>'
                : '<i class="bi bi-patch-question text-muted ms-1" title="Not in ERP DB"></i>';

            const checkIn = rec.firstLoggedTime === '-' ? '—' : `<span class="fw-semibold text-success">${rec.firstLoggedTime}</span>`;
            const checkOut = rec.lastLoggedTime === '-' || rec.lastLoggedTime === ' ' ? '—' : `<span class="fw-semibold text-secondary">${rec.lastLoggedTime}</span>`;

            // Location & Selfie indicator
            let locationHtml = "—";
            if (rec.siteLatitude && rec.siteLongitude) {
                const mapsUrl = `https://www.google.com/maps/search/?api=1&query=${rec.siteLatitude},${rec.siteLongitude}`;
                locationHtml = `<a href="${mapsUrl}" target="_blank" class="badge bg-light text-danger border border-danger-subtle text-decoration-none" title="Latitude: ${rec.siteLatitude}, Longitude: ${rec.siteLongitude}">
                    <i class="bi bi-geo-alt-fill me-1"></i>GPS Map
                </a>`;
            } else if (rec.workplaceName && rec.workplaceName !== 'N/A' && rec.workplaceName !== 'No Workplace') {
                locationHtml = `<span class="badge  text-secondary border" title="Physical Device Location"><i class="bi bi-cpu me-1"></i>${rec.workplaceName}</span>`;
            }

            let selfieHtml = "";
            if (rec.siteInPictureUrl && rec.siteInPictureUrl !== 'null' && rec.siteInPictureUrl.trim() !== '') {
                selfieHtml += `<a href="${rec.siteInPictureUrl}" target="_blank" class="text-primary ms-2" title="View In-Punch Selfie"><i class="bi bi-camera-fill"></i></a>`;
            }
            if (rec.siteOutPictureUrl && rec.siteOutPictureUrl !== 'null' && rec.siteOutPictureUrl.trim() !== '') {
                selfieHtml += `<a href="${rec.siteOutPictureUrl}" target="_blank" class="text-secondary ms-2" title="View Out-Punch Selfie"><i class="bi bi-camera-fill"></i></a>`;
            }

            return `
                <tr>
                    <td class="text-muted fw-semibold">${sn++}</td>
                    <td class="fw-semibold text-dark">${rec.dateStr}</td>
                    <td class="fw-semibold text-muted">${rec.employeeOfficeId || '—'}</td>
                    <td class="fw-bold">${rec.employeeName} ${localIndicator}</td>
                    <td>${rec.departmentName}</td>
                    <td><span class="badge text-dark border">${rec.designationName}</span></td>
                    <td>${checkIn}</td>
                    <td>${checkOut}</td>
                    <td class="text-danger fw-semibold">${rec.lateBy !== '-' ? rec.lateBy : '—'}</td>
                    <td class="text-muted fw-semibold">${rec.totalHour !== '-' ? rec.totalHour : '—'}</td>
                    <td>${locationHtml}${selfieHtml}</td>
                    <td>${statusBadge}</td>
                </tr>
            `;
        }).join('');
    }

    // Render Pagination Controls
    function renderPagination(total, page, limit) {
        const totalPages = Math.ceil(total / limit);
        const startRecord = total > 0 ? ((page - 1) * limit) + 1 : 0;
        const endRecord = Math.min(startRecord + limit - 1, total);

        summaryText.innerHTML = `Showing <strong>${startRecord}</strong> to <strong>${endRecord}</strong> of <strong>${total}</strong> entries`;

        if (totalPages <= 1) {
            paginationUl.innerHTML = '';
            return;
        }

        let paginationHtml = '';

        // Previous button
        paginationHtml += `
            <li class="page-item ${page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${page - 1}"><i class="bi bi-chevron-left"></i></a>
            </li>
        `;

        // Page buttons
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= page - 2 && i <= page + 2)) {
                paginationHtml += `
                    <li class="page-item ${page === i ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            } else if (i === page - 3 || i === page + 3) {
                paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        // Next button
        paginationHtml += `
            <li class="page-item ${page === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${page + 1}"><i class="bi bi-chevron-right"></i></a>
            </li>
        `;

        paginationUl.innerHTML = paginationHtml;

        // Click handlers
        paginationUl.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const targetPage = parseInt(this.getAttribute('data-page'), 10);
                if (targetPage && targetPage !== page) {
                    applyFiltersAndRender(targetPage);
                }
            });
        });
    }

    // Interactive Stats Cards Handlers
    function setupStatsCardListeners() {
        const cards = [
            { el: cardPresent, val: "present" },
            { el: cardLate, val: "late" },
            { el: cardAbsent, val: "absent" },
            { el: cardLeave, val: "leave" }
        ];

        cards.forEach(card => {
            card.el.addEventListener("click", function () {
                // Clear any existing active card styles
                cards.forEach(c => c.el.classList.remove("active-filter"));

                if (activeCardFilter === card.val) {
                    // Reset if clicked again
                    activeCardFilter = "";
                    statusFilter.value = "";
                } else {
                    activeCardFilter = card.val;
                    statusFilter.value = card.val;
                    card.el.classList.add("active-filter");
                }
                applyFiltersAndRender(1);
            });
        });

        // Sync dropdown change back to cards
        statusFilter.addEventListener("change", function () {
            cards.forEach(c => c.el.classList.remove("active-filter"));
            activeCardFilter = this.value;
            if (activeCardFilter) {
                const activeCardObj = cards.find(c => c.val === activeCardFilter);
                if (activeCardObj) {
                    activeCardObj.el.classList.add("active-filter");
                }
            }
            applyFiltersAndRender(1);
        });
    }

    function showErrorRow(msg) {
        statusCard.style.display = "block";
        statusCard.className = "card__wrapper mb-20 border-start border-danger border-4";
        statusIcon.className = "bi bi-exclamation-circle-fill text-danger fs-4";
        statusTitle.textContent = "Tipsoi API Status: Offline / Error";
        statusDesc.textContent = msg;
        modeBadge.className = "badge bg-danger text-light px-3 py-2";
        modeBadge.textContent = "Sync Error";

        tableBody.innerHTML = `
            <tr>
                <td colspan="11" class="text-center py-5 text-danger">
                    <i class="bi bi-exclamation-triangle-fill fs-2 mb-2 d-block"></i>
                    ${msg}
                </td>
            </tr>
        `;
    }

    // Filter Button Click
    btnFilterSearch.addEventListener("click", function(e) {
        e.preventDefault();
        fetchAttendanceData();
    });

    // Export Functions
    function exportToCSV() {
        if (filteredRecords.length === 0) {
            alert("No data available to export.");
            return;
        }

        const fromVal = fromDateInput.value;
        const toVal = toDateInput.value;

        let csv = [];
        csv.push(",,,,The Premium Homes Ltd");
        csv.push(",,,,Employee Attendance Report");
        csv.push(`,,,,Date Range: ${fromVal} to ${toVal}`);
        csv.push("");
        csv.push(",,,,Summary Overview");
        csv.push(`,,,,Total Present,${statPresent.textContent}`);
        csv.push(`,,,,Total Late,${statLate.textContent}`);
        csv.push(`,,,,Total Absent,${statAbsent.textContent}`);
        csv.push(`,,,,Total On Leave,${statLeave.textContent}`);
        csv.push("");
        
        // Header Row
        csv.push("SN,Date,Office ID,Employee Name,Department,Designation,Punch In,Punch Out,Late Duration,Work Hours,Entry Location,Status");

        let sn = 1;
        filteredRecords.forEach(rec => {
            const statusText = rec.isLeave ? "On Leave" 
                             : rec.isPresent ? (rec.isLate ? "Late" : "Present")
                             : rec.isOffday ? "Weekend"
                             : rec.isHoliday ? "Holiday"
                             : "Absent";
                             
            let locStr = "—";
            if (rec.siteLatitude && rec.siteLongitude) {
                locStr = `GPS Map (Lat: ${rec.siteLatitude}; Lon: ${rec.siteLongitude})`;
            } else if (rec.workplaceName && rec.workplaceName !== 'N/A' && rec.workplaceName !== 'No Workplace') {
                locStr = rec.workplaceName;
            }

            const row = [
                sn++,
                rec.dateStr,
                rec.employeeOfficeId || '—',
                rec.employeeName,
                rec.departmentName,
                rec.designationName,
                rec.firstLoggedTime,
                rec.lastLoggedTime,
                rec.lateBy !== '-' ? rec.lateBy : '—',
                rec.totalHour !== '-' ? rec.totalHour : '—',
                locStr,
                statusText
            ].map(val => {
                const stringVal = String(val);
                if (stringVal.includes(',') || stringVal.includes('"') || stringVal.includes('\n')) {
                    return `"${stringVal.replace(/"/g, '""')}"`;
                }
                return stringVal;
            });

            csv.push(row.join(','));
        });

        const csvContent = "\uFEFF" + csv.join("\n");
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute("download", `Attendance_Report_${fromVal}_to_${toVal}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    function exportToPDF() {
        if (filteredRecords.length === 0) {
            alert("No data available to export.");
            return;
        }

        const fromVal = fromDateInput.value;
        const toVal = toDateInput.value;

        let sn = 1;
        const rowsHtml = filteredRecords.map(rec => {
            const statusText = rec.isLeave ? "On Leave" 
                             : rec.isPresent ? (rec.isLate ? "Late" : "Present")
                             : rec.isOffday ? "Weekend"
                             : rec.isHoliday ? "Holiday"
                             : "Absent";
            
            let locStr = "—";
            if (rec.siteLatitude && rec.siteLongitude) {
                locStr = `GPS Map (Lat: ${rec.siteLatitude}, Lon: ${rec.siteLongitude})`;
            } else if (rec.workplaceName && rec.workplaceName !== 'N/A' && rec.workplaceName !== 'No Workplace') {
                locStr = rec.workplaceName;
            }

            return `
                <tr>
                    <td>${sn++}</td>
                    <td>${rec.dateStr}</td>
                    <td>${rec.employeeOfficeId || '—'}</td>
                    <td style="font-weight: 600;">${rec.employeeName}</td>
                    <td>${rec.departmentName}</td>
                    <td>${rec.designationName}</td>
                    <td>${rec.firstLoggedTime}</td>
                    <td>${rec.lastLoggedTime}</td>
                    <td>${rec.lateBy !== '-' ? rec.lateBy : '—'}</td>
                    <td>${rec.totalHour !== '-' ? rec.totalHour : '—'}</td>
                    <td>${locStr}</td>
                    <td>${statusText}</td>
                </tr>
            `;
        }).join('');

        const printWindow = window.open("", "_blank");
        
        let htmlStr = '';
        htmlStr += '<!DOCTYPE html>';
        htmlStr += '<html>';
        htmlStr += '<head>';
        htmlStr += '    <title>Attendance_Report_' + fromVal + '_to_' + toVal + '</title>';
        htmlStr += '    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">';
        htmlStr += '    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">';
        htmlStr += '    <style>';
        htmlStr += '        @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap");';
        htmlStr += '        body { font-family: "Inter", sans-serif; color: #333; margin: 30px; }';
        htmlStr += '        .report-header { border-bottom: 2px solid #15a15b; padding-bottom: 15px; margin-bottom: 25px; text-align: center; }';
        htmlStr += '        .company-name { font-size: 26px; font-weight: 800; color: #15a15bff; margin: 0; }';
        htmlStr += '        .report-title { font-size: 18px; font-weight: 600; color: #495057; margin: 5px 0 0 0; }';
        htmlStr += '        .meta-info { font-size: 12px; color: #6c757d; margin-top: 10px; }';
        htmlStr += '        .stat-card-custom { border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; text-align: center; background-color: #f8f9fa; }';
        htmlStr += '        .stat-label { font-size: 11px; font-weight: 600; text-transform: uppercase; color: #6c757d; margin-bottom: 5px; }';
        htmlStr += '        .stat-val { font-size: 22px; font-weight: 700; }';
        htmlStr += '        .table-custom th { background-color: #f8f9fa !important; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }';
        htmlStr += '        .table-custom td { font-size: 11px; }';
        htmlStr += '        @media print {';
        htmlStr += '            body { margin: 15px; }';
        htmlStr += '            .no-print { display: none; }';
        htmlStr += '        }';
        htmlStr += '    </style>';
        htmlStr += '</head>';
        htmlStr += '<body>';
        htmlStr += '    <div class="report-header position-relative">';
        htmlStr += '        <div class="no-print position-absolute end-0 top-0">';
        htmlStr += '            <button onclick="window.print();" class="btn btn-sm btn-primary me-2"><i class="bi bi-printer"></i> Print</button>';
        htmlStr += '            <button onclick="window.close();" class="btn btn-sm btn-outline-secondary">Close</button>';
        htmlStr += '        </div>';
        htmlStr += '        <h1 class="company-name">The Premium Homes Ltd</h1>';
        htmlStr += '        <h2 class="report-title">Employee Attendance Report</h2>';
        htmlStr += '        <div class="meta-info">';
        htmlStr += '            <strong>Date Range:</strong> ' + fromVal + ' to ' + toVal + ' &nbsp;&middot;&nbsp; ';
        htmlStr += '            <strong>Generated On:</strong> ' + new Date().toLocaleString();
        htmlStr += '        </div>';
        htmlStr += '    </div>';
        htmlStr += '    ';
        htmlStr += '    <!-- Stats Row -->';
        htmlStr += '    <div class="row g-3 mb-4">';
        htmlStr += '        <div class="col-3">';
        htmlStr += '            <div class="stat-card-custom border-start border-success border-4">';
        htmlStr += '                <div class="stat-label">Total Present</div>';
        htmlStr += '                <div class="stat-val text-success">' + statPresent.textContent + '</div>';
        htmlStr += '            </div>';
        htmlStr += '        </div>';
        htmlStr += '        <div class="col-3">';
        htmlStr += '            <div class="stat-card-custom border-start border-warning border-4">';
        htmlStr += '                <div class="stat-label">Total Late</div>';
        htmlStr += '                <div class="stat-val text-warning">' + statLate.textContent + '</div>';
        htmlStr += '            </div>';
        htmlStr += '        </div>';
        htmlStr += '        <div class="col-3">';
        htmlStr += '            <div class="stat-card-custom border-start border-danger border-4">';
        htmlStr += '                <div class="stat-label">Total Absent</div>';
        htmlStr += '                <div class="stat-val text-danger">' + statAbsent.textContent + '</div>';
        htmlStr += '            </div>';
        htmlStr += '        </div>';
        htmlStr += '        <div class="col-3">';
        htmlStr += '            <div class="stat-card-custom border-start border-info border-4">';
        htmlStr += '                <div class="stat-label">Total On Leave</div>';
        htmlStr += '                <div class="stat-val text-info">' + statLeave.textContent + '</div>';
        htmlStr += '            </div>';
        htmlStr += '        </div>';
        htmlStr += '    </div>';
        htmlStr += '    ';
        htmlStr += '    <!-- Table -->';
        htmlStr += '    <table class="table table-bordered table-striped table-custom align-middle">';
        htmlStr += '        <thead>';
        htmlStr += '            <tr>';
        htmlStr += '                <th>SN</th>';
        htmlStr += '                <th>Date</th>';
        htmlStr += '                <th>Office ID</th>';
        htmlStr += '                <th>Employee Name</th>';
        htmlStr += '                <th>Department</th>';
        htmlStr += '                <th>Designation</th>';
        htmlStr += '                <th>Punch In</th>';
        htmlStr += '                <th>Punch Out</th>';
        htmlStr += '                <th>Late Duration</th>';
        htmlStr += '                <th>Work Hours</th>';
        htmlStr += '                <th>Entry Location</th>';
        htmlStr += '                <th>Status</th>';
        htmlStr += '            </tr>';
        htmlStr += '        </thead>';
        htmlStr += '        <tbody>';
        htmlStr += rowsHtml;
        htmlStr += '        </tbody>';
        htmlStr += '    </table>';
        htmlStr += '    ';
        htmlStr += '    <script>';
        htmlStr += '        window.onload = function() {';
        htmlStr += '            setTimeout(function() {';
        htmlStr += '                window.print();';
        htmlStr += '            }, 500);';
        htmlStr += '        };';
        htmlStr += '    <\/script>';
        htmlStr += '</body>';
        htmlStr += '</html>';

        printWindow.document.write(htmlStr);
        printWindow.document.close();
    }

    const btnExportCSV = document.getElementById("btnExportCSV");
    const btnExportPDF = document.getElementById("btnExportPDF");
    btnExportCSV.addEventListener("click", exportToCSV);
    btnExportPDF.addEventListener("click", exportToPDF);

    // Event Listeners for Live Filtering and Auto-Sync
    searchQuery.addEventListener("input", () => applyFiltersAndRender(1));
    departmentFilter.addEventListener("change", () => applyFiltersAndRender(1));
    fromDateInput.addEventListener("change", fetchAttendanceData);
    toDateInput.addEventListener("change", fetchAttendanceData);

    // Start execution
    setupStatsCardListeners();

    // Parse URL parameters for automatic status and date filtering
    const urlParams = new URLSearchParams(window.location.search);
    const initialStatus = urlParams.get('status');
    const initialDate = urlParams.get('date');

    if (initialDate) {
        fromDateInput.value = initialDate;
        toDateInput.value = initialDate;
    }

    if (initialStatus) {
        const validStatuses = ['present', 'late', 'absent', 'leave', 'offday', 'holiday'];
        const cleanStatus = initialStatus.toLowerCase().trim();
        if (validStatuses.includes(cleanStatus)) {
            statusFilter.value = cleanStatus;
            activeCardFilter = cleanStatus;

            const cards = [
                { el: cardPresent, val: "present" },
                { el: cardLate, val: "late" },
                { el: cardAbsent, val: "absent" },
                { el: cardLeave, val: "leave" }
            ];
            const matchingCard = cards.find(c => c.val === cleanStatus);
            if (matchingCard) {
                matchingCard.el.classList.add("active-filter");
            }
        }
    }

    fetchAttendanceData();
});
<?php
$page_script = ob_get_clean();
?>

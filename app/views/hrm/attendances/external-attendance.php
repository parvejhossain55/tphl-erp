<?php
$page_title = "Tipsoi Attendance Dashboard";
$page = 'Tipsoi Attendance';
$show_breadcrumb = true;

// Default date range: last 7 days
$default_from = date('Y-m-d', strtotime('-6 days'));
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
                <h6 class="m-0 fw-bold text-dark" id="apiStatusTitle">Tipsoi API Status</h6>
                <small class="text-muted" id="apiStatusDesc">Establishing connection...</small>
            </div>
        </div>
        <span class="badge bg-warning text-dark px-3 py-2" id="apiModeBadge">Checking...</span>
    </div>
</div>

<!-- Stats Cards Heading -->
<div class="d-flex align-items-center justify-content-between mb-15">
    <h6 class="text-muted text-uppercase fw-bold m-0" style="font-size: 0.8rem; letter-spacing: 0.5px;" id="statsHeaderLabel">
        Today's Attendance Overview
    </h6>
    <span class="badge bg-light text-dark border px-2 py-1" id="statsDateBadge">...</span>
</div>

<!-- Stats Cards Row -->
<div class="row g-20 mb-25">
    <!-- Today Present -->
    <div class="col-lg-3 col-md-6 col-12">
        <div class="card__wrapper d-flex align-items-center justify-content-between p-20 border-start border-success border-4 shadow-sm stats-card" style="cursor: pointer;" id="cardPresent">
            <div>
                <h6 class="text-muted text-uppercase mb-2 fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Today Present</h6>
                <h2 class="fw-bold text-success mb-0" id="statPresent">0</h2>
                <small class="text-muted">Click to view present</small>
            </div>
            <div class="bg-light-success p-3 rounded-circle text-success-dark d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                <i class="bi bi-person-check-fill fs-4"></i>
            </div>
        </div>
    </div>
    <!-- Today Late -->
    <div class="col-lg-3 col-md-6 col-12">
        <div class="card__wrapper d-flex align-items-center justify-content-between p-20 border-start border-warning border-4 shadow-sm stats-card" style="cursor: pointer;" id="cardLate">
            <div>
                <h6 class="text-muted text-uppercase mb-2 fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Today Late</h6>
                <h2 class="fw-bold text-warning mb-0" id="statLate">0</h2>
                <small class="text-muted">Click to view late</small>
            </div>
            <div class="bg-light-warning p-3 rounded-circle text-warning-dark d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                <i class="bi bi-clock-fill fs-4"></i>
            </div>
        </div>
    </div>
    <!-- Today Absent -->
    <div class="col-lg-3 col-md-6 col-12">
        <div class="card__wrapper d-flex align-items-center justify-content-between p-20 border-start border-danger border-4 shadow-sm stats-card" style="cursor: pointer;" id="cardAbsent">
            <div>
                <h6 class="text-muted text-uppercase mb-2 fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Today Absent</h6>
                <h2 class="fw-bold text-danger mb-0" id="statAbsent">0</h2>
                <small class="text-muted">Click to view absent</small>
            </div>
            <div class="bg-light-danger p-3 rounded-circle text-danger-dark d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                <i class="bi bi-person-x-fill fs-4"></i>
            </div>
        </div>
    </div>
    <!-- Today On Leave -->
    <div class="col-lg-3 col-md-6 col-12">
        <div class="card__wrapper d-flex align-items-center justify-content-between p-20 border-start border-info border-4 shadow-sm stats-card" style="cursor: pointer;" id="cardLeave">
            <div>
                <h6 class="text-muted text-uppercase mb-2 fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Today On Leave</h6>
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
                modeBadge.className = "badge bg-success text-white px-3 py-2";
                modeBadge.textContent = "Live Sync Mode";

                allRawAttendance = res.data || [];
                localEmployeeIds = res.local_employee_ids || [];
                localEmployeeEmails = res.local_employee_emails || [];
                normalizedLocalIds = localEmployeeIds.map(id => normalizeId(id));
                normalizedLocalEmails = localEmployeeEmails.map(email => email.toLowerCase().trim());

                // Process raw attendance array into flat daily records
                processRecords(allRawAttendance);

                // Populate Department Dropdown
                populateDepartmentFilter(allRawAttendance);

                // Calculate Stats for the latest day in the response dates
                calculateStats(allRawAttendance, res.dates || []);

                // Apply Filters and Render Table
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
            Object.keys(attMap).forEach(timestamp => {
                const dayAtt = attMap[timestamp];
                
                // Determine raw attendance status
                let status = "absent";
                if (dayAtt.isLeave) {
                    status = "leave";
                } else if (dayAtt.isPresent) {
                    status = dayAtt.isLate ? "late" : "present";
                } else if (dayAtt.isOffday || dayAtt.isHoliday) {
                    status = "offday";
                }

                allRecords.push({
                    employeeId: emp.employeeId,
                    employeeName: emp.employeeName,
                    employeeOfficeId: emp.employeeOfficeId,
                    departmentName: emp.departmentName || 'No Department',
                    designationName: emp.designationName || 'N/A',
                    timestamp: parseInt(timestamp, 10),
                    dateStr: dayAtt.date, // e.g. "06.07.2026 (Mon)"
                    firstLoggedTime: dayAtt.firstLoggedTime || '-',
                    lastLoggedTime: dayAtt.lastLoggedTime || '-',
                    totalHour: dayAtt.totalHour || '-',
                    isLate: dayAtt.isLate || false,
                    isEarly: dayAtt.isEarly || false,
                    isHoliday: dayAtt.isHoliday || false,
                    isOffday: dayAtt.isOffday || false,
                    isLeave: dayAtt.isLeave || false,
                    isPresent: dayAtt.isPresent || false,
                    lateBy: dayAtt.lateBy || '-',
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

    // Calculate Stats Cards for the latest day of the selected range
    function calculateStats(employees, dates) {
        if (!dates || dates.length === 0) {
            statPresent.textContent = "0";
            statLate.textContent = "0";
            statAbsent.textContent = "0";
            statLeave.textContent = "0";
            statsDateBadge.textContent = "No data";
            return;
        }

        // Find the latest day string
        const latestDateStr = dates[dates.length - 1];
        statsDateBadge.textContent = latestDateStr;

        let present = 0;
        let absent = 0;
        let late = 0;
        let leave = 0;

        employees.forEach(emp => {
            const attMap = emp.attendance || {};
            // Find the attendance object matching latestDateStr
            let dayAtt = null;
            Object.keys(attMap).forEach(ts => {
                if (attMap[ts].date === latestDateStr) {
                    dayAtt = attMap[ts];
                }
            });

            if (dayAtt) {
                if (dayAtt.isLeave) {
                    leave++;
                } else if (dayAtt.isPresent) {
                    present++;
                    if (dayAtt.isLate) {
                        late++;
                    }
                } else if (!dayAtt.isOffday && !dayAtt.isHoliday) {
                    absent++;
                }
            } else {
                // If no record exists for this day, check if it's a weekend
                const isWeekend = latestDateStr.toLowerCase().includes('fri') || latestDateStr.toLowerCase().includes('sun');
                if (!isWeekend) {
                    absent++;
                }
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
        const searchVal = searchQuery.value.toLowerCase().trim();
        const deptVal = departmentFilter.value;
        const statusVal = statusFilter.value || activeCardFilter;

        filteredRecords = allRecords.filter(rec => {
            // 1. Search Query (matches name, email, office id)
            const name = String(rec.employeeName || '').toLowerCase();
            const officeId = String(rec.employeeOfficeId || '').toLowerCase();
            const matchesSearch = !searchVal || name.includes(searchVal) || officeId.includes(searchVal);

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
                statusBadge = rec.isLate
                    ? '<span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-exclamation-circle-fill me-1"></i>Late</span>'
                    : '<span class="badge bg-success text-white px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>Present</span>';
            } else if (rec.isOffday) {
                statusBadge = '<span class="badge bg-secondary text-white px-2 py-1"><i class="bi bi-calendar-event me-1"></i>Weekend</span>';
            } else if (rec.isHoliday) {
                statusBadge = '<span class="badge bg-secondary text-white px-2 py-1"><i class="bi bi-calendar-check me-1"></i>Holiday</span>';
            } else {
                statusBadge = '<span class="badge bg-danger text-white px-2 py-1"><i class="bi bi-person-x-fill me-1"></i>Absent</span>';
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

            return `
                <tr>
                    <td class="text-muted fw-semibold">${sn++}</td>
                    <td class="fw-semibold text-dark">${rec.dateStr}</td>
                    <td class="fw-semibold text-muted">${rec.employeeOfficeId || '—'}</td>
                    <td class="fw-bold">${rec.employeeName} ${localIndicator}</td>
                    <td>${rec.departmentName}</td>
                    <td><span class="badge bg-light text-dark border">${rec.designationName}</span></td>
                    <td>${checkIn}</td>
                    <td>${checkOut}</td>
                    <td class="text-danger fw-semibold">${rec.lateBy !== '-' ? rec.lateBy : '—'}</td>
                    <td class="text-muted fw-semibold">${rec.totalHour !== '-' ? rec.totalHour : '—'}</td>
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
        modeBadge.className = "badge bg-danger text-white px-3 py-2";
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

    // Start execution
    setupStatsCardListeners();
    fetchAttendanceData();
});
<?php
$page_script = ob_get_clean();
?>

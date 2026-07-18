<style>
/* Theme colors: Green #198754 */
.text-success-custom {
    color: #198754 !important;
}
.bg-success-custom {
    background-color: #198754 !important;
}
.border-success-custom {
    border-color: #198754 !important;
}
.table-success-header th {
    background-color: #198754 !important;
    color: #ffffff !important;
}
</style>

<!-- Flatpickr (Date Picker CSS & JS) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<div class="row">
    <div class="card__wrapper">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <h4 class="fw-bold mb-0 text-success-custom fs-18" id="reportTitle">DAILY REPORT OF <?= date('d M Y') ?></h4>
        </div>

        <!-- Filters Section -->
        <div class="card custom-card shadow-xs mb-4">
            <div class="card-body p-3">
                <div class="row g-2 align-items-center">
                    
                    <!-- Search Input -->
                    <div class="col-xxl-2 col-lg-3 col-md-6 col-12">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" id="employeeSearch" class="form-control form-control-sm border-start-0 fs-12" placeholder="Search Employee by Name/ID/RFID">
                        </div>
                    </div>

                    <!-- Date Picker -->
                    <div class="col-xxl-2 col-lg-3 col-md-6 col-12">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-calendar-day text-muted"></i></span>
                            <input type="text" id="dailyDate" class="form-control form-control-sm border-start-0 fs-12 bg-white" placeholder="Select Date">
                        </div>
                    </div>

                    <!-- Status Dropdown -->
                    <div class="col-xxl-2 col-lg-2 col-md-4 col-12">
                        <select id="statusFilter" class="form-select form-select-sm fs-12">
                            <option value="">Attendance Status</option>
                            <option value="Present">Present Only</option>
                            <option value="Late">Late Only</option>
                            <option value="Absent">Absent Only</option>
                            <option value="Leave">On Leave</option>
                            <option value="Off Day">Off Day</option>
                        </select>
                    </div>

                    <!-- Department Dropdown -->
                    <div class="col-xxl-2 col-lg-2 col-md-4 col-12">
                        <select id="departmentFilter" class="form-select form-select-sm fs-12">
                            <option value="">All Departments</option>
                        </select>
                    </div>

                    <!-- Sort By Dropdown -->
                    <div class="col-xxl-2 col-lg-2 col-md-4 col-12">
                        <select id="sortBy" class="form-select form-select-sm fs-12">
                            <option value="report_position">Sort By: Report Position</option>
                            <option value="id">Sort By: ID</option>
                            <option value="name">Sort By: Name</option>
                            <option value="department">Sort By: Department</option>
                            <option value="designation">Sort By: Designation</option>
                            <option value="employment_type">Sort By: Employment Type</option>
                            <option value="shift_group">Sort By: Shift Group</option>
                            <option value="workplace">Sort By: Workplace</option>
                        </select>
                    </div>

                    <!-- Rows Per Page -->
                    <div class="col-xxl-1 col-lg-2 col-md-4 col-12">
                        <div class="d-flex align-items-center gap-1">
                            <span class="text-muted fs-11 text-nowrap">Rows</span>
                            <select id="perPage" class="form-select form-select-sm fs-12" style="width: 75px;">
                                <option value="10">10</option>
                                <option value="20">20</option>
                                <option value="50" selected>50</option>
                                <option value="100">100</option>
                                <option value="500">500</option>
                                <option value="1000">1000</option>
                            </select>
                        </div>
                    </div>

                    <!-- Export Buttons -->
                    <div class="col-xxl-1 col-lg-2 col-md-4 col-12 text-end ms-auto">
                        <div class="d-flex gap-2 justify-content-end">
                            <button id="btnPdf" class="btn btn-sm btn-danger text-white px-3 d-flex align-items-center gap-1 fs-11" style="height: 32px; background-color: #dc3545; border-color: #dc3545;">
                                <i class="fa-solid fa-file-pdf"></i> PDF
                            </button>
                            <button id="btnExcel" class="btn btn-sm btn-success text-white px-3 d-flex align-items-center gap-1 fs-11" style="height: 32px; background-color: #198754; border-color: #198754;">
                                <i class="fa-solid fa-file-excel"></i> Excel
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Summary Statistics Row -->
        <div class="table-responsive mb-4 rounded border shadow-xs" style="background-color: #ffffff;">
            <table class="table table-bordered table-sm text-center align-middle mb-0 fs-12" style="border-color: #cbd5e1;">
                <tbody>
                    <tr>
                        <td class="fw-semibold text-muted text-start ps-3" style="width: 15%; background-color: #f8fafc; color: #475569;">Total Employees</td>
                        <td class="fw-bold text-dark" style="width: 10%; background-color: #ffffff;" id="statTotal">0</td>
                        <td class="fw-semibold text-muted text-start ps-3" style="width: 15%; background-color: #f8fafc; color: #475569;">Leave</td>
                        <td class="fw-bold text-dark" style="width: 10%; background-color: #ffffff;" id="statLeave">0</td>
                        <td class="fw-semibold text-muted text-start ps-3" style="width: 15%; background-color: #f8fafc; color: #475569;">Late</td>
                        <td class="fw-bold text-dark" style="width: 10%; background-color: #ffffff;" id="statLate">0</td>
                        <td class="fw-semibold text-muted text-start ps-3" style="width: 15%; background-color: #f8fafc; color: #475569;">Day off</td>
                        <td class="fw-bold text-dark" style="width: 10%; background-color: #ffffff;" id="statOffday">0</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold text-muted text-start ps-3" style="background-color: #f8fafc; color: #475569;">Present</td>
                        <td class="fw-bold text-dark" style="background-color: #ffffff;" id="statPresent">0</td>
                        <td class="fw-semibold text-muted text-start ps-3" style="background-color: #f8fafc; color: #475569;">Absent</td>
                        <td class="fw-bold text-dark" style="background-color: #ffffff;" id="statAbsent">0</td>
                        <td class="fw-semibold text-muted text-start ps-3" style="background-color: #f8fafc; color: #475569;">Early Exit</td>
                        <td class="fw-bold text-dark" style="background-color: #ffffff;" id="statEarly">0</td>
                        <td class="fw-semibold text-muted text-start ps-3" style="background-color: #f8fafc; color: #475569;">No Out Punch</td>
                        <td class="fw-bold text-dark" style="background-color: #ffffff;" id="statNoOut">0</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Main Data Table Card -->
        <div class="card custom-card shadow-xs border-top border-4 border-success-custom">
            <div class="card-body p-0">
                
                <div class="table-responsive">
                    <table class="table table-hover table-bordered text-start align-middle mb-0 fs-11" id="reportTable" style="border-color: #cbd5e1; min-width: 1200px;">
                        <thead class="table-success-header">
                            <tr>
                                <th class="text-center" style="width: 3%;">SL</th>
                                <th class="text-center" style="width: 4%;">ID</th>
                                <th>Employee Name</th>
                                <th>Workplace</th>
                                <th>Employment Type</th>
                                <th>Shift</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Entry</th>
                                <th class="text-center">Exit</th>
                                <th class="text-center">Late</th>
                                <th class="text-center">Early Exit</th>
                                <th class="text-center">Hours</th>
                                <th class="text-center">OT Hours</th>
                            </tr>
                        </thead>
                        <tbody id="reportTbody">
                            <tr>
                                <td colspan="13" class="text-center py-5">
                                    <div class="spinner-border spinner-border-sm text-success-custom mb-2" role="status"></div>
                                    <div class="fw-semibold text-muted">Fetching daily reports from Tipsoi...</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div class="d-flex justify-content-between align-items-center p-3 border-top flex-wrap gap-2">
                    <div class="text-muted fs-11" id="paginationInfo">Showing 0–0 of 0 entries</div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0" id="paginationList">
                            <!-- Dynamic pages -->
                        </ul>
                    </nav>
                </div>

            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    let allRows = [];
    let filteredRows = [];
    let currentPage = 1;

    // Date picker setup
    const dateInput = document.getElementById("dailyDate");
    const today = new Date();
    
    flatpickr(dateInput, {
        dateFormat: "Y-m-d",
        defaultDate: today,
        onChange: function(selectedDates) {
            if (selectedDates.length === 1) {
                const formatted = selectedDates[0].toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' });
                document.getElementById("reportTitle").textContent = `DAILY REPORT OF ${formatted.toUpperCase()}`;
                
                const dateStr = selectedDates[0].toISOString().split('T')[0];
                loadDailyReport(dateStr);
            }
        }
    });

    // Initial load
    const initDate = today.toISOString().split('T')[0];
    loadDailyReport(initDate);

    // Event listeners
    document.getElementById("employeeSearch").addEventListener("input", applyFilters);
    document.getElementById("statusFilter").addEventListener("change", applyFilters);
    document.getElementById("departmentFilter").addEventListener("change", applyFilters);
    document.getElementById("sortBy").addEventListener("change", applyFilters);
    document.getElementById("perPage").addEventListener("change", function() {
        currentPage = 1;
        renderTable();
    });

    function loadDailyReport(dateStr) {
        const tbody = document.getElementById("reportTbody");
        tbody.innerHTML = `
            <tr>
                <td colspan="13" class="text-center py-5">
                    <div class="spinner-border spinner-border-sm text-success-custom mb-2" role="status"></div>
                    <div class="fw-semibold text-muted">Fetching daily reports from Tipsoi...</div>
                </td>
            </tr>
        `;

        fetch(`<?= $GLOBALS['base_url'] ?>/api/employee/tipsoi-daily-data?date=${dateStr}`)
            .then(res => res.json())
            .then(res => {
                if (!res.success) {
                    tbody.innerHTML = `<tr><td colspan="13" class="text-center py-4 text-danger fw-semibold">${res.message}</td></tr>`;
                    return;
                }

                // Update Stats Summary Cards
                document.getElementById("statTotal").textContent = res.totalEmployees;
                document.getElementById("statLeave").textContent = res.leaveCount;
                document.getElementById("statLate").textContent = res.lateCount;
                document.getElementById("statOffday").textContent = res.dayOffCount;
                document.getElementById("statPresent").textContent = res.presentCount;
                document.getElementById("statAbsent").textContent = res.absentCount;
                document.getElementById("statEarly").textContent = res.earlyExitCount;
                document.getElementById("statNoOut").textContent = res.noOutPunchCount;

                // Map report position
                allRows = (res.rows || []).map((row, index) => {
                    row.reportPosition = index;
                    return row;
                });

                // Populate Department Dropdown dynamically
                populateDepartmentFilter(allRows);

                applyFilters();
            })
            .catch(err => {
                tbody.innerHTML = `<tr><td colspan="13" class="text-center py-4 text-danger fw-semibold">Error: ${err.message}</td></tr>`;
            });
    }

    function populateDepartmentFilter(rows) {
        const depts = new Set();
        rows.forEach(r => {
            if (r.department) {
                depts.add(r.department.trim());
            }
        });

        const select = document.getElementById("departmentFilter");
        const currentVal = select.value;
        select.innerHTML = '<option value="">All Departments</option>';

        Array.from(depts).sort().forEach(d => {
            const opt = document.createElement('option');
            opt.value = d;
            opt.textContent = d;
            select.appendChild(opt);
        });
        select.value = currentVal;
    }

    function applyFilters() {
        const searchVal = document.getElementById("employeeSearch").value.toLowerCase().trim();
        const statusVal = document.getElementById("statusFilter").value;
        const deptVal = document.getElementById("departmentFilter").value;
        const currentSortKey = document.getElementById("sortBy").value;

        filteredRows = allRows.filter(row => {
            // Search filter
            const matchesSearch = !searchVal || 
                                  String(row.name).toLowerCase().includes(searchVal) ||
                                  String(row.id).toLowerCase().includes(searchVal);
            
            // Status filter
            const matchesStatus = !statusVal || row.status === statusVal;

            // Department filter
            const matchesDept = !deptVal || row.department === deptVal;

            return matchesSearch && matchesStatus && matchesDept;
        });

        // Sorting
        if (currentSortKey === 'report_position') {
            filteredRows.sort((a, b) => a.reportPosition - b.reportPosition);
        } else if (currentSortKey === 'id') {
            filteredRows.sort((a, b) => parseInt(a.id) - parseInt(b.id));
        } else if (currentSortKey === 'name') {
            filteredRows.sort((a, b) => String(a.name).localeCompare(String(b.name)));
        } else if (currentSortKey === 'department') {
            filteredRows.sort((a, b) => String(a.department).localeCompare(String(b.department)));
        } else if (currentSortKey === 'designation') {
            filteredRows.sort((a, b) => String(a.designation).localeCompare(String(b.designation)));
        } else if (currentSortKey === 'employment_type') {
            filteredRows.sort((a, b) => String(a.employeeType).localeCompare(String(b.employeeType)));
        } else if (currentSortKey === 'shift_group') {
            filteredRows.sort((a, b) => String(a.shiftGroup || '').localeCompare(String(b.shiftGroup || '')));
        } else if (currentSortKey === 'workplace') {
            filteredRows.sort((a, b) => String(a.workplace).localeCompare(String(b.workplace)));
        }

        currentPage = 1;
        renderTable();
    }

    function renderTable() {
        const tbody = document.getElementById("reportTbody");
        const perPage = parseInt(document.getElementById("perPage").value);
        
        if (filteredRows.length === 0) {
            tbody.innerHTML = `<tr><td colspan="13" class="text-center py-4 text-muted">No records found matching filters.</td></tr>`;
            document.getElementById("paginationInfo").textContent = "Showing 0–0 of 0 entries";
            document.getElementById("paginationList").innerHTML = "";
            return;
        }

        const total = filteredRows.length;
        const totalPages = Math.ceil(total / perPage);
        const start = (currentPage - 1) * perPage;
        const end = Math.min(start + perPage, total);
        
        const displayRows = filteredRows.slice(start, end);
        tbody.innerHTML = "";

        displayRows.forEach((row, idx) => {
            const avatarHtml = row.photo 
                ? `<img src="${row.photo}" class="rounded-circle me-2" style="width: 28px; height: 28px; object-fit: cover;">`
                : `<div class="bg-secondary text-white rounded-circle d-inline-flex align-items-center justify-content-center me-2" style="width: 28px; height: 28px; font-size: 10px; font-weight: 700;">${String(row.name).charAt(0)}</div>`;

            let badgeClass = "text-muted";
            if (row.status === 'Present') badgeClass = "text-success fw-semibold";
            else if (row.status === 'Late') badgeClass = "text-danger fw-semibold";
            else if (row.status === 'Absent') badgeClass = "text-danger fw-semibold";
            else if (row.status === 'Leave') badgeClass = "text-primary fw-semibold";
            else if (row.status === 'Off Day') badgeClass = "text-success fw-semibold";
            else if (row.status === 'Holiday') badgeClass = "text-info fw-semibold";

            tbody.innerHTML += `
                <tr>
                    <td class="text-center text-muted fw-semibold">${start + idx + 1}</td>
                    <td class="text-center fw-bold text-dark">${row.id}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            ${avatarHtml}
                            <div>
                                <span class="fw-bold text-success-custom d-block">${row.name}</span>
                                <small class="text-muted d-block" style="font-size: 9px;">${row.designation}</small>
                            </div>
                        </div>
                    </td>
                    <td>${row.workplace}</td>
                    <td>${row.employeeType}</td>
                    <td>${row.shift}</td>
                    <td class="text-center ${badgeClass}">${row.status}</td>
                    <td class="text-center text-dark font-monospace">${row.entry}</td>
                    <td class="text-center text-dark font-monospace">${row.exit}</td>
                    <td class="text-center text-dark font-monospace">${row.late}</td>
                    <td class="text-center text-dark font-monospace">${row.early}</td>
                    <td class="text-center fw-bold text-dark">${row.hours}</td>
                    <td class="text-center text-muted">${row.ot}</td>
                </tr>
            `;
        });

        document.getElementById("paginationInfo").textContent = `Showing ${start + 1}–${end} of ${total} entries`;
        
        // Render Pagination Links
        const pag = document.getElementById("paginationList");
        pag.innerHTML = "";

        // Previous button
        const prevLi = document.createElement("li");
        prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `<a class="page-link" href="javascript:void(0);"><i class="fa-solid fa-angle-left"></i></a>`;
        if (currentPage > 1) {
            prevLi.addEventListener("click", () => {
                currentPage--;
                renderTable();
            });
        }
        pag.appendChild(prevLi);

        // Page Numbers
        for (let p = 1; p <= totalPages; p++) {
            if (p === 1 || p === totalPages || (p >= currentPage - 2 && p <= currentPage + 2)) {
                const li = document.createElement("li");
                li.className = `page-item ${currentPage === p ? 'active' : ''}`;
                li.innerHTML = `<a class="page-link" href="javascript:void(0);">${p}</a>`;
                li.addEventListener("click", () => {
                    currentPage = p;
                    renderTable();
                });
                pag.appendChild(li);
            } else if (p === currentPage - 3 || p === currentPage + 3) {
                const li = document.createElement("li");
                li.className = "page-item disabled";
                li.innerHTML = `<a class="page-link" href="javascript:void(0);">...</a>`;
                pag.appendChild(li);
            }
        }

        // Next button
        const nextLi = document.createElement("li");
        nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
        nextLi.innerHTML = `<a class="page-link" href="javascript:void(0);"><i class="fa-solid fa-angle-right"></i></a>`;
        if (currentPage < totalPages) {
            nextLi.addEventListener("click", () => {
                currentPage++;
                renderTable();
            });
        }
        pag.appendChild(nextLi);
    }

    // Export Event Listeners
    document.getElementById("btnExcel").addEventListener("click", function() {
        let csv = [];
        let headers = ["SL", "ID", "Employee Name", "Workplace", "Employment Type", "Shift", "Status", "Entry", "Exit", "Late", "Early Exit", "Hours", "OT Hours"];
        csv.push(headers.join(","));

        filteredRows.forEach((row, index) => {
            let r = [
                index + 1,
                `"${row.id}"`,
                `"${row.name}"`,
                `"${row.workplace}"`,
                `"${row.employeeType}"`,
                `"${row.shift.replace(/<br>/g, ' ').replace(/<[^>]*>/g, '')}"`,
                `"${row.status}"`,
                `"${row.entry}"`,
                `"${row.exit}"`,
                `"${row.late}"`,
                `"${row.early}"`,
                `"${row.hours}"`,
                `"${row.ot}"`
            ];
            csv.push(r.join(","));
        });

        let csvContent = "data:text/csv;charset=utf-8,\ufeff" + csv.join("\n");
        let encodedUri = encodeURI(csvContent);
        let link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "Daily_Attendance_Report.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    document.getElementById("btnPdf").addEventListener("click", function() {
        let title = document.getElementById("reportTitle").textContent || "Daily Report";
        let table = document.getElementById("reportTable");
        if (!table) return;

        let cloned = table.cloneNode(true);
        cloned.querySelectorAll("img").forEach(img => img.remove());

        let win = window.open("", "_blank");
        win.document.write(`
            <html>
            <head>
                <title>${title}</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    body { font-family: 'Inter', sans-serif; padding: 20px; }
                    h2 { color: #198754; font-weight: bold; margin-bottom: 20px; text-transform: uppercase; font-size: 16px; text-align: center; }
                    table { font-size: 9px !important; }
                    th { background-color: #198754 !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; text-align: center; }
                    td { vertical-align: middle; }
                    @page { size: landscape; margin: 10mm; }
                </style>
            </head>
            <body>
                <h2>${title}</h2>
                <div class="table-responsive">
                    ${cloned.outerHTML}
                </div>
                <script>
                    window.onload = function() {
                        window.print();
                        setTimeout(function() { window.close(); }, 500);
                    };
                <\/script>
            </body>
            </html>
        `);
        win.document.close();
    });
});
</script>

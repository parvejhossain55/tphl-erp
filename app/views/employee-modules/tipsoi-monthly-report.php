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

<!-- Flatpickr (Date Range Picker CSS & JS) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<div class="row">
    <div class="card__wrapper">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <h4 class="fw-bold mb-0 text-success-custom fs-18" id="reportTitle">MONTHLY REPORT OF <?= strtoupper(date('F Y')) ?></h4>
        </div>

        <!-- Filters Section -->
        <div class="card custom-card shadow-xs mb-4">
            <div class="card-body p-3">
                <div class="row g-2 align-items-center">
                    
                    <!-- Search Input -->
                    <div class="col-xxl-3 col-lg-3 col-md-6 col-12">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" id="employeeSearch" class="form-control form-control-sm border-start-0 fs-12" placeholder="Search Employee by Name/ID/RFID">
                        </div>
                    </div>

                    <!-- Date Range Picker -->
                    <div class="col-xxl-3 col-lg-3 col-md-6 col-12">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-calendar-days text-muted"></i></span>
                            <input type="text" id="dateRange" class="form-control form-control-sm border-start-0 fs-12 bg-white" placeholder="Select Date Range">
                        </div>
                    </div>

                    <!-- Status Dropdown -->
                    <div class="col-xxl-2 col-lg-2 col-md-4 col-12">
                        <select id="statusFilter" class="form-select form-select-sm fs-12">
                            <option value="">Attendance Status</option>
                            <option value="present">Present Only</option>
                            <option value="absent">Absent Only</option>
                            <option value="late">Late Only</option>
                            <option value="leave">On Leave</option>
                        </select>
                    </div>

                    <!-- Rows Per Page -->
                    <div class="col-xxl-2 col-lg-2 col-md-4 col-12">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted fs-11 text-nowrap">Rows Per Page</span>
                            <select id="perPage" class="form-select form-select-sm fs-12" style="width: 80px;">
                                <option value="10">10</option>
                                <option value="20">20</option>
                                <option value="50" selected>50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>

                    <!-- Export Buttons -->
                    <div class="col-xxl-2 col-lg-2 col-md-4 col-12 text-end ms-auto">
                        <div class="d-flex gap-2 justify-content-end">
                            <button id="btnPdf" class="btn btn-sm btn-danger text-white px-3 d-flex align-items-center gap-1 fs-11" style="height: 32px;">
                                <i class="fa-solid fa-file-pdf"></i> PDF
                            </button>
                            <button id="btnExcel" class="btn btn-sm btn-success text-white px-3 d-flex align-items-center gap-1 fs-11" style="height: 32px;">
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
                        <td class="fw-semibold text-muted text-start ps-3" style="width: 25%; background-color: #f8fafc; color: #475569;">Absent Days</td>
                        <td class="fw-bold text-dark" style="width: 25%; background-color: #ffffff;" id="statAbsent">0</td>
                        <td class="fw-semibold text-muted text-start ps-3" style="width: 25%; background-color: #f8fafc; color: #475569;">Leave Days</td>
                        <td class="fw-bold text-dark" style="width: 25%; background-color: #ffffff;" id="statLeave">0</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold text-muted text-start ps-3" style="background-color: #f8fafc; color: #475569;">Late Hours</td>
                        <td class="fw-bold text-dark" style="background-color: #ffffff;" id="statLate">0</td>
                        <td class="fw-semibold text-muted text-start ps-3" style="background-color: #f8fafc; color: #475569;">Overtime Hours</td>
                        <td class="fw-bold text-dark" style="background-color: #ffffff;" id="statOvertime">0</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Main Data Table Card -->
        <div class="card custom-card shadow-xs border-top border-4 border-success-custom">
            <div class="card-body p-0">
                
                <div class="table-responsive">
                    <table class="table table-hover table-bordered text-start align-middle mb-0 fs-11" id="reportTable" style="border-color: #cbd5e1; min-width: 1400px;">
                        <thead class="table-success-header">
                            <tr>
                                <th class="text-center" style="width: 3%;">SL</th>
                                <th class="text-center" style="width: 4%;">ID</th>
                                <th>Employee Name</th>
                                <th>Workplace</th>
                                <th>Employee Type</th>
                                <th class="text-center">Working Days</th>
                                <th class="text-center">Present</th>
                                <th class="text-center">Absent</th>
                                <th class="text-center">Leave</th>
                                <th class="text-center">Off Days</th>
                                <th class="text-center">Holidays</th>
                                <th class="text-center">Special Days</th>
                                <th class="text-center">Late</th>
                                <th class="text-center">Approved Lates</th>
                                <th class="text-center">Field Visits</th>
                                <th class="text-center">Expected Hours</th>
                                <th class="text-center">Working Hours</th>
                                <th class="text-center">Difference</th>
                                <th class="text-center">OT Hours</th>
                            </tr>
                        </thead>
                        <tbody id="reportTbody">
                            <tr>
                                <td colspan="19" class="text-center py-5">
                                    <div class="spinner-border spinner-border-sm text-success-custom mb-2" role="status"></div>
                                    <div class="fw-semibold text-muted">Fetching monthly reports from Tipsoi...</div>
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
    const dateInput = document.getElementById("dateRange");
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    
    flatpickr(dateInput, {
        mode: "range",
        dateFormat: "Y-m-d",
        defaultDate: [firstDay, today],
        onChange: function(selectedDates) {
            if (selectedDates.length === 2) {
                const startStr = formatDateStr(selectedDates[0]);
                const endStr = formatDateStr(selectedDates[1]);
                document.getElementById("reportTitle").textContent = `MONTHLY REPORT FROM ${startStr.toUpperCase()} TO ${endStr.toUpperCase()}`;
                
                const rangeQuery = selectedDates[0].toISOString().split('T')[0] + ' - ' + selectedDates[1].toISOString().split('T')[0];
                loadMonthlyReport(rangeQuery);
            }
        }
    });

    // Helper to format date string
    function formatDateStr(d) {
        return d.toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    // Initial load with current month range
    const initRange = firstDay.toISOString().split('T')[0] + ' - ' + today.toISOString().split('T')[0];
    loadMonthlyReport(initRange);

    // Event listeners
    document.getElementById("employeeSearch").addEventListener("input", applyFilters);
    document.getElementById("statusFilter").addEventListener("change", applyFilters);
    document.getElementById("perPage").addEventListener("change", function() {
        currentPage = 1;
        renderTable();
    });

    function loadMonthlyReport(range) {
        const tbody = document.getElementById("reportTbody");
        tbody.innerHTML = `
            <tr>
                <td colspan="19" class="text-center py-5">
                    <div class="spinner-border spinner-border-sm text-success-custom mb-2" role="status"></div>
                    <div class="fw-semibold text-muted">Fetching monthly reports from Tipsoi...</div>
                </td>
            </tr>
        `;

        fetch(`<?= $GLOBALS['base_url'] ?>/api/employee/tipsoi-monthly-data?range=${encodeURIComponent(range)}`)
            .then(res => res.json())
            .then(res => {
                if (!res.success) {
                    tbody.innerHTML = `<tr><td colspan="19" class="text-center py-4 text-danger fw-semibold">${res.message}</td></tr>`;
                    return;
                }

                // Update Stats Summary Cards
                document.getElementById("statAbsent").textContent = res.absentDays;
                document.getElementById("statLeave").textContent = res.leaveDays;
                document.getElementById("statLate").textContent = res.lateHours;
                document.getElementById("statOvertime").textContent = res.overtimeHours;

                allRows = res.rows || [];
                applyFilters();
            })
            .catch(err => {
                tbody.innerHTML = `<tr><td colspan="19" class="text-center py-4 text-danger fw-semibold">Error: ${err.message}</td></tr>`;
            });
    }

    function applyFilters() {
        const searchVal = document.getElementById("employeeSearch").value.toLowerCase().trim();
        const statusVal = document.getElementById("statusFilter").value;

        filteredRows = allRows.filter(row => {
            // Search filter
            const matchesSearch = !searchVal || 
                                  String(row.name).toLowerCase().includes(searchVal) ||
                                  String(row.id).toLowerCase().includes(searchVal);
            
            // Status filter
            let matchesStatus = true;
            if (statusVal === 'present') {
                matchesStatus = parseInt(row.present) > 0;
            } else if (statusVal === 'absent') {
                matchesStatus = parseInt(row.absent) > 0;
            } else if (statusVal === 'late') {
                matchesStatus = parseInt(row.late) > 0;
            } else if (statusVal === 'leave') {
                matchesStatus = parseFloat(row.leave) > 0;
            }

            return matchesSearch && matchesStatus;
        });

        currentPage = 1;
        renderTable();
    }

    function renderTable() {
        const tbody = document.getElementById("reportTbody");
        const perPage = parseInt(document.getElementById("perPage").value);
        
        if (filteredRows.length === 0) {
            tbody.innerHTML = `<tr><td colspan="19" class="text-center py-4 text-muted">No records found matching filters.</td></tr>`;
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
                    <td class="text-center fw-semibold text-dark">${row.workingDays}</td>
                    <td class="text-center fw-semibold text-dark">${row.present}</td>
                    <td class="text-center fw-semibold text-danger">${row.absent}</td>
                    <td class="text-center fw-semibold text-primary">${row.leave}</td>
                    <td class="text-center fw-semibold text-success">${row.offDays}</td>
                    <td class="text-center fw-semibold text-info">${row.holidays}</td>
                    <td class="text-center fw-semibold text-secondary">${row.specialDays}</td>
                    <td class="text-center fw-semibold text-warning">${row.late}</td>
                    <td class="text-center text-dark">${row.approvedLates}</td>
                    <td class="text-center text-dark">${row.fieldVisits}</td>
                    <td class="text-center text-muted">${row.expectedHours}</td>
                    <td class="text-center fw-bold text-dark">${row.workingHours}</td>
                    <td class="text-center fw-bold text-success">${row.difference}</td>
                    <td class="text-center fw-semibold text-dark">${row.otHours}</td>
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
        let headers = ["SL", "ID", "Employee Name", "Workplace", "Employee Type", "Working Days", "Present", "Absent", "Leave", "Off Days", "Holidays", "Special Days", "Late", "Approved Lates", "Field Visits", "Expected Hours", "Working Hours", "Difference", "OT Hours"];
        csv.push(headers.join(","));

        filteredRows.forEach((row, index) => {
            let r = [
                index + 1,
                `"${row.id}"`,
                `"${row.name}"`,
                `"${row.workplace}"`,
                `"${row.employeeType}"`,
                row.workingDays,
                row.present,
                row.absent,
                row.leave,
                row.offDays,
                row.holidays,
                row.specialDays,
                row.late,
                row.approvedLates,
                row.fieldVisits,
                `"${row.expectedHours}"`,
                `"${row.workingHours}"`,
                `"${row.difference}"`,
                `"${row.otHours}"`
            ];
            csv.push(r.join(","));
        });

        let csvContent = "data:text/csv;charset=utf-8,\ufeff" + csv.join("\n");
        let encodedUri = encodeURI(csvContent);
        let link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "Monthly_Attendance_Report.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    document.getElementById("btnPdf").addEventListener("click", function() {
        let title = document.getElementById("reportTitle").textContent || "Monthly Report";
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

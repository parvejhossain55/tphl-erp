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
    border-color: rgba(255,255,255,0.15) !important;
}
.status-present {
    color: #198754 !important;
    font-weight: 600;
}
.status-absent {
    color: #dc3545 !important;
    font-weight: 600;
}
.status-late {
    color: #fd7e14 !important;
    font-weight: 600;
}
.status-leave {
    color: #0dcaf0 !important;
    font-weight: 600;
}
.status-offday {
    color: #6f42c1 !important;
    font-weight: 600;
}
.status-holiday {
    color: #20c997 !important;
    font-weight: 600;
}
</style>

<!-- Flatpickr (Date Picker CSS & JS) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<div class="row">
    <div class="card__wrapper">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <h4 class="fw-bold mb-0 text-success-custom fs-18">DEPARTMENT ATTENDANCE REPORT</h4>
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
                            <option value="Present">Present (All)</option>
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
                    <div class="col-xxl-2 col-lg-2 col-md-4 col-12 text-end ms-auto">
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

        <!-- Main Data Table Card -->
        <div class="card custom-card shadow-xs border-top border-4 border-success-custom">
            <div class="card-body p-0">
                
                <div class="table-responsive" style="max-height: 650px; overflow-y: auto;">
                    <table class="table table-hover table-bordered text-start align-middle mb-0 fs-11" id="reportTable" style="border-color: #cbd5e1; min-width: 1500px;">
                        <thead class="table-success-header" style="position: sticky; top: 0; z-index: 10;">
                            <tr id="headerDatesRow">
                                <th rowspan="2" class="text-center align-middle" style="width: 3%; background-color: #198754;">SL</th>
                                <th rowspan="2" class="text-center align-middle" style="width: 4%; background-color: #198754;">ID</th>
                                <th rowspan="2" class="align-middle" style="width: 15%; background-color: #198754;">Employee Name</th>
                                <th rowspan="2" class="align-middle" style="width: 10%; background-color: #198754;">Employment Type</th>
                                <th rowspan="2" class="align-middle" style="width: 10%; background-color: #198754;">Shift Group</th>
                                <th rowspan="2" class="align-middle" style="width: 8%; background-color: #198754;">Workplace</th>
                            </tr>
                            <tr id="headerSubRow">
                            </tr>
                        </thead>
                        <tbody id="reportTbody">
                            <tr>
                                <td colspan="15" class="text-center py-5">
                                    <div class="spinner-border spinner-border-sm text-success-custom mb-2" role="status"></div>
                                    <div class="fw-semibold text-muted">Fetching department attendance from Tipsoi...</div>
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
    let dateColumns = [];
    let currentPage = 1;

    // Date range picker setup (Default to last 7 days)
    const rangeInput = document.getElementById("dateRange");
    const end = new Date();
    const start = new Date();
    start.setDate(end.getDate() - 6);

    function formatLocalDate(d) {
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    flatpickr(rangeInput, {
        mode: "range",
        dateFormat: "Y-m-d",
        defaultDate: [start, end],
        maxRange: 31,
        onChange: function(selectedDates) {
            if (selectedDates.length === 2) {
                const dateStr = formatLocalDate(selectedDates[0]) + ' - ' + formatLocalDate(selectedDates[1]);
                loadDepartmentReport(dateStr);
            }
        },
        onClose: function(selectedDates) {
            if (selectedDates.length === 1) {
                const dateStr = formatLocalDate(selectedDates[0]);
                loadDepartmentReport(dateStr);
            }
        }
    });

    // Initial load
    const initRange = formatLocalDate(start) + ' - ' + formatLocalDate(end);
    loadDepartmentReport(initRange);

    // Event listeners
    document.getElementById("employeeSearch").addEventListener("input", applyFilters);
    document.getElementById("statusFilter").addEventListener("change", applyFilters);
    document.getElementById("departmentFilter").addEventListener("change", applyFilters);
    document.getElementById("sortBy").addEventListener("change", applyFilters);
    document.getElementById("perPage").addEventListener("change", function() {
        currentPage = 1;
        renderTable();
    });

    function loadDepartmentReport(rangeStr) {
        const tbody = document.getElementById("reportTbody");
        tbody.innerHTML = `
            <tr>
                <td colspan="15" class="text-center py-5">
                    <div class="spinner-border spinner-border-sm text-success-custom mb-2" role="status"></div>
                    <div class="fw-semibold text-muted">Fetching department attendance from Tipsoi...</div>
                </td>
            </tr>
        `;

        fetch(`<?= $GLOBALS['base_url'] ?>/api/employee/tipsoi-department-data?range=${encodeURIComponent(rangeStr)}`)
            .then(res => res.json())
            .then(res => {
                if (!res.success) {
                    tbody.innerHTML = `<tr><td colspan="15" class="text-center py-4 text-danger fw-semibold">${res.message}</td></tr>`;
                    return;
                }

                dateColumns = res.dates || [];
                allRows = (res.rows || []).map((row, index) => {
                    row.reportPosition = index;
                    return row;
                });

                // Render dynamically generated date columns in the headers
                renderTableHeaders();

                // Populate Department Dropdown dynamically
                populateDepartmentFilter(allRows);

                applyFilters();
            })
            .catch(err => {
                tbody.innerHTML = `<tr><td colspan="15" class="text-center py-4 text-danger fw-semibold">Error: ${err.message}</td></tr>`;
            });
    }

    function renderTableHeaders() {
        const headerDatesRow = document.getElementById("headerDatesRow");
        const headerSubRow = document.getElementById("headerSubRow");

        // Clear existing dynamic headers
        const existingDates = headerDatesRow.querySelectorAll('.dynamic-date-header');
        existingDates.forEach(el => el.remove());
        headerSubRow.innerHTML = '';

        // Add dynamic headers
        dateColumns.forEach(dObj => {
            const th = document.createElement('th');
            th.className = 'text-center dynamic-date-header align-middle';
            th.setAttribute('colspan', '3');
            th.style.backgroundColor = '#198754';
            th.textContent = dObj.display;
            headerDatesRow.appendChild(th);

            // Add subheaders
            headerSubRow.innerHTML += `
                <th class="text-center" style="background-color: #198754; color: white;">Entry</th>
                <th class="text-center" style="background-color: #198754; color: white;">Exit</th>
                <th class="text-center" style="background-color: #198754; color: white;">Hours</th>
            `;
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
            
            // Department filter
            const matchesDept = !deptVal || row.department === deptVal;

            // Status filter (checks if the employee had the selected status on any of the date range columns)
            let matchesStatus = true;
            if (statusVal) {
                matchesStatus = Object.values(row.attendance).some(att => att.status === statusVal);
            }

            return matchesSearch && matchesDept && matchesStatus;
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
            filteredRows.sort((a, b) => String(a.shift).localeCompare(String(b.shift)));
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
            const totalCols = 6 + (dateColumns.length * 3);
            tbody.innerHTML = `<tr><td colspan="${totalCols}" class="text-center py-4 text-muted">No records found matching filters.</td></tr>`;
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

            let rowHtml = `
                <tr>
                    <td class="text-center text-muted fw-semibold">${start + idx + 1}</td>
                    <td class="text-center fw-bold text-dark">${row.id}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            ${avatarHtml}
                            <div>
                                <span class="fw-bold text-success-custom d-block">${row.name}</span>
                                <small class="text-muted d-block" style="font-size: 9px;">${row.designation}, ${row.department}</small>
                            </div>
                        </div>
                    </td>
                    <td class="text-muted fw-semibold">${row.employeeType}</td>
                    <td class="text-muted fw-semibold">${row.shift}</td>
                    <td><span class="badge text-secondary border">${row.workplace}</span></td>
            `;

            // Dynamic date columns for this employee
            dateColumns.forEach(dObj => {
                const dateKey = dObj.db_date;
                const att = row.attendance[dateKey] || { status: 'Absent', entry: '—', exit: '—', hours: '—' };

                if (att.status === 'Absent') {
                    rowHtml += `<td colspan="3" class="text-center font-monospace align-middle status-absent">Absent</td>`;
                } else if (att.status === 'Off Day') {
                    rowHtml += `<td colspan="3" class="text-center font-monospace align-middle status-offday">Off Day</td>`;
                } else if (att.status === 'Leave') {
                    rowHtml += `<td colspan="3" class="text-center font-monospace align-middle status-leave">Leave</td>`;
                } else if (att.status === 'Holiday') {
                    rowHtml += `<td colspan="3" class="text-center font-monospace align-middle status-holiday">Holiday</td>`;
                } else {
                    const entryClass = att.status === 'Late' ? 'status-late' : 'status-present';
                    rowHtml += `
                        <td class="text-center font-monospace align-middle ${entryClass}">${att.entry}</td>
                        <td class="text-center font-monospace align-middle text-secondary">${att.exit}</td>
                        <td class="text-center font-monospace align-middle fw-bold text-dark">${att.hours}</td>
                    `;
                }
            });

            rowHtml += `</tr>`;
            tbody.innerHTML += rowHtml;
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

    // Export Excel Event Listener (CSV format)
    document.getElementById("btnExcel").addEventListener("click", function() {
        let csv = [];
        
        // Build headers row 1 and row 2
        let row1 = ["SL", "ID", "Employee Name", "Employment Type", "Shift Group", "Workplace"];
        let row2 = ["", "", "", "", "", ""];
        
        dateColumns.forEach(dObj => {
            row1.push(dObj.display, "", "");
            row2.push("Entry", "Exit", "Hours");
        });
        
        csv.push(row1.join(","));
        csv.push(row2.join(","));

        filteredRows.forEach((row, index) => {
            let r = [
                index + 1,
                `"${row.id}"`,
                `"${row.name} - ${row.designation}"`,
                `"${row.employeeType}"`,
                `"${row.shift}"`,
                `"${row.workplace}"`
            ];

            dateColumns.forEach(dObj => {
                const dateKey = dObj.db_date;
                const att = row.attendance[dateKey] || { status: 'Absent', entry: '—', exit: '—', hours: '—' };
                if (att.status === 'Absent' || att.status === 'Off Day' || att.status === 'Leave' || att.status === 'Holiday') {
                    r.push(`"${att.status}"`, `"${att.status}"`, `"${att.status}"`);
                } else {
                    r.push(`"${att.entry}"`, `"${att.exit}"`, `"${att.hours}"`);
                }
            });
            csv.push(r.join(","));
        });

        let csvContent = "data:text/csv;charset=utf-8,\ufeff" + csv.join("\n");
        let encodedUri = encodeURI(csvContent);
        let link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "Department_Attendance_Report.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    // Export PDF Event Listener
    document.getElementById("btnPdf").addEventListener("click", function() {
        let title = "Department Attendance Report";
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
                    h2 { color: #198754; font-weight: bold; margin-bottom: 20px; text-transform: uppercase; font-size: 15px; text-align: center; }
                    table { font-size: 8px !important; border-collapse: collapse; width: 100%; }
                    th, td { padding: 4px 6px !important; text-align: center !important; vertical-align: middle !important; border: 1px solid #cbd5e1 !important; }
                    th { background-color: #198754 !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                    .status-absent { color: #dc3545 !important; font-weight: bold; }
                    .status-offday { color: #6f42c1 !important; font-weight: bold; }
                    .status-leave { color: #0dcaf0 !important; font-weight: bold; }
                    .status-holiday { color: #20c997 !important; font-weight: bold; }
                    .status-late { color: #fd7e14 !important; font-weight: bold; }
                    .status-present { color: #198754 !important; font-weight: bold; }
                    @page { size: landscape; margin: 8mm; }
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

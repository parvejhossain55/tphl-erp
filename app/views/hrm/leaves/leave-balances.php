<?php
$page_title = "Tipsoi Leave Balances";
$page = 'Tipsoi Leave Balances';
$show_breadcrumb = true;
?>

<style>
.bg-light-primary {
    background-color: rgba(13, 110, 253, 0.08) !important;
}
.text-primary-custom {
    color: #15a15b !important;
}
.stats-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.stats-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.08) !important;
}
.active-filter {
    border: 2px solid #15a15b !important;
    background-color: #f0fdf4 !important;
}
.leave-type-col {
    background-color: #fcfcfc;
    border-left: 3px solid #e9ecef;
}
.leave-type-col-header {
    background-color: #f8f9fa !important;
    font-weight: 700 !important;
    text-align: center;
}
</style>

<div class="leave-balances-container container-fluid p-0">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-10 mb-25">
        <div>
            <h4 class="fw-bold mb-0 text-dark">Tipsoi Leave Balances</h4>
            <p class="text-muted mb-0" style="font-size: 0.85rem;">View and track entitled, used, and remaining leave balances per employee</p>
        </div>
        <div class="d-flex align-items-center gap-10">
            <button id="btnExportCSV" class="btn btn-outline-success btn-sm fw-semibold">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
            </button>
            <button id="btnExportPDF" class="btn btn-outline-danger btn-sm fw-semibold">
                <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
            </button>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-20 mb-25">
        <div class="col-lg-3 col-md-6 col-12">
            <div class="card__wrapper d-flex align-items-center justify-content-between p-20 border-start border-primary border-4 shadow-sm stats-card" id="cardTotal">
                <div>
                    <h6 class="text-muted text-uppercase mb-2 fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Total Employees</h6>
                    <h2 class="fw-bold text-primary mb-0" id="statTotal">0</h2>
                    <small class="text-muted">Total sync records</small>
                </div>
                <div class="bg-light-primary p-3 rounded-circle text-primary-custom d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-people-fill fs-4"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-12">
            <div class="card__wrapper d-flex align-items-center justify-content-between p-20 border-start border-success border-4 shadow-sm stats-card" id="cardUsed">
                <div>
                    <h6 class="text-muted text-uppercase mb-2 fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Leave Taken</h6>
                    <h2 class="fw-bold text-success mb-0" id="statUsed">0</h2>
                    <small class="text-muted">Taken at least 1 day</small>
                </div>
                <div class="bg-light-success p-3 rounded-circle text-success d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-calendar-check-fill fs-4"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-12">
            <div class="card__wrapper d-flex align-items-center justify-content-between p-20 border-start border-warning border-4 shadow-sm stats-card" id="cardUnused">
                <div>
                    <h6 class="text-muted text-uppercase mb-2 fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">No Leave Taken</h6>
                    <h2 class="fw-bold text-warning mb-0" id="statUnused">0</h2>
                    <small class="text-muted">Full balances remaining</small>
                </div>
                <div class="bg-light-warning p-3 rounded-circle text-warning d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-calendar-fill fs-4"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-12">
            <div class="card__wrapper d-flex align-items-center justify-content-between p-20 border-start border-danger border-4 shadow-sm stats-card">
                <div>
                    <h6 class="text-muted text-uppercase mb-2 fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Sync Status</h6>
                    <h2 class="fw-bold text-danger mb-0" style="font-size: 1.5rem;">Live</h2>
                    <small class="text-muted">Real-time API Sync</small>
                </div>
                <div class="bg-light-danger p-3 rounded-circle text-danger d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-arrow-repeat fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="card__wrapper p-20 shadow-sm mb-20">
        <form id="filterForm">
            <div class="row g-15">
                <div class="col-lg-5 col-md-6 col-12">
                    <label class="form-label fw-bold text-secondary" style="font-size: 0.75rem;">Search Employee</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchQuery" class="form-control border-start-0 ps-0" placeholder="Search by name, office ID, designation...">
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 col-12">
                    <label class="form-label fw-bold text-secondary" style="font-size: 0.75rem;">Filter Department</label>
                    <select class="form-select form-select-sm" id="departmentFilter">
                        <option value="">All Departments</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-12 col-12 d-flex align-items-end">
                    <button type="button" id="btnResetFilters" class="btn btn-sm btn-light w-100 fw-semibold border">
                        <i class="bi bi-eraser me-1"></i> Clear Filters
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Table Grid -->
    <div class="card__wrapper p-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="min-width: 1200px;">
                <thead class="table-light text-secondary">
                    <tr>
                        <th rowspan="2" style="width: 50px; vertical-align: middle;">SN</th>
                        <th rowspan="2" style="width: 100px; vertical-align: middle;">Office ID</th>
                        <th rowspan="2" style="vertical-align: middle;">Full Name</th>
                        <th rowspan="2" style="vertical-align: middle;">Department</th>
                        <th colspan="3" class="leave-type-col-header" style="border-left: 2px solid #dee2e6;">Sick Leave</th>
                        <th colspan="3" class="leave-type-col-header" style="border-left: 2px solid #dee2e6;">Casual Leave</th>
                        <th colspan="3" class="leave-type-col-header" style="border-left: 2px solid #dee2e6;">Earned Leave</th>
                        <th colspan="3" class="leave-type-col-header" style="border-left: 2px solid #dee2e6; border-right: 2px solid #dee2e6;">Leave without Pay (LWP)</th>
                    </tr>
                    <tr>
                        <!-- Sick -->
                        <th style="border-left: 2px solid #dee2e6; font-size: 0.75rem; text-align: center;">Ent.</th>
                        <th style="font-size: 0.75rem; text-align: center;">Used</th>
                        <th style="font-size: 0.75rem; text-align: center;">Bal.</th>
                        <!-- Casual -->
                        <th style="border-left: 2px solid #dee2e6; font-size: 0.75rem; text-align: center;">Ent.</th>
                        <th style="font-size: 0.75rem; text-align: center;">Used</th>
                        <th style="font-size: 0.75rem; text-align: center;">Bal.</th>
                        <!-- Earned -->
                        <th style="border-left: 2px solid #dee2e6; font-size: 0.75rem; text-align: center;">Ent.</th>
                        <th style="font-size: 0.75rem; text-align: center;">Used</th>
                        <th style="font-size: 0.75rem; text-align: center; border-right: 2px solid #dee2e6;">Bal.</th>
                        <!-- LWP -->
                        <th style="border-left: 2px solid #dee2e6; font-size: 0.75rem; text-align: center;">Ent.</th>
                        <th style="font-size: 0.75rem; text-align: center;">Used</th>
                        <th style="font-size: 0.75rem; text-align: center; border-right: 2px solid #dee2e6;">Bal.</th>
                    </tr>
                </thead>
                <tbody id="balancesTableBody">
                    <!-- Loaded dynamically -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-10 mt-20">
        <div class="text-muted fw-semibold" id="paginationSummary">Showing 0 to 0 of 0 entries</div>
        <span class="badge bg-light text-dark border px-3 py-2 fw-semibold" id="totalCountBadge">Total Records: 0</span>
        <nav>
            <ul class="pagination pagination-sm m-0" id="paginationControls">
                <!-- Loaded dynamically -->
            </ul>
        </nav>
    </div>
</div>

<?php ob_start(); ?>
document.addEventListener("DOMContentLoaded", function() {
    const baseUrl = window.base_url || '<?= $GLOBALS['base_url'] ?>';
    
    // Elements
    const tableBody = document.getElementById("balancesTableBody");
    const searchQuery = document.getElementById("searchQuery");
    const departmentFilter = document.getElementById("departmentFilter");
    const btnResetFilters = document.getElementById("btnResetFilters");
    const paginationSummary = document.getElementById("paginationSummary");
    const totalCountBadge = document.getElementById("totalCountBadge");
    const paginationControls = document.getElementById("paginationControls");

    const statTotal = document.getElementById("statTotal");
    const statUsed = document.getElementById("statUsed");
    const statUnused = document.getElementById("statUnused");

    // State Variables
    let allRecords = [];
    let filteredRecords = [];
    let localEmployees = [];
    let normalizedLocalIds = [];
    let normalizedLocalEmails = [];

    let currentPage = 1;
    const limit = 20;

    // Helpers
    const normalizeId = (id) => {
        const str = String(id || '').trim();
        return /^\d+$/.test(str) ? String(parseInt(str, 10)) : str;
    };

    function fetchBalancesData() {
        tableBody.innerHTML = `
            <tr>
                <td colspan="13" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="text-muted mt-2">Fetching leave balances from Tipsoi API...</div>
                </td>
            </tr>
        `;

        fetch(`${baseUrl}/api/hrm/leave-balances`)
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                allRecords = res.data || [];
                localEmployees = res.local_employees || [];
                const localIds = res.local_employee_ids || [];
                const localEmails = res.local_employee_emails || [];
                normalizedLocalIds = localIds.map(id => normalizeId(id));
                normalizedLocalEmails = localEmails.map(email => email.toLowerCase().trim());

                // Process data and populate filters
                populateFilters(allRecords);
                calculateStats(allRecords);
                applyFiltersAndRender(1);
            } else {
                showErrorRow(res.message || "Failed to fetch leave balances.");
            }
        })
        .catch(err => {
            console.error(err);
            showErrorRow(err.message || "An unexpected network error occurred.");
        });
    }

    function calculateStats(records) {
        let totalCount = records.length;
        let takenCount = 0;
        let unusedCount = 0;

        records.forEach(emp => {
            const list = emp.leaveLeaveInfo?.leaveDataList || [];
            let totalUsed = 0;
            list.forEach(item => {
                totalUsed += parseFloat(item.used || 0);
            });

            if (totalUsed > 0) {
                takenCount++;
            } else {
                unusedCount++;
            }
        });

        statTotal.textContent = totalCount;
        statUsed.textContent = takenCount;
        statUnused.textContent = unusedCount;
    }

    function populateFilters(records) {
        const depts = new Set();
        records.forEach(emp => {
            if (emp.departmentName) depts.add(emp.departmentName.trim());
        });

        // Clear existing except first
        departmentFilter.innerHTML = '<option value="">All Departments</option>';
        [...depts].sort().forEach(dept => {
            departmentFilter.innerHTML += `<option value="${dept}">${dept}</option>`;
        });
    }

    function applyFiltersAndRender(page = 1) {
        currentPage = page;
        const searchVal = searchQuery.value.toLowerCase().trim();
        const deptVal = departmentFilter.value;

        filteredRecords = allRecords.filter(emp => {
            const officeId = normalizeId(emp.employeeOfficeId || emp.employeeId);
            const name = String(emp.employeeName || '').toLowerCase();
            const dept = String(emp.departmentName || '').toLowerCase();
            const desig = String(emp.designationName || '').toLowerCase();

            // Match ERP employee email
            const localMatch = localEmployees.find(le => {
                const leId = normalizeId(le.employee_id);
                const leName = String(le.full_name || '').toLowerCase().trim();
                return (officeId && leId === officeId) || (name.trim() && leName === name.trim());
            });
            const email = localMatch ? localMatch.email : '';

            // Search Filter
            const matchesSearch = !searchVal || 
                name.includes(searchVal) ||
                officeId.includes(searchVal) ||
                desig.includes(searchVal) ||
                email.includes(searchVal);

            // Dept Filter
            const matchesDept = !deptVal || emp.departmentName === deptVal;

            return matchesSearch && matchesDept;
        });

        totalCountBadge.textContent = `Total Records: ${filteredRecords.length}`;
        renderTable(filteredRecords);
        renderPagination(filteredRecords.length, currentPage);
    }

    function renderTable(records) {
        const offset = (currentPage - 1) * limit;
        const pageItems = records.slice(offset, offset + limit);

        if (pageItems.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="13" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-2 mb-2 d-block"></i>
                        No leave balance records found matching filters.
                    </td>
                </tr>
            `;
            return;
        }

        let sn = offset + 1;
        tableBody.innerHTML = pageItems.map(emp => {
            // Find ERP matches
            const officeId = normalizeId(emp.employeeOfficeId);
            const matchedLocal = localEmployees.find(le => normalizeId(le.employee_id) === officeId);
            const isRegistered = matchedLocal || (officeId && normalizedLocalIds.includes(officeId));

            const localIndicator = isRegistered
                ? '<i class="bi bi-patch-check-fill text-success ms-1" title="Registered in ERP DB"></i>'
                : '<i class="bi bi-patch-question text-muted ms-1" title="Not in ERP DB"></i>';

            // Extract balances
            const list = emp.leaveLeaveInfo?.leaveDataList || [];
            
            const getBal = (catName) => {
                const item = list.find(l => String(l.leaveCategoryName).toLowerCase().includes(catName.toLowerCase()));
                return {
                    entitled: item ? (item.entitled !== null ? item.entitled : '—') : '—',
                    used: item ? (item.used !== null ? item.used : '—') : '—',
                    balance: item ? (item.balance !== null ? item.balance : '—') : '—'
                };
            };

            const sick = getBal('Sick');
            const casual = getBal('Casual');
            const earned = getBal('Earned');
            const lwp = getBal('without Pay');

            return `
                <tr>
                    <td class="text-muted fw-semibold">${sn++}</td>
                    <td class="fw-semibold text-muted">${emp.employeeOfficeId || '—'}</td>
                    <td class="fw-bold">${emp.employeeName || '—'} ${localIndicator}</td>
                    <td>${emp.departmentName || 'No Department'}</td>
                    <!-- Sick -->
                    <td class="text-center" style="border-left: 2px solid #dee2e6;">${sick.entitled}</td>
                    <td class="text-center text-danger fw-semibold">${sick.used}</td>
                    <td class="text-center text-success fw-bold">${sick.balance}</td>
                    <!-- Casual -->
                    <td class="text-center" style="border-left: 2px solid #dee2e6;">${casual.entitled}</td>
                    <td class="text-center text-danger fw-semibold">${casual.used}</td>
                    <td class="text-center text-success fw-bold">${casual.balance}</td>
                    <!-- Earned -->
                    <td class="text-center" style="border-left: 2px solid #dee2e6;">${earned.entitled}</td>
                    <td class="text-center text-danger fw-semibold">${earned.used}</td>
                    <td class="text-center text-success fw-bold" style="border-right: 2px solid #dee2e6;">${earned.balance}</td>
                    <!-- LWP -->
                    <td class="text-center" style="border-left: 2px solid #dee2e6;">${lwp.entitled}</td>
                    <td class="text-center text-danger fw-semibold">${lwp.used}</td>
                    <td class="text-center text-success fw-bold" style="border-right: 2px solid #dee2e6;">${lwp.balance}</td>
                </tr>
            `;
        }).join('');
    }

    function renderPagination(total, page) {
        const totalPages = Math.ceil(total / limit);
        const startRecord = total > 0 ? ((page - 1) * limit) + 1 : 0;
        const endRecord = Math.min(startRecord + limit - 1, total);

        paginationSummary.textContent = `Showing ${startRecord} to ${endRecord} of ${total} entries`;

        if (totalPages <= 1) {
            paginationControls.innerHTML = '';
            return;
        }

        let html = `
            <li class="page-item ${page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${page - 1}"><i class="bi bi-chevron-left"></i></a>
            </li>
        `;

        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= page - 2 && i <= page + 2)) {
                html += `
                    <li class="page-item ${page === i ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            } else if (i === page - 3 || i === page + 3) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        html += `
            <li class="page-item ${page === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${page + 1}"><i class="bi bi-chevron-right"></i></a>
            </li>
        `;

        paginationControls.innerHTML = html;

        paginationControls.querySelectorAll("a").forEach(a => {
            a.addEventListener("click", function(e) {
                e.preventDefault();
                const targetPage = parseInt(this.getAttribute("data-page"), 10);
                if (targetPage > 0 && targetPage <= totalPages) {
                    applyFiltersAndRender(targetPage);
                }
            });
        });
    }

    function showErrorRow(msg) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="13" class="text-center py-5 text-danger">
                    <i class="bi bi-exclamation-triangle-fill fs-2 mb-2 d-block"></i>
                    ${msg}
                </td>
            </tr>
        `;
    }

    // Export Functions
    function exportToCSV() {
        if (filteredRecords.length === 0) {
            alert("No data available to export.");
            return;
        }

        let csv = [];
        csv.push(",,,,The Premium Homes Ltd");
        csv.push(",,,,Employee Leave Balances Report");
        csv.push(`,,,,Generated On: ${new Date().toLocaleString()}`);
        csv.push("");
        
        // Header
        csv.push("SN,Office ID,Employee Name,Department,Sick Entitled,Sick Used,Sick Balance,Casual Entitled,Casual Used,Casual Balance,Earned Entitled,Earned Used,Earned Balance,LWP Entitled,LWP Used,LWP Balance");

        let sn = 1;
        filteredRecords.forEach(emp => {
            const list = emp.leaveLeaveInfo?.leaveDataList || [];
            
            const getBal = (catName) => {
                const item = list.find(l => String(l.leaveCategoryName).toLowerCase().includes(catName.toLowerCase()));
                return {
                    entitled: item ? (item.entitled !== null ? item.entitled : '0') : '0',
                    used: item ? (item.used !== null ? item.used : '0') : '0',
                    balance: item ? (item.balance !== null ? item.balance : '0') : '0'
                };
            };

            const sick = getBal('Sick');
            const casual = getBal('Casual');
            const earned = getBal('Earned');
            const lwp = getBal('without Pay');

            const row = [
                sn++,
                emp.employeeOfficeId || '—',
                emp.employeeName || '—',
                emp.departmentName || 'No Department',
                sick.entitled, sick.used, sick.balance,
                casual.entitled, casual.used, casual.balance,
                earned.entitled, earned.used, earned.balance,
                lwp.entitled, lwp.used, lwp.balance
            ].map(val => {
                const stringVal = String(val);
                return (stringVal.includes(",") || stringVal.includes("\n")) 
                    ? `"${stringVal.replace(/"/g, '""')}"` 
                    : stringVal;
            });
            csv.push(row.join(","));
        });

        const csvString = csv.join("\n");
        const blob = new Blob(["\uFEFF" + csvString], { type: "text/csv;charset=utf-8;" });
        const link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.download = `Employee_Leave_Balances_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function exportToPDF() {
        if (filteredRecords.length === 0) {
            alert("No data available to print.");
            return;
        }

        let sn = 1;
        const rowsHtml = filteredRecords.map(emp => {
            const list = emp.leaveLeaveInfo?.leaveDataList || [];
            
            const getBal = (catName) => {
                const item = list.find(l => String(l.leaveCategoryName).toLowerCase().includes(catName.toLowerCase()));
                return {
                    entitled: item ? (item.entitled !== null ? item.entitled : '0') : '0',
                    used: item ? (item.used !== null ? item.used : '0') : '0',
                    balance: item ? (item.balance !== null ? item.balance : '0') : '0'
                };
            };

            const sick = getBal('Sick');
            const casual = getBal('Casual');
            const earned = getBal('Earned');
            const lwp = getBal('without Pay');

            return `
                <tr>
                    <td>${sn++}</td>
                    <td>${emp.employeeOfficeId || '—'}</td>
                    <td style="font-weight: 600;">${emp.employeeName || '—'}</td>
                    <td>${emp.departmentName || 'No Department'}</td>
                    <td class="text-center">${sick.entitled}</td>
                    <td class="text-center text-danger">${sick.used}</td>
                    <td class="text-center text-success" style="font-weight: 600;">${sick.balance}</td>
                    <td class="text-center">${casual.entitled}</td>
                    <td class="text-center text-danger">${casual.used}</td>
                    <td class="text-center text-success" style="font-weight: 600;">${casual.balance}</td>
                    <td class="text-center">${earned.entitled}</td>
                    <td class="text-center text-danger">${earned.used}</td>
                    <td class="text-center text-success" style="font-weight: 600;">${earned.balance}</td>
                    <td class="text-center">${lwp.entitled}</td>
                    <td class="text-center text-danger">${lwp.used}</td>
                    <td class="text-center text-success" style="font-weight: 600;">${lwp.balance}</td>
                </tr>
            `;
        }).join('');

        const printWindow = window.open("", "_blank");
        
        let htmlStr = '';
        htmlStr += '<!DOCTYPE html>';
        htmlStr += '<html>';
        htmlStr += '<head>';
        htmlStr += '    <title>Employee_Leave_Balances_' + new Date().toISOString().split('T')[0] + '</title>';
        htmlStr += '    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">';
        htmlStr += '    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">';
        htmlStr += '    <style>';
        htmlStr += '        @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap");';
        htmlStr += '        body { font-family: "Inter", sans-serif; color: #333; margin: 30px; }';
        htmlStr += '        .report-header { border-bottom: 2px solid #15a15b; padding-bottom: 15px; margin-bottom: 25px; text-align: center; }';
        htmlStr += '        .company-name { font-size: 26px; font-weight: 800; color: #15a15bff; margin: 0; }';
        htmlStr += '        .report-title { font-size: 18px; font-weight: 600; color: #495057; margin: 5px 0 0 0; }';
        htmlStr += '        .meta-info { font-size: 12px; color: #6c757d; margin-top: 10px; }';
        htmlStr += '        .table-custom th { background-color: #f8f9fa !important; font-weight: 600; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }';
        htmlStr += '        .table-custom td { font-size: 10px; }';
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
        htmlStr += '        <h2 class="report-title">Employee Leave Balances Report</h2>';
        htmlStr += '        <div class="meta-info">';
        htmlStr += '            <strong>Generated On:</strong> ' + new Date().toLocaleString();
        htmlStr += '        </div>';
        htmlStr += '    </div>';
        htmlStr += '    ';
        htmlStr += '    <table class="table table-bordered table-striped table-custom align-middle">';
        htmlStr += '        <thead>';
        htmlStr += '            <tr>';
        htmlStr += '                <th rowspan="2" style="vertical-align: middle;">SN</th>';
        htmlStr += '                <th rowspan="2" style="vertical-align: middle;">Office ID</th>';
        htmlStr += '                <th rowspan="2" style="vertical-align: middle;">Employee Name</th>';
        htmlStr += '                <th rowspan="2" style="vertical-align: middle;">Department</th>';
        htmlStr += '                <th colspan="3" class="text-center">Sick Leave</th>';
        htmlStr += '                <th colspan="3" class="text-center">Casual Leave</th>';
        htmlStr += '                <th colspan="3" class="text-center">Earned Leave</th>';
        htmlStr += '                <th colspan="3" class="text-center">Leave without Pay (LWP)</th>';
        htmlStr += '            </tr>';
        htmlStr += '            <tr>';
        htmlStr += '                <th class="text-center">Ent.</th><th class="text-center">Used</th><th class="text-center">Bal.</th>';
        htmlStr += '                <th class="text-center">Ent.</th><th class="text-center">Used</th><th class="text-center">Bal.</th>';
        htmlStr += '                <th class="text-center">Ent.</th><th class="text-center">Used</th><th class="text-center">Bal.</th>';
        htmlStr += '                <th class="text-center">Ent.</th><th class="text-center">Used</th><th class="text-center">Bal.</th>';
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

    // Reset Filters
    btnResetFilters.addEventListener("click", () => {
        searchQuery.value = "";
        departmentFilter.value = "";
        applyFiltersAndRender(1);
    });

    // Event Triggers
    searchQuery.addEventListener("input", () => applyFiltersAndRender(1));
    departmentFilter.addEventListener("change", () => applyFiltersAndRender(1));

    btnExportCSV.addEventListener("click", exportToCSV);
    btnExportPDF.addEventListener("click", exportToPDF);

    // Initial Fetch
    fetchBalancesData();
});
<?php $page_script = ob_get_clean(); ?>


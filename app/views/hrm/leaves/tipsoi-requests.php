<?php
$page_title = "All Pending Requests";
$page = 'All Pending Requests';
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
.selfie-thumbnail {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid #dee2e6;
    cursor: pointer;
    transition: transform 0.2s;
}
.selfie-thumbnail:hover {
    transform: scale(1.1);
}
</style>

<div class="requests-container container-fluid p-0">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-10 mb-25">
        <div>
            <h4 class="fw-bold mb-0 text-dark">All Pending Requests</h4>
            <p class="text-muted mb-0" style="font-size: 0.85rem;">Review and manage pending leave applications, manual attendance corrections, late excuses, and field visits from Tipsoi</p>
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
            <div class="card__wrapper d-flex align-items-center justify-content-between p-20 border-start border-primary border-4 shadow-sm stats-card" style="cursor: pointer;" id="cardTotal">
                <div>
                    <h6 class="text-muted text-uppercase mb-2 fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Total Pending</h6>
                    <h2 class="fw-bold text-primary mb-0" id="statTotal">0</h2>
                    <small class="text-muted">Click to view all</small>
                </div>
                <div class="bg-light-primary p-3 rounded-circle text-primary-custom d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-collection-play-fill fs-4"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-12">
            <div class="card__wrapper d-flex align-items-center justify-content-between p-20 border-start border-warning border-4 shadow-sm stats-card" style="cursor: pointer;" id="cardLeaves">
                <div>
                    <h6 class="text-muted text-uppercase mb-2 fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Pending Leaves</h6>
                    <h2 class="fw-bold text-warning mb-0" id="statLeaves">0</h2>
                    <small class="text-muted">Click to filter leaves</small>
                </div>
                <div class="bg-light-warning p-3 rounded-circle text-warning d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-calendar-event-fill fs-4"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-12">
            <div class="card__wrapper d-flex align-items-center justify-content-between p-20 border-start border-info border-4 shadow-sm stats-card" style="cursor: pointer;" id="cardCorrections">
                <div>
                    <h6 class="text-muted text-uppercase mb-2 fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Corrections</h6>
                    <h2 class="fw-bold text-info mb-0" id="statCorrections">0</h2>
                    <small class="text-muted">Click to filter manual</small>
                </div>
                <div class="bg-light-info p-3 rounded-circle text-info d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-pencil-square fs-4"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-12">
            <div class="card__wrapper d-flex align-items-center justify-content-between p-20 border-start border-danger border-4 shadow-sm stats-card" style="cursor: pointer;" id="cardExcuses">
                <div>
                    <h6 class="text-muted text-uppercase mb-2 fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Excuses & Visits</h6>
                    <h2 class="fw-bold text-danger mb-0" id="statExcuses">0</h2>
                    <small class="text-muted">Click to filter others</small>
                </div>
                <div class="bg-light-danger p-3 rounded-circle text-danger d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-alarm-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="card__wrapper p-20 shadow-sm mb-20">
        <form id="filterForm">
            <div class="row g-15 align-items-end">
                <div class="col-lg-3 col-md-6 col-12">
                    <label class="form-label fw-bold text-secondary" style="font-size: 0.75rem;">Request Type</label>
                    <select class="form-select form-select-sm" id="requestTypeFilter">
                        <option value="all_pending">All Pending Requests</option>
                        <option value="my_approvals">Pending My Approval Only</option>
                        <option value="pending_leaves">Pending Leave Requests</option>
                        <option value="manual_entry">Manual Attendance Correction</option>
                        <option value="late_excuse">Late Excuse Requests</option>
                        <option value="field_visit">Field Visit / Outdoor Requests</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <label class="form-label fw-bold text-secondary" style="font-size: 0.75rem;">Search Employee</label>
                    <input type="text" id="searchQuery" class="form-control form-control-sm" placeholder="Search by name, ID, designation...">
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <label class="form-label fw-bold text-secondary" style="font-size: 0.75rem;">Filter Department</label>
                    <select class="form-select form-select-sm" id="departmentFilter">
                        <option value="">All Departments</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-6 col-12 d-flex gap-10">
                    <button type="button" id="btnSyncTipsoi" class="btn btn-sm btn-primary w-100 fw-semibold">
                        <i class="bi bi-arrow-clockwise me-1"></i> Sync from Tipsoi
                    </button>
                    <button type="button" id="btnResetFilters" class="btn btn-sm btn-light border" title="Clear Filters">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Table Grid -->
    <div class="card__wrapper p-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="requestsTable" style="min-width: 1200px;">
                <thead class="table-light text-secondary" id="tableHeader">
                    <!-- Loaded dynamically based on request type -->
                </thead>
                <tbody id="requestsTableBody">
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
                <!-- Dynamic pagination -->
            </ul>
        </nav>
    </div>
</div>

<!-- Modal to View Punch Selfie -->
<div class="modal fade" id="selfieModal" tabindex="-1" aria-labelledby="selfieModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="selfieModalLabel">Punch Verification Selfie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-20 bg-light">
                <img id="selfieImage" src="" alt="Selfie" class="img-fluid rounded shadow-sm" style="max-height: 450px;">
                <div class="mt-15">
                    <h6 class="fw-bold mb-5" id="selfieEmpName">Employee Name</h6>
                    <span class="badge bg-secondary mb-10" id="selfieOfficeId">ID: 0000</span>
                    <p class="text-muted mb-0" style="font-size: 0.8rem;" id="selfieDateTime">Time</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
document.addEventListener("DOMContentLoaded", function() {
    const baseUrl = window.base_url || '<?= $GLOBALS['base_url'] ?>';

    // Form inputs
    const requestTypeFilter = document.getElementById("requestTypeFilter");
    const btnSyncTipsoi = document.getElementById("btnSyncTipsoi");
    const btnResetFilters = document.getElementById("btnResetFilters");
    const btnExportCSV = document.getElementById("btnExportCSV");
    const btnExportPDF = document.getElementById("btnExportPDF");

    const searchQuery = document.getElementById("searchQuery");
    const departmentFilter = document.getElementById("departmentFilter");

    // Grid elements
    const tableHeader = document.getElementById("tableHeader");
    const tableBody = document.getElementById("requestsTableBody");
    const paginationSummary = document.getElementById("paginationSummary");
    const totalCountBadge = document.getElementById("totalCountBadge");
    const paginationControls = document.getElementById("paginationControls");

    // Stats
    const statTotal = document.getElementById("statTotal");
    const statLeaves = document.getElementById("statLeaves");
    const statCorrections = document.getElementById("statCorrections");
    const statExcuses = document.getElementById("statExcuses");

    const cardTotal = document.getElementById("cardTotal");
    const cardLeaves = document.getElementById("cardLeaves");
    const cardCorrections = document.getElementById("cardCorrections");
    const cardExcuses = document.getElementById("cardExcuses");

    // State Variables
    let allRecords = [];
    let filteredRecords = [];
    let localEmployees = [];
    let loggedUserApproverInfo = null;
    let normalizedLocalIds = [];
    let normalizedLocalEmails = [];

    let currentPage = 1;
    const limit = 20;

    // Helpers
    const normalizeId = (id) => {
        const str = String(id || '').trim();
        return /^\d+$/.test(str) ? String(parseInt(str, 10)) : str;
    };

    function fetchRequestsData() {
        // Render loading state
        tableBody.innerHTML = `
            <tr>
                <td colspan="12" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="text-muted mt-2">Fetching pending requests from Tipsoi API...</div>
                </td>
            </tr>
        `;

        fetch(`${baseUrl}/api/hrm/tipsoi-requests`)
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                allRecords = res.data || [];
                localEmployees = res.local_employees || [];
                loggedUserApproverInfo = res.logged_user || null;
                const localIds = res.local_employee_ids || [];
                const localEmails = res.local_employee_emails || [];
                normalizedLocalIds = localIds.map(id => normalizeId(id));
                normalizedLocalEmails = localEmails.map(email => email.toLowerCase().trim());

                // Set up filters and render
                populateFilters(allRecords);
                calculateStats(allRecords);
                applyFiltersAndRender(1);
            } else {
                showErrorRow(res.message || "Failed to retrieve requests.");
            }
        })
        .catch(err => {
            console.error(err);
            showErrorRow(err.message || "An unexpected error occurred.");
        });
    }

    function calculateStats(records) {
        let total = records.length;
        let leaves = records.filter(r => r.requestType === 'Leave Request').length;
        let corrections = records.filter(r => r.requestType === 'Manual Entry').length;
        let others = records.filter(r => r.requestType === 'Late Excuse' || r.requestType === 'Field Visit').length;

        statTotal.textContent = total;
        statLeaves.textContent = leaves;
        statCorrections.textContent = corrections;
        statExcuses.textContent = others;
    }

    function populateFilters(records) {
        const depts = new Set();
        records.forEach(rec => {
            if (rec.departmentName) depts.add(rec.departmentName.trim());
        });

        departmentFilter.innerHTML = '<option value="">All Departments</option>';
        [...depts].sort().forEach(dept => {
            departmentFilter.innerHTML += `<option value="${dept}">${dept}</option>`;
        });
    }

    function applyFiltersAndRender(page = 1) {
        currentPage = page;
        const searchVal = searchQuery.value.toLowerCase().trim();
        const deptVal = departmentFilter.value;
        const reqTypeVal = requestTypeFilter.value;

        filteredRecords = allRecords.filter(rec => {
            const officeId = normalizeId(rec.employeeOfficeId);
            const name = String(rec.employeeName || '').toLowerCase();
            const dept = String(rec.departmentName || '').toLowerCase();
            const desig = String(rec.designationName || '').toLowerCase();

            // Match ERP employee email
            const localMatch = localEmployees.find(le => {
                const leId = normalizeId(le.employee_id);
                const leName = String(le.full_name || '').toLowerCase().trim();
                return (officeId && leId === officeId) || (name.trim() && leName === name.trim());
            });
            const email = localMatch ? localMatch.email : '';

            // Search query filter
            const matchesSearch = !searchVal ||
                name.includes(searchVal) ||
                officeId.includes(searchVal) ||
                desig.includes(searchVal) ||
                email.includes(searchVal);

            // Department filter
            const matchesDept = !deptVal || rec.departmentName === deptVal;

            // Request Type filter
            let matchesType = true;
            if (reqTypeVal === 'my_approvals') {
                const loggedUser = loggedUserApproverInfo;
                const userName = loggedUser ? (loggedUser.full_name || '').toLowerCase().trim() : '';
                const userOfficeId = loggedUser ? (loggedUser.employee_id || loggedUser.tipsoi_office_id || '').trim() : '';
                const userTipsoiId = loggedUser ? String(loggedUser.tipsoi_id || '') : '';

                const firstAppName = String(rec.firstApproverName || '').toLowerCase().trim();
                const firstAppId = String(rec.firstApproverId || '');
                const firstStatus = rec.firstApprovalStatus;

                const secondAppName = String(rec.secondApproverName || '').toLowerCase().trim();
                const secondAppId = String(rec.secondApproverId || '');
                const secondStatus = rec.secondApprovalStatus;

                const isFirstApprover = (userTipsoiId && firstAppId && userTipsoiId === firstAppId) ||
                    (userName && firstAppName && (userName.includes(firstAppName) || firstAppName.includes(userName))) ||
                    (userOfficeId && firstAppName && firstAppName.includes(userOfficeId));

                const isSecondApprover = (userTipsoiId && secondAppId && userTipsoiId === secondAppId) ||
                    (userName && secondAppName && (userName.includes(secondAppName) || secondAppName.includes(userName))) ||
                    (userOfficeId && secondAppName && secondAppName.includes(userOfficeId));

                matchesType = (isFirstApprover && firstStatus == 0) || (isSecondApprover && secondStatus == 0);
            } else if (reqTypeVal === 'pending_leaves') {
                matchesType = rec.requestType === 'Leave Request';
            } else if (reqTypeVal === 'manual_entry') {
                matchesType = rec.requestType === 'Manual Entry';
            } else if (reqTypeVal === 'late_excuse') {
                matchesType = rec.requestType === 'Late Excuse';
            } else if (reqTypeVal === 'field_visit') {
                matchesType = rec.requestType === 'Field Visit';
            }

            return matchesSearch && matchesDept && matchesType;
        });

        totalCountBadge.textContent = `Total Records: ${filteredRecords.length}`;
        renderTableHeader();
        renderTableRows(filteredRecords);
        renderPagination(filteredRecords.length, currentPage);
    }

    function renderTableHeader() {
        const reqType = requestTypeFilter.value;
        if (reqType === 'pending_leaves') {
            tableHeader.innerHTML = `
                <tr>
                    <th style="width: 50px;">SN</th>
                    <th style="width: 100px;">Office ID</th>
                    <th>Employee Name</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th>Leave Period</th>
                    <th style="width: 120px;">Category</th>
                    <th style="width: 300px;">Reason</th>
                    <th>First Approver</th>
                    <th>Second Approver</th>
                    <th style="width: 110px;">Status</th>
                </tr>
            `;
        } else if (reqType === 'all_pending') {
            tableHeader.innerHTML = `
                <tr>
                    <th style="width: 50px;">SN</th>
                    <th style="width: 100px;">Office ID</th>
                    <th>Employee Name</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th>Request Type</th>
                    <th>Period</th>
                    <th style="width: 250px;">Reason</th>
                    <th>First Approver</th>
                    <th>Second Approver</th>
                    <th style="width: 110px;">Status</th>
                </tr>
            `;
        } else {
            tableHeader.innerHTML = `
                <tr>
                    <th style="width: 50px;">SN</th>
                    <th style="width: 100px;">Office ID</th>
                    <th>Employee Name</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th>Period</th>
                    <th style="width: 90px;">Punch In</th>
                    <th style="width: 90px;">Punch Out</th>
                    <th style="width: 200px;">Reason</th>
                    <th>First Approver</th>
                    <th>Second Approver</th>
                    <th style="width: 110px;">Status</th>
                </tr>
            `;
        }
    }

    function renderTableRows(records) {
        const offset = (currentPage - 1) * limit;
        const pageItems = records.slice(offset, offset + limit);
        const reqType = requestTypeFilter.value;

        const totalCols = (reqType === 'pending_leaves') ? 11 : ((reqType === 'all_pending') ? 11 : 12);

        if (pageItems.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="${totalCols}" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-2 mb-2 d-block"></i>
                        No requests found matching filters.
                    </td>
                </tr>
            `;
            return;
        }

        let sn = offset + 1;
        tableBody.innerHTML = pageItems.map(rec => {
            const officeId = normalizeId(rec.employeeOfficeId);
            const matchedLocal = officeId ? localEmployees.find(le => {
                const leId = normalizeId(le.employee_id);
                return leId && leId === officeId;
            }) : null;
            const isRegistered = !!matchedLocal || (officeId && normalizedLocalIds.includes(officeId));

            const localIndicator = isRegistered
                ? '<i class="bi bi-patch-check-fill text-success ms-1" title="Registered in ERP DB"></i>'
                : '<i class="bi bi-patch-question text-muted ms-1" title="Not in ERP DB"></i>';

            let statusBadge = '<span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-hourglass-split me-1"></i>Pending</span>';

            const getSubBadge = (status, name) => {
                if (!name) return '—';
                if (status === 1) return `<div class="text-success"><i class="bi bi-check-circle-fill me-1"></i><small class="fw-semibold">${name}</small></div>`;
                if (status === 2) return `<div class="text-danger"><i class="bi bi-x-circle-fill me-1"></i><small class="fw-semibold">${name}</small></div>`;
                return `<div class="text-warning"><i class="bi bi-hourglass-split me-1"></i><small class="fw-semibold">${name} (Pending)</small></div>`;
            };

            const firstApp = getSubBadge(rec.firstApprovalStatus, rec.firstApproverName);
            const secondApp = getSubBadge(rec.secondApprovalStatus, rec.secondApproverName);

            const datePeriod = (rec.startDate === rec.endDate) 
                ? rec.startDate 
                : `${rec.startDate} to ${rec.endDate}`;

            if (reqType === 'pending_leaves') {
                return `
                    <tr>
                        <td class="text-muted fw-semibold">${sn++}</td>
                        <td class="fw-semibold text-muted">${rec.employeeOfficeId || '—'}</td>
                        <td class="fw-bold">${rec.employeeName} ${localIndicator}</td>
                        <td>${rec.departmentName}</td>
                        <td><span class="badge bg-light text-dark border">${rec.designationName}</span></td>
                        <td class="text-secondary fw-semibold">${datePeriod}</td>
                        <td><span class="badge bg-light-primary text-primary fw-semibold" style="font-size: 0.7rem;">${rec.subType}</span></td>
                        <td class="text-muted" style="max-width: 300px; word-wrap: break-word;">${rec.reason || '—'}</td>
                        <td>${firstApp}</td>
                        <td>${secondApp}</td>
                        <td>${statusBadge}</td>
                    </tr>
                `;
            } else if (reqType === 'all_pending') {
                return `
                    <tr>
                        <td class="text-muted fw-semibold">${sn++}</td>
                        <td class="fw-semibold text-muted">${rec.employeeOfficeId || '—'}</td>
                        <td class="fw-bold">${rec.employeeName} ${localIndicator}</td>
                        <td>${rec.departmentName}</td>
                        <td><span class="badge bg-light text-dark border">${rec.designationName}</span></td>
                        <td><span class="badge bg-light-primary text-primary fw-semibold" style="font-size: 0.7rem;">${rec.subType}</span></td>
                        <td class="text-secondary fw-semibold">${datePeriod}</td>
                        <td class="text-muted" style="max-width: 250px; word-wrap: break-word;">${rec.reason || '—'}</td>
                        <td>${firstApp}</td>
                        <td>${secondApp}</td>
                        <td>${statusBadge}</td>
                    </tr>
                `;
            } else {
                return `
                    <tr>
                        <td class="text-muted fw-semibold">${sn++}</td>
                        <td class="fw-semibold text-muted">${rec.employeeOfficeId || '—'}</td>
                        <td class="fw-bold">${rec.employeeName} ${localIndicator}</td>
                        <td>${rec.departmentName}</td>
                        <td><span class="badge bg-light text-dark border">${rec.designationName}</span></td>
                        <td class="text-secondary fw-semibold">${datePeriod}</td>
                        <td class="text-secondary fw-semibold">${rec.entryTime}</td>
                        <td class="text-secondary fw-semibold">${rec.exitTime}</td>
                        <td class="text-muted" style="max-width: 250px; word-wrap: break-word;">
                            <span class="badge bg-light-primary text-primary fw-semibold mb-1" style="font-size: 0.65rem;">${rec.subType}</span>
                            <div class="small">${rec.reason || '—'}</div>
                        </td>
                        <td>${firstApp}</td>
                        <td>${secondApp}</td>
                        <td>${statusBadge}</td>
                    </tr>
                `;
            }
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
                <td colspan="12" class="text-center py-5 text-danger">
                    <i class="bi bi-exclamation-triangle-fill fs-2 mb-2 d-block"></i>
                    ${msg}
                </td>
            </tr>
        `;
    }

    function setupStatsCardListeners() {
        const cards = [
            { el: cardTotal, type: "all_pending" },
            { el: cardLeaves, type: "pending_leaves" },
            { el: cardCorrections, type: "manual_entry" },
            { el: cardExcuses, type: "late_excuse" }
        ];

        cards.forEach(card => {
            card.el.addEventListener("click", function () {
                cards.forEach(c => c.el.classList.remove("active-filter"));
                card.el.classList.add("active-filter");
                requestTypeFilter.value = card.type;
                applyFiltersAndRender(1);
            });
        });
    }

    // Export Functions
    function exportToCSV() {
        if (filteredRecords.length === 0) {
            alert("No data available to export.");
            return;
        }

        let csv = [];
        csv.push(",,,,The Premium Homes Ltd");
        csv.push(`,,,,All Pending Requests (${requestTypeFilter.options[requestTypeFilter.selectedIndex].text})`);
        csv.push(`,,,,Generated On: ${new Date().toLocaleDateString()}`);
        csv.push("");

        csv.push("SN,Office ID,Employee Name,Department,Designation,Period,Punch In,Punch Out,Area/Type,Reason,First Approver,Second Approver,Status");
        let sn = 1;
        filteredRecords.forEach(rec => {
            const datePeriod = (rec.startDate === rec.endDate) ? rec.startDate : `${rec.startDate} to ${rec.endDate}`;
            const firstApproval = rec.firstApproverName 
                ? `${rec.firstApproverName} (${rec.firstApprovalStatus === 1 ? 'Approved' : (rec.firstApprovalStatus === 2 ? 'Rejected' : 'Pending')})` 
                : '—';
            const secondApproval = rec.secondApproverName 
                ? `${rec.secondApproverName} (${rec.secondApprovalStatus === 1 ? 'Approved' : (rec.secondApprovalStatus === 2 ? 'Rejected' : 'Pending')})` 
                : '—';

            const row = [
                sn++,
                rec.employeeOfficeId || '—',
                rec.employeeName || '—',
                rec.departmentName,
                rec.designationName,
                datePeriod,
                rec.entryTime,
                rec.exitTime,
                rec.requestType,
                rec.reason || '—',
                firstApproval,
                secondApproval,
                "Pending"
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
        link.download = `All_Pending_Requests_${requestTypeFilter.value}_${new Date().toISOString().slice(0,10)}.csv`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function exportToPDF() {
        if (filteredRecords.length === 0) {
            alert("No data available to print.");
            return;
        }

        const typeLabel = requestTypeFilter.options[requestTypeFilter.selectedIndex].text;

        let sn = 1;
        let rowsHtml = filteredRecords.map(rec => {
            const datePeriod = (rec.startDate === rec.endDate) ? rec.startDate : `${rec.startDate} to ${rec.endDate}`;
            const firstApproval = rec.firstApproverName 
                ? `${rec.firstApproverName} (${rec.firstApprovalStatus === 1 ? 'Approved' : (rec.firstApprovalStatus === 2 ? 'Rejected' : 'Pending')})` 
                : '—';
            const secondApproval = rec.secondApproverName 
                ? `${rec.secondApproverName} (${rec.secondApprovalStatus === 1 ? 'Approved' : (rec.secondApprovalStatus === 2 ? 'Rejected' : 'Pending')})` 
                : '—';
            return `
                <tr>
                    <td>${sn++}</td>
                    <td>${rec.employeeOfficeId || '—'}</td>
                    <td style="font-weight: 600;">${rec.employeeName}</td>
                    <td>${rec.departmentName}</td>
                    <td>${rec.designationName}</td>
                    <td>${datePeriod}</td>
                    <td>${rec.entryTime}</td>
                    <td>${rec.exitTime}</td>
                    <td><small>${rec.reason || '—'}</small></td>
                    <td><small>${firstApproval}</small></td>
                    <td><small>${secondApproval}</small></td>
                    <td>Pending</td>
                </tr>
            `;
        }).join('');

        const printWindow = window.open("", "_blank");
        
        let htmlStr = '';
        htmlStr += '<!DOCTYPE html>';
        htmlStr += '<html>';
        htmlStr += '<head>';
        htmlStr += '    <title>Pending_Requests_Report</title>';
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
        htmlStr += '        <h2 class="report-title">All Pending Requests (' + typeLabel + ')</h2>';
        htmlStr += '        <div class="meta-info">';
        htmlStr += '            <strong>Generated On:</strong> ' + new Date().toLocaleString();
        htmlStr += '        </div>';
        htmlStr += '    </div>';
        htmlStr += '    ';
        htmlStr += '    <table class="table table-bordered table-striped table-custom align-middle">';
        htmlStr += '        <thead>';
        htmlStr += '            <tr>';
        htmlStr += '                <th>SN</th>';
        htmlStr += '                <th>Office ID</th>';
        htmlStr += '                <th>Employee Name</th>';
        htmlStr += '                <th>Department</th>';
        htmlStr += '                <th>Designation</th>';
        htmlStr += '                <th>Period</th>';
        htmlStr += '                <th>Punch In</th>';
        htmlStr += '                <th>Punch Out</th>';
        htmlStr += '                <th>Reason</th>';
        htmlStr += '                <th>First Approver</th>';
        htmlStr += '                <th>Second Approver</th>';
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

    // Event listeners
    requestTypeFilter.addEventListener("change", () => {
        applyFiltersAndRender(1);
    });

    btnSyncTipsoi.addEventListener("click", function(e) {
        e.preventDefault();
        fetchRequestsData();
    });

    btnResetFilters.addEventListener("click", () => {
        searchQuery.value = "";
        departmentFilter.value = "";
        requestTypeFilter.value = "all_pending";
        document.querySelectorAll(".stats-card").forEach(c => c.classList.remove("active-filter"));
        document.getElementById("cardTotal").classList.add("active-filter");
        applyFiltersAndRender(1);
    });

    searchQuery.addEventListener("input", () => applyFiltersAndRender(1));
    departmentFilter.addEventListener("change", () => applyFiltersAndRender(1));

    btnExportCSV.addEventListener("click", exportToCSV);
    btnExportPDF.addEventListener("click", exportToPDF);

    // Initial load
    setupStatsCardListeners();
    document.getElementById("cardTotal").classList.add("active-filter");
    fetchRequestsData();
});
<?php $page_script = ob_get_clean(); ?>

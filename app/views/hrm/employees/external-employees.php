<?php
$page_title = "Tipsoi Employees (External)";
$page = 'Tipsoi Employees';
$show_breadcrumb = true;
?>

<style>
.bg-light-primary { background-color: rgba(13, 110, 253, 0.08) !important; }
.bg-light-success { background-color: rgba(25, 135, 84, 0.08) !important; }
.bg-light-danger { background-color: rgba(220, 53, 69, 0.08) !important; }

.text-primary-dark { color: #0d6efd !important; }
.text-success-dark { color: #198754 !important; }
.text-danger-dark { color: #dc3545 !important; }

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

<!-- Stats Cards Row -->
<div class="row g-20 mb-20">
    <!-- Total Tipsoi Employees -->
    <div class="col-lg-4 col-md-6 col-12">
        <div class="card__wrapper d-flex align-items-center justify-content-between p-20 border-start border-primary border-4 shadow-sm stats-card active-filter" style="cursor: pointer;" id="cardTotalEmployees">
            <div>
                <h6 class="text-muted text-uppercase mb-2 fw-semibold" style="font-size: 0.8rem; letter-spacing: 0.5px;">Total Tipsoi Employees</h6>
                <h2 class="fw-bold text-dark mb-0" id="statTotalEmployees">0</h2>
                <small class="text-muted">Click to view all</small>
            </div>
            <div class="bg-light-primary p-3 rounded-circle text-primary-dark d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                <i class="bi bi-people-fill fs-4"></i>
            </div>
        </div>
    </div>
    <!-- Registered in ERP -->
    <div class="col-lg-4 col-md-6 col-12">
        <div class="card__wrapper d-flex align-items-center justify-content-between p-20 border-start border-success border-4 shadow-sm stats-card" style="cursor: pointer;" id="cardRegisteredEmployees">
            <div>
                <h6 class="text-muted text-uppercase mb-2 fw-semibold" style="font-size: 0.8rem; letter-spacing: 0.5px;">Registered in ERP</h6>
                <h2 class="fw-bold text-success mb-0" id="statRegisteredEmployees">0</h2>
                <small class="text-muted">Click to view matching</small>
            </div>
            <div class="bg-light-success p-3 rounded-circle text-success-dark d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                <i class="bi bi-patch-check-fill fs-4"></i>
            </div>
        </div>
    </div>
    <!-- Missing in ERP (Not Registered) -->
    <div class="col-lg-4 col-md-6 col-12">
        <div class="card__wrapper d-flex align-items-center justify-content-between p-20 border-start border-danger border-4 shadow-sm stats-card" style="cursor: pointer;" id="cardMissingEmployees">
            <div>
                <h6 class="text-muted text-uppercase mb-2 fw-semibold" style="font-size: 0.8rem; letter-spacing: 0.5px;">Missing in ERP</h6>
                <h2 class="fw-bold text-danger mb-0" id="statMissingEmployees">0</h2>
                <small class="text-muted">Click to view missing list</small>
            </div>
            <div class="bg-light-danger p-3 rounded-circle text-danger-dark d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                <i class="bi bi-exclamation-triangle-fill fs-4"></i>
            </div>
        </div>
    </div>
</div>

<!-- Employees Grid/Table Wrapper -->
<div class="card__wrapper">
    <div class="d-flex align-items-center justify-content-between mb-20 flex-wrap gap-10">
        <h5 class="m-0 fw-bold text-dark">Tipsoi External Employees List</h5>
        <div class="d-flex align-items-center gap-10">
            <button type="button" id="btnRefresh" class="btn btn-outline-secondary btn-sm py-1 px-3">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh
            </button>
            <span class="badge bg-primary px-3 py-2 rounded-pill fs-7" id="totalCountBadge">Total: 0</span>
        </div>
    </div>

    <!-- Advanced Filter Bar -->
    <div class="row g-15 mb-20 align-items-end">
        <!-- Search Query -->
        <div class="col-lg-4 col-md-6 col-12">
            <div class="from__input-box">
                <label for="searchQuery" class="form-label fw-semibold text-muted mb-2">Search Employee</label>
                <div class="search-box">
                    <input type="text" class="form-control" id="searchQuery" placeholder="Search by name, email, or external ID...">
                </div>
            </div>
        </div>
        <!-- Department Filter -->
        <div class="col-lg-4 col-md-6 col-12">
            <div class="from__input-box">
                <label for="departmentFilter" class="form-label fw-semibold text-muted mb-2">Department (Tipsoi)</label>
                <select class="form-control" id="departmentFilter">
                    <option value="">All Departments</option>
                </select>
            </div>
        </div>
        <!-- ERP Registration Status Filter -->
        <div class="col-lg-4 col-12">
            <div class="from__input-box">
                <label for="registrationFilter" class="form-label fw-semibold text-muted mb-2">ERP Status</label>
                <select class="form-control" id="registrationFilter">
                    <option value="all">All Employees</option>
                    <option value="registered">Registered in ERP</option>
                    <option value="missing">Missing in ERP (Not Registered)</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Table Container -->
    <div class="table-responsive">
        <table class="table table-hover align-middle" id="employeesTable">
            <thead class="table-light">
                <tr>
                    <th scope="col" style="width: 80px;">S/N</th>
                    <th scope="col">External ID</th>
                    <th scope="col">Full Name</th>
                    <th scope="col">Designation</th>
                    <th scope="col">Department</th>
                    <th scope="col">Email</th>
                    <th scope="col">Phone</th>
                    <th scope="col">ERP Status</th>
                    <th scope="col" style="width: 100px;">Actions</th>
                </tr>
            </thead>
            <tbody id="employeesTableBody">
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-muted mt-2 mb-0">Loading external employees...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination Footer -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-15 mt-20 pt-15 border-top">
        <div class="text-muted small" id="paginationSummary">
            Showing 0 to 0 of 0 entries
        </div>
        <nav aria-label="Page navigation">
            <ul class="pagination pagination-secondary m-0 gap-5" id="paginationControls">
                <!-- Dynamic pagination buttons -->
            </ul>
        </nav>
    </div>
</div>

<!-- Update Employee Modal -->
<div class="modal fade" id="updateEmployeeModal" tabindex="-1" aria-labelledby="updateEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="updateEmployeeModalLabel">Update Tipsoi Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <div id="updateAlertContainer"></div>
                <form id="updateEmployeeForm">
                    <input type="hidden" id="updateEmpId">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="updateName" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Employee Office ID <span class="text-danger">*</span></label>
                            <input readonly type="text" class="form-control" name="employeeOfficeId" id="updateEmployeeOfficeId" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" class="form-control" name="email" id="updateEmail">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Department</label>
                            <select class="form-select" name="departmentId" id="updateDepartmentId">
                                <option value="0">Select Department</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Designation</label>
                            <select class="form-select" name="designationId" id="updateDesignationId">
                                <option value="0">Select Designation</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Employment Type</label>
                            <select class="form-select" name="employmentTypeId" id="updateEmploymentTypeId">
                                <option value="0">Select Employment Type</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Workplace</label>
                            <select class="form-select" name="workplaceId" id="updateWorkplaceId">
                                <option value="">Select Workplace</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Line Manager</label>
                            <select class="form-select" name="lineManagerId" id="updateLineManagerId">
                                <option value="-1">None</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">HR Manager</label>
                            <select class="form-select" name="hrManagerId" id="updateHrManagerId">
                                <option value="-1">None</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Shift Group</label>
                            <select class="form-select" name="shiftGroupId" id="updateShiftGroupId">
                                <option value="">Select Shift Group</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date of Join</label>
                            <input type="date" class="form-control" name="dateOfJoin" id="updateDateOfJoin">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date of Resignation</label>
                            <input type="date" class="form-control" name="dateOfResignation" id="updateDateOfResignation">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Report Position</label>
                            <input type="number" class="form-control" name="reportPosition" id="updateReportPosition">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnUpdateSubmit">Update</button>
            </div>
        </div>
    </div>
</div>

<?php
// Store custom page script to execute in master layout footer
ob_start();
?>
document.addEventListener("DOMContentLoaded", function () {
    const baseUrl = "<?= $GLOBALS['base_url'] ?>";
    let currentPage = 1;
    const limit = 15; // Clean pagination on frontend

    // Data Holders
    let allEmployees = [];
    let localEmployeeIds = [];
    let normalizedLocalIds = [];
    let localEmployeeEmails = [];
    let normalizedLocalEmails = [];
    let filteredEmployees = [];

    // Helper to normalize IDs (remove leading zeros for numeric IDs)
    const normalizeId = (id) => {
        const str = String(id || '').trim();
        return /^\d+$/.test(str) ? String(parseInt(str, 10)) : str;
    };

    // DOM Elements
    const btnRefresh = document.getElementById("btnRefresh");
    const tableBody = document.getElementById("employeesTableBody");
    const totalBadge = document.getElementById("totalCountBadge");
    const summaryText = document.getElementById("paginationSummary");
    const paginationUl = document.getElementById("paginationControls");

    const searchQuery = document.getElementById("searchQuery");
    const departmentFilter = document.getElementById("departmentFilter");
    const registrationFilter = document.getElementById("registrationFilter");

    // Stats Elements
    const statTotal = document.getElementById("statTotalEmployees");
    const statRegistered = document.getElementById("statRegisteredEmployees");
    const statMissing = document.getElementById("statMissingEmployees");

    // Stats Cards
    const cardTotal = document.getElementById("cardTotalEmployees");
    const cardRegistered = document.getElementById("cardRegisteredEmployees");
    const cardMissing = document.getElementById("cardMissingEmployees");

    const statusCard = document.getElementById("apiStatusCard");
    const statusIcon = document.getElementById("apiStatusIcon");
    const statusTitle = document.getElementById("apiStatusTitle");
    const statusDesc = document.getElementById("apiStatusDesc");
    const modeBadge = document.getElementById("apiModeBadge");

    // Fetch External Employees Function
    function fetchExternalEmployees() {
        // Show spinner
        tableBody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-5">
                    <div class="spinner-border text-primary mb-2" role="status"></div>
                    <p class="text-muted mb-0">Fetching data from Tipsoi API...</p>
                </td>
            </tr>
        `;

        fetch(`${baseUrl}/api/hrm/external-employees`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (response.status === 401) {
                window.location.href = `${baseUrl}/admin-login`;
                throw new Error("Unauthorized. Redirecting to login...");
            }
            return response.json();
        })
        .then(res => {
            if (res.success) {
                // Update Status Info Card based on whether it is mock or live API
                statusCard.style.display = "block";
                if (res.from_mock) {
                    statusCard.className = "card__wrapper mb-20 border-start border-warning border-4";
                    statusIcon.className = "bi bi-exclamation-circle-fill text-warning fs-4";
                    statusTitle.textContent = "Tipsoi API Status: Offline / Fallback Mode";
                    statusDesc.textContent = res.error_message || "Using mock simulation because the Tipsoi server credentials are not configured or the host is unreachable.";
                    modeBadge.className = "badge bg-warning text-dark px-3 py-2";
                    modeBadge.textContent = "Simulation Mode";
                } else {
                    statusCard.className = "card__wrapper mb-20 border-start border-success border-4";
                    statusIcon.className = "bi bi-check-circle-fill text-success fs-4";
                    statusTitle.textContent = "Tipsoi API Status: Connected";
                    statusDesc.textContent = "Authenticated successfully with Bearer Token and fetched latest live employees.";
                    modeBadge.className = "badge bg-success text-white px-3 py-2";
                    modeBadge.textContent = "Live Sync Mode";
                }

                allEmployees = res.employees || res.data || [];
                localEmployeeIds = res.local_employee_ids || [];
                normalizedLocalIds = localEmployeeIds.map(id => normalizeId(id));
                localEmployeeEmails = res.local_employee_emails || [];
                normalizedLocalEmails = localEmployeeEmails.map(email => email.toLowerCase().trim());

                // Populate Department Dropdown
                populateDepartmentFilter(allEmployees);

                // Calculate and Render Stats
                calculateStats(allEmployees, localEmployeeIds);

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

    // Populate Department Filter with Dynamic Counts
    function populateDepartmentFilter(employees) {
        const deptCounts = {};
        employees.forEach(emp => {
            const dept = emp.departmentName || emp.department_name || 'No Department';
            deptCounts[dept] = (deptCounts[dept] || 0) + 1;
        });

        const sortedDepts = Object.keys(deptCounts).sort();
        
        departmentFilter.innerHTML = '<option value="">All Departments</option>';
        sortedDepts.forEach(dept => {
            const option = document.createElement('option');
            option.value = dept;
            option.textContent = `${dept} (${deptCounts[dept]})`;
            departmentFilter.appendChild(option);
        });
    }

    // Calculate Stats Cards Values
    function calculateStats(employees, localIds) {
        const total = employees.length;
        let registered = 0;

        employees.forEach(emp => {
            const officeId = normalizeId(emp.employeeOfficeId || emp.employee_id);
            const email = String(emp.email || '').toLowerCase().trim();
            const isReg = (officeId && normalizedLocalIds.includes(officeId)) || 
                          (email && normalizedLocalEmails.includes(email));
            if (isReg) {
                registered++;
            }
        });

        const missing = total - registered;

        statTotal.textContent = total;
        statRegistered.textContent = registered;
        statMissing.textContent = missing;
    }

    // Apply Filter Actions and Render Table with Pagination
    function applyFiltersAndRender(page = 1) {
        currentPage = page;
        const searchVal = searchQuery.value.toLowerCase().trim();
        const deptVal = departmentFilter.value;
        const regVal = registrationFilter.value;

        // Perform Filtering
        filteredEmployees = allEmployees.filter(emp => {
            // 1. Search Query filter (matches name, email, office id)
            const name = String(emp.name || emp.full_name || '').toLowerCase();
            const email = String(emp.email || '').toLowerCase();
            const officeId = String(emp.employeeOfficeId || emp.employee_id || '').toLowerCase();
            
            const matchesSearch = !searchVal || 
                name.includes(searchVal) || 
                email.includes(searchVal) || 
                officeId.includes(searchVal);

            // 2. Department filter
            const dept = emp.departmentName || emp.department_name || 'No Department';
            const matchesDept = !deptVal || dept === deptVal;

            // 3. ERP Status filter
            const normOfficeId = normalizeId(emp.employeeOfficeId || emp.employee_id);
            const empEmail = String(emp.email || '').toLowerCase().trim();
            const isRegistered = (normOfficeId && normalizedLocalIds.includes(normOfficeId)) ||
                                 (empEmail && normalizedLocalEmails.includes(empEmail));
            
            let matchesReg = true;
            if (regVal === 'registered') {
                matchesReg = isRegistered;
            } else if (regVal === 'missing') {
                matchesReg = !isRegistered;
            }

            return matchesSearch && matchesDept && matchesReg;
        });

        // Update total badge count
        totalBadge.textContent = `Total: ${filteredEmployees.length}`;

        // Render Table Body for current page
        renderTable(filteredEmployees);

        // Render Pagination buttons
        renderPagination(filteredEmployees.length, currentPage, limit);
    }

    // Render Table Body
    function renderTable(employees) {
        const offset = (currentPage - 1) * limit;
        const pageItems = employees.slice(offset, offset + limit);

        if (!pageItems || pageItems.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-2 mb-2 d-block"></i>
                        No employees found matching the current filters.
                    </td>
                </tr>
            `;
            return;
        }

        let sn = offset + 1;
        tableBody.innerHTML = pageItems.map(emp => {
            const officeId = normalizeId(emp.employeeOfficeId || emp.employee_id);
            const empEmail = String(emp.email || '').toLowerCase().trim();
            const isRegistered = (officeId && normalizedLocalIds.includes(officeId)) ||
                                 (empEmail && normalizedLocalEmails.includes(empEmail));
            
            const statusBadge = isRegistered 
                ? '<span class="badge bg-success text-white px-2 py-1"><i class="bi bi-patch-check-fill me-1"></i>Registered</span>'
                : '<span class="badge bg-danger text-white px-2 py-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>Not in ERP</span>';

            return `
                <tr>
                    <td class="fw-semibold text-muted">${sn++}</td>
                    <td class="fw-semibold text-dark">${emp.employeeOfficeId || emp.employee_id || 'N/A'}</td>
                    <td class="fw-bold">${emp.name || emp.full_name || 'N/A'}</td>
                    <td><span class="badge bg-light text-dark border">${emp.designationName || emp.designation_name || 'N/A'}</span></td>
                    <td>${emp.departmentName || emp.department_name || 'N/A'}</td>
                    <td>${emp.email || 'N/A'}</td>
                    <td><i class="bi bi-phone me-1 text-muted"></i>${emp.phoneMobile || emp.phoneOffice || emp.phone || 'N/A'}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary btn-edit-employee" data-id="${emp.id}" title="Update Employee">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger btn-delete-employee" data-id="${emp.id}" title="Delete Employee">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // Render Pagination Controls
    function renderPagination(total, page, limit) {
        const totalPages = Math.ceil(total / limit);
        const startRecord = total > 0 ? ((page - 1) * limit) + 1 : 0;
        const endRecord = Math.min(startRecord + limit - 1, total);

        summaryText.innerHTML = `Showing <strong>${startRecord}</strong> to <strong>${endRecord}</strong> of <strong>${total}</strong> external employees`;

        if (totalPages <= 1) {
            paginationUl.innerHTML = '';
            return;
        }

        let paginationHtml = '';

        // Previous button
        if (page > 1) {
            paginationHtml += `
                <li class="page-item">
                    <button class="page-link" data-page="${page - 1}">&laquo; Prev</button>
                </li>
            `;
        } else {
            paginationHtml += `
                <li class="page-item disabled">
                    <span class="page-link">&laquo; Prev</span>
                </li>
            `;
        }

        // Page buttons (limit to showing nearby pages if too many)
        const range = 2; // how many pages to show around current page
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= page - range && i <= page + range)) {
                if (i === page) {
                    paginationHtml += `
                        <li class="page-item active">
                            <span class="page-link">${i}</span>
                        </li>
                    `;
                } else {
                    paginationHtml += `
                        <li class="page-item">
                            <button class="page-link" data-page="${i}">${i}</button>
                        </li>
                    `;
                }
            } else if (i === page - range - 1 || i === page + range + 1) {
                paginationHtml += `
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                `;
            }
        }

        // Next button
        if (page < totalPages) {
            paginationHtml += `
                <li class="page-item">
                    <button class="page-link" data-page="${page + 1}">Next &raquo;</button>
                </li>
            `;
        } else {
            paginationHtml += `
                <li class="page-item disabled">
                    <span class="page-link">Next &raquo;</span>
                </li>
            `;
        }

        paginationUl.innerHTML = paginationHtml;

        // Attach click handlers to pagination buttons
        paginationUl.querySelectorAll("button.page-link").forEach(btn => {
            btn.addEventListener("click", function() {
                const targetPage = parseInt(this.getAttribute("data-page"));
                applyFiltersAndRender(targetPage);
            });
        });
    }

    // Show Error Row
    function showErrorRow(message) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-5 text-danger">
                    <i class="bi bi-exclamation-triangle-fill fs-2 mb-2 d-block"></i>
                    Error: ${message}
                </td>
            </tr>
        `;
    }

    // Event Listeners for Filters
    searchQuery.addEventListener("input", () => applyFiltersAndRender(1));
    departmentFilter.addEventListener("change", () => applyFiltersAndRender(1));
    registrationFilter.addEventListener("change", (e) => {
        // Synchronize Active Card border style
        updateActiveCardStyle(e.target.value);
        applyFiltersAndRender(1);
    });

    // Stats Cards Click Handlers
    cardTotal.addEventListener("click", () => {
        registrationFilter.value = "all";
        updateActiveCardStyle("all");
        applyFiltersAndRender(1);
    });

    cardRegistered.addEventListener("click", () => {
        registrationFilter.value = "registered";
        updateActiveCardStyle("registered");
        applyFiltersAndRender(1);
    });

    cardMissing.addEventListener("click", () => {
        registrationFilter.value = "missing";
        updateActiveCardStyle("missing");
        applyFiltersAndRender(1);
    });

    function updateActiveCardStyle(selectedValue) {
        cardTotal.classList.remove("active-filter");
        cardRegistered.classList.remove("active-filter");
        cardMissing.classList.remove("active-filter");

        if (selectedValue === "all" || selectedValue === "") {
            cardTotal.classList.add("active-filter");
        } else if (selectedValue === "registered") {
            cardRegistered.classList.add("active-filter");
        } else if (selectedValue === "missing") {
            cardMissing.classList.add("active-filter");
        }
    }

    // Update & Delete Logic
    let isDropdownsLoaded = false;
    let updateModalInstance = null;

    function loadDropdowns() {
        if (isDropdownsLoaded) return Promise.resolve();
        return fetch(`${baseUrl}/api/hrm/tipsoi-dropdowns`)
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data) {
                    const populate = (id, items, key) => {
                        const el = document.getElementById(id);
                        if (!el) return;
                        let html = `<option value="0">Select Option</option>`;
                        if (id === 'updateLineManagerId' || id === 'updateHrManagerId') {
                            html = `<option value="-1">None</option>`;
                        }
                        if (items) {
                            items.forEach(it => {
                                html += `<option value="${it.id}">${it[key] || it.name || it.title || 'Unknown'}</option>`;
                            });
                        }
                        el.innerHTML = html;
                    };
                    populate('updateDesignationId', res.data.designations, 'name');
                    populate('updateDepartmentId', res.data.departments, 'name');
                    populate('updateEmploymentTypeId', res.data.employmentTypes, 'name');
                    populate('updateShiftGroupId', res.data.shiftGroups, 'name');
                    populate('updateWorkplaceId', res.data.workplaces, 'name');
                    
                    // Managers from allEmployees
                    const managersHtml = `<option value="-1">None</option>` + allEmployees.map(e => `<option value="${e.id}">${e.name || e.full_name || 'N/A'}</option>`).join('');
                    document.getElementById('updateLineManagerId').innerHTML = managersHtml;
                    document.getElementById('updateHrManagerId').innerHTML = managersHtml;
                    
                    isDropdownsLoaded = true;
                }
            });
    }

    tableBody.addEventListener("click", function (e) {
        // Handle Edit
        const editBtn = e.target.closest(".btn-edit-employee");
        if (editBtn) {
            const empId = editBtn.getAttribute("data-id");
            const emp = allEmployees.find(x => String(x.id) === String(empId));
            if (emp) {
                if (!updateModalInstance) {
                    updateModalInstance = new bootstrap.Modal(document.getElementById('updateEmployeeModal'));
                }
                
                document.getElementById('updateAlertContainer').innerHTML = '';
                
                // Show loading state if dropdowns not loaded
                const submitBtn = document.getElementById('btnUpdateSubmit');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Loading...';
                
                loadDropdowns().then(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Update';
                    
                    // Populate Form
                    document.getElementById('updateEmpId').value = emp.id;
                    document.getElementById('updateName').value = emp.name || emp.full_name || '';
                    document.getElementById('updateEmployeeOfficeId').value = emp.employeeOfficeId || emp.employee_id || '';
                    document.getElementById('updateEmail').value = emp.email || '';
                    
                    document.getElementById('updateDepartmentId').value = emp.departmentId || emp.department_id || (emp.department && emp.department.id) || 0;
                    document.getElementById('updateSubDepartmentId').value = emp.subDepartmentId || emp.sub_department_id || 0;
                    document.getElementById('updateDesignationId').value = emp.designationId || emp.designation_id || (emp.designation && emp.designation.id) || 0;
                    document.getElementById('updateEmploymentTypeId').value = emp.employmentTypeId || emp.employment_type_id || (emp.employmentType && emp.employmentType.id) || 0;
                    document.getElementById('updateWorkplaceId').value = emp.workplaceId || emp.workplace_id || (emp.workplace && emp.workplace.id) || '';
                    
                    const lmId = emp.lineManagerId !== undefined ? emp.lineManagerId : (emp.line_manager_id !== undefined ? emp.line_manager_id : -1);
                    document.getElementById('updateLineManagerId').value = lmId != null ? lmId : -1;

                    const hrId = emp.hrManagerId !== undefined ? emp.hrManagerId : (emp.hr_manager_id !== undefined ? emp.hr_manager_id : -1);
                    document.getElementById('updateHrManagerId').value = hrId != null ? hrId : -1;
                    
                    document.getElementById('updateShiftGroupId').value = emp.shiftGroupId || emp.shift_group_id || (emp.shiftGroup && emp.shiftGroup.id) || '';
                    document.getElementById('updateRfid').value = emp.rfid || '';
                    document.getElementById('updateDateOfJoin').value = emp.dateOfJoin || emp.date_of_join || '';
                    document.getElementById('updateDateOfResignation').value = emp.dateOfResignation || emp.date_of_resignation || '';
                    document.getElementById('updatePrimaryDisplayText').value = emp.primaryDisplayText || emp.primary_display_text || '';
                    document.getElementById('updateSecondaryDisplayText').value = emp.secondaryDisplayText || emp.secondary_display_text || '';
                    document.getElementById('updateReportPosition').value = emp.reportPosition || emp.report_position || '';
                }).catch(() => {
                    submitBtn.innerHTML = 'Update';
                    submitBtn.disabled = false;
                });
                
                updateModalInstance.show();
            }
        }

        // Handle Delete
        const deleteBtn = e.target.closest(".btn-delete-employee");
        if (deleteBtn) {
            const empId = deleteBtn.getAttribute("data-id");
            const emp = allEmployees.find(x => String(x.id) === String(empId));
            const empName = emp ? (emp.name || emp.full_name) : 'this employee';
            
            Swal.fire({
                title: 'Are you sure?',
                text: `Are you sure you want to delete ${empName}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Delete!'
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteEmployee(empId);
                }
            });
        }
    });

    document.getElementById('btnUpdateSubmit').addEventListener('click', function() {
        const form = document.getElementById('updateEmployeeForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const empId = document.getElementById('updateEmpId').value;
        const payload = {
            name: document.getElementById('updateName').value,
            employeeOfficeId: document.getElementById('updateEmployeeOfficeId').value,
            email: document.getElementById('updateEmail').value,
            departmentId: document.getElementById('updateDepartmentId').value ? parseInt(document.getElementById('updateDepartmentId').value) : 0,
            subDepartmentId: document.getElementById('updateSubDepartmentId').value ? parseInt(document.getElementById('updateSubDepartmentId').value) : 0,
            designationId: document.getElementById('updateDesignationId').value ? parseInt(document.getElementById('updateDesignationId').value) : 0,
            employmentTypeId: document.getElementById('updateEmploymentTypeId').value ? parseInt(document.getElementById('updateEmploymentTypeId').value) : 0,
            workplaceId: document.getElementById('updateWorkplaceId').value ? parseInt(document.getElementById('updateWorkplaceId').value) : null,
            lineManagerId: parseInt(document.getElementById('updateLineManagerId').value),
            hrManagerId: parseInt(document.getElementById('updateHrManagerId').value),
            shiftGroupId: document.getElementById('updateShiftGroupId').value ? parseInt(document.getElementById('updateShiftGroupId').value) : null,
            rfid: document.getElementById('updateRfid').value || null,
            dateOfJoin: document.getElementById('updateDateOfJoin').value || null,
            dateOfResignation: document.getElementById('updateDateOfResignation').value || null,
            primaryDisplayText: document.getElementById('updatePrimaryDisplayText').value || null,
            secondaryDisplayText: document.getElementById('updateSecondaryDisplayText').value || null,
            reportPosition: document.getElementById('updateReportPosition').value ? parseInt(document.getElementById('updateReportPosition').value) : null,
        };

        const btn = document.getElementById('btnUpdateSubmit');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Updating...';

        fetch(`${baseUrl}/api/hrm/tipsoi-action`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                endpoint: `/employee/profile/${empId}`,
                method: 'POST',
                payload: payload
            })
        })
        .then(res => res.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = 'Update';
            if (res.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Updated!',
                    text: 'Employee has been updated successfully.',
                    timer: 2000,
                    showConfirmButton: false
                });
                updateModalInstance.hide();
                fetchExternalEmployees();
            } else {
                document.getElementById('updateAlertContainer').innerHTML = `
                    <div class="alert alert-danger alert-dismissible fade show">
                        ${res.message || 'Failed to update employee.'}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>`;
            }
        })
        .catch(err => {
            console.error(err);
            btn.disabled = false;
            btn.innerHTML = 'Update';
            document.getElementById('updateAlertContainer').innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show">
                    Network error occurred.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>`;
        });
    });

    function deleteEmployee(empId) {
        fetch(`${baseUrl}/api/hrm/tipsoi-action`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                endpoint: `/employee/delete`,
                method: 'POST',
                payload: { employeeIdList: [String(empId)] }
            })
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Deleted!',
                    text: 'Employee has been deleted.',
                    timer: 2000,
                    showConfirmButton: false
                });
                fetchExternalEmployees();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: res.message || 'Failed to delete employee.'
                });
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Network error occurred.'
            });
        });
    }

    // Refresh action
    btnRefresh.addEventListener("click", () => fetchExternalEmployees());

    // Initial Load
    fetchExternalEmployees();
});
<?php
$page_script = ob_get_clean();
?>

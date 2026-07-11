<?php
$page_title = "Leave History & Requests";
$page = 'Leave History';
$show_breadcrumb = true;
?>

<style>
.bg-light-primary { background-color: rgba(13, 110, 253, 0.08) !important; }
.bg-light-success { background-color: rgba(25, 135, 84, 0.08) !important; }
.bg-light-danger { background-color: rgba(220, 53, 69, 0.08) !important; }
.bg-light-warning { background-color: rgba(255, 193, 7, 0.08) !important; }

.stats-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.stats-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1) !important;
}
.active-filter {
    border: 2px solid #15a15b !important;
    background-color: #f0fdf4 !important;
}
</style>

<!-- Connection Status Card (Tipsoi API indicator) -->
<div class="row mb-25 d-none" id="apiStatusCard" style="display: none;">
    <div class="col-12">
        <div class="card__wrapper d-flex align-items-center justify-content-between p-15 shadow-sm">
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
</div>

<!-- Stats Cards Heading -->
<div class="d-flex align-items-center justify-content-between mb-15">
    <h6 class="text-muted text-uppercase fw-bold m-0" style="font-size: 0.8rem; letter-spacing: 0.5px;" id="statsHeaderLabel">
        Leave Requests Overview
    </h6>
    <span class="badge bg-light text-dark border px-2 py-1" id="statsDateBadge">...</span>
</div>

<!-- Stats Cards Row -->
<div class="row g-20 mb-25">
    <!-- Total Requests -->
    <div class="col-lg-3 col-md-6 col-12">
        <div class="card__wrapper d-flex align-items-center justify-content-between p-20 border-start border-primary border-4 shadow-sm stats-card" style="cursor: pointer;" id="cardTotal">
            <div>
                <h6 class="text-muted text-uppercase mb-2 fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Total Requests</h6>
                <h2 class="fw-bold text-primary mb-0" id="statTotal">0</h2>
                <small class="text-muted">Click to view all</small>
            </div>
            <div class="bg-light-primary p-3 rounded-circle text-primary d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                <i class="bi bi-chat-left-text-fill fs-4"></i>
            </div>
        </div>
    </div>
    <!-- Approved Requests -->
    <div class="col-lg-3 col-md-6 col-12">
        <div class="card__wrapper d-flex align-items-center justify-content-between p-20 border-start border-success border-4 shadow-sm stats-card" style="cursor: pointer;" id="cardApproved">
            <div>
                <h6 class="text-muted text-uppercase mb-2 fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Approved</h6>
                <h2 class="fw-bold text-success mb-0" id="statApproved">0</h2>
                <small class="text-muted">Click to view approved</small>
            </div>
            <div class="bg-light-success p-3 rounded-circle text-success d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                <i class="bi bi-check-circle-fill fs-4"></i>
            </div>
        </div>
    </div>
    <!-- Pending Requests -->
    <div class="col-lg-3 col-md-6 col-12">
        <div class="card__wrapper d-flex align-items-center justify-content-between p-20 border-start border-warning border-4 shadow-sm stats-card" style="cursor: pointer;" id="cardPending">
            <div>
                <h6 class="text-muted text-uppercase mb-2 fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Pending</h6>
                <h2 class="fw-bold text-warning mb-0" id="statPending">0</h2>
                <small class="text-muted">Click to view pending</small>
            </div>
            <div class="bg-light-warning p-3 rounded-circle text-warning d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                <i class="bi bi-hourglass-split fs-4"></i>
            </div>
        </div>
    </div>
    <!-- Rejected Requests -->
    <div class="col-lg-3 col-md-6 col-12">
        <div class="card__wrapper d-flex align-items-center justify-content-between p-20 border-start border-danger border-4 shadow-sm stats-card" style="cursor: pointer;" id="cardRejected">
            <div>
                <h6 class="text-muted text-uppercase mb-2 fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Rejected</h6>
                <h2 class="fw-bold text-danger mb-0" id="statRejected">0</h2>
                <small class="text-muted">Click to view rejected</small>
            </div>
            <div class="bg-light-danger p-3 rounded-circle text-danger d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                <i class="bi bi-x-circle-fill fs-4"></i>
            </div>
        </div>
    </div>
</div>

<!-- Filters Panel -->
<div class="card__wrapper mb-25 p-20 bg-white rounded shadow-sm border border-light">
    <form class="row g-15 align-items-end">
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
        <!-- Leave Category Filter -->
        <div class="col-lg-2 col-md-4 col-6">
            <div class="from__input-box">
                <label for="categoryFilter" class="form-label fw-semibold text-muted mb-2">Leave Category</label>
                <select class="form-control" id="categoryFilter">
                    <option value="">All Categories</option>
                </select>
            </div>
        </div>
        <!-- Status Filter -->
        <div class="col-lg-2 col-md-4 col-6">
            <div class="from__input-box">
                <label for="statusFilter" class="form-label fw-semibold text-muted mb-2">Approval Status</label>
                <select class="form-control" id="statusFilter">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
        </div>
        <!-- Search Button -->
        <div class="col-lg-1 col-md-12 col-12">
            <button type="button" id="btnFilterSearch" class="btn btn-primary w-100 py-2 d-flex align-items-center justify-content-center">
                <i class="bi bi-search me-1"></i> Sync
            </button>
        </div>
    </form>

    <!-- Export Buttons -->
    <div class="d-flex justify-content-end align-items-center gap-2 mt-20">
        <button type="button" id="btnExportCSV" class="btn btn-sm btn-outline-success px-3 py-2 fw-semibold d-flex align-items-center justify-content-center">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
        </button>
        <button type="button" id="btnExportPDF" class="btn btn-sm btn-outline-danger px-3 py-2 fw-semibold d-flex align-items-center justify-content-center">
            <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
        </button>
    </div>
</div>

<!-- Table Section -->
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0" style="min-width: 1100px;">
        <thead class="table-light text-secondary">
            <tr>
                <th style="width: 50px;">SN</th>
                <th>Employee ID</th>
                <th>Full Name</th>
                <th>Department</th>
                <th>Designation</th>
                <th>Leave Category</th>
                <th>From Date</th>
                <th>To Date</th>
                <th style="width: 80px;">Days</th>
                <th>Remaining Balance</th>
                <th>Reason</th>
                <th>Approval Flow</th>
                <th style="width: 130px;">Status</th>
            </tr>
        </thead>
        <tbody id="leaveTableBody">
            <!-- Data will load dynamically -->
        </tbody>
    </table>
</div>

<!-- Pagination Controls -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-10 mt-20">
    <div class="text-muted fw-semibold" id="paginationSummary">Showing 0 to 0 of 0 entries</div>
    <span class="badge bg-light text-dark border px-3 py-2 fw-semibold" id="totalCountBadge">Total Records: 0</span>
    <nav>
        <ul class="pagination pagination-sm m-0" id="paginationControls">
            <!-- Dynamic pagination -->
        </ul>
    </nav>
</div>

<!-- Modal to View Full Reason -->
<div class="modal fade" id="reasonModal" tabindex="-1" aria-labelledby="reasonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="reasonModalLabel">Leave Reason Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <strong>Employee:</strong> <span id="modalEmployeeName"></span>
                </div>
                <div class="mb-3">
                    <strong>Leave Type:</strong> <span id="modalLeaveCategory"></span>
                </div>
                <div class="mb-3">
                    <strong>Period:</strong> <span id="modalLeavePeriod"></span> (<span id="modalLeaveDays"></span> days)
                </div>
                <hr>
                <div>
                    <strong>Reason for Leave:</strong>
                    <p class="mt-2 text-muted border p-3 bg-light rounded" id="modalLeaveReason" style="white-space: pre-line;"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
document.addEventListener("DOMContentLoaded", function () {
    const baseUrl = "<?= $GLOBALS['base_url'] ?>";
    let currentPage = 1;
    const limit = 15;

    // Data Holders
    let allRawLeaveEmployees = [];
    let allRecords = [];
    let filteredRecords = [];
    let localEmployees = [];
    let normalizedLocalIds = [];
    let normalizedLocalEmails = [];
    let activeCardFilter = ""; // "pending", "approved", "rejected"

    // DOM Elements
    const tableBody = document.getElementById("leaveTableBody");
    const totalBadge = document.getElementById("totalCountBadge");
    const summaryText = document.getElementById("paginationSummary");
    const paginationUl = document.getElementById("paginationControls");

    const fromDateInput = document.getElementById("fromDate");
    const toDateInput = document.getElementById("toDate");
    const searchQuery = document.getElementById("searchQuery");
    const departmentFilter = document.getElementById("departmentFilter");
    const categoryFilter = document.getElementById("categoryFilter");
    const statusFilter = document.getElementById("statusFilter");
    const btnFilterSearch = document.getElementById("btnFilterSearch");

    // Stats Elements
    const statTotal = document.getElementById("statTotal");
    const statApproved = document.getElementById("statApproved");
    const statPending = document.getElementById("statPending");
    const statRejected = document.getElementById("statRejected");
    const statsDateBadge = document.getElementById("statsDateBadge");

    const cardTotal = document.getElementById("cardTotal");
    const cardApproved = document.getElementById("cardApproved");
    const cardPending = document.getElementById("cardPending");
    const cardRejected = document.getElementById("cardRejected");

    // API Indicator Elements
    const statusCard = document.getElementById("apiStatusCard");
    const statusIcon = document.getElementById("apiStatusIcon");
    const statusTitle = document.getElementById("apiStatusTitle");
    const statusDesc = document.getElementById("apiStatusDesc");
    const modeBadge = document.getElementById("apiModeBadge");

    // Helpers
    const normalizeId = (id) => {
        const str = String(id || '').trim();
        return /^\d+$/.test(str) ? String(parseInt(str, 10)) : str;
    };

    // Fetch and Load Data
    function fetchLeaveData() {
        const fromDate = fromDateInput.value;
        const toDate = toDateInput.value;

        tableBody.innerHTML = `
            <tr>
                <td colspan="13" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="text-muted mt-2">Fetching leave records from Tipsoi API...</div>
                </td>
            </tr>
        `;

        const params = new URLSearchParams({
            from_date: fromDate,
            to_date: toDate
        });

        fetch(`${baseUrl}/api/hrm/leave-history?${params.toString()}`)
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                // Ensure connection card is hidden on success
                statusCard.style.display = "none";

                allRawLeaveEmployees = res.data || [];
                localEmployees = res.local_employees || [];
                const localIds = res.local_employee_ids || [];
                const localEmails = res.local_employee_emails || [];
                normalizedLocalIds = localIds.map(id => normalizeId(id));
                normalizedLocalEmails = localEmails.map(email => email.toLowerCase().trim());

                // Process data
                processRecords(allRawLeaveEmployees);

                // Populate Category and Department filters
                populateDropdownFilters(allRawLeaveEmployees);

                // Apply filters and render
                applyFiltersAndRender(1);
            } else {
                showErrorRow(res.message || "Failed to fetch leave history.");
            }
        })
        .catch(err => {
            console.error(err);
            showErrorRow(err.message || "An unexpected network error occurred.");
        });
    }

    // Process leave data from employee policy list
    function processRecords(data) {
        allRecords = [];
        data.forEach(emp => {
            const officeId = normalizeId(emp.employeeOfficeId || emp.employeeId);
            const nameNormalized = String(emp.employeeName || '').toLowerCase().trim();
            
            // Match local employee email
            const localMatch = localEmployees.find(le => {
                const leId = normalizeId(le.employee_id);
                const leName = String(le.full_name || '').toLowerCase().trim();
                return (officeId && leId === officeId) || (nameNormalized && leName === nameNormalized);
            });
            const email = localMatch ? localMatch.email : '';

            const leaveInfo = emp.leaveLeaveInfo || {};
            const dataList = leaveInfo.leaveDataList || [];

            // 1. Gather all category balances first (entries where from date is null)
            const balancesMap = {};
            dataList.forEach(item => {
                if (item.from === null) {
                    const balance = (item.balance !== null) ? item.balance : 'N/A';
                    balancesMap[item.leaveCategoryName] = balance;
                }
            });

            // 2. Extract leave applications (entries where from date is NOT null)
            const lineManager = emp.lineManager || {};
            const lineManagerName = (lineManager.name && lineManager.name !== 'Not Set') ? lineManager.name : '';

            dataList.forEach(item => {
                if (item.from !== null) {
                    allRecords.push({
                        employeeId: emp.employeeId,
                        employeeOfficeId: emp.employeeOfficeId,
                        employeeName: emp.employeeName,
                        email: email,
                        departmentName: emp.departmentName || 'No Department',
                        designationName: emp.designationName || 'N/A',
                        leaveCategoryName: item.leaveCategoryName,
                        from: item.from,
                        to: item.to,
                        days: item.days || 0,
                        reason: item.reason || '',
                        approvalStatus: item.approvalStatus, // 0 = Pending, 1 = Approved, 2 = Rejected
                        remainingBalance: item.remaining_balance ?? 'N/A',
                        lineManagerName: lineManagerName
                    });
                }
            });
        });

        // Sort by employee name alphabetically
        allRecords.sort((a, b) => a.employeeName.localeCompare(b.employeeName));
    }

    // Populate drop downs
    function populateDropdownFilters(employees) {
        const depts = new Set();
        const categories = new Set();

        employees.forEach(emp => {
            if (emp.departmentName) depts.add(emp.departmentName.trim());
            const dataList = emp.leaveLeaveInfo?.leaveDataList || [];
            dataList.forEach(item => {
                if (item.leaveCategoryName) categories.add(item.leaveCategoryName.trim());
            });
        });

        // Departments
        const sortedDepts = Array.from(depts).sort();
        departmentFilter.innerHTML = '<option value="">All Departments</option>';
        sortedDepts.forEach(dept => {
            const opt = document.createElement('option');
            opt.value = dept;
            opt.textContent = dept;
            departmentFilter.appendChild(opt);
        });

        // Categories
        const sortedCats = Array.from(categories).sort();
        categoryFilter.innerHTML = '<option value="">All Categories</option>';
        sortedCats.forEach(cat => {
            const opt = document.createElement('option');
            opt.value = cat;
            opt.textContent = cat;
            categoryFilter.appendChild(opt);
        });
    }

    // Calculate Stats Overview Card counts
    function calculateStats() {
        const searchVal = searchQuery.value.toLowerCase().trim();
        const deptVal = departmentFilter.value;
        const catVal = categoryFilter.value;

        // Set date range badge
        const fromVal = fromDateInput.value;
        const toVal = toDateInput.value;
        statsDateBadge.textContent = (fromVal === toVal) ? fromVal : `${fromVal} to ${toVal}`;

        let total = 0;
        let approved = 0;
        let pending = 0;
        let rejected = 0;

        allRecords.forEach(rec => {
            // Apply current filters first
            const name = String(rec.employeeName || '').toLowerCase();
            const officeId = String(rec.employeeOfficeId || '').toLowerCase();
            const email = String(rec.email || '').toLowerCase();

            const matchesSearch = !searchVal || 
                                  name.includes(searchVal) || 
                                  officeId.includes(searchVal) || 
                                  email.includes(searchVal);
            
            const matchesDept = !deptVal || rec.departmentName === deptVal;
            const matchesCat = !catVal || rec.leaveCategoryName === catVal;

            if (!matchesSearch || !matchesDept || !matchesCat) {
                return; // Skip if it doesn't match active filters
            }

            total++;
            if (rec.approvalStatus === 1) {
                approved++;
            } else if (rec.approvalStatus === 0) {
                pending++;
            } else if (rec.approvalStatus === 2) {
                rejected++;
            }
        });

        statTotal.textContent = total;
        statApproved.textContent = approved;
        statPending.textContent = pending;
        statRejected.textContent = rejected;
    }

    // Apply Filter and Render
    function applyFiltersAndRender(page = 1) {
        currentPage = page;

        // Recalculate stats dynamically
        calculateStats();

        const searchVal = searchQuery.value.toLowerCase().trim();
        const deptVal = departmentFilter.value;
        const catVal = categoryFilter.value;
        const statusVal = statusFilter.value || activeCardFilter;

        filteredRecords = allRecords.filter(rec => {
            // 1. Search Query
            const name = String(rec.employeeName || '').toLowerCase();
            const officeId = String(rec.employeeOfficeId || '').toLowerCase();
            const email = String(rec.email || '').toLowerCase();
            const matchesSearch = !searchVal || 
                                  name.includes(searchVal) || 
                                  officeId.includes(searchVal) || 
                                  email.includes(searchVal);

            // 2. Department
            const matchesDept = !deptVal || rec.departmentName === deptVal;

            // 3. Category
            const matchesCat = !catVal || rec.leaveCategoryName === catVal;

            // 4. Status
            let matchesStatus = true;
            if (statusVal === 'pending') {
                matchesStatus = rec.approvalStatus === 0;
            } else if (statusVal === 'approved') {
                matchesStatus = rec.approvalStatus === 1;
            } else if (statusVal === 'rejected') {
                matchesStatus = rec.approvalStatus === 2;
            }

            return matchesSearch && matchesDept && matchesCat && matchesStatus;
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
                    <td colspan="13" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-2 mb-2 d-block"></i>
                        No leave records found matching filters.
                    </td>
                </tr>
            `;
            return;
        }

        let sn = offset + 1;
        tableBody.innerHTML = pageItems.map(rec => {
            let statusBadge = "";
            if (rec.approvalStatus === 0) {
                statusBadge = '<span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-hourglass-split me-1"></i>Pending</span>';
            } else if (rec.approvalStatus === 1) {
                statusBadge = '<span class="badge bg-success text-white px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>Approved</span>';
            } else if (rec.approvalStatus === 2) {
                statusBadge = '<span class="badge bg-danger text-white px-2 py-1"><i class="bi bi-x-circle-fill me-1"></i>Rejected</span>';
            }

            // Build Approval Flow
            let flowHtml = "";
            if (rec.lineManagerName) {
                if (rec.approvalStatus === 1) {
                    flowHtml = `
                        <div class="d-flex flex-column gap-1">
                            <span class="badge bg-success-subtle text-success text-start py-1 px-2 border border-success-subtle" style="font-size: 0.7rem;">
                                <i class="bi bi-check-circle-fill me-1"></i> L1: ${rec.lineManagerName}
                            </span>
                            <span class="badge bg-success-subtle text-success text-start py-1 px-2 border border-success-subtle" style="font-size: 0.7rem;">
                                <i class="bi bi-check-circle-fill me-1"></i> L2: HR/Admin
                            </span>
                        </div>
                    `;
                } else if (rec.approvalStatus === 0) {
                    flowHtml = `
                        <div class="d-flex flex-column gap-1">
                            <span class="badge bg-warning-subtle text-warning text-start py-1 px-2 border border-warning-subtle" style="font-size: 0.7rem;">
                                <i class="bi bi-hourglass-split me-1"></i> L1: ${rec.lineManagerName}
                            </span>
                            <span class="badge bg-secondary-subtle text-secondary text-start py-1 px-2 border border-secondary-subtle" style="font-size: 0.7rem;">
                                <i class="bi bi-circle me-1"></i> L2: HR/Admin (Pending)
                            </span>
                        </div>
                    `;
                } else {
                    flowHtml = `
                        <div class="d-flex flex-column gap-1">
                            <span class="badge bg-danger-subtle text-danger text-start py-1 px-2 border border-danger-subtle" style="font-size: 0.7rem;">
                                <i class="bi bi-x-circle-fill me-1"></i> L1: ${rec.lineManagerName}
                            </span>
                            <span class="badge bg-danger-subtle text-danger text-start py-1 px-2 border border-danger-subtle" style="font-size: 0.7rem;">
                                <i class="bi bi-x-circle-fill me-1"></i> L2: HR/Admin (Rejected)
                            </span>
                        </div>
                    `;
                }
            } else {
                if (rec.approvalStatus === 1) {
                    flowHtml = `<span class="badge bg-success-subtle text-success py-1 px-2 border border-success-subtle" style="font-size: 0.7rem;"><i class="bi bi-check-circle-fill me-1"></i> Admin Approved</span>`;
                } else if (rec.approvalStatus === 0) {
                    flowHtml = `<span class="badge bg-warning-subtle text-warning py-1 px-2 border border-warning-subtle" style="font-size: 0.7rem;"><i class="bi bi-hourglass-split me-1"></i> Admin Pending</span>`;
                } else {
                    flowHtml = `<span class="badge bg-danger-subtle text-danger py-1 px-2 border border-danger-subtle" style="font-size: 0.7rem;"><i class="bi bi-x-circle-fill me-1"></i> Admin Rejected</span>`;
                }
            }

            // Local ERP matched indicator
            const officeId = normalizeId(rec.employeeOfficeId);
            const empEmail = String(rec.email || '').toLowerCase().trim();
            const isRegistered = (officeId && normalizedLocalIds.includes(officeId)) ||
                                 (empEmail && normalizedLocalEmails.includes(empEmail));
            
            const localIndicator = isRegistered
                ? '<i class="bi bi-patch-check-fill text-success ms-1" title="Registered in ERP DB"></i>'
                : '<i class="bi bi-patch-question text-muted ms-1" title="Not in ERP DB"></i>';

            // Truncate reason for display
            const rawReason = rec.reason || '';
            const isLong = rawReason.length > 30;
            const displayReason = isLong ? rawReason.substring(0, 30) + '...' : rawReason;
            
            // View details button
            const reasonButton = rawReason.trim() !== ''
                ? `<button type="button" class="btn btn-xs btn-link p-0 ms-1 fw-bold text-decoration-none view-reason-btn" 
                      data-name="${rec.employeeName}" 
                      data-category="${rec.leaveCategoryName}"
                      data-period="${rec.from} to ${rec.to}"
                      data-days="${rec.days}"
                      data-reason="${encodeURIComponent(rawReason)}">View</button>`
                : '—';

            return `
                <tr>
                    <td class="text-muted fw-semibold">${sn++}</td>
                    <td class="fw-semibold text-muted">${rec.employeeOfficeId || '—'}</td>
                    <td class="fw-bold">${rec.employeeName} ${localIndicator}</td>
                    <td>${rec.departmentName}</td>
                    <td><span class="badge bg-light text-dark border">${rec.designationName}</span></td>
                    <td><span class="badge bg-light-primary text-primary fw-semibold">${rec.leaveCategoryName}</span></td>
                    <td class="text-secondary fw-semibold">${rec.from}</td>
                    <td class="text-secondary fw-semibold">${rec.to}</td>
                    <td class="fw-bold text-dark">${rec.days}</td>
                    <td class="text-muted fw-semibold">${rec.remainingBalance}</td>
                    <td class="text-muted" style="max-width: 250px;">
                        <span>${displayReason}</span> ${reasonButton}
                    </td>
                    <td>${flowHtml}</td>
                    <td>${statusBadge}</td>
                </tr>
            `;
        }).join('');

        // Attach modal event listeners
        tableBody.querySelectorAll('.view-reason-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById("modalEmployeeName").textContent = this.getAttribute("data-name");
                document.getElementById("modalLeaveCategory").textContent = this.getAttribute("data-category");
                document.getElementById("modalLeavePeriod").textContent = this.getAttribute("data-period");
                document.getElementById("modalLeaveDays").textContent = this.getAttribute("data-days");
                document.getElementById("modalLeaveReason").textContent = decodeURIComponent(this.getAttribute("data-reason"));

                const reasonModal = new bootstrap.Modal(document.getElementById("reasonModal"));
                reasonModal.show();
            });
        });
    }

    // Render Pagination Controls
    function renderPagination(total, page, limit) {
        const totalPages = Math.ceil(total / limit);
        const startRecord = total > 0 ? ((page - 1) * limit) + 1 : 0;
        const endRecord = Math.min(startRecord + limit - 1, total);

        summaryText.textContent = `Showing ${startRecord} to ${endRecord} of ${total} entries`;

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
            { el: cardTotal, val: "" },
            { el: cardApproved, val: "approved" },
            { el: cardPending, val: "pending" },
            { el: cardRejected, val: "rejected" }
        ];

        cards.forEach(card => {
            card.el.addEventListener("click", function () {
                cards.forEach(c => c.el.classList.remove("active-filter"));

                if (activeCardFilter === card.val) {
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
                <td colspan="12" class="text-center py-5 text-danger">
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

        const fromVal = fromDateInput.value;
        const toVal = toDateInput.value;

        let csv = [];
        csv.push(",,,,The Premium Homes Ltd");
        csv.push(",,,,Employee Leave History Report");
        csv.push(`,,,,Date Range: ${fromVal} to ${toVal}`);
        csv.push("");
        csv.push(",,,,Summary Overview");
        csv.push(`,,,,Total Requests,${statTotal.textContent}`);
        csv.push(`,,,,Total Approved,${statApproved.textContent}`);
        csv.push(`,,,,Total Pending,${statPending.textContent}`);
        csv.push(`,,,,Total Rejected,${statRejected.textContent}`);
        csv.push("");
        
        // Header Row
        csv.push("SN,Office ID,Employee Name,Department,Designation,Leave Category,From Date,To Date,Days,Remaining Balance,Reason,Approval Flow,Status");

        let sn = 1;
        filteredRecords.forEach(rec => {
            const statusText = rec.approvalStatus === 1 ? "Approved"
                             : rec.approvalStatus === 0 ? "Pending"
                             : "Rejected";

            const flowText = rec.lineManagerName 
                ? `L1: ${rec.lineManagerName} (${statusText}) | L2: HR/Admin (${statusText})`
                : `Admin (${statusText})`;

            const row = [
                sn++,
                rec.employeeOfficeId || '—',
                rec.employeeName,
                rec.departmentName,
                rec.designationName,
                rec.leaveCategoryName,
                rec.from,
                rec.to,
                rec.days,
                rec.remainingBalance,
                rec.reason || '—',
                flowText,
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
        link.setAttribute("download", `Leave_History_Report_${fromVal}_to_${toVal}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    // Export to PDF
    function exportToPDF() {
        if (filteredRecords.length === 0) {
            alert("No data available to export.");
            return;
        }

        const fromVal = fromDateInput.value;
        const toVal = toDateInput.value;

        let sn = 1;
        const rowsHtml = filteredRecords.map(rec => {
            const statusText = rec.approvalStatus === 1 ? "Approved"
                             : rec.approvalStatus === 0 ? "Pending"
                             : "Rejected";

            const flowText = rec.lineManagerName 
                ? `L1: ${rec.lineManagerName}<br>L2: HR/Admin`
                : `Admin`;

            return `
                <tr>
                    <td>${sn++}</td>
                    <td>${rec.employeeOfficeId || '—'}</td>
                    <td style="font-weight: 600;">${rec.employeeName}</td>
                    <td>${rec.departmentName}</td>
                    <td>${rec.designationName}</td>
                    <td>${rec.leaveCategoryName}</td>
                    <td>${rec.from}</td>
                    <td>${rec.to}</td>
                    <td style="font-weight: 600;">${rec.days}</td>
                    <td>${rec.remainingBalance}</td>
                    <td style="max-width: 200px; word-wrap: break-word;">${rec.reason || '—'}</td>
                    <td><small>${flowText}</small></td>
                    <td>${statusText}</td>
                </tr>
            `;
        }).join('');

        const printWindow = window.open("", "_blank");
        
        let htmlStr = '';
        htmlStr += '<!DOCTYPE html>';
        htmlStr += '<html>';
        htmlStr += '<head>';
        htmlStr += '    <title>Leave_History_Report_' + fromVal + '_to_' + toVal + '</title>';
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
        htmlStr += '        <h2 class="report-title">Employee Leave History Report</h2>';
        htmlStr += '        <div class="meta-info">';
        htmlStr += '            <strong>Date Range:</strong> ' + fromVal + ' to ' + toVal + ' &nbsp;&middot;&nbsp; ';
        htmlStr += '            <strong>Generated On:</strong> ' + new Date().toLocaleString();
        htmlStr += '        </div>';
        htmlStr += '    </div>';
        htmlStr += '    ';
        htmlStr += '    <!-- Stats Row -->';
        htmlStr += '    <div class="row g-3 mb-4">';
        htmlStr += '        <div class="col-3">';
        htmlStr += '            <div class="stat-card-custom border-start border-primary border-4">';
        htmlStr += '                <div class="stat-label">Total Requests</div>';
        htmlStr += '                <div class="stat-val text-primary">' + statTotal.textContent + '</div>';
        htmlStr += '            </div>';
        htmlStr += '        </div>';
        htmlStr += '        <div class="col-3">';
        htmlStr += '            <div class="stat-card-custom border-start border-success border-4">';
        htmlStr += '                <div class="stat-label">Approved</div>';
        htmlStr += '                <div class="stat-val text-success">' + statApproved.textContent + '</div>';
        htmlStr += '            </div>';
        htmlStr += '        </div>';
        htmlStr += '        <div class="col-3">';
        htmlStr += '            <div class="stat-card-custom border-start border-warning border-4">';
        htmlStr += '                <div class="stat-label">Pending</div>';
        htmlStr += '                <div class="stat-val text-warning">' + statPending.textContent + '</div>';
        htmlStr += '            </div>';
        htmlStr += '        </div>';
        htmlStr += '        <div class="col-3">';
        htmlStr += '            <div class="stat-card-custom border-start border-danger border-4">';
        htmlStr += '                <div class="stat-label">Rejected</div>';
        htmlStr += '                <div class="stat-val text-danger">' + statRejected.textContent + '</div>';
        htmlStr += '            </div>';
        htmlStr += '        </div>';
        htmlStr += '    </div>';
        htmlStr += '    ';
        htmlStr += '    <!-- Table -->';
        htmlStr += '    <table class="table table-bordered table-striped table-custom align-middle">';
        htmlStr += '        <thead>';
        htmlStr += '            <tr>';
        htmlStr += '                <th>SN</th>';
        htmlStr += '                <th>Office ID</th>';
        htmlStr += '                <th>Employee Name</th>';
        htmlStr += '                <th>Department</th>';
        htmlStr += '                <th>Designation</th>';
        htmlStr += '                <th>Leave Category</th>';
        htmlStr += '                <th>From Date</th>';
        htmlStr += '                <th>To Date</th>';
        htmlStr += '                <th>Days</th>';
        htmlStr += '                <th>Remaining Balance</th>';
        htmlStr += '                <th>Reason</th>';
        htmlStr += '                <th>Approval Flow</th>';
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

    // Live filter search trigger
    btnFilterSearch.addEventListener("click", function(e) {
        e.preventDefault();
        fetchLeaveData();
    });

    searchQuery.addEventListener("input", () => applyFiltersAndRender(1));
    departmentFilter.addEventListener("change", () => applyFiltersAndRender(1));
    categoryFilter.addEventListener("change", () => applyFiltersAndRender(1));
    statusFilter.addEventListener("change", () => applyFiltersAndRender(1));
    fromDateInput.addEventListener("change", fetchLeaveData);
    toDateInput.addEventListener("change", fetchLeaveData);

    // Start execution
    setupStatsCardListeners();
    fetchLeaveData();
});
<?php $page_script = ob_get_clean(); ?>

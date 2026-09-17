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
                    <th scope="col" style="min-width: 170px;">Actions</th>
                </tr>
            </thead>
            <tbody id="employeesTableBody">
                <tr>
                    <td colspan="9" class="text-center py-5">
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

<!-- Quick Register in ERP Modal -->
<div class="modal fade" id="quickRegisterModal" tabindex="-1" aria-labelledby="quickRegisterModalLabel" aria-hidden="true" style="z-index: 100000;">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 820px; width: 95%; margin: 1.75rem auto;">
        <div class="modal-content" style="border-radius: 12px; border: 1px solid #dee2e6; overflow: hidden; background-color: #ffffff; width: 100%; box-shadow: 0 15px 35px rgba(0,0,0,0.25);">
            <div class="modal-header bg-success text-white py-3 px-4" style="border-bottom: 1px solid rgba(0,0,0,0.1);">
                <h5 class="modal-title fw-bold text-white mb-0" id="quickRegisterModalLabel">
                    <i class="bi bi-person-plus-fill me-2"></i>Register Tipsoi Employee in ERP
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="background-color: #f8f9fa;">
                <div id="quickRegisterAlert"></div>
                <form id="quickRegisterForm">
                    <!-- Tipsoi Profile Details -->
                    <div class="mb-3 p-3 bg-white" style="border: 1px solid #e2e8f0; border-radius: 10px;">
                        <div class="d-flex align-items-center gap-2 mb-3 text-primary fw-bold">
                            <i class="bi bi-person-badge fs-5"></i>
                            <span>Employee Information (From Tipsoi)</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Employee Office ID <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="employee_id" id="quickEmpId" required readonly style="background-color: #f1f3f5;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="full_name" id="quickFullName" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Email Address</label>
                                <input type="email" class="form-control" name="email" id="quickEmail" placeholder="employee@company.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Contact Number</label>
                                <input type="text" class="form-control" name="phone" id="quickPhone" placeholder="017XXXXXXXX">
                            </div>
                        </div>
                    </div>

                    <!-- ERP Employment Details -->
                    <div class="mb-3 p-3 bg-white" style="border: 1px solid #e2e8f0; border-radius: 10px;">
                        <div class="d-flex align-items-center gap-2 mb-3 text-primary fw-bold">
                            <i class="bi bi-building-gear fs-5"></i>
                            <span>ERP Employment Details</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Department <span class="text-danger">*</span></label>
                                <select class="form-select" name="department_id" id="quickDepartmentId" required>
                                    <option value="">Select Department</option>
                                    <?php if (!empty($departments)): ?>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?= $dept['id'] ?>" data-name="<?= htmlspecialchars(strtolower(trim($dept['name']))) ?>">
                                                <?= htmlspecialchars($dept['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Designation <span class="text-danger">*</span></label>
                                <select class="form-select" name="designation_id" id="quickDesignationId" required>
                                    <option value="">Select Designation</option>
                                    <?php if (!empty($designations)): ?>
                                        <?php foreach ($designations as $desg): ?>
                                            <option value="<?= $desg['id'] ?>" 
                                                    data-dept-id="<?= $desg['department_id'] ?>" 
                                                    data-name="<?= htmlspecialchars(strtolower(trim($desg['designation_name']))) ?>">
                                                <?= htmlspecialchars($desg['designation_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Workplace / Office Branch</label>
                                <select class="form-select" name="office_branch_id" id="quickBranchId">
                                    <?php if (!empty($branches)): ?>
                                        <?php foreach ($branches as $branch): ?>
                                            <option value="<?= $branch['id'] ?>" <?= ($branch['id'] == 2) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($branch['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Joining Date</label>
                                <input type="date" class="form-control" name="joining_date" id="quickJoiningDate" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold text-dark">Gross Salary (BDT)</label>
                                <input type="number" step="0.01" class="form-control" name="gross_salary" id="quickGrossSalary" value="0.00" placeholder="0.00">
                                <small class="text-muted">Basic Salary will automatically be set as 50% of Gross.</small>
                            </div>
                        </div>
                    </div>

                    <!-- User Account Section -->
                    <div class="p-3 bg-white" style="border: 1px solid #e2e8f0; border-radius: 10px;">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="make_user" value="1" id="quickMakeUser" checked style="cursor: pointer;">
                            <label class="form-check-label fw-semibold text-dark" for="quickMakeUser" style="cursor: pointer;">
                                Create ERP Login User Account
                            </label>
                        </div>
                        <div class="row g-3" id="quickUserFields">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">User Role</label>
                                <select class="form-select" name="role_id" id="quickRoleId">
                                    <?php if (!empty($roles)): ?>
                                        <?php foreach ($roles as $r): ?>
                                            <option value="<?= $r['id'] ?>" <?= ($r['id'] == 2) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($r['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Initial Password</label>
                                <input type="text" class="form-control" name="password" id="quickPassword" value="12345678">
                                <small class="text-muted">Default login password: <code>12345678</code></small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-white border-top px-4 py-3">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success px-4 fw-semibold" id="btnSubmitQuickRegister">
                    <i class="bi bi-check-circle-fill me-1"></i> Register in ERP
                </button>
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

    // Helper to escape HTML special characters for attributes
    const escapeHtml = (str) => {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
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
                    <td colspan="9" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-2 mb-2 d-block"></i>
                        No employees found matching the current filters.
                    </td>
                </tr>
            `;
            return;
        }

        let sn = offset + 1;
        tableBody.innerHTML = pageItems.map(emp => {
            const rawOfficeId = emp.employeeOfficeId || emp.employee_id || '';
            const officeId = normalizeId(rawOfficeId);
            const empEmail = String(emp.email || '').toLowerCase().trim();
            const isRegistered = (officeId && normalizedLocalIds.includes(officeId)) ||
                                 (empEmail && normalizedLocalEmails.includes(empEmail));
            
            const statusBadge = isRegistered 
                ? '<span class="badge bg-success text-white px-2 py-1"><i class="bi bi-patch-check-fill me-1"></i>Registered</span>'
                : '<span class="badge bg-danger text-white px-2 py-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>Not in ERP</span>';

            const quickRegisterBtn = !isRegistered ? `
                <button class="btn btn-sm btn-success text-nowrap btn-quick-register py-1 px-2" 
                        data-office-id="${escapeHtml(rawOfficeId)}" 
                        data-name="${escapeHtml(emp.name || emp.full_name || '')}" 
                        data-email="${escapeHtml(emp.email || '')}" 
                        data-phone="${escapeHtml(emp.phoneMobile || emp.phoneOffice || emp.phone || '')}" 
                        data-dept="${escapeHtml(emp.departmentName || emp.department_name || '')}" 
                        data-desg="${escapeHtml(emp.designationName || emp.designation_name || '')}" 
                        title="Register in ERP">
                    <i class="bi bi-person-plus-fill me-1"></i>Add to ERP
                </button>
            ` : '';

            return `
                <tr>
                    <td class="fw-semibold text-muted">${sn++}</td>
                    <td class="fw-semibold text-dark">${rawOfficeId || 'N/A'}</td>
                    <td class="fw-bold">${emp.name || emp.full_name || 'N/A'}</td>
                    <td><span class="badge bg-light text-dark border">${emp.designationName || emp.designation_name || 'N/A'}</span></td>
                    <td>${emp.departmentName || emp.department_name || 'N/A'}</td>
                    <td>${emp.email || 'N/A'}</td>
                    <td><i class="bi bi-phone me-1 text-muted"></i>${emp.phoneMobile || emp.phoneOffice || emp.phone || 'N/A'}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <div class="d-flex gap-1 align-items-center">
                            ${quickRegisterBtn}
                            <button class="btn btn-sm btn-outline-primary btn-edit-employee py-1 px-2" data-id="${emp.id}" title="Update Tipsoi Employee">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger btn-delete-employee py-1 px-2" data-id="${emp.id}" title="Delete Tipsoi Employee">
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
        // Handle Quick Register in ERP
        const quickBtn = e.target.closest(".btn-quick-register");
        if (quickBtn) {
            openQuickRegisterModal(quickBtn);
            return;
        }

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

    // ==========================================
    // Quick Register in ERP Logic
    // ==========================================
    let quickRegisterModalInstance = null;
    const quickRegisterModalEl = document.getElementById('quickRegisterModal');
    if (quickRegisterModalEl) {
        quickRegisterModalInstance = new bootstrap.Modal(quickRegisterModalEl);
    }

    const quickDeptSelect = document.getElementById('quickDepartmentId');
    const quickDesgSelect = document.getElementById('quickDesignationId');
    const quickMakeUser = document.getElementById('quickMakeUser');
    const quickUserFields = document.getElementById('quickUserFields');
    const btnSubmitQuickRegister = document.getElementById('btnSubmitQuickRegister');
    const quickAlert = document.getElementById('quickRegisterAlert');

    // Clone all designation options to filter dynamically by department
    const allDesgOptions = quickDesgSelect ? Array.from(quickDesgSelect.options) : [];

    function filterDesignationsByDept(deptId, targetDesgName = '') {
        if (!quickDesgSelect) return;
        const currentVal = quickDesgSelect.value;
        quickDesgSelect.innerHTML = '';
        
        allDesgOptions.forEach(opt => {
            if (!opt.value || !deptId || opt.getAttribute('data-dept-id') == deptId) {
                quickDesgSelect.appendChild(opt.cloneNode(true));
            }
        });

        // Try to match target designation if provided
        if (targetDesgName) {
            let matched = false;
            for (let opt of quickDesgSelect.options) {
                const optName = (opt.getAttribute('data-name') || '').toLowerCase().trim();
                if (optName && (optName === targetDesgName || targetDesgName.includes(optName) || optName.includes(targetDesgName))) {
                    quickDesgSelect.value = opt.value;
                    matched = true;
                    break;
                }
            }
            if (!matched) {
                quickDesgSelect.value = '';
            }
        } else {
            quickDesgSelect.value = currentVal;
            if (quickDesgSelect.selectedIndex === -1) {
                quickDesgSelect.value = '';
            }
        }
    }

    if (quickDeptSelect) {
        quickDeptSelect.addEventListener('change', function() {
            filterDesignationsByDept(this.value);
        });
    }

    if (quickMakeUser) {
        quickMakeUser.addEventListener('change', function() {
            if (quickUserFields) {
                quickUserFields.style.display = this.checked ? 'flex' : 'none';
            }
        });
    }

    function openQuickRegisterModal(btn) {
        if (!quickRegisterModalInstance) {
            quickRegisterModalInstance = new bootstrap.Modal(document.getElementById('quickRegisterModal'));
        }

        quickAlert.innerHTML = '';
        const form = document.getElementById('quickRegisterForm');
        if (form) form.reset();

        if (quickMakeUser) quickMakeUser.checked = true;
        if (quickUserFields) quickUserFields.style.display = 'flex';
        document.getElementById('quickPassword').value = '12345678';
        document.getElementById('quickJoiningDate').value = new Date().toISOString().split('T')[0];
        document.getElementById('quickGrossSalary').value = '0.00';

        const rawOfficeId = btn.getAttribute('data-office-id') || '';
        const empName = btn.getAttribute('data-name') || '';
        const empEmail = btn.getAttribute('data-email') || '';
        const empPhone = btn.getAttribute('data-phone') || '';
        const tipsoiDept = (btn.getAttribute('data-dept') || '').toLowerCase().trim();
        const tipsoiDesg = (btn.getAttribute('data-desg') || '').toLowerCase().trim();

        document.getElementById('quickEmpId').value = rawOfficeId;
        document.getElementById('quickFullName').value = empName;
        document.getElementById('quickEmail').value = empEmail;
        document.getElementById('quickPhone').value = empPhone;

        // Auto-match department if possible
        let matchedDeptId = '';
        if (quickDeptSelect) {
            for (let opt of quickDeptSelect.options) {
                const optName = (opt.getAttribute('data-name') || '').toLowerCase().trim();
                if (optName && tipsoiDept && (optName === tipsoiDept || tipsoiDept.includes(optName) || optName.includes(tipsoiDept))) {
                    quickDeptSelect.value = opt.value;
                    matchedDeptId = opt.value;
                    break;
                }
            }
            if (!matchedDeptId) {
                quickDeptSelect.value = '';
            }
        }

        // Filter designations by matched department (and try to match designation name)
        filterDesignationsByDept(matchedDeptId, tipsoiDesg);

        quickRegisterModalInstance.show();
    }

    if (btnSubmitQuickRegister) {
        btnSubmitQuickRegister.addEventListener('click', function() {
            const form = document.getElementById('quickRegisterForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData(form);
            btnSubmitQuickRegister.disabled = true;
            btnSubmitQuickRegister.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Registering...';
            quickAlert.innerHTML = '';

            fetch(`${baseUrl}/api/hrm/quick-create-employee`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btnSubmitQuickRegister.disabled = false;
                btnSubmitQuickRegister.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Register in ERP';

                if (data.success) {
                    if (quickRegisterModalInstance) {
                        quickRegisterModalInstance.hide();
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Registered in ERP!',
                        text: data.message || 'Employee registered successfully in ERP.',
                        timer: 2500,
                        showConfirmButton: false
                    });

                    // Update local memory arrays so the matching is updated immediately
                    if (data.employee_office_id) {
                        normalizedLocalIds.push(normalizeId(data.employee_office_id));
                    }
                    if (data.email) {
                        normalizedLocalEmails.push(data.email.toLowerCase().trim());
                    }

                    // Recalculate stats and re-render current view
                    calculateStats(allEmployees, localEmployeeIds);
                    applyFiltersAndRender(currentPage);
                } else {
                    quickAlert.innerHTML = `
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-octagon-fill me-2"></i>${data.message || 'Failed to register employee.'}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;
                }
            })
            .catch(err => {
                btnSubmitQuickRegister.disabled = false;
                btnSubmitQuickRegister.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Register in ERP';
                quickAlert.innerHTML = `
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-octagon-fill me-2"></i>Network or server error: ${err.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
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

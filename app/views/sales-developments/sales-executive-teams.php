<?php
$page_title = "Sales Incentive Target";
$page = 'Sales Incentive Target';
$show_breadcrumb = false;
$base_url = $GLOBALS['base_url'] ?? '/TPHL-ERP';

// Build pagination query string to preserve filter parameters across pages
$paginationParams = [];
if (!empty($searchTerm)) {
    $paginationParams['q'] = $searchTerm;
}
if (!empty($selectedTeamId)) {
    $paginationParams['team_id'] = $selectedTeamId;
}
if (!empty($selectedUserId)) {
    $paginationParams['user_id'] = $selectedUserId;
}
$paginationQuery = !empty($paginationParams) ? ('?' . http_build_query($paginationParams)) : '';
$paginationUrl = $base_url . "/sales-executive-teams" . $paginationQuery;
?>

<style>
/* Filter Card & Select2 Enhancements */
.set-filter-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    border: 1px solid #eef2f6;
}
[data-bs-theme="dark"] .set-filter-card,
body.dark-mode .set-filter-card {
    background: #1e2430;
    border-color: #2b3548;
}
.set-filter-card .form-label {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    margin-bottom: 6px;
}
[data-bs-theme="dark"] .set-filter-card .form-label,
body.dark-mode .set-filter-card .form-label {
    color: #94a3b8;
}
.set-filter-card .select2-container {
    width: 100% !important;
}
.set-filter-card .select2-container--default .select2-selection--single {
    height: 42px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    display: flex;
    align-items: center;
    background-color: #fff;
    padding: 0 8px;
}
[data-bs-theme="dark"] .set-filter-card .select2-container--default .select2-selection--single,
body.dark-mode .set-filter-card .select2-container--default .select2-selection--single {
    background-color: #171d26;
    border-color: #334155;
    color: #e2e8f0;
}
.set-filter-card .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 40px;
    color: #1e293b;
    padding-left: 4px;
    font-size: 14px;
}
[data-bs-theme="dark"] .set-filter-card .select2-container--default .select2-selection--single .select2-selection__rendered,
body.dark-mode .set-filter-card .select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #e2e8f0;
}
.set-filter-card .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 40px;
    right: 8px;
}
.set-filter-card .form-control {
    height: 42px;
    border-radius: 8px;
    font-size: 14px;
}
.filter-active-badge {
    background: #e0f2fe;
    color: #0369a1;
    font-size: 11px;
    padding: 3px 8px;
    border-radius: 12px;
    font-weight: 600;
}
</style>

<!-- Header & Add User Action -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h4 class="mb-1 fw-bold">Sales Incentive Targets</h4>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted font-size-13">Sales executives & teams target management</span>
            <span class="badge bg-primary-subtle text-primary fw-semibold px-2 py-1 rounded-pill">
                Total: <?= number_format($totalUsers ?? 0) ?>
            </span>
            <?php if (!empty($selectedTeamId) || !empty($selectedUserId) || !empty($searchTerm)): ?>
                <span class="filter-active-badge">
                    <i class="fa fa-filter me-1"></i> Filter Active
                </span>
            <?php endif; ?>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $base_url ?>/create-user" class="btn btn-primary">
            <i class="fa-solid fa-user-plus me-1"></i> Add Sales User
        </a>
    </div>
</div>

<!-- Search & Filter Card -->
<div class="set-filter-card">
    <form method="GET" action="<?= $base_url ?>/sales-executive-teams" id="salesTeamFilterForm">
        <div class="row  align-items-end">

            <!-- Filter by Team (Select2 Searchable) -->
            <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-12">
                <label for="teamFilterSelect" class="form-label">
                    <i class="fa-solid fa-people-group me-1 text-primary"></i> Filter by Team
                </label>
                <select name="team_id" id="teamFilterSelect" class="form-select select2" data-placeholder="All Teams">
                    <option value="">All Teams (<?= count($teams ?? []) ?>)</option>
                    <?php if (!empty($teams)): ?>
                        <?php foreach ($teams as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= (!empty($selectedTeamId) && (int)$selectedTeamId === (int)$t['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Filter by Sales Employee (Select2 Searchable) -->
            <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-6 col-12">
                <label for="employeeFilterSelect" class="form-label">
                    <i class="fa-solid fa-user-tie me-1 text-primary"></i> Filter by Sales Employee
                </label>
                <select name="user_id" id="employeeFilterSelect" class="form-select select2" data-placeholder="All Sales Employees">
                    <option value=""><?= !empty($selectedTeamId) ? 'All Employees in this Team' : 'All Sales Employees (' . count($salesEmployees ?? []) . ')' ?></option>
                    <?php if (!empty($salesEmployees)): ?>
                        <?php foreach ($salesEmployees as $emp): ?>
                            <?php
                            // If a team is currently filtered, narrow the initial options to that team
                            if (!empty($selectedTeamId) && (string)($emp['team_id'] ?? '') !== (string)$selectedTeamId) {
                                continue;
                            }
                            $empLabel = htmlspecialchars($emp['full_name']);
                            $details = [];
                            if (!empty($emp['employee_id'])) {
                                $details[] = $emp['employee_id'];
                            }
                            if (!empty($emp['designation_name'])) {
                                $details[] = $emp['designation_name'];
                            }
                            if (!empty($emp['team_name'])) {
                                $details[] = $emp['team_name'];
                            }
                            if (!empty($details)) {
                                $empLabel .= ' (' . implode(' - ', $details) . ')';
                            }
                            $isEmpSelected = (!empty($selectedUserId) && (int)$selectedUserId === (int)$emp['id']);
                            ?>
                            <option value="<?= $emp['id'] ?>" data-team-id="<?= $emp['team_id'] ?? '' ?>" <?= $isEmpSelected ? 'selected' : '' ?>>
                                <?= $empLabel ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Keyword Search (Name / Phone / Email / Code) -->
            <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-12">
                <label for="keywordInput" class="form-label">
                    <i class="fa-solid fa-magnifying-glass me-1 text-primary"></i> Search Keyword
                </label>
                <input type="text" class="form-control" id="keywordInput" name="q" placeholder="Name, phone, email, employee code..."
                    value="<?= htmlspecialchars($searchTerm ?? '') ?>">
            </div>

            <!-- Filter Buttons -->
            <div class="col-xxl-2 col-xl-2 col-lg-12 col-md-6 col-12">
                <div class="d-flex align-items-center gap-2">
                    <button type="submit" class="btn btn-primary w-100" title="Apply Filters">
                        <i class="fa-solid fa-filter me-1"></i> Filter
                    </button>
                    <a href="<?= $base_url ?>/sales-executive-teams" class="btn btn-outline-secondary w-100" title="Reset Filters">
                        <i class="fa-solid fa-rotate-left me-1"></i> Reset
                    </a>
                </div>
            </div>

        </div>
    </form>
</div>

<!-- Sales Users Cards Grid -->
<div class="row">
    <?php if (!empty($salesUsers)): ?>
        <?php foreach ($salesUsers as $user): ?>
            <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 mb-4">
                <div class="card__wrapper h-100">
                    <div class="employee__wrapper text-center d-flex flex-column h-100 justify-content-between">
                        <div>
                            <div class="employee__thumb mb-10">
                                <img src="<?= getUserProfileImage($user) ?>" alt="<?= htmlspecialchars($user['full_name'] ?: 'User') ?>">
                            </div>
                            <div class="employee__content">
                                <div class="employee__meta">
                                    <h5 class="mb-1 fw-bold"><?= htmlspecialchars($user['full_name'] ?: "No Name") ?></h5>
                                    <?php if (!empty($user['employee_id'])): ?>
                                        <p class="mb-1 text-muted font-size-12"><i class="fa-solid fa-id-badge me-1"></i>ID: <?= htmlspecialchars($user['employee_id']) ?></p>
                                    <?php endif; ?>
                                    <p class="mb-1 text-muted font-size-13"><?= htmlspecialchars($user['designation_name'] ?: "No Designation") ?></p>
                                    <p class="mb-1 font-size-12 text-truncate" title="<?= htmlspecialchars($user['email'] ?: '') ?>">
                                        <i class="fa-regular fa-envelope me-1"></i><?= htmlspecialchars($user['email'] ?: "No email") ?>
                                    </p>
                                    <p class="mb-1 font-size-12">
                                        <span class="fw-semibold pe-1">Role:</span>
                                        <span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($user['role_name'] ?: "No role") ?></span>
                                    </p>
                                    <p class="mb-2 font-size-12">
                                        <span class="fw-semibold pe-1">Team:</span>
                                        <span class="badge bg-info-subtle text-info fw-semibold"><?= htmlspecialchars($user['team_name'] ?: "No Team") ?></span>
                                    </p>
                                </div>
                                <div class="common-social mb-20">
                                    <?php if (!empty($user['media_link1'])): ?>
                                        <a href="<?= htmlspecialchars($user['media_link1']) ?>" target="_blank"><i class="fa-brands fa-facebook-f"></i></a>
                                    <?php endif; ?>
                                    <?php if (!empty($user['media_link2'])): ?>
                                        <a href="<?= htmlspecialchars($user['media_link2']) ?>" target="_blank"><i class="fa-brands fa-linkedin-in"></i></a>
                                    <?php endif; ?>
                                    <?php if (!empty($user['phone'])): ?>
                                        <a href="tel:<?= htmlspecialchars($user['phone']) ?>" title="Call <?= htmlspecialchars($user['phone']) ?>">
                                            <i class="fa-solid fa-phone"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="employee__btn mt-auto pt-2">
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <a class="btn btn-outline-theme-border flex-fill"
                                    href="<?= $base_url ?>/user-profile/<?= $user['id'] ?>">View</a>
                                <a class="btn btn-primary flex-fill"
                                    href="<?= $base_url ?>/set-sales-target/<?= $user['id'] ?>">Set Target</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12 py-5 text-center">
            <div class="card__wrapper py-5">
                <i class="fa-solid fa-user-slash text-muted fa-3x mb-3"></i>
                <h5 class="text-muted">No Sales Users Found</h5>
                <p class="text-muted font-size-13 mb-3">Try clearing or adjusting your team, employee, or keyword search filters.</p>
                <a href="<?= $base_url ?>/sales-executive-teams" class="btn btn-outline-primary btn-sm">
                    <i class="fa-solid fa-rotate-left me-1"></i> Clear All Filters
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination (Preserving all filter parameters) -->
<div class="row my-4">
    <div class="col-12 d-flex justify-content-center">
        <?= paginateLinksSecondary($paginationUrl, $currentPage, $totalPages); ?>
    </div>
</div>

<script>
    const allSalesEmployees = <?= json_encode($salesEmployees ?? []) ?>;

    document.addEventListener("DOMContentLoaded", function () {
        // Dynamic client-side filtering: update Employee dropdown when Team changes
        const teamSelect = document.getElementById('teamFilterSelect');
        const empSelect = document.getElementById('employeeFilterSelect');

        if (teamSelect && empSelect) {
            teamSelect.addEventListener('change', function () {
                const selectedTeamId = this.value;
                const prevEmpVal = empSelect.value;

                // Rebuild employee select options based on selected team
                empSelect.innerHTML = '';

                // Default empty option
                const defaultOpt = document.createElement('option');
                defaultOpt.value = '';
                defaultOpt.textContent = selectedTeamId ? 'All Employees in this Team' : 'All Sales Employees (' + allSalesEmployees.length + ')';
                empSelect.appendChild(defaultOpt);

                let isPrevValStillValid = false;

                allSalesEmployees.forEach(function (emp) {
                    const empTeamId = emp.team_id ? String(emp.team_id) : '';
                    if (!selectedTeamId || empTeamId === String(selectedTeamId)) {
                        const opt = document.createElement('option');
                        opt.value = emp.id;
                        opt.setAttribute('data-team-id', empTeamId);

                        let label = emp.full_name || 'No Name';
                        const details = [];
                        if (emp.employee_id) details.push(emp.employee_id);
                        if (emp.designation_name) details.push(emp.designation_name);
                        if (emp.team_name) details.push(emp.team_name);
                        if (details.length) label += ' (' + details.join(' - ') + ')';

                        opt.textContent = label;

                        if (String(emp.id) === String(prevEmpVal)) {
                            opt.selected = true;
                            isPrevValStillValid = true;
                        }

                        empSelect.appendChild(opt);
                    }
                });

                if (!isPrevValStillValid && prevEmpVal) {
                    empSelect.value = '';
                }

                // Notify Select2 about updated options
                if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                    $('#employeeFilterSelect').trigger('change');
                }
            });
        }
    });
</script>
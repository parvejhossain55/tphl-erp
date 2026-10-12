<?php
$page_title = "employees lists ";
$page = 'Employee Lists';
$show_breadcrumb = true;

// Helper to generate stat card URL preserving existing search & dropdown filters
$buildStatUrl = function($targetFilter) use ($statusFilter) {
    $params = $_GET;
    unset($params['page'], $params['url']);
    if ($statusFilter === $targetFilter || $targetFilter === '') {
        unset($params['status_filter']);
    } else {
        $params['status_filter'] = $targetFilter;
    }
    $query = http_build_query(array_filter($params, function($v) { return $v !== '' && $v !== null; }));
    return $GLOBALS['base_url'] . '/employee-lists' . ($query ? '?' . $query : '');
};
?>
<style>
.stat-kpi-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 16px 18px;
    border: 1px solid #e2e8f0;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 14px;
    color: inherit;
    position: relative;
    overflow: hidden;
    height: 100%;
}
.stat-kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 22px -6px rgba(0, 0, 0, 0.08);
    color: inherit;
}
.stat-kpi-card.is-active {
    border: 2px solid #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
    background: #f0f7ff;
}
.stat-kpi-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.stat-kpi-content {
    flex-grow: 1;
    min-width: 0;
}
.stat-kpi-title {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.stat-kpi-val {
    font-size: 22px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.15;
}
.stat-kpi-sub {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 2px;
    white-space: nowrap;
}
.stat-theme-total .stat-kpi-icon { background: #eff6ff; color: #2563eb; }
.stat-theme-active .stat-kpi-icon { background: #ecfdf5; color: #059669; }
.stat-theme-inactive .stat-kpi-icon { background: #fef2f2; color: #dc2626; }
.stat-theme-join .stat-kpi-icon { background: #f0fdfa; color: #0d9488; }
.stat-theme-resign .stat-kpi-icon { background: #fff1f2; color: #e11d48; }

.fs-11 { font-size: 11px !important; }
.badge-soft-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.badge-soft-danger { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
.badge-soft-warning { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
.badge-soft-info { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
.badge-soft-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
</style>

<!-- Top Action Bar -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <h5 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-users text-primary me-2"></i>Employee Directory</h5>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="<?= $GLOBALS['base_url'] ?>/employees-pdf-download?<?= $_SERVER['QUERY_STRING'] ?? '' ?>" class="btn btn-sm btn-outline-danger shadow-sm">
            <i class="fa-solid fa-file-pdf me-1"></i> PDF Download
        </a>
        <a href="<?= $GLOBALS['base_url'] ?>/employees-excel-download?<?= $_SERVER['QUERY_STRING'] ?? '' ?>" class="btn btn-sm btn-outline-success shadow-sm">
            <i class="fa-solid fa-file-excel me-1"></i> Excel Download
        </a>
        <a href="<?= $GLOBALS['base_url'] ?>/create-employee" class="btn btn-sm btn-primary shadow-sm">
            <i class="fa-solid fa-plus me-1"></i> Add Employee
        </a>
    </div>
</div>

<!-- 5 Interactive KPI Stats Cards -->
<div class="row g-2 mb-3">
    <!-- Total Employee -->
    <div class="col-xl col-lg-4 col-md-6 col-sm-6 col-12">
        <a href="<?= $buildStatUrl('') ?>" class="stat-kpi-card stat-theme-total <?= empty($statusFilter) ? 'is-active' : '' ?>" title="View All Employees">
            <div class="stat-kpi-icon">
                <i class="fa-solid fa-users"></i>
            </div>
            <div class="stat-kpi-content">
                <div class="stat-kpi-title">Total Employees</div>
                <div class="stat-kpi-val"><?= number_format($stats['total'] ?? 0) ?></div>
                <div class="stat-kpi-sub">All workforce</div>
            </div>
        </a>
    </div>

    <!-- Active Employee -->
    <div class="col-xl col-lg-4 col-md-6 col-sm-6 col-12">
        <a href="<?= $buildStatUrl('active') ?>" class="stat-kpi-card stat-theme-active <?= ($statusFilter === 'active') ? 'is-active' : '' ?>" title="Filter Active Employees">
            <div class="stat-kpi-icon">
                <i class="fa-solid fa-user-check"></i>
            </div>
            <div class="stat-kpi-content">
                <div class="stat-kpi-title">Active</div>
                <div class="stat-kpi-val"><?= number_format($stats['active'] ?? 0) ?></div>
                <div class="stat-kpi-sub">Currently working</div>
            </div>
        </a>
    </div>

    <!-- Inactive Employee -->
    <div class="col-xl col-lg-4 col-md-6 col-sm-6 col-12">
        <a href="<?= $buildStatUrl('inactive') ?>" class="stat-kpi-card stat-theme-inactive <?= ($statusFilter === 'inactive') ? 'is-active' : '' ?>" title="Filter Inactive Employees">
            <div class="stat-kpi-icon">
                <i class="fa-solid fa-user-xmark"></i>
            </div>
            <div class="stat-kpi-content">
                <div class="stat-kpi-title">Inactive</div>
                <div class="stat-kpi-val"><?= number_format($stats['inactive'] ?? 0) ?></div>
                <div class="stat-kpi-sub">Inactive / Terminated</div>
            </div>
        </a>
    </div>

    <!-- Recent Join (Selected/Current Month) -->
    <div class="col-xl col-lg-6 col-md-6 col-sm-6 col-12">
        <a href="<?= $buildStatUrl('recent_join') ?>" class="stat-kpi-card stat-theme-join <?= ($statusFilter === 'recent_join') ? 'is-active' : '' ?>" title="Filter Recent Joins">
            <div class="stat-kpi-icon">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <div class="stat-kpi-content">
                <div class="stat-kpi-title">Recent Join</div>
                <div class="stat-kpi-val"><?= number_format($stats['recent_join'] ?? 0) ?></div>
                <div class="stat-kpi-sub"><i class="fa-regular fa-calendar-check me-1"></i><?= date('F Y', strtotime($displayMonth . '-01')) ?></div>
            </div>
        </a>
    </div>

    <!-- Recent Resign (Selected/Current Month) -->
    <div class="col-xl col-lg-6 col-md-6 col-sm-6 col-12">
        <a href="<?= $buildStatUrl('recent_resign') ?>" class="stat-kpi-card stat-theme-resign <?= ($statusFilter === 'recent_resign') ? 'is-active' : '' ?>" title="Filter Recent Resignations">
            <div class="stat-kpi-icon">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </div>
            <div class="stat-kpi-content">
                <div class="stat-kpi-title">Recent Resign</div>
                <div class="stat-kpi-val"><?= number_format($stats['recent_resign'] ?? 0) ?></div>
                <div class="stat-kpi-sub"><i class="fa-regular fa-calendar-xmark me-1"></i><?= date('F Y', strtotime($displayMonth . '-01')) ?></div>
            </div>
        </a>
    </div>
</div>

<form action="" method="get" class="mb-20">
    <?php if (!empty($statusFilter)): ?>
        <input type="hidden" name="status_filter" value="<?= htmlspecialchars($statusFilter) ?>">
    <?php endif; ?>

    <!-- Filter Bar Card -->
    <div class="card__wrapper p-3 shadow-sm">
        <div class="row g-2 align-items-center">

            <!-- Search -->
            <div class="col-xxl-3 col-lg-3 col-md-6 col-12">
                <div class="search-box position-relative">
                    <input type="text" class="form-control" name="q" id="employeeName"
                        placeholder="Employee Name / Username / ID" autocomplete="off"
                        value="<?= htmlspecialchars($searchTerm ?? '') ?>">
                        
                    <div id="employeeSuggestions" class="dropdown-menu w-100 shadow-sm" style="display:none; position: absolute; max-height:250px; overflow-y:auto; top:100%; left:0; z-index:1050; padding:0; margin-top:2px;">
                    </div>
                </div>
            </div>

            <!-- Department Filter -->
            <div class="col-xxl-2 col-lg-2 col-md-6 col-12">
                <select class="form-select" name="department_id">
                    <option value="">Employee Department</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= $department['id'] ?>"
                            <?= $selectedDepartment == $department['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($department['name']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <!-- Team Filter -->
            <div class="col-xxl-2 col-lg-2 col-md-6 col-12">
                <select class="form-select" name="team_id">
                    <option value="">All Teams</option>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?= $team['id'] ?>"
                            <?= ($selectedTeam ?? '') == $team['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($team['name']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <!-- Workplace Filter -->
            <div class="col-xxl-2 col-lg-2 col-md-6 col-12">
                <select class="form-select" name="workplace">
                    <option value="">All Workplaces</option>
                    <?php foreach ($workplaces ?? [] as $wp): ?>
                        <option value="<?= htmlspecialchars($wp['workplace']) ?>"
                            <?= ($selectedWorkplace ?? '') === $wp['workplace'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($wp['workplace']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <!-- Month Filter -->
            <div class="col-xxl-2 col-lg-2 col-md-6 col-12">
                <input type="month" class="form-control" name="month" id="filterMonth"
                    value="<?= htmlspecialchars($selectedMonth ?? '') ?>"
                    title="Select month for Recent Join/Resign" placeholder="Select Month">
            </div>

            <!-- Submit / Reset Buttons -->
            <div class="col-xxl-1 col-lg-1 col-md-6 col-12">
                <div class="d-flex align-items-center gap-1">
                    <button type="submit" class="btn btn-secondary flex-fill text-nowrap p-2" title="Apply Filter">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    <a href="<?= $GLOBALS['base_url'] ?>/employee-lists"
                        class="btn btn-outline-secondary flex-fill text-nowrap p-2" title="Reset All Filters">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </div>

        </div>
    </div>
</form>

<?php if (!empty($statusFilter)): ?>
    <div class="alert alert-primary bg-primary-subtle border-primary-subtle py-2 px-3 d-flex align-items-center justify-content-between mb-3 shadow-sm rounded-3">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-filter text-primary"></i>
            <span>
                Showing <strong>
                    <?= $statusFilter === 'active' ? 'Active Employees' : 
                       ($statusFilter === 'inactive' ? 'Inactive Employees' : 
                       ($statusFilter === 'recent_join' ? 'Recent Joined Employees (' . date('F Y', strtotime($displayMonth . '-01')) . ')' : 
                       ($statusFilter === 'recent_resign' ? 'Recent Resigned Employees (' . date('F Y', strtotime($displayMonth . '-01')) . ')' : htmlspecialchars($statusFilter)))) ?>
                </strong>
                — <strong><?= $totalEmployees ?></strong> found
            </span>
        </div>
        <a href="<?= $buildStatUrl('') ?>" class="btn btn-sm btn-outline-primary bg-white">
            <i class="fa-solid fa-xmark me-1"></i> Clear Filter
        </a>
    </div>
<?php endif; ?>

<?php
$currentQuery = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
$listReturnUrl = $GLOBALS['base_url'] . '/employee-lists' . $currentQuery;
?>
<div class="row align-items-stretch">
    <?php if (empty($employees)): ?>
        <div class="col-12 py-5 text-center">
            <div class="card__wrapper p-5 shadow-sm">
                <div class="mb-3 text-muted">
                    <i class="fa-solid fa-user-slash" style="font-size: 54px; opacity: 0.6;"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">No Employees Found</h5>
                <p class="text-muted mb-3">No employee records match the selected filter criteria.</p>
                <a href="<?= $GLOBALS['base_url'] ?>/employee-lists" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-rotate-left me-1"></i> Reset Filters
                </a>
            </div>
        </div>
    <?php endif; ?>

    <?php foreach ($employees as $employee): ?>
        <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 px-2">
            <div class="card__wrapper">
                <div class="employee__wrapper text-center">
                    <div class="employee__thumb mb-15">
                        <a href="<?= $GLOBALS['base_url'] ?>/employee-profile/<?= $employee['id'] ?>">
                            <img src="<?= getImage('employees', $employee['photo']) ?? '' ?>" alt="image"></a>
                    </div>
                    <div class="employee__content">
                        <div class="employee__meta mb-15">
                            <h4 class="mb-8"><a href="<?= $GLOBALS['base_url'] ?>/employee-profile/<?= $employee['id'] ?>"><?= $employee['full_name'] ?? '' ?></a></h4>
                            
                            <!-- Status Badges -->
                            <div class="d-flex justify-content-center align-items-center flex-wrap gap-1 mb-2">
                                <?php 
                                $empStatus = strtolower($employee['employee_status'] ?? 'active');
                                if ($empStatus === 'active'): ?>
                                    <span class="badge badge-soft-success px-2 py-1 fs-11 rounded-pill">
                                        <i class="fa-solid fa-circle-check me-1"></i>Active
                                    </span>
                                <?php elseif (in_array($empStatus, ['inactive', 'terminated'])): ?>
                                    <span class="badge badge-soft-danger px-2 py-1 fs-11 rounded-pill">
                                        <i class="fa-solid fa-circle-xmark me-1"></i><?= ucfirst($empStatus) ?>
                                    </span>
                                <?php elseif ($empStatus === 'resigned'): ?>
                                    <span class="badge badge-soft-warning px-2 py-1 fs-11 rounded-pill">
                                        <i class="fa-solid fa-arrow-right-from-bracket me-1"></i>Resigned
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-soft-secondary px-2 py-1 fs-11 rounded-pill">
                                        <?= htmlspecialchars(ucfirst($empStatus)) ?>
                                    </span>
                                <?php endif; ?>

                                <?php 
                                $jDate = $employee['joining_date'] ?? '';
                                $jTime = (!empty($jDate) && $jDate !== '0000-00-00') ? strtotime($jDate) : false;
                                if ($jTime && date('Y-m', $jTime) === $displayMonth): ?>
                                    <span class="badge badge-soft-info px-2 py-1 fs-11 rounded-pill" title="Joined: <?= date('d M Y', $jTime) ?>">
                                        <i class="fa-solid fa-user-plus me-1"></i>New Join (<?= date('M Y', $jTime) ?>)
                                    </span>
                                <?php endif; ?>

                                <?php 
                                $rDate = $employee['resign_date'] ?? '';
                                $rTime = (!empty($rDate) && $rDate !== '0000-00-00') ? strtotime($rDate) : false;
                                if ($rTime && date('Y-m', $rTime) === $displayMonth): ?>
                                    <span class="badge badge-soft-danger px-2 py-1 fs-11 rounded-pill" title="Resigned: <?= date('d M Y', $rTime) ?>">
                                        <i class="fa-solid fa-calendar-xmark me-1"></i>Resigned (<?= date('M Y', $rTime) ?>)
                                    </span>
                                <?php endif; ?>
                            </div>

                            <p><?= $employee['department_name'] ?> - <strong><?= $employee['designation_name'] ?? '' ?></strong></p>

                            <p>Employee ID - <?= $employee['employee_id'] ?> 
                            <?php if (!empty($employee['team_name'])): ?>
                               - <strong><?= $employee['team_name'] ?></strong>
                            <?php endif; ?>
                            </p>
                            <p class="fs-12 text-primary mb-1"><i class="fa-solid fa-building me-1"></i><?= htmlspecialchars($employee['office_branch_name'] ?? 'Ashulia Site Office') ?></p>
                        </div>
                        <div class="common-social mb-20">
                            <a href="<?= formatUrl($employee['social_media1']) ?>"><i class="fa-brands fa-facebook-f"></i></a>
                            <a href="<?= formatUrl($employee['social_media2']) ?>" target="_blank" rel="noopener" title="LinkedIn">
                                <i class="fa-brands fa-linkedin"></i>
                            </a>
                            <a href="tel:<?= htmlspecialchars($employee['phone']) ?>" title="Call">
                                <i class="fa-solid fa-phone"></i>
                            </a>
                        </div>
                        <div class="employee__btn">
                            <div class="d-flex align-items-center justify-content-center gap-10">
                                <a class="btn btn-outline-theme-border" href="<?= $GLOBALS['base_url'] ?>/employee-profile/<?= $employee['id'] ?>">View</a>
                                <a class="btn btn-outline-theme-border" href="<?= $GLOBALS['base_url'] ?>/edit-employee/<?= $employee['id'] ?>?return_url=<?= urlencode($listReturnUrl) ?>">Edit</a>
                                <button class="removeBtnCust btn btn-outline-theme-border delete"
                                    data-id="<?= $employee['id'] ?>"
                                    data-name="<?= $employee['full_name'] ?>"
                                    data-action="delete-employee">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach ?>


    <!-- Count Info + Pagination -->
    <?php
        $currentPageCount = count($employees);
        $startRecord = $totalEmployees > 0 ? (($currentPage - 1) * $perPage) + 1 : 0;
        $endRecord   = min($startRecord + $currentPageCount - 1, $totalEmployees);
    ?>
    <div class="row mt-4 mb-2 align-items-center">
        <div class="col-12">
            <div class="card__wrapper py-2 px-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-people-fill text-primary fs-5"></i>
                        <span class="fw-semibold">
                            Showing
                            <span class="text-primary fw-bold"><?= $startRecord ?></span>
                            –
                            <span class="text-primary fw-bold"><?= $endRecord ?></span>
                            of
                            <span class="text-success fw-bold"><?= $totalEmployees ?></span>
                            employees
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2 text-muted" style="font-size: 13px;">
                        <i class="bi bi-file-earmark-person"></i>
                        Page <strong><?= $currentPage ?></strong> of <strong><?= $totalPages ?></strong>
                        &nbsp;|&nbsp;
                        <strong><?= $currentPageCount ?></strong> shown on this page
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-center">
            <?= paginateLinksSecondary($paginationRoute ?? ($base_url . "/employee-lists"), $currentPage, $totalPages); ?>
        </div>
    </div>
</div>
<script>
    const base_url = "<?= $GLOBALS['base_url'] ?>";

    document.addEventListener("DOMContentLoaded", function() {
        const searchInput = document.getElementById('employeeName');
        const suggestionsBox = document.getElementById('employeeSuggestions');
        let timeout = null;

        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            const query = this.value.trim();

            if (query.length < 2) {
                suggestionsBox.style.display = 'none';
                return;
            }

            timeout = setTimeout(() => {
                fetch(`${base_url}/api/employee/search-suggestions?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        suggestionsBox.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(emp => {
                                const item = document.createElement('a');
                                item.className = 'dropdown-item px-3 py-2 border-bottom';
                                item.style.cursor = 'pointer';
                                item.innerHTML = `<strong>${emp.full_name}</strong> <small class="text-muted">(${emp.employee_id || 'N/A'})</small><br><small class="text-primary">@${emp.username || 'N/A'}</small>`;
                                item.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    searchInput.value = emp.full_name;
                                    suggestionsBox.style.display = 'none';
                                    searchInput.closest('form').submit();
                                });
                                suggestionsBox.appendChild(item);
                            });
                            suggestionsBox.style.display = 'block';
                        } else {
                            suggestionsBox.innerHTML = '<div class="px-3 py-2 text-muted">No suggestions found</div>';
                            suggestionsBox.style.display = 'block';
                        }
                    })
                    .catch(err => console.error("Error fetching suggestions", err));
            }, 300);
        });

        // Hide when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
                suggestionsBox.style.display = 'none';
            }
        });
    });
</script>
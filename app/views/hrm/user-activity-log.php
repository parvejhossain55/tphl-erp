<?php
$base_url   = $GLOBALS['base_url'];
$filterDeptId   = $filterDeptId   ?? null;
$filterDateFrom = $filterDateFrom ?? null;
$filterDateTo   = $filterDateTo   ?? null;
$logs           = $logs           ?? [];
$departments    = $departments    ?? [];
$deptSummary    = $deptSummary    ?? [];

$currentPage    = $currentPage    ?? 1;
$totalPages     = $totalPages     ?? 1;
$showAll        = $showAll        ?? false;
$rawLogsCount   = $rawLogsCount   ?? count($logs);
$perPage        = $perPage        ?? 15;
$offset         = ($currentPage - 1) * $perPage;

function getPageUrl($p) {
    $params = $_GET;
    $params['page'] = $p;
    unset($params['show_all']);
    return '?' . http_build_query($params);
}

function getShowAllUrl() {
    $params = $_GET;
    $params['show_all'] = 1;
    unset($params['page']);
    return '?' . http_build_query($params);
}

function getShowPaginatedUrl() {
    $params = $_GET;
    unset($params['show_all']);
    $params['page'] = 1;
    return '?' . http_build_query($params);
}

// Helper: convert minutes to human-readable
function formatDuration(int $mins): string {
    if ($mins <= 0) return '<span class="badge bg-secondary">-</span>';
    $h = intdiv($mins, 60);
    $m = $mins % 60;
    if ($h > 0) return "{$h}h {$m}m";
    return "{$m}m";
}

function durationBadgeClass(int $mins): string {
    if ($mins >= 120) return 'bg-success';
    if ($mins >= 30)  return 'bg-primary';
    if ($mins >= 10)  return 'bg-warning ';
    return 'bg-danger';
}
?>

<div class="container-fluid">

    <!-- ── Page Header ───────────────────────────────────────────────────── -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h1 class="page-title fw-semibold fs-18 mb-0">User Activity Log</h1>
            <p class="text-muted mb-0 mt-1">Department-wise login & active session report</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/hrm-dashboard">HR Dashboard</a></li>
                <li class="breadcrumb-item active">User Activity Log</li>
            </ol>
        </nav>
    </div>

    <!-- ── Filter Card ───────────────────────────────────────────────────── -->
    <div class="card__wrapper mb-4">
        <div class="card-header py-3 d-flex align-items-center gap-2">
            <i class="fa-solid fa-filter text-primary"></i>
            <span class="fw-semibold">Filter Records</span>
        </div>
        <div class="p-3">
            <form method="GET" action="<?= $base_url ?>/user-activity-log" id="filterForm">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" for="department_id">Department</label>
                        <select name="department_id" id="department_id" class="form-select select2">
                            <option value="">— All Departments —</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= (int)$dept['id'] ?>"
                                    <?= ($filterDeptId == $dept['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold" for="date_from">Date From</label>
                        <input type="date" name="date_from" id="date_from" class="form-control"
                            value="<?= htmlspecialchars($filterDateFrom ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold" for="date_to">Date To</label>
                        <input type="date" name="date_to" id="date_to" class="form-control"
                            value="<?= htmlspecialchars($filterDateTo ?? '') ?>">
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fa-solid fa-magnifying-glass me-1"></i>Filter
                        </button>
                        <a href="<?= $base_url ?>/user-activity-log" class="btn btn-outline-secondary w-100">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ── Employee Time Search ──────────────────────────────────────────── -->
    <div class="card__wrapper mb-4" id="empSearchCard">
        <div class="card-header py-3 d-flex align-items-center gap-2">
            <i class="fa-solid fa-user-magnifying-glass text-success"></i>
            <span class="fw-semibold">Employee Time Search</span>
            <span class="badge bg-success-subtle text-success border border-success-subtle ms-1">Department-wise</span>
        </div>
        <div class="p-3">
            <div class="row align-items-end g-2">
                <div class="col-md-3">
                    <label class="form-label fw-semibold" for="empDeptSelect">Department</label>
                    <select id="empDeptSelect" class="form-select select2">
                        <option value="">— Select Department —</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= (int)$dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold" for="empSelect">Employee</label>
                    <select id="empSelect" class="form-select select2" disabled>
                        <option value="">— Select Department First —</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold" for="empDateFrom">Date From</label>
                    <input type="date" id="empDateFrom" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold" for="empDateTo">Date To</label>
                    <input type="date" id="empDateTo" class="form-control">
                </div>
                <div class="col-md-2">
                    <button id="empSearchBtn" class="btn btn-success w-100" disabled>
                        <i class="fa-solid fa-magnifying-glass me-1"></i>Search
                    </button>
                </div>
            </div>
        </div>

        <!-- Result Section (hidden initially) -->
        <div id="empResultSection" class="d-none px-3 pb-3">
            <hr class="my-2">

            <!-- Employee Info Banner -->
            <div id="empInfoBanner" class="alert alert-success d-flex align-items-center gap-3 py-2 mb-3">
                <div class="rounded-circle bg-success d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width:44px;height:44px;">
                    <i class="fa-solid fa-user text-white"></i>
                </div>
                <div>
                    <div class="fw-bold fs-6" id="empName">—</div>
                    <small class="text-light" id="empMeta">—</small>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="card__wrapper border-start border-4 border-primary h-100">
                        <div class="p-3 d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 fs-12 fw-semibold text-uppercase">Total Sessions</p>
                                <h3 class="fw-bold text-primary mb-0" id="statSessions">—</h3>
                            </div>
                            <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center"
                                 style="width:48px;height:48px;">
                                <i class="fa-solid fa-right-to-bracket text-primary fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card__wrapper border-start border-4 border-success h-100">
                        <div class="p-3 d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 fs-12 fw-semibold text-uppercase">Total Active Time</p>
                                <h3 class="fw-bold text-success mb-0" id="statTotal">—</h3>
                            </div>
                            <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center"
                                 style="width:48px;height:48px;">
                                <i class="fa-solid fa-clock text-success fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card__wrapper border-start border-4 border-warning h-100">
                        <div class="p-3 d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 fs-12 fw-semibold text-uppercase">Avg per Session</p>
                                <h3 class="fw-bold text-warning mb-0" id="statAvg">—</h3>
                            </div>
                            <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center"
                                 style="width:48px;height:48px;">
                                <i class="fa-solid fa-chart-bar text-warning fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Date-wise Breakdown Table -->
            <div class="table__wrapper table-responsive">
                <table class="table no-border-table mb-0" id="empDailyTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Sessions</th>
                            <th>First Login</th>
                            <th>Last Active</th>
                            <th>Active Duration</th>
                        </tr>
                    </thead>
                    <tbody id="empDailyBody">
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div id="empLoadingSpinner" class="d-none text-center py-4">
            <div class="spinner-border text-success" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted mt-2 mb-0">Fetching employee data...</p>
        </div>

        <!-- No Data Message -->
        <div id="empNoData" class="d-none text-center py-4 text-muted">
            <i class="fa-solid fa-inbox fa-2x mb-2 d-block opacity-30"></i>
            No activity records found for the selected employee.
        </div>
    </div>

    <!-- ── Department Summary Cards ──────────────────────────────────────── -->
    <?php if (!empty($deptSummary)): ?>
    <div class="row  mb-4">
        <?php foreach ($deptSummary as $s):
            $totalHrs = intdiv((int)$s['total_minutes'], 60);
            $totalMins = (int)$s['total_minutes'] % 60;
        ?>
        <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
            <div class="card__wrapper h-100 border-start border-primary border-4">
                <div class="p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1 fs-12 text-uppercase fw-semibold">
                                <?= htmlspecialchars($s['dept_name']) ?>
                            </p>
                            <h4 class="fw-bold mb-0 text-primary"><?= (int)$s['total_sessions'] ?> sessions</h4>
                            <small class="text-success fw-semibold">
                                <i class="fa-solid fa-clock me-1"></i>
                                <?= $totalHrs ?>h <?= $totalMins ?>m total active
                            </small>
                        </div>
                        <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center"
                            style="width:44px;height:44px;">
                            <i class="fa-solid fa-building text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>



    <!-- ── Main Data Table ───────────────────────────────────────────────── -->
    <div class="card__wrapper">
        <div class="card-header py-3 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-user-clock text-primary"></i>
                <span class="fw-semibold">Login Activity Records</span>
                <span class="badge bg-primary ms-1"><?= (int)$rawLogsCount ?> records</span>
            </div>
            <div>
                <?php if ($showAll): ?>
                    <a href="<?= getShowPaginatedUrl() ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fa-solid fa-list me-1"></i>Show Paginated (15 per page)
                    </a>
                <?php else: ?>
                    <a href="<?= getShowAllUrl() ?>" class="btn btn-sm btn-primary text-white">
                        <i class="fa-solid fa-eye me-1"></i>Show All Records
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="table__wrapper table-responsive p-2">
            <table id="activityLogTable" class="table no-border-table mb-0 dataTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th>Role</th>
                        <th>Login Time</th>
                        <th>Last Active</th>
                        <th>Active Duration</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-inbox fa-2x mb-3 d-block opacity-30"></i>
                                No activity records found for the selected criteria.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php
                    $serial = $showAll ? 1 : ($offset + 1);
                    $prevDept = null;
                    foreach ($logs as $log):
                        $dept        = $log['department_name'] ?: '—';
                        $activeMin   = max(0, (int)$log['active_minutes']);
                        $badgeClass  = durationBadgeClass($activeMin);
                        $durationStr = formatDuration($activeMin);
                        $loginTime   = date('d M Y, h:i A', strtotime($log['login_time']));
                        $lastActive  = $log['last_active_time']
                            ? date('d M Y, h:i A', strtotime($log['last_active_time']))
                            : '—';

                        // Department separator row
                        if ($dept !== $prevDept):
                            $prevDept = $dept;
                    ?>
                        <tr class="table-light">
                            <td colspan="8" class="fw-bold text-primary py-2 ps-3">
                                <i class="fa-solid fa-building me-2"></i>
                                <?= htmlspecialchars($dept) ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                        <tr>
                            <td class="text-muted"><?= $serial++ ?></td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($log['full_name'] ?? '—') ?></div>
                                <small class="text-muted"><?= htmlspecialchars($log['email'] ?? '') ?></small>
                            </td>
                            <td><?= htmlspecialchars($dept) ?></td>
                            <td><?= htmlspecialchars($log['designation_name'] ?? '—') ?></td>
                            <td>
                                <span class="badge bg-secondary bg-opacity-10  border">
                                    <?= htmlspecialchars($log['role_name'] ?? '—') ?>
                                </span>
                            </td>
                            <td>
                                <span class="text-nowrap"><?= $loginTime ?></span>
                            </td>
                            <td>
                                <span class="text-nowrap"><?= $lastActive ?></span>
                            </td>
                            <td>
                                <span class="badge <?= $badgeClass ?> px-3 py-2">
                                    <i class="fa-solid fa-clock me-1"></i>
                                    <?= $durationStr ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (!$showAll && $totalPages > 1): ?>
        <div class="card-footer py-3 d-flex align-items-center justify-content-between border-top">
            <div class="text-muted small">
                Showing <?= $offset + 1 ?> to <?= min($offset + $perPage, $rawLogsCount) ?> of <?= $rawLogsCount ?> records (Page <?= $currentPage ?> of <?= $totalPages ?>)
            </div>
            <nav aria-label="Activity Log Pagination">
                <ul class="pagination mb-0 gap-1">
                    <?php if ($currentPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= getPageUrl(1) ?>" aria-label="First">
                                <i class="fa-solid fa-angles-left"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="<?= getPageUrl($currentPage - 1) ?>" aria-label="Previous">
                                <i class="fa-solid fa-angle-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);
                    for ($p = $startPage; $p <= $endPage; $p++):
                    ?>
                        <li class="page-item <?= ($p == $currentPage) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= getPageUrl($p) ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= getPageUrl($currentPage + 1) ?>" aria-label="Next">
                                <i class="fa-solid fa-angle-right"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="<?= getPageUrl($totalPages) ?>" aria-label="Last">
                                <i class="fa-solid fa-angles-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── Duration Legend ──────────────────────────────────────────────────── -->
<div class="d-flex gap-3 align-items-center flex-wrap mt-3 px-1">
    <small class="text-muted fw-semibold">Duration Legend:</small>
    <span class="badge bg-success px-3">≥ 2 hours</span>
    <span class="badge bg-primary px-3">30 min – 2 hrs</span>
    <span class="badge bg-warning  px-3">10 – 30 min</span>
    <span class="badge bg-danger px-3">&lt; 10 min</span>
</div>

<!-- ── Employee Time Search – JavaScript ────────────────────────────────── -->
<script>
document.addEventListener('DOMContentLoaded', function () {
(function () {
    const BASE_URL = '<?= $base_url ?>';

    const deptSel    = document.getElementById('empDeptSelect');
    const empSel     = document.getElementById('empSelect');
    const searchBtn  = document.getElementById('empSearchBtn');
    const dateFrom   = document.getElementById('empDateFrom');
    const dateTo     = document.getElementById('empDateTo');

    const resultSec  = document.getElementById('empResultSection');
    const spinner    = document.getElementById('empLoadingSpinner');
    const noData     = document.getElementById('empNoData');

    // ── Helpers ──────────────────────────────────────────────────────────────
    function fmtMins(mins) {
        mins = Math.max(0, parseInt(mins) || 0);
        if (mins === 0) return '0m';
        const h = Math.floor(mins / 60);
        const m = mins % 60;
        return h > 0 ? `${h}h ${m}m` : `${m}m`;
    }

    function fmtDate(dateStr) {
        if (!dateStr) return '—';
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function fmtDateTime(dtStr) {
        if (!dtStr) return '—';
        const d = new Date(dtStr);
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
             + ', ' + d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    }

    function durationBadge(mins) {
        mins = parseInt(mins) || 0;
        let cls = 'bg-danger';
        if (mins >= 120) cls = 'bg-success';
        else if (mins >= 30) cls = 'bg-primary';
        else if (mins >= 10) cls = 'bg-warning';
        return `<span class="badge ${cls} px-3 py-2"><i class="fa-solid fa-clock me-1"></i>${fmtMins(mins)}</span>`;
    }

    function showState(state) {
        // state: 'loading' | 'result' | 'nodata' | 'hidden'
        resultSec.classList.add('d-none');
        spinner.classList.add('d-none');
        noData.classList.add('d-none');
        if (state === 'loading')  spinner.classList.remove('d-none');
        if (state === 'result')   resultSec.classList.remove('d-none');
        if (state === 'nodata')   noData.classList.remove('d-none');
    }

    // ── Department change → load employees (jQuery Select2 event) ────────────
    function loadEmployees(deptId) {
        // Destroy existing select2 on empSel before modifying DOM
        if ($(empSel).data('select2')) {
            $(empSel).select2('destroy');
        }
        empSel.innerHTML = '<option value="">— Loading... —</option>';
        empSel.disabled  = true;
        searchBtn.disabled = true;
        showState('hidden');

        if (!deptId) {
            empSel.innerHTML = '<option value="">— Select Department First —</option>';
            // Re-init select2
            $(empSel).select2({ placeholder: '— Select Department First —', width: '100%' });
            return;
        }

        fetch(`${BASE_URL}/user-activity-log/employees-by-dept?dept_id=${deptId}`)
            .then(r => r.json())
            .then(data => {
                if (!data.length) {
                    empSel.innerHTML = '<option value="">No employees found</option>';
                    $(empSel).select2({ placeholder: 'No employees found', width: '100%' });
                    return;
                }
                empSel.innerHTML = '<option value="">— Select Employee —</option>'
                    + data.map(e => {
                        const empCode = e.employee_id ? ` [EMP-${e.employee_id}]` : '';
                        return `<option value="${e.id}">${e.full_name}${empCode}</option>`;
                    }).join('');
                empSel.disabled = false;

                // Re-initialize Select2 on the now-populated dropdown
                $(empSel).select2({
                    placeholder: '— Select Employee —',
                    allowClear: true,
                    width: '100%'
                });

                // Listen for Select2 change event
                $(empSel).off('select2:select select2:clear').on('select2:select select2:clear', function () {
                    searchBtn.disabled = !$(this).val();
                    showState('hidden');
                });
            })
            .catch(() => {
                empSel.innerHTML = '<option value="">Error loading employees</option>';
                $(empSel).select2({ placeholder: 'Error loading employees', width: '100%' });
            });
    }

    // Listen on Department select2 change
    $(deptSel).on('select2:select select2:clear', function () {
        loadEmployees($(this).val());
    });
    // Also plain change for fallback
    deptSel.addEventListener('change', function () {
        loadEmployees(this.value);
    });

    // ── Employee change → enable search button (fallback) ────────────────────
    empSel.addEventListener('change', function () {
        searchBtn.disabled = !this.value;
        showState('hidden');
    });

    // ── Search button click ───────────────────────────────────────────────────
    searchBtn.addEventListener('click', function () {
        const userId = $(empSel).val() || empSel.value;
        if (!userId) return;

        showState('loading');

        let url = `${BASE_URL}/user-activity-log/employee-summary?user_id=${userId}`;
        if (dateFrom.value) url += `&date_from=${dateFrom.value}`;
        if (dateTo.value)   url += `&date_to=${dateTo.value}`;

        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    showState('nodata');
                    return;
                }

                // ── Populate employee info ──────────────────────────────────
                document.getElementById('empName').textContent =
                    data.employee.full_name || '—';
                document.getElementById('empMeta').innerHTML =
                    `${data.employee.email || '—'} &nbsp;|&nbsp; ` +
                    `${data.employee.department_name} &nbsp;|&nbsp; ` +
                    `${data.employee.designation_name}`;

                // ── Summary cards ───────────────────────────────────────────
                document.getElementById('statSessions').textContent = data.total_sessions;
                document.getElementById('statTotal').textContent    = fmtMins(data.total_minutes);
                document.getElementById('statAvg').textContent      = fmtMins(data.avg_minutes);

                // ── Date-wise table ─────────────────────────────────────────
                const tbody = document.getElementById('empDailyBody');
                if (!data.daily || !data.daily.length) {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-3">No daily records found.</td></tr>`;
                } else {
                    tbody.innerHTML = data.daily.map((row, i) => `
                        <tr>
                            <td class="text-muted">${i + 1}</td>
                            <td><strong>${fmtDate(row.log_date)}</strong></td>
                            <td><span class="badge bg-secondary bg-opacity-10 border text-dark px-2">${row.sessions} session${row.sessions > 1 ? 's' : ''}</span></td>
                            <td><span class="text-nowrap">${fmtDateTime(row.first_login)}</span></td>
                            <td><span class="text-nowrap">${fmtDateTime(row.last_active)}</span></td>
                            <td>${durationBadge(row.active_minutes)}</td>
                        </tr>
                    `).join('');
                }

                showState('result');

                // Smooth scroll to result
                resultSec.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            })
            .catch((err) => {
                console.error('Employee summary fetch error:', err);
                showState('nodata');
            });
    });
})();
}); // DOMContentLoaded
</script>

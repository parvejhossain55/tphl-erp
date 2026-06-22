<?php
$base_url   = $GLOBALS['base_url'];
$filterDeptId   = $filterDeptId   ?? null;
$filterDateFrom = $filterDateFrom ?? null;
$filterDateTo   = $filterDateTo   ?? null;
$logs           = $logs           ?? [];
$departments    = $departments    ?? [];
$deptSummary    = $deptSummary    ?? [];

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

    <!-- ── Department Summary Cards ──────────────────────────────────────── -->
    <?php if (!empty($deptSummary)): ?>
    <div class="row  mb-4">
        <?php foreach ($deptSummary as $s):
            $totalHrs = intdiv((int)$s['total_minutes'], 60);
            $totalMins = (int)$s['total_minutes'] % 60;
        ?>
        <div class="col-xl-3 col-md-4 col-sm-6">
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
                <span class="badge bg-primary ms-1"><?= count($logs) ?> records</span>
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
                    $serial = 1;
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

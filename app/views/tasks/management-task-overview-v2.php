<?php
/**
 * Management Task Overview View
 * Executive dashboard for organizational task tracking, department health, and HOD performance.
 */
$page_title = "Management Overview";
$show_breadcrumb = false;

// Format numbers
$totalOrg = $kpi['total_tasks'] ?? 0;
$totalComp = $kpi['completed'] ?? 0;
$compRate = $kpi['completion_rate'] ?? 0;
$totalAct = $kpi['active'] ?? 0;
$totalOvd = $kpi['overdue'] ?? 0;
$ovdRate = $kpi['overdue_rate'] ?? 0;
$onTimeRate = $kpi['on_time_rate'] ?? 100;
$hodAssigned = $kpi['hod_assigned'] ?? 0;

$currMonth = date('Y-m');
$monthNames = [
    '2026-01' => 'January 2026', '2026-02' => 'February 2026', '2026-03' => 'March 2026',
    '2026-04' => 'April 2026', '2026-05' => 'May 2026', '2026-06' => 'June 2026',
    '2026-07' => 'July 2026', '2026-08' => 'August 2026', '2026-09' => 'September 2026',
    '2026-10' => 'October 2026', '2026-11' => 'November 2026', '2026-12' => 'December 2026'
];
?>

<div class="container-fluid px-3 py-3">
    <!-- Header with Breadcrumb & Quick Actions -->
    <div class="row align-items-center mb-3">
        <div class="col-md-7">
            <h4 class="fw-bold mb-1 text-dark" style="letter-spacing: -0.4px;">
                <i class="fa-sharp fa-solid fa-chart-pie me-2 text-primary"></i>Management Overview
            </h4>
            <p class="text-muted small mb-0">Executive monitoring of organization-wide task delivery, department health, and HOD performance.</p>
        </div>
        <div class="col-md-5 text-md-end mt-2 mt-md-0 d-flex justify-content-md-end gap-2 align-items-center">
            <button type="button" onclick="exportOverviewCSV()" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 shadow-sm">
                <i class="fa-solid fa-file-csv text-success"></i> Export CSV
            </button>
            <a href="<?= $GLOBALS['base_url'] ?>/team-performance-v2" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 shadow-sm">
                <i class="fa-solid fa-users-viewfinder"></i> Team Performance
            </a>
            <a href="<?= $GLOBALS['base_url'] ?>/create-task" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-sm">
                <i class="fa-solid fa-plus"></i> Add Task
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px; background: #ffffff;">
        <div class="card-body p-3">
            <form method="GET" action="<?= $GLOBALS['base_url'] ?>/management-task-overview-v2" id="mgmtFilterForm">
                <div class="row g-2 align-items-end">
                    <!-- Period Filter -->
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <label class="form-label small fw-semibold text-secondary mb-1">Period</label>
                        <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="all" <?= ($period === 'all') ? 'selected' : '' ?>>All Time</option>
                            <?php foreach ($monthNames as $mKey => $mLabel): ?>
                                <option value="<?= $mKey ?>" <?= ($period === $mKey) ? 'selected' : '' ?>><?= $mLabel ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Department Filter -->
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <label class="form-label small fw-semibold text-secondary mb-1">Department</label>
                        <select name="department_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="0">All Departments</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= ($deptFilter == $d['id']) ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- HOD Filter -->
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <label class="form-label small fw-semibold text-secondary mb-1">Department Head (HOD)</label>
                        <select name="hod_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="0">All HODs</option>
                            <?php foreach ($hods as $h): ?>
                                <option value="<?= $h['employee_id'] ?>" <?= ($hodFilter == $h['employee_id']) ? 'selected' : '' ?>><?= htmlspecialchars($h['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Employee Filter -->
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <label class="form-label small fw-semibold text-secondary mb-1">Employee</label>
                        <select name="employee_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="0">All Employees</option>
                            <?php foreach ($employees as $e): ?>
                                <option value="<?= $e['id'] ?>" <?= ($empFilter == $e['id']) ? 'selected' : '' ?>><?= htmlspecialchars($e['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Task Type Filter -->
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <label class="form-label small fw-semibold text-secondary mb-1">Task Type</label>
                        <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="all" <?= ($typeFilter === 'all') ? 'selected' : '' ?>>Assigned + Self</option>
                            <option value="assigned" <?= ($typeFilter === 'assigned') ? 'selected' : '' ?>>Team Projects Only</option>
                            <option value="self" <?= ($typeFilter === 'self') ? 'selected' : '' ?>>Self Tasks Only</option>
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-lg-2 col-md-4 col-sm-6 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100 d-inline-flex align-items-center justify-content-center gap-1">
                            <i class="fa-solid fa-filter"></i> Apply
                        </button>
                        <a href="<?= $GLOBALS['base_url'] ?>/management-task-overview-v2" class="btn btn-sm btn-outline-secondary w-50 d-inline-flex align-items-center justify-content-center">
                            Reset
                        </a>
                    </div>
                </div>
                <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>" id="mgmtTabInput">
            </form>
        </div>
    </div>

    <!-- 6 KPI Metric Cards Row -->
    <div class="row  mb-4">
        <!-- 1. Organization Tasks -->
        <div class="col-xxl-2 col-lg-4 col-sm-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; background: #ffffff; border-left: 4px solid #6366f1 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="small fw-semibold text-muted text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Organization Tasks</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: rgba(99, 102, 241, 0.1); color: #6366f1;">
                            <i class="fa-solid fa-list-check" style="font-size: 14px;"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <h3 class="fw-bold mb-0 text-dark"><?= number_format($totalOrg) ?></h3>
                    </div>
                    <div class="mt-2 text-muted small" style="font-size: 11.5px;">
                        <span class="text-secondary fw-semibold">100%</span> total tracked pipeline
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Completed -->
        <div class="col-xxl-2 col-lg-4 col-sm-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; background: #ffffff; border-left: 4px solid #10b981 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="small fw-semibold text-muted text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Completed</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: rgba(16, 185, 129, 0.1); color: #10b981;">
                            <i class="fa-solid fa-circle-check" style="font-size: 14px;"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <h3 class="fw-bold mb-0 text-success"><?= number_format($totalComp) ?></h3>
                        <span class="badge bg-success-subtle text-success rounded-pill fw-semibold" style="font-size: 10px;"><?= $compRate ?>%</span>
                    </div>
                    <div class="mt-2 text-muted small" style="font-size: 11.5px;">
                        Delivered successfully
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Active Tasks -->
        <div class="col-xxl-2 col-lg-4 col-sm-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; background: #ffffff; border-left: 4px solid #3b82f6 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="small fw-semibold text-muted text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Active Tasks</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                            <i class="fa-solid fa-spinner" style="font-size: 14px;"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <h3 class="fw-bold mb-0 text-primary"><?= number_format($totalAct) ?></h3>
                    </div>
                    <div class="mt-2 text-muted small" style="font-size: 11.5px;">
                        In progress & pending
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Overdue -->
        <div class="col-xxl-2 col-lg-4 col-sm-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; background: #ffffff; border-left: 4px solid #ef4444 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="small fw-semibold text-muted text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Overdue</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                            <i class="fa-solid fa-triangle-exclamation" style="font-size: 14px;"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <h3 class="fw-bold mb-0 text-danger"><?= number_format($totalOvd) ?></h3>
                        <?php if ($totalOvd > 0): ?>
                            <span class="badge bg-danger-subtle text-danger rounded-pill fw-semibold" style="font-size: 10px;"><?= $ovdRate ?>%</span>
                        <?php endif; ?>
                    </div>
                    <div class="mt-2 text-muted small" style="font-size: 11.5px;">
                        Past target deadline
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. On-Time Rate -->
        <div class="col-xxl-2 col-lg-4 col-sm-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; background: #ffffff; border-left: 4px solid #06b6d4 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="small fw-semibold text-muted text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">On-Time Rate</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: rgba(6, 182, 212, 0.1); color: #06b6d4;">
                            <i class="fa-solid fa-clock-rotate-left" style="font-size: 14px;"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <h3 class="fw-bold mb-0" style="color: #06b6d4;"><?= $onTimeRate ?>%</h3>
                    </div>
                    <div class="mt-2 text-muted small" style="font-size: 11.5px;">
                        Punctuality index
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. HOD Assignments -->
        <div class="col-xxl-2 col-lg-4 col-sm-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; background: #ffffff; border-left: 4px solid #8b5cf6 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="small fw-semibold text-muted text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">HOD Assignments</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                            <i class="fa-solid fa-diagram-project" style="font-size: 14px;"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <h3 class="fw-bold mb-0" style="color: #8b5cf6;"><?= number_format($hodAssigned) ?></h3>
                    </div>
                    <div class="mt-2 text-muted small" style="font-size: 11.5px;">
                        Shared team tasks
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts & Analytics Row -->
    <div class="row  mb-4">
        <!-- Left: Department Performance (Horizontal Stacked Bars) -->
        <div class="col-xxl-7 col-xl-7 col-lg-12">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 14px; background: #ffffff;">
                <div class="card-header bg-transparent border-0 pt-3 pb-2 px-3 d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-bars-progress me-2 text-primary"></i>Department Performance</h6>
                        <span class="text-muted small" style="font-size: 11.5px;">Completed vs In-Progress vs Overdue tasks across operational units</span>
                    </div>
                    <span class="badge bg-light text-secondary border px-2 py-1"><?= count($deptList) ?> Units</span>
                </div>
                <div class="card-body p-3">
                    <?php if (empty($deptList)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fa-regular fa-folder-open fa-2x mb-2 d-block opacity-50"></i>
                            <p class="mb-0">No department task data found for the selected period.</p>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach (array_slice($deptList, 0, 7) as $dept): 
                                $dTotal = max(1, $dept['total']);
                                $compPct = round(($dept['completed'] / $dTotal) * 100);
                                $ovdPct = round(($dept['overdue'] / $dTotal) * 100);
                                $actPct = max(0, 100 - $compPct - $ovdPct);
                            ?>
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-semibold text-dark small" style="font-size: 13px;"><?= htmlspecialchars($dept['name']) ?></span>
                                            <span class="badge bg-light text-muted border rounded-pill" style="font-size: 10px;"><?= $dept['employees_count'] ?> Members</span>
                                        </div>
                                        <div class="small text-muted" style="font-size: 12px;">
                                            <strong class="text-success"><?= $dept['completed'] ?></strong> / <?= $dept['total'] ?> completed
                                            <span class="ms-1 fw-bold text-dark">(<?= $dept['completion_pct'] ?>%)</span>
                                        </div>
                                    </div>
                                    <div class="progress" style="height: 10px; border-radius: 6px; background-color: #f1f5f9;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $compPct ?>%;" title="Completed: <?= $dept['completed'] ?>"></div>
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $actPct ?>%;" title="Active: <?= $dept['active'] ?>"></div>
                                        <div class="progress-bar bg-danger" role="progressbar" style="width: <?= $ovdPct ?>%;" title="Overdue: <?= $dept['overdue'] ?>"></div>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted mt-1" style="font-size: 11px;">
                                        <span><i class="fa-solid fa-circle text-success me-1" style="font-size: 7px;"></i>Completed: <?= $dept['completed'] ?></span>
                                        <span><i class="fa-solid fa-circle text-primary me-1" style="font-size: 7px;"></i>Active: <?= $dept['active'] ?></span>
                                        <span><i class="fa-solid fa-circle text-danger me-1" style="font-size: 7px;"></i>Overdue: <?= $dept['overdue'] ?></span>
                                        <span>Score: <strong class="text-dark"><?= $dept['score'] ?>/100</strong></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right: Organization Task Health (Donut + Management Insights) -->
        <div class="col-xxl-5 col-xl-5 col-lg-12">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 14px; background: #ffffff;">
                <div class="card-header bg-transparent border-0 pt-3 pb-2 px-3 d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-heart-pulse me-2 text-primary"></i>Organization Task Health</h6>
                        <span class="text-muted small" style="font-size: 11.5px;">Overall breakdown & executive leadership observations</span>
                    </div>
                </div>
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <!-- Donut Chart Canvas Container -->
                    <div style="position: relative; height: 180px;" class="mb-3">
                        <canvas id="mgmtHealthDonutChart"></canvas>
                        <!-- Center Label Overlay -->
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; pointer-events: none;">
                            <div class="fw-bold text-dark" style="font-size: 22px; line-height: 1;"><?= number_format($totalOrg) ?></div>
                            <span class="text-muted" style="font-size: 11px; text-transform: uppercase;">Tasks</span>
                        </div>
                    </div>

                    <!-- Management Insights Box -->
                    <div class="card border-0 p-3" style="border-radius: 12px; background: linear-gradient(135deg, #f0fdf4 0%, #e0f2fe 100%); border-left: 4px solid var(--clr-theme-primary, #059669) !important;">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="rounded-circle d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; background: #059669; color: #ffffff; font-size: 11px;">
                                <i class="fa-solid fa-sparkles"></i>
                            </span>
                            <h6 class="fw-bold mb-0 text-dark" style="font-size: 13.5px;">Management Insights</h6>
                        </div>
                        <ul class="mb-0 ps-3 small text-dark" style="font-size: 12px; line-height: 1.55;">
                            <?php foreach ($insights as $ins): ?>
                                <li class="mb-1"><?= $ins ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaderboard & Performance Tabs Section -->
    <div class="card border-0 shadow-sm" style="border-radius: 14px; background: #ffffff;">
        <div class="card-header bg-transparent border-0 pt-3 pb-2 px-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <!-- Nav Tabs -->
                <ul class="nav nav-pills gap-2" id="mgmtTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= ($tab === 'hods' || empty($tab)) ? 'active' : '' ?> px-3 py-2 fw-semibold small" 
                                onclick="switchMgmtTab('hods')" type="button">
                            <i class="fa-solid fa-user-tie me-1"></i> HOD Performance
                            <span class="badge <?= ($tab === 'hods' || empty($tab)) ? 'bg-white text-primary' : 'bg-light text-secondary' ?> ms-1 rounded-pill"><?= count($hodList) ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= ($tab === 'departments') ? 'active' : '' ?> px-3 py-2 fw-semibold small" 
                                onclick="switchMgmtTab('departments')" type="button">
                            <i class="fa-solid fa-building me-1"></i> Departments
                            <span class="badge <?= ($tab === 'departments') ? 'bg-white text-primary' : 'bg-light text-secondary' ?> ms-1 rounded-pill"><?= count($deptList) ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= ($tab === 'individuals') ? 'active' : '' ?> px-3 py-2 fw-semibold small" 
                                onclick="switchMgmtTab('individuals')" type="button">
                            <i class="fa-solid fa-users me-1"></i> Top Individuals
                            <span class="badge <?= ($tab === 'individuals') ? 'bg-white text-primary' : 'bg-light text-secondary' ?> ms-1 rounded-pill"><?= count($topIndividuals) ?></span>
                        </button>
                    </li>
                </ul>

                <!-- Instant Table Search -->
                <div class="position-relative" style="min-width: 240px;">
                    <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="left: 12px; top: 50%; transform: translateY(-50%); font-size: 12px;"></i>
                    <input type="text" id="mgmtTableSearch" class="form-control form-control-sm ps-4" placeholder="Filter rows in view..." onkeyup="filterMgmtTable()">
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <!-- TAB 1: HOD Performance Table -->
            <div id="tab-hods" class="tab-pane-content <?= ($tab === 'hods' || empty($tab)) ? '' : 'd-none' ?>">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="hodTable">
                        <thead class="table-light text-uppercase small text-secondary" style="font-size: 11px; letter-spacing: 0.5px;">
                            <tr>
                                <th class="ps-3 py-3">HOD / Department</th>
                                <th class="text-center py-3">Total Tasks</th>
                                <th class="text-center py-3">Assigned by HOD</th>
                                <th class="text-center py-3">HOD Self</th>
                                <th class="text-center py-3">HOD Comp</th>
                                <th class="text-center py-3">Team Comp</th>
                                <th class="text-center py-3">Overdue</th>
                                <th class="text-center py-3">On-Time %</th>
                                <th class="text-center py-3">Leadership Score</th>
                                <th class="pe-3 text-center py-3">Trend</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php if (empty($hodList)): ?>
                                <tr>
                                    <td colspan="10" class="text-center py-5 text-muted">No HOD performance records found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($hodList as $h): ?>
                                    <tr class="searchable-row">
                                        <td class="ps-3 py-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 13px;">
                                                    <?= strtoupper(substr($h['full_name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <a href="<?= $GLOBALS['base_url'] ?>/team-performance-v2" class="fw-bold text-dark text-decoration-none d-block small mb-0">
                                                        <?= htmlspecialchars($h['full_name']) ?>
                                                    </a>
                                                    <span class="text-muted" style="font-size: 11px;">
                                                        <?= htmlspecialchars($h['designation_name']) ?> &bull; 
                                                        <span class="badge bg-light text-secondary border"><?= htmlspecialchars($h['department_name']) ?></span>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center fw-bold small text-dark"><?= $h['total_tasks'] ?></td>
                                        <td class="text-center small text-secondary"><?= $h['assigned_by_hod'] ?></td>
                                        <td class="text-center small text-secondary"><?= $h['self_tasks'] ?></td>
                                        <td class="text-center fw-semibold small text-success"><?= $h['hod_completed'] ?></td>
                                        <td class="text-center fw-semibold small text-primary"><?= $h['team_completed'] ?></td>
                                        <td class="text-center small">
                                            <?php if ($h['overdue'] > 0): ?>
                                                <span class="badge bg-danger-subtle text-danger rounded-pill"><?= $h['overdue'] ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center small">
                                            <span class="fw-semibold <?= ($h['on_time_rate'] >= 80) ? 'text-success' : (($h['on_time_rate'] >= 60) ? 'text-primary' : 'text-danger') ?>">
                                                <?= $h['on_time_rate'] ?>%
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-inline-flex align-items-center gap-1">
                                                <span class="fw-bold text-dark small"><?= $h['leadership_score'] ?></span>
                                                <div class="progress" style="width: 45px; height: 6px; border-radius: 4px; background: #e2e8f0;">
                                                    <div class="progress-bar <?= ($h['leadership_score'] >= 75) ? 'bg-success' : (($h['leadership_score'] >= 50) ? 'bg-primary' : 'bg-warning') ?>" 
                                                         style="width: <?= min(100, $h['leadership_score']) ?>%;"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="pe-3 text-center">
                                            <span class="badge <?= $h['trend_class'] ?> rounded-pill px-2 py-1" style="font-size: 11px;">
                                                <?= $h['trend'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: Departments Table -->
            <div id="tab-departments" class="tab-pane-content <?= ($tab === 'departments') ? '' : 'd-none' ?>">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="deptTable">
                        <thead class="table-light text-uppercase small text-secondary" style="font-size: 11px; letter-spacing: 0.5px;">
                            <tr>
                                <th class="ps-3 py-3">Department Name</th>
                                <th class="text-center py-3">Team Size</th>
                                <th class="text-center py-3">Total Tasks</th>
                                <th class="text-center py-3">Completed</th>
                                <th class="text-center py-3">Active</th>
                                <th class="text-center py-3">Overdue</th>
                                <th class="text-center py-3">Completion Rate</th>
                                <th class="text-center py-3">On-Time %</th>
                                <th class="pe-3 text-center py-3">Overall Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($deptList)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">No department records available.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($deptList as $d): ?>
                                    <tr class="searchable-row">
                                        <td class="ps-3 py-3 fw-bold small text-dark">
                                            <i class="fa-solid fa-folder me-2 text-primary"></i><?= htmlspecialchars($d['name']) ?>
                                        </td>
                                        <td class="text-center small text-secondary"><?= $d['employees_count'] ?> Employees</td>
                                        <td class="text-center fw-bold small text-dark"><?= $d['total'] ?></td>
                                        <td class="text-center fw-semibold small text-success"><?= $d['completed'] ?></td>
                                        <td class="text-center small text-primary"><?= $d['active'] ?></td>
                                        <td class="text-center small">
                                            <?php if ($d['overdue'] > 0): ?>
                                                <span class="badge bg-danger-subtle text-danger rounded-pill"><?= $d['overdue'] ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center small fw-semibold text-dark"><?= $d['completion_pct'] ?>%</td>
                                        <td class="text-center small fw-semibold text-info"><?= $d['on_time_pct'] ?>%</td>
                                        <td class="pe-3 text-center">
                                            <span class="badge bg-success-subtle text-success fw-bold px-2 py-1" style="font-size: 11px;">
                                                <?= $d['score'] ?> / 100
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 3: Top Individuals Table -->
            <div id="tab-individuals" class="tab-pane-content <?= ($tab === 'individuals') ? '' : 'd-none' ?>">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="indivTable">
                        <thead class="table-light text-uppercase small text-secondary" style="font-size: 11px; letter-spacing: 0.5px;">
                            <tr>
                                <th class="ps-3 py-3">Rank & Employee</th>
                                <th class="py-3">Department</th>
                                <th class="py-3">Designation</th>
                                <th class="text-center py-3">Total Tasks</th>
                                <th class="text-center py-3">Completed</th>
                                <th class="text-center py-3">Active</th>
                                <th class="text-center py-3">Overdue</th>
                                <th class="text-center py-3">Completion %</th>
                                <th class="pe-3 text-center py-3">Performance Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topIndividuals)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">No individual employee performance records found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($topIndividuals as $idx => $ind): 
                                    $rank = $idx + 1;
                                    $badgeColor = ($rank === 1) ? 'bg-warning text-dark' : (($rank === 2) ? 'bg-secondary text-white' : (($rank === 3) ? 'bg-dark text-white' : 'bg-light text-secondary border'));
                                ?>
                                    <tr class="searchable-row">
                                        <td class="ps-3 py-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge <?= $badgeColor ?> rounded-circle d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 11px;">
                                                    <?= $rank ?>
                                                </span>
                                                <div class="fw-bold small text-dark"><?= htmlspecialchars($ind['full_name']) ?></div>
                                            </div>
                                        </td>
                                        <td class="small text-muted"><?= htmlspecialchars($ind['department_name']) ?></td>
                                        <td class="small text-muted"><?= htmlspecialchars($ind['designation_name']) ?></td>
                                        <td class="text-center fw-bold small text-dark"><?= $ind['total'] ?></td>
                                        <td class="text-center fw-semibold small text-success"><?= $ind['completed'] ?></td>
                                        <td class="text-center small text-primary"><?= $ind['active'] ?></td>
                                        <td class="text-center small">
                                            <?php if ($ind['overdue'] > 0): ?>
                                                <span class="badge bg-danger-subtle text-danger rounded-pill"><?= $ind['overdue'] ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center small fw-semibold text-dark"><?= $ind['completion_pct'] ?>%</td>
                                        <td class="pe-3 text-center">
                                            <span class="badge bg-primary-subtle text-primary fw-bold px-2 py-1" style="font-size: 11px;">
                                                <?= $ind['score'] ?> / 100
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js CDN safeguard in case not already loaded -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
    // Tab switching handler
    function switchMgmtTab(tabKey) {
        document.getElementById('mgmtTabInput').value = tabKey;
        document.querySelectorAll('.tab-pane-content').forEach(el => el.classList.add('d-none'));
        const target = document.getElementById('tab-' + tabKey);
        if (target) target.classList.remove('d-none');

        document.querySelectorAll('#mgmtTabs .nav-link').forEach(btn => {
            btn.classList.remove('active');
            const badge = btn.querySelector('.badge');
            if (badge) {
                badge.className = 'badge bg-light text-secondary ms-1 rounded-pill';
            }
        });
        event.currentTarget.classList.add('active');
        const activeBadge = event.currentTarget.querySelector('.badge');
        if (activeBadge) {
            activeBadge.className = 'badge bg-white text-primary ms-1 rounded-pill';
        }
    }

    // Instant table search
    function filterMgmtTable() {
        const query = (document.getElementById('mgmtTableSearch').value || '').toLowerCase().trim();
        document.querySelectorAll('.searchable-row').forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    }

    // Export Table Data to CSV
    function exportOverviewCSV() {
        let activeTable = document.querySelector('.tab-pane-content:not(.d-none) table');
        if (!activeTable) activeTable = document.getElementById('hodTable');
        if (!activeTable) return;

        let csv = [];
        const rows = activeTable.querySelectorAll('tr');
        for (let i = 0; i < rows.length; i++) {
            if (rows[i].offsetParent === null) continue; // skip hidden rows
            let row = [];
            const cols = rows[i].querySelectorAll('th, td');
            for (let j = 0; j < cols.length; j++) {
                let cellText = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, ' ').replace(/"/g, '""').trim();
                row.push('"' + cellText + '"');
            }
            csv.push(row.join(','));
        }

        const csvContent = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv.join('\n'));
        const link = document.createElement('a');
        link.setAttribute('href', csvContent);
        link.setAttribute('download', 'Management_Task_Overview_' + new Date().toISOString().slice(0, 10) + '.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Initialize Donut Chart
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('mgmtHealthDonutChart');
        if (ctx && typeof Chart !== 'undefined') {
            const completed = <?= (int)$totalComp ?>;
            const active = <?= (int)$totalAct ?>;
            const overdue = <?= (int)$totalOvd ?>;

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'Active', 'Overdue'],
                    datasets: [{
                        data: [completed, active, overdue],
                        backgroundColor: ['#10b981', '#3b82f6', '#ef4444'],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 10,
                                padding: 12,
                                font: { size: 11, weight: '500' },
                                color: '#64748b'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const val = context.raw || 0;
                                    const total = <?= max(1, $totalOrg) ?>;
                                    const pct = Math.round((val / total) * 100);
                                    return ' ' + context.label + ': ' + val + ' (' + pct + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>

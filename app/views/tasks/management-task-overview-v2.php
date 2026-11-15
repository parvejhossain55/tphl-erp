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
$onTimeRate = $kpi['on_time_rate'] ?? 0;
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
            <button type="button" onclick="openAssignTaskDrawer()" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-sm">
                <i class="fa-solid fa-plus"></i> Add Task
            </button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 14px; background: #ffffff;">
        <div class="card-body p-3">
            <form method="GET" action="<?= $GLOBALS['base_url'] ?>/management-task-overview-v2" id="mgmtFilterForm">
                <div class="row g-2 align-items-end">
                    <!-- Period Filter -->
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <label class="form-label small fw-semibold text-secondary mb-1">Period</label>
                        <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="all" <?= ($period === 'all') ? 'selected' : '' ?>>All Time</option>
                            <?php foreach ($monthNames as $mKey => $mLabel): ?>
                                <option value="<?= $mKey ?>" <?= ($period === $mKey) ? 'selected' : '' ?>><?= $mLabel ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Department Filter -->
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <label class="form-label small fw-semibold text-secondary mb-1">Department</label>
                        <select name="department_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="0">All Departments</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= ($deptFilter == $d['id']) ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
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
                                $dTotal = (int)$dept['total'];
                                if ($dTotal > 0) {
                                    $compPct = round(($dept['completed'] / $dTotal) * 100);
                                    $ovdPct = round(($dept['overdue'] / $dTotal) * 100);
                                    $actPct = max(0, 100 - $compPct - $ovdPct);
                                } else {
                                    $compPct = 0;
                                    $ovdPct = 0;
                                    $actPct = 0;
                                }
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
                                        <div class="progress-bar" role="progressbar" style="width: <?= $compPct ?>%; background-color: #10b981;" title="Completed: <?= $dept['completed'] ?>"></div>
                                        <div class="progress-bar" role="progressbar" style="width: <?= $actPct ?>%; background-color: #3b82f6;" title="Active: <?= $dept['active'] ?>"></div>
                                        <div class="progress-bar" role="progressbar" style="width: <?= $ovdPct ?>%; background-color: #ef4444;" title="Overdue: <?= $dept['overdue'] ?>"></div>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted mt-1" style="font-size: 11px;">
                                        <span><i class="fa-solid fa-circle me-1" style="font-size: 7px; color: #10b981;"></i>Completed: <?= $dept['completed'] ?></span>
                                        <span><i class="fa-solid fa-circle me-1" style="font-size: 7px; color: #3b82f6;"></i>Active: <?= $dept['active'] ?></span>
                                        <span><i class="fa-solid fa-circle me-1" style="font-size: 7px; color: #ef4444;"></i>Overdue: <?= $dept['overdue'] ?></span>
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
                    <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="left: 12px; top: 50%; transform: translateY(-50%); font-size: 12px; pointer-events: none;"></i>
                    <input type="text" id="mgmtTableSearch" class="form-control form-control-sm" style="padding-left: 32px;" placeholder="Filter rows in view..." onkeyup="filterMgmtTable()">
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

<!-- ========================================================================
     Right Slide Drawer: Create & Assign Task (My Work Full Task Creator)
     ======================================================================== -->
<style>
.mw-btn-create {
    background-color: #087F5B;
    color: #FFFFFF !important;
    font-weight: 700;
    font-size: 12.5px;
    line-height: 15px;
    padding: 8px 14px;
    border-radius: 6px;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: background-color 0.15s ease;
}
.mw-btn-create:hover {
    background-color: #066649;
    color: #FFFFFF !important;
}
.mw-toast-wrap { position: fixed; top: 20px; right: 24px; z-index: 10050; display: flex; flex-direction: column; gap: 10px; pointer-events: none; }
.mw-toast { pointer-events: auto; min-width: 280px; max-width: 420px; padding: 12px 16px; border-radius: 8px; background: #fff; border: 1px solid #E2EAE5; border-left: 4px solid #087F5B; box-shadow: 0 10px 28px rgba(20,45,38,.14); display: flex; align-items: center; gap: 10px; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 500; color: #142D26; animation: mwToastIn .22s ease; }
.mw-toast.error { border-left-color: #C92A2A; }
.mw-toast .mw-toast-icon { font-size: 16px; color: #087F5B; flex-shrink: 0; }
.mw-toast.error .mw-toast-icon { color: #C92A2A; }
.mw-toast .mw-toast-close { margin-left: auto; background: none; border: 0; color: #65776F; cursor: pointer; padding: 2px 4px; font-size: 13px; }
@keyframes mwToastIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
.mw-multi { position: relative; }
.mw-multi-btn { text-align: left; width: 100%; cursor: pointer; }
.mw-multi-panel { display: none; position: absolute; z-index: 50; left: 0; right: 0; top: 100%; margin-top: 4px; background: #fff; border: 1px solid #E2EAE5; border-radius: 8px; box-shadow: 0 6px 16px rgba(0,0,0,.08); max-height: 240px; overflow-y: auto; padding: 8px; }
.mw-multi.open .mw-multi-panel { display: block; }
.mw-multi-search { margin-bottom: 6px; position: sticky; top: 0; z-index: 1; background: #fff; }
.mw-multi-item { display: flex; align-items: center; gap: 8px; padding: 5px 6px; border-radius: 6px; cursor: pointer; font-size: 13px; margin: 0; }
.mw-multi-item:hover { background: #F4FAF7; }
.mw-drawer-backdrop {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(20, 45, 38, 0.45);
    backdrop-filter: blur(2px);
    z-index: 9998;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.28s ease, visibility 0.28s ease;
}
.mw-drawer-backdrop.show { opacity: 1; visibility: visible; }
.mw-drawer {
    position: fixed;
    top: 0; right: 0; bottom: 0;
    left: 54.38%;
    background: #FFFFFF;
    z-index: 9999;
    box-shadow: -12px 0 40px rgba(0, 0, 0, 0.16);
    display: flex;
    flex-direction: column;
    transform: translateX(100%);
    transition: transform 0.32s cubic-bezier(0.16, 1, 0.3, 1);
    border-left: 1px solid #E2EAE5;
}
.mw-drawer.show { transform: translateX(0); }
@media (max-width: 1100px) { .mw-drawer { left: 35%; } }
@media (max-width: 768px) { .mw-drawer { left: 0; width: 100%; } }
.mw-drawer-header { padding: 22px 28px 16px; border-bottom: 1px solid #E2EAE5; position: relative; flex-shrink: 0; }
.mw-drawer-title-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; }
.mw-drawer-title { font-weight: 700; font-size: 20px; line-height: 24px; color: #142D26; margin: 0; }
.mw-drawer-close { background: transparent; border: none; font-size: 18px; color: #65776F; cursor: pointer; padding: 4px 8px; border-radius: 4px; line-height: 1; transition: all 0.15s ease; }
.mw-drawer-close:hover { color: #142D26; background: #F5F7F6; }
.mw-drawer-subtitle { font-weight: 400; font-size: 12px; line-height: 15px; color: #65776F; margin: 0; }
.mw-drawer-meta-bar { padding: 10px 28px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #F0F4F2; background: #FAFCFA; flex-shrink: 0; }
.mw-status-badge-todo { background: #F5F7F6; border: 1px solid #E2EAE5; border-radius: 4px; font-weight: 600; font-size: 11px; line-height: 13px; color: #142D26; padding: 4px 10px; display: inline-block; }
.mw-required-note { font-weight: 400; font-size: 11px; line-height: 13px; color: #65776F; }
.mw-drawer-body { padding: 24px 28px; overflow-y: auto; flex-grow: 1; }
.mw-form-group { margin-bottom: 18px; }
.mw-form-label { display: block; font-weight: 600; font-size: 12px; line-height: 15px; color: #142D26; margin-bottom: 6px; }
.mw-form-label span.req { color: #DC2626; }
.mw-form-input, .mw-form-select, .mw-form-textarea {
    width: 100%; background: #FFFFFF; border: 1px solid #D1DDD6; border-radius: 6px;
    padding: 9px 12px; font-size: 13px; line-height: 18px; color: #142D26; outline: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease; box-sizing: border-box;
}
.mw-form-input:focus, .mw-form-select:focus, .mw-form-textarea:focus {
    border-color: #087F5B; box-shadow: 0 0 0 3px rgba(8, 127, 91, 0.12);
}
.mw-form-row { display: flex; gap: 16px; }
.mw-form-col { flex: 1; min-width: 0; }
.mw-checkbox-wrap { display: flex; align-items: center; gap: 8px; margin: 14px 0 16px; cursor: pointer; user-select: none; }
.mw-checkbox-wrap input[type="checkbox"] { width: 16px; height: 16px; accent-color: #087F5B; cursor: pointer; }
.mw-checkbox-wrap span { font-weight: 500; font-size: 12.5px; color: #142D26; }
.mw-callout-box { background: #EBF7EE; border: 1px solid #C3E6CD; border-radius: 8px; padding: 12px 16px; display: flex; gap: 12px; align-items: flex-start; margin-top: 18px; margin-bottom: 12px; }
.mw-callout-icon { color: #087F5B; font-size: 16px; margin-top: 1px; }
.mw-callout-title { font-weight: 600; font-size: 12.5px; line-height: 16px; color: #087F5B; margin-bottom: 2px; }
.mw-callout-sub { font-weight: 400; font-size: 11.5px; line-height: 15px; color: #4A6B5D; }
.mw-drawer-footer { padding: 16px 28px 18px; border-top: 1px solid #E2EAE5; background: #FFFFFF; flex-shrink: 0; }
.mw-drawer-footer-actions { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
.mw-drawer-btn { padding: 9px 18px; border-radius: 6px; font-weight: 600; font-size: 13px; line-height: 16px; cursor: pointer; transition: all 0.15s ease; display: inline-flex; align-items: center; justify-content: center; }
.mw-drawer-btn.secondary { background: #FFFFFF; border: 1px solid #D1DDD6; color: #142D26; }
.mw-drawer-btn.secondary:hover { background: #F5F7F6; border-color: #B5C6BC; }
.mw-drawer-btn.primary { background: #087F5B; border: 1px solid #087F5B; color: #FFFFFF; }
.mw-drawer-btn.primary:hover { background: #066649; border-color: #066649; }
</style>

<div class="mw-drawer-backdrop" id="mwDrawerBackdrop" onclick="closeAssignTaskDrawer()"></div>
<div class="mw-drawer" id="mwCreateTaskDrawer">
    <!-- Header -->
    <div class="mw-drawer-header">
        <div class="mw-drawer-title-row">
            <h3 class="mw-drawer-title" id="mwDrawerTitle">Create & Assign Task</h3>
            <button type="button" class="mw-drawer-close" id="btnCloseCreateTask" onclick="closeAssignTaskDrawer()" title="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <p class="mw-drawer-subtitle" id="mwDrawerSubtitle">Define the output, owner and deadline.</p>
    </div>

    <!-- Status & Required Meta Bar -->
    <div class="mw-drawer-meta-bar">
        <span class="mw-status-badge-todo" id="mwDrawerStatusBadge">Status: To do</span>
        <span class="mw-required-note">Required fields marked <span class="text-danger">*</span></span>
    </div>

    <!-- Form & Body -->
    <form id="mwCreateTaskForm" enctype="multipart/form-data" class="d-flex flex-column flex-grow-1 overflow-hidden" style="margin: 0;">
        <input type="hidden" name="edit_task_id" id="drawerEditTaskId" value="">
        <div class="mw-drawer-body">
            <!-- Task Title -->
            <div class="mw-form-group">
                <label class="mw-form-label">Task title <span class="req">*</span></label>
                <input type="text" name="title" id="drawerTaskTitle" class="mw-form-input" 
                       placeholder="Client ledger: verify opening balances" required autocomplete="off">
            </div>

            <?php
            $currentUserId = (int)($userId ?? ($_SESSION['user']['id'] ?? 0));
            $mwMulti = function (string $name, string $placeholder, bool $single = false, ?int $selectedId = null, string $id = '') use ($usersList) { ?>
                <div class="mw-multi" <?= $id !== '' ? 'id="' . htmlspecialchars($id) . '"' : '' ?> data-single="<?= $single ? '1' : '0' ?>" data-clearable="<?= ($single && $selectedId === null) ? '1' : '0' ?>" data-placeholder="<?= htmlspecialchars($placeholder) ?>">
                    <button type="button" class="mw-form-select mw-multi-btn"><span class="mw-multi-text"><?= htmlspecialchars($placeholder) ?></span></button>
                    <div class="mw-multi-panel">
                        <input type="text" class="mw-form-input mw-multi-search" placeholder="Search...">
                        <?php foreach (($usersList ?? []) as $u): if (trim((string)$u['full_name']) === '') continue; 
                            $rawCode = !empty($u['employee_code']) ? trim((string)$u['employee_code']) : '';
                            $empCode = preg_replace('/^EMP-?/i', '', $rawCode);
                            $displayName = trim((string)$u['full_name']) . ($empCode !== '' ? " ({$empCode})" : '');
                        ?>
                            <label class="mw-multi-item">
                                <input type="<?= $single ? 'radio' : 'checkbox' ?>" name="<?= htmlspecialchars($name) ?>" value="<?= (int)$u['id'] ?>" data-name="<?= htmlspecialchars($displayName) ?>" <?= ($selectedId !== null && (int)$u['id'] === $selectedId) ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars($displayName) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php }; ?>

            <!-- Row 1: Owner & Due Date -->
            <div class="mw-form-row mw-form-group">
                <div class="mw-form-col">
                    <label class="mw-form-label">Owner</label>
                    <?php
                    $ownerName = $_SESSION['user']['full_name'] ?? ($_SESSION['user']['name'] ?? '');
                    $ownerEmpCode = !empty($_SESSION['user']['employee_id']) ? $_SESSION['user']['employee_id'] : '';
                    foreach (($usersList ?? []) as $u) {
                        if ((int)$u['id'] === $currentUserId) {
                            $ownerName = $u['full_name'];
                            if (!empty($u['employee_code'])) {
                                $ownerEmpCode = $u['employee_code'];
                            }
                            break;
                        }
                    }
                    $ownerEmpCode = preg_replace('/^EMP-?/i', '', trim((string)$ownerEmpCode));
                    $ownerDisplay = trim($ownerName) . ($ownerEmpCode !== '' ? " ({$ownerEmpCode})" : '');
                    ?>
                    <div id="taskOwnerSelect">
                        <input type="text" class="mw-form-input" value="<?= htmlspecialchars($ownerDisplay) ?>" readonly style="background:#F5F7F6;cursor:not-allowed;">
                    </div>
                </div>
                <div class="mw-form-col">
                    <label class="mw-form-label">Deadline <span class="req">*</span></label>
                    <input type="date" name="deadline" id="drawerDeadline" class="mw-form-input" value="<?= date('Y-m-d', strtotime('+3 days')) ?>" required>
                </div>
            </div>

            <!-- Row 2: Priority -->
            <div class="mw-form-row mw-form-group">
                <div class="mw-form-col">
                    <label class="mw-form-label">Priority</label>
                    <select name="priority" class="mw-form-select">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
            </div>

            <!-- Row 3: Real estate project -->
            <div class="mw-form-row mw-form-group">
                <div class="mw-form-col">
                    <label class="mw-form-label">Real estate project</label>
                    <select name="project_id" class="mw-form-select">
                        <option value="">Not linked</option>
                        <?php foreach (($realEstateProjectsList ?? []) as $rp): ?>
                            <option value="<?= (int)$rp['id'] ?>"><?= htmlspecialchars($rp['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mw-form-col">
                    <label class="mw-form-label">&nbsp;</label>
                    <label class="mw-checkbox-wrap" style="margin-top:8px;">
                        <input type="checkbox" name="is_recurring" value="1" id="chkIsRecurring">
                        <span>Is Recurring</span>
                    </label>
                </div>
            </div>

            <div class="mw-form-group" id="rowRecurrenceEnd" style="display:none;">
                <label class="mw-form-label">Repeat</label>
                <select name="recurrence_type" id="taskRecurrenceType" class="mw-form-select">
                    <option value="">Select...</option>
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                </select>
            </div>

            <!-- Expected output (textarea) -->
            <div class="mw-form-group">
                <label class="mw-form-label">Expected output</label>
                <textarea name="expected_output" id="drawerExpectedOutput" class="mw-form-textarea" rows="2" 
                          placeholder="Describe the expected deliverable, results, or outcome..."></textarea>
            </div>

            <!-- Acceptance Checklist (multiple allowed, optional) -->
            <div class="mw-form-group">
                <label class="mw-form-label">Acceptance checklist</label>
                <div id="expectedOutputList">
                    <div class="d-flex align-items-start gap-2 mb-2 mw-expected-row">
                        <textarea name="description[]" id="drawerDescription" class="mw-form-textarea" rows="1" 
                                  placeholder="e.g. Verify report totals, send summary to team..."></textarea>
                        <button type="button" class="mw-btn-create" id="btnAddExpectedOutput" title="Add another checklist item" style="white-space:nowrap;">
                            <i class="fa-solid fa-plus"></i> Add
                        </button>
                    </div>
                </div>
            </div>

            <!-- Assigned to & CC -->
            <div class="mw-form-row mw-form-group">
                <div class="mw-form-col">
                    <label class="mw-form-label">Assigned to</label>
                    <?php $mwMulti('assigned_to_id', 'Select Assigned to...', true, $currentUserId, 'taskAssignedToSelect'); ?>
                </div>
                <div class="mw-form-col">
                    <label class="mw-form-label">CC</label>
                    <?php $mwMulti('cc_ids[]', 'Select CC...'); ?>
                </div>
            </div>

            <!-- Row 4: Reviewer & Collaborators -->
            <div id="sectionReviewFields" class="mw-form-row mw-form-group">
                <div class="mw-form-col">
                    <label class="mw-form-label">Reviewer</label>
                    <?php $mwMulti('reviewer_id', 'Select Reviewer...', true, null, 'taskReviewerSelect'); ?>
                </div>
                <div class="mw-form-col">
                    <label class="mw-form-label">Collaborators</label>
                    <?php $mwMulti('collaborator_ids[]', 'Select Collaborators...'); ?>
                </div>
            </div>

            <!-- Attachment -->
            <div class="mw-form-group">
                <label class="mw-form-label">Attachment</label>
                <input type="file" name="attachment" id="drawerAttachment" class="mw-form-input">
            </div>

            <!-- Start Date -->
            <div class="mw-form-group">
                <label class="mw-form-label">Start Date</label>
                <input type="date" name="start_date" class="mw-form-input">
            </div>

            <!-- Callout Notice Box -->
            <div class="mw-callout-box" id="mwTaskNoticeBox">
                <div class="mw-callout-icon">
                    <i class="fa-regular fa-bell"></i>
                </div>
                <div class="mw-callout-content">
                    <div class="mw-callout-title" id="noticeOwnerText">Owner will receive the assignment.</div>
                    <div class="mw-callout-sub" id="noticeReviewerText">Reviewer will be notified when the task is submitted for review.</div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mw-drawer-footer">
            <div class="mw-drawer-footer-actions">
                <div class="d-flex gap-2">
                    <button type="button" class="mw-drawer-btn secondary" id="btnCancelDrawer" onclick="closeAssignTaskDrawer()">Cancel</button>
                </div>
                <button type="submit" class="mw-drawer-btn primary" id="btnSubmitDrawer">
                    <i class="fa-solid fa-plus me-1"></i> Create & assign
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Chart.js CDN safeguard in case not already loaded -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
    // Right Slide Drawer (My Work Full Task Creator) Logic
    const mwBaseUrl = '<?= $GLOBALS['base_url'] ?>';
    const mwCurrentUserId = '<?= (int)($userId ?? ($_SESSION['user']['id'] ?? 0)) ?>';

    function showMwToast(message, type = 'success') {
        let wrap = document.getElementById('mwToastWrap');
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.id = 'mwToastWrap';
            wrap.className = 'mw-toast-wrap';
            document.body.appendChild(wrap);
        }
        const el = document.createElement('div');
        const isErr = type === 'error';
        el.className = 'mw-toast' + (isErr ? ' error' : '');
        const icon = isErr ? 'fa-circle-exclamation' : 'fa-circle-check';
        el.innerHTML = `<i class="fa-solid ${icon} mw-toast-icon"></i><span></span><button type="button" class="mw-toast-close" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>`;
        el.querySelector('span').textContent = message;
        el.querySelector('.mw-toast-close').addEventListener('click', () => el.remove());
        wrap.appendChild(el);
        setTimeout(() => { if (el.parentNode) el.remove(); }, 4500);
    }

    function mwPicked(id) {
        const i = document.querySelector('#' + id + ' input:checked');
        return { value: i ? i.value : '', name: i ? i.dataset.name : '' };
    }

    function updateNoticeBox() {
        const taskOwnerSelect = document.getElementById('taskAssignedToSelect');
        const taskReviewerSelect = document.getElementById('taskReviewerSelect');
        const noticeOwnerText = document.getElementById('noticeOwnerText');
        const noticeReviewerText = document.getElementById('noticeReviewerText');

        if (taskOwnerSelect && noticeOwnerText) {
            const o = mwPicked('taskAssignedToSelect');
            const firstName = (o.name || 'Owner').trim().split(' ')[0];
            noticeOwnerText.innerText = `${firstName} will receive the assignment.`;
        }
        if (taskReviewerSelect && noticeReviewerText) {
            const r = mwPicked('taskReviewerSelect');
            if (r.value) {
                const firstNameR = r.name.trim().split(' ')[0];
                noticeReviewerText.innerText = `${firstNameR} will be notified when the task is submitted for review.`;
                noticeReviewerText.style.display = 'block';
            } else {
                noticeReviewerText.style.display = 'none';
            }
        }
    }

    function resetAssignDrawerForm() {
        const formCreateTask = document.getElementById('mwCreateTaskForm');
        if (!formCreateTask) return;
        formCreateTask.reset();

        const editIdInput = document.getElementById('drawerEditTaskId');
        if (editIdInput) editIdInput.value = '';

        const expectedList = document.getElementById('expectedOutputList');
        if (expectedList) {
            expectedList.querySelectorAll('.mw-expected-row').forEach((r, idx) => { if (idx > 0) r.remove(); });
        }

        const rowRecurrenceEnd = document.getElementById('rowRecurrenceEnd');
        if (rowRecurrenceEnd) rowRecurrenceEnd.style.display = 'none';
        const selRec = document.getElementById('taskRecurrenceType');
        if (selRec) selRec.required = false;

        document.querySelectorAll('.mw-multi').forEach(b => {
            const s = b.querySelector('.mw-multi-search');
            if (s) s.value = '';
            b.querySelectorAll('.mw-multi-item').forEach(l => l.style.display = '');
            if (b._refresh) b._refresh();
        });
        updateNoticeBox();
    }

    function openAssignTaskDrawer(prefillUserId = null) {
        const drawer = document.getElementById('mwCreateTaskDrawer');
        const backdrop = document.getElementById('mwDrawerBackdrop');
        if (!drawer) return;

        resetAssignDrawerForm();

        if (prefillUserId) {
            const box = document.getElementById('taskAssignedToSelect');
            if (box) {
                const radios = box.querySelectorAll('input[type="radio"]');
                radios.forEach(r => {
                    r.checked = (String(r.value) === String(prefillUserId));
                });
                if (box._refresh) box._refresh();
                updateNoticeBox();
            }
        }

        if (backdrop) backdrop.classList.add('show');
        drawer.classList.add('show');
        document.body.style.overflow = 'hidden';

        setTimeout(() => {
            const titleInput = document.getElementById('drawerTaskTitle');
            if (titleInput) titleInput.focus();
        }, 320);
    }

    function closeAssignTaskDrawer() {
        const drawer = document.getElementById('mwCreateTaskDrawer');
        const backdrop = document.getElementById('mwDrawerBackdrop');
        if (drawer) drawer.classList.remove('show');
        if (backdrop) backdrop.classList.remove('show');
        document.body.style.overflow = '';
        resetAssignDrawerForm();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const taskOwnerSelect = document.getElementById('taskAssignedToSelect');
        const taskReviewerSelect = document.getElementById('taskReviewerSelect');
        if (taskOwnerSelect) taskOwnerSelect.addEventListener('change', updateNoticeBox);
        if (taskReviewerSelect) taskReviewerSelect.addEventListener('change', updateNoticeBox);
        updateNoticeBox();

        const chkIsRecurring = document.getElementById('chkIsRecurring');
        const rowRecurrenceEnd = document.getElementById('rowRecurrenceEnd');
        if (chkIsRecurring && rowRecurrenceEnd) {
            chkIsRecurring.addEventListener('change', () => {
                rowRecurrenceEnd.style.display = chkIsRecurring.checked ? 'block' : 'none';
                const selRec = document.getElementById('taskRecurrenceType');
                if (selRec) selRec.required = chkIsRecurring.checked;
            });
        }

        document.querySelectorAll('.mw-multi').forEach(box => {
            const txt = box.querySelector('.mw-multi-text');
            const checks = box.querySelectorAll('input[type=checkbox], input[type=radio]');
            const refresh = () => {
                const names = [...checks].filter(i => i.checked).map(i => i.dataset.name);
                txt.innerText = names.length === 0 ? box.dataset.placeholder : (names.length <= 2 ? names.join(', ') : names.length + ' selected');
            };
            box._refresh = refresh;
            box.querySelector('.mw-multi-btn').addEventListener('click', () => {
                const wasOpen = box.classList.contains('open');
                document.querySelectorAll('.mw-multi.open').forEach(b => b.classList.remove('open'));
                if (!wasOpen) box.classList.add('open');
            });
            checks.forEach(i => i.addEventListener('change', () => {
                if (box.dataset.single === '1' && i.checked) {
                    checks.forEach(o => { if (o !== i) o.checked = false; });
                    box.classList.remove('open');
                }
                refresh();
                box.dispatchEvent(new Event('change', { bubbles: true }));
            }));
            checks.forEach(i => i.addEventListener('click', () => {
                if (i.type !== 'radio' || box.dataset.clearable !== '1') return;
                if (i.dataset.was === '1') {
                    i.checked = false;
                    i.dataset.was = '0';
                    box.classList.remove('open');
                    refresh();
                    box.dispatchEvent(new Event('change', { bubbles: true }));
                } else {
                    checks.forEach(o => o.dataset.was = '0');
                    i.dataset.was = '1';
                }
            }));
            refresh();
            box.querySelector('.mw-multi-search').addEventListener('input', function () {
                const q = this.value.toLowerCase();
                box.querySelectorAll('.mw-multi-item').forEach(l => l.style.display = l.innerText.toLowerCase().includes(q) ? '' : 'none');
            });
        });

        document.addEventListener('click', e => {
            document.querySelectorAll('.mw-multi.open').forEach(b => { if (!b.contains(e.target)) b.classList.remove('open'); });
        }, true);

        const expectedList = document.getElementById('expectedOutputList');
        const btnAddExpected = document.getElementById('btnAddExpectedOutput');
        if (expectedList && btnAddExpected) {
            btnAddExpected.addEventListener('click', function () {
                const row = document.createElement('div');
                row.className = 'd-flex align-items-start gap-2 mb-2 mw-expected-row';
                row.innerHTML = '<textarea name="description[]" class="mw-form-textarea" rows="1" placeholder="Another checklist item"></textarea>' +
                    '<button type="button" class="mw-btn-create" title="Remove" style="white-space:nowrap;background:#C92A2A;"><i class="fa-solid fa-xmark"></i></button>';
                row.querySelector('button').addEventListener('click', () => row.remove());
                expectedList.appendChild(row);
                row.querySelector('textarea').focus();
            });
        }

        const formCreateTask = document.getElementById('mwCreateTaskForm');
        const btnSubmitDrawer = document.getElementById('btnSubmitDrawer');
        if (formCreateTask) {
            formCreateTask.addEventListener('submit', function(e) {
                e.preventDefault();
                const ownerVal = mwPicked('taskAssignedToSelect').value || mwCurrentUserId;
                const reviewerVal = mwPicked('taskReviewerSelect').value;
                if (reviewerVal && ownerVal && String(reviewerVal) === String(ownerVal) &&
                    !confirm('Assignee and Reviewer are the same person. Continue?')) {
                    return;
                }

                if (btnSubmitDrawer) {
                    btnSubmitDrawer.disabled = true;
                    btnSubmitDrawer.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Creating...';
                }

                const formData = new FormData(formCreateTask);
                fetch(`${mwBaseUrl}/my-work/store-task?ajax=1`, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(async res => {
                    const text = await res.text();
                    try { return JSON.parse(text); }
                    catch (_) { throw new Error('Invalid server response.'); }
                })
                .then(data => {
                    if (data.status === 'success') {
                        closeAssignTaskDrawer();
                        showMwToast(data.message || 'Task created and assigned successfully.', 'success');
                        setTimeout(() => window.location.reload(), 900);
                    } else {
                        showMwToast(data.message || 'Failed to create task.', 'error');
                    }
                })
                .catch(err => {
                    console.error('Error creating task:', err);
                    showMwToast(err.message || 'An error occurred while creating the task.', 'error');
                })
                .finally(() => {
                    if (btnSubmitDrawer) {
                        btnSubmitDrawer.disabled = false;
                        btnSubmitDrawer.innerHTML = '<i class="fa-solid fa-plus me-1"></i> Create & assign';
                    }
                });
            });
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const drawer = document.getElementById('mwCreateTaskDrawer');
            if (drawer && drawer.classList.contains('show')) {
                closeAssignTaskDrawer();
            }
        }
    });

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

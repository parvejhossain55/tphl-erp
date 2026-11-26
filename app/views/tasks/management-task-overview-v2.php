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
$monthNames = [];
for ($m = 0; $m < 12; $m++) {
    $ts = strtotime("-$m months", strtotime(date('Y-m-01')));
    $monthNames[date('Y-m', $ts)] = date('F Y', $ts);
}
if (!empty($period) && $period !== 'all' && !isset($monthNames[$period]) && preg_match('/^\d{4}-\d{2}$/', $period)) {
    $monthNames[$period] = date('F Y', strtotime($period . '-01'));
}
?>

<style>
.mgmt-container {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    color: #1e293b;
    width: 100%;
    max-width: 100%;
    padding: 0 4px;
    margin: 0;
}
.mgmt-header-tag {
    font-size: 11px;
    font-weight: 800;
    color: #15803d;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}
.mgmt-header-title {
    font-size: 26px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
}
.mgmt-mode-pills {
    display: inline-flex;
    background: #f1f5f9;
    border-radius: 20px;
    padding: 3px;
    border: 1px solid #e2e8f0;
}
.mgmt-mode-pill {
    padding: 6px 14px;
    font-size: 12.5px;
    font-weight: 600;
    color: #64748b;
    border-radius: 16px;
    text-decoration: none;
    transition: all 0.2s;
    display: inline-block;
}
.mgmt-mode-pill:hover { color: #0f172a; }
.mgmt-mode-pill.active {
    background: #ffffff;
    color: #15803d;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}
.mgmt-btn-export {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    color: #334155;
    padding: 7px 16px;
    font-size: 13px;
    font-weight: 600;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.2s;
}
.mgmt-btn-export:hover {
    background: #f8fafc;
    color: #0f172a;
    border-color: #cbd5e1;
}
.mgmt-btn-add {
    background: #15803d;
    border: 1px solid #15803d;
    color: #ffffff;
    padding: 7px 18px;
    font-size: 13px;
    font-weight: 600;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.2s;
}
.mgmt-btn-add:hover {
    background: #166534;
    border-color: #166534;
    color: #ffffff;
}
.mgmt-filter-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 8px 14px;
    margin-bottom: 12px;
}
.mgmt-filter-label {
    font-size: 11.5px;
    font-weight: 800;
    color: #64748b;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    margin-right: 6px;
}
.mgmt-select-pill {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 6px 14px;
    font-size: 12.5px;
    font-weight: 600;
    color: #334155;
    outline: none;
    cursor: pointer;
    transition: all 0.2s;
}
.mgmt-select-pill:focus {
    border-color: #16a34a;
    box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.15);
}
.mgmt-kpi-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 10px;
    margin-bottom: 12px;
}
@media (max-width: 1280px) { .mgmt-kpi-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px) { .mgmt-kpi-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px) { .mgmt-kpi-grid { grid-template-columns: 1fr; } }
.mgmt-kpi-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px 16px;
}
.mgmt-kpi-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}
.mgmt-kpi-title {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}
.mgmt-kpi-icon {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    background: #f1f5f9;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
}
.mgmt-kpi-val {
    font-size: 26px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.1;
    margin-bottom: 6px;
}
.mgmt-kpi-sub {
    font-size: 11.5px;
    color: #64748b;
    font-weight: 500;
}
.mgmt-charts-grid {
    display: grid;
    grid-template-columns: minmax(0, 7fr) minmax(0, 5fr);
    gap: 12px;
    margin-bottom: 12px;
    align-items: stretch;
}
@media (max-width: 991px) { .mgmt-charts-grid { grid-template-columns: 1fr; } }
#mgmtTabs .nav-link {
    color: #475569;
    border-radius: 20px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}
#mgmtTabs .nav-link.active {
    background: #15803d;
    border-color: #15803d;
    color: #ffffff;
}
</style>

<div class="mgmt-container">
    <!-- Top Header Area (Matching Team Performance Design) -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div>
            <div class="mgmt-header-tag mb-1">EXECUTIVE OVERVIEW</div>
            <h3 class="mgmt-header-title mb-1">Management Overview</h3>
            <p class="text-muted small mb-0">Organization-wide task delivery, department health, and HOD leadership performance.</p>
        </div>
        <div class="d-flex align-items-center flex-wrap gap-2">
            <div class="mgmt-mode-pills">
                <a href="<?= $GLOBALS['base_url'] ?>/management-task-overview-v2" class="mgmt-mode-pill active">Overview</a>
                <a href="<?= $GLOBALS['base_url'] ?>/team-performance-v2" class="mgmt-mode-pill">Team Performance</a>
                <a href="<?= $GLOBALS['base_url'] ?>/my-work-dashboard" class="mgmt-mode-pill">My Work</a>
            </div>
            <button type="button" onclick="exportOverviewCSV()" class="mgmt-btn-export">
                <i class="bi bi-box-arrow-down"></i> Export
            </button>
            <button type="button" onclick="openAssignTaskDrawer()" class="mgmt-btn-add">
                <i class="bi bi-plus-lg"></i> Add Task
            </button>
        </div>
    </div>

    <!-- Filter Bar Card (Matching Team Performance Pill Bar) -->
    <div class="mgmt-filter-card">
        <form method="GET" action="<?= $GLOBALS['base_url'] ?>/management-task-overview-v2" id="mgmtFilterForm" class="d-flex align-items-center flex-wrap gap-2">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>" id="mgmtTabInput">
            <span class="mgmt-filter-label">FILTER BY</span>

            <!-- Period Filter -->
            <select name="period" id="mgmtFilterPeriod" class="mgmt-select-pill">
                <option value="all" <?= ($period === 'all') ? 'selected' : '' ?>>All Time</option>
                <?php foreach ($monthNames as $mKey => $mLabel): ?>
                    <option value="<?= $mKey ?>" <?= ($period === $mKey) ? 'selected' : '' ?>><?= $mLabel ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Department Filter -->
            <select name="department_id" id="mgmtFilterDepartment" class="mgmt-select-pill">
                <option value="0">All Departments</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= ($deptFilter == $d['id']) ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Employee Filter -->
            <select name="employee_id" id="mgmtFilterEmployee" class="mgmt-select-pill">
                <option value="0">All Employees</option>
                <?php foreach ($employees as $e): ?>
                    <option value="<?= $e['id'] ?>" <?= ($empFilter == $e['id']) ? 'selected' : '' ?>><?= htmlspecialchars($e['full_name']) ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Task Type Filter -->
            <select name="type" id="mgmtFilterType" class="mgmt-select-pill">
                <option value="all" <?= ($typeFilter === 'all') ? 'selected' : '' ?>>Assigned + Self</option>
                <option value="assigned" <?= ($typeFilter === 'assigned') ? 'selected' : '' ?>>Team Projects Only</option>
                <option value="self" <?= ($typeFilter === 'self') ? 'selected' : '' ?>>Self Tasks Only</option>
            </select>

            <!-- Action Buttons -->
            <div class="d-flex align-items-center gap-1 ms-1">
                <button type="submit" id="btnMgmtApplyFilters" class="btn btn-sm btn-success rounded-pill px-3 fw-semibold text-white d-inline-flex align-items-center gap-1" style="font-size: 12.5px; height: 32px; background-color: #15803d; border-color: #15803d;">
                    <i class="bi bi-funnel-fill"></i> Apply
                </button>
                <button type="button" id="btnMgmtResetFilters" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-semibold d-inline-flex align-items-center gap-1" style="font-size: 12.5px; height: 32px;">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
            </div>
        </form>
    </div>

    <div id="mgmtDashboardContent" style="transition: opacity 0.18s ease;">
    <!-- 6 KPI Metric Cards Grid (Matching Team Performance) -->
    <div class="mgmt-kpi-grid">
        <div class="mgmt-kpi-card">
            <div class="mgmt-kpi-header">
                <span class="mgmt-kpi-title">ORG TASKS</span>
                <span class="mgmt-kpi-icon"><i class="bi bi-grid-fill"></i></span>
            </div>
            <div class="mgmt-kpi-val"><?= str_pad((string)$totalOrg, 2, '0', STR_PAD_LEFT) ?></div>
            <div class="mgmt-kpi-sub">Total tracked pipeline</div>
        </div>

        <div class="mgmt-kpi-card">
            <div class="mgmt-kpi-header">
                <span class="mgmt-kpi-title">COMPLETED</span>
                <span class="mgmt-kpi-icon"><i class="bi bi-check2"></i></span>
            </div>
            <div class="mgmt-kpi-val"><?= str_pad((string)$totalComp, 2, '0', STR_PAD_LEFT) ?></div>
            <div class="mgmt-kpi-sub"><?= $compRate ?>% completion rate</div>
        </div>

        <div class="mgmt-kpi-card">
            <div class="mgmt-kpi-header">
                <span class="mgmt-kpi-title">ACTIVE TASKS</span>
                <span class="mgmt-kpi-icon"><i class="bi bi-clock"></i></span>
            </div>
            <div class="mgmt-kpi-val"><?= str_pad((string)$totalAct, 2, '0', STR_PAD_LEFT) ?></div>
            <div class="mgmt-kpi-sub">In progress & pending</div>
        </div>

        <div class="mgmt-kpi-card">
            <div class="mgmt-kpi-header">
                <span class="mgmt-kpi-title">OVERDUE</span>
                <span class="mgmt-kpi-icon" style="<?= $totalOvd > 0 ? 'background:#fef2f2;color:#dc2626;' : '' ?>"><i class="bi bi-exclamation-triangle"></i></span>
            </div>
            <div class="mgmt-kpi-val" style="<?= $totalOvd > 0 ? 'color:#dc2626;' : '' ?>"><?= str_pad((string)$totalOvd, 2, '0', STR_PAD_LEFT) ?></div>
            <div class="mgmt-kpi-sub" style="<?= $totalOvd > 0 ? 'color:#dc2626;' : '' ?>"><?= $totalOvd > 0 ? $ovdRate . '% past deadline' : 'Zero overdue' ?></div>
        </div>

        <div class="mgmt-kpi-card">
            <div class="mgmt-kpi-header">
                <span class="mgmt-kpi-title">ON-TIME RATE</span>
                <span class="mgmt-kpi-icon"><i class="bi bi-speedometer2"></i></span>
            </div>
            <div class="mgmt-kpi-val"><?= $onTimeRate ?>%</div>
            <div class="mgmt-kpi-sub">Punctuality index</div>
        </div>

        <div class="mgmt-kpi-card">
            <div class="mgmt-kpi-header">
                <span class="mgmt-kpi-title">HOD ASSIGNED</span>
                <span class="mgmt-kpi-icon"><i class="bi bi-diagram-3"></i></span>
            </div>
            <div class="mgmt-kpi-val"><?= str_pad((string)$hodAssigned, 2, '0', STR_PAD_LEFT) ?></div>
            <div class="mgmt-kpi-sub">Delegated team tasks</div>
        </div>
    </div>

    <!-- Charts & Analytics Row -->
    <div class="mgmt-charts-grid">
        <!-- Left: Department Performance (Horizontal Stacked Bars) -->
        <div>
            <div class="card border shadow-none h-100" style="border-radius: 12px; background: #ffffff; border-color: #e2e8f0 !important;">
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
        <div>
            <div class="card border shadow-none h-100" style="border-radius: 12px; background: #ffffff; border-color: #e2e8f0 !important;">
                <div class="card-header bg-transparent border-0 pt-3 pb-2 px-3 d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-heart-pulse me-2 text-primary"></i>Organization Task Health</h6>
                        <span class="text-muted small" style="font-size: 11.5px;">Overall breakdown & executive leadership observations</span>
                    </div>
                </div>
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <!-- Donut Chart Canvas Container -->
                    <div style="position: relative; height: 180px;" class="mb-3">
                        <canvas id="mgmtHealthDonutChart"
                                data-completed="<?= (int)$totalComp ?>"
                                data-active="<?= (int)$totalAct ?>"
                                data-overdue="<?= (int)$totalOvd ?>"
                                data-total="<?= max(1, (int)$totalOrg) ?>"></canvas>
                        <!-- Center Label Overlay -->
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; pointer-events: none;">
                            <div class="fw-bold text-dark" style="font-size: 22px; line-height: 1;"><?= number_format($totalOrg) ?></div>
                            <span class="text-muted" style="font-size: 11px; text-transform: uppercase;">Tasks</span>
                        </div>
                    </div>

                    <!-- Management Insights Box -->
                    <div class="card border p-3 shadow-none" style="border-radius: 10px; background: #f8fafc; border-color: #e2e8f0 !important;">
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
    <div class="card border shadow-none mb-3" style="border-radius: 12px; background: #ffffff; border-color: #e2e8f0 !important;">
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
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= ($tab === 'overdue') ? 'active' : '' ?> px-3 py-2 fw-semibold small" 
                                onclick="switchMgmtTab('overdue')" type="button">
                            <i class="fa-solid fa-triangle-exclamation text-danger me-1"></i> Overdue & Bottlenecks
                            <span class="badge <?= ($tab === 'overdue') ? 'bg-white text-danger' : 'bg-danger-subtle text-danger' ?> ms-1 rounded-pill"><?= count($overdueBottlenecks ?? []) ?></span>
                        </button>
                    </li>
                </ul>

                <!-- Instant Table Search -->
                <div class="position-relative" style="min-width: 240px;">
                    <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="left: 12px; top: 50%; transform: translateY(-50%); font-size: 12px; pointer-events: none;"></i>
                    <input type="text" id="mgmtTableSearch" class="form-control form-control-sm" style="padding-left: 34px !important; padding-inline-start: 34px !important;" placeholder="Filter rows in view..." onkeyup="filterMgmtTable()">
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
                                <th class="text-center py-3" title="Non-self tasks assigned to team members">Team Assigned</th>
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
                                    <tr class="searchable-row" style="cursor: pointer;" onclick="if (!event.target.closest('a, button')) window.location.href='<?= $GLOBALS['base_url'] ?>/team-performance-v2?hod_id=<?= (int)$h['employee_id'] ?>';">
                                        <td class="ps-3 py-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 13px;">
                                                    <?= strtoupper(substr($h['full_name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <a href="<?= $GLOBALS['base_url'] ?>/team-performance-v2?hod_id=<?= (int)$h['employee_id'] ?>" class="fw-bold text-dark text-decoration-none d-block small mb-0">
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
                                            <?php
                                                $deptScoreClass = $d['total'] === 0
                                                    ? 'bg-light text-secondary border'
                                                    : ($d['score'] >= 80 ? 'bg-success-subtle text-success'
                                                    : ($d['score'] >= 50 ? 'bg-primary-subtle text-primary'
                                                    : ($d['score'] > 0 ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger')));
                                            ?>
                                            <span class="badge <?= $deptScoreClass ?> fw-bold px-2 py-1" style="font-size: 11px;" title="35% Completion + 25% On-Time + 15% Early + 25% Feedback (normalized if unrated)">
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
                                            <?php
                                                $indScoreClass = $ind['score'] >= 80 ? 'bg-success-subtle text-success'
                                                    : ($ind['score'] >= 50 ? 'bg-primary-subtle text-primary'
                                                    : ($ind['score'] > 0 ? 'bg-warning-subtle text-warning' : 'bg-light text-secondary border'));
                                            ?>
                                            <span class="badge <?= $indScoreClass ?> fw-bold px-2 py-1" style="font-size: 11px;" title="35% Completion + 25% On-Time + 15% Early + 25% Feedback (Early: <?= $ind['before_deadline_pct'] ?? 0 ?>%, On-Time: <?= $ind['on_time_pct'] ?>%)">
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

            <!-- TAB 4: Overdue & Bottlenecks Table -->
            <div id="tab-overdue" class="tab-pane-content <?= ($tab === 'overdue') ? '' : 'd-none' ?>">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="overdueTable">
                        <thead class="table-light text-uppercase small text-secondary" style="font-size: 11px; letter-spacing: 0.5px;">
                            <tr>
                                <th class="ps-3 py-3">Task</th>
                                <th class="py-3">Assignee / Department</th>
                                <th class="py-3">Reporting Boss</th>
                                <th class="text-center py-3">Priority</th>
                                <th class="text-center py-3">Deadline & Delay</th>
                                <th class="pe-3 text-center py-3">Progress</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php if (empty($overdueBottlenecks)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">Zero overdue tasks found for the selected period.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($overdueBottlenecks as $ob):
                                    $pBadge = $ob['priority'] === 'urgent' ? 'bg-danger text-white'
                                        : ($ob['priority'] === 'high' ? 'bg-warning-subtle text-warning'
                                        : ($ob['priority'] === 'low' ? 'bg-light text-secondary border' : 'bg-info-subtle text-info'));
                                ?>
                                    <tr class="searchable-row">
                                        <td class="ps-3 py-3">
                                            <div class="fw-semibold text-dark small"><?= htmlspecialchars($ob['title']) ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark small"><?= htmlspecialchars($ob['assignee_name']) ?></div>
                                            <div class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($ob['department_name']) ?></div>
                                        </td>
                                        <td class="small text-secondary"><?= htmlspecialchars($ob['boss_name']) ?></td>
                                        <td class="text-center">
                                            <span class="badge <?= $pBadge ?> text-uppercase" style="font-size: 10px;"><?= htmlspecialchars($ob['priority']) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <div class="small fw-semibold text-dark"><?= date('d M Y', strtotime($ob['deadline'])) ?></div>
                                            <span class="badge bg-danger-subtle text-danger" style="font-size: 10.5px;"><?= (int)$ob['days_overdue'] ?>d overdue</span>
                                        </td>
                                        <td class="pe-3 text-center">
                                            <span class="fw-bold small text-secondary"><?= (int)$ob['progress'] ?>%</span>
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
    </div><!-- /#mgmtDashboardContent -->
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
.mw-drawer-footer-actions { display: flex; justify-content: flex-start; align-items: center; gap: 12px; }
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

            <!-- Row 1: Owner & Priority -->
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
                    <label class="mw-form-label">Priority</label>
                    <select name="priority" class="mw-form-select">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
            </div>

            <!-- Row 2: Start Date & Deadline -->
            <div class="mw-form-row mw-form-group">
                <div class="mw-form-col">
                    <label class="mw-form-label">Start Date</label>
                    <input type="date" name="start_date" class="mw-form-input">
                </div>
                <div class="mw-form-col">
                    <label class="mw-form-label">Deadline <span class="req" id="drawerDeadlineReq">*</span></label>
                    <input type="date" name="deadline" id="drawerDeadline" class="mw-form-input" value="<?= date('Y-m-d', strtotime('+3 days')) ?>" required>
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
                    <option value="custom">Custom</option>
                </select>
                <div id="customRecurringDatesWrap" style="display:none; margin-top:12px; padding:14px; background:#FAFCFA; border:1px solid #E2EAE5; border-radius:8px;">
                    <style>
                        .mw-day-pill input:checked + span {
                            background: #087F5B !important;
                            border-color: #087F5B !important;
                            color: #FFFFFF !important;
                        }
                    </style>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="mw-form-label mb-0" style="color:#142D26;">Select Days <span class="req">*</span></span>
                        <button type="button" id="btnClearCustomDates" style="border:0; background:none; color:#C92A2A; font-size:12px; font-weight:600; cursor:pointer; padding:0;">Clear</button>
                    </div>
                    <div id="customRecurringDaysGroup" style="display:flex; flex-wrap:wrap; gap:8px;">
                        <?php foreach (['sun' => 'Sun', 'mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat'] as $dayKey => $dayLabel): ?>
                            <label class="mw-day-pill" style="margin:0; cursor:pointer;">
                                <input type="checkbox" name="recurrence_custom_dates[]" value="<?= $dayKey ?>" class="mw-custom-day-chk" style="display:none;">
                                <span style="display:inline-flex; align-items:center; justify-content:center; min-width:44px; padding:6px 10px; border-radius:6px; border:1px solid #D1DDD6; background:#FFFFFF; color:#142D26; font-size:12.5px; font-weight:600; transition:all .15s ease; user-select:none;"><?= $dayLabel ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
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
        const dlInputReset = document.getElementById('drawerDeadline');
        const dlReqReset = document.getElementById('drawerDeadlineReq');
        if (dlInputReset) dlInputReset.required = true;
        if (dlReqReset) dlReqReset.style.display = '';
        if (typeof window.setCustomRecurringDates === 'function') {
            window.setCustomRecurringDates([]);
        }

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
        const selRecType = document.getElementById('taskRecurrenceType');
        const customDatesWrap = document.getElementById('customRecurringDatesWrap');
        const btnClearCustomDates = document.getElementById('btnClearCustomDates');

        window.setCustomRecurringDates = function (datesArr) {
            const picked = new Set(Array.isArray(datesArr) ? datesArr.map(d => String(d).trim().toLowerCase()) : []);
            document.querySelectorAll('#customRecurringDaysGroup .mw-custom-day-chk').forEach(chk => {
                chk.checked = picked.has(chk.value);
            });
            if (customDatesWrap) {
                customDatesWrap.style.display = (chkIsRecurring && chkIsRecurring.checked && selRecType && selRecType.value === 'custom') ? 'block' : 'none';
            }
        };

        if (btnClearCustomDates) {
            btnClearCustomDates.addEventListener('click', () => {
                document.querySelectorAll('#customRecurringDaysGroup .mw-custom-day-chk').forEach(chk => {
                    chk.checked = false;
                });
            });
        }

        if (selRecType) {
            selRecType.addEventListener('change', () => {
                const isCust = (selRecType.value === 'custom');
                if (customDatesWrap) customDatesWrap.style.display = isCust ? 'block' : 'none';
            });
        }

        if (chkIsRecurring && rowRecurrenceEnd) {
            chkIsRecurring.addEventListener('change', () => {
                rowRecurrenceEnd.style.display = chkIsRecurring.checked ? 'block' : 'none';
                if (selRecType) selRecType.required = chkIsRecurring.checked;
                const dlInput = document.getElementById('drawerDeadline');
                const dlReq = document.getElementById('drawerDeadlineReq');
                if (dlInput) dlInput.required = !chkIsRecurring.checked;
                if (dlReq) dlReq.style.display = chkIsRecurring.checked ? 'none' : '';
                const isCust = (chkIsRecurring.checked && selRecType && selRecType.value === 'custom');
                if (customDatesWrap) customDatesWrap.style.display = isCust ? 'block' : 'none';
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
                if (chkIsRecurring && chkIsRecurring.checked && selRecType && selRecType.value === 'custom') {
                    const pickedDays = document.querySelectorAll('#customRecurringDaysGroup .mw-custom-day-chk:checked').length;
                    if (pickedDays === 0) {
                        showMwToast('Please select at least one day for the custom recurring schedule.', 'error');
                        return;
                    }
                }
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

    // Initialize / Re-initialize Donut Chart
    window.mgmtHealthChartInstance = null;
    window.renderMgmtHealthChart = function() {
        const ctx = document.getElementById('mgmtHealthDonutChart');
        if (!ctx || typeof Chart === 'undefined') return;

        if (window.mgmtHealthChartInstance) {
            try { window.mgmtHealthChartInstance.destroy(); } catch (e) {}
            window.mgmtHealthChartInstance = null;
        }

        const completed = parseInt(ctx.dataset.completed || '0', 10);
        const active = parseInt(ctx.dataset.active || '0', 10);
        const overdue = parseInt(ctx.dataset.overdue || '0', 10);
        const total = Math.max(1, parseInt(ctx.dataset.total || '1', 10));

        window.mgmtHealthChartInstance = new Chart(ctx, {
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
                                const pct = Math.round((val / total) * 100);
                                return ' ' + context.label + ': ' + val + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    };

    document.addEventListener('DOMContentLoaded', function() {
        window.renderMgmtHealthChart();

        const filterForm = document.getElementById('mgmtFilterForm');
        const applyBtn = document.getElementById('btnMgmtApplyFilters');
        const resetBtn = document.getElementById('btnMgmtResetFilters');
        const defaultPeriod = <?= json_encode(date('Y-m')) ?>;

        function loadMgmtOverviewAjax(targetUrl, pushState) {
            const contentWrap = document.getElementById('mgmtDashboardContent');
            if (!contentWrap) {
                window.location.href = targetUrl;
                return;
            }

            const origApplyHtml = applyBtn ? applyBtn.innerHTML : '';
            if (applyBtn) {
                applyBtn.disabled = true;
                applyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Loading';
            }
            contentWrap.style.opacity = '0.5';
            contentWrap.style.pointerEvents = 'none';

            fetch(targetUrl, {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.text();
            })
            .then(html => {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const newContent = doc.getElementById('mgmtDashboardContent');
                if (!newContent) {
                    window.location.href = targetUrl;
                    return;
                }

                contentWrap.innerHTML = newContent.innerHTML;
                contentWrap.style.opacity = '1';
                contentWrap.style.pointerEvents = '';

                if (pushState) {
                    window.history.pushState({ url: targetUrl }, '', targetUrl);
                }

                window.renderMgmtHealthChart();
                filterMgmtTable();
            })
            .catch(() => {
                window.location.href = targetUrl;
            })
            .finally(() => {
                if (applyBtn) {
                    applyBtn.disabled = false;
                    applyBtn.innerHTML = origApplyHtml;
                }
            });
        }

        if (filterForm) {
            filterForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const params = new URLSearchParams(new FormData(filterForm));
                const baseAction = filterForm.getAttribute('action').split('?')[0];
                loadMgmtOverviewAjax(`${baseAction}?${params.toString()}`, true);
            });
        }

        if (resetBtn && filterForm) {
            resetBtn.addEventListener('click', function() {
                const pEl = document.getElementById('mgmtFilterPeriod');
                const dEl = document.getElementById('mgmtFilterDepartment');
                const eEl = document.getElementById('mgmtFilterEmployee');
                const tEl = document.getElementById('mgmtFilterType');
                const tabEl = document.getElementById('mgmtTabInput');

                if (pEl) pEl.value = defaultPeriod;
                if (dEl) dEl.value = '0';
                if (eEl) eEl.value = '0';
                if (tEl) tEl.value = 'all';
                if (tabEl) tabEl.value = 'hods';

                const baseAction = filterForm.getAttribute('action').split('?')[0];
                loadMgmtOverviewAjax(baseAction, true);
            });
        }

        window.addEventListener('popstate', function() {
            const urlObj = new URL(window.location.href);
            const params = urlObj.searchParams;
            const pEl = document.getElementById('mgmtFilterPeriod');
            const dEl = document.getElementById('mgmtFilterDepartment');
            const eEl = document.getElementById('mgmtFilterEmployee');
            const tEl = document.getElementById('mgmtFilterType');
            const tabEl = document.getElementById('mgmtTabInput');

            if (pEl) pEl.value = params.get('period') || defaultPeriod;
            if (dEl) dEl.value = params.get('department_id') || '0';
            if (eEl) eEl.value = params.get('employee_id') || '0';
            if (tEl) tEl.value = params.get('type') || 'all';
            if (tabEl) tabEl.value = params.get('tab') || 'hods';

            loadMgmtOverviewAjax(window.location.href, false);
        });
    });
</script>

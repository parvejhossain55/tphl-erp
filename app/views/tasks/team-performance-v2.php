<style>
/* ==========================================================================
   HOD & Team Performance Dashboard Styles (Leadership Workspace)
   ========================================================================== */
.tphl-team-container {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    color: #1e293b;
    width: 100%;
    max-width: 100%;
    padding: 0 4px;
    margin: 0;
}

/* Header & Tags */
.team-header-tag {
    font-size: 11px;
    font-weight: 800;
    color: #15803d;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}
.team-header-title {
    font-size: 26px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
}

/* Mode Pill Switch */
.team-mode-pills {
    display: inline-flex;
    background: #f1f5f9;
    border-radius: 20px;
    padding: 3px;
    border: 1px solid #e2e8f0;
}
.team-mode-pill {
    padding: 6px 16px;
    font-size: 12.5px;
    font-weight: 600;
    color: #64748b;
    border-radius: 16px;
    text-decoration: none;
    transition: all 0.2s;
    display: inline-block;
}
.team-mode-pill:hover {
    color: #0f172a;
}
.team-mode-pill.active {
    background: #ffffff;
    color: #15803d;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.team-btn-export {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    color: #334155;
    padding: 7px 18px;
    font-size: 13px;
    font-weight: 600;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    transition: all 0.2s;
}
.team-btn-export:hover {
    background: #f8fafc;
    color: #0f172a;
    border-color: #cbd5e1;
}

.team-btn-add {
    background: #15803d;
    border: 1px solid #15803d;
    color: #ffffff;
    padding: 7px 20px;
    font-size: 13px;
    font-weight: 600;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    transition: all 0.2s;
    box-shadow: 0 2px 6px rgba(21, 128, 61, 0.25);
}
.team-btn-add:hover {
    background: #166534;
    border-color: #166534;
    color: #ffffff;
}

/* Filter Card */
.team-filter-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 10px 18px;
    margin-bottom: 16px;
}
.team-filter-label {
    font-size: 11.5px;
    font-weight: 800;
    color: #64748b;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    margin-right: 8px;
}
.team-select-pill {
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
.team-select-pill:focus {
    border-color: #16a34a;
    box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.15);
}

/* 6 KPI Cards Grid */
.team-kpi-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 12px;
    margin-bottom: 16px;
}
@media (max-width: 1280px) {
    .team-kpi-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
@media (max-width: 768px) {
    .team-kpi-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 480px) {
    .team-kpi-grid {
        grid-template-columns: 1fr;
    }
}

.team-kpi-card {
    background: #ffffff;
    border: 1px solid #f1f5f9;
    border-radius: 14px;
    padding: 18px 20px;
    position: relative;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    overflow: hidden;
}
.team-kpi-card::after {
    content: "";
    position: absolute;
    bottom: -15px;
    right: -15px;
    width: 65px;
    height: 65px;
    border-radius: 50%;
    opacity: 0.12;
    pointer-events: none;
}
.team-kpi-card.kpi-green::after { background: #16a34a; }
.team-kpi-card.kpi-amber::after { background: #d97706; }
.team-kpi-card.kpi-red::after   { background: #dc2626; }
.team-kpi-card.kpi-purple::after{ background: #8b5cf6; }

.team-kpi-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}
.team-kpi-title {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}
.team-kpi-icon {
    width: 22px;
    height: 22px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
}
.team-kpi-val {
    font-size: 26px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.1;
    margin-bottom: 6px;
}
.team-kpi-sub {
    font-size: 11.5px;
    color: #64748b;
    font-weight: 500;
}

/* Charts Section */
.team-charts-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
}
@media (max-width: 992px) {
    .team-charts-row {
        grid-template-columns: 1fr;
    }
}
.team-chart-card {
    background: #ffffff;
    border: 1px solid #f1f5f9;
    border-radius: 14px;
    padding: 22px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}
.team-chart-title {
    font-size: 16px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 2px;
}
.team-chart-subtitle {
    font-size: 12px;
    color: #64748b;
    margin-bottom: 0;
}

/* Workload Balance List */
.workload-item {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
    padding: 6px 10px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}
.workload-item:hover {
    background: #f8fafc;
    border-color: #e2e8f0;
    transform: translateX(3px);
}
.workload-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #f1f5f9;
    color: #334155;
    font-size: 11px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e2e8f0;
    flex-shrink: 0;
}
.workload-name {
    width: 80px;
    font-size: 12.5px;
    font-weight: 600;
    color: #334155;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.workload-bar-wrap {
    flex-grow: 1;
    height: 7px;
    background: #f1f5f9;
    border-radius: 4px;
    overflow: hidden;
}
.workload-bar-fill {
    height: 100%;
    background: #059669;
    border-radius: 4px;
}
.workload-count {
    width: 24px;
    text-align: right;
    font-size: 12px;
    font-weight: 700;
    color: #475569;
}

.workload-callout {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 10px;
    padding: 10px 14px;
    margin-top: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.workload-callout-icon {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #dcfce7;
    color: #15803d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 800;
}

/* Bottom Table Section */
.team-table-card {
    background: #ffffff;
    border: 1px solid #f1f5f9;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    overflow: hidden;
}
.team-table-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    border-bottom: 1px solid #f1f5f9;
    flex-wrap: gap;
}
.team-tabs {
    display: flex;
    align-items: center;
    gap: 18px;
}
.team-tab-item {
    font-size: 13.5px;
    font-weight: 600;
    color: #64748b;
    text-decoration: none;
    padding-bottom: 12px;
    margin-bottom: -15px;
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.team-tab-item:hover {
    color: #0f172a;
}
.team-tab-item.active {
    color: #15803d;
    border-bottom-color: #15803d;
    font-weight: 700;
}
.team-tab-pill {
    background: #f1f5f9;
    color: #475569;
    font-size: 11px;
    font-weight: 700;
    padding: 1px 7px;
    border-radius: 10px;
}
.team-tab-item.active .team-tab-pill {
    background: #dcfce7;
    color: #15803d;
}

.team-table {
    width: 100%;
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
}
.team-table th {
    background: #f8fafc;
    font-size: 11px;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 12px 18px;
    border-bottom: 1px solid #e2e8f0;
}
.team-table td {
    padding: 14px 18px;
    font-size: 13px;
    color: #334155;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
}
.team-table tbody tr:hover {
    background-color: #f8fafc;
}

/* Performance Badges */
.badge-perf-excellent {
    background: #f0fdf4;
    color: #15803d;
    border: 1px solid #bbf7d0;
    font-size: 11.5px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 16px;
    display: inline-block;
}
.badge-perf-strong {
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
    font-size: 11.5px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 16px;
    display: inline-block;
}
.badge-perf-ontrack {
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
    font-size: 11.5px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 16px;
    display: inline-block;
}
.badge-perf-watch {
    background: #fffbeb;
    color: #d97706;
    border: 1px solid #fde68a;
    font-size: 11.5px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 16px;
    display: inline-block;
}
.badge-perf-atrisk {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
    font-size: 11.5px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 16px;
    display: inline-block;
}

.trend-up {
    color: #16a34a;
    font-weight: 700;
    font-size: 12px;
}
.trend-down {
    color: #dc2626;
    font-weight: 700;
    font-size: 12px;
}

/* Member Table Avatar */
.member-avatar-wrap {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #f1f5f9;
    color: #1e293b;
    font-size: 11.5px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e2e8f0;
    margin-right: 10px;
    flex-shrink: 0;
}
.member-avatar-img {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 10px;
    border: 1px solid #e2e8f0;
}
</style>

<div class="tphl-team-container py-3 px-3">
    <!-- Flash Messages -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5 text-success"></i>
            <div class="fw-semibold"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5 text-danger"></i>
            <div class="fw-semibold"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Top Header (Matching Screenshot) -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <div class="team-header-tag mb-1">LEADERSHIP WORKSPACE</div>
            <h3 class="team-header-title mb-1">HOD & Team Performance</h3>
            <p class="text-muted small mb-0">Review delivery, workload and individual contribution across your team.</p>
        </div>
        <div class="d-flex align-items-center flex-wrap gap-3">
            <!-- Mode Switch (My Performance vs My Team) -->
            <div class="team-mode-pills">
                <a href="<?= $GLOBALS['base_url'] ?>/my-work-dashboard" class="team-mode-pill">My Performance</a>
                <a href="<?= $GLOBALS['base_url'] ?>/team-performance-v2" class="team-mode-pill active">My Team</a>
            </div>

            <!-- Export Button -->
            <a href="<?= $GLOBALS['base_url'] ?>/team-performance-v2?export=csv&<?= http_build_query($_GET) ?>" class="team-btn-export">
                <i class="bi bi-box-arrow-down"></i> Export
            </a>

            <!-- Assign Task Button -->
            <button type="button" class="team-btn-add" data-bs-toggle="modal" data-bs-target="#modalAssignTask">
                <i class="bi bi-plus-lg"></i> Assign Task
            </button>
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="team-filter-card">
        <form action="<?= $GLOBALS['base_url'] ?>/team-performance-v2" method="GET" class="d-flex align-items-center flex-wrap gap-2">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">

            <span class="team-filter-label">FILTER BY</span>

            <!-- Month Filter -->
            <select name="month" class="team-select-pill" onchange="this.form.submit()">
                <option value=""><?= date('F Y') ?></option>
                <option value="all_time" <?= $monthFilter === 'all_time' ? 'selected' : '' ?>>All Time</option>
                <?php 
                    for ($m = 0; $m < 6; $m++) {
                        $ym = date('Y-m', strtotime("-$m months"));
                        $label = date('F Y', strtotime("-$m months"));
                        $sel = ($monthFilter === $ym) ? 'selected' : '';
                        echo "<option value=\"$ym\" $sel>$label</option>";
                    }
                ?>
            </select>

            <!-- Team Members Filter -->
            <select name="member_id" class="team-select-pill" onchange="this.form.submit()">
                <option value="">All Team Members</option>
                <?php if (!empty($teamEmployees)): ?>
                    <?php foreach ($teamEmployees as $te): ?>
                        <option value="<?= $te['employee_id'] ?>" <?= ($memberFilter == $te['employee_id'] || $memberFilter == $te['user_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($te['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>

            <!-- Project Filter -->
            <select name="project_id" class="team-select-pill" onchange="this.form.submit()">
                <option value="">All Projects</option>
                <?php if (!empty($projects)): ?>
                    <?php foreach ($projects as $proj): ?>
                        <option value="<?= $proj['id'] ?>" <?= ($projectFilter == $proj['id']) ? 'selected' : '' ?>>
                            <?= (!empty($proj['is_team_project']) ? '👥 [Team] ' : '') . htmlspecialchars($proj['name']) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>

            <!-- Status Filter -->
            <select name="status" class="team-select-pill" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="overdue" <?= $statusFilter === 'overdue' ? 'selected' : '' ?>>Overdue</option>
            </select>

            <!-- Type Filter -->
            <select name="type" class="team-select-pill" onchange="this.form.submit()">
                <option value="assigned_self" <?= $typeFilter === 'assigned_self' ? 'selected' : '' ?>>Assigned + Self</option>
                <option value="assigned" <?= $typeFilter === 'assigned' ? 'selected' : '' ?>>Assigned Only</option>
                <option value="self" <?= $typeFilter === 'self' ? 'selected' : '' ?>>Self Only</option>
            </select>

            <!-- Reset Link -->
            <a href="<?= $GLOBALS['base_url'] ?>/team-performance-v2" class="btn btn-link text-decoration-none text-success small fw-semibold px-2 ms-auto">Reset</a>
        </form>
    </div>

    <!-- 6 KPI Cards Grid (Matching Screenshot) -->
    <div class="team-kpi-grid">
        <!-- 1. Team Tasks -->
        <div class="team-kpi-card kpi-green">
            <div class="team-kpi-header">
                <span class="team-kpi-title">TEAM TASKS</span>
                <span class="team-kpi-icon bg-light text-success"><i class="bi bi-grid-fill"></i></span>
            </div>
            <div class="team-kpi-val"><?= str_pad($kpi['total_tasks'], 2, '0', STR_PAD_LEFT) ?></div>
            <div class="team-kpi-sub text-success"><?= $kpi['tasks_this_month'] > 0 ? '+' . $kpi['tasks_this_month'] . ' tasks this month' : '0 tasks this month' ?></div>
        </div>

        <!-- 2. Completed -->
        <div class="team-kpi-card kpi-green">
            <div class="team-kpi-header">
                <span class="team-kpi-title">COMPLETED</span>
                <span class="team-kpi-icon bg-success-subtle text-success"><i class="bi bi-check2"></i></span>
            </div>
            <div class="team-kpi-val"><?= str_pad($kpi['completed'], 2, '0', STR_PAD_LEFT) ?></div>
            <div class="team-kpi-sub text-success"><?= $kpi['total_tasks'] > 0 ? $kpi['completion_rate'] . '% completion rate' : 'No completed tasks' ?></div>
        </div>

        <!-- 3. In Progress -->
        <div class="team-kpi-card kpi-amber">
            <div class="team-kpi-header">
                <span class="team-kpi-title">IN PROGRESS</span>
                <span class="team-kpi-icon bg-warning-subtle text-warning"><i class="bi bi-clock"></i></span>
            </div>
            <div class="team-kpi-val"><?= str_pad($kpi['in_progress'], 2, '0', STR_PAD_LEFT) ?></div>
            <div class="team-kpi-sub text-warning"><?= $kpi['in_progress'] > 0 ? $kpi['due_this_week'] . ' due this week' : 'No active tasks' ?></div>
        </div>

        <!-- 4. Overdue -->
        <div class="team-kpi-card kpi-red">
            <div class="team-kpi-header">
                <span class="team-kpi-title">OVERDUE</span>
                <span class="team-kpi-icon bg-danger-subtle text-danger"><i class="bi bi-exclamation"></i></span>
            </div>
            <div class="team-kpi-val"><?= str_pad($kpi['overdue'], 2, '0', STR_PAD_LEFT) ?></div>
            <div class="team-kpi-sub text-danger"><?= $kpi['total_tasks'] > 0 ? $kpi['overdue_pct'] . '% of team tasks' : 'No overdue tasks' ?></div>
        </div>

        <!-- 5. On-Time Rate -->
        <div class="team-kpi-card kpi-purple">
            <div class="team-kpi-header">
                <span class="team-kpi-title">ON TIME RATE</span>
                <span class="team-kpi-icon bg-primary-subtle text-primary"><i class="bi bi-shield-check"></i></span>
            </div>
            <div class="team-kpi-val"><?= $kpi['on_time_rate'] ?>%</div>
            <div class="team-kpi-sub text-primary"><?= $kpi['total_tasks'] > 0 ? $kpi['on_time_rate'] . '% on-time delivery' : 'No delivery data' ?></div>
        </div>

        <!-- 6. Avg Team Score -->
        <div class="team-kpi-card kpi-purple">
            <div class="team-kpi-header">
                <span class="team-kpi-title">AVG. TEAM SCORE</span>
                <span class="team-kpi-icon bg-secondary-subtle text-secondary"><i class="bi bi-pencil-square"></i></span>
            </div>
            <div class="team-kpi-val"><?= $kpi['avg_team_score'] ?></div>
            <div class="team-kpi-sub text-secondary"><?= $kpi['team_health_label'] ?></div>
        </div>
    </div>

    <!-- Charts Row: Team Delivery Trend & Workload Balance (Matching Screenshot) -->
    <div class="team-charts-row">
        <!-- Left: Team Delivery Trend Chart -->
        <div class="team-chart-card">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h5 class="team-chart-title">Team Delivery Trend</h5>
                    <p class="team-chart-subtitle">Completed, assigned and overdue tasks by week</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="team-mode-pills" style="padding: 2px;">
                        <button type="button" class="perf-time-pill active" onclick="changeDeliveryTime(event, '4W')">4W</button>
                        <button type="button" class="perf-time-pill" onclick="changeDeliveryTime(event, '3M')">3M</button>
                        <button type="button" class="perf-time-pill" onclick="changeDeliveryTime(event, '6M')">6M</button>
                    </div>
                </div>
            </div>

            <!-- Legend -->
            <div class="d-flex align-items-center gap-3 mb-2 small text-muted">
                <span><span class="d-inline-block rounded-circle me-1" style="width: 8px; height: 8px; background-color: #16a34a;"></span> Completed</span>
                <span><span class="d-inline-block rounded-circle me-1" style="width: 8px; height: 8px; background-color: #3b82f6;"></span> Assigned</span>
                <span><span class="d-inline-block rounded-circle me-1" style="width: 8px; height: 8px; background-color: #ef4444;"></span> Overdue</span>
            </div>

            <!-- ApexChart -->
            <div id="teamDeliveryChart" style="min-height: 240px;"></div>
        </div>

        <!-- Right: Workload Balance -->
        <div class="team-chart-card d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <h5 class="team-chart-title mb-0">Workload Balance</h5>
                    <span class="badge bg-light text-secondary border px-2 py-1 rounded-pill" style="font-size: 10.5px; cursor: pointer;" onclick="openWorkloadFormulaInfo()" title="Click to view calculation formula">
                        <i class="bi bi-info-circle me-1 text-success"></i> How it works
                    </span>
                </div>
                <p class="team-chart-subtitle mb-3">Open tasks per team member <span class="text-success small fw-semibold ms-1">(Click member to view)</span></p>

                <!-- Workload List -->
                <div class="workload-list">
                    <?php if (!empty($workloadBars)): ?>
                        <?php foreach ($workloadBars as $wb): ?>
                            <div class="workload-item" 
                                 onclick="openWorkloadBreakdown(<?= htmlspecialchars(json_encode($wb), ENT_QUOTES, 'UTF-8') ?>)" 
                                 title="Click to view workload calculation & open tasks for <?= htmlspecialchars($wb['full_name'] ?? $wb['name']) ?>">
                                <div class="workload-avatar"><?= $wb['initials'] ?></div>
                                <div class="workload-name" title="<?= htmlspecialchars($wb['full_name'] ?? $wb['name']) ?>"><?= htmlspecialchars($wb['name']) ?></div>
                                <div class="workload-bar-wrap">
                                    <div class="workload-bar-fill" style="width: <?= $wb['bar_pct'] ?>%;"></div>
                                </div>
                                <div class="workload-count d-flex align-items-center gap-1 justify-content-end">
                                    <span><?= str_pad($wb['open_tasks'], 2, '0', STR_PAD_LEFT) ?></span>
                                    <i class="bi bi-chevron-right text-muted" style="font-size: 11px;"></i>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted small">No active team workloads recorded.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bottom Callout (Matching Screenshot) -->
            <div class="workload-callout">
                <div class="workload-callout-icon">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div>
                    <div class="fw-bold text-dark" style="font-size: 12.5px;">Workload Status</div>
                    <div class="text-muted" style="font-size: 11.5px;"><?= !empty($highestWorkloadMember) ? htmlspecialchars($highestWorkloadMember) . ' has the highest active workload' : 'All team workloads are clear and balanced' ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Section: Team Members Performance Table (Matching Screenshot) -->
    <div class="team-table-card">
        <!-- Table Header Tabs & Action Buttons -->
        <div class="team-table-header">
            <div class="team-tabs">
                <a href="<?= $GLOBALS['base_url'] ?>/team-performance-v2?tab=all&<?= http_build_query(array_diff_key($_GET, ['tab' => ''])) ?>" class="team-tab-item <?= $tab === 'all' ? 'active' : '' ?>">
                    All Members <span class="team-tab-pill"><?= $allMembersCount ?></span>
                </a>
                <a href="<?= $GLOBALS['base_url'] ?>/team-performance-v2?tab=top_performers&<?= http_build_query(array_diff_key($_GET, ['tab' => ''])) ?>" class="team-tab-item <?= $tab === 'top_performers' ? 'active' : '' ?>">
                    Top Performers <span class="team-tab-pill"><?= $topPerformersCount ?></span>
                </a>
                <a href="<?= $GLOBALS['base_url'] ?>/team-performance-v2?tab=needs_attention&<?= http_build_query(array_diff_key($_GET, ['tab' => ''])) ?>" class="team-tab-item <?= $tab === 'needs_attention' ? 'active' : '' ?>">
                    Needs Attention <span class="team-tab-pill"><?= $needsAttentionCount ?></span>
                </a>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a href="<?= $GLOBALS['base_url'] ?>/team-performance-v2?sort=<?= $sort === 'score_desc' ? 'score_asc' : 'score_desc' ?>&<?= http_build_query(array_diff_key($_GET, ['sort' => ''])) ?>" class="btn btn-sm btn-light border rounded-pill px-3 fw-semibold text-secondary" style="font-size: 12.5px;">
                    <i class="bi bi-filter me-1"></i> Sort by Score
                </a>
                <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 fw-semibold text-secondary" style="font-size: 12.5px;" onclick="window.print()">
                    <i class="bi bi-layout-three-columns me-1"></i> Columns
                </button>
            </div>
        </div>

        <!-- Table Data -->
        <div class="table-responsive">
            <table class="team-table">
                <thead>
                    <tr>
                        <th>TEAM MEMBER</th>
                        <th>TOTAL</th>
                        <th>ASSIGNED</th>
                        <th>SELF TASK</th>
                        <th>COMPLETED</th>
                        <th>OVERDUE</th>
                        <th>ON-TIME</th>
                        <th>COMPLETION RATE</th>
                        <th>PERFORMANCE</th>
                        <th>TREND</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($members)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <div class="mb-2 text-success" style="font-size: 40px; opacity: 0.6;">
                                    <i class="bi bi-people"></i>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">No team members found</h6>
                                <p class="small text-muted mb-0">Employees with this reporting line will appear here.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($members as $m): 
                            $nameParts = preg_split('/\s+/', trim($m['full_name']));
                            $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
                            $isTrendUp = str_starts_with($m['trend'], '+');
                        ?>
                        <tr style="cursor: pointer;" onclick="openMemberTasksOffcanvas(<?= $m['employee_id'] ?>, '<?= htmlspecialchars($m['full_name'], ENT_QUOTES) ?>')">
                            <!-- Team Member -->
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($m['photo'])): ?>
                                        <img src="<?= getImage('users', $m['photo']) ?>" alt="Avatar" class="member-avatar-img">
                                    <?php else: ?>
                                        <div class="member-avatar-wrap"><?= $initials ?></div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 13.5px;"><?= htmlspecialchars($m['full_name']) ?></div>
                                        <div class="text-muted" style="font-size: 11.5px;"><?= htmlspecialchars($m['designation']) ?></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Total -->
                            <td class="fw-semibold text-secondary"><?= $m['total'] ?></td>

                            <!-- Assigned -->
                            <td class="text-secondary"><?= $m['assigned'] ?></td>

                            <!-- Self Task -->
                            <td class="text-secondary"><?= $m['self'] ?></td>

                            <!-- Completed -->
                            <td class="fw-semibold text-dark"><?= $m['completed'] ?></td>

                            <!-- Overdue -->
                            <td class="<?= $m['overdue'] > 0 ? 'text-danger fw-semibold' : 'text-secondary' ?>"><?= $m['overdue'] ?></td>

                            <!-- On-Time -->
                            <td class="text-secondary"><?= $m['on_time_pct'] ?>%</td>

                            <!-- Completion Rate -->
                            <td style="min-width: 140px;">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 6px; background-color: #e2e8f0; border-radius: 4px;">
                                        <div class="progress-bar" style="width: <?= $m['completion_rate'] ?>%; background-color: <?= $m['completion_rate'] >= 70 ? '#059669' : ($m['completion_rate'] >= 50 ? '#d97706' : '#dc2626') ?>; border-radius: 4px;"></div>
                                    </div>
                                    <span class="text-muted fw-bold" style="font-size: 11.5px; min-width: 32px;"><?= $m['completion_rate'] ?>%</span>
                                </div>
                            </td>

                            <!-- Performance Badge -->
                            <td>
                                <span class="<?= $m['badge_class'] ?>"><?= $m['badge'] ?></span>
                            </td>

                            <!-- Trend -->
                            <td class="<?= $isTrendUp ? 'trend-up' : 'trend-down' ?>">
                                <?= $isTrendUp ? '↑' : '↓' ?> <?= ltrim($m['trend'], '+-') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================================
     Modal: Create Team Project
     ======================================================================== -->
<div class="modal fade" id="modalCreateTeamProject" tabindex="-1" aria-labelledby="modalCreateTeamProjectLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <div>
                    <span class="team-header-tag">PROJECT MANAGEMENT</span>
                    <h5 class="modal-title fw-bold text-dark" id="modalCreateTeamProjectLabel">Create New Team Project</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= $GLOBALS['base_url'] ?>/team-performance-v2/create-project" method="POST" id="formCreateTeamProject">
                <input type="hidden" name="is_team_project" value="1">
                <div class="modal-body pt-3">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Team Project Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="newTeamProjNameInput" class="form-control rounded-3" placeholder="e.g. Sales Campaign Q4, Operation Overhaul..." required autocomplete="off">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Project Theme Color</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="color" name="color" class="form-control form-control-color rounded-circle p-1 border-0" value="#16a34a" title="Choose project theme color" style="width: 40px; height: 40px; cursor: pointer;">
                            <span class="small text-muted">Pick a theme color to identify this project badge across all team boards.</span>
                        </div>
                    </div>

                    <div class="alert alert-light border small text-muted mb-0">
                        <i class="bi bi-people-fill text-success me-1"></i>
                        This Team Project will be shared with all members reporting to this department, enabling team-wide task assignment, progress tracking, and management overview.
                    </div>
                    <div id="createTeamProjectFeedback" class="mt-2 text-center" style="display: none;"></div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 fw-semibold px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="btnSaveTeamProject" class="btn btn-success rounded-3 fw-semibold px-4 text-white" style="background-color: #15803d; border-color: #15803d;">Create Team Project</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================
     Modal: Assign Task to Team Member
     ======================================================================== -->
<div class="modal fade" id="modalAssignTask" tabindex="-1" aria-labelledby="modalAssignTaskLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <div>
                    <span class="team-header-tag">TEAM WORKSPACE</span>
                    <h5 class="modal-title fw-bold text-dark" id="modalAssignTaskLabel">Assign Task to Team Member</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= $GLOBALS['base_url'] ?>/team-performance-v2/assign-task" method="POST">
                <div class="modal-body pt-3">
                    <!-- Member Selection -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Assign To Team Member</label>
                        <select name="assigned_user_id" class="form-select rounded-3" required>
                            <option value="">Select team member...</option>
                            <?php foreach ($teamEmployees as $te): ?>
                                <?php if (!empty($te['user_id'])): ?>
                                    <option value="<?= $te['user_id'] ?>">
                                        <?= htmlspecialchars($te['full_name']) ?> (<?= htmlspecialchars($te['designation_name'] ?? 'Staff') ?>)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Task Title -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Task Title</label>
                        <input type="text" name="title" class="form-control rounded-3" placeholder="Enter task title..." required autocomplete="off">
                    </div>

                    <!-- Project Selection -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Project / Team Project</label>
                        <select name="project_id" id="assignTaskProjectId" class="form-select rounded-3" onchange="toggleAssignNewProject(this.value)">
                            <option value="">No Project (Inbox)</option>
                            <?php if (!empty($projects)): ?>
                                <optgroup label="Shared Team Projects">
                                    <?php foreach ($projects as $pr): ?>
                                        <?php if (!empty($pr['is_team_project'])): ?>
                                            <option value="<?= $pr['id'] ?>">👥 <?= htmlspecialchars($pr['name']) ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Other Projects">
                                    <?php foreach ($projects as $pr): ?>
                                        <?php if (empty($pr['is_team_project'])): ?>
                                            <option value="<?= $pr['id'] ?>">📁 <?= htmlspecialchars($pr['name']) ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                            <option value="new">+ Create New Team Project...</option>
                        </select>
                        <div id="assignNewProjectWrap" style="display: none;" class="mt-2 p-2 bg-light rounded-3 border">
                            <label class="form-label small fw-bold text-dark mb-1">New Team Project Name</label>
                            <input type="text" name="new_team_project_name" id="assign_new_project_name" class="form-control form-control-sm mb-1" placeholder="e.g. Sales Campaign Q4, ERP Module">
                            <span class="text-muted" style="font-size: 11px;">This project will be automatically shared with the team.</span>
                        </div>
                    </div>

                    <!-- Priority & Deadline -->
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary">Priority</label>
                            <select name="priority" class="form-select rounded-3">
                                <option value="1">Urgent</option>
                                <option value="2">High</option>
                                <option value="3" selected>Medium</option>
                                <option value="4">Low</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary">Deadline</label>
                            <input type="date" name="due_date" class="form-control rounded-3" value="<?= date('Y-m-d', strtotime('+3 days')) ?>">
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-secondary">Task Description</label>
                        <textarea name="description" class="form-control rounded-3" rows="3" placeholder="Provide deliverables and instructions..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 fw-semibold px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-3 fw-semibold px-4 text-white" style="background-color: #15803d; border-color: #15803d;">Assign Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================
     Modal: Update Member Task Progress (HOD Action)
     ======================================================================== -->
<div class="modal fade" id="modalUpdateMemberTask" tabindex="-1" aria-labelledby="modalUpdateMemberTaskLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <div>
                    <span class="team-header-tag">TASK MANAGEMENT</span>
                    <h5 class="modal-title fw-bold text-dark" id="modalUpdateMemberTaskLabel">Update Task Progress</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= $GLOBALS['base_url'] ?>/my-created-tasks/update-progress" method="POST" id="formUpdateMemberTask">
                <input type="hidden" name="ajax" value="1">
                <input type="hidden" name="task_id" id="memberUpdateTaskId" value="">
                <div class="modal-body pt-3">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Task Title</label>
                        <input type="text" id="memberUpdateTaskTitle" class="form-control rounded-3 bg-light" readonly>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary">Status</label>
                            <select name="status" id="memberUpdateStatus" class="form-select rounded-3">
                                <option value="pending">In Progress</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary">Deadline</label>
                            <input type="date" name="due_date" id="memberUpdateDueDate" class="form-control rounded-3">
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold text-secondary mb-0">Progress</label>
                            <span class="fw-bold text-success" id="memberProgressDisplay">0%</span>
                        </div>
                        <input type="range" name="progress" id="memberUpdateProgress" class="form-range" min="0" max="100" step="5" value="0">
                        <div class="d-flex justify-content-between text-muted" style="font-size: 11px;">
                            <span>0%</span>
                            <span>25%</span>
                            <span>50%</span>
                            <span>75%</span>
                            <span>100%</span>
                        </div>
                    </div>

                    <div id="memberUpdateFeedback" class="mt-2 text-center" style="display: none;"></div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 fw-semibold px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="btnSaveMemberTask" class="btn btn-success rounded-3 fw-semibold px-4 text-white" style="background-color: #15803d; border-color: #15803d;">Save Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================
     Modal: Workload Balance Breakdown (Calculation Details)
     ======================================================================== -->
<div class="modal fade" id="modalWorkloadBreakdown" tabindex="-1" aria-labelledby="modalWorkloadBreakdownLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom pb-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="workload-avatar shadow-sm" id="wbModalAvatar" style="width: 46px; height: 46px; font-size: 17px; background: #e8f5e9; color: #166534; border: 2px solid #bbf7d0;"></div>
                    <div>
                        <div class="team-header-tag mb-0" style="color: #059669;">WORKLOAD CALCULATION BREAKDOWN</div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="wbModalMemberName">Member Name</h5>
                        <div class="text-muted" style="font-size: 12px;" id="wbModalDesignation">Designation</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Calculation Formula Banner -->
                <div class="p-3 rounded-3 mb-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                    <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                        <span class="fw-bold text-dark" style="font-size: 13px;">
                            <i class="bi bi-calculator me-1 text-success"></i> Workload Formula & Count Summary:
                        </span>
                        <span class="badge bg-success-subtle text-success px-2.5 py-1 rounded-pill fw-semibold" style="font-size: 11px;">
                            Open Tasks = In Progress + Overdue
                        </span>
                    </div>

                    <div class="row g-2 text-center mt-1">
                        <div class="col-sm-4 col-12">
                            <div class="p-2.5 rounded bg-white border h-100">
                                <div class="text-muted text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">In Progress Tasks</div>
                                <div class="fw-bold text-warning-emphasis fs-4 my-1" id="wbModalInProgress">0</div>
                                <div class="text-muted" style="font-size: 11px;">Status pending, &lt;100%</div>
                            </div>
                        </div>
                        <div class="col-sm-4 col-12">
                            <div class="p-2.5 rounded bg-white border h-100">
                                <div class="text-muted text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">Overdue Tasks</div>
                                <div class="fw-bold text-danger fs-4 my-1" id="wbModalOverdue">0</div>
                                <div class="text-muted" style="font-size: 11px;">Deadline passed, &lt;100%</div>
                            </div>
                        </div>
                        <div class="col-sm-4 col-12">
                            <div class="p-2.5 rounded bg-white border h-100" style="border-color: #10b981 !important; background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%) !important;">
                                <div class="text-success text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">Total Open Workload</div>
                                <div class="fw-bold text-success fs-4 my-1" id="wbModalTotalOpen">0</div>
                                <div class="text-success small fw-semibold" style="font-size: 11px;">Counted in Workload Balance</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-2.5 pt-2 border-top d-flex align-items-center justify-content-between text-muted small" style="font-size: 11.5px;">
                        <span><i class="bi bi-check-circle me-1 text-success"></i> Completed tasks (<strong id="wbModalCompleted">0</strong>) are excluded from active workload.</span>
                        <span>Total Assigned: <strong id="wbModalTotal">0</strong></span>
                    </div>
                </div>

                <!-- Tasks Listing Header & Filter Tabs -->
                <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                    <h6 class="fw-bold text-dark mb-0" style="font-size: 13.5px;">
                        <i class="bi bi-list-task me-1 text-primary"></i> Detailed Task Breakdown
                    </h6>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary active py-0.5 px-2.5" id="wbFilterBtnOpen" onclick="filterWbModalTasks('open')">Open Tasks (<span id="wbTabOpenCount">0</span>)</button>
                        <button type="button" class="btn btn-outline-secondary py-0.5 px-2.5" id="wbFilterBtnAll" onclick="filterWbModalTasks('all')">All Tasks (<span id="wbTabAllCount">0</span>)</button>
                    </div>
                </div>

                <!-- Loading State -->
                <div id="wbModalLoading" class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm text-success me-2" role="status"></div>
                    Loading task details...
                </div>

                <!-- Tasks List -->
                <div id="wbModalTasksList" style="display: none; max-height: 360px; overflow-y: auto;"></div>
            </div>
            <div class="modal-footer border-top bg-light py-2 px-4 d-flex justify-content-between align-items-center">
                <span class="text-muted small">
                    <i class="bi bi-info-circle me-1 text-primary"></i> Click any task's "Update" button to modify its progress.
                </span>
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================
     Right Offcanvas: Specific Member's Todo Tasks
     ======================================================================== -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasMemberTasks" aria-labelledby="offcanvasMemberTasksLabel" style="width: 580px; max-width: 95vw;">
    <div class="offcanvas-header p-4 pb-2 border-bottom-0">
        <div>
            <div class="team-header-tag">MEMBER WORKSPACE</div>
            <h4 class="fw-bold mb-0 text-dark" id="offcanvasMemberTitle">Team Member Tasks</h4>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-4 pt-2">
        <div id="memberTasksLoading" class="text-center py-5 text-muted">
            <div class="spinner-border spinner-border-sm text-success me-2" role="status"></div>
            Loading tasks...
        </div>
        <div id="memberTasksList" style="display: none;"></div>
    </div>
</div>

<!-- ApexCharts Script Initialization -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const trendWeeks = <?= json_encode($trendWeeks ?? []) ?>;
    const completedSeries = <?= json_encode($trendCompleted ?? []) ?>;
    const assignedSeries = <?= json_encode($trendAssigned ?? []) ?>;
    const overdueSeries = <?= json_encode($trendOverdue ?? []) ?>;

    const deliveryOptions = {
        series: [
            {
                name: 'Assigned',
                type: 'column',
                data: assignedSeries
            },
            {
                name: 'Completed',
                type: 'line',
                data: completedSeries
            },
            {
                name: 'Overdue',
                type: 'line',
                data: overdueSeries
            }
        ],
        chart: {
            height: 250,
            type: 'line',
            toolbar: { show: false },
            fontFamily: 'inherit'
        },
        stroke: {
            width: [0, 3, 3],
            curve: 'smooth',
            dashArray: [0, 0, 4]
        },
        markers: {
            size: [0, 4, 4],
            strokeWidth: 2,
            hover: {
                size: 6
            }
        },
        plotOptions: {
            bar: {
                columnWidth: '35%',
                borderRadius: 4
            }
        },
        colors: ['#bfdbfe', '#16a34a', '#ef4444'],
        dataLabels: { enabled: false },
        xaxis: {
            categories: trendWeeks,
            labels: {
                style: { colors: '#94a3b8', fontSize: '11px' }
            },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: [
            {
                seriesName: 'Assigned',
                min: 0,
                forceNiceScale: true,
                labels: {
                    style: { colors: '#94a3b8', fontSize: '11px' },
                    formatter: function (val) {
                        return Math.round(val);
                    }
                },
                title: {
                    text: 'Assigned / Completed',
                    style: { color: '#94a3b8', fontSize: '10.5px', fontWeight: 500 }
                }
            },
            {
                seriesName: 'Completed',
                show: false
            },
            {
                seriesName: 'Overdue',
                opposite: true,
                min: 0,
                forceNiceScale: true,
                labels: {
                    style: { colors: '#ef4444', fontSize: '11px' },
                    formatter: function (val) {
                        return Math.round(val);
                    }
                },
                title: {
                    text: 'Overdue',
                    style: { color: '#ef4444', fontSize: '10.5px', fontWeight: 500 }
                }
            }
        ],
        tooltip: {
            shared: true,
            intersect: false,
            y: {
                formatter: function (val) {
                    return val + ' tasks';
                }
            }
        },
        grid: {
            borderColor: '#f1f5f9',
            strokeDashArray: 4
        },
        legend: { show: false }
    };

    const deliveryChart = new ApexCharts(document.querySelector("#teamDeliveryChart"), deliveryOptions);
    deliveryChart.render();
});

function changeDeliveryTime(event, period) {
    document.querySelectorAll('.perf-time-pill').forEach(el => el.classList.remove('active'));
    event.target.classList.add('active');
}

let currentMemberEmpId = 0;
let currentMemberEmpName = '';

let currentWbMember = null;
let currentWbTasks = [];
let currentWbFilter = 'open';

function openWorkloadFormulaInfo() {
    alert("Workload Balance Calculation Formula:\n\n1. In Progress Tasks: Status is pending/in-progress with <100% progress and deadline has not passed.\n2. Overdue Tasks: Deadline has passed and task is <100% progress.\n\nTotal Open Tasks = In Progress + Overdue.\n\nCompleted tasks (100%) are excluded from the workload count.");
}

function openWorkloadBreakdown(wb) {
    if (!wb) return;
    currentWbMember = wb;
    currentMemberEmpId = wb.employee_id;
    currentMemberEmpName = wb.full_name || wb.name;

    document.getElementById('wbModalAvatar').textContent = wb.initials || 'TM';
    document.getElementById('wbModalMemberName').textContent = wb.full_name || wb.name;
    document.getElementById('wbModalDesignation').textContent = wb.designation || 'Team Member';
    document.getElementById('wbModalInProgress').textContent = wb.in_progress || 0;
    document.getElementById('wbModalOverdue').textContent = wb.overdue || 0;
    document.getElementById('wbModalTotalOpen').textContent = wb.open_tasks || 0;
    document.getElementById('wbModalCompleted').textContent = wb.completed || 0;
    document.getElementById('wbModalTotal').textContent = wb.total || 0;

    const modalEl = document.getElementById('modalWorkloadBreakdown');
    const bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    bsModal.show();

    const loading = document.getElementById('wbModalLoading');
    const list = document.getElementById('wbModalTasksList');
    loading.style.display = 'block';
    list.style.display = 'none';

    fetch('<?= $GLOBALS['base_url'] ?>/team-performance-v2/member/' + wb.employee_id + '<?= $projectFilter > 0 ? "?project_id=" . $projectFilter : "" ?>')
        .then(res => res.json())
        .then(data => {
            loading.style.display = 'none';
            list.style.display = 'block';

            if (data.success && data.tasks) {
                currentWbTasks = data.tasks;
                renderWbModalTasks();
            } else {
                list.innerHTML = '<div class="text-center py-4 text-muted small">No task details found for this member.</div>';
            }
        })
        .catch(err => {
            loading.style.display = 'none';
            list.style.display = 'block';
            list.innerHTML = '<div class="alert alert-danger small mb-0">Failed to load member tasks.</div>';
        });
}

function filterWbModalTasks(filterType) {
    currentWbFilter = filterType;
    document.getElementById('wbFilterBtnOpen').classList.toggle('active', filterType === 'open');
    document.getElementById('wbFilterBtnAll').classList.toggle('active', filterType === 'all');
    renderWbModalTasks();
}

function renderWbModalTasks() {
    const list = document.getElementById('wbModalTasksList');
    if (!list) return;

    const todayStr = new Date().toISOString().split('T')[0];
    let openCount = 0;
    let allCount = currentWbTasks.length;

    const taskItems = currentWbTasks.map(t => {
        const isComp = (t.status === 'completed' || t.progress >= 100);
        const isOverdue = !isComp && t.due_date_raw && t.due_date_raw < todayStr;
        const isOpen = !isComp;
        if (isOpen) openCount++;

        return { ...t, isComp, isOverdue, isOpen };
    });

    document.getElementById('wbTabOpenCount').textContent = openCount;
    document.getElementById('wbTabAllCount').textContent = allCount;

    const filtered = taskItems.filter(t => currentWbFilter === 'all' ? true : t.isOpen);

    if (filtered.length === 0) {
        list.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="bi bi-check-circle text-success fs-2 opacity-50 mb-1 d-block"></i>
                <div class="fw-semibold">No ${currentWbFilter === 'open' ? 'open' : ''} tasks found</div>
                <div class="small text-muted">${currentWbFilter === 'open' ? 'All assigned tasks for this member have been completed!' : 'No tasks recorded.'}</div>
            </div>
        `;
        return;
    }

    let html = '<div class="list-group list-group-flush">';
    filtered.forEach(t => {
        const isTeam = t.is_team_project == 1;
        const taskJson = JSON.stringify(t).replace(/"/g, '&quot;');

        let statusBadge = '';
        if (t.isComp) {
            statusBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5" style="font-size: 10.5px;"><i class="bi bi-check-circle-fill me-1"></i>Completed</span>';
        } else if (t.isOverdue) {
            statusBadge = '<span class="badge bg-danger text-white px-2 py-0.5" style="font-size: 10.5px;"><i class="bi bi-exclamation-triangle-fill me-1"></i>Overdue</span>';
        } else {
            statusBadge = '<span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-0.5" style="font-size: 10.5px;"><i class="bi bi-hourglass-split me-1"></i>In Progress</span>';
        }

        const prioBadge = t.priority === 1 ? '<span class="badge bg-danger-subtle text-danger py-0 px-1.5" style="font-size: 10px;">Urgent</span>' :
                         (t.priority === 2 ? '<span class="badge bg-warning-subtle text-dark py-0 px-1.5" style="font-size: 10px;">High</span>' : '');

        html += `
            <div class="list-group-item px-2 py-2.5 border-bottom rounded mb-1" style="background: ${t.isOverdue ? '#fff5f5' : '#ffffff'};">
                <div class="d-flex align-items-center justify-content-between mb-1 gap-2">
                    <div class="d-flex align-items-center gap-1.5 flex-grow-1 text-truncate">
                        ${prioBadge}
                        <span class="fw-bold text-dark text-truncate" style="font-size: 13.5px; ${t.isComp ? 'text-decoration: line-through; opacity: 0.7;' : ''}">${t.title}</span>
                    </div>
                    <div class="d-flex align-items-center gap-1 flex-shrink-0">
                        ${statusBadge}
                        <button type="button" class="btn btn-sm btn-light border py-0 px-2 rounded-pill ms-1 text-success fw-semibold" style="font-size: 11px;" onclick="editMemberTaskProgress(${taskJson})" title="Update task progress">
                            <i class="bi bi-sliders me-1"></i> Update
                        </button>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between text-muted" style="font-size: 11.5px;">
                    <span>
                        ${isTeam ? '<span class="badge bg-success-subtle text-success py-0 px-1 me-1"><i class="bi bi-people-fill me-1"></i>Team</span>' : '<i class="bi bi-folder2 text-secondary me-1"></i>'}
                        ${t.project_name}
                    </span>
                    <span class="${t.isOverdue ? 'text-danger fw-bold' : ''}">
                        <i class="bi bi-calendar3 me-1"></i> Due: ${t.due_date}
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2 mt-1.5">
                    <div class="progress flex-grow-1" style="height: 6px; background: #e2e8f0; border-radius: 4px; cursor: pointer;" onclick="editMemberTaskProgress(${taskJson})" title="Click to update progress">
                        <div class="progress-bar ${t.isComp ? 'bg-success' : (t.isOverdue ? 'bg-danger' : (t.progress >= 50 ? 'bg-success' : (t.progress > 0 ? 'bg-warning' : 'bg-secondary')))}" style="width: ${t.progress}%;"></div>
                    </div>
                    <span class="fw-bold text-dark" style="font-size: 11px; min-width: 32px; text-align: right;">${t.progress}%</span>
                </div>
            </div>
        `;
    });
    html += '</div>';
    list.innerHTML = html;
}

function openMemberTasksOffcanvas(empId, empName) {
    currentMemberEmpId = empId;
    currentMemberEmpName = empName;
    document.getElementById('offcanvasMemberTitle').textContent = empName + ' - Active Tasks';
    const loading = document.getElementById('memberTasksLoading');
    const list = document.getElementById('memberTasksList');
    
    loading.style.display = 'block';
    list.style.display = 'none';

    const offcanvasEl = document.getElementById('offcanvasMemberTasks');
    const bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl) || new bootstrap.Offcanvas(offcanvasEl);
    bsOffcanvas.show();

    fetch('<?= $GLOBALS['base_url'] ?>/team-performance-v2/member/' + empId + '<?= $projectFilter > 0 ? "?project_id=" . $projectFilter : "" ?>')
        .then(res => res.json())
        .then(data => {
            loading.style.display = 'none';
            list.style.display = 'block';
            
            if (data.success && data.tasks && data.tasks.length > 0) {
                let html = '<div class="list-group list-group-flush">';
                data.tasks.forEach(t => {
                    const isComp = (t.status === 'completed' || t.progress >= 100);
                    const isTeam = t.is_team_project == 1;
                    const prioColor = t.priority === 1 ? '#ef4444' : (t.priority === 2 ? '#f59e0b' : '#3b82f6');
                    const taskJson = JSON.stringify(t).replace(/"/g, '&quot;');
                    html += `
                        <div class="list-group-item px-0 py-3 border-bottom">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="fw-bold text-dark" style="font-size: 14px; ${isComp ? 'text-decoration: line-through; opacity: 0.75;' : ''}">${t.title}</span>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="badge ${isComp ? 'bg-success-subtle text-success' : 'bg-light text-secondary border'}" style="font-size: 11px;">
                                        ${isComp ? 'Completed' : 'In Progress'}
                                    </span>
                                    <span class="badge ${isComp ? 'bg-success text-white' : (t.progress > 0 ? 'bg-warning-subtle text-dark border' : 'bg-light text-secondary border')}" style="font-size: 11px; min-width: 44px; text-align: center;">
                                        ${t.progress}%
                                    </span>
                                    <button type="button" class="btn btn-sm btn-light border py-0 px-2 rounded-pill ms-1 text-success fw-semibold" style="font-size: 11px;" onclick="editMemberTaskProgress(${taskJson})" title="Update task progress">
                                        <i class="bi bi-sliders me-1"></i> Update
                                    </button>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between text-muted small">
                                <span>
                                    ${isTeam ? '<span class="badge bg-success-subtle text-success py-0 px-1 me-1"><i class="bi bi-people-fill me-1"></i>Team</span>' : '<i class="bi bi-folder2 text-secondary me-1"></i>'}
                                    ${t.project_name}
                                </span>
                                <span><i class="bi bi-calendar3 me-1"></i> ${t.due_date}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 mt-2">
                                <div class="progress flex-grow-1" style="height: 7px; background: #e2e8f0; border-radius: 4px; cursor: pointer;" onclick="editMemberTaskProgress(${taskJson})" title="Click to update progress">
                                    <div class="progress-bar ${isComp ? 'bg-success' : (t.progress >= 50 ? 'bg-success' : (t.progress > 0 ? 'bg-warning' : 'bg-secondary'))}" style="width: ${t.progress}%;"></div>
                                </div>
                                <span class="fw-bold text-dark" style="font-size: 11.5px; min-width: 34px; text-align: right;">${t.progress}%</span>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                list.innerHTML = html;
            } else {
                list.innerHTML = `
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-clipboard-check text-success fs-1 opacity-50 mb-2"></i>
                        <p class="mb-0 fw-semibold">No tasks found for this member</p>
                        <p class="small text-muted">Click "+ Assign Task" above to assign items.</p>
                    </div>
                `;
            }
        })
        .catch(err => {
            loading.style.display = 'none';
            list.style.display = 'block';
            list.innerHTML = '<div class="alert alert-danger small">Failed to load member tasks.</div>';
        });
}

function editMemberTaskProgress(task) {
    if (!task) return;
    document.getElementById('memberUpdateTaskId').value = task.id;
    document.getElementById('memberUpdateTaskTitle').value = task.title || '';
    document.getElementById('memberUpdateDueDate').value = task.due_date_raw || '';
    
    const progressVal = parseInt(task.progress) || 0;
    const progressRange = document.getElementById('memberUpdateProgress');
    const progressDisplay = document.getElementById('memberProgressDisplay');
    const statusSelect = document.getElementById('memberUpdateStatus');
    
    progressRange.value = progressVal;
    progressDisplay.textContent = progressVal + '%';
    
    if (task.status === 'completed' || progressVal >= 100) {
        statusSelect.value = 'completed';
    } else {
        statusSelect.value = 'pending';
    }
    
    const feedback = document.getElementById('memberUpdateFeedback');
    feedback.style.display = 'none';
    feedback.innerHTML = '';
    
    const submitBtn = document.getElementById('btnSaveMemberTask');
    submitBtn.disabled = false;
    submitBtn.innerHTML = 'Save Update';

    const modalEl = document.getElementById('modalUpdateMemberTask');
    const bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    bsModal.show();
}

document.addEventListener("DOMContentLoaded", function () {
    const progressRange = document.getElementById('memberUpdateProgress');
    const progressDisplay = document.getElementById('memberProgressDisplay');
    const statusSelect = document.getElementById('memberUpdateStatus');

    if (progressRange && progressDisplay && statusSelect) {
        progressRange.addEventListener('input', function() {
            const val = parseInt(this.value) || 0;
            progressDisplay.textContent = val + '%';
            if (val >= 100) {
                statusSelect.value = 'completed';
            } else if (statusSelect.value === 'completed') {
                statusSelect.value = 'pending';
            }
        });

        statusSelect.addEventListener('change', function() {
            if (this.value === 'completed') {
                progressRange.value = 100;
                progressDisplay.textContent = '100%';
            } else {
                if (parseInt(progressRange.value) >= 100) {
                    progressRange.value = 0;
                    progressDisplay.textContent = '0%';
                }
            }
        });
    }

    const updateForm = document.getElementById('formUpdateMemberTask');
    if (updateForm) {
        updateForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('btnSaveMemberTask');
            const feedback = document.getElementById('memberUpdateFeedback');
            const originalText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Saving...';

            const formData = new FormData(updateForm);
            formData.append('ajax', '1');

            fetch(updateForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    submitBtn.innerHTML = '<i class="bi bi-check2 me-1"></i> Saved!';
                    feedback.style.display = 'block';
                    feedback.innerHTML = '<div class="alert alert-success py-2 px-3 small rounded-3 mb-0">' + (data.message || 'Task updated successfully!') + '</div>';
                    setTimeout(() => {
                        const modalEl = document.getElementById('modalUpdateMemberTask');
                        const bsModal = bootstrap.Modal.getInstance(modalEl);
                        if (bsModal) bsModal.hide();
                        // Refresh the offcanvas task list immediately
                        if (currentMemberEmpId > 0) {
                            openMemberTasksOffcanvas(currentMemberEmpId, currentMemberEmpName);
                        }
                    }, 500);
                } else {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    feedback.style.display = 'block';
                    feedback.innerHTML = '<div class="alert alert-danger py-2 px-3 small rounded-3 mb-0">' + (data.message || 'Failed to update task') + '</div>';
                }
            })
            .catch(err => {
                console.error("AJAX update failed:", err);
                updateForm.submit();
            });
        });
    }

    const formCreateProject = document.getElementById('formCreateTeamProject');
    if (formCreateProject) {
        formCreateProject.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('btnSaveTeamProject');
            const feedback = document.getElementById('createTeamProjectFeedback');
            const originalText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Creating...';

            const formData = new FormData(formCreateProject);

            fetch(formCreateProject.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.project) {
                    submitBtn.innerHTML = '<i class="bi bi-check2 me-1"></i> Created!';
                    feedback.style.display = 'block';
                    feedback.innerHTML = '<div class="alert alert-success py-2 px-3 small rounded-3 mb-0">Team Project created successfully!</div>';
                    
                    const assignSelect = document.getElementById('assignTaskProjectId');
                    if (assignSelect) {
                        const opt = document.createElement('option');
                        opt.value = data.project.id;
                        opt.textContent = '👥 ' + data.project.name;
                        opt.selected = true;
                        assignSelect.appendChild(opt);
                    }

                    setTimeout(() => {
                        const modalEl = document.getElementById('modalCreateTeamProject');
                        const bsModal = bootstrap.Modal.getInstance(modalEl);
                        if (bsModal) bsModal.hide();
                        window.location.reload();
                    }, 800);
                } else {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    feedback.style.display = 'block';
                    feedback.innerHTML = '<div class="alert alert-danger py-2 px-3 small rounded-3 mb-0">' + (data.message || 'Failed to create team project') + '</div>';
                }
            })
            .catch(err => {
                console.error("Project creation failed:", err);
                formCreateProject.submit();
            });
        });
    }
});

function toggleAssignNewProject(val) {
    const wrap = document.getElementById('assignNewProjectWrap');
    if (wrap) {
        wrap.style.display = (val === 'new') ? 'block' : 'none';
        if (val === 'new') {
            const input = document.getElementById('assign_new_project_name');
            if (input) input.focus();
        }
    }
}
</script>

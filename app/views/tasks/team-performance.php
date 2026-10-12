<style>
/* ==========================================================================
   HOD & Team Performance Dashboard Styles (Leadership Workspace)
   ========================================================================== */
.tphl-team-container {
    max-width: 1600px;
    margin: 0 auto;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    color: #1e293b;
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
    margin-bottom: 24px;
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
    gap: 16px;
    margin-bottom: 24px;
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
    gap: 20px;
    margin-bottom: 24px;
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
    margin-bottom: 12px;
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

<div class="tphl-team-container py-4 px-md-4">
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
                <a href="<?= $GLOBALS['base_url'] ?>/my-created-tasks" class="team-mode-pill">My Performance</a>
                <a href="<?= $GLOBALS['base_url'] ?>/team-performance" class="team-mode-pill active">My Team</a>
            </div>

            <!-- Export Button -->
            <a href="<?= $GLOBALS['base_url'] ?>/team-performance?export=csv&<?= http_build_query($_GET) ?>" class="team-btn-export">
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
        <form action="<?= $GLOBALS['base_url'] ?>/team-performance" method="GET" class="d-flex align-items-center flex-wrap gap-2">
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
                            <?= htmlspecialchars($proj['name']) ?>
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
            <a href="<?= $GLOBALS['base_url'] ?>/team-performance" class="btn btn-link text-decoration-none text-success small fw-semibold px-2 ms-auto">Reset</a>
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
            <div class="team-kpi-sub text-success">+<?= $kpi['tasks_this_month'] ?> tasks this month</div>
        </div>

        <!-- 2. Completed -->
        <div class="team-kpi-card kpi-green">
            <div class="team-kpi-header">
                <span class="team-kpi-title">COMPLETED</span>
                <span class="team-kpi-icon bg-success-subtle text-success"><i class="bi bi-check2"></i></span>
            </div>
            <div class="team-kpi-val"><?= str_pad($kpi['completed'], 2, '0', STR_PAD_LEFT) ?></div>
            <div class="team-kpi-sub text-success"><?= $kpi['completion_rate'] ?>% completion rate</div>
        </div>

        <!-- 3. In Progress -->
        <div class="team-kpi-card kpi-amber">
            <div class="team-kpi-header">
                <span class="team-kpi-title">IN PROGRESS</span>
                <span class="team-kpi-icon bg-warning-subtle text-warning"><i class="bi bi-clock"></i></span>
            </div>
            <div class="team-kpi-val"><?= str_pad($kpi['in_progress'], 2, '0', STR_PAD_LEFT) ?></div>
            <div class="team-kpi-sub text-warning"><?= $kpi['due_this_week'] ?> due this week</div>
        </div>

        <!-- 4. Overdue -->
        <div class="team-kpi-card kpi-red">
            <div class="team-kpi-header">
                <span class="team-kpi-title">OVERDUE</span>
                <span class="team-kpi-icon bg-danger-subtle text-danger"><i class="bi bi-exclamation"></i></span>
            </div>
            <div class="team-kpi-val"><?= str_pad($kpi['overdue'], 2, '0', STR_PAD_LEFT) ?></div>
            <div class="team-kpi-sub text-danger"><?= $kpi['overdue_pct'] ?>% of team tasks</div>
        </div>

        <!-- 5. On-Time Rate -->
        <div class="team-kpi-card kpi-purple">
            <div class="team-kpi-header">
                <span class="team-kpi-title">ON TIME RATE</span>
                <span class="team-kpi-icon bg-primary-subtle text-primary"><i class="bi bi-shield-check"></i></span>
            </div>
            <div class="team-kpi-val"><?= $kpi['on_time_rate'] ?>%</div>
            <div class="team-kpi-sub text-primary">+4.2% vs last month</div>
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
                <h5 class="team-chart-title">Workload Balance</h5>
                <p class="team-chart-subtitle mb-4">Open tasks per team member</p>

                <!-- Workload List -->
                <div class="workload-list">
                    <?php if (!empty($workloadBars)): ?>
                        <?php foreach ($workloadBars as $wb): ?>
                            <div class="workload-item">
                                <div class="workload-avatar"><?= $wb['initials'] ?></div>
                                <div class="workload-name" title="<?= htmlspecialchars($wb['name']) ?>"><?= htmlspecialchars($wb['name']) ?></div>
                                <div class="workload-bar-wrap">
                                    <div class="workload-bar-fill" style="width: <?= $wb['bar_pct'] ?>%;"></div>
                                </div>
                                <div class="workload-count"><?= str_pad($wb['open_tasks'], 2, '0', STR_PAD_LEFT) ?></div>
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
                    <i class="bi bi-plus-lg"></i>
                </div>
                <div>
                    <div class="fw-bold text-dark" style="font-size: 12.5px;">Workload looks balanced</div>
                    <div class="text-muted" style="font-size: 11.5px;"><?= htmlspecialchars($highestWorkloadMember) ?> has the highest active workload</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Section: Team Members Performance Table (Matching Screenshot) -->
    <div class="team-table-card">
        <!-- Table Header Tabs & Action Buttons -->
        <div class="team-table-header">
            <div class="team-tabs">
                <a href="<?= $GLOBALS['base_url'] ?>/team-performance?tab=all&<?= http_build_query(array_diff_key($_GET, ['tab' => ''])) ?>" class="team-tab-item <?= $tab === 'all' ? 'active' : '' ?>">
                    All Members <span class="team-tab-pill"><?= $allMembersCount ?></span>
                </a>
                <a href="<?= $GLOBALS['base_url'] ?>/team-performance?tab=top_performers&<?= http_build_query(array_diff_key($_GET, ['tab' => ''])) ?>" class="team-tab-item <?= $tab === 'top_performers' ? 'active' : '' ?>">
                    Top Performers <span class="team-tab-pill"><?= $topPerformersCount ?></span>
                </a>
                <a href="<?= $GLOBALS['base_url'] ?>/team-performance?tab=needs_attention&<?= http_build_query(array_diff_key($_GET, ['tab' => ''])) ?>" class="team-tab-item <?= $tab === 'needs_attention' ? 'active' : '' ?>">
                    Needs Attention <span class="team-tab-pill"><?= $needsAttentionCount ?></span>
                </a>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a href="<?= $GLOBALS['base_url'] ?>/team-performance?sort=<?= $sort === 'score_desc' ? 'score_asc' : 'score_desc' ?>&<?= http_build_query(array_diff_key($_GET, ['sort' => ''])) ?>" class="btn btn-sm btn-light border rounded-pill px-3 fw-semibold text-secondary" style="font-size: 12.5px;">
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
            <form action="<?= $GLOBALS['base_url'] ?>/team-performance/assign-task" method="POST">
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
                        <label class="form-label small fw-bold text-secondary">Project</label>
                        <select name="project_id" class="form-select rounded-3">
                            <option value="">No Project (Inbox)</option>
                            <?php foreach ($projects as $pr): ?>
                                <option value="<?= $pr['id'] ?>"><?= htmlspecialchars($pr['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
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
            width: [0, 3, 2],
            curve: 'smooth',
            dashArray: [0, 0, 5]
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
        yaxis: {
            labels: {
                style: { colors: '#94a3b8', fontSize: '11px' }
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

function openMemberTasksOffcanvas(empId, empName) {
    document.getElementById('offcanvasMemberTitle').textContent = empName + ' - Active Tasks';
    const loading = document.getElementById('memberTasksLoading');
    const list = document.getElementById('memberTasksList');
    
    loading.style.display = 'block';
    list.style.display = 'none';

    const offcanvasEl = document.getElementById('offcanvasMemberTasks');
    const bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl) || new bootstrap.Offcanvas(offcanvasEl);
    bsOffcanvas.show();

    fetch('<?= $GLOBALS['base_url'] ?>/team-performance/member/' + empId)
        .then(res => res.json())
        .then(data => {
            loading.style.display = 'none';
            list.style.display = 'block';
            
            if (data.success && data.tasks && data.tasks.length > 0) {
                let html = '<div class="list-group list-group-flush">';
                data.tasks.forEach(t => {
                    const isComp = (t.status === 'completed' || t.progress >= 100);
                    const prioColor = t.priority === 1 ? '#ef4444' : (t.priority === 2 ? '#f59e0b' : '#3b82f6');
                    html += `
                        <div class="list-group-item px-0 py-3 border-bottom">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="fw-bold text-dark" style="font-size: 14px; ${isComp ? 'text-decoration: line-through; opacity: 0.75;' : ''}">${t.title}</span>
                                <span class="badge ${isComp ? 'bg-success-subtle text-success' : 'bg-light text-secondary'}" style="font-size: 11px;">
                                    ${isComp ? 'Completed' : 'In Progress'}
                                </span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between text-muted small">
                                <span><i class="bi bi-folder2 text-secondary me-1"></i> ${t.project_name}</span>
                                <span><i class="bi bi-calendar3 me-1"></i> ${t.due_date}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 mt-2">
                                <div class="progress flex-grow-1" style="height: 5px; background: #e2e8f0; border-radius: 4px;">
                                    <div class="progress-bar bg-success" style="width: ${t.progress}%;"></div>
                                </div>
                                <span class="fw-bold text-muted" style="font-size: 11px;">${t.progress}%</span>
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
</script>

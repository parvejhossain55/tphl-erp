<style>
/* ==========================================================================
   Personal Task & Performance Dashboard - Todo Lists Integration
   ========================================================================== */
.tphl-perf-container {
    background-color: #f8fafc;
    min-height: 100vh;
    padding-bottom: 40px;
}

/* Header */
.perf-header-tag {
    font-size: 11px;
    letter-spacing: 0.12em;
    font-weight: 800;
    color: #15803d;
    text-transform: uppercase;
}
.perf-header-title {
    font-size: 26px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
}

/* Action Buttons */
.perf-btn-export {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    color: #334155;
    font-size: 13px;
    font-weight: 600;
    padding: 8px 18px;
    border-radius: 24px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    transition: all 0.2s;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
}
.perf-btn-export:hover {
    background: #f8fafc;
    color: #0f172a;
    border-color: #cbd5e1;
}

.perf-btn-add {
    background-color: #15803d;
    border: 1px solid #15803d;
    color: #ffffff;
    font-size: 13px;
    font-weight: 600;
    padding: 8px 20px;
    border-radius: 24px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 2px 6px rgba(21, 128, 61, 0.25);
}
.perf-btn-add:hover {
    background-color: #166534;
    border-color: #166534;
    color: #ffffff;
}

/* Filter Bar */
.perf-filter-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 10px 18px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    margin-bottom: 22px;
}
.perf-search-input {
    border: none;
    background: transparent;
    font-size: 13.5px;
    color: #334155;
    outline: none;
    width: 100%;
}
.perf-search-input::placeholder { color: #94a3b8; }

.perf-select-pill {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 6px 14px;
    font-size: 12.5px;
    font-weight: 500;
    color: #334155;
    cursor: pointer;
    outline: none;
    transition: all 0.2s;
}
.perf-select-pill:hover, .perf-select-pill:focus {
    background-color: #ffffff;
    border-color: #16a34a;
}

/* KPI Cards Grid */
.perf-kpi-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 14px;
    margin-bottom: 22px;
}
@media (max-width: 1200px) {
    .perf-kpi-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 640px) {
    .perf-kpi-grid { grid-template-columns: repeat(2, 1fr); }
}

.perf-kpi-card {
    background: #ffffff;
    border: 1px solid #edf2f7;
    border-radius: 14px;
    padding: 16px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    transition: transform 0.2s, box-shadow 0.2s;
}
.perf-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.05);
}
.perf-kpi-blob {
    position: absolute;
    width: 76px;
    height: 76px;
    border-radius: 50%;
    bottom: -24px;
    right: -24px;
    pointer-events: none;
}
.perf-kpi-label {
    font-size: 10.5px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 6px;
}
.perf-kpi-val {
    font-size: 28px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.1;
    margin-bottom: 6px;
}
.perf-kpi-sub {
    font-size: 11px;
    font-weight: 600;
    margin-bottom: 0;
    position: relative;
    z-index: 2;
}
.perf-kpi-icon {
    position: absolute;
    top: 14px;
    right: 14px;
    width: 26px;
    height: 26px;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    z-index: 2;
}

/* Charts Row */
.perf-chart-row {
    display: grid;
    grid-template-columns: 1.8fr 1.2fr;
    gap: 16px;
    margin-bottom: 22px;
}
@media (max-width: 991px) {
    .perf-chart-row { grid-template-columns: 1fr; }
}

.perf-card-box {
    background: #ffffff;
    border: 1px solid #edf2f7;
    border-radius: 16px;
    padding: 20px 22px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}
.perf-card-title {
    font-size: 16px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 2px;
}
.perf-card-subtitle {
    font-size: 12px;
    color: #64748b;
    margin-bottom: 0;
}

/* Time Filter Pill */
.perf-time-pills {
    display: inline-flex;
    background: #f1f5f9;
    border-radius: 8px;
    padding: 3px;
}
.perf-time-pill {
    border: none;
    background: transparent;
    padding: 3px 11px;
    font-size: 11.5px;
    font-weight: 600;
    color: #64748b;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}
.perf-time-pill.active {
    background: #ffffff;
    color: #0f172a;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

/* Donut Legend */
.donut-legend-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 12.5px;
    color: #334155;
    padding: 6px 0;
}
.donut-legend-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 8px;
}

/* Table Section Tabs */
.perf-tabs-wrap {
    display: flex;
    align-items: center;
    margin-bottom: 0;
    overflow-x: auto;
    gap: 8px;
}
.perf-tab-item {
    padding: 12px 14px;
    font-size: 13.5px;
    font-weight: 600;
    color: #64748b;
    text-decoration: none;
    border-bottom: 2px solid transparent;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
    transition: all 0.2s;
}
.perf-tab-item:hover {
    color: #0f172a;
}
.perf-tab-item.active {
    color: #15803d;
    border-bottom-color: #15803d;
}
.perf-tab-badge {
    font-size: 11px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 12px;
    background-color: #f1f5f9;
    color: #475569;
}
.perf-tab-item.active .perf-tab-badge {
    background-color: #dcfce7;
    color: #15803d;
}

/* Table Styling */
.perf-table-wrap {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}
.perf-table {
    width: 100%;
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
}
.perf-table th {
    background-color: #fafbfc;
    color: #64748b;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 12px 18px;
    border-bottom: 1px solid #edf2f7;
    border-top: none;
    white-space: nowrap;
}
.perf-table td {
    padding: 14px 18px;
    vertical-align: middle;
    font-size: 13.5px;
    border-bottom: 1px solid #f1f5f9;
    color: #1e293b;
}
.perf-table tbody tr:hover td {
    background-color: #fbfcfe;
}
.perf-table tbody tr:last-child td {
    border-bottom: none;
}

/* Source Badges */
.badge-source-assigned {
    background-color: #f0fdf4;
    color: #15803d;
    border: 1px solid #bbf7d0;
    font-size: 11.5px;
    font-weight: 600;
    padding: 3px 12px;
    border-radius: 14px;
    display: inline-block;
}
.badge-source-self {
    background-color: #faf5ff;
    color: #7e22ce;
    border: 1px solid #e9d5ff;
    font-size: 11.5px;
    font-weight: 600;
    padding: 3px 12px;
    border-radius: 14px;
    display: inline-block;
}

/* Priority Badges */
.badge-priority-urgent {
    background-color: #fef2f2;
    color: #b91c1c;
    font-size: 11.5px;
    font-weight: 600;
    padding: 3px 12px;
    border-radius: 14px;
    border: 1px solid #fecaca;
    display: inline-block;
}
.badge-priority-high {
    background-color: #fff7ed;
    color: #c2410c;
    font-size: 11.5px;
    font-weight: 600;
    padding: 3px 12px;
    border-radius: 14px;
    border: 1px solid #ffedd5;
    display: inline-block;
}
.badge-priority-medium {
    background-color: #eff6ff;
    color: #2563eb;
    font-size: 11.5px;
    font-weight: 600;
    padding: 3px 12px;
    border-radius: 14px;
    border: 1px solid #bfdbfe;
    display: inline-block;
}
.badge-priority-low {
    background-color: #f8fafc;
    color: #475569;
    font-size: 11.5px;
    font-weight: 600;
    padding: 3px 12px;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    display: inline-block;
}

/* Assigned By Badge */
.avatar-badge-initials {
    min-width: 28px;
    height: 24px;
    padding: 0 5px;
    border-radius: 6px;
    background-color: #f1f5f9;
    color: #0f172a;
    font-size: 10.5px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-right: 8px;
    letter-spacing: 0.02em;
    border: 1px solid #e2e8f0;
}
.avatar-badge-img {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    object-fit: cover;
    margin-right: 8px;
}

.status-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 6px;
}

.hover-green:hover {
    color: #15803d !important;
}

/* Offcanvas Styling matching mockup */
.task-offcanvas {
    width: 680px !important;
    max-width: 95vw;
    border-left: 1px solid #e2e8f0;
    box-shadow: -10px 0 30px rgba(0, 0, 0, 0.08);
}
.task-offcanvas-tag {
    font-size: 11px;
    font-weight: 800;
    color: #15803d;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.task-offcanvas-title {
    font-size: 22px;
    font-weight: 800;
    color: #0f172a;
}
.task-offcanvas-label {
    font-size: 11px;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 6px;
    display: block;
}
.form-field-label {
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 6px;
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.advanced-settings-panel {
    background: linear-gradient(145deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 18px 20px;
    margin-top: 14px;
    margin-bottom: 18px;
}
.todo-row-4-col {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    width: 100%;
}
@media (max-width: 768px) {
    .todo-row-4-col {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 480px) {
    .todo-row-4-col {
        grid-template-columns: 1fr;
    }
}
.task-offcanvas-input {
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 14px;
    color: #1e293b;
    background-color: #ffffff;
    transition: all 0.2s;
    width: 100%;
}
.task-offcanvas-input:focus {
    border-color: #16a34a;
    box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.12);
    outline: none;
}
.task-offcanvas-input::placeholder {
    color: #94a3b8;
}
.task-offcanvas-btn-close {
    background-color: #f1f5f9;
    border-radius: 8px;
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    color: #475569;
    font-size: 18px;
    transition: all 0.2s;
}
.task-offcanvas-btn-close:hover {
    background-color: #e2e8f0;
    color: #0f172a;
}
</style>

<div class="tphl-perf-container py-4 px-md-4">
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

    <!-- Top Header -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <div class="perf-header-tag mb-1">EMPLOYEE LEDGER</div>
            <h3 class="perf-header-title mb-1">Personal Task & Performance</h3>
            <p class="text-muted small mb-0">Track your delivery, deadlines and complete task history in one place.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= $GLOBALS['base_url'] ?>/my-created-tasks?export=csv&<?= http_build_query($_GET) ?>" class="perf-btn-export">
                <i class="bi bi-box-arrow-down"></i> Export Report
            </a>
            <button type="button" class="perf-btn-add" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddNewTask">
                <i class="bi bi-plus-lg"></i> Add New Task
            </button>
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="perf-filter-card">
        <form action="<?= $GLOBALS['base_url'] ?>/my-created-tasks" method="GET" class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
            
            <!-- Left Search Input -->
            <div class="d-flex align-items-center flex-grow-1" style="min-width: 260px;">
                <i class="bi bi-search text-muted me-2" style="font-size: 14px;"></i>
                <input type="text" name="q" class="perf-search-input" placeholder="Search task, project or location..." value="<?= htmlspecialchars($searchTerm) ?>" autocomplete="off">
                <?php if (!empty($searchTerm)): ?>
                    <a href="<?= $GLOBALS['base_url'] ?>/my-created-tasks?<?= http_build_query(array_diff_key($_GET, ['q' => '', 'page' => ''])) ?>" class="text-muted me-2 text-decoration-none" title="Clear search">
                        <i class="bi bi-x-circle-fill"></i>
                    </a>
                <?php endif; ?>
                <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 py-1 fs-12 fw-semibold text-white ms-1" style="background-color: #15803d; border-color: #15803d;">
                    Search
                </button>
            </div>

            <!-- Right Select Filters -->
            <div class="d-flex align-items-center flex-wrap gap-2">
                <!-- Month Filter -->
                <select name="month" class="perf-select-pill" onchange="this.form.submit()">
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

                <!-- Project Filter -->
                <select name="project_id" class="perf-select-pill" onchange="this.form.submit()">
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
                <select name="status" class="perf-select-pill" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="expired" <?= ($statusFilter === 'expired' || $statusFilter === 'overdue') ? 'selected' : '' ?>>Overdue</option>
                </select>

                <!-- Priority Filter -->
                <select name="priority" class="perf-select-pill" onchange="this.form.submit()">
                    <option value="">All Priorities</option>
                    <option value="1" <?= $priorityFilter === '1' ? 'selected' : '' ?>>Urgent</option>
                    <option value="2" <?= $priorityFilter === '2' ? 'selected' : '' ?>>High</option>
                    <option value="3" <?= $priorityFilter === '3' ? 'selected' : '' ?>>Medium</option>
                    <option value="4" <?= $priorityFilter === '4' ? 'selected' : '' ?>>Low</option>
                </select>

                <!-- Per Page Filter (20 / 50 / 100 / All) -->
                <select name="per_page" class="perf-select-pill" onchange="this.form.submit()">
                    <option value="20" <?= ($perPageParam ?? '20') == '20' ? 'selected' : '' ?>>20 per page</option>
                    <option value="50" <?= ($perPageParam ?? '') == '50' ? 'selected' : '' ?>>50 per page</option>
                    <option value="100" <?= ($perPageParam ?? '') == '100' ? 'selected' : '' ?>>100 per page</option>
                    <option value="all" <?= ($perPageParam ?? '') === 'all' ? 'selected' : '' ?>>Show All</option>
                </select>

                <!-- Reset Link -->
                <a href="<?= $GLOBALS['base_url'] ?>/my-created-tasks" class="btn btn-link text-decoration-none text-success small fw-semibold px-2">Reset</a>
            </div>
        </form>
    </div>

    <!-- 6 KPI Stat Cards Grid -->
    <div class="perf-kpi-grid">
        <!-- 1. Total Tasks -->
        <div class="perf-kpi-card">
            <div class="perf-kpi-blob" style="background: #dcfce7; opacity: 0.5;"></div>
            <div class="perf-kpi-icon" style="background-color: #dcfce7; color: #16a34a;">
                <i class="bi bi-grid-fill"></i>
            </div>
            <div class="perf-kpi-label">TOTAL TASKS</div>
            <div class="perf-kpi-val"><?= sprintf("%02d", $totalTasks) ?></div>
            <div class="perf-kpi-sub text-success">+<?= $tasksThisMonth ?> tasks this month</div>
        </div>

        <!-- 2. In Progress -->
        <div class="perf-kpi-card">
            <div class="perf-kpi-blob" style="background: #ffedd5; opacity: 0.5;"></div>
            <div class="perf-kpi-icon" style="background-color: #ffedd5; color: #ea580c;">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="perf-kpi-label">IN PROGRESS</div>
            <div class="perf-kpi-val"><?= sprintf("%02d", $inProgressCount) ?></div>
            <div class="perf-kpi-sub" style="color: #ea580c;"><?= $dueThisWeek ?> tasks due this week</div>
        </div>

        <!-- 3. Completed -->
        <div class="perf-kpi-card">
            <div class="perf-kpi-blob" style="background: #dcfce7; opacity: 0.5;"></div>
            <div class="perf-kpi-icon" style="background-color: #dcfce7; color: #16a34a;">
                <i class="bi bi-check2"></i>
            </div>
            <div class="perf-kpi-label">COMPLETED</div>
            <div class="perf-kpi-val"><?= sprintf("%02d", $completedCount) ?></div>
            <div class="perf-kpi-sub text-success">+<?= round(($completedCount / max(1, $totalTasks)) * 100) ?>% vs total tasks</div>
        </div>

        <!-- 4. Deadline Missed -->
        <div class="perf-kpi-card">
            <div class="perf-kpi-blob" style="background: #fee2e2; opacity: 0.5;"></div>
            <div class="perf-kpi-icon" style="background-color: #fee2e2; color: #dc2626;">
                <i class="bi bi-exclamation"></i>
            </div>
            <div class="perf-kpi-label">DEADLINE MISSED</div>
            <div class="perf-kpi-val"><?= sprintf("%02d", $missedCount) ?></div>
            <div class="perf-kpi-sub text-danger"><?= $missedCount > 0 ? $missedCount . ' fewer than last month' : '0 overdue tasks' ?></div>
        </div>

        <!-- 5. Completion Rate -->
        <div class="perf-kpi-card">
            <div class="perf-kpi-blob" style="background: #ede9fe; opacity: 0.5;"></div>
            <div class="perf-kpi-icon" style="background-color: #f3e8ff; color: #9333ea;">
                <i class="bi bi-lightning-charge-fill"></i>
            </div>
            <div class="perf-kpi-label">COMPLETION RATE</div>
            <div class="perf-kpi-val"><?= $completionRate ?>%</div>
            <div class="perf-kpi-sub" style="color: #9333ea;"><?= $completionRate >= 70 ? 'Good progress this month' : 'Active progress' ?></div>
        </div>

        <!-- 6. On-Time Rate -->
        <div class="perf-kpi-card">
            <div class="perf-kpi-blob" style="background: #ccfbf1; opacity: 0.5;"></div>
            <div class="perf-kpi-icon" style="background-color: #ccfbf1; color: #0d9488;">
                <i class="bi bi-bullseye"></i>
            </div>
            <div class="perf-kpi-label">ON-TIME RATE</div>
            <div class="perf-kpi-val"><?= $onTimeRate ?>%</div>
            <div class="perf-kpi-sub" style="color: #0d9488;">Target 90% • <?= $onTimeRate >= 85 ? 'almost there' : 'on track' ?></div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="perf-chart-row">
        <!-- Left: Performance Trend Area Chart -->
        <div class="perf-card-box">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h5 class="perf-card-title">Performance Trend</h5>
                    <p class="perf-card-subtitle">Tasks completed versus missed deadlines</p>
                </div>
                <div class="perf-time-pills">
                    <button type="button" class="perf-time-pill" onclick="changeTrendTime(event, '7D')">7D</button>
                    <button type="button" class="perf-time-pill active" onclick="changeTrendTime(event, '30D')">30D</button>
                    <button type="button" class="perf-time-pill" onclick="changeTrendTime(event, '3M')">3M</button>
                </div>
            </div>
            <div id="performanceTrendChart" style="min-height: 250px;"></div>
        </div>

        <!-- Right: Task Status Donut Chart -->
        <div class="perf-card-box">
            <div class="mb-3">
                <h5 class="perf-card-title">Task Status</h5>
                <p class="perf-card-subtitle"><?= $totalTasks ?> tasks in selected period</p>
            </div>
            
            <div class="row align-items-center">
                <div class="col-sm-7 position-relative">
                    <div id="taskStatusDonutChart" style="min-height: 220px;"></div>
                    <!-- Center Overlay Rate -->
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; pointer-events: none;">
                        <span style="font-size: 20px; font-weight: 800; color: #0f172a; line-height: 1;"><?= $completionRate ?>%</span>
                        <div style="font-size: 11px; color: #64748b; font-weight: 600;">Completed</div>
                    </div>
                </div>
                <div class="col-sm-5">
                    <div class="donut-legend-item">
                        <span><span class="donut-legend-dot" style="background-color: #16a34a;"></span>Completed</span>
                        <strong class="text-dark"><?= sprintf("%02d", $completedCount) ?></strong>
                    </div>
                    <div class="donut-legend-item">
                        <span><span class="donut-legend-dot" style="background-color: #eab308;"></span>In progress</span>
                        <strong class="text-dark"><?= sprintf("%02d", $inProgressCount) ?></strong>
                    </div>
                    <div class="donut-legend-item">
                        <span><span class="donut-legend-dot" style="background-color: #ef4444;"></span>Overdue</span>
                        <strong class="text-dark"><?= sprintf("%02d", $missedCount) ?></strong>
                    </div>
                    <div class="donut-legend-item">
                        <span><span class="donut-legend-dot" style="background-color: #3b82f6;"></span>Inbox</span>
                        <strong class="text-dark"><?= sprintf("%02d", $inboxTasksCount) ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Table Card Section -->
    <div class="perf-table-wrap">
        <!-- Tabs & Actions Header -->
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 px-3 pt-2 border-bottom">
            <!-- Navigation Tabs -->
            <div class="perf-tabs-wrap">
                <a href="<?= $GLOBALS['base_url'] ?>/my-created-tasks?tab=all" class="perf-tab-item <?= $tab === 'all' ? 'active' : '' ?>">
                    All Tasks <span class="perf-tab-badge"><?= $totalTasks ?></span>
                </a>
                <a href="<?= $GLOBALS['base_url'] ?>/my-created-tasks?tab=in_progress" class="perf-tab-item <?= $tab === 'in_progress' ? 'active' : '' ?>">
                    In Progress <span class="perf-tab-badge"><?= $inProgressCount ?></span>
                </a>
                <a href="<?= $GLOBALS['base_url'] ?>/my-created-tasks?tab=completed" class="perf-tab-item <?= $tab === 'completed' ? 'active' : '' ?>">
                    Completed <span class="perf-tab-badge"><?= $completedCount ?></span>
                </a>
                <a href="<?= $GLOBALS['base_url'] ?>/my-created-tasks?tab=deadline_missed" class="perf-tab-item <?= $tab === 'deadline_missed' ? 'active' : '' ?>">
                    Deadline Missed <span class="perf-tab-badge"><?= $missedCount ?></span>
                </a>
                <a href="<?= $GLOBALS['base_url'] ?>/my-created-tasks?tab=team_tasks" class="perf-tab-item <?= $tab === 'team_tasks' ? 'active' : '' ?>">
                    <i class="bi bi-people-fill text-primary me-1"></i> Team Tasks <span class="perf-tab-badge"><?= $teamTasksCount ?? 0 ?></span>
                </a>
            </div>

            <!-- Right Controls -->
            <div class="d-flex align-items-center gap-2 pb-2">
                <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 py-1 fs-12 fw-medium text-secondary">
                    <i class="bi bi-arrow-down-up me-1"></i> Sort
                </button>
                <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 py-1 fs-12 fw-medium text-secondary">
                    <i class="bi bi-layout-three-columns me-1"></i> Columns
                </button>
            </div>
        </div>

        <?php
        $tasksMap = [];
        if (!empty($tasks)) {
            foreach ($tasks as $rowItem) {
                $pVal = (int)($rowItem['progress'] ?? 0);
                $isCompItem = ($rowItem['status'] === 'completed' || $pVal >= 100);
                $tasksMap[$rowItem['id']] = [
                    'id' => (int)$rowItem['id'],
                    'title' => (string)$rowItem['title'],
                    'description' => (string)($rowItem['description'] ?? ''),
                    'project_name' => !empty($rowItem['project_name']) ? $rowItem['project_name'] : 'Inbox',
                    'status' => $isCompItem ? 'Completed' : 'In Progress',
                    'status_raw' => $isCompItem ? 'completed' : 'pending',
                    'progress' => $pVal,
                    'due_date' => !empty($rowItem['due_date']) ? date('Y-m-d', strtotime($rowItem['due_date'])) : '',
                    'due_date_formatted' => !empty($rowItem['due_date']) ? date('d M Y', strtotime($rowItem['due_date'])) : 'No Deadline'
                ];
            }
        }
        ?>
        <script>
        window.allTasksData = <?= json_encode($tasksMap, JSON_UNESCAPED_UNICODE) ?> || {};
        </script>

        <!-- Table View -->
        <div class="table-responsive">
            <table class="perf-table">
                <thead>
                    <tr>
                        <th style="min-width: 280px;">TASK & PROJECT</th>
                        <th>SOURCE</th>
                        <th>PRIORITY</th>
                        <th>ASSIGNED BY</th>
                        <th>DEADLINE</th>
                        <th>STATUS</th>
                        <th style="min-width: 140px;">PROGRESS</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tasks)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="mb-2 text-success" style="font-size: 40px; opacity: 0.6;">
                                    <i class="bi bi-clipboard-check"></i>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">No tasks in this view</h6>
                                <p class="small text-muted mb-0">Click "+ Add New Task" or adjust filters to view your items.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tasks as $t): 
                            $progressPct = (int)($t['progress'] ?? 0);
                            $isCompleted = ($t['status'] === 'completed' || $progressPct >= 100);
                            if ($isCompleted && $progressPct < 100) {
                                $progressPct = 100;
                            }
                            $isOverdue = (!empty($t['due_date']) && strtotime($t['due_date']) < time() && !$isCompleted);
                            
                            $pNum = (int)($t['priority'] ?? 4);
                            if ($pNum === 1) {
                                $prioName = 'Urgent';
                                $prioClass = 'badge-priority-urgent';
                            } elseif ($pNum === 2) {
                                $prioName = 'High';
                                $prioClass = 'badge-priority-high';
                            } elseif ($pNum === 3) {
                                $prioName = 'Medium';
                                $prioClass = 'badge-priority-medium';
                            } else {
                                $prioName = 'Low';
                                $prioClass = 'badge-priority-low';
                            }

                            $creatorName = !empty($t['user_full_name']) ? $t['user_full_name'] : 'Me';
                            $creatorPhoto = $t['user_photo'] ?? '';
                            $nameParts = preg_split('/\s+/', trim($creatorName));
                            if (count($nameParts) >= 2) {
                                $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
                            } else {
                                $initials = strtoupper(substr($creatorName, 0, 2));
                            }

                            // Status label & color
                            $statusColor = '#d97706';
                            $statusLabel = 'In Progress';
                            if ($isCompleted) {
                                $statusColor = '#16a34a';
                                $statusLabel = 'Completed';
                            } elseif ($isOverdue) {
                                $statusColor = '#dc2626';
                                $statusLabel = 'Overdue';
                            }
                            
                            $projectName = !empty($t['project_name']) ? $t['project_name'] : 'Inbox';
                            
                            $taskData = [
                                "id" => (int)$t["id"],
                                "title" => (string)$t["title"],
                                "description" => (string)($t["description"] ?? ""),
                                "project_name" => $projectName,
                                "status" => $statusLabel,
                                "status_raw" => $isCompleted ? "completed" : "pending",
                                "progress" => $progressPct,
                                "due_date" => !empty($t["due_date"]) ? date("Y-m-d", strtotime($t["due_date"])) : "",
                                "due_date_formatted" => !empty($t["due_date"]) ? date("d M Y", strtotime($t["due_date"])) : "No Deadline"
                            ];
                            $taskDataAttr = htmlspecialchars(json_encode($taskData, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr>
                            <!-- Task & Project -->
                            <td>
                                <div class="fw-bold text-dark mb-1" style="font-size: 14px; <?= $isCompleted ? 'text-decoration: line-through; opacity: 0.75;' : '' ?>">
                                    <a href="javascript:void(0)" class="text-decoration-none text-dark hover-green" onclick="openTaskDetailsById(<?= $t['id'] ?>)">
                                        <?= htmlspecialchars($t['title']) ?>
                                    </a>
                                </div>
                                <div class="text-muted" style="font-size: 11.5px;">
                                    <?php if (!empty($t['is_team_project'])): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle me-1"><i class="bi bi-people-fill me-1"></i>Team</span>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($projectName) ?> • <span class="text-secondary fw-semibold">#TODO-<?= $t['id'] ?></span>
                                    <?php if (!empty($t['location'])): ?>
                                        • <span class="text-muted"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($t['location']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- Source -->
                            <td>
                                <?php if (!empty($t['is_team_project'])): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold" style="font-size: 11.5px; padding: 4px 8px;"><i class="bi bi-people-fill me-1"></i>Team Task</span>
                                <?php else: ?>
                                    <span class="badge-source-self"><?= htmlspecialchars($projectName) ?></span>
                                <?php endif; ?>
                            </td>

                            <!-- Priority -->
                            <td>
                                <span class="<?= $prioClass ?>">
                                    <?= $prioName ?>
                                </span>
                            </td>

                            <!-- Assigned By -->
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($creatorPhoto)): ?>
                                        <img src="<?= getImage('users', $creatorPhoto) ?>" alt="Avatar" class="avatar-badge-img">
                                    <?php else: ?>
                                        <span class="avatar-badge-initials"><?= $initials ?></span>
                                    <?php endif; ?>
                                    <span class="fw-semibold text-dark text-truncate" style="max-width: 140px;" title="<?= htmlspecialchars($creatorName) ?>">
                                        <?= htmlspecialchars($creatorName) ?>
                                    </span>
                                </div>
                            </td>

                            <!-- Deadline -->
                            <td class="text-nowrap">
                                <span class="<?= $isOverdue ? 'text-danger fw-semibold' : 'text-secondary' ?>" style="font-size: 13px;">
                                    <?= !empty($t['due_date']) ? date('d M Y', strtotime($t['due_date'])) : 'No Deadline' ?>
                                </span>
                            </td>

                            <!-- Status -->
                            <td class="text-nowrap" id="statusCell_<?= $t['id'] ?>">
                                <span class="fw-semibold" style="color: <?= $statusColor ?>; font-size: 13px;">
                                    <span class="status-dot" style="background-color: <?= $statusColor ?>;"></span>
                                    <?= $statusLabel ?>
                                </span>
                            </td>

                            <!-- Progress Column: Inline Number Input & Bar -->
                            <td style="min-width: 180px;">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 8px; background-color: #f1f5f9; border-radius: 6px; min-width: 48px; cursor: pointer;" onclick="openTaskDetailsById(<?= $t['id'] ?>)" title="Click to open full progress details">
                                        <div class="progress-bar" id="rowBar_<?= $t['id'] ?>" style="width: <?= $progressPct ?>%; background-color: <?= $progressPct >= 100 ? '#16a34a' : ($progressPct >= 50 ? '#059669' : ($progressPct > 0 ? '#d97706' : '#94a3b8')) ?>; border-radius: 6px;"></div>
                                    </div>
                                    <div class="input-group input-group-sm" style="width: 90px;" title="Type percentage & press Enter or checkmark to save">
                                        <input type="number" min="0" max="100" class="form-control text-center px-1 fw-bold" id="rowInput_<?= $t['id'] ?>" value="<?= $progressPct ?>" style="font-size: 12.5px; height: 30px; border-radius: 6px 0 0 6px;" onkeydown="if(event.key==='Enter'){ saveRowProgress(<?= $t['id'] ?>); }">
                                        <button type="button" class="btn btn-outline-success px-2 py-0" id="rowBtn_<?= $t['id'] ?>" onclick="saveRowProgress(<?= $t['id'] ?>)" style="height: 30px; border-radius: 0 6px 6px 0;" title="Save progress">
                                            <i class="bi bi-check2"></i>
                                        </button>
                                    </div>
                                </div>
                            </td>

                            <!-- Actions (Chevron dropdown) -->
                            <td class="text-end">
                                <div class="dropdown d-inline-block">
                                    <button class="btn btn-sm btn-light border text-muted px-2 py-1 rounded-circle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-chevron-down" style="font-size: 12px;"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                        <li>
                                            <a class="dropdown-item py-2 fs-13 fw-semibold text-success" href="javascript:void(0)" onclick="openTaskDetailsById(<?= $t['id'] ?>)">
                                                <i class="bi bi-sliders text-success me-2"></i> Update Progress & Details
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item py-2 fs-13" href="<?= $GLOBALS['base_url'] ?>/my-created-tasks/complete/<?= $t['id'] ?>">
                                                <i class="bi bi-check2-circle text-primary me-2"></i> 
                                                <?= $isCompleted ? 'Mark Pending' : 'Mark Completed' ?>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item py-2 fs-13" href="<?= $GLOBALS['base_url'] ?>/todo">
                                                <i class="bi bi-list-check text-secondary me-2"></i> Open in Todo List
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider my-1"></li>
                                        <li>
                                            <a class="dropdown-item py-2 fs-13 text-danger" href="<?= $GLOBALS['base_url'] ?>/my-created-tasks/delete/<?= $t['id'] ?>" onclick="return confirm('Are you sure you want to delete this todo task?');">
                                                <i class="bi bi-trash me-2"></i> Delete Task
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Table Footer Pagination -->
        <div class="d-flex align-items-center justify-content-between p-3 border-top bg-light flex-wrap gap-2">
            <span class="small text-muted">
                Showing <?= $filteredTotal > 0 ? (($currentPage - 1) * $perPage + 1) : 0 ?> to <?= min($filteredTotal, $currentPage * $perPage) ?> of <?= $filteredTotal ?> tasks (<?= $perPageParam === 'all' ? 'All items' : $perPage . ' per page' ?>)
            </span>
            <?php if ($totalPages > 1): ?>
            <ul class="pagination pagination-sm mb-0">
                <!-- Previous Button -->
                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                    <?php 
                        $prevParams = $_GET; 
                        $prevParams['page'] = max(1, $currentPage - 1); 
                    ?>
                    <a class="page-link text-dark" href="?<?= http_build_query($prevParams) ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>

                <?php 
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);

                if ($startPage > 1): 
                    $firstParams = $_GET;
                    $firstParams['page'] = 1;
                ?>
                    <li class="page-item"><a class="page-link text-dark" href="?<?= http_build_query($firstParams) ?>">1</a></li>
                    <?php if ($startPage > 2): ?><li class="page-item disabled"><span class="page-link text-muted">...</span></li><?php endif; ?>
                <?php endif; ?>

                <?php for ($p = $startPage; $p <= $endPage; $p++): 
                    $params = $_GET;
                    $params['page'] = $p;
                    $url = '?' . http_build_query($params);
                ?>
                <li class="page-item <?= $p == $currentPage ? 'active' : '' ?>">
                    <a class="page-link <?= $p == $currentPage ? 'bg-success border-success text-white' : 'text-dark' ?>" href="<?= $url ?>"><?= $p ?></a>
                </li>
                <?php endfor; ?>

                <?php if ($endPage < $totalPages): 
                    $lastParams = $_GET;
                    $lastParams['page'] = $totalPages;
                ?>
                    <?php if ($endPage < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link text-muted">...</span></li><?php endif; ?>
                    <li class="page-item"><a class="page-link text-dark" href="?<?= http_build_query($lastParams) ?>"><?= $totalPages ?></a></li>
                <?php endif; ?>

                <!-- Next Button -->
                <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                    <?php 
                        $nextParams = $_GET; 
                        $nextParams['page'] = min($totalPages, $currentPage + 1); 
                    ?>
                    <a class="page-link text-dark" href="?<?= http_build_query($nextParams) ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ========================================================================
     Right Offcanvas Sidebar: Add New Task Form (Matching Screenshot)
     ======================================================================== -->
<div class="offcanvas offcanvas-end task-offcanvas" tabindex="-1" id="offcanvasAddNewTask" aria-labelledby="offcanvasAddNewTaskLabel">
    <div class="offcanvas-header p-4 pb-2 border-bottom-0">
        <div>
            <div class="task-offcanvas-tag">NEW TASK</div>
            <h4 class="task-offcanvas-title mb-0" id="offcanvasAddNewTaskLabel">Add a new task</h4>
        </div>
        <button type="button" class="task-offcanvas-btn-close" data-bs-dismiss="offcanvas" aria-label="Close">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="offcanvas-body p-4 pt-2">
        <form action="<?= $GLOBALS['base_url'] ?>/my-created-tasks/store" method="POST" enctype="multipart/form-data" id="myCreatedTaskForm">
            <!-- 1. Task Title & Description -->
            <div class="mb-3">
                <input type="text" name="title" class="task-offcanvas-input fw-semibold mb-2" placeholder="Task name..." required autocomplete="off" style="font-size: 15.5px;">
                <input type="text" name="description" class="task-offcanvas-input" placeholder="Add description (optional)..." autocomplete="off" style="font-size: 13.5px;">
            </div>

            <!-- 2. Project Selection & Inline Project Creation -->
            <div class="mb-3 p-3 bg-light rounded-3 border">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <label class="form-field-label mb-0 fw-bold text-dark">
                        <i class="bi bi-folder2-open text-primary me-1"></i> Project
                    </label>
                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 py-1 fs-12 fw-semibold" onclick="toggleNewProject()">
                        <i class="bi bi-plus-lg me-1"></i> Create Project
                    </button>
                </div>
                
                <select name="todo_project_id" id="taskProjectSelect" class="form-select form-select-sm bg-white">
                    <?php if (!empty($projects)): ?>
                        <?php foreach ($projects as $pr): ?>
                            <option value="<?= $pr['id'] ?>" <?= ($pr['id'] == $inboxId) ? 'selected' : '' ?>>
                                <?= (!empty($pr['is_team_project']) ? '👥 [Team] ' : '') . htmlspecialchars($pr['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="1">Inbox</option>
                    <?php endif; ?>
                </select>

                <!-- Inline Quick Project Creation Box -->
                <div id="quickProjectBox" class="mt-2 p-2 bg-white rounded-3 border" style="display: none;">
                    <label class="small text-muted fw-bold mb-1 d-block">NEW PROJECT NAME</label>
                    <div class="input-group input-group-sm mb-1">
                        <input type="text" name="new_project_name" id="quickProjectName" class="form-control" placeholder="Enter project name (e.g. ERP Transformation)...">
                        <button type="button" class="btn btn-success fw-semibold px-3" onclick="saveNewProject()">Save & Select</button>
                        <button type="button" class="btn btn-light border" onclick="toggleNewProject(false)">Cancel</button>
                    </div>
                    <div class="form-check mt-2 mb-1">
                        <input class="form-check-input" type="checkbox" name="is_team_project" id="quickIsTeamProject" value="1" style="cursor: pointer;">
                        <label class="form-check-label small fw-semibold text-dark" for="quickIsTeamProject" style="cursor: pointer;">
                            <i class="bi bi-people-fill text-success me-1"></i> Team Project (Shared with my reporting boss & team members)
                        </label>
                    </div>
                    <div id="projectCreateMsg" class="small mt-1"></div>
                </div>
            </div>

            <!-- 3. Core Controls Row: 4 Columns (Due Date, Priority, Location, Attachment) -->
            <div class="todo-row-4-col mb-3">
                <div>
                    <label class="form-field-label"><i class="far fa-calendar text-danger me-1"></i> Due / Start Date</label>
                    <input type="date" name="due_date" class="form-control form-control-sm w-100" value="<?= date('Y-m-d') ?>">
                </div>

                <div>
                    <label class="form-field-label"><i class="fas fa-flag text-warning me-1"></i> Priority</label>
                    <select name="priority" class="form-select form-select-sm w-100">
                        <option value="4">Priority 4 (Normal)</option>
                        <option value="3">Priority 3 (Medium)</option>
                        <option value="2" selected>Priority 2 (High)</option>
                        <option value="1">Priority 1 (Urgent / Highest)</option>
                    </select>
                </div>

                <div>
                    <label class="form-field-label"><i class="fas fa-map-marker-alt text-primary me-1"></i> Location / Tag</label>
                    <input type="text" name="location" class="form-control form-control-sm w-100" placeholder="e.g. Office, Site, Online">
                </div>

                <div>
                    <label class="form-field-label"><i class="fas fa-paperclip text-info me-1"></i> Attachment File</label>
                    <input type="file" name="attachment" class="form-control form-control-sm w-100" style="font-size: 11.5px;">
                </div>
            </div>

            <!-- 4. Advanced Reminders & Date Range Repeat Panel -->
            <div class="advanced-settings-panel">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="mb-0 text-dark fw-bold" style="font-size: 13px;">
                        <i class="fas fa-bell text-warning me-1"></i> Advanced Reminders & Date Range Repeat
                    </h6>
                    <span class="badge bg-white text-secondary border px-2 py-1 fs-11">Custom Schedule & Email Trigger</span>
                </div>

                <div class="todo-row-4-col">
                    <div>
                        <label class="form-field-label">Repeat Mode</label>
                        <select name="repeat_type" id="offcanvas_repeat_type" class="form-select form-select-sm w-100" onchange="toggleRepeatUntilOffcanvas(this.value)">
                            <option value="">Do not repeat</option>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="custom">Custom Date Range</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-field-label text-primary">Repeat Until (To Date)</label>
                        <input type="date" name="repeat_until" id="offcanvas_repeat_until" class="form-control form-control-sm bg-white border-primary-subtle w-100" disabled>
                    </div>

                    <div>
                        <label class="form-field-label">Custom Reminder Date & Time</label>
                        <input type="datetime-local" name="reminder_date_time" class="form-control form-control-sm bg-white w-100">
                    </div>

                    <div>
                        <label class="form-field-label">Email Reminder Count</label>
                        <div class="input-group input-group-sm w-100">
                            <span class="input-group-text bg-white fs-11">Send Max</span>
                            <input type="number" name="max_reminders" class="form-control bg-white text-center" value="1" min="0">
                            <span class="input-group-text bg-white fs-11">Times</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Action Buttons -->
            <div class="d-flex align-items-center justify-content-end gap-2 pt-2 border-top">
                <button type="button" class="btn btn-light border px-4 py-2 rounded-3 fw-semibold text-secondary" data-bs-dismiss="offcanvas">Cancel</button>
                <button type="submit" class="btn btn-success px-4 py-2 rounded-3 fw-semibold" style="background-color: #15803d; border-color: #15803d;">
                    <i class="bi bi-check2 me-1"></i> Add Task
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================
     Right Offcanvas Sidebar: Task Details & Update Progress (Matching Screenshot)
     ======================================================================== -->
<div class="offcanvas offcanvas-end task-offcanvas" tabindex="-1" id="offcanvasTaskDetails" aria-labelledby="offcanvasTaskDetailsLabel" style="width: 580px !important;">
    <div class="offcanvas-header p-4 pb-2 border-bottom-0">
        <div>
            <div class="task-offcanvas-tag text-success" style="font-size: 11.5px; font-weight: 800; letter-spacing: 0.08em; color: #15803d;">TASK DETAILS</div>
            <h4 class="task-offcanvas-title mb-0" id="detailTaskTitle" style="font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1.3;">Task Details</h4>
        </div>
        <button type="button" class="task-offcanvas-btn-close" data-bs-dismiss="offcanvas" aria-label="Close">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="offcanvas-body p-4 pt-2">
        <form action="<?= $GLOBALS['base_url'] ?>/my-created-tasks/update-progress" method="POST" id="taskDetailsUpdateForm">
            <input type="hidden" name="ajax" value="1">
            <input type="hidden" name="task_id" id="detailTaskId" value="">
            <input type="hidden" name="status" id="detailStatusHidden" value="pending">

            <!-- Detail Information Card -->
            <div class="p-3 mb-3 rounded-3 border" style="background-color: #f8fafc; border-color: #e2e8f0;">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <h5 class="fw-bold mb-0" id="detailStatusText" style="color: #0f172a; font-size: 16px;">In Progress</h5>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="detailStatusCheckbox" style="cursor: pointer; width: 2em; height: 1.1em;">
                        <label class="form-check-label small fw-semibold text-secondary ms-1" for="detailStatusCheckbox" style="cursor: pointer;">Completed</label>
                    </div>
                </div>
                <div class="text-muted small mb-2 fw-medium" id="detailProjectSub" style="font-size: 12.5px;">
                    Project Name • #TODO-1
                </div>
                <p class="text-secondary small mb-0" id="detailDescriptionText" style="line-height: 1.6; font-size: 13px; color: #64748b;">
                    This task includes clear deliverables, ownership and a measurable deadline. Progress and activity history will appear here for accountability.
                </p>
            </div>

            <!-- Current Progress Visual Card -->
            <div class="p-3 mb-3 rounded-3 border" style="background: linear-gradient(135deg, #f0fdf4 0%, #f8fafc 100%); border-color: #bbf7d0 !important;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-bold text-success text-uppercase" style="letter-spacing: 0.06em;">CURRENT PROGRESS</span>
                    <span class="badge bg-success text-white px-2 py-1 fs-12 fw-bold" id="detailProgressBadge">0% Completed</span>
                </div>
                <div class="progress mb-2" style="height: 10px; background-color: #e2e8f0; border-radius: 6px;">
                    <div class="progress-bar bg-success" id="detailProgressBarVisual" style="width: 0%; border-radius: 6px; transition: width 0.3s ease;"></div>
                </div>
                <div class="d-flex justify-content-between text-muted" style="font-size: 11px;">
                    <span>0% (Not Started)</span>
                    <span>50% (Halfway)</span>
                    <span>100% (Completed)</span>
                </div>
            </div>

            <!-- Interactive Progress Range Slider & Quick Buttons -->
            <div class="mb-3 p-3 bg-light rounded-3 border">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <label class="task-offcanvas-label text-muted fw-bold mb-0">UPDATE COMPLETION PERCENTAGE</label>
                    <span class="fw-bold text-success fs-14" id="detailSliderDisplay">0%</span>
                </div>
                <input type="range" class="form-range mb-2" id="detailProgressRange" min="0" max="100" step="5" value="0">
                <div class="d-flex align-items-center justify-content-between gap-1">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-0 fs-11 fw-semibold" onclick="setDetailProgress(0)">0%</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-0 fs-11 fw-semibold" onclick="setDetailProgress(25)">25%</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-0 fs-11 fw-semibold" onclick="setDetailProgress(50)">50%</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-0 fs-11 fw-semibold" onclick="setDetailProgress(75)">75%</button>
                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-2 py-0 fs-11 fw-semibold" onclick="setDetailProgress(100)">100% (Done)</button>
                </div>
            </div>

            <!-- Two Side-by-Side Inputs: DEADLINE & MANUAL PROGRESS % -->
            <div class="row g-3 mb-4">
                <div class="col-6">
                    <label class="task-offcanvas-label text-muted fw-bold mb-2" style="font-size: 11px; letter-spacing: 0.06em;">DEADLINE</label>
                    <input type="date" name="due_date" id="detailDueDateInput" class="form-control rounded-3 border bg-white" style="height: 48px; font-size: 14.5px; border-color: #cbd5e1;">
                </div>
                <div class="col-6">
                    <label class="task-offcanvas-label text-muted fw-bold mb-2" style="font-size: 11px; letter-spacing: 0.06em;">MANUAL % INPUT</label>
                    <div class="input-group">
                        <input type="number" name="progress" id="detailProgressInput" min="0" max="100" class="form-control rounded-3 border bg-white fw-bold text-dark" placeholder="0" style="height: 48px; font-size: 14.5px; border-color: #cbd5e1;">
                        <span class="input-group-text bg-white border-start-0 text-muted fw-bold" style="border-radius: 0 10px 10px 0; border-color: #cbd5e1;">%</span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons matching screenshot -->
            <div class="d-flex align-items-center gap-3 mt-4 pt-2">
                <button type="button" class="btn btn-outline-secondary w-50 py-2 fw-semibold rounded-3" data-bs-dismiss="offcanvas" style="height: 48px; border-color: #e2e8f0; color: #475569;">
                    Close
                </button>
                <button type="submit" id="btnUpdateTaskProgress" class="btn btn-success w-50 py-2 fw-semibold rounded-3 text-white shadow-sm" style="height: 48px; background-color: #059669; border-color: #059669;">
                    Update Progress
                </button>
            </div>

            <!-- Feedback Message container -->
            <div id="detailFeedbackMsg" class="mt-3 text-center" style="display: none;"></div>

            <!-- Secondary Actions: Open in Todo & Delete -->
            <div class="d-flex align-items-center justify-content-between pt-3 mt-4 border-top">
                <a href="<?= $GLOBALS['base_url'] ?>/todo" class="text-primary small text-decoration-none fw-semibold">
                    <i class="bi bi-list-check me-1"></i> Open in Todo List
                </a>
                <a href="javascript:void(0)" id="detailDeleteActionBtn" class="text-danger small text-decoration-none fw-semibold">
                    <i class="bi bi-trash me-1"></i> Delete Task
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ApexCharts Script Initialization -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    // 1. Performance Trend Area / Line Chart
    const trendDates = <?= json_encode($trendDates ?? []) ?>;
    const completedSeries = <?= json_encode($smoothCompleted ?? []) ?>;
    const missedSeries = <?= json_encode($smoothMissed ?? []) ?>;

    const trendOptions = {
        series: [
            {
                name: 'Completed Tasks',
                data: completedSeries
            },
            {
                name: 'Missed Deadlines',
                data: missedSeries
            }
        ],
        chart: {
            height: 250,
            type: 'area',
            toolbar: { show: false },
            fontFamily: 'inherit'
        },
        colors: ['#16a34a', '#ef4444'],
        dataLabels: { enabled: false },
        stroke: {
            curve: 'smooth',
            width: [3, 2],
            dashArray: [0, 5]
        },
        fill: {
            type: ['gradient', 'solid'],
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.35,
                opacityTo: 0.05,
                stops: [0, 90, 100]
            }
        },
        xaxis: {
            categories: trendDates,
            labels: {
                style: { colors: '#94a3b8', fontSize: '11px' },
                rotate: 0,
                showDuplicates: false
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
        legend: {
            position: 'top',
            horizontalAlign: 'right',
            fontSize: '12px'
        },
        tooltip: {
            theme: 'light'
        }
    };

    const trendChart = new ApexCharts(document.querySelector("#performanceTrendChart"), trendOptions);
    trendChart.render();

    // 2. Task Status Donut Chart
    const compCount = <?= (int)$completedCount ?>;
    const inProgCount = <?= (int)$inProgressCount ?>;
    const overdueCount = <?= (int)$missedCount ?>;
    const inboxCount = <?= (int)$inboxTasksCount ?>;

    const donutOptions = {
        series: [compCount, inProgCount, overdueCount, inboxCount],
        chart: {
            type: 'donut',
            height: 220,
            fontFamily: 'inherit'
        },
        labels: ['Completed', 'In progress', 'Overdue', 'Inbox'],
        colors: ['#16a34a', '#eab308', '#ef4444', '#3b82f6'],
        plotOptions: {
            pie: {
                donut: {
                    size: '72%',
                    labels: {
                        show: false
                    }
                }
            }
        },
        dataLabels: { enabled: false },
        legend: { show: false },
        tooltip: {
            theme: 'light',
            y: {
                formatter: function (val) {
                    return val + " Tasks";
                }
            }
        }
    };

    const donutChart = new ApexCharts(document.querySelector("#taskStatusDonutChart"), donutOptions);
    donutChart.render();
});

function changeTrendTime(event, period) {
    document.querySelectorAll('.perf-time-pill').forEach(el => el.classList.remove('active'));
    event.target.classList.add('active');
}

function toggleNewProject(show) {
    const box = document.getElementById('quickProjectBox');
    if (show === undefined) {
        box.style.display = box.style.display === 'none' ? 'block' : 'none';
    } else {
        box.style.display = show ? 'block' : 'none';
    }
    if (box.style.display === 'block') {
        document.getElementById('quickProjectName').focus();
    }
}

function saveNewProject() {
    const nameInput = document.getElementById('quickProjectName');
    const msg = document.getElementById('projectCreateMsg');
    const isTeamCheck = document.getElementById('quickIsTeamProject');
    const name = nameInput.value.trim();
    if (!name) {
        msg.innerHTML = '<span class="text-danger fw-semibold">Please enter a project name</span>';
        return;
    }

    const isTeam = (isTeamCheck && isTeamCheck.checked) ? 1 : 0;
    msg.innerHTML = '<span class="text-muted"><i class="bi bi-hourglass-split"></i> Creating project...</span>';

    const formData = new FormData();
    formData.append('name', name);
    formData.append('is_team_project', isTeam);

    fetch('<?= $GLOBALS['base_url'] ?>/my-created-tasks/create-project', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.project) {
            const select = document.getElementById('taskProjectSelect');
            let exists = false;
            for (let opt of select.options) {
                if (opt.value == data.project.id) {
                    opt.selected = true;
                    exists = true;
                    break;
                }
            }
            if (!exists) {
                const optText = (data.project.is_team_project == 1 ? '👥 [Team] ' : '') + data.project.name;
                const newOpt = new Option(optText, data.project.id, true, true);
                select.add(newOpt);
            }
            msg.innerHTML = '<span class="text-success fw-semibold"><i class="bi bi-check-circle"></i> Project created and selected!</span>';
            nameInput.value = '';
            if (isTeamCheck) isTeamCheck.checked = false;
            setTimeout(() => {
                toggleNewProject(false);
                msg.innerHTML = '';
            }, 900);
        } else {
            msg.innerHTML = '<span class="text-danger">' + (data.message || 'Error creating project') + '</span>';
        }
    })
    .catch(err => {
        msg.innerHTML = '<span class="text-danger">Request failed</span>';
    });
}

function toggleRepeatUntilOffcanvas(val) {
    const until = document.getElementById('offcanvas_repeat_until');
    if (val && val !== '') {
        until.disabled = false;
    } else {
        until.disabled = true;
        until.value = '';
    }
}

// -------------------------------------------------------------
// Task Details Offcanvas Logic (Update Progress & Completion)
// -------------------------------------------------------------
function setDetailProgress(val) {
    val = Math.max(0, Math.min(100, parseInt(val) || 0));
    setDetailProgressUI(val);
}

function setDetailProgressUI(val) {
    val = Math.max(0, Math.min(100, parseInt(val) || 0));
    const progressInput = document.getElementById('detailProgressInput');
    const progressRange = document.getElementById('detailProgressRange');
    const progressBadge = document.getElementById('detailProgressBadge');
    const progressBar = document.getElementById('detailProgressBarVisual');
    const sliderDisplay = document.getElementById('detailSliderDisplay');
    const statusCheckbox = document.getElementById('detailStatusCheckbox');

    if (progressInput) progressInput.value = val;
    if (progressRange) progressRange.value = val;
    if (sliderDisplay) sliderDisplay.textContent = val + '%';
    
    if (progressBadge) {
        progressBadge.textContent = val + '% Completed';
        if (val >= 100) {
            progressBadge.className = 'badge bg-success text-white px-2 py-1 fs-12 fw-bold';
        } else if (val > 0) {
            progressBadge.className = 'badge bg-warning-subtle text-dark border px-2 py-1 fs-12 fw-bold';
        } else {
            progressBadge.className = 'badge bg-light text-secondary border px-2 py-1 fs-12 fw-bold';
        }
    }
    if (progressBar) {
        progressBar.style.width = val + '%';
        progressBar.style.backgroundColor = (val >= 100 ? '#16a34a' : (val >= 50 ? '#059669' : (val > 0 ? '#d97706' : '#94a3b8')));
    }

    const isCompleted = (val >= 100);
    if (statusCheckbox) {
        statusCheckbox.checked = isCompleted;
    }
    updateDetailStatusUI(isCompleted, val);
}

function openTaskDetailsOffcanvas(task) {
    if (!task) return;
    document.getElementById('detailTaskId').value = task.id;
    document.getElementById('detailTaskTitle').textContent = task.title || 'Task Details';
    
    const projName = task.project_name || 'Inbox';
    document.getElementById('detailProjectSub').textContent = projName + ' • #TODO-' + task.id;
    
    const desc = task.description && task.description.trim() !== '' 
        ? task.description 
        : 'This task includes clear deliverables, ownership and a measurable deadline. Progress and activity history will appear here for accountability.';
    document.getElementById('detailDescriptionText').textContent = desc;

    document.getElementById('detailDueDateInput').value = task.due_date || '';
    
    const progressVal = parseInt(task.progress) || 0;
    setDetailProgressUI(progressVal);

    const deleteBtn = document.getElementById('detailDeleteActionBtn');
    if (deleteBtn) {
        deleteBtn.onclick = function() {
            if (confirm('Are you sure you want to delete this task?')) {
                window.location.href = '<?= $GLOBALS['base_url'] ?>/my-created-tasks/delete/' + task.id;
            }
        };
    }

    const feedback = document.getElementById('detailFeedbackMsg');
    if (feedback) {
        feedback.style.display = 'none';
        feedback.innerHTML = '';
    }

    const submitBtn = document.getElementById('btnUpdateTaskProgress');
    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Update Progress';
    }

    const offcanvasEl = document.getElementById('offcanvasTaskDetails');
    if (offcanvasEl) {
        const bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl) || new bootstrap.Offcanvas(offcanvasEl);
        bsOffcanvas.show();
    }
}

function openTaskDetailsById(taskId) {
    if (window.allTasksData && window.allTasksData[taskId]) {
        openTaskDetailsOffcanvas(window.allTasksData[taskId]);
    } else {
        console.warn("Task data not found for ID:", taskId);
    }
}

function saveRowProgress(taskId) {
    const input = document.getElementById('rowInput_' + taskId);
    const btn = document.getElementById('rowBtn_' + taskId);
    const bar = document.getElementById('rowBar_' + taskId);
    const statusCell = document.getElementById('statusCell_' + taskId);
    if (!input) return;

    let val = parseInt(input.value);
    if (isNaN(val)) val = 0;
    if (val < 0) val = 0;
    if (val > 100) val = 100;
    input.value = val;

    const originalBtn = btn ? btn.innerHTML : '<i class="bi bi-check2"></i>';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" style="width: 12px; height: 12px;" role="status"></span>';
    }

    const formData = new FormData();
    formData.append('task_id', taskId);
    formData.append('progress', val);
    formData.append('ajax', '1');

    fetch('<?= $GLOBALS['base_url'] ?>/my-created-tasks/update-progress', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg text-white"></i>';
            btn.classList.remove('btn-outline-success');
            btn.classList.add('btn-success', 'text-white');
            setTimeout(() => {
                btn.classList.remove('btn-success', 'text-white');
                btn.classList.add('btn-outline-success');
                btn.innerHTML = originalBtn;
            }, 1200);
        }

        if (bar) {
            bar.style.width = val + '%';
            bar.style.backgroundColor = (val >= 100 ? '#16a34a' : (val >= 50 ? '#059669' : (val > 0 ? '#d97706' : '#94a3b8')));
        }

        // Update task object in memory
        if (window.allTasksData && window.allTasksData[taskId]) {
            window.allTasksData[taskId].progress = val;
            window.allTasksData[taskId].status_raw = (val >= 100) ? 'completed' : 'pending';
            window.allTasksData[taskId].status = (val >= 100) ? 'Completed' : 'In Progress';
        }

        // Update status badge in row
        if (statusCell) {
            if (val >= 100) {
                statusCell.innerHTML = '<span class="fw-semibold" style="color: #16a34a; font-size: 13px;"><span class="status-dot" style="background-color: #16a34a;"></span>Completed</span>';
            } else {
                statusCell.innerHTML = '<span class="fw-semibold" style="color: #d97706; font-size: 13px;"><span class="status-dot" style="background-color: #d97706;"></span>In Progress</span>';
            }
        }

        showQuickToast(data.message || 'Progress updated to ' + val + '%');
    })
    .catch(err => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalBtn;
        }
        showQuickToast('Failed to update progress');
    });
}

function showQuickToast(msg) {
    let toast = document.getElementById('tphlToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'tphlToast';
        toast.style.cssText = 'position:fixed; bottom:25px; right:25px; z-index:9999; background:#0f172a; color:#fff; padding:10px 18px; border-radius:10px; font-size:13px; font-weight:600; box-shadow:0 4px 14px rgba(0,0,0,0.18); transition: opacity 0.3s; display:none;';
        document.body.appendChild(toast);
    }
    toast.innerHTML = '<i class="bi bi-check-circle-fill text-success me-2"></i>' + msg;
    toast.style.display = 'block';
    toast.style.opacity = '1';
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => { toast.style.display = 'none'; }, 300);
    }, 2000);
}

function updateDetailStatusUI(isCompleted, progressVal) {
    const statusText = document.getElementById('detailStatusText');
    const statusHidden = document.getElementById('detailStatusHidden');
    if (isCompleted) {
        if (statusText) {
            statusText.textContent = 'Completed';
            statusText.style.color = '#16a34a';
        }
        if (statusHidden) statusHidden.value = 'completed';
    } else {
        if (statusText) {
            statusText.textContent = 'In Progress';
            statusText.style.color = '#0f172a';
        }
        if (statusHidden) statusHidden.value = 'pending';
    }
}

// Global click delegation for all .btn-open-task-details elements
document.addEventListener('click', function (e) {
    const trigger = e.target.closest('.btn-open-task-details');
    if (!trigger) return;
    
    e.preventDefault();
    const raw = trigger.getAttribute('data-task');
    if (raw) {
        try {
            const task = JSON.parse(raw);
            openTaskDetailsOffcanvas(task);
        } catch (err) {
            console.error("Error parsing task data:", err);
        }
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const statusCheckbox = document.getElementById('detailStatusCheckbox');
    const progressInput = document.getElementById('detailProgressInput');
    const progressRange = document.getElementById('detailProgressRange');

    if (statusCheckbox) {
        statusCheckbox.addEventListener('change', function() {
            if (this.checked) {
                setDetailProgressUI(100);
            } else {
                setDetailProgressUI(0);
            }
        });
    }

    if (progressRange) {
        progressRange.addEventListener('input', function() {
            setDetailProgressUI(this.value);
        });
    }

    if (progressInput) {
        progressInput.addEventListener('input', function() {
            let val = parseInt(this.value);
            if (isNaN(val)) val = 0;
            if (val > 100) { val = 100; this.value = 100; }
            if (val < 0) { val = 0; this.value = 0; }
            setDetailProgressUI(val);
        });
    }

    // Handle AJAX submission of Task Details form
    const detailsForm = document.getElementById('taskDetailsUpdateForm');
    if (detailsForm) {
        detailsForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const submitBtn = document.getElementById('btnUpdateTaskProgress');
            const feedback = document.getElementById('detailFeedbackMsg');
            const originalText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Updating...';

            const formData = new FormData(detailsForm);
            formData.append('ajax', '1');

            fetch(detailsForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    submitBtn.innerHTML = '<i class="bi bi-check2 me-1"></i> Updated!';
                    if (feedback) {
                        feedback.style.display = 'block';
                        feedback.innerHTML = '<div class="alert alert-success py-2 px-3 small rounded-3 mb-0"><i class="bi bi-check-circle me-1"></i> ' + (data.message || 'Progress updated successfully!') + '</div>';
                    }
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    if (feedback) {
                        feedback.style.display = 'block';
                        feedback.innerHTML = '<div class="alert alert-danger py-2 px-3 small rounded-3 mb-0">' + (data.message || 'Failed to update task') + '</div>';
                    }
                }
            })
            .catch(err => {
                console.error("AJAX failed, falling back to standard submit", err);
                detailsForm.submit();
            });
        });
    }
});
</script>

<?php
// Base URL from Controller
$base = $GLOBALS['base_url'] ?? '';

// Build preserve query string helper for presets
if (!function_exists('buildTeamUrl')) {
    function buildTeamUrl($extra = []) {
        $params = $_GET;
        foreach ($extra as $k => $v) {
            if ($v === null) {
                unset($params[$k]);
            } else {
                $params[$k] = $v;
            }
        }
        $b = $GLOBALS['base_url'] ?? '';
        return $b . '/team-dashboard' . (!empty($params) ? '?' . http_build_query($params) : '');
    }
}
?>

<style>
    /* ── Color Accents ── */
    .accent-blue      { --accent: #2563eb; }
    .accent-green     { --accent: #16a34a; }
    .accent-amber     { --accent: #d97706; }
    .accent-indigo    { --accent: #4f46e5; }
    .accent-emerald   { --accent: #059669; }
    .accent-orange    { --accent: #ea580c; }
    .accent-teal      { --accent: #0d9488; }
    .accent-red       { --accent: #dc2626; }
    .accent-sky       { --accent: #0284c7; }
    .accent-cyan      { --accent: #0891b2; }
    .accent-rose      { --accent: #e11d48; }
    .accent-purple    { --accent: #9333ea; }
    .accent-fuchsia   { --accent: #c026d3; }
    .accent-violet    { --accent: #7c3aed; }
    .accent-slate     { --accent: #475569; }
    .accent-tangerine { --accent: #f97316; }

    /* ── Base Card (Light Mode default) ── */
    .stat-card {
        border-radius: 10px;
        padding: 12px 14px;
        position: relative;
        overflow: hidden;
        transition: all 0.2s ease-in-out;
        border: 1px solid #e2e8f0;
        border-left: 4px solid var(--accent, #3b82f6) !important;
        background: #ffffff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        min-height: 86px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(0,0,0,0.07);
        border-color: #cbd5e1;
    }

    /* ── Typography & Values ── */
    .stat-card .stat-value {
        font-size: 24px;
        font-weight: 800;
        line-height: 1.15;
        letter-spacing: -0.02em;
        color: #0f172a;
    }
    .stat-card .stat-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #64748b;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: flex;
        align-items: center;
    }
    .stat-card .stat-label i {
        color: var(--accent, #3b82f6);
        font-size: 11.5px;
        margin-right: 6px;
    }
    .stat-card .card-hint {
        font-size: 11px;
        color: #94a3b8;
        font-weight: 500;
        margin-top: 4px;
        display: block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .stat-card .stat-unit {
        font-size: 12px;
        font-weight: 600;
        color: #94a3b8;
    }

    /* ── Percentage Pill ── */
    .stat-pill-badge {
        display: inline-flex;
        align-items: center;
        padding: 2px 7px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        background: #f1f5f9;
        color: #334155;
    }
    .accent-green .stat-pill-badge { background: #dcfce7; color: #15803d; }
    .accent-amber .stat-pill-badge { background: #fef3c7; color: #b45309; }
    .accent-violet .stat-pill-badge { background: #ede9fe; color: #6d28d9; }

    /* ── Link Hover Cue ── */
    a.stat-card-link {
        text-decoration: none;
        color: inherit;
        display: block;
        height: 100%;
        position: relative;
    }
    a.stat-card-link .card-nav-arrow {
        position: absolute;
        top: 10px;
        right: 12px;
        font-size: 11px;
        color: #94a3b8;
        opacity: 0;
        transition: all 0.2s ease;
        transform: translateX(-4px);
    }
    a.stat-card-link:hover .card-nav-arrow {
        opacity: 1;
        transform: translateX(0);
        color: var(--accent, #2563eb);
    }
    a.stat-card-link:hover .card-hint {
        color: #475569;
    }

    /* ── Dark Theme Overrides (if .dark-theme active) ── */
    .dark-theme .stat-card {
        background: #1e293b;
        border-color: #334155;
        box-shadow: 0 1px 3px rgba(0,0,0,0.3);
    }
    .dark-theme .stat-card:hover {
        border-color: #475569;
        box-shadow: 0 6px 18px rgba(0,0,0,0.5);
    }
    .dark-theme .stat-card .stat-value {
        color: #f1f5f9;
    }
    .dark-theme .stat-card .stat-label {
        color: #94a3b8;
    }
    .dark-theme .stat-card .card-hint {
        color: #64748b;
    }

    /* Compact mode on small screens */
    @media (max-width: 576px) {
        .stat-card {
            padding: 10px 12px;
            min-height: 78px;
        }
        .stat-card .stat-value {
            font-size: 20px;
        }
        .stat-card .stat-label {
            font-size: 10px;
        }
        .stat-card .card-hint {
            font-size: 9.5px;
        }
    }
</style>

<div class="main-content">

    <!-- TEAM OVERVIEW HERO / HEADER -->
    <div class="card__wrapper shadow-sm p-3 mb-3" style="border-radius: 14px; background: #ffffff; border: 1px solid #e2e8f0;">
        <!-- Top Row: Title, Badges & Metadata -->
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 pb-3">
            <div>
                <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                    <span class="badge bg-primary px-2.5 py-1 text-uppercase" style="letter-spacing: 0.05em; font-size: 10.5px; border-radius: 6px;">
                        <i class="fa-solid fa-users-rectangle me-1"></i> Team Overview
                    </span>
                    <?php if (!empty($selectedMemberId)): ?>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1" style="font-size: 11px; border-radius: 6px;">
                            <i class="fa-solid fa-user-check me-1"></i> Single Member View
                        </span>
                    <?php else: ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1" style="font-size: 11px; border-radius: 6px;">
                            <i class="fa-solid fa-chart-pie me-1"></i> <?= !empty($isAllTeamsMode) ? 'All Teams Aggregate' : 'Team Aggregate View' ?>
                        </span>
                    <?php endif; ?>
                </div>
                <h3 class="fw-bold mb-2" style="color: #0f172a; letter-spacing: -0.02em; font-size: 1.45rem;">
                    <i class="fa-solid fa-shield-halved text-primary me-2"></i><?= htmlspecialchars($teamName) ?>
                </h3>
                <div class="d-flex flex-wrap align-items-center gap-3 text-muted" style="font-size: 12.5px;">
                    <span class="d-inline-flex align-items-center gap-1">
                        <i class="fa-solid fa-crown text-warning"></i>
                        <strong class="text-dark">Leader:</strong>
                        <span><?= htmlspecialchars($teamLeader['full_name'] ?? 'Not Designated') ?></span>
                        <?= !empty($teamLeader['role_name']) ? '<span class="text-secondary">(' . htmlspecialchars($teamLeader['role_name']) . ')</span>' : '' ?>
                    </span>
                    <span class="text-secondary opacity-50">&bull;</span>
                    <span class="d-inline-flex align-items-center gap-1">
                        <i class="fa-solid fa-users text-info"></i>
                        <strong class="text-dark"><?= count($teamMembers) ?></strong> Active Members
                    </span>
                </div>
            </div>

            <!-- Active Cycle Indicator -->
            <div class="d-flex align-items-center gap-2 ms-auto">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1.5" style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; font-size: 12px;">
                    <i class="fa-solid fa-calendar-days text-primary"></i>
                    <span class="text-muted fw-semibold">Active Range:</span>
                    <strong class="text-primary"><?= htmlspecialchars($titlePeriod) ?></strong>
                    <?php if (!empty($hasFilter)): ?>
                        <a href="<?= buildTeamUrl(['preset' => 'all_time', 'from_date' => null, 'to_date' => null]) ?>" class="text-danger text-decoration-none ms-1" title="Reset to All-Time">
                            <i class="fa-solid fa-circle-xmark"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Unified Clean Filter Toolbar -->
        <div class="p-2.5 d-flex flex-wrap align-items-center justify-content-between gap-2" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;">
            <!-- Left: Team & Member Selectors -->
            <div class="d-flex flex-wrap align-items-center gap-2">
                <?php if (!empty($showAllTeamsBtn)): ?>
                    <a href="<?= buildTeamUrl(['team_id' => 'all', 'sales_manager_id' => null, 'member_id' => null, 'assigned_user' => null]) ?>"
                       class="btn btn-sm <?= (!empty($isAllTeamsMode) && empty($selectedSalesManagerId)) ? 'btn-primary shadow-sm' : 'btn-white bg-white border text-primary' ?> text-nowrap d-inline-flex align-items-center gap-1"
                       style="height: 34px; border-radius: 8px; font-size: 12.5px; font-weight: 600; padding: 0 12px;">
                        <i class="fa-solid fa-layer-group"></i>
                        <span>All Teams</span>
                    </a>
                <?php endif; ?>

                <?php if (!empty($isGlobalTeamViewer) && !empty($salesManagers)): ?>
                    <div class="input-group input-group-sm flex-nowrap" style="width: auto;">
                        <span class="input-group-text bg-white text-muted fw-semibold text-nowrap" style="height: 34px; border-radius: 8px 0 0 8px; font-size: 12px; border-color: #cbd5e1;">
                            <i class="fa-solid fa-user-tie text-primary me-1"></i> Sales Manager
                        </span>
                        <select id="managerSelect" class="form-select form-select-sm fw-semibold" style="height: 34px; min-width: 170px; max-width: 230px; border-radius: 0 8px 8px 0; font-size: 12.5px; border-color: #cbd5e1;" onchange="window.location.href=this.value;">
                            <option value="<?= buildTeamUrl(['sales_manager_id' => null, 'team_id' => 'all', 'member_id' => null, 'assigned_user' => null]) ?>" <?= empty($selectedSalesManagerId) ? 'selected' : '' ?>>
                                All Sales Managers
                            </option>
                            <?php foreach ($salesManagers as $sm): ?>
                                <option value="<?= buildTeamUrl(['sales_manager_id' => $sm['id'], 'team_id' => 'all', 'member_id' => null, 'assigned_user' => null]) ?>" <?= (!empty($selectedSalesManagerId) && (int)$selectedSalesManagerId === (int)$sm['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sm['name']) ?> (<?= count($sm['team_ids']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if (!empty($allTeams)): ?>
                    <div class="input-group input-group-sm flex-nowrap" style="width: auto;">
                        <span class="input-group-text bg-white text-muted fw-semibold text-nowrap" style="height: 34px; border-radius: 8px 0 0 8px; font-size: 12px; border-color: #cbd5e1;">
                            <i class="fa-solid fa-building text-primary me-1"></i> Team
                        </span>
                        <select id="teamSelect" class="form-select form-select-sm fw-semibold" style="height: 34px; min-width: 170px; max-width: 230px; border-radius: 0 8px 8px 0; font-size: 12.5px; border-color: #cbd5e1;" onchange="window.location.href=this.value;">
                            <?php if (!empty($showAllTeamsBtn)): ?>
                                <option value="<?= buildTeamUrl(['team_id' => 'all', 'member_id' => null, 'assigned_user' => null]) ?>" <?= !empty($isAllTeamsMode) ? 'selected' : '' ?>>
                                    All Teams (<?= count($allTeams) ?>)
                                </option>
                            <?php endif; ?>
                            <?php foreach ($allTeams as $t): ?>
                                <option value="<?= buildTeamUrl(['team_id' => $t['id'], 'member_id' => null, 'assigned_user' => null]) ?>" <?= (empty($isAllTeamsMode) && (int)$t['id'] === (int)$teamId) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="input-group input-group-sm flex-nowrap" style="width: auto;">
                    <span class="input-group-text bg-white text-muted fw-semibold text-nowrap" style="height: 34px; border-radius: 8px 0 0 8px; font-size: 12px; border-color: #cbd5e1;">
                        <i class="fa-solid fa-user text-info me-1"></i> Member
                    </span>
                    <select id="memberSelect" class="form-select form-select-sm fw-semibold" style="height: 34px; min-width: 190px; max-width: 260px; border-radius: 0 8px 8px 0; font-size: 12.5px; border-color: #cbd5e1;" onchange="window.location.href=this.value;">
                        <option value="<?= buildTeamUrl(['member_id' => null, 'assigned_user' => null]) ?>" <?= empty($selectedMemberId) ? 'selected' : '' ?>>
                            All Members (Aggregate)
                        </option>
                        <optgroup label="Team Members">
                            <?php foreach ($teamMembers as $mem): ?>
                                <option value="<?= buildTeamUrl(['member_id' => $mem['user_id'], 'assigned_user' => $mem['user_id']]) ?>" <?= (int)($mem['user_id'] ?? 0) === (int)$selectedMemberId ? 'selected' : '' ?>>
                                    <?= (!empty($mem['is_team_leader']) ? '[Leader] ' : '') . htmlspecialchars($mem['full_name']) . (!empty($mem['role_name']) ? ' (' . htmlspecialchars($mem['role_name']) . ')' : '') ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>
            </div>

            <!-- Right: Date Range Presets -->
            <div class="d-flex flex-wrap align-items-center gap-1">
                <a href="<?= buildTeamUrl(['preset' => 'today', 'from_date' => null, 'to_date' => null]) ?>" 
                   class="btn btn-sm <?= ($activePreset === 'today') ? 'btn-primary shadow-sm' : 'btn-white bg-white border text-secondary' ?> text-nowrap" 
                   style="height: 34px; line-height: 24px; border-radius: 8px; font-size: 12px; padding: 4px 11px;">
                    <i class="fa-solid fa-calendar-day me-1"></i> Today
                </a>
                <a href="<?= buildTeamUrl(['preset' => 'yesterday', 'from_date' => null, 'to_date' => null]) ?>" 
                   class="btn btn-sm <?= ($activePreset === 'yesterday') ? 'btn-primary shadow-sm' : 'btn-white bg-white border text-secondary' ?> text-nowrap" 
                   style="height: 34px; line-height: 24px; border-radius: 8px; font-size: 12px; padding: 4px 11px;">
                    <i class="fa-solid fa-clock-rotate-left me-1"></i> Yesterday
                </a>
                <a href="<?= buildTeamUrl(['preset' => 'this_month', 'from_date' => null, 'to_date' => null]) ?>" 
                   class="btn btn-sm <?= ($activePreset === 'this_month') ? 'btn-primary shadow-sm' : 'btn-white bg-white border text-secondary' ?> text-nowrap" 
                   style="height: 34px; line-height: 24px; border-radius: 8px; font-size: 12px; padding: 4px 11px;">
                    <i class="fa-solid fa-calendar-check me-1"></i> This Month
                </a>
                <a href="<?= buildTeamUrl(['preset' => 'all_time', 'from_date' => null, 'to_date' => null]) ?>" 
                   class="btn btn-sm <?= ($activePreset === 'all_time') ? 'btn-primary shadow-sm' : 'btn-white bg-white border text-secondary' ?> text-nowrap" 
                   style="height: 34px; line-height: 24px; border-radius: 8px; font-size: 12px; padding: 4px 11px;">
                    <i class="fa-solid fa-infinity me-1"></i> All Time
                </a>
                <button type="button" class="btn btn-sm <?= ($activePreset === 'custom') ? 'btn-primary shadow-sm' : 'btn-outline-primary bg-white' ?> text-nowrap" id="toggleCustomRange" style="height: 34px; border-radius: 8px; font-size: 12px; padding: 4px 11px;">
                    <i class="fa-solid fa-sliders me-1"></i> Custom Range
                </button>
            </div>
        </div>

        <!-- Feedback alert when individual executive is selected -->
        <?php if (!empty($selectedMemberId) && !empty($selectedMember)): ?>
            <div class="alert alert-primary py-2 px-3 mt-2 mb-0 d-flex align-items-center justify-content-between flex-wrap gap-2" style="border-radius: 8px;">
                <div class="d-flex align-items-center gap-2">
                    <div>
                        <strong><?= htmlspecialchars($selectedMember['full_name']) ?></strong>
                        <span class="ms-1 opacity-75" style="font-size: 12px;">(<?= htmlspecialchars($selectedMember['role_name'] ?? 'Team Member') ?>)</span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-2">Executive Drill-Down</span>
                    </div>
                </div>
                <a href="<?= buildTeamUrl(['member_id' => null, 'assigned_user' => null]) ?>" class="btn btn-outline-primary btn-sm px-2.5 py-0.5" style="font-size: 11.5px; border-radius: 6px;">
                    <i class="fa-solid fa-users me-1"></i> Switch to All Members Aggregate
                </a>
            </div>
        <?php endif; ?>

        <!-- Collapsible Custom Filter Bar: ONLY From Date and To Date -->
        <div id="customRangeSection" style="display: <?= ($activePreset === 'custom') ? 'block' : 'none' ?>;" class="border-top mt-3 pt-3">
            <form action="<?= $base ?>/team-dashboard" method="GET" class="row g-2 align-items-end">
                <?php if (!empty($canSwitchTeam)): ?>
                    <input type="hidden" name="team_id" value="<?= !empty($isAllTeamsMode) ? 'all' : (int)$teamId ?>">
                <?php endif; ?>
                <?php if (!empty($selectedSalesManagerId)): ?>
                    <input type="hidden" name="sales_manager_id" value="<?= (int)$selectedSalesManagerId ?>">
                <?php endif; ?>
                <?php if (!empty($selectedMemberId)): ?>
                    <input type="hidden" name="member_id" value="<?= (int)$selectedMemberId ?>">
                    <input type="hidden" name="assigned_user" value="<?= (int)$selectedMemberId ?>">
                <?php endif; ?>

                <div class="col-12 col-sm-5 col-md-4">
                    <label class="form-label small text-muted mb-1 fw-bold">From Date</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="<?= htmlspecialchars($fromDate ?? '') ?>" required style="border-radius: 6px;">
                </div>
                <div class="col-12 col-sm-5 col-md-4">
                    <label class="form-label small text-muted mb-1 fw-bold">To Date</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="<?= htmlspecialchars($toDate ?? '') ?>" required style="border-radius: 6px;">
                </div>
                <div class="col-12 col-sm-2 col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100" style="border-radius: 6px; height: 31px;">
                        <i class="fa-solid fa-check me-1"></i> Apply Filter
                    </button>
                </div>
            </form>
        </div>

    </div>

    <?php
    // Helper to generate lead-lists URL with team and optional member filter
    $GLOBALS['dashboard_team_id'] = $teamId ?? null;
    $GLOBALS['dashboard_selected_member_id'] = $selectedMemberId ?? null;

    if (!function_exists('leadListUrl')) {
        function leadListUrl($extraParams = []) {
            $tId = $GLOBALS['dashboard_team_id'] ?? null;
            $mId = $GLOBALS['dashboard_selected_member_id'] ?? null;
            $params = [];
            if (!empty($tId)) {
                $params['team_id'] = $tId;
            }
            if (!empty($mId)) {
                $params['assigned_user'] = $mId;
            }
            foreach ($extraParams as $k => $v) {
                if ($v !== null && $v !== '') {
                    $params[$k] = $v;
                }
            }
            $b = $GLOBALS['base_url'] ?? '';
            return $b . '/lead-lists?' . http_build_query($params);
        }
    }

    $leadDateFilter = [];
    if (!empty($fromDate) && !empty($toDate)) {
        $leadDateFilter = [
            'from_date' => $fromDate,
            'to_date'   => $toDate,
            'date_type' => 'created_at'
        ];
    }
    ?>

    <!-- 16 CORE SERIAL METRICS CARDS (Clean modern executive styling, no right icon badges) -->
    <div class="row g-2 mb-3 align-items-stretch">

        <!-- 1. Total / Period Leads Assigned -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= leadListUrl($leadDateFilter) ?>" class="stat-card-link">
                <div class="stat-card accent-blue shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <span class="stat-label"><i class="fa-solid fa-users"></i> <?= !empty($hasFilter) ? 'Period Leads Assigned' : 'Total Leads Assigned' ?></span>
                    <div class="d-flex align-items-baseline gap-1 mt-1">
                        <span class="stat-value"><?= number_format($totalLeadsAssigned ?? 0) ?></span>
                    </div>
                    <span class="card-hint"><?= !empty($hasFilter) ? 'All-Time: ' . number_format($allTimeLeadsAssigned ?? 0) . ' &rarr;' : 'All team assigned leads &rarr;' ?></span>
                </div>
            </a>
        </div>

        <!-- 2. Total Leads Contacted (with % Contacted) -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= leadListUrl(array_merge(['filter_card' => 'contacted'], $leadDateFilter)) ?>" class="stat-card-link">
                <div class="stat-card accent-green shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <span class="stat-label"><i class="fa-solid fa-phone-volume"></i> <?= !empty($hasFilter) ? 'Period Contacted' : 'Total Leads Contacted' ?></span>
                    <div class="d-flex align-items-baseline gap-1 mt-1">
                        <span class="stat-value"><?= number_format($totalLeadsContacted ?? 0) ?></span>
                        <span class="stat-pill-badge">
                            <?= $totalContactedPct ?? 0 ?>%
                        </span>
                    </div>
                    <span class="card-hint"><?= !empty($hasFilter) ? 'All-Time: ' . number_format($allTimeLeadsContacted ?? 0) . ' &rarr;' : 'Contacted leads &rarr;' ?></span>
                </div>
            </a>
        </div>

        <!-- 3. Total Not Contacted -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= leadListUrl(array_merge(['filter_card' => 'not_contacted'], $leadDateFilter)) ?>" class="stat-card-link">
                <div class="stat-card accent-amber shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <span class="stat-label"><i class="fa-solid fa-phone-slash"></i> <?= !empty($hasFilter) ? 'Period Not Contacted' : 'Total Not Contacted' ?></span>
                    <div class="d-flex align-items-baseline gap-1 mt-1">
                        <span class="stat-value"><?= number_format($totalNotContacted ?? 0) ?></span>
                        <span class="stat-pill-badge">
                            <?= round(100 - ($totalContactedPct ?? 0), 1) ?>%
                        </span>
                    </div>
                    <span class="card-hint"><?= !empty($hasFilter) ? 'All-Time: ' . number_format($allTimeNotContacted ?? 0) . ' &rarr;' : 'Pending initial contact &rarr;' ?></span>
                </div>
            </a>
        </div>

        <!-- 4. Today Leads Assigned -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= leadListUrl(['date' => date('Y-m-d'), 'date_type' => 'assigned_at']) ?>" class="stat-card-link">
                <div class="stat-card accent-indigo shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <span class="stat-label"><i class="fa-solid fa-user-plus"></i> Today Leads Assigned</span>
                    <div class="d-flex align-items-baseline gap-1 mt-1">
                        <span class="stat-value"><?= number_format($todayLeadsAssigned ?? 0) ?></span>
                    </div>
                    <span class="card-hint">Assigned today (<?= date('d M') ?>) &rarr;</span>
                </div>
            </a>
        </div>

        <!-- 5. Today Leads Contacted -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= leadListUrl(['filter_card' => 'contacted', 'date' => date('Y-m-d')]) ?>" class="stat-card-link">
                <div class="stat-card accent-emerald shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <span class="stat-label"><i class="fa-solid fa-headset"></i> Today Leads Contacted</span>
                    <div class="d-flex align-items-baseline gap-1 mt-1">
                        <span class="stat-value"><?= number_format($todayLeadsContacted ?? 0) ?></span>
                    </div>
                    <span class="card-hint">Follow-up complete today &rarr;</span>
                </div>
            </a>
        </div>

        <!-- 6. Today Not Contacted -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= leadListUrl(['filter_card' => 'not_contacted', 'date' => date('Y-m-d')]) ?>" class="stat-card-link">
                <div class="stat-card accent-orange shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <span class="stat-label"><i class="fa-solid fa-user-clock"></i> Today Not Contacted</span>
                    <div class="d-flex align-items-baseline gap-1 mt-1">
                        <span class="stat-value"><?= number_format($todayNotContacted ?? 0) ?></span>
                    </div>
                    <span class="card-hint">Remaining today leads &rarr;</span>
                </div>
            </a>
        </div>

        <!-- 7. Total Follow-Up Complete -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= leadListUrl(array_merge(['filter_card' => 'contacted'], $leadDateFilter)) ?>" class="stat-card-link">
                <div class="stat-card accent-teal shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <span class="stat-label"><i class="fa-solid fa-clipboard-check"></i> Total Follow-Up Complete</span>
                    <div class="d-flex align-items-baseline gap-1 mt-1">
                        <span class="stat-value"><?= number_format($totalFollowUpComplete ?? 0) ?></span>
                    </div>
                    <span class="card-hint">All completed follow-ups &rarr;</span>
                </div>
            </a>
        </div>

        <!-- 8. Total Follow-Up Missing -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= leadListUrl(array_merge(['filter_card' => 'missing_followup'], $leadDateFilter)) ?>" class="stat-card-link">
                <div class="stat-card accent-red shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <span class="stat-label"><i class="fa-solid fa-calendar-xmark"></i> <?= !empty($hasFilter) ? 'Period Follow-Up Missing' : 'Total Follow-Up Missing' ?></span>
                    <div class="d-flex align-items-baseline gap-1 mt-1">
                        <span class="stat-value text-danger"><?= number_format($totalFollowUpMissing ?? 0) ?></span>
                    </div>
                    <span class="card-hint"><?= !empty($hasFilter) ? 'All-Time: ' . number_format($allTimeFollowUpMissing ?? 0) . ' &rarr;' : 'Overdue follow-ups &rarr;' ?></span>
                </div>
            </a>
        </div>

        <!-- 9. Today Follow-Ups (Scheduled for Today) -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= leadListUrl(['filter_card' => 'today_follow_up']) ?>" class="stat-card-link">
                <div class="stat-card accent-sky shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <span class="stat-label"><i class="fa-solid fa-calendar-day"></i> Today Follow-Ups</span>
                    <div class="d-flex align-items-baseline gap-1 mt-1">
                        <span class="stat-value"><?= number_format($todayFollowUps ?? 0) ?></span>
                    </div>
                    <span class="card-hint">Scheduled for today (<?= date('d M') ?>) &rarr;</span>
                </div>
            </a>
        </div>

        <!-- 10. Today Follow-Up Complete -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= leadListUrl(['filter_card' => 'today_follow_up_complete']) ?>" class="stat-card-link">
                <div class="stat-card accent-cyan shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <span class="stat-label"><i class="fa-solid fa-circle-check"></i> Today Follow-Up Complete</span>
                    <div class="d-flex align-items-baseline gap-1 mt-1">
                        <span class="stat-value"><?= number_format($todayFollowUpComplete ?? 0) ?></span>
                    </div>
                    <span class="card-hint">Completed of today &rarr;</span>
                </div>
            </a>
        </div>

        <!-- 11. Remaining Today Follow-Up -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= leadListUrl(['filter_card' => 'pending_follow_ups']) ?>" class="stat-card-link">
                <div class="stat-card accent-rose shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <span class="stat-label"><i class="fa-solid fa-hourglass-half"></i> Remaining Today Follow-Up</span>
                    <div class="d-flex align-items-baseline gap-1 mt-1">
                        <span class="stat-value"><?= number_format($remainingTodayFollowUp ?? 0) ?></span>
                    </div>
                    <span class="card-hint">Pending of today &rarr;</span>
                </div>
            </a>
        </div>

        <!-- 12. Total Sales / Booked (Units) -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= leadListUrl(array_merge(['filter_card' => 'booked'], $leadDateFilter)) ?>" class="stat-card-link">
                <div class="stat-card accent-purple shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <span class="stat-label"><i class="fa-solid fa-bookmark"></i> Total Sales / Booked</span>
                    <div class="d-flex align-items-baseline gap-1 mt-1">
                        <span class="stat-value"><?= number_format($totalSalesBookedUnits ?? 0) ?></span>
                        <span class="stat-unit ms-1">Units</span>
                    </div>
                    <span class="card-hint">All booked units &rarr;</span>
                </div>
            </a>
        </div>

        <!-- 13. Target Bookings -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <div class="stat-card accent-fuchsia shadow-xs h-100">
                <span class="stat-label"><i class="fa-solid fa-bullseye"></i> Target</span>
                <div class="d-flex align-items-baseline gap-1 mt-1">
                    <span class="stat-value"><?= number_format($monthlyBookingTarget ?? 0) ?></span>
                    <span class="stat-unit ms-1">Units</span>
                </div>
                <span class="card-hint">From set-target (<?= htmlspecialchars($titlePeriod) ?>)</span>
            </div>
        </div>

        <!-- 14. Period / Current Month Booking -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= leadListUrl(array_merge(['filter_card' => 'booked'], $leadDateFilter)) ?>" class="stat-card-link">
                <div class="stat-card accent-violet shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <span class="stat-label"><i class="fa-solid fa-file-signature"></i> <?= !empty($hasFilter) ? 'Period Booking' : 'Current Month Booking' ?></span>
                    <div class="d-flex align-items-baseline gap-1 mt-1">
                        <span class="stat-value"><?= number_format($currentMonthBooking ?? 0) ?></span>
                        <span class="stat-unit ms-1">Units</span>
                        <?php if (!empty($currentMonthBookedLeads)): ?>
                            <span class="badge bg-light text-dark ms-1" style="font-size: 8.5px; border: 1px solid #e2e8f0;"><?= $currentMonthBookedLeads ?> Leads</span>
                        <?php endif; ?>
                        <?php if (!empty($monthlyBookingTarget) && (int)$monthlyBookingTarget > 0): ?>
                            <span class="stat-pill-badge ms-auto">
                                <?= $bookingAchievement ?? 0 ?>%
                            </span>
                        <?php endif; ?>
                    </div>
                    <span class="card-hint"><?= !empty($hasFilter) ? 'Booked in ' . htmlspecialchars($titlePeriod) : 'Booked in Current Month &rarr;' ?></span>
                </div>
            </a>
        </div>

        <!-- 15. Total Visited Complete -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= leadListUrl(['filter_card' => 'visited']) ?>" class="stat-card-link">
                <div class="stat-card accent-slate shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <span class="stat-label"><i class="fa-solid fa-location-dot"></i> Total Visited Complete</span>
                    <div class="d-flex align-items-baseline gap-1 mt-1">
                        <span class="stat-value"><?= number_format($totalVisitedComplete ?? 0) ?></span>
                    </div>
                    <span class="card-hint">All-time visited leads &rarr;</span>
                </div>
            </a>
        </div>

        <!-- 16. Period / This Month Visited -->
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-2">
            <a href="<?= leadListUrl(array_merge(['filter_card' => 'visited'], $leadDateFilter)) ?>" class="stat-card-link">
                <div class="stat-card accent-tangerine shadow-xs h-100">
                    <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <span class="stat-label"><i class="fa-solid fa-person-walking"></i> <?= !empty($hasFilter) ? 'Period Visited' : 'This Month Visited' ?></span>
                    <div class="d-flex align-items-baseline gap-1 mt-1">
                        <span class="stat-value"><?= number_format($thisMonthVisited ?? 0) ?></span>
                        <?php if (!empty($thisMonthVisitedLeads)): ?>
                            <span class="badge bg-light text-dark ms-1" style="font-size: 8.5px; border: 1px solid #e2e8f0;"><?= $thisMonthVisitedLeads ?> Leads</span>
                        <?php endif; ?>
                    </div>
                    <span class="card-hint"><?= !empty($hasFilter) ? 'Visits in ' . htmlspecialchars($titlePeriod) : 'Visits in Current Month &rarr;' ?></span>
                </div>
            </a>
        </div>

    </div>

    <!-- Team Members Sales & Performance Table (Replacing graph as requested) -->
    <div class="card__wrapper shadow-sm p-3 mb-4" style="border-radius: 12px; background: #ffffff;">
        <?php
        $membersList = isset($memberSalesPerformance['members']) 
            ? $memberSalesPerformance['members'] 
            : (is_array($memberSalesPerformance) ? $memberSalesPerformance : []);

        $perfTotals = isset($memberSalesPerformance['totals']) 
            ? $memberSalesPerformance['totals'] 
            : null;

        $totPeriodLeads   = $perfTotals['period_leads'] ?? 0;
        $totAllTimeLeads  = $perfTotals['all_time_leads'] ?? 0;
        $totGrossSales    = $perfTotals['gross_sales'] ?? 0;
        $totRefundSales   = $perfTotals['refund_sales'] ?? 0;
        $totNetSales      = $perfTotals['net_sales'] ?? 0;
        $totLandShareVal  = $perfTotals['land_share_value'] ?? 0;
        $totLandSharePaid = $perfTotals['land_share_paid'] ?? 0;
        $totTotalPaid     = $perfTotals['total_paid'] ?? 0;

        if (!$perfTotals && !empty($membersList)) {
            foreach ($membersList as $row) {
                $totPeriodLeads   += (int)($row['period_leads'] ?? 0);
                $totAllTimeLeads  += (int)($row['all_time_leads'] ?? 0);
                $totGrossSales    += (int)($row['gross_sales'] ?? 0);
                $totRefundSales   += (int)($row['refund_sales'] ?? 0);
                $totNetSales      += (int)($row['net_sales'] ?? 0);
                $totLandShareVal  += (float)($row['land_share_value'] ?? 0);
                $totLandSharePaid += (float)($row['land_share_paid'] ?? 0);
                $totTotalPaid     += (float)($row['total_paid'] ?? 0);
            }
        }
        $leadTotBase = ($hasFilter && $totPeriodLeads > 0) ? $totPeriodLeads : $totAllTimeLeads;
        $totConversionRate = isset($perfTotals['conversion_rate']) 
            ? $perfTotals['conversion_rate'] 
            : ($leadTotBase > 0 ? round(($totNetSales / $leadTotBase) * 100, 1) : 0);
        $totCollectionRate = isset($perfTotals['collection_rate']) 
            ? $perfTotals['collection_rate'] 
            : ($totLandShareVal > 0 ? round(($totLandSharePaid / $totLandShareVal) * 100, 1) : 0);
        ?>

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2 border-bottom">
            <div>
                <h5 class="fw-bold mb-1" style="color: #0f172a;">
                    <i class="fa-solid fa-users-gear text-primary me-2"></i>
                    <?= htmlspecialchars($teamName) ?> &mdash; Member Sales & Lead Performance
                </h5>
                <span class="text-muted fs-12">
                    <i class="fa-regular fa-calendar-check me-1"></i> Period: <strong><?= htmlspecialchars($titlePeriod) ?></strong>
                    &bull; Active Members: <strong><?= count($membersList) ?></strong>
                </span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 fs-11">
                    <i class="fa-solid fa-shield-halved me-1"></i> Refunds Deducted
                </span>
                <?php if (!empty($selectedMemberId)): ?>
                    <a href="<?= buildTeamUrl(['member_id' => null, 'assigned_user' => null]) ?>" class="btn btn-sm btn-outline-secondary py-1 px-2 fs-11" title="Clear member filter">
                        <i class="fa-solid fa-rotate-left me-1"></i> Reset Filter
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($selectedMemberId)): ?>
            <div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center justify-content-between" style="border-radius: 8px;">
                <span class="fs-12">
                    <i class="fa-solid fa-circle-info me-1"></i> Currently filtering top cards for <strong><?= htmlspecialchars($selectedMember['full_name'] ?? 'Member') ?></strong>. The table below lists all team members for full team comparison.
                </span>
                <a href="<?= buildTeamUrl(['member_id' => null, 'assigned_user' => null]) ?>" class="btn btn-xs btn-light border py-0 px-2 fs-11">
                    Show All Members
                </a>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                <thead style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #475569;">
                    <tr>
                        <th class="text-center py-2" style="width: 40px;">#</th>
                        <th class="py-2">Team Member</th>
                        <th class="text-center py-2">Leads Assigned</th>
                        <th class="text-center py-2">Sales / Bookings</th>
                        <th class="text-center py-2">Conversion</th>
                        <th class="text-end py-2">Total Land Share Value</th>
                        <th class="text-end py-2">Land Share Payment</th>
                        <th class="text-end py-2">Total Collected</th>
                        <th class="text-center py-2" style="width: 100px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($membersList)): ?>
                        <?php foreach ($membersList as $idx => $m): 
                            $isRowSelected = (!empty($selectedMemberId) && (int)$selectedMemberId === (int)$m['user_id']);
                            $memCollectionPct = ($m['land_share_value'] > 0) ? round(($m['land_share_paid'] / $m['land_share_value']) * 100, 1) : 0;
                        ?>
                            <tr style="<?= $isRowSelected ? 'background-color: #f0fdf4 !important; border-left: 3px solid #16a34a;' : '' ?>">
                                <td class="text-center text-muted fw-semibold py-3"><?= $idx + 1 ?></td>
                                <td class="py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm rounded-circle d-flex align-items-center justify-content-center text-white fw-bold me-2" style="width: 32px; height: 32px; font-size: 11px; background: <?= $m['is_team_leader'] ? '#d97706' : '#4f46e5' ?>;">
                                            <?= strtoupper(substr($m['full_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center gap-1">
                                                <span class="fw-bold text-dark"><?= htmlspecialchars($m['full_name']) ?></span>
                                                <?php if (!empty($m['is_team_leader'])): ?>
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-1.5 py-0.5" style="font-size: 10px;">Leader</span>
                                                <?php endif; ?>
                                                <?php if ($isRowSelected): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-1.5 py-0.5" style="font-size: 10px;">Active View</span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted d-block" style="font-size: 11px;">
                                                <?= htmlspecialchars($m['designation']) ?>
                                                <?php if (!empty($m['employee_id'])): ?>
                                                    &bull; ID: <?= htmlspecialchars($m['employee_id']) ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center py-3">
                                    <span class="fw-bold text-dark fs-14"><?= number_format($m['period_leads']) ?></span>
                                    <small class="text-muted d-block" style="font-size: 11px;">All-Time: <?= number_format($m['all_time_leads']) ?></small>
                                </td>
                                <td class="text-center py-3">
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        <span class="badge bg-success text-white px-2 py-1 fw-bold fs-12" title="Net Bookings">
                                            <?= (int)$m['net_sales'] ?> Units
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-center gap-1 mt-1">
                                        <small class="text-muted" style="font-size: 10.5px;">Gross: <?= (int)$m['gross_sales'] ?></small>
                                        <?php if ((int)$m['refund_sales'] > 0): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-1 py-0" style="font-size: 9.5px;" title="Refunded bookings deducted">
                                                -<?= (int)$m['refund_sales'] ?> Refund
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center py-3">
                                    <span class="badge bg-info-subtle text-primary border border-info-subtle px-2 py-1 fw-bold fs-12">
                                        <?= $m['conversion_rate'] ?>%
                                    </span>
                                    <small class="text-muted d-block" style="font-size: 10.5px;">Leads to Sale</small>
                                </td>
                                <td class="text-end py-3">
                                    <span class="fw-bold text-dark fs-13">৳ <?= number_format($m['land_share_value']) ?></span>
                                    <small class="text-muted d-block" style="font-size: 10.5px;">Agreed Value</small>
                                </td>
                                <td class="text-end py-3">
                                    <span class="fw-bold text-success fs-13">৳ <?= number_format($m['land_share_paid']) ?></span>
                                    <div class="d-flex align-items-center justify-content-end gap-1 mt-1">
                                        <div class="progress" style="height: 4px; width: 60px; background-color: #e2e8f0;">
                                            <div class="progress-bar bg-success" style="width: <?= min(100, $memCollectionPct) ?>%;"></div>
                                        </div>
                                        <span class="text-muted" style="font-size: 10.5px;"><?= $memCollectionPct ?>%</span>
                                    </div>
                                </td>
                                <td class="text-end py-3">
                                    <span class="fw-semibold text-secondary fs-13">৳ <?= number_format($m['total_paid']) ?></span>
                                    <small class="text-muted d-block" style="font-size: 10.5px;">All collections</small>
                                </td>
                                <td class="text-center py-3">
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($isRowSelected): ?>
                                            <a href="<?= buildTeamUrl(['member_id' => null, 'assigned_user' => null]) ?>" class="btn btn-outline-danger btn-sm py-0.5 px-2" title="Reset to All Members" style="font-size: 11px;">
                                                <i class="fa-solid fa-xmark"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= buildTeamUrl(['member_id' => $m['user_id']]) ?>" class="btn btn-outline-primary btn-sm py-0.5 px-2" title="Filter dashboard for this member" style="font-size: 11px;">
                                                <i class="fa-solid fa-filter"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?= leadListUrl(array_merge(['filter_card' => 'booked', 'assigned_user' => $m['user_id']], $leadDateFilter)) ?>" class="btn btn-outline-secondary btn-sm py-0.5 px-2" title="View Member Bookings" target="_blank" style="font-size: 11px;">
                                            <i class="fa-solid fa-file-contract"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="fa-solid fa-users-slash fs-2 mb-2 d-block text-secondary"></i>
                                No team members found for this team.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot style="background-color: #f1f5f9; border-top: 2px solid #cbd5e1; font-weight: 700;">
                    <tr>
                        <td colspan="2" class="py-2.5 px-3 text-uppercase" style="color: #334155; font-size: 12px;">
                            <i class="fa-solid fa-calculator me-1"></i> Team Total (Aggregate)
                        </td>
                        <td class="text-center py-2.5">
                            <span class="fs-13 text-dark"><?= number_format($totPeriodLeads) ?></span>
                            <small class="text-muted d-block fw-normal" style="font-size: 10.5px;">All: <?= number_format($totAllTimeLeads) ?></small>
                        </td>
                        <td class="text-center py-2.5">
                            <span class="badge bg-success text-white px-2 py-1 fs-12 fw-bold"><?= number_format($totNetSales) ?> Units</span>
                            <div class="mt-0.5">
                                <small class="text-muted fw-normal" style="font-size: 10px;">
                                    Gross: <?= $totGrossSales ?>
                                    <?php if ($totRefundSales > 0): ?>
                                        | <span class="text-danger">-<?= $totRefundSales ?> Refund</span>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </td>
                        <td class="text-center py-2.5">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5 fs-12 fw-bold">
                                <?= $totConversionRate ?>%
                            </span>
                        </td>
                        <td class="text-end py-2.5">
                            <span class="text-dark fs-13">৳ <?= number_format($totLandShareVal) ?></span>
                        </td>
                        <td class="text-end py-2.5">
                            <span class="text-success fs-13">৳ <?= number_format($totLandSharePaid) ?></span>
                            <small class="text-muted d-block fw-normal" style="font-size: 10px;"><?= $totCollectionRate ?>% Paid</small>
                        </td>
                        <td class="text-end py-2.5">
                            <span class="text-secondary fs-13">৳ <?= number_format($totTotalPaid) ?></span>
                        </td>
                        <td class="text-center py-2.5">
                            <span class="badge bg-light text-secondary border px-2 py-0.5 fs-10 fw-normal">Team View</span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mt-3 pt-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2 text-muted" style="font-size: 11.5px;">
            <span>
                <i class="fa-solid fa-circle-info text-primary me-1"></i>
                <strong>Net Sales</strong> = মোট বুকিং থেকে রিফান্ড/ক্যান্সেলড বুকিং বাদ দেওয়া হয়েছে। ল্যান্ড শেয়ার পেমেন্টে বুকিং মানি, রেজিস্ট্রেশন ও ল্যান্ড শেয়ার পেমেন্ট অন্তর্ভুক্ত।
            </span>
            <span>
                <i class="fa-solid fa-clock-rotate-left me-1"></i> ডাটা রিয়েল-টাইম ক্যালকুলেট করা হয়েছে
            </span>
        </div>
    </div>

</div>

<!-- UI Scripts -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Custom Range Toggle (shows ONLY From Date and To Date)
        const toggleBtn = document.getElementById('toggleCustomRange');
        const customRangeSection = document.getElementById('customRangeSection');
        if (toggleBtn && customRangeSection) {
            toggleBtn.addEventListener('click', function () {
                if (customRangeSection.style.display === 'none' || customRangeSection.style.display === '') {
                    customRangeSection.style.display = 'block';
                } else {
                    customRangeSection.style.display = 'none';
                }
            });
        }
    });
</script>

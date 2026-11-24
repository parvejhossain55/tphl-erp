<style>
    :root {
        --primary-green: #16a34a;
        --primary-green-hover: #15803d;
        --primary-green-dark: #166534;
        --accent-green-dim: #f0fdf4;
        --accent-green-light: #dcfce7;
        --accent-green-border: #bbf7d0;
        
        --text-dark: #1e293b;
        --text-muted: #64748b;
        --border-color: #e2e8f0;
        --card-bg: #ffffff;
        --page-bg: #f8fafc;
    }

    .tp-dashboard {
        font-family: var(--font-main, 'Inter', -apple-system, sans-serif);
        color: var(--text-dark);
        padding-bottom: 40px;
    }

    /* Top Page Header */
    .tp-header-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 24px 28px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }

    .tp-header-flex {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .tp-brand-wrap {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .tp-brand-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: linear-gradient(135deg, var(--primary-green), #22c55e);
        display: grid;
        place-items: center;
        font-size: 22px;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25);
        flex-shrink: 0;
    }

    .tp-header-title {
        font-size: 1.45rem;
        font-weight: 700;
        margin: 0;
        color: var(--text-dark);
        line-height: 1.2;
    }

    .tp-header-sub {
        font-size: 0.82rem;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-weight: 600;
        display: block;
        margin-bottom: 4px;
    }

    .tp-header-meta {
        text-align: right;
        font-size: 0.85rem;
        color: var(--text-muted);
    }

    .tp-header-meta strong {
        display: block;
        font-size: 1.05rem;
        color: var(--text-dark);
        font-weight: 600;
    }

    /* KPI Cards */
    .tp-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .tp-kpi-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 18px 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        display: flex;
        align-items: center;
        gap: 16px;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .tp-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
    }

    .tp-kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: grid;
        place-items: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .tp-kpi-green {
        background: var(--accent-green-dim);
        color: var(--primary-green);
        border: 1px solid var(--accent-green-border);
    }

    .tp-kpi-blue {
        background: #eff6ff;
        color: #2563eb;
        border: 1px solid #bfdbfe;
    }

    .tp-kpi-gold {
        background: #fffbeb;
        color: #d97706;
        border: 1px solid #fde68a;
    }

    .tp-kpi-purple {
        background: #f5f3ff;
        color: #7c3aed;
        border: 1px solid #ddd6fe;
    }

    .tp-kpi-info {
        flex: 1;
        min-width: 0;
    }

    .tp-kpi-label {
        font-size: 0.78rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-muted);
        margin-bottom: 3px;
        display: block;
    }

    .tp-kpi-value {
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--text-dark);
        line-height: 1.2;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .tp-kpi-sub {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-top: 3px;
    }

    /* Filter Bar */
    .tp-filter-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 18px 22px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }

    .tp-filter-form {
        display: flex;
        align-items: flex-end;
        gap: 12px;
        flex-wrap: wrap;
    }

    .tp-form-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .tp-form-group label {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-muted);
    }

    .tp-input {
        font-family: inherit;
        font-size: 0.85rem;
        font-weight: 500;
        color: var(--text-dark);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 7px 12px;
        background: #f8fafc;
        height: 38px;
        outline: none;
        transition: all 0.2s;
    }

    .tp-input:focus {
        border-color: var(--primary-green);
        box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.15);
        background: #ffffff;
    }

    .tp-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-left: auto;
        flex-wrap: wrap;
    }

    .btn-tp {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.82rem;
        font-weight: 600;
        padding: 8px 14px;
        border-radius: 8px;
        height: 38px;
        text-decoration: none !important;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid transparent;
        line-height: 1;
        white-space: nowrap;
    }

    .btn-tp-primary {
        background: var(--primary-green);
        color: #ffffff !important;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }

    .btn-tp-primary:hover {
        background: var(--primary-green-hover);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(22, 163, 74, 0.25);
    }

    .btn-tp-excel {
        background: #15803d;
        color: #ffffff !important;
    }

    .btn-tp-excel:hover {
        background: #166534;
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(21, 128, 61, 0.28);
    }

    .btn-tp-reset {
        background: #ffffff;
        color: var(--text-muted) !important;
        border-color: var(--border-color);
    }

    .btn-tp-reset:hover {
        background: #f1f5f9;
        color: var(--text-dark) !important;
    }

    /* Period indicator */
    .tp-period-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 14px;
        padding-top: 12px;
        border-top: 1px solid var(--border-color);
        font-size: 0.82rem;
        color: var(--text-muted);
        flex-wrap: wrap;
    }

    .tp-period-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: var(--accent-green-dim);
        color: var(--primary-green);
        border: 1px solid var(--accent-green-border);
        padding: 4px 10px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.78rem;
    }

    /* Main Table Panel */
    .tp-table-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }

    .tp-toolbar {
        padding: 16px 24px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        background: #f8fafc;
        flex-wrap: wrap;
    }

    .tp-toolbar-title {
        font-size: 0.92rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-dark);
        margin: 0;
    }

    .table-responsive {
        margin: 0;
        padding: 0;
    }

    .tp-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
        margin: 0;
    }

    .tp-table thead th {
        background: #f8fafc;
        color: var(--text-muted);
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 13px 14px;
        border-bottom: 1px solid var(--border-color);
        white-space: nowrap;
        text-align: center;
    }

    .tp-table thead th.text-start {
        text-align: left;
    }

    .tp-table tbody tr {
        border-bottom: 1px solid var(--border-color);
        transition: background 0.15s;
    }

    .tp-table tbody tr:hover {
        background: #f8fafc;
    }

    .tp-table tbody td {
        padding: 13px 14px;
        vertical-align: middle;
        text-align: center;
        color: var(--text-dark);
        white-space: nowrap;
    }

    .tp-table tbody td.text-start {
        text-align: left;
    }

    /* Rank Badges */
    .rank-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        font-weight: 700;
        font-size: 0.82rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .rank-1 {
        background: #fef3c7;
        color: #b45309;
        border: 1px solid #fde68a;
    }

    .rank-2 {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
    }

    .rank-3 {
        background: #ffedd5;
        color: #c2410c;
        border: 1px solid #fed7aa;
    }

    .rank-normal {
        background: #f8fafc;
        color: var(--text-muted);
        border: 1px solid var(--border-color);
        font-weight: 600;
    }

    .team-name-cell {
        font-weight: 700;
        color: var(--text-dark);
        font-size: 0.92rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-toggle-members {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--primary-green);
        background: var(--accent-green-dim);
        border: 1px solid var(--accent-green-border);
        padding: 3px 8px;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-toggle-members:hover {
        background: var(--accent-green-light);
    }

    .pct-badge {
        display: inline-block;
        padding: 3px 7px;
        border-radius: 4px;
        font-size: 0.76rem;
        font-weight: 700;
    }

    .pct-high {
        background: #dcfce7;
        color: #15803d;
    }

    .pct-mid {
        background: #fef9c3;
        color: #854d0e;
    }

    .pct-low {
        background: #fee2e2;
        color: #991b1b;
    }

    .score-badge {
        font-weight: 700;
        font-size: 0.92rem;
        color: var(--primary-green);
        background: var(--accent-green-dim);
        padding: 4px 10px;
        border-radius: 6px;
        border: 1px solid var(--accent-green-border);
        display: inline-block;
    }

    /* Sub table for members */
    .member-subrow {
        background: #fcfdfd !important;
        display: none;
    }

    .member-subrow.show {
        display: table-row !important;
    }

    .member-subbox {
        padding: 16px 20px;
        border-left: 3px solid var(--primary-green);
        background: #f8fafc;
        border-radius: 6px;
        margin: 6px 0;
    }

    .member-subtable {
        width: 100%;
        font-size: 0.8rem;
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        overflow: hidden;
    }

    .member-subtable th {
        background: #f1f5f9;
        padding: 8px 10px;
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--text-muted);
        text-align: center;
    }

    .member-subtable td {
        padding: 8px 10px;
        text-align: center;
        border-bottom: 1px solid #f1f5f9;
    }

    @media print {
        body {
            background: #ffffff !important;
        }
        .tp-filter-card, .btn-tp, .tp-actions, .btn-toggle-members {
            display: none !important;
        }
        .tp-table-card {
            border: 1px solid #cccccc !important;
            box-shadow: none !important;
        }
        .member-subrow {
            display: table-row !important;
        }
    }
</style>

<div class="tp-dashboard">
    <!-- Header Card -->
    <div class="tp-header-card">
        <div class="tp-header-flex">
            <div class="tp-brand-wrap">
                <div class="tp-brand-icon">
                    <i class="fa-solid fa-users-viewfinder"></i>
                </div>
                <div>
                    <span class="tp-header-sub">Sales Performance Analytics</span>
                    <h1 class="tp-header-title">Team Performance Summary & Rankings</h1>
                </div>
            </div>
            <div class="tp-header-meta">
                <strong><?= htmlspecialchars($loggedInUser['full_name'] ?? 'User') ?></strong>
                <span>Role: <?= htmlspecialchars($loggedInUser['role_name'] ?? 'Sales / Management') ?></span>
                <span class="d-block mt-1">Date: <?= date('d M Y') ?></span>
            </div>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="tp-kpi-grid">
        <!-- Total Teams -->
        <div class="tp-kpi-card">
            <div class="tp-kpi-icon tp-kpi-green">
                <i class="fa-solid fa-sitemap"></i>
            </div>
            <div class="tp-kpi-info">
                <span class="tp-kpi-label">Active Teams</span>
                <h3 class="tp-kpi-value"><?= number_format($kpis['total_teams'] ?? 0) ?></h3>
                <span class="tp-kpi-sub"><?= number_format($kpis['total_members'] ?? 0) ?> Total Members</span>
            </div>
        </div>

        <!-- Volume Achieved -->
        <div class="tp-kpi-card">
            <div class="tp-kpi-icon tp-kpi-blue">
                <i class="fa-solid fa-hand-holding-dollar"></i>
            </div>
            <div class="tp-kpi-info">
                <span class="tp-kpi-label">Volume Achieved</span>
                <h3 class="tp-kpi-value" title="<?= number_format((float)($kpis['total_volume_achieved'] ?? 0), 2) ?> Tk">
                    <?= number_format((float)($kpis['total_volume_achieved'] ?? 0) / 10000000, 2) ?> Cr
                </h3>
                <span class="tp-kpi-sub">
                    Target: <?= number_format((float)($kpis['total_volume_target'] ?? 0) / 10000000, 2) ?> Cr
                </span>
            </div>
        </div>

        <!-- Units Booked -->
        <div class="tp-kpi-card">
            <div class="tp-kpi-icon tp-kpi-purple">
                <i class="fa-solid fa-building-circle-check"></i>
            </div>
            <div class="tp-kpi-info">
                <span class="tp-kpi-label">Units Booked</span>
                <h3 class="tp-kpi-value"><?= number_format($kpis['total_units_sold'] ?? 0) ?></h3>
                <span class="tp-kpi-sub">Target: <?= number_format($kpis['total_units_target'] ?? 0) ?> Units</span>
            </div>
        </div>

        <!-- Top Team -->
        <div class="tp-kpi-card">
            <div class="tp-kpi-icon tp-kpi-gold">
                <i class="fa-solid fa-trophy"></i>
            </div>
            <div class="tp-kpi-info">
                <span class="tp-kpi-label">#1 Top Ranked Team</span>
                <h3 class="tp-kpi-value" style="font-size: 1.15rem;">
                    <?= htmlspecialchars($kpis['top_team']['team_name'] ?? 'N/A') ?>
                </h3>
                <span class="tp-kpi-sub">
                    Score: <strong><?= number_format((float)($kpis['top_team']['performance_score'] ?? 0), 2) ?></strong>
                </span>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="tp-filter-card">
        <form method="GET" action="<?= $GLOBALS['base_url'] ?>/team-performance" class="tp-filter-form">
            <!-- Year -->
            <div class="tp-form-group">
                <label for="fYear"><i class="fa-regular fa-calendar me-1"></i> Year</label>
                <select name="year" id="fYear" class="tp-input" style="min-width: 100px;">
                    <?php for ($y = 2024; $y <= date('Y') + 1; $y++): ?>
                        <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- Month -->
            <div class="tp-form-group">
                <label for="fMonth"><i class="fa-regular fa-calendar-days me-1"></i> Month</label>
                <select name="month" id="fMonth" class="tp-input" style="min-width: 130px;">
                    <?php 
                    $months = [
                        '01'=>'01 - Jan', '02'=>'02 - Feb', '03'=>'03 - Mar', '04'=>'04 - Apr', 
                        '05'=>'05 - May', '06'=>'06 - Jun', '07'=>'07 - Jul', '08'=>'08 - Aug', 
                        '09'=>'09 - Sep', '10'=>'10 - Oct', '11'=>'11 - Nov', '12'=>'12 - Dec'
                    ];
                    foreach ($months as $mCode => $mName): ?>
                        <option value="<?= $mCode ?>" <?= ((int)$mCode == (int)$month) ? 'selected' : '' ?>><?= $mName ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if (!empty($isGlobalViewer)): ?>
            <!-- Sales Manager (Cluster Head) Filter -->
            <div class="tp-form-group">
                <label for="fManager"><i class="fa-solid fa-user-tie me-1"></i> Cluster Head</label>
                <select name="sales_manager_id" id="fManager" class="tp-input" style="min-width: 175px;">
                    <option value="">All Cluster Heads</option>
                    <?php foreach (($salesManagers ?? []) as $sm): ?>
                        <option value="<?= (int)$sm['id'] ?>" data-teams="<?= htmlspecialchars(implode(',', $sm['team_ids'])) ?>" <?= (!empty($selectedSalesManagerId) && (int)$selectedSalesManagerId === (int)$sm['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sm['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- Team Filter -->
            <div class="tp-form-group">
                <label for="fTeam"><i class="fa-solid fa-users me-1"></i> Team</label>
                <select name="team_id" id="fTeam" class="tp-input" style="min-width: 160px;">
                    <option value="">All Teams (Company-Wide)</option>
                    <?php if (!empty($allTeams)): foreach ($allTeams as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= (!empty($selectedTeamId) && (int)$selectedTeamId === (int)$t['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['name']) ?>
                        </option>
                    <?php endforeach; endif; ?>
                </select>
            </div>

            <!-- Custom Date Range -->
            <div class="tp-form-group">
                <label for="dateFrom"><i class="fa-regular fa-calendar-check me-1"></i> From Date</label>
                <input type="date" name="date_from" id="dateFrom" class="tp-input" value="<?= htmlspecialchars($dateFrom ?? '') ?>">
            </div>

            <div class="tp-form-group">
                <label for="dateTo"><i class="fa-regular fa-calendar-xmark me-1"></i> To Date</label>
                <input type="date" name="date_to" id="dateTo" class="tp-input" value="<?= htmlspecialchars($dateTo ?? '') ?>">
            </div>

            <!-- Filter Buttons -->
            <div class="tp-actions">
                <button type="submit" class="btn-tp btn-tp-primary">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
                <a href="<?= $GLOBALS['base_url'] ?>/team-performance" class="btn-tp btn-tp-reset">
                    <i class="fa-solid fa-rotate-left"></i> Reset
                </a>
                <a href="<?= $GLOBALS['base_url'] ?>/team-performance-export?<?= http_build_query(array_merge($_GET, ['year' => $year, 'month' => $month, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'export' => 'excel'])) ?>" 
                   class="btn-tp btn-tp-excel" title="Download Excel Sheet">
                    <i class="fa-solid fa-file-excel"></i> Export Excel
                </a>
                <button type="button" class="btn-tp btn-tp-reset" onclick="window.print()" title="Print Summary">
                    <i class="fa-solid fa-print"></i> Print
                </button>
                <?php if (!empty($isGlobalViewer)): ?>
                <script>
                (function () {
                    var mgr = document.getElementById('fManager');
                    var team = document.getElementById('fTeam');
                    if (!mgr || !team) return;
                    function sync() {
                        var opt = mgr.options[mgr.selectedIndex];
                        var raw = mgr.value && opt ? (opt.getAttribute('data-teams') || '') : '';
                        var allowed = mgr.value ? raw.split(',').filter(Boolean) : null;
                        var cur = team.value;
                        var valid = false;
                        for (var i = 0; i < team.options.length; i++) {
                            var o = team.options[i];
                            if (!o.value) { o.disabled = false; o.hidden = false; continue; }
                            var ok = allowed === null || allowed.indexOf(o.value) !== -1;
                            o.disabled = !ok;
                            o.hidden = !ok;
                            if (o.value === cur && ok) valid = true;
                        }
                        if (cur && !valid) team.value = '';
                    }
                    mgr.addEventListener('change', sync);
                    if (mgr.value) sync();
                })();
                </script>
                <?php endif; ?>
            </div>
        </form>

        <div class="tp-period-bar">
            <div>
                <i class="fa-regular fa-clock me-1"></i> Analysis Period:
                <span class="tp-period-badge">
                    <?= htmlspecialchars($period['start'] ?? '') ?> &nbsp;—&nbsp; <?= htmlspecialchars($period['end'] ?? '') ?>
                </span>
                <?php if (empty($period['is_custom'])): ?>
                    <span class="ms-1" style="font-size:0.75rem; color:var(--text-muted);">(Standard 26th–25th monthly cycle)</span>
                <?php else: ?>
                    <span class="ms-1 text-primary" style="font-size:0.75rem; font-weight:600;">(Custom Date Range Active)</span>
                <?php endif; ?>
            </div>
            <div>
                Total Leads: <strong><?= number_format($kpis['total_leads'] ?? 0) ?></strong> &nbsp;|&nbsp; 
                Total Bookings: <strong><?= number_format($kpis['total_units_sold'] ?? 0) ?> Units</strong>
            </div>
        </div>
    </div>

    <!-- Team Performance Table Card -->
    <div class="tp-table-card">
        <div class="tp-toolbar">
            <h2 class="tp-toolbar-title">
                <i class="fa-solid fa-ranking-star me-2 text-warning"></i>Team Rankings & Achievement Summary
            </h2>
            <div class="d-flex align-items-center gap-2">
                <span style="font-size: 0.78rem; color:var(--text-muted);">
                    Rankings calculated using weighted analytical formula (Volume 45%, Units 20%, Visits 15%, Contact 10%, Coverage 10%)
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="tp-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">Rank</th>
                        <th class="text-start" style="min-width: 160px;">Team Name</th>
                        <th style="width: 80px;">Members</th>
                        <th style="width: 100px;">Assigned Leads</th>
                        <th style="width: 90px;">Total Calls</th>
                        <th style="width: 90px;">Contacted</th>
                        <th style="width: 90px;">Not Contacted</th>
                        <th style="width: 95px;">Contact %</th>
                        <th style="width: 120px;">Site Visits (T / Ach)</th>
                        <th style="width: 120px;">Bookings (T / Ach)</th>
                        <th style="width: 130px;">Volume Target</th>
                        <th style="width: 130px;">Volume Achieved</th>
                        <th style="width: 95px;">Vol. Ach %</th>
                        <th style="width: 100px;">Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($teams)): ?>
                        <tr>
                            <td colspan="14" style="padding: 50px 20px; text-align: center; color: var(--text-muted);">
                                <i class="fa-solid fa-folder-open" style="font-size: 2rem; color: #cbd5e1; display: block; margin-bottom: 10px;"></i>
                                <span style="font-weight: 600; font-size: 1rem; color: var(--text-dark);">No team performance records found</span>
                                <p style="font-size: 0.85rem; margin-top: 4px;">There are no active sales records or targets recorded for the selected period.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($teams as $t): 
                            $r = (int)($t['rank'] ?? 999);
                            $rankClass = match($r) {
                                1 => 'rank-1',
                                2 => 'rank-2',
                                3 => 'rank-3',
                                default => 'rank-normal'
                            };

                            $volPct = (float)($t['volume_ach_pct'] ?? 0);
                            $volBadgeClass = ($volPct >= 100) ? 'pct-high' : (($volPct >= 50) ? 'pct-mid' : 'pct-low');
                            $subRowId = 'team-sub-' . (int)($t['team_id'] ?? 0);
                        ?>
                            <tr>
                                <!-- Rank -->
                                <td>
                                    <span class="rank-badge <?= $rankClass ?>">#<?= $r ?></span>
                                </td>

                                <!-- Team Name -->
                                <td class="text-start">
                                    <div class="team-name-cell">
                                        <span><?= htmlspecialchars($t['team_name'] ?? 'General Team') ?></span>
                                    </div>
                                </td>

                                <!-- Members Count -->
                                <td>
                                    <button type="button" class="btn-toggle-members" onclick="toggleTeamMembers('<?= $subRowId ?>')">
                                        <i class="fa-solid fa-users"></i> <?= count($t['members'] ?? []) ?>
                                        <i class="fa-solid fa-chevron-down ms-1" style="font-size:0.65rem;"></i>
                                    </button>
                                </td>

                                <!-- Assigned Leads -->
                                <td><strong><?= number_format($t['total_leads'] ?? 0) ?></strong></td>

                                <!-- Total Calls -->
                                <td><?= number_format($t['total_follow_up_calls'] ?? 0) ?></td>

                                <!-- Contacted -->
                                <td style="color: #15803d; font-weight: 600;"><?= number_format($t['total_contacted'] ?? 0) ?></td>

                                <!-- Not Contacted -->
                                <td style="color: #dc2626;"><?= number_format($t['total_not_contacted'] ?? 0) ?></td>

                                <!-- Contacted % -->
                                <td>
                                    <span class="pct-badge <?= ((float)$t['contacted_pct'] >= 70) ? 'pct-high' : (((float)$t['contacted_pct'] >= 40) ? 'pct-mid' : 'pct-low') ?>">
                                        <?= number_format((float)($t['contacted_pct'] ?? 0), 1) ?>%
                                    </span>
                                </td>

                                <!-- Visits (Target / Ach) -->
                                <td>
                                    <span><?= number_format($t['visit_target'] ?? 0) ?> / <strong><?= number_format($t['visited'] ?? 0) ?></strong></span>
                                    <span class="d-block" style="font-size:0.75rem; color:var(--text-muted);">(<?= number_format((float)($t['visit_ach_pct'] ?? 0), 1) ?>%)</span>
                                </td>

                                <!-- Bookings (Target / Ach) -->
                                <td>
                                    <span><?= number_format($t['unit_target'] ?? 0) ?> / <strong><?= number_format($t['units_sold'] ?? 0) ?></strong></span>
                                    <span class="d-block" style="font-size:0.75rem; color:var(--text-muted);">(<?= number_format((float)($t['unit_ach_pct'] ?? 0), 1) ?>%)</span>
                                </td>

                                <!-- Volume Target -->
                                <td style="color:var(--text-muted);">
                                    <?= number_format((float)($t['volume_target'] ?? 0), 2) ?>
                                </td>

                                <!-- Volume Achieved -->
                                <td>
                                    <strong style="color:var(--primary-green); font-size: 0.9rem;">
                                        <?= number_format((float)($t['total_volume_achieved'] ?? 0), 2) ?>
                                    </strong>
                                </td>

                                <!-- Volume Ach % -->
                                <td>
                                    <span class="pct-badge <?= $volBadgeClass ?>">
                                        <?= number_format($volPct, 1) ?>%
                                    </span>
                                </td>

                                <!-- Performance Score -->
                                <td>
                                    <span class="score-badge">
                                        <?= number_format((float)($t['performance_score'] ?? 0), 2) ?>
                                    </span>
                                </td>
                            </tr>

                            <!-- Nested Team Member Breakdown Subrow -->
                            <?php if (!empty($t['members'])): ?>
                                <tr id="<?= $subRowId ?>" class="member-subrow">
                                    <td colspan="14" style="padding: 10px 24px; border-bottom: 2px solid var(--accent-green-border);">
                                        <div class="member-subbox">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <strong style="font-size:0.82rem; color: var(--primary-green-dark);">
                                                    <i class="fa-solid fa-user-group me-1"></i> <?= htmlspecialchars($t['team_name']) ?> — Individual Member Breakdown
                                                </strong>
                                                <span style="font-size:0.75rem; color:var(--text-muted);">
                                                    Showing <?= count($t['members']) ?> contributing members
                                                </span>
                                            </div>
                                            <table class="member-subtable">
                                                <thead>
                                                    <tr>
                                                        <th class="text-start">Member Name</th>
                                                        <th>Designation</th>
                                                        <th>Assigned Leads</th>
                                                        <th>Calls</th>
                                                        <th>Contacted</th>
                                                        <th>Visits</th>
                                                        <th>Booked Units</th>
                                                        <th>Volume Achieved (Tk)</th>
                                                        <th>Perf. Score</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($t['members'] as $m): ?>
                                                        <tr>
                                                            <td class="text-start" style="font-weight: 600;">
                                                                <?= htmlspecialchars($m['member_name'] ?? '—') ?>
                                                            </td>
                                                            <td style="color:var(--text-muted);">
                                                                <?= htmlspecialchars($m['level_designation'] ?? 'Sales Executive') ?>
                                                            </td>
                                                            <td><?= number_format($m['assigned_leads'] ?? 0) ?></td>
                                                            <td><?= number_format($m['total_follow_up_calls'] ?? 0) ?></td>
                                                            <td style="color: #15803d; font-weight:600;"><?= number_format($m['total_contacted'] ?? 0) ?></td>
                                                            <td><?= number_format($m['visited'] ?? 0) ?></td>
                                                            <td><strong><?= number_format($m['units_sold'] ?? 0) ?></strong></td>
                                                            <td style="font-weight: 600; color: var(--primary-green);">
                                                                <?= number_format((float)($m['total_volume_achieved'] ?? 0), 2) ?>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-light text-dark border">
                                                                    <?= number_format((float)($m['performance_score'] ?? 0), 2) ?>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleTeamMembers(rowId) {
    const row = document.getElementById(rowId);
    if (!row) return;
    if (row.classList.contains('show')) {
        row.classList.remove('show');
    } else {
        row.classList.add('show');
    }
}
</script>

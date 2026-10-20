<?php
/**
 * Team Follow Up Comparison Dashboard
 * -------------------------------------------------------------------------
 * Displays 1st to 5th follow-up performance rates, on-time vs overdue vs completed late,
 * team comparisons, salesperson drilldowns, and leads inspection.
 *
 * @var array $period
 * @var array $filters
 * @var array $data
 * @var array $cycleOptions
 * @var array $teams
 * @var array $salesUsers
 * @var array $projects
 */

if (!function_exists('tfu_h')) {
    function tfu_h($v): string {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }
    function tfu_n($v): string {
        return number_format((float) $v);
    }
    function tfu_pct($rate): string {
        if ($rate === null) return 'N/A';
        return number_format((float) $rate, 1) . '%';
    }
}

$grand = $data['grand'];
$topTeam = $data['top_team'] ?? null;
$teamsList = $data['teams'] ?? [];
$execsList = $data['executives'] ?? [];

$targetPct = 90.0;
$baseUrl = $GLOBALS['base_url'];
?>

<style>
:root {
    --tfu-bg-page: #f8fafc;
    --tfu-card-bg: #ffffff;
    --tfu-border: #e2e8f0;
    --tfu-text-main: #0f172a;
    --tfu-text-muted: #64748b;
    --tfu-green-bg: #ecfdf5;
    --tfu-green-text: #065f46;
    --tfu-green-border: #a7f3d0;
    --tfu-amber-bg: #fffbeb;
    --tfu-amber-text: #92400e;
    --tfu-amber-border: #fde68a;
    --tfu-red-text: #dc2626;
    --tfu-primary: #0f766e;
    --tfu-primary-dark: #115e59;
}

.tfu-page-wrap {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    color: var(--tfu-text-main);
    padding: 10px 4px 60px 4px;
}

/* Header & Filter Card */
.tfu-header-card {
    background: #ffffff;
    border: 1px solid var(--tfu-border);
    border-radius: 14px;
    padding: 20px 24px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}

.tfu-header-title {
    font-size: 22px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 4px 0;
    letter-spacing: -0.02em;
}

.tfu-header-subtitle {
    font-size: 13px;
    color: var(--tfu-text-muted);
    margin: 0;
}

.tfu-filter-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
    margin-top: 18px;
    padding-top: 16px;
    border-top: 1px solid #f1f5f9;
}

.tfu-btn-preset {
    font-size: 12.5px;
    font-weight: 500;
    padding: 6px 14px;
    border-radius: 20px;
    border: 1px solid var(--tfu-border);
    background: #ffffff;
    color: #475569;
    text-decoration: none;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.tfu-btn-preset:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
    color: #1e293b;
}
.tfu-btn-preset.active {
    background: var(--tfu-primary);
    border-color: var(--tfu-primary);
    color: #ffffff;
    box-shadow: 0 2px 4px rgba(15, 118, 110, 0.2);
}

/* 5 Top KPI Cards */
.tfu-kpi-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 16px;
    margin-bottom: 18px;
}
@media (max-width: 1200px) {
    .tfu-kpi-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 768px) {
    .tfu-kpi-grid { grid-template-columns: 1fr; }
}

.tfu-kpi-card {
    background: #ffffff;
    border: 1px solid var(--tfu-border);
    border-radius: 12px;
    padding: 18px 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.tfu-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.tfu-kpi-title {
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 8px;
}

.tfu-kpi-rate {
    font-size: 32px;
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.1;
    margin-bottom: 6px;
}
.tfu-kpi-rate.green { color: #059669; }
.tfu-kpi-rate.amber { color: #d97706; }
.tfu-kpi-rate.gray  { color: #64748b; }

.tfu-kpi-sub {
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 8px;
}

.tfu-kpi-footer {
    font-size: 11.5px;
    color: var(--tfu-text-muted);
    border-top: 1px dashed #e2e8f0;
    padding-top: 8px;
    margin-top: 4px;
}

/* Formula & Info Banner */
.tfu-info-banner {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 22px;
}
.tfu-info-title {
    font-size: 14.5px;
    font-weight: 700;
    color: #1e40af;
    margin: 0 0 6px 0;
}
.tfu-info-text {
    font-size: 12px;
    color: #3b82f6;
    margin: 0;
    line-height: 1.5;
}

/* Top Team Highlight Banner */
.tfu-topteam-banner {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 1px solid #86efac;
    border-radius: 12px;
    padding: 14px 20px;
    margin-bottom: 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.tfu-topteam-title {
    font-size: 14.5px;
    font-weight: 700;
    color: #166534;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Main Section: Team Follow-up Comparison */
.tfu-main-card {
    background: #ffffff;
    border: 1px solid var(--tfu-border);
    border-radius: 14px;
    padding: 24px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.03);
}

.tfu-main-hd {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
}

.tfu-main-title {
    font-size: 19px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 3px 0;
}

.tfu-main-subtitle {
    font-size: 13px;
    color: var(--tfu-text-muted);
    margin: 0;
}

.tfu-overall-summary {
    font-size: 15px;
    font-weight: 700;
    color: #0f766e;
    background: #f0fdfa;
    border: 1px solid #ccfbf1;
    padding: 6px 16px;
    border-radius: 8px;
}

/* Toolbar */
.tfu-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 18px;
    padding-bottom: 14px;
    border-bottom: 1px solid #f1f5f9;
}

/* Rounded Pill Toggle */
.tfu-toggle-group {
    display: inline-flex;
    background: #f1f5f9;
    padding: 4px;
    border-radius: 24px;
    gap: 2px;
}
.tfu-toggle-btn {
    padding: 6px 20px;
    font-size: 13px;
    font-weight: 600;
    border-radius: 20px;
    border: none;
    background: transparent;
    color: #64748b;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s ease;
}
.tfu-toggle-btn.active {
    background: var(--tfu-primary);
    color: #ffffff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

/* Search input */
.tfu-search-wrap {
    position: relative;
    width: 260px;
}
.tfu-search-input {
    width: 100%;
    padding: 7px 12px 7px 34px;
    font-size: 13px;
    border: 1px solid var(--tfu-border);
    border-radius: 20px;
    background: #ffffff;
    outline: none;
    transition: border-color 0.15s;
}
.tfu-search-input:focus {
    border-color: var(--tfu-primary);
}
.tfu-search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 13px;
}

/* Sort & Target */
.tfu-sort-select {
    padding: 6px 14px;
    font-size: 12.5px;
    font-weight: 500;
    border: 1px solid var(--tfu-border);
    border-radius: 8px;
    background: #ffffff;
    color: #334155;
    outline: none;
}
.tfu-target-badge {
    font-size: 12.5px;
    font-weight: 600;
    background: #f8fafc;
    border: 1px solid var(--tfu-border);
    padding: 6px 14px;
    border-radius: 8px;
    color: #475569;
}

/* Comparison Table */
.tfu-table-wrap {
    overflow-x: auto;
    border: 1px solid var(--tfu-border);
    border-radius: 10px;
}

.tfu-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 13px;
}

.tfu-table th {
    background: #f8fafc;
    color: #475569;
    font-weight: 700;
    font-size: 12px;
    padding: 12px 14px;
    text-align: center;
    border-bottom: 1px solid var(--tfu-border);
    white-space: nowrap;
}
.tfu-table th.text-start {
    text-align: left;
}

.tfu-table th .th-sub {
    display: block;
    font-size: 11px;
    font-weight: 500;
    color: #94a3b8;
    margin-top: 2px;
}

.tfu-table td {
    padding: 14px 12px;
    vertical-align: middle;
    text-align: center;
    border-bottom: 1px solid #f1f5f9;
}
.tfu-table td.text-start {
    text-align: left;
}
.tfu-table tbody tr:hover td {
    background: #fbfcfe;
}

/* Step Metric Pill */
.tfu-pill {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-width: 96px;
    padding: 6px 10px;
    border-radius: 8px;
    font-size: 13.5px;
    font-weight: 700;
    line-height: 1.2;
    transition: transform 0.1s ease;
}
.tfu-pill .pill-sub {
    font-size: 10.5px;
    font-weight: 500;
    opacity: 0.85;
    margin-top: 2px;
}

.tfu-pill.green {
    background: var(--tfu-green-bg);
    color: var(--tfu-green-text);
    border: 1px solid var(--tfu-green-border);
}
.tfu-pill.amber {
    background: var(--tfu-amber-bg);
    color: var(--tfu-amber-text);
    border: 1px solid var(--tfu-amber-border);
}
.tfu-pill.gray {
    background: #f8fafc;
    color: #64748b;
    border: 1px solid #e2e8f0;
}

/* Overdue / Late Cell */
.tfu-overdue-box {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    line-height: 1.25;
}
.tfu-open-overdue {
    font-size: 13px;
    font-weight: 700;
    color: #b45309;
}
.tfu-done-late {
    font-size: 11px;
    color: #64748b;
    margin-top: 2px;
}

/* Review button */
.tfu-btn-review {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    font-size: 12.5px;
    font-weight: 600;
    border: 1px solid var(--tfu-border);
    border-radius: 8px;
    background: #ffffff;
    color: #334155;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s ease;
    white-space: nowrap;
}
.tfu-btn-review:hover {
    background: var(--tfu-primary);
    border-color: var(--tfu-primary);
    color: #ffffff;
    box-shadow: 0 2px 4px rgba(15, 118, 110, 0.2);
}

/* Team Expandable details */
.tfu-expand-btn {
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    padding: 2px 6px;
    border-radius: 4px;
    margin-right: 6px;
}
.tfu-expand-btn:hover {
    background: #f1f5f9;
    color: #0f172a;
}
.tfu-exec-row {
    background: #fbfdff !important;
}
.tfu-exec-row td {
    padding: 10px 12px;
    border-bottom: 1px dashed #e2e8f0;
}

/* Footer Legend */
.tfu-legend {
    font-size: 11.5px;
    color: var(--tfu-text-muted);
    margin-top: 14px;
    line-height: 1.5;
}

/* Modal / Drawer */
.tfu-modal-backdrop {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(2px);
    z-index: 9998;
}
.tfu-modal-drawer {
    display: none;
    position: fixed;
    top: 0; right: 0; bottom: 0;
    width: 920px;
    max-width: 95vw;
    background: #ffffff;
    box-shadow: -4px 0 24px rgba(0,0,0,0.15);
    z-index: 9999;
    flex-direction: column;
    overflow: hidden;
}
.tfu-modal-drawer.active, .tfu-modal-backdrop.active {
    display: flex;
}
.tfu-modal-hd {
    padding: 16px 22px;
    border-bottom: 1px solid var(--tfu-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f8fafc;
}
.tfu-modal-bd {
    padding: 18px 22px;
    overflow-y: auto;
    flex: 1;
}
.tfu-modal-ft {
    padding: 12px 22px;
    border-top: 1px solid var(--tfu-border);
    background: #f8fafc;
    display: flex;
    justify-content: flex-end;
}
.tfu-modal-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 14px;
}
.tfu-tab-pill {
    padding: 5px 12px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 16px;
    border: 1px solid var(--tfu-border);
    background: #ffffff;
    color: #475569;
    cursor: pointer;
    text-decoration: none;
}
.tfu-tab-pill.active {
    background: var(--tfu-primary);
    border-color: var(--tfu-primary);
    color: #ffffff;
}
</style>

<div class="tfu-page-wrap">
    <!-- Top Header & Filter Form -->
    <div class="tfu-header-card">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:14px;">
            <div>
                <h1 class="tfu-header-title">1st-5th follow-up performance</h1>
                <p class="tfu-header-subtitle">
                    Due window: <strong><?= tfu_h($period['label']) ?></strong>
                    &middot; Uses the team, executive, and project filters below
                    &middot; Active Cycle: <?= tfu_h($period['cycle_label']) ?>
                </p>
            </div>
            <div style="display:flex; gap:10px; align-items:center;">
                <a href="<?= $baseUrl ?>/team-followup-comparison?<?= http_build_query(array_merge($filters, ['export' => 'csv'])) ?>" 
                   class="btn btn-outline-secondary btn-sm" style="border-radius:8px; font-weight:600;">
                    <i class="fa-solid fa-file-csv me-1"></i> Export CSV
                </a>
                <a href="<?= $baseUrl ?>/sales-performance" 
                   class="btn btn-outline-primary btn-sm" style="border-radius:8px; font-weight:600;">
                    <i class="fa-solid fa-ranking-star me-1"></i> Sales Intiligance
                </a>
            </div>
        </div>

        <!-- Filter Form -->
        <form method="GET" action="<?= $baseUrl ?>/team-followup-comparison" id="tfuFilterForm">
            <input type="hidden" name="view" value="<?= tfu_h($filters['view']) ?>">
            <input type="hidden" name="sort" value="<?= tfu_h($filters['sort']) ?>">

            <div class="tfu-filter-bar">
                <!-- Preset Quick Buttons -->
                <div style="display:flex; flex-wrap:wrap; gap:6px; align-items:center;">
                    <a href="<?= $baseUrl ?>/team-followup-comparison?preset=today&view=<?= tfu_h($filters['view']) ?>" 
                       class="tfu-btn-preset <?= ($filters['preset'] === 'today') ? 'active' : '' ?>">
                        <i class="fa-solid fa-bolt"></i> Today (Daily)
                    </a>
                    <a href="<?= $baseUrl ?>/team-followup-comparison?preset=weekly&view=<?= tfu_h($filters['view']) ?>" 
                       class="tfu-btn-preset <?= ($filters['preset'] === 'weekly') ? 'active' : '' ?>">
                        <i class="fa-solid fa-calendar-week"></i> Last 7 Days (Weekly)
                    </a>
                </div>

                <!-- Sales Cycle Dropdown -->
                <div style="min-width: 190px;">
                    <select name="cycle" class="form-select form-select-sm" style="border-radius:8px;" onchange="document.getElementById('tfuFilterForm').submit()">
                        <option value="">-- Select Sales Cycle --</option>
                        <?php foreach ($cycleOptions as $opt): ?>
                            <option value="<?= tfu_h($opt['value']) ?>" <?= ($filters['cycle'] === $opt['value']) ? 'selected' : '' ?>>
                                <?= tfu_h($opt['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Custom Range Inputs -->
                <div style="display:flex; align-items:center; gap:6px;">
                    <input type="date" name="from" value="<?= tfu_h($filters['from']) ?>" class="form-control form-control-sm" style="width:130px; border-radius:8px;" placeholder="From date">
                    <span style="color:#94a3b8; font-size:12px;">to</span>
                    <input type="date" name="to" value="<?= tfu_h($filters['to']) ?>" class="form-control form-control-sm" style="width:130px; border-radius:8px;" placeholder="To date">
                </div>

                <!-- Team Select -->
                <div style="min-width: 170px;">
                    <select name="team_id" class="form-select form-select-sm" style="border-radius:8px;">
                        <option value="">All Teams (<?= count($teams) ?>)</option>
                        <?php foreach ($teams as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= ($filters['team_id'] == $t['id']) ? 'selected' : '' ?>>
                                <?= tfu_h($t['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Project Select -->
                <div style="min-width: 160px;">
                    <select name="project_id" class="form-select form-select-sm" style="border-radius:8px;">
                        <option value="">All Projects (<?= count($projects) ?>)</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= ($filters['project_id'] == $p['id']) ? 'selected' : '' ?>>
                                <?= tfu_h($p['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-sm" style="background:var(--tfu-primary); border-color:var(--tfu-primary); border-radius:8px; font-weight:600; padding:6px 16px;">
                    <i class="fa-solid fa-filter me-1"></i> Apply
                </button>
                <a href="<?= $baseUrl ?>/team-followup-comparison" class="btn btn-light btn-sm" style="border-radius:8px;">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Top Team Highlight Banner -->
    <?php if ($topTeam && ($topTeam['metrics']['overall']['rate'] ?? 0) > 0): ?>
        <div class="tfu-topteam-banner">
            <div class="tfu-topteam-title">
                <i class="fa-solid fa-trophy" style="color:#eab308; font-size:18px;"></i>
                <span>Top Team Follow-up: <strong><?= tfu_h($topTeam['name']) ?></strong></span>
                <span class="badge bg-success" style="font-size:12px; margin-left:6px;">
                    <?= tfu_pct($topTeam['metrics']['overall']['rate']) ?> Weighted Overall
                </span>
            </div>
            <div style="font-size:13px; color:#15803d; font-weight:600;">
                <?= tfu_n($topTeam['metrics']['overall']['ontime']) ?> / <?= tfu_n($topTeam['metrics']['overall']['due']) ?> on time
                &middot; <?= tfu_n($topTeam['metrics']['overall']['overdue']) ?> open overdue
                &middot; <?= tfu_n($topTeam['exec_count']) ?> executives
            </div>
        </div>
    <?php endif; ?>

    <!-- 5 Top KPI Cards -->
    <div class="tfu-kpi-grid">
        <?php for ($s = 1; $s <= 5; $s++): 
            $st = $grand['steps'][$s];
            $rate = $st['rate'];
            $colorClass = ($rate === null) ? 'gray' : (($rate >= $targetPct) ? 'green' : 'amber');
            $suffix = ($s == 1) ? '1st' : (($s == 2) ? '2nd' : (($s == 3) ? '3rd' : $s . 'th'));
        ?>
            <div class="tfu-kpi-card">
                <div>
                    <div class="tfu-kpi-title"><?= $suffix ?> follow-up rate</div>
                    <div class="tfu-kpi-rate <?= $colorClass ?>">
                        <?= tfu_pct($rate) ?>
                    </div>
                    <div class="tfu-kpi-sub">
                        <?= tfu_n($st['ontime']) ?> / <?= tfu_n($st['due']) ?> on time
                    </div>
                </div>
                <div class="tfu-kpi-footer">
                    <strong><?= tfu_n($st['overdue']) ?></strong> overdue &middot; <?= tfu_n($st['late']) ?> completed late
                </div>
            </div>
        <?php endfor; ?>
    </div>

    <!-- Formula & Info Card -->
    <div class="tfu-info-banner">
        <h4 class="tfu-info-title">Rate = on-time logged follow-ups &divide; eligible follow-ups due &times; 100</h4>
        <p class="tfu-info-text">
            A logged call/message attempt counts once per lead and step. Future due items and leads closed before the due time are excluded. 
            Measured against the owner at due time &middot; Each step has its own due count &middot; N/A when due = 0 &middot; Customer response stays in the funnel metrics
        </p>
    </div>

    <!-- Section: Team Follow-up Comparison -->
    <div class="tfu-main-card">
        <div class="tfu-main-hd">
            <div>
                <h2 class="tfu-main-title">
                    <?= ($filters['view'] === 'individuals') ? 'Individual follow-up comparison' : 'Team follow-up comparison' ?>
                </h2>
                <p class="tfu-main-subtitle">
                    <?= ($filters['view'] === 'individuals') ? count($execsList) . ' sales executives' : count($teamsList) . ' sales teams' ?>
                    &middot; Compare the same five follow-up steps
                </p>
            </div>
            <div class="tfu-overall-summary">
                <?= tfu_pct($grand['overall']['rate']) ?> weighted overall &middot; <?= tfu_n($grand['overall']['overdue']) ?> overdue
            </div>
        </div>

        <!-- Controls Toolbar -->
        <div class="tfu-toolbar">
            <!-- Toggle between Teams and Individuals -->
            <div style="display:flex; align-items:center; gap:10px;">
                <span style="font-size:13px; font-weight:600; color:#475569;">Compare by</span>
                <div class="tfu-toggle-group">
                    <a href="<?= $baseUrl ?>/team-followup-comparison?<?= http_build_query(array_merge($filters, ['view' => 'teams'])) ?>" 
                       class="tfu-toggle-btn <?= ($filters['view'] === 'teams') ? 'active' : '' ?>">
                        Teams
                    </a>
                    <a href="<?= $baseUrl ?>/team-followup-comparison?<?= http_build_query(array_merge($filters, ['view' => 'individuals'])) ?>" 
                       class="tfu-toggle-btn <?= ($filters['view'] === 'individuals') ? 'active' : '' ?>">
                        Individuals
                    </a>
                </div>
            </div>

            <!-- Search, Sort & Target -->
            <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                <div class="tfu-search-wrap">
                    <i class="fa-solid fa-magnifying-glass tfu-search-icon"></i>
                    <input type="text" id="tfuSearchInput" class="tfu-search-input" 
                           placeholder="<?= ($filters['view'] === 'individuals') ? 'Search executive...' : 'Search team...' ?>">
                </div>

                <select id="tfuSortSelect" class="tfu-sort-select">
                    <option value="lowest">Sort: Lowest overall</option>
                    <option value="highest">Sort: Highest overall</option>
                    <option value="overdue">Sort: Most overdue</option>
                    <option value="leads">Sort: Most leads</option>
                    <option value="alpha">Sort: Name (A-Z)</option>
                </select>

                <div class="tfu-target-badge">
                    Target: <strong><?= number_format($targetPct, 0) ?>%</strong>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="tfu-table-wrap">
            <table class="tfu-table" id="tfuComparisonTable">
                <thead>
                    <tr>
                        <th class="text-start" style="width: 220px;">
                            <?= ($filters['view'] === 'individuals') ? 'Executive' : 'Team' ?>
                            <span class="th-sub"><?= ($filters['view'] === 'individuals') ? 'Team & Role' : 'Accountability owner' ?></span>
                        </th>
                        <th>1st follow-up<span class="th-sub">On time / due</span></th>
                        <th>2nd follow-up<span class="th-sub">On time / due</span></th>
                        <th>3rd follow-up<span class="th-sub">On time / due</span></th>
                        <th>4th follow-up<span class="th-sub">On time / due</span></th>
                        <th>5th follow-up<span class="th-sub">On time / due</span></th>
                        <th>Overall (weighted)<span class="th-sub">Total on time / due</span></th>
                        <th>Overdue / late<span class="th-sub">Open / done late</span></th>
                        <th style="width: 100px;">Review</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($filters['view'] === 'individuals'): ?>
                        <!-- INDIVIDUALS ROWS -->
                        <?php foreach ($execsList as $e): 
                            $m = $e['metrics'];
                            $ovRate = $m['overall']['rate'];
                        ?>
                            <tr class="tfu-row" 
                                data-name="<?= strtolower(tfu_h($e['name'] . ' ' . $e['team_name'])) ?>"
                                data-overall="<?= ($ovRate !== null) ? $ovRate : -1 ?>"
                                data-overdue="<?= (int)$m['overall']['overdue'] ?>"
                                data-leads="<?= (int)$e['total_leads'] ?>">
                                <td class="text-start">
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <div style="width:34px; height:34px; border-radius:50%; background:#e2e8f0; display:flex; align-items:center; justify-content:center; font-weight:700; color:#475569; font-size:12px; overflow:hidden;">
                                            <?php if (!empty($e['photo']) && file_exists(BASE_PATH . '/public/' . $e['photo'])): ?>
                                                <img src="<?= $baseUrl ?>/<?= tfu_h($e['photo']) ?>" alt="" style="width:100%; height:100%; object-fit:cover;">
                                            <?php else: ?>
                                                <?= strtoupper(substr($e['name'], 0, 1)) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <strong style="color:#1e293b; font-size:13.5px;"><?= tfu_h($e['name']) ?></strong>
                                            <div style="font-size:11.5px; color:#64748b;">
                                                <?= tfu_h($e['team_name']) ?> &middot; <?= tfu_n($e['total_leads']) ?> leads
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <?php for ($s = 1; $s <= 5; $s++): 
                                    $step = $m['steps'][$s];
                                    $rate = $step['rate'];
                                    $pillClass = ($rate === null) ? 'gray' : (($rate >= $targetPct) ? 'green' : 'amber');
                                ?>
                                    <td>
                                        <div class="tfu-pill <?= $pillClass ?>">
                                            <span><?= tfu_pct($rate) ?></span>
                                            <span class="pill-sub"><?= tfu_n($step['ontime']) ?> / <?= tfu_n($step['due']) ?></span>
                                        </div>
                                    </td>
                                <?php endfor; ?>

                                <!-- Overall (weighted) -->
                                <td>
                                    <?php 
                                    $ovPillClass = ($ovRate === null) ? 'gray' : (($ovRate >= $targetPct) ? 'green' : 'amber');
                                    ?>
                                    <div class="tfu-pill <?= $ovPillClass ?>">
                                        <span><?= tfu_pct($ovRate) ?></span>
                                        <span class="pill-sub"><?= tfu_n($m['overall']['ontime']) ?> / <?= tfu_n($m['overall']['due']) ?></span>
                                    </div>
                                </td>

                                <!-- Overdue / Late -->
                                <td>
                                    <div class="tfu-overdue-box">
                                        <span class="tfu-open-overdue"><?= tfu_n($m['overall']['overdue']) ?> open</span>
                                        <span class="tfu-done-late"><?= tfu_n($m['overall']['late']) ?> done late</span>
                                    </div>
                                </td>

                                <!-- Review -->
                                <td>
                                    <button type="button" class="tfu-btn-review" 
                                            onclick="openDrilldownModal('<?= tfu_h($e['name']) ?>', 0, <?= $e['id'] ?>)">
                                        See leads
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php else: ?>
                        <!-- TEAMS ROWS -->
                        <?php foreach ($teamsList as $t): 
                            $m = $t['metrics'];
                            $ovRate = $m['overall']['rate'];
                        ?>
                            <tr class="tfu-row" 
                                data-name="<?= strtolower(tfu_h($t['name'])) ?>"
                                data-overall="<?= ($ovRate !== null) ? $ovRate : -1 ?>"
                                data-overdue="<?= (int)$m['overall']['overdue'] ?>"
                                data-leads="<?= (int)$t['total_leads'] ?>"
                                id="team-row-<?= $t['id'] ?>">
                                <td class="text-start">
                                    <div style="display:flex; align-items:center;">
                                        <button type="button" class="tfu-expand-btn" onclick="toggleTeamMembers(<?= $t['id'] ?>)" title="View team executives">
                                            <i class="fa-solid fa-chevron-right" id="arrow-team-<?= $t['id'] ?>"></i>
                                        </button>
                                        <div>
                                            <strong style="color:#1e293b; font-size:14px;"><?= tfu_h($t['name']) ?></strong>
                                            <div style="font-size:11.5px; color:#64748b;">
                                                <?= tfu_n($t['exec_count']) ?> executives &middot; <?= tfu_n($t['total_leads']) ?> leads
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <?php for ($s = 1; $s <= 5; $s++): 
                                    $step = $m['steps'][$s];
                                    $rate = $step['rate'];
                                    $pillClass = ($rate === null) ? 'gray' : (($rate >= $targetPct) ? 'green' : 'amber');
                                ?>
                                    <td>
                                        <div class="tfu-pill <?= $pillClass ?>">
                                            <span><?= tfu_pct($rate) ?></span>
                                            <span class="pill-sub"><?= tfu_n($step['ontime']) ?> / <?= tfu_n($step['due']) ?></span>
                                        </div>
                                    </td>
                                <?php endfor; ?>

                                <!-- Overall (weighted) -->
                                <td>
                                    <?php 
                                    $ovPillClass = ($ovRate === null) ? 'gray' : (($ovRate >= $targetPct) ? 'green' : 'amber');
                                    ?>
                                    <div class="tfu-pill <?= $ovPillClass ?>">
                                        <span><?= tfu_pct($ovRate) ?></span>
                                        <span class="pill-sub"><?= tfu_n($m['overall']['ontime']) ?> / <?= tfu_n($m['overall']['due']) ?></span>
                                    </div>
                                </td>

                                <!-- Overdue / Late -->
                                <td>
                                    <div class="tfu-overdue-box">
                                        <span class="tfu-open-overdue"><?= tfu_n($m['overall']['overdue']) ?> open</span>
                                        <span class="tfu-done-late"><?= tfu_n($m['overall']['late']) ?> done late</span>
                                    </div>
                                </td>

                                <!-- Review -->
                                <td>
                                    <button type="button" class="tfu-btn-review" 
                                            onclick="openDrilldownModal('<?= tfu_h($t['name']) ?>', <?= $t['id'] ?>, 0)">
                                        See leads
                                    </button>
                                </td>
                            </tr>

                            <!-- Expandable Sub-rows for this team's executives -->
                            <?php 
                            $teamExecs = array_filter($execsList, fn($e) => $e['team_id'] === $t['id']);
                            foreach ($teamExecs as $te):
                                $tem = $te['metrics'];
                                $teOvRate = $tem['overall']['rate'];
                            ?>
                                <tr class="tfu-exec-row team-sub-<?= $t['id'] ?>" style="display:none;">
                                    <td class="text-start" style="padding-left:36px;">
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <i class="fa-solid fa-user" style="font-size:11px; color:#94a3b8;"></i>
                                            <div>
                                                <span style="font-weight:600; color:#334155; font-size:12.5px;"><?= tfu_h($te['name']) ?></span>
                                                <div style="font-size:11px; color:#94a3b8;"><?= tfu_h($te['role_name']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <?php for ($s = 1; $s <= 5; $s++): 
                                        $teStep = $tem['steps'][$s];
                                        $teRate = $teStep['rate'];
                                        $tePillClass = ($teRate === null) ? 'gray' : (($teRate >= $targetPct) ? 'green' : 'amber');
                                    ?>
                                        <td>
                                            <div class="tfu-pill <?= $tePillClass ?>" style="min-width:76px; padding:4px 6px; font-size:12px;">
                                                <span><?= tfu_pct($teRate) ?></span>
                                                <span class="pill-sub"><?= tfu_n($teStep['ontime']) ?>/<?= tfu_n($teStep['due']) ?></span>
                                            </div>
                                        </td>
                                    <?php endfor; ?>
                                    <td>
                                        <?php $teOvClass = ($teOvRate === null) ? 'gray' : (($teOvRate >= $targetPct) ? 'green' : 'amber'); ?>
                                        <div class="tfu-pill <?= $teOvClass ?>" style="min-width:76px; padding:4px 6px; font-size:12px;">
                                            <span><?= tfu_pct($teOvRate) ?></span>
                                            <span class="pill-sub"><?= tfu_n($tem['overall']['ontime']) ?>/<?= tfu_n($tem['overall']['due']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tfu-overdue-box">
                                            <span style="font-size:11.5px; font-weight:600; color:#b45309;"><?= tfu_n($tem['overall']['overdue']) ?> open</span>
                                            <span style="font-size:10.5px; color:#94a3b8;"><?= tfu_n($tem['overall']['late']) ?> late</span>
                                        </div>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" style="font-size:11px; padding:3px 8px; border-radius:6px;"
                                                onclick="openDrilldownModal('<?= tfu_h($te['name']) ?>', 0, <?= $te['id'] ?>)">
                                            Leads
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Legend Note at bottom -->
        <p class="tfu-legend">
            Green &ge; target 90% &middot; Amber below target &middot; N/A = no follow-up due &middot; Completed late is shown separately from open overdue activity.<br>
            Overall is weighted: total on-time attempts &divide; total due. Every percentage uses that step's own eligible due count. As of <?= date('d M Y') ?>.
        </p>
    </div>
</div>

<!-- Leads Drilldown Modal / Drawer -->
<div class="tfu-modal-backdrop" id="tfuModalBackdrop" onclick="closeDrilldownModal()"></div>
<div class="tfu-modal-drawer" id="tfuModalDrawer">
    <div class="tfu-modal-hd">
        <div>
            <h3 style="margin:0; font-size:17px; font-weight:700; color:#1e293b;" id="drillModalTitle">Leads Drilldown</h3>
            <div style="font-size:12px; color:#64748b; margin-top:3px;" id="drillModalSubtitle">Review follow-ups by step & timing status</div>
        </div>
        <button type="button" class="btn-close" onclick="closeDrilldownModal()" aria-label="Close"></button>
    </div>

    <!-- Quick Filter Tabs inside Modal -->
    <div style="padding: 12px 22px; background:#ffffff; border-bottom:1px solid #f1f5f9;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <!-- Step Tabs -->
            <div class="tfu-modal-tabs" id="drillStepTabs" style="margin-bottom:0;">
                <button type="button" class="tfu-tab-pill active" onclick="setDrillFilter('step', 0)">All Steps</button>
                <button type="button" class="tfu-tab-pill" onclick="setDrillFilter('step', 1)">1st Step</button>
                <button type="button" class="tfu-tab-pill" onclick="setDrillFilter('step', 2)">2nd Step</button>
                <button type="button" class="tfu-tab-pill" onclick="setDrillFilter('step', 3)">3rd Step</button>
                <button type="button" class="tfu-tab-pill" onclick="setDrillFilter('step', 4)">4th Step</button>
                <button type="button" class="tfu-tab-pill" onclick="setDrillFilter('step', 5)">5th Step</button>
            </div>

            <!-- Status Tabs -->
            <div class="tfu-modal-tabs" id="drillStatusTabs" style="margin-bottom:0;">
                <button type="button" class="tfu-tab-pill active" onclick="setDrillFilter('status', '')">All Statuses</button>
                <button type="button" class="tfu-tab-pill" onclick="setDrillFilter('status', 'overdue')" style="color:#b45309; border-color:#fde68a;">Overdue Only</button>
                <button type="button" class="tfu-tab-pill" onclick="setDrillFilter('status', 'late')">Completed Late</button>
                <button type="button" class="tfu-tab-pill" onclick="setDrillFilter('status', 'ontime')" style="color:#059669; border-color:#a7f3d0;">On-Time</button>
            </div>
        </div>
    </div>

    <div class="tfu-modal-bd" id="drillModalBody">
        <div style="text-align:center; padding:50px 20px; color:#64748b;">
            <i class="fa-solid fa-spinner fa-spin fa-2x"></i>
            <div style="margin-top:10px;">Loading leads data...</div>
        </div>
    </div>

    <div class="tfu-modal-ft">
        <button type="button" class="btn btn-secondary btn-sm" onclick="closeDrilldownModal()">Close</button>
    </div>
</div>

<script>
// Filter & search states
let currentDrillTeamId = 0;
let currentDrillExecId = 0;
let currentDrillStep = 0;
let currentDrillStatus = '';

// Toggle team members sub-rows
function toggleTeamMembers(teamId) {
    const rows = document.querySelectorAll('.team-sub-' + teamId);
    const arrow = document.getElementById('arrow-team-' + teamId);
    let isShowing = false;
    rows.forEach(r => {
        if (r.style.display === 'none' || r.style.display === '') {
            r.style.display = 'table-row';
            isShowing = true;
        } else {
            r.style.display = 'none';
        }
    });
    if (arrow) {
        arrow.className = isShowing ? 'fa-solid fa-chevron-down' : 'fa-solid fa-chevron-right';
    }
}

// Live client-side search filtering
document.getElementById('tfuSearchInput').addEventListener('input', function(e) {
    const q = e.target.value.toLowerCase().trim();
    const rows = document.querySelectorAll('#tfuComparisonTable tbody tr.tfu-row');
    rows.forEach(r => {
        const name = r.getAttribute('data-name') || '';
        const match = (!q || name.includes(q));
        r.style.display = match ? '' : 'none';
        // Hide sub-rows if parent is hidden
        const rowId = r.id;
        if (rowId && rowId.startsWith('team-row-')) {
            const tid = rowId.replace('team-row-', '');
            const subRows = document.querySelectorAll('.team-sub-' + tid);
            subRows.forEach(sr => sr.style.display = 'none');
            const arrow = document.getElementById('arrow-team-' + tid);
            if (arrow) arrow.className = 'fa-solid fa-chevron-right';
        }
    });
});

// Sort dropdown handling
document.getElementById('tfuSortSelect').addEventListener('change', function(e) {
    const sortMode = e.target.value;
    const tbody = document.querySelector('#tfuComparisonTable tbody');
    const rows = Array.from(tbody.querySelectorAll('tr.tfu-row'));

    rows.sort((a, b) => {
        if (sortMode === 'lowest') {
            return parseFloat(a.dataset.overall) - parseFloat(b.dataset.overall);
        } else if (sortMode === 'highest') {
            return parseFloat(b.dataset.overall) - parseFloat(a.dataset.overall);
        } else if (sortMode === 'overdue') {
            return parseInt(b.dataset.overdue) - parseInt(a.dataset.overdue);
        } else if (sortMode === 'leads') {
            return parseInt(b.dataset.leads) - parseInt(a.dataset.leads);
        } else if (sortMode === 'alpha') {
            return a.dataset.name.localeCompare(b.dataset.name);
        }
        return 0;
    });

    rows.forEach(r => {
        tbody.appendChild(r);
        const rowId = r.id;
        if (rowId && rowId.startsWith('team-row-')) {
            const tid = rowId.replace('team-row-', '');
            const subRows = document.querySelectorAll('.team-sub-' + tid);
            subRows.forEach(sr => tbody.appendChild(sr));
        }
    });
});

// Open Lead Drilldown Modal
function openDrilldownModal(title, teamId, execId) {
    currentDrillTeamId = teamId || 0;
    currentDrillExecId = execId || 0;
    currentDrillStep = 0;
    currentDrillStatus = '';

    document.getElementById('drillModalTitle').innerText = 'Leads: ' + title;
    document.getElementById('drillModalSubtitle').innerText = 'Viewing follow-up items for window: <?= tfu_h($period['label']) ?>';
    
    // Reset active tab pills
    document.querySelectorAll('#drillStepTabs .tfu-tab-pill').forEach((btn, idx) => {
        btn.classList.toggle('active', idx === 0);
    });
    document.querySelectorAll('#drillStatusTabs .tfu-tab-pill').forEach((btn, idx) => {
        btn.classList.toggle('active', idx === 0);
    });

    document.getElementById('tfuModalBackdrop').classList.add('active');
    document.getElementById('tfuModalDrawer').classList.add('active');

    loadDrilldownData();
}

function closeDrilldownModal() {
    document.getElementById('tfuModalBackdrop').classList.remove('active');
    document.getElementById('tfuModalDrawer').classList.remove('active');
}

function setDrillFilter(type, val) {
    if (type === 'step') {
        currentDrillStep = val;
        const btns = document.querySelectorAll('#drillStepTabs .tfu-tab-pill');
        btns.forEach((b, i) => b.classList.toggle('active', i === val));
    } else if (type === 'status') {
        currentDrillStatus = val;
        const btns = document.querySelectorAll('#drillStatusTabs .tfu-tab-pill');
        btns.forEach(b => {
            const isMatch = (val === '' && b.innerText.includes('All')) ||
                            (val === 'overdue' && b.innerText.includes('Overdue')) ||
                            (val === 'late' && b.innerText.includes('Late')) ||
                            (val === 'ontime' && b.innerText.includes('On-Time'));
            b.classList.toggle('active', isMatch);
        });
    }
    loadDrilldownData();
}

function loadDrilldownData() {
    const body = document.getElementById('drillModalBody');
    body.innerHTML = `
        <div style="text-align:center; padding:50px 20px; color:#64748b;">
            <i class="fa-solid fa-spinner fa-spin fa-2x"></i>
            <div style="margin-top:10px;">Loading leads data...</div>
        </div>
    `;

    const qs = new URLSearchParams({
        preset: '<?= tfu_h($filters['preset']) ?>',
        cycle: '<?= tfu_h($filters['cycle']) ?>',
        from: '<?= tfu_h($filters['from']) ?>',
        to: '<?= tfu_h($filters['to']) ?>',
        project_id: '<?= tfu_h($filters['project_id']) ?>',
        team_id: currentDrillTeamId,
        executive_id: currentDrillExecId,
        step: currentDrillStep,
        status: currentDrillStatus
    });

    fetch('<?= $baseUrl ?>/team-followup-comparison/leads-ajax?' + qs.toString())
        .then(res => res.json())
        .then(res => {
            if (!res || !res.rows || res.rows.length === 0) {
                body.innerHTML = `
                    <div style="text-align:center; padding:60px 20px; color:#94a3b8;">
                        <i class="fa-regular fa-folder-open fa-3x" style="margin-bottom:12px; opacity:0.6;"></i>
                        <h4 style="font-size:15px; font-weight:600; color:#475569; margin:0 0 4px 0;">No matching leads found</h4>
                        <p style="font-size:12.5px; margin:0;">Try switching step tabs or status filters above.</p>
                    </div>
                `;
                return;
            }

            let html = `
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; font-size:12.5px; color:#64748b;">
                    <span>Showing <strong>${res.returned}</strong> of <strong>${res.total}</strong> matching lead items</span>
                </div>
                <div class="table-responsive" style="border:1px solid #e2e8f0; border-radius:8px;">
                    <table class="table table-hover table-sm align-middle mb-0" style="font-size:12.5px;">
                        <thead class="table-light">
                            <tr>
                                <th>Lead</th>
                                <th>Phone</th>
                                <th>Project</th>
                                <th>Salesperson</th>
                                <th>Step</th>
                                <th>Due Date</th>
                                <th>Completed</th>
                                <th>Status</th>
                                <th style="width:60px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            res.rows.forEach(r => {
                let statusBadge = '';
                if (r.status === 'ontime') {
                    statusBadge = `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fa-solid fa-check me-1"></i>On-Time</span>`;
                } else if (r.status === 'late') {
                    statusBadge = `<span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1"><i class="fa-solid fa-clock me-1"></i>Completed Late</span>`;
                } else if (r.status === 'overdue') {
                    statusBadge = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="fa-solid fa-triangle-exclamation me-1"></i>${r.overdue_days}d Overdue</span>`;
                }

                html += `
                    <tr>
                        <td>
                            <strong style="color:#1e293b;">${escapeHtml(r.lead_name)}</strong>
                            <div style="font-size:11px; color:#94a3b8;">#${r.lead_id}</div>
                        </td>
                        <td>
                            <a href="tel:${escapeHtml(r.phone)}" style="text-decoration:none; color:#0f766e; font-weight:600;">
                                ${escapeHtml(r.phone)}
                            </a>
                        </td>
                        <td>${escapeHtml(r.project_name)}</td>
                        <td>
                            <div>${escapeHtml(r.exec_name)}</div>
                            <div style="font-size:10.5px; color:#94a3b8;">${escapeHtml(r.team_name)}</div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">Step ${r.step}</span>
                        </td>
                        <td>${escapeHtml(r.due_date)}</td>
                        <td>
                            ${r.completed_date ? escapeHtml(r.completed_date) : '<span style="color:#94a3b8;">—</span>'}
                        </td>
                        <td>${statusBadge}</td>
                        <td>
                            <a href="<?= $baseUrl ?>/view-lead/${r.lead_id}" target="_blank" class="btn btn-outline-primary btn-sm" style="font-size:11px; padding:2px 8px; border-radius:4px;">
                                View
                            </a>
                        </td>
                    </tr>
                `;
            });

            html += `
                        </tbody>
                    </table>
                </div>
            `;

            body.innerHTML = html;
        })
        .catch(err => {
            console.error(err);
            body.innerHTML = `
                <div class="alert alert-danger" style="margin:20px 0;">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> Failed to load leads details. Please try again.
                </div>
            `;
        });
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, m => map[m]);
}
</script>

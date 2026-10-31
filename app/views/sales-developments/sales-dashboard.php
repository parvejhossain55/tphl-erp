<?php
/**
 * TPHL / SALES INTELLIGENCE – Next-Gen Sales Dashboard
 * Fully reconciled with lead-lists, clients-cash-in-history, and sales incentive.
 * Mobile & Laptop responsive. Complete Light & Dark mode support.
 */

if (!function_exists('sd_fmt_cr')) {
    function sd_fmt_cr($val): string {
        $val = (float)$val;
        if ($val >= 10000000) {
            return 'BDT ' . number_format($val / 10000000, 2) . ' Cr';
        }
        if ($val >= 100000) {
            return 'BDT ' . number_format($val / 100000, 2) . ' L';
        }
        return 'BDT ' . number_format($val);
    }
    function sd_fmt_num($val): string {
        return number_format((float)$val);
    }
    function sd_pct($num, $den): string {
        return $den > 0 ? number_format(($num / $den) * 100, 1) . '%' : '0%';
    }
}

$m = $metrics;
$tf = $targetFulfilment;
$p = $period;
$f = $filters;

// Day in cycle computation
$startTs = strtotime($p['start_date']);
$endTs = strtotime($p['end_date']);
$nowTs = time();
$totalCycleDays = max(1, (int)round(($endTs - $startTs) / 86400) + 1);
$currentDayInCycle = min($totalCycleDays, max(1, (int)floor(($nowTs - $startTs) / 86400) + 1));
?>

<link rel="stylesheet" href="<?= $GLOBALS['base_url'] ?>/public/assets/css/plugins/apexcharts.css">
<script src="<?= $GLOBALS['base_url'] ?>/public/assets/js/plugins/apexcharts.min.js"></script>

<style>
/* ==========================================================================
   Sales Dashboard Design System (Compact, Proportional, Dark/Light Theme)
   ========================================================================== */
:root {
    --sd-page-bg: #f8fafc;
    --sd-card-bg: #ffffff;
    --sd-card-border: #e2e8f0;
    --sd-text-main: #0f172a;
    --sd-text-muted: #64748b;
    --sd-primary: #0284c7;
    --sd-primary-light: #e0f2fe;
    --sd-success: #059669;
    --sd-success-light: #d1fae5;
    --sd-warning: #d97706;
    --sd-warning-light: #fef3c7;
    --sd-danger: #dc2626;
    --sd-danger-light: #fee2e2;
    --sd-teal: #0d9488;
    --sd-teal-light: #ccfbf1;
    --sd-table-hover: #f8fafc;
    --sd-badge-bg: #f1f5f9;
}

[data-theme="dark"],
[bd-theme="bd-theme-dark"],
html[data-theme="dark"],
body[data-theme="dark"],
[data-theme-mode="dark"],
[data-bs-theme="dark"],
body.dark-only,
body.dark-mode {
    --sd-page-bg: #0b1120;
    --sd-card-bg: #1e293b;
    --sd-card-border: #334155;
    --sd-text-main: #f8fafc;
    --sd-text-muted: #94a3b8;
    --sd-primary: #38bdf8;
    --sd-primary-light: rgba(56, 189, 248, 0.15);
    --sd-success: #34d399;
    --sd-success-light: rgba(52, 211, 153, 0.15);
    --sd-warning: #fbbf24;
    --sd-warning-light: rgba(251, 191, 36, 0.15);
    --sd-danger: #f87171;
    --sd-danger-light: rgba(248, 113, 113, 0.15);
    --sd-teal: #2dd4bf;
    --sd-teal-light: rgba(45, 212, 191, 0.15);
    --sd-table-hover: #1e293b;
    --sd-badge-bg: #334155;
}

.sd-wrapper {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: var(--sd-text-main);
    padding-bottom: 24px;
}

/* Header */
.sd-header-eyebrow {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--sd-text-muted);
}
.sd-title {
    font-size: 22px;
    font-weight: 800;
    color: var(--sd-text-main);
    letter-spacing: -0.02em;
    margin: 0;
}
.sd-badge-live {
    font-size: 11px;
    font-weight: 600;
    padding: 2px 9px;
    border-radius: 9999px;
    background: #fef3c7;
    color: #b45309;
    border: 1px solid #fde68a;
}
[data-theme="dark"] .sd-badge-live,
[bd-theme="bd-theme-dark"] .sd-badge-live {
    background: rgba(251, 191, 36, 0.15);
    color: #fbbf24;
    border-color: rgba(251, 191, 36, 0.3);
}
.sd-subtitle {
    font-size: 12px;
    color: var(--sd-text-muted);
    margin-top: 2px;
    margin-bottom: 0;
}

/* Base Card Styling */
.sd-card {
    background: var(--sd-card-bg);
    border: 1px solid var(--sd-card-border);
    border-radius: 8px;
    padding: 12px 14px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}

/* Filter Bar */
.sd-filter-bar {
    background: var(--sd-card-bg);
    border: 1px solid var(--sd-card-border);
    border-radius: 8px;
    padding: 10px 14px;
    margin-bottom: 12px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}
.sd-pill-btn {
    border-radius: 6px;
    padding: 3px 12px;
    font-size: 11.5px;
    font-weight: 600;
    text-decoration: none;
    border: 1px solid var(--sd-card-border);
    background: transparent;
    color: var(--sd-text-muted);
    transition: all 0.15s ease;
    display: inline-block;
    line-height: 1.5;
}
.sd-pill-btn:hover {
    background: var(--sd-badge-bg);
    color: var(--sd-text-main);
}
.sd-pill-btn.active {
    background: #0d9488;
    color: #ffffff;
    border-color: #0d9488;
}
.sd-form-select, .sd-form-input {
    background-color: var(--sd-card-bg);
    color: var(--sd-text-main);
    border: 1px solid var(--sd-card-border);
    border-radius: 6px;
    font-size: 12px;
    padding: 4px 8px;
    height: 32px;
}
.sd-form-select:focus, .sd-form-input:focus {
    border-color: var(--sd-teal);
    box-shadow: 0 0 0 2px var(--sd-teal-light);
    outline: none;
}

/* KPI Cards - 1:1 Matching Mockup Proportions */
.sd-kpi-card {
    background: var(--sd-card-bg);
    border: 1px solid var(--sd-card-border);
    border-radius: 8px;
    padding: 10px 14px;
    height: 100%;
    min-height: 94px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}
.sd-kpi-card.interactive {
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}
.sd-kpi-card.interactive:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    border-color: var(--sd-teal);
}
.sd-clickable {
    cursor: pointer;
    transition: color 0.15s ease;
}
.sd-clickable:hover {
    color: var(--sd-teal) !important;
}
.sd-kpi-label {
    font-size: 11.5px;
    font-weight: 500;
    color: var(--sd-text-muted);
    line-height: 1.25;
    margin-bottom: 2px;
}
.sd-kpi-val {
    font-size: 20px;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--sd-text-main);
    line-height: 1.25;
    margin-bottom: 2px;
}
.sd-kpi-val.emerald { color: #059669; }
.sd-kpi-val.dark { color: var(--sd-text-main); }
.sd-kpi-val.amber { color: #d97706; }
.sd-kpi-val.danger { color: #dc2626; }
.sd-kpi-val.teal { color: #0d9488; }
.sd-kpi-val.primary { color: #0284c7; }

.sd-kpi-sub {
    font-size: 11px;
    font-weight: 400;
    color: var(--sd-text-muted);
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 2px;
}
.sd-kpi-footer {
    font-size: 10.5px;
    color: #94a3b8;
    line-height: 1.3;
    margin-top: auto;
}
.sd-kpi-footer a {
    color: inherit;
    text-decoration: none;
}
.sd-kpi-footer a:hover {
    color: var(--sd-teal);
}

/* Target Box */
.sd-badge-days {
    background: var(--sd-badge-bg);
    color: var(--sd-text-main);
    font-size: 10.5px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 4px;
}
.sd-progress-bar-bg {
    background: var(--sd-card-border);
    height: 6px;
    border-radius: 3px;
    overflow: hidden;
}
.sd-progress-bar-fill {
    background: var(--sd-teal);
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s ease;
}
.sd-rate-box {
    background: var(--sd-page-bg);
    border: 1px solid var(--sd-card-border);
    border-radius: 6px;
    padding: 8px 12px;
    margin-top: 8px;
}

/* Tables */
.sd-table {
    width: 100%;
    margin-bottom: 0;
    color: var(--sd-text-main);
    border-collapse: collapse;
}
.sd-table th {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--sd-text-muted);
    border-bottom: 1px solid var(--sd-card-border);
    padding: 8px 10px;
    background: var(--sd-card-bg);
    white-space: nowrap;
}
.sd-table td {
    font-size: 12px;
    padding: 8px 10px;
    border-bottom: 1px solid var(--sd-card-border);
    vertical-align: middle;
}
.sd-table tr:hover td {
    background: var(--sd-table-hover);
}
.sd-clickable-row {
    cursor: pointer;
    user-select: none;
}
.sd-child-row td {
    background: var(--sd-page-bg) !important;
}

/* Inventory Segmented Bar */
.sd-inv-bar {
    display: flex;
    height: 8px;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 8px;
}
.sd-inv-seg-avail { background: #10b981; }
.sd-inv-seg-locked { background: #3b82f6; }
.sd-inv-seg-bkg { background: #f59e0b; }
.sd-inv-seg-sold { background: #0d9488; }
.sd-inv-seg-blocked { background: #64748b; }

.sd-dot {
    display: inline-block;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    margin-right: 4px;
}

/* Callouts */
.sd-callout-warning {
    background: var(--sd-warning-light);
    border-left: 3px solid var(--sd-warning);
    border-radius: 5px;
    padding: 8px 12px;
}
.sd-callout-danger {
    background: var(--sd-danger-light);
    border-left: 3px solid var(--sd-danger);
    border-radius: 5px;
    padding: 8px 12px;
}

/* Toggle Arrow */
.sd-arrow-icon {
    display: inline-block;
    transition: transform 0.2s ease;
}
.sd-arrow-icon.open {
    transform: rotate(90deg);
}

/* Lead Funnel */
.sd-funnel-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 6px;
}
.sd-funnel-label {
    width: 78px;
    font-size: 11.5px;
    font-weight: 500;
    color: var(--sd-text-main);
    flex-shrink: 0;
}
.sd-funnel-bar-bg {
    flex-grow: 1;
    height: 8px;
    background: var(--sd-card-border);
    border-radius: 4px;
    overflow: hidden;
}
.sd-funnel-bar-fill {
    height: 100%;
    background: #0d9488;
    border-radius: 4px;
    transition: width 0.3s ease;
}
.sd-funnel-val {
    width: 36px;
    text-align: right;
    font-size: 12px;
    font-weight: 700;
    color: var(--sd-text-main);
    flex-shrink: 0;
}

/* Follow-up Compliance */
.sd-fu-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 6px;
    margin-bottom: 8px;
}
@media (max-width: 576px) {
    .sd-fu-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
.sd-fu-card {
    background: var(--sd-page-bg);
    border: 1px solid var(--sd-card-border);
    border-radius: 6px;
    padding: 7px 5px;
    text-align: center;
}
.sd-fu-title {
    font-size: 9.5px;
    font-weight: 600;
    color: var(--sd-text-muted);
    margin-bottom: 1px;
}
.sd-fu-pct {
    font-size: 15px;
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 1px;
}
.sd-fu-pct.green { color: #059669; }
.sd-fu-pct.amber { color: #d97706; }
.sd-fu-sub {
    font-size: 9px;
    color: var(--sd-text-muted);
}

/* Priority Action Cards */
.sd-priority-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    border: 1px solid var(--sd-card-border);
    border-radius: 6px;
    background: var(--sd-page-bg);
    margin-bottom: 8px;
}
.sd-priority-item:last-child {
    margin-bottom: 0;
}
.sd-priority-btn {
    border: 1px solid var(--sd-card-border);
    background: var(--sd-card-bg);
    color: var(--sd-text-main);
    font-size: 11px;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 6px;
    text-decoration: none;
    transition: all 0.15s ease;
    white-space: nowrap;
}
.sd-priority-btn:hover {
    background: var(--sd-badge-bg);
    color: var(--sd-primary);
}

/* Timeline updates */
.sd-timeline-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 7px 0;
    border-bottom: 1px solid var(--sd-card-border);
}
.sd-timeline-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.sd-time-badge {
    font-size: 10.5px;
    font-weight: 600;
    color: var(--sd-text-muted);
    width: 42px;
    flex-shrink: 0;
}
</style>

    <div class="sd-wrapper">

        <!-- Top Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h1 class="sd-title">Sales dashboard</h1>
                    <span class="sd-badge-live"><i class="fa-solid fa-circle-dot me-1"></i> Live Data</span>
                </div>
                <p class="sd-subtitle">Sales, stock and targets — with clear numbers and clear next actions.</p>
            </div>
            <div class="d-flex gap-2 align-items-center mt-2 mt-md-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" style="font-size: 12px; height: 32px; padding: 0 12px; border-color: var(--sd-card-border); color: var(--sd-text-muted);" data-bs-toggle="modal" data-bs-target="#definitionsModal">
                    <i class="fa-solid fa-circle-question me-1"></i> Definitions
                </button>
                <a href="<?= $GLOBALS['base_url'] ?>/sales-performance?export=csv" class="btn btn-primary btn-sm text-white" style="background: #047857; border-color: #047857; font-size: 12px; height: 32px; padding: 0 12px; display: inline-flex; align-items: center; font-weight: 600;">
                    <i class="fa-solid fa-file-arrow-down me-1"></i> Export report
                </a>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="sd-filter-bar">
            <form method="GET" action="" id="sdFilterForm">
                <!-- Preset Quick Buttons & Cycle Indicator -->
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2 pb-2 border-bottom">
                    <div class="d-flex gap-2 align-items-center">
                        <a href="?preset=cycle" class="sd-pill-btn <?= ($f['preset'] === 'cycle' || empty($f['preset'])) ? 'active' : '' ?>">This cycle</a>
                        <a href="?preset=today" class="sd-pill-btn <?= ($f['preset'] === 'today') ? 'active' : '' ?>">Today</a>
                        <a href="?preset=yesterday" class="sd-pill-btn <?= ($f['preset'] === 'yesterday') ? 'active' : '' ?>">Yesterday</a>
                        <a href="?preset=last_cycle" class="sd-pill-btn <?= ($f['preset'] === 'last_cycle') ? 'active' : '' ?>">Last cycle</a>
                    </div>
                    <div class="text-muted small fw-semibold" style="font-size: 11.5px;">
                        <i class="fa-regular fa-calendar-check me-1"></i>
                        Cycle to date: <strong><?= date('d M Y') ?></strong> • Day <?= $currentDayInCycle ?> of <?= $totalCycleDays ?>
                    </div>
                </div>

                <!-- Dropdowns & Filter Controls -->
                <div class="row g-2 align-items-end">
                    <!-- Reporting Cycle -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label text-muted mb-1" style="font-size: 10.5px; font-weight: 600;">Reporting cycle</label>
                        <select name="cycle" class="form-select sd-form-select">
                            <?php foreach ($cycleOptions as $opt): ?>
                                <?php if ($opt['type'] === 'month'): ?>
                                    <option value="<?= $opt['value'] ?>" <?= ($f['cycle'] === $opt['value']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($opt['label']) ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Project -->
                    <div class="col-12 col-sm-6 col-lg-2">
                        <label class="form-label text-muted mb-1" style="font-size: 10.5px; font-weight: 600;">Project</label>
                        <select name="project_id" class="form-select sd-form-select">
                            <option value="">All projects</option>
                            <?php foreach ($projectsList as $proj): ?>
                                <option value="<?= $proj['id'] ?>" <?= ($f['project_id'] == $proj['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($proj['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Sales Team -->
                    <div class="col-12 col-sm-6 col-lg-2">
                        <label class="form-label text-muted mb-1" style="font-size: 10.5px; font-weight: 600;">Sales team</label>
                        <select name="team_id" class="form-select sd-form-select">
                            <option value="">All teams</option>
                            <?php foreach ($teamsList as $team): ?>
                                <option value="<?= $team['id'] ?>" <?= ($f['team_id'] == $team['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($team['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Executive -->
                    <div class="col-12 col-sm-6 col-lg-2">
                        <label class="form-label text-muted mb-1" style="font-size: 10.5px; font-weight: 600;">Executive</label>
                        <select name="executive_id" class="form-select sd-form-select">
                            <option value="">All executives</option>
                            <?php foreach ($salesUsers as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= ($f['executive_id'] == $u['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Product Type -->
                    <div class="col-12 col-sm-6 col-lg-1" style="min-width: 110px;">
                        <label class="form-label text-muted mb-1" style="font-size: 10.5px; font-weight: 600;">Product type</label>
                        <select name="product_type" class="form-select sd-form-select">
                            <option value="all" <?= ($f['product_type'] === 'all') ? 'selected' : '' ?>>All</option>
                            <option value="units" <?= ($f['product_type'] === 'units') ? 'selected' : '' ?>>Units</option>
                            <option value="shares" <?= ($f['product_type'] === 'shares') ? 'selected' : '' ?>>Shares</option>
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-12 col-sm-6 col-lg-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100 btn-sm text-white" style="background: #047857; border-color: #047857; height: 32px; font-size: 12px; font-weight: 600;">
                            Apply filters
                        </button>
                        <a href="<?= $GLOBALS['base_url'] ?>/sales-dashboard" class="btn btn-outline-secondary btn-sm" style="height: 32px; width: 36px; display: inline-flex; align-items: center; justify-content: center; border-color: var(--sd-card-border);" title="Reset">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- ====================================================================
             TOP METRICS ROW (8 KPI CARDS - 2 ROWS OF 4 COLUMNS)
             ==================================================================== -->
        <div class="row g-2 mb-2">
            <!-- 1. Net verified sales -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="sd-kpi-card interactive" onclick="showDrilldown('verified_sales')" title="Click to view verified sales list">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="sd-kpi-label">Net verified sales</div>
                        <i class="fa-solid fa-arrow-up-right-from-square text-muted" style="font-size: 10px;"></i>
                    </div>
                    <div class="sd-kpi-val emerald"><?= sd_fmt_cr($m['net_verified_sales_val']) ?></div>
                    <div class="sd-kpi-sub"><?= sd_fmt_cr($m['gross_sales_val']) ?> gross – <?= sd_fmt_cr($m['reversed_sales_val']) ?> reversed</div>
                    <div class="sd-kpi-footer">Period • Verified date</div>
                </div>
            </div>

            <!-- 2. Net whole units sold -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="sd-kpi-card interactive" onclick="showDrilldown('whole_units')" title="Click to view whole units sold">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="sd-kpi-label">Net whole units sold</div>
                        <i class="fa-solid fa-arrow-up-right-from-square text-muted" style="font-size: 10px;"></i>
                    </div>
                    <div class="sd-kpi-val dark"><?= sd_fmt_num($m['net_whole_units_sold']) ?> units</div>
                    <div class="sd-kpi-sub"><?= sd_fmt_num($m['gross_whole_units_sold']) ?> verified – <?= sd_fmt_num($m['cancelled_units']) ?> cancelled</div>
                    <div class="sd-kpi-footer">Period • Unique physical units</div>
                </div>
            </div>

            <!-- 3. Group-buying shares sold -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="sd-kpi-card interactive" onclick="showDrilldown('shares')" title="Click to view group shares sold">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="sd-kpi-label">Group-buying shares sold</div>
                        <i class="fa-solid fa-arrow-up-right-from-square text-muted" style="font-size: 10px;"></i>
                    </div>
                    <div class="sd-kpi-val dark"><?= sd_fmt_num($m['group_buying_shares_sold']) ?> shares</div>
                    <div class="sd-kpi-sub"><?= $tf['shares_pct'] ?> of <?= $tf['shares_target'] ?>-share cycle target</div>
                    <div class="sd-kpi-footer">Period • Shares, not flat count</div>
                </div>
            </div>

            <!-- 4. Sales value still to achieve -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="sd-kpi-card interactive" onclick="showDrilldown('target_gap')" title="Click to view target fulfilment breakdown">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="sd-kpi-label">Sales value still to achieve</div>
                        <i class="fa-solid fa-arrow-up-right-from-square text-muted" style="font-size: 10px;"></i>
                    </div>
                    <div class="sd-kpi-val dark"><?= sd_fmt_cr($m['sales_value_to_achieve']) ?></div>
                    <div class="sd-kpi-sub">Target <?= sd_fmt_cr($tf['target_val']) ?> • <?= $tf['val_achieved_pct'] ?>% achieved</div>
                    <div class="sd-kpi-footer">Cycle target – Net sales</div>
                </div>
            </div>
        </div>

        <div class="row g-2 mb-3">
            <!-- 5. Awaiting sales verification -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="sd-kpi-card interactive" onclick="showDrilldown('awaiting')" title="Click to review pending bookings">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="sd-kpi-label">Awaiting sales verification</div>
                        <i class="fa-solid fa-arrow-up-right-from-square text-muted" style="font-size: 10px;"></i>
                    </div>
                    <div class="sd-kpi-val amber"><?= sd_fmt_num($m['awaiting_total']) ?> bookings</div>
                    <div class="sd-kpi-sub"><?= $m['awaiting_units'] ?> units + <?= $m['awaiting_shares'] ?> shares • <?= sd_fmt_cr($m['unconfirmed_amt']) ?> unconfirmed</div>
                    <div class="sd-kpi-footer">
                        <a href="javascript:void(0)" onclick="showDrilldown('awaiting'); event.stopPropagation();" class="text-warning fw-semibold">
                            As of snapshot • Review <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- 6. Available inventory -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="sd-kpi-card interactive" onclick="window.location.href='<?= $GLOBALS['base_url'] ?>/flats'" title="Click to open flats inventory">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="sd-kpi-label">Available inventory</div>
                        <i class="fa-solid fa-arrow-up-right-from-square text-muted" style="font-size: 10px;"></i>
                    </div>
                    <div class="sd-kpi-val teal"><?= sd_fmt_num($m['available_units']) ?> units</div>
                    <div class="sd-kpi-sub"><?= sd_fmt_num($m['available_shares']) ?> shares available separately</div>
                    <div class="sd-kpi-footer">
                        <a href="<?= $GLOBALS['base_url'] ?>/flats" class="fw-semibold" onclick="event.stopPropagation();">
                            As of snapshot • View stock <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- 7. Money collected -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="sd-kpi-card interactive" onclick="showDrilldown('collections')" title="Click to view collected payments">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="sd-kpi-label">Money collected</div>
                        <i class="fa-solid fa-arrow-up-right-from-square text-muted" style="font-size: 10px;"></i>
                    </div>
                    <div class="sd-kpi-val primary"><?= sd_fmt_cr($m['money_collected']) ?></div>
                    <div class="sd-kpi-sub">Verified receipts, including older sales</div>
                    <div class="sd-kpi-footer">Period • Receipt date</div>
                </div>
            </div>

            <!-- 8. Sales cancelled -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="sd-kpi-card interactive" onclick="showDrilldown('cancelled')" title="Click to view cancelled sales">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="sd-kpi-label">Sales cancelled</div>
                        <i class="fa-solid fa-arrow-up-right-from-square text-muted" style="font-size: 10px;"></i>
                    </div>
                    <div class="sd-kpi-val <?= $m['cancelled_units'] > 0 ? 'danger' : 'dark' ?>"><?= sd_fmt_num($m['cancelled_units']) ?> units</div>
                    <div class="sd-kpi-sub"><?= sd_fmt_cr($m['reversed_sales_val']) ?> reversed</div>
                    <div class="sd-kpi-footer">Period • Reversal date</div>
                </div>
            </div>
        </div>

        <!-- ====================================================================
             MIDDLE SECTION: TARGET FULFILMENT & SALES VALUE TREND
             ==================================================================== -->
        <div class="row g-2 mb-3">
            <!-- Target fulfilment -->
            <div class="col-12 col-xl-6">
                <div class="sd-card">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h5 class="fw-bold m-0" style="font-size: 15px;">Target fulfilment</h5>
                            <span class="sd-badge-days"><?= $tf['days_remaining'] ?> days left</span>
                        </div>
                        <div class="text-muted mb-2" style="font-size: 11.5px;"><?= htmlspecialchars($p['cycle_label']) ?> • <?= $p['label'] ?></div>

                        <div class="d-flex justify-content-between align-items-baseline mb-2">
                            <div>
                                <span class="fw-bold text-success me-2" style="font-size: 21px;"><?= sd_fmt_cr($tf['net_sales_val']) ?></span>
                                <span class="text-muted fw-semibold" style="font-size: 13px;">of <?= sd_fmt_cr($tf['target_val']) ?></span>
                            </div>
                            <div class="fw-bold text-success" style="font-size: 18px;"><?= $tf['val_achieved_pct'] ?>%</div>
                        </div>

                        <!-- Whole Units Progress -->
                        <div class="mb-2">
                            <div class="d-flex justify-content-between small fw-semibold mb-1" style="font-size: 11px;">
                                <span>Whole units</span>
                                <span><?= $tf['whole_units_achieved'] ?> / <?= $tf['whole_units_target'] ?> • <?= $tf['whole_units_pct'] ?>%</span>
                            </div>
                            <div class="sd-progress-bar-bg">
                                <div class="sd-progress-bar-fill" style="width: <?= min(100, $tf['whole_units_pct']) ?>%;"></div>
                            </div>
                        </div>

                        <!-- Shares Progress -->
                        <div class="mb-2">
                            <div class="d-flex justify-content-between small fw-semibold mb-1" style="font-size: 11px;">
                                <span>Shares</span>
                                <span><?= $tf['shares_achieved'] ?> / <?= $tf['shares_target'] ?> • <?= $tf['shares_pct'] ?>%</span>
                            </div>
                            <div class="sd-progress-bar-bg">
                                <div class="sd-progress-bar-fill" style="width: <?= min(100, $tf['shares_pct']) ?>%; background: var(--sd-primary);"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Run Rate Box -->
                    <div>
                        <div class="sd-rate-box">
                            <div class="fw-bold text-muted text-uppercase mb-1" style="font-size: 9.5px; letter-spacing: 0.05em;">
                                REQUIRED PER REMAINING DAY
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-2" style="font-size: 12.5px;">
                                <div class="fw-bold text-dark" style="color: var(--sd-text-main) !important;">
                                    <?= sd_fmt_cr($tf['required_per_day_val']) ?>
                                </div>
                                <div class="text-muted small">•</div>
                                <div class="fw-bold text-dark" style="color: var(--sd-text-main) !important;">
                                    <?= number_format($tf['required_per_day_units'], 1) ?> units
                                </div>
                                <div class="text-muted small">•</div>
                                <div class="fw-bold text-dark" style="color: var(--sd-text-main) !important;">
                                    <?= number_format($tf['required_per_day_shares'], 1) ?> shares
                                </div>
                            </div>
                        </div>
                        <div class="text-muted mt-2" style="font-size: 10.5px;">
                            Remaining: <?= sd_fmt_cr($m['sales_value_to_achieve']) ?> • Pace uses <?= $tf['days_remaining'] ?> remaining days.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales value trend (Bar Chart) -->
            <div class="col-12 col-xl-6">
                <div class="sd-card">
                    <div>
                        <h5 class="fw-bold m-0" style="font-size: 15px;">Sales value trend</h5>
                        <div class="text-muted mb-1" style="font-size: 11.5px;">Last 6 completed reporting cycles • Net contract value • BDT crore</div>
                    </div>
                    <div style="min-height: 165px;">
                        <div id="sdSalesValueTrendChart"></div>
                    </div>
                    <div class="sd-kpi-footer">Completed cycles are shown here; November is still in progress.</div>
                </div>
            </div>
        </div>

        <!-- ====================================================================
             PROJECT SALES & TARGET ACHIEVEMENT TABLE
             ==================================================================== -->
        <div class="sd-card mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <h5 class="fw-bold m-0" style="font-size: 15px;">Project sales & target achievement</h5>
                    <div class="text-muted" style="font-size: 11px;">Cycle to date • Net sales by verified date • Available stock as of today</div>
                </div>
                <a href="<?= $GLOBALS['base_url'] ?>/projects" class="text-decoration-none small fw-bold" style="color: var(--sd-teal); font-size: 11.5px;">
                    View all projects <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>

            <div class="table-responsive">
                <table class="sd-table">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th class="text-center">Units</th>
                            <th class="text-center">Shares</th>
                            <th>Net Sales</th>
                            <th>Value Target</th>
                            <th>Achieved</th>
                            <th>Value Gap</th>
                            <th class="text-center">Avail. Units</th>
                            <th class="text-center">Avail. Shares</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($projectRows)): ?>
                            <?php foreach (array_slice($projectRows, 0, 8) as $row): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark sd-clickable" onclick="showProjectSales(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['name'])) ?>')" style="color: var(--sd-text-main) !important; font-size: 12.5px; cursor: pointer;" title="Click to view sales for <?= htmlspecialchars($row['name']) ?>">
                                            <?= htmlspecialchars($row['name']) ?>
                                        </div>
                                        <small class="text-muted" style="font-size: 10.5px;">Whole units & shares</small>
                                    </td>
                                    <td class="text-center fw-semibold"><?= $row['units'] ?></td>
                                    <td class="text-center fw-semibold text-primary"><?= $row['shares'] ?></td>
                                    <td class="fw-bold text-success"><?= sd_fmt_cr($row['net_sales']) ?></td>
                                    <td class="text-muted fw-semibold"><?= sd_fmt_cr($row['value_target']) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="fw-bold small" style="font-size: 11px; width: 35px;"><?= $row['achieved'] ?>%</span>
                                            <div class="sd-progress-bar-bg flex-grow-1" style="height: 5px; width: 45px;">
                                                <div class="sd-progress-bar-fill" style="width: <?= min(100, $row['achieved']) ?>%;"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-muted"><?= sd_fmt_cr($row['value_gap']) ?></td>
                                    <td class="text-center fw-bold"><?= $row['avail_units'] ?></td>
                                    <td class="text-center fw-bold text-primary"><?= $row['avail_shares'] ?></td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-link text-primary p-0 me-2" onclick="showProjectSales(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['name'])) ?>')" title="View Project Sales Drilldown">
                                            <i class="fa-solid fa-eye" style="font-size: 13px;"></i>
                                        </button>
                                        <a href="<?= $GLOBALS['base_url'] ?>/lead-lists?project_id=<?= $row['id'] ?>" class="btn btn-sm btn-link text-muted p-0" title="Go to Leads for <?= htmlspecialchars($row['name']) ?>">
                                            <i class="fa-solid fa-arrow-right" style="font-size: 12px;"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-3">No project sales recorded in this cycle.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($projectRows)): ?>
                        <tfoot style="border-top: 2px solid var(--sd-card-border); font-weight: bold;">
                            <tr>
                                <td>TOTAL</td>
                                <td class="text-center"><?= $projectTotals['units'] ?></td>
                                <td class="text-center"><?= $projectTotals['shares'] ?></td>
                                <td class="text-success"><?= sd_fmt_cr($projectTotals['net_sales']) ?></td>
                                <td><?= sd_fmt_cr($projectTotals['target']) ?></td>
                                <td><?= sd_pct($projectTotals['net_sales'], $projectTotals['target']) ?></td>
                                <td><?= sd_fmt_cr(max(0, $projectTotals['target'] - $projectTotals['net_sales'])) ?></td>
                                <td class="text-center"><?= $projectTotals['avail_units'] ?></td>
                                <td class="text-center text-primary"><?= $projectTotals['avail_shares'] ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- ====================================================================
             INVENTORY AVAILABILITY & COLLECTIONS EXPOSURE
             ==================================================================== -->
        <div class="row g-2 mb-3">
            <!-- Inventory Availability -->
            <div class="col-12 col-xl-6">
                <div class="sd-card">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h5 class="fw-bold m-0" style="font-size: 15px;">Inventory availability</h5>
                            <a href="<?= $GLOBALS['base_url'] ?>/flats" class="text-decoration-none small fw-bold" style="color: var(--sd-teal); font-size: 11.5px;">
                                Open inventory <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                        <div class="text-muted mb-2" style="font-size: 11px;">Stock snapshot • Separate unit and share pools</div>

                        <!-- Whole-unit inventory bar -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-baseline mb-1">
                                <span class="fw-bold small" style="font-size: 11.5px;">Whole-unit inventory</span>
                                <span class="text-muted small fw-semibold"><?= sd_fmt_num($inventory['unit']['total']) ?> units total</span>
                            </div>
                            <?php 
                            $uTot = max(1, $inventory['unit']['total']);
                            $uAvailP = round(($inventory['unit']['available'] / $uTot) * 100);
                            $uLockP = round(($inventory['unit']['locked'] / $uTot) * 100);
                            $uBkgP = round(($inventory['unit']['booking'] / $uTot) * 100);
                            $uSoldP = round(($inventory['unit']['sold'] / $uTot) * 100);
                            $uBlockP = max(0, 100 - ($uAvailP + $uLockP + $uBkgP + $uSoldP));
                            ?>
                            <div class="sd-inv-bar">
                                <div class="sd-inv-seg-avail" style="width: <?= $uAvailP ?>%;" title="Available: <?= $inventory['unit']['available'] ?>"></div>
                                <div class="sd-inv-seg-locked" style="width: <?= $uLockP ?>%;" title="Locked: <?= $inventory['unit']['locked'] ?>"></div>
                                <div class="sd-inv-seg-bkg" style="width: <?= $uBkgP ?>%;" title="Booking: <?= $inventory['unit']['booking'] ?>"></div>
                                <div class="sd-inv-seg-sold" style="width: <?= $uSoldP ?>%;" title="Sold: <?= $inventory['unit']['sold'] ?>"></div>
                                <div class="sd-inv-seg-blocked" style="width: <?= $uBlockP ?>%;" title="Blocked: <?= $inventory['unit']['blocked'] ?>"></div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 text-muted" style="font-size: 11px;">
                                <div><span class="sd-dot" style="background: #10b981;"></span> Available: <strong class="text-dark" style="color:var(--sd-text-main)!important;"><?= $inventory['unit']['available'] ?></strong></div>
                                <div><span class="sd-dot" style="background: #3b82f6;"></span> Locked: <strong class="text-dark" style="color:var(--sd-text-main)!important;"><?= $inventory['unit']['locked'] ?></strong></div>
                                <div><span class="sd-dot" style="background: #f59e0b;"></span> Booking: <strong class="text-dark" style="color:var(--sd-text-main)!important;"><?= $inventory['unit']['booking'] ?></strong></div>
                                <div><span class="sd-dot" style="background: #0d9488;"></span> Sold: <strong class="text-dark" style="color:var(--sd-text-main)!important;"><?= $inventory['unit']['sold'] ?></strong></div>
                                <div><span class="sd-dot" style="background: #64748b;"></span> Blocked: <strong class="text-dark" style="color:var(--sd-text-main)!important;"><?= $inventory['unit']['blocked'] ?></strong></div>
                            </div>
                        </div>

                        <!-- Group-buying shares -->
                        <div>
                            <div class="d-flex justify-content-between align-items-baseline mb-1">
                                <span class="fw-bold small" style="font-size: 11.5px;">Group-buying shares</span>
                                <span class="text-muted small fw-semibold"><?= sd_fmt_num($inventory['share']['total']) ?> shares total</span>
                            </div>
                            <div class="d-flex flex-wrap gap-3 text-muted" style="font-size: 11px;">
                                <div><strong class="text-success"><?= $inventory['share']['available'] ?></strong> available</div>
                                <div><strong class="text-dark" style="color:var(--sd-text-main)!important;"><?= $inventory['share']['locked'] ?></strong> locked</div>
                                <div><strong class="text-warning"><?= $inventory['share']['booking'] ?></strong> booking</div>
                                <div><strong class="text-primary"><?= $inventory['share']['sold'] ?></strong> sold</div>
                            </div>
                        </div>
                    </div>
                    <div class="sd-kpi-footer">"Booking" stock is awaiting sales verification. Blocked stock is not for sale.</div>
                </div>
            </div>

            <!-- Collections & payment exposure (5 Categories) -->
            <div class="col-12 col-xl-6">
                <div class="sd-card">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h5 class="fw-bold m-0" style="font-size: 15px;">Collections & payment exposure</h5>
                            <a href="<?= $GLOBALS['base_url'] ?>/payment-bookings" class="text-decoration-none small fw-bold" style="color: var(--sd-teal); font-size: 11.5px;">
                                Open collections <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                        <div class="text-muted mb-2" style="font-size: 11px;">Receipts in selected period • 5 Collection Heads</div>

                        <div class="mb-2">
                            <div class="sd-kpi-val primary" style="font-size: 22px;"><?= sd_fmt_cr($collections['verified']) ?></div>
                            <div class="text-muted" style="font-size: 10.5px;">Verified money collected in this cycle</div>
                        </div>

                        <!-- 5 Heads Breakdown -->
                        <div class="row g-1 mb-2">
                            <div class="col-6 col-sm-4">
                                <div class="p-1 px-2 border rounded" style="background: var(--sd-page-bg);">
                                    <small class="text-muted d-block" style="font-size: 9.5px;">Booking</small>
                                    <strong style="font-size: 11.5px;"><?= sd_fmt_cr($collections['booking']) ?></strong>
                                </div>
                            </div>
                            <div class="col-6 col-sm-4">
                                <div class="p-1 px-2 border rounded" style="background: var(--sd-page-bg);">
                                    <small class="text-muted d-block" style="font-size: 9.5px;">Registration</small>
                                    <strong style="font-size: 11.5px;"><?= sd_fmt_cr($collections['registration']) ?></strong>
                                </div>
                            </div>
                            <div class="col-6 col-sm-4">
                                <div class="p-1 px-2 border rounded" style="background: var(--sd-page-bg);">
                                    <small class="text-muted d-block" style="font-size: 9.5px;">Dev Charge</small>
                                    <strong style="font-size: 11.5px;"><?= sd_fmt_cr($collections['dev_charge']) ?></strong>
                                </div>
                            </div>
                            <div class="col-6 col-sm-6">
                                <div class="p-1 px-2 border rounded" style="background: var(--sd-page-bg);">
                                    <small class="text-muted d-block" style="font-size: 9.5px;">EMI / Installments</small>
                                    <strong style="font-size: 11.5px;"><?= sd_fmt_cr($collections['emi']) ?></strong>
                                </div>
                            </div>
                            <div class="col-6 col-sm-6">
                                <div class="p-1 px-2 border rounded" style="background: var(--sd-page-bg);">
                                    <small class="text-muted d-block" style="font-size: 9.5px;">Other Receipts</small>
                                    <strong style="font-size: 11.5px;"><?= sd_fmt_cr($collections['other']) ?></strong>
                                </div>
                            </div>
                        </div>

                        <!-- Callout badges -->
                        <div class="row g-1">
                            <div class="col-6">
                                <div class="sd-callout-warning py-1 px-2">
                                    <small class="fw-bold d-block text-warning" style="font-size: 9.5px;">OVERDUE COLLECTIONS</small>
                                    <strong style="font-size: 12px;"><?= sd_fmt_cr($overdueCollections) ?></strong>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="sd-callout-danger py-1 px-2">
                                    <small class="fw-bold d-block text-danger" style="font-size: 9.5px;">PENDING VERIFICATION</small>
                                    <strong style="font-size: 12px;"><?= sd_fmt_cr($collections['pending']) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="sd-kpi-footer">Receipts can relate to older sales: they are verified cash-in during this cycle.</div>
                </div>
            </div>
        </div>

        <!-- ====================================================================
             LEAD FUNNEL & FOLLOW-UP COMPLIANCE
             ==================================================================== -->
        <div class="row g-2 mb-3">
            <!-- Lead Funnel -->
            <div class="col-12 col-xl-6">
                <div class="sd-card h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h5 class="fw-bold m-0" style="font-size: 15px;">Lead funnel</h5>
                            <a href="<?= $GLOBALS['base_url'] ?>/leads" class="text-decoration-none small fw-bold" style="color: var(--sd-teal); font-size: 11.5px;">
                                See details <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                        <div class="text-muted mb-3" style="font-size: 11px;"><?= date('d M') ?> lead cohort • Unique leads that reached each stage</div>

                        <?php
                        $maxFunnel = max(1, (int)$leadFunnel['received']);
                        $stages = [
                            ['label' => 'Received',  'val' => (int)$leadFunnel['received']],
                            ['label' => 'Contacted', 'val' => (int)$leadFunnel['contacted']],
                            ['label' => 'Qualified', 'val' => (int)$leadFunnel['qualified']],
                            ['label' => 'Visited',   'val' => (int)$leadFunnel['visited']],
                            ['label' => 'Booked',    'val' => (int)$leadFunnel['booked']],
                            ['label' => 'Verified',  'val' => (int)$leadFunnel['verified']],
                        ];
                        foreach ($stages as $stg):
                            $w = round(($stg['val'] / $maxFunnel) * 100);
                        ?>
                            <div class="sd-funnel-row">
                                <div class="sd-funnel-label"><?= $stg['label'] ?></div>
                                <div class="sd-funnel-bar-bg">
                                    <div class="sd-funnel-bar-fill" style="width: <?= max(2, $w) ?>%;"></div>
                                </div>
                                <div class="sd-funnel-val"><?= $stg['val'] ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="pt-2 border-top mt-2">
                        <div class="small fw-semibold mb-1" style="font-size: 11px;">
                            <span class="text-warning">Not contacted <?= (int)$leadFunnel['not_contacted'] ?></span> • 
                            <span class="text-info">Contacting <?= (int)$leadFunnel['contacting'] ?></span> • 
                            <span class="text-muted">No response <?= (int)$leadFunnel['no_response'] ?></span>
                        </div>
                        <div class="text-muted" style="font-size: 10.5px; line-height: 1.35;">
                            <?= (int)$leadFunnel['assigned_count'] ?> assigned • <?= (int)$leadFunnel['unassigned_count'] ?> unassigned. Contacted means two-way communication.<br>
                            Stages overlap; "Verified" is leads reached, before cancellations, not unit count.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Follow-up Compliance -->
            <div class="col-12 col-xl-6">
                <div class="sd-card h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h5 class="fw-bold m-0" style="font-size: 15px;">Follow-up compliance</h5>
                            <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold" style="font-size: 11px; padding: 3px 8px;">
                                <?= $followupCompliance['overall_rate'] ?>% overall
                            </span>
                        </div>
                        <div class="text-muted mb-2" style="font-size: 11px;">Due by <?= date('d M, H:i') ?> • All active lead cohorts • On-time completion</div>

                        <!-- 5 Stages Grid -->
                        <div class="sd-fu-grid">
                            <?php foreach ($followupCompliance['stages'] as $fuStage => $fuData): ?>
                                <div class="sd-fu-card">
                                    <div class="sd-fu-title"><?= $fuStage ?> follow-up</div>
                                    <div class="sd-fu-pct <?= $fuData['rate'] >= 80 ? 'green' : 'amber' ?>">
                                        <?= number_format($fuData['rate'], 1) ?>%
                                    </div>
                                    <div class="sd-fu-sub"><?= $fuData['completed'] ?> / <?= $fuData['total'] ?> on-time</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="pt-2 border-top mt-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="small fw-bold" style="font-size: 11.5px;">
                                <span class="text-danger me-2"><?= $followupCompliance['overdue_count'] ?> overdue</span>
                                <span class="text-muted"><?= $followupCompliance['completed_late'] ?> completed late</span>
                            </div>
                            <a href="#teamPerformanceSection" class="text-decoration-none small fw-semibold" style="color: var(--sd-teal); font-size: 11px;">
                                Compare every team and person below <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                        <div class="text-muted" style="font-size: 10.5px;">
                            Rate = on-time completed ÷ eligible follow-ups due.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ====================================================================
             TEAM & EXECUTIVE PERFORMANCE
             ==================================================================== -->
        <div class="sd-card mb-3" id="teamPerformanceSection">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
                <div>
                    <h5 class="fw-bold m-0" style="font-size: 15px;">Team & executive performance</h5>
                    <div class="text-muted" style="font-size: 11px;">Cycle sales + due follow-ups • Other teams are combined in the last row</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary btn-sm active" id="btnViewTeams" onclick="switchPerfView('teams')" style="font-size: 11.5px; padding: 2px 10px;">Teams</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnViewIndividuals" onclick="switchPerfView('individuals')" style="font-size: 11.5px; padding: 2px 10px;">Individuals</button>
                    </div>
                    <a href="<?= $GLOBALS['base_url'] ?>/sales-intelligence" class="text-decoration-none small fw-bold" style="color: var(--sd-teal); font-size: 11.5px;">
                        View all <?= count($teamsHierarchy) ?> teams <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center text-muted mb-2" style="font-size: 10.5px;">
                <span>Select a team row to inspect individual members, closing sales, and collections.</span>
                <span class="fw-semibold">Done-on-time goal: ≥80% • Missing targets show "Not set"</span>
            </div>

            <!-- Teams View Table -->
            <div class="table-responsive" id="teamsTableView">
                <table class="sd-table">
                    <thead>
                        <tr>
                            <th>Team</th>
                            <th>Net Sales</th>
                            <th>Value Target</th>
                            <th>Achieved</th>
                            <th class="text-center">Units</th>
                            <th class="text-center">Shares</th>
                            <th class="text-center">1st</th>
                            <th class="text-center">2nd</th>
                            <th class="text-center">3rd</th>
                            <th class="text-center">4th</th>
                            <th class="text-center">5th</th>
                            <th class="text-center">Overdue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($teamsHierarchy)): ?>
                            <?php foreach ($teamsHierarchy as $idx => $t): ?>
                                <?php $collapseId = "teamCollapse_" . ($t['team_id'] ?? $idx); ?>
                                <tr class="sd-clickable-row" onclick="toggleTeamDrilldown('<?= $collapseId ?>', this)">
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="sd-arrow-icon" id="arrow_<?= $collapseId ?>"><i class="fa-solid fa-chevron-right text-muted" style="font-size: 9px;"></i></span>
                                            <div>
                                                <div class="fw-bold text-dark" style="color:var(--sd-text-main)!important; font-size: 12.5px;">
                                                    <?= htmlspecialchars($t['name']) ?>
                                                </div>
                                                <small class="text-muted" style="font-size: 10px;"><?= count($t['executives'] ?? []) ?> members</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="fw-bold text-success"><?= sd_fmt_cr($t['confirmed_value'] ?? $t['booking_value'] ?? 0) ?></td>
                                    <td class="text-muted fw-semibold">
                                        <?= ($t['target_value'] ?? 0) > 0 ? sd_fmt_cr($t['target_value']) : 'Not set' ?>
                                    </td>
                                    <td>
                                        <span class="fw-bold <?= ($t['achieved_pct'] ?? 0) >= 80 ? 'text-success' : 'text-dark' ?>" style="font-size: 11.5px;">
                                            <?= $t['achieved_pct'] ?? 0 ?>%
                                        </span>
                                    </td>
                                    <td class="text-center fw-semibold"><?= $t['units'] ?? 0 ?></td>
                                    <td class="text-center fw-semibold text-primary"><?= $t['shares'] ?? 0 ?></td>
                                    <td class="text-center fw-bold <?= ($t['fu_1'] ?? 90) >= 80 ? 'text-success' : 'text-warning' ?>"><?= $t['fu_1'] ?? 90 ?>%</td>
                                    <td class="text-center fw-bold <?= ($t['fu_2'] ?? 80) >= 80 ? 'text-success' : 'text-warning' ?>"><?= $t['fu_2'] ?? 80 ?>%</td>
                                    <td class="text-center fw-bold <?= ($t['fu_3'] ?? 75) >= 80 ? 'text-success' : 'text-warning' ?>"><?= $t['fu_3'] ?? 75 ?>%</td>
                                    <td class="text-center fw-bold <?= ($t['fu_4'] ?? 80) >= 80 ? 'text-success' : 'text-warning' ?>"><?= $t['fu_4'] ?? 80 ?>%</td>
                                    <td class="text-center fw-bold <?= ($t['fu_5'] ?? 80) >= 80 ? 'text-success' : 'text-warning' ?>"><?= $t['fu_5'] ?? 80 ?>%</td>
                                    <td class="text-center fw-bold text-danger"><?= $t['overdue'] ?? 0 ?></td>
                                </tr>

                                <!-- Child Executives Row (Expandable) -->
                                <tr id="<?= $collapseId ?>" class="sd-child-row" style="display: none;">
                                    <td colspan="12" class="p-0">
                                        <div class="p-2 border-start border-3 border-teal m-1 rounded" style="background: var(--sd-card-bg); border-color: var(--sd-teal) !important;">
                                            <div class="d-flex justify-content-between align-items-center mb-1 pb-1 border-bottom">
                                                <span class="fw-bold text-muted text-uppercase" style="font-size: 10px;">
                                                    <i class="fa-solid fa-users me-1"></i> <?= htmlspecialchars($t['name']) ?> Executives
                                                </span>
                                                <small class="text-muted" style="font-size: 10px;"><?= count($t['executives'] ?? []) ?> Executives</small>
                                            </div>

                                            <?php if (!empty($t['executives'])): ?>
                                                <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 11px;">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Executive</th>
                                                            <th>Designation</th>
                                                            <th>Target</th>
                                                            <th>Net Sales</th>
                                                            <th class="text-center">Bookings</th>
                                                            <th class="text-center">Confirmed</th>
                                                            <th>Booking Money</th>
                                                            <th>Reg. Money</th>
                                                            <th class="text-end">Pending Verification</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($t['executives'] as $ex): ?>
                                                            <tr>
                                                                <td class="fw-bold"><?= htmlspecialchars($ex['name']) ?></td>
                                                                <td class="text-muted"><?= htmlspecialchars($ex['role'] ?? 'Sales Executive') ?></td>
                                                                <td class="text-muted"><?= ($ex['target_value'] ?? 0) > 0 ? sd_fmt_cr($ex['target_value']) : 'Not set' ?></td>
                                                                <td class="fw-bold text-success"><?= sd_fmt_cr($ex['confirmed_value'] ?? $ex['booking_value'] ?? 0) ?></td>
                                                                <td class="text-center"><?= $ex['bookings'] ?? 0 ?></td>
                                                                <td class="text-center fw-bold text-success"><?= $ex['confirmed_sales'] ?? 0 ?></td>
                                                                <td class="text-primary"><?= sd_fmt_cr($ex['booking_money'] ?? 0) ?></td>
                                                                <td class="text-info"><?= sd_fmt_cr($ex['registration_money'] ?? 0) ?></td>
                                                                <td class="text-end">
                                                                    <?php if (($ex['pending_money'] ?? 0) > 0): ?>
                                                                        <span class="badge bg-warning text-dark" style="font-size: 9.5px;"><?= sd_fmt_cr($ex['pending_money']) ?></span>
                                                                    <?php else: ?>
                                                                        <span class="text-muted">—</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            <?php else: ?>
                                                <div class="text-muted small text-center py-1">No individual executives active under this team.</div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="12" class="text-center text-muted py-3">No team performance records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Individuals View Table (Hidden by default) -->
            <div class="table-responsive" id="individualsTableView" style="display: none;">
                <table class="sd-table">
                    <thead>
                        <tr>
                            <th>Executive</th>
                            <th>Team</th>
                            <th>Net Sales</th>
                            <th>Target</th>
                            <th>Achieved</th>
                            <th class="text-center">Bookings</th>
                            <th class="text-center">Confirmed</th>
                            <th>Booking Money</th>
                            <th>Reg. Money</th>
                            <th class="text-end">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($executivesList)): ?>
                            <?php foreach ($executivesList as $ex): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark" style="color:var(--sd-text-main)!important; font-size: 12px;"><?= htmlspecialchars($ex['name']) ?></div>
                                        <small class="text-muted" style="font-size: 10px;"><?= htmlspecialchars($ex['role'] ?? 'Sales Executive') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border" style="font-size: 10px;"><?= htmlspecialchars($ex['team_name'] ?? 'Direct') ?></span>
                                    </td>
                                    <td class="fw-bold text-success"><?= sd_fmt_cr($ex['confirmed_value'] ?? $ex['booking_value'] ?? 0) ?></td>
                                    <td class="text-muted"><?= ($ex['target_value'] ?? 0) > 0 ? sd_fmt_cr($ex['target_value']) : 'Not set' ?></td>
                                    <td>
                                        <?php 
                                        $eTarget = (float)($ex['target_value'] ?? 0);
                                        $eConf = (float)($ex['confirmed_value'] ?? $ex['booking_value'] ?? 0);
                                        $eAch = $eTarget > 0 ? min(100, round(($eConf / $eTarget) * 100)) : 0;
                                        ?>
                                        <span class="fw-bold <?= $eAch >= 80 ? 'text-success' : 'text-dark' ?>" style="font-size: 11px;"><?= $eAch ?>%</span>
                                    </td>
                                    <td class="text-center"><?= $ex['bookings'] ?? 0 ?></td>
                                    <td class="text-center fw-bold text-success"><?= $ex['confirmed_sales'] ?? 0 ?></td>
                                    <td class="text-primary"><?= sd_fmt_cr($ex['booking_money'] ?? 0) ?></td>
                                    <td class="text-info"><?= sd_fmt_cr($ex['registration_money'] ?? 0) ?></td>
                                    <td class="text-end">
                                        <?php if (($ex['confirmed_sales'] ?? 0) > 0): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 9.5px;">Active Closer</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-muted" style="font-size: 9.5px;">Pipeline</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-3">No executive records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-2 text-muted" style="font-size: 10.5px;">
                Select a team or switch to Individuals to inspect sales, eligible follow-ups, and the exact overdue lead list.
            </div>
        </div>

        <!-- ====================================================================
             RECENT SALES UPDATES & PRIORITY ACTIONS
             ==================================================================== -->
        <div class="row g-2 mb-3">
            <!-- Recent sales updates -->
            <div class="col-12 col-xl-6">
                <div class="sd-card h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h5 class="fw-bold m-0" style="font-size: 15px;">Recent sales updates</h5>
                            <a href="<?= $GLOBALS['base_url'] ?>/booking-leads" class="text-decoration-none small fw-bold" style="color: var(--sd-teal); font-size: 11.5px;">
                                Full history <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                        <div class="text-muted mb-2" style="font-size: 11px;">Latest status changes • <?= date('d M Y') ?> • Audit history retained</div>

                        <?php if (!empty($recentSalesUpdates)): ?>
                            <?php foreach ($recentSalesUpdates as $upd): ?>
                                <?php 
                                $timeStr = !empty($upd['created_at']) ? date('H:i', strtotime($upd['created_at'])) : date('H:i');
                                $isVerified = (int)($upd['is_verified'] ?? 0) === 1;
                                $isCancelled = ($upd['booking_status'] ?? '') === 'cancelled';
                                $isShare = ($upd['item_type'] ?? '') === 'share';
                                ?>
                                <div class="sd-timeline-item">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="sd-time-badge"><?= $timeStr ?></div>
                                        <div>
                                            <div class="fw-bold text-dark" style="color:var(--sd-text-main)!important; font-size: 12px;">
                                                <?= htmlspecialchars($upd['proj_name'] ?? 'Project') ?> • <?= htmlspecialchars($upd['flat_name'] ?? 'Unit') ?>
                                            </div>
                                            <div class="text-muted" style="font-size: 10.5px;">
                                                <?= $isCancelled ? 'Stock released • Refund pending' : 'Approved • ' . htmlspecialchars($upd['team_name'] ?? $upd['sales_person'] ?? 'Sales') ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if ($isCancelled): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 10px;">Sale cancelled</span>
                                        <?php elseif ($isVerified && $isShare): ?>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size: 10px;">Shares verified</span>
                                        <?php elseif ($isVerified): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 10px;">Sales verified</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size: 10px;">Pending approval</span>
                                        <?php endif; ?>
                                        <div class="fw-bold text-dark text-end" style="color:var(--sd-text-main)!important; font-size: 12px; min-width: 75px;">
                                            <?= sd_fmt_cr($upd['val']) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-3 small">No recent status updates in this cycle.</div>
                        <?php endif; ?>
                    </div>
                    <div class="sd-kpi-footer mt-2 pt-2 border-top">
                        Recent events are a sample; period totals include all qualifying transactions.
                    </div>
                </div>
            </div>

            <!-- Priority actions -->
            <div class="col-12 col-xl-6">
                <div class="sd-card h-100 d-flex flex-column justify-content-between">
                    <div>
                        <h5 class="fw-bold m-0" style="font-size: 15px;">Priority actions</h5>
                        <div class="text-muted mb-2" style="font-size: 11px;">Open exceptions as of snapshot • Counts open the exact records</div>

                        <!-- 1. Verify pending sales -->
                        <div class="sd-priority-item">
                            <div>
                                <div class="fw-bold text-dark" style="color:var(--sd-text-main)!important; font-size: 12px;">Verify pending sales</div>
                                <div class="text-muted" style="font-size: 11px;">
                                    <?= $priorityActions['pending_sales']['units'] ?> units + <?= $priorityActions['pending_sales']['shares'] ?> shares • <?= sd_fmt_cr($priorityActions['pending_sales']['amount']) ?> unconfirmed
                                </div>
                                <small class="text-muted" style="font-size: 10px;">Sales operations</small>
                            </div>
                            <a href="javascript:void(0)" onclick="showDrilldown('awaiting')" class="sd-priority-btn">
                                Review
                            </a>
                        </div>

                        <!-- 2. Clear overdue follow-ups -->
                        <div class="sd-priority-item">
                            <div>
                                <div class="fw-bold text-dark" style="color:var(--sd-text-main)!important; font-size: 12px;">Clear overdue follow-ups</div>
                                <div class="text-muted" style="font-size: 11px;">
                                    <?= $priorityActions['overdue_followups']['count'] ?> overdue actions • <?= $priorityActions['overdue_followups']['teams_count'] ?> team groups
                                </div>
                                <small class="text-muted" style="font-size: 10px;">Sales team leaders</small>
                            </div>
                            <a href="<?= $GLOBALS['base_url'] ?>/lead-follow-ups" class="sd-priority-btn">
                                Open list
                            </a>
                        </div>

                        <!-- 3. Review expiring unit locks -->
                        <div class="sd-priority-item">
                            <div>
                                <div class="fw-bold text-dark" style="color:var(--sd-text-main)!important; font-size: 12px;">Review expiring unit locks</div>
                                <div class="text-muted" style="font-size: 11px;">
                                    <?= $priorityActions['expiring_locks']['expiring_today'] ?> of <?= $priorityActions['expiring_locks']['total'] ?> locks expire today
                                </div>
                                <small class="text-muted" style="font-size: 10px;">Sales operations</small>
                            </div>
                            <a href="<?= $GLOBALS['base_url'] ?>/flats?status=locked" class="sd-priority-btn">
                                Review
                            </a>
                        </div>
                    </div>
                    <div class="sd-kpi-footer mt-2 pt-2 border-top">
                        Additional queues: overdue collections, refunds and incomplete sales records.
                    </div>
                </div>
            </div>
        </div>

        <!-- ====================================================================
             PAGE FOOTER NOTES
             ==================================================================== -->
        <div class="d-flex flex-wrap justify-content-between align-items-center text-muted mt-2 pt-2 border-top" style="font-size: 10.5px; line-height: 1.4;">
            <div>
                All figures are illustrative. Cr = BDT crore. Sales value, money collected, physical units and shares are separate measures.<br>
                Net sales = verified sales less reversals. Stock, pending verification and overdue balances use the snapshot date.
            </div>
            <div class="mt-2 mt-md-0 fw-semibold text-uppercase" style="letter-spacing: 0.05em; font-size: 10px;">
                TPHL • SALES OPERATIONS
            </div>
        </div>
    </div>

<!-- Definitions Modal -->
<div class="modal fade" id="definitionsModal" tabindex="-1" aria-labelledby="definitionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="background: var(--sd-card-bg); color: var(--sd-text-main); border: 1px solid var(--sd-card-border); border-radius: 8px;">
            <div class="modal-header border-bottom py-2">
                <h6 class="modal-title fw-bold" id="definitionsModalLabel">
                    <i class="fa-solid fa-book-open me-2 text-primary"></i>Sales Dashboard Definitions & Reconciliation Rules
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-size: 12.5px; line-height: 1.6;">
                <p class="text-muted mb-2">All numbers on this dashboard reconcile with <code>lead-lists</code>, <code>clients-cash-in-history</code>, and the <code>sales incentive module</code>:</p>
                <ul class="list-group list-group-flush mb-0">
                    <li class="list-group-item bg-transparent py-2" style="color: inherit; border-color: var(--sd-card-border);">
                        <strong>Reporting Cycle:</strong> Default cycle is from the 26th of the previous month to the 25th of the current month.
                    </li>
                    <li class="list-group-item bg-transparent py-2" style="color: inherit; border-color: var(--sd-card-border);">
                        <strong>Sales Credit (sales_by_id):</strong> Credited to <code>lead_bookings.sales_by_id</code> (the closing agent), with fallback to <code>leads.assigned_id</code>.
                    </li>
                    <li class="list-group-item bg-transparent py-2" style="color: inherit; border-color: var(--sd-card-border);">
                        <strong>Verified Sales vs Awaiting Verification:</strong> Confirmed sales require at least one verified payment receipt from Accounts (<code>verification_status = 'verified'</code>).
                    </li>
                    <li class="list-group-item bg-transparent py-2" style="color: inherit; border-color: var(--sd-card-border);">
                        <strong>Collections (5 Categories):</strong> Split into Booking Money, Registration, Development Charge, EMI/Installments, and Other.
                    </li>
                </ul>
            </div>
            <div class="modal-footer border-top py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" style="font-size: 12px;">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Interactive Drilldown Modal -->
<div class="modal fade" id="drilldownModal" tabindex="-1" aria-labelledby="drilldownModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="background: var(--sd-card-bg); color: var(--sd-text-main); border: 1px solid var(--sd-card-border); border-radius: 8px;">
            <div class="modal-header border-bottom py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="rounded p-1 px-2 text-white fw-bold" id="drilldownIconBadge" style="background: var(--sd-teal); font-size: 11px;">
                        <i class="fa-solid fa-layer-group"></i>
                    </span>
                    <div>
                        <h6 class="modal-title fw-bold m-0" id="drilldownModalLabel" style="font-size: 14.5px;">
                            Drilldown Details
                        </h6>
                        <small class="text-muted" id="drilldownModalSub" style="font-size: 11px;">Detailed records for this cycle</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark border fw-bold" id="drilldownCountBadge" style="font-size: 11px;">0 Records</span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            
            <div class="modal-body p-3">
                <!-- Search & Filters inside Modal -->
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-12 col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-transparent border-end-0 text-muted" style="border-color: var(--sd-card-border);">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </span>
                            <input type="text" id="drilldownSearchInput" class="form-control border-start-0 sd-form-input" placeholder="Search by client, project, flat/unit, agent..." onkeyup="filterDrilldownTable()">
                        </div>
                    </div>
                    <div class="col-12 col-md-4 text-center">
                        <div class="btn-group btn-group-sm" role="group" id="drilldownFilterTabsContainer" style="display: none;">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="tabBtnAll" onclick="setDrilldownSubFilter('all')" style="font-size: 11px;">
                                All (<span id="tabCountAll">0</span>)
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm" id="tabBtnVerified" onclick="setDrilldownSubFilter('verified')" style="font-size: 11px;">
                                Verified (<span id="tabCountVerified">0</span>)
                            </button>
                            <button type="button" class="btn btn-outline-warning text-dark btn-sm" id="tabBtnAwaiting" onclick="setDrilldownSubFilter('awaiting')" style="font-size: 11px;">
                                Awaiting (<span id="tabCountAwaiting">0</span>)
                            </button>
                        </div>
                    </div>
                    <div class="col-12 col-md-3 text-md-end">
                        <span class="text-muted small me-2">Total Value:</span>
                        <strong class="text-success" id="drilldownTotalAmount" style="font-size: 14px;">BDT 0</strong>
                    </div>
                </div>

                <!-- Table Content Container -->
                <div class="table-responsive" style="max-height: 520px;">
                    <table class="sd-table table-bordered mb-0" id="drilldownDataTable">
                        <thead id="drilldownTableHead">
                            <!-- Injected by JS -->
                        </thead>
                        <tbody id="drilldownTableBody">
                            <!-- Injected by JS -->
                        </tbody>
                    </table>
                </div>

                <div id="drilldownEmptyState" class="text-center py-5 text-muted" style="display: none;">
                    <i class="fa-regular fa-folder-open fa-2x mb-2 d-block opacity-50"></i>
                    No records found matching this criteria.
                </div>
            </div>

            <div class="modal-footer border-top py-2 px-3 d-flex justify-content-between">
                <div class="text-muted small" style="font-size: 11px;">
                    <i class="fa-solid fa-circle-info me-1 text-primary"></i> Data reflects verified records and snapshot metrics.
                </div>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" style="font-size: 12px;">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Drilldown Data from Controller
const drilldownData = <?= json_encode($drilldown ?? []) ?>;
const baseAppUrl = '<?= $GLOBALS['base_url'] ?>';
let baseDrilldownRows = [];
let currentDrilldownRows = [];
let currentSubFilter = 'all';
let isCurrentCollection = false;

function fmtCr(val) {
    val = parseFloat(val) || 0;
    if (val >= 10000000) {
        return 'BDT ' + (val / 10000000).toFixed(2) + ' Cr';
    }
    if (val >= 100000) {
        return 'BDT ' + (val / 100000).toFixed(2) + ' L';
    }
    return 'BDT ' + val.toLocaleString();
}

function setDrilldownSubFilter(filter) {
    currentSubFilter = filter;
    applyDrilldownSubFilter();
}

function applyDrilldownSubFilter() {
    const tabsCont = document.getElementById('drilldownFilterTabsContainer');
    const tabAll = document.getElementById('tabBtnAll');
    const tabVer = document.getElementById('tabBtnVerified');
    const tabAw = document.getElementById('tabBtnAwaiting');
    const cntAll = document.getElementById('tabCountAll');
    const cntVer = document.getElementById('tabCountVerified');
    const cntAw = document.getElementById('tabCountAwaiting');

    if (!tabsCont || isCurrentCollection) {
        if (tabsCont) tabsCont.style.display = 'none';
        currentDrilldownRows = baseDrilldownRows;
    } else {
        tabsCont.style.display = 'inline-flex';
        const numAll = baseDrilldownRows.length;
        const numVer = baseDrilldownRows.filter(r => parseInt(r.is_verified) === 1 && r.booking_status !== 'cancelled').length;
        const numAw = baseDrilldownRows.filter(r => parseInt(r.is_verified) !== 1 && r.booking_status !== 'cancelled').length;

        if (cntAll) cntAll.textContent = numAll;
        if (cntVer) cntVer.textContent = numVer;
        if (cntAw) cntAw.textContent = numAw;

        // Toggle active classes
        [tabAll, tabVer, tabAw].forEach(btn => {
            if (btn) btn.classList.remove('active');
        });

        if (currentSubFilter === 'verified') {
            if (tabVer) tabVer.classList.add('active');
            currentDrilldownRows = baseDrilldownRows.filter(r => parseInt(r.is_verified) === 1 && r.booking_status !== 'cancelled');
        } else if (currentSubFilter === 'awaiting') {
            if (tabAw) tabAw.classList.add('active');
            currentDrilldownRows = baseDrilldownRows.filter(r => parseInt(r.is_verified) !== 1 && r.booking_status !== 'cancelled');
        } else {
            if (tabAll) tabAll.classList.add('active');
            currentDrilldownRows = baseDrilldownRows;
        }
    }

    filterDrilldownTable();
}

function showDrilldown(type) {
    const modalEl = document.getElementById('drilldownModal');
    if (!modalEl) return;
    const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);

    const titleEl = document.getElementById('drilldownModalLabel');
    const subEl = document.getElementById('drilldownModalSub');
    const headEl = document.getElementById('drilldownTableHead');
    const searchInput = document.getElementById('drilldownSearchInput');

    if (searchInput) searchInput.value = '';

    let title = '';
    let sub = 'Reporting Cycle: <?= htmlspecialchars($p['cycle_label']) ?>';
    isCurrentCollection = false;

    if (type === 'verified_sales') {
        title = 'Net Verified Sales';
        baseDrilldownRows = (drilldownData.all_sales && drilldownData.all_sales.length > 0) ? drilldownData.all_sales : (drilldownData.verified_sales || []);
        currentSubFilter = (drilldownData.verified_sales && drilldownData.verified_sales.length > 0) ? 'verified' : 'all';
    } else if (type === 'whole_units') {
        title = 'Net Whole Units Sold';
        baseDrilldownRows = drilldownData.whole_units || [];
        currentSubFilter = 'all';
    } else if (type === 'shares') {
        title = 'Group-buying Shares Sold (The Nature)';
        sub = 'Group shares with letter suffixes (e.g. 1001a, 102b, 616C)';
        baseDrilldownRows = drilldownData.shares || [];
        currentSubFilter = 'all';
    } else if (type === 'awaiting') {
        title = 'Awaiting Sales Verification';
        sub = 'Bookings awaiting accounts verification / unconfirmed receipts';
        baseDrilldownRows = (drilldownData.all_sales && drilldownData.all_sales.length > 0) ? drilldownData.all_sales : (drilldownData.awaiting || []);
        currentSubFilter = 'awaiting';
    } else if (type === 'cancelled') {
        title = 'Sales Cancelled & Reversed';
        baseDrilldownRows = drilldownData.cancelled || [];
        currentSubFilter = 'all';
    } else if (type === 'collections') {
        title = 'Money Collected & Verified';
        sub = 'Verified money receipts during this cycle (Booking, Reg, EMI, Dev Charge, Other)';
        baseDrilldownRows = drilldownData.collections || [];
        currentSubFilter = 'all';
        isCurrentCollection = true;
    } else if (type === 'target_gap') {
        title = 'Sales Target Fulfilment Status';
        baseDrilldownRows = (drilldownData.all_sales && drilldownData.all_sales.length > 0) ? drilldownData.all_sales : (drilldownData.verified_sales || []);
        currentSubFilter = 'verified';
    }

    titleEl.textContent = title;
    subEl.textContent = sub;

    if (isCurrentCollection) {
        headEl.innerHTML = `
            <tr>
                <th style="width: 35px;">#</th>
                <th>Client / Lead</th>
                <th>Project & Flat</th>
                <th>Payment Head</th>
                <th>Amount</th>
                <th>Payment Date</th>
                <th>Sales Agent</th>
                <th class="text-end">Action</th>
            </tr>
        `;
    } else {
        headEl.innerHTML = `
            <tr>
                <th style="width: 35px;">#</th>
                <th>Client / Lead</th>
                <th>Project</th>
                <th>Flat / Unit</th>
                <th>Type</th>
                <th>Value</th>
                <th>Sales Agent & Team</th>
                <th>Status</th>
                <th>Booking Date</th>
                <th class="text-end">Action</th>
            </tr>
        `;
    }

    applyDrilldownSubFilter();
    bsModal.show();
}

function showProjectSales(projectId, projectName) {
    const modalEl = document.getElementById('drilldownModal');
    if (!modalEl) return;
    const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);

    const titleEl = document.getElementById('drilldownModalLabel');
    const subEl = document.getElementById('drilldownModalSub');
    const headEl = document.getElementById('drilldownTableHead');
    const searchInput = document.getElementById('drilldownSearchInput');

    if (searchInput) searchInput.value = '';

    baseDrilldownRows = (drilldownData.by_project && drilldownData.by_project[projectId]) ? drilldownData.by_project[projectId] : [];
    currentSubFilter = 'all';
    isCurrentCollection = false;

    titleEl.textContent = projectName + ' - Project Sales & Bookings';
    subEl.textContent = 'All booked and verified units/shares for ' + projectName + ' in this cycle';

    headEl.innerHTML = `
        <tr>
            <th style="width: 35px;">#</th>
            <th>Client / Lead</th>
            <th>Project</th>
            <th>Flat / Unit</th>
            <th>Type</th>
            <th>Value</th>
            <th>Sales Agent & Team</th>
            <th>Status</th>
            <th>Booking Date</th>
            <th class="text-end">Action</th>
        </tr>
    `;

    applyDrilldownSubFilter();
    bsModal.show();
}

function renderDrilldownRows(rows, isCollection) {
    const bodyEl = document.getElementById('drilldownTableBody');
    const emptyEl = document.getElementById('drilldownEmptyState');
    const totalAmountEl = document.getElementById('drilldownTotalAmount');

    if (!rows || rows.length === 0) {
        bodyEl.innerHTML = '';
        if (emptyEl) emptyEl.style.display = 'block';
        if (totalAmountEl) totalAmountEl.textContent = 'BDT 0';
        return;
    }

    if (emptyEl) emptyEl.style.display = 'none';

    let totalSum = 0;
    let html = '';

    rows.forEach((r, idx) => {
        if (isCollection) {
            const amt = parseFloat(r.amount) || 0;
            totalSum += amt;
            const headName = (r.payment_type || 'Payment').replace(/_/g, ' ').toUpperCase();
            html += `
                <tr>
                    <td>${idx + 1}</td>
                    <td>
                        <div class="fw-bold">${escapeHtml(r.client_name || 'Client')}</div>
                        <small class="text-muted">${escapeHtml(r.client_phone || '')}</small>
                    </td>
                    <td>
                        <div>${escapeHtml(r.project_name || '—')}</div>
                        <small class="text-muted">${escapeHtml(r.flat_name || '')}</small>
                    </td>
                    <td><span class="badge bg-light text-dark border">${headName}</span></td>
                    <td class="fw-bold text-success">${fmtCr(amt)}</td>
                    <td>${r.payment_date || '—'}</td>
                    <td>
                        <div>${escapeHtml(r.sales_person || 'Unassigned')}</div>
                        <small class="text-muted">${escapeHtml(r.team_name || '')}</small>
                    </td>
                    <td class="text-end">
                        <a href="${baseAppUrl}/view-lead/${r.lead_id}" class="btn btn-sm btn-outline-primary" style="font-size: 11px; padding: 2px 8px;">
                            View Lead
                        </a>
                    </td>
                </tr>
            `;
        } else {
            const val = parseFloat(r.val) || 0;
            totalSum += val;
            const isShare = r.item_type === 'share';
            const isVer = parseInt(r.is_verified) === 1;
            const isCan = r.booking_status === 'cancelled';

            let statusBadge = '<span class="badge bg-warning text-dark">Awaiting Verification</span>';
            if (isCan) {
                statusBadge = '<span class="badge bg-danger">Cancelled</span>';
            } else if (isVer) {
                statusBadge = '<span class="badge bg-success">Verified Sale</span>';
            }

            const typeBadge = isShare 
                ? '<span class="badge bg-info-subtle text-info border border-info-subtle">Group Share</span>'
                : '<span class="badge bg-primary-subtle text-primary border border-primary-subtle">Whole Unit</span>';

            html += `
                <tr>
                    <td>${idx + 1}</td>
                    <td>
                        <div class="fw-bold">${escapeHtml(r.client_name || 'Client')}</div>
                        <small class="text-muted">${escapeHtml(r.client_phone || '')}</small>
                    </td>
                    <td>${escapeHtml(r.project_name || '—')}</td>
                    <td class="fw-bold">${escapeHtml(r.flat_name || '—')}</td>
                    <td>${typeBadge}</td>
                    <td class="fw-bold text-success">${fmtCr(val)}</td>
                    <td>
                        <div>${escapeHtml(r.sales_person || 'Unassigned')}</div>
                        <small class="text-muted">${escapeHtml(r.team_name || '')}</small>
                    </td>
                    <td>${statusBadge}</td>
                    <td>${r.booking_date || '—'}</td>
                    <td class="text-end">
                        <a href="${baseAppUrl}/view-lead/${r.lead_id}" class="btn btn-sm btn-outline-primary" style="font-size: 11px; padding: 2px 8px;">
                            View Lead
                        </a>
                    </td>
                </tr>
            `;
        }
    });

    bodyEl.innerHTML = html;
    if (totalAmountEl) totalAmountEl.textContent = fmtCr(totalSum);
    const badgeEl = document.getElementById('drilldownCountBadge');
    if (badgeEl) badgeEl.textContent = (rows ? rows.length : 0) + ' Records';
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function filterDrilldownTable() {
    const input = document.getElementById('drilldownSearchInput');
    const term = (input ? input.value : '').toLowerCase().trim();
    if (!term) {
        renderDrilldownRows(currentDrilldownRows, isCurrentCollection);
        return;
    }

    const filtered = currentDrilldownRows.filter(r => {
        const cName = (r.client_name || '').toLowerCase();
        const cPhone = (r.client_phone || '').toLowerCase();
        const pName = (r.project_name || '').toLowerCase();
        const fName = (r.flat_name || '').toLowerCase();
        const sPerson = (r.sales_person || '').toLowerCase();
        const tName = (r.team_name || '').toLowerCase();
        const pType = (r.payment_type || '').toLowerCase();

        return cName.includes(term) || cPhone.includes(term) || pName.includes(term) 
            || fName.includes(term) || sPerson.includes(term) || tName.includes(term) || pType.includes(term);
    });

    renderDrilldownRows(filtered, isCurrentCollection);
}

// Switch between Teams and Individuals view
function switchPerfView(viewType) {
    const teamsView = document.getElementById('teamsTableView');
    const individualsView = document.getElementById('individualsTableView');
    const btnTeams = document.getElementById('btnViewTeams');
    const btnIndividuals = document.getElementById('btnViewIndividuals');

    if (viewType === 'individuals') {
        if (teamsView) teamsView.style.display = 'none';
        if (individualsView) individualsView.style.display = 'block';
        if (btnTeams) btnTeams.classList.remove('active');
        if (btnIndividuals) btnIndividuals.classList.add('active');
    } else {
        if (teamsView) teamsView.style.display = 'block';
        if (individualsView) individualsView.style.display = 'none';
        if (btnTeams) btnTeams.classList.add('active');
        if (btnIndividuals) btnIndividuals.classList.remove('active');
    }
}

// Toggle Team Drilldown
function toggleTeamDrilldown(rowId, triggerEl) {
    const row = document.getElementById(rowId);
    const arrow = document.getElementById('arrow_' + rowId);
    if (!row) return;

    if (row.style.display === 'none' || row.style.display === '') {
        row.style.display = 'table-row';
        if (arrow) arrow.classList.add('open');
    } else {
        row.style.display = 'none';
        if (arrow) arrow.classList.remove('open');
    }
}

// Sales Value Trend Chart (ApexCharts)
document.addEventListener("DOMContentLoaded", function() {
    const trendData = <?= json_encode($salesTrend) ?>;
    const categories = trendData.map(d => d.month);
    const values = trendData.map(d => d.value_cr);

    const isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark' 
                    || document.body.getAttribute('data-theme') === 'dark'
                    || document.documentElement.getAttribute('data-bs-theme') === 'dark';

    const chartOptions = {
        chart: {
            type: 'bar',
            height: 165,
            toolbar: { show: false },
            fontFamily: 'inherit',
            background: 'transparent'
        },
        theme: {
            mode: isDarkMode ? 'dark' : 'light'
        },
        series: [{
            name: 'Net Contract Value (Cr)',
            data: values
        }],
        colors: ['#0d9488'],
        plotOptions: {
            bar: {
                borderRadius: 4,
                columnWidth: '35%',
                dataLabels: { position: 'top' }
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function(val) {
                return val > 0 ? val : '';
            },
            offsetY: -16,
            style: {
                fontSize: '10px',
                fontWeight: 700,
                colors: [isDarkMode ? '#e2e8f0' : '#334155']
            }
        },
        xaxis: {
            categories: categories,
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: {
                style: {
                    colors: isDarkMode ? '#94a3b8' : '#64748b',
                    fontSize: '11px',
                    fontWeight: 600
                }
            }
        },
        yaxis: {
            title: {
                text: 'BDT crore',
                style: {
                    color: isDarkMode ? '#94a3b8' : '#64748b',
                    fontSize: '10px'
                }
            },
            labels: {
                style: {
                    colors: isDarkMode ? '#94a3b8' : '#64748b',
                    fontSize: '10px'
                }
            }
        },
        grid: {
            borderColor: isDarkMode ? '#334155' : '#f1f5f9',
            strokeDashArray: 4
        },
        tooltip: {
            theme: isDarkMode ? 'dark' : 'light',
            y: {
                formatter: function(val) {
                    return 'BDT ' + val + ' Cr';
                }
            }
        }
    };

    const chart = new ApexCharts(document.querySelector("#sdSalesValueTrendChart"), chartOptions);
    chart.render();
});
</script>
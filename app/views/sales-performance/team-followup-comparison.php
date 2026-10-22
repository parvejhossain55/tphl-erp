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
$topExecutives = $data['top_executives'] ?? [];
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

/* Select2 Compact Modern Styling in Filter Bar */
.tfu-filter-bar .select2-container--default .select2-selection--single {
    height: 33px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    background: #ffffff;
    display: flex;
    align-items: center;
}
.tfu-filter-bar .select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #334155;
    font-size: 13px;
    font-weight: 500;
    line-height: 31px;
    padding-left: 10px;
    padding-right: 24px;
}
.tfu-filter-bar .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 31px;
    right: 8px;
}
.tfu-filter-bar .select2-container--default .select2-selection--single:focus,
.tfu-filter-bar .select2-container--default.select2-container--open .select2-selection--single {
    border-color: var(--tfu-primary);
    box-shadow: 0 0 0 2px rgba(15, 118, 110, 0.15);
}
.select2-dropdown {
    border: 1px solid #cbd5e1 !important;
    border-radius: 8px !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05) !important;
    font-size: 13px !important;
    z-index: 10050 !important;
    overflow: hidden;
}
.select2-search--dropdown {
    padding: 6px 8px !important;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.select2-search--dropdown .select2-search__field {
    border: 1px solid #cbd5e1 !important;
    border-radius: 6px !important;
    padding: 5px 10px !important;
    font-size: 12.5px !important;
    background: #ffffff !important;
    outline: none !important;
}
.select2-search--dropdown .select2-search__field:focus {
    border-color: var(--tfu-primary) !important;
    box-shadow: 0 0 0 2px rgba(15, 118, 110, 0.15) !important;
}
.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
    background-color: var(--tfu-primary) !important;
    color: #ffffff !important;
}
.select2-container--default .select2-results__option--selected {
    background-color: #ecfdf5 !important;
    color: #065f46 !important;
    font-weight: 600;
}
.select2-results__option {
    padding: 6px 12px !important;
    font-size: 13px !important;
    line-height: 1.4 !important;
}

/* Champions / Leaderboard Section */
.tfu-leaderboard-section {
    margin-bottom: 22px;
}
.tfu-leaderboard-hd {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}
.tfu-leaderboard-title {
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
}
.tfu-leaderboard-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}
@media (max-width: 1280px) {
    .tfu-leaderboard-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
    .tfu-leaderboard-grid { grid-template-columns: 1fr; }
}

.tfu-champion-card {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid var(--tfu-border);
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    display: flex;
    flex-direction: column;
    position: relative;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.tfu-champion-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.06);
}

/* Team Champion Special Theme */
.tfu-champion-card.tfu-team-champion {
    background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 42%);
    border-color: #86efac;
}
/* Gold 1st Place Executive */
.tfu-champion-card.tfu-exec-gold {
    background: linear-gradient(180deg, #fefce8 0%, #ffffff 42%);
    border-color: #fde047;
}
/* Silver 2nd Place Executive */
.tfu-champion-card.tfu-exec-silver {
    background: linear-gradient(180deg, #f8fafc 0%, #ffffff 42%);
    border-color: #cbd5e1;
}
/* Bronze 3rd Place Executive */
.tfu-champion-card.tfu-exec-bronze {
    background: linear-gradient(180deg, #fff7ed 0%, #ffffff 42%);
    border-color: #fdba74;
}

.tfu-champion-badge {
    position: absolute;
    top: 10px;
    right: 12px;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 4px;
}
.tfu-team-champion .tfu-champion-badge {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #86efac;
}

.tfu-champion-body {
    padding: 16px 16px 10px 16px;
    flex: 1;
    display: flex;
    flex-direction: column;
}
.tfu-champion-top-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}
.tfu-champion-icon-wrap {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.team-trophy-wrap {
    background: #dcfce7;
    color: #15803d;
    border: 1px solid #86efac;
}
.tfu-champion-avatar-wrap {
    position: relative;
    width: 44px;
    height: 44px;
    flex-shrink: 0;
}
.tfu-champion-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e2e8f0;
}
.tfu-champion-avatar-initials {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 700;
    color: #ffffff;
    background: #64748b;
}
.tfu-exec-gold .tfu-champion-avatar-initials {
    background: linear-gradient(135deg, #eab308 0%, #ca8a04 100%);
    box-shadow: 0 2px 6px rgba(202, 138, 4, 0.3);
}
.tfu-exec-silver .tfu-champion-avatar-initials {
    background: linear-gradient(135deg, #94a3b8 0%, #475569 100%);
    box-shadow: 0 2px 6px rgba(71, 85, 105, 0.3);
}
.tfu-exec-bronze .tfu-champion-avatar-initials {
    background: linear-gradient(135deg, #f97316 0%, #c2410c 100%);
    box-shadow: 0 2px 6px rgba(194, 65, 12, 0.3);
}
.tfu-avatar-rank-badge {
    position: absolute;
    bottom: -4px;
    right: -4px;
    font-size: 13px;
    line-height: 1;
}

.tfu-champion-info {
    min-width: 0;
    flex: 1;
}
.tfu-champion-name {
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 2px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.tfu-champion-meta {
    font-size: 11.5px;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.tfu-champion-score-box {
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    border-radius: 8px;
    padding: 10px 12px;
    margin-top: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.tfu-champion-rate-col {
    display: flex;
    flex-direction: column;
}
.tfu-champion-rate-num {
    font-size: 22px;
    font-weight: 800;
    line-height: 1.1;
    letter-spacing: -0.02em;
}
.tfu-champion-rate-lbl {
    font-size: 10px;
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.tfu-champion-stats-col {
    text-align: right;
    font-size: 11.5px;
    color: #475569;
    line-height: 1.4;
}
.tfu-champion-ft {
    padding: 8px 16px 12px 16px;
    background: transparent;
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
.tfu-table thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    background-color: #f8fafc !important;
    box-shadow: inset 0 -1px 0 var(--tfu-border);
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
    background: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(2px);
    z-index: 9998;
}
.tfu-modal-drawer {
    display: none;
    position: fixed;
    top: 0; right: 0; bottom: 0;
    width: 1120px;
    max-width: 96vw;
    background: #ffffff;
    box-shadow: -6px 0 28px rgba(0,0,0,0.18);
    z-index: 9999;
    flex-direction: column;
    overflow: hidden;
}
.tfu-modal-drawer.active, .tfu-modal-backdrop.active {
    display: flex;
}
.tfu-modal-hd {
    padding: 16px 24px;
    border-bottom: 1px solid var(--tfu-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f8fafc;
}
.tfu-modal-bd {
    padding: 16px 24px;
    overflow: hidden;
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
}
.tfu-drill-table-container {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    overflow-x: auto;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #ffffff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}
.tfu-drill-table-container table {
    width: 100%;
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
}
.tfu-drill-table-container thead th {
    position: sticky;
    top: 0;
    z-index: 20;
    background-color: #f8fafc !important;
    color: #475569;
    font-weight: 700;
    font-size: 12px;
    padding: 11px 12px;
    border-bottom: 1px solid #cbd5e1;
    box-shadow: inset 0 -1px 0 #cbd5e1, 0 2px 4px rgba(0,0,0,0.04);
    white-space: nowrap;
}
.tfu-drill-journey-container {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    padding-right: 6px;
}
.tfu-modal-ft {
    padding: 12px 24px;
    border-top: 1px solid var(--tfu-border);
    background: #f8fafc;
    display: flex;
    justify-content: flex-end;
}
.tfu-modal-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 0;
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
    transition: all 0.15s ease;
}
.tfu-tab-pill.active {
    background: var(--tfu-primary);
    border-color: var(--tfu-primary);
    color: #ffffff;
}

/* Follow-up Journey Cards */
.tfu-journey-card {
    background: #ffffff;
    border: 1px solid var(--tfu-border);
    border-radius: 10px;
    padding: 16px 18px;
    margin-bottom: 14px;
    position: relative;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}
.tfu-journey-card.ontime {
    border-left: 4px solid #10b981;
}
.tfu-journey-card.late {
    border-left: 4px solid #f59e0b;
}
.tfu-journey-card.overdue {
    border-left: 4px solid #ef4444;
    background: #fffafa;
}
.tfu-journey-note {
    background: #f8fafc;
    border-left: 3px solid #94a3b8;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 12.5px;
    color: #334155;
    margin-top: 8px;
    white-space: pre-wrap;
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
                    <i class="fa-solid fa-ranking-star me-1"></i> Sales Intelligence
                </a>
            </div>
        </div>

        <!-- Filter Form -->
        <form method="GET" action="<?= $baseUrl ?>/team-followup-comparison" id="tfuFilterForm">
            <input type="hidden" name="view" value="<?= tfu_h($filters['view'] ?? 'teams') ?>">
            <input type="hidden" name="sort" value="<?= tfu_h($filters['sort'] ?? '') ?>">

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

                <!-- Team Select (Searchable Select2) -->
                <div style="min-width: 190px; max-width: 230px;">
                    <select name="team_id" id="filterTeamSelect" class="form-select form-select-sm select2" data-placeholder="All Teams (<?= count($teams) ?>)">
                        <option value="">All Teams (<?= count($teams) ?>)</option>
                        <?php foreach ($teams as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= ($filters['team_id'] == $t['id']) ? 'selected' : '' ?>>
                                <?= tfu_h($t['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Project Select (Searchable Select2) -->
                <div style="min-width: 200px; max-width: 260px;">
                    <select name="project_id" id="filterProjectSelect" class="form-select form-select-sm select2" data-placeholder="All Projects (<?= count($projects) ?>)">
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

    <!-- Performance Champions (Best Team & Top 3 Executives) -->
    <?php if (($topTeam && ($topTeam['metrics']['overall']['rate'] ?? 0) > 0) || !empty($topExecutives)): ?>
        <div class="tfu-leaderboard-section">
            <div class="tfu-leaderboard-hd">
                <div class="tfu-leaderboard-title">
                    <i class="fa-solid fa-trophy" style="color:#eab308; font-size:17px; margin-right:8px;"></i>
                    <span>Follow-up Performance Champions</span>
                    <span class="badge bg-light text-secondary ms-2" style="font-size:11.5px; font-weight:600; border:1px solid #e2e8f0;">
                        <i class="fa-regular fa-calendar-check me-1"></i><?= tfu_h($period['label'] ?? 'Active Period') ?> (<?= tfu_h($period['start_date']) ?> &ndash; <?= tfu_h($period['end_date']) ?>)
                    </span>
                </div>
            </div>

            <div class="tfu-leaderboard-grid">
                <!-- Best Sales Team Card -->
                <?php if ($topTeam && ($topTeam['metrics']['overall']['rate'] ?? 0) > 0): ?>
                    <div class="tfu-champion-card tfu-team-champion">
                        <div class="tfu-champion-badge">
                            <i class="fa-solid fa-crown" style="color:#ca8a04;"></i> Best Sales Team
                        </div>
                        <div class="tfu-champion-body">
                            <div class="tfu-champion-top-row">
                                <div class="tfu-champion-icon-wrap team-trophy-wrap">
                                    <i class="fa-solid fa-people-group"></i>
                                </div>
                                <div class="tfu-champion-info">
                                    <h4 class="tfu-champion-name" title="<?= tfu_h($topTeam['name']) ?>">
                                        <?= tfu_h($topTeam['name']) ?>
                                    </h4>
                                    <div class="tfu-champion-meta">
                                        <span><i class="fa-solid fa-user-tie me-1"></i><?= tfu_n($topTeam['exec_count']) ?> executives</span>
                                        <span>&middot;</span>
                                        <span><i class="fa-solid fa-address-book me-1"></i><?= tfu_n($topTeam['total_leads']) ?> leads</span>
                                    </div>
                                </div>
                            </div>

                            <div class="tfu-champion-score-box">
                                <div class="tfu-champion-rate-col">
                                    <div class="tfu-champion-rate-num text-success">
                                        <?= tfu_pct($topTeam['metrics']['overall']['rate']) ?>
                                    </div>
                                    <div class="tfu-champion-rate-lbl">Overall Rate</div>
                                </div>
                                <div class="tfu-champion-stats-col">
                                    <div><strong><?= tfu_n($topTeam['metrics']['overall']['ontime']) ?></strong> / <?= tfu_n($topTeam['metrics']['overall']['due']) ?> on time</div>
                                    <div class="text-danger fw-semibold" style="font-size:11px;"><?= tfu_n($topTeam['metrics']['overall']['overdue']) ?> open overdue</div>
                                </div>
                            </div>
                        </div>
                        <div class="tfu-champion-ft">
                            <?php if (!empty($filters['team_id']) && (int)$filters['team_id'] === (int)$topTeam['id']): ?>
                                <button type="button" class="btn btn-xs btn-success w-100" style="font-weight:600; font-size:11.5px; border-radius:6px;" onclick="filterByTeam('')" title="Click to show all teams">
                                    <i class="fa-solid fa-check me-1"></i> Focused (Click to Show All Teams)
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-xs btn-outline-success w-100" style="font-weight:600; font-size:11.5px; border-radius:6px;" onclick="filterByTeam(<?= $topTeam['id'] ?>)">
                                    <i class="fa-solid fa-filter me-1"></i> Focus Team
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Top 3 Executives Cards -->
                <?php 
                $podiumRanks = [
                    0 => ['rank' => '1st', 'icon' => '', 'title' => '1st Best Executive', 'class' => 'tfu-exec-gold', 'badge_bg' => '#fef08a', 'badge_color' => '#854d0e', 'border' => '#facc15'],
                    1 => ['rank' => '2nd', 'icon' => '', 'title' => '2nd Best Executive', 'class' => 'tfu-exec-silver', 'badge_bg' => '#f1f5f9', 'badge_color' => '#334155', 'border' => '#cbd5e1'],
                    2 => ['rank' => '3rd', 'icon' => '', 'title' => '3rd Best Executive', 'class' => 'tfu-exec-bronze', 'badge_bg' => '#ffedd5', 'badge_color' => '#9a3412', 'border' => '#fdba74'],
                ];
                foreach ($topExecutives as $idx => $exec): 
                    $rankInfo = $podiumRanks[$idx] ?? ['rank' => ($idx+1).'th', 'icon' => '🏅', 'title' => ($idx+1).'th Executive', 'class' => '', 'badge_bg' => '#f1f5f9', 'badge_color' => '#334155', 'border' => '#e2e8f0'];
                    $initials = '';
                    $words = preg_split('/\s+/', trim($exec['name']));
                    foreach (array_slice($words, 0, 2) as $w) {
                        $initials .= mb_strtoupper(mb_substr($w, 0, 1));
                    }
                    if (!$initials) $initials = 'SE';
                    $rate = $exec['metrics']['overall']['rate'] ?? 0;
                    $rateColor = ($rate >= 80) ? '#059669' : (($rate >= 50) ? '#d97706' : '#dc2626');
                ?>
                    <div class="tfu-champion-card tfu-exec-champion <?= $rankInfo['class'] ?>">
                        <div class="tfu-champion-badge" style="background: <?= $rankInfo['badge_bg'] ?>; color: <?= $rankInfo['badge_color'] ?>; border: 1px solid <?= $rankInfo['border'] ?>;">
                            <span><?= $rankInfo['icon'] ?> <?= $rankInfo['title'] ?></span>
                        </div>
                        <div class="tfu-champion-body">
                            <div class="tfu-champion-top-row">
                                <div class="tfu-champion-avatar-wrap">
                                    <?php if (!empty($exec['photo'])): ?>
                                        <img src="<?= tfu_h($exec['photo']) ?>" alt="<?= tfu_h($exec['name']) ?>" class="tfu-champion-avatar" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="tfu-champion-avatar-initials" style="display:none;"><?= tfu_h($initials) ?></div>
                                    <?php else: ?>
                                        <div class="tfu-champion-avatar-initials"><?= tfu_h($initials) ?></div>
                                    <?php endif; ?>
                                    <span class="tfu-avatar-rank-badge"><?= $rankInfo['icon'] ?></span>
                                </div>
                                <div class="tfu-champion-info">
                                    <h4 class="tfu-champion-name" title="<?= tfu_h($exec['name']) ?>">
                                        <?= tfu_h($exec['name']) ?>
                                    </h4>
                                    <div class="tfu-champion-meta">
                                        <span class="badge bg-light text-dark text-truncate" style="max-width: 140px; font-size:11px;" title="<?= tfu_h($exec['team_name']) ?>">
                                            <i class="fa-solid fa-users me-1 text-primary"></i><?= tfu_h($exec['team_name']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="tfu-champion-score-box">
                                <div class="tfu-champion-rate-col">
                                    <div class="tfu-champion-rate-num" style="color: <?= $rateColor ?>;">
                                        <?= tfu_pct($rate) ?>
                                    </div>
                                    <div class="tfu-champion-rate-lbl">Compliance</div>
                                </div>
                                <div class="tfu-champion-stats-col">
                                    <div><strong><?= tfu_n($exec['metrics']['overall']['ontime']) ?></strong> / <?= tfu_n($exec['metrics']['overall']['due']) ?> on time</div>
                                    <div class="text-danger fw-semibold" style="font-size:11px;"><?= tfu_n($exec['metrics']['overall']['overdue']) ?> overdue</div>
                                </div>
                            </div>
                        </div>
                        <div class="tfu-champion-ft">
                            <button type="button" class="btn btn-xs btn-outline-primary w-100" style="font-weight:600; font-size:11.5px; border-radius:6px;" onclick="openDrilldownModal('<?= tfu_h(addslashes($exec['name'])) ?> &ndash; Leads', 0, <?= $exec['id'] ?>)">
                                <i class="fa-solid fa-list-check me-1"></i> See Leads (<?= tfu_n($exec['total_leads']) ?>)
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
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
                    <option value="execs" <?= ($filters['view'] === 'teams') ? 'selected' : '' ?>>Sort: Most executives</option>
                    <option value="ontime">Sort: Most follow-ups done</option>
                    <option value="highest">Sort: Highest overall rate (%)</option>
                    <option value="lowest" <?= ($filters['view'] !== 'teams') ? 'selected' : '' ?>>Sort: Lowest overall rate (%)</option>
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
                                data-leads="<?= (int)$e['total_leads'] ?>"
                                data-ontime="<?= (int)$m['overall']['ontime'] ?>">
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
                                data-execs="<?= (int)$t['exec_count'] ?>"
                                data-ontime="<?= (int)$m['overall']['ontime'] ?>"
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
                            usort($teamExecs, fn($a, $b) => $b['total_leads'] <=> $a['total_leads']);
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

    <!-- Quick Filter Tabs & Search inside Modal -->
    <div style="padding: 12px 24px; background:#ffffff; border-bottom:1px solid #f1f5f9;" id="drillFilterToolbar">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
            <!-- Step Tabs -->
            <div class="tfu-modal-tabs" id="drillStepTabs">
                <button type="button" class="tfu-tab-pill active" onclick="setDrillFilter('step', 0)">All Steps</button>
                <button type="button" class="tfu-tab-pill" onclick="setDrillFilter('step', 1)">1st Step</button>
                <button type="button" class="tfu-tab-pill" onclick="setDrillFilter('step', 2)">2nd Step</button>
                <button type="button" class="tfu-tab-pill" onclick="setDrillFilter('step', 3)">3rd Step</button>
                <button type="button" class="tfu-tab-pill" onclick="setDrillFilter('step', 4)">4th Step</button>
                <button type="button" class="tfu-tab-pill" onclick="setDrillFilter('step', 5)">5th Step</button>
            </div>

            <!-- Status Tabs -->
            <div class="tfu-modal-tabs" id="drillStatusTabs">
                <button type="button" class="tfu-tab-pill active" onclick="setDrillFilter('status', '')">All Statuses</button>
                <button type="button" class="tfu-tab-pill" onclick="setDrillFilter('status', 'overdue')" style="color:#b45309; border-color:#fde68a;">Overdue Only</button>
                <button type="button" class="tfu-tab-pill" onclick="setDrillFilter('status', 'late')">Completed Late</button>
                <button type="button" class="tfu-tab-pill" onclick="setDrillFilter('status', 'ontime')" style="color:#059669; border-color:#a7f3d0;">On-Time</button>
            </div>

            <!-- Live Filter in modal -->
            <div style="position:relative; width:220px;">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:12px;"></i>
                <input type="text" id="drillSearchInput" class="form-control form-control-sm" 
                       style="padding-left:32px; border-radius:20px; font-size:12px;" placeholder="Search leads...">
            </div>
        </div>
    </div>

    <!-- Modal Body: Leads List OR Single Lead Journey -->
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
let currentDrillLeads = []; // Store loaded leads array

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
        if (sortMode === 'execs') {
            const exA = parseInt(a.dataset.execs || 0);
            const exB = parseInt(b.dataset.execs || 0);
            if (exA !== exB) return exB - exA;
            return parseInt(b.dataset.leads || 0) - parseInt(a.dataset.leads || 0);
        } else if (sortMode === 'ontime') {
            return parseInt(b.dataset.ontime || 0) - parseInt(a.dataset.ontime || 0);
        } else if (sortMode === 'lowest') {
            const valA = parseFloat(a.dataset.overall);
            const valB = parseFloat(b.dataset.overall);
            if (valA < 0 && valB >= 0) return 1;
            if (valB < 0 && valA >= 0) return -1;
            return valA - valB;
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

// Filter By Team helper (from Leaderboard card)
function filterByTeam(teamId) {
    if (!teamId) return;
    const teamSelect = $('#filterTeamSelect');
    if (teamSelect.length) {
        teamSelect.val(teamId).trigger('change');
    }
    const form = document.getElementById('tfuFilterForm');
    if (form) form.submit();
}

// Initialize Select2 for Team & Project Filter Dropdowns with Search
$(function() {
    function initFilterSelects() {
        if (typeof $.fn.select2 === 'function') {
            $('#filterTeamSelect').select2({
                width: '100%',
                placeholder: 'All Teams (<?= count($teams) ?>)',
                allowClear: true
            });
            $('#filterProjectSelect').select2({
                width: '100%',
                placeholder: 'All Projects (<?= count($projects) ?>)',
                allowClear: true
            });
        }
    }
    initFilterSelects();
    window.addEventListener('load', initFilterSelects);
});

// Open Lead Drilldown Modal
function openDrilldownModal(titleOrObj, teamId, execId) {
    if (typeof titleOrObj === 'object' && titleOrObj !== null) {
        teamId = titleOrObj.team_id || titleOrObj.teamId || 0;
        execId = titleOrObj.executive_id || titleOrObj.execId || 0;
        titleOrObj = titleOrObj.title || 'Leads';
    }
    const title = titleOrObj || 'Leads';
    currentDrillTeamId = teamId || 0;
    currentDrillExecId = execId || 0;
    currentDrillStep = 0;
    currentDrillStatus = '';
    currentDrillLeads = [];

    document.getElementById('drillModalTitle').innerText = 'Leads: ' + title;
    document.getElementById('drillModalSubtitle').innerText = 'Viewing follow-up items for window: <?= tfu_h($period['label']) ?>';
    
    // Show filter toolbar
    document.getElementById('drillFilterToolbar').style.display = 'block';

    // Reset active tab pills
    document.querySelectorAll('#drillStepTabs .tfu-tab-pill').forEach((btn, idx) => {
        btn.classList.toggle('active', idx === 0);
    });
    document.querySelectorAll('#drillStatusTabs .tfu-tab-pill').forEach((btn, idx) => {
        btn.classList.toggle('active', idx === 0);
    });
    document.getElementById('drillSearchInput').value = '';

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

// Search input inside modal
document.getElementById('drillSearchInput').addEventListener('input', function(e) {
    renderDrilldownTable();
});

function loadDrilldownData() {
    const body = document.getElementById('drillModalBody');
    body.innerHTML = `
        <div style="text-align:center; padding:50px 20px; color:#64748b; margin:auto;">
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
            currentDrillLeads = res.rows || [];
            renderDrilldownTable();
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

function renderDrilldownTable() {
    const body = document.getElementById('drillModalBody');
    document.getElementById('drillFilterToolbar').style.display = 'block';

    const searchQ = (document.getElementById('drillSearchInput').value || '').toLowerCase().trim();

    let filtered = currentDrillLeads.filter(r => {
        if (!searchQ) return true;
        const statuses = (r.status_updates || []).join(' ');
        const text = (r.lead_name + ' ' + r.lead_id + ' ' + r.phone + ' ' + r.project_name + ' ' + r.exec_name + ' ' + statuses).toLowerCase();
        return text.includes(searchQ);
    });

    if (!filtered || filtered.length === 0) {
        body.innerHTML = `
            <div style="text-align:center; padding:60px 20px; color:#94a3b8; margin:auto;">
                <i class="fa-regular fa-folder-open fa-3x" style="margin-bottom:12px; opacity:0.6;"></i>
                <h4 style="font-size:15px; font-weight:600; color:#475569; margin:0 0 4px 0;">No matching leads found</h4>
                <p style="font-size:12.5px; margin:0;">Try switching step tabs or status filters above.</p>
            </div>
        `;
        return;
    }

    let html = `
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; font-size:12.5px; color:#64748b; flex-shrink:0;">
            <span>Showing <strong>${filtered.length}</strong> matching leads (Click any lead to inspect follow-up history)</span>
        </div>
        <div class="tfu-drill-table-container">
            <table class="table table-hover table-sm align-middle mb-0" style="font-size:12.5px;">
                <thead>
                    <tr>
                        <th style="width:200px;">Lead</th>
                        <th>Phone</th>
                        <th>Project</th>
                        <th>Salesperson</th>
                        <th class="text-center" style="width:145px;">Follow-up & Status</th>
                        <th>1st Follow-up</th>
                        <th>Next Follow-up</th>
                        <th>Next Given?</th>
                        <th style="width:130px; text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
    `;

    filtered.forEach((r, idx) => {
        // 1st FU display
        let fu1Str = '';
        if (r.first_followup.date) {
            let f1Badge = (r.first_followup.status === 'ontime') 
                ? '<span class="badge bg-success-subtle text-success border border-success-subtle ms-1 fs-10">On-Time</span>' 
                : '<span class="badge bg-warning-subtle text-warning border border-warning-subtle ms-1 fs-10">Late</span>';
            fu1Str = `<div>${escapeHtml(r.first_followup.date)} ${f1Badge}</div><div style="font-size:11px; color:#94a3b8;">${escapeHtml(r.first_followup.type || '')}</div>`;
        } else {
            fu1Str = '<span class="text-danger small"><i class="fa-solid fa-clock me-1"></i>Not done</span>';
        }

        // Next Follow-up Date
        let nextDateStr = r.next_followup.scheduled_date ? `<strong style="color:#1e293b;">${escapeHtml(r.next_followup.scheduled_date)}</strong>` : '<span class="text-muted">—</span>';

        // Next Follow-up Given status
        let nextGivenBadge = '';
        if (r.next_followup.status === 'given_ontime') {
            nextGivenBadge = `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fa-solid fa-check me-1"></i>${escapeHtml(r.next_followup.label)}</span>`;
        } else if (r.next_followup.status === 'given_late') {
            nextGivenBadge = `<span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1"><i class="fa-solid fa-clock-rotate-left me-1"></i>${escapeHtml(r.next_followup.label)}</span>`;
        } else if (r.next_followup.status === 'overdue') {
            nextGivenBadge = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="fa-solid fa-triangle-exclamation me-1"></i>${escapeHtml(r.next_followup.label)}</span>`;
        } else if (r.next_followup.status === 'scheduled') {
            nextGivenBadge = `<span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1"><i class="fa-regular fa-clock me-1"></i>${escapeHtml(r.next_followup.label)}</span>`;
        } else {
            nextGivenBadge = `<span class="text-muted small">None scheduled</span>`;
        }

        // Follow-ups & Status updates display
        let fuDoneBadge = '';
        const totalFu = r.total_followups || 0;
        if (totalFu > 0) {
            let statusesHtml = '';
            if (r.status_updates && r.status_updates.length > 0) {
                const badges = r.status_updates.slice(0, 3).map(s => getLeadStatusBadgeHtml(s));
                if (r.status_updates.length > 3) {
                    badges.push(`<span class="badge bg-light text-muted border" style="font-size:9px; padding:2px 4px;">+${r.status_updates.length - 3}</span>`);
                }
                statusesHtml = `<div style="display:flex; flex-wrap:wrap; justify-content:center; align-items:center; gap:3px; margin-top:3px;">${badges.join('<span style="color:#cbd5e1; font-size:9px;">→</span>')}</div>`;
            } else {
                statusesHtml = `<div style="font-size:10px; color:#94a3b8; margin-top:2px;">No status tag</div>`;
            }

            fuDoneBadge = `
                <div style="display:flex; flex-direction:column; align-items:center;">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" style="font-size:11px; font-weight:700;">
                        <i class="fa-solid fa-arrows-rotate me-1"></i>${totalFu} ${totalFu === 1 ? 'Update' : 'Updates'}
                    </span>
                    ${statusesHtml}
                </div>
            `;
        } else {
            fuDoneBadge = `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1" style="font-size:11px;">0 Updates</span>`;
        }

        html += `
            <tr style="cursor:pointer;" onclick="showLeadFollowupJourney(${idx})">
                <td>
                    <div style="font-weight:700; color:#0f766e;">${escapeHtml(r.lead_name)}</div>
                    <div style="font-size:11px; color:#94a3b8;">#${r.lead_id}</div>
                </td>
                <td onclick="event.stopPropagation()">
                    <a href="tel:${escapeHtml(r.phone)}" style="text-decoration:none; color:#0f766e; font-weight:600;">
                        ${escapeHtml(r.phone)}
                    </a>
                </td>
                <td>${escapeHtml(r.project_name)}</td>
                <td>
                    <div>${escapeHtml(r.exec_name)}</div>
                    <div style="font-size:10.5px; color:#94a3b8;">${escapeHtml(r.team_name)}</div>
                </td>
                <td class="text-center">${fuDoneBadge}</td>
                <td>${fu1Str}</td>
                <td>${nextDateStr}</td>
                <td>${nextGivenBadge}</td>
                <td class="text-center" onclick="event.stopPropagation()">
                    <div style="display:flex; align-items:center; justify-content:center; gap:6px;">
                        <button type="button" class="btn btn-primary btn-sm" style="font-size:11.5px; padding:3px 10px; border-radius:6px; background:var(--tfu-primary); border-color:var(--tfu-primary);"
                                onclick="showLeadFollowupJourney(${idx})">
                            <i class="fa-solid fa-timeline me-1"></i>History
                        </button>
                        <a href="<?= $baseUrl ?>/view-lead/${r.lead_id}" target="_blank" class="text-muted ms-1" title="Open lead in CRM">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        </a>
                    </div>
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
}

// Show Lead Follow-up Journey (Complete Details)
function showLeadFollowupJourney(leadIndex) {
    const lead = currentDrillLeads[leadIndex];
    if (!lead) return;

    // Hide top filter tabs while viewing individual journey
    document.getElementById('drillFilterToolbar').style.display = 'none';

    const body = document.getElementById('drillModalBody');

    let stepsHtml = '';
    const steps = lead.steps || {};

    for (let s = 1; s <= 5; s++) {
        const st = steps[s];
        if (!st) continue;

        let statusClass = st.timing_status || 'ontime';
        let cardBadge = '';
        if (st.timing_status === 'ontime') {
            cardBadge = `<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa-solid fa-check me-1"></i>Completed On-Time</span>`;
        } else if (st.timing_status === 'late') {
            cardBadge = `<span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="fa-solid fa-clock-rotate-left me-1"></i>Completed Late</span>`;
        } else if (st.timing_status === 'overdue') {
            cardBadge = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fa-solid fa-triangle-exclamation me-1"></i>${st.overdue_days || 1}d Overdue</span>`;
        } else {
            cardBadge = `<span class="badge bg-light text-muted border">Pending</span>`;
        }

        let nextGivenText = '';
        if (st.next_given_status === 'given_ontime') {
            nextGivenText = `<div style="margin-top:6px; color:#065f46; font-size:12.5px; font-weight:600;"><i class="fa-solid fa-circle-check me-1"></i>${escapeHtml(st.next_given_label)}</div>`;
        } else if (st.next_given_status === 'given_late') {
            nextGivenText = `<div style="margin-top:6px; color:#b45309; font-size:12.5px; font-weight:600;"><i class="fa-solid fa-clock-rotate-left me-1"></i>${escapeHtml(st.next_given_label)}</div>`;
        } else if (st.next_given_status === 'overdue') {
            nextGivenText = `<div style="margin-top:6px; color:#dc2626; font-size:12.5px; font-weight:600;"><i class="fa-solid fa-triangle-exclamation me-1"></i>${escapeHtml(st.next_given_label)}</div>`;
        } else if (st.next_given_status === 'scheduled') {
            nextGivenText = `<div style="margin-top:6px; color:#0284c7; font-size:12.5px;"><i class="fa-regular fa-clock me-1"></i>${escapeHtml(st.next_given_label)}</div>`;
        }

        stepsHtml += `
            <div class="tfu-journey-card ${statusClass}">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; flex-wrap:wrap; gap:8px;">
                    <div>
                        <strong style="font-size:14.5px; color:#1e293b;">
                            Step ${s} &middot; ${s === 1 ? '1st Follow-up' : (s === 2 ? '2nd Follow-up' : (s === 3 ? '3rd Follow-up' : s + 'th Follow-up'))}
                        </strong>
                        <span style="font-size:12px; color:#64748b; margin-left:8px;">
                            ${st.done ? (st.logged_date + ' ' + (st.logged_time || '')) : 'Not logged yet'}
                        </span>
                    </div>
                    <div>${cardBadge}</div>
                </div>

                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:12px; font-size:12.5px; margin-bottom:6px;">
                    <div>
                        <span style="color:#64748b;">Follow-up Type:</span> 
                        <strong>${escapeHtml(st.type || 'N/A')}</strong>
                    </div>
                    <div>
                        <span style="color:#64748b;">Lead Status:</span> 
                        ${st.lead_status ? getLeadStatusBadgeHtml(st.lead_status) : '<strong class="text-muted">None</strong>'}
                    </div>
                    <div>
                        <span style="color:#64748b;">Scheduled Next Date:</span> 
                        <strong>${escapeHtml(st.scheduled_next_date || 'None')}</strong>
                    </div>
                </div>

                ${st.note ? `<div class="tfu-journey-note"><strong>Discussion Note:</strong> ${escapeHtml(st.note)}</div>` : ''}

                ${nextGivenText}
            </div>
        `;
    }

    if (!stepsHtml) {
        stepsHtml = `
            <div class="alert alert-warning" style="margin:20px 0;">
                <i class="fa-solid fa-triangle-exclamation me-2"></i> No follow-up steps have been logged yet for this lead.
            </div>
        `;
    }

    body.innerHTML = `
        <div class="tfu-drill-journey-container">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="renderDrilldownTable()">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to Leads List
                </button>
                <a href="<?= $baseUrl ?>/view-lead/${lead.lead_id}" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Open Full CRM Profile
                </a>
            </div>

            <!-- Lead Overview Card -->
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:16px 20px; margin-bottom:20px;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
                    <div>
                        <h4 style="margin:0 0 4px 0; font-size:18px; font-weight:700; color:#1e293b;">
                            ${escapeHtml(lead.lead_name)} <span style="font-size:13px; font-weight:500; color:#64748b;">(#${lead.lead_id})</span>
                        </h4>
                        <div style="font-size:13px; color:#475569;">
                            <a href="tel:${escapeHtml(lead.phone)}" style="color:#0f766e; text-decoration:none; font-weight:600;">
                                <i class="fa-solid fa-phone me-1"></i>${escapeHtml(lead.phone)}
                            </a>
                            &middot; Project: <strong>${escapeHtml(lead.project_name)}</strong>
                        </div>
                    </div>

                    <div style="text-align:right;">
                        <span class="badge bg-primary px-3 py-2" style="font-size:13px; font-weight:700;">
                            <i class="fa-solid fa-arrows-rotate me-1"></i> Total ${lead.total_followups} Follow-ups &middot; ${lead.status_update_count || 0} Status Updates
                        </span>
                        <div style="font-size:12px; color:#64748b; margin-top:4px;">
                            Assigned to: <strong>${escapeHtml(lead.exec_name)}</strong> (${escapeHtml(lead.team_name)})
                        </div>
                    </div>
                </div>

                <!-- Quick Summary Bar -->
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px; margin-top:14px; padding-top:14px; border-top:1px solid #e2e8f0; font-size:12.5px;">
                    <div>
                        <span style="color:#64748b;">1st Follow-up Date:</span><br>
                        <strong>${lead.first_followup.date ? (lead.first_followup.date + ' ' + (lead.first_followup.time || '')) : 'Not done yet'}</strong>
                    </div>
                    <div>
                        <span style="color:#64748b;">Next Follow-up Scheduled:</span><br>
                        <strong>${lead.next_followup.scheduled_date || 'None scheduled'}</strong>
                    </div>
                    <div>
                        <span style="color:#64748b;">Next Date Follow-up Given?:</span><br>
                        <strong>${escapeHtml(lead.next_followup.label)}</strong>
                    </div>
                    <div>
                        <span style="color:#64748b;">Status Updates History:</span><br>
                        ${(lead.status_updates && lead.status_updates.length > 0) 
                            ? lead.status_updates.map(s => getLeadStatusBadgeHtml(s)).join(' <span style="color:#cbd5e1; font-size:9px;">→</span> ') 
                            : '<span class="text-muted small">No status update</span>'}
                    </div>
                </div>
            </div>

            <!-- Steps Section -->
            <h5 style="font-size:15px; font-weight:700; color:#1e293b; margin-bottom:12px;">
                <i class="fa-solid fa-clock-rotate-left me-1" style="color:var(--tfu-primary);"></i>
                Follow-up Journey & Timeline (1st to 5th Steps)
            </h5>

            ${stepsHtml}
        </div>
    `;
}

function getLeadStatusBadgeHtml(status) {
    if (!status) return '';
    const s = status.trim();
    let badgeStyle = 'background:#f1f5f9; color:#475569; border:1px solid #cbd5e1;';
    
    if (['Prospect', 'High Prospect', 'Sold'].includes(s)) {
        badgeStyle = 'background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0;';
    } else if (s === 'Hot') {
        badgeStyle = 'background:#fef2f2; color:#b91c1c; border:1px solid #fecaca;';
    } else if (['Warm', 'Will Visit', 'Visited'].includes(s)) {
        badgeStyle = 'background:#fffbeb; color:#92400e; border:1px solid #fde68a;';
    } else if (s === 'No Response') {
        badgeStyle = 'background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe;';
    } else if (['Cold', 'Not Interested', "Don't Call Again"].includes(s)) {
        badgeStyle = 'background:#f8fafc; color:#64748b; border:1px solid #e2e8f0;';
    } else if (['Lead Transferred', 'Project Transferred'].includes(s)) {
        badgeStyle = 'background:#fdf4ff; color:#86198f; border:1px solid #f0abfc;';
    }
    return `<span class="badge" style="font-size:10px; font-weight:600; padding:2px 7px; border-radius:12px; ${badgeStyle}">${escapeHtml(s)}</span>`;
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

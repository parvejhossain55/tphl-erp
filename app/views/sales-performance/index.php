<?php
/**
 * Sales Performance – Management / Chief Executive dashboard
 * Tabs: overview | team | executive
 *
 * @var string $tab
 * @var array  $filters
 * @var array  $period
 * @var array  $data
 * @var array|null $quality
 * @var array  $cycleOptions
 * @var array  $teams
 * @var array  $salesUsers
 * @var array  $projects
 */

if (!function_exists('sp_h')) {
    function sp_h($v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }
    function sp_n($v): string
    {
        return number_format((float) $v);
    }
    function sp_tk($v): string
    {
        $v = (float) $v;
        if ($v >= 10000000) {
            return '৳' . number_format($v / 10000000, 2) . ' Cr';
        }
        if ($v >= 100000) {
            return '৳' . number_format($v / 100000, 2) . ' L';
        }
        return '৳' . number_format($v);
    }
    function sp_pct($num, $den, int $dec = 1): string
    {
        return $den ? number_format($num / $den * 100, $dec) . '%' : '0%';
    }
    function sp_hours($h): string
    {
        if ($h === null) {
            return '—';
        }
        if ($h < 1) {
            return round($h * 60) . ' min';
        }
        if ($h < 48) {
            return rtrim(rtrim(number_format($h, 1), '0'), '.') . ' h';
        }
        return round($h / 24, 1) . ' days';
    }
}

$o = $data['overall'];
$base = $GLOBALS['base_url'] . '/sales-performance';
$qsBase = array_filter([
    'cycle' => $filters['cycle'] ?: null,
    'from' => $filters['from'],
    'to' => $filters['to'],
    'project_id' => $filters['project_id'],
    'team_id' => $filters['team_id'],
    'executive_id' => $filters['executive_id'],
]);
$tabUrl = fn($t) => $base . '?' . http_build_query(array_merge($qsBase, ['tab' => $t]));
$exportUrl = $base . '?' . http_build_query(array_merge($qsBase, ['tab' => $tab, 'export' => 'csv']));
$selectedCycle = $period['cycle_key'] ?? ($period['year'] . '-' . $period['month']);

$realTeams = array_values(array_filter($data['teams'], fn($t) => $t['is_real_team']));
$otherBuckets = array_values(array_filter($data['teams'], fn($t) => !$t['is_real_team']));
$activeExecs = array_values(array_filter($data['executives'], fn($e) => $e['is_sales'] && $e['active']));
$inactiveExecs = array_values($data['inactive_executives'] ?? []);
$activeExecCount = count($activeExecs);
$inactiveExecCount = count($inactiveExecs);

// Gaps & Reconciliation Calculations
$unassignedBucket = array_values(array_filter($otherBuckets, fn($b) => $b['key'] === 'unassigned'))[0] ?? null;
$unassignedLeads = $unassignedBucket['stage_1'] ?? 0;

$teamLeadsSum = array_sum(array_column($realTeams, 'stage_1'));
$teamBookingsSum = array_sum(array_column($realTeams, 'bookings'));
$teamConfirmedSum = array_sum(array_column($realTeams, 'confirmed_sales'));
$teamCollectionSum = array_sum(array_column($realTeams, 'collection'));

$nonTeamBookings = max(0, $o['bookings'] - $teamBookingsSum);
$nonTeamConfirmed = max(0, $o['confirmed_sales'] - $teamConfirmedSum);
$nonTeamCollection = max(0.0, $o['collection'] - $teamCollectionSum);
$nonTeamLeads = max(0, $o['stage_1'] - $teamLeadsSum);

$activeExecLeadsSum = array_sum(array_column($activeExecs, 'stage_1'));
$inactiveExecLeadsSum = array_sum(array_column($inactiveExecs, 'stage_1'));
$activeExecBookingsSum = array_sum(array_column($activeExecs, 'bookings'));
$inactiveExecBookingsSum = array_sum(array_column($inactiveExecs, 'bookings'));
$activeExecConfirmedSum = array_sum(array_column($activeExecs, 'confirmed_sales'));
$inactiveExecConfirmedSum = array_sum(array_column($inactiveExecs, 'confirmed_sales'));
$activeExecCollectionSum = array_sum(array_column($activeExecs, 'collection'));

$directExecBookings = max(0, $o['bookings'] - $activeExecBookingsSum - $inactiveExecBookingsSum);
$directExecConfirmed = max(0, $o['confirmed_sales'] - $activeExecConfirmedSum - $inactiveExecConfirmedSum);
$directExecCollection = max(0.0, $o['collection'] - $activeExecCollectionSum - array_sum(array_column($inactiveExecs, 'collection')));

$scopeLabel = 'All teams';
if ($filters['executive_id']) {
    foreach ($salesUsers as $su) {
        if ($su['id'] === $filters['executive_id']) {
            $scopeLabel = $su['name'];
        }
    }
} elseif ($filters['team_id'] && isset($teams[$filters['team_id']])) {
    $scopeLabel = $teams[$filters['team_id']]['name'];
}

$jsData = [
    'overall' => $o,
    'teams' => $data['teams'],
    'executives' => $data['executives'],
    'inactiveExecutives' => $data['inactive_executives'],
    'all_executives' => $data['all_executives'] ?? array_merge($data['executives'], $data['inactive_executives']),
    'stages' => $data['stages'],
    'trend' => $data['trend'],
    'baseUrl' => $GLOBALS['base_url'],
    'tab' => $tab,
    'focusTeam' => $filters['focus_team'],
];
?>
<style>
    :root {
        --sp-green: #0b7a55;
        --sp-green-600: #096a4a;
        --sp-green-50: #eef7f2;
        --sp-green-100: #dcefe5;
        --sp-amber: #b4690e;
        --sp-amber-50: #fff6e5;
        --sp-amber-100: #fde9c4;
        --sp-red: #c2410c;
        --sp-red-50: #fff1ea;
        --sp-blue: #2463b5;
        --sp-text: #14211c;
        --sp-muted: #6b7a74;
        --sp-soft: #94a39d;
        --sp-border: #e5ebe8;
        --sp-bg: #f5f7f6;
        --sp-card: #ffffff;
        --sp-radius: 12px;
    }
    .sp, .sp-drawer {
        font-family: 'Inter', 'DM Sans', system-ui, -apple-system, sans-serif;
        color: var(--sp-text);
        padding: 20px 24px 48px;
        background: var(--sp-bg);
        min-height: 100vh;
    }
    .sp * { box-sizing: border-box; }
    .sp a { text-decoration: none; }
    .sp-crumb { font-size: 11px; letter-spacing: .08em; color: var(--sp-soft); text-transform: uppercase; font-weight: 600; }
    .sp-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; margin: 6px 0 18px; }
    .sp-head h1 { font-size: 26px; font-weight: 700; margin: 0; letter-spacing: -.02em; display: flex; align-items: center; gap: 10px; }
    .sp-head p { margin: 4px 0 0; color: var(--sp-muted); font-size: 13.5px; }
    .sp-live { font-size: 11px; font-weight: 600; color: var(--sp-green); background: var(--sp-green-50); border: 1px solid var(--sp-green-100); border-radius: 99px; padding: 3px 10px; display: inline-flex; align-items: center; gap: 6px; }
    .sp-live::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: var(--sp-green); animation: spPulse 1.8s infinite; }
    @keyframes spPulse { 0%,100% { opacity: 1 } 50% { opacity: .3 } }
    .sp-btn { display: inline-flex; align-items: center; gap: 8px; height: 38px; padding: 0 16px; border-radius: 9px; font-size: 13px; font-weight: 600; border: 1px solid var(--sp-border); background: var(--sp-card); color: var(--sp-text); cursor: pointer; transition: all .15s; white-space: nowrap; }
    .sp-btn:hover { border-color: #cfd8d4; background: #fafcfb; color: var(--sp-text); }
    .sp-btn.primary { background: var(--sp-green); border-color: var(--sp-green); color: #fff; }
    .sp-btn.primary:hover { background: var(--sp-green-600); color: #fff; }
    .sp-btn.sm { height: 30px; padding: 0 12px; font-size: 12px; }

    .sp-card { background: var(--sp-card); border: 1px solid var(--sp-border); border-radius: var(--sp-radius); padding: 18px 20px; }
    .sp-card h3 { font-size: 15.5px; font-weight: 700; margin: 0; letter-spacing: -.01em; }
    .sp-card .sp-sub { font-size: 12px; color: var(--sp-muted); margin-top: 2px; }
    .sp-card-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 14px; }

    .sp-preset-bar {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 16px;
        border-bottom: 1px solid var(--sp-border);
        background: rgba(0, 0, 0, 0.015);
        flex-wrap: wrap;
    }
    .sp-preset-label {
        font-size: 11.5px;
        font-weight: 700;
        color: var(--sp-muted);
        display: flex;
        align-items: center;
        gap: 5px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .sp-preset-pills {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        align-items: center;
    }
    .sp-preset-pill {
        border: 1px solid var(--sp-border);
        background: var(--sp-card);
        color: var(--sp-text);
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all .15s ease;
        line-height: 1.2;
    }
    .sp-preset-pill:hover {
        border-color: var(--sp-green);
        color: var(--sp-green);
        background: rgba(11, 122, 85, 0.05);
    }
    .sp-preset-pill.active {
        background: #0b7a55;
        border-color: #0b7a55;
        color: #fff;
        font-weight: 600;
        box-shadow: 0 2px 6px rgba(11, 122, 85, 0.25);
    }
    .sp-preset-pill.active i {
        color: #fff !important;
    }

    [bd-theme="bd-theme-dark"] .sp-preset-bar,
    [data-bs-theme="dark"] .sp-preset-bar,
    body.dark-mode .sp-preset-bar {
        background: rgba(255, 255, 255, 0.02);
        border-bottom-color: rgba(255, 255, 255, 0.08);
    }
    [bd-theme="bd-theme-dark"] .sp-preset-pill,
    [data-bs-theme="dark"] .sp-preset-pill,
    body.dark-mode .sp-preset-pill {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(255, 255, 255, 0.1);
        color: #e2e8f0;
    }
    [bd-theme="bd-theme-dark"] .sp-preset-pill:hover,
    [data-bs-theme="dark"] .sp-preset-pill:hover,
    body.dark-mode .sp-preset-pill:hover {
        border-color: #10b981;
        color: #10b981;
        background: rgba(16, 185, 129, 0.1);
    }
    [bd-theme="bd-theme-dark"] .sp-preset-pill.active,
    [data-bs-theme="dark"] .sp-preset-pill.active,
    body.dark-mode .sp-preset-pill.active {
        background: #10b981;
        border-color: #10b981;
        color: #042f2e;
        font-weight: 700;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }
    [bd-theme="bd-theme-dark"] .sp-preset-pill.active i,
    [data-bs-theme="dark"] .sp-preset-pill.active i,
    body.dark-mode .sp-preset-pill.active i {
        color: #042f2e !important;
    }

    .sp-filters { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; padding: 14px 16px; }
    .sp-filters select, .sp-filters input[type=date] { height: 38px; border: 1px solid var(--sp-border); border-radius: 9px; padding: 0 12px; font-size: 13px; color: var(--sp-text); background: var(--sp-card); min-width: 150px; outline: none; transition: border-color .15s, box-shadow .15s; }
    .sp-filters select:focus, .sp-filters input:focus { border-color: var(--sp-green); box-shadow: 0 0 0 3px rgba(11,122,85,.12); }
    .sp-filters .sp-range { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--sp-muted); }
    .sp-filters .sp-range input { min-width: 135px; }
    .sp-filters .sp-spacer { flex: 1; }
    .sp-period-note { font-size: 12px; color: var(--sp-muted); padding: 0 16px 12px; display: flex; gap: 14px; flex-wrap: wrap; }
    .sp-period-note b { color: var(--sp-text); font-weight: 600; }

    /* Select2 integration for Sales Performance search filters */
    .sp-filters .select2-container { font-family: inherit; font-size: 13px; }
    .sp-filters #spProject + .select2-container { min-width: 175px; max-width: 220px; flex: 1 1 175px; }
    .sp-filters #spTeam + .select2-container { min-width: 165px; max-width: 200px; flex: 1 1 165px; }
    .sp-filters #spExec + .select2-container { min-width: 220px; max-width: 280px; flex: 1 1 220px; }

    .sp-filters .select2-container--default .select2-selection--single {
        height: 38px;
        background: var(--sp-card);
        border: 1px solid var(--sp-border);
        border-radius: 9px;
        display: flex;
        align-items: center;
        padding: 0 32px 0 12px;
        position: relative;
        outline: none;
        transition: border-color .15s, box-shadow .15s;
    }
    .sp-filters .select2-container--default.select2-container--open .select2-selection--single,
    .sp-filters .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: var(--sp-green);
        box-shadow: 0 0 0 3px rgba(11,122,85,.12);
    }
    .sp-filters .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: var(--sp-text);
        font-size: 13px;
        font-weight: 500;
        padding: 0;
        line-height: normal;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sp-filters .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
        width: 26px;
        right: 4px;
        top: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .sp-filters .select2-container--default .select2-selection--single .select2-selection__arrow b {
        border-color: var(--sp-muted) transparent transparent transparent;
        border-width: 5px 4px 0 4px;
    }
    .sp-filters .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
        border-color: transparent transparent var(--sp-green) transparent;
        border-width: 0 4px 5px 4px;
    }

    /* Select2 Dropdown Popup */
    .select2-dropdown.sp-select2-dropdown {
        border: 1px solid #dcefe5 !important;
        border-radius: 10px !important;
        background: #ffffff !important;
        box-shadow: 0 14px 30px -4px rgba(16,24,20,.16), 0 4px 10px -2px rgba(16,24,20,.08) !important;
        z-index: 10000 !important;
        overflow: hidden !important;
    }
    .select2-dropdown.sp-select2-dropdown .select2-search--dropdown {
        padding: 8px !important;
        background: #f8faf9 !important;
        border-bottom: 1px solid #edf1ef !important;
    }
    .select2-dropdown.sp-select2-dropdown .select2-search__field {
        height: 34px !important;
        border: 1px solid #cfd8d4 !important;
        border-radius: 7px !important;
        padding: 0 10px !important;
        font-size: 12.5px !important;
        color: #14211c !important;
        background: #ffffff !important;
        outline: none !important;
        font-family: inherit !important;
        transition: border-color .15s, box-shadow .15s !important;
    }
    .select2-dropdown.sp-select2-dropdown .select2-search__field:focus {
        border-color: #0b7a55 !important;
        box-shadow: 0 0 0 2px rgba(11,122,85,.15) !important;
    }
    .select2-dropdown.sp-select2-dropdown .select2-results__options {
        max-height: 240px !important;
        padding: 4px !important;
    }
    .select2-dropdown.sp-select2-dropdown .select2-results__option {
        padding: 7px 10px !important;
        font-size: 12.5px !important;
        line-height: 1.4 !important;
        border-radius: 6px !important;
        margin-bottom: 2px !important;
        color: #14211c !important;
        background: transparent !important;
        transition: background .12s, color .12s !important;
    }
    .select2-dropdown.sp-select2-dropdown .select2-results__option:hover,
    .select2-dropdown.sp-select2-dropdown .select2-results__option--highlighted,
    .select2-dropdown.sp-select2-dropdown .select2-results__option--highlighted[aria-selected],
    .select2-container--default .select2-dropdown.sp-select2-dropdown .select2-results__option--highlighted.select2-results__option--selectable {
        background: #eef7f2 !important;
        background-color: #eef7f2 !important;
        color: #0b7a55 !important;
        font-weight: 600 !important;
    }
    .select2-dropdown.sp-select2-dropdown .select2-results__option[aria-selected="true"],
    .select2-container--default .select2-dropdown.sp-select2-dropdown .select2-results__option--selected {
        background: #0b7a55 !important;
        background-color: #0b7a55 !important;
        color: #ffffff !important;
        font-weight: 600 !important;
    }
    .select2-dropdown.sp-select2-dropdown .select2-results__option[aria-selected="true"]:hover,
    .select2-dropdown.sp-select2-dropdown .select2-results__option[aria-selected="true"].select2-results__option--highlighted {
        background: #096a4a !important;
        background-color: #096a4a !important;
        color: #ffffff !important;
    }
    .select2-dropdown.sp-select2-dropdown .select2-results__option[aria-disabled="true"] {
        display: none !important;
    }
    .select2-dropdown.sp-select2-dropdown .select2-results__message {
        padding: 12px !important;
        font-size: 12px !important;
        color: #6b7a74 !important;
        text-align: center !important;
    }

    /* Contact Status Breakdown (Below Funnel) */
    .sp-funnel-subgroup {
        margin: 10px 0 4px;
        padding: 10px 12px;
        background: #f8faf9;
        border: 1px solid var(--sp-border);
        border-radius: 8px;
    }
    .sp-funnel-subgroup-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 11.5px;
        font-weight: 600;
        color: var(--sp-muted);
        margin-bottom: 7px;
    }
    .sp-funnel-subgroup-title small {
        font-weight: 400;
        color: var(--sp-soft);
    }
    .sp-funnel-subgroup-pills {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .sp-funnel-subpill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 9px;
        background: #fff;
        border: 1px solid var(--sp-border);
        border-radius: 6px;
        font-size: 11.5px;
        cursor: pointer;
        transition: all .15s ease;
    }
    .sp-funnel-subpill:hover {
        border-color: var(--sp-green);
        background: var(--sp-green-50);
    }
    .sp-funnel-subpill.amber:hover {
        border-color: #f59e0b;
        background: #fffbeb;
    }
    .sp-funnel-subpill.red:hover {
        border-color: #ef4444;
        background: #fef2f2;
    }
    .sp-funnel-subpill .dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
    }
    .sp-funnel-subpill .dot.green { background: #10b981; }
    .sp-funnel-subpill .dot.amber { background: #f59e0b; }
    .sp-funnel-subpill .dot.red { background: #ef4444; }
    .sp-funnel-subpill .lbl { color: var(--sp-muted); font-weight: 500; }
    .sp-funnel-subpill .val { font-weight: 700; color: var(--sp-text); }
    .sp-funnel-subpill .pct { font-size: 10.5px; color: var(--sp-soft); }

    .sp-tabs { display: flex; gap: 28px; border-bottom: 1px solid var(--sp-border); margin: 20px 0 18px; }
    .sp-tabs a { padding: 10px 2px; font-size: 13.5px; color: var(--sp-muted); font-weight: 500; border-bottom: 2px solid transparent; margin-bottom: -1px; transition: color .15s; }
    .sp-tabs a:hover { color: var(--sp-text); }
    .sp-tabs a.active { color: var(--sp-green); border-bottom-color: var(--sp-green); font-weight: 600; }

    .sp-kpis { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 12px; margin-bottom: 20px; align-items: stretch; }
    .sp-kpi.amber .lbl i, .sp-kpi.amber .hint { color: #d97706; }
    .sp-kpi.amber .lbl { color: #d97706; }
    .sp-kpi.amber .val { color: #b45309; }
    .sp-kpi.amber:hover { border-color: #fcd34d; box-shadow: 0 10px 24px -10px rgba(217,119,6,.18); }
    .sp-funnel-row.no-response-row { background: #fffbf2; border-left: 3px solid #f59e0b; border-radius: 6px; }
    .sp-funnel-row.no-response-row:hover { background: #fef3c7; }
    .sp-funnel-row.no-response-row .no,
    .sp-funnel-row.no-response-row .nm,
    .sp-funnel-row.no-response-row .ct,
    .sp-funnel-row.no-response-row .pc { color: #b45309; }
    .sp-funnel-row.no-response-row .nm small,
    .sp-funnel-row.no-response-row .nm i { color: #d97706; }
    .sp-funnel-row.uncontacted-row { background: #fff8f8; border-left: 3px solid #ef4444; border-radius: 6px; }
    .sp-funnel-row.uncontacted-row:hover { background: #fee2e2; }
    .sp-funnel-row.uncontacted-row .no,
    .sp-funnel-row.uncontacted-row .nm,
    .sp-funnel-row.uncontacted-row .ct,
    .sp-funnel-row.uncontacted-row .pc { color: #b91c1c; }
    .sp-funnel-row.uncontacted-row .nm small { color: #ef4444; }
    .sp-col-no-response { color: #b45309; font-weight: 600; }
    .sp-col-no-response small { color: var(--sp-soft); font-weight: normal; }
    .sp-banner.sp-banner-inactive { background: #fef6ee; border-color: #fbd5b5; }
    .sp-unassigned-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: 9px; padding: 9px 13px; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .sp-unassigned-box .icon { color: #d97706; font-size: 16px; }
    .sp-unassigned-box .title { font-weight: 700; color: #92400e; font-size: 12.5px; }
    .sp-unassigned-box .desc { font-size: 11px; color: #b45309; margin-top: 1px; }
    .sp-unassigned-box .sp-count-chip { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; font-weight: 700; font-size: 12.5px; }
    .sp-kpi { background: var(--sp-card); border: 1px solid var(--sp-border); border-radius: var(--sp-radius); padding: 16px 18px; display: flex; flex-direction: column; justify-content: space-between; height: 100%; transition: transform .18s cubic-bezier(.16,1,.3,1), box-shadow .18s cubic-bezier(.16,1,.3,1), border-color .18s; }
    .sp-kpi:hover { transform: translateY(-2px); border-color: #cfded7; box-shadow: 0 10px 24px -10px rgba(20,33,28,.18); }
    .sp-kpi .lbl { font-size: 12px; font-weight: 600; color: var(--sp-text); display: flex; align-items: center; gap: 7px; }
    .sp-kpi .lbl i { color: var(--sp-green); font-size: 13px; width: 14px; text-align: center; }
    .sp-kpi .val { font-size: 26px; font-weight: 700; margin: 10px 0 2px; letter-spacing: -.02em; font-variant-numeric: tabular-nums; }
    .sp-kpi .hint { font-size: 11.5px; color: var(--sp-muted); margin-top: auto; }
    .sp-kpi.warn .lbl i, .sp-kpi.warn .hint { color: var(--sp-amber); }
    .sp-kpi.click { cursor: pointer; }
    .sp-kpi.click:hover { border-color: var(--sp-green) !important; }
    .sp-kpi.click:active { transform: translateY(0); }
    .sp-mini.click { cursor: pointer; transition: all .15s; }
    .sp-mini.click:hover { background: #eaf3ee; border: 1px solid #cce3d7; }
    [data-gap] { cursor: pointer; }
    span.click[data-gap]:hover { color: var(--sp-green) !important; text-decoration: underline; }

    .sp-grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 18px; align-items: start; }
    .sp-grid-2 { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 18px; align-items: stretch; }
    @media (max-width: 1024px) { .sp-grid-2 { grid-template-columns: 1fr; } }
    .sp-stack { display: flex; flex-direction: column; gap: 14px; }
    .sp-stack > .sp-card { display: flex; flex-direction: column; }

    .sp-toggle { display: inline-flex; background: #f0f3f2; border-radius: 8px; padding: 3px; }
    .sp-toggle button { border: 0; background: transparent; padding: 5px 14px; font-size: 12px; font-weight: 600; border-radius: 6px; color: var(--sp-muted); cursor: pointer; transition: all .15s; }
    .sp-toggle button.on { background: var(--sp-green); color: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.08); }

    .sp-funnel-row { display: grid; grid-template-columns: 26px 1fr 80px 62px; gap: 10px; align-items: center; padding: 7px 8px; border-radius: 8px; cursor: pointer; transition: background .15s; }
    .sp-funnel-row:hover { background: #f7faf8; }
    .sp-funnel-row.sel { background: var(--sp-green-50); }
    .sp-funnel-row .no { font-size: 11px; color: var(--sp-soft); font-variant-numeric: tabular-nums; }
    .sp-funnel-row .nm { font-size: 12.5px; font-weight: 500; margin-bottom: 5px; display: flex; gap: 6px; align-items: center; }
    .sp-funnel-row .nm small { color: var(--sp-soft); font-weight: 400; font-size: 11px; }
    .sp-bar { height: 6px; background: #edf1ef; border-radius: 99px; overflow: hidden; }
    .sp-bar > span { display: block; height: 100%; background: linear-gradient(90deg, var(--sp-green), #15a06f); border-radius: 99px; transition: width .6s cubic-bezier(.2,.8,.2,1); }
    .sp-bar.amber > span { background: linear-gradient(90deg, #d38a1c, #e9a641); }
    .sp-bar.blue > span { background: linear-gradient(90deg, #2463b5, #4b8be0); }
    .sp-bar.red > span, .uncontacted-row .sp-bar > span { background: linear-gradient(90deg, #ef4444, #dc2626) !important; }
    .sp-grid-team { display: grid; grid-template-columns: minmax(360px, 1fr) minmax(0, 1.65fr); gap: 18px; align-items: start; }
    @media (max-width: 1100px) { .sp-grid-team { grid-template-columns: 1fr; } }
    .sp-funnel-row.uncontacted-row { background: #fff8f8; border-left: 3px solid #ef4444; border-radius: 6px; }
    .sp-funnel-row.uncontacted-row:hover { background: #fee2e2; }
    .sp-funnel-row .ct { text-align: right; font-weight: 700; font-size: 13.5px; font-variant-numeric: tabular-nums; }
    .sp-funnel-row .pc { text-align: right; font-size: 11.5px; color: var(--sp-muted); font-variant-numeric: tabular-nums; }
    .sp-funnel-foot { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; border-top: 1px solid var(--sp-border); margin-top: 12px; padding-top: 14px; }
    .sp-funnel-foot .k { font-size: 11.5px; color: var(--sp-muted); }
    .sp-funnel-foot .v { font-size: 19px; font-weight: 700; margin-top: 2px; font-variant-numeric: tabular-nums; }
    .sp-funnel-foot .s { font-size: 11px; color: var(--sp-soft); }
    .sp-note { font-size: 11.5px; color: var(--sp-muted); margin-top: 12px; line-height: 1.5; }

    .sp-attn { display: flex; gap: 12px; align-items: flex-start; padding: 12px 4px; border-bottom: 1px solid #f0f3f2; }
    .sp-attn:last-child { border-bottom: 0; }
    .sp-attn .ic { width: 30px; height: 30px; border-radius: 8px; background: var(--sp-amber-50); color: var(--sp-amber); display: grid; place-items: center; flex-shrink: 0; font-size: 13px; }
    .sp-attn .ic.red { background: var(--sp-red-50); color: var(--sp-red); }
    .sp-attn .ic.green { background: var(--sp-green-50); color: var(--sp-green); }
    .sp-attn .t { font-size: 13px; font-weight: 600; }
    .sp-attn .d { font-size: 11.5px; color: var(--sp-muted); margin-top: 2px; }
    .sp-attn .go { margin-left: auto; color: var(--sp-green); align-self: center; font-size: 13px; }
    .sp-count-chip { min-width: 22px; height: 22px; padding: 0 7px; border-radius: 6px; background: var(--sp-amber-50); color: var(--sp-amber); font-size: 11.5px; font-weight: 700; display: inline-grid; place-items: center; }

    .sp-mini-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .sp-mini { background: #f6f8f7; border-radius: 9px; padding: 11px 13px; }
    .sp-mini .k { font-size: 11.5px; color: var(--sp-muted); }
    .sp-mini .v { font-size: 20px; font-weight: 700; margin-top: 4px; font-variant-numeric: tabular-nums; }
    .sp-mini .s { font-size: 11px; color: var(--sp-soft); }
    .sp-spark { margin-top: 12px; }
    .sp-spark svg { width: 100%; height: 54px; display: block; }
    .sp-focus { background: linear-gradient(135deg, var(--sp-green-50), #f3faf6); border: 1px solid var(--sp-green-100); border-radius: var(--sp-radius); padding: 14px 16px; }
    .sp-focus .k { font-size: 11px; font-weight: 700; color: var(--sp-green); text-transform: uppercase; letter-spacing: .05em; }
    .sp-focus .v { font-size: 13px; margin-top: 4px; line-height: 1.5; }

    .sp-section-head { display: flex; justify-content: space-between; align-items: flex-end; margin: 30px 0 12px; gap: 12px; flex-wrap: wrap; }
    .sp-section-head h2 { font-size: 17px; font-weight: 700; margin: 0; }
    .sp-section-head p { font-size: 12px; color: var(--sp-muted); margin: 2px 0 0; }
    .sp-pill { font-size: 11.5px; border: 1px solid var(--sp-border); background: #fff; padding: 6px 12px; border-radius: 8px; color: var(--sp-text); font-weight: 500; }

    .sp-banner { display: flex; align-items: center; gap: 12px; padding: 13px 16px; background: var(--sp-amber-50); border: 1px solid var(--sp-amber-100); border-radius: var(--sp-radius); margin-bottom: 16px; }
    .sp-banner .ic { width: 32px; height: 32px; border-radius: 8px; background: #fff; color: var(--sp-amber); display: grid; place-items: center; }
    .sp-banner .t { font-weight: 700; color: var(--sp-amber); font-size: 13.5px; }
    .sp-banner .d { font-size: 12px; color: var(--sp-muted); }
    .sp-banner .sp-btn { margin-left: auto; }

    .sp-cov-big { font-size: 28px; font-weight: 700; letter-spacing: -.02em; }
    .sp-cov-row { margin-top: 14px; }
    .sp-cov-row .top { display: flex; justify-content: space-between; font-size: 12.5px; font-weight: 600; margin-bottom: 6px; }
    .sp-cov-row .bot { display: flex; justify-content: space-between; font-size: 11.5px; color: var(--sp-muted); margin-top: 5px; }
    .sp-cov-row .bot .warn { color: var(--sp-amber); }
    .sp-cov-row .sp-bar { height: 8px; }

    .sp-queue { display: flex; align-items: center; gap: 12px; padding: 11px 0; border-bottom: 1px solid #f0f3f2; }
    .sp-queue:last-child { border-bottom: 0; }
    .sp-queue .ic { width: 30px; height: 30px; border-radius: 8px; background: var(--sp-amber-50); color: var(--sp-amber); display: grid; place-items: center; font-size: 12.5px; }
    .sp-queue .t { font-size: 13px; font-weight: 600; }
    .sp-queue .d { font-size: 11.5px; color: var(--sp-muted); }
    .sp-queue .n { margin-left: auto; font-weight: 700; font-size: 16px; font-variant-numeric: tabular-nums; }

    /* Data Quality Grid: 6 columns on PC, 4 columns on Laptop, Equal Height */
    .sp-dq-group { margin-top: 20px; }
    .sp-dq-group h4 { font-size: 12px; text-transform: uppercase; letter-spacing: .06em; color: var(--sp-muted); margin: 0 0 10px; font-weight: 700; }
    .sp-dq-grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 12px; align-items: stretch; }
    .sp-dq { border: 1px solid var(--sp-border); border-radius: 11px; padding: 14px 15px; background: var(--sp-card); display: flex; flex-direction: column; justify-content: space-between; height: 100%; min-height: 154px; box-shadow: 0 1px 3px rgba(16,24,20,.03); transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease; position: relative; }
    .sp-dq:hover { transform: translateY(-2px); border-color: var(--sp-green); box-shadow: 0 8px 22px -8px rgba(11,122,85,.22); }
    .sp-dq .top { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 8px; }
    .sp-dq .lbl { font-size: 13px; font-weight: 600; color: var(--sp-text); line-height: 1.35; }
    .sp-dq .cnt { font-size: 20px; font-weight: 800; font-variant-numeric: tabular-nums; color: #14211c; line-height: 1; letter-spacing: -.02em; }
    .sp-dq .cnt.zero { color: var(--sp-green); }
    .sp-dq .desc { font-size: 11.5px; color: var(--sp-muted); line-height: 1.45; flex-grow: 1; margin-bottom: 12px; }
    .sp-dq .foot { display: flex; justify-content: space-between; align-items: center; margin-top: auto; padding-top: 8px; border-top: 1px solid #f2f5f3; gap: 6px; }
    .sp-sev { font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 99px; text-transform: uppercase; letter-spacing: .05em; display: inline-block; }
    .sp-sev.high { background: #fff1f0; color: #cf1322; border: 1px solid #ffa39e; }
    .sp-sev.medium { background: #fffbe6; color: #d48806; border: 1px solid #ffe58f; }
    .sp-sev.low { background: #e6f7ff; color: #096dd9; border: 1px solid #91d5ff; }
    .sp-sev.ok { background: #f6ffed; color: #389e0d; border: 1px solid #b7eb8f; }
    .sp-dq .impact { font-size: 10.5px; color: var(--sp-soft); margin-left: 4px; }
    .sp-link { font-size: 11.5px; font-weight: 600; color: var(--sp-green); background: #f0f7f3; border: 1px solid #d4eae0; border-radius: 6px; cursor: pointer; padding: 3px 9px; transition: all .15s; white-space: nowrap; }
    .sp-link:hover { background: var(--sp-green); color: #fff; border-color: var(--sp-green); text-decoration: none; }

    .sp-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
    .sp-table th { text-align: left; font-weight: 500; color: var(--sp-muted); font-size: 11.5px; padding: 9px 10px; border-bottom: 1px solid var(--sp-border); white-space: nowrap; user-select: none; }
    .sp-table th.sortable { cursor: pointer; }
    .sp-table th.sortable:hover { color: var(--sp-text); }
    .sp-table th .arr { font-size: 9px; margin-left: 3px; opacity: .6; }
    .sp-table td { padding: 11px 10px; border-bottom: 1px solid #f0f3f2; vertical-align: middle; font-variant-numeric: tabular-nums; }
    .sp-table tr.click { cursor: pointer; transition: background .12s; }
    .sp-table tr.click:hover td { background: #f8fbf9; }
    .sp-table tr.sel td { background: var(--sp-green-50) !important; }
    .sp-table tr.muted td { color: var(--sp-muted); }
    .sp-table .num { text-align: right; }
    .sp-table .nm { font-weight: 600; color: var(--sp-text); }
    .sp-table .nm small { display: block; font-weight: 400; color: var(--sp-muted); font-size: 11px; margin-top: 1px; }
    .sp-table tfoot td { font-weight: 700; border-top: 1px solid var(--sp-border); border-bottom: 0; background: #fafcfb; }
    .sp-tag { font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 4px; margin-left: 6px; vertical-align: 1px; }
    .sp-tag.inactive { background: #f3f4f6; color: #6b7280; }
    .sp-tag.lead { background: var(--sp-green-50); color: var(--sp-green); }
    .sp-tag.warn { background: var(--sp-amber-50); color: var(--sp-amber); }
    .sp-ach { display: flex; align-items: center; gap: 10px; min-width: 150px; }
    .sp-ach .sp-bar { flex: 1; }
    .sp-ach b { font-size: 12px; min-width: 44px; text-align: right; color: var(--sp-green); }
    .sp-ach b.amber { color: var(--sp-amber); }
    .sp-scroll { overflow-x: auto; }
    .sp-table-foot { display: flex; justify-content: space-between; font-size: 11.5px; color: var(--sp-muted); padding-top: 12px; flex-wrap: wrap; gap: 8px; }
    .sp-recon { font-size: 11.5px; color: var(--sp-muted); background: #f6f8f7; border-radius: 8px; padding: 9px 12px; margin-top: 12px; }
    .sp-recon b { color: var(--sp-text); }

    .sp-toolbar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 12px; }
    .sp-toolbar input[type=search], .sp-toolbar select { height: 34px; border: 1px solid var(--sp-border); border-radius: 8px; padding: 0 12px; font-size: 12.5px; outline: none; background: #fff; }
    .sp-toolbar input[type=search] { min-width: 220px; }
    .sp-toolbar label { font-size: 12px; color: var(--sp-muted); display: inline-flex; align-items: center; gap: 6px; cursor: pointer; }

    .sp-avatar { width: 30px; height: 30px; border-radius: 50%; background: var(--sp-green-50); color: var(--sp-green); display: inline-grid; place-items: center; font-size: 11px; font-weight: 700; margin-right: 10px; flex-shrink: 0; }
    .sp-who { display: flex; align-items: center; }

    /* Executive Performance Cards */
    /* Executive Performance Cards: Equal Height & Responsive Grid */
    .sp-cards-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-top: 14px; align-items: stretch; }
    .sp-exec-card { background: var(--sp-card); border: 1px solid var(--sp-border); border-radius: var(--sp-radius); padding: 16px; transition: transform .18s cubic-bezier(.16,1,.3,1), box-shadow .18s cubic-bezier(.16,1,.3,1), border-color .18s; display: flex; flex-direction: column; justify-content: space-between; height: 100%; gap: 12px; cursor: pointer; position: relative; box-shadow: 0 1px 3px rgba(16,24,20,.03); }
    .sp-exec-card:hover { transform: translateY(-2px); border-color: #0b7a55; box-shadow: 0 10px 24px -10px rgba(11,122,85,.25); }
    .sp-exec-card-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .sp-exec-card-user { display: flex; align-items: center; gap: 11px; min-width: 0; }
    .sp-exec-card-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--sp-green-50); color: var(--sp-green); display: grid; place-items: center; font-weight: 700; font-size: 13px; flex-shrink: 0; }
    .sp-exec-card-avatar.inactive { background: var(--sp-amber-50); color: var(--sp-amber); }
    .sp-exec-card-avatar.resigned { background: var(--sp-red-50); color: var(--sp-red); }
    .sp-exec-card-name { font-weight: 700; font-size: 14px; color: var(--sp-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sp-exec-card-meta { font-size: 11.5px; color: var(--sp-muted); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sp-exec-card-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; flex-grow: 1; }
    .sp-exec-stat-box { background: #f8faf9; border-radius: 8px; padding: 9px 11px; display: flex; flex-direction: column; justify-content: space-between; }
    .sp-exec-stat-box .k { font-size: 11px; color: var(--sp-muted); font-weight: 500; display: flex; align-items: center; gap: 5px; }
    .sp-exec-stat-box .k i { color: var(--sp-soft); font-size: 11px; width: 13px; text-align: center; }
    .sp-exec-stat-box .v { font-size: 17px; font-weight: 700; margin-top: 3px; font-variant-numeric: tabular-nums; color: var(--sp-text); }
    .sp-exec-stat-box .s { font-size: 10.5px; color: var(--sp-soft); margin-top: 1px; }
    .sp-exec-stat-box.highlight { background: var(--sp-green-50); border: 1px solid var(--sp-green-100); }
    .sp-exec-stat-box.highlight .v { color: var(--sp-green); }
    .sp-exec-card-foot { display: flex; align-items: center; justify-content: space-between; padding-top: 10px; border-top: 1px solid #f0f3f2; font-size: 11.5px; color: var(--sp-muted); margin-top: auto; }

    .sp-status-badge { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 99px; font-size: 11px; font-weight: 600; text-transform: capitalize; }
    .sp-status-badge.active { background: var(--sp-green-50); color: var(--sp-green); border: 1px solid var(--sp-green-100); }
    .sp-status-badge.inactive { background: var(--sp-amber-50); color: var(--sp-amber); border: 1px solid var(--sp-amber-100); }
    .sp-status-badge.resigned { background: var(--sp-red-50); color: var(--sp-red); border: 1px solid #fecaca; }
    .sp-status-badge.legacy { background: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb; }
    .sp-status-badge.unlinked { background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; }
    .sp-exec-card-avatar.legacy, .sp-exec-card-avatar.unlinked { background: #f1f5f9; color: #64748b; }

    .sp-drawer-bg { position: fixed !important; inset: 0 !important; top: 0 !important; left: 0 !important; width: 100vw !important; height: 100vh !important; background: rgba(15,23,20,.45) !important; backdrop-filter: blur(2px); opacity: 0; pointer-events: none; transition: opacity .25s ease; z-index: 9999998 !important; display: none; }
    .sp-drawer-bg.open { opacity: 1 !important; pointer-events: auto !important; display: block !important; }
    .sp-drawer { padding: 0 !important; min-height: 0 !important; position: fixed !important; top: 0 !important; right: 0 !important; bottom: 0 !important; height: 100vh !important; width: min(580px, 96vw) !important; max-width: 96vw !important; background: var(--sp-card, #ffffff) !important; color: var(--sp-text, #14211c) !important; z-index: 9999999 !important; transform: translateX(105%) !important; transition: transform .28s cubic-bezier(.16,1,.3,1), box-shadow .28s !important; display: flex !important; flex-direction: column !important; box-shadow: -15px 0 45px rgba(0,0,0,.28) !important; border: none !important; border-left: 1px solid var(--sp-border, #e3eae6) !important; }
    .sp-drawer.open { transform: translateX(0) !important; }
    .sp-drawer .hd { padding: 18px 22px; border-bottom: 1px solid var(--sp-border); display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; background: var(--sp-card); }
    .sp-drawer .bd { padding: 18px 22px; overflow-y: auto; flex: 1; }
    .sp-x { border: 0; background: #f2f5f4; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; color: var(--sp-muted); display: inline-flex; align-items: center; justify-content: center; font-size: 15px; }
    .sp-x:hover { background: #e7ecea; color: var(--sp-text); }
    .sp-dtable { width: 100%; font-size: 12px; border-collapse: collapse; }
    .sp-dtable th { text-align: left; color: var(--sp-muted); font-weight: 600; padding: 10px 12px; border-bottom: 1px solid var(--sp-border); position: sticky; top: 0; background: var(--sp-card, #fff); z-index: 2; }
    .sp-dtable td { padding: 10px 12px; border-bottom: 1px solid var(--sp-border); vertical-align: top; }
    .sp-dtable tr:hover td { background: var(--sp-bg-soft, #f8fafc); }
    .sp-empty { text-align: center; color: var(--sp-muted); padding: 30px 10px; font-size: 13px; }
    .sp-defs { margin-top: 26px; }
    .sp-defs summary { cursor: pointer; font-size: 13px; font-weight: 600; color: var(--sp-text); list-style: none; display: flex; align-items: center; gap: 8px; }
    .sp-defs summary::-webkit-details-marker { display: none; }
    .sp-defs dl { display: grid; grid-template-columns: 200px 1fr; gap: 8px 18px; font-size: 12.5px; margin: 14px 0 0; }
    .sp-defs dt { font-weight: 600; }
    .sp-defs dd { margin: 0; color: var(--sp-muted); line-height: 1.5; }

    /* Responsive Media Queries: PC 6 columns, Laptop 4 columns */
    @media (min-width: 1440px) {
        .sp-dq-grid { grid-template-columns: repeat(6, minmax(0, 1fr)); }
        .sp-kpis { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); }
        .sp-cards-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }
    @media (max-width: 1439px) and (min-width: 1024px) {
        .sp-dq-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .sp-kpis { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .sp-cards-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }
    @media (max-width: 1023px) and (min-width: 768px) {
        .sp-dq-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .sp-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .sp-cards-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .sp-grid, .sp-grid-2 { grid-template-columns: 1fr; }
    }
    @media (max-width: 767px) {
        .sp { padding: 14px; }
        .sp-dq-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .sp-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .sp-cards-grid { grid-template-columns: 1fr; }
        .sp-grid, .sp-grid-2 { grid-template-columns: 1fr; }
        .sp-defs dl { grid-template-columns: 1fr; }
    }
    @media (max-width: 480px) {
        .sp-dq-grid { grid-template-columns: 1fr; }
        .sp-kpis { grid-template-columns: 1fr; }
    }

    /* ==========================================================================
       Dark Mode Comprehensive Theme Engine for Sales Performance
       ========================================================================== */
    [bd-theme="bd-theme-dark"] .sp,
    [bd-theme="bd-theme-dark"] .sp-drawer,
    [data-bs-theme="dark"] .sp,
    [data-bs-theme="dark"] .sp-drawer,
    body.dark-mode .sp,
    body.dark-mode .sp-drawer,
    body.dark-theme .sp,
    body.dark-theme .sp-drawer,
    html.dark .sp,
    html.dark .sp-drawer,
    html[data-theme="dark"] .sp,
    html[data-theme="dark"] .sp-drawer,
    body[data-theme="dark"] .sp,
    body[data-theme="dark"] .sp-drawer {
        --sp-bg: #0b1319;
        --sp-card: #131f27;
        --sp-text: #e6edf3;
        --sp-muted: #94a3b8;
        --sp-soft: #64748b;
        --sp-border: #20313d;
        --sp-green: #10b981;
        --sp-green-600: #059669;
        --sp-green-50: rgba(16, 185, 129, 0.15);
        --sp-green-100: rgba(16, 185, 129, 0.28);
        --sp-amber: #f59e0b;
        --sp-amber-50: rgba(245, 158, 11, 0.15);
        --sp-amber-100: rgba(245, 158, 11, 0.28);
        --sp-red: #ef4444;
        --sp-red-50: rgba(239, 68, 68, 0.15);
        --sp-blue: #3b82f6;
        background: #0b1319 !important;
        color: #e6edf3 !important;
    }

    [bd-theme="bd-theme-dark"] .sp-head h1,
    [data-bs-theme="dark"] .sp-head h1,
    body.dark-mode .sp-head h1 {
        color: #f1f5f9;
    }

    [bd-theme="bd-theme-dark"] .sp-card,
    [data-bs-theme="dark"] .sp-card,
    body.dark-mode .sp-card,
    [bd-theme="bd-theme-dark"] .sp-kpi,
    [data-bs-theme="dark"] .sp-kpi,
    body.dark-mode .sp-kpi {
        background: #131f27 !important;
        border-color: #20313d !important;
        color: #e6edf3 !important;
    }

    [bd-theme="bd-theme-dark"] .sp-kpi:hover,
    [data-bs-theme="dark"] .sp-kpi:hover,
    body.dark-mode .sp-kpi:hover {
        border-color: #2e4759 !important;
        box-shadow: 0 10px 24px -10px rgba(0,0,0,0.6) !important;
    }

    [bd-theme="bd-theme-dark"] .sp-kpi .val,
    [data-bs-theme="dark"] .sp-kpi .val,
    body.dark-mode .sp-kpi .val {
        color: #f8fafc !important;
    }

    [bd-theme="bd-theme-dark"] .sp-kpi.amber,
    [data-bs-theme="dark"] .sp-kpi.amber,
    body.dark-mode .sp-kpi.amber {
        background: rgba(245, 158, 11, 0.08) !important;
        border-color: rgba(245, 158, 11, 0.35) !important;
    }
    [bd-theme="bd-theme-dark"] .sp-kpi.amber .lbl,
    [bd-theme="bd-theme-dark"] .sp-kpi.amber .lbl i,
    [bd-theme="bd-theme-dark"] .sp-kpi.amber .val,
    [bd-theme="bd-theme-dark"] .sp-kpi.amber .hint,
    [data-bs-theme="dark"] .sp-kpi.amber .lbl,
    [data-bs-theme="dark"] .sp-kpi.amber .lbl i,
    [data-bs-theme="dark"] .sp-kpi.amber .val,
    [data-bs-theme="dark"] .sp-kpi.amber .hint,
    body.dark-mode .sp-kpi.amber .lbl,
    body.dark-mode .sp-kpi.amber .lbl i,
    body.dark-mode .sp-kpi.amber .val,
    body.dark-mode .sp-kpi.amber .hint {
        color: #fbbf24 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-kpi.warn,
    [data-bs-theme="dark"] .sp-kpi.warn,
    body.dark-mode .sp-kpi.warn {
        background: rgba(239, 68, 68, 0.08) !important;
        border-color: rgba(239, 68, 68, 0.35) !important;
    }
    [bd-theme="bd-theme-dark"] .sp-kpi.warn .lbl i,
    [bd-theme="bd-theme-dark"] .sp-kpi.warn .hint,
    [data-bs-theme="dark"] .sp-kpi.warn .lbl i,
    [data-bs-theme="dark"] .sp-kpi.warn .hint,
    body.dark-mode .sp-kpi.warn .lbl i,
    body.dark-mode .sp-kpi.warn .hint {
        color: #f87171 !important;
    }

    [bd-theme="bd-theme-dark"] .sp-btn,
    [data-bs-theme="dark"] .sp-btn,
    body.dark-mode .sp-btn {
        background: #17242d !important;
        border-color: #243846 !important;
        color: #e6edf3 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-btn:hover,
    [data-bs-theme="dark"] .sp-btn:hover,
    body.dark-mode .sp-btn:hover {
        background: #1e313d !important;
        border-color: #334e61 !important;
        color: #fff !important;
    }
    [bd-theme="bd-theme-dark"] .sp-btn.primary,
    [data-bs-theme="dark"] .sp-btn.primary,
    body.dark-mode .sp-btn.primary {
        background: #0b7a55 !important;
        border-color: #0b7a55 !important;
        color: #fff !important;
    }

    [bd-theme="bd-theme-dark"] .sp-filters select,
    [bd-theme="bd-theme-dark"] .sp-filters input,
    [bd-theme="bd-theme-dark"] .sp-toolbar select,
    [bd-theme="bd-theme-dark"] .sp-toolbar input,
    [data-bs-theme="dark"] .sp-filters select,
    [data-bs-theme="dark"] .sp-filters input,
    [data-bs-theme="dark"] .sp-toolbar select,
    [data-bs-theme="dark"] .sp-toolbar input,
    body.dark-mode .sp-filters select,
    body.dark-mode .sp-filters input {
        background: #17242d !important;
        border-color: #243846 !important;
        color: #e6edf3 !important;
    }

    [bd-theme="bd-theme-dark"] .sp-filters .select2-container--default .select2-selection--single,
    [data-bs-theme="dark"] .sp-filters .select2-container--default .select2-selection--single,
    body.dark-mode .sp-filters .select2-container--default .select2-selection--single {
        background: #17242d !important;
        border-color: #243846 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-filters .select2-container--default .select2-selection--single .select2-selection__rendered,
    [data-bs-theme="dark"] .sp-filters .select2-container--default .select2-selection--single .select2-selection__rendered,
    body.dark-mode .sp-filters .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #e6edf3 !important;
    }

    [bd-theme="bd-theme-dark"] .select2-dropdown.sp-select2-dropdown,
    [data-bs-theme="dark"] .select2-dropdown.sp-select2-dropdown,
    body.dark-mode .select2-dropdown.sp-select2-dropdown {
        background: #131f27 !important;
        border-color: #243846 !important;
        box-shadow: 0 14px 30px rgba(0,0,0,0.6) !important;
    }
    [bd-theme="bd-theme-dark"] .select2-dropdown.sp-select2-dropdown .select2-search__field,
    [data-bs-theme="dark"] .select2-dropdown.sp-select2-dropdown .select2-search__field,
    body.dark-mode .select2-dropdown.sp-select2-dropdown .select2-search__field {
        background: #17242d !important;
        border-color: #243846 !important;
        color: #e6edf3 !important;
    }
    [bd-theme="bd-theme-dark"] .select2-dropdown.sp-select2-dropdown .select2-results__option,
    [data-bs-theme="dark"] .select2-dropdown.sp-select2-dropdown .select2-results__option,
    body.dark-mode .select2-dropdown.sp-select2-dropdown .select2-results__option {
        color: #cbd5e1 !important;
    }
    [bd-theme="bd-theme-dark"] .select2-dropdown.sp-select2-dropdown .select2-results__option:hover,
    [data-bs-theme="dark"] .select2-dropdown.sp-select2-dropdown .select2-results__option:hover,
    body.dark-mode .select2-dropdown.sp-select2-dropdown .select2-results__option:hover,
    [bd-theme="bd-theme-dark"] .select2-dropdown.sp-select2-dropdown .select2-results__option--highlighted,
    [data-bs-theme="dark"] .select2-dropdown.sp-select2-dropdown .select2-results__option--highlighted,
    body.dark-mode .select2-dropdown.sp-select2-dropdown .select2-results__option--highlighted,
    [bd-theme="bd-theme-dark"] .select2-dropdown.sp-select2-dropdown .select2-results__option--highlighted[aria-selected],
    [data-bs-theme="dark"] .select2-dropdown.sp-select2-dropdown .select2-results__option--highlighted[aria-selected],
    body.dark-mode .select2-dropdown.sp-select2-dropdown .select2-results__option--highlighted[aria-selected] {
        background: rgba(16, 185, 129, 0.22) !important;
        background-color: rgba(16, 185, 129, 0.22) !important;
        color: #34d399 !important;
    }
    [bd-theme="bd-theme-dark"] .select2-dropdown.sp-select2-dropdown .select2-results__option[aria-selected="true"],
    [data-bs-theme="dark"] .select2-dropdown.sp-select2-dropdown .select2-results__option[aria-selected="true"],
    body.dark-mode .select2-dropdown.sp-select2-dropdown .select2-results__option[aria-selected="true"] {
        background: #0b7a55 !important;
        background-color: #0b7a55 !important;
        color: #ffffff !important;
    }

    /* Subgroup in Dark Mode */
    [bd-theme="bd-theme-dark"] .sp-funnel-subgroup,
    [data-bs-theme="dark"] .sp-funnel-subgroup,
    body.dark-mode .sp-funnel-subgroup {
        background: #17242d !important;
        border-color: #243846 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-funnel-subpill,
    [data-bs-theme="dark"] .sp-funnel-subpill,
    body.dark-mode .sp-funnel-subpill {
        background: #131f27 !important;
        border-color: #243846 !important;
        color: #cbd5e1 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-funnel-subpill .val,
    [data-bs-theme="dark"] .sp-funnel-subpill .val,
    body.dark-mode .sp-funnel-subpill .val {
        color: #f1f5f9 !important;
    }

    /* Tabs in Dark Mode */
    [bd-theme="bd-theme-dark"] .sp-tabs,
    [data-bs-theme="dark"] .sp-tabs,
    body.dark-mode .sp-tabs {
        border-bottom-color: #20313d;
    }
    [bd-theme="bd-theme-dark"] .sp-tabs a,
    [data-bs-theme="dark"] .sp-tabs a,
    body.dark-mode .sp-tabs a {
        color: #94a3b8;
    }
    [bd-theme="bd-theme-dark"] .sp-tabs a:hover,
    [data-bs-theme="dark"] .sp-tabs a:hover,
    body.dark-mode .sp-tabs a:hover {
        color: #f1f5f9;
    }
    [bd-theme="bd-theme-dark"] .sp-tabs a.active,
    [data-bs-theme="dark"] .sp-tabs a.active,
    body.dark-mode .sp-tabs a.active {
        color: #10b981;
        border-bottom-color: #10b981;
    }

    /* Funnel in Dark Mode */
    [bd-theme="bd-theme-dark"] .sp-funnel-row:hover,
    [data-bs-theme="dark"] .sp-funnel-row:hover,
    body.dark-mode .sp-funnel-row:hover {
        background: #182833;
    }
    [bd-theme="bd-theme-dark"] .sp-funnel-row.sel,
    [data-bs-theme="dark"] .sp-funnel-row.sel,
    body.dark-mode .sp-funnel-row.sel {
        background: rgba(16, 185, 129, 0.18);
    }
    [bd-theme="bd-theme-dark"] .sp-bar,
    [data-bs-theme="dark"] .sp-bar,
    body.dark-mode .sp-bar {
        background: #1e313d !important;
    }
    [bd-theme="bd-theme-dark"] .sp-funnel-row.no-response-row,
    [data-bs-theme="dark"] .sp-funnel-row.no-response-row,
    body.dark-mode .sp-funnel-row.no-response-row {
        background: rgba(245, 158, 11, 0.12) !important;
        border-left: 3px solid #f59e0b !important;
    }
    [bd-theme="bd-theme-dark"] .sp-funnel-row.no-response-row:hover,
    [data-bs-theme="dark"] .sp-funnel-row.no-response-row:hover,
    body.dark-mode .sp-funnel-row.no-response-row:hover {
        background: rgba(245, 158, 11, 0.22) !important;
    }
    [bd-theme="bd-theme-dark"] .sp-funnel-row.no-response-row .no,
    [bd-theme="bd-theme-dark"] .sp-funnel-row.no-response-row .nm,
    [bd-theme="bd-theme-dark"] .sp-funnel-row.no-response-row .ct,
    [bd-theme="bd-theme-dark"] .sp-funnel-row.no-response-row .pc,
    [data-bs-theme="dark"] .sp-funnel-row.no-response-row .no,
    [data-bs-theme="dark"] .sp-funnel-row.no-response-row .nm,
    [data-bs-theme="dark"] .sp-funnel-row.no-response-row .ct,
    [data-bs-theme="dark"] .sp-funnel-row.no-response-row .pc,
    body.dark-mode .sp-funnel-row.no-response-row .no,
    body.dark-mode .sp-funnel-row.no-response-row .nm,
    body.dark-mode .sp-funnel-row.no-response-row .ct,
    body.dark-mode .sp-funnel-row.no-response-row .pc {
        color: #fbbf24 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-funnel-row.no-response-row .nm small,
    [bd-theme="bd-theme-dark"] .sp-funnel-row.no-response-row .nm i,
    [data-bs-theme="dark"] .sp-funnel-row.no-response-row .nm small,
    [data-bs-theme="dark"] .sp-funnel-row.no-response-row .nm i,
    body.dark-mode .sp-funnel-row.no-response-row .nm small,
    body.dark-mode .sp-funnel-row.no-response-row .nm i {
        color: #fde68a !important;
    }
    [bd-theme="bd-theme-dark"] .sp-funnel-row.uncontacted-row,
    [data-bs-theme="dark"] .sp-funnel-row.uncontacted-row,
    body.dark-mode .sp-funnel-row.uncontacted-row {
        background: rgba(239, 68, 68, 0.12) !important;
        border-left: 3px solid #ef4444 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-funnel-row.uncontacted-row:hover,
    [data-bs-theme="dark"] .sp-funnel-row.uncontacted-row:hover,
    body.dark-mode .sp-funnel-row.uncontacted-row:hover {
        background: rgba(239, 68, 68, 0.22) !important;
    }
    [bd-theme="bd-theme-dark"] .sp-funnel-row.uncontacted-row .no,
    [bd-theme="bd-theme-dark"] .sp-funnel-row.uncontacted-row .nm,
    [bd-theme="bd-theme-dark"] .sp-funnel-row.uncontacted-row .ct,
    [bd-theme="bd-theme-dark"] .sp-funnel-row.uncontacted-row .pc,
    [data-bs-theme="dark"] .sp-funnel-row.uncontacted-row .no,
    [data-bs-theme="dark"] .sp-funnel-row.uncontacted-row .nm,
    [data-bs-theme="dark"] .sp-funnel-row.uncontacted-row .ct,
    [data-bs-theme="dark"] .sp-funnel-row.uncontacted-row .pc,
    body.dark-mode .sp-funnel-row.uncontacted-row .no,
    body.dark-mode .sp-funnel-row.uncontacted-row .nm,
    body.dark-mode .sp-funnel-row.uncontacted-row .ct,
    body.dark-mode .sp-funnel-row.uncontacted-row .pc {
        color: #f87171 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-funnel-row.uncontacted-row .nm small,
    [data-bs-theme="dark"] .sp-funnel-row.uncontacted-row .nm small,
    body.dark-mode .sp-funnel-row.uncontacted-row .nm small {
        color: #fca5a5 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-funnel-foot,
    [data-bs-theme="dark"] .sp-funnel-foot,
    body.dark-mode .sp-funnel-foot {
        border-top-color: #20313d;
    }

    /* Tables in Dark Mode */
    [bd-theme="bd-theme-dark"] .sp-table th,
    [data-bs-theme="dark"] .sp-table th,
    body.dark-mode .sp-table th {
        color: #94a3b8;
        border-bottom-color: #20313d;
    }
    [bd-theme="bd-theme-dark"] .sp-table td,
    [data-bs-theme="dark"] .sp-table td,
    body.dark-mode .sp-table td {
        border-bottom-color: #1a2933;
        color: #cbd5e1;
    }
    [bd-theme="bd-theme-dark"] .sp-table .nm,
    [data-bs-theme="dark"] .sp-table .nm,
    body.dark-mode .sp-table .nm {
        color: #f1f5f9;
    }
    [bd-theme="bd-theme-dark"] .sp-table tr.click:hover td,
    [data-bs-theme="dark"] .sp-table tr.click:hover td,
    body.dark-mode .sp-table tr.click:hover td {
        background: #182833 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-table tr.sel td,
    [data-bs-theme="dark"] .sp-table tr.sel td,
    body.dark-mode .sp-table tr.sel td {
        background: rgba(16, 185, 129, 0.18) !important;
    }
    [bd-theme="bd-theme-dark"] .sp-table tfoot td,
    [data-bs-theme="dark"] .sp-table tfoot td,
    body.dark-mode .sp-table tfoot td {
        background: #111b22;
        border-top-color: #20313d;
        color: #f1f5f9;
    }

    /* Executive Cards in Dark Mode */
    [bd-theme="bd-theme-dark"] .sp-exec-card,
    [data-bs-theme="dark"] .sp-exec-card,
    body.dark-mode .sp-exec-card {
        background: #131f27 !important;
        border-color: #20313d !important;
    }
    [bd-theme="bd-theme-dark"] .sp-exec-card:hover,
    [data-bs-theme="dark"] .sp-exec-card:hover,
    body.dark-mode .sp-exec-card:hover {
        border-color: #10b981 !important;
        box-shadow: 0 10px 24px -10px rgba(0,0,0,0.6) !important;
    }
    [bd-theme="bd-theme-dark"] .sp-exec-stat-box,
    [data-bs-theme="dark"] .sp-exec-stat-box,
    body.dark-mode .sp-exec-stat-box {
        background: #17242d !important;
    }
    [bd-theme="bd-theme-dark"] .sp-exec-stat-box .v,
    [data-bs-theme="dark"] .sp-exec-stat-box .v,
    body.dark-mode .sp-exec-stat-box .v {
        color: #f1f5f9 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-exec-card-foot,
    [data-bs-theme="dark"] .sp-exec-card-foot,
    body.dark-mode .sp-exec-card-foot {
        border-top-color: #20313d !important;
    }

    /* Mini Cards & Focus in Dark Mode */
    [bd-theme="bd-theme-dark"] .sp-mini,
    [data-bs-theme="dark"] .sp-mini,
    body.dark-mode .sp-mini {
        background: #17242d !important;
    }
    [bd-theme="bd-theme-dark"] .sp-mini .v,
    [data-bs-theme="dark"] .sp-mini .v,
    body.dark-mode .sp-mini .v {
        color: #f1f5f9 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-focus,
    [data-bs-theme="dark"] .sp-focus,
    body.dark-mode .sp-focus {
        background: rgba(16, 185, 129, 0.12) !important;
        border-color: rgba(16, 185, 129, 0.28) !important;
        color: #e6edf3 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-toggle,
    [data-bs-theme="dark"] .sp-toggle,
    body.dark-mode .sp-toggle {
        background: #17242d !important;
    }
    [bd-theme="bd-theme-dark"] .sp-toggle button,
    [data-bs-theme="dark"] .sp-toggle button,
    body.dark-mode .sp-toggle button {
        color: #94a3b8 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-toggle button.on,
    [data-bs-theme="dark"] .sp-toggle button.on,
    body.dark-mode .sp-toggle button.on {
        background: #10b981 !important;
        color: #fff !important;
    }

    /* Drawer in Dark Mode */
    [bd-theme="bd-theme-dark"] .sp-drawer,
    [data-bs-theme="dark"] .sp-drawer,
    body.dark-mode .sp-drawer {
        background: #131f27 !important;
        color: #e6edf3 !important;
        border-left: 1px solid #20313d !important;
        box-shadow: -20px 0 40px -20px rgba(0,0,0,0.6) !important;
    }
    [bd-theme="bd-theme-dark"] .sp-drawer .hd,
    [data-bs-theme="dark"] .sp-drawer .hd,
    body.dark-mode .sp-drawer .hd {
        border-bottom-color: #20313d !important;
    }
    [bd-theme="bd-theme-dark"] .sp-x,
    [data-bs-theme="dark"] .sp-x,
    body.dark-mode .sp-x {
        background: #1e313d !important;
        color: #94a3b8 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-x:hover,
    [data-bs-theme="dark"] .sp-x:hover,
    body.dark-mode .sp-x:hover {
        background: #253d4c !important;
        color: #f1f5f9 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-dtable td,
    [data-bs-theme="dark"] .sp-dtable td,
    body.dark-mode .sp-dtable td {
        border-bottom-color: #1e313d !important;
        color: #cbd5e1 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-dtable th,
    [data-bs-theme="dark"] .sp-dtable th,
    body.dark-mode .sp-dtable th {
        border-bottom-color: #20313d !important;
        color: #94a3b8 !important;
    }
    [bd-theme="bd-theme-dark"] #spDrawerSearchWrap,
    [data-bs-theme="dark"] #spDrawerSearchWrap,
    body.dark-mode #spDrawerSearchWrap {
        background: #0f1920 !important;
        border-bottom-color: #20313d !important;
    }
    [bd-theme="bd-theme-dark"] #spDrawerFilter,
    [data-bs-theme="dark"] #spDrawerFilter,
    body.dark-mode #spDrawerFilter {
        background: #17242d !important;
        border-color: #20313d !important;
        color: #f1f5f9 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-mini.click:hover,
    [data-bs-theme="dark"] .sp-mini.click:hover,
    body.dark-mode .sp-mini.click:hover {
        background: #1f333f !important;
        border-color: #10b981 !important;
    }

    /* Data Quality cards in Dark Mode */
    [bd-theme="bd-theme-dark"] .sp-dq,
    [data-bs-theme="dark"] .sp-dq,
    body.dark-mode .sp-dq {
        background: #131f27 !important;
        border-color: #20313d !important;
    }
    [bd-theme="bd-theme-dark"] .sp-dq .cnt,
    [data-bs-theme="dark"] .sp-dq .cnt,
    body.dark-mode .sp-dq .cnt {
        color: #f1f5f9 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-dq .foot,
    [data-bs-theme="dark"] .sp-dq .foot,
    body.dark-mode .sp-dq .foot {
        border-top-color: #1e313d !important;
    }
    [bd-theme="bd-theme-dark"] .sp-link,
    [data-bs-theme="dark"] .sp-link,
    body.dark-mode .sp-link {
        background: rgba(16, 185, 129, 0.16) !important;
        border-color: rgba(16, 185, 129, 0.3) !important;
        color: #34d399 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-link:hover,
    [data-bs-theme="dark"] .sp-link:hover,
    body.dark-mode .sp-link:hover {
        background: #10b981 !important;
        color: #fff !important;
    }

    /* Needs Attention & Activity in Dark Mode */
    [bd-theme="bd-theme-dark"] .sp-attn,
    [data-bs-theme="dark"] .sp-attn,
    body.dark-mode .sp-attn,
    [bd-theme="bd-theme-dark"] .sp-queue,
    [data-bs-theme="dark"] .sp-queue,
    body.dark-mode .sp-queue {
        border-bottom-color: #1e313d !important;
    }
    [bd-theme="bd-theme-dark"] .sp-attn:hover,
    [data-bs-theme="dark"] .sp-attn:hover,
    body.dark-mode .sp-attn:hover {
        background: #182833 !important;
    }

    /* Banners in Dark Mode */
    [bd-theme="bd-theme-dark"] .sp-banner,
    [data-bs-theme="dark"] .sp-banner,
    body.dark-mode .sp-banner {
        background: #1c1815 !important;
        border-color: rgba(245, 158, 11, 0.3) !important;
    }
    [bd-theme="bd-theme-dark"] .sp-banner .ic,
    [data-bs-theme="dark"] .sp-banner .ic,
    body.dark-mode .sp-banner .ic {
        background: #25201b !important;
        color: #fbbf24 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-banner .t,
    [data-bs-theme="dark"] .sp-banner .t,
    body.dark-mode .sp-banner .t {
        color: #fbbf24 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-banner .d,
    [data-bs-theme="dark"] .sp-banner .d,
    body.dark-mode .sp-banner .d {
        color: #cbd5e1 !important;
    }

    /* Pill in Dark Mode */
    [bd-theme="bd-theme-dark"] .sp-pill,
    [data-bs-theme="dark"] .sp-pill,
    body.dark-mode .sp-pill {
        background: #17242d !important;
        border-color: #243846 !important;
        color: #cbd5e1 !important;
    }

    /* Definitions (How these numbers are calculated) in Dark Mode */
    [bd-theme="bd-theme-dark"] .sp-defs summary,
    [data-bs-theme="dark"] .sp-defs summary,
    body.dark-mode .sp-defs summary {
        color: #f1f5f9 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-defs dt,
    [data-bs-theme="dark"] .sp-defs dt,
    body.dark-mode .sp-defs dt {
        color: #f1f5f9 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-defs dd,
    [data-bs-theme="dark"] .sp-defs dd,
    body.dark-mode .sp-defs dd {
        color: #94a3b8 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-defs dd code,
    [data-bs-theme="dark"] .sp-defs dd code,
    body.dark-mode .sp-defs dd code {
        background: #1e313d !important;
        color: #34d399 !important;
    }

    /* Status Badges in Dark Mode */
    [bd-theme="bd-theme-dark"] .sp-status-badge.active,
    [data-bs-theme="dark"] .sp-status-badge.active,
    body.dark-mode .sp-status-badge.active {
        background: rgba(16, 185, 129, 0.18) !important;
        color: #34d399 !important;
        border-color: rgba(16, 185, 129, 0.35) !important;
    }
    [bd-theme="bd-theme-dark"] .sp-status-badge.inactive,
    [data-bs-theme="dark"] .sp-status-badge.inactive,
    body.dark-mode .sp-status-badge.inactive {
        background: rgba(245, 158, 11, 0.18) !important;
        color: #fbbf24 !important;
        border-color: rgba(245, 158, 11, 0.35) !important;
    }
    [bd-theme="bd-theme-dark"] .sp-status-badge.resigned,
    [data-bs-theme="dark"] .sp-status-badge.resigned,
    body.dark-mode .sp-status-badge.resigned {
        background: rgba(239, 68, 68, 0.18) !important;
        color: #f87171 !important;
        border-color: rgba(239, 68, 68, 0.35) !important;
    }
    [bd-theme="bd-theme-dark"] .sp-status-badge.legacy,
    [bd-theme="bd-theme-dark"] .sp-status-badge.unlinked,
    [data-bs-theme="dark"] .sp-status-badge.legacy,
    [data-bs-theme="dark"] .sp-status-badge.unlinked,
    body.dark-mode .sp-status-badge.legacy,
    body.dark-mode .sp-status-badge.unlinked {
        background: #1e313d !important;
        color: #94a3b8 !important;
        border-color: #243846 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-exec-card-avatar.legacy,
    [bd-theme="bd-theme-dark"] .sp-exec-card-avatar.unlinked,
    [data-bs-theme="dark"] .sp-exec-card-avatar.legacy,
    [data-bs-theme="dark"] .sp-exec-card-avatar.unlinked,
    body.dark-mode .sp-exec-card-avatar.legacy,
    body.dark-mode .sp-exec-card-avatar.unlinked {
        background: #1e313d !important;
        color: #94a3b8 !important;
    }

    /* Severity chips in Data Quality */
    [bd-theme="bd-theme-dark"] .sp-sev.high,
    [data-bs-theme="dark"] .sp-sev.high,
    body.dark-mode .sp-sev.high {
        background: rgba(239, 68, 68, 0.2) !important;
        color: #f87171 !important;
        border-color: rgba(239, 68, 68, 0.4) !important;
    }
    [bd-theme="bd-theme-dark"] .sp-sev.medium,
    [data-bs-theme="dark"] .sp-sev.medium,
    body.dark-mode .sp-sev.medium {
        background: rgba(245, 158, 11, 0.2) !important;
        color: #fbbf24 !important;
        border-color: rgba(245, 158, 11, 0.4) !important;
    }
    [bd-theme="bd-theme-dark"] .sp-sev.low,
    [data-bs-theme="dark"] .sp-sev.low,
    body.dark-mode .sp-sev.low {
        background: rgba(59, 130, 246, 0.2) !important;
        color: #60a5fa !important;
        border-color: rgba(59, 130, 246, 0.4) !important;
    }
    [bd-theme="bd-theme-dark"] .sp-sev.ok,
    [data-bs-theme="dark"] .sp-sev.ok,
    body.dark-mode .sp-sev.ok {
        background: rgba(16, 185, 129, 0.2) !important;
        color: #34d399 !important;
        border-color: rgba(16, 185, 129, 0.4) !important;
    }

    /* Count chips & Unassigned box */
    [bd-theme="bd-theme-dark"] .sp-count-chip,
    [data-bs-theme="dark"] .sp-count-chip,
    body.dark-mode .sp-count-chip {
        background: rgba(245, 158, 11, 0.2) !important;
        color: #fbbf24 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-unassigned-box,
    [data-bs-theme="dark"] .sp-unassigned-box,
    body.dark-mode .sp-unassigned-box {
        background: rgba(245, 158, 11, 0.12) !important;
        border-color: rgba(245, 158, 11, 0.3) !important;
    }
    [bd-theme="bd-theme-dark"] .sp-unassigned-box .title,
    [data-bs-theme="dark"] .sp-unassigned-box .title,
    body.dark-mode .sp-unassigned-box .title {
        color: #fbbf24 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-unassigned-box .desc,
    [data-bs-theme="dark"] .sp-unassigned-box .desc,
    body.dark-mode .sp-unassigned-box .desc {
        color: #cbd5e1 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-unassigned-box .sp-count-chip,
    [data-bs-theme="dark"] .sp-unassigned-box .sp-count-chip,
    body.dark-mode .sp-unassigned-box .sp-count-chip {
        background: rgba(245, 158, 11, 0.22) !important;
        color: #fbbf24 !important;
        border-color: rgba(245, 158, 11, 0.4) !important;
    }

    /* No Response column */
    [bd-theme="bd-theme-dark"] .sp-col-no-response,
    [data-bs-theme="dark"] .sp-col-no-response,
    body.dark-mode .sp-col-no-response {
        color: #fbbf24 !important;
    }
    [bd-theme="bd-theme-dark"] .sp-col-no-response small,
    [data-bs-theme="dark"] .sp-col-no-response small,
    body.dark-mode .sp-col-no-response small {
        color: #94a3b8 !important;
    }

    /* Select2 refinements in Dark Mode */
    [bd-theme="bd-theme-dark"] .select2-dropdown.sp-select2-dropdown .select2-search--dropdown,
    [data-bs-theme="dark"] .select2-dropdown.sp-select2-dropdown .select2-search--dropdown,
    body.dark-mode .select2-dropdown.sp-select2-dropdown .select2-search--dropdown {
        background: #131f27 !important;
        border-bottom-color: #20313d !important;
    }
    [bd-theme="bd-theme-dark"] .sp-filters .select2-container--default .select2-selection--single .select2-selection__arrow b,
    [data-bs-theme="dark"] .sp-filters .select2-container--default .select2-selection--single .select2-selection__arrow b,
    body.dark-mode .sp-filters .select2-container--default .select2-selection--single .select2-selection__arrow b {
        border-color: #94a3b8 transparent transparent transparent;
    }

</style>

<div class="sp" id="spRoot">
    <div class="sp-crumb">TPHL / Sales CRM</div>
    <div class="sp-head">
        <div>
            <h1>
                <?php if ($tab === 'team'): ?>Team Performance
                <?php elseif ($tab === 'executive'): ?>Executive Performance
                <?php elseif ($tab === 'inactive-executive'): ?>Inactive Executive Performance
                <?php else: ?>Sales Performance<?php endif; ?>
                <span class="sp-live">Live data</span>
            </h1>
            <p>
                <?php if ($tab === 'team'): ?>Compare teams, track targets, visits completed and bookings brought by each sales team.
                <?php elseif ($tab === 'executive'): ?>Individual performance cards and activity breakdown for active sales personnel.
                <?php elseif ($tab === 'inactive-executive'): ?>Historical performance of inactive or resigned sales personnel (leads, follow-ups, visits, and bookings).
                <?php else: ?>Overall 5-stage sales funnel (Received ➔ Contacted ➔ Visit Scheduled ➔ Visited ➔ Booked) and performance overview.<?php endif; ?>
            </p>
        </div>
        <a class="sp-btn" href="<?= sp_h($exportUrl) ?>" id="spExportBtn"><i class="fa-regular fa-file-arrow-down"></i> Export report</a>
    </div>

    <?php
    $todayStr = date('Y-m-d');
    $yesterdayStr = date('Y-m-d', strtotime('-1 day'));
    $weeklyStartStr = date('Y-m-d', strtotime('-6 days'));

    $currPreset = $filters['preset'] ?? '';
    if ($currPreset === 'today' || ($filters['from'] === $todayStr && $filters['to'] === $todayStr)) {
        $activePreset = 'today';
    } elseif ($currPreset === 'yesterday' || ($filters['from'] === $yesterdayStr && $filters['to'] === $yesterdayStr)) {
        $activePreset = 'yesterday';
    } elseif ($currPreset === 'weekly' || ($filters['from'] === $weeklyStartStr && $filters['to'] === $todayStr)) {
        $activePreset = 'weekly';
    } elseif (!empty($filters['from']) && !empty($filters['to'])) {
        $activePreset = 'custom';
    } else {
        $activePreset = 'cycle';
    }
    ?>
    <!-- Filters -->
    <form class="sp-card" style="padding:0" method="get" action="<?= sp_h($base) ?>" id="spFilterForm">
        <input type="hidden" name="tab" value="<?= sp_h($tab) ?>">
        <input type="hidden" name="preset" id="spPreset" value="<?= sp_h($activePreset) ?>">
        
        <div class="sp-preset-bar">
            <div class="sp-preset-label"><i class="fa-solid fa-bolt" style="color:var(--sp-amber)"></i> Quick Filter:</div>
            <div class="sp-preset-pills">
                <button type="button" class="sp-preset-pill <?= $activePreset === 'today' ? 'active' : '' ?>" data-preset="today" title="Filter for Today (<?= date('d M Y') ?>)">
                    <i class="fa-regular fa-sun"></i> Daily (Today)
                </button>
                <button type="button" class="sp-preset-pill <?= $activePreset === 'yesterday' ? 'active' : '' ?>" data-preset="yesterday" title="Filter for Yesterday (<?= date('d M Y', strtotime('-1 day')) ?>)">
                    <i class="fa-regular fa-clock-rotate-left"></i> Yesterday
                </button>
                <button type="button" class="sp-preset-pill <?= $activePreset === 'weekly' ? 'active' : '' ?>" data-preset="weekly" title="Filter for Last 7 Days (<?= date('d M', strtotime('-6 days')) ?> – <?= date('d M Y') ?>)">
                    <i class="fa-regular fa-calendar-week"></i> Weekly (7 Days)
                </button>
                <button type="button" class="sp-preset-pill <?= $activePreset === 'cycle' ? 'active' : '' ?>" data-preset="cycle" title="Monthly Sales Cycle (26th to 25th)">
                    <i class="fa-regular fa-rotate"></i> Sales Cycle
                </button>
                <button type="button" class="sp-preset-pill <?= $activePreset === 'custom' ? 'active' : '' ?>" data-preset="custom" title="Custom Date Range">
                    <i class="fa-regular fa-calendar-range"></i> Custom Range
                </button>
            </div>
        </div>
        <div class="sp-filters">
            <select name="cycle" id="spCycle" title="Sales cycle / Period">
                <?php foreach ($cycleOptions as $c): ?>
                    <option value="<?= sp_h($c['value']) ?>" <?= $c['value'] === $selectedCycle ? 'selected' : '' ?>><?= sp_h($c['label']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="sp-range">
                <input type="date" name="from" id="spFrom" value="<?= sp_h($filters['from']) ?>" title="Custom start date">
                <span>to</span>
                <input type="date" name="to" id="spTo" value="<?= sp_h($filters['to']) ?>" title="Custom end date">
            </div>
            <select name="project_id" id="spProject" class="sp-select2" data-placeholder="All projects">
                <option value="">All projects</option>
                <?php foreach ($projects as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= (int) $filters['project_id'] === (int) $p['id'] ? 'selected' : '' ?>><?= sp_h($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="team_id" id="spTeam" class="sp-select2" data-placeholder="All teams">
                <option value="">All teams</option>
                <?php foreach ($teams as $t): if (!$t['active']) continue; ?>
                    <option value="<?= $t['id'] ?>" <?= (int) $filters['team_id'] === $t['id'] ? 'selected' : '' ?>><?= sp_h($t['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="executive_id" id="spExec" class="sp-select2" data-placeholder="All executives">
                <option value="">All executives</option>
                <?php foreach ($salesUsers as $su): if (!$su['active'] && (int) $filters['executive_id'] !== $su['id']) continue; ?>
                    <option value="<?= $su['id'] ?>" data-team="<?= $su['team_id'] ?>" <?= (int) $filters['executive_id'] === $su['id'] ? 'selected' : '' ?>>
                        <?= sp_h($su['name']) ?><?= $su['team_id'] && isset($teams[$su['team_id']]) ? ' · ' . sp_h($teams[$su['team_id']]['name']) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="sp-spacer"></div>
            <button type="submit" class="sp-btn primary" id="spApply">Apply filters</button>
            <a href="<?= sp_h($base . '?tab=' . $tab) ?>" class="sp-btn" id="spReset">Reset</a>
        </div>
        <div class="sp-period-note">
            <span>Period: <b><?= sp_h($period['label']) ?></b><?= $period['is_custom'] ? ' (custom range)' : ($period['is_all_time'] ? ' (lifetime)' : ' · ' . sp_h($period['cycle_label'])) ?></span>
            <span>Scope: <b><?= sp_h($scopeLabel) ?></b></span>
            <span>Updated: <b><?= date('d M Y, h:i A') ?></b></span>
        </div>
    </form>

    <nav class="sp-tabs">
        <a href="<?= sp_h($tabUrl('overview')) ?>" class="<?= $tab === 'overview' ? 'active' : '' ?>" id="spTabOverview">Overview</a>
        <a href="<?= sp_h($tabUrl('team')) ?>" class="<?= $tab === 'team' ? 'active' : '' ?>" id="spTabTeam">Team performance</a>
        <a href="<?= sp_h($tabUrl('executive')) ?>" class="<?= $tab === 'executive' ? 'active' : '' ?>" id="spTabExecutive">Executive performance</a>
        <a href="<?= sp_h($tabUrl('inactive-executive')) ?>" class="<?= $tab === 'inactive-executive' ? 'active' : '' ?>" id="spTabInactiveExecutive">Inactive executive performance</a>
    </nav>

    <?php if ($tab === 'overview'): ?>
        <!-- ===================== OVERVIEW ===================== -->
        <?php $lifetime = $quality['coverage']['total'] ?? 0; ?>
        <div class="sp-kpis">
            <div class="sp-kpi click" data-gap="stage_1" style="cursor:pointer" title="Click to view Leads Received (Stage 01)">
                <div class="lbl"><i class="fa-regular fa-user-group"></i> Leads Received</div>
                <div class="val"><?= sp_n($o['stage_1']) ?></div>
                <div class="hint"><?= sp_n($lifetime) ?> lifetime <i class="fa-regular fa-arrow-up-right-from-square" style="font-size:9.5px;margin-left:2px"></i></div>
            </div>
            <div class="sp-kpi click" data-gap="stage_2" style="cursor:pointer" title="Click to view Contacted Leads (Stage 02)">
                <div class="lbl"><i class="fa-regular fa-phone"></i> Contacted</div>
                <div class="val"><?= sp_n($o['stage_2']) ?></div>
                <div class="hint"><?= $o['contact_rate'] ?>% contact rate <i class="fa-regular fa-arrow-up-right-from-square" style="font-size:9.5px;margin-left:2px"></i></div>
            </div>
            <div class="sp-kpi amber click" data-gap="no_response_leads" title="Click to view No Response leads history" style="cursor:pointer">
                <div class="lbl"><i class="fa-regular fa-phone-slash"></i> No Response</div>
                <div class="val"><?= sp_n($o['no_response'] ?? 0) ?></div>
                <div class="hint"><?= sp_pct($o['no_response'] ?? 0, $o['stage_1']) ?> of received <i class="fa-regular fa-arrow-up-right-from-square" style="font-size:9.5px;margin-left:2px"></i></div>
            </div>
            <div class="sp-kpi warn click" data-gap="uncontacted" style="cursor:pointer" title="Click to view Not Contacted leads">
                <div class="lbl"><i class="fa-regular fa-phone-xmark"></i> Not Contacted</div>
                <div class="val"><?= sp_n($o['uncontacted']) ?></div>
                <div class="hint"><?= sp_pct($o['uncontacted'], $o['stage_1']) ?> need first contact <i class="fa-regular fa-arrow-up-right-from-square" style="font-size:9.5px;margin-left:2px"></i></div>
            </div>
            <div class="sp-kpi click" data-gap="bookings" style="cursor:pointer" title="Click to view Bookings in this period">
                <div class="lbl"><i class="fa-regular fa-calendar-check"></i> Bookings</div>
                <div class="val"><?= sp_n($o['bookings']) ?></div>
                <div class="hint"><?= sp_n($o['stage_5'] ?? 0) ?> unique clients · <?= sp_tk($o['booking_value']) ?> <i class="fa-regular fa-arrow-up-right-from-square" style="font-size:9.5px;margin-left:2px"></i></div>
            </div>
            <div class="sp-kpi click" data-gap="confirmed_sales" style="cursor:pointer" title="Click to view Confirmed Sales">
                <div class="lbl"><i class="fa-regular fa-circle-check"></i> Confirmed Sales</div>
                <div class="val"><?= sp_n($o['confirmed_sales']) ?></div>
                <div class="hint"><?= sp_pct($o['confirmed_sales'], $o['bookings']) ?> of bookings verified <i class="fa-regular fa-arrow-up-right-from-square" style="font-size:9.5px;margin-left:2px"></i></div>
            </div>
            <div class="sp-kpi click" data-gap="collection" style="cursor:pointer" title="Click to view Verified Collections">
                <div class="lbl"><i class="fa-regular fa-arrow-trend-up"></i> Booking Collection</div>
                <div class="val"><?= sp_tk($o['collection']) ?></div>
                <div class="hint">New: <?= sp_tk($o['collection_new'] ?? 0) ?> · Prev: <?= sp_tk($o['collection_prev'] ?? 0) ?> <i class="fa-regular fa-arrow-up-right-from-square" style="font-size:9.5px;margin-left:2px"></i></div>
            </div>
        </div>

        <div class="sp-grid">
            <div class="sp-card">
                <div class="sp-card-head">
                    <div>
                        <h3>Sales Funnel Performance</h3>
                        <div class="sp-sub">Leads received this period → furthest stage reached up to today (click any bar to view leads)</div>
                    </div>
                </div>
                <div id="spFunnelOverview" data-source="overall"></div>
                <div class="sp-note">
                    Conversion from leads received this period. Each stage counts leads that reached it <em>or any later stage</em>.
                    <?php if ($data['cohort_gaps']['booked_without_visit'] > 0): ?>
                        <br><span style="color:var(--sp-amber)"><i class="fa-regular fa-triangle-exclamation"></i>
                        <?= sp_n($data['cohort_gaps']['booked_without_visit']) ?> booked leads have no visit logged – visits are under-recorded.</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="sp-stack">
                <?php
                $attn = [];
                if ($o['uncontacted'] > 0) $attn[] = ['ic' => 'fa-phone', 'cls' => '', 't' => sp_n($o['uncontacted']) . ' uncontacted leads', 'd' => 'Received this period, no call or follow-up yet', 'gap' => 'uncontacted'];
                if ($o['overdue'] > 0) $attn[] = ['ic' => 'fa-clock-rotate-left', 'cls' => 'red', 't' => sp_n($o['overdue']) . ' overdue follow-ups', 'd' => 'Next follow-up date has passed (open leads)', 'gap' => 'overdue'];
                if ($o['upcoming_visits'] > 0) $attn[] = ['ic' => 'fa-location-dot', 'cls' => 'green', 't' => sp_n($o['upcoming_visits']) . ' site visits in next 7 days', 'd' => 'Confirm with assigned executives', 'gap' => 'upcoming_visits'];
                if ($o['due_today'] > 0) $attn[] = ['ic' => 'fa-calendar-day', 'cls' => 'green', 't' => sp_n($o['due_today']) . ' follow-ups due today', 'd' => 'Scheduled callbacks for today', 'gap' => 'due_today'];
                $unverified = $o['bookings'] - $o['confirmed_sales'];
                if ($unverified > 0) $attn[] = ['ic' => 'fa-file-invoice-dollar', 'cls' => '', 't' => sp_n($unverified) . ' bookings awaiting payment verification', 'd' => 'Not counted as confirmed sales yet', 'gap' => 'unverified_bookings'];
                ?>
                <div class="sp-card">
                    <div class="sp-card-head" style="margin-bottom:4px">
                        <h3>Needs Attention</h3>
                        <span class="sp-count-chip"><?= count($attn) ?></span>
                    </div>
                    <?php if (!$attn): ?>
                        <div class="sp-empty"><i class="fa-regular fa-circle-check" style="color:var(--sp-green)"></i> Nothing urgent.</div>
                    <?php endif; ?>
                    <?php foreach ($attn as $a): ?>
                        <div class="sp-attn click" data-gap="<?= sp_h($a['gap']) ?>" style="cursor:pointer" title="Click to view records">
                            <div class="ic <?= $a['cls'] ?>"><i class="fa-regular <?= $a['ic'] ?>"></i></div>
                            <div>
                                <div class="t"><?= $a['t'] ?></div>
                                <div class="d"><?= $a['d'] ?></div>
                            </div>
                            <i class="fa-regular fa-arrow-up-right-from-square go"></i>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="sp-card">
                    <div class="sp-card-head" style="margin-bottom:12px"><h3>Activity Snapshot</h3></div>
                    <div class="sp-mini-grid">
                        <div class="sp-mini click" data-gap="follow_ups" style="cursor:pointer" title="Click to view follow-ups"><div class="k">Follow-ups logged</div><div class="v"><?= sp_n($o['follow_ups']) ?></div><div class="s"><?= sp_n($o['leads_followed']) ?> unique leads <i class="fa-regular fa-arrow-up-right-from-square" style="font-size:9.5px"></i></div></div>
                        <div class="sp-mini"><div class="k">Sales executives</div><div class="v"><?= $activeExecCount ?></div><div class="s">active in scope</div></div>
                        <div class="sp-mini"><div class="k">First response</div><div class="v"><?= sp_hours($o['median_response_hours']) ?></div><div class="s">median · <?= $o['contacted_24h_rate'] ?? 0 ?>% within 24h</div></div>
                        <div class="sp-mini click" data-gap="onboarded" style="cursor:pointer" title="Click to view onboarded clients"><div class="k">Clients onboarded</div><div class="v"><?= sp_n($o['onboarded']) ?></div><div class="s">this period <i class="fa-regular fa-arrow-up-right-from-square" style="font-size:9.5px"></i></div></div>
                    </div>
                    <div class="sp-spark" id="spSpark" title="Follow-ups logged per day"></div>
                    <div class="sp-note" style="margin-top:4px">Daily follow-ups logged this period</div>
                </div>

                <?php
                if ($o['uncontacted'] > $o['stage_1'] * 0.3) $focus = 'Prioritize first contact: ' . sp_pct($o['uncontacted'], $o['stage_1'], 0) . ' of this period\'s leads have not been called yet.';
                elseif ($o['overdue'] > 0) $focus = 'Clear overdue follow-ups – ' . sp_n($o['overdue']) . ' open leads have missed their callback date.';
                elseif ($o['bookings'] > $o['confirmed_sales']) $focus = 'Push accounts to verify ' . sp_n($o['bookings'] - $o['confirmed_sales']) . ' pending booking payments.';
                else $focus = 'Pipeline is healthy – focus on converting completed visits to bookings.';
                ?>
                <div class="sp-focus">
                    <div class="k">Focus for today</div>
                    <div class="v"><?= sp_h($focus) ?></div>
                </div>
            </div>
        </div>

        <!-- Lead operations & data quality -->
        <?php
        $cov = $quality['coverage'];
        $checks = $quality['checks'];
        $byKey = [];
        foreach ($checks as $c) $byKey[$c['key']] = $c;
        $dup = $byKey['duplicate_phone']['count'] ?? 0;
        ?>
        <div class="sp-section-head">
            <div>
                <h2>Lead Operations &amp; Data Quality</h2>
                <p>Lifetime lead inventory and every data gap that can make the numbers above wrong</p>
            </div>
            <span class="sp-pill">All-time snapshot · <?= date('d M Y') ?></span>
        </div>

        <?php if ($dup > 0): ?>
            <div class="sp-banner">
                <div class="ic"><i class="fa-regular fa-clone"></i></div>
                <div>
                    <div class="t"><?= sp_n($dup) ?> leads share a phone number with another lead</div>
                    <div class="d">Duplicate imports inflate “Leads received” and lower conversion. Review and merge before judging teams on conversion.</div>
                </div>
                <button type="button" class="sp-btn sm" data-gap="duplicate_phone">Review matches</button>
            </div>
        <?php endif; ?>

        <div class="sp-grid-2">
            <div class="sp-card">
                <div class="sp-card-head">
                    <div><h3>Lead Coverage</h3><div class="sp-sub">Total lifetime leads</div></div>
                    <div class="sp-cov-big"><?= sp_n($cov['total']) ?></div>
                </div>
                <div class="sp-cov-row">
                    <div class="top"><span>Assignment Coverage</span><span><?= sp_pct($cov['assigned'], $cov['total']) ?></span></div>
                    <div class="sp-bar"><span style="width:<?= $cov['total'] ? round($cov['assigned'] / $cov['total'] * 100, 1) : 0 ?>%"></span></div>
                    <div class="bot"><span>Assigned <?= sp_n($cov['assigned']) ?></span><span class="warn">Unassigned <?= sp_n($cov['total'] - $cov['assigned']) ?></span></div>
                </div>
                <div class="sp-cov-row">
                    <div class="top"><span>Contact Coverage</span><span><?= sp_pct($cov['contacted'], $cov['total']) ?></span></div>
                    <div class="sp-bar"><span style="width:<?= $cov['total'] ? round($cov['contacted'] / $cov['total'] * 100, 1) : 0 ?>%"></span></div>
                    <div class="bot"><span>Contacted <?= sp_n($cov['contacted']) ?></span><span class="warn">Not contacted <?= sp_n($cov['total'] - $cov['contacted']) ?></span></div>
                </div>
                <div class="sp-funnel-foot">
                    <div><div class="k">Owned by inactive staff</div><div class="v"><?= sp_n($byKey['owner_inactive']['count'] ?? 0) ?></div><div class="s">open leads to reassign</div></div>
                    <div><div class="k">Uncontacted &gt; 24h</div><div class="v"><?= sp_n($o['backlog_uncontacted']) ?></div><div class="s">open, never touched</div></div>
                    <div><div class="k">Owner missing / non-sales</div><div class="v"><?= sp_n(($byKey['owner_missing']['count'] ?? 0) + ($byKey['owner_non_sales']['count'] ?? 0)) ?></div><div class="s">no team credit</div></div>
                </div>
                <div style="margin-top:12px"><button type="button" class="sp-link" data-gap="unassigned">View <?= sp_n($cov['total'] - $cov['assigned']) ?> unassigned leads →</button></div>
            </div>

            <div class="sp-card">
                <div class="sp-card-head"><div><h3>Follow-up &amp; Review Queues</h3><div class="sp-sub">Live workload as of today</div></div></div>
                <div class="sp-queue"><div class="ic"><i class="fa-regular fa-calendar-day"></i></div><div><div class="t">Follow-ups Due Today</div><div class="d">Scheduled callbacks for today</div></div><div class="n"><?= sp_n($o['due_today']) ?></div></div>
                <div class="sp-queue"><div class="ic"><i class="fa-regular fa-clock"></i></div><div><div class="t">Overdue Follow-ups</div><div class="d">Open leads past their next follow-up date</div></div><div class="n"><?= sp_n($o['overdue']) ?></div></div>
                <div class="sp-queue"><div class="ic"><i class="fa-regular fa-location-dot"></i></div><div><div class="t">Upcoming Site Visits</div><div class="d">Planned in the next 7 days</div></div><div class="n"><?= sp_n($o['upcoming_visits']) ?></div></div>
                <div class="sp-queue"><div class="ic"><i class="fa-regular fa-file-invoice-dollar"></i></div><div><div class="t">Payments Awaiting Verification</div><div class="d">Excluded from collection until verified</div></div><div class="n"><?= sp_n($byKey['payment_pending_verification']['count'] ?? 0) ?></div></div>
                <div class="sp-queue"><div class="ic"><i class="fa-regular fa-user-check"></i></div><div><div class="t">Confirmed, Not Onboarded</div><div class="d">Verified bookings without onboarding</div></div><div class="n"><?= sp_n($byKey['booked_not_onboarded']['count'] ?? 0) ?></div></div>
            </div>
        </div>

        <div class="sp-grid-2" style="margin-top:18px">
            <div class="sp-card">
                <div class="sp-card-head"><div><h3>Project Performance</h3><div class="sp-sub">Leads received vs bookings this period</div></div></div>
                <div class="sp-scroll">
                    <table class="sp-table">
                        <thead><tr><th>Project</th><th class="num">Leads</th><th class="num">Bookings</th><th class="num">Confirmed</th><th class="num">Value</th><th class="num">Conversion</th></tr></thead>
                        <tbody>
                            <?php if (!$data['projects']): ?><tr><td colspan="6" class="sp-empty">No project activity in this period.</td></tr><?php endif; ?>
                            <?php foreach ($data['projects'] as $p): ?>
                                <tr>
                                    <td class="nm"><?= sp_h($p['name']) ?></td>
                                    <td class="num"><?= sp_n($p['leads']) ?></td>
                                    <td class="num"><?= sp_n($p['bookings']) ?></td>
                                    <td class="num"><?= sp_n($p['confirmed']) ?></td>
                                    <td class="num"><?= sp_tk($p['value']) ?></td>
                                    <td class="num"><?= $p['leads'] ? sp_pct($p['bookings'], $p['leads'], 2) : '—' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="sp-note">Bookings are counted by booking date; some bookings belong to leads received in earlier periods.</div>
            </div>
            <div class="sp-card">
                <div class="sp-card-head">
                    <div><h3>Team Performance</h3><div class="sp-sub">Bookings, confirmed sales and booking value this period</div></div>
                    <a class="sp-link" href="<?= sp_h($tabUrl('team')) ?>">All teams →</a>
                </div>
                <div class="sp-scroll">
                    <table class="sp-table">
                        <thead>
                            <tr>
                                <th>Team</th>
                                <th class="num">Leads</th>
                                <th class="num">Bookings</th>
                                <th class="num">Confirmed</th>
                                <th class="num">Value</th>
                                <th class="num">Conversion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$realTeams): ?><tr><td colspan="6" class="sp-empty">No team activity in this period.</td></tr><?php endif; ?>
                            <?php foreach (array_slice($realTeams, 0, 8) as $t): ?>
                                <tr style="cursor:pointer" onclick="window.location='<?= sp_h($tabUrl('team') . '&focus_team=' . $t['key']) ?>'">
                                    <td class="nm">
                                        <span style="font-weight:600"><?= sp_h($t['name']) ?></span>
                                        <?php if (!empty($t['leaders'])): ?>
                                            <span class="sp-sub" style="display:block;font-size:11px;color:var(--sp-muted)">Lead: <?= sp_h(implode(', ', $t['leaders'])) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="num"><?= sp_n($t['stage_1']) ?></td>
                                    <td class="num" style="font-weight:700;color:var(--sp-green)"><?= sp_n($t['bookings']) ?></td>
                                    <td class="num"><?= sp_n($t['confirmed_sales']) ?></td>
                                    <td class="num"><?= sp_tk($t['booking_value']) ?></td>
                                    <td class="num"><?= $t['stage_1'] ? sp_pct($t['bookings'], $t['stage_1'], 2) : '—' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="sp-note">Click any team to view member-wise breakdown, visits, and unassigned leads.</div>
            </div>
        </div>

        <!-- AI Data Quality Checks -->
        <div class="sp-card" style="margin-top:18px">
            <div class="sp-card-head">
                <div>
                    <h3><i class="fa-solid fa-wand-magic-sparkles" style="color:var(--sp-green);margin-right:6px"></i> AI Data Quality Checks</h3>
                    <div class="sp-sub">Fix these to keep performance numbers exact. Click “Review” to see the affected records.</div>
                </div>
            </div>
            <?php
            $groups = [];
            foreach ($checks as $c) $groups[$c['group']][] = $c;
            foreach ($groups as $gName => $items): ?>
                <div class="sp-dq-group">
                    <h4><?= sp_h($gName) ?></h4>
                    <div class="sp-dq-grid">
                        <?php foreach ($items as $c): ?>
                            <div class="sp-dq">
                                <div class="top">
                                    <span class="lbl"><?= sp_h($c['label']) ?></span>
                                    <span class="cnt <?= $c['count'] === 0 ? 'zero' : '' ?>"><?= sp_n($c['count']) ?></span>
                                </div>
                                <div class="desc"><?= sp_h($c['description']) ?></div>
                                <div class="foot">
                                    <span>
                                        <span class="sp-sev <?= $c['count'] === 0 ? 'ok' : $c['severity'] ?>"><?= $c['count'] === 0 ? 'Clean' : $c['severity'] ?></span>
                                        <span class="impact">Affects: <?= sp_h($c['impact']) ?></span>
                                    </span>
                                    <?php if ($c['count'] > 0): ?><button type="button" class="sp-link" data-gap="<?= sp_h($c['key']) ?>">Review</button><?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php elseif ($tab === 'team'): ?>
        <!-- ===================== TEAM ===================== -->
        <?php
        $teamTarget = array_sum(array_column($realTeams, 'target_bookings'));
        $teamMembers = array_sum(array_column($realTeams, 'member_count'));
        $activeTeams = count(array_filter($realTeams, fn($t) => $t['member_count'] > 0));
        ?>
        <div class="sp-kpis">
            <div class="sp-kpi">
                <div class="lbl"><i class="fa-regular fa-users"></i> Active Teams</div>
                <div class="val"><?= $activeTeams ?></div>
                <div class="hint"><?= $teamMembers ?> sales members in <?= count($realTeams) ?> teams</div>
            </div>
            <div class="sp-kpi click" data-gap="stage_1" style="cursor:pointer" title="Click to view all Leads Received">
                <div class="lbl"><i class="fa-regular fa-user-group"></i> Leads Received</div>
                <div class="val"><?= sp_n($o['stage_1']) ?></div>
                <div class="hint"><?= sp_n($teamLeadsSum) ?> in teams · <?= sp_n($unassignedLeads) ?> unassigned gap <i class="fa-regular fa-arrow-up-right-from-square" style="font-size:9.5px"></i></div>
            </div>
            <div class="sp-kpi click" data-gap="confirmed_sales" style="cursor:pointer" title="Click to view all Confirmed Sales">
                <div class="lbl"><i class="fa-regular fa-circle-check"></i> Confirmed Sales</div>
                <div class="val"><?= sp_n($o['confirmed_sales']) ?></div>
                <div class="hint"><?= sp_n($teamConfirmedSum) ?> team · <?= sp_n($nonTeamConfirmed) ?> direct / other <i class="fa-regular fa-arrow-up-right-from-square" style="font-size:9.5px"></i></div>
            </div>
            <div class="sp-kpi click" data-gap="bookings" style="cursor:pointer" title="Click to view all Bookings">
                <div class="lbl"><i class="fa-regular fa-calendar-check"></i> Total Bookings</div>
                <div class="val"><?= sp_n($o['bookings']) ?></div>
                <div class="hint"><?= sp_n($teamBookingsSum) ?> team · <?= sp_n($nonTeamBookings) ?> direct / other <i class="fa-regular fa-arrow-up-right-from-square" style="font-size:9.5px"></i></div>
            </div>
            <div class="sp-kpi <?= $teamTarget ? '' : 'warn' ?>">
                <div class="lbl"><i class="fa-regular fa-bullseye-arrow"></i> Booking Target</div>
                <div class="val"><?= $teamTarget ? sp_n($teamTarget) : '—' ?></div>
                <div class="hint"><?= $teamTarget ? max(0, $teamTarget - $teamBookingsSum) . ' units remaining' : 'No targets set for this cycle' ?></div>
            </div>
            <div class="sp-kpi click" data-gap="collection" style="cursor:pointer" title="Click to view all Collections">
                <div class="lbl"><i class="fa-regular fa-sack-dollar"></i> Booking Collection</div>
                <div class="val"><?= sp_tk($o['collection']) ?></div>
                <div class="hint">New: <?= sp_tk($o['collection_new'] ?? 0) ?> · Prev: <?= sp_tk($o['collection_prev'] ?? 0) ?> <i class="fa-regular fa-arrow-up-right-from-square" style="font-size:9.5px"></i></div>
            </div>
        </div>

        <!-- Team Gap & Reconciliation Banner -->
        <div class="sp-card" style="margin-top:14px;padding:12px 18px;background:var(--sp-card);border:1px dashed var(--sp-border);border-radius:10px">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;font-size:12.5px">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                    <span style="font-weight:700;color:var(--sp-text);display:inline-flex;align-items:center;gap:6px"><i class="fa-solid fa-scale-balanced" style="color:var(--sp-green)"></i> Team Gap Breakdown:</span>
                    <span>Total Leads: <b><?= sp_n($o['stage_1']) ?></b> (Teams: <b><?= sp_n($teamLeadsSum) ?></b> + Unassigned/Other: <b style="color:var(--sp-amber)"><?= sp_n($nonTeamLeads) ?></b>)</span>
                    <span style="color:var(--sp-soft)">|</span>
                    <span>Total Bookings: <b><?= sp_n($o['bookings']) ?></b> (Teams: <b><?= sp_n($teamBookingsSum) ?></b> + Direct/Co: <b style="color:var(--sp-blue)"><?= sp_n($nonTeamBookings) ?></b>)</span>
                    <span style="color:var(--sp-soft)">|</span>
                    <span>Collection: <b><?= sp_tk($o['collection']) ?></b> (Teams: <b><?= sp_tk($teamCollectionSum) ?></b> + Direct: <b><?= sp_tk($nonTeamCollection) ?></b>)</span>
                </div>
                <?php if ($unassignedLeads > 0): ?>
                    <button type="button" class="sp-btn sm click" data-gap="uncontacted" style="font-size:11.5px;padding:4px 10px;background:var(--sp-amber-50, #fff7ed);color:var(--sp-amber, #d97706);border:1px solid rgba(217,119,6,0.3);cursor:pointer" title="Click to view unassigned leads">
                        <i class="fa-regular fa-user-clock"></i> View <?= sp_n($unassignedLeads) ?> Unassigned Leads
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="sp-card">
            <div class="sp-card-head">
                <div><h3>Team Comparison</h3><div class="sp-sub">Click a team to explore its funnel, members and issues</div></div>
                <div class="sp-toolbar" style="margin:0">
                    <select id="spTeamSort">
                        <option value="confirmed_sales">Sort: Confirmed sales</option>
                        <option value="bookings">Sort: Bookings</option>
                        <option value="stage_1">Sort: Leads</option>
                        <option value="contact_rate">Sort: Contact rate</option>
                        <option value="no_response">Sort: No Response</option>
                        <option value="lead_to_sale">Sort: Lead-to-sale</option>
                        <option value="collection">Sort: Collection</option>
                        <option value="overdue">Sort: Overdue follow-ups</option>
                    </select>
                </div>
            </div>
            <div class="sp-scroll">
                <table class="sp-table" id="spTeamTable">
                    <thead>
                        <tr>
                            <th>#</th><th>Team</th><th class="num">Leads</th><th class="num">Contact rate</th><th class="num">No Response</th><th class="num">1st response</th>
                            <th class="num">Follow-ups</th><th class="num">Visits</th><th class="num">Bookings</th><th class="num">Sales / Target</th>
                            <th>Achievement</th><th class="num">Collection</th><th class="num">Overdue</th><th></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot></tfoot>
                </table>
            </div>
            <div class="sp-recon" id="spTeamRecon"></div>
        </div>

        <div class="sp-grid-team" style="margin-top:18px">
            <div class="sp-stack">
                <div class="sp-card">
                    <div class="sp-card-head">
                        <div><h3 id="spTeamFunnelTitle">Sales Funnel</h3><div class="sp-sub" id="spTeamFunnelSub"></div></div>
                    </div>
                    <div id="spFunnelTeam"></div>
                    <div class="sp-note">Conversion from assigned leads. Each stage counts leads that reached it <em>or any later stage</em>.</div>
                </div>
                <div class="sp-card">
                    <div class="sp-card-head" style="margin-bottom:4px"><h3 id="spTeamAttnTitle">Needs Attention</h3><span class="sp-count-chip" id="spTeamAttnCount">0</span></div>
                    <div id="spTeamAttn"></div>
                </div>
            </div>
            <div>
                <div class="sp-card" style="display:flex;flex-direction:column;justify-content:space-between;height:100%">
                    <div>
                        <div class="sp-card-head" style="margin-bottom:8px">
                            <div>
                                <h3 id="spTeamMembersTitle">Team Members Performance</h3>
                                <div class="sp-sub" id="spTeamMembersSub">All members of this team ranked by performance</div>
                            </div>
                            <div class="sp-toolbar" style="margin:0">
                                <select id="spMemberSort" style="height:32px;font-size:12px;padding:0 8px;border-radius:7px">
                                    <option value="bookings">Sort: Bookings</option>
                                    <option value="confirmed_sales">Sort: Confirmed Sales</option>
                                    <option value="stage_2">Sort: Most Contacted</option>
                                    <option value="no_response">Sort: No Response</option>
                                    <option value="stage_1">Sort: Leads Received</option>
                                    <option value="uncontacted">Sort: Not Contacted</option>
                                    <option value="follow_ups">Sort: Follow-ups Logged</option>
                                    <option value="visits_done">Sort: Visits Completed</option>
                                    <option value="booking_value">Sort: Booking Value</option>
                                </select>
                            </div>
                        </div>
                        <div id="spTeamUnassignedAlert" style="display:none;margin-bottom:12px"></div>
                        <div class="sp-scroll">
                            <table class="sp-table" id="spTeamMembersTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Executive</th>
                                        <th class="num">Leads</th>
                                        <th class="num">Contacted</th>
                                        <th class="num">No Response</th>
                                        <th class="num">Not Contacted</th>
                                        <th class="num">Follow-ups</th>
                                        <th class="num">Visits</th>
                                        <th class="num">Bookings</th>
                                        <th class="num">Confirmed</th>
                                        <th class="num">Value</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                                <tfoot></tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="sp-table-foot" style="margin-top:10px">
                        <a class="sp-link" id="spTopExecAll" href="#">View in Executive tab →</a>
                        <span>Click any member to open their complete individual performance profile.</span>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($tab === 'executive'): ?>
        <!-- ===================== EXECUTIVE ===================== -->
        <?php
        $ex = $activeExecs;
        $totalExecLeads = array_sum(array_column($ex, 'stage_1'));
        $totalExecFu = array_sum(array_column($ex, 'follow_ups'));
        $totalExecVisits = array_sum(array_column($ex, 'visits_done'));
        $totalExecBookings = array_sum(array_column($ex, 'bookings'));
        $totalExecValue = array_sum(array_column($ex, 'booking_value'));
        $totalExecConfirmed = array_sum(array_column($ex, 'confirmed_sales'));
        $totalExecCollection = array_sum(array_column($ex, 'collection'));
        ?>
        <div class="sp-kpis">
            <div class="sp-kpi">
                <div class="lbl"><i class="fa-regular fa-id-badge"></i> Active Executives</div>
                <div class="val"><?= count($ex) ?></div>
                <div class="hint"><?= count(array_filter($ex, fn($e) => $e['follow_ups'] > 0)) ?> logged follow-ups · <?= $inactiveExecCount ?> inactive</div>
            </div>
            <div class="sp-kpi click" data-gap="stage_1" style="cursor:pointer" title="Click to view all Leads Received">
                <div class="lbl"><i class="fa-regular fa-user-group"></i> Leads Received</div>
                <div class="val"><?= sp_n($o['stage_1']) ?></div>
                <div class="hint"><?= sp_n($totalExecLeads) ?> active · <?= sp_n($inactiveExecLeadsSum) ?> inactive · <?= sp_n($unassignedLeads) ?> unassigned gap <i class="fa-regular fa-arrow-up-right-from-square" style="font-size:9.5px"></i></div>
            </div>
            <div class="sp-kpi click" data-gap="follow_ups" style="cursor:pointer" title="Click to view Follow-ups Logged">
                <div class="lbl"><i class="fa-regular fa-headset"></i> Follow-ups Logged</div>
                <div class="val"><?= sp_n($o['follow_ups']) ?></div>
                <div class="hint"><?= sp_n($totalExecFu) ?> active execs (<?= count($ex) ? round($totalExecFu / max(1, count($ex))) : 0 ?> avg) <i class="fa-regular fa-arrow-up-right-from-square" style="font-size:9.5px"></i></div>
            </div>
            <div class="sp-kpi click" data-gap="visits_done" style="cursor:pointer" title="Click to view Site Visits Done">
                <div class="lbl"><i class="fa-regular fa-location-dot"></i> Site Visits Done</div>
                <div class="val"><?= sp_n($o['visits_done']) ?></div>
                <div class="hint"><?= sp_n($totalExecVisits) ?> active execs · <?= sp_n(array_sum(array_column($ex, 'upcoming_visits'))) ?> upcoming <i class="fa-regular fa-arrow-up-right-from-square" style="font-size:9.5px"></i></div>
            </div>
            <div class="sp-kpi click" data-gap="bookings" style="cursor:pointer" title="Click to view all Bookings">
                <div class="lbl"><i class="fa-regular fa-calendar-check"></i> Total Bookings</div>
                <div class="val"><?= sp_n($o['bookings']) ?></div>
                <div class="hint"><?= sp_n($totalExecBookings) ?> active · <?= sp_n($inactiveExecBookingsSum) ?> inactive · <?= sp_n($directExecBookings) ?> direct / co <i class="fa-regular fa-arrow-up-right-from-square" style="font-size:9.5px"></i></div>
            </div>
            <div class="sp-kpi click" data-gap="confirmed_sales" style="cursor:pointer" title="Click to view all Confirmed Sales">
                <div class="lbl"><i class="fa-regular fa-circle-check"></i> Confirmed Sales</div>
                <div class="val"><?= sp_n($o['confirmed_sales']) ?></div>
                <div class="hint"><?= sp_n($activeExecConfirmedSum) ?> active · <?= sp_n($inactiveExecConfirmedSum) ?> inactive · <?= sp_n($directExecConfirmed) ?> direct / co <i class="fa-regular fa-arrow-up-right-from-square" style="font-size:9.5px"></i></div>
            </div>
            <div class="sp-kpi click" data-gap="collection" style="cursor:pointer" title="Click to view all Collections">
                <div class="lbl"><i class="fa-regular fa-sack-dollar"></i> Booking Collection</div>
                <div class="val"><?= sp_tk($o['collection']) ?></div>
                <div class="hint">New: <?= sp_tk($o['collection_new'] ?? 0) ?> · Prev: <?= sp_tk($o['collection_prev'] ?? 0) ?> <i class="fa-regular fa-arrow-up-right-from-square" style="font-size:9.5px"></i></div>
            </div>
        </div>

        <!-- Executive Gap & Reconciliation Banner -->
        <div class="sp-card" style="margin-top:14px;padding:12px 18px;background:var(--sp-card);border:1px dashed var(--sp-border);border-radius:10px">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;font-size:12.5px">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                    <span style="font-weight:700;color:var(--sp-text);display:inline-flex;align-items:center;gap:6px"><i class="fa-solid fa-scale-balanced" style="color:var(--sp-green)"></i> Executive Gap Breakdown:</span>
                    <span>Total Leads: <b><?= sp_n($o['stage_1']) ?></b> (Active Execs: <b><?= sp_n($totalExecLeads) ?></b> + Inactive: <b><?= sp_n($inactiveExecLeadsSum) ?></b> + Unassigned Gap: <b style="color:var(--sp-amber)"><?= sp_n($unassignedLeads) ?></b>)</span>
                    <span style="color:var(--sp-soft)">|</span>
                    <span>Total Bookings: <b><?= sp_n($o['bookings']) ?></b> (Active: <b><?= sp_n($totalExecBookings) ?></b> + Inactive: <b><?= sp_n($inactiveExecBookingsSum) ?></b> + Direct/Co: <b style="color:var(--sp-blue)"><?= sp_n($directExecBookings) ?></b>)</span>
                    <span style="color:var(--sp-soft)">|</span>
                    <span>Confirmed Sales: <b><?= sp_n($o['confirmed_sales']) ?></b> (Active: <b><?= sp_n($activeExecConfirmedSum) ?></b> + Inactive: <b><?= sp_n($inactiveExecConfirmedSum) ?></b> + Direct: <b><?= sp_n($directExecConfirmed) ?></b>)</span>
                    <span style="color:var(--sp-soft)">|</span>
                    <span>Collection: <b><?= sp_tk($o['collection']) ?></b> (Active: <b><?= sp_tk($totalExecCollection) ?></b> + Inactive/Direct: <b><?= sp_tk(max(0.0, $o['collection'] - $totalExecCollection)) ?></b>)</span>
                </div>
                <div style="display:flex;align-items:center;gap:8px">
                    <?php if ($unassignedLeads > 0): ?>
                        <button type="button" class="sp-btn sm click" data-gap="uncontacted" style="font-size:11.5px;padding:4px 10px;background:var(--sp-amber-50, #fff7ed);color:var(--sp-amber, #d97706);border:1px solid rgba(217,119,6,0.3);cursor:pointer" title="Click to view unassigned leads">
                            <i class="fa-regular fa-user-clock"></i> <?= sp_n($unassignedLeads) ?> Unassigned Leads
                        </button>
                    <?php endif; ?>
                    <a href="<?= sp_h($tabUrl('inactive-executive')) ?>" class="sp-btn sm" style="font-size:11.5px;padding:4px 10px;text-decoration:none"><i class="fa-regular fa-user-slash"></i> Inactive Staff (<?= count($inactiveExecs) ?>) →</a>
                </div>
            </div>
        </div>

        <div class="sp-card">
            <div class="sp-card-head">
                <div>
                    <h3>Executive Performance</h3>
                    <div class="sp-sub">Individual performance cards and activity breakdown for active sales personnel.</div>
                </div>
                <div class="sp-toggle" id="spExecViewToggle">
                    <button type="button" class="on" data-view="cards"><i class="fa-regular fa-grid-2"></i> Cards view</button>
                    <button type="button" data-view="table"><i class="fa-regular fa-table-list"></i> Table view</button>
                </div>
            </div>

            <div class="sp-toolbar">
                <input type="search" id="spExecSearch" placeholder="Search executive or team…">
                <select id="spExecTeam">
                    <option value="">All teams</option>
                    <?php foreach ($realTeams as $t): ?><option value="<?= sp_h($t['key']) ?>"><?= sp_h($t['name']) ?></option><?php endforeach; ?>
                </select>
                <select id="spExecSort">
                    <option value="confirmed_sales">Sort: Confirmed sales</option>
                    <option value="bookings">Sort: Bookings</option>
                    <option value="booking_value">Sort: Booking value</option>
                    <option value="stage_1">Sort: Leads received</option>
                    <option value="follow_ups">Sort: Follow-ups logged</option>
                    <option value="visits_done">Sort: Visits done</option>
                    <option value="contact_rate">Sort: Contact rate</option>
                    <option value="no_response">Sort: No Response</option>
                </select>
                <label><input type="checkbox" id="spExecZero"> Hide zero activity</label>
                <div class="sp-spacer"></div>
                <a href="<?= sp_h($tabUrl('inactive-executive')) ?>" class="sp-btn sm"><i class="fa-regular fa-user-slash"></i> Inactive Executives (<?= count($inactiveExecs) ?>) →</a>
            </div>

            <!-- Cards Container -->
            <div id="spExecCards" class="sp-cards-grid"></div>

            <!-- Table Container -->
            <div id="spExecTableWrap" class="sp-scroll" style="display:none">
                <table class="sp-table" id="spExecTable">
                    <thead>
                        <tr>
                            <th class="sortable" data-k="name">Executive</th>
                            <th class="sortable num" data-k="stage_1">Leads</th>
                            <th class="sortable num" data-k="contact_rate">Contacted</th>
                            <th class="sortable num" data-k="no_response">No Response</th>
                            <th class="sortable num" data-k="median_response_hours">1st response</th>
                            <th class="sortable num" data-k="follow_ups">Follow-ups</th>
                            <th class="sortable num" data-k="overdue">Overdue</th>
                            <th class="sortable num" data-k="backlog_uncontacted">Untouched</th>
                            <th class="sortable num" data-k="visits_done">Visits</th>
                            <th class="sortable num" data-k="bookings">Bookings</th>
                            <th class="sortable num" data-k="booking_value">Value</th>
                            <th class="sortable num" data-k="confirmed_sales">Confirmed</th>
                            <th class="sortable num" data-k="collection">Collection</th>
                            <th class="sortable num" data-k="target_achievement">Target</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot></tfoot>
                </table>
            </div>

            <div class="sp-table-foot">
                <span id="spExecCount"></span>
                <span>Click any card or table row for full activity details &amp; individual sales funnel.</span>
            </div>
            <div class="sp-recon" id="spExecRecon"></div>
        </div>

    <?php elseif ($tab === 'inactive-executive'): ?>
        <!-- ===================== INACTIVE EXECUTIVE ===================== -->
        <?php
        $iex = $inactiveExecs;
        $totalInactLeads = array_sum(array_column($iex, 'stage_1'));
        $totalInactFu = array_sum(array_column($iex, 'follow_ups'));
        $totalInactVisits = array_sum(array_column($iex, 'visits_done'));
        $totalInactBookings = array_sum(array_column($iex, 'bookings'));
        $totalInactValue = array_sum(array_column($iex, 'booking_value'));
        $totalInactConfirmed = array_sum(array_column($iex, 'confirmed_sales'));
        $totalInactCollection = array_sum(array_column($iex, 'collection'));
        ?>
        <div class="sp-banner sp-banner-inactive">
            <div class="ic"><i class="fa-regular fa-user-slash"></i></div>
            <div>
                <div class="t">Inactive &amp; Resigned Personnel Records</div>
                <div class="d">Historical performance metrics for sales personnel whose employment status is marked as Inactive or Resigned in the Employees registry. All past leads, calls, visits, and bookings remain intact for audit and performance tracking.</div>
            </div>
            <a class="sp-btn sm" href="<?= sp_h($tabUrl('executive')) ?>">← Back to Active Executives</a>
        </div>

        <div class="sp-kpis">
            <div class="sp-kpi">
                <div class="lbl"><i class="fa-regular fa-user-xmark"></i> Inactive Staff</div>
                <div class="val"><?= count($iex) ?></div>
                <div class="hint">Inactive or resigned in employees table</div>
            </div>
            <div class="sp-kpi">
                <div class="lbl"><i class="fa-regular fa-user-group"></i> Leads Received</div>
                <div class="val"><?= sp_n($totalInactLeads) ?></div>
                <div class="hint">Lifetime / period assigned leads</div>
            </div>
            <div class="sp-kpi">
                <div class="lbl"><i class="fa-regular fa-headset"></i> Follow-ups Logged</div>
                <div class="val"><?= sp_n($totalInactFu) ?></div>
                <div class="hint">Logged during tenure</div>
            </div>
            <div class="sp-kpi">
                <div class="lbl"><i class="fa-regular fa-location-dot"></i> Site Visits Done</div>
                <div class="val"><?= sp_n($totalInactVisits) ?></div>
                <div class="hint">Completed site visits</div>
            </div>
            <div class="sp-kpi">
                <div class="lbl"><i class="fa-regular fa-calendar-check"></i> Bookings Brought</div>
                <div class="val"><?= sp_n($totalInactBookings) ?></div>
                <div class="hint"><?= sp_tk($totalInactValue) ?> booking value</div>
            </div>
            <div class="sp-kpi">
                <div class="lbl"><i class="fa-regular fa-sack-dollar"></i> Collection</div>
                <div class="val"><?= sp_tk($totalInactCollection) ?></div>
                <div class="hint"><?= sp_n($totalInactConfirmed) ?> confirmed sales</div>
            </div>
        </div>

        <div class="sp-card">
            <div class="sp-card-head">
                <div>
                    <h3>Inactive Executive Performance</h3>
                    <div class="sp-sub">Historical performance cards and records of former and inactive team members.</div>
                </div>
                <div class="sp-toggle" id="spInactiveExecViewToggle">
                    <button type="button" class="on" data-view="cards"><i class="fa-regular fa-grid-2"></i> Cards view</button>
                    <button type="button" data-view="table"><i class="fa-regular fa-table-list"></i> Table view</button>
                </div>
            </div>

            <div class="sp-toolbar">
                <input type="search" id="spInactiveExecSearch" placeholder="Search inactive executive…">
                <select id="spInactiveExecStatus">
                    <option value="">All statuses</option>
                    <option value="inactive">Inactive</option>
                    <option value="resigned">Resigned</option>
                    <option value="legacy">Legacy / Archive</option>
                    <option value="unlinked">Unlinked Account</option>
                </select>
                <select id="spInactiveExecTeam">
                    <option value="">All teams</option>
                    <?php foreach ($realTeams as $t): ?><option value="<?= sp_h($t['key']) ?>"><?= sp_h($t['name']) ?></option><?php endforeach; ?>
                </select>
                <select id="spInactiveExecSort">
                    <option value="confirmed_sales">Sort: Confirmed sales</option>
                    <option value="bookings">Sort: Bookings</option>
                    <option value="booking_value">Sort: Booking value</option>
                    <option value="stage_1">Sort: Leads received</option>
                    <option value="follow_ups">Sort: Follow-ups logged</option>
                    <option value="visits_done">Sort: Visits done</option>
                </select>
                <label><input type="checkbox" id="spInactiveExecZero"> Hide zero activity</label>
            </div>

            <!-- Inactive Cards Grid -->
            <div id="spInactiveExecCards" class="sp-cards-grid"></div>

            <!-- Inactive Table -->
            <div id="spInactiveExecTableWrap" class="sp-scroll" style="display:none">
                <table class="sp-table" id="spInactiveExecTable">
                    <thead>
                        <tr>
                            <th class="sortable" data-k="name">Executive</th>
                            <th>Status</th>
                            <th class="sortable num" data-k="stage_1">Leads</th>
                            <th class="sortable num" data-k="contact_rate">Contacted</th>
                            <th class="sortable num" data-k="no_response">No Response</th>
                            <th class="sortable num" data-k="follow_ups">Follow-ups</th>
                            <th class="sortable num" data-k="visits_done">Visits</th>
                            <th class="sortable num" data-k="bookings">Bookings</th>
                            <th class="sortable num" data-k="booking_value">Value</th>
                            <th class="sortable num" data-k="confirmed_sales">Confirmed</th>
                            <th class="sortable num" data-k="collection">Collection</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="sp-table-foot">
                <span id="spInactiveExecCount"></span>
                <span>Click any row or card to open their complete historical sales funnel and metrics.</span>
            </div>
        </div>
    <?php endif; ?>

    <details class="sp-card sp-defs">
        <summary><i class="fa-regular fa-circle-info" style="color:var(--sp-green)"></i> How these numbers are calculated</summary>
        <dl>
            <dt>Leads received</dt><dd>Active leads whose assignment time (or creation time if never assigned) falls inside the period. Credited to the current owner (<code>assigned_id</code>).</dd>
            <dt>Team</dt><dd>The owner's team (<code>users.team_id</code>). Sales Manager / Head / Executive with the same team_id roll up into that team; all teams + unattributed rows add up exactly to the overall total.</dd>
            <dt>Contacted</dt><dd>Lead has a follow-up logged, a first-contact time, or a call status of Called / Received / Not received.</dd>
            <dt>Qualified</dt><dd>Lead status (current or in any follow-up) is Prospect, High Prospect, Hot, Warm, Will Visit, Visited or Sold.</dd>
            <dt>Visit scheduled / completed</dt><dd>Follow-up next action “site visit” or status Visit Scheduled (Will Visit) → scheduled. Status Visited or follow-up type Visit / Face to Face → completed.</dd>
            <dt>Negotiation</dt><dd>A follow-up has next action “booking”.</dd>
            <dt>Funnel stages</dt><dd>Each stage counts leads that reached it or any later stage, so the funnel never increases. Booking / confirmed in the funnel = leads from this period that are booked as of today.</dd>
            <dt>Bookings (period)</dt><dd>Active bookings (booked / completed) with booking date in the period. Credited to “Sales by”, or to the lead owner if empty.</dd>
            <dt>Confirmed sale</dt><dd>A booking with at least one payment verified by accounts.</dd>
            <dt>Collection</dt><dd>Verified, paid / partially-paid payments with payment date in the period (trashed payments excluded). Clicking opens the drill-down showing the exact split between <b>New Bookings</b> (booked in period) and <b>Previous Bookings</b> (installments/past bookings).</dd>
            <dt>Follow-ups &amp; visits</dt><dd>Follow-ups logged in the period, credited to the person who logged them.</dd>
            <dt>First response</dt><dd>Median time between assignment and first contact, only where both timestamps exist and contact happened after assignment.</dd>
            <dt>Overdue / due today</dt><dd>Live as of today: open (not booked, not dropped) leads whose latest follow-up's next date is before / equal to today.</dd>
            <dt>Target</dt><dd>Booking target from Sales Targets for the selected cycle month. Achievement = bookings in period ÷ target.</dd>
        </dl>
    </details>
</div>

<!-- Drawer (executive details & data-quality drill-down) -->
<div class="sp-drawer-bg" id="spDrawerBg"></div>
<aside class="sp-drawer" id="spDrawer" aria-hidden="true">
    <div class="hd" style="display:flex;justify-content:space-between;align-items:flex-start;padding:16px 20px;border-bottom:1px solid var(--sp-border)">
        <div><h3 id="spDrawerTitle" style="font-size:17px;font-weight:700;margin:0"></h3><div class="sp-sub" id="spDrawerSub" style="font-size:12px;color:var(--sp-muted);margin-top:3px"></div></div>
        <div style="display:flex;align-items:center;gap:10px">
            <button type="button" class="sp-btn sm" id="spDrawerExportBtn" style="display:none;font-size:11.5px;padding:4px 9px" title="Export as CSV"><i class="fa-regular fa-file-csv"></i> Export CSV</button>
            <button type="button" class="sp-x" id="spDrawerClose" aria-label="Close"><i class="fa-regular fa-xmark"></i></button>
        </div>
    </div>
    <div id="spDrawerSearchWrap" style="padding:10px 20px;border-bottom:1px solid var(--sp-border);background:var(--sp-bg-soft, #f8fafc);display:none">
        <div style="position:relative">
            <i class="fa-regular fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--sp-soft);font-size:12px"></i>
            <input type="text" id="spDrawerFilter" placeholder="Filter by lead name, phone, executive, status..." style="width:100%;height:32px;padding:0 10px 0 30px;font-size:12px;border:1px solid var(--sp-border);border-radius:6px;background:var(--sp-surface);color:inherit;outline:none" autocomplete="off">
        </div>
    </div>
    <div class="bd" id="spDrawerBody"></div>
</aside>

<script>
    window.SP_DATA = <?= json_encode($jsData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>;
</script>
<script src="<?= $GLOBALS['base_url'] ?>/assets/js/sales-performance.js?v=<?= @filemtime(__DIR__ . '/../../../public/assets/js/sales-performance.js') ?: time() ?>"></script>

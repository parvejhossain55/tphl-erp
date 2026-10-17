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
    'stages' => $data['stages'],
    'trend' => $data['trend'],
    'baseUrl' => $GLOBALS['base_url'],
    'tab' => $tab,
    'focusTeam' => $filters['focus_team'],
];
?>
<style>
    .sp, .sp-drawer {
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
    .sp-btn { display: inline-flex; align-items: center; gap: 8px; height: 38px; padding: 0 16px; border-radius: 9px; font-size: 13px; font-weight: 600; border: 1px solid var(--sp-border); background: #fff; color: var(--sp-text); cursor: pointer; transition: all .15s; white-space: nowrap; }
    .sp-btn:hover { border-color: #cfd8d4; background: #fafcfb; color: var(--sp-text); }
    .sp-btn.primary { background: var(--sp-green); border-color: var(--sp-green); color: #fff; }
    .sp-btn.primary:hover { background: var(--sp-green-600); color: #fff; }
    .sp-btn.sm { height: 30px; padding: 0 12px; font-size: 12px; }

    .sp-card { background: var(--sp-card); border: 1px solid var(--sp-border); border-radius: var(--sp-radius); padding: 18px 20px; }
    .sp-card h3 { font-size: 15.5px; font-weight: 700; margin: 0; letter-spacing: -.01em; }
    .sp-card .sp-sub { font-size: 12px; color: var(--sp-muted); margin-top: 2px; }
    .sp-card-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 14px; }

    .sp-filters { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; padding: 14px 16px; }
    .sp-filters select, .sp-filters input[type=date] { height: 38px; border: 1px solid var(--sp-border); border-radius: 9px; padding: 0 12px; font-size: 13px; color: var(--sp-text); background: #fff; min-width: 150px; outline: none; transition: border-color .15s, box-shadow .15s; }
    .sp-filters select:focus, .sp-filters input:focus { border-color: var(--sp-green); box-shadow: 0 0 0 3px rgba(11,122,85,.12); }
    .sp-filters .sp-range { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--sp-muted); }
    .sp-filters .sp-range input { min-width: 135px; }
    .sp-filters .sp-spacer { flex: 1; }
    .sp-period-note { font-size: 12px; color: var(--sp-muted); padding: 0 16px 12px; display: flex; gap: 14px; flex-wrap: wrap; }
    .sp-period-note b { color: var(--sp-text); font-weight: 600; }

    .sp-tabs { display: flex; gap: 28px; border-bottom: 1px solid var(--sp-border); margin: 20px 0 18px; }
    .sp-tabs a { padding: 10px 2px; font-size: 13.5px; color: var(--sp-muted); font-weight: 500; border-bottom: 2px solid transparent; margin-bottom: -1px; transition: color .15s; }
    .sp-tabs a:hover { color: var(--sp-text); }
    .sp-tabs a.active { color: var(--sp-green); border-bottom-color: var(--sp-green); font-weight: 600; }

    .sp-kpis { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 14px; margin-bottom: 20px; align-items: stretch; }
    .sp-kpi { background: #fff; border: 1px solid var(--sp-border); border-radius: var(--sp-radius); padding: 16px 18px; display: flex; flex-direction: column; justify-content: space-between; height: 100%; transition: transform .18s cubic-bezier(.16,1,.3,1), box-shadow .18s cubic-bezier(.16,1,.3,1), border-color .18s; }
    .sp-kpi:hover { transform: translateY(-2px); border-color: #cfded7; box-shadow: 0 10px 24px -10px rgba(20,33,28,.18); }
    .sp-kpi .lbl { font-size: 12px; font-weight: 600; color: var(--sp-text); display: flex; align-items: center; gap: 7px; }
    .sp-kpi .lbl i { color: var(--sp-green); font-size: 13px; width: 14px; text-align: center; }
    .sp-kpi .val { font-size: 26px; font-weight: 700; margin: 10px 0 2px; letter-spacing: -.02em; font-variant-numeric: tabular-nums; }
    .sp-kpi .hint { font-size: 11.5px; color: var(--sp-muted); margin-top: auto; }
    .sp-kpi.warn .lbl i, .sp-kpi.warn .hint { color: var(--sp-amber); }

    .sp-grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 18px; align-items: stretch; }
    .sp-grid-2 { display: grid; grid-template-columns: minmax(0, 1.3fr) minmax(0, 1fr); gap: 18px; align-items: stretch; }
    .sp-stack { display: flex; flex-direction: column; gap: 18px; height: 100%; justify-content: space-between; }
    .sp-stack > .sp-card { height: 100%; display: flex; flex-direction: column; justify-content: space-between; flex: 1; }

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
    .sp-dq { border: 1px solid var(--sp-border); border-radius: 11px; padding: 14px 15px; background: #fff; display: flex; flex-direction: column; justify-content: space-between; height: 100%; min-height: 154px; box-shadow: 0 1px 3px rgba(16,24,20,.03); transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease; position: relative; }
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
    .sp-exec-card { background: #fff; border: 1px solid var(--sp-border); border-radius: var(--sp-radius); padding: 16px; transition: transform .18s cubic-bezier(.16,1,.3,1), box-shadow .18s cubic-bezier(.16,1,.3,1), border-color .18s; display: flex; flex-direction: column; justify-content: space-between; height: 100%; gap: 12px; cursor: pointer; position: relative; box-shadow: 0 1px 3px rgba(16,24,20,.03); }
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

    .sp-drawer-bg { position: fixed; inset: 0; background: rgba(15,23,20,.35); opacity: 0; pointer-events: none; transition: opacity .2s; z-index: 1050; }
    .sp-drawer-bg.open { opacity: 1; pointer-events: auto; }
    .sp-drawer { padding: 0; min-height: 0; position: fixed; top: 0; right: 0; height: 100vh; width: min(560px, 96vw); background: #fff; z-index: 1060; transform: translateX(100%); transition: transform .28s cubic-bezier(.2,.8,.2,1); display: flex; flex-direction: column; box-shadow: -20px 0 40px -20px rgba(0,0,0,.25); }
    .sp-drawer.open { transform: none; }
    .sp-drawer .hd { padding: 18px 22px; border-bottom: 1px solid var(--sp-border); display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
    .sp-drawer .bd { padding: 18px 22px; overflow-y: auto; flex: 1; }
    .sp-x { border: 0; background: #f2f5f4; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; color: var(--sp-muted); }
    .sp-x:hover { background: #e7ecea; }
    .sp-dtable { width: 100%; font-size: 12px; border-collapse: collapse; }
    .sp-dtable th { text-align: left; color: var(--sp-muted); font-weight: 500; padding: 8px; border-bottom: 1px solid var(--sp-border); position: sticky; top: 0; background: #fff; }
    .sp-dtable td { padding: 8px; border-bottom: 1px solid #f0f3f2; }
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
        .sp-kpis { grid-template-columns: repeat(6, minmax(0, 1fr)); }
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
                <?php else: ?>Overall 5-stage sales funnel (Received ➔ Contacted ➔ Will Visit ➔ Visited ➔ Booked) and performance overview.<?php endif; ?>
            </p>
        </div>
        <a class="sp-btn" href="<?= sp_h($exportUrl) ?>" id="spExportBtn"><i class="fa-regular fa-file-arrow-down"></i> Export report</a>
    </div>

    <!-- Filters -->
    <form class="sp-card" style="padding:0" method="get" action="<?= sp_h($base) ?>" id="spFilterForm">
        <input type="hidden" name="tab" value="<?= sp_h($tab) ?>">
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
            <select name="project_id" id="spProject">
                <option value="">All projects</option>
                <?php foreach ($projects as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= (int) $filters['project_id'] === (int) $p['id'] ? 'selected' : '' ?>><?= sp_h($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="team_id" id="spTeam">
                <option value="">All teams</option>
                <?php foreach ($teams as $t): if (!$t['active']) continue; ?>
                    <option value="<?= $t['id'] ?>" <?= (int) $filters['team_id'] === $t['id'] ? 'selected' : '' ?>><?= sp_h($t['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="executive_id" id="spExec">
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
            <div class="sp-kpi">
                <div class="lbl"><i class="fa-regular fa-user-group"></i> Leads Received</div>
                <div class="val"><?= sp_n($o['stage_1']) ?></div>
                <div class="hint"><?= sp_n($lifetime) ?> lifetime</div>
            </div>
            <div class="sp-kpi">
                <div class="lbl"><i class="fa-regular fa-phone"></i> Contacted</div>
                <div class="val"><?= sp_n($o['stage_2']) ?></div>
                <div class="hint"><?= $o['contact_rate'] ?>% contact rate</div>
            </div>
            <div class="sp-kpi warn">
                <div class="lbl"><i class="fa-regular fa-phone-xmark"></i> Uncontacted</div>
                <div class="val"><?= sp_n($o['uncontacted']) ?></div>
                <div class="hint"><?= sp_pct($o['uncontacted'], $o['stage_1']) ?> need first contact</div>
            </div>
            <div class="sp-kpi">
                <div class="lbl"><i class="fa-regular fa-calendar-check"></i> Bookings</div>
                <div class="val"><?= sp_n($o['bookings']) ?></div>
                <div class="hint"><?= sp_tk($o['booking_value']) ?> booked this period</div>
            </div>
            <div class="sp-kpi">
                <div class="lbl"><i class="fa-regular fa-circle-check"></i> Confirmed Sales</div>
                <div class="val"><?= sp_n($o['confirmed_sales']) ?></div>
                <div class="hint"><?= sp_pct($o['confirmed_sales'], $o['bookings']) ?> of bookings verified</div>
            </div>
            <div class="sp-kpi">
                <div class="lbl"><i class="fa-regular fa-arrow-trend-up"></i> Booking Collection</div>
                <div class="val"><?= sp_tk($o['collection']) ?></div>
                <div class="hint">Verified, collected this period</div>
            </div>
        </div>

        <div class="sp-grid">
            <div class="sp-card">
                <div class="sp-card-head">
                    <div>
                        <h3>Sales Funnel Performance</h3>
                        <div class="sp-sub">Leads received this period → furthest stage reached up to today</div>
                    </div>
                    <div class="sp-toggle" data-funnel-toggle="spFunnelOverview">
                        <button type="button" class="on" data-mode="overall">Overall</button>
                        <button type="button" data-mode="step">Step-wise</button>
                    </div>
                </div>
                <div id="spFunnelOverview" data-source="overall"></div>
                <div class="sp-note">
                    Overall: conversion from leads received. Step-wise: conversion from the previous stage.
                    Each stage counts leads that reached it <em>or any later stage</em>.
                    <?php if ($data['cohort_gaps']['booked_without_visit'] > 0): ?>
                        <br><span style="color:var(--sp-amber)"><i class="fa-regular fa-triangle-exclamation"></i>
                        <?= sp_n($data['cohort_gaps']['booked_without_visit']) ?> booked leads have no visit logged – visits are under-recorded.</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="sp-stack">
                <?php
                $attn = [];
                if ($o['uncontacted'] > 0) $attn[] = ['ic' => 'fa-phone', 'cls' => '', 't' => sp_n($o['uncontacted']) . ' uncontacted leads', 'd' => 'Received this period, no call or follow-up yet', 'tab' => 'executive'];
                if ($o['overdue'] > 0) $attn[] = ['ic' => 'fa-clock-rotate-left', 'cls' => 'red', 't' => sp_n($o['overdue']) . ' overdue follow-ups', 'd' => 'Next follow-up date has passed (open leads)', 'tab' => 'team'];
                if ($o['upcoming_visits'] > 0) $attn[] = ['ic' => 'fa-location-dot', 'cls' => 'green', 't' => sp_n($o['upcoming_visits']) . ' site visits in next 7 days', 'd' => 'Confirm with assigned executives', 'tab' => 'executive'];
                if ($o['due_today'] > 0) $attn[] = ['ic' => 'fa-calendar-day', 'cls' => 'green', 't' => sp_n($o['due_today']) . ' follow-ups due today', 'd' => 'Scheduled callbacks for today', 'tab' => 'executive'];
                $unverified = $o['bookings'] - $o['confirmed_sales'];
                if ($unverified > 0) $attn[] = ['ic' => 'fa-file-invoice-dollar', 'cls' => '', 't' => sp_n($unverified) . ' bookings awaiting payment verification', 'd' => 'Not counted as confirmed sales yet', 'tab' => 'executive'];
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
                        <a class="sp-attn" href="<?= sp_h($tabUrl($a['tab'])) ?>" style="color:inherit">
                            <div class="ic <?= $a['cls'] ?>"><i class="fa-regular <?= $a['ic'] ?>"></i></div>
                            <div>
                                <div class="t"><?= $a['t'] ?></div>
                                <div class="d"><?= $a['d'] ?></div>
                            </div>
                            <i class="fa-regular fa-arrow-right go"></i>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="sp-card">
                    <div class="sp-card-head" style="margin-bottom:12px"><h3>Activity Snapshot</h3></div>
                    <div class="sp-mini-grid">
                        <div class="sp-mini"><div class="k">Follow-ups logged</div><div class="v"><?= sp_n($o['follow_ups']) ?></div><div class="s"><?= sp_n($o['leads_followed']) ?> unique leads</div></div>
                        <div class="sp-mini"><div class="k">Sales executives</div><div class="v"><?= $activeExecCount ?></div><div class="s">active in scope</div></div>
                        <div class="sp-mini"><div class="k">First response</div><div class="v"><?= sp_hours($o['median_response_hours']) ?></div><div class="s">median · <?= $o['contacted_24h_rate'] ?? 0 ?>% within 24h</div></div>
                        <div class="sp-mini"><div class="k">Clients onboarded</div><div class="v"><?= sp_n($o['onboarded']) ?></div><div class="s">this period</div></div>
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

        <!-- Data quality checks -->
        <div class="sp-card" style="margin-top:18px">
            <div class="sp-card-head">
                <div>
                    <h3>Data Quality Checks</h3>
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
                    <div><h3>Team Performance</h3><div class="sp-sub">Confirmed sales and booking target achievement</div></div>
                    <a class="sp-link" href="<?= sp_h($tabUrl('team')) ?>">All teams →</a>
                </div>
                <?php $maxConf = max(1, ...array_map(fn($t) => $t['confirmed_sales'], $realTeams ?: [['confirmed_sales' => 1]])); ?>
                <?php foreach (array_slice($realTeams, 0, 8) as $t): ?>
                    <a href="<?= sp_h($tabUrl('team') . '&focus_team=' . $t['key']) ?>" style="display:grid;grid-template-columns:150px 70px 1fr 54px;gap:10px;align-items:center;padding:8px 0;color:inherit;border-bottom:1px solid #f0f3f2">
                        <span style="font-weight:600;font-size:12.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= sp_h($t['name']) ?></span>
                        <span style="font-size:11.5px;color:var(--sp-muted)"><?= $t['confirmed_sales'] ?> sales</span>
                        <div class="sp-bar <?= $t['target_achievement'] !== null && $t['target_achievement'] < 60 ? 'amber' : '' ?>"><span style="width:<?= $t['target_achievement'] !== null ? min(100, $t['target_achievement']) : round($t['confirmed_sales'] / $maxConf * 100) ?>%"></span></div>
                        <b style="font-size:12px;text-align:right;color:var(--sp-green)"><?= $t['target_achievement'] !== null ? $t['target_achievement'] . '%' : '<span style="color:var(--sp-soft);font-weight:500" title="No booking target set">—</span>' ?></b>
                    </a>
                <?php endforeach; ?>
                <div class="sp-note">“—” = no booking target set in Sales Targets for this cycle.</div>
            </div>
        </div>

    <?php elseif ($tab === 'team'): ?>
        <!-- ===================== TEAM ===================== -->
        <?php
        $teamLeads = array_sum(array_column($realTeams, 'assigned_leads'));
        $teamTarget = array_sum(array_column($realTeams, 'target_bookings'));
        $teamBookings = array_sum(array_column($realTeams, 'bookings'));
        $teamConfirmed = array_sum(array_column($realTeams, 'confirmed_sales'));
        $teamMembers = array_sum(array_column($realTeams, 'member_count'));
        $activeTeams = count(array_filter($realTeams, fn($t) => $t['member_count'] > 0));
        ?>
        <div class="sp-kpis">
            <div class="sp-kpi"><div class="lbl"><i class="fa-regular fa-users"></i> Active Teams</div><div class="val"><?= $activeTeams ?></div><div class="hint"><?= $teamMembers ?> sales members</div></div>
            <div class="sp-kpi"><div class="lbl"><i class="fa-regular fa-phone"></i> Assigned Leads</div><div class="val"><?= sp_n($teamLeads) ?></div><div class="hint">Across all teams this period</div></div>
            <div class="sp-kpi"><div class="lbl"><i class="fa-regular fa-circle-check"></i> Confirmed Sales</div><div class="val"><?= sp_n($teamConfirmed) ?></div><div class="hint"><?= sp_n($teamBookings) ?> bookings · <?= sp_pct($teamConfirmed, $teamBookings) ?> verified</div></div>
            <div class="sp-kpi <?= $teamTarget ? '' : 'warn' ?>"><div class="lbl"><i class="fa-regular fa-bullseye-arrow"></i> Booking Target</div><div class="val"><?= $teamTarget ? sp_n($teamTarget) : '—' ?></div><div class="hint"><?= $teamTarget ? max(0, $teamTarget - $teamBookings) . ' units remaining' : 'No targets set for this cycle' ?></div></div>
            <div class="sp-kpi"><div class="lbl"><i class="fa-regular fa-arrow-trend-up"></i> Target Achievement</div><div class="val"><?= $teamTarget ? sp_pct($teamBookings, $teamTarget) : '—' ?></div><div class="hint"><?= $teamTarget ? sp_n($teamBookings) . ' of ' . sp_n($teamTarget) . ' units' : 'Set targets in Sales Dashboard' ?></div></div>
            <div class="sp-kpi"><div class="lbl"><i class="fa-regular fa-sack-dollar"></i> Booking Collection</div><div class="val"><?= sp_tk(array_sum(array_column($realTeams, 'collection'))) ?></div><div class="hint">Verified this period</div></div>
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
                            <th>#</th><th>Team</th><th class="num">Leads</th><th class="num">Contact rate</th><th class="num">1st response</th>
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

        <div class="sp-grid" style="margin-top:18px">
            <div class="sp-card">
                <div class="sp-card-head">
                    <div><h3 id="spTeamFunnelTitle">Sales Funnel</h3><div class="sp-sub" id="spTeamFunnelSub"></div></div>
                    <div class="sp-toggle" data-funnel-toggle="spFunnelTeam">
                        <button type="button" class="on" data-mode="overall">Overall</button>
                        <button type="button" data-mode="step">Step-wise</button>
                    </div>
                </div>
                <div id="spFunnelTeam"></div>
                <div class="sp-note">Overall: conversion from assigned leads. Step-wise: conversion from the previous stage.</div>
            </div>
            <div class="sp-stack">
                <div class="sp-card">
                    <div class="sp-card-head" style="margin-bottom:6px"><div><h3>Top Executives</h3><div class="sp-sub" id="spTopExecSub"></div></div></div>
                    <table class="sp-table" id="spTopExec">
                        <thead><tr><th>Executive</th><th class="num">Leads</th><th class="num">Contact</th><th class="num">Bookings</th><th class="num">Sales</th></tr></thead>
                        <tbody></tbody>
                    </table>
                    <div style="margin-top:10px"><a class="sp-link" id="spTopExecAll" href="#">View all executives →</a></div>
                </div>
                <div class="sp-card">
                    <div class="sp-card-head" style="margin-bottom:4px"><h3 id="spTeamAttnTitle">Needs Attention</h3><span class="sp-count-chip" id="spTeamAttnCount">0</span></div>
                    <div id="spTeamAttn"></div>
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
                <div class="hint"><?= count(array_filter($ex, fn($e) => $e['follow_ups'] > 0)) ?> logged follow-ups</div>
            </div>
            <div class="sp-kpi">
                <div class="lbl"><i class="fa-regular fa-user-group"></i> Leads Received</div>
                <div class="val"><?= sp_n($totalExecLeads) ?></div>
                <div class="hint"><?= count($ex) ? round($totalExecLeads / max(1, count($ex))) : 0 ?> avg per executive</div>
            </div>
            <div class="sp-kpi">
                <div class="lbl"><i class="fa-regular fa-headset"></i> Follow-ups Logged</div>
                <div class="val"><?= sp_n($totalExecFu) ?></div>
                <div class="hint"><?= count($ex) ? round($totalExecFu / max(1, count($ex))) : 0 ?> per executive</div>
            </div>
            <div class="sp-kpi">
                <div class="lbl"><i class="fa-regular fa-location-dot"></i> Site Visits Done</div>
                <div class="val"><?= sp_n($totalExecVisits) ?></div>
                <div class="hint"><?= sp_n(array_sum(array_column($ex, 'upcoming_visits'))) ?> upcoming in 7d</div>
            </div>
            <div class="sp-kpi">
                <div class="lbl"><i class="fa-regular fa-calendar-check"></i> Bookings</div>
                <div class="val"><?= sp_n($totalExecBookings) ?></div>
                <div class="hint"><?= sp_tk($totalExecValue) ?> booked value</div>
            </div>
            <div class="sp-kpi">
                <div class="lbl"><i class="fa-regular fa-sack-dollar"></i> Collection</div>
                <div class="val"><?= sp_tk($totalExecCollection) ?></div>
                <div class="hint"><?= sp_n($totalExecConfirmed) ?> confirmed sales</div>
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
                </table>
            </div>

            <div class="sp-table-foot">
                <span id="spExecCount"></span>
                <span>Click any card or table row for full activity details &amp; individual sales funnel.</span>
            </div>
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
        <div class="sp-banner" style="background:#fef6ee;border-color:#fbd5b5">
            <div class="ic" style="color:var(--sp-amber)"><i class="fa-regular fa-user-slash"></i></div>
            <div>
                <div class="t" style="color:var(--sp-amber)">Inactive &amp; Resigned Personnel Records</div>
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
            <dt>Visit scheduled / completed</dt><dd>Follow-up next action “site visit” or status Will Visit → scheduled. Status Visited or follow-up type Visit / Face to Face → completed.</dd>
            <dt>Negotiation</dt><dd>A follow-up has next action “booking”.</dd>
            <dt>Funnel stages</dt><dd>Each stage counts leads that reached it or any later stage, so the funnel never increases. Booking / confirmed in the funnel = leads from this period that are booked as of today.</dd>
            <dt>Bookings (period)</dt><dd>Active bookings (booked / completed) with booking date in the period. Credited to “Sales by”, or to the lead owner if empty.</dd>
            <dt>Confirmed sale</dt><dd>A booking with at least one payment verified by accounts.</dd>
            <dt>Collection</dt><dd>Verified, paid / partially-paid payments with payment date in the period (trashed payments excluded).</dd>
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
    <div class="hd">
        <div><h3 id="spDrawerTitle" style="font-size:17px;font-weight:700;margin:0"></h3><div class="sp-sub" id="spDrawerSub" style="font-size:12px;color:var(--sp-muted);margin-top:3px"></div></div>
        <button type="button" class="sp-x" id="spDrawerClose" aria-label="Close"><i class="fa-regular fa-xmark"></i></button>
    </div>
    <div class="bd" id="spDrawerBody"></div>
</aside>

<script>
    window.SP_DATA = <?= json_encode($jsData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>;
</script>
<script src="<?= $GLOBALS['base_url'] ?>/assets/js/sales-performance.js?v=<?= @filemtime(BASE_PATH . '/public/assets/js/sales-performance.js') ?: time() ?>"></script>

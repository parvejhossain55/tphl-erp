<?php
$base            = $GLOBALS['base_url'];
$target          = $target          ?? [];
$weeklyProgress  = $weeklyProgress  ?? [];
$weeklyItems     = $weeklyItems     ?? [];
$selectedDate    = $selectedDate    ?? date('Y-m-d');
$dailyLog        = $dailyLog        ?? [];
$dailyQuantities = $dailyQuantities ?? [];
$weeklyLogsSummary= $weeklyLogsSummary ?? [];
$project         = $project         ?? [];
$weekNo          = $weekNo          ?? 1;
$workTypes       = $workTypes       ?? [];

$target_id  = (int)($target['id']         ?? 0);
$project_id = (int)($target['project_id'] ?? 0);
$phase_id   = (int)($target['phase_id']   ?? 0);

$dt_obj     = new DateTime($target['target_month'] ?? 'now');
$monthLabel = $dt_obj->format('F Y');

$isLocked    = ($weeklyProgress['entry_status'] ?? 'Draft') !== 'Draft';
$entryStatus = $weeklyProgress['entry_status'] ?? 'Draft';
$disAttr     = $isLocked ? 'disabled' : '';

// Build weekly items map
$weeklyItemsMap = [];
foreach ($weeklyItems as $wi) {
    $weeklyItemsMap[(int)$wi['target_item_id']] = $wi;
}

// Daily quantities map: target_item_id → row(s)
$dqByItem = [];
foreach ($dailyQuantities as $dq) {
    $iid = (int)$dq['target_item_id'];
    $dqByItem[$iid][] = $dq;
}

// Calendar days
$start_dt = new DateTime($weeklyProgress['week_start_date'] ?? 'now');
$end_dt   = new DateTime($weeklyProgress['week_end_date']   ?? 'now');
$daysList = [];
$tmp = clone $start_dt;
while ($tmp <= $end_dt) { $daysList[] = $tmp->format('Y-m-d'); $tmp->modify('+1 day'); }

// Summary by date
$summaryByDate = [];
foreach ($weeklyLogsSummary as $ls) { $summaryByDate[$ls['log_date']] = $ls; }

// KPI totals
$totalPlanned  = 0; $totalAchieved = 0; $completedItems = 0; $inProgressItems = 0; $delayedItems = 0;
foreach ($weeklyItems as $wi) {
    $totalPlanned  += (float)$wi['planned_qty'];
    $totalAchieved += (float)$wi['achieved_qty'];
    $pct = (float)$wi['percent_complete'];
    if ($pct >= 100) $completedItems++;
    elseif ($pct > 0) $inProgressItems++;
}
$overallWeekPct = $totalPlanned > 0 ? min(100, ($totalAchieved / $totalPlanned) * 100) : 0;
$updatedToday   = count($dqByItem);
$totalItems     = count($weeklyItems);

// Issue items (delayed or pending QA from daily qty status)
$issueItems = [];
foreach ($dailyQuantities as $dq) {
    $st = $dq['item_status'] ?? '';
    if (in_array($st, ['Delayed','Pending QA'])) {
        $issueItems[] = $dq;
    }
}

// Entry status meta
$statusMeta = [
    'Draft'     => ['label'=>'Draft',     'color'=>'#64748b','bg'=>'#f1f5f9','icon'=>'bi-pencil-square'],
    'Submitted' => ['label'=>'Submitted', 'color'=>'var(--clr-theme-primary)','bg'=>'rgba(var(--clr-theme-primary-rgb),.08)','icon'=>'bi-send-fill'],
    'Approved'  => ['label'=>'Approved',  'color'=>'#10b981','bg'=>'#d1fae5','icon'=>'bi-check-circle-fill'],
];
$asm = $statusMeta[$entryStatus] ?? $statusMeta['Draft'];

// Phase weight for rollup display
$phaseWeight = (float)($target['phase_weight'] ?? 35);
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
body { font-family: 'Inter', sans-serif; }
:root {
    --pri:var(--clr-theme-primary); --green:#10b981; --amber:#f59e0b; --red:#ef4444;
    --cyan:#0ea5e9; --purple:var(--clr-theme-secondary); --slate:#64748b;
}

/* ---------- KPI Cards ---------- */
.wp-kpi-grid { display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:22px }
.wp-kpi { background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:14px 18px;display:flex;align-items:center;gap:12px }
.wp-kpi-icon { width:44px;height:44px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:19px;flex-shrink:0 }
.wp-kpi-label { font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--slate);margin-bottom:2px }
.wp-kpi-val { font-size:17px;font-weight:800;line-height:1 }
.wp-kpi-sub { font-size:10.5px;color:var(--slate);margin-top:2px }

/* ---------- Main Card ---------- */
.wp-card { background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;margin-bottom:20px }
.wp-card-header { padding:14px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between }
.wp-card-title { font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#475569;display:flex;align-items:center;gap:8px }

/* ---------- Progress Table ---------- */
.wp-table { width:100%;border-collapse:collapse;font-size:12.5px }
.wp-table th { font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#475569;background:#f8fafc;padding:9px 10px;border-bottom:2px solid #e2e8f0;white-space:nowrap }
.wp-table td { padding:9px 10px;border-bottom:1px solid #f8fafc;vertical-align:middle;color:#1e293b }
.wp-table tr:last-child td { border-bottom:none }
.wp-table tr:hover td { background:#f8fafc }

.pct-bar { height:6px;border-radius:3px;background:#e2e8f0;overflow:hidden;margin-top:3px;min-width:60px }
.pct-fill { height:100%;border-radius:3px }

.status-badge { display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:700;padding:3px 9px;border-radius:999px;white-space:nowrap }
.s-completed { background:#d1fae5;color:#065f46 }
.s-in-progress { background:#dbeafe;color:#1e40af }
.s-delayed { background:#fee2e2;color:#991b1b }
.s-pending-qa { background:#fef3c7;color:#92400e }
.s-not-started { background:#f1f5f9;color:#64748b }

.qty-input { width:85px;padding:5px 8px;border:1px solid #cbd5e1;border-radius:7px;font-size:12px;font-weight:600;text-align:center;background:#fff;color:#1e293b }
.qty-input:focus { outline:none;border-color:var(--pri);box-shadow:0 0 0 2px rgba(var(--clr-theme-primary-rgb),.15) }
.qty-input:disabled { background:#f8fafc;color:#94a3b8 }

.remarks-input { width:100%;padding:5px 8px;border:1px solid #e2e8f0;border-radius:7px;font-size:11.5px;background:#fff;color:#1e293b }
.remarks-input:focus { outline:none;border-color:var(--pri) }

.photo-btn { width:30px;height:30px;border-radius:7px;border:1px dashed #94a3b8;background:transparent;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#94a3b8;font-size:14px }
.photo-btn:hover { border-color:var(--pri);color:var(--pri) }
.photo-btn.has-photo { border-color:var(--green);color:var(--green);background:rgba(16,185,129,.08);border-style:solid }

.issue-flag { width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0;cursor:pointer }
.issue-none { background:#f1f5f9;color:#94a3b8 }
.issue-delay { background:#fee2e2;color:#dc2626 }
.issue-low-labor { background:#fef3c7;color:#d97706 }
.issue-pending { background:#dbeafe;color:#3b82f6 }

/* ---------- Total Row ---------- */
.wp-total-row { background:linear-gradient(135deg,#f0f9ff,#e0f2fe);border-top:2px solid #bae6fd;font-weight:700 }

/* ---------- Weekly Gantt Visual ---------- */
.gantt-wrap { overflow-x:auto }
.gantt-table { width:100%;border-collapse:collapse;font-size:11.5px;min-width:700px }
.gantt-table th { font-size:9px;font-weight:700;text-transform:uppercase;color:#64748b;padding:7px 6px;border-bottom:1px solid #e2e8f0;text-align:center }
.gantt-table td { padding:5px 6px;border-bottom:1px solid #f8fafc;vertical-align:middle }
.gantt-item-name { font-weight:600;white-space:nowrap;max-width:160px;overflow:hidden;text-overflow:ellipsis }
.gantt-bar-cell { text-align:center }
.gantt-bar { height:12px;border-radius:4px;margin:0 auto;min-width:4px }
.gantt-bar-completed { background:var(--green) }
.gantt-bar-in-progress { background:var(--pri) }
.gantt-bar-delayed { background:var(--red) }
.gantt-bar-pending { background:#94a3b8 }
.gantt-bar-none { background:#e2e8f0 }
.gantt-legend { display:flex;gap:14px;flex-wrap:wrap;padding:10px 16px;background:#f8fafc;border-top:1px solid #e2e8f0;font-size:11px;font-weight:600 }
.gantt-legend-item { display:flex;align-items:center;gap:5px }
.gantt-dot { width:10px;height:10px;border-radius:3px }

/* ---------- Phase Progress Card ---------- */
.phase-rollup { background:linear-gradient(135deg,var(--clr-theme-primary),var(--clr-theme-secondary));border-radius:14px;padding:18px 22px;color:#fff;margin-bottom:20px }
.phase-rollup h6 { font-size:11px;font-weight:700;opacity:.75;text-transform:uppercase;margin-bottom:6px }
.phase-rollup .phase-val { font-size:28px;font-weight:900 }
.phase-prog-bar { height:8px;border-radius:4px;background:rgba(255,255,255,.25);overflow:hidden;margin-top:8px }
.phase-prog-fill { height:100%;border-radius:4px;background:rgba(255,255,255,.9) }

/* ---------- Site Inputs Card ---------- */
.site-input-grid { display:grid;grid-template-columns:1fr 1fr;gap:10px }
.site-inp-group { display:flex;flex-direction:column;gap:3px }
.site-inp-label { font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#94a3b8 }
.site-inp-val { font-size:13px;font-weight:700;color:#1e293b }
.site-field { width:100%;padding:6px 9px;border:1px solid #e2e8f0;border-radius:8px;font-size:12px;background:#fff;color:#1e293b }
.site-field:focus { outline:none;border-color:var(--pri) }

/* ---------- Issue Tracker ---------- */
.issue-item { display:flex;align-items:center;gap:8px;padding:7px 0;border-bottom:1px solid #f1f5f9;font-size:12px }
.issue-dot { width:8px;height:8px;border-radius:50%;flex-shrink:0 }

/* ---------- Donut Chart ---------- */
.donut-wrap { position:relative;display:inline-flex;align-items:center;justify-content:center }
.donut-center { position:absolute;text-align:center }
.donut-val { font-size:22px;font-weight:800;color:#1e293b }
.donut-lbl { font-size:9px;font-weight:700;color:#94a3b8;text-transform:uppercase }

/* ---------- Day Calendar ---------- */
.day-bar { display:flex;gap:8px;overflow-x:auto;padding-bottom:6px;margin-bottom:16px }
.day-chip { border:1px solid #e2e8f0;border-radius:10px;padding:8px 12px;text-align:center;cursor:pointer;text-decoration:none;color:#1e293b;min-width:70px;position:relative;flex-shrink:0;transition:all .15s }
.day-chip:hover { border-color:var(--pri);transform:translateY(-1px) }
.day-chip.active { background:var(--pri);color:#fff;border-color:var(--pri);box-shadow:0 4px 12px rgba(var(--clr-theme-primary-rgb),.3) }
.day-chip.has-log { border-top:3px solid var(--green) }
.day-chip .day-name { font-size:9px;font-weight:700;text-transform:uppercase;opacity:.6 }
.day-chip .day-num { font-size:18px;font-weight:800;line-height:1;margin:2px 0 }
.day-chip .day-month { font-size:9px;font-weight:600 }

/* ---------- Buttons ---------- */
.btn-save-draft { background:#f8fafc;border:1px solid #cbd5e1;color:#475569;font-weight:700;font-size:12.5px;padding:8px 20px;border-radius:9px;cursor:pointer }
.btn-save-draft:hover { background:#f1f5f9 }
.btn-submit { background:var(--red);color:#fff;font-weight:700;font-size:12.5px;padding:8px 22px;border-radius:9px;border:none;cursor:pointer }
.btn-submit:hover { background:#dc2626 }
.btn-save { background:var(--pri);color:#fff;font-weight:700;font-size:12.5px;padding:9px 22px;border-radius:9px;border:none;cursor:pointer }
.btn-save:hover { background:var(--clr-theme-primary) }
.btn-add-item { background:transparent;border:1.5px dashed #94a3b8;color:#64748b;font-size:12px;font-weight:600;padding:7px 18px;border-radius:8px;cursor:pointer }
.btn-add-item:hover { border-color:var(--pri);color:var(--pri) }

.team-inp { width:100%;padding:4px 7px;border:1px solid #e2e8f0;border-radius:6px;font-size:11.5px;background:#fff;color:#1e293b }
.team-inp:focus { outline:none;border-color:var(--pri) }
.st-sel { width:100%;padding:4px 6px;border:1px solid #e2e8f0;border-radius:6px;font-size:11px;background:#fff;color:#1e293b }
.st-sel:focus { outline:none;border-color:var(--pri) }
</style>

<?php
// Derive phase actual progress from weekly items
// weighted average: sum(achieved_qty / monthly_target_qty * weight_pct) for items with monthly_target_qty > 0
$phaseActual = 0;
$weightSum = 0;
foreach ($weeklyItems as $wi) {
    $mt = (float)($wi['monthly_target_qty'] ?? 0);
    $ach = (float)($wi['achieved_qty'] ?? 0);
    $wp2 = (float)($wi['sub_item_weight'] ?? 0);
    if ($mt > 0 && $wp2 > 0) {
        $phaseActual += ($ach / $mt) * $wp2;
        $weightSum   += $wp2;
    }
}
$phaseActualPct = ($weightSum > 0) ? min(100, $phaseActual) : 0;
$projectContrib = $phaseActualPct * ($phaseWeight / 100);
?>

<div class="px-2 py-1">

<!-- ░░ Header ░░ -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-3 gap-3">
    <div>
        <h4 class="fw-extrabold mb-1" style="font-size:20px">Daily / Weekly Progress Entry
            <span class="ms-2" style="font-size:12px;font-weight:500;color:#64748b;vertical-align:middle">
                <i class="bi bi-info-circle"></i>
                Capture daily and weekly on-site progress, quantities, issues, and proof updates.
            </span>
        </h4>
        <div style="font-size:11.5px;color:#64748b">
            <a href="<?= $base ?>/con-projects" class="text-decoration-none" style="color:#94a3b8">Projects</a> ›
            <a href="<?= $base ?>/con-monthly-target/<?= $target_id ?>" class="text-decoration-none" style="color:#64748b"><?= htmlspecialchars($target['phase_name'] ?? '') ?> — <?= $monthLabel ?></a>
        </div>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <a href="<?= $base ?>/con-monthly-target/<?= $target_id ?>" class="btn btn-sm btn-outline-secondary fw-bold px-3">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <?php if (!$isLocked): ?>
        <button type="button" class="btn-submit" data-bs-toggle="modal" data-bs-target="#submitModal">
            <i class="bi bi-send-fill me-1"></i> Submit Weekly Update
        </button>
        <?php else: ?>
        <button class="btn-submit" disabled style="opacity:.5">
            <i class="bi bi-lock-fill me-1"></i> Submitted
        </button>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $_SESSION['error']; unset($_SESSION['error']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i><?= $_SESSION['success']; unset($_SESSION['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ░░ Week Selector ░░ -->
<div class="wp-card mb-3" style="border-radius:12px">
    <div style="padding:12px 18px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
        <div class="d-flex align-items-center gap-2">
            <span style="font-size:10.5px;font-weight:700;color:#94a3b8;text-transform:uppercase">Work Week:</span>
            <div class="btn-group">
            <?php for ($w = 1; $w <= 4; $w++): ?>
                <a href="<?= $base ?>/con-weekly-progress/<?= $target_id ?>?week=<?= $w ?>"
                   class="btn btn-sm <?= ($w===$weekNo)?'btn-primary fw-bold':'btn-outline-secondary' ?>"
                   style="font-size:12px">Week <?= $w ?></a>
            <?php endfor; ?>
            </div>
        </div>
        <div class="d-flex gap-4" style="font-size:12.5px">
            <div><span style="color:#94a3b8">Duration:</span>
                 <strong><?= date('d M',strtotime($weeklyProgress['week_start_date']??'now')) ?> – <?= date('d M Y',strtotime($weeklyProgress['week_end_date']??'now')) ?></strong></div>
            <div><span style="color:#94a3b8">Status:</span>
                 <span style="background:<?= $asm['bg'] ?>;color:<?= $asm['color'] ?>;font-size:10.5px;font-weight:700;padding:2px 10px;border-radius:999px">
                     <i class="bi <?= $asm['icon'] ?> me-1"></i><?= $entryStatus ?>
                 </span></div>
        </div>
    </div>
</div>

<!-- ░░ KPI Row ░░ -->
<div class="wp-kpi-grid">
    <!-- Selected Week -->
    <div class="wp-kpi">
        <div class="wp-kpi-icon" style="background:rgba(var(--clr-theme-primary-rgb),.08);color:var(--purple)"><i class="bi bi-calendar-week-fill"></i></div>
        <div>
            <div class="wp-kpi-label">Selected Week</div>
            <div class="wp-kpi-val">Week <?= $weekNo ?></div>
            <div class="wp-kpi-sub"><?= date('d M',strtotime($weeklyProgress['week_start_date']??'now')) ?> – <?= date('d M Y',strtotime($weeklyProgress['week_end_date']??'now')) ?></div>
        </div>
    </div>
    <!-- Active Phase -->
    <div class="wp-kpi">
        <div class="wp-kpi-icon" style="background:#dbeafe;color:var(--cyan)"><i class="bi bi-diagram-3-fill"></i></div>
        <div>
            <div class="wp-kpi-label">Active Phase</div>
            <div class="wp-kpi-val" style="font-size:13px"><?= htmlspecialchars($target['phase_name'] ?? '') ?></div>
            <div class="wp-kpi-sub"><?= $monthLabel ?> Targets</div>
        </div>
    </div>
    <!-- Work Items Updated -->
    <div class="wp-kpi">
        <div class="wp-kpi-icon" style="background:#d1fae5;color:var(--green)"><i class="bi bi-journal-check"></i></div>
        <div>
            <div class="wp-kpi-label">Work Items Updated</div>
            <div class="wp-kpi-val"><?= $updatedToday ?></div>
            <div class="wp-kpi-sub">of <?= $totalItems ?> Planned Items</div>
        </div>
    </div>
    <!-- Completed -->
    <div class="wp-kpi">
        <div class="wp-kpi-icon" style="background:#d1fae5;color:var(--green)"><i class="bi bi-check2-circle"></i></div>
        <div>
            <div class="wp-kpi-label">Completed</div>
            <div class="wp-kpi-val"><?= $completedItems ?></div>
            <div class="wp-kpi-sub">Work Items (100%)</div>
        </div>
    </div>
    <!-- Entry Status -->
    <div class="wp-kpi">
        <div class="wp-kpi-icon" style="background:<?= $asm['bg'] ?>;color:<?= $asm['color'] ?>"><i class="bi <?= $asm['icon'] ?>"></i></div>
        <div>
            <div class="wp-kpi-label">Entry Status</div>
            <div class="wp-kpi-val" style="font-size:14px"><?= $entryStatus ?></div>
            <div class="wp-kpi-sub"><?= number_format($overallWeekPct,0) ?>% Data Entered</div>
        </div>
    </div>
</div>

<!-- ░░ Date Calendar ░░ -->
<div class="wp-card" style="margin-bottom:18px">
    <div class="wp-card-header">
        <div class="wp-card-title"><i class="bi bi-calendar3"></i> Weekly Calendar Logs</div>
        <div style="font-size:11.5px;color:#64748b">Tap a date to log quantities for that day</div>
    </div>
    <div style="padding:12px 16px">
        <div class="day-bar">
        <?php foreach ($daysList as $dt):
            $isA = ($dt === $selectedDate);
            $hasL = isset($summaryByDate[$dt]);
            $cls  = $isA ? 'active' : '';
            if ($hasL && !$isA) $cls .= ' has-log';
        ?>
            <a href="<?= $base ?>/con-weekly-progress/<?= $target_id ?>?week=<?= $weekNo ?>&log_date=<?= $dt ?>"
               class="day-chip <?= $cls ?>">
                <div class="day-name"><?= date('D',$t=strtotime($dt)) ?></div>
                <div class="day-num"><?= date('d',$t) ?></div>
                <div class="day-month"><?= date('M',$t) ?></div>
                <?php if ($hasL): ?>
                <span class="position-absolute top-0 end-0 translate-middle badge rounded-pill bg-success" style="font-size:7px;padding:2px 4px"><i class="bi bi-check"></i></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ░░ MAIN LAYOUT: 8/4 ░░ -->
<form method="POST" action="<?= $base ?>/con-weekly-progress-save" enctype="multipart/form-data">
<input type="hidden" name="weekly_progress_id" value="<?= (int)($weeklyProgress['id']??0) ?>">
<input type="hidden" name="target_id" value="<?= $target_id ?>">
<input type="hidden" name="week_number" value="<?= $weekNo ?>">
<input type="hidden" name="project_id" value="<?= $project_id ?>">
<input type="hidden" name="log_date" value="<?= htmlspecialchars($selectedDate) ?>">

<div class="row ">
<div class="col-lg-8">

<?php if ($entryStatus !== 'Draft'): ?>
<!-- ░░ Quality Control & Manager Review Panel ░░ -->
<div class="wp-card mb-4" style="border-radius:16px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05); background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);">
    <div class="wp-card-header" style="background: rgba(248, 250, 252, 0.5); border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
        <div class="wp-card-title" style="font-size: 13px; font-weight: 800; color: #1e293b; text-transform: uppercase;">
            <i class="bi bi-shield-check text-primary fs-5"></i>
            Quality Control &amp; Management Review
        </div>
        <div>
            <span style="font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 20px; 
                background: <?= $entryStatus === 'Approved' ? 'rgba(16, 185, 129, 0.12)' : 'rgba(var(--clr-theme-primary-rgb), 0.12)' ?>; 
                color: <?= $entryStatus === 'Approved' ? '#10b981' : 'var(--clr-theme-primary)' ?>;">
                <i class="bi <?= $entryStatus === 'Approved' ? 'bi-check-circle-fill' : 'bi-clock-history' ?> me-1"></i>
                Report Status: <?= $entryStatus ?>
            </span>
        </div>
    </div>
    
    <div style="padding: 24px;">
        <div class="row g-4">
            
            <!-- Quality Control (QC) Section -->
            <div class="col-md-6" style="border-right: 1px dashed #e2e8f0;">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span style="width: 28px; height: 28px; border-radius: 50%; background: rgba(var(--clr-theme-primary-rgb), 0.1); color: var(--pri); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">1</span>
                    <h6 class="mb-0 fw-bold" style="color: #334155; font-size: 14px;">Quality Control Review</h6>
                </div>
                
                <?php if (empty($dailyLog['qc_approved_id'])): ?>
                    <!-- QC Review Form (Visible only to role 13 [QC] or role 1 [Admin]) -->
                    <?php if (in_array((int)($_SESSION['user']['role_id'] ?? 0), [1, 13])): ?>
                        <div class="pe-2">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">QC Inspection Decision</label>
                                <select name="qc_status" form="qcForm" class="form-select form-select-sm" required style="border-radius: 8px; font-weight: 600;">
                                    <option value="Approved">Approved (Meets QA/QC standards)</option>
                                    <option value="Partially Approved">Partially Approved (Minor observations)</option>
                                    <option value="Rejected">Rejected (Requires correction)</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Verification Photo / Proof</label>
                                <input type="file" name="qc_image" form="qcForm" class="form-control form-control-sm" accept="image/*" style="border-radius: 8px; color: #334155; background-color: #d9cfcf; border: 1px solid #cbd5e1;">
                                <div class="form-text" style="font-size: 10px;">Upload site photo as quality assessment proof.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">QC Remarks / Comments</label>
                                <textarea name="qc_comment" form="qcForm" rows="3" class="form-control form-control-sm" placeholder="Provide quality check observations, notes on workmanship, materials, etc..." style="border-radius: 8px;"></textarea>
                            </div>
                            
                            <button type="submit" form="qcForm" class="btn btn-sm btn-primary w-100 fw-bold py-2" style="border-radius: 8px; background: var(--pri); border: none; box-shadow: 0 4px 12px rgba(var(--clr-theme-primary-rgb), 0.2);">
                                <i class="bi bi-shield-fill-check me-1"></i> Submit QC Review
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 rounded-3" style="background: rgba(241, 245, 249, 0.5); border-radius: 12px; border: 1px solid #e2e8f0;">
                            <i class="bi bi-hourglass-split text-muted fs-4"></i>
                            <p class="text-muted small mt-2 mb-0">Awaiting review from Quality Control team.</p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- QC Review Details Display -->
                    <?php
                        $qcStatus = $dailyLog['qc_status'] ?? 'Approved';
                        $qcStatusColors = [
                            'Approved' => ['bg' => 'rgba(16, 185, 129, 0.1)', 'text' => '#10b981', 'icon' => 'bi-check-circle-fill'],
                            'Partially Approved' => ['bg' => 'rgba(245, 158, 11, 0.1)', 'text' => '#f59e0b', 'icon' => 'bi-exclamation-triangle-fill'],
                            'Rejected' => ['bg' => 'rgba(239, 68, 68, 0.1)', 'text' => '#ef4444', 'icon' => 'bi-x-circle-fill']
                        ];
                        $qsm = $qcStatusColors[$qcStatus] ?? $qcStatusColors['Approved'];
                    ?>
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.01);">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase;">QC Specialist</div>
                                <div style="font-size: 14px; font-weight: 700; color: #1e293b;"><?= htmlspecialchars($qcName) ?></div>
                            </div>
                            <span style="font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; background: <?= $qsm['bg'] ?>; color: <?= $qsm['text'] ?>;">
                                <i class="bi <?= $qsm['icon'] ?> me-1"></i><?= $qcStatus ?>
                            </span>
                        </div>
                        
                        <div class="mb-3">
                            <div style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 4px;">Comments</div>
                            <div style="font-size: 12.5px; color: #334155; line-height: 1.5; background: #f8fafc; padding: 10px; border-radius: 8px; border-left: 3px solid <?= $qsm['text'] ?>;">
                                <?= nl2br(htmlspecialchars($dailyLog['qc_comment'] ?? 'No comments provided.')) ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($dailyLog['qc_image'])): ?>
                            <div>
                                <div style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 6px;">Inspection Proof</div>
                                <a href="<?= $base ?>/<?= htmlspecialchars($dailyLog['qc_image']) ?>" target="_blank" style="display: block; position: relative; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0;">
                                    <img src="<?= $base ?>/<?= htmlspecialchars($dailyLog['qc_image']) ?>" style="width: 100%; max-height: 150px; object-fit: cover;" alt="QC Verification Image">
                                    <div style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.5); color: #fff; font-size: 10px; text-align: center; padding: 4px; font-weight: 600;">
                                        <i class="bi bi-zoom-in me-1"></i> View Full Image
                                    </div>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Project Manager (PM) Section -->
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span style="width: 28px; height: 28px; border-radius: 50%; background: rgba(16, 185, 129, 0.1); color: var(--green); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">2</span>
                    <h6 class="mb-0 fw-bold" style="color: #334155; font-size: 14px;">Management Decision</h6>
                </div>
                
                <?php if (empty($dailyLog['qc_approved_id'])): ?>
                    <div class="text-center py-4 bg-light rounded-3" style="background: rgba(241, 245, 249, 0.5); border-radius: 12px; border: 1px solid #e2e8f0; height: calc(100% - 40px); display: flex; flex-direction: column; align-items: center; justify-content: center;">
                        <i class="bi bi-lock text-muted fs-4"></i>
                        <p class="text-muted small mt-2 mb-0 px-3">PM Approval will unlock after Quality Control verification has been submitted.</p>
                    </div>
                <?php else: ?>
                    <?php if (empty($dailyLog['pm_approved_id'])): ?>
                        <!-- PM Review Form (Visible only to role 15 [PM], 22 [PM Head], or 1 [Admin]) -->
                        <?php if (in_array((int)($_SESSION['user']['role_id'] ?? 0), [1, 15, 22])): ?>
                            <div class="ps-2">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted">PM Approval Decision</label>
                                    <select name="pm_status" form="pmForm" class="form-select form-select-sm" required style="border-radius: 8px; font-weight: 600;">
                                        <option value="Approved">Approved (Lock &amp; publish report)</option>
                                        <option value="Partially Approved">Partially Approved (Minor observations)</option>
                                        <option value="Rejected">Rejected (Revert to Draft for correction)</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted">Manager Remarks / Instructions</label>
                                    <textarea name="pm_comment" form="pmForm" rows="4" class="form-control form-control-sm" placeholder="Provide instructions, target feedback, adjustments needed, or reasons for rejection..." style="border-radius: 8px;"></textarea>
                                </div>
                                
                                <button type="submit" form="pmForm" class="btn btn-sm btn-success w-100 fw-bold py-2" style="border-radius: 8px; background: var(--green); border: none; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);">
                                    <i class="bi bi-check2-all me-1"></i> Submit Management Approval
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4 bg-light rounded-3" style="background: rgba(241, 245, 249, 0.5); border-radius: 12px; border: 1px solid #e2e8f0;">
                                <i class="bi bi-hourglass-split text-muted fs-4"></i>
                                <p class="text-muted small mt-2 mb-0">Awaiting Project Manager approval.</p>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- PM Review Details Display -->
                        <?php
                            $pmStatus = $dailyLog['pm_status'] ?? 'Approved';
                            $pmStatusColors = [
                                'Approved' => ['bg' => 'rgba(16, 185, 129, 0.1)', 'text' => '#10b981', 'icon' => 'bi-check-circle-fill'],
                                'Partially Approved' => ['bg' => 'rgba(245, 158, 11, 0.1)', 'text' => '#f59e0b', 'icon' => 'bi-exclamation-triangle-fill'],
                                'Rejected' => ['bg' => 'rgba(239, 68, 68, 0.1)', 'text' => '#ef4444', 'icon' => 'bi-x-circle-fill']
                            ];
                            $psm = $pmStatusColors[$pmStatus] ?? $pmStatusColors['Approved'];
                        ?>
                        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.01);">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <div style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Project Manager</div>
                                    <div style="font-size: 14px; font-weight: 700; color: #1e293b;"><?= htmlspecialchars($pmName) ?></div>
                                </div>
                                <span style="font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; background: <?= $psm['bg'] ?>; color: <?= $psm['text'] ?>;">
                                    <i class="bi <?= $psm['icon'] ?> me-1"></i><?= $pmStatus ?>
                                </span>
                            </div>
                            
                            <div>
                                <div style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 4px;">Comments</div>
                                <div style="font-size: 12.5px; color: #334155; line-height: 1.5; background: #f8fafc; padding: 10px; border-radius: 8px; border-left: 3px solid <?= $psm['text'] ?>;">
                                    <?= nl2br(htmlspecialchars($dailyLog['pm_comment'] ?? 'No comments provided.')) ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ░░ Progress Entry Table ░░ -->
<div class="wp-card">
    <div class="wp-card-header" style="background:#f8fafc">
        <div class="wp-card-title">
            <i class="bi bi-journal-check text-primary"></i>
            Progress Entry Table
            <span style="font-size:10px;font-weight:500;color:#94a3b8"><?= date('d M Y',strtotime($selectedDate)) ?></span>
        </div>
        <div class="d-flex gap-2">
            <?php if (!$isLocked): ?>
            <button type="submit" name="save_action" value="draft" class="btn-save-draft">
                <i class="bi bi-save me-1"></i>Save Draft
            </button>
            <button type="submit" name="save_action" value="save" class="btn-save">
                <i class="bi bi-check-circle me-1"></i>Save Progress
            </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($isLocked): ?>
    <div class="px-4 pt-2"><div class="alert alert-info border-0 py-2 small"><i class="bi bi-lock-fill me-1"></i>This week's report is locked. View only.</div></div>
    <?php endif; ?>

    <div style="overflow-x:auto">
    <table class="wp-table">
        <thead>
            <tr>
                <th style="width:30px">#</th>
                <th style="min-width:160px">Work Item</th>
                <th style="width:50px">Unit</th>
                <th class="text-center" style="width:80px">Planned Qty</th>
                <th class="text-center" style="width:80px">Achieved Qty</th>
                <th class="text-center" style="width:80px">% Complete</th>
                <th class="text-center" style="width:40px" title="Day 1">D1</th>
                <th class="text-center" style="width:40px" title="Day 2">D2</th>
                <th class="text-center" style="width:40px" title="Day 3">D3</th>
                <th class="text-center" style="width:40px" title="Day 4">D4</th>
                <th class="text-center" style="width:40px" title="Day 5">D5</th>
                <th class="text-center" style="width:40px" title="Day 6">D6</th>
                <th class="text-center" style="width:40px" title="Day 7">D7</th>
                <th class="text-center" style="width:100px" title="Enter qty done today">Today's Progress</th>
                <th style="min-width:110px">Responsible Team</th>
                <th class="text-center" style="width:100px">Status</th>
                <th class="text-center" style="width:40px" title="Issue Flag">⚑</th>
                <th class="text-center" style="width:40px" title="Site Photo">📷</th>
                <th style="min-width:120px">Remarks / Action</th>
            </tr>
        </thead>
        <tbody id="progressTableBody">
        <?php if (empty($weeklyItems)): ?>
            <tr><td colspan="19" class="text-center py-5" style="color:#94a3b8">
                <i class="bi bi-inbox" style="font-size:30px;display:block;margin-bottom:8px"></i>
                No work items planned for this week. Add items in the Monthly Target page.
            </td></tr>
        <?php else: ?>
        <?php 
        $formIndex = 0;
        foreach ($weeklyItems as $wi):
            $itemId     = (int)$wi['target_item_id'];
            $itemName   = htmlspecialchars($wi['work_item_name']);
            $unit       = htmlspecialchars($wi['unit'] ?? '');
            $planned    = (float)$wi['planned_qty'];
            $achieved   = (float)$wi['achieved_qty'];
            $pct        = $planned > 0 ? min(100, ($achieved / $planned) * 100) : 0;
            $monthlyQty = (float)($wi['monthly_target_qty'] ?? 0);

            // Fetch daily logs/quantities for this item on the selected date
            $itemLogs = $dqByItem[$itemId] ?? [];

            // Color for weekly progress pct bar
            if ($pct >= 100) { 
                $barColor = 'var(--green)'; 
            } elseif ($pct > 0) { 
                $barColor = 'var(--pri)'; 
            } else { 
                $barColor = '#cbd5e1'; 
            }

            if (empty($itemLogs)):
                if ($planned <= 0) {
                    continue; // Skip rendering if not planned for this week and has no daily log today
                }
                // Render a default empty row for this planned weekly item
                $todayQty    = 0;
                $todayRem    = '';
                $todayWt     = '';
                $todayStatus = $wi['status'] ?? 'Not Started';
                $todayTeam   = $wi['responsible_team'] ?? '';

                $stClass = 's-not-started';
                $stLabel = 'Not Started';
                $flagClass = 'issue-none';
                $flagIcon = 'bi-flag';
        ?>
        <tr data-item-id="<?= $itemId ?>">
            <td style="color:#94a3b8;font-weight:700"><?= $formIndex + 1 ?></td>
            <td>
                <div style="font-weight:600"><?= $itemName ?></div>
                <?php if ($monthlyQty > 0): ?>
                <div style="font-size:10px;color:#94a3b8">Monthly: <?= number_format($monthlyQty,0,'.',',') ?> <?= $unit ?></div>
                <?php endif; ?>
                <?php if (!empty($wi['contractor_name'])): ?>
                <div style="font-size:11px;color:#64748b;font-weight:500;margin-top:2px">
                    <i class="bi bi-briefcase me-1"></i><?= htmlspecialchars($wi['contractor_name']) ?>
                </div>
                <?php endif; ?>
                <input type="hidden" name="items[<?= $formIndex ?>][target_item_id]" value="<?= $itemId ?>">
                <input type="hidden" name="items[<?= $formIndex ?>][work_type_id]" value="">
            </td>
            <td><span style="font-size:10.5px;font-weight:600;background:#f1f5f9;padding:2px 7px;border-radius:5px"><?= $unit ?></span></td>
            <td class="text-center" style="font-weight:700"><?= number_format($planned,0,'.',',') ?></td>
            <td class="text-center" style="font-weight:700;color:var(--pri)"><?= number_format($achieved,0,'.',',') ?></td>
            <td class="text-center">
                <div style="font-weight:800;font-size:13px;color:<?= $pct>=100?'var(--green)':($pct>0?'var(--pri)':'#94a3b8') ?>"><?= number_format($pct,1) ?>%</div>
                <div class="pct-bar"><div class="pct-fill" style="width:<?= $pct ?>%;background:<?= $barColor ?>"></div></div>
            </td>
            <td class="text-center text-muted" style="font-size:11px"><?= (float)($wi['1st_day_qty'] ?? 0) > 0 ? number_format($wi['1st_day_qty'],1) : '—' ?></td>
            <td class="text-center text-muted" style="font-size:11px"><?= (float)($wi['2nd_day_qty'] ?? 0) > 0 ? number_format($wi['2nd_day_qty'],1) : '—' ?></td>
            <td class="text-center text-muted" style="font-size:11px"><?= (float)($wi['3rd_day_qty'] ?? 0) > 0 ? number_format($wi['3rd_day_qty'],1) : '—' ?></td>
            <td class="text-center text-muted" style="font-size:11px"><?= (float)($wi['4th_day_qty'] ?? 0) > 0 ? number_format($wi['4th_day_qty'],1) : '—' ?></td>
            <td class="text-center text-muted" style="font-size:11px"><?= (float)($wi['5th_day_qty'] ?? 0) > 0 ? number_format($wi['5th_day_qty'],1) : '—' ?></td>
            <td class="text-center text-muted" style="font-size:11px"><?= (float)($wi['6_th_day_qty'] ?? 0) > 0 ? number_format($wi['6_th_day_qty'],1) : '—' ?></td>
            <td class="text-center text-muted" style="font-size:11px"><?= (float)($wi['7th_day_qty'] ?? 0) > 0 ? number_format($wi['7th_day_qty'],1) : '—' ?></td>
            <td class="text-center">
                <input type="number" name="items[<?= $formIndex ?>][qty_done_today]"
                       value="<?= number_format($todayQty,2,'.','') ?>"
                       step="0.01" min="0"
                       class="qty-input" <?= $disAttr ?>
                       placeholder="0.00"
                       title="Enter qty done today">
                <input type="hidden" name="items[<?= $formIndex ?>][unit]" value="<?= $unit ?>">
            </td>
            <td>
                <input type="text" name="items[<?= $formIndex ?>][responsible_team]"
                       value="<?= htmlspecialchars($todayTeam) ?>"
                       class="team-inp" <?= $disAttr ?>
                       placeholder="Team name">
            </td>
            <td class="text-center">
                <select name="items[<?= $formIndex ?>][item_status]" class="st-sel" <?= $disAttr ?>>
                    <?php foreach (['Not Started','In Progress','Completed','Delayed','Pending QA'] as $st): ?>
                    <option value="<?= $st ?>" <?= $todayStatus===$st?'selected':'' ?>><?= $st ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td class="text-center">
                <div class="issue-flag <?= $flagClass ?> d-inline-flex" title="Issue flag">
                    <i class="bi <?= $flagIcon ?>"></i>
                </div>
            </td>
            <td class="text-center">
                <div class="d-flex align-items-center justify-content-center gap-1">
                    <label class="photo-btn <?= !empty($wi['photo_path']) ? 'has-photo' : '' ?>" title="Upload site photo">
                        <i class="bi bi-camera-fill"></i>
                        <input type="file" name="photos[<?= $formIndex ?>]" accept="image/*" style="display:none" <?= $disAttr ?>>
                    </label>
                    <?php if (!empty($wi['photo_path'])): ?>
                    <a href="<?= $base ?>/<?= htmlspecialchars($wi['photo_path']) ?>" target="_blank" class="text-success" style="font-size:14px" title="View uploaded photo">
                        <i class="bi bi-image"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </td>
            <td>
                <input type="text" name="items[<?= $formIndex ?>][remarks]"
                       value="<?= htmlspecialchars($todayRem) ?>"
                       class="remarks-input" <?= $disAttr ?>
                       placeholder="Remarks...">
            </td>
        </tr>
        <?php 
            $formIndex++;
            else:
                // Render a row for each daily progress log entry
                foreach ($itemLogs as $dq):
                    $todayQty    = (float)$dq['qty_done_today'];
                    $todayRem    = $dq['remarks'] ?? '';
                    $todayWt     = $dq['work_type_id'] ?? '';
                    $todayStatus = $dq['item_status'] ?? 'In Progress';
                    $todayTeam   = $dq['responsible_team'] ?? $wi['responsible_team'] ?? '';

                    // Status style deriving
                    if ($todayStatus === 'Delayed') { 
                        $stClass='s-delayed'; $stLabel='Delayed'; $flagClass='issue-delay'; $flagIcon='bi-exclamation-circle-fill'; 
                    } elseif ($todayStatus === 'Pending QA') { 
                        $stClass='s-pending-qa'; $stLabel='Pending QA'; $flagClass='issue-pending'; $flagIcon='bi-question-circle-fill'; 
                    } elseif ($todayStatus === 'Completed') { 
                        $stClass='s-completed'; $stLabel='Completed'; $flagClass='issue-none'; $flagIcon='bi-flag';
                    } else { 
                        $stClass='s-in-progress'; $stLabel='In Progress'; $flagClass='issue-none'; $flagIcon='bi-flag';
                    }
        ?>
        <tr data-item-id="<?= $itemId ?>">
            <td style="color:#94a3b8;font-weight:700"><?= $formIndex + 1 ?></td>
            <td>
                <div style="font-weight:600"><?= $itemName ?></div>
                <?php if ($monthlyQty > 0): ?>
                <div style="font-size:10px;color:#94a3b8">Monthly: <?= number_format($monthlyQty,0,'.',',') ?> <?= $unit ?></div>
                <?php endif; ?>
                <?php if (!empty($wi['contractor_name'])): ?>
                <div style="font-size:11px;color:#64748b;font-weight:500;margin-top:2px">
                    <i class="bi bi-briefcase me-1"></i><?= htmlspecialchars($wi['contractor_name']) ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($dq['work_type_name'])): ?>
                <div style="font-size:11px;color:var(--pri);font-weight:600;margin-top:2px"><?= htmlspecialchars($dq['work_type_name']) ?></div>
                <?php endif; ?>
                <input type="hidden" name="items[<?= $formIndex ?>][target_item_id]" value="<?= $itemId ?>">
                <input type="hidden" name="items[<?= $formIndex ?>][work_type_id]" value="<?= $todayWt ?>">
            </td>
            <td><span style="font-size:10.5px;font-weight:600;background:#f1f5f9;padding:2px 7px;border-radius:5px"><?= $unit ?></span></td>
            <td class="text-center" style="font-weight:700"><?= number_format($planned,0,'.',',') ?></td>
            <td class="text-center" style="font-weight:700;color:var(--pri)"><?= number_format($achieved,0,'.',',') ?></td>
            <td class="text-center">
                <div style="font-weight:800;font-size:13px;color:<?= $pct>=100?'var(--green)':($pct>0?'var(--pri)':'#94a3b8') ?>"><?= number_format($pct,1) ?>%</div>
                <div class="pct-bar"><div class="pct-fill" style="width:<?= $pct ?>%;background:<?= $barColor ?>"></div></div>
            </td>
            <td class="text-center text-muted" style="font-size:11px"><?= (float)($wi['1st_day_qty'] ?? 0) > 0 ? number_format($wi['1st_day_qty'],1) : '—' ?></td>
            <td class="text-center text-muted" style="font-size:11px"><?= (float)($wi['2nd_day_qty'] ?? 0) > 0 ? number_format($wi['2nd_day_qty'],1) : '—' ?></td>
            <td class="text-center text-muted" style="font-size:11px"><?= (float)($wi['3rd_day_qty'] ?? 0) > 0 ? number_format($wi['3rd_day_qty'],1) : '—' ?></td>
            <td class="text-center text-muted" style="font-size:11px"><?= (float)($wi['4th_day_qty'] ?? 0) > 0 ? number_format($wi['4th_day_qty'],1) : '—' ?></td>
            <td class="text-center text-muted" style="font-size:11px"><?= (float)($wi['5th_day_qty'] ?? 0) > 0 ? number_format($wi['5th_day_qty'],1) : '—' ?></td>
            <td class="text-center text-muted" style="font-size:11px"><?= (float)($wi['6_th_day_qty'] ?? 0) > 0 ? number_format($wi['6_th_day_qty'],1) : '—' ?></td>
            <td class="text-center text-muted" style="font-size:11px"><?= (float)($wi['7th_day_qty'] ?? 0) > 0 ? number_format($wi['7th_day_qty'],1) : '—' ?></td>
            <td class="text-center">
                <input type="number" name="items[<?= $formIndex ?>][qty_done_today]"
                       value="<?= number_format($todayQty,2,'.','') ?>"
                       step="0.01" min="0"
                       class="qty-input" <?= $disAttr ?>
                       placeholder="0.00"
                       title="Enter qty done today">
                <input type="hidden" name="items[<?= $formIndex ?>][unit]" value="<?= $unit ?>">
            </td>
            <td>
                <input type="text" name="items[<?= $formIndex ?>][responsible_team]"
                       value="<?= htmlspecialchars($todayTeam) ?>"
                       class="team-inp" <?= $disAttr ?>
                       placeholder="Team name">
            </td>
            <td class="text-center">
                <select name="items[<?= $formIndex ?>][item_status]" class="st-sel" <?= $disAttr ?>>
                    <?php foreach (['Not Started','In Progress','Completed','Delayed','Pending QA'] as $st): ?>
                    <option value="<?= $st ?>" <?= $todayStatus===$st?'selected':'' ?>><?= $st ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td class="text-center">
                <div class="issue-flag <?= $flagClass ?> d-inline-flex" title="Issue flag">
                    <i class="bi <?= $flagIcon ?>"></i>
                </div>
            </td>
            <td class="text-center">
                <div class="d-flex align-items-center justify-content-center gap-1">
                    <label class="photo-btn <?= !empty($wi['photo_path']) ? 'has-photo' : '' ?>" title="Upload site photo">
                        <i class="bi bi-camera-fill"></i>
                        <input type="file" name="photos[<?= $formIndex ?>]" accept="image/*" style="display:none" <?= $disAttr ?>>
                    </label>
                    <?php if (!empty($wi['photo_path'])): ?>
                    <a href="<?= $base ?>/<?= htmlspecialchars($wi['photo_path']) ?>" target="_blank" class="text-success" style="font-size:14px" title="View uploaded photo">
                        <i class="bi bi-image"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <input type="text" name="items[<?= $formIndex ?>][remarks]"
                           value="<?= htmlspecialchars($todayRem) ?>"
                           class="remarks-input" <?= $disAttr ?>
                           placeholder="Remarks...">
                    <?php if (!$isLocked && isset($dq['log_row_id'])): ?>
                    <button type="button" class="btn btn-sm btn-link text-danger p-0 border-0"
                            onclick="confirmDelete(<?= (int)$dq['log_row_id'] ?>, '<?= htmlspecialchars(addslashes($itemName . (!empty($dq['work_type_name']) ? ' - ' . $dq['work_type_name'] : '')), ENT_QUOTES, 'UTF-8') ?>')"
                            title="Remove from schedule">
                        <i class="bi bi-trash-fill fs-5"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php 
                $formIndex++;
                endforeach;
            endif;
        endforeach;
        ?>
        <?php endif; ?>
        </tbody>

        <?php if (!empty($weeklyItems)): ?>
        <!-- Total Row -->
        <tfoot>
        <tr class="wp-total-row">
            <td colspan="3" style="font-weight:700;color:#1e40af">Totals</td>
            <td class="text-center" style="color:#1e40af"><?= number_format($totalPlanned,0,'.',',') ?></td>
            <td class="text-center" style="color:#1e40af"><?= number_format($totalAchieved,0,'.',',') ?></td>
            <td class="text-center" style="color:var(--green);font-weight:800;font-size:14px"><?= number_format($overallWeekPct,1) ?>%</td>
            <td colspan="6" class="text-end" style="padding-right:16px;color:#64748b;font-size:11.5px">
                Overall Weekly Progress: <strong style="color:var(--green);font-size:14px"><?= number_format($overallWeekPct,1) ?>%</strong>
            </td>
        </tr>
        </tfoot>
        <?php endif; ?>
    </table>
    </div>

    <?php if (!$isLocked && !empty($weeklyItems)): ?>
    <div style="padding:12px 16px;border-top:1px solid #f1f5f9">
        <!-- Work type selector row -->
        <div style="background:#f8fafc;border-radius:10px;padding:12px 16px">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:8px">
                <i class="bi bi-plus-circle me-1 text-primary"></i>Add Work to Schedule
            </div>
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label style="font-size:10px;font-weight:700;color:#94a3b8;display:block;margin-bottom:4px">1. Select Work Item</label>
                    <select id="addItemSelect" name="new_work_item_id" class="form-select form-select-sm fs-12">
                        <option value="">-- Choose Work Item --</option>
                        <?php foreach ($weeklyItems as $wi): ?>
                        <option value="<?= $wi['target_item_id'] ?>"
                                data-name="<?= htmlspecialchars($wi['work_item_name']) ?>"
                                data-unit="<?= htmlspecialchars($wi['unit']??'') ?>"
                                data-target="<?= number_format($wi['planned_qty'],2,'.','') ?>"
                                data-achieved="<?= number_format($wi['achieved_qty'],2,'.','') ?>"
                                data-pct="<?= number_format($wi['percent_complete']??0,1) ?>">
                            <?= htmlspecialchars($wi['work_item_name']) ?> (<?= $wi['unit']??'' ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label style="font-size:10px;font-weight:700;color:#94a3b8;display:block;margin-bottom:4px">2. Select Work Type(s) <span style="font-weight:400">(optional)</span></label>
                    <div class="d-flex flex-wrap gap-2">
                    <?php foreach (array_slice($workTypes,0,6) as $wt): ?>
                        <div class="form-check mb-0">
                            <input class="form-check-input wt-check" type="checkbox" name="new_work_types[]"
                                   value="<?= $wt['id'] ?>" id="wt_<?= $wt['id'] ?>"
                                   data-name="<?= htmlspecialchars($wt['work_type_name']) ?>">
                            <label class="form-check-label" style="font-size:11.5px" for="wt_<?= $wt['id'] ?>">
                                <?= htmlspecialchars($wt['work_type_name']) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" name="save_action" value="save" class="btn btn-primary btn-sm w-100 fw-bold">
                        <i class="bi bi-plus-lg me-1"></i>Add
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ░░ Weekly Progress Visual (Gantt) ░░ -->
<div class="wp-card">
    <div class="wp-card-header">Weekly Progress Visual
            <span style="font-size:10px;font-weight:500;color:#94a3b8"><?= date('d M', strtotime($weeklyProgress['week_start_date']??'now')) ?> – <?= date('d M Y', strtotime($weeklyProgress['week_end_date']??'now')) ?> (Week <?= $weekNo ?>)</span>
        </div>
    </div>
    <div class="gantt-wrap">
    <table class="gantt-table">
        <thead>
            <tr>
                <th style="text-align:left;min-width:160px">Work Item</th>
                <?php foreach ($daysList as $dt): ?>
                <th><?= date('D',$t=strtotime($dt)) ?><br><span style="font-weight:800"><?= date('d',$t) ?></span></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($weeklyItems as $wi):
            $iid = (int)$wi['target_item_id'];
            $pct = (float)$wi['percent_complete'];
            if ($pct>=100) $baseBarClass = 'gantt-bar-completed';
            elseif ($pct>0) $baseBarClass = 'gantt-bar-in-progress';
            else $baseBarClass = 'gantt-bar-none';
            $startDate = $wi['start_date'] ?? '';
            $endDate   = $wi['end_date']   ?? '';
        ?>
        <tr>
            <td>
                <div class="gantt-item-name" title="<?= htmlspecialchars($wi['work_item_name']) ?>">
                    <?= htmlspecialchars($wi['work_item_name']) ?>
                </div>
                <div style="font-size:9.5px;color:#94a3b8"><?= number_format($pct,0) ?>% done</div>
            </td>
            <?php foreach ($daysList as $dt):
                $hasEntry = isset($summaryByDate[$dt]);
                $inRange  = (!empty($startDate) && !empty($endDate)) ? ($dt >= $startDate && $dt <= $endDate) : true;
                $barClass = $inRange ? $baseBarClass : 'gantt-bar-none';
                $width    = $inRange ? max(20, $pct) : 0;
                if ($dt === $selectedDate && $hasEntry) $barClass = 'gantt-bar-in-progress';
                $title = $inRange ? number_format($pct,0).'% on '.date('d M',strtotime($dt)) : '—';
            ?>
            <td class="gantt-bar-cell">
                <?php if ($inRange): ?>
                <div class="gantt-bar <?= $barClass ?>" style="width:<?= $width ?>%" title="<?= $title ?>"></div>
                <?php else: ?>
                <div style="height:12px;border-radius:4px;background:#f1f5f9"></div>
                <?php endif; ?>
            </td>
            <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <div class="gantt-legend">
        <div class="gantt-legend-item"><div class="gantt-dot gantt-bar-completed"></div> Completed</div>
        <div class="gantt-legend-item"><div class="gantt-dot gantt-bar-in-progress"></div> In Progress</div>
        <div class="gantt-legend-item"><div class="gantt-dot gantt-bar-delayed"></div> Delayed</div>
        <div class="gantt-legend-item"><div class="gantt-dot" style="background:#94a3b8"></div> Pending QA</div>
        <div class="gantt-legend-item"><div class="gantt-dot gantt-bar-none"></div> Not Scheduled</div>
    </div>
</div>


<!-- ░░ Sub-Item Progress vs Monthly Target (full width) ░░ -->
<div class="wp-card">
    <div class="wp-card-header">
        <div class="wp-card-title"><i class="bi bi-table text-primary"></i> Sub-Item Progress vs Monthly Target</div>
    </div>
    <div style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse;font-size:10.5px">
        <thead>
            <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0">
                <th style="padding:7px 9px;font-size:8.5px;font-weight:700;text-transform:uppercase;color:#94a3b8;text-align:left;min-width:100px">Sub-Item</th>
                <th style="padding:7px 7px;font-size:8.5px;font-weight:700;text-transform:uppercase;color:var(--clr-theme-secondary);text-align:center;width:36px" title="Weight % of this sub-item in phase">Wt%</th>
                <th style="padding:7px 7px;font-size:8.5px;font-weight:700;text-transform:uppercase;color:#64748b;text-align:center;width:52px" title="Monthly Target Quantity">M.Target</th>
                <th style="padding:7px 7px;font-size:8.5px;font-weight:700;text-transform:uppercase;color:#1d4ed8;text-align:center;width:52px" title="Total Achieved Quantity this month">M.Achieved</th>
                <th style="padding:7px 7px;font-size:8.5px;font-weight:700;text-transform:uppercase;color:#6d28d9;text-align:center;width:46px" title="Actual monthly % (achieved/target)">Act%</th>
                <th style="padding:7px 7px;font-size:8.5px;font-weight:700;text-transform:uppercase;color:#0369a1;text-align:center;width:44px" title="Planned % for this month">Plan%</th>
                <th style="padding:7px 7px;font-size:8.5px;font-weight:700;text-transform:uppercase;color:#065f46;text-align:center;width:46px" title="Phase contribution = Weight% x Actual%">Contrib</th>
                <th style="padding:7px 7px;font-size:8.5px;font-weight:700;text-transform:uppercase;color:#991b1b;text-align:center;width:54px" title="Remaining to complete">Remain</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $totalContrib    = 0;
        $totalMTQty      = 0;
        $totalMonthlyAch = 0;
        $totalRemaining  = 0;
        $totalWeightSum  = 0;
        foreach ($weeklyItems as $wi):
            $itemName      = $wi['work_item_name'];
            $unit          = $wi['unit'] ?? '';
            $weight        = (float)($wi['sub_item_weight'] ?? 0);
            $mtQty         = (float)($wi['monthly_target_qty'] ?? 0);

            // ✅ Use MONTHLY actual qty (pmti.actual_qty), NOT weekly pwpi.achieved_qty
            $monthlyAchQty = (float)($wi['monthly_actual_qty'] ?? $wi['achieved_qty'] ?? 0);

            // Planned % from pmti.planned_progress_pct
            $planPct       = (float)($wi['monthly_planned_pct'] ?? 0);

            // Actual monthly % — use DB stored actual_progress_pct if available
            $dbActualPct   = (float)($wi['monthly_actual_pct'] ?? 0);
            $monthlyActPct = ($dbActualPct > 0 && $dbActualPct <= 100)
                           ? $dbActualPct
                           : (($mtQty > 0) ? min(100, $monthlyAchQty / $mtQty * 100) : 0);

            // Phase contribution = Weight% × Actual% / 100
            $contrib       = ($weight * $monthlyActPct / 100);

            // Remaining
            $remQty        = max(0, $mtQty - $monthlyAchQty);
            $remPct        = max(0, 100 - $monthlyActPct);

            // Colors
            if ($monthlyActPct >= 100)                              { $barC='#10b981'; $pctColor='#065f46'; }
            elseif ($planPct > 0 && $monthlyActPct >= $planPct)    { $barC='var(--clr-theme-primary)'; $pctColor='#3730a3'; }
            elseif ($monthlyActPct > 0)                             { $barC='#f59e0b'; $pctColor='#92400e'; }
            else                                                     { $barC='#e2e8f0'; $pctColor='#94a3b8'; }

            $totalContrib    += $contrib;
            $totalMTQty      += $mtQty;
            $totalMonthlyAch += $monthlyAchQty;
            $totalRemaining  += $remQty;
            $totalWeightSum  += $weight;
        ?>
        <tr style="border-bottom:1px solid #f1f5f9" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
            <td style="padding:7px 10px;vertical-align:middle">
                <div style="font-weight:600;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:110px" title="<?= htmlspecialchars($itemName) ?>">
                    <?= htmlspecialchars($itemName) ?>
                </div>
                <?php if ($unit): ?><div style="font-size:9px;color:#94a3b8"><?= $unit ?></div><?php endif; ?>
            </td>

            <!-- Weight % -->
            <td style="padding:6px 8px;text-align:center;vertical-align:middle">
                <span style="background:rgba(var(--clr-theme-primary-rgb),.08);color:#5b21b6;font-size:10px;font-weight:800;padding:2px 6px;border-radius:999px">
                    <?= number_format($weight,0) ?>%
                </span>
            </td>

            <!-- Monthly Target Qty -->
            <td style="padding:6px 8px;text-align:center;vertical-align:middle;color:#1e293b;font-weight:700;font-size:11px">
                <?= $mtQty > 0 ? number_format($mtQty,0,'.',',') : '<span style="color:#94a3b8">—</span>' ?>
            </td>

            <!-- Monthly Achieved Qty -->
            <td style="padding:6px 7px;text-align:center;vertical-align:middle">
                <span style="color:<?= $monthlyAchQty>0?'#1d4ed8':'#94a3b8' ?>;font-weight:800;font-size:11px">
                    <?= number_format($monthlyAchQty, 0, '.', ',') ?>
                </span>
            </td>

            <!-- Actual Monthly % -->
            <td style="padding:6px 7px;text-align:center;vertical-align:middle">
                <div style="font-weight:900;font-size:12px;color:<?= $pctColor ?>;line-height:1">
                    <?= number_format($monthlyActPct, 1) ?>%
                </div>
                <div style="height:4px;border-radius:2px;background:#f1f5f9;overflow:hidden;margin-top:3px">
                    <div style="width:<?= $monthlyActPct ?>%;height:100%;border-radius:2px;background:<?= $barC ?>"></div>
                </div>
            </td>

            <!-- Planned Monthly % -->
            <td style="padding:6px 7px;text-align:center;vertical-align:middle">
                <div style="font-weight:700;font-size:11px;color:#0369a1"><?= number_format($planPct, 0) ?>%</div>
                <?php if ($planPct > 0 && $monthlyActPct < $planPct): ?>
                <div style="font-size:8px;color:#dc2626;font-weight:700">Behind</div>
                <?php elseif ($planPct > 0): ?>
                <div style="font-size:8px;color:#10b981;font-weight:700">On Track ✓</div>
                <?php endif; ?>
            </td>

            <!-- Phase Contribution earned -->
            <td style="padding:6px 8px;text-align:center;vertical-align:middle">
                <span style="font-weight:800;font-size:11px;color:#065f46">
                    <?= number_format($contrib,2) ?>%
                </span>
                <div style="font-size:8.5px;color:#94a3b8;margin-top:1px">of phase</div>
            </td>

            <!-- Remaining -->
            <td style="padding:6px 8px;text-align:center;vertical-align:middle">
                <?php if ($mtQty > 0): ?>
                <div style="color:#dc2626;font-weight:700;font-size:10.5px">
                    <?= number_format($remQty,0,'.',',') ?> <?= $unit ?>
                </div>
                <div style="font-size:9px;color:#ef4444;font-weight:600"><?= number_format($remPct,0) ?>% left</div>
                <?php else: ?>
                <span style="color:#94a3b8;font-size:10px">—</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border-top:2px solid #86efac">
                <td style="padding:8px 9px;font-size:10.5px;font-weight:800;color:#166534">Total</td>
                <td style="padding:8px 7px;text-align:center;color:#6d28d9;font-size:11px;font-weight:900"><?= number_format($totalWeightSum,0) ?>%</td>
                <td style="padding:8px 7px;text-align:center;color:#374151;font-size:11px;font-weight:700"><?= number_format($totalMTQty,0,'.',',') ?></td>
                <td style="padding:8px 7px;text-align:center;color:#1d4ed8;font-size:11px;font-weight:700"><?= number_format($totalMonthlyAch,0,'.',',') ?></td>
                <td style="padding:8px 7px;text-align:center">
                    <?php $tActPct = $totalMTQty>0 ? min(100,$totalMonthlyAch/$totalMTQty*100) : 0; ?>
                    <span style="font-weight:900;font-size:13px;color:<?= $tActPct>=100?'#065f46':'#6d28d9' ?>"><?= number_format($tActPct,1) ?>%</span>
                </td>
                <td style="padding:8px 7px;text-align:center;color:#0369a1">—</td>
                <td style="padding:8px 7px;text-align:center;color:#065f46;font-size:13px;font-weight:900"><?= number_format($totalContrib,2) ?>%</td>
                <td style="padding:8px 7px;text-align:center;color:#dc2626;font-size:11px;font-weight:700"><?= number_format($totalRemaining,0,'.',',') ?></td>
            </tr>
            <tr style="background:#e0f2fe">
                <td colspan="8" style="padding:7px 10px;font-size:10px;color:#1e40af;font-weight:600;line-height:1.7">
                    <i class="bi bi-info-circle me-1"></i>
                    Phase: <strong><?= number_format($phaseActualPct,1) ?>%</strong>
                    → Project contrib: <strong style="color:#166534"><?= number_format($projectContrib,2) ?>%</strong>
                    | To earn: <strong style="color:#dc2626"><?= number_format(max(0,$phaseWeight-$projectContrib),2) ?>%</strong>
                    <div style="font-size:9px;color:#3b82f6;margin-top:2px">
                        Formula: Wt% × Act% = Contrib — e.g. Footing 20% × 8.57% = 1.71%
                    </div>
                </td>
            </tr>
        </tfoot>
    </table>
    </div>
</div>

</div><!-- col-8 -->
<div class="col-lg-4">

<!-- ░░ Phase Progress Rollup ░░ -->
<div class="phase-rollup">
    <h6><i class="bi bi-layers-half me-1"></i><?= htmlspecialchars($target['phase_name']??'Phase') ?> Progress</h6>
    <div class="d-flex justify-content-between align-items-end">
        <div>
            <div class="phase-val"><?= number_format($phaseActualPct, 1) ?>%</div>
            <div style="font-size:10.5px;opacity:.75;margin-top:2px">of this phase (<?= $phaseWeight ?>% of project)</div>
        </div>
        <div style="text-align:right">
            <div style="font-size:12px;opacity:.75">Project Contribution</div>
            <div style="font-size:22px;font-weight:800"><?= number_format($projectContrib,2) ?>%</div>
        </div>
    </div>
    <div class="phase-prog-bar"><div class="phase-prog-fill" style="width:<?= $phaseActualPct ?>%"></div></div>
    <div style="font-size:10px;opacity:.6;margin-top:6px">Sub-items completing → Phase complete → <?= $phaseWeight ?>% of project total</div>
</div>

<!-- ░░ Daily Entry Summary Donut ░░ -->
<div class="wp-card mb-3">
    <div class="wp-card-header"><div class="wp-card-title"><i class="bi bi-pie-chart-fill"></i> Daily Entry Summary</div></div>
    <div style="padding:16px;text-align:center">
        <?php
        $c = $completedItems; $ip = $inProgressItems;
        $d = count($issueItems); $ns = $totalItems - $c - $ip - $d;
        $ns = max(0, $ns);
        $cPct  = $totalItems > 0 ? ($c  / $totalItems * 100) : 0;
        $ipPct = $totalItems > 0 ? ($ip / $totalItems * 100) : 0;
        $dPct  = $totalItems > 0 ? ($d  / $totalItems * 100) : 0;
        ?>
        <!-- SVG Donut -->
        <svg viewBox="0 0 120 120" width="140" height="140" style="transform:rotate(-90deg)">
            <circle cx="60" cy="60" r="50" fill="none" stroke="#f1f5f9" stroke-width="18"/>
            <?php
            $offset = 0; $circ = 314.16;
            $segs = [
                ['pct'=>$cPct,  'color'=>'#10b981'],
                ['pct'=>$ipPct, 'color'=>'var(--clr-theme-primary)'],
                ['pct'=>$dPct,  'color'=>'#ef4444'],
                ['pct'=>max(0,100-$cPct-$ipPct-$dPct), 'color'=>'#94a3b8'],
            ];
            foreach ($segs as $seg):
                $dash = $seg['pct'] / 100 * $circ;
                $gap  = $circ - $dash;
            ?>
            <circle cx="60" cy="60" r="50" fill="none"
                    stroke="<?= $seg['color'] ?>" stroke-width="18"
                    stroke-dasharray="<?= $dash ?> <?= $gap ?>"
                    stroke-dashoffset="-<?= $offset ?>"/>
            <?php $offset += $dash; endforeach; ?>
        </svg>
        <div style="font-size:11px;font-weight:600;color:#64748b;margin-top:-10px"><?= $totalItems ?> Work Items Total</div>
        <div class="d-flex flex-wrap justify-content-center gap-3 mt-3">
            <div style="font-size:11.5px;display:flex;align-items:center;gap:5px"><span style="width:10px;height:10px;border-radius:2px;background:#10b981;display:inline-block"></span>Completed <?= $c ?></div>
            <div style="font-size:11.5px;display:flex;align-items:center;gap:5px"><span style="width:10px;height:10px;border-radius:2px;background:var(--clr-theme-primary);display:inline-block"></span>In Progress <?= $ip ?></div>
            <div style="font-size:11.5px;display:flex;align-items:center;gap:5px"><span style="width:10px;height:10px;border-radius:2px;background:#ef4444;display:inline-block"></span>Delayed <?= $d ?></div>
            <div style="font-size:11.5px;display:flex;align-items:center;gap:5px"><span style="width:10px;height:10px;border-radius:2px;background:#94a3b8;display:inline-block"></span>Not Started <?= $ns ?></div>
        </div>
    </div>
</div>

<!-- ░░ Site Inputs ░░ -->
<div class="wp-card mb-3">
    <div class="wp-card-header"><div class="wp-card-title"><i class="bi bi-tools"></i> Site Inputs &amp; Resources</div></div>
    <div style="padding:14px 16px">
        <div class="site-input-grid mb-3">
            <div class="site-inp-group">
                <div class="site-inp-label"><i class="bi bi-people-fill me-1 text-primary"></i>Labour Count</div>
                <input type="number" name="labour_count" value="<?= (int)($dailyLog['labour_count']??0) ?>" class="site-field" <?= $disAttr ?> placeholder="0">
            </div>
            <div class="site-inp-group">
                <div class="site-inp-label"><i class="bi bi-cpu-fill me-1 text-warning"></i>Equipment Active</div>
                <input type="number" name="equipment_active" value="<?= (int)($dailyLog['equipment_active']??0) ?>" class="site-field" <?= $disAttr ?> placeholder="0">
            </div>
            <div class="site-inp-group">
                <div class="site-inp-label"><i class="bi bi-box-seam me-1 text-success"></i>Cement (Bags)</div>
                <input type="number" name="cement_bags" value="<?= (int)($dailyLog['cement_bags']??0) ?>" class="site-field" <?= $disAttr ?> placeholder="0">
            </div>
            <div class="site-inp-group">
                <div class="site-inp-label"><i class="bi bi-droplet-fill me-1 text-info"></i>Concrete (CFT)</div>
                <input type="number" name="concrete_cft" step="0.01" value="<?= number_format((float)($dailyLog['concrete_cft']??0),2,'.','') ?>" class="site-field" <?= $disAttr ?> placeholder="0.00">
            </div>
            <div class="site-inp-group">
                <div class="site-inp-label"><i class="bi bi-exclamation-triangle-fill me-1 text-danger"></i>Safety Incidents</div>
                <input type="number" name="safety_incidents" value="0" class="site-field" <?= $disAttr ?> placeholder="0">
            </div>
            <div class="site-inp-group">
                <div class="site-inp-label"><i class="bi bi-thermometer-half me-1 text-orange"></i>Temperature (°C)</div>
                <input type="number" name="temperature_celsius" step="0.1" value="<?= htmlspecialchars($dailyLog['temperature_celsius']??'') ?>" class="site-field" <?= $disAttr ?> placeholder="e.g. 32">
            </div>
        </div>
        <!-- Weather -->
        <div class="mb-3">
            <div class="site-inp-label mb-1"><i class="bi bi-cloud-sun me-1"></i>Weather Condition</div>
            <select name="weather" class="site-field" <?= $disAttr ?>>
                <?php foreach (['Clear'=>'☀️ Clear','Rainy'=>'🌧️ Rainy','Cloudy'=>'☁️ Cloudy','Stormy'=>'⛈️ Stormy','Windy'=>'💨 Windy'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= (($dailyLog['weather']??'Clear')===$v)?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Remarks -->
        <div class="mb-3">
            <div class="site-inp-label mb-1"><i class="bi bi-chat-text me-1"></i>Site Engineer Remarks</div>
            <textarea name="site_remarks" rows="3" class="site-field" <?= $disAttr ?>
                      placeholder="Summarize progress, issues, delays..."><?= htmlspecialchars($dailyLog['site_remarks']??'') ?></textarea>
        </div>
        <!-- Checkboxes -->
        <div class="d-flex flex-column gap-2">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="safety_ok" id="safety_ok" value="1" <?= ($dailyLog['safety_ok']??0)?'checked':'' ?> <?= $disAttr ?>>
                <label class="form-check-label" style="font-size:11.5px;font-weight:600" for="safety_ok">
                    <i class="bi bi-shield-check text-success me-1"></i>Safety OK — Site is safe
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="qc_checked" id="qc_checked" value="1" <?= ($dailyLog['qc_checked']??0)?'checked':'' ?> <?= $disAttr ?>>
                <label class="form-check-label" style="font-size:11.5px;font-weight:600" for="qc_checked">
                    <i class="bi bi-clipboard-check text-primary me-1"></i>QA Checked — Inspected today
                </label>
            </div>
        </div>

        <!-- ── Material Use Button ── -->
        <?php
            $matIssueUrl = $base . '/con-material-issue-create'
                         . '?from_weekly=1'
                         . '&project_id=' . $project_id
                         . '&phase_id='   . $phase_id
                         . '&target_id='  . $target_id
                         . '&log_date='   . urlencode($selectedDate)
                         . '&back_url='   . urlencode(
                               $base . '/con-weekly-progress/' . $target_id
                             . '?week=' . $weekNo
                             . '&log_date=' . $selectedDate
                           );
        ?>
        <div style="margin-top:12px;padding-top:12px;border-top:1px solid #f1f5f9">
            <a href="<?= $matIssueUrl ?>"
               style="display:flex;align-items:center;justify-content:center;gap:8px;
                      width:100%;padding:9px 0;border-radius:10px;border:none;cursor:pointer;
                      background:linear-gradient(135deg,#0ea5e9,#0284c7);color:#fff;
                      font-weight:700;font-size:13px;text-decoration:none;
                      box-shadow:0 4px 12px rgba(14,165,233,.3);transition:all .15s"
               onmouseover="this.style.background='linear-gradient(135deg,#0284c7,#0369a1)'"
               onmouseout="this.style.background='linear-gradient(135deg,#0ea5e9,#0284c7)'">
                <i class="bi bi-box-arrow-right"></i>
                Material Use
            </a>
        </div>

        <?php if (!$isLocked): ?>
        <!-- ── Save Button (right panel) ── -->
        <div style="display:flex;gap:8px;margin-top:14px;padding-top:12px;border-top:1px solid #f1f5f9">
            <button type="submit" name="save_action" value="save"
                    style="flex:1;background:var(--pri);color:#fff;font-weight:700;font-size:13px;
                           padding:10px 0;border-radius:10px;border:none;cursor:pointer;
                           display:flex;align-items:center;justify-content:center;gap:6px;
                           box-shadow:0 4px 12px rgba(var(--clr-theme-primary-rgb),.3);transition:all .15s"
                    onmouseover="this.style.background='var(--clr-theme-primary)'" onmouseout="this.style.background='var(--pri)'">
                <i class="bi bi-check-circle-fill"></i> Save Progress
            </button>
            <button type="submit" name="save_action" value="draft"
                    style="background:#f8fafc;border:1px solid #cbd5e1;color:#475569;font-weight:700;
                           font-size:12px;padding:10px 14px;border-radius:10px;cursor:pointer;
                           display:flex;align-items:center;gap:5px;white-space:nowrap"
                    onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f8fafc'">
                <i class="bi bi-save"></i> Draft
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

</div><!-- col-4 -->
</div><!-- row -->
</form>

<!-- ░░ Issue & Delay Tracker + Material Issues (side by side) ░░ -->
<?php $materialIssues = $materialIssues ?? []; ?>
<div style="display:flex;gap:12px;margin-bottom:20px;align-items:stretch;flex-wrap:wrap">

    <!-- Issue & Delay Tracker -->
    <div style="flex:1;min-width:280px">
        <div class="wp-card" style="margin-bottom:0;height:100%">
            <div class="wp-card-header">
                <div class="wp-card-title">
                    <i class="bi bi-exclamation-triangle-fill text-danger"></i> Issue &amp; Delay Tracker
                </div>
                <span style="font-size:11px;background:#fee2e2;color:#dc2626;padding:2px 8px;border-radius:999px;font-weight:700">
                    <?= count($issueItems) ?> Issues
                </span>
            </div>
            <div style="padding:10px 16px">
                <?php if (empty($issueItems) && empty($weeklyItems)): ?>
                <div style="text-align:center;color:#94a3b8;font-size:12px;padding:16px 0">
                    <i class="bi bi-check-circle-fill text-success" style="font-size:22px;display:block;margin-bottom:6px"></i>No issues flagged
                </div>
                <?php else: ?>
                <?php foreach ($weeklyItems as $wi):
                    $pct = (float)$wi['percent_complete'];
                    if ($pct < 30 && $pct >= 0 && (float)$wi['planned_qty'] > 0):
                        $issType = $pct == 0 ? 'Not Started' : 'Low Progress';
                        $color   = $pct == 0 ? '#ef4444' : '#f59e0b';
                ?>
                <div class="issue-item">
                    <div class="issue-dot" style="background:<?= $color ?>"></div>
                    <div>
                        <div style="font-weight:600;font-size:12px"><?= htmlspecialchars($wi['work_item_name']) ?></div>
                        <div style="font-size:10.5px;color:#94a3b8"><?= $issType ?> — <?= number_format($pct,0) ?>% complete</div>
                    </div>
                </div>
                <?php endif; endforeach; ?>
                <?php if (empty($issueItems) && !empty($weeklyItems)): ?>
                <div style="text-align:center;color:#94a3b8;font-size:11.5px;padding:12px 0">
                    <i class="bi bi-check2-circle text-success me-1"></i>All items on track
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Material Issues -->
    <div style="flex:1;min-width:280px">
        <div class="wp-card" style="margin-bottom:0;height:100%">
            <div class="wp-card-header">
                <div class="wp-card-title">
                    <i class="bi bi-box-arrow-right text-info"></i> Material Issues
                </div>
                <div style="display:flex;align-items:center;gap:6px">
                    <span style="font-size:11px;background:#e0f2fe;color:#0369a1;padding:2px 8px;border-radius:999px;font-weight:700">
                        <?= count($materialIssues) ?> Issue<?= count($materialIssues) !== 1 ? 's' : '' ?>
                    </span>
                    <?php
                        $matIssueUrl = $base . '/con-material-issue-create'
                                     . '?from_weekly=1'
                                     . '&project_id=' . $project_id
                                     . '&phase_id='   . $phase_id
                                     . '&target_id='  . $target_id
                                     . '&log_date='   . urlencode($selectedDate)
                                     . '&back_url='   . urlencode(
                                           $base . '/con-weekly-progress/' . $target_id
                                         . '?week=' . $weekNo . '&log_date=' . $selectedDate
                                       );
                    ?>
                    <a href="<?= $matIssueUrl ?>"
                       style="font-size:11px;background:linear-gradient(135deg,#0ea5e9,#0284c7);color:#fff;
                              padding:3px 10px;border-radius:999px;font-weight:700;text-decoration:none;
                              display:inline-flex;align-items:center;gap:4px">
                        <i class="bi bi-plus-lg"></i> Add
                    </a>
                </div>
            </div>
            <div style="padding:10px 14px">
                <?php if (empty($materialIssues)): ?>
                    <div style="text-align:center;color:#94a3b8;font-size:12px;padding:16px 0">
                        <i class="bi bi-box-seam" style="font-size:22px;display:block;margin-bottom:6px;opacity:.4"></i>
                        No material issued for this target yet
                    </div>
                <?php else: ?>
                    <div style="display:flex;flex-direction:column;gap:7px">
                    <?php foreach ($materialIssues as $mi): ?>
                        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;
                                    padding:8px 11px;display:flex;align-items:center;justify-content:space-between;gap:8px">
                            <div style="flex:1;min-width:0">
                                <div style="font-size:12px;font-weight:700;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                    <?= htmlspecialchars($mi['material_name'] ?? '—') ?>
                                </div>
                                <div style="font-size:10px;color:#64748b;margin-top:2px;display:flex;gap:6px;flex-wrap:wrap">
                                    <?php if (!empty($mi['contractor_name'])): ?>
                                        <span><i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($mi['contractor_name']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($mi['purpose'])): ?>
                                        <span><i class="bi bi-chat-text me-1"></i><?= htmlspecialchars($mi['purpose']) ?></span>
                                    <?php endif; ?>
                                    <span><i class="bi bi-calendar2 me-1"></i><?= date('d M Y', strtotime($mi['issue_date'])) ?></span>
                                </div>
                            </div>
                            <div style="text-align:right;flex-shrink:0">
                                <div style="font-size:13px;font-weight:800;color:#dc2626">
                                    <?= number_format((float)$mi['quantity'], 2) ?>
                                    <span style="font-size:10px;font-weight:600;color:#94a3b8"><?= htmlspecialchars($mi['unit'] ?? '') ?></span>
                                </div>
                                <a href="<?= $base ?>/con-material-issue/<?= $mi['id'] ?>"
                                   style="font-size:10px;color:#0ea5e9;text-decoration:none;font-weight:600">
                                    ISS-<?= $mi['id'] ?> ↗
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div><!-- side-by-side row -->






<!-- ░░ Material Issues for this Target ░░ -->
<?php $materialIssues = $materialIssues ?? []; ?>


</div><!-- px-2 -->

<!-- ░░ Submit Modal ░░ -->
<div class="modal fade" id="submitModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="<?= $base ?>/con-weekly-progress-submit">
                <input type="hidden" name="weekly_progress_id" value="<?= (int)($weeklyProgress['id']??0) ?>">
                <input type="hidden" name="target_id" value="<?= $target_id ?>">
                <input type="hidden" name="week_number" value="<?= $weekNo ?>">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-send-fill text-success me-2"></i>Submit Weekly Report — Week <?= $weekNo ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">This will finalize Week <?= $weekNo ?> achievements and lock edits for supervisor review.</p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Weekly Weather Summary</label>
                        <select name="weather_condition" class="form-select">
                            <option>Mostly Sunny</option><option>Clear</option><option>Rainy</option><option>Cloudy</option><option>Stormy</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Site Engineer's Remarks *</label>
                        <textarea name="site_engineer_remarks" class="form-control" rows="3" required
                                  placeholder="Summarize achievements, delays, material issues this week..."></textarea>
                    </div>
                    <div class="d-flex flex-column gap-2">
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="safety_ok" id="m_safety" value="1" required><label class="form-check-label small" for="m_safety">Safety &amp; health compliance verified</label></div>
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="qc_checked" id="m_qc" value="1" required><label class="form-check-label small" for="m_qc">QC checklists verified</label></div>
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="material_received" id="m_mat" value="1" required><label class="form-check-label small" for="m_mat">Materials conformity verified</label></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm fw-bold px-4"><i class="bi bi-send-fill me-1"></i>Submit Report</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Hidden forms for QC and PM Review submissions (avoiding form nesting) -->
<form id="qcForm" method="POST" action="<?= $base ?>/con-weekly-progress-qc-approve" enctype="multipart/form-data" style="display:none">
    <input type="hidden" name="weekly_progress_id" value="<?= (int)($weeklyProgress['id']??0) ?>">
    <input type="hidden" name="target_id" value="<?= $target_id ?>">
    <input type="hidden" name="week_number" value="<?= $weekNo ?>">
    <input type="hidden" name="log_date" value="<?= htmlspecialchars($selectedDate) ?>">
</form>

<form id="pmForm" method="POST" action="<?= $base ?>/con-weekly-progress-pm-approve" style="display:none">
    <input type="hidden" name="weekly_progress_id" value="<?= (int)($weeklyProgress['id']??0) ?>">
    <input type="hidden" name="target_id" value="<?= $target_id ?>">
    <input type="hidden" name="week_number" value="<?= $weekNo ?>">
    <input type="hidden" name="log_date" value="<?= htmlspecialchars($selectedDate) ?>">
</form>

<?php
$activeWtByItem = [];
foreach ($dailyQuantities as $dq) {
    $iid = (int)$dq['target_item_id'];
    $wtId = $dq['work_type_id'] ? (int)$dq['work_type_id'] : 0;
    if ($wtId > 0) {
        $activeWtByItem[$iid][] = $wtId;
    }
}
?>
<script>
const activeWtByItem = <?= json_encode($activeWtByItem) ?>;

function onAddItemSelectChange() {
    const sel = document.getElementById('addItemSelect');
    const itemId = parseInt(sel.value) || 0;
    const activeWts = activeWtByItem[itemId] || [];
    
    document.querySelectorAll('.wt-check').forEach(cb => {
        const wtId = parseInt(cb.value) || 0;
        cb.checked = activeWts.includes(wtId);
    });
}

// Bind the change event to the work item selector dropdown
document.addEventListener('DOMContentLoaded', function() {
    const sel = document.getElementById('addItemSelect');
    if (sel) {
        sel.addEventListener('change', onAddItemSelectChange);
    }
});

function confirmDelete(logRowId, itemName) {
    if (confirm("Are you sure you want to remove '" + itemName + "' from today's schedule?")) {
        window.location.href = "<?= $base ?>/con-weekly-progress-delete/" + logRowId;
    }
}
</script>

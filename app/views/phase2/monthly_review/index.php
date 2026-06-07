<?php
$base        = $GLOBALS['base_url'];
$project     = $project    ?? [];
$phases      = $phases     ?? [];
$targetsByPhase = $targetsByPhase ?? [];
$projectId   = (int)($project['id'] ?? 0);

$statusColors = [
    'Not Started' => ['bg'=>'#f1f5f9','color'=>'#64748b'],
    'In Progress' => ['bg'=>'#dbeafe','color'=>'#1e40af'],
    'Completed'   => ['bg'=>'#d1fae5','color'=>'#065f46'],
    'At Risk'     => ['bg'=>'#fef3c7','color'=>'#92400e'],
    'Delayed'     => ['bg'=>'#fee2e2','color'=>'#991b1b'],
];
$planStatusColors = [
    'Draft'     => ['bg'=>'#f1f5f9','color'=>'#64748b'],
    'In Review' => ['bg'=>'#fef3c7','color'=>'#d97706'],
    'Approved'  => ['bg'=>'#d1fae5','color'=>'#059669'],
];
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
body { font-family:'Inter',sans-serif }
:root { --pri:var(--clr-theme-primary);--green:#10b981;--amber:#f59e0b;--red:#ef4444;--slate:#64748b }

.mr-header { margin-bottom:24px }
.mr-crumb { font-size:11.5px;color:var(--slate);margin-bottom:6px }
.mr-crumb a { color:var(--pri);text-decoration:none }
.mr-title { font-size:22px;font-weight:900;color:#1e293b;margin:0 }
.mr-subtitle { font-size:12.5px;color:var(--slate);margin-top:4px }

.phase-grid { display:flex;flex-direction:column;gap:16px }

.phase-card {
    background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;
    box-shadow:0 1px 4px rgba(0,0,0,.06)
}
.phase-card-header {
    display:flex;align-items:center;justify-content:space-between;
    padding:16px 22px;border-bottom:1px solid #f1f5f9;flex-wrap:wrap;gap:10px
}
.phase-name { font-size:15px;font-weight:800;color:#1e293b }
.phase-meta { font-size:11px;color:var(--slate);margin-top:3px }
.phase-badge { display:inline-flex;align-items:center;font-size:10.5px;font-weight:700;padding:3px 11px;border-radius:999px }
.phase-stats { display:flex;gap:18px;align-items:center }
.phase-stat { text-align:center }
.phase-stat-val { font-size:16px;font-weight:800;color:#1e293b }
.phase-stat-lbl { font-size:9.5px;color:var(--slate);font-weight:600;text-transform:uppercase }

.phase-prog-row { display:flex;gap:10px;align-items:center;padding:0 22px 14px }
.phase-prog-bar { flex:1;height:6px;border-radius:3px;background:#f1f5f9;overflow:hidden }
.phase-prog-fill { height:100%;border-radius:3px }

.months-body { padding:18px 22px }
.months-label { font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--slate);margin-bottom:12px }
.months-grid { display:flex;flex-wrap:wrap;gap:10px }

.month-card {
    border-radius:12px;border:1px solid #e2e8f0;padding:14px 18px;
    text-decoration:none;color:inherit;min-width:170px;flex:0 0 auto;
    transition:all .18s;cursor:pointer;background:#fafafa;position:relative;overflow:hidden
}
.month-card:hover { border-color:var(--pri);background:#fff;box-shadow:0 4px 14px rgba(var(--clr-theme-primary-rgb),.14);transform:translateY(-2px) }
.month-card.approved { border-color:#10b981;background:linear-gradient(135deg,#f0fdf4,#fff) }
.month-card.draft    { border-color:#e2e8f0 }

.month-name { font-size:13.5px;font-weight:800;color:#1e293b;margin-bottom:6px }
.month-stat { font-size:10.5px;color:var(--slate);display:flex;align-items:center;gap:5px;margin-top:3px }
.month-stat-val { font-weight:700;color:#1e293b }
.month-pct-row { display:flex;gap:8px;margin-top:10px }
.month-pct-chip { font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px }
.month-pct-plan { background:#dbeafe;color:#1e40af }
.month-pct-act  { background:#d1fae5;color:#065f46 }
.month-pct-behind { background:#fee2e2;color:#991b1b }

.month-arrow {
    position:absolute;right:12px;top:50%;transform:translateY(-50%);
    font-size:16px;color:#cbd5e1
}
.month-card:hover .month-arrow { color:var(--pri) }

.empty-state { text-align:center;padding:40px;color:var(--slate);font-size:13px }
</style>

<div class="px-3 py-2">

<!-- Header -->
<div class="mr-header">
    <div class="mr-crumb">
        <a href="<?= $base ?>/con-projects">Projects</a> ›
        <a href="<?= $base ?>/con-phase-plan/<?= $projectId ?>"><?= htmlspecialchars($project['name']??'') ?></a> ›
        Monthly Review
    </div>
    <h1 class="mr-title"><i class="bi bi-calendar-check me-2" style="color:var(--pri)"></i>Monthly Progress Review</h1>
    <div class="mr-subtitle">Select a phase and month to view the detailed review dashboard</div>
</div>

<!-- Phase list -->
<div class="phase-grid">
<?php if (empty($phases)): ?>
    <div class="empty-state">
        <i class="bi bi-layers" style="font-size:36px;opacity:.2;display:block;margin-bottom:10px"></i>
        No phases set up for this project yet.
        <a href="<?= $base ?>/con-phase-plan/<?= $projectId ?>" style="color:var(--pri)">Set up phases →</a>
    </div>
<?php else: ?>
<?php foreach ($phases as $phase):
    $sc  = $statusColors[$phase['status']] ?? $statusColors['Not Started'];
    $planPct = round((float)($phase['planned_progress'] ?? 0), 1);
    $actPct  = round((float)($phase['actual_progress']  ?? 0), 1);
    $gap     = $actPct - $planPct;
    $targets = $targetsByPhase[(int)$phase['id']] ?? [];
?>
<div class="phase-card">
    <!-- Phase header -->
    <div class="phase-card-header">
        <div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                <div class="phase-name"><?= htmlspecialchars($phase['phase_name']) ?></div>
                <span class="phase-badge" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>">
                    <?= $phase['status'] ?>
                </span>
                <span style="font-size:10.5px;background:#f1f5f9;color:#475569;padding:2px 9px;border-radius:999px;font-weight:600">
                    <?= number_format((float)$phase['weight_percentage'],0) ?>% of project
                </span>
            </div>
            <div class="phase-meta">
                <?php if ($phase['planned_start_date']): ?>
                    <?= date('d M Y', strtotime($phase['planned_start_date'])) ?> →
                    <?= $phase['planned_end_date'] ? date('d M Y', strtotime($phase['planned_end_date'])) : 'TBD' ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="phase-stats">
            <div class="phase-stat">
                <div class="phase-stat-val" style="color:var(--clr-theme-primary)"><?= number_format($planPct,1) ?>%</div>
                <div class="phase-stat-lbl">Planned</div>
            </div>
            <div class="phase-stat">
                <div class="phase-stat-val" style="color:<?= $actPct>=$planPct?'#10b981':'#ef4444' ?>"><?= number_format($actPct,1) ?>%</div>
                <div class="phase-stat-lbl">Actual</div>
            </div>
            <div class="phase-stat">
                <div class="phase-stat-val" style="color:<?= $gap>=0?'#10b981':'#ef4444' ?>"><?= ($gap>=0?'+':'').number_format($gap,1) ?>%</div>
                <div class="phase-stat-lbl">Gap</div>
            </div>
            <div class="phase-stat">
                <div class="phase-stat-val" style="color:#0369a1"><?= count($targets) ?></div>
                <div class="phase-stat-lbl">Months</div>
            </div>
        </div>
    </div>

    <!-- Progress bar -->
    <div class="phase-prog-row">
        <div style="font-size:10px;color:#94a3b8;width:50px;text-align:right"><?= number_format($planPct,0) ?>%</div>
        <div class="phase-prog-bar">
            <div class="phase-prog-fill" style="width:<?= min(100,$actPct) ?>%;background:<?= $actPct>=$planPct?'#10b981':'#f59e0b' ?>"></div>
        </div>
        <div style="font-size:10px;color:#10b981;width:36px"><?= number_format($actPct,0) ?>%</div>
    </div>

    <!-- Month chips -->
    <div class="months-body">
        <?php if (empty($targets)): ?>
        <div style="color:#94a3b8;font-size:12px;font-style:italic">
            <i class="bi bi-calendar-x me-1"></i>No monthly targets set yet —
            <a href="<?= $base ?>/con-monthly-targets/<?= $projectId ?>" style="color:var(--pri)">Add targets →</a>
        </div>
        <?php else: ?>
        <div class="months-label"><i class="bi bi-calendar3 me-1"></i>Monthly Targets</div>
        <div class="months-grid">
        <?php foreach ($targets as $t):
            $tPlan = round((float)($t['avg_planned_pct'] ?? 0), 1);
            $tAct  = round((float)($t['avg_actual_pct']  ?? 0), 1);
            $tGap  = $tAct - $tPlan;
            $tStatus = $t['planning_status'] ?? 'Draft';
            $psc   = $planStatusColors[$tStatus] ?? $planStatusColors['Draft'];
            $monthLabel = date('F Y', strtotime($t['target_month']));
            $monthYM    = date('Y-m', strtotime($t['target_month']));
            $reviewUrl  = $base . '/con-monthly-review/' . $projectId
                        . '/phase/' . $phase['id']
                        . '/month/' . $monthYM;
            $cardClass  = strtolower(str_replace(' ','-',$tStatus));
        ?>
        <a href="<?= $reviewUrl ?>" class="month-card <?= $cardClass ?>">
            <div style="display:flex;align-items:flex-start;justify-content:space-between">
                <div>
                    <div class="month-name"><?= $monthLabel ?></div>
                    <span class="phase-badge" style="font-size:9.5px;background:<?= $psc['bg'] ?>;color:<?= $psc['color'] ?>;padding:2px 8px">
                        <?= $tStatus ?>
                    </span>
                </div>
                <i class="bi bi-arrow-right-circle month-arrow"></i>
            </div>
            <div class="month-stat">
                <i class="bi bi-list-check" style="color:var(--clr-theme-primary)"></i>
                <span class="month-stat-val"><?= (int)$t['item_count'] ?></span> work items
            </div>
            <?php if ($t['total_target_qty'] > 0): ?>
            <div class="month-stat">
                <i class="bi bi-bullseye" style="color:#64748b"></i>
                Target: <span class="month-stat-val"><?= number_format((float)$t['total_target_qty'],0,'.',',') ?></span>
                / Achieved: <span class="month-stat-val" style="color:#10b981"><?= number_format((float)$t['total_actual_qty'],0,'.',',') ?></span>
            </div>
            <div class="month-stat" style="font-size: 10px; margin-top: 1px; color: var(--slate);">
                <i class="bi bi-info-circle-fill" style="color:#94a3b8; font-size: 9px; margin-left: 2px; margin-right: 4px;"></i>
                <span>Total Phase BOQ: <span class="month-stat-val"><?= number_format((float)($phase['total_phase_boq'] ?? 0),0,'.',',') ?></span></span>
            </div>
            <?php endif; ?>
            <div class="month-pct-row">
                <span class="month-pct-chip month-pct-plan">Plan <?= number_format($tPlan,1) ?>%</span>
                <span class="month-pct-chip <?= $tAct>=$tPlan?'month-pct-act':'month-pct-behind' ?>">
                    Act <?= number_format($tAct,1) ?>%
                </span>
                <?php if ($tGap < -2): ?>
                <span class="month-pct-chip month-pct-behind"><?= number_format($tGap,1) ?>% gap</span>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div><!-- phase-grid -->

</div><!-- px-3 -->

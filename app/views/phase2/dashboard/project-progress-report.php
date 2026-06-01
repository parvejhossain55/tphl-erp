<?php
$base             = $GLOBALS['base_url'];
$project          = $project          ?? [];
$timeline         = $timeline         ?? [];
$phases           = $phases           ?? [];
$totalProjectProgress = (float)($totalProjectProgress ?? 0);

$projectName = htmlspecialchars($project['name'] ?? 'Project');
$startDate   = $timeline['start_date'] ?? ($project['start_date'] ?? null);
$endDate     = $timeline['end_date']   ?? ($project['end_date']   ?? null);
$projectId   = (int)($project['id'] ?? 0);

$completedPhases = 0; $inProgressPhases = 0;
foreach ($phases as $ph) {
    if ((float)($ph['computed_actual'] ?? 0) >= 100) $completedPhases++;
    elseif ((float)($ph['computed_actual'] ?? 0) > 0) $inProgressPhases++;
}
$totalWeight = array_sum(array_column($phases, 'weight_percentage'));
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

:root {
    --pri:#5e4cdc; --green:#10b981; --amber:#f59e0b; --red:#ef4444;
    --cyan:#0ea5e9; --purple:#8b5cf6; --slate:#64748b; --navy:#1e3a5f;
}

/* ── Hero Banner ── */
.prog-hero {
    background: #5e4cdc;
    border-radius: 20px; padding: 28px 32px; color: #fff;
    position: relative; overflow: hidden; margin-bottom: 22px;
}
.prog-hero::before {
    content: ''; position: absolute; top: -40px; right: -40px;
    width: 200px; height: 200px; border-radius: 50%;
    background: rgba(255,255,255,0.07); pointer-events: none;
}
.prog-hero::after {
    content: ''; position: absolute; bottom: -50px; left: 25%;
    width: 140px; height: 140px; border-radius: 50%;
    background: rgba(255,255,255,0.05); pointer-events: none;
}

/* ── Overall Progress Ring ── */
.prog-ring-wrap { position: relative; display: inline-flex; align-items: center; justify-content: center }
.prog-center { position: absolute; text-align: center; }
.prog-val  { font-size: 28px; font-weight: 900; color: #fff; line-height: 1 }
.prog-sub  { font-size: 9px; font-weight: 700; color: rgba(255,255,255,.65); text-transform: uppercase }

/* ── KPI Strip ── */
.kpi-strip { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; margin-bottom: 22px }
.kpi-card  { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px 18px;
             display: flex; align-items: center; gap: 12px; transition: transform .15s }
.kpi-card:hover { transform: translateY(-2px) }
.kpi-ico   { width: 44px; height: 44px; border-radius: 11px; display: flex; align-items: center;
             justify-content: center; font-size: 20px; flex-shrink: 0 }
.kpi-lbl   { font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px;
             color: var(--slate); margin-bottom: 2px }
.kpi-val   { font-size: 20px; font-weight: 800; line-height: 1 }
.kpi-sub2  { font-size: 10px; color: var(--slate); margin-top: 2px }

/* ── Timeline Bar ── */
.timeline-bar { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px 20px; margin-bottom: 18px }
.tl-track { position: relative; height: 10px; background: #e2e8f0; border-radius: 5px; margin: 10px 0 }
.tl-fill  { height: 100%; border-radius: 5px; background: var(--pri); transition: width .4s }
.tl-thumb { position: absolute; top: 50%; transform: translate(-50%,-50%);
            width: 20px; height: 20px; border-radius: 50%; background: var(--green);
            border: 3px solid #fff; box-shadow: 0 2px 8px rgba(16,185,129,.5) }

/* ── Phase Card ── */
.phase-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; margin-bottom: 14px }
.phase-header { padding: 14px 20px; display: flex; align-items: center; gap: 12px; cursor: pointer;
                transition: background .15s; user-select: none }
.phase-header:hover { background: #f8fafc }
.phase-num  { width: 32px; height: 32px; border-radius: 9px; display: flex; align-items: center; justify-content:center;
              font-size: 12px; font-weight: 800; color: #fff; flex-shrink: 0 }
.phase-name { font-weight: 700; font-size: 14px; color: #1e293b; flex: 1 }
.phase-weight-badge { font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px;
                      background: #ede9fe; color: #5b21b6 }
.phase-pct  { font-size: 18px; font-weight: 900; min-width: 60px; text-align: right }
.phase-bar-wrap { height: 8px; border-radius: 4px; background: #f1f5f9; overflow: hidden; margin: 0 20px 0 }
.phase-bar-fill { height: 100%; border-radius: 4px; transition: width .4s }
.phase-body { padding: 0 16px 14px }

/* ── Sub-items table ── */
.sub-table { width: 100%; border-collapse: collapse; font-size: 12px; margin-top: 6px }
.sub-table th { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px;
                color: #94a3b8; padding: 7px 10px; border-bottom: 1px solid #f1f5f9; text-align: left; background: #f8fafc }
.sub-table td { padding: 8px 10px; border-bottom: 1px solid #f8fafc; vertical-align: middle }
.sub-table tr:last-child td { border-bottom: none }
.sub-table tr:hover td { background: #fafafa }

.sub-pct-bar { height: 5px; border-radius: 3px; background: #e2e8f0; overflow: hidden; min-width: 80px; margin-top: 3px }
.sub-pct-fill { height: 100%; border-radius: 3px }

.s-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 9.5px; font-weight: 700;
           padding: 2px 8px; border-radius: 999px; white-space: nowrap }
.s-done  { background: #d1fae5; color: #065f46 }
.s-prog  { background: #dbeafe; color: #1e40af }
.s-none  { background: #f1f5f9; color: #64748b }
.s-recov { background: #fef3c7; color: #92400e }

.recov-flag { display: inline-flex; align-items: center; gap: 4px; font-size: 10px; font-weight: 700;
              color: #d97706; background: #fef3c7; border-radius: 6px; padding: 2px 7px }

/* ── Section headers ── */
.section-title { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .5px;
                 color: #64748b; margin-bottom: 12px; display: flex; align-items: center; gap: 6px }

/* ── Weight pie visual ── */
.weight-pill { display: inline-block; font-size: 9.5px; font-weight: 700; padding: 2px 8px;
               border-radius: 999px; background: #f1f5f9; color: #1e293b; margin-right: 4px }
</style>

<?php
// Determine phase colors by index
$phaseColors = [
    '#4f46e5','#10b981','#f59e0b','#ef4444','#0ea5e9','#8b5cf6','#14b8a6',
    '#f97316','#06b6d4','#84cc16','#ec4899','#a855f7','#22c55e','#eab308','#3b82f6'
];

// Calculate days elapsed for timeline bar
$elapsed = 0;
if ($startDate && $endDate) {
    $today  = new DateTime();
    $tStart = new DateTime($startDate);
    $tEnd   = new DateTime($endDate);
    $total  = $tStart->diff($tEnd)->days ?: 1;
    $done   = min($total, max(0, $tStart->diff($today)->days));
    $elapsed = min(100, round($done / $total * 100));
}
?>

<div class="px-1 py-1">

<!-- ░░ Hero Banner ░░ -->
<div class="prog-hero">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div style="flex:1">
            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;opacity:.65;margin-bottom:6px">
                Project Progress Report
            </div>
            <h3 style="font-size:22px;font-weight:900;margin-bottom:6px"><?= $projectName ?></h3>
            <div style="font-size:12px;opacity:.75;display:flex;gap:16px;flex-wrap:wrap">
                <?php if ($startDate): ?>
                <span><i class="bi bi-calendar-event me-1"></i>Start: <?= date('d M Y', strtotime($startDate)) ?></span>
                <?php endif; ?>
                <?php if ($endDate): ?>
                <span><i class="bi bi-calendar-check me-1"></i>Handover: <?= date('d M Y', strtotime($endDate)) ?></span>
                <?php endif; ?>
                <span><i class="bi bi-layers me-1"></i><?= count($phases) ?> Phases</span>
            </div>
        </div>
        <!-- Overall progress ring -->
        <div class="text-center">
            <div class="prog-ring-wrap">
                <svg viewBox="0 0 120 120" width="130" height="130" style="transform:rotate(-90deg)">
                    <circle cx="60" cy="60" r="50" fill="none" stroke="rgba(255,255,255,.15)" stroke-width="14"/>
                    <circle cx="60" cy="60" r="50" fill="none" stroke="#10b981" stroke-width="14"
                            stroke-dasharray="<?= ($totalProjectProgress/100)*314.16 ?> <?= (1-$totalProjectProgress/100)*314.16 ?>"
                            stroke-dashoffset="0" stroke-linecap="round"/>
                </svg>
                <div class="prog-center">
                    <div class="prog-val"><?= number_format($totalProjectProgress, 1) ?>%</div>
                    <div class="prog-sub">Overall</div>
                </div>
            </div>
            <div style="font-size:10px;opacity:.65;margin-top:4px">Project Progress</div>
        </div>
    </div>
</div>

<!-- ░░ KPI Strip ░░ -->
<div class="kpi-strip">
    <div class="kpi-card">
        <div class="kpi-ico" style="background:#ede9fe;color:var(--purple)"><i class="bi bi-layers-fill"></i></div>
        <div><div class="kpi-lbl">Total Phases</div><div class="kpi-val"><?= count($phases) ?></div><div class="kpi-sub2"><?= number_format($totalWeight, 0) ?>% total weight</div></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-ico" style="background:#d1fae5;color:var(--green)"><i class="bi bi-check-circle-fill"></i></div>
        <div><div class="kpi-lbl">Completed</div><div class="kpi-val"><?= $completedPhases ?></div><div class="kpi-sub2">Phases 100%</div></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-ico" style="background:#dbeafe;color:var(--pri)"><i class="bi bi-hourglass-split"></i></div>
        <div><div class="kpi-lbl">In Progress</div><div class="kpi-val"><?= $inProgressPhases ?></div><div class="kpi-sub2">Active phases</div></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-ico" style="background:#fef3c7;color:var(--amber)"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <div>
            <div class="kpi-lbl">Recovery Needed</div>
            <?php
            $recov = 0;
            foreach ($phases as $ph) foreach ($ph['items'] ?? [] as $it) if ($it['needs_recovery'] ?? false) $recov++;
            ?>
            <div class="kpi-val"><?= $recov ?></div>
            <div class="kpi-sub2">Sub-items flagged</div>
        </div>
    </div>
</div>

<!-- ░░ Project Timeline Bar ░░ -->
<?php if ($startDate && $endDate): ?>
<div class="timeline-bar mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="section-title mb-0"><i class="bi bi-calendar-range text-primary"></i> Project Timeline</div>
        <div style="font-size:11.5px;color:var(--slate)"><?= $elapsed ?>% time elapsed</div>
    </div>
    <div class="tl-track">
        <div class="tl-fill" style="width:<?= $elapsed ?>%"></div>
        <div class="tl-thumb" style="left:<?= $elapsed ?>%"></div>
    </div>
    <div class="d-flex justify-content-between" style="font-size:10.5px;color:var(--slate);font-weight:600;margin-top:4px">
        <span>Start: <?= date('d M Y', strtotime($startDate)) ?></span>
        <span>Today: <?= date('d M Y') ?></span>
        <span>Handover: <?= date('d M Y', strtotime($endDate)) ?></span>
    </div>
</div>
<?php endif; ?>

<!-- ░░ Phase Hierarchy List ░░ -->
<div class="section-title"><i class="bi bi-diagram-3-fill text-primary"></i> Phase Progress Breakdown (Timeline Start → Handover)</div>

<?php foreach ($phases as $pi => $phase):
    $pColor   = $phaseColors[$pi % count($phaseColors)];
    $pActual  = (float)($phase['computed_actual'] ?? 0);
    $pWeight  = (float)($phase['weight_percentage'] ?? 0);
    $pContrib = (float)($phase['contribution'] ?? 0);
    $pItems   = $phase['items'] ?? [];
    $pMtCount = (int)($phase['monthly_target_count'] ?? 0);
    $pName    = htmlspecialchars($phase['phase_name'] ?? '');
    $pStatus  = $phase['status'] ?? 'Not Started';
    $pOrder   = (int)($phase['phase_order'] ?? $pi+1);

    if ($pActual >= 100) { $statusClass='s-done'; $statusLabel='Completed'; $barColor='#10b981'; }
    elseif ($pActual > 0){ $statusClass='s-prog'; $statusLabel='In Progress'; $barColor=$pColor; }
    else                  { $statusClass='s-none'; $statusLabel='Not Started'; $barColor='#cbd5e1'; }

    $phaseCollapseId = 'phase_' . $phase['id'];
?>
<div class="phase-card">
    <!-- Phase Header (collapsible) -->
    <div class="phase-header" data-bs-toggle="collapse" data-bs-target="#<?= $phaseCollapseId ?>">
        <div class="phase-num" style="background:<?= $pColor ?>"><?= $pOrder ?></div>
        <div style="flex:1">
            <div class="phase-name"><?= $pName ?></div>
            <div style="font-size:10.5px;color:var(--slate);margin-top:2px">
                <span class="weight-pill">Weight: <?= number_format($pWeight, 0) ?>%</span>
                <span class="weight-pill"><?= $pMtCount ?> Monthly Target<?= $pMtCount!=1?'s':'' ?></span>
                <span class="weight-pill"><?= count($pItems) ?> Sub-items</span>
            </div>
        </div>
        <!-- Progress bar inline -->
        <div style="min-width:180px">
            <div class="d-flex justify-content-between mb-1" style="font-size:10px;font-weight:700;color:var(--slate)">
                <span><?= $pName ?> Progress</span>
                <span style="color:<?= $pColor ?>"><?= number_format($pActual, 1) ?>%</span>
            </div>
            <div style="height:7px;border-radius:4px;background:#f1f5f9;overflow:hidden">
                <div style="width:<?= $pActual ?>%;height:100%;border-radius:4px;background:<?= $barColor ?>"></div>
            </div>
            <div style="font-size:9.5px;color:var(--slate);margin-top:2px">
                Project contribution: <strong style="color:<?= $pColor ?>"><?= number_format($pContrib, 2) ?>%</strong>
            </div>
        </div>
        <div class="ms-3">
            <div class="phase-pct" style="color:<?= $barColor ?>"><?= number_format($pActual, 0) ?>%</div>
            <span class="s-badge <?= $statusClass ?>"><?= $statusLabel ?></span>
        </div>
        <i class="bi bi-chevron-down ms-2" style="color:var(--slate);font-size:13px"></i>
    </div>

    <!-- Phase Progress Bar -->
    <div class="phase-bar-wrap" style="margin-top:-2px;margin-bottom:2px">
        <div class="phase-bar-fill" style="width:<?= $pActual ?>%;background:<?= $barColor ?>"></div>
    </div>

    <!-- Phase Sub-items (collapsed by default) -->
    <div class="collapse show" id="<?= $phaseCollapseId ?>">
    <div class="phase-body">
        <?php if (empty($pItems)): ?>
        <div style="text-align:center;color:var(--slate);font-size:12px;padding:12px 0">
            <i class="bi bi-inbox me-1"></i>No sub-items configured for this phase.
            <a href="<?= $base ?>/con-phase-plan/<?= $projectId ?>" style="font-size:12px;margin-left:6px">Setup →</a>
        </div>
        <?php else: ?>
        <table class="sub-table">
            <thead>
                <tr>
                    <th style="width:30px">#</th>
                    <th>Sub-Item Name</th>
                    <th class="text-center" style="width:70px">Weight %</th>
                    <th class="text-center" style="width:120px">Monthly Target Progress</th>
                    <th class="text-center" style="width:180px">Completion (vs Monthly Target)</th>
                    <th class="text-center" style="width:80px">Phase Contrib</th>
                    <th class="text-center" style="width:90px">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pItems as $si => $item):
                $iWeight  = (float)$item['weight_pct'];
                $iActual  = (float)($item['computed_actual'] ?? 0);
                $iMtQty   = (float)($item['monthly_target_qty'] ?? 0);
                $iActQty  = (float)($item['monthly_actual_qty'] ?? 0);
                $iMtCount = (int)($item['monthly_count'] ?? 0);
                $iContrib = ($iActual * $iWeight / 100);
                $iNeedRec = (bool)($item['needs_recovery'] ?? false);

                if ($iActual >= 100) { $iStatCls='s-done'; $iStatLbl='Complete ✓'; $iBarColor='#10b981'; }
                elseif ($iActual > 0){ $iStatCls='s-prog'; $iStatLbl='In Progress'; $iBarColor=$pColor; }
                else                  { $iStatCls='s-none'; $iStatLbl='Not Started'; $iBarColor='#e2e8f0'; }
            ?>
            <tr>
                <td style="color:#94a3b8;font-weight:700;font-size:11px"><?= $si+1 ?></td>
                <td>
                    <div style="font-weight:600;color:#1e293b"><?= htmlspecialchars($item['item_name']) ?></div>
                    <?php if ($iNeedRec): ?>
                    <div class="recov-flag mt-1"><i class="bi bi-exclamation-triangle-fill"></i>Recovery Plan Needed</div>
                    <?php endif; ?>
                    <?php if ($iMtCount > 0): ?>
                    <div style="font-size:9.5px;color:var(--slate);margin-top:2px">
                        <?= $iMtCount ?> monthly target<?= $iMtCount!=1?'s':'' ?>
                        <?php if ($iMtQty > 0): ?>
                        | Qty: <?= number_format($iActQty,0) ?> / <?= number_format($iMtQty,0) ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <div style="display:inline-block;background:<?= $pColor ?>18;color:<?= $pColor ?>;font-size:12px;font-weight:800;padding:3px 10px;border-radius:999px">
                        <?= number_format($iWeight, 0) ?>%
                    </div>
                    <div style="font-size:9px;color:var(--slate);margin-top:2px">of phase</div>
                </td>
                <td class="text-center" style="color:var(--slate);font-size:11px">
                    <?php if ($iMtCount > 0): ?>
                    <?= $iMtCount ?> month<?= $iMtCount!=1?'s':'' ?><br>
                    <?php if ($iMtQty > 0): ?>
                    <strong style="font-size:12px;color:#1e293b"><?= number_format($iActQty,0) ?></strong> / <?= number_format($iMtQty,0) ?>
                    <?php endif; ?>
                    <?php else: ?>
                    <span style="color:#94a3b8">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div style="flex:1">
                            <div class="sub-pct-bar">
                                <div class="sub-pct-fill" style="width:<?= $iActual ?>%;background:<?= $iBarColor ?>"></div>
                            </div>
                        </div>
                        <div style="font-weight:800;font-size:12px;min-width:40px;text-align:right;color:<?= $iActual>=100?'#10b981':($iActual>0?$pColor:'#94a3b8') ?>">
                            <?= number_format($iActual, 0) ?>%
                        </div>
                    </div>
                    <?php if ($iActual >= 100): ?>
                    <div style="font-size:9px;color:#10b981;font-weight:700;margin-top:2px">✓ This sub-item complete → contributes <?= number_format($iWeight,0) ?>% to <?= $pName ?></div>
                    <?php elseif ($iWeight > 0): ?>
                    <div style="font-size:9px;color:var(--slate);margin-top:2px">Need 100% to fully contribute <?= number_format($iWeight,0) ?>% to <?= $pName ?></div>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <div style="font-weight:800;font-size:13px;color:<?= $pColor ?>"><?= number_format($iContrib, 2) ?>%</div>
                    <div style="font-size:9px;color:var(--slate)">of phase</div>
                </td>
                <td class="text-center">
                    <span class="s-badge <?= $iStatCls ?>"><?= $iStatLbl ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f8fafc;font-weight:700">
                    <td colspan="2" class="text-end" style="font-size:11px;color:var(--slate)">Phase Total:</td>
                    <td class="text-center" style="color:<?= $pColor ?>;font-size:13px"><?= number_format(array_sum(array_column($pItems,'weight_pct')),0) ?>%</td>
                    <td></td>
                    <td class="text-center" style="color:<?= $pColor ?>;font-size:14px;font-weight:900"><?= number_format($pActual,1) ?>%</td>
                    <td class="text-center" style="color:var(--green);font-size:13px"><?= number_format($pContrib,2) ?>%</td>
                    <td></td>
                </tr>
                <tr style="background:#e0f2fe">
                    <td colspan="7" style="font-size:11px;font-weight:700;padding:8px 10px;color:#1e40af">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong><?= $pName ?></strong> = <?= number_format($pWeight,0) ?>% of project.
                        Current: <strong><?= number_format($pActual,1) ?>%</strong> complete →
                        Project contribution: <strong style="color:var(--green)"><?= number_format($pContrib,2) ?>%</strong>
                        <?php if ($pActual < 100): ?>
                        | Remaining: <strong><?= number_format($pWeight - $pContrib,2) ?>%</strong> still to earn
                        <?php else: ?>
                        ✓ <strong style="color:#10b981">This phase fully complete!</strong>
                        <?php endif; ?>
                    </td>
                </tr>
            </tfoot>
        </table>
        <?php endif; ?>

        <!-- Monthly Target Link -->
        <?php if ($pMtCount > 0): ?>
        <div style="text-align:right;padding-top:8px">
            <a href="<?= $base ?>/con-monthly-targets/<?= $projectId ?>" style="font-size:11.5px;color:var(--pri);font-weight:600;text-decoration:none">
                <i class="bi bi-calendar-month me-1"></i>View Monthly Targets →
            </a>
        </div>
        <?php endif; ?>
    </div>
    </div>
</div>
<?php endforeach; ?>

<!-- ░░ Project Total Summary ░░ -->
<div style="background:#5e4cdc;color:#fff;border-radius:16px;padding:20px 24px;margin-top:8px">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:rgba(255,255,255,.8);margin-bottom:4px">
                <i class="bi bi-calculator me-1"></i>Total Project Progress Formula
            </div>
            <div style="font-size:12.5px;color:#fff;font-weight:600">
                = Σ (Phase Actual% × Phase Weight%) for all <?= count($phases) ?> phases
            </div>
            <div style="font-size:11px;color:rgba(255,255,255,.8);margin-top:4px">
                When all sub-items of each phase reach 100% → Phase complete → Project reaches 100%
            </div>
        </div>
        <div style="text-align:center">
            <div style="font-size:42px;font-weight:900;color:#fff;line-height:1"><?= number_format($totalProjectProgress,2) ?>%</div>
            <div style="font-size:11px;color:#fff;font-weight:700">Overall Project Progress</div>
        </div>
    </div>
    <!-- Stacked bar: each phase contribution -->
    <div style="height:14px;border-radius:7px;background:#dcfce7;overflow:hidden;margin-top:14px;display:flex">
    <?php foreach ($phases as $pi2 => $ph2):
        $c2 = $phaseColors[$pi2 % count($phaseColors)];
        $w2 = min(100, (float)($ph2['contribution'] ?? 0));
        if ($w2 <= 0) continue;
    ?>
        <div style="width:<?= $w2 ?>%;background:<?= $c2 ?>;transition:width .4s"
             title="<?= htmlspecialchars($ph2['phase_name']) ?>: <?= number_format($w2,2) ?>%"></div>
    <?php endforeach; ?>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px">
    <?php foreach ($phases as $pi3 => $ph3):
        $c3 = $phaseColors[$pi3 % count($phaseColors)];
        $contrib3 = (float)($ph3['contribution'] ?? 0);
    ?>
        <div style="display:flex;align-items:center;gap:4px;font-size:10.5px;font-weight:600;color:#166534">
            <span style="width:10px;height:10px;border-radius:2px;background:<?= $c3 ?>;display:inline-block;flex-shrink:0"></span>
            <?= htmlspecialchars($ph3['phase_name']) ?> (<?= number_format($contrib3,2) ?>%)
        </div>
    <?php endforeach; ?>
    </div>
</div>

</div><!-- px-1 -->

<?php
$base           = $GLOBALS['base_url'];
$project        = $project        ?? [];
$phase          = $phase          ?? [];
$monthlyTarget  = $monthlyTarget  ?? [];
$workItems      = $workItems      ?? [];
$itemAgg        = $itemAgg        ?? [];
$weeklySessions = $weeklySessions ?? [];
$teamPerf       = $teamPerf       ?? [];
$delayMap       = $delayMap       ?? [];
$projectId      = (int)($project['id'] ?? 0);
$phaseId        = (int)($phase['id']   ?? 0);

$monthDate   = $monthDate ?? 'now';
$monthLabel  = date('F Y', strtotime($monthDate));
$monthStart  = date('d M Y', strtotime(date('Y-m-01', strtotime($monthDate))));
$monthEnd    = date('d M Y', strtotime(date('Y-m-t',  strtotime($monthDate))));

$perfScore = (int)($perfScore ?? 0);
$scoreColor = $perfScore >= 80 ? '#10b981' : ($perfScore >= 60 ? '#f59e0b' : '#ef4444');
$scoreLabel = $perfScore >= 80 ? 'Good Performance' : ($perfScore >= 60 ? 'Moderate' : 'Poor Performance');

$totalItems    = count($workItems);
$achievedItems = 0; $partialItems = 0; $delayedItems = 0;
foreach ($workItems as $wi) {
    $tid = (int)$wi['id'];
    $st  = $itemAgg[$tid]['status'] ?? 'Not Started';
    if ($st === 'Completed')  $achievedItems++;
    elseif ($st === 'Delayed') $delayedItems++;
    else $partialItems++;
}

// Each item's actual_progress_pct is its share contribution — SUM gives correct total
$totalAchievementPct = array_sum(array_column($workItems, 'actual_progress_pct'));
$totalPlannedProRated = $totalPlannedProRated ?? $totalPlannedPct ?? 0;
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
body { font-family:'Inter',sans-serif }
:root { --pri:var(--clr-theme-primary);--green:#10b981;--amber:#f59e0b;--red:#ef4444;--slate:var(--clr-text-muted, #64748b) }

/* ── KPI row ── */
.rev-kpi-row { display:flex;gap:12px;flex-wrap:wrap;margin-bottom:22px }
.rev-kpi { background:var(--clr-bg-card, #fff);border:1px solid var(--clr-border-1, #e2e8f0);border-radius:14px;padding:14px 18px;flex:1;min-width:150px;display:flex;flex-direction:column;gap:3px }
.rev-kpi-icon { width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:17px;margin-bottom:6px }
.rev-kpi-val  { font-size:22px;font-weight:900;line-height:1 }
.rev-kpi-lbl  { font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--clr-text-muted, var(--slate)) }
.rev-kpi-sub  { font-size:10.5px;color:var(--clr-text-muted, var(--slate)) }

/* ── Cards ── */
.rev-card { background:var(--clr-bg-card, #fff);border:1px solid var(--clr-border-1, #e2e8f0);border-radius:16px;overflow:hidden;margin-bottom:18px }
.rev-card-hd { padding:14px 20px;border-bottom:1px solid var(--clr-border-1, #f1f5f9);display:flex;align-items:center;justify-content:space-between }
.rev-card-title { font-size:13px;font-weight:700;color:var(--clr-text-muted, #475569);display:flex;align-items:center;gap:7px;text-transform:uppercase;letter-spacing:.4px }
.rev-card-body { padding:16px 20px }

/* ── Donut ── */
.donut-wrap { position:relative;display:inline-flex;align-items:center;justify-content:center }
.donut-center { position:absolute;text-align:center }
.donut-val { font-size:24px;font-weight:900;color:var(--clr-text-body, #1e293b) }
.donut-lbl { font-size:9px;font-weight:700;color:var(--clr-text-muted, var(--slate));text-transform:uppercase }

/* ── Tables ── */
.rev-table { width:100%;border-collapse:collapse;font-size:12px }
.rev-table th { font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:var(--clr-text-muted, var(--slate));padding:8px 10px;border-bottom:2px solid var(--clr-border-1, #e2e8f0);white-space:nowrap }
.rev-table td { padding:8px 10px;border-bottom:1px solid var(--clr-border-1, #f8fafc);vertical-align:middle;color:var(--clr-text-body, #1e293b) }
.rev-table tbody tr:hover td { background:var(--clr-bg-card-hover, rgba(0, 0, 0, 0.02)) }
.rev-table tr:last-child td { border-bottom:none }

/* Subrow (work type under item) */
.wt-subrow td { background:rgba(0, 0, 0, 0.02);font-size:11px;color:var(--clr-text-muted, #64748b);padding:5px 10px 5px 26px;border-bottom:1px solid var(--clr-border-1, #f1f5f9) }

/* ── Status badges ── */
.sb { display:inline-flex;align-items:center;font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px }
.sb-completed   { background:rgba(16, 185, 129, 0.15);color:#10b981; }
.sb-in-progress { background:rgba(37, 99, 235, 0.15);color:#2563eb; }
.sb-delayed     { background:rgba(239, 68, 68, 0.15);color:#ef4444; }
.sb-not-started { background:rgba(148, 163, 184, 0.15);color:var(--clr-text-muted, #64748b) }
.sb-at-risk     { background:rgba(245, 158, 11, 0.15);color:#d97706; }
.sb-partial     { background:rgba(245, 158, 11, 0.15);color:#d97706; }

/* ── Progress bar ── */
.pbar { height:6px;border-radius:3px;background:var(--clr-border-1, #f1f5f9);overflow:hidden;min-width:60px }
.pbar-fill { height:100%;border-radius:3px }

/* ── Week cards ── */
.week-grid { display:flex;gap:10px;flex-wrap:wrap }
.week-card { flex:1;min-width:140px;border-radius:12px;border:1px solid var(--clr-border-1, #e2e8f0);padding:14px 16px;text-align:center }
.week-card.completed { border-color:#10b981;background:rgba(16, 185, 129, 0.08) }
.week-card.partial   { border-color:#f59e0b;background:rgba(245, 158, 11, 0.08) }
.week-card.not-started{border-color:var(--clr-border-1, #e2e8f0);background:rgba(148, 163, 184, 0.05) }

/* ── Legend dot ── */
.ldot { width:10px;height:10px;border-radius:50%;flex-shrink:0 }
</style>

<div class="px-3 py-2">

<!-- Breadcrumb + Header -->
<div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px">
    <div>
        <div style="font-size:11.5px;color:var(--slate);margin-bottom:5px">
            <a href="<?= $base ?>/con-projects" style="color:var(--pri);text-decoration:none">Projects</a> ›
            <a href="<?= $base ?>/con-monthly-review/<?= $projectId ?>" style="color:var(--pri);text-decoration:none"><?= htmlspecialchars($project['name']??'') ?></a> ›
            <?= htmlspecialchars($phase['phase_name']??'') ?> ›
            <?= $monthLabel ?>
        </div>
        <h1 style="font-size:22px;font-weight:900;color:var(--clr-text-body, #1e293b);margin:0">
            <i class="bi bi-calendar-check-fill me-2" style="color:var(--pri)"></i>Monthly Progress Review Dashboard
        </h1>
        <div style="font-size:12px;color:var(--slate);margin-top:4px">
            Review monthly target vs actual progress, delays, risks, and management actions.
        </div>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <a href="<?= $base ?>/con-monthly-review/<?= $projectId ?>"
           style="padding:8px 18px;border:1px solid var(--clr-border-1, #e2e8f0);border-radius:9px;font-size:12.5px;font-weight:600;color:var(--clr-text-body, #475569);text-decoration:none;background:var(--clr-bg-card, #fff)">
            ← Back
        </a>
        <a href="<?= $base ?>/con-monthly-target/<?= $targetId ?>"
           style="padding:8px 18px;background:var(--red);color:#fff;border-radius:9px;font-size:12.5px;font-weight:700;text-decoration:none">
            Edit Target
        </a>
    </div>
</div>

<!-- KPI Row -->
<div class="rev-kpi-row">
    <!-- Review Month -->
    <div class="rev-kpi">
        <div class="rev-kpi-icon" style="background:rgba(var(--clr-theme-primary-rgb),.08)"><i class="bi bi-calendar3" style="color:var(--clr-theme-secondary)"></i></div>
        <div class="rev-kpi-lbl">Review Month</div>
        <div class="rev-kpi-val" style="font-size:18px;color:var(--clr-theme-primary)"><?= $monthLabel ?></div>
        <div class="rev-kpi-sub"><?= $monthStart ?> – <?= $monthEnd ?></div>
    </div>
    <!-- Planned Progress -->
    <div class="rev-kpi">
        <div class="rev-kpi-icon" style="background:rgba(59, 130, 246, 0.15)"><i class="bi bi-bullseye" style="color:var(--clr-theme-primary, #3b82f6)"></i></div>
        <div class="rev-kpi-lbl">Planned Progress (To Date)</div>
        <div class="rev-kpi-val" style="color:var(--clr-theme-primary, #3b82f6)"><?= number_format($totalPlannedProRated,1) ?>%</div>
        <div class="rev-kpi-sub" style="margin-bottom:4px">of Overall Project</div>
        <div style="font-size:9.5px;font-weight:700;background:rgba(59, 130, 246, 0.1);color:var(--clr-theme-primary, #3b82f6);padding:2px 8px;border-radius:6px;align-self:flex-start;display:inline-flex;align-items:center;gap:4px">
            <i class="bi bi-layers-half"></i>Monthly Phase Scope: <?= number_format($totalPlannedPct,1) ?>%
        </div>
    </div>
    <!-- Actual Progress -->
    <div class="rev-kpi">
        <div class="rev-kpi-icon" style="background:rgba(16, 185, 129, 0.15)"><i class="bi bi-check-circle-fill" style="color:#10b981"></i></div>
        <div class="rev-kpi-lbl">Actual Progress</div>
        <div class="rev-kpi-val" style="color:#10b981"><?= number_format($totalActualPct,1) ?>%</div>
        <div class="rev-kpi-sub">of Overall Project</div>
    </div>
    <!-- Delay Gap -->
    <div class="rev-kpi">
        <div class="rev-kpi-icon" style="background:<?= $delayGap>=0?'rgba(16, 185, 129, 0.15)':'rgba(239, 68, 68, 0.15)' ?>">
            <i class="bi bi-<?= $delayGap>=0?'arrow-up':'arrow-down' ?>-circle-fill" style="color:<?= $delayGap>=0?'#10b981':'#ef4444' ?>"></i>
        </div>
        <div class="rev-kpi-lbl">Delay Gap</div>
        <div class="rev-kpi-val" style="color:<?= $delayGap>=0?'#10b981':'#ef4444' ?>"><?= ($delayGap>=0?'+':'').number_format($delayGap,1) ?>%</div>
        <div class="rev-kpi-sub"><?= $delayGap>=0?'Ahead of Schedule':'Behind Schedule' ?></div>
    </div>
    <!-- Performance Score -->
    <div class="rev-kpi">
        <div class="rev-kpi-icon" style="background:rgba(245, 158, 11, 0.15)"><i class="bi bi-star-fill" style="color:#d97706"></i></div>
        <div class="rev-kpi-lbl">Monthly Performance Score</div>
        <div class="rev-kpi-val" style="color:<?= $scoreColor ?>"><?= $perfScore ?><span style="font-size:12px;font-weight:600">/100</span></div>
        <div class="rev-kpi-sub"><?= $scoreLabel ?></div>
    </div>
    <!-- Risk Status -->
    <div class="rev-kpi">
        <div class="rev-kpi-icon" style="background:<?= abs($delayGap)>15?'rgba(239, 68, 68, 0.15)':(abs($delayGap)>5?'rgba(245, 158, 11, 0.15)':'rgba(16, 185, 129, 0.15)') ?>">
            <i class="bi bi-shield-exclamation" style="color:<?= $riskColor ?>"></i>
        </div>
        <div class="rev-kpi-lbl">Project Risk Status</div>
        <div class="rev-kpi-val" style="font-size:15px;color:<?= $riskColor ?>"><?= $riskStatus ?></div>
        <div class="rev-kpi-sub">Monitor Closely</div>
    </div>
</div>

<!-- Main 2-column layout -->
<div style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-start">

    <!-- LEFT: Planned vs Actual donut -->
    <div style="flex:0 0 220px;min-width:200px">
        <div class="rev-card">
            <div class="rev-card-hd"><div class="rev-card-title"><i class="bi bi-pie-chart-fill text-primary"></i>Planned vs Actual</div></div>
            <div class="rev-card-body" style="text-align:center">
                <?php
                $planD = min(100,(float)$totalPlannedProRated);
                $actD  = min(100,(float)$totalActualPct);
                $circ  = 2 * pi() * 50;
                $planOff = $circ - ($planD/100*$circ);
                $actOff  = $circ - ($actD /100*$circ);
                ?>
                <div class="donut-wrap" style="margin:0 auto 14px;width:130px;height:130px">
                    <svg viewBox="0 0 120 120" width="130" height="130" style="transform:rotate(-90deg)">
                        <circle cx="60" cy="60" r="50" fill="none" stroke="var(--clr-border-1, #f1f5f9)" stroke-width="16"/>
                        <circle cx="60" cy="60" r="50" fill="none" stroke="rgba(59, 130, 246, 0.25)" stroke-width="16"
                                stroke-dasharray="<?= $circ ?>" stroke-dashoffset="<?= $planOff ?>" stroke-linecap="round"/>
                        <circle cx="60" cy="60" r="50" fill="none" stroke="#10b981" stroke-width="10"
                                stroke-dasharray="<?= $circ ?>" stroke-dashoffset="<?= $actOff ?>" stroke-linecap="round"/>
                    </svg>
                    <div class="donut-center">
                        <div class="donut-val" style="color:<?= $delayGap>=0?'#10b981':'#ef4444' ?>"><?= ($delayGap>=0?'+':'').number_format($delayGap,0) ?>%</div>
                        <div class="donut-lbl">Gap</div>
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:8px;text-align:left">
                    <div style="display:flex;align-items:center;gap:8px">
                        <div class="ldot" style="background:rgba(59, 130, 246, 0.5)"></div>
                        <div style="flex:1;font-size:11px;color:var(--slate)">Planned (To Date)</div>
                        <div style="font-weight:800;font-size:13px;color:var(--clr-theme-primary, #1d4ed8)"><?= number_format($totalPlannedProRated,1) ?>%</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px">
                        <div class="ldot" style="background:#10b981"></div>
                        <div style="flex:1;font-size:11px;color:var(--slate)">Actual Progress</div>
                        <div style="font-weight:800;font-size:13px;color:#10b981"><?= number_format($totalActualPct,1) ?>%</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px">
                        <div class="ldot" style="background:<?= $delayGap>=0?'#10b981':'#ef4444' ?>"></div>
                        <div style="flex:1;font-size:11px;color:var(--slate)">Delay Gap</div>
                        <div style="font-weight:800;font-size:13px;color:<?= $delayGap>=0?'#10b981':'#ef4444' ?>"><?= ($delayGap>=0?'+':'').number_format($delayGap,1) ?>%</div>
                    </div>
                </div>

                <!-- Bars -->
                <div style="margin-top:14px;display:flex;flex-direction:column;gap:6px">
                    <div>
                        <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--slate);margin-bottom:3px"><span>Planned (To Date)</span><span><?= number_format($totalPlannedProRated,0) ?>%</span></div>
                        <div class="pbar"><div class="pbar-fill" style="width:<?= min(100,$totalPlannedProRated) ?>%;background:rgba(59, 130, 246, 0.5)"></div></div>
                    </div>
                    <div>
                        <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--slate);margin-bottom:3px"><span>Actual</span><span><?= number_format($totalActualPct,0) ?>%</span></div>
                        <div class="pbar"><div class="pbar-fill" style="width:<?= min(100,$totalActualPct) ?>%;background:#10b981"></div></div>
                    </div>
                    <?php if ($delayGap < 0): ?>
                    <div>
                        <div style="display:flex;justify-content:space-between;font-size:10px;color:#ef4444;margin-bottom:3px"><span>Delay Gap</span><span><?= number_format(abs($delayGap),0) ?>%</span></div>
                        <div class="pbar"><div class="pbar-fill" style="width:<?= min(100,abs($delayGap)) ?>%;background:#ef4444"></div></div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if ($delayGap < 0): ?>
                <div style="margin-top:12px;background:rgba(239, 68, 68, 0.1);border:1px solid rgba(239, 68, 68, 0.2);border-radius:8px;padding:8px 10px;font-size:10.5px;color:var(--clr-theme-danger, #ef4444)">
                    <i class="bi bi-info-circle me-1"></i>
                    Delay of <?= number_format(abs($delayGap),1) ?> percentage points compared to planned progress.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- MIDDLE: Work Performance Table            <div class="rev-card-hd">
                <div class="rev-card-title"><i class="bi bi-table text-primary"></i>Monthly Work Performance</div>
                <span style="font-size:11px;background:var(--clr-bg-body, #f1f5f9);color:var(--clr-text-muted, #475569);padding:2px 10px;border-radius:999px;font-weight:700">
                    <?= $totalItems ?> Items
                </span>
            </div>
            <div style="overflow-x:auto">
            <table class="rev-table">
                <thead>
                    <tr>
                        <th style="width:24px">#</th>
                        <th>Work Item</th>
                        <th>Unit</th>
                        <th style="text-align:center">Target Qty</th>
                        <th style="text-align:center">Achieved Qty</th>
                        <th style="text-align:center;width:90px">Achievement%</th>
                        <th>Status</th>
                        <th>Delay Reason</th>
                        <th>Responsible</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $rowN = 0;
                foreach ($workItems as $wi):
                    $rowN++;
                    $tid       = (int)$wi['id'];
                    $agg       = $itemAgg[$tid] ?? [];
                    $targetQty = (float)$wi['monthly_target_qty'];
                    $achQty    = (float)($wi['actual_qty'] ?? 0);
                    $achPct    = $targetQty > 0 ? min(100, $achQty / $targetQty * 100) : 0;
                    $status    = $agg['status']   ?? $wi['status'] ?? 'Not Started';
                    $team      = !empty($agg['responsible_team']) ? $agg['responsible_team'] : ($wi['responsible_team'] ?? '—');
                    $delayRsn  = $agg['delay_reason'] ?? '';
                    $wtName    = $wi['work_type_name'] ?? '';

                    // Status badge class
                    $sbCls = 'sb-not-started';
                    if ($status==='Completed')  $sbCls='sb-completed';
                    elseif ($status==='Delayed') $sbCls='sb-delayed';
                    elseif ($status==='In Progress') $sbCls='sb-in-progress';
                    elseif ($status==='At Risk') $sbCls='sb-at-risk';

                    // Color
                    $pctCol = $achPct>=100?'#10b981':($achPct>=($wi['planned_progress_pct']??0)?'var(--clr-theme-primary, #3b82f6)':($achPct>0?'#f59e0b':'var(--clr-text-muted, #94a3b8)'));
                    $barCol = $achPct>=100?'#10b981':($achPct>50?'var(--clr-theme-primary)':'#f59e0b');
                ?>
                <tr>
                    <td style="color:var(--clr-text-muted, #94a3b8);font-weight:700"><?= $rowN ?></td>
                    <td>
                        <div style="font-weight:700;color:var(--clr-text-body, #1e293b)"><?= htmlspecialchars($wi['work_item_name']) ?></div>
                        <?php if ($wtName): ?>
                        <div style="font-size:10px;color:var(--clr-text-muted, #94a3b8);margin-top:2px">
                            <i class="bi bi-tag me-1"></i><?= htmlspecialchars($wtName) ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td style="color:var(--clr-text-muted, #64748b)"><?= htmlspecialchars($wi['unit']??'') ?></td>
                    <td style="text-align:center;font-weight:700"><?= number_format($targetQty,0,'.',',') ?></td>
                    <td style="text-align:center;font-weight:800;color:var(--clr-theme-primary, #1d4ed8)"><?= number_format($achQty,0,'.',',') ?></td>
                    <td style="text-align:center">
                        <div style="font-weight:900;font-size:12.5px;color:<?= $pctCol ?>"><?= number_format($achPct,1) ?>%</div>
                        <div class="pbar" style="margin-top:3px">
                            <div class="pbar-fill" style="width:<?= $achPct ?>%;background:<?= $barCol ?>"></div>
                        </div>
                        <?php if ($achPct >= $wi['planned_progress_pct'] && $wi['planned_progress_pct']>0): ?>
                        <div style="font-size:9px;color:#10b981;font-weight:700;margin-top:2px">Achieved ✓</div>
                        <?php elseif ($wi['planned_progress_pct']>0): ?>
                        <div style="font-size:9px;color:#ef4444;font-weight:700;margin-top:2px">Partial</div>
                        <?php endif; ?>
                    </td>
                    <td><span class="sb <?= $sbCls ?>"><?= $status ?></span></td>
                    <td style="font-size:11px;color:var(--clr-text-muted, #64748b);max-width:120px">
                        <?= $delayRsn ? htmlspecialchars(mb_substr($delayRsn,0,40)) : '<span style="color:var(--clr-border-1, #cbd5e1)">—</span>' ?>
                    </td>
                    <td style="font-size:11px;color:var(--clr-text-body, #475569)"><?= htmlspecialchars($team) ?></td>
                </tr>ize:11px;color:#475569"><?= htmlspecialchars($team) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background:rgba(22, 163, 74, 0.1);border-top:2px solid rgba(22, 163, 74, 0.3)">
                        <td colspan="3" style="padding:9px 10px;font-weight:800;color:#10b981">
                            Total Achievement
                        </td>
                        <td style="text-align:center;font-weight:700;color:var(--clr-text-body, #374151)">
                            <?= number_format(array_sum(array_column($workItems,'monthly_target_qty')),0,'.',',') ?>
                        </td>
                        <td style="text-align:center;font-weight:700;color:var(--clr-theme-primary, #1d4ed8)">
                            <?= number_format(array_sum(array_column($workItems,'actual_qty')),0,'.',',') ?>
                        </td>
                        <td style="text-align:center">
                            <?php
                            $totalTargetQty = array_sum(array_column($workItems, 'monthly_target_qty'));
                            $totalActualQty = array_sum(array_column($workItems, 'actual_qty'));
                            
                            $trueAchievementPct = 0;
                            foreach ($workItems as $wi) {
                                $targetQty = (float)$wi['monthly_target_qty'];
                                $achQty    = (float)($wi['actual_qty'] ?? 0);
                                $trueAchievementPct += $targetQty > 0 ? min(100, $achQty / $targetQty * 100) : 0;
                            }
                            ?>
                            <span style="font-weight:900;font-size:13px;color:<?= $trueAchievementPct>=100?'#10b981':'var(--clr-theme-primary, #6d28d9)' ?>">
                                <?= number_format($trueAchievementPct,1) ?>% Achieved
                            </span>
                        </td>
                        <td colspan="3" style="font-size:10.5px;color:var(--clr-text-muted, #166534);font-weight:600">
                            Items: <?= $totalItems ?> |
                            Completed: <?= $achievedItems ?> |
                            Delayed: <?= $delayedItems ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
            </div>
        </div>
    </div>

</div><!-- main 2-col -->

<!-- Bottom row: Delay Analytics + Team Performance -->
<div style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-start">

    <!-- Delay Reason Analytics -->
    <div style="flex:0 0 280px;min-width:240px">
        <div class="rev-card">
            <div class="rev-card-hd"><div class="rev-card-title"><i class="bi bi-exclamation-triangle-fill text-danger"></i>Delay Reason Analytics <small style="font-size:9px;font-weight:500">(By Impact)</small></div></div>
            <div class="rev-card-body">
                <?php
                $delayTotal = array_sum($delayMap);
                $delayColors = ['#ef4444','#f59e0b','var(--clr-theme-secondary)','#3b82f6','#10b981','#ec4899'];
                $di = 0;
                // Donut for delay
                $delayCirc = 2 * pi() * 38;
                $delayOffset = 0;
                ?>
                <?php if ($delayTotal > 0): ?>
                <div style="display:flex;align-items:center;gap:18px">
                    <div class="donut-wrap" style="width:100px;height:100px;flex-shrink:0">
                        <svg viewBox="0 0 100 100" width="100" height="100" style="transform:rotate(-90deg)">
                            <circle cx="50" cy="50" r="38" fill="none" stroke="var(--clr-border-1, #f1f5f9)" stroke-width="14"/>
                            <?php foreach ($delayMap as $reason => $cnt):
                                if ($cnt === 0) { $di++; continue; }
                                $pct = $cnt / $delayTotal;
                                $dash = $pct * $delayCirc;
                                $col  = $delayColors[$di % count($delayColors)];
                            ?>
                            <circle cx="50" cy="50" r="38" fill="none"
                                    stroke="<?= $col ?>" stroke-width="14"
                                    stroke-dasharray="<?= $dash ?> <?= $delayCirc - $dash ?>"
                                    stroke-dashoffset="<?= -$delayOffset ?>"/>
                            <?php $delayOffset += $dash; $di++; endforeach; ?>
                        </svg>
                        <div class="donut-center">
                            <div style="font-size:16px;font-weight:900;color:#ef4444"><?= $delayTotal ?></div>
                            <div style="font-size:8px;font-weight:700;color:var(--slate);text-transform:uppercase">Issues</div>
                        </div>
                    </div>
                    <div style="flex:1;display:flex;flex-direction:column;gap:6px">
                    <?php $di=0; foreach ($delayMap as $reason => $cnt):
                        $col = $delayColors[$di % count($delayColors)];
                        $pct2 = $delayTotal > 0 ? round($cnt/$delayTotal*100) : 0;
                        $di++;
                    ?>
                    <div style="display:flex;align-items:center;gap:6px">
                        <div class="ldot" style="background:<?= $col ?>"></div>
                        <div style="flex:1;font-size:10.5px;color:var(--clr-text-body, #475569)"><?= $reason ?></div>
                        <div style="font-size:10.5px;font-weight:700;color:var(--clr-text-body, #1e293b)"><?= $pct2 ?>%</div>
                    </div>
                    <?php endforeach; ?>
                    </div>
                </div>
                <div style="margin-top:14px;text-align:center;background:rgba(239, 68, 68, 0.1);border-radius:8px;padding:8px;font-size:11.5px;font-weight:800;color:var(--clr-theme-danger, #ef4444)">
                    Total Impact: <?= number_format(abs($delayGap),1) ?>%
                </div>
                <?php else: ?>
                <div style="text-align:center;color:var(--clr-text-muted, #94a3b8);font-size:12px;padding:16px 0">
                    <i class="bi bi-check-circle-fill text-success" style="font-size:28px;display:block;margin-bottom:8px"></i>
                    No delays or issues flagged
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Contractor / Team Performance -->
    <div style="flex:1;min-width:300px">
        <div class="rev-card">
            <div class="rev-card-hd"><div class="rev-card-title"><i class="bi bi-people-fill text-primary"></i>Contractor / Team Performance</div></div>
            <div style="overflow-x:auto">
            <table class="rev-table">
                <thead>
                    <tr>
                        <th style="width:24px">#</th>
                        <th>Team / Contractor</th>
                        <th style="text-align:center">Planned Progress</th>
                        <th style="text-align:center">Actual Progress</th>
                        <th style="text-align:center">Performance Score</th>
                        <th style="text-align:center;width:60px">Trend</th>
                    </tr>
                </thead>
                <tbody>
                <?php $ti=1; foreach ($teamPerf as $team => $tp):
                    $tPlan = (float)$tp['planned_sum'];
                    $tAct  = (float)$tp['achieved_sum'];
                    $tScore = $tPlan > 0 ? min(100, round($tAct / $tPlan * 100)) : 0;
                    $tScoreCol = $tScore >= 80 ? '#10b981' : ($tScore >= 60 ? '#f59e0b' : '#ef4444');
                    $tTrend = $tAct >= $tPlan ? '↑' : '↓';
                    $tTrendCol = $tAct >= $tPlan ? '#10b981' : '#ef4444';
                ?>
                <tr>
                    <td style="color:var(--clr-text-muted, #94a3b8);font-weight:700"><?= $ti++ ?></td>
                    <td style="font-weight:600;color:var(--clr-text-body, #1e293b)"><?= htmlspecialchars($team) ?></td>
                    <td style="text-align:center">
                        <div style="font-weight:700;font-size:12px"><?= number_format($tPlan,0) ?>%</div>
                        <div class="pbar" style="margin-top:3px">
                            <div class="pbar-fill" style="width:<?= min(100,$tPlan) ?>%;background:rgba(59, 130, 246, 0.5)"></div>
                        </div>
                    </td>
                    <td style="text-align:center">
                        <div style="font-weight:700;font-size:12px;color:var(--clr-theme-primary, #1d4ed8)"><?= number_format($tAct,0) ?>%</div>
                        <div class="pbar" style="margin-top:3px">
                            <div class="pbar-fill" style="width:<?= min(100,$tAct) ?>%;background:#10b981"></div>
                        </div>
                    </td>
                    <td style="text-align:center">
                        <span style="font-weight:800;font-size:12px;color:<?= $tScoreCol ?>"><?= $tScore ?>/100</span>
                    </td>
                    <td style="text-align:center;font-size:18px;font-weight:900;color:<?= $tTrendCol ?>"><?= $tTrend ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($teamPerf)): ?>
                <tr><td colspan="6" style="text-align:center;color:var(--clr-text-muted, #94a3b8);padding:20px;font-style:italic">
                    No team data recorded yet. Assign teams in weekly progress.
                </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>

</div><!-- bottom analytics row -->

<!-- Monthly Timeline Review: Week cards -->
<div class="rev-card">
    <div class="rev-card-hd">
        <div class="rev-card-title"><i class="bi bi-calendar-week text-primary"></i>Monthly Timeline Review <small style="font-size:9px;font-weight:500">(Week-wise Progress)</small></div>
    </div>
    <div class="rev-card-body">
        <div class="week-grid">
        <?php
        // Map sessions by week_number
        $weekMap = [];
        foreach ($weeklySessions as $ws) $weekMap[(int)$ws['week_number']] = $ws;

        // Calculate week dates from monthDate (1st of month)
        $monthDT = new DateTime($monthDate);
        for ($wk = 1; $wk <= 4; $wk++):
            $ws = $weekMap[$wk] ?? null;
            $wStart = (clone $monthDT)->modify('+' . (($wk-1)*7) . ' days');
            $wEnd   = $wk < 4
                    ? (clone $wStart)->modify('+6 days')
                    : new DateTime(date('Y-m-t', strtotime($monthDate)));

            // Calculate target qty for this week
            $wTarget = 0;
            foreach ($workItems as $wi) {
                $wTarget += (float)($wi['week_' . $wk . '_qty'] ?? 0);
            }

            // Aggregate weekly achievement for this week
            $wAch = 0;
            foreach ($allWPI ?? [] as $wpi) {
                if ((int)$wpi['week_number'] === $wk) $wAch += (float)$wpi['achieved_qty'];
            }

            // Calculate calculated week percentage
            $wPct = $wTarget > 0 ? ($wAch / $wTarget * 100) : ($ws ? (float)$ws['overall_progress_pct'] : 0);

            $wStatus = $ws ? $ws['entry_status'] : 'Not Started';

            $wCardCls = 'not-started';
            if ($wPct >= 100 || $wStatus === 'Submitted' || $wStatus === 'Approved') {
                $wCardCls = 'completed';
            } elseif ($wPct > 0 || $ws) {
                $wCardCls = 'partial';
            }

            $wColBar = $wCardCls==='completed'?'#10b981':($wCardCls==='partial'?'#f59e0b':'#e2e8f0');
            $wLbl    = $wPct >= 100 || $wStatus === 'Submitted' || $wStatus === 'Approved' ? 'Completed' : ($wPct > 0 ? 'In Progress' : 'Not Started');
            ?>
            <a href="<?= $base ?>/con-weekly-progress/<?= $targetId ?>?week=<?= $wk ?>" class="week-card <?= $wCardCls ?>" style="text-decoration:none;display:block;color:inherit;transition:all 0.2s" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(0,0,0,0.05)';" onmouseout="this.style.transform='none';this.style.boxShadow='none';">
            <div style="font-size:11px;font-weight:700;color:var(--clr-text-muted, #94a3b8);margin-bottom:6px">Week <?= $wk ?></div>
            <div style="font-size:11px;font-weight:600;color:var(--clr-text-muted, #64748b);margin-bottom:10px">
                <?= $wStart->format('d M') ?> – <?= $wEnd->format('d M Y') ?>
            </div>
            <div style="font-size:26px;font-weight:900;color:<?= $wCardCls==='completed'?'#10b981':($wCardCls==='partial'?'#f59e0b':'var(--clr-text-muted, #94a3b8)') ?>;line-height:1">
                <?= number_format($wPct,0) ?>%
            </div>
            <div style="font-size:10px;font-weight:600;color:var(--clr-text-muted, #94a3b8);margin-bottom:8px"><?= $wLbl ?></div>
            <div class="pbar" style="height:5px">
                <div class="pbar-fill" style="width:<?= min(100,$wPct) ?>%;background:<?= $wColBar ?>"></div>
            </div>
            <div style="font-size:10.5px;color:var(--clr-text-muted, #64748b);margin-top:8px;display:flex;flex-direction:column;gap:2px;text-align:left;border-top:1px solid var(--clr-border-1, #f1f5f9);padding-top:6px">
                <div>Target Qty: <strong style="color:var(--clr-text-body, #475569)"><?= number_format($wTarget,0,'.',',') ?></strong></div>
                <div>Achieved Qty: <strong style="color:var(--clr-theme-primary, #1d4ed8)"><?= number_format($wAch,0,'.',',') ?></strong></div>
            </div>
        </a>
        <?php endfor; ?>
        </div>
 
        <!-- Overall weekly bar -->
        <div style="margin-top:16px;background:var(--clr-bg-body, #f8fafc);border-radius:10px;padding:12px 16px;display:flex;align-items:center;gap:12px">
            <div style="font-size:11px;font-weight:700;color:var(--clr-text-muted, #64748b);white-space:nowrap">Overall Weekly Performance:</div>
            <div class="pbar" style="flex:1;height:8px">
                <div class="pbar-fill" style="width:<?= min(100,$totalActualPct) ?>%;background:linear-gradient(90deg,var(--clr-theme-primary),#10b981)"></div>
            </div>
            <div style="font-size:14px;font-weight:900;color:var(--clr-theme-primary);white-space:nowrap"><?= number_format($totalActualPct,0) ?>%</div>
        </div>mary);white-space:nowrap"><?= number_format($totalActualPct,0) ?>%</div>
        </div>
    </div>
</div>

<!-- ░░ Full-width: Sub-Item Weekly Breakdown ░░ -->
<div class="rev-card" style="margin-top:4px">
    <div class="rev-card-hd" style="justify-content:space-between">
        <div class="rev-card-title">
            <i class="bi bi-grid-3x3-gap-fill text-primary"></i>
            Sub-Item Progress Breakdown
            <small style="font-size:9px;font-weight:500">(by Work Type &amp; Week)</small>
        </div>
        <span style="font-size:10.5px;color:var(--slate)">
            <?= date('F Y', strtotime($monthDate)) ?>
        </span>
    </d        <thead>
            <tr style="background:var(--clr-bg-body, #f8fafc)">
                <th style="min-width:130px">Work Item</th>
                <th style="min-width:120px">Work Type</th>
                <th style="text-align:center;width:50px">Unit</th>
                <th style="text-align:center;width:70px">M.Target</th>
                <?php for($w=1;$w<=4;$w++): ?>
                <th style="text-align:center;width:80px">
                    <a href="<?= $base ?>/con-weekly-progress/<?= $targetId ?>?week=<?= $w ?>" style="color:var(--clr-theme-primary);text-decoration:none;font-weight:800;border-bottom:1px dashed var(--clr-border-1, #c7d2fe);display:inline-block" title="Go to Week <?= $w ?> progress entry">
                        Week <?= $w ?>
                    </a>
                    <?php
                    // show week date range in header
                    $whStart = (new DateTime($monthDate))->modify('+' . (($w-1)*7) . ' days');
                    $whEnd   = $w < 4
                             ? (clone $whStart)->modify('+6 days')
                             : new DateTime(date('Y-m-t', strtotime($monthDate)));
                    ?>
                    <div style="font-size:8px;font-weight:400;color:var(--clr-text-muted, #94a3b8);white-space:nowrap">
                        <?= $whStart->format('d M') ?>–<?= $whEnd->format('d M') ?>
                    </div>
                </th>
                <?php endfor; ?>
                <th style="text-align:center;width:70px;color:#10b981">Total Ach.</th>
                <th style="text-align:center;width:80px">Progress</th>
                <th style="text-align:center;min-width:140px">Site Photos</th>
            </tr>
        </thead>
        <tbody>
        <?php
        // Build lookup: target_item_id → [week_number → [achieved_qty, photo_path]]
        $wpiByItem = [];
        foreach ($allWPI ?? [] as $wpi) {
            $tid = (int)$wpi['target_item_id'];
            $wkn = (int)$wpi['week_number'];
            if (!isset($wpiByItem[$tid][$wkn])) {
                $wpiByItem[$tid][$wkn] = ['qty' => 0, 'photos' => []];
            }
            $wpiByItem[$tid][$wkn]['qty'] += (float)$wpi['achieved_qty'];
            if (!empty($wpi['photo_path'])) {
                $wpiByItem[$tid][$wkn]['photos'][] = $wpi['photo_path'];
            }
        }
 
        // Group work items by work_item_name so same sub-item rows are grouped
        $groupedItems = [];
        foreach ($workItems as $wi) {
            $groupedItems[$wi['work_item_name']][] = $wi;
        }
 
        $photoIdx = 0;
        foreach ($groupedItems as $subItemName => $subRows):
            $firstRow = true;
            foreach ($subRows as $wi):
                $tid       = (int)$wi['id'];
                $targetQty = (float)$wi['monthly_target_qty'];
                $totalAch  = 0;
                foreach ($wpiByItem[$tid] ?? [] as $wkData) $totalAch += $wkData['qty'];
                // Fallback to DB actual_qty if weekly items have no data
                if ($totalAch == 0) $totalAch = (float)($wi['actual_qty'] ?? 0);
                $achPct    = $targetQty > 0 ? min(100, $totalAch / $targetQty * 100) : 0;
                $barCol    = $achPct >= 100 ? '#10b981' : ($achPct > 0 ? 'var(--clr-theme-primary)' : 'var(--clr-border-1, #e2e8f0)');
                $wtName    = $wi['work_type_name'] ?? '—';
        ?>
        <tr style="border-bottom:1px solid var(--clr-border-1, #f1f5f9)" onmouseover="this.style.background='var(--clr-bg-card-hover, rgba(0,0,0,0.02))'" onmouseout="this.style.background=''">
            <!-- Work item name (grouped: only show on first work-type row) -->
            <td style="vertical-align:middle">
                <?php if ($firstRow): ?>
                <div style="font-weight:700;color:var(--clr-text-body, #1e293b)"><?= htmlspecialchars($subItemName) ?></div>
                <div style="font-size:9px;color:var(--clr-text-muted, #94a3b8);margin-top:2px"><?= number_format($targetQty,0,'.',',') ?> planned</div>
                <?php else: ?>
                <div style="font-size:10px;color:var(--clr-text-muted, #cbd5e1);padding-left:10px">↳ same item</div>
                <?php endif; ?>
            </td>
            <!-- Work type -->
            <td style="vertical-align:middle">
                <span style="background:rgba(var(--clr-theme-primary-rgb),.08);color:var(--clr-theme-primary, #5b21b6);font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;white-space:nowrap">
                    <i class="bi bi-tag me-1"></i><?= htmlspecialchars($wtName) ?>
                </span>
            </td>
            <!-- Unit -->
            <td style="text-align:center;color:var(--clr-text-muted, #64748b);font-size:11px"><?= htmlspecialchars($wi['unit']??'') ?></td>
            <!-- Monthly target -->
            <td style="text-align:center;font-weight:700;color:var(--clr-text-body, #374151)">
                <?= $targetQty > 0 ? number_format($targetQty,0,'.',',') : '<span style="color:var(--clr-border-1, #cbd5e1)">—</span>' ?>
            </td>
            <!-- Week 1-4 qty cells -->
            <?php for ($wk=1; $wk<=4; $wk++):
                $wkData  = $wpiByItem[$tid][$wk] ?? null;
                $wkQty   = $wkData ? $wkData['qty'] : 0;
                $wkPhotos= $wkData ? ($wkData['photos'] ?? []) : [];
                // Use planned week qty from target item as reference
                $plannedWkField = 'week_' . $wk . '_qty';
                $wkPlanned = (float)($wi[$plannedWkField] ?? 0);
                $wkOnTarget = $wkPlanned > 0 && $wkQty >= $wkPlanned;
                $wkColor = $wkQty > 0 ? ($wkOnTarget ? '#10b981' : 'var(--clr-theme-primary, #1d4ed8)') : 'var(--clr-text-muted, #cbd5e1)';
            ?>
            <td style="text-align:center;vertical-align:middle;padding:0">
                <a href="<?= $base ?>/con-weekly-progress/<?= $targetId ?>?week=<?= $wk ?>" style="display:block;padding:6px 4px;text-decoration:none;color:inherit;height:100%;transition:background 0.2s" onmouseover="this.style.background='var(--clr-bg-card-hover, rgba(0,0,0,0.02))'" onmouseout="this.style.background='transparent'" title="Edit/View Week <?= $wk ?> Progress">
                    <div style="font-weight:800;font-size:12px;color:<?= $wkColor ?>">
                        <?= $wkQty > 0 ? number_format($wkQty,0,'.',',') : '—' ?>
                    </div>
                    <?php if ($wkPlanned > 0): ?>
                    <div style="font-size:8.5px;color:var(--clr-text-muted, #94a3b8)">/ <?= number_format($wkPlanned,0,'.',',') ?> plan</div>
                    <?php endif; ?>
                </a>
            </td>
            <?php endfor; ?>
            <!-- Total achieved -->
            <td style="text-align:center;vertical-align:middle">
                <div style="font-weight:900;font-size:13px;color:<?= $achPct>=100?'#10b981':'var(--clr-theme-primary, #1d4ed8)' ?>">
                    <?= number_format($totalAch,0,'.',',') ?>
                </div>
            </td>
            <!-- Progress bar + % -->
            <td style="text-align:center;vertical-align:middle;padding:6px 8px">
                <div style="font-weight:800;font-size:12px;color:<?= $barCol ?>"><?= number_format($achPct,1) ?>%</div>
                <div class="pbar" style="margin-top:4px">
                    <div class="pbar-fill" style="width:<?= $achPct ?>%;background:<?= $barCol ?>"></div>
                </div>
                <?php
                $planPctItem = (float)($wi['planned_progress_pct'] ?? 0);
                if ($planPctItem > 0 && $achPct >= $planPctItem):
                ?><div style="font-size:8.5px;color:#10b981;font-weight:700;margin-top:2px">On Target ✓</div>
                <?php elseif ($planPctItem > 0): ?>
                <div style="font-size:8.5px;color:#ef4444;font-weight:700;margin-top:2px">Behind</div>
                <?php endif; ?>
            </td>
            <!-- Site Photos column -->
            <td style="text-align:center;vertical-align:middle;padding:6px 8px">
                <?php
                // Collect all photos for this item across all weeks
                $allPhotos = [];
                for ($wk=1; $wk<=4; $wk++) {
                    $wkPh = $wpiByItem[$tid][$wk]['photos'] ?? [];
                    foreach ($wkPh as $ph) {
                        $allPhotos[] = ['wk'=>$wk, 'path'=>$ph];
                    }
                }
                ?>
                <?php if (!empty($allPhotos)): ?>
                <div style="display:flex;flex-wrap:wrap;gap:4px;justify-content:center">
                    <?php foreach ($allPhotos as $ph): $photoIdx++; ?>
                    <a href="<?= $base ?>/<?= htmlspecialchars($ph['path']) ?>" target="_blank"
                       title="Week <?= $ph['wk'] ?> — Click to view full size"
                       style="display:inline-block;position:relative">
                        <img src="<?= $base ?>/<?= htmlspecialchars($ph['path']) ?>"
                             alt="Wk<?= $ph['wk'] ?> photo"
                             style="width:38px;height:38px;object-fit:cover;border-radius:6px;border:2px solid var(--clr-border-1, #e2e8f0);cursor:pointer"
                             onerror="this.style.display='none'"
                             loading="lazy">
                        <span style="position:absolute;bottom:1px;left:1px;background:rgba(0,0,0,.55);color:#fff;font-size:7px;font-weight:700;padding:1px 3px;border-radius:3px">W<?= $ph['wk'] ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <span style="color:var(--clr-border-1, #e2e8f0);font-size:18px" title="No photos uploaded for this item">
                    <i class="bi bi-camera"></i>
                </span>
                <?php endif; ?>
            </td>
        </tr>
        <?php
            $firstRow = false;
            endforeach; // subRows
        endforeach; // groupedItems
        ?>
        </tbody>
        <tfoot>
            <tr style="background:rgba(22, 163, 74, 0.1);border-top:2px solid rgba(22, 163, 74, 0.3)">
                <td colspan="3" style="padding:9px 10px;font-weight:800;color:#10b981;font-size:12px">Monthly Total</td>
                <td style="text-align:center;font-weight:700;color:var(--clr-text-body, #374151)">
                    <?= number_format(array_sum(array_column($workItems,'monthly_target_qty')),0,'.',',') ?>
                </td>
                <?php
                // Week totals
                for ($wk=1; $wk<=4; $wk++):
                    $wkTotal = 0;
                    foreach ($workItems as $wi) {
                        $wkTotal += $wpiByItem[(int)$wi['id']][$wk]['qty'] ?? 0;
                    }
                ?>
                <td style="text-align:center;font-weight:700;color:var(--clr-theme-primary, #1d4ed8)"><?= number_format($wkTotal,0,'.',',') ?></td>
                <?php endfor; ?>
                <td style="text-align:center;font-weight:900;font-size:13px;color:<?= $totalAchievementPct>=100?'#10b981':'var(--clr-theme-primary, #6d28d9)' ?>">
                    <?= number_format(array_sum(array_column($workItems,'actual_qty')),0,'.',',') ?>
                </td>
                <td style="text-align:center">
                    <span style="font-weight:900;font-size:13px;color:<?= $totalActualPct>=100?'#10b981':'var(--clr-theme-primary, #6d28d9)' ?>">
                        <?= number_format($totalActualPct,1) ?>%
                    </span>
                </td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    </div>
</div>

</div><!-- px-3 -->

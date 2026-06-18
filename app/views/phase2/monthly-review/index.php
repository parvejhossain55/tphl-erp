<?php
$base      = $base ?? $GLOBALS['base_url'];
$targets   = $targets ?? [];
$phases    = $phases ?? [];
$project   = $project ?? [];
$targetItems = $targetItems ?? [];
$matByTarget = $matByTarget ?? [];
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
body { font-family:'Inter',sans-serif; }
:root { --pri:var(--clr-theme-primary);--green:#10b981;--amber:#f59e0b;--red:#ef4444;--slate:var(--clr-text-muted, #64748b); }

.mr-page { padding:18px 20px; }
.mr-header { display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px }
.mr-title { font-size:20px;font-weight:800;color:var(--clr-text-body, #1e293b) }
.mr-subtitle { font-size:12px;color:var(--slate);margin-top:2px }

.mr-filters { display:flex;gap:10px;flex-wrap:wrap;align-items:center }
.mr-filter-inp { padding:7px 11px;border:1px solid var(--clr-border-1, #e2e8f0);border-radius:8px;font-size:12.5px;background:var(--clr-bg-card, #fff);color:var(--clr-text-body, #1e293b);font-family:inherit }
.mr-filter-inp:focus { outline:none;border-color:var(--pri) }
.mr-btn { padding:7px 16px;border-radius:8px;font-size:12.5px;font-weight:700;background:var(--pri);color:#fff;border:none;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px }

.mr-card { background:var(--clr-bg-card, #fff);border:1px solid var(--clr-border-1, #e2e8f0);border-radius:16px;overflow:hidden;margin-bottom:18px }
.mr-card-header { padding:14px 20px;border-bottom:1px solid var(--clr-border-1, #f1f5f9);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px }
.mr-card-title { font-size:13.5px;font-weight:800;color:var(--clr-text-body, #1e293b);display:flex;align-items:center;gap:8px }
.mr-badge { display:inline-flex;align-items:center;font-size:10px;font-weight:700;padding:3px 10px;border-radius:999px;white-space:nowrap }
.badge-approved { background:rgba(16, 185, 129, 0.15);color:#10b981 }
.badge-draft    { background:rgba(148, 163, 184, 0.15);color:var(--clr-text-muted, #64748b) }
.badge-review   { background:rgba(245, 158, 11, 0.15);color:#d97706 }

.mr-stats { display:flex;gap:16px;flex-wrap:wrap;padding:14px 20px;border-bottom:1px solid var(--clr-border-1, #f8fafc) }
.mr-stat { text-align:center }
.mr-stat-val { font-size:18px;font-weight:900;line-height:1 }
.mr-stat-lbl { font-size:9.5px;font-weight:700;text-transform:uppercase;color:var(--slate);margin-top:2px }

.mr-table { width:100%;border-collapse:collapse;font-size:11.5px }
.mr-table th { font-size:9px;font-weight:700;text-transform:uppercase;color:var(--slate);padding:7px 10px;border-bottom:2px solid var(--clr-border-1, #e2e8f0);background:var(--clr-bg-body, #f8fafc);white-space:nowrap }
.mr-table td { padding:8px 10px;border-bottom:1px solid var(--clr-border-1, #f8fafc);vertical-align:middle }
.mr-table tr:last-child td { border-bottom:none }
.mr-table tr:hover td { background:var(--clr-bg-card-hover, rgba(0, 0, 0, 0.02)) }

.pct-bar { height:5px;border-radius:3px;background:var(--clr-border-1, #f1f5f9);overflow:hidden;margin-top:3px;min-width:50px }
.pct-fill-plan { height:100%;border-radius:3px;background:rgba(99, 102, 241, 0.4) }
.pct-fill-actual { height:100%;border-radius:3px;background:var(--green) }

.mr-empty { text-align:center;padding:32px;color:var(--slate);font-size:12.5px }
.mr-mat-badge { display:inline-flex;align-items:center;gap:4px;font-size:10.5px;background:rgba(59, 130, 246, 0.15);color:var(--clr-theme-primary, #3b82f6);padding:2px 8px;border-radius:999px;font-weight:600 }
.progress-ring-wrap { display:flex;align-items:center;gap:10px }
</style>

<div class="mr-page">

<!-- Header -->
<div class="mr-header">
    <div>
        <div class="mr-title"><i class="bi bi-calendar3-range me-2" style="color:var(--pri)"></i>Monthly Review</div>
        <div class="mr-subtitle"><?= htmlspecialchars($project['name'] ?? '') ?> — Phase-wise monthly progress overview</div>
    </div>
    <div class="mr-filters">
        <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
            <select name="phase_id" class="mr-filter-inp" onchange="this.form.submit()">
                <option value="">All Phases</option>
                <?php foreach ($phases as $ph): ?>
                <option value="<?= $ph['id'] ?>" <?= $selectedPhase == $ph['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($ph['phase_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <input type="month" name="month" class="mr-filter-inp" value="<?= htmlspecialchars($selectedMonth) ?>"
                   onchange="this.form.submit()">
            <a href="<?= $base ?>/con-monthly-review/<?= $projectId ?>" class="mr-btn" style="background:#64748b;font-size:11px">
                <i class="bi bi-x-lg"></i> Clear
            </a>
        </form>
    </div>
</div>

<?php if (empty($targets)): ?>
<div class="mr-empty">
    <i class="bi bi-calendar-x" style="font-size:36px;opacity:.25;display:block;margin-bottom:10px"></i>
    No monthly targets found<?= $selectedMonth || $selectedPhase ? ' for the selected filters.' : ' yet.' ?>
</div>
<?php else: ?>

<?php
// Group targets by phase
$byPhase = [];
foreach ($targets as $t) {
    $byPhase[$t['phase_name']][] = $t;
}
?>

<?php foreach ($byPhase as $phaseName => $phaseTargets): ?>
<div style="margin-bottom:8px">
    <div style="font-size:11.5px;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:var(--pri);padding:4px 0 8px;display:flex;align-items:center;gap:8px">
        <i class="bi bi-layers-half"></i> <?= htmlspecialchars($phaseName) ?>
        <span style="font-size:10px;font-weight:600;color:var(--slate);text-transform:none;letter-spacing:0">(<?= count($phaseTargets) ?> month<?= count($phaseTargets)>1?'s':'' ?>)</span>
    </div>

    <?php foreach ($phaseTargets as $t):
        $tid      = (int)$t['target_id'];
        $monthLbl = (new DateTime($t['target_month']))->format('F Y');
        $planPct  = (float)$t['avg_planned_pct'];
        $actPct   = (float)$t['avg_actual_pct'];
        $planQty  = (float)$t['total_planned_qty'];
        $actQty   = (float)$t['total_actual_qty'];
        $statusMap = ['Approved'=>'badge-approved','Draft'=>'badge-draft','In Review'=>'badge-review'];
        $statusBadge = $statusMap[$t['planning_status'] ?? 'Draft'] ?? 'badge-draft';
        $overallBarW = min(100, $actPct);
        $barColor = $actPct >= 100 ? '#10b981' : ($actPct >= $planPct ? 'var(--clr-theme-primary)' : ($actPct > 0 ? '#f59e0b' : '#e2e8f0'));
        $items = $targetItems[$tid] ?? [];
        $mat   = $matByTarget[$tid] ?? null;
    ?>
    <div class="mr-card">
        <div class="mr-card-header">
            <div class="mr-card-title">
                <i class="bi bi-calendar-check" style="color:var(--amber)"></i>
                <?= $monthLbl ?>
                <span class="mr-badge <?= $statusBadge ?>"><?= htmlspecialchars($t['planning_status'] ?? 'Draft') ?></span>
                <?php if ($t['responsible_team']): ?>
                <span style="font-size:10.5px;font-weight:500;color:var(--slate)"><i class="bi bi-people me-1"></i><?= htmlspecialchars($t['responsible_team']) ?></span>
                <?php endif; ?>
            </div>
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                <?php if ($mat): ?>
                <span class="mr-mat-badge"><i class="bi bi-box-seam"></i> <?= $mat['issue_count'] ?> Material Issue<?= $mat['issue_count']>1?'s':'' ?></span>
                <?php endif; ?>
                <a href="<?= $base ?>/con-monthly-target/<?= $tid ?>" style="font-size:11px;color:var(--pri);text-decoration:none;font-weight:600">
                    <i class="bi bi-pencil-square me-1"></i>Edit
                </a>
                <a href="<?= $base ?>/con-weekly-progress/<?= $tid ?>" style="font-size:11px;color:var(--green);text-decoration:none;font-weight:600">
                    <i class="bi bi-activity me-1"></i>Progress
                </a>
            </div>
        </div>

        <!-- Month-level stats -->
        <div class="mr-stats">
            <div class="mr-stat">
                <div class="mr-stat-val" style="color:var(--slate)"><?= (int)$t['total_items'] ?></div>
                <div class="mr-stat-lbl">Work Items</div>
            </div>
            <div class="mr-stat">
                <div class="mr-stat-val" style="color:var(--clr-theme-primary, #1d4ed8)"><?= number_format($planQty,0,'.',',') ?></div>
                <div class="mr-stat-lbl">Planned Qty</div>
            </div>
            <div class="mr-stat">
                <div class="mr-stat-val" style="color:var(--green)"><?= number_format($actQty,0,'.',',') ?></div>
                <div class="mr-stat-lbl">Achieved Qty</div>
            </div>
            <div class="mr-stat">
                <div class="mr-stat-val" style="color:var(--clr-theme-secondary, #6d28d9)"><?= number_format($planPct,1) ?>%</div>
                <div class="mr-stat-lbl">Planned Progress</div>
            </div>
            <div class="mr-stat">
                <div class="mr-stat-val" style="color:<?= $actPct >= $planPct ? '#10b981' : '#ef4444' ?>">
                    <?= number_format($actPct,1) ?>%
                </div>
                <div class="mr-stat-lbl">Actual Progress</div>
            </div>
            <div style="flex:1;min-width:120px;display:flex;flex-direction:column;justify-content:center">
                <div style="font-size:9px;color:var(--slate);font-weight:700;margin-bottom:3px">Overall Progress</div>
                <div style="height:8px;border-radius:4px;background:var(--clr-border-1, #f1f5f9);overflow:hidden;position:relative">
                    <div style="position:absolute;left:0;top:0;height:100%;width:<?= $planPct ?>%;background:rgba(99, 102, 241, 0.4);border-radius:4px"></div>
                    <div style="position:absolute;left:0;top:0;height:100%;width:<?= $overallBarW ?>%;background:<?= $barColor ?>;border-radius:4px"></div>
                </div>
                <div style="font-size:9px;color:var(--slate);margin-top:2px">
                    <span style="color:var(--clr-theme-secondary, #6d28d9)">Plan: <?= number_format($planPct,0) ?>%</span>
                    &nbsp;|&nbsp;
                    <span style="color:<?= $barColor ?>">Actual: <?= number_format($actPct,0) ?>%</span>
                </div>
            </div>
        </div>

        <!-- Items table -->
        <?php if (!empty($items)): ?>
        <div style="overflow-x:auto">
        <table class="mr-table">
            <thead>
                <tr>
                    <th>Work Item</th>
                    <th>Work Type</th>
                    <th style="text-align:center">Unit</th>
                    <th style="text-align:center">Target Qty</th>
                    <th style="text-align:center">Achieved</th>
                    <th style="text-align:center">Planned %</th>
                    <th style="text-align:center">Actual %</th>
                    <th style="text-align:center">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item):
                $iPlanned = (float)$item['planned_progress_pct'];
                $iActual  = (float)$item['actual_progress_pct'];
                $iTarget  = (float)$item['target_qty'];
                $iActQty  = (float)$item['actual_qty'];
                if ($iActual >= 100)             { $iColor='#10b981'; $iStatus='✅ Done'; }
                elseif ($iActual >= $iPlanned)   { $iColor='var(--clr-theme-primary)'; $iStatus='On Track'; }
                elseif ($iActual > 0)            { $iColor='#f59e0b'; $iStatus='In Progress'; }
                else                              { $iColor='var(--clr-text-muted, #94a3b8)'; $iStatus='Not Started'; }
            ?>
            <tr>
                <td style="font-weight:600;color:var(--clr-text-body, #1e293b);white-space:nowrap"><?= htmlspecialchars($item['work_item_name'] ?? '—') ?></td>
                <td style="color:var(--slate);font-size:11px"><?= htmlspecialchars($item['work_type_name'] ?? '—') ?></td>
                <td style="color:var(--slate);text-align:center"><?= htmlspecialchars($item['unit'] ?? '') ?></td>
                <td style="text-align:center;font-weight:700"><?= number_format($iTarget,0,'.',',') ?></td>
                <td style="text-align:center;color:var(--clr-theme-primary, #1d4ed8);font-weight:700"><?= number_format($iActQty,0,'.',',') ?></td>
                <td style="text-align:center">
                    <div style="font-weight:700;color:var(--clr-theme-secondary, #6d28d9)"><?= number_format($iPlanned,0) ?>%</div>
                    <div class="pct-bar"><div class="pct-fill-plan" style="width:<?= min(100,$iPlanned) ?>%"></div></div>
                </td>
                <td style="text-align:center">
                    <div style="font-weight:800;color:<?= $iColor ?>"><?= number_format($iActual,1) ?>%</div>
                    <div class="pct-bar"><div class="pct-fill-actual" style="width:<?= min(100,$iActual) ?>%;background:<?= $iColor ?>"></div></div>
                </td>
                <td style="text-align:center">
                    <span style="font-size:10px;font-weight:700;color:<?= $iColor ?>"><?= $iStatus ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>

        <?php if ($mat && !empty($mat['materials'])): ?>
        <div style="padding:8px 16px;font-size:11px;color:var(--slate);border-top:1px solid var(--clr-border-1, #f1f5f9);background:var(--clr-bg-body, #f8fafc)">
            <i class="bi bi-box-seam me-1 text-info"></i>
            <strong>Materials Issued:</strong> <?= htmlspecialchars($mat['materials']) ?>
            &nbsp;·&nbsp; Total Qty: <?= number_format((float)$mat['total_qty'], 2) ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>

<?php endif; ?>
</div>

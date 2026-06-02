<?php
$base        = $base ?? $GLOBALS['base_url'];
$behindItems = $behindItems ?? [];
$project     = $project ?? [];

$riskColors = [
    'Critical'=> ['bg'=>'#fee2e2','color'=>'#991b1b','border'=>'#fca5a5'],
    'High'    => ['bg'=>'#ffedd5','color'=>'#9a3412','border'=>'#fdba74'],
    'Medium'  => ['bg'=>'#fef3c7','color'=>'#92400e','border'=>'#fde68a'],
    'Low'     => ['bg'=>'#e0f2fe','color'=>'#075985','border'=>'#7dd3fc'],
];
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
body{font-family:'Inter',sans-serif;}
:root{--pri:#4f46e5;--green:#10b981;--amber:#f59e0b;--red:#ef4444;--slate:#64748b;}

.rp-page{padding:18px 20px}
.rp-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px}
.rp-title{font-size:20px;font-weight:800;color:#1e293b}
.rp-subtitle{font-size:12px;color:var(--slate);margin-top:2px}

.rp-item{border-radius:14px;border:1px solid #e2e8f0;background:#fff;overflow:hidden;margin-bottom:14px}
.rp-item-header{padding:12px 18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;border-bottom:1px solid #f1f5f9}
.rp-item-title{font-size:13px;font-weight:800;color:#1e293b}
.rp-item-meta{font-size:10.5px;color:var(--slate);display:flex;gap:12px;flex-wrap:wrap;margin-top:3px}

.rp-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(110px,1fr));gap:10px;padding:14px 18px;border-bottom:1px solid #f8fafc}
.rp-stat{text-align:center}
.rp-stat-val{font-size:16px;font-weight:900;line-height:1}
.rp-stat-lbl{font-size:9px;font-weight:700;text-transform:uppercase;color:var(--slate);margin-top:3px}

.rp-catchup{padding:12px 18px;background:linear-gradient(135deg,#f0f9ff,#e0f2fe);display:flex;gap:16px;flex-wrap:wrap;align-items:center}
.rp-catchup-lbl{font-size:10px;font-weight:700;text-transform:uppercase;color:#0369a1}
.rp-catchup-val{font-size:14px;font-weight:900;color:#0c4a6e}
.rp-catchup-unit{font-size:9px;color:#0369a1;font-weight:600}

.rp-notes-row{padding:12px 18px;display:flex;align-items:flex-start;gap:10px}
.rp-notes-inp{flex:1;padding:7px 11px;border:1px solid #e2e8f0;border-radius:8px;font-size:12px;color:#1e293b;resize:vertical;min-height:38px;font-family:inherit}
.rp-notes-inp:focus{outline:none;border-color:var(--pri)}

.risk-badge{display:inline-flex;align-items:center;gap:5px;font-size:10px;font-weight:700;padding:3px 10px;border-radius:999px}
.rp-save-btn{padding:7px 18px;border-radius:8px;font-size:12.5px;font-weight:700;background:var(--pri);color:#fff;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:6px}
.rp-save-btn:hover{background:#4338ca}

.pct-bar{height:6px;border-radius:3px;background:#f1f5f9;overflow:hidden;margin-top:3px}
</style>

<div class="rp-page">

<!-- Header -->
<div class="rp-header">
    <div>
        <div class="rp-title"><i class="bi bi-arrow-clockwise me-2" style="color:var(--amber)"></i>Recovery Plan</div>
        <div class="rp-subtitle"><?= htmlspecialchars($project['name'] ?? '') ?> — Behind-schedule items & catch-up plan</div>
    </div>
    <a href="<?= $base ?>/con-risk-dashboard/<?= $projectId ?>" style="font-size:12px;font-weight:700;color:var(--slate);text-decoration:none;background:#f1f5f9;padding:7px 14px;border-radius:8px;display:inline-flex;align-items:center;gap:6px">
        <i class="bi bi-shield-exclamation"></i> Risk Dashboard
    </a>
</div>

<?php if (empty($behindItems)): ?>
<div style="text-align:center;padding:48px;background:#fff;border-radius:16px;border:1px solid #e2e8f0">
    <i class="bi bi-check-circle-fill text-success" style="font-size:40px;display:block;margin-bottom:12px"></i>
    <div style="font-size:16px;font-weight:800;color:#065f46;margin-bottom:4px">All items on track!</div>
    <div style="font-size:12px;color:var(--slate)">No work items are behind their planned progress.</div>
</div>
<?php else: ?>

<div style="font-size:12px;color:var(--slate);margin-bottom:14px;padding:10px 14px;background:#fff;border:1px solid #e2e8f0;border-radius:10px">
    <i class="bi bi-info-circle me-1 text-info"></i>
    <strong><?= count($behindItems) ?> item<?= count($behindItems)>1?'s':'' ?></strong> behind schedule.
    Add recovery notes and save to track your catch-up plan.
</div>

<form method="POST" action="<?= $base ?>/con-recovery-plan-save">
    <input type="hidden" name="project_id" value="<?= $projectId ?>">

    <?php foreach ($behindItems as $item):
        $rc = $riskColors[$item['risk']] ?? $riskColors['Low'];
        $month = !empty($item['target_month']) ? (new DateTime($item['target_month']))->format('F Y') : '—';
        $gapBar = min(100, $item['gap_pct']);
        $actBar = min(100, (float)$item['actual_progress_pct']);
        $planBar = min(100, (float)$item['planned_progress_pct']);
    ?>
    <div class="rp-item" style="border-left:4px solid <?= $rc['border'] ?>">
        <div class="rp-item-header">
            <div>
                <div class="rp-item-title"><?= htmlspecialchars($item['work_item_name'] ?? '—') ?></div>
                <div class="rp-item-meta">
                    <span><i class="bi bi-layers-half me-1"></i><?= htmlspecialchars($item['phase_name']) ?></span>
                    <span><i class="bi bi-calendar2 me-1"></i><?= $month ?></span>
                    <?php if ($item['work_type_name']): ?>
                    <span><i class="bi bi-tag me-1"></i><?= htmlspecialchars($item['work_type_name']) ?></span>
                    <?php endif; ?>
                    <?php if ($item['responsible_team']): ?>
                    <span><i class="bi bi-people me-1"></i><?= htmlspecialchars($item['responsible_team']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <span class="risk-badge" style="background:<?= $rc['bg'] ?>;color:<?= $rc['color'] ?>">
                <i class="bi bi-exclamation-triangle-fill"></i> <?= $item['risk'] ?> Risk
            </span>
        </div>

        <!-- Stats grid -->
        <div class="rp-grid">
            <div class="rp-stat">
                <div class="rp-stat-val" style="color:#6d28d9"><?= number_format($planBar,0) ?>%</div>
                <div class="rp-stat-lbl">Planned</div>
                <div class="pct-bar"><div style="width:<?= $planBar ?>%;height:100%;background:#c7d2fe;border-radius:3px"></div></div>
            </div>
            <div class="rp-stat">
                <div class="rp-stat-val" style="color:#1d4ed8"><?= number_format($actBar,1) ?>%</div>
                <div class="rp-stat-lbl">Actual</div>
                <div class="pct-bar"><div style="width:<?= $actBar ?>%;height:100%;background:#3b82f6;border-radius:3px"></div></div>
            </div>
            <div class="rp-stat">
                <div class="rp-stat-val" style="color:#dc2626">-<?= number_format($item['gap_pct'],1) ?>%</div>
                <div class="rp-stat-lbl">Gap %</div>
                <div class="pct-bar"><div style="width:<?= $gapBar ?>%;height:100%;background:#fca5a5;border-radius:3px"></div></div>
            </div>
            <div class="rp-stat">
                <div class="rp-stat-val" style="color:#dc2626"><?= number_format($item['gap_qty'],0,'.',',') ?></div>
                <div class="rp-stat-lbl">Gap Qty (<?= htmlspecialchars($item['unit'] ?? '') ?>)</div>
            </div>
            <div class="rp-stat">
                <div class="rp-stat-val" style="color:#0369a1"><?= $item['rem_days'] ?></div>
                <div class="rp-stat-lbl">Days Left</div>
            </div>
            <div class="rp-stat">
                <div class="rp-stat-val" style="color:#0369a1"><?= $item['rem_weeks'] ?></div>
                <div class="rp-stat-lbl">Weeks Left</div>
            </div>
        </div>

        <!-- Catch-up suggestion -->
        <?php if ($item['gap_qty'] > 0): ?>
        <div class="rp-catchup">
            <i class="bi bi-lightning-charge-fill text-warning" style="font-size:16px"></i>
            <div>
                <div class="rp-catchup-lbl">Suggested Catch-up</div>
                <div>
                    <span class="rp-catchup-val"><?= number_format($item['daily_catchup'],2) ?></span>
                    <span class="rp-catchup-unit"> <?= htmlspecialchars($item['unit'] ?? '') ?>/day</span>
                    &nbsp;&nbsp;
                    <span class="rp-catchup-val"><?= number_format($item['weekly_catchup'],2) ?></span>
                    <span class="rp-catchup-unit"> <?= htmlspecialchars($item['unit'] ?? '') ?>/week</span>
                </div>
            </div>
            <div style="margin-left:auto;text-align:right">
                <a href="<?= $base ?>/con-weekly-progress/<?= $item['target_id'] ?>" style="font-size:11.5px;font-weight:700;color:var(--pri);text-decoration:none">
                    <i class="bi bi-activity me-1"></i>Log Progress →
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Notes -->
        <div class="rp-notes-row">
            <i class="bi bi-journal-text" style="font-size:16px;color:var(--slate);margin-top:8px;flex-shrink:0"></i>
            <textarea name="notes[<?= $item['item_id'] ?>]" class="rp-notes-inp"
                      placeholder="Recovery action notes, responsible person, revised target date…"
                      rows="2"><?= htmlspecialchars($item['recovery_notes'] ?? '') ?></textarea>
        </div>
    </div>
    <?php endforeach; ?>

    <div style="text-align:right;margin-top:8px">
        <button type="submit" class="rp-save-btn">
            <i class="bi bi-floppy2-fill"></i> Save Recovery Notes
        </button>
    </div>
</form>

<?php endif; ?>
</div>

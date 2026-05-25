<?php
$base       = $GLOBALS['base_url'];
$project_id = $project['id']   ?? 0;
$byMonth    = $byMonth         ?? [];
$phases     = $phases          ?? [];

$statusMeta = [
    'Draft'     => ['color' => '#94a3b8', 'bg' => 'rgba(148,163,184,.12)'],
    'In Review' => ['color' => '#f59e0b', 'bg' => 'rgba(245,158,11,.12)'],
    'Approved'  => ['color' => '#10b981', 'bg' => 'rgba(16,185,129,.12)'],
];
$phaseColors = ['#6366f1','#10b981','#f59e0b','#ef4444','#14b8a6','#8b5cf6','#ec4899','#0ea5e9','#f97316','#84cc16'];
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
:root{ --mt-primary:#6366f1;--mt-green:#10b981;--mt-amber:#f59e0b;--mt-red:#ef4444; }

.mt-header{ display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:10px }
.mt-header h4{ font-size:19px;font-weight:800;margin:0 0 3px }
.mt-breadcrumb{ font-size:11.5px;color:var(--text-muted) }
.mt-breadcrumb a{ color:var(--mt-primary);text-decoration:none }

/* Create target card */
.mt-create-card{ border-radius:14px;border:2px dashed rgba(99,102,241,.3);padding:22px 24px;margin-bottom:24px;background:rgba(99,102,241,.03) }
.mt-create-card h6{ font-size:13px;font-weight:700;margin-bottom:14px;color:var(--mt-primary) }

/* Month group */
.mt-month-group{ margin-bottom:24px }
.mt-month-label{ font-size:11px;font-weight:800;letter-spacing:.8px;text-transform:uppercase;color:var(--text-muted);padding:6px 14px;border-left:3px solid var(--mt-primary);margin-bottom:10px;display:flex;align-items:center;gap:10px }
.mt-month-label span{ color:var(--mt-primary);font-size:13px;font-weight:800 }

/* Target card */
.mt-card{ border-radius:12px;border:1px solid rgba(0,0,0,.07);padding:0;overflow:hidden;margin-bottom:10px;transition:box-shadow .2s }
.mt-card:hover{ box-shadow:0 4px 16px rgba(0,0,0,.09) }
.mt-card-head{ padding:14px 18px;display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap }
.mt-phase-dot{ width:10px;height:10px;border-radius:50%;flex-shrink:0 }
.mt-phase-name{ font-size:13px;font-weight:700 }
.mt-badge{ font-size:10px;font-weight:700;padding:2px 10px;border-radius:999px;white-space:nowrap }
.mt-progress-wrap{ padding:0 18px 14px;display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:10px }
.mt-stat{ text-align:center }
.mt-stat-val{ font-size:15px;font-weight:800 }
.mt-stat-label{ font-size:10px;font-weight:600;color:var(--text-muted);margin-top:1px }
.mt-prog-bar{ height:5px;border-radius:3px;background:rgba(0,0,0,.06);overflow:hidden;margin-top:4px }
.mt-prog-fill{ height:100%;border-radius:3px;transition:width .4s }
.mt-card-footer{ padding:9px 18px;border-top:1px solid rgba(0,0,0,.06);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px }
.btn-mt-view{ font-size:11.5px;font-weight:700;padding:5px 14px;border-radius:7px;background:var(--mt-primary);color:#fff;text-decoration:none;display:inline-flex;align-items:center;gap:5px;transition:opacity .2s }
.btn-mt-view:hover{ opacity:.85;color:#fff }
.btn-mt-del{ font-size:11px;color:var(--mt-red);text-decoration:none;display:inline-flex;align-items:center;gap:4px }
.btn-mt-del:hover{ text-decoration:underline;color:var(--mt-red) }

/* Empty state */
.mt-empty{ text-align:center;padding:48px;color:var(--text-muted) }
.mt-empty i{ font-size:38px;margin-bottom:12px;opacity:.25;display:block }

/* Form controls */
.mt-form-row{ display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:10px;align-items:end }
.mt-form-ctrl{ display:flex;flex-direction:column;gap:4px }
.mt-form-ctrl label{ font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted) }
.mt-sel,.mt-inp{ width:100%;padding:7px 10px;border:1px solid rgba(0,0,0,.12);border-radius:8px;font-size:12.5px;font-weight:500;background:transparent;color:inherit }
.mt-sel:focus,.mt-inp:focus{ outline:none;border-color:var(--mt-primary) }
.btn-mt-create{ padding:7px 16px;border-radius:8px;font-size:12.5px;font-weight:700;background:var(--mt-primary);color:#fff;border:none;cursor:pointer;white-space:nowrap }
</style>

<div class="row"><div class="col-12">

<!-- Header -->
<div class="mt-header">
    <div>
        <h4><i class="fal fa-calendar-alt me-2" style="color:var(--mt-primary)"></i>Monthly Target Planner</h4>
        <div class="mt-breadcrumb">
            <a href="<?= $base ?>/con-projects">Projects</a> ›
            <a href="<?= $base ?>/con-phase-plan/<?= $project_id ?>"><?= htmlspecialchars($project['name']) ?></a> ›
            <span>Monthly Targets</span>
        </div>
    </div>
    <a href="<?= $base ?>/con-phase-plan/<?= $project_id ?>" class="btn btn-sm btn-outline-secondary" style="font-size:12px">
        <i class="fal fa-arrow-left me-1"></i>Phase Plan
    </a>
</div>

<!-- Create New Target -->
<div class="mt-create-card">
    <h6><i class="fal fa-plus-circle me-2"></i>Create New Monthly Target</h6>
    <form method="POST" action="<?= $base ?>/con-monthly-target-create/<?= $project_id ?>">
        <div class="mt-form-row">
            <div class="mt-form-ctrl">
                <label>Phase</label>
                <select name="phase_id" class="mt-sel" required>
                    <option value="">-- Select Phase --</option>
                    <?php foreach ($phases as $ph): if ((float)$ph['weight_percentage'] <= 0) continue; ?>
                    <option value="<?= $ph['id'] ?>"><?= htmlspecialchars($ph['phase_name']) ?> (<?= number_format((float)$ph['weight_percentage'],0) ?>%)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mt-form-ctrl">
                <label>Target Month</label>
                <input type="month" name="target_month" class="mt-inp" required>
            </div>
            <div class="mt-form-ctrl">
                <label>Responsible Team</label>
                <input type="text" name="responsible_team" class="mt-inp" placeholder="e.g. Civil Team">
            </div>
            <div>
                <button type="submit" class="btn-mt-create">
                    <i class="fal fa-plus me-1"></i>Create
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Targets List -->
<?php if (empty($byMonth)): ?>
<div class="mt-empty">
    <i class="fal fa-calendar-times"></i>
    <div style="font-size:14px;font-weight:600;margin-bottom:6px">No Monthly Targets Yet</div>
    <div style="font-size:12px">Create your first monthly target above.</div>
</div>
<?php else: ?>
<?php foreach ($byMonth as $monthKey => $group):
    $dt = new DateTime($monthKey);
    $monthLabel = $dt->format('F Y');
?>
<div class="mt-month-group">
    <div class="mt-month-label">
        <i class="fal fa-calendar"></i>
        <span><?= $monthLabel ?></span>
        <span style="color:var(--text-muted);font-size:10px;font-weight:600"><?= count($group['targets']) ?> phase(s)</span>
    </div>

    <?php foreach ($group['targets'] as $idx => $t):
        $sm    = $statusMeta[$t['planning_status']] ?? $statusMeta['Draft'];
        $color = $phaseColors[$idx % count($phaseColors)];
        $actual = (float)($t['avg_actual_pct'] ?? 0);
        $items  = (int)($t['item_count'] ?? 0);
    ?>
    <div class="mt-card">
        <div class="mt-card-head">
            <div style="display:flex;align-items:center;gap:9px">
                <div class="mt-phase-dot" style="background:<?= $color ?>"></div>
                <div class="mt-phase-name"><?= htmlspecialchars($t['phase_name']) ?></div>
                <span style="font-size:10px;color:var(--text-muted)">(<?= number_format((float)$t['phase_weight'],0) ?>% of project)</span>
            </div>
            <div style="display:flex;align-items:center;gap:8px">
                <?php if ($t['responsible_team']): ?>
                <span style="font-size:10.5px;color:var(--text-muted)"><i class="fal fa-users me-1"></i><?= htmlspecialchars($t['responsible_team']) ?></span>
                <?php endif; ?>
                <span class="mt-badge" style="background:<?= $sm['bg'] ?>;color:<?= $sm['color'] ?>"><?= $t['planning_status'] ?></span>
            </div>
        </div>
        <div class="mt-progress-wrap">
            <div class="mt-stat">
                <div class="mt-stat-val"><?= $items ?></div>
                <div class="mt-stat-label">Work Items</div>
            </div>
            <div class="mt-stat">
                <div class="mt-stat-val" style="color:var(--mt-primary)"><?= number_format($actual,1) ?>%</div>
                <div class="mt-stat-label">Avg Actual</div>
                <div class="mt-prog-bar"><div class="mt-prog-fill" style="width:<?= $actual ?>%;background:<?= $actual>=100?'var(--mt-green)':'var(--mt-primary)' ?>"></div></div>
            </div>
            <div class="mt-stat">
                <div class="mt-stat-val" style="color:var(--text-muted)"><?= $monthLabel ?></div>
                <div class="mt-stat-label">Month</div>
            </div>
            <div class="mt-stat">
                <div class="mt-stat-val" style="color:<?= $t['planning_status']==='Approved'?'var(--mt-green)':'var(--mt-amber)' ?>">
                    <?= $t['planning_status'] ?>
                </div>
                <div class="mt-stat-label">Status</div>
            </div>
        </div>
        <div class="mt-card-footer">
            <a href="<?= $base ?>/con-monthly-target/<?= $t['id'] ?>" class="btn-mt-view">
                <i class="fal fa-edit"></i>Manage Items
            </a>
            <a href="<?= $base ?>/con-monthly-target-delete/<?= $t['id'] ?>"
               class="btn-mt-del"
               onclick="return confirm('Delete this monthly target and all its items?')">
                <i class="fal fa-trash"></i>Delete
            </a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>

</div></div>

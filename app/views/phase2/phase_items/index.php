<?php
$addPage         = true;
$show_breadcrumb = false;
$base            = $GLOBALS['base_url'];

$phase       = $phase       ?? [];
$items       = $items       ?? [];
$totalWeight = $totalWeight ?? 0;
$phaseProgress = $phaseProgress ?? 0;
$designTypes = $designTypes ?? [];
$workTypes   = $workTypes   ?? [];

$phase_id   = $phase['id']         ?? 0;
$project_id = $phase['project_id'] ?? 0;
$weightOk   = abs($totalWeight - 100) <= 0.01;

$statusMeta = [
    'Not Started' => ['color' => '#94a3b8', 'bg' => 'rgba(148,163,184,.12)'],
    'In Progress' => ['color' => '#f59e0b', 'bg' => 'rgba(245,158,11,.12)'],
    'Completed'   => ['color' => '#10b981', 'bg' => 'rgba(16,185,129,.12)'],
    'At Risk'     => ['color' => '#ef4444', 'bg' => 'rgba(239,68,68,.12)'],
];
$phaseColors = ['#6366f1','#10b981','#f59e0b','#ef4444','#14b8a6','#8b5cf6','#ec4899','#0ea5e9'];
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

:root { --pi-primary:#6366f1; --pi-green:#10b981; --pi-red:#ef4444; --pi-amber:#f59e0b; }

/* ── Header ── */
.pi-header { display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px }
.pi-header-left h4 { font-size:19px;font-weight:800;margin:0 0 4px }
.pi-breadcrumb { font-size:11.5px;color:var(--text-muted);display:flex;align-items:center;gap:6px }
.pi-breadcrumb a { color:var(--pi-primary);text-decoration:none }
.pi-breadcrumb a:hover { text-decoration:underline }

/* ── Phase info strip ── */
.pi-phase-strip { border-radius:12px;border:1px solid rgba(0,0,0,.06);padding:14px 18px;margin-bottom:18px;display:flex;align-items:center;gap:16px;flex-wrap:wrap }
.pi-phase-badge { font-size:11px;font-weight:700;padding:3px 12px;border-radius:999px }

/* ── Progress bar ── */
.pi-progress-wrap { flex:1;min-width:200px }
.pi-progress-label { display:flex;justify-content:space-between;font-size:11px;font-weight:600;margin-bottom:5px }
.pi-progress-track { height:8px;border-radius:4px;background:rgba(0,0,0,.07);overflow:hidden }
.pi-progress-fill { height:100%;border-radius:4px;background:linear-gradient(90deg,#059669,#10b981);transition:width .5s ease }

/* ── Table ── */
.pi-table-wrap { border-radius:12px;border:1px solid rgba(0,0,0,.07);overflow:hidden;margin-bottom:16px }
.pi-table { width:100%;border-collapse:collapse }
.pi-table thead th {
    padding:9px 12px;font-size:10px;font-weight:700;letter-spacing:.7px;
    text-transform:uppercase;color:var(--text-muted);
    border-bottom:1px solid rgba(0,0,0,.07);white-space:nowrap;text-align:left
}
.pi-table tbody tr { border-bottom:1px solid rgba(0,0,0,.05);transition:background .15s }
.pi-table tbody tr:last-child { border-bottom:none }
.pi-table tbody tr:hover { background:rgba(99,102,241,.03) }
.pi-table td { padding:8px 12px;vertical-align:middle }

.pi-item-name { font-size:12.5px;font-weight:600;border:1px solid transparent;border-radius:6px;padding:4px 7px;width:100%;background:transparent;color:inherit;transition:border-color .2s }
.pi-item-name:focus { outline:none;border-color:var(--pi-primary);background:rgba(99,102,241,.05) }

.pi-num-inp { width:64px;text-align:center;font-size:12px;font-weight:700;border:1px solid rgba(0,0,0,.12);border-radius:7px;padding:4px 6px;background:transparent;color:inherit }
.pi-num-inp:focus { outline:none;border-color:var(--pi-primary) }

.pi-progress-inp { width:72px;text-align:center;font-size:12px;font-weight:700;border:1px solid rgba(0,0,0,.12);border-radius:7px;padding:4px 6px;background:transparent;color:inherit }
.pi-progress-inp:focus { outline:none;border-color:var(--pi-primary) }

/* ── Mini progress bar in table ── */
.pi-mini-bar { height:4px;border-radius:2px;background:rgba(0,0,0,.07);margin-top:3px;overflow:hidden }
.pi-mini-fill { height:100%;border-radius:2px;transition:width .3s }

.pi-status-sel { font-size:11px;font-weight:600;border:1px solid rgba(0,0,0,.1);border-radius:7px;padding:3px 7px;background:transparent;color:inherit;cursor:pointer }

.pi-design-chip { font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;white-space:nowrap }

.pi-del-btn { width:26px;height:26px;border-radius:6px;background:rgba(239,68,68,.1);color:var(--pi-red);border:none;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;font-size:11px;transition:background .15s }
.pi-del-btn:hover { background:rgba(239,68,68,.22) }

/* ── Action bar ── */
.pi-action-bar { display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px }
.btn-pi-save { display:inline-flex;align-items:center;gap:7px;padding:8px 20px;border-radius:8px;font-size:13px;font-weight:700;background:var(--pi-primary);color:#fff;border:none;cursor:pointer;transition:opacity .2s }
.btn-pi-save:hover { opacity:.88 }
.btn-pi-save:disabled { opacity:.45;cursor:not-allowed }

/* ── Weight total bar ── */
.pi-w-track { height:8px;border-radius:4px;background:rgba(0,0,0,.07);overflow:hidden;width:180px }
.pi-w-fill { height:100%;border-radius:4px;transition:width .4s }
.pi-w-fill.ok { background:linear-gradient(90deg,#059669,#10b981) }
.pi-w-fill.over { background:linear-gradient(90deg,#dc2626,#ef4444) }
</style>

<div class="row"><div class="col-12">

<!-- ══ HEADER ══════════════════════════════════════════════════════════ -->
<div class="pi-header">
    <div class="pi-header-left">
        <h4><i class="fal fa-list-ul me-2" style="color:var(--pi-primary)"></i><?= htmlspecialchars($phase['phase_name']) ?> — Sub-items</h4>
        <div class="pi-breadcrumb">
            <a href="<?= $base ?>/con-projects">Projects</a>
            <span>›</span>
            <a href="<?= $base ?>/con-phase-plan/<?= $project_id ?>">Phase Plan</a>
            <span>›</span>
            <span><?= htmlspecialchars($phase['phase_name']) ?></span>
        </div>
    </div>
    <a href="<?= $base ?>/con-phase-plan/<?= $project_id ?>" class="btn btn-sm btn-outline-secondary" style="font-size:12px">
        <i class="fal fa-arrow-left me-1"></i>Back to Phases
    </a>
</div>

<!-- ══ PHASE INFO STRIP ═════════════════════════════════════════════════ -->
<div class="pi-phase-strip">
    <div>
        <div style="font-size:10px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;color:var(--text-muted);margin-bottom:3px">Phase Weight</div>
        <div style="font-size:18px;font-weight:800;color:var(--pi-primary)"><?= number_format((float)$phase['weight_percentage'],1) ?>%</div>
    </div>
    <div style="width:1px;height:36px;background:rgba(0,0,0,.08)"></div>
    <div>
        <div style="font-size:10px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;color:var(--text-muted);margin-bottom:3px">Sub-items</div>
        <div style="font-size:18px;font-weight:800"><?= count($items) ?></div>
    </div>
    <div style="width:1px;height:36px;background:rgba(0,0,0,.08)"></div>
    <div class="pi-progress-wrap">
        <div class="pi-progress-label">
            <span style="color:var(--text-muted)">Phase Progress</span>
            <span style="color:<?= $phaseProgress>=100?'var(--pi-green)':'var(--pi-primary)' ?>;font-weight:800">
                <?= number_format($phaseProgress,1) ?>%
            </span>
        </div>
        <div class="pi-progress-track">
            <div class="pi-progress-fill" style="width:<?= min($phaseProgress,100) ?>%"></div>
        </div>
    </div>
    <div>
        <span class="pi-phase-badge" style="background:<?= $statusMeta[$phase['status']]['bg']??'rgba(148,163,184,.12)' ?>;color:<?= $statusMeta[$phase['status']]['color']??'#94a3b8' ?>">
            <?= htmlspecialchars($phase['status']) ?>
        </span>
    </div>
</div>

<!-- ══ ITEMS TABLE ══════════════════════════════════════════════════════ -->
<form method="POST" action="<?= $base ?>/con-phase-items-update/<?= $phase_id ?>" id="itemsForm">
<div class="pi-table-wrap">
<table class="pi-table">
    <thead>
        <tr>
            <th style="width:28px">#</th>
            <th style="min-width:200px">Sub-item Name</th>
            <th style="text-align:center">Weight %</th>
            <th style="text-align:center;min-width:120px">Planned %</th>
            <th style="text-align:center;min-width:120px">Actual %</th>
            <th>Work Type</th>
            <th>Status</th>
            <th style="width:32px"></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($items as $idx => $item):
        $sm    = $statusMeta[$item['status']] ?? $statusMeta['Not Started'];
        $color = $phaseColors[$idx % count($phaseColors)];
        $actual = (float)$item['actual_progress'];
    ?>
        <tr>
            <td>
                <input type="hidden" name="items[<?= $idx ?>][id]" value="<?= $item['id'] ?>">
                <div style="width:20px;height:20px;border-radius:5px;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;background:<?= $color ?>22;color:<?= $color ?>">
                    <?= $item['item_order'] ?>
                </div>
            </td>
            <td>
                <input type="text"
                       name="items[<?= $idx ?>][item_name]"
                       class="pi-item-name"
                       value="<?= htmlspecialchars($item['item_name']) ?>"
                       required>
            </td>
            <td style="text-align:center">
                <input type="number"
                       name="items[<?= $idx ?>][weight_pct]"
                       class="pi-num-inp weight-field"
                       value="<?= number_format((float)$item['weight_pct'],2,'.','')?>"
                       min="0" max="100" step="0.01"
                       oninput="recalcWeight()">
            </td>
            <td style="text-align:center">
                <input type="number"
                       name="items[<?= $idx ?>][planned_progress]"
                       class="pi-progress-inp"
                       value="<?= number_format((float)$item['planned_progress'],1,'.','')?>"
                       min="0" max="100" step="0.1">
            </td>
            <td>
                <input type="number"
                       name="items[<?= $idx ?>][actual_progress]"
                       class="pi-progress-inp actual-progress"
                       data-idx="<?= $idx ?>"
                       value="<?= number_format($actual,1,'.','')?>"
                       min="0" max="100" step="0.1"
                       oninput="updateMiniBar(<?= $idx ?>, this.value)">
                <div class="pi-mini-bar">
                    <div class="pi-mini-fill" id="minibar_<?= $idx ?>"
                         style="width:<?= $actual ?>%;background:<?= $actual>=100?'#10b981':($actual>0?'#6366f1':'#94a3b8') ?>"></div>
                </div>
            </td>
            <td>
                <select name="items[<?= $idx ?>][work_type_id]" class="pi-status-sel" style="max-width:180px">
                    <option value="">-- select work type --</option>
                    <?php 
                    $grouped = [];
                    foreach ($workTypes as $wt) {
                        $g = $wt['design_type_name'] ?? 'Other';
                        $grouped[$g][] = $wt;
                    }
                    foreach ($grouped as $grp => $wts):
                    ?>
                    <optgroup label="<?= htmlspecialchars($grp) ?>">
                        <?php foreach ($wts as $wt): ?>
                        <option value="<?= $wt['id'] ?>" <?= ((int)$item['work_type_id'] === (int)$wt['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($wt['work_type_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <select name="items[<?= $idx ?>][status]" class="pi-status-sel" style="color:<?= $sm['color'] ?>">
                    <?php foreach ($statusMeta as $sv => $svm): ?>
                    <option value="<?= $sv ?>" <?= ($item['status']===$sv)?'selected':'' ?>
                            style="color:<?= $svm['color'] ?>"><?= $sv ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <a href="<?= $base ?>/con-phase-items-delete/<?= $item['id'] ?>"
                   class="pi-del-btn"
                   onclick="return confirm('Delete sub-item \'<?= htmlspecialchars($item['item_name']) ?>\'?')"
                   title="Delete">
                    <i class="fal fa-trash"></i>
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<!-- Action Bar -->
<div class="pi-action-bar">
    <div class="d-flex align-items-center gap-3">
        <div>
            <div style="font-size:10px;font-weight:700;color:var(--text-muted);margin-bottom:4px">Sub-item weights must sum to 100%</div>
            <div style="display:flex;align-items:center;gap:8px">
                <div class="pi-w-track">
                    <div class="pi-w-fill <?= $weightOk?'ok':($totalWeight>100?'over':'') ?>"
                         id="wFill" style="width:<?= min($totalWeight,100) ?>%"></div>
                </div>
                <span id="wLabel" style="font-size:11px;font-weight:800;color:<?= $weightOk?'var(--pi-green)':'var(--pi-red)' ?>">
                    <?= number_format($totalWeight,1) ?>%
                </span>
            </div>
        </div>
        <div id="phaseProgressDisplay" style="font-size:11px;color:var(--text-muted)">
            Phase progress will update on Save
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $base ?>/con-phase-plan/<?= $project_id ?>" class="btn btn-sm btn-outline-secondary">
            Cancel
        </a>
        <button type="submit" class="btn-pi-save" id="saveBtn" <?= $weightOk?'':'disabled' ?>>
            <i class="fal fa-check-circle me-1"></i>Save & Update Progress
        </button>
    </div>
</div>
</form>

</div></div>

<script>
// ── Weight recalc ─────────────────────────────────────────────────────
function recalcWeight() {
    const fields = document.querySelectorAll('.weight-field');
    let total = 0;
    fields.forEach(f => total += parseFloat(f.value) || 0);
    total = Math.round(total * 100) / 100;

    const ok = Math.abs(total - 100) <= 0.01;
    document.getElementById('wLabel').textContent = total.toFixed(1) + '%';
    document.getElementById('wLabel').style.color = ok ? 'var(--pi-green)' : 'var(--pi-red)';
    const fill = document.getElementById('wFill');
    fill.style.width = Math.min(total, 100) + '%';
    fill.className   = 'pi-w-fill ' + (ok ? 'ok' : (total > 100 ? 'over' : ''));
    document.getElementById('saveBtn').disabled = !ok;
}

// ── Mini progress bar update ──────────────────────────────────────────
function updateMiniBar(idx, val) {
    const pct = Math.min(100, Math.max(0, parseFloat(val) || 0));
    const bar = document.getElementById('minibar_' + idx);
    if (bar) {
        bar.style.width = pct + '%';
        bar.style.background = pct >= 100 ? '#10b981' : (pct > 0 ? '#6366f1' : '#94a3b8');
    }
}

// ── Form validation ───────────────────────────────────────────────────
document.getElementById('itemsForm').addEventListener('submit', function(e) {
    const fields = document.querySelectorAll('.weight-field');
    let total = 0;
    fields.forEach(f => total += parseFloat(f.value) || 0);
    if (Math.abs(total - 100) > 0.01) {
        e.preventDefault();
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon:'error', title:'Invalid Weight', text:'Sub-item weights must sum to 100%. Current: '+total.toFixed(2)+'%', confirmButtonColor:'#6366f1' });
        } else {
            alert('Sub-item weights must sum to 100%.');
        }
    }
});
</script>

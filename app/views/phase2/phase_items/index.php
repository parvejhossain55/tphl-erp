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
$phaseColors = ['var(--clr-theme-primary)','#10b981','#f59e0b','#ef4444','#14b8a6','var(--clr-theme-secondary)','#ec4899','#0ea5e9'];
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

:root { --pi-primary:var(--clr-theme-primary); --pi-green:#10b981; --pi-red:#ef4444; --pi-amber:#f59e0b; }

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
.pi-table tbody tr:hover { background:rgba(var(--clr-theme-primary-rgb),.03) }
.pi-table td { padding:8px 12px;vertical-align:middle }

.pi-item-name { font-size:12.5px;font-weight:600;border:1px solid transparent;border-radius:6px;padding:4px 7px;width:100%;background:transparent;color:inherit;transition:border-color .2s }
.pi-item-name:focus { outline:none;border-color:var(--pi-primary);background:rgba(var(--clr-theme-primary-rgb),.05) }

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

<!-- Add New Sub-item Card -->
<div class="mf-add-card" style="border-radius:12px;border:2px dashed rgba(var(--clr-theme-primary-rgb),.3);padding:16px 20px;margin-bottom:22px;background:rgba(var(--clr-theme-primary-rgb),.02)">
    <h6 style="font-size:12.5px;font-weight:700;margin-bottom:12px;color:var(--pi-primary)"><i class="fal fa-plus-circle me-2"></i>Add Custom Sub-work Item</h6>
    <form id="addSubItemForm">
        <div style="display:grid;grid-template-columns:3fr 1fr auto;gap:12px;align-items:end">
            <div class="mf-ctrl" style="display:flex;flex-direction:column;gap:4px">
                <label style="font-size:10px;font-weight:700;text-transform:uppercase;color:var(--text-muted)">Sub-item Name *</label>
                <input type="text" id="add_item_name" class="mf-inp" style="padding:7px 10px;border:1px solid rgba(0,0,0,.12);border-radius:8px;font-size:12.5px;background:transparent;color:inherit" placeholder="e.g. Columns Cast" required>
            </div>
            <div class="mf-ctrl" style="display:flex;flex-direction:column;gap:4px">
                <label style="font-size:10px;font-weight:700;text-transform:uppercase;color:var(--text-muted)">Weight %</label>
                <input type="number" id="add_weight_pct" class="mf-inp" style="padding:7px 10px;border:1px solid rgba(0,0,0,.12);border-radius:8px;font-size:12.5px;background:transparent;color:inherit" step="0.01" min="0" max="100" value="0">
            </div>
            <div>
                <button type="button" class="btn-pi-save" style="padding:8px 20px;border-radius:8px;font-size:13px;font-weight:700;background:var(--pi-primary);color:#fff;border:none;cursor:pointer" onclick="addSubItem()">
                    <i class="fal fa-plus"></i> Add
                </button>
            </div>
        </div>
    </form>
</div>

<!-- ══ ITEMS TABLE ══════════════════════════════════════════════════════ -->
<div class="pi-table-wrap">
<table class="pi-table">
    <thead>
        <tr>
            <th style="width:28px">#</th>
            <th style="min-width:200px">Sub-item Name</th>
            <th style="text-align:center;width:150px">Weight %</th>
            <th style="width:120px;text-align:right">Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($items as $idx => $item):
        $color = $phaseColors[$idx % count($phaseColors)];
    ?>
        <tr>
            <td>
                <div style="width:20px;height:20px;border-radius:5px;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;background:<?= (strpos($color, 'var(') === 0 ? ($color === 'var(--clr-theme-primary)' ? 'rgba(var(--clr-theme-primary-rgb),.15)' : 'rgba(var(--clr-theme-secondary-rgb),.15)') : $color.'22') ?>;color:<?= $color ?>">
                    <?= $item['item_order'] ?>
                </div>
            </td>
            <td>
                <span style="font-size:13px;font-weight:600;padding-left:7px;"><?= htmlspecialchars($item['item_name']) ?></span>
            </td>
            <td style="text-align:center;font-weight:700">
                <?= number_format((float)$item['weight_pct'],2) ?>%
            </td>
            <td style="text-align:right;white-space:nowrap">
                <button type="button" 
                        class="btn btn-sm btn-outline-primary"
                        style="padding:3px 9px;font-size:11px;font-weight:700;border-radius:6px"
                        onclick='openEditModal(<?= json_encode($item, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                    <i class="fal fa-edit me-1"></i>Edit
                </button>
                <a href="<?= $base ?>/con-phase-items-delete/<?= $item['id'] ?>"
                   class="pi-del-btn"
                   style="display:inline-flex;padding:3px 9px;margin-left:4px;vertical-align:middle"
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
        <div style="width:1px;height:24px;background:rgba(0,0,0,.08)"></div>
        <div id="phaseProgressDisplay" style="font-size:11.5px;color:var(--text-muted)">
            Phase Progress: <span style="font-weight:800;color:<?= $phaseProgress>=100?'var(--pi-green)':'var(--pi-primary)' ?>"><?= number_format($phaseProgress,1) ?>%</span>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $base ?>/con-phase-plan/<?= $project_id ?>" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;font-weight:600;padding:8px 16px;">
            Back to Phases
        </a>
    </div>
</div>

</div></div>

<!-- Edit Sub-item Modal -->
<div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px;border:none;box-shadow:0 10px 30px rgba(0,0,0,.15)">
            <div class="modal-header" style="border-bottom:1px solid rgba(0,0,0,.08);padding:16px 20px">
                <h5 class="modal-title" id="editItemModalLabel" style="font-size:14.5px;font-weight:800;color:var(--pi-primary)">
                    <i class="fal fa-edit me-2"></i>Edit Sub-work Item
                </h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large" style="font-size:12px"></i>
                </button>
            </div>
            <form id="editSubItemForm">
                <div class="modal-body" style="padding:20px;display:flex;flex-direction:column;gap:14px">
                    <input type="hidden" id="edit_item_id">
                    
                    <div style="display:flex;flex-direction:column;gap:5px">
                        <label style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted)">Sub-item Name *</label>
                        <input type="text" id="edit_item_name" class="form-control" style="border-radius:8px;font-size:13px;padding:8px 12px;background:transparent;border:1px solid rgba(0,0,0,.12)" required>
                    </div>
                    
                    <div style="display:flex;flex-direction:column;gap:5px">
                        <label style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted)">Weight % *</label>
                        <input type="number" id="edit_weight_pct" class="form-control" step="0.01" min="0" max="100" style="border-radius:8px;font-size:13px;padding:8px 12px;background:transparent;border:1px solid rgba(0,0,0,.12)" required>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid rgba(0,0,0,.08);padding:14px 20px">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal" style="border-radius:8px;font-weight:600">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-pi-save" style="background:var(--pi-primary);color:#fff;border:none;font-weight:700;padding:8px 16px;border-radius:8px">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ── Open Edit Modal ───────────────────────────────────────────────────
function openEditModal(item) {
    document.getElementById('edit_item_id').value = item.id;
    document.getElementById('edit_item_name').value = item.item_name;
    document.getElementById('edit_weight_pct').value = item.weight_pct;
    
    // Trigger modal show using Bootstrap 5 class
    const myModal = new bootstrap.Modal(document.getElementById('editItemModal'));
    myModal.show();
}

// ── Edit Form Submit via AJAX ──────────────────────────────────────────
document.getElementById('editSubItemForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('edit_item_id').value;
    const name = document.getElementById('edit_item_name').value.trim();
    const weight = document.getElementById('edit_weight_pct').value;

    if (!name) { alert('Sub-item name is required.'); return; }

    const data = new FormData();
    data.append('id', id);
    data.append('item_name', name);
    data.append('weight_pct', weight);

    fetch('<?= $base ?>/con-phase-item-edit', {
        method: 'POST',
        body: data
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            location.reload();
        } else {
            alert('Error: ' + (res.message || 'Failed to update item'));
        }
    })
    .catch(() => alert('Network error. Please try again.'));
});

// ── Add new sub-item via AJAX ─────────────────────────────────────────
function addSubItem() {
    const name = document.getElementById('add_item_name').value.trim();
    if (!name) { alert('Sub-item name is required.'); return; }

    const data = new FormData();
    data.append('phase_id', '<?= $phase_id ?>');
    data.append('item_name', name);
    data.append('weight_pct', document.getElementById('add_weight_pct').value);
    data.append('planned_progress', '0');
    data.append('actual_progress', '0');
    data.append('status', 'Not Started');

    fetch('<?= $base ?>/con-phase-item-add', {
        method: 'POST',
        body: data
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            location.reload();
        } else {
            alert('Error: ' + (res.message || 'Failed to add item'));
        }
    })
    .catch(() => alert('Network error. Please try again.'));
}
</script>

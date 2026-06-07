<?php
/**
 * Monthly Target → Work Items Management Form
 *
 * Variables passed from Con_MonthlyTargetController::show():
 *   $target        – phase_monthly_targets row (with phase_name, phase_weight, project_name, etc.)
 *   $items         – phase_monthly_target_items rows
 *   $summary       – progress summary aggregate
 *   $workTypes     – available work types
 *   $designTypes   – design type list for filter
 *   $phaseSubItems – project_phase_items for this phase (Layer 2 sub-items)
 */
$base       = $GLOBALS['base_url'];
$target     = $target    ?? [];
$items      = $items     ?? [];
$summary    = $summary   ?? [];
$workTypes  = $workTypes  ?? [];
$designTypes= $designTypes?? [];
$phaseSubItems = $phaseSubItems ?? [];
$is_readonly   = $is_readonly   ?? false;

$targetId   = (int)($target['id']         ?? 0);
$projectId  = (int)($target['project_id'] ?? 0);
$phaseName  = htmlspecialchars($target['phase_name'] ?? 'Phase');
$monthLabel = !empty($target['target_month'])
    ? (new DateTime($target['target_month']))->format('F Y')
    : 'N/A';
$planStatus = $target['planning_status'] ?? 'Draft';
$responsibleTeam = $target['responsible_team'] ?? '';

// Summary stats
$totalItems   = (int)($summary['total_items']     ?? 0);
$totalPlanQty = (float)($summary['total_planned_qty']  ?? 0);
$totalActQty  = (float)($summary['total_actual_qty']   ?? 0);
$avgPlanned   = (float)($summary['avg_planned_pct']    ?? 0);
$avgActual    = (float)($summary['avg_actual_pct']     ?? 0);

$statusColors = [
    'Draft'     => ['bg' => '#e2e8f0', 'color' => '#64748b'],
    'In Review' => ['bg' => '#fef3c7', 'color' => '#d97706'],
    'Approved'  => ['bg' => '#d1fae5', 'color' => '#059669'],
];
$sm = $statusColors[$planStatus] ?? $statusColors['Draft'];

$priorityColors = [
    'High'   => '#ef4444',
    'Medium' => '#f59e0b',
    'Low'    => '#10b981',
];
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
:root { --pri: var(--clr-theme-primary); --green: #10b981; --amber: #f59e0b; --red: #ef4444; }

/* Layout */
.mf-header { display:flex;align-items:flex-start;justify-content:space-between;gap:14px;margin-bottom:20px;flex-wrap:wrap }
.mf-breadcrumb { font-size:11.5px;color:var(--text-muted);margin-bottom:4px }
.mf-breadcrumb a { color:var(--pri);text-decoration:none }
.mf-title { font-size:18px;font-weight:800;margin:0 }
.mf-badge { display:inline-block;font-size:10.5px;font-weight:700;padding:3px 11px;border-radius:999px }

/* Summary cards */
.mf-summary { display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px }
.mf-stat-card { border-radius:12px;border:1px solid rgba(0,0,0,.07);padding:14px 18px }
.mf-stat-val  { font-size:22px;font-weight:800;line-height:1 }
.mf-stat-lbl  { font-size:10.5px;color:var(--text-muted);margin-top:3px;font-weight:600 }
.mf-prog-bar  { height:5px;border-radius:3px;background:rgba(0,0,0,.07);overflow:hidden;margin-top:8px }
.mf-prog-fill { height:100%;border-radius:3px }

/* Phase info bar */
.mf-info-bar { display:flex;gap:12px;flex-wrap:wrap;align-items:center;padding:12px 18px;border-radius:10px;border:1px solid rgba(0,0,0,.07);margin-bottom:18px;font-size:12.5px }
.mf-info-item { display:flex;align-items:center;gap:6px;color:var(--text-muted) }
.mf-info-item strong { color:inherit;font-weight:700 }

/* Status update form */
.mf-status-card { border-radius:12px;border:1px solid rgba(0,0,0,.07);padding:16px 20px;margin-bottom:20px }
.mf-status-card h6 { font-size:12.5px;font-weight:700;margin-bottom:12px;color:var(--text-muted) }
.mf-status-row { display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end }
.mf-ctrl { display:flex;flex-direction:column;gap:4px }
.mf-ctrl label { font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted) }
.mf-sel,.mf-inp { padding:7px 10px;border:1px solid rgba(0,0,0,.12);border-radius:8px;font-size:12.5px;background:transparent;color:inherit;width:100%;box-sizing:border-box }
.mf-sel:focus,.mf-inp:focus { outline:none;border-color:var(--pri) }
.btn-save { padding:7px 18px;border-radius:8px;font-size:12.5px;font-weight:700;background:var(--pri);color:#fff;border:none;cursor:pointer }

/* Add item form */
.mf-add-card { border-radius:12px;border:2px dashed rgba(var(--clr-theme-primary-rgb),.3);padding:16px 20px;margin-bottom:22px;background:rgba(var(--clr-theme-primary-rgb),.02) }
.mf-add-card h6 { font-size:12.5px;font-weight:700;margin-bottom:12px;color:var(--pri) }
.mf-add-grid { display:grid;grid-template-columns:2fr 1fr 1fr 1fr 1fr auto;gap:9px;align-items:end }

/* Items table */
.mf-table-wrap { overflow-x:auto }
table.mf-table { width:100%;border-collapse:collapse;font-size:12.5px }
table.mf-table th { font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);padding:8px 10px;border-bottom:2px solid rgba(0,0,0,.08);white-space:nowrap }
table.mf-table td { padding:9px 10px;border-bottom:1px solid rgba(0,0,0,.05);vertical-align:middle }
table.mf-table tr:last-child td { border-bottom:none }
table.mf-table input,table.mf-table select { width:100%;padding:4px 7px;border:1px solid rgba(0,0,0,.1);border-radius:6px;font-size:12px;background:transparent;color:inherit }
table.mf-table input:focus,table.mf-table select:focus { outline:none;border-color:var(--pri) }

.pct-badge { display:inline-block;font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:999px;background:rgba(var(--clr-theme-primary-rgb),.1);color:var(--pri) }
.pct-badge.green { background:rgba(16,185,129,.1);color:var(--green) }
.pct-badge.amber { background:rgba(245,158,11,.1);color:var(--amber) }

.btn-del-item { font-size:11px;color:var(--red);text-decoration:none;display:inline-flex;align-items:center;gap:4px }
.btn-del-item:hover { text-decoration:underline }

.mf-section-title { font-size:13px;font-weight:700;margin-bottom:12px;display:flex;align-items:center;gap:8px }

.mf-weekly-btn { display:inline-flex;align-items:center;gap:6px;font-size:11.5px;font-weight:700;padding:6px 14px;border-radius:8px;background:rgba(16,185,129,.1);color:var(--green);text-decoration:none;border:1px solid rgba(16,185,129,.2) }
.mf-weekly-btn:hover { background:rgba(16,185,129,.18);color:var(--green) }
</style>

<div class="row"><div class="col-12">

<!-- Header -->
<div class="mf-header">
    <div>
        <div class="mf-breadcrumb">
            <a href="<?= $base ?>/con-projects">Projects</a> ›
            <a href="<?= $base ?>/con-monthly-targets/<?= $projectId ?>"><?= htmlspecialchars($target['project_name'] ?? 'Project') ?></a> ›
            <span>Monthly Target — <?= $phaseName ?></span>
        </div>
        <h4 class="mf-title">
            <i class="fal fa-tasks me-2" style="color:var(--pri)"></i>
            <?= $phaseName ?> — <?= $monthLabel ?>
        </h4>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <a href="<?= $base ?>/con-weekly-progress/<?= $targetId ?>?week=1"
           class="mf-weekly-btn">
            <i class="fal fa-chart-line"></i>Weekly Progress
        </a>
        <?php if ($is_readonly): ?>
        <a href="<?= $base ?>/con-project-plan-targets"
           class="btn btn-sm btn-outline-secondary" style="font-size:12px">
            <i class="fal fa-arrow-left me-1"></i>Back to Targets
        </a>
        <?php else: ?>
        <a href="<?= $base ?>/con-monthly-targets/<?= $projectId ?>"
           class="btn btn-sm btn-outline-secondary" style="font-size:12px">
            <i class="fal fa-arrow-left me-1"></i>Back
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Summary Cards -->
<div class="mf-summary">
    <div class="mf-stat-card">
        <div class="mf-stat-val"><?= $totalItems ?></div>
        <div class="mf-stat-lbl">Work Items</div>
    </div>
    <div class="mf-stat-card">
        <div class="mf-stat-val" style="color:var(--pri)"><?= number_format($avgPlanned, 1) ?>%</div>
        <div class="mf-stat-lbl">Avg Planned</div>
        <div class="mf-prog-bar"><div class="mf-prog-fill" style="width:<?= min(100,$avgPlanned) ?>%;background:var(--pri)"></div></div>
    </div>
    <div class="mf-stat-card">
        <div class="mf-stat-val" style="color:var(--green)"><?= number_format($avgActual, 1) ?>%</div>
        <div class="mf-stat-lbl">Avg Actual</div>
        <div class="mf-prog-bar"><div class="mf-prog-fill" style="width:<?= min(100,$avgActual) ?>%;background:var(--green)"></div></div>
    </div>
    <div class="mf-stat-card">
        <div class="mf-stat-val" style="color:<?= $planStatus==='Approved'?'var(--green)':($planStatus==='In Review'?'var(--amber)':'var(--text-muted)') ?>">
            <?= htmlspecialchars($planStatus) ?>
        </div>
        <div class="mf-stat-lbl">Planning Status</div>
    </div>
</div>

<!-- Phase Info Bar -->
<div class="mf-info-bar">
    <div class="mf-info-item"><i class="fal fa-layer-group"></i>Phase: <strong><?= $phaseName ?></strong></div>
    <div class="mf-info-item"><i class="fal fa-calendar"></i>Month: <strong><?= $monthLabel ?></strong></div>
    <?php if ($target['responsible_team']): ?>
    <div class="mf-info-item"><i class="fal fa-users"></i>Team: <strong><?= htmlspecialchars($target['responsible_team']) ?></strong></div>
    <?php endif; ?>
    <?php if (!empty($target['planned_start_date'])): ?>
    <div class="mf-info-item"><i class="fal fa-calendar-check"></i>Phase: <strong><?= $target['planned_start_date'] ?></strong> → <strong><?= $target['planned_end_date'] ?></strong></div>
    <?php endif; ?>
</div>

<!-- Status Update — hidden for site engineers -->
<?php if (!$is_readonly): ?>
<div class="mf-status-card">
    <h6><i class="fal fa-cog me-2"></i>Update Planning Status & Phase Details</h6>
    <form method="POST" action="<?= $base ?>/con-monthly-target-status/<?= $targetId ?>">
        <div class="mf-status-row">
            <div class="mf-ctrl">
                <label>Planning Status</label>
                <select name="planning_status" class="mf-sel">
                    <?php foreach (['Draft','In Review','Approved'] as $s): ?>
                    <option value="<?= $s ?>" <?= $planStatus===$s?'selected':'' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mf-ctrl">
                <label>Responsible Team</label>
                <input type="text" name="responsible_team" class="mf-inp"
                       value="<?= htmlspecialchars($responsibleTeam) ?>" placeholder="e.g. Civil Team">
            </div>
            <div class="mf-ctrl">
                <label>Default Contractor</label>
                <select name="contractor_id" class="mf-sel">
                    <option value="">-- Select Contractor --</option>
                    <?php foreach ($contractors as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($target['contractor_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mf-ctrl">
                <label>Phase Status</label>
                <select name="phase_status" class="mf-sel">
                    <?php foreach (['Not Started','In Progress','Completed','At Risk','Delayed'] as $ps): ?>
                    <option value="<?= $ps ?>" <?= ($target['phase_status']??'')===$ps?'selected':'' ?>><?= $ps ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mf-ctrl">
                <label>Phase Start Date</label>
                <input type="date" name="planned_start_date" class="mf-inp"
                       value="<?= $target['planned_start_date'] ?? '' ?>">
            </div>
            <div class="mf-ctrl">
                <label>Phase End Date</label>
                <input type="date" name="planned_end_date" class="mf-inp"
                       value="<?= $target['planned_end_date'] ?? '' ?>">
            </div>
            <div>
                <button type="submit" class="btn-save">
                    <i class="fal fa-save me-1"></i>Save
                </button>
            </div>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Add New Item — hidden for site engineers -->
<?php if (!$is_readonly): ?>
<div class="mf-add-card">
    <h6><i class="fal fa-plus-circle me-2"></i>Add Work Item</h6>
    <form id="addItemForm">
        <div style="display:flex;flex-wrap:wrap;gap:4px;align-items:flex-end">
            <div class="mf-ctrl" style="flex:3;min-width:180px">
                <label>Work Item (Sub-item) *</label>
                <select id="wi_name" class="mf-sel" required onchange="onSubItemSelect()">
                    <option value="">-- Select Sub-item --</option>
                    <?php foreach ($phaseSubItems as $sub): ?>
                    <option value="<?= htmlspecialchars($sub['item_name']) ?>"
                            data-id="<?= htmlspecialchars($sub['id']) ?>"
                            data-weight="<?= htmlspecialchars($sub['weight_pct']) ?>"
                            data-worktype="<?= htmlspecialchars($sub['work_type_id'] ?? '') ?>">
                        <?= htmlspecialchars($sub['item_name']) ?> (<?= number_format((float)$sub['weight_pct'], 2) ?>%)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mf-ctrl" style="flex:0 0 88px;min-width:80px">
                <label>Sub Weight</label>
                <input type="text" id="wi_sub_weight" class="mf-inp" readonly value="0.00%">
            </div>
            <div class="mf-ctrl" style="flex:0 0 107px;min-width:90px">
                <label>Target Planned %</label>
                <input type="number" id="wi_planned_progress_pct" class="mf-inp" min="0" max="100" step="0.1" value="0">
            </div>
            <div class="mf-ctrl" style="flex:2;min-width:140px">
                <label>Work Type <span style="color:var(--text-muted);font-weight:400">(optional)</span></label>
                <select id="wi_work_type" class="mf-sel">
                    <option value="">-- None --</option>
                    <?php foreach ($workTypes as $wt): ?>
                    <option value="<?= $wt['id'] ?>"><?= htmlspecialchars($wt['work_type_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mf-ctrl" style="flex:2;min-width:140px">
                <label>Contractor</label>
                <select id="wi_contractor_id" class="mf-sel">
                    <option value="">-- None --</option>
                    <?php foreach ($contractors as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($target['contractor_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mf-ctrl" style="flex:0 0 80px;min-width:70px">
                <label>Unit</label>
                <input type="text" id="wi_unit" class="mf-inp" placeholder="RFT / NO">
            </div>
            <div class="mf-ctrl" style="flex:0 0 95px;min-width:80px">
                <label>Target Qty</label>
                <input type="number" id="wi_qty" class="mf-inp" step="0.01" min="0" value="0">
            </div>
            <div class="mf-ctrl" style="flex:0 0 135px;min-width:120px">
                <label>Start Date</label>
                <input type="date" id="wi_start_date" class="mf-inp">
            </div>
            <div class="mf-ctrl" style="flex:0 0 135px;min-width:120px">
                <label>End Date</label>
                <input type="date" id="wi_end_date" class="mf-inp">
            </div>
            <div class="mf-ctrl" style="flex:0 0 95px;min-width:85px">
                <label>Priority</label>
                <select id="wi_priority" class="mf-sel">
                    <option value="Medium">Medium</option>
                    <option value="High">High</option>
                    <option value="Low">Low</option>
                </select>
            </div>
            <div style="align-self:flex-end;padding-bottom:1px">
                <button type="button" class="btn-save" onclick="addItem()" title="Add Work Item">
                    <i class="fal fa-plus me-1"></i> Add
                </button>
            </div>
        </div>
    </form>
</div>
<?php endif; ?>

<?php if ($is_readonly): ?>
<!-- READ-ONLY items table for site engineers -->
<div class="mf-section-title">
    <i class="fal fa-list-check" style="color:var(--pri)"></i>
    Work Items
    <span style="font-size:11px;font-weight:500;color:var(--text-muted)">(<?= $totalItems ?> items)</span>
    <span class="mf-badge" style="background:rgba(16,185,129,.1);color:var(--green);font-size:10px;margin-left:8px">
        <i class="fal fa-eye me-1"></i>View Only
    </span>
</div>
<?php if (empty($items)): ?>
<div style="text-align:center;padding:40px;color:var(--text-muted)">
    <i class="fal fa-clipboard-list" style="font-size:34px;opacity:.25;display:block;margin-bottom:10px"></i>
    No work items yet.
</div>
<?php else: ?>
<div class="mf-table-wrap">
<table class="mf-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Work Item</th>
            <th>Unit</th>
            <th>Target Qty</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Wk 1</th>
            <th>Wk 2</th>
            <th>Wk 3</th>
            <th>Wk 4</th>
            <th style="text-align:center">Planned %</th>
            <th style="text-align:center">Actual %</th>
            <th>Priority</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($items as $i => $item):
        $actualPct = (float)($item['actual_progress_pct'] ?? 0);
        $prioColor = $priorityColors[$item['priority'] ?? 'Medium'] ?? '#64748b';
    ?>
    <tr>
        <td style="color:var(--text-muted);font-weight:600"><?= $i+1 ?></td>
        <td><span style="font-weight:600;font-size:12.5px"><?= htmlspecialchars($item['work_item_name']) ?></span></td>
        <td><?= htmlspecialchars($item['unit'] ?? '') ?></td>
        <td style="font-weight:600"><?= number_format((float)($item['monthly_target_qty'] ?? 0), 2) ?></td>
        <td><?= htmlspecialchars($item['start_date'] ?? '') ?></td>
        <td><?= htmlspecialchars($item['end_date'] ?? '') ?></td>
        <td><?= number_format((float)($item['week_1_qty'] ?? 0), 2) ?></td>
        <td><?= number_format((float)($item['week_2_qty'] ?? 0), 2) ?></td>
        <td><?= number_format((float)($item['week_3_qty'] ?? 0), 2) ?></td>
        <td><?= number_format((float)($item['week_4_qty'] ?? 0), 2) ?></td>
        <td style="text-align:center">
            <span class="pct-badge"><?= number_format((float)($item['planned_progress_pct'] ?? 0), 1) ?>%</span>
        </td>
        <td style="text-align:center">
            <span class="pct-badge <?= $actualPct>=100?'green':($actualPct>0?'amber':'') ?>">
                <?= number_format($actualPct, 1) ?>%
            </span>
        </td>
        <td>
            <span style="font-size:11px;font-weight:700;color:<?= $prioColor ?>"><?= htmlspecialchars($item['priority'] ?? '') ?></span>
        </td>
        <td>
            <span style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($item['status'] ?? '') ?></span>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

<?php else: ?>
<!-- EDITABLE items table for PM/Admin -->
<div class="mf-section-title">
    <i class="fal fa-list-check" style="color:var(--pri)"></i>
    Work Items
    <span style="font-size:11px;font-weight:500;color:var(--text-muted)">(<?= $totalItems ?> items)</span>
</div>

<?php if (empty($items)): ?>
<div style="text-align:center;padding:40px;color:var(--text-muted)">
    <i class="fal fa-clipboard-list" style="font-size:34px;opacity:.25;display:block;margin-bottom:10px"></i>
    No work items yet. Add the first item above.
</div>
<?php else: ?>
<form method="POST" action="<?= $base ?>/con-monthly-target-save/<?= $targetId ?>">
<div class="mf-table-wrap">
<table class="mf-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Work Item</th>
            <th>Work Type <small style="font-weight:400;opacity:.7">(optional)</small></th>
            <th>Contractor</th>
            <th>Unit</th>
            <th>Monthly Qty</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Wk 1</th>
            <th>Wk 2</th>
            <th>Wk 3</th>
            <th>Wk 4</th>
            <th style="text-align:center">Sub Weight %</th>
            <th style="text-align:center">Planned Progress %</th>
            <th style="text-align:center">Actual Progress %</th>
            <th style="text-align:center">Actual Weight %</th>
            <th>Priority</th>
            <th>Status</th>
            <th>Recovery Notes</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($items as $i => $item): ?>
    <?php
        $actualPct = (float)($item['actual_progress_pct'] ?? 0);
        $prioColor = $priorityColors[$item['priority'] ?? 'Medium'] ?? '#64748b';
        $subWeight = (float)($item['sub_item_weight'] ?? 0);
    ?>
    <tr>
        <td style="color:var(--text-muted);font-weight:600"><?= $i+1 ?></td>
        <td>
            <span style="font-weight:600;font-size:12.5px;"><?= htmlspecialchars($item['work_item_name']) ?></span>
            <input type="hidden" name="items[<?= $i ?>][work_item_name]" value="<?= htmlspecialchars($item['work_item_name']) ?>">
            <input type="hidden" name="items[<?= $i ?>][phase_item_id]" value="<?= htmlspecialchars($item['phase_item_id'] ?? '') ?>">
            <input type="hidden" name="items[<?= $i ?>][id]" value="<?= $item['id'] ?>">
        </td>
        <td>
            <select name="items[<?= $i ?>][work_type_id]" style="min-width:120px">
                <option value="">-- None --</option>
                <?php foreach ($workTypes as $wt): ?>
                <option value="<?= $wt['id'] ?>" <?= ($item['work_type_id']==$wt['id'])?'selected':'' ?>>
                    <?= htmlspecialchars($wt['work_type_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <select name="items[<?= $i ?>][contractor_id]" style="min-width:120px">
                <option value="">-- None --</option>
                <?php foreach ($contractors as $c): ?>
                <option value="<?= $c['id'] ?>" <?= (($item['contractor_id'] ?? 0) == $c['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="text" name="items[<?= $i ?>][unit]" value="<?= htmlspecialchars($item['unit'] ?? '') ?>" style="width:60px"></td>
        <td><input type="number" name="items[<?= $i ?>][monthly_target_qty]" value="<?= (float)($item['monthly_target_qty'] ?? 0) ?>" step="0.01" style="width:80px"></td>
        <td><input type="date" name="items[<?= $i ?>][start_date]" value="<?= htmlspecialchars($item['start_date'] ?? '') ?>" style="width:120px"></td>
        <td><input type="date" name="items[<?= $i ?>][end_date]" value="<?= htmlspecialchars($item['end_date'] ?? '') ?>" style="width:120px"></td>
        <td><input type="number" name="items[<?= $i ?>][week_1_qty]" value="<?= (float)($item['week_1_qty'] ?? 0) ?>" step="0.01" style="width:70px"></td>
        <td><input type="number" name="items[<?= $i ?>][week_2_qty]" value="<?= (float)($item['week_2_qty'] ?? 0) ?>" step="0.01" style="width:70px"></td>
        <td><input type="number" name="items[<?= $i ?>][week_3_qty]" value="<?= (float)($item['week_3_qty'] ?? 0) ?>" step="0.01" style="width:70px"></td>
        <td><input type="number" name="items[<?= $i ?>][week_4_qty]" value="<?= (float)($item['week_4_qty'] ?? 0) ?>" step="0.01" style="width:70px"></td>
        <td style="text-align:center;font-weight:600">
            <?= number_format($subWeight, 2) ?>%
            <input type="hidden" id="sub_w_<?= $i ?>" value="<?= $subWeight ?>">
        </td>
        <td style="text-align:center">
            <input type="number" name="items[<?= $i ?>][planned_progress_pct]"
                   id="plan_pct_<?= $i ?>"
                   value="<?= (float)($item['planned_progress_pct'] ?? 0) ?>"
                   step="0.1" min="0" max="100" style="width:65px">
        </td>
        <td style="text-align:center">
            <span class="pct-badge <?= $actualPct>=100?'green':($actualPct>0?'amber':'') ?>">
                <?= number_format($actualPct, 1) ?>%
            </span>
        </td>
        <td style="text-align:center;font-weight:700;color:var(--green)">
            <?= number_format(($subWeight * $actualPct) / 100, 2) ?>%
        </td>
        <td>
            <select name="items[<?= $i ?>][priority]" style="width:80px">
                <?php foreach (['High','Medium','Low'] as $p): ?>
                <option value="<?= $p ?>" <?= ($item['priority']===$p)?'selected':'' ?>><?= $p ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <select name="items[<?= $i ?>][status]" style="min-width:90px">
                <?php foreach (['Draft','Ready','Approved','At Risk','Critical'] as $st): ?>
                <option value="<?= $st ?>" <?= ($item['status']===$st)?'selected':'' ?>><?= $st ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <input type="text" name="items[<?= $i ?>][recovery_notes]" value="<?= htmlspecialchars($item['recovery_notes'] ?? '') ?>" placeholder="e.g. Incl. 500 shortfall" style="min-width:140px">
        </td>
        <td>
            <a href="<?= $base ?>/con-monthly-item-delete/<?= $item['id'] ?>"
               class="btn-del-item"
               onclick="return confirm('Delete this item?')">
                <i class="fal fa-trash"></i>
            </a>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<div class="mt-3 d-flex gap-2">
    <button type="submit" class="btn-save">
        <i class="fal fa-save me-1"></i>Save All Items
    </button>
    <a href="<?= $base ?>/con-monthly-targets/<?= $projectId ?>" class="btn btn-sm btn-outline-secondary" style="font-size:12px">
        Cancel
    </a>
</div>
</form>
<?php endif; ?>

<?php endif; // end is_readonly check for items section ?>

</div></div>

<script>
// ── Handle Sub-item selection changed ──────────────────────────────────
function onSubItemSelect() {
    var select = document.getElementById('wi_name');
    var opt = select.options[select.selectedIndex];
    var weight = opt ? opt.getAttribute('data-weight') : '';
    var worktype = opt ? opt.getAttribute('data-worktype') : '';
    
    document.getElementById('wi_sub_weight').value = weight ? parseFloat(weight).toFixed(2) + '%' : '0.00%';
    
    var wtSelect = document.getElementById('wi_work_type');
    if (wtSelect) {
        wtSelect.value = worktype || '';
    }
}



// ── Add Item ───────────────────────────────────────────────────────────
function addItem() {
    var select = document.getElementById('wi_name');
    var opt = select.options[select.selectedIndex];
    var phaseItemId = opt ? opt.getAttribute('data-id') : '';
    var name = select.value.trim();
    if (!name) { alert('Work item selection is required.'); return; }
 
    var data = new FormData();
    data.append('target_id', '<?= $targetId ?>');
    data.append('work_item_name', name);
    data.append('phase_item_id', phaseItemId);
    data.append('work_type_id', document.getElementById('wi_work_type').value);
    data.append('unit', document.getElementById('wi_unit').value);
    data.append('monthly_target_qty', document.getElementById('wi_qty').value);
    data.append('start_date', document.getElementById('wi_start_date').value);
    data.append('end_date', document.getElementById('wi_end_date').value);
    data.append('priority', document.getElementById('wi_priority').value);
    data.append('contractor_id', document.getElementById('wi_contractor_id').value);
    data.append('status', 'Draft');
    data.append('planned_progress_pct', document.getElementById('wi_planned_progress_pct').value);

    fetch('<?= $base ?>/con-monthly-item-add', {
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
<?php
$addPage         = true;
$show_breadcrumb = false;
$base            = $GLOBALS['base_url'];

$project     = $project     ?? [];
$phases      = $phases      ?? [];
$drawings    = $drawings    ?? [];
$totalWeight = $totalWeight ?? 0;
$timeline    = $timeline    ?? [];
$project_id  = $project['id'] ?? 0;

// Timeline dates
$proj_start_date = $timeline['project_start_date']      ?? date('Y-m-d');
$con_start_date  = $timeline['construction_start_date'] ?? date('Y-m-d');
$con_end_date    = $timeline['construction_end_date']   ?? date('Y-m-d');
$total_months    = (int)($timeline['handover_months']   ?? 24);

// Helper: date string → project month number (1-based)
function dateToMonth(string $dateStr, string $projStart): int {
    if (!$dateStr || !$projStart) return 1;
    $d1 = new DateTime($projStart);
    $d2 = new DateTime($dateStr);
    $m  = ($d2->format('Y') - $d1->format('Y')) * 12 + ($d2->format('n') - $d1->format('n'));
    return max(1, $m + 1);
}

// Pre-compute start/end months for each phase
$projStartDate = new DateTime($proj_start_date);
foreach ($phases as &$ph) {
    $ph['start_month']     = $ph['planned_start_date'] ? dateToMonth($ph['planned_start_date'], $proj_start_date) : 1;
    $ph['end_month']       = $ph['planned_end_date']   ? dateToMonth($ph['planned_end_date'],   $proj_start_date) : 2;
    $ph['duration_months'] = max(1, $ph['end_month'] - $ph['start_month']);
}
unset($ph);

$weightOk = abs($totalWeight - 100) <= 0.01;

$statusMeta = [
    'Not Started' => ['color' => '#94a3b8', 'bar' => '#94a3b8', 'badge' => 'rgba(148,163,184,.15)'],
    'In Progress' => ['color' => '#f59e0b', 'bar' => '#f59e0b', 'badge' => 'rgba(245,158,11,.15)'],
    'Completed'   => ['color' => '#10b981', 'bar' => '#10b981', 'badge' => 'rgba(16,185,129,.15)'],
    'At Risk'     => ['color' => '#ef4444', 'bar' => '#ef4444', 'badge' => 'rgba(239,68,68,.15)'],
    'Delayed'     => ['color' => 'var(--clr-theme-secondary)', 'bar' => 'var(--clr-theme-secondary)', 'badge' => 'rgba(var(--clr-theme-secondary-rgb),.15)'],
];
$phaseColors = ['var(--clr-theme-primary)','#10b981','#f59e0b','#ef4444','#14b8a6','var(--clr-theme-secondary)','#ec4899','#0ea5e9'];
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

:root {
    --pp-primary : var(--clr-theme-primary);
    --pp-green   : #10b981;
    --pp-amber   : #f59e0b;
    --pp-red     : #ef4444;
    --pp-radius  : 12px;
}

/* ── Top KPI Strip ───────────────────────────── */
.pp-kpi-strip {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}
.pp-kpi-card {
    border-radius: 12px;
    padding: 14px 16px;
    border: 1px solid rgba(0,0,0,.06);
    display: flex;
    align-items: center;
    gap: 12px;
}
.pp-kpi-icon {
    width: 38px; height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}
.pp-kpi-label { font-size: 10px; font-weight: 700; letter-spacing: .6px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 2px; }
.pp-kpi-value { font-size: 17px; font-weight: 800; line-height: 1.2; }

/* ── Main Grid ───────────────────────────────── */
.pp-main-grid {
    display: grid;
    grid-template-columns: 58% 1fr;
    gap: 18px;
    align-items: start;
}

/* ── Phase Config Table ──────────────────────── */
.pp-config-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
}
.pp-config-header h6 { font-size: 14px; font-weight: 800; margin: 0; }
.pp-config-wrap { border-radius: 12px; border: 1px solid rgba(0,0,0,.07); overflow-x: auto; -webkit-overflow-scrolling: touch; }
.pp-config-table { width: 100%; border-collapse: collapse; min-width: 800px; }
.pp-config-table thead th {
    padding: 9px 10px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .7px;
    text-transform: uppercase;
    color: var(--text-muted);
    border-bottom: 1px solid rgba(0,0,0,.07);
    white-space: nowrap;
    text-align: center;
}
.pp-config-table thead th:nth-child(2) { text-align: left; }
.pp-config-table tbody tr { border-bottom: 1px solid rgba(0,0,0,.05); transition: background .15s; }
.pp-config-table tbody tr:last-child { border-bottom: none; }
.pp-config-table tbody tr:hover { background: rgba(var(--clr-theme-primary-rgb),.03); }
.pp-config-table td { padding: 8px 10px; vertical-align: middle; }

.pp-phase-name-inp {
    font-size: 12.5px; font-weight: 600;
    border: 1px solid transparent; border-radius: 6px;
    padding: 4px 7px; width: 100%;
    background: transparent; color: inherit;
    transition: border-color .2s;
}
.pp-phase-name-inp:focus { outline: none; border-color: var(--pp-primary); background: rgba(var(--clr-theme-primary-rgb),.05); }

.pp-month-inp {
    width: 52px; text-align: center;
    font-size: 13px; font-weight: 700;
    border: 1px solid rgba(0,0,0,.12); border-radius: 7px;
    padding: 4px 6px;
    background: transparent; color: inherit;
    transition: border-color .2s;
}
.pp-month-inp:focus { outline: none; border-color: var(--pp-primary); }

.pp-dur-badge {
    font-size: 11px; font-weight: 700;
    padding: 2px 8px;
    border-radius: 6px;
    background: rgba(var(--clr-theme-primary-rgb),.1);
    color: var(--pp-primary);
    white-space: nowrap;
    display: inline-block;
    min-width: 64px;
    text-align: center;
}
.pp-weight-inp {
    width: 58px; text-align: center;
    font-size: 12px; font-weight: 700;
    border: 1px solid rgba(0,0,0,.12); border-radius: 7px;
    padding: 4px 6px;
    background: transparent; color: inherit;
}
.pp-weight-inp:focus { outline: none; border-color: var(--pp-primary); }

.pp-status-sel {
    font-size: 11px; font-weight: 600;
    border: 1px solid rgba(0,0,0,.1); border-radius: 7px;
    padding: 4px 7px;
    background: transparent; color: inherit; cursor: pointer;
}
.pp-del-btn {
    width: 26px; height: 26px; border-radius: 6px;
    background: rgba(239,68,68,.1); color: var(--pp-red);
    border: none; cursor: pointer; display: inline-flex;
    align-items: center; justify-content: center;
    font-size: 11px; transition: background .15s;
}
.pp-del-btn:hover { background: rgba(239,68,68,.22); }

/* ── Action Bar ──────────────────────────────── */
.pp-action-bar {
    display: flex; align-items: center; justify-content: space-between;
    margin-top: 14px; flex-wrap: wrap; gap: 10px;
}
.pp-weight-indicator {
    font-size: 12px; font-weight: 700; padding: 5px 14px;
    border-radius: 999px;
}
.btn-add-phase {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 12px; font-weight: 600;
    padding: 7px 14px; border-radius: 8px;
    background: rgba(var(--clr-theme-primary-rgb),.1); color: var(--pp-primary);
    border: 1px dashed rgba(var(--clr-theme-primary-rgb),.4); cursor: pointer;
    transition: background .2s;
}
.btn-add-phase:hover { background: rgba(var(--clr-theme-primary-rgb),.18); }
.btn-pp-save {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 8px 20px; border-radius: 8px; font-size: 13px; font-weight: 700;
    background: var(--pp-primary); color: #fff; border: none; cursor: pointer;
    transition: opacity .2s; 
}
.btn-pp-save:hover { opacity: .88; }
.btn-pp-save:disabled { opacity: .45; cursor: not-allowed; }

/* ── Right Column ────────────────────────────── */
.pp-right-col { display: flex; flex-direction: column; gap: 16px; }

/* ── Gantt ───────────────────────────────────── */
.pp-gantt-wrap {
    border-radius: 12px; border: 1px solid rgba(0,0,0,.07); padding: 16px;
}
.pp-gantt-header { font-size: 13px; font-weight: 800; margin-bottom: 12px; }
.pp-gantt-ruler {
    display: flex; margin-bottom: 8px;
    margin-left: 130px;
    font-size: 9.5px; font-weight: 700; color: var(--text-muted);
}
.pp-gantt-row { display: flex; align-items: center; margin-bottom: 6px; }
.pp-gantt-label {
    width: 130px; min-width: 130px; font-size: 11px; font-weight: 600;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    padding-right: 10px; color: var(--text-muted);
}
.pp-gantt-track { flex: 1; height: 18px; border-radius: 4px; background: rgba(0,0,0,.05); position: relative; }
.pp-gantt-bar {
    position: absolute; height: 100%; border-radius: 4px;
    display: flex; align-items: center;
    font-size: 9px; font-weight: 700; color: #fff;
    padding: 0 4px; overflow: hidden; white-space: nowrap;
    transition: opacity .2s;
}
.pp-gantt-bar:hover { opacity: .85; }

/* ── Legend ──────────────────────────────────── */
.pp-legend {
    display: flex; gap: 14px; margin-top: 10px; flex-wrap: wrap;
}
.pp-legend-item { display: flex; align-items: center; gap: 5px; font-size: 10.5px; font-weight: 600; color: var(--text-muted); }
.pp-legend-dot { width: 10px; height: 10px; border-radius: 3px; }

/* ── Weight Bar ──────────────────────────────── */
.pp-weight-card { border-radius: 12px; border: 1px solid rgba(0,0,0,.07); padding: 14px 16px; }
.pp-weight-card-title { font-size: 12px; font-weight: 800; margin-bottom: 12px; }
.pp-wbar-row { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
.pp-wbar-label { font-size: 10.5px; font-weight: 600; width: 130px; min-width: 130px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pp-wbar-track { flex: 1; height: 8px; border-radius: 4px; background: rgba(0,0,0,.06); overflow: hidden; }
.pp-wbar-fill { height: 100%; border-radius: 4px; }
.pp-wbar-pct { font-size: 10px; font-weight: 700; min-width: 30px; text-align: right; }

/* ── Total weight track bar ──────────────────── */
.pp-total-bar-wrap { margin-top: 12px; padding-top: 10px; border-top: 1px solid rgba(0,0,0,.06); }
.pp-total-track { height: 10px; border-radius: 6px; background: rgba(0,0,0,.06); overflow: hidden; }
.pp-total-fill { height: 100%; border-radius: 6px; transition: width .4s ease; }
.pp-total-fill.ok   { background: linear-gradient(90deg,#059669,#10b981); }
.pp-total-fill.over { background: linear-gradient(90deg,#dc2626,#ef4444); }

@media (max-width:1100px) {
    .pp-main-grid { grid-template-columns: 1fr; }
    .pp-kpi-strip { grid-template-columns: repeat(3,1fr); }
}
@media (max-width:600px) {
    .pp-kpi-strip { grid-template-columns: 1fr 1fr; }
}
</style>

<div class="row"><div class="col-12">

<!-- ══ KPI STRIP ══════════════════════════════════════════════════════ -->
<div class="pp-kpi-strip">
    <div class="pp-kpi-card">
        <div class="pp-kpi-icon" style="background:rgba(var(--clr-theme-primary-rgb),.12);color:var(--clr-theme-primary)"><i class="fal fa-layer-group"></i></div>
        <div><div class="pp-kpi-label">Total Phases</div><div class="pp-kpi-value" style="color:var(--clr-theme-primary)"><?= count($phases) ?></div></div>
    </div>
    <div class="pp-kpi-card">
        <div class="pp-kpi-icon" style="background:rgba(245,158,11,.12);color:#f59e0b"><i class="fal fa-calendar-alt"></i></div>
        <div><div class="pp-kpi-label">Total Duration</div><div class="pp-kpi-value"><?= $total_months ?> Months</div></div>
    </div>
    <div class="pp-kpi-card">
        <div class="pp-kpi-icon" style="background:rgba(16,185,129,.12);color:#10b981"><i class="fal fa-tasks"></i></div>
        <div><div class="pp-kpi-label">Planning Status</div><div class="pp-kpi-value" style="font-size:12px">Draft / In Review</div></div>
    </div>
    <div class="pp-kpi-card">
        <div class="pp-kpi-icon" style="background:<?= $weightOk ? 'rgba(16,185,129,.12)' : 'rgba(239,68,68,.12)' ?>;color:<?= $weightOk ? '#10b981' : '#ef4444' ?>"><i class="fal fa-balance-scale"></i></div>
        <div>
            <div class="pp-kpi-label">Weight Model</div>
            <div class="pp-kpi-value" id="kpiWeight" style="color:<?= $weightOk ? '#10b981' : '#ef4444' ?>"><?= number_format($totalWeight, 0) ?>%</div>
        </div>
    </div>
    <div class="pp-kpi-card">
        <div class="pp-kpi-icon" style="background:rgba(239,68,68,.1);color:#ef4444"><i class="fal fa-exclamation-triangle"></i></div>
        <div>
            <div class="pp-kpi-label">Critical Phases</div>
            <div class="pp-kpi-value" style="color:#ef4444">
                <?= count(array_filter($phases, fn($p) => in_array($p['status'], ['At Risk','Delayed']))) ?>
            </div>
        </div>
    </div>
</div>

<!-- ══ MAIN GRID ═══════════════════════════════════════════════════════ -->
<div class="pp-main-grid">

<!-- ── LEFT: Phase Configuration ─────────────────────────────────────── -->
<div>
    <div class="pp-config-header">
        <h6><i class="fal fa-sitemap me-2" style="color:var(--pp-primary)"></i>Phase Configuration</h6>
        <button class="btn-add-phase" onclick="openAddModal()">
            <i class="fal fa-plus"></i> Add Phase
        </button>
    </div>

    <form method="POST" action="<?= $base ?>/con-phase-plan-update/<?= $project_id ?>" id="phasePlanForm">
    <div class="pp-config-wrap">
    <table class="pp-config-table">
        <thead>
            <tr>
                <th style="width:28px">#</th>
                <th style="text-align:left;min-width:140px">Phase Name</th>
                <th>Start M</th>
                <th>End M</th>
                <th>Duration</th>
                <th>Weight %</th>
                <th>Status</th>
                <th>Drawing</th>
                <th style="width:30px"></th>
            </tr>
        </thead>
        <tbody id="phaseTableBody">
        <?php foreach ($phases as $idx => $ph):
            $sm    = $statusMeta[$ph['status']] ?? $statusMeta['Not Started'];
            $color = $phaseColors[$idx % count($phaseColors)];
        ?>
            <tr data-id="<?= $ph['id'] ?>" data-idx="<?= $idx ?>">
                <td>
                    <input type="hidden" name="phases[<?= $idx ?>][id]"          value="<?= $ph['id'] ?>">
                    <input type="hidden" name="phases[<?= $idx ?>][phase_order]"  value="<?= $ph['phase_order'] ?>">
                    <div style="width:22px;height:22px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;background:<?= (strpos($color, 'var(') === 0 ? ($color === 'var(--clr-theme-primary)' ? 'rgba(var(--clr-theme-primary-rgb),.15)' : 'rgba(var(--clr-theme-secondary-rgb),.15)') : $color.'22') ?>;color:<?= $color ?>">
                        <?= $ph['phase_order'] ?>
                    </div>
                </td>
                <td>
                    <input type="text"
                           name="phases[<?= $idx ?>][phase_name]"
                           class="pp-phase-name-inp"
                           value="<?= htmlspecialchars($ph['phase_name']) ?>"
                           required>
                </td>
                <td style="text-align:center">
                    <input type="number"
                           name="phases[<?= $idx ?>][start_month]"
                           class="pp-month-inp start-month"
                           data-row="<?= $idx ?>"
                           value="<?= $ph['start_month'] ?>"
                           min="1" max="<?= $total_months ?>"
                           oninput="updateDuration(<?= $idx ?>)">
                </td>
                <td style="text-align:center">
                    <input type="number"
                           name="phases[<?= $idx ?>][end_month]"
                           class="pp-month-inp end-month"
                           data-row="<?= $idx ?>"
                           value="<?= $ph['end_month'] ?>"
                           min="1" max="<?= $total_months ?>"
                           oninput="updateDuration(<?= $idx ?>)">
                </td>
                <td style="text-align:center">
                    <span class="pp-dur-badge" id="dur_<?= $idx ?>">
                        <?= $ph['duration_months'] ?> mo
                    </span>
                </td>
                <td style="text-align:center">
                    <input type="number"
                           name="phases[<?= $idx ?>][weight_percentage]"
                           class="pp-weight-inp weight-field"
                           value="<?= number_format((float)$ph['weight_percentage'],2,'.','')?>"
                           min="0" max="100" step="0.01"
                           oninput="recalcWeight()">
                </td>
                <td>
                    <select name="phases[<?= $idx ?>][status]" class="pp-status-sel"
                            style="color:<?= $sm['color'] ?>">
                        <?php foreach ($statusMeta as $sv => $svm): ?>
                        <option value="<?= $sv ?>" <?= ($ph['status']===$sv)?'selected':'' ?>
                                style="color:<?= $svm['color'] ?>"><?= $sv ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <select name="phases[<?= $idx ?>][drawing_id]" class="pp-status-sel" style="max-width:150px; font-size:11px;">
                        <option value="">None</option>
                        <?php foreach ($drawings as $dr): ?>
                            <option value="<?= $dr['id'] ?>" <?= ($dr['phase_id'] == $ph['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dr['design_name'] . ' (v' . $dr['version'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:5px">
                        <?php if ((float)$ph['weight_percentage'] > 0): ?>
                        <a href="<?= $base ?>/con-phase-items/<?= $ph['id'] ?>"
                           class="btn btn-sm"
                           title="Manage Sub-items"
                           style="font-size:10.5px;font-weight:700;padding:3px 8px;
                                  background:rgba(var(--clr-theme-primary-rgb),.1);color:var(--pp-primary);
                                  border:1px solid rgba(var(--clr-theme-primary-rgb),.2);border-radius:6px;white-space:nowrap">
                            <i class="fal fa-list-ul me-1"></i>Items
                        </a>
                        <?php endif; ?>
                        <a href="<?= $base ?>/con-phase-delete/<?= $ph['id'] ?>"
                           class="pp-del-btn"
                           onclick="return confirm('Delete phase \'<?= htmlspecialchars($ph['phase_name']) ?>\'?')"
                           title="Delete">
                            <i class="fal fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>

        <?php endforeach; ?>
        </tbody>
    </table>
    </div><!-- config-wrap -->

    <!-- Action Bar -->
    <div class="pp-action-bar">
        <div class="d-flex align-items-center gap-2">
            <span style="font-size:11px;color:var(--text-muted)">Total phase weight must equal 100%.</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span id="weightIndicator" class="pp-weight-indicator"
                  style="background:<?= $weightOk?'rgba(16,185,129,.12)':'rgba(239,68,68,.12)' ?>;
                         color:<?= $weightOk?'var(--pp-green)':'var(--pp-red)' ?>">
                Total Weight: <strong id="weightTotal"><?= number_format($totalWeight,0) ?></strong>%
            </span>
            <button type="submit" class="btn-pp-save" id="saveBtn" <?= $weightOk?'':'disabled' ?>>
                <i class="fal fa-check-circle me-1"></i>Save Plan
            </button>
        </div>
    </div>
    </form>
</div>

<!-- ── RIGHT: Gantt + Weight Chart ──────────────────────────────────── -->
<div class="pp-right-col">

    <!-- Phase Timeline Preview (Gantt) -->
    <div class="pp-gantt-wrap">
        <div class="pp-gantt-header"><i class="fal fa-chart-gantt me-2" style="color:var(--pp-primary)"></i>Phase Timeline Preview</div>

        <!-- Ruler -->
        <?php
        $rulerStep = max(1, (int)round($total_months / 8));
        ?>
        <div class="pp-gantt-ruler">
            <?php for ($m = 1; $m <= $total_months; $m += $rulerStep): ?>
            <div style="flex:<?= $rulerStep ?>;text-align:center"><?= $m ?></div>
            <?php endfor; ?>
            <div style="flex:1;text-align:right"><?= $total_months ?></div>
        </div>

        <!-- Bars -->
        <?php foreach ($phases as $idx => $ph):
            $sm    = $statusMeta[$ph['status']] ?? $statusMeta['Not Started'];
            $color = $phaseColors[$idx % count($phaseColors)];
            $leftPct  = ($ph['start_month'] - 1) / max($total_months, 1) * 100;
            $widthPct = max(1, ($ph['duration_months']) / max($total_months, 1) * 100);
        ?>
        <div class="pp-gantt-row">
            <div class="pp-gantt-label" title="<?= htmlspecialchars($ph['phase_name']) ?>">
                <?= htmlspecialchars($ph['phase_name']) ?>
            </div>
            <div class="pp-gantt-track">
                <div class="pp-gantt-bar"
                     style="left:<?= round($leftPct,2) ?>%;width:<?= round($widthPct,2) ?>%;background:<?= $color ?>;"
                     title="<?= htmlspecialchars($ph['phase_name']) ?> | M<?= $ph['start_month'] ?>–M<?= $ph['end_month'] ?>">
                    <?php if ($widthPct > 6): ?>M<?= $ph['start_month'] ?>–M<?= $ph['end_month'] ?><?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Legend -->
        <div class="pp-legend">
            <div class="pp-legend-item"><div class="pp-legend-dot" style="background:#10b981"></div>Completed</div>
            <div class="pp-legend-item"><div class="pp-legend-dot" style="background:#f59e0b"></div>In Progress</div>
            <div class="pp-legend-item"><div class="pp-legend-dot" style="background:#94a3b8"></div>Upcoming</div>
            <div class="pp-legend-item"><div class="pp-legend-dot" style="background:#ef4444"></div>At Risk</div>
        </div>
    </div>

    <!-- Phase Weight Distribution -->
    <div class="pp-weight-card">
        <div class="pp-weight-card-title"><i class="fal fa-balance-scale me-2" style="color:var(--pp-primary)"></i>Phase Weight Distribution</div>
        <?php foreach ($phases as $idx => $ph):
            $color = $phaseColors[$idx % count($phaseColors)];
            $pct   = (float)$ph['weight_percentage'];
        ?>
        <div class="pp-wbar-row">
            <div class="pp-wbar-label" style="color:<?= $color ?>;font-weight:700;font-size:10.5px">
                <i class="fal fa-circle me-1" style="font-size:8px"></i><?= htmlspecialchars($ph['phase_name']) ?>
            </div>
            <div class="pp-wbar-track">
                <div class="pp-wbar-fill" style="width:<?= $pct ?>%;background:<?= $color ?>"></div>
            </div>
            <div class="pp-wbar-pct" style="color:<?= $color ?>"><?= number_format($pct,0) ?>%</div>
        </div>
        <?php endforeach; ?>

        <!-- Total Bar -->
        <div class="pp-total-bar-wrap">
            <div class="d-flex justify-content-between mb-1">
                <span style="font-size:10px;font-weight:700;color:var(--text-muted)">TOTAL</span>
                <span id="wTotalPct" style="font-size:10px;font-weight:800;color:<?= $weightOk?'var(--pp-green)':'var(--pp-red)' ?>">
                    <?= number_format($totalWeight,0) ?>%
                </span>
            </div>
            <div class="pp-total-track">
                <div class="pp-total-fill <?= $totalWeight>100?'over':($weightOk?'ok':'') ?>"
                     id="totalFill" style="width:<?= min($totalWeight,100) ?>%"></div>
            </div>
        </div>
    </div>

</div><!-- right col -->
</div><!-- main grid -->

</div></div>

<!-- ══ ADD PHASE MODAL ════════════════════════════════════════════════ -->
<div class="modal fade" id="addPhaseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fal fa-plus-circle me-2" style="color:var(--pp-primary)"></i>Add New Phase</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addPhaseForm">
                    <input type="hidden" name="project_id" value="<?= $project_id ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:13px">Phase Name <span class="text-danger">*</span></label>
                        <input type="text" name="phase_name" id="newPhaseName" class="form-control form-control-sm" placeholder="e.g. Roof Slab" required>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label fw-semibold" style="font-size:13px">Start Month</label>
                            <input type="number" name="planned_start_date" id="newPhaseStart" class="form-control form-control-sm" min="1" max="<?= $total_months ?>" placeholder="e.g. 1">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold" style="font-size:13px">End Month</label>
                            <input type="number" name="planned_end_date" id="newPhaseEnd" class="form-control form-control-sm" min="1" max="<?= $total_months ?>" placeholder="e.g. 4">
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label fw-semibold" style="font-size:13px">Project Drawing</label>
                        <select name="drawing_id" id="newPhaseDrawing" class="form-select form-select-sm">
                            <option value="">None / Select Drawing</option>
                            <?php foreach ($drawings as $dr): ?>
                                <option value="<?= $dr['id'] ?>">
                                    <?= htmlspecialchars($dr['design_name'] . ' (v' . $dr['version'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="addPhaseSubmitBtn" onclick="submitAddPhase()">
                    <i class="fal fa-plus me-1"></i>Add Phase
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ── Constants ─────────────────────────────────────────────────────────
const PROJ_START   = new Date('<?= $proj_start_date ?>');
const TOTAL_MONTHS = <?= $total_months ?>;

// ── Duration updater ──────────────────────────────────────────────────
function updateDuration(rowIdx) {
    const s = parseInt(document.querySelector(`.start-month[data-row="${rowIdx}"]`)?.value) || 1;
    const e = parseInt(document.querySelector(`.end-month[data-row="${rowIdx}"]`)?.value) || 1;
    const dur = Math.max(0, e - s);
    const badge = document.getElementById('dur_' + rowIdx);
    if (badge) badge.textContent = dur + ' mo';
}

// ── Weight Recalculation ───────────────────────────────────────────────
function recalcWeight() {
    const fields = document.querySelectorAll('.weight-field');
    let total = 0;
    fields.forEach(f => total += parseFloat(f.value) || 0);
    total = Math.round(total * 100) / 100;

    const ok     = Math.abs(total - 100) <= 0.01;
    const okBg   = 'rgba(16,185,129,.12)', errBg = 'rgba(239,68,68,.12)';
    const okClr  = 'var(--pp-green)',      errClr = 'var(--pp-red)';

    // KPI card
    const kpi = document.getElementById('kpiWeight');
    if (kpi) { kpi.textContent = Math.round(total) + '%'; kpi.style.color = ok ? okClr : errClr; }

    // Weight indicator
    const wi = document.getElementById('weightIndicator');
    if (wi) {
        wi.style.background = ok ? okBg : errBg;
        wi.style.color      = ok ? okClr : errClr;
        document.getElementById('weightTotal').textContent = Math.round(total);
    }

    // Total bar
    const fill = document.getElementById('totalFill');
    if (fill) {
        fill.style.width    = Math.min(total, 100) + '%';
        fill.className      = 'pp-total-fill ' + (ok ? 'ok' : (total > 100 ? 'over' : ''));
    }
    const wTot = document.getElementById('wTotalPct');
    if (wTot) { wTot.textContent = Math.round(total) + '%'; wTot.style.color = ok ? okClr : errClr; }

    document.getElementById('saveBtn').disabled = !ok;
}

// ── Save form validation ───────────────────────────────────────────────
document.getElementById('phasePlanForm').addEventListener('submit', function(e) {
    const fields = document.querySelectorAll('.weight-field');
    let total = 0;
    fields.forEach(f => total += parseFloat(f.value) || 0);
    if (Math.abs(total - 100) > 0.01) {
        e.preventDefault();
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'Invalid Weight', text: 'Total weight must equal 100%. Current: ' + total.toFixed(2) + '%', confirmButtonColor: 'var(--clr-theme-primary)' });
        } else {
            alert('Total weight must equal 100%. Current: ' + total.toFixed(2) + '%');
        }
    }
});

// ── Bootstrap Modal ────────────────────────────────────────────────────
let addPhaseModalBS;
document.addEventListener('DOMContentLoaded', function() {
    const el = document.getElementById('addPhaseModal');
    if (el && typeof bootstrap !== 'undefined') {
        addPhaseModalBS = new bootstrap.Modal(el);
    }
});
function openAddModal() {
    document.getElementById('addPhaseForm').reset();
    if (addPhaseModalBS) { addPhaseModalBS.show(); setTimeout(() => document.getElementById('newPhaseName').focus(), 300); }
}
function closeAddModal() { if (addPhaseModalBS) addPhaseModalBS.hide(); }

function submitAddPhase() {
    const btn    = document.getElementById('addPhaseSubmitBtn');
    const nameEl = document.getElementById('newPhaseName');
    const form   = document.getElementById('addPhaseForm');
    if (!nameEl.value.trim()) { nameEl.focus(); return; }

    // Convert month numbers to approximate dates for the AJAX endpoint
    const startM = parseInt(document.getElementById('newPhaseStart').value) || 1;
    const endM   = parseInt(document.getElementById('newPhaseEnd').value)   || 2;

    function addMonths(date, m) {
        const d = new Date(date);
        d.setMonth(d.getMonth() + m);
        return d.toISOString().split('T')[0];
    }

    const data = new FormData();
    data.append('project_id',         '<?= $project_id ?>');
    data.append('phase_name',          nameEl.value.trim());
    data.append('planned_start_date',  addMonths(PROJ_START, startM - 1));
    data.append('planned_end_date',    addMonths(PROJ_START, endM - 1));
    data.append('drawing_id',          document.getElementById('newPhaseDrawing').value);

    btn.disabled = true;
    btn.innerHTML = '<i class="fal fa-spinner fa-spin me-1"></i>Adding…';

    fetch('<?= $base ?>/con-phase-add', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                closeAddModal();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'success', title: 'Phase Added!', timer: 1200, showConfirmButton: false });
                }
                setTimeout(() => location.reload(), 1300);
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Failed.', confirmButtonColor: 'var(--clr-theme-primary)' });
                }
            }
        })
        .catch(() => alert('Network error. Please try again.'))
        .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="fal fa-plus me-1"></i>Add Phase'; });
}
</script>

<?php
$base      = $base ?? $GLOBALS['base_url'];
$kpi       = $kpi ?? [];
$riskItems = $riskItems ?? [];
$byPhase   = $byPhase ?? [];
$project   = $project ?? [];

$riskColors = [
    'Critical'   => ['bg'=>'#fee2e2','color'=>'#991b1b','dot'=>'#ef4444'],
    'High'       => ['bg'=>'#ffedd5','color'=>'#9a3412','dot'=>'#f97316'],
    'Medium'     => ['bg'=>'#fef3c7','color'=>'#92400e','dot'=>'#f59e0b'],
    'Low'        => ['bg'=>'#e0f2fe','color'=>'#075985','dot'=>'#0ea5e9'],
    'On Track'   => ['bg'=>'#d1fae5','color'=>'#065f46','dot'=>'#10b981'],
    'Not Started'=> ['bg'=>'#f1f5f9','color'=>'#64748b','dot'=>'#94a3b8'],
    'Completed'  => ['bg'=>'#d1fae5','color'=>'#065f46','dot'=>'#10b981'],
];
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
body{font-family:'Inter',sans-serif;}
:root{--pri:#4f46e5;--green:#10b981;--amber:#f59e0b;--red:#ef4444;--slate:#64748b;}

.rd-page{padding:18px 20px;}
.rd-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px}
.rd-title{font-size:20px;font-weight:800;color:#1e293b}
.rd-subtitle{font-size:12px;color:var(--slate);margin-top:2px}

.rd-kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:12px;margin-bottom:22px}
.rd-kpi{border-radius:14px;padding:14px 18px;border:1px solid rgba(0,0,0,.07)}
.rd-kpi-val{font-size:26px;font-weight:900;line-height:1}
.rd-kpi-lbl{font-size:9.5px;font-weight:700;text-transform:uppercase;color:var(--slate);margin-top:4px}

.rd-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;margin-bottom:18px}
.rd-card-header{padding:12px 18px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between}
.rd-card-title{font-size:13px;font-weight:800;color:#1e293b;display:flex;align-items:center;gap:8px}

.rd-table{width:100%;border-collapse:collapse;font-size:11.5px}
.rd-table th{font-size:9px;font-weight:700;text-transform:uppercase;color:var(--slate);padding:7px 10px;border-bottom:2px solid #e2e8f0;background:#f8fafc;white-space:nowrap}
.rd-table td{padding:8px 10px;border-bottom:1px solid #f8fafc;vertical-align:middle}
.rd-table tr:last-child td{border-bottom:none}
.rd-table tr:hover td{background:#fafafa}

.risk-badge{display:inline-flex;align-items:center;gap:5px;font-size:10px;font-weight:700;padding:3px 10px;border-radius:999px;white-space:nowrap}
.risk-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}

.phase-bar-wrap{margin-bottom:14px;border:1px solid #e2e8f0;border-radius:12px;padding:12px 16px}
.phase-bar-label{display:flex;justify-content:space-between;font-size:11px;font-weight:700;margin-bottom:6px}
.phase-bar-track{height:10px;border-radius:5px;background:#f1f5f9;overflow:hidden;position:relative}
.phase-bar-plan{position:absolute;left:0;top:0;height:100%;border-radius:5px;background:#c7d2fe}
.phase-bar-actual{position:absolute;left:0;top:0;height:100%;border-radius:5px}

.pct-bar{height:4px;border-radius:2px;background:#f1f5f9;overflow:hidden;margin-top:3px;min-width:50px}
</style>

<div class="rd-page">

<!-- Header -->
<div class="rd-header">
    <div>
        <div class="rd-title"><i class="bi bi-shield-exclamation me-2" style="color:#ef4444"></i>Risk Dashboard</div>
        <div class="rd-subtitle"><?= htmlspecialchars($project['name'] ?? '') ?> — Work item risk classification</div>
    </div>
    <div style="display:flex;gap:8px">
        <a href="<?= $base ?>/con-recovery-plan/<?= $projectId ?>" class="btn btn-sm btn-outline-warning" style="font-size:12px;font-weight:700">
            <i class="bi bi-arrow-clockwise me-1"></i>Recovery Plan
        </a>
    </div>
</div>

<!-- KPI Cards -->
<div class="rd-kpi-grid">
    <div class="rd-kpi" style="background:linear-gradient(135deg,#f8fafc,#f1f5f9)">
        <div class="rd-kpi-val" style="color:#1e293b"><?= $kpi['total'] ?></div>
        <div class="rd-kpi-lbl">Total Items</div>
    </div>
    <div class="rd-kpi" style="background:linear-gradient(135deg,#fee2e2,#fecaca)">
        <div class="rd-kpi-val" style="color:#991b1b"><?= $kpi['critical'] ?></div>
        <div class="rd-kpi-lbl">Critical (0% done)</div>
    </div>
    <div class="rd-kpi" style="background:linear-gradient(135deg,#fef3c7,#fde68a)">
        <div class="rd-kpi-val" style="color:#92400e"><?= $kpi['behind'] ?></div>
        <div class="rd-kpi-lbl">Behind Schedule</div>
    </div>
    <div class="rd-kpi" style="background:linear-gradient(135deg,#d1fae5,#a7f3d0)">
        <div class="rd-kpi-val" style="color:#065f46"><?= $kpi['on_track'] ?></div>
        <div class="rd-kpi-lbl">On Track</div>
    </div>
    <div class="rd-kpi" style="background:linear-gradient(135deg,#dbeafe,#bfdbfe)">
        <div class="rd-kpi-val" style="color:#1e40af"><?= $kpi['completed'] ?></div>
        <div class="rd-kpi-lbl">Completed</div>
    </div>
    <div class="rd-kpi" style="background:linear-gradient(135deg,#f8fafc,#f1f5f9)">
        <div class="rd-kpi-val" style="color:#64748b"><?= $kpi['not_started'] ?></div>
        <div class="rd-kpi-lbl">Not Started</div>
    </div>
</div>

<!-- Phase-level risk bars -->
<div class="rd-card" style="margin-bottom:18px">
    <div class="rd-card-header">
        <div class="rd-card-title"><i class="bi bi-layers-half" style="color:var(--pri)"></i>Phase Progress Overview</div>
    </div>
    <div style="padding:14px 18px">
        <?php foreach ($byPhase as $phaseName => $ph):
            $planW  = min(100, $ph['avg_planned']);
            $actW   = min(100, $ph['avg_actual']);
            $barC   = $actW >= $planW ? '#10b981' : ($actW > 0 ? '#f59e0b' : '#e2e8f0');
        ?>
        <div class="phase-bar-wrap">
            <div class="phase-bar-label">
                <span style="font-weight:700;color:#1e293b"><?= htmlspecialchars($phaseName) ?></span>
                <span>
                    <span style="color:#6d28d9">Plan: <?= number_format($ph['avg_planned'],1) ?>%</span>
                    &nbsp;|&nbsp;
                    <span style="color:<?= $barC ?>">Actual: <?= number_format($ph['avg_actual'],1) ?>%</span>
                    &nbsp;·&nbsp;
                    <span style="color:var(--slate)">Weight: <?= number_format($ph['weight'],1) ?>%</span>
                </span>
            </div>
            <div class="phase-bar-track">
                <div class="phase-bar-plan" style="width:<?= $planW ?>%"></div>
                <div class="phase-bar-actual" style="width:<?= $actW ?>%;background:<?= $barC ?>"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Risk Item Table -->
<div class="rd-card">
    <div class="rd-card-header">
        <div class="rd-card-title"><i class="bi bi-table text-danger"></i>Work Item Risk Table</div>
        <span style="font-size:11px;color:var(--slate)"><?= count($riskItems) ?> items</span>
    </div>
    <?php if (empty($riskItems)): ?>
    <div style="text-align:center;padding:32px;color:var(--slate)">
        <i class="bi bi-check-circle-fill text-success" style="font-size:32px;display:block;margin-bottom:8px"></i>
        All items are on track!
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table class="rd-table">
        <thead>
            <tr>
                <th>Risk</th>
                <th>Work Item</th>
                <th>Phase</th>
                <th>Month</th>
                <th style="text-align:center">Planned %</th>
                <th style="text-align:center">Actual %</th>
                <th style="text-align:center">Deviation</th>
                <th>Team</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($riskItems as $item):
            $rc     = $riskColors[$item['risk_level']] ?? $riskColors['Low'];
            $dev    = (float)$item['deviation'];
            $devClr = $dev >= 0 ? '#065f46' : '#dc2626';
            $month  = !empty($item['target_month']) ? (new DateTime($item['target_month']))->format('M Y') : '—';
        ?>
        <tr>
            <td>
                <span class="risk-badge" style="background:<?= $rc['bg'] ?>;color:<?= $rc['color'] ?>">
                    <span class="risk-dot" style="background:<?= $rc['dot'] ?>"></span>
                    <?= $item['risk_level'] ?>
                </span>
            </td>
            <td>
                <div style="font-weight:600;color:#1e293b;white-space:nowrap"><?= htmlspecialchars($item['work_item_name'] ?? '—') ?></div>
                <?php if (!empty($item['work_type_name'])): ?>
                <div style="font-size:9.5px;color:var(--slate)"><?= htmlspecialchars($item['work_type_name']) ?></div>
                <?php endif; ?>
            </td>
            <td style="color:var(--slate);font-size:11px;white-space:nowrap"><?= htmlspecialchars($item['phase_name']) ?></td>
            <td style="color:var(--slate);font-size:11px;white-space:nowrap"><?= $month ?></td>
            <td style="text-align:center">
                <div style="font-weight:700;color:#6d28d9"><?= number_format((float)$item['planned_progress_pct'],0) ?>%</div>
                <div class="pct-bar"><div style="width:<?= min(100,(float)$item['planned_progress_pct']) ?>%;height:100%;background:#c7d2fe;border-radius:2px"></div></div>
            </td>
            <td style="text-align:center">
                <div style="font-weight:800;color:<?= $rc['dot'] ?>"><?= number_format((float)$item['actual_progress_pct'],1) ?>%</div>
                <div class="pct-bar"><div style="width:<?= min(100,(float)$item['actual_progress_pct']) ?>%;height:100%;background:<?= $rc['dot'] ?>;border-radius:2px"></div></div>
            </td>
            <td style="text-align:center;font-weight:800;color:<?= $devClr ?>">
                <?= $dev >= 0 ? '+' : '' ?><?= number_format($dev,1) ?>%
            </td>
            <td style="color:var(--slate);font-size:11px"><?= htmlspecialchars($item['responsible_team'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

</div>

<?php
$base       = $GLOBALS['base_url'];
$project_id = $project['id']   ?? 0;
$byMonth    = $byMonth         ?? [];
$phases     = $phases          ?? [];
$shortfallsByTarget = $shortfallsByTarget ?? [];

$statusMeta = [
    'Draft'     => ['color' => '#94a3b8', 'bg' => 'rgba(148,163,184,.12)'],
    'In Review' => ['color' => '#f59e0b', 'bg' => 'rgba(245,158,11,.12)'],
    'Approved'  => ['color' => '#10b981', 'bg' => 'rgba(16,185,129,.12)'],
];
$phaseColors = ['var(--clr-theme-primary)','#10b981','#f59e0b','#ef4444','#14b8a6','var(--clr-theme-secondary)','#ec4899','#0ea5e9','#f97316','#84cc16'];
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
:root{ --mt-primary:var(--clr-theme-primary);--mt-green:#10b981;--mt-amber:#f59e0b;--mt-red:#ef4444; }

.mt-header{ display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:10px }
.mt-header h4{ font-size:19px;font-weight:800;margin:0 0 3px }
.mt-breadcrumb{ font-size:11.5px;color:var(--text-muted) }
.mt-breadcrumb a{ color:var(--mt-primary);text-decoration:none }

/* Create target card */
.mt-create-card{ border-radius:14px;border:2px dashed rgba(var(--clr-theme-primary-rgb),.3);padding:22px 24px;margin-bottom:24px;background:rgba(var(--clr-theme-primary-rgb),.03) }
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

/* Shortfall / Recovery styles */
.toggle-recovery-btn {
    font-size: 11px;
    font-weight: 700;
    padding: 5px 12px;
    border-radius: 7px;
    background: #ef4444;
    color: #fff;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.2s;
    box-shadow: 0 2px 6px rgba(239, 68, 68, 0.2);
}
.toggle-recovery-btn:hover {
    background: #dc2626;
    box-shadow: 0 4px 10px rgba(239, 68, 68, 0.35);
}
.toggle-pending-btn {
    font-size: 11px;
    font-weight: 700;
    padding: 5px 12px;
    border-radius: 7px;
    background: #3b82f6;
    color: #fff;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.2s;
    box-shadow: 0 2px 6px rgba(59, 130, 246, 0.2);
}
.toggle-pending-btn:hover {
    background: #2563eb;
    box-shadow: 0 4px 10px rgba(59, 130, 246, 0.35);
}
.recovery-details-panel {
    background: #ffffff;
    border-top: 1px solid rgba(0, 0, 0, 0.06);
    padding: 18px;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.01);
}
.recovery-title {
    font-size: 12px;
    font-weight: 800;
    color: #4b5563;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.recovery-tbl {
    width: 100%;
    border-collapse: collapse;
    font-size: 11.5px;
    margin-bottom: 12px;
}
.recovery-tbl th {
    background: transparent;
    color: #4b5563;
    font-weight: 700;
    padding: 6px 8px;
    border-bottom: 2px solid #e5e7eb;
    text-align: left;
}
.recovery-tbl td {
    padding: 8px;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
    color: #374151;
}
.recovery-tbl tr:last-child td {
    border-bottom: none;
}
.recovery-progress-bar {
    width: 60px;
    height: 6px;
    background: #e5e7eb;
    border-radius: 4px;
    display: inline-block;
    overflow: hidden;
    vertical-align: middle;
    margin-right: 6px;
}
.recovery-progress-fill {
    height: 100%;
    background: #10b981;
    border-radius: 4px;
}
.badge-rec-status {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 9.5px;
    font-weight: 700;
}
.recovery-tip-box {
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 11.5px;
    margin-top: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
    line-height: 1.4;
    text-align: left;
}
.recovery-tip-box.danger-tip {
    background: rgba(239, 68, 68, 0.05);
    border: 1px dashed rgba(239, 68, 68, 0.3);
    color: #b91c1c;
}
.recovery-tip-box.info-tip {
    background: rgba(59, 130, 246, 0.05);
    border: 1px dashed rgba(59, 130, 246, 0.3);
    color: #1d4ed8;
}
.recovery-tip-box i {
    font-size: 14px;
    flex-shrink: 0;
}
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
                <div class="mt-stat-label">Monthly Actual Progress</div>
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
            <div class="d-flex align-items-center gap-2">
                <a href="<?= $base ?>/con-monthly-target/<?= $t['id'] ?>" class="btn-mt-view">
                    <i class="fal fa-edit"></i>Manage Items
                </a>
                <?php if (!empty($shortfallsByTarget[$t['id']])): 
                    $shortfalls = $shortfallsByTarget[$t['id']];
                    $totalOverdueShortfall = 0;
                    $totalPendingShortfall = 0;
                    $overdueCount = 0;
                    $pendingCount = 0;
                    $today = date('Y-m-d');
                    
                    foreach ($shortfalls as $sf) {
                        $itemEndDate = $sf['end_date'] ?? null;
                        if (empty($itemEndDate) || $itemEndDate === '0000-00-00') {
                            $targetMonth = $sf['target_month'] ?? '';
                            if (strlen($targetMonth) === 7) {
                                $deadlineDate = date('Y-m-t', strtotime($targetMonth . '-01'));
                            } else {
                                $deadlineDate = date('Y-m-t', strtotime($targetMonth));
                            }
                        } else {
                            $deadlineDate = $itemEndDate;
                        }
                        
                        if ($deadlineDate < $today) {
                            $totalOverdueShortfall += $sf['shortfall_qty'];
                            $overdueCount++;
                        } else {
                            $totalPendingShortfall += $sf['shortfall_qty'];
                            $pendingCount++;
                        }
                    }
                ?>
                <?php if ($overdueCount > 0): ?>
                <button class="toggle-recovery-btn" type="button" onclick="toggleRecovery(<?= $t['id'] ?>)">
                    <i class="fal fa-exclamation-triangle"></i> Recovery Qty: <?= number_format($totalOverdueShortfall, 1) ?> (<?= $overdueCount ?> Overdue)
                </button>
                <?php endif; ?>
                
                <?php if ($pendingCount > 0): ?>
                <button class="toggle-pending-btn" type="button" onclick="toggleRecovery(<?= $t['id'] ?>)">
                    <i class="fal fa-clock"></i> Pending Qty: <?= number_format($totalPendingShortfall, 1) ?> (<?= $pendingCount ?> On Track)
                </button>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            <a href="<?= $base ?>/con-monthly-target-delete/<?= $t['id'] ?>"
               class="btn-mt-del"
               onclick="return confirm('Delete this monthly target and all its items?')">
                <i class="fal fa-trash"></i>Delete
            </a>
        </div>
        
        <!-- Collapsible Shortfall Panel -->
        <?php if (!empty($shortfallsByTarget[$t['id']])): ?>
        <div id="recovery-details-<?= $t['id'] ?>" class="recovery-details-panel" style="display:none;">
            <div class="recovery-title">
                <i class="fal fa-exclamation-circle"></i> Shortfall Details
            </div>
            <div class="table-responsive">
                <table class="recovery-tbl">
                    <thead>
                        <tr>
                            <th style="width:35%;">Work Item</th>
                            <th style="width:15%;">Planned Target</th>
                            <th style="width:15%;">Achieved Qty</th>
                            <th style="width:15%; color:#ef4444;">Shortfall</th>
                            <th style="width:12%; text-align:center;">Deadline</th>
                            <th style="width:8%; text-align:center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $hasOverdue = false;
                        $hasPending = false;
                        foreach ($shortfalls as $sf): 
                            $targetQty   = (float)$sf['monthly_target_qty'];
                            $achievedQty = (float)$sf['actual_qty'];
                            $shortfallQty= (float)$sf['shortfall_qty'];
                            
                            $itemEndDate = $sf['end_date'] ?? null;
                            if (empty($itemEndDate) || $itemEndDate === '0000-00-00') {
                                $targetMonth = $sf['target_month'] ?? '';
                                if (strlen($targetMonth) === 7) {
                                    $deadlineDate = date('Y-m-t', strtotime($targetMonth . '-01'));
                                } else {
                                    $deadlineDate = date('Y-m-t', strtotime($targetMonth));
                                }
                            } else {
                                $deadlineDate = $itemEndDate;
                            }
                            
                            $isOverdue = ($deadlineDate < $today);
                            if ($isOverdue) {
                                $hasOverdue = true;
                                $statusBadge = '<span class="badge-rec-status text-danger" style="background:#fee2e2;">🔴 Overdue</span>';
                            } else {
                                $hasPending = true;
                                $statusBadge = '<span class="badge-rec-status text-warning" style="background:#fef3c7;">🟡 On Track</span>';
                            }
                            
                            $formattedDeadline = date('d M Y', strtotime($deadlineDate));
                        ?>
                        <tr>
                            <td style="font-weight:600;"><?= htmlspecialchars($sf['work_item_name']) ?></td>
                            <td><?= number_format($targetQty, 1) ?> <?= htmlspecialchars($sf['unit']) ?></td>
                            <td><?= number_format($achievedQty, 1) ?> <?= htmlspecialchars($sf['unit']) ?></td>
                            <td style="font-weight:700; color:#ef4444;">
                                <?= number_format($shortfallQty, 1) ?> <?= htmlspecialchars($sf['unit']) ?>
                            </td>
                            <td style="text-align:center;"><?= $formattedDeadline ?></td>
                            <td style="text-align:center;"><?= $statusBadge ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($hasOverdue): ?>
            <div class="recovery-tip-box danger-tip">
                <i class="fal fa-exclamation-triangle"></i>
                <span>
                    <strong>Shortfall Recovery Required:</strong> You have overdue shortfall items. Please ensure these quantities are carried forward and added when planning targets for the next month. You can write a note in the <strong>Recovery Notes</strong> column on the target sheet to keep track.
                </span>
            </div>
            <?php else: ?>
            <div class="recovery-tip-box info-tip">
                <i class="fal fa-info-circle"></i>
                <span>
                    <strong>On Track Notice:</strong> All shortfall items are currently on track (deadlines are in the future). Monitor progress to ensure these targets are completed before their deadlines.
                </span>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>

</div></div>

<script>
function toggleRecovery(targetId) {
    var panel = document.getElementById('recovery-details-' + targetId);
    if (!panel) return;
    
    if (panel.style.display === 'none' || panel.style.display === '') {
        panel.style.display = 'block';
        panel.style.opacity = 0;
        var op = 0;
        var timer = setInterval(function () {
            if (op >= 1){
                clearInterval(timer);
            }
            panel.style.opacity = op;
            op += 0.1;
        }, 15);
    } else {
        panel.style.display = 'none';
    }
}
</script>

<?php
$base      = $GLOBALS['base_url'];
$byProject = $byProject ?? [];

$statusColors = [
    'Draft'     => ['color' => '#94a3b8', 'bg' => 'rgba(148,163,184,.13)'],
    'In Review' => ['color' => '#f59e0b', 'bg' => 'rgba(245,158,11,.13)'],
    'Approved'  => ['color' => '#10b981', 'bg' => 'rgba(16,185,129,.13)'],
];
$phaseColors = ['#6366f1','#10b981','#f59e0b','#ef4444','#14b8a6','#8b5cf6','#ec4899','#0ea5e9','#f97316','#84cc16'];
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
:root {
    --pt-primary: #6366f1;
    --pt-green:   #10b981;
    --pt-amber:   #f59e0b;
    --pt-red:     #ef4444;
}

.pt-page-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 24px; flex-wrap: wrap; gap: 10px;
}
.pt-page-header h4 {
    font-size: 20px; font-weight: 800; margin: 0 0 3px;
    background: linear-gradient(135deg, var(--pt-primary), #818cf8);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}
.pt-breadcrumb { font-size: 11.5px; color: var(--text-muted); }
.pt-breadcrumb a { color: var(--pt-primary); text-decoration: none; }

/* Project Group */
.pt-project-group { margin-bottom: 28px; }
.pt-project-label {
    display: flex; align-items: center; gap: 10px;
    background: linear-gradient(90deg, rgba(99,102,241,.07), transparent);
    border-left: 4px solid var(--pt-primary);
    padding: 10px 16px; border-radius: 0 8px 8px 0;
    margin-bottom: 12px;
}
.pt-project-label h6 {
    font-size: 14px; font-weight: 800; margin: 0;
    color: var(--pt-primary);
}
.pt-project-label small { font-size: 11px; color: var(--text-muted); font-weight: 600; }

/* Target Card */
.pt-card {
    border: 1px solid rgba(0,0,0,.07); border-radius: 12px;
    overflow: hidden; margin-bottom: 10px;
    transition: box-shadow .2s, transform .15s;
    background: var(--custom-white);
}
.pt-card:hover { box-shadow: 0 4px 20px rgba(99,102,241,.1); transform: translateY(-1px); }

.pt-card-head {
    padding: 13px 18px;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 8px;
}
.pt-phase-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.pt-phase-name { font-size: 13px; font-weight: 700; }
.pt-badge {
    font-size: 9.5px; font-weight: 700; padding: 2px 9px;
    border-radius: 999px; white-space: nowrap;
}

.pt-card-body {
    padding: 0 18px 14px;
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 12px;
}
.pt-stat { text-align: center; }
.pt-stat-val { font-size: 15px; font-weight: 800; }
.pt-stat-label { font-size: 10px; font-weight: 600; color: var(--text-muted); margin-top: 1px; }
.pt-prog-bar { height: 4px; border-radius: 3px; background: rgba(0,0,0,.06); overflow: hidden; margin-top: 4px; }
.pt-prog-fill { height: 100%; border-radius: 3px; transition: width .4s; }

.pt-card-footer {
    padding: 10px 18px;
    border-top: 1px solid rgba(0,0,0,.05);
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 8px;
    background: rgba(0,0,0,.01);
}

/* BOQ Buttons */
.btn-boq-create {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 11.5px; font-weight: 700;
    padding: 6px 14px; border-radius: 8px;
    background: linear-gradient(135deg, var(--pt-primary), #818cf8);
    color: #fff; text-decoration: none;
    transition: opacity .2s, transform .1s;
    border: none; cursor: pointer;
}
.btn-boq-create:hover { opacity: .88; color: #fff; transform: translateY(-1px); }

.btn-boq-view {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 11.5px; font-weight: 700;
    padding: 6px 14px; border-radius: 8px;
    background: rgba(16,185,129,.1); color: var(--pt-green);
    text-decoration: none; border: 1px solid rgba(16,185,129,.3);
    transition: all .2s;
}
.btn-boq-view:hover { background: var(--pt-green); color: #fff; }

.btn-target-view {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 11px; font-weight: 600;
    padding: 5px 12px; border-radius: 7px;
    background: rgba(99,102,241,.08); color: var(--pt-primary);
    text-decoration: none; border: 1px solid rgba(99,102,241,.2);
    transition: all .2s;
}
.btn-target-view:hover { background: var(--pt-primary); color: #fff; }

/* Month pill */
.pt-month-pill {
    display: inline-block;
    font-size: 10px; font-weight: 700;
    padding: 2px 9px; border-radius: 999px;
    background: rgba(99,102,241,.1); color: var(--pt-primary);
}

/* Empty state */
.pt-empty {
    text-align: center; padding: 60px 20px;
    color: var(--text-muted);
}
.pt-empty i { font-size: 42px; margin-bottom: 14px; opacity: .2; display: block; }

@media (max-width: 576px) {
    .pt-card-body { grid-template-columns: repeat(2, 1fr); }
}
</style>

<div class="container-fluid">

    <!-- Page Header -->
    <div class="pt-page-header">
        <div>
            <h4><i class="fal fa-bullseye-arrow me-2"></i>Project Plan Targets</h4>
            <div class="pt-breadcrumb">
                <a href="<?= $base ?>/con-dashboard">Dashboard</a> ›
                <span>Project Plan Targets</span>
            </div>
        </div>
        <a href="<?= $base ?>/con-boq" class="btn btn-sm btn-outline-secondary">
            <i class="fal fa-list me-1"></i>All BOQs
        </a>
    </div>

    <?php if (empty($byProject)): ?>
    <!-- Empty State -->
    <div class="card custom-card">
        <div class="card-body">
            <div class="pt-empty text-light">
                <i class="fal fa-calendar-times"></i>
                <div style="font-size:15px; font-weight:700; margin-bottom:6px">No Targets Found</div>
                <div style="font-size:12.5px">আপনার assigned project-এ এখনো কোনো monthly target তৈরি হয়নি।</div>
                <div style="font-size:12px; margin-top:6px; color:var(--text-muted)">
                    PM বা admin-কে monthly target তৈরি করতে বলুন।
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>

    <?php foreach ($byProject as $pid => $group):
        $targets = $group['targets'];
    ?>
    <!-- Project Group -->
    <div class="pt-project-group">
        <div class="pt-project-label">
            <i class="fal fa-building" style="color:var(--pt-primary); font-size:16px;"></i>
            <div style="flex:1">
                <h6><?= htmlspecialchars($group['project_name']) ?></h6>
                <small><?= count($targets) ?> টি monthly target</small>
            </div>
            <!-- Project-level quick links: Stock & Material Issue -->
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= $base ?>/con-stocks?project_id=<?= $pid ?>"
                   style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:5px 12px;border-radius:7px;background:rgba(99,102,241,.08);color:var(--pt-primary);text-decoration:none;border:1px solid rgba(99,102,241,.2)">
                    <i class="fal fa-boxes"></i> Stock দেখুন
                </a>
                <a href="<?= $base ?>/con-material-issues?project_id=<?= $pid ?>"
                   style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:5px 12px;border-radius:7px;background:rgba(16,185,129,.08);color:var(--pt-green);text-decoration:none;border:1px solid rgba(16,185,129,.2)">
                    <i class="fal fa-dolly"></i> Material Issue
                </a>
            </div>
        </div>

        <?php foreach ($targets as $idx => $t):
            $sm      = $statusColors[$t['planning_status']] ?? $statusColors['Draft'];
            $color   = $phaseColors[$idx % count($phaseColors)];
            $actual  = (float)($t['avg_actual_pct'] ?? 0);
            $items   = (int)($t['item_count'] ?? 0);
            $hasBoq  = (int)($t['has_boq'] ?? 0) > 0;
            $boqId   = $t['boq_id'] ?? null;
            try {
                $dt = new DateTime($t['target_month']);
                $monthLabel = $dt->format('F Y');
            } catch (Exception $e) {
                $monthLabel = $t['target_month'] ?? '';
            }
        ?>
        <div class="pt-card">
            <!-- Card Head -->
            <div class="pt-card-head">
                <div style="display:flex; align-items:center; gap:9px;">
                    <div class="pt-phase-dot" style="background:<?= $color ?>"></div>
                    <div>
                        <div class="pt-phase-name"><?= htmlspecialchars($t['phase_name'] ?? 'Phase') ?></div>
                        <div style="font-size:10px; color:var(--text-muted); margin-top:1px;">
                            <?= htmlspecialchars($group['project_name']) ?> &bull;
                            <span class="pt-month-pill"><?= $monthLabel ?></span>
                        </div>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <?php if ($t['responsible_team'] ?? ''): ?>
                    <span style="font-size:10.5px; color:var(--text-muted);">
                        <i class="fal fa-users me-1"></i><?= htmlspecialchars($t['responsible_team']) ?>
                    </span>
                    <?php endif; ?>
                    <span class="pt-badge" style="background:<?= $sm['bg'] ?>; color:<?= $sm['color'] ?>;">
                        <?= $t['planning_status'] ?>
                    </span>
                    <?php if ($hasBoq): ?>
                    <span class="pt-badge" style="background:rgba(16,185,129,.1); color:#10b981;">
                        <i class="fal fa-check-circle me-1"></i>BOQ Created
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats -->
            <div class="pt-card-body">
                <div class="pt-stat">
                    <div class="pt-stat-val"><?= $items ?></div>
                    <div class="pt-stat-label">Work Items</div>
                </div>
                <div class="pt-stat">
                    <div class="pt-stat-val" style="color:var(--pt-primary);"><?= number_format($actual, 1) ?>%</div>
                    <div class="pt-stat-label">Avg Progress</div>
                    <div class="pt-prog-bar">
                        <div class="pt-prog-fill" style="width:<?= min($actual, 100) ?>%; background:<?= $actual >= 100 ? 'var(--pt-green)' : 'var(--pt-primary)' ?>"></div>
                    </div>
                </div>
                <div class="pt-stat">
                    <div class="pt-stat-val" style="font-size:12px; color:var(--text-muted);"><?= $monthLabel ?></div>
                    <div class="pt-stat-label">Target Month</div>
                </div>
                <div class="pt-stat">
                    <div class="pt-stat-val" style="font-size:12px; color:<?= $t['planning_status'] === 'Approved' ? 'var(--pt-green)' : 'var(--pt-amber)' ?>;">
                        <?= $t['planning_status'] ?>
                    </div>
                    <div class="pt-stat-label">Status</div>
                </div>
            </div>

            <!-- Footer with BOQ Action -->
            <div class="pt-card-footer">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- View Target Details -->
                    <a href="<?= $base ?>/con-monthly-target/<?= $t['id'] ?>" class="btn-target-view">
                        <i class="fal fa-eye"></i> Target Details
                    </a>

                    <?php if ($hasBoq && $boqId): ?>
                    <!-- BOQ Exists → View latest + allow new -->
                    <a href="<?= $base ?>/con-boq-show/<?= $boqId ?>" class="btn-boq-view">
                        <i class="fal fa-file-invoice"></i> View BOQ
                    </a>
                    <a href="<?= $base ?>/con-boq-create?target_id=<?= $t['id'] ?>" class="btn-boq-create"
                       title="আগের BOQ receive হলে নতুন BOQ তৈরি করুন">
                        <i class="fal fa-plus-circle"></i> New BOQ
                    </a>
                    <?php else: ?>
                    <!-- No BOQ → Create first -->
                    <a href="<?= $base ?>/con-boq-create?target_id=<?= $t['id'] ?>" class="btn-boq-create">
                        <i class="fal fa-plus-circle"></i> BOQ তৈরি করুন
                    </a>
                    <?php endif; ?>
                </div>

                <div style="font-size:10.5px; color:var(--text-muted);">
                    <?php if ($t['phase_weight'] ?? 0): ?>
                    Phase weight: <strong><?= number_format((float)$t['phase_weight'], 0) ?>%</strong>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <?php endif; ?>
</div>

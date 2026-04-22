<?php
$addPage = true;
$show_breadcrumb = false;
$base = $GLOBALS['base_url'];

function estFmt($n) {
    $n = (float)$n;
    if ($n >= 10000000) return '৳' . number_format($n/10000000, 2) . ' Cr';
    if ($n >= 100000)   return '৳' . number_format($n/100000,   2) . ' L';
    if ($n >= 1000)     return '৳' . number_format($n/1000,     1) . 'K';
    return '৳' . number_format($n, 0);
}

$totalDrawings           = $totalDrawings           ?? 0;
$totalDesignTypes        = $totalDesignTypes        ?? 0;
$pendingEstimates        = $pendingEstimates        ?? 0;
$approvedEstimates       = $approvedEstimates       ?? 0;
$totalBudgetValue        = $totalBudgetValue        ?? 0.0;
$drawingsByType          = $drawingsByType          ?? [];
$recentDrawings          = $recentDrawings          ?? [];
$projectsWithoutEstimate = $projectsWithoutEstimate ?? [];
$recentEstimates         = $recentEstimates         ?? [];

$typeColors = [
    '#198754','#3b82f6','#f59e0b','#8b5cf6','#14b8a6',
    '#ef4444','#f97316','#ec4899','#6366f1','#10b981',
];
?>
<style>
:root {
  --est-primary:#198754; --est-dark:#0f5132; --est-light:#20a86a;
  --est-blue:#3b82f6; --est-green:#10b981; --est-amber:#f59e0b;
  --est-red:#ef4444; --est-purple:#8b5cf6; --est-teal:#14b8a6;
}

.est-hero {
  background: linear-gradient(135deg, #0f5132 0%, #198754 55%, #20a86a 100%);
  border-radius: 18px; padding: 28px 32px; margin-bottom: 22px;
  position: relative; overflow: hidden;
}
.est-hero::before { content:''; position:absolute; width:280px; height:280px;
  border-radius:50%; background:rgba(255,255,255,.04); top:-90px; right:-70px; }
.est-hero::after { content:''; position:absolute; width:170px; height:170px;
  border-radius:50%; background:rgba(255,255,255,.03); bottom:-55px; left:-35px; }
.est-hero-inner { position:relative; z-index:2; }

.est-role-badge { display:inline-flex; align-items:center; gap:7px;
  background:rgba(255,255,255,.13); border:1px solid rgba(255,255,255,.22);
  padding:4px 14px; border-radius:20px; font-size:10.5px; font-weight:700;
  letter-spacing:1.5px; text-transform:uppercase; color:#fff; margin-bottom:12px; }
.est-hero-title  { font-size:26px; font-weight:900; color:#fff; margin:0 0 5px; }
.est-hero-sub    { font-size:12.5px; color:rgba(255,255,255,.7); margin:0; }
.est-btn-w { background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.3);
  color:#fff; border-radius:10px; padding:7px 18px; font-size:12px; font-weight:700;
  text-decoration:none; display:inline-flex; align-items:center; gap:6px; transition:background .18s; }
.est-btn-w:hover { background:rgba(255,255,255,.25); color:#fff; text-decoration:none; }

/* KPI */
.est-kpi-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:14px; margin-bottom:22px; }
@media(max-width:1200px){ .est-kpi-grid { grid-template-columns:repeat(3,1fr); } }
@media(max-width:768px) { .est-kpi-grid { grid-template-columns:repeat(2,1fr); } }
.est-kpi { border-radius:14px; padding:18px 20px; border:1px solid rgba(0,0,0,.08);
  background:var(--card-bg,#fff); position:relative; overflow:hidden;
  transition:transform .18s, box-shadow .18s; cursor:default; }
.est-kpi:hover { transform:translateY(-3px); box-shadow:0 8px 28px rgba(0,0,0,.11); }
.est-kpi-a { text-decoration:none !important; display:block; }
.est-accent { position:absolute; left:0; top:0; width:4px; height:100%; border-radius:14px 0 0 14px; }
.est-icon { width:40px; height:40px; border-radius:11px; display:flex; align-items:center;
  justify-content:center; font-size:17px; margin-bottom:11px; }
.est-val { font-size:26px; font-weight:900; line-height:1; margin-bottom:3px; }
.est-lbl { font-size:10px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--text-muted); }
.est-sub { font-size:11px; color:var(--text-muted); margin-top:2px; }

/* Panel */
.est-panel { border-radius:14px; padding:22px; border:1px solid rgba(0,0,0,.08);
  background:var(--card-bg,#fff); margin-bottom:20px; }
.est-panel-title { font-size:11px; font-weight:700; letter-spacing:1.5px;
  text-transform:uppercase; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
.est-dot { width:8px; height:8px; border-radius:50%; }

/* Table */
.est-table { width:100%; border-collapse:collapse; }
.est-table th { font-size:10px; letter-spacing:1.3px; text-transform:uppercase;
  font-weight:600; padding:0 10px 10px; border-bottom:2px solid rgba(0,0,0,.07); text-align:left; }
.est-table td { padding:11px 10px; border-bottom:1px solid rgba(0,0,0,.05);
  font-size:12.5px; vertical-align:middle; }
.est-table tr:last-child td { border-bottom:none; }

/* Design Type Bars */
.dt-bar-row { display:flex; align-items:center; gap:10px; margin-bottom:10px; }
.dt-bar-row:last-child { margin-bottom:0; }
.dt-bar-track { flex:1; height:9px; border-radius:6px; background:rgba(0,0,0,.07); overflow:hidden; }
.dt-bar-fill { height:100%; border-radius:6px; }
.dt-bar-name { font-size:12px; min-width:130px; font-weight:600; }
.dt-bar-count { font-size:12px; font-weight:800; min-width:28px; text-align:right; }

/* Status pill */
.est-pill { font-size:10px; font-weight:700; padding:3px 10px; border-radius:20px;
  display:inline-flex; align-items:center; gap:4px; }
.est-pending  { background:rgba(245,158,11,.12); color:#d97706; }
.est-approved { background:rgba(16,185,129,.12);  color:#059669; }
.est-cancelled{ background:rgba(239,68,68,.12);   color:#dc2626; }

.empty-state { text-align:center; padding:28px 0; color:var(--text-muted); }
.empty-state i { font-size:30px; opacity:.25; display:block; margin-bottom:8px; }
</style>

<!-- ═══════════════ HERO ═══════════════ -->
<div class="est-hero">
  <div class="est-hero-inner" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:14px">
    <div>
      <div class="est-role-badge"><i class="fal fa-calculator"></i> Estimator</div>
      <h4 class="est-hero-title">Estimator Dashboard</h4>
      <p class="est-hero-sub">
        <i class="fal fa-calendar me-1"></i><?= date('d M Y, h:i A') ?>
        &nbsp;·&nbsp; Review drawings → create accurate budgets
      </p>
      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px">
        <a href="<?= $base ?>/con-project-drawings" class="est-btn-w"><i class="fal fa-drafting-compass"></i> Project Drawings</a>
        <a href="<?= $base ?>/con-estimates" class="est-btn-w"><i class="fal fa-file-invoice-dollar"></i> Estimate Budgets</a>
        <a href="<?= $base ?>/con-estimate-create" class="est-btn-w"><i class="fal fa-plus"></i> New Estimate</a>
      </div>
    </div>
    <!-- Approved Budget highlight -->
    <div style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);
      border-radius:14px;padding:18px 22px;text-align:center;min-width:180px">
      <div style="font-size:10px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;
        color:rgba(255,255,255,.7);margin-bottom:6px">Total Approved Budget</div>
      <div style="font-size:26px;font-weight:900;color:#fff"><?= estFmt($totalBudgetValue) ?></div>
      <div style="font-size:11px;color:rgba(255,255,255,.6);margin-top:4px">
        <?= $approvedEstimates ?> approved estimates
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════ KPI GRID ═══════════════ -->
<div class="est-kpi-grid">

  <a href="<?= $base ?>/con-project-drawings" class="est-kpi est-kpi-a">
    <div class="est-accent" style="background:var(--est-blue)"></div>
    <div class="est-icon" style="background:rgba(59,130,246,.12);color:var(--est-blue)"><i class="fal fa-drafting-compass"></i></div>
    <div class="est-lbl">Total Drawings</div>
    <div class="est-val" style="color:var(--est-blue)"><?= $totalDrawings ?></div>
    <div class="est-sub">All project drawings</div>
  </a>

  <div class="est-kpi">
    <div class="est-accent" style="background:var(--est-purple)"></div>
    <div class="est-icon" style="background:rgba(139,92,246,.12);color:var(--est-purple)"><i class="fal fa-layer-group"></i></div>
    <div class="est-lbl">Design Types</div>
    <div class="est-val" style="color:var(--est-purple)"><?= $totalDesignTypes ?></div>
    <div class="est-sub">Available categories</div>
  </div>

  <a href="<?= $base ?>/con-estimate-create" class="est-kpi est-kpi-a">
    <div class="est-accent" style="background:var(--est-amber)"></div>
    <div class="est-icon" style="background:rgba(245,158,11,.12);color:var(--est-amber)"><i class="fal fa-clock"></i></div>
    <div class="est-lbl">Pending Estimates</div>
    <div class="est-val" style="color:var(--est-amber)"><?= $pendingEstimates ?></div>
    <div class="est-sub">Awaiting approval</div>
    <?php if ($pendingEstimates > 0): ?>
    <span style="position:absolute;top:14px;right:14px;font-size:9px;font-weight:700;
      padding:3px 8px;border-radius:20px;background:rgba(245,158,11,.12);color:var(--est-amber)">REVIEW</span>
    <?php endif; ?>
  </a>

  <a href="<?= $base ?>/con-estimates" class="est-kpi est-kpi-a">
    <div class="est-accent" style="background:var(--est-green)"></div>
    <div class="est-icon" style="background:rgba(16,185,129,.12);color:var(--est-green)"><i class="fal fa-check-circle"></i></div>
    <div class="est-lbl">Approved Estimates</div>
    <div class="est-val" style="color:var(--est-green)"><?= $approvedEstimates ?></div>
    <div class="est-sub">Locked & finalised</div>
  </a>

  <div class="est-kpi">
    <div class="est-accent" style="background:var(--est-primary)"></div>
    <div class="est-icon" style="background:rgba(25,135,84,.12);color:var(--est-primary)"><i class="fal fa-triangle-exclamation"></i></div>
    <div class="est-lbl">No Estimate Yet</div>
    <div class="est-val" style="color:var(--est-primary)"><?= count($projectsWithoutEstimate) ?></div>
    <div class="est-sub">Projects need budget</div>
  </div>

</div>

<!-- ═══════════════ MAIN ROW ═══════════════ -->
<div class="row">

  <!-- Left: Recent Drawings -->
  <div class="col-lg-8">
    <div class="est-panel">
      <div class="est-panel-title">
        <div class="est-dot" style="background:var(--est-blue)"></div>
        Recent Project Drawings
        <a href="<?= $base ?>/con-project-drawings" style="font-size:10px;color:var(--est-blue);margin-left:auto;text-decoration:none">
          View All <i class="fal fa-arrow-right ms-1"></i>
        </a>
      </div>
      <?php if (empty($recentDrawings)): ?>
        <div class="empty-state"><i class="fal fa-drafting-compass"></i>No drawings uploaded yet</div>
      <?php else: ?>
      <div style="overflow-x:auto">
        <table class="est-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Project</th>
              <th>Design Type</th>
              <th>Designer / Firm</th>
              <th>Version</th>
              <th>Status</th>
              <th>Link</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentDrawings as $di => $dr): ?>
            <tr>
              <td style="font-size:11px;color:var(--text-muted)"><?= $di+1 ?></td>
              <td style="font-weight:700;font-size:12.5px"><?= htmlspecialchars($dr['project_name'] ?? 'N/A') ?></td>
              <td>
                <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;
                  background:rgba(139,92,246,.1);color:#8b5cf6">
                  <?= htmlspecialchars($dr['design_type_name'] ?? '—') ?>
                </span>
              </td>
              <td style="font-size:12px">
                <?php if (!empty($dr['designer_full_name'])): ?>
                  <i class="fal fa-user me-1" style="color:var(--text-muted)"></i><?= htmlspecialchars($dr['designer_full_name']) ?>
                <?php elseif (!empty($dr['designer_name'])): ?>
                  <i class="fal fa-building me-1" style="color:var(--text-muted)"></i><?= htmlspecialchars($dr['designer_name']) ?>
                <?php else: ?>
                  <span style="color:var(--text-muted)">—</span>
                <?php endif; ?>
              </td>
              <td style="font-size:11.5px;color:var(--text-muted)">v<?= htmlspecialchars($dr['version'] ?? '1.0') ?></td>
              <td>
                <?php
                  $dSt = $dr['status'] ?? 'Draft';
                  $dStColor = $dSt === 'Approved' ? '#10b981' : ($dSt === 'In Review' ? '#f59e0b' : '#8b5cf6');
                ?>
                <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;
                  background:<?= $dStColor ?>18;color:<?= $dStColor ?>"><?= $dSt ?></span>
              </td>
              <td>
                <?php if (!empty($dr['design_link'])): ?>
                  <a href="<?= htmlspecialchars($dr['design_link']) ?>" target="_blank"
                    style="font-size:11px;color:var(--est-blue);text-decoration:none">
                    Open <i class="fal fa-external-link ms-1"></i>
                  </a>
                <?php else: ?>
                  <span style="font-size:11px;color:var(--text-muted)">No link</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- Recent Estimates Table -->
    <div class="est-panel">
      <div class="est-panel-title">
        <div class="est-dot" style="background:var(--est-green)"></div>
        Recent Estimate Budgets
        <a href="<?= $base ?>/con-estimates" style="font-size:10px;color:var(--est-blue);margin-left:auto;text-decoration:none">
          View All <i class="fal fa-arrow-right ms-1"></i>
        </a>
      </div>
      <?php if (empty($recentEstimates)): ?>
        <div class="empty-state"><i class="fal fa-file-invoice-dollar"></i>No estimates created yet</div>
      <?php else: ?>
      <div style="overflow-x:auto">
        <table class="est-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Project</th>
              <th>Material</th>
              <th>Labor</th>
              <th>Grand Total</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentEstimates as $ei => $est):
              $st = $est['status'] ?? 'pending';
              $stClass = $st === 'approved' ? 'est-approved' : ($st === 'cancelled' ? 'est-cancelled' : 'est-pending');
              $stIcon  = $st === 'approved' ? 'fa-check-circle' : ($st === 'cancelled' ? 'fa-times-circle' : 'fa-clock');
            ?>
            <tr>
              <td style="font-size:11px;color:var(--text-muted)"><?= $ei+1 ?></td>
              <td style="font-weight:700;font-size:12.5px"><?= htmlspecialchars($est['project_name'] ?? 'N/A') ?></td>
              <td style="color:var(--est-green);font-weight:600"><?= estFmt($est['material_cost_total'] ?? 0) ?></td>
              <td style="color:var(--est-blue);font-weight:600"><?= estFmt($est['labor_cost_total'] ?? 0) ?></td>
              <td style="font-weight:900;color:var(--est-primary)"><?= estFmt($est['total_estimated_cost'] ?? 0) ?></td>
              <td>
                <span class="est-pill <?= $stClass ?>">
                  <i class="fal <?= $stIcon ?>"></i><?= ucfirst($st) ?>
                </span>
              </td>
              <td>
                <a href="<?= $base ?>/con-estimate-show/<?= $est['id'] ?>"
                  style="font-size:11px;color:var(--est-blue);text-decoration:none">
                  View <i class="fal fa-arrow-right ms-1"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Right Column -->
  <div class="col-lg-4">

    <!-- Drawings by Design Type -->
    <div class="est-panel">
      <div class="est-panel-title">
        <div class="est-dot" style="background:var(--est-purple)"></div>
        Drawings by Design Type
      </div>
      <?php if (empty($drawingsByType)): ?>
        <div class="empty-state" style="padding:16px 0"><i class="fal fa-layer-group"></i>No data</div>
      <?php else:
        $maxCount = max(array_column($drawingsByType, 'drawing_count') ?: [1]);
        foreach ($drawingsByType as $ci => $dt):
          $pct = $maxCount > 0 ? round($dt['drawing_count'] / $maxCount * 100) : 0;
          $color = $typeColors[$ci % count($typeColors)];
      ?>
      <div class="dt-bar-row">
        <div class="dt-bar-name" style="color:<?= $color ?>" title="<?= htmlspecialchars($dt['design_type_name']) ?>">
          <?= htmlspecialchars(mb_strimwidth($dt['design_type_name'], 0, 18, '…')) ?>
        </div>
        <div class="dt-bar-track">
          <div class="dt-bar-fill" style="width:<?= $pct ?>%;background:<?= $color ?>"></div>
        </div>
        <div class="dt-bar-count" style="color:<?= $color ?>"><?= $dt['drawing_count'] ?></div>
      </div>
      <?php endforeach; endif; ?>
    </div>

    <!-- Projects Without Estimate -->
    <div class="est-panel">
      <div class="est-panel-title">
        <div class="est-dot" style="background:var(--est-amber)"></div>
        Projects Needing Budget
        <?php if (!empty($projectsWithoutEstimate)): ?>
        <span style="margin-left:auto;font-size:9px;font-weight:700;padding:2px 8px;border-radius:10px;
          background:rgba(245,158,11,.12);color:var(--est-amber)">
          <?= count($projectsWithoutEstimate) ?> Projects
        </span>
        <?php endif; ?>
      </div>
      <?php if (empty($projectsWithoutEstimate)): ?>
        <div class="empty-state" style="padding:16px 0">
          <i class="fal fa-circle-check" style="color:var(--est-green);opacity:.5"></i>
          <span style="color:var(--est-green);font-weight:600">All projects have estimates!</span>
        </div>
      <?php else: ?>
        <?php foreach ($projectsWithoutEstimate as $pw): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;
          padding:10px 12px;border-radius:10px;border:1px solid rgba(0,0,0,.06);
          margin-bottom:8px;gap:8px">
          <div>
            <div style="font-size:12.5px;font-weight:700"><?= htmlspecialchars($pw['name']) ?></div>
            <div style="font-size:10.5px;color:var(--text-muted)"><?= $pw['project_status'] ?? '—' ?></div>
          </div>
          <a href="<?= $base ?>/con-estimate-create?project=<?= $pw['id'] ?>"
            style="flex-shrink:0;background:linear-gradient(135deg,#0f5132,#198754);
              color:#fff;border-radius:8px;padding:5px 12px;font-size:11px;font-weight:700;
              text-decoration:none;white-space:nowrap">
            <i class="fal fa-plus me-1"></i>Budget
          </a>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="est-panel">
      <div class="est-panel-title">
        <div class="est-dot" style="background:var(--est-teal)"></div>
        Quick Actions
      </div>
      <div style="display:flex;flex-direction:column;gap:10px">
        <?php
        $actions = [
          ['href'=>'/con-project-drawings','icon'=>'fa-drafting-compass','label'=>'View All Drawings',      'color'=>'#3b82f6'],
          ['href'=>'/con-estimate-create',  'icon'=>'fa-plus-circle',     'label'=>'Create New Estimate',    'color'=>'#198754'],
          ['href'=>'/con-estimates',        'icon'=>'fa-file-invoice-dollar','label'=>'All Estimates',        'color'=>'#8b5cf6'],
          ['href'=>'/con-design-types',     'icon'=>'fa-layer-group',     'label'=>'Design Types',           'color'=>'#f59e0b'],
        ];
        foreach ($actions as $act):
        ?>
        <a href="<?= $base . $act['href'] ?>"
          style="display:flex;align-items:center;gap:12px;padding:12px;border-radius:12px;
            border:1px solid rgba(0,0,0,.08);text-decoration:none;color:<?= $act['color'] ?>;
            font-size:12px;font-weight:700;transition:box-shadow .15s"
          onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,.1)'"
          onmouseout="this.style.boxShadow='none'">
          <div style="width:38px;height:38px;border-radius:10px;background:<?= $act['color'] ?>18;
            display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0">
            <i class="fal <?= $act['icon'] ?>"></i>
          </div>
          <span><?= $act['label'] ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</div>
<div style="height:30px"></div>

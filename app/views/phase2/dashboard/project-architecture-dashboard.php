<?php
$addPage = true;
$show_breadcrumb = false;
$base = $GLOBALS['base_url'];

$totalAE              = $totalAE              ?? 0;
$totalDesignTypes     = $totalDesignTypes     ?? 0;
$totalDrawings        = $totalDrawings        ?? 0;
$projectsWithDrawings = $projectsWithDrawings ?? 0;
$drawingsByType       = $drawingsByType       ?? [];
$recentDrawings       = $recentDrawings       ?? [];
$recentAE             = $recentAE             ?? [];
?>
<style>
  :root {
    --pa-primary: #198754;
    --pa-dark: #0f5132;
    --pa-light: #20a86a;
    --pa-blue: #3b82f6;
    --pa-green: #10b981;
    --pa-amber: #f59e0b;
    --pa-red: #ef4444;
    --pa-purple: #8b5cf6;
    --pa-indigo: #6366f1;
    --pa-teal: #14b8a6;
  }

  .pa-hero {
    background: linear-gradient(135deg, #0f5132 0%, #198754 55%, #20a86a 100%);
    border-radius: 18px;
    padding: 28px 32px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
  }

  .pa-hero::before {
    content: '';
    position: absolute;
    width: 270px;
    height: 270px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .04);
    top: -90px;
    right: -70px;
  }

  .pa-hero::after {
    content: '';
    position: absolute;
    width: 170px;
    height: 170px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .03);
    bottom: -55px;
    left: -35px;
  }

  .pa-role-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, .12);
    border: 1px solid rgba(255, 255, 255, .2);
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: #fff;
    margin-bottom: 14px;
  }

  .pa-hero-title {
    font-size: 28px;
    font-weight: 900;
    color: #fff;
    margin: 0 0 6px;
  }

  .pa-hero-sub {
    font-size: 13px;
    color: rgba(255, 255, 255, .7);
    margin: 0;
  }

  .pa-btn-white {
    background: rgba(255, 255, 255, .15);
    border: 1px solid rgba(255, 255, 255, .3);
    color: #fff;
    border-radius: 10px;
    padding: 8px 18px;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background .18s;
  }

  .pa-btn-white:hover {
    background: rgba(255, 255, 255, .25);
    color: #fff;
    text-decoration: none;
  }

  .pa-hero-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 16px;
  }

  /* KPI Grid */
  .kpi-grid-4 {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 22px;
  }

  @media(max-width:992px) {
    .kpi-grid-4 {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  @media(max-width:576px) {
    .kpi-grid-4 {
      grid-template-columns: repeat(1, 1fr);
    }
  }

  .kpi-card {
    border-radius: 14px;
    padding: 18px 20px;
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, .08);
    text-decoration: none !important;
    display: block;
    transition: transform .18s, box-shadow .18s;
    background: var(--card-bg, #fff);
  }

  .kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 28px rgba(0, 0, 0, .12);
  }

  .kpi-accent {
    position: absolute;
    left: 0;
    top: 0;
    width: 4px;
    height: 100%;
    border-radius: 14px 0 0 14px;
  }

  .kpi-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    margin-bottom: 12px;
  }

  .kpi-value {
    font-size: 28px;
    font-weight: 900;
    line-height: 1;
    margin-bottom: 4px;
  }

  .kpi-label {
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 3px;
  }

  .kpi-sub {
    font-size: 11px;
    color: var(--text-muted);
  }

  /* Panel */
  .pa-panel {
    border-radius: 14px;
    padding: 22px;
    border: 1px solid rgba(0, 0, 0, .08);
    background: var(--card-bg, #fff);
    margin-bottom: 22px;
  }

  .pa-panel-title {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .pa-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
  }

  .empty-msg {
    text-align: center;
    padding: 28px 0;
    color: var(--text-muted);
    font-size: 12px;
  }

  /* Table */
  .pa-table {
    width: 100%;
    border-collapse: collapse;
  }

  .pa-table th {
    font-size: 10px;
    letter-spacing: 1.4px;
    text-transform: uppercase;
    font-weight: 600;
    padding: 0 10px 10px;
    border-bottom: 2px solid rgba(0, 0, 0, .07);
    text-align: left;
  }

  .pa-table td {
    padding: 11px 10px;
    border-bottom: 1px solid rgba(0, 0, 0, .05);
    font-size: 12.5px;
    vertical-align: middle;
  }

  .pa-table tr:last-child td {
    border-bottom: none;
  }

  /* Type bar rows */
  .type-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid rgba(0, 0, 0, .06);
  }

  .type-row:last-child {
    border-bottom: none;
  }

  .type-track {
    flex: 1;
    height: 8px;
    border-radius: 6px;
    background: rgba(0, 0, 0, .07);
    overflow: hidden;
  }

  .type-fill {
    height: 100%;
    border-radius: 6px;
  }

  /* AE card */
  .ae-card {
    border-radius: 12px;
    padding: 14px 16px;
    border: 1px solid rgba(0, 0, 0, .08);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 14px;
    transition: box-shadow .15s;
  }

  .ae-card:last-child {
    margin-bottom: 0;
  }

  .ae-card:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, .1);
  }

  .ae-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba(25, 135, 84, .1);
    color: #198754;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
  }
  html { scroll-behavior: smooth; }
  .kpi-card { cursor: pointer; }
  .sec-anchor { scroll-margin-top: 80px; }
</style>

<!-- ═════ HERO ═════ -->
<div class="pa-hero" style="position:relative;z-index:1">
  <div style="position:relative;z-index:2;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px">
    <div>
      <div class="pa-role-badge"><i class="fa-solid fa-building-columns"></i> Project Architecture</div>
      <h4 class="pa-hero-title">Architect Dashboard</h4>
      <p class="pa-hero-sub">Architect & Engineering · Design Types · Project Drawings · <?= date('d M Y, h:i A') ?></p>
    </div>
  </div>
  <div class="pa-hero-actions">
    <a href="<?= $base ?>/con-architect-engineering" class="pa-btn-white"><i class="fal fa-drafting-compass"></i> Architect & Engineering</a>
    <a href="<?= $base ?>/con-design-types" class="pa-btn-white"><i class="fal fa-layer-group"></i> Design Types</a>
    <a href="<?= $base ?>/con-project-drawings" class="pa-btn-white"><i class="fal fa-pencil-ruler"></i> Project Drawings</a>
  </div>
</div>

<!-- ═════ KPI GRID ═════ -->
<div class="kpi-grid-4">

  <!-- A&E → scroll to recent AE panel -->
  <a href="#sec-ae" class="kpi-card">
    <div class="kpi-accent" style="background:var(--pa-primary)"></div>
    <div class="kpi-icon" style="background:rgba(25,135,84,.12);color:var(--pa-primary)"><i class="fal fa-drafting-compass"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Architect & Engineering</div>
    <div class="kpi-value" style="color:var(--pa-primary)"><?= $totalAE ?></div>
    <div class="kpi-sub">Total A&E records</div>
    <span style="position:absolute;top:14px;right:14px;font-size:9px;font-weight:700;padding:3px 8px;border-radius:20px;background:rgba(25,135,84,.1);color:var(--pa-primary)"><i class="fal fa-arrow-down me-1"></i>View</span>
  </a>

  <!-- Design Types → scroll to type bars -->
  <a href="#sec-types" class="kpi-card">
    <div class="kpi-accent" style="background:var(--pa-indigo)"></div>
    <div class="kpi-icon" style="background:rgba(99,102,241,.12);color:var(--pa-indigo)"><i class="fal fa-layer-group"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Design Types</div>
    <div class="kpi-value" style="color:var(--pa-indigo)"><?= $totalDesignTypes ?></div>
    <div class="kpi-sub">Registered design categories</div>
    <span style="position:absolute;top:14px;right:14px;font-size:9px;font-weight:700;padding:3px 8px;border-radius:20px;background:rgba(99,102,241,.1);color:var(--pa-indigo)"><i class="fal fa-arrow-down me-1"></i>View</span>
  </a>

  <!-- Drawings → scroll to drawings table -->
  <a href="#sec-drawings" class="kpi-card">
    <div class="kpi-accent" style="background:var(--pa-teal)"></div>
    <div class="kpi-icon" style="background:rgba(20,184,166,.12);color:var(--pa-teal)"><i class="fal fa-pencil-ruler"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Project Drawings</div>
    <div class="kpi-value" style="color:var(--pa-teal)"><?= $totalDrawings ?></div>
    <div class="kpi-sub">Total drawing files</div>
    <span style="position:absolute;top:14px;right:14px;font-size:9px;font-weight:700;padding:3px 8px;border-radius:20px;background:rgba(20,184,166,.1);color:var(--pa-teal)"><i class="fal fa-arrow-down me-1"></i>View</span>
  </a>

  <!-- Projects with Drawings → scroll to drawings table -->
  <a href="#sec-drawings" class="kpi-card">
    <div class="kpi-accent" style="background:var(--pa-amber)"></div>
    <div class="kpi-icon" style="background:rgba(245,158,11,.12);color:var(--pa-amber)"><i class="fal fa-folder-open"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Projects with Drawings</div>
    <div class="kpi-value" style="color:var(--pa-amber)"><?= $projectsWithDrawings ?></div>
    <div class="kpi-sub">Projects having drawings</div>
    <span style="position:absolute;top:14px;right:14px;font-size:9px;font-weight:700;padding:3px 8px;border-radius:20px;background:rgba(245,158,11,.1);color:var(--pa-amber)"><i class="fal fa-arrow-down me-1"></i>View</span>
  </a>

</div>

<!-- ═════ ROW 2 ═════ -->
<div class="row">

  <!-- Recent Project Drawings -->
  <div class="col-lg-8">
    <div id="sec-drawings" class="pa-panel sec-anchor">
      <div class="pa-panel-title">
        <div class="pa-dot" style="background:var(--pa-teal)"></div>
        Recent Project Drawings
        <a href="<?= $base ?>/con-project-drawings" style="font-size:10px;color:var(--pa-blue);margin-left:auto;text-decoration:none">View All <i class="fal fa-external-link ms-1"></i></a>
      </div>
      <?php if (empty($recentDrawings)): ?>
        <div class="empty-msg"><i class="fal fa-pencil-ruler" style="font-size:24px;display:block;margin-bottom:8px"></i>No project drawings yet</div>
      <?php else: ?>
        <div style="overflow-x:auto">
          <table class="pa-table">
            <thead>
              <tr>
                <th>Drawing Name</th>
                <th>Project</th>
                <th>Design Type</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentDrawings as $dr): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($dr['drawing_name'] ?? $dr['name'] ?? 'N/A') ?></strong></td>
                  <td style="font-size:11.5px;color:var(--text-muted)"><?= htmlspecialchars($dr['project_name'] ?? 'N/A') ?></td>
                  <td>
                    <span style="font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px;background:rgba(20,184,166,.1);color:var(--pa-teal)">
                      <?= htmlspecialchars($dr['design_type_name'] ?? 'N/A') ?>
                    </span>
                  </td>
                  <td>
                    <a href="<?= $base ?>/con-project-drawings" style="font-size:11px;color:var(--pa-blue);text-decoration:none">
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
    <div id="sec-types" class="pa-panel sec-anchor">
      <div class="pa-panel-title">
        <div class="pa-dot" style="background:var(--pa-indigo)"></div>
        Drawings by Design Type
      </div>
      <?php
      $typeColors = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#14b8a6', '#8b5cf6', '#3b82f6', '#f97316'];
      $maxCnt = !empty($drawingsByType) ? max(array_column($drawingsByType, 'cnt')) : 1;
      if ($maxCnt < 1) $maxCnt = 1;
      ?>
      <?php if (empty($drawingsByType)): ?>
        <div class="empty-msg">No design types found</div>
      <?php else: ?>
        <?php foreach ($drawingsByType as $idx => $dt):
          $clr = $typeColors[$idx % count($typeColors)];
          $pct = round($dt['cnt'] / $maxCnt * 100);
        ?>
          <div class="type-row">
            <div style="min-width:100px;font-size:12px;font-weight:700;color:<?= $clr ?>;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?= htmlspecialchars($dt['design_type']) ?>">
              <?= htmlspecialchars($dt['design_type']) ?>
            </div>
            <div class="type-track">
              <div class="type-fill" style="width:<?= $pct ?>%;background:<?= $clr ?>"></div>
            </div>
            <div style="font-size:12px;font-weight:800;min-width:28px;text-align:right;color:<?= $clr ?>"><?= $dt['cnt'] ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

   

  </div>
</div>

<div class="row">
  <div class="col-xxl-6">
     <!-- Quick Actions -->
    <div class="pa-panel">
      <div class="pa-panel-title">
        <div class="pa-dot" style="background:var(--pa-teal)"></div>
        Quick Actions
      </div>
      <div style="display:flex;flex-direction:column;gap:10px">
        <a href="<?= $base ?>/con-architect-engineering" style="display:flex;align-items:center;gap:12px;padding:13px;border-radius:12px;border:1px solid rgba(0,0,0,.08);text-decoration:none;color:var(--pa-primary);font-size:12px;font-weight:700;transition:box-shadow .15s" onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,.1)'" onmouseout="this.style.boxShadow='none'">
          <div style="width:38px;height:38px;border-radius:10px;background:rgba(25,135,84,.12);display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0"><i class="fal fa-drafting-compass"></i></div>
          <span>Architect & Engineering</span>
        </a>
        <a href="<?= $base ?>/con-design-types" style="display:flex;align-items:center;gap:12px;padding:13px;border-radius:12px;border:1px solid rgba(0,0,0,.08);text-decoration:none;color:var(--pa-indigo);font-size:12px;font-weight:700;transition:box-shadow .15s" onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,.1)'" onmouseout="this.style.boxShadow='none'">
          <div style="width:38px;height:38px;border-radius:10px;background:rgba(99,102,241,.12);display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0"><i class="fal fa-layer-group"></i></div>
          <span>Manage Design Types</span>
        </a>
        <a href="<?= $base ?>/con-project-drawings" style="display:flex;align-items:center;gap:12px;padding:13px;border-radius:12px;border:1px solid rgba(0,0,0,.08);text-decoration:none;color:var(--pa-teal);font-size:12px;font-weight:700;transition:box-shadow .15s" onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,.1)'" onmouseout="this.style.boxShadow='none'">
          <div style="width:38px;height:38px;border-radius:10px;background:rgba(20,184,166,.12);display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0"><i class="fal fa-pencil-ruler"></i></div>
          <span>Project Drawings</span>
        </a>
      </div>
    </div>

   
  </div>
  <div class="col-xxl-6">
     <!-- ═════ RECENT A&E ═════ -->
    <?php if (!empty($recentAE)): ?>
      <div id="sec-ae" class="pa-panel sec-anchor">
        <div class="pa-panel-title">
          <div class="pa-dot" style="background:var(--pa-primary)"></div>
          Recent Architect & Engineering Records
          <a href="<?= $base ?>/con-architect-engineering" style="font-size:10px;color:var(--pa-blue);margin-left:auto;text-decoration:none">View All <i class="fal fa-external-link ms-1"></i></a>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px">
          <?php foreach ($recentAE as $ae): ?>
            <div class="ae-card">
              <div class="ae-icon"><i class="fal fa-drafting-compass"></i></div>
              <div style="min-width:0">
                <div style="font-size:13px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($ae['firm_name'] ?? $ae['name'] ?? 'A&E Record #' . $ae['id']) ?></div>
                <div style="font-size:11px;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($ae['project_name'] ?? 'N/A') ?></div>
                <?php if (!empty($ae['contact_person'])): ?>
                  <div style="font-size:11px;color:var(--pa-blue);margin-top:2px"><i class="fal fa-user me-1"></i><?= htmlspecialchars($ae['contact_person']) ?></div>
                <?php endif; ?>
              </div>
              <a href="<?= $base ?>/con-architect-engineering" style="margin-left:auto;flex-shrink:0;font-size:11px;color:var(--pa-blue);text-decoration:none"><i class="fal fa-arrow-right"></i></a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>



<div style="height:30px"></div>
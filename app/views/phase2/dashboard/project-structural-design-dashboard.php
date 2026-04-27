<?php
$addPage = true;
$show_breadcrumb = false;
$base = $GLOBALS['base_url'];

$totalDesigns          = $totalDesigns          ?? 0;
$projectsWithDesign    = $projectsWithDesign    ?? 0;
$internalDesigners     = $internalDesigners     ?? 0;
$externalDesigners     = $externalDesigners     ?? 0;
$totalProjects         = $totalProjects         ?? 0;
$projectsNoDesign      = $projectsNoDesign      ?? 0;
$recentDesigns         = $recentDesigns         ?? [];
$projectsNeedingDesign = $projectsNeedingDesign ?? [];
$byDesignerType        = $byDesignerType        ?? [];

$coverage = $totalProjects > 0 ? round($projectsWithDesign / $totalProjects * 100) : 0;
?>
<style>
  :root {
    --sd-primary: #198754;
    --sd-dark: #0f5132;
    --sd-light: #20a86a;
    --sd-blue: #3b82f6;
    --sd-green: #10b981;
    --sd-amber: #f59e0b;
    --sd-red: #ef4444;
    --sd-teal: #14b8a6;
    --sd-indigo: #6366f1;
    --sd-rose: #f43f5e;
  }

  /* ─── Hero ─────────────────────────────────────────────────────────── */
  .sd-hero {
    background: linear-gradient(135deg, #0f5132 0%, #198754 55%, #20a86a 100%);
    border-radius: 18px;
    padding: 28px 32px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
  }

  .sd-hero::before {
    content: '';
    position: absolute;
    width: 280px;
    height: 280px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .04);
    top: -90px;
    right: -70px;
  }

  .sd-hero::after {
    content: '';
    position: absolute;
    width: 160px;
    height: 160px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .03);
    bottom: -50px;
    left: -30px;
  }

  .sd-role-badge {
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

  .sd-hero-title {
    font-size: 28px;
    font-weight: 900;
    color: #fff;
    margin: 0 0 6px;
  }

  .sd-hero-sub {
    font-size: 13px;
    color: rgba(255, 255, 255, .7);
    margin: 0;
  }

  .sd-btn-white {
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

  .sd-btn-white:hover {
    background: rgba(255, 255, 255, .28);
    color: #fff;
    text-decoration: none;
  }

  .sd-hero-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 16px;
  }

  /* ─── KPI Grid ──────────────────────────────────────────────────────── */
  .kpi-grid-5 {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 14px;
    margin-bottom: 22px;
  }

  @media(max-width:1200px) {
    .kpi-grid-5 {
      grid-template-columns: repeat(3, 1fr);
    }
  }

  @media(max-width:768px) {
    .kpi-grid-5 {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  @media(max-width:480px) {
    .kpi-grid-5 {
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

  /* ─── Panel ─────────────────────────────────────────────────────────── */
  .sd-panel {
    border-radius: 14px;
    padding: 22px;
    border: 1px solid rgba(0, 0, 0, .08);
    background: var(--card-bg, #fff);
    margin-bottom: 22px;
  }

  .sd-panel-title {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .sd-dot {
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

  /* ─── Table ─────────────────────────────────────────────────────────── */
  .sd-table {
    width: 100%;
    border-collapse: collapse;
  }

  .sd-table th {
    font-size: 10px;
    letter-spacing: 1.4px;
    text-transform: uppercase;
    font-weight: 600;
    padding: 0 10px 10px;
    border-bottom: 2px solid rgba(0, 0, 0, .07);
    text-align: left;
  }

  .sd-table td {
    padding: 11px 10px;
    border-bottom: 1px solid rgba(0, 0, 0, .05);
    font-size: 12.5px;
    vertical-align: middle;
  }

  .sd-table tr:last-child td {
    border-bottom: none;
  }

  /* ─── Coverage bar ──────────────────────────────────────────────────── */
  .coverage-track {
    height: 10px;
    border-radius: 8px;
    background: rgba(0, 0, 0, .08);
    overflow: hidden;
    margin-top: 6px;
  }

  .coverage-fill {
    height: 100%;
    border-radius: 8px;
    background: linear-gradient(90deg, var(--sd-primary), var(--sd-light));
    transition: width .6s ease;
  }

  /* ─── Designer card ─────────────────────────────────────────────────── */
  .sd-design-card {
    border-radius: 12px;
    padding: 14px 16px;
    border: 1px solid rgba(0, 0, 0, .08);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 14px;
    transition: box-shadow .15s;
  }

  .sd-design-card:last-child {
    margin-bottom: 0;
  }

  .sd-design-card:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, .1);
  }

  .sd-avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
  }

  /* ─── Quick Action links ─────────────────────────────────────────────── */
  .qa-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 13px;
    border-radius: 12px;
    border: 1px solid rgba(0, 0, 0, .08);
    text-decoration: none;
    font-size: 12px;
    font-weight: 700;
    transition: box-shadow .15s;
    margin-bottom: 10px;
  }

  .qa-link:last-child {
    margin-bottom: 0;
  }

  .qa-link:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, .1);
    text-decoration: none;
  }

  .qa-link-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
  }
</style>

<!-- ═══ HERO ═══ -->
<div class="sd-hero" style="position:relative;z-index:1">
  <div style="position:relative;z-index:2;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px">
    <div>
      <div class="sd-role-badge"><i class="fa-solid fa-vector-square"></i> Structural Design</div>
      <h4 class="sd-hero-title">Structural Design Dashboard</h4>
      <p class="sd-hero-sub">Project Structural Designs &middot; Designer Records &middot; Coverage Tracking &middot; <?= date('d M Y, h:i A') ?></p>
    </div>
  </div>
  <div class="sd-hero-actions">
    <a href="<?= $base ?>/structure-design" class="sd-btn-white"><i class="fal fa-layer-plus"></i> All Structural Designs</a>
    <a href="<?= $base ?>/structure-design-create" class="sd-btn-white"><i class="fal fa-plus-circle"></i> Add New Design</a>
  </div>
</div>

<!-- ═══ KPI GRID ═══ -->
<div class="kpi-grid-5">

  <a href="<?= $base ?>/structure-design" class="kpi-card">
    <div class="kpi-accent" style="background:var(--sd-primary)"></div>
    <div class="kpi-icon" style="background:rgba(25,135,84,.12);color:var(--sd-primary)"><i class="fal fa-vector-square"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Total Designs</div>
    <div class="kpi-value" style="color:var(--sd-primary)"><?= $totalDesigns ?></div>
    <div class="kpi-sub">Structural design records</div>
  </a>

  <div class="kpi-card" style="cursor:default">
    <div class="kpi-accent" style="background:var(--sd-green)"></div>
    <div class="kpi-icon" style="background:rgba(16,185,129,.12);color:var(--sd-green)"><i class="fal fa-building"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Projects Covered</div>
    <div class="kpi-value" style="color:var(--sd-green)"><?= $projectsWithDesign ?></div>
    <div class="kpi-sub">of <?= $totalProjects ?> total projects</div>
  </div>

  <div class="kpi-card" style="cursor:default">
    <div class="kpi-accent" style="background:var(--sd-blue)"></div>
    <div class="kpi-icon" style="background:rgba(59,130,246,.12);color:var(--sd-blue)"><i class="fal fa-user-hard-hat"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Internal Designers</div>
    <div class="kpi-value" style="color:var(--sd-blue)"><?= $internalDesigners ?></div>
    <div class="kpi-sub">In-house team records</div>
  </div>

  <div class="kpi-card" style="cursor:default">
    <div class="kpi-accent" style="background:var(--sd-amber)"></div>
    <div class="kpi-icon" style="background:rgba(245,158,11,.12);color:var(--sd-amber)"><i class="fal fa-building-columns"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">External / Firm</div>
    <div class="kpi-value" style="color:var(--sd-amber)"><?= $externalDesigners ?></div>
    <div class="kpi-sub">Outsourced firms</div>
  </div>

  <div class="kpi-card" style="cursor:default">
    <div class="kpi-accent" style="background:var(--sd-rose)"></div>
    <div class="kpi-icon" style="background:rgba(244,63,94,.12);color:var(--sd-rose)"><i class="fal fa-triangle-exclamation"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Pending Coverage</div>
    <div class="kpi-value" style="color:var(--sd-rose)"><?= $projectsNoDesign ?></div>
    <div class="kpi-sub">Projects without design</div>
  </div>

</div>

<!-- ═══ Coverage Progress ═══ -->
<div class="sd-panel" style="margin-bottom:22px">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
    <div style="font-size:12px;font-weight:700;color:var(--text-muted);letter-spacing:.8px;text-transform:uppercase">
      <i class="fal fa-chart-pie me-2" style="color:var(--sd-primary)"></i>Project Structural Design Coverage
    </div>
    <div style="font-size:20px;font-weight:900;color:var(--sd-primary)"><?= $coverage ?>%</div>
  </div>
  <div class="coverage-track">
    <div class="coverage-fill" style="width:<?= $coverage ?>%"></div>
  </div>
  <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:11px;color:var(--text-muted)">
    <span><i class="fal fa-check-circle me-1" style="color:var(--sd-green)"></i><?= $projectsWithDesign ?> covered</span>
    <span><i class="fal fa-clock me-1" style="color:var(--sd-rose)"></i><?= $projectsNoDesign ?> pending</span>
    <span><i class="fal fa-folder me-1"></i><?= $totalProjects ?> total projects</span>
  </div>
</div>

<!-- ═══ ROW 2 ═══ -->
<div class="row">

  <!-- Recent Structural Designs -->
  <div class="col-lg-8">
    <div class="sd-panel">
      <div class="sd-panel-title">
        <div class="sd-dot" style="background:var(--sd-primary)"></div>
        Recent Structural Designs
        <a href="<?= $base ?>/structure-design" style="font-size:10px;color:var(--sd-blue);margin-left:auto;text-decoration:none">View All <i class="fal fa-arrow-right ms-1"></i></a>
      </div>
      <?php if (empty($recentDesigns)): ?>
        <div class="empty-msg"><i class="fal fa-vector-square" style="font-size:26px;display:block;margin-bottom:8px"></i>No structural design records yet</div>
      <?php else: ?>
        <div style="overflow-x:auto">
          <table class="sd-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Project</th>
                <th>Designer</th>
                <th>Type</th>
                <th>Company</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentDesigns as $idx => $sd): ?>
                <tr>
                  <td style="color:var(--text-muted);font-size:11px"><?= $idx + 1 ?></td>
                  <td>
                    <div style="font-weight:700;font-size:12.5px"><?= htmlspecialchars($sd['project_name'] ?? 'N/A') ?></div>
                    <?php if (!empty($sd['location_name'])): ?>
                      <div style="font-size:10.5px;color:var(--text-muted)"><i class="fal fa-map-marker-alt me-1"></i><?= htmlspecialchars($sd['location_name']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td style="font-size:12.5px;font-weight:600"><?= htmlspecialchars($sd['designer_name'] ?? '—') ?></td>
                  <td>
                    <?php if (($sd['designer_type'] ?? '') === 'internal'): ?>
                      <span style="font-size:10.5px;font-weight:700;padding:2px 9px;border-radius:20px;background:rgba(59,130,246,.1);color:var(--sd-blue)">Internal</span>
                    <?php else: ?>
                      <span style="font-size:10.5px;font-weight:700;padding:2px 9px;border-radius:20px;background:rgba(245,158,11,.1);color:var(--sd-amber)">External</span>
                    <?php endif; ?>
                  </td>
                  <td style="font-size:11.5px;color:var(--text-muted)"><?= htmlspecialchars($sd['company_name'] ?? '—') ?></td>
                  <td>
                    <a href="<?= $base ?>/structure-design-edit?id=<?= $sd['id'] ?>" style="font-size:11px;color:var(--sd-primary);text-decoration:none"><i class="fal fa-edit me-1"></i>Edit</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    
    <!-- ═══ Projects Needing Structural Design ═══ -->
    <?php if (!empty($projectsNeedingDesign)): ?>
      <div class="sd-panel">
        <div class="sd-panel-title">
          <div class="sd-dot" style="background:var(--sd-rose)"></div>
          Projects Without Structural Design
          <span style="margin-left:auto;font-size:10px;color:var(--sd-rose);font-weight:700;background:rgba(244,63,94,.1);padding:2px 10px;border-radius:12px">
            <?= count($projectsNeedingDesign) ?> pending
          </span>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px">
          <?php foreach ($projectsNeedingDesign as $proj): ?>
            <div class="sd-design-card">
              <div class="sd-avatar" style="background:rgba(244,63,94,.1);color:var(--sd-rose)">
                <i class="fal fa-building"></i>
              </div>
              <div style="min-width:0;flex:1">
                <div style="font-size:12.5px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($proj['name']) ?></div>
                <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($proj['location_name'] ?: 'No location') ?></div>
                <div style="font-size:10px;margin-top:3px">
                  <span style="background:rgba(244,63,94,.1);color:var(--sd-rose);padding:1px 7px;border-radius:10px;font-weight:700">
                    <?= htmlspecialchars($proj['project_status'] ?? 'Active') ?>
                  </span>
                </div>
              </div>
              <a href="<?= $base ?>/structure-design-create" style="margin-left:auto;flex-shrink:0;font-size:11px;color:var(--sd-primary);text-decoration:none" title="Add Design">
                <i class="fal fa-plus-circle"></i>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Right column -->
  <div class="col-lg-4">

    <!-- Designer Type Breakdown -->
    <div class="sd-panel">
      <div class="sd-panel-title">
        <div class="sd-dot" style="background:var(--sd-indigo)"></div>
        Designer Type Breakdown
      </div>
      <?php if (empty($byDesignerType)): ?>
        <div class="empty-msg">No data yet</div>
      <?php else: ?>
        <?php
        $typeColors = ['internal' => '#3b82f6', 'external' => '#f59e0b'];
        $typeLabels = ['internal' => 'Internal', 'external' => 'External / Firm'];
        $typeIcons  = ['internal' => 'fa-user-hard-hat', 'external' => 'fa-building-columns'];
        $maxCnt = max(array_column($byDesignerType, 'cnt') ?: [1]);
        if ($maxCnt < 1) $maxCnt = 1;
        ?>
        <?php foreach ($byDesignerType as $bt):
          $clr = $typeColors[$bt['designer_type']] ?? '#8b5cf6';
          $lbl = $typeLabels[$bt['designer_type']] ?? ucfirst($bt['designer_type']);
          $ico = $typeIcons[$bt['designer_type']] ?? 'fa-circle';
          $pct = round($bt['cnt'] / $maxCnt * 100);
        ?>
          <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid rgba(0,0,0,.06)">
            <div style="width:32px;height:32px;border-radius:8px;background:<?= $clr ?>22;color:<?= $clr ?>;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0">
              <i class="fal <?= $ico ?>"></i>
            </div>
            <div style="flex:1;min-width:0">
              <div style="font-size:11.5px;font-weight:700;color:<?= $clr ?>"><?= $lbl ?></div>
              <div style="height:6px;border-radius:4px;background:rgba(0,0,0,.07);margin-top:4px;overflow:hidden">
                <div style="height:100%;width:<?= $pct ?>%;background:<?= $clr ?>;border-radius:4px"></div>
              </div>
            </div>
            <div style="font-size:16px;font-weight:900;color:<?= $clr ?>;min-width:28px;text-align:right"><?= $bt['cnt'] ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="sd-panel">
      <div class="sd-panel-title">
        <div class="sd-dot" style="background:var(--sd-teal)"></div>
        Quick Actions
      </div>
      <a href="<?= $base ?>/structure-design" class="qa-link" style="color:var(--sd-primary)">
        <div class="qa-link-icon" style="background:rgba(25,135,84,.12)"><i class="fal fa-vector-square"></i></div>
        <span>View All Designs</span>
      </a>
      <a href="<?= $base ?>/structure-design-create" class="qa-link" style="color:var(--sd-green)">
        <div class="qa-link-icon" style="background:rgba(16,185,129,.12)"><i class="fal fa-plus-circle"></i></div>
        <span>Add New Structural Design</span>
      </a>
      <a href="<?= $base ?>/con-projects" class="qa-link" style="color:var(--sd-blue)">
        <div class="qa-link-icon" style="background:rgba(59,130,246,.12)"><i class="fal fa-folder-open"></i></div>
        <span>All Projects</span>
      </a>
    </div>


  </div>

</div>



<div style="height:30px"></div>
<?php
$addPage = true;
$show_breadcrumb = false;
$base = $GLOBALS['base_url'];

$totalStudies        = $totalStudies        ?? 0;
$totalProjects       = $totalProjects       ?? 0;
$projectsWithStudy   = $projectsWithStudy   ?? 0;
$projectsNoStudy     = $projectsNoStudy     ?? 0;
$recentStudies       = $recentStudies       ?? [];
$pendingStudyProjects = $pendingStudyProjects ?? [];

$coveragePct = $totalProjects > 0 ? round($projectsWithStudy / $totalProjects * 100, 1) : 0;
?>
<style>
  :root {
    --lm-primary: #198754;
    --lm-dark: #0f5132;
    --lm-light: #20a86a;
    --lm-blue: #3b82f6;
    --lm-green: #10b981;
    --lm-amber: #f59e0b;
    --lm-red: #ef4444;
    --lm-teal: #14b8a6;
  }

  .lm-hero {
    background: linear-gradient(135deg, #0f5132 0%, #198754 55%, #20a86a 100%);
    border-radius: 18px;
    padding: 28px 32px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
  }

  .lm-hero::before {
    content: '';
    position: absolute;
    width: 260px;
    height: 260px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .04);
    top: -80px;
    right: -60px;
  }

  .lm-hero::after {
    content: '';
    position: absolute;
    width: 160px;
    height: 160px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .03);
    bottom: -50px;
    left: -30px;
  }

  .lm-role-badge {
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

  .lm-hero-title {
    font-size: 28px;
    font-weight: 900;
    color: #fff;
    margin: 0 0 6px;
  }

  .lm-hero-sub {
    font-size: 13px;
    color: rgba(255, 255, 255, .7);
    margin: 0;
  }

  .lm-btn-white {
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

  .lm-btn-white:hover {
    background: rgba(255, 255, 255, .25);
    color: #fff;
    text-decoration: none;
  }

  .lm-hero-actions {
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

  .kpi-badge {
    position: absolute;
    top: 14px;
    right: 14px;
    font-size: 9px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 20px;
  }

  /* Panel */
  .lm-panel {
    border-radius: 14px;
    padding: 22px;
    border: 1px solid rgba(0, 0, 0, .08);
    background: var(--card-bg, #fff);
    margin-bottom: 22px;
  }

  .lm-panel-title {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .lm-dot {
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

  /* Coverage Donut */
  .donut-ring {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    margin: 0 auto 16px;
  }

  .donut-inner {
    position: absolute;
    z-index: 2;
    text-align: center;
  }

  .donut-pct {
    font-size: 26px;
    font-weight: 900;
    line-height: 1;
  }

  .donut-sub {
    font-size: 11px;
    color: var(--text-muted);
  }

  .donut-legend {
    display: flex;
    gap: 20px;
    justify-content: center;
    flex-wrap: wrap;
  }

  .dleg-item {
    text-align: center;
  }

  .dleg-val {
    font-size: 18px;
    font-weight: 800;
  }

  .dleg-label {
    font-size: 10px;
    color: var(--text-muted);
  }

  /* Study card */
  .study-card {
    border: 1px solid rgba(0, 0, 0, .07);
    border-radius: 12px;
    padding: 14px;
    margin-bottom: 10px;
    transition: box-shadow .15s;
  }

  .study-card:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, .1);
  }

  .study-card:last-child {
    margin-bottom: 0;
  }

  .study-proj {
    font-size: 13px;
    font-weight: 700;
  }

  .study-meta {
    font-size: 11px;
    color: var(--text-muted);
    margin-top: 4px;
  }

  .pending-row {
    padding: 11px 0;
    border-bottom: 1px solid rgba(0, 0, 0, .06);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .pending-row:last-child {
    border-bottom: none;
  }

  .urgent-badge {
    background: rgba(239, 68, 68, .1);
    color: #ef4444;
    font-size: 10px;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 20px;
  }
  html { scroll-behavior: smooth; }
  .kpi-card { cursor: pointer; }
  .sec-anchor { scroll-margin-top: 80px; }
</style>

<!-- ═════ HERO ═════ -->
<div class="lm-hero" style="position:relative;z-index:1">
  <div style="position:relative;z-index:2;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px">
    <div>
      <div class="lm-role-badge"><i class="fa-solid fa-map-location-dot"></i> Land Manager</div>
      <h4 class="lm-hero-title">Land Study Dashboard</h4>
      <p class="lm-hero-sub">Land visibility & feasibility overview · <?= date('d M Y') ?></p>
    </div>
  </div>
  <div class="lm-hero-actions">
    <a href="<?= $base ?>/con-land-visibility-studies" class="lm-btn-white"><i class="fal fa-map"></i> Land Studies</a>
    <a href="<?= $base ?>/con-projects" class="lm-btn-white"><i class="fal fa-building"></i> All Projects</a>
    <a href="<?= $base ?>/con-land-visibility-studies/create" class="lm-btn-white"><i class="fal fa-plus"></i> Add Study</a>
  </div>
</div>

<!-- ═════ KPI GRID ═════ -->
<div class="kpi-grid-4">

  <!-- Land Studies → scroll to recent studies -->
  <a href="#sec-studies" class="kpi-card">
    <div class="kpi-accent" style="background:#b45309"></div>
    <div class="kpi-icon" style="background:rgba(180,83,9,.12);color:#b45309"><i class="fal fa-map"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Land Studies</div>
    <div class="kpi-value" style="color:#b45309"><?= $totalStudies ?></div>
    <div class="kpi-sub">Completed land studies</div>
    <span class="kpi-badge" style="background:rgba(180,83,9,.1);color:#b45309"><i class="fal fa-arrow-down me-1"></i>View</span>
  </a>

  <!-- Total Projects → scroll to pending list -->
  <a href="#sec-pending" class="kpi-card">
    <div class="kpi-accent" style="background:var(--lm-blue)"></div>
    <div class="kpi-icon" style="background:rgba(59,130,246,.12);color:var(--lm-blue)"><i class="fal fa-building"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Total Projects</div>
    <div class="kpi-value" style="color:var(--lm-blue)"><?= $totalProjects ?></div>
    <div class="kpi-sub">Active construction projects</div>
    <span class="kpi-badge" style="background:rgba(59,130,246,.1);color:var(--lm-blue)"><i class="fal fa-arrow-down me-1"></i>View</span>
  </a>

  <!-- Studies Completed → scroll to recent studies -->
  <a href="#sec-studies" class="kpi-card">
    <div class="kpi-accent" style="background:var(--lm-green)"></div>
    <div class="kpi-icon" style="background:rgba(16,185,129,.12);color:var(--lm-green)"><i class="fal fa-check-circle"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Studies Completed</div>
    <div class="kpi-value" style="color:var(--lm-green)"><?= $projectsWithStudy ?></div>
    <div class="kpi-sub">Projects with land study</div>
    <span class="kpi-badge" style="background:rgba(16,185,129,.1);color:var(--lm-green)"><i class="fal fa-arrow-down me-1"></i>View</span>
  </a>

  <!-- Pending Studies → scroll to awaiting list -->
  <a href="#sec-pending" class="kpi-card">
    <div class="kpi-accent" style="background:var(--lm-red)"></div>
    <div class="kpi-icon" style="background:rgba(239,68,68,.12);color:var(--lm-red)"><i class="fal fa-exclamation-circle"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Pending Studies</div>
    <div class="kpi-value" style="color:var(--lm-red)"><?= $projectsNoStudy ?></div>
    <div class="kpi-sub">Projects without study</div>
    <?php if ($projectsNoStudy > 0): ?>
      <span class="kpi-badge" style="background:rgba(239,68,68,.12);color:var(--lm-red)"><i class="fas fa-exclamation-triangle me-1"></i>PENDING</span>
    <?php endif; ?>
  </a>

</div>

<!-- ═════ ROW 2 ═════ -->
<div class="row">

  <!-- Coverage Donut -->
  <div class="col-lg-4">
    <div class="lm-panel text-center">
      <div class="lm-panel-title justify-content-center">
        <div class="lm-dot" style="background:#b45309"></div>
        Study Coverage
      </div>
      <?php
      $deg = round($coveragePct * 3.6);
      $clr  = $coveragePct >= 80 ? '#10b981' : ($coveragePct >= 50 ? '#f59e0b' : '#ef4444');
      ?>
      <div class="donut-ring" style="background:conic-gradient(<?= $clr ?> 0% <?= $deg ?>deg, rgba(0,0,0,.08) <?= $deg ?>deg 360deg)">
        <div style="width:100px;height:100px;border-radius:50%;position:absolute;background:var(--card-bg,#fff)"></div>
        <div class="donut-inner">
          <div class="donut-pct" style="color:<?= $clr ?>"><?= $coveragePct ?>%</div>
          <div class="donut-sub">Covered</div>
        </div>
      </div>
      <div class="donut-legend">
        <div class="dleg-item">
          <div class="dleg-val" style="color:var(--lm-green)"><?= $projectsWithStudy ?></div>
          <div class="dleg-label">Completed</div>
        </div>
        <div class="dleg-item">
          <div class="dleg-val" style="color:var(--lm-red)"><?= $projectsNoStudy ?></div>
          <div class="dleg-label">Pending</div>
        </div>
        <div class="dleg-item">
          <div class="dleg-val"><?= $totalProjects ?></div>
          <div class="dleg-label">Total</div>
        </div>
      </div>
    </div>

    <!-- Projects awaiting study -->
    <div id="sec-pending" class="lm-panel sec-anchor">
      <div class="lm-panel-title">
        <div class="lm-dot" style="background:var(--lm-red)"></div>
        Awaiting Land Study
      </div>
      <?php if (empty($pendingStudyProjects)): ?>
        <div class="empty-msg" style="color:var(--lm-green)"><i class="fal fa-check-circle fa-2x" style="display:block;margin-bottom:8px"></i>All projects covered!</div>
      <?php else: ?>
        <?php foreach ($pendingStudyProjects as $p): ?>
          <div class="pending-row">
            <div>
              <div style="font-size:12.5px;font-weight:700"><?= htmlspecialchars($p['name']) ?></div>
              <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($p['location_name'] ?: 'Location N/A') ?></div>
            </div>
            <span class="urgent-badge">No Study</span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent Studies -->
  <div class="col-lg-8">
    <div id="sec-studies" class="lm-panel sec-anchor">
      <div class="lm-panel-title">
        <div class="lm-dot" style="background:var(--lm-teal)"></div>
        Recent Land Studies
        <a href="<?= $base ?>/con-land-visibility-studies" style="font-size:10px;color:var(--lm-blue);margin-left:auto;text-decoration:none">View All <i class="fal fa-external-link ms-1"></i></a>
      </div>

      <?php if (empty($recentStudies)): ?>
        <div class="empty-msg">No land studies recorded yet</div>
      <?php else: ?>
        <div class="row">
          <?php foreach ($recentStudies as $st): ?>
            <div class="col-md-6">
              <div class="study-card">
                <div class="study-proj"><?= htmlspecialchars($st['project_name'] ?? 'N/A') ?></div>
                <div class="study-meta">
                  <i class="fal fa-map-marker-alt me-1"></i><?= htmlspecialchars($st['location_name'] ?: 'N/A') ?>
                </div>
                <?php if (!empty($st['study_date'])): ?>
                  <div class="study-meta mt-1">
                    <i class="fal fa-calendar me-1"></i><?= date('d M Y', strtotime($st['study_date'])) ?>
                  </div>
                <?php endif; ?>
                <?php if (!empty($st['feasibility_status'])): ?>
                  <div class="mt-2">
                    <?php $fclr = ($st['feasibility_status'] === 'Feasible') ? 'rgba(16,185,129,.12)' : 'rgba(245,158,11,.12)';
                    $ftxt = ($st['feasibility_status'] === 'Feasible') ? '#10b981' : '#f59e0b'; ?>
                    <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;background:<?= $fclr ?>;color:<?= $ftxt ?>">
                      <?= htmlspecialchars($st['feasibility_status']) ?>
                    </span>
                  </div>
                <?php endif; ?>
                <?php if (!empty($st['document_link'])): ?>
                  <div class="mt-2">
                    <a href="<?= htmlspecialchars($st['document_link']) ?>" target="_blank" style="font-size:11px;color:var(--lm-blue);text-decoration:none">
                      <i class="fal fa-file-alt me-1"></i>View Document
                    </a>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div style="text-align:center;margin-top:16px;padding-top:16px;border-top:1px solid rgba(0,0,0,.07)">
          <a href="<?= $base ?>/con-land-visibility-studies/create" class="btn btn-sm" style="background:linear-gradient(135deg,#0f5132,#198754);color:#fff;border-radius:10px;font-size:12px;font-weight:700;padding:8px 20px">
            <i class="fal fa-plus me-2"></i>Add New Land Study
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>
<div style="height:30px"></div>
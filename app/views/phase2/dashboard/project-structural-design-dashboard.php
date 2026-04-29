<?php
$addPage = true;
$show_breadcrumb = false;
$base = $GLOBALS['base_url'];

$isAdmin               = $isAdmin               ?? false;
$myProjectsCount       = $myProjectsCount       ?? 0;
$totalDesigns          = $totalDesigns          ?? 0;
$projectsWithDesign    = $projectsWithDesign    ?? 0;
$projectsNoDesign      = $projectsNoDesign      ?? 0;
$internalDesigners     = $internalDesigners     ?? 0;
$externalDesigners     = $externalDesigners     ?? 0;
$recentDesigns         = $recentDesigns         ?? [];
$myProjects            = $myProjects            ?? [];
$projectsNeedingDesign = $projectsNeedingDesign ?? [];

$coveragePct = $myProjectsCount > 0 ? round($projectsWithDesign / $myProjectsCount * 100, 1) : 0;
$userName    = $_SESSION['user']['full_name'] ?? 'Structural Engineer';
?>
<style>
  :root {
    --sd-primary: #7c3aed;
    --sd-dark:    #4c1d95;
    --sd-light:   #a78bfa;
    --sd-blue:    #3b82f6;
    --sd-green:   #10b981;
    --sd-amber:   #f59e0b;
    --sd-red:     #ef4444;
    --sd-teal:    #14b8a6;
  }

  /* ── Hero ── */
  .sd-hero {
    background: linear-gradient(135deg, #4c1d95 0%, #7c3aed 55%, #a78bfa 100%);
    border-radius: 18px;
    padding: 28px 32px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
  }
  .sd-hero::before {
    content: '';
    position: absolute;
    width: 280px; height: 280px;
    border-radius: 50%;
    background: rgba(255,255,255,.04);
    top: -90px; right: -60px;
  }
  .sd-hero::after {
    content: '';
    position: absolute;
    width: 160px; height: 160px;
    border-radius: 50%;
    background: rgba(255,255,255,.03);
    bottom: -50px; left: -30px;
  }
  .sd-role-badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.2);
    padding: 5px 14px; border-radius: 20px;
    font-size: 11px; font-weight: 700;
    letter-spacing: 1.5px; text-transform: uppercase;
    color: #fff; margin-bottom: 14px;
  }
  .sd-hero-title { font-size: 27px; font-weight: 900; color: #fff; margin: 0 0 5px; }
  .sd-hero-sub   { font-size: 13px; color: rgba(255,255,255,.7); margin: 0; }
  .sd-btn-white {
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.3);
    color: #fff; border-radius: 10px;
    padding: 8px 18px; font-size: 12px; font-weight: 700;
    text-decoration: none;
    display: inline-flex; align-items: center; gap: 6px;
    transition: background .18s;
  }
  .sd-btn-white:hover { background: rgba(255,255,255,.25); color: #fff; text-decoration: none; }
  .sd-hero-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 16px; }

  /* ── KPI grid ── */
  .kpi-grid-4 {
    display: grid;
    grid-template-columns: repeat(4,1fr);
    gap: 14px; margin-bottom: 22px;
  }
  @media(max-width:992px){ .kpi-grid-4 { grid-template-columns: repeat(2,1fr); } }
  @media(max-width:480px){ .kpi-grid-4 { grid-template-columns: 1fr; } }

  .kpi-card {
    border-radius: 14px; padding: 18px 20px;
    position: relative; overflow: hidden;
    border: 1px solid rgba(0,0,0,.08);
    text-decoration: none !important; display: block;
    transition: transform .18s, box-shadow .18s;
    background: var(--card-bg, #fff);
  }
  .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 28px rgba(0,0,0,.12); }
  .kpi-accent {
    position: absolute; left: 0; top: 0;
    width: 4px; height: 100%;
    border-radius: 14px 0 0 14px;
  }
  .kpi-icon {
    width: 42px; height: 42px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; margin-bottom: 12px;
  }
  .kpi-value { font-size: 28px; font-weight: 900; line-height: 1; margin-bottom: 4px; }
  .kpi-label {
    font-size: 10.5px; font-weight: 700;
    letter-spacing: 1px; text-transform: uppercase; margin-bottom: 3px;
  }
  .kpi-sub   { font-size: 11px; color: var(--text-muted); }
  .kpi-badge {
    position: absolute; top: 14px; right: 14px;
    font-size: 9px; font-weight: 700;
    padding: 3px 8px; border-radius: 20px;
  }

  /* ── Panel ── */
  .sd-panel {
    border-radius: 14px; padding: 22px;
    border: 1px solid rgba(0,0,0,.08);
    background: var(--card-bg, #fff);
    margin-bottom: 22px;
  }
  .sd-panel-title {
    font-size: 11px; font-weight: 700;
    letter-spacing: 1.5px; text-transform: uppercase;
    margin-bottom: 16px;
    display: flex; align-items: center; gap: 8px;
  }
  .sd-dot { width: 8px; height: 8px; border-radius: 50%; }
  .empty-msg { text-align: center; padding: 28px 0; color: var(--text-muted); font-size: 12px; }

  /* ── Donut ── */
  .donut-ring {
    width: 140px; height: 140px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    position: relative; margin: 0 auto 16px;
  }
  .donut-inner { position: absolute; z-index: 2; text-align: center; }
  .donut-pct   { font-size: 26px; font-weight: 900; line-height: 1; }
  .donut-sub   { font-size: 11px; color: var(--text-muted); }
  .donut-legend { display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; }
  .dleg-item   { text-align: center; }
  .dleg-val    { font-size: 18px; font-weight: 800; }
  .dleg-label  { font-size: 10px; color: var(--text-muted); }

  /* ── Project rows ── */
  .proj-row {
    padding: 11px 0;
    border-bottom: 1px solid rgba(0,0,0,.06);
    display: flex; justify-content: space-between; align-items: center;
  }
  .proj-row:last-child { border-bottom: none; }
  .proj-name   { font-size: 12.5px; font-weight: 700; }
  .proj-meta   { font-size: 11px; color: var(--text-muted); margin-top: 3px; }
  .no-design-badge {
    background: rgba(239,68,68,.1); color: #ef4444;
    font-size: 10px; font-weight: 700;
    padding: 3px 9px; border-radius: 20px;
  }
  .has-design-badge {
    background: rgba(16,185,129,.1); color: #10b981;
    font-size: 10px; font-weight: 700;
    padding: 3px 9px; border-radius: 20px;
  }

  /* ── Design cards ── */
  .design-card {
    border: 1px solid rgba(0,0,0,.07);
    border-radius: 12px; padding: 14px;
    margin-bottom: 10px;
    transition: box-shadow .15s;
  }
  .design-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.1); }
  .design-card:last-child { margin-bottom: 0; }
  .design-proj  { font-size: 13px; font-weight: 700; }
  .design-meta  { font-size: 11px; color: var(--text-muted); margin-top: 4px; }

  /* ── Stacked bar ── */
  .type-bar-wrap { background: rgba(0,0,0,.07); border-radius: 8px; height: 10px; overflow: hidden; margin-top: 8px; }
  .type-bar-fill { height: 100%; border-radius: 8px; transition: width .5s; }

  /* ── Smooth scroll ── */
  html { scroll-behavior: smooth; }
  .kpi-card { cursor: pointer; }
  .section-anchor { scroll-margin-top: 80px; }
</style>

<!-- ═════ HERO ═════ -->
<div class="sd-hero" style="position:relative;z-index:1">
  <div style="position:relative;z-index:2;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px">
    <div>
      <div class="sd-role-badge"><i class="fa-solid fa-drafting-compass"></i> Structural Engineer</div>
      <h4 class="sd-hero-title">Project Structure Design Dashboard</h4>
      <p class="sd-hero-sub">
        <?= $isAdmin ? 'All projects — admin view' : 'Projects assigned to: <strong style="color:#fff">' . htmlspecialchars($userName) . '</strong>' ?>
        &nbsp;·&nbsp; <?= date('d M Y') ?>
      </p>
    </div>
  </div>
  <div class="sd-hero-actions">
    <a href="<?= $base ?>/structure-design" class="sd-btn-white"><i class="fal fa-list"></i> All Designs</a>
    <a href="<?= $base ?>/structure-design/create" class="sd-btn-white"><i class="fal fa-plus"></i> Add Design</a>
    <a href="<?= $base ?>/con-projects" class="sd-btn-white"><i class="fal fa-building"></i> Projects</a>
  </div>
</div>

<!-- ═════ KPI GRID ═════ -->
<div class="kpi-grid-4">

  <!-- My Projects → scroll to projects table -->
  <a href="#sec-projects" class="kpi-card">
    <div class="kpi-accent" style="background:var(--sd-primary)"></div>
    <div class="kpi-icon" style="background:rgba(124,58,237,.12);color:var(--sd-primary)"><i class="fal fa-building"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)"><?= $isAdmin ? 'Total Projects' : 'My Projects' ?></div>
    <div class="kpi-value" style="color:var(--sd-primary)"><?= $myProjectsCount ?></div>
    <div class="kpi-sub"><?= $isAdmin ? 'All construction projects' : 'Projects assigned to me' ?></div>
    <span class="kpi-badge" style="background:rgba(124,58,237,.1);color:var(--sd-primary)"><i class="fal fa-arrow-down me-1"></i>View</span>
  </a>

  <!-- Designs Done → scroll to designs table -->
  <a href="#sec-designs" class="kpi-card">
    <div class="kpi-accent" style="background:var(--sd-green)"></div>
    <div class="kpi-icon" style="background:rgba(16,185,129,.12);color:var(--sd-green)"><i class="fal fa-check-circle"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Design Submitted</div>
    <div class="kpi-value" style="color:var(--sd-green)"><?= $projectsWithDesign ?></div>
    <div class="kpi-sub">Projects with structural design</div>
    <span class="kpi-badge" style="background:rgba(16,185,129,.1);color:var(--sd-green)"><i class="fal fa-arrow-down me-1"></i>View</span>
  </a>

  <!-- Pending Design → scroll to pending section -->
  <a href="#sec-pending" class="kpi-card">
    <div class="kpi-accent" style="background:var(--sd-red)"></div>
    <div class="kpi-icon" style="background:rgba(239,68,68,.12);color:var(--sd-red)"><i class="fal fa-exclamation-circle"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Pending Design</div>
    <div class="kpi-value" style="color:var(--sd-red)"><?= $projectsNoDesign ?></div>
    <div class="kpi-sub">Projects without design</div>
    <?php if ($projectsNoDesign > 0): ?>
      <span class="kpi-badge" style="background:rgba(239,68,68,.1);color:var(--sd-red)"><i class="fas fa-exclamation-triangle me-1"></i>ACTION</span>
    <?php endif; ?>
  </a>

  <!-- Total Records → scroll to designs table -->
  <a href="#sec-all-table" class="kpi-card">
    <div class="kpi-accent" style="background:var(--sd-blue)"></div>
    <div class="kpi-icon" style="background:rgba(59,130,246,.12);color:var(--sd-blue)"><i class="fal fa-file-alt"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Total Records</div>
    <div class="kpi-value" style="color:var(--sd-blue)"><?= $totalDesigns ?></div>
    <div class="kpi-sub">Total design records</div>
    <span class="kpi-badge" style="background:rgba(59,130,246,.1);color:var(--sd-blue)"><i class="fal fa-arrow-down me-1"></i>View</span>
  </a>

</div>

<!-- ═════ ROW 2 ═════ -->
<div class="row">

  <!-- Coverage + Type Breakdown -->
  <div class="col-lg-4">

    <!-- Donut -->
    <div class="sd-panel text-center">
      <div class="sd-panel-title justify-content-center">
        <div class="sd-dot" style="background:var(--sd-primary)"></div>
        Design Coverage
      </div>
      <?php
        $deg = round($coveragePct * 3.6);
        $clr = $coveragePct >= 80 ? '#10b981' : ($coveragePct >= 50 ? '#f59e0b' : '#ef4444');
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
          <div class="dleg-val" style="color:var(--sd-green)"><?= $projectsWithDesign ?></div>
          <div class="dleg-label">Done</div>
        </div>
        <div class="dleg-item">
          <div class="dleg-val" style="color:var(--sd-red)"><?= $projectsNoDesign ?></div>
          <div class="dleg-label">Pending</div>
        </div>
        <div class="dleg-item">
          <div class="dleg-val"><?= $myProjectsCount ?></div>
          <div class="dleg-label">Total</div>
        </div>
      </div>
    </div>

    <!-- Designer Type -->
    <div class="sd-panel">
      <div class="sd-panel-title">
        <div class="sd-dot" style="background:var(--sd-amber)"></div>
        Designer Type
      </div>
      <?php $totalType = $internalDesigners + $externalDesigners; ?>
      <div style="margin-bottom:12px">
        <div style="display:flex;justify-content:space-between;font-size:12px;font-weight:600;margin-bottom:4px">
          <span><i class="fal fa-user me-1" style="color:var(--sd-primary)"></i>Internal</span>
          <span style="color:var(--sd-primary)"><?= $internalDesigners ?></span>
        </div>
        <div class="type-bar-wrap">
          <div class="type-bar-fill" style="width:<?= $totalType > 0 ? round($internalDesigners/$totalType*100) : 0 ?>%;background:var(--sd-primary)"></div>
        </div>
      </div>
      <div>
        <div style="display:flex;justify-content:space-between;font-size:12px;font-weight:600;margin-bottom:4px">
          <span><i class="fal fa-building me-1" style="color:var(--sd-teal)"></i>External / Firm</span>
          <span style="color:var(--sd-teal)"><?= $externalDesigners ?></span>
        </div>
        <div class="type-bar-wrap">
          <div class="type-bar-fill" style="width:<?= $totalType > 0 ? round($externalDesigners/$totalType*100) : 0 ?>%;background:var(--sd-teal)"></div>
        </div>
      </div>
    </div>

    <!-- Projects awaiting design -->
    <div id="sec-pending" class="sd-panel section-anchor">
      <div class="sd-panel-title">
        <div class="sd-dot" style="background:var(--sd-red)"></div>
        Awaiting Design
      </div>
      <?php if (empty($projectsNeedingDesign)): ?>
        <div class="empty-msg" style="color:var(--sd-green)">
          <i class="fal fa-check-circle fa-2x" style="display:block;margin-bottom:8px"></i>
          All projects have a design!
        </div>
      <?php else: ?>
        <?php foreach ($projectsNeedingDesign as $p): ?>
          <div class="proj-row">
            <div>
              <div class="proj-name"><?= htmlspecialchars($p['name']) ?></div>
              <div class="proj-meta"><i class="fal fa-map-marker-alt me-1"></i><?= htmlspecialchars($p['location_name'] ?: 'N/A') ?></div>
            </div>
            <a href="<?= $base ?>/structure-design/create" class="no-design-badge" style="text-decoration:none">Add</a>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>

  <!-- Right: Recent Designs + Project List -->
  <div class="col-lg-8">

    <!-- Recent Designs -->
    <div id="sec-designs" class="sd-panel section-anchor">
      <div class="sd-panel-title">
        <div class="sd-dot" style="background:var(--sd-teal)"></div>
        Recent Structure Designs
        <a href="#sec-all-table" style="font-size:10px;color:var(--sd-blue);margin-left:auto;text-decoration:none">
          View All <i class="fal fa-arrow-down ms-1"></i>
        </a>
      </div>

      <?php if (empty($recentDesigns)): ?>
        <div class="empty-msg">No structural design records yet</div>
      <?php else: ?>
        <div class="row">
          <?php foreach ($recentDesigns as $sd): ?>
            <div class="col-md-6">
              <div class="design-card">
                <div class="design-proj"><?= htmlspecialchars($sd['project_name'] ?? 'N/A') ?></div>
                <div class="design-meta">
                  <i class="fal fa-map-marker-alt me-1"></i><?= htmlspecialchars($sd['location_name'] ?: 'N/A') ?>
                </div>
                <div style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap;align-items:center">
                  <?php if (($sd['designer_type'] ?? '') === 'internal'): ?>
                    <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;background:rgba(124,58,237,.1);color:var(--sd-primary)">
                      <i class="fas fa-user me-1"></i>Internal
                    </span>
                  <?php else: ?>
                    <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;background:rgba(20,184,166,.1);color:var(--sd-teal)">
                      <i class="fas fa-building me-1"></i>External
                    </span>
                  <?php endif; ?>
                  <?php if (!empty($sd['designer_name'])): ?>
                    <span style="font-size:11px;color:var(--text-muted)">
                      <i class="fal fa-user-hard-hat me-1"></i><?= htmlspecialchars($sd['designer_name']) ?>
                    </span>
                  <?php endif; ?>
                </div>
                <?php if (!empty($sd['design_link'])): ?>
                  <div style="margin-top:8px">
                    <a href="<?= htmlspecialchars($sd['design_link']) ?>" target="_blank"
                       style="font-size:11px;color:var(--sd-blue);text-decoration:none">
                      <i class="fal fa-file-alt me-1"></i>View Design
                    </a>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div style="text-align:center;margin-top:16px;padding-top:16px;border-top:1px solid rgba(0,0,0,.07)">
          <a href="<?= $base ?>/structure-design/create" class="btn btn-sm"
             style="background:linear-gradient(135deg,#4c1d95,#7c3aed);color:#fff;border-radius:10px;font-size:12px;font-weight:700;padding:8px 20px">
            <i class="fal fa-plus me-2"></i>Add New Structure Design
          </a>
        </div>
      <?php endif; ?>
    </div>

    <!-- All My Projects Table -->
    <div id="sec-projects" class="sd-panel section-anchor">
      <div class="sd-panel-title">
        <div class="sd-dot" style="background:var(--sd-primary)"></div>
        <?= $isAdmin ? 'All Projects' : 'My Assigned Projects' ?>
        <span style="margin-left:auto;font-size:10px;font-weight:700;background:rgba(124,58,237,.1);color:var(--sd-primary);padding:3px 10px;border-radius:20px">
          <?= count($myProjects) ?> Projects
        </span>
      </div>

      <?php if (empty($myProjects)): ?>
        <div class="empty-msg">No projects assigned yet</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table mb-0" style="font-size:12.5px">
            <thead>
              <tr style="font-size:10px;text-transform:uppercase;letter-spacing:.8px;color:var(--text-muted)">
                <th>#</th>
                <th>Project</th>
                <th>Location</th>
                <th>Status</th>
                <th>Design</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($myProjects as $i => $p): ?>
                <tr>
                  <td style="font-weight:600;color:var(--text-muted)"><?= $i + 1 ?></td>
                  <td style="font-weight:700"><?= htmlspecialchars($p['name']) ?></td>
                  <td><?= htmlspecialchars($p['location_name'] ?: '—') ?></td>
                  <td>
                    <?php
                      $st = $p['project_status'] ?? '';
                      $sc = match($st) {
                        'ongoing'   => ['#10b981', 'Ongoing'],
                        'completed' => ['#6366f1', 'Completed'],
                        'planning'  => ['#f59e0b', 'Planning'],
                        'on-hold'   => ['#ef4444', 'On Hold'],
                        default     => ['#94a3b8', ucfirst($st ?: 'N/A')],
                      };
                    ?>
                    <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;background:<?= $sc[0] ?>22;color:<?= $sc[0] ?>">
                      <?= $sc[1] ?>
                    </span>
                  </td>
                  <td>
                    <?php if (!empty($p['design_id'])): ?>
                      <span class="has-design-badge"><i class="fas fa-check me-1"></i><?= ucfirst($p['designer_type'] ?? '') ?></span>
                    <?php else: ?>
                      <span class="no-design-badge">No Design</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($p['design_id'])): ?>
                      <a href="#sec-designs" style="font-size:11px;color:var(--sd-blue);text-decoration:none">
                        <i class="fal fa-eye me-1"></i>View
                      </a>
                    <?php else: ?>
                      <a href="<?= $base ?>/structure-design/create" style="font-size:11px;color:var(--sd-primary);text-decoration:none">
                        <i class="fal fa-plus me-1"></i>Add
                      </a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- ═════ ALL DESIGNS FULL TABLE ═════ -->
    <div id="sec-all-table" class="sd-panel section-anchor">
      <div class="sd-panel-title">
        <div class="sd-dot" style="background:var(--sd-blue)"></div>
        All Structure Design Records
        <span style="margin-left:auto;font-size:10px;font-weight:700;background:rgba(59,130,246,.1);color:var(--sd-blue);padding:3px 10px;border-radius:20px">
          <?= count($recentDesigns) ?> Records
        </span>
      </div>
      <?php if (empty($recentDesigns)): ?>
        <div class="empty-msg">No structural design records yet</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table mb-0" style="font-size:12.5px">
            <thead>
              <tr style="font-size:10px;text-transform:uppercase;letter-spacing:.8px;color:var(--text-muted)">
                <th>#</th>
                <th>Project</th>
                <th>Location</th>
                <th>Designer Type</th>
                <th>Designer Name</th>
                <th>Company</th>
                <th>Design Link</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentDesigns as $i => $sd): ?>
                <tr>
                  <td style="font-weight:600;color:var(--text-muted)"><?= $i + 1 ?></td>
                  <td style="font-weight:700"><?= htmlspecialchars($sd['project_name'] ?? 'N/A') ?></td>
                  <td><?= htmlspecialchars($sd['location_name'] ?: '—') ?></td>
                  <td>
                    <?php if (($sd['designer_type'] ?? '') === 'internal'): ?>
                      <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;background:rgba(124,58,237,.1);color:var(--sd-primary)">
                        <i class="fas fa-user me-1"></i>Internal
                      </span>
                    <?php else: ?>
                      <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;background:rgba(20,184,166,.1);color:var(--sd-teal)">
                        <i class="fas fa-building me-1"></i>External
                      </span>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($sd['designer_name'] ?? '—') ?></td>
                  <td><?= htmlspecialchars($sd['company_name'] ?? '—') ?></td>
                  <td>
                    <?php if (!empty($sd['design_link'])): ?>
                      <a href="<?= htmlspecialchars($sd['design_link']) ?>" target="_blank"
                         style="font-size:11px;color:var(--sd-blue);text-decoration:none">
                        <i class="fal fa-external-link-alt me-1"></i>Open
                      </a>
                    <?php else: ?>
                      <span style="color:var(--text-muted);font-size:11px">—</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div style="text-align:center;margin-top:16px;padding-top:16px;border-top:1px solid rgba(0,0,0,.07)">
          <a href="<?= $base ?>/structure-design/create" class="btn btn-sm"
             style="background:linear-gradient(135deg,#4c1d95,#7c3aed);color:#fff;border-radius:10px;font-size:12px;font-weight:700;padding:8px 20px">
            <i class="fal fa-plus me-2"></i>Add New Structure Design
          </a>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>
<div style="height:30px"></div>
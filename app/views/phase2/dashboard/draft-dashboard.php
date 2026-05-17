<?php
$addPage = true;
$show_breadcrumb = false;
$base = $GLOBALS['base_url'];

$totalDrawings = $totalDrawings ?? 0;
$totalDesignTypes = $totalDesignTypes ?? 0;
$projectsWithDrawings = $projectsWithDrawings ?? 0;
$totalAE = $totalAE ?? 0;
$drawingsByType = $drawingsByType ?? [];
$recentDrawings = $recentDrawings ?? [];
$projectsNeedingDrawings = $projectsNeedingDrawings ?? [];
$projectsWaitingForAE = $projectsWaitingForAE ?? [];

$aeStatusLabel = [
  'not_started' => ['label' => 'Not Started', 'color' => '#ef4444', 'icon' => 'fa-circle-xmark'],
  'draft' => ['label' => 'Draft', 'color' => '#94a3b8', 'icon' => 'fa-file-dashed-line'],
  'partially' => ['label' => 'In Progress', 'color' => '#f59e0b', 'icon' => 'fa-spinner'],
  'fully' => ['label' => 'Complete', 'color' => '#10b981', 'icon' => 'fa-circle-check'],
];
?>
<div class="row">
  <div class="col-12">
    <style>
      :root {
        --dr-primary: #198754;
        --dr-dark: #0f5132;
        --dr-light: #20a86a;
        --dr-blue: #3b82f6;
        --dr-green: #10b981;
        --dr-amber: #f59e0b;
        --dr-red: #ef4444;
        --dr-purple: #8b5cf6;
        --dr-teal: #14b8a6;
        --dr-pink: #ec4899;
      }

      .dr-hero {
        background: linear-gradient(135deg, #0f5132 0%, #198754 55%, #20a86a 100%);
        border-radius: 18px;
        padding: 28px 32px;
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
      }

      .dr-hero::before {
        content: '';
        position: absolute;
        width: 250px;
        height: 250px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .04);
        top: -70px;
        right: -50px;
      }

      .dr-hero::after {
        content: '';
        position: absolute;
        width: 150px;
        height: 150px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .03);
        bottom: -40px;
        left: -20px;
      }

      .dr-role-badge {
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

      .dr-hero-title {
        font-size: 28px;
        font-weight: 900;
        color: #fff;
        margin: 0 0 6px;
      }

      .dr-hero-sub {
        font-size: 13px;
        color: rgba(255, 255, 255, .7);
        margin: 0;
      }

      .dr-btn-white {
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

      .dr-btn-white:hover {
        background: rgba(255, 255, 255, .25);
        color: #fff;
        text-decoration: none;
      }

      .dr-hero-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 16px;
      }

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

      .dr-panel {
        border-radius: 14px;
        padding: 22px;
        border: 1px solid rgba(0, 0, 0, .08);
        background: var(--card-bg, #fff);
        margin-bottom: 22px;
      }

      .dr-panel-title {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .dr-dot {
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

      /* Chart-style type bars */
      .type-bar-row {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 11px;
      }

      .type-bar-row:last-child {
        margin-bottom: 0;
      }

      .type-bar-label {
        font-size: 12px;
        font-weight: 600;
        min-width: 120px;
      }

      .type-bar-track {
        flex: 1;
        height: 8px;
        border-radius: 6px;
        background: rgba(0, 0, 0, .07);
        overflow: hidden;
      }

      .type-bar-fill {
        height: 100%;
        border-radius: 6px;
      }

      .type-bar-count {
        font-size: 12px;
        font-weight: 800;
        min-width: 28px;
        text-align: right;
      }

      /* Drawing cards */
      .draw-card {
        border: 1px solid rgba(0, 0, 0, .07);
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 10px;
        transition: box-shadow .15s;
      }

      .draw-card:hover {
        box-shadow: 0 4px 16px rgba(0, 0, 0, .1);
      }

      .draw-card:last-child {
        margin-bottom: 0;
      }

      .draw-title {
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 4px;
      }

      .draw-meta {
        font-size: 11px;
        color: var(--text-muted);
      }

      /* Need-drawing badge */
      .need-badge {
        background: rgba(239, 68, 68, .1);
        color: #ef4444;
        font-size: 10px;
        font-weight: 700;
        padding: 3px 9px;
        border-radius: 20px;
      }

      html {
        scroll-behavior: smooth;
      }

      .sec-anchor {
        scroll-margin-top: 80px;
      }
    </style>

    <!-- ═════ HERO ═════ -->
    <div class="dr-hero" style="position:relative;z-index:1">
      <div
        style="position:relative;z-index:2;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px">
        <div>
          <div class="dr-role-badge"><i class="fa-solid fa-drafting-compass"></i> Draftsman</div>
          <h4 class="dr-hero-title"> Drafting Dashboard</h4>
          <p class="dr-hero-sub">Real-time design overview · <?= date('d M Y, h:i A') ?></p>
        </div>
      </div>
      <div class="dr-hero-actions">
        <a href="<?= $base ?>/con-project-drawings" class="dr-btn-white"><i class="fal fa-pencil-ruler"></i> All
          Drawings</a>
        <a href="<?= $base ?>/con-design-types" class="dr-btn-white"><i class="fal fa-layer-group"></i> Drawing Types</a>
      </div>
    </div>

    <?php if (!empty($projectsWaitingForAE)): ?>
      <!-- ═════ WORKFLOW GATE ALERT ═════ -->
      <div
        style="background:linear-gradient(135deg,#78350f,#d97706);border-radius:14px;padding:18px 22px;margin-bottom:20px;border:1px solid #fbbf24;">
        <div style="display:flex;align-items:flex-start;gap:14px;">
          <div
            style="width:42px;height:42px;border-radius:12px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:20px;color:#fff;">
            <i class="fal fa-clock"></i>
          </div>
          <div style="flex:1;">
            <div
              style="font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:rgba(255,255,255,.7);font-weight:700;margin-bottom:4px;">
              Workflow Locked</div>
            <div style="font-size:15px;font-weight:800;color:#fff;margin-bottom:6px;">Waiting for Architect &amp;
              Engineering to Complete</div>
            <div style="font-size:12px;color:rgba(255,255,255,.8);margin-bottom:12px;">You cannot upload Project Drawings
              for the following projects until the Architect marks the A&amp;E work as <strong
                style="color:#fde68a;">Fully Complete</strong>.</div>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
              <?php foreach ($projectsWaitingForAE as $wp):
                $aeInfo = $aeStatusLabel[$wp['ae_status']] ?? ['label' => 'Unknown', 'color' => '#ccc', 'icon' => 'fa-question'];
                ?>
                <div
                  style="background:rgba(0,0,0,.2);border:1px solid rgba(255,255,255,.2);border-radius:10px;padding:8px 14px;display:flex;align-items:center;gap:8px;">
                  <i class="fal <?= $aeInfo['icon'] ?>" style="color:<?= $aeInfo['color'] ?>;font-size:13px;"></i>
                  <div>
                    <div style="font-size:12px;font-weight:700;color:#fff;"><?= htmlspecialchars($wp['name']) ?></div>
                    <div style="font-size:10px;color:rgba(255,255,255,.65);">A&amp;E: <?= $aeInfo['label'] ?> &bull;
                      Architect: <?= htmlspecialchars($wp['architect_name']) ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- ═════ KPI GRID ═════ -->
    <div class="kpi-grid-4">

      <!-- Total Drawings → scroll to recent drawings -->
      <a href="#sec-drawings" class="kpi-card">
        <div class="kpi-accent" style="background:var(--dr-purple)"></div>
        <div class="kpi-icon" style="background:rgba(139,92,246,.12);color:var(--dr-purple)"><i
            class="fal fa-pencil-ruler"></i></div>
        <div class="kpi-label" style="color:var(--text-muted)">Total Drawings</div>
        <div class="kpi-value" style="color:var(--dr-purple)"><?= $totalDrawings ?></div>
        <div class="kpi-sub">Uploaded design files</div>
        <span
          style="position:absolute;top:14px;right:14px;font-size:9px;font-weight:700;padding:3px 8px;border-radius:20px;background:rgba(139,92,246,.1);color:var(--dr-purple)"><i
            class="fal fa-arrow-down me-1"></i>View</span>
      </a>

      <!-- Drawing Types → scroll to type bars -->
      <a href="#sec-types" class="kpi-card">
        <div class="kpi-accent" style="background:var(--dr-blue)"></div>
        <div class="kpi-icon" style="background:rgba(59,130,246,.12);color:var(--dr-blue)"><i
            class="fal fa-layer-group"></i></div>
        <div class="kpi-label" style="color:var(--text-muted)">Drawing Types</div>
        <div class="kpi-value" style="color:var(--dr-blue)"><?= $totalDesignTypes ?></div>
        <div class="kpi-sub">Design categories</div>
        <span
          style="position:absolute;top:14px;right:14px;font-size:9px;font-weight:700;padding:3px 8px;border-radius:20px;background:rgba(59,130,246,.1);color:var(--dr-blue)"><i
            class="fal fa-arrow-down me-1"></i>View</span>
      </a>

      <!-- Projects with Drawings → scroll to recent drawings -->
      <a href="#sec-drawings" class="kpi-card">
        <div class="kpi-accent" style="background:var(--dr-green)"></div>
        <div class="kpi-icon" style="background:rgba(16,185,129,.12);color:var(--dr-green)"><i
            class="fal fa-building"></i></div>
        <div class="kpi-label" style="color:var(--text-muted)">Projects w/ Drawings</div>
        <div class="kpi-value" style="color:var(--dr-green)"><?= $projectsWithDrawings ?></div>
        <div class="kpi-sub">Have ≥1 drawing</div>
        <span
          style="position:absolute;top:14px;right:14px;font-size:9px;font-weight:700;padding:3px 8px;border-radius:20px;background:rgba(16,185,129,.1);color:var(--dr-green)"><i
            class="fal fa-arrow-down me-1"></i>View</span>
      </a>

      <!-- Arch & Engineering → scroll to AE table -->
      <a href="#architecture_table" class="kpi-card">
        <div class="kpi-accent" style="background:var(--dr-teal)"></div>
        <div class="kpi-icon" style="background:rgba(20,184,166,.12);color:var(--dr-teal)"><i
            class="fal fa-compass-drafting"></i></div>
        <div class="kpi-label" style="color:var(--text-muted)">Architecture Design Records</div>
        <div class="kpi-value" style="color:var(--dr-teal)"><?= $totalAE ?></div>
        <div class="kpi-sub">A&E studies registered</div>
        <span
          style="position:absolute;top:14px;right:14px;font-size:9px;font-weight:700;padding:3px 8px;border-radius:20px;background:rgba(20,184,166,.1);color:var(--dr-teal)"><i
            class="fal fa-arrow-down me-1"></i>View</span>
      </a>

    </div>

    <!-- ═════ ROW 2 ═════ -->
    <div class="row">

      <!-- Drawings by Type -->
      <div class="col-lg-4">
        <div id="sec-types" class="dr-panel sec-anchor">
          <div class="dr-panel-title">
            <div class="dr-dot" style="background:var(--dr-purple)"></div>
            Drawings by Design Type
          </div>
          <?php
          $maxCount = !empty($drawingsByType) ? max(array_column($drawingsByType, 'count')) : 1;
          $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#14b8a6', '#ec4899', '#f97316'];
          $i = 0;
          ?>
          <?php if (empty($drawingsByType)): ?>
            <div class="empty-msg">No design types yet</div>
          <?php else: ?>
            <?php foreach ($drawingsByType as $dt):
              $pct = $maxCount > 0 ? round($dt['count'] / $maxCount * 100) : 0;
              $clr = $colors[$i % count($colors)];
              $i++;
              ?>
              <div class="type-bar-row">
                <div class="type-bar-label"><?= htmlspecialchars($dt['design_type']) ?></div>
                <div class="type-bar-track">
                  <div class="type-bar-fill" style="width:<?= $pct ?>%;background:<?= $clr ?>"></div>
                </div>
                <div class="type-bar-count" style="color:<?= $clr ?>"><?= $dt['count'] ?></div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Recent Drawings -->
      <div class="col-lg-8">
        <div id="sec-drawings" class="dr-panel sec-anchor">
          <div class="dr-panel-title">
            <div class="dr-dot" style="background:var(--dr-blue)"></div>
            Recent Project Drawings
            <a href="<?= $base ?>/con-project-drawings"
              style="font-size:10px;color:var(--dr-blue);margin-left:auto;text-decoration:none">View All <i
                class="fal fa-external-link ms-1"></i></a>
          </div>
          <?php if (empty($recentDrawings)): ?>
            <div class="empty-msg">No drawings uploaded yet</div>
          <?php else: ?>
            <div class="row">
              <?php foreach ($recentDrawings as $d): ?>
                <div class="col-md-6">
                  <div class="draw-card">
                    <div class="d-flex justify-content-between align-items-start">
                      <div>
                        <div class="draw-title"><?= htmlspecialchars($d['drawing_title'] ?? $d['title'] ?? 'Untitled') ?>
                        </div>
                        <div class="draw-meta"><i
                            class="fal fa-building me-1"></i><?= htmlspecialchars($d['project_name'] ?? 'N/A') ?></div>
                        <div class="draw-meta mt-1"><i
                            class="fal fa-layer-group me-1"></i><?= htmlspecialchars($d['design_type_name'] ?? 'N/A') ?>
                        </div>
                      </div>
                      <div style="flex-shrink:0">
                        <?php if (!empty($d['file_path']) || !empty($d['drawing_link'])):
                          $link = $d['file_path'] ?? $d['drawing_link'];
                          ?>
                          <a href="<?= htmlspecialchars($link) ?>" target="_blank"
                            style="font-size:18px;color:var(--dr-blue);text-decoration:none"><i
                              class="fal fa-file-alt"></i></a>
                        <?php else: ?>
                          <span style="font-size:18px;color:var(--text-muted);opacity:.3"><i
                              class="fal fa-file-slash"></i></span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <!-- Add Drawing CTA -->
            <div style="text-align:center;margin-top:16px;padding-top:16px;border-top:1px solid rgba(0,0,0,.07)">
              <a href="<?= $base ?>/con-project-drawings/create" class="btn btn-sm"
                style="background:linear-gradient(135deg,#0f5132,#198754);color:#fff;border-radius:10px;font-size:12px;font-weight:700;padding:8px 20px">
                <i class="fal fa-plus me-2"></i>Upload New Drawing
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>

  <!-- ═════ ROW 3: Projects Needing Drawings  +  A&E Studies  (side by side) ═════ -->
  <?php $architectEngineeringList = $architectEngineeringList ?? []; ?>
  <div class="row mt-2">

    <!-- LEFT: Projects Needing Drawings (full table view) -->
    <div class="col-lg-6">
      <div class="dr-panel h-100" style="border-top:4px solid var(--dr-red)">
        <div class="dr-panel-title mb-3">
          <div class="dr-dot" style="background:var(--dr-red)"></div>
          <i class="fal fa-exclamation-circle me-2" style="color:var(--dr-red)"></i>
          Projects Needing Drawings
          <?php if (!empty($projectsNeedingDrawings)): ?>
            <span style="margin-left:auto;font-size:10px;background:rgba(239,68,68,.1);color:#ef4444;padding:3px 10px;border-radius:20px;font-weight:700">
              <?= count($projectsNeedingDrawings) ?> Pending
            </span>
          <?php endif; ?>
        </div>
        <?php if (empty($projectsNeedingDrawings)): ?>
          <div class="empty-msg" style="color:var(--dr-green)">
            <i class="fal fa-check-circle fa-2x" style="display:block;margin-bottom:8px"></i>All projects have drawings!
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:13px;">
              <thead style="background:rgba(239,68,68,.04);">
                <tr>
                  <th class="text-uppercase text-muted" style="font-size:10px;letter-spacing:1px;padding:10px 12px;">#</th>
                  <th class="text-uppercase text-muted" style="font-size:10px;letter-spacing:1px;padding:10px 12px;">Project</th>
                  <th class="text-uppercase text-muted" style="font-size:10px;letter-spacing:1px;padding:10px 12px;">Location</th>
                  <th class="text-uppercase text-muted" style="font-size:10px;letter-spacing:1px;padding:10px 12px;">Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($projectsNeedingDrawings as $idx => $p): ?>
                  <tr>
                    <td class="text-muted"><?= $idx + 1 ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($p['name']) ?></td>
                    <td style="font-size:11.5px;color:var(--text-muted)"><?= htmlspecialchars($p['location_name'] ?: '—') ?></td>
                    <td>
                      <span style="font-size:10px;font-weight:700;padding:3px 9px;border-radius:20px;background:rgba(239,68,68,.1);color:#ef4444;">
                        No Drawing
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- RIGHT: Recent A&E Studies -->
    <div class="col-lg-6">
      <div id="architecture_table" class="dr-panel h-100 sec-anchor" style="border-top:4px solid var(--dr-teal)">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="dr-panel-title mb-0">
            <div class="dr-dot" style="background:var(--dr-teal)"></div>
            <i class="fal fa-building me-2" style="color:var(--dr-teal)"></i>
            Recent Architect &amp; Engineering Studies
          </div>
          <a href="<?= $base ?>/con-architect-engineering"
            style="font-size:11px;color:var(--dr-teal);text-decoration:none;white-space:nowrap">
            View All <i class="fal fa-arrow-right ms-1"></i>
          </a>
        </div>
        <?php if (empty($architectEngineeringList)): ?>
          <div class="empty-msg">
            <i class="fal fa-drafting-compass fa-2x" style="display:block;margin-bottom:8px"></i>No A&amp;E records yet
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:13px;">
              <thead style="background:rgba(20,184,166,.04);">
                <tr>
                  <th class="text-uppercase text-muted" style="font-size:10px;letter-spacing:1px;padding:10px 12px;">#</th>
                  <th class="text-uppercase text-muted" style="font-size:10px;letter-spacing:1px;padding:10px 12px;">Project</th>
                  <th class="text-uppercase text-muted" style="font-size:10px;letter-spacing:1px;padding:10px 12px;">Architect</th>
                  <th class="text-uppercase text-muted" style="font-size:10px;letter-spacing:1px;padding:10px 12px;">Status</th>
                  <th class="text-uppercase text-muted" style="font-size:10px;letter-spacing:1px;padding:10px 12px;">Link</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($architectEngineeringList as $index => $ae): ?>
                  <?php
                    $status = strtolower($ae['status'] ?? 'not_started');
                    $sInfo  = $aeStatusLabel[$status] ?? ['label' => ucfirst($status), 'color' => '#6c757d', 'icon' => 'fa-info-circle'];
                  ?>
                  <tr>
                    <td class="text-muted"><?= $index + 1 ?></td>
                    <td class="fw-bold " style="max-width:130px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                      <?= htmlspecialchars($ae['project_name'] ?? 'N/A') ?>
                    </td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <div style="width:24px;height:24px;border-radius:50%;background:var(--dr-teal);color:#fff;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:bold;flex-shrink:0">
                          <?= strtoupper(substr($ae['internal_architect_name'] ?? 'U', 0, 1)) ?>
                        </div>
                        <span style="font-size:12px"><?= htmlspecialchars($ae['internal_architect_name'] ?? 'N/A') ?></span>
                      </div>
                    </td>
                    <td>
                      <span style="font-size:10px;font-weight:700;padding:4px 10px;border-radius:20px;background:<?= $sInfo['color'] ?>15;color:<?= $sInfo['color'] ?>">
                        <i class="fal <?= $sInfo['icon'] ?> me-1"></i><?= $sInfo['label'] ?>
                      </span>
                    </td>
                    <td>
                      <?php if (!empty($ae['full_architecture_design_link'])): ?>
                        <a href="<?= htmlspecialchars($ae['full_architecture_design_link']) ?>" target="_blank"
                          class="btn btn-sm btn-light" style="font-size:11px;color:var(--dr-blue)">
                          <i class="fal fa-external-link me-1"></i>View
                        </a>
                      <?php else: ?>
                        <span class="text-muted" style="font-size:11px;">—</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>

  <div style="height:30px"></div>

</div>
</div>
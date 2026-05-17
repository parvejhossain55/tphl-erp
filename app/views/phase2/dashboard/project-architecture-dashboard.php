<?php
$addPage = true;
$show_breadcrumb = false;
$base = $GLOBALS['base_url'];

$totalAE = $totalAE ?? 0;
$projectsWithAE = $projectsWithAE ?? 0;
$aeFullyDone = $aeFullyDone ?? 0;
$aePartial = $aePartial ?? 0;
$totalProjects = $totalProjects ?? 0;
$projectsNoAE = $projectsNoAE ?? 0;
$internalArchitects = $internalArchitects ?? 0;
$externalArchitects = $externalArchitects ?? 0;
$recentAE = $recentAE ?? [];
$projectsNeedingAE = $projectsNeedingAE ?? [];
$isAdmin = $isAdmin ?? false;
?>
<div class="row">
  <div class="col-12">
    <style>
      :root {
        --pa-primary: #198754;
        --pa-dark: #0f5132;
        --pa-light: #20a86a;
        --pa-blue: #3b82f6;
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
        background: var(--clr-bg-card, #fff);
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
        background: var(--clr-bg-card, #fff);
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

      /* Status badge */
      .status-badge {
        font-size: 10px;
        font-weight: 700;
        padding: 3px 9px;
        border-radius: 20px;
      }

      .status-fully {
        background: rgba(25, 135, 84, .12);
        color: #198754;
      }

      .status-partially {
        background: rgba(245, 158, 11, .12);
        color: #d97706;
      }

      .status-pending {
        background: rgba(239, 68, 68, .12);
        color: #ef4444;
      }

      /* Donut-style type bar */
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

      html {
        scroll-behavior: smooth;
      }

      .kpi-card {
        cursor: pointer;
      }

      .sec-anchor {
        scroll-margin-top: 80px;
      }
    </style>

    <!-- ═════ HERO ═════ -->
    <div class="pa-hero" style="position:relative;z-index:1">
      <div
        style="position:relative;z-index:2;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px">
        <div>
          <div class="pa-role-badge"><i class="fa-solid fa-drafting-compass"></i> Project Architecture</div>
          <h4 class="pa-hero-title">Architecture Dashboard</h4>
          <p class="pa-hero-sub">Architecture Records · <?= date('d M Y, h:i A') ?></p>
        </div>
      </div>
      <div class="pa-hero-actions">
        <a href="<?= $base ?>/con-architect-engineering" class="pa-btn-white"><i class="fal fa-drafting-compass"></i>
          Architecture</a>
      </div>
    </div>

    <!-- ═════ WORKFLOW NOTIFICATIONS ═════ -->
    <?php if (!empty($landDoneNotifications)): ?>
      <div class="mb-4" style="border-radius:14px; padding:20px; border:1px solid rgba(25,135,84,.2); background:rgba(25,135,84,.04);">
        <div style="font-weight:800; font-size:14px; color:#0f5132; margin-bottom:12px; display:flex; align-items:center; gap:8px;">
          <div style="width:32px; height:32px; border-radius:50%; background:rgba(25,135,84,.15); display:flex; align-items:center; justify-content:center; color:#198754;">
            <i class="fal fa-bell"></i>
          </div>
          Workflow Notification: Land Visibility Completed (Ready for Architecture)
        </div>
        <div style="overflow-x:auto; background:#fff; border-radius:10px; border:1px solid rgba(0,0,0,.05);">
          <table class="pa-table" style="margin:0;">
            <thead>
              <tr>
                <th>Project Name</th>
                <th>Land Study Completed On</th>
                <th style="text-align:right;">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($landDoneNotifications as $noti): ?>
                <tr>
                  <td><strong style="font-size:13px; color:var(--pa-primary);"><?= htmlspecialchars($noti['project_name']) ?></strong></td>
                  <td style="font-size:12px; color:var(--text-muted);">
                    <i class="fal fa-calendar me-1"></i> <?= date('d M Y, h:i A', strtotime($noti['land_completed_date'])) ?>
                  </td>
                  <td style="text-align:right;">
                    <a href="<?= $base ?>/con-architect-engineering" style="display:inline-flex; align-items:center; gap:5px; font-size:11px; font-weight:700; padding:6px 14px; border-radius:8px; text-decoration:none; background:var(--pa-primary); color:#fff;">
                      Start Architecture <i class="fal fa-arrow-right"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

    <!-- ═════ KPI GRID ═════ -->
    <div class="kpi-grid-4">

      <!-- Total A&E -->
      <a href="#sec-ae" class="kpi-card">
        <div class="kpi-accent" style="background:var(--pa-primary)"></div>
        <div class="kpi-icon" style="background:rgba(25,135,84,.12);color:var(--pa-primary)"><i
            class="fal fa-drafting-compass"></i></div>
        <div class="kpi-label" style="color:var(--text-muted)">Total A&amp;E Records</div>
        <div class="kpi-value" style="color:var(--pa-primary)"><?= $totalAE ?></div>
        <div class="kpi-sub">All architect &amp; engineering entries</div>
        <span
          style="position:absolute;top:14px;right:14px;font-size:9px;font-weight:700;padding:3px 8px;border-radius:20px;background:rgba(25,135,84,.1);color:var(--pa-primary)"><i
            class="fal fa-arrow-down me-1"></i>View</span>
      </a>

      <!-- Projects with A&E -->
      <a href="#sec-ae" class="kpi-card">
        <div class="kpi-accent" style="background:var(--pa-blue)"></div>
        <div class="kpi-icon" style="background:rgba(59,130,246,.12);color:var(--pa-blue)"><i
            class="fal fa-building"></i></div>
        <div class="kpi-label" style="color:var(--text-muted)">Projects with A&amp;E</div>
        <div class="kpi-value" style="color:var(--pa-blue)"><?= $projectsWithAE ?></div>
        <div class="kpi-sub">of <?= $totalProjects ?> total projects</div>
        <span
          style="position:absolute;top:14px;right:14px;font-size:9px;font-weight:700;padding:3px 8px;border-radius:20px;background:rgba(59,130,246,.1);color:var(--pa-blue)"><i
            class="fal fa-arrow-down me-1"></i>View</span>
      </a>

      <!-- Fully Done -->
      <a href="#sec-ae" class="kpi-card">
        <div class="kpi-accent" style="background:var(--pa-teal)"></div>
        <div class="kpi-icon" style="background:rgba(20,184,166,.12);color:var(--pa-teal)"><i
            class="fal fa-check-circle"></i></div>
        <div class="kpi-label" style="color:var(--text-muted)">Fully Completed</div>
        <div class="kpi-value" style="color:var(--pa-teal)"><?= $aeFullyDone ?></div>
        <div class="kpi-sub">A&amp;E status = Fully done</div>
      </a>

      <!-- Pending / Partial -->
      <a href="#sec-pending" class="kpi-card">
        <div class="kpi-accent" style="background:var(--pa-amber)"></div>
        <div class="kpi-icon" style="background:rgba(245,158,11,.12);color:var(--pa-amber)"><i
            class="fal fa-hourglass-half"></i></div>
        <div class="kpi-label" style="color:var(--text-muted)">Needs A&amp;E</div>
        <div class="kpi-value" style="color:var(--pa-amber)"><?= $projectsNoAE ?></div>
        <div class="kpi-sub">Projects without any A&amp;E record</div>
        <span
          style="position:absolute;top:14px;right:14px;font-size:9px;font-weight:700;padding:3px 8px;border-radius:20px;background:rgba(245,158,11,.1);color:var(--pa-amber)"><i
            class="fal fa-arrow-down me-1"></i>View</span>
      </a>

    </div>

    <!-- ═════ ROW 2 ═════ -->
    <div class="row">

      <!-- Recent A&E Records (left 8 cols) -->
      <div class="col-lg-8">
        <div id="sec-ae" class="pa-panel sec-anchor">
          <div class="pa-panel-title">
            <div class="pa-dot" style="background:var(--pa-primary)"></div>
            Recent Architect &amp; Engineering Records
            <a href="<?= $base ?>/con-architect-engineering"
              style="font-size:10px;color:var(--pa-blue);margin-left:auto;text-decoration:none">View All <i
                class="fal fa-external-link ms-1"></i></a>
          </div>
          <?php if (empty($recentAE)): ?>
            <div class="empty-msg"><i class="fal fa-drafting-compass"
                style="font-size:24px;display:block;margin-bottom:8px"></i>No A&amp;E records yet</div>
          <?php else: ?>
            <div style="overflow-x:auto">
              <table class="pa-table">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Firm / Architect Name</th>
                    <th>Project</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentAE as $i => $ae): ?>
                    <?php
                    $status = $ae['status'] ?? 'pending';
                    $statusCls = $status === 'fully' ? 'status-fully' : ($status === 'partially' ? 'status-partially' : 'status-pending');
                    $firmName = $ae['firm_name'] ?? $ae['name'] ?? ('A&E #' . $ae['id']);
                    ?>
                    <tr>
                      <td style="color:var(--text-muted);font-size:11px"><?= $i + 1 ?></td>
                      <td>
                        <strong><?= htmlspecialchars($firmName) ?></strong>
                        <?php if (!empty($ae['contact_person'])): ?>
                          <div style="font-size:11px;color:var(--text-muted)"><i
                              class="fal fa-user me-1"></i><?= htmlspecialchars($ae['contact_person']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($ae['internal_architect_name'])): ?>
                          <div style="font-size:11px;color:var(--pa-blue)"><i
                              class="fal fa-user-check me-1"></i><?= htmlspecialchars($ae['internal_architect_name']) ?></div>
                        <?php endif; ?>
                      </td>
                      <td style="font-size:11.5px;color:var(--text-muted)">
                        <?= htmlspecialchars($ae['project_name'] ?? 'N/A') ?></td>
                      <td><span class="status-badge <?= $statusCls ?>"><?= ucfirst($status) ?></span></td>
                      <td>
                        <a href="<?= $base ?>/con-architect-engineering"
                          style="font-size:11px;color:var(--pa-blue);text-decoration:none">
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

        <!-- Internal vs External Breakdown -->
        <div class="pa-panel">
          <div class="pa-panel-title">
            <div class="pa-dot" style="background:var(--pa-indigo)"></div>
            Architect Type Breakdown
          </div>
          <?php
          $intPct = ($totalAE > 0) ? round($internalArchitects / $totalAE * 100) : 0;
          $extPct = ($totalAE > 0) ? round($externalArchitects / $totalAE * 100) : 0;
          $partialPct = ($totalAE > 0) ? round($aePartial / $totalAE * 100) : 0;
          ?>
          <div class="type-row">
            <div style="min-width:90px;font-size:12px;font-weight:700;color:#6366f1">Internal</div>
            <div class="type-track">
              <div class="type-fill" style="width:<?= $intPct ?>%;background:#6366f1"></div>
            </div>
            <div style="font-size:12px;font-weight:800;min-width:32px;text-align:right;color:#6366f1">
              <?= $internalArchitects ?></div>
          </div>
          <div class="type-row">
            <div style="min-width:90px;font-size:12px;font-weight:700;color:#f59e0b">External</div>
            <div class="type-track">
              <div class="type-fill" style="width:<?= $extPct ?>%;background:#f59e0b"></div>
            </div>
            <div style="font-size:12px;font-weight:800;min-width:32px;text-align:right;color:#f59e0b">
              <?= $externalArchitects ?></div>
          </div>
          <div class="type-row">
            <div style="min-width:90px;font-size:12px;font-weight:700;color:#198754">Fully Done</div>
            <div class="type-track">
              <div class="type-fill"
                style="width:<?= $totalAE > 0 ? round($aeFullyDone / $totalAE * 100) : 0 ?>%;background:#198754"></div>
            </div>
            <div style="font-size:12px;font-weight:800;min-width:32px;text-align:right;color:#198754">
              <?= $aeFullyDone ?></div>
          </div>
          <div class="type-row">
            <div style="min-width:90px;font-size:12px;font-weight:700;color:#d97706">Partial</div>
            <div class="type-track">
              <div class="type-fill" style="width:<?= $partialPct ?>%;background:#d97706"></div>
            </div>
            <div style="font-size:12px;font-weight:800;min-width:32px;text-align:right;color:#d97706"><?= $aePartial ?>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="pa-panel">
          <div class="pa-panel-title">
            <div class="pa-dot" style="background:var(--pa-teal)"></div>
            Quick Actions
          </div>
          <div style="display:flex;flex-direction:column;gap:10px">
            <a href="<?= $base ?>/con-architect-engineering"
              style="display:flex;align-items:center;gap:12px;padding:13px;border-radius:12px;border:1px solid rgba(0,0,0,.08);text-decoration:none;color:var(--pa-primary);font-size:12px;font-weight:700;transition:box-shadow .15s"
              onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,.1)'" onmouseout="this.style.boxShadow='none'">
              <div
                style="width:38px;height:38px;border-radius:10px;background:rgba(25,135,84,.12);display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0">
                <i class="fal fa-drafting-compass"></i></div>
              <span>Architect &amp; Engineering List</span>
            </a>
          </div>
        </div>

      </div>
    </div>

    <!-- ═════ Projects Needing A&E ═════ -->
    <?php if (!empty($projectsNeedingAE)): ?>
      <div id="sec-pending" class="pa-panel sec-anchor">
        <div class="pa-panel-title">
          <div class="pa-dot" style="background:var(--pa-amber)"></div>
          Projects Without A&amp;E Record
          <span
            style="margin-left:auto;font-size:10px;background:rgba(245,158,11,.12);color:#d97706;padding:3px 10px;border-radius:20px;font-weight:700"><?= count($projectsNeedingAE) ?>
            Pending</span>
        </div>
        <div style="overflow-x:auto">
          <table class="pa-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Project Name</th>
                <th>Location</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($projectsNeedingAE as $i => $p): ?>
                <tr>
                  <td style="color:var(--text-muted);font-size:11px"><?= $i + 1 ?></td>
                  <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                  <td style="font-size:11.5px;color:var(--text-muted)"><?= htmlspecialchars($p['location_name'] ?: '-') ?>
                  </td>
                  <td>
                    <span
                      style="font-size:10px;font-weight:700;padding:3px 9px;border-radius:20px;background:rgba(25,135,84,.1);color:#198754">
                      <?= htmlspecialchars($p['project_status'] ?? 'N/A') ?>
                    </span>
                  </td>
                  <td>
                    <a href="<?= $base ?>/con-architect-engineering"
                      style="font-size:11px;color:var(--pa-blue);text-decoration:none">
                      Add A&amp;E <i class="fal fa-plus ms-1"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

    <div style="height:30px"></div>
  </div>
</div>
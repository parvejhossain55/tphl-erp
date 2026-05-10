<?php
$addPage = true;
$show_breadcrumb = false;
$base = $GLOBALS['base_url'];

function qcFmt($n)
{
  $n = (float) $n;
  if ($n >= 10000000)
    return '৳' . number_format($n / 10000000, 2) . ' Cr';
  if ($n >= 100000)
    return '৳' . number_format($n / 100000, 2) . ' L';
  if ($n >= 1000)
    return '৳' . number_format($n / 1000, 1) . 'K';
  return '৳' . number_format($n, 0);
}

$totalIssues = $totalIssues ?? 0;
$totalPOs = $totalPOs ?? 0;
$pendingPlans = $pendingPlans ?? 0;
$totalDPRs = $totalDPRs ?? 0;
$lowStock = $lowStock ?? 0;
$totalEstimates = $totalEstimates ?? 0;
$projects = $projects ?? [];
$recentIssues = $recentIssues ?? [];
$recentDPRs = $recentDPRs ?? [];
$pendingTargets = $pendingTargets ?? [];
?>
<div class="row">
  <div class="col-12">
    <style>
      :root {
        --qc-primary: #198754;
        --qc-dark: #0f5132;
        --qc-light: #20a86a;
        --qc-blue: #3b82f6;
        --qc-green: #10b981;
        --qc-amber: #f59e0b;
        --qc-red: #ef4444;
        --qc-purple: #8b5cf6;
        --qc-teal: #14b8a6;
        --qc-indigo: #6366f1;
      }

      .qc-hero {
        background: linear-gradient(135deg, #0f5132 0%, #198754 55%, #20a86a 100%);
        border-radius: 18px;
        padding: 28px 32px;
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
      }

      .qc-hero::before {
        content: '';
        position: absolute;
        width: 300px;
        height: 300px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .04);
        top: -100px;
        right: -80px;
      }

      .qc-hero::after {
        content: '';
        position: absolute;
        width: 200px;
        height: 200px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .03);
        bottom: -60px;
        left: -40px;
      }

      .qc-role-badge {
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

      .qc-hero-title {
        font-size: 28px;
        font-weight: 900;
        color: #fff;
        margin: 0 0 6px;
      }

      .qc-hero-sub {
        font-size: 13px;
        color: rgba(255, 255, 255, .7);
        margin: 0;
      }

      .qc-hero-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
      }

      .qc-btn-white {
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

      .qc-btn-white:hover {
        background: rgba(255, 255, 255, .25);
        color: #fff;
        text-decoration: none;
      }

      /* KPI Grid */
      .kpi-grid-6 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 14px;
        margin-bottom: 22px;
      }

      @media(max-width:992px) {
        .kpi-grid-6 {
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

      .kpi-badge {
        position: absolute;
        top: 14px;
        right: 14px;
        font-size: 9px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 20px;
        letter-spacing: .5px;
      }

      /* Panel */
      .rd-panel {
        border-radius: 14px;
        padding: 22px;
        border: 1px solid rgba(0, 0, 0, .08);
        background: var(--clr-bg-card, #fff);
        margin-bottom: 22px;
      }

      .rd-panel-title {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .rd-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
      }

      /* Progress bars */
      .prog-row {
        margin-bottom: 13px;
      }

      .prog-row:last-child {
        margin-bottom: 0;
      }

      .prog-meta {
        display: flex;
        justify-content: space-between;
        font-size: 12px;
        margin-bottom: 5px;
      }

      .prog-track {
        height: 7px;
        border-radius: 6px;
        background: rgba(0, 0, 0, .07);
        overflow: hidden;
      }

      .prog-fill {
        height: 100%;
        border-radius: 6px;
        transition: width .5s ease;
      }

      /* Table */
      .rd-table {
        width: 100%;
        border-collapse: collapse;
      }

      .rd-table th {
        font-size: 10px;
        letter-spacing: 1.4px;
        text-transform: uppercase;
        font-weight: 600;
        padding: 0 10px 10px;
        border-bottom: 2px solid rgba(0, 0, 0, .07);
        text-align: left;
      }

      .rd-table td {
        padding: 11px 10px;
        border-bottom: 1px solid rgba(0, 0, 0, .05);
        font-size: 12.5px;
        vertical-align: middle;
      }

      .rd-table tr:last-child td {
        border-bottom: none;
      }

      .status-pill {
        font-size: 10px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 20px;
        letter-spacing: .5px;
      }

      .empty-msg {
        text-align: center;
        padding: 28px 0;
        color: var(--text-muted);
        font-size: 12px;
      }

      /* Quick Links */
      .quick-links {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
      }

      .ql-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 16px 10px;
        border-radius: 12px;
        text-decoration: none;
        border: 1px solid rgba(0, 0, 0, .08);
        font-size: 11px;
        font-weight: 700;
        transition: transform .15s, box-shadow .15s;
        background: var(--clr-bg-card, #fff);
      }

      .ql-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, .1);
        text-decoration: none;
      }

      .ql-icon {
        font-size: 22px;
      }
    </style>

    <!-- ═══════════════ HERO HEADER ═══════════════ -->
    <div class="qc-hero" style="position:relative;z-index:1">
      <div
        style="position:relative;z-index:2;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px">
        <div>
          <div class="qc-role-badge">
            <i class="fa-solid fa-shield-check"></i>
            Quality Controller (QC)
          </div>
          <h4 class="qc-hero-title">QC Dashboard</h4>
          <p class="qc-hero-sub">Real-time overview · <?= date('d M Y, h:i A') ?></p>
        </div>
        <div class="qc-hero-actions">
          <a href="<?= $base ?>/con-estimates" class="qc-btn-white"><i class="fal fa-file-alt"></i> Estimates</a>
          <a href="<?= $base ?>/con-material-issues" class="qc-btn-white"><i class="fal fa-box-open"></i> Material
            Issues</a>
          <a href="<?= $base ?>/con-daily-reports" class="qc-btn-white"><i class="fal fa-chart-bar"></i> Reports</a>
        </div>
      </div>
    </div>

    <!-- ═══════════════ KPI CARDS ═══════════════ -->
    <div class="kpi-grid-6">

      <a href="<?= $base ?>/con-material-issues" class="kpi-card">
        <div class="kpi-accent" style="background:var(--qc-blue)"></div>
        <div class="kpi-icon" style="background:rgba(59,130,246,.12);color:var(--qc-blue)"><i
            class="fal fa-box-open"></i></div>
        <div class="kpi-label" style="color:var(--text-muted)">Material Issues</div>
        <div class="kpi-value" style="color:var(--qc-blue)"><?= $totalIssues ?></div>
        <div class="kpi-sub">Total stock-out records</div>
      </a>

      <a href="<?= $base ?>/con-purchase-orders" class="kpi-card">
        <div class="kpi-accent" style="background:var(--qc-green)"></div>
        <div class="kpi-icon" style="background:rgba(16,185,129,.12);color:var(--qc-green)"><i
            class="fal fa-cart-shopping"></i></div>
        <div class="kpi-label" style="color:var(--text-muted)">Purchase Orders</div>
        <div class="kpi-value" style="color:var(--qc-green)"><?= $totalPOs ?></div>
        <div class="kpi-sub">Total POs raised</div>
      </a>

      <a href="<?= $base ?>/con-working-plans" class="kpi-card">
        <div class="kpi-accent" style="background:var(--qc-amber)"></div>
        <div class="kpi-icon" style="background:rgba(245,158,11,.12);color:var(--qc-amber)"><i
            class="fal fa-calendar-alt"></i></div>
        <div class="kpi-label" style="color:var(--text-muted)">Working Plans</div>
        <div class="kpi-value" style="color:var(--qc-amber)"><?= $pendingPlans ?></div>
        <div class="kpi-sub">Draft / Pending plans</div>
        <?php if ($pendingPlans > 0): ?>
          <span class="kpi-badge" style="background:rgba(245,158,11,.12);color:var(--qc-amber)">REVIEW</span>
        <?php endif; ?>
      </a>

      <a href="<?= $base ?>/con-daily-reports" class="kpi-card">
        <div class="kpi-accent" style="background:var(--qc-purple)"></div>
        <div class="kpi-icon" style="background:rgba(139,92,246,.12);color:var(--qc-purple)"><i
            class="fal fa-chart-bar"></i></div>
        <div class="kpi-label" style="color:var(--text-muted)">Project Reports (WPR)</div>
        <div class="kpi-value" style="color:var(--qc-purple)"><?= $totalDPRs ?></div>
        <div class="kpi-sub">Daily progress reports</div>
      </a>

      <a href="<?= $base ?>/con-stocks" class="kpi-card">
        <div class="kpi-accent" style="background:var(--qc-red)"></div>
        <div class="kpi-icon" style="background:rgba(239,68,68,.12);color:var(--qc-red)"><i
            class="fal fa-warehouse"></i></div>
        <div class="kpi-label" style="color:var(--text-muted)">Low Stock Alerts</div>
        <div class="kpi-value" style="color:var(--qc-red)"><?= $lowStock ?></div>
        <div class="kpi-sub">Items below minimum level</div>
        <?php if ($lowStock > 0): ?>
          <span class="kpi-badge" style="background:rgba(239,68,68,.12);color:var(--qc-red)"><i
              class="fas fa-exclamation-triangle me-1"></i>ALERT</span>
        <?php endif; ?>
      </a>

      <a href="<?= $base ?>/con-estimates" class="kpi-card">
        <div class="kpi-accent" style="background:var(--qc-teal)"></div>
        <div class="kpi-icon" style="background:rgba(20,184,166,.12);color:var(--qc-teal)"><i
            class="fal fa-file-invoice-dollar"></i></div>
        <div class="kpi-label" style="color:var(--text-muted)">Estimates</div>
        <div class="kpi-value" style="color:var(--qc-teal)"><?= $totalEstimates ?></div>
        <div class="kpi-sub">Initial budget estimates</div>
      </a>

    </div>

    <!-- ═══════════════ ROW 2 ═══════════════ -->
    <div class="row align-items-stretch">

      <!-- Left Column -->
      <div class="col-lg-7 d-flex flex-column">

        <!-- Project Progress -->
        <div class="rd-panel flex-fill">
          <div class="rd-panel-title">
            <div class="rd-dot" style="background:var(--qc-blue)"></div>
            Project Progress Overview
            <a href="<?= $base ?>/con-projects"
              style="font-size:10px;color:var(--qc-blue);margin-left:auto;text-decoration:none">
              View All <i class="fal fa-arrow-right ms-1"></i>
            </a>
          </div>

          <?php if (empty($projects)): ?>
            <div class="empty-msg">No projects found</div>
          <?php else: ?>
            <?php foreach ($projects as $pr):
              $pct = round((float) ($pr['progress_pct'] ?? 0), 1);
              $grad = $pct >= 75 ? 'linear-gradient(90deg,#059669,#10b981)' : ($pct >= 40 ? 'linear-gradient(90deg,#d97706,#f59e0b)' : 'linear-gradient(90deg,#dc2626,#ef4444)');
              $clr = $pct >= 75 ? 'var(--qc-green)' : ($pct >= 40 ? 'var(--qc-amber)' : 'var(--qc-red)');
              ?>
              <div class="prog-row" style="cursor:pointer"
                onclick="location.href='<?= $base ?>/con-project/<?= $pr['id'] ?>'">

                <div class="prog-meta">
                  <div>
                    <strong style="font-size:13px"><?= htmlspecialchars($pr['name']) ?></strong>
                    <span style="font-size:11px;color:var(--text-muted);margin-left:8px">
                      <i class="fal fa-map-marker-alt me-1"></i>
                      <?= htmlspecialchars($pr['location_name'] ?: 'N/A') ?>
                    </span>
                  </div>
                  <span style="font-weight:800;font-size:13px;color:<?= $clr ?>">
                    <?= $pct ?>%
                  </span>
                </div>

                <div class="prog-track">
                  <div class="prog-fill" style="width:<?= $pct ?>%;background:<?= $grad ?>"></div>
                </div>

              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- Recent Material Issues -->
        <div class="rd-panel flex-fill">
          <div class="rd-panel-title">
            <div class="rd-dot" style="background:var(--qc-purple)"></div>
            Recent Material Issues
          </div>

          <?php if (empty($recentIssues)): ?>
            <div class="empty-msg">No material issues found</div>
          <?php else: ?>
            <table class="rd-table">
              <thead>
                <tr>
                  <th>Material</th>
                  <th>Project</th>
                  <th>Qty</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentIssues as $iss): ?>
                  <tr>
                    <td><strong><?= htmlspecialchars($iss['material_name'] ?? 'N/A') ?></strong></td>
                    <td style="font-size:11.5px;color:var(--text-muted)">
                      <?= htmlspecialchars($iss['project_name'] ?? 'N/A') ?>
                    </td>
                    <td><strong><?= $iss['quantity_issued'] ?? 0 ?></strong></td>
                    <td style="font-size:11px;color:var(--text-muted)">
                      <?= isset($iss['issue_date']) ? date('d M Y', strtotime($iss['issue_date'])) : 'N/A' ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>

      </div>

      <!-- Right Column -->
      <div class="col-lg-5 d-flex flex-column">

        <!-- Quick Actions (small) -->
        <div class="rd-panel flex-grow-0">
          <div class="rd-panel-title">
            <div class="rd-dot" style="background:var(--qc-indigo)"></div>
            Quick Actions
          </div>

          <div class="quick-links">
            <a href="<?= $base ?>/con-estimates" class="ql-btn">Estimates</a>
            <a href="<?= $base ?>/con-working-plans" class="ql-btn">Work Plans</a>
            <a href="<?= $base ?>/con-material-issues" class="ql-btn">Material Issues</a>
            <a href="<?= $base ?>/con-purchase-orders" class="ql-btn">Purchase Orders</a>
            <a href="<?= $base ?>/con-daily-reports" class="ql-btn">DPR Reports</a>
            <a href="<?= $base ?>/con-targets" class="ql-btn">Targets</a>
          </div>
        </div>

        <!-- Recent DPRs (big) -->
        <div class="rd-panel flex-grow-1">
          <div class="rd-panel-title">
            <div class="rd-dot" style="background:var(--qc-amber)"></div>
            Recent Project Reports
          </div>

          <?php if (empty($recentDPRs)): ?>
            <div class="empty-msg">No reports yet</div>
          <?php else: ?>
            <?php foreach ($recentDPRs as $dpr): ?>
              <div
                style="padding:11px 0;border-bottom:1px solid rgba(0,0,0,.06);display:flex;justify-content:space-between;">
                <div>
                  <div style="font-size:12.5px;font-weight:600">
                    <?= htmlspecialchars($dpr['project_name'] ?? 'N/A') ?>
                  </div>
                  <div style="font-size:10.5px;color:var(--text-muted)">
                    <?= isset($dpr['report_date']) ? date('d M Y', strtotime($dpr['report_date'])) : 'N/A' ?>
                  </div>
                </div>
                <a href="<?= $base ?>/con-daily-report/<?= $dpr['id'] ?>">View</a>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>

        </div>

      </div>

    </div>

    <?php if (!empty($pendingGRNsQC)): ?>
      <div class="rd-panel" style="border-color:rgba(139,92,246,.3);background:rgba(139,92,246,.03);margin-top:4px">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="rd-panel-title" style="margin-bottom:0">
            <div class="rd-dot" style="background:var(--qc-purple,#8b5cf6)"></div>
            <i class="fal fa-clipboard-check me-2" style="color:var(--qc-purple,#8b5cf6)"></i>
            Pending Material Receivings (GRN) — QC Approval Required
            <span style="margin-left:8px;font-size:9px;font-weight:700;padding:2px 10px;border-radius:20px;
        background:rgba(139,92,246,.12);color:var(--qc-purple,#8b5cf6)">
              <?= count($pendingGRNsQC) ?> Awaiting
            </span>
          </div>
          <a href="<?= $base ?>/con-material-receivings"
            style="font-size:11px;color:var(--qc-blue);text-decoration:none">View All <i
              class="fal fa-arrow-right ms-1"></i></a>
        </div>
        <div style="overflow-x:auto">
          <table class="rd-table">
            <thead>
              <tr>
                <th>#</th>
                <th>GRN Number</th>
                <th>Project</th>
                <th>Receiving Date</th>
                <th>Logged By</th>
                <th style="text-align:right">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pendingGRNsQC as $pi => $grn): ?>
                <tr>
                  <td style="font-size:11px;color:var(--text-muted)"><?= $pi + 1 ?></td>
                  <td style="font-weight:800;color:var(--qc-purple,#8b5cf6)">GRN-<?= $grn['id'] ?></td>
                  <td style="font-weight:700"><?= htmlspecialchars($grn['project_name'] ?? 'N/A') ?></td>
                  <td style="font-size:11px;color:var(--text-muted)">
                    <?= !empty($grn['receiving_date']) ? date('d M Y', strtotime($grn['receiving_date'])) : '&mdash;' ?>
                  </td>
                  <td style="font-size:12px"><?= htmlspecialchars($grn['received_by_name'] ?? '&mdash;') ?></td>
                  <td style="text-align:right">
                    <a href="<?= $base ?>/con-material-receiving-qc/<?= $grn['id'] ?>" class="btn btn-sm"
                      style="font-size:11px;padding:4px 12px;background:linear-gradient(135deg,#7c3aed,#8b5cf6);color:#fff;border-radius:6px;text-decoration:none">
                      <i class="fal fa-check-circle me-1"></i> Review &amp; Approve
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

    <?php
    $pendingWeeklyReportsQC = $pendingWeeklyReportsQC ?? [];
    if (!empty($pendingWeeklyReportsQC)):
      ?>
      <div class="rd-panel" style="border-color:rgba(16,185,129,.3);background:rgba(16,185,129,.03);margin-top:4px">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="rd-panel-title" style="margin-bottom:0">
            <div class="rd-dot" style="background:#0f5132"></div>
            <i class="fal fa-calendar-week me-2" style="color:#0f5132"></i>
            Pending Weekly Reports — QC Approval Required
            <span style="margin-left:8px;font-size:9px;font-weight:700;padding:2px 10px;border-radius:20px;
        background:rgba(16,185,129,.12);color:#0f5132">
              <?= count($pendingWeeklyReportsQC) ?> Awaiting
            </span>
          </div>
          <a href="<?= $base ?>/con-weekly-reports" style="font-size:11px;color:var(--qc-blue);text-decoration:none">View
            All <i class="fal fa-arrow-right ms-1"></i></a>
        </div>
        <div style="overflow-x:auto">
          <table class="rd-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Report Date</th>
                <th>Project</th>
                <th>Week No</th>
                <th>Submitted By</th>
                <th style="text-align:right">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pendingWeeklyReportsQC as $pi => $rep): ?>
                <tr>
                  <td style="font-size:11px;color:var(--text-muted)"><?= $pi + 1 ?></td>
                  <td style="font-weight:800;color:#0f5132">
                    <?= !empty($rep['report_date']) ? date('d M Y', strtotime($rep['report_date'])) : '&mdash;' ?>
                  </td>
                  <td style="font-weight:700"><?= htmlspecialchars($rep['project_name'] ?? 'N/A') ?></td>
                  <td style="font-size:11px;color:var(--text-muted)">Week <?= (int) $rep['week_no'] ?></td>
                  <td style="font-size:12px"><?= htmlspecialchars($rep['created_by_name'] ?? '&mdash;') ?></td>
                  <td style="text-align:right">
                    <a href="<?= $base ?>/con-weekly-report/<?= $rep['id'] ?>" class="btn btn-sm"
                      style="font-size:11px;padding:4px 12px;background:linear-gradient(135deg,#0f5132,#198754);color:#fff;border-radius:6px;text-decoration:none">
                      <i class="fal fa-check-circle me-1"></i> Review &amp; Approve
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($pendingTargets)): ?>
      <div class="rd-panel" style="border-color:rgba(245,158,11,.3);background:rgba(245,158,11,.03);margin-top:4px">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="rd-panel-title" style="margin-bottom:0">
            <div class="rd-dot" style="background:var(--qc-amber)"></div>
            <i class="fal fa-bullseye me-2" style="color:var(--qc-amber)"></i>
            Pending Targets — QC Approval Required
            <span style="margin-left:8px;font-size:9px;font-weight:700;padding:2px 10px;border-radius:20px;
        background:rgba(245,158,11,.12);color:var(--qc-amber)">
              <?= count($pendingTargets) ?> Awaiting
            </span>
          </div>
          <a href="<?= $base ?>/con-targets" style="font-size:11px;color:var(--qc-blue);text-decoration:none">View All <i
              class="fal fa-arrow-right ms-1"></i></a>
        </div>
        <div style="overflow-x:auto">
          <table class="rd-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Project</th>
                <th>Target Title</th>
                <th>Submitted By</th>
                <th>Designation</th>
                <th>Target %</th>
                <th>Gain %</th>
                <th>Period</th>
                <th>Submitted On</th>
                <th style="text-align:right">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pendingTargets as $pi => $pt): ?>
                <tr>
                  <td style="font-size:11px;color:var(--text-muted)"><?= $pi + 1 ?></td>
                  <td style="font-weight:700"><?= htmlspecialchars($pt['project_name'] ?? 'N/A') ?></td>
                  <td><?= htmlspecialchars($pt['target_title'] ?? '—') ?></td>
                  <td>
                    <div style="display:flex;align-items:center;gap:7px">
                      <div style="width:26px;height:26px;border-radius:50%;background:rgba(99,102,241,.1);
                display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="fal fa-user" style="font-size:10px;color:#6366f1"></i>
                      </div>
                      <span
                        style="font-size:12px;font-weight:600"><?= htmlspecialchars($pt['creator_name'] ?? '—') ?></span>
                    </div>
                  </td>
                  <td>
                    <span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px;
              background:rgba(99,102,241,.08);color:#6366f1">
                      <?= htmlspecialchars($pt['creator_designation'] ?: 'SE') ?>
                    </span>
                  </td>
                  <td><span class="status-pill"
                      style="background:rgba(59,130,246,.1);color:#2563eb"><?= number_format($pt['target_percentage'] ?? 0, 1) ?>%</span>
                  </td>
                  <td>
                    <?php $g = (float) ($pt['gain_percentage'] ?? 0);
                    $tp = (float) ($pt['target_percentage'] ?? 0); ?>
                    <span class="status-pill" style="background:<?= $g >= $tp ? 'rgba(16,185,129,.1)' : 'rgba(245,158,11,.1)' ?>;
              color:<?= $g >= $tp ? '#059669' : '#d97706' ?>"><?= number_format($g, 1) ?>%</span>
                  </td>
                  <td style="font-size:11px;color:var(--text-muted)">
                    <?= $pt['year'] ?? '' ?>
                    <?= $pt['week_number'] ? ' / Wk ' . $pt['week_number'] : '' ?>
                  </td>
                  <td style="font-size:11px;color:var(--text-muted);white-space:nowrap">
                    <i class="fal fa-calendar me-1"></i>
                    <?= !empty($pt['created_at']) ? date('d M Y', strtotime($pt['created_at'])) : '—' ?>
                  </td>
                  <td style="text-align:right">
                    <a href="<?= $base ?>/con-target/<?= $pt['id'] ?>" style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;
                padding:5px 14px;border-radius:8px;text-decoration:none;
                background:linear-gradient(135deg,#059669,#10b981);color:#fff">
                      <i class="fal fa-check-circle"></i> Review &amp; Approve
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
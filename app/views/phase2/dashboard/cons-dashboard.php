<?php
$addPage = true;
$show_breadcrumb = false;
$base = $GLOBALS['base_url'];

// Helper: format BDT amounts
function conFmt($n)
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

$stats = $stats ?? [];
$projects = $projects ?? [];
$budgetUtil = $budgetUtil ?? ['total' => 0, 'spent' => 0, 'pct' => 0, 'remaining' => 0];
$stockAlerts = $stockAlerts ?? [];
$pendingAlerts = $pendingAlerts ?? [];
$soilAlerts = $soilAlerts ?? [];
$pendingInitialBudgets = $pendingInitialBudgets ?? [];

$statusColor = [
  'Planning' => ['bg' => 'rgba(99,102,241,.12)', 'text' => '#6366f1'],
  'In Progress' => ['bg' => 'rgba(245,158,11,.12)', 'text' => '#f59e0b'],
  'On Hold' => ['bg' => 'rgba(239,68,68,.12)', 'text' => '#ef4444'],
  'Completed' => ['bg' => 'rgba(16,185,129,.12)', 'text' => '#10b981'],
];
?>
<div class="row">
  <div class="col-12">
    <style>
      :root {
        --cd-primary: #198754;
        --cd-dark: #0f5132;
        --cd-light: #20a86a;
        --cd-blue: #3b82f6;
        --cd-green: #10b981;
        --cd-amber: #f59e0b;
        --cd-red: #ef4444;
        --cd-purple: #8b5cf6;
        --cd-indigo: #6366f1;
        --cd-teal: #14b8a6;
        --cd-pink: #ec4899;
      }

      /* ── KPI Cards ────────────────────────────────────────────── */
      .kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 22px;
      }

      .kpi-card {
        border-radius: 14px;
        padding: 18px 20px;
        position: relative;
        overflow: hidden;
        text-decoration: none !important;
        display: block;
        border: 1px solid var(--border-color, rgba(0, 0, 0, .08));
        transition: transform .18s ease, box-shadow .18s ease;
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
        font-size: 26px;
        font-weight: 800;
        line-height: 1;
        margin-bottom: 4px;
      }

      .kpi-label {
        font-size: 10.5px;
        font-weight: 600;
        letter-spacing: 1px;
        text-transform: uppercase;
        margin-bottom: 3px;
      }

      .kpi-sub {
        font-size: 11px;
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

      /* ── Section Panels ───────────────────────────────────────── */
      .con-panel {
        border-radius: 14px;
        padding: 22px;
        border: 1px solid var(--border-color, rgba(0, 0, 0, .08));
      }

      .sec-title {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .sec-title-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
      }

      /* ── Progress bars ────────────────────────────────────────── */
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
        height: 8px;
        border-radius: 6px;
        background: rgba(0, 0, 0, .07);
        overflow: hidden;
      }

      .prog-fill {
        height: 100%;
        border-radius: 6px;
        transition: width .5s ease;
      }

      /* ── Donut ────────────────────────────────────────────────── */
      .donut-ring {
        width: 130px;
        height: 130px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        margin: 0 auto 14px;
      }

      .donut-inner {
        position: absolute;
        z-index: 2;
        text-align: center;
      }

      .donut-pct {
        font-size: 22px;
        font-weight: 800;
        line-height: 1;
      }

      .donut-sub {
        font-size: 10px;
      }

      .donut-stats {
        display: flex;
        gap: 20px;
        justify-content: center;
        flex-wrap: wrap;
        margin-top: 14px;
      }

      .donut-stat {
        text-align: center;
      }

      .donut-stat-val {
        font-size: 16px;
        font-weight: 700;
      }

      .donut-stat-lbl {
        font-size: 10px;
      }

      /* ── Project Table ────────────────────────────────────────── */
      .proj-table {
        width: 100%;
        border-collapse: collapse;
      }

      .proj-table th {
        font-size: 10px;
        letter-spacing: 1.4px;
        text-transform: uppercase;
        font-weight: 600;
        padding: 0 12px 10px;
        border-bottom: 2px solid rgba(0, 0, 0, .07);
        text-align: left;
      }

      .proj-table td {
        padding: 12px;
        border-bottom: 1px solid rgba(0, 0, 0, .05);
        font-size: 12.5px;
        vertical-align: middle;
      }

      .proj-table tr:last-child td {
        border-bottom: none;
      }

      .proj-table tr.proj-row {
        cursor: pointer;
        transition: background .15s;
      }

      .proj-table tr.proj-row:hover td {
        background: rgba(59, 130, 246, .04);
      }

      .proj-name-cell {
        font-weight: 700;
        font-size: 13px;
      }

      .proj-loc-cell {
        font-size: 11px;
        margin-top: 2px;
      }

      .status-pill {
        font-size: 10px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 20px;
        letter-spacing: .5px;
      }

      /* ── Alert Cards ──────────────────────────────────────────── */
      .alert-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 11px 13px;
        border-radius: 10px;
        margin-bottom: 8px;
        cursor: pointer;
        transition: opacity .15s;
        border: 1px solid rgba(0, 0, 0, .06);
      }

      .alert-item:last-child {
        margin-bottom: 0;
      }

      .alert-item:hover {
        opacity: .8;
      }

      .alert-icon-wrap {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
      }

      .alert-item-title {
        font-size: 12.5px;
        font-weight: 600;
      }

      .alert-item-sub {
        font-size: 11px;
        margin-top: 1px;
      }

      /* ── Stock Alert ──────────────────────────────────────────── */
      .stock-item {
        padding: 11px 13px;
        border-radius: 10px;
        margin-bottom: 8px;
        border: 1px solid rgba(0, 0, 0, .06);
      }

      .stock-item:last-child {
        margin-bottom: 0;
      }

      .stock-name {
        font-size: 12.5px;
        font-weight: 700;
      }

      .stock-qty {
        font-size: 16px;
        font-weight: 700;
      }

      .stock-track {
        height: 5px;
        border-radius: 3px;
        background: rgba(0, 0, 0, .08);
        overflow: hidden;
        margin-top: 6px;
      }

      .stock-fill {
        height: 100%;
        border-radius: 3px;
        background: linear-gradient(90deg, #ef4444, #f87171);
      }

      /* ── Empty state ──────────────────────────────────────────── */
      .empty-state {
        text-align: center;
        padding: 28px 0;
      }

      .empty-state-icon {
        font-size: 28px;
        opacity: .3;
        margin-bottom: 8px;
      }

      .empty-state-text {
        font-size: 12px;
      }
    </style>

    <!-- ═══════════════════════════════ GREEN HERO BANNER ═══════════════════════════════ -->
    <div style="background:linear-gradient(135deg,#0f5132 0%,#198754 55%,#20a86a 100%);
            border-radius:18px;padding:26px 30px;margin-bottom:22px;
            position:relative;overflow:hidden">
      <div style="position:absolute;width:260px;height:260px;border-radius:50%;
    background:rgba(255,255,255,.04);top:-80px;right:-60px"></div>
      <div style="position:absolute;width:160px;height:160px;border-radius:50%;
    background:rgba(255,255,255,.03);bottom:-50px;left:-30px"></div>
      <div
        style="position:relative;z-index:2;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:14px">
        <div>
          <div style="display:inline-flex;align-items:center;gap:7px;
        background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.22);
        padding:4px 14px;border-radius:20px;font-size:10.5px;font-weight:700;
        letter-spacing:1.5px;text-transform:uppercase;color:#fff;margin-bottom:12px">
            <i class="fa-solid fa-hard-hat"></i> Construction Management
          </div>
          <h4 style="font-size:24px;font-weight:900;color:#fff;margin:0 0 5px">Construction Dashboard</h4>
          <p style="font-size:12.5px;color:rgba(255,255,255,.7);margin:0">
            <i class="fal fa-calendar me-1"></i>Real-time overview · <?= date('d M Y, h:i A') ?>
          </p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
          <a href="<?= $base ?>/con-estimates" style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);
        color:#fff;border-radius:10px;padding:7px 18px;font-size:12px;font-weight:700;
        text-decoration:none;display:inline-flex;align-items:center;gap:6px">
            <i class="fal fa-file-alt"></i>Estimates
          </a>
          <a href="<?= $base ?>/con-working-plans" style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);
        color:#fff;border-radius:10px;padding:7px 18px;font-size:12px;font-weight:700;
        text-decoration:none;display:inline-flex;align-items:center;gap:6px">
            <i class="fal fa-calendar-alt"></i>Work Plans
          </a>
          <a href="<?= $base ?>/con-projects" style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);
        color:#fff;border-radius:10px;padding:7px 18px;font-size:12px;font-weight:700;
        text-decoration:none;display:inline-flex;align-items:center;gap:6px">
            <i class="fal fa-building"></i>Projects
          </a>
        </div>
      </div>
    </div>

    <!-- ══════════════════════ ROW 1: 4 KPI CARDS ═══════════════════════════════ -->
    <div class="kpi-grid">

      <!-- 1. Total Projects -->
      <a href="<?= $base ?>/con-projects" class="kpi-card card__wrapper">
        <div class="kpi-accent" style="background:var(--cd-blue)"></div>
        <div class="kpi-icon" style="background:rgba(59,130,246,.12);color:var(--cd-blue)"><i
            class="fal fa-building"></i></div>
        <div class="kpi-label" style="color:var(--text-muted)">Total Projects</div>
        <div class="kpi-value" style="color:var(--cd-blue)"><?= $stats['total_projects'] ?? 0 ?></div>
        <div class="kpi-sub" style="color:var(--text-muted)">Active construction sites</div>
      </a>

      <!-- 2. Total Initial Budget -->
      <a href="<?= $base ?>/con-estimates" class="kpi-card card__wrapper">
        <div class="kpi-accent" style="background:var(--cd-green)"></div>
        <div class="kpi-icon" style="background:rgba(16,185,129,.12);color:var(--cd-green)"><i
            class="fal fa-money-bill-wave"></i></div>
        <div class="kpi-label" style="color:var(--text-muted)">Total Initial Budget</div>
        <div class="kpi-value" style="color:var(--cd-green)"><?= conFmt($stats['total_budget'] ?? 0) ?></div>
        <div class="kpi-sub" style="color:var(--text-muted)">Approved estimates total</div>
      </a>

      <!-- 3. Material Budget -->
      <a href="<?= $base ?>/con-estimates" class="kpi-card card__wrapper">
        <div class="kpi-accent" style="background:var(--cd-amber)"></div>
        <div class="kpi-icon" style="background:rgba(245,158,11,.12);color:var(--cd-amber)"><i class="fal fa-boxes"></i>
        </div>
        <div class="kpi-label" style="color:var(--text-muted)">Total Material Budget</div>
        <div class="kpi-value" style="color:var(--cd-amber)"><?= conFmt($stats['total_material'] ?? 0) ?></div>
        <div class="kpi-sub" style="color:var(--text-muted)">Construction materials cost</div>
      </a>

      <!-- 4. Manpower Budget -->
      <a href="<?= $base ?>/con-estimates" class="kpi-card card__wrapper">
        <div class="kpi-accent" style="background:var(--cd-purple)"></div>
        <div class="kpi-icon" style="background:rgba(139,92,246,.12);color:var(--cd-purple)"><i
            class="fal fa-hard-hat"></i></div>
        <div class="kpi-label" style="color:var(--text-muted)">Total Manpower Budget</div>
        <div class="kpi-value" style="color:var(--cd-purple)"><?= conFmt($stats['total_labour'] ?? 0) ?></div>
        <div class="kpi-sub" style="color:var(--text-muted)">Labour &amp; workforce cost</div>
      </a>

    </div>

    <!-- ══════════════════════ ROW 2: 4 MORE CARDS ══════════════════════════════ -->
    <div class="kpi-grid mb-4">

      <!-- 5. Stock Alerts -->
      <a href="<?= $base ?>/con-stocks" class="kpi-card card__wrapper">
        <div class="kpi-accent" style="background:var(--cd-red)"></div>
        <div class="kpi-icon" style="background:rgba(239,68,68,.12);color:var(--cd-red)"><i
            class="fal fa-warehouse"></i></div>
        <div class="kpi-label" style="color:var(--text-muted)">Stock Alerts</div>
        <div class="kpi-value" style="color:var(--cd-red)"><?= $stats['stock_alerts'] ?? 0 ?></div>
        <div class="kpi-sub" style="color:var(--text-muted)">Items below minimum level</div>
        <?php if (($stats['stock_alerts'] ?? 0) > 0): ?>
          <span class="kpi-badge" style="background:rgba(239,68,68,.12);color:var(--cd-red)"><i
              class="fas fa-exclamation-triangle me-1"></i>ALERT</span>
        <?php endif; ?>
      </a>

      <!-- 6. Pending Approvals -->
      <a href="<?= $base ?>/con-pending-payments" class="kpi-card card__wrapper">
        <div class="kpi-accent" style="background:var(--cd-indigo)"></div>
        <div class="kpi-icon" style="background:rgba(99,102,241,.12);color:var(--cd-indigo)"><i
            class="fal fa-check-circle"></i></div>
        <div class="kpi-label" style="color:var(--text-muted)">Pending Approvals</div>
        <div class="kpi-value" style="color:var(--cd-indigo)"><?= $stats['pending_approvals'] ?? 0 ?></div>
        <div class="kpi-sub" style="color:var(--text-muted)">Bills, funds &amp; vendor payments</div>
        <?php if (($stats['pending_approvals'] ?? 0) > 0): ?>
          <span class="kpi-badge" style="background:rgba(99,102,241,.12);color:var(--cd-indigo)">PENDING</span>
        <?php endif; ?>
      </a>

      <!-- 7. Budget Requests -->
      <a href="<?= $base ?>/con-budget-extends" class="kpi-card card__wrapper">
        <div class="kpi-accent" style="background:var(--cd-teal)"></div>
        <div class="kpi-icon" style="background:rgba(20,184,166,.12);color:var(--cd-teal)"><i
            class="fal fa-file-invoice-dollar"></i></div>
        <div class="kpi-label" style="color:var(--text-muted)">Budget Requests</div>
        <div class="kpi-value" style="color:var(--cd-teal)"><?= $stats['budget_requests'] ?? 0 ?></div>
        <div class="kpi-sub" style="color:var(--text-muted)">Pending extension requests</div>
        <?php if (($stats['budget_requests'] ?? 0) > 0): ?>
          <span class="kpi-badge" style="background:rgba(20,184,166,.12);color:var(--cd-teal)">REVIEW</span>
        <?php endif; ?>
      </a>

      <!-- 8. Lab Testing Alerts -->
      <a href="<?= $base ?>/con-soil-tests" class="kpi-card card__wrapper">
        <div class="kpi-accent" style="background:var(--cd-pink)"></div>
        <div class="kpi-icon" style="background:rgba(236,72,153,.12);color:var(--cd-pink)"><i class="fal fa-flask"></i>
        </div>
        <div class="kpi-label" style="color:var(--text-muted)">Lab Testing Alerts</div>
        <div class="kpi-value" style="color:var(--cd-pink)"><?= $stats['lab_alerts'] ?? 0 ?></div>
        <div class="kpi-sub" style="color:var(--text-muted)">Soil test records</div>
      </a>

    </div>

    <!-- ══════════════════════ PENDING INITIAL BUDGET APPROVALS ══════════════════════ -->
    <?php if (!empty($pendingInitialBudgets)): ?>
      <div class="con-panel card__wrapper mb-4" style="border-color:rgba(245,158,11,.3);background:rgba(245,158,11,.03)">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="sec-title" style="margin-bottom:0">
            <div class="sec-title-dot" style="background:var(--cd-amber)"></div>
            <i class="fal fa-clock me-2" style="color:var(--cd-amber)"></i>
            Pending Initial Budget Approvals
            <span style="margin-left:8px;font-size:9px;font-weight:700;padding:2px 10px;border-radius:20px;
        background:rgba(245,158,11,.12);color:var(--cd-amber)">
              <?= count($pendingInitialBudgets) ?> Awaiting
            </span>
          </div>
          <a href="<?= $base ?>/con-estimates" style="font-size:11px;color:var(--cd-blue);text-decoration:none">View All
            <i class="fal fa-arrow-right ms-1"></i></a>
        </div>
        <div style="overflow-x:auto">
          <table class="proj-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Project</th>
                <th>Submitted By</th>
                <th>Designation</th>
                <th>Grand Total</th>
                <th>Submitted On</th>
                <th style="text-align:right">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pendingInitialBudgets as $bi => $pb): ?>
                <tr>
                  <td style="font-size:11px;color:var(--text-muted)"><?= $bi + 1 ?></td>
                  <td>
                    <div style="font-weight:700;font-size:13px"><?= htmlspecialchars($pb['project_name'] ?? 'N/A') ?></div>
                  </td>
                  <td>
                    <div style="display:flex;align-items:center;gap:8px">
                      <div style="width:30px;height:30px;border-radius:50%;background:rgba(99,102,241,.1);
                display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="fal fa-user" style="font-size:12px;color:#6366f1"></i>
                      </div>
                      <span
                        style="font-size:12.5px;font-weight:600"><?= htmlspecialchars($pb['creator_name'] ?? '—') ?></span>
                    </div>
                  </td>
                  <td>
                    <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;
              background:rgba(139,92,246,.08);color:#8b5cf6">
                      <?= htmlspecialchars($pb['creator_designation'] ?: 'Estimator') ?>
                    </span>
                  </td>
                  <td style="font-weight:800;color:var(--cd-primary,#198754)">
                    <?= conFmt($pb['total_estimated_cost'] ?? 0) ?>
                  </td>
                  <td style="font-size:11px;color:var(--text-muted);white-space:nowrap">
                    <i class="fal fa-calendar me-1"></i>
                    <?= !empty($pb['created_at']) ? date('d M Y', strtotime($pb['created_at'])) : '—' ?>
                    <div style="font-size:10px;opacity:.75">
                      <?= !empty($pb['created_at']) ? date('h:i A', strtotime($pb['created_at'])) : '' ?>
                    </div>
                  </td>
                  <td style="text-align:right">
                    <a href="<?= $base ?>/con-estimate-show/<?= $pb['id'] ?>" style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;
                padding:5px 14px;border-radius:8px;text-decoration:none;
                background:linear-gradient(135deg,#059669,#10b981);color:#fff" onclick="event.stopPropagation()">
                      <i class="fal fa-check-circle"></i> Review & Approve
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
    // ── PRs Awaiting Approval (roles 3, 15, 22) ──────────────────────────────
    $prsForApproval = $prsForApproval ?? [];
    $sessionRoleId  = (int)($_SESSION['user']['role_id'] ?? 0);
    if (!empty($prsForApproval) && in_array($sessionRoleId, [3, 15, 22])):
    ?>
        <div class="con-panel card__wrapper mb-4" style="border-color:rgba(239,68,68,.4);background:rgba(239,68,68,.03)">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="sec-title" style="margin-bottom:0">
              <div class="sec-title-dot" style="background:#ef4444;animation:pulse 1.5s infinite"></div>
              <i class="fal fa-file-signature me-2" style="color:#ef4444"></i>
              Purchase Requests &mdash; Awaiting Approval
              <span style="margin-left:8px;font-size:9px;font-weight:700;padding:2px 10px;border-radius:20px;
                background:rgba(239,68,68,.12);color:#ef4444">
                <?= count($prsForApproval) ?> Pending
              </span>
            </div>
            <a href="<?= $base_url ?>/con-purchase-requests" class="btn btn-sm btn-outline-danger" style="font-size:11px">
              View All PRs →
            </a>
          </div>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle" style="font-size:12.5px">
              <thead class="table-light">
                <tr>
                  <th>#PR</th>
                  <th>Project</th>
                  <th>Status</th>
                  <th>Items</th>
                  <th>Created By</th>
                  <th>Date</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($prsForApproval as $apr):
                  $aprSt = $apr['status'] ?? 'Draft';
                  $aprBadge = $aprSt === 'Submitted' ? 'bg-primary' : 'bg-warning text-dark';
                ?>
                <tr <?= $aprSt === 'Submitted' ? 'class="table-info bg-opacity-10"' : '' ?>>
                  <td class="fw-bold" style="color:#4338ca">PR-<?= $apr['id'] ?></td>
                  <td><strong><?= htmlspecialchars($apr['project_name'] ?? 'N/A') ?></strong></td>
                  <td>
                    <span class="badge <?= $aprBadge ?> px-2" style="font-size:10px"><?= $aprSt ?></span>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border" style="font-size:10px"><?= (int)$apr['item_count'] ?> items</span>
                  </td>
                  <td class="text-muted" style="font-size:11px">
                    <?= htmlspecialchars($apr['created_by_name'] ?? '—') ?>
                    <?php if (!empty($apr['created_by_role'])): ?>
                      <small class="d-block text-muted" style="font-size:9px"><?= htmlspecialchars($apr['created_by_role']) ?></small>
                    <?php endif; ?>
                  </td>
                  <td class="text-muted" style="font-size:11px">
                    <?= !empty($apr['created_at']) ? date('d M Y', strtotime($apr['created_at'])) : '—' ?>
                  </td>
                  <td class="text-end" style="white-space:nowrap">
                    <a href="<?= $base_url ?>/con-purchase-request/<?= $apr['id'] ?>"
                       class="btn btn-sm btn-outline-primary" style="font-size:11px">
                      <i class="bi bi-eye"></i> View
                    </a>
                    <?php if ($aprSt === 'Submitted'): ?>
                    <form action="<?= $base_url ?>/con-purchase-request-approve/<?= $apr['id'] ?>" method="POST" class="d-inline ms-1"
                          onsubmit="return confirm('Approve PR-<?= $apr['id'] ?>?')">
                      <input type="hidden" name="status" value="Approved">
                      <button type="submit" class="btn btn-sm btn-success" style="font-size:11px">
                        <i class="bi bi-check-circle"></i> Approve
                      </button>
                    </form>
                    <form action="<?= $base_url ?>/con-purchase-request-approve/<?= $apr['id'] ?>" method="POST" class="d-inline ms-1"
                          onsubmit="return confirm('Reject PR-<?= $apr['id'] ?>?')">
                      <input type="hidden" name="status" value="Rejected">
                      <button type="submit" class="btn btn-sm btn-danger" style="font-size:11px">
                        <i class="bi bi-x-circle"></i> Reject
                      </button>
                    </form>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php
    $pendingLandRequisitions = $pendingLandRequisitions ?? [];
    if (!empty($pendingLandRequisitions) && in_array($sessionRoleId, [1, 3])):
    ?>
        <div class="con-panel card__wrapper mb-4" style="border-color:rgba(16,185,129,.4);background:rgba(16,185,129,.03)">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="sec-title" style="margin-bottom:0">
              <div class="sec-title-dot" style="background:#10b981;animation:pulse 1.5s infinite"></div>
              <i class="fal fa-file-invoice-dollar me-2" style="color:#10b981"></i>
              Land Fund Requisitions &mdash; Awaiting Management Approval
              <span style="margin-left:8px;font-size:9px;font-weight:700;padding:2px 10px;border-radius:20px;
                background:rgba(16,185,129,.12);color:#10b981">
                <?= count($pendingLandRequisitions) ?> Pending
              </span>
            </div>
            <a href="<?= $base_url ?>/con-land-requisitions" class="btn btn-sm btn-outline-success" style="font-size:11px">
              View All Requisitions →
            </a>
          </div>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle" style="font-size:12.5px">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Project & Landowner</th>
                  <th>Type</th>
                  <th>Amount</th>
                  <th>Requested By</th>
                  <th>Date</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pendingLandRequisitions as $lrIndex => $lreq): ?>
                <tr>
                  <td><?= $lrIndex + 1 ?></td>
                  <td>
                    <strong><?= htmlspecialchars($lreq['project_name'] ?? 'N/A') ?></strong>
                    <div style="font-size:11px; color:#6c757d;">Owner: <?= htmlspecialchars($lreq['land_owner_name'] ?? 'N/A') ?></div>
                  </td>
                  <td><span class="badge bg-secondary"><?= htmlspecialchars($lreq['requisition_type']) ?></span></td>
                  <td class="fw-bold text-success">BDT <?= number_format($lreq['requested_amount'], 2) ?></td>
                  <td><?= htmlspecialchars($lreq['requester_name'] ?? '—') ?></td>
                  <td class="text-muted" style="font-size:11px"><?= date('d M Y', strtotime($lreq['created_at'])) ?></td>
                  <td class="text-end">
                    <a href="<?= $base_url ?>/con-land-requisitions" class="btn btn-sm btn-success" style="font-size:11px">
                      <i class="bi bi-check-circle"></i> Review & Approve
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
    $pendingBudgetExtends = $pendingBudgetExtends ?? [];
    if (!empty($pendingBudgetExtends)):
      ?>
        <div class="con-panel card__wrapper mb-4" style="border-color:rgba(168,85,247,.3);background:rgba(168,85,247,.03)">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="sec-title" style="margin-bottom:0">
              <div class="sec-title-dot" style="background:#a855f7"></div>
              <i class="fal fa-file-invoice-dollar me-2" style="color:#a855f7"></i>
              Pending Budget Adjustments
              <span style="margin-left:8px;font-size:9px;font-weight:700;padding:2px 10px;border-radius:20px;
        background:rgba(168,85,247,.12);color:#a855f7">
                <?= count($pendingBudgetExtends) ?> Awaiting
              </span>
            </div>
            <a href="<?= $base ?>/con-budget-extends" style="font-size:11px;color:var(--cd-blue);text-decoration:none">View
              All <i class="fal fa-arrow-right ms-1"></i></a>
          </div>
          <div style="overflow-x:auto">
            <table class="proj-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Project</th>
                  <th>Requested By</th>
                  <th>Title & Reason</th>
                  <th>Amount (৳)</th>
                  <th>Requested On</th>
                  <th style="text-align:right">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pendingBudgetExtends as $bi => $be): ?>
                    <tr>
                      <td style="font-size:11px;color:var(--text-muted)"><?= $bi + 1 ?></td>
                      <td style="font-weight:700;font-size:13px"><?= htmlspecialchars($be['project_name'] ?? 'N/A') ?></td>
                      <td>
                        <div style="display:flex;align-items:center;gap:8px">
                          <div style="width:30px;height:30px;border-radius:50%;background:rgba(168,85,247,.1);
                display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <i class="fal fa-user" style="font-size:12px;color:#a855f7"></i>
                          </div>
                          <span
                            style="font-size:12.5px;font-weight:600"><?= htmlspecialchars($be['requester_name'] ?? '—') ?></span>
                        </div>
                      </td>
                      <td>
                        <div style="font-weight:700;font-size:12.5px"><?= htmlspecialchars($be['title']) ?></div>
                        <div
                          style="font-size:11px;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px"
                          title="<?= htmlspecialchars($be['reason']) ?>">
                          <?= htmlspecialchars($be['reason']) ?>
                        </div>
                      </td>
                      <td style="font-weight:800;color:<?= $be['amount'] > 0 ? '#ef4444' : '#10b981' ?>">
                        <?= $be['amount'] > 0 ? '+' : '' ?>         <?= number_format($be['amount'], 2) ?>
                      </td>
                      <td style="font-size:11px;color:var(--text-muted);white-space:nowrap">
                        <i class="fal fa-calendar me-1"></i>
                        <?= !empty($be['created_at']) ? date('d M Y', strtotime($be['created_at'])) : '—' ?>
                      </td>
                      <td style="text-align:right">
                        <button type="button" class="btn btn-sm"
                          style="background:#10b981;color:#fff;border-radius:8px;font-size:11px;font-weight:700;padding:5px 10px;"
                          data-bs-toggle="modal" data-bs-target="#approveBudgetModal" data-id="<?= $be['id'] ?>"
                          data-title="<?= htmlspecialchars($be['title']) ?>"><i class="fal fa-check me-1"></i> Approve</button>
                        <button type="button" class="btn btn-sm ms-1"
                          style="background:#ef4444;color:#fff;border-radius:8px;font-size:11px;font-weight:700;padding:5px 10px;"
                          data-bs-toggle="modal" data-bs-target="#rejectBudgetModal" data-id="<?= $be['id'] ?>"
                          data-title="<?= htmlspecialchars($be['title']) ?>"><i class="fal fa-times me-1"></i> Reject</button>
                      </td>
                    </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
    <?php endif; ?>

    <?php
    $pendingBoqs = $pendingBoqs ?? [];
    if (!empty($pendingBoqs)):
      ?>
        <div class="con-panel card__wrapper mb-4" style="border-color:rgba(139,92,246,.3);background:rgba(139,92,246,.03)">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="sec-title" style="margin-bottom:0">
              <div class="sec-title-dot" style="background:var(--cd-purple)"></div>
              <i class="fal fa-clipboard-list me-2" style="color:var(--cd-purple)"></i>
              Pending BOQ Approvals
              <span style="margin-left:8px;font-size:9px;font-weight:700;padding:2px 10px;border-radius:20px;
        background:rgba(139,92,246,.12);color:var(--cd-purple)">
                <?= count($pendingBoqs) ?> Awaiting
              </span>
            </div>
            <a href="<?= $base ?>/con-boq" style="font-size:11px;color:var(--cd-blue);text-decoration:none">View All <i
                class="fal fa-arrow-right ms-1"></i></a>
          </div>
          <div style="overflow-x:auto">
            <table class="proj-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Project</th>
                  <th>Created By</th>
                  <th>Designation</th>
                  <th>Version</th>
                  <th>Items</th>
                  <th>Grand Total</th>
                  <th>Status</th>
                  <th>Submitted On</th>
                  <th style="text-align:right">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pendingBoqs as $bi => $bq):
                  $bqStatus = $bq['status'] ?? 'Draft';
                  $bqSc = $bqStatus === 'Submitted'
                    ? ['bg' => 'rgba(59,130,246,.1)', 'text' => '#2563eb']
                    : ['bg' => 'rgba(245,158,11,.1)', 'text' => '#d97706'];
                  ?>
                    <tr>
                      <td style="font-size:11px;color:var(--text-muted)"><?= $bi + 1 ?></td>
                      <td style="font-weight:700;font-size:13px"><?= htmlspecialchars($bq['project_name'] ?? 'N/A') ?></td>
                      <td>
                        <div style="display:flex;align-items:center;gap:8px">
                          <div style="width:30px;height:30px;border-radius:50%;background:rgba(99,102,241,.1);
                display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <i class="fal fa-user" style="font-size:12px;color:#6366f1"></i>
                          </div>
                          <span
                            style="font-size:12.5px;font-weight:600"><?= htmlspecialchars($bq['creator_name'] ?? '—') ?></span>
                        </div>
                      </td>
                      <td>
                        <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;
              background:rgba(99,102,241,.08);color:#6366f1">
                          <?= htmlspecialchars($bq['creator_designation'] ?: 'Site Engineer') ?>
                        </span>
                      </td>
                      <td style="font-size:11.5px;color:var(--text-muted)">v<?= htmlspecialchars($bq['version'] ?? '1.0') ?>
                      </td>
                      <td style="font-weight:700;text-align:center"><?= (int) ($bq['item_count'] ?? 0) ?></td>
                      <td style="font-weight:800;color:var(--cd-primary,#198754)"><?= conFmt($bq['grand_total'] ?? 0) ?></td>
                      <td>
                        <span style="font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;
              background:<?= $bqSc['bg'] ?>;color:<?= $bqSc['text'] ?>">
                          <?= $bqStatus ?>
                        </span>
                      </td>
                      <td style="font-size:11px;color:var(--text-muted);white-space:nowrap">
                        <i class="fal fa-calendar me-1"></i>
                        <?= !empty($bq['created_at']) ? date('d M Y', strtotime($bq['created_at'])) : '—' ?>
                        <div style="font-size:10px;opacity:.75">
                          <?= !empty($bq['created_at']) ? date('h:i A', strtotime($bq['created_at'])) : '' ?>
                        </div>
                      </td>
                      <td style="text-align:right">
                        <a href="<?= $base ?>/con-boq-show/<?= $bq['id'] ?>" style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;
                padding:5px 14px;border-radius:8px;text-decoration:none;
                background:linear-gradient(135deg,#6d28d9,#8b5cf6);color:#fff" onclick="event.stopPropagation()">
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

    <?php
    $pendingTargetsPM = $pendingTargetsPM ?? [];
    if (!empty($pendingTargetsPM)):
      ?>
        <div class="con-panel card__wrapper mb-4" style="border-color:rgba(245,158,11,.35);background:rgba(245,158,11,.03)">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="sec-title" style="margin-bottom:0">
              <div class="sec-title-dot" style="background:var(--cd-amber)"></div>
              <i class="fal fa-bullseye me-2" style="color:var(--cd-amber)"></i>
              Pending Targets — PM Approval Required
              <span style="margin-left:8px;font-size:9px;font-weight:700;padding:2px 10px;border-radius:20px;
        background:rgba(245,158,11,.12);color:var(--cd-amber)">
                <?= count($pendingTargetsPM) ?> Awaiting
              </span>
            </div>
            <a href="<?= $base ?>/con-targets" style="font-size:11px;color:var(--cd-blue);text-decoration:none">View All <i
                class="fal fa-arrow-right ms-1"></i></a>
          </div>
          <div style="overflow-x:auto">
            <table class="proj-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Project</th>
                  <th>Target Title</th>
                  <th>Created By (SE)</th>

                  <th>Target %</th>
                  <th>Gain %</th>
                  <th>Period</th>
                  <th>Submitted On</th>
                  <th style="text-align:right">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pendingTargetsPM as $pi => $pt): ?>
                    <tr>
                      <td style="font-size:11px;color:var(--text-muted)"><?= $pi + 1 ?></td>
                      <td style="font-weight:700;font-size:13px"><?= htmlspecialchars($pt['project_name'] ?? 'N/A') ?></td>
                      <td><?= htmlspecialchars($pt['target_title'] ?? '—') ?></td>
                      <td>
                        <div style="display:flex;align-items:center;gap:8px">
                          <div style="width:28px;height:28px;border-radius:50%;background:rgba(99,102,241,.1);
                display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <i class="fal fa-user" style="font-size:11px;color:#6366f1"></i>
                          </div>
                          <div>
                            <div style="font-size:12px;font-weight:600"><?= htmlspecialchars($pt['creator_name'] ?? '—') ?>
                            </div>
                            <div style="font-size:10px;color:var(--text-muted)">
                              <?= htmlspecialchars($pt['creator_designation'] ?: 'SE') ?>
                            </div>
                          </div>
                        </div>
                      </td>

                      <td><span
                          style="font-size:11px;font-weight:700;color:#2563eb"><?= number_format($pt['target_percentage'] ?? 0, 1) ?>%</span>
                      </td>
                      <td>
                        <?php $g2 = (float) ($pt['gain_percentage'] ?? 0);
                        $t2 = (float) ($pt['target_percentage'] ?? 0); ?>
                        <span style="font-size:11px;font-weight:700;color:<?= $g2 >= $t2 ? '#059669' : '#d97706' ?>">
                          <?= number_format($g2, 1) ?>%
                        </span>
                      </td>
                      <td style="font-size:11px;color:var(--text-muted)">
                        <?= $pt['year'] ?? '' ?>         <?= $pt['week_number'] ? ' / Wk ' . $pt['week_number'] : '' ?>
                      </td>
                      <td style="font-size:11px;color:var(--text-muted);white-space:nowrap">
                        <i class="fal fa-calendar me-1"></i>
                        <?= !empty($pt['created_at']) ? date('d M Y', strtotime($pt['created_at'])) : '—' ?>
                      </td>
                      <td style="text-align:right">
                        <a href="<?= $base ?>/con-target/<?= $pt['id'] ?>" style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;
                padding:5px 14px;border-radius:8px;text-decoration:none;
                background:linear-gradient(135deg,#059669,#10b981);color:#fff" onclick="event.stopPropagation()">
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

    <?php
    $pendingGRNsPM = $pendingGRNsPM ?? [];
    if (!empty($pendingGRNsPM)):
      ?>
        <div class="con-panel card__wrapper mb-4" style="border-color:rgba(139,92,246,.35);background:rgba(139,92,246,.03)">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="sec-title" style="margin-bottom:0">
              <div class="sec-title-dot" style="background:#8b5cf6"></div>
              <i class="fal fa-file-check me-2" style="color:#7c3aed"></i>
              Pending Material Receivings (GRN) &mdash; PM Approval Required
              <span
                style="margin-left:8px;font-size:9px;font-weight:700;padding:2px 10px;border-radius:20px;background:rgba(139,92,246,.12);color:#7c3aed">
                <?= count($pendingGRNsPM) ?> Awaiting
              </span>
            </div>
            <a href="<?= $base ?>/con-material-receivings" class="cd-btn-outline"
              style="border-color:#8b5cf6;color:#8b5cf6">
              View All <i class="fal fa-arrow-right ms-1"></i>
            </a>
          </div>

          <div class="table-responsive">
            <table class="table con-table mb-0">
              <thead>
                <tr>
                  <th style="width:5%">#</th>
                  <th style="width:10%">GRN</th>
                  <th style="width:25%">Project</th>
                  <th style="width:15%">Receiving Date</th>
                  <th style="width:20%">Received By</th>
                  <th style="width:15%">QC Status</th>
                  <th style="width:10%;text-align:right">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pendingGRNsPM as $i => $pm): ?>
                    <tr onclick="window.location='<?= $base ?>/con-material-receiving-qc/<?= $pm['id'] ?>'"
                      style="cursor:pointer" class="tr-hover">
                      <td style="font-size:11px;color:var(--text-muted)"><?= $i + 1 ?></td>
                      <td style="font-weight:700;font-size:12px;color:#7c3aed">GRN-<?= $pm['id'] ?></td>
                      <td style="font-weight:600;font-size:12px"><?= htmlspecialchars($pm['project_name'] ?? '—') ?></td>
                      <td style="font-size:11px;color:var(--text-muted);white-space:nowrap">
                        <i class="fal fa-calendar me-1"></i>
                        <?= !empty($pm['receiving_date']) ? date('d M Y', strtotime($pm['receiving_date'])) : '—' ?>
                      </td>
                      <td>
                        <div style="display:flex;align-items:center;gap:8px">
                          <div style="width:28px;height:28px;border-radius:50%;background:rgba(99,102,241,.1);
                display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <i class="fal fa-user" style="font-size:11px;color:#6366f1"></i>
                          </div>
                          <div>
                            <div style="font-size:12px;font-weight:600"><?= htmlspecialchars($pm['received_by_name'] ?? '—') ?>
                            </div>
                          </div>
                        </div>
                      </td>
                      <td>
                        <span style="font-size:10px;font-weight:700;padding:3px 9px;border-radius:20px;
              background:rgba(16,185,129,.12);color:#059669">
                          ✓ QC Done
                        </span>
                      </td>
                      <td style="text-align:right">
                        <a href="<?= $base ?>/con-material-receiving-qc/<?= $pm['id'] ?>" style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;
                padding:5px 14px;border-radius:8px;text-decoration:none;
                background:linear-gradient(135deg,#8b5cf6,#6d28d9);color:#fff" onclick="event.stopPropagation()">
                          <i class="fal fa-check-circle"></i> Acknowledge
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
    $pendingPOsForSE = $pendingPOsForSE ?? [];
    if (!empty($pendingPOsForSE)):
      ?>
        <div class="con-panel card__wrapper mb-4" style="border-color:rgba(16,185,129,.35);background:rgba(16,185,129,.03)">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="sec-title" style="margin-bottom:0">
              <div class="sec-title-dot" style="background:var(--cd-green)"></div>
              <i class="fal fa-hard-hat me-2" style="color:#059669"></i>
              Pending Purchase Orders &mdash; SE Approval Required
              <span
                style="margin-left:8px;font-size:9px;font-weight:700;padding:2px 10px;border-radius:20px;background:rgba(16,185,129,.12);color:#059669">
                <?= count($pendingPOsForSE) ?> Awaiting
              </span>
            </div>
            <a href="<?= $base ?>/con-purchase-orders" style="font-size:11px;color:#059669;text-decoration:none">View All <i
                class="fal fa-arrow-right ms-1"></i></a>
          </div>
          <div style="overflow-x:auto">
            <table class="proj-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>PO</th>
                  <th>Project</th>
                  <th>Order Date</th>
                  <th>PM Status</th>
                  <th>Created By</th>
                  <th style="text-align:right">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pendingPOsForSE as $pi => $po): ?>
                    <tr>
                      <td style="font-size:11px;color:var(--text-muted)"><?= $pi + 1 ?></td>
                      <td style="font-weight:800;color:#059669">PO-<?= $po['id'] ?></td>
                      <td style="font-weight:700;font-size:13px"><?= htmlspecialchars($po['project_name'] ?? '&mdash;') ?></td>
                      <td style="font-size:11px;color:var(--text-muted)">
                        <?= !empty($po['order_date']) ? date('d M Y', strtotime($po['order_date'])) : '&mdash;' ?>
                      </td>
                      <td><?php $pmOk = !empty($po['approved_by_pm']); ?><span
                          style="font-size:10px;font-weight:700;padding:3px 9px;border-radius:20px;background:<?= $pmOk ? 'rgba(16,185,129,.12)' : ' rgba(239,68,68,.1)' ?>;color:<?= $pmOk ? '#059669' : '#dc2626' ?>"><?= $pmOk ? '&#10003; PM Approved' : '&#x23f3; PM Pending' ?></span>
                      </td>
                      <td style="font-size:12px"><?= htmlspecialchars($po['created_by_name'] ?? '&mdash;') ?></td>
                      <td style="text-align:right"><a href="<?= $base ?>/con-purchase-order/<?= $po['id'] ?>"
                          style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:5px 14px;border-radius:8px;text-decoration:none;background:linear-gradient(135deg,#059669,#10b981);color:#fff"><i
                            class="fal fa-check-circle"></i> Review &amp; Approve</a></td>
                    </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
    <?php endif; ?>

    <?php
    $pendingPOsForPM = $pendingPOsForPM ?? [];
    if (!empty($pendingPOsForPM)):
      ?>
        <div class="con-panel card__wrapper mb-4" style="border-color:rgba(59,130,246,.35);background:rgba(59,130,246,.03)">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="sec-title" style="margin-bottom:0">
              <div class="sec-title-dot" style="background:var(--cd-blue)"></div>
              <i class="fal fa-file-contract me-2" style="color:var(--cd-blue)"></i>
              Pending Purchase Orders &mdash; Management/PM Approval Required
              <span
                style="margin-left:8px;font-size:9px;font-weight:700;padding:2px 10px;border-radius:20px;background:rgba(59,130,246,.12);color:var(--cd-blue)">
                <?= count($pendingPOsForPM) ?> Awaiting
              </span>
            </div>
            <a href="<?= $base ?>/con-purchase-orders" style="font-size:11px;color:var(--cd-blue);text-decoration:none">View
              All <i class="fal fa-arrow-right ms-1"></i></a>
          </div>
          <div style="overflow-x:auto">
            <table class="proj-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>PO</th>
                  <th>Project</th>
                  <th>Order Date</th>
                  <th>SE Status</th>
                  <th>Created By</th>
                  <th style="text-align:right">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pendingPOsForPM as $pi => $po): ?>
                    <tr>
                      <td style="font-size:11px;color:var(--text-muted)"><?= $pi + 1 ?></td>
                      <td style="font-weight:800;color:var(--cd-blue)">PO-<?= $po['id'] ?></td>
                      <td style="font-weight:700;font-size:13px"><?= htmlspecialchars($po['project_name'] ?? '&mdash;') ?></td>
                      <td style="font-size:11px;color:var(--text-muted)">
                        <?= !empty($po['order_date']) ? date('d M Y', strtotime($po['order_date'])) : '&mdash;' ?>
                      </td>
                      <td><?php $seOk = !empty($po['approved_by_se']); ?><span
                          style="font-size:10px;font-weight:700;padding:3px 9px;border-radius:20px;background:<?= $seOk ? 'rgba(16,185,129,.12)' : ' rgba(239,68,68,.1)' ?>;color:<?= $seOk ? '#059669' : '#dc2626' ?>"><?= $seOk ? '&#10003; SE Approved' : '&#x23f3; SE Pending' ?></span>
                      </td>
                      <td style="font-size:12px"><?= htmlspecialchars($po['created_by_name'] ?? '&mdash;') ?></td>
                      <td style="text-align:right"><a href="<?= $base ?>/con-purchase-order/<?= $po['id'] ?>"
                          style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:5px 14px;border-radius:8px;text-decoration:none;background:linear-gradient(135deg,#1d4ed8,#3b82f6);color:#fff"><i
                            class="fal fa-check-circle"></i> Review &amp; Approve</a></td>
                    </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
    <?php endif; ?>

    <?php
    $pendingWeeklyReportsPM = $pendingWeeklyReportsPM ?? [];
    if (!empty($pendingWeeklyReportsPM)):
      ?>
        <div class="con-panel card__wrapper mb-4" style="border-color:rgba(16,185,129,.35);background:rgba(16,185,129,.03)">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="sec-title" style="margin-bottom:0">
              <div class="sec-title-dot" style="background:#0f5132"></div>
              <i class="fal fa-calendar-week me-2" style="color:#0f5132"></i>
              Pending Weekly Reports &mdash; PM Approval Required
              <span
                style="margin-left:8px;font-size:9px;font-weight:700;padding:2px 10px;border-radius:20px;background:rgba(16,185,129,.12);color:#0f5132">
                <?= count($pendingWeeklyReportsPM) ?> Awaiting
              </span>
            </div>
            <a href="<?= $base ?>/con-weekly-reports" style="font-size:11px;color:#0f5132;text-decoration:none">View All <i
                class="fal fa-arrow-right ms-1"></i></a>
          </div>
          <div style="overflow-x:auto">
            <table class="proj-table">
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
                <?php foreach ($pendingWeeklyReportsPM as $pi => $rep): ?>
                    <tr>
                      <td style="font-size:11px;color:var(--text-muted)"><?= $pi + 1 ?></td>
                      <td style="font-weight:800;color:#0f5132">
                        <?= !empty($rep['report_date']) ? date('d M Y', strtotime($rep['report_date'])) : '&mdash;' ?>
                      </td>
                      <td style="font-weight:700;font-size:13px"><?= htmlspecialchars($rep['project_name'] ?? '&mdash;') ?></td>
                      <td style="font-size:12px">Week <?= (int) $rep['week_no'] ?></td>
                      <td style="font-size:12px"><?= htmlspecialchars($rep['created_by_name'] ?? '&mdash;') ?></td>
                      <td style="text-align:right"><a href="<?= $base ?>/con-weekly-report/<?= $rep['id'] ?>"
                          style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:5px 14px;border-radius:8px;text-decoration:none;background:linear-gradient(135deg,#0f5132,#198754);color:#fff"><i
                            class="fal fa-check-circle"></i> Review &amp; Approve</a></td>
                    </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
    <?php endif; ?>

    <?php
    $recentMaterialIssues = $recentMaterialIssues ?? [];
    if (!empty($recentMaterialIssues)):
      ?>
        <div class="con-panel card__wrapper mb-4" style="border-color:rgba(236,72,153,.35);background:rgba(236,72,153,.03)">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="sec-title" style="margin-bottom:0">
              <div class="sec-title-dot" style="background:var(--cd-pink)"></div>
              <i class="fal fa-boxes-stacked me-2" style="color:var(--cd-pink)"></i>
              Recent Material Issues (Stock OUT)
            </div>
            <a href="<?= $base ?>/con-material-issues" style="font-size:11px;color:var(--cd-pink);text-decoration:none">View
              All <i class="fal fa-arrow-right ms-1"></i></a>
          </div>
          <div style="overflow-x:auto">
            <table class="proj-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Date</th>
                  <th>Project</th>
                  <th>Material</th>
                  <th>Qty</th>
                  <th>Purpose</th>
                  <th>Issued By</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentMaterialIssues as $pi => $issue): ?>
                    <tr>
                      <td style="font-size:11px;color:var(--text-muted)"><?= $pi + 1 ?></td>
                      <td style="font-weight:800;color:var(--cd-pink)">
                        <?= !empty($issue['issue_date']) ? date('d M Y', strtotime($issue['issue_date'])) : '&mdash;' ?>
                      </td>
                      <td style="font-weight:700;font-size:13px"><?= htmlspecialchars($issue['project_name'] ?? '&mdash;') ?>
                      </td>
                      <td style="font-weight:600"><?= htmlspecialchars($issue['material_name'] ?? '&mdash;') ?> <span
                          class="text-muted small">(<?= htmlspecialchars($issue['unit'] ?? '') ?>)</span></td>
                      <td style="font-weight:800;color:var(--cd-pink)"><?= number_format($issue['quantity'], 2) ?></td>
                      <td style="font-size:11px"><?= htmlspecialchars($issue['purpose'] ?? '&mdash;') ?></td>
                      <td style="font-size:11px"><?= htmlspecialchars($issue['issued_by_name'] ?? '&mdash;') ?></td>
                    </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
    <?php endif; ?>


    <?php
    $pendingGRNs = $pendingGRNs ?? [];
    if (!empty($pendingGRNs)):
      ?>
        <div class="con-panel card__wrapper mb-4" style="border-color:rgba(139,92,246,.35);background:rgba(139,92,246,.03)">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="sec-title" style="margin-bottom:0">
              <div class="sec-title-dot" style="background:var(--cd-purple,#8b5cf6)"></div>
              <i class="fal fa-box-open me-2" style="color:var(--cd-purple,#8b5cf6)"></i>
              Pending Material Receivings (GRN) &mdash; Awaiting QC
              <span
                style="margin-left:8px;font-size:9px;font-weight:700;padding:2px 10px;border-radius:20px;background:rgba(139,92,246,.12);color:var(--cd-purple,#8b5cf6)">
                <?= count($pendingGRNs) ?> Awaiting
              </span>
            </div>
            <a href="<?= $base ?>/con-material-receivings"
              style="font-size:11px;color:var(--cd-purple,#8b5cf6);text-decoration:none">View All <i
                class="fal fa-arrow-right ms-1"></i></a>
          </div>
          <div style="overflow-x:auto">
            <table class="proj-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>GRN</th>
                  <th>Project</th>
                  <th>Receiving Date</th>
                  <th>Logged By</th>
                  <th style="text-align:right">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pendingGRNs as $pi => $grn): ?>
                    <tr>
                      <td style="font-size:11px;color:var(--text-muted)"><?= $pi + 1 ?></td>
                      <td style="font-weight:800;color:var(--cd-purple,#8b5cf6)">GRN-<?= $grn['id'] ?></td>
                      <td style="font-weight:700;font-size:13px"><?= htmlspecialchars($grn['project_name'] ?? '&mdash;') ?></td>
                      <td style="font-size:11px;color:var(--text-muted)">
                        <?= !empty($grn['receiving_date']) ? date('d M Y', strtotime($grn['receiving_date'])) : '&mdash;' ?>
                      </td>
                      <td style="font-size:12px"><?= htmlspecialchars($grn['received_by_name'] ?? '&mdash;') ?></td>
                      <td style="text-align:right"><a href="<?= $base ?>/con-material-receiving-qc/<?= $grn['id'] ?>"
                          style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:5px 14px;border-radius:8px;text-decoration:none;background:linear-gradient(135deg,#7c3aed,#8b5cf6);color:#fff"><i
                            class="fal fa-eye"></i> View</a></td>
                    </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
    <?php endif; ?>

    <?php
    $pendingWorkOrders = $pendingWorkOrders ?? [];
    if (!empty($pendingWorkOrders)):
      ?>
        <div class="con-panel card__wrapper mb-4" style="border-color:rgba(14,165,233,.35);background:rgba(14,165,233,.03)">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="sec-title" style="margin-bottom:0">
              <div class="sec-title-dot" style="background:#0ea5e9"></div>
              <i class="fal fa-file-signature me-2" style="color:#0ea5e9"></i>
              Pending Work Orders &mdash; Management Approval Required
              <span
                style="margin-left:8px;font-size:9px;font-weight:700;padding:2px 10px;border-radius:20px;background:rgba(14,165,233,.12);color:#0ea5e9">
                <?= count($pendingWorkOrders) ?> Awaiting
              </span>
            </div>
            <a href="<?= $base ?>/con-work-orders" style="font-size:11px;color:#0ea5e9;text-decoration:none">View All <i
                class="fal fa-arrow-right ms-1"></i></a>
          </div>
          <div style="overflow-x:auto">
            <table class="proj-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Project</th>
                  <th>Work Order Title</th>
                  <th>Contractor</th>
                  <th>Total Amount</th>
                  <th>Created By</th>
                  <th>Date</th>
                  <th style="text-align:right">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pendingWorkOrders as $pi => $wo): ?>
                    <tr>
                      <td style="font-size:11px;color:var(--text-muted)"><?= $pi + 1 ?></td>
                      <td style="font-weight:700;font-size:13px"><?= htmlspecialchars($wo['project_name'] ?? '&mdash;') ?></td>
                      <td style="font-weight:600;color:#0ea5e9"><?= htmlspecialchars($wo['title'] ?? '&mdash;') ?></td>
                      <td style="font-size:12px"><?= htmlspecialchars($wo['contractor_name'] ?? '&mdash;') ?></td>
                      <td style="font-weight:800;color:var(--cd-primary,#198754)"><?= conFmt($wo['total_amount'] ?? 0) ?></td>
                      <td style="font-size:12px">
                        <div style="display:flex;align-items:center;gap:8px">
                          <div style="width:28px;height:28px;border-radius:50%;background:rgba(14,165,233,.1);
                display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <i class="fal fa-user" style="font-size:11px;color:#0ea5e9"></i>
                          </div>
                          <div>
                            <div style="font-size:12px;font-weight:600">
                              <?= htmlspecialchars($wo['creator_name'] ?? '&mdash;') ?>
                            </div>
                          </div>
                        </div>
                      </td>
                      <td style="font-size:11px;color:var(--text-muted)">
                        <?= !empty($wo['created_at']) ? date('d M Y', strtotime($wo['created_at'])) : '&mdash;' ?>
                      </td>
                      <td style="text-align:right"><a href="<?= $base ?>/con-work-order/<?= $wo['id'] ?>"
                          style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:5px 14px;border-radius:8px;text-decoration:none;background:linear-gradient(135deg,#0284c7,#0ea5e9);color:#fff"><i
                            class="fal fa-check-circle"></i> Review &amp; Approve</a></td>
                    </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
    <?php endif; ?>

    <?php
    $pendingReturns = $pendingReturns ?? [];
    if (!empty($pendingReturns)):
      ?>
        <div class="con-panel card__wrapper mb-4" style="border-color:rgba(239,68,68,.35);background:rgba(239,68,68,.03)">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="sec-title" style="margin-bottom:0">
              <div class="sec-title-dot" style="background:var(--cd-red,#ef4444)"></div>
              <i class="fal fa-undo me-2" style="color:var(--cd-red,#ef4444)"></i>
              Pending Material Return Requests
              <span
                style="margin-left:8px;font-size:9px;font-weight:700;padding:2px 10px;border-radius:20px;background:rgba(239,68,68,.12);color:var(--cd-red,#ef4444)">
                <?= count($pendingReturns) ?> Awaiting
              </span>
            </div>
            <a href="<?= $base ?>/con-material-returns"
              style="font-size:11px;color:var(--cd-red,#ef4444);text-decoration:none">View All <i
                class="fal fa-arrow-right ms-1"></i></a>
          </div>
          <div style="overflow-x:auto">
            <table class="proj-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>GRN Ref</th>
                  <th>Project</th>
                  <th>Vendor</th>
                  <th>Return Date</th>
                  <th>Logged By</th>
                  <th style="text-align:right">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pendingReturns as $pi => $ret): ?>
                    <tr>
                      <td style="font-size:11px;color:var(--text-muted)"><?= $pi + 1 ?></td>
                      <td style="font-weight:800;color:var(--cd-red,#ef4444)">GRN-<?= $ret['receiving_id'] ?></td>
                      <td style="font-weight:700;font-size:13px"><?= htmlspecialchars($ret['project_name'] ?? '&mdash;') ?></td>
                      <td style="font-size:12px"><?= htmlspecialchars($ret['vendor_name'] ?? '&mdash;') ?></td>
                      <td style="font-size:11px;color:var(--text-muted)">
                        <?= !empty($ret['return_date']) ? date('d M Y', strtotime($ret['return_date'])) : '&mdash;' ?>
                      </td>
                      <td style="font-size:12px"><?= htmlspecialchars($ret['created_by_name'] ?? '&mdash;') ?></td>
                      <td style="text-align:right"><a href="<?= $base ?>/con-material-returns"
                          style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:5px 14px;border-radius:8px;text-decoration:none;background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff"><i
                            class="fal fa-truck"></i> Process Return</a></td>
                    </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
    <?php endif; ?>

    <?php
    $pendingGRNsPM = $pendingGRNsPM ?? [];
    if (!empty($pendingGRNsPM)):
      ?>
        <div class="con-panel card__wrapper mb-4" style="border-color:rgba(59,130,246,.35);background:rgba(59,130,246,.03)">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="sec-title" style="margin-bottom:0">
              <div class="sec-title-dot" style="background:var(--cd-blue)"></div>
              <i class="fal fa-box-check me-2" style="color:var(--cd-blue)"></i>
              Pending Material Receivings (GRN) &mdash; PM Approval Required
              <span
                style="margin-left:8px;font-size:9px;font-weight:700;padding:2px 10px;border-radius:20px;background:rgba(59,130,246,.12);color:var(--cd-blue)">
                <?= count($pendingGRNsPM) ?> Awaiting
              </span>
            </div>
            <a href="<?= $base ?>/con-material-receivings"
              style="font-size:11px;color:var(--cd-blue);text-decoration:none">View All <i
                class="fal fa-arrow-right ms-1"></i></a>
          </div>
          <div style="overflow-x:auto">
            <table class="proj-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>GRN</th>
                  <th>Project</th>
                  <th>Receiving Date</th>
                  <th>Logged By</th>
                  <th style="text-align:right">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pendingGRNsPM as $pi => $grn): ?>
                    <tr>
                      <td style="font-size:11px;color:var(--text-muted)"><?= $pi + 1 ?></td>
                      <td style="font-weight:800;color:var(--cd-blue)">GRN-<?= $grn['id'] ?></td>
                      <td style="font-weight:700;font-size:13px"><?= htmlspecialchars($grn['project_name'] ?? '&mdash;') ?></td>
                      <td style="font-size:11px;color:var(--text-muted)">
                        <?= !empty($grn['receiving_date']) ? date('d M Y', strtotime($grn['receiving_date'])) : '&mdash;' ?>
                      </td>
                      <td style="font-size:12px"><?= htmlspecialchars($grn['received_by_name'] ?? '&mdash;') ?></td>
                      <td style="text-align:right"><a href="<?= $base ?>/con-material-receiving-qc/<?= $grn['id'] ?>"
                          style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:5px 14px;border-radius:8px;text-decoration:none;background:linear-gradient(135deg,#3b82f6,#60a5fa);color:#fff"><i
                            class="fal fa-check-circle"></i> Review &amp; Approve</a></td>
                    </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
    <?php endif; ?>

    <?php
    $pendingIncidents = $pendingIncidents ?? [];
    if (!empty($pendingIncidents)):
      ?>
        <div class="con-panel card__wrapper mb-4" style="border-color:rgba(239,68,68,.35);background:rgba(239,68,68,.03)">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="sec-title" style="margin-bottom:0">
              <div class="sec-title-dot" style="background:var(--cd-red)"></div>
              <i class="fal fa-exclamation-triangle me-2" style="color:var(--cd-red)"></i>
              Pending Security Incidents
              <span
                style="margin-left:8px;font-size:9px;font-weight:700;padding:2px 10px;border-radius:20px;background:rgba(239,68,68,.12);color:var(--cd-red)">
                <?= count($pendingIncidents) ?> Awaiting
              </span>
            </div>
            <a href="<?= $base ?>/con-security-incidents"
              style="font-size:11px;color:var(--cd-red);text-decoration:none">View All <i
                class="fal fa-arrow-right ms-1"></i></a>
          </div>
          <div style="overflow-x:auto">
            <table class="proj-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Incident Title</th>
                  <th>Project</th>
                  <th>Priority</th>
                  <th>Reported On</th>
                  <th style="text-align:right">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pendingIncidents as $pi => $inc): ?>
                    <tr>
                      <td style="font-size:11px;color:var(--text-muted)"><?= $pi + 1 ?></td>
                      <td style="font-weight:800;color:var(--cd-red)"><?= htmlspecialchars($inc['issue_title'] ?? '&mdash;') ?></td>
                      <td style="font-weight:700;font-size:13px"><?= htmlspecialchars($inc['project_name'] ?? '&mdash;') ?></td>
                      <td style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($inc['priority'] ?? '&mdash;') ?></td>
                      <td style="font-size:11px;color:var(--text-muted)">
                        <?= !empty($inc['reported_at']) ? date('d M Y', strtotime($inc['reported_at'])) : '&mdash;' ?>
                      </td>
                      <td style="text-align:right"><a href="<?= $base ?>/con-security-incidents/view?id=<?= $inc['id'] ?>"
                          style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:5px 14px;border-radius:8px;text-decoration:none;background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff"><i
                            class="fal fa-check-circle"></i> Resolve</a></td>
                    </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
    <?php endif; ?>

    <?php
    $pendingLogs = $pendingLogs ?? [];
    if (!empty($pendingLogs)):
      ?>
        <div class="con-panel card__wrapper mb-4" style="border-color:rgba(245,158,11,.35);background:rgba(245,158,11,.03)">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="sec-title" style="margin-bottom:0">
              <div class="sec-title-dot" style="background:var(--cd-amber)"></div>
              <i class="fal fa-clipboard-check me-2" style="color:var(--cd-amber)"></i>
              Pending Security Daily Logs (Issues Found)
              <span
                style="margin-left:8px;font-size:9px;font-weight:700;padding:2px 10px;border-radius:20px;background:rgba(245,158,11,.12);color:var(--cd-amber)">
                <?= count($pendingLogs) ?> Awaiting
              </span>
            </div>
            <a href="<?= $base ?>/con-security-logs"
              style="font-size:11px;color:var(--cd-amber);text-decoration:none">View All <i
                class="fal fa-arrow-right ms-1"></i></a>
          </div>
          <div style="overflow-x:auto">
            <table class="proj-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Log Date</th>
                  <th>Project</th>
                  <th>Remarks</th>
                  <th style="text-align:right">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pendingLogs as $pi => $log): ?>
                    <tr>
                      <td style="font-size:11px;color:var(--text-muted)"><?= $pi + 1 ?></td>
                      <td style="font-weight:800;color:var(--cd-amber)">
                        <?= !empty($log['log_date']) ? date('d M Y', strtotime($log['log_date'])) : '&mdash;' ?>
                      </td>
                      <td style="font-weight:700;font-size:13px"><?= htmlspecialchars($log['project_name'] ?? '&mdash;') ?></td>
                      <td style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($log['remarks'] ?? '&mdash;') ?></td>
                      <td style="text-align:right"><a href="<?= $base ?>/con-security-logs/view?id=<?= $log['id'] ?>"
                          style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:5px 14px;border-radius:8px;text-decoration:none;background:linear-gradient(135deg,#f59e0b,#fbbf24);color:#fff"><i
                            class="fal fa-check-circle"></i> Resolve</a></td>
                    </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
    <?php endif; ?>

    <?php
    $weekTargetNotifications = $weekTargetNotifications ?? [];
    $labDueNotifications = $labDueNotifications ?? [];
    $currentWeek = $currentWeek ?? (int) ceil(date('j') / 7);
    $currentMonth = $currentMonth ?? (int) date('n');
    $currentYear = $currentYear ?? (int) date('Y');
    $monthNames = [
      '',
      'January',
      'February',
      'March',
      'April',
      'May',
      'June',
      'July',
      'August',
      'September',
      'October',
      'November',
      'December'
    ];
    if (!empty($weekTargetNotifications) || !empty($labDueNotifications)):
      ?>
        <div class="con-panel card__wrapper mb-4" style="border-color:rgba(6,182,212,.35);background:rgba(6,182,212,.03)">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="sec-title" style="margin-bottom:0">
              <div class="sec-title-dot" style="background:#0891b2"></div>
              <i class="bi bi-bell-fill me-2" style="color:#0891b2"></i>
              This Week — Target Notifications
              <span style="margin-left:8px;font-size:9px;font-weight:700;padding:2px 10px;border-radius:20px;
        background:rgba(6,182,212,.12);color:#0891b2">
                <?= $monthNames[$currentMonth] ?>     <?= $currentYear ?> · Week <?= $currentWeek ?>
              </span>
            </div>
            <a href="<?= $base ?>/con-targets" style="font-size:11px;color:#0891b2;text-decoration:none">View All <i
                class="fal fa-arrow-right ms-1"></i></a>
          </div>

          <div class="row">
            <!-- Targets this week -->
            <div class="col-md-7">
              <div
                style="font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#0891b2;margin-bottom:10px">
                <i class="bi bi-bullseye me-1"></i> Active Targets This Week (<?= count($weekTargetNotifications) ?>)
              </div>
              <?php if (empty($weekTargetNotifications)): ?>
                  <div class="text-muted small text-center py-3">No targets assigned for this week.</div>
              <?php else: ?>
                  <?php foreach ($weekTargetNotifications as $wt):
                    $wtQC = !empty($wt['approved_by_qc']);
                    $wtPM = !empty($wt['approved_by_manager']);
                    $wtLab = !empty($wt['is_lab_test']);
                    ?>
                      <div class="d-flex align-items-start gap-2 mb-2 p-2 rounded border" style="background:rgba(6,182,212,.04)">
                        <div style="width:32px;height:32px;border-radius:9px;background:rgba(6,182,212,.12);
                      display:flex;align-items:center;justify-content:center;flex-shrink:0">
                          <i class="bi bi-bullseye" style="color:#0891b2;font-size:14px"></i>
                        </div>
                        <div style="flex:1;min-width:0">
                          <div style="font-size:12.5px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                            <a href="<?= $base ?>/con-target/<?= $wt['id'] ?>" style="text-decoration:none;color:inherit">
                              <?= htmlspecialchars($wt['target_title'] ?? '—') ?>
                            </a>
                          </div>
                          <div style="font-size:10.5px;color:var(--text-muted)">
                            <?= htmlspecialchars($wt['project_name'] ?? '—') ?> ·
                            Target: <strong><?= number_format($wt['target_percentage'] ?? 0, 1) ?>%</strong>
                            <?php if ($wtLab): ?>
                                <span
                                  style="background:rgba(6,182,212,.12);color:#0891b2;padding:1px 6px;border-radius:6px;font-size:9px;margin-left:4px">
                                  <i class="bi bi-flask me-1"></i>Lab Test
                                </span>
                            <?php endif; ?>
                          </div>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:3px;align-items:flex-end;flex-shrink:0">
                          <span style="font-size:9px;font-weight:700;padding:2px 7px;border-radius:8px;
              background:<?= $wtQC ? 'rgba(16,185,129,.12)' : 'rgba(245,158,11,.1)' ?>;
              color:<?= $wtQC ? '#059669' : '#d97706' ?>">
                            QC: <?= $wtQC ? '✓' : '⏳' ?>
                          </span>
                          <span style="font-size:9px;font-weight:700;padding:2px 7px;border-radius:8px;
              background:<?= $wtPM ? 'rgba(16,185,129,.12)' : 'rgba(245,158,11,.1)' ?>;
              color:<?= $wtPM ? '#059669' : '#d97706' ?>">
                            PM: <?= $wtPM ? '✓' : '⏳' ?>
                          </span>
                        </div>
                      </div>
                  <?php endforeach; ?>
              <?php endif; ?>
            </div>

            <!-- Lab test due this week -->
            <div class="col-md-5">
              <div
                style="font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#dc2626;margin-bottom:10px">
                <i class="bi bi-flask me-1"></i> Lab Tests Due This Week (<?= count($labDueNotifications) ?>)
              </div>
              <?php if (empty($labDueNotifications)): ?>
                  <div class="text-muted small text-center py-3">No lab tests due this week.</div>
              <?php else: ?>
                  <?php foreach ($labDueNotifications as $ld): ?>
                      <div class="d-flex align-items-center gap-2 mb-2 p-2 rounded border"
                        style="background:rgba(239,68,68,.04);border-color:rgba(239,68,68,.2) !important">
                        <div style="width:30px;height:30px;border-radius:9px;background:rgba(239,68,68,.1);
                      display:flex;align-items:center;justify-content:center;flex-shrink:0">
                          <i class="bi bi-flask-fill" style="color:#dc2626;font-size:13px"></i>
                        </div>
                        <div style="flex:1;min-width:0">
                          <div style="font-size:12px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                            <a href="<?= $base ?>/con-target/<?= $ld['id'] ?>" style="text-decoration:none;color:inherit">
                              <?= htmlspecialchars($ld['target_title'] ?? '—') ?>
                            </a>
                          </div>
                          <div style="font-size:10.5px;color:var(--text-muted)">
                            <?= htmlspecialchars($ld['project_name'] ?? '—') ?>
                          </div>
                        </div>
                        <div style="text-align:right;flex-shrink:0">
                          <div style="font-size:10px;font-weight:700;color:#dc2626">
                            <?= !empty($ld['lab_test_date']) ? date('d M', strtotime($ld['lab_test_date'])) : '—' ?>
                          </div>
                          <div style="font-size:9px;color:var(--text-muted)">Due date</div>
                        </div>
                      </div>
                  <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
    <?php endif; ?>


     <!-- ════════════════ ROW 5: SMART ALERTS + LAB ALERTS + STOCK ═══════════════ -->
    <div class="row  mb-4">

      <!-- Smart Alerts -->
      <div class="col-4">
        <div class="con-panel card__wrapper h-100">
          <div class="sec-title">
            <div class="sec-title-dot" style="background:var(--cd-red)"></div>
            Smart Alerts
          </div>

          <?php if (($stats['stock_alerts'] ?? 0) > 0): ?>
            <a href="<?= $base ?>/con-stocks" class="alert-item"
              style="background:rgba(239,68,68,.05);text-decoration:none">
              <div class="alert-icon-wrap" style="background:rgba(239,68,68,.12);color:var(--cd-red)"><i
                  class="fal fa-warehouse"></i></div>
              <div>
                <div class="alert-item-title"><?= $stats['stock_alerts'] ?> Low Stock
                  Item<?= $stats['stock_alerts'] > 1 ? 's' : '' ?></div>
                <div class="alert-item-sub" style="color:var(--text-muted)">Materials below minimum level</div>
              </div>
            </a>
          <?php endif; ?>

          <?php if (($stats['pending_approvals'] ?? 0) > 0): ?>
            <a href="<?= $base ?>/con-pending-payments" class="alert-item"
              style="background:rgba(99,102,241,.05);text-decoration:none">
              <div class="alert-icon-wrap" style="background:rgba(99,102,241,.12);color:var(--cd-indigo)"><i
                  class="fal fa-hourglass-half"></i></div>
              <div>
                <div class="alert-item-title"><?= $stats['pending_approvals'] ?> Pending
                  Approval<?= $stats['pending_approvals'] > 1 ? 's' : '' ?></div>
                <div class="alert-item-sub" style="color:var(--text-muted)">Bills &amp; payments awaiting action</div>
              </div>
            </a>
          <?php endif; ?>

          <?php if (($stats['budget_requests'] ?? 0) > 0): ?>
            <a href="<?= $base ?>/con-budget-extends" class="alert-item"
              style="background:rgba(20,184,166,.05);text-decoration:none">
              <div class="alert-icon-wrap" style="background:rgba(20,184,166,.12);color:var(--cd-teal)"><i
                  class="fal fa-file-invoice-dollar"></i></div>
              <div>
                <div class="alert-item-title"><?= $stats['budget_requests'] ?> Budget
                  Extension<?= $stats['budget_requests'] > 1 ? 's' : '' ?></div>
                <div class="alert-item-sub" style="color:var(--text-muted)">Pending review &amp; approval</div>
              </div>
            </a>
          <?php endif; ?>

          <?php if (empty($pendingAlerts) && ($stats['stock_alerts'] ?? 0) == 0 && ($stats['pending_approvals'] ?? 0) == 0 && ($stats['budget_requests'] ?? 0) == 0): ?>
            <div class="empty-state">
              <div class="empty-state-icon"><i class="fal fa-check-circle" style="color:var(--cd-green)"></i></div>
              <div class="empty-state-text" style="color:var(--text-muted)">All clear — no alerts</div>
            </div>
          <?php endif; ?>

          <!-- Recent pending items list -->
          <?php if (!empty($pendingAlerts)): ?>
            <div style="margin-top:12px;padding-top:12px;border-top:1px solid rgba(0,0,0,.07)">
              <div
                style="font-size:10px;font-weight:600;letter-spacing:1px;text-transform:uppercase;color:var(--text-muted);margin-bottom:8px">
                Recent</div>
              <?php foreach (array_slice($pendingAlerts, 0, 3) as $pa): ?>
                <div
                  style="display:flex;justify-content:space-between;align-items:center;font-size:12px;padding:6px 0;border-bottom:1px solid rgba(0,0,0,.05)">
                  <span>
                    <a href="<?= $base ?><?= $pa['url'] ?? '#' ?>" style="text-decoration:none;color:inherit;">
                      <?= $pa['description'] ?>
                    </a>
                  </span>
                  <span style="font-weight:600;color:var(--cd-amber)"><?= conFmt($pa['amount'] ?? 0) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Lab Testing Alerts -->
      <div class="col-4">
        <div class="con-panel card__wrapper h-100">
          <div class="sec-title">
            <div class="sec-title-dot" style="background:var(--cd-pink)"></div>
            Lab Testing Alerts
            <a href="<?= $base ?>/con-soil-tests"
              style="font-size:10px;color:var(--cd-blue);text-decoration:none;margin-left:auto">View All <i
                class="fal fa-arrow-right ms-1"></i></a>
          </div>
          <?php if (empty($soilAlerts)): ?>
            <div class="empty-state">
              <div class="empty-state-icon"><i class="fal fa-flask"></i></div>
              <div class="empty-state-text" style="color:var(--text-muted)">No soil test records</div>
            </div>
          <?php else: ?>
            <?php foreach ($soilAlerts as $st): ?>
              <a href="<?= $base ?>/con-soil-tests" class="alert-item"
                style="background:rgba(236,72,153,.05);text-decoration:none">
                <div class="alert-icon-wrap" style="background:rgba(236,72,153,.12);color:var(--cd-pink)"><i
                    class="fal fa-flask"></i></div>
                <div>
                  <div class="alert-item-title"><?= htmlspecialchars($st['project_name'] ?? 'Project') ?></div>
                  <div class="alert-item-sub" style="color:var(--text-muted)">
                    Test: <?= $st['test_date'] ? date('d M Y', strtotime($st['test_date'])) : 'N/A' ?>
                    <?php if (!empty($st['lab_test_document_link'])): ?>
                      · <a href="<?= htmlspecialchars($st['lab_test_document_link']) ?>" target="_blank"
                        style="color:var(--cd-blue)" onclick="event.stopPropagation()"><i class="fal fa-link me-1"></i>Lab
                        Doc</a>
                    <?php endif; ?>
                    <?php if (!empty($st['field_test_doc_link'])): ?>
                      · <a href="<?= htmlspecialchars($st['field_test_doc_link']) ?>" target="_blank"
                        style="color:var(--cd-green)" onclick="event.stopPropagation()"><i class="fal fa-link me-1"></i>Field
                        Doc</a>
                    <?php endif; ?>
                  </div>
                </div>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Low Stock Items -->
      <div class="col-4">
        <div class="con-panel card__wrapper h-100">
          <div class="sec-title">
            <div class="sec-title-dot" style="background:var(--cd-red)"></div>
            Low Stock Items
            <a href="<?= $base ?>/con-stocks"
              style="font-size:10px;color:var(--cd-blue);text-decoration:none;margin-left:auto">Manage <i
                class="fal fa-arrow-right ms-1"></i></a>
          </div>
          <?php if (empty($stockAlerts)): ?>
            <div class="empty-state">
              <div class="empty-state-icon"><i class="fal fa-check-circle" style="color:var(--cd-green)"></i></div>
              <div class="empty-state-text" style="color:var(--text-muted)">Stock levels are healthy</div>
            </div>
          <?php else: ?>
            <?php foreach ($stockAlerts as $sk):
              $pct = $sk['min_stock_level'] > 0 ? min(100, round($sk['current_quantity'] / $sk['min_stock_level'] * 100)) : 100;
              ?>
              <div class="stock-item">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="stock-name"><?= htmlspecialchars($sk['material_name']) ?></div>
                    <div style="font-size:10px;color:var(--text-muted)"><i
                        class="fal fa-warehouse me-1"></i><?= htmlspecialchars($sk['warehouse_name']) ?></div>
                  </div>
                  <div style="text-align:right">
                    <div class="stock-qty" style="color:var(--cd-red)"><?= $sk['current_quantity'] ?> <span
                        style="font-size:11px"><?= $sk['unit'] ?></span></div>
                    <div style="font-size:10px;color:var(--text-muted)">Min: <?= $sk['min_stock_level'] ?></div>
                  </div>
                </div>
                <div class="stock-track">
                  <div class="stock-fill" style="width:<?= $pct ?>%"></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- ═══════════════ ROW 3: PROJECT PROGRESS + BUDGET DONUT ══════════════════ -->
    <div class="row  mb-4">

      <!-- Project Progress Overview -->
      <div class="col-8">
        <div class="con-panel card__wrapper h-100">
          <div class="sec-title">
            <div class="sec-title-dot" style="background:var(--cd-blue)"></div>
            Project Progress Overview
          </div>
          <?php if (empty($projects)): ?>
              <div class="empty-state">
                <div class="empty-state-icon"><i class="fal fa-chart-bar"></i></div>
                <div class="empty-state-text" style="color:var(--text-muted)">No projects found</div>
              </div>
          <?php else: ?>
              <?php foreach ($projects as $pr):
                $pct = round((float) ($pr['progress_pct'] ?? 0), 1);
                $color = $pct >= 75 ? 'var(--cd-green)' : ($pct >= 40 ? 'var(--cd-amber)' : 'var(--cd-red)');
                $grad = $pct >= 75 ? 'linear-gradient(90deg,#059669,#10b981)' : ($pct >= 40 ? 'linear-gradient(90deg,#d97706,#f59e0b)' : 'linear-gradient(90deg,#dc2626,#ef4444)');
                $sc = $statusColor[$pr['project_status'] ?? ''] ?? ['bg' => 'rgba(0,0,0,.06)', 'text' => 'var(--text-muted)'];
                ?>
                  <div class="prog-row" onclick="location.href='<?= $base ?>/con-project/<?= $pr['id'] ?>'"
                    style="cursor:pointer">
                    <div class="prog-meta">
                      <div>
                        <strong style="font-size:13px"><?= htmlspecialchars($pr['name']) ?></strong>
                        <span style="font-size:11px;color:var(--text-muted);margin-left:8px"><i
                            class="fal fa-map-marker-alt me-1"></i><?= htmlspecialchars($pr['location_name'] ?: 'N/A') ?></span>
                        <span class="status-pill ms-2"
                          style="background:<?= $sc['bg'] ?>;color:<?= $sc['text'] ?>"><?= $pr['project_status'] ?? 'N/A' ?></span>
                      </div>
                      <div style="text-align:right">
                        <span style="font-weight:700;font-size:13px;color:<?= $color ?>"><?= $pct ?>%</span>
                        <span style="font-size:10px;color:var(--text-muted);margin-left:8px"><?= conFmt($pr['total_budget']) ?>
                          budget</span>
                      </div>
                    </div>
                    <div class="prog-track">
                      <div class="prog-fill" style="width:<?= $pct ?>%;background:<?= $grad ?>"></div>
                    </div>
                  </div>
              <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Budget Utilization Donut -->
      <div class="col-4">
        <div class="con-panel card__wrapper h-100 text-center">
          <div class="sec-title justify-content-center">
            <div class="sec-title-dot" style="background:var(--cd-amber)"></div>
            Budget Utilization
          </div>
          <?php
          $pct = (float) ($budgetUtil['pct'] ?? 0);
          $deg = round($pct * 3.6);
          $clr = $pct > 85 ? '#ef4444' : ($pct > 60 ? '#f59e0b' : '#10b981');
          ?>
          <div class="donut-ring"
            style="background:conic-gradient(<?= $clr ?> 0% <?= $deg ?>deg, rgba(0,0,0,.08) <?= $deg ?>deg 360deg)">
            <div style="width:92px;height:92px;border-radius:50%;position:absolute;background:var(--card-bg,#fff)">
            </div>
            <div class="donut-inner">
              <div class="donut-pct" style="color:<?= $clr ?>"><?= $pct ?>%</div>
              <div class="donut-sub" style="color:var(--text-muted)">Used</div>
            </div>
          </div>
          <div class="donut-stats">
            <div class="donut-stat">
              <div class="donut-stat-val"><?= conFmt($budgetUtil['total']) ?></div>
              <div class="donut-stat-lbl" style="color:var(--text-muted)">Total Budget</div>
            </div>
            <div class="donut-stat">
              <div class="donut-stat-val" style="color:<?= $clr ?>"><?= conFmt($budgetUtil['spent']) ?></div>
              <div class="donut-stat-lbl" style="color:var(--text-muted)">Spent</div>
            </div>
            <div class="donut-stat">
              <div class="donut-stat-val" style="color:var(--cd-green)"><?= conFmt($budgetUtil['remaining']) ?></div>
              <div class="donut-stat-lbl" style="color:var(--text-muted)">Remaining</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════ ROW 4: PROJECT STATUS TABLE ══════════════════════ -->
    <div class="con-panel card__wrapper mb-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="sec-title" style="margin-bottom:0">
          <div class="sec-title-dot" style="background:var(--cd-indigo)"></div>
          Project Status Overview
        </div>
        <a href="<?= $base ?>/con-projects" style="font-size:11px;color:var(--cd-blue);text-decoration:none">View All <i
            class="fal fa-arrow-right ms-1"></i></a>
      </div>
      <table class="proj-table">
        <thead>
          <tr>
            <th>Project</th>
            <th>Floors</th>
            <th>Progress</th>
            <th>Budget Used</th>
            <th>Status</th>
            <th style="text-align:right">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($projects)): ?>
              <tr>
                <td colspan="6" class="empty-state">
                  <div class="empty-state-icon"><i class="fal fa-hard-hat"></i></div>
                  <div class="empty-state-text">No projects yet</div>
                </td>
              </tr>
          <?php else: ?>
              <?php foreach ($projects as $pr):
                $pct = round((float) ($pr['progress_pct'] ?? 0), 1);
                $clr = $pct >= 75 ? 'var(--cd-green)' : ($pct >= 40 ? 'var(--cd-amber)' : 'var(--cd-red)');
                $pclr = $pct >= 75 ? 'var(--cd-green)' : ($pct >= 40 ? 'var(--cd-amber)' : 'var(--cd-red)');
                $grad = $pct >= 75 ? 'linear-gradient(90deg,#059669,#10b981)' : ($pct >= 40 ? 'linear-gradient(90deg,#d97706,#f59e0b)' : 'linear-gradient(90deg,#dc2626,#ef4444)');
                $sc = $statusColor[$pr['project_status'] ?? ''] ?? ['bg' => 'rgba(0,0,0,.06)', 'text' => 'var(--text-muted)'];
                $budgetUsedPct = ($pr['total_budget'] > 0) ? round($pr['total_spent'] / $pr['total_budget'] * 100, 1) : 0;
                ?>
                  <tr class="proj-row" onclick="location.href='<?= $base ?>/con-project/<?= $pr['id'] ?>'">
                    <td>
                      <div class="proj-name-cell"><?= htmlspecialchars($pr['name']) ?></div>
                      <div class="proj-loc-cell" style="color:var(--text-muted)"><i
                          class="fal fa-map-marker-alt me-1"></i><?= htmlspecialchars($pr['location_name'] ?: 'N/A') ?></div>
                    </td>
                    <td style="font-weight:600"><?= $pr['total_floors'] ?: '—' ?></td>
                    <td>
                      <div style="display:flex;align-items:center;gap:8px;min-width:130px">
                        <div class="prog-track" style="flex:1">
                          <div class="prog-fill" style="width:<?= $pct ?>%;background:<?= $grad ?>"></div>
                        </div>
                        <span style="font-weight:700;font-size:12px;color:<?= $pclr ?>"><?= $pct ?>%</span>
                      </div>
                    </td>
                    <td>
                      <div style="font-weight:600"><?= conFmt($pr['total_spent']) ?></div>
                      <div style="font-size:10.5px;color:var(--text-muted)">of <?= conFmt($pr['total_budget']) ?>
                        (<?= $budgetUsedPct ?>%)</div>
                    </td>
                    <td>
                      <span class="status-pill" style="background:<?= $sc['bg'] ?>;color:<?= $sc['text'] ?>">
                        <?= htmlspecialchars($pr['project_status'] ?? 'Unknown') ?>
                      </span>
                    </td>
                    <td style="text-align:right">
                      <a href="<?= $base ?>/con-project/<?= $pr['id'] ?>" class="btn btn-sm"
                        style="font-size:11px;padding:4px 12px;background:rgba(59,130,246,.1);color:var(--cd-blue);border-radius:6px;text-decoration:none"
                        onclick="event.stopPropagation()">View <i class="fal fa-arrow-right ms-1"></i></a>
                    </td>
                  </tr>
              <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

   

    <div style="height:30px"></div>
  </div>
</div>

<!-- ================================================================ -->
<!-- Approve Budget Modal                                               -->
<!-- ================================================================ -->
<div class="modal fade" id="approveBudgetModal" tabindex="-1" aria-labelledby="approveBudgetModalLabel"
  aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="approveBudgetForm" method="POST">
        <div class="modal-header">
          <h5 class="modal-title text-success" id="approveBudgetModalLabel">
            <i class="fal fa-check-circle me-2"></i>Approve Budget Adjustment
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Approving: <strong id="approveBudgetTitleText"></strong></p>
          <label class="form-label fw-bold">Remarks (optional)</label>
          <textarea name="remarks" class="form-control" rows="3" placeholder="Any note on the approval…"></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success px-4">
            <i class="fal fa-check-circle me-1"></i> Confirm Approve
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ================================================================ -->
<!-- Reject Budget Modal                                                -->
<!-- ================================================================ -->
<div class="modal fade" id="rejectBudgetModal" tabindex="-1" aria-labelledby="rejectBudgetModalLabel"
  aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="rejectBudgetForm" method="POST">
        <div class="modal-header">
          <h5 class="modal-title text-danger" id="rejectBudgetModalLabel">
            <i class="fal fa-times-circle me-2"></i>Reject Budget Adjustment
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Rejecting: <strong id="rejectBudgetTitleText"></strong></p>
          <label class="form-label fw-bold">
            Reason for Rejection <span class="text-danger">*</span>
          </label>
          <textarea name="remarks" class="form-control" rows="3"
            placeholder="Explain why this request is being rejected…" required></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger px-4">
            <i class="fal fa-times-circle me-1"></i> Confirm Reject
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var base = '<?= $base ?>';

    // Approve Budget modal
    var approveBudgetModalEl = document.getElementById('approveBudgetModal');
    if (approveBudgetModalEl) {
      approveBudgetModalEl.addEventListener('show.bs.modal', function (e) {
        var btn = e.relatedTarget;
        var id = btn.getAttribute('data-id');
        var title = btn.getAttribute('data-title');
        document.getElementById('approveBudgetTitleText').textContent = title;
        document.getElementById('approveBudgetForm').action = base + '/con-budget-extend-approve/' + id;
      });
    }

    // Reject Budget modal
    var rejectBudgetModalEl = document.getElementById('rejectBudgetModal');
    if (rejectBudgetModalEl) {
      rejectBudgetModalEl.addEventListener('show.bs.modal', function (e) {
        var btn = e.relatedTarget;
        var id = btn.getAttribute('data-id');
        var title = btn.getAttribute('data-title');
        document.getElementById('rejectBudgetTitleText').textContent = title;
        document.getElementById('rejectBudgetForm').action = base + '/con-budget-extend-reject/' + id;
      });
    }
  });
</script>
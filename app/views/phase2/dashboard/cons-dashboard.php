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

if (!function_exists('cpFmtPct')) {
  function cpFmtPct($n)
  {
    $n = (float)$n;
    if ($n <= 0) return '0.0%';
    if ($n < 0.1) return number_format($n, 3) . '%';
    return number_format($n, 1) . '%';
  }
}

if (!function_exists('toBengaliNum')) {
  function toBengaliNum($number) {
    return (string)$number;
  }
}

if (!function_exists('getBengaliMonthName')) {
  function getBengaliMonthName($dateStr) {
    if (empty($dateStr)) return '';
    $bengaliMonths = [
      'January' => 'জানুয়ারি', 'February' => 'ফেব্রুয়ারি', 'March' => 'মার্চ',
      'April' => 'এপ্রিল', 'May' => 'মে', 'June' => 'জুন',
      'July' => 'জুলাই', 'August' => 'আগস্ট', 'September' => 'সেপ্টেম্বর',
      'October' => 'অক্টোবর', 'November' => 'নভেম্বর', 'December' => 'ডিসেম্বর'
    ];
    $engMonth = date('F', strtotime($dateStr));
    return $bengaliMonths[$engMonth] ?? '';
  }
}

$stats = $stats ?? [];
$projects = $projects ?? [];
$selectedProjId = isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int)$_GET['project_id'] : null;
$filteredProjects = [];
foreach ($projects as $pr) {
  if (!$selectedProjId || $pr['id'] == $selectedProjId) {
    $filteredProjects[] = $pr;
  }
}
$budgetUtil = $budgetUtil ?? ['total' => 0, 'spent' => 0, 'pct' => 0, 'remaining' => 0];
$stockAlerts = $stockAlerts ?? [];
$pendingAlerts = $pendingAlerts ?? [];
$soilAlerts = $soilAlerts ?? [];
$pendingInitialBudgets = $pendingInitialBudgets ?? [];

$statusColor = [
  'Planning' => ['bg' => 'rgba(var(--clr-theme-primary-rgb),.12)', 'text' => 'var(--clr-theme-primary)'],
  'In Progress' => ['bg' => 'rgba(245,158,11,.12)', 'text' => '#f59e0b'],
  'On Hold' => ['bg' => 'rgba(239,68,68,.12)', 'text' => '#ef4444'],
  'Completed' => ['bg' => 'rgba(16,185,129,.12)', 'text' => '#10b981'],
];
?>
<div class="row">
  <div class="col-12">
    <style>
      :root {
        --cd-primary: var(--clr-theme-primary, #198754);
        --cd-dark: var(--clr-theme-primary, #198754);
        --cd-light: var(--clr-theme-secondary, #1abc9c);
        --cd-blue: #3b82f6;
        --cd-green: #10b981;
        --cd-amber: #f59e0b;
        --cd-red: #ef4444;
        --cd-purple: var(--clr-theme-secondary, #1abc9c);
        --cd-indigo: var(--clr-theme-primary, #198754);
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

      @media (max-width: 1200px) {
        .kpi-grid {
          grid-template-columns: repeat(2, 1fr);
        }
      }

      @media (max-width: 576px) {
        .kpi-grid {
          grid-template-columns: 1fr;
          gap: 10px;
        }
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

      /* Unified Table Grid Format from overview.php */
      .po-table-container {
          width: 100%;
          overflow-x: auto;
      }
      .po-table {
          width: 100%;
          border-collapse: collapse;
          font-size: 13px;
      }
      .po-table th {
          background: #fcfdfd;
          padding: 14px 16px;
          font-size: 10px;
          font-weight: 700;
          text-transform: uppercase;
          color: var(--text-muted, #64748b);
          letter-spacing: 0.5px;
          text-align: left;
          border-bottom: 1px solid rgba(0,0,0,0.05);
      }
      .po-table td {
          padding: 14px 16px;
          border-bottom: 1px solid rgba(0,0,0,0.04);
      }

      /* Custom Progress Bars */
      .prog-compare-wrap {
          width: 100%;
      }
      .prog-bar-container {
          width: 100%;
          border-radius: 99px;
          overflow: hidden;
      }
      .prog-bar-fill-planned {
          border-radius: 99px;
          transition: width 0.3s ease;
      }
      .prog-bar-fill-actual {
          border-radius: 99px;
          transition: width 0.3s ease;
      }

      /* Status Pills */
      .status-pill {
          display: inline-flex;
          align-items: center;
          gap: 5px;
          font-size: 10px;
          font-weight: 700;
          padding: 3px 10px;
          border-radius: 99px;
          letter-spacing: 0.2px;
      }
      .status-pill-dot {
          width: 6px;
          height: 6px;
          border-radius: 50%;
          display: inline-block;
      }
      .status-ontrack { background: rgba(25, 135, 84, 0.08); color: #198754; }
      .status-ontrack .status-pill-dot { background: #198754; }
      .status-atrisk { background: rgba(245, 158, 11, 0.08); color: #d97706; }
      .status-atrisk .status-pill-dot { background: #f59e0b; }
      .status-delayed { background: rgba(239, 68, 68, 0.08); color: #dc2626; }
      .status-delayed .status-pill-dot { background: #ef4444; }
      .status-planning { background: rgba(59, 130, 246, 0.08); color: #2563eb; }
      .status-planning .status-pill-dot { background: #3b82f6; }
      .status-completed { background: rgba(16, 185, 129, 0.08); color: #059669; }
      .status-completed .status-pill-dot { background: #10b981; }

      .proj-title {
          font-weight: 700;
          /* color: #0f172a; */
          text-decoration: none;
          font-size: 13.5px;
          transition: color 0.15s;
      }
      .proj-title:hover {
          color: var(--clr-theme-primary, #198754);
      }
      .proj-loc {
          font-size: 11px;
          color: #64748b;
          margin-top: 3px;
          display: flex;
          align-items: center;
          gap: 4px;
      }
      .phase-items-container {
          animation: slideDown 0.2s ease-out;
      }
      @keyframes slideDown {
          from { opacity: 0; transform: translateY(-5px); }
          to { opacity: 1; transform: translateY(0); }
      }
      @media (max-width: 576px) {
        .phase-progress-row {
          grid-template-columns: 1fr !important;
          gap: 8px !important;
        }
        .subitem-progress-row {
          grid-template-columns: 1fr !important;
          gap: 6px !important;
        }
      }
    </style>

    <!-- ═══════════════════════════════ GREEN HERO BANNER ═══════════════════════════════ -->
    <div style="background:linear-gradient(135deg, var(--clr-theme-primary), var(--clr-theme-secondary));
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
          <!-- Project Filter Dropdown -->
          <form method="GET" action="" id="projectFilterForm" style="margin:0">
            <select name="project_id" class="form-select form-select-sm" onchange="this.form.submit()" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.3);color:#fff;border-radius:10px;padding:7px 30px 7px 15px;font-size:12px;font-weight:700;height:auto;cursor:pointer;outline:none;-webkit-appearance:none;-moz-appearance:none;appearance:none;background-image:url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23ffffff%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22/%3E%3C/svg%3E');background-repeat:no-repeat;background-position:right 10px top 50%;background-size:10px auto;">
              <option value="" style="color:#333;">All Projects</option>
              <?php foreach ($projects as $proj): ?>
                <option value="<?= $proj['id'] ?>" <?= ($selectedProjId == $proj['id']) ? 'selected' : '' ?> style="color:#333;">
                  <?= htmlspecialchars($proj['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </form>
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
        <div class="kpi-icon" style="background:rgba(var(--clr-theme-secondary-rgb),.12);color:var(--cd-purple)"><i
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
        <div class="kpi-icon" style="background:rgba(var(--clr-theme-primary-rgb),.12);color:var(--cd-indigo)"><i
            class="fal fa-check-circle"></i></div>
        <div class="kpi-label" style="color:var(--text-muted)">Pending Approvals</div>
        <div class="kpi-value" style="color:var(--cd-indigo)"><?= $stats['pending_approvals'] ?? 0 ?></div>
        <div class="kpi-sub" style="color:var(--text-muted)">Bills, funds &amp; vendor payments</div>
        <?php if (($stats['pending_approvals'] ?? 0) > 0): ?>
          <span class="kpi-badge" style="background:rgba(var(--clr-theme-primary-rgb),.12);color:var(--cd-indigo)">PENDING</span>
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

    <!-- ══════════════════════ THIS MONTH'S PROJECT TARGETS ══════════════════════ -->
    <?php 
    $thisMonthTargets = $thisMonthTargets ?? [];
    if (!empty($thisMonthTargets)): 
    ?>
      <div class="con-panel card__wrapper mb-4" style="border-color:rgba(var(--clr-theme-primary-rgb),.3);background:rgba(var(--clr-theme-primary-rgb),.03)">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="sec-title" style="margin-bottom:0">
            <div class="sec-title-dot" style="background:var(--cd-primary)"></div>
            <i class="fal fa-calendar-check me-2" style="color:var(--cd-primary)"></i>
            This Month's Project Targets (<?= date('F Y') ?>)
            <span style="margin-left:8px;font-size:9px;font-weight:700;padding:2px 10px;border-radius:20px;
        background:rgba(var(--clr-theme-primary-rgb),.12);color:var(--cd-primary)">
              <?= count($thisMonthTargets) ?> Targets Set
            </span>
          </div>
        </div>
        <div style="overflow-x:auto">
          <table class="proj-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Project Name</th>
                <th>Phase / Target Name</th>
                <th>Responsible Team</th>
                <th>Phase Weight</th>
                <th>Work Items</th>
                <th>Planning Status</th>
                <th style="text-align:right">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($thisMonthTargets as $ti => $targetPlan): 
                $tStatus = $targetPlan['planning_status'];
                $tBadgeBg = 'rgba(148,163,184,.12)';
                $tBadgeColor = '#94a3b8';
                if ($tStatus === 'Approved') {
                    $tBadgeBg = 'rgba(16,185,129,.12)';
                    $tBadgeColor = '#10b981';
                } elseif ($tStatus === 'In Review') {
                    $tBadgeBg = 'rgba(245,158,11,.12)';
                    $tBadgeColor = '#f59e0b';
                }
              ?>
                <tr>
                  <td style="font-size:11px;color:var(--text-muted)"><?= $ti + 1 ?></td>
                  <td>
                    <div style="font-weight:700;font-size:13px"><?= htmlspecialchars($targetPlan['project_name']) ?></div>
                  </td>
                  <td>
                    <div style="font-weight:600;font-size:12.5px"><?= htmlspecialchars($targetPlan['phase_name']) ?></div>
                  </td>
                  <td>
                    <span style="font-size:12px;color:var(--text-muted)"><i class="fal fa-users me-1"></i><?= htmlspecialchars($targetPlan['responsible_team'] ?: 'General') ?></span>
                  </td>
                  <td style="font-weight:700;">
                    <?= number_format((float)$targetPlan['phase_weight'], 0) ?>%
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border" style="font-size:10px"><?= (int)$targetPlan['item_count'] ?> items</span>
                  </td>
                  <td>
                    <span class="badge" style="background: <?= $tBadgeBg ?>; color: <?= $tBadgeColor ?>; font-size: 10px; font-weight: 700;"><?= $tStatus ?></span>
                  </td>
                  <td style="text-align:right">
                    <a href="<?= $base ?>/con-monthly-target/<?= $targetPlan['id'] ?>?read_only=1" class="btn btn-sm btn-outline-primary" style="font-size:11px;font-weight:700;padding:4px 12px;border-radius:6px">
                      <i class="fal fa-eye me-1"></i> View Plan
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

    <!-- ══════════════════════ PENDING CONTRACTOR BILLS ══════════════════════ -->
    <?php if (!empty($pendingContractorBills)): ?>
      <div class="con-panel card__wrapper mb-4" style="border-color:rgba(59,130,246,.3);background:rgba(59,130,246,.03)">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="sec-title" style="margin-bottom:0">
            <div class="sec-title-dot" style="background:var(--cd-blue)"></div>
            <i class="fal fa-file-invoice-dollar me-2" style="color:var(--cd-blue)"></i>
            Pending Contractor Bill Approvals
            <span style="margin-left:8px;font-size:9px;font-weight:700;padding:2px 10px;border-radius:20px;
        background:rgba(59,130,246,.12);color:var(--cd-blue)">
              <?= count($pendingContractorBills) ?> Awaiting
            </span>
          </div>
          <a href="<?= $base ?>/con-contractor-bills" style="font-size:11px;color:var(--cd-blue);text-decoration:none">View All
            <i class="fal fa-arrow-right ms-1"></i></a>
        </div>
        <div style="overflow-x:auto">
          <table class="proj-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Project</th>
                <th>Contractor</th>
                <th>Bill Type</th>
                <th>Bill Amount</th>
                <th>Submitted By</th>
                <th>Submitted On</th>
                <th style="text-align:right">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pendingContractorBills as $bi => $pb): ?>
                <tr>
                  <td style="font-size:11px;color:var(--text-muted)"><?= $bi + 1 ?></td>
                  <td>
                    <div style="font-weight:700;font-size:13px"><?= htmlspecialchars($pb['project_name'] ?? 'N/A') ?></div>
                  </td>
                  <td>
                    <div style="font-weight:600;font-size:12.5px"><?= htmlspecialchars($pb['contractor_name'] ?? '—') ?></div>
                  </td>
                  <td>
                    <span class="badge bg-<?= ($pb['bill_type'] === 'Advance') ? 'warning' : (($pb['bill_type'] === 'Retention') ? 'info' : 'primary') ?>-transparent">
                      <?= htmlspecialchars($pb['bill_type'] ?? 'Progress') ?>
                    </span>
                  </td>
                  <td style="font-weight:800;color:var(--cd-primary,#198754)">
                    ৳<?= number_format($pb['bill_amount'], 2) ?>
                  </td>
                  <td>
                    <span style="font-size:12.5px;font-weight:600"><?= htmlspecialchars($pb['creator_name'] ?? '—') ?></span>
                  </td>
                  <td style="font-size:12px;color:var(--text-muted)">
                    <?= date('d M Y h:i A', strtotime($pb['created_at'])) ?>
                  </td>
                  <td style="text-align:right">
                    <a href="<?= $base ?>/con-contractor-bill/<?= $pb['id'] ?>" class="btn btn-sm btn-primary" style="font-size:11px;font-weight:700;padding:4px 12px;border-radius:6px">
                      <i class="fal fa-eye me-1"></i> View & Approve
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

    <!-- ══════════════════════ PENDING LABOR REQUISITIONS ══════════════════════ -->
    <?php if (!empty($pendingLaborRequisitions ?? [])): ?>
      <div class="con-panel card__wrapper mb-4" style="border-color:rgba(253,126,20,.4);background:rgba(253,126,20,.04)">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="sec-title" style="margin-bottom:0">
            <div class="sec-title-dot" style="background:#fd7e14"></div>
            <i class="fa-solid fa-hard-hat me-2" style="color:#fd7e14"></i>
            Pending Labor Requisitions
            <span style="margin-left:8px;font-size:9px;font-weight:700;padding:2px 10px;border-radius:20px;
              background:rgba(253,126,20,.12);color:#fd7e14">
              <?= count($pendingLaborRequisitions) ?> Action Required
            </span>
          </div>
          <a href="<?= $base ?>/con-labor-requisitions" style="font-size:11px;color:#fd7e14;text-decoration:none">View All
            <i class="fal fa-arrow-right ms-1"></i>
          </a>
        </div>
        <div style="overflow-x:auto">
          <table class="proj-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Req. No</th>
                <th>Project</th>
                <th>Labor</th>
                <th style="text-align:right">Amount</th>
                <th>Requested By</th>
                <th>Date</th>
                <th>Status</th>
                <th style="text-align:right">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $lrStatusLabel = [
                'pending'              => ['label'=>'Pending PM',     'bg'=>'rgba(255,193,7,.15)',  'color'=>'#856404'],
                'payment_requested'    => ['label'=>'QC Review',      'bg'=>'rgba(32,201,151,.15)', 'color'=>'#0d6952'],
                'qc_approved'          => ['label'=>'PM Verify',      'bg'=>'rgba(102,16,242,.15)','color'=>'#5a0fc8'],
                'pm_payment_approved'  => ['label'=>'Mgmt Approve',   'bg'=>'rgba(40,167,69,.15)', 'color'=>'#155724'],
                'management_approved'  => ['label'=>'Ready to Pay',   'bg'=>'rgba(13,110,253,.15)','color'=>'#0a3622'],
              ];
              foreach ($pendingLaborRequisitions as $lri => $lr):
                $lrSt = $lrStatusLabel[$lr['status']] ?? ['label'=>$lr['status'],'bg'=>'rgba(108,117,125,.15)','color'=>'#495057'];
              ?>
              <tr>
                <td style="font-size:11px;color:var(--text-muted)"><?= $lri + 1 ?></td>
                <td>
                  <a href="<?= $base ?>/con-labor-requisition/<?= $lr['id'] ?>"
                     style="font-weight:700;font-size:13px;color:var(--clr-theme-primary);text-decoration:none">
                    <?= htmlspecialchars($lr['requisition_no']) ?>
                  </a>
                </td>
                <td><div style="font-size:12px;font-weight:600"><?= htmlspecialchars($lr['project_name'] ?? '---') ?></div></td>
                <td>
                  <span style="font-weight:700"><?= $lr['labor_count'] ?></span>
                  <span style="font-size:11px;color:var(--text-muted)"> <?= htmlspecialchars($lr['labor_type']) ?></span>
                </td>
                <td style="text-align:right;font-weight:700;color:#28a745">
                  ৳ <?= number_format($lr['total_amount'], 0) ?>
                </td>
                <td style="font-size:12px"><?= htmlspecialchars($lr['requested_by_name'] ?? '---') ?></td>
                <td style="font-size:11px;color:var(--text-muted)"><?= date('d M Y', strtotime($lr['requisition_date'])) ?></td>
                <td>
                  <span style="font-size:10px;font-weight:700;padding:3px 8px;border-radius:20px;
                    background:<?= $lrSt['bg'] ?>;color:<?= $lrSt['color'] ?>">
                    <?= $lrSt['label'] ?>
                  </span>
                </td>
                <td style="text-align:right">
                  <?php if ($lr['status'] === 'qc_approved'): ?>
                    <a href="<?= $base ?>/con-labor-requisition/<?= $lr['id'] ?>"
                       class="btn btn-sm" style="font-size:11px;font-weight:700;padding:4px 12px;border-radius:6px;
                       background:#6610f2;color:#fff;border:none">
                      <i class="fal fa-user-check me-1"></i> PM Verify
                    </a>
                  <?php else: ?>
                    <a href="<?= $base ?>/con-labor-requisition/<?= $lr['id'] ?>"
                       class="btn btn-sm" style="font-size:11px;font-weight:700;padding:4px 12px;border-radius:6px;
                       background:#fd7e14;color:#fff;border:none">
                      <i class="fal fa-eye me-1"></i> Review
                    </a>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

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
                      <div style="width:30px;height:30px;border-radius:50%;background:rgba(var(--clr-theme-primary-rgb),.1);
                display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="fal fa-user" style="font-size:12px;color:var(--clr-theme-primary)"></i>
                      </div>
                      <span
                        style="font-size:12.5px;font-weight:600"><?= htmlspecialchars($pb['creator_name'] ?? '—') ?></span>
                    </div>
                  </td>
                  <td>
                    <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;
              background:rgba(var(--clr-theme-secondary-rgb),.08);color:var(--clr-theme-secondary)">
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
                background:var(--clr-theme-primary);color:#fff" onclick="event.stopPropagation()">
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
                  <th>Req. Date (BOQ)</th>
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
                  <td class="fw-bold" style="color:var(--clr-theme-primary)">PR-<?= $apr['id'] ?></td>
                  <td><strong><?= htmlspecialchars($apr['project_name'] ?? 'N/A') ?></strong></td>
                  <td>
                    <span class="badge <?= $aprBadge ?> px-2" style="font-size:10px"><?= $aprSt ?></span>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border" style="font-size:10px"><?= (int)$apr['item_count'] ?> items</span>
                  </td>
                  <td style="white-space:nowrap">
                    <?php if (!empty($apr['earliest_required_date'])): ?>
                      <span class="badge bg-light text-danger border border-danger px-2" style="font-size:10px">
                        <i class="bi bi-calendar-event me-1"></i><?= date('d M Y', strtotime($apr['earliest_required_date'])) ?>
                      </span>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
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
                        <?php 
                        $showApproveBtn = false;
                        $btnLabel = 'Approve';
                        $pmApproved = !empty($apr['approved_by_id']);
                        $qcApproved = !empty($apr['qc_approved_by_id']);
                        $mgmtApproved = !empty($apr['mgmt_approved_by_id']);

                        if (!$pmApproved) {
                            if (in_array($sessionRoleId, [15, 22])) {
                                $showApproveBtn = true;
                                $btnLabel = 'Approve PM';
                            }
                        } elseif (!$qcApproved) {
                            $showApproveBtn = false;
                        } elseif (!$mgmtApproved) {
                            if ($sessionRoleId === 3) {
                                $showApproveBtn = true;
                                $btnLabel = 'Final Approve';
                            }
                        }
                        ?>
                        <?php if ($showApproveBtn): ?>
                        <form action="<?= $base_url ?>/con-purchase-request-approve/<?= $apr['id'] ?>" method="POST" class="d-inline ms-1"
                              onsubmit="return confirm('<?= $btnLabel ?> PR-<?= $apr['id'] ?>?')">
                          <input type="hidden" name="status" value="Approved">
                          <button type="submit" class="btn btn-sm btn-success" style="font-size:11px">
                            <i class="bi bi-check-circle"></i> <?= $btnLabel ?>
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
    $pendingPettyCash = $pendingPettyCash ?? [];
    if (!empty($pendingPettyCash) && in_array($sessionRoleId, [1, 3])):
    ?>
        <div class="con-panel card__wrapper mb-4" style="border-color:rgba(245,158,11,.4);background:rgba(245,158,11,.03)">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="sec-title" style="margin-bottom:0">
              <div class="sec-title-dot" style="background:#f59e0b;animation:pulse 1.5s infinite"></div>
              <i class="fal fa-wallet me-2" style="color:#f59e0b"></i>
              Fund Requisitions &mdash; Awaiting Management Approval
              <span style="margin-left:8px;font-size:9px;font-weight:700;padding:2px 10px;border-radius:20px;
                background:rgba(245,158,11,.12);color:#f59e0b">
                <?= count($pendingPettyCash) ?> Pending
              </span>
            </div>
            <a href="<?= $base_url ?>/fund-requisitions" class="btn btn-sm btn-outline-warning text-dark" style="font-size:11px">
              View All Requisitions &rarr;
            </a>
          </div>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle" style="font-size:12.5px">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Department & Project</th>
                  <th>Category / Type</th>
                  <th>Amount</th>
                  <th>Purpose</th>
                  <th>Requested By</th>
                  <th>Date</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pendingPettyCash as $pcIndex => $pcreq): ?>
                <tr>
                  <td><?= $pcIndex + 1 ?></td>
                  <td>
                    <strong><?= htmlspecialchars($pcreq['department_name'] ?? 'N/A') ?></strong>
                    <div style="font-size:11px; color:#6c757d;">Project: <?= htmlspecialchars($pcreq['project_name'] ?? 'Office General') ?></div>
                  </td>
                  <td>
                    <span class="badge bg-secondary"><?= htmlspecialchars($pcreq['requisition_type'] === 'petty_cash' ? 'Petty Cash' : 'General') ?></span>
                    <div style="font-size:10px; color:#6c757d;"><?= htmlspecialchars($pcreq['category']) ?></div>
                  </td>
                  <td class="fw-bold text-success">BDT <?= number_format($pcreq['amount'], 2) ?></td>
                  <td>
                    <div style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($pcreq['purpose']) ?>">
                      <?= htmlspecialchars($pcreq['purpose']) ?>
                    </div>
                  </td>
                  <td><?= htmlspecialchars($pcreq['requester_name'] ?? '—') ?></td>
                  <td class="text-muted" style="font-size:11px"><?= date('d M Y', strtotime($pcreq['created_at'])) ?></td>
                  <td class="text-end">
                    <a href="<?= $base_url ?>/fund-requisitions" class="btn btn-sm btn-warning text-dark fw-bold" style="font-size:11px">
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
        <div class="con-panel card__wrapper mb-4" style="border-color:rgba(var(--clr-theme-secondary-rgb),.3);background:rgba(var(--clr-theme-secondary-rgb),.03)">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="sec-title" style="margin-bottom:0">
              <div class="sec-title-dot" style="background:var(--cd-purple)"></div>
              <i class="fal fa-clipboard-list me-2" style="color:var(--cd-purple)"></i>
              Pending BOQ Approvals
              <span style="margin-left:8px;font-size:9px;font-weight:700;padding:2px 10px;border-radius:20px;
        background:rgba(var(--clr-theme-secondary-rgb),.12);color:var(--cd-purple)">
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
                  <th>Req. Date (BOQ)</th>
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
                          <div style="width:30px;height:30px;border-radius:50%;background:rgba(var(--clr-theme-primary-rgb),.1);
                display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <i class="fal fa-user" style="font-size:12px;color:var(--clr-theme-primary)"></i>
                          </div>
                          <span
                            style="font-size:12.5px;font-weight:600"><?= htmlspecialchars($bq['creator_name'] ?? '—') ?></span>
                        </div>
                      </td>
                      <td>
                        <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;
              background:rgba(var(--clr-theme-primary-rgb),.08);color:var(--clr-theme-primary)">
                          <?= htmlspecialchars($bq['creator_designation'] ?: 'Site Engineer') ?>
                        </span>
                      </td>
                      <td style="font-size:11.5px;color:var(--text-muted)">v<?= htmlspecialchars($bq['version'] ?? '1.0') ?>
                      </td>
                      <td style="font-weight:700;text-align:center"><?= (int) ($bq['item_count'] ?? 0) ?></td>
                      <td style="font-weight:800;color:var(--cd-primary,#198754)"><?= conFmt($bq['grand_total'] ?? 0) ?></td>
                      <td style="white-space:nowrap">
                        <?php if (!empty($bq['earliest_required_date'])): ?>
                          <span class="badge bg-light text-danger border border-danger px-2" style="font-size:10.5px">
                            <i class="fal fa-calendar-alt me-1"></i><?= date('d M Y', strtotime($bq['earliest_required_date'])) ?>
                          </span>
                        <?php else: ?>
                          <span class="text-muted">—</span>
                        <?php endif; ?>
                      </td>
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
                background:linear-gradient(135deg,var(--clr-theme-primary),var(--clr-theme-secondary));color:#fff" onclick="event.stopPropagation()">
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
        <div class="con-panel card__wrapper mb-4" style="border-color:rgba(var(--clr-theme-secondary-rgb),.35);background:rgba(var(--clr-theme-secondary-rgb),.03)">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="sec-title" style="margin-bottom:0">
              <div class="sec-title-dot" style="background:var(--clr-theme-secondary)"></div>
              <i class="fal fa-file-check me-2" style="color:var(--clr-theme-secondary)"></i>
              Pending Material Receivings (GRN) &mdash; PM Approval Required
              <span
                style="margin-left:8px;font-size:9px;font-weight:700;padding:2px 10px;border-radius:20px;background:rgba(var(--clr-theme-secondary-rgb),.12);color:var(--clr-theme-secondary)">
                <?= count($pendingGRNsPM) ?> Awaiting
              </span>
            </div>
            <a href="<?= $base ?>/con-material-receivings" class="cd-btn-outline"
              style="border-color:var(--clr-theme-secondary);color:var(--clr-theme-secondary)">
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
                      <td style="font-weight:700;font-size:12px;color:var(--clr-theme-secondary)">GRN-<?= $pm['id'] ?></td>
                      <td style="font-weight:600;font-size:12px"><?= htmlspecialchars($pm['project_name'] ?? '—') ?></td>
                      <td style="font-size:11px;color:var(--text-muted);white-space:nowrap">
                        <i class="fal fa-calendar me-1"></i>
                        <?= !empty($pm['receiving_date']) ? date('d M Y', strtotime($pm['receiving_date'])) : '—' ?>
                      </td>
                      <td>
                        <div style="display:flex;align-items:center;gap:8px">
                          <div style="width:28px;height:28px;border-radius:50%;background:rgba(var(--clr-theme-primary-rgb),.1);
                display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <i class="fal fa-user" style="font-size:11px;color:var(--clr-theme-primary)"></i>
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
                background:linear-gradient(135deg,var(--clr-theme-secondary),var(--clr-theme-primary));color:#fff" onclick="event.stopPropagation()">
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
        <div class="con-panel card__wrapper mb-4" style="border-color:rgba(245,158,11,.35);background:rgba(245,158,11,.03)">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="sec-title" style="margin-bottom:0">
              <div class="sec-title-dot" style="background:var(--cd-orange)"></div>
              <i class="fal fa-file-contract me-2" style="color:var(--cd-orange)"></i>
              Pending Purchase Orders &mdash; Management Approval Required
              <span
                style="margin-left:8px;font-size:9px;font-weight:700;padding:2px 10px;border-radius:20px;background:rgba(245,158,11,.12);color:var(--cd-orange)">
                <?= count($pendingPOsForSE) ?> Awaiting
              </span>
            </div>
            <a href="<?= $base ?>/con-purchase-orders" style="font-size:11px;color:var(--cd-orange);text-decoration:none">View
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
                  <th>QC Status</th>
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
                      <td><?php $qcOk = !empty($po['qc_approved_by_id']); ?><span
                          style="font-size:10px;font-weight:700;padding:3px 9px;border-radius:20px;background:<?= $qcOk ? 'rgba(16,185,129,.12)' : ' rgba(239,68,68,.1)' ?>;color:<?= $qcOk ? '#059669' : '#dc2626' ?>"><?= $qcOk ? '&#10003; QC Approved' : '&#x23f3; QC Pending' ?></span>
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
                  <th>Req. Date (BOQ)</th>
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
                      <td style="white-space:nowrap">
                        <?php if (!empty($po['earliest_required_date'])): ?>
                          <span class="badge bg-light text-danger border border-danger px-2" style="font-size:11px">
                            <i class="fal fa-calendar-alt me-1"></i><?= date('d M Y', strtotime($po['earliest_required_date'])) ?>
                          </span>
                        <?php else: ?>
                          <span class="text-muted">—</span>
                        <?php endif; ?>
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
        <?php if (!empty($pendingWeeklyReportsPM)): ?>
        <table class="proj-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Progress Approval</th>
              <th>Project / Phase</th>
              <th>Week</th>
              <th>Submitted By</th>
              <th style="text-align:right">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pendingWeeklyReportsPM as $pi => $rep): 
              $monthText = !empty($rep['target_month']) ? date('F Y', strtotime($rep['target_month'])) : 'N/A';
              $phaseText = !empty($rep['phase_name']) ? htmlspecialchars($rep['phase_name']) : 'N/A';
            ?>
                <tr>
                  <td style="font-size:11px;color:var(--text-muted)"><?= $pi + 1 ?></td>
                  <td style="font-weight:800;color:#0f5132">
                    <i class="fal fa-file-invoice me-1"></i><?= $monthText ?> Progress Approval
                  </td>
                  <td>
                    <div style="font-weight:700;font-size:13px"><?= htmlspecialchars($rep['project_name'] ?? '&mdash;') ?></div>
                    <div style="font-size:11px;color:var(--text-muted)"><?= $phaseText ?></div>
                  </td>
                  <td style="font-size:12px;font-weight:700">Week <?= (int) $rep['week_no'] ?></td>
                  <td style="font-size:12px"><?= htmlspecialchars($rep['created_by_name'] ?? '&mdash;') ?></td>
                  <td style="text-align:right"><a href="<?= $base ?>/con-weekly-progress/<?= $rep['target_id'] ?>?week=<?= $rep['week_no'] ?>"
                      style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:5px 14px;border-radius:8px;text-decoration:none;background:linear-gradient(135deg,#0f5132,#198754);color:#fff"><i
                        class="fal fa-check-circle"></i> Review &amp; Approve</a></td>
                </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
          <div class="empty-state-icon" style="color:#0f5132"><i class="fal fa-calendar-week"></i></div>
          <div class="empty-state-text text-muted">No pending weekly progress reports awaiting PM approval.</div>
        </div>
        <?php endif; ?>
      </div>
    </div>

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
        <div class="con-panel card__wrapper mb-4" style="border-color:rgba(var(--clr-theme-secondary-rgb),.35);background:rgba(var(--clr-theme-secondary-rgb),.03)">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="sec-title" style="margin-bottom:0">
              <div class="sec-title-dot" style="background:var(--cd-purple)"></div>
              <i class="fal fa-box-open me-2" style="color:var(--cd-purple)"></i>
              Pending Material Receivings (GRN) &mdash; Awaiting QC
              <span
                style="margin-left:8px;font-size:9px;font-weight:700;padding:2px 10px;border-radius:20px;background:rgba(var(--clr-theme-secondary-rgb),.12);color:var(--cd-purple)">
                <?= count($pendingGRNs) ?> Awaiting
              </span>
            </div>
            <a href="<?= $base ?>/con-material-receivings"
              style="font-size:11px;color:var(--cd-purple);text-decoration:none">View All <i
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
                      <td style="font-weight:800;color:var(--cd-purple,var(--clr-theme-secondary))">GRN-<?= $grn['id'] ?></td>
                      <td style="font-weight:700;font-size:13px"><?= htmlspecialchars($grn['project_name'] ?? '&mdash;') ?></td>
                      <td style="font-size:11px;color:var(--text-muted)">
                        <?= !empty($grn['receiving_date']) ? date('d M Y', strtotime($grn['receiving_date'])) : '&mdash;' ?>
                      </td>
                      <td style="font-size:12px"><?= htmlspecialchars($grn['received_by_name'] ?? '&mdash;') ?></td>
                      <td style="text-align:right"><a href="<?= $base ?>/con-material-receiving-qc/<?= $grn['id'] ?>"
                          style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:5px 14px;border-radius:8px;text-decoration:none;background:linear-gradient(135deg,var(--clr-theme-secondary),var(--clr-theme-primary));color:#fff"><i
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
              Pending Work Orders &mdash; Awaiting Your Approval
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
      <div class="col-12 col-md-6 col-lg-4 mb-3 mb-lg-0">
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
              style="background:rgba(var(--clr-theme-primary-rgb),.05);text-decoration:none">
              <div class="alert-icon-wrap" style="background:rgba(var(--clr-theme-primary-rgb),.12);color:var(--cd-indigo)"><i
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
      <div class="col-12 col-md-6 col-lg-4 mb-3 mb-lg-0">
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
      <div class="col-12 col-md-6 col-lg-4 mb-3 mb-lg-0">
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
      <div class="col-12 col-xl-8 mb-4 mb-xl-0">
        <div class="con-panel card__wrapper h-100">
          <div class="d-flex justify-content-between align-items-center mb-3" style="flex-wrap: wrap; gap: 10px;">
            <div class="sec-title" style="margin-bottom:0">
              <div class="sec-title-dot" style="background:var(--cd-blue)"></div>
              Project Progress Overview
            </div>
            <div>
              <select id="card-project-filter" class="form-select form-select-sm" onchange="filterCardProjects(this.value)" style="width: 220px; font-size: 12px; border-radius: 8px; padding: 5px 10px; border: 1px solid rgba(0,0,0,.12); outline: none; background-color: var(--card-bg, #fff); color: var(--text-color, #333); font-weight: 600;">
                <option value="">All Projects</option>
                <?php foreach ($projects as $proj): ?>
                  <option value="<?= $proj['id'] ?>" <?= (isset($selectedProjId) && $selectedProjId == $proj['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($proj['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <?php if (empty($filteredProjects)): ?>
              <div class="empty-state">
                <div class="empty-state-icon"><i class="fal fa-chart-bar"></i></div>
                <div class="empty-state-text" style="color:var(--text-muted)">No projects found</div>
              </div>
          <?php else: ?>
              <div class="po-table-container">
                  <table class="po-table" id="projectOverviewTable">
                      <thead>
                          <tr>
                              <th style="width: 28%;">Project & Location</th>
                              <th style="width: 15%;">Current Phase</th>
                              <th style="width: 27%;">Planned vs Actual Progress</th>
                              <th style="width: 10%;">Status</th>
                              <th style="width: 10%;">Handover Target</th>
                              <th style="width: 8%;">Budget Usage</th>
                              <th style="width: 2%; text-align: center;"></th>
                          </tr>
                      </thead>
                      <tbody id="project-progress-rows">
                          <?php 
                          $initialFiltered = array_slice($filteredProjects, 0, 10);
                          foreach ($initialFiltered as $pr):
                              $actual_pct = (float)($pr['overall_project_actual'] ?? $pr['progress_pct'] ?? 0.0);
                              $planned_pct = (float)($pr['overall_project_planned'] ?? $pr['planned_progress_pct'] ?? 0.0);
                              $pct = round($actual_pct, 1);
                              $plPct = round($planned_pct, 1);
                              
                              if ($pr['project_status'] === 'Completed') {
                                  $status = 'Completed';
                              } elseif ($pr['project_status'] === 'Planning' || ($actual_pct == 0 && $planned_pct == 0)) {
                                  $status = 'Planning';
                              } else {
                                  $gap = $planned_pct - $actual_pct;
                                  if ($gap <= 5) {
                                      $status = 'On Track';
                                  } elseif ($gap <= 15) {
                                      $status = 'At Risk';
                                  } else {
                                      $status = 'Delayed';
                                  }
                              }
                              
                              $isPhaseBased = ($pr['workflow_version'] ?? '') === 'phase_based';
                              
                              $statusClass = 'status-planning';
                              if ($status === 'On Track') $statusClass = 'status-ontrack';
                              elseif ($status === 'At Risk') $statusClass = 'status-atrisk';
                              elseif ($status === 'Delayed') $statusClass = 'status-delayed';
                              elseif ($status === 'Completed') $statusClass = 'status-completed';

                              $detailUrl = ($isPhaseBased) 
                                  ? $base . '/con-project-timeline/' . $pr['id'] 
                                  : $base . '/con-project/' . $pr['id'];

                              $budgetPct = $pr['total_budget'] > 0 ? round(($pr['total_spent'] / $pr['total_budget']) * 100) : 0;

                              // Construct Overall Progress Popover Content
                              $plaLines = [];
                              $actLines = [];
                              if (!empty($pr['phases'])) {
                                  foreach ($pr['phases'] as $phase) {
                                      if ((float)$phase['total_boq'] > 0) {
                                          $phName = $phase['phase_name'];
                                          $phWeight = (float)$phase['phase_weight'];
                                          
                                          // Month name
                                          $targetMonthBn = 'জুনের'; // default
                                          if (!empty($phase['target_month'])) {
                                              $bnMonth = getBengaliMonthName($phase['target_month']);
                                              $targetMonthBn = ($bnMonth === 'জুন') ? 'জুনের' : $bnMonth . ' মাসের';
                                          }
                                          
                                          // Planned
                                          $phPlannedVal = (float)$phase['phase_planned_weighted_pct'];
                                          $phPlannedContrib = $phPlannedVal * ($phWeight / 100);
                                          if ($phPlannedVal > 0) {
                                              $bnWeight = toBengaliNum(round($phWeight, 1));
                                              $bnPlanned = toBengaliNum(round($phPlannedVal, 1));
                                              $bnPlannedContrib = toBengaliNum(round($phPlannedContrib, 1));
                                              $plaLines[] = "{$phName} ফেজের {$targetMonthBn} নিজস্ব প্ল্যান ({$bnPlanned}%) × প্রজেক্টে এই ফেজের গুরুত্ব ({$bnWeight}%) = {$bnPlannedContrib}%";
                                          }
                                          
                                          // Actual
                                          $phActualVal = (float)$phase['phase_actual_weighted_pct'];
                                          $phActualContrib = $phActualVal * ($phWeight / 100);
                                          if ($phActualVal > 0) {
                                              $bnWeight = toBengaliNum(round($phWeight, 1));
                                              $bnActual = toBengaliNum(round($phActualVal, 2));
                                              $bnActualContrib = toBengaliNum(round($phActualContrib, 2));
                                              $actLines[] = "{$phName} ফেজের {$targetMonthBn} নিজস্ব প্রকৃত অগ্রগতি ({$bnActual}%) × প্রজেক্টে এই ফেজের গুরুত্ব ({$bnWeight}%) = {$bnActualContrib}%";
                                          }
                                      }
                                  }
                              }
                              
                              $formattedPla = ($planned_pct > 0 && $planned_pct < 0.1) ? number_format($planned_pct, 3) : number_format($planned_pct, 1);
                              $formattedAct = ($actual_pct > 0 && $actual_pct < 0.1) ? number_format($actual_pct, 3) : number_format($actual_pct, 1);

                              $plaPopoverContent = "<strong>ℹ️ সামগ্রিক পরিকল্পিত অগ্রগতি (Overall Planned):</strong><br/>"
                                                 . (empty($plaLines) ? "কোনো পরিকল্পিত অগ্রগতি নেই।" : "হিসাব: " . implode(" + ", $plaLines) . " = " . toBengaliNum($formattedPla) . "% (পুরো প্রজেক্টের সাপেক্ষে)");
                                                 
                              $actPopoverContent = "<strong>ℹ️ সামগ্রিক প্রকৃত অগ্রগতি (Overall Actual):</strong><br/>"
                                                 . (empty($actLines) ? "কোনো প্রকৃত অগ্রগতি নেই।" : "হিসাব: " . implode(" + ", $actLines) . " = " . toBengaliNum($formattedAct) . "% (পুরো প্রজেক্টের সাপেক্ষে)");
                              
                              // Find current active phase
                              $currentPhaseName = 'N/A';
                              $currentPhaseSub = 'No active phases';
                              if (!empty($pr['phases'])) {
                                  $totalPhases = count($pr['phases']);
                                  $currentPhaseIdx = 1;
                                  foreach ($pr['phases'] as $idx => $phase) {
                                      if ((float)$phase['phase_actual_pct'] < 100) {
                                          $currentPhaseName = $phase['phase_name'];
                                          $currentPhaseIdx = $idx + 1;
                                          break;
                                      }
                                  }
                                  // If all phases are completed, default to the last phase
                                  if ($currentPhaseName === 'N/A') {
                                      $lastPhase = end($pr['phases']);
                                      $currentPhaseName = $lastPhase['phase_name'];
                                      $currentPhaseIdx = $totalPhases;
                                  }
                                  $currentPhaseSub = "Phase " . $currentPhaseIdx . " of " . $totalPhases;
                              }
                              
                              // Dynamic building color gradient for thumbnail
                              $colors = ['#4f46e5', '#0891b2', '#0d9488', '#ea580c', '#e11d48', '#2563eb'];
                              $gradColor = $colors[$pr['id'] % count($colors)];
                              ?>
                              <tr class="project-row" onclick="window.location.href='<?= $detailUrl ?>'" style="cursor: pointer;">
                                  <!-- Column 1: Project & Location -->
                                  <td style="padding: 14px 16px; vertical-align: middle;">
                                      <div style="display: flex; align-items: center; gap: 12px;">
                                          <!-- Phase Toggle Button -->
                                          <?php if (!empty($pr['phases'])): ?>
                                              <button onclick="event.stopPropagation(); togglePhaseItems('phases-breakdown-<?= $pr['id'] ?>', this.querySelector('i'))" 
                                                      style="border: none; background: transparent; padding: 4px; color: #94a3b8; cursor: pointer; transition: color 0.15s; outline: none;">
                                                  <i class="fa-solid fa-eye" style="font-size: 13px;"></i>
                                              </button>
                                          <?php else: ?>
                                              <span style="width: 19px; display: inline-block;"></span>
                                          <?php endif; ?>

                                          <!-- Thumbnail/Image placeholder -->
                                          <?php if (!empty($pr['image'])): ?>
                                              <div style="width: 42px; height: 42px; border-radius: 8px; overflow: hidden; flex-shrink: 0; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                                                  <img src="<?= getImage('projects', $pr['image']) ?>" alt="<?= htmlspecialchars($pr['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                              </div>
                                          <?php else: ?>
                                              <div style="width: 42px; height: 42px; border-radius: 8px; background: linear-gradient(135deg, <?= $gradColor ?>, <?= $gradColor ?>dd); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 700; flex-shrink: 0; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                                                  <i class="fa-solid fa-building"></i>
                                              </div>
                                          <?php endif; ?>

                                          <div>
                                              <a href="<?= $detailUrl ?>" class="proj-title" style="font-size: 13px; font-weight: 700; text-decoration: none; display: block;"><?= htmlspecialchars($pr['name']) ?></a>
                                              <div class="proj-loc" style="font-size: 11px;  margin-top: 2px; display: flex; align-items: center; gap: 4px;">
                                                  <i class="fa-solid fa-location-dot" style="font-size: 10px;"></i>
                                                  <?= htmlspecialchars($pr['location_name'] ?: 'N/A') ?>
                                              </div>
                                          </div>
                                      </div>
                                  </td>
                                  
                                  <!-- Column 2: Current Phase -->
                                  <td style="padding: 14px 16px; vertical-align: middle;">
                                      <div style="font-weight: 700; color: #1e293b; font-size: 13px;"><?= htmlspecialchars($currentPhaseName) ?></div>
                                      <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;"><?= $currentPhaseSub ?></div>
                                  </td>
                                  
                                  <!-- Column 3: Planned vs Actual Progress -->
                                  <td style="padding: 14px 16px; vertical-align: middle; min-width: 180px;">
                                      <div class="prog-compare-wrap" style="display: flex; flex-direction: column; gap: 6px;">
                                          <!-- Planned Bar -->
                                          <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                                              <span style="font-size: 10px; font-weight: 700; color: #94a3b8; min-width: 24px;">Pla</span>
                                              <div class="prog-bar-container" style="height: 6px; flex: 1; background: #f1f5f9;">
                                                  <div class="prog-bar-fill-planned" style="width: <?= min(100, $planned_pct) ?>%; height: 100%; background: #6366f1; opacity: 0.85;"></div>
                                              </div>
                                              <span style="font-size: 11px; font-weight: 800; color: #6366f1; min-width: 32px; text-align: right; display: flex; align-items: center; gap: 4px;">
                                                  <?= cpFmtPct($planned_pct) ?>
                                                  <span style="font-size: 10px; cursor: pointer; color: #94a3b8;" data-bs-toggle="popover" data-bs-content="<?= htmlspecialchars($plaPopoverContent) ?>" onclick="event.stopPropagation();">ℹ️</span>
                                              </span>
                                          </div>
                                          <!-- Actual Bar -->
                                          <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                                              <span style="font-size: 10px; font-weight: 700; color: #94a3b8; min-width: 24px;">Act</span>
                                              <div class="prog-bar-container" style="height: 6px; flex: 1; background: #f1f5f9;">
                                                  <div class="prog-bar-fill-actual" style="width: <?= min(100, $actual_pct) ?>%; height: 100%; background: #198754;"></div>
                                              </div>
                                              <span style="font-size: 11px; font-weight: 800; color: #198754; min-width: 32px; text-align: right; display: flex; align-items: center; gap: 4px;">
                                                  <?= cpFmtPct($actual_pct) ?>
                                                  <span style="font-size: 10px; cursor: pointer; color: #94a3b8;" data-bs-toggle="popover" data-bs-content="<?= htmlspecialchars($actPopoverContent) ?>" onclick="event.stopPropagation();">ℹ️</span>
                                              </span>
                                          </div>
                                      </div>
                                  </td>
                                  
                                  <!-- Column 4: Status Badge -->
                                  <td style="padding: 14px 16px; vertical-align: middle;">
                                      <span class="status-pill <?= $statusClass ?>">
                                          <span class="status-pill-dot"></span>
                                          <?= $status ?>
                                      </span>
                                  </td>
                                  
                                  <!-- Column 5: Handover Target -->
                                  <td style="padding: 14px 16px; vertical-align: middle;">
                                      <div style="font-weight: 700; color: #1e293b; font-size: 13px;">
                                          <?= $pr['expected_handover_date'] ? date('M Y', strtotime($pr['expected_handover_date'])) : 'N/A' ?>
                                      </div>
                                      <div style="font-size:11px; color: #94a3b8; margin-top:2px">
                                          <?php if ($pr['expected_handover_date']):
                                              $diff = time() - strtotime($pr['expected_handover_date']);
                                              $months = abs(round($diff / (60 * 60 * 24 * 30)));
                                              if ($diff < 0) {
                                                  echo "In $months m";
                                              } else {
                                                  echo "$months m ago";
                                              }
                                          else:
                                              echo 'Timeline unset';
                                          endif; ?>
                                      </div>
                                  </td>
                                  
                                  <!-- Column 6: Budget Usage -->
                                  <td style="padding: 14px 16px; vertical-align: middle;">
                                      <div style="font-weight: 700; color: #1e293b; font-size: 13px;"><?= conFmt($pr['total_spent']) ?></div>
                                      <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 4px;">
                                          <div class="prog-bar-container" style="height: 4px; flex: 1; background: #f1f5f9;">
                                              <div class="prog-bar-fill-actual" style="width: <?= min(100, $budgetPct) ?>%; height: 100%; background: <?= $budgetPct > 90 ? '#ef4444' : '#198754' ?>;"></div>
                                          </div>
                                          <span style="font-size: 10px; font-weight: 700; color: #64748b; min-width: 26px; text-align: right;"><?= $budgetPct ?>%</span>
                                      </div>
                                  </td>
                                  
                                  <!-- Column 7: Actions Menu -->
                                  <td style="padding: 14px 16px; vertical-align: middle; text-align: center;">
                                      <button style="border: none; background: transparent; color: #94a3b8; cursor: pointer; padding: 4px; font-size: 14px; outline: none;" 
                                              onclick="event.stopPropagation(); window.location.href='<?= $detailUrl ?>'">
                                          <i class="fa-solid fa-ellipsis-vertical"></i>
                                      </button>
                                  </td>
                              </tr>

                              <!-- Nested Timeline Phases Detail Block (Collapsible) -->
                              <?php if (!empty($pr['phases'])): ?>
                                  <tr id="phases-breakdown-<?= $pr['id'] ?>" class="phase-items-container" style="display: none; background: #fafbfc;">
                                      <td colspan="7" style="padding: 18px 24px; border-bottom: 1px solid rgba(0,0,0,0.05);">
                                          <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                                              <i class="fa-solid fa-folder-open text-primary"></i> Phase Progress Breakdown for <?= htmlspecialchars($pr['name']) ?>
                                          </div>
                                          
                                          <div style="display: flex; flex-direction: column; gap: 12px;">
                                              <?php foreach ($pr['phases'] as $phase): 
                                                  $phWeight = (float)$phase['phase_weight'];
                                                  $phaseActualProgress = (float)$phase['phase_actual_pct'];
                                                  $phaseContribution = (float)$phase['phase_contribution'];

                                                  // Compute dynamic tooltips for this phase
                                                  $totalApprovedVal = (float)$phase['total_approved_qty'];
                                                  $totalBOQVal = (float)$phase['total_boq'];
                                                  
                                                  $phaseProgressTooltip = "<strong>ℹ️ ফেজের নিজস্ব অগ্রগতি (Phase Internal Progress):</strong><br/>"
                                                                        . "সূত্র: (অনুমোদিত কাজের ভলিউম ÷ মোট ফেজ BOQ) × ১০০<br/>"
                                                                        . "হিসাব: (" . toBengaliNum(number_format($totalApprovedVal)) . " CFT ÷ " . toBengaliNum(number_format($totalBOQVal)) . " CFT) × ১০০ = " . toBengaliNum(number_format(round($phaseActualProgress, 1), 1)) . "%";

                                                  $phaseProgressVal2 = number_format($phaseActualProgress, 2); 
                                                  $phaseWeightVal = number_format($phWeight, 1); 
                                                  $phaseContributionVal = number_format($phaseContribution, 2); 
                                                  
                                                  $phaseContributionTooltip = "<strong>ℹ️ প্রজেক্টে এই ফেজের অবদান (Project Contribution):</strong><br/>"
                                                                            . "সূত্র: ফেজের বর্তমান প্রগ্রেস (" . toBengaliNum($phaseProgressVal2) . "%) × (ফেজ ওয়েট " . toBengaliNum($phaseWeightVal) . " ÷ ১০০) = " . toBengaliNum($phaseContributionVal) . "%";
                                                  ?>
                                                  <div class="phase-progress-row" style="display: grid; grid-template-columns: 200px 1fr; align-items: center; gap: 16px;">
                                                      <div style="font-size: 12px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                                                           <?php if (!empty($phase['items'])): ?>
                                                              <i class="fa-solid fa-chevron-right phase-toggle-icon" 
                                                                 onclick="event.stopPropagation(); togglePhaseItems('subitems-<?= $pr['id'] ?>-<?= $phase['phase_id'] ?>', this)" 
                                                                 style="cursor: pointer; font-size: 9px; width: 12px; color: #198754; transition: transform 0.15s; display: inline-block;"></i>
                                                           <?php else: ?>
                                                              <span style="width: 12px; display: inline-block;"></span>
                                                           <?php endif; ?>
                                                           <span style="<?php if (!empty($phase['items'])): ?>cursor: pointer;<?php endif; ?>" 
                                                                 onclick="event.stopPropagation(); <?php if (!empty($phase['items'])): ?>togglePhaseItems('subitems-<?= $pr['id'] ?>-<?= $phase['phase_id'] ?>', this.previousElementSibling)<?php endif; ?>">
                                                               <?= htmlspecialchars($phase['phase_name']) ?>
                                                           </span>
                                                      </div>
                                                      <div style="width: 100%;">
                                                          <div style="font-size: 9px; font-weight: 800; display: flex; justify-content: space-between; margin-bottom: 2px;">
                                                              <span style="color:#6366f1">Phase Weight: <?= number_format($phWeight, 1) ?>%</span>
                                                              <span style="color:#198754; display: flex; align-items: center; gap: 4px;">
                                                                   Contribution: <?= number_format($phaseContribution, 2) ?>% 
                                                                   <span style="font-size: 9px; cursor: pointer; color: #94a3b8;" data-bs-toggle="popover" data-bs-content="<?= htmlspecialchars($phaseContributionTooltip) ?>" onclick="event.stopPropagation();">ℹ️</span>
                                                                   (<?= number_format($phaseActualProgress, 1) ?>% complete)
                                                                   <span style="font-size: 9px; cursor: pointer; color: #94a3b8;" data-bs-toggle="popover" data-bs-content="<?= htmlspecialchars($phaseProgressTooltip) ?>" onclick="event.stopPropagation();">ℹ️</span>
                                                              </span>
                                                          </div>
                                                          <div class="prog-bar-container" style="height: 6px; background: #e2e8f0; position: relative;">
                                                              <!-- Planned phase width (representing total phase weight) -->
                                                              <div class="prog-bar-fill-planned" style="width: <?= $phWeight ?>%; height: 100%; background: #6366f1; opacity: 0.15; position: absolute; left: 0;"></div>
                                                              <!-- Actual phase contribution fill -->
                                                              <div class="prog-bar-fill-actual" style="width: <?= $phaseContribution ?>%; height: 100%; background: <?= $phaseActualProgress >= 100 ? '#198754' : '#f59e0b' ?>; position: absolute; left: 0;"></div>
                                                          </div>
                                                      </div>
                                                  </div>

                                                  <!-- Nested Sub-items Loop -->
                                                  <?php if (!empty($phase['items'])): ?>
                                                      <div id="subitems-<?= $pr['id'] ?>-<?= $phase['phase_id'] ?>" class="phase-items-container" style="display: none; flex-direction: column; gap: 8px; margin-left: 24px; border-left: 2px solid #e2e8f0; padding-left: 14px; margin-top: 4px; margin-bottom: 4px;">
                                                          <?php foreach ($phase['items'] as $item): 
                                                              $itemWeight = (float)$item['item_weight'];
                                                              $itemActualPct = (float)$item['item_actual_pct'];
                                                              $itemProjWeight = ($itemWeight / 100) * $phWeight;
                                                              $itemActWeightVal = ($itemActualPct / 100) * $itemProjWeight;
                                                              ?>
                                                              <div class="subitem-progress-row" style="display: grid; grid-template-columns: 180px 1fr; align-items: center; gap: 16px;">
                                                                  <div style="font-size: 11.5px; font-weight: 600; color: #64748b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($item['item_name']) ?>">
                                                                      ↳ <?= htmlspecialchars($item['item_name']) ?>
                                                                  </div>
                                                                  <div style="width: 100%;">
                                                                      <div style="font-size: 8px; font-weight: 700; display: flex; justify-content: space-between; margin-bottom: 1px;">
                                                                          <span style="color:#6366f1">Item Weight: <?= number_format($itemProjWeight, 2) ?>%</span>
                                                                          <span style="color:#198754">
                                                                              Progress: <?= number_format($itemActualPct, 1) ?>% (<?= number_format($itemActWeightVal, 2) ?>% project wt)
                                                                              <?php if (!empty($item['qty_usage_text'])): ?>
                                                                                  &middot; <span style="color: #64748b; font-weight: 500;"><?= htmlspecialchars($item['qty_usage_text']) ?></span>
                                                                              <?php endif; ?>
                                                                          </span>
                                                                      </div>
                                                                      <div class="prog-bar-container" style="height: 4px; background: #e2e8f0; position: relative;">
                                                                          <div class="prog-bar-fill-planned" style="width: <?= $itemProjWeight ?>%; height: 100%; background: #6366f1; opacity: 0.15; position: absolute; left: 0;"></div>
                                                                          <div class="prog-bar-fill-actual" style="width: <?= $itemActWeightVal ?>%; height: 100%; background: <?= $itemActualPct >= 100 ? '#198754' : '#f59e0b' ?>; position: absolute; left: 0;"></div>
                                                                      </div>
                                                                  </div>
                                                              </div>
                                                          <?php endforeach; ?>
                                                      </div>
                                                  <?php endif; ?>

                                              <?php endforeach; ?>
                                          </div>
                                      </td>
                                  </tr>
                              <?php endif; ?>
                          <?php endforeach; ?>
                      </tbody>
                  </table>
              </div>
          <?php endif; ?>

          <div id="project-progress-pagination" class="d-flex justify-content-between align-items-center mt-3 pt-2" style="font-size:12px;border-top:1px solid rgba(0,0,0,.05); <?= empty($filteredProjects) ? 'display: none !important;' : '' ?>">
            <span id="proj-page-info" style="color:var(--text-muted)">Showing 1-<?= min(10, count($filteredProjects)) ?> of <?= count($filteredProjects) ?> projects</span>
            <div class="btn-group">
              <button type="button" class="btn btn-sm btn-outline-secondary" id="proj-prev-btn" disabled onclick="changeProjPage(-1)" style="padding: 2px 8px; font-size: 11px;">Previous</button>
              <button type="button" class="btn btn-sm btn-outline-secondary ms-1" id="proj-next-btn" <?= count($filteredProjects) <= 10 ? 'disabled' : '' ?> onclick="changeProjPage(1)" style="padding: 2px 8px; font-size: 11px;">Next</button>
            </div>
          </div>

          <script>
          let currentProjPage = 1;
          let currentProjLimit = 10;
          let currentProjTotal = <?= count($filteredProjects) ?>;
          let currentProjPages = Math.ceil(currentProjTotal / currentProjLimit);
          let activeFilterProjId = '<?= htmlspecialchars($selectedProjId ?? "") ?>';

          function loadCardProjects(page, projId) {
              if (page < 1) return;
              
              const listContainer = document.getElementById('project-progress-rows');
              listContainer.style.opacity = '0.5';
              
              let url = `<?= $base ?>/api/con-dashboard/projects-progress?page=${page}`;
              if (projId) {
                  url += `&project_id=${projId}`;
              }
              
              fetch(url)
                  .then(response => response.json())
                  .then(data => {
                      listContainer.innerHTML = data.html;
                      listContainer.style.opacity = '1';
                      
                      currentProjPage = data.page;
                      currentProjTotal = data.totalProjects;
                      currentProjPages = data.totalPages;
                      activeFilterProjId = projId;
                      
                      // Update page info
                      const start = currentProjTotal > 0 ? (currentProjPage - 1) * currentProjLimit + 1 : 0;
                      const end = Math.min(currentProjPage * currentProjLimit, currentProjTotal);
                      
                      const paginationContainer = document.getElementById('project-progress-pagination');
                      if (currentProjTotal === 0) {
                          paginationContainer.style.display = 'none';
                      } else {
                          paginationContainer.style.display = 'flex';
                          document.getElementById('proj-page-info').textContent = `Showing ${start}-${end} of ${currentProjTotal} projects`;
                          
                          // Enable/disable buttons
                          document.getElementById('proj-prev-btn').disabled = (currentProjPage === 1 || currentProjPages <= 1);
                          document.getElementById('proj-next-btn').disabled = (currentProjPage === currentProjPages || currentProjPages <= 1);
                      }
                      
                      // Re-initialize Bootstrap popovers
                      if (typeof bootstrap !== 'undefined' && bootstrap.Popover) {
                          const popoverTriggerList = [].slice.call(listContainer.querySelectorAll('[data-bs-toggle="popover"]'));
                          popoverTriggerList.map(function (popoverTriggerEl) {
                              return new bootstrap.Popover(popoverTriggerEl, {
                                  html: true,
                                  trigger: 'hover'
                              });
                          });
                      }
                  })
                  .catch(error => {
                      console.error('Error fetching paginated projects:', error);
                      listContainer.style.opacity = '1';
                  });
          }

          function changeProjPage(direction) {
              const newPage = currentProjPage + direction;
              if (newPage < 1 || newPage > currentProjPages) return;
              loadCardProjects(newPage, activeFilterProjId);
          }

          function filterCardProjects(projId) {
              loadCardProjects(1, projId);
          }
          </script>
        </div>
      </div>

      <!-- Budget Utilization Donut -->
      <div class="col-12 col-xl-4">
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
          <?php if (empty($filteredProjects)): ?>
              <tr>
                <td colspan="6" class="empty-state">
                  <div class="empty-state-icon"><i class="fal fa-hard-hat"></i></div>
                  <div class="empty-state-text">No projects yet</div>
                </td>
              </tr>
          <?php else: ?>
              <?php foreach ($filteredProjects as $pr):
                $raw_pct = (float) ($pr['progress_pct'] ?? 0);
                $pct = round($raw_pct, 1);
                $clr = $raw_pct >= 75 ? 'var(--cd-green)' : ($raw_pct >= 40 ? 'var(--cd-amber)' : 'var(--cd-red)');
                $pclr = $raw_pct >= 75 ? 'var(--cd-green)' : ($raw_pct >= 40 ? 'var(--cd-amber)' : 'var(--cd-red)');
                $grad = $raw_pct >= 75 ? 'linear-gradient(90deg,#059669,#10b981)' : ($raw_pct >= 40 ? 'linear-gradient(90deg,#d97706,#f59e0b)' : 'linear-gradient(90deg,#dc2626,#ef4444)');
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
                          <div class="prog-fill" style="width:<?= min(100, $raw_pct) ?>%;background:<?= $grad ?>"></div>
                        </div>
                        <span style="font-weight:700;font-size:12px;color:<?= $pclr ?>"><?= cpFmtPct($raw_pct) ?></span>
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
  // Collapsible nested detail elements toggle
  function togglePhaseItems(containerId, toggleIcon) {
      const container = document.getElementById(containerId);
      if (!container) return;

      if (container.style.display === 'none' || container.style.display === '') {
          container.style.display = 'table-row';
          if (toggleIcon) {
              if (toggleIcon.classList.contains('fa-chevron-right')) {
                  toggleIcon.classList.remove('fa-chevron-right');
                  toggleIcon.classList.add('fa-chevron-down');
              } else if (toggleIcon.classList.contains('fa-eye')) {
                  toggleIcon.classList.remove('fa-eye');
                  toggleIcon.classList.add('fa-eye-slash');
              }
          }
      } else {
          container.style.display = 'none';
          if (toggleIcon) {
              if (toggleIcon.classList.contains('fa-chevron-down')) {
                  toggleIcon.classList.remove('fa-chevron-down');
                  toggleIcon.classList.add('fa-chevron-right');
              } else if (toggleIcon.classList.contains('fa-eye-slash')) {
                  toggleIcon.classList.remove('fa-eye-slash');
                  toggleIcon.classList.add('fa-eye');
              }
          }
      }
  }

  // Initialize Popovers using Bootstrap 5 Popover API
  function initPopovers() {
      if (window.popoverInstances) {
          window.popoverInstances.forEach(function(p) {
              p.dispose();
          });
      }
      window.popoverInstances = [];

      var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
      window.popoverInstances = popoverTriggerList.map(function (popoverTriggerEl) {
          return new bootstrap.Popover(popoverTriggerEl, {
              html: true,
              trigger: 'hover',
              placement: 'top',
              container: 'body'
          });
      });
  }

  document.addEventListener('DOMContentLoaded', function () {
    var base = '<?= $base ?>';

    // Initialize bootstrap popovers
    initPopovers();

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
<?php
$addPage = true;
$show_breadcrumb = false;
$base = $GLOBALS['base_url'];

// Helper: format BDT amounts
function conFmt($n) {
    $n = (float)$n;
    if ($n >= 10000000) return '৳' . number_format($n/10000000, 2) . ' Cr';
    if ($n >= 100000)   return '৳' . number_format($n/100000,   2) . ' L';
    if ($n >= 1000)     return '৳' . number_format($n/1000,     1) . 'K';
    return '৳' . number_format($n, 0);
}

$stats       = $stats      ?? [];
$projects    = $projects   ?? [];
$budgetUtil  = $budgetUtil ?? ['total'=>0,'spent'=>0,'pct'=>0,'remaining'=>0];
$stockAlerts = $stockAlerts ?? [];
$pendingAlerts = $pendingAlerts ?? [];
$soilAlerts  = $soilAlerts ?? [];

$statusColor = [
    'Planning'    => ['bg'=>'rgba(99,102,241,.12)', 'text'=>'#6366f1'],
    'In Progress' => ['bg'=>'rgba(245,158,11,.12)', 'text'=>'#f59e0b'],
    'On Hold'     => ['bg'=>'rgba(239,68,68,.12)',  'text'=>'#ef4444'],
    'Completed'   => ['bg'=>'rgba(16,185,129,.12)', 'text'=>'#10b981'],
];
?>
<style>
:root {
  --cd-primary: #198754;
  --cd-dark:    #0f5132;
  --cd-light:   #20a86a;
  --cd-blue:    #3b82f6;
  --cd-green:   #10b981;
  --cd-amber:   #f59e0b;
  --cd-red:     #ef4444;
  --cd-purple:  #8b5cf6;
  --cd-indigo:  #6366f1;
  --cd-teal:    #14b8a6;
  --cd-pink:    #ec4899;
}

/* ── KPI Cards ────────────────────────────────────────────── */
.kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:22px; }
.kpi-card {
  border-radius:14px;
  padding:18px 20px;
  position:relative;
  overflow:hidden;
  text-decoration:none !important;
  display:block;
  border:1px solid var(--border-color, rgba(0,0,0,.08));
  transition:transform .18s ease, box-shadow .18s ease;
}
.kpi-card:hover { transform:translateY(-3px); box-shadow:0 8px 28px rgba(0,0,0,.12); }
.kpi-accent {
  position:absolute; left:0; top:0; width:4px; height:100%; border-radius:14px 0 0 14px;
}
.kpi-icon {
  width:42px; height:42px; border-radius:12px;
  display:flex; align-items:center; justify-content:center;
  font-size:18px; margin-bottom:12px;
}
.kpi-value { font-size:26px; font-weight:800; line-height:1; margin-bottom:4px; }
.kpi-label { font-size:10.5px; font-weight:600; letter-spacing:1px; text-transform:uppercase; margin-bottom:3px; }
.kpi-sub   { font-size:11px; }
.kpi-badge {
  position:absolute; top:14px; right:14px;
  font-size:9px; font-weight:700; padding:3px 8px; border-radius:20px; letter-spacing:.5px;
}

/* ── Section Panels ───────────────────────────────────────── */
.con-panel {
  border-radius:14px; padding:22px;
  border:1px solid var(--border-color, rgba(0,0,0,.08));
}
.sec-title {
  font-size:11px; font-weight:700; letter-spacing:1.5px;
  text-transform:uppercase; margin-bottom:16px;
  display:flex; align-items:center; gap:8px;
}
.sec-title-dot {
  width:8px; height:8px; border-radius:50%;
}

/* ── Progress bars ────────────────────────────────────────── */
.prog-row { margin-bottom:13px; }
.prog-row:last-child { margin-bottom:0; }
.prog-meta { display:flex; justify-content:space-between; font-size:12px; margin-bottom:5px; }
.prog-track { height:8px; border-radius:6px; background:rgba(0,0,0,.07); overflow:hidden; }
.prog-fill  { height:100%; border-radius:6px; transition:width .5s ease; }

/* ── Donut ────────────────────────────────────────────────── */
.donut-ring {
  width:130px; height:130px; border-radius:50%;
  display:flex; align-items:center; justify-content:center;
  position:relative; margin:0 auto 14px;
}
.donut-inner {
  position:absolute; z-index:2; text-align:center;
}
.donut-pct { font-size:22px; font-weight:800; line-height:1; }
.donut-sub { font-size:10px; }
.donut-stats { display:flex; gap:20px; justify-content:center; flex-wrap:wrap; margin-top:14px; }
.donut-stat { text-align:center; }
.donut-stat-val { font-size:16px; font-weight:700; }
.donut-stat-lbl { font-size:10px; }

/* ── Project Table ────────────────────────────────────────── */
.proj-table { width:100%; border-collapse:collapse; }
.proj-table th {
  font-size:10px; letter-spacing:1.4px; text-transform:uppercase; font-weight:600;
  padding:0 12px 10px; border-bottom:2px solid rgba(0,0,0,.07); text-align:left;
}
.proj-table td {
  padding:12px; border-bottom:1px solid rgba(0,0,0,.05);
  font-size:12.5px; vertical-align:middle;
}
.proj-table tr:last-child td { border-bottom:none; }
.proj-table tr.proj-row { cursor:pointer; transition:background .15s; }
.proj-table tr.proj-row:hover td { background:rgba(59,130,246,.04); }
.proj-name-cell { font-weight:700; font-size:13px; }
.proj-loc-cell  { font-size:11px; margin-top:2px; }
.status-pill {
  font-size:10px; font-weight:700; padding:3px 10px; border-radius:20px; letter-spacing:.5px;
}

/* ── Alert Cards ──────────────────────────────────────────── */
.alert-item {
  display:flex; align-items:flex-start; gap:12px;
  padding:11px 13px; border-radius:10px; margin-bottom:8px;
  cursor:pointer; transition:opacity .15s;
  border:1px solid rgba(0,0,0,.06);
}
.alert-item:last-child { margin-bottom:0; }
.alert-item:hover { opacity:.8; }
.alert-icon-wrap {
  width:34px; height:34px; border-radius:9px; flex-shrink:0;
  display:flex; align-items:center; justify-content:center; font-size:15px;
}
.alert-item-title { font-size:12.5px; font-weight:600; }
.alert-item-sub   { font-size:11px; margin-top:1px; }

/* ── Stock Alert ──────────────────────────────────────────── */
.stock-item {
  padding:11px 13px; border-radius:10px; margin-bottom:8px;
  border:1px solid rgba(0,0,0,.06);
}
.stock-item:last-child { margin-bottom:0; }
.stock-name  { font-size:12.5px; font-weight:700; }
.stock-qty   { font-size:16px; font-weight:700; }
.stock-track { height:5px; border-radius:3px; background:rgba(0,0,0,.08); overflow:hidden; margin-top:6px; }
.stock-fill  { height:100%; border-radius:3px; background:linear-gradient(90deg,#ef4444,#f87171); }

/* ── Empty state ──────────────────────────────────────────── */
.empty-state { text-align:center; padding:28px 0; }
.empty-state-icon { font-size:28px; opacity:.3; margin-bottom:8px; }
.empty-state-text { font-size:12px; }
</style>

<!-- ═══════════════════════════════ GREEN HERO BANNER ═══════════════════════════════ -->
<div style="background:linear-gradient(135deg,#0f5132 0%,#198754 55%,#20a86a 100%);
            border-radius:18px;padding:26px 30px;margin-bottom:22px;
            position:relative;overflow:hidden">
  <div style="position:absolute;width:260px;height:260px;border-radius:50%;
    background:rgba(255,255,255,.04);top:-80px;right:-60px"></div>
  <div style="position:absolute;width:160px;height:160px;border-radius:50%;
    background:rgba(255,255,255,.03);bottom:-50px;left:-30px"></div>
  <div style="position:relative;z-index:2;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:14px">
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
      <a href="<?=$base?>/con-estimates" style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);
        color:#fff;border-radius:10px;padding:7px 18px;font-size:12px;font-weight:700;
        text-decoration:none;display:inline-flex;align-items:center;gap:6px">
        <i class="fal fa-file-alt"></i>Estimates
      </a>
      <a href="<?=$base?>/con-working-plans" style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);
        color:#fff;border-radius:10px;padding:7px 18px;font-size:12px;font-weight:700;
        text-decoration:none;display:inline-flex;align-items:center;gap:6px">
        <i class="fal fa-calendar-alt"></i>Work Plans
      </a>
      <a href="<?=$base?>/con-projects" style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);
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
  <a href="<?=$base?>/con-projects" class="kpi-card card__wrapper">
    <div class="kpi-accent" style="background:var(--cd-blue)"></div>
    <div class="kpi-icon" style="background:rgba(59,130,246,.12);color:var(--cd-blue)"><i class="fal fa-building"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Total Projects</div>
    <div class="kpi-value" style="color:var(--cd-blue)"><?= $stats['total_projects'] ?? 0 ?></div>
    <div class="kpi-sub" style="color:var(--text-muted)">Active construction sites</div>
  </a>

  <!-- 2. Total Initial Budget -->
  <a href="<?=$base?>/con-estimates" class="kpi-card card__wrapper">
    <div class="kpi-accent" style="background:var(--cd-green)"></div>
    <div class="kpi-icon" style="background:rgba(16,185,129,.12);color:var(--cd-green)"><i class="fal fa-money-bill-wave"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Total Initial Budget</div>
    <div class="kpi-value" style="color:var(--cd-green)"><?= conFmt($stats['total_budget'] ?? 0) ?></div>
    <div class="kpi-sub" style="color:var(--text-muted)">Approved estimates total</div>
  </a>

  <!-- 3. Material Budget -->
  <a href="<?=$base?>/con-estimates" class="kpi-card card__wrapper">
    <div class="kpi-accent" style="background:var(--cd-amber)"></div>
    <div class="kpi-icon" style="background:rgba(245,158,11,.12);color:var(--cd-amber)"><i class="fal fa-boxes"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Total Material Budget</div>
    <div class="kpi-value" style="color:var(--cd-amber)"><?= conFmt($stats['total_material'] ?? 0) ?></div>
    <div class="kpi-sub" style="color:var(--text-muted)">Construction materials cost</div>
  </a>

  <!-- 4. Manpower Budget -->
  <a href="<?=$base?>/con-estimates" class="kpi-card card__wrapper">
    <div class="kpi-accent" style="background:var(--cd-purple)"></div>
    <div class="kpi-icon" style="background:rgba(139,92,246,.12);color:var(--cd-purple)"><i class="fal fa-hard-hat"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Total Manpower Budget</div>
    <div class="kpi-value" style="color:var(--cd-purple)"><?= conFmt($stats['total_labour'] ?? 0) ?></div>
    <div class="kpi-sub" style="color:var(--text-muted)">Labour &amp; workforce cost</div>
  </a>

</div>

<!-- ══════════════════════ ROW 2: 4 MORE CARDS ══════════════════════════════ -->
<div class="kpi-grid mb-4">

  <!-- 5. Stock Alerts -->
  <a href="<?=$base?>/con-stocks" class="kpi-card card__wrapper">
    <div class="kpi-accent" style="background:var(--cd-red)"></div>
    <div class="kpi-icon" style="background:rgba(239,68,68,.12);color:var(--cd-red)"><i class="fal fa-warehouse"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Stock Alerts</div>
    <div class="kpi-value" style="color:var(--cd-red)"><?= $stats['stock_alerts'] ?? 0 ?></div>
    <div class="kpi-sub" style="color:var(--text-muted)">Items below minimum level</div>
    <?php if (($stats['stock_alerts'] ?? 0) > 0): ?>
    <span class="kpi-badge" style="background:rgba(239,68,68,.12);color:var(--cd-red)"><i class="fas fa-exclamation-triangle me-1"></i>ALERT</span>
    <?php endif; ?>
  </a>

  <!-- 6. Pending Approvals -->
  <a href="<?=$base?>/con-pending-payments" class="kpi-card card__wrapper">
    <div class="kpi-accent" style="background:var(--cd-indigo)"></div>
    <div class="kpi-icon" style="background:rgba(99,102,241,.12);color:var(--cd-indigo)"><i class="fal fa-check-circle"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Pending Approvals</div>
    <div class="kpi-value" style="color:var(--cd-indigo)"><?= $stats['pending_approvals'] ?? 0 ?></div>
    <div class="kpi-sub" style="color:var(--text-muted)">Bills, funds &amp; vendor payments</div>
    <?php if (($stats['pending_approvals'] ?? 0) > 0): ?>
    <span class="kpi-badge" style="background:rgba(99,102,241,.12);color:var(--cd-indigo)">PENDING</span>
    <?php endif; ?>
  </a>

  <!-- 7. Budget Requests -->
  <a href="<?=$base?>/con-budget-extends" class="kpi-card card__wrapper">
    <div class="kpi-accent" style="background:var(--cd-teal)"></div>
    <div class="kpi-icon" style="background:rgba(20,184,166,.12);color:var(--cd-teal)"><i class="fal fa-file-invoice-dollar"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Budget Requests</div>
    <div class="kpi-value" style="color:var(--cd-teal)"><?= $stats['budget_requests'] ?? 0 ?></div>
    <div class="kpi-sub" style="color:var(--text-muted)">Pending extension requests</div>
    <?php if (($stats['budget_requests'] ?? 0) > 0): ?>
    <span class="kpi-badge" style="background:rgba(20,184,166,.12);color:var(--cd-teal)">REVIEW</span>
    <?php endif; ?>
  </a>

  <!-- 8. Lab Testing Alerts -->
  <a href="<?=$base?>/con-soil-tests" class="kpi-card card__wrapper">
    <div class="kpi-accent" style="background:var(--cd-pink)"></div>
    <div class="kpi-icon" style="background:rgba(236,72,153,.12);color:var(--cd-pink)"><i class="fal fa-flask"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Lab Testing Alerts</div>
    <div class="kpi-value" style="color:var(--cd-pink)"><?= $stats['lab_alerts'] ?? 0 ?></div>
    <div class="kpi-sub" style="color:var(--text-muted)">Soil test records</div>
  </a>

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
          $pct   = round((float)($pr['progress_pct'] ?? 0), 1);
          $color = $pct >= 75 ? 'var(--cd-green)' : ($pct >= 40 ? 'var(--cd-amber)' : 'var(--cd-red)');
          $grad  = $pct >= 75 ? 'linear-gradient(90deg,#059669,#10b981)' : ($pct >= 40 ? 'linear-gradient(90deg,#d97706,#f59e0b)' : 'linear-gradient(90deg,#dc2626,#ef4444)');
          $sc    = $statusColor[$pr['project_status'] ?? ''] ?? ['bg'=>'rgba(0,0,0,.06)','text'=>'var(--text-muted)'];
        ?>
        <div class="prog-row" onclick="location.href='<?=$base?>/con-project/<?= $pr['id'] ?>'" style="cursor:pointer">
          <div class="prog-meta">
            <div>
              <strong style="font-size:13px"><?= htmlspecialchars($pr['name']) ?></strong>
              <span style="font-size:11px;color:var(--text-muted);margin-left:8px"><i class="fal fa-map-marker-alt me-1"></i><?= htmlspecialchars($pr['location_name'] ?: 'N/A') ?></span>
              <span class="status-pill ms-2" style="background:<?=$sc['bg']?>;color:<?=$sc['text']?>"><?= $pr['project_status'] ?? 'N/A' ?></span>
            </div>
            <div style="text-align:right">
              <span style="font-weight:700;font-size:13px;color:<?=$color?>"><?=$pct?>%</span>
              <span style="font-size:10px;color:var(--text-muted);margin-left:8px"><?= conFmt($pr['total_budget']) ?> budget</span>
            </div>
          </div>
          <div class="prog-track">
            <div class="prog-fill" style="width:<?=$pct?>%;background:<?=$grad?>"></div>
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
        $pct  = (float)($budgetUtil['pct'] ?? 0);
        $deg  = round($pct * 3.6);
        $clr  = $pct > 85 ? '#ef4444' : ($pct > 60 ? '#f59e0b' : '#10b981');
      ?>
      <div class="donut-ring" style="background:conic-gradient(<?=$clr?> 0% <?=$deg?>deg, rgba(0,0,0,.08) <?=$deg?>deg 360deg)">
        <div style="width:92px;height:92px;border-radius:50%;position:absolute;background:var(--card-bg,#fff)"></div>
        <div class="donut-inner">
          <div class="donut-pct" style="color:<?=$clr?>"><?=$pct?>%</div>
          <div class="donut-sub" style="color:var(--text-muted)">Used</div>
        </div>
      </div>
      <div class="donut-stats">
        <div class="donut-stat">
          <div class="donut-stat-val"><?= conFmt($budgetUtil['total']) ?></div>
          <div class="donut-stat-lbl" style="color:var(--text-muted)">Total Budget</div>
        </div>
        <div class="donut-stat">
          <div class="donut-stat-val" style="color:<?=$clr?>"><?= conFmt($budgetUtil['spent']) ?></div>
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
    <a href="<?=$base?>/con-projects" style="font-size:11px;color:var(--cd-blue);text-decoration:none">View All <i class="fal fa-arrow-right ms-1"></i></a>
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
      <tr><td colspan="6" class="empty-state"><div class="empty-state-icon"><i class="fal fa-hard-hat"></i></div><div class="empty-state-text">No projects yet</div></td></tr>
      <?php else: ?>
      <?php foreach ($projects as $pr):
        $pct = round((float)($pr['progress_pct'] ?? 0), 1);
        $clr = $pct >= 75 ? 'var(--cd-green)' : ($pct >= 40 ? 'var(--cd-amber)' : 'var(--cd-red)');
        $pclr = $pct >= 75 ? 'var(--cd-green)' : ($pct >= 40 ? 'var(--cd-amber)' : 'var(--cd-red)');
        $grad = $pct >= 75 ? 'linear-gradient(90deg,#059669,#10b981)' : ($pct >= 40 ? 'linear-gradient(90deg,#d97706,#f59e0b)' : 'linear-gradient(90deg,#dc2626,#ef4444)');
        $sc   = $statusColor[$pr['project_status'] ?? ''] ?? ['bg'=>'rgba(0,0,0,.06)','text'=>'var(--text-muted)'];
        $budgetUsedPct = ($pr['total_budget'] > 0) ? round($pr['total_spent'] / $pr['total_budget'] * 100, 1) : 0;
      ?>
      <tr class="proj-row" onclick="location.href='<?=$base?>/con-project/<?= $pr['id'] ?>'">
        <td>
          <div class="proj-name-cell"><?= htmlspecialchars($pr['name']) ?></div>
          <div class="proj-loc-cell" style="color:var(--text-muted)"><i class="fal fa-map-marker-alt me-1"></i><?= htmlspecialchars($pr['location_name'] ?: 'N/A') ?></div>
        </td>
        <td style="font-weight:600"><?= $pr['total_floors'] ?: '—' ?></td>
        <td>
          <div style="display:flex;align-items:center;gap:8px;min-width:130px">
            <div class="prog-track" style="flex:1">
              <div class="prog-fill" style="width:<?=$pct?>%;background:<?=$grad?>"></div>
            </div>
            <span style="font-weight:700;font-size:12px;color:<?=$pclr?>"><?=$pct?>%</span>
          </div>
        </td>
        <td>
          <div style="font-weight:600"><?= conFmt($pr['total_spent']) ?></div>
          <div style="font-size:10.5px;color:var(--text-muted)">of <?= conFmt($pr['total_budget']) ?> (<?=$budgetUsedPct?>%)</div>
        </td>
        <td>
          <span class="status-pill" style="background:<?=$sc['bg']?>;color:<?=$sc['text']?>">
            <?= htmlspecialchars($pr['project_status'] ?? 'Unknown') ?>
          </span>
        </td>
        <td style="text-align:right">
          <a href="<?=$base?>/con-project/<?= $pr['id'] ?>" class="btn btn-sm" style="font-size:11px;padding:4px 12px;background:rgba(59,130,246,.1);color:var(--cd-blue);border-radius:6px;text-decoration:none" onclick="event.stopPropagation()">View <i class="fal fa-arrow-right ms-1"></i></a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

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
      <a href="<?=$base?>/con-stocks" class="alert-item" style="background:rgba(239,68,68,.05);text-decoration:none">
        <div class="alert-icon-wrap" style="background:rgba(239,68,68,.12);color:var(--cd-red)"><i class="fal fa-warehouse"></i></div>
        <div>
          <div class="alert-item-title"><?= $stats['stock_alerts'] ?> Low Stock Item<?= $stats['stock_alerts'] > 1 ? 's' : '' ?></div>
          <div class="alert-item-sub" style="color:var(--text-muted)">Materials below minimum level</div>
        </div>
      </a>
      <?php endif; ?>

      <?php if (($stats['pending_approvals'] ?? 0) > 0): ?>
      <a href="<?=$base?>/con-pending-payments" class="alert-item" style="background:rgba(99,102,241,.05);text-decoration:none">
        <div class="alert-icon-wrap" style="background:rgba(99,102,241,.12);color:var(--cd-indigo)"><i class="fal fa-hourglass-half"></i></div>
        <div>
          <div class="alert-item-title"><?= $stats['pending_approvals'] ?> Pending Approval<?= $stats['pending_approvals'] > 1 ? 's' : '' ?></div>
          <div class="alert-item-sub" style="color:var(--text-muted)">Bills &amp; payments awaiting action</div>
        </div>
      </a>
      <?php endif; ?>

      <?php if (($stats['budget_requests'] ?? 0) > 0): ?>
      <a href="<?=$base?>/con-budget-extends" class="alert-item" style="background:rgba(20,184,166,.05);text-decoration:none">
        <div class="alert-icon-wrap" style="background:rgba(20,184,166,.12);color:var(--cd-teal)"><i class="fal fa-file-invoice-dollar"></i></div>
        <div>
          <div class="alert-item-title"><?= $stats['budget_requests'] ?> Budget Extension<?= $stats['budget_requests'] > 1 ? 's' : '' ?></div>
          <div class="alert-item-sub" style="color:var(--text-muted)">Pending review &amp; approval</div>
        </div>
      </a>
      <?php endif; ?>

      <?php if (empty($pendingAlerts) && ($stats['stock_alerts']??0) == 0 && ($stats['pending_approvals']??0) == 0 && ($stats['budget_requests']??0) == 0): ?>
      <div class="empty-state">
        <div class="empty-state-icon"><i class="fal fa-check-circle" style="color:var(--cd-green)"></i></div>
        <div class="empty-state-text" style="color:var(--text-muted)">All clear — no alerts</div>
      </div>
      <?php endif; ?>

      <!-- Recent pending items list -->
      <?php if (!empty($pendingAlerts)): ?>
      <div style="margin-top:12px;padding-top:12px;border-top:1px solid rgba(0,0,0,.07)">
        <div style="font-size:10px;font-weight:600;letter-spacing:1px;text-transform:uppercase;color:var(--text-muted);margin-bottom:8px">Recent</div>
        <?php foreach (array_slice($pendingAlerts, 0, 3) as $pa): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;padding:6px 0;border-bottom:1px solid rgba(0,0,0,.05)">
          <span><?= $pa['description']?></span>
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
        <a href="<?=$base?>/con-soil-tests" style="font-size:10px;color:var(--cd-blue);text-decoration:none;margin-left:auto">View All <i class="fal fa-arrow-right ms-1"></i></a>
      </div>
      <?php if (empty($soilAlerts)): ?>
      <div class="empty-state">
        <div class="empty-state-icon"><i class="fal fa-flask"></i></div>
        <div class="empty-state-text" style="color:var(--text-muted)">No soil test records</div>
      </div>
      <?php else: ?>
      <?php foreach ($soilAlerts as $st): ?>
      <a href="<?=$base?>/con-soil-tests" class="alert-item" style="background:rgba(236,72,153,.05);text-decoration:none">
        <div class="alert-icon-wrap" style="background:rgba(236,72,153,.12);color:var(--cd-pink)"><i class="fal fa-flask"></i></div>
        <div>
          <div class="alert-item-title"><?= htmlspecialchars($st['project_name'] ?? 'Project') ?></div>
          <div class="alert-item-sub" style="color:var(--text-muted)">
            Test: <?= $st['test_date'] ? date('d M Y', strtotime($st['test_date'])) : 'N/A' ?>
            <?php if (!empty($st['lab_test_document_link'])): ?>
            · <a href="<?= htmlspecialchars($st['lab_test_document_link']) ?>" target="_blank" style="color:var(--cd-blue)" onclick="event.stopPropagation()"><i class="fal fa-link me-1"></i>Lab Doc</a>
            <?php endif; ?>
            <?php if (!empty($st['field_test_doc_link'])): ?>
            · <a href="<?= htmlspecialchars($st['field_test_doc_link']) ?>" target="_blank" style="color:var(--cd-green)" onclick="event.stopPropagation()"><i class="fal fa-link me-1"></i>Field Doc</a>
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
        <a href="<?=$base?>/con-stocks" style="font-size:10px;color:var(--cd-blue);text-decoration:none;margin-left:auto">Manage <i class="fal fa-arrow-right ms-1"></i></a>
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
            <div style="font-size:10px;color:var(--text-muted)"><i class="fal fa-warehouse me-1"></i><?= htmlspecialchars($sk['warehouse_name']) ?></div>
          </div>
          <div style="text-align:right">
            <div class="stock-qty" style="color:var(--cd-red)"><?= $sk['current_quantity'] ?> <span style="font-size:11px"><?= $sk['unit'] ?></span></div>
            <div style="font-size:10px;color:var(--text-muted)">Min: <?= $sk['min_stock_level'] ?></div>
          </div>
        </div>
        <div class="stock-track">
          <div class="stock-fill" style="width:<?=$pct?>%"></div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<div style="height:30px"></div>
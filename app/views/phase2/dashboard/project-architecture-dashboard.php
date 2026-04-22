<?php
$addPage = true;
$show_breadcrumb = false;
$base = $GLOBALS['base_url'];

function paFmt($n) {
    $n = (float)$n;
    if ($n >= 10000000) return '৳' . number_format($n/10000000, 2) . ' Cr';
    if ($n >= 100000)   return '৳' . number_format($n/100000,   2) . ' L';
    if ($n >= 1000)     return '৳' . number_format($n/1000,     1) . 'K';
    return '৳' . number_format($n, 0);
}

$totalPOs        = $totalPOs        ?? 0;
$pendingPRs      = $pendingPRs      ?? 0;
$approvedPRs     = $approvedPRs     ?? 0;
$totalPOValue    = $totalPOValue    ?? 0.0;
$recentPOs       = $recentPOs       ?? [];
$statusBreakdown = $statusBreakdown ?? [];
?>
<style>
:root {
  --pa-primary: #198754;
  --pa-dark:    #0f5132;
  --pa-light:   #20a86a;
  --pa-blue:    #3b82f6;
  --pa-green:   #10b981;
  --pa-amber:   #f59e0b;
  --pa-red:     #ef4444;
  --pa-purple:  #8b5cf6;
  --pa-indigo:  #6366f1;
  --pa-teal:    #14b8a6;
}
.pa-hero {
  background: linear-gradient(135deg, #0f5132 0%, #198754 55%, #20a86a 100%);
  border-radius: 18px; padding: 28px 32px; margin-bottom: 24px;
  position: relative; overflow: hidden;
}
.pa-hero::before { content:''; position:absolute; width:270px; height:270px; border-radius:50%; background:rgba(255,255,255,.04); top:-90px; right:-70px; }
.pa-hero::after  { content:''; position:absolute; width:170px; height:170px; border-radius:50%; background:rgba(255,255,255,.03); bottom:-55px; left:-35px; }
.pa-role-badge {
  display:inline-flex; align-items:center; gap:8px;
  background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.2);
  padding:5px 14px; border-radius:20px; font-size:11px; font-weight:700;
  letter-spacing:1.5px; text-transform:uppercase; color:#fff; margin-bottom:14px;
}
.pa-hero-title { font-size:28px; font-weight:900; color:#fff; margin:0 0 6px; }
.pa-hero-sub   { font-size:13px; color:rgba(255,255,255,.7); margin:0; }
.pa-btn-white {
  background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.3);
  color:#fff; border-radius:10px; padding:8px 18px; font-size:12px; font-weight:700;
  text-decoration:none; display:inline-flex; align-items:center; gap:6px; transition:background .18s;
}
.pa-btn-white:hover { background:rgba(255,255,255,.25); color:#fff; text-decoration:none; }
.pa-hero-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:16px; }

/* KPI Grid */
.kpi-grid-4 { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:22px; }
@media(max-width:992px){ .kpi-grid-4 { grid-template-columns:repeat(2,1fr); } }
.kpi-card {
  border-radius:14px; padding:18px 20px; position:relative; overflow:hidden;
  border:1px solid rgba(0,0,0,.08); text-decoration:none !important; display:block;
  transition:transform .18s, box-shadow .18s; background:var(--card-bg,#fff);
}
.kpi-card:hover { transform:translateY(-3px); box-shadow:0 8px 28px rgba(0,0,0,.12); }
.kpi-accent { position:absolute; left:0; top:0; width:4px; height:100%; border-radius:14px 0 0 14px; }
.kpi-icon  { width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:18px; margin-bottom:12px; }
.kpi-value { font-size:28px; font-weight:900; line-height:1; margin-bottom:4px; }
.kpi-label { font-size:10.5px; font-weight:700; letter-spacing:1px; text-transform:uppercase; margin-bottom:3px; }
.kpi-sub   { font-size:11px; color:var(--text-muted); }
.kpi-badge { position:absolute; top:14px; right:14px; font-size:9px; font-weight:700; padding:3px 8px; border-radius:20px; }

/* Panel */
.pa-panel { border-radius:14px; padding:22px; border:1px solid rgba(0,0,0,.08); background:var(--card-bg,#fff); margin-bottom:22px; }
.pa-panel-title { font-size:11px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
.pa-dot { width:8px; height:8px; border-radius:50%; }
.empty-msg { text-align:center; padding:28px 0; color:var(--text-muted); font-size:12px; }

/* Table */
.pa-table { width:100%; border-collapse:collapse; }
.pa-table th { font-size:10px; letter-spacing:1.4px; text-transform:uppercase; font-weight:600; padding:0 10px 10px; border-bottom:2px solid rgba(0,0,0,.07); text-align:left; }
.pa-table td { padding:11px 10px; border-bottom:1px solid rgba(0,0,0,.05); font-size:12.5px; vertical-align:middle; }
.pa-table tr:last-child td { border-bottom:none; }

/* Status pills in breakdown */
.status-row { display:flex; align-items:center; gap:12px; padding:10px 0; border-bottom:1px solid rgba(0,0,0,.06); }
.status-row:last-child { border-bottom:none; }
.status-track { flex:1; height:8px; border-radius:6px; background:rgba(0,0,0,.07); overflow:hidden; }
.status-fill  { height:100%; border-radius:6px; }

/* Value highlight */
.big-value-box {
  border-radius:14px; padding:22px; text-align:center;
  background:linear-gradient(135deg,#1e1b4b,#3730a3);
}
</style>

<!-- ═════ HERO ═════ -->
<div class="pa-hero" style="position:relative;z-index:1">
  <div style="position:relative;z-index:2;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px">
    <div>
      <div class="pa-role-badge"><i class="fa-solid fa-building-columns"></i> Project Architecture</div>
      <h4 class="pa-hero-title">Architecture Dashboard</h4>
      <p class="pa-hero-sub">Purchase order overview · <?= date('d M Y, h:i A') ?></p>
    </div>
  </div>
  <div class="pa-hero-actions">
    <a href="<?= $base ?>/con-purchase-orders" class="pa-btn-white"><i class="fal fa-cart-shopping"></i> Purchase Orders</a>
    <a href="<?= $base ?>/con-purchase-requests" class="pa-btn-white"><i class="fal fa-file-alt"></i> Purchase Requests</a>
  </div>
</div>

<!-- ═════ KPI GRID ═════ -->
<div class="kpi-grid-4">

  <a href="<?= $base ?>/con-purchase-orders" class="kpi-card">
    <div class="kpi-accent" style="background:var(--pa-indigo)"></div>
    <div class="kpi-icon" style="background:rgba(99,102,241,.12);color:var(--pa-indigo)"><i class="fal fa-file-invoice"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Total Purchase Orders</div>
    <div class="kpi-value" style="color:var(--pa-indigo)"><?= $totalPOs ?></div>
    <div class="kpi-sub">Generated POs</div>
  </a>

  <a href="<?= $base ?>/con-purchase-requests" class="kpi-card">
    <div class="kpi-accent" style="background:var(--pa-amber)"></div>
    <div class="kpi-icon" style="background:rgba(245,158,11,.12);color:var(--pa-amber)"><i class="fal fa-clock"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Pending Requests</div>
    <div class="kpi-value" style="color:var(--pa-amber)"><?= $pendingPRs ?></div>
    <div class="kpi-sub">Awaiting approval</div>
    <?php if ($pendingPRs > 0): ?>
    <span class="kpi-badge" style="background:rgba(245,158,11,.12);color:var(--pa-amber)">REVIEW</span>
    <?php endif; ?>
  </a>

  <a href="<?= $base ?>/con-purchase-requests" class="kpi-card">
    <div class="kpi-accent" style="background:var(--pa-green)"></div>
    <div class="kpi-icon" style="background:rgba(16,185,129,.12);color:var(--pa-green)"><i class="fal fa-check-circle"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Approved Requests</div>
    <div class="kpi-value" style="color:var(--pa-green)"><?= $approvedPRs ?></div>
    <div class="kpi-sub">Ready for PO generation</div>
  </a>

  <div class="kpi-card" style="cursor:default">
    <div class="kpi-accent" style="background:var(--pa-blue)"></div>
    <div class="kpi-icon" style="background:rgba(59,130,246,.12);color:var(--pa-blue)"><i class="fal fa-money-bill-wave"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Total PO Value</div>
    <div class="kpi-value" style="color:var(--pa-blue);font-size:22px"><?= paFmt($totalPOValue) ?></div>
    <div class="kpi-sub">Combined purchase orders value</div>
  </div>

</div>

<!-- ═════ ROW 2 ═════ -->
<div class="row">

  <!-- Recent POs -->
  <div class="col-lg-8">
    <div class="pa-panel">
      <div class="pa-panel-title">
        <div class="pa-dot" style="background:var(--pa-indigo)"></div>
        Recent Purchase Orders
        <a href="<?= $base ?>/con-purchase-orders" style="font-size:10px;color:var(--pa-blue);margin-left:auto;text-decoration:none">View All <i class="fal fa-arrow-right ms-1"></i></a>
      </div>
      <?php if (empty($recentPOs)): ?>
        <div class="empty-msg">No purchase orders yet</div>
      <?php else: ?>
      <table class="pa-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Vendor</th>
            <th>Project</th>
            <th>Total Amount</th>
            <th>Created</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentPOs as $po): ?>
          <tr>
            <td style="font-size:11px;font-weight:700;color:var(--pa-indigo)">#<?= $po['id'] ?></td>
            <td><strong><?= htmlspecialchars($po['vendor_name'] ?? 'N/A') ?></strong></td>
            <td style="font-size:11.5px;color:var(--text-muted)"><?= htmlspecialchars($po['project_name'] ?? 'N/A') ?></td>
            <td><strong style="color:var(--pa-green)"><?= paFmt($po['total_amount'] ?? 0) ?></strong></td>
            <td style="font-size:11px;color:var(--text-muted)"><?= isset($po['created_at']) ? date('d M Y', strtotime($po['created_at'])) : 'N/A' ?></td>
            <td>
              <a href="<?= $base ?>/con-purchase-order/<?= $po['id'] ?>" style="font-size:11px;color:var(--pa-blue);text-decoration:none">
                View <i class="fal fa-arrow-right ms-1"></i>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- Right Column -->
  <div class="col-lg-4">

    <!-- Request Status Breakdown -->
    <div class="pa-panel">
      <div class="pa-panel-title">
        <div class="pa-dot" style="background:var(--pa-amber)"></div>
        Request Status Breakdown
      </div>
      <?php
      $statusColors = [
        'Pending'   => ['bg'=>'rgba(245,158,11,.12)','fill'=>'#f59e0b','text'=>'#f59e0b'],
        'Approved'  => ['bg'=>'rgba(16,185,129,.12)', 'fill'=>'#10b981','text'=>'#10b981'],
        'Rejected'  => ['bg'=>'rgba(239,68,68,.12)',  'fill'=>'#ef4444','text'=>'#ef4444'],
        'Completed' => ['bg'=>'rgba(59,130,246,.12)', 'fill'=>'#3b82f6','text'=>'#3b82f6'],
      ];
      $totalReqs = array_sum(array_column($statusBreakdown, 'cnt'));
      ?>
      <?php if (empty($statusBreakdown)): ?>
        <div class="empty-msg">No requests found</div>
      <?php else: ?>
        <?php foreach ($statusBreakdown as $sb):
          $clr = $statusColors[$sb['status']] ?? ['fill'=>'#94a3b8','text'=>'#64748b'];
          $pct = $totalReqs > 0 ? round($sb['cnt'] / $totalReqs * 100) : 0;
        ?>
        <div class="status-row">
          <div style="min-width:90px;font-size:12px;font-weight:700;color:<?= $clr['text'] ?>"><?= htmlspecialchars($sb['status']) ?></div>
          <div class="status-track"><div class="status-fill" style="width:<?= $pct ?>%;background:<?= $clr['fill'] ?>"></div></div>
          <div style="font-size:12px;font-weight:800;min-width:32px;text-align:right;color:<?= $clr['text'] ?>"><?= $sb['cnt'] ?></div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="pa-panel">
      <div class="pa-panel-title">
        <div class="pa-dot" style="background:var(--pa-teal)"></div>
        Quick Actions
      </div>
      <div style="display:flex;flex-direction:column;gap:10px">
        <a href="<?= $base ?>/con-purchase-requests" style="display:flex;align-items:center;gap:12px;padding:13px;border-radius:12px;border:1px solid rgba(0,0,0,.08);text-decoration:none;color:var(--pa-amber);font-size:12px;font-weight:700;transition:box-shadow .15s" onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,.1)'" onmouseout="this.style.boxShadow='none'">
          <div style="width:38px;height:38px;border-radius:10px;background:rgba(245,158,11,.12);display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0"><i class="fal fa-file-alt"></i></div>
          <span>View All Purchase Requests</span>
        </a>
        <a href="<?= $base ?>/con-purchase-orders" style="display:flex;align-items:center;gap:12px;padding:13px;border-radius:12px;border:1px solid rgba(0,0,0,.08);text-decoration:none;color:var(--pa-indigo);font-size:12px;font-weight:700;transition:box-shadow .15s" onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,.1)'" onmouseout="this.style.boxShadow='none'">
          <div style="width:38px;height:38px;border-radius:10px;background:rgba(99,102,241,.12);display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0"><i class="fal fa-cart-shopping"></i></div>
          <span>Manage Purchase Orders</span>
        </a>
        <a href="<?= $base ?>/con-vendors" style="display:flex;align-items:center;gap:12px;padding:13px;border-radius:12px;border:1px solid rgba(0,0,0,.08);text-decoration:none;color:var(--pa-blue);font-size:12px;font-weight:700;transition:box-shadow .15s" onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,.1)'" onmouseout="this.style.boxShadow='none'">
          <div style="width:38px;height:38px;border-radius:10px;background:rgba(59,130,246,.12);display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0"><i class="fal fa-user-tie"></i></div>
          <span>Vendor Directory</span>
        </a>
      </div>
    </div>

  </div>
</div>
<div style="height:30px"></div>

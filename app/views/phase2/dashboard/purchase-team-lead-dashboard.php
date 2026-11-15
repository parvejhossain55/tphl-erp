<?php
$addPage = true;
$show_breadcrumb = false;
$base = $GLOBALS['base_url'];

function ptlFmt($n) {
    $n = (float)$n;
    if ($n >= 10000000) return '৳' . number_format($n/10000000, 2) . ' Cr';
    if ($n >= 100000)   return '৳' . number_format($n/100000,   2) . ' L';
    if ($n >= 1000)     return '৳' . number_format($n/1000,     1) . 'K';
    return '৳' . number_format($n, 0);
}

$totalPOs        = $totalPOs        ?? 0;
$totalVendors    = $totalVendors    ?? 0;
$totalPenalties  = $totalPenalties  ?? 0;
$totalIssues     = $totalIssues     ?? 0;
$totalMaterials  = $totalMaterials  ?? 0;
$pendingPayments = $pendingPayments ?? 0;
$recentVendors   = $recentVendors   ?? [];
$recentPOs       = $recentPOs       ?? [];
$stockOverview    = $stockOverview    ?? [];
$pendingBOQsForPR = $pendingBOQsForPR ?? [];
$pendingPRsForPO  = $pendingPRsForPO  ?? [];
$allBOQs          = $allBOQs          ?? [];
$prsForApproval   = $prsForApproval   ?? [];
$posForApproval   = $posForApproval   ?? [];
?>
<div class="row"><div class="col-12">
<style>
:root {
  --ptl-primary: #198754;
  --ptl-dark:    #0f5132;
  --ptl-light:   #20a86a;
  --ptl-blue:    #3b82f6;
  --ptl-green:   #10b981;
  --ptl-amber:   #f59e0b;
  --ptl-red:     #ef4444;
  --ptl-purple:  var(--clr-theme-secondary);
  --ptl-teal:    #14b8a6;
  --ptl-orange:  #f97316;
  --ptl-indigo:  var(--clr-theme-primary);
}
.ptl-hero {
  background: linear-gradient(135deg, #0f5132 0%, #198754 55%, #20a86a 100%);
  border-radius: 18px; padding: 28px 32px; margin-bottom: 24px;
  position: relative; overflow: hidden;
}
.ptl-hero::before {
  content: ''; position: absolute; width: 260px; height: 260px;
  border-radius: 50%; background: rgba(255,255,255,.04);
  top: -80px; right: -60px;
}
.ptl-hero::after {
  content: ''; position: absolute; width: 160px; height: 160px;
  border-radius: 50%; background: rgba(255,255,255,.03);
  bottom: -50px; left: -30px;
}
.ptl-role-badge {
  display: inline-flex; align-items: center; gap: 8px;
  background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2);
  padding: 5px 14px; border-radius: 20px; font-size: 11px; font-weight: 700;
  letter-spacing: 1.5px; text-transform: uppercase; color: #fff; margin-bottom: 14px;
}
.ptl-hero-title { font-size: 28px; font-weight: 900; color: #fff; margin: 0 0 6px; }
.ptl-hero-sub   { font-size: 13px; color: rgba(255,255,255,.7); margin: 0; }
.ptl-btn-white {
  background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.3);
  color: #fff; border-radius: 10px; padding: 8px 18px; font-size: 12px;
  font-weight: 700; text-decoration: none; display: inline-flex; align-items: center;
  gap: 6px; transition: background .18s;
}
.ptl-btn-white:hover { background: rgba(255,255,255,.25); color: #fff; text-decoration: none; }
.ptl-hero-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 16px; }

/* KPI Grid – 3 cols */
.kpi-grid-3c { display: grid; grid-template-columns: repeat(3,1fr); gap: 14px; margin-bottom: 22px; }
@media(max-width:992px){ .kpi-grid-3c { grid-template-columns: repeat(2,1fr); } }
.kpi-card {
  border-radius: 14px; padding: 18px 20px; position: relative; overflow: hidden;
  border: 1px solid rgba(0,0,0,.08); text-decoration: none !important; display: block;
  transition: transform .18s, box-shadow .18s; background: var(--clr-bg-card, #fff);
}
.kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 28px rgba(0,0,0,.12); }
.kpi-accent { position: absolute; left: 0; top: 0; width: 4px; height: 100%; border-radius: 14px 0 0 14px; }
.kpi-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-bottom: 12px; }
.kpi-value { font-size: 28px; font-weight: 900; line-height: 1; margin-bottom: 4px; }
.kpi-label { font-size: 10.5px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 3px; }
.kpi-sub   { font-size: 11px; color: var(--text-muted); }
.kpi-badge { position: absolute; top: 14px; right: 14px; font-size: 9px; font-weight: 700; padding: 3px 8px; border-radius: 20px; letter-spacing: .5px; }

/* Panel */
.ptl-panel { border-radius: 14px; padding: 22px; border: 1px solid rgba(0,0,0,.08); background: var(--clr-bg-card, #fff); margin-bottom: 22px; }
.ptl-panel-title { font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.ptl-dot { width: 8px; height: 8px; border-radius: 50%; }

/* Table */
.ptl-table { width: 100%; border-collapse: collapse; }
.ptl-table th { font-size: 10px; letter-spacing: 1.4px; text-transform: uppercase; font-weight: 600; padding: 0 10px 10px; border-bottom: 2px solid rgba(0,0,0,.07); text-align: left; }
.ptl-table td { padding: 11px 10px; border-bottom: 1px solid rgba(0,0,0,.05); font-size: 12.5px; vertical-align: middle; }
.ptl-table tr:last-child td { border-bottom: none; }
.empty-msg { text-align: center; padding: 28px 0; color: var(--text-muted); font-size: 12px; }

/* Stock Bar */
.stock-bar-row { padding: 10px 0; border-bottom: 1px solid rgba(0,0,0,.06); }
.stock-bar-row:last-child { border-bottom: none; }
.stock-bar-row .label { font-size: 12px; font-weight: 600; margin-bottom: 5px; display: flex; justify-content: space-between; }
.stock-track { height: 7px; border-radius: 6px; background: rgba(0,0,0,.07); overflow: hidden; }
.stock-fill  { height: 100%; border-radius: 6px; }

/* Quick Links */
.quick-links2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
.ql2-btn {
  display: flex; align-items: center; gap: 10px; padding: 13px 14px;
  border-radius: 12px; text-decoration: none; border: 1px solid rgba(0,0,0,.08);
  font-size: 12px; font-weight: 700; transition: transform .15s, box-shadow .15s;
  background: var(--clr-bg-card, #fff);
}
.ql2-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.1); text-decoration: none; }
.ql2-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 17px; flex-shrink: 0; }
</style>

<!-- ═════════════════ HERO ═════════════════ -->
<div class="ptl-hero" style="position:relative;z-index:1">
  <div style="position:relative;z-index:2">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px">
      <div>
        <div class="ptl-role-badge"><i class="fa-solid fa-users-gear"></i> Purchase Team Leader</div>
        <h4 class="ptl-hero-title">Procurement Dashboard</h4>
        <p class="ptl-hero-sub">Real-time procurement overview · <?= date('d M Y, h:i A') ?></p>
      </div>
      <div style="display:flex;gap:12px;flex-wrap:wrap">
        <?php if (!empty($prsForApproval)): ?>
        <div style="background:rgba(239,68,68,.18);border:1px solid rgba(239,68,68,.35);border-radius:12px;padding:10px 18px;display:flex;align-items:center;gap:10px">
          <i class="fal fa-bell" style="color:#fca5a5;font-size:20px"></i>
          <div>
            <div style="font-size:11px;font-weight:700;color:#fca5a5;letter-spacing:1px">PENDING PR APPROVAL</div>
            <div style="font-size:22px;font-weight:900;color:#fff;line-height:1"><?= count($prsForApproval) ?> PR<?= count($prsForApproval) > 1 ? 's' : '' ?></div>
          </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($posForApproval)): ?>
        <div style="background:rgba(245,158,11,.18);border:1px solid rgba(245,158,11,.35);border-radius:12px;padding:10px 18px;display:flex;align-items:center;gap:10px">
          <i class="fal fa-bell" style="color:#fcd34d;font-size:20px"></i>
          <div>
            <div style="font-size:11px;font-weight:700;color:#fcd34d;letter-spacing:1px">PENDING PO APPROVAL</div>
            <div style="font-size:22px;font-weight:900;color:#fff;line-height:1"><?= count($posForApproval) ?> PO<?= count($posForApproval) > 1 ? 's' : '' ?></div>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <div class="ptl-hero-actions">
      <a href="<?= $base ?>/con-purchase-orders" class="ptl-btn-white"><i class="fal fa-cart-shopping"></i> Purchase Orders</a>
      <a href="<?= $base ?>/con-vendors" class="ptl-btn-white"><i class="fal fa-user-tie"></i> Vendors</a>
      <a href="<?= $base ?>/con-material-issues" class="ptl-btn-white"><i class="fal fa-box-open"></i> Material Issues</a>
      <a href="<?= $base ?>/con-vendor-penalties" class="ptl-btn-white"><i class="fal fa-gavel"></i> Penalties</a>
      <a href="<?= $base ?>/con-purchase-requests" class="ptl-btn-white"><i class="fal fa-file-invoice"></i> All PRs</a>
    </div>
  </div>
</div>

<?php if (!empty($prsForApproval)): ?>
<!-- ═════════════════ PRs AWAITING APPROVAL ═════════════════ -->
<div class="ptl-panel" style="border:2px solid rgba(239,68,68,.4);background:rgba(239,68,68,.03);margin-bottom:22px">
  <div class="ptl-panel-title" style="color:#dc2626">
    <div class="ptl-dot" style="background:var(--ptl-red);animation:pulse 1.5s infinite"></div>
    <i class="fal fa-file-signature me-2" style="color:var(--ptl-red)"></i>
    Purchase Requests — Awaiting Your Approval
    <span style="margin-left:auto;font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;background:rgba(239,68,68,.15);color:#dc2626">
      <?= count($prsForApproval) ?> Pending
    </span>
  </div>
  <p style="font-size:11.5px;color:var(--text-muted);margin-bottom:14px">
    নিচের Purchase Request গুলো আপনার Approval এর অপেক্ষায়। <strong>Submitted</strong> গুলো সরাসরি Approve বা Reject করুন।
  </p>
  <div style="overflow-x:auto">
  <table class="ptl-table">
    <thead>
      <tr>
        <th>#PR</th>
        <th>Project</th>
        <th>Status</th>
        <th>Items</th>
        <th>Created By</th>
        <th>Date</th>
        <th style="text-align:right">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($prsForApproval as $apr):
        $aprSt = $apr['status'] ?? 'Draft';
        $aprSc = match($aprSt) {
          'Submitted' => ['bg'=>'rgba(59,130,246,.12)','text'=>'#3b82f6'],
          default     => ['bg'=>'rgba(245,158,11,.12)','text'=>'#f59e0b'],
        };
      ?>
      <tr style="<?= $aprSt === 'Submitted' ? 'background:rgba(59,130,246,.03)' : '' ?>">
        <td style="font-weight:700;color:var(--ptl-indigo)">PR-<?= $apr['id'] ?></td>
        <td><strong><?= htmlspecialchars($apr['project_name'] ?? 'N/A') ?></strong></td>
        <td>
          <span style="background:<?= $aprSc['bg'] ?>;color:<?= $aprSc['text'] ?>;font-size:10px;font-weight:700;padding:3px 9px;border-radius:20px">
            <?= $aprSt ?>
          </span>
        </td>
        <td>
          <span style="font-size:11px;background:rgba(var(--clr-theme-secondary-rgb),.1);color:var(--clr-theme-secondary);padding:2px 8px;border-radius:10px">
            <?= (int)$apr['item_count'] ?> items
          </span>
        </td>
        <td style="font-size:11px;color:var(--text-muted)">
          <?= htmlspecialchars($apr['created_by_name'] ?? '—') ?>
          <?php if (!empty($apr['created_by_role'])): ?>
            <span style="font-size:9px;color:var(--text-muted);display:block"><?= htmlspecialchars($apr['created_by_role']) ?></span>
          <?php endif; ?>
        </td>
        <td style="font-size:11px;color:var(--text-muted)"><?= !empty($apr['created_at']) ? date('d M Y', strtotime($apr['created_at'])) : '—' ?></td>
        <td style="text-align:right;white-space:nowrap">
          <a href="<?= $base ?>/con-purchase-request/<?= $apr['id'] ?>"
             style="display:inline-flex;align-items:center;gap:4px;background:linear-gradient(135deg,var(--clr-theme-primary),var(--clr-theme-primary));color:#fff;
                    border:none;border-radius:8px;padding:5px 12px;font-size:11.5px;font-weight:700;text-decoration:none">
            <i class="fal fa-eye"></i> View
          </a>
          <?php if ($aprSt === 'Draft'): ?>
          <span style="font-size:10px;color:var(--ptl-amber);margin-left:6px;font-weight:600">
            <i class="fal fa-clock me-1"></i>Draft
          </span>
          <?php elseif ($aprSt === 'Submitted'): ?>
          <span style="font-size:10px;color:#3b82f6;margin-left:6px;font-weight:600">
            <i class="fal fa-hourglass-half me-1"></i>Awaiting Dept. Head
          </span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($posForApproval)): ?>
<!-- ═════════════════ POs AWAITING APPROVAL ═════════════════ -->
<div class="ptl-panel" style="border:2px solid rgba(245,158,11,.4);background:rgba(245,158,11,.03);margin-bottom:22px">
  <div class="ptl-panel-title" style="color:#d97706">
    <div class="ptl-dot" style="background:var(--ptl-amber);animation:pulse 1.5s infinite"></div>
    <i class="fal fa-file-signature me-2" style="color:var(--ptl-amber)"></i>
    Purchase Orders — Awaiting Your Approval
    <span style="margin-left:auto;font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;background:rgba(245,158,11,.15);color:#d97706">
      <?= count($posForApproval) ?> Pending
    </span>
  </div>
  <p style="font-size:11.5px;color:var(--text-muted);margin-bottom:14px">
    নিচের Purchase Order গুলো আপনার Approval এর অপেক্ষায়। <strong>Pending for Approval</strong> এ ক্লিক করে PO টি ভিউ এবং অ্যাপ্রুভ করুন।
  </p>
  <div style="overflow-x:auto">
  <table class="ptl-table">
    <thead>
      <tr>
        <th>#PO</th>
        <th>Project</th>
        <th>Amount</th>
        <th>Created By</th>
        <th>Date</th>
        <th style="text-align:right">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($posForApproval as $apo): ?>
      <tr>
        <td style="font-weight:700;color:var(--ptl-indigo)">PO-<?= $apo['id'] ?></td>
        <td><strong><?= htmlspecialchars($apo['project_name'] ?? 'N/A') ?></strong></td>
        <td><strong style="color:var(--ptl-green)"><?= ptlFmt($apo['total_amount'] ?? 0) ?></strong></td>
        <td style="font-size:11px;color:var(--text-muted)">
          <?= htmlspecialchars($apo['created_by_name'] ?? '—') ?>
          <?php if (!empty($apo['created_by_role'])): ?>
            <span style="font-size:9px;color:var(--text-muted);display:block"><?= htmlspecialchars($apo['created_by_role']) ?></span>
          <?php endif; ?>
        </td>
        <td style="font-size:11px;color:var(--text-muted)"><?= !empty($apo['order_date']) ? date('d M Y', strtotime($apo['order_date'])) : '—' ?></td>
        <td style="text-align:right;white-space:nowrap">
          <a href="<?= $base ?>/con-purchase-order/<?= $apo['id'] ?>"
             style="display:inline-flex;align-items:center;gap:4px;background:linear-gradient(135deg,var(--clr-theme-primary),var(--clr-theme-primary));color:#fff;
                    border:none;border-radius:8px;padding:5px 12px;font-size:11.5px;font-weight:700;text-decoration:none">
            <i class="fal fa-eye"></i> View & Approve
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>

<!-- ═════════════════ KPI GRID ═════════════════ -->
<div class="kpi-grid-3c">
  <a href="<?= $base ?>/con-purchase-orders" class="kpi-card">
    <div class="kpi-accent" style="background:var(--ptl-green)"></div>
    <div class="kpi-icon" style="background:rgba(16,185,129,.12);color:var(--ptl-green)"><i class="fal fa-cart-shopping"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Purchase Orders</div>
    <div class="kpi-value" style="color:var(--ptl-green)"><?= $totalPOs ?></div>
    <div class="kpi-sub">Total POs generated</div>
  </a>

  <a href="<?= $base ?>/con-vendors" class="kpi-card">
    <div class="kpi-accent" style="background:var(--ptl-blue)"></div>
    <div class="kpi-icon" style="background:rgba(59,130,246,.12);color:var(--ptl-blue)"><i class="fal fa-user-tie"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Vendors</div>
    <div class="kpi-value" style="color:var(--ptl-blue)"><?= $totalVendors ?></div>
    <div class="kpi-sub">Registered vendors</div>
  </a>

  <a href="<?= $base ?>/con-vendor-penalties" class="kpi-card">
    <div class="kpi-accent" style="background:var(--ptl-red)"></div>
    <div class="kpi-icon" style="background:rgba(239,68,68,.12);color:var(--ptl-red)"><i class="fal fa-gavel"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Vendor Penalties</div>
    <div class="kpi-value" style="color:var(--ptl-red)"><?= $totalPenalties ?></div>
    <div class="kpi-sub">Penalties issued</div>
    <?php if ($totalPenalties > 0): ?>
    <span class="kpi-badge" style="background:rgba(239,68,68,.12);color:var(--ptl-red)">ACTIVE</span>
    <?php endif; ?>
  </a>

  <a href="<?= $base ?>/con-material-issues" class="kpi-card">
    <div class="kpi-accent" style="background:var(--ptl-amber)"></div>
    <div class="kpi-icon" style="background:rgba(245,158,11,.12);color:var(--ptl-amber)"><i class="fal fa-box-open"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Material Issues</div>
    <div class="kpi-value" style="color:var(--ptl-amber)"><?= $totalIssues ?></div>
    <div class="kpi-sub">Total stock-out records</div>
  </a>

  <a href="<?= $base ?>/con-materials" class="kpi-card">
    <div class="kpi-accent" style="background:var(--ptl-purple)"></div>
    <div class="kpi-icon" style="background:rgba(var(--clr-theme-secondary-rgb),.12);color:var(--ptl-purple)"><i class="fal fa-boxes-stacked"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Materials</div>
    <div class="kpi-value" style="color:var(--ptl-purple)"><?= $totalMaterials ?></div>
    <div class="kpi-sub">Total material types</div>
  </a>

  <a href="<?= $base ?>/con-vendor-payment-requests" class="kpi-card">
    <div class="kpi-accent" style="background:var(--ptl-orange)"></div>
    <div class="kpi-icon" style="background:rgba(249,115,22,.12);color:var(--ptl-orange)"><i class="fal fa-clock"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Pending Payments</div>
    <div class="kpi-value" style="color:var(--ptl-orange)"><?= $pendingPayments ?></div>
    <div class="kpi-sub">Awaiting processing</div>
    <?php if ($pendingPayments > 0): ?>
    <span class="kpi-badge" style="background:rgba(249,115,22,.12);color:var(--ptl-orange)">PENDING</span>
    <?php endif; ?>
  </a>
</div>

<!-- ═════════════════ ROW 2 ═════════════════ -->
<div class="row">

  <!-- Left Column -->
  <div class="col-lg-8">

    <!-- Recent POs -->
    <div class="ptl-panel">
      <div class="ptl-panel-title">
        <div class="ptl-dot" style="background:var(--ptl-green)"></div>
        Recent Purchase Orders
        <a href="<?= $base ?>/con-purchase-orders" style="font-size:10px;color:var(--ptl-blue);margin-left:auto;text-decoration:none">View All <i class="fal fa-arrow-right ms-1"></i></a>
      </div>
      <?php if (empty($recentPOs)): ?>
        <div class="empty-msg">No purchase orders found</div>
      <?php else: ?>
      <table class="ptl-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Vendor</th>
            <th>Project</th>
            <th>Amount</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentPOs as $po):
            $status = $po['status'] ?? 'Pending';
            $sc = match($status) {
              'Approved','Completed' => ['bg'=>'rgba(16,185,129,.12)','text'=>'#10b981'],
              'Rejected'             => ['bg'=>'rgba(239,68,68,.12)','text'=>'#ef4444'],
              default                => ['bg'=>'rgba(245,158,11,.12)','text'=>'#f59e0b'],
            };
          ?>
          <tr>
            <td style="font-size:11px;color:var(--text-muted)">#<?= $po['id'] ?></td>
            <td><strong><?= htmlspecialchars($po['vendor_name'] ?? 'N/A') ?></strong></td>
            <td style="font-size:11.5px;color:var(--text-muted)"><?= htmlspecialchars($po['project_name'] ?? 'N/A') ?></td>
            <td><strong style="color:var(--ptl-green)"><?= ptlFmt($po['total_amount'] ?? 0) ?></strong></td>
            <td><span class="kpi-badge" style="position:static;background:<?= $sc['bg'] ?>;color:<?= $sc['text'] ?>;font-size:10px;padding:3px 8px;border-radius:20px"><?= $status ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

    <!-- Stock Overview -->
    <div class="ptl-panel">
      <div class="ptl-panel-title">
        <div class="ptl-dot" style="background:var(--ptl-purple)"></div>
        Material Stock Levels
        <a href="<?= $base ?>/con-stocks" style="font-size:10px;color:var(--ptl-blue);margin-left:auto;text-decoration:none">View All <i class="fal fa-arrow-right ms-1"></i></a>
      </div>
      <?php if (empty($stockOverview)): ?>
        <div class="empty-msg">No stock data found</div>
      <?php else: ?>
        <?php foreach ($stockOverview as $sk):
          $min  = (float)($sk['min_stock_level'] ?? 1);
          $qty  = (float)($sk['total_qty'] ?? 0);
          $pct  = $min > 0 ? min(100, round($qty / $min * 100)) : 100;
          $clr  = $pct <= 30 ? 'linear-gradient(90deg,#dc2626,#ef4444)' : ($pct <= 70 ? 'linear-gradient(90deg,#d97706,#f59e0b)' : 'linear-gradient(90deg,#059669,#10b981)');
          $tclr = $pct <= 30 ? 'var(--ptl-red)' : ($pct <= 70 ? 'var(--ptl-amber)' : 'var(--ptl-green)');
        ?>
        <div class="stock-bar-row">
          <div class="label">
            <span><?= htmlspecialchars($sk['material_name']) ?> <span style="font-size:10px;color:var(--text-muted);font-weight:400">(<?= $sk['unit'] ?>)</span></span>
            <span style="color:<?= $tclr ?>"><?= $qty ?> / Min:<?= $min ?></span>
          </div>
          <div class="stock-track"><div class="stock-fill" style="width:<?= $pct ?>%;background:<?= $clr ?>"></div></div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>

  <!-- Right Column -->
  <div class="col-lg-4">

    <!-- Quick Links -->
    <div class="ptl-panel">
      <div class="ptl-panel-title">
        <div class="ptl-dot" style="background:var(--ptl-teal)"></div>
        Quick Actions
      </div>
      <div class="quick-links2">
        <a href="<?= $base ?>/con-purchase-request-create" class="ql2-btn" style="color:var(--ptl-green)">
          <div class="ql2-icon" style="background:rgba(16,185,129,.12);color:var(--ptl-green)"><i class="fal fa-plus"></i></div>
          <span>New Purchase Request</span>
        </a>
        <a href="<?= $base ?>/con-vendor-create" class="ql2-btn" style="color:var(--ptl-blue)">
          <div class="ql2-icon" style="background:rgba(59,130,246,.12);color:var(--ptl-blue)"><i class="fal fa-user-plus"></i></div>
          <span>Add New Vendor</span>
        </a>
        <a href="<?= $base ?>/con-material-issue-create" class="ql2-btn" style="color:var(--ptl-amber)">
          <div class="ql2-icon" style="background:rgba(245,158,11,.12);color:var(--ptl-amber)"><i class="fal fa-box-open"></i></div>
          <span>New Material Issue</span>
        </a>
        <a href="<?= $base ?>/con-vendor-payment-create" class="ql2-btn" style="color:var(--ptl-orange)">
          <div class="ql2-icon" style="background:rgba(249,115,22,.12);color:var(--ptl-orange)"><i class="fal fa-hand-holding-usd"></i></div>
          <span>Vendor Payment</span>
        </a>
        <a href="<?= $base ?>/con-vendor-penalty-create" class="ql2-btn" style="color:var(--ptl-red)">
          <div class="ql2-icon" style="background:rgba(239,68,68,.12);color:var(--ptl-red)"><i class="fal fa-gavel"></i></div>
          <span>Issue Penalty</span>
        </a>
        <a href="<?= $base ?>/con-material-create" class="ql2-btn" style="color:var(--ptl-purple)">
          <div class="ql2-icon" style="background:rgba(var(--clr-theme-secondary-rgb),.12);color:var(--ptl-purple)"><i class="fal fa-boxes-stacked"></i></div>
          <span>Add Material</span>
        </a>
      </div>
    </div>

    <!-- Recent Vendors -->
    <div class="ptl-panel">
      <div class="ptl-panel-title">
        <div class="ptl-dot" style="background:var(--ptl-blue)"></div>
        Recent Vendors
        <a href="<?= $base ?>/con-vendors" style="font-size:10px;color:var(--ptl-blue);margin-left:auto;text-decoration:none">View All <i class="fal fa-arrow-right ms-1"></i></a>
      </div>
      <?php if (empty($recentVendors)): ?>
        <div class="empty-msg">No vendors registered</div>
      <?php else: ?>
        <?php foreach ($recentVendors as $v): ?>
        <div style="padding:10px 0;border-bottom:1px solid rgba(0,0,0,.06);display:flex;justify-content:space-between;align-items:center">
          <div>
            <div style="font-size:12.5px;font-weight:700"><?= htmlspecialchars($v['name'] ?? 'N/A') ?></div>
            <div style="font-size:10.5px;color:var(--text-muted)"><?= htmlspecialchars($v['contact_person'] ?? '') ?></div>
          </div>
          <a href="<?= $base ?>/con-vendor/<?= $v['id'] ?>" style="font-size:11px;color:var(--ptl-blue);text-decoration:none">View <i class="fal fa-arrow-right ms-1"></i></a>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php if (!empty($pendingBOQsForPR)): ?>
<!-- ═════════════════ BOQ → PR ALERT PANEL ═════════════════ -->
<div class="ptl-panel" style="border:2px solid rgba(245,158,11,.35);background:rgba(245,158,11,.04);margin-bottom:22px">
  <div class="ptl-panel-title" style="color:#b45309">
    <div class="ptl-dot" style="background:#f59e0b"></div>
    <i class="fal fa-exclamation-triangle me-2" style="color:#f59e0b"></i>
    BOQ Approved — Purchase Request Required
    <span style="margin-left:auto;font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;background:rgba(245,158,11,.15);color:#b45309">
      <?= count($pendingBOQsForPR) ?> Pending
    </span>
  </div>
  <p style="font-size:11.5px;color:var(--text-muted);margin-bottom:14px">
    The following BOQs have been approved by the Project Manager. Please create a <strong>Purchase Request</strong> for each.
  </p>
  <table class="ptl-table">
    <thead>
      <tr>
        <th>BOQ #</th>
        <th>Project</th>
        <th>Items</th>
        <th>Est. Value</th>
        <th>Approved On</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($pendingBOQsForPR as $bq): ?>
      <tr>
        <td style="font-size:11px;color:var(--text-muted)">BOQ #<?= $bq['boq_id'] ?> <span style="font-size:10px">(v<?= htmlspecialchars($bq['version']) ?>)</span></td>
        <td><strong><?= htmlspecialchars($bq['project_name'] ?? 'N/A') ?></strong></td>
        <td><span style="font-size:11px;background:rgba(59,130,246,.1);color:#2563eb;padding:2px 8px;border-radius:10px"><?= (int)$bq['item_count'] ?> items</span></td>
        <td><strong style="color:var(--ptl-green)"><?= ptlFmt($bq['grand_total']) ?></strong></td>
        <td style="font-size:11px;color:var(--text-muted)"><?= !empty($bq['approved_at']) ? date('d M Y', strtotime($bq['approved_at'])) : '—' ?></td>
        <td>
          <a href="<?= $base ?>/con-purchase-request-create?boq_id=<?= $bq['boq_id'] ?>&project_id=<?= $bq['project_id'] ?>"
             style="display:inline-flex;align-items:center;gap:5px;background:linear-gradient(135deg,#d97706,#f59e0b);color:#fff;
                    border:none;border-radius:8px;padding:5px 14px;font-size:11.5px;font-weight:700;text-decoration:none">
            <i class="fal fa-plus-circle"></i> Create PR
          </a>
          <a href="<?= $base ?>/con-boq-show/<?= $bq['boq_id'] ?>" style="font-size:11px;color:var(--ptl-blue);margin-left:8px;text-decoration:none">View BOQ</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php if (!empty($pendingPRsForPO)): ?>
<!-- ═════════════════ PR → PO PANEL ═════════════════ -->
<div class="ptl-panel" style="border:2px solid rgba(59,130,246,.3);background:rgba(59,130,246,.03);margin-bottom:22px">
  <div class="ptl-panel-title" style="color:#1d4ed8">
    <div class="ptl-dot" style="background:var(--ptl-blue)"></div>
    <i class="fal fa-file-invoice me-2" style="color:var(--ptl-blue)"></i>
    Purchase Requests — Awaiting Purchase Order
    <span style="margin-left:auto;font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;background:rgba(59,130,246,.12);color:#1d4ed8">
      <?= count($pendingPRsForPO) ?> Pending
    </span>
  </div>
  <p style="font-size:11.5px;color:var(--text-muted);margin-bottom:14px">
    These Purchase Requests are approved but no Purchase Order has been generated yet. Open the PR to generate a PO via CST comparison.
  </p>
  <table class="ptl-table">
    <thead>
      <tr>
        <th>#PR</th>
        <th>Project</th>
        <th>Created By</th>
        <th>Items</th>
        <th>Status</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($pendingPRsForPO as $pr):
        $prSt = $pr['status'] ?? 'Draft';
        $prSc = match($prSt) {
          'Approved'  => ['bg'=>'rgba(16,185,129,.12)','text'=>'#10b981'],
          'Submitted' => ['bg'=>'rgba(59,130,246,.12)', 'text'=>'#3b82f6'],
          default     => ['bg'=>'rgba(245,158,11,.12)', 'text'=>'#f59e0b'],
        };
      ?>
      <tr>
        <td style="font-size:11px;color:var(--text-muted)">#<?= $pr['id'] ?></td>
        <td><strong><?= htmlspecialchars($pr['project_name'] ?? 'N/A') ?></strong></td>
        <td style="font-size:11.5px;color:var(--text-muted)"><?= htmlspecialchars($pr['created_by_name'] ?? '—') ?></td>
        <td><span style="font-size:11px;background:rgba(var(--clr-theme-secondary-rgb),.1);color:var(--clr-theme-secondary);padding:2px 8px;border-radius:10px"><?= (int)$pr['item_count'] ?> items</span></td>
        <td><span style="background:<?= $prSc['bg'] ?>;color:<?= $prSc['text'] ?>;font-size:10px;font-weight:700;padding:3px 9px;border-radius:20px"><?= $prSt ?></span></td>
        <td>
          <a href="<?= $base ?>/con-purchase-request/<?= $pr['id'] ?>"
             style="display:inline-flex;align-items:center;gap:5px;background:linear-gradient(135deg,#1d4ed8,#3b82f6);color:#fff;
                    border:none;border-radius:8px;padding:5px 14px;font-size:11.5px;font-weight:700;text-decoration:none">
            <i class="fal fa-eye"></i> Open PR
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php if (!empty($allBOQs)): ?>
<!-- ═════════════════ ALL BOQs OVERVIEW PANEL ═════════════════ -->
<div class="ptl-panel" style="border:2px solid rgba(var(--clr-theme-primary-rgb),.25);background:rgba(var(--clr-theme-primary-rgb),.03);margin-bottom:22px">
  <div class="ptl-panel-title" style="color:var(--clr-theme-primary)">
    <div class="ptl-dot" style="background:var(--ptl-indigo)"></div>
    <i class="fal fa-file-lines me-2" style="color:var(--ptl-indigo)"></i>
    সকল BOQ Overview
    <span style="margin-left:auto;font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;background:rgba(var(--clr-theme-primary-rgb),.12);color:var(--clr-theme-primary)">
      <?= count($allBOQs) ?> BOQ<?= count($allBOQs) > 1 ? 's' : '' ?>
    </span>
  </div>
  <p style="font-size:11.5px;color:var(--text-muted);margin-bottom:14px">
    সব প্রজেক্টের BOQ এর সর্বশেষ status, item count, এবং estimated value এক জায়গায়।
  </p>
  <div style="overflow-x:auto">
  <table class="ptl-table">
    <thead>
      <tr>
        <th>BOQ #</th>
        <th>Project</th>
        <th>Status</th>
        <th>Items</th>
        <th>Est. Value</th>
        <th>Created By</th>
        <th>Created At</th>
        <th>Approved By</th>
        <th style="text-align:right">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($allBOQs as $bq):
        $bst = $bq['status'] ?? 'Draft';
        $bsc = match($bst) {
          'Approved'  => ['bg'=>'rgba(16,185,129,.12)', 'text'=>'#10b981'],
          'Submitted' => ['bg'=>'rgba(59,130,246,.12)', 'text'=>'#3b82f6'],
          'Rejected'  => ['bg'=>'rgba(239,68,68,.12)',  'text'=>'#ef4444'],
          default     => ['bg'=>'rgba(245,158,11,.12)', 'text'=>'#f59e0b'],
        };
      ?>
      <tr>
        <td style="font-size:11px;color:var(--text-muted)">
          BOQ #<?= $bq['boq_id'] ?>
          <span style="font-size:10px">(v<?= htmlspecialchars($bq['version']) ?>)</span>
        </td>
        <td><strong><?= htmlspecialchars($bq['project_name'] ?? 'N/A') ?></strong></td>
        <td>
          <span style="background:<?= $bsc['bg'] ?>;color:<?= $bsc['text'] ?>;font-size:10px;font-weight:700;padding:3px 9px;border-radius:20px">
            <?= $bst ?>
          </span>
        </td>
        <td>
          <span style="font-size:11px;background:rgba(59,130,246,.1);color:#2563eb;padding:2px 8px;border-radius:10px">
            <?= (int)$bq['item_count'] ?> items
          </span>
        </td>
        <td><strong style="color:var(--ptl-green)"><?= ptlFmt($bq['grand_total']) ?></strong></td>
        <td style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($bq['creator_name'] ?? '—') ?></td>
        <td style="font-size:11px;color:var(--text-muted)">
          <?= !empty($bq['created_at']) ? date('d M Y', strtotime($bq['created_at'])) : '—' ?>
        </td>
        <td style="font-size:11px;color:var(--text-muted)">
          <?php if (!empty($bq['approver_name'])): ?>
            <span style="color:#10b981"><i class="fal fa-check-circle me-1"></i><?= htmlspecialchars($bq['approver_name']) ?></span>
          <?php else: ?>
            <span style="color:var(--text-muted)">—</span>
          <?php endif; ?>
        </td>
        <td style="text-align:right">
          <a href="<?= $base ?>/con-boq-show/<?= $bq['boq_id'] ?>"
             style="display:inline-flex;align-items:center;gap:5px;background:linear-gradient(135deg,var(--clr-theme-primary),var(--clr-theme-primary));color:#fff;
                    border:none;border-radius:8px;padding:5px 14px;font-size:11.5px;font-weight:700;text-decoration:none">
            <i class="fal fa-eye"></i> View
          </a>
          <?php if ($bst === 'Approved'): ?>
          <a href="<?= $base ?>/con-purchase-request-create?boq_id=<?= $bq['boq_id'] ?>&project_id=<?= $bq['project_id'] ?>"
             style="display:inline-flex;align-items:center;gap:5px;background:linear-gradient(135deg,#d97706,#f59e0b);color:#fff;
                    border:none;border-radius:8px;padding:5px 12px;font-size:11.5px;font-weight:700;text-decoration:none;margin-left:6px">
            <i class="fal fa-plus-circle"></i> PR
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

<div style="height:30px"></div>

</div></div>

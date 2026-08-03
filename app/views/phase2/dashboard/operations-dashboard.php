<?php
$addPage = true;
$show_breadcrumb = false;
$base = $GLOBALS['base_url'];

function opFmt($n) {
    $n = (float)$n;
    if ($n >= 10000000) return '৳' . number_format($n/10000000, 2) . ' Cr';
    if ($n >= 100000)   return '৳' . number_format($n/100000,   2) . ' L';
    if ($n >= 1000)     return '৳' . number_format($n/1000,     1) . 'K';
    return '৳' . number_format($n, 0);
}

$isAdmin = $isAdmin ?? false;
$myProjectsCount = $myProjectsCount ?? 0;
$pendingWorkingPlans = $pendingWorkingPlans ?? 0;
$totalMaterialIssues = $totalMaterialIssues ?? 0;
$pendingVendorPayments = $pendingVendorPayments ?? 0;

$myProjects = $myProjects ?? [];
$recentWorkingPlans = $recentWorkingPlans ?? [];
$recentMaterialIssues = $recentMaterialIssues ?? [];
$recentVendorPayments = $recentVendorPayments ?? [];
$prsForApproval = $prsForApproval ?? [];
$posForApproval = $posForApproval ?? [];
$pendingWorkOrders = $pendingWorkOrders ?? [];
$prsCount = $prsCount ?? 0;
$posCount = $posCount ?? 0;
$wosCount = $wosCount ?? 0;
$roleId = $roleId ?? 0;
?>
<div class="row"><div class="col-12">
<style>
:root {
  --op-primary: #198754;
  --op-dark:    #0f5132;
  --op-blue:    #3b82f6;
  --op-green:   #10b981;
  --op-amber:   #f59e0b;
  --op-purple:  var(--clr-theme-secondary);
}
.op-hero {
  background: linear-gradient(135deg, #0f5132 0%, #198754 55%, #20a86a 100%);
  border-radius: 18px;
  padding: 28px 32px;
  margin-bottom: 24px;
  position: relative;
  overflow: hidden;
}
.op-hero::before {
  content: '';
  position: absolute;
  width: 300px; height: 300px;
  border-radius: 50%;
  background: rgba(255,255,255,.04);
  top: -100px; right: -80px;
}
.op-hero::after {
  content: '';
  position: absolute;
  width: 200px; height: 200px;
  border-radius: 50%;
  background: rgba(255,255,255,.03);
  bottom: -60px; left: -40px;
}
.op-role-badge {
  display: inline-flex; align-items: center; gap: 8px;
  background: rgba(255,255,255,.12);
  border: 1px solid rgba(255,255,255,.2);
  padding: 5px 14px; border-radius: 20px;
  font-size: 11px; font-weight: 700; letter-spacing: 1.5px;
  text-transform: uppercase; color: #fff; margin-bottom: 14px;
}
.op-hero-title { font-size: 28px; font-weight: 900; color: #fff; margin: 0 0 6px; }
.op-hero-sub   { font-size: 13px; color: rgba(255,255,255,.7); margin: 0; }

.kpi-grid-4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 22px; }
.kpi-card {
  border-radius: 14px; padding: 18px 20px;
  position: relative; overflow: hidden;
  border: 1px solid rgba(0,0,0,.08);
  text-decoration: none !important; display: block;
  transition: transform .18s, box-shadow .18s;
  background: var(--clr-bg-card, #fff);
}
.kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 28px rgba(0,0,0,.12); }
.kpi-accent { position: absolute; left: 0; top: 0; width: 4px; height: 100%; border-radius: 14px 0 0 14px; }
.kpi-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-bottom: 12px; }
.kpi-value { font-size: 28px; font-weight: 900; line-height: 1; margin-bottom: 4px; }
.kpi-label { font-size: 10.5px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 3px; }

.rd-panel { border-radius: 14px; padding: 22px; border: 1px solid rgba(0,0,0,.08); background: var(--clr-bg-card, #fff); margin-bottom: 22px; }
.rd-panel-title { font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.rd-dot { width: 8px; height: 8px; border-radius: 50%; }
.rd-table { width: 100%; border-collapse: collapse; }
.rd-table th { font-size: 10px; letter-spacing: 1.4px; text-transform: uppercase; font-weight: 600; padding: 0 10px 10px; border-bottom: 2px solid rgba(0,0,0,.07); text-align: left; }
.rd-table td { padding: 11px 10px; border-bottom: 1px solid rgba(0,0,0,.05); font-size: 12.5px; vertical-align: middle; }
.empty-msg { text-align: center; padding: 28px 0; color: var(--text-muted); font-size: 12px; }
</style>

<!-- HERO HEADER -->
<div class="op-hero" style="position:relative;z-index:1">
  <div style="position:relative;z-index:2;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px">
    <div>
      <div class="op-role-badge">
        <i class="fa-solid fa-hard-hat"></i>
        Site Operations
      </div>
      <h4 class="op-hero-title">Operations Dashboard</h4>
      <p class="op-hero-sub">Site execution and management overview · <?= date('d M Y, h:i A') ?></p>
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

      <?php if (!empty($pendingWorkOrders)): ?>
      <div style="background:rgba(59,130,246,.18);border:1px solid rgba(59,130,246,.35);border-radius:12px;padding:10px 18px;display:flex;align-items:center;gap:10px">
        <i class="fal fa-bell" style="color:#93c5fd;font-size:20px"></i>
        <div>
          <div style="font-size:11px;font-weight:700;color:#93c5fd;letter-spacing:1px">PENDING WO APPROVAL</div>
          <div style="font-size:22px;font-weight:900;color:#fff;line-height:1"><?= count($pendingWorkOrders) ?> WO<?= count($pendingWorkOrders) > 1 ? 's' : '' ?></div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- KPI CARDS -->
<div class="kpi-grid-4">
  <a href="<?= $base ?>/con-projects" class="kpi-card">
    <div class="kpi-accent" style="background:var(--op-blue)"></div>
    <div class="kpi-icon" style="background:rgba(59,130,246,.12);color:var(--op-blue)"><i class="fal fa-building"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">My Projects</div>
    <div class="kpi-value" style="color:var(--op-blue)"><?= $myProjectsCount ?></div>
  </a>

  <a href="<?= $base ?>/con-working-plans" class="kpi-card">
    <div class="kpi-accent" style="background:var(--op-amber)"></div>
    <div class="kpi-icon" style="background:rgba(245,158,11,.12);color:var(--op-amber)"><i class="fal fa-calendar-alt"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Pending Work Plans</div>
    <div class="kpi-value" style="color:var(--op-amber)"><?= $pendingWorkingPlans ?></div>
  </a>

  <a href="<?= $base ?>/con-material-issues" class="kpi-card">
    <div class="kpi-accent" style="background:var(--op-green)"></div>
    <div class="kpi-icon" style="background:rgba(16,185,129,.12);color:var(--op-green)"><i class="fal fa-box-open"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Material Issues</div>
    <div class="kpi-value" style="color:var(--op-green)"><?= $totalMaterialIssues ?></div>
  </a>

  <a href="<?= $base ?>/con-vendor-payments" class="kpi-card">
    <div class="kpi-accent" style="background:var(--op-purple)"></div>
    <div class="kpi-icon" style="background:rgba(var(--clr-theme-secondary-rgb),.12);color:var(--op-purple)"><i class="fal fa-file-invoice-dollar"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Pending Vendor Payments</div>
    <div class="kpi-value" style="color:var(--op-purple)"><?= $pendingVendorPayments ?></div>
  </a>

  <a href="<?= $base ?>/fund-requisitions" class="kpi-card">
    <div class="kpi-accent" style="background:var(--op-blue)"></div>
    <div class="kpi-icon" style="background:rgba(59,130,246,.12);color:var(--op-blue)"><i class="fal fa-hand-holding-usd"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Pending Fund Requisitions</div>
    <div class="kpi-value" style="color:var(--op-blue)"><?= $pendingFundRequisitions ?? 0 ?></div>
  </a>

  <?php if ($roleId === 20 || $roleId === 3 || $isAdmin): ?>
  <a href="#pending-prs" class="kpi-card">
    <div class="kpi-accent" style="background:var(--op-amber)"></div>
    <div class="kpi-icon" style="background:rgba(245,158,11,.12);color:var(--op-amber)"><i class="fal fa-file-alt"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Pending PR Approvals</div>
    <div class="kpi-value" style="color:var(--op-amber)"><?= $prsCount ?></div>
  </a>

  <a href="#pending-pos" class="kpi-card">
    <div class="kpi-accent" style="background:var(--op-green)"></div>
    <div class="kpi-icon" style="background:rgba(16,185,129,.12);color:var(--op-green)"><i class="fal fa-shopping-cart"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Pending PO Approvals</div>
    <div class="kpi-value" style="color:var(--op-green)"><?= $posCount ?></div>
  </a>
  <?php endif; ?>

  <?php if ($roleId === 20 || $roleId === 12 || $isAdmin): ?>
  <a href="#pending-wos" class="kpi-card">
    <div class="kpi-accent" style="background:var(--op-blue)"></div>
    <div class="kpi-icon" style="background:rgba(59,130,246,.12);color:var(--op-blue)"><i class="fal fa-file-contract"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Pending WO Approvals</div>
    <div class="kpi-value" style="color:var(--op-blue)"><?= $wosCount ?></div>
  </a>
  <?php endif; ?>
</div>

<!-- PENDING PURCHASE REQUESTS FOR FINAL APPROVAL -->
<?php if (!empty($prsForApproval)): ?>
<div class="rd-panel" id="pending-prs">
  <div class="rd-panel-title">
    <div class="rd-dot" style="background:var(--op-amber)"></div>
    Pending Purchase Requests for Final Approval
    <span class="badge bg-warning text-dark ms-2" style="font-size: 11px;"><?= count($prsForApproval) ?></span>
  </div>
  <div class="table-responsive">
    <table class="rd-table">
      <thead>
        <tr>
          <th>#PR</th>
          <th>Project</th>
          <th>Items</th>
          <th>Awaiting Final Approval Since</th>
          <th class="text-end">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($prsForApproval as $apr): ?>
        <tr>
          <td class="fw-bold" style="color:var(--clr-theme-primary)">PR-<?= $apr['id'] ?></td>
          <td><strong><?= htmlspecialchars($apr['project_name'] ?? 'N/A') ?></strong></td>
          <td>
            <span class="badge bg-light text-dark border" style="font-size:10px"><?= (int)$apr['item_count'] ?> items</span>
          </td>
          <td class="text-muted" style="font-size:11.5px"><?= date('d M Y', strtotime($apr['created_at'])) ?></td>
          <td class="text-end">
            <a href="<?= $base ?>/con-purchase-request/<?= $apr['id'] ?>" class="btn btn-sm btn-primary py-1 px-3">
              <i class="bi bi-shield-check"></i> View &amp; Act
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- PENDING PURCHASE ORDERS FOR APPROVAL -->
<?php if (!empty($posForApproval)): ?>
<div class="rd-panel" id="pending-pos" style="border:2px solid rgba(245,158,11,.4);background:rgba(245,158,11,.03);margin-bottom:22px">
  <div class="rd-panel-title" style="color:#d97706">
    <div class="rd-dot" style="background:var(--op-amber);animation:pulse 1.5s infinite"></div>
    <i class="bi bi-file-earmark-check me-2" style="color:var(--op-amber)"></i>
    Pending Purchase Orders for Approval
    <span class="badge bg-warning text-dark ms-2" style="font-size: 11px;"><?= count($posForApproval) ?></span>
  </div>
  <div class="table-responsive">
    <table class="rd-table">
      <thead>
        <tr>
          <th>#PO</th>
          <th>Project</th>
          <th>Amount</th>
          <th>Created By</th>
          <th>Date</th>
          <th class="text-end">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($posForApproval as $apo): ?>
        <tr>
          <td class="fw-bold" style="color:var(--clr-theme-primary)">PO-<?= $apo['id'] ?></td>
          <td><strong><?= htmlspecialchars($apo['project_name'] ?? 'N/A') ?></strong></td>
          <td><strong style="color:var(--op-green)"><?= opFmt($apo['total_amount'] ?? 0) ?></strong></td>
          <td style="font-size:11px;color:var(--text-muted)">
            <?= htmlspecialchars($apo['created_by_name'] ?? '—') ?>
          </td>
          <td class="text-muted" style="font-size:11.5px"><?= !empty($apo['order_date']) ? date('d M Y', strtotime($apo['order_date'])) : '—' ?></td>
          <td class="text-end">
            <a href="<?= $base ?>/con-purchase-order/<?= $apo['id'] ?>" class="btn btn-sm btn-primary py-1 px-3">
              <i class="bi bi-shield-check"></i> View &amp; Act
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- PENDING WORK ORDERS FOR APPROVAL -->
<?php if (!empty($pendingWorkOrders)): ?>
<div class="rd-panel" id="pending-wos">
  <div class="rd-panel-title">
    <div class="rd-dot" style="background:var(--op-amber)"></div>
    Pending Work Orders for Approval
    <span class="badge bg-warning text-dark ms-2" style="font-size: 11px;"><?= count($pendingWorkOrders) ?></span>
  </div>
  <div class="table-responsive">
    <table class="rd-table">
      <thead>
        <tr>
          <th>Work Order Title</th>
          <th>Project</th>
          <th>Contractor</th>
          <th>Amount</th>
          <th>Created At</th>
          <th class="text-end">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pendingWorkOrders as $wo): ?>
        <tr>
          <td><strong><?= htmlspecialchars($wo['title']) ?></strong></td>
          <td><?= htmlspecialchars($wo['project_name'] ?? 'N/A') ?></td>
          <td><?= htmlspecialchars($wo['contractor_name'] ?? 'N/A') ?></td>
          <td class="fw-bold text-primary">৳<?= number_format($wo['total_amount'], 2) ?></td>
          <td class="text-muted" style="font-size:11.5px"><?= date('d M Y', strtotime($wo['created_at'])) ?></td>
          <td class="text-end">
            <a href="<?= $base ?>/con-work-order/<?= $wo['id'] ?>" class="btn btn-sm btn-primary py-1 px-3">
              <i class="bi bi-shield-check"></i> View &amp; Act
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<div class="row">
  <!-- Left Column -->
  <div class="col-lg-6">
    <div class="rd-panel">
      <div class="rd-panel-title">
        <div class="rd-dot" style="background:var(--op-blue)"></div>
        My Assigned Projects
        <a href="<?= $base ?>/con-projects" style="font-size:10px;color:var(--op-blue);margin-left:auto;text-decoration:none">View All <i class="fal fa-arrow-right ms-1"></i></a>
      </div>
      <?php if (empty($myProjects)): ?>
        <div class="empty-msg">No projects assigned</div>
      <?php else: ?>
        <table class="rd-table">
          <thead>
            <tr>
              <th>Project Name</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (array_slice($myProjects, 0, 5) as $pr): ?>
            <tr>
              <td>
                <a href="<?= $base ?>/con-project/<?= $pr['id'] ?>" style="text-decoration:none;font-weight:600">
                  <?= htmlspecialchars($pr['name']) ?>
                </a>
                <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($pr['location_name'] ?: 'N/A') ?></div>
              </td>
              <td><span class="badge bg-secondary"><?= $pr['project_status'] ?? 'N/A' ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div class="rd-panel">
      <div class="rd-panel-title">
        <div class="rd-dot" style="background:var(--op-green)"></div>
        Recent Material Issues
        <a href="<?= $base ?>/con-material-issues" style="font-size:10px;color:var(--op-green);margin-left:auto;text-decoration:none">View All <i class="fal fa-arrow-right ms-1"></i></a>
      </div>
      <?php if (empty($recentMaterialIssues)): ?>
        <div class="empty-msg">No material issues found</div>
      <?php else: ?>
      <table class="rd-table">
        <thead>
          <tr>
            <th>Material</th>
            <th>Project</th>
            <th>Qty</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentMaterialIssues as $iss): ?>
          <tr>
            <td><strong><?= htmlspecialchars($iss['material_name'] ?? 'N/A') ?></strong></td>
            <td style="font-size:11.5px;color:var(--text-muted)"><?= htmlspecialchars($iss['project_name'] ?? 'N/A') ?></td>
            <td><strong><?= $iss['quantity_issued'] ?? 0 ?></strong></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- Right Column -->
  <div class="col-lg-6">
    <div class="rd-panel">
      <div class="rd-panel-title">
        <div class="rd-dot" style="background:var(--op-amber)"></div>
        Recent Working Plans
        <a href="<?= $base ?>/con-working-plans" style="font-size:10px;color:var(--op-amber);margin-left:auto;text-decoration:none">View All <i class="fal fa-arrow-right ms-1"></i></a>
      </div>
      <?php if (empty($recentWorkingPlans)): ?>
        <div class="empty-msg">No working plans found</div>
      <?php else: ?>
      <table class="rd-table">
        <thead>
          <tr>
            <th>Project</th>
            <th>Work Name</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentWorkingPlans as $wp): ?>
          <tr>
            <td style="font-size:11.5px;color:var(--text-muted)"><?= htmlspecialchars($wp['project_name'] ?? 'N/A') ?></td>
            <td><strong><?= htmlspecialchars($wp['work_name'] ?? $wp['work_description'] ?? 'N/A') ?></strong></td>
            <?php
                $wpStatus = $wp['approved_status'] ?? 'Draft';
                $wpBadge  = match(strtolower($wpStatus)) {
                    'approved'   => 'success',
                    'in review'  => 'info',
                    'draft'      => 'secondary',
                    default      => 'secondary',
                };
            ?>
            <td><span class="badge bg-<?= $wpBadge ?>"><?= htmlspecialchars($wpStatus) ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

    <div class="rd-panel">
      <div class="rd-panel-title">
        <div class="rd-dot" style="background:var(--op-purple)"></div>
        Recent Vendor Payments
        <a href="<?= $base ?>/con-vendor-payments" style="font-size:10px;color:var(--op-purple);margin-left:auto;text-decoration:none">View All <i class="fal fa-arrow-right ms-1"></i></a>
      </div>
      <?php if (empty($recentVendorPayments)): ?>
        <div class="empty-msg">No vendor payments found</div>
      <?php else: ?>
      <table class="rd-table">
        <thead>
          <tr>
            <th>Vendor</th>
            <th>Project</th>
            <th>Amount</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentVendorPayments as $vp): ?>
          <tr>
            <td><strong><?= htmlspecialchars($vp['vendor_name'] ?? 'N/A') ?></strong></td>
            <td style="font-size:11.5px;color:var(--text-muted)"><?= htmlspecialchars($vp['project_name'] ?? 'N/A') ?></td>
            <td><strong><?= opFmt($vp['net_payable']) ?></strong></td>
            <td><span class="badge bg-secondary"><?= htmlspecialchars($vp['status'] ?? 'Draft') ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>

</div></div>

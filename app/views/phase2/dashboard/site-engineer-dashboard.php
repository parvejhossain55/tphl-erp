<?php
$base_url = $GLOBALS['base_url'];

if (!function_exists('toBengaliNum')) {
    function toBengaliNum($number) {
        return (string)$number;
    }
}

if (!function_exists('cpFmtPct')) {
    function cpFmtPct($n) {
        $n = (float)$n;
        if ($n <= 0) return '0.0%';
        if ($n < 0.1) return number_format($n, 3) . '%';
        return number_format($n, 1) . '%';
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
?>

<div class="row">
<div class="col-12">
<style>
/* Dashboard Premium Styling & Responsive Layouts */
:root {
  --se-primary: #16a34a;
  --se-dark:    #15803d;
  --se-blue:    #3b82f6;
  --se-green:   #10b981;
  --se-amber:   #f59e0b;
  --se-rose:    #ef4444;
  --se-purple:  #8b5cf6;
  --se-cyan:    #06b6d4;
}

.se-hero {
  background: linear-gradient(135deg, #15803d 0%, #16a34a 55%, #22c55e 100%);
  border-radius: 18px;
  padding: 28px 32px;
  margin-bottom: 24px;
  position: relative;
  overflow: hidden;
}
.se-hero::before {
  content: '';
  position: absolute;
  width: 300px; height: 300px;
  border-radius: 50%;
  background: rgba(255,255,255,.04);
  top: -100px; right: -80px;
}
.se-hero::after {
  content: '';
  position: absolute;
  width: 200px; height: 200px;
  border-radius: 50%;
  background: rgba(255,255,255,.03);
  bottom: -60px; left: -40px;
}
.se-role-badge {
  display: inline-flex; align-items: center; gap: 8px;
  background: rgba(255,255,255,.12);
  border: 1px solid rgba(255,255,255,.2);
  padding: 5px 14px; border-radius: 20px;
  font-size: 11px; font-weight: 700; letter-spacing: 1.5px;
  text-transform: uppercase; color: #fff; margin-bottom: 14px;
}
.se-hero-title { font-size: 28px; font-weight: 900; color: #fff; margin: 0 0 6px; }
.se-hero-sub   { font-size: 13px; color: rgba(255,255,255,.7); margin: 0; }

.kpi-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 22px; }
@media(max-width:992px){ .kpi-grid-4 { grid-template-columns: repeat(2, 1fr); } }
@media(max-width:576px){ .kpi-grid-4 { grid-template-columns: 1fr; } }

.kpi-card {
  border-radius: 14px; padding: 18px 20px;
  position: relative; overflow: hidden;
  border: 1px solid var(--clr-bg-body, #f1f5f9) !important;
  text-decoration: none !important; display: block;
  transition: transform .18s, box-shadow .18s;
  background: var(--clr-bg-card, #fff);
}
.kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 28px rgba(0,0,0,.08); }
.kpi-accent { position: absolute; left: 0; top: 0; width: 4px; height: 100%; border-radius: 14px 0 0 14px; }
.kpi-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-bottom: 12px; }
.kpi-value { font-size: 28px; font-weight: 900; line-height: 1; margin-bottom: 4px; }
.kpi-label { font-size: 10.5px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 3px; }

.rd-panel { border-radius: 14px; padding: 22px; border: 1px solid var(--clr-bg-body, #f1f5f9) !important; background: var(--clr-bg-card, #fff); margin-bottom: 22px; }
.rd-panel-title { font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.rd-dot { width: 8px; height: 8px; border-radius: 50%; }
.rd-table { width: 100%; border-collapse: collapse; }
.rd-table th { font-size: 10px; letter-spacing: 1.4px; text-transform: uppercase; font-weight: 600; padding: 0 10px 10px; border-bottom: 2px solid var(--clr-bg-body, #f1f5f9); text-align: left; }
.rd-table td { padding: 12px 10px; border-bottom: 1px solid var(--clr-bg-body, #f1f5f9); font-size: 12.5px; vertical-align: middle; }
.empty-msg { text-align: center; padding: 28px 0; color: var(--text-muted); font-size: 12px; }

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
    white-space: nowrap;
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

/* Progress bar system */
.prog-compare-wrap {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.prog-bar-container {
    width: 100%;
    border-radius: 99px;
    overflow: hidden;
    background: var(--clr-bg-body, #f1f5f9);
}
.prog-bar-fill-planned {
    border-radius: 99px;
    transition: width 0.3s ease;
}
.prog-bar-fill-actual {
    border-radius: 99px;
    transition: width 0.3s ease;
}

/* Collapsible phases */
.phase-items-container {
    animation: slideDown 0.2s ease-out;
}
.phase-breakdown-row {
    background: var(--clr-bg-body, #fafbfc) !important;
}
html[data-theme-mode="dark"] .phase-breakdown-row,
.dark-theme .phase-breakdown-row,
[data-theme="dark"] .phase-breakdown-row,
body.bd-theme-dark .phase-breakdown-row {
    background: transparent !important;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes pulse {
    0% { transform: scale(0.9); opacity: 0.6; }
    50% { transform: scale(1.1); opacity: 1; }
    100% { transform: scale(0.9); opacity: 0.6; }
}

/* Mobile Layout Styling */
.mobile-wo-card, .mobile-po-card, .mobile-project-card {
    transition: transform 0.15s, box-shadow 0.15s;
    box-shadow: 0 2px 6px rgba(0,0,0,0.03);
    border: 1px solid var(--clr-bg-body, #f1f5f9) !important;
}
.mobile-wo-card:hover, .mobile-po-card:hover, .mobile-project-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}
</style>

<!-- HERO HEADER -->
<div class="se-hero">
  <div style="position:relative;z-index:2;">
    <div class="se-role-badge">
      <i class="fa-solid fa-hard-hat"></i>
      Site Engineer
    </div>
    <h4 class="se-hero-title">Site Engineer Dashboard</h4>
    <p class="se-hero-sub">Welcome back! Manage your assigned projects, material inventory and submit progress reports · <?= date('d M Y') ?></p>
  </div>
</div>

<!-- KPI CARDS -->
<div class="kpi-grid-4">
  <div class="kpi-card">
    <div class="kpi-accent" style="background:var(--se-primary)"></div>
    <div class="kpi-icon" style="background:rgba(22,163,74,.12);color:var(--se-primary)"><i class="bi bi-buildings"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Assigned Projects</div>
    <div class="kpi-value" style="color:var(--clr-common-black, #0f172a)"><?= $myProjectsCount ?></div>
  </div>

  <div class="kpi-card">
    <div class="kpi-accent" style="background:var(--se-green)"></div>
    <div class="kpi-icon" style="background:rgba(16,185,129,.12);color:var(--se-green)"><i class="bi bi-box-seam"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Material Received (GRN)</div>
    <div class="kpi-value" style="color:var(--clr-common-black, #0f172a)"><?= $totalReceived ?></div>
  </div>

  <div class="kpi-card">
    <div class="kpi-accent" style="background:var(--se-amber)"></div>
    <div class="kpi-icon" style="background:rgba(245,158,11,.12);color:var(--se-amber)"><i class="bi bi-arrow-up-right-square"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Material Issued</div>
    <div class="kpi-value" style="color:var(--clr-common-black, #0f172a)"><?= $totalIssued ?></div>
  </div>

  <div class="kpi-card">
    <div class="kpi-accent" style="background:var(--se-cyan)"></div>
    <div class="kpi-icon" style="background:rgba(6,182,212,.12);color:var(--se-cyan)"><i class="bi bi-clipboard-data"></i></div>
    <div class="kpi-label" style="color:var(--text-muted)">Reports Submitted</div>
    <div class="kpi-value" style="color:var(--clr-common-black, #0f172a)"><?= $totalReports ?></div>
  </div>
</div>

<!-- PENDING WORK ORDERS FOR APPROVAL (ACTION REQUIRED) -->
<?php
$pendingWorkOrders = $pendingWorkOrders ?? [];
if (!empty($pendingWorkOrders)):
?>
<div class="rd-panel" style="border: 1px solid var(--clr-bg-body, #f1f5f9) !important; background: rgba(14,165,233,.015)">
  <div class="rd-panel-title">
    <div class="rd-dot" style="background:#0ea5e9; animation:pulse 1.5s infinite"></div>
    Work Orders Awaiting Your Approval
    <span class="badge bg-info ms-2" style="font-size:10px"><?= count($pendingWorkOrders) ?> Pending</span>
    <a href="<?= $base_url ?>/con-work-orders" style="font-size:10px; margin-left:auto; text-decoration:none">
       View All Work Orders <i class="fa-solid fa-arrow-right ms-1"></i>
    </a>
  </div>
  
  <div class="alert alert-info border-0 rounded-3 mb-3 py-2 px-3" style="font-size:12px; background: rgba(14,165,233,.08); color: #0369a1;">
      <i class="bi bi-info-circle me-1"></i>
      নিচের Work Order গুলো আপনার এপ্রুভাল এর জন্য পেন্ডিং রয়েছে। রিভিউ করে এপ্রুভ করুন।
  </div>

  <!-- Desktop Table View -->
  <div class="table-responsive d-none d-lg-block">
    <table class="rd-table" style="font-size:12.5px">
      <thead>
        <tr>
          <th style="width: 5%">#</th>
          <th style="width: 20%">Project</th>
          <th style="width: 25%">Work Order Title</th>
          <th style="width: 15%">Contractor</th>
          <th style="width: 15%">Total Amount</th>
          <th style="width: 10%">Created By</th>
          <th style="width: 10%">Submitted Date</th>
          <th style="width: 10%; text-align: right;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pendingWorkOrders as $woIndex => $wo): ?>
        <tr>
          <td><?= $woIndex + 1 ?></td>
          <td><strong><?= htmlspecialchars($wo['project_name'] ?? '—') ?></strong></td>
          <td class="fw-semibold"><?= htmlspecialchars($wo['title'] ?? '—') ?></td>
          <td><?= htmlspecialchars($wo['contractor_name'] ?? '—') ?></td>
          <td class="fw-bold">৳<?= number_format((float)$wo['total_amount'], 2) ?></td>
          <td class="text-muted" style="font-size:11px"><?= htmlspecialchars($wo['creator_name'] ?? '—') ?></td>
          <td class="text-muted" style="font-size:11px"><?= !empty($wo['created_at']) ? date('d M Y', strtotime($wo['created_at'])) : '—' ?></td>
          <td class="text-end">
            <a href="<?= $base_url ?>/con-work-order/<?= $wo['id'] ?>" class="btn btn-sm btn-info text-white py-1 px-3" style="font-size:11.5px">
              <i class="bi bi-check-circle"></i> Review
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Mobile Cards View -->
  <div class="d-block d-lg-none">
    <?php foreach ($pendingWorkOrders as $woIndex => $wo): ?>
    <div class="mobile-wo-card p-3 mb-2 rounded-3 border">
       <div class="d-flex justify-content-between align-items-start mb-2">
           <div>
               <div class="text-muted" style="font-size:10px; text-transform:uppercase; font-weight:700;">Project</div>
               <div class="fw-bold text-dark" style="font-size:13px;"><?= htmlspecialchars($wo['project_name'] ?? '—') ?></div>
           </div>
           <span class="badge bg-info-transparent" style="font-size: 10px;">Pending Approval</span>
       </div>
       <div class="mb-2">
           <div class="text-muted" style="font-size:10px; text-transform:uppercase; font-weight:700;">Work Order Title</div>
           <div class="fw-semibold text-dark" style="font-size:13px;"><?= htmlspecialchars($wo['title'] ?? '—') ?></div>
       </div>
       <div class="row g-2 mb-3">
           <div class="col-6">
               <div class="text-muted" style="font-size:10px; text-transform:uppercase; font-weight:700;">Contractor</div>
               <div style="font-size:12px;"><?= htmlspecialchars($wo['contractor_name'] ?? '—') ?></div>
           </div>
           <div class="col-6">
               <div class="text-muted" style="font-size:10px; text-transform:uppercase; font-weight:700;">Total Amount</div>
               <div class="fw-bold text-dark" style="font-size:13px;">৳<?= number_format((float)$wo['total_amount'], 2) ?></div>
           </div>
       </div>
       <div class="d-flex justify-content-between align-items-center pt-2 border-top" style="font-size:11px; color:#6b7280;">
           <div>Created by: <?= htmlspecialchars($wo['creator_name'] ?? '—') ?></div>
           <div><?= !empty($wo['created_at']) ? date('d M Y', strtotime($wo['created_at'])) : '—' ?></div>
       </div>
       <div class="mt-3">
           <a href="<?= $base_url ?>/con-work-order/<?= $wo['id'] ?>" class="btn btn-info w-100 text-white" style="font-size:12px; padding: 8px 12px;">
               <i class="bi bi-check-circle"></i> Review &amp; Approve
           </a>
       </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- APPROVED PURCHASE ORDERS (ACTION REQUIRED) -->
<?php
$approvedPOs = $approvedPOs ?? [];
if (!empty($approvedPOs)):
?>
<div class="rd-panel" style="border: 1px solid var(--clr-bg-body, #f1f5f9) !important; background: rgba(16,185,129,.015)">
  <div class="rd-panel-title">
    <div class="rd-dot" style="background:#10b981; animation:pulse 1.5s infinite"></div>
    Purchase Orders Approved — Action Required
    <span class="badge bg-success ms-2" style="font-size:10px"><?= count($approvedPOs) ?> POs</span>
    <a href="<?= $base_url ?>/con-purchase-orders" style="font-size:10px; margin-left:auto; text-decoration:none">
       View All POs <i class="fa-solid fa-arrow-right ms-1"></i>
    </a>
  </div>
  
  <div class="alert alert-success border-0 rounded-3 mb-3 py-2 px-3" style="font-size:12px; background: rgba(16,185,129,.08); color: #047857;">
      <i class="bi bi-info-circle me-1"></i>
      নিচের Purchase Order গুলো Project Manager কর্তৃক Approved হয়েছে। এগুলোর Material Receiving (GRN) করুন।
  </div>

  <!-- Desktop Table View -->
  <div class="table-responsive d-none d-lg-block">
    <table class="rd-table" style="font-size:12.5px">
      <thead>
        <tr>
          <th style="width: 10%">#PO</th>
          <th style="width: 20%">Project</th>
          <th style="width: 10%">PR Ref</th>
          <th style="width: 10%">Items</th>
          <th style="width: 15%">Total Amount</th>
          <th style="width: 10%">PO Status</th>
          <th style="width: 12%">Approved By (PM)</th>
          <th style="width: 13%">Approved On</th>
          <th style="width: 10%; text-align: right;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($approvedPOs as $po):
            $poStatus = $po['status'] ?? 'Issued';
            $stBadge = match($poStatus) {
                'Issued'            => 'bg-primary-transparent',
                'Partially Received' => 'bg-warning-transparent',
                'Fully Received'    => 'bg-success-transparent',
                'Cancelled'         => 'bg-danger-transparent',
                default             => 'bg-secondary-transparent',
            };
        ?>
        <tr>
          <td class="fw-bold">PO-<?= $po['id'] ?></td>
          <td><strong><?= htmlspecialchars($po['project_name'] ?? 'N/A') ?></strong></td>
          <td><a href="<?= $base_url ?>/con-purchase-request/<?= $po['pr_id'] ?>" class="text-primary" style="font-size:11px">PR-<?= $po['pr_id'] ?></a></td>
          <td><span class="badge text-dark border" style="font-size:10px; background: transparent;"><?= (int)$po['item_count'] ?> items</span></td>
          <td class="fw-bold">৳<?= number_format((float)$po['total_amount'], 2) ?></td>
          <td><span class="badge <?= $stBadge ?>" style="font-size:10px"><?= $poStatus ?></span></td>
          <td class="text-muted" style="font-size:11px"><?= htmlspecialchars($po['approved_by_name'] ?? '—') ?></td>
          <td class="text-muted" style="font-size:11px"><?= !empty($po['pm_approved_at']) ? date('d M Y', strtotime($po['pm_approved_at'])) : '—' ?></td>
          <td class="text-end">
            <div class="d-flex justify-content-end gap-1">
                <a href="<?= $base_url ?>/con-purchase-order/<?= $po['id'] ?>" class="btn btn-sm btn-outline-success" style="font-size:10px; padding: 2px 6px;">
                    <i class="bi bi-eye"></i> View
                </a>
                <?php if (in_array($poStatus, ['Issued', 'Partially Received'])): ?>
                <a href="<?= $base_url ?>/con-material-receiving-create?po_id=<?= $po['id'] ?>" class="btn btn-sm btn-success text-white" style="font-size:10px; padding: 2px 6px;">
                    <i class="bi bi-inbox-fill"></i> Receive
                </a>
                <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Mobile Cards View -->
  <div class="d-block d-lg-none">
    <?php foreach ($approvedPOs as $po):
        $poStatus = $po['status'] ?? 'Issued';
        $stBadge = match($poStatus) {
            'Issued'            => 'bg-primary-transparent',
            'Partially Received' => 'bg-warning-transparent',
            'Fully Received'    => 'bg-success-transparent',
            'Cancelled'         => 'bg-danger-transparent',
            default             => 'bg-secondary-transparent',
        };
    ?>
    <div class="mobile-po-card p-3 mb-2 rounded-3 border">
       <div class="d-flex justify-content-between align-items-start mb-2">
           <div>
               <span class="fw-bold text-dark" style="font-size:13.5px;">PO-<?= $po['id'] ?></span>
               <div style="font-size:11px; color:#6b7280;">PR Ref: <a href="<?= $base_url ?>/con-purchase-request/<?= $po['pr_id'] ?>" class="text-primary">PR-<?= $po['pr_id'] ?></a></div>
           </div>
           <span class="badge <?= $stBadge ?>" style="font-size: 10px;"><?= $poStatus ?></span>
       </div>
       <div class="mb-2">
           <div class="text-muted" style="font-size:10px; text-transform:uppercase; font-weight:700;">Project</div>
           <div class="fw-bold text-dark" style="font-size:13px;"><?= htmlspecialchars($po['project_name'] ?? 'N/A') ?></div>
       </div>
       <div class="row g-2 mb-3">
           <div class="col-6">
               <div class="text-muted" style="font-size:10px; text-transform:uppercase; font-weight:700;">Items Count</div>
               <span class="badge text-dark border" style="font-size:10px; background: transparent;"><?= (int)$po['item_count'] ?> items</span>
           </div>
           <div class="col-6">
               <div class="text-muted" style="font-size:10px; text-transform:uppercase; font-weight:700;">Total Amount</div>
               <div class="fw-bold text-dark" style="font-size:13px;">৳<?= number_format((float)$po['total_amount'], 2) ?></div>
           </div>
       </div>
       <div class="d-flex justify-content-between align-items-center pt-2 border-top" style="font-size:11px; color:#6b7280;">
           <div>Approved by: <?= htmlspecialchars($po['approved_by_name'] ?? '—') ?></div>
           <div><?= !empty($po['pm_approved_at']) ? date('d M Y', strtotime($po['pm_approved_at'])) : '—' ?></div>
       </div>
       <div class="mt-3 d-flex gap-2">
           <a href="<?= $base_url ?>/con-purchase-order/<?= $po['id'] ?>" class="btn btn-outline-success flex-fill" style="font-size:12px; padding: 6px 12px;">
               <i class="bi bi-eye"></i> View
           </a>
           <?php if (in_array($poStatus, ['Issued', 'Partially Received'])): ?>
           <a href="<?= $base_url ?>/con-material-receiving-create?po_id=<?= $po['id'] ?>" class="btn btn-success flex-fill text-white" style="font-size:12px; padding: 6px 12px;">
               <i class="bi bi-inbox-fill"></i> Receive
           </a>
           <?php endif; ?>
       </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- ASSIGNED PROJECTS PANEL -->
<div class="rd-panel">
  <div class="rd-panel-title">
    <div class="rd-dot" style="background:var(--se-primary)"></div>
    My Assigned Projects
    <a href="<?= $base_url ?>/con-projects" style="font-size:10px; margin-left:auto; text-decoration:none">
       View All Projects <i class="fa-solid fa-arrow-right ms-1"></i>
    </a>
  </div>

  <!-- Desktop Table View -->
  <div class="table-responsive d-none d-lg-block">
    <table class="rd-table" id="projectOverviewTable">
      <thead>
        <tr>
          <th style="width: 25%;">Project & Location</th>
          <th style="width: 15%;">Current Phase</th>
          <th style="width: 30%;">Planned vs Actual Progress</th>
          <th style="width: 10%;">Status</th>
          <th style="width: 10%;">Handover Target</th>
          <th style="width: 8%;">Budget Usage</th>
          <th style="width: 2%; text-align: center;"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($myProjects)): ?>
            <tr><td colspan="7" class="text-center text-muted empty-msg">No projects assigned to you.</td></tr>
        <?php else: ?>
            <?php foreach ($myProjects as $pr):
                $pct = round((float) ($pr['progress_pct'] ?? 0));
                $plPct = round((float) ($pr['planned_progress_pct'] ?? 0));
                $status = $pr['calculated_status'] ?? $pr['project_status'] ?? 'Planning';
                $isPhaseBased = ($pr['workflow_version'] ?? '') === 'phase_based';
                
                $statusClass = 'status-planning';
                if ($status === 'On Track') $statusClass = 'status-ontrack';
                elseif ($status === 'At Risk') $statusClass = 'status-atrisk';
                elseif ($status === 'Delayed') $statusClass = 'status-delayed';
                elseif ($status === 'Completed') $statusClass = 'status-completed';

                $detailUrl = ($isPhaseBased) 
                    ? $base_url . '/con-project-timeline/' . $pr['id'] 
                    : $base_url . '/con-project/' . $pr['id'];

                $budgetDetailUrl = !empty($pr['budget_id'])
                    ? $base_url . '/con-estimate-show/' . $pr['budget_id']
                    : $detailUrl;

                $budgetPct = $pr['total_budget'] > 0 ? round(($pr['total_spent'] / $pr['total_budget']) * 100) : 0;

                // Construct Overall Progress Popover Content
                $plaLines = [];
                $actLines = [];
                if (!empty($pr['phases'])) {
                    foreach ($pr['phases'] as $phase) {
                        if ((float)$phase['total_boq'] > 0) {
                            $phName = $phase['phase_name'];
                            $phWeight = (float)$phase['phase_weight'];
                            
                            $targetMonthBn = 'জুনের';
                            if (!empty($phase['target_month'])) {
                                $bnMonth = getBengaliMonthName($phase['target_month']);
                                $targetMonthBn = ($bnMonth === 'জুন') ? 'জুনের' : $bnMonth . ' মাসের';
                            }
                            
                            $phPlannedVal = (float)$phase['phase_planned_weighted_pct'];
                            $phPlannedContrib = $phPlannedVal * ($phWeight / 100);
                            if ($phPlannedVal > 0) {
                                $bnWeight = toBengaliNum(round($phWeight, 1));
                                $bnPlanned = toBengaliNum(round($phPlannedVal, 1));
                                $bnPlannedContrib = toBengaliNum(round($phPlannedContrib, 1));
                                $plaLines[] = "{$phName} ফেজের {$targetMonthBn} নিজস্ব প্ল্যান ({$bnPlanned}%) × ফেজ ওয়েট ({$bnWeight}%) = {$bnPlannedContrib}%";
                            }
                            
                            $phActualVal = (float)$phase['phase_actual_weighted_pct'];
                            $phActualContrib = $phActualVal * ($phWeight / 100);
                            if ($phActualVal > 0) {
                                $bnWeight = toBengaliNum(round($phWeight, 1));
                                $bnActual = toBengaliNum(round($phActualVal, 2));
                                $bnActualContrib = toBengaliNum(round($phActualContrib, 2));
                                $actLines[] = "{$phName} ফেজের {$targetMonthBn} অগ্রগতি ({$bnActual}%) × ফেজ ওয়েট ({$bnWeight}%) = {$bnActualContrib}%";
                            }
                        }
                    }
                }
                
                $formattedPla = ($pr['planned_progress_pct'] > 0 && $pr['planned_progress_pct'] < 0.1) ? number_format($pr['planned_progress_pct'], 3) : number_format($pr['planned_progress_pct'], 1);
                $formattedAct = ($pr['progress_pct'] > 0 && $pr['progress_pct'] < 0.1) ? number_format($pr['progress_pct'], 3) : number_format($pr['progress_pct'], 1);

                $plaPopoverContent = "<strong>ℹ️ সামগ্রিক পরিকল্পিত অগ্রগতি:</strong><br/>"
                                   . (empty($plaLines) ? "কোনো প্ল্যান সেট করা নেই।" : "হিসাব: " . implode(" + ", $plaLines) . " = " . toBengaliNum($formattedPla) . "% (পুরো প্রজেক্টের সাপেক্ষে)");
                                   
                $actPopoverContent = "<strong>ℹ️ সামগ্রিক প্রকৃত অগ্রগতি:</strong><br/>"
                                   . (empty($actLines) ? "কোনো অগ্রগতি নেই।" : "হিসাব: " . implode(" + ", $actLines) . " = " . toBengaliNum($formattedAct) . "% (পুরো প্রজেক্টের সাপেক্ষে)");
                
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
                    if ($currentPhaseName === 'N/A') {
                        $lastPhase = end($pr['phases']);
                        $currentPhaseName = $lastPhase['phase_name'];
                        $currentPhaseIdx = $totalPhases;
                    }
                    $currentPhaseSub = "Phase " . $currentPhaseIdx . " of " . $totalPhases;
                }
                
                $colors = ['#4f46e5', '#0891b2', '#0d9488', '#ea580c', '#e11d48', '#2563eb'];
                $gradColor = $colors[$pr['id'] % count($colors)];
                ?>
                <tr class="project-row">
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <?php if (!empty($pr['phases'])): ?>
                                <button onclick="togglePhaseItems('phases-breakdown-dt-<?= $pr['id'] ?>', this.querySelector('i'))" 
                                        style="border: none; background: transparent; padding: 4px; color: #94a3b8; cursor: pointer; transition: color 0.15s; outline: none;">
                                    <i class="fa-solid fa-eye" style="font-size: 13px;"></i>
                                </button>
                            <?php else: ?>
                                <span style="width: 19px; display: inline-block;"></span>
                            <?php endif; ?>

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
                                <a href="<?= $detailUrl ?>" class="proj-title" style="font-size: 13.5px; font-weight: 700; color: var(--clr-common-black, #0f172a); text-decoration: none; display: block;"><?= htmlspecialchars($pr['name']) ?></a>
                                <div class="proj-loc" style="font-size: 11px; color: var(--clr-text-muted, #64748b); margin-top: 2px; display: flex; align-items: center; gap: 4px;">
                                    <i class="fa-solid fa-location-dot" style="font-size: 10px;"></i>
                                    <?= htmlspecialchars($pr['location_name'] ?: 'N/A') ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    
                    <td>
                        <div style="font-weight: 700; color: var(--clr-common-black, #1e293b); font-size: 13px;"><?= htmlspecialchars($currentPhaseName) ?></div>
                        <div style="font-size: 11px; color: var(--text-muted, #94a3b8); margin-top: 2px;"><?= $currentPhaseSub ?></div>
                    </td>
                    
                    <td style="min-width: 200px;">
                        <div class="prog-compare-wrap">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                                <span style="font-size: 10px; font-weight: 700; color: var(--text-muted, #94a3b8); min-width: 24px;">Pla</span>
                                <div class="prog-bar-container" style="height: 6px; flex: 1;">
                                    <div class="prog-bar-fill-planned" style="width: <?= min(100, $pr['planned_progress_pct']) ?>%; height: 100%; background: #6366f1; opacity: 0.85;"></div>
                                </div>
                                <span style="font-size: 11px; font-weight: 800; min-width: 32px; text-align: right; display: flex; align-items: center; gap: 4px;">
                                    <?= cpFmtPct($pr['planned_progress_pct']) ?>
                                    <span style="font-size: 10px; cursor: pointer; color: var(--text-muted, #94a3b8);" data-bs-toggle="popover" data-bs-content="<?= htmlspecialchars($plaPopoverContent) ?>">ℹ️</span>
                                </span>
                            </div>
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                                <span style="font-size: 10px; font-weight: 700; color: var(--text-muted, #94a3b8); min-width: 24px;">Act</span>
                                <div class="prog-bar-container" style="height: 6px; flex: 1;">
                                    <div class="prog-bar-fill-actual" style="width: <?= min(100, $pr['progress_pct']) ?>%; height: 100%; background: #198754;"></div>
                                </div>
                                <span style="font-size: 11px; font-weight: 800; min-width: 32px; text-align: right; display: flex; align-items: center; gap: 4px;">
                                    <?= cpFmtPct($pr['progress_pct']) ?>
                                    <span style="font-size: 10px; cursor: pointer; color: var(--text-muted, #94a3b8);" data-bs-toggle="popover" data-bs-content="<?= htmlspecialchars($actPopoverContent) ?>">ℹ️</span>
                                </span>
                            </div>
                        </div>
                    </td>
                    
                    <td>
                        <span class="status-pill <?= $statusClass ?>">
                            <span class="status-pill-dot"></span>
                            <?= $status ?>
                        </span>
                    </td>
                    
                    <td>
                        <div style="font-weight: 700; color: var(--clr-common-black, #1e293b); font-size: 13px;">
                            <?= $pr['expected_handover_date'] ? date('M Y', strtotime($pr['expected_handover_date'])) : 'N/A' ?>
                        </div>
                        <div style="font-size:11px; color: var(--text-muted, #94a3b8); margin-top:2px">
                            <?php if ($pr['expected_handover_date']):
                                $diff = time() - strtotime($pr['expected_handover_date']);
                                $months = abs(round($diff / (60 * 60 * 24 * 30)));
                                echo $diff < 0 ? "In $months months" : "$months months ago";
                            else:
                                echo 'Timeline unset';
                            endif; ?>
                        </div>
                    </td>
                    
                    <td>
                        <div style="font-weight: 700; color: var(--clr-common-black, #1e293b); font-size: 13px;"><?= cpFmt($pr['total_spent']) ?></div>
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 4px;">
                            <div class="prog-bar-container" style="height: 4px; flex: 1;">
                                <div class="prog-bar-fill-actual" style="width: <?= min(100, $budgetPct) ?>%; height: 100%; background: <?= $budgetPct > 90 ? '#ef4444' : '#198754' ?>;"></div>
                            </div>
                            <span style="font-size: 10px; font-weight: 700; color: var(--clr-text-muted, #64748b); min-width: 26px; text-align: right;"><?= $budgetPct ?>%</span>
                        </div>
                    </td>
                    
                    <td style="text-align: center;">
                        <button style="border: none; background: transparent; color: var(--text-muted, #94a3b8); cursor: pointer; padding: 4px; font-size: 14px; outline: none;" 
                                onclick="window.location.href='<?= $budgetDetailUrl ?>'">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        </button>
                    </td>
                </tr>

                <!-- Nested Timeline Phases Detail Block (Desktop) -->
                <?php if (!empty($pr['phases'])): ?>
                    <tr id="phases-breakdown-dt-<?= $pr['id'] ?>" class="phase-breakdown-row phase-items-container" style="display: none;">
                        <td colspan="7" style="padding: 18px 24px; border-bottom: 1px solid var(--clr-bg-body, #f1f5f9);">
                            <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--clr-text-muted, #64748b); letter-spacing: 0.5px; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-folder-open text-primary"></i> Phase Progress Breakdown for <?= htmlspecialchars($pr['name']) ?>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <?php foreach ($pr['phases'] as $phase): 
                                    $phWeight = (float)$phase['phase_weight'];
                                    $phaseActualProgress = (float)$phase['phase_actual_pct'];
                                    $phaseContribution = (float)$phase['phase_contribution'];

                                    $totalApprovedVal = (float)$phase['total_approved_qty'];
                                    $totalBOQVal = (float)$phase['total_boq'];
                                    
                                    $phaseProgressTooltip = "<strong>ℹ️ ফেজের নিজস্ব অগ্রগতি:</strong><br/>"
                                                          . "হিসাব: (" . number_format($totalApprovedVal) . " CFT ÷ " . number_format($totalBOQVal) . " CFT) × ১০০ = " . number_format(round($phaseActualProgress, 1), 1) . "%";

                                    $phaseProgressVal2 = number_format($phaseActualProgress, 2);
                                    $phaseWeightVal = number_format($phWeight, 1);
                                    $phaseContributionVal = number_format($phaseContribution, 2);
                                    
                                    $phaseContributionTooltip = "<strong>ℹ️ প্রজেক্টে এই ফেজের অবদান:</strong><br/>"
                                                              . "হিসাব: " . $phaseProgressVal2 . "% × (" . $phaseWeightVal . " ÷ ১০০) = " . $phaseContributionVal . "%";
                                    ?>
                                    <div style="display: grid; grid-template-columns: 200px 1fr; align-items: center; gap: 16px;">
                                        <div style="font-size: 12px; font-weight: 700; color: var(--clr-common-black, #1e293b); display: flex; align-items: center; gap: 8px;">
                                             <?php if (!empty($phase['items'])): ?>
                                                <i class="fa-solid fa-chevron-right phase-toggle-icon" 
                                                   onclick="togglePhaseItems('subitems-dt-<?= $pr['id'] ?>-<?= $phase['phase_id'] ?>', this)" 
                                                   style="cursor: pointer; font-size: 9px; width: 12px; color: #198754; transition: transform 0.15s; display: inline-block;"></i>
                                             <?php else: ?>
                                                <span style="width: 12px; display: inline-block;"></span>
                                             <?php endif; ?>
                                             <span style="<?php if (!empty($phase['items'])): ?>cursor: pointer;<?php endif; ?>" 
                                                   onclick="<?php if (!empty($phase['items'])): ?>togglePhaseItems('subitems-dt-<?= $pr['id'] ?>-<?= $phase['phase_id'] ?>', this.previousElementSibling)<?php endif; ?>">
                                                 <?= htmlspecialchars($phase['phase_name']) ?>
                                             </span>
                                        </div>
                                        <div style="width: 100%;">
                                            <div style="font-size: 9px; font-weight: 800; display: flex; justify-content: space-between; margin-bottom: 2px;">
                                                <span class="text-muted">Phase Weight: <?= number_format($phWeight, 1) ?>%</span>
                                                <span class="text-muted" style="display: flex; align-items: center; gap: 4px;">
                                                     Contribution: <?= number_format($phaseContribution, 2) ?>% 
                                                     <span style="font-size: 9px; cursor: pointer;" data-bs-toggle="popover" data-bs-content="<?= htmlspecialchars($phaseContributionTooltip) ?>">ℹ️</span>
                                                     (<?= number_format($phaseActualProgress, 1) ?>% complete)
                                                     <span style="font-size: 9px; cursor: pointer;" data-bs-toggle="popover" data-bs-content="<?= htmlspecialchars($phaseProgressTooltip) ?>">ℹ️</span>
                                                </span>
                                            </div>
                                            <div class="prog-bar-container" style="height: 6px; background: var(--clr-bg-body, #e2e8f0); position: relative;">
                                                <div class="prog-bar-fill-planned" style="width: <?= $phWeight ?>%; height: 100%; background: #6366f1; opacity: 0.15; position: absolute; left: 0;"></div>
                                                <div class="prog-bar-fill-actual" style="width: <?= $phaseContribution ?>%; height: 100%; background: <?= $phaseActualProgress >= 100 ? '#198754' : '#f59e0b' ?>; position: absolute; left: 0;"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Nested Sub-items Loop (Desktop) -->
                                    <?php if (!empty($phase['items'])): ?>
                                        <div id="subitems-dt-<?= $pr['id'] ?>-<?= $phase['phase_id'] ?>" class="phase-items-container" style="display: none; flex-direction: column; gap: 8px; margin-left: 24px; border-left: 2px solid var(--clr-bg-body, #e2e8f0); padding-left: 14px; margin-top: 4px; margin-bottom: 4px;">
                                            <?php foreach ($phase['items'] as $item): 
                                                $itemWeight = (float)$item['item_weight'];
                                                $itemActualPct = (float)$item['item_actual_pct'];
                                                $itemProjWeight = ($itemWeight / 100) * $phWeight;
                                                $itemActWeightVal = ($itemActualPct / 100) * $itemProjWeight;
                                                ?>
                                                <div style="display: grid; grid-template-columns: 180px 1fr; align-items: center; gap: 16px;">
                                                    <div style="font-size: 11.5px; font-weight: 600; color: var(--clr-text-muted, #64748b); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($item['item_name']) ?>">
                                                        ↳ <?= htmlspecialchars($item['item_name']) ?>
                                                    </div>
                                                    <div style="width: 100%;">
                                                        <div style="font-size: 8px; font-weight: 700; display: flex; justify-content: space-between; margin-bottom: 1px;">
                                                            <span class="text-muted">Item Weight: <?= number_format($itemProjWeight, 2) ?>%</span>
                                                            <span class="text-muted">
                                                                Contribution: <?= number_format($itemActWeightVal, 2) ?>% (<?= number_format($itemActualPct, 1) ?>% complete)
                                                                <?php if (!empty($item['qty_usage_text'])): ?>
                                                                    &middot; <span style="font-weight: 500;"><?= htmlspecialchars($item['qty_usage_text']) ?></span>
                                                                <?php endif; ?>
                                                            </span>
                                                        </div>
                                                        <div class="prog-bar-container" style="height: 4px; position: relative;">
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
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Mobile Cards View -->
  <div class="d-block d-lg-none">
    <?php if (empty($myProjects)): ?>
        <div class="empty-msg">No projects assigned to you.</div>
    <?php else: ?>
        <?php foreach ($myProjects as $pr):
            $pct = round((float) ($pr['progress_pct'] ?? 0));
            $plPct = round((float) ($pr['planned_progress_pct'] ?? 0));
            $status = $pr['calculated_status'] ?? $pr['project_status'] ?? 'Planning';
            $isPhaseBased = ($pr['workflow_version'] ?? '') === 'phase_based';
            
            $statusClass = 'status-planning';
            if ($status === 'On Track') $statusClass = 'status-ontrack';
            elseif ($status === 'At Risk') $statusClass = 'status-atrisk';
            elseif ($status === 'Delayed') $statusClass = 'status-delayed';
            elseif ($status === 'Completed') $statusClass = 'status-completed';

            $detailUrl = ($isPhaseBased) 
                ? $base_url . '/con-project-timeline/' . $pr['id'] 
                : $base_url . '/con-project/' . $pr['id'];

            $budgetDetailUrl = !empty($pr['budget_id'])
                ? $base_url . '/con-estimate-show/' . $pr['budget_id']
                : $detailUrl;

            $budgetPct = $pr['total_budget'] > 0 ? round(($pr['total_spent'] / $pr['total_budget']) * 100) : 0;

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
                if ($currentPhaseName === 'N/A') {
                    $lastPhase = end($pr['phases']);
                    $currentPhaseName = $lastPhase['phase_name'];
                    $currentPhaseIdx = $totalPhases;
                }
                $currentPhaseSub = "Phase " . $currentPhaseIdx . " of " . $totalPhases;
            }
            
            $colors = ['#4f46e5', '#0891b2', '#0d9488', '#ea580c', '#e11d48', '#2563eb'];
            $gradColor = $colors[$pr['id'] % count($colors)];
        ?>
        <div class="mobile-project-card p-3 mb-3 rounded-3 border">
           <div class="d-flex align-items-center gap-3 mb-3">
               <?php if (!empty($pr['image'])): ?>
                   <div style="width: 44px; height: 44px; border-radius: 8px; overflow: hidden; flex-shrink: 0; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                       <img src="<?= getImage('projects', $pr['image']) ?>" alt="<?= htmlspecialchars($pr['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                   </div>
               <?php else: ?>
                   <div style="width: 44px; height: 44px; border-radius: 8px; background: linear-gradient(135deg, <?= $gradColor ?>, <?= $gradColor ?>dd); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                       <i class="fa-solid fa-building"></i>
                   </div>
               <?php endif; ?>
               
               <div class="flex-grow-1">
                   <a href="<?= $detailUrl ?>" style="font-size: 14px; font-weight: 700; color: var(--clr-common-black, #0f172a); text-decoration: none; display: block;"><?= htmlspecialchars($pr['name']) ?></a>
                   <div style="font-size: 11px; color: var(--clr-text-muted, #64748b); margin-top: 2px; display: flex; align-items: center; gap: 4px;">
                       <i class="fa-solid fa-location-dot" style="font-size: 10px;"></i>
                       <?= htmlspecialchars($pr['location_name'] ?: 'N/A') ?>
                   </div>
               </div>
               
               <span class="status-pill <?= $statusClass ?>" style="align-self: flex-start;">
                   <span class="status-pill-dot"></span>
                   <?= $status ?>
               </span>
           </div>

           <div class="row g-2 mb-3 p-2 rounded-2" style="font-size: 12px; margin-left:0; margin-right:0; border: 1px solid var(--clr-bg-body, #f1f5f9);">
               <div class="col-6">
                   <span class="text-muted d-block" style="font-size: 9px; font-weight: 700; text-transform: uppercase;">Current Phase</span>
                   <span class="fw-bold text-dark"><?= htmlspecialchars($currentPhaseName) ?></span>
                   <div class="text-muted" style="font-size: 10px;"><?= $currentPhaseSub ?></div>
               </div>
               <div class="col-6">
                   <span class="text-muted d-block" style="font-size: 9px; font-weight: 700; text-transform: uppercase;">Handover Target</span>
                   <span class="fw-bold text-dark"><?= $pr['expected_handover_date'] ? date('M Y', strtotime($pr['expected_handover_date'])) : 'N/A' ?></span>
                   <div class="text-muted" style="font-size: 10px;">
                       <?php if ($pr['expected_handover_date']):
                           $diff = time() - strtotime($pr['expected_handover_date']);
                           $months = abs(round($diff / (60 * 60 * 24 * 30)));
                           echo $diff < 0 ? "In $months months" : "$months months ago";
                       else:
                           echo 'Timeline unset';
                       endif; ?>
                   </div>
               </div>
           </div>

           <!-- Planned vs Actual Progress (Mobile) -->
           <div class="mb-3 prog-compare-wrap p-2 rounded-2" style="border: 1px solid var(--clr-bg-body, #f1f5f9);">
               <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                   <span style="font-size: 10px; font-weight: 700; color: var(--text-muted, #94a3b8); min-width: 44px;">Planned</span>
                   <div class="prog-bar-container" style="height: 6px; flex: 1;">
                       <div class="prog-bar-fill-planned" style="width: <?= min(100, $pr['planned_progress_pct']) ?>%; height: 100%; background: #6366f1; opacity: 0.85;"></div>
                   </div>
                   <span style="font-size: 11px; font-weight: 800; min-width: 44px; text-align: right;">
                       <?= cpFmtPct($pr['planned_progress_pct']) ?>
                       <span style="font-size: 10px; cursor: pointer; color: var(--text-muted, #94a3b8);" data-bs-toggle="popover" data-bs-content="<?= htmlspecialchars($plaPopoverContent) ?>">ℹ️</span>
                   </span>
               </div>
               <div class="d-flex align-items-center justify-content-between gap-2">
                   <span style="font-size: 10px; font-weight: 700; color: var(--text-muted, #94a3b8); min-width: 44px;">Actual</span>
                   <div class="prog-bar-container" style="height: 6px; flex: 1;">
                       <div class="prog-bar-fill-actual" style="width: <?= min(100, $pr['progress_pct']) ?>%; height: 100%; background: #198754;"></div>
                   </div>
                   <span style="font-size: 11px; font-weight: 800; min-width: 44px; text-align: right;">
                       <?= cpFmtPct($pr['progress_pct']) ?>
                       <span style="font-size: 10px; cursor: pointer; color: var(--text-muted, #94a3b8);" data-bs-toggle="popover" data-bs-content="<?= htmlspecialchars($actPopoverContent) ?>">ℹ️</span>
                   </span>
               </div>
           </div>

           <!-- Budget Usage (Mobile) -->
           <div class="mb-3 p-2 rounded-2" style="border: 1px solid var(--clr-bg-body, #f1f5f9);">
               <div class="d-flex justify-content-between align-items-center mb-1">
                   <span class="text-muted" style="font-size: 9px; font-weight: 700; text-transform: uppercase;">Budget Spent</span>
                   <span class="fw-bold text-dark" style="font-size:12px;"><?= cpFmt($pr['total_spent']) ?></span>
               </div>
               <div class="d-flex align-items-center gap-2">
                   <div class="prog-bar-container" style="height: 4px; flex: 1;">
                       <div class="prog-bar-fill-actual" style="width: <?= min(100, $budgetPct) ?>%; height: 100%; background: <?= $budgetPct > 90 ? '#ef4444' : '#198754' ?>;"></div>
                   </div>
                   <span style="font-size: 10px; font-weight: 700; color: var(--clr-text-muted, #64748b); min-width: 28px; text-align: right;"><?= $budgetPct ?>%</span>
               </div>
           </div>

           <!-- Action Buttons (Mobile) -->
           <div class="d-flex gap-2">
               <?php if (!empty($pr['phases'])): ?>
                   <button class="btn btn-outline-primary flex-fill" style="font-size:12px; font-weight:600;" 
                           onclick="togglePhaseItems('phases-breakdown-mb-<?= $pr['id'] ?>', this.querySelector('i'))">
                       <i class="fa-solid fa-eye me-1"></i> Phase Breakdown
                   </button>
               <?php endif; ?>
               <a href="<?= $budgetDetailUrl ?>" class="btn btn-primary" style="font-size:12px; font-weight:600; padding: 6px 12px;">
                   <i class="fa-solid fa-arrow-up-right-from-square"></i> Details
               </a>
           </div>

           <!-- Collapsible Phases Breakdown (Mobile) -->
           <?php if (!empty($pr['phases'])): ?>
               <div id="phases-breakdown-mb-<?= $pr['id'] ?>" class="phase-items-container mt-3 pt-3 border-top" style="display: none; flex-direction: column; gap: 12px;">
                   <div style="font-size: 10.5px; font-weight: 800; text-transform: uppercase; color: var(--clr-text-muted, #64748b); letter-spacing: 0.5px; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
                       <i class="fa-solid fa-folder-open text-primary"></i> Phase Breakdown
                   </div>
                   
                   <?php foreach ($pr['phases'] as $phase): 
                       $phWeight = (float)$phase['phase_weight'];
                       $phaseActualProgress = (float)$phase['phase_actual_pct'];
                       $phaseContribution = (float)$phase['phase_contribution'];
                       
                       $totalApprovedVal = (float)$phase['total_approved_qty'];
                       $totalBOQVal = (float)$phase['total_boq'];
                       
                       $phaseProgressTooltip = "<strong>ℹ️ ফেজের নিজস্ব অগ্রগতি:</strong><br/>"
                                             . "হিসাব: (" . number_format($totalApprovedVal) . " CFT ÷ " . number_format($totalBOQVal) . " CFT) × ১০০ = " . number_format(round($phaseActualProgress, 1), 1) . "%";
                       
                       $phaseContributionTooltip = "<strong>ℹ️ প্রজেক্টে এই ফেজের অবদান:</strong><br/>"
                                                 . "হিসাব: " . number_format($phaseActualProgress, 2) . "% × (" . number_format($phWeight, 1) . " ÷ ১০০) = " . number_format($phaseContribution, 2) . "%";
                   ?>
                       <div class="border rounded-2 p-2" style="background: rgba(0,0,0,0.01); border-color: var(--clr-bg-body, #f1f5f9) !important;">
                           <div class="d-flex justify-content-between align-items-center mb-1">
                               <div style="font-size: 12px; font-weight: 700; color: var(--clr-common-black, #1e293b); display: flex; align-items: center; gap: 6px;">
                                   <?php if (!empty($phase['items'])): ?>
                                       <i class="fa-solid fa-chevron-right phase-toggle-icon" 
                                          onclick="togglePhaseItems('subitems-mb-<?= $pr['id'] ?>-<?= $phase['phase_id'] ?>', this)" 
                                          style="cursor: pointer; font-size: 9px; width: 12px; color: #198754; transition: transform 0.15s; display: inline-block;"></i>
                                   <?php else: ?>
                                       <span style="width: 12px; display: inline-block;"></span>
                                   <?php endif; ?>
                                   <span style="<?php if (!empty($phase['items'])): ?>cursor: pointer;<?php endif; ?>" 
                                         onclick="<?php if (!empty($phase['items'])): ?>togglePhaseItems('subitems-mb-<?= $pr['id'] ?>-<?= $phase['phase_id'] ?>', this.previousElementSibling)<?php endif; ?>">
                                       <?= htmlspecialchars($phase['phase_name']) ?>
                                   </span>
                               </div>
                               <span class="badge bg-secondary-transparent" style="font-size:9.5px;"><?= number_format($phaseActualProgress, 1) ?>%</span>
                           </div>
                           
                           <div style="font-size: 9px; font-weight: 700; display: flex; justify-content: space-between; margin-bottom: 3px;">
                               <span class="text-muted">Phase Weight: <?= number_format($phWeight, 1) ?>%</span>
                               <span class="text-muted" style="display: flex; align-items: center; gap: 4px;">
                                   Contrib: <?= number_format($phaseContribution, 2) ?>%
                                   <span style="font-size: 9px; cursor: pointer;" data-bs-toggle="popover" data-bs-content="<?= htmlspecialchars($phaseContributionTooltip) ?>">ℹ️</span>
                               </span>
                           </div>
                           
                           <div class="prog-bar-container" style="height: 5px; position: relative;">
                               <div class="prog-bar-fill-planned" style="width: <?= $phWeight ?>%; height: 100%; background: #6366f1; opacity: 0.15; position: absolute; left: 0;"></div>
                               <div class="prog-bar-fill-actual" style="width: <?= $phaseContribution ?>%; height: 100%; background: <?= $phaseActualProgress >= 100 ? '#198754' : '#f59e0b' ?>; position: absolute; left: 0;"></div>
                           </div>
                           
                           <!-- Nested Sub-items Loop (Mobile) -->
                           <?php if (!empty($phase['items'])): ?>
                               <div id="subitems-mb-<?= $pr['id'] ?>-<?= $phase['phase_id'] ?>" class="phase-items-container mt-2 pt-2 border-top" style="display: none; flex-direction: column; gap: 8px; padding-left: 8px; border-color: var(--clr-bg-body, #f1f5f9) !important;">
                                   <?php foreach ($phase['items'] as $item): 
                                       $itemWeight = (float)$item['item_weight'];
                                       $itemActualPct = (float)$item['item_actual_pct'];
                                       $itemProjWeight = ($itemWeight / 100) * $phWeight;
                                       $itemActWeightVal = ($itemActualPct / 100) * $itemProjWeight;
                                   ?>
                                       <div class="bg-white p-2 rounded border" style="font-size:11px; border-color: var(--clr-bg-body, #f1f5f9) !important;">
                                           <div class="fw-semibold text-dark mb-1" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                               ↳ <?= htmlspecialchars($item['item_name']) ?>
                                           </div>
                                           <div class="d-flex justify-content-between mb-1 text-muted" style="font-size: 9.5px;">
                                               <span>Weight: <?= number_format($itemProjWeight, 2) ?>%</span>
                                               <span class="fw-bold text-dark"><?= number_format($itemActualPct, 1) ?>% complete</span>
                                           </div>
                                           <div class="prog-bar-container" style="height: 4px; position: relative;">
                                               <div class="prog-bar-fill-planned" style="width: <?= $itemProjWeight ?>%; height: 100%; background: #6366f1; opacity: 0.15; position: absolute; left: 0;"></div>
                                               <div class="prog-bar-fill-actual" style="width: <?= $itemActWeightVal ?>%; height: 100%; background: <?= $itemActualPct >= 100 ? '#198754' : '#f59e0b' ?>; position: absolute; left: 0;"></div>
                                           </div>
                                           <?php if (!empty($item['qty_usage_text'])): ?>
                                               <div class="text-muted mt-1 text-end" style="font-size: 9px; font-weight: 500;"><?= htmlspecialchars($item['qty_usage_text']) ?></div>
                                           <?php endif; ?>
                                       </div>
                                   <?php endforeach; ?>
                               </div>
                           <?php endif; ?>
                       </div>
                   <?php endforeach; ?>
               </div>
           <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- ROW 2: RECENT TRANSACTIONS & REPORTS -->
<div class="row mt-3">
  <!-- Recent Material Received -->
  <div class="col-xl-4 col-lg-4 col-md-12 mb-3">
    <div class="rd-panel h-100 mb-0">
      <div class="rd-panel-title">
        <div class="rd-dot" style="background:var(--se-green)"></div>
        Recent Material Received (GRN)
        <a href="<?= $base_url ?>/con-material-receivings" style="font-size:10px;margin-left:auto;text-decoration:none">View All <i class="fa-solid fa-arrow-right ms-1"></i></a>
      </div>
      <?php if (empty($recentReceives)): ?>
         <div class="empty-msg">No materials received recently.</div>
      <?php else: ?>
         <div class="table-responsive">
            <table class="rd-table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentReceives as $mr): ?>
                        <tr>
                            <td><span class="fw-semibold text-dark"><?= htmlspecialchars($mr['project_name'] ?? '—') ?></span></td>
                            <td class="text-muted" style="font-size:11.5px"><?= !empty($mr['receiving_date']) ? date('d M Y', strtotime($mr['receiving_date'])) : '—' ?></td>
                            <td>
                                <?php 
                                $badge = 'bg-warning-transparent';
                                if (($mr['overall_status'] ?? '') === 'Stock In') $badge = 'bg-success-transparent';
                                if (($mr['overall_status'] ?? '') === 'Rejected') $badge = 'bg-danger-transparent';
                                ?>
                                <span class="badge <?= $badge ?>" style="font-size:9.5px;"><?= htmlspecialchars($mr['overall_status'] ?? '—') ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
         </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent Material Issues -->
  <div class="col-xl-4 col-lg-4 col-md-12 mb-3">
    <div class="rd-panel h-100 mb-0">
      <div class="rd-panel-title">
        <div class="rd-dot" style="background:var(--se-amber)"></div>
        Recent Material Issues
        <a href="<?= $base_url ?>/con-material-issues" style="font-size:10px;margin-left:auto;text-decoration:none">View All <i class="fa-solid fa-arrow-right ms-1"></i></a>
      </div>
      <?php if (empty($recentIssues)): ?>
         <div class="empty-msg">No materials issued recently.</div>
      <?php else: ?>
         <div class="table-responsive">
            <table class="rd-table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Material</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentIssues as $mi): ?>
                        <tr>
                            <td><span class="fw-semibold text-dark"><?= htmlspecialchars($mi['project_name'] ?? '—') ?></span></td>
                            <td style="font-size:12px"><?= htmlspecialchars($mi['material_name'] ?? '—') ?></td>
                            <td class="text-muted" style="font-size:11.5px"><?= !empty($mi['issue_date']) ? date('d M Y', strtotime($mi['issue_date'])) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
         </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent Weekly Reports -->
  <div class="col-xl-4 col-lg-4 col-md-12 mb-3">
    <div class="rd-panel h-100 mb-0 d-flex flex-column justify-content-between">
      <div>
        <div class="rd-panel-title">
          <div class="rd-dot" style="background:var(--se-primary)"></div>
          Recent Weekly Reports
          <a href="<?= $base_url ?>/con-weekly-reports" style="font-size:10px;margin-left:auto;text-decoration:none">View All <i class="fa-solid fa-arrow-right ms-1"></i></a>
        </div>
        <div class="text-center py-3">
          <div class="avatar avatar-xxl rounded-circle mb-3 mx-auto" style="width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; font-size: 28px; color: var(--se-primary); background-color: rgba(22, 163, 74, 0.12);">
              <i class="bi bi-file-earmark-bar-graph"></i>
          </div>
          <h6 class="fw-semibold mb-2 fs-14">Weekly Report Updates</h6>
          <p class="text-muted mb-0 fs-12 px-2" style="line-height: 1.5;">
              নিচের বাটনে ক্লিক করে প্রজেক্টের সাপ্তাহিক অগ্রগতি রিপোর্ট দেখুন এবং নতুন রিপোর্ট জমা দিন।
          </p>
        </div>
      </div>
      <div class="pt-3">
          <a href="<?= $base_url ?>/con-weekly-reports" class="btn btn-primary w-100 py-2" style="font-size:12.5px; font-weight:600;">
              View &amp; Submit Reports →
          </a>
      </div>
    </div>
  </div>
</div>

</div>
</div>

<?php ob_start(); ?>
(function() {
    // initialize Bootstrap 5 popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl, {
            html: true,
            trigger: 'hover'
        });
    });
})();

function togglePhaseItems(id, iconEl) {
    var el = document.getElementById(id);
    if (!el) return;
    if (el.style.display === 'none' || el.style.display === '') {
        el.style.display = (el.tagName === 'TR') ? 'table-row' : 'flex';
        if (iconEl) {
            if (iconEl.classList.contains('fa-eye')) {
                iconEl.classList.remove('fa-eye');
                iconEl.classList.add('fa-eye-slash');
            } else if (iconEl.classList.contains('fa-chevron-right')) {
                iconEl.classList.remove('fa-chevron-right');
                iconEl.classList.add('fa-chevron-down');
            }
        }
    } else {
        el.style.display = 'none';
        if (iconEl) {
            if (iconEl.classList.contains('fa-eye-slash')) {
                iconEl.classList.remove('fa-eye-slash');
                iconEl.classList.add('fa-eye');
            } else if (iconEl.classList.contains('fa-chevron-down')) {
                iconEl.classList.remove('fa-chevron-down');
                iconEl.classList.add('fa-chevron-right');
            }
        }
    }
}
<?php $page_script = ob_get_clean(); ?>

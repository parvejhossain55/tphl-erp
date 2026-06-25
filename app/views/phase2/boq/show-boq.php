<?php
$base = $base_url;
$boq = $boq ?? [];
$items = $items ?? [];
$canApprove = $canApprove ?? false;
$canEdit = $canEdit ?? false;
$creatorInfo = $creatorInfo ?? [];
$totalBoqQty = $totalBoqQty ?? 0;
$totalOrdered = $totalOrdered ?? 0;
$totalReceived = $totalReceived ?? 0;
$totalRemaining = $totalRemaining ?? 0;
$overallConsumedPct = $overallConsumedPct ?? 0;

function boqFmt($n)
{
  $n = (float) $n;
  if ($n >= 10000000)
    return '৳' . number_format($n / 10000000, 2) . ' Cr';
  if ($n >= 100000)
    return '৳' . number_format($n / 100000, 2) . ' L';
  if ($n >= 1000)
    return '৳' . number_format($n / 1000, 1) . 'K';
  return '৳' . number_format($n, 2);
}

$grandTotal = array_sum(array_column($items, 'total_cost'));
$status = $boq['status'] ?? 'Draft';
$statusMap = [
  'Approved' => ['bg' => 'rgba(16,185,129,.2)', 'text' => '#059669', 'icon' => 'fa-check-circle'],
  'Rejected' => ['bg' => 'rgba(239,68,68,.2)', 'text' => '#dc2626', 'icon' => 'fa-times-circle'],
  'Submitted' => ['bg' => 'rgba(59,130,246,.2)', 'text' => '#2563eb', 'icon' => 'fa-paper-plane'],
  'Draft' => ['bg' => 'rgba(245,158,11,.2)', 'text' => '#d97706', 'icon' => 'fa-pencil-alt'],
];
$sc = $statusMap[$status] ?? $statusMap['Draft'];
?>
<style>
  :root {
    --boq-primary: #198754;
    --boq-dark: #0f5132;
    --boq-blue: #3b82f6;
    --boq-green: #10b981;
    --boq-amber: #f59e0b;
    --boq-red: #ef4444;
    --boq-purple: var(--clr-theme-secondary);
    --boq-indigo: var(--clr-theme-primary);
  }

  /* Hero */
  .boq-hero {
    background: linear-gradient(135deg, #0f5132 0%, #198754 55%, #20a86a 100%);
    border-radius: 18px;
    padding: 26px 30px;
    margin-bottom: 22px;
    position: relative;
    overflow: hidden;
  }

  .boq-hero::before {
    content: '';
    position: absolute;
    width: 280px;
    height: 280px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .04);
    top: -90px;
    right: -70px;
  }

  .boq-hero::after {
    content: '';
    position: absolute;
    width: 160px;
    height: 160px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .03);
    bottom: -50px;
    left: -30px;
  }

  .boq-hero-inner {
    position: relative;
    z-index: 2;
  }

  .boq-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255, 255, 255, .12);
    border: 1px solid rgba(255, 255, 255, .2);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: 1.4px;
    text-transform: uppercase;
    color: #fff;
    margin-bottom: 12px;
  }

  .boq-hero-title {
    font-size: 24px;
    font-weight: 900;
    color: #fff;
    margin: 0 0 5px;
  }

  .boq-hero-sub {
    font-size: 12.5px;
    color: rgba(255, 255, 255, .65);
    margin: 0;
  }

  .boq-btn-w {
    background: rgba(255, 255, 255, .14);
    border: 1px solid rgba(255, 255, 255, .28);
    color: #fff;
    border-radius: 10px;
    padding: 7px 18px;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background .18s;
  }

  .boq-btn-w:hover {
    background: rgba(255, 255, 255, .25);
    color: #fff;
    text-decoration: none;
  }

  /* KPI */
  .boq-kpi-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-bottom: 22px;
  }

  @media(max-width:992px) {
    .boq-kpi-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  .boq-kpi {
    border-radius: 14px;
    padding: 18px 20px;
    border: 1px solid rgba(0, 0, 0, .08);
    background: var(--card-bg, #fff);
    position: relative;
    overflow: hidden;
  }

  .boq-kpi-accent {
    position: absolute;
    left: 0;
    top: 0;
    width: 4px;
    height: 100%;
    border-radius: 14px 0 0 14px;
  }

  .boq-kpi-icon {
    width: 40px;
    height: 40px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    margin-bottom: 11px;
  }

  .boq-kpi-val {
    font-size: 20px;
    font-weight: 900;
    line-height: 1;
    margin-bottom: 3px;
  }

  .boq-kpi-lbl {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--text-muted);
  }

  /* Panel */
  .boq-panel {
    border-radius: 14px;
    padding: 22px;
    border: 1px solid rgba(0, 0, 0, .08);
    background: var(--card-bg, #fff);
    margin-bottom: 20px;
  }

  .boq-panel-title {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .boq-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
  }

  /* Table */
  .boq-table {
    width: 100%;
    border-collapse: collapse;
  }

  .boq-table th {
    font-size: 10px;
    letter-spacing: 1.3px;
    text-transform: uppercase;
    font-weight: 600;
    padding: 0 12px 10px;
    border-bottom: 2px solid rgba(0, 0, 0, .07);
    text-align: left;
  }

  .boq-table td {
    padding: 12px;
    border-bottom: 1px solid rgba(0, 0, 0, .05);
    font-size: 12.5px;
    vertical-align: middle;
  }

  .boq-table tr:last-child td {
    border-bottom: none;
  }

  .boq-table .tr {
    text-align: right;
  }

  /* Approval card */
  .boq-action-card {
    border-radius: 14px;
    padding: 22px;
    border: 2px solid rgba(245, 158, 11, .3);
    background: rgba(245, 158, 11, .04);
    margin-bottom: 20px;
  }

  .empty-state {
    text-align: center;
    padding: 32px 0;
    color: var(--text-muted);
  }

  .empty-state i {
    font-size: 32px;
    opacity: .25;
    display: block;
    margin-bottom: 8px;
  }

  @media print {

    .boq-hero .boq-btn-w,
    .boq-action-card,
    .app-sidebar,
    .sidebar,
    .app__header__area,
    .main-header-container,
    .main-footer {
      display: none !important;
    }

    .page__full-wrapper,
    .page__body-wrapper {
      margin: 0 !important;
      padding: 0 !important;
      width: 100% !important;
      max-width: 100% !important;
    }
  }
</style>

<!-- ══════════ HERO ══════════ -->
<div class="boq-hero">
  <div class="boq-hero-inner">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:14px">
      <div>
        <div class="boq-badge"><i class="fal fa-clipboard-list"></i>Bill of Quantities (BOQ)</div>
        <h4 class="boq-hero-title"><?= htmlspecialchars($boq['project_name'] ?? 'N/A') ?></h4>
        <p class="boq-hero-sub">
          <i class="fal fa-code-branch me-1"></i>Version <?= htmlspecialchars($boq['version'] ?? '1.0') ?>
          &nbsp;·&nbsp;
          <i
            class="fal fa-calendar me-1"></i><?= !empty($boq['created_at']) ? date('d M Y', strtotime($boq['created_at'])) : '—' ?>
          <?php if (!empty($boq['target_title'])): ?>
            &nbsp;·&nbsp;
            <i class="bi bi-bullseye me-1"></i>
            <span style="color:rgba(255,255,255,.85);font-weight:600"><?= htmlspecialchars($boq['target_title']) ?></span>
          <?php endif; ?>
          <?php if (!empty($boq['design_type_name'])): ?>
            &nbsp;·&nbsp;
            <i class="bi bi-layers me-1"></i>
            <span
              style="background:rgba(255,255,255,.15);padding:1px 8px;border-radius:8px;font-size:11px;font-weight:700">
              <?= htmlspecialchars($boq['design_type_name']) ?>
            </span>
          <?php endif; ?>
        </p>

        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px">
          <?php if ($canEdit): ?>
            <a href="<?= $base ?>/con-boq-edit/<?= $boq['id'] ?>" class="boq-btn-w">
              <i class="fal fa-pencil"></i>Edit BOQ
            </a>
          <?php endif; ?>
          <?php if ($status === 'Approved' && empty($linkedPr)): ?>
            <a href="<?= $base ?>/con-purchase-request-create?boq_id=<?= $boq['id'] ?>&project_id=<?= $boq['project_id'] ?>"
              style="background:linear-gradient(135deg,#d97706,#f59e0b);color:#fff;border-radius:10px;
                    padding:7px 18px;font-size:12px;font-weight:700;text-decoration:none;
                    display:inline-flex;align-items:center;gap:6px;border:none">
              <i class="fal fa-cart-plus"></i>Create Purchase Request
            </a>
          <?php endif; ?>
          <a href="<?= $base ?>/con-boq" class="boq-btn-w">
            <i class="fal fa-arrow-left"></i>Back to List
          </a>
          <button onclick="window.print()" class="boq-btn-w" style="border:none;cursor:pointer">
            <i class="fal fa-print"></i>Print
          </button>
        </div>
      </div>
      <!-- Status pill -->
      <div style="text-align:right">
        <div style="display:inline-flex;align-items:center;gap:7px;font-size:13px;font-weight:700;
          padding:8px 18px;border-radius:20px;background:#fff;color:<?= $sc['text'] ?>">
          <i class="fal <?= $sc['icon'] ?>"></i><?= $status ?>
        </div>
        <?php if ($status === 'Approved' && !empty($boq['approved_by_name'])): ?>
          <div style="font-size:11px;color:rgba(255,255,255,.65);margin-top:8px">
            <i class="fal fa-user-check me-1"></i>
            Approved by <strong style="color:#fff"><?= htmlspecialchars($boq['approved_by_name']) ?></strong><br>
            <?php if (!empty($boq['approved_by_role'])): ?>
              <span
                style="font-size:10px;font-style:italic;color:rgba(255,255,255,.5)"><?= htmlspecialchars($boq['approved_by_role']) ?></span><br>
            <?php endif; ?>
            <i
              class="fal fa-calendar me-1"></i><?= !empty($boq['approved_at']) ? date('d M Y', strtotime($boq['approved_at'])) : '' ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ══════════ NEXT ACTION TRACKER ══════════ -->
<?php if (!empty($nextStepInfo)): ?>
<div class="boq-panel" style="border: 1px solid #3b82f6; background: rgba(59, 130, 246, 0.03); margin-bottom: 22px; padding: 18px 22px; border-radius: 14px;">
  <div style="display: flex; align-items: flex-start; gap: 14px;">
    <div style="width: 32px; height: 32px; border-radius: 50%; background: #3b82f6; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #fff; box-shadow: 0 0 10px rgba(59, 130, 246, 0.4);">
      <i class="fal fa-arrow-alt-right-to-line" style="font-size: 14px;"></i>
    </div>
    <div>
      <div style="font-size: 11px; font-weight: 800; color: #3b82f6; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 4px;">
        <i class="fal fa-spinner-third fa-spin me-1"></i> Next Step / Stuck At
      </div>
      <div style="font-size: 15px; font-weight: 800; line-height: 1.4;">
        <?= htmlspecialchars($nextStepInfo) ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ══════════ KPI GRID ══════════ -->
<div class="boq-kpi-grid">
  <div class="boq-kpi">
    <div class="boq-kpi-accent" style="background:var(--boq-blue)"></div>
    <div class="boq-kpi-icon" style="background:rgba(59,130,246,.12);color:var(--boq-blue)">
      <i class="fal fa-boxes-stacked"></i>
    </div>
    <div class="boq-kpi-lbl">Total Items</div>
    <div class="boq-kpi-val" style="color:var(--boq-blue)"><?= count($items) ?></div>
  </div>
  <div class="boq-kpi">
    <div class="boq-kpi-accent" style="background:var(--boq-amber)"></div>
    <div class="boq-kpi-icon" style="background:rgba(245,158,11,.12);color:var(--boq-amber)">
      <i class="bi bi-cart-check"></i>
    </div>
    <div class="boq-kpi-lbl">Ordered Qty</div>
    <div class="boq-kpi-val" style="color:var(--boq-amber)"><?= number_format($totalOrdered, 2) ?></div>
  </div>
  <div class="boq-kpi">
    <div class="boq-kpi-accent"
      style="background:<?= $overallConsumedPct >= 90 ? 'var(--boq-red)' : ($overallConsumedPct >= 60 ? 'var(--boq-amber)' : 'var(--boq-green)') ?>">
    </div>
    <div class="boq-kpi-icon" style="background:rgba(16,185,129,.12);color:var(--boq-green)">
      <i class="bi bi-box-arrow-in-down"></i>
    </div>
    <div class="boq-kpi-lbl">Received / <?= $overallConsumedPct ?>% consumed</div>
    <div class="boq-kpi-val" style="color:var(--boq-green)"><?= number_format($totalReceived, 2) ?></div>
    <?php if ($totalOrdered > 0): ?>
      <div style="height:5px;background:rgba(0,0,0,.07);border-radius:3px;overflow:hidden;margin-top:6px">
        <div style="height:100%;border-radius:3px;width:<?= $overallConsumedPct ?>%;
        background:<?= $overallConsumedPct >= 90 ? '#ef4444' : ($overallConsumedPct >= 60 ? '#f59e0b' : '#10b981') ?>">
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($linkedPr)): ?>
  <!-- ══════════ PROCUREMENT & PAYMENT TRACKING DASHBOARD ══════════ -->
  <div class="boq-panel" style="border: 1px solid rgba(var(--clr-theme-primary-rgb), 0.25); background: linear-gradient(180deg, rgba(var(--clr-theme-primary-rgb), 0.02) 0%, rgba(255, 255, 255, 1) 100%); margin-bottom: 22px;">
    <div class="boq-panel-title" style="color: var(--clr-theme-primary); display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(var(--clr-theme-primary-rgb), 0.15); padding-bottom: 12px; margin-bottom: 20px;">
      <div style="display: flex; align-items: center; gap: 8px; font-weight: 800; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">
        <div class="boq-dot" style="background: var(--clr-theme-primary); box-shadow: 0 0 8px var(--clr-theme-primary);"></div>
        <i class="fal fa-truck-ramp-box" style="font-size: 16px;"></i> Procurement &amp; Payment Pipeline
      </div>
      <span style="font-size: 10px; font-weight: 800; background: rgba(var(--clr-theme-primary-rgb), 0.12); color: var(--clr-theme-primary); padding: 4px 12px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px;">
        Already PR Created
      </span>
    </div>

    <!-- Main Grid: PR Info, PO Info, Payment Info -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 20px;">
      
      <!-- Card 1: Purchase Request -->
      <div style="background: rgba(249, 250, 251, 0.7); border: 1px solid rgba(0,0,0,0.04); border-radius: 12px; padding: 16px; position: relative;">
        <div style="position: absolute; right: 16px; top: 16px; font-size: 20px; color: rgba(59, 130, 246, 0.15);"><i class="fal fa-file-invoice"></i></div>
        <div style="font-size: 10px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 8px;">Purchase Request Details</div>
        <div style="margin-bottom: 12px;">
          <a href="<?= $base ?>/con-purchase-request/<?= $linkedPr['pr_id'] ?>" style="font-size: 15px; font-weight: 800; color: #2563eb; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
            PR-<?= $linkedPr['pr_id'] ?> <i class="fal fa-external-link" style="font-size: 11px;"></i>
          </a>
        </div>
        <div style="font-size: 12.5px; margin-bottom: 6px; color: #374151;">
          <span style="color: #6b7280;">Assigned Executive:</span> <strong style="color: #111827;"><?= htmlspecialchars($linkedPr['executive_name'] ?? 'Not Assigned Yet') ?></strong>
        </div>
        <div style="font-size: 12.5px; margin-bottom: 6px; color: #374151; display: flex; align-items: center; gap: 6px;">
          <span style="color: #6b7280;">Stage/Status:</span>
          <?php
            $prBadgeColor = match($linkedPr['pr_status']) {
              'Approved' => 'background: rgba(16, 185, 129, 0.1); color: #059669;',
              'PO Issued' => 'background: rgba(59, 130, 246, 0.1); color: #2563eb;',
              'Rejected' => 'background: rgba(239, 68, 68, 0.1); color: #dc2626;',
              'Submitted' => 'background: rgba(6, 182, 212, 0.1); color: #0891b2;',
              default => 'background: rgba(245, 158, 11, 0.1); color: #d97706;',
            };
          ?>
          <span style="font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 6px; <?= $prBadgeColor ?>">
            <?= $linkedPr['pr_status'] ?>
          </span>
        </div>
        <div style="font-size: 11px; color: #9ca3af; margin-top: 12px;">
          Created: <?= date('d M Y, h:i A', strtotime($linkedPr['pr_created_at'])) ?>
        </div>
      </div>

      <!-- Card 2: Purchase Order -->
      <div style="background: rgba(249, 250, 251, 0.7); border: 1px solid rgba(0,0,0,0.04); border-radius: 12px; padding: 16px; position: relative;">
        <div style="position: absolute; right: 16px; top: 16px; font-size: 20px; color: rgba(16, 185, 129, 0.15);"><i class="fal fa-file-signature"></i></div>
        <div style="font-size: 10px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 8px;">Purchase Order Status</div>
        <?php if ($linkedPo): ?>
          <div style="margin-bottom: 12px;">
            <a href="<?= $base ?>/con-purchase-order/<?= $linkedPo['po_id'] ?>" style="font-size: 15px; font-weight: 800; color: #059669; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
              PO-<?= $linkedPo['po_id'] ?> <i class="fal fa-external-link" style="font-size: 11px;"></i>
            </a>
          </div>
          <div style="font-size: 12.5px; margin-bottom: 6px; color: #374151;">
            <span style="color: #6b7280;">Selected Vendor:</span> <strong style="color: #111827;"><?= !empty($vendors) ? htmlspecialchars(implode(', ', $vendors)) : 'No Vendor Assigned' ?></strong>
          </div>
          <div style="font-size: 12.5px; margin-bottom: 6px; color: #374151; display: flex; align-items: center; gap: 6px;">
            <span style="color: #6b7280;">PO Status:</span>
            <?php
              $poBadgeColor = match($linkedPo['po_status']) {
                'Approved' => 'background: rgba(16, 185, 129, 0.1); color: #059669;',
                'Cancelled' => 'background: rgba(239, 68, 68, 0.1); color: #dc2626;',
                'Issued' => 'background: rgba(59, 130, 246, 0.1); color: #2563eb;',
                default => 'background: rgba(245, 158, 11, 0.1); color: #d97706;',
              };
            ?>
            <span style="font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 6px; <?= $poBadgeColor ?>">
              <?= $linkedPo['po_status'] ?>
            </span>
          </div>
          <div style="font-size: 11px; color: #9ca3af; margin-top: 12px;">
            PO Date: <?= date('d M Y', strtotime($linkedPo['po_date'])) ?>
          </div>
        <?php else: ?>
          <div style="display: flex; flex-direction: column; justify-content: center; height: calc(100% - 20px); text-align: center; color: #9ca3af; padding: 12px 0;">
            <i class="fal fa-file-slash" style="font-size: 24px; margin-bottom: 6px; opacity: 0.5;"></i>
            <span style="font-size: 12px;">No Purchase Order Generated Yet</span>
          </div>
        <?php endif; ?>
      </div>

      <!-- Card 3: Vendor Payments & Accounts -->
      <div style="background: rgba(249, 250, 251, 0.7); border: 1px solid rgba(0,0,0,0.04); border-radius: 12px; padding: 16px; position: relative;">
        <div style="position: absolute; right: 16px; top: 16px; font-size: 20px; color: rgba(245, 158, 11, 0.15);"><i class="fal fa-money-bill-wave"></i></div>
        <div style="font-size: 10px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 8px;">Payments &amp; Accounts Summary</div>
        <?php if ($linkedPo): ?>
          <div style="display: flex; flex-direction: column; gap: 6px;">
            <div style="font-size: 12.5px; color: #374151; display: flex; justify-content: space-between;">
              <span style="color: #6b7280;">Total Paid:</span>
              <strong style="color: #059669; font-weight: 700;">৳<?= number_format($totalPaid, 2) ?></strong>
            </div>
            <div style="font-size: 12.5px; color: #374151; display: flex; justify-content: space-between; border-top: 1px dashed rgba(0,0,0,0.06); padding-top: 6px;">
              <span style="color: #6b7280;">Payment Requests:</span>
              <strong style="color: #111827;"><?= count($vprDetails) ?> Request(s)</strong>
            </div>
            <div style="margin-top: 6px; font-size: 11px;">
              <?php if ($totalPaid > 0): ?>
                <span class="badge bg-success" style="font-size: 10px; padding: 4px 8px;"><i class="bi bi-wallet2 me-1"></i> Paid</span>
              <?php else: ?>
                <span class="badge bg-warning " style="font-size: 10px; padding: 4px 8px;"><i class="bi bi-exclamation-circle me-1"></i> No Payments Disbursed</span>
              <?php endif; ?>
            </div>
          </div>
        <?php else: ?>
          <div style="display: flex; flex-direction: column; justify-content: center; height: calc(100% - 20px); text-align: center; color: #9ca3af; padding: 12px 0;">
            <i class="fal fa-money-bill-slash" style="font-size: 24px; margin-bottom: 6px; opacity: 0.5;"></i>
            <span style="font-size: 12px;">Waiting for PO approval to request payment</span>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Bottom Stage Status & Timeline -->
    <div style="background: rgba(var(--clr-theme-primary-rgb), 0.04); border: 1px solid rgba(var(--clr-theme-primary-rgb), 0.12); border-radius: 10px; padding: 14px 16px;">
      <div style="display: flex; align-items: flex-start; gap: 12px;">
        <div style="width: 24px; height: 24px; border-radius: 50%; background: var(--clr-theme-primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #fff; animation: pulse 2s infinite;">
          <i class="fal fa-spinner fa-spin" style="font-size: 11px;"></i>
        </div>
        <div>
          <div style="font-size: 11px; font-weight: 700; color: var(--clr-theme-primary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px;">
            Next Action / Stuck At
          </div>
          <div style="font-size: 13.5px; font-weight: 700; ">
            <?= htmlspecialchars($nextStepInfo) ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <style>
    @keyframes pulse {
      0% { box-shadow: 0 0 0 0 rgba(var(--clr-theme-primary-rgb), 0.4); }
      70% { box-shadow: 0 0 0 8px rgba(var(--clr-theme-primary-rgb), 0); }
      100% { box-shadow: 0 0 0 0 rgba(var(--clr-theme-primary-rgb), 0); }
    }
  </style>
<?php endif; ?>

<!-- ══════════ APPROVAL ACTION (only PM, not creator) ══════════ -->
<?php if ($canApprove && in_array($status, ['Draft', 'Submitted'])): ?>
  <div class="boq-action-card">

    <!-- Creator Info Banner -->
    <?php if (!empty($creatorInfo)): ?>
      <div style="display:flex;align-items:center;gap:14px;padding:12px 16px;border-radius:10px;
    background:rgba(var(--clr-theme-primary-rgb),.06);border:1px solid rgba(var(--clr-theme-primary-rgb),.15);margin-bottom:16px">
        <div style="width:38px;height:38px;border-radius:50%;background:rgba(var(--clr-theme-primary-rgb),.12);
      display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <i class="fal fa-user" style="color:var(--clr-theme-primary);font-size:16px"></i>
        </div>
        <div>
          <div style="font-size:13px;font-weight:800;color:var(--clr-theme-primary)">
            <?= htmlspecialchars($creatorInfo['full_name'] ?? 'Unknown') ?>
          </div>
          <div style="font-size:11px;color:var(--text-muted)">
            <?= htmlspecialchars($creatorInfo['designation'] ?: 'Site Engineer') ?>
            &nbsp;·&nbsp;
            <i class="fal fa-calendar me-1"></i>
            Submitted <?= !empty($boq['created_at']) ? date('d M Y, h:i A', strtotime($boq['created_at'])) : '—' ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="boq-panel-title" style="margin-bottom:14px">
      <div class="boq-dot" style="background:var(--boq-amber)"></div>
      BOQ Approval — Your Action Required
    </div>
    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
      <form action="<?= $base ?>/con-boq-approve/<?= $boq['id'] ?>" method="POST">
        <input type="hidden" name="status" value="Approved">
        <button type="submit" style="background:linear-gradient(135deg,#059669,#10b981);color:#fff;border:none;
          border-radius:10px;padding:8px 20px;font-size:12px;font-weight:700;cursor:pointer"
          onclick="return confirm('Approve this BOQ? This cannot be undone.')">
          <i class="fal fa-check-circle me-2"></i>Approve BOQ
        </button>
      </form>
      <button type="button" style="background:rgba(239,68,68,.1);color:#dc2626;border:1px solid rgba(239,68,68,.3);
        border-radius:10px;padding:8px 20px;font-size:12px;font-weight:700;cursor:pointer" data-bs-toggle="modal"
        data-bs-target="#rejectBoqModal">
        <i class="fal fa-times-circle me-2"></i>Reject BOQ
      </button>
      <span style="font-size:11px;color:var(--text-muted)">
        <i class="fal fa-info-circle me-1"></i>Once approved, items cannot be edited.
      </span>
    </div>
  </div>

  <!-- Reject Modal -->
  <div class="modal fade" id="rejectBoqModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content" style="border-radius:16px;border:none">
        <form action="<?= $base ?>/con-boq-approve/<?= $boq['id'] ?>" method="POST">
          <input type="hidden" name="status" value="Rejected">
          <div class="modal-header" style="border-bottom:1px solid rgba(0,0,0,.07)">
            <h5 class="modal-title" style="font-size:14px;font-weight:800">
              <i class="fal fa-exclamation-triangle text-danger me-2"></i>Reject BOQ
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p style="font-size:12.5px;color:var(--text-muted)">
              Are you sure you want to reject the BOQ for
              <strong><?= htmlspecialchars($boq['project_name'] ?? '') ?></strong>?
            </p>
          </div>
          <div class="modal-footer" style="border-top:1px solid rgba(0,0,0,.07)">
            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-sm btn-danger px-4" style="border-radius:10px">Confirm Reject</button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- ══════════ SE: Submit for Review (only for SE on Draft) ══════════ -->
<?php
if (session_status() === PHP_SESSION_NONE)
  session_start();
$viewerRole = (int) ($_SESSION['user']['role_id'] ?? 0);
if ($viewerRole === 12 && $status === 'Draft'):
  ?>
  <div class="boq-panel" style="border-color:rgba(59,130,246,.3);background:rgba(59,130,246,.03);margin-bottom:20px">
    <div class="boq-panel-title" style="margin-bottom:14px">
      <div class="boq-dot" style="background:var(--boq-blue)"></div>
      Submit for PM Approval
    </div>
    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
      <form action="<?= $base ?>/con-boq-approve/<?= $boq['id'] ?>" method="POST">
        <input type="hidden" name="status" value="Submitted">
        <button type="submit" style="background:linear-gradient(135deg,#1e40af,#3b82f6);color:#fff;border:none;
          border-radius:10px;padding:8px 20px;font-size:12px;font-weight:700;cursor:pointer"
          onclick="return confirm('Submit this BOQ for PM approval?')">
          <i class="fal fa-paper-plane me-2"></i>Submit for Approval
        </button>
      </form>
      <span style="font-size:11px;color:var(--text-muted)">
        <i class="fal fa-info-circle me-1"></i>The Project Manager will review and approve this BOQ.
      </span>
    </div>
  </div>
<?php endif; ?>

<!-- ══════════ ITEMS TABLE ══════════ -->
<div class="boq-panel">
  <div class="boq-panel-title">
    <div class="boq-dot" style="background:var(--boq-green)"></div>
    Bill of Quantities — Material Items &amp; Consumption Tracker
    <span style="margin-left:auto;font-size:9px;font-weight:700;padding:2px 8px;border-radius:10px;
      background:rgba(16,185,129,.12);color:var(--boq-green)"><?= count($items) ?> Items</span>
  </div>

  <?php if (empty($items)): ?>
    <div class="empty-state"><i class="fal fa-boxes-stacked"></i>No items added to this BOQ.</div>
  <?php else: ?>
    <div style="overflow-x:auto">
      <table class="boq-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Material</th>
            <th>Floor</th>
            <th>Unit</th>
            <th style="text-align:right">BOQ Qty</th>
            <th>Required Date</th>
            <th style="text-align:right">Ordered</th>
            <th style="text-align:right">Received</th>
            <th style="text-align:right">Remaining</th>
            <th style="min-width:100px">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $i => $item):
            $ordered = (float) ($item['ordered_qty'] ?? 0);
            $received = (float) ($item['received_qty'] ?? 0);
            $boqQty = (float) ($item['quantity'] ?? 0);
            $remaining = (float) ($item['remaining_qty'] ?? $boqQty);
            $consPct = $boqQty > 0 ? min(100, round($received / $boqQty * 100, 1)) : 0;
            $clr = $consPct >= 90 ? '#dc2626' : ($consPct >= 60 ? '#d97706' : '#059669');
            ?>
            <tr>
              <td style="font-size:11px;color:var(--text-muted)"><?= $i + 1 ?></td>
              <td>
                <span style="font-weight:700"><?= htmlspecialchars($item['material_name']) ?></span>
                <br><small class="text-muted">[Category:
                  <?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?>]</small>
              </td>
              <td>
                <?php
                if ($item['floor_type'] === 'Roof') {
                    echo 'Roof (Lvl ' . $item['floor_no'] . ')';
                } elseif ($item['floor_type'] === 'Ground_Floor') {
                    echo 'Ground Floor';
                } elseif ($item['floor_type'] === 'Basement') {
                    echo 'Basement ' . abs($item['floor_no']);
                } else {
                    if ($item['floor_no'] !== null && $item['floor_no'] !== '') {
                        $n = $item['floor_no'];
                        $s = ["th", "st", "nd", "rd"];
                        $v = $n % 100;
                        $ord = $n . ($s[($v - 20) % 10] ?? $s[$v] ?? $s[0]);
                        echo $ord . ' Floor';
                    } else {
                        echo 'General';
                    }
                }
                ?>
              </td>
              <td style="font-size:11.5px;color:var(--text-muted)"><?= htmlspecialchars($item['unit']) ?></td>
              <td style="text-align:right;font-weight:700;color:#2563eb"><?= number_format($boqQty, 3) ?></td>
              <td><?= (!empty($item['required_date']) && $item['required_date'] !== '0000-00-00') ? date('d M Y', strtotime($item['required_date'])) : '—' ?></td>
              <td style="text-align:right;color:#d97706;font-weight:600"><?= number_format($ordered, 3) ?></td>
              <td style="text-align:right;color:#059669;font-weight:700"><?= number_format($received, 3) ?></td>
              <td style="text-align:right;font-weight:800;color:<?= $remaining <= 0 ? '#dc2626' : '#374151' ?>">
                <?= number_format($remaining, 3) ?>
              </td>
              <td>
                <?php if ($boqQty > 0): ?>
                  <div style="font-size:9.5px;color:var(--text-muted);margin-bottom:2px"><?= $consPct ?>% received</div>
                  <div style="height:6px;background:rgba(0,0,0,.07);border-radius:4px;overflow:hidden">
                    <div style="height:100%;border-radius:4px;width:<?= $consPct ?>%;background:<?= $clr ?>"></div>
                  </div>
                <?php else: ?><span class="text-muted">—</span><?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr style="background:rgba(0,0,0,.03)">
            <td colspan="4" style="text-align:right;font-weight:800;padding:14px 12px;font-size:12px">
              TOTALS:
            </td>
            <td style="text-align:right;font-weight:900;font-size:14px;color:#2563eb;padding:14px 12px">
              <?= number_format($totalBoqQty, 3) ?>
            </td>
            <td></td>
            <td style="text-align:right;font-weight:800;color:#d97706;padding:14px 12px">
              <?= number_format($totalOrdered, 3) ?>
            </td>
            <td style="text-align:right;font-weight:800;color:#059669;padding:14px 12px">
              <?= number_format($totalReceived, 3) ?>
            </td>
            <td
              style="text-align:right;font-weight:900;color:<?= $totalRemaining <= 0 ? '#dc2626' : '#374151' ?>;padding:14px 12px">
              <?= number_format($totalRemaining, 3) ?>
            </td>
            <td style="padding:14px 12px">
              <?php if ($totalOrdered > 0): ?>
                <div style="font-size:9.5px;color:var(--text-muted);margin-bottom:3px">
                  <?= $overallConsumedPct ?>% overall
                </div>
                <div style="height:7px;background:rgba(0,0,0,.07);border-radius:4px;overflow:hidden">
                  <div
                    style="height:100%;border-radius:4px;width:<?= $overallConsumedPct ?>%;
                background:<?= $overallConsumedPct >= 90 ? '#ef4444' : ($overallConsumedPct >= 60 ? '#f59e0b' : '#10b981') ?>">
                  </div>
                </div>
              <?php endif; ?>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  <?php endif; ?>
</div>

<div style="height:30px"></div>
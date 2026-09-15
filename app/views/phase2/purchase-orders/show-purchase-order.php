<?php
$base_url     = $GLOBALS['base_url'];
$po           = $po ?? [];
$items        = $items ?? [];
$pendingItems = $pendingItems ?? [];
$qcApproved     = $qcApproved ?? false;
$mgmtApproved   = $mgmtApproved ?? false;
$fullyApproved  = $fullyApproved ?? false;
$canApproveQC   = $canApproveQC ?? false;
$canApproveMgmt = $canApproveMgmt ?? false;
$currentRoleId  = $currentRoleId ?? 0;
$linkedBoq      = $linkedBoq ?? null;

$statusBadge = [
  'Issued'             => ['bg' => 'rgba(59,130,246,.15)',  'color' => '#2563eb', 'label' => 'Issued'],
  'Approved'           => ['bg' => 'rgba(16,185,129,.15)',  'color' => '#059669', 'label' => 'Approved'],
  'Partially Received' => ['bg' => 'rgba(245,158,11,.15)',  'color' => '#d97706', 'label' => 'Partially Received'],
  'Fully Received'     => ['bg' => 'rgba(16,185,129,.15)',  'color' => '#059669', 'label' => 'Fully Received'],
  'Cancelled'          => ['bg' => 'rgba(239,68,68,.15)',   'color' => '#dc2626', 'label' => 'Cancelled'],
  'QC Approved'        => ['bg' => 'rgba(59,130,246,.15)',  'color' => '#2563eb', 'label' => 'QC Approved'],
  'Purchase Head Approved' => ['bg' => 'rgba(245,158,11,.15)', 'color' => '#d97706', 'label' => 'PH Approved'],
];
$sb = $statusBadge[$po['status']] ?? ['bg' => 'rgba(107,114,128,.15)', 'color' => '#6b7280', 'label' => $po['status']];
?>

<style>
  .po-hero {
    background: #198754;
    border-radius: 18px;
    padding: 24px 28px;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
  }

  .po-hero::before {
    content: '';
    position: absolute;
    width: 240px;
    height: 240px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .04);
    top: -70px;
    right: -50px;
  }

  .po-hero-inner {
    position: relative;
    z-index: 2;
  }

  .po-badge-top {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255, 255, 255, .12);
    border: 1px solid rgba(255, 255, 255, .2);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.4px;
    text-transform: uppercase;
    color: #fff;
    margin-bottom: 10px;
  }

  .po-btn-w {
    background: rgba(255, 255, 255, .14);
    border: 1px solid rgba(255, 255, 255, .28);
    color: #fff;
    border-radius: 10px;
    padding: 6px 16px;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background .18s;
  }

  .po-btn-w:hover {
    background: rgba(255, 255, 255, .25);
    color: #fff;
  }

  .po-panel {
    border-radius: 14px;
    padding: 20px;
    border: 1px solid rgba(0, 0, 0, .08);
    background: var(--card-bg, #fff);
    margin-bottom: 16px;
  }

  .po-panel-title {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .po-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
  }

  .po-apv-step {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 13px 15px;
    border-radius: 12px;
    margin-bottom: 8px;
  }

  .po-apv-step:last-child {
    margin-bottom: 0;
  }

  .po-apv-icon {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
  }
</style>

<!-- HERO -->
<div class="po-hero">
  <div class="po-hero-inner">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:14px">
      <div>
        <div class="po-badge-top"><i class="fal fa-file-contract"></i>Purchase Order</div>
        <h4 style="font-size:24px;font-weight:900;color:#fff;margin:0 0 4px">PO-<?= $po['id'] ?></h4>
        <p style="font-size:12px;color:rgba(255,255,255,.65);margin:0">
          <i class="fal fa-building me-1"></i><?= htmlspecialchars($po['project_name'] ?? '—') ?>
          &nbsp;·&nbsp;
          <i class="fal fa-calendar me-1"></i><?= !empty($po['order_date']) ? date('d M Y', strtotime($po['order_date'])) : '—' ?>
          &nbsp;·&nbsp;
          <i class="fal fa-user me-1"></i><?= htmlspecialchars($po['created_by_name'] ?? 'N/A') ?>
        </p>
        <div style="display:flex;gap:8px;margin-top:14px;flex-wrap:wrap">
          <button onclick="window.print()" class="po-btn-w"><i class="fal fa-print"></i>Print</button>
          <a href="<?= $base_url ?>/con-purchase-orders" class="po-btn-w"><i class="fal fa-arrow-left"></i>Back</a>
        </div>
      </div>
      <div style="text-align:right">
        <div style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;
          padding:7px 16px;border-radius:20px;background:<?= $sb['bg'] ?>;color:#fff">
          <i class="fal fa-circle"></i><?= $sb['label'] ?>
        </div>
        <div style="margin-top:8px;font-size:11px;color:rgba(255,255,255,.55)">
          <?php
          if ($fullyApproved) {
              echo '✅ Fully Approved';
          } elseif ($phNeeded && !$phApproved) {
              echo '⏳ Awaiting Purchase Head Approval';
          } elseif (!$qcApproved) {
              echo '⏳ Awaiting QC & Internal Audit Approval';
          } else {
              echo '⏳ Awaiting Operation Head Approval';
          }
          ?>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <!-- Left: Items + Delivery Tracker -->
  <div class="col-xl-8">

    <!-- PO Remarks -->
    <?php if (!empty($po['remarks'])): ?>
      <div class="po-panel" style="border-left: 4px solid #3b82f6;">
        <div class="po-panel-title">
          <div class="po-dot" style="background:#3b82f6"></div>
          PO Remarks
        </div>
        <div style="font-size:13px; color:var(--text-color, #4b5563); line-height:1.5; white-space:pre-wrap;"><?= htmlspecialchars($po['remarks']) ?></div>
      </div>
    <?php endif; ?>

    <!-- Order Items -->
    <div class="po-panel">
      <div class="po-panel-title">
        <div class="po-dot" style="background:#3b82f6"></div>
        Order Items
      </div>
      <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle mb-0">
          <thead class="table-dark">
            <tr>
              <th>#</th>
              <th>Material</th>
              <th>Vendor</th>
              <th>Unit</th>
              <th class="text-end">Qty</th>
              <th class="text-end">Unit Price</th>
              <th class="text-end">Total</th>
            </tr>
          </thead>
          <tbody>
            <?php $grandTotal = 0;
            foreach ($items as $i => $item):
              $lt = $item['quantity'] * $item['unit_price'];
              $grandTotal += $lt; ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td>
                  <span class="fw-bold"><?= htmlspecialchars($item['material_name']) ?></span>
                  <br><small class="text-muted">[Category: <?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?>]</small>
                </td>
                <td><?= htmlspecialchars($item['vendor_name']) ?></td>
                <td><?= htmlspecialchars($item['unit']) ?></td>
                <td class="text-end"><?= number_format($item['quantity'], 3) ?></td>
                <td class="text-end">৳ <?= number_format($item['unit_price'], 2) ?></td>
                <td class="text-end fw-bold">৳ <?= number_format($lt, 2) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot class="table-light">
            <tr>
              <th colspan="6" class="text-end py-3">TOTAL ORDER VALUE:</th>
              <th class="text-end py-3 fs-5 text-primary">৳ <?= number_format($grandTotal, 2) ?></th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- Delivery Tracker -->
    <div class="po-panel">
      <div class="po-panel-title">
        <div class="po-dot" style="background:#10b981"></div>
        Delivery Tracker
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Material</th>
              <th>Vendor</th>
              <th class="text-end">Ordered</th>
              <th class="text-end">Received</th>
              <th class="text-end">Pending</th>
              <th class="text-center">Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pendingItems as $pi): ?>
              <tr>
                <td>
                  <span class="fw-bold"><?= htmlspecialchars($pi['material_name']) ?></span>
                  <br><small class="text-muted">[Category: <?= htmlspecialchars($pi['category_name'] ?? 'Uncategorized') ?>]</small>
                </td>
                <td><?= htmlspecialchars($pi['vendor_name']) ?></td>
                <td class="text-end"><?= number_format($pi['quantity'], 3) ?></td>
                <td class="text-end text-success"><?= number_format($pi['received_total'], 3) ?></td>
                <td class="text-end <?= ($pi['pending_qty'] > 0) ? 'text-danger fw-bold' : 'text-success' ?>">
                  <?= number_format($pi['pending_qty'], 3) ?>
                </td>
                <td class="text-center">
                  <?php if ($pi['pending_qty'] <= 0): ?>
                    <span class="badge bg-success"><i class="bi bi-check-lg"></i> Done</span>
                  <?php elseif ($pi['received_total'] > 0): ?>
                    <span class="badge bg-warning text-dark">Partial</span>
                  <?php else: ?>
                    <span class="badge bg-danger">Pending</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Actions Section: GRN & Payment Disbursal -->
    <?php 
      $hasReceiving = $hasReceiving ?? false;
      $canProcessPayment = $canProcessPayment ?? false;
      $isViewOnlyRole = in_array((int)$currentRoleId, [20, 24, 25, 32], true);
      $canLogGrn = !$isViewOnlyRole;
      if ($currentRoleId == 12 && $hasReceiving) {
          $canLogGrn = false; // Site Engineer can only create GRN once per PO
      }
      $poTotalValue = $poTotalValue ?? 0;
      $poTotalPaid  = $poTotalPaid ?? 0;
      $poDueBalance = $poDueBalance ?? max(0, $poTotalValue - $poTotalPaid);
      $poPayments   = $poPayments ?? [];
    ?>

    <?php if ($fullyApproved && $po['status'] !== 'Cancelled'): ?>
      <div class="po-panel" style="border-color:rgba(16,185,129,.35);background:rgba(16,185,129,.04)">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
          <div>
            <div style="font-size:13px;font-weight:700;color:#059669"><i class="fal fa-check-circle me-2"></i>PO is Fully Approved</div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">All approvals completed. Material receiving and payment disbursal can now be processed.</div>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <?php if ($canLogGrn && $po['status'] !== 'Fully Received'): ?>
              <a href="<?= $base_url ?>/con-material-receiving-create/<?= $po['id'] ?>"
                style="background:linear-gradient(135deg,#059669,#10b981);color:#fff;border:none;border-radius:10px;padding:9px 18px;font-size:12px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:7px">
                <i class="fal fa-box-seam"></i> Log Material Receiving (GRN)
              </a>
            <?php endif; ?>
            
            <a href="<?= $base_url ?>/con-vendor-payment-create?po_id=<?= $po['id'] ?>&project_id=<?= $po['project_id'] ?? '' ?>&vendor_id=<?= $po['vendor_id'] ?? '' ?>"
              style="background:linear-gradient(135deg,#2563eb,#3b82f6);color:#fff;border:none;border-radius:10px;padding:9px 18px;font-size:12px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:7px">
              <i class="fal fa-wallet"></i> <?= ($poTotalPaid > 0) ? 'Pay / Disburse Balance' : 'Create Payment Request / Pay' ?>
            </a>
          </div>
        </div>
      </div>
    <?php elseif (!$fullyApproved && $po['status'] !== 'Cancelled'): ?>
      <div class="po-panel" style="border-color:rgba(245,158,11,.3);background:rgba(245,158,11,.03)">
        <div style="display:flex;align-items:center;gap:10px">
          <i class="fal fa-lock" style="color:#d97706;font-size:20px"></i>
          <div>
            <div style="font-size:13px;font-weight:700;color:#d97706">Material Receiving & Payment Locked</div>
            <div style="font-size:11px;color:var(--text-muted)">Waiting for all required department approvals before receiving and payment processing.</div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- PO Payment & Disbursal Status Panel -->
    <div class="po-panel" style="border-color:rgba(59,130,246,.25);background:#fff">
      <div class="po-panel-title d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-2">
          <div class="po-dot" style="background:#2563eb"></div>
          <span style="font-size:12px;font-weight:700;">Vendor Payment & Disbursal Status</span>
        </div>
        <div class="d-flex gap-3 text-end">
          <div><small class="text-muted d-block" style="font-size:10px">Total PO Value</small><strong class="text-dark" style="font-size:13px">৳ <?= number_format($poTotalValue, 2) ?></strong></div>
          <div><small class="text-muted d-block" style="font-size:10px">Total Paid</small><strong class="text-success" style="font-size:13px">৳ <?= number_format($poTotalPaid, 2) ?></strong></div>
          <div><small class="text-muted d-block" style="font-size:10px">Due Balance</small><strong class="text-danger" style="font-size:13px">৳ <?= number_format($poDueBalance, 2) ?></strong></div>
        </div>
      </div>

      <?php if (!empty($poPayments)): ?>
        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle mb-0" style="font-size:11.5px">
            <thead class="table-light">
              <tr>
                <th>VPR ID</th>
                <th>Type</th>
                <th>Created</th>
                <th class="text-end">Invoice Amount</th>
                <th class="text-end">Net Payable</th>
                <th class="text-end">Total Paid</th>
                <th class="text-center">Status</th>
                <th class="text-center">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($poPayments as $vp): ?>
                <?php 
                  $vpStatus = $vp['status'] ?? 'Draft';
                  $vpBadgeClass = match($vpStatus) {
                    'Approved' => 'bg-success',
                    'Fully_Paid', 'Paid' => 'bg-secondary',
                    'Partially_Paid' => 'bg-info text-white',
                    'QC Approved' => 'bg-primary',
                    'Rejected' => 'bg-danger',
                    default => 'bg-warning text-dark'
                  };
                ?>
                <tr>
                  <td class="fw-bold">VPR-<?= $vp['id'] ?></td>
                  <td><span class="badge bg-light text-dark border"><?= $vp['payment_type'] ?></span></td>
                  <td><?= !empty($vp['created_at']) ? date('d M Y', strtotime($vp['created_at'])) : '—' ?></td>
                  <td class="text-end">৳ <?= number_format($vp['invoice_amount'] ?? 0, 2) ?></td>
                  <td class="text-end fw-bold">৳ <?= number_format($vp['net_payable'] ?? 0, 2) ?></td>
                  <td class="text-end text-success fw-bold">৳ <?= number_format($vp['total_paid'] ?? 0, 2) ?></td>
                  <td class="text-center"><span class="badge <?= $vpBadgeClass ?>"><?= str_replace('_', ' ', $vpStatus) ?></span></td>
                  <td class="text-center">
                    <a href="<?= $base_url ?>/con-vendor-payment-show/<?= $vp['id'] ?>" class="btn btn-xs btn-outline-primary" style="font-size:11px;padding:2px 8px" title="View & Process Payment">
                      <i class="fal fa-eye me-1"></i> View / Pay
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="text-center py-2 text-muted" style="font-size:11.5px">
          <i class="fal fa-info-circle me-1"></i> No payment requests created for this PO yet.
          <?php if ($fullyApproved): ?>
            <a href="<?= $base_url ?>/con-vendor-payment-create?po_id=<?= $po['id'] ?>&project_id=<?= $po['project_id'] ?? '' ?>&vendor_id=<?= $po['vendor_id'] ?? '' ?>" class="fw-bold text-primary ms-1">Create Payment Request Now</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Selected Vendor & CST Details Section in PO View -->
    <?php
    $resolvePoAttUrl = function($att, $type = 'vendor_quotation') use ($base_url) {
        if (empty($att)) return '';
        $att = trim($att);
        if (preg_match('/^https?:\/\//i', $att)) return $att;
        if (strpos($att, 'public/') === 0 || strpos($att, '/public/') === 0) {
            return $base_url . '/' . ltrim($att, '/');
        }
        if (strpos($att, 'uploads/') === 0 || strpos($att, '/uploads/') === 0) {
            return $base_url . '/' . ltrim($att, '/');
        }
        if ($type === 'purchase_requests') {
            return $base_url . '/public/uploads/purchase_requests/' . ltrim($att, '/');
        }
        return $base_url . '/public/uploads/vendor_quotation/' . ltrim($att, '/');
    };

    $isPoImg = function($file) {
        if (empty($file)) return false;
        $ext = strtolower(pathinfo(parse_url($file, PHP_URL_PATH), PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
    };

    $poSelVenId = $selectedVendor['id'] ?? ($po['vendor_id'] ?? null);
    $poPrAttUrl = !empty($prAttachment) ? $resolvePoAttUrl($prAttachment, 'purchase_requests') : null;
    ?>

    <div class="po-panel" style="border-color: rgba(59,130,246,0.35); background: #ffffff;">
      <div class="po-panel-title d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
          <div class="po-dot" style="background:#3b82f6"></div>
          <span>Selected Vendor & Comparative Statement (CST) Details</span>
        </div>
        <div class="d-flex gap-2 align-items-center">
          <?php if (isset($cstMatrix) && !empty($cstMatrix['vendors'])): ?>
            <button type="button" class="btn btn-sm btn-primary" style="font-size:11.5px;font-weight:700;border-radius:8px;padding:4px 12px" data-bs-toggle="modal" data-bs-target="#cstMatrixModal">
              <i class="fal fa-table me-1"></i> View Full CST Matrix
            </button>
          <?php endif; ?>
          <?php if (!empty($poPrAttUrl)): ?>
            <a href="<?= htmlspecialchars($poPrAttUrl) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" style="font-size:11.5px;font-weight:600;border-radius:8px;padding:4px 12px">
              <i class="fal fa-paperclip me-1"></i> PR Attachment
            </a>
          <?php endif; ?>
        </div>
      </div>

      <!-- 1. Selected Vendor Profile Box -->
      <div style="background:linear-gradient(135deg, rgba(59,130,246,.04) 0%, rgba(16,185,129,.04) 100%); border:1px solid rgba(59,130,246,.2); border-radius:12px; padding:16px; margin-bottom:20px">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(59,130,246,.15); padding-bottom:10px; margin-bottom:14px; flex-wrap:wrap; gap:8px">
          <div style="display:flex; align-items:center; gap:8px">
            <span class="badge bg-success" style="font-size:11px; padding:4px 8px"><i class="fal fa-check-circle me-1"></i> Selected / Awarded Vendor</span>
            <span style="font-weight:700; font-size:14px; color:var(--text-color, #1f2937)">
              <?= !empty($selectedVendor['name']) ? htmlspecialchars($selectedVendor['name']) : 'null' ?>
            </span>
            <?php if (!empty($selectedVendor['company_name'])): ?>
              <span class="text-muted" style="font-size:12px">(<?= htmlspecialchars($selectedVendor['company_name']) ?>)</span>
            <?php endif; ?>
          </div>
          <?php if (!empty($cstInfo['prepared_by_name'])): ?>
            <div style="font-size:11.5px; color:var(--text-muted)">
              <i class="fal fa-user-check me-1 text-primary"></i> CST Prepared By: 
              <strong style="color:var(--text-color)"><?= htmlspecialchars($cstInfo['prepared_by_name']) ?></strong>
              <?php if (!empty($cstInfo['created_at'])): ?>
                <span style="margin-left:4px">(<?= date('d M Y, h:i A', strtotime($cstInfo['created_at'])) ?>)</span>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- 6 Required Fields: [id], [name], [phone], [email], [address], [company_name] -->
        <div class="row g-3">
          <div class="col-md-2 col-6">
            <div style="font-size:10.5px; text-transform:uppercase; font-weight:700; color:var(--text-muted); letter-spacing:0.5px">Vendor ID [id]</div>
            <div style="font-weight:700; font-size:13px; color:#1e293b; font-family:monospace; margin-top:3px">
              <?= isset($selectedVendor['id']) && $selectedVendor['id'] !== '' ? '#' . htmlspecialchars($selectedVendor['id']) : '<span style="color:#94a3b8;font-style:italic">null</span>' ?>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div style="font-size:10.5px; text-transform:uppercase; font-weight:700; color:var(--text-muted); letter-spacing:0.5px">Vendor Name [name]</div>
            <div style="font-weight:600; font-size:13px; color:#1e293b; margin-top:3px">
              <?= !empty($selectedVendor['name']) ? htmlspecialchars($selectedVendor['name']) : '<span style="color:#94a3b8;font-style:italic">null</span>' ?>
            </div>
          </div>
          <div class="col-md-2 col-6">
            <div style="font-size:10.5px; text-transform:uppercase; font-weight:700; color:var(--text-muted); letter-spacing:0.5px">Phone [phone]</div>
            <div style="font-weight:600; font-size:13px; color:#1e293b; margin-top:3px">
              <?php if (!empty($selectedVendor['phone'])): ?>
                <a href="tel:<?= htmlspecialchars($selectedVendor['phone']) ?>" style="color:#059669; text-decoration:none">
                  <i class="fal fa-phone me-1"></i><?= htmlspecialchars($selectedVendor['phone']) ?>
                </a>
              <?php else: ?>
                <span style="color:#94a3b8;font-style:italic">null</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="col-md-2 col-6">
            <div style="font-size:10.5px; text-transform:uppercase; font-weight:700; color:var(--text-muted); letter-spacing:0.5px">Email [email]</div>
            <div style="font-weight:600; font-size:13px; color:#1e293b; margin-top:3px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap" title="<?= htmlspecialchars($selectedVendor['email'] ?? '') ?>">
              <?php if (!empty($selectedVendor['email'])): ?>
                <a href="mailto:<?= htmlspecialchars($selectedVendor['email']) ?>" style="color:#2563eb; text-decoration:none">
                  <i class="fal fa-envelope me-1"></i><?= htmlspecialchars($selectedVendor['email']) ?>
                </a>
              <?php else: ?>
                <span style="color:#94a3b8;font-style:italic">null</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="col-md-3 col-12">
            <div style="font-size:10.5px; text-transform:uppercase; font-weight:700; color:var(--text-muted); letter-spacing:0.5px">Company [company_name]</div>
            <div style="font-weight:600; font-size:13px; color:#1e293b; margin-top:3px">
              <?= !empty($selectedVendor['company_name']) ? htmlspecialchars($selectedVendor['company_name']) : '<span style="color:#94a3b8;font-style:italic">null</span>' ?>
            </div>
          </div>
          <div class="col-12" style="border-top:1px solid rgba(0,0,0,0.06); padding-top:10px; margin-top:8px">
            <div style="font-size:10.5px; text-transform:uppercase; font-weight:700; color:var(--text-muted); letter-spacing:0.5px">Address [address]</div>
            <div style="font-size:12.5px; color:#334155; margin-top:3px">
              <?php if (!empty($selectedVendor['address'])): ?>
                <i class="fal fa-map-marker-alt text-danger me-1"></i><?= nl2br(htmlspecialchars($selectedVendor['address'])) ?>
              <?php else: ?>
                <span style="color:#94a3b8;font-style:italic">null</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. CST Participating Vendors Table -->
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px">
        <div style="font-weight:700; font-size:13px; color:var(--text-color)">
          <i class="fal fa-users me-1 text-primary"></i> Participating Vendors in CST Comparison
        </div>
        <span class="badge bg-light text-dark border" style="font-size:11px">
          <?= count($quotationsList) ?> Vendor Quote(s)
        </span>
      </div>

      <?php if (!empty($quotationsList)): ?>
        <div class="table-responsive mb-4">
          <table class="table table-bordered table-hover align-middle mb-0" style="font-size:12px">
            <thead class="table-light">
              <tr>
                <th style="width:40px">#</th>
                <th>Vendor Details</th>
                <th>Company Name</th>
                <th>Contact Info</th>
                <th class="text-end">Quoted Total</th>
                <th class="text-center">Quotation Attachment</th>
                <th class="text-center">CST Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($quotationsList as $idx => $q): ?>
                <?php
                $isThisSelected = ($poSelVenId && (int)$q['vendor_id'] === (int)$poSelVenId);
                $qAttUrl = $resolvePoAttUrl($q['attachment'] ?? null);
                $qIsImg = $isPoImg($q['attachment'] ?? null);
                ?>
                <tr class="<?= $isThisSelected ? 'table-success bg-opacity-25' : '' ?>">
                  <td class="fw-bold"><?= $idx + 1 ?></td>
                  <td>
                    <div class="fw-bold text-dark">
                      <?= !empty($q['vendor_name']) ? htmlspecialchars($q['vendor_name']) : 'null' ?>
                    </div>
                    <div class="text-muted small font-monospace">
                      Vendor ID: #<?= !empty($q['vendor_id']) ? htmlspecialchars($q['vendor_id']) : 'null' ?>
                    </div>
                  </td>
                  <td>
                    <?= !empty($q['vendor_company_name']) ? htmlspecialchars($q['vendor_company_name']) : '<span class="text-muted fst-italic">null</span>' ?>
                  </td>
                  <td>
                    <div>
                      <i class="fal fa-phone text-muted me-1 small"></i>
                      <?= !empty($q['vendor_phone']) ? htmlspecialchars($q['vendor_phone']) : '<span class="text-muted fst-italic">null</span>' ?>
                    </div>
                    <div class="small text-truncate" style="max-width:200px">
                      <i class="fal fa-envelope text-muted me-1 small"></i>
                      <?= !empty($q['vendor_email']) ? htmlspecialchars($q['vendor_email']) : '<span class="text-muted fst-italic">null</span>' ?>
                    </div>
                  </td>
                  <td class="text-end fw-bold">
                    <?php if (isset($q['total_quoted_amount'])): ?>
                      ৳ <?= number_format((float)$q['total_quoted_amount'], 2) ?>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <div class="d-flex flex-column gap-1 align-items-center justify-content-center">
                      <?php if (!empty($qAttUrl)): ?>
                        <a href="<?= htmlspecialchars($qAttUrl) ?>" target="_blank" class="btn btn-xs btn-outline-primary px-2 py-1" style="font-size:11px">
                          <?php if ($qIsImg): ?>
                            <i class="fal fa-image me-1 text-info"></i> View Image
                          <?php else: ?>
                            <i class="fal fa-file-pdf me-1 text-danger"></i> View Document
                          <?php endif; ?>
                        </a>
                      <?php endif; ?>
                      <?php if (!empty($q['drive_link'])): ?>
                        <a href="<?= htmlspecialchars($q['drive_link']) ?>" target="_blank" class="btn btn-xs btn-outline-info px-2 py-1" style="font-size:11px" title="Open Google Drive / External Link">
                          <i class="fal fa-link me-1"></i> Drive Link
                        </a>
                      <?php endif; ?>
                      <?php if (empty($qAttUrl) && empty($q['drive_link'])): ?>
                        <span class="text-muted fst-italic small">No file</span>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td class="text-center">
                    <?php if ($isThisSelected): ?>
                      <span class="badge bg-success" style="font-size:10.5px"><i class="fal fa-check-circle me-1"></i> Selected Vendor</span>
                    <?php else: ?>
                      <span class="badge bg-light text-muted border" style="font-size:10.5px">Alternative</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="alert alert-light border text-center text-muted py-3 mb-4" style="font-size:12px">
          <i class="fal fa-info-circle me-1"></i> No CST quotation records found for this purchase request.
        </div>
      <?php endif; ?>

      <!-- 3. Price Quotation & PR Attachments Gallery -->
      <?php
      $poHasAttachments = false;
      if (!empty($poPrAttUrl)) $poHasAttachments = true;
      foreach ($quotationsList as $q) {
          if (!empty($q['attachment']) || !empty($q['drive_link'])) {
              $poHasAttachments = true;
              break;
          }
      }
      ?>

      <?php if ($poHasAttachments): ?>
        <div style="border-top:1px solid rgba(0,0,0,0.06); padding-top:14px">
          <div style="font-weight:700; font-size:13px; color:var(--text-color); margin-bottom:12px">
            <i class="fal fa-images me-1 text-primary"></i> Price Quotations, Drive Links & PR Attachments
          </div>
          <div class="row g-3">
            <?php if (!empty($poPrAttUrl)): ?>
              <?php $prIsImg = $isPoImg($prAttachment); ?>
              <div class="col-md-3 col-sm-6">
                <div class="card h-100 border shadow-none bg-light-subtle rounded-3 p-2 text-center position-relative">
                  <span class="badge bg-secondary position-absolute top-0 start-0 m-2" style="font-size:10px">PR Attachment</span>
                  <div class="py-3">
                    <?php if ($prIsImg): ?>
                      <a href="<?= htmlspecialchars($poPrAttUrl) ?>" target="_blank">
                        <img src="<?= htmlspecialchars($poPrAttUrl) ?>" alt="PR Attachment" class="img-fluid rounded border" style="max-height:100px; object-fit:cover">
                      </a>
                    <?php else: ?>
                      <i class="fal fa-file-pdf text-danger" style="font-size:3rem"></i>
                    <?php endif; ?>
                  </div>
                  <div class="small fw-semibold text-truncate mb-2" title="Purchase Request Attachment">
                    PR #<?= htmlspecialchars($po['request_id'] ?? '') ?> Document
                  </div>
                  <a href="<?= htmlspecialchars($poPrAttUrl) ?>" target="_blank" class="btn btn-sm btn-outline-secondary w-100" style="font-size:11px">
                    <i class="fal fa-external-link me-1"></i> Open PR File
                  </a>
                </div>
              </div>
            <?php endif; ?>

            <?php foreach ($quotationsList as $q): ?>
              <?php if (!empty($q['attachment']) || !empty($q['drive_link'])): ?>
                <?php
                $qUrl = !empty($q['attachment']) ? $resolvePoAttUrl($q['attachment']) : null;
                $imgCheck = !empty($q['attachment']) ? $isPoImg($q['attachment']) : false;
                $isAwarded = ($poSelVenId && (int)$q['vendor_id'] === (int)$poSelVenId);
                ?>
                <div class="col-md-3 col-sm-6">
                  <div class="card h-100 border <?= $isAwarded ? 'border-success' : '' ?> shadow-none bg-light-subtle rounded-3 p-2 text-center position-relative">
                    <?php if ($isAwarded): ?>
                      <span class="badge bg-success position-absolute top-0 start-0 m-2" style="font-size:10px">
                        <i class="fal fa-check-circle me-1"></i> Selected
                      </span>
                    <?php else: ?>
                      <span class="badge bg-secondary position-absolute top-0 start-0 m-2" style="font-size:10px">Quote</span>
                    <?php endif; ?>
                    <div class="py-3">
                      <?php if ($imgCheck): ?>
                        <a href="<?= htmlspecialchars($qUrl) ?>" target="_blank">
                          <img src="<?= htmlspecialchars($qUrl) ?>" alt="Quotation Attachment" class="img-fluid rounded border" style="max-height:100px; object-fit:cover">
                        </a>
                      <?php elseif (!empty($qUrl)): ?>
                        <i class="fal fa-file-alt text-primary" style="font-size:3rem"></i>
                      <?php else: ?>
                        <i class="fal fa-cloud-upload text-info" style="font-size:3rem"></i>
                      <?php endif; ?>
                    </div>
                    <div class="small fw-bold text-truncate" title="<?= htmlspecialchars($q['vendor_name'] ?? '') ?>">
                      <?= htmlspecialchars($q['vendor_name'] ?? 'Vendor Quote') ?>
                    </div>
                    <div class="text-muted small mb-2 font-monospace" style="font-size:10.5px">
                      <?= !empty($q['total_quoted_amount']) ? '৳ ' . number_format((float)$q['total_quoted_amount'], 2) : '' ?>
                    </div>
                    <?php if (!empty($qUrl)): ?>
                      <a href="<?= htmlspecialchars($qUrl) ?>" target="_blank" class="btn btn-sm btn-outline-primary w-100 mb-1" style="font-size:11px">
                        <i class="fal fa-external-link me-1"></i> View Quotation File
                      </a>
                    <?php endif; ?>
                    <?php if (!empty($q['drive_link'])): ?>
                      <a href="<?= htmlspecialchars($q['drive_link']) ?>" target="_blank" class="btn btn-sm btn-outline-info w-100" style="font-size:11px">
                        <i class="fal fa-link me-1"></i> Open Drive Link
                      </a>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        </div>
      <?php else: ?>
        <div style="border-top:1px solid rgba(0,0,0,0.06); padding-top:14px; margin-top:10px">
          <div style="background:rgba(0,0,0,0.02); border:1px solid rgba(0,0,0,0.06); border-radius:10px; padding:12px 16px; display:flex; align-items:center; gap:12px">
            <div style="font-size:24px; color:#94a3b8"><i class="fal fa-file-times"></i></div>
            <div style="font-size:12px; color:#64748b">
              <strong style="color:#1e293b">No Quotation or PR Attachments Uploaded:</strong><br>
              No quotation files (PDF / Image) or PR attachments were uploaded when these vendor quotations were recorded for this CST.
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>

  </div>


  <!-- Right: Approval Panel -->
  <div class="col-xl-4">

    <!-- Vendor Selection Info -->
    <?php 
    $vendorSelectionData = $vendorSelectionData ?? null; 
    $showVendorPanel = !empty($selectedVendor) || !empty($po['vendor_id']) || !empty($vendorSelectionData);
    ?>
    <?php if ($showVendorPanel): ?>
    <div class="po-panel" style="border-color:rgba(59,130,246,.3);background:rgba(59,130,246,.03)">
      <div class="po-panel-title">
        <div class="po-dot" style="background:#3b82f6"></div>
        Selected Vendor & CST Details
      </div>
      
      <div style="font-size:12px;margin-bottom:6px">
        <span style="color:var(--text-muted)">Vendor ID:</span>
        <strong style="color:var(--text-color);font-family:monospace">#<?= isset($selectedVendor['id']) && $selectedVendor['id'] !== '' ? htmlspecialchars($selectedVendor['id']) : 'null' ?></strong>
      </div>
      <div style="font-size:12px;margin-bottom:6px">
        <span style="color:var(--text-muted)">Name:</span>
        <strong style="color:var(--text-color)"><?= !empty($selectedVendor['name']) ? htmlspecialchars($selectedVendor['name']) : 'null' ?></strong>
      </div>
      <div style="font-size:12px;margin-bottom:6px">
        <span style="color:var(--text-muted)">Company:</span>
        <span style="color:var(--text-color);font-weight:600"><?= !empty($selectedVendor['company_name']) ? htmlspecialchars($selectedVendor['company_name']) : 'null' ?></span>
      </div>
      <div style="font-size:12px;margin-bottom:6px">
        <span style="color:var(--text-muted)">Phone:</span>
        <span style="color:var(--text-color)"><?= !empty($selectedVendor['phone']) ? htmlspecialchars($selectedVendor['phone']) : 'null' ?></span>
      </div>
      <div style="font-size:12px;margin-bottom:6px">
        <span style="color:var(--text-muted)">Email:</span>
        <span style="color:var(--text-color)"><?= !empty($selectedVendor['email']) ? htmlspecialchars($selectedVendor['email']) : 'null' ?></span>
      </div>
      <div style="font-size:12px;margin-bottom:10px">
        <span style="color:var(--text-muted)">Address:</span>
        <span style="color:var(--text-color)"><?= !empty($selectedVendor['address']) ? htmlspecialchars($selectedVendor['address']) : 'null' ?></span>
      </div>

      <?php if (!empty($cstInfo['prepared_by_name'])): ?>
      <div style="font-size:12px;margin-bottom:8px;border-top:1px dashed rgba(59,130,246,.2);padding-top:8px">
        <span style="color:var(--text-muted)">CST Prepared By:</span>
        <span style="font-weight:700;color:var(--text-color)"><?= htmlspecialchars($cstInfo['prepared_by_name']) ?></span>
      </div>
      <?php elseif (!empty($vendorSelectionData['selector_name'])): ?>
      <div style="font-size:12px;margin-bottom:8px;border-top:1px dashed rgba(59,130,246,.2);padding-top:8px">
        <span style="color:var(--text-muted)">Selected By:</span>
        <span style="font-weight:700;color:var(--text-color)"><?= htmlspecialchars($vendorSelectionData['selector_name']) ?></span>
      </div>
      <?php endif; ?>

      <?php
      $selAtt = $vendorSelectionData['attachment'] ?? null;
      $selDrive = $vendorSelectionData['drive_link'] ?? null;
      if (empty($selAtt) || empty($selDrive)) {
          foreach ($quotationsList as $q) {
              if ($poSelVenId && (int)$q['vendor_id'] === (int)$poSelVenId) {
                  if (empty($selAtt) && !empty($q['attachment'])) $selAtt = $q['attachment'];
                  if (empty($selDrive) && !empty($q['drive_link'])) $selDrive = $q['drive_link'];
              }
          }
      }
      ?>

      <?php if (!empty($selAtt)): ?>
      <div style="margin-bottom:8px">
        <a href="<?= $resolvePoAttUrl($selAtt) ?>" target="_blank"
          style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:8px;background:rgba(59,130,246,.1);color:#2563eb;font-size:11px;font-weight:700;text-decoration:none;width:100%;justify-content:center">
          <i class="fal fa-paperclip"></i> View Quotation Attachment
        </a>
      </div>
      <?php endif; ?>

      <?php if (!empty($selDrive)): ?>
      <div style="margin-bottom:8px">
        <a href="<?= htmlspecialchars($selDrive) ?>" target="_blank"
          style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:8px;background:rgba(6,182,212,.1);color:#0891b2;font-size:11px;font-weight:700;text-decoration:none;width:100%;justify-content:center">
          <i class="fal fa-link"></i> Open Drive Link
        </a>
      </div>
      <?php endif; ?>

      <?php if (isset($cstMatrix) && !empty($cstMatrix['vendors'])): ?>
      <div>
        <button type="button" class="btn btn-sm w-100" style="background:rgba(59,130,246,.15);color:#1d4ed8;border:1px solid rgba(59,130,246,.3);border-radius:8px;font-size:11px;font-weight:700;padding:6px" data-bs-toggle="modal" data-bs-target="#cstMatrixModal">
          <i class="fal fa-table"></i> View CST Matrix
        </button>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Negotiation / Observation Notes Panel -->
    <?php if (in_array((int)$currentRoleId, [17, 23, 24, 31]) || !empty($negotiationNotes)): ?>
    <div class="po-panel" style="border-color:rgba(139,92,246,.3);background:rgba(139,92,246,.03)">
      <div class="po-panel-title">
        <div class="po-dot" style="background:#8b5cf6"></div>
        Rate Observation Notes
      </div>

      <!-- Notes List -->
      <?php if (!empty($negotiationNotes)): ?>
        <div style="max-height: 250px; overflow-y: auto; margin-bottom: 15px; padding-right: 5px;">
          <?php foreach ($negotiationNotes as $note): ?>
            <div style="background:#fff;border:1px solid rgba(139,92,246,.2);border-radius:10px;padding:12px;margin-bottom:10px;box-shadow:0 2px 4px rgba(0,0,0,.02)">
              <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px">
                <div style="font-size:11px;font-weight:700;color:#6d28d9">
                  <i class="fal fa-user-circle me-1"></i><?= htmlspecialchars($note['author_name']) ?>
                </div>
                <div style="font-size:9px;color:var(--text-muted)">
                  <?= date('d M, h:i A', strtotime($note['created_at'])) ?>
                </div>
              </div>
              <div style="font-size:12px;color:#4b5563;white-space:pre-wrap;line-height:1.4"><?= htmlspecialchars($note['note_text']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div style="font-size:11px;color:var(--text-muted);margin-bottom:15px;text-align:center"><i class="fal fa-comment-alt-slash me-1"></i> No observation notes yet.</div>
      <?php endif; ?>

      <!-- Add Note Form (Visible to PH and QC) -->
      <?php if (in_array((int)$currentRoleId, [17, 23, 24, 31])): ?>
        <form action="<?= $base_url ?>/con-purchase-order-note/<?= $po['id'] ?>" method="POST">
          <div class="mb-2">
            <textarea name="note_text" class="form-control" rows="2" placeholder="Write observation / rate negotiation note..." required style="font-size:12px;border-radius:10px;border-color:rgba(139,92,246,.3)"></textarea>
          </div>
          <button type="submit" class="btn btn-sm w-100" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed);color:#fff;border-radius:8px;font-size:11px;font-weight:700;padding:6px">
            <i class="fal fa-paper-plane me-1"></i> Submit Note
          </button>
        </form>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="po-panel" style="<?= !$fullyApproved ? 'border-color:rgba(245,158,11,.3)' : 'border-color:rgba(16,185,129,.3)' ?>">
      <div class="po-panel-title">
        <div class="po-dot" style="background:var(--clr-theme-secondary)"></div>
        Approval Status
        <?php
        $totalSteps = $phNeeded ? 3 : 2;
        $doneSteps = ($phNeeded && $phApproved ? 1 : 0) + ($qcApproved ? 1 : 0) + ($opApproved ? 1 : 0);
        ?>
        <span style="margin-left:auto;font-size:9px;font-weight:700;padding:2px 8px;border-radius:10px;
          background:<?= $fullyApproved ? 'rgba(16,185,129,.12)' : 'rgba(245,158,11,.12)' ?>;
          color:<?= $fullyApproved ? '#059669' : '#d97706' ?>">
          <?= $doneSteps ?>/<?= $totalSteps ?> Done
        </span>
      </div>

      <?php if ($phNeeded): ?>
      <!-- Purchase Head Step -->
      <div class="po-apv-step" style="background:<?= $phApproved ? 'rgba(16,185,129,.06)' : 'rgba(245,158,11,.05)' ?>;
        border:1px solid <?= $phApproved ? 'rgba(16,185,129,.2)' : 'rgba(245,158,11,.2)' ?>;margin-bottom:14px">
        <div style="display:flex;align-items:center;gap:10px">
          <div class="po-apv-icon" style="background:<?= $phApproved ? 'rgba(16,185,129,.15)' : 'rgba(245,158,11,.1)' ?>;
            color:<?= $phApproved ? '#059669' : '#d97706' ?>">
            <i class="fal <?= $phApproved ? 'fa-check-circle' : 'fa-user-tie' ?>"></i>
          </div>
          <div>
            <div style="font-size:12px;font-weight:700">Purchase Head</div>
            <?php if ($phApproved && !empty($po['ph_approver_name'])): ?>
              <div style="font-size:10px;font-weight:700;color:#059669"><?= htmlspecialchars($po['ph_approver_name']) ?></div>
              <?php if (!empty($po['purchase_head_approved_at'])): ?>
                <div style="font-size:9px;color:var(--text-muted)"><?= date('d M Y, h:i A', strtotime($po['purchase_head_approved_at'])) ?></div>
              <?php endif; ?>
              <?php if (!empty($po['purchase_head_note'])): ?>
                <div style="font-size:10px;color:#555;font-style:italic;margin-top:2px">Note: <?= htmlspecialchars($po['purchase_head_note']) ?></div>
              <?php endif; ?>
            <?php else: ?>
              <div style="font-size:10px;color:var(--text-muted)">Awaiting Purchase Head approval</div>
            <?php endif; ?>
          </div>
        </div>
        <span style="font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;
          background:<?= $phApproved ? 'rgba(16,185,129,.12)' : 'rgba(245,158,11,.1)' ?>;
          color:<?= $phApproved ? '#059669' : '#d97706' ?>">
          <?= $phApproved ? '✓ Approved' : 'Pending' ?>
        </span>
      </div>
      <?php endif; ?>

      <!-- QC & Internal Audit Step -->
      <div class="po-apv-step" style="background:<?= $qcApproved ? 'rgba(16,185,129,.06)' : 'rgba(245,158,11,.05)' ?>;
        border:1px solid <?= $qcApproved ? 'rgba(16,185,129,.2)' : 'rgba(245,158,11,.2)' ?>;margin-bottom:14px">
        <div style="display:flex;align-items:center;gap:10px">
          <div class="po-apv-icon" style="background:<?= $qcApproved ? 'rgba(16,185,129,.15)' : 'rgba(245,158,11,.1)' ?>;
            color:<?= $qcApproved ? '#059669' : '#d97706' ?>">
            <i class="fal <?= $qcApproved ? 'fa-check-circle' : 'fa-clipboard-check' ?>"></i>
          </div>
          <div>
            <div style="font-size:12px;font-weight:700">QC & Internal Audit</div>
            <?php if ($qcApproved && !empty($po['qc_approver_name'])): ?>
              <div style="font-size:10px;font-weight:700;color:#059669"><?= htmlspecialchars($po['qc_approver_name']) ?></div>
              <?php if (!empty($po['qc_approved_at'])): ?>
                <div style="font-size:9px;color:var(--text-muted)"><?= date('d M Y, h:i A', strtotime($po['qc_approved_at'])) ?></div>
              <?php endif; ?>
              <?php if (!empty($po['qc_note'])): ?>
                <div style="font-size:10px;color:#555;font-style:italic;margin-top:2px">Note: <?= htmlspecialchars($po['qc_note']) ?></div>
              <?php endif; ?>
            <?php else: ?>
              <?php if ($phNeeded && !$phApproved): ?>
                <div style="font-size:10px;color:var(--text-muted)">Waiting for Purchase Head first</div>
              <?php else: ?>
                <div style="font-size:10px;color:var(--text-muted)">Awaiting QC approval</div>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
        <span style="font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;
          background:<?= $qcApproved ? 'rgba(16,185,129,.12)' : 'rgba(245,158,11,.1)' ?>;
          color:<?= $qcApproved ? '#059669' : '#d97706' ?>">
          <?= $qcApproved ? '✓ Approved' : 'Pending' ?>
        </span>
      </div>

      <!-- Operation Head Step -->
      <div class="po-apv-step" style="background:<?= $opApproved ? 'rgba(16,185,129,.06)' : 'rgba(245,158,11,.05)' ?>;
        border:1px solid <?= $opApproved ? 'rgba(16,185,129,.2)' : 'rgba(245,158,11,.2)' ?>;margin-bottom:14px">
        <div style="display:flex;align-items:center;gap:10px">
          <div class="po-apv-icon" style="background:<?= $opApproved ? 'rgba(16,185,129,.15)' : 'rgba(245,158,11,.1)' ?>;
            color:<?= $opApproved ? '#059669' : '#d97706' ?>">
            <i class="fal <?= $opApproved ? 'fa-check-circle' : 'fa-user-tie' ?>"></i>
          </div>
          <div>
            <div style="font-size:12px;font-weight:700">Operation Head</div>
            <?php if ($opApproved && !empty($po['op_approver_name'])): ?>
              <div style="font-size:10px;font-weight:700;color:#059669"><?= htmlspecialchars($po['op_approver_name']) ?></div>
              <?php if (!empty($po['op_head_approved_at'])): ?>
                <div style="font-size:9px;color:var(--text-muted)"><?= date('d M Y, h:i A', strtotime($po['op_head_approved_at'])) ?></div>
              <?php endif; ?>
              <?php if (!empty($po['op_head_note'])): ?>
                <div style="font-size:10px;color:#555;font-style:italic;margin-top:2px">Note: <?= htmlspecialchars($po['op_head_note']) ?></div>
              <?php endif; ?>
            <?php else: ?>
              <?php if (!$qcApproved): ?>
                <div style="font-size:10px;color:var(--text-muted)">Waiting for QC first</div>
              <?php else: ?>
                <div style="font-size:10px;color:var(--text-muted)">Awaiting Operation Head approval</div>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
        <span style="font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;
          background:<?= $opApproved ? 'rgba(16,185,129,.12)' : 'rgba(245,158,11,.1)' ?>;
          color:<?= $opApproved ? '#059669' : '#d97706' ?>">
          <?= $opApproved ? '✓ Approved' : 'Pending' ?>
        </span>
      </div>

      <!-- Fully Approved Banner -->
      <?php if ($fullyApproved): ?>
        <div style="padding:12px;border-radius:10px;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);text-align:center;margin-bottom:14px">
          <i class="fal fa-check-circle" style="color:#059669;font-size:20px;display:block;margin-bottom:5px"></i>
          <div style="font-size:12px;font-weight:800;color:#059669">Approved</div>
          <div style="font-size:11px;color:var(--text-muted)">GRN can be created</div>
        </div>
      <?php endif; ?>

      <!-- Approve Action — Purchase Head -->
      <?php if ($canApprovePH): ?>
        <form action="<?= $base_url ?>/con-purchase-order-approve/<?= $po['id'] ?>" method="POST" id="phApproveForm" style="margin-bottom:8px">
          <div class="mb-2">
            <textarea name="approval_note" class="form-control" rows="2" placeholder="Purchase Head Approval Note (optional)" style="font-size:12px;border-radius:10px"></textarea>
          </div>
          <button type="button" id="btnApprovePH"
            style="width:100%;background:linear-gradient(135deg,#d97706,#f59e0b);color:#fff;border:none;
            border-radius:10px;padding:10px;font-size:12px;font-weight:700;cursor:pointer">
            <i class="fal fa-user-tie me-2"></i>Approve as Purchase Head
          </button>
        </form>

        <button type="button" class="btn btn-outline-danger w-100" style="border-radius:10px;font-size:12px;font-weight:700;padding:8px" data-bs-toggle="modal" data-bs-target="#rejectPoModal">
          <i class="fal fa-times-circle me-2"></i>Reject Order
        </button>
      <?php endif; ?>

      <!-- Approve Action — QC -->
      <?php if ($canApproveQC): ?>
        <form action="<?= $base_url ?>/con-purchase-order-approve/<?= $po['id'] ?>" method="POST" id="qcApproveForm" style="margin-bottom:8px">
          <div class="mb-2">
            <textarea name="approval_note" class="form-control" rows="2" placeholder="QC Approval Note (optional)" style="font-size:12px;border-radius:10px"></textarea>
          </div>
          <button type="button" id="btnApproveQC"
            style="width:100%;background:linear-gradient(135deg,#1d4ed8,#3b82f6);color:#fff;border:none;
            border-radius:10px;padding:10px;font-size:12px;font-weight:700;cursor:pointer">
            <i class="fal fa-clipboard-check me-2"></i>Approve as QC
          </button>
        </form>

        <button type="button" class="btn btn-outline-danger w-100" style="border-radius:10px;font-size:12px;font-weight:700;padding:8px" data-bs-toggle="modal" data-bs-target="#rejectPoModal">
          <i class="fal fa-times-circle me-2"></i>Reject Order
        </button>
      <?php endif; ?>

      <!-- Approve Action — Operation Head -->
      <?php if ($canApproveOP): ?>
        <form action="<?= $base_url ?>/con-purchase-order-approve/<?= $po['id'] ?>" method="POST" id="opApproveForm" style="margin-bottom:8px">
          <div class="mb-2">
            <textarea name="approval_note" class="form-control" rows="2" placeholder="Operation Head Approval Note (optional)" style="font-size:12px;border-radius:10px"></textarea>
          </div>
          <button type="button" id="btnApproveOP"
            style="width:100%;background:linear-gradient(135deg,#059669,#10b981);color:#fff;border:none;
            border-radius:10px;padding:10px;font-size:12px;font-weight:700;cursor:pointer">
            <i class="fal fa-user-tie me-2"></i>Approve as Operation Head
          </button>
        </form>

        <button type="button" class="btn btn-outline-danger w-100" style="border-radius:10px;font-size:12px;font-weight:700;padding:8px" data-bs-toggle="modal" data-bs-target="#rejectPoModal">
          <i class="fal fa-times-circle me-2"></i>Reject Order
        </button>
      <?php endif; ?>

      <!-- Reject Modal -->
      <?php if ($canApprovePH || $canApproveQC || $canApproveOP): ?>
        <div class="modal fade" id="rejectPoModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:16px;border:none;box-shadow:0 10px 40px rgba(0,0,0,.15)">
              <form action="<?= $base_url ?>/con-purchase-order-reject/<?= $po['id'] ?>" method="POST">
                <div class="modal-header border-0 pb-0">
                  <h5 class="modal-title text-danger fw-bold"><i class="fal fa-exclamation-triangle me-2"></i>Reject Purchase Order</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <div class="mb-3">
                    <label class="form-label fw-bold">Reason for Rejection <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="remarks" rows="3" placeholder="Explain why this PO is being rejected..." required></textarea>
                    <div class="form-text">An email notification will be sent with these remarks.</div>
                  </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                  <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:10px;font-weight:600">Cancel</button>
                  <button type="submit" class="btn btn-danger" style="border-radius:10px;font-weight:600"><i class="fal fa-times-circle me-1"></i> Confirm Rejection</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         BOQ REFERENCE PANEL — visible to PM (role 15) always
         ══════════════════════════════════════════════════════════ -->
    <?php if ($linkedBoq): ?>
      <?php
      $boqRef   = $linkedBoq['boq'];
      $boqItems = $linkedBoq['items'];
      $boqTotal = array_sum(array_column($boqItems, 'line_total'));
      $months   = [
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
      $boqMonthName = $months[(int)($boqRef['target_month'] ?? 0)] ?? '';
      ?>
      <div class="po-panel mt-3" style="border-color:rgba(var(--clr-theme-primary-rgb),.3);background:rgba(var(--clr-theme-primary-rgb),.03)">
        <div class="po-panel-title" style="color:var(--clr-theme-primary)">
          <div class="po-dot" style="background:var(--clr-theme-primary)"></div>
          BOQ Reference
          <span style="margin-left:auto;font-size:9px;font-weight:700;padding:2px 8px;border-radius:10px;
          background:rgba(var(--clr-theme-primary-rgb),.1);color:var(--clr-theme-primary)">
            BOQ #<?= $boqRef['id'] ?> · v<?= htmlspecialchars($boqRef['version'] ?? '1.0') ?>
          </span>
        </div>

        <!-- BOQ Meta Info -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px">
          <div style="background:rgba(var(--clr-theme-primary-rgb),.06);border-radius:10px;padding:10px 13px">
            <div style="font-size:9px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--clr-theme-primary);margin-bottom:4px">Target</div>
            <div style="font-size:12px;font-weight:700"><?= htmlspecialchars($boqRef['target_title'] ?? '—') ?></div>
            <?php if (!empty($boqRef['target_year'])): ?>
              <div style="font-size:10px;color:var(--text-muted)">
                <?= $boqMonthName . ' ' . $boqRef['target_year'] ?>
                <?= !empty($boqRef['target_week']) ? ' · Week ' . $boqRef['target_week'] : '' ?>
              </div>
            <?php endif; ?>
          </div>
          <div style="background:rgba(var(--clr-theme-primary-rgb),.06);border-radius:10px;padding:10px 13px">
            <div style="font-size:9px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--clr-theme-primary);margin-bottom:4px">Design Type</div>
            <div style="font-size:12px;font-weight:700"><?= htmlspecialchars($boqRef['design_type_name'] ?? '—') ?></div>
            <div style="font-size:10px;color:var(--text-muted)">Status: <?= htmlspecialchars($boqRef['status'] ?? '') ?></div>
          </div>
          <div style="background:rgba(var(--clr-theme-primary-rgb),.06);border-radius:10px;padding:10px 13px">
            <div style="font-size:9px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--clr-theme-primary);margin-bottom:4px">Prepared By</div>
            <div style="font-size:12px;font-weight:700"><?= htmlspecialchars($boqRef['created_by_name'] ?? '—') ?></div>
          </div>
          <div style="background:rgba(var(--clr-theme-primary-rgb),.06);border-radius:10px;padding:10px 13px">
            <div style="font-size:9px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--clr-theme-primary);margin-bottom:4px">BOQ Total Value</div>
            <div style="font-size:14px;font-weight:900;color:var(--clr-theme-primary)">৳ <?= number_format($boqTotal, 2) ?></div>
            <div style="font-size:10px;color:var(--text-muted)"><?= count($boqItems) ?> material(s)</div>
          </div>
        </div>

        <!-- Toggle Button -->
        <button onclick="toggleBoqItems()" id="boq-toggle-btn"
          style="width:100%;background:rgba(var(--clr-theme-primary-rgb),.1);border:1px solid rgba(var(--clr-theme-primary-rgb),.25);
          color:var(--clr-theme-primary);border-radius:8px;padding:7px;font-size:11px;font-weight:700;cursor:pointer;
          margin-bottom:10px;display:flex;align-items:center;justify-content:center;gap:6px">
          <i class="fal fa-list-ul" id="boq-toggle-icon"></i>
          <span id="boq-toggle-text">Show BOQ Items (<?= count($boqItems) ?>)</span>
        </button>

        <!-- BOQ Items Table (collapsed by default) -->
        <div id="boq-items-table" style="display:none">
          <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-2" style="font-size:11px">
              <thead style="background:rgba(var(--clr-theme-primary-rgb),.08)">
                <tr>
                  <th style="color:var(--clr-theme-primary)">#</th>
                  <th style="color:var(--clr-theme-primary)">Material</th>
                  <th style="color:var(--clr-theme-primary)">Unit</th>
                  <th class="text-end" style="color:var(--clr-theme-primary)">BOQ Qty</th>
                  <th class="text-end" style="color:var(--clr-theme-primary)">Unit Cost</th>
                  <th class="text-end" style="color:var(--clr-theme-primary)">Line Total</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($boqItems as $bi => $bitem): ?>
                  <tr>
                    <td><?= $bi + 1 ?></td>
                    <td>
                      <span class="fw-bold"><?= htmlspecialchars($bitem['material_name']) ?></span>
                      <br><small class="text-muted">[Category: <?= htmlspecialchars($bitem['category_name'] ?? 'Uncategorized') ?>]</small>
                    </td>
                    <td><?= htmlspecialchars($bitem['unit']) ?></td>
                    <td class="text-end"><?= number_format($bitem['quantity'], 3) ?></td>
                    <td class="text-end">৳ <?= number_format($bitem['unit_cost'], 2) ?></td>
                    <td class="text-end fw-bold text-indigo">৳ <?= number_format($bitem['line_total'], 2) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot style="background:rgba(var(--clr-theme-primary-rgb),.06)">
                <tr>
                  <th colspan="5" class="text-end py-2" style="color:var(--clr-theme-primary)">BOQ TOTAL:</th>
                  <th class="text-end py-2" style="color:var(--clr-theme-primary);font-size:13px">৳ <?= number_format($boqTotal, 2) ?></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- Full BOQ Link -->
        <a href="<?= $base_url ?>/con-boq-show/<?= $boqRef['id'] ?>" target="_blank"
          style="display:flex;align-items:center;justify-content:center;gap:6px;
          padding:8px;border-radius:8px;background:linear-gradient(135deg,var(--clr-theme-primary),var(--clr-theme-primary));
          color:#fff;font-size:11px;font-weight:700;text-decoration:none;margin-top:4px">
          <i class="fal fa-external-link"></i> View Full BOQ Sheet
        </a>
      </div>
    <?php else: ?>
      <div class="po-panel mt-3" style="border-color:rgba(107,114,128,.2);background:rgba(107,114,128,.03)">
        <div style="text-align:center;padding:10px;color:var(--text-muted);font-size:11px">
          <i class="fal fa-info-circle me-1"></i> No BOQ linked to this Purchase Request.
        </div>
      </div>
    <?php endif; ?>

  </div>
</div>

<script>
  function toggleBoqItems() {
    const table = document.getElementById('boq-items-table');
    const btn = document.getElementById('boq-toggle-btn');
    const icon = document.getElementById('boq-toggle-icon');
    const text = document.getElementById('boq-toggle-text');
    if (table.style.display === 'none') {
      table.style.display = 'block';
      icon.className = 'fal fa-chevron-up';
      text.textContent = 'Hide BOQ Items';
      btn.style.background = 'rgba(var(--clr-theme-primary-rgb),.18)';
    } else {
      table.style.display = 'none';
      icon.className = 'fal fa-list-ul';
      text.textContent = 'Show BOQ Items (<?= count($linkedBoq["items"] ?? []) ?>)';
      btn.style.background = 'rgba(var(--clr-theme-primary-rgb),.1)';
    }
  }
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var phBtn = document.getElementById('btnApprovePH');
    if (phBtn) {
        phBtn.addEventListener('click', function() {
            Swal.fire({
                title: 'Purchase Head Approval',
                text: 'Approve this Purchase Order as Purchase Head? This will forward it to QC.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#d97706',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Approve!'
            }).then(function(result) {
                if (result.isConfirmed) {
                    document.getElementById('phApproveForm').submit();
                }
            });
        });
    }
    var qcBtn = document.getElementById('btnApproveQC');
    if (qcBtn) {
        qcBtn.addEventListener('click', function() {
            Swal.fire({
                title: 'QC Approval',
                text: 'Approve this Purchase Order as QC & Internal Audit? This will forward it to Operation Head.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1d4ed8',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Approve!'
            }).then(function(result) {
                if (result.isConfirmed) {
                    document.getElementById('qcApproveForm').submit();
                }
            });
        });
    }
    var opBtn = document.getElementById('btnApproveOP');
    if (opBtn) {
        opBtn.addEventListener('click', function() {
            Swal.fire({
                title: 'Operation Head Approval',
                text: 'Approve this Purchase Order as Operation Head? This is the final approval step.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#059669',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Approve!'
            }).then(function(result) {
                if (result.isConfirmed) {
                    document.getElementById('opApproveForm').submit();
                }
            });
        });
    }
});
</script>

<style>
  @media print {

    .po-hero .d-flex.gap-2,
    .po-btn-w,
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

<!-- CST Matrix Modal -->
<?php if (isset($cstMatrix) && !empty($cstMatrix['vendors'])): ?>
<div class="modal fade" id="cstMatrixModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:16px;border:none;box-shadow:0 10px 40px rgba(0,0,0,.15)">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" style="color:var(--clr-theme-primary)">
          <i class="fal fa-columns me-2"></i>Comparative Statement (CST) Matrix
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?php
        $materials = $cstMatrix['materials'] ?? [];
        $vendors   = $cstMatrix['vendors']   ?? [];
        $matrix    = $cstMatrix['matrix']    ?? [];
        $totals    = $cstMatrix['totals']    ?? [];
        $vendorIds = array_keys($vendors);
        $selectedVendorId = $po['vendor_id'];
        ?>
        <div class="table-responsive">
          <table class="table table-bordered align-middle text-center" style="font-size:12px">
            <thead class="table-dark">
              <tr>
                <th class="text-start" style="width:5%;">#</th>
                <th class="text-start" style="width:25%;">Material</th>
                <th style="width:10%;">PR Qty</th>
                <?php foreach ($vendors as $vid => $vname): ?>
                  <th style="min-width:130px;" class="<?= ($vid == $selectedVendorId) ? 'bg-success text-white border-success' : '' ?>">
                    <?= htmlspecialchars($vname) ?>
                    <?php if ($vid == $selectedVendorId): ?>
                      <br><span class="badge bg-white text-success mt-1 px-2" style="font-size:9px">Selected Vendor</span>
                    <?php endif; ?>
                  </th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($materials as $idx => $mat): ?>
              <?php
              $mid = $mat['material_id'];
              $lowestPrice  = PHP_FLOAT_MAX;
              $lowestVendor = 0;
              foreach ($vendorIds as $vid) {
                  if (isset($matrix[$mid][$vid])) {
                      if ($matrix[$mid][$vid]['unit_price'] < $lowestPrice) {
                          $lowestPrice  = $matrix[$mid][$vid]['unit_price'];
                          $lowestVendor = $vid;
                      }
                  }
              }
              ?>
              <tr>
                  <td class="text-start"><?= $idx + 1 ?></td>
                  <td class="text-start fw-bold">
                      <?= htmlspecialchars($mat['material_name']) ?>
                      <span class="text-muted small">(<?= $mat['unit'] ?>)</span>
                  </td>
                  <td><?= number_format($mat['quantity'], 3) ?></td>
                  <?php foreach ($vendorIds as $vid): ?>
                      <?php
                      $cell = $matrix[$mid][$vid] ?? null;
                      $isLowest = ($vid === $lowestVendor && $cell);
                      $isSelectedVendor = ($vid == $selectedVendorId);
                      ?>
                      <td class="<?= $isLowest ? 'table-success fw-bold' : '' ?> <?= $isSelectedVendor ? 'border-success' : '' ?>" style="<?= $isSelectedVendor ? 'border-left-width:2px;border-right-width:2px;' : '' ?>">
                          <?php if ($cell): ?>
                              ৳ <?= number_format($cell['unit_price'], 2) ?>
                              <div class="small text-muted" style="margin-top:2px">
                                  Total: ৳ <?= number_format($cell['total_price'], 2) ?>
                              </div>
                          <?php else: ?>
                              <span class="text-muted">—</span>
                          <?php endif; ?>
                      </td>
                  <?php endforeach; ?>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light">
                <tr class="fw-bold">
                    <td colspan="3" class="text-end">Vendor Totals:</td>
                    <?php foreach ($vendorIds as $vid): ?>
                        <td class="<?= ($vid == $selectedVendorId) ? 'text-success' : '' ?>">৳ <?= number_format($totals[$vid] ?? 0, 2) ?></td>
                    <?php endforeach; ?>
                </tr>
            </tfoot>
          </table>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:10px;font-weight:600">Close Window</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
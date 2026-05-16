<?php
$base_url     = $GLOBALS['base_url'];
$po           = $po ?? [];
$items        = $items ?? [];
$pendingItems = $pendingItems ?? [];
$seApproved   = $seApproved  ?? false;
$pmApproved   = $pmApproved  ?? false;
$fullyApproved = $fullyApproved ?? false;
$canApproveSE = $canApproveSE ?? false;
$canApprovePM = $canApprovePM ?? false;
$currentRoleId = $currentRoleId ?? 0;
$linkedBoq    = $linkedBoq ?? null;

$statusBadge = [
  'Issued'             => ['bg' => 'rgba(59,130,246,.15)',  'color' => '#2563eb', 'label' => 'Issued'],
  'Approved'           => ['bg' => 'rgba(16,185,129,.15)',  'color' => '#059669', 'label' => 'Approved'],
  'Partially Received' => ['bg' => 'rgba(245,158,11,.15)',  'color' => '#d97706', 'label' => 'Partially Received'],
  'Fully Received'     => ['bg' => 'rgba(16,185,129,.15)',  'color' => '#059669', 'label' => 'Fully Received'],
  'Cancelled'          => ['bg' => 'rgba(239,68,68,.15)',   'color' => '#dc2626', 'label' => 'Cancelled'],
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
          <?= $fullyApproved ? '✅ Management Approved' : '⏳ Awaiting Management Approval' ?>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <!-- Left: Items + Delivery Tracker -->
  <div class="col-xl-8">

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
                <td class="fw-bold"><?= htmlspecialchars($item['material_name']) ?></td>
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
                <td class="fw-bold"><?= htmlspecialchars($pi['material_name']) ?></td>
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

    <!-- GRN Button — only if fully approved -->
    <?php 
      $hasReceiving = $hasReceiving ?? false;
      // Allow GRN logging for any authenticated user EXCEPT site operator (role 20)
      // Purchase Head, Admin, PM, Site Eng, etc. all should be able to log receiving
      $canLogGrn = !in_array($currentRoleId, [20]);
      if ($currentRoleId == 12 && $hasReceiving) {
          $canLogGrn = false; // Site Engineer can only create GRN once per PO
      }
    ?>
    <?php if (in_array($_SESSION['user']['role_id'], [1,3,12,15,22], true)) : ?>
   

    <?php if ($fullyApproved && $po['status'] !== 'Fully Received' && $po['status'] !== 'Cancelled' && $canLogGrn): ?>
      <div class="po-panel" style="border-color:rgba(16,185,129,.35);background:rgba(16,185,129,.04)">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
          <div>
            <div style="font-size:13px;font-weight:700;color:#059669"><i class="fal fa-check-circle me-2"></i>PO is Fully Approved</div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Management approved. You can now log material receiving.</div>
          </div>
          <a href="<?= $base_url ?>/con-material-receiving-create/<?= $po['id'] ?>"
            style="background:linear-gradient(135deg,#059669,#10b981);color:#fff;border:none;border-radius:10px;padding:9px 22px;font-size:12px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:7px">
            <i class="fal fa-box-seam"></i> Log Material Receiving (GRN)
          </a>
        </div>
      </div>
        <?php endif; ?>
    <?php elseif (!$fullyApproved && $po['status'] !== 'Cancelled'): ?>
      <div class="po-panel" style="border-color:rgba(245,158,11,.3);background:rgba(245,158,11,.03)">
        <div style="display:flex;align-items:center;gap:10px">
          <i class="fal fa-lock" style="color:#d97706;font-size:20px"></i>
          <div>
            <div style="font-size:13px;font-weight:700;color:#d97706">Material Receiving Locked</div>
            <div style="font-size:11px;color:var(--text-muted)">Waiting for Management/PM approval.</div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>


  <!-- Right: Approval Panel -->
  <div class="col-xl-4">
    <div class="po-panel" style="<?= !$fullyApproved ? 'border-color:rgba(245,158,11,.3)' : 'border-color:rgba(16,185,129,.3)' ?>">
      <div class="po-panel-title">
        <div class="po-dot" style="background:#8b5cf6"></div>
        Approval Status
        <span style="margin-left:auto;font-size:9px;font-weight:700;padding:2px 8px;border-radius:10px;
          background:<?= $fullyApproved ? 'rgba(16,185,129,.12)' : 'rgba(245,158,11,.12)' ?>;
          color:<?= $fullyApproved ? '#059669' : '#d97706' ?>">
          <?= ($pmApproved ? 1 : 0) ?>/1 Done
        </span>
      </div>


      <!-- Management / PM Step -->
      <div class="po-apv-step" style="background:<?= $pmApproved ? 'rgba(16,185,129,.06)' : 'rgba(245,158,11,.05)' ?>;
        border:1px solid <?= $pmApproved ? 'rgba(16,185,129,.2)' : 'rgba(245,158,11,.2)' ?>;margin-bottom:14px">
        <div style="display:flex;align-items:center;gap:10px">
          <div class="po-apv-icon" style="background:<?= $pmApproved ? 'rgba(16,185,129,.15)' : 'rgba(245,158,11,.1)' ?>;
            color:<?= $pmApproved ? '#059669' : '#d97706' ?>">
            <i class="fal <?= $pmApproved ? 'fa-check-circle' : 'fa-user-tie' ?>"></i>
          </div>
          <div>
            <div style="font-size:12px;font-weight:700">Management / Project Manager</div>
            <?php if ($pmApproved && !empty($po['pm_approver_name'])): ?>
              <div style="font-size:10px;font-weight:700;color:#059669"><?= htmlspecialchars($po['pm_approver_name']) ?></div>
              <?php if (!empty($po['pm_approver_role'])): ?>
                <div style="font-size:9px;color:#6b7280;font-style:italic"><?= htmlspecialchars($po['pm_approver_role']) ?></div>
              <?php endif; ?>
              <?php if (!empty($po['pm_approved_at'])): ?>
                <div style="font-size:9px;color:var(--text-muted)"><?= date('d M Y, h:i A', strtotime($po['pm_approved_at'])) ?></div>
              <?php endif; ?>
            <?php else: ?>
              <div style="font-size:10px;color:var(--text-muted)">Awaiting approval</div>
            <?php endif; ?>
          </div>
        </div>
        <span style="font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;
          background:<?= $pmApproved ? 'rgba(16,185,129,.12)' : 'rgba(245,158,11,.1)' ?>;
          color:<?= $pmApproved ? '#059669' : '#d97706' ?>">
          <?= $pmApproved ? '✓ Approved' : 'Pending' ?>
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

      <!-- Approve Action — PM -->
      <?php if ($canApprovePM): ?>
        <form action="<?= $base_url ?>/con-purchase-order-approve/<?= $po['id'] ?>" method="POST" style="margin-bottom:8px">
          <button type="submit"
            style="width:100%;background:linear-gradient(135deg,#1d4ed8,#3b82f6);color:#fff;border:none;
            border-radius:10px;padding:10px;font-size:12px;font-weight:700;cursor:pointer"
            onclick="return confirm('Approve this Purchase Order as Management/PM?')">
            <i class="fal fa-user-tie me-2"></i>Approve as Management/PM
          </button>
        </form>

        <button type="button" class="btn btn-outline-danger w-100" style="border-radius:10px;font-size:12px;font-weight:700;padding:8px" data-bs-toggle="modal" data-bs-target="#rejectPoModal">
          <i class="fal fa-times-circle me-2"></i>Reject Order
        </button>
        
        <!-- Reject Modal -->
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
                    <div class="form-text">An email notification will be sent to the Procurement Head and Site Engineer with these remarks.</div>
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
      <div class="po-panel mt-3" style="border-color:rgba(99,102,241,.3);background:rgba(99,102,241,.03)">
        <div class="po-panel-title" style="color:#4f46e5">
          <div class="po-dot" style="background:#4f46e5"></div>
          BOQ Reference
          <span style="margin-left:auto;font-size:9px;font-weight:700;padding:2px 8px;border-radius:10px;
          background:rgba(99,102,241,.1);color:#4f46e5">
            BOQ #<?= $boqRef['id'] ?> · v<?= htmlspecialchars($boqRef['version'] ?? '1.0') ?>
          </span>
        </div>

        <!-- BOQ Meta Info -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px">
          <div style="background:rgba(99,102,241,.06);border-radius:10px;padding:10px 13px">
            <div style="font-size:9px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#6366f1;margin-bottom:4px">Target</div>
            <div style="font-size:12px;font-weight:700"><?= htmlspecialchars($boqRef['target_title'] ?? '—') ?></div>
            <?php if (!empty($boqRef['target_year'])): ?>
              <div style="font-size:10px;color:var(--text-muted)">
                <?= $boqMonthName . ' ' . $boqRef['target_year'] ?>
                <?= !empty($boqRef['target_week']) ? ' · Week ' . $boqRef['target_week'] : '' ?>
              </div>
            <?php endif; ?>
          </div>
          <div style="background:rgba(99,102,241,.06);border-radius:10px;padding:10px 13px">
            <div style="font-size:9px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#6366f1;margin-bottom:4px">Design Type</div>
            <div style="font-size:12px;font-weight:700"><?= htmlspecialchars($boqRef['design_type_name'] ?? '—') ?></div>
            <div style="font-size:10px;color:var(--text-muted)">Status: <?= htmlspecialchars($boqRef['status'] ?? '') ?></div>
          </div>
          <div style="background:rgba(99,102,241,.06);border-radius:10px;padding:10px 13px">
            <div style="font-size:9px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#6366f1;margin-bottom:4px">Prepared By</div>
            <div style="font-size:12px;font-weight:700"><?= htmlspecialchars($boqRef['created_by_name'] ?? '—') ?></div>
          </div>
          <div style="background:rgba(99,102,241,.06);border-radius:10px;padding:10px 13px">
            <div style="font-size:9px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#6366f1;margin-bottom:4px">BOQ Total Value</div>
            <div style="font-size:14px;font-weight:900;color:#4f46e5">৳ <?= number_format($boqTotal, 2) ?></div>
            <div style="font-size:10px;color:var(--text-muted)"><?= count($boqItems) ?> material(s)</div>
          </div>
        </div>

        <!-- Toggle Button -->
        <button onclick="toggleBoqItems()" id="boq-toggle-btn"
          style="width:100%;background:rgba(99,102,241,.1);border:1px solid rgba(99,102,241,.25);
          color:#4f46e5;border-radius:8px;padding:7px;font-size:11px;font-weight:700;cursor:pointer;
          margin-bottom:10px;display:flex;align-items:center;justify-content:center;gap:6px">
          <i class="fal fa-list-ul" id="boq-toggle-icon"></i>
          <span id="boq-toggle-text">Show BOQ Items (<?= count($boqItems) ?>)</span>
        </button>

        <!-- BOQ Items Table (collapsed by default) -->
        <div id="boq-items-table" style="display:none">
          <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-2" style="font-size:11px">
              <thead style="background:rgba(99,102,241,.08)">
                <tr>
                  <th style="color:#4f46e5">#</th>
                  <th style="color:#4f46e5">Material</th>
                  <th style="color:#4f46e5">Unit</th>
                  <th class="text-end" style="color:#4f46e5">BOQ Qty</th>
                  <th class="text-end" style="color:#4f46e5">Unit Cost</th>
                  <th class="text-end" style="color:#4f46e5">Line Total</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($boqItems as $bi => $bitem): ?>
                  <tr>
                    <td><?= $bi + 1 ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($bitem['material_name']) ?></td>
                    <td><?= htmlspecialchars($bitem['unit']) ?></td>
                    <td class="text-end"><?= number_format($bitem['quantity'], 3) ?></td>
                    <td class="text-end">৳ <?= number_format($bitem['unit_cost'], 2) ?></td>
                    <td class="text-end fw-bold text-indigo">৳ <?= number_format($bitem['line_total'], 2) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot style="background:rgba(99,102,241,.06)">
                <tr>
                  <th colspan="5" class="text-end py-2" style="color:#4f46e5">BOQ TOTAL:</th>
                  <th class="text-end py-2" style="color:#4f46e5;font-size:13px">৳ <?= number_format($boqTotal, 2) ?></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- Full BOQ Link -->
        <a href="<?= $base_url ?>/con-boq-show/<?= $boqRef['id'] ?>" target="_blank"
          style="display:flex;align-items:center;justify-content:center;gap:6px;
          padding:8px;border-radius:8px;background:linear-gradient(135deg,#4f46e5,#6366f1);
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
      btn.style.background = 'rgba(99,102,241,.18)';
    } else {
      table.style.display = 'none';
      icon.className = 'fal fa-list-ul';
      text.textContent = 'Show BOQ Items (<?= count($linkedBoq["items"] ?? []) ?>)';
      btn.style.background = 'rgba(99,102,241,.1)';
    }
  }
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
<?php
$prId  = (int)$opo['extra_purchase_id'];
$opoId = (int)$opo['id'];
$totalAmt = (float)($opo['total_amount'] ?? 0);
$isPaid   = (($opo['payment_status'] ?? '') === 'paid');
$isAhDone = (strtolower($opo['accounts_head_status'] ?? '') === 'approved') || $isPaid;
$isMgmtDone = (($opo['mgmt_status'] ?? '') === 'approved') || ($isOver10K && $isPaid);
$isPayRcv = !empty($opo['assignee_payment_received_at']) || in_array($opo['status'], ['process_on_purchase', 'purchased', 'received'], true);
$isPurchased = !empty($opo['purchase_confirmed_at']) || in_array($opo['status'], ['purchased', 'received'], true);
$isAssetRcv = !empty($opo['asset_received_at']) || ($opo['status'] === 'received');

$stageTitles = [
    'purchase_head' => 'Purchase Department Head Approval',
    'qc'            => 'QC & Internal Audit Head Approval',
    'op_head'       => 'Admin & Operation Head Approval',
];

// Build visual horizontal progress steps
$progressSteps = [
    ['label' => 'PR Submitted', 'done' => true, 'by' => $opo['requested_by_name'] ?? '', 'at' => $opo['pr_created_at'] ?? ''],
    ['label' => 'Dept Head', 'done' => in_array($opo['dept_head_status'] ?? 'not_required', ['approved', 'not_required'], true), 'by' => $opo['dept_head_by_name'] ?? ($opo['dept_head_status'] === 'not_required' ? 'N/A' : ''), 'at' => $opo['dept_head_at'] ?? ''],
    ['label' => 'Admin & Ops Assign', 'done' => !empty($opo['assigned_cst_user_id']), 'by' => $opo['assigned_cst_by_name'] ?? $opo['operations_by_name'] ?? '', 'at' => $opo['assigned_cst_at'] ?? $opo['operations_at'] ?? ''],
    ['label' => 'CST & OPO', 'done' => true, 'by' => $opo['assigned_cst_name'] ?? '', 'at' => $opo['created_at'] ?? ''],
    ['label' => 'Purchase Head', 'done' => ($opo['purchase_head_status'] === 'approved'), 'active' => ($opo['status'] === 'pending_purchase_head'), 'rev' => ($opo['purchase_head_status'] === 'revision_requested'), 'by' => $opo['purchase_head_by_name'] ?? '', 'at' => $opo['purchase_head_at'] ?? ''],
    ['label' => 'QC & Audit Head', 'done' => ($opo['qc_status'] === 'approved'), 'active' => ($opo['status'] === 'pending_qc'), 'rev' => ($opo['qc_status'] === 'revision_requested'), 'by' => $opo['qc_by_name'] ?? '', 'at' => $opo['qc_at'] ?? ''],
    ['label' => 'Admin & Op Head', 'done' => ($opo['op_head_status'] === 'approved'), 'active' => ($opo['status'] === 'pending_op_head'), 'rev' => ($opo['op_head_status'] === 'revision_requested'), 'by' => $opo['op_head_by_name'] ?? '', 'at' => $opo['op_head_at'] ?? ''],
    ['label' => 'Accounts Head', 'done' => $isAhDone, 'active' => ($opo['status'] === 'approved' && !$isAhDone), 'by' => $opo['accounts_head_by_name'] ?? '', 'at' => $opo['accounts_head_at'] ?? ''],
];
if ($isOver10K) {
    $progressSteps[] = ['label' => 'Mgmt (>10K)', 'done' => $isMgmtDone, 'active' => ($opo['status'] === 'approved' && $isAhDone && !$isMgmtDone), 'by' => $opo['mgmt_by_name'] ?? '', 'at' => $opo['mgmt_at'] ?? ''];
}
$progressSteps[] = ['label' => 'Disbursed', 'done' => $isPaid, 'active' => ($opo['status'] === 'approved' && $isAhDone && (!$isOver10K || $isMgmtDone) && !$isPaid), 'by' => $opo['paid_by_name'] ?? '', 'at' => $opo['paid_at'] ?? ''];
$progressSteps[] = ['label' => 'Process on Purchase', 'done' => $isPayRcv, 'active' => ($isPaid && !$isPayRcv), 'by' => $opo['payment_received_by_name'] ?? '', 'at' => $opo['assignee_payment_received_at'] ?? ''];
$progressSteps[] = ['label' => 'Purchased & Sent to Requester', 'done' => $isPurchased, 'active' => ($isPayRcv && !$isPurchased), 'by' => $opo['purchased_by_name'] ?? '', 'at' => $opo['purchase_confirmed_at'] ?? ''];
$progressSteps[] = ['label' => 'Received Asset', 'done' => $isAssetRcv, 'active' => ($isPurchased && !$isAssetRcv), 'by' => $opo['asset_received_by_name'] ?? '', 'at' => $opo['asset_received_at'] ?? ''];
$hasRightActionCard = (
    !empty($canActOpoStage)
    || !empty($canReviseCst)
    || !empty($canApproveAh)
    || !empty($canApproveMgmt)
    || !empty($canDisburse)
    || !empty($canReceivePayment)
    || !empty($canMarkPurchased)
    || !empty($canReceiveAsset)
);

$waitingLabels = [
    'pending_purchase_head' => 'Awaiting Purchase Department Head approval',
    'pending_qc'            => 'Awaiting QC & Internal Audit Head approval',
    'pending_op_head'       => 'Awaiting Admin & Operation Head approval',
    'revision_requested'    => 'Sent back to CST Assignee for rate revision',
    'approved'              => !$isAhDone ? 'Awaiting Accounts Head approval' : (($isOver10K && !$isMgmtDone) ? 'Awaiting Management final approval (> TK10,000)' : 'Awaiting Accounts Department payment disbursement'),
    'paid'                  => 'Payment disbursed — Awaiting CST Assignee to confirm Received Payment',
    'process_on_purchase'   => 'Process on Purchase — Awaiting CST Assignee to mark Purchased',
    'purchased'             => 'Item Purchased — Awaiting Original Requester to confirm Received Asset',
    'received'              => 'Completed — Asset received, registered in Asset Tracker & synced to Accounts',
    'rejected'              => 'This OPO has been rejected',
];
$currentWaitText = $waitingLabels[$opo['status'] ?? ''] ?? 'In Progress';
?>

<style>
.opo-stepper { display: flex; overflow-x: auto; gap: 4px; padding: 10px 4px 6px; }
.opo-step { flex: 1 0 96px; text-align: center; position: relative; padding: 0 3px; }
.opo-step:not(:last-child)::after { content: ''; position: absolute; top: 15px; left: calc(50% + 17px); width: calc(100% - 30px); height: 2px; background: #e2e8f0; z-index: 0; }
.opo-step.done:not(:last-child)::after { background: #86efac; }
.opo-step-dot { width: 30px; height: 30px; border-radius: 50%; margin: 0 auto 6px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; border: 2px solid #cbd5e1; background: #f8fafc; color: #64748b; position: relative; z-index: 1; }
.opo-step.done .opo-step-dot { background: #16a34a; border-color: #16a34a; color: #fff; }
.opo-step.active .opo-step-dot { background: #f59e0b; border-color: #f59e0b; color: #fff; box-shadow: 0 0 0 4px rgba(245,158,11,0.22); }
.opo-step.rev .opo-step-dot { background: #dc2626; border-color: #dc2626; color: #fff; }
.opo-step-title { font-size: 11px; font-weight: 700; color: #1e293b; line-height: 1.25; }
.opo-step-meta { font-size: 10px; color: #64748b; margin-top: 2px; line-height: 1.2; }
.opo-kpi-tile { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; height: 100%; }
.opo-action-sticky { position: sticky; top: 76px; }
.opo-audit-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
@media (max-width: 767.98px) {
    .opo-audit-grid { grid-template-columns: 1fr; }
}
.opo-tl-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; height: 100%; display: flex; flex-direction: column; justify-content: space-between; transition: box-shadow 0.15s ease, border-color 0.15s ease; }
.opo-tl-card:hover { border-color: #cbd5e1; box-shadow: 0 3px 10px rgba(15, 23, 42, 0.06); }
.opo-tl-card.tl-success { border-left: 4px solid #16a34a; background: #fcfffd; }
.opo-tl-card.tl-warning { border-left: 4px solid #f59e0b; background: #fffdf7; }
.opo-tl-card.tl-danger  { border-left: 4px solid #dc2626; background: #fffafa; }
.opo-tl-card.tl-primary { border-left: 4px solid #2563eb; background: #f8fbff; }
.opo-tl-card.tl-info    { border-left: 4px solid #0284c7; background: #f8fcff; }
.opo-tl-icon { width: 26px; height: 26px; border-radius: 50%; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; color: #fff; }
.opo-rate-chip { display: inline-flex; flex-wrap: wrap; align-items: center; gap: 6px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 4px 9px; font-size: 11px; margin-top: 8px; }
</style>

<div class="container-fluid pt-2">
    <!-- Header Card -->
    <div class="card custom-card border-0 shadow-sm mb-3">
        <div class="card-body py-2.5 px-3 d-md-flex d-block align-items-center justify-content-between">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 fs-12">
                        <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/dashboard"><i class="fa-solid fa-house me-1"></i> Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/office-procurement/orders">Office Procurement Orders</a></li>
                        <li class="breadcrumb-item active"><?= htmlspecialchars($opo['opo_number']) ?></li>
                    </ol>
                </nav>
                <h5 class="mb-0 fw-bold text-dark d-flex align-items-center flex-wrap gap-2">
                    <span><?= htmlspecialchars($opo['opo_number']) ?></span>
                    <span class="badge bg-primary-subtle text-primary border fs-11">PR-#<?= $prId ?></span>
                    <span class="badge bg-<?= $opo['status'] === 'received' ? 'success' : ($opo['status'] === 'rejected' ? 'danger' : ($opo['status'] === 'revision_requested' ? 'warning text-dark' : 'info')) ?> fs-11 text-uppercase">
                        <?= htmlspecialchars(str_replace('_', ' ', $opo['status'])) ?>
                    </span>
                    <?php if ((int)($opo['revision_count'] ?? 0) > 0): ?>
                        <span class="badge bg-light text-danger border fs-11">Revision #<?= (int)$opo['revision_count'] ?></span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-2 mt-md-0">
                <a href="<?= $GLOBALS['base_url'] ?>/office-procurement/orders" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> All OPOs
                </a>
                <a href="<?= $GLOBALS['base_url'] ?>/asset-extra-purchase/<?= $prId ?>" class="btn btn-outline-primary btn-sm">
                    <i class="fa-solid fa-file-lines me-1"></i> View PR #<?= $prId ?>
                </a>
                <?php if ($isPaid): ?>
                    <a href="<?= $GLOBALS['base_url'] ?>/print-debit-voucher?module=project_extra_assets&id=<?= $prId ?>" target="_blank" class="btn btn-primary btn-sm shadow-sm">
                        <i class="fa-solid fa-print me-1"></i> Debit Voucher
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Current Stage Status Banner -->
    <div class="alert alert-<?= $opo['status'] === 'received' ? 'success' : ($opo['status'] === 'rejected' ? 'danger' : ($opo['status'] === 'revision_requested' ? 'warning' : 'info')) ?> py-2 px-3 mb-3 fs-12 d-flex align-items-center gap-2 shadow-sm">
        <i class="fa-solid fa-circle-info"></i>
        <span><strong>Current Stage:</strong> <?= htmlspecialchars($currentWaitText) ?></span>
    </div>

    <!-- Alerts -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
            <i class="fa-solid fa-circle-check me-2"></i><?= $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm">
            <i class="fa-solid fa-circle-exclamation me-2"></i><?= $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Visual End-to-End Progress Stepper -->
    <div class="card custom-card border-0 shadow-sm mb-3">
        <div class="card-header bg-light py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
            <span class="fw-bold fs-12 text-dark"><i class="fa-solid fa-route text-primary me-1.5"></i> Visual Procurement Lifecycle Tracker</span>
            <span class="badge <?= $isOver10K ? 'bg-warning-subtle text-dark border' : 'bg-success-subtle text-success border' ?> fs-10">
                <?= $isOver10K ? '> TK10,000 Flow (Accounts Head + Management)' : '≤ TK10,000 Flow (Accounts Head -> Disburse)' ?>
            </span>
        </div>
        <div class="card-body py-2 px-3">
            <div class="opo-stepper">
                <?php foreach ($progressSteps as $idx => $st):
                    $cls = !empty($st['rev']) ? 'rev' : (!empty($st['done']) ? 'done' : (!empty($st['active']) ? 'active' : ''));
                ?>
                <div class="opo-step <?= $cls ?>">
                    <div class="opo-step-dot">
                        <?php if (!empty($st['rev'])): ?>
                            <i class="fa-solid fa-rotate-left"></i>
                        <?php elseif (!empty($st['done'])): ?>
                            <i class="fa-solid fa-check"></i>
                        <?php elseif (!empty($st['active'])): ?>
                            <i class="fa-solid fa-hourglass-half"></i>
                        <?php else: ?>
                            <?= $idx + 1 ?>
                        <?php endif; ?>
                    </div>
                    <div class="opo-step-title"><?= htmlspecialchars($st['label']) ?></div>
                    <?php if ((!empty($st['done']) || !empty($st['rev'])) && !empty($st['by'])): ?>
                        <div class="opo-step-meta fw-semibold text-dark"><?= htmlspecialchars($st['by']) ?></div>
                    <?php endif; ?>
                    <?php if ((!empty($st['done']) || !empty($st['rev'])) && !empty($st['at'])): ?>
                        <div class="opo-step-meta"><?= date('d M, h:i A', strtotime($st['at'])) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- LEFT COLUMN: OPO Info, CST Matrix, Registered Assets & Audit Log -->
        <div class="<?= $hasRightActionCard ? 'col-lg-8' : 'col-12' ?>">
            <!-- OPO Details Card -->
            <div class="card custom-card border-0 shadow-sm mb-3">
                <div class="card-header bg-light py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold fs-13"><i class="fa-solid fa-file-contract text-primary me-1"></i> Order &amp; Vendor Summary</h6>
                    <span class="fs-12 text-muted">Order Date: <strong class="text-dark"><?= date('d M Y', strtotime($opo['order_date'])) ?></strong></span>
                </div>
                <div class="card-body p-3 fs-13">
                    <!-- Row 1: Core Reference, Item, Winning Vendor -->
                    <div class="row pb-3 mb-3 border-bottom">
                        <div class="col-md-4 mb-2 mb-md-0">
                            <span class="text-muted fs-11 text-uppercase fw-semibold d-block mb-1">OPO &amp; PR Reference</span>
                            <div class="d-flex align-items-center flex-wrap gap-1">
                                <strong class="fs-14 text-primary"><?= htmlspecialchars($opo['opo_number']) ?></strong>
                                <span class="text-muted">/</span>
                                <a href="<?= $GLOBALS['base_url'] ?>/asset-extra-purchase/<?= $prId ?>" class="badge bg-primary-subtle text-primary border text-decoration-none fs-11">PR-#<?= $prId ?></a>
                            </div>
                            <div class="text-muted fs-11 mt-1"><i class="fa-regular fa-calendar me-1"></i>PR Created: <?= !empty($opo['pr_created_at']) ? date('d M Y, h:i A', strtotime($opo['pr_created_at'])) : '—' ?></div>
                        </div>
                        <div class="col-md-4 mb-2 mb-md-0">
                            <span class="text-muted fs-11 text-uppercase fw-semibold d-block mb-1">Item &amp; Category</span>
                            <div class="d-flex align-items-center flex-wrap gap-1">
                                <strong class="fs-14 text-dark"><?= htmlspecialchars($opo['item_name']) ?></strong>
                                <?php if (!empty($opo['category_name'])): ?>
                                    <span class="badge bg-light text-secondary border fs-10"><?= htmlspecialchars($opo['category_name']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="text-muted fs-11 mt-1">Type: <strong class="text-dark"><?= htmlspecialchars($opo['request_type'] ?? 'Purchase') ?></strong></div>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted fs-11 text-uppercase fw-semibold d-block mb-1">Selected Winning Vendor</span>
                            <strong class="fs-14 text-dark"><i class="fa-solid fa-store text-warning me-1"></i><?= htmlspecialchars($opo['vendor_name'] ?? '—') ?></strong>
                            <?php if (!empty($opo['vendor_phone']) || !empty($opo['vendor_email'])): ?>
                                <div class="text-muted fs-11 mt-1">
                                    <?= htmlspecialchars($opo['vendor_phone'] ?? '') ?>
                                    <?= !empty($opo['vendor_email']) ? ' &bull; ' . htmlspecialchars($opo['vendor_email']) : '' ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Row 2: 4 Highlight KPI Tiles -->
                    <div class="row mb-3">
                        <div class="col-6 col-md-3 mb-2 mb-md-0">
                            <div class="opo-kpi-tile">
                                <span class="text-muted fs-11 d-block">Quantity</span>
                                <strong class="text-dark fs-15"><?= number_format((float)$opo['quantity'], 0) ?> <span class="fs-11 fw-normal text-muted">pcs</span></strong>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-2 mb-md-0">
                            <div class="opo-kpi-tile">
                                <span class="text-muted fs-11 d-block">Final Unit Rate</span>
                                <strong class="text-primary fs-15">TK <?= number_format((float)$opo['unit_price'], 2) ?></strong>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="opo-kpi-tile" style="background:#f0fdf4; border-color:#bbf7d0;">
                                <span class="text-success fs-11 fw-semibold d-block">OPO Total Amount</span>
                                <strong class="text-success fs-15">TK <?= number_format($totalAmt, 2) ?></strong>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="opo-kpi-tile">
                                <span class="text-muted fs-11 d-block">CST Assigned To</span>
                                <strong class="text-dark fs-13"><?= htmlspecialchars($opo['assigned_cst_name'] ?? '—') ?></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: Justification -->
                    <?php if (!empty($opo['justification'])): ?>
                    <div class="mb-3">
                        <span class="text-muted fs-11 text-uppercase fw-semibold d-block mb-1">PR Justification / Reason</span>
                        <div class="bg-light border rounded px-3 py-2 fs-12 text-dark"><?= nl2br(htmlspecialchars($opo['justification'])) ?></div>
                    </div>
                    <?php endif; ?>

                    <!-- Row 4: Requester & Documents -->
                    <div class="row pt-2 border-top">
                        <div class="col-md-6 mb-2 mb-md-0">
                            <span class="text-muted fs-11 text-uppercase fw-semibold d-block mb-1">Requested By &amp; Branch</span>
                            <strong class="text-dark"><?= htmlspecialchars($opo['requested_by_name'] ?? '—') ?></strong>
                            <span class="text-muted fs-12 ms-1">(<i class="fa-solid fa-location-dot text-danger me-1"></i><?= htmlspecialchars($opo['branch_name'] ?? $opo['asset_location'] ?? 'Head Office') ?>)</span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted fs-11 text-uppercase fw-semibold d-block mb-1">Documents</span>
                            <div class="d-flex flex-wrap gap-1">
                                <?php $hasDocs = false; ?>
                                <?php if (!empty($opo['pr_attachment'])): $hasDocs = true; ?>
                                    <a href="<?= $GLOBALS['base_url'] ?>/<?= htmlspecialchars($opo['pr_attachment']) ?>" target="_blank" class="btn btn-xs btn-outline-secondary py-0 px-2 fs-11"><i class="fa-solid fa-paperclip me-1"></i>PR Doc</a>
                                <?php endif; ?>
                                <?php if (!empty($opo['payment_attachment'])): $hasDocs = true; ?>
                                    <a href="<?= $GLOBALS['base_url'] ?>/<?= htmlspecialchars($opo['payment_attachment']) ?>" target="_blank" class="btn btn-xs btn-outline-success py-0 px-2 fs-11"><i class="fa-solid fa-receipt me-1"></i>Payment Receipt</a>
                                <?php endif; ?>
                                <?php if (!empty($opo['purchase_document'])): $hasDocs = true; ?>
                                    <a href="<?= $GLOBALS['base_url'] ?>/<?= htmlspecialchars($opo['purchase_document']) ?>" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-2 fs-11"><i class="fa-solid fa-file-invoice me-1"></i>Purchase Invoice</a>
                                <?php endif; ?>
                                <?php if (!$hasDocs): ?>
                                    <span class="text-muted fs-12">No documents attached yet</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Multi-Vendor CST Comparison Matrix -->
            <div class="card custom-card border-0 shadow-sm mb-3">
                <div class="card-header bg-light py-2.5 px-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold fs-13"><i class="fa-solid fa-scale-balanced text-primary me-1.5"></i> Multi-Vendor CST Comparison (<?= count($quotations) ?> Vendors)</h6>
                    <?php if ($canReviseCst): ?>
                        <a href="<?= $GLOBALS['base_url'] ?>/office-procurement/cst/<?= $prId ?>" class="btn btn-warning btn-sm fw-bold">
                            <i class="fa-solid fa-pen-to-square me-1"></i> Edit CST &amp; Resubmit
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 fs-12">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Vendor</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Unit Rate (TK)</th>
                                    <th class="text-end">Total (TK)</th>
                                    <th>Validity</th>
                                    <th>Quotation</th>
                                    <th>Remarks</th>
                                    <th class="text-center pe-3">Selection</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($quotations as $q): ?>
                                <tr class="<?= !empty($q['is_selected']) ? 'table-success' : '' ?>">
                                    <td class="ps-3">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($q['vendor_name'] ?? '—') ?></div>
                                        <?php if (!empty($q['vendor_phone'])): ?>
                                            <small class="text-muted fs-10"><?= htmlspecialchars($q['vendor_phone']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?= number_format((float)$q['quantity'], 0) ?></td>
                                    <td class="text-end fw-semibold">TK <?= number_format((float)$q['unit_price'], 2) ?></td>
                                    <td class="text-end fw-bold text-primary">TK <?= number_format((float)$q['total_price'], 2) ?></td>
                                    <td><?= !empty($q['validity_date']) ? date('d M Y', strtotime($q['validity_date'])) : '—' ?></td>
                                    <td>
                                        <?php if (!empty($q['attachment'])): ?>
                                            <a href="<?= $GLOBALS['base_url'] ?>/<?= htmlspecialchars($q['attachment']) ?>" target="_blank" class="badge bg-info-subtle text-info text-decoration-none">
                                                <i class="fa-solid fa-file-pdf me-1"></i>File
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($q['drive_link'])): ?>
                                            <a href="<?= htmlspecialchars($q['drive_link']) ?>" target="_blank" class="badge bg-primary-subtle text-primary text-decoration-none">
                                                <i class="fa-solid fa-link me-1"></i>Link
                                            </a>
                                        <?php endif; ?>
                                        <?php if (empty($q['attachment']) && empty($q['drive_link'])): ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($q['notes'] ?: '—') ?></td>
                                    <td class="text-center pe-3">
                                        <?php if (!empty($q['is_selected'])): ?>
                                            <span class="badge bg-success"><i class="fa-solid fa-trophy me-1"></i>Selected</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">Compared</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Registered Assets Card (Shown after Requester confirms Received Asset) -->
            <?php if (!empty($registeredAssets)): ?>
            <div class="card custom-card border-0 shadow-sm mb-3" style="border-left: 4px solid #16a34a !important;">
                <div class="card-header bg-success-transparent py-2.5 px-3 border-bottom">
                    <h6 class="mb-0 fw-bold fs-13 text-success">
                        <i class="fa-solid fa-barcode me-1.5"></i> Registered in Asset Tracker &amp; Financial Statements
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($registeredAssets as $ast): ?>
                            <a href="<?= $GLOBALS['base_url'] ?>/office-assets/show/<?= $ast['id'] ?>" class="btn btn-sm btn-outline-success fw-bold">
                                <i class="fa-solid fa-box-open me-1"></i> <?= htmlspecialchars($ast['asset_code']) ?>
                                <span class="ms-1 text-muted fw-normal">(TK<?= number_format((float)$ast['purchase_price'], 2) ?>)</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Full Chronological Activity & Revision Audit Trail -->
            <div class="card custom-card border-0 shadow-sm mb-3">
                <div class="card-header bg-light py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold fs-13"><i class="fa-solid fa-clock-rotate-left text-primary me-1"></i> Full Action, Review &amp; Timestamp Audit Trail</h6>
                    <span class="badge bg-white text-secondary border fs-11"><?= count($logs) ?> <?= count($logs) === 1 ? 'Event' : 'Events' ?></span>
                </div>
                <div class="card-body p-3">
                    <?php if (!empty($logs)): ?>
                    <div class="opo-audit-grid">
                        <?php foreach ($logs as $lg):
                            $act = $lg['action_type'] ?? '';
                            $bColor = match($act) {
                                'approved', 'asset_received' => 'success',
                                'review_requested' => 'warning',
                                'rejected' => 'danger',
                                'payment_disbursed', 'payment_received', 'purchased' => 'primary',
                                default => 'info',
                            };
                            $dotBg = match($bColor) {
                                'success' => '#16a34a',
                                'warning' => '#f59e0b',
                                'danger'  => '#dc2626',
                                'primary' => '#2563eb',
                                default   => '#0284c7',
                            };
                            $dotIcon = match($act) {
                                'approved', 'asset_received' => 'fa-check',
                                'review_requested' => 'fa-rotate-left',
                                'rejected' => 'fa-xmark',
                                'cst_submitted', 'cst_resubmitted' => 'fa-scale-balanced',
                                'assigned' => 'fa-user-check',
                                'payment_disbursed', 'payment_received' => 'fa-bangladeshi-taka-sign',
                                'purchased' => 'fa-bag-shopping',
                                default => 'fa-circle-info',
                            };
                            $actLabel = strtoupper(str_replace('_', ' ', $act));
                            $oldRate = (float)($lg['old_unit_price'] ?? 0);
                            $sugRate = (float)($lg['suggested_unit_price'] ?? 0);
                        ?>
                        <div class="opo-tl-card tl-<?= $bColor ?>">
                            <div>
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="opo-tl-icon" style="background: <?= $dotBg ?>;">
                                            <i class="fa-solid <?= $dotIcon ?>"></i>
                                        </span>
                                        <span class="badge bg-<?= $bColor ?> <?= $bColor === 'warning' ? 'text-dark' : '' ?> fs-10 px-2 py-1"><?= htmlspecialchars($actLabel) ?></span>
                                    </div>
                                    <div class="text-muted fs-10 text-nowrap d-inline-flex align-items-center gap-1" style="background:#f1f5f9; padding:3px 8px; border-radius:20px;">
                                        <i class="fa-regular fa-clock"></i>
                                        <span><?= date('d M Y, h:i A', strtotime($lg['acted_at'])) ?></span>
                                    </div>
                                </div>

                                <div class="mt-2 d-flex align-items-center flex-wrap gap-2">
                                    <span class="fw-bold text-dark fs-13"><?= htmlspecialchars($lg['stage_name']) ?></span>
                                    <span class="badge bg-light text-secondary border fw-normal fs-11">
                                        <i class="fa-regular fa-user me-1"></i><?= htmlspecialchars($lg['acted_by_name'] ?? 'User') ?>
                                    </span>
                                </div>
                            </div>

                            <?php if (!empty($lg['note']) || ($sugRate > 0)): ?>
                            <div class="mt-2 pt-2 border-top">
                                <?php if (!empty($lg['note'])): ?>
                                    <div class="fs-12 text-dark" style="line-height: 1.45; word-break: break-word;"><?= nl2br(htmlspecialchars($lg['note'])) ?></div>
                                <?php endif; ?>
                                <?php if ($sugRate > 0):
                                    $diff = $sugRate - $oldRate;
                                ?>
                                    <div class="opo-rate-chip">
                                        <span class="text-muted">Rate Review:</span>
                                        <span class="text-decoration-line-through text-secondary">TK <?= number_format($oldRate, 2) ?></span>
                                        <i class="fa-solid fa-arrow-right text-danger fs-10"></i>
                                        <strong class="text-danger">TK <?= number_format($sugRate, 2) ?></strong>
                                        <?php if ($oldRate > 0 && $diff != 0): ?>
                                            <span class="badge <?= $diff < 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> fs-10">
                                                <?= $diff < 0 ? '−' : '+' ?>TK<?= number_format(abs($diff), 2) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                        <p class="text-muted fs-12 mb-0">No activity logs recorded yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Action Cards -->
        <?php if ($hasRightActionCard): ?>
        <div class="col-lg-4">
            <div class="opo-action-sticky">
            <!-- 1. OPO Stage Action Card (Purchase Head / QC / Admin & Op Head) -->
            <?php if (!empty($canActOpoStage)): ?>
            <div class="card custom-card border-0 shadow-sm mb-3" style="border-top: 3px solid #2563eb !important;">
                <div class="card-header bg-white py-2 px-3 border-bottom">
                    <h6 class="fw-bold fs-13 text-primary mb-0">
                        <i class="fa-solid fa-gavel me-1"></i> <?= htmlspecialchars($stageTitles[$canActOpoStage] ?? 'OPO Review') ?>
                    </h6>
                </div>
                <div class="card-body p-3">
                    <p class="fs-12 text-muted mb-3">
                        Approve this OPO, send it back to the CST Assignee with a suggested rate, or reject it.
                    </p>
                    <form action="<?= $GLOBALS['base_url'] ?>/office-procurement/order/action/<?= $opoId ?>" method="POST" id="opoActionForm">
                        <input type="hidden" name="stage" value="<?= htmlspecialchars($canActOpoStage) ?>">
                        <input type="hidden" name="action" id="opoActionField" value="approved">

                        <div class="mb-3">
                            <label class="form-label fs-11 fw-semibold text-dark mb-1">Suggested Unit Rate (TK) <span class="text-muted fw-normal">(Only for Review)</span></label>
                            <input type="number" step="0.01" min="0" name="suggested_unit_price" class="form-control form-control-sm" placeholder="Current: <?= number_format((float)$opo['unit_price'], 2) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-11 fw-semibold text-dark mb-1">Approval / Review / Rejection Remarks</label>
                            <textarea name="note" class="form-control form-control-sm" rows="3" placeholder="Enter remarks or rate negotiation instructions..."></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-sm fw-bold shadow-sm py-2" onclick="document.getElementById('opoActionField').value='approved';">
                                <i class="fa-solid fa-check me-1"></i> Approve OPO
                            </button>
                            <button type="submit" class="btn btn-warning btn-sm fw-bold text-dark py-2" onclick="document.getElementById('opoActionField').value='revision_requested';">
                                <i class="fa-solid fa-rotate-left me-1"></i> Send Back for Rate Review (Edit CST)
                            </button>
                            <button type="submit" class="btn btn-outline-danger btn-sm py-2" onclick="document.getElementById('opoActionField').value='rejected'; return confirm('Are you sure you want to REJECT this OPO?');">
                                <i class="fa-solid fa-xmark me-1"></i> Reject OPO
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- 2. Revision Requested Card (For CST Assignee) -->
            <?php if ($canReviseCst): ?>
            <div class="card custom-card border-0 shadow-sm mb-3" style="border-left:4px solid #f59e0b !important; background:#fffbeb;">
                <div class="card-body p-3">
                    <h6 class="fw-bold fs-13 text-warning mb-1">
                        <i class="fa-solid fa-rotate-left me-1"></i> Action Required: Revise CST
                    </h6>
                    <p class="fs-12 text-dark mb-3">
                        An approver requested a rate review. Update the CST vendor rates and resubmit to restart OPO approval.
                    </p>
                    <a href="<?= $GLOBALS['base_url'] ?>/office-procurement/cst/<?= $prId ?>" class="btn btn-warning btn-sm w-100 fw-bold text-dark shadow-sm">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit CST &amp; Resubmit OPO
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- 3. Accounts Head Verification Card (Role 30) -->
            <?php if ($canApproveAh): ?>
            <div class="card custom-card border-0 shadow-sm mb-3" style="border-left:4px solid #2563eb !important; background:#eff6ff;">
                <div class="card-body p-3">
                    <h6 class="fw-bold fs-13 text-primary mb-1"><i class="fa-solid fa-stamp me-1"></i> Accounts Head Approval</h6>
                    <p class="fs-12 text-secondary mb-3">
                        OPO is approved by Admin &amp; Operation Head. Verify &amp; approve as Accounts Head to <?= $isOver10K ? 'forward to Management (> TK10,000).' : 'unlock Accounts disbursement (≤ TK10,000).' ?>
                    </p>
                    <button type="button" class="btn btn-primary btn-sm w-100 fw-bold shadow-sm" onclick="triggerAhApprove(<?= (int)$opo['approval_request_id'] ?>)">
                        <i class="fa-solid fa-stamp me-1"></i> Verify &amp; Approve (Accounts Head)
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- 4. Management Final Approval Card (> 10,000) -->
            <?php if ($canApproveMgmt): ?>
            <div class="card custom-card border-0 shadow-sm mb-3" style="border-left:4px solid #16a34a !important; background:#f0fdf4;">
                <div class="card-body p-3">
                    <h6 class="fw-bold fs-13 text-success mb-1"><i class="fa-solid fa-shield-check me-1"></i> Management Final Approval (&gt; TK10,000)</h6>
                    <p class="fs-12 text-secondary mb-3">
                        Accounts Head has verified this payment request. Management approval is required before Accounts can disburse.
                    </p>
                    <form action="<?= $GLOBALS['base_url'] ?>/asset-extra-purchases/approve/<?= $prId ?>?redirect=opo" method="POST">
                        <input type="hidden" name="step" value="mgmt">
                        <input type="hidden" name="approval_status" value="approved">
                        <input type="hidden" name="approve_mode" value="override">
                        <textarea name="approval_note" class="form-control form-control-sm mb-2" rows="2" placeholder="Management remarks (optional)..."></textarea>
                        <button type="submit" class="btn btn-success btn-sm w-100 fw-bold shadow-sm">
                            <i class="fa-solid fa-check-double me-1"></i> Final Approve for Disbursement
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- 5. Accounts Payment Disbursement Card -->
            <?php if ($canDisburse): ?>
            <div class="card custom-card border-0 shadow-sm mb-3" style="border-left:4px solid #16a34a !important; background:#f0fdf4;">
                <div class="card-body p-3">
                    <h6 class="fw-bold fs-13 text-success mb-1"><i class="fa-solid fa-money-bill-wave me-1"></i> Accounts Payment Disbursement</h6>
                    <p class="fs-12 text-secondary mb-3">
                        All approvals complete. Disburse <strong>TK <?= number_format($totalAmt, 2) ?></strong> to CST Assignee (<?= htmlspecialchars($opo['assigned_cst_name'] ?? '') ?>).
                    </p>
                    <button type="button" class="btn btn-success btn-sm w-100 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#opoDisburseModal">
                        <i class="fa-solid fa-money-bill-wave me-1"></i> Disburse Payment
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- 6. CST Assignee: Received Payment -> Process on Purchase -->
            <?php if ($canReceivePayment): ?>
            <div class="card custom-card border-0 shadow-sm mb-3" style="border-left:4px solid #0284c7 !important; background:#f0f9ff;">
                <div class="card-body p-3">
                    <h6 class="fw-bold fs-13 text-primary mb-1"><i class="fa-solid fa-hand-holding-dollar me-1"></i> Confirm Payment Received</h6>
                    <p class="fs-12 text-secondary mb-3">
                        Accounts has disbursed <strong>TK <?= number_format((float)($opo['paid_amount'] ?? $totalAmt), 2) ?></strong>. Click below once you receive the money to set status to <strong>Process on Purchase</strong>.
                    </p>
                    <form action="<?= $GLOBALS['base_url'] ?>/office-procurement/order/receive-payment/<?= $prId ?>" method="POST">
                        <input type="hidden" name="redirect_to" value="<?= $GLOBALS['base_url'] ?>/office-procurement/order/<?= $opoId ?>">
                        <input type="text" name="note" class="form-control form-control-sm mb-2" placeholder="Optional note...">
                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold shadow-sm">
                            <i class="fa-solid fa-check-circle me-1"></i> Received Payment (Start Purchase)
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- 7. CST Assignee: Mark Purchased -->
            <?php if ($canMarkPurchased): ?>
            <div class="card custom-card border-0 shadow-sm mb-3" style="border-left:4px solid #7c3aed !important; background:#f5f3ff;">
                <div class="card-body p-3">
                    <h6 class="fw-bold fs-13 mb-1" style="color:#6d28d9;"><i class="fa-solid fa-bag-shopping me-1"></i> Purchased &amp; Send to Requester</h6>
                    <p class="fs-12 text-secondary mb-3">
                        Once you finish buying the item from the vendor, upload the invoice/bill and mark it as <strong>Purchased &amp; Sent to Requester</strong>.
                    </p>
                    <form action="<?= $GLOBALS['base_url'] ?>/office-procurement/order/mark-purchased/<?= $prId ?>" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="redirect_to" value="<?= $GLOBALS['base_url'] ?>/office-procurement/order/<?= $opoId ?>">
                        <div class="mb-2">
                            <label class="form-label fs-11 fw-semibold mb-1">Purchase Date <span class="text-danger">*</span></label>
                            <input type="date" name="purchase_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fs-11 fw-semibold mb-1">Vendor Invoice / Bill (Optional)</label>
                            <input type="file" name="purchase_document" class="form-control form-control-sm">
                        </div>
                        <div class="mb-2">
                            <label class="form-label fs-11 fw-semibold mb-1">Return Balance (TK, if any saved)</label>
                            <input type="number" step="0.01" min="0" name="return_amount" class="form-control form-control-sm" placeholder="0.00">
                        </div>
                        <div class="mb-2">
                            <textarea name="purchase_note" class="form-control form-control-sm" rows="2" placeholder="Purchase remarks / serial numbers..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-sm w-100 fw-bold text-white shadow-sm" style="background:#7c3aed;">
                            <i class="fa-solid fa-box-check me-1"></i> Mark Purchased &amp; Send to Requester
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- 8. Original Requester: Received Asset -> Asset Tracker + Financial Statements -->
            <?php if ($canReceiveAsset): ?>
            <div class="card custom-card border-0 shadow-sm mb-3" style="border-left:4px solid #16a34a !important; background:#f0fdf4;">
                <div class="card-body p-3">
                    <h6 class="fw-bold fs-13 text-success mb-1"><i class="fa-solid fa-box-open me-1"></i> Confirm Asset Received</h6>
                    <p class="fs-12 text-secondary mb-3">
                        Item has been purchased! Click <strong>Received Asset</strong> to confirm delivery, auto-generate an Asset Tracker code, and finalize Accounts/Financial Statements.
                    </p>
                    <form action="<?= $GLOBALS['base_url'] ?>/office-procurement/order/receive-asset/<?= $prId ?>" method="POST">
                        <input type="hidden" name="redirect_to" value="<?= $GLOBALS['base_url'] ?>/office-procurement/order/<?= $opoId ?>">
                        <textarea name="asset_received_note" class="form-control form-control-sm mb-2" rows="2" placeholder="Condition / receipt remarks (optional)..."></textarea>
                        <button type="submit" class="btn btn-success btn-sm w-100 fw-bold shadow-sm py-2">
                            <i class="fa-solid fa-circle-check me-1"></i> Received Asset
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL: Accounts Disbursement -->
<div class="modal fade" id="opoDisburseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success-transparent py-2.5 px-3">
                <h6 class="modal-title fw-semibold text-success"><i class="fa-solid fa-money-bill-wave me-1.5"></i>Disburse OPO Payment</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3 fs-13">
                <form action="<?= $GLOBALS['base_url'] ?>/asset-extra-purchases/purchased/<?= $prId ?>?redirect=opo" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="redirect" value="opo">
                    <div class="mb-3">
                        <label class="form-label fs-12 fw-semibold">Disbursement Amount (TK) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="paid_amount" class="form-control form-control-sm" required value="<?= number_format($totalAmt, 2, '.', '') ?>" max="<?= number_format($totalAmt, 2, '.', '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fs-12 fw-semibold">Payment Account (Chart of Accounts) <span class="text-danger">*</span></label>
                        <select name="payment_account_id" class="form-select form-select-sm" required>
                            <option value="">-- Select Cash or Bank Account --</option>
                            <?php foreach ($bankAccounts as $bAcc): ?>
                                <option value="<?= $bAcc['id'] ?>"><?= htmlspecialchars($bAcc['account_code'] . ' - ' . $bAcc['account_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fs-12 fw-semibold">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select form-select-sm" required>
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cheque">Cheque</option>
                            <option value="bKash">bKash</option>
                            <option value="Nagad">Nagad</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fs-12 fw-semibold">Payment Receipt / Voucher (Optional)</label>
                        <input type="file" name="payment_attachment" class="form-control form-control-sm">
                    </div>
                    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm px-4 fw-bold">Confirm Disbursement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../layouts/accounts_head_approval_card.php'; ?>

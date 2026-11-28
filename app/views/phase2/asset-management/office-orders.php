<?php
$statusBadges = [
    'pending_purchase_head' => ['label' => 'Pending Purchase Head', 'class' => 'bg-warning-subtle text-dark border border-warning-subtle'],
    'pending_qc'            => ['label' => 'Pending QC & Audit', 'class' => 'bg-info-subtle text-info border border-info-subtle'],
    'pending_op_head'       => ['label' => 'Pending Admin & Ops Head', 'class' => 'bg-primary-subtle text-primary border border-primary-subtle'],
    'revision_requested'    => ['label' => 'Revision Requested (CST)', 'class' => 'bg-danger-subtle text-danger border border-danger-subtle'],
    'approved'              => ['label' => 'OPO Approved (Payment Pending)', 'class' => 'bg-success-subtle text-success border border-success-subtle'],
    'paid'                  => ['label' => 'Disbursed (Confirm Receipt)', 'class' => 'bg-primary text-white'],
    'process_on_purchase'   => ['label' => 'Process on Purchase', 'class' => 'bg-info text-white'],
    'purchased'             => ['label' => 'Purchased (Awaiting Asset Receipt)', 'class' => 'bg-purple text-white'],
    'received'              => ['label' => 'Received & Registered', 'class' => 'bg-success text-white'],
    'rejected'              => ['label' => 'Rejected', 'class' => 'bg-danger text-white'],
];
?>

<style>
.bg-purple { background-color: #6f42c1 !important; }
</style>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-3 page-header-breadcrumb">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/dashboard"><i class="fa-solid fa-house me-1"></i> Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/asset-extra-purchases">Office Procurement</a></li>
                    <li class="breadcrumb-item active">Office Procurement Orders (OPO)</li>
                </ol>
            </nav>
            <h4 class="mb-0 fw-bold text-dark">
                <i class="fa-solid fa-file-invoice-dollar text-primary me-1.5"></i> Office Procurement Orders (OPO)
            </h4>
        </div>
        <div class="d-flex gap-2 mt-2 mt-md-0">
            <a href="<?= $GLOBALS['base_url'] ?>/asset-extra-purchases" class="btn btn-outline-primary btn-sm fw-semibold">
                <i class="fa-solid fa-list-check me-1"></i> Office PR Requests
            </a>
            <a href="<?= $GLOBALS['base_url'] ?>/office-assets" class="btn btn-outline-secondary btn-sm fw-semibold">
                <i class="fa-solid fa-boxes-stacked me-1"></i> Asset Tracker
            </a>
        </div>
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

    <!-- KPI Cards -->
    <div class="row  mb-3">
        <div class="col-sm-6 col-xl-3">
            <div class="card custom-card border-0 shadow-sm mb-0 p-3 bg-white" style="border-left:4px solid #2563eb !important;">
                <span class="text-muted fs-11 fw-semibold text-uppercase">Total OPOs</span>
                <h4 class="fw-bold text-primary mb-0 mt-1"><?= (int)($kpis['total_opos'] ?? 0) ?></h4>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card custom-card border-0 shadow-sm mb-0 p-3 bg-white" style="border-left:4px solid #f59e0b !important;">
                <span class="text-muted fs-11 fw-semibold text-uppercase">In Approval / Revision</span>
                <h4 class="fw-bold text-warning mb-0 mt-1"><?= (int)($kpis['pending_review'] ?? 0) ?></h4>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card custom-card border-0 shadow-sm mb-0 p-3 bg-white" style="border-left:4px solid #16a34a !important;">
                <span class="text-muted fs-11 fw-semibold text-uppercase">Completed &amp; Received</span>
                <h4 class="fw-bold text-success mb-0 mt-1"><?= (int)($kpis['received_done'] ?? 0) ?></h4>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card custom-card border-0 shadow-sm mb-0 p-3 bg-white" style="border-left:4px solid #0d9488 !important;">
                <span class="text-muted fs-11 fw-semibold text-uppercase">Total OPO Value</span>
                <h4 class="fw-bold text-dark mb-0 mt-1">TK <?= number_format((float)($kpis['total_value'] ?? 0), 2) ?></h4>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card custom-card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
            <form method="GET" action="<?= $GLOBALS['base_url'] ?>/office-procurement/orders" class="row g-2 align-items-center">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($activeTab) ?>">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="Search OPO#, Item, Vendor...">
                </div>
                <div class="col-md-3">
                    <select name="vendor_id" class="form-select form-select-sm">
                        <option value="">All Vendors</option>
                        <?php foreach ($vendors as $v): ?>
                            <option value="<?= $v['id'] ?>" <?= ((string)($filters['vendor_id'] ?? '') === (string)$v['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($v['display_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All OPO Statuses</option>
                        <?php foreach ($statusBadges as $sKey => $sMeta): ?>
                            <option value="<?= $sKey ?>" <?= (($filters['status'] ?? '') === $sKey) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sMeta['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                    <a href="<?= $GLOBALS['base_url'] ?>/office-procurement/orders?tab=<?= htmlspecialchars($activeTab) ?>" class="btn btn-outline-secondary btn-sm flex-fill">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabs + Table -->
    <div class="card custom-card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom pt-3 pb-0 px-3">
            <ul class="nav nav-tabs nav-tabs-bordered">
                <li class="nav-item">
                    <a class="nav-link fw-semibold <?= $activeTab === 'pending_my_action' ? 'active' : '' ?>" href="?tab=pending_my_action">
                        <i class="fa-solid fa-bell me-1 text-warning"></i> Pending My Action
                        <?php if ($pendingCount > 0): ?>
                            <span class="badge bg-warning text-dark rounded-pill ms-1"><?= $pendingCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold <?= $activeTab === 'revision_requested' ? 'active' : '' ?>" href="?tab=revision_requested">
                        <i class="fa-solid fa-rotate-left me-1 text-danger"></i> Rate Revisions
                        <?php if ($revCount > 0): ?>
                            <span class="badge bg-danger rounded-pill ms-1"><?= $revCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold <?= $activeTab === 'all' ? 'active' : '' ?>" href="?tab=all">
                        <i class="fa-solid fa-list me-1"></i> All OPOs
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold <?= $activeTab === 'my_orders' ? 'active' : '' ?>" href="?tab=my_orders">
                        <i class="fa-solid fa-user me-1"></i> My OPOs
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 fs-13">
                    <thead class="table-light fs-12">
                        <tr>
                            <th class="ps-3">OPO #</th>
                            <th>PR # / Date</th>
                            <th>Item &amp; Location</th>
                            <th>Selected Vendor</th>
                            <th class="text-end">Total (TK)</th>
                            <th>CST Assignee</th>
                            <th class="text-center">OPO Approvals</th>
                            <th>Current Status</th>
                            <th class="text-center pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($orders)): ?>
                            <?php foreach ($orders as $o):
                                $st = $o['status'] ?? 'pending_purchase_head';
                                $stMeta = $statusBadges[$st] ?? ['label' => ucfirst($st), 'class' => 'bg-secondary text-white'];
                            ?>
                            <tr>
                                <td class="ps-3">
                                    <a href="<?= $GLOBALS['base_url'] ?>/office-procurement/order/<?= $o['id'] ?>" class="fw-bold text-primary text-decoration-none">
                                        <?= htmlspecialchars($o['opo_number']) ?>
                                    </a>
                                    <?php if ((int)($o['revision_count'] ?? 0) > 0): ?>
                                        <br><span class="badge bg-light text-danger border fs-10">Rev #<?= (int)$o['revision_count'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= $GLOBALS['base_url'] ?>/asset-extra-purchase/<?= $o['extra_purchase_id'] ?>" class="text-dark fw-semibold text-decoration-none">
                                        PR-#<?= $o['extra_purchase_id'] ?>
                                    </a>
                                    <div class="text-muted fs-11"><?= date('d M Y', strtotime($o['order_date'])) ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($o['item_name']) ?></div>
                                    <small class="text-muted fs-11">
                                        <?= number_format((float)$o['quantity'], 0) ?> pcs &times; TK<?= number_format((float)$o['unit_price'], 2) ?>
                                        &bull; <?= htmlspecialchars($o['location_name'] ?? 'Head Office') ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark"><i class="fa-solid fa-store text-warning me-1"></i><?= htmlspecialchars($o['vendor_name'] ?? '—') ?></span>
                                </td>
                                <td class="text-end fw-bold text-dark">
                                    TK <?= number_format((float)$o['total_amount'], 2) ?>
                                    <?php if (($o['payment_status'] ?? '') === 'paid'): ?>
                                        <div class="text-success fs-10">Paid: TK<?= number_format((float)($o['paid_amount'] ?? $o['total_amount']), 2) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold fs-12"><?= htmlspecialchars($o['assigned_cst_name'] ?? '—') ?></div>
                                    <small class="text-muted fs-10">Req: <?= htmlspecialchars($o['requested_by_name'] ?? '—') ?></small>
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-1">
                                        <span class="badge fs-10 <?= $o['purchase_head_status'] === 'approved' ? 'bg-success' : ($o['purchase_head_status'] === 'revision_requested' ? 'bg-warning text-dark' : ($o['purchase_head_status'] === 'rejected' ? 'bg-danger' : 'bg-light text-muted border')) ?>" title="Purchase Head">PH</span>
                                        <span class="badge fs-10 <?= $o['qc_status'] === 'approved' ? 'bg-success' : ($o['qc_status'] === 'revision_requested' ? 'bg-warning text-dark' : ($o['qc_status'] === 'rejected' ? 'bg-danger' : 'bg-light text-muted border')) ?>" title="QC & Internal Audit (Role 24)">QC</span>
                                        <span class="badge fs-10 <?= $o['op_head_status'] === 'approved' ? 'bg-success' : ($o['op_head_status'] === 'revision_requested' ? 'bg-warning text-dark' : ($o['op_head_status'] === 'rejected' ? 'bg-danger' : 'bg-light text-muted border')) ?>" title="Admin & Operation Head (Role 20)">OP</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?= $stMeta['class'] ?> fs-11 px-2 py-1"><?= htmlspecialchars($stMeta['label']) ?></span>
                                </td>
                                <td class="text-center pe-3 text-nowrap">
                                    <a href="<?= $GLOBALS['base_url'] ?>/office-procurement/order/<?= $o['id'] ?>" class="btn btn-sm btn-primary px-2.5">
                                        <i class="fa-solid fa-eye me-1"></i> View / Action
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-inbox fs-2 d-block mb-2"></i>
                                    No Office Procurement Orders found in this tab.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

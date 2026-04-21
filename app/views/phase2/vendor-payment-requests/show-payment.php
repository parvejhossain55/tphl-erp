<div class="pagetitle d-flex justify-content-between align-items-center">
    <div>
        <h1><?= htmlspecialchars($page_title) ?></h1>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-vendor-payment-requests">Payment Requests</a></li>
                <li class="breadcrumb-item active">VPR-<?= $request['id'] ?></li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $base_url ?>/con-vendor-payment-requests" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>
</div>

<?php
// Payment type badge config
$typeBadge = match($request['payment_type'] ?? 'Final') {
    'Advance' => ['class' => 'bg-primary',  'icon' => 'bi-clock',           'label' => 'Advance'],
    'Partial' => ['class' => 'bg-warning text-dark', 'icon' => 'bi-hourglass-split', 'label' => 'Partial'],
    default   => ['class' => 'bg-success',  'icon' => 'bi-check-circle',    'label' => 'Final'],
};
$statusBadge = match($request['status'] ?? 'Draft') {
    'Approved'  => 'bg-success',
    'Submitted' => 'bg-info',
    'Rejected'  => 'bg-danger',
    'Paid'      => 'bg-dark',
    default     => 'bg-warning text-dark',
};

// Use actual column names from DB
$penaltyAmt   = (float)($request['penalty_amount']      ?? 0);
$netPayable   = (float)($request['net_payable']         ?? 0);
$invoiceAmt   = (float)($request['invoice_amount']      ?? 0);
$createdAt    = $request['created_at'] ?? null;
?>

<section class="section">
    <div class="row">
        <!-- Main Details -->
        <div class="col-lg-8">
            <div class="card__wrapper shadow-sm mb-4">
                <div class="card__header d-flex justify-content-between align-items-center">
                    <h6 class="card__title mb-0">
                        <i class="bi bi-receipt me-2"></i>Request Details (VPR-<?= $request['id'] ?>)
                    </h6>
                    <div class="d-flex gap-2">
                        <span class="badge <?= $typeBadge['class'] ?>">
                            <i class="bi <?= $typeBadge['icon'] ?> me-1"></i><?= $typeBadge['label'] ?> Payment
                        </span>
                        <span class="badge <?= $statusBadge ?>">
                            <?= htmlspecialchars($request['status'] ?? '') ?>
                        </span>
                    </div>
                </div>
                <div class="card__body p-4">
                    <table class="table table-bordered mb-0">
                        <tbody>
                            <tr>
                                <th style="width: 30%" class="">Project</th>
                                <td class="fw-bold"><?= htmlspecialchars($request['project_name'] ?? '—') ?></td>
                            </tr>
                            <tr>
                                <th class="">Vendor</th>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($request['vendor_name'] ?? '—') ?></span></td>
                            </tr>
                            <?php if (!empty($request['purchase_order_id']) && $request['purchase_order_id'] > 0): ?>
                            <tr>
                                <th class="">Purchase Order</th>
                                <td>
                                    <a href="<?= $base_url ?>/con-purchase-order/<?= $request['purchase_order_id'] ?>">
                                        PO-<?= $request['purchase_order_id'] ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th class="">Invoice Number</th>
                                <td><?= htmlspecialchars($request['invoice_number'] ?? '—') ?></td>
                            </tr>
                            <tr>
                                <th class="">Payment Type</th>
                                <td>
                                    <span class="badge <?= $typeBadge['class'] ?>">
                                        <?= $typeBadge['label'] ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th class="">Delivery Verified</th>
                                <td>
                                    <?php if ($request['delivery_verified']): ?>
                                        <span class="badge bg-success"><i class="bi bi-check me-1"></i>Yes</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="bi bi-x me-1"></i>Not Yet</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="">QC Verified</th>
                                <td>
                                    <?php if ($request['qc_verified']): ?>
                                        <span class="badge bg-success"><i class="bi bi-check me-1"></i>Yes</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="bi bi-x me-1"></i>Not Yet</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="">Created By</th>
                                <td><?= htmlspecialchars($request['created_by_name'] ?? '—') ?>
                                    <?php if ($createdAt): ?>
                                        <span class="text-muted small">(<?= date('d M Y H:i', strtotime($createdAt)) ?>)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Linked Penalties -->
            <?php if (!empty($penalties)): ?>
            <div class="card__wrapper shadow-sm mb-4">
                <div class="card__header">
                    <h6 class="card__title mb-0 text-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>Deducted Penalties
                    </h6>
                </div>
                <div class="card__body p-3">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Penalty ID</th><th>Date</th><th>Reason</th>
                                <th class="text-end">Amount (৳)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($penalties as $p): ?>
                            <tr>
                                <td>PEN-<?= $p['id'] ?></td>
                                <td><?= !empty($p['penalty_date']) ? date('d M Y', strtotime($p['penalty_date'])) : '—' ?></td>
                                <td><?= htmlspecialchars($p['reason'] ?? '') ?></td>
                                <td class="text-end text-danger"><?= number_format($p['penalty_amount'] ?? 0, 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- PO Payment History -->
            <?php if (!empty($po_payments) && count($po_payments) > 1): ?>
            <div class="card__wrapper shadow-sm mb-4">
                <div class="card__header">
                    <h6 class="card__title mb-0">
                        <i class="bi bi-clock-history me-2"></i>PO Payment History
                    </h6>
                </div>
                <div class="card__body p-3">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>VPR</th><th>Type</th><th>Created</th>
                                <th class="text-end">Invoice</th>
                                <th class="text-end">Net Payable</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($po_payments as $pp): ?>
                            <tr class="<?= $pp['id'] == $request['id'] ? 'table-primary fw-bold' : '' ?>">
                                <td>VPR-<?= $pp['id'] ?></td>
                                <td>
                                    <span class="badge <?= $pp['payment_type'] === 'Advance' ? 'bg-primary' : ($pp['payment_type'] === 'Partial' ? 'bg-warning text-dark' : 'bg-success') ?>">
                                        <?= $pp['payment_type'] ?>
                                    </span>
                                </td>
                                <td><?= !empty($pp['created_at']) ? date('d M Y', strtotime($pp['created_at'])) : '—' ?></td>
                                <td class="text-end">৳ <?= number_format($pp['invoice_amount'] ?? 0, 2) ?></td>
                                <td class="text-end">৳ <?= number_format($pp['net_payable'] ?? 0, 2) ?></td>
                                <td><span class="badge <?= ($pp['status'] === 'Paid') ? 'bg-dark' : (($pp['status'] === 'Approved') ? 'bg-success' : 'bg-warning text-dark') ?>">
                                    <?= $pp['status'] ?>
                                </span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar Financials -->
        <div class="col-lg-4">
            <div class="card__wrapper shadow-sm border-top border-primary border-4 mb-4">
                <div class="card__body p-4">
                    <h6 class="text-uppercase text-muted fw-bold mb-4 text-center">Financial Summary</h6>

                    <?php if (($request['payment_type'] ?? '') === 'Advance'): ?>
                        <div class="text-center mb-3">
                            <span class="badge bg-primary px-3 py-2 fs-6">Advance Payment</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Advance Amount</span>
                            <span class="fw-bold fs-5">৳ <?= number_format($invoiceAmt, 2) ?></span>
                        </div>
                        <div class="alert alert-info small py-2">
                            <i class="bi bi-info-circle me-1"></i>
                            This advance will be auto-deducted from the Final invoice for the same PO.
                        </div>
                    <?php else: ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Invoice Amount</span>
                            <span class="fw-bold fs-5">৳ <?= number_format($invoiceAmt, 2) ?></span>
                        </div>

                        <?php if ($advance_paid > 0): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2 text-primary">
                            <span><i class="bi bi-dash-circle me-1"></i>Advance Paid</span>
                            <span class="fw-bold">৳ <?= number_format($advance_paid, 2) ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if ($penaltyAmt > 0): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2 text-danger">
                            <span><i class="bi bi-dash-circle me-1"></i>Penalties</span>
                            <span class="fw-bold">৳ <?= number_format($penaltyAmt, 2) ?></span>
                        </div>
                        <?php endif; ?>

                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-success fw-bold fs-5">Net Payable</span>
                            <span class="fw-bold fs-3 text-success">৳ <?= number_format($netPayable, 2) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Approval Action -->
            <?php if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 2, 3])): ?>
                <?php if (($request['status'] ?? '') === 'Submitted'): ?>
                    <div class="card__wrapper shadow-sm border-warning mb-3">
                        <div class="card__body p-4 text-center">
                            <h6 class="fw-bold mb-3"><i class="bi bi-shield-lock me-2"></i>Accounts Action</h6>
                            <p class="small text-muted mb-4">Review the financial summary before approving this payment.</p>
                            <form action="<?= $base_url ?>/con-vendor-payment-approve/<?= $request['id'] ?>"
                                  method="POST" class="d-grid gap-2 mb-2">
                                <button type="submit" class="btn btn-success fw-bold py-2"
                                        onclick="return confirm('Approve this payment request?');">
                                    <i class="bi bi-check-circle me-1"></i> Approve Payment
                                </button>
                            </form>
                            <form action="<?= $base_url ?>/con-vendor-payment-reject/<?= $request['id'] ?>"
                                  method="POST" class="d-grid">
                                <button type="submit" class="btn btn-outline-danger btn-sm"
                                        onclick="return confirm('Reject this payment request?');">
                                    <i class="bi bi-x-circle me-1"></i> Reject
                                </button>
                            </form>
                        </div>
                    </div>
                <?php elseif (($request['status'] ?? '') === 'Approved'): ?>
                    <div class="alert alert-success text-center">
                        <i class="bi bi-check-circle-fill fs-4 d-block mb-2"></i>
                        Approved — awaiting payment processing in
                        <a href="<?= $base_url ?>/con-pending-payments">Pending Payments</a>.
                    </div>
                <?php elseif (($request['status'] ?? '') === 'Paid'): ?>
                    <div class="alert alert-dark text-center">
                        <i class="bi bi-cash-coin fs-4 d-block mb-2"></i>
                        Payment has been processed and recorded.
                    </div>
                <?php elseif (($request['status'] ?? '') === 'Rejected'): ?>
                    <div class="alert alert-danger text-center">
                        <i class="bi bi-x-circle-fill fs-4 d-block mb-2"></i>
                        This payment request has been rejected.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

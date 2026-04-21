<?php
// Determine display label and base amounts
$isVendorPayment = ($ref_type === 'vendor_payment');
$isContractorBill = ($ref_type === 'contractor_bill');

if ($isVendorPayment) {
    $refLabel     = ($source['payment_type'] ?? 'Final') . ' Payment — ' . htmlspecialchars($source['vendor_name'] ?? '');
    $invoiceAmt   = (float)($source['invoice_amount'] ?? 0);
    $vprPayType   = $source['payment_type'] ?? 'Final';
} elseif ($isContractorBill) {
    $refLabel   = 'Contractor Bill';
    $invoiceAmt = (float)($source['bill_amount'] ?? 0);
    $vprPayType = 'Full';
} else {
    $refLabel   = 'Operational Fund Request';
    $invoiceAmt = (float)($source['approved_amount'] ?? $source['amount'] ?? 0);
    $vprPayType = 'Full';
}

$advancePaid = (float)($advance_paid ?? 0);
$penaltyAmt  = (float)($penalty ?? 0);
$netPayable  = (float)($net_payable ?? $invoiceAmt);
?>

<div class="pagetitle d-flex justify-content-between align-items-center">
    <div>
        <h1><?= htmlspecialchars($page_title) ?></h1>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-pending-payments">Pending Payments</a></li>
                <li class="breadcrumb-item active">Process</li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-pending-payments" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<section class="section">
    <div class="row g-4 justify-content-center">

        <!-- Payment Summary Card -->
        <div class="col-xl-4">
            <div class="card__wrapper shadow-sm border-top border-warning border-4">
                <div class="card__header">
                    <h6 class="card__title mb-0 text-warning">
                        <i class="bi bi-info-circle me-2"></i>Payment For
                    </h6>
                </div>
                <div class="card__body p-4">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3">
                            <span class="text-muted small text-uppercase">Type</span><br>
                            <strong><?= $refLabel ?></strong>
                            <?php if ($isVendorPayment): ?>
                                <span class="badge ms-1 <?= $vprPayType === 'Advance' ? 'bg-primary' : ($vprPayType === 'Partial' ? 'bg-warning text-dark' : 'bg-success') ?>">
                                    <?= $vprPayType ?>
                                </span>
                            <?php endif; ?>
                        </li>
                        <li class="mb-3">
                            <span class="text-muted small text-uppercase">Project</span><br>
                            <strong><?= htmlspecialchars($source['project_name'] ?? '—') ?></strong>
                        </li>
                        <?php if (!empty($source['operator_name'])): ?>
                        <li class="mb-3">
                            <span class="text-muted small text-uppercase">Contractor</span><br>
                            <strong><?= htmlspecialchars($source['operator_name']) ?></strong>
                        </li>
                        <?php endif; ?>
                        <?php if (!empty($source['vendor_name'])): ?>
                        <li class="mb-3">
                            <span class="text-muted small text-uppercase">Vendor</span><br>
                            <strong><?= htmlspecialchars($source['vendor_name']) ?></strong>
                        </li>
                        <?php endif; ?>
                    </ul>

                    <!-- Amount Breakdown -->
                    <div class="bg-light rounded p-3 mt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small"><?= $isVendorPayment ? ($vprPayType === 'Advance' ? 'Advance Amount' : 'Invoice Amount') : 'Approved Amount' ?></span>
                            <span class="fw-bold">৳ <?= number_format($invoiceAmt, 2) ?></span>
                        </div>

                        <?php if ($advancePaid > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-primary">
                            <span class="small"><i class="bi bi-dash-circle me-1"></i>Advance Paid</span>
                            <span class="fw-bold">৳ <?= number_format($advancePaid, 2) ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if ($penaltyAmt > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-danger">
                            <span class="small"><i class="bi bi-dash-circle me-1"></i>Penalties</span>
                            <span class="fw-bold">৳ <?= number_format($penaltyAmt, 2) ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if ($advancePaid > 0 || $penaltyAmt > 0): ?>
                        <hr class="my-2">
                        <?php endif; ?>

                        <div class="d-flex justify-content-between">
                            <span class="fw-bold text-success">Net Payable</span>
                            <span class="fw-bold fs-5 text-success">৳ <?= number_format($netPayable, 2) ?></span>
                        </div>
                    </div>

                    <?php if ($isVendorPayment && $vprPayType === 'Advance'): ?>
                    <div class="alert alert-primary mt-3 py-2 small">
                        <i class="bi bi-lightbulb me-1"></i>
                        This advance will be automatically deducted from the Final invoice for the same Purchase Order.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Payment Form -->
        <div class="col-xl-6">
            <div class="card__wrapper shadow-sm">
                <div class="card__header">
                    <h6 class="card__title mb-0">
                        <i class="bi bi-credit-card me-2 text-success"></i>Payment Details
                    </h6>
                </div>
                <div class="card__body p-4">
                    <form action="<?= $base_url ?>/con-payment-store" method="POST">
                        <input type="hidden" name="ref_type"        value="<?= htmlspecialchars($ref_type) ?>">
                        <input type="hidden" name="ref_id"          value="<?= (int)$ref_id ?>">
                        <input type="hidden" name="project_id"      value="<?= (int)($source['project_id'] ?? 0) ?>">
                        <input type="hidden" name="vpr_payment_type" value="<?= htmlspecialchars($vprPayType) ?>">

                        <div class="row ">

                            <!-- Amount — Pre-filled with net payable -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Payment Amount (৳) <span class="text-danger">*</span></label>
                                <input type="number" name="amount" class="form-control form-control-lg text-end fw-bold"
                                       value="<?= number_format($netPayable, 2, '.', '') ?>"
                                       step="0.01" min="0.01" required>
                                <div class="form-text">Pre-filled with net payable amount.</div>
                            </div>

                            <!-- Payment Date -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" class="form-control form-control-lg"
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>

                            <!-- Payment Method -->
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="Cash">💵 Cash</option>
                                    <option value="Cheque">📋 Cheque</option>
                                    <option value="BankTransfer">🏦 Bank Transfer</option>
                                    <option value="MobileWallet">📱 Mobile Wallet (bKash/Nagad)</option>
                                </select>
                            </div>

                            <!-- Reference No -->
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Reference / Cheque No.
                                    <span class="text-muted small fw-normal">(optional)</span>
                                </label>
                                <input type="text" name="reference_no" class="form-control"
                                       placeholder="Cheque number, transaction ID, etc.">
                            </div>

                            <!-- Notes -->
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Notes
                                    <span class="text-muted small fw-normal">(optional)</span>
                                </label>
                                <textarea name="notes" class="form-control" rows="3"
                                          placeholder="Additional payment notes..."></textarea>
                            </div>
                        </div>

                        <hr class="my-4">
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="<?= $base_url ?>/con-pending-payments" class="btn btn-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-success px-4 fw-bold"
                                    onclick="return confirm('Confirm payment of ৳<?= number_format($netPayable, 2) ?>? This action cannot be undone.');">
                                <i class="bi bi-check-circle me-1"></i> Confirm Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

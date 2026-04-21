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

$advancePaid    = (float)($advance_paid ?? 0);
$penaltyAmt     = (float)($penalty ?? 0);
$netPayable     = (float)($net_payable ?? $invoiceAmt);
$totalPaidSoFar = (float)($total_paid_so_far ?? 0);
$remainingDue   = (float)($remaining_due ?? $netPayable);
$prevPayments   = $previous_payments ?? [];
$hasPartial     = !empty($prevPayments);
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

                        <div class="d-flex justify-content-between mb-2">
                            <span class="fw-bold">Net Payable</span>
                            <span class="fw-bold fs-6">৳ <?= number_format($netPayable, 2) ?></span>
                        </div>

                        <?php if ($totalPaidSoFar > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-info">
                            <span class="small"><i class="bi bi-check-circle me-1"></i>Already Paid</span>
                            <span class="fw-bold">৳ <?= number_format($totalPaidSoFar, 2) ?></span>
                        </div>
                        <hr class="my-2">
                        <?php endif; ?>

                        <div class="d-flex justify-content-between">
                            <span class="fw-bold text-success">Remaining Due</span>
                            <span class="fw-bold fs-5 text-success">৳ <?= number_format($remainingDue, 2) ?></span>
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

            <!-- Previous Payments History -->
            <?php if ($hasPartial): ?>
            <div class="card__wrapper shadow-sm mt-3 border-top border-info border-3">
                <div class="card__header">
                    <h6 class="card__title mb-0 text-info">
                        <i class="bi bi-clock-history me-2"></i>Payment History
                        <span class="badge bg-info ms-1"><?= count($prevPayments) ?></span>
                    </h6>
                </div>
                <div class="card__body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Method</th>
                                    <th class="text-end">Amount</th>
                                    <th>By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($prevPayments as $i => $pp): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><small><?= date('d M Y', strtotime($pp['transaction_date'] ?? $pp['created_at'])) ?></small></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($pp['payment_method'] ?? '') ?></span></td>
                                    <td class="text-end fw-semibold">৳ <?= number_format((float)($pp['amount_paid'] ?? 0), 2) ?></td>
                                    <td><small><?= htmlspecialchars($pp['processed_by_name'] ?? '—') ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr class="table-info">
                                    <td colspan="3" class="text-end fw-bold">Total Paid</td>
                                    <td class="text-end fw-bold">৳ <?= number_format($totalPaidSoFar, 2) ?></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
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
                    <form action="<?= $base_url ?>/con-payment-store" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="ref_type"        value="<?= htmlspecialchars($ref_type) ?>">
                        <input type="hidden" name="ref_id"          value="<?= (int)$ref_id ?>">
                        <input type="hidden" name="project_id"      value="<?= (int)($source['project_id'] ?? 0) ?>">
                        <input type="hidden" name="vpr_payment_type" value="<?= htmlspecialchars($vprPayType) ?>">

                        <div class="row ">

                            <!-- Amount — Pre-filled with remaining due -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Payment Amount (৳) <span class="text-danger">*</span></label>
                                <input type="number" name="amount" class="form-control fw-bold"
                                       value="<?= number_format($remainingDue, 2, '.', '') ?>"
                                       step="0.01" min="0.01" max="<?= number_format($remainingDue, 2, '.', '') ?>" required>
                                <div class="form-text">
                                    Max: ৳ <?= number_format($remainingDue, 2) ?>
                                    <?php if ($hasPartial): ?>
                                        <span class="text-info">(after previous payments)</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Payment Date -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" class="form-control"
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>

                            <!-- Payment Method -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method" id="conPaymentMethod" class="form-select" required>
                                    <option value="Cash">Cash</option>
                                    <option value="Cheque">Cheque</option>
                                    <option value="BankTransfer">Bank Transfer</option>
                                    <option value="MobileWallet">Mobile Wallet (bKash/Nagad)</option>
                                </select>
                            </div>

                            <!-- Bank Dropdown (shown when BankTransfer selected) -->
                            <div class="col-md-6 con-bank-field" style="display:none;">
                                <label class="form-label fw-bold">Bank <span class="text-danger">*</span></label>
                                <select name="bank_name" class="form-select">
                                    <option value="">-- Select Bank --</option>
                                    <?php foreach ($banks ?? [] as $bank): ?>
                                        <option value="<?= htmlspecialchars($bank['bank_name'] . ' - ' . $bank['branch_name']) ?>">
                                            <?= htmlspecialchars($bank['bank_name'] . ' — ' . $bank['branch_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
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

                            <!-- Disbursement Proof Upload (MANDATORY) -->
                            <div class="col-md-12">
                                <label class="form-label fw-bold">
                                    Disbursement Proof <span class="text-danger">*</span>
                                </label>
                                <input type="file" name="disbursement_proof[]" class="form-control" 
                                       multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Upload payment receipt, bank slip, or cheque scan. 
                                    Accepted: PDF, JPG, PNG, DOC, DOCX (max 5MB each). Multiple files allowed.
                                </div>
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
                                    onclick="return confirm('Confirm payment of ৳<?= number_format($remainingDue, 2) ?>? This action cannot be undone.');">
                                <i class="bi bi-check-circle me-1"></i> Confirm Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
$(document).ready(function() {
    $('#conPaymentMethod').on('change', function() {
        if ($(this).val() === 'BankTransfer') {
            $('.con-bank-field').show();
            $('.con-bank-field select').attr('required', true);
        } else {
            $('.con-bank-field').hide();
            $('.con-bank-field select').attr('required', false);
        }
    });
});
</script>

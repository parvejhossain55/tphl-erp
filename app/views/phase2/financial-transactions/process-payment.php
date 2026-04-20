<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Process Payment';

// Determine display label
$refLabel  = $ref_type === 'contractor_bill' ? 'Contractor Bill' : 'Operational Fund Request';
$amountKey = $ref_type === 'operational_fund' ? ($source['approved_amount'] ?? $source['amount']) : $source['bill_amount'];
?>
<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h4 class="page-title fw-semibold fs-18 mb-0"><?= $page_title ?></h4>
            <div class="d-flex">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-pending-payments">Pending Payments</a></li>
                    <li class="breadcrumb-item active">Process</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row g-4 justify-content-center">
        <!-- Source Summary -->
        <div class="col-xl-4">
            <div class="card custom-card border-warning">
                <div class="card-header bg-warning bg-opacity-10">
                    <h6 class="card-title mb-0 text-warning"><i class="bi bi-info-circle me-2"></i> Payment For</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <span class="text-muted small">Type</span><br>
                            <strong><?= $refLabel ?></strong>
                        </li>
                        <li class="mb-2">
                            <span class="text-muted small">Project</span><br>
                            <strong><?= htmlspecialchars($source['project_name'] ?? '-') ?></strong>
                        </li>
                        <?php if (!empty($source['operator_name'])): ?>
                        <li class="mb-2">
                            <span class="text-muted small">Contractor</span><br>
                            <strong><?= htmlspecialchars($source['operator_name']) ?></strong>
                        </li>
                        <?php endif; ?>
                        <li class="mb-2">
                            <span class="text-muted small">Approved Amount</span><br>
                            <strong class="text-success fs-5">৳ <?= number_format($amountKey, 2) ?></strong>
                        </li>
                        <?php if (!empty($source['purpose']) || !empty($source['work_description'])): ?>
                        <li>
                            <span class="text-muted small">Description</span><br>
                            <?= htmlspecialchars($source['purpose'] ?? $source['work_description'] ?? '') ?>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Payment Form -->
        <div class="col-xl-6">
            <div class="card custom-card">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-credit-card me-2 text-success"></i> Payment Details</h6>
                </div>
                <div class="card-body">
                    <form action="<?= $base_url ?>/con-payment-store" method="POST">
                        <input type="hidden" name="ref_type"   value="<?= htmlspecialchars($ref_type) ?>">
                        <input type="hidden" name="ref_id"     value="<?= (int)$ref_id ?>">
                        <input type="hidden" name="project_id" value="<?= (int)($source['project_id'] ?? 0) ?>">

                        <div class="row g-3">
                            <!-- Amount -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Payment Amount (৳) <span class="text-danger">*</span></label>
                                <input type="number" name="amount" class="form-control"
                                    value="<?= number_format($amountKey, 2, '.', '') ?>"
                                    step="0.01" min="0" required>
                            </div>

                            <!-- Payment Date -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>

                            <!-- Payment Method -->
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="Cash">💵 Cash</option>
                                    <option value="Cheque">📋 Cheque</option>
                                    <option value="BankTransfer">🏦 Bank Transfer</option>
                                    <option value="MobileWallet">📱 Mobile Wallet (bKash/Nagad)</option>
                                </select>
                            </div>

                            <!-- Reference No -->
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Reference / Cheque No. <span class="text-muted small">(optional)</span></label>
                                <input type="text" name="reference_no" class="form-control"
                                    placeholder="Cheque number, transaction ID, etc.">
                            </div>

                            <!-- Notes -->
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Notes <span class="text-muted small">(optional)</span></label>
                                <textarea name="notes" class="form-control" rows="3"
                                    placeholder="Additional payment notes..."></textarea>
                            </div>
                        </div>

                        <hr class="my-4">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle me-1"></i> Confirm Payment
                            </button>
                            <a href="<?= $base_url ?>/con-pending-payments" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

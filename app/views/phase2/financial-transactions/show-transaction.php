<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Transaction Receipt';

$methodIcons = [
    'Cash'          => 'cash',
    'Cheque'        => 'journal-text',
    'BankTransfer'  => 'bank',
    'MobileWallet'  => 'phone',
];
$icon = $methodIcons[$txn['payment_method']] ?? 'credit-card';

$refLabel = [
    'contractor_bill'  => 'Contractor Bill',
    'operational_fund' => 'Operational Fund',
    'vendor_payment'   => 'Vendor Payment',
][$txn['ref_type']] ?? ucfirst($txn['ref_type']);
?>
<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h4 class="page-title fw-semibold fs-18 mb-0"><?= $page_title ?></h4>
            <div class="d-flex">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-transactions">Transactions</a></li>
                    <li class="breadcrumb-item active">#<?= $txn['id'] ?></li>
                </ol>
            </div>
        </div>
        <div class="my-auto d-flex gap-2">
            <a href="<?= $base_url ?>/con-transactions" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Ledger
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-xl-7">
            <!-- Receipt Card -->
            <div class="card custom-card">
                <div class="card-header bg-success bg-opacity-10 text-center py-4">
                    <div class="avatar avatar-lg bg-success rounded-circle mx-auto mb-2">
                        <i class="bi bi-check-lg text-white fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-success mb-1">Payment Processed</h5>
                    <p class="text-muted small mb-0">Transaction #<?= $txn['id'] ?></p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Payment Type</label>
                            <p class="fw-semibold mb-0"><?= $refLabel ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Project</label>
                            <p class="fw-semibold mb-0"><?= htmlspecialchars($txn['project_name'] ?? '-') ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Amount Paid</label>
                            <p class="fw-bold mb-0 fs-4 text-success">৳ <?= number_format($txn['amount'], 2) ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Payment Date</label>
                            <p class="fw-semibold mb-0"><?= date('d M Y', strtotime($txn['payment_date'])) ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Payment Method</label>
                            <p class="fw-semibold mb-0">
                                <i class="bi bi-<?= $icon ?> me-1 text-primary"></i>
                                <?= htmlspecialchars($txn['payment_method']) ?>
                            </p>
                        </div>
                        <?php if (!empty($txn['reference_no'])): ?>
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Reference No.</label>
                            <p class="fw-semibold mb-0"><?= htmlspecialchars($txn['reference_no']) ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($txn['notes'])): ?>
                        <div class="col-md-12">
                            <label class="form-label text-muted small mb-1">Notes</label>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($txn['notes'])) ?></p>
                        </div>
                        <?php endif; ?>

                        <div class="col-md-12">
                            <hr class="my-2">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <span class="text-muted small">Processed By</span><br>
                                    <strong><?= htmlspecialchars($txn['processed_by_name'] ?? '-') ?></strong>
                                </div>
                                <div class="text-end">
                                    <span class="text-muted small">Transaction Date</span><br>
                                    <strong><?= date('d M Y h:i A', strtotime($txn['created_at'])) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

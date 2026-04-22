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
                <div class="card-header bg-opacity-10 text-center py-4">

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
                            <p class="fw-bold mb-0 fs-4 text-success">৳ <?= number_format($txn['amount_paid'] ?? $txn['amount'] ?? 0, 2) ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Payment Date</label>
                            <p class="fw-semibold mb-0"><?= date('d M Y', strtotime($txn['transaction_date'] ?? $txn['created_at'])) ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Payment Method</label>
                            <p class="fw-semibold mb-0">
                                <i class="bi bi-<?= $icon ?> me-1 text-primary"></i>
                                <?= htmlspecialchars($txn['payment_method']) ?>
                            </p>
                        </div>
                        <?php if (!empty($txn['reference_number'])): ?>
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Reference No.</label>
                            <p class="fw-semibold mb-0"><?= htmlspecialchars($txn['reference_number']) ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($txn['notes'])): ?>
                        <div class="col-md-12">
                            <label class="form-label text-muted small mb-1">Notes</label>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($txn['notes'])) ?></p>
                        </div>
                        <?php endif; ?>

                        <?php
                        // Display disbursement proof documents
                        $proofFiles = [];
                        if (!empty($txn['disbursement_proof'])) {
                            $proofFiles = json_decode($txn['disbursement_proof'], true) ?? [];
                        }
                        ?>
                        <?php if (!empty($proofFiles)): ?>
                        <div class="col-md-12">
                            <label class="form-label text-muted small mb-1">
                                <i class="bi bi-file-earmark-check me-1"></i>Disbursement Proof
                            </label>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($proofFiles as $idx => $filePath): ?>
                                    <a href="<?= $base_url ?>/public/<?= htmlspecialchars($filePath) ?>"
                                       target="_blank" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-file-earmark me-1"></i>
                                        Document <?= $idx + 1 ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
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

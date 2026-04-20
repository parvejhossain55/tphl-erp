<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Contractor Bill Detail';

$statusMap = [
    'submitted' => ['label' => 'Submitted',  'color' => 'primary'],
    'verified'  => ['label' => 'Verified',   'color' => 'info'],
    'approved'  => ['label' => 'Approved',   'color' => 'success'],
    'paid'      => ['label' => 'Paid',       'color' => 'dark'],
    'rejected'  => ['label' => 'Rejected',   'color' => 'danger'],
];
$status = $bill['status'] ?? 'submitted';
$statusInfo = $statusMap[$status] ?? ['label' => ucfirst($status), 'color' => 'secondary'];
?>
<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h4 class="page-title fw-semibold fs-18 mb-0"><?= $page_title ?></h4>
            <div class="d-flex">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-contractor-bills">Contractor Bills</a></li>
                    <li class="breadcrumb-item active">#<?= $bill['id'] ?></li>
                </ol>
            </div>
        </div>
        <div class="my-auto d-flex gap-2">
            <span class="badge bg-<?= $statusInfo['color'] ?> fs-6 px-3 py-2"><?= $statusInfo['label'] ?></span>
            <a href="<?= $base_url ?>/con-contractor-bills" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Bill Details -->
        <div class="col-xl-8">
            <div class="card custom-card">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-receipt me-2 text-primary"></i> Bill Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Project</label>
                            <p class="fw-semibold mb-0"><?= htmlspecialchars($bill['project_name'] ?? '-') ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Contractor</label>
                            <p class="fw-semibold mb-0">
                                <?= htmlspecialchars($bill['operator_name'] ?? '-') ?>
                                <?php if (!empty($bill['operator_phone'])): ?>
                                    <small class="text-muted ms-1">(<?= $bill['operator_phone'] ?>)</small>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Bill Date</label>
                            <p class="fw-semibold mb-0"><?= date('d M Y', strtotime($bill['bill_date'])) ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Bill Amount</label>
                            <p class="fw-semibold mb-0 text-success fs-5">৳ <?= number_format($bill['bill_amount'], 2) ?></p>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-muted small mb-1">Work Description</label>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($bill['work_description'] ?? '-')) ?></p>
                        </div>
                        <?php if (!empty($bill['attachment_path'])): ?>
                        <div class="col-md-12">
                            <label class="form-label text-muted small mb-1">Attachment</label><br>
                            <a href="<?= $base_url ?>/<?= $bill['attachment_path'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-paperclip me-1"></i> View Attachment
                            </a>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($bill['verification_note'])): ?>
                        <div class="col-md-12">
                            <label class="form-label text-muted small mb-1">Verification Note</label>
                            <p class="mb-0 text-info"><?= htmlspecialchars($bill['verification_note']) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions & Meta -->
        <div class="col-xl-4">
            <!-- Verify Action -->
            <?php if ($status === 'submitted'): ?>
            <div class="card custom-card border-info mb-3">
                <div class="card-header bg-info bg-opacity-10">
                    <h6 class="card-title mb-0 text-info"><i class="bi bi-check2-circle me-2"></i> Verify Bill</h6>
                </div>
                <div class="card-body">
                    <form action="<?= $base_url ?>/con-contractor-bill-verify/<?= $bill['id'] ?>" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Verification Note</label>
                            <textarea name="verification_note" class="form-control form-control-sm" rows="3"
                                placeholder="Add verification remarks..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-info btn-sm w-100">
                            <i class="bi bi-patch-check me-1"></i> Mark as Verified
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Meta Info -->
            <div class="card custom-card">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-info-circle me-2"></i> Meta Info</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <span class="text-muted small">Submitted By:</span><br>
                            <strong><?= htmlspecialchars($bill['created_by_name'] ?? '-') ?></strong>
                        </li>
                        <?php if (!empty($bill['verified_by_name'])): ?>
                        <li class="mb-2">
                            <span class="text-muted small">Verified By:</span><br>
                            <strong><?= htmlspecialchars($bill['verified_by_name']) ?></strong>
                        </li>
                        <?php endif; ?>
                        <li class="mb-2">
                            <span class="text-muted small">Submitted At:</span><br>
                            <?= date('d M Y h:i A', strtotime($bill['created_at'])) ?>
                        </li>
                        <?php if (!empty($bill['verified_at'])): ?>
                        <li>
                            <span class="text-muted small">Verified At:</span><br>
                            <?= date('d M Y h:i A', strtotime($bill['verified_at'])) ?>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

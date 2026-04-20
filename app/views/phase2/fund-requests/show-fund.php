<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Fund Request Detail';

$statusMap = [
    'pending'  => ['label' => 'Pending Approval', 'color' => 'warning'],
    'approved' => ['label' => 'Approved',          'color' => 'success'],
    'rejected' => ['label' => 'Rejected',          'color' => 'danger'],
    'paid'     => ['label' => 'Paid',              'color' => 'dark'],
];
$status     = $request['status'] ?? 'pending';
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
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-fund-requests">Fund Requests</a></li>
                    <li class="breadcrumb-item active">#<?= $request['id'] ?></li>
                </ol>
            </div>
        </div>
        <div class="my-auto d-flex gap-2">
            <span class="badge bg-<?= $statusInfo['color'] ?> fs-6 px-3 py-2"><?= $statusInfo['label'] ?></span>
            <a href="<?= $base_url ?>/con-fund-requests" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Request Details -->
        <div class="col-xl-8">
            <div class="card custom-card">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-cash-coin me-2 text-success"></i> Fund Request Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Project</label>
                            <p class="fw-semibold mb-0"><?= htmlspecialchars($request['project_name'] ?? '-') ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Requested By</label>
                            <p class="fw-semibold mb-0"><?= htmlspecialchars($request['requester_name'] ?? '-') ?></p>
                        </div>
                        <?php if (!empty($request['operator_name'])): ?>
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Related Operator</label>
                            <p class="fw-semibold mb-0"><?= htmlspecialchars($request['operator_name']) ?></p>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Fund Date</label>
                            <p class="fw-semibold mb-0"><?= date('d M Y', strtotime($request['fund_date'])) ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Requested Amount</label>
                            <p class="fw-semibold mb-0 fs-5 text-success">৳ <?= number_format($request['amount'], 2) ?></p>
                        </div>
                        <?php if (!empty($request['approved_amount'])): ?>
                        <div class="col-md-6">
                            <label class="form-label text-muted small mb-1">Approved Amount</label>
                            <p class="fw-semibold mb-0 fs-5 text-primary">৳ <?= number_format($request['approved_amount'], 2) ?></p>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-12">
                            <label class="form-label text-muted small mb-1">Purpose</label>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($request['purpose'] ?? '-')) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Meta Info -->
        <div class="col-xl-4">
            <div class="card custom-card">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-info-circle me-2"></i> Approval Info</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3">
                            <span class="text-muted small">Current Status</span><br>
                            <span class="badge bg-<?= $statusInfo['color'] ?> mt-1"><?= $statusInfo['label'] ?></span>
                        </li>
                        <li class="mb-3">
                            <span class="text-muted small">Submitted At</span><br>
                            <strong><?= date('d M Y h:i A', strtotime($request['created_at'])) ?></strong>
                        </li>
                        <?php if (!empty($request['approved_by_name'])): ?>
                        <li class="mb-3">
                            <span class="text-muted small">Approved By</span><br>
                            <strong><?= htmlspecialchars($request['approved_by_name']) ?></strong>
                        </li>
                        <?php endif; ?>
                    </ul>

                    <?php if ($status === 'pending'): ?>
                    <div class="alert alert-warning border-0 py-2 mt-3 mb-0 small">
                        <i class="bi bi-clock me-1"></i> Awaiting management approval
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

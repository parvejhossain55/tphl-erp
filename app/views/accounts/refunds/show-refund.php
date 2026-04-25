<?php
$base_url  = $GLOBALS['base_url'];
$refund    = $refund ?? [];
$payments  = $payments ?? [];
$cashout   = $cashout_request ?? null;
$approval  = $approval_request ?? null;
$canApproveReq = $canApprove ?? false;

$statusMap = [
    'pending'  => ['label' => 'Pending',  'color' => 'warning'],
    'approved' => ['label' => 'Approved', 'color' => 'success'],
    'rejected' => ['label' => 'Rejected', 'color' => 'danger'],
    'refunded' => ['label' => 'Refunded', 'color' => 'secondary'],
];

$status     = $refund['status'] ?? 'pending';
$statusInfo = $statusMap[$status] ?? ['label' => ucfirst($status), 'color' => 'secondary'];
$isPending  = ($status === 'pending');
$isApproved = in_array($status, ['approved', 'refunded']);
?>

<!-- Header -->
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div class="my-auto">
        <h4 class="page-title fw-semibold fs-18 mb-0">Refund Request #<?= $refund['id'] ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/accounts-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/client-refunds">Client Refunds</a></li>
                <li class="breadcrumb-item active">#<?= $refund['id'] ?></li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <span class="badge bg-<?= $statusInfo['color'] ?> fs-6 px-3 py-2"><?= $statusInfo['label'] ?></span>
        <a href="<?= $base_url ?>/client-refunds" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <!-- Main Content (Left) -->
        <div class="col-lg-8">
            <!-- Booking & Client Info -->
            <div class="card custom-card mb-3">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-person me-2"></i>Client & Booking Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="info-label text-uppercase text-muted small fw-bold">Client</div>
                            <div class="info-value fw-semibold"><?= htmlspecialchars($refund['client_name'] ?? '—') ?></div>
                            <?php if (!empty($refund['client_phone'])): ?>
                                <small class="text-muted"><?= $refund['client_phone'] ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label text-uppercase text-muted small fw-bold">Project</div>
                            <div class="info-value"><?= htmlspecialchars($refund['project_name'] ?? '—') ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-label text-uppercase text-muted small fw-bold">Flat</div>
                            <div class="info-value"><?= htmlspecialchars($refund['flat_name'] ?? '—') ?>
                                <?php if (!empty($refund['floor_no'])): ?> (Floor <?= $refund['floor_no'] ?>)<?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-label text-uppercase text-muted small fw-bold">Booking Date</div>
                            <div class="info-value"><?= !empty($refund['booking_date']) ? date('d M Y', strtotime($refund['booking_date'])) : '—' ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-label text-uppercase text-muted small fw-bold">Booking Status</div>
                            <div class="info-value">
                                <?php
                                $bStatus = $refund['booking_status'] ?? 'booked';
                                $bColor = match ($bStatus) {
                                    'booked'   => 'info',
                                    'refunded' => 'danger',
                                    'cancelled'=> 'secondary',
                                    default    => 'primary',
                                };
                                ?>
                                <span class="badge bg-<?= $bColor ?>"><?= ucfirst($bStatus) ?></span>
                            </div>
                        </div>

                        <!-- Financial Summary -->
                        <div class="col-md-12 mt-3">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <div class="border rounded p-3 text-center">
                                        <small class="text-muted d-block mb-1">Total Verified Paid</small>
                                        <h5 class="text-primary mb-0 fw-bold">৳ <?= number_format($refund['total_paid'], 2) ?></h5>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded p-3 text-center border-danger">
                                        <small class="text-muted d-block mb-1">Retention (Non-refundable)</small>
                                        <h5 class="text-danger mb-0 fw-bold">৳ <?= number_format($refund['retention_amount'], 2) ?></h5>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded p-3 text-center border-success">
                                        <small class="text-muted d-block mb-1">Refund Amount</small>
                                        <h5 class="text-success mb-0 fw-bold">৳ <?= number_format($refund['refund_amount'], 2) ?></h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($refund['reason'])): ?>
                        <div class="col-md-12">
                            <div class="info-label text-uppercase text-muted small fw-bold">Reason</div>
                            <div class="info-value"><?= nl2br(htmlspecialchars($refund['reason'])) ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($refund['document'])): ?>
                        <div class="col-md-12 mt-2">
                            <div class="info-label text-uppercase text-muted small fw-bold">Document</div>
                            <div class="info-value">
                                <a href="<?= $base_url ?>/<?= htmlspecialchars($refund['document']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-file-earmark-text me-1"></i> View Document
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Payment History -->
            <?php if (!empty($payments)): ?>
            <div class="card custom-card mb-3">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-clock-history me-2"></i>Payment History</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Amount (৳)</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th>By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $idx => $pay): ?>
                                <tr class="<?= $pay['verification_status'] !== 'verified' ? 'text-muted' : '' ?>">
                                    <td><?= $idx + 1 ?></td>
                                    <td><?= date('d M Y', strtotime($pay['payment_date'])) ?></td>
                                    <td><?= ucfirst(str_replace('_', ' ', $pay['payment_type'])) ?></td>
                                    <td class="text-end fw-semibold">৳ <?= number_format($pay['amount'], 2) ?></td>
                                    <td><?= ucfirst($pay['payment_method'] ?? '') ?></td>
                                    <td>
                                        <?php
                                        $vColor = match ($pay['verification_status'] ?? '') {
                                            'verified' => 'success',
                                            'rejected' => 'danger',
                                            default    => 'warning',
                                        };
                                        ?>
                                        <span class="badge bg-<?= $vColor ?>"><?= ucfirst($pay['verification_status'] ?? 'pending') ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($pay['created_by_name'] ?? '—') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar (Right) -->
        <div class="col-lg-4">
            <!-- Status Info -->
            <div class="card custom-card mb-3">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-info-circle me-2"></i>Status Info</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Current Status</small>
                        <span class="badge bg-<?= $statusInfo['color'] ?> fs-6"><?= $statusInfo['label'] ?></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Requested By</small>
                        <strong><?= htmlspecialchars($refund['requested_by_name'] ?? '—') ?></strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Requested At</small>
                        <strong><?= date('d M Y h:i A', strtotime($refund['created_at'])) ?></strong>
                    </div>
                    <?php if (!empty($refund['approved_by_name'])): ?>
                    <div class="mb-3">
                        <small class="text-muted d-block"><?= $status === 'rejected' ? 'Rejected' : 'Approved' ?> By</small>
                        <strong><?= htmlspecialchars($refund['approved_by_name']) ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($refund['approved_at'])): ?>
                    <div class="mb-3">
                        <small class="text-muted d-block"><?= $status === 'rejected' ? 'Rejected' : 'Approved' ?> At</small>
                        <strong><?= date('d M Y h:i A', strtotime($refund['approved_at'])) ?></strong>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($cashout)): ?>
                    <hr>
                    <div class="mb-2">
                        <small class="text-muted d-block">Linked Cashout Request</small>
                        <a href="<?= $base_url ?>/view-cashout-request?id=<?= $cashout['id'] ?>&redirect=client-refunds" class="btn btn-sm btn-outline-info mt-1">
                            <i class="bi bi-link-45deg me-1"></i> Cashout #<?= $cashout['id'] ?> — 
                            <span class="badge bg-<?= $cashout['status'] === 'approved' ? 'success' : ($cashout['status'] === 'pending' ? 'warning' : 'secondary') ?>">
                                <?= ucfirst($cashout['status']) ?>
                            </span>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Approval Actions (Management only, pending status) -->
            <?php if ($canApproveReq && $isPending): ?>
            <div class="card custom-card border-warning mb-3">
                <div class="card-header bg-warning bg-opacity-10">
                    <h6 class="card-title mb-0"><i class="bi bi-shield-lock me-2"></i>Approval Action</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Approving will:<br>
                        • Cancel the booking<br>
                        • Release the flat back to available<br>
                        • Cancel all pending EMIs<br>
                        • Create a cashout request for ৳<?= number_format($refund['refund_amount'], 2) ?>
                    </div>
                    <form action="<?= $base_url ?>/client-refund-approve/<?= $refund['id'] ?>" method="POST" class="mb-3">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Approved Amount (৳)</label>
                            <input type="number" step="0.01" name="approved_amount" class="form-control" value="<?= htmlspecialchars($refund['refund_amount']) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100 fw-bold"
                                onclick="return confirm('Approve this refund? This will cancel the booking and release the flat.');">
                            <i class="bi bi-check-circle me-1"></i> Approve Refund
                        </button>
                    </form>
                    <form action="<?= $base_url ?>/client-refund-reject/<?= $refund['id'] ?>" method="POST">
                        <button type="submit" class="btn btn-outline-danger w-100 btn-sm"
                                onclick="return confirm('Reject this refund request?');">
                            <i class="bi bi-x-circle me-1"></i> Reject
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Status Messages -->
            <?php if ($isApproved): ?>
                <div class="alert alert-success text-center">
                    <i class="bi bi-check-circle-fill fs-4 d-block mb-2"></i>
                    Refund approved. Booking cancelled. Flat released.
                    <?php if (!empty($cashout)): ?>
                        <br><small>Cashout request #<?= $cashout['id'] ?> created for disbursement.</small>
                    <?php endif; ?>
                </div>

                <?php if (!empty($cashout)): ?>
                <?php
                    $cashoutPaid = (float)($cashout['paid_amount'] ?? 0);
                    $cashoutTotal = (float)($cashout['approved_amount'] ?? $cashout['amount'] ?? 0);
                    $cashoutRemaining = max(0, $cashoutTotal - $cashoutPaid);
                    $isFullyPaid = $cashoutRemaining <= 0;
                ?>
                <div class="card custom-card border-<?= $isFullyPaid ? 'success' : 'primary' ?> mb-3">
                    <div class="card-header bg-<?= $isFullyPaid ? 'success' : 'primary' ?> bg-opacity-10">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-cash-stack me-2"></i>Refund Payment
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center g-2 mb-3">
                            <div class="col-4">
                                <small class="text-muted d-block">Total</small>
                                <strong class="text-primary">৳ <?= number_format($cashoutTotal, 2) ?></strong>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Paid</small>
                                <strong class="text-success">৳ <?= number_format($cashoutPaid, 2) ?></strong>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Remaining</small>
                                <strong class="<?= $isFullyPaid ? 'text-success' : 'text-danger' ?>">
                                    <?= $isFullyPaid ? '✓ Paid' : '৳ ' . number_format($cashoutRemaining, 2) ?>
                                </strong>
                            </div>
                        </div>
                        <?php if (!$isFullyPaid): ?>
                        <a href="<?= $base_url ?>/view-cashout-request?id=<?= $cashout['id'] ?>&redirect=client-refunds"
                           class="btn btn-primary w-100 fw-bold">
                            <i class="bi bi-plus-circle me-1"></i> Add Payment
                        </a>
                        <?php else: ?>
                        <div class="text-center text-success fw-bold">
                            <i class="bi bi-check-circle me-1"></i> Refund fully disbursed
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php elseif ($status === 'rejected'): ?>
                <div class="alert alert-danger text-center">
                    <i class="bi bi-x-circle-fill fs-4 d-block mb-2"></i>
                    This refund request has been rejected.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

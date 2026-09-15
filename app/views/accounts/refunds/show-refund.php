<?php
$base_url  = $GLOBALS['base_url'];
$refund    = $refund ?? [];
$payments  = $payments ?? [];
$cashout   = $cashout_request ?? null;
$approval  = $approval_request ?? null;
$canApproveReq = $canApprove ?? false;
$userRole = $userRole ?? ($_SESSION['user']['role_id'] ?? null);

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
                        <div class="col-md-12 mt-3">
                            <div class="p-3 rounded-2 border border-warning bg-warning bg-opacity-10 shadow-sm">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="bi bi-chat-left-quote-fill text-warning fs-5 me-2"></i>
                                    <span class="fw-bold text-dark text-uppercase small">Sales Refund Note / Reason:</span>
                                </div>
                                <div class="text-dark fw-semibold ps-4" style="font-size: 15px; line-height: 1.5;">
                                    <?= nl2br(htmlspecialchars($refund['reason'])) ?>
                                </div>
                            </div>
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
                        <strong><?= htmlspecialchars($refund['requested_by_name'] ?? '—') ?></strong><br>
                        <small class="text-muted">(<?= htmlspecialchars($refund['requested_by_role'] ?? 'Role not found') ?>)</small>
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
                        • Authorize the refund request for Accounts payout<br>
                        • Confirm booking cancellation and flat release<br>
                        • Create a linked cashout request for ৳<?= number_format($refund['refund_amount'], 2) ?>
                    </div>
                    <form action="<?= $base_url ?>/client-refund-approve/<?= $refund['id'] ?>" method="POST" class="mb-3" id="approveForm">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Approved Amount (৳)</label>
                            <input type="number" step="0.01" name="approved_amount" class="form-control" value="<?= htmlspecialchars($refund['refund_amount']) ?>" required>
                        </div>
                        <button type="button" class="btn btn-success w-100 fw-bold" onclick="confirmApproval()">
                            <i class="bi bi-check-circle me-1"></i> Approve Refund
                        </button>
                    </form>
                    <form action="<?= $base_url ?>/client-refund-reject/<?= $refund['id'] ?>" method="POST" id="rejectForm">
                        <button type="button" class="btn btn-outline-danger w-100 btn-sm" onclick="confirmRejection()">
                            <i class="bi bi-x-circle me-1"></i> Reject
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Informative Box for Accounts when Pending -->
            <?php if ($isPending && !$canApproveReq): ?>
            <div class="card custom-card border-info mb-3">
                <div class="card-header bg-info bg-opacity-10">
                    <h6 class="card-title mb-0 text-info"><i class="bi bi-hourglass-split me-2"></i>Awaiting Management Approval</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-2">
                        এই রিফান্ড রিকোয়েস্টটি বর্তমানে <strong>Pending (অপেক্ষমান)</strong> রয়েছে।
                    </p>
                    <div class="alert alert-light border small mb-0">
                        <i class="bi bi-info-circle text-primary "></i>
                        <strong>রিটেনশন কেটে রিফান্ড প্রদান:</strong><br>
                        ম্যানেজমেন্ট কর্তৃক এই রিকোয়েস্টটি <strong>Approve</strong> করার পর, অ্যাকাউন্টস ডিপার্টমেন্ট রিটেনশন ফি (কোম্পানি চার্জ) কেটে বাকি রিফান্ড পেইড করার অপশন পাবে।
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Accounts Payout Action (Approved status) -->
            <?php if ($status === 'approved' && in_array($userRole, [1, 3, 6, 10, 30])): ?>
            <div class="card custom-card border-success mb-3 shadow-sm">
                <div class="card-header bg-success bg-opacity-10">
                    <h6 class="card-title mb-0 text-success"><i class="bi bi-cash-stack me-2"></i>Process Refund Payout</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-success small mb-3">
                        <i class="bi bi-check-circle-fill me-1"></i>
                        <strong>ম্যানেজমেন্ট কর্তৃক Approved!</strong><br>
                        এখন অ্যাকাউন্টস রিটেনশন কেটে ক্লায়েন্টকে রিফান্ডের টাকা পরিশোধ করতে পারবে।
                    </div>
                    <a href="<?= $base_url ?>/client-refund-create/<?= $refund['booking_id'] ?>" class="btn btn-danger w-100 fw-bold py-2 shadow-sm">
                        <i class="bi bi-cash-coin me-1"></i> Pay Refund & Deduct Retention
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Refund Completed -->
            <?php if ($status === 'refunded'): ?>
            <div class="card custom-card border-secondary mb-3 shadow-sm">
                <div class="card-header bg-secondary bg-opacity-10">
                    <h6 class="card-title mb-0 text-secondary"><i class="bi bi-check2-all me-2"></i>Refund Disbursed</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-success small mb-0">
                        <i class="bi bi-check-circle me-1"></i>
                        রিফান্ড পরিশোধ সম্পন্ন হয়েছে এবং ক্লায়েন্ট লেজার থেকে <strong>৳ <?= number_format($refund['refund_amount'], 2) ?></strong> সমন্বয় করা হয়েছে।
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <script>
            function confirmApproval() {
                Swal.fire({
                    title: 'Approve this refund?',
                    text: 'This will authorize the refund for Accounts payout and confirm booking cancellation.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, Approve it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('approveForm').submit();
                    }
                });
            }

            function confirmRejection() {
                Swal.fire({
                    title: 'Reject this refund request?',
                    text: 'This action cannot be undone.',
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Reject it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('rejectForm').submit();
                    }
                });
            }
            </script>

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
                        <button type="button" class="btn btn-primary w-100 fw-bold mb-3" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                            <i class="bi bi-plus-circle me-1"></i> Add Payment
                        </button>
                        <?php else: ?>
                        <div class="text-center text-success fw-bold mb-3">
                            <i class="bi bi-check-circle me-1"></i> Refund fully disbursed
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($cashout_payments)): ?>
                            <hr>
                            <h6 class="fs-14 fw-semibold mb-3">Payment History</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Amount (৳)</th>
                                            <th>Docs</th>
                                            <th>Added By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cashout_payments as $cp): ?>
                                        <tr>
                                            <td><?= date('d M Y h:i A', strtotime($cp['created_at'])) ?></td>
                                            <td class="text-success fw-semibold">৳ <?= number_format($cp['amount'], 2) ?></td>
                                            <td>
                                                <?php if (!empty($cp['disbursement_proof'])): ?>
                                                    <?php 
                                                    $proofStr = $cp['disbursement_proof'];
                                                    $proofFiles = json_decode($proofStr, true);
                                                    if (!is_array($proofFiles)) {
                                                        $proofFiles = [$proofStr];
                                                    }
                                                    ?>
                                                    <div class="d-flex flex-wrap gap-1">
                                                    <?php foreach ($proofFiles as $pIdx => $pPath): ?>
                                                        <?php $pExt = strtolower(pathinfo($pPath, PATHINFO_EXTENSION)); ?>
                                                        <a href="<?= $GLOBALS['base_url'] ?>/<?= htmlspecialchars($pPath) ?>" target="_blank"
                                                            class="btn btn-xs btn-outline-success py-0 px-1"
                                                            style="font-size:11px;"
                                                            title="<?= htmlspecialchars(basename($pPath)) ?>">
                                                            <?php if (in_array($pExt, ['jpg','jpeg','png','gif','webp'])): ?>
                                                                <i class="bi bi-image"></i>
                                                            <?php elseif ($pExt === 'pdf'): ?>
                                                                <i class="bi bi-file-pdf"></i>
                                                            <?php else: ?>
                                                                <i class="bi bi-file-earmark-text"></i>
                                                            <?php endif; ?>
                                                            <?= $pIdx + 1 ?>
                                                        </a>
                                                    <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($cp['added_by_name'] ?? '—') ?><br>
                                                <small class="text-muted"><?= htmlspecialchars($cp['added_by_role'] ?? '—') ?></small>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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

<!-- Add Payment Modal -->
<?php if (!empty($cashout) && !$isFullyPaid): ?>
    <div class="modal fade" id="addPaymentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-light fa-plus-circle me-2"></i>Add Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="<?= $GLOBALS['base_url'] ?>/add-cashout-payment?redirect=client-refunds" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="request_id" value="<?= $cashout['id'] ?>">

                        <div class="alert alert-info">
                            <strong>Total Approved:</strong> TK.<?= number_format($cashoutTotal, 2) ?><br>
                            <strong>Already Paid:</strong> TK.<?= number_format($cashoutPaid, 2) ?><br>
                            <strong>Due Amount:</strong> <span class="text-danger">TK.<?= number_format($cashoutRemaining, 2) ?></span>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Amount (TK.): <span class="text-danger">*</span></label>
                                <input type="number" name="amount" class="form-control" placeholder="0.00" step="0.01"
                                    min="0.01" max="<?= $cashoutRemaining ?>" value="<?= $cashoutRemaining ?>" required>
                                <small class="text-muted">Max: TK.<?= number_format($cashoutRemaining, 2) ?></small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Payment Date: <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" class="form-control"
                                    value="<?= date('Y-m-d') ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Payment Method: <span class="text-danger">*</span></label>
                                <select name="payment_method" class="form-control" id="vrPaymentMethod" required>
                                    <option value="cash">Cash</option>
                                    <option value="bank">Bank Transfer</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="online">Online Payment</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Transaction Reference:</label>
                                <input type="text" name="transaction_ref" class="form-control"
                                    placeholder="e.g., Receipt #, Transaction ID">
                            </div>

                            <!-- Bank Transfer Fields -->
                            <div class="col-md-12 mb-3 vr-bank-fields" style="display:none;">
                                <label class="form-label">Transfer To / Company Bank <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select name="bank_id" id="vrBankSelect" class="form-control">
                                        <option value="">-- Loading banks... --</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary" id="addCompanyBankBtn" title="Add Company Bank" style="border: 1px solid #ced4da;"><i class="fa-light fa-plus"></i></button>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3 vr-bank-fields" style="display:none;">
                                <label class="form-label">Account Number:</label>
                                <input type="text" name="bank_account_no" class="form-control" placeholder="Enter account number">
                            </div>

                            <!-- Cheque Fields -->
                            <div class="col-md-6 mb-3 vr-cheque-fields" style="display:none;">
                                <label class="form-label">Cheque Number:</label>
                                <input type="text" name="cheque_no" class="form-control" placeholder="Enter cheque number">
                            </div>

                            <!-- Online Payment Fields -->
                            <div class="col-md-6 mb-3 vr-online-fields" style="display:none;">
                                <label class="form-label">Online Payment Type:</label>
                                <select name="online_type" class="form-control">
                                    <option value="">Select type</option>
                                    <option value="bkash">bKash</option>
                                    <option value="nagad">Nagad</option>
                                    <option value="rocket">Rocket</option>
                                    <option value="upay">Upay</option>
                                    <option value="paypal">PayPal</option>
                                    <option value="stripe">Stripe</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <!-- Mandatory Disbursement Proof -->
                            <div class="col-md-12 mb-3">
                                <label class="form-label">
                                    <i class="fa-light fa-paperclip me-1"></i>
                                    Disbursement Proof <span class="text-danger">*</span>
                                    <span class="text-muted small ms-1">(JPG, PNG, PDF, DOC — max 5MB each)</span>
                                </label>
                                <input type="file" name="disbursement_proof[]" id="vrProofFiles"
                                    class="form-control" multiple
                                    accept="image/*,application/pdf,.doc,.docx" required>
                                <div id="vrFilePreview" class="d-flex flex-wrap gap-2 mt-2"></div>
                                <small class="text-danger">Upload at least one proof document (receipt, bank slip, screenshot, etc.)</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fa-light fa-check me-1"></i> Add Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const method  = document.getElementById('vrPaymentMethod');
    const bankF   = document.querySelectorAll('.vr-bank-fields');
    const chequeF = document.querySelectorAll('.vr-cheque-fields');
    const onlineF = document.querySelectorAll('.vr-online-fields');

    function toggleFields() {
        bankF.forEach(el => el.style.display = 'none');
        chequeF.forEach(el => el.style.display = 'none');
        onlineF.forEach(el => el.style.display = 'none');
        if (!method) return;
        if (method.value === 'bank')   bankF.forEach(el => el.style.display = 'block');
        if (method.value === 'cheque') chequeF.forEach(el => el.style.display = 'block');
        if (method.value === 'online') onlineF.forEach(el => el.style.display = 'block');
    }

    if (method) { method.addEventListener('change', toggleFields); toggleFields(); }

    // Load Company Banks options via AJAX
    window.companyBanksList = [];
    function loadCompanyBanks() {
        fetch('<?= $GLOBALS['base_url'] ?>/get-company-banks')
            .then(r => r.json())
            .then(data => {
                window.companyBanksList = data.banks || [];
                const sel = document.getElementById('vrBankSelect');
                if (!sel) return;
                const oldVal = sel.value;
                sel.innerHTML = '<option value="">-- Select Transfer To / Company Bank --</option>';
                window.companyBanksList.forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b.id;
                    opt.textContent = b.bank_name + ' - ' + b.account_name + (b.account_number ? ' (' + b.account_number + ')' : '');
                    sel.appendChild(opt);
                });
                if(oldVal) sel.value = oldVal;
            })
            .catch(e => console.error('Error loading company banks:', e));
    }
    loadCompanyBanks();

    document.getElementById('addCompanyBankBtn')?.addEventListener('click', function() {
        document.getElementById('companyBankModalTitle').textContent = 'Add Company Bank';
        document.getElementById('companyBankForm').reset();
        document.getElementById('cb_id').value = '';
        new bootstrap.Modal(document.getElementById('companyBankModal')).show();
    });

    document.getElementById('saveCompanyBankBtn')?.addEventListener('click', function() {
        const form = document.getElementById('companyBankForm');
        if(!form.checkValidity()) { form.reportValidity(); return; }
        
        const fd = new FormData(form);
        const isEdit = fd.get('id') !== '';
        const endpoint = isEdit ? '/update-company-bank-ajax' : '/store-company-bank-ajax';
        
        fetch('<?= $GLOBALS['base_url'] ?>' + endpoint, {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                loadCompanyBanks();
                bootstrap.Modal.getInstance(document.getElementById('companyBankModal')).hide();
                if(typeof toastr !== 'undefined') {
                    toastr.success(res.message || 'Bank saved successfully');
                } else {
                    alert('Bank saved successfully');
                }
            } else {
                alert(res.message || 'Failed to save bank');
            }
        })
        .catch(e => {
            console.error(e);
            alert('Error saving bank');
        });
    });
    // File thumbnail preview
    const proofInput = document.getElementById('vrProofFiles');
    const preview    = document.getElementById('vrFilePreview');
    if (proofInput && preview) {
        proofInput.addEventListener('change', function() {
            preview.innerHTML = '';
            Array.from(this.files).forEach(file => {
                const thumb = document.createElement('div');
                thumb.className = 'border rounded p-1 text-center';
                thumb.style.cssText = 'width:72px;font-size:10px;overflow:hidden;';
                if (file.type.startsWith('image/')) {
                    const img = document.createElement('img');
                    img.style.cssText = 'width:62px;height:46px;object-fit:cover;border-radius:3px;';
                    img.src = URL.createObjectURL(file);
                    thumb.appendChild(img);
                } else {
                    thumb.innerHTML = '<i class="fa-light fa-file-pdf fa-2x text-danger"></i>';
                }
                const n = document.createElement('div');
                n.textContent = file.name.length > 9 ? file.name.substr(0,9)+'...' : file.name;
                thumb.appendChild(n);
                preview.appendChild(thumb);
            });
        });
    }
});
</script>
<?php endif; ?>

<!-- Company Bank Add/Edit Modal -->
<div class="modal fade" id="companyBankModal" tabindex="-1" aria-labelledby="companyBankModalLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-primary text-white border-0 py-3">
                <h5 class="modal-title fw-bold text-white" id="companyBankModalLabel">
                    <i class="fa-light fa-building-columns me-2"></i><span id="companyBankModalTitle">Add Company Bank</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <form id="companyBankForm">
                    <input type="hidden" name="id" id="cb_id" value="">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fs-14 fw-semibold text-dark">Bank Name <span class="text-danger">*</span></label>
                            <input type="text" name="bank_name" id="cb_bank_name" class="form-control" required placeholder="e.g. Dutch Bangla Bank">
                        </div>
                        <div class="col-12">
                            <label class="form-label fs-14 fw-semibold text-dark">Account Name <span class="text-danger">*</span></label>
                            <input type="text" name="account_name" id="cb_account_name" class="form-control" required placeholder="e.g. TPHL">
                        </div>
                        <div class="col-12">
                            <label class="form-label fs-14 fw-semibold text-dark">Account Number</label>
                            <input type="text" name="account_number" id="cb_account_number" class="form-control" placeholder="e.g. 101.XXX.XXXXX">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0 pb-4 px-4 bg-light">
                <button type="button" class="btn btn-light border shadow-sm px-4 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="saveCompanyBankBtn" class="btn btn-primary px-4 rounded-pill shadow-sm"><i class="fa-light fa-save me-1"></i>Save Bank</button>
            </div>
        </div>
    </div>
</div>

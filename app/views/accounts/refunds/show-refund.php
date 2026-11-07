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

if (!class_exists('ApprovalRequest')) {
    require_once __DIR__ . '/../../../models/ApprovalRequest.php';
}
$ahInfo = ApprovalRequest::getAccountsHeadApprovalInfo($GLOBALS['db'] ?? ($this->db ?? null), 'client_refund_requests', (int)$refund['id']);
$isAhApproved = empty($ahInfo['has_request']) || !empty($ahInfo['is_accounts_head_approved']);
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
        <?php
        $backUrl = (!empty($_GET['ref']) && $_GET['ref'] === 'all_approvals') 
            ? "{$base_url}/all-approval-requests" 
            : "{$base_url}/client-refunds";
        $backLabel = (!empty($_GET['ref']) && $_GET['ref'] === 'all_approvals') 
            ? "Back to Approval Requests" 
            : "Back";
        ?>
        <a href="<?= $backUrl ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> <?= $backLabel ?>
        </a>
    </div>
</div>

<div class="container-fluid">
    <?php if (!empty($_GET['ref']) && $_GET['ref'] === 'all_approvals'): ?>
    <div class="alert alert-info py-2 px-3 small mb-3 d-flex align-items-center justify-content-between">
        <div>
            <i class="fa-solid fa-stamp me-1 text-primary"></i>
            Opened from <strong>All Approval Requests</strong> for review &amp; sign-off.
        </div>
        <a href="<?= $base_url ?>/all-approval-requests" class="btn btn-sm btn-outline-primary py-0 px-2 fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Back to All Requests
        </a>
    </div>
    <?php endif; ?>
    <div class="row">
        <!-- Main Content (Left) -->
        <div class="col-lg-8">
            <!-- Booking & Client Info -->
            <div class="card custom-card mb-3">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-person me-2"></i>Client & Booking Details</h6>
                </div>
                <div class="card-body">
                    <div class="row ">
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

                        <!-- Financial & Request Summary -->
                        <div class="col-md-12 mt-3">
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <div class="border rounded p-3 text-center bg-white">
                                        <small class="text-muted d-block mb-1">Total Verified Paid</small>
                                        <h5 class="text-primary mb-0 fw-bold">৳ <?= number_format($refund['total_paid'], 2) ?></h5>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded p-3 text-center border-danger bg-white">
                                        <small class="text-muted d-block mb-1">Retention (Non-refundable)</small>
                                        <?php
                                        $retPctSummary = ((float)$refund['total_paid'] > 0) ? round(((float)$refund['retention_amount'] / (float)$refund['total_paid']) * 100, 2) : 0;
                                        ?>
                                        <h5 class="text-danger mb-0 fw-bold">
                                            ৳ <?= number_format($refund['retention_amount'], 2) ?>
                                            <?php if ($retPctSummary > 0): ?>
                                                <span class="badge bg-danger-subtle text-danger fs-11 ms-1"><?= $retPctSummary ?>%</span>
                                            <?php endif; ?>
                                        </h5>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded p-3 text-center border-success bg-white">
                                        <small class="text-muted d-block mb-1">Net Refund to Pay</small>
                                        <h5 class="text-success mb-0 fw-bold">৳ <?= number_format($refund['refund_amount'], 2) ?></h5>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded p-3 text-center border-info bg-white">
                                        <small class="text-muted d-block mb-1"><i class="fa-regular fa-calendar-check text-info me-1"></i>Expected Refund Date</small>
                                        <h5 class="text-info mb-0 fw-bold fs-15">
                                            <?= !empty($refund['expected_refund_date']) ? date('d M Y', strtotime($refund['expected_refund_date'])) : '<span class="text-muted fs-13 fst-italic">Not specified</span>' ?>
                                        </h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sales Refund Reason / Note -->
                        <div class="col-md-12 mt-3">
                            <div class="card custom-card border mb-0 shadow-none" style="border-left: 4px solid #2563eb !important; background: #f8fafc;">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 28px; height: 28px; background: rgba(37, 99, 235, 0.1); color: #2563eb;">
                                                <i class="fa-solid fa-comment-dots fs-13"></i>
                                            </div>
                                            <h6 class="card-title mb-0 fs-13 fw-bold text-dark text-uppercase" style="letter-spacing: 0.5px;">Sales Refund Reason / Note</h6>
                                        </div>
                                        <?php if (!empty($refund['requested_at'])): ?>
                                            <span class="text-muted small fs-11">
                                                <i class="fa-regular fa-clock me-1"></i>Submitted: <?= date('d M Y, h:i A', strtotime($refund['requested_at'])) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="p-3 bg-white rounded border" style="min-height: 48px;">
                                        <?php if (!empty(trim($refund['reason'] ?? ''))): ?>
                                            <div class="d-flex align-items-start">
                                                <i class="fa-solid fa-quote-left text-primary opacity-50 fs-5 me-2 mt-1 flex-shrink-0"></i>
                                                <span class="text-dark fs-14 fw-medium" style="line-height: 1.6; word-break: break-word;">
                                                    <?= nl2br(htmlspecialchars($refund['reason'])) ?>
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-muted fst-italic fs-13">
                                                <i class="fa-solid fa-circle-info me-1"></i>No specific reason provided during refund request.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Client Payout Bank & Sales Application Attachment -->
                        <div class="col-md-12 mt-3">
                            <div class="card custom-card border mb-0 shadow-none">
                                <div class="card-header bg-light py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <i class="fa-solid fa-building-columns text-primary me-2"></i>
                                        <h6 class="card-title mb-0 fs-13 fw-semibold text-primary">Client Payout Bank &amp; Request Details (Submitted by Sales)</h6>
                                    </div>
                                    <?php if (!empty($refund['preferred_payment_method'])): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace text-uppercase" style="font-size: 11px;">
                                            Method: <?= htmlspecialchars($refund['preferred_payment_method']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body p-3 bg-white">
                                    <div class="row ">
                                        <div class="col-sm-6 col-md-4">
                                            <span class="text-muted small d-block">Client Bank Name:</span>
                                            <strong class="text-dark fs-13"><?= htmlspecialchars($refund['client_bank_name'] ?? '—') ?: '—' ?></strong>
                                        </div>
                                        <div class="col-sm-6 col-md-4">
                                            <span class="text-muted small d-block">A/C Holder Name:</span>
                                            <strong class="text-dark fs-13"><?= htmlspecialchars($refund['client_bank_account_name'] ?? '—') ?: '—' ?></strong>
                                        </div>
                                        <div class="col-sm-6 col-md-4">
                                            <span class="text-muted small d-block">Bank Account No:</span>
                                            <strong class="text-dark fs-13 font-monospace"><?= htmlspecialchars($refund['client_bank_account_no'] ?? '—') ?: '—' ?></strong>
                                        </div>
                                        <div class="col-sm-6 col-md-4">
                                            <span class="text-muted small d-block">Branch:</span>
                                            <strong class="text-dark fs-13"><?= htmlspecialchars($refund['client_bank_branch'] ?? '—') ?: '—' ?></strong>
                                        </div>
                                        <div class="col-sm-6 col-md-4">
                                            <span class="text-muted small d-block">Routing Number:</span>
                                            <strong class="text-dark fs-13 font-monospace"><?= htmlspecialchars($refund['client_bank_routing_no'] ?? '—') ?: '—' ?></strong>
                                        </div>
                                        <div class="col-sm-6 col-md-4">
                                            <span class="text-muted small d-block">Expected Refund Date:</span>
                                            <?php if (!empty($refund['expected_refund_date'])): ?>
                                                <strong class="text-primary fs-13"><i class="fa-light fa-calendar me-1"></i><?= date('d M Y', strtotime($refund['expected_refund_date'])) ?></strong>
                                            <?php else: ?>
                                                <span class="text-muted fst-italic small">Not specified</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-sm-6 col-md-4">
                                            <span class="text-muted small d-block">Application / Cheque Attachment:</span>
                                            <?php if (!empty($refund['document'])): ?>
                                                <a href="<?= $base_url ?>/<?= htmlspecialchars($refund['document']) ?>" target="_blank" class="btn btn-xs btn-outline-primary py-1 px-2.5 mt-0.5 fw-semibold">
                                                    <i class="bi bi-file-earmark-text me-1"></i> View Client Document
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted fst-italic small">No document attached</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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

            <!-- Approval Actions (Management & Role 20 only, pending status) -->
            <?php if ($canApproveReq && $isPending): ?>
            <?php
            $refundAmtToCheck = (float)$refund['refund_amount'];
            $isOver1LacRefund = ($refundAmtToCheck > 100000);
            $isOpHeadApprovedRefund = (!empty($ahInfo['is_op_head_approved']) || ($ahInfo['op_head_status'] ?? '') === 'approved');
            $userRoleRefund = (int)($_SESSION['user']['role_id'] ?? 0);
            $roleNameRefund = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
            $isOpHeadUserRefund = (in_array($userRoleRefund, [20, 32], true) || str_contains($roleNameRefund, 'operation head'));
            $isMgmtUserRefund = in_array($userRoleRefund, [1, 3, 38], true);
            ?>
            <div class="card custom-card border-0 shadow-sm mb-3" style="border-left: 4px solid #ffc107 !important; background: #fffdf5;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2.5">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-2 flex-shrink-0" style="width: 34px; height: 34px; background: rgba(255, 193, 7, 0.2); color: #b78103;">
                            <i class="bi bi-shield-lock-fill fs-6"></i>
                        </div>
                        <div>
                            <h6 class="card-title mb-0 fw-bold text-dark fs-14">Management &amp; Role 20 Approval</h6>
                            <span class="badge bg-warning text-dark font-monospace" style="font-size: 10px;">Approval Action</span>
                        </div>
                    </div>
                    
                    <div class="p-2.5 rounded-2 border small mb-3" style="font-size: 12px; line-height: 1.6; background-color: #ffffff;">
                        <span class="fw-semibold text-dark d-block mb-1.5 border-bottom pb-1">
                            <i class="fa-solid fa-clipboard-check text-primary me-1"></i>Request &amp; Approval Summary:
                        </span>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Refund Reason:</span>
                            <strong class="text-dark text-end ms-2" style="max-width: 170px; word-break: break-word;"><?= htmlspecialchars($refund['reason'] ?? '—') ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Expected Refund Date:</span>
                            <strong class="text-info"><?= !empty($refund['expected_refund_date']) ? date('d M Y', strtotime($refund['expected_refund_date'])) : '—' ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Client Payout Bank:</span>
                            <strong class="text-dark text-end ms-2"><?= htmlspecialchars($refund['client_bank_name'] ?? '—') ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Net Refund to Pay:</span>
                            <strong class="text-success">৳ <?= number_format($refund['refund_amount'], 2) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Company Retention:</span>
                            <strong class="text-danger">৳ <?= number_format($refund['retention_amount'], 2) ?></strong>
                        </div>
                    </div>

                    <?php if ($isOver1LacRefund && !$isOpHeadApprovedRefund && $isMgmtUserRefund && !$isOpHeadUserRefund): ?>
                        <div class="p-2 rounded-2 mb-2" style="font-size: 11.5px; background-color: #fff9e6; border: 1px solid #ffe082; color: #855700; line-height: 1.5;">
                            <i class="fa-solid fa-circle-info me-1 text-warning"></i><strong>Notice:</strong> High-value refund (> ৳1,00,000). Op Head verification is pending, but Management may approve directly.
                        </div>
                    <?php endif; ?>

                    <form action="<?= $base_url ?>/client-refund-approve/<?= $refund['id'] ?><?= (!empty($_GET['ref']) && $_GET['ref'] === 'all_approvals') ? '?ref=all_approvals' : '' ?>" method="POST" class="mb-2" id="approveForm">
                        <!-- Quick % Buttons -->
                        <div class="mb-2">
                            <label class="form-label fw-bold small mb-1 text-muted d-flex justify-content-between">
                                <span>Quick Cut-Off %</span>
                                <span class="text-danger fw-normal">Total Paid: ৳ <?= number_format($refund['total_paid'], 2) ?></span>
                            </label>
                            <div class="d-flex flex-wrap gap-1">
                                <?php foreach ([0, 5, 10, 15, 20, 25, 30] as $pct): ?>
                                    <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2 fw-semibold quick-pct-btn-app" data-pct="<?= $pct ?>"><?= $pct ?>%</button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-5">
                                <label class="form-label fw-bold small mb-1 text-danger">Retention (%)</label>
                                <?php
                                $appRetPct = ((float)$refund['total_paid'] > 0) ? round(((float)$refund['retention_amount'] / (float)$refund['total_paid']) * 100, 2) : 0;
                                ?>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" min="0" max="100" name="retention_percent" id="app_retention_percent" class="form-control form-control-sm fw-bold border-danger text-danger" value="<?= $appRetPct > 0 ? $appRetPct : '' ?>" placeholder="0 - 100">
                                    <span class="input-group-text bg-danger text-white fw-bold">%</span>
                                </div>
                            </div>
                            <div class="col-7">
                                <label class="form-label fw-bold small mb-1 text-danger">Retention (৳)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light fw-bold">৳</span>
                                    <input type="number" step="0.01" name="retention_amount" id="app_retention_amount" class="form-control form-control-sm fw-bold border-danger text-danger" value="<?= htmlspecialchars($refund['retention_amount']) ?>" min="0" max="<?= htmlspecialchars($refund['total_paid']) ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small mb-1 text-success">Approved Refund Amount (৳)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-success text-white fw-bold">৳</span>
                                <input type="number" step="0.01" name="approved_amount" id="app_approved_amount" class="form-control form-control-sm fw-bold border-success text-success" value="<?= htmlspecialchars($refund['refund_amount']) ?>" min="0" max="<?= htmlspecialchars($refund['total_paid']) ?>" required>
                            </div>
                        </div>
                        <?php if ($isOver1LacRefund && !$isOpHeadApprovedRefund && $isOpHeadUserRefund): ?>
                            <button type="button" class="btn btn-success w-100 fw-bold btn-sm py-2 shadow-sm" onclick="confirmApproval()">
                                <i class="fa-solid fa-check-double me-1"></i> Verify &amp; Approve (Op Head)
                            </button>
                        <?php elseif ($isMgmtUserRefund): ?>
                            <button type="button" class="btn btn-success w-100 fw-bold btn-sm py-2 shadow-sm" onclick="confirmApproval()">
                                <i class="bi bi-check-circle me-1"></i> Approve Refund (Management)
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-success w-100 fw-bold btn-sm py-2 shadow-sm" onclick="confirmApproval()">
                                <i class="bi bi-check-circle me-1"></i> Approve Refund
                            </button>
                        <?php endif; ?>
                    </form>
                    <form action="<?= $base_url ?>/client-refund-reject/<?= $refund['id'] ?><?= (!empty($_GET['ref']) && $_GET['ref'] === 'all_approvals') ? '?ref=all_approvals' : '' ?>" method="POST" id="rejectForm">
                        <button type="button" class="btn btn-outline-danger w-100 btn-sm" onclick="confirmRejection()">
                            <i class="bi bi-x-circle me-1"></i> Reject
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Accounts Retention Cut-Off Action (Pending status before Management approval) -->
            <?php
            $isAccountsRole = in_array((int)$userRole, [1, 3, 6, 7, 9, 10, 20, 30, 33, 37], true);
            ?>
            <?php if ($isPending && !$canApproveReq && $isAccountsRole): ?>
            <div class="card custom-card border-0 shadow-sm mb-3" style="border-left: 4px solid #fd7e14 !important; background: #fffaf5;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2.5">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-2 flex-shrink-0" style="width: 34px; height: 34px; background: rgba(253, 126, 20, 0.2); color: #d66408;">
                            <i class="bi bi-scissors fs-5"></i>
                        </div>
                        <div>
                            <h6 class="card-title mb-0 fw-bold text-dark fs-14">Accounts Retention Cut-Off</h6>
                            <span class="badge bg-warning text-dark font-monospace" style="font-size: 10px;">Accounts Action</span>
                        </div>
                    </div>
                    
                    <div class="p-2.5 rounded-2  mt-2 small mb-3 text-secondary" style="font-size: 12px; line-height: 1.6;">
                        <span class="fw-semibold text-dark d-block mb-1"><i class="bi bi-info-circle text-warning me-1"></i>Guideline:</span>
                        Prior to Management approval, Accounts department can set the company retention fee (% or fixed ৳) deducted from client verified paid amount (<strong>৳ <?= number_format($refund['total_paid'], 2) ?></strong>).
                    </div>

                    <form action="<?= $base_url ?>/client-refund-adjust/<?= $refund['id'] ?>" method="POST" id="accPendingRetentionForm">
                        <!-- Quick % Buttons -->
                        <div class="mb-2">
                            <label class="form-label fw-bold small mb-1 text-muted d-flex justify-content-between">
                                <span>Quick Cut-Off %</span>
                                <span class="text-danger fw-normal">Total Paid: ৳ <?= number_format($refund['total_paid'], 2) ?></span>
                            </label>
                            <div class="d-flex flex-wrap gap-1">
                                <?php foreach ([0, 5, 10, 15, 20, 25, 30] as $pct): ?>
                                    <button type="button" class="btn btn-xs btn-outline-warning py-0 px-2 fw-semibold quick-pct-btn-acc" data-pct="<?= $pct ?>"><?= $pct ?>%</button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-5">
                                <label class="form-label fw-bold small mb-1 text-danger">Retention (%)</label>
                                <?php
                                $accRetPct = ((float)$refund['total_paid'] > 0) ? round(((float)$refund['retention_amount'] / (float)$refund['total_paid']) * 100, 2) : 0;
                                ?>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" min="0" max="100" name="retention_percent" id="pending_acc_retention_percent" class="form-control form-control-sm fw-bold border-danger text-danger" value="<?= $accRetPct > 0 ? $accRetPct : '' ?>" placeholder="0 - 100">
                                    <span class="input-group-text bg-danger text-white fw-bold">%</span>
                                </div>
                            </div>
                            <div class="col-7">
                                <label class="form-label fw-bold small mb-1 text-danger">Retention (৳)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light fw-bold">৳</span>
                                    <input type="number" step="0.01" name="retention_amount" id="pending_acc_retention_amount" class="form-control form-control-sm fw-bold border-danger text-danger" value="<?= htmlspecialchars($refund['retention_amount']) ?>" min="0" max="<?= htmlspecialchars($refund['total_paid']) ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small mb-1 text-success">Net Refund to Client (৳)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-success text-white fw-bold">৳</span>
                                <input type="number" step="0.01" id="pending_acc_net_refund" class="form-control form-control-sm fw-bold border-success text-success bg-light" value="<?= htmlspecialchars($refund['refund_amount']) ?>" readonly>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-warning w-100 fw-bold btn-sm py-2 shadow-sm d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-scissors fs-6"></i> Save Retention Cut-Off
                        </button>
                    </form>

                    <div class="p-2 rounded bg-light border border-light-subtle small mt-3 text-secondary" style="font-size: 11.5px; line-height: 1.5;">
                        <i class="bi bi-shield-lock text-primary me-1"></i>
                        Once retention is set, this request will automatically move to Management and Role 20 for approval.
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Informative Box for Non-Accounts / Non-Management when Pending -->
            <?php if ($isPending && !$canApproveReq && !$isAccountsRole): ?>
            <div class="card custom-card border-0 shadow-sm mb-3" style="border-left: 4px solid #0d6efd !important; background: #f8faff;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2.5">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-2 flex-shrink-0" style="width: 34px; height: 34px; background: rgba(13, 110, 253, 0.12); color: #0d6efd;">
                            <i class="bi bi-hourglass-split fs-6"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold fs-13 text-primary">Awaiting Management &amp; Role 20 Approval</h6>
                            <span class="badge bg-warning text-dark font-monospace" style="font-size: 10px;">Pending Approval</span>
                        </div>
                    </div>
                    
                    <div class="p-2.5 rounded-2 bg-white border border-light-subtle small mt-2 text-secondary" style="font-size: 12.5px; line-height: 1.6;">
                        <div class="fw-semibold text-dark mb-1">
                            <i class="bi bi-shield-check text-success me-1"></i> Retention &amp; Payout Guideline:
                        </div>
                        This refund request is awaiting <strong>Management &amp; Role 20</strong> approval. After approval, Accounts department can disburse payment with voucher/cheque attachments.
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Adjust Retention / Refund Amount Action (Accounts & Management, Approved status before final payout) -->
            <?php if ($status === 'approved' && in_array((int)$userRole, [1, 3], true)): ?>
            <div class="card custom-card border-0 shadow-sm mb-3" style="border-left: 4px solid #0d6efd !important; background: #f0f7ff;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2.5">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-2 flex-shrink-0" style="width: 34px; height: 34px; background: rgba(13, 110, 253, 0.15); color: #0d6efd;">
                            <i class="bi bi-sliders2-vertical fs-5"></i>
                        </div>
                        <div>
                            <h6 class="card-title mb-0 fw-bold text-dark fs-14">Retention &amp; Refund Adjustment</h6>
                            <span class="badge bg-primary text-white font-monospace" style="font-size: 10px;">Accounts &amp; Management</span>
                        </div>
                    </div>
                    
                    <div class="p-2.5 rounded-2  border  small mb-3 text-secondary" style="font-size: 12px; line-height: 1.6;">
                        <span class="fw-semibold text-dark d-block mb-1"><i class="bi bi-shield-check text-primary me-1"></i>Auto-Sync Across All 3 Tables:</span>
                        If retention fee is adjusted, updates will automatically synchronize across <strong>Client Refund, Cashout Request (<?= !empty($refund['cashout_request_id']) ? '#' . htmlspecialchars($refund['cashout_request_id']) : '—' ?>), and Approval Request</strong> tables.
                    </div>

                    <form action="<?= $base_url ?>/client-refund-adjust/<?= $refund['id'] ?>" method="POST" id="adjustRetentionForm">
                        <div class="mb-2">
                            <label class="form-label fw-bold small mb-1 text-muted">Total Verified Paid</label>
                            <input type="text" class="form-control form-control-sm bg-light fw-bold text-dark" value="৳ <?= number_format($refund['total_paid'], 2) ?>" readonly>
                        </div>

                        <!-- Quick % Buttons -->
                        <div class="mb-2">
                            <label class="form-label fw-bold small mb-1 text-muted">Quick Cut-Off %</label>
                            <div class="d-flex flex-wrap gap-1">
                                <?php foreach ([0, 5, 10, 15, 20, 25, 30] as $pct): ?>
                                    <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 fw-semibold quick-pct-btn-adj" data-pct="<?= $pct ?>"><?= $pct ?>%</button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-5">
                                <label class="form-label fw-bold small mb-1 text-danger">Retention (%)</label>
                                <?php
                                $adjRetPct = ((float)$refund['total_paid'] > 0) ? round(((float)$refund['retention_amount'] / (float)$refund['total_paid']) * 100, 2) : 0;
                                ?>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" min="0" max="100" name="retention_percent" id="adj_retention_percent" class="form-control form-control-sm fw-bold border-danger text-danger" value="<?= $adjRetPct > 0 ? $adjRetPct : '' ?>" placeholder="0 - 100">
                                    <span class="input-group-text bg-danger text-white fw-bold">%</span>
                                </div>
                            </div>
                            <div class="col-7">
                                <label class="form-label fw-bold small mb-1 text-danger">Retention (৳)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light fw-bold">৳</span>
                                    <input type="number" step="0.01" name="retention_amount" id="adj_retention_amount" class="form-control form-control-sm fw-bold border-danger text-danger" value="<?= htmlspecialchars($refund['retention_amount']) ?>" min="0" max="<?= htmlspecialchars($refund['total_paid']) ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small mb-1 text-success">Net Refund to Client (৳)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-success text-white fw-bold">৳</span>
                                <input type="number" step="0.01" id="adj_net_refund_amount" class="form-control form-control-sm fw-bold border-success text-success" value="<?= htmlspecialchars($refund['refund_amount']) ?>" min="0" max="<?= htmlspecialchars($refund['total_paid']) ?>" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-bold btn-sm py-2 shadow-sm d-flex align-items-center justify-content-center gap-2" onclick="return confirm('Are you sure you want to adjust retention & refund amounts and synchronize across Refund, Cashout, and Approval tables?');">
                            <i class="bi bi-arrow-repeat fs-6"></i> Update Across All 3 Tables
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Accounts Payout Action (Approved status) -->
            <?php if ($status === 'approved' && in_array($userRole, [1, 3, 6, 10, 30])): ?>
            <div class="card custom-card border-0 shadow-sm mb-3" style="border-left: 4px solid #198754 !important; background: #f4fbf7;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2.5">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-2 flex-shrink-0" style="width: 34px; height: 34px; background: rgba(25, 135, 84, 0.15); color: #198754;">
                            <i class="bi bi-check2-circle fs-5"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold fs-14 text-success">Approved by Management</h6>
                            <span class="badge bg-success text-white font-monospace" style="font-size: 10px;">Approved</span>
                        </div>
                    </div>
                    <div class="p-2.5 rounded-2 bg-white border border-light-subtle small mt-2 mb-3 text-secondary" style="font-size: 12.5px; line-height: 1.6;">
                        <span class="fw-semibold text-dark d-block mb-1">Action for Accounts Department:</span>
                        Approved by Management. Now disburse refund payment with voucher/cheque attachment.
                    </div>
                    <button type="button" class="btn btn-success w-100 fw-bold py-2 shadow-sm d-flex align-items-center justify-content-center gap-2" data-bs-toggle="modal" data-bs-target="#disburseRefundModal">
                        <i class="bi bi-cash-stack fs-6"></i> Pay Refund (with Attachment)
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Refund Completed -->
            <?php if ($status === 'refunded'): ?>
            <div class="card custom-card border-0 shadow-sm mb-3" style="border-left: 4px solid #198754 !important; background: #f4fbf7;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2.5">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-2 flex-shrink-0" style="width: 34px; height: 34px; background: rgba(25, 135, 84, 0.15); color: #198754;">
                            <i class="bi bi-check-all fs-5"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold fs-14 text-success">Refund Disbursed &amp; Paid</h6>
                            <span class="badge bg-success text-white font-monospace" style="font-size: 10px;">Refunded Paid</span>
                        </div>
                    </div>
                    <div class="p-2.5 rounded-2 bg-white border border-light-subtle small mt-2 mb-3" style="font-size: 12px; line-height: 1.6;">
                        <span class="fw-semibold text-dark d-block mb-1 border-bottom pb-1">Payment Settlement Summary:</span>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Total Collected:</span>
                            <strong class="text-dark">৳ <?= number_format($refund['total_paid'], 2) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Company Retention (<?= $retPctSummary ?>%):</span>
                            <strong class="text-danger">৳ <?= number_format($refund['retention_amount'], 2) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Net Disbursed to Client:</span>
                            <strong class="text-success">৳ <?= number_format($refund['refund_amount'], 2) ?></strong>
                        </div>
                        <?php if (!empty($refund['refunded_at'])): ?>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Disbursed Date:</span>
                            <strong class="text-dark"><?= date('d M Y, h:i A', strtotime($refund['refunded_at'])) ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php 
                        $payoutProof = !empty($refund['disbursement_proof']) ? $refund['disbursement_proof'] : (!empty($refund['document']) ? $refund['document'] : null);
                    ?>
                    <?php if ($payoutProof): ?>
                    <div class="mt-2 pt-2 border-top">
                        <a href="<?= $base_url ?>/<?= htmlspecialchars($payoutProof) ?>" target="_blank" class="btn btn-sm btn-outline-success w-100 fw-semibold">
                            <i class="bi bi-file-earmark-arrow-down me-1"></i> View Payment Voucher / Attachment
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ========================================================================= -->
            <!-- Modal: Accounts Refund Disbursement Payout (Matching Image 2) -->
            <!-- ========================================================================= -->
            <?php if ($status === 'approved' && in_array($userRole, [1, 3, 6, 10, 30])): ?>
            <?php
                $preMethod = !empty($refund['preferred_payment_method']) ? strtolower($refund['preferred_payment_method']) : 'bank';
                $clientBankVal = $refund['client_bank_name'] ?? '';
                $clientAcVal   = $refund['client_bank_account_no'] ?? '';
            ?>
            <div class="modal fade" id="disburseRefundModal" tabindex="-1" aria-labelledby="disburseRefundModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-success text-white py-2.5 px-3">
                            <h5 class="modal-title fs-15 fw-bold text-white" id="disburseRefundModalLabel">
                                <i class="fa-solid fa-money-bill-transfer me-2"></i>Accounts Refund Disbursement Payout
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="<?= $base_url ?>/client-refund-disburse/<?= $refund['id'] ?><?= (!empty($_GET['ref']) && $_GET['ref'] === 'all_approvals') ? '?ref=all_approvals' : '' ?>" method="POST" enctype="multipart/form-data" id="disburseForm">
                            <div class="modal-body p-3">
                                <!-- Client & Amount Summary Banner matching Image 2 -->
                                <div class="p-3 rounded-2 bg-light border mb-3">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <span class="text-muted small d-block">Client & Flat:</span>
                                            <strong class="text-dark fs-14"><?= htmlspecialchars($refund['client_name'] ?? '—') ?><?= !empty($refund['client_phone']) ? ' (' . htmlspecialchars($refund['client_phone']) . ')' : '' ?></strong>
                                            <div class="text-muted small"><?= htmlspecialchars($refund['project_name'] ?? '—') ?> — Flat: <?= htmlspecialchars($refund['flat_name'] ?? '—') ?></div>
                                            <?php if (!empty($clientBankVal) || !empty($clientAcVal)): ?>
                                                <div class="mt-1 fs-12 text-primary">
                                                    <i class="fa-solid fa-building-columns me-1"></i> Client Bank: <strong><?= htmlspecialchars($clientBankVal ?: '—') ?></strong> (A/C: <?= htmlspecialchars($clientAcVal ?: '—') ?>)
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6 text-md-end">
                                            <span class="text-muted small d-block">Approved Net Refund to Pay:</span>
                                            <strong class="text-success fs-18 font-monospace">৳ <?= number_format($refund['refund_amount'], 2) ?></strong>
                                            <div class="text-danger small">Retention: ৳ <?= number_format($refund['retention_amount'] ?? 0, 2) ?> | Total Paid: ৳ <?= number_format($refund['total_paid'] ?? 0, 2) ?></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Form Grid matching Image 2 -->
                                <div class="row ">
                                    <!-- Payment Method * -->
                                    <div class="col-md-6">
                                        <label class="form-label fs-12 fw-semibold text-dark">Payment Method <span class="text-danger">*</span></label>
                                        <select name="payment_method" id="disburseMethod" class="form-select form-select-sm" required onchange="toggleDisburseFields(this.value)">
                                            <option value="bank" <?= $preMethod === 'bank' ? 'selected' : '' ?>>Bank</option>
                                            <option value="cheque" <?= $preMethod === 'cheque' ? 'selected' : '' ?>>Cheque</option>
                                            <option value="cash" <?= $preMethod === 'cash' ? 'selected' : '' ?>>Cash</option>
                                            <option value="online" <?= $preMethod === 'online' ? 'selected' : '' ?>>Online</option>
                                        </select>
                                    </div>

                                    <!-- Transfer To / Company Bank * -->
                                    <div class="col-md-6" id="disburseCompanyBankCol">
                                        <label class="form-label fs-12 fw-semibold text-dark">Transfer To / Company Bank <span class="text-danger">*</span></label>
                                        <div class="input-group input-group-sm">
                                            <select name="company_bank_id" id="disburseCompanyBankSelect" class="form-select form-select-sm">
                                                <option value="">-- Select Transfer To / Company Bank --</option>
                                                <?php if (!empty($companyBanks)): ?>
                                                    <?php foreach ($companyBanks as $cb): ?>
                                                        <option value="<?= $cb['id'] ?>">
                                                            <?= htmlspecialchars($cb['bank_name'] . (!empty($cb['account_name']) ? ' - ' . $cb['account_name'] : '') . (!empty($cb['account_number']) ? ' (' . $cb['account_number'] . ')' : '')) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                            <button type="button" class="btn btn-outline-primary" id="addCompanyBankBtn" title="Add Company Bank"><i class="fa-light fa-plus"></i></button>
                                        </div>
                                    </div>

                                    <!-- Transfer From / Client Bank * -->
                                    <div class="col-md-6" id="disburseClientBankCol">
                                        <label class="form-label fs-12 fw-semibold text-dark">Transfer From / Client Bank <span class="text-danger">*</span></label>
                                        <select name="bank_name" id="disburseClientBankSelect" class="form-select form-select-sm" style="width: 100%;">
                                            <option value="">-- Select Client Bank --</option>
                                            <option value="Other Country / Foreign Bank" <?= $clientBankVal === 'Other Country / Foreign Bank' ? 'selected' : '' ?>>Other Country / Foreign Bank</option>
                                            <option value="Other Bank" <?= $clientBankVal === 'Other Bank' ? 'selected' : '' ?>>Other Bank</option>
                                            <?php if (!empty($clientBankVal) && !in_array($clientBankVal, $allBangladeshBanks ?? []) && !in_array($clientBankVal, ['Other Country / Foreign Bank', 'Other Bank'])): ?>
                                                <option value="<?= htmlspecialchars($clientBankVal) ?>" selected><?= htmlspecialchars($clientBankVal) ?></option>
                                            <?php endif; ?>
                                            <?php if (!empty($allBangladeshBanks)): ?>
                                                <?php foreach ($allBangladeshBanks as $bName): ?>
                                                    <option value="<?= htmlspecialchars($bName) ?>" <?= (strcasecmp($clientBankVal, $bName) === 0) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($bName) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>

                                    <!-- Client Bank A/C No. (Optional) -->
                                    <div class="col-md-6" id="disburseClientAcCol">
                                        <label class="form-label fs-12 fw-semibold text-dark">Client Bank A/C No. <span class="text-muted">(Optional)</span></label>
                                        <input type="text" name="bank_account_no" id="disburseClientAcNo" class="form-control form-control-sm" placeholder="Account Number" value="<?= htmlspecialchars($clientAcVal) ?>">
                                    </div>

                                    <!-- Cheque Number (When Cheque selected) -->
                                    <div class="col-md-6" id="disburseChequeCol" style="display:none;">
                                        <label class="form-label fs-12 fw-semibold text-dark">Cheque Number <span class="text-danger">*</span></label>
                                        <input type="text" name="cheque_no" id="disburseChequeNo" class="form-control form-control-sm" placeholder="Enter cheque number">
                                    </div>

                                    <!-- Payment Date * -->
                                    <div class="col-md-6">
                                        <label class="form-label fs-12 fw-semibold text-dark">Payment Date <span class="text-danger">*</span></label>
                                        <input type="date" name="payment_date" id="disbursePaymentDate" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                                    </div>

                                    <!-- Transaction Reference -->
                                    <div class="col-md-6" id="disburseRefCol">
                                        <label class="form-label fs-12 fw-semibold text-dark">Transaction Reference</label>
                                        <input type="text" name="transaction_ref" id="disburseTransactionRef" class="form-control form-control-sm" placeholder="Required reference number">
                                    </div>

                                    <!-- Attachment Image (Mandatory) -->
                                    <div class="col-md-12">
                                        <label class="form-label fs-12 fw-semibold text-dark">
                                            <i class="fa-light fa-paperclip text-primary me-1"></i> Attachment Image / Voucher Proof <span class="text-danger">*</span>
                                        </label>
                                        <input type="file" name="disbursement_proof" id="disburseProofFile" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" required>
                                        <small class="text-muted fs-11 d-block mt-1">
                                            Mandatory: Upload scanned copy/photo of cheque, bank transfer slip, or signed voucher (Max 10MB).
                                        </small>
                                        <div id="disburseFilePreview" class="d-flex flex-wrap gap-2 mt-2"></div>
                                    </div>

                                    <!-- Remarks / Notes -->
                                    <div class="col-md-12">
                                        <label class="form-label fs-12 fw-semibold text-dark">Remarks / Payment Notes</label>
                                        <textarea name="remarks" class="form-control form-control-sm" rows="2" placeholder="Optional payout details or remarks..."></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer bg-light py-2 px-3">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-success btn-sm fw-bold px-3">
                                    <i class="fa-light fa-check me-1"></i> Disburse & Record Payment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <script>
            function toggleDisburseFields(method) {
                const coBankCol     = document.getElementById('disburseCompanyBankCol');
                const clientBankCol = document.getElementById('disburseClientBankCol');
                const clientAcCol   = document.getElementById('disburseClientAcCol');
                const chequeCol     = document.getElementById('disburseChequeCol');
                const refCol        = document.getElementById('disburseRefCol');
                const coBankSelect  = document.getElementById('disburseCompanyBankSelect');
                const clientBankSelect = document.getElementById('disburseClientBankSelect');
                const chequeInput   = document.getElementById('disburseChequeNo');

                if (method === 'bank') {
                    if (coBankCol) coBankCol.style.display = 'block';
                    if (clientBankCol) clientBankCol.style.display = 'block';
                    if (clientAcCol) clientAcCol.style.display = 'block';
                    if (chequeCol) chequeCol.style.display = 'none';
                    if (refCol) refCol.style.display = 'block';
                    if (coBankSelect) coBankSelect.required = true;
                    if (clientBankSelect) clientBankSelect.required = true;
                    if (chequeInput) chequeInput.required = false;
                } else if (method === 'cheque') {
                    if (coBankCol) coBankCol.style.display = 'block';
                    if (clientBankCol) clientBankCol.style.display = 'none';
                    if (clientAcCol) clientAcCol.style.display = 'none';
                    if (chequeCol) chequeCol.style.display = 'block';
                    if (refCol) refCol.style.display = 'none';
                    if (coBankSelect) coBankSelect.required = true;
                    if (clientBankSelect) clientBankSelect.required = false;
                    if (chequeInput) chequeInput.required = true;
                } else if (method === 'online') {
                    if (coBankCol) coBankCol.style.display = 'block';
                    if (clientBankCol) clientBankCol.style.display = 'block';
                    if (clientAcCol) clientAcCol.style.display = 'block';
                    if (chequeCol) chequeCol.style.display = 'none';
                    if (refCol) refCol.style.display = 'block';
                    if (coBankSelect) coBankSelect.required = true;
                    if (clientBankSelect) clientBankSelect.required = false;
                    if (chequeInput) chequeInput.required = false;
                } else { // cash
                    if (coBankCol) coBankCol.style.display = 'none';
                    if (clientBankCol) clientBankCol.style.display = 'none';
                    if (clientAcCol) clientAcCol.style.display = 'none';
                    if (chequeCol) chequeCol.style.display = 'none';
                    if (refCol) refCol.style.display = 'none';
                    if (coBankSelect) coBankSelect.required = false;
                    if (clientBankSelect) clientBankSelect.required = false;
                    if (chequeInput) chequeInput.required = false;
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                toggleDisburseFields(document.getElementById('disburseMethod')?.value || 'bank');

                const proofInput = document.getElementById('disburseProofFile');
                const preview    = document.getElementById('disburseFilePreview');
                if (proofInput && preview) {
                    proofInput.addEventListener('change', function() {
                        preview.innerHTML = '';
                        Array.from(this.files).forEach(file => {
                            const thumb = document.createElement('div');
                            thumb.className = 'border rounded p-1 text-center bg-white shadow-sm';
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

                // If opened with ?pay=1
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('pay') === '1') {
                    const modalEl = document.getElementById('disburseRefundModal');
                    if (modalEl) {
                        const m = new bootstrap.Modal(modalEl);
                        m.show();
                    }
                }
            });
            </script>
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
                        <button type="button" class="btn btn-success w-100 fw-bold mb-3 shadow-sm d-flex align-items-center justify-content-center gap-1" data-bs-toggle="modal" data-bs-target="#disburseRefundModal">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load Company Banks options via AJAX for the quick modal if needed
    window.companyBanksList = [];
    function loadCompanyBanks() {
        fetch('<?= $GLOBALS['base_url'] ?>/get-company-banks')
            .then(r => r.json())
            .then(data => {
                window.companyBanksList = data.banks || [];
                const sel = document.getElementById('disburseCompanyBankSelect');
                if (!sel) return;
                const oldVal = sel.value;
                sel.innerHTML = '<option value="">-- Select Transfer To / Company Bank --</option>';
                window.companyBanksList.forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b.id;
                    opt.textContent = b.bank_name + (b.account_name ? ' - ' + b.account_name : '') + (b.account_number ? ' (' + b.account_number + ')' : '');
                    sel.appendChild(opt);
                });
                if (oldVal) sel.value = oldVal;
            })
            .catch(e => console.error('Error loading company banks:', e));
    }

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
});
</script>

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
                    <div class="row ">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const totalPaid = <?= json_encode((float)($refund['total_paid'] ?? 0)) ?>;

    // Helper: calculate % from amount
    function calcPercent(amount) {
        if (!totalPaid || totalPaid <= 0) return 0;
        return parseFloat(((amount / totalPaid) * 100).toFixed(2));
    }

    // Helper: calculate amount from %
    function calcAmount(percent) {
        if (!totalPaid || totalPaid <= 0) return 0;
        return parseFloat(((totalPaid * percent) / 100).toFixed(2));
    }

    // =========================================================================
    // 1. Management Approval Form (Pending Status)
    // =========================================================================
    const appPct = document.getElementById('app_retention_percent');
    const appRet = document.getElementById('app_retention_amount');
    const appApp = document.getElementById('app_approved_amount');

    if (appRet && appApp) {
        if (appPct) {
            appPct.addEventListener('input', function() {
                let p = parseFloat(this.value);
                if (isNaN(p) || p < 0) p = 0;
                if (p > 100) p = 100;
                let retAmt = calcAmount(p);
                appRet.value = retAmt.toFixed(2);
                appApp.value = Math.max(0, totalPaid - retAmt).toFixed(2);
            });
        }

        appRet.addEventListener('input', function() {
            let ret = parseFloat(this.value);
            if (isNaN(ret) || ret < 0) ret = 0;
            if (ret > totalPaid) ret = totalPaid;
            appApp.value = Math.max(0, totalPaid - ret).toFixed(2);
            if (appPct) appPct.value = calcPercent(ret);
        });

        appApp.addEventListener('input', function() {
            let app = parseFloat(this.value);
            if (isNaN(app) || app < 0) app = 0;
            if (app > totalPaid) app = totalPaid;
            let retAmt = Math.max(0, totalPaid - app);
            appRet.value = retAmt.toFixed(2);
            if (appPct) appPct.value = calcPercent(retAmt);
        });

        document.querySelectorAll('.quick-pct-btn-app').forEach(btn => {
            btn.addEventListener('click', function() {
                let p = parseFloat(this.dataset.pct);
                if (appPct) appPct.value = p;
                let retAmt = calcAmount(p);
                appRet.value = retAmt.toFixed(2);
                appApp.value = Math.max(0, totalPaid - retAmt).toFixed(2);
            });
        });
    }

    // =========================================================================
    // 2. Accounts Retention Cut-Off Form (Pending Status)
    // =========================================================================
    const penPct = document.getElementById('pending_acc_retention_percent');
    const penRet = document.getElementById('pending_acc_retention_amount');
    const penNet = document.getElementById('pending_acc_net_refund');

    if (penRet && penNet) {
        if (penPct) {
            penPct.addEventListener('input', function() {
                let p = parseFloat(this.value);
                if (isNaN(p) || p < 0) p = 0;
                if (p > 100) p = 100;
                let retAmt = calcAmount(p);
                penRet.value = retAmt.toFixed(2);
                penNet.value = Math.max(0, totalPaid - retAmt).toFixed(2);
            });
        }

        penRet.addEventListener('input', function() {
            let ret = parseFloat(this.value);
            if (isNaN(ret) || ret < 0) ret = 0;
            if (ret > totalPaid) ret = totalPaid;
            penNet.value = Math.max(0, totalPaid - ret).toFixed(2);
            if (penPct) penPct.value = calcPercent(ret);
        });

        document.querySelectorAll('.quick-pct-btn-acc').forEach(btn => {
            btn.addEventListener('click', function() {
                let p = parseFloat(this.dataset.pct);
                if (penPct) penPct.value = p;
                let retAmt = calcAmount(p);
                penRet.value = retAmt.toFixed(2);
                penNet.value = Math.max(0, totalPaid - retAmt).toFixed(2);
            });
        });
    }

    // =========================================================================
    // 3. Adjust Retention Form (Approved Status)
    // =========================================================================
    const adjPct = document.getElementById('adj_retention_percent');
    const adjRet = document.getElementById('adj_retention_amount');
    const adjNet = document.getElementById('adj_net_refund_amount');

    if (adjRet && adjNet) {
        if (adjPct) {
            adjPct.addEventListener('input', function() {
                let p = parseFloat(this.value);
                if (isNaN(p) || p < 0) p = 0;
                if (p > 100) p = 100;
                let retAmt = calcAmount(p);
                adjRet.value = retAmt.toFixed(2);
                adjNet.value = Math.max(0, totalPaid - retAmt).toFixed(2);
            });
        }

        adjRet.addEventListener('input', function() {
            let ret = parseFloat(this.value);
            if (isNaN(ret) || ret < 0) ret = 0;
            if (ret > totalPaid) ret = totalPaid;
            adjNet.value = Math.max(0, totalPaid - ret).toFixed(2);
            if (adjPct) adjPct.value = calcPercent(ret);
        });

        adjNet.addEventListener('input', function() {
            let net = parseFloat(this.value);
            if (isNaN(net) || net < 0) net = 0;
            if (net > totalPaid) net = totalPaid;
            let retAmt = Math.max(0, totalPaid - net);
            adjRet.value = retAmt.toFixed(2);
            if (adjPct) adjPct.value = calcPercent(retAmt);
        });

        document.querySelectorAll('.quick-pct-btn-adj').forEach(btn => {
            btn.addEventListener('click', function() {
                let p = parseFloat(this.dataset.pct);
                if (adjPct) adjPct.value = p;
                let retAmt = calcAmount(p);
                adjRet.value = retAmt.toFixed(2);
                adjNet.value = Math.max(0, totalPaid - retAmt).toFixed(2);
            });
        });
    }
});
</script>


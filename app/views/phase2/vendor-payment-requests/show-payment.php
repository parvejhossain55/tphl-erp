<?php
$base_url = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Payment Request Details';

$statusMap = [
    'Draft' => ['label' => 'Draft', 'color' => 'warning'],
    'Submitted' => ['label' => 'Submitted', 'color' => 'info'],
    'QC Approved' => ['label' => 'QC Approved', 'color' => 'success'],
    'Approved' => ['label' => 'Approved', 'color' => 'success'],
    'Rejected' => ['label' => 'Rejected', 'color' => 'danger'],
    'Paid' => ['label' => 'Paid', 'color' => 'secondary'],
    'Partially_Paid' => ['label' => 'Partially Paid', 'color' => 'info'],
    'Fully_Paid' => ['label' => 'Fully Paid', 'color' => 'secondary'],
];
$status = $request['status'] ?? 'Draft';
$statusInfo = $statusMap[$status] ?? ['label' => str_replace('_', ' ', $status), 'color' => 'secondary'];

$canApproveReq = $canApprove ?? false;
$canPayReq = $canProcessPayment ?? false;
$userRole = $userRole ?? null;
$payments = $payments ?? [];
$totalPaid = $total_paid ?? 0;
$dueAmount = $due_amount ?? 0;
$payableAmount = $payable_amount ?? 0;

$isSubmitted = ($status === 'Submitted');
$isQCApproved = ($status === 'QC Approved');
$isApproved = in_array($status, ['Approved', 'Partially_Paid']);
$isRejected = ($status === 'Rejected');
$materialLogs = $material_logs ?? [];
$isFullyPaid = in_array($status, ['Fully_Paid', 'Paid']) || ($totalPaid >= $payableAmount && $payableAmount > 0);
$isPartiallyPaid = ($status === 'Partially_Paid') || ($totalPaid > 0 && $totalPaid < $payableAmount);
$isAdvanceType = ($request['payment_type'] ?? '') === 'Advance';

if ($isFullyPaid) {
    $paidStatusLabel = 'Fully Paid';
    $paidStatusClass = 'bg-success';
} elseif ($isPartiallyPaid) {
    $paidStatusLabel = 'Partially Paid';
    $paidStatusClass = 'bg-info ';
} elseif ($isAdvanceType) {
    $paidStatusLabel = 'Advance Request';
    $paidStatusClass = 'bg-primary';
} else {
    $paidStatusLabel = 'Unpaid / Pending';
    $paidStatusClass = 'bg-secondary';
}

require_once __DIR__ . '/../../../models/ApprovalRequest.php';
$ahInfo = ApprovalRequest::getAccountsHeadApprovalInfo($GLOBALS['db'] ?? null, 'vendor_payment_requests', (int)$request['id']);
$isAccountsHeadApproved = !empty($ahInfo['is_accounts_head_approved']);

$netPayable = (float) ($request['net_payable'] ?? 0);
$approvedAmtSoFar = (float) ($request['approved_amount'] ?? 0);
$unapprovedAmt = max(0, $netPayable - $approvedAmtSoFar);

$isOver1Lac = ($netPayable > 10000);
$canPayReq = $canProcessPayment && $isApproved && $dueAmount > 0 && $isAccountsHeadApproved && (!$isOver1Lac || !empty($ahInfo['is_mgmt_approved']));
$showPaymentForm = $canPayReq;
$isOpHeadUser = (in_array((int)$userRole, [20, 32], true) || strpos(strtolower($_SESSION['user']['role_name'] ?? ''), 'operation head') !== false || strpos(strtolower($_SESSION['user']['role_name'] ?? ''), 'admin & operation') !== false);
$isMgmtUser = in_array((int)$userRole, [1, 3], true);
$isOpHeadApproved = !empty($ahInfo['is_op_head_approved']);
$isAhApproved = !empty($ahInfo['is_accounts_head_approved']);

$isOpHeadAction = false;
$isMgmtLocked = false;
$mgmtLockReason = '';

if ($isOver1Lac) {
    if (!$isOpHeadApproved) {
        if ($isOpHeadUser && !$isMgmtUser) {
            $canApproveReq = ($isSubmitted || $isQCApproved || ($isApproved && $unapprovedAmt > 0.01));
            $isOpHeadAction = true;
        } else {
            $canApproveReq = false;
            $isMgmtLocked = $isMgmtUser;
            $mgmtLockReason = 'op_head';
        }
    } elseif (!$isAhApproved) {
        $canApproveReq = false;
        $isMgmtLocked = $isMgmtUser;
        $mgmtLockReason = 'accounts_head';
    } else {
        if ($isMgmtUser) {
            $canApproveReq = ($isSubmitted || $isQCApproved || ($isApproved && $unapprovedAmt > 0.01));
        } else {
            $canApproveReq = false;
        }
    }
} else {
    $canApproveReq = ($canApprove ?? false) && ($isSubmitted || $isQCApproved || ($isApproved && $unapprovedAmt > 0.01));
}
$canReject = ($canApproveReq || ($canApprove ?? false)) && in_array($status, ['Submitted', 'QC Approved', 'Approved']) && ($totalPaid <= 0);

// Payment type badge
$typeBadge = match ($request['payment_type'] ?? 'Final') {
    'Advance' => ['class' => 'bg-primary', 'label' => 'Advance'],
    'Partial' => ['class' => 'bg-warning ', 'label' => 'Partial'],
    default => ['class' => 'bg-success', 'label' => 'Final'],
};

$penaltyAmt = (float) ($request['penalty_amount'] ?? 0);
$invoiceAmt = (float) ($request['invoice_amount'] ?? 0);
$advancePaid = (float) ($advance_paid ?? 0);

$resolveAttUrl = function($att, $type = 'vendor_quotation') use ($base_url) {
    if (empty($att)) return '';
    $att = trim($att);
    if (preg_match('/^https?:\/\//i', $att)) return $att;
    if (strpos($att, 'public/') === 0 || strpos($att, '/public/') === 0) {
        return $base_url . '/' . ltrim($att, '/');
    }
    if (strpos($att, 'uploads/') === 0 || strpos($att, '/uploads/') === 0) {
        return $base_url . '/' . ltrim($att, '/');
    }
    if ($type === 'purchase_requests') {
        return $base_url . '/public/uploads/purchase_requests/' . ltrim($att, '/');
    }
    return $base_url . '/public/uploads/vendor_quotation/' . ltrim($att, '/');
};

$isImg = function($file) {
    if (empty($file)) return false;
    $ext = strtolower(pathinfo(parse_url($file, PHP_URL_PATH), PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
};
?>

<!-- Header -->
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div class="my-auto">
        <h3 class="page-title fw-semibold fs-18 mb-0 d-flex align-items-center flex-wrap gap-2">
            <span>Vendor Payment Request #<?= $request['id'] ?></span>
            <?php if (!empty($poDetails['request_id'])): ?>
                <a href="<?= $base_url ?>/con-purchase-request/<?= $poDetails['request_id'] ?>" target="_blank" class="badge bg-warning-subtle text-dark border border-warning-subtle fs-12 px-2.5 py-1 text-decoration-none shadow-sm" title="Originating Purchase Request">
                    <i class="bi bi-file-earmark-text text-warning me-1"></i>Against PR #<?= htmlspecialchars($poDetails['request_id']) ?> <i class="bi bi-box-arrow-up-right ms-1" style="font-size: 9px;"></i>
                </a>
            <?php endif; ?>
            <?php if (!empty($request['purchase_order_id'])): ?>
                <a href="<?= $base_url ?>/con-purchase-order/<?= $request['purchase_order_id'] ?>" target="_blank" class="badge bg-primary-subtle text-primary border border-primary-subtle fs-12 px-2.5 py-1 text-decoration-none shadow-sm" title="Originating Purchase Order">
                    <i class="bi bi-file-earmark-check me-1"></i>PO-<?= $request['purchase_order_id'] ?> <i class="bi bi-box-arrow-up-right ms-1" style="font-size: 9px;"></i>
                </a>
            <?php endif; ?>
        </h3>
        <nav>
            <ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-vendor-payment-requests">Payment Requests</a></li>
                <?php if (!empty($poDetails['request_id'])): ?>
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-purchase-request/<?= $poDetails['request_id'] ?>" target="_blank"><i class="bi bi-file-earmark-text text-warning me-1"></i>PR #<?= $poDetails['request_id'] ?></a></li>
                <?php endif; ?>
                <?php if (!empty($request['purchase_order_id'])): ?>
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-purchase-order/<?= $request['purchase_order_id'] ?>" target="_blank">PO-<?= $request['purchase_order_id'] ?></a></li>
                <?php endif; ?>
                <li class="breadcrumb-item active">VPR-<?= $request['id'] ?></li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <?php if (!empty($payments) || $totalPaid > 0 || in_array($request['status'] ?? '', ['Approved', 'QC Approved', 'Fully_Paid', 'Partially_Paid'])): ?>
            <?php if (!empty($payments) || $totalPaid > 0): ?>
            <a href="<?= $base_url ?>/expense-adjustments/create?parent_type=vendor&cashout_request_id=<?= $request['id'] ?>&project_id=<?= $request['project_id'] ?>" class="btn btn-warning btn-sm">
                <i class="bi bi-sliders me-1"></i> Settlement / Adjustment
            </a>
            <?php endif; ?>
            <a href="<?= $base_url ?>/print-debit-voucher?module=vendor_payment&id=<?= $request['id'] ?>" target="_blank" class="btn btn-primary btn-sm">
                <i class="bi bi-printer me-1"></i> Debit Voucher
            </a>
        <?php endif; ?>
        <span class="badge bg-<?= $statusInfo['color'] ?> fs-6 px-3 py-2"><?= $statusInfo['label'] ?></span>
        <span class="badge <?= $typeBadge['class'] ?> fs-6 px-3 py-2"><?= $typeBadge['label'] ?> Request</span>
        <span class="badge <?= $paidStatusClass ?> fs-6 px-3 py-2"><?= $paidStatusLabel ?></span>
        <?php if (!empty($materialLogs)): ?>
            <span class="badge bg-success-subtle text-success border border-success-subtle fs-6 px-3 py-2">
                <i class="bi bi-box-seam-fill me-1"></i> Material Log Received (<?= count($materialLogs) ?> Log<?= count($materialLogs) > 1 ? 's' : '' ?>)
            </span>
        <?php else: ?>
            <span class="badge bg-warning-subtle text-warning border border-warning-subtle fs-6 px-3 py-2">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> Material Log Not Received
            </span>
        <?php endif; ?>
        <a href="<?= $base_url ?>/con-vendor-payment-requests" class="btn btn-outline-secondary btn-sm ms-2">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<style>
    .bg-success-transparent {
        background-color: rgba(40, 167, 69, 0.08) !important;
    }
    .bg-danger-transparent {
        background-color: rgba(220, 53, 69, 0.08) !important;
    }
    .bg-info-transparent {
        background-color: rgba(23, 162, 184, 0.08) !important;
    }
    .bg-warning-transparent {
        background-color: rgba(255, 193, 7, 0.08) !important;
    }
    .timeline-steps .col-md-4 {
        transition: transform 0.2s ease-in-out;
    }
    .timeline-steps .col-md-4:hover {
        transform: translateY(-2px);
    }
    @keyframes blink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    .animate-blink {
        animation: blink 1.5s infinite;
    }
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.08); }
    }
    .animate-pulse {
        display: inline-block;
        animation: pulse 2s infinite ease-in-out;
    }
</style>

<?php
// Compute status timeline details
$qcApproved = ($status !== 'Draft' && $status !== 'Submitted' && ($status !== 'Rejected' || $request['qc_verified']));
$qcRejected = ($status === 'Rejected' && !$request['qc_verified']);
$qcPending = ($status === 'Submitted');

$mgmtApproved = in_array($status, ['Approved', 'Partially_Paid', 'Fully_Paid', 'Paid']);
$mgmtRejected = ($status === 'Rejected' && $request['qc_verified']);
$mgmtPending = ($status === 'QC Approved');

$payApproved = in_array($status, ['Fully_Paid', 'Paid']);
$payPartial = ($status === 'Partially_Paid');
$payPending = ($status === 'Approved');

// Determine Next Action
$nextActionText = '';
$nextActionRole = '';
$nextActionColor = 'secondary';

if ($status === 'Draft') {
    $nextActionText = 'Submit the payment request for QC review.';
    $nextActionRole = 'Creator / Purchase Executive';
    $nextActionColor = 'warning';
} elseif ($status === 'Submitted') {
    $nextActionText = 'Verify materials/delivery and approve QC status.';
    $nextActionRole = 'QC & Internal Audit Team';
    $nextActionColor = 'info';
} elseif ($status === 'QC Approved') {
    $nextActionText = 'Review the QC-verified request and approve for payment.';
    $nextActionRole = 'Management / Admin';
    $nextActionColor = 'primary';
} elseif ($status === 'Approved') {
    if ($isOver1Lac && empty($ahInfo['is_mgmt_approved'])) {
        $nextActionText = 'Step 2 completed by Accounts Head. Awaiting Management (Role 1/3) final sign-off.';
        $nextActionRole = 'Management / Authority';
        $nextActionColor = 'warning';
    } else {
        $nextActionText = 'Disburse the net payable amount of tk ' . number_format($dueAmount, 2) . '.';
        $nextActionRole = 'Accounts Team';
        $nextActionColor = 'success';
    }
} elseif ($status === 'Partially_Paid') {
    $nextActionText = 'Disburse the remaining due amount of tk ' . number_format($dueAmount, 2) . '.';
    $nextActionRole = 'Accounts Team';
    $nextActionColor = 'info';
} elseif (in_array($status, ['Fully_Paid', 'Paid'])) {
    $nextActionText = 'Payment completed successfully. No further action needed.';
    $nextActionRole = 'None';
    $nextActionColor = 'secondary';
} elseif ($status === 'Rejected') {
    $nextActionText = 'This request has been rejected. No further action possible.';
    $nextActionRole = 'None';
    $nextActionColor = 'danger';
}
?>

<!-- ACCOUNTS HEAD APPROVAL STATUS & ACTION CARD -->
<?php 
if (!empty($ahInfo['has_request'])) {
    $sourceTable = 'vendor_payment_requests';
    $sourceId = (int)$request['id'];
    include __DIR__ . '/../../layouts/accounts_head_approval_card.php';
}
?>

<!-- Approval Workflow & Next Action Status Tracker -->
<div class="card custom-card mb-4 border-<?= $nextActionColor ?> shadow-sm">
    <div class="card-header bg-<?= $nextActionColor ?>-transparent d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0 text-<?= $nextActionColor === 'secondary' ? 'dark' : $nextActionColor ?> fw-bold">
            <i class="bi bi-arrow-right-circle-fill me-2"></i>Approval Workflow & Next Action Tracker
        </h6>
        <div>
            <span class="fw-bold me-1">Next Action:</span>
            <span class="badge bg-<?= $nextActionColor ?> fs-12 px-2 py-1"><?= $nextActionRole ?></span>
        </div>
    </div>
    <div class="card-body">
        <!-- Next Action Description banner -->
        <div class="alert alert-<?= $nextActionColor ?> d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-info-circle-fill fs-5 me-2"></i>
            <div>
                <strong>Current State Description:</strong> <?= $nextActionText ?>
            </div>
        </div>

        <!-- 3-Step Approval Timeline -->
        <div class="row text-center timeline-steps">
            <!-- Step 1: QC Verification -->
            <div class="col-md-4 mb-3 mb-md-0 position-relative">
                <div class="p-3 border rounded h-100 <?= $qcApproved ? 'border-success bg-success-transparent' : ($qcRejected ? 'border-danger bg-danger-transparent' : ($qcPending ? 'border-info bg-info-transparent' : 'bg-light')) ?>">
                    <div class="fs-4 mb-2">
                        <?php if ($qcApproved): ?>
                            <i class="bi bi-shield-check text-success fs-2"></i>
                        <?php elseif ($qcRejected): ?>
                            <i class="bi bi-shield-x text-danger fs-2"></i>
                        <?php else: ?>
                            <i class="bi bi-shield text-muted fs-2 <?= $qcPending ? 'animate-pulse' : '' ?>"></i>
                        <?php endif; ?>
                    </div>
                    <h6 class="fw-bold mb-1">1. QC Audit & Verification</h6>
                    
                    <div class="mt-2">
                        <?php if ($qcApproved): ?>
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <span class="badge bg-success">QC Approved</span>
                                <?php if (($canApproveQC || in_array($userRole, [1, 3, 20, 32])) && $totalPaid <= 0 && !in_array($status, ['Rejected', 'Fully_Paid', 'Paid'])): ?>
                                    <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2 shadow-sm" onclick="triggerQcRejectModal()">
                                        <i class="bi bi-x-circle me-1"></i> Reject
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div class="small mt-2 text-muted">
                                <strong>By:</strong> <?= htmlspecialchars($request['qc_approved_by_name'] ?? 'QC Department') ?><br>
                                <?php if (!empty($request['qc_approved_at'])): ?>
                                    <strong>On:</strong> <?= date('d M Y h:i A', strtotime($request['qc_approved_at'])) ?>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($qcRejected): ?>
                            <span class="badge bg-danger">QC Rejected</span>
                            <div class="small mt-2 text-muted">
                                <strong>By:</strong> <?= htmlspecialchars($request['qc_approved_by_name'] ?? 'QC Department') ?><br>
                                <?php if (!empty($request['qc_approved_at'])): ?>
                                    <strong>On:</strong> <?= date('d M Y h:i A', strtotime($request['qc_approved_at'])) ?>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($qcPending): ?>
                            <span class="badge bg-info animate-blink">Pending Review</span>
                            <div class="small mt-2 text-muted">Awaiting QC audit & material verification.</div>
                        <?php else: ?>
                            <span class="badge bg-secondary">Awaiting Submission</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($request['qc_note'])): ?>
                        <div class="mt-2 p-2 bg-white rounded border text-start small">
                            <strong>Note:</strong> <?= htmlspecialchars($request['qc_note']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Step 2: Management Approval -->
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="p-3 border rounded h-100 <?= $mgmtApproved ? 'border-success bg-success-transparent' : ($mgmtRejected ? 'border-danger bg-danger-transparent' : ($mgmtPending ? 'border-info bg-info-transparent' : 'bg-light')) ?>">
                    <div class="fs-4 mb-2">
                        <?php if ($mgmtApproved): ?>
                            <i class="bi bi-person-check text-success fs-2"></i>
                        <?php elseif ($mgmtRejected): ?>
                            <i class="bi bi-person-x text-danger fs-2"></i>
                        <?php else: ?>
                            <i class="bi bi-person text-muted fs-2 <?= $mgmtPending ? 'animate-pulse' : '' ?>"></i>
                        <?php endif; ?>
                    </div>
                    <h6 class="fw-bold mb-1">2. Management Final Approval</h6>
                    
                    <div class="mt-2">
                        <?php if ($mgmtApproved): ?>
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <span class="badge bg-success">Approved</span>
                                <?php if ((($canApprove ?? false) || in_array($userRole, [1, 3, 20, 32])) && $totalPaid <= 0 && !in_array($status, ['Rejected', 'Fully_Paid', 'Paid'])): ?>
                                    <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2 shadow-sm" onclick="triggerMgmtRejectModal()">
                                        <i class="bi bi-x-circle me-1"></i> Reject
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div class="small mt-2 text-muted">
                                <strong>By:</strong> <?= htmlspecialchars($request['approved_by_name'] ?? 'Management') ?><br>
                                <?php if (!empty($approval_request['approved_at'])): ?>
                                    <strong>On:</strong> <?= date('d M Y h:i A', strtotime($approval_request['approved_at'])) ?>
                                <?php elseif (!empty($request['updated_at']) && $status !== 'Submitted' && $status !== 'QC Approved'): ?>
                                    <strong>On:</strong> <?= date('d M Y h:i A', strtotime($request['updated_at'])) ?>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($mgmtRejected): ?>
                            <span class="badge bg-danger">Rejected</span>
                            <div class="small mt-2 text-muted">
                                <strong>By:</strong> <?= htmlspecialchars($request['approved_by_name'] ?? 'Management') ?><br>
                                <?php if (!empty($approval_request['approved_at'])): ?>
                                    <strong>On:</strong> <?= date('d M Y h:i A', strtotime($approval_request['approved_at'])) ?>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($mgmtPending): ?>
                            <span class="badge bg-info animate-blink">Pending Review</span>
                            <div class="small mt-2 text-muted">QC verification completed. Awaiting Management final decision.</div>
                        <?php elseif ($qcRejected): ?>
                            <span class="badge bg-secondary">Bypassed</span>
                            <div class="small mt-2 text-muted">Request was rejected during QC stage.</div>
                        <?php else: ?>
                            <span class="badge bg-secondary">Awaiting QC First</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($request['management_note'])): ?>
                        <div class="mt-2 p-2 bg-white rounded border text-start small">
                            <strong>Note:</strong> <?= htmlspecialchars($request['management_note']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Step 3: Accounts Payment -->
            <div class="col-md-4">
                <div class="p-3 border rounded h-100 <?= $payApproved ? 'border-success bg-success-transparent' : ($payPartial ? 'border-info bg-info-transparent' : ($payPending ? 'border-warning bg-warning-transparent' : 'bg-light')) ?>">
                    <div class="fs-4 mb-2">
                        <?php if ($payApproved): ?>
                            <i class="bi bi-wallet2 text-success fs-2"></i>
                        <?php elseif ($payPartial): ?>
                            <i class="bi bi-wallet2 text-info fs-2"></i>
                        <?php elseif ($payPending): ?>
                            <i class="bi bi-wallet2 text-warning fs-2 <?= $payPending ? 'animate-pulse' : '' ?>"></i>
                        <?php else: ?>
                            <i class="bi bi-wallet2 text-muted fs-2"></i>
                        <?php endif; ?>
                    </div>
                    <h6 class="fw-bold mb-1">3. Accounts Disbursement</h6>
                    
                    <div class="mt-2">
                        <?php if ($payApproved): ?>
                            <span class="badge bg-success">Fully Paid</span>
                            <div class="small mt-2 text-muted">
                                <strong>Total Paid:</strong> tk <?= number_format($totalPaid, 2) ?><br>
                                <strong>Remaining Due:</strong> tk 0.00
                            </div>
                        <?php elseif ($payPartial): ?>
                            <span class="badge bg-info animate-blink">Partially Paid</span>
                            <div class="small mt-2 text-muted">
                                <strong>Total Paid:</strong> tk <?= number_format($totalPaid, 2) ?><br>
                                <strong>Remaining Due:</strong> <span class="text-danger fw-bold">tk <?= number_format($dueAmount, 2) ?></span>
                            </div>
                        <?php elseif ($payPending): ?>
                            <span class="badge bg-warning animate-blink">Pending Payment</span>
                            <div class="small mt-2 text-muted">
                                <strong>Approved Amt:</strong> tk <?= number_format($payableAmount, 2) ?><br>
                                <strong>Pending Due:</strong> <span class="text-danger fw-bold">tk <?= number_format($dueAmount, 2) ?></span>
                            </div>
                        <?php elseif ($status === 'Rejected'): ?>
                            <span class="badge bg-secondary">Cancelled</span>
                            <div class="small mt-2 text-muted">Request was rejected. No payment will be processed.</div>
                        <?php else: ?>
                            <span class="badge bg-secondary">Awaiting Approvals</span>
                            <div class="small mt-2 text-muted">Payment will be enabled once approved by Management.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ROW 1: Request Information & Additional Documents & Notes -->
<div class="row mb-3">
    <!-- Left: Request Information Card -->
    <div class="col-lg-6 d-flex mb-3 mb-lg-0">
        <div class="card custom-card mb-0 w-100">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="bi bi-info-circle me-2"></i>Request Information</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-label text-uppercase text-muted small fw-bold">Project</div>
                        <div class=""><?= htmlspecialchars($request['project_name'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label text-uppercase text-muted small fw-bold">Vendor</div>
                        <div class=""><span
                                class="badge bg-secondary"><?= htmlspecialchars($request['vendor_name'] ?? '—') ?></span>
                        </div>
                    </div>
                    <?php if (!empty($request['purchase_order_id']) && $request['purchase_order_id'] > 0): ?>
                        <div class="col-md-6 mb-2">
                            <div class="info-label text-uppercase text-muted small fw-bold">Purchase Order (PO)</div>
                            <div class="d-flex align-items-center gap-2 mt-0.5">
                                <a href="<?= $base_url ?>/con-purchase-order/<?= $request['purchase_order_id'] ?>" class="fw-bold text-primary text-decoration-none" target="_blank">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 font-monospace" style="font-size: 12px;">
                                        <i class="bi bi-file-earmark-check me-1"></i>PO-<?= $request['purchase_order_id'] ?>
                                    </span>
                                </a>
                                <a href="<?= $base_url ?>/con-purchase-order/<?= $request['purchase_order_id'] ?>" target="_blank" class="btn btn-xs btn-outline-primary py-0.5 px-2" style="font-size: 11px;" title="View Purchase Order Details">
                                    <i class="bi bi-box-arrow-up-right me-1"></i>View PO
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php 
                    $linkedPrId = $poDetails['request_id'] ?? null;
                    if (!empty($linkedPrId)): 
                    ?>
                        <div class="col-md-6 mb-2">
                            <div class="info-label text-uppercase text-muted small fw-bold">Purchase Request (Requisition)</div>
                            <div class="d-flex align-items-center gap-2 mt-0.5 flex-wrap">
                                <a href="<?= $base_url ?>/con-purchase-request/<?= $linkedPrId ?>" class="fw-bold text-dark text-decoration-none" target="_blank">
                                    <span class="badge bg-warning text-dark px-2 py-1 font-monospace" style="font-size: 12px;">
                                        <i class="bi bi-file-earmark-text me-1"></i>PR #<?= htmlspecialchars($linkedPrId) ?>
                                    </span>
                                </a>
                                <a href="<?= $base_url ?>/con-purchase-request/<?= $linkedPrId ?>" target="_blank" class="btn btn-xs btn-outline-warning text-dark py-0.5 px-2" style="font-size: 11px;" title="View Source Purchase Request">
                                    <i class="bi bi-box-arrow-up-right me-1"></i>View PR
                                </a>
                                <?php if (!empty($prAttachment)): ?>
                                    <a href="<?= $resolveAttUrl($prAttachment, 'purchase_requests') ?>" target="_blank" class="badge bg-light text-muted border text-decoration-none" title="PR Attached Document">
                                        <i class="bi bi-paperclip me-1"></i>Doc
                                    </a>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($prDetails['purpose'])): ?>
                                <small class="text-muted d-block mt-1" style="font-size: 11.5px;">
                                    <strong>Purpose:</strong> <?= htmlspecialchars($prDetails['purpose']) ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="col-md-6">
                        <div class="info-label text-uppercase text-muted small fw-bold">Invoice Number</div>
                        <div class=""><?= htmlspecialchars($request['invoice_number'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label text-uppercase text-muted small fw-bold">Delivery Verified</div>
                        <div class="">
                            <?php if ($request['delivery_verified']): ?>
                                <span class="badge bg-success">Yes</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Not Yet</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label text-uppercase text-muted small fw-bold">QC Verified</div>
                        <div class="">
                            <?php if ($request['qc_verified']): ?>
                                <span class="badge bg-success">Yes</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Not Yet</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label text-uppercase text-muted small fw-bold">Payment Paid Status</div>
                        <div class="">
                            <span class="badge <?= $paidStatusClass ?>"><?= $paidStatusLabel ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label text-uppercase text-muted small fw-bold">Material Log Received</div>
                        <div class="">
                            <?php if (!empty($materialLogs)): ?>
                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Received (<?= count($materialLogs) ?> Log<?= count($materialLogs) > 1 ? 's' : '' ?>)</span>
                            <?php else: ?>
                                <span class="badge bg-warning "><i class="bi bi-exclamation-circle me-1"></i> Not Received</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Financial Summary Row -->
                    <div class="col-md-12">
                        <div class="d-grid gap-2 mt-1" style="grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));">
                            <div class="border rounded p-3 text-center">
                                <small class="text-muted d-block mb-1">Invoice Amount</small>
                                <h5 class="text-primary mb-0 fw-bold">tk <?= number_format($invoiceAmt, 2) ?>
                                </h5>
                            </div>
                            <?php if ($penaltyAmt > 0): ?>
                                <div class="border rounded p-3 text-center">
                                    <small class="text-muted d-block mb-1">Penalty Deducted</small>
                                    <h5 class="text-danger mb-0 fw-bold">- tk <?= number_format($penaltyAmt, 2) ?>
                                    </h5>
                                </div>
                            <?php endif; ?>
                            <div class="border rounded p-3 text-center">
                                <small class="text-muted d-block mb-1">Net Payable</small>
                                <h5 class="text-success mb-0 fw-bold">tk <?= number_format($netPayable, 2) ?>
                                </h5>
                            </div>
                            <?php if (!empty($request['approved_amount'])): ?>
                                <div class="border rounded p-3 text-center border-success">
                                    <small class="text-muted d-block mb-1">Approved Amount</small>
                                    <h5 class="text-success mb-0 fw-bold">tk
                                        <?= number_format($request['approved_amount'], 2) ?>
                                    </h5>
                                </div>
                            <?php endif; ?>
                            <?php if ($isApproved || $isFullyPaid): ?>
                                <div class="border rounded p-3 text-center">
                                    <small class="text-muted d-block mb-1">Total Paid</small>
                                    <h5 class="text-success mb-0 fw-bold">tk <?= number_format($totalPaid, 2) ?></h5>
                                </div>
                                <div class="border rounded p-3 text-center">
                                    <small class="text-muted d-block mb-1">Due Amount</small>
                                    <?php if ($dueAmount > 0): ?>
                                        <h5 class="text-danger mb-0 fw-bold">tk <?= number_format($dueAmount, 2) ?></h5>
                                    <?php else: ?>
                                        <h5 class="text-success mb-0 fw-bold">tk 0.00</h5>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mt-2 py-2 px-3 bg-light rounded text-muted small d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <span><strong class="text-dark">Net Payable in Words:</strong> <?= class_exists('FormatHelper') ? FormatHelper::amountInWords($netPayable) : (function_exists('amountInWords') ? amountInWords($netPayable) : '') ?></span>
                            <?php if ($totalPaid > 0): ?>
                                <span><strong class="text-dark">Total Paid in Words:</strong> <span class="text-success fw-semibold"><?= class_exists('FormatHelper') ? FormatHelper::amountInWords($totalPaid) : (function_exists('amountInWords') ? amountInWords($totalPaid) : '') ?></span></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="info-label text-uppercase text-muted small fw-bold">Created By</div>
                        <span class="text-muted">
                            <?= htmlspecialchars($request['created_by_name'] ?? '—') ?>
                        </span>
                        <div class="">
                            <?php if (!empty($request['created_at'])): ?>
                                <small
                                    class="text-muted">(<?= date('d M Y h:i A', strtotime($request['created_at'])) ?>)</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!empty($request['approved_by_name'])): ?>
                        <div class="col-md-6">
                            <div class="info-label text-uppercase text-muted small fw-bold">Approved By</div>
                            <div class=""><strong><?= htmlspecialchars($request['approved_by_name']) ?></strong></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($request['payment_attachment'])): ?>
                        <div class="col-md-12">
                            <div class="info-label text-uppercase text-muted small fw-bold mb-2">
                                <i class="bi bi-paperclip me-1"></i>Payment Attachment
                            </div>
                            <?php
                            $attPath = $request['payment_attachment'];
                            $attExt = strtolower(pathinfo($attPath, PATHINFO_EXTENSION));
                            $attUrl = $base_url . '/' . $attPath;
                            $imgExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                            ?>
                            <?php if (in_array($attExt, $imgExts)): ?>
                                <a href="<?= $attUrl ?>" target="_blank" title="View Attachment">
                                    <img src="<?= $attUrl ?>" alt="Payment Attachment"
                                        style="max-height: 120px; max-width: 100%; border-radius: 6px; border: 1px solid #dee2e6; object-fit: cover;">
                                </a>
                                <div class="mt-1">
                                    <a href="<?= $attUrl ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-box-arrow-up-right me-1"></i>Open Full View
                                    </a>
                                </div>
                            <?php else: ?>
                                <a href="<?= $attUrl ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                    <?php if ($attExt === 'pdf'): ?>
                                        <i class="bi bi-file-earmark-pdf me-1 text-danger"></i>
                                    <?php elseif (in_array($attExt, ['doc', 'docx'])): ?>
                                        <i class="bi bi-file-earmark-word me-1 text-primary"></i>
                                    <?php else: ?>
                                        <i class="bi bi-file-earmark me-1"></i>
                                    <?php endif; ?>
                                    View Attachment (<?= strtoupper($attExt) ?>)
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Additional Documents & Notes -->
    <div class="col-lg-6 d-flex">
        <div class="card custom-card mb-0 w-100">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="bi bi-folder me-2"></i>Additional Documents & Notes</h6>
            </div>
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="row">
                    <div class="col-md-7">
                        <?php if (empty($documents)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-chat-square-text fs-2 mb-2 d-block text-secondary"></i>
                                <p class="mb-0">No additional notes or documents have been added yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="timeline-container ps-3 mt-2" style="border-left: 2px solid #e9ecef; max-height: 250px; overflow-y: auto;">
                                <?php foreach ($documents as $doc): ?>
                                    <div class="mb-3 position-relative ps-4">
                                        <div class="position-absolute"
                                            style="left: -24px; top: 0; background: #fff; border-radius: 50%; padding: 4px;">
                                            <div style="width: 12px; height: 12px; border-radius: 50%; background: #0d6efd;">
                                            </div>
                                        </div>
                                        <div class="p-3 rounded border shadow-sm">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <div>
                                                    <strong
                                                        class="text-primary"><?= htmlspecialchars($doc['uploaded_by_name']) ?></strong>
                                                    <span class="text-muted small ms-2"><i
                                                            class="bi bi-clock me-1"></i><?= date('d M Y, h:i A', strtotime($doc['created_at'])) ?></span>
                                                </div>
                                            </div>
                                            <?php if (!empty($doc['note'])): ?>
                                                <p class="mb-2 " style="font-size: 14px;">
                                                    <?= nl2br(htmlspecialchars($doc['note'])) ?>
                                                </p>
                                            <?php endif; ?>

                                            <?php if (!empty($doc['document_path'])): ?>
                                                <a href="<?= $base_url ?>/<?= htmlspecialchars($doc['document_path']) ?>"
                                                    target="_blank" class="btn btn-sm btn-outline-primary mt-1"
                                                    style="font-size: 12px;">
                                                    <i class="bi bi-paperclip me-1"></i> View Attachment
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (!in_array($userRole, [24])): // Hide from QC ?>
                        <div class="col-md-5 mt-3 mt-md-0">
                            <div class="card border-0 shadow-sm" style="background-color: #f8f9fa;">
                                <div class="card-body">
                                    <h6 class="mb-3 fw-bold text-primary"><i class="bi bi-pencil-square me-2"></i>Post a New
                                        Note</h6>
                                    <form action="<?= $base_url ?>/con-vendor-payment-document/<?= $request['id'] ?>"
                                        method="POST" enctype="multipart/form-data">
                                        <div class="mb-3">
                                            <textarea name="note" class="form-control border-primary-subtle" rows="3"
                                                placeholder="Write your note or comment here..."
                                                style="font-size: 14px;"></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold text-muted">Attach File
                                                (Optional)</label>
                                            <input type="file" name="document"
                                                class="form-control form-control-sm border-primary-subtle">
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100 fw-bold">
                                            <i class="bi bi-send me-1"></i> Submit Note
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Selected Vendor & CST (Comparative Statement) Details Section -->
<?php
$selVenId = $selectedVendor['id'] ?? ($request['vendor_id'] ?? null);
$prAttUrl = !empty($prAttachment) ? $resolveAttUrl($prAttachment, 'purchase_requests') : null;
?>

<div class="card custom-card mb-4 border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="avatar avatar-sm rounded bg-primary-subtle text-primary p-2">
                <i class="bi bi-briefcase-fill fs-5"></i>
            </span>
            <div>
                <h6 class="card-title mb-0 fw-bold text-dark">
                    Selected Vendor & CST (Comparative Statement) Details
                </h6>
                <div class="text-muted small d-flex align-items-center flex-wrap gap-2 mt-1">
                    <?php if (!empty($request['purchase_order_id'])): ?>
                        <span>PO Reference: 
                            <a href="<?= $base_url ?>/con-purchase-order/<?= $request['purchase_order_id'] ?>" target="_blank" class="badge bg-primary-subtle text-primary border border-primary-subtle text-decoration-none px-2 py-0.5" title="View Originating Purchase Order">
                                <i class="bi bi-file-earmark-check me-1"></i>PO-<?= htmlspecialchars($request['purchase_order_id']) ?> <i class="bi bi-box-arrow-up-right ms-1" style="font-size: 9px;"></i>
                            </a>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($poDetails['request_id'])): ?>
                        <span>&nbsp;|&nbsp; Source PR Requisition: 
                            <a href="<?= $base_url ?>/con-purchase-request/<?= $poDetails['request_id'] ?>" target="_blank" class="badge bg-warning-subtle text-dark border border-warning-subtle text-decoration-none px-2 py-0.5" title="View Source Purchase Request">
                                <i class="bi bi-file-earmark-text text-warning me-1"></i>PR #<?= htmlspecialchars($poDetails['request_id']) ?> <i class="bi bi-box-arrow-up-right ms-1" style="font-size: 9px;"></i>
                            </a>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($cstInfo['prepared_by_name'])): ?>
                        <span class="text-muted">&nbsp;|&nbsp; CST Prepared By: <strong class="text-dark"><?= htmlspecialchars($cstInfo['prepared_by_name']) ?></strong></span>
                        <?php if (!empty($cstInfo['created_at'])): ?>
                            <span class="text-muted">(<?= date('d M Y, h:i A', strtotime($cstInfo['created_at'])) ?>)</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <?php if (!empty($cstMatrix) && !empty($cstMatrix['vendors'])): ?>
                <button type="button" class="btn btn-sm btn-primary fw-bold px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#vprCstMatrixModal">
                    <i class="bi bi-table me-1"></i> View Full CST Matrix
                </button>
            <?php endif; ?>
            <?php if (!empty($prAttUrl)): ?>
                <a href="<?= htmlspecialchars($prAttUrl) ?>" target="_blank" class="btn btn-sm btn-outline-secondary fw-semibold">
                    <i class="bi bi-paperclip me-1"></i> PR Attachment
                </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body p-4">
        <!-- 1. Selected Vendor Full Details Box -->
        <div class="p-3 mb-4 rounded-3 border" style="background: linear-gradient(135deg, rgba(13,110,253,0.03) 0%, rgba(25,135,84,0.04) 100%); border-color: rgba(13,110,253,0.2) !important;">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success px-2 py-1 fs-7 shadow-sm">
                        <i class="bi bi-check-circle-fill me-1"></i> Selected Vendor in CST / PO
                    </span>
                    <span class="fw-bold fs-6 text-dark">
                        <?= !empty($selectedVendor['name']) ? htmlspecialchars($selectedVendor['name']) : '<span class="text-muted fst-italic">null</span>' ?>
                    </span>
                    <?php if (!empty($selectedVendor['company_name'])): ?>
                        <span class="text-muted small">(<?= htmlspecialchars($selectedVendor['company_name']) ?>)</span>
                    <?php endif; ?>
                </div>
                <div class="badge bg-light text-dark border px-2 py-1 small">
                    <i class="bi bi-tag-fill me-1 text-primary"></i> Vendor ID: <strong><?= isset($selectedVendor['id']) && $selectedVendor['id'] !== '' ? '#' . htmlspecialchars($selectedVendor['id']) : 'null' ?></strong>
                </div>
            </div>

            <!-- Exact required fields: [id], [name], [phone], [email], [address], [company_name] -->
            <div class="row ">
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label text-uppercase text-muted fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Vendor ID [id]</label>
                    <div class="fw-bold text-dark fs-6 font-monospace">
                        <?= isset($selectedVendor['id']) && $selectedVendor['id'] !== '' ? '#' . htmlspecialchars($selectedVendor['id']) : '<span class="badge bg-secondary-subtle text-muted">null</span>' ?>
                    </div>
                </div>
                <div class="col-l col-md-4 col-sm-6">
                    <label class="form-label text-uppercase text-muted fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Name [name]</label>
                    <div class="fw-semibold text-dark">
                        <?= !empty($selectedVendor['name']) ? htmlspecialchars($selectedVendor['name']) : '<span class="text-muted fst-italic">null</span>' ?>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label text-uppercase text-muted fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Phone [phone]</label>
                    <div class="fw-semibold text-dark">
                        <?php if (!empty($selectedVendor['phone'])): ?>
                            <a href="tel:<?= htmlspecialchars($selectedVendor['phone']) ?>" class="text-decoration-none text-dark">
                                <i class="bi bi-telephone-fill text-success me-1 small"></i><?= htmlspecialchars($selectedVendor['phone']) ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted fst-italic">null</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label text-uppercase text-muted fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Email [email]</label>
                    <div class="fw-semibold text-dark text-truncate" title="<?= htmlspecialchars($selectedVendor['email'] ?? '') ?>">
                        <?php if (!empty($selectedVendor['email'])): ?>
                            <a href="mailto:<?= htmlspecialchars($selectedVendor['email']) ?>" class="text-decoration-none text-primary">
                                <i class="bi bi-envelope-fill me-1 small"></i><?= htmlspecialchars($selectedVendor['email']) ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted fst-italic">null</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-l col-md-4 col-sm-6">
                    <label class="form-label text-uppercase text-muted fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Company [company_name]</label>
                    <div class="fw-semibold text-dark">
                        <?= !empty($selectedVendor['company_name']) ? htmlspecialchars($selectedVendor['company_name']) : '<span class="text-muted fst-italic">null</span>' ?>
                    </div>
                </div>
                <div class="col-12 mt-2 pt-2 border-top">
                    <label class="form-label text-uppercase text-muted fw-bold mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Address [address]</label>
                    <div class="text-dark">
                        <?php if (!empty($selectedVendor['address'])): ?>
                            <i class="bi bi-geo-alt-fill text-danger me-1 small"></i><?= nl2br(htmlspecialchars($selectedVendor['address'])) ?>
                        <?php else: ?>
                            <span class="text-muted fst-italic">null</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. CST Participating Vendors Table -->
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-bold mb-0 text-dark" style="font-size: 13.5px;">
                <i class="bi bi-diagram-3-fill me-1 text-primary"></i> Participating Vendors in Comparative Statement (CST)
            </h6>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                <?= count($cstQuotations) ?> Vendor Quote(s) Found
            </span>
        </div>

        <?php if (!empty($cstQuotations)): ?>
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-hover align-middle mb-0" style="font-size: 12.5px;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 50px;">#</th>
                            <th>Vendor Details</th>
                            <th>Company Name</th>
                            <th>Contact Info</th>
                            <th class="text-end">Quoted Total</th>
                            <th class="text-center">Quotation Attachment</th>
                            <th class="text-center">CST Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cstQuotations as $idx => $q): ?>
                            <?php
                            $isThisSelected = ($selVenId && (int)$q['vendor_id'] === (int)$selVenId);
                            $qAttUrl = $resolveAttUrl($q['attachment'] ?? null);
                            $qIsImg = $isImg($q['attachment'] ?? null);
                            ?>
                            <tr class="<?= $isThisSelected ? 'table-success bg-opacity-25' : '' ?>">
                                <td class="ps-3 fw-bold"><?= $idx + 1 ?></td>
                                <td>
                                    <div class="fw-bold text-dark">
                                        <?= !empty($q['vendor_name']) ? htmlspecialchars($q['vendor_name']) : 'null' ?>
                                    </div>
                                    <div class="text-muted small font-monospace">
                                        Vendor ID: #<?= !empty($q['vendor_id']) ? htmlspecialchars($q['vendor_id']) : 'null' ?>
                                    </div>
                                </td>
                                <td>
                                    <?= !empty($q['vendor_company_name']) ? htmlspecialchars($q['vendor_company_name']) : '<span class="text-muted fst-italic">null</span>' ?>
                                </td>
                                <td>
                                    <div>
                                        <i class="bi bi-telephone text-muted me-1 small"></i>
                                        <?= !empty($q['vendor_phone']) ? htmlspecialchars($q['vendor_phone']) : '<span class="text-muted fst-italic">null</span>' ?>
                                    </div>
                                    <div class="small text-truncate" style="max-width: 200px;">
                                        <i class="bi bi-envelope text-muted me-1 small"></i>
                                        <?= !empty($q['vendor_email']) ? htmlspecialchars($q['vendor_email']) : '<span class="text-muted fst-italic">null</span>' ?>
                                    </div>
                                </td>
                                <td class="text-end fw-bold">
                                    <?php if (isset($q['total_quoted_amount'])): ?>
                                        tk <?= number_format((float)$q['total_quoted_amount'], 2) ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex flex-column gap-1 align-items-center justify-content-center">
                                        <?php if (!empty($qAttUrl)): ?>
                                            <a href="<?= htmlspecialchars($qAttUrl) ?>" target="_blank" class="btn btn-xs btn-outline-primary px-2 py-1" style="font-size: 11px;">
                                                <?php if ($qIsImg): ?>
                                                    <i class="bi bi-file-earmark-image me-1 text-info"></i> View Image
                                                <?php else: ?>
                                                    <i class="bi bi-file-earmark-pdf me-1 text-danger"></i> View Document
                                                <?php endif; ?>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($q['drive_link'])): ?>
                                            <a href="<?= htmlspecialchars($q['drive_link']) ?>" target="_blank" class="btn btn-xs btn-outline-info px-2 py-1" style="font-size: 11px;" title="Open Google Drive / External Link">
                                                <i class="bi bi-link-45deg me-1"></i> Drive Link
                                            </a>
                                        <?php endif; ?>
                                        <?php if (empty($qAttUrl) && empty($q['drive_link'])): ?>
                                            <span class="badge bg-light text-muted border py-1 px-2" style="font-size: 10px;">
                                                <i class="bi bi-file-earmark-x me-1"></i> No file
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php if ($isThisSelected): ?>
                                        <span class="badge bg-success text-white px-2 py-1 shadow-sm">
                                            <i class="bi bi-check-circle-fill me-1"></i> Selected Vendor
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-secondary border px-2 py-1">
                                            Alternative
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-light border text-center text-muted py-3 mb-4">
                <i class="bi bi-info-circle me-1"></i> No CST quotation records found for this purchase request.
            </div>
        <?php endif; ?>

        <!-- 3. Price Quotation & PR Attachments Gallery -->
        <?php
        $hasAttachments = false;
        if (!empty($prAttUrl)) $hasAttachments = true;
        foreach ($cstQuotations as $q) {
            if (!empty($q['attachment']) || !empty($q['drive_link'])) {
                $hasAttachments = true;
                break;
            }
        }
        ?>

        <?php if ($hasAttachments): ?>
            <div class="pt-2 border-top">
                <h6 class="fw-bold mb-3 text-dark d-flex align-items-center" style="font-size: 13px;">
                    <i class="bi bi-images me-2 text-primary"></i> Price Quotations, Drive Links & PR Attachments
                </h6>
                <div class="row ">
                    <?php if (!empty($prAttUrl)): ?>
                        <?php $prIsImg = $isImg($prAttachment); ?>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card h-100 border shadow-none bg-light-subtle rounded-3 p-2 text-center position-relative">
                                <span class="badge bg-secondary position-absolute top-0 start-0 m-2" style="font-size: 10px;">PR Attachment</span>
                                <div class="py-3">
                                    <?php if ($prIsImg): ?>
                                        <a href="<?= htmlspecialchars($prAttUrl) ?>" target="_blank">
                                            <img src="<?= htmlspecialchars($prAttUrl) ?>" alt="PR Attachment" class="img-fluid rounded border" style="max-height: 100px; object-fit: cover;">
                                        </a>
                                    <?php else: ?>
                                        <i class="bi bi-file-earmark-pdf-fill text-danger" style="font-size: 3rem;"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="small fw-semibold text-truncate mb-2" title="Purchase Request Attachment">
                                    PR #<?= htmlspecialchars($poDetails['request_id'] ?? '') ?> Document
                                </div>
                                <a href="<?= htmlspecialchars($prAttUrl) ?>" target="_blank" class="btn btn-sm btn-outline-secondary w-100" style="font-size: 11px;">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> Open PR File
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($cstQuotations as $q): ?>
                        <?php if (!empty($q['attachment']) || !empty($q['drive_link'])): ?>
                            <?php
                            $qUrl = !empty($q['attachment']) ? $resolveAttUrl($q['attachment']) : null;
                            $imgCheck = !empty($q['attachment']) ? $isImg($q['attachment']) : false;
                            $isAwarded = ($selVenId && (int)$q['vendor_id'] === (int)$selVenId);
                            ?>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="card h-100 border <?= $isAwarded ? 'border-success' : '' ?> shadow-none bg-light-subtle rounded-3 p-2 text-center position-relative">
                                    <?php if ($isAwarded): ?>
                                        <span class="badge bg-success position-absolute top-0 start-0 m-2" style="font-size: 10px;">
                                            <i class="bi bi-check-circle-fill me-1"></i> Selected
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary position-absolute top-0 start-0 m-2" style="font-size: 10px;">Quote</span>
                                    <?php endif; ?>
                                    <div class="py-3">
                                        <?php if ($imgCheck): ?>
                                            <a href="<?= htmlspecialchars($qUrl) ?>" target="_blank">
                                                <img src="<?= htmlspecialchars($qUrl) ?>" alt="Quotation Attachment" class="img-fluid rounded border" style="max-height: 100px; object-fit: cover;">
                                            </a>
                                        <?php elseif (!empty($qUrl)): ?>
                                            <i class="bi bi-file-earmark-text-fill text-primary" style="font-size: 3rem;"></i>
                                        <?php else: ?>
                                            <i class="bi bi-cloud-arrow-up-fill text-info" style="font-size: 3rem;"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="small fw-bold text-truncate" title="<?= htmlspecialchars($q['vendor_name'] ?? '') ?>">
                                        <?= htmlspecialchars($q['vendor_name'] ?? 'Vendor Quote') ?>
                                    </div>
                                    <div class="text-muted small mb-2 font-monospace" style="font-size: 10.5px;">
                                        <?= !empty($q['total_quoted_amount']) ? 'tk ' . number_format((float)$q['total_quoted_amount'], 2) : '' ?>
                                    </div>
                                    <?php if (!empty($qUrl)): ?>
                                        <a href="<?= htmlspecialchars($qUrl) ?>" target="_blank" class="btn btn-sm btn-outline-primary w-100 mb-1" style="font-size: 11px;">
                                            <i class="bi bi-box-arrow-up-right me-1"></i> View Quotation File
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($q['drive_link'])): ?>
                                        <a href="<?= htmlspecialchars($q['drive_link']) ?>" target="_blank" class="btn btn-sm btn-outline-info w-100" style="font-size: 11px;">
                                            <i class="bi bi-link-45deg me-1"></i> Open Drive Link
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="pt-3 border-top mt-2">
                <div class="alert alert-light border d-flex align-items-center gap-3 py-3 px-3 mb-0" style="border-radius:10px; background:#f8fafc">
                    <div class="text-secondary fs-4"><i class="bi bi-file-earmark-x"></i></div>
                    <div class="small text-muted">
                        <strong class="text-dark">No Quotation or PR Attachments Uploaded:</strong><br>
                        No price quotation documents (PDF / Image) or PR attachments were uploaded during CST quotation entry for this request.
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Material Receiving Logs (GRN) Section -->
<div class="card custom-card mb-4 border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0 fw-bold ">
            <i class="bi bi-boxes me-2 text-primary"></i>Material Receiving Logs (GRN)
        </h6>
        <?php if (!empty($materialLogs)): ?>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1">
                <i class="bi bi-check-circle-fill me-1"></i> <?= count($materialLogs) ?> Log(s) Received
            </span>
        <?php else: ?>
            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-1">
                <i class="bi bi-exclamation-circle-fill me-1"></i> No Material Logs Received
            </span>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($materialLogs)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small">
                        <tr>
                            <th class="ps-3">GRN #</th>
                            <th>Receiving Date</th>
                            <th>Received Status</th>
                            <th>QC / Overall Status</th>
                            <th>Received By</th>
                            <th>Warehouse</th>
                            <th class="pe-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materialLogs as $ml): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-primary">GRN-<?= $ml['id'] ?></td>
                                <td><?= date('d M Y', strtotime($ml['receiving_date'])) ?></td>
                                <td>
                                    <span class="badge bg-<?= $ml['received_status'] === 'Full' ? 'success' : ($ml['received_status'] === 'Partial' ? 'warning ' : 'danger') ?>">
                                        <?= htmlspecialchars($ml['received_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= in_array($ml['overall_status'], ['Accepted', 'QC Approved']) ? 'success' : 'info' ?>">
                                        <?= htmlspecialchars($ml['overall_status']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($ml['received_by_name'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($ml['warehouse_name'] ?? '—') ?></td>
                                <td class="pe-3 text-end">
                                    <a href="<?= $base_url ?>/con-material-receiving-show/<?= $ml['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i> View GRN
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-4 text-center text-muted">
                <i class="bi bi-inbox fs-2 text-warning d-block mb-2"></i>
                <p class="mb-0 small fw-semibold">No material receiving log (GRN) has been recorded against this Purchase Order yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ROW 2: Related PO Stock at Project & Payment History -->
<?php if (!empty($poStocks) || !empty($payments)): ?>
<div class="row mb-3">
    <?php if (!empty($poStocks) && !empty($payments)): ?>
        <!-- Left: Related PO Stock -->
        <div class="col-lg-6 d-flex mb-3 mb-lg-0">
            <div class="card custom-card mb-0 w-100 border-info">
                <div class="card-header bg-info bg-opacity-10">
                    <h6 class="card-title mb-0 text-info"><i class="bi bi-box-seam me-2"></i>Related PO Stock at Project</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Material</th>
                                    <th class="text-end">Ordered (PO)</th>
                                    <th class="text-end">Current Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($poStocks as $stock): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($stock['material_name']) ?></td>
                                        <td class="text-end"><?= number_format($stock['po_qty'], 2) ?>
                                            <?= htmlspecialchars($stock['unit']) ?>
                                        </td>
                                        <td
                                            class="text-end fw-bold <?= $stock['current_stock'] > 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= number_format($stock['current_stock'], 2) ?>
                                            <?= htmlspecialchars($stock['unit']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted d-block mt-2"><i class="bi bi-info-circle me-1"></i>QC & Audit must verify the stock levels.</small>
                </div>
            </div>
        </div>

        <!-- Right: Payment History -->
        <div class="col-lg-6 d-flex">
            <div class="card custom-card mb-0 w-100">
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
                                    <th>Amount (tk)</th>
                                    <th>Type</th>
                                    <th>Method</th>
                                    <th>Processed By</th>
                                    <th>Proof</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $idx => $pay): ?>
                                    <tr>
                                        <td><?= $idx + 1 ?></td>
                                        <td><?php
                                        $displayDate = $pay['transaction_date_str'] ?? $pay['transaction_date'] ?? null;
                                        echo $displayDate ? date('d M Y', strtotime($displayDate)) : '—';
                                        ?></td>
                                        <td class="text-end fw-semibold">tk <?= number_format($pay['amount_paid'], 2) ?></td>
                                        <td><span
                                                class="badge bg-<?= $pay['payment_type'] === 'Full' ? 'success' : 'info' ?>"><?= $pay['payment_type'] ?></span>
                                        </td>
                                        <td><?= str_replace('BankTransfer', 'Bank Transfer', $pay['payment_method']) ?></td>
                                        <td><?= htmlspecialchars($pay['processed_by_name'] ?? '—') ?></td>
                                        <td>
                                            <?php
                                            $proofs = json_decode($pay['disbursement_proof'] ?? '[]', true);
                                            if (!empty($proofs)):
                                                foreach ($proofs as $pi => $proof): ?>
                                                    <a href="<?= $base_url ?>/<?= $proof ?>" target="_blank"
                                                        class="btn btn-sm btn-outline-info">
                                                        <i class="bi bi-file-earmark"></i> Doc <?= $pi + 1 ?>
                                                    </a>
                                                <?php endforeach;
                                            else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php if (!empty($poStocks)): ?>
            <div class="col-12">
                <div class="card custom-card mb-0 border-info">
                    <div class="card-header bg-info bg-opacity-10">
                        <h6 class="card-title mb-0 text-info"><i class="bi bi-box-seam me-2"></i>Related PO Stock at Project</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Material</th>
                                        <th class="text-end">Ordered (PO)</th>
                                        <th class="text-end">Current Stock</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($poStocks as $stock): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($stock['material_name']) ?></td>
                                            <td class="text-end"><?= number_format($stock['po_qty'], 2) ?>
                                                <?= htmlspecialchars($stock['unit']) ?>
                                            </td>
                                            <td
                                                class="text-end fw-bold <?= $stock['current_stock'] > 0 ? 'text-success' : 'text-danger' ?>">
                                                <?= number_format($stock['current_stock'], 2) ?>
                                                <?= htmlspecialchars($stock['unit']) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php if (!empty($payments)): ?>
            <div class="col-12">
                <div class="card custom-card mb-0">
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
                                        <th>Amount (tk)</th>
                                        <th>Type</th>
                                        <th>Method</th>
                                        <th>Processed By</th>
                                        <th>Proof</th>
                                        <th class="text-center">Receipt / Voucher</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $idx => $pay): ?>
                                        <tr>
                                            <td><?= $idx + 1 ?></td>
                                            <td><?php
                                            $displayDate = $pay['transaction_date_str'] ?? $pay['transaction_date'] ?? null;
                                            echo $displayDate ? date('d M Y', strtotime($displayDate)) : '—';
                                            ?></td>
                                            <td class="text-end fw-semibold">tk <?= number_format($pay['amount_paid'], 2) ?></td>
                                            <td><span
                                                    class="badge bg-<?= $pay['payment_type'] === 'Full' ? 'success' : 'info' ?>"><?= $pay['payment_type'] ?></span>
                                            </td>
                                            <td><?= str_replace('BankTransfer', 'Bank Transfer', $pay['payment_method']) ?></td>
                                            <td><?= htmlspecialchars($pay['processed_by_name'] ?? '—') ?></td>
                                            <td>
                                                <?php
                                                $proofs = json_decode($pay['disbursement_proof'] ?? '[]', true);
                                                if (!empty($proofs)):
                                                    foreach ($proofs as $pi => $proof): ?>
                                                        <a href="<?= $base_url ?>/<?= $proof ?>" target="_blank"
                                                            class="btn btn-sm btn-outline-info">
                                                            <i class="bi bi-file-earmark"></i> Doc <?= $pi + 1 ?>
                                                        </a>
                                                    <?php endforeach;
                                                else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center text-nowrap">
                                                <a href="<?= $base_url ?>/con-transaction/<?= $pay['id'] ?>" class="btn btn-xs btn-outline-success" target="_blank" title="View Transaction Receipt">
                                                    <i class="bi bi-receipt me-1"></i>Receipt
                                                </a>
                                                <a href="<?= $base_url ?>/print-debit-voucher?module=vendor_payment&id=<?= $request['id'] ?>" class="btn btn-xs btn-outline-primary" target="_blank" title="Print Debit Voucher">
                                                    <i class="bi bi-printer me-1"></i>Voucher
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ROW 2.5: Linked Penalties (Full width if present) -->
<?php if (!empty($penalties)): ?>
<div class="row mb-3">
    <div class="col-12">
        <div class="card custom-card mb-0">
            <div class="card-header">
                <h6 class="card-title mb-0 text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Deducted Penalties</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Penalty ID</th>
                            <th>Date</th>
                            <th>Reason</th>
                            <th class="text-end">Amount (tk)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($penalties as $p): ?>
                            <tr>
                                <td>PEN-<?= $p['id'] ?></td>
                                <td><?= !empty($p['penalty_date']) ? date('d M Y', strtotime($p['penalty_date'])) : '—' ?></td>
                                <td><?= htmlspecialchars($p['reason'] ?? '') ?></td>
                                <td class="text-end text-danger"><?= number_format($p['penalty_amount'] ?? 0, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ROW 3: Expense Settlement & Status Info / Actions -->
<div class="row mb-3">
    <!-- Left: Expense Settlement & Adjustments -->
    <div class="col-lg-6 d-flex mb-3 mb-lg-0">
        <div class="card custom-card mb-0 w-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0"><i class="bi bi-patch-check me-2"></i>Expense Settlement & Adjustments</h6>
                <?php if ($totalPaid > 0): ?>
                    <a href="<?= $base_url ?>/expense-adjustments/create?parent_type=vendor&cashout_request_id=<?= $request['id'] ?>&project_id=<?= $request['project_id'] ?>"
                        class="bd-badge bg-primary fw-bold">
                        <i class="bi bi-file-earmark-plus me-1"></i> Submit Settlement
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($adjustments)): ?>
                    <div class="text-center py-3 text-muted">
                        <i class="bi bi-clipboard-x fs-2 d-block mb-1 text-secondary"></i>
                        No settlements or adjustments submitted yet for this request.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th class="text-end">Amount (tk)</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($adjustments as $adj):
                                    $typeBadge = ($adj['adjustment_type'] === 'return') ? 'info ' : 'warning ';
                                    $typeLabel = ($adj['adjustment_type'] === 'return') ? 'Return' : 'Extra';

                                    $statusClass = match ($adj['status']) {
                                        'pending' => 'warning',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'partially_paid' => 'info',
                                        'fully_paid' => 'secondary',
                                        default => 'dark',
                                    };
                                    $statusLabel = str_replace('_', ' ', ucfirst($adj['status']));
                                    ?>
                                    <tr>
                                        <td>ADJ-<?= $adj['id'] ?></td>
                                        <td><?= date('d M Y', strtotime($adj['created_at'])) ?></td>
                                        <td><span class="badge bg-<?= $typeBadge ?>"><?= $typeLabel ?></span></td>
                                        <td class="text-end fw-semibold">tk <?= number_format($adj['amount'], 2) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= $statusClass ?>"><?= $statusLabel ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= $base_url ?>/expense-adjustments/show?id=<?= $adj['id'] ?>"
                                                class="btn btn-xs btn-outline-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right: Status Info, Actions, Alerts, and Payment processing -->
    <div class="col-lg-6 d-flex flex-column gap-3">
        <!-- Status Info Card -->
        <div class="card custom-card mb-0">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="bi bi-info-circle me-2"></i>Status Info</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <small class="text-muted d-block">Current Status</small>
                        <span class="badge bg-<?= $statusInfo['color'] ?> fs-6"><?= $statusInfo['label'] ?></span>
                    </div>
                    <div class="col-md-6 mb-2">
                        <small class="text-muted d-block">Created At</small>
                        <strong><?= date('d M Y h:i A', strtotime($request['created_at'])) ?></strong>
                    </div>
                    <?php if (!empty($request['qc_approved_by_id'])): ?>
                        <div class="col-md-12 mb-2">
                            <small class="text-muted d-block">QC Approved By</small>
                            <strong><?= htmlspecialchars($request['qc_approved_by_name'] ?? 'QC User') ?></strong>
                            <small class="text-muted">(<?= date('d M Y h:i A', strtotime($request['qc_approved_at'])) ?>)</small>
                            <?php if (isset($request['qc_approved_amount']) && (float)$request['qc_approved_amount'] > 0): ?>
                                <div class="mt-1">
                                    <span class="badge bg-info text-white">QC Approved Amount: tk <?= number_format((float)$request['qc_approved_amount'], 2) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($request['qc_note'])): ?>
                                <p class="small text-muted mt-1 fst-italic mb-0">"<?= nl2br(htmlspecialchars($request['qc_note'])) ?>"</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($request['approved_by_name'])): ?>
                        <div class="col-md-12 mb-2">
                            <small class="text-muted d-block">Approved By</small>
                            <strong><?= htmlspecialchars($request['approved_by_name']) ?></strong>
                            <?php if (!empty($request['management_note'])): ?>
                                <p class="small text-muted mt-1 fst-italic mb-0">"<?= nl2br(htmlspecialchars($request['management_note'])) ?>"</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Approval Actions (QC & Internal Audit) -->
        <?php if (($canApproveQC ?? false) && $isSubmitted): ?>
            <div class="card custom-card border-info mb-0 shadow-sm">
                <div class="card-header bg-info bg-opacity-10 d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0 text-info fw-bold">
                        <i class="bi bi-shield-check me-2"></i>QC Audit & Verification Action
                    </h6>
                    <span class="badge bg-info text-white">Step 1 of 3</span>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        Review delivery details, materials received, invoice amount and submit your QC verification to forward to Management.
                    </p>
                    <form action="<?= $base_url ?>/con-vendor-payment-qc-approve/<?= $request['id'] ?>" method="POST" id="qcApproveVprForm" class="mb-3">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">QC Approved Amount (tk) <span class="text-danger">*</span></label>
                            <input type="number" name="qc_approved_amount" id="qcApprovedAmtInput" class="form-control"
                                value="<?= number_format($netPayable, 2, '.', '') ?>" step="0.01" min="0.01"
                                max="<?= number_format($netPayable, 2, '.', '') ?>" required>
                            <small class="text-muted">Net payable: tk <?= number_format($netPayable, 2) ?> (You can adjust this amount)</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">QC & Audit Observation Note <span class="text-muted">(Optional)</span></label>
                            <textarea name="qc_note" class="form-control" rows="2" placeholder="Write observation, material verification notes or comments..."></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" id="btnQcApprove" class="btn btn-info text-white flex-grow-1 fw-bold">
                                <i class="bi bi-shield-check me-1"></i> Approve & Verify as QC
                            </button>
                            <button type="button" id="btnQcReject" class="btn btn-danger flex-grow-1 fw-bold">
                                <i class="bi bi-x-circle me-1"></i> Reject
                            </button>
                        </div>
                    </form>
                    <form action="<?= $base_url ?>/con-vendor-payment-qc-reject/<?= $request['id'] ?>" method="POST" id="qcRejectVprForm" style="display:none;">
                        <input type="hidden" name="qc_note" id="qcRejectNoteHidden" value="">
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Management Approval Locked Banner for > 10K -->
        <?php if ($isMgmtLocked): ?>
            <div class="card custom-card border-warning mb-3 shadow-sm">
                <div class="card-header bg-warning bg-opacity-10 d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0 fw-bold text-warning">
                        <i class="bi bi-lock-fill me-2"></i>Management Approval Locked (&gt; tk10,000)
                    </h6>
                    <span class="badge bg-warning text-dark"><?= $mgmtLockReason === 'accounts_head' ? 'Awaiting Accounts Head' : 'Awaiting Op Head' ?></span>
                </div>
                <div class="card-body">
                    <p class="mb-0 text-muted small">
                        <?php if ($mgmtLockReason === 'accounts_head'): ?>
                            This request exceeds <strong>tk10,000</strong>. Admin & Operation Head has approved. <strong>Accounts Head (Role 30)</strong> must verify and approve this request before Management final sign-off.
                        <?php else: ?>
                            This request exceeds <strong>tk10,000</strong>. In accordance with policy, <strong>Admin & Operation Head (Role 20)</strong> must verify and approve this request first before Management sign-off.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Approval Actions (Operation Head / Management) -->
        <?php if ($canApproveReq): ?>
            <div class="card custom-card <?= $isOpHeadAction ? 'border-primary' : 'border-warning' ?> mb-0 shadow-sm">
                <div class="card-header <?= $isOpHeadAction ? 'bg-primary bg-opacity-10' : 'bg-warning bg-opacity-10' ?> d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0 fw-bold">
                        <i class="bi <?= $isOpHeadAction ? 'bi-shield-check text-primary' : 'bi-shield-lock' ?> me-2"></i>
                        <?= $isOpHeadAction ? 'Operation Head Verification Action (Step 1 of 3)' : (($isApproved) ? 'Additional Approval Action' : 'Management Final Approval Action') ?>
                    </h6>
                    <span class="badge <?= $isOpHeadAction ? 'bg-primary' : 'bg-warning text-dark' ?>">
                        <?= $isOpHeadAction ? 'Step 1 of 3' : 'Step 3 of 3' ?>
                    </span>
                </div>
                <div class="card-body">
                    <!-- Management Approval Form -->
                    <form action="<?= $base_url ?>/con-vendor-payment-approve/<?= $request['id'] ?>" method="POST"
                        id="approveVprForm" class="mb-3">
                        <input type="hidden" name="status" value="Approved">
                        <div class="mb-3">
                            <?php if ($isApproved): ?>
                                <div class="alert alert-info py-2 px-3 mb-3 small">
                                    Approved so far: <strong>tk <?= number_format($approvedAmtSoFar, 2) ?></strong><br>
                                    Remaining unapproved: <strong>tk <?= number_format($unapprovedAmt, 2) ?></strong>
                                </div>
                                <label class="form-label fw-semibold">Approve Additional Amount (TK)</label>
                                <input type="number" name="approved_amount" id="approveAmtInput" class="form-control"
                                    value="<?= number_format($unapprovedAmt, 2, '.', '') ?>" step="0.01" min="0.01"
                                    max="<?= number_format($unapprovedAmt, 2, '.', '') ?>" required>
                            <?php else: ?>
                                <?php 
                                    $defaultApprovedAmt = (isset($request['qc_approved_amount']) && (float)$request['qc_approved_amount'] > 0)
                                        ? (float)$request['qc_approved_amount']
                                        : $netPayable;
                                ?>
                                <?php if (isset($request['qc_approved_amount']) && (float)$request['qc_approved_amount'] > 0): ?>
                                    <div class="alert alert-info py-2 px-3 mb-3 small">
                                        <i class="bi bi-shield-check me-1"></i>
                                        QC Audited & Approved Amount: <strong>tk <?= number_format((float)$request['qc_approved_amount'], 2) ?></strong>
                                        <?php if ((float)$request['qc_approved_amount'] < $netPayable): ?>
                                            <span class="text-muted">(Adjusted from Net Payable tk <?= number_format($netPayable, 2) ?>)</span>
                                        <?php endif; ?>
                                        <?php if (!empty($request['qc_note'])): ?>
                                            <div class="fst-italic mt-1">"<?= htmlspecialchars($request['qc_note']) ?>"</div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <label class="form-label fw-semibold">Approved Amount (tk)</label>
                                <input type="number" name="approved_amount" id="approveAmtInput" class="form-control"
                                    value="<?= number_format($defaultApprovedAmt, 2, '.', '') ?>" step="0.01" min="0.01"
                                    max="<?= number_format($netPayable, 2, '.', '') ?>" required>
                                <small class="text-muted">Net payable: tk <?= number_format($netPayable, 2) ?><?= (isset($request['qc_approved_amount']) && (float)$request['qc_approved_amount'] > 0) ? ' | QC Recommended: tk ' . number_format((float)$request['qc_approved_amount'], 2) : '' ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Management Note (Optional)</label>
                            <textarea name="approval_note" class="form-control" rows="2"
                                placeholder="Leave a note..."></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" id="approveVprBtn" class="btn <?= $isOpHeadAction ? 'btn-primary' : 'btn-success' ?> flex-grow-1 fw-bold">
                                <i class="bi bi-check-circle me-1"></i>
                                <?= $isOpHeadAction ? 'Approve as Operation Head' : (($isApproved) ? 'Approve Additional' : 'Approve Payment') ?>
                            </button>
                            <?php if ($canReject): ?>
                                <button type="button" id="rejectVprBtn" class="btn btn-danger flex-grow-1 fw-bold">
                                    <i class="bi bi-x-circle me-1"></i> Reject
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                    <?php if ($canReject): ?>
                        <form action="<?= $base_url ?>/con-vendor-payment-reject/<?= $request['id'] ?>" method="POST"
                            id="rejectVprForm" style="display:none;">
                            <input type="hidden" name="approval_note" id="rejectNoteHidden" value="">
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Status Alerts -->
        <?php if ($isApproved && !$showPaymentForm): ?>
            <div class="alert alert-success text-center mb-0">
                <i class="bi bi-check-circle-fill fs-4 d-block mb-2"></i>
                Approved — awaiting payment processing.
                <?php if ($canReject): ?>
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="triggerMgmtRejectModal()">
                            <i class="bi bi-x-circle me-1"></i> Reject Requisition
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            </div>
        <?php elseif ($isFullyPaid): ?>
            <div class="alert alert-secondary text-center mb-0">
                <i class="bi bi-cash-coin fs-4 d-block mb-2"></i>
                All payments have been fully processed.
            </div>
        <?php elseif ($isRejected): ?>
            <div class="alert alert-danger text-center mb-0">
                <i class="bi bi-x-circle-fill fs-4 d-block mb-2"></i>
                This payment request has been rejected.
            </div>
        <?php endif; ?>

        <!-- Process Payment Card -->
        <?php if ($showPaymentForm): ?>
            <div class="card custom-card border-success mb-0">
                <div class="card-header bg-success bg-opacity-10">
                    <h6 class="card-title mb-0"><i class="bi bi-wallet2 me-2"></i>Process Payment</h6>
                </div>
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Remaining Due: <strong class="text-danger fs-5">tk
                            <?= number_format($dueAmount, 2) ?></strong></p>
                    <button class="btn btn-success w-100 fw-bold" data-bs-toggle="modal"
                        data-bs-target="#addPaymentModal">
                        <i class="bi bi-plus-circle me-1"></i>
                        <?= empty($payments) ? 'Add First Payment' : 'Add Another Payment' ?>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ROW 4: PO Payment History (Full width if present) -->
<?php if (!empty($po_payments) && count($po_payments) > 1): ?>
<div class="row mb-3">
    <div class="col-12">
        <div class="card custom-card mb-0">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="bi bi-list-check me-2"></i>PO Payment History</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>VPR</th>
                            <th>Type</th>
                            <th>Created</th>
                            <th class="text-end">Invoice</th>
                            <th class="text-end">Net Payable</th>
                            <th class="text-end">Total Paid</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($po_payments as $pp): ?>
                            <tr class="<?= $pp['id'] == $request['id'] ? 'table-primary fw-bold' : '' ?>">
                                <td>VPR-<?= $pp['id'] ?></td>
                                <td>
                                    <span
                                        class="badge <?= $pp['payment_type'] === 'Advance' ? 'bg-primary' : ($pp['payment_type'] === 'Partial' ? 'bg-warning ' : 'bg-success') ?>">
                                        <?= $pp['payment_type'] ?>
                                    </span>
                                </td>
                                <td><?= !empty($pp['created_at']) ? date('d M Y', strtotime($pp['created_at'])) : '—' ?>
                                </td>
                                <td class="text-end">tk <?= number_format($pp['invoice_amount'] ?? 0, 2) ?></td>
                                <td class="text-end">tk <?= number_format($pp['net_payable'] ?? 0, 2) ?></td>
                                <td class="text-end fw-semibold text-success">tk
                                    <?= number_format($pp['total_paid'] ?? 0, 2) ?>
                                </td>
                                <td>
                                    <?php
                                    $ppBadge = $statusMap[$pp['status']] ?? ['color' => 'secondary', 'label' => $pp['status']];
                                    ?>
                                    <span class="badge bg-<?= $ppBadge['color'] ?>"><?= $ppBadge['label'] ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Add Payment Modal -->
<?php if ($showPaymentForm): ?>
    <div class="modal fade" id="addPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="<?= $base_url ?>/con-vendor-payment-payment/<?= $request['id'] ?>" method="POST"
                    enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-wallet2 me-2"></i>Add Payment — VPR-<?= $request['id'] ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row ">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Payment Amount (tk) *</label>
                                <input type="number" name="amount" class="form-control" step="0.01" min="0.01"
                                    max="<?= number_format($dueAmount, 2, '.', '') ?>" required
                                    placeholder="Max: <?= number_format($dueAmount, 2) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Payment Date *</label>
                                <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Payment Method *</label>
                                <select name="payment_method" id="vprPaymentMethod" class="form-select" required>
                                    <option value="Cash">Cash</option>
                                    <option value="Cheque">Cheque</option>
                                    <option value="BankTransfer">Bank Transfer</option>
                                    <option value="MobileWallet">Mobile Wallet</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Reference Number</label>
                                <input type="text" name="reference_no" class="form-control"
                                    placeholder="Cheque/Transaction No.">
                            </div>
                            <!-- Bank Transfer Field (hidden by default) -->
                            <div class="col-md-6 vpr-bank-field" style="display: none;">
                                <label class="form-label fw-semibold">Select Bank *</label>
                                <select name="bank_id" class="form-select">
                                    <option value="">-- Select Bank --</option>
                                    <?php if (!empty($banks)): ?>
                                        <?php foreach ($banks as $bank): ?>
                                            <option value="<?= $bank['id'] ?>"><?= htmlspecialchars($bank['bank_name']) ?> —
                                                <?= htmlspecialchars($bank['branch_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Disbursement Proof (Required) *</label>
                                <input type="file" name="disbursement_proof[]" class="form-control" multiple required
                                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                <small class="text-muted">PDF, DOC, JPG, PNG — Max 5MB each</small>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Notes</label>
                                <textarea name="notes" class="form-control" rows="2"
                                    placeholder="Optional payment notes..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success fw-bold">
                            <i class="bi bi-check-circle me-1"></i> Submit Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- CST Matrix Modal for Vendor Payment Request -->
<?php if (!empty($cstMatrix) && !empty($cstMatrix['vendors'])): ?>
<div class="modal fade" id="vprCstMatrixModal" tabindex="-1" aria-labelledby="vprCstMatrixModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:14px;border:none;box-shadow:0 10px 40px rgba(0,0,0,.15)">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold text-primary" id="vprCstMatrixModalLabel">
          <i class="bi bi-table me-2"></i>Comparative Statement (CST) Matrix
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-3">
        <?php
        $materials = $cstMatrix['materials'] ?? [];
        $vendors   = $cstMatrix['vendors']   ?? [];
        $matrix    = $cstMatrix['matrix']    ?? [];
        $totals    = $cstMatrix['totals']    ?? [];
        $vendorIds = array_keys($vendors);
        $selectedVendorId = $selVenId;
        ?>
        <div class="table-responsive">
          <table class="table table-bordered align-middle text-center mb-0" style="font-size:12px">
            <thead class="table-dark">
              <tr>
                <th class="text-start" style="width:4%;">#</th>
                <th class="text-start" style="width:22%;">Material</th>
                <th style="width:8%;">PR Qty</th>
                <th style="width:8%;">Site Stock</th>
                <th style="width:8%;">3-Mo Forecast</th>
                <?php foreach ($vendors as $vid => $vname): ?>
                  <th style="min-width:130px;" class="<?= ($vid == $selectedVendorId) ? 'bg-success text-white border-success' : '' ?>">
                    <?= htmlspecialchars($vname) ?>
                    <?php if ($vid == $selectedVendorId): ?>
                      <br><span class="badge bg-white text-success mt-1 px-2" style="font-size:9px"><i class="bi bi-check-circle-fill me-1"></i>Selected Vendor</span>
                    <?php endif; ?>
                  </th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($materials as $idx => $mat): ?>
              <?php
              $mid = $mat['material_id'];
              $lowestPrice  = PHP_FLOAT_MAX;
              $lowestVendor = 0;
              foreach ($vendorIds as $vid) {
                  if (isset($matrix[$mid][$vid])) {
                      if ($matrix[$mid][$vid]['unit_price'] < $lowestPrice) {
                          $lowestPrice  = $matrix[$mid][$vid]['unit_price'];
                          $lowestVendor = $vid;
                      }
                  }
              }
              ?>
              <tr>
                  <td class="text-start fw-bold"><?= $idx + 1 ?></td>
                  <td class="text-start fw-bold">
                      <?= htmlspecialchars($mat['material_name']) ?>
                      <span class="text-muted small">(<?= $mat['unit'] ?>)</span>
                  </td>
                  <td><?= number_format($mat['quantity'], 3) ?></td>
                  <td>
                      <span class="badge bg-light text-dark border">
                          <?= number_format((float)($mat['current_stock'] ?? 0), 3) ?>
                      </span>
                  </td>
                  <td>
                      <?php if (!empty($mat['next_3_months_requirement']) && (float)$mat['next_3_months_requirement'] > 0): ?>
                          <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                              <?= number_format((float)$mat['next_3_months_requirement'], 3) ?>
                          </span>
                      <?php else: ?>
                          <span class="text-muted">—</span>
                      <?php endif; ?>
                  </td>
                  <?php foreach ($vendorIds as $vid): ?>
                      <?php
                      $cell = $matrix[$mid][$vid] ?? null;
                      $isLowest = ($vid === $lowestVendor && $cell);
                      $isSelectedVendor = ($vid == $selectedVendorId);
                      ?>
                      <td class="<?= $isLowest ? 'table-success fw-bold' : '' ?> <?= $isSelectedVendor ? 'border-success' : '' ?>" style="<?= $isSelectedVendor ? 'border-left-width:2px;border-right-width:2px;background:rgba(25,135,84,0.06);' : '' ?>">
                          <?php if ($cell): ?>
                              tk <?= number_format($cell['unit_price'], 2) ?>
                              <div class="small text-muted" style="margin-top:2px">
                                  Total: tk <?= number_format($cell['total_price'], 2) ?>
                              </div>
                          <?php else: ?>
                              <span class="text-muted">—</span>
                          <?php endif; ?>
                      </td>
                  <?php endforeach; ?>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light">
                <tr class="fw-bold">
                    <td colspan="5" class="text-end">Vendor Totals:</td>
                    <?php foreach ($vendorIds as $vid): ?>
                        <td class="<?= ($vid == $selectedVendorId) ? 'text-success' : '' ?>">tk <?= number_format($totals[$vid] ?? 0, 2) ?></td>
                    <?php endforeach; ?>
                </tr>
            </tfoot>
          </table>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close Window</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        var methodSelect = document.getElementById('vprPaymentMethod');
        var bankFields = document.querySelectorAll('.vpr-bank-field');

        function toggleBankField() {
            var show = methodSelect && methodSelect.value === 'BankTransfer';
            bankFields.forEach(function (el) {
                el.style.display = show ? '' : 'none';
                var sel = el.querySelector('select');
                if (sel) sel.required = show;
            });
        }

        if (methodSelect) {
            methodSelect.addEventListener('change', toggleBankField);
            var modal = document.getElementById('addPaymentModal');
            if (modal) {
                modal.addEventListener('shown.bs.modal', toggleBankField);
            }
            toggleBankField();
        }

        // ── QC Approve Button ──────────────────────────────────────────────────
        var btnQcApprove = document.getElementById('btnQcApprove');
        var qcApproveForm = document.getElementById('qcApproveVprForm');
        if (btnQcApprove && qcApproveForm) {
            btnQcApprove.addEventListener('click', function () {
                var amtInput = document.getElementById('qcApprovedAmtInput');
                if (amtInput) {
                    var val = parseFloat(amtInput.value);
                    var max = parseFloat(amtInput.getAttribute('max'));
                    if (isNaN(val) || val <= 0) {
                        Swal.fire('Validation Error', 'Please enter a valid QC approved amount greater than 0.', 'error');
                        return;
                    }
                    if (max && val > max + 0.01) {
                        Swal.fire('Validation Error', 'QC approved amount cannot exceed the net payable amount (tk ' + max.toFixed(2) + ').', 'error');
                        return;
                    }
                }
                Swal.fire({
                    title: 'Approve QC Verification?',
                    text: 'This will verify and forward this payment request to Management for final approval.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0dcaf0',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Approve QC',
                    cancelButtonText: 'Cancel'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        qcApproveForm.submit();
                    }
                });
            });
        }

        // ── QC Reject Button ───────────────────────────────────────────────────
        var btnQcReject = document.getElementById('btnQcReject');
        var qcRejectForm = document.getElementById('qcRejectVprForm');
        if (btnQcReject && qcRejectForm) {
            btnQcReject.addEventListener('click', function () {
                Swal.fire({
                    title: 'Reject Payment Request (QC)?',
                    text: 'Please enter rejection reason / observation:',
                    input: 'textarea',
                    inputPlaceholder: 'Reason for QC rejection...',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Reject',
                    cancelButtonText: 'Cancel',
                    inputValidator: function (val) {
                        if (!val || !val.trim()) {
                            return 'Rejection reason is required!';
                        }
                    }
                }).then(function (result) {
                    if (result.isConfirmed) {
                        document.getElementById('qcRejectNoteHidden').value = result.value;
                        qcRejectForm.submit();
                    }
                });
            });
        }

        // ── Management Approve Button ──────────────────────────────────────────
        var approveBtn = document.getElementById('approveVprBtn');
        var approveForm = document.getElementById('approveVprForm');
        if (approveBtn && approveForm) {
            approveBtn.addEventListener('click', function () {
                var amt = document.getElementById('approveAmtInput');
                if (!amt || !amt.value || parseFloat(amt.value) <= 0) {
                    Swal.fire({ icon: 'warning', title: 'Amount Required', text: 'Please enter a valid approved amount.', confirmButtonColor: '#198754' });
                    return;
                }
                Swal.fire({
                    title: 'Approve Payment?',
                    text: 'Amount: tk ' + parseFloat(amt.value).toLocaleString('en-BD', { minimumFractionDigits: 2 }),
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Approve',
                    cancelButtonText: 'Cancel'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        approveForm.submit();
                    }
                });
            });
        }

        // ── Management Reject Button ───────────────────────────────────────────
        var rejectBtn = document.getElementById('rejectVprBtn');
        var rejectForm = document.getElementById('rejectVprForm');
        if (rejectBtn && rejectForm) {
            rejectBtn.addEventListener('click', function () {
                Swal.fire({
                    title: 'Reject Payment Request?',
                    text: 'This action will reject the request. Are you sure?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Reject',
                    cancelButtonText: 'Cancel'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        rejectForm.submit();
                    }
                });
            });
        }

        // ── Auto-Open Payment Modal if Hash is #disburse or #pay ───────────────
        if (window.location.hash === '#disburse' || window.location.hash === '#pay') {
            var payModalEl = document.getElementById('addPaymentModal');
            if (payModalEl) {
                var payModal = new bootstrap.Modal(payModalEl);
                payModal.show();
            }
        }
    });

    window.triggerQcRejectModal = function () {
        Swal.fire({
            title: 'Reject Payment Request (QC)?',
            text: 'Please enter rejection reason / observation:',
            input: 'textarea',
            inputPlaceholder: 'Reason for QC rejection...',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Reject',
            cancelButtonText: 'Cancel',
            inputValidator: function (val) {
                if (!val || !val.trim()) {
                    return 'Rejection reason is required!';
                }
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                var f = document.getElementById('qcRejectVprForm');
                if (!f) {
                    f = document.createElement('form');
                    f.method = 'POST';
                    f.action = '<?= $base_url ?>/con-vendor-payment-qc-reject/<?= $request['id'] ?>';
                    var inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = 'qc_note';
                    inp.value = result.value;
                    f.appendChild(inp);
                    document.body.appendChild(f);
                } else {
                    document.getElementById('qcRejectNoteHidden').value = result.value;
                }
                f.submit();
            }
        });
    };

    window.triggerMgmtRejectModal = function () {
        Swal.fire({
            title: 'Reject Payment Request (Management)?',
            text: 'Please enter remarks for rejection:',
            input: 'textarea',
            inputPlaceholder: 'Reason for rejection...',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Reject',
            cancelButtonText: 'Cancel',
            inputValidator: function (val) {
                if (!val || !val.trim()) {
                    return 'Rejection remarks are required!';
                }
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                var f = document.getElementById('rejectVprForm');
                if (!f) {
                    f = document.createElement('form');
                    f.method = 'POST';
                    f.action = '<?= $base_url ?>/con-vendor-payment-reject/<?= $request['id'] ?>';
                    var inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = 'approval_note';
                    inp.value = result.value;
                    f.appendChild(inp);
                    document.body.appendChild(f);
                } else {
                    var hiddenNote = document.getElementById('rejectNoteHidden');
                    if (hiddenNote) hiddenNote.value = result.value;
                }
                f.submit();
            }
        });
    };
</script>
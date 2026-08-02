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
    $paidStatusClass = 'bg-info text-white';
} elseif ($isAdvanceType) {
    $paidStatusLabel = 'Advance Request';
    $paidStatusClass = 'bg-primary';
} else {
    $paidStatusLabel = 'Unpaid / Pending';
    $paidStatusClass = 'bg-secondary';
}

$canPayReq = $canProcessPayment && $isApproved && $dueAmount > 0;
$showPaymentForm = $canPayReq;

$netPayable = (float) ($request['net_payable'] ?? 0);
$approvedAmtSoFar = (float) ($request['approved_amount'] ?? 0);
$unapprovedAmt = max(0, $netPayable - $approvedAmtSoFar);
$canApproveReq = ($canApprove ?? false) && ($isSubmitted || $isQCApproved || ($isApproved && $unapprovedAmt > 0.01));
$canReject = ($canApproveReq && $isSubmitted) || ($canApproveReq && $isQCApproved);

// Payment type badge
$typeBadge = match ($request['payment_type'] ?? 'Final') {
    'Advance' => ['class' => 'bg-primary', 'label' => 'Advance'],
    'Partial' => ['class' => 'bg-warning text-dark', 'label' => 'Partial'],
    default => ['class' => 'bg-success', 'label' => 'Final'],
};

$penaltyAmt = (float) ($request['penalty_amount'] ?? 0);
$invoiceAmt = (float) ($request['invoice_amount'] ?? 0);
$advancePaid = (float) ($advance_paid ?? 0);
?>

<!-- Header -->
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div class="my-auto">
        <h3 class="page-title fw-semibold fs-18 mb-0">Vendor Payment Request #<?= $request['id'] ?></h4>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-vendor-payment-requests">Payment
                            Requests</a></li>
                    <li class="breadcrumb-item active">VPR-<?= $request['id'] ?></li>
                </ol>
            </nav>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <?php if (!empty($payments)): ?>
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
    $nextActionText = 'Disburse the net payable amount of ৳ ' . number_format($dueAmount, 2) . '.';
    $nextActionRole = 'Accounts Team';
    $nextActionColor = 'success';
} elseif ($status === 'Partially_Paid') {
    $nextActionText = 'Disburse the remaining due amount of ৳ ' . number_format($dueAmount, 2) . '.';
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
                            <span class="badge bg-success">QC Approved</span>
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
                            <span class="badge bg-success">Approved</span>
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
                                <strong>Total Paid:</strong> ৳ <?= number_format($totalPaid, 2) ?><br>
                                <strong>Remaining Due:</strong> ৳ 0.00
                            </div>
                        <?php elseif ($payPartial): ?>
                            <span class="badge bg-info animate-blink">Partially Paid</span>
                            <div class="small mt-2 text-muted">
                                <strong>Total Paid:</strong> ৳ <?= number_format($totalPaid, 2) ?><br>
                                <strong>Remaining Due:</strong> <span class="text-danger fw-bold">৳ <?= number_format($dueAmount, 2) ?></span>
                            </div>
                        <?php elseif ($payPending): ?>
                            <span class="badge bg-warning animate-blink">Pending Payment</span>
                            <div class="small mt-2 text-muted">
                                <strong>Approved Amt:</strong> ৳ <?= number_format($payableAmount, 2) ?><br>
                                <strong>Pending Due:</strong> <span class="text-danger fw-bold">৳ <?= number_format($dueAmount, 2) ?></span>
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
                        <div class="col-md-6">
                            <div class="info-label text-uppercase text-muted small fw-bold">Purchase Order</div>
                            <div class="">
                                <a
                                    href="<?= $base_url ?>/con-purchase-order/<?= $request['purchase_order_id'] ?>">PO-<?= $request['purchase_order_id'] ?></a>
                            </div>
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
                                <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-circle me-1"></i> Not Received</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Financial Summary Row -->
                    <div class="col-md-12">
                        <div class="d-grid gap-2 mt-1" style="grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));">
                            <div class="border rounded p-3 text-center">
                                <small class="text-muted d-block mb-1">Invoice Amount</small>
                                <h5 class="text-primary mb-0 fw-bold">৳ <?= number_format($invoiceAmt, 2) ?>
                                </h5>
                            </div>
                            <?php if ($penaltyAmt > 0): ?>
                                <div class="border rounded p-3 text-center">
                                    <small class="text-muted d-block mb-1">Penalty Deducted</small>
                                    <h5 class="text-danger mb-0 fw-bold">- ৳ <?= number_format($penaltyAmt, 2) ?>
                                    </h5>
                                </div>
                            <?php endif; ?>
                            <div class="border rounded p-3 text-center">
                                <small class="text-muted d-block mb-1">Net Payable</small>
                                <h5 class="text-success mb-0 fw-bold">৳ <?= number_format($netPayable, 2) ?>
                                </h5>
                            </div>
                            <?php if (!empty($request['approved_amount'])): ?>
                                <div class="border rounded p-3 text-center border-success">
                                    <small class="text-muted d-block mb-1">Approved Amount</small>
                                    <h5 class="text-success mb-0 fw-bold">৳
                                        <?= number_format($request['approved_amount'], 2) ?>
                                    </h5>
                                </div>
                            <?php endif; ?>
                            <?php if ($isApproved || $isFullyPaid): ?>
                                <div class="border rounded p-3 text-center">
                                    <small class="text-muted d-block mb-1">Total Paid</small>
                                    <h5 class="text-success mb-0 fw-bold">৳ <?= number_format($totalPaid, 2) ?></h5>
                                </div>
                                <div class="border rounded p-3 text-center">
                                    <small class="text-muted d-block mb-1">Due Amount</small>
                                    <?php if ($dueAmount > 0): ?>
                                        <h5 class="text-danger mb-0 fw-bold">৳ <?= number_format($dueAmount, 2) ?></h5>
                                    <?php else: ?>
                                        <h5 class="text-success mb-0 fw-bold">৳ 0.00</h5>
                                    <?php endif; ?>
                                </div>
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
                                                <p class="mb-2 text-dark" style="font-size: 14px;">
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

<!-- Material Receiving Logs (GRN) Section -->
<div class="card custom-card mb-4 border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0 fw-bold text-dark">
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
                                    <span class="badge bg-<?= $ml['received_status'] === 'Full' ? 'success' : ($ml['received_status'] === 'Partial' ? 'warning text-dark' : 'danger') ?>">
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
                                    <th>Amount (৳)</th>
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
                                        <td class="text-end fw-semibold">৳ <?= number_format($pay['amount_paid'], 2) ?></td>
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
                                        <th>Amount (৳)</th>
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
                                            <td class="text-end fw-semibold">৳ <?= number_format($pay['amount_paid'], 2) ?></td>
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
                            <th class="text-end">Amount (৳)</th>
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
                        class="btn btn-sm btn-primary fw-bold">
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
                                    <th class="text-end">Amount (৳)</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($adjustments as $adj):
                                    $typeBadge = ($adj['adjustment_type'] === 'return') ? 'info text-dark' : 'warning text-dark';
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
                                        <td class="text-end fw-semibold">৳ <?= number_format($adj['amount'], 2) ?></td>
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

        <!-- Approval Actions (Management) -->
        <?php if ($canApproveReq): ?>
            <div class="card custom-card border-warning mb-0">
                <div class="card-header bg-warning bg-opacity-10">
                    <h6 class="card-title mb-0"><i
                            class="bi bi-shield-lock me-2"></i><?= ($isApproved) ? 'Additional Approval Action' : 'Approval Action' ?>
                    </h6>
                </div>
                <div class="card-body">
                    <!-- Management Approval Form -->
                    <form action="<?= $base_url ?>/con-vendor-payment-approve/<?= $request['id'] ?>" method="POST"
                        id="approveVprForm" class="mb-3">
                        <input type="hidden" name="status" value="Approved">
                        <div class="mb-3">
                            <?php if ($isApproved): ?>
                                <div class="alert alert-info py-2 px-3 mb-3 small">
                                    Approved so far: <strong>৳ <?= number_format($approvedAmtSoFar, 2) ?></strong><br>
                                    Remaining unapproved: <strong>৳ <?= number_format($unapprovedAmt, 2) ?></strong>
                                </div>
                                <label class="form-label fw-semibold">Approve Additional Amount (৳)</label>
                                <input type="number" name="approved_amount" id="approveAmtInput" class="form-control"
                                    value="<?= number_format($unapprovedAmt, 2, '.', '') ?>" step="0.01" min="0.01"
                                    max="<?= number_format($unapprovedAmt, 2, '.', '') ?>" required>
                            <?php else: ?>
                                <label class="form-label fw-semibold">Approved Amount (৳)</label>
                                <input type="number" name="approved_amount" id="approveAmtInput" class="form-control"
                                    value="<?= number_format($netPayable, 2, '.', '') ?>" step="0.01" min="0.01"
                                    max="<?= number_format($netPayable, 2, '.', '') ?>" required>
                                <small class="text-muted">Net payable: ৳ <?= number_format($netPayable, 2) ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Management Note (Optional)</label>
                            <textarea name="approval_note" class="form-control" rows="2"
                                placeholder="Leave a note..."></textarea>
                        </div>
                        <button type="button" id="approveVprBtn" class="btn btn-success w-100 fw-bold mb-2">
                            <i class="bi bi-check-circle me-1"></i>
                            <?= ($isApproved) ? 'Approve Additional' : 'Approve Payment' ?>
                        </button>
                    </form>
                    <?php if ($canReject): ?>
                        <form action="<?= $base_url ?>/con-vendor-payment-reject/<?= $request['id'] ?>" method="POST"
                            id="rejectVprForm">
                            <button type="button" id="rejectVprBtn" class="btn btn-outline-danger w-100 btn-sm">
                                <i class="bi bi-x-circle me-1"></i> Reject
                            </button>
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
                    <p class="text-muted mb-2">Remaining Due: <strong class="text-danger fs-5">৳
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
                                        class="badge <?= $pp['payment_type'] === 'Advance' ? 'bg-primary' : ($pp['payment_type'] === 'Partial' ? 'bg-warning text-dark' : 'bg-success') ?>">
                                        <?= $pp['payment_type'] ?>
                                    </span>
                                </td>
                                <td><?= !empty($pp['created_at']) ? date('d M Y', strtotime($pp['created_at'])) : '—' ?>
                                </td>
                                <td class="text-end">৳ <?= number_format($pp['invoice_amount'] ?? 0, 2) ?></td>
                                <td class="text-end">৳ <?= number_format($pp['net_payable'] ?? 0, 2) ?></td>
                                <td class="text-end fw-semibold text-success">৳
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
                                <label class="form-label fw-semibold">Payment Amount (৳) *</label>
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

        // ── Approve Button ─────────────────────────────────────────────────────
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
                    text: 'Amount: ৳ ' + parseFloat(amt.value).toLocaleString('en-BD', { minimumFractionDigits: 2 }),
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

        // ── Reject Button ──────────────────────────────────────────────────────
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
    });
</script>
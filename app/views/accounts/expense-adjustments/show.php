<?php
$page_title = "Adjustment Details";
$page = 'Accounts';
$show_breadcrumb = true;

$isReturn = ($adj['adjustment_type'] == 'return');
$isExtra = ($adj['adjustment_type'] == 'extra');

// Status badge and descriptive label
$badge = 'bg-secondary';
$statusLabel = strtoupper(str_replace('_', ' ', $adj['status']));

if ($adj['status'] == 'pending') {
    if ($isReturn) {
        $badge = 'bg-warning text-dark';
        $statusLabel = 'PENDING ACCOUNTS VERIFICATION';
    } else {
        $badge = 'bg-warning text-dark';
        $statusLabel = 'PENDING QC & INTERNAL AUDIT';
    }
} elseif ($adj['status'] == 'qc_approved') {
    $badge = 'bg-primary text-white';
    $statusLabel = 'QC AUDITED — AWAITING MANAGEMENT';
} elseif ($adj['status'] == 'approved') {
    $badge = 'bg-info text-dark';
    $statusLabel = 'APPROVED — PENDING ACCOUNTS PAYMENT';
} elseif ($adj['status'] == 'partially_paid') {
    $badge = 'bg-info text-dark';
    $statusLabel = 'PARTIALLY SETTLED';
} elseif ($adj['status'] == 'fully_paid') {
    $badge = 'bg-success';
    $statusLabel = $isReturn ? 'VERIFIED & RETURNED TO CASH-IN' : 'SETTLED & FULLY PAID';
} elseif ($adj['status'] == 'rejected') {
    $badge = 'bg-danger';
    $statusLabel = 'REJECTED';
}

// Parent request linking
$link = '#';
$parentLabel = 'Requisition';
$prefix = 'REQ';

if ($adj['parent_type'] == 'cashout') {
    $link = $GLOBALS['base_url'] . '/view-cashout-request?id=' . $adj['cashout_request_id'];
    $prefix = 'REQ';
    $parentLabel = 'Cashout Request';
} elseif ($adj['parent_type'] == 'vendor') {
    $link = $GLOBALS['base_url'] . '/vendor-payment-requests/show?id=' . $adj['cashout_request_id'];
    $prefix = 'VENDORREQ';
    $parentLabel = 'Vendor Payment Request';
} elseif ($adj['parent_type'] == 'contractor') {
    $link = $GLOBALS['base_url'] . '/con-contractor-bill/' . $adj['cashout_request_id'];
    $prefix = 'CONTRACTORREQ';
    $parentLabel = 'Contractor Bill';
} elseif ($adj['parent_type'] == 'purchase') {
    $link = $GLOBALS['base_url'] . '/purchase-requests/show?id=' . $adj['cashout_request_id'];
    $prefix = 'PURCHASEREQ';
    $parentLabel = 'Purchase Request';
} elseif ($adj['parent_type'] == 'other_cost') {
    $link = $GLOBALS['base_url'] . '/project-other-costs';
    $prefix = 'OTHERCOST';
    $parentLabel = 'Project Other Cost';
} elseif ($adj['parent_type'] == 'extra_purchase') {
    $link = $GLOBALS['base_url'] . '/asset-extra-purchases';
    $prefix = 'EXTRAASSET';
    $parentLabel = 'Asset Extra Purchase';
}

$effectiveDue = (float)($adj['due_amount'] > 0 ? $adj['due_amount'] : ($isReturn ? $adj['amount'] : ($adj['approved_amount'] ?: $adj['amount'])));

if (!class_exists('ApprovalRequest')) {
    require_once __DIR__ . '/../../../models/ApprovalRequest.php';
}
$ahInfo = ApprovalRequest::getAccountsHeadApprovalInfo($GLOBALS['db'] ?? ($this->db ?? null), 'expense_adjustments', (int)$adj['id']);
$isAhApproved = empty($ahInfo['has_request']) || !empty($ahInfo['is_accounts_head_approved']);
?>

<?php 
if (!empty($ahInfo['has_request'])) {
    $sourceTable = 'expense_adjustments';
    $sourceId = (int)$adj['id'];
    include __DIR__ . '/../../layouts/accounts_head_approval_card.php';
}
?>

<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card__header d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="card__title mb-0">Expense Adjustment ADJ-<?= $adj['id'] ?></h5>
                    <?php if ($isReturn): ?>
                        <span class="badge bg-info-subtle text-info border border-info px-2 py-1 fs-6">
                            <i class="fa-light fa-arrow-turn-down-left me-1"></i> Return Unused Money
                        </span>
                    <?php else: ?>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2 py-1 fs-6">
                            <i class="fa-light fa-circle-plus me-1"></i> Extra Amount Request
                        </span>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-2">
                    <?php if (!empty($transactions)): ?>
                        <a href="<?= $GLOBALS['base_url'] ?>/print-debit-voucher?module=expense_adjustment&id=<?= $adj['id'] ?>" target="_blank" class="btn btn-primary btn-sm">
                            <i class="fa-light fa-file-invoice-dollar me-1"></i> <?= $isReturn ? 'Receipt / Debit Voucher' : 'Debit Voucher' ?>
                        </a>
                    <?php endif; ?>
                    <a href="<?= $GLOBALS['base_url'] ?>/expense-adjustments" class="btn btn-secondary btn-sm goBackBtn" style="flex: none;">
                        <i class="fa-light fa-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
            
            <div class="card__body">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $_SESSION['success']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <?php unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= $_SESSION['error']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <?php unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Left Column: Details & Audit Lifecycle -->
                    <div class="col-lg-8">
                        <!-- Adjustment Request Info Card -->
                        <div class="card mb-4 border shadow-sm">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold"><i class="fa-light fa-file-lines me-2 text-primary"></i>Adjustment Request Details</h6>
                                <span class="badge <?= $badge ?> fs-7 px-2 py-1"><?= $statusLabel ?></span>
                            </div>
                            <div class="card-body">
                                <div class="row ">
                                    <div class="col-md-6">
                                        <label class="text-muted mb-1 d-block small text-uppercase fw-semibold">Requester</label>
                                        <div class="fw-bold fs-6"><?= htmlspecialchars($adj['requester_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($adj['requester_role'] ?? 'Staff') ?></small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted mb-1 d-block small text-uppercase fw-semibold">Submission Date</label>
                                        <div class="fw-bold"><?= date('d M Y, h:i A', strtotime($adj['created_at'])) ?></div>
                                        <small class="text-muted"><?= date('l', strtotime($adj['created_at'])) ?></small>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="text-muted mb-1 d-block small text-uppercase fw-semibold">Adjustment Title / Purpose</label>
                                        <div class="fw-bold fs-6 p-2 rounded bg-light border"><?= htmlspecialchars($adj['title']) ?></div>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="text-muted mb-1 d-block small text-uppercase fw-semibold">Description / Justification</label>
                                        <div class="p-2 rounded bg-light border text-secondary"><?= nl2br(htmlspecialchars($adj['description'] ?? '—')) ?></div>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="text-muted mb-1 d-block small text-uppercase fw-semibold"><?= $isReturn ? 'Return Amount' : 'Requested Extra' ?></label>
                                        <div class="fs-4 fw-bold <?= $isReturn ? 'text-success' : 'text-danger' ?>">
                                            TK. <?= number_format($adj['amount'], 2) ?>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="text-muted mb-1 d-block small text-uppercase fw-semibold">Approved Amount</label>
                                        <div class="fs-4 fw-bold text-primary">
                                            <?= $adj['approved_amount'] ? 'TK. ' . number_format($adj['approved_amount'], 2) : ($isReturn ? 'TK. ' . number_format($adj['amount'], 2) : '—') ?>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="text-muted mb-1 d-block small text-uppercase fw-semibold">Settlement Balance (Due)</label>
                                        <div class="fs-4 fw-bold <?= ($adj['status'] == 'fully_paid') ? 'text-success' : 'text-warning' ?>">
                                            <?php if ($adj['status'] == 'fully_paid'): ?>
                                                <span class="text-success"><i class="fa-light fa-check-circle me-1"></i> TK. 0.00</span>
                                            <?php else: ?>
                                                TK. <?= number_format($effectiveDue, 2) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <?php if ($adj['status'] == 'rejected' && !empty($adj['rejection_note'])): ?>
                                        <div class="col-md-12">
                                            <div class="alert alert-danger mb-0">
                                                <strong><i class="fa-light fa-circle-exclamation me-1"></i> Rejection Reason:</strong>
                                                <div class="mt-1"><?= nl2br(htmlspecialchars($adj['rejection_note'])) ?></div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($adj['document']) && $adj['document'] !== 'null'): ?>
                                        <div class="col-md-12 border-top pt-3">
                                            <label class="text-muted mb-2 d-block small text-uppercase fw-semibold">Attached Supporting Documents</label>
                                            <div class="d-flex flex-wrap gap-2">
                                                <?php 
                                                $docs = json_decode($adj['document'], true) ?: []; 
                                                foreach($docs as $i => $path): 
                                                    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                                                ?>
                                                    <a href="<?= $GLOBALS['base_url'] ?>/<?= $path ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                                                        <?php if(in_array($ext, ['jpg','png','jpeg'])): ?>
                                                            <i class="fa-light fa-image me-1 text-success"></i>
                                                        <?php elseif($ext == 'pdf'): ?>
                                                            <i class="fa-light fa-file-pdf me-1 text-danger"></i>
                                                        <?php else: ?>
                                                            <i class="fa-light fa-file me-1"></i>
                                                        <?php endif; ?>
                                                        Attachment <?= $i+1 ?>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Parent Request Reference Card -->
                        <div class="card mb-4 border shadow-sm">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold"><i class="fa-light fa-link me-2 text-info"></i>Original Requisition Context (<?= htmlspecialchars($parentLabel) ?>)</h6>
                                <a href="<?= $link ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                    View Original Request <i class="fa-light fa-arrow-up-right-from-square ms-1"></i>
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="row ">
                                    <div class="col-md-6">
                                        <label class="text-muted mb-1 d-block small text-uppercase fw-semibold">Parent Reference ID</label>
                                        <a href="<?= $link ?>" class="fw-bold fs-6 text-primary" target="_blank">
                                            <?= $prefix ?>-<?= $adj['cashout_request_id'] ?> <i class="fa-light fa-external-link ms-1"></i>
                                        </a>
                                        <small class="text-muted d-block"><?= htmlspecialchars($parentLabel) ?></small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted mb-1 d-block small text-uppercase fw-semibold">Original Paid / Approved Amount</label>
                                        <div class="fw-bold fs-6 text-dark">TK. <?= number_format($adj['original_amount'], 2) ?></div>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="text-muted mb-1 d-block small text-uppercase fw-semibold">Original Purpose / Work Description</label>
                                        <div class="p-2 rounded bg-light border"><?= htmlspecialchars($adj['original_purpose'] ?? '—') ?></div>
                                    </div>

                                    <?php if(!empty($adj['project_name'])): ?>
                                        <div class="col-md-6">
                                            <label class="text-muted mb-1 d-block small text-uppercase fw-semibold">Assigned Project</label>
                                            <div class="fw-bold text-info"><i class="fa-light fa-building me-1"></i> <?= htmlspecialchars($adj['project_name']) ?></div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if(!empty($adj['office'])): ?>
                                        <div class="col-md-6">
                                            <label class="text-muted mb-1 d-block small text-uppercase fw-semibold">Office / Site Location</label>
                                            <div class="fw-bold text-secondary"><i class="fa-light fa-location-dot me-1"></i> <?= htmlspecialchars($adj['office']) ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Approval & Audit Lifecycle Timeline -->
                        <div class="card mb-4 border shadow-sm">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 fw-bold"><i class="fa-light fa-timeline-arrow me-2 text-primary"></i>Approval & Verification Lifecycle</h6>
                            </div>
                            <div class="card-body">
                                <div class="timeline-steps">
                                    <!-- Step 1: Requester Submission -->
                                    <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
                                        <div class="me-3 text-center" style="width: 40px;">
                                            <span class="badge rounded-circle bg-success p-2"><i class="fa-light fa-check text-white"></i></span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0 fw-bold">1. Requisition Submitted</h6>
                                                <small class="text-muted"><?= date('d M Y, h:i A', strtotime($adj['created_at'])) ?></small>
                                            </div>
                                            <div class="small text-muted mt-1">
                                                Submitted by <strong><?= htmlspecialchars($adj['requester_name']) ?></strong> (<?= htmlspecialchars($adj['requester_role'] ?? 'Requester') ?>)
                                                — <?= $isReturn ? 'Return amount' : 'Extra requested' ?>: <strong>TK. <?= number_format($adj['amount'], 2) ?></strong>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($isExtra): ?>
                                        <!-- Step 2: QC & Internal Audit Review -->
                                        <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
                                            <div class="me-3 text-center" style="width: 40px;">
                                                <?php if (in_array($adj['status'], ['qc_approved', 'approved', 'partially_paid', 'fully_paid'])): ?>
                                                    <span class="badge rounded-circle bg-success p-2"><i class="fa-light fa-check text-white"></i></span>
                                                <?php elseif ($adj['status'] == 'rejected' && empty($adj['approved_by'])): ?>
                                                    <span class="badge rounded-circle bg-danger p-2"><i class="fa-light fa-times text-white"></i></span>
                                                <?php else: ?>
                                                    <span class="badge rounded-circle bg-warning p-2"><i class="fa-light fa-hourglass-half text-dark"></i></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h6 class="mb-0 fw-bold">2. QC & Internal Audit Review</h6>
                                                    <?php if (!empty($adj['qc_approved_at'])): ?>
                                                        <small class="text-muted"><?= date('d M Y, h:i A', strtotime($adj['qc_approved_at'])) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (in_array($adj['status'], ['qc_approved', 'approved', 'partially_paid', 'fully_paid'])): ?>
                                                    <div class="small text-success fw-semibold mt-1">
                                                        <i class="fa-light fa-badge-check me-1"></i> Audited & Approved by <strong><?= htmlspecialchars($adj['qc_name'] ?? 'QC Officer') ?></strong> (<?= htmlspecialchars($adj['qc_role'] ?? 'QC / Internal Audit') ?>)
                                                    </div>
                                                    <?php if (!empty($adj['qc_note'])): ?>
                                                        <div class="small p-2 bg-light rounded border mt-2">
                                                            <strong>QC Audit Note:</strong> <?= nl2br(htmlspecialchars($adj['qc_note'])) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php elseif ($adj['status'] == 'rejected' && empty($adj['approved_by'])): ?>
                                                    <div class="small text-danger fw-semibold mt-1">
                                                        <i class="fa-light fa-circle-xmark me-1"></i> Rejected during QC & Internal Audit inspection.
                                                    </div>
                                                <?php else: ?>
                                                    <div class="small text-warning-emphasis mt-1">
                                                        <i class="fa-light fa-clock me-1"></i> Pending review by QC & Internal Audit (Role 1, 3, 13, 24, 25).
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Step 3: Management Final Approval -->
                                        <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
                                            <div class="me-3 text-center" style="width: 40px;">
                                                <?php if (in_array($adj['status'], ['approved', 'partially_paid', 'fully_paid'])): ?>
                                                    <span class="badge rounded-circle bg-success p-2"><i class="fa-light fa-check text-white"></i></span>
                                                <?php elseif ($adj['status'] == 'rejected' && !empty($adj['qc_approved_by'])): ?>
                                                    <span class="badge rounded-circle bg-danger p-2"><i class="fa-light fa-times text-white"></i></span>
                                                <?php else: ?>
                                                    <span class="badge rounded-circle bg-secondary p-2"><i class="fa-light fa-clock text-white"></i></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h6 class="mb-0 fw-bold">3. Management / Role 20 Final Approval</h6>
                                                    <?php if (!empty($adj['approved_at'])): ?>
                                                        <small class="text-muted"><?= date('d M Y, h:i A', strtotime($adj['approved_at'])) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (in_array($adj['status'], ['approved', 'partially_paid', 'fully_paid'])): ?>
                                                    <div class="small text-success fw-semibold mt-1">
                                                        <i class="fa-light fa-badge-check me-1"></i> Approved by <strong><?= htmlspecialchars($adj['approver_name'] ?? 'Management') ?></strong> (<?= htmlspecialchars($adj['approver_role'] ?? 'Management / Role 20') ?>)
                                                        — Final Amount: <strong>TK. <?= number_format($adj['approved_amount'], 2) ?></strong>
                                                    </div>
                                                    <?php if (!empty($adj['mgmt_note'])): ?>
                                                        <div class="small p-2 bg-light rounded border mt-2">
                                                            <strong>Management Note:</strong> <?= nl2br(htmlspecialchars($adj['mgmt_note'])) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php elseif ($adj['status'] == 'rejected' && !empty($adj['qc_approved_by'])): ?>
                                                    <div class="small text-danger fw-semibold mt-1">
                                                        <i class="fa-light fa-circle-xmark me-1"></i> Rejected by Management.
                                                    </div>
                                                <?php else: ?>
                                                    <div class="small text-muted mt-1">
                                                        <i class="fa-light fa-clock me-1"></i> Pending Management approval (Role 1, 3, 20, 22, 32, 33) following QC verification.
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <!-- Return Flow Note -->
                                        <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
                                            <div class="me-3 text-center" style="width: 40px;">
                                                <span class="badge rounded-circle bg-info p-2"><i class="fa-light fa-route text-white"></i></span>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0 fw-bold">2. Direct Routing to Accounts</h6>
                                                <div class="small text-muted mt-1">
                                                    Return of unused funds directly routes to Accounts for physical/bank verification & Cash-In recording. Bypasses QC & Management.
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Final Step: Accounts Verification & Cash Flow Settlement -->
                                    <div class="d-flex align-items-start">
                                        <div class="me-3 text-center" style="width: 40px;">
                                            <?php if ($adj['status'] == 'fully_paid'): ?>
                                                <span class="badge rounded-circle bg-success p-2"><i class="fa-light fa-check-double text-white"></i></span>
                                            <?php elseif ($adj['status'] == 'partially_paid'): ?>
                                                <span class="badge rounded-circle bg-info p-2"><i class="fa-light fa-pie-chart text-white"></i></span>
                                            <?php else: ?>
                                                <span class="badge rounded-circle bg-secondary p-2"><i class="fa-light fa-wallet text-white"></i></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0 fw-bold">4. Accounts Settlement & Voucher Entry</h6>
                                                <?php if ($adj['status'] == 'fully_paid' && !empty($transactions)): ?>
                                                    <small class="text-success fw-bold"><i class="fa-light fa-circle-check me-1"></i> Fully Settled</small>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($adj['status'] == 'fully_paid'): ?>
                                                <div class="small text-success fw-semibold mt-1">
                                                    <?= $isReturn ? 'Funds verified and entered into Cash-In.' : 'Payment disbursed and completed.' ?>
                                                    Recorded in General Ledger & Debit Voucher created.
                                                </div>
                                            <?php elseif ($adj['status'] == 'partially_paid'): ?>
                                                <div class="small text-info fw-semibold mt-1">
                                                    Partially settled. Remaining due balance: <strong>TK. <?= number_format($adj['due_amount'], 2) ?></strong>
                                                </div>
                                            <?php else: ?>
                                                <div class="small text-muted mt-1">
                                                    <?= $isReturn ? 'Awaiting Accounts officer verification to accept cash into Cash-In.' : 'Awaiting Accounts payment disbursement.' ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Transactions Table -->
                        <?php if (!empty($transactions)): ?>
                        <div class="card mb-4 border shadow-sm">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold"><i class="fa-light fa-receipt me-2 text-success"></i>Settlement Transactions & Receipts</h6>
                                <a href="<?= $GLOBALS['base_url'] ?>/print-debit-voucher?module=expense_adjustment&id=<?= $adj['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fa-light fa-print me-1"></i> Print Voucher
                                </a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-3">Txn Date</th>
                                                <th>Type</th>
                                                <th>Amount</th>
                                                <th>Method & Ref</th>
                                                <th>Proof / Receipt</th>
                                                <th>Recorded By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($transactions as $txn): ?>
                                                <tr>
                                                    <td class="ps-3"><?= date('d M Y', strtotime($txn['payment_date'])) ?></td>
                                                    <td>
                                                        <?php if ($txn['transaction_type'] == 'cashin'): ?>
                                                            <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                                                                <i class="fa-light fa-arrow-down-left me-1"></i> Cash-In
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1">
                                                                <i class="fa-light fa-arrow-up-right me-1"></i> Cash-Out
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="fw-bold <?= $txn['transaction_type'] == 'cashin' ? 'text-success' : 'text-danger' ?>">
                                                        TK. <?= number_format($txn['amount'], 2) ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-dark border"><?= ucfirst($txn['payment_method']) ?></span>
                                                        <?php if(!empty($txn['transaction_ref'])): ?>
                                                            <div class="small text-muted mt-1">Ref: <?= htmlspecialchars($txn['transaction_ref']) ?></div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($txn['disbursement_proof'])): ?>
                                                            <?php $proofs = json_decode($txn['disbursement_proof'], true) ?: []; ?>
                                                            <div class="d-flex gap-1 flex-wrap">
                                                                <?php foreach ($proofs as $pIdx => $pPath): ?>
                                                                    <a href="<?= $GLOBALS['base_url'] ?>/<?= $pPath ?>" target="_blank" class="btn btn-xs btn-outline-secondary p-1" title="View Document">
                                                                        <i class="fa-light fa-paperclip"></i> Doc <?= $pIdx+1 ?>
                                                                    </a>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="fw-semibold"><?= htmlspecialchars($txn['added_by_name'] ?? 'System') ?></div>
                                                        <small class="text-muted"><?= htmlspecialchars($txn['added_by_role'] ?? '') ?></small>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Right Column: Action Hub -->
                    <div class="col-lg-4">
                        <div class="card border shadow-sm sticky-top" style="top: 20px;">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 fw-bold"><i class="fa-light fa-sliders me-2 text-primary"></i>Action & Settlement Hub</h6>
                            </div>
                            <div class="card-body">
                                <?php if ($isReturn): ?>
                                    <!-- ============================================== -->
                                    <!-- RETURN UNUSED MONEY FLOW                       -->
                                    <!-- ============================================== -->
                                    <?php if ($adj['status'] == 'pending'): ?>
                                        <div class="alert alert-info border mb-3">
                                            <h6 class="fw-bold mb-1"><i class="fa-light fa-arrow-turn-down-left me-1"></i> Return Unused Money</h6>
                                            <div class="small">
                                                Requester <strong><?= htmlspecialchars($adj['requester_name']) ?></strong> has declared 
                                                <strong>TK. <?= number_format($adj['amount'], 2) ?></strong> unused funds from 
                                                <?= htmlspecialchars($parentLabel) ?> (<?= $prefix ?>-<?= $adj['cashout_request_id'] ?>).
                                            </div>
                                        </div>

                                        <?php if ($isAccounts): ?>
                                            <div class="p-3 bg-light rounded border mb-3">
                                                <h6 class="fw-bold text-success mb-2"><i class="fa-light fa-shield-check me-1"></i> Accounts Verification</h6>
                                                <p class="small text-muted mb-3">
                                                    Verify that the cash has been physically received or bank deposit verified before confirming receipt into Cash-In.
                                                </p>
                                                <button type="button" class="btn btn-success w-100 fw-bold py-2 mb-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                                                    <i class="fa-light fa-check-circle me-1"></i> Verify & Receive Money (Cash-In)
                                                </button>
                                            </div>

                                            <div class="border-top pt-3">
                                                <button type="button" class="btn btn-outline-danger btn-sm w-100" data-bs-toggle="collapse" data-bs-target="#rejectReturnBox">
                                                    <i class="fa-light fa-ban me-1"></i> Reject / Dispute Return Request
                                                </button>
                                                <div class="collapse mt-2" id="rejectReturnBox">
                                                    <form action="<?= $GLOBALS['base_url'] ?>/expense-adjustments/reject" method="POST" onsubmit="return confirm('Are you sure you want to reject this return request?');">
                                                        <input type="hidden" name="id" value="<?= $adj['id'] ?>">
                                                        <div class="mb-2">
                                                            <textarea name="rejection_note" class="form-control form-control-sm" rows="2" placeholder="State reason for rejecting return..." required></textarea>
                                                        </div>
                                                        <button type="submit" class="btn btn-danger btn-sm w-100">Confirm Rejection</button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-warning text-center border mb-0" style="background:rgba(245,158,11,.08); border-color:rgba(245,158,11,.3) !important;">
                                                <i class="fa-light fa-hourglass-half fa-2x d-block mb-2 text-warning"></i>
                                                <strong>Pending Accounts Verification</strong>
                                                <div class="text-muted small mt-1">Returned money is awaiting verification and receipt confirmation by Accounts team.</div>
                                            </div>
                                        <?php endif; ?>

                                    <?php elseif (in_array($adj['status'], ['partially_paid', 'approved'])): ?>
                                        <div class="alert alert-warning text-center mb-3">
                                            <div class="mb-1"><strong>Total Return:</strong> TK. <?= number_format($adj['amount'], 2) ?></div>
                                            <div><strong>Remaining Due:</strong> <span class="text-danger fw-bold">TK. <?= number_format($effectiveDue, 2) ?></span></div>
                                        </div>

                                        <?php if ($isAccounts && $effectiveDue > 0): ?>
                                            <button type="button" class="btn btn-success w-100 fw-bold py-2 mb-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                                                <i class="fa-light fa-check-circle me-1"></i> Receive Remaining Return (Cash-In)
                                            </button>
                                        <?php endif; ?>

                                    <?php elseif ($adj['status'] == 'fully_paid'): ?>
                                        <div class="bg-success text-white rounded p-3 text-center mb-3">
                                            <div class="mb-2"><i class="fa-regular fa-check-circle fa-2x"></i></div>
                                            <div class="fw-bold fs-6 mb-1">Return Verified & Settled</div>
                                            <div class="small">TK. <?= number_format($adj['amount'], 2) ?> successfully credited to Cash-In</div>
                                        </div>
                                        <a href="<?= $GLOBALS['base_url'] ?>/print-debit-voucher?module=expense_adjustment&id=<?= $adj['id'] ?>" target="_blank" class="btn btn-outline-primary w-100">
                                            <i class="fa-light fa-print me-1"></i> Print Receipt Voucher
                                        </a>

                                    <?php elseif ($adj['status'] == 'rejected'): ?>
                                        <div class="bg-danger text-white rounded p-3 text-center mb-0">
                                            <div class="mb-2"><i class="fa-regular fa-times-circle fa-2x"></i></div>
                                            <div class="fw-bold fs-6 mb-1">Return Request Rejected</div>
                                            <?php if (!empty($adj['rejection_note'])): ?>
                                                <div class="small opacity-75"><?= htmlspecialchars($adj['rejection_note']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <!-- ============================================== -->
                                    <!-- REQUEST EXTRA MONEY FLOW                       -->
                                    <!-- ============================================== -->
                                    <?php if ($adj['status'] == 'pending'): ?>
                                        <!-- Step 1: QC & Internal Audit Review -->
                                        <?php if ($canQcApprove): ?>
                                            <div class="p-3 bg-light rounded border mb-3">
                                                <h6 class="fw-bold text-primary mb-2">
                                                    <i class="fa-light fa-magnifying-glass-check me-1"></i> QC & Internal Audit Review
                                                </h6>
                                                <p class="small text-muted mb-3">
                                                    Review this extra expense request. You can modify the recommended amount and attach audit notes before forwarding to Management.
                                                </p>
                                                <form action="<?= $GLOBALS['base_url'] ?>/expense-adjustments/qc-approve" method="POST" class="mb-3">
                                                    <input type="hidden" name="id" value="<?= $adj['id'] ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold">QC Approved Amount (TK.) <span class="text-danger">*</span></label>
                                                        <input type="number" name="qc_approved_amount" class="form-control" step="0.01" min="1" value="<?= $adj['amount'] ?>" required>
                                                        <small class="text-muted">Requested: TK. <?= number_format($adj['amount'], 2) ?></small>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold">QC / Audit Note</label>
                                                        <textarea name="qc_note" class="form-control" rows="2" placeholder="Inspection remarks, physical check findings, bill audit..."></textarea>
                                                    </div>
                                                    <button type="submit" class="btn btn-success w-100 fw-bold shadow-sm">
                                                        <i class="fa-light fa-check-circle me-1"></i> Pass QC & Send to Management
                                                    </button>
                                                </form>

                                                <div class="border-top pt-2">
                                                    <button type="button" class="btn btn-outline-danger btn-sm w-100" data-bs-toggle="collapse" data-bs-target="#rejectQcBox">
                                                        <i class="fa-light fa-times-circle me-1"></i> Reject Request
                                                    </button>
                                                    <div class="collapse mt-2" id="rejectQcBox">
                                                        <form action="<?= $GLOBALS['base_url'] ?>/expense-adjustments/reject" method="POST" onsubmit="return confirm('Are you sure you want to reject this request?');">
                                                            <input type="hidden" name="id" value="<?= $adj['id'] ?>">
                                                            <div class="mb-2">
                                                                <textarea name="rejection_note" class="form-control form-control-sm" rows="2" placeholder="State reason for rejection..." required></textarea>
                                                            </div>
                                                            <button type="submit" class="btn btn-danger btn-sm w-100">Confirm Rejection</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-warning text-center border mb-0" style="background:rgba(245,158,11,.08); border-color:rgba(245,158,11,.3) !important;">
                                                <i class="fa-light fa-magnifying-glass-chart fa-2x d-block mb-2 text-warning"></i>
                                                <strong>Pending QC & Internal Audit</strong>
                                                <div class="text-muted small mt-1">Awaiting audit review by QC / Internal Audit before Management approval.</div>
                                            </div>
                                        <?php endif; ?>

                                    <?php elseif ($adj['status'] == 'qc_approved'): ?>
                                        <!-- Step 2: Management / Role 20 Final Approval -->
                                        <div class="alert alert-primary border mb-3">
                                            <div class="d-flex align-items-center mb-1">
                                                <i class="fa-light fa-check-circle me-2 fs-5 text-primary"></i>
                                                <strong>QC Audit Passed</strong>
                                            </div>
                                            <div class="small">
                                                Audited by <strong><?= htmlspecialchars($adj['qc_name'] ?? 'QC Officer') ?></strong>.
                                                Recommended: <strong>TK. <?= number_format($adj['approved_amount'] ?: $adj['amount'], 2) ?></strong>
                                            </div>
                                            <?php if(!empty($adj['qc_note'])): ?>
                                                <div class="small mt-1 fst-italic text-muted">"<?= htmlspecialchars($adj['qc_note']) ?>"</div>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($canMgmtApprove): ?>
                                            <?php
                                            $adjAmtToCheck = (float)($adj['approved_amount'] ?: $adj['amount']);
                                            $isOver1LacAdj = ($adjAmtToCheck > 10000);
                                            $isOpHeadApprovedAdj = (!empty($ahInfo['is_op_head_approved']) || ($ahInfo['op_head_status'] ?? '') === 'approved');
                                            $isAhApprovedAdj = (!empty($ahInfo['is_accounts_head_approved']) || ($ahInfo['accounts_head_status'] ?? '') === 'approved');
                                            $isOpHeadUserAdj = (in_array((int)($_SESSION['user']['role_id'] ?? 0), [20, 32], true) || str_contains(strtolower($_SESSION['user']['role_name'] ?? ''), 'operation head'));
                                            $isMgmtUserAdj = in_array((int)($_SESSION['user']['role_id'] ?? 0), [1, 3], true);
                                            $isMgmtLockedAdj = ($isOver1LacAdj && (!$isOpHeadApprovedAdj || !$isAhApprovedAdj) && $isMgmtUserAdj && !$isOpHeadUserAdj);
                                            $mgmtLockMsgAdj = (!$isOpHeadApprovedAdj)
                                                ? 'This expense adjustment exceeds ৳10,000 and must be verified and approved by <strong>Admin &amp; Operation Head (Role 20)</strong> before Management can authorize it.'
                                                : 'Admin &amp; Operation Head has approved. Awaiting <strong>Accounts Head (Role 30)</strong> payment verification &amp; approval before Management can authorize it.';
                                            ?>
                                            <div class="p-3 bg-light rounded border mb-3">
                                                <h6 class="fw-bold text-primary mb-2">
                                                    <i class="fa-light fa-stamp me-1"></i> Management Final Approval
                                                </h6>
                                                <?php if ($isMgmtLockedAdj): ?>
                                                    <div class="alert alert-warning mb-3">
                                                        <i class="fa-solid fa-lock me-2"></i><strong>Management Approval Locked (> ৳10,000):</strong> <?= $mgmtLockMsgAdj ?>
                                                    </div>
                                                <?php else: ?>
                                                    <p class="small text-muted mb-3">
                                                        Review the QC recommendation and authorize final disbursement approval.
                                                    </p>
                                                <?php endif; ?>
                                                <form action="<?= $GLOBALS['base_url'] ?>/expense-adjustments/approve" method="POST" class="mb-3">
                                                    <input type="hidden" name="id" value="<?= $adj['id'] ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold">Final Approved Amount (TK.) <span class="text-danger">*</span></label>
                                                        <input type="number" name="approved_amount" class="form-control" step="0.01" min="1" value="<?= $adj['approved_amount'] ?: $adj['amount'] ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold">Management Note</label>
                                                        <textarea name="mgmt_note" class="form-control" rows="2" placeholder="Management approval remarks..."></textarea>
                                                    </div>
                                                    <?php if ($isMgmtLockedAdj): ?>
                                                        <button type="button" class="btn btn-secondary w-100 fw-bold shadow-sm disabled" title="Awaiting Admin & Operation Head approval">
                                                            <i class="fa-solid fa-lock me-1"></i> Locked (> ৳10,000)
                                                        </button>
                                                    <?php elseif ($isOver1LacAdj && !$isOpHeadApprovedAdj && $isOpHeadUserAdj): ?>
                                                        <button type="submit" class="btn btn-success w-100 fw-bold shadow-sm">
                                                            <i class="fa-solid fa-check-double me-1"></i> Verify &amp; Approve (Op Head)
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm">
                                                            <i class="fa-light fa-check-circle me-1"></i> Final Approve &amp; Forward to Accounts
                                                        </button>
                                                    <?php endif; ?>
                                                </form>

                                                <div class="border-top pt-2">
                                                    <button type="button" class="btn btn-outline-danger btn-sm w-100" data-bs-toggle="collapse" data-bs-target="#rejectMgmtBox">
                                                        <i class="fa-light fa-times-circle me-1"></i> Reject Request
                                                    </button>
                                                    <div class="collapse mt-2" id="rejectMgmtBox">
                                                        <form action="<?= $GLOBALS['base_url'] ?>/expense-adjustments/reject" method="POST" onsubmit="return confirm('Are you sure you want to reject this request?');">
                                                            <input type="hidden" name="id" value="<?= $adj['id'] ?>">
                                                            <div class="mb-2">
                                                                <textarea name="rejection_note" class="form-control form-control-sm" rows="2" placeholder="State reason for rejection..." required></textarea>
                                                            </div>
                                                            <button type="submit" class="btn btn-danger btn-sm w-100">Confirm Rejection</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-warning text-center border mb-0" style="background:rgba(245,158,11,.08); border-color:rgba(245,158,11,.3) !important;">
                                                <i class="fa-light fa-hourglass-half fa-2x d-block mb-2 text-warning"></i>
                                                <strong>Awaiting Management Approval</strong>
                                                <div class="text-muted small mt-1">Request has passed QC inspection. Pending final approval by Management (Role 20).</div>
                                            </div>
                                        <?php endif; ?>

                                    <?php elseif (in_array($adj['status'], ['approved', 'partially_paid'])): ?>
                                        <!-- Step 3: Accounts Payout -->
                                        <div class="alert alert-info text-center mb-3">
                                            <div class="mb-1"><strong>Approved Amount:</strong> TK. <?= number_format($adj['approved_amount'], 2) ?></div>
                                            <div><strong>Remaining Due:</strong> <span class="text-danger fw-bold">TK. <?= number_format($effectiveDue, 2) ?></span></div>
                                        </div>

                                        <div class="alert text-center border mb-3" style="background: rgba(13,110,253,.08); border-color: rgba(13,110,253,.3) !important;">
                                            <i class="fa-light fa-money-bill-transfer fa-2x d-block mb-2 text-primary"></i>
                                            <strong>Pending Payout from Accounts</strong>
                                            <div class="text-muted small mt-1">Approved by Management. Awaiting Accounts disbursement.</div>
                                        </div>

                                        <?php if ($isAccounts && $effectiveDue > 0): ?>
                                            <?php if ($isAhApproved): ?>
                                                <button type="button" class="btn btn-primary w-100 fw-bold py-2 mb-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                                                    <i class="fa-light fa-money-bill-transfer me-1"></i> Disburse Payout (Cash-Out)
                                                </button>
                                            <?php else: ?>
                                                <div class="alert alert-warning py-2 px-3 small mb-2 border-warning text-center">
                                                    <i class="fa-solid fa-lock me-1"></i>
                                                    <strong>Payment Locked</strong><br>
                                                    <span class="fs-12">Pending Accounts Head approval (<strong>Md. Mamunoor Rashid [1309]</strong>).</span>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                    <?php elseif ($adj['status'] == 'fully_paid'): ?>
                                        <div class="bg-success text-white rounded p-3 text-center mb-3">
                                            <div class="mb-2"><i class="fa-regular fa-check-circle fa-2x"></i></div>
                                            <div class="fw-bold fs-6 mb-1">Settled & Fully Disbursed</div>
                                            <div class="small">Total Disbursed: TK. <?= number_format($adj['approved_amount'], 2) ?></div>
                                        </div>
                                        <a href="<?= $GLOBALS['base_url'] ?>/print-debit-voucher?module=expense_adjustment&id=<?= $adj['id'] ?>" target="_blank" class="btn btn-outline-primary w-100">
                                            <i class="fa-light fa-print me-1"></i> Print Debit Voucher
                                        </a>

                                    <?php elseif ($adj['status'] == 'rejected'): ?>
                                        <div class="bg-danger text-white rounded p-3 text-center mb-0">
                                            <div class="mb-2"><i class="fa-regular fa-times-circle fa-2x"></i></div>
                                            <div class="fw-bold fs-6 mb-1">Request Rejected</div>
                                            <?php if (!empty($adj['rejection_note'])): ?>
                                                <div class="small opacity-75"><?= htmlspecialchars($adj['rejection_note']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                <?php endif; ?>

                                <?php if (!empty($adj['approved_by'])): ?>
                                    <div class="border-top mt-3 pt-3">
                                        <div class="small text-muted">
                                            <div><strong>Approved By:</strong> <?= htmlspecialchars($adj['approver_name'] ?? '') ?> (<?= htmlspecialchars($adj['approver_role'] ?? 'Management') ?>)</div>
                                            <div><strong>Approved At:</strong> <?= date('d M Y, h:i A', strtotime($adj['approved_at'])) ?></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Add Payment / Verification Modal -->
<?php if ($isAccounts && (
    ($isReturn && in_array($adj['status'], ['pending', 'partially_paid', 'approved'])) ||
    ($isExtra && in_array($adj['status'], ['approved', 'partially_paid']) && $isAhApproved)
)): ?>
<div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="addPaymentModalLabel">
                    <i class="fa-light <?= $isReturn ? 'fa-arrow-turn-down-left text-success' : 'fa-money-bill-transfer text-primary' ?> me-2"></i>
                    <?= $isReturn ? 'Verify & Receive Return Money (Cash-In)' : 'Record Requisition Disbursement (Cash-Out)' ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= $GLOBALS['base_url'] ?>/expense-adjustments/add-payment" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="adjustment_id" value="<?= $adj['id'] ?>">

                    <div class="alert <?= $isReturn ? 'alert-success' : 'alert-info' ?> py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><strong><?= $isReturn ? 'Return Amount to Receive:' : 'Pending Due Payout:' ?></strong></span>
                            <span class="fs-5 fw-bold">TK. <?= number_format($effectiveDue, 2) ?></span>
                        </div>
                    </div>

                    <div class="row ">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Amount (TK.) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control" step="0.01" max="<?= $effectiveDue ?>" value="<?= $effectiveDue ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Transaction Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method" id="adjPaymentMethod" class="form-select" required>
                                <option value="cash">Cash</option>
                                <option value="bank">Bank Deposit / Transfer</option>
                                <option value="cheque">Cheque</option>
                                <option value="online">Online / Mobile Banking</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Transaction / Receipt Ref</label>
                            <input type="text" name="transaction_ref" class="form-control" placeholder="Slip number, MR No, or TXN Ref">
                        </div>

                        <!-- Bank fields (shown for Bank & Cheque) -->
                        <div class="col-md-12 adj-bank-fields" style="display: none;">
                            <label class="form-label fw-semibold">Bank / Account <span class="text-danger">*</span></label>
                            <select name="bank_id" id="adjBankSelect" class="form-select">
                                <option value="">-- Select Bank Account --</option>
                                <?php foreach ($bankAccounts as $ba): ?>
                                    <option value="<?= $ba['id'] ?>">
                                        <?= htmlspecialchars($ba['account_name']) ?> (<?= htmlspecialchars($ba['account_code']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 adj-bank-fields" style="display: none;">
                            <label class="form-label fw-semibold">Account Number</label>
                            <input type="text" name="bank_account_no" class="form-control" placeholder="Enter bank account number">
                        </div>

                        <!-- Cheque Field (shown for Cheque) -->
                        <div class="col-md-6 adj-cheque-fields" style="display: none;">
                            <label class="form-label fw-semibold">Cheque Number</label>
                            <input type="text" name="cheque_no" class="form-control" placeholder="Enter cheque number">
                        </div>

                        <!-- Online Field (shown for Online) -->
                        <div class="col-md-6 adj-online-fields" style="display: none;">
                            <label class="form-label fw-semibold">MFS / Online Channel</label>
                            <select name="online_type" class="form-select">
                                <option value="">-- Select MFS --</option>
                                <option value="bkash">bKash</option>
                                <option value="nagad">Nagad</option>
                                <option value="rocket">Rocket</option>
                                <option value="upay">Upay</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Proof / Deposit Receipt Attachment</label>
                            <input type="file" name="disbursement_proof[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.pdf">
                            <small class="text-muted">Deposit slips, money receipts, or payment vouchers (JPG, PNG, PDF)</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn <?= $isReturn ? 'btn-success' : 'btn-primary' ?> fw-bold">
                        <i class="fa-light fa-check-circle me-1"></i> <?= $isReturn ? 'Confirm & Record Cash-In' : 'Confirm & Record Payout' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const methodSelect = document.getElementById('adjPaymentMethod');
    if (methodSelect) {
        function toggleMethodFields() {
            const val = methodSelect.value;
            const bankFields = document.querySelectorAll('.adj-bank-fields');
            const chequeFields = document.querySelectorAll('.adj-cheque-fields');
            const onlineFields = document.querySelectorAll('.adj-online-fields');

            bankFields.forEach(el => el.style.display = (val === 'bank' || val === 'cheque') ? '' : 'none');
            chequeFields.forEach(el => el.style.display = (val === 'cheque') ? '' : 'none');
            onlineFields.forEach(el => el.style.display = (val === 'online') ? '' : 'none');
        }

        methodSelect.addEventListener('change', toggleMethodFields);
        toggleMethodFields();
    }
});
</script>
<?php endif; ?>

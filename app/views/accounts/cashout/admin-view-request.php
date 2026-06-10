<?php
$page_title = "Admin - Cashout Request Details";
$page = 'Accounts';
$show_breadcrumb = true;

// Get user role name
$roleNames = [
    1 => 'Admin',
    2 => 'Employee',
    3 => 'Super Admin',
    4 => 'HR',
    5 => 'Sales Head',
    6 => 'Accounts',
    7 => 'Clients Experiment'
];
$userRoleName = $roleNames[$userRole ?? 1] ?? 'Admin';
?>
<style>
    
    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-approved { background: #d4edda; color: #155724; }
    .status-rejected { background: #f8d7da; color: #721c24; }
    .status-partial { background: #cce5ff; color: #004085; }
    .status-paid { background: #d1ecf1; color: #0c5460; }
    
    .timeline {
        position: relative;
        padding-left: 30px;
    }
    
    .timeline-item {
        position: relative;
        padding-bottom: 20px;
        border-left: 2px solid #e9ecef;
    }
    
    .timeline-item:last-child {
        border-left: 2px solid transparent;
    }
    
    .timeline-marker {
        position: absolute;
        left: -6px;
        top: 0;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #6c757d;
    }
    
    .timeline-content {
        padding-left: 20px;
    }
    
    .approval-card {
        border-left: 4px solid #6c5ffc;
    }
    
    .info-label {
        font-weight: 600;
        color: #6c757d;
        font-size: 0.875rem;
        margin-bottom: 5px;
    }
    
    .info-value {
        font-size: 1rem;
        color: #2c3e50;
        margin-bottom: 15px;
    }
    
    .action-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
    }
    
    .action-card .card-header {
        background: transparent;
        border-bottom: 1px solid rgba(255,255,255,0.2);
        color: white;
    }
    
    .payment-history-badge {
        font-size: 0.875rem;
        padding: 4px 10px;
    }
</style>

<div class="row">
    <!-- Header Section -->
    <div class="col-xxl-12 mb-4">
        <div class="card__wrapper">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="card__title mb-1">
                        <i class="fa-light fa-file-invoice-dollar me-2"></i>
                        Cashout Request #<?= $request['id'] ?? '' ?>
                    </h5>
                    <p class="text-muted mb-0">
                        Admin View | Your Role: <strong><?= $userRoleName ?></strong> 
                        | Requested by: <strong><?= htmlspecialchars($request['requester_name'] ?? 'N/A') ?></strong>
                    </p>
                </div>
                <div class="text-end">
                    <a href="<?= $GLOBALS['base_url'] ?>/admin-cashout-requests" class="btn btn-secondary me-2">
                        <i class="fa-light fa-arrow-left me-2"></i> Back to All Requests
                    </a>
                    <?php if ($userRole == 3): // Super Admin can delete ?>
                        <a href="<?= $GLOBALS['base_url'] ?>/delete-cashout-request?id=<?= $request['id'] ?>" 
                           class="btn btn-danger"
                           onclick="return confirm('Are you sure you want to delete this request? This action cannot be undone.')">
                            <i class="fa-light fa-trash me-2"></i> Delete Request
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="col-xxl-12 mb-3">
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fa-light fa-check-circle me-2"></i>
                <?= $_SESSION['success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                <?php unset($_SESSION['success']); ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="col-xxl-12 mb-3">
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fa-light fa-exclamation-triangle me-2"></i>
                <?= $_SESSION['error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                <?php unset($_SESSION['error']); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="col-xxl-8">
        <!-- Request Details Card -->
        <div class="card__wrapper mb-4">
            <div class="card__header d-flex justify-content-between align-items-center">
                <h6 class="card__title mb-0">
                    <i class="fa-light fa-info-circle me-2"></i>Request Information
                </h6>
                <?php
                if ($request['status'] !== 'rejected' && $request['status'] !== 'pending' && isset($request['due_amount']) && $request['due_amount'] <= 0) {
                    $request['status'] = 'fully_paid';
                }
                $statusClass = 'status-pending';
                switch($request['status']) {
                    case 'approved_by_HR':
                    case 'approved_by_Admin':
                    case 'approved_by_Super_Admin':
                        $statusClass = 'status-approved';
                        break;
                    case 'rejected':
                        $statusClass = 'status-rejected';
                        break;
                    case 'partially_paid':
                        $statusClass = 'status-partial';
                        break;
                    case 'fully_paid':
                        $statusClass = 'status-paid';
                        break;
                }
                ?>
                <span class="status-badge <?= $statusClass ?>">
                    <?= ucfirst(str_replace('_', ' ', $request['status'])) ?>
                </span>
            </div>
            <div class="card__body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-label">Requester</div>
                        <div class="info-value">
                            <strong><?= htmlspecialchars($request['requester_name'] ?? 'N/A') ?></strong>
                            <br><small class="text-muted">User ID: <?= $request['requested_by'] ?></small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="info-label">Request Date</div>
                        <div class="info-value">
                            <?= date('d M, Y', strtotime($request['created_at'])) ?>
                            <small class="text-muted"><?= date('h:i A', strtotime($request['created_at'])) ?></small>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="info-label">Purpose / Paid To</div>
                        <div class="info-value">
                            <strong><?= htmlspecialchars($request['purpose_paid_to']) ?></strong>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="info-label">Total Amount</div>
                        <div class="info-value">
                            <h4 class="text-primary mb-0">TK.<?= number_format($request['amount'], 2) ?></h4>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="info-label">Total Paid</div>
                        <div class="info-value">
                            <h4 class="text-success mb-0">TK.<?= number_format($total_paid ?? 0, 2) ?></h4>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="info-label">Due Amount</div>
                        <div class="info-value">
                            <?php if ($request['due_amount'] > 0): ?>
                                <h4 class="text-danger mb-0">TK.<?= number_format($request['due_amount'], 2) ?></h4>
                            <?php else: ?>
                                <h4 class="text-success mb-0">TK.0.00</h4>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="info-label">Payment Method</div>
                        <div class="info-value">
                            <span class="badge bg-info"><?= ucfirst($request['payment_method'] ?? 'N/A') ?></span>
                        </div>
                    </div>
                    
                    <?php if (!empty($request['transaction_ref'])): ?>
                        <div class="col-md-6">
                            <div class="info-label">Transaction Reference</div>
                            <div class="info-value"><?= htmlspecialchars($request['transaction_ref']) ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($request['description'])): ?>
                        <div class="col-md-12">
                            <div class="info-label">Description</div>
                            <div class="info-value">
                                <div class="border-start border-4 border-info ps-3">
                                    <?= nl2br(htmlspecialchars($request['description'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($request['attachment_path'])): ?>
                        <div class="col-md-12">
                            <div class="info-label">Requester's Documents</div>
                            <div class="info-value">
                                <?php
                                    $attachPaths = json_decode($request['attachment_path'], true);
                                    if (!is_array($attachPaths)) $attachPaths = [$request['attachment_path']];
                                ?>
                                <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($attachPaths as $idx => $path): ?>
                                    <?php $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION)); ?>
                                    <a href="<?= $GLOBALS['base_url'] ?>/download-cashout-attachment?id=<?= $request['id'] ?>&file=<?= $idx ?>"
                                        class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1"
                                        title="<?= htmlspecialchars(basename($path)) ?>">
                                        <?php if (in_array($ext, ['jpg','jpeg','png','gif','webp'])): ?>
                                            <i class="fa-light fa-image"></i>
                                        <?php elseif ($ext === 'pdf'): ?>
                                            <i class="fa-light fa-file-pdf"></i>
                                        <?php else: ?>
                                            <i class="fa-light fa-file"></i>
                                        <?php endif; ?>
                                        File <?= $idx + 1 ?>
                                    </a>
                                <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($request['rejection_reason'])): ?>
                        <div class="col-md-12">
                            <div class="alert alert-danger mb-0">
                                <div class="info-label text-danger">
                                    <i class="fa-light fa-exclamation-circle me-1"></i> Rejection Reason
                                </div>
                                <div class="info-value mb-0"><?= nl2br(htmlspecialchars($request['rejection_reason'])) ?></div>
                                <?php if (!empty($request['rejected_by_name'])): ?>
                                    <small class="text-muted d-block mt-2">
                                        Rejected by: <?= htmlspecialchars($request['rejected_by_name']) ?> 
                                        on <?= date('d M, Y h:i A', strtotime($request['rejected_at'])) ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Payment History Card -->
        <div class="card__wrapper">
            <div class="card__header d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="card__title mb-0">
                        <i class="fa-light fa-credit-card me-2"></i>Payment History
                    </h6>
                </div>
                <div>
                    <span class="badge bg-light text-dark payment-history-badge">
                        Total Payments: <?= count($payments ?? []) ?>
                    </span>
                    <?php if (!empty($payments) && $request['status'] != 'rejected' && $request['due_amount'] > 0): ?>
                        <button type="button" class="btn btn-sm btn-success ms-2" 
                                data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                            <i class="fa-light fa-plus me-1"></i> Add Payment
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card__body">
                <?php if (!empty($payments)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Payment Date</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Bank / Ref</th>
                                    <th>Proof</th>
                                    <th>Added By</th>
                                    <th>Added On</th>
                                    <?php if ($userRole == 3): // Super Admin ?>
                                        <th>Action</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?= date('d M, Y', strtotime($payment['payment_date'])) ?></td>
                                        <td><strong class="text-success">TK.<?= number_format($payment['amount'], 2) ?></strong></td>
                                        <td><span class="badge bg-info"><?= ucfirst($payment['payment_method']) ?></span></td>
                                        <td>
                                            <?php if (!empty($payment['bank_name'])): ?>
                                                <small class="text-muted d-block"><?= htmlspecialchars($payment['bank_name']) ?></small>
                                            <?php endif; ?>
                                            <?php if (!empty($payment['bank_account_no'])): ?>
                                                <small class="text-muted d-block">Acct: <?= htmlspecialchars($payment['bank_account_no']) ?></small>
                                            <?php endif; ?>
                                            <?php if (!empty($payment['cheque_no'])): ?>
                                                <small class="text-muted d-block">Cheque: <?= htmlspecialchars($payment['cheque_no']) ?></small>
                                            <?php endif; ?>
                                            <?php if (!empty($payment['online_type'])): ?>
                                                <small class="text-muted d-block"><?= htmlspecialchars(ucfirst($payment['online_type'])) ?></small>
                                            <?php endif; ?>
                                            <?php if (!empty($payment['transaction_ref'])): ?>
                                                <small class="text-muted d-block">Ref: <?= htmlspecialchars($payment['transaction_ref']) ?></small>
                                            <?php endif; ?>
                                            <?php if (empty($payment['bank_name']) && empty($payment['cheque_no']) && empty($payment['online_type']) && empty($payment['transaction_ref'])): ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($payment['disbursement_proof'])): ?>
                                                <?php $proofFiles = json_decode($payment['disbursement_proof'], true) ?: []; ?>
                                                <div class="d-flex flex-wrap gap-1">
                                                <?php foreach ($proofFiles as $pIdx => $pPath): ?>
                                                    <?php $pExt = strtolower(pathinfo($pPath, PATHINFO_EXTENSION)); ?>
                                                    <a href="<?= $GLOBALS['base_url'] ?>/<?= htmlspecialchars($pPath) ?>" target="_blank"
                                                        class="btn btn-xs btn-outline-success py-0 px-1"
                                                        style="font-size:11px;"
                                                        title="<?= htmlspecialchars(basename($pPath)) ?>">
                                                        <?php if (in_array($pExt, ['jpg','jpeg','png','gif','webp'])): ?>
                                                            <i class="fa-light fa-image"></i>
                                                        <?php elseif ($pExt === 'pdf'): ?>
                                                            <i class="fa-light fa-file-pdf"></i>
                                                        <?php else: ?>
                                                            <i class="fa-light fa-file"></i>
                                                        <?php endif; ?>
                                                        <?= $pIdx + 1 ?>
                                                    </a>
                                                <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($payment['created_by_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <?= date('d M, Y', strtotime($payment['created_at'])) ?><br>
                                            <small class="text-muted"><?= date('h:i A', strtotime($payment['created_at'])) ?></small>
                                        </td>
                                        <!-- // Super Admin -->
                                        <?php if ($userRole == 3):  ?>
                                            <td>
                                                <a href="<?= $GLOBALS['base_url'] ?>/delete-cashout-payment?id=<?= $payment['id'] ?>&request_id=<?= $request['id'] ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Delete this payment? This will update the due amount.')">
                                                    <i class="fa-light fa-trash"></i>
                                                </a>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <td colspan="1"><strong>Total Paid:</strong></td>
                                    <td><strong class="text-success">TK.<?= number_format($total_paid ?? 0, 2) ?></strong></td>
                                    <td colspan="<?= $userRole == 3 ? 7 : 6 ?>"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fa-light fa-credit-card fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">No payments recorded yet.</p>
                        <?php if ($request['status'] != 'rejected' && $request['due_amount'] > 0): ?>
                            <button type="button" class="btn btn-success mt-3" 
                                    data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                                <i class="fa-light fa-plus me-2"></i> Add First Payment
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar - Actions & Timeline -->
    <div class="col-xxl-4">
        <!-- Approval Actions Card -->
        <?php if ($request['status'] != 'rejected' && $request['status'] != 'fully_paid'): ?>
            <div class="card__wrapper action-card mb-4">
                <div class="card__header">
                    <h6 class="mb-0">
                        <i class="fa-light fa-gavel me-2"></i>Approval Actions
                    </h6>
                </div>
                <div class="card__body">
                    <?php
                    // Determine which actions are available based on status and role
                    $canApproveHR = ($userRole == 4 && $request['status'] == 'pending');
                    $canApproveAdmin = (in_array($userRole, [1, 3]) && $request['status'] == 'approved_by_HR');
                    $canApproveSuperAdmin = ($userRole == 3 && $request['status'] == 'approved_by_Admin');
                    $canReject = (in_array($userRole, [1, 3, 4]) && $request['status'] != 'rejected');
                    ?>
                    
                    <?php if ($canApproveHR): ?>
                        <form action="<?= $GLOBALS['base_url'] ?>/approve-cashout-hr" method="POST" class="mb-2">
                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                            <button type="submit" class="btn btn-light w-100" 
                                    onclick="return confirm('Approve this request as HR?')">
                                <i class="fa-light fa-check me-2"></i> Approve as HR
                            </button>
                        </form>
                        <div class="alert alert-info mb-2" style="background: rgba(255,255,255,0.2); border: none; color: white;">
                            <small><i class="fa-light fa-info-circle me-1"></i> As HR, you can approve this request to move it to the next level.</small>
                        </div>
                    <?php elseif ($canApproveAdmin): ?>
                        <form action="<?= $GLOBALS['base_url'] ?>/approve-cashout-admin" method="POST" class="mb-2">
                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                            <button type="submit" class="btn btn-light w-100" 
                                    onclick="return confirm('Approve this request as Admin?')">
                                <i class="fa-light fa-check me-2"></i> Approve as Admin
                            </button>
                        </form>
                        <div class="alert alert-info mb-2" style="background: rgba(255,255,255,0.2); border: none; color: white;">
                            <small><i class="fa-light fa-info-circle me-1"></i> Request was approved by HR. You can now approve it as Admin.</small>
                        </div>
                    <?php elseif ($canApproveSuperAdmin): ?>
                        <form action="<?= $GLOBALS['base_url'] ?>/approve-cashout-sadmin" method="POST" class="mb-2">
                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                            <button type="submit" class="btn btn-light w-100" 
                                    onclick="return confirm('Give final approval as Super Admin?')">
                                <i class="fa-light fa-check me-2"></i> Approve as Super Admin
                            </button>
                        </form>
                        <div class="alert alert-info mb-2" style="background: rgba(255,255,255,0.2); border: none; color: white;">
                            <small><i class="fa-light fa-info-circle me-1"></i> Request was approved by Admin. Give final Super Admin approval.</small>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0" style="background: rgba(255,255,255,0.2); border: none; color: white;">
                            <small>
                                <i class="fa-light fa-info-circle me-1"></i>
                                <?php if ($request['status'] == 'pending'): ?>
                                    Waiting for HR approval. Only HR role can approve at this stage.
                                <?php elseif ($request['status'] == 'approved_by_HR'): ?>
                                    Waiting for Admin approval. Only Admin/Super Admin can approve at this stage.
                                <?php elseif ($request['status'] == 'approved_by_Admin'): ?>
                                    Waiting for Super Admin final approval. Only Super Admin can approve at this stage.
                                <?php elseif ($request['status'] == 'approved_by_Super_Admin'): ?>
                                    Request is fully approved. No further approval needed.
                                <?php else: ?>
                                    No approval actions available for current status.
                                <?php endif; ?>
                            </small>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($canReject): ?>
                        <hr style="border-color: rgba(255,255,255,0.2);">
                        <button type="button" class="btn btn-danger w-100" 
                                data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="fa-light fa-times me-2"></i> Reject Request
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Role Permissions Info
        <div class="card__wrapper approval-card mb-4">
            <div class="card__header">
                <h6 class="mb-0">
                    <i class="fa-light fa-shield me-2"></i>Your Permissions (<?= $userRoleName ?>)
                </h6>
            </div>
            <div class="card__body">
                <div class="small">
                    <?php if ($userRole == 4): // HR ?>
                        <div class="mb-2">
                            <i class="fa-light fa-check text-success me-2"></i> View all requests
                        </div>
                        <div class="mb-2">
                            <i class="fa-light fa-check text-success me-2"></i> Approve pending requests (First level)
                        </div>
                        <div class="mb-2">
                            <i class="fa-light fa-check text-success me-2"></i> Add payments to approved requests
                        </div>
                        <div class="mb-2">
                            <i class="fa-light fa-check text-success me-2"></i> Reject requests
                        </div>
                        <div class="text-muted">
                            <i class="fa-light fa-times me-2"></i> Cannot approve at Admin/Super Admin level
                        </div>
                    <?php elseif ($userRole == 1): // Admin ?>
                        <div class="mb-2">
                            <i class="fa-light fa-check text-success me-2"></i> View all requests
                        </div>
                        <div class="mb-2">
                            <i class="fa-light fa-check text-success me-2"></i> Approve HR-approved requests (Second level)
                        </div>
                        <div class="mb-2">
                            <i class="fa-light fa-check text-success me-2"></i> Add payments to approved requests
                        </div>
                        <div class="mb-2">
                            <i class="fa-light fa-check text-success me-2"></i> Reject requests
                        </div>
                        <div class="mb-2">
                            <i class="fa-light fa-check text-success me-2"></i> Export data
                        </div>
                        <div class="text-muted">
                            <i class="fa-light fa-times me-2"></i> Cannot give Super Admin approval
                        </div>
                    <?php elseif ($userRole == 3): // Super Admin ?>
                        <div class="mb-2">
                            <i class="fa-light fa-check text-success me-2"></i> <strong>Full access to all features</strong>
                        </div>
                        <div class="mb-2">
                            <i class="fa-light fa-check text-success me-2"></i> Approve at all levels (including final approval)
                        </div>
                        <div class="mb-2">
                            <i class="fa-light fa-check text-success me-2"></i> Add and delete payments
                        </div>
                        <div class="mb-2">
                            <i class="fa-light fa-check text-success me-2"></i> Delete any request
                        </div>
                        <div class="mb-2">
                            <i class="fa-light fa-check text-success me-2"></i> Reject requests
                        </div>
                        <div class="mb-2">
                            <i class="fa-light fa-check text-success me-2"></i> Export data
                        </div>
                    <?php else: ?>
                        <div class="mb-2">
                            <i class="fa-light fa-check text-success me-2"></i> View all requests
                        </div>
                        <div class="mb-2">
                            <i class="fa-light fa-check text-success me-2"></i> Add payments
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div> -->

        <!-- Approval Timeline Card
        <div class="card__wrapper">
            <div class="card__header">
                <h6 class="mb-0">
                    <i class="fa-light fa-history me-2"></i>Approval Timeline
                </h6>
            </div>
            <div class="card__body">
                <div class="timeline">
                    <!-- Created -->
                    <div class="timeline-item">
                        <div class="timeline-marker" style="background: #6c757d;"></div>
                        <div class="timeline-content">
                            <h6 class="mb-1">Request Created</h6>
                            <p class="mb-0 text-muted small">
                                By: <?= htmlspecialchars($request['requester_name']) ?>
                            </p>
                            <p class="mb-0 text-muted small">
                                Date: <?= date('d M, Y H:i', strtotime($request['created_at'])) ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- HR Approval -->
                    <?php if (!empty($request['hr_approver_name'])): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker" style="background: #28a745;"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">
                                    <i class="fa-light fa-check-circle text-success me-1"></i>
                                    HR Approved
                                </h6>
                                <p class="mb-0 text-muted small">
                                    By: <?= htmlspecialchars($request['hr_approver_name']) ?>
                                </p>
                                <p class="mb-0 text-muted small">
                                    Date: <?= date('d M, Y H:i', strtotime($request['hr_approved_at'])) ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Admin Approval -->
                    <?php if (!empty($request['admin_approver_name'])): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker" style="background: #007bff;"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">
                                    <i class="fa-light fa-check-circle text-primary me-1"></i>
                                    Admin Approved
                                </h6>
                                <p class="mb-0 text-muted small">
                                    By: <?= htmlspecialchars($request['admin_approver_name']) ?>
                                </p>
                                <p class="mb-0 text-muted small">
                                    Date: <?= date('d M, Y H:i', strtotime($request['admin_approved_at'])) ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Super Admin Approval -->
                    <?php if (!empty($request['sadmin_approver_name'])): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker" style="background: #ffc107;"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">
                                    <i class="fa-light fa-check-circle text-warning me-1"></i>
                                    Super Admin Approved
                                </h6>
                                <p class="mb-0 text-muted small">
                                    By: <?= htmlspecialchars($request['sadmin_approver_name']) ?>
                                </p>
                                <p class="mb-0 text-muted small">
                                    Date: <?= date('d M, Y H:i', strtotime($request['sadmin_approved_at'])) ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Rejection -->
                    <?php if (!empty($request['rejected_by_name'])): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker" style="background: #dc3545;"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">
                                    <i class="fa-light fa-times-circle text-danger me-1"></i>
                                    Request Rejected
                                </h6>
                                <p class="mb-0 text-muted small">
                                    By: <?= htmlspecialchars($request['rejected_by_name']) ?>
                                </p>
                                <p class="mb-0 text-muted small">
                                    Date: <?= date('d M, Y H:i', strtotime($request['rejected_at'])) ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- <?php if (empty($request['hr_approver_name']) && empty($request['admin_approver_name']) && 
                              empty($request['sadmin_approver_name']) && empty($request['rejected_by_name'])): ?>
                        <div class="text-center text-muted py-3">
                            <i class="fa-light fa-hourglass-half fa-2x mb-2"></i>
                            <p class="mb-0 small">Waiting for approval...</p>
                        </div>
                    <?php endif; ?> -->
                </div>
            </div>
        </div> 
    </div>
</div>

<!-- Add Payment Modal -->
<?php if ($request['status'] != 'rejected' && $request['due_amount'] > 0): ?>
<div class="modal fade" id="addPaymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-light fa-plus-circle me-2"></i>Add Payment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= $GLOBALS['base_url'] ?>/add-cashout-payment" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                    
                    <div class="alert alert-info">
                        <strong>Request Amount:</strong> TK.<?= number_format($request['amount'], 2) ?><br>
                        <strong>Already Paid:</strong> TK.<?= number_format($total_paid ?? 0, 2) ?><br>
                        <strong>Due Amount:</strong> <span class="text-danger">TK.<?= number_format($request['due_amount'], 2) ?></span>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Amount (TK.): <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control" 
                                   placeholder="0.00" step="0.01" min="0.01" max="<?= $request['due_amount'] ?>"
                                   value="<?= $request['due_amount'] ?>" required>
                            <small class="text-muted">Maximum: TK.<?= number_format($request['due_amount'], 2) ?></small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Date: <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control" 
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Method: <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-control" id="coPaymentMethod" required>
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
                        <div class="col-md-12 mb-3 co-bank-fields" style="display:none;">
                            <label class="form-label">Bank / Branch: <span class="text-danger">*</span></label>
                            <select name="bank_id" id="coBankSelect" class="form-control">
                                <option value="">-- Loading banks... --</option>
                            </select>
                            <small class="text-muted">Managed in <a href="<?= $GLOBALS['base_url'] ?>/bank-registry" target="_blank">Bank Registry</a></small>
                        </div>
                        
                        <div class="col-md-6 mb-3 co-bank-fields" style="display:none;">
                            <label class="form-label">Account Number:</label>
                            <input type="text" name="bank_account_no" class="form-control" placeholder="Enter account number">
                        </div>
                        
                        <!-- Cheque Fields -->
                        <div class="col-md-6 mb-3 co-cheque-fields" style="display:none;">
                            <label class="form-label">Cheque Number:</label>
                            <input type="text" name="cheque_no" class="form-control" placeholder="Enter cheque number">
                        </div>
                        
                        <!-- Online Payment Fields -->
                        <div class="col-md-6 mb-3 co-online-fields" style="display:none;">
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
                            <input type="file" name="disbursement_proof[]" id="coProofFiles"
                                class="form-control" multiple
                                accept="image/*,application/pdf,.doc,.docx" required>
                            <div id="coFilePreview" class="d-flex flex-wrap gap-2 mt-2"></div>
                            <small class="text-danger">Upload at least one proof document (receipt, bank slip, screenshot, etc.)</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fa-light fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fa-light fa-check me-2"></i>Add Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const method    = document.getElementById('coPaymentMethod');
    const bankF     = document.querySelectorAll('.co-bank-fields');
    const chequeF   = document.querySelectorAll('.co-cheque-fields');
    const onlineF   = document.querySelectorAll('.co-online-fields');

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

    // Load Bank Registry options
    fetch('<?= $GLOBALS['base_url'] ?>/get-banks')
        .then(r => r.json())
        .then(data => {
            const sel = document.getElementById('coBankSelect');
            if (!sel) return;
            sel.innerHTML = '<option value="">-- Select Bank / Branch --</option>';
            (data.banks || []).forEach(b => {
                const opt = document.createElement('option');
                opt.value = b.id;
                opt.textContent = b.bank_name + ' — ' + b.branch_name;
                sel.appendChild(opt);
            });
        });

    // File preview
    const proofInput = document.getElementById('coProofFiles');
    const preview    = document.getElementById('coFilePreview');
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
                n.textContent = file.name.length > 9 ? file.name.substr(0,9)+'…' : file.name;
                thumb.appendChild(n);
                preview.appendChild(thumb);
            });
        });
    }
});
</script>
<?php endif; ?>

<!-- Reject Modal -->
<?php if (in_array($userRole, [1, 3, 4]) && $request['status'] != 'rejected'): ?>
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fa-light fa-exclamation-triangle me-2"></i>Reject Request
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= $GLOBALS['base_url'] ?>/reject-cashout-request" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                    
                    <div class="alert alert-warning">
                        <i class="fa-light fa-info-circle me-2"></i>
                        <strong>Warning:</strong> This action will reject the cashout request. 
                        Please provide a clear reason for rejection.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason: <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" 
                                  rows="4" placeholder="Enter detailed reason for rejection..." required></textarea>
                        <small class="text-muted">Be specific about why this request is being rejected.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fa-light fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa-light fa-ban me-2"></i>Reject Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
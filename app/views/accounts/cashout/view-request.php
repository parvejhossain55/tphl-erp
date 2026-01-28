<?php
$page_title = "Cashout Request Details";
$page = 'Cashout';
$show_breadcrumb = true;
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
</style>

<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card__header d-flex justify-content-between align-items-center mb-3">
                <h5 class="card__title">Cashout Request #<?= $request['id'] ?? '' ?></h5>
                <a href="<?= $GLOBALS['base_url'] ?>/cashout-requests" class="btn btn-secondary">
                    <i class="fa-light fa-arrow-left me-2"></i> Back to List
                </a>
            </div>
            <div class="card__body">
                <?php if (!empty($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $_SESSION['success']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <?php unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= $_SESSION['error']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <?php unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Request Details -->
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0">Request Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Requester:</label>
                                        <p class="form-control-static"><strong><?= htmlspecialchars($request['requester_name'] ?? 'N/A') ?></strong></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Request Date:</label>
                                        <p class="form-control-static"><?= date('d M, Y', strtotime($request['created_at'])) ?></p>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Purpose / Paid To:</label>
                                        <p class="form-control-static"><strong><?= htmlspecialchars($request['purpose_paid_to']) ?></strong></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Amount:</label>
                                        <p class="form-control-static"><strong class="text-primary">৳<?= number_format($request['amount'], 2) ?></strong></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Due Amount:</label>
                                        <p class="form-control-static">
                                            <?php if ($request['due_amount'] > 0): ?>
                                                <strong class="text-danger">৳<?= number_format($request['due_amount'], 2) ?></strong>
                                            <?php else: ?>
                                                <strong class="text-success">৳0.00</strong>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Status:</label>
                                        <p class="form-control-static">
                                            <?php
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
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Payment Method:</label>
                                        <p class="form-control-static"><?= ucfirst($request['payment_method'] ?? 'N/A') ?></p>
                                    </div>
                                    <?php if (!empty($request['transaction_ref'])): ?>
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Transaction Reference:</label>
                                            <p class="form-control-static"><?= htmlspecialchars($request['transaction_ref']) ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($request['description'])): ?>
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Description:</label>
                                            <p class="form-control-static"><?= nl2br(htmlspecialchars($request['description'])) ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($request['attachment_path'])): ?>
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Attachment:</label>
                                            <p class="form-control-static">
                                                <a href="<?= $GLOBALS['base_url'] ?>/download-cashout-attachment?id=<?= $request['id'] ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fa-light fa-download me-1"></i> Download File
                                                </a>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($request['rejection_reason'])): ?>
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label text-danger">Rejection Reason:</label>
                                            <p class="form-control-static"><?= nl2br(htmlspecialchars($request['rejection_reason'])) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Payment History -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Payment History</h6>
                                <?php if ($isAdmin && $request['status'] != 'rejected' && $request['due_amount'] > 0): ?>
                                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                                        <i class="fa-light fa-plus me-1"></i> Add Payment
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($payments)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Amount</th>
                                                    <th>Method</th>
                                                    <th>Reference</th>
                                                    <th>Added By</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($payments as $payment): ?>
                                                    <tr>
                                                        <td><?= date('d M, Y', strtotime($payment['payment_date'])) ?></td>
                                                        <td><strong>৳<?= number_format($payment['amount'], 2) ?></strong></td>
                                                        <td><?= ucfirst($payment['payment_method']) ?></td>
                                                        <td><?= htmlspecialchars($payment['transaction_ref'] ?? 'N/A') ?></td>
                                                        <td><?= htmlspecialchars($payment['created_by_name'] ?? 'N/A') ?></td>
                                                        <td>
                                                            <?php if ($isAdmin): ?>
                                                                <a href="#" class="btn btn-sm btn-danger" 
                                                                   onclick="return confirm('Delete this payment?')">
                                                                    <i class="fa-light fa-trash"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center mb-0">No payments recorded yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Approval Actions & Info -->
                    <div class="col-md-4">
                        <?php if ($isAdmin): ?>
                            <!-- Admin Actions -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Approval Actions</h6>
                                </div>
                                <div class="card-body">
                                    <?php if ($request['status'] == 'pending'): ?>
                                        <form action="<?= $GLOBALS['base_url'] ?>/approve-cashout-request" method="POST" class="mb-2">
                                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                            <button type="submit" class="btn btn-success w-100">
                                                <i class="fa-light fa-check me-1"></i> Approve (HR)
                                            </button>
                                        </form>
                                        
                                        <form action="<?= $GLOBALS['base_url'] ?>/reject-cashout-request" method="POST" class="mb-2">
                                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                            <div class="mb-2">
                                                <textarea name="rejection_reason" class="form-control form-control-sm" 
                                                          placeholder="Rejection reason..." rows="2" required></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-danger w-100">
                                                <i class="fa-light fa-times me-1"></i> Reject
                                            </button>
                                        </form>
                                    <?php elseif ($request['status'] == 'approved_by_HR'): ?>
                                        <form action="<?= $GLOBALS['base_url'] ?>/approve-cashout-admin" method="POST" class="mb-2">
                                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                            <button type="submit" class="btn btn-success w-100">
                                                <i class="fa-light fa-check me-1"></i> Approve (Admin)
                                            </button>
                                        </form>
                                    <?php elseif ($request['status'] == 'approved_by_Admin'): ?>
                                        <form action="<?= $GLOBALS['base_url'] ?>/approve-cashout-sadmin" method="POST" class="mb-2">
                                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                            <button type="submit" class="btn btn-success w-100">
                                                <i class="fa-light fa-check me-1"></i> Approve (Super Admin)
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <p class="text-muted text-center mb-0">No actions available</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Approval Timeline -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Approval Timeline</h6>
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    <?php if (!empty($request['hr_approver_name'])): ?>
                                        <div class="timeline-item mb-3">
                                            <div class="timeline-marker bg-success"></div>
                                            <div class="timeline-content">
                                                <h6 class="mb-1">Request Approved</h6>
                                                <p class="mb-0 text-muted small">By: <?= htmlspecialchars($request['hr_approver_name']) ?></p>
                                                <p class="mb-0 text-muted small">Date: <?= date('d M, Y H:i', strtotime($request['hr_approved_at'])) ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($request['admin_approver_name'])): ?>
                                        <div class="timeline-item mb-3">
                                            <div class="timeline-marker bg-success"></div>
                                            <div class="timeline-content">
                                                <h6 class="mb-1">Admin Approved</h6>
                                                <p class="mb-0 text-muted small">By: <?= htmlspecialchars($request['admin_approver_name']) ?></p>
                                                <p class="mb-0 text-muted small">Date: <?= date('d M, Y H:i', strtotime($request['admin_approved_at'])) ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($request['sadmin_approver_name'])): ?>
                                        <div class="timeline-item mb-3">
                                            <div class="timeline-marker bg-success"></div>
                                            <div class="timeline-content">
                                                <h6 class="mb-1">Super Admin Approved</h6>
                                                <p class="mb-0 text-muted small">By: <?= htmlspecialchars($request['sadmin_approver_name']) ?></p>
                                                <p class="mb-0 text-muted small">Date: <?= date('d M, Y H:i', strtotime($request['sadmin_approved_at'])) ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($request['rejected_by_name'])): ?>
                                        <div class="timeline-item mb-3">
                                            <div class="timeline-marker bg-danger"></div>
                                            <div class="timeline-content">
                                                <h6 class="mb-1">Rejected</h6>
                                                <p class="mb-0 text-muted small">By: <?= htmlspecialchars($request['rejected_by_name']) ?></p>
                                                <p class="mb-0 text-muted small">Date: <?= date('d M, Y H:i', strtotime($request['rejected_at'])) ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (empty($request['hr_approver_name']) && empty($request['admin_approver_name']) && 
                                              empty($request['sadmin_approver_name']) && empty($request['rejected_by_name'])): ?>
                                        <p class="text-muted text-center mb-0">No approval actions yet</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Payment Modal -->
<?php if ($isAdmin && $request['status'] != 'rejected' && $request['due_amount'] > 0): ?>
<div class="modal fade" id="addPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= $GLOBALS['base_url'] ?>/add-cashout-payment" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Amount (৳): <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" 
                               placeholder="0.00" step="0.01" min="0.01" max="<?= $request['due_amount'] ?>"
                               value="<?= $request['due_amount'] ?>" required>
                        <small class="text-muted">Max: ৳<?= number_format($request['due_amount'], 2) ?></small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Date:</label>
                        <input type="date" name="payment_date" class="form-control" 
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Method:</label>
                        <select name="payment_method" class="form-control" required>
                            <option value="cash">Cash</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="online">Online Payment</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Transaction Reference:</label>
                        <input type="text" name="transaction_ref" class="form-control" 
                               placeholder="e.g., Receipt #, Transaction ID">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
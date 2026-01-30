<?php
$page_title = "Cashout Request Details";
$page = 'Cashout';
$show_breadcrumb = true;

// Use the display_status from synced data
$displayStatus = $request['display_status'] ?? $request['status'] ?? 'pending';
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
                                            switch($displayStatus) {
                                                case 'approved':
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
                                                <?= ucfirst(str_replace('_', ' ', $displayStatus)) ?>
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
                                                    <i class="fa-light fa-download me-2"></i> Download Attachment
                                                </a>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Payment History -->
                        <?php if (!empty($payments)): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0">Payment History</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Amount</th>
                                                <th>Method</th>
                                                <th>Reference</th>
                                                <th>Recorded By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($payments as $payment): ?>
                                                <tr>
                                                    <td><?= date('d M, Y', strtotime($payment['payment_date'])) ?></td>
                                                    <td><strong>৳<?= number_format($payment['amount'], 2) ?></strong></td>
                                                    <td><?= ucfirst($payment['payment_method']) ?></td>
                                                    <td><?= htmlspecialchars($payment['transaction_ref'] ?? '-') ?></td>
                                                    <td><?= htmlspecialchars($payment['created_by_name'] ?? 'N/A') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="1" class="text-end"><strong>Total Paid:</strong></td>
                                                <td colspan="4"><strong class="text-success">৳<?= number_format($total_paid, 2) ?></strong></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Right Sidebar -->
                    <div class="col-md-4">
                        <!-- Quick Actions -->
                        <?php if ($isAdmin): ?>
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Quick Actions</h6>
                                </div>
                                <div class="card-body">
                                    <?php if ($displayStatus == 'pending'): ?>
                                        <form action="<?= $GLOBALS['base_url'] ?>/approve-cashout-request" method="POST" class="mb-2">
                                            <input type="hidden" name="id" value="<?= $request['id'] ?>">
                                            <button type="submit" class="btn btn-success w-100 mb-2">
                                                <i class="fa-light fa-check me-2"></i> Approve Request
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                            <i class="fa-light fa-times me-2"></i> Reject Request
                                        </button>
                                    <?php elseif ($displayStatus == 'approved' || $displayStatus == 'partially_paid'): ?>
                                        <?php if ($request['due_amount'] > 0): ?>
                                            <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                                                <i class="fa-light fa-plus me-2"></i> Add Payment
                                            </button>
                                        <?php else: ?>
                                            <p class="text-success text-center mb-0">Fully Paid</p>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p class="text-muted text-center mb-0">No actions available</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Approval Information -->
                        <?php if (!empty($approval_request)): ?>
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Approval Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Approval Status:</label>
                                    <p class="form-control-static">
                                        <?php
                                        $approvalStatusClass = 'status-pending';
                                        switch($approval_request['status']) {
                                            case 'approved':
                                                $approvalStatusClass = 'status-approved';
                                                break;
                                            case 'rejected':
                                                $approvalStatusClass = 'status-rejected';
                                                break;
                                        }
                                        ?>
                                        <span class="status-badge <?= $approvalStatusClass ?>">
                                            <?= ucfirst($approval_request['status']) ?>
                                        </span>
                                    </p>
                                </div>
                                
                                <?php if ($approval_request['status'] == 'approved' && !empty($request['approved_by_name'])): ?>
                                    <div class="mb-3">
                                        <label class="form-label">Approved By:</label>
                                        <p class="form-control-static"><?= htmlspecialchars($request['approved_by_name']) ?></p>
                                    </div>
                                    
                                    <?php if (!empty($approval_request['approved_at'])): ?>
                                        <div class="mb-3">
                                            <label class="form-label">Approved At:</label>
                                            <p class="form-control-static"><?= date('d M, Y H:i', strtotime($approval_request['approved_at'])) ?></p>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if ($approval_request['status'] == 'rejected' && !empty($request['rejected_by_name'])): ?>
                                    <div class="mb-3">
                                        <label class="form-label">Rejected By:</label>
                                        <p class="form-control-static"><?= htmlspecialchars($request['rejected_by_name']) ?></p>
                                    </div>
                                    
                                    <?php if (!empty($request['rejection_reason'])): ?>
                                        <div class="mb-3">
                                            <label class="form-label">Rejection Reason:</label>
                                            <p class="form-control-static"><?= nl2br(htmlspecialchars($request['rejection_reason'])) ?></p>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<?php if ($isAdmin && $displayStatus == 'pending'): ?>
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= $GLOBALS['base_url'] ?>/reject-cashout-request" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?= $request['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason: <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="3" required 
                                  placeholder="Please provide a reason for rejection..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Add Payment Modal -->
<?php if ($isAdmin && ($displayStatus == 'approved' || $displayStatus == 'partially_paid') && $request['due_amount'] > 0): ?>
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
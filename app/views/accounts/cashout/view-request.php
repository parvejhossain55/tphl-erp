<?php
$page_title = "Cashout Request Details";
$page = 'Cashout';
$show_breadcrumb = true;

// Use the display_status from synced data
$displayStatus = $request['display_status'] ?? $request['status'] ?? 'pending';

// Check if already approved from homepage approval system
$isAlreadyApproved = !empty($request['approval_status']) && in_array($request['approval_status'], ['approved', 'partial']);
$isApprovalPending = !empty($request['approval_status']) && $request['approval_status'] === 'pending';
?>
<style>
    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-approved {
        background: #d4edda;
        color: #155724;
    }

    .status-rejected {
        background: #f8d7da;
        color: #721c24;
    }

    .status-partial {
        background: #cce5ff;
        color: #004085;
    }

    .status-paid {
        background: #d1ecf1;
        color: #0c5460;
    }
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
                                        <p class="form-control-static">
                                            <strong><?= htmlspecialchars($request['requester_name'] ?? 'N/A') ?></strong>
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Request Date:</label>
                                        <p class="form-control-static">
                                            <?= date('d M, Y', strtotime($request['created_at'])) ?></p>
                                    </div>

                                    <!-- NEW: Project Information -->
                                    <?php if (!empty($request['project_name'])): ?>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Project:</label>
                                            <p class="form-control-static">
                                                <strong><?= htmlspecialchars($request['project_name']) ?></strong>
                                            </p>
                                        </div>
                                    <?php endif; ?>

                                    <!-- NEW: Office Information -->
                                    <?php if (!empty($request['office'])): ?>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Office:</label>
                                            <p class="form-control-static">
                                                <strong><?= htmlspecialchars($request['office']) ?></strong>
                                            </p>
                                        </div>
                                    <?php endif; ?>

                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Purpose / Paid To:</label>
                                        <p class="form-control-static">
                                            <strong><?= htmlspecialchars($request['purpose_paid_to']) ?></strong></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Requested Amount:</label>
                                        <p class="form-control-static"><strong
                                                class="text-primary">TK.<?= number_format($request['amount'], 2) ?></strong>
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Approved Amount:</label>
                                        <p class="form-control-static">
                                            <?php if (!empty($request['approved_amount'])): ?>
                                                <strong
                                                    class="text-success">TK.<?= number_format($request['approved_amount'], 2) ?></strong>
                                                <?php if (!empty($request['approval_note'])): ?>
                                                    <br><small class="text-muted">Note:
                                                        <?= htmlspecialchars($request['approval_note']) ?></small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Due Amount:</label>
                                        <p class="form-control-static">
                                            <?php if ($request['due_amount'] > 0): ?>
                                                <strong
                                                    class="text-danger">TK.<?= number_format($request['due_amount'], 2) ?></strong>
                                            <?php else: ?>
                                                <strong class="text-success">TK.0.00</strong>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Status:</label>
                                        <p class="form-control-static">
                                            <?php
                                            $statusClass = 'status-pending';
                                            switch ($displayStatus) {
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
                                                case 'partial':
                                                    $statusClass = 'status-partial';
                                                    break;
                                            }
                                            ?>
                                            <span class="status-badge <?= $statusClass ?>">
                                                <?= ucfirst(str_replace('_', ' ', $displayStatus)) ?>
                                            </span>
                                            <?php if ($isAlreadyApproved && $request['approval_status'] === 'partial'): ?>
                                                <br><small class="text-muted">(Partially Approved)</small>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Payment Method:</label>
                                        <p class="form-control-static">
                                            <?= ucfirst($request['payment_method'] ?? 'N/A') ?></p>
                                    </div>
                                    <?php if (!empty($request['transaction_ref'])): ?>
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Transaction Reference:</label>
                                            <p class="form-control-static">
                                                <?= htmlspecialchars($request['transaction_ref']) ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($request['description'])): ?>
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Description:</label>
                                            <p class="form-control-static">
                                                <?= nl2br(htmlspecialchars($request['description'])) ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($request['attachment_path'])): ?>
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Attachments:</label>
                                            <div class="form-control-static">
                                                <?php
                                                // Support both legacy single string and new JSON array
                                                $attachments = json_decode($request['attachment_path'], true);
                                                if (!is_array($attachments)) {
                                                    $attachments = [$request['attachment_path']];
                                                }
                                                ?>
                                                <?php foreach ($attachments as $index => $filePath): ?>
                                                    <?php
                                                    $fileName = basename($filePath);
                                                    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                                                    $icon = in_array($ext, ['jpg', 'jpeg', 'png']) ? 'fa-image'
                                                        : ($ext === 'pdf' ? 'fa-file-pdf'
                                                            : ($ext === 'doc' || $ext === 'docx' ? 'fa-file-word' : 'fa-file'));
                                                    $iconColor = in_array($ext, ['jpg', 'jpeg', 'png']) ? 'text-success'
                                                        : ($ext === 'pdf' ? 'text-danger' : 'text-primary');
                                                    ?>
                                                    <div class="d-flex align-items-center gap-2 mb-2">
                                                        <i class="fa-light <?= $icon ?> <?= $iconColor ?> fa-lg"></i>
                                                        <a href="<?= $GLOBALS['base_url'] ?>/download-cashout-attachment?id=<?= $request['id'] ?>&file=<?= $index ?>"
                                                            class="btn btn-sm btn-outline-primary">
                                                            <i class="fa-light fa-download me-1"></i>
                                                            <?= htmlspecialchars($fileName) ?>
                                                        </a>
                                                        <span
                                                            class="badge bg-light text-muted border"><?= strtoupper($ext) ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                                <?php if (count($attachments) > 1): ?>
                                                    <small class="text-muted">
                                                        <i class="fa-light fa-paperclip me-1"></i>
                                                        <?= count($attachments) ?> file(s) attached
                                                    </small>
                                                <?php endif; ?>
                                            </div>
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
                                                    <!-- NEW: Project & Office in Payment History -->
                                                    <?php if (!empty($payments[0]['project_name']) || !empty($payments[0]['office'])): ?>
                                                        <th>Project/Office</th>
                                                    <?php endif; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($payments as $payment): ?>
                                                    <tr>
                                                        <td><?= date('d M, Y', strtotime($payment['payment_date'])) ?></td>
                                                        <td><strong>TK.<?= number_format($payment['amount'], 2) ?></strong></td>
                                                        <td><?= ucfirst($payment['payment_method']) ?></td>
                                                        <td><?= htmlspecialchars($payment['transaction_ref'] ?? '-') ?></td>
                                                        <td><?= htmlspecialchars($payment['created_by_name'] ?? 'N/A') ?></td>
                                                        <!-- NEW: Display Project/Office in payments -->
                                                        <?php if (!empty($payments[0]['project_name']) || !empty($payments[0]['office'])): ?>
                                                            <td>
                                                                <?php if (!empty($payment['project_name'])): ?>
                                                                    <small class="text-muted">Project:</small><br>
                                                                    <span
                                                                        class="text-info"><?= htmlspecialchars($payment['project_name']) ?></span>
                                                                <?php elseif (!empty($payment['office'])): ?>
                                                                    <small class="text-muted">Office:</small><br>
                                                                    <span
                                                                        class="text-info"><?= htmlspecialchars($payment['office']) ?></span>
                                                                <?php else: ?>
                                                                    <span class="text-muted">-</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        <?php endif; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <?php $colspan = !empty($payments[0]['project_name']) || !empty($payments[0]['office']) ? 6 : 5; ?>
                                                    <td colspan="<?= $colspan - 1 ?>" class="text-end"><strong>Total
                                                            Paid:</strong></td>
                                                    <td colspan="1"><strong
                                                            class="text-success">TK.<?= number_format($total_paid, 2) ?></strong>
                                                    </td>
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
                                    <?php
                                    // Determine what actions to show
                                    $showApproveReject = $displayStatus == 'pending' && !$isAlreadyApproved;
                                    $showAddPayment = ($displayStatus == 'approved' || $displayStatus == 'partial' || $displayStatus == 'partially_paid') && $request['due_amount'] > 0;
                                    $showFullyPaid = $request['due_amount'] <= 0;
                                    ?>

                                    <?php if ($showApproveReject && $canApproveOrReject): ?>
                                        <!-- Show approve/reject buttons only if pending and not already approved from homepage -->
                                        <form action="<?= $GLOBALS['base_url'] ?>/approve-cashout-request" method="POST"
                                            class="mb-2">
                                            <input type="hidden" name="id" value="<?= $request['id'] ?>">
                                            <button type="submit" class="btn btn-success w-100 mb-2">
                                                <i class="fa-light fa-check me-2"></i> Approve Request
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal"
                                            data-bs-target="#rejectModal">
                                            <i class="fa-light fa-times me-2"></i> Reject Request
                                        </button>
                                    <?php elseif ($showAddPayment): ?>
                                        <!-- Show add payment button if approved and has due amount -->
                                        <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal"
                                            data-bs-target="#addPaymentModal">
                                            <i class="fa-light fa-plus me-2"></i> Add Payment
                                        </button>
                                        <?php if (!empty($request['approved_amount'])): ?>
                                            <p class="text-info text-center mt-2 mb-0 small">
                                                <i class="fa-light fa-info-circle me-1"></i>
                                                Approved: TK.<?= number_format($request['approved_amount'], 2) ?>
                                            </p>
                                        <?php endif; ?>
                                    <?php elseif ($showFullyPaid): ?>
                                        <p class="text-success text-center mb-0">
                                            <i class="fa-light fa-check-circle me-1"></i> Fully Paid
                                        </p>
                                    <?php else: ?>
                                        <p class="text-muted text-center mb-0">
                                            <?php if ($isAlreadyApproved): ?>
                                                <i class="fa-light fa-check-circle me-1"></i> Already approved from approval system
                                            <?php else: ?>
                                                No actions available
                                            <?php endif; ?>
                                        </p>
                                    <?php endif; ?>

                                    <!-- NEW: Project/Office Info Box -->
                                    <?php if (!empty($request['project_name']) || !empty($request['office'])): ?>
                                        <div class="mt-3 pt-3 border-top">
                                            <p class="mb-1 small">
                                                <?php if (!empty($request['project_name'])): ?>
                                                    <i class="fa-light fa-building me-1 text-primary"></i>
                                                    <strong>Project:</strong> <?= htmlspecialchars($request['project_name']) ?><br>
                                                <?php endif; ?>
                                                <?php if (!empty($request['office'])): ?>
                                                    <i class="fa-light fa-building-columns me-1 text-info"></i>
                                                    <strong>Office:</strong> <?= htmlspecialchars($request['office']) ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
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
                                            switch ($approval_request['status']) {
                                                case 'approved':
                                                    $approvalStatusClass = 'status-approved';
                                                    break;
                                                case 'partial':
                                                    $approvalStatusClass = 'status-partial';
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

                                    <?php if (!empty($approval_request['approved_amount'])): ?>
                                        <div class="mb-3">
                                            <label class="form-label">Approved Amount:</label>
                                            <p class="form-control-static">
                                                <strong
                                                    class="text-success">TK.<?= number_format($approval_request['approved_amount'], 2) ?></strong>
                                            </p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($approval_request['approval_note'])): ?>
                                        <div class="mb-3">
                                            <label class="form-label">Approval Note:</label>
                                            <p class="form-control-static">
                                                <?= nl2br(htmlspecialchars($approval_request['approval_note'])) ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <!-- NEW: Include project/office in approval info if available -->
                                    <?php if (!empty($request['project_name']) || !empty($request['office'])): ?>
                                        <div class="mb-3 pt-2 border-top">
                                            <label class="form-label">Associated With:</label>
                                            <p class="form-control-static">
                                                <?php if (!empty($request['project_name'])): ?>
                                                    <span class="text-primary">
                                                        <i class="fa-light fa-building me-1"></i>
                                                        <?= htmlspecialchars($request['project_name']) ?>
                                                    </span>
                                                    <br>
                                                <?php endif; ?>
                                                <?php if (!empty($request['office'])): ?>
                                                    <span class="text-info">
                                                        <i class="fa-light fa-building-columns me-1"></i>
                                                        <?= htmlspecialchars($request['office']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (in_array($approval_request['status'], ['approved', 'partial', 'rejected']) && !empty($request['approved_by_name'])): ?>
                                        <div class="mb-3">
                                            <label class="form-label">Approved By:</label>
                                            <p class="form-control-static"><?= htmlspecialchars($request['approved_by_name']) ?>
                                            </p>
                                        </div>

                                        <?php if (!empty($approval_request['approved_at'])): ?>
                                            <div class="mb-3">
                                                <label class="form-label">Approved At:</label>
                                                <p class="form-control-static">
                                                    <?= date('d M, Y H:i', strtotime($approval_request['approved_at'])) ?></p>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php if ($approval_request['status'] == 'rejected' && !empty($request['rejected_by_name'])): ?>
                                        <div class="mb-3">
                                            <label class="form-label">Rejected By:</label>
                                            <p class="form-control-static"><?= htmlspecialchars($request['rejected_by_name']) ?>
                                            </p>
                                        </div>

                                        <?php if (!empty($request['rejection_reason'])): ?>
                                            <div class="mb-3">
                                                <label class="form-label">Rejection Reason:</label>
                                                <p class="form-control-static">
                                                    <?= nl2br(htmlspecialchars($request['rejection_reason'])) ?></p>
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
<?php if ($isAdmin && $canApproveOrReject && $displayStatus == 'pending' && !$isAlreadyApproved): ?>
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
<?php if ($isAdmin && ($displayStatus == 'approved' || $displayStatus == 'partial' || $displayStatus == 'partially_paid') && $request['due_amount'] > 0): ?>
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
                            <label class="form-label">Amount (TK.): <span class="text-danger">*</span></label>
                            <?php
                            // Determine max amount - use approved amount if available, otherwise use due amount
                            $maxAmount = !empty($request['approved_amount']) ?
                                min($request['approved_amount'], $request['due_amount']) :
                                $request['due_amount'];
                            ?>
                            <input type="number" name="amount" class="form-control" placeholder="0.00" step="0.01"
                                min="0.01" max="<?= $maxAmount ?>" value="<?= $maxAmount ?>" required>
                            <small class="text-muted">
                                Max: TK.<?= number_format($maxAmount, 2) ?>
                                <?php if (!empty($request['approved_amount'])): ?>
                                    <br>Approved Amount: TK.<?= number_format($request['approved_amount'], 2) ?>
                                <?php endif; ?>
                            </small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Payment Date:</label>
                            <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>"
                                required>
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
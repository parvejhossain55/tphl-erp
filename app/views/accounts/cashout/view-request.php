<?php
$page_title = "Cashout Request Details";
$page = 'Cashout';
$show_breadcrumb = true;

// Use the display_status from synced data
$displayStatus = $request['display_status'] ?? $request['status'] ?? 'pending';

if ($displayStatus !== 'rejected' && $displayStatus !== 'pending' && isset($request['due_amount']) && $request['due_amount'] <= 0) {
    $displayStatus = 'fully_paid';
}


// Check if already approved from homepage approval system
$isAlreadyApproved = !empty($request['approval_status']) && in_array($request['approval_status'], ['approved', 'partial']);
$isApprovalPending = !empty($request['approval_status']) && $request['approval_status'] === 'pending';

if (!class_exists('ApprovalRequest')) {
    require_once __DIR__ . '/../../../models/ApprovalRequest.php';
}
$ahInfo = ApprovalRequest::getAccountsHeadApprovalInfo($GLOBALS['db'] ?? ($this->db ?? null), 'cashout_requests', (int)$request['id']);
$isAhApproved = empty($ahInfo['has_request']) || !empty($ahInfo['is_accounts_head_approved']);
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

    .status-qc_approved {
        background: #e2e3e5;
        color: #383d41;
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
                <a href="<?= $GLOBALS['base_url'] ?><?= isset($_GET['redirect']) && $_GET['redirect'] === 'client-refunds' ? '/client-refunds' : '/cashout-requests' ?>" class="btn btn-secondary">
                    <i class="fa-light fa-arrow-left me-2"></i> Back
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

                <?php 
                if (!empty($ahInfo['has_request'])) {
                    $sourceTable = 'cashout_requests';
                    $sourceId = (int)$request['id'];
                    include __DIR__ . '/../../layouts/accounts_head_approval_card.php';
                }
                ?>

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
                                                case 'qc_approved':
                                                    $statusClass = 'status-qc_approved';
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
                                                    <th>Bank / Ref</th>
                                                    <th>Proof</th>
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
                                                        <!-- NEW: Display Project/Office in payments -->
                                                        <?php if (!empty($payments[0]['project_name']) || !empty($payments[0]['office'])): ?>
                                                            <td>
                                                                <?php if (!empty($payment['project_name'])): ?>
                                                                    <small class="text-muted">Project:</small><br>
                                                                    <span class="text-info"><?= htmlspecialchars($payment['project_name']) ?></span>
                                                                <?php elseif (!empty($payment['office'])): ?>
                                                                    <small class="text-muted">Office:</small><br>
                                                                    <span class="text-info"><?= htmlspecialchars($payment['office']) ?></span>
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
                                                    <?php
                                                        $hasExtra = !empty($payments[0]['project_name']) || !empty($payments[0]['office']);
                                                        $colspan = $hasExtra ? 7 : 6;
                                                    ?>
                                                    <td colspan="<?= $colspan - 1 ?>" class="text-end"><strong>Total Paid:</strong></td>
                                                    <td colspan="1"><strong class="text-success">TK.<?= number_format($total_paid, 2) ?></strong></td>
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
                        <!-- Print Debit Voucher -->
                        <?php if (!empty($payments)): ?>
                            <div class="card mb-4 border-primary">
                                <div class="card-body text-center">
                                    <a href="<?= $GLOBALS['base_url'] ?>/print-debit-voucher?module=cashout&id=<?= $request['id'] ?>" target="_blank" class="btn btn-primary w-100">
                                        <i class="bi bi-printer"></i> Print Debit Voucher
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Quick Actions -->
                        <?php if ($isAdmin || $canApproveQC): ?>
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Quick Actions</h6>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Determine what actions to show
                                    $isPending = ($displayStatus === 'pending');
                                    $isQCApproved = ($displayStatus === 'qc_approved');
                                    
                                    $showAddPayment = ($displayStatus == 'approved' || $displayStatus == 'partial' || $displayStatus == 'partially_paid') && $request['due_amount'] > 0 && $isAhApproved;
                                    $isWaitingAccountsHead = ($displayStatus == 'approved' || $displayStatus == 'partial' || $displayStatus == 'partially_paid') && $request['due_amount'] > 0 && !$isAhApproved;
                                    $showFullyPaid = $request['due_amount'] <= 0;
                                    ?>

                                    <?php 
                                    $userRoleId = (int) ($userRole ?? 0);
                                    $sessionRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));
                                    $isChiefOfficer = ($userRoleId === 33 || $sessionRoleName === 'chief officer');
                                    $isFullManagement = (in_array($userRoleId, [1, 3], true) || in_array($sessionRoleName, ['super admin', 'admin', 'management'], true)) && !$isChiefOfficer;
                                    $isSiteOperatorUser = (in_array($userRoleId, [20, 32], true) || strpos($sessionRoleName, 'operation head') !== false || strpos($sessionRoleName, 'admin & operation') !== false) && !$isChiefOfficer;

                                    $reqAmount = (float)($request['amount'] ?? 0);
                                    $isOver1Lac = ($reqAmount > 10000.01);
                                    $isOpHeadApproved = (!empty($approvalRequest['op_head_status']) && strtolower($approvalRequest['op_head_status']) === 'approved');
                                    $isAhApproved = (!empty($approvalRequest['accounts_head_status']) && strtolower($approvalRequest['accounts_head_status']) === 'approved');
                                    $canDirectApprove = !$isChiefOfficer && (
                                        (!$isOver1Lac && ($isFullManagement || $isSiteOperatorUser)) ||
                                        ($isOver1Lac && $isFullManagement && $isOpHeadApproved && $isAhApproved)
                                    );
                                    $isQCOnlyUser = $canApproveQC && !$isFullManagement && !$isSiteOperatorUser && !$isChiefOfficer;
                                    ?>

                                    <?php if ($isPending && $isOver1Lac && !$isOpHeadApproved && $isSiteOperatorUser && !$isAlreadyApproved): ?>
                                        <!-- Operation Head Approval Step for > 10K -->
                                        <form action="<?= $GLOBALS['base_url'] ?>/approve-cashout-request" method="POST" class="mb-2">
                                            <input type="hidden" name="id" value="<?= $request['id'] ?>">
                                            <input type="hidden" name="status" value="approved">
                                            <div class="alert alert-info py-2 mb-3">
                                                <small><i class="fa-solid fa-layer-group me-1"></i> <strong>Step 1 of 3:</strong> As Admin &amp; Operation Head, your sign-off is required to forward this request to Accounts Head (Role 30).</small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Approval Note (Optional)</label>
                                                <textarea name="approval_note" class="form-control" rows="2" placeholder="Leave a note for management..."></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100 mb-2">
                                                <i class="fa-solid fa-stamp me-2"></i> Approve as Operation Head
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal"
                                            data-bs-target="#rejectModal">
                                            <i class="fa-light fa-times me-2"></i> Reject Request
                                        </button>

                                    <?php elseif ($isPending && $isOver1Lac && (!$isOpHeadApproved || !$isAhApproved) && $isFullManagement && !$isAlreadyApproved): ?>
                                        <!-- Threshold lock notice for Management -->
                                        <div class="alert alert-warning mb-2 py-3">
                                            <div class="fw-bold text-dark"><i class="fa-solid fa-lock text-warning me-1"></i> Management Approval Locked</div>
                                            <?php if (!$isOpHeadApproved): ?>
                                                <small class="text-secondary">Amount (৳<?= number_format($reqAmount, 2) ?>) exceeds ৳10,000 threshold. Admin &amp; Operation Head (Role 20) must approve first.</small>
                                            <?php else: ?>
                                                <small class="text-secondary">Amount (৳<?= number_format($reqAmount, 2) ?>) exceeds ৳10,000 threshold. Operation Head approved; Accounts Head (Role 30) must verify &amp; approve before Management final sign-off.</small>
                                            <?php endif; ?>
                                        </div>

                                    <?php elseif ($isPending && $canDirectApprove && !$isAlreadyApproved): ?>
                                        <!-- Management / Operation Head Direct Approval -->
                                        <form action="<?= $GLOBALS['base_url'] ?>/approve-cashout-request" method="POST" class="mb-2">
                                            <input type="hidden" name="id" value="<?= $request['id'] ?>">
                                            <input type="hidden" name="status" value="approved">
                                            <?php if ($isOver1Lac && $isOpHeadApproved): ?>
                                                <div class="alert alert-success py-2 mb-3">
                                                    <small><i class="fa-solid fa-circle-check text-success me-1"></i> Steps 1 &amp; 2 Completed: Operation Head &amp; Accounts Head Approved. Ready for Management final approval.</small>
                                                </div>
                                            <?php endif; ?>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Approved Amount (Optional)</label>
                                                <input type="number" step="0.01" name="approved_amount" class="form-control" value="<?= htmlspecialchars($request['amount']) ?>" max="<?= htmlspecialchars($request['amount']) ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Approval Note (Optional)</label>
                                                <textarea name="approval_note" class="form-control" rows="2" placeholder="Leave a note..."></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-success w-100 mb-2">
                                                <i class="fa-light fa-check me-2"></i> Approve Request
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal"
                                            data-bs-target="#rejectModal">
                                            <i class="fa-light fa-times me-2"></i> Reject Request
                                        </button>

                                    <?php elseif ($isPending && $isQCOnlyUser && !$isAlreadyApproved): ?>
                                        <!-- QC Approval (only for QC-role users, not management) -->
                                        <form action="<?= $GLOBALS['base_url'] ?>/approve-cashout-request" method="POST" class="mb-2">
                                            <input type="hidden" name="id" value="<?= $request['id'] ?>">
                                            <input type="hidden" name="status" value="qc_approved">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">QC Note (Optional)</label>
                                                <textarea name="approval_note" class="form-control" rows="2" placeholder="Leave a note..."></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-warning w-100 mb-2">
                                                <i class="fa-light fa-shield-check me-2"></i> QC Approve
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal"
                                            data-bs-target="#rejectModal">
                                            <i class="fa-light fa-times me-2"></i> Reject Request
                                        </button>

                                    <?php elseif ($isQCApproved && $canDirectApprove && !$isAlreadyApproved): ?>
                                        <!-- Management / Operation Head Approval (after QC has approved) -->
                                        <form action="<?= $GLOBALS['base_url'] ?>/approve-cashout-request" method="POST" class="mb-2">
                                            <input type="hidden" name="id" value="<?= $request['id'] ?>">
                                            <input type="hidden" name="status" value="approved">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Approved Amount (Optional)</label>
                                                <input type="number" step="0.01" name="approved_amount" class="form-control" value="<?= htmlspecialchars($request['amount']) ?>" max="<?= htmlspecialchars($request['amount']) ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Approval Note (Optional)</label>
                                                <textarea name="approval_note" class="form-control" rows="2" placeholder="Leave a note..."></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-success w-100 mb-2">
                                                <i class="fa-light fa-check me-2"></i> Approve Request
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal"
                                            data-bs-target="#rejectModal">
                                            <i class="fa-light fa-times me-2"></i> Reject Request
                                        </button>

                                    <?php elseif ($isWaitingAccountsHead): ?>
                                        <div class="alert alert-warning py-2 px-3 small mb-2 border-warning text-center">
                                            <i class="fa-solid fa-lock me-1"></i>
                                            <strong>Payment Locked</strong><br>
                                            <span class="fs-12">Pending Accounts Head approval.</span>
                                        </div>
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
                                        <label class="form-label">Management Approval:</label>
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

                                    <?php if (!empty($request['qc_approved_by_id'])): ?>
                                        <div class="mb-3">
                                            <label class="form-label">QC Approved By:</label>
                                            <p class="form-control-static fw-bold">
                                                <?= htmlspecialchars($request['qc_approved_by_name'] ?? 'Unknown') ?> 
                                                <span class="text-muted fw-normal">(ID: <?= htmlspecialchars($request['qc_approved_by_id']) ?>)</span>
                                            </p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">QC Approved At:</label>
                                            <p class="form-control-static"><?= date('d M Y h:i A', strtotime($request['qc_approved_at'])) ?></p>
                                        </div>
                                        <?php if (!empty($request['qc_note'])): ?>
                                            <div class="mb-3">
                                                <label class="form-label">QC Note:</label>
                                                <p class="form-control-static fst-italic">"<?= nl2br(htmlspecialchars($request['qc_note'])) ?>"</p>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>

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
<?php if (($isAdmin || $canApproveQC) && ($isPending || $isQCApproved) && !$isAlreadyApproved && ($canApproveOrReject || $canApproveQC)): ?>
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
<?php if ($isAdmin && ($displayStatus == 'approved' || $displayStatus == 'partial' || $displayStatus == 'partially_paid') && $request['due_amount'] > 0 && $isAhApproved): ?>
    <div class="modal fade" id="addPaymentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-light fa-plus-circle me-2"></i>Add Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="<?= $GLOBALS['base_url'] ?>/add-cashout-payment<?= isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="request_id" value="<?= $request['id'] ?>">

                        <div class="alert alert-info">
                            <strong>Request Amount:</strong> TK.<?= number_format($request['amount'], 2) ?><br>
                            <strong>Already Paid:</strong> TK.<?= number_format($total_paid ?? 0, 2) ?><br>
                            <strong>Due Amount:</strong> <span class="text-danger">TK.<?= number_format($request['due_amount'], 2) ?></span>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Amount (TK.): <span class="text-danger">*</span></label>
                                <?php
                                $maxAmount = !empty($request['approved_amount'])
                                    ? min($request['approved_amount'], $request['due_amount'])
                                    : $request['due_amount'];
                                ?>
                                <input type="number" name="amount" class="form-control" placeholder="0.00" step="0.01"
                                    min="0.01" max="<?= $maxAmount ?>" value="<?= $maxAmount ?>" required>
                                <small class="text-muted">Max: TK.<?= number_format($maxAmount, 2) ?>
                                    <?php if (!empty($request['approved_amount'])): ?>
                                        <br>Approved Amount: TK.<?= number_format($request['approved_amount'], 2) ?>
                                    <?php endif; ?>
                                </small>
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
                                <label class="form-label">Bank / Branch: <span class="text-danger">*</span></label>
                                <select name="bank_id" id="vrBankSelect" class="form-control">
                                    <option value="">-- Loading company banks... --</option>
                                </select>
                                <small class="text-muted">Managed in <a href="<?= $GLOBALS['base_url'] ?>/company-bank-accounts" target="_blank">Company Bank Manage</a></small>
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

    // Load company banks options via AJAX
    fetch('<?= $GLOBALS['base_url'] ?>/get-company-banks')
        .then(r => r.json())
        .then(data => {
            const sel = document.getElementById('vrBankSelect');
            if (!sel) return;
            sel.innerHTML = '<option value="">-- Select Company Bank --</option>';
            (data.banks || []).forEach(b => {
                const opt = document.createElement('option');
                opt.value = b.id;
                opt.textContent = b.bank_name + ' — ' + b.account_name + (b.account_number ? ' (' + b.account_number + ')' : '');
                sel.appendChild(opt);
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

    document.querySelectorAll('form[action*="approve-cashout-request"]').forEach(function(f) {
        var isSubmitting = false;
        f.addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            isSubmitting = true;
            var btn = f.querySelector('button[type="submit"]');
            if (btn) {
                btn.classList.add('disabled');
                btn.innerHTML = '<i class="fa-light fa-spinner fa-spin me-2"></i> Processing...';
            }
        });
    });
});
</script>
<?php endif; ?>
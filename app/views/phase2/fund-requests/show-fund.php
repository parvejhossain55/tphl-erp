<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Fund Request Detail';

$statusMap = [
    'pending'        => ['label' => 'Pending Approval', 'color' => 'warning'],
    'approved'       => ['label' => 'Approved',          'color' => 'success'],
    'rejected'       => ['label' => 'Rejected',          'color' => 'danger'],
    'paid'           => ['label' => 'Paid',              'color' => 'secondary'],
    'partially_paid' => ['label' => 'Partially Paid',    'color' => 'info'],
    'fully_paid'     => ['label' => 'Fully Paid',        'color' => 'secondary'],
];
$status     = $request['status'] ?? 'pending';
$statusInfo = $statusMap[$status] ?? ['label' => ucfirst(str_replace('_', ' ', $status)), 'color' => 'secondary'];

$canApproveReq  = $canApprove ?? false;
$canPayReq      = $canProcessPayment ?? false;
$userRole       = $userRole ?? null;
$payments       = $payments ?? [];
$totalPaid      = $total_paid ?? 0;
$dueAmount      = $due_amount ?? 0;
$payableAmount  = $payable_amount ?? 0;

$isPending      = ($status === 'pending');
$isApproved     = in_array($status, ['approved', 'partially_paid']);
$isRejected     = ($status === 'rejected');
$isFullyPaid    = in_array($status, ['fully_paid', 'paid']);
$canReject      = $canApproveReq && !$isRejected && !$isFullyPaid;
$showPaymentForm = $canPayReq && $isApproved && $dueAmount > 0;
?>

<style>
    .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-approved { background: #d4edda; color: #155724; }
    .status-rejected { background: #f8d7da; color: #721c24; }
    .status-partial { background: #cce5ff; color: #004085; }
    .status-paid { background: #d1ecf1; color: #0c5460; }
    .action-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; }
    .action-card .card__header { background: transparent; border-bottom: 1px solid rgba(255,255,255,0.2); color: white; }
    .info-label { font-weight: 600; color: #6c757d; font-size: 0.875rem; margin-bottom: 5px; }
    .info-value { font-size: 1rem; color: #2c3e50; margin-bottom: 15px; }
</style>

<div class="row">
    <!-- Header -->
    <div class="col-xxl-12 mb-4">
        <div class="card__wrapper">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="card__title mb-1">
                        <i class="bi bi-cash-coin me-2"></i>
                        Fund Request #<?= $request['id'] ?? '' ?>
                    </h5>
                    <p class="text-muted mb-0">
                        Requested by: <strong><?= htmlspecialchars($request['requester_name'] ?? 'N/A') ?></strong>
                        | Project: <strong><?= htmlspecialchars($request['project_name'] ?? 'N/A') ?></strong>
                    </p>
                </div>
                <div class="text-end d-flex gap-2">
                    <?php if (!empty($payments)): ?>
                        <a href="<?= $base_url ?>/print-debit-voucher?module=operational_fund&id=<?= $request['id'] ?>" target="_blank" class="btn btn-primary btn-sm">
                            <i class="bi bi-printer me-1"></i> Debit Voucher
                        </a>
                    <?php endif; ?>
                    <span class="badge bg-<?= $statusInfo['color'] ?> fs-6 px-3 py-2"><?= $statusInfo['label'] ?></span>
                    <a href="<?= $base_url ?>/con-fund-requests" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="col-xxl-8">
        <!-- Request Details Card -->
        <div class="card__wrapper mb-4">
            <div class="card__header d-flex justify-content-between align-items-center">
                <h6 class="card__title mb-0">
                    <i class="bi bi-info-circle me-2"></i>Request Information
                </h6>
            </div>
            <div class="card__body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-label">Project</div>
                        <div class="info-value"><strong><?= htmlspecialchars($request['project_name'] ?? '-') ?></strong></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Requested By</div>
                        <div class="info-value"><strong><?= htmlspecialchars($request['requester_name'] ?? '-') ?></strong></div>
                    </div>
                    <?php if (!empty($request['operator_name'])): ?>
                    <div class="col-md-6">
                        <div class="info-label">Related Operator</div>
                        <div class="info-value"><strong><?= htmlspecialchars($request['operator_name']) ?></strong></div>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-6">
                        <div class="info-label">Fund Date</div>
                        <div class="info-value"><?= date('d M Y', strtotime($request['fund_date'])) ?></div>
                    </div>

                    <!-- Financial Summary Row -->
                    <div class="col-md-12">
                        <div class="row g-2 mt-1">
                            <div class="col-md-3">
                                <div class="border rounded p-3 text-center">
                                    <small class="text-muted d-block mb-1">Requested Amount</small>
                                    <h5 class="text-primary mb-0 fw-bold">৳ <?= number_format($request['amount'], 2) ?></h5>
                                </div>
                            </div>
                            <?php if (!empty($request['approved_amount'])): ?>
                            <div class="col-md-3">
                                <div class="border rounded p-3 text-center">
                                    <small class="text-muted d-block mb-1">Approved Amount</small>
                                    <h5 class="text-success mb-0 fw-bold">৳ <?= number_format($request['approved_amount'], 2) ?></h5>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if ($isApproved || $isFullyPaid): ?>
                            <div class="col-md-3">
                                <div class="border rounded p-3 text-center">
                                    <small class="text-muted d-block mb-1">Total Paid</small>
                                    <h5 class="text-success mb-0 fw-bold">৳ <?= number_format($totalPaid, 2) ?></h5>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3 text-center">
                                    <small class="text-muted d-block mb-1">Due Amount</small>
                                    <?php if ($dueAmount > 0): ?>
                                        <h5 class="text-danger mb-0 fw-bold">৳ <?= number_format($dueAmount, 2) ?></h5>
                                    <?php else: ?>
                                        <h5 class="text-success mb-0 fw-bold">৳ 0.00</h5>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="info-label">Purpose</div>
                        <div class="info-value">
                            <div class="border-start border-4 border-info ps-3">
                                <?= nl2br(htmlspecialchars($request['purpose'] ?? '-')) ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Submission Date</div>
                        <div class="info-value">
                            <?= date('d M, Y', strtotime($request['created_at'])) ?>
                            <small class="text-muted"><?= date('h:i A', strtotime($request['created_at'])) ?></small>
                        </div>
                    </div>
                    <?php if (!empty($request['approved_by_name'])): ?>
                    <div class="col-md-6">
                        <div class="info-label">Approved By</div>
                        <div class="info-value"><strong><?= htmlspecialchars($request['approved_by_name']) ?></strong></div>
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
                        <i class="bi bi-credit-card me-2"></i>Payment History
                    </h6>
                </div>
                <div>
                    <span class="badge bg-light text-dark">
                        Total Payments: <?= count($payments) ?>
                    </span>
                    <?php if ($showPaymentForm && !empty($payments)): ?>
                        <button type="button" class="btn btn-sm btn-success ms-2"
                                data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                            <i class="bi bi-plus me-1"></i> Add Payment
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
                                    <th>Reference</th>
                                    <th>Proof</th>
                                    <th>Processed By</th>
                                    <th>Added On</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?= date('d M, Y', strtotime($payment['transaction_date'] ?? $payment['created_at'])) ?></td>
                                    <td><strong class="text-success">৳ <?= number_format($payment['amount_paid'] ?? 0, 2) ?></strong></td>
                                    <td><span class="badge bg-info"><?= ucfirst($payment['payment_method'] ?? '') ?></span></td>
                                    <td>
                                        <?php if (!empty($payment['reference_number'])): ?>
                                            <small><?= htmlspecialchars($payment['reference_number']) ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($payment['disbursement_proof'])):
                                            $proofFiles = json_decode($payment['disbursement_proof'], true) ?: [];
                                        ?>
                                            <div class="d-flex flex-wrap gap-1">
                                            <?php foreach ($proofFiles as $pIdx => $pPath): ?>
                                                <?php $pExt = strtolower(pathinfo($pPath, PATHINFO_EXTENSION)); ?>
                                                <a href="<?= $base_url ?>/public/<?= htmlspecialchars($pPath) ?>" target="_blank"
                                                    class="btn btn-xs btn-outline-success py-0 px-1" style="font-size:11px;"
                                                    title="<?= htmlspecialchars(basename($pPath)) ?>">
                                                    <i class="bi bi-<?= in_array($pExt, ['jpg','jpeg','png','gif','webp']) ? 'image' : ($pExt === 'pdf' ? 'file-pdf' : 'file-earmark') ?> me-1"></i>
                                                    <?= $pIdx + 1 ?>
                                                </a>
                                            <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($payment['processed_by_name'] ?? 'N/A') ?></td>
                                    <td>
                                        <?= date('d M, Y', strtotime($payment['created_at'])) ?><br>
                                        <small class="text-muted"><?= date('h:i A', strtotime($payment['created_at'])) ?></small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <td><strong>Total Paid:</strong></td>
                                    <td><strong class="text-success">৳ <?= number_format($totalPaid, 2) ?></strong></td>
                                    <td colspan="5"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-credit-card fs-1 text-muted mb-3 d-block"></i>
                        <p class="text-muted mb-0">No payments recorded yet.</p>
                        <?php if ($showPaymentForm): ?>
                            <button type="button" class="btn btn-success mt-3"
                                    data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                                <i class="bi bi-plus me-2"></i> Add First Payment
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar — Actions -->
    <div class="col-xxl-4">

        <!-- Approval Actions Card -->
        <?php if (!$isRejected && !$isFullyPaid): ?>
        <div class="card__wrapper action-card mb-4">
            <div class="card__header">
                <h6 class="mb-0">
                    <i class="bi bi-shield-check me-2"></i>Approval Actions
                </h6>
            </div>
            <div class="card__body">
                <?php if ($isPending && $canApproveReq): ?>
                    <!-- Approve Form with Amount -->
                    <form action="<?= $base_url ?>/con-fund-request-approve/<?= $request['id'] ?>" method="POST" class="mb-3">
                        <div class="mb-2">
                            <label class="form-label text-white small">Approved Amount (৳)</label>
                            <input type="number" name="approved_amount" class="form-control"
                                   value="<?= number_format($request['amount'], 2, '.', '') ?>"
                                   step="0.01" min="0.01" max="<?= $request['amount'] ?>">
                            <small class="text-white-50">Requested: ৳ <?= number_format($request['amount'], 2) ?></small>
                        </div>
                        <button type="submit" class="btn btn-light w-100 fw-bold"
                                onclick="return confirm('Approve this fund request?')">
                            <i class="bi bi-check-circle me-2"></i> Approve
                        </button>
                    </form>
                    <div class="alert mb-2 py-2 small" style="background: rgba(255,255,255,0.2); border: none; color: white;">
                        <i class="bi bi-info-circle me-1"></i>
                        You can adjust the approved amount before approving.
                    </div>
                <?php elseif ($isPending && !$canApproveReq): ?>
                    <div class="alert mb-0 py-2 small" style="background: rgba(255,255,255,0.2); border: none; color: white;">
                        <i class="bi bi-hourglass-split me-1"></i>
                        Waiting for approval. Only Admin/Super Admin/Operation can approve.
                    </div>
                <?php elseif ($isApproved): ?>
                    <div class="alert mb-0 py-2 small" style="background: rgba(255,255,255,0.2); border: none; color: white;">
                        <i class="bi bi-check-circle me-1"></i>
                        Request is approved. Accounts team can now add payments.
                    </div>
                <?php endif; ?>

                <?php if ($canReject && ($isPending || $isApproved)): ?>
                    <hr style="border-color: rgba(255,255,255,0.2);">
                    <form action="<?= $base_url ?>/con-fund-request-reject/<?= $request['id'] ?>" method="POST">
                        <button type="submit" class="btn btn-danger w-100"
                                onclick="return confirm('Reject this fund request? This action cannot be undone.')">
                            <i class="bi bi-x-circle me-2"></i> Reject Request
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Approval Info Card -->
        <div class="card__wrapper mb-4">
            <div class="card__header">
                <h6 class="card__title mb-0"><i class="bi bi-info-circle me-2"></i> Status Info</h6>
            </div>
            <div class="card__body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-3">
                        <span class="text-muted small">Current Status</span><br>
                        <span class="badge bg-<?= $statusInfo['color'] ?> mt-1"><?= $statusInfo['label'] ?></span>
                    </li>
                    <li class="mb-3">
                        <span class="text-muted small">Submitted At</span><br>
                        <strong><?= date('d M Y h:i A', strtotime($request['created_at'])) ?></strong>
                    </li>
                    <?php if (!empty($request['approved_by_name'])): ?>
                    <li class="mb-3">
                        <span class="text-muted small">Approved By</span><br>
                        <strong><?= htmlspecialchars($request['approved_by_name']) ?></strong>
                    </li>
                    <?php endif; ?>
                    <?php if (!empty($request['updated_at'])): ?>
                    <li class="mb-3">
                        <span class="text-muted small">Last Updated</span><br>
                        <strong><?= date('d M Y h:i A', strtotime($request['updated_at'])) ?></strong>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Add Payment Modal -->
<?php if ($showPaymentForm): ?>
<div class="modal fade" id="addPaymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle me-2"></i>Add Payment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= $base_url ?>/con-fund-request-payment/<?= $request['id'] ?>" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Approved Amount:</strong> ৳ <?= number_format($payableAmount, 2) ?><br>
                        <strong>Already Paid:</strong> ৳ <?= number_format($totalPaid, 2) ?><br>
                        <strong>Due Amount:</strong> <span class="text-danger">৳ <?= number_format($dueAmount, 2) ?></span>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Amount (৳): <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control"
                                   placeholder="0.00" step="0.01" min="0.01" max="<?= $dueAmount ?>"
                                   value="<?= number_format($dueAmount, 2, '.', '') ?>" required>
                            <small class="text-muted">Maximum: ৳ <?= number_format($dueAmount, 2) ?></small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Date: <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control"
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Method: <span class="text-danger">*</span></label>
                            <select name="payment_method" id="fundPaymentMethod" class="form-select" required>
                                <option value="Cash">Cash</option>
                                <option value="Cheque">Cheque</option>
                                <option value="BankTransfer">Bank Transfer</option>
                                <option value="MobileWallet">Mobile Wallet (bKash/Nagad)</option>
                            </select>
                        </div>

                        <!-- Bank Dropdown (shown when BankTransfer selected) -->
                        <div class="col-md-6 mb-3 fund-bank-field" style="display:none;">
                            <label class="form-label">Bank <span class="text-danger">*</span></label>
                            <select name="bank_name" class="form-select">
                                <option value="">-- Select Bank --</option>
                                <?php foreach ($banks ?? [] as $bank): ?>
                                    <option value="<?= htmlspecialchars($bank['bank_name'] . ' - ' . $bank['branch_name']) ?>">
                                        <?= htmlspecialchars($bank['bank_name'] . ' — ' . $bank['branch_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Reference / Cheque No.</label>
                            <input type="text" name="reference_no" class="form-control"
                                   placeholder="Cheque number, transaction ID, etc.">
                        </div>

                        <!-- Mandatory Disbursement Proof -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                <i class="bi bi-paperclip me-1"></i>
                                Disbursement Proof <span class="text-danger">*</span>
                                <span class="text-muted small ms-1">(PDF, JPG, PNG, DOC — max 5MB each)</span>
                            </label>
                            <input type="file" name="disbursement_proof[]" class="form-control"
                                   multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                            <small class="text-muted">Upload payment receipt, bank slip, or cheque scan.</small>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label">Notes <span class="text-muted small">(optional)</span></label>
                            <textarea name="notes" class="form-control" rows="2"
                                      placeholder="Additional payment notes..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold"
                            onclick="return confirm('Confirm this payment? This action cannot be undone.')">
                        <i class="bi bi-check-circle me-1"></i> Confirm Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var methodSelect = document.getElementById('fundPaymentMethod');
    var bankFields = document.querySelectorAll('.fund-bank-field');

    function toggleBankField() {
        var show = methodSelect && methodSelect.value === 'BankTransfer';
        bankFields.forEach(function(el) {
            el.style.display = show ? '' : 'none';
            var sel = el.querySelector('select');
            if (sel) sel.required = show;
        });
    }

    if (methodSelect) {
        methodSelect.addEventListener('change', toggleBankField);
        // Also run on modal show
        var modal = document.getElementById('addPaymentModal');
        if (modal) {
            modal.addEventListener('shown.bs.modal', toggleBankField);
        }
        toggleBankField();
    }
});
</script>

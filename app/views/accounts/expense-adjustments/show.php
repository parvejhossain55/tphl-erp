<?php
$page_title = "Adjustment Details";
$page = 'Accounts';
$show_breadcrumb = true;

$isReturn = $adj['adjustment_type'] == 'return';
$isExtra = $adj['adjustment_type'] == 'extra';
?>
<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="card__header d-flex justify-content-between align-items-center mb-3">
                <h5 class="card__title">Expense Adjustment REQ-<?= $adj['id'] ?></h5>
                <div class="d-flex gap-2">
                    <?php if (!empty($transactions)): ?>
                        <a href="<?= $GLOBALS['base_url'] ?>/print-debit-voucher?module=expense_adjustment&id=<?= $adj['id'] ?>" target="_blank" class="btn btn-primary btn-sm">
                            <i class="fa-light fa-file-invoice-dollar me-1"></i> Debit Voucher
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
                    <!-- Left Column: Details -->
                    <div class="col-md-8">
                        <div class="card mb-4 border shadow-sm">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Adjustment Request Info</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted mb-1 d-block">Type</label>
                                        <?php if ($isReturn): ?>
                                            <span class="badge bg-info fs-6">Return Unused Money</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark fs-6">Request Extra Money</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted mb-1 d-block">Status</label>
                                        <?php
                                            $badge = 'bg-secondary';
                                            $statusLabel = strtoupper(str_replace('_', ' ', $adj['status']));
                                            
                                            if ($adj['status'] == 'pending') {
                                                $badge = 'bg-warning text-dark';
                                                $statusLabel = 'AWAITING APPROVAL';
                                            } elseif ($adj['status'] == 'approved' || $adj['status'] == 'partially_paid') {
                                                if ($isExtra) {
                                                    $badge = 'bg-info text-dark';
                                                    $statusLabel = 'PENDING PAYMENT FROM ACCOUNTS';
                                                } else {
                                                    $badge = 'bg-warning text-dark';
                                                    $statusLabel = 'PENDING ACCOUNTS VERIFICATION';
                                                }
                                            } elseif ($adj['status'] == 'rejected') {
                                                $badge = 'bg-danger';
                                            } elseif ($adj['status'] == 'fully_paid') {
                                                $badge = 'bg-success';
                                                $statusLabel = $isReturn ? 'VERIFIED & CLOSED BY ACCOUNTS' : 'SETTLED & PAID';
                                            }
                                        ?>
                                        <span class="badge <?= $badge ?> fs-6"><?= $statusLabel ?></span>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted mb-1 d-block">Requester</label>
                                        <div class="fw-bold"><?= htmlspecialchars($adj['requester_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($adj['requester_role']) ?></small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted mb-1 d-block">Date</label>
                                        <div class="fw-bold"><?= date('d M Y, h:i A', strtotime($adj['created_at'])) ?></div>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label class="text-muted mb-1 d-block">Title / Purpose</label>
                                        <div class="fw-bold"><?= htmlspecialchars($adj['title']) ?></div>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label class="text-muted mb-1 d-block">Description</label>
                                        <div><?= nl2br(htmlspecialchars($adj['description'] ?? '—')) ?></div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted mb-1 d-block">Requested Amount</label>
                                        <div class="fs-5 fw-bold <?= $isReturn ? 'text-success' : 'text-danger' ?>">
                                            TK.<?= number_format($adj['amount'], 2) ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted mb-1 d-block">Approved Amount</label>
                                        <div class="fs-5 fw-bold text-primary">
                                            <?= $adj['approved_amount'] ? 'TK.' . number_format($adj['approved_amount'], 2) : '—' ?>
                                        </div>
                                    </div>

                                    <?php if (!empty($adj['document']) && $adj['document'] !== 'null'): ?>
                                    <div class="col-md-12 mt-3 border-top pt-3">
                                        <label class="text-muted mb-2 d-block">Attached Documents</label>
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
                                                    Doc <?= $i+1 ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Parent Request Reference -->
                        <div class="card mb-4 border shadow-sm">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fa-light fa-link me-2"></i>Original Cashout Request Info</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <label class="text-muted mb-1 d-block">Parent Request ID</label>
                                        <?php 
                                            $link = '#'; // Fallback if no specific view page exists for the type
                                            if ($adj['parent_type'] == 'cashout') {
                                                $link = $GLOBALS['base_url'] . '/view-cashout-request?id=' . $adj['cashout_request_id'];
                                                $prefix = 'REQ';
                                            } elseif ($adj['parent_type'] == 'vendor') {
                                                $prefix = 'VENDORREQ';
                                            } elseif ($adj['parent_type'] == 'contractor') {
                                                $prefix = 'CONTRACTORREQ';
                                            } elseif ($adj['parent_type'] == 'purchase') {
                                                $prefix = 'PURCHASEREQ';
                                            } else {
                                                $prefix = 'REQ';
                                            }
                                        ?>
                                        <a href="<?= $link ?>" class="fw-bold" target="_blank">
                                            <?= $prefix ?>-<?= $adj['cashout_request_id'] ?> <i class="fa-light fa-external-link ms-1"></i>
                                        </a>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="text-muted mb-1 d-block">Original Amount</label>
                                        <div class="fw-bold">TK.<?= number_format($adj['original_amount'], 2) ?></div>
                                    </div>
                                    <div class="col-md-12 mb-2">
                                        <label class="text-muted mb-1 d-block">Original Purpose</label>
                                        <div><?= htmlspecialchars($adj['original_purpose'] ?? '') ?></div>
                                    </div>
                                    <?php if(!empty($adj['project_name'])): ?>
                                    <div class="col-md-6 mb-2 mt-2">
                                        <label class="text-muted mb-1 d-block">Project</label>
                                        <div class="fw-bold text-info"><?= htmlspecialchars($adj['project_name']) ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if(!empty($adj['office'])): ?>
                                    <div class="col-md-6 mb-2 mt-2">
                                        <label class="text-muted mb-1 d-block">Office</label>
                                        <div class="fw-bold text-primary"><?= htmlspecialchars($adj['office']) ?></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Transactions Table -->
                        <?php if (!empty($transactions)): ?>
                        <div class="card mb-4 border shadow-sm">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Transaction History</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Amount</th>
                                                <th>Method / Ref</th>
                                                <th>Proof</th>
                                                <th>Recorded By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($transactions as $txn): ?>
                                                <tr>
                                                    <td><?= date('d M Y', strtotime($txn['payment_date'])) ?></td>
                                                    <td class="fw-bold <?= $txn['transaction_type'] == 'cashin' ? 'text-success' : 'text-danger' ?>">
                                                        TK.<?= number_format($txn['amount'], 2) ?>
                                                    </td>
                                                    <td>
                                                        <?= ucfirst($txn['payment_method']) ?>
                                                        <?php if(!empty($txn['transaction_ref'])): ?>
                                                            <br><small class="text-muted">Ref: <?= htmlspecialchars($txn['transaction_ref']) ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($txn['disbursement_proof'])): ?>
                                                            <?php $proofs = json_decode($txn['disbursement_proof'], true) ?: []; ?>
                                                            <?php foreach ($proofs as $pIdx => $pPath): ?>
                                                                <a href="<?= $GLOBALS['base_url'] ?>/<?= $pPath ?>" target="_blank" class="btn btn-xs btn-outline-success p-1">
                                                                    <i class="fa-light fa-file"></i>
                                                                </a>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            —
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?= htmlspecialchars($txn['added_by_name']) ?>
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

                    <!-- Right Column: Actions & Summary -->
                    <div class="col-md-4">
                        <div class="card border shadow-sm sticky-top" style="top: 20px;">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Actions & Tracking</h6>
                            </div>
                            <div class="card-body">
                                <?php if ($adj['status'] == 'pending'): ?>
                                    <?php if ($canApprove): ?>
                                        <form action="<?= $GLOBALS['base_url'] ?>/expense-adjustments/approve" method="POST" class="mb-3">
                                            <input type="hidden" name="id" value="<?= $adj['id'] ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Approved Amount (TK.) <span class="text-danger">*</span></label>
                                                <input type="number" name="approved_amount" class="form-control" step="0.01" min="1" value="<?= $adj['amount'] ?>" required>
                                                <small class="text-muted">You can modify the approved amount if needed.</small>
                                            </div>
                                            <button type="submit" class="btn btn-success w-100 fw-bold">
                                                <i class="fa-light fa-check-circle me-1"></i> Approve Request
                                            </button>
                                        </form>

                                        <form action="<?= $GLOBALS['base_url'] ?>/expense-adjustments/reject" method="POST" onsubmit="return confirm('Are you sure you want to reject this request?');">
                                            <input type="hidden" name="id" value="<?= $adj['id'] ?>">
                                            <button type="submit" class="btn btn-danger w-100">
                                                <i class="fa-light fa-times-circle me-1"></i> Reject Request
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <div class="alert alert-warning text-center border mb-0" style="background:rgba(245,158,11,.08); border-color:rgba(245,158,11,.3) !important;">
                                            <i class="fa-light fa-hourglass-half fa-2x d-block mb-2"></i>
                                            <strong>Pending Approval</strong>
                                            <div class="text-muted small mt-1">Awaiting Management approval</div>
                                        </div>
                                    <?php endif; ?>

                                <?php elseif (in_array($adj['status'], ['approved', 'partially_paid'])): ?>
                                    <div class="alert alert-info text-center mb-3">
                                        <div class="mb-1"><strong>Total Approved:</strong> TK.<?= number_format($adj['approved_amount'], 2) ?></div>
                                        <div><strong>Due Amount:</strong> <span class="text-danger">TK.<?= number_format($adj['due_amount'], 2) ?></span></div>
                                    </div>
                                    
                                    <!-- Custom Pending Status Notice -->
                                    <div class="alert text-center border mb-3" style="background: rgba(13,110,253,.08); border-color: rgba(13,110,253,.3) !important;">
                                        <i class="fa-light <?= $isReturn ? 'fa-wallet' : 'fa-hourglass-half' ?> fa-2x d-block mb-2 text-primary"></i>
                                        <?php if ($isExtra): ?>
                                            <strong>Pending Payment from Accounts</strong>
                                            <div class="text-muted small mt-1">Management approved. Awaiting Accounts disbursement.</div>
                                        <?php else: ?>
                                            <strong>Pending Accounts Verification</strong>
                                            <div class="text-muted small mt-1">Cash returned by requester. Awaiting Accounts officer verification and receive confirmation.</div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($isAccounts && $adj['due_amount'] > 0): ?>
                                        <button class="btn btn-primary w-100 fw-bold" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                                            <i class="fa-light fa-money-bill-transfer me-1"></i> 
                                            <?= $isReturn ? 'Receive Return Money' : 'Add Cashout Payment' ?>
                                        </button>
                                    <?php endif; ?>

                                <?php elseif ($adj['status'] == 'fully_paid'): ?>
                                    <div class="bg-success text-white rounded p-3 text-center mb-0">
                                        <div class="mb-2"><i class="fa-regular fa-check-circle fa-2x"></i></div>
                                        <div class="fw-bold fs-6 mb-1">Settled Successfully</div>
                                        <div class="small">Total: TK.<?= number_format($adj['approved_amount'], 2) ?></div>
                                    </div>
                                <?php elseif ($adj['status'] == 'rejected'): ?>
                                    <div class="bg-danger text-white rounded p-3 text-center mb-0">
                                        <div class="mb-2"><i class="fa-regular fa-times-circle fa-2x"></i></div>
                                        <div class="fw-bold fs-6">Request Rejected</div>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($adj['approved_by'])): ?>
                                    <div class="border-top mt-3 pt-3">
                                        <div class="small">
                                            <div class="mb-1"><strong>Processed By:</strong> <?= htmlspecialchars($adj['approver_name'] ?? '') ?></div>
                                            <div><strong>Date:</strong> <?= date('d M Y, h:i A', strtotime($adj['approved_at'])) ?></div>
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

<!-- Add Payment Modal -->
<?php if ($isAccounts && in_array($adj['status'], ['approved', 'partially_paid'])): ?>
<div class="modal fade" id="addPaymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-light fa-money-bill-transfer me-2"></i>
                    <?= $isReturn ? 'Record Received Return Money' : 'Record Requisition Payment' ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= $GLOBALS['base_url'] ?>/expense-adjustments/add-payment" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="adjustment_id" value="<?= $adj['id'] ?>">

                    <div class="alert alert-info">
                        <strong>Due Amount:</strong> <span class="text-danger">TK.<?= number_format($adj['due_amount'], 2) ?></span>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount (TK.) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control" step="0.01" max="<?= $adj['due_amount'] ?>" value="<?= $adj['due_amount'] ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Method <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-control" required>
                                <option value="cash">Cash</option>
                                <option value="bank">Bank</option>
                                <option value="cheque">Cheque</option>
                                <option value="online">Online</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Transaction Reference</label>
                            <input type="text" name="transaction_ref" class="form-control" placeholder="Receipt or Transaction ID">
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label">Proof / Document <span class="text-danger">*</span></label>
                            <input type="file" name="disbursement_proof[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.pdf" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Transaction</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

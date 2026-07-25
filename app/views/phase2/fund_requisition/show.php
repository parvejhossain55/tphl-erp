<?php
$sessionUserId = $sessionUserId ?? (int)($_SESSION['user']['id'] ?? 0);
$sessionRoleId = $sessionRoleId ?? (int)($_SESSION['user']['role_id'] ?? 0);
$base          = $GLOBALS['base_url'];

$isOwner    = ($req['requested_by'] == $sessionUserId);
$isAccounts = in_array($sessionRoleId, [1, 6, 30]);
$isAdmin    = in_array($sessionRoleId, [1, 3]);
$isVerified = !empty($req['accounts_settlement_verified']);

$statusColors = ['pending'=>'bg-warning text-dark','approved'=>'bg-info','paid'=>'bg-success','rejected'=>'bg-danger'];
$statusClass = $statusColors[$req['status']] ?? 'bg-secondary';

$settleBadgeMap = [
    'pending'         => ['bg-warning text-dark','Pending'],
    'settled'         => ['bg-success','Settled'],
    'extra_requested' => ['bg-danger','Extra Fund Requested'],
];
[$settleClass, $settleLabel] = $settleBadgeMap[$req['settlement_status'] ?? 'pending'] ?? ['bg-secondary','---'];
?>
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h1 class="page-title fw-semibold fs-18 mb-0">REQ-<?php echo $req['id']; ?> &mdash; Fund Requisition Details</h1>
            <nav><ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item"><a href="<?php echo $base; ?>/fund-requisitions">Fund Requisitions</a></li>
                <li class="breadcrumb-item active">REQ-<?php echo $req['id']; ?></li>
            </ol></nav>
        </div>
        <div class="d-flex gap-2 no-print">
            <?php if ($req['status'] === 'paid'): ?>
            <a href="<?php echo $base; ?>/print-debit-voucher?module=fund_requisition&id=<?php echo $req['id']; ?>" target="_blank" class="btn btn-primary btn-sm border">
                <i class="fa-solid fa-file-invoice-dollar me-1"></i> Debit Voucher
            </a>
            <?php endif; ?>
            <button type="button" class="btn btn-light btn-sm border" onclick="window.print()">
                <i class="fa-solid fa-print me-1"></i> Print
            </button>
            <a href="<?php echo $base; ?>/fund-requisitions" class="btn btn-light btn-sm border">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    <!-- PRINT-ONLY HEADER -->
    <div class="print-only-header d-none">
        <div class="text-center mb-3">
            <h4 class="fw-bold mb-1">TPHL — Fund Requisition</h4>
            <div class="fs-13 text-muted">REQ-<?php echo $req['id']; ?> &nbsp;|&nbsp;
                Printed on: <span id="printDateTime"></span>
            </div>
            <hr>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-4">
                <small class="text-muted d-block">Requested By</small>
                <strong><?php echo htmlspecialchars($req['requester_name'] ?? '---'); ?></strong>
            </div>
            <div class="col-4">
                <small class="text-muted d-block">Department</small>
                <strong><?php echo htmlspecialchars($req['department_name'] ?? '---'); ?></strong>
            </div>
            <div class="col-4">
                <small class="text-muted d-block">Project</small>
                <strong><?php echo $req['project_name'] ? htmlspecialchars($req['project_name']) : 'Office General'; ?></strong>
            </div>
            <div class="col-4">
                <small class="text-muted d-block">Type</small>
                <strong><?php echo ucfirst(str_replace('_', ' ', $req['requisition_type'] ?? '')); ?> — <?php echo htmlspecialchars($req['category'] ?? ''); ?></strong>
            </div>
            <div class="col-4">
                <small class="text-muted d-block">Amount Requested</small>
                <strong>&#2547; <?php echo number_format($req['amount'], 2); ?></strong>
            </div>
            <div class="col-4">
                <small class="text-muted d-block">Date</small>
                <strong><?php echo date('d M Y', strtotime($req['created_at'])); ?></strong>
            </div>
            <div class="col-12">
                <small class="text-muted d-block">Purpose</small>
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($req['purpose'])); ?></p>
            </div>
            <?php if ($req['status'] === 'paid'): ?>
            <div class="col-4">
                <small class="text-muted d-block">Status</small>
                <strong>Paid via <?php echo htmlspecialchars($req['payment_method'] ?? 'Cash'); ?></strong>
            </div>
            <div class="col-4">
                <small class="text-muted d-block">Total Spent</small>
                <strong>&#2547; <?php echo number_format($totalSpent, 2); ?></strong>
            </div>
            <div class="col-4">
                <small class="text-muted d-block">Balance</small>
                <strong>&#2547; <?php echo number_format($balance, 2); ?></strong>
            </div>
            <?php endif; ?>
        </div>
        <hr>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
        <div class="alert alert-danger fs-13"><i class="fa-solid fa-triangle-exclamation me-1"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="row print-area">
        <!-- Left Column: Primary Details & Actions -->
        <div class="col-lg-4">
            <div class="card custom-card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="card-title fs-13 text-primary">Requisition Info</div>
                    <span class="badge <?php echo $statusClass; ?> fs-11"><?php echo ucfirst($req['status']); ?></span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <small class="text-muted d-block fs-11 text-uppercase fw-semibold">Requisition ID</small>
                            <span class="fw-bold fs-13">REQ-<?php echo $req['id']; ?></span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block fs-11 text-uppercase fw-semibold">Date</small>
                            <span class="fs-13"><?php echo date('d M Y', strtotime($req['created_at'])); ?></span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block fs-11 text-uppercase fw-semibold">Requested By</small>
                            <span class="fs-13 fw-semibold"><?php echo htmlspecialchars($req['requester_name']); ?></span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block fs-11 text-uppercase fw-semibold">Department</small>
                            <span class="fs-13"><?php echo htmlspecialchars($req['department_name'] ?? '---'); ?></span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block fs-11 text-uppercase fw-semibold">Project</small>
                            <span class="fs-13 fst-italic text-muted"><?php echo htmlspecialchars($req['project_name'] ?? 'Office General'); ?></span>
                            
                            <?php if (!empty($req['asset_code'])): ?>
                                <div class="mt-2 p-2 bg-primary-transparent border border-primary border-opacity-25 rounded fs-12">
                                    <i class="fa-solid fa-cube text-primary me-1"></i>
                                    <strong><?= htmlspecialchars($req['asset_code']) ?></strong>
                                    <div class="text-muted fs-11 mt-1"><?= htmlspecialchars($req['asset_item_name'] ?? '') ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block fs-11 text-uppercase fw-semibold">Type</small>
                            <?php if ($req['requisition_type'] === 'petty_cash'): ?>
                                <span class="badge bg-warning fs-11 text-dark">Petty Cash</span>
                            <?php else: ?>
                                <span class="badge bg-info fs-11">General</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block fs-11 text-uppercase fw-semibold">Amount Requested</small>
                            <span class="fw-bold fs-18 text-success">&#2547; <?php echo number_format($req['amount'], 2); ?></span>
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block fs-11 text-uppercase fw-semibold">Purpose</small>
                            <p class="mb-0 bg-light p-2 rounded fs-12"><?php echo nl2br(htmlspecialchars($req['purpose'])); ?></p>
                        </div>
                        <?php if (!empty($req['receipt_path'])): ?>
                            <div class="col-12">
                                <a href="<?php echo $base . '/' . $req['receipt_path']; ?>" target="_blank" class="btn btn-sm btn-light border">
                                    <i class="fa-solid fa-paperclip me-1"></i> View Receipt
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card custom-card mb-3">
                <div class="card-header"><div class="card-title fs-13 text-info">Management Approval</div></div>
                <div class="card-body">
                    <?php if (in_array($req['status'], ['approved','paid'])): ?>
                        <span class="badge bg-success-transparent fs-11 mb-2">Approved</span>
                        <div class="fs-12">By: <strong><?php echo htmlspecialchars($req['approved_by_name'] ?? '---'); ?></strong><br>
                        <span class="text-muted"><?php echo $req['approved_at'] ? date('d M Y, h:i A', strtotime($req['approved_at'])) : ''; ?></span></div>
                        <?php if (!empty($req['approval_note'])): ?>
                            <p class="mt-2 mb-0 bg-light p-2 rounded fs-12"><?php echo htmlspecialchars($req['approval_note']); ?></p>
                        <?php endif; ?>
                    <?php elseif ($req['status'] === 'rejected' && !empty($req['approved_by'])): ?>
                        <span class="badge bg-danger-transparent fs-11 mb-2">Rejected</span>
                        <p class="fs-12 mb-0 bg-light p-2 rounded"><?php echo htmlspecialchars($req['approval_note'] ?? '---'); ?></p>
                    <?php else: ?>
                        <span class="badge bg-warning-transparent fs-11 mb-2">Awaiting Approval</span>
                        <?php if ($req['status'] === 'pending' && $isAdmin): ?>
                        <div class="mt-2">
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#approveModal">
                                <i class="fa-solid fa-stamp me-1"></i> Approve / Reject
                            </button>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card custom-card mb-3">
                <div class="card-header"><div class="card-title fs-13 text-success">Accounts Disbursement</div></div>
                <div class="card-body">
                    <?php if ($req['status'] === 'paid'): ?>
                        <span class="badge bg-success-transparent fs-11 mb-2">Paid</span>
                        <div class="fs-12">By: <strong><?php echo htmlspecialchars($req['paid_by_name'] ?? '---'); ?></strong><br>
                        Method: <strong><?php echo htmlspecialchars($req['payment_method'] ?? 'Cash'); ?></strong><br>
                        <span class="text-muted"><?php echo $req['paid_at'] ? date('d M Y, h:i A', strtotime($req['paid_at'])) : ''; ?></span></div>
                        <?php if (!empty($req['accounts_note'])): ?>
                            <p class="mt-2 mb-0 bg-light p-2 rounded fs-12"><?php echo htmlspecialchars($req['accounts_note']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($req['payment_attachment_path'])): ?>
                            <a href="<?php echo $base . '/' . $req['payment_attachment_path']; ?>" target="_blank" class="btn btn-sm btn-success-light border mt-2">
                                <i class="fa-solid fa-receipt me-1"></i> Payment Slip
                            </a>
                        <?php endif; ?>
                    <?php elseif ($req['status'] === 'approved' && $isAccounts): ?>
                        <span class="badge bg-info-transparent fs-11 mb-2 text-dark">Ready to Pay</span>
                        <div class="mt-2">
                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#payModal">
                                <i class="fa-solid fa-file-invoice-dollar me-1"></i> Process Payment
                            </button>
                        </div>
                    <?php else: ?>
                        <span class="badge bg-warning-transparent text-dark fs-11">Awaiting Payment</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <?php if ($req['status'] === 'paid'): ?>
            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <div class="card custom-card mb-0 text-center p-3">
                        <small class="text-muted fs-11 text-uppercase fw-semibold">Disbursed</small>
                        <div class="fw-bold fs-18 text-success mt-1">&#2547; <?php echo number_format($req['amount'], 2); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card custom-card mb-0 text-center p-3">
                        <small class="text-muted fs-11 text-uppercase fw-semibold">Total Spent</small>
                        <div class="fw-bold fs-18 text-primary mt-1">&#2547; <?php echo number_format($totalSpent, 2); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card custom-card mb-0 text-center p-3">
                        <small class="text-muted fs-11 text-uppercase fw-semibold">Balance Remaining</small>
                        <div class="fw-bold fs-18 mt-1 <?php echo $balance > 0 ? 'text-warning' : 'text-muted'; ?>">&#2547; <?php echo number_format($balance, 2); ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="card custom-card mb-3">
                <div class="card-header justify-content-between align-items-center">
                    <div class="card-title fs-13">Expense Log</div>
                    <?php if ($req['status'] === 'paid' && ($isOwner || $isAdmin)): ?>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                        <i class="fa-solid fa-plus me-1"></i> Log Expense
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table text-nowrap table-hover mb-0 fs-12">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th><th>Date</th><th>Purpose</th><th>Project</th>
                                    <th class="text-end">Amount</th><th>Receipt</th><th>By</th>
                                    <?php if ($isAdmin || $isOwner): ?><th>Del</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($expenses)): ?>
                                <tr><td colspan="8" class="text-center text-muted py-4">No expenses logged yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($expenses as $i => $exp): ?>
                                <tr>
                                    <td><?php echo $i+1; ?></td>
                                    <td><?php echo date('d M Y', strtotime($exp['created_at'])); ?></td>
                                    <td class="text-wrap" style="max-width:200px"><?php echo htmlspecialchars($exp['purpose']); ?></td>
                                    <td><?php echo $exp['project_name'] ? htmlspecialchars($exp['project_name']) : ($exp['other_project_name'] ? htmlspecialchars($exp['other_project_name']) : '<span class="text-muted">Office</span>'); ?></td>
                                    <td class="text-end fw-semibold">&#2547; <?php echo number_format($exp['amount'],2); ?></td>
                                    <td>
                                        <?php if (!empty($exp['attachment_path'])): ?>
                                            <a href="<?php echo $base.'/'.$exp['attachment_path']; ?>" target="_blank" class="btn btn-icon btn-sm btn-light border py-0 px-2"><i class="fa-solid fa-paperclip fs-11"></i></a>
                                        <?php else: echo '---'; endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($exp['creator_name'] ?? 'Staff'); ?></td>
                                    <?php if ($isAdmin || $isOwner): ?>
                                    <td>
                                        <form action="<?php echo $base; ?>/fund-requisitions/delete-expense" method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
                                            <input type="hidden" name="id" value="<?php echo $exp['id']; ?>">
                                            <button class="btn btn-icon btn-sm btn-danger border py-0 px-2"><i class="fa-solid fa-trash-can fs-11"></i></button>
                                        </form>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>


        </div>
    </div>
</div>

<?php if ($req['status'] === 'pending' && $isAdmin): ?>
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog"><form action="<?php echo $base; ?>/fund-requisitions/approve" method="POST">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Approve / Reject</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="id" value="<?php echo $req['id']; ?>">
                <div class="mb-3"><label class="form-label">Action</label>
                    <select name="status" class="form-select" required><option value="approved">Approve</option><option value="rejected">Reject</option></select></div>
                <div class="mb-3"><label class="form-label">Remarks</label>
                    <textarea name="approval_note" class="form-control" rows="3"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary btn-sm">Submit</button></div>
        </div>
    </form></div>
</div>
<?php endif; ?>

<?php if ($req['status'] === 'approved' && $isAccounts): ?>
<div class="modal fade" id="payModal" tabindex="-1">
    <div class="modal-dialog"><form action="<?php echo $base; ?>/fund-requisitions/pay" method="POST" enctype="multipart/form-data">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Process Payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="id" value="<?php echo $req['id']; ?>">
                <div class="mb-2 p-3 bg-light rounded border">
                    <strong><?php echo htmlspecialchars($req['requester_name']??'---'); ?></strong> &mdash; <?php echo htmlspecialchars($req['department_name']??''); ?>
                    <div class="fs-18 text-success fw-bold mt-1">&#2547; <?php echo number_format($req['amount'],2); ?></div>
                </div>
                <div class="mb-3"><label class="form-label">Payment Method</label>
                    <select name="payment_method" class="form-select"><option value="Cash" selected>Cash</option><option value="Bank Transfer">Bank Transfer</option><option value="Cheque">Cheque</option><option value="bKash">bKash</option><option value="Nagad">Nagad</option></select></div>
                <div class="mb-3"><label class="form-label">Payment Slip (Optional)</label><input type="file" name="payment_attachment" class="form-control" accept="image/*,application/pdf"></div>
                <div class="mb-3"><label class="form-label">Accounts Note</label><textarea name="accounts_note" class="form-control" rows="2"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success btn-sm">Confirm Payment</button></div>
        </div>
    </form></div>
</div>
<?php endif; ?>

<?php if ($req['status'] === 'paid' && ($isOwner || $isAdmin)): ?>
<div class="modal fade" id="addExpenseModal" tabindex="-1">
    <div class="modal-dialog"><form action="<?php echo $base; ?>/fund-requisitions/store-expense" method="POST" enctype="multipart/form-data">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Log an Expense</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="fund_requisition_id" value="<?php echo $req['id']; ?>">
                <div class="mb-3"><label class="form-label">Amount (&#2547;) <span class="text-danger">*</span></label>
                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01" max="<?php echo $balance; ?>" placeholder="0.00" required></div>
                <div class="mb-3"><label class="form-label">Purpose <span class="text-danger">*</span></label>
                    <textarea name="purpose" class="form-control" rows="2" required></textarea></div>

                <div class="mb-3"><label class="form-label">Receipt (Optional)</label><input type="file" name="attachment" class="form-control" accept="image/*,application/pdf"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm" <?php echo $balance<=0?'disabled':''; ?>>Log Expense</button></div>
        </div>
    </form></div>
</div>
<?php endif; ?>

<?php if ($req['status']==='paid' && ($isOwner||$isAdmin) && ($req['settlement_status']??'')!=='extra_requested'): ?>
<div class="modal fade" id="extraFundModal" tabindex="-1">
    <div class="modal-dialog"><form action="<?php echo $base; ?>/fund-requisitions/extra-fund/<?php echo $req['id']; ?>" method="POST">
        <div class="modal-content">
            <div class="modal-header bg-danger bg-opacity-10"><h5 class="modal-title text-danger">Request Extra Fund</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="alert alert-warning py-2 fs-12">Original: <strong>&#2547; <?php echo number_format($req['amount'],2); ?></strong> | Spent: <strong>&#2547; <?php echo number_format($totalSpent,2); ?></strong></div>
                <div class="mb-3"><label class="form-label">Extra Amount (&#2547;) <span class="text-danger">*</span></label>
                    <input type="number" name="extra_amount" class="form-control" step="0.01" min="0.01" value="<?php echo max(0,$totalSpent-$req['amount']); ?>" required></div>
                <div class="mb-3"><label class="form-label">Reason <span class="text-danger">*</span></label>
                    <textarea name="extra_reason" class="form-control" rows="3" required></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger btn-sm">Submit</button></div>
        </div>
    </form></div>
</div>
<?php endif; ?>

<style>
@media print {
    /* Hide everything by default */
    body * { visibility: hidden; }

    /* Show the printable area */
    .print-area, .print-area * { visibility: visible; }
    .print-only-header, .print-only-header * { visibility: visible; display: block !important; }

    /* Position for printing */
    .print-only-header {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        background: white;
        padding: 10px 20px;
        z-index: 9999;
    }
    .print-area {
        position: absolute;
        top: 220px;
        left: 0;
        width: 100%;
        padding: 10px 20px;
    }

    /* Hide non-print elements */
    .no-print, .modal, .btn-close,
    .page-header-breadcrumb { display: none !important; visibility: hidden !important; }

    /* Clean table styles */
    .table { width: 100% !important; border-collapse: collapse !important; }
    .table th, .table td {
        border: 1px solid #ccc !important;
        padding: 6px 8px !important;
        font-size: 11px !important;
    }
    .table thead th { background: #f0f0f0 !important; }

    /* Card borders */
    .card, .custom-card { border: 1px solid #ccc !important; box-shadow: none !important; margin-bottom: 10px !important; }
    .card-header { background: #f5f5f5 !important; }

    /* Remove Bootstrap bg colors for print */
    .bg-success, .bg-info, .bg-warning, .bg-danger, .bg-primary, .bg-secondary,
    .bg-success-transparent, .bg-info-transparent, .bg-warning-transparent, .bg-danger-transparent {
        background: transparent !important;
        border: 1px solid #999 !important;
        color: #000 !important;
    }

    /* Page breaks */
    .row { page-break-inside: avoid; }
    @page { margin: 10mm; }
}
</style>

<script>
(function() {
    function formatDate(d) {
        var days   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var day    = days[d.getDay()];
        var date   = String(d.getDate()).padStart(2, '0');
        var month  = months[d.getMonth()];
        var year   = d.getFullYear();
        var hours  = d.getHours();
        var mins   = String(d.getMinutes()).padStart(2, '0');
        var ampm   = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        return day + ', ' + date + ' ' + month + ' ' + year + ', ' + hours + ':' + mins + ' ' + ampm;
    }

    function setPrintDate() {
        var el = document.getElementById('printDateTime');
        if (el) el.textContent = formatDate(new Date());
    }

    if (window.matchMedia) {
        var mql = window.matchMedia('print');
        mql.addListener(function(m) { if (m.matches) setPrintDate(); });
    }
    window.onbeforeprint = setPrintDate;
    document.addEventListener('DOMContentLoaded', setPrintDate);
})();
</script>

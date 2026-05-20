<?php
$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
?>

<div class="mb-3 pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4 class="mb-2">Petty Cash Requisitions</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Petty Cash</li>
            </ol>
        </nav>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#requestPettyCashModal">
        <i class="fa-light fa-plus me-1"></i> Request Petty Cash
    </button>
</div>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Requester & Dept</th>
                                <th>Project</th>
                                <th>Date & Category</th>
                                <th>Amount</th>
                                <th>Purpose & Receipt</th>
                                <th>Status</th>
                                <th>Payment Method</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($requests)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">No petty cash requests found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($requests as $index => $row): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($row['requester_name'] ?? 'N/A') ?></div>
                                            <div class="badge bg-secondary"><?= htmlspecialchars($row['department_name'] ?? 'N/A') ?></div>
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-primary"><?= $row['project_id'] ? htmlspecialchars($row['project_name']) : 'Office General' ?></span>
                                        </td>
                                        <td>
                                            <div><?= date('d M Y', strtotime($row['expense_date'])) ?></div>
                                            <small class="text-muted text-capitalize"><?= htmlspecialchars($row['expense_category']) ?></small>
                                        </td>
                                        <td class="fw-bold text-success">
                                            BDT <?= number_format((float)($row['amount'] ?? 0), 2) ?>
                                        </td>
                                        <td>
                                            <div class="small" style="max-width: 250px;"><?= htmlspecialchars($row['purpose']) ?></div>
                                            <?php if (!empty($row['receipt_path'])): ?>
                                                <div class="mt-1">
                                                    <a href="<?= $base_url . '/' . htmlspecialchars($row['receipt_path']) ?>" target="_blank" class="btn btn-sm btn-outline-info py-0 px-2" style="font-size: 11px;">
                                                        <i class="fa-light fa-paperclip me-1"></i> Receipt
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $statusBadge = match ($row['status']) {
                                                'pending' => 'warning',
                                                'approved' => 'info',
                                                'paid' => 'success',
                                                'rejected' => 'danger',
                                                default => 'secondary'
                                            };
                                            ?>
                                            <span class="badge bg-<?= $statusBadge ?> text-capitalize"><?= $row['status'] ?></span>
                                            <?php if ($row['status'] === 'approved' && !empty($row['approved_by_name'])): ?>
                                                <div style="font-size:10px;" class="text-muted">Approved by: <?= htmlspecialchars($row['approved_by_name']) ?></div>
                                            <?php endif; ?>
                                            <?php if ($row['status'] === 'paid' && !empty($row['paid_by_name'])): ?>
                                                <div style="font-size:10px;" class="text-muted">Paid by: <?= htmlspecialchars($row['paid_by_name']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($row['approval_note'])): ?>
                                                <div style="font-size:10px; font-style:italic;" class="text-muted">"<?= htmlspecialchars($row['approval_note']) ?>"</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($row['status'] === 'paid'): ?>
                                                <div class="small">Method: <strong><?= htmlspecialchars($row['payment_method'] ?? 'Cash') ?></strong></div>
                                                <small class="text-muted" style="font-size:10px;">At: <?= date('d M Y H:i', strtotime($row['paid_at'])) ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-1">
                                                <!-- Management Approve / Reject -->
                                                <?php if ($row['status'] === 'pending' && in_array($roleId, [1, 3])): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-success" 
                                                            data-bs-toggle="modal" data-bs-target="#mgmtApproveModal"
                                                            data-id="<?= $row['id'] ?>" data-amount="<?= $row['amount'] ?>">
                                                        Approve/Reject
                                                    </button>
                                                <?php endif; ?>

                                                <!-- Accounts Pay -->
                                                <?php if ($row['status'] === 'approved' && in_array($roleId, [1, 6])): ?>
                                                    <button type="button" class="btn btn-sm btn-success" 
                                                            data-bs-toggle="modal" data-bs-target="#accountsPayModal"
                                                            data-id="<?= $row['id'] ?>" data-amount="<?= $row['amount'] ?>">
                                                        Pay Cash
                                                    </button>
                                                <?php endif; ?>

                                                <?php if ($roleId === 1 || $roleId === 3): ?>
                                                <form action="<?= $base_url ?>/petty-cash-requests/delete" method="POST" onsubmit="return confirm('Are you sure you want to delete this petty cash request?');" class="d-inline">
                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fa-light fa-trash"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
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

<!-- Request Petty Cash Modal -->
<div class="modal fade" id="requestPettyCashModal" tabindex="-1" aria-labelledby="requestPettyCashModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="requestPettyCashModalLabel">Request Petty Cash</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= $base_url ?>/petty-cash-requests/store" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                        <select class="form-select" name="department_id" required>
                            <option value="" disabled selected>Select Department...</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= ($_SESSION['user']['department_id'] ?? 0) == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Project (Optional)</label>
                        <select class="form-select" name="project_id">
                            <option value="">Office General (No Project)</option>
                            <?php foreach ($projects as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Expense Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="expense_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Expense Category <span class="text-danger">*</span></label>
                            <select class="form-select" name="expense_category" required>
                                <option value="Transport" selected>Transport / Conveyance</option>
                                <option value="Food">Food / Refreshment</option>
                                <option value="Office Supplies">Office Supplies / Stationery</option>
                                <option value="Communication">Communication (Mobile/Internet)</option>
                                <option value="Site Expense">Site Expense</option>
                                <option value="Repair">Repair & Maintenance</option>
                                <option value="Miscellaneous">Miscellaneous</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Amount (BDT) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" name="amount" required placeholder="0.00">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Purpose <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="purpose" rows="3" required placeholder="Describe the cash requirement details..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Upload Receipt / Bill Copy</label>
                        <input type="file" class="form-control" name="receipt" accept="image/*,application/pdf">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Management Approve Modal -->
<div class="modal fade" id="mgmtApproveModal" tabindex="-1" aria-labelledby="mgmtApproveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="mgmtApproveModalLabel">Approve Petty Cash Request</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= $base_url ?>/petty-cash-requests/approve" method="POST">
                <input type="hidden" name="id" id="mgmt_req_id">
                <div class="modal-body p-4">
                    <h5 class="mb-3">Request Amount: <strong class="text-success">BDT <span id="mgmt_req_amount"></span></strong></h5>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Approval Status</label>
                        <select class="form-select" name="status" required>
                            <option value="approved" selected>Approve Request</option>
                            <option value="rejected">Reject Request</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Approval Remarks</label>
                        <textarea class="form-control" name="approval_note" rows="3" placeholder="Provide reason or remarks"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Save Decision</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Accounts Payment Modal -->
<div class="modal fade" id="accountsPayModal" tabindex="-1" aria-labelledby="accountsPayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="accountsPayModalLabel">Disburse Cash Payment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= $base_url ?>/petty-cash-requests/pay" method="POST">
                <input type="hidden" name="id" id="accounts_req_id">
                <div class="modal-body p-4">
                    <h5 class="mb-3">Disburse Amount: <strong class="text-success">BDT <span id="accounts_req_amount"></span></strong></h5>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Disbursement Decision</label>
                        <select class="form-select" name="status" required>
                            <option value="paid" selected>Mark as Paid & Disbursed</option>
                            <option value="rejected">Reject Payment</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Disbursement Method</label>
                        <select class="form-select" name="payment_method" required>
                            <option value="Cash" selected>Cash Handover</option>
                            <option value="Mobile Banking">Mobile Banking (bKash/Nagad)</option>
                            <option value="Bank">Bank Cheque</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-dark">Disburse Funds</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var mgmtApproveModal = document.getElementById('mgmtApproveModal');
    mgmtApproveModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var amount = button.getAttribute('data-amount');
        
        document.getElementById('mgmt_req_id').value = id;
        document.getElementById('mgmt_req_amount').textContent = parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2});
    });

    var accountsPayModal = document.getElementById('accountsPayModal');
    accountsPayModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var amount = button.getAttribute('data-amount');
        
        document.getElementById('accounts_req_id').value = id;
        document.getElementById('accounts_req_amount').textContent = parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2});
    });
});
</script>

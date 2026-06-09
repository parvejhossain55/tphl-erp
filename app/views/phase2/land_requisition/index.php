<?php
$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
$canRequest = in_array($roleId, [1, 3, 14, 15, 22]);
?>


<div class="mb-3 pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4 class="mb-2">Land Fund Requisitions</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Land Requisitions</li>
            </ol>
        </nav>
    </div>
    <?php if ($canRequest): ?>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#requestFundModal">
        <i class="fa-light fa-plus me-1"></i> New Fund Requisition
    </button>
    <?php endif; ?>
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
                                <th>Project & Landowner</th>
                                <th>Req Type</th>
                                <th>Amount Requested</th>
                                <th>Requested By</th>
                                <th>Mgmt Status</th>
                                <th>Accounts Status</th>
                                <th>Payment Info</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($requisitions)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">No fund requisitions found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($requisitions as $index => $row): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td>
                                            <div class="fw-semibold text-primary"><?= htmlspecialchars($row['project_name'] ?? 'N/A') ?></div>
                                            <div class="small">Owner: <?= htmlspecialchars($row['land_owner_name'] ?? 'N/A') ?></div>
                                            <div class="text-muted small" style="font-size:11px;"><?= htmlspecialchars($row['purpose']) ?></div>
                                            <?php if (!empty($row['req_attachment_path'])): ?>
                                                <div class="mt-1">
                                                    <a href="<?= $base_url ?>/<?= htmlspecialchars($row['req_attachment_path']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:10px;">
                                                        <i class="fa-light fa-paperclip me-1"></i>Req Attachment
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($row['requisition_type']) ?></span>
                                        </td>
                                        <td class="fw-bold text-success">
                                            BDT <?= number_format((float)($row['requested_amount'] ?? 0), 2) ?>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($row['requester_name'] ?? 'N/A') ?></div>
                                            <small class="text-muted" style="font-size:10px;"><?= date('M d, Y', strtotime($row['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            $mBadge = match ($row['mgmt_status']) {
                                                'Approved' => 'success',
                                                'Rejected' => 'danger',
                                                default => 'warning'
                                            };
                                            ?>
                                            <span class="badge bg-<?= $mBadge ?>"><?= $row['mgmt_status'] ?></span>
                                            <?php if (!empty($row['mgmt_name'])): ?>
                                                <div style="font-size:10px;" class="text-muted">By: <?= htmlspecialchars($row['mgmt_name']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($row['mgmt_approved_at']) && $row['mgmt_status'] !== 'Pending'): ?>
                                                <div style="font-size:10px;" class="text-muted">On: <?= date('d M Y h:i A', strtotime($row['mgmt_approved_at'])) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($row['mgmt_note'])): ?>
                                                <div style="font-size:10px; font-style:italic;" class="text-muted">"<?= htmlspecialchars($row['mgmt_note']) ?>"</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $aBadge = match ($row['accounts_status']) {
                                                'Paid' => 'success',
                                                'Rejected' => 'danger',
                                                default => 'warning'
                                            };
                                            ?>
                                            <span class="badge bg-<?= $aBadge ?>"><?= $row['accounts_status'] ?></span>
                                            <?php if (!empty($row['accounts_name'])): ?>
                                                <div style="font-size:10px;" class="text-muted">By: <?= htmlspecialchars($row['accounts_name']) ?></div>
                                            <?php endif; ?>
                                            <?php if ($row['accounts_status'] === 'Paid' && !empty($row['accounts_paid_at'])): ?>
                                                <div style="font-size:10px;" class="text-muted">On: <?= date('d M Y h:i A', strtotime($row['accounts_paid_at'])) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($row['accounts_status'] === 'Paid'): ?>
                                                <div class="small">Method: <strong><?= htmlspecialchars($row['payment_method'] ?? 'N/A') ?></strong></div>
                                                <div class="small">Txn: <code><?= htmlspecialchars($row['transaction_no'] ?? 'N/A') ?></code></div>
                                                <small class="text-muted" style="font-size:10px;">Paid at: <?= date('d M Y', strtotime($row['accounts_paid_at'])) ?></small>
                                                <?php if (!empty($row['attachment_path'])): ?>
                                                    <div class="mt-1">
                                                        <a href="<?= $base_url ?>/<?= htmlspecialchars($row['attachment_path']) ?>" target="_blank" class="btn btn-sm btn-outline-info py-0 px-2" style="font-size:10px;">
                                                            <i class="fa-light fa-paperclip me-1"></i>View Attachment
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-1">
                                                <!-- Management Action -->
                                                <?php if ($row['mgmt_status'] === 'Pending' && in_array($roleId, [1, 3])): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-success" 
                                                            data-bs-toggle="modal" data-bs-target="#mgmtApproveModal"
                                                            data-id="<?= $row['id'] ?>" data-amount="<?= $row['requested_amount'] ?>">
                                                        Approve/Reject
                                                    </button>
                                                <?php endif; ?>

                                                <!-- Accounts Action -->
                                                <?php if ($row['mgmt_status'] === 'Approved' && $row['accounts_status'] === 'Pending' && in_array($roleId, [1, 6])): ?>
                                                    <button type="button" class="btn btn-sm btn-success" 
                                                            data-bs-toggle="modal" data-bs-target="#accountsPayModal"
                                                            data-id="<?= $row['id'] ?>" data-amount="<?= $row['requested_amount'] ?>">
                                                        Record Payment
                                                    </button>
                                                <?php endif; ?>

                                                <?php if ($roleId === 1 || $roleId === 3): ?>
                                                <form action="<?= $base_url ?>/con-land-requisitions/delete" method="POST" onsubmit="return confirm('Are you sure you want to delete this requisition?');" class="d-inline">
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

<!-- Request Fund Modal -->
<div id="requestFundModal" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Fund Requisition</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= $base_url ?>/con-land-requisitions/store" method="POST" enctype="multipart/form-data">
                    <div class="row gy-10">
                        <div class="col-xl-12">
                            <div class="card__wrapper">
                                <div class="row gy-20">
                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Select Land Acquisition Portfolio <span class="text-danger">*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <select class="form-control" name="land_acquisition_id" required>
                                                    <option value="" disabled selected>Select Portfolio...</option>
                                                    <?php foreach ($acquisitions as $la): ?>
                                                        <option value="<?= $la['id'] ?>"><?= htmlspecialchars($la['project_name']) ?> — Owner: <?= htmlspecialchars($la['land_owner_name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Requisition Type <span class="text-danger">*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <select class="form-control" name="requisition_type" required>
                                                    <option value="Bayana">Bayana (Advance Payment)</option>
                                                    <option value="Registration">Deed Registration Fee</option>
                                                    <option value="Extra Cost">Extra Cost (Mutation/Legal/Brokerage)</option>
                                                    <option value="Other">Other</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Requested Amount (BDT) <span class="text-danger">*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input type="number" step="0.01" class="form-control" name="requested_amount" required placeholder="0.00">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Detailed Purpose <span class="text-danger">*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <textarea class="form-control" name="purpose" rows="3" required placeholder="Describe the reason/purpose for requesting this amount"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Requisition Attachment (Receipt / Estimation / Document)</label>
                                            </div>
                                            <div class="form__input">
                                                <input type="file" class="form-control" name="req_attachment">
                                                <small class="text-muted">Max file size: 2MB. Format: JPG, PNG, PDF, DOCX</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="submit__btn text-center mt-3">
                        <button class="btn btn-primary" type="submit">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Management Approval Modal -->
<div id="mgmtApproveModal" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Management Approval Option</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= $base_url ?>/con-land-requisitions/approve" method="POST">
                    <input type="hidden" name="id" id="mgmt_req_id">
                    <div class="row gy-10">
                        <div class="col-xl-12">
                            <div class="card__wrapper">
                                <div class="row gy-20">
                                    <div class="col-lg-12">
                                        <h5 class="mb-3">Amount: <strong class="text-success">BDT <span id="mgmt_req_amount"></span></strong></h5>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Approval Decision</label>
                                            </div>
                                            <div class="form__input">
                                                <select class="form-control" name="status" required>
                                                    <option value="Approved" selected>Approve Request</option>
                                                    <option value="Rejected">Reject Request</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Remarks / Notes</label>
                                            </div>
                                            <div class="form__input">
                                                <textarea class="form-control" name="mgmt_note" rows="3" placeholder="Provide reason or comments"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="submit__btn text-center mt-3">
                        <button class="btn btn-primary" type="submit">Save Decision</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Accounts Payment Modal -->
<div id="accountsPayModal" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Payment Detail</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= $base_url ?>/con-land-requisitions/pay" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="accounts_req_id">
                    <div class="row gy-10">
                        <div class="col-xl-12">
                            <div class="card__wrapper">
                                <div class="row gy-20">
                                    <div class="col-lg-12">
                                        <h5 class="mb-3">Pay Amount: <strong class="text-success">BDT <span id="accounts_req_amount"></span></strong></h5>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Payment Decision</label>
                                            </div>
                                            <div class="form__input">
                                                <select class="form-control" name="status" required>
                                                    <option value="Paid" selected>Mark as Paid</option>
                                                    <option value="Rejected">Reject Payment</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Payment Method</label>
                                            </div>
                                            <div class="form__input">
                                                <select class="form-control" name="payment_method" required>
                                                    <option value="Bank" selected>Bank Transfer / Cheque</option>
                                                    <option value="Cash">Cash</option>
                                                    <option value="Mobile Banking">Mobile Banking (bKash/Nagad/Rocket)</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Transaction / Reference / Cheque No.</label>
                                            </div>
                                            <div class="form__input">
                                                <input type="text" class="form-control" name="transaction_no" placeholder="Txn ID, Cheque Number etc.">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Payment Attachment (Receipt / Document)</label>
                                            </div>
                                            <div class="form__input">
                                                <input type="file" class="form-control" name="attachment">
                                                <small class="text-muted">Max file size: 2MB. Format: JPG, PNG, PDF, DOCX</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label>Accounts Note</label>
                                            </div>
                                            <div class="form__input">
                                                <textarea class="form-control" name="accounts_note" rows="3" placeholder="Provide payment details or note"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="submit__btn text-center mt-3">
                        <button class="btn btn-primary" type="submit">Record Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<?php
$page_script = "(function() {
    function init() {
        // Set up event listeners for modal data binding
        const mgmtApproveModal = document.getElementById('mgmtApproveModal');
        if (mgmtApproveModal) {
            mgmtApproveModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const amount = button.getAttribute('data-amount');
                
                document.getElementById('mgmt_req_id').value = id;
                document.getElementById('mgmt_req_amount').textContent = parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2});
            });
        }

        const accountsPayModal = document.getElementById('accountsPayModal');
        if (accountsPayModal) {
            accountsPayModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const amount = button.getAttribute('data-amount');
                
                document.getElementById('accounts_req_id').value = id;
                document.getElementById('accounts_req_amount').textContent = parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2});
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
";
?>
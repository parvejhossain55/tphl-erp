

<main id="main" class="main">
    <div class="pagetitle d-flex justify-content-between align-items-center">
        <div>
            <h1>Review Project Other Cost Request</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/">Home</a></li>
                    <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/project-other-costs">Project Other Costs</a></li>
                    <li class="breadcrumb-item active">Review</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <?php if ($request['status'] === 'paid'): ?>
                <a href="<?= $GLOBALS['base_url'] ?>/expense-adjustments/create?parent_type=other_cost&cashout_request_id=<?= $request['id'] ?>&project_id=<?= $request['project_id'] ?>" class="btn btn-warning btn-sm shadow-sm">
                    <i class="bi bi-sliders me-1"></i> Settlement / Adjustment
                </a>
                <a href="<?= $GLOBALS['base_url'] ?>/print-debit-voucher?module=project_other_costs&id=<?= $request['id'] ?>" target="_blank" class="btn btn-primary btn-sm">
                    <i class="bi bi-printer me-1"></i> Debit Voucher
                </a>
            <?php endif; ?>
            <a href="<?= $GLOBALS['base_url'] ?>/project-other-costs" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-8">
                <!-- Request Details Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white pb-0 border-bottom-0">
                        <h5 class="card-title mb-0">Request Details</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered mt-3">
                            <tbody>
                                <tr>
                                    <th style="width: 30%; bg-light">Project Name</th>
                                    <td><?= htmlspecialchars($request['project_name']) ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Cost Type</th>
                                    <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', $request['rcost_type']))) ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Purpose</th>
                                    <td><?= nl2br(htmlspecialchars($request['purpose'])) ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Request Amount</th>
                                    <td class="text-primary fw-bold">৳ <?= number_format($request['request_amount'], 2) ?></td>
                                </tr>
                                <?php if(!empty($request['attachment'])): ?>
                                <tr>
                                    <th class="bg-light">Attachment</th>
                                    <td>
                                        <a href="<?= $GLOBALS['base_url'] ?>/public/uploads/project_other_costs/<?= htmlspecialchars($request['attachment']) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-paperclip"></i> View File</a>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php if($request['approved_amount']): ?>
                                <tr>
                                    <th class="bg-light text-success">Approved Amount</th>
                                    <td class="text-success fw-bold">
                                        ৳ <?= number_format($request['approved_amount'], 2) ?>
                                        <div class="mt-1">
                                            <small class="text-muted fw-normal" style="font-size: 0.75rem;">
                                                <i class="bi bi-person me-1"></i>Updated by <?= htmlspecialchars($request['management_apporved_by_id'] ? ($request['mgmt_name'] ?? 'N/A') : ($request['qc_name'] ?? 'N/A')) ?>
                                            </small>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php if($request['status'] === 'paid'): ?>
                                <tr>
                                    <th class="bg-light text-primary">Paid Amount</th>
                                    <td class="text-primary fw-bold">
                                        ৳ <?= number_format($request['payment_amount'] ?? $request['approved_amount'], 2) ?>
                                        <div class="mt-1">
                                            <small class="text-muted fw-normal" style="font-size: 0.75rem;">
                                                <i class="bi bi-cash me-1"></i>Disbursed by <?= htmlspecialchars($request['pay_name'] ?? 'Accounts') ?>
                                                <?php if(!empty($request['payment_at'])): ?>
                                                    on <?= date('d M Y h:i A', strtotime($request['payment_at'])) ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <th class="bg-light">Status</th>
                                    <td>
                                        <span class="badge bg-<?= $request['status'] == 'pending' ? 'warning' : ($request['status'] == 'paid' ? 'success' : 'info') ?>">
                                            <?= ucfirst(str_replace('_', ' ', $request['status'])) ?>
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Timeline / History Card -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white pb-0 border-bottom-0">
                        <h5 class="card-title mb-0">Approval History</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush mt-3">
                            <li class="list-group-item">
                                <strong>Created By:</strong> <?= htmlspecialchars($request['creator_name'] ?? 'N/A') ?><br>
                                <small class="text-muted"><?= date('d M Y h:i A', strtotime($request['created_at'])) ?></small>
                                <?php if (!empty($request['attachment'])): ?>
                                    <br>
                                    <a href="<?= $GLOBALS['base_url'] ?>/public/uploads/project_other_costs/<?= htmlspecialchars($request['attachment']) ?>" target="_blank" class="badge bg-secondary text-white mt-1 text-decoration-none">
                                        <i class="bi bi-paperclip me-1"></i> View Attached File
                                    </a>
                                <?php endif; ?>
                            </li>
                            <li class="list-group-item">
                                <strong>Dept Head Approval:</strong> 
                                <?php if($request['dept_head_status'] === 'not_required'): ?>
                                    <span class="text-muted"><i class="bi bi-dash-circle"></i> Not Required (No Dept Head Added)</span>
                                <?php elseif($request['dept_head_status'] === 'approved'): ?>
                                    <span class="text-success"><i class="bi bi-check-circle"></i> Approved</span> by <?= htmlspecialchars($request['dh_name'] ?? 'N/A') ?><br>
                                    <?php if(!empty($request['dept_head_approved_at'])): ?>
                                        <small class="text-muted"><?= date('d M Y h:i A', strtotime($request['dept_head_approved_at'])) ?></small><br>
                                    <?php endif; ?>
                                    <?php if($request['dept_head_approval_note']): ?><small>Note: <?= htmlspecialchars($request['dept_head_approval_note']) ?></small><?php endif; ?>
                                <?php elseif($request['dept_head_status'] === 'rejected'): ?>
                                    <span class="text-danger"><i class="bi bi-x-circle"></i> Rejected</span> by <?= htmlspecialchars($request['dh_name'] ?? 'N/A') ?><br>
                                    <?php if(!empty($request['dept_head_approved_at'])): ?>
                                        <small class="text-muted"><?= date('d M Y h:i A', strtotime($request['dept_head_approved_at'])) ?></small><br>
                                    <?php endif; ?>
                                    <?php if($request['dept_head_approval_note']): ?><small>Note: <?= htmlspecialchars($request['dept_head_approval_note']) ?></small><?php endif; ?>
                                <?php else: ?>
                                    <span class="text-warning"><i class="bi bi-clock"></i> Pending</span>
                                    <?php if (!empty($request['designated_dh_name'])): ?>
                                        from <strong><?= htmlspecialchars($request['designated_dh_name']) ?></strong>
                                        <?php if (!empty($request['designated_dh_designation'])): ?>
                                            <span class="text-muted small">(<?= htmlspecialchars($request['designated_dh_designation']) ?>)</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </li>
                            <li class="list-group-item">
                                <strong>QC Approval:</strong> 
                                <?php if($request['qc_audit_approved_id']): ?>
                                    <span class="text-success"><i class="bi bi-check-circle"></i> Approved</span> by <?= htmlspecialchars($request['qc_name'] ?? 'N/A') ?><br>
                                    <?php if(!empty($request['qc_audit_approved_at'])): ?>
                                        <small class="text-muted"><?= date('d M Y h:i A', strtotime($request['qc_audit_approved_at'])) ?></small><br>
                                    <?php endif; ?>
                                    <?php if($request['qc_audit_note']): ?><small>Note: <?= htmlspecialchars($request['qc_audit_note']) ?></small><?php endif; ?>
                                <?php else: ?>
                                    <span class="text-warning"><i class="bi bi-clock"></i> Pending</span>
                                <?php endif; ?>
                            </li>
                            <li class="list-group-item">
                                <strong>Management Approval:</strong>
                                <?php if($request['management_apporved_by_id']): ?>
                                    <span class="text-success"><i class="bi bi-check-circle"></i> Approved</span> by <?= htmlspecialchars($request['mgmt_name'] ?? 'N/A') ?><br>
                                    <?php if(!empty($request['management_apporved_at'])): ?>
                                        <small class="text-muted"><?= date('d M Y h:i A', strtotime($request['management_apporved_at'])) ?></small><br>
                                    <?php endif; ?>
                                    <?php if($request['management_apporved_note']): ?><small>Note: <?= htmlspecialchars($request['management_apporved_note']) ?></small><?php endif; ?>
                                <?php else: ?>
                                    <span class="text-warning"><i class="bi bi-clock"></i> Pending</span>
                                <?php endif; ?>
                            </li>
                            <li class="list-group-item">
                                <strong>Payment Status:</strong>
                                <?php if($request['payment_by_id']): ?>
                                    <span class="text-success"><i class="bi bi-check-circle"></i> Paid & Settled</span> by <?= htmlspecialchars($request['pay_name'] ?? 'N/A') ?><br>
                                    <small class="text-muted"><i class="bi bi-cash me-1"></i> Amount: ৳<?= number_format($request['payment_amount'] ?? $request['approved_amount'], 2) ?></small><br>
                                    <small class="text-muted"><i class="bi bi-calendar me-1"></i> Date: <?= date('d M Y h:i A', strtotime($request['payment_at'])) ?></small>
                                    <?php if(!empty($request['attachment'])): ?>
                                        <br>
                                        <a href="<?= $GLOBALS['base_url'] ?>/public/uploads/project_other_costs/<?= htmlspecialchars($request['attachment']) ?>" target="_blank" class="badge bg-primary text-white mt-1 text-decoration-none">
                                            <i class="bi bi-paperclip me-1"></i> View Attachment
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-warning"><i class="bi bi-clock"></i> Pending Accounts Payout</span>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Action / Processing Card -->
            <div class="col-lg-4">
                <?php
                $userId = $_SESSION['user']['id'] ?? ($userId ?? null);
                $creatorReportingUserId = (int)($request['creator_reporting_user_id'] ?? 0);
                $canDeptHeadApprove = ($request['dept_head_status'] === 'pending' && ($userId == $creatorReportingUserId || $roleId == 1));
                $canQCApprove = ($request['status'] == 'pending' && $request['dept_head_status'] !== 'pending' && in_array($roleId, [13, 24, 25, 1]));
                $canMgmtApprove = ($request['status'] == 'qc_approved' && in_array($roleId, [3, 15, 20, 22, 32, 33, 1]));
                $canPay = ($request['status'] == 'management_approved' && in_array($roleId, [6, 30, 1]));
                ?>

                <?php if ($canDeptHeadApprove || $canQCApprove || $canMgmtApprove || $canPay): ?>
                    <div class="card shadow-sm border-0 border-top border-3 border-primary">
                        <div class="card-header bg-white pb-0 border-bottom-0">
                            <h5 class="card-title text-primary mb-0"><i class="bi bi-pencil-square me-2"></i>Process Request</h5>
                        </div>
                        <div class="card-body mt-3">
                            
                            <?php if ($canDeptHeadApprove): ?>
                                <form method="POST" action="<?= $GLOBALS['base_url'] ?>/project-other-costs/approve-dept-head/<?= $request['id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Department Head Note (Optional)</label>
                                        <textarea class="form-control" name="dept_head_approval_note" rows="3" placeholder="Enter remarks..."></textarea>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" name="dept_head_status" value="approved" class="btn btn-success flex-grow-1" onclick="return confirm('Are you sure you want to approve this request?');">
                                            <i class="bi bi-check-circle me-1"></i> Approve
                                        </button>
                                        <button type="submit" name="dept_head_status" value="rejected" class="btn btn-danger flex-grow-1" onclick="return confirm('Are you sure you want to reject this request?');">
                                            <i class="bi bi-x-circle me-1"></i> Reject
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>

                            <?php if ($canQCApprove): ?>
                                <form method="POST" action="<?= $GLOBALS['base_url'] ?>/project-other-costs/approve-qc/<?= $request['id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Approved Amount (৳)</label>
                                        <input type="number" step="0.01" class="form-control form-control-lg" name="approved_amount" value="<?= $request['request_amount'] ?>" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">QC Note (Optional)</label>
                                        <textarea class="form-control" name="qc_audit_note" rows="3" placeholder="Enter remarks..."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success btn-lg w-100" onclick="return confirm('Are you sure you want to approve this request?');">
                                        <i class="bi bi-check-circle me-1"></i> Submit QC Approval
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($canMgmtApprove): ?>
                                <form method="POST" action="<?= $GLOBALS['base_url'] ?>/project-other-costs/approve-management/<?= $request['id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Final Approved Amount (৳)</label>
                                        <input type="number" step="0.01" class="form-control form-control-lg" name="approved_amount" value="<?= $request['approved_amount'] ?? $request['request_amount'] ?>" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Management Note (Optional)</label>
                                        <textarea class="form-control" name="management_apporved_note" rows="3" placeholder="Enter remarks..."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success btn-lg w-100" onclick="return confirm('Are you sure you want to approve this request?');">
                                        <i class="bi bi-check-circle me-1"></i> Submit Mgmt Approval
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($canPay): ?>
                                <form method="POST" action="<?= $GLOBALS['base_url'] ?>/project-other-costs/pay/<?= $request['id'] ?>" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Amount to Pay (৳)</label>
                                        <input type="number" step="0.01" class="form-control form-control-lg" name="payment_amount" value="<?= $request['approved_amount'] ?? 0 ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Payment Date</label>
                                        <input type="date" class="form-control" name="payment_at" value="<?= date('Y-m-d') ?>" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Attachment (Optional)</label>
                                        <input type="file" class="form-control" name="attachment" accept="image/*,.pdf,.doc,.docx">
                                        <small class="text-muted">Upload receipt or payment proof.</small>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-lg w-100" onclick="return confirm('Are you sure you want to disburse this payment?');">
                                        <i class="bi bi-cash me-1"></i> Disburse Payment
                                    </button>
                                </form>
                            <?php endif; ?>

                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-secondary text-center shadow-sm">
                        <i class="bi bi-info-circle fs-4 d-block mb-2 text-muted"></i>
                        <?php if ($request['dept_head_status'] === 'pending'): ?>
                            Currently waiting for Department Head approval from <strong><?= htmlspecialchars($request['designated_dh_name'] ?? 'Reporting Boss') ?></strong>.
                        <?php elseif ($request['status'] === 'pending'): ?>
                            Currently waiting for QC & Audit approval.
                        <?php elseif ($request['status'] === 'qc_approved'): ?>
                            Currently waiting for Management approval.
                        <?php elseif ($request['status'] === 'management_approved'): ?>
                            Currently waiting for Accounts Payout.
                        <?php elseif ($request['status'] === 'paid'): ?>
                            Request has been paid and completed.
                        <?php else: ?>
                            No actions required from your end at this stage.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($request['status'] === 'paid'): ?>
                    <div class="card shadow-sm border-0 border-top border-3 border-warning mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title text-warning mb-0 fs-6">
                                <i class="bi bi-patch-check me-2"></i>Expense Settlement & Adjustments
                            </h5>
                            <a href="<?= $GLOBALS['base_url'] ?>/expense-adjustments/create?parent_type=other_cost&cashout_request_id=<?= $request['id'] ?>&project_id=<?= $request['project_id'] ?>" class="btn btn-warning btn-sm shadow-sm fw-semibold">
                                <i class="bi bi-file-earmark-plus me-1"></i> Submit Settlement
                            </a>
                        </div>
                        <div class="card-body mt-2">
                            <?php if (empty($adjustments)): ?>
                                <div class="text-center py-3 text-muted">
                                    <i class="bi bi-clipboard-x fs-2 d-block mb-1 text-secondary"></i>
                                    No settlements or adjustments submitted yet for this payment.<br>
                                    <small class="text-muted">You can return unused funds or request extra money for this expense.</small>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Type</th>
                                                <th class="text-end">Amount (৳)</th>
                                                <th class="text-center">Status</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($adjustments as $adj):
                                                $typeBadge = ($adj['adjustment_type'] === 'return') ? 'info' : 'warning text-dark';
                                                $typeLabel = ($adj['adjustment_type'] === 'return') ? 'Return Unused' : 'Extra Money';
                                                $statusClass = match ($adj['status']) {
                                                    'pending' => 'warning text-dark',
                                                    'approved' => 'success',
                                                    'rejected' => 'danger',
                                                    'partially_paid' => 'info text-dark',
                                                    'fully_paid' => 'success',
                                                    default => 'secondary'
                                                };
                                            ?>
                                            <tr>
                                                <td class="fw-bold">REQ-<?= $adj['id'] ?></td>
                                                <td><span class="badge bg-<?= $typeBadge ?>"><?= $typeLabel ?></span></td>
                                                <td class="text-end fw-bold">৳ <?= number_format($adj['amount'], 2) ?></td>
                                                <td class="text-center"><span class="badge bg-<?= $statusClass ?>"><?= ucfirst(str_replace('_', ' ', $adj['status'])) ?></span></td>
                                                <td class="text-center">
                                                    <a href="<?= $GLOBALS['base_url'] ?>/expense-adjustments/show?id=<?= $adj['id'] ?>" class="btn btn-xs btn-outline-primary" style="font-size: 0.75rem;">
                                                        <i class="bi bi-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </section>
</main>



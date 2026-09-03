

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
        <a href="<?= $GLOBALS['base_url'] ?>/project-other-costs" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
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
                            </li>
                            <li class="list-group-item">
                                <strong>QC Approval:</strong> 
                                <?php if($request['qc_audit_approved_id']): ?>
                                    <span class="text-success"><i class="bi bi-check-circle"></i> Approved</span> by <?= htmlspecialchars($request['qc_name'] ?? 'N/A') ?><br>
                                    <small class="text-muted"><?= date('d M Y h:i A', strtotime($request['qc_audit_approved_at'])) ?></small>
                                    <?php if($request['qc_audit_note']): ?><br><small>Note: <?= htmlspecialchars($request['qc_audit_note']) ?></small><?php endif; ?>
                                <?php else: ?>
                                    <span class="text-warning"><i class="bi bi-clock"></i> Pending</span>
                                <?php endif; ?>
                            </li>
                            <li class="list-group-item">
                                <strong>Management Approval:</strong>
                                <?php if($request['management_apporved_by_id']): ?>
                                    <span class="text-success"><i class="bi bi-check-circle"></i> Approved</span> by <?= htmlspecialchars($request['mgmt_name'] ?? 'N/A') ?><br>
                                    <small class="text-muted"><?= date('d M Y h:i A', strtotime($request['management_apporved_at'])) ?></small>
                                    <?php if($request['management_apporved_note']): ?><br><small>Note: <?= htmlspecialchars($request['management_apporved_note']) ?></small><?php endif; ?>
                                <?php else: ?>
                                    <span class="text-warning"><i class="bi bi-clock"></i> Pending</span>
                                <?php endif; ?>
                            </li>
                            <li class="list-group-item">
                                <strong>Payment Status:</strong>
                                <?php if($request['payment_by_id']): ?>
                                    <span class="text-success"><i class="bi bi-check-circle"></i> Paid</span> by <?= htmlspecialchars($request['pay_name'] ?? 'N/A') ?><br>
                                    <small class="text-muted"><?= date('d M Y h:i A', strtotime($request['payment_at'])) ?></small>
                                <?php else: ?>
                                    <span class="text-warning"><i class="bi bi-clock"></i> Pending</span>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Action / Processing Card -->
            <div class="col-lg-4">
                <?php
                $canQCApprove = ($request['status'] == 'pending' && in_array($roleId, [13, 24, 25, 1]));
                $canMgmtApprove = ($request['status'] == 'qc_approved' && in_array($roleId, [3, 15, 22, 32, 33, 1]));
                $canPay = ($request['status'] == 'management_approved' && in_array($roleId, [6, 30, 1]));
                ?>

                <?php if ($canQCApprove || $canMgmtApprove || $canPay): ?>
                    <div class="card shadow-sm border-0 border-top border-3 border-primary">
                        <div class="card-header bg-white pb-0 border-bottom-0">
                            <h5 class="card-title text-primary mb-0"><i class="bi bi-pencil-square me-2"></i>Process Request</h5>
                        </div>
                        <div class="card-body mt-3">
                            
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
                                <form method="POST" action="<?= $GLOBALS['base_url'] ?>/project-other-costs/pay/<?= $request['id'] ?>">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i> Amount to Pay: <strong>৳ <?= number_format($request['approved_amount'], 2) ?></strong>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-lg w-100" onclick="return confirm('Are you sure you want to mark this as paid?');">
                                        <i class="bi bi-cash me-1"></i> Disburse Payment
                                    </button>
                                </form>
                            <?php endif; ?>

                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-secondary text-center shadow-sm">
                        <i class="bi bi-info-circle fs-4 d-block mb-2 text-muted"></i>
                        No actions required from your end at this stage.
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </section>
</main>



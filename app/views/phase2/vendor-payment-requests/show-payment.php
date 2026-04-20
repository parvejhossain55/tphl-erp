<div class="pagetitle d-flex justify-content-between align-items-center">
    <div>
        <h1><?= htmlspecialchars($page_title) ?></h1>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-vendor-payment-requests">Payment Requests</a></li>
                <li class="breadcrumb-item active">Detail</li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-vendor-payment-requests" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<section class="section">
    <div class="row">
        <!-- Main Details -->
        <div class="col-lg-8">
            <div class="card__wrapper shadow-sm mb-4">
                <div class="card__header d-flex justify-content-between align-items-center">
                    <h6 class="card__title mb-0">
                        <i class="bi bi-receipt me-2"></i>Request Details (VPR-<?= $request['id'] ?>)
                    </h6>
                    <?php if($request['status'] === 'Approved'): ?>
                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Approved</span>
                    <?php elseif($request['status'] === 'Rejected'): ?>
                        <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i> Rejected</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark"><i class="bi bi-hourglass me-1"></i> Pending</span>
                    <?php endif; ?>
                </div>
                <div class="card__body p-4">
                    <table class="table table-bordered mb-0">
                        <tbody>
                            <tr>
                                <th style="width: 30%" class="bg-light">Project</th>
                                <td class="fw-bold"><?= htmlspecialchars($request['project_name']) ?></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Vendor Name</th>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($request['vendor_name']) ?></span></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Date</th>
                                <td><?= date('d M Y', strtotime($request['payment_date'])) ?></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Remarks</th>
                                <td><?= nl2br(htmlspecialchars($request['remarks'] ?: '—')) ?></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Created By</th>
                                <td><?= htmlspecialchars($request['created_by_name']) ?> <span class="text-muted small">(<?= date('d M Y H:i', strtotime($request['created_at'])) ?>)</span></td>
                            </tr>
                            <?php if($request['status'] === 'Approved'): ?>
                                <tr>
                                    <th class="bg-light">Approved By</th>
                                    <td class="text-success"><i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($request['approved_by_name'] ?? '') ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Linked Penalties details -->
            <?php if (!empty($penalties)): ?>
            <div class="card__wrapper shadow-sm mb-4">
                <div class="card__header">
                    <h6 class="card__title mb-0 text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Deducted Penalties List</h6>
                </div>
                <div class="card__body p-3">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr class="table-light">
                                <th>Penalty ID</th>
                                <th>Date</th>
                                <th>Reason</th>
                                <th class="text-end">Amount (৳)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($penalties as $p): ?>
                                <tr>
                                    <td>PEN-<?= $p['id'] ?></td>
                                    <td><?= date('d M Y', strtotime($p['penalty_date'])) ?></td>
                                    <td><?= htmlspecialchars($p['reason']) ?></td>
                                    <td class="text-end text-danger"><?= number_format($p['penalty_amount'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

        </div>
        
        <!-- Sidebar Financials -->
        <div class="col-lg-4">
            <div class="card__wrapper shadow-sm border-top border-primary border-4 mb-4">
                <div class="card__body p-4 text-center">
                    <h6 class="text-uppercase text-muted fw-bold mb-4">Financial Summary</h6>
                    
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">Invoice Amount</span>
                        <span class="fw-bold fs-5">৳ <?= number_format($request['invoice_amount'], 2) ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-2 text-danger">
                        <span><i class="bi bi-dash-circle me-1"></i> Penalties Deducted</span>
                        <span class="fw-bold fs-5">৳ <?= number_format($request['penalty_deducted'], 2) ?></span>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-success fw-bold fs-5">Net Payable</span>
                        <span class="fw-bold fs-3 text-success">৳ <?= number_format($request['net_payable'], 2) ?></span>
                    </div>

                </div>
            </div>

            <!-- Approval Action -->
            <?php if(hasPermission('vendor_payment_approve') || in_array($_SESSION['user']['role_id'], [1,2,3])): ?>
                <?php if($request['status'] === 'Pending'): ?>
                    <div class="card__wrapper shadow-sm border-warning">
                        <div class="card__body p-4 text-center">
                            <h6 class="fw-bold mb-3"><i class="bi bi-shield-lock me-2"></i>Accounts Action</h6>
                            <p class="small text-muted mb-4">Review the financial summary before approving this payment. Handled correctly by workflow.</p>
                            
                            <form action="<?= $base_url ?>/con-vendor-payment-approve/<?= $request['id'] ?>" method="POST" class="d-grid gap-2">
                                <button type="submit" class="btn btn-success fw-bold py-2" onclick="return confirm('Ensure all physical paperwork is valid before Approving. Proceed?');">
                                    <i class="bi bi-check-circle me-1"></i> Approve Payment
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

    </div>
</section>

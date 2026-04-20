<div class="pagetitle d-flex justify-content-between align-items-center">
    <div>
        <h1><?= htmlspecialchars($page_title) ?></h1>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Vendor Payment Requests</li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-vendor-payment-create" class="btn btn-primary btn-sm shadow-sm">
        <i class="bi bi-plus-circle me-1"></i> New Request
    </a>
</div>

<section class="section">
    <div class="card__wrapper shadow-sm">
        <div class="card__header">
            <h6 class="card__title mb-0"><i class="bi bi-card-list me-2"></i>Vendor Payment List</h6>
        </div>
        <div class="card__body p-4">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle datatable">
                    <thead class="table-light">
                        <tr>
                            <th>Req ID</th>
                            <th>Date</th>
                            <th>Project</th>
                            <th>Vendor</th>
                            <th class="text-end">Invoice (৳)</th>
                            <th class="text-end text-danger">- Penalty (৳)</th>
                            <th class="text-end text-success">Net Payable</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                        <tr>
                            <td class="fw-bold">VPR-<?= $req['id'] ?></td>
                            <td><?= date('d M Y', strtotime($req['payment_date'])) ?></td>
                            <td><?= htmlspecialchars($req['project_name']) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($req['vendor_name']) ?></span></td>
                            
                            <td class="text-end"><?= number_format($req['invoice_amount'], 2) ?></td>
                            <td class="text-end text-danger"><?= number_format($req['penalty_deducted'], 2) ?></td>
                            <td class="text-end fw-bold text-success"><?= number_format($req['net_payable'], 2) ?></td>
                            
                            <td>
                                <?php if($req['status'] === 'Approved'): ?>
                                    <span class="badge bg-success">Approved</span>
                                <?php elseif($req['status'] === 'Rejected'): ?>
                                    <span class="badge bg-danger">Rejected</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td><small><?= htmlspecialchars($req['created_by_name'] ?? '') ?></small></td>
                            <td class="text-center">
                                <a href="<?= $base_url ?>/con-vendor-payment-show/<?= $req['id'] ?>" class="btn btn-sm btn-info text-white" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

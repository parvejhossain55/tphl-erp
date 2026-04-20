<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Purchase Orders</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section">
    <div class="card__wrapper shadow-sm">
        <div class="card__body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped custom-table mb-0 multiple-table">
                    <thead class="table-light">
                        <tr>
                            <th>PO #</th>
                            <th>Project</th>
                            <th class="text-end">Total Amount</th>
                            <th>Status</th>
                            <th>Order Date</th>
                            <th>Created By</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($orders)): ?>
                            <?php foreach ($orders as $po): ?>
                            <tr>
                                <td><span class="fw-bold text-primary">PO-<?= $po['id'] ?></span></td>
                                <td class="fw-bold"><?= htmlspecialchars($po['project_name']) ?></td>
                                <td class="text-end">৳ <?= number_format($po['total_amount'] ?? 0, 2) ?></td>
                                <td>
                                    <?php
                                    $badge = match($po['status']) {
                                        'Issued'             => 'bg-info',
                                        'Partially Received' => 'bg-warning text-dark',
                                        'Fully Received'     => 'bg-success',
                                        'Cancelled'          => 'bg-danger',
                                        default              => 'bg-secondary',
                                    };
                                    ?>
                                    <span class="badge <?= $badge ?>"><?= $po['status'] ?></span>
                                </td>
                                <td class="text-muted small"><?= date('d M Y', strtotime($po['order_date'])) ?></td>
                                <td><?= htmlspecialchars($po['created_by_name'] ?? '—') ?></td>
                                <td class="text-center">
                                    <a href="<?= $base_url ?>/con-purchase-order/<?= $po['id'] ?>"
                                       class="btn btn-sm btn-outline-info" title="View PO">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-clipboard-x me-2"></i>No purchase orders yet.
                                    POs are auto-generated from approved CST comparisons.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

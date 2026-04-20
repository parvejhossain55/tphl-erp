<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Purchase Requests</li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-purchase-request-create" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i> New Purchase Request
    </a>
</div>

<section class="section">
    <div class="card__wrapper shadow-sm">
        <div class="card__body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped custom-table mb-0 multiple-table">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Project</th>
                            <th class="text-center">Items</th>
                            <th class="text-end">Est. Total</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Date</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($requests)): ?>
                            <?php foreach ($requests as $i => $r): ?>
                            <tr>
                                <td><span class="fw-bold text-muted">PR-<?= $r['id'] ?></span></td>
                                <td class="fw-bold"><?= htmlspecialchars($r['project_name']) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border"><?= $r['item_count'] ?> items</span>
                                </td>
                                <td class="text-end">৳ <?= number_format($r['estimated_total'] ?? 0, 2) ?></td>
                                <td>
                                    <?php
                                    $badge = match($r['status']) {
                                        'Approved'  => 'bg-success',
                                        'PO Issued' => 'bg-primary',
                                        'Rejected'  => 'bg-danger',
                                        'Submitted' => 'bg-info',
                                        default     => 'bg-warning text-dark',
                                    };
                                    ?>
                                    <span class="badge <?= $badge ?>"><?= $r['status'] ?></span>
                                </td>
                                <td><?= htmlspecialchars($r['created_by_name'] ?? '—') ?></td>
                                <td class="text-muted small"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                                <td class="text-center" style="white-space:nowrap;">
                                    <a href="<?= $base_url ?>/con-purchase-request/<?= $r['id'] ?>"
                                       class="btn btn-sm btn-outline-info" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if ($r['status'] === 'Approved'): ?>
                                        <a href="<?= $base_url ?>/con-quotation-create/<?= $r['id'] ?>"
                                           class="btn btn-sm btn-outline-success ms-1" title="Add Quotation">
                                            <i class="bi bi-receipt"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="bi bi-clipboard-x me-2"></i>No purchase requests yet.
                                    <a href="<?= $base_url ?>/con-purchase-request-create" class="ms-2">Create one →</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

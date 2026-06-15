<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
       
    </div>
    <div class="mb-4 d-flex gap-2 align-items-center">
        <?php if ($_SESSION['user']['role_id'] == 12): ?>
        <a href="<?= $base_url ?>/con-wastage-create" class="btn btn-success btn-sm fw-bold">
            <i class="bi bi-plus-circle me-1"></i> Add Wastage
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="card__wrapper shadow-sm">
    <div class="card__body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped custom-table mb-0 multiple-table">
                <thead class="table-light">
                    <tr>
                        <th>ID #</th>
                        <th>Project</th>
                        <th>Warehouse</th>
                        <th>Type</th>
                        <th class="text-end">Selling Amount</th>
                        <th>Status</th>
                        <th>Recorded By</th>
                        <th>Date</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($wastages)): ?>
                        <?php foreach ($wastages as $w): ?>
                        <tr>
                            <td><span class="fw-bold text-primary">WST-<?= $w['id'] ?></span></td>
                            <td class="fw-bold"><?= htmlspecialchars($w['project_name']) ?></td>
                            <td><?= htmlspecialchars($w['warehouse_name']) ?></td>
                            <td>
                                <?php if (($w['wastage_type'] ?? '') === 'storage_damage'): ?>
                                    <span class="badge bg-secondary">Storage Damage</span>
                                <?php else: ?>
                                    <span class="badge bg-dark">Selling Scrap</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-bold text-success">৳ <?= number_format($w['total_selling_amount'], 2) ?></td>
                            <td>
                                <?php
                                $badge = match($w['status']) {
                                    'Pending QC'       => 'bg-warning text-dark',
                                    'Pending Purchase' => 'bg-info',
                                    'Pending Payment'  => 'bg-primary',
                                    'Completed'        => 'bg-success',
                                    'Rejected'         => 'bg-danger',
                                    default            => 'bg-secondary',
                                };
                                ?>
                                <span class="badge <?= $badge ?>"><?= $w['status'] ?></span>
                            </td>
                            <td><?= htmlspecialchars($w['recorded_by_name'] ?? '—') ?></td>
                            <td class="text-muted small"><?= date('d M Y', strtotime($w['created_at'])) ?></td>
                            <td class="text-center">
                                <a href="<?= $base_url ?>/con-wastage-show/<?= $w['id'] ?>"
                                   class="btn btn-sm btn-outline-info" title="View Details">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-clipboard-x me-2"></i>No wastage records found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

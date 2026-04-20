<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Vendor Penalties</li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-vendor-penalty-create" class="btn btn-danger btn-sm">
        <i class="bi bi-exclamation-triangle me-1"></i> Issue Penalty
    </a>
</div>

<section class="section">
    <!-- Stats Cards -->
    <div class="row  mb-4">
        <div class="col-md-4">
            <div class="card__wrapper shadow-sm">
                <div class="card__body p-3 text-center">
                    <div class="fs-3 fw-bold text-danger"><?= $stats['total_penalties'] ?? 0 ?></div>
                    <div class="small text-muted">Total Penalties</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card__wrapper shadow-sm">
                <div class="card__body p-3 text-center">
                    <div class="fs-3 fw-bold text-warning">৳ <?= number_format($stats['total_amount'] ?? 0, 0) ?></div>
                    <div class="small text-muted">Total Penalty Amount</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card__wrapper shadow-sm">
                <div class="card__body p-3 text-center">
                    <div class="fs-3 fw-bold text-info"><?= $stats['vendors_affected'] ?? 0 ?></div>
                    <div class="small text-muted">Vendors Affected</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Penalty Table -->
    <div class="card__wrapper shadow-sm">
        <div class="card__body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped custom-table mb-0 multiple-table">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Vendor</th>
                            <th>Category</th>
                            <th class="text-end">Amount</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Date</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($penalties)): ?>
                            <?php foreach ($penalties as $i => $pen): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <a href="<?= $base_url ?>/con-vendor/<?= $pen['vendor_id'] ?>" class="fw-bold text-primary">
                                        <?= htmlspecialchars($pen['vendor_name']) ?>
                                    </a>
                                </td>
                                <td><span class="badge bg-warning text-dark"><?= htmlspecialchars($pen['penalty_category']) ?></span></td>
                                <td class="text-end fw-bold text-danger"><?= ($pen['penalty_amount'] > 0) ? '৳ ' . number_format($pen['penalty_amount'], 2) : '—' ?></td>
                                <td class="small"><?= htmlspecialchars(mb_strimwidth($pen['reason'] ?? '', 0, 50, '...')) ?></td>
                                <td>
                                    <?php
                                    $pBadge = match($pen['penalty_status']) {
                                        'Approved' => 'bg-success',
                                        'Rejected' => 'bg-danger',
                                        default    => 'bg-secondary',
                                    };
                                    ?>
                                    <span class="badge <?= $pBadge ?>"><?= $pen['penalty_status'] ?></span>
                                </td>
                                <td><?= htmlspecialchars($pen['created_by_name'] ?? '—') ?></td>
                                <td class="text-muted small"><?= date('d M Y', strtotime($pen['created_at'])) ?></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            onclick="confirmDeletePenalty(<?= $pen['id'] ?>, '<?= htmlspecialchars(addslashes($pen['vendor_name'])) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="bi bi-check-circle text-success me-2"></i>No vendor penalties recorded.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- Delete Modal -->
<div class="modal fade" id="deletePenaltyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Delete Penalty</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Remove penalty for <strong id="deletePenaltyVendor"></strong>?</p>
                <p class="text-muted small">Vendor rating will be recalculated.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <form id="deletePenaltyForm" action="<?= $base_url ?>/con-vendor-penalty-delete" method="POST" class="d-inline">
                    <input type="hidden" name="id" id="deletePenaltyId">
                    <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-trash me-1"></i> Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDeletePenalty(id, vendorName) {
    document.getElementById('deletePenaltyId').value = id;
    document.getElementById('deletePenaltyVendor').textContent = vendorName;
    new bootstrap.Modal(document.getElementById('deletePenaltyModal')).show();
}
</script>

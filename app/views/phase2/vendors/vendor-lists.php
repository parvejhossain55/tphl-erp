<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Vendors</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex flex-column flex-sm-row gap-2">
        <a href="<?= $base_url ?>/con-vendor-create" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i> Add Vendor
        </a>
    </div>
</div>

<section class="section">
    <div class="card__wrapper shadow-sm">
        <div class="card__body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped custom-table mb-0 multiple-table">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Vendor Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th class="text-center">PO Orders</th>
                            <th class="text-center">Penalties</th>
                            <th>Rating</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($vendors)): ?>
                            <?php foreach ($vendors as $i => $v): ?>
                            <?php
                            $hasCompany = !empty(trim($v['company_name'] ?? ''));
                            $displayName = $hasCompany ? trim($v['company_name']) : $v['name'];
                            ?>
                            <tr>
                                <td><?= ($offset ?? 0) + $i + 1 ?></td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($displayName) ?></div>
                                    <?php if ($hasCompany && !empty($v['name'])): ?>
                                        <div class="small text-muted"><i class="bi bi-person me-1"></i><?= htmlspecialchars($v['name']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($v['phone'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($v['email'] ?? '—') ?></td>
                                <td class="text-center">
                                    <span class="badge bg-info"><?= $v['total_orders'] ?? 0 ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if (($v['penalty_count'] ?? 0) > 0): ?>
                                        <span class="badge bg-danger"><?= $v['penalty_count'] ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $rating = (float)($v['rating'] ?? 0);
                                    $stars  = '';
                                    for ($s = 1; $s <= 5; $s++) {
                                        $stars .= ($s <= round($rating))
                                            ? '<i class="bi bi-star-fill text-warning"></i>'
                                            : '<i class="bi bi-star text-muted"></i>';
                                    }
                                    echo $stars;
                                    ?>
                                    <span class="ms-1 small text-muted">(<?= number_format($rating, 1) ?>)</span>
                                </td>
                                <td>
                                    <?php
                                    $vSt = $v['status'] ?? 'active';
                                    if ($vSt === 'active') {
                                        echo '<span class="badge bg-success">Active</span>';
                                    } elseif ($vSt === 'pending_approval') {
                                        echo '<span class="badge bg-warning text-dark"><i class="bi bi-clock-history me-1"></i>Pending Approval</span>';
                                    } elseif ($vSt === 'rejected') {
                                        echo '<span class="badge bg-danger">Rejected</span>';
                                    } else {
                                        echo '<span class="badge bg-secondary">' . htmlspecialchars(ucfirst($vSt)) . '</span>';
                                    }
                                    ?>
                                </td>
                                <td class="text-center" style="white-space:nowrap;">
                                    <a href="<?= $base_url ?>/con-vendor/<?= $v['id'] ?>"
                                       class="btn btn-sm btn-outline-info" title="View Profile">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= $base_url ?>/con-vendor-edit/<?= $v['id'] ?>"
                                       class="btn btn-sm btn-outline-warning ms-1" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger ms-1"
                                            title="Delete" onclick="confirmDeleteVendor(<?= $v['id'] ?>, '<?= htmlspecialchars(addslashes($displayName)) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="bi bi-people me-2"></i>No vendors yet.
                                    <a href="<?= $base_url ?>/con-vendor-create" class="ms-2">Add one →</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div class="row my-3">
                <div class="col-12 d-flex justify-content-center">
                    <?= paginateLinksSecondary($base_url . "/con-vendors", $currentPage, $totalPages); ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteVendorModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Confirm Delete</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete vendor <strong id="deleteVendorName"></strong>?</p>
                <p class="text-muted small">This will permanently remove the vendor record. PO history will remain.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteVendorForm" action="<?= $base_url ?>/con-vendor-delete" method="POST" class="d-inline">
                    <input type="hidden" name="id" id="deleteVendorId">
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-trash me-1"></i> Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDeleteVendor(id, name) {
    document.getElementById('deleteVendorId').value = id;
    document.getElementById('deleteVendorName').textContent = name;
    var modal = new bootstrap.Modal(document.getElementById('deleteVendorModal'));
    modal.show();
}
</script>

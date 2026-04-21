<div class="mb-4 pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <!-- <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">BOQ</li>
            </ol>
        </nav> -->
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $base_url ?>/con-boq-create" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i> New BOQ
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
                            <th>Project</th>
                            <th>Version</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Date</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($boqs)): ?>
                            <?php foreach ($boqs as $i => $boq): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($boq['project_name']) ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border">v<?= htmlspecialchars($boq['version']) ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $badge = match ($boq['status']) {
                                            'Approved'  => 'bg-success',
                                            'Rejected'  => 'bg-danger',
                                            'Submitted' => 'bg-info',
                                            default     => 'bg-warning text-dark',
                                        };
                                        ?>
                                        <span class="badge <?= $badge ?>"><?= $boq['status'] ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($boq['created_by_name'] ?? '—') ?></td>
                                    <td class="text-muted small"><?= date('d M Y', strtotime($boq['created_at'])) ?></td>
                                    <td class="text-center" style="white-space:nowrap;">
                                        <a href="<?= $base_url ?>/con-boq-show/<?= $boq['id'] ?>"
                                            class="btn btn-sm btn-outline-info" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($boq['status'] !== 'Approved'): ?>
                                            <a href="<?= $base_url ?>/con-boq-edit/<?= $boq['id'] ?>"
                                                class="btn btn-sm btn-outline-warning ms-1" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger ms-1 btn-delete-boq"
                                                data-id="<?= $boq['id'] ?>"
                                                data-name="<?= htmlspecialchars($boq['project_name']) ?>"
                                                title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-clipboard-x me-2"></i>No BOQ records yet.
                                    <a href="<?= $base_url ?>/con-boq-create" class="ms-2">Create one →</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteBoqModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="deleteBoqForm" method="POST" action="<?= $base_url ?>/con-boq-delete">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the BOQ for <strong id="deleteBoqName"></strong>?
                        All associated items will be removed.</p>
                    <input type="hidden" name="id" id="deleteBoqId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i> Delete
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Delete modal — vanilla JS
        document.querySelectorAll('.btn-delete-boq').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = this.getAttribute('data-id');
                var name = this.getAttribute('data-name');
                document.getElementById('deleteBoqId').value = id;
                document.getElementById('deleteBoqName').textContent = name;
                var modal = new bootstrap.Modal(document.getElementById('deleteBoqModal'));
                modal.show();
            });
        });
    });
</script>
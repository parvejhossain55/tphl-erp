<div class="container-fluid pt-4">
    <div class="row">
        <div class="col-12">
            <div class="card__wrapper">
                <div class="card__header d-flex justify-content-between align-items-center">
                    <h5 class="card__title mb-0">Project-wise Bill of Quantities (BOQ)</h5>
                    <a href="<?= $base_url ?>/con-boq-create" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus me-1"></i> New BOQ
                    </a>
                </div>
                <div class="card__body">
                    <div class="table__wrapper mt-3">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Project Name</th>
                                    <th>Version</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Date</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($boqs)): ?>
                                    <?php foreach ($boqs as $index => $boq): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><strong><?= htmlspecialchars($boq['project_name']) ?></strong></td>
                                            <td><?= htmlspecialchars($boq['version']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $boq['status'] == 'Approved' ? 'success' : ($boq['status'] == 'Rejected' ? 'danger' : 'warning') ?>">
                                                    <?= $boq['status'] ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($boq['created_by_name']) ?></td>
                                            <td><?= date('d M, Y', strtotime($boq['created_at'])) ?></td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="<?= $base_url ?>/con-boq-show/<?= $boq['id'] ?>" class="btn btn-sm btn-info" title="View Details">
                                                        <i class="fa-solid fa-eye text-white"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger removeBtnCust" data-id="<?= $boq['id'] ?>" data-route="con-boq-delete" title="Delete">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">No BOQ records found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal confirm delete as per project pattern -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="" id="deleteForm" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this BOQ? This will also remove all associated items.</p>
                    <input type="hidden" name="id" id="delete_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.removeBtnCust').on('click', function() {
        var id = $(this).data('id');
        var route = $(this).data('route');
        $('#delete_id').val(id);
        $('#deleteForm').attr('action', '<?= $base_url ?>/' + route);
        $('#deleteModal').modal('show');
    });
});
</script>

<div class="row"><div class="col-12">
    <div class="container-fluid">
        <!-- Breadcrumb -->
        <div class="row align-items-center mb-4">
            <div class="col-sm-6">
                <div class="page-title-box">
                    <h4 class="mb-0">Manage QC Test Types</h4>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/qc-dashboard">QC Dashboard</a></li>
                        <li class="breadcrumb-item active">Test Types</li>
                    </ol>
                </div>
            </div>
            <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModal">
                    <i class="fal fa-plus me-1"></i> Add New Test Type
                </button>
            </div>
        </div>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered table-striped" id="testTypesTable">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Test Type Name</th>
                                <th>Description</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($testTypes)): ?>
                                <?php foreach($testTypes as $index => $type): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td class="fw-bold text-primary"><?= htmlspecialchars($type['name']) ?></td>
                                        <td><?= htmlspecialchars($type['description'] ?? 'N/A') ?></td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-info text-white me-1" 
                                                data-bs-toggle="modal" data-bs-target="#editModal<?= $type['id'] ?>" title="Edit">
                                                <i class="fal fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                data-bs-toggle="modal" data-bs-target="#deleteModal<?= $type['id'] ?>" title="Delete">
                                                <i class="fal fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal<?= $type['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Test Type</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="<?= $GLOBALS['base_url'] ?>/qc-test-types-update" method="POST">
                                                    <div class="modal-body text-start">
                                                        <input type="hidden" name="id" value="<?= $type['id'] ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Test Type Name <span class="text-danger">*</span></label>
                                                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($type['name']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Description</label>
                                                            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($type['description'] ?? '') ?></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Delete Modal -->
                                    <div class="modal fade" id="deleteModal<?= $type['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-sm">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title text-white">Confirm Delete</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="<?= $GLOBALS['base_url'] ?>/qc-test-types-delete" method="POST">
                                                    <div class="modal-body text-center">
                                                        <input type="hidden" name="id" value="<?= $type['id'] ?>">
                                                        <i class="fal fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                                                        <p>Are you sure you want to delete <strong><?= htmlspecialchars($type['name']) ?></strong>?</p>
                                                    </div>
                                                    <div class="modal-footer justify-content-center">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No Test Types found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
    </div>
</div></div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Test Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= $GLOBALS['base_url'] ?>/qc-test-types-store" method="POST">
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Test Type Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g., Cylinder Compressive Strength" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Short description of this test..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Create Test Type</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        if ($('#testTypesTable tbody tr').length > 1 || !$('#testTypesTable tbody tr td').hasClass('text-center')) {
            $('#testTypesTable').DataTable({
                "pageLength": 10,
                "ordering": false
            });
        }
    });
</script>

<?php
$page_title = "Design Types";
$page = 'Design Types';
$addPage = true;
$show_breadcrumb = true;
?>

<div class="breadcrumb__btn my-3">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDesignTypeModal">
        <i class="fa-light fa-plus me-1"></i> Add Design Type
    </button>
</div>

<div class="row mt-4">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 hover ">
                    <thead>
                        <tr class="table__title">
                            <th>#</th>
                            <th>Design Type Name</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php if (!empty($records)): ?>
                            <?php foreach ($records as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($row['name']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $row['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($row['status']) ?>
                                        </span>
                                    </td>
                                    <td class="table__icon-box">
                                        <div class="d-flex align-items-center justify-content-start gap-10">
                                            <button type="button"
                                                class="table__icon edit"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editDesignTypeModal"
                                                data-id="<?= $row['id'] ?>"
                                                data-name="<?= htmlspecialchars($row['name']) ?>"
                                                data-status="<?= $row['status'] ?>">
                                                <i class="fa-sharp fa-light fa-pen"></i>
                                            </button>
                                            <button class="table__icon delete removeBtnCust"
                                                data-id="<?= $row['id'] ?>"
                                                data-name="<?= htmlspecialchars($row['name']) ?>"
                                                data-action="delete-design-type">
                                                <i class="fa-regular fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No design types found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Design Type Modal -->
<div class="modal fade" id="addDesignTypeModal" tabindex="-1" aria-labelledby="addDesignTypeLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDesignTypeLabel">Add Design Type</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="employee__info">
                    <form action="<?= $GLOBALS['base_url'] ?>/con-design-types/store" method="POST">
                        <div class="card__wrapper mb-20">
                            <div class="row g-20 gy-20">
                                <div class="col-lg-12">
                                    <div class="from__input-box">
                                        <div class="form__input-title">
                                            <label>Design Type Name <span>*</span></label>
                                        </div>
                                        <div class="form__input">
                                            <input type="text" class="form-control" name="name" required placeholder="e.g. Architectural, Structural">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="from__input-box">
                                        <div class="form__input-title">
                                            <label>Status</label>
                                        </div>
                                        <div class="form__input">
                                            <select name="status" class="form-control">
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="submit__btn text-center">
                            <button class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Design Type Modal -->
<div class="modal fade" id="editDesignTypeModal" tabindex="-1" aria-labelledby="editDesignTypeLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDesignTypeLabel">Edit Design Type</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/con-design-types/update" method="POST">
                    <input type="hidden" name="id" id="edit_design_type_id">
                    <div class="card__wrapper mb-20">
                        <div class="row g-20 gy-20">
                            <div class="col-lg-12">
                                <div class="from__input-box">
                                    <div class="form__input-title">
                                        <label>Design Type Name <span>*</span></label>
                                    </div>
                                    <div class="form__input">
                                        <input type="text" class="form-control" name="name" id="edit_design_type_name" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="from__input-box">
                                    <div class="form__input-title">
                                        <label>Status</label>
                                    </div>
                                    <div class="form__input">
                                        <select name="status" id="edit_design_type_status" class="form-control">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="submit__btn text-center">
                        <button class="btn btn-primary" type="submit">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const base_url = "<?= $GLOBALS['base_url'] ?>";

    document.addEventListener("DOMContentLoaded", function () {
        const editModal = document.getElementById('editDesignTypeModal');
        editModal.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            document.getElementById('edit_design_type_id').value      = btn.getAttribute('data-id');
            document.getElementById('edit_design_type_name').value    = btn.getAttribute('data-name');
            document.getElementById('edit_design_type_status').value  = btn.getAttribute('data-status');
        });
    });
</script>

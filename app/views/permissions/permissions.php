<?php
$page_title = "Create Permission";
$page = 'Add Permission ';
$show_breadcrumb = true;

?>
<!-- <div class="row">
    <div class="card__wrapper">

    </div>
</div> -->
<?php
$page_title = "department Page";
$page = 'Department';
$addPage = true;
$show_breadcrumb = true;

?>
<div class="breadcrumb__btn my-4 text-align-end">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNewPermission">Add Permission</button>
</div>

<div class="row">

    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 multiple_tables">
                    <thead>
                        <tr class="table__title table__sort">
                            <th class="no-sort sorting"><input type="checkbox" id="selectall"></input>
                            </th>
                            <th>S/L</th>
                            <th>Deparment Name</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">


                        <?php foreach ($permissions as $index => $permission): ?>
                            <tr>
                                <!-- Checkbox with department id -->
                                <td><input type="checkbox" class="selectedId" name="selectedId[]" value="<?= $permission['id'] ?>"></td>

                                <!-- Serial number -->
                                <td>#<?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($permission['name']) ?></td>
                                <td>
                                    <?php if ($permission['status'] == 1): ?>
                                        <span class="bd-badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="bd-badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Action buttons -->
                                <td>
                                    <div class="d-flex align-items-center justify-content-start gap-10">
                                        <button type="button" class="table__icon edit"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editPermission"
                                            data-id="<?= $permission['id'] ?>"
                                            data-name="<?= htmlspecialchars($permission['name']) ?>"
                                            data-status="<?= $permission['status'] ?>">
                                            <i class="fa-sharp fa-light fa-pen"></i>
                                        </button>

                                        <button class="removeBtnCust table__icon delete"
                                            data-id="<?= $permission['id'] ?>"
                                            data-name="<?= $permission['name'] ?>"
                                            data-action="delete-permission">
                                            <i class="fa-regular fa-trash"></i>
                                        </button>

                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>



                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- new permission modal start -->
<div id="addNewPermission" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New permission</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close"><i
                        class="fa-solid fa-xmark-large"></i></button>
            </div>
            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/store-permission" method="POST">
                    <div class="row">
                        <div class="col-xxl-12">
                            <div class="row gy-10">
                                <!-- Permission Name -->
                                <div class="col-lg-12">
                                    <div class="from__input-box">
                                        <div class="form__input-title">
                                            <label for="name">Permission Name<span>*</span></label>
                                        </div>
                                        <div class="form__input">
                                            <input class="form-control" name="name" id="name" type="text" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="col-lg-12">
                                    <div class="form__input-box">
                                        <div class="form__input-title">
                                            <label for="status">Status <span>*</span></label>
                                        </div>
                                        <div class="form__input">
                                            <select class="form-control" name="status" id="status" required>
                                                <option value="">-- Select Status --</option>
                                                <option value="1">Active</option>
                                                <option value="0">In Active</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="submit__btn text-center mb-20 mt-3">
                        <button class="btn btn-primary" type="submit">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- new permission modal end -->
<!-- edit department modal start -->
<div id="editPermission" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Permission</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>

            <div class="modal-body">
                <form id="editPermissionForm" action="<?= $GLOBALS['base_url'] ?>/update-permission" method="POST">
                    <input type="hidden" name="id" id="editPermissionId">

                    <div class="row gy-10">
                        <div class="col-xl-12">
                            <div class="card__wrapper">
                                <div class="row gy-20">

                                    <!-- Department Name -->
                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="edit_permission">Permission Name <span>*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" name="name" id="edit_permission" type="text" required>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Status -->
                                    <div class="col-md-6">
                                        <div class="form__input-title">
                                            <label>Status</label>
                                        </div>

                                        <div class="radio d-flex align-items-center gap-20">
                                            <div class="form-check">
                                                <input name="status" class="form-check-input" type="radio" value="1" id="editStatusActive">
                                                <label class="form-check-label" for="editStatusActive">Active</label>
                                            </div>
                                            <div class="form-check">
                                                <input name="status" class="form-check-input" type="radio" value="0" id="editStatusInactive">
                                                <label class="form-check-label" for="editStatusInactive">Inactive</label>
                                            </div>
                                        </div>
                                    </div>

                                </div> <!-- row -->
                            </div>
                        </div>
                    </div>

                    <!-- Update Button -->
                    <div class="submit__btn text-center">
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
<!-- edit department modal end -->
<script>
    const base_url = "<?= $GLOBALS['base_url'] ?>"; // PHP outputs the correct base URL



    document.addEventListener("DOMContentLoaded", function() {
        const modal = document.getElementById('editPermission');

        // Attach listener only once when modal is shown
        modal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget; // the clicked button

            // Get data attributes
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const status = button.getAttribute('data-status');

            // Fill form fields
            document.getElementById('editPermissionId').value = id;
            document.getElementById('edit_permission').value = name;
        
            if (status === "1") {
                document.getElementById('editStatusActive').checked = true;
            } else {
                document.getElementById('editStatusInactive').checked = true;
            }
        });
    });
</script>
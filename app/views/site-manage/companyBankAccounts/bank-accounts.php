<?php
$page_title = "department Page";
$page = 'Department';
$addPage = true;
$show_breadcrumb = true;

?>
<div class="breadcrumb__btn my-4 text-align-end">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNewDepartment">Add Company Account</button>
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
                            <th>Bank Name</th>
                            <th>Account Name</th>
                            <th>Account Number</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">


                        <?php foreach ($accounts as $index => $account): ?>
                            <tr>
                                <!-- Checkbox with department id -->
                                <td><input type="checkbox" class="selectedId" name="selectedId[]" value="<?= $account['id'] ?>"></td>

                                <!-- Serial number -->
                                <td>#<?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($account['name']) ?></td>
                                <td><?= htmlspecialchars($account['account_name']) ?></td>
                                <td><?= htmlspecialchars($account['account_number']) ?></td>
                                <td>
                                    <?php if ($account['status'] == 'active'): ?>
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
                                            data-bs-target="#editDepartment"
                                            data-id="<?= $account['id'] ?>"
                                            data-name="<?= htmlspecialchars($account['name']) ?>"
                                            data-status="<?= $account['status'] ?>">
                                            <i class="fa-sharp fa-light fa-pen"></i>
                                        </button>

                                        <button class="removeBtnCust table__icon delete"
                                            data-id="<?= $account['id'] ?>"
                                            data-name="<?= $account['name'] ?>"
                                            data-action="delete-department">
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

<!-- new lead modal start -->
<div id="addNewDepartment" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Bank Account</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close"><i
                        class="fa-solid fa-xmark-large"></i></button>
            </div>
            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/store-bank-account" method="POST">

                    <div class="row gy-10">
                        <div class="col-xl-12">
                            <div class="card__wrapper">
                                <div class="row gy-20">
                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="department">Bank Name with Branch<span>*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" name="bank_name" id="bank_name" type="text">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="department">Account Holder Name<span>*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" name="account_name" id="account_name" type="text">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="department">Account Number<span>*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" name="account_number" id="account_number" type="text">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form__input-title">
                                            <label> Status</label>
                                        </div>
                                        <div class="radio d-flex align-items-center gap-20">
                                            <div class="form-check">
                                                <input name="status" class="form-check-input" type="radio" value="active" id="statusRadioActive" checked>
                                                <label class="form-check-label" for="statusRadioActive">Active</label>
                                            </div>
                                            <div class="form-check">
                                                <input name="status" class="form-check-input" type="radio" value="inactive" id="statusRadioInactive">
                                                <label class="form-check-label" for="statusRadioInactive">Inactive</label>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="submit__btn text-center">
                        <button class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- new lead modal end -->
<!-- edit department modal start -->
<div id="editDepartment" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Bank Account</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>

            <div class="modal-body">
                <form id="editDepartmentForm" action="<?= $GLOBALS['base_url'] ?>/update-bank-account" method="POST">
                    <input type="hidden" name="id" id="edit_department_id">

                    <div class="row gy-10">
                        <div class="col-xl-12">
                            <div class="card__wrapper">
                                <div class="row gy-20">

                                    <!-- Bank Name -->
                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="edit_department">Bank Name <span>*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" name="bank_name" id="edit_department" type="text" required>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Bank Name -->
                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="edit_account">Account Holder Name<span>*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" name="account_name" id="edit_account" type="text" required>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Bank Name -->
                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="edit_account">Account Number <span>*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" name="account_number" id="edit_account_number" type="text" required>
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
        const modal = document.getElementById('editDepartment');

        // Attach listener only once when modal is shown
        modal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget; // the clicked button

            // Get data attributes
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const status = button.getAttribute('data-status');

            // Fill form fields
            document.getElementById('edit_department_id').value = id;
            document.getElementById('edit_department').value = name;
            console.log("Button clicked:", button);
            console.log("Department ID:", id, "Name:", name, "Status:", status);

            if (status === "1") {
                document.getElementById('editStatusActive').checked = true;
            } else {
                document.getElementById('editStatusInactive').checked = true;
            }
        });
    });
</script>
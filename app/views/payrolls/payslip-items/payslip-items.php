<?php
$page_title = "Payslip Item";
$page = 'Payslip Item';
$addPage = true;
$show_breadcrumb = true;

?>


<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

    <h4 class="mb-2 mb-md-0">
        Payslip Items
    </h4>

    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPayslipItem">Add Payslip Item</button>
    </div>

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
                            <th>Payslip Item</th>
                            <th>Type</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">


                        <?php foreach ($items as $index => $item): ?>
                            <tr>
                                <!-- Checkbox with department id -->
                                <td><input type="checkbox" class="selectedId" name="selectedId[]" value="<?= $item['id'] ?>"></td>

                                <!-- Serial number -->
                                <td>#<?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($item['name']) ?></td>
                                <td><?= $item['type'] ?? '' ?></td>
                                <!-- Action buttons -->
                                <td>
                                    <div class="d-flex align-items-center justify-content-start gap-10">
                                        <button type="button" class="table__icon edit"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editDepartment"
                                            data-id="<?= $item['id'] ?>"
                                            data-name="<?= htmlspecialchars($item['name']) ?>"
                                            data-type="<?= $item['type'] ?>">
                                            <i class="fa-sharp fa-light fa-pen"></i>
                                        </button>

                                        <button class="removeBtnCust table__icon delete"
                                            data-id="<?= $item['id'] ?>"
                                            data-name="<?= $item['name'] ?>"
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
<div id="addPayslipItem" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Lead</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close"><i
                        class="fa-solid fa-xmark-large"></i></button>
            </div>
            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/store-payslip-item" method="POST">

                    <div class="row gy-10">
                        <div class="col-xl-12">
                            <div class="card__wrapper">
                                <div class="row gy-20">
                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="department">Payslip Item Name<span>*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" name="name" id="name" type="text">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="from__input-box">
                                            <label>Item Type *</label>
                                            <select name="project_type" class="form-select">
                                                <option value="earning">Earning</option>
                                                <option value="deduction">Deduction</option>
                                            </select>
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
                <h5 class="modal-title">Edit Department</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>

            <div class="modal-body">
                <form id="editDepartmentForm" action="<?= $GLOBALS['base_url'] ?>/update-department" method="POST">
                    <input type="hidden" name="id" id="edit_department_id">

                    <div class="row gy-10">
                        <div class="col-xl-12">
                            <div class="card__wrapper">
                                <div class="row gy-20">

                                    <!-- payslip Item Name -->
                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="edit_department">Payslip Item Name <span>*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" name="name" id="edit_department" type="text" required>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Status -->
                                    <div class="col-md-6">
                                        <div class="from__input-box">
                                            <label>Communication Type</label>
                                            <select name="type" class="form-select">
                                                <option value="earning" >Earning</option>
                                                <option value="deduction">Deduction</option>
                                            </select>
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
<?php
$page_title = "designation Page";
$page = 'designation';
$addPage = true;
$show_breadcrumb = true;

?>



<div class="breadcrumb__btn my-3">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDesignation">
        Add Designation
    </button>
</div>
<div class="row">

</div>




<div class="row mt-4">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 hover multiple_tables">
                    <thead>
                        <tr class="table__title">
                            <th>Designation</th>
                            <th>Department</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php if (!empty($designations)): ?>
                            <?php foreach ($designations as $desg): ?>
                                <tr>
                                    <td class="table__designation"><?= htmlspecialchars($desg['designation_name'] ?? 'N/A') ?></td>
                                    <td class="table__department"><?= htmlspecialchars($desg['department_name'] ?? 'N/A') ?></td>
                                    <td class="table__icon-box">
                                        <div class="d-flex align-items-center justify-content-start gap-10">
                                            <button type="button"
                                                class="table__icon edit"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editDesignation"
                                                data-id="<?= $desg['id'] ?>"
                                                data-name="<?= htmlspecialchars($desg['designation_name']) ?>"
                                                data-department="<?= $desg['department_id'] ?>">
                                                <i class="fa-sharp fa-light fa-pen"></i>
                                            </button>

                                            <button class="removeBtnCust table__icon delete"
                                                data-id="<?= $desg['id'] ?>"
                                                data-name="<?= htmlspecialchars($desg['designation_name']) ?>"
                                                data-action="delete-designation">
                                                <i class="fa-regular fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center">No designations found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>



<div class="row my-3">
    <h5>Department Wise All Designations</h5>
</div>
<div class="row g-4">


    <?php if (!empty($groupedDesignations) && is_array($groupedDesignations)): ?>
        <?php foreach ($groupedDesignations as $department => $designations2): ?>
            <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-12">
                <div class="card h-100 shadow-sm border-0" style="border-radius: 12px; overflow: hidden; transition: transform 0.3s ease, box-shadow 0.3s ease;">
                    <div class="card-header border-0 py-3" style="background: linear-gradient(135deg, #085431 0%, #378c8d 100%)">
                        <h5 class="mb-0  fw-bold" style="font-size: 1.1rem; letter-spacing: 0.5px; color: #fff;">
                            <i class="bi bi-building me-2"></i><?= htmlspecialchars($department) ?>
                        </h5>
                    </div>
                    <div class="card-body p-4" style="background-color: #f8f9fa;">
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($designations2 as $desig): ?>
                                <span class="badge rounded-pill px-3 py-2" style="background-color: #fff; color: #0d8f5d; border: 2px solid #0f777a; font-size: 0.85rem; font-weight: 500; transition: all 0.3s ease; cursor: default;">
                                    <?= htmlspecialchars($desig) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-info border-0 shadow-sm" style="border-radius: 10px; background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);">
                <div class="d-flex align-items-center">
                    <i class="bi bi-info-circle-fill me-3" style="font-size: 2rem; color: #0b5242;"></i>
                    <div>
                        <h6 class="mb-1 fw-bold" style="color: #044c52;">No Designations Found</h6>
                        <p class="mb-0 text-muted">There are currently no designations available to display.</p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>




<!-- New Designation Modal Start -->
<div class="modal fade" id="addDesignation" tabindex="-1" aria-labelledby="addDesignationLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDesignationLabel">Add New Designation</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close"><i
                        class="fa-solid fa-xmark-large"></i></button>
            </div>
            <div class="modal-body">
                <div class="employee__info">
                    <form action="<?= $GLOBALS['base_url'] ?>/store-designation" method="POST">

                        <div class="card__wrapper mb-20">
                            <div class="row g-20 gy-20 align-items-center justify-content-center">
                                <div class="col-lg-12">
                                    <div class="from__input-box">
                                        <div class="form__input-title">
                                            <label for="designation">Designation <span>*</span></label>
                                        </div>
                                        <div class="form__input">
                                            <input class="form-control" name="name" id="name" type="text">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="from__input-box">
                                        <div class="form__input-title">
                                            <label>Employee Department <span>*</span></label>
                                        </div>
                                        <div class="form__input">
                                            <select class="form-control" name="department_id">
                                                <option value="">Select Department </option>
                                                <?php foreach ($departments as $dept): ?>

                                                    <option value="<?= $dept['id'] ?>"><?= $dept['name'] ?></option>
                                                <?php endforeach ?>
                                            </select>
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
</div>
<!-- New Designation Modal End -->

<!-- Designations Edit Modal Start -->
<div class="modal fade" id="editDesignation" tabindex="-1" aria-labelledby="editDesignationLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDesignationLabel">Edit Designation</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>

            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/update-designation" method="POST">
                    <input type="hidden" name="id" id="edit_designation_id">


                    <div class="card__wrapper mb-20">
                        <div class="row g-20 gy-20">

                            <!-- Designation Name -->
                            <div class="col-lg-12">
                                <div class="from__input-box">
                                    <div class="form__input-title">
                                        <label for="edit_name">Designation <span>*</span></label>
                                    </div>
                                    <div class="form__input">
                                        <input type="text" class="form-control" name="name" id="edit_desgination" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Department -->
                            <div class="col-lg-12">
                                <div class="from__input-box">
                                    <div class="form__input-title">
                                        <label for="edit_department_id">Department <span>*</span></label>
                                    </div>
                                    <div class="form__input">
                                        <select class="form-control" name="department_id" id="edit_department_id" required>
                                            <option value="">Select Department</option>

                                            <!-- PHP Loop -->
                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?= $dept['id'] ?>"><?= $dept['name'] ?></option>
                                            <?php endforeach; ?>

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

<!-- Designations Edit Modal End -->


<script>
    const base_url = "<?= $GLOBALS['base_url'] ?>"; // PHP outputs the correct base URL



    document.addEventListener("DOMContentLoaded", function() {
        const modal = document.getElementById('editDesignation');

        modal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;

            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const departmentId = button.getAttribute('data-department'); // <-- important

            document.getElementById('edit_designation_id').value = id;
            document.getElementById('edit_desgination').value = name;

            // Set selected department
            document.getElementById('edit_department_id').value = departmentId;
        });
    });
</script>
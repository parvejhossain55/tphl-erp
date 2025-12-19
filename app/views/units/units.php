<?php
$page = 'Locatiopn';
$addPage = true;
$show_breadcrumb = true;

?>
<div class="breadcrumb__btn my-4">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNewUnit">Add Unit</button>
</div>

<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 multiple_tables">
                    <thead>
                        <tr class="table__title table__sort">
                            </th>
                            <th>Unit Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php foreach ($units as $unit): ?>
                            <tr>
                                <td><?= $unit['name'] ?? '' ?></td>
                                <td>
                                    <div class="d-flex align-items-center justify-content-start gap-10">
                                        <button type="button" class="table__icon edit"
                                            data-bs-toggle="modal"
                                            data-bs-target="#unitEdit"
                                            data-id="<?= $unit['id'] ?>"
                                            data-name="<?= htmlspecialchars($unit['name']) ?>">
                                            <i class="fa-sharp fa-light fa-pen"></i>
                                        </button>

                                        <button class="removeBtnCust table__icon delete"
                                            data-id="<?= $unit['id'] ?>"
                                            data-name="<?= $unit['name'] ?>"
                                            data-action="delete-department">
                                            <i class="fa-regular fa-trash"></i>
                                        </button>
                                    </div>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<!-- new lead modal start -->
<div id="addNewUnit" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New unit</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close"><i
                        class="fa-solid fa-xmark-large"></i></button>
            </div>
            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/store-unit" method="POST">
                    <div class="row gy-10">
                        <div class="col-xl-12">
                            <div class="card__wrapper">
                                <div class="row gy-20">
                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="name">unit Name<span>*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" name="name" id="name" type="text" required>
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



<!-- lead update modal start -->
<div id="unitEdit" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Lead Update</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close"><i
                        class="fa-solid fa-xmark-large"></i></button>
            </div>
            <div class="modal-body form__edit-view">
                <form id="editDepartmentForm" action="<?= $GLOBALS['base_url'] ?>/update-unit" method="POST">
                    <input type="hidden" name="id" id="edit_unit_id">
                    <div class="row gy-10">
                        <div class="col-xl-12">
                            <div class="card__wrapper">
                                <div class="row gy-20">
                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="leadName2">Lead Name<span>*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" name="name" id="edit_unit" type="text">
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

<!-- edit department modal end -->
<script>
    const base_url = "<?= $GLOBALS['base_url'] ?>"; // PHP outputs the correct base URL



    document.addEventListener("DOMContentLoaded", function() {
        const modal = document.getElementById('unitEdit');

        // Attach listener only once when modal is shown
        modal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget; // the clicked button

            // Get data attributes
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');

            // Fill form fields
            document.getElementById('edit_unit_id').value = id;
            document.getElementById('edit_unit').value = name;
            console.log("Button clicked:", button);

        });
    });
</script>
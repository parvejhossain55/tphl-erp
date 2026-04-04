<?php
$page = 'Leads Source';
$addPage = true;
$show_breadcrumb = true;

?>
<div class="breadcrumb__btn my-4">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNewLeadSource">Add Lead Source</button>
</div>

<div class="row">



    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 multiple_tables">
                    <thead>
                        <tr class="table__title table__sort">

                            </th>
                            <th>Lead Source Name</th>

                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php foreach ($sources as $source): ?>
                            <tr>
                                <td><?= $source['name'] ?? '' ?></td>
                                <td>
                                    <span class="bd-badge <?php echo $source['is_active'] == 1 ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $source['is_active'] == 1 ? 'Active' : 'Inactive'; ?>
                                    </span>

                                <td>
                                    <div class="d-flex align-items-center justify-content-start gap-10">
                                        <button type="button" class="table__icon edit"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editLeadSource"
                                            data-id="<?= $source['id'] ?>"
                                            data-name="<?= htmlspecialchars($source['name']) ?>"
                                            data-status="<?= $source['is_active'] ?>">
                                            <i class="fa-sharp fa-light fa-pen"></i>
                                        </button>

                                        <button class="removeBtnCust table__icon delete"
                                            data-id="<?= $source['id'] ?>"
                                            data-name="<?= htmlspecialchars($source['name']) ?>"
                                            data-action="delete-leadSource">
                                            <i class="fa-regular fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach ?>


                    </tbody>
                </table>
            </div>
        </div>
    </div>



</div>


<!-- new lead modal start -->
<div id="addNewLeadSource" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Lead Source</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close"><i
                        class="fa-solid fa-xmark-large"></i></button>
            </div>
            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/store-leadSource" method="POST">
                    <div class="row gy-10">
                        <div class="col-xl-12">
                            <div class="card__wrapper">
                                <div class="row gy-20">
                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="name">Lead Source Name<span>*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" name="name" id="name" type="text" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form__input-title">
                                            <label>Status</label>
                                        </div>
                                        <div class="radio d-flex align-items-center gap-20">
                                            <div class="form-check">
                                                <input name="is_active" class="form-check-input" type="radio" value="1" id="statusActive" checked>
                                                <label class="form-check-label" for="statusActive">Active</label>
                                            </div>
                                            <div class="form-check">
                                                <input name="is_active" class="form-check-input" type="radio" value="0" id="statusInactive">
                                                <label class="form-check-label" for="statusInactive">Inactive</label>
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

<!-- edit lead source modal start -->
<div id="editLeadSource" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Lead Source</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>

            <div class="modal-body">
                <form id="editLeadSourceForm" action="<?= $GLOBALS['base_url'] ?>/update-leadSource" method="POST">
                    <input type="hidden" name="id" id="edit_source_id">

                    <div class="row gy-10">
                        <div class="col-xl-12">
                            <div class="card__wrapper">
                                <div class="row gy-20">

                                    <!-- Source Name -->
                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="edit_source_name">Lead Source Name <span>*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" name="name" id="edit_source_name" type="text" required>
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
                                                <input name="is_active" class="form-check-input" type="radio" value="1" id="editStatusActive">
                                                <label class="form-check-label" for="editStatusActive">Active</label>
                                            </div>
                                            <div class="form-check">
                                                <input name="is_active" class="form-check-input" type="radio" value="0" id="editStatusInactive">
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
<!-- edit lead source modal end -->

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const modal = document.getElementById('editLeadSource');
        if (modal) {
            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const status = button.getAttribute('data-status');

                document.getElementById('edit_source_id').value = id;
                document.getElementById('edit_source_name').value = name;

                if (status === "1") {
                    document.getElementById('editStatusActive').checked = true;
                } else {
                    document.getElementById('editStatusInactive').checked = true;
                }
            });
        }
    });
</script>
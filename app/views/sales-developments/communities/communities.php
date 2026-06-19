<?php
$page_title = "community Page";
// $page = 'Commun';
$addPage = true;
$show_breadcrumb = False;

?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

    <h4 class="mb-2 mb-md-0">
        Community Management
    </h4>

    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNewcommunity">Add community</button>
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
                            <th> Name</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">


                        <?php foreach ($communities as $index => $community): ?>
                            <tr>
                                <!-- Checkbox with community id -->
                                <td><input type="checkbox" class="selectedId" name="selectedId[]" value="<?= $community['id'] ?>"></td>

                                <!-- Serial number -->
                                <td>#<?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($community['name']) ?></td>
                                <td>
                                    <?php if ($community['status'] == 1): ?>
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
                                            data-bs-target="#editcommunity"
                                            data-id="<?= $community['id'] ?>"
                                            data-name="<?= htmlspecialchars($community['name']) ?>"
                                            data-status="<?= $community['status'] ?>">
                                            <i class="fa-sharp fa-light fa-pen"></i>
                                        </button>

                                        <button type="button" class="removeBtnCust table__icon delete"
                                            data-id="<?= $community['id'] ?>"
                                            data-name="<?= $community['name'] ?>"
                                            data-action="delete-community">
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
<div id="addNewcommunity" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Lead</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close"><i
                        class="fa-solid fa-xmark-large"></i></button>
            </div>
            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/store-community" method="POST">

                    <div class="row gy-10">
                        <div class="col-xl-12">
                            <div class="card__wrapper">
                                <div class="row gy-20">
                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="community">Community Name<span>*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" name="name" id="name" type="text">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form__input-title">
                                            <label> Status</label>
                                        </div>
                                        <div class="radio d-flex align-items-center gap-20">
                                            <div class="form-check">
                                                <input name="status" class="form-check-input" type="radio" value="1" id="statusRadioActive" checked>
                                                <label class="form-check-label" for="statusRadioActive">Active</label>
                                            </div>
                                            <div class="form-check">
                                                <input name="status" class="form-check-input" type="radio" value="0" id="statusRadioInactive">
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
<!-- edit community modal start -->
<div id="editcommunity" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit community</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>

            <div class="modal-body">
                <form id="editcommunityForm" action="<?= $GLOBALS['base_url'] ?>/update-community" method="POST">
                    <input type="hidden" name="id" id="edit_community_id">

                    <div class="row gy-10">
                        <div class="col-xl-12">
                            <div class="card__wrapper">
                                <div class="row gy-20">

                                    <!-- community Name -->
                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="edit_community">community Name <span>*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" name="name" id="edit_community" type="text" required>
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
<!-- edit community modal end -->
<script>
    var base_url = "<?= $GLOBALS['base_url'] ?>";

    document.addEventListener("DOMContentLoaded", function() {
        var modal = document.getElementById('editcommunity');
        if (modal) {
            modal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var id     = button.getAttribute('data-id');
                var name   = button.getAttribute('data-name');
                var status = button.getAttribute('data-status');
                document.getElementById('edit_community_id').value = id;
                document.getElementById('edit_community').value    = name;
                if (status === "1") {
                    document.getElementById('editStatusActive').checked = true;
                } else {
                    document.getElementById('editStatusInactive').checked = true;
                }
            });
        }
    });


    // Deletion is handled globally by footer.php's delegated listener on .removeBtnCust

</script>
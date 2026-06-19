<?php
$page = 'Locations';
$addPage = true;
$show_breadcrumb = false;

?>
<div class="row mb-4 ">
<div class="d-flex justify-content-evenly align-items-center mb-4">
    <h4 class="mb-0">Location Management</h4>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNewLocation">
        Add Location
    </button>
</div>



</div>
<div class="row justify-content-center mt-2">
    <div class="col-xxl-6 col-xl-8 col-lg-10">
        <div class="card__wrapper ">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 multiple_tables">
                    <thead>
                        <tr class="table__title table__sort">
                            <th>S/L</th>
                            <th>Location Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php foreach ($locations as $key =>$location): ?>
                            <tr>
                                 <td><?= $key + 1 ?></td>
                                <td><?= htmlspecialchars($location['name'] ?? '') ?></td>
                                <td>
                                    <div class="d-flex align-items-center justify-content-start gap-2">
                                        <button type="button" class="table__icon edit"
                                            data-bs-toggle="modal"
                                            data-bs-target="#locationEdit"
                                            data-id="<?= $location['id'] ?>"
                                            data-name="<?= htmlspecialchars($location['name']) ?>">
                                            <i class="fa-sharp fa-light fa-pen"></i>
                                        </button>

                                        <button type="button" class="removeBtnCust table__icon delete"
                                            data-id="<?= $location['id'] ?>"
                                            data-name="<?= htmlspecialchars($location['name']) ?>"
                                            data-action="delete-location">
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
<div id="addNewLocation" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New location</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close"><i
                        class="fa-solid fa-xmark-large"></i></button>
            </div>
            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/store-location" method="POST">
                    <div class="row gy-10">
                        <div class="col-xl-12">
                            <div class="card__wrapper">
                                <div class="row gy-20">
                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="name">Location Name<span>*</span></label>
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
<div id="locationEdit" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Lead Update</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close"><i
                        class="fa-solid fa-xmark-large"></i></button>
            </div>
            <div class="modal-body form__edit-view">
                <form id="editDepartmentForm" action="<?= $GLOBALS['base_url'] ?>/update-location" method="POST">
                    <input type="hidden" name="id" id="edit_location_id">
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
                                                <input class="form-control" name="name" id="edit_location" type="text" >
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
    var base_url = "<?= $GLOBALS['base_url'] ?>";

    document.addEventListener("DOMContentLoaded", function() {
        var modal = document.getElementById('locationEdit');
        if (modal) {
            modal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var id     = button.getAttribute('data-id');
                var name   = button.getAttribute('data-name');
                document.getElementById('edit_location_id').value = id;
                document.getElementById('edit_location').value    = name;
            });
        }
    });


    // Deletion is handled globally by footer.php's delegated listener on .removeBtnCust

</script>
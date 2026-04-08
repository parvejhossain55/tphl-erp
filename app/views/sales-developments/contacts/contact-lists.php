<?php
$page_title = "Contact Lists";
// $page = 'Commun';
$addPage = true;
$show_breadcrumb = False;

?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

    <h4 class="mb-2 mb-md-0">
        Contact Management
    </h4>

    <div class="d-flex gap-2 d-none">
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
                            <th> email</th>
                            <th> phone</th>
                            <th> Message</th>
                            <th>Status</th>
                            <th class="d-none">Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">


                        <?php foreach ($contacts as $index => $contact): ?>
                            <tr>
                                <!-- Checkbox with contact id -->
                                <td><input type="checkbox" class="selectedId" name="selectedId[]" value="<?= $contact['id'] ?>"></td>

                                <!-- Serial number -->
                                <td>#<?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($contact['name']) ?></td>
                                <td><?= htmlspecialchars($contact['email']) ?></td>
                                <td><?= htmlspecialchars($contact['phone']) ?></td>
                                <td><?= htmlspecialchars($contact['message']) ?></td>
                                <td>
                                    <form action="<?= $GLOBALS['base_url'] ?>/contacts/update-status" method="POST" class="d-flex align-items-center gap-2">
                                        <input type="hidden" name="id" value="<?= $contact['id'] ?>">
                                        <input type="hidden" name="current_status" value="<?= $contact['contact_status'] ?>">

                                        <!-- Status Text (Left Side) -->
                                        <span class="fw-semibold  <?= $contact['contact_status'] == 'pending' ? 'text-danger' : 'text-success' ?>">
                                            <?= ucfirst($contact['contact_status']) ?>
                                        </span>

                                        <!-- Toggle Switch -->
                                        <label class="switch mb-0">
                                            <input type="checkbox"
                                                onchange="this.form.submit()"
                                                <?= $contact['contact_status'] == 'contacted' ? 'checked' : '' ?>>
                                            <span class="slider round"></span>
                                        </label>
                                    </form>
                                </td>
                                <!-- Action buttons -->
                                <td class="d-none">
                                    <div class="d-flex align-items-center justify-content-start gap-10">
                                        <button type="button" class="table__icon edit"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editcontact"
                                            data-id="<?= $contact['id'] ?>"
                                            data-name="<?= htmlspecialchars($community['name']) ?>"
                                            data-status="<?= $community['status'] ?>">
                                            <i class="fa-sharp fa-light fa-pen"></i>
                                        </button>

                                        <button class="removeBtnCust table__icon delete"
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
    const base_url = "<?= $GLOBALS['base_url'] ?>"; // PHP outputs the correct base URL



    document.addEventListener("DOMContentLoaded", function() {
        const modal = document.getElementById('editcommunity');

        // Attach listener only once when modal is shown
        modal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget; // the clicked button

            // Get data attributes
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const status = button.getAttribute('data-status');

            // Fill form fields
            document.getElementById('edit_community_id').value = id;
            document.getElementById('edit_community').value = name;
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
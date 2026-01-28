<?php
$page_title = "Leaves Page";
$page = 'Leaves';
$addPage = true;
$show_breadcrumb = true;
?>

<div class="breadcrumb__btn my-3">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLeave">Add Leave</button>
</div>
<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="row">
                <div class="col-xxl-12">
                    <div class="table__wrapper table-responsive">
                        <table class="table mb-20 hover multiple_tables">
                            <thead>
                                <tr class="table__title">
                                    <th>S/L</th>
                                    <th>Leave Name</th>
                                    <th>Number Of Days</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody class="table__body">
                                <?php foreach($leaveTypes  as  $key => $type): ?>
                                <tr>
                                    <td>#<?= $key + 1 ?></td>
                                    <td><?= $type['name'] ?? '' ?></td>
                                    <td><?= $type['total_days'] ?? 0 ?></td>
                                    <td><?php if ($type['is_active'] == 1): ?>
                                        <span class="bd-badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="bd-badge bg-danger">Inactive</span>
                                    <?php endif; ?></td>

                                    <td>
                                        <div class="d-flex align-items-center gap-10">

                                            <!-- Edit Button -->
                                            
                                            <button type="button" class="table__icon edit"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editLeaveType"
                                            data-id="<?= $type['id'] ?>"
                                            data-name="<?= htmlspecialchars($type['name']) ?>"
                                            data-status="<?= $type['is_active'] ?>"
                                            data-total-days="<?= $type['total_days']?>">
                                            <i class="fa-sharp fa-light fa-pen"></i>
                                        </button>

                                            <!-- Delete Button -->
                                          
                                              <button class="removeBtnCust table__icon delete"
                                            data-id="<?= $type['id'] ?>"
                                            data-name="<?= $type['name'] ?>"
                                            data-action="delete-leave-type">
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
    </div>
</div>



<!-- New Leave Modal Start -->
<div class="modal fade" id="addLeave" tabindex="-1" aria-labelledby="addLeaveLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addLeaveLabel">Add New Leave</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close"><i
                        class="fa-solid fa-xmark-large"></i></button>
            </div>
            <div class="modal-body">
                <div class="employee__info">
                    <form action="<?= $GLOBALS['base_url'] ?>/store-leave-type" method="post">
                        <div class="card__wrapper mb-20">
                            <div class="row g-20 gy-20 align-items-center justify-content-center">
                                <div class="col-lg-12">
                                    <div class="from__input-box">
                                        <div class="form__input-title">
                                            <label for="Leave">Leave <span>*</span></label>
                                        </div>
                                        <div class="form__input">
                                            <input class="form-control" name="name" id="leave" type="text">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="from__input-box">
                                        <div class="form__input-title">
                                            <label for="Leave">Days<span>*</span></label>
                                        </div>
                                        <div class="form__input">
                                            <input class="form-control" name="total_days" id="leave_days" type="number">
                                        </div>
                                    </div>
                                </div>
                              
                                <div class="col-lg-12">
                                    <div class="from__input-box">
                                        <div class="form__input-title">
                                            <label> Status <span>*</span></label>
                                        </div>
                                        <div class="form__input">
                                            <select class="form-control" name="is_active">
                                                <option value="1">Active</option>
                                                <option value="0">In-Active</option>

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
<!-- New leave Modal End -->

<!-- edit leave modal start -->
<div id="editLeaveType" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Leave Type</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>

            <div class="modal-body">
                <form id="editLeaveTypeForm" action="<?= $GLOBALS['base_url'] ?>/update-leave-type" method="POST">
                    <input type="hidden" name="id" id="edit_leaveType_id">

                    <div class="row gy-10">
                        <div class="col-xl-12">
                            <div class="card__wrapper">
                                <div class="row gy-20">

                                    <!-- Department Name -->
                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="edit_leave_type">Leave Type Name <span>*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" name="name" id="edit_leave_type" type="text" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="edit_total_days">Leave Total Days <span>*</span></label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" name="total_days" id="edit_total_days" type="text" required>
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
<!-- edit leave modal end -->
<script>
    const base_url = "<?= $GLOBALS['base_url'] ?>"; // PHP outputs the correct base URL
    document.addEventListener("DOMContentLoaded", function() {
        const modal = document.getElementById('editLeaveType');

        // Attach listener only once when modal is shown
        modal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget; // the clicked button

            // Get data attributes
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const status = button.getAttribute('data-status');
            const total_days = button.getAttribute('data-total-days');

            // Fill form fields
            document.getElementById('edit_leaveType_id').value = id;
            document.getElementById('edit_leave_type').value = name;
            document.getElementById('edit_total_days').value = total_days;
           
            if (status === "1") {
                document.getElementById('editStatusActive').checked = true;
            } else {
                document.getElementById('editStatusInactive').checked = true;
            }
        });
    });
</script>
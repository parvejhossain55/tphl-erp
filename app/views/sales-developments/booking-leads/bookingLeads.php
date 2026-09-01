<?php
$page_title = "Onboard Lead";
$page = 'Onboard Leads';
$show_breadcrumb = true;

if (session_status() === PHP_SESSION_NONE) session_start();
?>
<style>
    .locked-option {
        color: #dc3545 !important;
        font-weight: 600;
    }
    .booked-option {
        color: #6c757d;
    }
</style>
<div class="row">
    <div clas="card__wrapper">
        <a href="<?= $base_url ?>/edit-lead/<?= $lead['id'] ?>"
            class="btn btn-sm btn-primary">
            Complete Client Profile
        </a>
    </div>

    <div class="row mb-4">

    </div>
    <div class="col-xxl-12">
        <div class="card__wrapper p-3">
            <h5 class="mb-3">Booking Flat/Unit</h5>


            <form action="<?= $GLOBALS['base_url'] ?>/booking-store" method="POST" enctype="multipart/form-data">
                <div class="row gy-3">

                    <!-- Lead ID (hidden) -->
                    <input type="hidden" name="lead_id" value="<?= $lead['id'] ?? '' ?>">

                    <!-- Auth User -->
                    <input type="hidden" name="user_id" value="<?= $_SESSION['user']['id'] ?? 1 ?>">

                    <!-- Lead Name -->
                    <div class="col-lg-6 ">
                        <div class="form__input-title">

                            <label class="form-label">Client Name</label>
                        </div>
                        <input type="text" class="form-control"
                            value="<?= $lead['full_name'] ?? '' ?>" readonly>
                    </div>

                    <!-- Booking Date -->
                    <div class="col-lg-6">
                        <div class="form__input-title">

                            <label class="form-label">Booking Date</label>
                        </div>
                        <input type="date" name="booking_date" class="form-control"
                            value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <!-- Project -->
                    <div class="col-lg-6">
                        <div class="form__input-title">

                            <label class="form-label">Project *</label>
                        </div>
                        <select name="project_id" id="projectSelect" class="form-select select2" data-placeholder="Select Project" required>
                            <option value="">Select Project</option>
                            <?php foreach ($projects as $project): ?>
                                <?php 
                                    $isUpcoming = ($project['project_sale_status'] === 'Upcoming');
                                    $isSoldOut = ($project['project_sale_status'] === 'Sold Out');
                                    $statusSuffix = !empty($project['project_sale_status']) ? " ({$project['project_sale_status']})" : '';
                                ?>
                                <option value="<?= $project['id'] ?>" 
                                    <?= $isUpcoming ? 'disabled style="color: red;"' : '' ?>
                                    <?= $isSoldOut ? 'style="color: red;"' : '' ?>>
                                    <?= htmlspecialchars($project['name']) ?><?= $statusSuffix ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Flat -->
                    <div class="col-lg-6">
                        <div class="form__input-title">

                            <label class="form-label">Flat / Unit *</label>
                        </div>
                        <select id="flatSelect" name="flat_id" class="form-select select2" data-placeholder="Select Flat">

                            <option value="">Select Flat</option>
                        </select>
                    </div>

                    <!-- Plot / Flat Price -->
                    <div class="col-lg-6">
                        <div class="form__input-title">

                            <label class="form-label">Total Flat Value <span>(Approx)</span> </label>
                        </div>
                        <input type="text"
                            class="form-control"
                            id="flatPrice"
                            readonly>
                    </div>
                    <!-- Land Share Value -->
                    <div class="col-lg-6">
                        <div class="form__input-title">

                            <label class="form-label"> Land Share amount  </label>
                        </div>
                        <input type="text"
                            class="form-control"
                            id="landValue"
                            readonly>
                    </div>

                    <!-- Agreement Value -->
                    <div class="col-lg-6">
                        <div class="form__input-title">

                            <label class="form-label">Land Share Money/Booking Money *</label>
                        </div>
                        <input type="number" name="agreement_value" class="form-control"
                            placeholder="e.g. 5000000" required>
                    </div>
                    <div class="col-lg-6">
                        <div class="form__input-title">

                            <label class="form-label">Discount Amount</label>
                        </div>
                        <input type="number" name="discount_amount" id="discount_amount_input" class="form-control"
                            placeholder="" >
                    </div>
                    
                    <!-- Already Approved Options -->
                    <div class="col-lg-12 mt-3" id="already_approved_container" style="display: none;">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="is_already_approved" id="is_already_approved" value="1">
                            <label class="form-check-label" for="is_already_approved">
                                Is Discount Already Approved? (Backlog Data)
                            </label>
                        </div>
                        <div class="row mt-2" id="already_approved_fields" style="display: none;">
                            <div class="col-lg-6">
                                <label class="form-label">Approved By *</label>
                                <select name="approved_by" id="approved_by" class="form-select select2">
                                    <option value="">Select Management User</option>
                                    <?php if(!empty($managementUsers)): ?>
                                        <?php foreach($managementUsers as $mu): ?>
                                            <option value="<?= $mu['id'] ?>"><?= htmlspecialchars($mu['full_name']) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-lg-6">
                                <label class="form-label">Approval Attachment *</label>
                                <input type="file" name="approval_attachment" id="approval_attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-success">Locked Booking</button>
                </div>

            </form>
        </div>
    </div>
</div>


<!-- Onboard Records Table -->
<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table table-striped table-bordered multiple_tables" id="bookingTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Client</th>
                            <th>Project</th>
                            <th>Flat</th>
                            <th>Sales By</th>
                            <th>Created By</th>
                            <th>Discount Approved Status /Amount </th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!empty($bookingRecords)): ?>
                            <?php foreach ($bookingRecords as $index => $or): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                              


                                    <td><?= htmlspecialchars($lead['full_name'] ?? '-') ?></td>

                                    <td><?= htmlspecialchars($or['project_name'] ?? '-') ?></td>

                                    <td>
                                        <?= htmlspecialchars($or['flat_name'] ?? '-') ?>
                                        <br>
                                        <small class="text-muted">
                                            Floor: <?= $or['floor_no'] ?? '-' ?>,
                                            <?= $or['sqft'] ?? '-' ?> sqft
                                        </small>
                                    </td>

                                    <td><?= $or['sales_user'] ?? 'N/A'?></td>
                                    <td><?= $or['created_user'] ?? 'N/A'?></td>

                                    <!-- Discount Status -->
                                    <td>
                                        <?php if ($or['approval_status'] === 'pending'): ?>
                                            <span class="badge bg-warning">Pending Approval</span>
                                        <?php elseif ($or['approval_status'] === 'approved'): ?>
                                            <span class="badge bg-success">Approved</span>
                                            <div class="mt-1" style="font-size: 12px; font-weight: 600;">৳ <?= number_format($or['approved_amount'], 2) ?></div>
                                        <?php elseif ($or['approval_status'] === 'partial'): ?>
                                            <span class="badge bg-success">Partial Approved</span>
                                            <div class="mt-1" style="font-size: 12px; font-weight: 600;">৳ <?= number_format($or['approved_amount'], 2) ?></div>
                                        <?php elseif ($or['approval_status'] === 'rejected'): ?>
                                            <span class="badge bg-danger">Rejected</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">No Discount</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Actions -->
                                    <td>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">

                                            <?php 
                                                $isBookingVerified = (!empty($or['verified_payment_count']) && (int)$or['verified_payment_count'] > 0);
                                            ?>

                                            <!-- Edit & Delete (Enabled only if payments not verified) -->
                                            <?php if (!$isBookingVerified && !in_array($or['booking_status'] ?? '', ['refunded', 'cancelled'])): ?>
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-primary editBookingBtn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editModalBooking"
                                                    data-id="<?= $or['id'] ?>"
                                                    data-project="<?= $or['project_id'] ?>"
                                                    data-flat="<?= $or['flat_id'] ?>"
                                                    data-date="<?= $or['booking_date'] ?>"
                                                    data-agreement="<?= $or['agreement_value'] ?>"
                                                    data-discount="<?= $or['discount_amount'] ?? 0 ?>"
                                                    data-approval="<?= $or['approval_status'] ?? '' ?>"
                                                    data-approved-amt="<?= $or['approved_amount'] ?? '' ?>"
                                                    data-status="<?= $or['booking_status'] ?>">
                                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                                </button>

                                                <a href="<?= $GLOBALS['base_url'] ?>/booking-delete/<?= $or['id'] ?>"
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Are you sure you want to delete this booking? All unverified payment records will be removed and the flat will be reset to available status.');">
                                                    <i class="fa-solid fa-trash"></i> Delete
                                                </a>
                                            <?php endif; ?>

                                            <!-- Payments -->
                                            <?php if (($or['booking_status'] ?? '') === 'refunded'): ?>
                                                <button class="btn btn-sm btn-secondary" disabled>
                                                    Refunded
                                                </button>
                                            <?php elseif (($or['booking_status'] ?? '') === 'cancelled'): ?>
                                                <button class="btn btn-sm btn-secondary" disabled>
                                                    Cancelled
                                                </button>
                                            <?php elseif ($or['approval_status'] === 'rejected'): ?>
                                                <button class="btn btn-sm btn-danger" disabled
                                                    title="Discount rejected by management">
                                                    Rejected
                                                </button>
                                            <?php else: ?>
                                                <?php if (isset($or['payment_status']) && $or['payment_status'] === 'paid'): ?>
                                                    <a href="<?= $GLOBALS['base_url'] ?>/payments-booking/<?= $or['id'] ?>"
                                                        class="btn btn-sm btn-info">
                                                        Registration Payment
                                                    </a>
                                                <?php else: ?>
                                                    <a href="<?= $GLOBALS['base_url'] ?>/payments-booking/<?= $or['id'] ?>"
                                                        class="btn btn-sm btn-success">
                                                        Booking Payment
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <a href="<?= $GLOBALS['base_url'] ?>/client-refund-create/<?= $or['id'] ?>"
                                                    class="btn btn-sm btn-outline-danger">
                                                    Refund Request
                                                </a>

                                                <?php if ($or['approval_status'] === 'pending'): ?>
                                                    <small class="text-warning d-block w-100" style="font-size:11px;">
                                                        <i class="fa-solid fa-clock"></i> Discount Pending Approval
                                                    </small>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        <?php else: ?>
                            No Data Available
                            <!-- Empty State -->
                            <!-- <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No booking records found
                                </td>
                            </tr> -->
                        <?php endif; ?>
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>


<!-- Edit Booking Modal -->
<div id="editModalBooking" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Edit Booking</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>

            <div class="modal-body">

                <form action="<?= $GLOBALS['base_url'] ?>/booking-update" method="POST">
                    <input type="hidden" name="booking_id" id="edit_booking_id">
                    <input type="hidden" name="lead_id" value="<?= $lead['id'] ?? '' ?>">

                    <div class="row gy-3">
                        <!-- Booking Date -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Booking Date *</label>
                            <input type="date" name="booking_date" id="edit_booking_date" class="form-control" required>
                        </div>

                        <!-- Agreement Value -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Agreement Value / Booking Target *</label>
                            <input type="number" step="any" name="agreement_value" id="edit_agreement" class="form-control" required>
                        </div>

                        <!-- Project -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Project *</label>
                            <select name="project_id" id="edit_project" class="form-select select2" data-placeholder="Select Project" required>
                                <option value="">Select Project</option>
                                <?php foreach ($projects as $project): ?>
                                    <?php 
                                        $isUpcoming = ($project['project_sale_status'] === 'Upcoming');
                                        $isSoldOut = ($project['project_sale_status'] === 'Sold Out');
                                        $statusSuffix = !empty($project['project_sale_status']) ? " ({$project['project_sale_status']})" : '';
                                    ?>
                                    <option value="<?= $project['id'] ?>" 
                                        <?= $isUpcoming ? 'disabled style="color: red;"' : '' ?>
                                        <?= $isSoldOut ? 'style="color: red;"' : '' ?>>
                                        <?= htmlspecialchars($project['name']) ?><?= $statusSuffix ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Flat -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Flat / Unit *</label>
                            <select name="flat_id" id="edit_flat" class="form-select select2" data-placeholder="Select Flat" required>
                                <option value="">Select Flat</option>
                            </select>
                        </div>

                        <!-- Discount Amount -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label d-flex justify-content-between align-items-center mb-1">
                                <span>Discount Amount</span>
                                <span id="edit_discount_lock_badge" class="badge bg-success d-none" style="font-size:11px;">
                                    <i class="fa-solid fa-lock me-1"></i> Approved by Management
                                </span>
                            </label>
                            <input type="number" step="any" name="discount_amount" id="edit_discount" class="form-control" value="0">
                            <small id="edit_discount_lock_help" class="text-muted d-none" style="font-size:11px;">
                                <i class="fa-solid fa-info-circle"></i> Discount was already approved by management and cannot be modified.
                            </small>
                        </div>
                    </div>

                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Update Booking</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const projectSelect = document.getElementById('projectSelect');
        const flatSelect = document.getElementById('flatSelect');
        const flatPriceInput = document.getElementById('flatPrice');
        const landValueInput = document.getElementById('landValue');

        function refreshSelect2(el) {
            if (window.jQuery && $(el).data('select2')) {
                $(el).trigger('change.select2');
            }
        }

        let flatData = []; // store flats

        if (projectSelect) {
            $(projectSelect).on('change', function() {
                const projectId = this.value;
                flatSelect.innerHTML = '<option value="">Loading...</option>';
                refreshSelect2(flatSelect);

                flatPriceInput.value = '';
                landValueInput.value = '';

                if (!projectId) {
                    flatSelect.innerHTML = '<option value="">Select Flat</option>';
                    refreshSelect2(flatSelect);
                    return;
                }

                fetch("<?= $GLOBALS['base_url'] ?>/get-flats-by-project/" + projectId)
                    .then(res => {
                        if (!res.ok) throw new Error('Network response was not ok');
                        return res.json();
                    })
                    .then(data => {
                        flatData = data;
                        flatSelect.innerHTML = '<option value="">Select Flat</option>';

                        if (data.length === 0) {
                            flatSelect.innerHTML = '<option value="">No flats available</option>';
                            refreshSelect2(flatSelect);
                            return;
                        }

                        data.forEach(flat => {
                            const option = document.createElement('option');
                            option.value = flat.flat_id;

                            // Calculate 48-hour lock
                            const now = new Date();
                            const bookingTime = flat.booking_time ? new Date(flat.booking_time) : null;
                            const isLocked = flat.booking_status === 'booked' && bookingTime && (now - bookingTime < 48 * 60 * 60 * 1000);

                            if (isLocked) {
                                option.textContent = `${flat.flat_name} | Floor ${flat.floor_no} | ${flat.sqft} sqft [Locked for 48 hours]`;
                                option.className = 'locked-option';
                                option.disabled = true;
                                option.style.color = 'red';
                            } else if (flat.is_booked == 1) {
                                option.textContent = `${flat.flat_name} | Floor ${flat.floor_no} | ${flat.sqft} sqft [Booked]`;
                                option.className = 'booked-option';
                                option.disabled = true;
                            } else {
                                option.textContent = `${flat.flat_name} | Floor ${flat.floor_no} | ${flat.sqft} sqft`;
                            }

                            flatSelect.appendChild(option);
                        });
                        refreshSelect2(flatSelect);
                    })
                    .catch(error => {
                        console.error('Error fetching flats:', error);
                        flatSelect.innerHTML = '<option value="">Error loading flats</option>';
                        refreshSelect2(flatSelect);
                    });
            });
        }
        
        // Toggle Already Approved fields
        const discountInput = document.getElementById('discount_amount_input');
        const alreadyApprovedContainer = document.getElementById('already_approved_container');
        const isAlreadyApprovedCheckbox = document.getElementById('is_already_approved');
        const alreadyApprovedFields = document.getElementById('already_approved_fields');
        const approvedBySelect = document.getElementById('approved_by');
        const approvalAttachmentInput = document.getElementById('approval_attachment');

        if (discountInput) {
            discountInput.addEventListener('input', function() {
                if (parseFloat(this.value) > 0) {
                    alreadyApprovedContainer.style.display = 'block';
                } else {
                    alreadyApprovedContainer.style.display = 'none';
                    isAlreadyApprovedCheckbox.checked = false;
                    alreadyApprovedFields.style.display = 'none';
                    approvedBySelect.required = false;
                    approvalAttachmentInput.required = false;
                }
            });
        }

        if (isAlreadyApprovedCheckbox) {
            isAlreadyApprovedCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    alreadyApprovedFields.style.display = 'flex';
                    approvedBySelect.required = true;
                    approvalAttachmentInput.required = true;
                } else {
                    alreadyApprovedFields.style.display = 'none';
                    approvedBySelect.required = false;
                    approvalAttachmentInput.required = false;
                }
            });
        }

        // Show price when flat selected
        $(flatSelect).on('change', function() {
            const selectedFlatId = this.value;
            const flat = flatData.find(f => f.flat_id == selectedFlatId);
            flatPriceInput.value = flat ? Number(flat.price_value).toLocaleString() : '';
            landValueInput.value = flat ? Number(flat.land_share_value).toLocaleString() : '';
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let flatDataEdit = []; // store flats for edit modal
        let currentEditingFlatId = null;

        const editProjectSelect = document.getElementById('edit_project');
        const editFlatSelect = document.getElementById('edit_flat');

        function refreshSelect2(el) {
            if (window.jQuery && $(el).data('select2')) {
                $(el).trigger('change.select2');
            }
        }

        function loadEditFlats(projectId, targetFlatId) {
            if (!editFlatSelect) return;
            editFlatSelect.innerHTML = '<option value="">Loading...</option>';
            refreshSelect2(editFlatSelect);

            if (!projectId) {
                editFlatSelect.innerHTML = '<option value="">Select Flat</option>';
                refreshSelect2(editFlatSelect);
                return;
            }

            fetch("<?= $GLOBALS['base_url'] ?>/get-flats-by-project/" + projectId)
                .then(res => {
                    if (!res.ok) throw new Error('Network response was not ok');
                    return res.json();
                })
                .then(data => {
                    flatDataEdit = data;
                    editFlatSelect.innerHTML = '<option value="">Select Flat</option>';

                    data.forEach(flat => {
                        const opt = document.createElement('option');
                        opt.value = flat.flat_id;

                        const isCurrentFlat = (targetFlatId && flat.flat_id == targetFlatId);
                        const isBooked = (flat.is_booked == 1);

                        if (isBooked && !isCurrentFlat) {
                            opt.disabled = true;
                            opt.textContent = `${flat.flat_name} | Floor ${flat.floor_no} | ${flat.sqft} sqft [Booked]`;
                        } else {
                            opt.textContent = `${flat.flat_name} | Floor ${flat.floor_no} | ${flat.sqft} sqft` + (isCurrentFlat ? ' (Current)' : '');
                        }
                        editFlatSelect.appendChild(opt);
                    });

                    if (targetFlatId) {
                        editFlatSelect.value = targetFlatId;
                    }
                    refreshSelect2(editFlatSelect);
                })
                .catch(error => {
                    console.error('Error fetching flats (edit):', error);
                    editFlatSelect.innerHTML = '<option value="">Error loading flats</option>';
                    refreshSelect2(editFlatSelect);
                });
        }

        if (editProjectSelect) {
            $(editProjectSelect).on('change', function() {
                loadEditFlats(this.value, currentEditingFlatId);
            });
        }

        document.querySelectorAll('.editBookingBtn').forEach(btn => {
            btn.addEventListener('click', function() {
                const bookingId = this.dataset.id;
                const projectId = this.dataset.project;
                const flatId = this.dataset.flat;
                const agreement = this.dataset.agreement;
                const date = this.dataset.date;
                const discount = this.dataset.discount;
                const approval = this.dataset.approval;

                currentEditingFlatId = flatId;

                document.getElementById('edit_booking_id').value = bookingId;
                document.getElementById('edit_agreement').value = agreement;
                if (document.getElementById('edit_booking_date')) {
                    document.getElementById('edit_booking_date').value = date;
                }
                
                const discountInput = document.getElementById('edit_discount');
                const lockBadge = document.getElementById('edit_discount_lock_badge');
                const lockHelp = document.getElementById('edit_discount_lock_help');

                if (discountInput) {
                    discountInput.value = discount || 0;
                    if (approval === 'approved' || approval === 'partial') {
                        discountInput.readOnly = true;
                        discountInput.classList.add('bg-light');
                        if (lockBadge) lockBadge.classList.remove('d-none');
                        if (lockHelp) lockHelp.classList.remove('d-none');
                    } else {
                        discountInput.readOnly = false;
                        discountInput.classList.remove('bg-light');
                        if (lockBadge) lockBadge.classList.add('d-none');
                        if (lockHelp) lockHelp.classList.add('d-none');
                    }
                }

                $(editProjectSelect).val(projectId);
                refreshSelect2(editProjectSelect);

                loadEditFlats(projectId, flatId);
            });
        });
    });
</script>
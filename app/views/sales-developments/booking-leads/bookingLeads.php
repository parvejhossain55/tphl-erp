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


            <form action="<?= $GLOBALS['base_url'] ?>/booking-store" method="POST">
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
                        <select name="project_id" id="projectSelect" class="form-select" required>
                            <option value="">Select Project</option>
                            <?php foreach ($projects as $project): ?>
                                <?php 
                                    $isRestricted = in_array($project['project_sale_status'], ['Sold Out', 'Upcoming']);
                                    $statusSuffix = !empty($project['project_sale_status']) ? " ({$project['project_sale_status']})" : '';
                                ?>
                                <option value="<?= $project['id'] ?>" 
                                    <?= $isRestricted ? 'disabled style="color: red;"' : '' ?>>
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
                        <select id="flatSelect" name="flat_id" class="form-select" data-live-search="true">

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
                        <input type="number" name="discount_amount" class="form-control"
                            placeholder="" >
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
                                            <span class="bd-badge bg-success">Approved</span>
                                            <span class="bd-badge bg-primary"><?= $or['approved_amount'] ?></span>
                                        <?php elseif ($or['approval_status'] === 'partial'): ?>
                                            <span class="bd-badge bg-success">Partial Approved</span>
                                            <span class="bd-badge bg-primary"><?= $or['approved_amount'] ?></span>
                                        <?php elseif ($or['approval_status'] === 'rejected'): ?>
                                            <span class="badge bg-danger">Rejected</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">No Discount</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Actions -->
                                    <td>
                                        <div class="d-flex align-items-center gap-2">

                                            <!-- Edit -->
                                            <button  type="button"
                                                class="d-none table__icon edit editBookingBtn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editModalBooking"
                                                data-id="<?= $or['id'] ?>"
                                                data-project="<?= $or['project_id'] ?>"
                                                data-flat="<?= $or['flat_id'] ?>"
                                                data-date="<?= $or['booking_date'] ?>"
                                                data-agreement="<?= $or['agreement_value'] ?>"
                                                data-status="<?= $or['booking_status'] ?>">
                                                Edit
                                            </button>

                                            <!-- Payments -->
                                            <?php if ($or['approval_status'] === 'rejected'): ?>
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
                                                <?php if ($or['approval_status'] === 'pending'): ?>
                                                    <br><small class="text-warning mt-1 d-block" style="font-size:11px;">
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

                <form action="<?= $GLOBALS['base_url'] ?>/update-flats-by-project" method="POST">
                    <input type="hidden" name="booking_id" id="edit_booking_id">

                    <!-- Agreement Value -->
                    <div class="mb-3">
                        <label class="form-label">Agreement Value/Booking Money *</label>
                        <input type="number" name="agreement_value" id="edit_agreement" class="form-control" required>
                    </div>

                    <!-- Project -->
                    <div class="mb-3">
                        <label class="form-label">Project *</label>
                        <select name="project_id" id="edit_project" class="form-select" required>
                            <option value="">Select Project</option>
                            <?php foreach ($projects as $project): ?>
                                <?php 
                                    $isRestricted = in_array($project['project_sale_status'], ['Sold Out', 'Upcoming']);
                                    $statusSuffix = !empty($project['project_sale_status']) ? " ({$project['project_sale_status']})" : '';
                                ?>
                                <option value="<?= $project['id'] ?>" 
                                    <?= $isRestricted ? 'disabled style="color: red;"' : '' ?>>
                                    <?= htmlspecialchars($project['name']) ?><?= $statusSuffix ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Flat -->
                    <div class="mb-3">
                        <label class="form-label">Flat / Unit *</label>
                        <select name="flat_id" id="edit_flat" class="form-select" required>
                            <option value="">Select Flat</option>
                        </select>
                    </div>

                    <div class="text-end">
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

        let flatData = []; // store flats

        if (projectSelect) {
            projectSelect.addEventListener('change', function() {
                const projectId = this.value;
                flatSelect.innerHTML = '<option>Loading...</option>';
                flatPriceInput.value = '';
                landValueInput.value = '';

                if (!projectId) {
                    flatSelect.innerHTML = '<option value="">Select Flat</option>';
                    return;
                }
                //strat fetch
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
                                option.style.color = 'red'; // ensure visibility in basic select
                            } else if (flat.is_booked == 1) {
                                option.textContent = `${flat.flat_name} | Floor ${flat.floor_no} | ${flat.sqft} sqft [Booked]`;
                                option.className = 'booked-option';
                                option.disabled = true;
                            } else {
                                option.textContent = `${flat.flat_name} | Floor ${flat.floor_no} | ${flat.sqft} sqft`;
                            }

                            flatSelect.appendChild(option)
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching flats:', error);
                        flatSelect.innerHTML = '<option value="">Error loading flats</option>';
                    });
                //end fetch
            });
        }

        // Show price when flat selected
        flatSelect.addEventListener('change', function() {
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

        const editProjectSelect = document.getElementById('edit_project');
        const editFlatSelect = document.getElementById('edit_flat');
        const editFlatPriceInput = document.getElementById('edit_flatPrice'); // optional price display

        // Load flats on project change in edit modal
        if (editProjectSelect) {
            editProjectSelect.addEventListener('change', function() {
                const projectId = this.value;
                editFlatSelect.innerHTML = '<option>Loading...</option>';
                if (!projectId) {
                    editFlatSelect.innerHTML = '<option value="">Select Flat</option>';
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

                            // Calculate 48-hour lock
                            const now = new Date();
                            const bTime = flat.booking_time ? new Date(flat.booking_time) : null;
                            const isLocked = flat.booking_status === 'booked' && bTime && (now - bTime < 48 * 60 * 60 * 1000);

                            if (isLocked) {
                                opt.textContent = `${flat.flat_name} | Floor ${flat.floor_no} | ${flat.sqft} sqft [Locked for 48 hours]`;
                                opt.style.color = 'red';
                                opt.disabled = true;
                            } else if (flat.is_booked == 1) {
                                opt.disabled = true;
                                opt.textContent = `${flat.flat_name} | Floor ${flat.floor_no} | ${flat.sqft} sqft [Booked]`;
                            } else {
                                opt.textContent = `${flat.flat_name} | Floor ${flat.floor_no} | ${flat.sqft} sqft`;
                            }
                            editFlatSelect.appendChild(opt);
                        });

                        // Set flat after options loaded
                        if (typeof flatId !== 'undefined') { 
                            editFlatSelect.value = flatId;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching flats (edit):', error);
                        editFlatSelect.innerHTML = '<option value="">Error loading flats</option>';
                    });

            });
        }

        // Optional: show price when flat selected
        if (editFlatSelect && editFlatPriceInput) {
            editFlatSelect.addEventListener('change', function() {
                const selectedFlatId = this.value;
                const flat = flatDataEdit.find(f => f.flat_id == selectedFlatId);
                editFlatPriceInput.value = flat ? Number(flat.price_value).toLocaleString() : '';
            });
        }

        // ===============================
        // Populate edit modal when button clicked
        // ===============================
        document.querySelectorAll('.editBookingBtn').forEach(btn => {
            btn.addEventListener('click', function() {
                const bookingId = this.dataset.id;
                const projectId = this.dataset.project;
                const flatId = this.dataset.flat;
                const agreement = this.dataset.agreement;

                document.getElementById('edit_booking_id').value = bookingId;
                document.getElementById('edit_agreement').value = agreement;

                // set project and trigger change to load flats
                editProjectSelect.value = projectId;
                const event = new Event('change');
                editProjectSelect.dispatchEvent(event);

                // Wait for flats to load then select the flat
                setTimeout(() => {
                    editFlatSelect.value = flatId;
                }, 300); // 300ms delay to ensure flats are loaded
            });
        });

    });
</script>
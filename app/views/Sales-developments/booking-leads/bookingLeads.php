<?php
$page_title = "Onboard Lead";
$page = 'Onboard Leads';
$show_breadcrumb = true;

if (session_status() === PHP_SESSION_NONE) session_start();
?>
<div class="row mb-4">
    <div class="col-xxl-12">
        <div class="card__wrapper p-3">
            <h5>Onboard Lead</h5>

            <form action="<?= $GLOBALS['base_url'] ?>/booking-store" method="POST">
                <div class="row gy-3">

                    <!-- Lead ID (hidden) -->
                    <input type="hidden" name="lead_id" value="<?= $lead['id'] ?? '' ?>">

                    <!-- Auth User -->
                    <input type="hidden" name="user_id" value="<?= $_SESSION['user']['id'] ?? 1 ?>">

                    <!-- Lead Name -->
                    <div class="col-lg-6">
                        <label class="form-label">Lead Name</label>
                        <input type="text" class="form-control"
                            value="<?= $lead['lead_name'] ?? '' ?>" readonly>
                    </div>

                    <!-- Booking Date -->
                    <div class="col-lg-6">
                        <label class="form-label">Booking Date</label>
                        <input type="date" name="booking_date" class="form-control"
                            value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <!-- Project -->
                    <div class="col-lg-6">
                        <label class="form-label">Project *</label>
                        <select name="project_id" id="projectSelect" class="form-select" required>
                            <option value="">Select Project</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?= $project['id'] ?>"><?= $project['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Flat -->
                    <div class="col-lg-6">
                        <label class="form-label">Flat / Unit *</label>
                        <select id="flatSelect" class="form-select " data-live-search="true">

                            <option value="">Select Flat</option>
                        </select>
                    </div>

                    <!-- Plot / Flat Price -->
                    <div class="col-lg-6">
                        <label class="form-label">Share Value</label>
                        <input type="text"
                            class="form-control"
                            id="flatPrice"
                            readonly>
                    </div>

                    <!-- Agreement Value -->
                    <div class="col-lg-6">
                        <label class="form-label">Agreement Value/Booking Money *</label>
                        <input type="number" name="agreement_value" class="form-control"
                            placeholder="e.g. 5000000" required>
                    </div>

                    <!-- Status -->
                    <div class="col-lg-6">
                        <label class="form-label">Status</label>
                        <select name="booking_status" class="form-select">
                            <option value="booked">Booked</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-success">Confirm Onboard</button>
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
                            <th>Lead</th>
                            <th>Project</th>
                            <th>Flat</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($bookingRecords)): ?>
                            <?php foreach ($bookingRecords as $index => $or): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= $lead['lead_name'] ?? '' ?></td>
                                    <td><?= $or['project_name'] ?? '' ?></td>
                                    <td><?= $or['unit_name'] ?? '' ?> (Floor: <?= $or['floor_no'] ?? '' ?>, <?= $or['sqft'] ?? '' ?> sqft)</td>
                                    <td><?= $_SESSION['user']['name'] ?? '' ?></td>
                                    <td>
                                        <a href="<?= $GLOBALS['base_url'] ?>/onboard-edit/<?= $or['id'] ?>" class="btn btn-sm btn-primary">Edit</a>

                                        <a href="<?= $GLOBALS['base_url'] ?>/payments-booking/<?= $or['id'] ?>" class="btn btn-sm btn-success">Payments</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Safe empty row with 6 td -->
                            <tr>
                                <td></td>
                                <td></td>
                                <td class="text-center text-muted">No onboarding records found</td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const projectSelect = document.getElementById('projectSelect');
        const flatSelect = document.getElementById('flatSelect');
        const flatPriceInput = document.getElementById('flatPrice');

        let flatData = []; // store flats

        if (projectSelect) {
            projectSelect.addEventListener('change', function() {
                const projectId = this.value;
                flatSelect.innerHTML = '<option>Loading...</option>';
                flatPriceInput.value = '';

                if (!projectId) {
                    flatSelect.innerHTML = '<option value="">Select Flat</option>';
                    return;
                }
                //strat fetch
                fetch("<?= $GLOBALS['base_url'] ?>/get-flats-by-project/" + projectId)
                    .then(res => res.json())
                    .then(data => {
                        flatData = data;
                        flatSelect.innerHTML = '<option value="">Select Flat</option>';

                        data.forEach(flat => {
                            const option = document.createElement('option');
                            option.value = flat.flat_id;

                            if (flat.is_booked == 1) {
                                option.textContent = `${flat.unit_name} | Floor ${flat.floor_no} | ${flat.sqft} sqft [Booked]`;
                                option.classList.add('booked-option');
                                option.disabled = true; // Disable booked flats
                            } else {
                                option.textContent = `${flat.unit_name} | Floor ${flat.floor_no} | ${flat.sqft} sqft`;
                            }

                            flatSelect.appendChild(option);
                        });
                    });



                //end fetch
            });
        }



        // Show price when flat selected
        flatSelect.addEventListener('change', function() {
            const selectedFlatId = this.value;
            const flat = flatData.find(f => f.flat_id == selectedFlatId);
            flatPriceInput.value = flat ? Number(flat.price_value).toLocaleString() : '';
        });
    });
</script>>
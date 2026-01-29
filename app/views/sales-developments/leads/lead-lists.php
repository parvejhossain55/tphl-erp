<?php
$page_title = "leads Page";
$page = 'Lead & Booking Management';
$addPage = 'Add Leads';
$addPage = true;
$show_breadcrumb = true;
?>
<div class="d-none flex-wrap justify-content-between align-items-center mb-3">
    <h4 class="mb-2 mb-md-0">
        Lead Management
    </h4>

    <div class="d-flex gap-2">


        <a href="<?= $GLOBALS['base_url'] ?>/create-lead"
            class="btn btn-primary">
            <i class="fa-light fa-plus me-1"></i>
            Add Lead
        </a>
    </div>

</div>

<div class="row lead-select align-items-stretch">


    <form action="" method="get">
        <div class="row  mb-3 align-items-end">

            <?php
            $currentYear = date('Y');
            $startYear   = $currentYear - 10;
            $selectedYear = $slectedYear ?? '';
            ?>
            <!-- Year Select -->
            <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-12">
                <div class="card__wrapper h-100">
                    <div class="from__input-box">
                        <select class="form-control" name="year">
                            <option value="">Year Select</option>

                            <?php for ($year = $currentYear; $year >= $startYear; $year--): ?>
                                <option
                                    value="<?= $year ?>"
                                    <?= $selectedYear == $year ? 'selected' : '' ?>>
                                    <?= $year ?>
                                </option>
                            <?php endfor; ?>

                        </select>
                    </div>
                </div>
            </div>

            <?php
            $months = [
                1 => 'January',
                2 => 'February',
                3 => 'March',
                4 => 'April',
                5 => 'May',
                6 => 'June',
                7 => 'July',
                8 => 'August',
                9 => 'September',
                10 => 'October',
                11 => 'November',
                12 => 'December'
            ];
            $selectedMonth = $_GET['month'] ?? '';
            ?>

            <!-- Month Select -->
            <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-12">
                <div class="card__wrapper h-100">
                    <div class="from__input-box">
                        <select class="form-control" name="month">
                            <option value="">Month Select</option>
                            <?php foreach ($months as $id => $name): ?>

                                <option value="<?= $id ?>" <?= $selectedMonth == $id ? 'selected' : '' ?>>
                                    <?= $name ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                    </div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="col-xxl-4 col-xl-12 col-lg-12 col-md-12 col-12">
                <div class="card__wrapper h-100">
                    <div class="d-flex flex-row gap-2 align-items-center p-0">
                        <button type="submit" class="btn btn-secondary btn-sm flex-grow-1 py-1">
                            Search Lead
                        </button>
                        <a href="<?= $GLOBALS['base_url'] ?>/lead-lists" class=" d-none btn btn-success  w-100">
                            Reset
                        </a>
                        <button
                            type="button"
                            class="btn btn-success btn-sm py-1"
                            data-bs-toggle="modal"
                            data-bs-target="#importCsvModal">
                            <i class="fa-light fa-file-arrow-up me-1"></i>
                            Import CSV
                        </button>


                        <a class="btn btn-primary btn-sm flex-grow-1 py-1"
                            href="<?= $GLOBALS['base_url'] ?>/create-lead">
                            Add Lead
                        </a>
                    </div>
                </div>
            </div>





        </div>
    </form>

    <div class="col-xxl-3 col-xl-3 col-lg-3 col-md-3 col-12">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-light fa-ban"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Lead</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title"><?= $leadCounts ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-3 col-lg-3 col-md-3 col-12">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-sharp fa-regular fa-user"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Booked </h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title"><?= $paymentCount ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-xl-3 col-lg-3 col-md-3 col-12">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-sharp fa-regular fa-user"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Today Follow Up</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title"><?= $totalTodayFollowUps ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-xl-3  col-md-3 col-12">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <a href="<?= $GLOBALS['base_url'] ?>/onboarding-clients" class="btn btn-primary w-100 ">
                    <span class="fw-semibold fs-6">All Onboard Clients</span>
                </a>
            </div>
        </div>
    </div>


    <div class="col-xxl-12">
        <div class="card__wrapper shadow-sm p-3 d-flex flex-wrap justify-content-between gap-2">

            <div>
                <!-- <h5 class="mb-0">Today Leads: <= $leadCounts ?></h5> -->
                <!-- <small class="text-muted">Follow Ups Today: <= $totalTodayFollowUps ?></small> -->
            </div>

<!-- Assign Leads Card -->
<div id="assignCard" class="card shadow-sm p-3 mb-3" style="display:none; border-radius:12px; background:#f9f9f9;">
    <div class="d-flex flex-wrap align-items-center gap-2">

        <label for="assignUserSelect" class="me-2 fw-bold mb-0" style="white-space: nowrap;">
            Assign Selected Leads to:
        </label>

        <div style="flex:0 0 auto;"> <!-- prevents select from stretching -->
            <select id="assignUserSelect" class="form-select form-select-sm" style="min-width:200px; width:auto;">
                <option value="">Select User</option>
                <?php foreach ($assignedUsers as $user): ?>
                    <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['full_name']) ?></option>
                <?php endforeach ?>
            </select>
        </div>

        <button id="assignUserBtn" class="btn btn-primary btn-sm px-3">
            <i class="fa-solid fa-user-plus me-1"></i> Assign Leads
        </button>

        <span id="assignFeedback" class="text-success fw-bold ms-2" style="display:none;">Leads assigned successfully!</span>
    </div>
</div>



        </div>
    </div>

    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 multiple_tables">
                    <thead>
                        <tr class="table__title table__sort">


                            <th style="min-width:60px;">
                                <input type="checkbox" id="selectAllLeads" title="Select All">
                                S/L
                            </th><!-- non-sticky first column -->
                            <th class="">Create Lead</th>
                            <th class="th_1">Lead Name</th>

                            <th>Phone</th>
                            <!-- <th>Alt Phone</th> -->
                            <th>Project Campaign</th>
                            <!-- <th>Source Type</th> -->

                            <th>Follow Up</th>
                            <th>Booking</th>
                            <th>Contact Status</th>
                            <th>Contact Note</th>
                            <th class="d-none">Interest On</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">

                        <?php foreach ($leads as $key => $lead): ?>
                            <tr data-id="<?= $lead['id'] ?>">
                                <td>
                                    <input type="checkbox" class="me-2 selectedId" data-id="<?= $lead['id'] ?>">
                                    <?= $key + 1 ?>
                                </td>

                                <td class=""><?= $lead['created_at'] ? date('d F, Y', strtotime($lead['created_at'])) : '' ?></td>
                                <td class="td_1"><?= $lead['full_name'] ?? '' ?></td>
                                <td><?= $lead['phone_number'] ?? '' ?></td>
                                <td><?= $lead['campaign_name'] ?? '' ?></td>

                                <td>
                                    <a href="<?= $GLOBALS['base_url'] ?>/follow-up-leads/<?= $lead['id'] ?>"
                                        class="btn btn-primary d-flex align-items-center gap-2 justify-content-between">

                                        <span>Follow Up</span>

                                        <?php if (!empty($followUpCounts[$lead['id']])): ?>
                                            <span class="followup-badge">
                                                <?= $followUpCounts[$lead['id']] ?>
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="<?= $GLOBALS['base_url'] ?>/booking-leads/<?= $lead['id'] ?>"
                                        class="btn btn-secondary d-flex align-items-center gap-2 justify-content-between">
                                        <?php if (!empty($bookingCount[$lead['id']])): ?>
                                            <span>Booked </span>
                                            <span class="followup-badge">
                                                <?= $bookingCount[$lead['id']] ?>
                                            </span>
                                        <?php else: ?>
                                            <span>Booking </span>
                                        <?php endif ?>
                                    </a>
                                </td>
                                <td><span class="bd-badge bg-success"><?= $lead['call_status'] ?? '' ?></span></td>
                                <td><span class="bd-badge bg-success"><?= $lead['call_note'] ?? 'N/A' ?></span></td>
                                <td class="d-none"><?= $lead['interest_on'] ?? '' ?></td>
                                <td>
                                    <div class="d-flex align-items-center justify-content-start gap-10">

                                        <!-- View Button (Existing) -->
                                        <a class="table__icon download" href="<?= $GLOBALS['base_url'] ?>/view-lead/<?= $lead['id'] ?>" title="View Lead">
                                            <i class="fa-regular fa-eye"></i>
                                        </a>

                                        <!-- Edit Button (Existing) -->
                                        <a class="table__icon edit" href="<?= $GLOBALS['base_url'] ?>/edit-lead/<?= $lead['id'] ?>" title="Edit Lead">
                                            <i class="fa-sharp fa-light fa-pen"></i>
                                        </a>

                                        <!-- Delete Button (Existing) -->
                                        <button class="table__icon delete removeBtnCust"
                                            data-name="<?= $lead['full_name'] ?>"
                                            data-action="lead-delete"
                                            data-id="<?= $lead['id'] ?>"
                                            title="Delete Lead">
                                            <i class="fa-regular fa-trash"></i>
                                        </button>

                                        <!-- New Last Action Update Button -->
                                        <form method="POST"
                                            action="<?= $GLOBALS['base_url'] ?>/update-last_leadAction/<?= $lead['id'] ?>"
                                            class="m-0 p-0">

                                            <select name="last_lead_action"
                                                class="form-select form-select-sm last-action-select <?= 'action-' . ($lead['last_lead_action'] ?? 'not_called') ?>"
                                                onchange="this.form.submit()">

                                                <option value=""> Update action </option>
                                                <option value="not_called" <?= $lead['last_lead_action'] == 'not_called' ? 'selected' : '' ?>>
                                                    Not Called
                                                </option>

                                                <option value="calling" <?= $lead['last_lead_action'] == 'calling' ? 'selected' : '' ?>>
                                                    Calling
                                                </option>

                                                <option value="called" <?= $lead['last_lead_action'] == 'called' ? 'selected' : '' ?>>
                                                    Called
                                                </option>

                                                <option value="visited" <?= $lead['last_lead_action'] == 'visited' ? 'selected' : '' ?>>
                                                    Visited
                                                </option>
                                            </select>

                                        </form>


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


<!-- CSV Import Modal -->
<div class="modal fade" id="importCsvModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Leads from CSV</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>

            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/leads-import-csv"
                    method="post"
                    enctype="multipart/form-data">

                    <div class="from__input-box mb-20">
                        <div class="form__input-title">
                            <label>Upload CSV File <span>*</span></label>
                        </div>
                        <div class="form__input">
                            <input type="file"
                                name="csv_file"
                                class="form-control"
                                accept=".csv"
                                required>
                        </div>
                    </div>

                    <div class="submit__btn text-center">
                        <button class="btn btn-primary">Import</button>
                    </div>
                </form>

                <p class="mt-15 text-muted small">
                    CSV header must match lead table columns.
                </p>
            </div>
        </div>
    </div>
</div>
<script>
    const base_url = "<?= $GLOBALS['base_url'] ?>";
    // Disable right-click context menu
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
    });

    // Disable Ctrl+C, Ctrl+X, Ctrl+V
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey && e.key === 'c') ||
            (e.ctrlKey && e.key === 'x') ||
            (e.ctrlKey && e.key === 'v')) {
            e.preventDefault();
        }
    });

    // Optional: Disable drag
    document.addEventListener('dragstart', function(e) {
        e.preventDefault();
    });


    const form = document.querySelector('form');
    const resetBtn = form.querySelector('button[type="reset"]');

    resetBtn.addEventListener('click', function(e) {
        e.preventDefault(); // avoid default reset
        // Clear all inputs
        form.querySelectorAll('input, select').forEach(function(el) {
            if (el.tagName === 'SELECT') el.selectedIndex = 0; // first option select
            else el.value = ''; // input clear
        });
    });
</script>


<!-- assigned user select  -->
<script>
    document.getElementById('assignUserBtn').addEventListener('click', function() {

        const userId = document.getElementById('assignUserSelect').value;

        if (!userId) {
            alert("Please select a user to assign leads!");
            return;
        }

        // Get all selected leads
        const selectedLeads = [];
        document.querySelectorAll('.selectedId:checked').forEach(cb => {
            const tr = cb.closest('tr');
            if (tr) selectedLeads.push(tr.dataset.id || tr.querySelector('.selectedId').dataset.id);
        });

        if (selectedLeads.length === 0) {
            alert("Please select at least one lead!");
            return;
        }

        // Send AJAX request
        fetch('<?= $base_url ?>/assign-multiple-leads', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'user_id=' + encodeURIComponent(userId) +
                    '&lead_ids=' + encodeURIComponent(selectedLeads.join(','))
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert("Leads assigned successfully!");

                    // Update table instantly
                    document.querySelectorAll('.selectedId:checked').forEach(cb => {
                        const tr = cb.closest('tr');
                        if (tr) {
                            tr.querySelector('td:nth-child(3)').textContent = data.user_name; // assigned user name column (customize as needed)
                            cb.checked = false; // uncheck
                        }
                    });
                } else {
                    alert("Failed to assign leads!");
                }
            });
    });
</script>

<!-- <script>
const selectAll = document.getElementById('selectAllLeads');

selectAll.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.selectedId');
    checkboxes.forEach(cb => cb.checked = this.checked);
});

// Optional: if any checkbox is manually unchecked, uncheck selectAll
document.querySelectorAll('.selectedId').forEach(cb => {
    cb.addEventListener('change', function() {
        if (!this.checked) selectAll.checked = false;

        // If all checked, then check selectAll
        const allChecked = Array.from(document.querySelectorAll('.selectedId'))
                                .every(c => c.checked);
        if (allChecked) selectAll.checked = true;
    });
});
</script> -->
<script>
const selectAll = document.getElementById('selectAllLeads');
const assignCard = document.getElementById('assignCard');

selectAll.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.selectedId');
    checkboxes.forEach(cb => cb.checked = this.checked);

    // Show the card if any checkbox is checked
    if(this.checked) {
        assignCard.style.display = 'flex';
    } else {
        // Optional: hide if none checked
        const anyChecked = Array.from(document.querySelectorAll('.selectedId')).some(c => c.checked);
        assignCard.style.display = anyChecked ? 'flex' : 'none';
    }
});

// Individual checkbox toggle
document.querySelectorAll('.selectedId').forEach(cb => {
    cb.addEventListener('change', function() {
        // If any checkbox checked → show assign card
        const anyChecked = Array.from(document.querySelectorAll('.selectedId')).some(c => c.checked);
        assignCard.style.display = anyChecked ? 'flex' : 'none';

        // Header checkbox logic
        const allChecked = Array.from(document.querySelectorAll('.selectedId')).every(c => c.checked);
        selectAll.checked = allChecked;
    });
});
</script>

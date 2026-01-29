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
        <button
            class="d-none btn btn-outline-success"
            data-bs-toggle="modal"
            data-bs-target="#importCsvModal">
            <i class="fa-light fa-file-arrow-up me-1"></i>
            Import CSV
        </button>

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
                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <button type="submit" class="btn btn-secondary w-100 ">
                            Search Lead
                        </button>
                        <a href="<?= $GLOBALS['base_url'] ?>/lead-lists" class="btn btn-success  w-100">
                            Reset
                        </a>

                        <a class="btn btn-primary w-100 "
                            href="<?= $GLOBALS['base_url'] ?>/create-lead">
                            Add Lead </a>
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
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 multiple_tables">
                    <thead>
                        <tr class="table__title table__sort">


                            <th style="min-width:60px;">S/L</th> <!-- non-sticky first column -->
                            <th class="">Create Lead</th>
                            <th class="th_1">Lead Name</th>

                            <th>Phone</th>
                            <!-- <th>Alt Phone</th> -->
                            <th>Email</th>
                            <!-- <th>Source Type</th> -->

                            <th>Follow Up</th>
                            <th>Booking</th>
                            <th>Preferred Project </th>
                            <th class="d-none">Interest On</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">

                        <?php foreach ($leads as $key => $lead): ?>
                            <tr>
                                <td><?= $key + 1 ?></td>
                                <td class=""><?= $lead['created_at'] ? date('d F, Y', strtotime($lead['created_at'])) : '' ?></td>
                                <td class="td_1"><?= $lead['full_name'] ?? '' ?></td>
                                <td><?= $lead['phone'] ?? '' ?></td>
                                <td><?= $lead['email'] ?? '' ?></td>

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
                                <td><span class="bd-badge bg-success"><?= $lead['project_type'] ?? '' ?></span></td>
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
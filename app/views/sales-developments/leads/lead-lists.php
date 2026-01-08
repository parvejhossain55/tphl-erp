<?php
$page_title = "leads Page";
$page = 'Leads';
$addPage = 'Add Leads';
$addPage = true;
$show_breadcrumb = true;
?>

<div class="row lead-select">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

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

    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4 col-sm-4">
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

    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4 col-sm-4">
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
    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4 col-sm-4">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-sharp fa-regular fa-user"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Today Follow Up</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title"><?= $paymentCount ?></h4>
                    </div>
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


                            <th class="pe-2">S/L</th>
                            <th>Create Lead</th>

                            <th>Lead Name</th>

                            <th>Phone</th>
                            <!-- <th>Alt Phone</th> -->
                            <th>Email</th>
                            <!-- <th>Source Type</th> -->
                            <th>Assigned Agent</th>
                            <th>Preferred Location</th>
                            <th>Follow Up</th>
                            <th>Booking</th>
                            <th>Preferred Project </th>
                            <th class="d-none">Interest On</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">

                        <?php foreach ($leads as $key => $lead): ?>
                            <tr>
                                <td><?= $key + 1 ?></td>
                                <td><?= $lead['created_at'] ? date('d F, Y', strtotime($lead['created_at'])) : '' ?></td>
                                <td><?= $lead['lead_name'] ?? '' ?></td>
                                <td><?= $lead['phone'] ?? '' ?></td>
                                <td><?= $lead['email'] ?? '' ?></td>
                                <td><?= $lead['agent_name'] ?? '' ?></td>
                                <td><?= $lead['pref_location'] ?? 'N/A' ?></td>
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
                                        <a class="table__icon download" href="<?= $GLOBALS['base_url'] ?>/view-lead/<?= $lead['id'] ?>">
                                            <i class="fa-regular fa-eye"></i>
                                        </a>
                                        <a class="table__icon edit" href="<?= $GLOBALS['base_url'] ?>/edit-lead/<?= $lead['id'] ?>">
                                            <i class="fa-sharp fa-light fa-pen"></i>
                                        </a>
                                        <button class="removeBtn table__icon delete">
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
</script>
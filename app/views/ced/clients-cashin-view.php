<?php
$page_title = "Clients Cash in";
$page = 'Clients';
$show_breadcrumb = true;
?>

<div class="row">

    <!-- ══ STAT CARDS (existing) ══ -->
    <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-light fa-users"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Clients</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title"><?= $paymentStats['total_clients'] ?? 0 ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-light fa-badge-check"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Full Payment Completion</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title"><?= $paymentStats['full_payment_completed'] ?? 0 ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-sharp fa-regular fa-check"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Approved Bookings</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title"><?= $paymentStats['approved_bookings'] ?? 0 ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ COLLECTION FINANCIAL SUMMARY CARDS ══ -->
    <div class="col-12 mb-3">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-chart-pie text-primary me-2"></i>Collection & EMI Financial Summary
                    </h6>
                    <small class="text-muted">Overview of total collections, land shares, and EMI verification status</small>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="openProjectCollectionModal()">
                    <i class="fa-solid fa-list-check me-1"></i> View Project-Wise Breakdown
                </button>
            </div>
            <div class="card-body p-3">
                <div class="row g-3">
                    <!-- Total Collected -->
                    <div class="col-xl col-md-4 col-sm-6">
                        <div class="card__wrapper border-start border-4 border-primary h-100 p-3" style="cursor: pointer;" onclick="openProjectCollectionModal('total')" title="Click to view project breakdown (Verified + Unverified)">
                            <div class="d-flex align-items-center gap-2">
                                <div class="card__icon" style="background: rgba(13, 110, 253, 0.1); color: #0d6efd; padding: 10px; border-radius: 8px;">
                                    <span><i class="fa-solid fa-vault fs-5"></i></span>
                                </div>
                                <div class="card__title-wrap">
                                    <h6 class="card__sub-title mb-1 text-muted extra-small text-uppercase fw-bold">Total Collected</h6>
                                    <h5 class="fw-bold mb-0 text-primary">৳<?= number_format($collectionStats['total_collected'] ?? 0, 2) ?></h5>
                                    <small class="text-muted extra-small d-block" style="font-size: 10px;"><i class="fa-solid fa-circle-info me-1 text-primary"></i>Verified + Unverified (View Breakdown)</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Land Share Collected -->
                    <div class="col-xl col-md-4 col-sm-6">
                        <div class="card__wrapper border-start border-4 border-info h-100 p-3" style="cursor: pointer;" onclick="openProjectCollectionModal('land_share')" title="Click to view land share breakdown (Booking Money + Registration Fee)">
                            <div class="d-flex align-items-center gap-2">
                                <div class="card__icon" style="background: rgba(13, 202, 240, 0.1); color: #0dcaf0; padding: 10px; border-radius: 8px;">
                                    <span><i class="fa-solid fa-map-location-dot fs-5"></i></span>
                                </div>
                                <div class="card__title-wrap">
                                    <h6 class="card__sub-title mb-1 text-muted extra-small text-uppercase fw-bold">Land Share Collected</h6>
                                    <h5 class="fw-bold mb-0 text-info">৳<?= number_format($collectionStats['total_land_share_collected'] ?? 0, 2) ?></h5>
                                    <small class="text-muted extra-small d-block" style="font-size: 10px;"><i class="fa-solid fa-circle-info me-1 text-info"></i>Booking + Reg Fee (View Breakdown)</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total EMI Collected -->
                    <div class="col-xl col-md-4 col-sm-6">
                        <div class="card__wrapper border-start border-4 border-secondary h-100 p-3" style="cursor: pointer;" onclick="openProjectCollectionModal('emi')" title="Click to view total EMI breakdown (Verified + Unverified)">
                            <div class="d-flex align-items-center gap-2">
                                <div class="card__icon" style="background: rgba(108, 117, 125, 0.1); color: #6c757d; padding: 10px; border-radius: 8px;">
                                    <span><i class="fa-solid fa-receipt fs-5"></i></span>
                                </div>
                                <div class="card__title-wrap">
                                    <h6 class="card__sub-title mb-1 text-muted extra-small text-uppercase fw-bold">Total EMI Collected</h6>
                                    <h5 class="fw-bold mb-0 text-dark">৳<?= number_format($collectionStats['total_emi_collected'] ?? 0, 2) ?></h5>
                                    <small class="text-muted extra-small d-block" style="font-size: 10px;"><i class="fa-solid fa-circle-info me-1 text-secondary"></i>Verified + Unverified (View Breakdown)</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Verified EMI Collected -->
                    <div class="col-xl col-md-6 col-sm-6">
                        <div class="card__wrapper border-start border-4 border-success h-100 p-3" style="cursor: pointer;" onclick="openProjectCollectionModal('verified')" title="Click to view verified EMI breakdown">
                            <div class="d-flex align-items-center gap-2">
                                <div class="card__icon" style="background: rgba(25, 135, 84, 0.1); color: #198754; padding: 10px; border-radius: 8px;">
                                    <span><i class="fa-solid fa-circle-check fs-5"></i></span>
                                </div>
                                <div class="card__title-wrap">
                                    <h6 class="card__sub-title mb-1 text-muted extra-small text-uppercase fw-bold">Verified EMI</h6>
                                    <h5 class="fw-bold mb-0 text-success">৳<?= number_format($collectionStats['total_verified_emi_collected'] ?? 0, 2) ?></h5>
                                    <small class="text-muted extra-small"><i class="fa-solid fa-hand-pointer me-1"></i>View Breakdown</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Unverified EMI Collected -->
                    <div class="col-xl col-md-6 col-sm-6">
                        <div class="card__wrapper border-start border-4 border-warning h-100 p-3" style="cursor: pointer;" onclick="openProjectCollectionModal('unverified')" title="Click to view unverified EMI breakdown">
                            <div class="d-flex align-items-center gap-2">
                                <div class="card__icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107; padding: 10px; border-radius: 8px;">
                                    <span><i class="fa-solid fa-clock-rotate-left fs-5"></i></span>
                                </div>
                                <div class="card__title-wrap">
                                    <h6 class="card__sub-title mb-1 text-muted extra-small text-uppercase fw-bold">Unverified EMI</h6>
                                    <h5 class="fw-bold mb-0 text-warning">৳<?= number_format($collectionStats['total_unverified_emi_collected'] ?? 0, 2) ?></h5>
                                    <small class="text-muted extra-small"><i class="fa-solid fa-hand-pointer me-1"></i>View Breakdown</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ PROJECT & STATUS FILTER DROPDOWN ══ -->
    <div class="col-12">
        <div class="card__wrapper">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-10">
                <form method="GET" action="<?= $GLOBALS['base_url'] ?>/clients-cashin-view" id="filterForm" class="d-flex align-items-center gap-10 w-100 flex-wrap">
                    <div class="d-flex align-items-center gap-10">
                        <div class="card__icon">
                            <span><i class="fa-light fa-filter"></i></span>
                        </div>
                        <div>
                            <h6 class="card__sub-title mb-0">Filter Data</h6>
                        </div>
                        <select name="project_id" id="projectFilter" class="form-select select2" style="max-width: 350px;" onchange="document.getElementById('filterForm').submit()">
                            <option value="">All Projects</option>
                            <?php if (!empty($projects)): ?>
                                <?php foreach ($projects as $project): ?>
                                    <option value="<?= $project['id'] ?>"
                                        <?= ($selectedProjectId == $project['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($project['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <select name="verification_status" class="form-select" style="max-width: 200px;" onchange="document.getElementById('filterForm').submit()">
                            <option value="">All Statuses</option>
                            <option value="pending" <?= (isset($selectedVerificationStatus) && $selectedVerificationStatus == 'pending') ? 'selected' : '' ?>>Pending Verification</option>
                            <option value="verified" <?= (isset($selectedVerificationStatus) && $selectedVerificationStatus == 'verified') ? 'selected' : '' ?>>Verified</option>
                            <option value="rejected" <?= (isset($selectedVerificationStatus) && $selectedVerificationStatus == 'rejected') ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
                    <?php if ($selectedProjectId && $selectedProjectName): ?>
                        <div class="d-flex align-items-center gap-10 ms-auto">
                            <span class="bd-badge bg-primary">
                                <i class="fa-light fa-building me-1"></i>
                                <?= htmlspecialchars($selectedProjectName) ?>
                            </span>
                            <a href="<?= $GLOBALS['base_url'] ?>/clients-cashin-view<?= isset($selectedVerificationStatus) && $selectedVerificationStatus ? '?verification_status=' . $selectedVerificationStatus : '' ?>" class="bd-badge bg-danger" style="text-decoration:none;">
                                <i class="fa-light fa-times"></i> Clear Project
                            </a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- ══ PROJECT FLAT STAT CARDS (shown only when project is selected) ══ -->
    <?php if ($selectedProjectId && $flatStats): ?>
        <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon">
                        <span><i class="fa-light fa-building"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Total Flats</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title"><?= (int)$flatStats['total_flats'] ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper" id="bookedFlatsCard" style="cursor: pointer;" onclick="openBookedFlatsModal()" title="Click to view booked flats list">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon">
                        <span style="color: #f59e0b; background: rgba(245, 158, 11, 0.1);"><i class="fa-light fa-bookmark"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Booked Flats</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title" style="color: #f59e0b;"><?= (int)$flatStats['booked_flats'] ?></h4>
                            <small class="text-muted"><i class="fa-light fa-hand-pointer"></i> Click to view</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-6">
            <div class="card__wrapper" id="availableFlatsCard" style="cursor: pointer;" onclick="openAvailableFlatsModal()" title="Click to view available flats list">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon">
                        <span style="color: #10b981; background: rgba(16, 185, 129, 0.1);"><i class="fa-light fa-check-circle"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Available Flats</h6>
                        <div class="d-flex flex-wrap align-items-end gap-10">
                            <h4 class="card__title" style="color: #10b981;"><?= (int)$flatStats['available_flats'] ?></h4>
                            <small class="text-muted"><i class="fa-light fa-hand-pointer"></i> Click to view</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    <?php endif; ?>

    <!-- ══ CLIENTS TABLE ══ -->
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="d-flex justify-content-between align-items-center mb-20">
                <h4>
                    Clients Information
                    <?php if ($selectedProjectName): ?>
                        <small class="text-muted" style="font-size: 14px; font-weight: 400;">
                            — <?= htmlspecialchars($selectedProjectName) ?> (<?= count($clients) ?> records)
                        </small>
                    <?php endif; ?>
                </h4>
                <a href="<?= $GLOBALS['base_url'] ?>/csv-import-form" class="btn btn-sm btn-success rounded-2 shadow-sm px-3">
                    <i class="fa-light fa-file-csv me-1 fs-6"></i>Import CSV (EMI & Dev Charge)
                </a>
            </div>

            <div class="table__wrapper table-responsive">
                <table class="table mb-20 multiple_tables">
                    <thead>
                        <tr class="table__title table__sort">
                            <th>Client Name</th>
                            <th>Company</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Booking Agent(s)</th>
                            <th>Designation</th>
                            <th>Total Agreement Value</th>
                            <th>Total Paid</th>
                            <th>Last Payment Date</th>
                            <th>Current Month</th>
                            <th>Profile</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php if (!empty($clients)): ?>
                            <?php foreach ($clients as $client): ?>
                                <tr>
                                    <td><?= htmlspecialchars($client['client_name']) ?></td>
                                    <td><?= htmlspecialchars($client['company'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($client['phone'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($client['email'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($client['agent_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($client['designation'] ?? 'N/A') ?></td>

                                    <td>TK.<?= number_format($client['total_agreement_value'] ?? 0, 2) ?></td>
                                    <td>TK.<?= number_format($client['total_paid'] ?? 0, 2) ?></td>
                                    <td>
                                        <?= $client['last_payment_date'] ? date('d M, Y', strtotime($client['last_payment_date'])) : 'No Payment' ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $cms = $client['current_month_status'] ?? 'N/A';
                                            if ($cms === 'paid') echo '<span class="badge bg-success">Paid</span>';
                                            elseif ($cms === 'matured') echo '<span class="badge bg-info text-dark" style="background-color: #0dcaf0; color: #000;">Matured</span>';
                                            elseif ($cms === 'pending') echo '<span class="badge bg-warning text-dark">Pending</span>';
                                            elseif ($cms === 'due') echo '<span class="badge bg-danger">Due</span>';
                                            elseif ($cms === 'future') echo '<span class="badge bg-secondary">Future</span>';
                                            else echo '<span class="text-muted">-</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary py-0 px-2"
                                            onclick="openProfileModal(<?= $client['lead_id'] ?>)"
                                            title="View Client Profile" style="font-size:12px;">
                                            <i class="fa-light fa-id-card me-1"></i>Profile
                                        </button>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center justify-content-start gap-2">
                                            <button class="table__icon download"
                                                onclick="openProjectsModal(<?= $client['lead_id'] ?>, '<?= htmlspecialchars($client['client_name'], ENT_QUOTES) ?>')"
                                                title="View Projects">
                                                <i class="fa-regular fa-eye"></i>
                                            </button>
                                            <?php if (!empty($client['booking_id'])): ?>
                                                <?php $fCount = (int)($client['total_followup_count'] ?? 0); ?>
                                                <button type="button"
                                                        onclick="openClientEmiFollowupModal(<?= $client['booking_id'] ?>, '<?= htmlspecialchars($client['client_name'], ENT_QUOTES) ?>', <?= (int)($client['total_emi_count'] ?? 36) ?>, <?= $client['lead_id'] ?? 'null' ?>)"
                                                        class="btn btn-sm btn-outline-primary py-1 px-2 text-nowrap"
                                                        style="font-size:11px;"
                                                        title="Add / View Follow Up Notes">
                                                    <i class="fa-light fa-comment-dots me-1"></i>Follow Up
                                                    <span class="badge <?= $fCount > 0 ? 'bg-primary' : 'bg-secondary' ?> rounded-pill ms-1 followup-count-badge" data-booking-id="<?= $client['booking_id'] ?>" style="font-size:10px;"><?= $fCount ?></span>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                      
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- ══ PROJECTS MODAL (existing — per-lead land shares) ══ -->
<div class="modal fade" id="projectsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Land Shares - <span id="modal_client_name"></span></h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>

            <div class="modal-body">
                <div id="projects_loading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading land shares...</p>
                </div>

                <div id="projects_content" style="display: none;">
                    <div class="table__wrapper table-responsive">
                        <table class="table">
                            <thead>
                                <tr class="table__title">
                                    <th>Project Name</th>
                                    <th>Unit - Floor</th>
                                    <th>Added By</th>
                                    <th>Flat Size</th>
                                    <th>Total Price</th>
                                    <th>Agreement Value</th>
                                    <th>Total Paid</th>
                                    <th>Service Fee</th>
                                    <th>Booking Status</th>
                                    <th>Land Share Status</th>
                                    <th>Last Payment</th>
                                    <th>Current Month</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="projects_table_body">
                                <!-- Dynamic content will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="projects_error" class="alert alert-danger" style="display: none;">
                    <i class="fa-solid fa-exclamation-triangle"></i>
                    <span id="error_message"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== CLIENT PROFILE MODAL ===== -->
<div class="modal fade" id="clientProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:14px; overflow:hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #1e3a5f, #2563eb); color:#fff; border:0; padding:18px 24px;">
                <h5 class="modal-title" style="font-weight:600;">
                    <i class="fa-light fa-id-card me-2"></i>Client Profile
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="profileModalBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Loading profile...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══ BOOKED FLATS MODAL (shown when project selected) ══ -->
<?php if ($selectedProjectId && !empty($bookedFlats)): ?>
<div class="modal fade" id="bookedFlatsModal" tabindex="-1" aria-labelledby="bookedFlatsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bookedFlatsModalLabel">
                    <i class="fa-light fa-bookmark me-2"></i>
                    Booked Flats — <?= htmlspecialchars($selectedProjectName) ?>
                    <span class="bd-badge bg-primary ms-2"><?= count($bookedFlats) ?> flats</span>
                </h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="table__wrapper table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr class="table__title">
                                <th>Unit</th>
                                <th>Floor</th>
                                <th>Size</th>
                                <th>Client</th>
                                <th>Phone</th>
                                <th>Agent</th>
                                <th>Agreement Value</th>
                                <th>Total Paid</th>
                                <th>Due</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookedFlats as $flat): ?>
                                <?php
                                    $due = max(0, (float)($flat['agreement_value'] ?? 0) - (float)($flat['total_paid'] ?? 0));
                                ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($flat['flat_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($flat['floor_no'] ?? 'N/A') ?></td>
                                    <td><?= number_format($flat['sqft'] ?? 0) ?> sqft</td>
                                    <td class="fw-semibold"><?= htmlspecialchars($flat['client_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($flat['client_phone'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($flat['agent_name'] ?? 'N/A') ?></td>
                                    <td>TK.<?= number_format($flat['agreement_value'] ?? 0, 2) ?></td>
                                    <td class="text-success fw-semibold">TK.<?= number_format($flat['total_paid'] ?? 0, 2) ?></td>
                                    <td class="<?= $due > 0 ? 'text-danger' : 'text-success' ?> fw-semibold">
                                        TK.<?= number_format($due, 2) ?>
                                    </td>
                                    <td>
                                        <span class="bd-badge <?= $flat['booking_status'] === 'booked' ? 'bg-success' : 'bg-warning' ?>">
                                            <?= htmlspecialchars(ucfirst($flat['booking_status'] ?? 'N/A')) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a class="table__icon download"
                                            href="<?= $GLOBALS['base_url'] ?>/clients-cash-in-history-view/<?= $flat['booking_id'] ?>"
                                            title="View Payment History">
                                            <i class="fa-regular fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ══ AVAILABLE FLATS MODAL (shown when project selected) ══ -->
<?php if ($selectedProjectId && !empty($availableFlats)): ?>
<div class="modal fade" id="availableFlatsModal" tabindex="-1" aria-labelledby="availableFlatsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="availableFlatsModalLabel">
                    <i class="fa-light fa-check-circle me-2 text-success"></i>
                    Available Flats — <?= htmlspecialchars($selectedProjectName) ?>
                    <span class="bd-badge bg-success ms-2"><?= count($availableFlats) ?> flats</span>
                </h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="table__wrapper table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr class="table__title">
                                <th>Unit</th>
                                <th>Floor</th>
                                <th>Size</th>
                                <th>Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($availableFlats as $flat): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($flat['flat_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($flat['floor_no'] ?? 'N/A') ?></td>
                                    <td><?= number_format($flat['sqft'] ?? 0) ?> sqft</td>
                                    <td class="text-primary fw-semibold">TK.<?= number_format($flat['price_value'] ?? 0, 2) ?></td>
                                    <td>
                                        <span class="bd-badge bg-success">
                                            <?= htmlspecialchars(ucfirst($flat['flat_status'] ?? 'Available')) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ===== CLIENT EMI FOLLOW UP MODAL ===== -->
<div class="modal fade" id="clientEmiFollowupModal" tabindex="-1" aria-labelledby="clientEmiFollowupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:14px; overflow:hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #1e3a5f, #2563eb); color:#fff; border:0; padding:16px 24px;">
                <h5 class="modal-title" style="font-weight:600;">
                    <i class="fa-light fa-comment-dots me-2"></i>EMI Follow Up Notes — <span id="followup_modal_client_name">Client</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row ">
                    <div class="col-md-5 border-end pe-3">
                        <h6 class="text-primary mb-3"><i class="fa-light fa-plus-circle me-1"></i>Add New Follow Up Note</h6>
                        <input type="hidden" id="modalFollowupBookingId">
                        
                        <div class="mb-3" id="modalFollowupProjectContainer">
                            <label class="form-label fs-13 font-semibold text-muted">Project & Flat</label>
                            <select id="modalFollowupBookingSelect" class="form-select form-select-sm" onchange="onFollowupBookingChange()">
                                <!-- dynamically loaded -->
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fs-13 font-semibold text-muted">Installment / EMI</label>
                            <select id="modalFollowupEmiSelect" class="form-select form-select-sm" onchange="loadClientEmiFollowups($('#modalFollowupBookingId').val())">
                                <option value="0">General / Overall Follow Up</option>
                                <option value="1">EMI #1</option>
                                <option value="2">EMI #2</option>
                                <option value="3">EMI #3</option>
                                <option value="4">EMI #4</option>
                                <option value="5">EMI #5</option>
                                <option value="6">EMI #6</option>
                                <option value="7">EMI #7</option>
                                <option value="8">EMI #8</option>
                                <option value="9">EMI #9</option>
                                <option value="10">EMI #10</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fs-13 font-semibold text-muted">Date of Activity <span class="text-danger">*</span></label>
                            <input type="date" id="modalFollowupActivityDate" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-13 font-semibold text-muted">Mode of Activity <span class="text-danger">*</span></label>
                            <select id="modalFollowupActivityMode" class="form-select form-select-sm">
                                <option value="">Select Mode</option>
                                <option value="Phone Call">Phone Call</option>
                                <option value="WhatsApp">WhatsApp</option>
                                <option value="SMS">SMS</option>
                                <option value="Email">Email</option>
                                <option value="Meeting">Meeting</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-13 font-semibold text-muted">Reason for Activity <span class="text-danger">*</span></label>
                            <select id="modalFollowupActivityReason" class="form-select form-select-sm">
                                <option value="">Select Reason</option>
                                <option value="EMI Payment Follow-up">EMI Payment Follow-up</option>
                                <option value="Payment Reminder (General)">Payment Reminder (General)</option>
                                <option value="Development Charge Follow-up">Development Charge Follow-up</option>
                                <option value="Overdue Payment Follow-up">Overdue Payment Follow-up</option>
                                <option value="Handover / Registration Discussion">Handover / Registration Discussion</option>
                                <option value="Document Collection">Document Collection</option>
                                <option value="Client Query / Issue Resolution">Client Query / Issue Resolution</option>
                                <option value="Commitment Date Extension">Commitment Date Extension</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fs-13 font-semibold text-muted">Discussion Note / Remarks <span class="text-danger">*</span></label>
                            <textarea id="modalFollowupNoteInput" class="form-control form-control-sm" rows="3" placeholder="Enter follow up discussion details, commitment date, or notes..."></textarea>
                        </div>
                        <button type="button" class="btn btn-primary w-100 shadow-sm" onclick="saveClientEmiFollowup()">
                            <i class="fa-light fa-paper-plane me-1"></i>Save Follow Up Note
                        </button>
                    </div>
                    
                    <div class="col-md-7 ps-3">
                        <h6 class="text-dark mb-3"><i class="fa-light fa-clock-rotate-left me-1"></i>Previous Follow Up History</h6>
                        <div id="modalFollowupHistoryContainer" style="max-height: 320px; overflow-y: auto;">
                            <div class="text-center text-muted py-4">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading history...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let projectsModal;

    document.addEventListener('DOMContentLoaded', function () {
        projectsModal = new bootstrap.Modal(document.getElementById('projectsModal'));
    });

    // ── Project Filter ──────────────────────────────────────────────────────
    function filterByProject(projectId) {
        if (projectId) {
            window.location.href = '<?= $GLOBALS['base_url'] ?>/clients-cashin-view?project_id=' + projectId;
        } else {
            window.location.href = '<?= $GLOBALS['base_url'] ?>/clients-cashin-view';
        }
    }

    // ── Booked Flats Modal ──────────────────────────────────────────────────
    function openBookedFlatsModal() {
        const modalEl = document.getElementById('bookedFlatsModal');
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    }

    // ── Available Flats Modal ───────────────────────────────────────────────
    function openAvailableFlatsModal() {
        const modalEl = document.getElementById('availableFlatsModal');
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    }

    function openProjectsModal(leadId, clientName) {
        console.log('Opening modal for lead ID:', leadId);
        console.log('Client name:', clientName);

        // Set client name
        document.getElementById('modal_client_name').textContent = clientName;

        // Reset modal state
        document.getElementById('projects_loading').style.display = 'block';
        document.getElementById('projects_content').style.display = 'none';
        document.getElementById('projects_error').style.display = 'none';

        // Show modal
        projectsModal.show();

        // Construct URL
        const url = '<?= $GLOBALS['base_url'] ?>/get-client-lands?lead_id=' + leadId;
        console.log('Fetching from URL:', url);

        // Fetch projects
        fetch(url)
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);

                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }

                return response.text(); // First get as text to see what we're getting
            })
            .then(text => {
                console.log('Response text:', text);

                // Try to parse as JSON
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed data:', data);

                    if (data.success) {
                        if (data.lands && data.lands.length > 0) {
                            displayProjects(data.lands, leadId);
                        } else {
                            showError('No land shares found for this client');
                        }
                    } else {
                        showError(data.message || 'Failed to load land shares');
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response was:', text);
                    showError('Invalid response from server. Please check console for details.');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showError('Failed to load land shares: ' + error.message);
            });
    }

    function displayProjects(lands, leadId = null) {
        console.log('Displaying projects:', lands);

        const tbody = document.getElementById('projects_table_body');
        tbody.innerHTML = '';

        lands.forEach(land => {
            const row = document.createElement('tr');

            // Calculate due amount
            const dueAmount = (parseFloat(land.total_price) || 0) - (parseFloat(land.total_paid) || 0);

            // Determine status classes
            const bookingStatusClass = getStatusClass(land.booking_status);
            const landshareStatusClass = getStatusClass(land.landshare_status);
            const transferBadge = parseInt(land.transfer_status) === 1 
                ? '<span class="badge bg-warning text-dark ms-1" style="font-size:0.7em;">Transferred</span>' 
                : '<span class="badge bg-success ms-1" style="font-size:0.7em;">Current</span>';

            row.innerHTML = `
            <td>${escapeHtml(land.project_name)} ${transferBadge}</td>
            <td>${escapeHtml(land.unit_name)} - Floor ${escapeHtml(land.flat_no)}</td>
            <td>${escapeHtml(land.booking_agent_name || 'System')}</td>
            <td>${Number(land.flat_size).toLocaleString('en-BD')} sqft</td>
            <td>TK.${Number(land.total_price).toLocaleString('en-BD', { minimumFractionDigits: 2 })}</td>
            <td>TK.${Number(land.agreement_value).toLocaleString('en-BD', { minimumFractionDigits: 2 })}</td>
            <td>TK.${Number(land.total_paid || 0).toLocaleString('en-BD', { minimumFractionDigits: 2 })}</td>
            <td>
                <div>
                    <strong>Fee:</strong> TK.${Number(land.total_service_fee || 0).toLocaleString('en-BD', { minimumFractionDigits: 2 })}
                </div>
                <div class="text-muted small">
                    <strong>Balance:</strong> TK.${Number(land.service_fee_balance || 0).toLocaleString('en-BD', { minimumFractionDigits: 2 })}
                </div>
            </td>
            <td>
                <span class="bd-badge ${bookingStatusClass}">
                    ${escapeHtml(land.booking_status || 'N/A')}
                </span>
            </td>
            <td>
                <span class="bd-badge ${landshareStatusClass}">
                    ${escapeHtml(land.landshare_status || 'N/A')}
                </span>
            </td>
            <td>
                ${land.last_payment_date ? formatDate(land.last_payment_date) : 'No Payment'}
            </td>
            <td>
                ${getCurrentMonthBadge(land.current_month_status)}
            </td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <a class="table__icon download"
                        href="<?= $GLOBALS['base_url'] ?>/clients-cash-in-history-view/${land.booking_id}"
                        title="View Transaction History">
                        <i class="fa-regular fa-eye"></i>
                    </a>
                    <button type="button"
                        class="btn btn-sm btn-outline-primary py-1 px-2 text-nowrap"
                        style="font-size:11px;"
                        onclick="openClientEmiFollowupModal(${land.booking_id}, '${escapeHtml(land.client_name || '')}', ${land.total_emi_count || 36}, ${leadId})"
                        title="Add / View Follow Up Notes">
                        <i class="fa-light fa-comment-dots me-1"></i>Follow Up
                        <span class="badge ${(land.total_followup_count || 0) > 0 ? 'bg-primary' : 'bg-secondary'} rounded-pill ms-1 followup-count-badge" data-booking-id="${land.booking_id}" style="font-size:10px;">
                            ${land.total_followup_count || 0}
                        </span>
                    </button>
                </div>
            </td>
        `;

            tbody.appendChild(row);
        });

        // Show content, hide loading
        document.getElementById('projects_loading').style.display = 'none';
        document.getElementById('projects_content').style.display = 'block';
    }

    function showError(message) {
        document.getElementById('error_message').textContent = message;
        document.getElementById('projects_loading').style.display = 'none';
        document.getElementById('projects_content').style.display = 'none';
        document.getElementById('projects_error').style.display = 'block';
    }

    function getCurrentMonthBadge(status) {
        if (!status) return '<span class="text-muted">-</span>';
        
        switch(status.toLowerCase()) {
            case 'paid':
                return '<span class="badge bg-success">Paid</span>';
            case 'matured':
                return '<span class="badge bg-info text-dark" style="background-color: #0dcaf0; color: #000;">Matured</span>';
            case 'pending':
                return '<span class="badge bg-warning text-dark">Pending</span>';
            case 'due':
                return '<span class="badge bg-danger">Due</span>';
            case 'future':
                return '<span class="badge bg-secondary">Future</span>';
            default:
                return '<span class="text-muted">-</span>';
        }
    }

    function getStatusClass(status) {
        if (!status) return 'bg-secondary';

        status = status.toLowerCase();

        if (status === 'booked' || status === 'active' || status === 'approved' || status === 'paid') {
            return 'bg-success';
        } else if (status === 'pending') {
            return 'bg-warning';
        } else if (status === 'cancelled' || status === 'rejected') {
            return 'bg-danger';
        } else {
            return 'bg-info';
        }
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        const options = { day: '2-digit', month: 'short', year: 'numeric' };
        return date.toLocaleDateString('en-GB', options);
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }

    // ─── Client Profile Modal ──────────────────────────────────────────
    function openProfileModal(leadId) {
        const body = document.getElementById('profileModalBody');
        body.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Loading profile...</p></div>';
        new bootstrap.Modal(document.getElementById('clientProfileModal')).show();

        fetch('<?= $GLOBALS['base_url'] ?>/get-client-profile?lead_id=' + leadId)
            .then(r => r.json())
            .then(data => {
                if (data.success && data.profile) {
                    body.innerHTML = renderProfile(data.profile);
                } else {
                    body.innerHTML = '<div class="alert alert-danger m-3"><i class="fa-light fa-exclamation-triangle me-1"></i>' + (data.message || 'Profile not found') + '</div>';
                }
            })
            .catch(err => {
                body.innerHTML = '<div class="alert alert-danger m-3"><i class="fa-light fa-exclamation-triangle me-1"></i>Failed to load: ' + err.message + '</div>';
            });
    }

    function renderProfile(p) {
        const v = (val) => escapeHtml(val || '—');
        const formatDate = (d) => {
            if (!d) return '—';
            const dt = new Date(d);
            return dt.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        };
        const imgBase = '<?= $GLOBALS['base_url'] ?>';
        const clientImg = p.lead_image ? `<img src="${imgBase}/public/uploads/leads/${p.lead_image}" class="profile-img" alt="Client">` : '<div class="profile-img-placeholder"><i class="fa-light fa-user fa-3x"></i></div>';
        const nomineeImg = p.nomi_image ? `<img src="${imgBase}/public/uploads/leads/${p.nomi_image}" class="profile-img" alt="Nominee">` : '<div class="profile-img-placeholder"><i class="fa-light fa-user-shield fa-3x"></i></div>';

        return `
        <div class="profile-card">
            <!-- CLIENT SECTION -->
            <div class="profile-section">
                <div class="profile-section-header">
                    <i class="fa-light fa-user me-2"></i>Client Information
                </div>
                <div class="profile-content">
                    <div class="row">
                        <div class="col-md-3 text-center mb-3">
                            ${clientImg}
                        </div>
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-sm-6 mb-2"><div class="profile-label">Full Name</div><div class="profile-value">${v(p.full_name)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">Name (Bangla)</div><div class="profile-value">${v(p.lead_name_bn)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">Phone</div><div class="profile-value">${v(p.phone)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">Alt. Phone</div><div class="profile-value">${v(p.alt_phone_number)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">Email</div><div class="profile-value">${v(p.email)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">Company</div><div class="profile-value">${v(p.company)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">Designation</div><div class="profile-value">${v(p.designation)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">Date of Birth</div><div class="profile-value">${formatDate(p.dob)}</div></div>
                            </div>
                        </div>
                    </div>
                    <hr style="border-color:#e5e7eb;">
                    <div class="row">
                        <div class="col-sm-6 mb-2"><div class="profile-label">Father's Name</div><div class="profile-value">${v(p.father_name)}</div></div>
                        <div class="col-sm-6 mb-2"><div class="profile-label">Mother's Name</div><div class="profile-value">${v(p.mother_name)}</div></div>
                        <div class="col-sm-6 mb-2"><div class="profile-label">Father (Bangla)</div><div class="profile-value">${v(p.father_name_bn)}</div></div>
                        <div class="col-sm-6 mb-2"><div class="profile-label">Mother (Bangla)</div><div class="profile-value">${v(p.mother_name_bn)}</div></div>
                        <div class="col-sm-4 mb-2"><div class="profile-label">${v(p.identity_type) || 'NID'} No.</div><div class="profile-value">${v(p.nid_or_passport)}</div></div>
                        <div class="col-sm-4 mb-2"><div class="profile-label">Nationality</div><div class="profile-value">${v(p.nationality)}</div></div>
                        <div class="col-sm-4 mb-2"><div class="profile-label">Religion</div><div class="profile-value">${v(p.religion)}</div></div>
                        <div class="col-sm-4 mb-2"><div class="profile-label">Residency</div><div class="profile-value">${v(p.residency_type)}</div></div>
                        <div class="col-sm-4 mb-2"><div class="profile-label">Country</div><div class="profile-value">${v(p.country)}</div></div>
                    </div>
                    <hr style="border-color:#e5e7eb;">
                    <div class="row">
                        <div class="col-sm-6 mb-2"><div class="profile-label">Present Address</div><div class="profile-value">${v(p.address || p.present_add_bn)}</div></div>
                        <div class="col-sm-6 mb-2"><div class="profile-label">Permanent Address</div><div class="profile-value">${v(p.permanent_address || p.permanent_add_bn)}</div></div>
                    </div>
                </div>
            </div>

            <!-- NOMINEE SECTION -->
            <div class="profile-section" style="margin-top:0;">
                <div class="profile-section-header" style="background: linear-gradient(135deg, #065f46, #059669);">
                    <i class="fa-light fa-user-shield me-2"></i>Nominee Information
                </div>
                <div class="profile-content">
                    ${p.nominee_name ? `
                    <div class="row">
                        <div class="col-md-3 text-center mb-3">
                            ${nomineeImg}
                        </div>
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-sm-6 mb-2"><div class="profile-label">Nominee Name</div><div class="profile-value">${v(p.nominee_name)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">Relation</div><div class="profile-value">${v(p.relation_with_lead)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">Father's Name</div><div class="profile-value">${v(p.nomi_father_name)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">Mother's Name</div><div class="profile-value">${v(p.nomi_mother_name)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">Phone</div><div class="profile-value">${v(p.nomi_phone)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">Email</div><div class="profile-value">${v(p.nomi_email)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">Profession</div><div class="profile-value">${v(p.nomi_profession)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">Date of Birth</div><div class="profile-value">${formatDate(p.nomi_dob)}</div></div>
                                <div class="col-sm-6 mb-2"><div class="profile-label">NID / Passport</div><div class="profile-value">${v(p.nomi_nid_passport)}</div></div>
                            </div>
                        </div>
                    </div>
                    <hr style="border-color:#e5e7eb;">
                    <div class="row">
                        <div class="col-sm-6 mb-2"><div class="profile-label">Present Address</div><div class="profile-value">${v(p.nomi_present_add)}</div></div>
                        <div class="col-sm-6 mb-2"><div class="profile-label">Permanent Address</div><div class="profile-value">${v(p.nomi_permanent_address)}</div></div>
                    </div>
                    ` : '<p class="text-muted text-center py-3 mb-0"><i class="fa-light fa-info-circle me-1"></i>No nominee information on file.</p>'}
                </div>
            </div>
        </div>`;
    }

    // ── EMI Follow Up Modal Logic ──────────────────────────────────────────
    let clientEmiFollowupModalInstance;

    function openClientEmiFollowupModal(bookingId, clientName, totalEmi = 36, leadId = null) {
        document.getElementById('followup_modal_client_name').textContent = clientName || 'Client';
        document.getElementById('modalFollowupBookingId').value = bookingId;
        document.getElementById('modalFollowupNoteInput').value = '';
        
        const bookingSelect = document.getElementById('modalFollowupBookingSelect');
        if (bookingSelect) {
            bookingSelect.innerHTML = '<option value="' + bookingId + '">Loading projects...</option>';
            bookingSelect.disabled = true;
        }

        populateEmiSelect(totalEmi);

        document.getElementById('modalFollowupHistoryContainer').innerHTML = '<div class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading history...</div>';
        
        const modalEl = document.getElementById('clientEmiFollowupModal');
        if (modalEl) {
            clientEmiFollowupModalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            clientEmiFollowupModalInstance.show();
        }
        
        if (leadId) {
            fetch('<?= $GLOBALS['base_url'] ?>/get-client-lands?lead_id=' + leadId)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.lands && data.lands.length > 0) {
                        let opts = '';
                        data.lands.forEach(land => {
                            const selected = (land.booking_id == bookingId) ? 'selected' : '';
                            opts += `<option value="${land.booking_id}" data-emi="${land.total_emi_count || 36}" ${selected}>${escapeHtml(land.project_name)} - ${escapeHtml(land.unit_name)} (Floor ${escapeHtml(land.flat_no)})</option>`;
                        });
                        if (bookingSelect) {
                            bookingSelect.innerHTML = opts;
                            bookingSelect.disabled = false;
                        }
                    } else {
                        if (bookingSelect) {
                            bookingSelect.innerHTML = '<option value="' + bookingId + '">Current Project</option>';
                            bookingSelect.disabled = false;
                        }
                    }
                })
                .catch(err => {
                    if (bookingSelect) {
                        bookingSelect.innerHTML = '<option value="' + bookingId + '">Current Project</option>';
                        bookingSelect.disabled = false;
                    }
                });
        } else {
            if (bookingSelect) {
                bookingSelect.innerHTML = '<option value="' + bookingId + '">Current Project</option>';
                bookingSelect.disabled = false;
            }
        }

        loadClientEmiFollowups(bookingId);
    }

    function onFollowupBookingChange() {
        const bookingSelect = document.getElementById('modalFollowupBookingSelect');
        if (!bookingSelect) return;
        
        const selectedOption = bookingSelect.options[bookingSelect.selectedIndex];
        const bookingId = selectedOption.value;
        const totalEmi = selectedOption.getAttribute('data-emi') || 36;
        
        document.getElementById('modalFollowupBookingId').value = bookingId;
        populateEmiSelect(totalEmi);
        loadClientEmiFollowups(bookingId);
    }

    function populateEmiSelect(totalEmi) {
        const emiSelect = document.getElementById('modalFollowupEmiSelect');
        if (emiSelect) {
            let optionsHtml = '<option value="0">General / Overall Follow Up</option>';
            optionsHtml += '<option value="999">Development Charge</option>';
            const maxEmi = parseInt(totalEmi) || 36;
            for (let i = 1; i <= maxEmi; i++) {
                optionsHtml += `<option value="${i}">EMI #${i}</option>`;
            }
            emiSelect.innerHTML = optionsHtml;
        }
    }

    function loadClientEmiFollowups(bookingId) {
        $.ajax({
            url: '<?= $GLOBALS['base_url'] ?>/account-client/getEmiFollowups',
            type: 'GET',
            data: { booking_id: bookingId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let html = '';
                    if (!response.data || response.data.length === 0) {
                        html = '<div class="text-center text-muted py-4"><i class="fa-light fa-comment-slash d-block fs-3 mb-2"></i>No follow up notes added yet.</div>';
                    } else {
                        response.data.forEach(function(note) {
                            const label = note.purpose_label || (note.emi_no == 999 ? 'Development Charge' : (note.emi_no == 0 ? 'General Follow Up' : 'EMI #' + note.emi_no));
                            const badgeClass = note.purpose_badge_class || (note.emi_no == 999 ? 'bg-warning text-dark' : (note.emi_no == 0 ? 'bg-secondary text-white' : 'bg-primary text-white'));
                            const borderClr = note.emi_no == 999 ? '#f59e0b' : (note.emi_no == 0 ? '#64748b' : '#2563eb');

                            html += `
                            <div class="card mb-2 shadow-sm border-0" style="background:#f8fafc; border-left: 4px solid ${borderClr} !important;">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge ${badgeClass} fs-11 px-2 py-1">
                                            <i class="fa-light fa-tag me-1"></i>Follow Up for: <strong>${escapeHtml(label)}</strong>
                                        </span>
                                        <span class="text-muted fs-11"><i class="fa-light fa-clock me-1"></i>${note.created_at_formatted || note.created_at || ''}</span>
                                    </div>
                                    <div class="mb-2" style="font-size:12px; color:#475569;">
                                        ${note.activity_date ? `<span><i class="fa-light fa-calendar me-1"></i>${escapeHtml(note.activity_date)}</span>` : ''}
                                        ${note.activity_mode ? `<span class="ms-3"><i class="fa-light fa-phone-volume me-1"></i>${escapeHtml(note.activity_mode)}</span>` : ''}
                                        ${note.activity_reason ? `<span class="ms-3"><i class="fa-light fa-bullseye me-1"></i>${escapeHtml(note.activity_reason)}</span>` : ''}
                                    </div>
                                    <p class="mb-2 text-dark fs-13 fw-medium">${escapeHtml(note.note || '')}</p>
                                    <div class="text-muted fs-11 text-end">
                                        <span><i class="fa-light fa-user me-1"></i>By ${escapeHtml(note.author_name || 'System')}</span>
                                    </div>
                                </div>
                            </div>`;
                        });
                    }
                    $('#modalFollowupHistoryContainer').html(html);
                } else {
                    $('#modalFollowupHistoryContainer').html('<div class="text-danger p-3">Failed to load history.</div>');
                }
            },
            error: function() {
                $('#modalFollowupHistoryContainer').html('<div class="text-danger p-3">Error connecting to server.</div>');
            }
        });
    }

    function saveClientEmiFollowup() {
        const bookingId = $('#modalFollowupBookingId').val();
        const emiNo = $('#modalFollowupEmiSelect').val() ?? 0;
        const note = $('#modalFollowupNoteInput').val().trim();
        const activityDate = $('#modalFollowupActivityDate').val();
        const activityMode = $('#modalFollowupActivityMode').val();
        const activityReason = $('#modalFollowupActivityReason').val();
        
        if (!note || !activityDate || !activityMode || !activityReason) {
            Swal.fire({
                icon: 'warning',
                title: 'Fields Required',
                text: 'Please fill in all the required fields (*).',
            });
            return;
        }
        
        $.ajax({
            url: '<?= $GLOBALS['base_url'] ?>/account-client/saveEmiFollowup',
            type: 'POST',
            data: { 
                booking_id: bookingId, 
                emi_no: emiNo, 
                note: note,
                activity_date: activityDate,
                activity_mode: activityMode,
                activity_reason: activityReason
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved!',
                        text: 'Follow up note saved successfully.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    $('#modalFollowupNoteInput').val('');
                    loadClientEmiFollowups(bookingId);
                    
                    // Update count badge in real-time
                    const badges = document.querySelectorAll(`.followup-count-badge[data-booking-id="${bookingId}"]`);
                    badges.forEach(badge => {
                        let currentCount = parseInt(badge.textContent) || 0;
                        badge.textContent = currentCount + 1;
                        badge.className = 'badge bg-primary rounded-pill ms-1 followup-count-badge';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed',
                        text: response.message || "Failed to save note."
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Server Error',
                    text: 'Please try again later.'
                });
            }
        });
    }
</script>


<style>
    .card__icon span {
        color: var(--clr-theme-primary);
        background: rgba(108, 95, 252, 0.1);
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        text-align: center;
        justify-content: center;
        align-items: center;
        font-size: 16px;
    }

    .card__title {
        font-size: 1.25rem;
    }

    @media (max-width: 575px) {
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            font-size: 12px;
            padding: 6px 8px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            font-size: 12px;
            padding: 6px 16px;
        }
    }

    /* Modal styles */
    .modal-lg {
        max-width: 900px;
    }

    .land-item {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 12px;
        transition: all 0.3s;
        background-color: #fff;
    }

    .land-item:hover {
        background-color: #f8f9fa;
        border-color: var(--clr-theme-primary);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .land-status {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }

    .status-active {
        background-color: #d4edda;
        color: #155724;
    }

    .status-completed {
        background-color: #cce5ff;
        color: #004085;
    }

    .status-cancelled {
        background-color: #f8d7da;
        color: #721c24;
    }

    .land-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 8px;
        margin-top: 10px;
    }

    .land-detail-item {
        font-size: 13px;
        color: #666;
    }

    .land-detail-item strong {
        color: #333;
        display: block;
        margin-bottom: 2px;
        font-size: 12px;
    }

    .loading-spinner {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px;
    }

    .loading-spinner i {
        font-size: 36px;
        margin-bottom: 10px;
    }

    /* ─── Profile Modal ─── */
    .profile-card { font-family: inherit; }
    .profile-section { border-bottom: 1px solid #e5e7eb; }
    .profile-section-header {
        background: linear-gradient(135deg, #1e3a5f, #2563eb);
        color: #fff; padding: 10px 20px; font-size: 14px; font-weight: 600;
        letter-spacing: 0.3px;
    }
    .profile-content { padding: 20px 24px; }
    .profile-label { font-size: 11px; text-transform: uppercase; color: #6b7280; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 2px; }
    .profile-value { font-size: 14px; color: #1f2937; font-weight: 500; }
    .profile-img {
        width: 110px; height: 110px; border-radius: 50%; object-fit: cover;
        border: 3px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .profile-img-placeholder {
        width: 110px; height: 110px; border-radius: 50%;
        border: 3px dashed #d1d5db; display: inline-flex;
        align-items: center; justify-content: center; color: #9ca3af;
        background: #f9fafb;
    }

    /* ── Column & Footer Dynamic Highlight ── */
    .active-col-highlight {
        background-color: rgba(255, 243, 205, 0.6) !important;
        font-weight: 800 !important;
    }
    #projectBreakdownFooterRow .col-target {
        transition: all 0.3s ease;
    }
    #projectBreakdownFooterRow .footer-col-highlight {
        background-color: #f59e0b !important;
        color: #ffffff !important;
        font-size: 1.05rem !important;
        font-weight: 900 !important;
        box-shadow: 0 0 10px rgba(245, 158, 11, 0.8);
        border-radius: 4px;
        opacity: 1 !important;
    }
</style>

<!-- ══ PROJECT-WISE COLLECTION BREAKDOWN MODAL ══ -->
<div class="modal fade" id="projectCollectionModal" tabindex="-1" aria-labelledby="projectCollectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fs-6 fw-bold text-white mb-0" id="projectCollectionModalLabel">
                    <i class="fa-solid fa-building-user me-2 text-warning"></i>Project-Wise Collection & EMI Breakdown
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <!-- Search & Info Bar -->
                <div class="row align-items-center mb-3 g-2">
                    <div class="col-md-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" id="projectBreakdownSearch" class="form-control" placeholder="Search project name..." onkeyup="filterProjectBreakdownTable()">
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fs-12 fw-semibold">
                            <i class="fa-solid fa-layer-group me-1"></i> Total Projects: <?= count($projectCollectionBreakdown ?? []) ?>
                        </span>
                    </div>
                </div>

                <!-- Table Card -->
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="projectBreakdownTable">
                            <thead class="bg-white text-muted extra-small text-uppercase border-bottom">
                                <tr>
                                    <th class="ps-3" style="width: 40px;">#</th>
                                    <th>Project Name</th>
                                    <th class="text-center">Bookings</th>
                                    <th class="text-end col-target col-total">Total Collected</th>
                                    <th class="text-end col-target col-land_share">Land Share</th>
                                    <th class="text-end col-target col-dev_charge text-purple">Dev Charge</th>
                                    <th class="text-end col-target col-emi">Total EMI</th>
                                    <th class="text-end text-success col-target col-verified">Verified EMI</th>
                                    <th class="text-end text-warning col-target col-unverified">Unverified EMI</th>
                                    <th class="pe-3 text-center" style="width: 100px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $sumBookings = 0;
                                $sumTotal = 0;
                                $sumLand = 0;
                                $sumDevCharge = 0;
                                $sumEmi = 0;
                                $sumVerified = 0;
                                $sumUnverified = 0;
                                ?>
                                <?php if (empty($projectCollectionBreakdown)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center py-4 text-muted">
                                            <i class="fa-solid fa-building-slash fs-3 d-block mb-2"></i>
                                            No project collection data found.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($projectCollectionBreakdown as $idx => $pData): ?>
                                        <?php 
                                        $sumBookings += (int)$pData['total_bookings'];
                                        $sumTotal += (float)$pData['total_collected'];
                                        $sumLand += (float)$pData['land_share_collected'];
                                        $sumDevCharge += (float)($pData['dev_charge_collected'] ?? 0);
                                        $sumEmi += (float)$pData['total_emi_collected'];
                                        $sumVerified += (float)$pData['verified_emi_collected'];
                                        $sumUnverified += (float)$pData['unverified_emi_collected'];
                                        ?>
                                        <tr class="project-breakdown-row">
                                            <td class="ps-3 fw-bold text-muted extra-small"><?= $idx + 1 ?></td>
                                            <td class="fw-bold text-dark project-name-cell">
                                                <i class="fa-solid fa-building text-primary me-2"></i><?= htmlspecialchars($pData['project_name']) ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1"><?= number_format($pData['total_bookings']) ?></span>
                                            </td>
                                            <td class="text-end fw-bold text-primary col-target col-total">
                                                ৳<?= number_format($pData['total_collected'], 2) ?>
                                            </td>
                                            <td class="text-end fw-semibold text-info col-target col-land_share">
                                                ৳<?= number_format($pData['land_share_collected'], 2) ?>
                                            </td>
                                            <td class="text-end fw-semibold text-purple col-target col-dev_charge">
                                                ৳<?= number_format($pData['dev_charge_collected'] ?? 0, 2) ?>
                                            </td>
                                            <td class="text-end fw-semibold text-dark col-target col-emi">
                                                ৳<?= number_format($pData['total_emi_collected'], 2) ?>
                                            </td>
                                            <td class="text-end fw-bold text-success col-target col-verified">
                                                ৳<?= number_format($pData['verified_emi_collected'], 2) ?>
                                            </td>
                                            <td class="text-end fw-bold text-warning col-target col-unverified">
                                                ৳<?= number_format($pData['unverified_emi_collected'], 2) ?>
                                            </td>
                                            <td class="pe-3 text-center">
                                                <a href="<?= $GLOBALS['base_url'] ?>/clients-cashin-view?project_id=<?= $pData['project_id'] ?>" class="btn btn-xs btn-outline-primary rounded-2 px-2" title="Filter page by this project">
                                                    <i class="fa-solid fa-filter me-1"></i>Filter
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="bg-dark text-white fw-bold" id="projectBreakdownFooterRow">
                                <tr>
                                    <td colspan="2" class="ps-3 text-uppercase">Total Company Collection Summary</td>
                                    <td class="text-center"><?= number_format($sumBookings) ?></td>
                                    <td class="text-end text-primary-light col-target col-total">৳<?= number_format($sumTotal, 2) ?></td>
                                    <td class="text-end text-info-light col-target col-land_share">৳<?= number_format($sumLand, 2) ?></td>
                                    <td class="text-end text-purple-light col-target col-dev_charge">৳<?= number_format($sumDevCharge, 2) ?></td>
                                    <td class="text-end text-white col-target col-emi">৳<?= number_format($sumEmi, 2) ?></td>
                                    <td class="text-end text-success-light col-target col-verified">৳<?= number_format($sumVerified, 2) ?></td>
                                    <td class="text-end text-warning-light col-target col-unverified">৳<?= number_format($sumUnverified, 2) ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function openProjectCollectionModal(highlightType = 'all') {
    const modalEl = document.getElementById('projectCollectionModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }

    // Reset highlights and column opacities
    document.querySelectorAll('#projectBreakdownTable .col-target').forEach(el => {
        el.classList.remove('active-col-highlight', 'footer-col-highlight');
    });

    const footerCells = document.querySelectorAll('#projectBreakdownFooterRow .col-target');

    if (highlightType && highlightType !== 'all') {
        // Dim unselected footer summary cells
        footerCells.forEach(el => {
            el.style.opacity = '0.4';
        });

        // Highlight table body cells of selected column
        document.querySelectorAll('#projectBreakdownTable tbody .col-' + highlightType).forEach(el => {
            el.classList.add('active-col-highlight');
        });

        // Highlight header cell
        document.querySelectorAll('#projectBreakdownTable thead .col-' + highlightType).forEach(el => {
            el.classList.add('active-col-highlight');
        });

        // Highlight selected footer summary cell prominently
        const footerTarget = document.querySelector('#projectBreakdownFooterRow .col-' + highlightType);
        if (footerTarget) {
            footerTarget.classList.add('footer-col-highlight');
            footerTarget.style.opacity = '1';
        }
    } else {
        // Reset all footer cells to full opacity
        footerCells.forEach(el => {
            el.style.opacity = '1';
        });
    }
}

function filterProjectBreakdownTable() {
    const input = document.getElementById('projectBreakdownSearch');
    const filter = input ? input.value.toLowerCase() : '';
    const rows = document.querySelectorAll('.project-breakdown-row');

    rows.forEach(row => {
        const nameCell = row.querySelector('.project-name-cell');
        if (nameCell) {
            const text = nameCell.textContent || nameCell.innerText;
            if (text.toLowerCase().indexOf(filter) > -1) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });
}
</script>

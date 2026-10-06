<?php
$page_title = "Clients Cash in";
$page = 'Clients';
$addPage = true;
$show_breadcrumb = true;
?>

<div class="row">

    <!-- Top 4 Existing Stat Cards -->
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6 mb-3">
        <div class="card__wrapper h-100">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-light fa-users"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Clients</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h4 class="card__title" id="cashInTopTotalClients"><?= number_format($totalClients ?? ($paymentStats['total_clients'] ?? 0)) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6 mb-3">
        <div class="card__wrapper h-100">
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
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6 mb-3">
        <div class="card__wrapper h-100">
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
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6 mb-3">
        <div class="card__wrapper h-100" style="border-left: 4px solid #f59e0b; cursor: pointer; transition: transform 0.2s;" onclick="filterPendingVerification()" title="Click to filter Pending Verification clients">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span style="color:#f59e0b; background:rgba(245,158,11,0.1);"><i class="fa-light fa-clock"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Pending Verification <small class="text-muted">(Click to view)</small></h6>
                    <h3 class="card__title mb-0" style="font-size: 1.5rem; margin-bottom: 0.25rem !important;">
                        <?= $paymentStats['pending_verifications'] ?? 0 ?> <span style="font-size: 1rem; font-weight: normal;">Clients</span>
                    </h3>
                    <div style="font-size: 0.8rem; color: #6c757d; font-weight: 500;">
                        Total Transactions Pending: <?= $paymentStats['total_pending_transactions'] ?? 0 ?>
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
                    <small class="text-muted">Overview of total collections, land shares, dev charges, and EMI verification status</small>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="openProjectCollectionModal()">
                    <i class="fa-solid fa-list-check me-1"></i> View Project-Wise Breakdown
                </button>
            </div>
            <div class="card-body p-3">
                <div class="row ">
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

<style>
    .table-pending-highlight, .table-pending-highlight > td {
        background-color: #fff0f0 !important;
    }
</style>

<div class="col-xxl-12">
    <div class="card__wrapper">
        <!-- Header: Title and Legend Badges -->
        <div class="d-flex justify-content-between align-items-center mb-15 flex-wrap gap-2">
            <div>
                <h4 class="mb-1 d-flex align-items-center flex-wrap gap-2">
                    <span>Clients Information</span>
                    <small class="text-muted fs-13 font-semibold">
                        (<span id="cashInTotalCount"><?= number_format($totalClients ?? 0) ?></span> Records)
                    </small>
                    <span class="badge bg-primary text-white rounded-pill px-2 py-1 ms-1" id="cashInFilterBadge" style="font-size: 11px;">
                        <span id="cashInFilterFoundCount"><?= number_format($totalClients ?? 0) ?></span> Found
                    </span>
                </h4>
                <div class="text-muted fs-12">Manage all booked clients and installment collections</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="fa-light fa-clock me-1"></i> Pending</span>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fa-light fa-check-circle me-1"></i> Verified</span>
            </div>
        </div>

        <!-- Toolbar: Search & Filters (Side-by-Side) -->
        <div class="p-2 px-3 mb-20 rounded-3 bg-light border d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                <!-- Search Box -->
                <div class="position-relative" style="width: 270px; min-width: 200px;">
                    <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="left: 12px; top: 50%; transform: translateY(-50%); font-size: 13px; z-index: 4; pointer-events: none;"></i>
                    <input type="text" id="cashInSearchInput" class="form-control form-control-sm" 
                           style="padding-left: 36px !important; padding-right: 28px; height: 36px; border-radius: 6px;" 
                           placeholder="Search client, phone, email, project..." 
                           value="<?= htmlspecialchars($search ?? '') ?>" autocomplete="off">
                    <button type="button" id="cashInClearSearch" class="btn btn-sm btn-link text-muted position-absolute p-0 <?= empty($search) ? 'd-none' : '' ?>" 
                            style="right: 10px; top: 50%; transform: translateY(-50%); text-decoration: none; z-index: 4;" title="Clear Search">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <!-- Project Filter (Same as flat-lists) -->
                <div style="min-width: 220px; max-width: 280px; flex: 1;">
                    <select class="form-control form-control-sm select2" name="project_id" id="cashInProjectFilter">
                        <option value="">All Projects</option>
                        <?php if(!empty($projects)): foreach($projects as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= ($selectedProjectId == $p['id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>

                <!-- Verification Status Filter -->
                <div style="width: 170px; min-width: 140px;">
                    <select name="verification_status" id="cashInStatusFilter" class="form-select form-select-sm" style="width: 100% !important; height: 36px; border-radius: 6px;">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= ($selectedVerificationStatus == 'pending') ? 'selected' : '' ?>>Pending Verification</option>
                        <option value="verified" <?= ($selectedVerificationStatus == 'verified') ? 'selected' : '' ?>>Verified</option>
                        <option value="rejected" <?= ($selectedVerificationStatus == 'rejected') ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>

                <!-- Date Type Filter -->
                <div style="width: 165px; min-width: 140px;">
                    <select name="date_type" id="cashInDateType" class="form-select form-select-sm" style="width: 100% !important; height: 36px; border-radius: 6px;">
                        <option value="">Filter By Date...</option>
                        <option value="booking_date" <?= (($dateType ?? '') == 'booking_date') ? 'selected' : '' ?>>Booking Date</option>
                        <option value="last_payment_date" <?= (($dateType ?? '') == 'last_payment_date') ? 'selected' : '' ?>>Last Payment Date</option>
                    </select>
                </div>

                <!-- Date Range (From & To) -->
                <div class="d-flex align-items-center gap-1">
                    <div style="width: 135px;">
                        <input type="date" name="start_date" id="cashInStartDate" class="form-control form-control-sm" style="height: 36px; border-radius: 6px;" title="From Date" value="<?= htmlspecialchars($startDate ?? '') ?>">
                    </div>
                    <span class="text-muted small">to</span>
                    <div style="width: 135px;">
                        <input type="date" name="end_date" id="cashInEndDate" class="form-control form-control-sm" style="height: 36px; border-radius: 6px;" title="To Date" value="<?= htmlspecialchars($endDate ?? '') ?>">
                    </div>
                </div>

                <!-- Per Page Limit -->
                <div class="d-flex align-items-center gap-1" style="width: 120px;">
                    <span class="text-muted extra-small text-nowrap">Show:</span>
                    <select id="cashInLimitSelect" class="form-select form-select-sm" style="width: 75px !important; height: 36px; border-radius: 6px;" title="Rows per page">
                        <option value="10" <?= ($limit == 10) ? 'selected' : '' ?>>10</option>
                        <option value="20" <?= ($limit == 20 || empty($limit)) ? 'selected' : '' ?>>20</option>
                        <option value="50" <?= ($limit == 50) ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= ($limit == 100) ? 'selected' : '' ?>>100</option>
                    </select>
                </div>

                <!-- Reset Filters Button -->
                <button type="button" id="cashInResetFilters" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" style="height: 36px; border-radius: 6px; font-size: 12.5px;" title="Reset Search & Filters">
                    <i class="fa-solid fa-rotate-left"></i> Reset
                </button>
            </div>
        </div>

        <?php if (!empty($conflictedUnits)): ?>
            <div class="card mb-3 border-0 shadow-sm" style="background: #fff5f5; border: 1px solid #fecdd3 !important; border-left: 5px solid #dc2626 !important; border-radius: 8px;">
                <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="d-flex align-items-center">
                        <span class="d-inline-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 38px; height: 38px; border-radius: 50%; background: #fee2e2; color: #dc2626; font-size: 18px;">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </span>
                        <div style="color: #991b1b !important; font-size: 13.5px; line-height: 1.4;">
                            <span style="font-weight: 700; color: #7f1d1d !important;">Warning:</span>
                            Found <strong style="color: #991b1b !important;"><?= count($conflictedUnits) ?></strong> unit(s) with multiple active client bookings (Double Booked).
                            <div style="font-size: 12px; color: #b91c1c !important; margin-top: 2px;">
                                Multiple clients are assigned to the exact same flat/shop. Review and cancel or delete duplicate bookings.
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <button type="button" id="btnFilterConflicts" class="btn btn-sm btn-outline-danger fw-semibold <?= !empty($hasConflict) ? 'd-none' : '' ?>" style="font-size: 12.5px; border-radius: 6px; padding: 6px 12px;">
                            <i class="fa-solid fa-filter me-1"></i>Show Conflicted Units Only
                        </button>
                        <button type="button" id="btnResetConflictFilter" class="btn btn-sm btn-secondary fw-semibold <?= empty($hasConflict) ? 'd-none' : '' ?>" style="font-size: 12.5px; border-radius: 6px; padding: 6px 12px;">
                            <i class="fa-solid fa-rotate-left me-1"></i>Show All
                        </button>
                        <button type="button" class="btn btn-sm btn-danger fw-semibold" data-bs-toggle="modal" data-bs-target="#conflictResolutionModal" style="font-size: 12.5px; border-radius: 6px; padding: 6px 12px;">
                            <i class="fa-solid fa-list-check me-1"></i>Resolve Conflicts (<?= count($conflictedUnits) ?>)
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="table__wrapper table-responsive position-relative">
            <!-- Loading Overlay -->
            <div id="cashInTableLoadingOverlay" class="position-absolute top-0 start-0 w-100 h-100 d-none justify-content-center align-items-center" style="background: rgba(255,255,255,0.75); z-index: 10; min-height: 150px; border-radius: 8px;">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="small text-muted mt-2 fw-semibold">Loading clients data...</div>
                </div>
            </div>

            <table class="table mb-0" id="clientsTable">
                <thead>
                    <tr class="table__title table__sort">
                        <th>Client Name</th>
                        <th>Company</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Project & Flats</th>
                        <th>Booking Agent(s) & Date</th>
                        <th>Total Paid</th>
                        <th>Last Payment</th>
                        <th>Verification</th>
                        <th>Profile</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody class="table__body" id="clientsTableBody">
                    <?php
                    require_once __DIR__ . '/../../../controllers/AccountClientController.php';
                    $controllerInstance = new AccountClientController($GLOBALS['db'] ?? null);
                    echo $controllerInstance->renderCashInRowsHtml($clients, $conflictedUnits ?? []);
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Container -->
        <div id="clientsPaginationContainer">
            <?php
            echo $controllerInstance->generateAjaxPagination($currentPage ?? 1, $totalPages ?? 1, $totalClients ?? count($clients), $limit ?? 20, 'loadCashInPage');
            ?>
        </div>
    </div>
</div></div>
    </div>

</div>

<!-- Projects Modal -->
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

<!-- ===== CONFLICT RESOLUTION MODAL ===== -->
<div class="modal fade" id="conflictResolutionModal" tabindex="-1" aria-labelledby="conflictResolutionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header py-3 px-4" style="background: linear-gradient(135deg, #dc2626, #991b1b); color: #fff;">
                <div class="d-flex align-items-center">
                    <span class="d-inline-flex align-items-center justify-content-center me-3" style="width: 38px; height: 38px; border-radius: 50%; background: rgba(255,255,255,0.2); font-size: 18px;">
                        <i class="fa-solid fa-triangle-exclamation text-white"></i>
                    </span>
                    <div>
                        <h5 class="modal-title text-white mb-0" id="conflictResolutionModalLabel" style="font-weight: 700;">Conflicted / Double-Booked Units Management</h5>
                        <p class="mb-0 text-white-50" style="font-size: 12px;">Found <?= !empty($conflictedBookingsDetail) ? count($conflictedBookingsDetail) : (!empty($conflictedUnits) ? count($conflictedUnits) : 0) ?> unit(s) with multiple active bookings. Review payments, client info, and cancel or delete unwanted bookings.</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="background-color: #f8fafc; max-height: 75vh; overflow-y: auto;">
                <?php if (!empty($conflictedBookingsDetail)): ?>
                    <?php $conflictIndex = 0; ?>
                    <?php foreach ($conflictedBookingsDetail as $unitKey => $uBookings): ?>
                        <?php 
                            $conflictIndex++; 
                            $firstB = $uBookings[0] ?? [];
                            $projName = $firstB['project_name'] ?? 'N/A';
                            $fltName = $firstB['flat_name'] ?? 'N/A';
                            $flrNo = $firstB['floor_no'] ?? '';
                        ?>
                        <div class="card mb-4 border-0 shadow-sm" style="border-radius: 10px; overflow: hidden; border: 1px solid #e2e8f0 !important;">
                            <div class="card-header bg-white py-2 px-3 d-flex flex-wrap align-items-center justify-content-between border-bottom" style="border-left: 5px solid #dc2626 !important;">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="badge bg-danger text-white px-2 py-1" style="font-size: 11px;">Conflict #<?= $conflictIndex ?></span>
                                    <span class="badge bg-light text-dark border px-2 py-1 fw-bold" style="font-size: 12.5px;">
                                        <i class="fa-solid fa-building me-1 text-primary"></i><?= htmlspecialchars($projName) ?>
                                    </span>
                                    <strong class="text-dark" style="font-size: 14px;">Unit: <span class="text-danger fw-bold"><?= htmlspecialchars($fltName) ?></span></strong>
                                    <?php if (!empty($flrNo)): ?>
                                        <span class="text-muted small">(Floor: <?= htmlspecialchars($flrNo) ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" style="font-size: 11.5px;">
                                    <i class="fa-solid fa-users me-1"></i><?= count($uBookings) ?> Active Bookings (Conflict)
                                </span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="font-size: 12.5px;">
                                    <thead style="background-color: #f8fafc; color: #475569;">
                                        <tr>
                                            <th class="ps-3" style="width: 100px;">Booking #</th>
                                            <th>Client Name</th>
                                            <th>Phone</th>
                                            <th>Booking Date</th>
                                            <th>Agreement Value</th>
                                            <th>Total Paid</th>
                                            <th>Status</th>
                                            <th class="text-center pe-3" style="width: 220px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($uBookings as $bk): ?>
                                            <?php 
                                                $paidAmt = (float)($bk['total_paid'] ?? 0);
                                                $agrVal = (float)($bk['agreement_value'] ?? 0);
                                                $bDate = !empty($bk['booking_date']) ? date('d M, Y', strtotime($bk['booking_date'])) : 'N/A';
                                                $historyUrl = $GLOBALS['base_url'] . '/clients-cash-in-history/' . $bk['booking_id'];
                                            ?>
                                            <tr id="conflict-booking-row-<?= $bk['booking_id'] ?>">
                                                <td class="ps-3">
                                                    <span class="fw-bold text-dark">#<?= $bk['booking_id'] ?></span>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($bk['client_name']) ?></div>
                                                    <div class="text-muted" style="font-size: 11px;">Agent: <?= htmlspecialchars($bk['agent_name'] ?? 'System') ?></div>
                                                </td>
                                                <td><?= htmlspecialchars($bk['phone'] ?? 'N/A') ?></td>
                                                <td><?= $bDate ?></td>
                                                <td>TK. <?= number_format($agrVal, 2) ?></td>
                                                <td>
                                                    <span class="badge <?= $paidAmt > 0 ? 'bg-success text-white' : 'bg-secondary text-white' ?> px-2 py-1" style="font-size: 11.5px;">
                                                        TK. <?= number_format($paidAmt, 2) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                                        <?= ucfirst(htmlspecialchars($bk['booking_status'] ?? 'Active')) ?>
                                                    </span>
                                                </td>
                                                <td class="text-center pe-3">
                                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                                        <a href="<?= $historyUrl ?>" target="_blank" class="btn btn-xs btn-outline-primary" style="padding: 4px 8px; font-size: 11px;" title="View Client Ledger">
                                                            <i class="fa-regular fa-eye me-1"></i>Ledger
                                                        </a>
                                                        <button type="button" class="btn btn-xs btn-outline-warning" style="padding: 4px 8px; font-size: 11px;"
                                                            onclick="cancelBookingAction(<?= $bk['booking_id'] ?>, '<?= htmlspecialchars($bk['client_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($projName . ' - ' . $fltName, ENT_QUOTES) ?>')"
                                                            title="Cancel this booking">
                                                            <i class="fa-solid fa-ban me-1"></i>Cancel
                                                        </button>
                                                        <?php if (($_SESSION['user']['role_id'] ?? 0) === 3): ?>
                                                            <button type="button" class="btn btn-xs btn-outline-danger" style="padding: 4px 8px; font-size: 11px;"
                                                                onclick="deleteBookingAction(<?= $bk['booking_id'] ?>, '<?= htmlspecialchars($bk['client_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($projName . ' - ' . $fltName, ENT_QUOTES) ?>', <?= $paidAmt ?>)"
                                                                title="Permanently delete this booking">
                                                                <i class="fa-solid fa-trash-can me-1"></i>Delete
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fa-solid fa-circle-check text-success fs-1 mb-3"></i>
                        <h5>No Conflicted Units Found!</h5>
                        <p class="text-muted">All active bookings have unique units.</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer bg-white py-2 px-4 border-top">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    let projectsModal;

    document.addEventListener('DOMContentLoaded', function () {
        projectsModal = new bootstrap.Modal(document.getElementById('projectsModal'));
    });

    function filterPendingVerification() {
        const form = document.getElementById('filterForm');
        const select = form ? form.querySelector('select[name="verification_status"]') : null;
        if (select) {
            select.value = (select.value === 'pending') ? '' : 'pending';
            form.submit();
        }
    }

    let currentLeadId = null;
    let currentClientName = '';

    function openProjectsModal(leadId, clientName) {
        currentLeadId = leadId;
        currentClientName = clientName;
        document.getElementById('modal_client_name').textContent = clientName;
        document.getElementById('projects_loading').style.display = 'block';
        document.getElementById('projects_content').style.display = 'none';
        document.getElementById('projects_error').style.display = 'none';
        projectsModal.show();

        const url = '<?= $GLOBALS['base_url'] ?>/get-client-lands?lead_id=' + leadId;
        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (data.success && data.lands && data.lands.length > 0) {
                    displayProjects(data.lands);
                } else {
                    showError(data.message || 'No land shares found for this client');
                }
            })
            .catch(err => showError('Failed to load: ' + err.message));
    }

    function displayProjects(lands) {
        const tbody = document.getElementById('projects_table_body');
        tbody.innerHTML = '';

        lands.forEach(land => {
            const row = document.createElement('tr');
            const bookingStatusClass = getStatusClass(land.booking_status);
            const landshareStatusClass = getStatusClass(land.landshare_status);
            const transferBadge = parseInt(land.transfer_status) === 1 
                ? '<span class="badge bg-warning text-dark ms-1" style="font-size:0.7em;">Transferred</span>' 
                : '<span class="badge bg-success ms-1" style="font-size:0.7em;">Current</span>';

            
            const isManagement = <?= in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 6, 7, 30, 33, 35]) ? 'true' : 'false' ?>;
            let actionHtml = `
                <div class="d-flex align-items-center gap-1 justify-content-center">
                    <a class="table__icon download"
                        href="<?= $GLOBALS['base_url'] ?>/clients-cash-in-history/${land.booking_id}"
                        title="View Transaction History">
                        <i class="fa-regular fa-eye"></i>
                    </a>`;
                
            if (isManagement) {
                actionHtml += `
                    <button type="button" class="table__icon" style="color: #eab308; border: 1px solid #eab308; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 4px;"
                        onclick="cancelBookingFromModal(${land.booking_id}, '${escapeHtml(land.unit_name || '')}')"
                        title="Cancel Booking">
                        <i class="fa-solid fa-ban"></i>
                    </button>`;
                
                <?php if (($_SESSION['user']['role_id'] ?? 0) === 3): ?>
                actionHtml += `
                    <button type="button" class="table__icon delete" style="color: #dc3545; border: 1px solid #dc3545; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 4px;"
                        onclick="deleteBooking(${land.booking_id})"
                        title="Delete Booking & All Payments">
                        <i class="fa-regular fa-trash-can"></i>
                    </button>`;
                <?php endif; ?>
            }
            
            actionHtml += `</div>`;

            row.innerHTML = `
            <td>${escapeHtml(land.project_name)} ${transferBadge}</td>
            <td>${escapeHtml(land.unit_name)} - Floor ${escapeHtml(land.flat_no)}</td>
            <td>${escapeHtml(land.booking_agent_name || 'System')}</td>
            <td>${Number(land.flat_size).toLocaleString('en-BD')} sqft</td>
            <td>TK.${Number(land.total_price).toLocaleString('en-BD', { minimumFractionDigits: 2 })}</td>
            <td>TK.${Number(land.agreement_value).toLocaleString('en-BD', { minimumFractionDigits: 2 })}</td>
            <td>TK.${Number(land.total_paid || 0).toLocaleString('en-BD', { minimumFractionDigits: 2 })}</td>
            <td>
                <div><strong>Fee:</strong> TK.${Number(land.total_service_fee || 0).toLocaleString('en-BD', { minimumFractionDigits: 2 })}</div>
                <div class="text-muted small"><strong>Balance:</strong> TK.${Number(land.service_fee_balance || 0).toLocaleString('en-BD', { minimumFractionDigits: 2 })}</div>
            </td>
            <td><span class="bd-badge ${bookingStatusClass}">${escapeHtml(land.booking_status || 'N/A')}</span></td>
            <td><span class="bd-badge ${landshareStatusClass}">${escapeHtml(land.landshare_status || 'N/A')}</span></td>
            <td>${land.last_payment_date ? formatDate(land.last_payment_date) : 'No Payment'}</td>
            <td style="white-space: nowrap;">${actionHtml}</td>`;
            tbody.appendChild(row);
        });

        document.getElementById('projects_loading').style.display = 'none';
        document.getElementById('projects_content').style.display = 'block';
    }

    function cancelBookingFromModal(bookingId, unitName) {
        const modalElement = document.getElementById('projectsModal');
        Swal.fire({
            title: 'Cancel Booking #' + bookingId + '?',
            html: `<p>Are you sure you want to cancel the booking for unit <b>${escapeHtml(unitName)}</b>?</p>
                   <input id="swalCancelReasonModal" class="swal2-input" placeholder="Enter reason (optional)" value="Cancelled double booking conflict">`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#eab308',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fa-solid fa-ban me-1"></i>Yes, Cancel',
            cancelButtonText: 'Abort',
            target: modalElement,
            preConfirm: () => {
                return document.getElementById('swalCancelReasonModal').value;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('<?= $GLOBALS['base_url'] ?>/cancel-booking', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ booking_id: bookingId, reason: result.value })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Cancelled!',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false,
                            target: modalElement
                        });
                        if (currentLeadId) {
                            openProjectsModal(currentLeadId, currentClientName);
                        } else {
                            window.location.reload();
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to cancel booking.',
                            target: modalElement
                        });
                    }
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'A network error occurred.',
                        target: modalElement
                    });
                });
            }
        });
    }

    function deleteBooking(bookingId) {
        // Find the modal element to set it as target to prevent z-index issues
        const modalElement = document.getElementById('projectsModal');
        
        Swal.fire({
            title: 'Are you sure?',
            html: '<p class="text-danger">This will permanently delete the booking and ALL associated payments.</p><p>The flat will also be marked as available if no other active booking exists. This action cannot be undone!</p>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            target: modalElement // This renders Swal inside the modal, fixing the z-index issue!
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('<?= $GLOBALS['base_url'] ?>/delete-booking', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ booking_id: bookingId })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        // Refresh the modal content
                        if (currentLeadId) {
                            openProjectsModal(currentLeadId, currentClientName);
                        } else {
                            window.location.reload();
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to delete booking.'
                        });
                    }
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'A network error occurred.'
                    });
                });
            }
        });
    }

    function showError(message) {
        document.getElementById('error_message').textContent = message;
        document.getElementById('projects_loading').style.display = 'none';
        document.getElementById('projects_error').style.display = 'block';
    }

    function getStatusClass(status) {
        if (!status) return 'bg-secondary';
        status = status.toLowerCase();
        if (['booked','active','approved','paid'].includes(status)) return 'bg-success';
        if (status === 'pending') return 'bg-warning';
        if (['cancelled','rejected'].includes(status)) return 'bg-danger';
        return 'bg-info';
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
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
</script>

<style>
    .card__icon span {
        color: var(--clr-theme-primary);
        background: rgba(108, 95, 252, 0.1);
        width: 40px; height: 40px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center; font-size: 16px;
    }
    .card__title { font-size: 1.25rem; }
    /* Amber highlight for provisional / pending rows */
    tr.table-warning td { background-color: rgba(245, 158, 11, 0.08) !important; }
    tr.table-danger  td { background-color: rgba(220, 53, 69, 0.06) !important; }
    @media (max-width: 575px) {
        .dataTables_wrapper .dataTables_paginate .paginate_button { font-size: 12px; padding: 6px 8px; }
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

    /* ── Property Chips ── */
    .property-chip {
        display: inline-flex;
        align-items: stretch;
        background: #ffffff;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        overflow: hidden;
        font-size: 11.5px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        transition: all 0.2s ease;
    }
    .property-chip:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.08);
        border-color: #9ca3af;
    }
    .property-chip .project-name {
        padding: 4px 8px;
        background: #eff6ff;
        color: #1d4ed8;
        font-weight: 600;
        display: flex;
        align-items: center;
        white-space: nowrap;
    }
    .property-chip .flat-name {
        padding: 4px 8px;
        background: #ffffff;
        color: #374151;
        font-weight: 700;
        border-left: 1px dashed #d1d5db;
        display: flex;
        align-items: center;
        white-space: nowrap;
    }
    .view-more-btn:not(.collapsed) .show-more-text {
        display: none !important;
    }
    .view-more-btn:not(.collapsed) .show-less-text {
        display: inline !important;
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
                                                <a href="<?= $GLOBALS['base_url'] ?>/clients-cash-in?project_id=<?= $pData['project_id'] ?>" class="btn btn-xs btn-outline-primary rounded-2 px-2" title="Filter page by this project">
                                                    <i class="fa-solid fa-filter me-1"></i>Filter
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="fw-bold" id="projectBreakdownFooterRow">
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

// ── AJAX Pagination, Filter & Live Search Logic for Clients Cash In (Pure Vanilla JS) ──
let cashInCurrentPage = <?= (int)($currentPage ?? 1) ?>;
let cashInSearchTimer = null;
let cashInConflictOnly = <?= !empty($hasConflict) ? 'true' : 'false' ?>;

function loadCashInPage(page) {
    page = parseInt(page) || 1;
    if (page < 1) page = 1;
    cashInCurrentPage = page;

    const overlay = document.getElementById('cashInTableLoadingOverlay');
    const tbody = document.getElementById('clientsTableBody');
    const pagContainer = document.getElementById('clientsPaginationContainer');
    const searchVal = document.getElementById('cashInSearchInput') ? document.getElementById('cashInSearchInput').value.trim() : '';
    const projectVal = document.getElementById('cashInProjectFilter') ? document.getElementById('cashInProjectFilter').value : '';
    const statusVal = document.getElementById('cashInStatusFilter') ? document.getElementById('cashInStatusFilter').value : '';
    const limitVal = document.getElementById('cashInLimitSelect') ? document.getElementById('cashInLimitSelect').value : '20';
    const dateTypeVal = document.getElementById('cashInDateType') ? document.getElementById('cashInDateType').value : '';
    const startDateVal = document.getElementById('cashInStartDate') ? document.getElementById('cashInStartDate').value : '';
    const endDateVal = document.getElementById('cashInEndDate') ? document.getElementById('cashInEndDate').value : '';

    if (overlay) {
        overlay.classList.remove('d-none');
        overlay.classList.add('d-flex');
    }
    if (tbody) tbody.style.opacity = '0.4';

    const params = new URLSearchParams({
        ajax: '1',
        page: page,
        limit: limitVal,
        search: searchVal,
        project_id: projectVal,
        verification_status: statusVal,
        date_type: dateTypeVal,
        start_date: startDateVal,
        end_date: endDateVal
    });
    if (cashInConflictOnly) {
        params.append('has_conflict', '1');
    }

    // Update browser URL without reloading to preserve state for Back button
    const urlParams = new URLSearchParams();
    if (page > 1) urlParams.set('page', page);
    if (limitVal && limitVal !== '20') urlParams.set('limit', limitVal);
    if (searchVal) urlParams.set('search', searchVal);
    if (projectVal) urlParams.set('project_id', projectVal);
    if (statusVal) urlParams.set('verification_status', statusVal);
    if (dateTypeVal) urlParams.set('date_type', dateTypeVal);
    if (startDateVal) urlParams.set('start_date', startDateVal);
    if (endDateVal) urlParams.set('end_date', endDateVal);
    if (cashInConflictOnly) urlParams.set('has_conflict', '1');

    const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
    window.history.replaceState({path: newUrl}, '', newUrl);

    fetch('<?= $GLOBALS['base_url'] ?>/clients-cash-in?' + params.toString(), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data && data.success) {
            if (tbody && data.table_html) {
                tbody.innerHTML = data.table_html;
            }
            if (pagContainer && data.pagination_html) {
                pagContainer.innerHTML = data.pagination_html;
            }
            const totalCount = (data.total_clients !== undefined) ? data.total_clients : data.totalRecords;
            if (totalCount !== undefined) {
                const formattedCount = Number(totalCount).toLocaleString();
                const totalCountEl = document.getElementById('cashInTotalCount');
                if (totalCountEl) totalCountEl.textContent = formattedCount;
                const topCountEl = document.getElementById('cashInTopTotalClients');
                if (topCountEl) topCountEl.textContent = formattedCount;
                const filterBadgeCount = document.getElementById('cashInFilterFoundCount');
                if (filterBadgeCount) filterBadgeCount.textContent = formattedCount;
            }
        }
    })
    .catch(err => {
        console.error('Failed to load clients:', err);
    })
    .finally(() => {
        if (overlay) {
            overlay.classList.add('d-none');
            overlay.classList.remove('d-flex');
        }
        if (tbody) tbody.style.opacity = '1';
    });

    // Smooth scroll to table
    const tableEl = document.getElementById('clientsTable');
    if (tableEl) {
        const topPos = tableEl.getBoundingClientRect().top + window.pageYOffset - 120;
        window.scrollTo({ top: topPos, behavior: 'smooth' });
    }
}

// Expose globally so onclick can always find them
window.loadCashInPage = loadCashInPage;
window.loadCedClientsPage = loadCashInPage;

// Event Listeners for AJAX Controls (Vanilla JS)
function initCashInAjaxControls() {
    // 1. Search input with debounce
    const searchInput = document.getElementById('cashInSearchInput');
    const clearBtn = document.getElementById('cashInClearSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const val = this.value.trim();
            if (clearBtn) {
                if (val.length > 0) clearBtn.classList.remove('d-none');
                else clearBtn.classList.add('d-none');
            }
            clearTimeout(cashInSearchTimer);
            cashInSearchTimer = setTimeout(() => {
                loadCashInPage(1);
            }, 300);
        });
    }

    // Debounce for project & filter dropdown changes
    let cashInFilterChangeTimer = null;
    function triggerCashInFilterChange() {
        clearTimeout(cashInFilterChangeTimer);
        cashInFilterChangeTimer = setTimeout(() => {
            loadCashInPage(1);
        }, 50);
    }

    // 2. Clear search button
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            if (searchInput) searchInput.value = '';
            this.classList.add('d-none');
            loadCashInPage(1);
        });
    }

    // 3. Status, Date & Limit dropdowns / inputs
    ['cashInStatusFilter', 'cashInLimitSelect', 'cashInDateType', 'cashInStartDate', 'cashInEndDate'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', function() {
                loadCashInPage(1);
            });
        }
    });

    // Listen on Project Filter change (fires when Select2 or native select changes)
    $(document).on('change', '#cashInProjectFilter', function() {
        triggerCashInFilterChange();
    });

    // 4. Conflict Filter Buttons
    const btnConflicts = document.getElementById('btnFilterConflicts');
    const btnResetConflicts = document.getElementById('btnResetConflictFilter');
    if (btnConflicts) {
        btnConflicts.addEventListener('click', function() {
            cashInConflictOnly = true;
            this.classList.add('d-none');
            if (btnResetConflicts) btnResetConflicts.classList.remove('d-none');
            loadCashInPage(1);
        });
    }

    if (btnResetConflicts) {
        btnResetConflicts.addEventListener('click', function() {
            cashInConflictOnly = false;
            this.classList.add('d-none');
            if (btnResetConflicts) btnResetConflicts.classList.add('d-none');
            if (btnConflicts) btnConflicts.classList.remove('d-none');
            loadCashInPage(1);
        });
    }

    // 5. Reset Filters Button
    const resetBtn = document.getElementById('cashInResetFilters');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            if (searchInput) searchInput.value = '';
            if (clearBtn) clearBtn.classList.add('d-none');
            $('#cashInProjectFilter').val('').trigger('change');
            const statusEl = document.getElementById('cashInStatusFilter');
            if (statusEl) statusEl.value = '';
            const dateTypeEl = document.getElementById('cashInDateType');
            if (dateTypeEl) dateTypeEl.value = '';
            const startDateEl = document.getElementById('cashInStartDate');
            if (startDateEl) startDateEl.value = '';
            const endDateEl = document.getElementById('cashInEndDate');
            if (endDateEl) endDateEl.value = '';
            const limitEl = document.getElementById('cashInLimitSelect');
            if (limitEl) limitEl.value = '20';
            cashInConflictOnly = false;
            if (btnResetConflicts) btnResetConflicts.classList.add('d-none');
            if (btnConflicts) btnConflicts.classList.remove('d-none');
            loadCashInPage(1);
        });
    }

    // 6. Delegated click on pagination container (in addition to onclick)
    document.addEventListener('click', function(e) {
        const link = e.target.closest('#clientsPaginationContainer .ajax-page-link');
        if (link && link.hasAttribute('data-page')) {
            e.preventDefault();
            const targetPage = parseInt(link.getAttribute('data-page'));
            if (targetPage > 0) {
                loadCashInPage(targetPage);
            }
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCashInAjaxControls);
} else {
    initCashInAjaxControls();
}

function cancelBookingAction(bookingId, clientName, unitName) {
    Swal.fire({
        title: 'Cancel Booking #' + bookingId + '?',
        html: `<p>Are you sure you want to cancel the booking for <b>${escapeHtml(clientName)}</b> (${escapeHtml(unitName)})?</p>
               <input id="swalCancelReasonAction" class="swal2-input" placeholder="Enter reason (optional)" value="Cancelled double booking conflict">`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#eab308',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fa-solid fa-ban me-1"></i>Yes, Cancel Booking',
        cancelButtonText: 'Abort',
        preConfirm: () => {
            return document.getElementById('swalCancelReasonAction').value;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Cancelling...',
                text: 'Please wait while booking is being cancelled',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            fetch('<?= $GLOBALS['base_url'] ?>/cancel-booking', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ booking_id: bookingId, reason: result.value })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Cancelled!',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message || 'Failed to cancel booking', 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error', 'Network or server error: ' + err.message, 'error');
            });
        }
    });
}

function deleteBookingAction(bookingId, clientName, unitName, totalPaid) {
    let warningHtml = `<p>Permanently delete booking #<b>${bookingId}</b> for <b>${escapeHtml(clientName)}</b> (${escapeHtml(unitName)})?</p>`;
    if (totalPaid > 0) {
        warningHtml += `<p class="text-danger fw-bold" style="font-size:13px;"><i class="fa-solid fa-triangle-exclamation me-1"></i>Warning: This booking has <b>TK. ${Number(totalPaid).toLocaleString('en-BD', {minimumFractionDigits: 2})}</b> in payments which will also be permanently deleted!</p>`;
    } else {
        warningHtml += `<p class="text-muted" style="font-size:12.5px;">This booking has TK. 0 payments. The flat will be freed if no other active booking exists.</p>`;
    }

    Swal.fire({
        title: 'Delete Booking & Payments?',
        html: warningHtml,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fa-solid fa-trash-can me-1"></i>Yes, Delete Permanently',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            fetch('<?= $GLOBALS['base_url'] ?>/delete-booking', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ booking_id: bookingId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message || 'Failed to delete booking', 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error', 'Network or server error: ' + err.message, 'error');
            });
        }
    });
}
</script>


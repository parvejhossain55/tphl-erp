<?php
$page = "Lead & Booking Management";
$show_breadcrumb = true;
?>
<!-- Stats Grid -->
<div class="row  mb-4 align-items-stretch">
    <div class="col-12 col-md-6 col-lg-4 col-xl-2">
        <div class="card__wrapper stat-card">
            <div class="stat-label">Total Leads</div>
            <div class="stat-value"><?= $leadCounts ?></div>
            <div class="stat-change positive d-none">
                <span class="<?= $leadsPercentChange >= 0 ? 'price-increase' : 'price-decrease' ?>">
                    <i class="fa-light <?= $leadsPercentChange >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' ?>"></i>
                    <?= abs($leadsPercentChange) ?>%
                </span>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-4 col-xl-2">
        <div class="card__wrapper stat-card">
            <div class="stat-label">Total Booked</div>
            <div class="stat-value"><?= $totalBookingCount ?></div>
            <div class="stat-change positive d-none">
                <i class="bi bi-arrow-up"></i> 3.1%
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-4 col-xl-2">
        <div class="card__wrapper stat-card">
            <div class="stat-label">Today Follow-Up</div>
            <div class="stat-value"><?= $totalTodayFollowUps ?></div>
            <div class="stat-change positive d-none">
                <i class="bi bi-arrow-up"></i> 1.2%
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-4 col-xl-2">
        <div class="card__wrapper stat-card">
            <div class="stat-label">Pending Follow-Ups</div>
            <div class="stat-value"><?= $totalTodayPendingFollowUps ?></div>
            <div class="stat-change negative d-none">
                <i class="bi bi-arrow-down"></i> 12.5%
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-4 col-xl-2">
        <div class="card__wrapper stat-card">
            <div class="stat-label">Conversion Rate</div>
            <div class="stat-value"><?= format_bdt($conversionRate ?? 0) ?>%</div>
            <div class="stat-change positive">
                <i class="bi bi-arrow-up"></i> 8.4%
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-4 col-xl-2">
        <div class="card__wrapper stat-card">
            <div class="stat-label">Hot Leads</div>
            <div class="stat-value"><?= $totalHotLeads ?></div>
            <div class="stat-change positive d-none">
                <i class="bi bi-arrow-up"></i> 2.5%
            </div>
        </div>
    </div>


    <!-- Desktop View: Filters and Table -->
    <div class="desktop-only">



        <!-- Filters -->
        <div class="filters">
            <form action="" method="get">
                <div class="row g-2 align-items-center flex-wrap">

                    <!-- Year Select -->
                    <div class="col-auto col-sm-6 col-md-auto">
                        <?php
                        $currentYear = date('Y');
                        $startYear   = $currentYear - 10;
                        $selectedYear = $slectedYear ?? '';
                        ?>
                        <select class="form-control" name="year">
                            <option value="">Year Select</option>
                            <?php for ($year = $currentYear; $year >= $startYear; $year--): ?>
                                <option value="<?= $year ?>" <?= $selectedYear == $year ? 'selected' : '' ?>>
                                    <?= $year ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <!-- Month Select -->
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
                    <div class="col-auto col-sm-6 col-md-auto">
                        <select class="form-control" name="month">
                            <option value="">Month Select</option>
                            <?php foreach ($months as $id => $name): ?>
                                <option value="<?= $id ?>" <?= $selectedMonth == $id ? 'selected' : '' ?>>
                                    <?= $name ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Hidden Project Select (optional) -->
                    <div class="col-auto col-sm-6 col-md-auto d-none">
                        <select class="form-select form-select-sm filter-select">
                            <option>Project: All Projects</option>
                            <option>Green Valley</option>
                            <option>Oceanview Residences</option>
                        </select>
                    </div>

                    <!-- Last Action Select -->
                    <div class="col-auto col-sm-6 col-md-auto">
                        <select class="form-select form-select-sm filter-select" name="last_action">
                            <option value="">Status: All</option>
                            <option value="new" <?= ($selectedAction ?? '') == 'new' ? 'selected' : '' ?>>New</option>
                            <option value="booked" <?= ($selectedAction ?? '') == 'booked' ? 'selected' : '' ?>>Booked</option>
                            <option value="follow up" <?= ($selectedAction ?? '') == 'follow up' ? 'selected' : '' ?>>Follow Up</option>
                            <option value="visited" <?= ($selectedAction ?? '') == 'visited' ? 'selected' : '' ?>>Visited</option>
                            <option value="cancel" <?= ($selectedAction ?? '') == 'cancel' ? 'selected' : '' ?>>Cancel</option>
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-auto ms-auto d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-outline-secondary btn-sm flex-grow-1 py-1">
                            Search
                        </button>
                        <a href="<?= $GLOBALS['base_url'] ?>/lead-lists" class="btn btn-primary btn-sm">
                            Reset
                        </a>

                        <!-- Import CSV button outside form submit -->
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="importCsvBtn" data-bs-toggle="modal" data-bs-target="#importCsvModal">
                            Import CSV
                        </button>

                        <a href="<?= $GLOBALS['base_url'] ?>/create-lead" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg"></i> Add Lead
                        </a>
                    </div>

                </div>
            </form>
        </div>

        <div class="col-md-6">
            <div id="assignCard"
                class="card__wrapper shadow-sm p-3 my-2"
                style="display:none; border-radius:12px;">

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <label for="assignUserSelect" class="fw-bold mb-0" style="white-space: nowrap;">
                        Assign Selected Leads to:
                    </label>

                    <select id="assignUserSelect"
                        class="form-select form-select-sm"
                        style="min-width:200px; width:auto;">
                        <option value="">Assigned User</option>
                        <?php foreach ($assignedUsers as $user): ?>
                            <option value="<?= $user['id'] ?>">
                                <?= htmlspecialchars($user['full_name']) ?>-<?= htmlspecialchars($user['role_name']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>

                    <button id="assignUserBtn" class="btn btn-primary btn-sm px-3">
                        <i class="fa-solid fa-user-plus me-1"></i> Assign Leads
                    </button>

                    <span id="assignFeedback"
                        class="text-success fw-bold"
                        style="display:none;">
                        Leads assigned successfully!
                    </span>
                </div>

            </div>
        </div>


        <!-- Table -->
        <div class="col-xxl-12">
            <div class="card__wrapper">
                <div class="table__wrapper table-responsive">
                    <table class="table no-border-table mb-20 multiple_tables">
                        <thead>
                            <tr class="table__title table__sort">
                                <th style="min-width:60px;">
                                    <input type="checkbox" id="selectAllLeads" title="Select All">
                                    S/L
                                </th><!-- non-sticky first column -->
                                <th>Lead Date</th>
                                <th>Lead Name</th>
                                <th>Phone</th>
                                <th>Project Campaign</th>
                                <th>Status</th>
                                <th>Next Follow-Up</th>
                                <th>Sales Executive</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leads as $key => $lead): ?>
                                <tr data-id="<?= $lead['id'] ?>">
                                    <td>
                                        <input type="checkbox" class="me-2 selectedId" data-id="<?= $lead['id'] ?>">
                                        <?= $key + 1 ?>
                                    </td>
                                    <td><?= $lead['created_at'] ? date('d F, Y', strtotime($lead['created_at'])) : '' ?></td>
                                    <td><strong><?= $lead['full_name'] ?? '' ?></strong></td>
                                    <td><?= $lead['phone_number'] ?? $lead['phone']  ?>
                                    </td>
                                    <?php
                                    $displayName = !empty($lead['campaign_name'])
                                        ? $lead['campaign_name']
                                        : (!empty($lead['project_name']) ? $lead['project_name'] : '-');

                                    $plainText = strip_tags($displayName);
                                    ?>


                                    <td>
                                        <div class="d-flex align-items-center gap-2 flex-nowrap">
                                            <span class="project-badge mb-0">
                                                <i class="bi bi-building"></i>
                                                <?= truncateText($displayName, 15) ?>
                                            </span>

                                            <?php if (mb_strlen($plainText) > 15): ?>
                                                <button type="button"
                                                    id="viewCampaignBtn<?= $lead['id'] ?>"
                                                    class="btn btn-sm btn-outline-primary flex-shrink-0"
                                                    data-description="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#viewProjectCampaignName">
                                                    View



                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <!-- last_action_form  -->
                                    <!-- New Last Action Update Button -->
                                    <td>

                                        <form method="POST"
                                            action="<?= $GLOBALS['base_url'] ?>/update-last_leadAction/<?= $lead['id'] ?>"
                                            class="m-0 p-0">

                                            <select name="last_lead_action"
                                                class="form-select form-select-sm last-action-select <?= 'action-' . ($lead['last_lead_action'] ?? 'not_called') ?>"
                                                onchange="this.form.submit()">

                                                <option value=""> Update action </option>
                                                <option value="new" <?= $lead['last_lead_action'] == 'new' ? 'selected' : '' ?>>
                                                    New
                                                </option>

                                                <option value="booked" <?= $lead['last_lead_action'] == 'booked' ? 'selected' : '' ?>>
                                                    Booked
                                                </option>

                                                <option value="follow up"
                                                    <?= $lead['last_lead_action'] == 'follow up' ? 'selected' : '' ?>
                                                    class="<?= $lead['last_lead_action'] == 'follow up' ? 'action-follow_up' : '' ?>">
                                                    Follow Up
                                                </option>


                                                <option value="visited" <?= $lead['last_lead_action'] == 'visited' ? 'selected' : '' ?>>
                                                    Visited
                                                </option>
                                                <option value="cancel" <?= $lead['last_lead_action'] == 'cancel' ? 'selected' : '' ?>>
                                                    Cancel
                                                </option>

                                            </select>

                                        </form>
                                    </td>
                                    <!-- last_action_form  -->
                                    <td>
                                        <div><?= formatDate($lead['next_follow_up_date'] ?? 'No Schedule') ?></div>
                                    </td>

                                    <td><?= $lead['agent_name'] ?? 'No Agent Assigned' ?></td>

                                    <td>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">

                                            <!-- Follow Up -->
                                            <a href="<?= $GLOBALS['base_url'] ?>/follow-up-leads/<?= $lead['id'] ?>"
                                                class="btn btn-primary">

                                                <span>Follow Up</span>

                                                <?php if (!empty($followUpCounts[$lead['id']])): ?>
                                                    <span class="action-badge">
                                                        <?= $followUpCounts[$lead['id']] ?>
                                                    </span>
                                                <?php endif; ?>
                                            </a>

                                            <!-- Booking -->
                                            <a href="<?= $GLOBALS['base_url'] ?>/booking-leads/<?= $lead['id'] ?>"
                                                class="btn btn-secondary">

                                                <span>
                                                    <?= !empty($bookingCount[$lead['id']]) ? 'Booked' : 'Booking' ?>
                                                </span>

                                                <?php if (!empty($bookingCount[$lead['id']])): ?>
                                                    <span class="action-badge">
                                                        <?= $bookingCount[$lead['id']] ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="action-badge">0</span>
                                                <?php endif; ?>
                                            </a>

                                            <!-- 3 Dot Dropdown -->
                                            <div class="dropdown">
                                                <button class="action-dot-btn" type="button" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>

                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                                    <li>
                                                        <a class="dropdown__item" href="<?= $GLOBALS['base_url'] ?>/edit-lead/<?= $lead['id'] ?>">
                                                            <i class="bi bi-pencil-square me-2 text-primary"></i> Edit
                                                        </a>
                                                    </li>

                                                    <li>
                                                        <a class="dropdown__item" href="<?= $GLOBALS['base_url'] ?>/view-lead/<?= $lead['id'] ?>">
                                                            <i class="bi bi-eye me-2 text-success"></i> View
                                                        </a>
                                                    </li>

                                                    <li>
                                                        <buton class="dropdown__item text-danger removeBtnCust" data-name="<?= $lead['full_name'] ?>"
                                                            data-action="lead-delete"
                                                            data-id="<?= $lead['id'] ?>">
                                                            <i class="bi bi-trash me-2"></i> Delete
                                                        </buton>
                                                    </li>
                                                </ul>
                                            </div>

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

    <!-- Mobile View: Filter Button and Cards -->
    <div class="mobile-only">
        <!-- Filter & Sort Button -->

        <!-- Filter Toggle Button -->
        <button class="filter-button w-100 mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#mobileFilterCollapse" aria-expanded="false" aria-controls="mobileFilterCollapse">
            <i class="bi bi-funnel"></i> Filter & Sort
        </button>

        <!-- Collapsible Filter Section -->
        <div class="collapse" id="mobileFilterCollapse">
            <form action="" method="get" class="p-2 border rounded ">
                <div class="d-flex flex-column gap-2">

                    <!-- Year Select -->
                    <?php
                    $currentYear = date('Y');
                    $startYear   = $currentYear - 10;
                    $selectedYear = $slectedYear ?? '';
                    ?>
                    <select class="form-control form-select-sm" name="year">
                        <option value="">Year Select</option>
                        <?php for ($year = $currentYear; $year >= $startYear; $year--): ?>
                            <option value="<?= $year ?>" <?= $selectedYear == $year ? 'selected' : '' ?>><?= $year ?></option>
                        <?php endfor; ?>
                    </select>

                    <!-- Month Select -->
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
                    <select class="form-control form-select-sm" name="month">
                        <option value="">Month Select</option>
                        <?php foreach ($months as $id => $name): ?>
                            <option value="<?= $id ?>" <?= $selectedMonth == $id ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Status Select -->
                    <select class="form-select form-select-sm" name="last_action">
                        <option value="">Status: All</option>
                        <option value="new" <?= ($selectedAction ?? '') == 'new' ? 'selected' : '' ?>>New</option>
                        <option value="booked" <?= ($selectedAction ?? '') == 'booked' ? 'selected' : '' ?>>Booked</option>
                        <option value="follow up" <?= ($selectedAction ?? '') == 'follow up' ? 'selected' : '' ?>>Follow Up</option>
                        <option value="visited" <?= ($selectedAction ?? '') == 'visited' ? 'selected' : '' ?>>Visited</option>
                        <option value="cancel" <?= ($selectedAction ?? '') == 'cancel' ? 'selected' : '' ?>>Cancel</option>
                    </select>

                    <!-- Action Buttons -->
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <button type="submit" class="btn btn-outline-secondary btn-sm flex-grow-1">Search</button>
                        <a href="<?= $GLOBALS['base_url'] ?>/lead-lists" class="btn btn-primary btn-sm flex-grow-1">Reset</a>
                        <button type="button" class="btn btn-outline-secondary btn-sm flex-grow-1" data-bs-toggle="modal" data-bs-target="#importCsvModal">Import CSV</button>


                        <a href="<?= $GLOBALS['base_url'] ?>/create-lead" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-plus-lg"></i> Add Lead</a>

                    </div>

                </div>
            </form>
        </div>

        <!-- Mobile Assign Card (sticky bottom) -->
        <div class="mobile-only" id="assignCardMobile" style="display:none; position:sticky; bottom:0; z-index:1000; background:#fff; padding:10px; border-top:1px solid #ccc; border-radius:8px;">
            <div class="d-flex align-items-center gap-2 flex-wrap justify-content-between">
                <select id="assignUserSelectMobile" class="form-select form-select-sm" style="min-width:150px; flex:1;">
                    <option value="">Select User</option>
                    <?php foreach ($assignedUsers as $user): ?>
                        <option value="<?= $user['id'] ?>">
                            <?= htmlspecialchars($user['full_name']) ?> - <?= htmlspecialchars($user['role_name']) ?>
                        </option>
                    <?php endforeach ?>
                </select>

                <button id="assignUserBtnMobile" class="btn btn-primary btn-sm px-3">
                    <i class="fa-solid fa-user-plus me-1"></i> Assign Leads
                </button>

                <span id="assignFeedbackMobile" class="text-success fw-bold" style="display:none;">Leads assigned!</span>
            </div>
        </div>

        <?php foreach ($leads as $key => $lead): ?>
            <?php
            $displayName = !empty($lead['campaign_name'])
                ? $lead['campaign_name']
                : (!empty($lead['project_name']) ? $lead['project_name'] : '-');
            $plainText = strip_tags($displayName);
            ?>
            <!-- Lead Card -->
            <div class="lead-card" data-id="<?= $lead['id'] ?>">
                <div class="lead-card-header d-flex justify-content-between align-items-center">
                    <div>
                        <input type="checkbox" class="selectedIdMobile me-2" data-id="<?= $lead['id'] ?>">
                        <span class="lead-name"><?= $lead['full_name'] ?></span>
                        <div class="lead-phone"><?= $lead['phone'] ?></div>
                    </div>

                    <!-- 3 Dot Dropdown as Action Button -->
                    <div class="dropdown">
                        <button class="action-dot-btn" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                            <li>
                                <a class="dropdown__item" href="<?= $GLOBALS['base_url'] ?>/edit-lead/<?= $lead['id'] ?>">
                                    <i class="bi bi-pencil-square me-2 text-primary"></i> Edit
                                </a>
                            </li>
                            <li>
                                <a class="dropdown__item" href="<?= $GLOBALS['base_url'] ?>/view-lead/<?= $lead['id'] ?>">
                                    <i class="bi bi-eye me-2 text-success"></i> View
                                </a>
                            </li>
                            <li>
                                <buton class="dropdown__item text-danger removeBtnCust" data-name="<?= $lead['full_name'] ?>"
                                    data-action="lead-delete"
                                    data-id="<?= $lead['id'] ?>">
                                    <i class="bi bi-trash me-2"></i> Delete
                                </buton>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Status -->
                <div class="info-label mt-2">Status</div>
                <div class="lead-info-row">
                    <form method="POST" action="<?= $GLOBALS['base_url'] ?>/update-last_leadAction/<?= $lead['id'] ?>" class="m-0 p-0">
                        <select name="last_lead_action"
                            class="form-select form-select-sm last-action-select <?= 'action-' . ($lead['last_lead_action'] ?? 'not_called') ?>"
                            onchange="this.form.submit()">
                            <option value="">Update action</option>
                            <option value="new" <?= $lead['last_lead_action'] == 'new' ? 'selected' : '' ?>>New</option>
                            <option value="booked" <?= $lead['last_lead_action'] == 'booked' ? 'selected' : '' ?>>Booked</option>
                            <option value="follow up" <?= $lead['last_lead_action'] == 'follow up' ? 'selected' : '' ?>>Follow Up</option>
                            <option value="visited" <?= $lead['last_lead_action'] == 'visited' ? 'selected' : '' ?>>Visited</option>
                            <option value="cancel" <?= $lead['last_lead_action'] == 'cancel' ? 'selected' : '' ?>>Cancel</option>
                        </select>
                    </form>

                    <!-- Follow Up & Booking Buttons -->
                    <div class="d-flex align-items-center gap-2 flex-wrap ms-2">
                        <a href="<?= $GLOBALS['base_url'] ?>/follow-up-leads/<?= $lead['id'] ?>" class="btn btn-primary d-flex align-items-center gap-1">
                            <span>Follow Up</span>
                            <?php if (!empty($followUpCounts[$lead['id']])): ?>
                                <span class="action-badge"><?= $followUpCounts[$lead['id']] ?></span>
                            <?php endif; ?>
                        </a>

                        <a href="<?= $GLOBALS['base_url'] ?>/booking-leads/<?= $lead['id'] ?>" class="btn btn-secondary d-flex align-items-center gap-1">
                            <span><?= !empty($bookingCount[$lead['id']]) ? 'Booked' : 'Booking' ?></span>
                            <?php if (!empty($bookingCount[$lead['id']])): ?>
                                <span class="action-badge"><?= $bookingCount[$lead['id']] ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>

                <!-- Project Info -->
                <div class="project-detail mt-2">
                    <div class="info-label flex-shrink-0">Project</div>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <span class="project-badge mb-0">
                            <i class="bi bi-building"></i>
                            <?= truncateText($displayName, 15) ?>
                        </span>
                        <?php if (mb_strlen($plainText) > 15): ?>
                            <button type="button"
                                id="viewCampaignBtn<?= $lead['id'] ?>"
                                class="btn btn-sm btn-outline-primary ms-3 w-auto px-1 py-0"
                                data-description="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>"
                                data-bs-toggle="modal"
                                data-bs-target="#viewProjectCampaignName"
                                title="View Project">View
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Next Follow-Up -->
                <div class="d-flex align-items-center gap-2 mt-2">
                    <div class="info-label flex-shrink-0">Next Follow-Up</div>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1" style="font-size: 0.75rem;">
                        <i class="bi bi-calendar2-check me-1"></i>
                        <?= formatDate($lead['next_follow_up_date'] ?? 'No Schedule') ?>
                    </span>
                </div>

                <!-- Agent Name -->
                <div class="d-flex align-items-center gap-2 mt-2">
                    <div class="info-label flex-shrink-0">Executive Name</div>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1" style="font-size: 0.75rem;">
                        <i class="bi bi-person me-1"></i>
                        <?= $lead['agent_name'] ?? 'No Agent Assigned' ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- FAB Button -->
        <button class="fab-button d-none">
            <i class="bi bi-plus-lg"></i>
        </button>
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


<!-- view full rule description -->
<div id="viewProjectCampaignName" class="modal custom-modal fade">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Project Campaing Name</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>

            <div class="modal-body">
                <textarea id="view_campaign_id" class="form-control" rows="2" readonly></textarea>

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


    // campaign full show
</script>
<!-- assigned user select  -->
<script>
    document.getElementById('assignUserBtn').addEventListener('click', function() {
        const userId = document.getElementById('assignUserSelect').value;
        const selectedLeads = [];

        document.querySelectorAll('.selectedId:checked').forEach(cb => {
            const tr = cb.closest('tr');
            if (tr) selectedLeads.push(tr.dataset.id || tr.querySelector('.selectedId').dataset.id);
        });

        // If nothing selected, just return
        if (!userId || selectedLeads.length === 0) {
            return; // no alert
        }

        // Create a form dynamically and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= $base_url ?>/assign-multiple-leads';

        const inputUser = document.createElement('input');
        inputUser.type = 'hidden';
        inputUser.name = 'user_id';
        inputUser.value = userId;
        form.appendChild(inputUser);

        const inputLeads = document.createElement('input');
        inputLeads.type = 'hidden';
        inputLeads.name = 'lead_ids';
        inputLeads.value = selectedLeads.join(',');
        form.appendChild(inputLeads);

        document.body.appendChild(form);
        form.submit();
    });
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {

    const assignCardMobile = document.getElementById('assignCardMobile');
    const assignBtnMobile = document.getElementById('assignUserBtnMobile');
    const userSelectMobile = document.getElementById('assignUserSelectMobile');

    // Toggle mobile assign card visibility
    function toggleMobileAssignCard() {
        const anyChecked = Array.from(document.querySelectorAll('.selectedIdMobile')).some(cb => cb.checked);
        assignCardMobile.style.display = anyChecked ? 'flex' : 'none';
    }

    // Listen for checkbox changes
    document.querySelectorAll('.selectedIdMobile').forEach(cb => {
        cb.addEventListener('change', toggleMobileAssignCard);
    });

    // Assign Leads button click
    assignBtnMobile.addEventListener('click', function() {
        const selectedLeads = Array.from(document.querySelectorAll('.selectedIdMobile:checked'))
                                  .map(cb => cb.dataset.id);

        const userId = userSelectMobile.value;

        if (!userId || selectedLeads.length === 0) {
            alert('Select leads and a user to assign.');
            return;
        }

        // Create form dynamically to submit POST
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= $base_url ?>/assign-multiple-leads';

        const inputUser = document.createElement('input');
        inputUser.type = 'hidden';
        inputUser.name = 'user_id';
        inputUser.value = userId;
        form.appendChild(inputUser);

        const inputLeads = document.createElement('input');
        inputLeads.type = 'hidden';
        inputLeads.name = 'lead_ids';
        inputLeads.value = selectedLeads.join(',');
        form.appendChild(inputLeads);

        document.body.appendChild(form);
        form.submit();

        // Optional: reset checkboxes & hide assign card
        document.querySelectorAll('.selectedIdMobile').forEach(cb => cb.checked = false);
        toggleMobileAssignCard();
    });
});
</script>


<script>
    const selectAll = document.getElementById('selectAllLeads');
    const assignCard = document.getElementById('assignCard');

    selectAll.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.selectedId');
        checkboxes.forEach(cb => cb.checked = this.checked);

        // Show the card if any checkbox is checked
        if (this.checked) {
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

<script>
    document.querySelectorAll('[id^="viewCampaignBtn"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            //From data-description  full text 
            const description = this.getAttribute('data-description');
            // textarea  set 
            document.getElementById('view_campaign_id').value = description;
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Prevent page reload when Import CSV clicked
        const importBtn = document.getElementById('importCsvBtn');
        importBtn.addEventListener('click', function(e) {
            e.preventDefault(); // ensures no form submit
            // Bootstrap modal opens automatically via data-bs-toggle="modal"
        });
    });
</script>
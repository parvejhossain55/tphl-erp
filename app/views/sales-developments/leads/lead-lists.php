<?php
$page = "Lead & Booking Management";
$show_breadcrumb = true;
?>
<!-- Stats Grid -->
<div class="row  mb-4 align-items-stretch">
    <div class="col-6 col-md-6 col-lg-4 col-xl-2">
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
    <div class="col-6 col-md-6 col-lg-4 col-xl-2">
        <div class="card__wrapper stat-card">
            <div class="stat-label">Total Booked</div>
            <div class="stat-value"><?= $totalBookingCount ?></div>
            <div class="stat-change positive d-none">
                <i class="bi bi-arrow-up"></i> 3.1%
            </div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-lg-4 col-xl-2">
        <div class="card__wrapper stat-card">
            <div class="stat-label">Today Follow-Up</div>
            <div class="stat-value"><?= $totalTodayFollowUps ?></div>
            <div class="stat-change positive d-none">
                <i class="bi bi-arrow-up"></i> 1.2%
            </div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-lg-4 col-xl-2">
        <div class="card__wrapper stat-card">
            <div class="stat-label">Pending Follow-Ups</div>
            <div class="stat-value"><?= $totalTodayPendingFollowUps ?></div>
            <div class="stat-change negative d-none">
                <i class="bi bi-arrow-down"></i> 12.5%
            </div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-lg-4 col-xl-2">
        <div class="card__wrapper stat-card">
            <div class="stat-label">Conversion Rate</div>
            <div class="stat-value"><?= format_bdt($conversionRate ?? 0) ?>%</div>
            <div class="stat-change positive">
                <i class="bi bi-arrow-up"></i> 8.4%
            </div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-lg-4 col-xl-2">
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
                    <div class="col-auto col-sm-6 col-md-auto ">
                        <select class="form-select form-select-sm filter-select select2" name="assigned_user">
                            <option value="">Select Sales Executive</option>
                            <?php foreach ($assignedUsers as $user): ?>
                                <option value="<?= $user['id'] ?>" <?= ($selectedAssignedUser ?? '') == $user['id'] ? 'selected' : '' ?>>
                                    <?= $user['full_name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto col-sm-6 col-md-auto">
                        <input type="date" name="date" class="form-control form-control-sm"
                            value="<?= $_GET['date'] ?? '' ?>">
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
                    <!-- Assigned Filter -->
                    <div class="col-auto col-sm-6 col-md-auto">
                        <select class="form-select form-select-sm filter-select" name="assigned">
                            <option value="">Assigned: All</option>
                            <option value="assigned" <?= ($selectedAssigned ?? '') == 'assigned'   ? 'selected' : '' ?>>
                                Assigned
                            </option>
                            <option value="unassigned" <?= ($selectedAssigned ?? '') == 'unassigned' ? 'selected' : '' ?>>
                                Not Assigned
                            </option>
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
                        <button type="button" class="d-none btn btn-outline-secondary btn-sm" id="importCsvBtn" data-bs-toggle="modal" data-bs-target="#importCsvModal">
                            Import CSV
                        </button>

                        <a href="<?= $GLOBALS['base_url'] ?>/create-lead" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg"></i> Add Lead
                        </a>
                    </div>

                </div>
            </form>
        </div>
        <?php if (isset($_SESSION['user']['role_id']) && $_SESSION['user']['role_id'] == 5): ?>
            <div class="col-md-6">
                <div id="assignCard"
                    class="card__wrapper shadow-sm p-3 my-2"
                    style="display:none; border-radius:12px;">

                    <div class="d-flex align-items-center gap-2 flex-wrap">

                        <label class="fw-bold mb-0">Assign Selected Leads to: <span id="selectedCount" class="badge bg-primary ms-2">0</span></label>

                        <select id="assignUserSelect"
                            class="form-select form-select-sm select2"
                            style="min-width:200px; width:100%;">

                            <option value=""></option> <!-- important for placeholder -->

                            <?php foreach ($assignedUsers as $user): ?>
                                <option value="<?= $user['id'] ?>">
                                    <?= htmlspecialchars($user['full_name']) ?> - <?= htmlspecialchars($user['role_name']) ?>
                                </option>
                            <?php endforeach ?>
                        </select>

                        <!--  NEW DATE INPUT -->
                        <input type="date"
                            id="assignedDate"
                            class="form-control form-control-sm"
                            style="width:auto;"
                            value="<?= date('Y-m-d') ?>">

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
        <?php endif; ?>


        <p>Lead Lists</p>

        <!-- Table -->
        <div class="col-xxl-12">
            <div class="card__wrapper">
                <div class="table__wrapper table-responsive">
                    <table id="serverSideLeadTable" class="table no-border-table mb-20">
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
                                <th>Sales Executive & Note</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded via AJAX DataTables -->
                        </tbody>

                        </tbody>
                    </table>
                </div>
            </div>

            <div id="viewNoteModal" class="modal custom-modal fade">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Call Note</h5>
                            <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                                <i class="fa-solid fa-xmark-large"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <textarea id="view_note_text" class="form-control" rows="4" readonly></textarea>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Mobile View: Filter Button and Cards -->
    <div class="mobile-only">
        <!-- Filter & Sort Button -->

        <!-- Mobile Filter Button -->
        <button class="filter-button w-100 mb-2 btn btn-outline-primary"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mobileFilterCollapse"
            aria-expanded="false"
            aria-controls="mobileFilterCollapse">
            <i class="bi bi-funnel"></i> Filter & Sort
        </button>

        <!-- Mobile Filter -->
        <div class="collapse" id="mobileFilterCollapse">

            <form action="" method="get" class="p-3 border rounded bg-white shadow-sm">

                <div class="d-flex flex-column gap-2">

                    <!-- Year -->
                    <?php
                    $currentYear = date('Y');
                    $startYear = $currentYear - 10;
                    $selectedYear = $_GET['year'] ?? '';
                    ?>
                    <select class="form-select form-select-sm" name="year">
                        <option value="">Select Year</option>
                        <?php for ($y = $currentYear; $y >= $startYear; $y--): ?>
                            <option value="<?= $y ?>" <?= $selectedYear == $y ? 'selected' : '' ?>>
                                <?= $y ?>
                            </option>
                        <?php endfor; ?>
                    </select>

                    <!-- Month -->
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
                    <select class="form-select form-select-sm" name="month">
                        <option value="">Select Month</option>
                        <?php foreach ($months as $id => $name): ?>
                            <option value="<?= $id ?>" <?= $selectedMonth == $id ? 'selected' : '' ?>>
                                <?= $name ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Date -->
                    <input type="date"
                        name="date"
                        class="form-control form-control-sm"
                        value="<?= $_GET['date'] ?? '' ?>">

                    <!-- Assigned User -->
                    <select class="form-select form-select-sm select2" name="assigned_user">
                        <option value="">Select Sales Executive</option>
                        <?php foreach ($assignedUsers as $user): ?>
                            <option value="<?= $user['id'] ?>"
                                <?= ($_GET['assigned_user'] ?? '') == $user['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Status -->
                    <select class="form-select form-select-sm" name="last_action">
                        <option value="">Status: All</option>
                        <option value="new" <?= ($_GET['last_action'] ?? '') == 'new' ? 'selected' : '' ?>>New</option>
                        <option value="booked" <?= ($_GET['last_action'] ?? '') == 'booked' ? 'selected' : '' ?>>Booked</option>
                        <option value="follow up" <?= ($_GET['last_action'] ?? '') == 'follow up' ? 'selected' : '' ?>>Follow Up</option>
                        <option value="visited" <?= ($_GET['last_action'] ?? '') == 'visited' ? 'selected' : '' ?>>Visited</option>
                        <option value="cancel" <?= ($_GET['last_action'] ?? '') == 'cancel' ? 'selected' : '' ?>>Cancel</option>
                    </select>

                    <!-- Assigned / Unassigned -->
                    <select class="form-select form-select-sm" name="assigned">
                        <option value="">Assigned: All</option>
                        <option value="assigned" <?= ($_GET['assigned'] ?? '') == 'assigned' ? 'selected' : '' ?>>
                            Assigned
                        </option>
                        <option value="unassigned" <?= ($_GET['assigned'] ?? '') == 'unassigned' ? 'selected' : '' ?>>
                            Not Assigned
                        </option>
                    </select>

                    <!-- Buttons -->
                    <div class="d-flex flex-column gap-2 mt-2">

                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            Search
                        </button>

                        <a href="<?= $GLOBALS['base_url'] ?>/lead-lists"
                            class="btn btn-outline-secondary btn-sm w-100">
                            Reset
                        </a>

                        <a href="<?= $GLOBALS['base_url'] ?>/create-lead"
                            class="btn btn-success btn-sm w-100">
                            <i class="bi bi-plus-lg"></i> Add Lead
                        </a>

                    </div>

                </div>
            </form>
        </div>

        <!-- Mobile Assign Card (sticky bottom) -->
        <div class="mobile-only mb-2" id="assignCardMobile"
            style="display:none; position:sticky; bottom:0; z-index:1000; background:#fff; padding:10px; border-top:1px solid #ccc;">
            <div class="text-center small mb-1 fw-bold text-primary">Selected: <span id="selectedCountMobile">0</span> leads</div>
            <div class="d-flex flex-column gap-2">

                <!-- User Select -->
                <select id="assignUserSelectMobile"
                    class="form-select form-select-sm select2">
                    <option value="">Select User</option>
                    <?php foreach ($assignedUsers as $user): ?>
                        <option value="<?= $user['id'] ?>">
                            <?= htmlspecialchars($user['full_name']) ?> - <?= htmlspecialchars($user['role_name']) ?>
                        </option>
                    <?php endforeach ?>
                </select>

                <!--  Assigned Date -->
                <input type="date"
                    id="assignedDateMobile"
                    class="form-control form-control-sm"
                    value="<?= date('Y-m-d') ?>">

                <!-- Button -->
                <button id="assignUserBtnMobile"
                    class="btn btn-primary btn-sm w-100">
                    <i class="fa-solid fa-user-plus me-1"></i> Assign Leads
                </button>

                <span id="assignFeedbackMobile"
                    class="text-success fw-bold text-center"
                    style="display:none;">
                    Leads assigned!
                </span>

            </div>
        </div>

        <div class="row g-3" id="mobileLeadCards">
            <?php foreach ($leads as $key => $lead): ?>
                <?php
                $displayName = !empty($lead['campaign_name'])
                    ? $lead['campaign_name']
                    : (!empty($lead['project_name']) ? $lead['project_name'] : '-');
                $plainText = strip_tags($displayName);
                ?>
                <div class="col-12 col-sm-6">
                    <!-- Lead Card -->
                    <div class="lead-card h-100" data-id="<?= $lead['id'] ?>">
                        <div class="lead-card-header d-flex justify-content-between align-items-start gap-1">
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="d-flex align-items-center gap-1">
                                    <input type="checkbox" class="selectedIdMobile me-1" data-id="<?= $lead['id'] ?>">
                                    <span class="lead-name text-truncate"><?= $lead['full_name'] ?></span>
                                </div>
                                <div class="lead-phone text-truncate small"><?= $lead['phone'] ?></div>
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

                        <!-- Status & Actions -->
                        <div class="mt-2">
                            <div class="info-label">Status & Action</div>
                            <form method="POST" action="<?= $GLOBALS['base_url'] ?>/update-last_leadAction/<?= $lead['id'] ?>" class="m-0 p-0 mb-2">
                                <select name="last_lead_action"
                                    class="form-select form-select-sm last-action-select w-100 <?= 'action-' . ($lead['last_lead_action'] ?? 'not_called') ?>"
                                    onchange="this.form.submit()">
                                    <option value="">Update action</option>
                                    <option value="new" <?= $lead['last_lead_action'] == 'new' ? 'selected' : '' ?>>New</option>
                                    <option value="booked" <?= $lead['last_lead_action'] == 'booked' ? 'selected' : '' ?>>Booked</option>
                                    <option value="follow up" <?= $lead['last_lead_action'] == 'follow up' ? 'selected' : '' ?>>Follow Up</option>
                                    <option value="visited" <?= $lead['last_lead_action'] == 'visited' ? 'selected' : '' ?>>Visited</option>
                                    <option value="cancel" <?= $lead['last_lead_action'] == 'cancel' ? 'selected' : '' ?>>Cancel</option>
                                </select>
                            </form>

                            <div class="d-flex flex-column gap-2">
                                <a href="<?= $GLOBALS['base_url'] ?>/follow-up-leads/<?= $lead['id'] ?>"
                                    class="btn btn-primary btn-sm w-100 d-flex align-items-center gap-2 px-2">
                                    <span>Follow Up</span>

                                    <?php if (!empty($followUpCounts[$lead['id']])): ?>
                                        <span class="badge bg-white text-dark rounded-circle  justify-content-center"
                                            style="font-size: 10px; width: 18px; height: 18px;">
                                            <?= $followUpCounts[$lead['id']] ?>
                                        </span>
                                    <?php endif; ?>
                                </a>

                                <a href="<?= $GLOBALS['base_url'] ?>/booking-leads/<?= $lead['id'] ?>"
                                    class="btn btn-secondary btn-sm w-100 d-flex align-items-center gap-2 px-2">
                                    <span>Booking</span>

                                    <?php if (!empty($bookingCount[$lead['id']])): ?>
                                        <span class="badge bg-white text-dark rounded-circle  justify-content-center"
                                            style="font-size: 10px; width: 18px; height: 18px;">
                                            <?= $bookingCount[$lead['id']] ?>
                                        </span>
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
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Mobile Load More -->
        <div class="text-center py-3" id="mobileLoadMoreWrapper">
            <div class="small text-muted mb-2" id="mobileLeadCount">Showing <?= count($leads) ?> of <?= $leadCounts ?> leads</div>
            <?php if (count($leads) < $leadCounts): ?>
            <button id="mobileLoadMoreBtn" class="btn btn-outline-success px-4">
                <i class="bi bi-arrow-down-circle me-1"></i> Load More
            </button>
            <?php endif; ?>
            <div id="mobileLoader" class="py-2" style="display:none;">
                <div class="spinner-border spinner-border-sm text-success"></div>
                <span class="small text-muted ms-2">Loading...</span>
            </div>
        </div>

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

    document.querySelectorAll('.note-view-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('view_note_text').value = this.getAttribute('data-note');
        });
    });

    // Disable right-click
    // document.addEventListener('contextmenu', function(e) {
    //     e.preventDefault();
    // });

    // // Disable Ctrl+C, Ctrl+X, Ctrl+V
    // document.addEventListener('keydown', function(e) {
    //     if ((e.ctrlKey && e.key === 'c') ||
    //         (e.ctrlKey && e.key === 'x') ||
    //         (e.ctrlKey && e.key === 'v')) {
    //         e.preventDefault();
    //     }
    // });

    // // Disable drag
    // document.addEventListener('dragstart', function(e) {
    //     e.preventDefault();
    // });

    // Reset button (null check added)
    const form = document.querySelector('form');
    if (form) {
        const resetBtn = form.querySelector('button[type="reset"]');
        if (resetBtn) {
            resetBtn.addEventListener('click', function(e) {
                e.preventDefault();
                form.querySelectorAll('input, select').forEach(function(el) {
                    if (el.tagName === 'SELECT') el.selectedIndex = 0;
                    else el.value = '';
                });
            });
        }
    }

    // campaign full show
</script>

<!-- Lead Selection, Assignment & DataTables — unified block -->
<script>
// Global so mobile Load More cards can also trigger these
let selectedLeadsSet = new Set();
let updateAssignUIData;

document.addEventListener('DOMContentLoaded', function () {

    const assignCard       = document.getElementById('assignCard');
    const assignCardMobile = document.getElementById('assignCardMobile');
    const selectAll        = document.getElementById('selectAllLeads');

    // ── Update assign card visibility ─────────────────────────────────
    updateAssignUIData = function () {
        const count = selectedLeadsSet.size;
        const any   = count > 0;
        if (assignCard)       assignCard.style.display       = any ? 'flex'  : 'none';
        if (assignCardMobile) assignCardMobile.style.display = any ? 'block' : 'none';

        const countEl       = document.getElementById('selectedCount');
        const countMobileEl = document.getElementById('selectedCountMobile');
        if (countEl)       countEl.innerText       = count;
        if (countMobileEl) countMobileEl.innerText = count;
    };

    // ── Sync checkboxes with Set (called after DataTables page change) ─
    function syncCheckboxes() {
        document.querySelectorAll('.selectedId').forEach(cb => {
            cb.checked = selectedLeadsSet.has(cb.dataset.id);
        });
        document.querySelectorAll('.selectedIdMobile').forEach(cb => {
            cb.checked = selectedLeadsSet.has(cb.dataset.id);
        });
        // Update header checkbox state
        const deskCbs = Array.from(document.querySelectorAll('.selectedId'));
        if (deskCbs.length > 0 && selectAll) {
            selectAll.checked = deskCbs.every(cb => cb.checked);
        }
    }

    // ── Submit assignment form ─────────────────────────────────────────
    function submitAssignForm(userId, selectedLeads, assignedDate) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= $base_url ?>/assign-multiple-leads';
        const addHidden = (name, val) => {
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = name; inp.value = val;
            form.appendChild(inp);
        };
        addHidden('user_id',       userId);
        addHidden('lead_ids',      selectedLeads.join(','));
        addHidden('assigned_date', assignedDate);
        document.body.appendChild(form);
        form.submit();
    }

    // ── Desktop Assign button ─────────────────────────────────────────
    const assignBtn = document.getElementById('assignUserBtn');
    if (assignBtn) {
        assignBtn.addEventListener('click', function () {
            const userId       = document.getElementById('assignUserSelect').value;
            const assignedDate = document.getElementById('assignedDate').value;
            const leads        = Array.from(selectedLeadsSet);
            if (!userId || leads.length === 0) { alert('Select leads and a user.'); return; }
            submitAssignForm(userId, leads, assignedDate);
        });
    }

    // ── Mobile Assign button ──────────────────────────────────────────
    const assignBtnMobile = document.getElementById('assignUserBtnMobile');
    if (assignBtnMobile) {
        assignBtnMobile.addEventListener('click', function () {
            const userId       = document.getElementById('assignUserSelectMobile').value;
            const assignedDate = document.getElementById('assignedDateMobile').value;
            const leads        = Array.from(selectedLeadsSet);
            if (!userId || leads.length === 0) { alert('Select leads and a user.'); return; }
            submitAssignForm(userId, leads, assignedDate);
        });
    }

    // ── Header "Select All" checkbox (current DataTables page) ────────
    if (selectAll) {
        selectAll.addEventListener('click',  e => e.stopPropagation());
        selectAll.addEventListener('change', function () {
            const isChecked = this.checked;
            document.querySelectorAll('#serverSideLeadTable tbody .selectedId').forEach(cb => {
                cb.checked = isChecked;
                const id   = cb.dataset.id;
                isChecked ? selectedLeadsSet.add(id) : selectedLeadsSet.delete(id);
                const mob = document.querySelector(`.selectedIdMobile[data-id="${id}"]`);
                if (mob) mob.checked = isChecked;
            });
            updateAssignUIData();
        });
    }

    // ── Individual checkbox change (event delegation — works for AJAX rows too) ──
    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('selectedId') || e.target.classList.contains('selectedIdMobile')) {
            const id = e.target.dataset.id;
            e.target.checked ? selectedLeadsSet.add(id) : selectedLeadsSet.delete(id);
            // Mirror to the sibling view
            const mirror = e.target.classList.contains('selectedId')
                ? document.querySelector(`.selectedIdMobile[data-id="${id}"]`)
                : document.querySelector(`.selectedId[data-id="${id}"]`);
            if (mirror) mirror.checked = e.target.checked;
            syncCheckboxes();
            updateAssignUIData();
        }
    });

    // Prevent table sort when clicking checkboxes
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('selectedId') || e.target.classList.contains('selectedIdMobile')) {
            e.stopPropagation();
        }
    });

    // ── importCsvBtn null-safe ────────────────────────────────────────
    const importBtn = document.getElementById('importCsvBtn');
    if (importBtn) {
        importBtn.addEventListener('click', e => e.preventDefault());
    }

    // ── DataTables (server-side) init ─────────────────────────────────
    if ($('#serverSideLeadTable').length > 0) {
        const urlParams = new URLSearchParams(window.location.search);
        const ajaxUrl   = base_url + '/leads-ajax-data?' + urlParams.toString();

        const dtTable = $('#serverSideLeadTable').DataTable({
            serverSide : true,
            processing : true,
            ajax       : { url: ajaxUrl, type: 'POST' },
            lengthMenu : [[10, 25, 50, 100], [10, 25, 50, 100]],
            order      : [],
            columnDefs : [
                { targets: 0, orderable: false },
                { targets: 8, orderable: false }
            ],
            drawCallback: function () {
                // After each page draw: re-sync checkbox states
                syncCheckboxes();

                // Re-bind note view buttons
                $('.note-view-btn').off('click').on('click', function () {
                    const noteEl = document.getElementById('view_note_text');
                    if (noteEl) noteEl.value = this.getAttribute('data-note');
                });
                // Re-bind campaign view buttons
                $('.viewCampaignBtnDynamic').off('click').on('click', function () {
                    const camEl = document.getElementById('view_campaign_id');
                    if (camEl) camEl.value = this.getAttribute('data-description');
                });
            }
        });
    }

}); // end DOMContentLoaded
</script>




<script>
// ── Mobile Lead Cards: Load More via AJAX ───────────────────────────
(function () {
    const cardsContainer = document.getElementById('mobileLeadCards');
    const loadMoreBtn    = document.getElementById('mobileLoadMoreBtn');
    const loader         = document.getElementById('mobileLoader');
    const countEl        = document.getElementById('mobileLeadCount');

    if (!cardsContainer || !loadMoreBtn) return;

    const PAGE_SIZE = 20;
    let currentStart = <?= count($leads) ?>;  // PHP already rendered these
    let totalRecords = <?= (int)$leadCounts ?>;
    let isLoading    = false;

    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('mobile', '1');
    const ajaxUrl = base_url + '/leads-ajax-data?' + urlParams.toString();

    const actionColors = {
        'new':       'bg-info-subtle text-info',
        'booked':    'bg-success-subtle text-success',
        'follow up': 'bg-warning-subtle text-warning',
        'visited':   'bg-primary-subtle text-primary',
        'cancel':    'bg-danger-subtle text-danger',
    };

    function buildCard(lead) {
        const ac    = actionColors[lead.last_lead_action] || 'bg-secondary-subtle text-secondary';
        const label = lead.last_lead_action
            ? lead.last_lead_action.charAt(0).toUpperCase() + lead.last_lead_action.slice(1)
            : 'No Action';
        const nextFU = lead.next_follow_up
            ? new Date(lead.next_follow_up).toLocaleDateString('en-GB', {day:'2-digit',month:'short',year:'numeric'})
            : 'No Schedule';
        const fuBadge = lead.follow_up_count > 0
            ? `<span class="badge bg-white text-dark rounded-circle" style="font-size:10px;min-width:18px;height:18px;line-height:18px;">${lead.follow_up_count}</span>` : '';
        const bkBadge = lead.booking_count > 0
            ? `<span class="badge bg-white text-dark rounded-circle" style="font-size:10px;min-width:18px;height:18px;line-height:18px;">${lead.booking_count}</span>` : '';
        const name    = lead.full_name.replace(/</g,'&lt;').replace(/>/g,'&gt;');
        const project = (lead.campaign_name || '-').substring(0, 20);

        return `
        <div class="col-12 col-sm-6">
          <div class="lead-card h-100" data-id="${lead.id}">
            <div class="lead-card-header d-flex justify-content-between align-items-start gap-1">
              <div class="flex-grow-1 overflow-hidden">
                <div class="d-flex align-items-center gap-1">
                  <input type="checkbox" class="selectedIdMobile me-1" data-id="${lead.id}">
                  <span class="lead-name text-truncate">${name}</span>
                </div>
                <div class="lead-phone text-truncate small">${lead.phone || ''}</div>
              </div>
              <div class="dropdown">
                <button class="action-dot-btn" type="button" data-bs-toggle="dropdown">
                  <i class="bi bi-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                  <li><a class="dropdown__item" href="${base_url}/edit-lead/${lead.id}"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit</a></li>
                  <li><a class="dropdown__item" href="${base_url}/view-lead/${lead.id}"><i class="bi bi-eye me-2 text-success"></i>View</a></li>
                </ul>
              </div>
            </div>
            <div class="mt-2">
              <span class="badge ${ac} rounded-pill px-2 py-1 mb-2" style="font-size:.78rem;">${label}</span>
              <div class="d-flex flex-column gap-2">
                <a href="${base_url}/follow-up-leads/${lead.id}" class="btn btn-primary btn-sm w-100 d-flex align-items-center gap-2 px-2">
                  <span>Follow Up</span>${fuBadge}
                </a>
                <a href="${base_url}/booking-leads/${lead.id}" class="btn btn-secondary btn-sm w-100 d-flex align-items-center gap-2 px-2">
                  <span>Booking</span>${bkBadge}
                </a>
              </div>
            </div>
            <div class="project-detail mt-2">
              <div class="info-label">Project</div>
              <span class="project-badge mb-0 mt-1"><i class="bi bi-building"></i> ${project}</span>
            </div>
            <div class="d-flex align-items-center gap-2 mt-2">
              <div class="info-label flex-shrink-0">Next Follow-Up</div>
              <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1" style="font-size:.75rem;">
                <i class="bi bi-calendar2-check me-1"></i>${nextFU}
              </span>
            </div>
            <div class="d-flex align-items-center gap-2 mt-2">
              <div class="info-label flex-shrink-0">Executive</div>
              <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1" style="font-size:.75rem;">
                <i class="bi bi-person me-1"></i>${lead.agent_name || 'No Agent'}
              </span>
            </div>
          </div>
        </div>`;
    }

    function loadMore() {
        if (isLoading) return;
        isLoading = true;
        loadMoreBtn.disabled = true;
        if (loader) loader.style.display = 'block';

        const form = new FormData();
        form.append('draw', Math.ceil(currentStart / PAGE_SIZE) + 1);
        form.append('start', currentStart);
        form.append('length', PAGE_SIZE);
        form.append('search[value]', '');

        fetch(ajaxUrl, { method: 'POST', body: form, credentials: 'same-origin' })
            .then(r => r.json())
            .then(json => {
                totalRecords = json.recordsTotal || totalRecords;
                const newCards = (json.data || []).map(buildCard).join('');
                cardsContainer.insertAdjacentHTML('beforeend', newCards);
                currentStart += (json.data || []).length;

                if (countEl) countEl.textContent = `Showing ${currentStart} of ${totalRecords} leads`;

                if (currentStart >= totalRecords) {
                    loadMoreBtn.style.display = 'none';
                    if (countEl) countEl.textContent = `All ${totalRecords} leads loaded ✓`;
                }
            })
            .catch(() => {
                cardsContainer.insertAdjacentHTML('beforeend',
                    `<div class="col-12"><div class="alert alert-warning py-2">Failed to load. <a href="#" onclick="location.reload()">Refresh</a></div></div>`);
            })
            .finally(() => {
                isLoading = false;
                loadMoreBtn.disabled = false;
                if (loader) loader.style.display = 'none';
            });
    }

    loadMoreBtn.addEventListener('click', loadMore);
})();
</script>



<!-- my old lead-lists page where assigned user show and checkbox dile first page er ata a time select by row ata one page row paginate all 10 row selcted  -->

<?php
$page = "Lead & Booking Management";
$show_breadcrumb = true;
?>
<!-- Stats Grid -->
<div class="row  mb-4 align-items-stretch">
    <div class="col-6 col-md-6 col-lg-4 col-xl-2">
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
    <div class="col-6 col-md-6 col-lg-4 col-xl-2">
        <div class="card__wrapper stat-card">
            <div class="stat-label">Total Booked</div>
            <div class="stat-value"><?= $totalBookingCount ?></div>
            <div class="stat-change positive d-none">
                <i class="bi bi-arrow-up"></i> 3.1%
            </div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-lg-4 col-xl-2">
        <div class="card__wrapper stat-card">
            <div class="stat-label">Today Follow-Up</div>
            <div class="stat-value"><?= $totalTodayFollowUps ?></div>
            <div class="stat-change positive d-none">
                <i class="bi bi-arrow-up"></i> 1.2%
            </div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-lg-4 col-xl-2">
        <div class="card__wrapper stat-card">
            <div class="stat-label">Pending Follow-Ups</div>
            <div class="stat-value"><?= $totalTodayPendingFollowUps ?></div>
            <div class="stat-change negative d-none">
                <i class="bi bi-arrow-down"></i> 12.5%
            </div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-lg-4 col-xl-2">
        <div class="card__wrapper stat-card">
            <div class="stat-label">Conversion Rate</div>
            <div class="stat-value"><?= format_bdt($conversionRate ?? 0) ?>%</div>
            <div class="stat-change positive">
                <i class="bi bi-arrow-up"></i> 8.4%
            </div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-lg-4 col-xl-2">
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
                    <div class="col-auto col-sm-6 col-md-auto ">
                        <select class="form-select form-select-sm filter-select select2" name="assigned_user">
                            <option value="">Select Sales Executive</option>
                            <?php foreach ($assignedUsers as $user): ?>
                                <option value="<?= $user['id'] ?>" <?= ($selectedAssignedUser ?? '') == $user['id'] ? 'selected' : '' ?>>
                                    <?= $user['full_name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto col-sm-6 col-md-auto">
                        <input type="date" name="date" class="form-control form-control-sm"
                            value="<?= $_GET['date'] ?? '' ?>">
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
                    <!-- Assigned Filter -->
                    <div class="col-auto col-sm-6 col-md-auto">
                        <select class="form-select form-select-sm filter-select" name="assigned">
                            <option value="">Assigned: All</option>
                            <option value="assigned" <?= ($selectedAssigned ?? '') == 'assigned'   ? 'selected' : '' ?>>
                                Assigned
                            </option>
                            <option value="unassigned" <?= ($selectedAssigned ?? '') == 'unassigned' ? 'selected' : '' ?>>
                                Not Assigned
                            </option>
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
                        <button type="button" class="d-none btn btn-outline-secondary btn-sm" id="importCsvBtn" data-bs-toggle="modal" data-bs-target="#importCsvModal">
                            Import CSV
                        </button>

                        <a href="<?= $GLOBALS['base_url'] ?>/create-lead" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg"></i> Add Lead
                        </a>
                    </div>

                </div>
            </form>
        </div>
        <?php if (isset($_SESSION['user']['role_id']) && $_SESSION['user']['role_id'] == 5): ?>
            <div class="col-md-6">
                <div id="assignCard"
                    class="card__wrapper shadow-sm p-3 my-2"
                    style="display:none; border-radius:12px;">

                    <div class="d-flex align-items-center gap-2 flex-wrap">

                        <label class="fw-bold mb-0">Assign Selected Leads to: <span id="selectedCount" class="badge bg-primary ms-2">0</span></label>

                        <select id="assignUserSelect"
                            class="form-select form-select-sm select2"
                            style="min-width:200px; width:100%;">

                            <option value=""></option> <!-- important for placeholder -->

                            <?php foreach ($assignedUsers as $user): ?>
                                <option value="<?= $user['id'] ?>">
                                    <?= htmlspecialchars($user['full_name']) ?> - <?= htmlspecialchars($user['role_name']) ?>
                                </option>
                            <?php endforeach ?>
                        </select>

                        <!--  NEW DATE INPUT -->
                        <input type="date"
                            id="assignedDate"
                            class="form-control form-control-sm"
                            style="width:auto;"
                            value="<?= date('Y-m-d') ?>">

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
        <?php endif; ?>


        <p>Lead Lists</p>

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
                                <th>Sales Executive & Note</th>
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
                                    <td>
                                        <?php
                                        $phone = $lead['phone_number'] ?? $lead['phone'];
                                        $phone = preg_replace('/[^0-9]/', '', $phone); // clean number
                                        ?>

                                        <a href="https://wa.me/<?= $phone ?>" target="_blank">
                                            <?= $phone ?>
                                        </a>
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
                                        <div>
                                            <?= !empty($lead['next_follow_up_date']) && $lead['next_follow_up_date'] !== '0000-00-00'
                                                ? formatDate($lead['next_follow_up_date'])
                                                : 'No Schedule' ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2 flex-nowrap">
                                            <span class="bd-badge bg-primary text-truncate" style="max-width: 150px;">
                                                <?= $lead['agent_name'] ?? 'No Agent Assigned' ?>
                                            </span>

                                            <?php
                                            $note      = $lead['call_note'] ?? 'No note';
                                            $words     = explode(' ', trim($note));
                                            $isLong    = count($words) > 10;
                                            $shortNote = $isLong ? implode(' ', array_slice($words, 0, 10)) . '...' : $note;
                                            ?>

                                            <span class="bd-badge bg-success"
                                                style="">

                                                <?php if (!empty($lead['call_note'])): ?>
                                                    <button type="button"
                                                        class="btn btn-sm  p-0 px-1 note-view-btn"
                                                        style="font-size:11px; vertical-align:baseline; color:#fff;"
                                                        data-note="<?= htmlspecialchars($lead['call_note'], ENT_QUOTES, 'UTF-8') ?>"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#viewNoteModal">
                                                        View Note
                                                    </button>
                                                <?php else: ?>
                                                    No Note
                                                <?php endif; ?>

                                            </span>
                                        </div>
                                    </td>

                                                            <td>
                                        <?php if (
                                            isset($_SESSION['user']['role_id']) &&
                                            in_array($_SESSION['user']['role_id'], [1, 3, 5, 9])
                                        ): ?>
                                       <div class="d-flex align-items-center gap-1">

    <!-- Follow Up -->
    <a href="<?= $GLOBALS['base_url'] ?>/follow-up-leads/<?= $lead['id'] ?>"
        class="btn btn-primary btn-sm position-relative"
        style="white-space:nowrap;">
        Follow Up
        <?php if (!empty($followUpCounts[$lead['id']])): ?>
            <span class="action-badge" style="">
                <?= $followUpCounts[$lead['id']] ?>
            </span>
        <?php endif; ?>
    </a>

    <!-- Booking -->
    <a href="<?= $GLOBALS['base_url'] ?>/booking-leads/<?= $lead['id'] ?>"
        class="btn btn-secondary btn-sm position-relative"
        style="white-space:nowrap;">
        <?= !empty($bookingCount[$lead['id']]) ? 'Booked' : 'Booking' ?>
        <span class="action-badge" style="">
            <?= $bookingCount[$lead['id']] ?? 0 ?>
        </span>
    </a>

    <!-- 3 Dot Dropdown -->
    <div class="dropdown">
        <button class="action-dot-btn btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
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
                <button class="dropdown__item text-danger removeBtnCust"
                    data-name="<?= $lead['full_name'] ?>"
                    data-action="lead-delete"
                    data-id="<?= $lead['id'] ?>">
                    <i class="bi bi-trash me-2"></i> Delete
                </button>
            </li>
        </ul>
    </div>

</div>
                                        <?php else: ?>
                                            <div class="d-flex align-items-center gap-1 flex-nowrap">

                                                <!-- Follow Up (Disabled) -->
                                                <button class="btn btn-success btn-sm position-relative" disabled>
                                                    Follow Up
                                                    <span class="action-badge"><?= $followUpCounts[$lead['id']] ?? 0 ?></span>
                                                </button>

                                                <!-- Booking (Disabled) -->
                                                <button class="btn btn-secondary btn-sm position-relative" disabled>
                                                    <?= !empty($bookingCount[$lead['id']]) ? 'Booked' : 'Booking' ?>
                                                    <span class="action-badge"><?= $bookingCount[$lead['id']] ?? 0 ?></span>
                                                </button>

                                            </div>
                                        <?php endif; ?>
                                    </td>


                                </tr>
                            <?php endforeach; ?>

                        </tbody>
                    </table>
                </div>
            </div>

            <div id="viewNoteModal" class="modal custom-modal fade">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Call Note</h5>
                            <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                                <i class="fa-solid fa-xmark-large"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <textarea id="view_note_text" class="form-control" rows="4" readonly></textarea>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Mobile View: Filter Button and Cards -->
    <div class="mobile-only">
        <!-- Filter & Sort Button -->

        <!-- Mobile Filter Button -->
        <button class="filter-button w-100 mb-2 btn btn-outline-primary"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mobileFilterCollapse"
            aria-expanded="false"
            aria-controls="mobileFilterCollapse">
            <i class="bi bi-funnel"></i> Filter & Sort
        </button>

        <!-- Mobile Filter -->
        <div class="collapse" id="mobileFilterCollapse">

            <form action="" method="get" class="p-3 border rounded bg-white shadow-sm">

                <div class="d-flex flex-column gap-2">

                    <!-- Year -->
                    <?php
                    $currentYear = date('Y');
                    $startYear = $currentYear - 10;
                    $selectedYear = $_GET['year'] ?? '';
                    ?>
                    <select class="form-select form-select-sm" name="year">
                        <option value="">Select Year</option>
                        <?php for ($y = $currentYear; $y >= $startYear; $y--): ?>
                            <option value="<?= $y ?>" <?= $selectedYear == $y ? 'selected' : '' ?>>
                                <?= $y ?>
                            </option>
                        <?php endfor; ?>
                    </select>

                    <!-- Month -->
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
                    <select class="form-select form-select-sm" name="month">
                        <option value="">Select Month</option>
                        <?php foreach ($months as $id => $name): ?>
                            <option value="<?= $id ?>" <?= $selectedMonth == $id ? 'selected' : '' ?>>
                                <?= $name ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Date -->
                    <input type="date"
                        name="date"
                        class="form-control form-control-sm"
                        value="<?= $_GET['date'] ?? '' ?>">

                    <!-- Assigned User -->
                    <select class="form-select form-select-sm select2" name="assigned_user">
                        <option value="">Select Sales Executive</option>
                        <?php foreach ($assignedUsers as $user): ?>
                            <option value="<?= $user['id'] ?>"
                                <?= ($_GET['assigned_user'] ?? '') == $user['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Status -->
                    <select class="form-select form-select-sm" name="last_action">
                        <option value="">Status: All</option>
                        <option value="new" <?= ($_GET['last_action'] ?? '') == 'new' ? 'selected' : '' ?>>New</option>
                        <option value="booked" <?= ($_GET['last_action'] ?? '') == 'booked' ? 'selected' : '' ?>>Booked</option>
                        <option value="follow up" <?= ($_GET['last_action'] ?? '') == 'follow up' ? 'selected' : '' ?>>Follow Up</option>
                        <option value="visited" <?= ($_GET['last_action'] ?? '') == 'visited' ? 'selected' : '' ?>>Visited</option>
                        <option value="cancel" <?= ($_GET['last_action'] ?? '') == 'cancel' ? 'selected' : '' ?>>Cancel</option>
                    </select>

                    <!-- Assigned / Unassigned -->
                    <select class="form-select form-select-sm" name="assigned">
                        <option value="">Assigned: All</option>
                        <option value="assigned" <?= ($_GET['assigned'] ?? '') == 'assigned' ? 'selected' : '' ?>>
                            Assigned
                        </option>
                        <option value="unassigned" <?= ($_GET['assigned'] ?? '') == 'unassigned' ? 'selected' : '' ?>>
                            Not Assigned
                        </option>
                    </select>

                    <!-- Buttons -->
                    <div class="d-flex flex-column gap-2 mt-2">

                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            Search
                        </button>

                        <a href="<?= $GLOBALS['base_url'] ?>/lead-lists"
                            class="btn btn-outline-secondary btn-sm w-100">
                            Reset
                        </a>

                        <a href="<?= $GLOBALS['base_url'] ?>/create-lead"
                            class="btn btn-success btn-sm w-100">
                            <i class="bi bi-plus-lg"></i> Add Lead
                        </a>

                    </div>

                </div>
            </form>
        </div>

        <!-- Mobile Assign Card (sticky bottom) -->
        <div class="mobile-only mb-2" id="assignCardMobile"
            style="display:none; position:sticky; bottom:0; z-index:1000; background:#fff; padding:10px; border-top:1px solid #ccc;">
            <div class="text-center small mb-1 fw-bold text-primary">Selected: <span id="selectedCountMobile">0</span> leads</div>
            <div class="d-flex flex-column gap-2">

                <!-- User Select -->
                <select id="assignUserSelectMobile"
                    class="form-select form-select-sm select2">
                    <option value="">Select User</option>
                    <?php foreach ($assignedUsers as $user): ?>
                        <option value="<?= $user['id'] ?>">
                            <?= htmlspecialchars($user['full_name']) ?> - <?= htmlspecialchars($user['role_name']) ?>
                        </option>
                    <?php endforeach ?>
                </select>

                <!--  Assigned Date -->
                <input type="date"
                    id="assignedDateMobile"
                    class="form-control form-control-sm"
                    value="<?= date('Y-m-d') ?>">

                <!-- Button -->
                <button id="assignUserBtnMobile"
                    class="btn btn-primary btn-sm w-100">
                    <i class="fa-solid fa-user-plus me-1"></i> Assign Leads
                </button>

                <span id="assignFeedbackMobile"
                    class="text-success fw-bold text-center"
                    style="display:none;">
                    Leads assigned!
                </span>

            </div>
        </div>

        <div class="">
            <?php foreach ($leads as $key => $lead): ?>
                <?php
                $displayName = !empty($lead['campaign_name'])
                    ? $lead['campaign_name']
                    : (!empty($lead['project_name']) ? $lead['project_name'] : '-');
                $plainText = strip_tags($displayName);
                ?>
                <div class="col-12 col-sm-6">
                    <!-- Lead Card -->
                    <div class="lead-card h-100" data-id="<?= $lead['id'] ?>">
                        <div class="lead-card-header d-flex justify-content-between align-items-start gap-1">
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="d-flex align-items-center gap-1">
                                    <input type="checkbox" class="selectedIdMobile me-1" data-id="<?= $lead['id'] ?>">
                                    <span class="lead-name text-truncate"><?= $lead['full_name'] ?></span>
                                </div>
                                <div class="lead-phone text-truncate small"><?= $lead['phone'] ?></div>
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

                        <!-- Status & Actions -->
                        <div class="mt-2">
                            <div class="info-label">Status & Action</div>
                            <form method="POST" action="<?= $GLOBALS['base_url'] ?>/update-last_leadAction/<?= $lead['id'] ?>" class="m-0 p-0 mb-2">
                                <select name="last_lead_action"
                                    class="form-select form-select-sm last-action-select w-100 <?= 'action-' . ($lead['last_lead_action'] ?? 'not_called') ?>"
                                    onchange="this.form.submit()">
                                    <option value="">Update action</option>
                                    <option value="new" <?= $lead['last_lead_action'] == 'new' ? 'selected' : '' ?>>New</option>
                                    <option value="booked" <?= $lead['last_lead_action'] == 'booked' ? 'selected' : '' ?>>Booked</option>
                                    <option value="follow up" <?= $lead['last_lead_action'] == 'follow up' ? 'selected' : '' ?>>Follow Up</option>
                                    <option value="visited" <?= $lead['last_lead_action'] == 'visited' ? 'selected' : '' ?>>Visited</option>
                                    <option value="cancel" <?= $lead['last_lead_action'] == 'cancel' ? 'selected' : '' ?>>Cancel</option>
                                </select>
                            </form>

                            <div class="d-flex flex-column gap-2">
                                <a href="<?= $GLOBALS['base_url'] ?>/follow-up-leads/<?= $lead['id'] ?>"
                                    class="btn btn-primary btn-sm w-100 d-flex align-items-center gap-2 px-2">
                                    <span>Follow Up</span>

                                    <?php if (!empty($followUpCounts[$lead['id']])): ?>
                                        <span class="badge bg-white text-dark rounded-circle  justify-content-center"
                                            style="font-size: 10px; width: 18px; height: 18px;">
                                            <?= $followUpCounts[$lead['id']] ?>
                                        </span>
                                    <?php endif; ?>
                                </a>

                                <a href="<?= $GLOBALS['base_url'] ?>/booking-leads/<?= $lead['id'] ?>"
                                    class="btn btn-secondary btn-sm w-100 d-flex align-items-center gap-2 px-2">
                                    <span>Booking</span>

                                    <?php if (!empty($bookingCount[$lead['id']])): ?>
                                        <span class="badge bg-white text-dark rounded-circle  justify-content-center"
                                            style="font-size: 10px; width: 18px; height: 18px;">
                                            <?= $bookingCount[$lead['id']] ?>
                                        </span>
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
                </div>
            <?php endforeach; ?>
        </div>

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

    document.querySelectorAll('.note-view-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('view_note_text').value = this.getAttribute('data-note');
        });
    });

    // Disable right-click
    // document.addEventListener('contextmenu', function(e) {
    //     e.preventDefault();
    // });

    // // Disable Ctrl+C, Ctrl+X, Ctrl+V
    // document.addEventListener('keydown', function(e) {
    //     if ((e.ctrlKey && e.key === 'c') ||
    //         (e.ctrlKey && e.key === 'x') ||
    //         (e.ctrlKey && e.key === 'v')) {
    //         e.preventDefault();
    //     }
    // });

    // // Disable drag
    // document.addEventListener('dragstart', function(e) {
    //     e.preventDefault();
    // });

    // Reset button (null check added)
    const form = document.querySelector('form');
    if (form) {
        const resetBtn = form.querySelector('button[type="reset"]');
        if (resetBtn) {
            resetBtn.addEventListener('click', function(e) {
                e.preventDefault();
                form.querySelectorAll('input, select').forEach(function(el) {
                    if (el.tagName === 'SELECT') el.selectedIndex = 0;
                    else el.value = '';
                });
            });
        }
    }

    // campaign full show
</script>

<!-- Lead Selection & Assignment Logic -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectedLeadsSet = new Set();
        const assignCard = document.getElementById('assignCard');
        const assignCardMobile = document.getElementById('assignCardMobile');
        const selectAll = document.getElementById('selectAllLeads');

        // Function to update the visible state of the assign card
        function updateAssignUIData() {
            const count = selectedLeadsSet.size;
            const anySelected = count > 0;
            if (assignCard) assignCard.style.display = anySelected ? 'flex' : 'none';
            if (assignCardMobile) assignCardMobile.style.display = anySelected ? 'block' : 'none';

            const countEl = document.getElementById('selectedCount');
            if (countEl) countEl.innerText = count;

            const countMobileEl = document.getElementById('selectedCountMobile');
            if (countMobileEl) countMobileEl.innerText = count;
        }

        // Function to sync checkboxes with the Set
        function syncCheckboxes() {
            // Desk checkboxes
            document.querySelectorAll('.selectedId').forEach(cb => {
                cb.checked = selectedLeadsSet.has(cb.dataset.id);
            });
            // Mobile checkboxes
            document.querySelectorAll('.selectedIdMobile').forEach(cb => {
                cb.checked = selectedLeadsSet.has(cb.dataset.id);
            });

            // Update header checkbox
            const currentDeskCheckboxes = Array.from(document.querySelectorAll('.selectedId'));
            if (currentDeskCheckboxes.length > 0) {
                const allCheckedOnPage = currentDeskCheckboxes.every(cb => cb.checked);
                if (selectAll) selectAll.checked = allCheckedOnPage;
            }
        }

        // Shared function to submit the assignment form
        function submitAssignForm(userId, selectedLeads, assignedDate) {
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

            const inputDate = document.createElement('input');
            inputDate.type = 'hidden';
            inputDate.name = 'assigned_date';
            inputDate.value = assignedDate;
            form.appendChild(inputDate);

            document.body.appendChild(form);
            form.submit();
        }

        // --- DESKTOP ASSIGN ---
        const assignBtn = document.getElementById('assignUserBtn');
        if (assignBtn) {
            assignBtn.addEventListener('click', function() {
                const userId = document.getElementById('assignUserSelect').value;
                const assignedDate = document.getElementById('assignedDate').value;
                const selectedLeads = Array.from(selectedLeadsSet);

                if (!userId || selectedLeads.length === 0) {
                    alert('Select leads and a user to assign.');
                    return;
                }
                submitAssignForm(userId, selectedLeads, assignedDate);
            });
        }

        // --- MOBILE ASSIGN ---
        const assignBtnMobile = document.getElementById('assignUserBtnMobile');
        if (assignBtnMobile) {
            assignBtnMobile.addEventListener('click', function() {
                const selectedLeads = Array.from(selectedLeadsSet);
                const userId = document.getElementById('assignUserSelectMobile').value;
                const assignedDate = document.getElementById('assignedDateMobile').value;

                if (!userId || selectedLeads.length === 0) {
                    alert('Select leads and a user to assign.');
                    return;
                }
                submitAssignForm(userId, selectedLeads, assignedDate);
            });
        }

        // Handle header checkbox (Select All on current page)
        if (selectAll) {
            // Prevent DataTables scaling/sorting when clicking the checkbox
            selectAll.addEventListener('click', function(e) {
                e.stopPropagation();
            });

            selectAll.addEventListener('change', function() {
                const table = $('.multiple_tables').DataTable();
                const currentPageNodes = table.rows({
                    page: 'current'
                }).nodes();
                const isChecked = this.checked;

                // Only toggle leads on the current page
                $(currentPageNodes).find('.selectedId').each(function() {
                    this.checked = isChecked;
                    const id = this.dataset.id;

                    if (isChecked) {
                        selectedLeadsSet.add(id);
                    } else {
                        selectedLeadsSet.delete(id);
                    }

                    // Sync the mobile version for this specific lead only
                    const mobileCb = document.querySelector(`.selectedIdMobile[data-id="${id}"]`);
                    if (mobileCb) mobileCb.checked = isChecked;
                });

                updateAssignUIData();
            });
        }

        // Event delegation for individual row checkboxes
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('selectedId') || e.target.classList.contains('selectedIdMobile')) {
                const id = e.target.dataset.id;
                if (e.target.checked) {
                    selectedLeadsSet.add(id);
                } else {
                    selectedLeadsSet.delete(id);
                }
                syncCheckboxes();
                updateAssignUIData();
            }
        });

        // Prevent sorting when clicking row checkboxes
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('selectedId') || e.target.classList.contains('selectedIdMobile')) {
                e.stopPropagation();
            }
        });

        // Hook into DataTables 'draw' event
        const dtTable = $('.multiple_tables');
        if (dtTable.length > 0) {
            // Get DataTable instance
            const table = dtTable.DataTable();

            // Disable sorting on the first column (where the checkbox is)
            table.settings()[0].aoColumns[0].bSortable = false;

            table.on('draw', function() {
                syncCheckboxes();
            });
        }
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
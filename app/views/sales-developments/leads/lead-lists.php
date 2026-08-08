<?php
$page = "Lead & Booking Management";
$show_breadcrumb = true;

// ── Booking CSV import skipped-row report ────────────────────────
$bookingCsvSkipped = $_SESSION['booking_csv_import_skipped'] ?? [];
unset($_SESSION['booking_csv_import_skipped']); // clear after reading
?>
<?php if (!empty($bookingCsvSkipped)): ?>
<div class="card__wrapper mb-3" style="border-left: 4px solid #f59e0b;">
    <div class="d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
        <h6 class="mb-0 fw-semibold text-warning">
            <?= count($bookingCsvSkipped) ?> row(s) were <u>not imported</u> from the Booking CSV
        </h6>
    </div>
    <div class="table-responsive" style="max-height:320px; overflow-y:auto;">
        <table class="table table-sm table-bordered mb-0" style="font-size:13px;">
            <thead class="table-warning">
                <tr>
                    <th style="width:60px;">CSV Row</th>
                    <th>Lead Phone</th>
                    <th>Project</th>
                    <th>Flat / Unit</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookingCsvSkipped as $sk): ?>
                <tr>
                    <td class="text-center fw-bold"><?= (int)$sk['row'] ?></td>
                    <td><?= htmlspecialchars($sk['lead_phone'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($sk['project']    ?? '—') ?></td>
                    <td><?= htmlspecialchars($sk['flat']       ?? '—') ?></td>
                    <td class="text-danger"><?= htmlspecialchars($sk['reason'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<!-- Stats Grid -->
<?php
// Find the selected executive's name for display
$filteredExecName = '';
if (!empty($selectedAssignedUser)) {
    foreach (($assignedUsers ?? []) as $u) {
        if ($u['id'] == $selectedAssignedUser) {
            $filteredExecName = $u['full_name'] ?? '';
            break;
        }
    }
}

// Helper function to build stat card link
if (!function_exists('getStatCardLink')) {
    function getStatCardLink($cardType) {
        $params = $_GET;
        if ($cardType === 'total_leads') {
            unset($params['filter_card']);
        } else {
            $params['filter_card'] = $cardType;
        }
        unset($params['page'], $params['url']);
        return $GLOBALS['base_url'] . '/lead-lists' . ($params ? '?' . http_build_query($params) : '');
    }
}
$activeCard = $_GET['filter_card'] ?? '';
?>
<?php if (!empty($filteredExecName)): ?>
<div class="d-flex align-items-center gap-2 mb-2">
    <span class="badge rounded-pill text-bg-primary" style="font-size:0.8rem; padding:6px 14px;">
        <i class="fa-solid fa-filter me-1"></i>
        Filtered for: <strong><?= htmlspecialchars($filteredExecName) ?></strong>
    </span>
    <span class="text-muted small">Stats below are specific to this sales executive</span>
</div>
<?php endif; ?>
<div class="row mb-4 align-items-stretch">
    <div class="col-6 col-md-6 col-lg-4 col-xl-2">
        <a href="<?= getStatCardLink('total_leads') ?>" class="card__wrapper stat-card text-decoration-none d-block h-100 <?= !empty($filteredExecName) ? 'border border-primary border-opacity-25' : '' ?>" style="cursor: pointer; <?= empty($activeCard) ? 'border: 2px solid #10b981 !important; background-color: #f0fdf4;' : '' ?>">
            <div class="stat-label">Total Leads</div>
            <div class="stat-value"><?= $leadCounts ?></div>
            <div class="stat-change positive d-none">
                <span class="<?= $leadsPercentChange >= 0 ? 'price-increase' : 'price-decrease' ?>">
                    <i class="fa-light <?= $leadsPercentChange >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' ?>"></i>
                    <?= abs($leadsPercentChange) ?>%
                </span>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-6 col-lg-4 col-xl-2">
        <a href="<?= getStatCardLink('total_booked') ?>" class="card__wrapper stat-card text-decoration-none d-block h-100 <?= !empty($filteredExecName) ? 'border border-primary border-opacity-25' : '' ?>" style="cursor: pointer; <?= $activeCard === 'total_booked' ? 'border: 2px solid #10b981 !important; background-color: #f0fdf4;' : '' ?>">
            <div class="stat-label">Total Booked <?= !empty($filteredExecName) ? '<i class="fa-solid fa-filter ms-1 text-primary" style="font-size:9px;" title="Filtered"></i>' : '' ?></div>
            <div class="stat-value"><?= $totalBookingCount ?></div>
            <div class="stat-change positive d-none">
                <i class="bi bi-arrow-up"></i> 3.1%
            </div>
        </a>
    </div>
    <div class="col-6 col-md-6 col-lg-4 col-xl-2">
        <a href="<?= getStatCardLink('today_follow_up') ?>" class="card__wrapper stat-card text-decoration-none d-block h-100 <?= !empty($filteredExecName) ? 'border border-primary border-opacity-25' : '' ?>" style="cursor: pointer; <?= $activeCard === 'today_follow_up' ? 'border: 2px solid #10b981 !important; background-color: #f0fdf4;' : '' ?>">
            <div class="stat-label">Today Follow-Up <?= !empty($filteredExecName) ? '<i class="fa-solid fa-filter ms-1 text-primary" style="font-size:9px;" title="Filtered"></i>' : '' ?></div>
            <div class="stat-value"><?= $totalTodayFollowUps ?></div>
            <div class="stat-change positive d-none">
                <i class="bi bi-arrow-up"></i> 1.2%
            </div>
        </a>
    </div>
    <div class="col-6 col-md-6 col-lg-4 col-xl-2">
        <a href="<?= getStatCardLink('pending_follow_ups') ?>" class="card__wrapper stat-card text-decoration-none d-block h-100 <?= !empty($filteredExecName) ? 'border border-primary border-opacity-25' : '' ?>" style="cursor: pointer; <?= $activeCard === 'pending_follow_ups' ? 'border: 2px solid #10b981 !important; background-color: #f0fdf4;' : '' ?>">
            <div class="stat-label">Pending Follow-Ups <?= !empty($filteredExecName) ? '<i class="fa-solid fa-filter ms-1 text-primary" style="font-size:9px;" title="Filtered"></i>' : '' ?></div>
            <div class="stat-value"><?= $totalTodayPendingFollowUps ?></div>
            <div class="stat-change negative d-none">
                <i class="bi bi-arrow-down"></i> 12.5%
            </div>
        </a>
    </div>
    <div class="col-6 col-md-6 col-lg-4 col-xl-2">
        <div class="card__wrapper stat-card <?= !empty($filteredExecName) ? 'border border-primary border-opacity-25' : '' ?>">
            <div class="stat-label">Conversion Rate <?= !empty($filteredExecName) ? '<i class="fa-solid fa-filter ms-1 text-primary" style="font-size:9px;" title="Filtered"></i>' : '' ?></div>
            <div class="stat-value"><?= format_bdt($conversionRate ?? 0) ?>%</div>
            <div class="stat-change positive <?= empty($filteredExecName) ? '' : 'd-none' ?>">
                <i class="bi bi-arrow-up"></i> 8.4%
            </div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-lg-4 col-xl-2">
        <a href="<?= getStatCardLink('hot_leads') ?>" class="card__wrapper stat-card text-decoration-none d-block h-100 <?= !empty($filteredExecName) ? 'border border-primary border-opacity-25' : '' ?>" style="cursor: pointer; <?= $activeCard === 'hot_leads' ? 'border: 2px solid #10b981 !important; background-color: #f0fdf4;' : '' ?>">
            <div class="stat-label">Hot Leads <?= !empty($filteredExecName) ? '<i class="fa-solid fa-filter ms-1 text-primary" style="font-size:9px;" title="Filtered"></i>' : '' ?></div>
            <div class="stat-value"><?= $totalHotLeads ?></div>
            <div class="stat-change positive d-none">
                <i class="bi bi-arrow-up"></i> 2.5%
            </div>
        </a>
    </div>
    <div class="col-6 col-md-6 col-lg-4 col-xl-2">
        <a href="<?= getStatCardLink('contacted_leads') ?>" class="card__wrapper stat-card text-decoration-none d-block h-100 <?= !empty($filteredExecName) ? 'border border-primary border-opacity-25' : '' ?>" style="cursor: pointer; <?= $activeCard === 'contacted_leads' ? 'border: 2px solid #10b981 !important; background-color: #f0fdf4;' : '' ?>">
            <div class="stat-label">Total Contacted <?= !empty($filteredExecName) ? '<i class="fa-solid fa-filter ms-1 text-primary" style="font-size:9px;" title="Filtered"></i>' : '' ?></div>
            <div class="stat-value"><?= $totalContactedLeads ?></div>
            <div class="stat-change positive d-none">
                <i class="bi bi-arrow-up"></i> 1.5%
            </div>
        </a>
    </div>


    <!-- Duplicate Leads Alert Card -->
    <?php if (!empty($dupGroups)): ?>
    <div class="col-12 mt-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 rounded shadow-sm"
             style="background:linear-gradient(135deg,#fff5f5,#ffe0e0);border-left:4px solid #dc3545;">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width:40px;height:40px;background:#dc3545;">
                    <i class="bi bi-exclamation-triangle-fill text-white"></i>
                </div>
                <div>
                    <div class="fw-bold text-danger" style="font-size:.92rem;">
                        &#9888; Duplicate Leads Detected
                    </div>
                    <div class="text-muted" style="font-size:.76rem;">
                        <strong><?= $dupCount ?></strong> leads from your team (assigned to you)
                        also exist in other assignments &nbsp;&middot;&nbsp;
                        <span class="text-danger fw-semibold">by Phone &amp; Email</span>
                    </div>
                </div>
            </div>
            <button class="btn btn-danger btn-sm px-3" data-bs-toggle="modal" data-bs-target="#leadDupModal">
                <i class="bi bi-eye me-1"></i> View Details
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Desktop View: Filters and Table -->
    <div class="desktop-only">
        <!-- Filters -->
        <div class="filters card__wrapper mb-4 p-3 bg-white rounded shadow-sm">
            <form action="" method="get">
                <!-- Line 1 -->
                <div class="row  align-items-end">
                    
                    <!-- Year Select -->
                    <div class="col-6 col-md-2">
                        <label class="form-label mb-1 fw-bold text-dark small" style="font-size: 0.8rem;">Year</label>
                        <?php
                        $currentYear = date('Y');
                        $startYear   = $currentYear - 10;
                        $selectedYear = $selectedYear ?? '';
                        ?>
                        <select class="form-select form-select-sm" name="year">
                            <option value="">Year: All</option>
                            <?php for ($year = $currentYear; $year >= $startYear; $year--): ?>
                                <option value="<?= $year ?>" <?= $selectedYear == $year ? 'selected' : '' ?>>
                                    <?= $year ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <!-- Month Select -->
                    <div class="col-6 col-md-2">
                        <label class="form-label mb-1 fw-bold text-dark small" style="font-size: 0.8rem;">Month</label>
                        <?php
                        $months = [
                            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                        ];
                        $selectedMonth = $_GET['month'] ?? '';
                        ?>
                        <select class="form-select form-select-sm" name="month">
                            <option value="">Month: All</option>
                            <?php foreach ($months as $id => $name): ?>
                                <option value="<?= $id ?>" <?= $selectedMonth == $id ? 'selected' : '' ?>>
                                    <?= $name ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- From Date -->
                    <div class="col-6 col-md-2">
                        <label class="form-label mb-1 fw-bold text-dark small" style="font-size: 0.8rem;">From Date</label>
                        <input type="date" name="from_date" class="form-control form-control-sm"
                            value="<?= $_GET['from_date'] ?? '' ?>">
                    </div>

                    <!-- To Date -->
                    <div class="col-6 col-md-2">
                        <label class="form-label mb-1 fw-bold text-dark small" style="font-size: 0.8rem;">To Date</label>
                        <input type="date" name="to_date" class="form-control form-control-sm"
                            value="<?= $_GET['to_date'] ?? '' ?>">
                    </div>

                    <!-- Team Filter -->
                    <?php 
                    $sessionUserRoleId = $_SESSION['user']['role_id'] ?? null;
                    if (in_array($sessionUserRoleId, [1, 3, 9]) && !empty($teams)): 
                    ?>
                    <div class="col-6 col-md-2">
                        <label class="form-label mb-1 fw-bold text-dark small" style="font-size: 0.8rem;">Team</label>
                        <select class="form-select form-select-sm filter-select select2" name="team_id" data-placeholder="Team: All">
                            <option value=""></option>
                            <?php foreach ($teams as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= ($selectedTeam ?? '') == $t['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- Project Filter -->
                    <?php if (!empty($projects)): ?>
                    <div class="col-6 col-md-2">
                        <label class="form-label mb-1 fw-bold text-dark small" style="font-size: 0.8rem;">Project</label>
                        <select class="form-select form-select-sm filter-select select2" name="project_id" data-placeholder="Project: All">
                            <option value=""></option>
                            <?php foreach ($projects as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= ($selectedProject ?? '') == $p['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

             

                    <!-- Category Filter -->
                    <?php if (!empty($categories)): ?>
                    <div class="col-6 col-md-2">
                        <label class="form-label mb-1 fw-bold text-dark small" style="font-size: 0.8rem;">Category</label>
                        <select class="form-select form-select-sm filter-select select2" name="project_category_id" data-placeholder="Category: All">
                            <option value=""></option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($selectedCategory ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- Country Filter (Multi-Select) -->
                    <?php if (!empty($countries)): ?>
                    <div class="col-6 col-md-2">
                        <label class="form-label mb-1 fw-bold text-dark small" style="font-size: 0.8rem;">Country</label>
                        <?php 
                        $selectedCountriesArr = is_array($selectedCountry ?? null) 
                            ? $selectedCountry 
                            : (!empty($selectedCountry) ? (array)$selectedCountry : []);
                        ?>
                        <select class="form-select form-select-sm filter-select select2" name="filter_country[]" multiple data-placeholder="Country: All">
                            <?php foreach ($countries as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>" <?= in_array($c, $selectedCountriesArr) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- Sales Executive Filter -->
                    <div class="col-6 col-md-2">
                        <label class="form-label mb-1 fw-bold text-dark small" style="font-size: 0.8rem;">Sales Executive</label>
                        <select class="form-select form-select-sm filter-select select2" name="assigned_user" data-placeholder="Sales Executive: All">
                            <option value="">Sales Executive: All</option>
                            <?php foreach ($assignedUsers as $user): ?>
                                <option value="<?= $user['id'] ?>" <?= ($selectedAssignedUser ?? '') == $user['id'] ? 'selected' : '' ?>>
                                    <?= $user['full_name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Last Action Select -->
                    <div class="col-6 col-md-2">
                        <label class="form-label mb-1 fw-bold text-dark small" style="font-size: 0.8rem;">Status</label>
                        <select class="form-select form-select-sm filter-select" name="last_action">
                            <option value="">Status: All</option>
                            <option value="contacted" <?= ($selectedAction ?? '') == 'contacted' ? 'selected' : '' ?>>Contacted</option>
                            <option value="new" <?= ($selectedAction ?? '') == 'new' ? 'selected' : '' ?>>New</option>
                            <option value="booked" <?= ($selectedAction ?? '') == 'booked' ? 'selected' : '' ?>>Booked</option>
                            <option value="follow up" <?= ($selectedAction ?? '') == 'follow up' ? 'selected' : '' ?>>Follow Up</option>
                            <option value="visited" <?= ($selectedAction ?? '') == 'visited' ? 'selected' : '' ?>>Visited</option>
                            <option value="cancel" <?= ($selectedAction ?? '') == 'cancel' ? 'selected' : '' ?>>Cancel</option>
                        </select>
                    </div>

                    <!-- Assigned Filter -->
                    <div class="col-6 col-md-2">
                        <label class="form-label mb-1 fw-bold text-dark small" style="font-size: 0.8rem;">Assigned</label>
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

                </div>

                <!-- Action Buttons Row -->
                <div class="row mt-3">
                    <div class="col-12 d-flex justify-content-end gap-2 flex-wrap">
                        <button type="submit" class="btn btn-outline-secondary btn-sm px-3 py-1.5">
                            <i class="bi bi-search me-1"></i> Search
                        </button>
                        <a href="<?= $GLOBALS['base_url'] ?>/lead-lists" class="btn btn-primary btn-sm px-3 py-1.5">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                        </a>

                        <!-- Import CSV button outside form submit -->
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3" id="importCsvBtn" data-bs-toggle="modal" data-bs-target="#importCsvModal">
                            Import Leads CSV
                        </button>
                        
                        <button type="button" class="btn btn-outline-info btn-sm px-3" id="importBookingsCsvBtn" data-bs-toggle="modal" data-bs-target="#importBookingsCsvModal">
                            Import Bookings CSV
                        </button>

                        <button type="button" class="btn btn-outline-success btn-sm px-3" id="importPaymentsCsvBtn" data-bs-toggle="modal" data-bs-target="#importPaymentsCsvModal">
                            Import Payments CSV
                        </button>

                        <a href="<?= $GLOBALS['base_url'] ?>/create-lead" class="btn btn-primary btn-sm px-3">
                            <i class="bi bi-plus-lg"></i> Add Lead
                        </a>
                    </div>
                </div>
            </form>
        </div>
        <?php if ((isset($_SESSION['user']['role_id']) && in_array($_SESSION['user']['role_id'], [5, 9])) || isSalesCoLeader()): ?>
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
                                <th>Lead Name & Job title/Company</th>
                                <th>Address & City</th>
                                <th>Lead Uploaded</th>
                                <th>Phone</th>
                                <th>Best Time to Contact</th>
                                <th>Project Campaign</th>
                                <th>Status</th>
                                <th>Next Follow-Up</th>
                                <th>Sales Executive & Note</th>
                                <th>Assigned By</th>
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
                        <option value="">Year: All</option>
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
                        <option value="">Month: All</option>
                        <?php foreach ($months as $id => $name): ?>
                            <option value="<?= $id ?>" <?= $selectedMonth == $id ? 'selected' : '' ?>>
                                <?= $name ?>
                            </option>
                        <?php endforeach; ?>
                    </select>


                    <!-- From Date -->
                    <input type="date"
                        name="from_date"
                        class="form-control form-control-sm"
                        value="<?= $_GET['from_date'] ?? '' ?>"
                        title="From Date"
                        placeholder="From Date">

                    <!-- To Date -->
                    <input type="date"
                        name="to_date"
                        class="form-control form-control-sm"
                        value="<?= $_GET['to_date'] ?? '' ?>"
                        title="To Date"
                        placeholder="To Date">

                    <!-- Assigned User -->
                    <select class="form-select form-select-sm select2" name="assigned_user" data-placeholder="Sales Executive: All">
                        <option value="">Sales Executive: All</option>
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
                        <option value="contacted" <?= ($_GET['last_action'] ?? '') == 'contacted' ? 'selected' : '' ?>>Contacted</option>
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

        <div class="row " id="mobileLeadCards">
            <?php foreach ($leads as $key => $lead): ?>
                <?php
                $displayName = !empty($lead['campaign_name'])
                    ? $lead['campaign_name']
                    : (!empty($lead['project_name']) ? $lead['project_name'] : '-');
                if (!empty($lead['project_category_name'])) {
                    $displayName .= ' (' . $lead['project_category_name'] . ')';
                }
                $plainText = strip_tags($displayName ?? '');
                ?>
                <div class="col-12 col-sm-6">
                    <!-- Lead Card -->
                    <div id="lead-<?= $lead['id'] ?>" class="lead-card h-100" data-id="<?= $lead['id'] ?>">
                        <div class="lead-card-header d-flex justify-content-between align-items-start gap-1">
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="d-flex align-items-center gap-1">
                                    <input type="checkbox" class="selectedIdMobile me-1" data-id="<?= $lead['id'] ?>">
                                    <span class="lead-name text-truncate"><?= $lead['full_name'] ?></span>
                                </div>
                                <?php
                                $jt = !empty($lead['job_title']) ? $lead['job_title'] : (!empty($lead['designation']) ? $lead['designation'] : '');
                                $co = !empty($lead['company_name']) ? $lead['company_name'] : (!empty($lead['company']) ? $lead['company'] : '');
                                if ($jt !== '' || $co !== ''):
                                ?>
                                <div class="text-muted small mt-1">
                                    <i class="bi bi-briefcase me-1"></i>
                                    <?= htmlspecialchars($jt) ?><?= ($jt !== '' && $co !== '') ? ' at ' : '' ?><strong><?= htmlspecialchars($co) ?></strong>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($lead['when_is_the_best_time_to_contact_you'])): ?>
                                <div class="text-info small mt-1">
                                    <i class="bi bi-clock me-1"></i>Best Contact Time: <?= htmlspecialchars($lead['when_is_the_best_time_to_contact_you']) ?>
                                </div>
                                <?php endif; ?>
                                <div class="lead-phone text-truncate small"><?= $lead['phone'] ?></div>
                                <?php 
                                $addrParts = [];
                                if (!empty($lead['country'])) $addrParts[] = trim($lead['country']);
                                if (!empty($lead['address'])) $addrParts[] = trim($lead['address']);
                                if (!empty($lead['city'])) $addrParts[] = trim($lead['city']);
                                $mAddr = implode(', ', $addrParts);
                                ?>
                                <?php if ($mAddr): ?>
                                <div class="mt-1">
                                    <i class="bi bi-geo-alt-fill text-muted me-1" style="font-size:11px"></i>
                                    <span class="fs-11 text-muted"><?= htmlspecialchars($mAddr) ?></span>
                                </div>
                                <?php endif; ?>
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
                                    <option value="contacted" <?= $lead['last_lead_action'] == 'contacted' ? 'selected' : '' ?>>Contacted</option>
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
                        <div class="d-flex align-items-start gap-2 mt-2">
                            <div class="info-label flex-shrink-0 mt-1">Executive Name</div>
                            <div>
                                <?php if (empty($lead['agent_name'])): ?>
                                    <span class="badge rounded-pill px-2 py-1 text-danger" style="font-size: 0.75rem; background-color: #fde8e8; border: 1px solid #f8b4b4;">
                                        <i class="bi bi-person me-1"></i>
                                        No Agent Assigned
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1" style="font-size: 0.75rem;">
                                        <i class="bi bi-person me-1"></i>
                                        <?= htmlspecialchars($lead['agent_name']) ?>
                                    </span>
                                    <?php
                                    $assignedRaw = (!empty($lead['assigned_at']) && $lead['assigned_at'] !== '0000-00-00 00:00:00' && $lead['assigned_at'] !== '0000-00-00')
                                        ? $lead['assigned_at']
                                        : (!empty($lead['updated_at']) && $lead['updated_at'] !== '0000-00-00 00:00:00' ? $lead['updated_at'] : $lead['created_at']);
                                    ?>
                                    <div class="text-muted" style="font-size: 11px; margin-top: 3px;">
                                        <i class="bi bi-calendar-event me-1"></i>Assigned Date: <?= date('d M, Y', strtotime($assignedRaw)) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Assigned By Name -->
                        <div class="d-flex align-items-center gap-2 mt-2">
                            <div class="info-label flex-shrink-0">Assigned By</div>
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1" style="font-size: 0.75rem;">
                                <i class="bi bi-person-check me-1"></i>
                                <?= htmlspecialchars($lead['assigned_by_name'] ?? 'System') ?>
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
                <form action="<?= $GLOBALS['base_url'] ?>/bulk-leads-import-csv"
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

                <div class="mt-15 p-2  rounded text-muted" style="font-size: 12px; max-height: 200px; overflow-y: auto;">
                    <strong>Required CSV Headers:</strong><br>
                    full_name, job_title, company_name, phone, alt_phone_number, email, lead_image, lead_name_bn, father_name_bn, mother_name_bn, profession_bn, nationality, religion, nominee_name, nomi_father_name, nomi_mother_name, nomi_profession, relation_with_lead, nomi_dob, nomi_nid_passport, nomi_phone, nomi_email, nomi_present_add, nomi_permanent_address, nomi_image, team_id, assigned_id
                    <br><br>
                    <em>Note: For image columns, provide the exact file name (e.g., `user.jpg`). Ensure these images exist in the upload directory.</em>
                </div>
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
    var base_url = "<?= $GLOBALS['base_url'] ?>"; // var allows redeclaration safely

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
    function submitAssignForm(userId, selectedLeads, assignedDate, routeMode = 'new') {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= $GLOBALS['base_url'] ?>/assign-multiple-leads';
        const addHidden = (name, val) => {
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = name; inp.value = val;
            form.appendChild(inp);
        };
        // Capture current page from DataTables or URL
        const urlParams = new URLSearchParams(window.location.search);
        const currentPage = urlParams.get('page') || '1';
        // Also try to get DataTables current page start
        let dtPage = 1;
        try {
            const dtTable = $('#serverSideLeadTable').DataTable();
            const pageInfo = dtTable.page.info();
            dtPage = pageInfo.page + 1; // DataTables is 0-indexed
        } catch(e) {}
        const finalPage = dtPage > 1 ? dtPage : currentPage;

        addHidden('user_id',       userId);
        addHidden('lead_ids',      selectedLeads.join(','));
        addHidden('assigned_date', assignedDate);
        addHidden('redirect_page', finalPage);
        addHidden('route_mode',    routeMode);
        // Also preserve all current GET filters
        const filterParams = ['year','month','assigned_user','last_action','assigned','q','from_date','to_date','project_id','project_category_id'];
        filterParams.forEach(p => {
            const v = urlParams.get(p);
            if (v) addHidden('redirect_' + p, v);
        });
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
            
            // Check for duplicates via AJAX first
            const fd = new FormData();
            fd.append('lead_ids', leads.join(','));
            
            fetch(base_url + '/check-leads-duplicates', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.has_duplicates) {
                        let dupInfo = data.duplicates.map(d => 
                            `• <strong>${d.current_lead_name}</strong> is already assigned to <strong>${d.original_assignee_name}</strong> (${d.original_team_name || 'No Team'})`
                        ).join('<br>');
                        
                        Swal.fire({
                            title: 'Duplicate Leads Detected',
                            html: `<div class="text-start mb-3" style="font-size:0.85rem;">
                                    Some of the selected leads are already assigned:<br><br>
                                    ${dupInfo}<br><br>
                                    Please choose how to assign them:<br><br>
                                    • <strong>Split Assign:</strong> Assign duplicate leads back to their <strong>Existing Executive(s)</strong> (e.g. Shafia), and assign the remaining leads to the <strong>New Executive</strong>.<br><br>
                                    • <strong>Assign All to New:</strong> Override and assign all selected leads (including duplicates) to the <strong>New Executive</strong>.
                                   </div>`,
                            icon: 'warning',
                            showCancelButton: true,
                            showDenyButton: true,
                            confirmButtonText: 'Split Assign (Existing + Rest to New)',
                            denyButtonText: 'Assign All to New',
                            cancelButtonText: 'Cancel',
                            confirmButtonColor: '#198754',
                            denyButtonColor: '#0d6efd',
                            cancelButtonColor: '#6c757d'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                submitAssignForm(userId, leads, assignedDate, 'existing');
                            } else if (result.isDenied) {
                                submitAssignForm(userId, leads, assignedDate, 'new');
                            }
                        });
                    } else {
                        submitAssignForm(userId, leads, assignedDate, 'new');
                    }
                })
                .catch(() => {
                    submitAssignForm(userId, leads, assignedDate, 'new');
                });
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
            
            // Check for duplicates via AJAX first
            const fd = new FormData();
            fd.append('lead_ids', leads.join(','));
            
            fetch(base_url + '/check-leads-duplicates', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.has_duplicates) {
                        let dupInfo = data.duplicates.map(d => 
                            `• <strong>${d.current_lead_name}</strong> is already assigned to <strong>${d.original_assignee_name}</strong> (${d.original_team_name || 'No Team'})`
                        ).join('<br>');
                        
                        Swal.fire({
                            title: 'Duplicate Leads Detected',
                            html: `<div class="text-start mb-3" style="font-size:0.85rem;">
                                    Some of the selected leads are already assigned:<br><br>
                                    ${dupInfo}<br><br>
                                    Please choose how to assign them:<br><br>
                                    • <strong>Split Assign:</strong> Assign duplicate leads back to their <strong>Existing Executive(s)</strong> (e.g. Shafia), and assign the remaining leads to the <strong>New Executive</strong>.<br><br>
                                    • <strong>Assign All to New:</strong> Override and assign all selected leads (including duplicates) to the <strong>New Executive</strong>.
                                   </div>`,
                            icon: 'warning',
                            showCancelButton: true,
                            showDenyButton: true,
                            confirmButtonText: 'Split Assign (Existing + Rest to New)',
                            denyButtonText: 'Assign All to New',
                            cancelButtonText: 'Cancel',
                            confirmButtonColor: '#198754',
                            denyButtonColor: '#0d6efd',
                            cancelButtonColor: '#6c757d'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                submitAssignForm(userId, leads, assignedDate, 'existing');
                            } else if (result.isDenied) {
                                submitAssignForm(userId, leads, assignedDate, 'new');
                            }
                        });
                    } else {
                        submitAssignForm(userId, leads, assignedDate, 'new');
                    }
                })
                .catch(() => {
                    submitAssignForm(userId, leads, assignedDate, 'new');
                });
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

        // Restore page position from URL ?page= parameter
        const pageFromUrl   = parseInt(urlParams.get('page') || '1', 10);
        const pageLength    = 10; // must match server perPage
        const displayStart  = (pageFromUrl > 1) ? (pageFromUrl - 1) * pageLength : 0;

        const dtTable = $('#serverSideLeadTable').DataTable({
            serverSide   : true,
            processing   : true,
            ajax         : { url: ajaxUrl, type: 'POST' },
            lengthMenu   : [[10, 25, 50, 100, 200, -1], [10, 25, 50, 100, 200, "All"]],
            pageLength   : pageLength,
            displayStart : displayStart,
            order        : [],
            columnDefs   : [
                { targets: 0, orderable: false },
                { targets: 10, orderable: false },
                { targets: 11, orderable: false },
                { targets: 12, orderable: false }
            ],
            drawCallback: function () {
                // After each page draw: re-sync checkbox states
                syncCheckboxes();

                // Highlight and scroll to target lead once on load
                if (!window.hasScrolledToTarget) {
                    const hash = window.location.hash;
                    if (hash && hash.startsWith('#lead-')) {
                        const targetEl = $(hash);
                        if (targetEl.length > 0) {
                            window.hasScrolledToTarget = true;
                            $('html, body').animate({
                                scrollTop: targetEl.offset().top - 100
                            }, 500);
                        }
                    }
                }

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

                // Re-bind delete buttons (Bootstrap dropdown blocks event delegation)
                $('#serverSideLeadTable .removeBtnCust').off('click.leadDelete').on('click.leadDelete', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const itemId    = $(this).data('id');
                    const itemName  = $(this).data('name');
                    const actionUrl = $(this).attr('data-action');
                    // close dropdown first
                    $(this).closest('.dropdown').find('[data-bs-toggle="dropdown"]').dropdown('hide');
                    setTimeout(function () {
                        Swal.fire({
                            title: 'Are you sure?',
                            text: `You are about to delete "${itemName}". This action cannot be undone!`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, delete it!'
                        }).then(function (result) {
                            if (result.isConfirmed) {
                                const form = $('<form>', {
                                    method: 'POST',
                                    action: base_url + '/' + actionUrl
                                });
                                form.append($('<input>', { type: 'hidden', name: 'id', value: itemId }));
                                $('body').append(form);
                                form.submit();
                            }
                        });
                    }, 200);
                });
            }
        });

        dtTable.on('page.dt', function () {
            const info = dtTable.page.info();
            const page = info.page + 1;
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            window.history.replaceState(null, '', url.toString());
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
        'contacted': 'bg-primary-subtle text-primary',
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

        const jt = lead.job_title || lead.designation || '';
        const co = lead.company_name || lead.company || '';
        let jobCompanyStr = '';
        if (jt || co) {
            jobCompanyStr = `<div class="text-muted small mt-1"><i class="bi bi-briefcase me-1"></i>${jt.replace(/</g,'&lt;').replace(/>/g,'&gt;')}${jt && co ? ' at ' : ''}<strong>${co.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</strong></div>`;
        }
        let bestTimeStr = '';
        if (lead.when_is_the_best_time_to_contact_you) {
            bestTimeStr = `<div class="text-info small mt-1"><i class="bi bi-clock me-1"></i>Best Contact Time: ${lead.when_is_the_best_time_to_contact_you.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</div>`;
        }

        let agentBadge = '';
        if (lead.agent_name) {
            const agentNameClean = lead.agent_name.replace(/</g,'&lt;').replace(/>/g,'&gt;');
            const assignedAtStr = lead.assigned_at_formatted || (lead.assigned_at ? new Date(lead.assigned_at).toLocaleDateString('en-GB', {day:'2-digit',month:'short',year:'numeric'}) : '');
            agentBadge = `<div>
                <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1" style="font-size:.75rem;">
                    <i class="bi bi-person me-1"></i>${agentNameClean}
                </span>
                ${assignedAtStr ? `<div class="text-muted" style="font-size:11px; margin-top:3px;"><i class="bi bi-calendar-event me-1"></i>Assigned Date: ${assignedAtStr}</div>` : ''}
            </div>`;
        } else {
            agentBadge = `<span class="badge rounded-pill px-2 py-1 text-danger" style="font-size:.75rem; background-color: #fde8e8; border: 1px solid #f8b4b4;">
                <i class="bi bi-person me-1"></i>No Agent Assigned
            </span>`;
        }

        return `
        <div class="col-12 col-sm-6">
          <div id="lead-${lead.id}" class="lead-card h-100" data-id="${lead.id}">
            <div class="lead-card-header d-flex justify-content-between align-items-start gap-1">
              <div class="flex-grow-1 overflow-hidden">
                <div class="d-flex align-items-center gap-1">
                  <input type="checkbox" class="selectedIdMobile me-1" data-id="${lead.id}">
                  <span class="lead-name text-truncate">${name}</span>
                </div>
                ${jobCompanyStr}
                ${bestTimeStr}
                <div class="lead-phone text-truncate small">${lead.phone || ''}</div>
                ${lead.address ? `<div class="mt-1"><i class="bi bi-geo-alt-fill text-muted me-1" style="font-size:11px"></i><span class="fs-11 text-muted">${lead.address.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</span></div>` : ''}
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
              ${agentBadge}
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

<!-- Bookings CSV Import Modal -->
<div class="modal fade" id="importBookingsCsvModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Bookings from CSV</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>

            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/bulk-bookings-import-csv"
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

                <div class="mt-15 p-2 rounded text-muted" style="font-size: 12px; max-height: 200px; overflow-y: auto;">
                    <strong>Required CSV Headers:</strong><br>
                    lead_phone, project_name, flat_name, agreement_value, discount_amount, booking_date, booking_status, sales_agent, approved_amount, service_fee_balance, landshare_status, payment_status, attachment_image
                    <br><br>
                    <em>Note: lead_phone, project_name, and flat_name are used to lookup IDs in the database. If the `sales_agent` is provided by name or ID and not found, it is saved as an old agent. If approved_amount > 0, approval_status becomes 'approved'.</em>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payments CSV Import Modal -->
<div class="modal fade" id="importPaymentsCsvModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Payments from CSV</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>

            <div class="modal-body">
                <form id="importPaymentsCsvForm"
                    action="<?= $GLOBALS['base_url'] ?>/bulk-payments-import-csv"
                    method="post"
                    enctype="multipart/form-data">

                    <div class="from__input-box mb-20">
                        <div class="form__input-title">
                            <label>Upload CSV File <span>*</span></label>
                        </div>
                        <div class="form__input">
                            <input type="file"
                                id="importPaymentsCsvFile"
                                name="csv_file"
                                class="form-control"
                                accept=".csv"
                                required>
                        </div>
                    </div>

                    <div class="submit__btn text-center">
                        <button class="btn btn-primary">Import Payments</button>
                    </div>
                </form>

                <div class="mt-15 p-2 rounded text-muted" style="font-size: 12px; max-height: 200px; overflow-y: auto;">
                    <strong>Required CSV Headers:</strong><br>
                    lead_number, project_name, flat_name, payment_type, amount, payment_method, payment_date, total_emi_no, transaction_ref, bank_name, cheque_no, online_type, attachment_image
                    <br><br>
                    <em>Note: <code>lead_number</code>, <code>project_name</code>, and <code>flat_name</code> are used to find the `booking_id`. Each imported payment will be automatically verified, and if the total paid `booking_money` covers the expected amount, the booking status and flat `is_booked` will be updated to completed. `payment_type` refers to the type of payment (e.g., booking_money, registration).</em>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payments CSV Import Confirmation Modal -->
<div class="modal fade" id="importPaymentsConfirmModal" tabindex="-1" aria-labelledby="importPaymentsConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:14px; overflow:hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #0d6efd, #0b5ed7); color: #fff;">
                <h5 class="modal-title fw-bold" id="importPaymentsConfirmModalLabel">
                    <i class="bi bi-file-earmark-check me-2"></i>Confirm Payments CSV Import
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="background: #fafafa; max-height: 60vh; overflow-y: auto;">
                <div id="confirmPaymentsAlert" class="alert d-none mb-3"></div>
                <div class="table-responsive rounded border shadow-sm bg-white">
                    <table class="table table-sm table-hover mb-0" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width: 50px;">Row</th>
                                <th>Lead Number</th>
                                <th>Project Name</th>
                                <th>Flat Name</th>
                                <th>Payment Type</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="confirmPaymentsTableBody">
                            <!-- Rows dynamically populated -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer" style="background: #f8f9fa;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="submitPaymentsImportConfirmed" class="btn btn-success btn-sm">
                    <i class="bi bi-check-circle me-1"></i>Confirm & Import
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ Lead Lists — Duplicate Leads Modal ══════════════════════════════════ -->
<?php if (!empty($dupGroups)): ?>
<style>
.swal2-container {
    z-index: 99999 !important;
}
</style>
<div class="modal fade" id="leadDupModal" tabindex="-1" aria-labelledby="leadDupModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:14px;overflow:hidden;">

      <!-- Header -->
      <div class="modal-header" style="background:linear-gradient(135deg,#dc3545,#b02a37);color:#fff;">
        <div>
          <h5 class="modal-title fw-bold mb-0" id="leadDupModalLabel">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>Duplicate Lead Assignments
          </h5>
          <small style="opacity:.85;font-size:.78rem;">
            My team leads (assigned to me) whose phone/email also exists in other assignments
          </small>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <!-- Filter Bar -->
      <div class="modal-body pb-0 pt-3" style="background:#fafafa;">
        <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
          <label class="fw-semibold small text-muted mb-0">Filter by Assigned Member:</label>
          <select id="leadDupMemberFilter" class="form-select form-select-sm" style="max-width:240px;">
            <option value="">All Members</option>
            <?php
              $ldAllAssignees = [];
              foreach ($dupGroups as $grp) {
                  foreach ($grp['rows'] as $r) {
                      $n = $r['assigned_to_name'] ?? 'Unknown';
                      $ldAllAssignees[$n] = true;
                  }
              }
              ksort($ldAllAssignees);
              foreach (array_keys($ldAllAssignees) as $aName):
            ?>
            <option value="<?= htmlspecialchars($aName) ?>"><?= htmlspecialchars($aName) ?></option>
            <?php endforeach; ?>
          </select>
          <span class="badge bg-danger rounded-pill ms-auto"><?= $dupCount ?> Total Leads</span>
        </div>
      </div>

      <!-- Body -->
      <div class="modal-body pt-2">
        <div id="leadDupContainer">
        <?php foreach ($dupGroups as $grp): ?>
          <div class="ld-dup-group mb-4"
               data-members="<?= htmlspecialchars(implode('|', array_column($grp['rows'], 'assigned_to_name'))) ?>">

            <!-- Group header -->
            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
              <span class="badge bg-danger rounded-pill" style="font-size:.7rem;">
                <?= count($grp['rows']) ?> assignment<?= count($grp['rows']) > 1 ? 's' : '' ?>
              </span>
              <span class="fw-bold text-dark" style="font-size:.9rem;">
                <?= htmlspecialchars($grp['name']) ?>
              </span>
              <?php if (!empty($grp['label'])): ?>
              <span class="text-muted small">&mdash; <?= htmlspecialchars($grp['label']) ?></span>
              <?php endif; ?>
            </div>

            <!-- Assignment table -->
            <div class="table-responsive rounded border shadow-sm">
              <table class="table table-sm mb-0" style="font-size:.82rem;">
                <thead style="background:#f8d7da;">
                  <tr>
                    <th class="ps-3">#</th>
                    <th>Lead Name</th>
                    <th>Campaign / Project</th>
                    <th>Phone / Email</th>
                    <th>Assigned To (Member)</th>
                    <th>Team</th>
                    <th>Assigned Date</th>
                    <th class="pe-3 text-center">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                    // Find the index of the oldest assignment
                    $oldestIndex = 0;
                    $minTime = null;
                    foreach ($grp['rows'] as $idx => $r) {
                        $time = !empty($r['assigned_date']) ? strtotime($r['assigned_date']) : PHP_INT_MAX;
                        if ($minTime === null || $time < $minTime) {
                            $minTime = $time;
                            $oldestIndex = $idx;
                        }
                    }
                  ?>
                  <?php foreach ($grp['rows'] as $ri => $r): ?>
                  <tr class="ld-dup-row" data-member="<?= htmlspecialchars($r['assigned_to_name'] ?? '') ?>">
                    <td class="ps-3 text-muted"><?= $ri + 1 ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($r['full_name'] ?? 'N/A') ?></td>
                    <td>
                      <div class="mb-1">
                        <span class="badge" style="background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;font-size:.72rem;">
                          <i class="bi bi-building me-1"></i>Project: <?= htmlspecialchars($r['project_name'] ?? 'No Project') ?>
                        </span>
                      </div>
                      <div>
                        <span class="badge" style="background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;font-size:.72rem;">
                          <i class="bi bi-megaphone me-1"></i>Campaign: <?= htmlspecialchars($r['campaign_name'] ?? 'N/A') ?>
                        </span>
                      </div>
                    </td>
                    <td>
                      <?php if (!empty($r['phone_number'])): ?>
                        <div><i class="bi bi-telephone-fill text-muted me-1" style="font-size:.73rem;"></i><?= htmlspecialchars($r['phone_number']) ?></div>
                      <?php endif; ?>
                      <?php if (!empty($r['email'])): ?>
                        <div class="text-muted small"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($r['email']) ?></div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <span class="badge rounded-pill d-inline-flex align-items-center gap-1"
                            style="background:#e7f3ff;color:#0d6efd;border:1px solid #b6d4fe;font-size:.75rem;padding:.3rem .6rem;">
                        <i class="bi bi-person-fill"></i>
                        <?= htmlspecialchars($r['assigned_to_name'] ?? 'Unknown') ?>
                      </span>
                    </td>
                    <td>
                      <?php if (!empty($r['team_name'])): ?>
                      <span class="badge" style="background:#e8f4fd;color:#0369a1;border:1px solid #bae6fd;font-size:.72rem;">
                        <i class="bi bi-people-fill me-1"></i><?= htmlspecialchars($r['team_name']) ?>
                      </span>
                      <?php else: ?><span class="text-muted">&mdash;</span><?php endif; ?>
                    </td>
                    <td class="text-muted">
                      <?php if (!empty($r['assigned_date'])): ?>
                        <div class="fw-semibold text-dark" style="font-size:.8rem;"><?= date('M d, Y', strtotime($r['assigned_date'])) ?></div>
                        <small><?= date('h:i A', strtotime($r['assigned_date'])) ?></small>
                        <?php if ($ri === $oldestIndex): ?>
                          <div class="mt-1">
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.65rem; font-weight: bold;">
                              <i class="bi bi-clock-history me-1"></i>First Assigned
                            </span>
                          </div>
                        <?php endif; ?>
                      <?php else: ?>&mdash;<?php endif; ?>
                    </td>
                    <td class="pe-3 text-center">
                      <button class="btn btn-xs btn-outline-primary py-1 px-2 fw-semibold assign-dup-all-btn"
                              onclick="handleReassignGroup(this, event)"
                              data-user-id="<?= $r['assigned_id'] ?>"
                              data-user-name="<?= htmlspecialchars($r['assigned_to_name'] ?? '') ?>"
                              data-phone-or-email="<?= htmlspecialchars($grp['label']) ?>"
                              data-lead-name="<?= htmlspecialchars($grp['name']) ?>"
                              style="font-size:0.75rem;">
                        Assign Group Here
                      </button>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endforeach; ?>

        <!-- Empty state -->
        <div id="leadDupNoResult" class="text-center py-5 d-none">
          <i class="bi bi-person-x fs-2 text-muted d-block mb-2"></i>
          <div class="text-muted">No duplicate leads found for selected member.</div>
        </div>
        </div>
      </div>

      <div class="modal-footer" style="background:#f8f9fa;">
        <small class="text-muted me-auto">Showing leads from your team that are also assigned elsewhere</small>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var filterEl = document.getElementById('leadDupMemberFilter');
    if (filterEl) {
        filterEl.addEventListener('change', function () {
            var selected = this.value.toLowerCase();
            var anyVisible = false;
            document.querySelectorAll('.ld-dup-group').forEach(function (grp) {
                var members = (grp.getAttribute('data-members') || '').toLowerCase();
                var show = !selected || members.includes(selected);
                grp.style.display = show ? '' : 'none';
                if (show) anyVisible = true;
            });
            var noRes = document.getElementById('leadDupNoResult');
            if (noRes) noRes.classList.toggle('d-none', anyVisible);
        });
    }
});

// Global function for assigning duplicate lead group
window.handleReassignGroup = function (btn, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    var targetUserId = btn.getAttribute('data-user-id');
    var targetUserName = btn.getAttribute('data-user-name');
    var phoneOrEmail = btn.getAttribute('data-phone-or-email');
    var leadName = btn.getAttribute('data-lead-name');

    Swal.fire({
        title: 'Assign Duplicate Group',
        text: 'Are you sure you want to assign all duplicate leads for "' + leadName + '" (' + phoneOrEmail + ') to ' + targetUserName + '?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Assign All',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            var baseUrl = window.base_url || '<?= $GLOBALS['base_url'] ?>';
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = baseUrl + '/reassign-duplicate-group';

            var input1 = document.createElement('input');
            input1.type = 'hidden';
            input1.name = 'target_user_id';
            input1.value = targetUserId;
            form.appendChild(input1);

            var input2 = document.createElement('input');
            input2.type = 'hidden';
            input2.name = 'phone_or_email';
            input2.value = phoneOrEmail;
            form.appendChild(input2);

            document.body.appendChild(form);
            form.submit();
        }
    });
};
</script>
<?php endif; ?>

<!-- Standalone Script for Payments CSV Import Confirmation -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const importForm = document.getElementById('importPaymentsCsvForm');
    const fileInput = document.getElementById('importPaymentsCsvFile');
    const confirmModalEl = document.getElementById('importPaymentsConfirmModal');
    let confirmModal = null;
    let isConfirmed = false;

    if (importForm && fileInput) {
        importForm.addEventListener('submit', function (e) {
            if (isConfirmed) {
                return; // Let form submit
            }

            e.preventDefault();

            const file = fileInput.files[0];
            if (!file) {
                alert("Please select a CSV file first.");
                return;
            }

            // Create FormData to send file to server
            const formData = new FormData();
            formData.append('csv_file', file);

            // Show confirmation modal with spinner/loading state
            const tbody = document.getElementById('confirmPaymentsTableBody');
            tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted">Checking database records...</div></td></tr>';

            const alertEl = document.getElementById('confirmPaymentsAlert');
            alertEl.className = 'alert alert-info d-block';
            alertEl.innerHTML = '<i class="bi bi-info-circle-fill me-2"></i>Verifying CSV data against database...';

            const submitBtn = document.getElementById('submitPaymentsImportConfirmed');
            submitBtn.disabled = true;

            if (!confirmModal) {
                confirmModal = new bootstrap.Modal(confirmModalEl);
            }
            // Hide upload modal
            const uploadModalEl = document.getElementById('importPaymentsCsvModal');
            const uploadModal = bootstrap.Modal.getInstance(uploadModalEl);
            if (uploadModal) {
                uploadModal.hide();
            }
            confirmModal.show();

            fetch('<?= $GLOBALS['base_url'] ?>/bulk-payments-validate-csv', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text().then(text => {
                let resData;
                try {
                    resData = JSON.parse(text);
                } catch (e) {
                    console.error("Invalid JSON response:", text);
                    throw new Error("Server response error: " + (text ? text.replace(/<[^>]*>?/gm, '').trim().substring(0, 150) : 'Empty server response'));
                }
                return resData;
            }))
            .then(res => {
                if (!res.success) {
                    tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-danger fw-semibold">${escapeHTML(res.message || 'Validation failed')}</td></tr>`;
                    alertEl.className = 'alert alert-danger d-block';
                    alertEl.innerHTML = `<i class="bi bi-x-circle-fill me-2"></i><strong>Error:</strong> ${escapeHTML(res.message || 'Failed to validate CSV')}`;
                    submitBtn.disabled = true;
                    return;
                }

                tbody.innerHTML = '';
                let hasErrors = false;
                let rowCount = 0;

                res.rows.forEach((rowResult, idx) => {
                    rowCount++;
                    const rowData = rowResult.data;
                    const error = rowResult.error;
                    const valid = rowResult.valid;

                    if (!valid) {
                        hasErrors = true;
                    }

                    let typeBadge = '';
                    const pt = (rowData.payment_type || '').toLowerCase();
                    if (pt === 'booking_money') {
                        typeBadge = '<span class="badge bg-success-subtle text-success">Booking Money</span>';
                    } else if (pt === 'registration') {
                        typeBadge = '<span class="badge bg-info-subtle text-info">Registration</span>';
                    } else if (pt === 'land_share') {
                        typeBadge = '<span class="badge bg-warning-subtle text-dark">Land Share</span>';
                    } else if (pt === 'emi') {
                        typeBadge = '<span class="badge bg-primary-subtle text-primary">EMI</span>';
                    } else if (pt === 'development_charge') {
                        typeBadge = '<span class="badge bg-secondary-subtle text-dark">Development Charge</span>';
                    } else {
                        typeBadge = `<span class="badge bg-light text-dark border">${escapeHTML(rowData.payment_type || 'empty')}</span>`;
                    }

                    // Normalize method display
                    let methodDisplay = rowData.payment_method || 'cash';
                    const m = methodDisplay.toLowerCase();
                    if (m === 'bank_transfer' || m === 'bank transfer' || m === 'transfer') methodDisplay = 'bank';
                    else if (['bkash', 'nagad', 'rocket', 'mobile_banking', 'mobile banking'].includes(m)) methodDisplay = 'online';
                    else if (m === 'check') methodDisplay = 'cheque';

                    const statusHtml = !valid 
                        ? `<span class="text-danger fw-semibold"><i class="bi bi-x-circle-fill me-1"></i>Error: ${escapeHTML(error)}</span>`
                        : '<span class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Valid</span>';

                    const tr = document.createElement('tr');
                    if (!valid) {
                        tr.style.backgroundColor = '#fff5f5';
                    }
                    tr.innerHTML = `
                        <td class="ps-3 text-muted fw-bold">${idx + 1}</td>
                        <td><strong>${escapeHTML(rowData.lead_number)}</strong></td>
                        <td>${escapeHTML(rowData.project_name)}</td>
                        <td>${escapeHTML(rowData.flat_name)}</td>
                        <td>${typeBadge}</td>
                        <td><strong>${escapeHTML(rowData.amount)}</strong></td>
                        <td>${escapeHTML(methodDisplay)}</td>
                        <td>${escapeHTML(rowData.payment_date)}</td>
                        <td>${statusHtml}</td>
                    `;
                    tbody.appendChild(tr);
                });

                if (hasErrors) {
                    alertEl.className = 'alert alert-danger d-block';
                    alertEl.innerHTML = `<i class="bi bi-exclamation-octagon-fill me-2"></i><strong>Validation Failed:</strong> The CSV contains invalid rows or mismatching database records. Please correct the CSV and try again.`;
                    submitBtn.disabled = true;
                } else {
                    alertEl.className = 'alert alert-success d-block';
                    alertEl.innerHTML = `<i class="bi bi-check-circle-fill me-2"></i><strong>Ready to Import:</strong> All ${rowCount} rows are valid and match database bookings. Please verify the information below before importing.`;
                    submitBtn.disabled = false;
                }
            })
            .catch(err => {
                console.error("AJAX validation error:", err);
                tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-danger fw-semibold">${escapeHTML(err.message || 'Error verifying CSV file with the server.')}</td></tr>`;
                alertEl.className = 'alert alert-danger d-block';
                alertEl.innerHTML = `<i class="bi bi-x-circle-fill me-2"></i><strong>Error:</strong> ${escapeHTML(err.message || 'Failed to connect to verification server.')}`;
                submitBtn.disabled = true;
            });
        });

        document.getElementById('submitPaymentsImportConfirmed').addEventListener('click', function () {
            isConfirmed = true;
            importForm.submit();
        });
    }

    // Helper: RFC 4180 CSV parser
    function parseCSV(text) {
        let lines = [];
        let row = [""];
        let inQuotes = false;
        for (let i = 0; i < text.length; i++) {
            let c = text[i];
            let next = text[i+1];
            if (c === '"') {
                if (inQuotes && next === '"') {
                    row[row.length - 1] += '"';
                    i++;
                } else {
                    inQuotes = !inQuotes;
                }
            } else if (c === ',' && !inQuotes) {
                row.push('');
            } else if ((c === '\r' || c === '\n') && !inQuotes) {
                if (c === '\r' && next === '\n') {
                    i++;
                }
                lines.push(row);
                row = [''];
            } else {
                row[row.length - 1] += c;
            }
        }
        if (row.length > 1 || row[0] !== '') {
            lines.push(row);
        }
        return lines;
    }

    function escapeHTML(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;')
                  .replace(/</g, '&lt;')
                  .replace(/>/g, '&gt;')
                  .replace(/"/g, '&quot;')
                  .replace(/'/g, '&#039;');
    }
});
</script>


<!-- Breadcrumb -->
<div class="container-fluid mt-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Lead & Booking Management</li>
        </ol>
    </nav>
</div>

<!-- Main Content -->
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
</div>

<!-- Desktop View: Filters and Table -->
<div class="desktop-only">
    <form action="" method="get" class="d-none">
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
                            Search
                        </button>
                        <a href="<?= $GLOBALS['base_url'] ?>/lead-lists" class=" d-none btn btn-success  w-100">
                            Reset
                        </a>
                        <button
                            type="button"
                            class="btn btn-success btn-sm py-1 flex-grow-1"
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


    <!-- Filters -->
    <div class="filters">
        <form action="" method="get">
            <div class="row g-2 align-items-center flex-wrap">
                <div class="col-auto col-sm-6 col-md-auto">
                    <?php
                    $currentYear = date('Y');
                    $startYear   = $currentYear - 10;
                    $selectedYear = $slectedYear ?? '';
                    ?>
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
                <div class="col-auto col-sm-6 col-md-auto">
                    <select class="form-select form-select-sm filter-select">
                        <option>Project: All Projects</option>
                        <option>Green Valley</option>
                        <option>Oceanview Residences</option>
                    </select>
                </div>
                <div class="col-auto col-sm-6 col-md-auto d-none">
                    <select class="form-select form-select-sm filter-select">
                        <option>Status: All Status</option>
                        <option>Follow-Up</option>
                        <option>Visit</option>
                        <option>New</option>
                    </select>
                </div>


                <div class="col-auto ms-auto d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-outline-secondary btn-sm flex-grow-1 py-1">
                        Search
                    </button>
                    <a href="<?= $GLOBALS['base_url'] ?>/lead-lists" class="btn btn-primary btn-sm">
                            Reset
                    </a>

                    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#importCsvModal">
                        Import CSV
                    </button>
                    <a href="<?= $GLOBALS['base_url'] ?>/create-lead" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg"></i> Add Lead
                    </a>

                    <button class="d-none btn btn-outline-secondary btn-sm">Export</button>
                </div>
            </div>
    </div>
    </form>

</div>


<!-- Table -->
<div class="col-xxl-12">
    <div class="card__wrapper">
        <div class="table__wrapper table-responsive">
            <table class="table no-border-table mb-20 multiple_tables">
                <thead>
                    <tr class="table__title table__sort">
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
                            <td><?= $lead['created_at'] ? date('d F, Y', strtotime($lead['created_at'])) : '' ?></td>
                            <td><strong><?= $lead['full_name'] ?? '' ?></strong></td>
                            <td><?= $lead['phone_number'] ?? $lead['phone']  ?></td>
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
                                <div><?= formatDate($lead['next_follow_up_date'] ?? 'No Follow-up Date') ?></div>
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

<!-- Mobile View: Filter Button and Cards -->
<div class="mobile-only">
    <!-- Filter & Sort Button -->
    <button class="filter-button w-100">
        <i class="bi bi-funnel"></i>
        Filter & Sort
    </button>

    <!-- Lead Cards -->
    <div class="lead-card">
        <div class="lead-card-header">
            <div>
                <div class="lead-name">Alex Johnson</div>
                <div class="lead-phone">+1 234 567 8900</div>
            </div>
            <button class="btn-action">
                Follow Up <i class="bi bi-chevron-right"></i>
            </button>
        </div>
        <div class="lead-info-row">
            <div>
                <div class="info-label">Status</div>
                <span class="status-badge follow-up mt-1">
                    <i class="bi bi-circle-fill" style="font-size: 8px;"></i>
                    Follow-Up
                </span>
            </div>
            <div class="text-end">
                <div class="info-label">Booked</div>
                <div class="info-value">✓ Booked</div>
            </div>
        </div>
        <div class="lead-info-row">
            <div>
                <div class="info-label">Project</div>
                <div class="info-value">Green Valley</div>
            </div>
            <div>
                <i class="bi bi-calendar3 text-muted"></i>
            </div>
        </div>
        <div class="lead-info-row">
            <div>
                <div class="info-label">Next Follow-Up</div>
                <div class="info-value">Oct 12, 2022 | 10:00 AM</div>
            </div>
        </div>
    </div>

    <div class="lead-card">
        <div class="lead-card-header">
            <div>
                <div class="lead-name">Richard Lee</div>
                <div class="lead-phone">+1 345 678 9012</div>
            </div>
            <button class="btn-action">
                Follow Up <i class="bi bi-chevron-right"></i>
            </button>
        </div>
        <div class="lead-info-row">
            <div>
                <div class="info-label">Status</div>
                <span class="status-badge visit mt-1">
                    <i class="bi bi-circle-fill" style="font-size: 8px;"></i>
                    Visit
                </span>
            </div>
            <div class="text-end">
                <div class="info-label">Booked</div>
                <div class="info-value">✓ Booked</div>
            </div>
        </div>
        <div class="lead-info-row">
            <div>
                <div class="info-label">Project</div>
                <div class="info-value">Oceanview Residences</div>
            </div>
            <div>
                <i class="bi bi-calendar3 text-muted"></i>
            </div>
        </div>
        <div class="lead-info-row">
            <div>
                <div class="info-label">Next Follow-Up</div>
                <div class="info-value">Oct 11, 2022 | 03:30 PM</div>
            </div>
        </div>
    </div>

    <div class="lead-card">
        <div class="lead-card-header">
            <div>
                <div class="lead-name">Sofia Ahmed</div>
                <div class="lead-phone">+1 456 789 0123</div>
            </div>
            <button class="btn-action">
                Follow Up <i class="bi bi-chevron-right"></i>
            </button>
        </div>
        <div class="lead-info-row">
            <div>
                <div class="info-label">Status</div>
                <span class="status-badge new mt-1">
                    <i class="bi bi-circle-fill" style="font-size: 8px;"></i>
                    New
                </span>
            </div>
        </div>
        <div class="lead-info-row">
            <div>
                <div class="info-label">Project</div>
                <div class="info-value">City Heights</div>
            </div>
            <div>
                <i class="bi bi-calendar3 text-muted"></i>
            </div>
        </div>
        <div class="lead-info-row">
            <div>
                <div class="info-label">Next Follow-Up</div>
                <div class="info-value">Oct 11, 2022 | 12:00 PM</div>
            </div>
        </div>
    </div>

    <!-- FAB Button -->
    <button class="fab-button">
        <i class="bi bi-plus-lg"></i>
    </button>
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
<!-- Breadcrumb -->
<div class="container-fluid mt-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $GLOBALS['base_url'] ?>/my-dashboard" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Lead & Booking Management</li>
        </ol>
    </nav>
</div>

<!-- Stats Grid -->
<div class="row  mb-4 align-items-stretch">
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
                                <option value="<?= $year ?>" <?= $selectedYear == $year ? 'selected' : '' ?>><?= $year ?></option>
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
                                <option value="<?= $id ?>" <?= $selectedMonth == $id ? 'selected' : '' ?>><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Search & Reset Buttons Next to Select -->
                    <div class="col-auto d-flex align-items-center gap-2 flex-wrap">
                        <button type="submit" class="btn btn-outline-secondary btn-sm py-1">
                            Search
                        </button>
                        <a href="<?= $GLOBALS['base_url'] ?>/lead-lists" class="btn btn-primary btn-sm">
                            Reset
                        </a>
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

                                <th>Last Follow Up Project</th>
                                <th>Change Client Action</th>
                                <th>Next Follow-Up</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myAllleads as $key => $lead): ?>
                                <tr data-id="<?= $lead['id'] ?>">
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
                                    <td>

                                        <?php if (!empty($lead['project_name'])): ?>
                                            <span class="bd-badge bg-primary fw-semibold">
                                                <?= htmlspecialchars($lead['project_name']) ?>,
                                                <strong class="ms-2">Flat:</strong>
                                                <span><?= !empty($lead['flat_name']) ? htmlspecialchars($lead['flat_name']) : 'N/A' ?></span>
                                            </span>
                                        <?php else: ?>
                                            <span class="bd-badge bg-secondary fw-semibold">
                                                No Follow Data
                                            </span>
                                        <?php endif; ?>
                                    </td>
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
                                                class="btn btn-secondary d-flex align-items-center gap-2 justify-content-between" >
                                                <?php if (!empty($bookingCount[$lead['id']])): ?>
                                                    <span>Booked </span>
                                                    <span class="followup-badge">
                                                        <?= $bookingCount[$lead['id']] ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span>Booking </span>
                                                    <span class="followup-badge">0</span>
                                                <?php endif ?>
                                            </a>


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

                    </div>

                </div>
            </form>
        </div>
        <?php foreach ($myAllleads as $key => $lead): ?>
            <!-- Lead Card -->
            <div class="lead-card h-100 mb-0" data-id="<?= $lead['id'] ?>">
                <div class="lead-card-header d-flex justify-content-between align-items-start gap-1">
                    <div class="flex-grow-1 overflow-hidden">
                        <span class="lead-name text-truncate"><?= $lead['full_name'] ?></span>
                        <div class="lead-phone text-truncate small"><?= $lead['phone'] ?></div>
                    </div>
                </div>

                <!-- Status & Action -->
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
                        <a href="<?= $GLOBALS['base_url'] ?>/follow-up-leads/<?= $lead['id'] ?>" class="btn btn-primary btn-sm w-100 d-flex justify-content-between align-items-center px-2">
                            <span>Follow Up</span>
                            <?php if (!empty($followUpCounts[$lead['id']])): ?>
                                <span class="badge bg-white text-dark rounded-circle" style="font-size: 10px; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center;"><?= $followUpCounts[$lead['id']] ?></span>
                            <?php endif; ?>
                        </a>

                        <a href="<?= $GLOBALS['base_url'] ?>/booking-leads/<?= $lead['id'] ?>" class="btn btn-secondary btn-sm w-100 d-flex justify-content-between align-items-center px-2">
                            <span>Booking</span>
                            <?php if (!empty($bookingCount[$lead['id']])): ?>
                                <span class="badge bg-white text-dark rounded-circle" style="font-size: 10px; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center;"><?= $bookingCount[$lead['id']] ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
                <!-- Project & Last Follow-up -->
                <div class="mt-2">
                    <div class="info-label ">Project Info</div>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <span class="project-badge mb-0">
                            <i class="bi bi-building"></i>
                            <?= truncateText($displayName, 15) ?>
                        </span>
                        <?php if (mb_strlen($plainText) > 15): ?>
                            <button type="button"
                                id="viewCampaignBtn<?= $lead['id'] ?>"
                                class="btn btn-sm btn-outline-primary w-auto px-1 py-0"
                                data-description="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>"
                                data-bs-toggle="modal"
                                data-bs-target="#viewProjectCampaignName"
                                title="View Project">View
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="info-label mt-2">Follow-up Project</div>
                    <div class="small mt-1">
                        <?php if (!empty($lead['project_name'])): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1" style="font-size: 0.65rem;">
                                <?= htmlspecialchars($lead['project_name']) ?> (<?= !empty($lead['flat_name']) ? htmlspecialchars($lead['flat_name']) : 'N/A' ?>)
                            </span>
                        <?php else: ?>
                            <span class="text-muted italic small" style="font-size: 0.65rem;">No Follow Data</span>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Next Follow-Up -->
                <div class="d-flex align-items-center gap-2 mt-3">
                    <div class="info-label flex-shrink-0">Next Follow-Up</div>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-2" style="font-size: 0.75rem;">
                        <i class="bi bi-calendar2-check me-1"></i>
                        <?= formatDate($lead['next_follow_up_date'] ?? 'No Schedule') ?>
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
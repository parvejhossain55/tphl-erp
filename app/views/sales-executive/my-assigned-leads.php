<!-- Custom Styles for Table and Responsiveness -->
<style>
    /* Table Layout Fixes */
    .table__wrapper.table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .multiple_tables {
        width: 100%;
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
    }

    .multiple_tables th {
        white-space: nowrap;
        background: #f8f9fa;
        padding: 12px 15px;
        font-size: 0.85rem;
        font-weight: 700;
        color: #333;
        border-bottom: 2px solid #dee2e6;
    }

    .multiple_tables td {
        vertical-align: middle;
        padding: 10px 15px;
        font-size: 0.85rem;
        border-bottom: 1px solid #eee;
    }

    /* Column Specific Widths */
    .multiple_tables th:nth-child(1),
    .multiple_tables td:nth-child(1) {
        min-width: 110px !important;
    }

    /* Date */
    .multiple_tables th:nth-child(2),
    .multiple_tables td:nth-child(2) {
        min-width: 150px !important;
    }

    /* Name */
    .multiple_tables th:nth-child(3),
    .multiple_tables td:nth-child(3) {
        min-width: 130px !important;
    }

    /* Phone */
    .multiple_tables th:nth-child(4),
    .multiple_tables td:nth-child(4) {
        min-width: 180px !important;
    }

    /* Project Campaign */
    .multiple_tables th:nth-child(5),
    .multiple_tables td:nth-child(5) {
        min-width: 220px !important;
    }

    /* Last Follow Project */
    .multiple_tables th:nth-child(6),
    .multiple_tables td:nth-child(6) {
        min-width: 150px !important;
    }

    /* Action Dropdown */
    .multiple_tables th:nth-child(7),
    .multiple_tables td:nth-child(7) {
        min-width: 150px !important;
    }

    /* Communicate Type */
    .multiple_tables th:nth-child(8),
    .multiple_tables td:nth-child(8) {
        min-width: 200px !important;
    }

    /* Note */
    .multiple_tables th:nth-child(9),
    .multiple_tables td:nth-child(9) {
        min-width: 130px !important;
    }

    /* Next Follow Up */
    .multiple_tables th:nth-child(10),
    .multiple_tables td:nth-child(10) {
        min-width: 180px !important;
    }

    /* Action Buttons */

    /* Badge and Note Styles Refinement */
    .bd-badge-custom {
        display: block;
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 0.75rem;
        line-height: 1.4;
        background-color: #0d894f;
        color: #fff;
    }

    .bd-badge-secondary-custom {
        background-color: #6c757d;
        color: #fff;
    }

    .note-wrapper {
        display: flex;
        gap: 5px;
        align-items: center;
    }

    .call-note {
        flex: 1;
        min-height: 38px;
        max-height: 38px;
        font-size: 0.8rem;
        padding: 5px 8px;
        border: 1px solid #ddd;
        border-radius: 5px;
        resize: none;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .call-note:focus {
        border-color: #22c55e;
        box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
        outline: none;
        min-height: 80px;
        max-height: 200px;
    }

    .note-save-btn {
        padding: 5px 10px;
        font-size: 0.75rem;
        height: 38px;
    }

    /* Next Follow-up Badge */
    .next-followup-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background-color: #e0f2fe;
        color: #075985;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        white-space: nowrap;
    }

    /* Mobile Improvements */
    @media (max-width: 768px) {

        .multiple_tables td,
        .multiple_tables th {
            padding: 8px 10px;
            font-size: 0.8rem;
        }

        .lead-card {
            border-left: 4px solid #22c55e;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }
    }
</style>

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

                    <!-- Call Status Select -->
                    <div class="col-auto col-sm-6 col-md-auto">
                        <select class="form-select form-select-sm" name="call_status">
                            <option value="">Call Status: All</option>
                            <option value="not_called" <?= ($selectedCallStatus ?? '') == 'not_called' ? 'selected' : '' ?>>Not Called</option>
                            <option value="called" <?= ($selectedCallStatus ?? '') == 'called' ? 'selected' : '' ?>>Called</option>
                            <option value="received" <?= ($selectedCallStatus ?? '') == 'received' ? 'selected' : '' ?>>Received</option>
                            <option value="not_received" <?= ($selectedCallStatus ?? '') == 'not_received' ? 'selected' : '' ?>>Not Received</option>
                        </select>
                    </div>

                    <!-- Search & Reset Buttons Next to Select -->
                    <div class="col-auto d-flex align-items-center gap-2 flex-wrap">
                        <button type="submit" class="btn btn-outline-secondary btn-sm py-1">
                            Search
                        </button>
                        <a href="<?= $GLOBALS['base_url'] ?>/my-all-assigned-leads" class="btn btn-primary btn-sm">
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
                                <th style="min-width:60px;">
                                    <input type="checkbox" id="selectAllLeads" title="Select All">
                                    S/L
                                </th>
                                <th style="min-width: 120px;">Lead Date</th>
                                <th style="min-width: 160px;">Lead Name</th>
                                <th style="min-width: 180px;">Project Campaign</th>

                                <th style="min-width: 200px;">Last Follow Up Project</th>
                                <th style="min-width: 160px;">Change Client Action</th>
                                <th style="min-width: 160px;" class="text-center">Communicate Type</th>
                                <th style="min-width: 250px;" class="text-center">Communicate Note</th>
                                <th style="min-width: 140px;">Next Follow-Up</th>
                                <th style="min-width: 180px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myAllleads as $key => $lead): ?>
                                <tr data-id="<?= $lead['id'] ?>">
                                    <td>
                                        <input type="checkbox" class="me-2 selectedId" data-id="<?= $lead['id'] ?>">
                                        <?= $key + 1 ?>
                                    </td>
                                    <td><?= $lead['created_at'] ? date('d F, Y', strtotime($lead['created_at'])) : '' ?></td>
                                    <td><strong><?= $lead['full_name'] ?? '' ?></strong>
                                        <?php
                                        $phone = $lead['phone_number'] ?? $lead['phone'] ?? '';
                                        $phone = preg_replace('/[^0-9]/', '', $phone);

                                        if (substr($phone, 0, 2) === '01') {
                                            $phone = '880' . substr($phone, 1);
                                        }
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
                                    <td>

                                        <?php if (!empty($lead['project_name'])): ?>
                                            <div class="bd-badge-custom">
                                                <div class="fw-bold"><?= htmlspecialchars($lead['project_name']) ?></div>
                                                <div class="mt-1 small" style="opacity: 0.9; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 2px;">
                                                    <strong>Flat:</strong> <?= !empty($lead['flat_name']) ? htmlspecialchars($lead['flat_name']) : 'N/A' ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="bd-badge-custom bd-badge-secondary-custom">
                                                No Follow Data
                                            </div>
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
                                    <!-- Communicate Type -->
                                    <?php
                                    $status = $lead['call_status'] ?? 'not_called';

                                    $statusClass = '';
                                    if ($status == 'received') {
                                        $statusClass = 'bg-received';
                                    } elseif ($status == 'not_received') {
                                        $statusClass = 'bg-not-received';
                                    }
                                    ?>

                                    <td class="call-status-cell">
                                        <select
                                            class="call-status <?= $statusClass ?>"
                                            data-id="<?= $lead['id'] ?>">

                                            <option value="not_called" <?= $status == 'not_called' ? 'selected' : '' ?>>Not Called</option>
                                            <option value="called" <?= $status == 'called' ? 'selected' : '' ?>>Called</option>
                                            <option value="received" <?= $status == 'received' ? 'selected' : '' ?>>Received</option>
                                            <option value="not_received" <?= $status == 'not_received' ? 'selected' : '' ?>>Not Received</option>

                                        </select>
                                    </td>

                                    <!-- Interactive Note -->
                                    <td class="note-cell">
                                        <div class="note-wrapper">
                                            <textarea
                                                class="call-note"
                                                data-id="<?= $lead['id'] ?>"
                                                placeholder="Click to add note..."><?= htmlspecialchars($lead['call_note'] ?? '') ?></textarea>
                                            <button type="button" class="btn btn-sm btn-success note-save-btn" data-id="<?= $lead['id'] ?>">Save</button>
                                        </div>
                                    </td>

                                    <!-- Next Follow-up -->
                                    <td>
                                        <div class="next-followup-badge">
                                            <i class="bi bi-calendar-check me-1"></i>
                                            <?= formatDate($lead['next_follow_up_date'] ?? 'No Schedule') ?>
                                        </div>
                                    </td>



                                    <td>
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
                        <a href="<?= $GLOBALS['base_url'] ?>/my-all-assigned-leads" class="btn btn-primary btn-sm flex-grow-1">Reset</a>

                    </div>

                </div>
            </form>
        </div>
        <?php foreach ($myAllleads as $key => $lead): ?>
            <!-- Lead Card -->
            <div class="lead-card  mb-3" data-id="<?= $lead['id'] ?>">
                <div class="lead-card-header d-flex justify-content-between align-items-start gap-1">
                    <div class="flex-grow-1 overflow-hidden">
                        <span class="lead-name text-truncate"><?= $lead['full_name'] ?></span>
                        <div class="lead-phone text-truncate small"><?= $lead['phone'] ??  $lead['phone_number']  ?></div>
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
                                <span class="badge bg-white text-dark rounded-circle" style="font-size: 10px; width: 18px; height: 18px;  align-items: center; justify-content: center;"><?= $followUpCounts[$lead['id']] ?></span>
                            <?php endif; ?>
                        </a>

                        <a href="<?= $GLOBALS['base_url'] ?>/booking-leads/<?= $lead['id'] ?>" class="btn btn-secondary btn-sm w-100 d-flex justify-content-between align-items-center px-2">
                            <span>Booking</span>
                            <?php if (!empty($bookingCount[$lead['id']])): ?>
                                <span class="badge bg-white text-dark rounded-circle" style="font-size: 10px; width: 18px; height: 18px;  align-items: center; justify-content: center;"><?= $bookingCount[$lead['id']] ?></span>
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

                    <!-- Call Status (Communicate Type) -->
                    <div class="mt-2">
                        <label class="info-label d-block mb-1">Call Status (Communicate Type)</label>
                        <select class="call-status w-100" data-id="<?= $lead['id'] ?>">
                            <option value="not_called" <?= ($lead['call_status'] ?? 'not_called') == 'not_called' ? 'selected' : '' ?>>Not Called</option>
                            <option value="called" <?= ($lead['call_status'] ?? '') == 'called' ? 'selected' : '' ?>>Called</option>
                            <option value="received" <?= ($lead['call_status'] ?? '') == 'received' ? 'selected' : '' ?>>Received</option>
                            <option value="not_received" <?= ($lead['call_status'] ?? '') == 'not_received' ? 'selected' : '' ?>>Not Received</option>
                        </select>
                    </div>

                    <div class="info-label mt-2">Last Follow-up Project</div>
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

                <!-- Last Contacted Note -->
                <div class="mt-2">
                    <label class="info-label d-block mb-1">Note</label>
                    <textarea class="form-control call-note" data-id="<?= $lead['id'] ?>" placeholder="Add note..."><?= htmlspecialchars($lead['call_note'] ?? '') ?></textarea>
                    <button type="button" class="btn btn-sm btn-success mt-1 note-save-btn" data-id="<?= $lead['id'] ?>">Save</button>
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

    // // Optional: Disable drag
    // document.addEventListener('dragstart', function(e) {
    //     e.preventDefault();
    // });


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

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.selectedId');
            checkboxes.forEach(cb => cb.checked = this.checked);

            if (this.checked) {
                if (assignCard) assignCard.style.display = 'flex';
            } else {
                const anyChecked = Array.from(document.querySelectorAll('.selectedId')).some(c => c.checked);
                if (assignCard) assignCard.style.display = anyChecked ? 'flex' : 'none';
            }
        });
    }

    document.querySelectorAll('.selectedId').forEach(cb => {
        cb.addEventListener('change', function() {
            const anyChecked = Array.from(document.querySelectorAll('.selectedId')).some(c => c.checked);
            if (assignCard) assignCard.style.display = anyChecked ? 'flex' : 'none';

            if (selectAll) {
                const allChecked = Array.from(document.querySelectorAll('.selectedId')).every(c => c.checked);
                selectAll.checked = allChecked;
            }
        });
    });

    // --- Interactive Column AJAX Logic ---
    // Update call status (Communicate Type)
    document.querySelectorAll('.call-status').forEach(select => {
        select.addEventListener('change', function() {
            fetch(base_url + '/update-call-status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'lead_id=' + this.dataset.id + '&call_status=' + this.value
            });
        });
    });

    // Save notes via button
    document.querySelectorAll('.note-save-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const leadId = this.dataset.id;
            const textarea = document.querySelector('.call-note[data-id="' + leadId + '"]');
            saveNote(leadId, textarea.value, textarea);
        });
    });

    // Save notes via Enter key
    document.querySelectorAll('.call-note').forEach(textarea => {
        textarea.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                saveNote(this.dataset.id, this.value, this);
            }
        });
    });

    function saveNote(leadId, note, el) {
        fetch(base_url + '/update-call-notes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'lead_id=' + encodeURIComponent(leadId) + '&call_note=' + encodeURIComponent(note)
            })
            .then(res => res.text())
            .then(() => {
                el.style.borderColor = '#198754'; // success hint
                setTimeout(() => el.style.borderColor = '#ddd', 800);
            })
            .catch(err => {
                console.error('Error saving note:', err);
                el.style.borderColor = '#dc3545'; // error hint
            });
    }
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
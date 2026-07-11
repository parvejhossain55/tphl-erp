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

    /* Stat Card Styles */
    .stat-card {
        border-radius: 12px;
        padding: 0 !important;
        margin-bottom: 0 !important;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
    }
    .stat-value {
        font-size: 1.5rem;
        font-weight: bold;
        margin: 0.2rem 0;
    }
    .stat-label {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        color: #718096;
    }
    a.stat-card-link {
        display: block;
        text-decoration: none;
        color: inherit;
        border-radius: 15px;
    }
    a.stat-card-link .stat-card {
        position: relative;
        border: 1.5px solid transparent !important;
        transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    }
    a.stat-card-link:hover .stat-card {
        transform: translateY(-6px);
        box-shadow: 0 14px 30px rgba(220,53,69,0.18) !important;
    }
    a.stat-card-link .card-nav-arrow {
        position: absolute;
        top: 10px; right: 12px;
        font-size: 0.65rem;
        color: #bbb;
        opacity: 0;
        transition: opacity 0.2s, transform 0.2s;
    }
    a.stat-card-link:hover .card-nav-arrow {
        opacity: 1;
        transform: translateX(3px);
    }
    .card-hint {
        font-size: 10px;
        color: #a0aec0;
        display: block;
        margin-top: 2px;
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
    <!-- Missing Follow-ups Stats Card -->
    <div class="col-12 col-md-4 mb-3">
        <a href="<?= $GLOBALS['base_url'] ?>/my-all-assigned-leads?filter=missing_followup" class="stat-card-link">
            <div class="card__wrapper stat-card shadow-sm <?= ($isFilterMissing ?? false) ? 'border-danger' : '' ?>" style="background: linear-gradient(135deg, #fff5f5 0%, #ffe3e3 100%); border: 1.5px solid <?= ($isFilterMissing ?? false) ? '#dc3545' : '#fecaca' ?> !important; min-height: 85px;">
                <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right text-danger"></i></span>
                <div class="card-body d-flex justify-content-between align-items-center p-2 px-3">
                    <div>
                        <p class="stat-label mb-1 text-danger"><i class="fa-solid fa-bell-slash me-1"></i> Missing Follow-Ups</p>
                        <h2 class="stat-value text-danger mb-1" style="font-size: 1.6rem; font-weight: 800;"><?= $missingFollowUpCount ?? 0 ?></h2>
                        <span class="card-hint text-danger" style="font-size: 10px; margin-top: 0;">Click to view missing follow-ups →</span>
                    </div>
                    <div class="card__icon text-danger" style="opacity: 0.15; font-size: 1.8rem;"><i class="fa-solid fa-bell-slash"></i></div>
                </div>
            </div>
        </a>
    </div>

    <!-- Duplicate Assigned Leads Card (Team-based) -->
    <?php if (!empty($duplicateGroups)): ?>
    <div class="col-12 col-md-8 mb-3">
        <div class="alert alert-danger border-0 shadow-sm mb-0 p-2 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2 h-100"
             style="background:linear-gradient(135deg,#fff5f5,#ffe0e0);border-left:4px solid #dc3545 !important;border-radius:10px;">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center"
                     style="width:36px;height:36px;background:#dc3545;flex-shrink:0;">
                    <i class="bi bi-exclamation-triangle-fill text-white" style="font-size:1rem;"></i>
                </div>
                <div>
                    <div class="fw-bold text-danger" style="font-size:.9rem; line-height: 1.2;">
                        &#9888; Duplicate Leads &mdash; In Your Team &amp; Also in Other Teams
                    </div>
                    <div class="text-muted" style="font-size:.75rem;">
                        <strong><?= $duplicateCount ?></strong> leads from your team are also assigned in
                        <span class="fw-semibold text-danger">other teams</span> or
                        <span class="fw-semibold text-danger">other members</span>
                        &nbsp;&middot;&nbsp; by Phone &amp; Email
                    </div>
                </div>
            </div>
            <button class="btn btn-danger btn-sm px-3" data-bs-toggle="modal" data-bs-target="#dupAssignedModal">
                <i class="bi bi-eye me-1"></i> View Details
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Active Filter Warning Alert -->
    <?php if ($isFilterMissing ?? false): ?>
    <div class="col-12 mb-3">
        <div class="alert alert-warning border-0 shadow-sm mb-0 p-3 d-flex align-items-center justify-content-between flex-wrap gap-2"
             style="background:linear-gradient(135deg,#fffbeb,#fef3c7);border-left:4px solid #d97706 !important;border-radius:10px;">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-funnel-fill text-warning fs-4"></i>
                <div>
                    <div class="fw-bold text-warning-emphasis">Showing Missing Follow-Ups Only</div>
                    <div class="text-muted small">Showing leads scheduled for today where no follow-up has been logged.</div>
                </div>
            </div>
            <a href="<?= $GLOBALS['base_url'] ?>/my-all-assigned-leads" class="btn btn-warning btn-sm">Show All Leads</a>
        </div>
    </div>
    <?php endif; ?>

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
                    <table id="assignedLeadsTable" data-dt-custom="1" class="table no-border-table mb-20 multiple_tables">
                        <thead>
                            <tr class="table__title table__sort">
                                <th style="min-width:60px;">
                                    <input type="checkbox" id="selectAllLeads" title="Select All">
                                    S/L
                                </th>
                                <th style="min-width: 120px;">Lead Date</th>
                                <th style="min-width: 160px;">Lead Name $ Job /Company name</th>
                                <th style="min-width: 150px;">Best Time to Contact</th>
                                <th style="min-width: 140px;">Address & City</th>
                                <th style="min-width: 180px;">Project Campaign</th>

                                <th style="min-width: 200px;">Last Follow Up Project</th>
                                <th style="min-width: 160px;">Change Client Action</th>
                                <th style="min-width: 160px;" class="text-center">Call Statas</th>
                                <th style="min-width: 160px;" class="text-center">Communtication Type</th>
                                <th style="min-width: 160px;" class="text-center">Lead Status</th>
                                <th style="min-width: 130px;" class="text-center">Follow Up Status</th>
                                <th style="min-width: 250px;" class="text-center">Instant Call Note</th>
                                <th style="min-width: 250px;" class="text-center">Last Follow Up Note</th>
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
                                     <td>
                                         <strong><?= htmlspecialchars($lead['full_name'] ?? '') ?></strong>
                                         <?php
                                         $phone = $lead['phone_number'] ?? $lead['phone'] ?? '';
                                         $phone = preg_replace('/[^0-9]/', '', $phone);

                                         if (substr($phone, 0, 2) === '01') {
                                             $phone = '880' . substr($phone, 1);
                                         }
                                         ?>
                                         <div class="mt-1">
                                             <a href="https://wa.me/<?= $phone ?>" target="_blank">
                                                 <?= htmlspecialchars($lead['phone_number'] ?? $lead['phone'] ?? '') ?>
                                             </a>
                                         </div>
                                         <?php
                                         $jt = !empty($lead['job_title']) ? $lead['job_title'] : (!empty($lead['designation']) ? $lead['designation'] : '');
                                         $co = !empty($lead['company_name']) ? $lead['company_name'] : (!empty($lead['company']) ? $lead['company'] : '');
                                         if ($jt !== '' || $co !== ''):
                                         ?>
                                         <div class="text-muted fs-11 mt-1">
                                             <i class="bi bi-briefcase me-1"></i>
                                             <?= htmlspecialchars($jt) ?><?= ($jt !== '' && $co !== '') ? ' at ' : '' ?><strong><?= htmlspecialchars($co) ?></strong>
                                         </div>
                                         <?php endif; ?>
                                     </td>
                                     <!-- Best Time to Contact -->
                                     <td>
                                         <?= htmlspecialchars($lead['when_is_the_best_time_to_contact_you'] ?? '—') ?>
                                     </td>

                                     <!-- Address & City -->
                                     <td>
                                         <?php 
                                         $addrParts = [];
                                         if (!empty($lead['country'])) $addrParts[] = trim($lead['country']);
                                         if (!empty($lead['address'])) $addrParts[] = trim($lead['address']);
                                         if (!empty($lead['city'])) $addrParts[] = trim($lead['city']);
                                         $addr = implode(', ', $addrParts);
                                         ?>
                                         <span class="fs-12"><?= htmlspecialchars($addr !== '' ? $addr : '—') ?></span>
                                     </td>

                                    <?php
                                    $displayName = !empty($lead['campaign_name'])
                                        ? $lead['campaign_name']
                                        : (!empty($lead['project_name']) ? $lead['project_name'] : '-');
                                    if (!empty($lead['project_category_name'])) {
                                        $displayName .= ' (' . $lead['project_category_name'] . ')';
                                    }

                                    $plainText = strip_tags($displayName ?? '');
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
                                            class="call-status <?= $statusClass ?> "
                                            data-id="<?= $lead['id'] ?>">

                                            <option value="not_called" <?= $status == 'not_called' ? 'selected' : '' ?>>Not Called</option>
                                            <option value="called" <?= $status == 'called' ? 'selected' : '' ?>>Called</option>
                                            <option value="received" <?= $status == 'received' ? 'selected' : '' ?>>Received</option>
                                            <option value="not_received" <?= $status == 'not_received' ? 'selected' : '' ?>>Not Received</option>

                                        </select>
                                    </td>
                                    <?php
                                    $status = $lead['communication_type'] ?? 'Not Select';

                                    $statusClass = '';
                                    if (in_array($status, ['WA Messaging', 'WA Conversion', 'Wa Call', 'Imo Massage', 'Imo Call', 'Phone Call', 'Phone Massage'])) {
                                        $statusClass = 'bg-received';
                                    } elseif (in_array($status, ['Invalid Number', 'No Wa /No Imo', 'Number Off'])) {
                                        $statusClass = 'bg-not-received';
                                    }
                                    ?>
                                    <td class="call-status-cell">
                                        <select
                                            class="communication-status <?= $statusClass ?> form-select form-control"
                                            data-id="<?= $lead['id'] ?>">
                                            <option value="">Select type</option>
                                            <option value="WA Messaging" <?= $status == 'WA Messaging' ? 'selected' : '' ?>>WA Messaging</option>
                                            <option value="WA Conversion" <?= $status == 'WA Conversion' ? 'selected' : '' ?>>WA Conversion</option>
                                            <option value="Wa Call" <?= $status == 'Wa Call' ? 'selected' : '' ?>>Wa Call</option>
                                            <option value="Imo Massage" <?= $status == 'Imo Massage' ? 'selected' : '' ?>>Imo Massage</option>
                                            <option value="Imo Call" <?= $status == 'Imo Call' ? 'selected' : '' ?>>Imo Call</option>
                                            <option value="Phone Call" <?= $status == 'Phone Call' ? 'selected' : '' ?>>Phone Call</option>
                                            <option value="Phone Massage" <?= $status == 'Phone Massage' ? 'selected' : '' ?>>Phone Massage</option>
                                            <option value="Invalid Number" <?= $status == 'Invalid Number' ? 'selected' : '' ?>>Invalid Number</option>
                                            <option value="No Wa /No Imo" <?= $status == 'No Wa /No Imo' ? 'selected' : '' ?>>No Wa /No Imo</option>
                                            <option value="Number Off" <?= $status == 'Number Off' ? 'selected' : '' ?>>Number Off</option>
                                        </select>
                                    </td>
                                    <?php
                                    $statusVal = $lead['lead_type'] ?? 'Pending';

                                    $class = '';
                                    if ($statusVal == 'Prospect' || $statusVal == 'High Prospect') {
                                        $class = 'bg-received';
                                    } elseif ($statusVal == 'Not Interested' || $statusVal == "Don't Call Again" || $statusVal == 'Cold' || $statusVal == 'Refund') {
                                        $class = 'bg-not-received';
                                    }
                                    ?>

                                    <td>
                                        <select
                                            class="lead-status <?= $class ?> form-control select-form"
                                            data-id="<?= $lead['id'] ?>">
                                            <?php
                                            $statuses = [
                                                'No Response', 'Pending', 'Prospect', 'High Prospect', 
                                                'Will Visit', 'Visited', 'Sold', 'Lead Transferred', 
                                                'Project Transferred', 'Not Interested', 'Don\'t Call Again', 
                                                'Refund', 'Future', 'Cold', 'Hot', 'Warm'
                                            ];
                                            foreach($statuses as $st) {
                                                $selected = ($statusVal == $st) ? 'selected' : '';
                                                echo "<option value=\"$st\" $selected>$st</option>";
                                            }
                                            ?>
                                        </select>
                                    </td>

                                    <!-- Follow Up Status -->
                                    <td class="text-center">
                                        <?php
                                        $fuStatus   = $lead['last_follow_up_status'] ?? null;
                                        $nextDate   = $lead['next_follow_up_date'] ?? null;
                                        $today      = date('Y-m-d');

                                        if ($fuStatus === 'completed') {
                                            $fuBadgeClass = 'bg-success';
                                            $fuLabel      = '✔ Completed';
                                        } elseif ($fuStatus === 'missed') {
                                            $fuBadgeClass = 'bg-danger';
                                            $fuLabel      = '✘ Missed';
                                        } elseif ($fuStatus === 'canceled') {
                                            $fuBadgeClass = 'bg-secondary';
                                            $fuLabel      = 'Canceled';
                                        } elseif ($fuStatus === 'pending' && !empty($nextDate)) {
                                            if ($nextDate < $today) {
                                                // Overdue
                                                $fuBadgeClass = 'bg-danger';
                                                $fuLabel      = '⚠ Overdue';
                                            } elseif ($nextDate === $today) {
                                                // Due Today
                                                $fuBadgeClass = 'bg-warning text-dark';
                                                $fuLabel      = '🔔 Due Today';
                                            } else {
                                                // Upcoming
                                                $fuBadgeClass = 'bg-info text-dark';
                                                $fuLabel      = '📅 Upcoming';
                                            }
                                        } elseif ($fuStatus === 'pending') {
                                            $fuBadgeClass = 'bg-warning text-dark';
                                            $fuLabel      = 'Pending';
                                        } else {
                                            $fuBadgeClass = 'bg-light text-muted border';
                                            $fuLabel      = 'No Follow Up';
                                        }
                                        ?>
                                        <span class="badge <?= $fuBadgeClass ?>" style="font-size:11px; padding:5px 8px; white-space:nowrap;">
                                            <?= $fuLabel ?>
                                        </span>
                                    </td>

                                    <!-- First Call Note -->
                                    <td class="note-cell">
                                        <div class="note-wrapper" style="display: flex; gap: 5px; align-items: center;">
                                            <textarea
                                                class="call-note"
                                                data-id="<?= $lead['id'] ?>"
                                                placeholder="Write call note..."
                                                style="min-height: 38px; max-height: 38px; font-size: 0.8rem; padding: 5px 8px; border: 1px solid #ddd; border-radius: 5px; resize: none; flex: 1;"><?= htmlspecialchars($lead['call_note'] ?? '') ?></textarea>
                                            <button type="button" class="btn btn-success btn-sm note-save-btn" data-id="<?= $lead['id'] ?>" style="padding: 5px 10px; font-size: 0.75rem; height: 38px;">Save</button>
                                        </div>
                                    </td>

                                    <!-- Interactive Note -->
                                    <td class="note-cell">
                                        <div class="note-wrapper">
                                            <textarea
                                                class="last-followup-note"
                                                placeholder="No follow-up note yet..."
                                                readonly
                                                style="background:#f8f9fa; cursor:default; min-height: 38px; max-height: 38px; font-size: 0.8rem; padding: 5px 8px; border: 1px solid #ddd; border-radius: 5px; resize: none; flex: 1;"><?= htmlspecialchars($lead['last_follow_up_note'] ?? '') ?></textarea>
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
            <?php
            $displayName = !empty($lead['campaign_name'])
                ? $lead['campaign_name']
                : (!empty($lead['project_name']) ? $lead['project_name'] : '-');
            if (!empty($lead['project_category_name'])) {
                $displayName .= ' (' . $lead['project_category_name'] . ')';
            }
            $plainText = strip_tags($displayName ?? '');
            ?>
            <!-- Lead Card -->
            <div class="lead-card  mb-3" data-id="<?= $lead['id'] ?>">
                <div class="lead-card-header d-flex justify-content-between align-items-start gap-1">
                    <div class="flex-grow-1 overflow-hidden">
                        <span class="lead-name text-truncate"><?= htmlspecialchars($lead['full_name'] ?? '') ?></span>
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
                        <div class="lead-phone text-truncate small"><?= $lead['phone'] ??  $lead['phone_number']  ?></div>
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

                <!-- Lead Status (inline AJAX) -->
                <div class="mt-2">
                    <div class="info-label">Lead Status</div>
                    <?php
                    $statusVal = $lead['lead_type'] ?? 'Pending';
                    $statusClass = '';
                    if (in_array($statusVal, ['Prospect', 'High Prospect'])) {
                        $statusClass = 'bg-received';
                    } elseif (in_array($statusVal, ['Not Interested', "Don't Call Again", 'Cold', 'Refund'])) {
                        $statusClass = 'bg-not-received';
                    }
                    ?>
                    <select class="lead-status <?= $statusClass ?> form-control form-select-sm select-form mt-1"
                        data-id="<?= $lead['id'] ?>">
                        <?php
                        $statuses = [
                            'No Response', 'Pending', 'Prospect', 'High Prospect',
                            'Will Visit', 'Visited', 'Sold', 'Lead Transferred',
                            'Project Transferred', 'Not Interested', "Don't Call Again",
                            'Refund', 'Future', 'Cold', 'Hot', 'Warm'
                        ];
                        foreach ($statuses as $st) {
                            $selected = ($statusVal == $st) ? 'selected' : '';
                            echo "<option value=\"$st\" $selected>$st</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Follow Up Status Badge -->
                <div class="mt-2">
                    <div class="info-label">Follow Up Status</div>
                    <?php
                    $fuStatus = $lead['last_follow_up_status'] ?? null;
                    $nextDate = $lead['next_follow_up_date'] ?? null;
                    $today    = date('Y-m-d');
                    if ($fuStatus === 'completed') {
                        $fuBadgeClass = 'bg-success'; $fuLabel = '✔ Completed';
                    } elseif ($fuStatus === 'missed') {
                        $fuBadgeClass = 'bg-danger'; $fuLabel = '✘ Missed';
                    } elseif ($fuStatus === 'canceled') {
                        $fuBadgeClass = 'bg-secondary'; $fuLabel = 'Canceled';
                    } elseif ($fuStatus === 'pending' && !empty($nextDate)) {
                        if ($nextDate < $today)      { $fuBadgeClass = 'bg-danger';           $fuLabel = '⚠ Overdue'; }
                        elseif ($nextDate === $today) { $fuBadgeClass = 'bg-warning text-dark'; $fuLabel = '🔔 Due Today'; }
                        else                          { $fuBadgeClass = 'bg-info text-dark';    $fuLabel = '📅 Upcoming'; }
                    } elseif ($fuStatus === 'pending') {
                        $fuBadgeClass = 'bg-warning text-dark'; $fuLabel = 'Pending';
                    } else {
                        $fuBadgeClass = 'bg-light text-muted border'; $fuLabel = 'No Follow Up';
                    }
                    ?>
                    <span class="badge <?= $fuBadgeClass ?> mt-1" style="font-size:11px; padding:5px 8px; white-space:nowrap;">
                        <?= $fuLabel ?>
                    </span>
                </div>

                <!-- First Call Note (editable) -->
                <div class="mt-2">
                    <label class="info-label d-block mb-1">First Call Note</label>
                    <textarea class="form-control call-note" data-id="<?= $lead['id'] ?>"
                        placeholder="Write call note..."
                        style="font-size:12px;"><?= htmlspecialchars($lead['call_note'] ?? '') ?></textarea>
                    <button type="button" class="btn btn-sm btn-success mt-1 note-save-btn" data-id="<?= $lead['id'] ?>">Save</button>
                </div>

                <!-- Last Follow-up Note (read-only) -->
                <div class="mt-2">
                    <label class="info-label d-block mb-1">Last Follow Up Note</label>
                    <textarea class="form-control" rows="2"
                        placeholder="No follow-up note yet..."
                        readonly
                        style="background:#f8f9fa; cursor:default; font-size:12px;"><?= htmlspecialchars($lead['last_follow_up_note'] ?? '') ?></textarea>
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
    const assignUserBtn = document.getElementById('assignUserBtn');
    if (assignUserBtn) {
        assignUserBtn.addEventListener('click', function() {
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
    }
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
    // Call status update
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

    // Communication type update
    document.querySelectorAll('.communication-status').forEach(select => {
        select.addEventListener('change', function() {
            fetch(base_url + '/update-communication-type', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'lead_id=' + this.dataset.id + '&communication_type=' + this.value
            });
        });
    });

    // lead status
    document.querySelectorAll('.lead-status').forEach(select => {
        select.addEventListener('change', function() {

            fetch(base_url + '/update-lead-status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'lead_id=' + this.dataset.id + '&lead_status=' + encodeURIComponent(this.value)
                })
                .then(res => res.json())
                .then(data => {
                    console.log(data);
                })
                .catch(err => {
                    console.error(err);
                });

        });
    });

    // Save notes via button
    document.querySelectorAll('.note-save-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const leadId = this.dataset.id;
            const textarea = this.parentElement.querySelector('.call-note');
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
        const url = (typeof base_url !== 'undefined' ? base_url : window.base_url || "<?= $GLOBALS['base_url'] ?>") + '/update-call-notes';
        fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'lead_id=' + encodeURIComponent(leadId) + '&call_note=' + encodeURIComponent(note) + '&ajax=1'
            })
            .then(res => {
                if (res.status === 401) {
                    // Session expired
                    return res.json().then(data => {
                        throw new Error(data.message || 'Session expired. Please login again.');
                    });
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    el.style.borderColor = '#198754'; // success hint
                    setTimeout(() => el.style.borderColor = '#ddd', 800);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Note saved successfully!',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        });
                    }
                } else {
                    throw new Error(data.error || 'Server returned failure');
                }
            })
            .catch(err => {
                console.error('Error saving note:', err);
                el.style.borderColor = '#dc3545'; // error hint

                const isSessionErr = err.message && (err.message.toLowerCase().includes('session') || err.message.toLowerCase().includes('login'));

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: isSessionErr ? 'warning' : 'error',
                        title: isSessionErr ? 'Session Expired!' : 'Failed to save note!',
                        text: err.message,
                        toast: !isSessionErr,
                        position: isSessionErr ? 'center' : 'top-end',
                        showConfirmButton: isSessionErr,
                        confirmButtonText: isSessionErr ? 'Login Again' : undefined,
                        timer: isSessionErr ? undefined : 3000,
                        timerProgressBar: !isSessionErr
                    }).then(result => {
                        if (isSessionErr && result.isConfirmed) {
                            window.location.href = (typeof base_url !== 'undefined' ? base_url : '') + '/admin-login';
                        }
                    });
                }
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

<!-- ══ Duplicate Assigned Leads Modal (Team-scoped) ════════════════════════════════════════════════════ -->
<?php if (!empty($duplicateGroups)): ?>
<div class="modal fade" id="dupAssignedModal" tabindex="-1" aria-labelledby="dupAssignedModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:14px;overflow:hidden;">

      <!-- Header -->
      <div class="modal-header" style="background:linear-gradient(135deg,#dc3545,#b02a37);color:#fff;">
        <div>
          <h5 class="modal-title fw-bold mb-0" id="dupAssignedModalLabel">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>Duplicate Assigned Leads
          </h5>
          <small style="opacity:.85;font-size:.78rem;">
            Leads in your team that are also assigned in other teams or to other members
          </small>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <!-- Filter Bar -->
      <div class="modal-body pb-0 pt-3" style="background:#fafafa;">
        <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
          <label class="fw-semibold small text-muted mb-0">Filter by Assigned Member:</label>
          <select id="dupMemberFilter" class="form-select form-select-sm" style="max-width:240px;">
            <option value="">All Members</option>
            <?php
              $allAssignees = [];
              foreach ($duplicateGroups as $grp) {
                  foreach ($grp['rows'] as $r) {
                      $name = $r['assigned_to_name'] ?? 'Unknown';
                      $allAssignees[$name] = true;
                  }
              }
              ksort($allAssignees);
              foreach (array_keys($allAssignees) as $aName):
            ?>
            <option value="<?= htmlspecialchars($aName) ?>"><?= htmlspecialchars($aName) ?></option>
            <?php endforeach; ?>
          </select>
          <span class="badge bg-danger rounded-pill ms-auto"><?= $duplicateCount ?> Total Leads</span>
        </div>
      </div>

      <!-- Body -->
      <div class="modal-body pt-2">
        <div id="dupGroupsContainer">
        <?php foreach ($duplicateGroups as $gi => $grp): ?>
          <div class="dup-group mb-4"
               data-members="<?= htmlspecialchars(implode('|', array_column($grp['rows'], 'assigned_to_name'))) ?>">

            <!-- Group Header -->
            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
              <span class="badge bg-danger rounded-pill" style="font-size:.7rem;">
                <?= count($grp['rows']) ?> lead<?= count($grp['rows']) > 1 ? 's' : '' ?>
              </span>
              <span class="fw-bold text-dark" style="font-size:.9rem;">
                <?= htmlspecialchars($grp['name']) ?>
              </span>
              <?php if (!empty($grp['label'])): ?>
              <span class="text-muted small">— <?= htmlspecialchars($grp['label']) ?></span>
              <?php endif; ?>
              <?php if (!empty($grp['team_name'])): ?>
              <span class="badge ms-1" style="background:#e8f4fd;color:#0369a1;border:1px solid #bae6fd;font-size:.7rem;">
                <i class="bi bi-people-fill me-1"></i><?= htmlspecialchars($grp['team_name']) ?>
              </span>
              <?php endif; ?>
            </div>

            <!-- Assignments table -->
            <div class="table-responsive rounded shadow-sm border">
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
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($grp['rows'] as $ri => $r): ?>
                  <tr class="dup-row <?= ($r['is_mine'] ?? 0) ? 'table-success' : '' ?>"
                      data-member="<?= htmlspecialchars($r['assigned_to_name'] ?? '') ?>">
                    <td class="ps-3 text-muted"><?= $ri + 1 ?></td>
                    <td class="fw-semibold">
                      <?= htmlspecialchars($r['full_name'] ?? 'N/A') ?>
                      <?php if ($r['is_mine'] ?? 0): ?>
                        <span class="badge ms-1" style="background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;font-size:.65rem;">
                          <i class="bi bi-people-fill me-1"></i>My Team
                        </span>
                      <?php endif; ?>
                    </td>
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
                        <div><i class="bi bi-telephone-fill text-muted me-1" style="font-size:.75rem;"></i><?= htmlspecialchars($r['phone_number']) ?></div>
                      <?php endif; ?>
                      <?php if (!empty($r['email'])): ?>
                        <div class="text-muted small"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($r['email']) ?></div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <span class="badge rounded-pill d-inline-flex align-items-center gap-1"
                            style="background:#e7f3ff;color:#0d6efd;border:1px solid #b6d4fe;font-size:.75rem;padding:.3rem .65rem;">
                        <i class="bi bi-person-fill"></i>
                        <?= htmlspecialchars($r['assigned_to_name'] ?? 'Unknown') ?>
                      </span>
                    </td>
                    <td>
                      <?php if (!empty($r['team_name'])): ?>
                      <span class="badge" style="background:#e8f4fd;color:#0369a1;border:1px solid #bae6fd;font-size:.72rem;">
                        <i class="bi bi-people-fill me-1"></i><?= htmlspecialchars($r['team_name']) ?>
                      </span>
                      <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                    <td class="text-muted">
                      <?php if (!empty($r['assigned_date'])): ?>
                        <div class="fw-semibold text-dark" style="font-size:.8rem;"><?= date('M d, Y', strtotime($r['assigned_date'])) ?></div>
                        <small class="text-muted"><?= date('h:i A', strtotime($r['assigned_date'])) ?></small>
                      <?php else: ?>—<?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endforeach; ?>

        <!-- Empty state -->
        <div id="dupNoResult" class="text-center py-5 d-none">
          <i class="bi bi-person-x fs-2 text-muted d-block mb-2"></i>
          <div class="text-muted">No duplicate leads found for selected member.</div>
        </div>
        </div>
      </div>

      <div class="modal-footer" style="background:#f8f9fa;">
        <small class="text-muted me-auto">Leads in your team also found in other teams or assigned to other members</small>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var filterEl = document.getElementById('dupMemberFilter');
    if (!filterEl) return;
    filterEl.addEventListener('change', function () {
        var selected = this.value.toLowerCase();
        var anyVisible = false;
        document.querySelectorAll('.dup-group').forEach(function (grp) {
            var members = (grp.getAttribute('data-members') || '').toLowerCase();
            var show = !selected || members.includes(selected);
            grp.style.display = show ? '' : 'none';
            if (show) anyVisible = true;
        });
        var noRes = document.getElementById('dupNoResult');
        if (noRes) noRes.classList.toggle('d-none', anyVisible);
    });
});
</script>
<?php endif; ?>



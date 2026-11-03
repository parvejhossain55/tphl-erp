<!-- Custom Styles for Table and Responsiveness -->
<?php
$totalDuplicatesCount = 0;
$duplicateLeadIds = [];
foreach ($myAllleads as $lead) {
    $cleanPhone = preg_replace('/[^0-9]/', '', $lead['phone_number'] ?? $lead['phone'] ?? '');
    $cleanEmail = trim(strtolower($lead['email'] ?? ''));
    $leadDuplicates = [];
    $dupIds = [];
    if ($cleanPhone !== '' && isset($phoneMap[$cleanPhone])) {
        foreach ($phoneMap[$cleanPhone] as $item) {
            if (!in_array($item['id'], $dupIds)) { $leadDuplicates[] = $item; $dupIds[] = $item['id']; }
        }
    }
    if ($cleanEmail !== '' && isset($emailMap[$cleanEmail])) {
        foreach ($emailMap[$cleanEmail] as $item) {
            if (!in_array($item['id'], $dupIds)) { $leadDuplicates[] = $item; $dupIds[] = $item['id']; }
        }
    }
    if (count($leadDuplicates) > 1) {
        $totalDuplicatesCount++;
    }
}
?>
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

    .project-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
        background-color: #f1f5f9;
        color: #334155;
    }

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

    /* Lead Status Badges (under Action Select) */
    .lead-status-badge-wrap .badge {
        font-weight: 600;
        font-size: 10px !important;
        padding: 3px 8px !important;
        border-radius: 4px;
        display: inline-block;
        letter-spacing: 0.2px;
        line-height: 1.2;
    }
    .badge.bg-not-received, .bg-not-received, .lead-status-badge-wrap .bg-not-received {
        background-color: #dc3545 !important;
        color: #ffffff !important;
    }
    .badge.bg-received, .bg-received, .lead-status-badge-wrap .bg-received {
        background-color: #198754 !important;
        color: #ffffff !important;
    }
    .lead-status-badge-wrap .bg-danger, .badge.bg-danger {
        background-color: #dc3545 !important;
        color: #ffffff !important;
    }
    .lead-status-badge-wrap .bg-success, .badge.bg-success {
        background-color: #198754 !important;
        color: #ffffff !important;
    }
    .lead-status-badge-wrap .bg-warning, .badge.bg-warning {
        background-color: #f59e0b !important;
        color: #1e293b !important;
    }
    .lead-status-badge-wrap .bg-info, .badge.bg-info {
        background-color: #0284c7 !important;
        color: #ffffff !important;
    }
    .lead-status-badge-wrap .bg-secondary, .badge.bg-secondary {
        background-color: #0d9488 !important;
        color: #ffffff !important;
    }
    .lead-status-badge-wrap .bg-dark, .badge.bg-dark {
        background-color: #334155 !important;
        color: #ffffff !important;
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

<?php
if (!function_exists('getMyAssignedStatCardLink')) {
    function getMyAssignedStatCardLink($cardType) {
        $params = $_GET;
        if ($cardType === 'total_leads') {
            unset($params['filter_card'], $params['filter']);
        } elseif ($cardType === 'missing_followup') {
            unset($params['filter_card']);
            $params['filter'] = 'missing_followup';
        } else {
            unset($params['filter']);
            $params['filter_card'] = $cardType;
        }
        unset($params['page'], $params['url']);
        return $GLOBALS['base_url'] . '/my-all-assigned-leads' . ($params ? '?' . http_build_query($params) : '');
    }
}
?>

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
<div class="row mb-4 align-items-stretch">
    <!-- Total My Leads Card -->
    <div class="col-6 col-md-4 col-xl mb-2">
        <a href="<?= getMyAssignedStatCardLink('total_leads') ?>" class="stat-card-link">
            <div class="card__wrapper stat-card shadow-sm <?= (empty($filterCard) && empty($selectedAction) && empty($selectedCallStatus) && empty($search) && empty($followup_date) && !($isFilterMissing ?? false)) ? 'border-primary' : '' ?>" 
                 style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 1.5px solid #bfdbfe !important; min-height: 85px;">
                <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right text-primary"></i></span>
                <div class="card-body d-flex justify-content-between align-items-center p-2 px-3">
                    <div>
                        <p class="stat-label mb-1 text-primary"><i class="fa-solid fa-users me-1"></i> Total My Leads</p>
                        <h2 class="stat-value text-primary mb-1" style="font-size: 1.6rem; font-weight: 800;"><?= $totalLeadsCount ?? count($myAllleads) ?></h2>
                        <span class="card-hint text-primary" style="font-size: 10px; margin-top: 0;">Click for all leads &rarr;</span>
                    </div>
                    <div class="card__icon text-primary" style="opacity: 0.15; font-size: 1.8rem;"><i class="fa-solid fa-users"></i></div>
                </div>
            </div>
        </a>
    </div>

    <!-- Contacted Card (Includes Contacted & Follow Up) -->
    <div class="col-6 col-md-4 col-xl mb-2">
        <a href="<?= getMyAssignedStatCardLink('contacted') ?>" class="stat-card-link">
            <div class="card__wrapper stat-card shadow-sm <?= (($filterCard ?? '') === 'contacted' || ($selectedAction ?? '') === 'Contacted') ? 'border-success' : '' ?>" 
                 style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1.5px solid #bbf7d0 !important; min-height: 85px;">
                <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right text-success"></i></span>
                <div class="card-body d-flex justify-content-between align-items-center p-2 px-3">
                    <div>
                        <p class="stat-label mb-1 text-success"><i class="fa-solid fa-phone-volume me-1"></i> Contacted</p>
                        <h2 class="stat-value text-success mb-1" style="font-size: 1.6rem; font-weight: 800;"><?= $contactedLeadsCount ?? 0 ?></h2>
                        <span class="card-hint text-success" style="font-size: 10px; margin-top: 0;">Inc. Follow Up, Booked & Comm &rarr;</span>
                    </div>
                    <div class="card__icon text-success" style="opacity: 0.15; font-size: 1.8rem;"><i class="fa-solid fa-phone-volume"></i></div>
                </div>
            </div>
        </a>
    </div>

    <!-- Not Contacted Card -->
    <div class="col-6 col-md-4 col-xl mb-2">
        <a href="<?= getMyAssignedStatCardLink('not_contacted') ?>" class="stat-card-link">
            <div class="card__wrapper stat-card shadow-sm <?= (($filterCard ?? '') === 'not_contacted' || ($filterCard ?? '') === 'not_called' || ($selectedCallStatus ?? '') === 'not_called') ? 'border-warning' : '' ?>" 
                 style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); border: 1.5px solid #fed7aa !important; min-height: 85px;">
                <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right text-warning"></i></span>
                <div class="card-body d-flex justify-content-between align-items-center p-2 px-3">
                    <div>
                        <p class="stat-label mb-1 text-warning-emphasis"><i class="fa-solid fa-phone-slash me-1"></i> Not Contacted</p>
                        <h2 class="stat-value text-warning-emphasis mb-1" style="font-size: 1.6rem; font-weight: 800;"><?= $notContactedLeadsCount ?? $notCalledLeadsCount ?? 0 ?></h2>
                        <span class="card-hint text-warning-emphasis" style="font-size: 10px; margin-top: 0;">Click to view not contacted &rarr;</span>
                    </div>
                    <div class="card__icon text-warning" style="opacity: 0.2; font-size: 1.8rem;"><i class="fa-solid fa-phone-slash"></i></div>
                </div>
            </div>
        </a>
    </div>

    <!-- Booked Card -->
    <div class="col-6 col-md-4 col-xl mb-2">
        <a href="<?= getMyAssignedStatCardLink('booked') ?>" class="stat-card-link">
            <div class="card__wrapper stat-card shadow-sm <?= (($filterCard ?? '') === 'booked' || ($selectedAction ?? '') === 'Booked') ? 'border-purple' : '' ?>" 
                 style="background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%); border: 1.5px solid #e9d5ff !important; min-height: 85px;">
                <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right text-purple"></i></span>
                <div class="card-body d-flex justify-content-between align-items-center p-2 px-3">
                    <div>
                        <p class="stat-label mb-1" style="color: #6b21a8;"><i class="fa-solid fa-bookmark me-1"></i> Booked Leads</p>
                        <h2 class="stat-value mb-1" style="font-size: 1.6rem; font-weight: 800; color: #6b21a8;"><?= number_format($totalBookingsCount ?? $bookedLeadsCount ?? 0) ?></h2>
                        <?php if (!empty($bookedLeadsCount) && !empty($totalBookingsCount) && $bookedLeadsCount != $totalBookingsCount): ?>
                            <div style="font-size: 11px; color: #6b21a8; font-weight: 600; margin-top: 1px;"><?= number_format($bookedLeadsCount) ?> Leads / Clients</div>
                        <?php else: ?>
                            <span class="card-hint" style="color: #6b21a8; font-size: 10px; margin-top: 0;">Click to view booked &rarr;</span>
                        <?php endif; ?>
                    </div>
                    <div class="card__icon" style="color: #6b21a8; opacity: 0.15; font-size: 1.8rem;"><i class="fa-solid fa-bookmark"></i></div>
                </div>
            </div>
        </a>
    </div>

    <!-- Upcoming Follow-ups Card -->
    <div class="col-6 col-md-4 col-xl mb-2">
        <a href="<?= getMyAssignedStatCardLink('upcoming_follow_ups') ?>" class="stat-card-link">
            <div class="card__wrapper stat-card shadow-sm <?= (($filterCard ?? '') === 'upcoming_follow_ups') ? 'border-info' : '' ?>" 
                 style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 1.5px solid #bae6fd !important; min-height: 85px;">
                <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right text-info"></i></span>
                <div class="card-body d-flex justify-content-between align-items-center p-2 px-3">
                    <div>
                        <p class="stat-label mb-1 text-info-emphasis" style="color: #0369a1;"><i class="fa-solid fa-clock-rotate-left me-1"></i> Upcoming Follow-Ups</p>
                        <h2 class="stat-value mb-1" style="font-size: 1.6rem; font-weight: 800; color: #0369a1;"><?= $upcomingFollowUpsCount ?? 0 ?></h2>
                        <span class="card-hint" style="font-size: 10px; margin-top: 0; color: #0369a1;">Click to view upcoming &rarr;</span>
                    </div>
                    <div class="card__icon" style="opacity: 0.15; font-size: 1.8rem; color: #0369a1;"><i class="fa-solid fa-clock-rotate-left"></i></div>
                </div>
            </div>
        </a>
    </div>

    <!-- Missing Follow-ups Stats Card -->
    <div class="col-6 col-md-4 col-xl mb-2">
        <a href="<?= getMyAssignedStatCardLink('missing_followup') ?>" class="stat-card-link">
            <div class="card__wrapper stat-card shadow-sm <?= ($isFilterMissing ?? false) ? 'border-danger' : '' ?>" 
                 style="background: linear-gradient(135deg, #fff5f5 0%, #ffe3e3 100%); border: 1.5px solid <?= ($isFilterMissing ?? false) ? '#dc3545' : '#fecaca' ?> !important; min-height: 85px;">
                <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right text-danger"></i></span>
                <div class="card-body d-flex justify-content-between align-items-center p-2 px-3">
                    <div>
                        <p class="stat-label mb-1 text-danger"><i class="fa-solid fa-bell-slash me-1"></i> Missing Follow-Ups</p>
                        <h2 class="stat-value text-danger mb-1" style="font-size: 1.6rem; font-weight: 800;"><?= $missingFollowUpCount ?? 0 ?></h2>
                        <span class="card-hint text-danger" style="font-size: 10px; margin-top: 0;">Click to view missing &rarr;</span>
                    </div>
                    <div class="card__icon text-danger" style="opacity: 0.15; font-size: 1.8rem;"><i class="fa-solid fa-bell-slash"></i></div>
                </div>
            </div>
        </a>
    </div>

    <!-- No Response Leads Card -->
    <div class="col-6 col-md-4 col-xl mb-2">
        <a href="<?= getMyAssignedStatCardLink('no_response') ?>" class="stat-card-link">
            <div class="card__wrapper stat-card shadow-sm <?= (in_array(($filterCard ?? ''), ['no_response', 'no_response_leads']) || ($selectedLeadStatus ?? '') === 'No Response') ? 'border-warning' : '' ?>" 
                 style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border: 1.5px solid #fde68a !important; min-height: 85px;">
                <span class="card-nav-arrow"><i class="fa-solid fa-arrow-right text-warning"></i></span>
                <div class="card-body d-flex justify-content-between align-items-center p-2 px-3">
                    <div>
                        <p class="stat-label mb-1 text-warning-emphasis"><i class="fa-solid fa-phone-slash me-1"></i> No Response</p>
                        <h2 class="stat-value mb-1" style="font-size: 1.6rem; font-weight: 800; color: #d97706;"><?= $noResponseLeadsCount ?? 0 ?></h2>
                        <span class="card-hint text-warning-emphasis" style="font-size: 10px; margin-top: 0;">Click to view no response &rarr;</span>
                    </div>
                    <div class="card__icon text-warning" style="opacity: 0.2; font-size: 1.8rem;"><i class="fa-solid fa-phone-slash"></i></div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Duplicate Assigned Leads Card (Team-based) -->
<?php if (!empty($duplicateGroups)): ?>
<div class="col-12 mb-3">
    <div class="alert alert-danger border-0 shadow-sm mb-0 p-2 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2"
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

<!-- Active Filter Info Alert -->
<?php 
$selectedCountriesArr = is_array($selectedCountry ?? null) 
    ? $selectedCountry 
    : (!empty($selectedCountry) ? (array)$selectedCountry : []);

$selectedProjectObj = null;
if (!empty($_GET['project_id']) && !empty($projects)) {
    foreach ($projects as $p) {
        if ($p['id'] == $_GET['project_id']) { $selectedProjectObj = $p; break; }
    }
}
$selectedCategoryObj = null;
if (!empty($_GET['project_category_id']) && !empty($projectCategories)) {
    foreach ($projectCategories as $cat) {
        if ($cat['id'] == $_GET['project_category_id']) { $selectedCategoryObj = $cat; break; }
    }
}

$hasActiveFilters = !empty($search) 
    || !empty($followup_date) 
    || !empty($filterCard) 
    || !empty($selectedAction) 
    || !empty($selectedLeadStatus) 
    || !empty($selectedCallStatus) 
    || !empty($selectedCountriesArr) 
    || ($isFilterMissing ?? false)
    || !empty($_GET['from_date'])
    || !empty($_GET['to_date'])
    || !empty($_GET['uncontacted'])
    || !empty($_GET['project_id'])
    || !empty($_GET['project_category_id'])
    || !empty($_GET['year'])
    || !empty($_GET['month'])
    || !empty($_GET['assigned_hours']);
?>
<div id="activeFiltersContainer">
<?php if ($hasActiveFilters): ?>
<div class="col-12 mb-3">
    <div class="alert alert-info border-0 shadow-sm mb-0 p-3 d-flex align-items-center justify-content-between flex-wrap gap-2"
         style="background:linear-gradient(135deg,#f0f9ff,#e0f2fe);border-left:4px solid #0284c7 !important;border-radius:10px;">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <i class="bi bi-funnel-fill text-info fs-5 me-1"></i>
            <span class="fw-bold text-dark me-2">Active Filters:</span>
            <?php if (!empty($search)): ?>
                <span class="badge bg-primary rounded-pill px-3 py-2"><i class="bi bi-search me-1"></i> Search: "<?= htmlspecialchars($search) ?>"</span>
            <?php endif; ?>
            <?php if (!empty($_GET['from_date']) || !empty($_GET['to_date'])): ?>
                <span class="badge bg-success rounded-pill px-3 py-2">
                    <i class="bi bi-calendar-range me-1"></i> 
                    <?= (($_GET['date_type'] ?? 'lead_date') === 'assigned_date' ? 'Assigned Date' : 'Lead Date') ?>: 
                    <?= !empty($_GET['from_date']) ? date('d M, Y', strtotime($_GET['from_date'])) : 'Any' ?> 
                    to 
                    <?= !empty($_GET['to_date']) ? date('d M, Y', strtotime($_GET['to_date'])) : 'Any' ?>
                </span>
            <?php endif; ?>
            <?php if (!empty($_GET['uncontacted'])): ?>
                <span class="badge bg-warning text-dark rounded-pill px-3 py-2"><i class="bi bi-telephone-x me-1"></i> Uncontacted Only</span>
            <?php endif; ?>
            <?php if (!empty($followup_date)): ?>
                <span class="badge bg-success rounded-pill px-3 py-2"><i class="bi bi-calendar-event me-1"></i> Follow-Up Date: <?= date('d M, Y', strtotime($followup_date)) ?></span>
            <?php endif; ?>
            <?php if ($selectedProjectObj): ?>
                <span class="badge bg-primary rounded-pill px-3 py-2"><i class="bi bi-building me-1"></i> Project: <?= htmlspecialchars($selectedProjectObj['name']) ?></span>
            <?php endif; ?>
            <?php if ($selectedCategoryObj): ?>
                <span class="badge bg-info text-dark rounded-pill px-3 py-2"><i class="bi bi-tags me-1"></i> Category: <?= htmlspecialchars($selectedCategoryObj['name']) ?></span>
            <?php endif; ?>
            <?php if (!empty($selectedCountriesArr)): ?>
                <span class="badge bg-info text-dark rounded-pill px-3 py-2"><i class="bi bi-globe me-1"></i> Country: <?= htmlspecialchars(implode(', ', $selectedCountriesArr)) ?></span>
            <?php endif; ?>
            <?php if (!empty($selectedAction)): ?>
                <span class="badge bg-secondary rounded-pill px-3 py-2"><i class="bi bi-lightning me-1"></i> Action: <?= htmlspecialchars($selectedAction) ?></span>
            <?php endif; ?>
            <?php if (!empty($selectedLeadStatus)): ?>
                <span class="badge bg-dark rounded-pill px-3 py-2"><i class="bi bi-flag me-1"></i> Status: <?= htmlspecialchars($selectedLeadStatus) ?></span>
            <?php endif; ?>
            <?php if (!empty($_GET['year']) || !empty($_GET['month'])): ?>
                <span class="badge bg-light text-dark border rounded-pill px-3 py-2">
                    <i class="bi bi-calendar-date me-1"></i> Period: 
                    <?= !empty($_GET['month']) ? date('F', mktime(0, 0, 0, (int)$_GET['month'], 10)) : '' ?>
                    <?= htmlspecialchars($_GET['year'] ?? '') ?>
                </span>
            <?php endif; ?>
            <?php if (!empty($_GET['assigned_hours'])): ?>
                <span class="badge bg-secondary rounded-pill px-3 py-2"><i class="bi bi-clock-history me-1"></i> Assigned: Last <?= htmlspecialchars($_GET['assigned_hours']) ?> Hours</span>
            <?php endif; ?>
            <?php if (!empty($filterCard)): ?>
                <span class="badge bg-dark rounded-pill px-3 py-2"><i class="bi bi-tag me-1"></i> Card: <?= ucwords(str_replace('_', ' ', $filterCard)) ?></span>
            <?php endif; ?>
            <?php if (!empty($selectedCallStatus)): ?>
                <span class="badge bg-warning text-dark rounded-pill px-3 py-2"><i class="bi bi-telephone me-1"></i> Call Status: <?= htmlspecialchars($selectedCallStatus) ?></span>
            <?php endif; ?>
            <?php if ($isFilterMissing ?? false): ?>
                <span class="badge bg-danger rounded-pill px-3 py-2"><i class="bi bi-bell-slash me-1"></i> Missing Follow-Ups</span>
            <?php endif; ?>
        </div>
        <a href="<?= $GLOBALS['base_url'] ?>/my-all-assigned-leads" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-x-circle me-1"></i> Clear All Filters
        </a>
    </div>
</div>
<?php endif; ?>
</div>

<!-- Desktop View: Filters and Table -->
<div class="desktop-only">
    <!-- Filters -->
    <div class="filters mb-3">
        <form action="" method="get">

            <div class="row g-2 align-items-center flex-wrap">

                <!-- Date Type Select -->
                <div class="col-auto col-sm-6 col-md-auto">
                    <select class="form-select form-select-sm" name="date_type" title="Filter dates by Lead Date (Created) or Assigned Date">
                        <option value="lead_date" <?= (($date_type ?? 'lead_date') === 'lead_date') ? 'selected' : '' ?>>Date: Lead Date</option>
                        <option value="assigned_date" <?= (($date_type ?? '') === 'assigned_date') ? 'selected' : '' ?>>Date: Assigned Date</option>
                    </select>
                </div>

                <!-- From Date Filter -->
                <div class="col-12 col-sm-6 col-md-auto">
                    <div class="input-group input-group-sm" title="From Date">
                        <span class="input-group-text " style="font-size: 11px;"><i class="bi bi-calendar me-1"></i> From</span>
                        <input type="date" name="from_date" class="form-control form-control-sm" value="<?= htmlspecialchars($_GET['from_date'] ?? '') ?>">
                    </div>
                </div>

                <!-- To Date Filter -->
                <div class="col-12 col-sm-6 col-md-auto">
                    <div class="input-group input-group-sm" title="To Date">
                        <span class="input-group-text " style="font-size: 11px;"><i class="bi bi-calendar me-1"></i> To</span>
                        <input type="date" name="to_date" class="form-control form-control-sm" value="<?= htmlspecialchars($_GET['to_date'] ?? '') ?>">
                    </div>
                </div>

                <!-- Uncontacted Filter -->
                <div class="col-12 col-sm-6 col-md-auto">
                    <div class="form-check mt-1">
                        <input class="form-check-input" type="checkbox" name="uncontacted" id="uncontactedFilter" value="1" <?= isset($_GET['uncontacted']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="uncontactedFilter" style="font-size: 12px; font-weight: bold;">
                            Uncontacted
                        </label>
                    </div>
                </div>

                <!-- Follow Up Date Filter -->
                <div class="col-12 col-sm-6 col-md-auto">
                    <div class="input-group input-group-sm" title="Filter by Next Follow-Up Date">
                        <span class="input-group-text " style="font-size: 11px;"><i class="bi bi-calendar-event me-1"></i> Next Follow-Up</span>
                        <input type="date" name="followup_date" class="form-control form-control-sm" value="<?= htmlspecialchars($followup_date ?? '') ?>">
                    </div>
                </div>

                <!-- Project Filter -->
                <?php if (!empty($projects)): ?>
                <div class="col-12 col-sm-6 col-md-2">
                    <?php $selectedProject = $_GET['project_id'] ?? ''; ?>
                    <select class="form-select form-select-sm filter-select select2" name="project_id" data-placeholder="Project: All">
                        <option value=""></option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $selectedProject == $p['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <!-- Category Filter -->
                <?php if (!empty($projectCategories)): ?>
                <div class="col-12 col-sm-6 col-md-2">
                    <?php $selectedCategory = $_GET['project_category_id'] ?? ''; ?>
                    <select class="form-select form-select-sm filter-select select2" name="project_category_id" data-placeholder="Category: All">
                        <option value=""></option>
                        <?php foreach ($projectCategories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $selectedCategory == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <!-- Country Filter (Multi-Select) -->
                <?php if (!empty($countries)): ?>
                <div class="col-12 col-sm-6 col-md-2">
                    <select class="form-select form-select-sm filter-select select2" name="filter_country[]" multiple data-placeholder="Country: All">
                        <?php foreach ($countries as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= in_array($c, $selectedCountriesArr) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <!-- Client Action Select -->
                <div class="col-auto col-sm-6 col-md-auto">
                    <select class="form-select form-select-sm" name="last_action">
                        <option value="">Client Action: All</option>
                        <option value="Contacted" <?= ($selectedAction ?? '') == 'Contacted' ? 'selected' : '' ?>>Contacted</option>
                        <option value="Follow Up" <?= ($selectedAction ?? '') == 'Follow Up' ? 'selected' : '' ?>>Follow Up</option>
                        <option value="Visited" <?= ($selectedAction ?? '') == 'Visited' ? 'selected' : '' ?>>Visited</option>
                        <option value="Booked" <?= ($selectedAction ?? '') == 'Booked' ? 'selected' : '' ?>>Booked</option>
                        <option value="New" <?= ($selectedAction ?? '') == 'New' ? 'selected' : '' ?>>New</option>
                        <option value="Cancel" <?= ($selectedAction ?? '') == 'Cancel' ? 'selected' : '' ?>>Cancel</option>
                    </select>
                </div>

                <!-- Lead Status Select -->
                <div class="col-auto col-sm-6 col-md-auto">
                    <select class="form-select form-select-sm" name="lead_status">
                        <option value="">Lead Status: All</option>
                        <?php
                        $statuses = [
                            'No Response', 'Pending', 'Qualification in Progress', 'Potential', 'High Potential', 
                            'Visit Scheduled', 'Visit Completed', 'Sold', 'Lead Transferred', 
                            'Project Transferred', 'Not Interested', 
                            'Refund', 'Future', 'Invalid Lead'
                        ];
                        foreach($statuses as $st) {
                            $selected = ($selectedLeadStatus ?? '') == $st ? 'selected' : '';
                            echo "<option value=\"$st\" $selected>$st</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Year Select -->
                <div class="col-auto col-sm-6 col-md-auto">
                    <?php
                    $currentYear = date('Y');
                    $startYear   = $currentYear - 10;
                    $selectedYear = $_GET['year'] ?? '';
                    ?>
                    <select class="form-select form-select-sm" name="year">
                        <option value="">Year Select</option>
                        <?php for ($yearVal = $currentYear; $yearVal >= $startYear; $yearVal--): ?>
                            <option value="<?= $yearVal ?>" <?= $selectedYear == $yearVal ? 'selected' : '' ?>><?= $yearVal ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <!-- Month Select -->
                <?php
                $months = [
                    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                ];
                $selectedMonth = $_GET['month'] ?? '';
                ?>
                <div class="col-auto col-sm-6 col-md-auto">
                    <select class="form-select form-select-sm" name="month">
                        <option value="">Month Select</option>
                        <?php foreach ($months as $id => $name): ?>
                            <option value="<?= $id ?>" <?= $selectedMonth == $id ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Latest Assigned Hours Filter -->
                <div class="col-auto col-sm-6 col-md-auto">
                    <select class="form-select form-select-sm" name="assigned_hours" title="Latest Assigned Leads within Hours">
                        <option value="">Assigned Within: All</option>
                        <option value="1" <?= ($_GET['assigned_hours'] ?? '') == '1' ? 'selected' : '' ?>>Last 1 Hour</option>
                        <option value="2" <?= ($_GET['assigned_hours'] ?? '') == '2' ? 'selected' : '' ?>>Last 2 Hours</option>
                        <option value="3" <?= ($_GET['assigned_hours'] ?? '') == '3' ? 'selected' : '' ?>>Last 3 Hours</option>
                        <option value="4" <?= ($_GET['assigned_hours'] ?? '') == '4' ? 'selected' : '' ?>>Last 4 Hours</option>
                        <option value="5" <?= ($_GET['assigned_hours'] ?? '') == '5' ? 'selected' : '' ?>>Last 5 Hours</option>
                        <option value="6" <?= ($_GET['assigned_hours'] ?? '') == '6' ? 'selected' : '' ?>>Last 6 Hours</option>
                        <option value="12" <?= ($_GET['assigned_hours'] ?? '') == '12' ? 'selected' : '' ?>>Last 12 Hours</option>
                        <option value="24" <?= ($_GET['assigned_hours'] ?? '') == '24' ? 'selected' : '' ?>>Last 24 Hours</option>
                    </select>
                </div>

                <?php if (!empty($filterCard)): ?>
                    <input type="hidden" name="filter_card" value="<?= htmlspecialchars($filterCard) ?>">
                <?php endif; ?>
                <?php if (!empty($_GET['filter'])): ?>
                    <input type="hidden" name="filter" value="<?= htmlspecialchars($_GET['filter']) ?>">
                <?php endif; ?>
                <?php if (!empty($search)): ?>
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                <?php endif; ?>
                <?php if (isset($_GET['limit'])): ?>
                    <input type="hidden" name="limit" value="<?= htmlspecialchars($_GET['limit']) ?>">
                <?php endif; ?>

                <!-- Filter & Reset Buttons -->
                <div class="col-auto d-flex align-items-center gap-2 flex-wrap">
                    <button type="submit" class="btn btn-primary btn-sm px-3 py-1">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <a href="<?= $GLOBALS['base_url'] ?>/my-all-assigned-leads" class="btn btn-outline-secondary btn-sm px-3 py-1">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </a>
                </div>

            </div>
        </form>
    </div>

        <!-- Table -->
        <!-- Desktop Assign Card -->
        <?php if (!empty($teamMembers)): ?>
            <div class="col-md-12 mb-2">
                <div id="assignCard" class="card__wrapper shadow-sm p-3 my-2" style="display:none; border-radius:12px;">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <label class="fw-bold mb-0">Assign Selected Leads to: <span id="selectedCount" class="badge bg-primary ms-2">0</span></label>
                        <select id="assignUserSelect" class="form-select form-select-sm select2" style="min-width:200px; width:auto;">
                            <option value="">Select User</option>
                            <?php foreach ($teamMembers as $user): ?>
                                <option value="<?= $user['id'] ?>">
                                    <?= htmlspecialchars($user['full_name']) ?> - <?= htmlspecialchars($user['role_name']) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                        <input type="date" id="assignedDate" class="form-control form-control-sm" style="width:auto;" value="<?= date('Y-m-d') ?>">
                        <button type="button" id="assignUserBtn" class="btn btn-primary btn-sm px-3"><i class="fa-solid fa-user-plus me-1"></i> Assign Leads</button>
                        <span id="assignFeedback" class="badge bg-success ms-2 p-2" style="display:none;">Assigned!</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2 mt-2">
            <div class="d-flex align-items-center gap-3">
                <label class="d-flex align-items-center gap-2 mb-0 text-muted" style="font-size: 0.85rem;">
                    Show 
                    <select id="tableLengthSelect" class="form-select form-select-sm" style="width: auto; min-width: 60px; display: inline-block;">
                        <option value="25" <?= (($limit ?? 25) == 25) ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= (($limit ?? 25) == 50) ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= (($limit ?? 25) == 100) ? 'selected' : '' ?>>100</option>
                        <option value="200" <?= (($limit ?? 25) == 200) ? 'selected' : '' ?>>200</option>
                        <option value="-1" <?= (($limit ?? 25) == -1) ? 'selected' : '' ?>>All</option>
                    </select>
                    entries
                </label>
            </div>
            <div>
                <form id="assignedLeadsSearchForm" action="" method="get" class="d-flex align-items-center gap-2 mb-0">
                    <?php 
                    foreach($_GET as $key => $val) {
                        if($key !== 'search' && $key !== 'page' && $key !== 'url' && !is_array($val)) {
                            echo '<input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($val).'">';
                        }
                        if (is_array($val)) {
                            foreach($val as $v) {
                                echo '<input type="hidden" name="'.htmlspecialchars($key).'[]" value="'.htmlspecialchars($v).'">';
                            }
                        }
                    }
                    ?>
                    <label class="mb-0 text-muted" style="font-size: 0.85rem;">Search:</label>
                    <div class="position-relative">
                        <input type="search" id="assignedLeadsSearchInput" name="search" class="form-control form-control-sm pe-4" style="width: 200px;" placeholder="Search..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" autocomplete="off">
                        <span id="searchSpinner" class="spinner-border spinner-border-sm text-primary position-absolute top-50 end-0 translate-middle-y me-2 d-none" role="status" style="width: 0.85rem; height: 0.85rem;"></span>
                    </div>
                </form>
            </div>
        </div>

        <div id="assignedLeadsContentArea" class="col-xxl-12">
            <div class="card__wrapper">
                <div class="table__wrapper table-responsive">
                    <table id="assignedLeadsTable" class="table no-border-table mb-20">
                        <thead>
                            <tr class="table__title table__sort">
                                <th style="min-width:60px;">
                                    <input type="checkbox" id="selectAllLeads" title="Select All">
                                    S/L
                                </th>
                                <th style="min-width: 120px;">Lead Date</th>
                                <th style="min-width: 180px;">Lead Name & Job title/Company</th>
                                <th style="min-width: 160px;">Address & Phone</th>
                                <th style="min-width: 110px;">Lead Uploaded</th>
                                <th style="min-width: 175px;">Preferences & Requirements</th>
                                <th style="min-width: 170px;">Project Campaign</th>
                                <th style="min-width: 120px;">Status</th>
                                <th style="min-width: 130px;">Next Follow-Up</th>
                                <th style="min-width: 180px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($myAllleads)): ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                                        <span>No matching leads found.</span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($myAllleads as $key => $lead): ?>
                                <?php
                                 $cleanPhone = preg_replace('/[^0-9]/', '', $lead['phone_number'] ?? $lead['phone'] ?? '');
                                 $cleanEmail = trim(strtolower($lead['email'] ?? ''));
                                 $leadDuplicates = [];
                                 $dupIds = [];
                                 if ($cleanPhone !== '' && isset($phoneMap[$cleanPhone])) {
                                     foreach ($phoneMap[$cleanPhone] as $item) {
                                         if (!in_array($item['id'], $dupIds)) {
                                             $leadDuplicates[] = $item;
                                             $dupIds[] = $item['id'];
                                         }
                                     }
                                 }
                                 if ($cleanEmail !== '' && isset($emailMap[$cleanEmail])) {
                                     foreach ($emailMap[$cleanEmail] as $item) {
                                         if (!in_array($item['id'], $dupIds)) {
                                             $leadDuplicates[] = $item;
                                             $dupIds[] = $item['id'];
                                         }
                                     }
                                 }
                                 $isDup = count($leadDuplicates) > 1;
                                 ?>
                                <tr id="lead-<?= $lead['id'] ?>" data-id="<?= $lead['id'] ?>" data-is-duplicate="<?= $isDup ? '1' : '0' ?>">
                                    <!-- 1. S/L -->
                                    <td>
                                        <input type="checkbox" class="me-2 selectedId" data-id="<?= $lead['id'] ?>">
                                        <?= (($currentPage - 1) * (($limit ?? 25) === -1 ? 0 : ($limit ?? 25))) + $key + 1 ?>
                                    </td>

                                    <!-- 2. Lead Date -->
                                    <td>
                                        <div class="fw-medium">
                                            <?= (!empty($lead['created_at']) && $lead['created_at'] !== '0000-00-00 00:00:00') ? date('d M, Y, h:i A', strtotime($lead['created_at'])) : '—' ?>
                                        </div>
                                    </td>

                                    <!-- 3. Lead Name & Job title/Company -->
                                    <td>
                                        <div class="d-flex align-items-center gap-1 flex-wrap">
                                            <strong><?= htmlspecialchars($lead['full_name'] ?? '') ?></strong>
                                            <?php if ($isDup): ?>
                                                <span class="badge bg-danger cursor-pointer duplicate-badge-click" 
                                                      data-duplicates="<?= htmlspecialchars(json_encode($leadDuplicates), ENT_QUOTES, 'UTF-8') ?>"
                                                      data-current-lead-id="<?= $lead['id'] ?>"
                                                      style="cursor: pointer; font-size: 10px; padding: 2px 5px;"
                                                      title="Click to view duplicate history">
                                                    Duplicate (<?= count($leadDuplicates) ?>)
                                                </span>
                                            <?php endif; ?>
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

                                    <!-- 4. Address & Phone -->
                                    <td>
                                        <?php 
                                        $addrParts = [];
                                        if (!empty($lead['address'])) $addrParts[] = trim($lead['address']);
                                        if (!empty($lead['city'])) $addrParts[] = trim($lead['city']);
                                        $addr = implode(', ', $addrParts);
                                        if (!empty($lead['country']) && strpos($addr, $lead['country']) === false) {
                                            $addr = trim($lead['country'] . ($addr !== '' ? ', ' . $addr : ''));
                                        }

                                        $rawPhone = $lead['phone_number'] ?? $lead['phone'] ?? '';
                                        $cleanPhoneDigits = preg_replace('/[^0-9]/', '', $rawPhone);
                                        if (substr($cleanPhoneDigits, 0, 2) === '01') {
                                            $cleanPhoneDigits = '880' . substr($cleanPhoneDigits, 1);
                                        }
                                        ?>
                                        <div>
                                            <div class="fs-12"><?= htmlspecialchars($addr !== '' ? $addr : '—') ?></div>
                                            <?php if (!empty($rawPhone)): ?>
                                                <div class="mt-1">
                                                    <a href="https://wa.me/<?= $cleanPhoneDigits ?>" target="_blank" class="text-decoration-none" style="white-space: nowrap; font-size: 12px;">
                                                        <i class="bi bi-whatsapp text-success me-1"></i><?= htmlspecialchars($rawPhone) ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <!-- 5. Lead Uploaded -->
                                    <td>
                                        <span class="bd-badge bg-success" style="white-space: nowrap;">
                                            <?= htmlspecialchars(!empty($lead['uploaded_from']) ? $lead['uploaded_from'] : '—') ?>
                                        </span>
                                    </td>

                                    <!-- 6. Preferences & Requirements -->
                                    <td>
                                        <?php
                                        $prefItems = [];

                                        // Best Time to Contact
                                        $rawTime = trim($lead['when_is_the_best_time_to_contact_you'] ?? $lead['preferred_contact_time'] ?? '');
                                        if ($rawTime !== '' && $rawTime !== '-' && $rawTime !== '—') {
                                            $cleanTime = ucfirst(str_replace('_', ' ', $rawTime));
                                            $prefItems[] = [
                                                'icon'  => 'bi-clock text-info me-1',
                                                'class' => 'bg-light text-secondary border',
                                                'text'  => truncateText($cleanTime, 24),
                                                'title' => 'Best Time to Contact: ' . $cleanTime
                                            ];
                                        }

                                        // Buying Plan
                                        $rawPlan = trim($lead['when_are_you_planning_to_buy'] ?? '');
                                        if ($rawPlan !== '' && $rawPlan !== '-' && $rawPlan !== '—') {
                                            $cleanPlan = ucwords(str_replace('_', ' ', $rawPlan));
                                            $prefItems[] = [
                                                'icon'  => 'bi-calendar3 text-primary me-1',
                                                'class' => 'bg-light text-dark border',
                                                'text'  => 'Plan: ' . truncateText($cleanPlan, 20),
                                                'title' => 'Buying Plan: ' . $cleanPlan
                                            ];
                                        }

                                        // Site Visit
                                        $rawVisit = trim($lead['would_you_like_to_schedule_a_site_visit'] ?? '');
                                        if ($rawVisit !== '' && $rawVisit !== '-' && $rawVisit !== '—') {
                                            $lowerVisit = strtolower($rawVisit);
                                            if (in_array($lowerVisit, ['yes!', 'yes', '1', 'হ্যাঁ'])) {
                                                $prefItems[] = [
                                                    'icon'  => 'bi-check-circle-fill text-success me-1',
                                                    'class' => 'bg-success-subtle text-success border border-success-subtle',
                                                    'text'  => 'Visit: Yes',
                                                    'title' => 'Site Visit: Yes'
                                                ];
                                            } elseif (in_array($lowerVisit, ['no!', 'no', '0', 'না'])) {
                                                $prefItems[] = [
                                                    'icon'  => 'bi-x-circle text-muted me-1',
                                                    'class' => 'bg-secondary-subtle text-muted border',
                                                    'text'  => 'Visit: No',
                                                    'title' => 'Site Visit: No'
                                                ];
                                            } else {
                                                $cleanVisit = ucwords(str_replace('_', ' ', $rawVisit));
                                                $prefItems[] = [
                                                    'icon'  => 'bi-geo-alt text-info me-1',
                                                    'class' => 'bg-info-subtle text-info border border-info-subtle',
                                                    'text'  => 'Visit: ' . truncateText($cleanVisit, 15),
                                                    'title' => 'Site Visit: ' . $cleanVisit
                                                ];
                                            }
                                        }

                                        // Apt Size
                                        $rawSize = trim($lead['which_apartment_size_are_you_interested_in'] ?? $lead['pref_flat_size'] ?? '');
                                        if ($rawSize !== '' && $rawSize !== '-' && $rawSize !== '—') {
                                            $cleanSize = str_replace('_', ' ', $rawSize);
                                            $prefItems[] = [
                                                'icon'  => 'bi-aspect-ratio text-primary me-1',
                                                'class' => 'bg-light text-primary border border-primary-subtle fw-semibold',
                                                'text'  => truncateText($cleanSize, 18),
                                                'title' => 'Apartment Size: ' . $cleanSize
                                            ];
                                        }
                                        ?>
                                        <?php if (!empty($prefItems)): ?>
                                            <div class="d-flex flex-wrap gap-1 align-items-center" style="max-width: 220px;">
                                                <?php foreach ($prefItems as $item): ?>
                                                    <span class="badge <?= $item['class'] ?> px-2 py-1" 
                                                          style="font-size: 11px; white-space: nowrap; line-height: 1.2;" 
                                                          title="<?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>">
                                                        <i class="bi <?= $item['icon'] ?>"></i><?= htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- 8. Project Campaign -->
                                    <td>
                                        <?php
                                        $displayName = !empty($lead['campaign_name'])
                                            ? $lead['campaign_name']
                                            : (!empty($lead['project_name']) ? $lead['project_name'] : '-');
                                        if (!empty($lead['project_category_name'])) {
                                            $displayName .= ' (' . $lead['project_category_name'] . ')';
                                        }
                                        $plainText = strip_tags($displayName ?? '');
                                        ?>
                                        <div class="d-flex align-items-center gap-2 flex-nowrap">
                                            <span class="project-badge mb-0">
                                                <i class="bi bi-building"></i>
                                                <?= truncateText($displayName, 15) ?>
                                            </span>
                                            <?php if (mb_strlen($plainText) > 15): ?>
                                                <button type="button"
                                                    id="viewCampaignBtn<?= $lead['id'] ?>"
                                                    class="btn btn-sm btn-outline-primary flex-shrink-0 viewCampaignBtnDynamic"
                                                    data-description="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#viewProjectCampaignName">
                                                    View
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <!-- 9. Status (Action Select + Lead Status Badge) -->
                                    <td>
                                        <?php
                                        $last_action = $lead['last_lead_action'] ?? 'not_called';
                                        
                                        // Robust Lead Status Resolution
                                        $statusVal = trim($lead['last_follow_up_lead_status'] ?? '');
                                        
                                        // Check follow-up history if latest follow-up had empty lead status
                                        if (($statusVal === '' || $statusVal === '-' || $statusVal === '—') && !empty($followUpHistories[$lead['id']])) {
                                            foreach ($followUpHistories[$lead['id']] as $fuItem) {
                                                if (!empty($fuItem['lead_status']) && trim($fuItem['lead_status']) !== '' && trim($fuItem['lead_status']) !== '-') {
                                                    $statusVal = trim($fuItem['lead_status']);
                                                    break;
                                                }
                                            }
                                        }
                                        
                                        // Check leads.lead_type
                                        if ($statusVal === '' || $statusVal === '-' || $statusVal === '—') {
                                            $statusVal = trim($lead['lead_type'] ?? '');
                                        }
                                        
                                        // Check follow-up status (e.g. pending, completed)
                                        if (($statusVal === '' || $statusVal === '-' || $statusVal === '—') && !empty($lead['last_follow_up_status'])) {
                                            $statusVal = ucfirst(trim($lead['last_follow_up_status']));
                                        }
                                        
                                        // Fallback default
                                        if ($statusVal === '' || $statusVal === '-' || $statusVal === '—') {
                                            $statusVal = 'Pending';
                                        }
                                        
                                        $badgeClass = 'bg-secondary text-white';
                                        if (in_array($statusVal, ['High Potential', 'High Prospect', 'Hot'])) {
                                            $badgeClass = 'bg-danger text-white';
                                        } elseif ($statusVal === 'Qualification in Progress') {
                                            $badgeClass = 'bg-info text-white';
                                        } elseif (in_array($statusVal, ['Potential', 'Prospect'])) {
                                            $badgeClass = 'bg-success text-white';
                                        } elseif (in_array($statusVal, ['Visit Scheduled', 'Will Visit'])) {
                                            $badgeClass = 'bg-info text-dark';
                                        } elseif (in_array($statusVal, ['Visit Completed', 'Visited'])) {
                                            $badgeClass = 'bg-primary text-white';
                                        } elseif (in_array($statusVal, ['Not Interested', "Don't Call Again", 'Cold', 'Refund'])) {
                                            $badgeClass = 'bg-danger text-white';
                                        } elseif ($statusVal == 'Warm') {
                                            $badgeClass = 'bg-warning text-dark';
                                        } elseif (in_array($statusVal, ['Invalid Lead', 'Irrelevant'])) {
                                            $badgeClass = 'bg-dark text-white';
                                        } elseif ($statusVal == 'No Response') {
                                            $badgeClass = 'bg-warning text-dark';
                                        } elseif ($statusVal == 'Lead Transferred' || $statusVal == 'Project Transferred') {
                                            $badgeClass = 'bg-dark text-white';
                                        } elseif ($statusVal == 'Sold' || $statusVal == 'Completed') {
                                            $badgeClass = 'bg-success text-white';
                                        } elseif ($statusVal == 'Pending') {
                                            $badgeClass = 'bg-secondary text-white';
                                        }
                                        ?>
                                        <select name="last_lead_action" 
                                                class="form-select form-select-sm last-action-select last-action-select-ajax action-<?= str_replace(' ', '_', $last_action) ?> mb-1" 
                                                data-lead-id="<?= $lead['id'] ?>"
                                                style="min-width: 110px;">
                                            <option value="">Update action</option>
                                            <option value="contacted" <?= $last_action == 'contacted' ? 'selected' : '' ?>>Contacted</option>
                                            <option value="new" <?= $last_action == 'new' ? 'selected' : '' ?>>New</option>
                                            <option value="booked" <?= $last_action == 'booked' ? 'selected' : '' ?>>Booked</option>
                                            <option value="follow up" <?= $last_action == 'follow up' ? 'selected' : '' ?>>Follow Up</option>
                                            <option value="visited" <?= $last_action == 'visited' ? 'selected' : '' ?>>Visited</option>
                                            <option value="cancel" <?= $last_action == 'cancel' ? 'selected' : '' ?>>Cancel</option>
                                        </select>
                                        <div class="text-center lead-status-badge-wrap" data-lead-id="<?= $lead['id'] ?>">
                                            <span class="badge <?= $badgeClass ?> px-2 py-1" style="font-size: 10px;"><?= htmlspecialchars($statusVal) ?></span>
                                        </div>
                                    </td>

                                    <!-- 10. Next Follow-Up -->
                                    <td>
                                        <div class="d-flex align-items-center gap-2 flex-nowrap">
                                            <div class="next-followup-badge">
                                                <i class="bi bi-calendar-check me-1"></i>
                                                <span class="next-date-display"><?= formatDate($lead['next_follow_up_date'] ?? 'No Schedule') ?></span>
                                            </div>
                                            <div class="position-relative d-inline-block">
                                                <button type="button" 
                                                        class="btn btn-sm rounded-circle d-flex align-items-center justify-content-center border-0 shadow-sm"
                                                        style="width: 30px; height: 30px; background-color: #e0f2fe; color: #0284c7; cursor: pointer;"
                                                        onclick="if(this.nextElementSibling.showPicker){this.nextElementSibling.showPicker()}else{this.nextElementSibling.click()}"
                                                        title="Click to view / pick calendar date">
                                                    <i class="bi bi-calendar3" style="font-size: 12px;"></i>
                                                </button>
                                                <input type="date" 
                                                       class="position-absolute start-0 top-0 opacity-0 pe-none"
                                                       style="width: 1px; height: 1px;"
                                                       value="<?= (!empty($lead['next_follow_up_date']) && $lead['next_follow_up_date'] !== '0000-00-00') ? date('Y-m-d', strtotime($lead['next_follow_up_date'])) : '' ?>"
                                                       onchange="if(this.value){ const dt = new Date(this.value); const formatted = dt.toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'numeric'}); this.closest('.d-flex').querySelector('.next-date-display').textContent = formatted; }">
                                            </div>
                                        </div>
                                    </td>

                                    <!-- 13. Action -->
                                    <td>
                                        <div class="d-flex align-items-center gap-1 flex-nowrap">
                                            <!-- Follow Up -->
                                            <button type="button" 
                                                class="btn btn-primary btn-sm position-relative create-followup-btn"
                                                style="white-space:nowrap;"
                                                data-bs-toggle="modal"
                                                data-bs-target="#createFollowUpModal"
                                                data-lead-id="<?= $lead['id'] ?>">
                                                Follow Up
                                                <?php if (!empty($followUpCounts[$lead['id']])): ?>
                                                    <span class="action-badge">
                                                        <?= $followUpCounts[$lead['id']] ?>
                                                    </span>
                                                <?php endif; ?>
                                            </button>

                                            <!-- Follow Up History -->
                                            <button type="button" class="btn btn-info btn-sm position-relative ms-1 view-history-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#followupHistoryModal"
                                                    data-lead-id="<?= $lead['id'] ?>"
                                                    data-history="<?= htmlspecialchars(json_encode($followUpHistories[$lead['id']] ?? []), ENT_QUOTES, 'UTF-8') ?>"
                                                    style="white-space:nowrap; padding: 0.25rem 0.5rem; color: #fff;">
                                                <i class="bi bi-clock-history"></i> History
                                            </button>

                                            <!-- Booking -->
                                            <a href="<?= $GLOBALS['base_url'] ?>/booking-leads/<?= $lead['id'] ?>"
                                                class="btn btn-secondary btn-sm position-relative ms-1"
                                                style="white-space:nowrap;">
                                                <?= !empty($bookingCount[$lead['id']]) ? 'Booked' : 'Booking' ?>
                                                <span class="action-badge">
                                                    <?= $bookingCount[$lead['id']] ?? 0 ?>
                                                </span>
                                            </a>

                                            <!-- Delete Request -->
                                            <?php if (!empty($deleteRequests[$lead['id']])): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1 px-2 py-1.5 d-inline-flex align-items-center" 
                                                      title="Delete Reason: <?= htmlspecialchars($deleteRequests[$lead['id']]['reason'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                                      style="font-size: 11px; white-space: nowrap;">
                                                    <i class="fa-solid fa-clock-rotate-left me-1"></i> Requested
                                                </span>
                                            <?php else: ?>
                                                <button type="button" 
                                                    class="btn btn-outline-danger btn-sm ms-1 request-delete-btn"
                                                    style="white-space:nowrap;"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteRequestModal"
                                                    data-lead-id="<?= $lead['id'] ?>"
                                                    data-lead-name="<?= htmlspecialchars($lead['full_name'] ?? 'Lead', ENT_QUOTES, 'UTF-8') ?>"
                                                    title="Request Lead Deletion">
                                                    <i class="fa-solid fa-trash-can me-1"></i> Delete Request
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3 mb-2">
                    <div class="text-muted small">
                        <?php 
                        $totalTableCount = $totalTableLeads ?? count($myAllleads);
                        $startCount = $totalTableCount > 0 ? (($offset ?? 0) + 1) : 0;
                        $endCount = min((($offset ?? 0) + count($myAllleads)), $totalTableCount);
                        ?>
                        Showing <?= $startCount ?> to <?= $endCount ?> of <?= number_format($totalTableCount) ?> entries
                    </div>
                    <?php if (isset($totalPages) && $totalPages > 1 && ($limit ?? 25) !== -1): ?>
                    <div>
                        <?= paginateLinksSecondary($paginationRoute ?? ($base_url . "/my-all-assigned-leads"), $currentPage, $totalPages); ?>
                    </div>
                    <?php endif; ?>
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

                    <!-- Date Type Select -->
                    <select class="form-select form-select-sm" name="date_type">
                        <option value="lead_date" <?= (($date_type ?? 'lead_date') === 'lead_date') ? 'selected' : '' ?>>Date: Lead Date</option>
                        <option value="assigned_date" <?= (($date_type ?? '') === 'assigned_date') ? 'selected' : '' ?>>Date: Assigned Date</option>
                    </select>

                    <!-- From Date Filter -->
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-calendar me-1"></i> From</span>
                        <input type="date" name="from_date" class="form-control form-control-sm" value="<?= htmlspecialchars($_GET['from_date'] ?? '') ?>">
                    </div>

                    <!-- To Date Filter -->
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-calendar me-1"></i> To</span>
                        <input type="date" name="to_date" class="form-control form-control-sm" value="<?= htmlspecialchars($_GET['to_date'] ?? '') ?>">
                    </div>

                    <!-- Uncontacted Filter -->
                    <div class="form-check mt-1">
                        <input class="form-check-input" type="checkbox" name="uncontacted" id="uncontactedFilterMobile" value="1" <?= isset($_GET['uncontacted']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="uncontactedFilterMobile" style="font-size: 12px; font-weight: bold;">
                            Uncontacted Only
                        </label>
                    </div>

                    <!-- Follow Up Date Filter -->
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-calendar-event me-1"></i> Next Follow-Up</span>
                        <input type="date" name="followup_date" class="form-control form-control-sm" value="<?= htmlspecialchars($followup_date ?? '') ?>">
                    </div>

                    <!-- Project Filter -->
                    <?php if (!empty($projects)): ?>
                        <?php $selectedProjectMobile = $_GET['project_id'] ?? ''; ?>
                        <select class="form-select form-select-sm" name="project_id">
                            <option value="">Project: All</option>
                            <?php foreach ($projects as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= $selectedProjectMobile == $p['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>

                    <!-- Category Filter -->
                    <?php if (!empty($projectCategories)): ?>
                        <?php $selectedCategoryMobile = $_GET['project_category_id'] ?? ''; ?>
                        <select class="form-select form-select-sm" name="project_category_id">
                            <option value="">Category: All</option>
                            <?php foreach ($projectCategories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $selectedCategoryMobile == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>

                    <!-- Year Select -->
                    <?php
                    $currentYear = date('Y');
                    $startYear   = $currentYear - 10;
                    $selectedYear = $_GET['year'] ?? '';
                    ?>
                    <select class="form-select form-select-sm" name="year">
                        <option value="">Year Select</option>
                        <?php for ($yearVal = $currentYear; $yearVal >= $startYear; $yearVal--): ?>
                            <option value="<?= $yearVal ?>" <?= $selectedYear == $yearVal ? 'selected' : '' ?>><?= $yearVal ?></option>
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
                    <select class="form-select form-select-sm" name="month">
                        <option value="">Month Select</option>
                        <?php foreach ($months as $id => $name): ?>
                            <option value="<?= $id ?>" <?= $selectedMonth == $id ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Status / Action Select -->
                    <select class="form-select form-select-sm" name="last_action">
                        <option value="">Client Action: All</option>
                        <option value="Contacted" <?= strcasecmp(($selectedAction ?? ''), 'contacted') === 0 ? 'selected' : '' ?>>Contacted</option>
                        <option value="Follow Up" <?= strcasecmp(($selectedAction ?? ''), 'follow up') === 0 ? 'selected' : '' ?>>Follow Up</option>
                        <option value="Visited" <?= strcasecmp(($selectedAction ?? ''), 'visited') === 0 ? 'selected' : '' ?>>Visited</option>
                        <option value="Booked" <?= strcasecmp(($selectedAction ?? ''), 'booked') === 0 ? 'selected' : '' ?>>Booked</option>
                        <option value="New" <?= strcasecmp(($selectedAction ?? ''), 'new') === 0 ? 'selected' : '' ?>>New</option>
                        <option value="Cancel" <?= strcasecmp(($selectedAction ?? ''), 'cancel') === 0 ? 'selected' : '' ?>>Cancel</option>
                    </select>

                    <!-- Lead Status Select -->
                    <select class="form-select form-select-sm" name="lead_status">
                        <option value="">Lead Status: All</option>
                        <?php
                        $statuses = [
                            'No Response', 'Pending', 'Qualification in Progress', 'Potential', 'High Potential', 
                            'Visit Scheduled', 'Visit Completed', 'Sold', 'Lead Transferred', 
                            'Project Transferred', 'Not Interested', 
                            'Refund', 'Future', 'Invalid Lead'
                        ];
                        foreach($statuses as $st) {
                            $selected = ($selectedLeadStatus ?? '') == $st ? 'selected' : '';
                            echo "<option value=\"$st\" $selected>$st</option>";
                        }
                        ?>
                    </select>

                    <!-- Latest Assigned Hours Filter -->
                    <select class="form-select form-select-sm" name="assigned_hours" title="Latest Assigned Leads within Hours">
                        <option value="">Assigned Within: All</option>
                        <option value="1" <?= ($_GET['assigned_hours'] ?? '') == '1' ? 'selected' : '' ?>>Last 1 Hour</option>
                        <option value="2" <?= ($_GET['assigned_hours'] ?? '') == '2' ? 'selected' : '' ?>>Last 2 Hours</option>
                        <option value="3" <?= ($_GET['assigned_hours'] ?? '') == '3' ? 'selected' : '' ?>>Last 3 Hours</option>
                        <option value="4" <?= ($_GET['assigned_hours'] ?? '') == '4' ? 'selected' : '' ?>>Last 4 Hours</option>
                        <option value="5" <?= ($_GET['assigned_hours'] ?? '') == '5' ? 'selected' : '' ?>>Last 5 Hours</option>
                        <option value="6" <?= ($_GET['assigned_hours'] ?? '') == '6' ? 'selected' : '' ?>>Last 6 Hours</option>
                        <option value="12" <?= ($_GET['assigned_hours'] ?? '') == '12' ? 'selected' : '' ?>>Last 12 Hours</option>
                        <option value="24" <?= ($_GET['assigned_hours'] ?? '') == '24' ? 'selected' : '' ?>>Last 24 Hours</option>
                    </select>

                    <?php if (!empty($filterCard)): ?>
                        <input type="hidden" name="filter_card" value="<?= htmlspecialchars($filterCard) ?>">
                    <?php endif; ?>
                    <?php if (!empty($_GET['filter'])): ?>
                        <input type="hidden" name="filter" value="<?= htmlspecialchars($_GET['filter']) ?>">
                    <?php endif; ?>
                    <?php if (!empty($search)): ?>
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                    <?php endif; ?>
                    <?php if (isset($_GET['limit'])): ?>
                        <input type="hidden" name="limit" value="<?= htmlspecialchars($_GET['limit']) ?>">
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <button type="submit" class="btn btn-outline-secondary btn-sm flex-grow-1"><i class="bi bi-funnel me-1"></i> Filter</button>
                        <a href="<?= $GLOBALS['base_url'] ?>/my-all-assigned-leads" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-arrow-counterclockwise me-1"></i> Reset</a>
                    </div>

                </div>
            </form>
        </div>
        <div id="assignedLeadsMobileCards">
        <?php if (empty($myAllleads)): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                <span>No matching leads found.</span>
            </div>
        <?php endif; ?>
        <?php foreach ($myAllleads as $key => $lead): ?>
            <?php
            $displayName = !empty($lead['campaign_name'])
                ? $lead['campaign_name']
                : (!empty($lead['project_name']) ? $lead['project_name'] : '-');
            if (!empty($lead['project_category_name'])) {
                $displayName .= ' (' . $lead['project_category_name'] . ')';
            }
            $plainText = strip_tags($displayName ?? '');

            $cleanPhone = preg_replace('/[^0-9]/', '', $lead['phone_number'] ?? $lead['phone'] ?? '');
            $cleanEmail = trim(strtolower($lead['email'] ?? ''));
            $leadDuplicates = [];
            $dupIds = [];
            if ($cleanPhone !== '' && isset($phoneMap[$cleanPhone])) {
                foreach ($phoneMap[$cleanPhone] as $item) {
                    if (!in_array($item['id'], $dupIds)) {
                        $leadDuplicates[] = $item;
                        $dupIds[] = $item['id'];
                    }
                }
            }
            if ($cleanEmail !== '' && isset($emailMap[$cleanEmail])) {
                foreach ($emailMap[$cleanEmail] as $item) {
                    if (!in_array($item['id'], $dupIds)) {
                        $leadDuplicates[] = $item;
                        $dupIds[] = $item['id'];
                    }
                }
            }
            $isDup = count($leadDuplicates) > 1;
            ?>
            <!-- Lead Card -->
            <div id="lead-<?= $lead['id'] ?>" class="lead-card mb-3" data-id="<?= $lead['id'] ?>" data-is-duplicate="<?= $isDup ? '1' : '0' ?>">
                <div class="lead-card-header d-flex justify-content-between align-items-start gap-1">
                    <div class="flex-grow-1 overflow-hidden">
                        <span class="lead-name text-truncate"><?= htmlspecialchars($lead['full_name'] ?? '') ?></span>
                        <?php if ($isDup): ?>
                            <span class="badge bg-danger cursor-pointer duplicate-badge-click" 
                                  data-duplicates="<?= htmlspecialchars(json_encode($leadDuplicates), ENT_QUOTES, 'UTF-8') ?>"
                                  data-current-lead-id="<?= $lead['id'] ?>"
                                  style="cursor: pointer; font-size: 10px; padding: 2px 4px;"
                                  title="Click to view duplicate history">
                                Dup (<?= count($leadDuplicates) ?>)
                            </span>
                        <?php endif; ?>
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
                        <?php 
                        $mobTime = trim($lead['when_is_the_best_time_to_contact_you'] ?? $lead['preferred_contact_time'] ?? '');
                        if ($mobTime !== '' && $mobTime !== '-' && $mobTime !== '—'): 
                        ?>
                        <div class="text-info small mt-1">
                            <i class="bi bi-clock me-1"></i>Best Contact Time: <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $mobTime))) ?>
                        </div>
                        <?php endif; ?>
                        <?php 
                        $mobPlan = trim($lead['when_are_you_planning_to_buy'] ?? '');
                        if ($mobPlan !== '' && $mobPlan !== '-' && $mobPlan !== '—'): 
                        ?>
                        <div class="text-muted small mt-1">
                            <i class="bi bi-calendar3 me-1 text-primary"></i>Plan: <span class="badge bg-light text-dark border"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $mobPlan))) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php 
                        $mobVisit = trim($lead['would_you_like_to_schedule_a_site_visit'] ?? '');
                        if ($mobVisit !== '' && $mobVisit !== '-' && $mobVisit !== '—'): 
                        ?>
                        <div class="text-muted small mt-1">
                            <i class="bi bi-geo-alt me-1 text-success"></i>Visit: <span class="badge bg-light text-success border border-success-subtle"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $mobVisit))) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php 
                        $mobApt = trim($lead['which_apartment_size_are_you_interested_in'] ?? $lead['pref_flat_size'] ?? '');
                        if ($mobApt !== '' && $mobApt !== '-' && $mobApt !== '—'): 
                        ?>
                        <div class="text-muted small mt-1">
                            <i class="bi bi-aspect-ratio me-1 text-primary"></i>Size: <span class="badge bg-light text-primary border border-primary-subtle"><?= htmlspecialchars(str_replace('_', ' ', $mobApt)) ?></span>
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
                        <?php
                        $mAssignedRaw = (!empty($lead['assigned_at']) && $lead['assigned_at'] !== '0000-00-00 00:00:00' && $lead['assigned_at'] !== '0000-00-00')
                            ? $lead['assigned_at']
                            : (!empty($lead['updated_at']) && $lead['updated_at'] !== '0000-00-00 00:00:00' ? $lead['updated_at'] : $lead['created_at']);
                        ?>
                        <?php if ($mAssignedRaw): ?>
                        <div class="text-muted small mt-1">
                            <i class="bi bi-calendar-event me-1"></i>Assigned: <?= date('d M, Y, h:i A', strtotime($mAssignedRaw)) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Status & Action -->
                <div class="mt-2">
                    <div class="info-label">Status & Action</div>


                    <div class="d-flex flex-column gap-2">
                        <button type="button" 
                            class="btn btn-primary btn-sm w-100 d-flex justify-content-between align-items-center px-2 create-followup-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#createFollowUpModal"
                            data-lead-id="<?= $lead['id'] ?>">
                            <span>Follow Up</span>
                            <?php if (!empty($followUpCounts[$lead['id']])): ?>
                                <span class="action-badge badge bg-white text-dark rounded-circle" style="font-size: 10px; width: 18px; height: 18px;  align-items: center; justify-content: center;"><?= $followUpCounts[$lead['id']] ?></span>
                            <?php endif; ?>
                        </button>

                        <button type="button" class="btn btn-info btn-sm w-100 d-flex justify-content-center align-items-center px-2 view-history-btn" 
                                data-bs-toggle="modal" 
                                data-bs-target="#followupHistoryModal"
                                data-lead-id="<?= $lead['id'] ?>"
                                data-history="<?= htmlspecialchars(json_encode($followUpHistories[$lead['id']] ?? []), ENT_QUOTES, 'UTF-8') ?>"
                                style="color: #fff;">
                            <i class="bi bi-clock-history me-1"></i> History
                        </button>

                        <a href="<?= $GLOBALS['base_url'] ?>/booking-leads/<?= $lead['id'] ?>" class="btn btn-secondary btn-sm w-100 d-flex justify-content-between align-items-center px-2">
                            <span>Booking</span>
                            <?php if (!empty($bookingCount[$lead['id']])): ?>
                                <span class="badge bg-white text-dark rounded-circle" style="font-size: 10px; width: 18px; height: 18px;  align-items: center; justify-content: center;"><?= $bookingCount[$lead['id']] ?></span>
                            <?php endif; ?>
                        </a>

                        <?php if (!empty($deleteRequests[$lead['id']])): ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle w-100 py-2 d-flex justify-content-center align-items-center"
                                  title="Delete Reason: <?= htmlspecialchars($deleteRequests[$lead['id']]['reason'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                  style="font-size: 11px;">
                                <i class="fa-solid fa-clock-rotate-left me-1"></i> Delete Requested
                            </span>
                        <?php else: ?>
                            <button type="button" 
                                class="btn btn-outline-danger btn-sm w-100 d-flex justify-content-center align-items-center px-2 request-delete-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#deleteRequestModal"
                                data-lead-id="<?= $lead['id'] ?>"
                                data-lead-name="<?= htmlspecialchars($lead['full_name'] ?? 'Lead', ENT_QUOTES, 'UTF-8') ?>">
                                <i class="fa-solid fa-trash-can me-1"></i> Delete Request
                            </button>
                        <?php endif; ?>
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
                    <div class="info-label">Last Lead Status</div>
                    <?php
                    $mStatusVal = trim($lead['last_follow_up_lead_status'] ?? '');
                    if (($mStatusVal === '' || $mStatusVal === '-' || $mStatusVal === '—') && !empty($followUpHistories[$lead['id']])) {
                        foreach ($followUpHistories[$lead['id']] as $fuItem) {
                            if (!empty($fuItem['lead_status']) && trim($fuItem['lead_status']) !== '' && trim($fuItem['lead_status']) !== '-') {
                                $mStatusVal = trim($fuItem['lead_status']);
                                break;
                            }
                        }
                    }
                    if ($mStatusVal === '' || $mStatusVal === '-' || $mStatusVal === '—') {
                        $mStatusVal = trim($lead['lead_type'] ?? '');
                    }
                    if (($mStatusVal === '' || $mStatusVal === '-' || $mStatusVal === '—') && !empty($lead['last_follow_up_status'])) {
                        $mStatusVal = ucfirst(trim($lead['last_follow_up_status']));
                    }
                    if ($mStatusVal === '' || $mStatusVal === '-' || $mStatusVal === '—') {
                        $mStatusVal = 'Pending';
                    }
                    
                    $statusClass = 'bg-secondary text-white';
                    if (in_array($mStatusVal, ['High Potential', 'High Prospect', 'Hot'])) {
                        $statusClass = 'bg-danger text-white';
                    } elseif ($mStatusVal === 'Qualification in Progress') {
                        $statusClass = 'bg-info text-white';
                    } elseif (in_array($mStatusVal, ['Potential', 'Prospect'])) {
                        $statusClass = 'bg-success text-white';
                    } elseif (in_array($mStatusVal, ['Visit Scheduled', 'Will Visit'])) {
                        $statusClass = 'bg-info text-dark';
                    } elseif (in_array($mStatusVal, ['Visit Completed', 'Visited'])) {
                        $statusClass = 'bg-primary text-white';
                    } elseif (in_array($mStatusVal, ['Not Interested', "Don't Call Again", 'Cold', 'Refund'])) {
                        $statusClass = 'bg-danger text-white';
                    } elseif ($mStatusVal === 'Warm') {
                        $statusClass = 'bg-warning text-dark';
                    } elseif ($mStatusVal === 'No Response') {
                        $statusClass = 'bg-warning text-dark';
                    } elseif (in_array($mStatusVal, ['Invalid Lead', 'Irrelevant'])) {
                        $statusClass = 'bg-dark text-white';
                    }
                    ?>
                    <input type="text"
                        class="<?= $statusClass ?> form-control form-control-sm select-form mt-1 text-center"
                        value="<?= htmlspecialchars($mStatusVal) ?>"
                        readonly style="cursor: default; pointer-events: none;">
                </div>

                <!-- Transfer Leads Badge & Transfer From -->
                <?php if (($lead['last_follow_up_lead_status'] ?? '') === 'Lead Transferred'): ?>
                <div class="mt-2">
                    <span class="badge bg-warning text-dark px-2 py-1" style="font-size: 0.7rem;">
                        <i class="bi bi-arrow-left-right me-1"></i>Transferred
                    </span>
                    <?php if (!empty($lead['transfer_from_name'])): ?>
                    <div class="text-muted small mt-1">
                        <i class="bi bi-person-fill me-1"></i>From: <strong><?= htmlspecialchars($lead['transfer_from_name']) ?></strong>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

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

        <?php if (isset($totalPages) && $totalPages > 1): ?>
        <div class="d-flex justify-content-center mt-3 mb-2">
            <?= paginateLinksSecondary($paginationRoute ?? ($base_url . "/my-all-assigned-leads"), $currentPage, $totalPages); ?>
        </div>
        <?php endif; ?>
        </div>

        <!-- FAB Button -->
        <button class="fab-button d-none">
            <i class="bi bi-plus-lg"></i>
        </button>
    </div>


</div>

<?php if ((isset($_SESSION['user']['role_id']) && in_array($_SESSION['user']['role_id'], [1, 3, 5, 9, 33])) || isSalesCoLeader()): ?>
    <!-- Mobile Assign Card (sticky bottom) -->
    <div class="mobile-only mb-2" id="assignCardMobile" style="display:none; position:sticky; bottom:0; z-index:1000; background:#fff; padding:10px; border-top:1px solid #ccc;">
        <div class="text-center small mb-1 fw-bold text-primary">Selected: <span id="selectedCountMobile">0</span> leads</div>
        <div class="d-flex flex-column gap-2">
            <select id="assignUserSelectMobile" class="form-select form-select-sm select2">
                <option value="">Select User</option>
                <?php foreach ($teamMembers as $user): ?>
                    <option value="<?= $user['id'] ?>">
                        <?= htmlspecialchars($user['full_name']) ?> - <?= htmlspecialchars($user['role_name']) ?>
                    </option>
                <?php endforeach ?>
            </select>
            <input type="date" id="assignedDateMobile" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
            <button type="button" id="assignUserBtnMobile" class="btn btn-primary btn-sm w-100"><i class="fa-solid fa-user-plus me-1"></i> Assign</button>
            <div id="assignFeedbackMobile" class="text-success text-center fw-bold small mt-1" style="display:none;">Successfully Assigned!</div>
        </div>
    </div>
<?php endif; ?>

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

<!-- View Call Note Modal -->
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
    const resetBtn = form ? form.querySelector('button[type="reset"]') : null;

    if (resetBtn) {
        resetBtn.addEventListener('click', function(e) {
            e.preventDefault(); // avoid default reset
            // Clear all inputs
            form.querySelectorAll('input, select').forEach(function(el) {
                if (el.tagName === 'SELECT') el.selectedIndex = 0; // first option select
                else el.value = ''; // input clear
            });
        });
    }


    // campaign full show
</script>

<script>
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
<style>
.swal2-container {
    z-index: 99999 !important;
}
</style>
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
                        <?php if ($ri === $oldestIndex): ?>
                          <div class="mt-1">
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.65rem; font-weight: bold;">
                              <i class="bi bi-clock-history me-1"></i>First Assigned
                            </span>
                          </div>
                        <?php endif; ?>
                      <?php else: ?>—<?php endif; ?>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    $(document).on('change', '.last-action-select-ajax', function() {
        const selectEl = $(this);
        const leadId = selectEl.data('lead-id');
        const action = selectEl.val();
        const url = (typeof base_url !== 'undefined' ? base_url : window.base_url || "<?= $GLOBALS['base_url'] ?>") + '/update-last_leadAction/' + leadId;

        const formData = new URLSearchParams();
        formData.append('last_lead_action', action);
        formData.append('ajax', '1');

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData.toString()
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Remove existing classes starting with 'action-'
                selectEl.removeClass(function (index, className) {
                    return (className.match(/(^|\s)action-\S+/g) || []).join(' ');
                });
                // Add new action class
                const classVal = action.replace(' ', '_');
                selectEl.addClass('action-' + (classVal || 'not_called'));

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: data.message || 'Action updated successfully!',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true
                    });
                }
            } else {
                throw new Error(data.message || 'Update failed');
            }
        })
        .catch(err => {
            console.error('Error updating action:', err);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Failed to update action!',
                    text: err.message,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            }
            setTimeout(() => location.reload(), 1500);
        });
    });
});
</script>

<!-- Duplicate Leads Details Modal -->
<div id="duplicateLeadsModal" class="modal custom-modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="fa-solid fa-clone me-2"></i>Duplicate Lead History</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Below are the leads assigned to you with the same phone number or email address.</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="duplicateLeadsModalTable">
                        <thead>
                            <tr class="bg-light">
                                <th>Lead ID</th>
                                <th>Name</th>
                                <th>Assigned Date</th>
                                <th>Project</th>
                                <th>Action Status</th>
                                <th>Lead Status</th>
                            </tr>
                        </thead>
                        <tbody id="duplicateLeadsModalBody">
                            <!-- Populated via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Follow Up Notes Modal -->

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Duplicate leads modal handler
        $(document).on('click', '.duplicate-badge-click', function() {
            const duplicates = $(this).data('duplicates');
            const currentLeadId = $(this).data('current-lead-id');
            let html = '';

            function escapeHtml(text) {
                if (!text) return '';
                return text.toString()
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }

            if (duplicates && duplicates.length > 0) {
                duplicates.forEach(function(item) {
                    const isCurrent = (item.id == currentLeadId) ? 'table-warning fw-bold' : '';
                    const currentBadge = (item.id == currentLeadId) ? ' <span class="badge bg-warning text-dark ms-1">Current</span>' : '';
                    html += `
                        <tr class="${isCurrent}">
                            <td>#${item.id}${currentBadge}</td>
                            <td>${escapeHtml(item.full_name)}</td>
                            <td>${item.created_at}</td>
                            <td>${escapeHtml(item.project_name)}</td>
                            <td><span class="badge bg-secondary">${escapeHtml(item.last_lead_action)}</span></td>
                            <td><span class="badge bg-info">${escapeHtml(item.lead_type)}</span></td>
                        </tr>
                    `;
                });
            } else {
                html = '<tr><td colspan="6" class="text-center">No duplicate details found.</td></tr>';
            }
            $('#duplicateLeadsModalBody').html(html);
            $('#duplicateLeadsModal').modal('show');
        });
        // History Modal Show handler
        $('#followupHistoryModal').on('show.bs.modal', function(event) {
            const button = $(event.relatedTarget); // Button that triggered the modal
            const tbody = $('#followUpHistoryBody');
            
            let historyDataRaw = button.attr('data-history');
            let historyData = [];
            
            if (historyDataRaw) {
                try {
                    historyData = JSON.parse(historyDataRaw);
                } catch(e) {
                    console.error("Failed to parse history JSON", e);
                }
            }
            
            if (Array.isArray(historyData) && historyData.length > 0) {
                console.log("Found history:", historyData.length);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'info',
                        title: 'History Count',
                        text: "Total " + historyData.length + " follow-up(s) found for this lead.",
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                } else {
                    alert("Total " + historyData.length + " follow-up(s) found for this lead.");
                }
            } else {
                console.log("No history found");
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No History',
                        text: "No follow-ups found for this lead.",
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                } else {
                    alert("No follow-ups found for this lead.");
                }
            }
            
            let html = '';
            
            if (Array.isArray(historyData) && historyData.length > 0) {
                const currentUserId = <?= (int)($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0) ?>;
                const currentUserRole = <?= (int)($_SESSION['user']['role_id'] ?? 0) ?>;
                const currentLeadId = button.attr('data-lead-id');

                historyData.forEach(function(item, index) {
                    const canEdit = (currentUserId && Number(item.created_by) === Number(currentUserId)) || (currentUserRole === 1);
                    let actionHtml = '';
                    if (canEdit) {
                        actionHtml = `<button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 history-modal-edit-btn" data-fuid="${item.id}" data-lead-id="${item.lead_id || currentLeadId}" title="Edit this follow up">
                            <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                        </button>`;
                    } else {
                        actionHtml = `<span class="badge bg-light text-muted border py-1 px-2" title="Only creator can edit"><i class="fa-solid fa-lock me-1"></i> No Access</span>`;
                    }

                    html += `<tr>
                        <td>${index + 1}</td>
                        <td>${escapeHtml(item.full_name || '')}</td>
                        <td>${escapeHtml(item.project_name || '')}</td>
                        <td>${escapeHtml(item.flat_name || '')}</td>
                        <td>${item.follow_up_date || ''}</td>
                        <td>${item.follow_up_time || ''}</td>
                        <td>${escapeHtml(item.follow_up_type || '')}</td>
                        <td>${escapeHtml(item.discussion_note || '')}</td>
                        <td>${item.next_follow_up_date || ''}</td>
                        <td>${escapeHtml(item.next_action || '')}</td>
                        <td class="text-center">${actionHtml}</td>
                    </tr>`;
                });
            } else {
                html = '<tr><td colspan="11" class="text-center text-danger">No follow-ups found for this lead.</td></tr>';
            }
            
            tbody.html(html);
        });

        // Edit button click inside Follow Up History Modal
        $(document).on('click', '.history-modal-edit-btn', function() {
            const fuid = $(this).data('fuid');
            const leadId = $(this).data('lead-id');
            $('#followupHistoryModal').modal('hide');
            setTimeout(function() {
                $('#createFollowUpLeadId').val(leadId);
                $('#createFollowUpModal').modal('show');
                let attempts = 0;
                let checkInterval = setInterval(function() {
                    attempts++;
                    let btn = $(`.edit-modal-followup-btn[data-fuid="${fuid}"]`);
                    if (btn.length > 0) {
                        clearInterval(checkInterval);
                        btn.trigger('click');
                        return;
                    }
                    if (attempts > 30) clearInterval(checkInterval);
                }, 100);
            }, 400);
        });
    });
</script>

<!-- Follow Up History Modal -->
<div class="modal custom-modal fade" id="followupHistoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-list-task me-2"></i> Follow Up History</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" style="white-space: nowrap;">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>Lead</th>
                                <th>Project</th>
                                <th>Flat</th>
                                <th>Follow Up Date</th>
                                <th>Follow Up Time</th>
                                <th>Type</th>
                                <th>Discussion Note</th>
                                <th>Next Follow Up</th>
                                <th>Next Action</th>
                                <th class="text-center" style="min-width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="followUpHistoryBody">
                            <tr>
                                <td colspan="11" class="text-center text-muted">Loading history...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Request Modal -->
<div class="modal custom-modal fade" id="deleteRequestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title text-white"><i class="fa-solid fa-trash-can me-2"></i> Request Lead Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deleteRequestForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="lead_id" id="delete_request_lead_id">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Lead Name</label>
                        <div class="fw-bold text-dark fs-6" id="delete_request_lead_name">—</div>
                    </div>
                    <div class="mb-3">
                        <label for="delete_request_reason" class="form-label fw-semibold text-dark">
                            Reason for Deletion <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" name="reason" id="delete_request_reason" rows="4" 
                                  placeholder="Please explain in detail why this lead should be deleted (e.g. invalid number, fake lead, duplicate, testing, etc.)..." required></textarea>
                    </div>
                    <div class="alert alert-info py-2 px-3 small mb-0">
                        <i class="fa-solid fa-circle-info me-1"></i> Your deletion request will be reviewed by your Team Sales Manager / Head / Management.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm px-4" id="submitDeleteRequestBtn">
                        <i class="fa-solid fa-paper-plane me-1"></i> Submit Delete Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Follow Up Modal -->
<div class="modal custom-modal fade" id="createFollowUpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createFollowUpModalTitle"><i class="bi bi-calendar-plus me-2"></i> Create Follow Up</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <!-- Edit mode notification banner -->
                <div id="followUpEditModeAlert" class="alert alert-warning py-2 px-3 mb-3 align-items-center justify-content-between" style="display: none;">
                    <div>
                        <i class="fa-solid fa-pen-to-square me-2 text-warning-emphasis"></i>
                        <strong>Editing Follow Up #<span id="followUpEditIdDisplay"></span></strong>
                        <span class="text-muted ms-2 fs-12">(Created by: <span id="followUpEditCreatorDisplay"></span>)</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-dark py-0 px-2" id="cancelFollowUpEditBtn">
                        <i class="fa-solid fa-xmark me-1"></i> Cancel Edit
                    </button>
                </div>

                <form id="createFollowUpForm" action="<?= $GLOBALS['base_url'] ?>/store-follow-up" method="POST">
                    <input type="hidden" name="lead_id" id="createFollowUpLeadId" value="">
                    <input type="hidden" name="id" id="createFollowUpEditId" value="">
                    <input type="hidden" name="is_ajax" value="1">
                    <input type="hidden" name="assigned_with" value="<?= $_SESSION['user']['id'] ?? 1 ?>">
                    <input type="hidden" name="created_by" value="<?= $_SESSION['user_id'] ?? 1 ?>">

                    <div class="row gy-3">
                        <!-- Project -->
                        <div class="col-lg-6">
                            <label class="form__input-title">Project</label>
                            <select name="project_id" id="createFollowUpProjectSelect" class="form-select select2-no-scroll" data-placeholder="Select Project">
                                <option value="">Select Project</option>
                                <?php foreach ($projects as $project): ?>
                                    <?php 
                                        $isRestricted = in_array($project['project_sale_status'], ['Sold Out', 'Upcoming']);
                                        $statusSuffix = !empty($project['project_sale_status']) ? " ({$project['project_sale_status']})" : '';
                                    ?>
                                    <option value="<?= $project['id'] ?>" <?= $isRestricted ? 'disabled style="color: red;"' : '' ?>>
                                        <?= htmlspecialchars($project['name']) ?><?= $statusSuffix ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Flat / Unit -->
                        <div class="col-lg-6">
                            <label class="form__input-title">Flat / Unit</label>
                            <select id="createFollowUpFlatSelect" name="flat_id" class="form-select select2-no-scroll" data-placeholder="Select Flat">
                                <option value="">Select Flat</option>
                            </select>
                        </div>

                        <!-- Follow Up Date -->
                        <div class="col-lg-6">
                            <label class="form__input-title">Follow Up Date*</label>
                            <input type="date" name="follow_up_date" class="form-control" required>
                        </div>

                        <!-- Follow Up Time -->
                        <div class="col-lg-6">
                            <label class="form__input-title">Follow Up Time</label>
                            <input type="time" name="follow_up_time" class="form-control">
                        </div>

                        <!-- Follow Up Type -->
                        <div class="col-lg-6">
                            <label class="form__input-title">Follow Up Type</label>
                            <select name="follow_up_type" class="form-select">
                                <option value="">Select Type</option>
                                <option value="Inbound Call">Inbound Call</option>
                                <option value="Phone Call">Phone Call</option>
                                <option value="WA Call">WA Call</option>
                                <option value="Imo Call">Imo Call</option>
                                <option value="Messenger Call">Messenger Call</option>
                                <option value="Messenger Conversesion">Messenger Conversesion</option>
                                <option value="WA Conversesion">WA Conversesion</option>
                                <option value="WhatsApp Message">WhatsApp Message</option>
                                <option value="Imo Message">Imo Message</option>
                                <option value="Phone Massaging">Phone Massaging</option>
                                <option value="Email">Email</option>
                                <option value="Number Off">Number Off</option>
                                <option value="Wrong Number">Wrong Number</option>
                                <option value="Invalid Number">Invalid Number</option>
                                <option value="Face To Face">Face To Face</option>
                                <option value="call">Call (Legacy)</option>
                                <option value="visit">Visit (Legacy)</option>
                                <option value="meeting">Meeting (Legacy)</option>
                                <option value="whatsapp">WhatsApp (Legacy)</option>
                                <option value="Email">Email (Legacy)</option>
                            </select>
                        </div>

                        <!-- Lead Status -->
                        <div class="col-lg-6">
                            <label class="form__input-title">Lead Status</label>
                            <select name="lead_status" id="createModalLeadStatus" class="form-select">
                                <option value="">Select Status</option>
                                <?php
                                $statuses = [
                                    'No Response', 'Pending', 'Qualification in Progress', 'Potential', 'High Potential', 
                                    'Visit Scheduled', 'Visit Completed', 'Lead Transferred', 
                                    'Not Interested', 'Invalid Lead'
                                ];
                                foreach($statuses as $st) {
                                    echo "<option value=\"$st\">$st</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <!-- Team Re-assign (Hidden by default) -->
                        <div class="col-lg-6" id="createModalReassignTeamDiv" style="display: none;">
                            <label class="form__input-title">Re-assigned to (Team)</label>
                            <select name="team_id" id="createModalReassignTeamId" class="form-select">
                                <option value="">Select Team</option>
                                <?php foreach (($teams ?? []) as $team): ?>
                                    <option value="<?= $team['id'] ?>"><?= htmlspecialchars($team['name'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Transfer To User (Hidden by default, shown when Lead Transferred) -->
                        <div class="col-lg-6" id="createModalTransferUserDiv" style="display: none;">
                            <label class="form__input-title">Transfer To (User)</label>
                            <select name="transfer_to_user_id" id="createModalTransferUserId" class="form-select select2">
                                <option value="">Search user...</option>
                                <?php foreach (($transferableUsers ?? []) as $u): ?>
                                    <option value="<?= (int)$u['id'] ?>">
                                        <?= htmlspecialchars($u['full_name']) ?>
                                        <?php if (!empty($u['employee_id'])): ?>#EMP-<?= (int)$u['employee_id'] ?><?php endif; ?>
                                        (<?= htmlspecialchars($u['role_name'] ?? '') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Follow Up Status -->
                        <div class="col-lg-6">
                            <label class="form__input-title">Follow Up Status</label>
                            <select name="status" class="form-select">
                                <option value="">Select Status</option>
                                <option value="pending" selected>Pending</option>
                                <option value="completed">Completed</option>
                                <option value="missed">Missed</option>
                                <option value="canceled">Canceled</option>
                            </select>
                        </div>

                        <!-- Follow Up Agent (Optional) -->
                        <div class="col-lg-6">
                            <label class="form__input-title">Follow-up Agent (Optional)</label>
                            <select name="follow_up_agent_id" class="form-select">
                                <option value="">Myself (Default)</option>
                                <?php foreach (($teamMembers ?? []) as $member): ?>
                                    <option value="<?= $member['id'] ?>"><?= htmlspecialchars($member['full_name'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Discussion Note -->
                        <div class="col-lg-6">
                            <label class="form__input-title">Discussion Note*</label>
                            <textarea name="discussion_note" class="form-control" rows="3" required></textarea>
                        </div>

                        <!-- Next Follow Up Date -->
                        <div class="col-lg-6">
                            <label class="form__input-title">Next Follow Up Date</label>
                            <input type="date" name="next_follow_up_date" class="form-control">
                        </div>

                        <!-- Next Action -->
                        <div class="col-lg-6">
                            <label class="form__input-title">Next Action</label>
                            <select name="next_action" class="form-select">
                                <option value="">Select Action</option>
                                <option value="site visit">Site Visit</option>
                                <option value="booking">Booking</option>
                                <option value="call_back">Call Back</option>
                                <option value="take_time">Take Time</option>
                            </select>
                        </div>

                        <!-- Next Follow Up Plan Notes -->
                        <div class="col-lg-6 mt-2">
                            <label class="form__input-title">Next Follow Up Plan Notes</label>
                            <textarea name="next_follow_up_plan_notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    
                    <div class="mt-4 text-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="saveFollowUpBtn">Save Follow Up</button>
                    </div>
                </form>

                <hr class="my-4">
                
                <h6 class="mb-3"><i class="bi bi-clock-history me-2"></i>Recent Follow Ups</h6>
                <div class="table-responsive border" style="max-height: 250px; overflow-y: auto;">
                    <table class="table table-bordered table-striped table-sm mb-0" style="font-size: 0.85rem; white-space: nowrap;">
                        <thead class="bg-light sticky-top" style="z-index: 1; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                            <tr>
                                <th>#</th>
                                <th>Lead</th>
                                <th>Project</th>
                                <th>Flat</th>
                                <th>Follow Up Date</th>
                                <th>Follow Up Time</th>
                                <th>Type</th>
                                <th>Lead Status</th>
                                <th>Follow Up Status</th>
                                <th>Discussion Note</th>
                                <th>Next Follow Up</th>
                                <th>Next Action</th>
                                <th>Follow Up With</th>
                                <th class="text-center" style="min-width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="createModalHistoryBody">
                            <tr>
                                <td colspan="14" class="text-center text-muted">Loading history...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const currentUserId = <?= (int)($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0) ?>;
    const currentUserRole = <?= (int)($_SESSION['user']['role_id'] ?? 0) ?>;
    let modalHistoryData = [];

    function escapeHtml(text) {
        if (!text) return '';
        return text.toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function loadFlatsForProject(projectId, selectedFlatId = null) {
        let flatSel = $('#createFollowUpFlatSelect');
        if (projectId) {
            flatSel.html('<option value="">Loading...</option>');
            fetch(`<?= $GLOBALS['base_url'] ?>/get-flats/${projectId}`)
                .then(res => res.json())
                .then(data => {
                    flatSel.html('<option value="">Select Flat</option>');
                    data.forEach(flat => {
                        const flatId = flat.flat_id ?? flat.id;
                        if (!flatId) return;
                        
                        let option = document.createElement('option');
                        option.value = flatId;
                        if (flat.is_booked == 1 && (!selectedFlatId || flatId != selectedFlatId)) {
                            option.textContent = `${flat.flat_name} | Floor ${flat.floor_no} | ${flat.sqft} sqft [Booked]`;
                            option.classList.add('booked-option');
                            option.disabled = true;
                        } else {
                            option.textContent = `${flat.flat_name} | Floor ${flat.floor_no} | ${flat.sqft} sqft`;
                        }
                        if (selectedFlatId && Number(flatId) === Number(selectedFlatId)) {
                            option.selected = true;
                        }
                        flatSel.append(option);
                    });
                    if (selectedFlatId) {
                        flatSel.val(selectedFlatId);
                    }
                    if (flatSel.hasClass('select2-hidden-accessible')) {
                        flatSel.trigger('change.select2');
                    }
                })
                .catch(err => {
                    console.error('Failed to load flats', err);
                    flatSel.html('<option value="">Error loading flats</option>');
                });
        } else {
            flatSel.html('<option value="">Select Flat</option>');
        }
    }

    function resetFormToCreateMode() {
        $('#createFollowUpForm').attr('action', '<?= $GLOBALS['base_url'] ?>/store-follow-up');
        $('#createFollowUpEditId').val('');
        $('#followUpEditModeAlert').hide().removeClass('d-flex').addClass('d-none');
        $('#createFollowUpModalTitle').html('<i class="bi bi-calendar-plus me-2"></i> Create Follow Up');
        $('#saveFollowUpBtn').removeClass('btn-warning').addClass('btn-primary').html('Save Follow Up');
        
        let leadId = $('#createFollowUpLeadId').val();
        $('#createFollowUpForm')[0].reset();
        $('#createFollowUpLeadId').val(leadId);
        $('#createModalReassignTeamDiv').hide();
        $('#createFollowUpFlatSelect').html('<option value="">Select Flat</option>');
        $('#createFollowUpProjectSelect').val('');
        if ($('#createFollowUpProjectSelect').hasClass('select2-hidden-accessible')) {
            $('#createFollowUpProjectSelect').trigger('change.select2');
        }
    }

    function loadModalHistory(leadId) {
        let tbody = $('#createModalHistoryBody');
        tbody.html('<tr><td colspan="12" class="text-center text-muted">Loading history...</td></tr>');
        modalHistoryData = [];
        
        fetch(`<?= $GLOBALS['base_url'] ?>/get-follow-up-history/${leadId}`)
            .then(res => res.json())
            .then(data => {
                let html = '';
                if (Array.isArray(data) && data.length > 0) {
                    modalHistoryData = data;
                    data.forEach(item => {
                        const canEdit = (currentUserId && Number(item.created_by) === Number(currentUserId)) || (currentUserRole === 1);
                        let actionHtml = '';
                        if (canEdit) {
                            actionHtml = `<button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 edit-modal-followup-btn" data-fuid="${item.id}" title="Edit this follow up">
                                <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                            </button>`;
                        } else {
                            actionHtml = `<span class="badge bg-light text-muted border py-1 px-2" title="Only the creator can edit this follow up"><i class="fa-solid fa-lock me-1"></i> No Access</span>`;
                        }

                        let lsBadge = '<span class="text-muted">—</span>';
                        if (item.lead_status) {
                            let lsCls = 'bg-secondary text-white';
                            if (item.lead_status === 'High Potential' || item.lead_status === 'High Prospect' || item.lead_status === 'Hot') lsCls = 'bg-danger text-white';
                            else if (item.lead_status === 'Qualification in Progress') lsCls = 'bg-info text-white';
                            else if (item.lead_status === 'Potential' || item.lead_status === 'Prospect' || item.lead_status === 'Warm') lsCls = 'bg-success text-white';
                            else if (item.lead_status === 'Visit Scheduled' || item.lead_status === 'Will Visit') lsCls = 'bg-info text-dark';
                            else if (item.lead_status === 'Visit Completed' || item.lead_status === 'Visited') lsCls = 'bg-primary text-white';
                            else if (item.lead_status === 'Invalid Lead' || item.lead_status === 'Irrelevant') lsCls = 'bg-dark text-white';
                            else if (item.lead_status === 'Cold' || item.lead_status === 'Not Interested' || item.lead_status === "Don't Call Again") lsCls = 'bg-secondary text-white';
                            lsBadge = `<span class="badge ${lsCls} px-2 py-1">${escapeHtml(item.lead_status)}</span>`;
                        }

                        let fuStatusBadge = '<span class="badge bg-warning text-dark">Pending</span>';
                        if (item.status === 'completed') fuStatusBadge = '<span class="badge bg-success text-white">Completed</span>';
                        else if (item.status === 'missed') fuStatusBadge = '<span class="badge bg-danger text-white">Missed</span>';
                        else if (item.status === 'canceled') fuStatusBadge = '<span class="badge bg-secondary text-white">Canceled</span>';

                        html += `<tr>
                            <td>${item.id || ''}</td>
                            <td>${escapeHtml(item.lead_name || '')}</td>
                            <td>${escapeHtml(item.project_name || '')}</td>
                            <td>${escapeHtml(item.flat_name || '')}</td>
                            <td>${item.follow_up_date || ''}</td>
                            <td>${item.follow_up_time || ''}</td>
                            <td>${escapeHtml(item.follow_up_type || '')}</td>
                            <td>${lsBadge}</td>
                            <td>${fuStatusBadge}</td>
                            <td title="${escapeHtml(item.discussion_note || '')}">${escapeHtml(item.discussion_note || '')}</td>
                            <td>${item.next_follow_up_date || ''}</td>
                            <td>${escapeHtml(item.next_action || '')}</td>
                            <td>${escapeHtml(item.agent_name || '')}</td>
                            <td class="text-center">${actionHtml}</td>
                        </tr>`;
                    });
                    
                    // Update the badge count on the main page silently
                    let badge = $(`.create-followup-btn[data-lead-id="${leadId}"] .action-badge`);
                    if(badge.length > 0) {
                        badge.text(data.length);
                    } else {
                        $(`.create-followup-btn[data-lead-id="${leadId}"]`).append(`<span class="action-badge">${data.length}</span>`);
                    }
                } else {
                    html = '<tr><td colspan="14" class="text-center text-muted">No history found.</td></tr>';
                }
                tbody.html(html);
            })
            .catch(err => {
                console.error("Failed to load history", err);
                tbody.html('<tr><td colspan="12" class="text-center text-danger">Failed to load history</td></tr>');
            });
    }

    // Inject lead ID when opening the Create Follow Up Modal
    $('#createFollowUpModal').on('show.bs.modal', function (event) {
        let button = $(event.relatedTarget);
        let leadId = button.attr('data-lead-id') || $('#createFollowUpLeadId').val();
        $('#createFollowUpLeadId').val(leadId);
        
        resetFormToCreateMode();
        loadModalHistory(leadId);
    });

    // Handle project change to load flats
    $('#createFollowUpProjectSelect').on('change', function() {
        let projectId = $(this).val();
        loadFlatsForProject(projectId, null);
    });

    // Handle Lead Transferred toggle
    $('#createModalLeadStatus').on('change', function() {
        if (this.value === 'Lead Transferred') {
            $('#createModalReassignTeamDiv').show();
            $('#createModalReassignTeamId').prop('required', false);
            $('#createModalTransferUserDiv').show();
        } else {
            $('#createModalReassignTeamDiv').hide();
            $('#createModalReassignTeamId').prop('required', false);
            $('#createModalTransferUserDiv').hide();
            $('#createModalTransferUserId').val('');
        }
    });

    // Edit button click in modal history table
    $(document).on('click', '.edit-modal-followup-btn', function() {
        const fuid = $(this).data('fuid');
        const item = modalHistoryData.find(x => Number(x.id) === Number(fuid));
        if (!item) return;

        // Switch form to Edit mode
        $('#createFollowUpForm').attr('action', '<?= $GLOBALS['base_url'] ?>/update-follow-up');
        $('#createFollowUpEditId').val(item.id);
        $('#followUpEditIdDisplay').text(item.id);
        $('#followUpEditCreatorDisplay').text(item.agent_name || 'You');
        $('#followUpEditModeAlert').removeClass('d-none').addClass('d-flex').show();
        $('#createFollowUpModalTitle').html('<i class="fa-solid fa-pen-to-square me-2 text-warning"></i> Edit Follow Up #' + item.id);
        $('#saveFollowUpBtn').removeClass('btn-primary').addClass('btn-warning').html('<i class="fa-solid fa-check me-1"></i> Update Follow Up');

        // Populate fields
        $('#createFollowUpProjectSelect').val(item.project_id || '');
        if ($('#createFollowUpProjectSelect').hasClass('select2-hidden-accessible')) {
            $('#createFollowUpProjectSelect').trigger('change.select2');
        }
        loadFlatsForProject(item.project_id, item.flat_id);

        let fDate = item.follow_up_date ? item.follow_up_date.split(' ')[0] : '';
        let fTime = item.follow_up_time ? item.follow_up_time.substring(0, 5) : '';
        let nextDate = (item.next_follow_up_date && item.next_follow_up_date !== '0000-00-00') ? item.next_follow_up_date.split(' ')[0] : '';

        $('#createFollowUpForm input[name="follow_up_date"]').val(fDate);
        $('#createFollowUpForm input[name="follow_up_time"]').val(fTime);
        $('#createFollowUpForm select[name="follow_up_type"]').val(item.follow_up_type || '');
        $('#createModalLeadStatus').val(item.lead_status || '');
        $('#createModalTransferUserId').val('');
        $('#createModalTransferUserDiv').hide();
        if (item.lead_status === 'Lead Transferred') {
            $('#createModalReassignTeamDiv').show();
            $('#createModalReassignTeamId').prop('required', false).val(item.team_id || '');
            $('#createModalTransferUserDiv').show();
        } else {
            $('#createModalReassignTeamDiv').hide();
            $('#createModalReassignTeamId').prop('required', false).val('');
        }

        $('#createFollowUpForm select[name="status"]').val(item.status || 'pending');
        $('#createFollowUpForm select[name="follow_up_agent_id"]').val(item.follow_up_agent_id || '');
        $('#createFollowUpForm textarea[name="discussion_note"]').val(item.discussion_note || '');
        $('#createFollowUpForm input[name="next_follow_up_date"]').val(nextDate);
        $('#createFollowUpForm select[name="next_action"]').val(item.next_action || '');
        $('#createFollowUpForm textarea[name="next_follow_up_plan_notes"]').val(item.next_follow_up_plan_notes || '');

        // Scroll to form top inside modal
        $('#createFollowUpModal .modal-body').animate({ scrollTop: 0 }, 300);
    });

    // Cancel edit button click
    $(document).on('click', '#cancelFollowUpEditBtn', function() {
        resetFormToCreateMode();
    });

    // Handle AJAX form submission (Create or Update)
    $('#createFollowUpForm').on('submit', function(e) {
        e.preventDefault();
        let form = $(this);
        let submitBtn = $('#saveFollowUpBtn');
        let isEditMode = Boolean($('#createFollowUpEditId').val());
        let originalHtml = submitBtn.html();
        let leadId = $('#createFollowUpLeadId').val();
        
        submitBtn.prop('disabled', true).html(isEditMode ? '<i class="fa-solid fa-spinner fa-spin me-1"></i> Updating...' : '<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');
        
        $.ajax({
            url: form.attr('action'),
            method: form.attr('method'),
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                submitBtn.prop('disabled', false).html(originalHtml);
                if (response.status === 'success') {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message,
                            toast: true,
                            position: 'top-end',
                            timer: 3000,
                            showConfirmButton: false
                        });
                    } else {
                        alert(response.message);
                    }
                    
                    let savedLeadStatus = $('#createModalLeadStatus').val();
                    let fuStatus = $('#createFollowUpForm select[name="status"]').val();
                    let displayStatus = savedLeadStatus || (fuStatus ? fuStatus.charAt(0).toUpperCase() + fuStatus.slice(1) : 'Pending');
                    let badgeWrap = $(`.lead-status-badge-wrap[data-lead-id="${leadId}"]`);
                    if (badgeWrap.length > 0) {
                        let bCls = 'bg-secondary text-white';
                        if (displayStatus === 'High Potential' || displayStatus === 'High Prospect' || displayStatus === 'Hot') bCls = 'bg-danger text-white';
                        else if (displayStatus === 'Qualification in Progress') bCls = 'bg-info text-white';
                        else if (displayStatus === 'Potential' || displayStatus === 'Prospect') bCls = 'bg-success text-white';
                        else if (displayStatus === 'Visit Scheduled' || displayStatus === 'Will Visit') bCls = 'bg-info text-dark';
                        else if (displayStatus === 'Visit Completed' || displayStatus === 'Visited') bCls = 'bg-primary text-white';
                        else if (displayStatus === 'Warm') bCls = 'bg-warning text-dark';
                        else if (displayStatus === 'Invalid Lead' || displayStatus === 'Irrelevant') bCls = 'bg-dark text-white';
                        else if (displayStatus === 'No Response') bCls = 'bg-warning text-dark';
                        else if (displayStatus === 'Not Interested' || displayStatus === "Don't Call Again" || displayStatus === 'Cold' || displayStatus === 'Refund') bCls = 'bg-danger text-white';
                        else if (displayStatus === 'Sold' || displayStatus === 'Completed') bCls = 'bg-success text-white';
                        badgeWrap.html(`<span class="badge ${bCls} px-2 py-1" style="font-size: 10px;">${escapeHtml(displayStatus)}</span>`);
                    }

                    resetFormToCreateMode();
                    loadModalHistory(leadId);
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Something went wrong.'
                        });
                    } else {
                        alert(response.message || 'Error occurred.');
                    }
                }
            },
            error: function(xhr, status, error) {
                submitBtn.prop('disabled', false).html(originalHtml);
                console.error('AJAX Error:', error, xhr.responseText);
                let errMsg = 'Failed to submit follow up.';
                try {
                    let jsonResp = JSON.parse(xhr.responseText);
                    if (jsonResp && jsonResp.message) errMsg = jsonResp.message;
                } catch(e) {}
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errMsg
                    });
                } else {
                    alert(errMsg);
                }
            }
        });
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let selectedLeadsSet = new Set();
    const selectAllLeads = document.getElementById('selectAllLeads');
    const selectAllLeadsMobile = document.getElementById('selectAllLeadsMobile');
    
    function updateAssignUIData() {
        // Desktop
        const desktopCard = document.getElementById('assignCard');
        const desktopCount = document.getElementById('selectedCount');
        if (desktopCard && desktopCount) {
            if (selectedLeadsSet.size > 0) {
                desktopCard.style.display = 'block';
                desktopCount.textContent = selectedLeadsSet.size;
            } else {
                desktopCard.style.display = 'none';
            }
        }
        
        // Mobile
        const mobileCard = document.getElementById('assignCardMobile');
        const mobileCount = document.getElementById('selectedCountMobile');
        if (mobileCard && mobileCount) {
            if (selectedLeadsSet.size > 0) {
                mobileCard.style.display = 'block';
                mobileCount.textContent = selectedLeadsSet.size;
            } else {
                mobileCard.style.display = 'none';
            }
        }
        
        // Update checkboxes
        document.querySelectorAll('.selectedId').forEach(cb => {
            cb.checked = selectedLeadsSet.has(cb.dataset.id);
        });
        document.querySelectorAll('.selectedIdMobile').forEach(cb => {
            cb.checked = selectedLeadsSet.has(cb.dataset.id);
        });
    }

    // Delegate checkbox clicks
    document.body.addEventListener('change', function(e) {
        if (e.target.classList.contains('selectedId') || e.target.classList.contains('selectedIdMobile')) {
            const id = e.target.dataset.id;
            if (e.target.checked) {
                selectedLeadsSet.add(id);
            } else {
                selectedLeadsSet.delete(id);
                const allL = document.getElementById('selectAllLeads');
                const allLM = document.getElementById('selectAllLeadsMobile');
                if (allL) allL.checked = false;
                if (allLM) allLM.checked = false;
            }
            updateAssignUIData();
        }
    });

    // Delegate header "Select All" checkbox (works even after AJAX reload)
    document.body.addEventListener('change', function(e) {
        if (e.target && e.target.id === 'selectAllLeads') {
            const isChecked = e.target.checked;
            document.querySelectorAll('.selectedId').forEach(cb => {
                const id = cb.dataset.id;
                if (isChecked) {
                    selectedLeadsSet.add(id);
                } else {
                    selectedLeadsSet.delete(id);
                }
            });
            updateAssignUIData();
        }
    });

    // Delegate mobile "Select All" checkbox
    document.body.addEventListener('change', function(e) {
        if (e.target && e.target.id === 'selectAllLeadsMobile') {
            const isChecked = e.target.checked;
            document.querySelectorAll('.selectedIdMobile').forEach(cb => {
                const id = cb.dataset.id;
                if (isChecked) {
                    selectedLeadsSet.add(id);
                } else {
                    selectedLeadsSet.delete(id);
                }
            });
            updateAssignUIData();
        }
    });

    // Submit Assignment (Desktop)
    const assignBtn = document.getElementById('assignUserBtn');
    if (assignBtn) {
        assignBtn.addEventListener('click', function() {
            handleBulkAssign('assignUserSelect', 'assignedDate', 'assignFeedback', 'assignCard');
        });
    }

    // Submit Assignment (Mobile)
    const assignBtnMobile = document.getElementById('assignUserBtnMobile');
    if (assignBtnMobile) {
        assignBtnMobile.addEventListener('click', function() {
            handleBulkAssign('assignUserSelectMobile', 'assignedDateMobile', 'assignFeedbackMobile', 'assignCardMobile');
        });
    }

    function submitAssignForm(userId, selectedLeads, assignedDate, routeMode = 'new') {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= $GLOBALS['base_url'] ?>/assign-multiple-leads';
        const addHidden = (name, val) => {
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = name; inp.value = val;
            form.appendChild(inp);
        };
        addHidden('user_id',       userId);
        addHidden('lead_ids',      selectedLeads.join(','));
        addHidden('assigned_date', assignedDate);
        addHidden('route_mode',    routeMode);
        addHidden('return_url',    window.location.href);
        document.body.appendChild(form);
        form.submit();
    }

    function handleBulkAssign(selectId, dateId, feedbackId, cardId) {
        const userId = document.getElementById(selectId).value;
        const assignedDate = document.getElementById(dateId).value;
        const leads = Array.from(selectedLeadsSet);
        
        if (!userId || leads.length === 0) {
            alert('Select leads and a user.');
            return;
        }

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
                                • <strong>Split Assign:</strong> Assign duplicate leads back to their <strong>Existing Executive(s)</strong>, and assign the remaining leads to the <strong>New Executive</strong>.<br><br>
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
            .catch(e => {
                console.error(e);
                submitAssignForm(userId, leads, assignedDate, 'new');
            });
    }

    // ── Table Length Select (Show 25, 50, 100, 200, All entries) ──
    const lengthSelect = document.getElementById('tableLengthSelect');
    if (lengthSelect) {
        lengthSelect.addEventListener('change', function() {
            const url = new URL(window.location.href);
            url.searchParams.set('limit', this.value);
            url.searchParams.set('page', 1);
            window.location.href = url.toString();
        });
    }

    // ── Note view modal handler ──
    $(document).on('click', '.note-view-btn', function () {
        const noteEl = document.getElementById('view_note_text');
        if (noteEl) {
            noteEl.value = this.getAttribute('data-note') || 'No note available.';
        }
    });

    // ── Campaign view modal handler ──
    $(document).on('click', '.viewCampaignBtnDynamic, [id^="viewCampaignBtn"]', function () {
        const camEl = document.getElementById('view_campaign_id');
        if (camEl) {
            camEl.value = this.getAttribute('data-description') || '';
        }
    });

    // ── Live Debounced Search (Just type to search, matching lead-lists UX) ──
    const searchInput = document.getElementById('assignedLeadsSearchInput');
    const searchForm = document.getElementById('assignedLeadsSearchForm');
    const searchSpinner = document.getElementById('searchSpinner');
    let searchDebounceTimer = null;
    let searchAbortController = null;

    function doLiveSearch(searchTerm, pushToHistory = false) {
        const url = new URL(window.location.href);
        if (searchTerm) {
            url.searchParams.set('search', searchTerm);
        } else {
            url.searchParams.delete('search');
        }
        url.searchParams.set('page', '1');

        if (searchSpinner) searchSpinner.classList.remove('d-none');
        const contentArea = document.getElementById('assignedLeadsContentArea');
        if (contentArea) {
            contentArea.style.opacity = '0.5';
            contentArea.style.pointerEvents = 'none';
        }

        if (searchAbortController) {
            searchAbortController.abort();
        }
        searchAbortController = new AbortController();

        fetch(url.toString(), {
            signal: searchAbortController.signal,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => {
            if (!res.ok) throw new Error('Network error: ' + res.status);
            return res.text();
        })
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            const newContentArea = doc.getElementById('assignedLeadsContentArea');
            if (newContentArea && contentArea) {
                contentArea.innerHTML = newContentArea.innerHTML;
            }

            const mobileArea = document.getElementById('assignedLeadsMobileCards');
            const newMobileArea = doc.getElementById('assignedLeadsMobileCards');
            if (mobileArea && newMobileArea) {
                mobileArea.innerHTML = newMobileArea.innerHTML;
            }

            const activeFilters = document.getElementById('activeFiltersContainer');
            const newActiveFilters = doc.getElementById('activeFiltersContainer');
            if (activeFilters && newActiveFilters) {
                activeFilters.innerHTML = newActiveFilters.innerHTML;
            } else if (activeFilters && !newActiveFilters) {
                activeFilters.innerHTML = '';
            }

            if (pushToHistory) {
                window.history.pushState({ search: searchTerm }, '', url.toString());
            } else {
                window.history.replaceState({ search: searchTerm }, '', url.toString());
            }

            // Re-sync checkboxes and update assign card
            updateAssignUIData();
        })
        .catch(err => {
            if (err.name === 'AbortError') return;
            console.error('Live search error, submitting form instead:', err);
            if (searchForm) searchForm.submit();
        })
        .finally(() => {
            if (searchSpinner) searchSpinner.classList.add('d-none');
            if (contentArea) {
                contentArea.style.opacity = '1';
                contentArea.style.pointerEvents = '';
            }
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchDebounceTimer);
            const query = this.value.trim();
            if (query === '') {
                // Immediate search when cleared
                doLiveSearch('');
            } else {
                // Debounce 400ms while typing
                searchDebounceTimer = setTimeout(() => {
                    doLiveSearch(query);
                }, 400);
            }
        });

        if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                clearTimeout(searchDebounceTimer);
                doLiveSearch(searchInput.value.trim());
            });
        }
    }

    // Smooth AJAX Pagination within assigned leads table
    $(document).on('click', '#assignedLeadsContentArea .pagination a.page-link', function(e) {
        const href = $(this).attr('href');
        if (!href || href === '#' || href.startsWith('javascript:')) return;
        e.preventDefault();

        const contentArea = document.getElementById('assignedLeadsContentArea');
        if (contentArea) {
            contentArea.style.opacity = '0.5';
            contentArea.style.pointerEvents = 'none';
        }
        if (searchSpinner) searchSpinner.classList.remove('d-none');

        fetch(href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => {
                if (!res.ok) throw new Error('Network error');
                return res.text();
            })
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContentArea = doc.getElementById('assignedLeadsContentArea');
                if (newContentArea && contentArea) {
                    contentArea.innerHTML = newContentArea.innerHTML;
                }
                const mobileArea = document.getElementById('assignedLeadsMobileCards');
                const newMobileArea = doc.getElementById('assignedLeadsMobileCards');
                if (mobileArea && newMobileArea) {
                    mobileArea.innerHTML = newMobileArea.innerHTML;
                }
                window.history.pushState(null, '', href);
                updateAssignUIData();
                contentArea.scrollIntoView({ behavior: 'smooth', block: 'start' });
            })
            .catch(err => {
                window.location.href = href;
            })
            .finally(() => {
                if (searchSpinner) searchSpinner.classList.add('d-none');
                if (contentArea) {
                    contentArea.style.opacity = '1';
                    contentArea.style.pointerEvents = '';
                }
            });
    });

    // ── Delete Request Modal Handler (Bootstrap 5 native + jQuery safe) ──
    let activeDeleteBtn = null;

    const delModalEl = document.getElementById('deleteRequestModal');
    if (delModalEl) {
        delModalEl.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button) {
                activeDeleteBtn = $(button);
                const leadId = button.getAttribute('data-lead-id') || $(button).data('lead-id');
                const leadName = button.getAttribute('data-lead-name') || $(button).data('lead-name');
                $('#delete_request_lead_id').val(leadId);
                $('#delete_request_lead_name').text(leadName || ('Lead #' + leadId));
                $('#delete_request_reason').val('');
            }
        });
    }

    $(document).on('click', '.request-delete-btn', function(e) {
        activeDeleteBtn = $(this);
        const leadId = $(this).attr('data-lead-id') || $(this).data('lead-id');
        const leadName = $(this).attr('data-lead-name') || $(this).data('lead-name');
        $('#delete_request_lead_id').val(leadId);
        $('#delete_request_lead_name').text(leadName || ('Lead #' + leadId));
        $('#delete_request_reason').val('');

        const modalEl = document.getElementById('deleteRequestModal');
        if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else if (typeof $ !== 'undefined' && typeof $.fn.modal !== 'undefined') {
            $('#deleteRequestModal').modal('show');
        }
    });

    $('#deleteRequestForm').on('submit', function(e) {
        e.preventDefault();
        const submitBtn = $('#submitDeleteRequestBtn');
        const leadId = $('#delete_request_lead_id').val();
        const reason = $('#delete_request_reason').val().trim();
        if (!reason) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Required', text: 'Please enter a reason for deletion.' });
            } else {
                alert('Please enter a reason for deletion.');
            }
            return;
        }

        submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Submitting...');

        $.ajax({
            url: '<?= $GLOBALS["base_url"] ?>/submit-lead-delete-request',
            type: 'POST',
            data: { lead_id: leadId, reason: reason },
            dataType: 'json',
            success: function(res) {
                submitBtn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane me-1"></i> Submit Delete Request');
                if (res.success) {
                    const modalEl = document.getElementById('deleteRequestModal');
                    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                    } else if (typeof $ !== 'undefined' && typeof $.fn.modal !== 'undefined') {
                        $('#deleteRequestModal').modal('hide');
                    }
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: res.message || 'Delete request submitted successfully.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        alert(res.message || 'Delete request submitted successfully.');
                    }
                    if (activeDeleteBtn && activeDeleteBtn.length) {
                        activeDeleteBtn.replaceWith(`
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1 px-2 py-1.5 d-inline-flex align-items-center" 
                                  title="Delete Reason: ${reason}" 
                                  style="font-size: 11px; white-space: nowrap;">
                                <i class="fa-solid fa-clock-rotate-left me-1"></i> Requested
                            </span>
                        `);
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Oops...', text: res.message || 'Failed to submit delete request.' });
                    } else {
                        alert(res.message || 'Failed to submit delete request.');
                    }
                }
            },
            error: function() {
                submitBtn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane me-1"></i> Submit Delete Request');
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'An unexpected error occurred. Please try again.' });
                } else {
                    alert('An unexpected error occurred. Please try again.');
                }
            }
        });
    });
});
</script>
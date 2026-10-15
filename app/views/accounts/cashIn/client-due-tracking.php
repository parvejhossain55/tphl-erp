<?php
$page_title = "Client-wise Due Tracking & Payment Management";
$page = 'Clients';
$show_breadcrumb = true;
?>

<style>
    /* Styling for Client-wise Due Tracking */
    .due-stat-card {
        border: 0;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        overflow: hidden;
    }
    .due-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
    }
    .due-stat-card .card-body {
        padding: 1.15rem 1.25rem;
    }
    .stat-pill {
        font-size: 11px;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 20px;
    }
    .project-chip {
        cursor: pointer;
        transition: all 0.2s ease;
        border: 1px solid rgba(0,0,0,0.08);
        border-radius: 10px;
        background: #fff;
    }
    .project-chip:hover, .project-chip.active {
        border-color: #0d6efd;
        background: #f0f7ff;
        box-shadow: 0 2px 8px rgba(13, 110, 253, 0.15);
    }
    .due-type-pill {
        border-radius: 20px;
        font-size: 12px;
        padding: 6px 14px;
        cursor: pointer;
        transition: all 0.15s ease;
        border: 1px solid #dee2e6;
        background: #fff;
        color: #495057;
        font-weight: 500;
    }
    .due-type-pill:hover {
        background: #f8f9fa;
        border-color: #adb5bd;
    }
    .due-type-pill.active {
        background: #0d6efd;
        color: #fff;
        border-color: #0d6efd;
        font-weight: 600;
        box-shadow: 0 2px 6px rgba(13, 110, 253, 0.3);
    }
    .table-due thead th {
        background: #f8f9fa;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6c757d;
        border-bottom: 2px solid #e9ecef;
        padding: 12px 10px;
        white-space: nowrap;
    }
    .table-due tbody td {
        font-size: 13px;
        padding: 12px 10px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f3f5;
    }
    .table-due tbody tr:hover {
        background-color: #fafbfd;
    }
    .fs-11 { font-size: 11px !important; }
    .fs-12 { font-size: 12px !important; }
    .fs-13 { font-size: 13px !important; }
    .fs-14 { font-size: 14px !important; }
    .fs-15 { font-size: 15px !important; }
    @media print {
        #sidebar, .main-header, .due-toolbar, .no-print {
            display: none !important;
        }
        .main-content {
            margin: 0 !important;
            padding: 0 !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
    }
</style>

<div class="row">

    <!-- ══ HEADER & ACTIONS ══ -->
    <div class="col-12 mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 bg-white p-3 rounded-3 shadow-sm border">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-md bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-file-invoice-dollar fs-4"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold text-dark">Client-wise Due Tracking & Payment Management</h4>
                        <small class="text-muted">
                            Project-wise and client-wise tracking of <strong>Land Share Due</strong> (Booking + Registration), <strong>Development Charge Due</strong>, <strong>EMI Due</strong>, and <strong>Overall Due</strong>
                        </small>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2 no-print">
                <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#projectBreakdownModal">
                    <i class="fa-solid fa-layer-group me-1"></i> Project Breakdown
                </button>
                <a href="<?= $GLOBALS['base_url'] ?>/client-wise-due-tracking-export?<?= http_build_query($_GET) ?>" id="exportDueCsvBtn" class="btn btn-sm btn-success fw-semibold">
                    <i class="fa-solid fa-file-excel me-1"></i> Export to CSV
                </a>
                <button type="button" class="btn btn-sm btn-outline-secondary fw-semibold" onclick="window.print()">
                    <i class="fa-solid fa-print me-1"></i> Print Report
                </button>
                <button type="button" class="btn btn-sm btn-outline-info fw-semibold" onclick="refreshDueTracking(true)" title="Refresh data">
                    <i class="fa-solid fa-rotate me-1"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- ══ TOP KPI SUMMARY CARDS ══ -->
    <!-- 1. Overall Outstanding Due -->
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-12 mb-3">
        <div class="card due-stat-card border-start border-4 border-danger h-100 bg-white">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase fw-bold text-muted fs-11 tracking-wider">Overall Outstanding Due</span>
                    <span class="avatar avatar-xs bg-danger-subtle text-danger rounded-circle d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-circle-exclamation"></i>
                    </span>
                </div>
                <h3 class="fw-bold text-danger mb-1" id="statOverallDue">
                    ৳<?= number_format($stats['overall_due'] ?? 0) ?>
                </h3>
                <div class="d-flex align-items-center justify-content-between text-muted fs-12">
                    <span>Total Booked Value: <strong>৳<?= number_format(($stats['total_value'] ?? 0) / 10000000, 2) ?> Cr</strong></span>
                    <span class="badge bg-danger-subtle text-danger stat-pill">
                        <?= ($stats['total_value'] ?? 0) > 0 ? round((($stats['overall_due'] ?? 0) / ($stats['total_value'] ?? 1)) * 100, 1) : 0 ?>% Due
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Land Share Due (Booking + Registration) -->
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-12 mb-3">
        <div class="card due-stat-card border-start border-4 border-warning h-100 bg-white">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase fw-bold text-muted fs-11 tracking-wider">Land Share Due</span>
                    <span class="avatar avatar-xs bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-map-location-dot"></i>
                    </span>
                </div>
                <h3 class="fw-bold text-dark mb-1" id="statLandShareDue">
                    ৳<?= number_format($stats['total_land_share_due'] ?? 0) ?>
                </h3>
                <div class="d-flex align-items-center justify-content-between text-muted fs-12">
                    <span title="Booking Money + Registration Fee">Paid: <strong>৳<?= number_format(($stats['total_land_share_paid'] ?? 0) / 10000000, 2) ?> Cr</strong></span>
                    <span class="badge bg-warning-subtle text-dark stat-pill" title="Target: ৳<?= number_format(($stats['total_land_share_target'] ?? 0) / 10000000, 2) ?> Cr">
                        Target: ৳<?= number_format(($stats['total_land_share_target'] ?? 0) / 10000000, 2) ?> Cr
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Development Charge Due -->
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-12 mb-3">
        <div class="card due-stat-card border-start border-4 border-info h-100 bg-white">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase fw-bold text-muted fs-11 tracking-wider">Development Charge Due</span>
                    <span class="avatar avatar-xs bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-screwdriver-wrench"></i>
                    </span>
                </div>
                <h3 class="fw-bold text-info mb-1" id="statDevChargeDue">
                    ৳<?= number_format($stats['total_dev_charge_due'] ?? 0) ?>
                </h3>
                <div class="d-flex align-items-center justify-content-between text-muted fs-12">
                    <span>Paid: <strong>৳<?= number_format(($stats['total_dev_charges_paid'] ?? 0) / 100000, 2) ?> Lac</strong></span>
                    <span class="badge bg-info-subtle text-info stat-pill">
                        Target: ৳<?= number_format(($stats['total_dev_charges_target'] ?? 0) / 100000, 2) ?> Lac
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Total EMI / Construction Due -->
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-12 mb-3">
        <div class="card due-stat-card border-start border-4 border-primary h-100 bg-white">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase fw-bold text-muted fs-11 tracking-wider">Construction / EMI Due</span>
                    <span class="avatar avatar-xs bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-building-columns"></i>
                    </span>
                </div>
                <h3 class="fw-bold text-primary mb-1" id="statEmiDue">
                    ৳<?= number_format($stats['total_emi_due'] ?? 0) ?>
                </h3>
                <div class="d-flex align-items-center justify-content-between text-muted fs-12">
                    <span>EMI Paid: <strong>৳<?= number_format(($stats['total_emi_paid'] ?? 0) / 10000000, 2) ?> Cr</strong></span>
                    <?php if (($stats['total_overdue_emi'] ?? 0) > 0): ?>
                        <span class="badge bg-danger text-white stat-pill" title="Matured installments overdue">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i>Overdue: ৳<?= number_format(($stats['total_overdue_emi'] ?? 0) / 100000, 2) ?> L
                        </span>
                    <?php else: ?>
                        <span class="badge bg-success-subtle text-success stat-pill">No Overdues</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ PROJECT-WISE DUES SUMMARY GRID ══ -->
    <div class="col-12 mb-3">
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-diagram-project text-primary fs-5"></i>
                    <h6 class="fw-bold mb-0 text-dark">Project-wise Due Breakdown</h6>
                    <span class="badge bg-light text-secondary border fs-11"><?= count($projectBreakdown ?? []) ?> Active Projects</span>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="position-relative" style="width: 190px;">
                        <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="left: 10px; top: 50%; transform: translateY(-50%); font-size: 11px;"></i>
                        <input type="text" id="projectChipSearch" class="form-control form-control-sm ps-4" 
                               placeholder="Search <?= count($projectBreakdown ?? []) ?> projects..." 
                               oninput="filterProjectChips(this.value)" 
                               style="height: 30px; border-radius: 6px; font-size: 12px;">
                    </div>
                    <?php if (count($projectBreakdown ?? []) > 12): ?>
                        <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2 fs-12" id="toggleProjectChipsBtn" onclick="toggleProjectChipsView()">
                            <i class="fa-solid fa-layer-group me-1"></i> <span id="toggleProjectChipsText">Show All (<?= count($projectBreakdown ?? []) ?>)</span>
                        </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2 fs-12" id="clearProjectFilterBtn" onclick="selectProjectFilter('')" style="<?= empty($selectedProjectId) ? 'display:none;' : '' ?>">
                        <i class="fa-solid fa-filter-circle-xmark me-1"></i> Show All Projects
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-primary py-1 px-2 fs-12" data-bs-toggle="modal" data-bs-target="#projectBreakdownModal">
                        <i class="fa-solid fa-table me-1"></i> View Full Table
                    </button>
                </div>
            </div>
            <div class="card-body p-3">
                <div class="row g-2" id="projectChipsContainer" style="max-height: 245px; overflow-y: auto;">
                    <?php if (!empty($projectBreakdown)): ?>
                        <?php foreach ($projectBreakdown as $idx => $pb): 
                            $isPbActive = ((int)$pb['project_id'] === (int)($selectedProjectId ?? 0));
                            $isExtra = ($idx >= 12 && !$isPbActive);
                        ?>
                            <div class="col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-sm-12 project-chip-item <?= $isExtra ? 'project-chip-extra d-none' : '' ?>" data-project-name="<?= strtolower(htmlspecialchars($pb['project_name'])) ?>">
                                <div class="p-3 project-chip <?= $isPbActive ? 'active border-primary' : '' ?>" onclick="selectProjectFilter(<?= $pb['project_id'] ?>)" title="Click to filter by <?= htmlspecialchars($pb['project_name']) ?>">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <strong class="text-truncate d-inline-block text-dark fs-13" style="max-width: 175px;">
                                            <?= htmlspecialchars($pb['project_name']) ?>
                                        </strong>
                                        <span class="badge bg-primary-subtle text-primary rounded-pill fs-11"><?= $pb['total_clients'] ?> Clients</span>
                                    </div>
                                    <div class="d-flex align-items-baseline justify-content-between text-muted fs-11 mt-1">
                                        <span>Overall Due:</span>
                                        <strong class="text-danger fs-12">৳<?= number_format($pb['overall_due']) ?></strong>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mt-2 pt-1 border-top" style="font-size: 10px; color: #6c757d;">
                                        <span title="Land Share Due">Land: <strong>৳<?= number_format($pb['land_share_due']) ?></strong></span>
                                        <span title="Dev Charge Due">Dev: <strong>৳<?= number_format($pb['dev_charge_due']) ?></strong></span>
                                        <span title="EMI / Cons Due">EMI: <strong>৳<?= number_format($pb['emi_due']) ?></strong></span>
                                        <span title="Collection ratio" class="badge bg-success-subtle text-success"><?= $pb['collection_ratio'] ?>% Rec</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center text-muted py-3">No project data available.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ MAIN CLIENT DUE TRACKING SECTION ══ -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            
            <!-- Card Header: Title & Quick Counts -->
            <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                        <i class="fa-solid fa-users-viewfinder text-primary"></i>
                        <span>Client-wise Payment & Due Tracker</span>
                        <span class="badge bg-primary rounded-pill px-2 py-1 fs-12" id="dueRecordCountBadge">
                            <span id="dueTotalCount"><?= number_format($totalClients ?? 0) ?></span> Clients
                        </span>
                        <?php if (!empty($selectedProjectName)): ?>
                            <span class="badge bg-info-subtle text-info border border-info-subtle fs-12">
                                <i class="fa-solid fa-building me-1"></i><?= htmlspecialchars($selectedProjectName) ?>
                            </span>
                        <?php endif; ?>
                    </h5>
                    <small class="text-muted">Displaying Land Share Due (Registration + Booking), Development Charge Due, EMI Due, and Overall Due per client</small>
                </div>

                <!-- Quick Due Status Filter Pills -->
                <div class="d-flex flex-wrap align-items-center gap-1 no-print">
                    <button type="button" class="due-type-pill <?= ($dueType === 'all') ? 'active' : '' ?>" onclick="setDueTypeFilter('all')">
                        All Clients
                    </button>
                    <button type="button" class="due-type-pill <?= ($dueType === 'land_share_due') ? 'active' : '' ?>" onclick="setDueTypeFilter('land_share_due')">
                        <i class="fa-solid fa-map-location-dot me-1"></i> Land Share Due
                    </button>
                    <button type="button" class="due-type-pill <?= ($dueType === 'dev_charge_due') ? 'active' : '' ?>" onclick="setDueTypeFilter('dev_charge_due')">
                        <i class="fa-solid fa-screwdriver-wrench me-1"></i> Dev Charge Due
                    </button>
                    <button type="button" class="due-type-pill <?= ($dueType === 'emi_due') ? 'active' : '' ?>" onclick="setDueTypeFilter('emi_due')">
                        <i class="fa-solid fa-building-columns me-1"></i> EMI Due
                    </button>
                    <button type="button" class="due-type-pill <?= ($dueType === 'overall_due') ? 'active' : '' ?>" onclick="setDueTypeFilter('overall_due')">
                        <i class="fa-solid fa-circle-exclamation me-1 text-danger"></i> Has Any Due
                    </button>
                    <button type="button" class="due-type-pill <?= ($dueType === 'cleared') ? 'active' : '' ?>" onclick="setDueTypeFilter('cleared')">
                        <i class="fa-solid fa-circle-check me-1 text-success"></i> Fully Cleared
                    </button>
                </div>
            </div>

            <!-- Toolbar: Search, Project Dropdown, Date Range, Sort, Limit -->
            <div class="card-body p-3 border-bottom bg-light due-toolbar no-print">
                <form id="dueTrackingFilterForm" onsubmit="event.preventDefault(); loadDueTrackingPage(1);" class="row g-2 align-items-center">
                    
                    <!-- Search Input -->
                    <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-sm-12">
                        <div class="position-relative">
                            <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="left: 12px; top: 50%; transform: translateY(-50%); font-size: 13px; z-index: 4;"></i>
                            <input type="text" id="dueSearchInput" class="form-control form-control-sm ps-5" 
                                   placeholder="Search by client, phone, email, unit..." 
                                   value="<?= htmlspecialchars($search ?? '') ?>" autocomplete="off"
                                   style="height: 38px; border-radius: 8px;">
                            <button type="button" id="dueClearSearch" class="btn btn-sm btn-link text-muted position-absolute p-0 <?= empty($search) ? 'd-none' : '' ?>" 
                                    style="right: 10px; top: 50%; transform: translateY(-50%); text-decoration: none; z-index: 4;" 
                                    onclick="clearDueSearch()" title="Clear Search">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Project Filter Dropdown -->
                    <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-sm-12">
                        <select class="form-select form-select-sm" id="dueProjectFilter" onchange="loadDueTrackingPage(1);" style="height: 38px; border-radius: 8px;">
                            <option value="">All Projects (All Sites)</option>
                            <?php if (!empty($projects)): foreach ($projects as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= ((int)($selectedProjectId ?? 0) === (int)$p['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['name']) ?>
                                </option>
                            <?php endforeach; endif; ?>
                        </select>
                    </div>

                    <!-- Sort By Selector -->
                    <div class="col-xxl-2 col-xl-2 col-lg-4 col-md-4 col-sm-6">
                        <select class="form-select form-select-sm" id="dueSortBy" onchange="loadDueTrackingPage(1);" style="height: 38px; border-radius: 8px;">
                            <option value="overall_due" <?= (($sortBy ?? '') === 'overall_due') ? 'selected' : '' ?>>Sort: Overall Due (High-Low)</option>
                            <option value="land_share_due" <?= (($sortBy ?? '') === 'land_share_due') ? 'selected' : '' ?>>Sort: Land Share Due</option>
                            <option value="dev_charge_due" <?= (($sortBy ?? '') === 'dev_charge_due') ? 'selected' : '' ?>>Sort: Dev Charge Due</option>
                            <option value="remaining_emi_due" <?= (($sortBy ?? '') === 'remaining_emi_due') ? 'selected' : '' ?>>Sort: EMI Due</option>
                            <option value="total_paid" <?= (($sortBy ?? '') === 'total_paid') ? 'selected' : '' ?>>Sort: Total Paid</option>
                            <option value="booking_id" <?= (($sortBy ?? '') === 'booking_id') ? 'selected' : '' ?>>Sort: Recent Booking ID</option>
                            <option value="client_name" <?= (($sortBy ?? '') === 'client_name') ? 'selected' : '' ?>>Sort: Client Name</option>
                        </select>
                    </div>

                    <!-- Date Range Filter -->
                    <div class="col-xxl-3 col-xl-3 col-lg-8 col-md-8 col-sm-12">
                        <div class="d-flex align-items-center gap-1">
                            <select id="dueDateType" class="form-select form-select-sm" style="height: 38px; border-radius: 8px; width: 110px;">
                                <option value="booking_date" <?= (($dateType ?? '') === 'booking_date') ? 'selected' : '' ?>>Booking</option>
                                <option value="last_payment_date" <?= (($dateType ?? '') === 'last_payment_date') ? 'selected' : '' ?>>Payment</option>
                            </select>
                            <input type="date" id="dueStartDate" class="form-control form-control-sm" style="height: 38px; border-radius: 8px;" value="<?= htmlspecialchars($startDate ?? '') ?>" title="From Date">
                            <span class="text-muted small">to</span>
                            <input type="date" id="dueEndDate" class="form-control form-control-sm" style="height: 38px; border-radius: 8px;" value="<?= htmlspecialchars($endDate ?? '') ?>" title="To Date">
                        </div>
                    </div>

                    <!-- Per Page Limit & Reset -->
                    <div class="col-xxl-1 col-xl-1 col-lg-4 col-md-4 col-sm-6 d-flex align-items-center gap-1 justify-content-end">
                        <select id="duePageLimit" class="form-select form-select-sm" onchange="loadDueTrackingPage(1);" style="height: 38px; border-radius: 8px; width: 80px;" title="Entries per page">
                            <option value="10" <?= ((int)($limit ?? 20) === 10) ? 'selected' : '' ?>>10</option>
                            <option value="20" <?= ((int)($limit ?? 20) === 20) ? 'selected' : '' ?>>20</option>
                            <option value="50" <?= ((int)($limit ?? 20) === 50) ? 'selected' : '' ?>>50</option>
                            <option value="100" <?= ((int)($limit ?? 20) === 100) ? 'selected' : '' ?>>100</option>
                        </select>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetAllDueFilters()" title="Reset Filters" style="height: 38px; width: 38px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-arrows-rotate"></i>
                        </button>
                    </div>

                </form>
            </div>

            <!-- Table Responsive Container -->
            <div class="table-responsive" style="min-height: 350px;">
                <table class="table table-hover table-due mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 85px;">ID & Date</th>
                            <th style="min-width: 190px;">Client Info</th>
                            <th style="min-width: 170px;">Project & Unit</th>
                            <th class="text-end" style="width: 140px;">Flat Value</th>
                            <th class="text-end" style="width: 150px;">Land Share Due</th>
                            <th class="text-end" style="width: 140px;">Dev Charge Due</th>
                            <th class="text-end" style="width: 160px;">EMI / Cons Due</th>
                            <th class="text-end" style="width: 140px;">Overall Due</th>
                            <th class="text-center no-print" style="width: 110px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="dueTrackingTableBody">
                        <?= (new AccountClientController($GLOBALS['db'] ?? null))->renderDueTrackingRowsHtml($clients ?? []) ?>
                    </tbody>
                </table>
            </div>

            <!-- Table Footer: Pagination & Info -->
            <div class="card-footer bg-white py-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="text-muted fs-13" id="dueInfoText">
                    <?php 
                        $startEntry = ($totalClients ?? 0) > 0 ? ((($currentPage ?? 1) - 1) * ($limit ?? 20)) + 1 : 0;
                        $endEntry = min($totalClients ?? 0, ($currentPage ?? 1) * ($limit ?? 20));
                    ?>
                    Showing <?= $startEntry ?> to <?= $endEntry ?> of <?= number_format($totalClients ?? 0) ?> client bookings
                </div>
                <div id="duePaginationContainer" class="no-print">
                    <?= (new AccountClientController($GLOBALS['db'] ?? null))->generateAjaxPagination($currentPage ?? 1, $totalPages ?? 1, $totalClients ?? 0, $limit ?? 20, 'loadDueTrackingPage') ?>
                </div>
            </div>

        </div>
    </div>

</div>

<!-- ══ MODAL 1: QUICK CLIENT DUE BREAKDOWN ══ -->
<div class="modal fade" id="clientDueBreakdownModal" tabindex="-1" aria-labelledby="clientDueModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header py-3 px-4 bg-primary text-white">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-file-invoice-dollar fs-5"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="clientDueModalTitle">Client Due Breakdown</h5>
                        <small class="text-white-50" id="clientDueModalSubtitle">Booking Financial Breakdown & Dues</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="clientDueModalBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary mb-2" role="status"></div>
                    <p class="text-muted mb-0">Loading client financial breakdown...</p>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-4 d-flex justify-content-between">
                <a href="#" id="clientDueModalLedgerBtn" target="_blank" class="btn btn-sm btn-primary">
                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Open Full Ledger
                </a>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL 2: FULL PROJECT BREAKDOWN TABLE ══ -->
<div class="modal fade" id="projectBreakdownModal" tabindex="-1" aria-labelledby="projModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header py-3 px-4 bg-dark text-white">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-building-circle-check text-warning fs-5"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="projModalTitle">All Projects Due Breakdown</h5>
                        <small class="text-white-50">Comparative Land Share, Dev Charge, EMI, and Overall Dues across all projects</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Project Name</th>
                                <th class="text-center">Clients</th>
                                <th class="text-end">Total Value</th>
                                <th class="text-end">Total Paid</th>
                                <th class="text-end text-danger">Land Share Due</th>
                                <th class="text-end text-danger">Dev Charge Due</th>
                                <th class="text-end text-danger">EMI Due</th>
                                <th class="text-end text-danger fw-bold">Overall Due</th>
                                <th class="text-center">Recovery</th>
                                <th class="text-center">Filter</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($projectBreakdown)): $pIdx = 1; ?>
                                <?php foreach ($projectBreakdown as $pRow): ?>
                                    <tr>
                                        <td><?= $pIdx++ ?></td>
                                        <td>
                                            <strong class="text-dark"><?= htmlspecialchars($pRow['project_name']) ?></strong>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary-subtle text-primary rounded-pill"><?= $pRow['total_clients'] ?></span>
                                        </td>
                                        <td class="text-end">৳<?= number_format($pRow['total_project_value']) ?></td>
                                        <td class="text-end text-success">৳<?= number_format($pRow['total_paid']) ?></td>
                                        <td class="text-end text-danger">৳<?= number_format($pRow['land_share_due']) ?></td>
                                        <td class="text-end text-danger">৳<?= number_format($pRow['dev_charge_due']) ?></td>
                                        <td class="text-end text-danger">৳<?= number_format($pRow['emi_due']) ?></td>
                                        <td class="text-end text-danger fw-bold">৳<?= number_format($pRow['overall_due']) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-success-subtle text-success"><?= $pRow['collection_ratio'] ?>%</span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-xs btn-outline-primary py-1 px-2" 
                                                    onclick="selectProjectFilter(<?= $pRow['project_id'] ?>); bootstrap.Modal.getInstance(document.getElementById('projectBreakdownModal')).hide();">
                                                Select
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="11" class="text-center py-4 text-muted">No project records found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-4">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ══ CLIENT DUE TRACKING JAVASCRIPT ══ -->
<script>
    let dueCurrentPage = <?= (int)($currentPage ?? 1) ?>;
    let dueActiveType = '<?= htmlspecialchars($dueType ?? 'all') ?>';
    let dueSearchTimer = null;

    // Set Due Type filter tab
    function setDueTypeFilter(type) {
        dueActiveType = type;
        document.querySelectorAll('.due-type-pill').forEach(btn => btn.classList.remove('active'));
        const activeBtn = Array.from(document.querySelectorAll('.due-type-pill')).find(b => b.getAttribute('onclick') === `setDueTypeFilter('${type}')`);
        if (activeBtn) activeBtn.classList.add('active');
        loadDueTrackingPage(1);
    }

    let projectChipsExpanded = false;

    // Toggle between showing top 12 vs all projects in the breakdown card
    function toggleProjectChipsView() {
        projectChipsExpanded = !projectChipsExpanded;
        const extras = document.querySelectorAll('.project-chip-extra');
        const container = document.getElementById('projectChipsContainer');
        const textSpan = document.getElementById('toggleProjectChipsText');
        const btn = document.getElementById('toggleProjectChipsBtn');

        if (projectChipsExpanded) {
            extras.forEach(el => el.classList.remove('d-none'));
            if (container) container.style.maxHeight = '450px';
            if (textSpan) textSpan.innerText = 'Show Top 12';
            if (btn) btn.classList.replace('btn-outline-secondary', 'btn-primary');
        } else {
            const searchVal = document.getElementById('projectChipSearch')?.value.trim() || '';
            if (!searchVal) {
                extras.forEach(el => el.classList.add('d-none'));
                if (container) container.style.maxHeight = '245px';
            }
            if (textSpan) textSpan.innerText = `Show All (${document.querySelectorAll('.project-chip-item').length})`;
            if (btn) btn.classList.replace('btn-primary', 'btn-outline-secondary');
        }
    }

    // Instant live search filter for project breakdown cards
    function filterProjectChips(query) {
        const q = (query || '').toLowerCase().trim();
        const items = document.querySelectorAll('.project-chip-item');
        const container = document.getElementById('projectChipsContainer');

        if (q.length > 0) {
            if (container) container.style.maxHeight = '450px';
            items.forEach(item => {
                const name = item.getAttribute('data-project-name') || '';
                if (name.includes(q)) {
                    item.classList.remove('d-none');
                } else {
                    item.classList.add('d-none');
                }
            });
        } else {
            if (container) container.style.maxHeight = projectChipsExpanded ? '450px' : '245px';
            items.forEach(item => {
                if (item.classList.contains('project-chip-extra') && !projectChipsExpanded) {
                    item.classList.add('d-none');
                } else {
                    item.classList.remove('d-none');
                }
            });
        }
    }

    // Select Project filter from breakdown chips or dropdown
    function selectProjectFilter(projectId) {
        const select = document.getElementById('dueProjectFilter');
        if (select) {
            select.value = projectId || '';
        }
        // Update chip active states
        document.querySelectorAll('.project-chip').forEach(chip => chip.classList.remove('active'));
        if (projectId) {
            const activeChip = Array.from(document.querySelectorAll('.project-chip')).find(c => c.getAttribute('onclick') === `selectProjectFilter(${projectId})`);
            if (activeChip) activeChip.classList.add('active');
            document.getElementById('clearProjectFilterBtn')?.style.removeProperty('display');
        } else {
            const clearBtn = document.getElementById('clearProjectFilterBtn');
            if (clearBtn) clearBtn.style.display = 'none';
        }
        loadDueTrackingPage(1);
    }

    // Clear search box
    function clearDueSearch() {
        const input = document.getElementById('dueSearchInput');
        if (input) input.value = '';
        document.getElementById('dueClearSearch')?.classList.add('d-none');
        loadDueTrackingPage(1);
    }

    // Reset all filters to default
    function resetAllDueFilters() {
        document.getElementById('dueSearchInput').value = '';
        document.getElementById('dueProjectFilter').value = '';
        document.getElementById('dueDateType').value = 'booking_date';
        document.getElementById('dueStartDate').value = '';
        document.getElementById('dueEndDate').value = '';
        document.getElementById('dueSortBy').value = 'overall_due';
        document.getElementById('duePageLimit').value = '20';
        dueActiveType = 'all';
        document.querySelectorAll('.due-type-pill').forEach(btn => btn.classList.remove('active'));
        document.querySelector('.due-type-pill')?.classList.add('active');
        document.querySelectorAll('.project-chip').forEach(chip => chip.classList.remove('active'));
        const clearBtn = document.getElementById('clearProjectFilterBtn');
        if (clearBtn) clearBtn.style.display = 'none';
        const chipSearch = document.getElementById('projectChipSearch');
        if (chipSearch) {
            chipSearch.value = '';
            filterProjectChips('');
        }
        loadDueTrackingPage(1);
    }

    // Live search debouncing
    document.getElementById('dueSearchInput')?.addEventListener('input', function() {
        const clearBtn = document.getElementById('dueClearSearch');
        if (this.value.trim().length > 0) {
            clearBtn?.classList.remove('d-none');
        } else {
            clearBtn?.classList.add('d-none');
        }
        clearTimeout(dueSearchTimer);
        dueSearchTimer = setTimeout(() => {
            loadDueTrackingPage(1);
        }, 350);
    });

    // Date filters auto-reload
    document.getElementById('dueStartDate')?.addEventListener('change', () => loadDueTrackingPage(1));
    document.getElementById('dueEndDate')?.addEventListener('change', () => loadDueTrackingPage(1));

    // Core AJAX Page Loader
    function loadDueTrackingPage(page = 1) {
        dueCurrentPage = page;
        const search = document.getElementById('dueSearchInput')?.value.trim() || '';
        const projectId = document.getElementById('dueProjectFilter')?.value || '';
        const sortBy = document.getElementById('dueSortBy')?.value || 'overall_due';
        const dateType = document.getElementById('dueDateType')?.value || 'booking_date';
        const startDate = document.getElementById('dueStartDate')?.value || '';
        const endDate = document.getElementById('dueEndDate')?.value || '';
        const limit = document.getElementById('duePageLimit')?.value || '20';

        const tbody = document.getElementById('dueTrackingTableBody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="10" class="text-center py-5 text-muted"><div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>Loading due records...</td></tr>';
        }

        const params = new URLSearchParams({
            ajax: '1',
            page: page,
            limit: limit,
            due_type: dueActiveType,
            search: search,
            project_id: projectId,
            sort_by: sortBy,
            date_type: dateType,
            start_date: startDate,
            end_date: endDate
        });

        // Update CSV export link with active filters
        const exportBtn = document.getElementById('exportDueCsvBtn');
        if (exportBtn) {
            exportBtn.href = `${window.base_url || ''}/client-wise-due-tracking-export?${params.toString()}`;
        }

        // Update URL query string without reloading
        const newUrl = `${window.location.pathname}?${params.toString().replace('ajax=1&', '')}`;
        history.pushState(null, '', newUrl);

        fetch(`${window.base_url || ''}/client-wise-due-tracking?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (tbody) tbody.innerHTML = data.table_html;
                const pagination = document.getElementById('duePaginationContainer');
                if (pagination) pagination.innerHTML = data.pagination_html;
                const infoText = document.getElementById('dueInfoText');
                if (infoText) infoText.innerText = data.info_text;
                const totalCountSpan = document.getElementById('dueTotalCount');
                if (totalCountSpan) totalCountSpan.innerText = Number(data.totalRecords).toLocaleString();

                // Update Top KPI stats if returned
                if (data.stats) {
                    const s = data.stats;
                    const fmt = (num) => '৳' + Number(num || 0).toLocaleString();
                    if (document.getElementById('statOverallDue')) document.getElementById('statOverallDue').innerText = fmt(s.overall_due);
                    if (document.getElementById('statLandShareDue')) document.getElementById('statLandShareDue').innerText = fmt(s.total_land_share_due);
                    if (document.getElementById('statDevChargeDue')) document.getElementById('statDevChargeDue').innerText = fmt(s.total_dev_charge_due);
                    if (document.getElementById('statEmiDue')) document.getElementById('statEmiDue').innerText = fmt(s.total_emi_due);
                }
            } else {
                if (tbody) tbody.innerHTML = '<tr><td colspan="10" class="text-center py-4 text-danger">Failed to load data.</td></tr>';
            }
        })
        .catch(err => {
            console.error('Error loading due tracking data:', err);
            if (tbody) tbody.innerHTML = '<tr><td colspan="10" class="text-center py-4 text-danger">Error loading data. Please try again.</td></tr>';
        });
    }

    // Refresh data handler
    function refreshDueTracking(force = false) {
        loadDueTrackingPage(dueCurrentPage);
    }

    // Open Quick Client Due Breakdown Modal
    function openClientDueBreakdownModal(bookingId) {
        const modal = new bootstrap.Modal(document.getElementById('clientDueBreakdownModal'));
        const body = document.getElementById('clientDueModalBody');
        const title = document.getElementById('clientDueModalTitle');
        const ledgerBtn = document.getElementById('clientDueModalLedgerBtn');

        title.innerText = `Client Due Breakdown #${bookingId}`;
        ledgerBtn.href = `${window.base_url || ''}/clients-cash-in-history-view/${bookingId}`;
        body.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary mb-2" role="status"></div>
                <p class="text-muted mb-0">Loading booking financial details...</p>
            </div>
        `;
        modal.show();

        fetch(`${window.base_url || ''}/client-wise-due-tracking?ajax=client_detail&booking_id=${bookingId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.summary && data.details) {
                    const s = data.summary;
                    const d = data.details;
                    const fmt = (num) => '৳' + Number(num || 0).toLocaleString();

                    body.innerHTML = `
                        <!-- Client Header -->
                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <div class="row align-items-center">
                                <div class="col-md-7">
                                    <h5 class="fw-bold mb-1 text-dark">${d.client_name || 'N/A'}</h5>
                                    <div class="text-muted extra-small">
                                        <i class="fa-solid fa-phone me-1"></i>${d.phone || 'N/A'} | 
                                        <i class="fa-solid fa-envelope me-1"></i>${d.email || 'N/A'}
                                    </div>
                                </div>
                                <div class="col-md-5 text-md-end mt-2 mt-md-0">
                                    <span class="badge bg-primary fs-12">${d.project_name || 'Project'}</span>
                                    <div class="fw-semibold text-dark extra-small mt-1">
                                        Unit: ${d.unit_name || 'Unit'} - Floor ${d.flat_no || ''}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4 Pillars Breakdown -->
                        <div class="row g-2 mb-3">
                            <!-- 1. Land Share -->
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 h-100 bg-white border-start border-4 border-warning">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong class="text-dark fs-13"><i class="fa-solid fa-map-location-dot text-warning me-1"></i> Land Share</strong>
                                        <span class="badge ${s.remaining_land_share <= 0 ? 'bg-success' : 'bg-danger'}">${s.remaining_land_share <= 0 ? 'Cleared' : 'Due: ' + fmt(s.remaining_land_share)}</span>
                                    </div>
                                    <div class="extra-small text-muted">
                                        Target: <strong>${fmt(s.total_land_share)}</strong><br>
                                        Booking Money Paid: <strong>${fmt(s.booking_money)}</strong><br>
                                        Registration Fee Paid: <strong>${fmt(s.registration_paid)}</strong><br>
                                        Total Land Share Paid: <strong>${fmt(s.total_land_share_paid)}</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Development Charges -->
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 h-100 bg-white border-start border-4 border-info">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong class="text-dark fs-13"><i class="fa-solid fa-screwdriver-wrench text-info me-1"></i> Dev. Charges</strong>
                                        <span class="badge ${s.is_dev_charge_completed ? 'bg-success' : 'bg-danger'}">${s.is_dev_charge_completed ? 'Cleared' : 'Due: ' + fmt((s.flat_development_charges || 0) - (s.development_charge_paid || 0))}</span>
                                    </div>
                                    <div class="extra-small text-muted">
                                        Dev. Target: <strong>${fmt(s.flat_development_charges)}</strong><br>
                                        Dev. Charges Paid: <strong>${fmt(s.development_charge_paid)}</strong><br>
                                        Status: <strong>${s.is_dev_charge_completed ? 'Fully Completed' : 'Pending Payment'}</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Construction & EMI -->
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 h-100 bg-white border-start border-4 border-primary">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong class="text-dark fs-13"><i class="fa-solid fa-building-columns text-primary me-1"></i> Construction & EMI</strong>
                                        <span class="badge ${s.remaining_construction_fee <= 0 ? 'bg-success' : 'bg-danger'}">${s.remaining_construction_fee <= 0 ? 'Cleared' : 'Due: ' + fmt(s.remaining_construction_fee)}</span>
                                    </div>
                                    <div class="extra-small text-muted">
                                        Total Construction Fee: <strong>${fmt(s.construction_fee)}</strong><br>
                                        EMI Paid: <strong>${fmt(s.construction_fee_paid)}</strong> (${s.completed_emi || 0} / ${s.total_emi || 0} EMIs)<br>
                                        Per EMI Rate: <strong>${fmt(s.per_emi_amount)}</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- 4. Overall Position -->
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 h-100 bg-white border-start border-4 border-danger">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong class="text-dark fs-13"><i class="fa-solid fa-vault text-danger me-1"></i> Overall Account Due</strong>
                                        <span class="badge ${s.total_due <= 0 ? 'bg-success' : 'bg-danger'}">${s.total_due <= 0 ? 'All Clear ✓' : 'Due: ' + fmt(s.total_due)}</span>
                                    </div>
                                    <div class="extra-small text-muted">
                                        Total Flat Value: <strong>${fmt(s.total_price)}</strong><br>
                                        Discount Approved: <strong>${fmt(s.discount_amount)}</strong><br>
                                        Total Confirmed Paid: <strong>${fmt(s.total_paid)}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    body.innerHTML = '<div class="alert alert-danger mb-0">Failed to load client details.</div>';
                }
            })
            .catch(err => {
                console.error(err);
                body.innerHTML = '<div class="alert alert-danger mb-0">An error occurred while fetching details.</div>';
            });
    }
</script>

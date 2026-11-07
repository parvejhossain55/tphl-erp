<?php
$base_url            = $GLOBALS['base_url'];
$refunds             = $refunds ?? [];
$summaryStats        = $summaryStats ?? [];
$projects            = $projects ?? [];
$companyBanks        = $companyBanks ?? [];
$allBangladeshBanks  = $allBangladeshBanks ?? [];
$filters             = $filters ?? [];
$page                = $page ?? 1;
$perPage             = $perPage ?? 15;
$totalRecords        = $totalRecords ?? count($refunds);
$totalPages          = $totalPages ?? 1;
$canCreate           = $canCreate ?? false;
$userRole            = $userRole ?? ($_SESSION['user']['role_id'] ?? 0);
$currentUserId       = $_SESSION['user']['id'] ?? 0;

$statusMap = [
    'pending'  => ['label' => 'Pending Approval', 'class' => 'bg-warning text-dark', 'icon' => 'fa-clock'],
    'approved' => ['label' => 'Approved (Awaiting Payout)', 'class' => 'bg-info text-white', 'icon' => 'fa-badge-check'],
    'refunded' => ['label' => 'Refund Paid', 'class' => 'bg-success text-white', 'icon' => 'fa-check-double'],
    'rejected' => ['label' => 'Rejected', 'class' => 'bg-danger text-white', 'icon' => 'fa-circle-xmark'],
];
?>

<style>
/* Client Refund Report Styling */
.refund-kpi-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border-radius: 10px;
    border: 1px solid rgba(0,0,0,0.06);
    background: #fff;
}
.refund-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(0,0,0,0.06);
}
.kpi-icon-wrap {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    font-size: 20px;
    flex-shrink: 0;
}
.project-track-pill {
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid #dee2e6;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 8px 14px;
    min-width: 170px;
    text-decoration: none;
    display: inline-block;
    color: #212529;
}
.project-track-pill:hover,
.project-track-pill.active {
    background: #0d6efd;
    color: #fff !important;
    border-color: #0d6efd;
    box-shadow: 0 4px 10px rgba(13, 110, 253, 0.25);
}
.project-track-pill.active .text-muted,
.project-track-pill.active small,
.project-track-pill:hover .text-muted,
.project-track-pill:hover small {
    color: rgba(255,255,255,0.85) !important;
}
.project-track-pill.active .badge-sub,
.project-track-pill:hover .badge-sub {
    background: rgba(255,255,255,0.25) !important;
    color: #fff !important;
}
.badge-sub {
    font-size: 11px;
    background: #e9ecef;
    color: #495057;
    border-radius: 4px;
    padding: 2px 6px;
    font-weight: 600;
}
.table-report th {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background-color: #f8f9fa;
    color: #495057;
    font-weight: 600;
    vertical-align: middle;
    padding: 10px 12px;
}
.table-report td {
    font-size: 13px;
    vertical-align: middle;
    padding: 10px 12px;
}
.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.7);
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    backdrop-filter: blur(1px);
}
.client-name-cell {
    max-width: 260px;
    word-break: break-word;
}
.retention-highlight {
    background: rgba(220, 53, 69, 0.08);
    padding: 3px 6px;
    border-radius: 4px;
    font-weight: 700;
    color: #dc3545;
}
.refund-highlight {
    background: rgba(25, 135, 84, 0.08);
    padding: 3px 6px;
    border-radius: 4px;
    font-weight: 700;
    color: #198754;
}

/* ========================================================================= */
/* PRINT-SPECIFIC CSS: HIDE ALL CARDS, SEARCH, BUTTONS, SHOW ONLY CLEAN REPORT */
/* ========================================================================= */
@media print {
    @page {
        size: landscape;
        margin: 6mm 8mm;
    }
    html, body {
        background: #fff !important;
        color: #000 !important;
        font-size: 11px !important;
        line-height: 1.3 !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* 1. HIDE ALL WEB UI, SIDEBARS, NAV, SYSTEM FOOTERS, BREADCRUMBS, MODALS */
    #sidebar,
    .app-sidebar,
    .sidebar,
    .main-header,
    .app-header,
    .navbar,
    .header-wrapper,
    footer,
    .main-footer,
    .footer,
    .btn,
    button,
    .modal,
    .modal-backdrop,
    #tableLoadingOverlay,
    .page-header-breadcrumb,
    .d-print-none {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
        width: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* 2. HIDE CARDS, TRACKING PILLS & SEARCH FILTER FORM IN PRINT */
    #kpiStatsRow,
    #projectTrackCard,
    #filterCard,
    .card-footer,
    .refund-kpi-card {
        display: none !important;
    }

    /* 3. SHOW PRINT HEADER */
    .print-report-header {
        display: block !important;
        margin-bottom: 12px !important;
    }

    /* 4. CONTAINER & TABLE FORMATTING */
    .container-fluid {
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    .card.custom-card {
        border: none !important;
        box-shadow: none !important;
        margin: 0 !important;
        padding: 0 !important;
        background: transparent !important;
    }

    .card-header {
        display: none !important;
    }

    .table-responsive {
        overflow: visible !important;
        display: block !important;
    }

    .table-report {
        width: 100% !important;
        border-collapse: collapse !important;
        border: 1.5px solid #000 !important;
        font-size: 10.5px !important;
    }

    .table-report thead th {
        background-color: #f1f3f5 !important;
        color: #000 !important;
        font-weight: 700 !important;
        border: 1px solid #333 !important;
        padding: 6px 6px !important;
        font-size: 10px !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .table-report tbody td,
    .table-report tfoot td {
        border: 1px solid #444 !important;
        padding: 5px 6px !important;
        color: #000 !important;
    }

    .table-report tfoot {
        display: table-footer-group !important;
        font-weight: bold !important;
        background-color: #f8f9fa !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .retention-highlight {
        background: transparent !important;
        color: #b02a37 !important;
        font-weight: 700 !important;
        padding: 0 !important;
    }

    .refund-highlight {
        background: transparent !important;
        color: #146c43 !important;
        font-weight: 700 !important;
        padding: 0 !important;
    }

    .badge {
        border: 1px solid #666 !important;
        color: #000 !important;
        background: transparent !important;
        padding: 1px 4px !important;
        font-size: 9px !important;
    }

    .col-action {
        display: none !important;
    }
}
</style>

<!-- ========================================================================= -->
<!-- PRINT-ONLY CLEAN CORPORATE REPORT HEADER -->
<!-- ========================================================================= -->
<div class="print-report-header d-none d-print-block mb-3 border-bottom pb-2">
    <div class="d-flex align-items-center justify-content-between mb-1">
        <div>
            <h4 class="fw-bold mb-0 text-dark" style="letter-spacing: 0.5px;">TRUST PROPERTY HOLDINGS LTD.</h4>
            <div class="fs-12 text-muted fw-semibold">Client Refund Statement & Transaction Registry</div>
        </div>
        <div class="text-end fs-11 text-muted">
            <div><strong>Print Date:</strong> <?= date('d M Y, h:i A') ?></div>
            <div><strong>Printed By:</strong> <?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Accounts Department') ?></div>
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-center fs-11 text-dark pt-1 border-top border-secondary mt-1">
        <div><strong>Total Records:</strong> <?= $totalRecords ?> Cases</div>
        <div><strong>Total Collection (Paid):</strong> ৳ <?= number_format($summaryStats['total_verified_paid'] ?? 0, 2) ?></div>
        <div><strong>Total Company Retention:</strong> ৳ <?= number_format($summaryStats['total_retention'] ?? 0, 2) ?></div>
        <div><strong>Total Net Refund:</strong> ৳ <?= number_format($summaryStats['total_refund_amount'] ?? 0, 2) ?></div>
        <div><strong>Disbursed Paid:</strong> ৳ <?= number_format($summaryStats['total_refunded_paid'] ?? 0, 2) ?></div>
    </div>
</div>

<!-- PAGE HEADER (WEB VIEW ONLY) -->
<div class="d-md-flex d-block align-items-center justify-content-between my-3 page-header-breadcrumb d-print-none">
    <div>
        <h4 class="page-title fw-bold fs-18 mb-1">
            <i class="fa-light fa-hand-holding-dollar text-primary me-2"></i>Client Refund Requests & Statement Report
        </h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 fs-12">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/accounts-dashboard">Accounts</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/clients-cash-in-history">Cash In</a></li>
                <li class="breadcrumb-item active" aria-current="page">Client Refunds</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2 mt-md-0 mt-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.location.reload();" title="Refresh Data">
            <i class="fa-light fa-arrows-rotate me-1"></i> Refresh
        </button>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print();" title="Print Statement Report">
            <i class="fa-light fa-print me-1"></i> Print Report
        </button>
    </div>
</div>

<div class="container-fluid px-0">

    <div class="d-print-none">
        <?php include __DIR__ . '/../../layouts/management_approval_notice.php'; ?>
    </div>

    <!-- ========================================================================= -->
    <!-- 1. SUMMARY KPI STATS (WEB VIEW ONLY) -->
    <!-- ========================================================================= -->
    <div class="row  mb-3 d-print-none" id="kpiStatsRow">
        <!-- 1. Total Retention (Company Cut/Profit) - EMPHASIZED AS REQUESTED -->
        <div class="col-xxl-3 col-xl-6 col-md-6">
            <div class="card refund-kpi-card shadow-sm h-100" style="border-left: 4px solid #fd7e14 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="fs-11 text-uppercase fw-bold text-muted d-block">
                                <i class="fa-solid fa-shield-halved me-1 text-warning"></i> Total Retention Amount
                            </span>
                            <h3 class="fw-bold mb-1 fs-20 mt-1" id="kpiTotalRetention">
                                ৳ <?= number_format($summaryStats['total_retention'] ?? 0, 2) ?>
                            </h3>
                            <div class="fs-11 text-muted" id="kpiRetentionSubtitle">
                                Realized (Paid): <strong class="text-danger" id="kpiRealizedRetention">৳ <?= number_format($summaryStats['refunded_retention'] ?? 0, 2) ?></strong>
                                <span class="ms-1 text-muted">(Pending: <span id="kpiPendingRetention">৳ <?= number_format($summaryStats['pending_retention'] ?? 0, 2) ?></span>)</span>
                            </div>
                        </div>
                        <div class="kpi-icon-wrap bg-warning bg-opacity-10">
                            <i class="fa-solid fa-piggy-bank fs-4 text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Total Net Refund Volume (Payable / Paid) -->
        <div class="col-xxl-3 col-xl-6 col-md-6">
            <div class="card refund-kpi-card shadow-sm h-100" style="border-left: 4px solid #198754 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="fs-11 text-uppercase fw-bold text-muted d-block">
                                <i class="fa-solid fa-money-bill-transfer me-1 text-success"></i> Total Net Refund
                            </span>
                            <h3 class="fw-bold mb-1 fs-20 mt-1" id="kpiTotalRefund">
                                ৳ <?= number_format($summaryStats['total_refund_amount'] ?? 0, 2) ?>
                            </h3>
                            <div class="fs-11 text-muted" id="kpiRefundSubtitle">
                                Paid Disbursed: <strong class="text-success" id="kpiTotalDisbursed">৳ <?= number_format($summaryStats['total_refunded_paid'] ?? 0, 2) ?></strong>
                                <span class="ms-1 text-muted">(Pending: <span id="kpiPendingRefund">৳ <?= number_format($summaryStats['pending_refund_amount'] ?? 0, 2) ?></span>)</span>
                            </div>
                        </div>
                        <div class="kpi-icon-wrap bg-success bg-opacity-10">
                            <i class="fa-solid fa-money-check-dollar fs-4 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Total Client Cash-In Verified -->
        <div class="col-xxl-3 col-xl-6 col-md-6">
            <div class="card refund-kpi-card shadow-sm h-100" style="border-left: 4px solid #0d6efd !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="fs-11 text-uppercase fw-bold d-block text-muted">
                                <i class="fa-solid fa-wallet me-1 text-primary"></i> Total Verified Paid
                            </span>
                            <h3 class="fw-bold mb-1 fs-20 mt-1" id="kpiTotalPaid">
                                ৳ <?= number_format($summaryStats['total_verified_paid'] ?? 0, 2) ?>
                            </h3>
                            <div class="fs-11 text-muted" id="kpiPaidSubtitle">
                                Disbursed Cases: <strong class="text-primary" id="kpiDisbursedPaid">৳ <?= number_format($summaryStats['refunded_total_paid'] ?? 0, 2) ?></strong>
                            </div>
                        </div>
                        <div class="kpi-icon-wrap bg-primary bg-opacity-10">
                            <i class="fa-solid fa-receipt fs-4 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Total Requests Count & Status Breakdown -->
        <div class="col-xxl-3 col-xl-6 col-md-6">
            <div class="card refund-kpi-card shadow-sm h-100" style="border-left: 4px solid #6f42c1 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div>
                            <span class="fs-11 text-uppercase fw-bold  d-block">
                                <i class="fa-solid fa-list-check  me-1"></i> Refund Requests
                            </span>
                            <h3 class="fw-bold mb-0 text-dark fs-20" id="kpiTotalRequests">
                                <?= number_format($summaryStats['total_requests'] ?? 0) ?> Cases
                            </h3>
                        </div>
                        <div class="kpi-icon-wrap bg-purple bg-opacity-10 " style="background: rgba(111, 66, 193, 0.1); color: #6f42c1;">
                            <i class="fa-solid fa-file-invoice fs-4"></i>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-1 mt-1">
                        <span class="badge bg-warning text-dark fs-10" id="badgePendingCount" title="Pending Management/Accounts Approval">
                            <i class="fa-regular fa-clock me-1"></i><?= $summaryStats['pending_count'] ?? 0 ?> Pending
                        </span>
                        <span class="badge bg-info text-white fs-10" id="badgeApprovedCount" title="Approved, ready for payout">
                            <i class="fa-solid fa-check me-1"></i><?= $summaryStats['approved_count'] ?? 0 ?> Approved
                        </span>
                        <span class="badge bg-success text-white fs-10" id="badgeRefundedCount" title="Payment Disbursed">
                            <i class="fa-solid fa-check-double me-1"></i><?= $summaryStats['refunded_count'] ?? 0 ?> Paid
                        </span>
                        <?php if (!empty($summaryStats['rejected_count'])): ?>
                        <span class="badge bg-danger text-white fs-10" id="badgeRejectedCount">
                            <i class="fa-solid fa-xmark me-1"></i><?= $summaryStats['rejected_count'] ?> Rejected
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. PROJECT-WISE REFUND TRACKING (WEB VIEW ONLY) -->
    <!-- ========================================================================= -->
    <div class="card custom-card border-0 shadow-sm mb-3 d-print-none" id="projectTrackCard">
        <div class="card-header bg-light py-2 px-3 d-flex align-items-center justify-content-between">
            <h6 class="card-title mb-0 fw-bold fs-13 text-dark">
                <i class="fa-solid fa-city text-primary me-2"></i>Project-Wise Refund Tracking
            </h6>
            <span class="text-muted fs-11">Click any project to filter table</span>
        </div>
        <div class="card-body p-2.5">
            <div class="d-flex flex-wrap gap-2 align-items-center" id="projectTrackContainer">
                <a href="javascript:void(0);" class="project-track-pill <?= (empty($filters['project_id']) || $filters['project_id'] === 'all') ? 'active' : '' ?>" onclick="filterByProject('all', this)">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong class="fs-12">All Projects</strong>
                        <span class="badge-sub"><?= $summaryStats['total_requests'] ?? 0 ?></span>
                    </div>
                    <div class="d-flex justify-content-between fs-11 text-muted">
                        <span>Ret: <strong class="text-danger me-2">৳<?= number_format($summaryStats['total_retention'] ?? 0) ?></strong></span>
                        <span>Ref: <strong class="text-success">৳<?= number_format($summaryStats['total_refund_amount'] ?? 0) ?></strong></span>
                    </div>
                </a>

                <?php if (!empty($summaryStats['project_breakdown'])): ?>
                    <?php foreach ($summaryStats['project_breakdown'] as $pb): 
                        $isActive = (!empty($filters['project_id']) && (int)$filters['project_id'] === (int)$pb['project_id']);
                    ?>
                    <a href="javascript:void(0);" 
                       class="project-track-pill <?= $isActive ? 'active' : '' ?>" 
                       onclick="filterByProject(<?= (int)$pb['project_id'] ?>, this)"
                       title="<?= htmlspecialchars($pb['project_name']) ?>">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="fs-12 text-truncate" style="max-width: 130px;"><?= htmlspecialchars($pb['project_name']) ?></strong>
                            <span class="badge-sub"><?= $pb['total_refunds'] ?></span>
                        </div>
                        <div class="d-flex justify-content-between fs-11 text-muted">
                            <span>Ret: <strong class="text-danger me-2">৳<?= number_format($pb['total_retention']) ?></strong></span>
                            <span>Ref: <strong class="text-success">৳<?= number_format($pb['total_refund']) ?></strong></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span class="text-muted fs-12 fst-italic">No project refund history found yet.</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. ADVANCED SEARCH & FILTER BAR (WEB VIEW ONLY) -->
    <!-- ========================================================================= -->
    <div class="card custom-card border-0 shadow-sm mb-3 d-print-none" id="filterCard">
        <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
            <h6 class="card-title mb-0 fw-semibold fs-13 text-secondary">
                <i class="fa-light fa-filter me-2 text-primary"></i>Filters & Search Criteria
            </h6>
            <button type="button" class="btn btn-outline-danger btn-sm py-0.5 px-2 fs-11" onclick="resetFilters()">
                <i class="fa-light fa-rotate-left me-1"></i> Reset Filters
            </button>
        </div>
        <div class="card-body p-3">
            <form id="refundFilterForm" class="row g-2 align-items-end" onsubmit="event.preventDefault(); applyFilters(1);">
                <!-- Search Keyword -->
                <div class="col-xl-3 col-l col-md-6">
                    <label class="form-label fs-11 fw-semibold text-muted mb-1">Search Client / Phone / Flat</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="fa-light fa-magnifying-glass"></i></span>
                        <input type="text" id="filterSearch" name="search" class="form-control" 
                               placeholder="Client, phone, flat, reason..." 
                               value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                    </div>
                </div>

                <!-- Project Select -->
                <div class="col-xl-3 col-l col-md-6">
                    <label class="form-label fs-11 fw-semibold text-muted mb-1">Project</label>
                    <select id="filterProject" name="project_id" class="form-select form-select-sm">
                        <option value="all">-- All Projects --</option>
                        <?php foreach ($projects as $pr): ?>
                            <option value="<?= $pr['id'] ?>" <?= (!empty($filters['project_id']) && $filters['project_id'] == $pr['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pr['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Status Select -->
                <div class="col-xl-2 col-lg-2 col-md-4">
                    <label class="form-label fs-11 fw-semibold text-muted mb-1">Status</label>
                    <select id="filterStatus" name="status" class="form-select form-select-sm">
                        <option value="all">-- All Statuses --</option>
                        <option value="pending" <?= (!empty($filters['status']) && $filters['status'] === 'pending') ? 'selected' : '' ?>>Pending Approval</option>
                        <option value="approved" <?= (!empty($filters['status']) && $filters['status'] === 'approved') ? 'selected' : '' ?>>Approved (Awaiting Payout)</option>
                        <option value="refunded" <?= (!empty($filters['status']) && $filters['status'] === 'refunded') ? 'selected' : '' ?>>Refund Paid</option>
                        <option value="rejected" <?= (!empty($filters['status']) && $filters['status'] === 'rejected') ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>

                <!-- Date Range From -->
                <div class="col-xl-2 col-lg-2 col-md-4">
                    <label class="form-label fs-11 fw-semibold text-muted mb-1">Date Range</label>
                    <div class="input-group input-group-sm">
                        <input type="date" id="filterDateFrom" name="date_from" class="form-control" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>" title="From Date">
                        <input type="date" id="filterDateTo" name="date_to" class="form-control" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>" title="To Date">
                    </div>
                </div>

                <!-- Per Page -->
                <div class="col-xl-1 col-lg-1 col-md-2">
                    <label class="form-label fs-11 fw-semibold text-muted mb-1">Per Page</label>
                    <select id="filterPerPage" name="per_page" class="form-select form-select-sm" onchange="applyFilters(1)">
                        <option value="10" <?= $perPage == 10 ? 'selected' : '' ?>>10</option>
                        <option value="15" <?= $perPage == 15 ? 'selected' : '' ?>>15</option>
                        <option value="25" <?= $perPage == 25 ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= $perPage == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $perPage == 100 ? 'selected' : '' ?>>100</option>
                    </select>
                </div>

                <!-- Actions -->
                <div class="col-xl-1 col-lg-1 col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold" title="Apply Filter">
                        <i class="fa-light fa-filter"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. REFUND REPORT DATA TABLE (PRINT & WEB) -->
    <!-- ========================================================================= -->
    <div class="card custom-card border-0 shadow-sm position-relative">
        <!-- Spinner Overlay -->
        <div id="tableLoadingOverlay" class="loading-overlay d-print-none" style="display:none;">
            <div class="spinner-border text-primary" role="status" style="width: 2.5rem; height: 2.5rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between d-print-none">
            <div class="d-flex align-items-center gap-2">
                <h6 class="card-title mb-0 fw-bold fs-14 text-dark">
                    <i class="fa-light fa-table-list text-primary me-2"></i>Refund Transactions & Status Registry
                </h6>
                <span class="badge bg-light text-dark border font-monospace fs-11" id="recordCountBadge">
                    <?= $totalRecords ?> Records
                </span>
            </div>
            <div class="fs-12 text-muted" id="paginationSummaryText">
                Showing <?= min(1, $totalRecords) ?> to <?= min($perPage, $totalRecords) ?> of <?= $totalRecords ?> entries
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-report mb-0" id="refundTable">
                    <thead>
                        <tr>
                            <th style="width: 40px;" class="text-center">#</th>
                            <th style="min-width: 180px;">Client & Contact</th>
                            <th style="min-width: 150px;">Project & Flat</th>
                            <th style="min-width: 110px;" class="text-end">Total Paid (৳)</th>
                            <th style="min-width: 110px;" class="text-end text-danger">Retention (৳)</th>
                            <th style="min-width: 115px;" class="text-end text-success">Net Refund (৳)</th>
                            <th style="min-width: 100px;" class="text-center">Status</th>
                            <th style="min-width: 130px;">Requested By</th>
                            <th style="min-width: 130px;">Approved By</th>
                            <th style="width: 100px;" class="text-center col-action d-print-none">Action</th>
                        </tr>
                    </thead>
                    <tbody id="refundTableBody">
                        <?php if (empty($refunds)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fa-light fa-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        <h6 class="fs-14 fw-semibold mb-1">No refund records found</h6>
                                        <p class="fs-12 text-muted mb-0">Try changing your search keywords or clear the filters.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($refunds as $idx => $r): 
                                $sInfo = $statusMap[$r['status']] ?? ['label' => ucfirst($r['status']), 'class' => 'bg-secondary text-white', 'icon' => 'fa-circle-info'];
                                $rowNum = ($page - 1) * $perPage + $idx + 1;
                            ?>
                            <tr id="refund-row-<?= $r['id'] ?>">
                                <td class="text-center text-muted font-monospace"><?= $rowNum ?></td>
                                <td class="client-name-cell">
                                    <div class="fw-bold text-dark fs-13"><?= htmlspecialchars($r['client_name'] ?? '—') ?></div>
                                    <?php if (!empty($r['client_phone'])): ?>
                                        <div class="text-muted small fs-11">
                                            <i class="fa-light fa-phone me-1"></i><?= htmlspecialchars($r['client_phone']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($r['reason'])): ?>
                                        <div class="mt-1" style="max-width: 260px;">
                                            <span class="badge bg-warning bg-opacity-25 text-dark border border-warning px-1 py-0 me-1" style="font-size: 10px;">
                                                <i class="fa-solid fa-comment-dots text-primary me-1"></i>Note:
                                            </span>
                                            <span class="text-muted small fst-italic" style="font-size: 11.5px;"><?= htmlspecialchars($r['reason']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle mb-1 fs-11">
                                        <i class="fa-light fa-city me-1"></i><?= htmlspecialchars($r['project_name'] ?? '—') ?>
                                    </span>
                                    <div class="fw-semibold text-dark fs-12">
                                        <?= htmlspecialchars($r['flat_name'] ?? '—') ?>
                                        <?php if (!empty($r['floor_no'])): ?>
                                            <span class="text-muted small">(Floor <?= $r['floor_no'] ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-end fw-semibold text-dark font-monospace">
                                    ৳ <?= number_format($r['total_paid'], 2) ?>
                                </td>
                                <td class="text-end font-monospace">
                                    <span class="retention-highlight">
                                        ৳ <?= number_format($r['retention_amount'], 2) ?>
                                    </span>
                                </td>
                                <td class="text-end font-monospace">
                                    <span class="refund-highlight fs-13">
                                        ৳ <?= number_format($r['refund_amount'], 2) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $sInfo['class'] ?> fs-10 px-2 py-1">
                                        <i class="fa-solid <?= $sInfo['icon'] ?> me-1"></i><?= $sInfo['label'] ?>
                                    </span>
                                </td>
                                <td class="fs-12">
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($r['requested_by_name'] ?? '—') ?></div>
                                    <span class="text-muted fs-11 d-block">
                                        <?= !empty($r['created_at']) ? date('d M Y, h:i A', strtotime($r['created_at'])) : '—' ?>
                                    </span>
                                </td>
                                <td class="fs-12">
                                    <?php if (!empty($r['approved_by_name'])): ?>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($r['approved_by_name']) ?></div>
                                        <span class="text-muted fs-11 d-block">
                                            <?= !empty($r['approved_at']) ? date('d M Y, h:i A', strtotime($r['approved_at'])) : '—' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center text-nowrap col-action d-print-none">
                                    <a href="<?= $base_url ?>/client-refund/<?= $r['id'] ?>" class="btn btn-xs btn-outline-primary py-1 px-2" title="View Details">
                                        <i class="fa-light fa-eye me-1"></i> View
                                    </a>
                                    <?php if ($r['status'] === 'pending' && in_array($userRole, [1, 3, 6, 7, 9, 10, 20, 30, 33, 37])): ?>
                                        <button type="button" class="btn btn-xs btn-outline-warning py-1 px-2 text-dark ms-1 fw-semibold" 
                                                onclick="openQuickRetentionModal(<?= htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8') ?>)" 
                                                title="Cut Retention Amount or %">
                                            <i class="fa-light fa-scissors text-warning me-1"></i> Retention
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($r['status'] === 'approved' && in_array($userRole, [1, 3, 6, 10, 30])): ?>
                                        <button type="button" class="btn btn-xs btn-success py-1 px-2 text-white ms-1" 
                                                onclick="openQuickPayModal(<?= htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8') ?>)" 
                                                title="Disburse Payment">
                                            <i class="fa-light fa-money-bill-wave me-1"></i> Pay
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($r['status'] === 'pending' && $r['requested_by'] == $currentUserId): ?>
                                        <form action="<?= $base_url ?>/client-refund-delete/<?= $r['id'] ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete this refund request?');">
                                            <button type="submit" class="btn btn-xs btn-outline-danger py-1 px-1.5 ms-1" title="Delete">
                                                <i class="fa-light fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-light fw-bold font-monospace">
                        <tr>
                            <td colspan="3" class="text-end text-uppercase fs-11">Total:</td>
                            <td class="text-end" id="tfootTotalPaid">৳ <?= number_format($summaryStats['total_verified_paid'] ?? 0, 2) ?></td>
                            <td class="text-end text-danger" id="tfootTotalRetention">৳ <?= number_format($summaryStats['total_retention'] ?? 0, 2) ?></td>
                            <td class="text-end text-success" id="tfootTotalRefund">৳ <?= number_format($summaryStats['total_refund_amount'] ?? 0, 2) ?></td>
                            <td colspan="3" class="text-center font-sans-serif fs-11">
                                <?= $summaryStats['total_requests'] ?? 0 ?> Cases (<?= $summaryStats['pending_count'] ?? 0 ?> Pending, <?= $summaryStats['approved_count'] ?? 0 ?> Approved, <?= $summaryStats['refunded_count'] ?? 0 ?> Paid)
                            </td>
                            <td class="col-action d-print-none"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Card Footer with Pagination Controls (WEB VIEW ONLY) -->
        <div class="card-footer bg-light py-2 px-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-2 d-print-none">
            <div class="fs-12 text-muted" id="footerPaginationText">
                Showing page <strong id="curPageNum"><?= $page ?></strong> of <strong id="totalPageNum"><?= max(1, $totalPages) ?></strong>
            </div>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0" id="paginationControls">
                    <!-- Populated by JS -->
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 4.9 QUICK RETENTION CUT-OFF MODAL (Dual % and ৳ with Live Calculations) -->
<!-- ========================================================================= -->
<div class="modal fade d-print-none" id="quickRetentionModal" tabindex="-1" aria-labelledby="quickRetentionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark py-2.5 px-3">
                <h5 class="modal-title fs-15 fw-bold" id="quickRetentionModalLabel">
                    <i class="fa-light fa-scissors me-2"></i>Accounts Retention Cut-Off (<span id="qretModalRefundId">#0</span>)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickRetentionForm" action="" method="POST">
                <input type="hidden" name="ajax" value="1">
                <div class="modal-body p-3">
                    <!-- Client & Total Paid Banner -->
                    <div class="p-3 rounded-2 bg-light border mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="text-muted small d-block">Client & Flat:</span>
                                <strong class="text-dark fs-14" id="qretClientName">—</strong>
                                <div class="text-muted small" id="qretProjectFlat">—</div>
                            </div>
                            <div class="text-end">
                                <span class="text-muted small d-block">Total Verified Paid:</span>
                                <strong class="text-primary fs-16 font-monospace" id="qretTotalPaidDisplay">৳ 0.00</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Quick % Buttons -->
                    <div class="mb-3">
                        <label class="form-label fs-12 fw-semibold text-muted d-flex justify-content-between mb-1">
                            <span>Quick Cut-Off Percentage (%)</span>
                            <span class="text-danger fw-normal fs-11">Deducted from Total Paid</span>
                        </label>
                        <div class="d-flex flex-wrap gap-1">
                            <?php foreach ([0, 5, 10, 15, 20, 25, 30] as $pct): ?>
                                <button type="button" class="btn btn-xs btn-outline-warning py-1 px-2.5 text-dark fw-bold qret-pct-btn" data-pct="<?= $pct ?>"><?= $pct ?>%</button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Dual Inputs: % and ৳ -->
                    <div class="row g-2 mb-3">
                        <div class="col-5">
                            <label class="form-label fs-12 fw-semibold text-danger">Retention (%)</label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" min="0" max="100" name="retention_percent" id="qretPercentInput" class="form-control fw-bold border-danger text-danger" placeholder="0 - 100">
                                <span class="input-group-text bg-danger text-white fw-bold">%</span>
                            </div>
                        </div>
                        <div class="col-7">
                            <label class="form-label fs-12 fw-semibold text-danger">Retention Amount (৳)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light fw-bold">৳</span>
                                <input type="number" step="0.01" min="0" name="retention_amount" id="qretAmountInput" class="form-control fw-bold border-danger text-danger" placeholder="0.00" required>
                            </div>
                        </div>
                    </div>

                    <!-- Net Refund Amount Preview -->
                    <div class="mb-2">
                        <label class="form-label fs-12 fw-semibold text-success">Net Refund to Client (৳)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-success text-white fw-bold">৳</span>
                            <input type="number" step="0.01" id="qretNetRefundDisplay" class="form-control fw-bold border-success text-success bg-light" readonly>
                        </div>
                        <small class="text-muted fs-11 d-block mt-1">
                            • Net Refund = Total Paid - Company Retention.<br>
                            • Once saved, this request will be forwarded for Management &amp; Role 20 approval.
                        </small>
                    </div>

                    <div id="qretErrorAlert" class="alert alert-danger py-1.5 px-2 small mb-0 mt-2" style="display: none;"></div>
                </div>
                <div class="modal-footer py-2 px-3 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="qretSubmitBtn" class="btn btn-warning btn-sm fw-bold px-3">
                        <i class="fa-light fa-check-double me-1"></i> Save Retention Cut-Off
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 5. QUICK DISBURSE PAYMENT MODAL (Accounts Payout matching Image 2) -->
<!-- ========================================================================= -->
<div class="modal fade d-print-none" id="quickPayModal" tabindex="-1" aria-labelledby="quickPayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white py-2.5 px-3">
                <h5 class="modal-title fs-15 fw-bold text-white" id="quickPayModalLabel">
                    <i class="fa-solid fa-money-bill-transfer me-2"></i>Accounts Refund Disbursement Payout
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickPayForm" action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-3">
                    <!-- Client & Amount Summary Banner -->
                    <div class="p-3 rounded-2 bg-light border mb-3">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <span class="text-muted small d-block">Client & Flat:</span>
                                <strong class="text-dark fs-14" id="modalClientName">—</strong>
                                <div class="text-muted small" id="modalProjectFlat">—</div>
                                <div class="mt-1 fs-12 text-primary" id="modalBankExpectedNote" style="display:none;"></div>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <span class="text-muted small d-block">Approved Net Refund to Pay:</span>
                                <strong class="text-success fs-18 font-monospace" id="modalRefundAmount">৳ 0.00</strong>
                                <div class="text-danger small" id="modalRetentionNote">Retention: ৳ 0.00</div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Form matching Image 2 -->
                    <div class="row ">
                        <!-- Payment Method * -->
                        <div class="col-md-6">
                            <label class="form-label fs-12 fw-semibold text-dark">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method" id="modalPaymentMethod" class="form-select form-select-sm" required onchange="toggleModalFields(this.value)">
                                <option value="bank" selected>Bank</option>
                                <option value="cheque">Cheque</option>
                                <option value="cash">Cash</option>
                                <option value="online">Online</option>
                            </select>
                        </div>

                        <!-- Transfer From / Company Bank * -->
                        <div class="col-md-6" id="modalCompanyBankCol">
                            <label class="form-label fs-12 fw-semibold text-dark">Transfer To / Company Bank <span class="text-danger">*</span></label>
                            <select name="company_bank_id" id="modalCompanyBankSelect" class="form-select form-select-sm">
                                <option value="">-- Select Transfer To / Company Bank --</option>
                                <?php foreach ($companyBanks as $cb): ?>
                                    <option value="<?= $cb['id'] ?>">
                                        <?= htmlspecialchars($cb['bank_name'] . (!empty($cb['account_name']) ? ' - ' . $cb['account_name'] : '') . (!empty($cb['account_number']) ? ' (' . $cb['account_number'] . ')' : '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Transfer To / Client Bank * -->
                        <div class="col-md-6" id="modalClientBankCol">
                            <label class="form-label fs-12 fw-semibold text-dark">Transfer From / Client Bank <span class="text-danger">*</span></label>
                            <select name="bank_name" id="modalClientBankSelect" class="form-select form-select-sm" style="width: 100%;">
                                <option value="">-- Select Client Bank --</option>
                                <option value="Other Country / Foreign Bank">Other Country / Foreign Bank</option>
                                <option value="Other Bank">Other Bank</option>
                                <?php foreach ($allBangladeshBanks as $bName): ?>
                                    <option value="<?= htmlspecialchars($bName) ?>"><?= htmlspecialchars($bName) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Client Bank A/C No. (Optional) -->
                        <div class="col-md-6" id="modalClientAcCol">
                            <label class="form-label fs-12 fw-semibold text-dark">Client Bank A/C No. <span class="text-muted">(Optional)</span></label>
                            <input type="text" name="bank_account_no" id="modalClientAcNo" class="form-control form-control-sm" placeholder="Account Number">
                        </div>

                        <!-- Cheque Number (When Cheque selected) -->
                        <div class="col-md-6" id="modalChequeCol" style="display:none;">
                            <label class="form-label fs-12 fw-semibold text-dark">Cheque Number <span class="text-danger">*</span></label>
                            <input type="text" name="cheque_no" id="modalChequeNo" class="form-control form-control-sm" placeholder="Enter cheque number">
                        </div>

                        <!-- Payment Date * -->
                        <div class="col-md-6">
                            <label class="form-label fs-12 fw-semibold text-dark">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" id="modalPaymentDate" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <!-- Transaction Reference -->
                        <div class="col-md-6" id="modalRefCol">
                            <label class="form-label fs-12 fw-semibold text-dark">Transaction Reference</label>
                            <input type="text" name="transaction_ref" id="modalTransactionRef" class="form-control form-control-sm" placeholder="Required reference number">
                        </div>

                        <!-- Attachment Image (Mandatory) -->
                        <div class="col-md-12">
                            <label class="form-label fs-12 fw-semibold text-dark">
                                <i class="fa-light fa-paperclip text-primary me-1"></i> Attachment Image / Voucher Proof <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="disbursement_proof" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" required>
                            <small class="text-muted fs-11 d-block mt-1">
                                Mandatory: Upload scanned copy/photo of cheque, bank transfer slip, or signed voucher (Max 10MB).
                            </small>
                        </div>

                        <!-- Remarks / Notes -->
                        <div class="col-md-12">
                            <label class="form-label fs-12 fw-semibold text-dark">Remarks / Payment Notes</label>
                            <textarea name="remarks" class="form-control form-control-sm" rows="2" placeholder="Optional payout details or remarks..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm fw-bold px-3">
                        <i class="fa-light fa-check me-1"></i> Disburse & Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 6. AJAX FILTERING & PAGINATION JAVASCRIPT -->
<!-- ========================================================================= -->
<script>
let currentPage = <?= (int)$page ?>;
let totalPages  = <?= (int)max(1, $totalPages) ?>;
let currentFilterProjectId = '<?= htmlspecialchars($filters['project_id'] ?? 'all') ?>';
let userRole = <?= (int)$userRole ?>;
let currentUserId = <?= (int)$currentUserId ?>;
let baseUrl = '<?= $base_url ?>';

const statusMapJs = {
    'pending':  { label: 'Pending Approval', class: 'bg-warning text-dark', icon: 'fa-clock' },
    'approved': { label: 'Approved (Awaiting Payout)', class: 'bg-info text-white', icon: 'fa-badge-check' },
    'refunded': { label: 'Refund Paid', class: 'bg-success text-white', icon: 'fa-check-double' },
    'rejected': { label: 'Rejected', class: 'bg-danger text-white', icon: 'fa-circle-xmark' }
};

function formatMoney(amount) {
    let num = parseFloat(amount || 0);
    return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function escapeHtml(text) {
    if (!text) return '';
    return text.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function filterByProject(projectId, pillEl) {
    currentFilterProjectId = projectId.toString();
    const projSelect = document.getElementById('filterProject');
    if (projSelect) {
        projSelect.value = currentFilterProjectId;
    }
    // Update pills active status
    document.querySelectorAll('.project-track-pill').forEach(el => el.classList.remove('active'));
    if (pillEl) {
        pillEl.classList.add('active');
    }
    applyFilters(1);
}

function resetFilters() {
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterProject').value = 'all';
    document.getElementById('filterStatus').value = 'all';
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value = '';
    document.getElementById('filterPerPage').value = '15';
    currentFilterProjectId = 'all';

    document.querySelectorAll('.project-track-pill').forEach(el => el.classList.remove('active'));
    const firstPill = document.querySelector('.project-track-pill');
    if (firstPill) firstPill.classList.add('active');

    applyFilters(1);
}

function applyFilters(page = 1) {
    currentPage = page;
    const overlay = document.getElementById('tableLoadingOverlay');
    if (overlay) overlay.style.display = 'flex';

    const search    = document.getElementById('filterSearch').value.trim();
    const projectId = document.getElementById('filterProject').value;
    const status    = document.getElementById('filterStatus').value;
    const dateFrom  = document.getElementById('filterDateFrom').value;
    const dateTo    = document.getElementById('filterDateTo').value;
    const perPage   = document.getElementById('filterPerPage').value;

    const params = new URLSearchParams({
        ajax: '1',
        page: currentPage,
        per_page: perPage,
        search: search,
        project_id: projectId,
        status: status,
        date_from: dateFrom,
        date_to: dateTo
    });

    fetch(`${baseUrl}/client-refunds?${params.toString()}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (overlay) overlay.style.display = 'none';
        if (data.success) {
            renderTableRows(data.data, data.page, data.per_page);
            renderPagination(data.page, data.total_pages, data.total);
            updateSummaryKpis(data.summary_stats);
        } else {
            console.error('Failed to load refund data', data);
        }
    })
    .catch(err => {
        if (overlay) overlay.style.display = 'none';
        console.error('AJAX Error:', err);
    });
}

function renderTableRows(rows, page, perPage) {
    const tbody = document.getElementById('refundTableBody');
    if (!tbody) return;

    if (!rows || rows.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" class="text-center py-5">
                    <div class="text-muted">
                        <i class="fa-light fa-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        <h6 class="fs-14 fw-semibold mb-1">No refund records found</h6>
                        <p class="fs-12 text-muted mb-0">Try changing your search keywords or clear the filters.</p>
                    </div>
                </td>
            </tr>`;
        return;
    }

    let html = '';
    const accountsRoles = [1, 3, 6, 10, 30];

    rows.forEach((r, idx) => {
        const rowNum = (page - 1) * perPage + idx + 1;
        const sInfo = statusMapJs[r.status] || { label: r.status, class: 'bg-secondary text-white', icon: 'fa-circle-info' };
        
        let clientPhoneHtml = r.client_phone ? `<div class="text-muted small fs-11"><i class="fa-light fa-phone me-1"></i>${escapeHtml(r.client_phone)}</div>` : '';
        let noteHtml = r.reason ? `
            <div class="mt-1" style="max-width: 260px;">
                <span class="badge bg-warning bg-opacity-25 text-dark border border-warning px-1 py-0 me-1" style="font-size: 10px;">
                    <i class="fa-solid fa-comment-dots text-primary me-1"></i>Note:
                </span>
                <span class="text-muted small fst-italic" style="font-size: 11.5px;">${escapeHtml(r.reason)}</span>
            </div>` : '';

        let floorHtml = r.floor_no ? `<span class="text-muted small">(Floor ${escapeHtml(r.floor_no)})</span>` : '';
        let approverHtml = r.approved_by_name ? `
            <div class="fw-semibold text-dark">${escapeHtml(r.approved_by_name)}</div>
            <span class="text-muted fs-11 d-block">${r.approved_at || '—'}</span>` : '<span class="text-muted">—</span>';

        let retentionBtnHtml = '';
        const retentionRoles = [1, 3, 6, 7, 9, 10, 20, 30, 33, 37];
        if (r.status === 'pending' && retentionRoles.includes(userRole)) {
            retentionBtnHtml = `
                <button type="button" class="btn btn-xs btn-outline-warning py-1 px-2 text-dark ms-1 fw-semibold" 
                        onclick='openQuickRetentionModal(${JSON.stringify(r)})' 
                        title="Cut Retention Amount or %">
                    <i class="fa-light fa-scissors text-warning me-1"></i> Retention
                </button>`;
        }

        let payBtnHtml = '';
        if (r.status === 'approved' && accountsRoles.includes(userRole)) {
            payBtnHtml = `
                <button type="button" class="btn btn-xs btn-success py-1 px-2 text-white ms-1" 
                        onclick='openQuickPayModal(${JSON.stringify(r)})' 
                        title="Disburse Payment">
                    <i class="fa-light fa-money-bill-wave me-1"></i> Pay
                </button>`;
        }

        let deleteBtnHtml = '';
        if (r.status === 'pending' && r.requested_by == currentUserId) {
            deleteBtnHtml = `
                <form action="${baseUrl}/client-refund-delete/${r.id}" method="POST" class="d-inline" onsubmit="return confirm('Delete this refund request?');">
                    <button type="submit" class="btn btn-xs btn-outline-danger py-1 px-1.5 ms-1" title="Delete">
                        <i class="fa-light fa-trash"></i>
                    </button>
                </form>`;
        }

        html += `
            <tr id="refund-row-${r.id}">
                <td class="text-center text-muted font-monospace">${rowNum}</td>
                <td class="client-name-cell">
                    <div class="fw-bold text-dark fs-13">${escapeHtml(r.client_name || '—')}</div>
                    ${clientPhoneHtml}
                    ${noteHtml}
                </td>
                <td>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle mb-1 fs-11">
                        <i class="fa-light fa-city me-1"></i>${escapeHtml(r.project_name || '—')}
                    </span>
                    <div class="fw-semibold text-dark fs-12">
                        ${escapeHtml(r.flat_name || '—')} ${floorHtml}
                    </div>
                </td>
                <td class="text-end fw-semibold text-dark font-monospace">৳ ${formatMoney(r.total_paid)}</td>
                <td class="text-end font-monospace">
                    <span class="retention-highlight">৳ ${formatMoney(r.retention_amount)}</span>
                </td>
                <td class="text-end font-monospace">
                    <span class="refund-highlight fs-13">৳ ${formatMoney(r.refund_amount)}</span>
                </td>
                <td class="text-center">
                    <span class="badge ${sInfo.class} fs-10 px-2 py-1">
                        <i class="fa-solid ${sInfo.icon} me-1"></i>${sInfo.label}
                    </span>
                </td>
                <td class="fs-12">
                    <div class="fw-semibold text-dark">${escapeHtml(r.requested_by_name || '—')}</div>
                    <span class="text-muted fs-11 d-block">${r.created_at || '—'}</span>
                </td>
                <td class="fs-12">${approverHtml}</td>
                <td class="text-center text-nowrap col-action d-print-none">
                    <a href="${baseUrl}/client-refund/${r.id}" class="btn btn-xs btn-outline-primary py-1 px-2" title="View Details">
                        <i class="fa-light fa-eye me-1"></i> View
                    </a>
                    ${retentionBtnHtml}
                    ${payBtnHtml}
                    ${deleteBtnHtml}
                </td>
            </tr>`;
    });

    tbody.innerHTML = html;
}

function renderPagination(page, totalPgs, totalRecs) {
    currentPage = page;
    totalPages  = Math.max(1, totalPgs);

    const badge = document.getElementById('recordCountBadge');
    if (badge) badge.innerText = `${totalRecs} Records`;

    const curPageSpan = document.getElementById('curPageNum');
    if (curPageSpan) curPageSpan.innerText = page;

    const totPageSpan = document.getElementById('totalPageNum');
    if (totPageSpan) totPageSpan.innerText = totalPages;

    const perPage = parseInt(document.getElementById('filterPerPage')?.value || 15);
    const start = totalRecs === 0 ? 0 : (page - 1) * perPage + 1;
    const end   = Math.min(page * perPage, totalRecs);

    const summaryText = document.getElementById('paginationSummaryText');
    if (summaryText) {
        summaryText.innerText = `Showing ${start} to ${end} of ${totalRecs} entries`;
    }

    const container = document.getElementById('paginationControls');
    if (!container) return;

    let html = '';

    // First & Previous
    html += `
        <li class="page-item ${page <= 1 ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0);" onclick="applyFilters(1)" title="First"><i class="fa-solid fa-angles-left"></i></a>
        </li>
        <li class="page-item ${page <= 1 ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0);" onclick="applyFilters(${page - 1})" title="Previous"><i class="fa-solid fa-angle-left"></i></a>
        </li>`;

    let startPage = Math.max(1, page - 2);
    let endPage   = Math.min(totalPages, page + 2);

    for (let p = startPage; p <= endPage; p++) {
        html += `
            <li class="page-item ${p === page ? 'active' : ''}">
                <a class="page-link" href="javascript:void(0);" onclick="applyFilters(${p})">${p}</a>
            </li>`;
    }

    // Next & Last
    html += `
        <li class="page-item ${page >= totalPages ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0);" onclick="applyFilters(${page + 1})" title="Next"><i class="fa-solid fa-angle-right"></i></a>
        </li>
        <li class="page-item ${page >= totalPages ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0);" onclick="applyFilters(${totalPages})" title="Last"><i class="fa-solid fa-angles-right"></i></a>
        </li>`;

    container.innerHTML = html;
}

function updateSummaryKpis(stats) {
    if (!stats) return;
    if (document.getElementById('kpiTotalRetention')) {
        document.getElementById('kpiTotalRetention').innerText = '৳ ' + formatMoney(stats.total_retention);
    }
    if (document.getElementById('kpiRealizedRetention')) {
        document.getElementById('kpiRealizedRetention').innerText = '৳ ' + formatMoney(stats.refunded_retention || 0);
    }
    if (document.getElementById('kpiPendingRetention')) {
        document.getElementById('kpiPendingRetention').innerText = '৳ ' + formatMoney(stats.pending_retention || 0);
    }
    if (document.getElementById('kpiTotalRefund')) {
        document.getElementById('kpiTotalRefund').innerText = '৳ ' + formatMoney(stats.total_refund_amount);
    }
    if (document.getElementById('kpiTotalDisbursed')) {
        document.getElementById('kpiTotalDisbursed').innerText = '৳ ' + formatMoney(stats.total_refunded_paid || 0);
    }
    if (document.getElementById('kpiPendingRefund')) {
        document.getElementById('kpiPendingRefund').innerText = '৳ ' + formatMoney(stats.pending_refund_amount || 0);
    }
    if (document.getElementById('kpiTotalPaid')) {
        document.getElementById('kpiTotalPaid').innerText = '৳ ' + formatMoney(stats.total_verified_paid);
    }
    if (document.getElementById('kpiDisbursedPaid')) {
        document.getElementById('kpiDisbursedPaid').innerText = '৳ ' + formatMoney(stats.refunded_total_paid || 0);
    }
    if (document.getElementById('kpiTotalRequests')) {
        document.getElementById('kpiTotalRequests').innerText = (stats.total_requests || 0) + ' Cases';
    }
    if (document.getElementById('badgePendingCount')) {
        document.getElementById('badgePendingCount').innerHTML = `<i class="fa-regular fa-clock me-1"></i>${stats.pending_count || 0} Pending`;
    }
    if (document.getElementById('badgeApprovedCount')) {
        document.getElementById('badgeApprovedCount').innerHTML = `<i class="fa-solid fa-check me-1"></i>${stats.approved_count || 0} Approved`;
    }
    if (document.getElementById('badgeRefundedCount')) {
        document.getElementById('badgeRefundedCount').innerHTML = `<i class="fa-solid fa-check-double me-1"></i>${stats.refunded_count || 0} Paid`;
    }

    // Also update tfoot totals
    if (document.getElementById('tfootTotalPaid')) {
        document.getElementById('tfootTotalPaid').innerText = '৳ ' + formatMoney(stats.total_verified_paid);
    }
    if (document.getElementById('tfootTotalRetention')) {
        document.getElementById('tfootTotalRetention').innerText = '৳ ' + formatMoney(stats.total_retention);
    }
    if (document.getElementById('tfootTotalRefund')) {
        document.getElementById('tfootTotalRefund').innerText = '৳ ' + formatMoney(stats.total_refund_amount);
    }
}

// Quick Pay Modal Handler
function openQuickPayModal(refund) {
    const form = document.getElementById('quickPayForm');
    if (!form || !refund) return;

    form.action = `${baseUrl}/client-refund-disburse/${refund.id}?redirect=client-refunds`;

    document.getElementById('modalClientName').innerText = (refund.client_name || 'Client') + (refund.client_phone ? ' (' + refund.client_phone + ')' : '');
    document.getElementById('modalProjectFlat').innerText = (refund.project_name || 'Project') + ' — Flat: ' + (refund.flat_name || 'N/A') + (refund.floor_no ? ' (Floor ' + refund.floor_no + ')' : '');
    document.getElementById('modalRefundAmount').innerText = '৳ ' + formatMoney(refund.refund_amount);
    document.getElementById('modalRetentionNote').innerText = 'Retention: ৳ ' + formatMoney(refund.retention_amount) + ' | Total Paid: ৳ ' + formatMoney(refund.total_paid);

    // Payment method & client bank pre-filling from sales submission
    const preMethod = refund.preferred_payment_method || 'bank';
    document.getElementById('modalPaymentMethod').value = preMethod;
    toggleModalFields(preMethod);

    const clientBankSel = document.getElementById('modalClientBankSelect');
    if (clientBankSel && refund.client_bank_name) {
        clientBankSel.value = refund.client_bank_name;
    }
    const clientAcInput = document.getElementById('modalClientAcNo');
    if (clientAcInput && refund.client_bank_account_no) {
        clientAcInput.value = refund.client_bank_account_no;
    }

    const noteEl = document.getElementById('modalBankExpectedNote');
    if (noteEl) {
        let notes = [];
        if (refund.client_bank_name) notes.push('Client Bank: <strong>' + refund.client_bank_name + '</strong>' + (refund.client_bank_account_no ? ' (A/C: ' + refund.client_bank_account_no + ')' : ''));
        if (refund.expected_refund_date) notes.push('Expected: <strong>' + refund.expected_refund_date + '</strong>');
        if (notes.length > 0) {
            noteEl.innerHTML = '<i class="fa-solid fa-circle-info me-1"></i> ' + notes.join(' | ');
            noteEl.style.display = 'block';
        } else {
            noteEl.style.display = 'none';
        }
    }

    const modal = new bootstrap.Modal(document.getElementById('quickPayModal'));
    modal.show();
}

function toggleModalFields(method) {
    const coBankCol   = document.getElementById('modalCompanyBankCol');
    const clientBankCol = document.getElementById('modalClientBankCol');
    const clientAcCol   = document.getElementById('modalClientAcCol');
    const chequeCol     = document.getElementById('modalChequeCol');
    const refCol        = document.getElementById('modalRefCol');
    const coBankSel     = document.getElementById('modalCompanyBankSelect');
    const clientBankSel = document.getElementById('modalClientBankSelect');
    const chequeInput   = document.getElementById('modalChequeNo');

    if (method === 'bank') {
        if (coBankCol) coBankCol.style.display = 'block';
        if (clientBankCol) clientBankCol.style.display = 'block';
        if (clientAcCol) clientAcCol.style.display = 'block';
        if (chequeCol) chequeCol.style.display = 'none';
        if (refCol) refCol.style.display = 'block';
        if (coBankSel) coBankSel.required = true;
        if (clientBankSel) clientBankSel.required = true;
        if (chequeInput) chequeInput.required = false;
    } else if (method === 'cheque') {
        if (coBankCol) coBankCol.style.display = 'block';
        if (clientBankCol) clientBankCol.style.display = 'none';
        if (clientAcCol) clientAcCol.style.display = 'none';
        if (chequeCol) chequeCol.style.display = 'block';
        if (refCol) refCol.style.display = 'none';
        if (coBankSel) coBankSel.required = true;
        if (clientBankSel) clientBankSel.required = false;
        if (chequeInput) chequeInput.required = true;
    } else if (method === 'online') {
        if (coBankCol) coBankCol.style.display = 'block';
        if (clientBankCol) clientBankCol.style.display = 'block';
        if (clientAcCol) clientAcCol.style.display = 'block';
        if (chequeCol) chequeCol.style.display = 'none';
        if (refCol) refCol.style.display = 'block';
        if (coBankSel) coBankSel.required = true;
        if (clientBankSel) clientBankSel.required = false;
        if (chequeInput) chequeInput.required = false;
    } else { // cash
        if (coBankCol) coBankCol.style.display = 'none';
        if (clientBankCol) clientBankCol.style.display = 'none';
        if (clientAcCol) clientAcCol.style.display = 'none';
        if (chequeCol) chequeCol.style.display = 'none';
        if (refCol) refCol.style.display = 'none';
        if (coBankSel) coBankSel.required = false;
        if (clientBankSel) clientBankSel.required = false;
        if (chequeInput) chequeInput.required = false;
    }
}

// Initial pagination render on page load
document.addEventListener('DOMContentLoaded', function() {
    renderPagination(<?= (int)$page ?>, <?= (int)max(1, $totalPages) ?>, <?= (int)$totalRecords ?>);

    // Debounced search
    let searchTimeout;
    document.getElementById('filterSearch')?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            applyFilters(1);
        }, 400);
    });

    document.getElementById('filterProject')?.addEventListener('change', function() {
        currentFilterProjectId = this.value;
        document.querySelectorAll('.project-track-pill').forEach(el => el.classList.remove('active'));
        applyFilters(1);
    });

    document.getElementById('filterStatus')?.addEventListener('change', function() {
        applyFilters(1);
    });

    document.getElementById('filterDateFrom')?.addEventListener('change', function() {
        applyFilters(1);
    });

    document.getElementById('filterDateTo')?.addEventListener('change', function() {
        applyFilters(1);
    });

    // =========================================================================
    // QUICK RETENTION CUT-OFF MODAL HANDLERS
    // =========================================================================
    const qretPercent = document.getElementById('qretPercentInput');
    const qretAmount  = document.getElementById('qretAmountInput');
    const qretNet     = document.getElementById('qretNetRefundDisplay');

    if (qretPercent && qretAmount && qretNet) {
        qretPercent.addEventListener('input', function() {
            let p = parseFloat(this.value);
            if (isNaN(p) || p < 0) p = 0;
            if (p > 100) p = 100;
            let retAmt = (currentQretTotalPaid > 0) ? parseFloat(((currentQretTotalPaid * p) / 100).toFixed(2)) : 0;
            qretAmount.value = retAmt.toFixed(2);
            qretNet.value = Math.max(0, currentQretTotalPaid - retAmt).toFixed(2);
        });

        qretAmount.addEventListener('input', function() {
            let ret = parseFloat(this.value);
            if (isNaN(ret) || ret < 0) ret = 0;
            if (ret > currentQretTotalPaid) ret = currentQretTotalPaid;
            let p = (currentQretTotalPaid > 0) ? parseFloat(((ret / currentQretTotalPaid) * 100).toFixed(2)) : 0;
            qretPercent.value = p;
            qretNet.value = Math.max(0, currentQretTotalPaid - ret).toFixed(2);
        });

        document.querySelectorAll('.qret-pct-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                let p = parseFloat(this.dataset.pct);
                qretPercent.value = p;
                let retAmt = (currentQretTotalPaid > 0) ? parseFloat(((currentQretTotalPaid * p) / 100).toFixed(2)) : 0;
                qretAmount.value = retAmt.toFixed(2);
                qretNet.value = Math.max(0, currentQretTotalPaid - retAmt).toFixed(2);
            });
        });
    }

    const qretForm = document.getElementById('quickRetentionForm');
    if (qretForm) {
        qretForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('qretSubmitBtn');
            const errorAlert = document.getElementById('qretErrorAlert');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';
            }
            if (errorAlert) errorAlert.style.display = 'none';

            const formData = new FormData(qretForm);

            fetch(qretForm.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa-light fa-check-double me-1"></i> Save Retention Cut-Off';
                }
                if (data.success) {
                    if (quickRetentionModalObj) quickRetentionModalObj.hide();
                    applyFilters(currentPage);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Retention Saved!',
                            text: data.message || 'Retention cut-off has been successfully saved.',
                            timer: 2500,
                            showConfirmButton: false
                        });
                    } else {
                        alert(data.message || 'Retention cut-off saved successfully!');
                    }
                } else {
                    if (errorAlert) {
                        errorAlert.innerText = data.message || 'Failed to save retention cut-off.';
                        errorAlert.style.display = 'block';
                    } else {
                        alert(data.message || 'Failed to save retention.');
                    }
                }
            })
            .catch(err => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa-light fa-check-double me-1"></i> Save Retention Cut-Off';
                }
                if (errorAlert) {
                    errorAlert.innerText = 'Network error: ' + err.message;
                    errorAlert.style.display = 'block';
                }
            });
        });
    }
});

// Quick Retention Modal Opener
let currentQretTotalPaid = 0;
let quickRetentionModalObj = null;

function openQuickRetentionModal(refund) {
    if (!refund) return;
    const form = document.getElementById('quickRetentionForm');
    if (!form) return;

    form.action = `${baseUrl}/client-refund-adjust/${refund.id}?redirect=client-refunds`;

    document.getElementById('qretModalRefundId').innerText = '#' + refund.id;
    document.getElementById('qretClientName').innerText = (refund.client_name || 'Client') + (refund.client_phone ? ' (' + refund.client_phone + ')' : '');
    document.getElementById('qretProjectFlat').innerText = (refund.project_name || 'Project') + ' — ' + (refund.flat_name || 'N/A') + (refund.floor_no ? ' (Floor ' + refund.floor_no + ')' : '');

    currentQretTotalPaid = parseFloat(refund.total_paid || 0);
    document.getElementById('qretTotalPaidDisplay').innerText = '৳ ' + formatMoney(currentQretTotalPaid);

    let retAmt = parseFloat(refund.retention_amount || 0);
    let retPct = (currentQretTotalPaid > 0) ? parseFloat(((retAmt / currentQretTotalPaid) * 100).toFixed(2)) : 0;
    let netAmt = Math.max(0, currentQretTotalPaid - retAmt);

    document.getElementById('qretAmountInput').value = retAmt.toFixed(2);
    document.getElementById('qretAmountInput').max = currentQretTotalPaid;
    document.getElementById('qretPercentInput').value = retPct > 0 ? retPct : '';
    document.getElementById('qretNetRefundDisplay').value = netAmt.toFixed(2);
    
    const errAlert = document.getElementById('qretErrorAlert');
    if (errAlert) errAlert.style.display = 'none';

    if (!quickRetentionModalObj) {
        quickRetentionModalObj = new bootstrap.Modal(document.getElementById('quickRetentionModal'));
    }
    quickRetentionModalObj.show();
}
</script>

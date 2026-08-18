<?php
$page_title = "Home Dashboard";
$show_breadcrumb = false;
$chartLabels = $chartLabels ?? [];
$chartData = $chartData ?? [];

// If no chart data passed, try to generate from monthlyData
if (empty($chartLabels) && !empty($monthlyData)) {
    foreach ($monthlyData as $data) {
        $chartLabels[] = date('M Y', strtotime($data['month'] . '-01'));
        $chartData[] = floatval($data['total']);
    }
}

// Payment method data
$paymentMethodLabels = ['Cash', 'Bank', 'Cheque', 'Online'];
$paymentMethodData = [];
if (isset($statistics['by_payment_method'])) {
    $paymentMethodData = [
        $statistics['by_payment_method']['cash'] ?? 0,
        $statistics['by_payment_method']['bank'] ?? 0,
        $statistics['by_payment_method']['cheque'] ?? 0,
        $statistics['by_payment_method']['online'] ?? 0
    ];
} else {
    $paymentMethodData = [0, 0, 0, 0];
}

// Check if we have any data to show
$hasTransactions = !empty($recentTransactions);



?>
<div class="row mb-2">
    <div class="card__wrapper">
        <div class="btn__group mb-3 d-flex flex-nowrap overflow-auto gap-2">

            <a href="?period=daily"
                class="btn btn-sm flex-shrink-0 <?= ($period ?? '') === 'daily' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                Daily
            </a>

            <a href="?period=weekly"
                class="btn btn-sm flex-shrink-0 <?= ($period ?? '') === 'weekly' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                Weekly
            </a>

            <a href="?period=monthly"
                class="btn btn-sm flex-shrink-0 <?= ($period ?? '') === 'monthly' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                Monthly
            </a>

            <a href="?period=yearly"
                class="btn btn-sm flex-shrink-0 <?= ($period ?? '') === 'yearly' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                Yearly
            </a>

            <a href="<?= $GLOBALS['base_url'] ?>/home"
                class="btn btn-sm flex-shrink-0 <?= empty($period) || $period === 'all_time' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                All Over
            </a>

        </div>
    </div>
</div>

</div>
<div class="row  align-items-stretch">

    <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-6 mb-3">
        <div class="card__wrapper h-100 d-flex flex-column justify-content-center">
            <div class="d-flex align-items-center gap-3">
                <div class="card__icon">
                    <span><i class="fa-sharp fa-regular fa-users"></i></span>
                </div>
                <div class="ms-2 card__title-wrap flex-grow-1">
                    <h6 class="card__sub-title mb-2">Total Leads</h6>
                    <div class="d-flex align-items-end gap-2">
                        <h3 class="card__title"><?= $totalLeadsCount ?? '' ?></h3>
                        <span class="card__desc style_two">
                            <span class="<?= $leadsPercentChange >= 0 ? 'price-increase' : 'price-decrease' ?>">
                                <i class="fa-light <?= $leadsPercentChange >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' ?>"></i>
                                <?= abs($leadsPercentChange) ?>%
                            </span>
                            Than <?= $period ? ucfirst($period) : 'Last Month' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-6 mb-3">
        <div class="card__wrapper h-100 d-flex flex-column justify-content-center">
            <div class="d-flex align-items-center gap-3">
                <div class="card__icon">
                    <span><i class="fa-sharp fa-regular fa-gear"></i></span>
                </div>
                <div class="ms-2 card__title-wrap flex-grow-1">
                    <h6 class="card__sub-title mb-2">Total Projects</h6>
                    <h3 class="card__title"><?= $totalProjects ?? '' ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-6 mb-3">
        <div class="card__wrapper h-100 d-flex flex-column justify-content-center">
            <div class="d-flex align-items-center gap-3">
                <div class="card__icon">
                    <span><i class="fa-light fa-badge-check"></i></span>
                </div>
                <div class="ms-2 card__title-wrap flex-grow-1">
                    <h6 class="card__sub-title mb-2">Total Flat Booking</h6>
                    <h3 class="card__title"><?= $totalFlatsBooking ?? '' ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Employees Card -->
    <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-6 mb-3">
        <div class="card__wrapper h-100 d-flex flex-column justify-content-center dashboard-card">
            <div class="d-flex align-items-center gap-3">
                <div class="card__icon icon-indigo">
                    <span><i class="fa-solid fa-users-gear"></i></span>
                </div>
                <div class="ms-2 card__title-wrap flex-grow-1 overflow-hidden">
                    <h6 class="card__sub-title mb-1 text-muted">Total Employees</h6>
                    <h3 class="card__title mb-0 text-break"><?= $totalEmployees ?> <small class="text-muted extra-small fs-6"><?= $period ? "({$period})" : "" ?></small></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Manpower Requisition Pending Card -->
    <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-6 mb-3">
        <a href="<?= $GLOBALS['base_url'] ?>/requisition-lists?status=pending&open_pending=1" class="text-decoration-none">
            <div class="card__wrapper h-100 d-flex flex-column justify-content-center dashboard-card position-relative" style="border-left: 4px solid #f59e0b !important; background: linear-gradient(135deg, #fffdf5 0%, #ffffff 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="card__icon" style="background: rgba(245, 158, 11, 0.15); color: #d97706;">
                        <span><i class="fa-solid fa-user-clock fs-4"></i></span>
                    </div>
                    <div class="ms-2 card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-1 text-muted">Manpower Requisition Pending</h6>
                        <div class="d-flex align-items-center gap-2">
                            <h3 class="card__title mb-0 text-dark fw-bold"><?= $pendingManpowerCount ?? 0 ?></h3>
                            <?php if (($pendingManpowerCount ?? 0) > 0): ?>
                                <span class="badge bg-warning text-dark rounded-pill px-2 py-1 small">
                                    <i class="fa-solid fa-hourglass-half me-1"></i> Action Required
                                </span>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border rounded-pill px-2 py-1 small">Clean</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-6 mb-3">
        <div class="card__wrapper h-100">
            <div class="d-flex align-items-center gap-10">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-success">
                        <span><i class="fa-light fa-arrow-down"></i></span>
                    </div>
                    <div class="ms-2 card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-1 text-muted">Total Cash In</h6>
                        <h4 class="card__title mb-1 text-primary text-break">৳<?= format_bdt($overviewStats['total_cashin']) ?></h4>
                        <small class="text-muted extra-small d-block fw-semibold" style="font-size: 11px; line-height: 1.2;">
                            <i class="fa-solid fa-circle-info text-primary me-1"></i>Total Verified (Land Share + EMI Payments + Development Charge)
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-6 mb-3">
        <div class="card__wrapper h-100">
            <div class="d-flex align-items-center gap-10">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-danger">
                        <span><i class="fa-light fa-arrow-up"></i></span>
                    </div>
                    <div class="ms-2 card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-1 text-muted">Total Revenue</h6>
                        <h4 class="card__title mb-1 text-success text-break">৳<?= format_bdt($overviewStats['total_revenue']) ?></h4>
                        <small class="text-muted extra-small d-block fw-semibold" style="font-size: 11px; line-height: 1.2;">
                            <i class="fa-solid fa-circle-info text-success me-1"></i>(Total Verified EMI 10% + Booking + Dev Charge)
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-6 mb-3">
        <div class="card__wrapper h-100">
            <div class="d-flex align-items-center gap-10">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-danger">
                        <span><i class="fa-light fa-arrow-up"></i></span>
                    </div>
                    <div class="ms-2 card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-2 text-muted">Total Expenses</h6>
                        <h4 class="card__title mb-0 text-danger text-break">৳<?= format_bdt($overviewStats['total_cashout']) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ COLLECTION FINANCIAL SUMMARY CARDS (HOME DASHBOARD) ══ -->
    <div class="col-12 mb-3">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-chart-pie text-primary me-2"></i>Collection & EMI Financial Summary
                    </h6>
                    <small class="text-muted">Overview of total collections, land shares, dev charges, and EMI verification status</small>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="openProjectCollectionModal()">
                    <i class="fa-solid fa-list-check me-1"></i> View Project-Wise Breakdown
                </button>
            </div>
            <div class="card-body p-3">
                <div class="row g-3">
                    <!-- Total Collected -->
                    <div class="col-xl col-md-4 col-sm-6">
                        <div class="card__wrapper border-start border-4 border-primary h-100 p-3" style="cursor: pointer;" onclick="openProjectCollectionModal('total')" title="Click to view project breakdown (Verified + Unverified)">
                            <div class="d-flex align-items-center gap-2">
                                <div class="card__icon" style="background: rgba(13, 110, 253, 0.1); color: #0d6efd; padding: 10px; border-radius: 8px;">
                                    <span><i class="fa-solid fa-vault fs-5"></i></span>
                                </div>
                                <div class="card__title-wrap">
                                    <h6 class="card__sub-title mb-1 text-muted extra-small text-uppercase fw-bold">Total Collected</h6>
                                    <h5 class="fw-bold mb-0 text-primary">৳<?= number_format($collectionStats['total_collected'] ?? 0, 2) ?></h5>
                                    <small class="text-muted extra-small d-block" style="font-size: 10px;"><i class="fa-solid fa-circle-info me-1 text-primary"></i>Verified + Unverified (View Breakdown)</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Land Share Collected -->
                    <div class="col-xl col-md-4 col-sm-6">
                        <div class="card__wrapper border-start border-4 border-info h-100 p-3" style="cursor: pointer;" onclick="openProjectCollectionModal('land_share')" title="Click to view land share breakdown (Booking Money + Registration Fee)">
                            <div class="d-flex align-items-center gap-2">
                                <div class="card__icon" style="background: rgba(13, 202, 240, 0.1); color: #0dcaf0; padding: 10px; border-radius: 8px;">
                                    <span><i class="fa-solid fa-map-location-dot fs-5"></i></span>
                                </div>
                                <div class="card__title-wrap">
                                    <h6 class="card__sub-title mb-1 text-muted extra-small text-uppercase fw-bold">Land Share Collected</h6>
                                    <h5 class="fw-bold mb-0 text-info">৳<?= number_format($collectionStats['total_land_share_collected'] ?? 0, 2) ?></h5>
                                    <small class="text-muted extra-small d-block" style="font-size: 10px;"><i class="fa-solid fa-circle-info me-1 text-info"></i>Booking + Reg Fee (View Breakdown)</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total EMI Collected -->
                    <div class="col-xl col-md-4 col-sm-6">
                        <div class="card__wrapper border-start border-4 border-secondary h-100 p-3" style="cursor: pointer;" onclick="openProjectCollectionModal('emi')" title="Click to view total EMI breakdown (Verified + Unverified)">
                            <div class="d-flex align-items-center gap-2">
                                <div class="card__icon" style="background: rgba(108, 117, 125, 0.1); color: #6c757d; padding: 10px; border-radius: 8px;">
                                    <span><i class="fa-solid fa-receipt fs-5"></i></span>
                                </div>
                                <div class="card__title-wrap">
                                    <h6 class="card__sub-title mb-1 text-muted extra-small text-uppercase fw-bold">Total EMI Collected</h6>
                                    <h5 class="fw-bold mb-0 text-dark">৳<?= number_format($collectionStats['total_emi_collected'] ?? 0, 2) ?></h5>
                                    <small class="text-muted extra-small d-block" style="font-size: 10px;"><i class="fa-solid fa-circle-info me-1 text-secondary"></i>Verified + Unverified (View Breakdown)</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Verified EMI Collected -->
                    <div class="col-xl col-md-6 col-sm-6">
                        <div class="card__wrapper border-start border-4 border-success h-100 p-3" style="cursor: pointer;" onclick="openProjectCollectionModal('verified')" title="Click to view verified EMI breakdown">
                            <div class="d-flex align-items-center gap-2">
                                <div class="card__icon" style="background: rgba(25, 135, 84, 0.1); color: #198754; padding: 10px; border-radius: 8px;">
                                    <span><i class="fa-solid fa-circle-check fs-5"></i></span>
                                </div>
                                <div class="card__title-wrap">
                                    <h6 class="card__sub-title mb-1 text-muted extra-small text-uppercase fw-bold">Verified EMI</h6>
                                    <h5 class="fw-bold mb-0 text-success">৳<?= number_format($collectionStats['total_verified_emi_collected'] ?? 0, 2) ?></h5>
                                    <small class="text-muted extra-small"><i class="fa-solid fa-hand-pointer me-1"></i>View Breakdown</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Unverified EMI Collected -->
                    <div class="col-xl col-md-6 col-sm-6">
                        <div class="card__wrapper border-start border-4 border-warning h-100 p-3" style="cursor: pointer;" onclick="openProjectCollectionModal('unverified')" title="Click to view unverified EMI breakdown">
                            <div class="d-flex align-items-center gap-2">
                                <div class="card__icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107; padding: 10px; border-radius: 8px;">
                                    <span><i class="fa-solid fa-clock-rotate-left fs-5"></i></span>
                                </div>
                                <div class="card__title-wrap">
                                    <h6 class="card__sub-title mb-1 text-muted extra-small text-uppercase fw-bold">Unverified EMI</h6>
                                    <h5 class="fw-bold mb-0 text-warning">৳<?= number_format($collectionStats['total_unverified_emi_collected'] ?? 0, 2) ?></h5>
                                    <small class="text-muted extra-small"><i class="fa-solid fa-hand-pointer me-1"></i>View Breakdown</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- script in footer  -->
<div class="row equal-height-row">
    <!-- Booking Overview -->
    <div class="col-xxl-4 col-xl-12 col-lg-12 col-12 mb-3 ">
        <div class="card__wrapper height-equal">
            <div class="card__title-wrap d-flex flex-wrap gap-10 align-items-center justify-content-between mb-25">
                <h5 class="card__heading-title">Booking Overview</h5>
                <div class="card__tab">
                    <ul class="nav nav-pills" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pills-bar-year-tab" data-period="year" type="button">1Y</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-bar-month-tab" data-period="month" type="button">1M</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-bar-week-tab" data-period="week" type="button">1W</button>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card__content">
                <div class="card__line-chart">
                    <div id="salesChart"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Overview -->
    <div class="col-xxl-4 col-xl-12 col-lg-12 mb-3">
        <div class="card__wrapper height-equal">
            <div class="card__title-wrap d-flex flex-wrap gap-10 align-items-center justify-content-between mb-50">
                <h5 class="card__heading-title">Transaction Overview</h5>
                <div class="card__tab">
                    <ul class="nav nav-pills" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-bar-year-tab" data-bs-toggle="pill"
                                data-bs-target="#pills-bar-year" type="button" role="tab"
                                aria-controls="pills-bar-year" aria-selected="true">1Y</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pills-bar-month-tab" data-bs-toggle="pill"
                                data-bs-target="#pills-bar-month" type="button" role="tab"
                                aria-controls="pills-bar-month" aria-selected="true">1M</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-bar-week-tab"
                                data-bs-toggle="pill" data-bs-target="#pills-bar-week" type="button"
                                role="tab" aria-controls="pills-bar-week"
                                aria-selected="false">1W</button>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card__content">
                <div class="tab-content" id="pills-tabContent">
                    <div class="tab-pane fade" id="pills-bar-year" role="tabpanel"
                        aria-labelledby="pills-bar-year-tab">
                        <div class="chart-container">
                            <?php if (!empty($chartLabels)): ?>
                                <canvas id="transactionChartYearly" class="card__title"></canvas>
                            <?php else: ?>
                                <div class="no-data-placeholder">
                                    <i class="fa-light fa-chart-line"></i>
                                    <p>No yearly data available</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="tab-pane fade show active" id="pills-bar-month" role="tabpanel"
                        aria-labelledby="pills-bar-month-tab">
                        <div class="chart-container">
                            <?php if (!empty($chartLabels)): ?>
                                <canvas id="transactionChartMonthly" class="card__title"></canvas>
                            <?php else: ?>
                                <div class="no-data-placeholder">
                                    <i class="fa-light fa-chart-line"></i>
                                    <p>No monthly data available</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="pills-bar-week" role="tabpanel"
                        aria-labelledby="pills-bar-week-tab">
                        <div class="chart-container">
                            <canvas id="transactionChartWeek" class="card__title"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Assistant Widget -->
    <div class="col-xxl-4 col-xl-12 col-lg-12 mb-3">
        <div class="card__wrapper height-equal d-flex flex-column justify-content-between" style="padding: 24px;">
            <!-- Card Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="d-inline-flex align-items-center justify-content-center" style="color: var(--clr-theme-primary); font-size: 16px;">
                        <i class="fa-light fa-sparkles" style="color: var(--clr-theme-primary);"></i>
                    </span>
                    <h6 class="mb-0 fw-bold" style="font-size: 14.5px;">AI Assistant</h6>
                    <span class="badge fw-bold" style="font-size: 9px; padding: 3px 6px; border-radius: 6px; letter-spacing: 0.5px; text-transform: uppercase; background: rgba(var(--clr-theme-primary-rgb), 0.1); color: var(--clr-theme-primary); border: 1px solid rgba(var(--clr-theme-primary-rgb), 0.2);">Beta</span>
                </div>
                <a href="javascript:void(0)" class="text-muted" style="font-size: 14px;"><i class="fa-light fa-chevron-right"></i></a>
            </div>

            <!-- Container for switching views -->
            <div class="flex-grow-1 d-flex flex-column overflow-hidden" style="min-height: 290px;">
                
                <!-- 1. Default View (Insights list) -->
                <div id="ai-default-view" class="d-flex flex-column justify-content-between h-100">
                    <!-- AI Banner with cute robot avatar (no background as requested) -->
                    <div class="mb-3 d-flex align-items-center justify-content-between position-relative" style="background: transparent; border: none; padding: 12px 0;">
                        <div style="flex: 1; z-index: 1;">
                            <h5 class="fw-bold mb-1 " style="font-size: 15px; font-weight: 700; letter-spacing: -0.3px;">Hi Management! 👋</h5>
                            <p class="mb-0 text-muted" style="font-size: 11.5px; line-height: 1.45;">I've analyzed your business data and found some key insights.</p>
                        </div>
                        <div style="width: 75px; height: 75px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; z-index: 1; margin-left: 10px;">
                            <img src="<?= $GLOBALS['base_url'] ?>/public/assets/images/ai_robot_avatar.png" alt="AI Robot" style="width: 100%; height: 100%; object-fit: contain;">
                        </div>
                    </div>

                    <!-- Key Insights section inside a premium card container -->
                    <div class="flex-grow-1">
                        <div class="card__wrapper shadow-sm border mb-0" style="border-radius: 16px; border-color: #8ecd8e !important;  overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -1px rgba(0,0,0,0.02) !important;">
                            <!-- Header -->
                            <div class="d-flex align-items-center gap-2 px-3 py-3 border-bottom" style="border-color: #f1f5f9 !important;">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 28px; height: 28px; background: rgba(var(--clr-theme-primary-rgb), 0.08); color: var(--clr-theme-primary); font-size: 13px;">
                                    <i class="fa-light fa-sparkles"></i>
                                </span>
                                <span class="fw-bold mb-0 " style="font-size: 13px; letter-spacing: 0.3px;">Key Insights</span>
                            </div>
                            
                            <!-- Insight List -->
                            <div class="d-flex flex-column">
                                <!-- Insight 1: Total Booked Flats -->
                                <div class="d-flex align-items-center justify-content-between px-3 py-3 hover-bg-light border-bottom ai-insight-item" data-question="সিস্টেমে মোট কতটি ফ্ল্যাট বুকিং করা হয়েছে?" style="cursor: pointer; transition: all 0.2s; border-color: #f1f5f9 !important;">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 40px; height: 40px; background: rgba(99, 102, 241, 0.08); color: #6366f1; flex-shrink: 0; font-size: 16px;">
                                            <i class="fa-light fa-building"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="" style="font-size: 12.5px; font-weight: 600; line-height: 1.3;">
                                                Total flats booked: <strong style="color: var(--clr-theme-primary);"><?= $totalFlatsBooking ?? 0 ?></strong>
                                            </div>
                                            <div style="font-size: 10.5px; color: #64748b; margin-top: 2px;">Based on overall sales inventory data.</div>
                                        </div>
                                    </div>
                                    <div class="text-muted ps-2" style="font-size: 14px;"><i class="fa-light fa-chevron-right" style="color: #94a3b8;"></i></div>
                                </div>

                                <!-- Insight 2: Construction in Progress -->
                                <div class="d-flex align-items-center justify-content-between px-3 py-3 hover-bg-light border-bottom ai-insight-item" data-question="বর্তমানে কোন কোন প্রজেক্টে কনস্ট্রাকশনের কাজ চলছে?" style="cursor: pointer; transition: all 0.2s; border-color: #f1f5f9 !important;">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 40px; height: 40px; background: rgba(245, 158, 11, 0.08); color: #f59e0b; flex-shrink: 0; font-size: 16px;">
                                            <i class="fa-light fa-helmet-safety"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="" style="font-size: 12.5px; font-weight: 600; line-height: 1.3;">
                                                Construction in progress: <strong style="color: #f59e0b;"><?= $constructionProjectsCount ?? 0 ?></strong> projects
                                            </div>
                                            <div style="font-size: 10.5px; color: #64748b; margin-top: 2px;">Active phases currently under development.</div>
                                        </div>
                                    </div>
                                    <div class="text-muted ps-2" style="font-size: 14px;"><i class="fa-light fa-chevron-right" style="color: #94a3b8;"></i></div>
                                </div>

                                <!-- Insight 3: Net Cashflow -->
                                <div class="d-flex align-items-center justify-content-between px-3 py-3 hover-bg-light ai-insight-item" data-question="আমাদের টোটাল ক্যাশফ্লো এবং ক্যাশ ইন ও ক্যাশ আউট এর হিসাব দেখাও" style="cursor: pointer; transition: all 0.2s;">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 40px; height: 40px; background: rgba(16, 185, 129, 0.08); color: #10b981; flex-shrink: 0; font-size: 16px;">
                                            <i class="fa-light fa-wallet"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="" style="font-size: 12.5px; font-weight: 600; line-height: 1.3;">
                                                Net Cashflow: <strong style="color: #10b981;">৳<?= format_bdt(($overviewStats['total_cashin'] ?? 0) - ($overviewStats['total_cashout'] ?? 0)) ?></strong>
                                            </div>
                                            <div style="font-size: 10.5px; color: #64748b; margin-top: 2px;">Cash In: ৳<?= format_bdt($overviewStats['total_cashin'] ?? 0) ?> | Cash Out: ৳<?= format_bdt($overviewStats['total_cashout'] ?? 0) ?></div>
                                        </div>
                                    </div>
                                    <div class="text-muted ps-2" style="font-size: 14px;"><i class="fa-light fa-chevron-right" style="color: #94a3b8;"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Chat View (Conversation log) -->
                <div id="ai-chat-view" class="d-none d-flex flex-column h-100 justify-content-between">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                        <button id="ai-back-btn" class="btn btn-sm p-0 text-muted" style="font-size: 11px; font-weight: 600; background: transparent; border: none; box-shadow: none;">
                            <i class="fa-light fa-arrow-left"></i> Back to Insights
                        </button>
                        <button id="ai-clear-btn" class="btn btn-sm p-0 text-muted" style="font-size: 11px; background: transparent; border: none; box-shadow: none;" title="Clear conversation">
                            <i class="fa-light fa-trash"></i>
                        </button>
                    </div>
                    <!-- Messages Log -->
                    <div id="ai-messages-list" class="flex-grow-1 overflow-auto pe-1" style="max-height: 235px; font-size: 11.5px; display: flex; flex-direction: column; gap: 10px; scrollbar-width: thin;">
                        <!-- Messages dynamically added here -->
                    </div>
                </div>

            </div>

            <!-- Ask anything search input -->
            <div class="mt-3">
                <div class="d-flex align-items-center p-1 w-100 flex-nowrap" style="border: 1.5px solid green; border-radius: 24px; background: var(--clr-bg-body); box-shadow: 0 2px 8px rgba(var(--clr-theme-primary-rgb), 0.03); gap: 8px;">
                    <input type="text" id="ai-input" class="form-control border-0 bg-transparent flex-grow-1" placeholder="Ask me anything..." style="font-size: 12px; box-shadow: none; padding-left: 14px; min-width: 0;">
                    <button id="ai-send-btn" class="btn btn-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; min-width: 32px; min-height: 32px; border-radius: 50% !important; padding: 0; background: var(--clr-theme-primary); border: none;">
                        <i class="fa-light fa-arrow-right" style="font-size: 12px; color: #fff;"></i>
                    </button>
                </div>
                <div class="text-center mt-2" style="font-size: 9.5px; color: #94a3b8;">
                    AI insights are based on your data. <a href="javascript:void(0)" class="text-decoration-underline text-muted" style="color: inherit;">Learn more <i class="fa-light fa-info-circle" style="font-size: 10px;"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- project wise flat sale  -->

<!-- Booking Overview Section -->
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-0"> Overview</h4>
            <p class="text-muted small mb-0">Complete booking and payment summary</p>
        </div>
    </div>

    <!-- Payment Methods & Recent Bookings -->
    <div class="row ">

        <?php if ((int)($_SESSION['user']['role_id'] ?? 0) !== 33): ?>
        <div class="col-xl-6 col-lg-6 col-md-12 col-12 mt-2">
            <div class="card__wrapper h-100">
                <div class="card-header_title pt-3 border-bottom-0">
                    <h6 class="mb-3">Latest Approval Requests</h6>
                </div>


                <div class="card__body">
                    <div class="approval-scrollable" style="max-height: 500px; overflow-y: auto; padding: 1rem;">
                        <?php if (!empty($pendingRequests)): ?>
                            <?php foreach ($pendingRequests as $req): ?>
                                <div class="border rounded p-3 mb-3 shadow-sm">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($req['department']) ?> Department</h6>
                                            <small class="text-muted d-block"><?= htmlspecialchars($req['title']) ?></small>
                                            <small class="text-muted">Requested: <?= date('d M Y', strtotime($req['created_at'])) ?></small>
                                        </div>
                                        <span class="badge bg-warning "><?= ucfirst($req['status']) ?></span>
                                    </div>

                                    <div class="mb-2">
                                        <?php if ($req['source_table'] === 'employee_leaves'):
                                            $meta = json_decode($req['meta_data'], true);
                                            $requested_days = $meta['requested_days'] ?? 0;
                                        ?>
                                            <strong>Requested Days:</strong> <?= $requested_days ?>
                                        <?php elseif (!empty($req['request_amount'])): ?>
                                            <strong>Requested Amount:</strong> ৳ <?= format_bdt($req['request_amount']) ?>
                                        <?php endif; ?>
                                    </div>

                                    <?php renderApprovalContext($req); ?>


                                    <div class="d-flex gap-2 flex-wrap">
                                        <!-- Full Approve -->
                                        <form method="POST" action="<?= $GLOBALS['base_url'] ?>/approve-request/<?= $req['id'] ?>" class="d-inline">
                                            <button class="btn btn-sm btn-success">Full Approve</button>
                                        </form>

                                        <!-- Partial Approve (Modal) -->
                                        <?php if ($req['request_type'] !== 'manpower_requisition'): ?>
                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#partialApproveModal<?= $req['id'] ?>">Partial Approve</button>
                                        <?php endif; ?>

                                        <!-- Reject -->
                                        <form method="POST" action="<?= $GLOBALS['base_url'] ?>/reject-request/<?= $req['id'] ?>" class="d-inline">
                                            <button class="btn btn-sm btn-danger">Reject</button>
                                        </form>

                                        <!-- View Button -->
                                        <?php if ($req['request_type'] === 'manpower_requisition' && !empty($req['source_id'])): ?>
                                            <a href="<?= $GLOBALS['base_url'] ?>/view-requisition/<?= $req['source_id'] ?>" class="btn btn-sm btn-outline-info">
                                                <i class="fa-light fa-eye me-1"></i> View Requisition
                                            </a>
                                        <?php endif; ?>


                                    </div>

                                </div>

                                <!-- Partial Modal -->
                                <div class="modal fade" id="partialApproveModal<?= $req['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h6 class="modal-title">Partial Approval</h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST" action="<?= $GLOBALS['base_url'] ?>/partial-approve-request/<?= $req['id'] ?>">
                                                    <?php if ($req['source_table'] === 'employee_leaves'):
                                                        $meta = json_decode($req['meta_data'], true);
                                                        $requested_days = $meta['requested_days'] ?? 0;
                                                    ?>
                                                        <div class="mb-3">
                                                            <label class="form-label">Requested Days</label>
                                                            <input type="number" class="form-control" value="<?= $requested_days ?>" disabled>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Approve Days</label>
                                                            <input type="number" class="form-control" name="approved_days" placeholder="Enter approved days" required max="<?= $requested_days ?>">
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="mb-3">
                                                            <label class="form-label">Requested Amount</label>
                                                            <input type="text" class="form-control" value="৳ <?= number_format($req['request_amount'], 2) ?>" disabled>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Approve Amount</label>
                                                            <input type="number" class="form-control" name="approved_amount" placeholder="Enter approved amount" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Note</label>
                                                            <textarea class="form-control" name="approval_note" placeholder="Enter note here" rows="4"></textarea>
                                                        </div>

                                                    <?php endif; ?>

                                                    <div class="modal-footer">
                                                        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button class="btn btn-success">Approve</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- view modal  -->
                                <!-- View Context Modal -->
                                <div class="modal fade" id="viewContextModal<?= $req['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h6 class="modal-title">Request Details</h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <?php renderApprovalContext($req); ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No pending requests</p>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Bookings (All Bookings) -->
        <div class="<?= ((int)($_SESSION['user']['role_id'] ?? 0) === 33) ? 'col-12' : 'col-xl-6 col-lg-6 col-md-12 col-12' ?> mt-2">
            <div class="card__wrapper h-100 ">
                <div class="card-header pt-3 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                    <h6 class="mb-2 mb-md-0">All Bookings</h6>
                    <div class="d-flex gap-2 flex-wrap d-none">
                        <input type="text" class="form-control form-control-sm" placeholder="Search..." style="min-width: 150px;">
                        <select class="form-select form-select-sm" style="min-width: 120px;">
                            <option>All Status</option>
                            <option>Confirmed</option>
                            <option>Pending</option>
                            <option>Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="card__body p-0 ">
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Customer</th>
                                    <th>Project</th>
                                    <th>Flat/Unit</th>
                                    <th>Unit Value</th>
                                    <th>Booking Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th class="text-center d-none">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookingData as $item): ?>
                                    <tr>
                                        <td>#BK-<?= $item['id'] ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($item['lead_image'])): ?>
                                                    <img src="<?= getImage('leads', $item['lead_image']) ?>" alt="lead_image" class="pe-1 rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-primary bg-opacity-10 p-2 rounded me-2">
                                                        <i class="fa-light fa-user text-primary"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <span class="fw-semibold"><?= $item['full_name'] ?? '' ?></span>
                                                    <small class="text-muted d-block"><?= $item['phone'] ?? '' ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= $item['project_name'] ?? '' ?></td>
                                        <td>
                                            <strong class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded bg-primary text-light text-nowrap">
                                                <?= $item['flat_name'] ?>
                                                <span class="fw-normal"><?= $item['sqft'] ?? '' ?> Sqft</span>
                                            </strong>
                                        </td>



                                        <td class="fw-semibold">৳ <?= format_bdt($item['price_value']) ?></td>
                                        <td class="text-success fw-semibold">৳ <?= format_bdt(number: $item['agreement_value'] ?? '') ?></td>
                                        <td>
                                            <?php if ($item['is_booked'] === 1): ?>
                                                <span class="badge bg-success">Paid Booked</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Unpaid</span>
                                            <?php endif ?>
                                        </td>
                                        <td><?= formatDate($item['booking_date']) ?></td>
                                        <td class="text-center d-none">
                                            <a href="<?= $GLOBALS['base_url'] ?>/payments-booking/<?= $item['id'] ?>" class="btn btn-sm btn-success">Show Payment</a>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
        <?php
        // echo '<pre style="background:#111;color:#0f0;padding:15px;">';
        // print_r($pendingRequests);
        // echo '</pre>';
        ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>



<script>
    document.addEventListener('DOMContentLoaded', function() {
        const viewTypeSelector = document.getElementById('viewTypeSelector');
        const periodButtons = document.querySelectorAll('.btn__group .btn');

        // View type change handler
        if (viewTypeSelector) {
            viewTypeSelector.addEventListener('change', function() {
                const url = new URL(window.location.href);
                url.searchParams.set('view', this.value);
                window.location.href = url.toString();
            });
        }

        // Period button active state
        periodButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                const url = new URL(this.href);
                url.searchParams.set('view', '<?= $viewType ?>');
                this.href = url.toString();
            });
        });

        // Chart data from PHP
        const dynamicChartLabels = <?= json_encode($chartLabels) ?>;
        const dynamicChartData = <?= json_encode($chartData) ?>;
        const paymentMethodLabels = <?= json_encode($paymentMethodLabels) ?>;
        const paymentMethodData = <?= json_encode($paymentMethodData) ?>;

        // Currency formatter
        const formatCurrency = (value) => '৳' + value.toLocaleString();

        // Modern chart color palette
        const colors = {
            primary: '#6366f1',
            success: '#10b981',
            danger: '#ef4444',
            warning: '#f59e0b',
            info: '#06b6d4',
            purple: '#a855f7',
            pink: '#ec4899',
            doughnut: [
                '#8b5cf6',
                '#f02345ff',
                '#10b981',
                '#f59e0b',
                '#ef4444',
                '#ec4899'
            ]
        };

        /* ==========================
            WEEKLY CHART (Animated Area Chart)
        ========================== */
        const ctxWeek = document.getElementById('transactionChartWeek');
        if (ctxWeek) {
            const transactionChartWeek = new Chart(ctxWeek.getContext('2d'), {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                            label: 'Cash In',
                            data: <?= json_encode($weeklyCashInData) ?>,
                            borderColor: colors.success,
                            backgroundColor: colors.success + '15',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 0,
                            pointHoverRadius: 6,
                            pointHoverBackgroundColor: colors.success,
                            pointHoverBorderColor: '#fff',
                            pointHoverBorderWidth: 2
                        },
                        {
                            label: 'Cash Out',
                            data: <?= json_encode($weeklyCashOutData) ?>,
                            borderColor: colors.danger,
                            backgroundColor: colors.danger + '15',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 0,
                            pointHoverRadius: 6,
                            pointHoverBackgroundColor: colors.danger,
                            pointHoverBorderColor: '#fff',
                            pointHoverBorderWidth: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    animation: {
                        duration: 2000,
                        easing: 'easeOutQuart'
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    size: 13,
                                    weight: '500'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + formatCurrency(context.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            border: {
                                display: false
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawTicks: false
                            },
                            ticks: {
                                padding: 10,
                                callback: function(value) {
                                    return formatCurrency(value);
                                }
                            }
                        },
                        x: {
                            border: {
                                display: false
                            },
                            grid: {
                                display: false
                            },
                            ticks: {
                                padding: 10
                            }
                        }
                    }
                }
            });
        }

        /* ==========================
            MONTHLY CHART (Animated Bar Chart)
        ========================== */
        const ctxMonth = document.getElementById('transactionChartMonthly');
        if (ctxMonth) {
            const monthlyLabels = dynamicChartLabels.length ? dynamicChartLabels : ['Jan', 'Feb', 'Mar', 'Apr'];
            const monthlyData = dynamicChartData.length ? dynamicChartData : [120000, 155000, 142000, 168000];
            const monthlyColor = '<?= $viewType === "cashin" ? "#6366f1" : "#ef4444" ?>';

            const transactionChartMonthly = new Chart(ctxMonth.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: monthlyLabels,
                    datasets: [{
                        label: '<?= ucfirst($viewType) ?>',
                        data: monthlyData,
                        backgroundColor: monthlyColor,
                        borderRadius: 8,
                        borderSkipped: false,
                        barThickness: 35,
                        maxBarThickness: 40
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animations: {
                        y: {
                            duration: 1500,
                            from: 0,
                            easing: 'easeOutBounce'
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    return formatCurrency(context.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            border: {
                                display: false
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawTicks: false
                            },
                            ticks: {
                                padding: 10,
                                callback: function(value) {
                                    return formatCurrency(value);
                                }
                            }
                        },
                        x: {
                            border: {
                                display: false
                            },
                            grid: {
                                display: false
                            },
                            ticks: {
                                padding: 10
                            }
                        }
                    }
                }
            });
        }

        /* ==========================
            YEARLY CHART (Animated Line Chart)
        ========================== */
        const ctxYear = document.getElementById('transactionChartYearly');
        if (ctxYear) {
            const yearlyLabels = dynamicChartLabels.length ? dynamicChartLabels : ['2023', '2024', '2025'];
            const yearlyData = dynamicChartData.length ? dynamicChartData : [1250000, 1420000, 1380000];
            const yearlyColor = '<?= $viewType === "cashin" ? "#6366f1" : "#ef4444" ?>';

            const transactionChartYearly = new Chart(ctxYear.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: yearlyLabels,
                    datasets: [{
                        label: '<?= ucfirst($viewType) ?>',
                        data: yearlyData,
                        backgroundColor: yearlyColor,
                        borderRadius: 8,
                        borderSkipped: false,
                        barThickness: 35,
                        maxBarThickness: 40
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animations: {
                        y: {
                            duration: 1500,
                            from: 0,
                            easing: 'easeOutBounce'
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    return formatCurrency(context.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            border: {
                                display: false
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawTicks: false
                            },
                            ticks: {
                                padding: 10,
                                callback: function(value) {
                                    return formatCurrency(value);
                                }
                            }
                        },
                        x: {
                            border: {
                                display: false
                            },
                            grid: {
                                display: false
                            },
                            ticks: {
                                padding: 10
                            }
                        }
                    }
                }
            });
        }

        /* ==========================
            PAYMENT METHODS CHART (Animated Pie Chart)
        ========================== */
        const ctxPayment = document.getElementById('paymentMethodsChart');
        if (ctxPayment) {
            const paymentMethodsChart = new Chart(ctxPayment.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: paymentMethodLabels,
                    datasets: [{
                        data: paymentMethodData,
                        backgroundColor: colors.doughnut,
                        hoverBorderWidth: 5,
                        hoverOffset: 15
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animations: {
                        animateRotate: true,
                        animateScale: true,
                        duration: 2000,
                        easing: 'easeOutQuart'
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 15,
                                font: {
                                    size: 12
                                },
                                color: '#6b7280'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return label + ': ' + formatCurrency(value) + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }

        // ==========================================
        // AI Assistant JavaScript Handlers
        // ==========================================
        const aiInput = document.getElementById('ai-input');
        const aiSendBtn = document.getElementById('ai-send-btn');
        const aiDefaultView = document.getElementById('ai-default-view');
        const aiChatView = document.getElementById('ai-chat-view');
        const aiMessagesList = document.getElementById('ai-messages-list');
        const aiBackBtn = document.getElementById('ai-back-btn');
        const aiClearBtn = document.getElementById('ai-clear-btn');
        const insightItems = document.querySelectorAll('.ai-insight-item');
        const baseUrl = '<?= $GLOBALS['base_url'] ?>';

        function addMessage(sender, text, isHtml = false) {
            const msgDiv = document.createElement('div');
            msgDiv.className = 'd-flex flex-column mb-2';
            
            const contentSpan = document.createElement('div');
            contentSpan.style.maxWidth = '85%';
            contentSpan.style.padding = '8px 12px';
            contentSpan.style.borderRadius = '12px';
            contentSpan.style.lineHeight = '1.4';
            contentSpan.style.wordBreak = 'break-word';

            if (sender === 'user') {
                msgDiv.className += ' align-items-end';
                contentSpan.style.background = 'var(--clr-theme-primary)';
                contentSpan.style.color = '#fff';
                contentSpan.style.borderRadius = '12px 12px 0 12px';
                contentSpan.innerText = text;
            } else {
                msgDiv.className += ' align-items-start';
                contentSpan.style.borderRadius = '12px 12px 12px 0';
                contentSpan.style.border = '1px solid rgba(var(--clr-theme-primary-rgb), 0.2)';
                if (isHtml) {
                    contentSpan.innerHTML = text;
                } else {
                    contentSpan.innerText = text;
                }
            }

            msgDiv.appendChild(contentSpan);
            aiMessagesList.appendChild(msgDiv);
            aiMessagesList.scrollTop = aiMessagesList.scrollHeight;
        }

        function sendMessage(text) {
            if (!text || text.trim() === '') return;
            
            // Switch view
            aiDefaultView.classList.add('d-none');
            aiChatView.classList.remove('d-none');

            // Add user message
            addMessage('user', text);
            aiInput.value = '';

            // Add loading indicator
            const loadingDiv = document.createElement('div');
            loadingDiv.id = 'ai-loading-indicator';
            loadingDiv.className = 'd-flex align-items-start mb-2';
            loadingDiv.innerHTML = `
                <div style="max-width: 85%; padding: 8px 12px; border-radius: 12px 12px 12px 0; border: 1px solid rgba(var(--clr-theme-primary-rgb), 0.1);">
                    <i class="fa-light fa-spinner fa-spin me-2"></i> এআই প্রসেস করছে...
                </div>
            `;
            aiMessagesList.appendChild(loadingDiv);
            aiMessagesList.scrollTop = aiMessagesList.scrollHeight;

            // Fetch query from ChatController
            fetch(baseUrl + '/ai-chatbox', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ message: text })
            })
            .then(res => res.json())
            .then(data => {
                const loader = document.getElementById('ai-loading-indicator');
                if (loader) loader.remove();

                const reply = data.reply || 'দুঃখিত, কোনো উত্তর পাওয়া যায়নি।';
                const formattedReply = reply.replace(/\n/g, '<br>');
                addMessage('ai', formattedReply, true);
            })
            .catch(err => {
                const loader = document.getElementById('ai-loading-indicator');
                if (loader) loader.remove();
                addMessage('ai', 'দুঃখিত, সার্ভারের সাথে সংযোগ করা সম্ভব হয়নি।');
                console.error(err);
            });
        }

        if (aiSendBtn && aiInput) {
            aiSendBtn.addEventListener('click', () => sendMessage(aiInput.value));
            aiInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') sendMessage(aiInput.value);
            });
        }

        if (aiBackBtn) {
            aiBackBtn.addEventListener('click', () => {
                aiChatView.classList.add('d-none');
                aiDefaultView.classList.remove('d-none');
            });
        }

        if (aiClearBtn) {
            aiClearBtn.addEventListener('click', () => {
                aiMessagesList.innerHTML = '';
                addMessage('ai', 'আসসালামু আলাইকুম! 👋 আমি আপনার AI Assistant। আপনার বিজনেস এবং প্রজেক্টের যেকোনো প্রশ্ন আমাকে জিজ্ঞাসা করতে পারেন।');
                // Clear session history on backend
                fetch(baseUrl + '/ai-chatbox', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ message: 'clear_session_history_request' })
                }).catch(err => console.error('Failed to clear backend session:', err));
            });
        }

        insightItems.forEach(item => {
            item.addEventListener('click', () => {
                const ques = item.getAttribute('data-question');
                if (ques) sendMessage(ques);
            });
        });

        // Initialize greeting message
        addMessage('ai', 'আসসালামু আলাইকুম! 👋 আমি আপনার AI Assistant। আপনার বিজনেস এবং প্রজেক্টের যেকোনো প্রশ্ন আমাকে জিজ্ঞাসা করতে পারেন।');
    });
</script>

<style>
    /* ===============================
   Equal Height Dashboard Cards
   =============================== */

    /* Row flex setup */
    .equal-height-row {
        display: flex;
        flex-wrap: wrap;
        align-items: stretch;
    }

    /* Column full height */
    .equal-height-row>[class*="col-"] {
        display: flex;
    }

    /* Card wrapper */
    .card__wrapper.height-equal {
        width: 100%;
        display: flex;
        flex-direction: column;
        min-height: 100%;
    }

    /* Card body/content */
    .card__content {
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
    }

    /* Chart containers */
    .card__line-chart,
    .chart-container {
        position: relative;
        flex: 1 1 auto;
        min-height: 260px;
        /* safe minimum height */
    }

    /* Chart canvas full size */
    canvas {
        width: 100% !important;
        height: 100% !important;
    }

    /* Optional: consistent card minimum height */
    .card__wrapper.height-equal {
        min-height: 420px;
    }

    /* Mobile responsiveness */
    @media (max-width: 991px) {
        .equal-height-row {
            display: block;
        }
/* 
        .card__wrapper.height-equal {
            min-height: auto;
        } */
    }

    @media (max-width: 767px) {
        .app__slide-wrapper {
            min-height: 5px !important;
        }

        .btn__group {
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }

        .btn__group::-webkit-scrollbar {
            display: none;
        }
    }

    /* AI Assistant Hover effect */
    .hover-bg-light:hover {
        background-color: rgba(var(--clr-theme-primary-rgb), 0.05) !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
    }

    /* ==========================================
       AI Assistant Responsive Styles
       ========================================== */
    #ai-default-view,
    #ai-chat-view {
        flex-direction: column !important; /* Stack vertically on all screens */
    }

    #ai-default-view > div:first-child,
    #ai-default-view > div:last-child {
        width: 100%;
        min-width: 0;
    }

    #ai-default-view > div:first-child {
        margin-bottom: 8px !important;
    }

    /* Force column layout and prevent overflow on all screens for specific AI assistant containers */
    #ai-default-view,
    #ai-default-view .flex-column,
    #ai-chat-view,
    #ai-chat-view .flex-column,
    #ai-messages-list {
        flex-direction: column !important;
    }

    /* ── Column & Footer Dynamic Highlight ── */
    .active-col-highlight {
        background-color: rgba(255, 243, 205, 0.6) !important;
        font-weight: 800 !important;
    }
    #projectBreakdownFooterRow .col-target {
        transition: all 0.3s ease;
    }
    #projectBreakdownFooterRow .footer-col-highlight {
        background-color: #f59e0b !important;
        color: #ffffff !important;
        font-size: 1.05rem !important;
        font-weight: 900 !important;
        box-shadow: 0 0 10px rgba(245, 158, 11, 0.8);
        border-radius: 4px;
        opacity: 1 !important;
    }
</style>

<!-- ══ PROJECT-WISE COLLECTION BREAKDOWN MODAL (HOME DASHBOARD) ══ -->
<div class="modal fade" id="projectCollectionModal" tabindex="-1" aria-labelledby="projectCollectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fs-6 fw-bold text-white mb-0" id="projectCollectionModalLabel">
                    <i class="fa-solid fa-building-user me-2 text-warning"></i>Project-Wise Collection & EMI Breakdown
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <!-- Search & Info Bar -->
                <div class="row align-items-center mb-3 g-2">
                    <div class="col-md-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" id="projectBreakdownSearch" class="form-control" placeholder="Search project name..." onkeyup="filterProjectBreakdownTable()">
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fs-12 fw-semibold">
                            <i class="fa-solid fa-layer-group me-1"></i> Total Projects: <?= count($projectCollectionBreakdown ?? []) ?>
                        </span>
                    </div>
                </div>

                <!-- Table Card -->
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="projectBreakdownTable">
                            <thead class="bg-white text-muted extra-small text-uppercase border-bottom">
                                <tr>
                                    <th class="ps-3" style="width: 40px;">#</th>
                                    <th>Project Name</th>
                                    <th class="text-center">Bookings</th>
                                    <th class="text-end col-target col-total">Total Collected</th>
                                    <th class="text-end col-target col-land_share">Land Share</th>
                                    <th class="text-end col-target col-dev_charge text-purple">Dev Charge</th>
                                    <th class="text-end col-target col-emi">Total EMI</th>
                                    <th class="text-end text-success col-target col-verified">Verified EMI</th>
                                    <th class="text-end text-warning col-target col-unverified">Unverified EMI</th>
                                    <th class="pe-3 text-center" style="width: 100px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $sumBookings = 0;
                                $sumTotal = 0;
                                $sumLand = 0;
                                $sumDevCharge = 0;
                                $sumEmi = 0;
                                $sumVerified = 0;
                                $sumUnverified = 0;
                                ?>
                                <?php if (empty($projectCollectionBreakdown)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center py-4 text-muted">
                                            <i class="fa-solid fa-building-slash fs-3 d-block mb-2"></i>
                                            No project collection data found.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($projectCollectionBreakdown as $idx => $pData): ?>
                                        <?php 
                                        $sumBookings += (int)$pData['total_bookings'];
                                        $sumTotal += (float)$pData['total_collected'];
                                        $sumLand += (float)$pData['land_share_collected'];
                                        $sumDevCharge += (float)($pData['dev_charge_collected'] ?? 0);
                                        $sumEmi += (float)$pData['total_emi_collected'];
                                        $sumVerified += (float)$pData['verified_emi_collected'];
                                        $sumUnverified += (float)$pData['unverified_emi_collected'];
                                        ?>
                                        <tr class="project-breakdown-row">
                                            <td class="ps-3 fw-bold text-muted extra-small"><?= $idx + 1 ?></td>
                                            <td class="fw-bold text-dark project-name-cell">
                                                <i class="fa-solid fa-building text-primary me-2"></i><?= htmlspecialchars($pData['project_name']) ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1"><?= number_format($pData['total_bookings']) ?></span>
                                            </td>
                                            <td class="text-end fw-bold text-primary col-target col-total">
                                                ৳<?= number_format($pData['total_collected'], 2) ?>
                                            </td>
                                            <td class="text-end fw-semibold text-info col-target col-land_share">
                                                ৳<?= number_format($pData['land_share_collected'], 2) ?>
                                            </td>
                                            <td class="text-end fw-semibold text-purple col-target col-dev_charge">
                                                ৳<?= number_format($pData['dev_charge_collected'] ?? 0, 2) ?>
                                            </td>
                                            <td class="text-end fw-semibold text-dark col-target col-emi">
                                                ৳<?= number_format($pData['total_emi_collected'], 2) ?>
                                            </td>
                                            <td class="text-end fw-bold text-success col-target col-verified">
                                                ৳<?= number_format($pData['verified_emi_collected'], 2) ?>
                                            </td>
                                            <td class="text-end fw-bold text-warning col-target col-unverified">
                                                ৳<?= number_format($pData['unverified_emi_collected'], 2) ?>
                                            </td>
                                            <td class="pe-3 text-center">
                                                <a href="<?= $GLOBALS['base_url'] ?>/clients-cash-in?project_id=<?= $pData['project_id'] ?>" class="btn btn-xs btn-outline-primary rounded-2 px-2" title="Go to Clients Cash-In page for this project">
                                                    <i class="fa-solid fa-filter me-1"></i>Filter
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class=" fw-bold" id="projectBreakdownFooterRow">
                                <tr>
                                    <td colspan="2" class="ps-3 text-uppercase">Total Company Collection Summary</td>
                                    <td class="text-center"><?= number_format($sumBookings) ?></td>
                                    <td class="text-end text-primary-light col-target col-total">৳<?= number_format($sumTotal, 2) ?></td>
                                    <td class="text-end text-info-light col-target col-land_share">৳<?= number_format($sumLand, 2) ?></td>
                                    <td class="text-end text-purple-light col-target col-dev_charge">৳<?= number_format($sumDevCharge, 2) ?></td>
                                    <td class="text-end text-white col-target col-emi">৳<?= number_format($sumEmi, 2) ?></td>
                                    <td class="text-end text-success-light col-target col-verified">৳<?= number_format($sumVerified, 2) ?></td>
                                    <td class="text-end text-warning-light col-target col-unverified">৳<?= number_format($sumUnverified, 2) ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function openProjectCollectionModal(highlightType = 'all') {
    const modalEl = document.getElementById('projectCollectionModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }

    // Reset highlights and column opacities
    document.querySelectorAll('#projectBreakdownTable .col-target').forEach(el => {
        el.classList.remove('active-col-highlight', 'footer-col-highlight');
    });

    const footerCells = document.querySelectorAll('#projectBreakdownFooterRow .col-target');

    if (highlightType && highlightType !== 'all') {
        // Dim unselected footer summary cells
        footerCells.forEach(el => {
            el.style.opacity = '0.4';
        });

        // Highlight table body cells of selected column
        document.querySelectorAll('#projectBreakdownTable tbody .col-' + highlightType).forEach(el => {
            el.classList.add('active-col-highlight');
        });

        // Highlight header cell
        document.querySelectorAll('#projectBreakdownTable thead .col-' + highlightType).forEach(el => {
            el.classList.add('active-col-highlight');
        });

        // Highlight selected footer summary cell prominently
        const footerTarget = document.querySelector('#projectBreakdownFooterRow .col-' + highlightType);
        if (footerTarget) {
            footerTarget.classList.add('footer-col-highlight');
            footerTarget.style.opacity = '1';
        }
    } else {
        // Reset all footer cells to full opacity
        footerCells.forEach(el => {
            el.style.opacity = '1';
        });
    }
}

function filterProjectBreakdownTable() {
    const input = document.getElementById('projectBreakdownSearch');
    const filter = input ? input.value.toLowerCase() : '';
    const rows = document.querySelectorAll('.project-breakdown-row');

    rows.forEach(row => {
        const nameCell = row.querySelector('.project-name-cell');
        if (nameCell) {
            const text = nameCell.textContent || nameCell.innerText;
            if (text.toLowerCase().indexOf(filter) > -1) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });
}
</script>
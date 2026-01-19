<?php
$page_title = "Home Dashboard";
$show_breadcrumb = false;
?>
<div class="row  align-items-stretch">

    <div class="col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-12">
        <div class="card__wrapper h-100 d-flex flex-column justify-content-center">
            <div class="d-flex align-items-center gap-3">
                <div class="card__icon">
                    <span><i class="fa-sharp fa-regular fa-users"></i></span>
                </div>
                <div class="card__title-wrap flex-grow-1">
                    <h6 class="card__sub-title mb-2">Total Leads</h6>
                    <div class="d-flex align-items-end gap-2">
                        <h3 class="card__title"><?= $totalLeadsCount ?? '' ?></h3>
                        <span class="card__desc style_two">
                            <span class="<?= $leadsPercentChange >= 0 ? 'price-increase' : 'price-decrease' ?>">
                                <i class="fa-light <?= $leadsPercentChange >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' ?>"></i>
                                <?= abs($leadsPercentChange) ?>%
                            </span> Than Last Month
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-12">
        <div class="card__wrapper h-100 d-flex flex-column justify-content-center">
            <div class="d-flex align-items-center gap-3">
                <div class="card__icon">
                    <span><i class="fa-light fa-badge-check"></i></span>
                </div>
                <div class="card__title-wrap flex-grow-1">
                    <h6 class="card__sub-title mb-2">Total Lead Visited</h6>
                    <h3 class="card__title"><?= $totalVisitedCount ?? '' ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-12">
        <div class="card__wrapper h-100 d-flex flex-column justify-content-center">
            <div class="d-flex align-items-center gap-3">
                <div class="card__icon">
                    <span><i class="fa-sharp fa-regular fa-gear"></i></span>
                </div>
                <div class="card__title-wrap flex-grow-1">
                    <h6 class="card__sub-title mb-2">Total Projects</h6>
                    <h3 class="card__title"><?= $totalProjects ?? '' ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-12">
        <div class="card__wrapper h-100 d-flex flex-column justify-content-center">
            <div class="d-flex align-items-center gap-3">
                <div class="card__icon">
                    <span><i class="fa-light fa-badge-check"></i></span>
                </div>
                <div class="card__title-wrap flex-grow-1">
                    <h6 class="card__sub-title mb-2">Total Flat Booking</h6>
                    <h3 class="card__title"><?= $totalFlatsBooking ?? '' ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Example for Revenue Card -->
    <div class="col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-12">
        <div class="card__wrapper h-100 d-flex flex-column justify-content-center dashboard-card">
            <div class="d-flex align-items-center gap-3">
                <div class="card__icon icon-indigo">
                    <span><i class="fa-light fa-file-certificate"></i></span>
                </div>
                <div class="card__title-wrap flex-grow-1 overflow-hidden">
                    <h6 class="card__sub-title mb-1 text-muted">Total Revenue</h6>
                    <h4 class="card__title mb-0 text-break">৳<?= format_bdt($statistics['lead_payments']) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-12">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-10">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-success">
                        <span><i class="fa-light fa-arrow-down"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-2 text-muted">Total Cash In</h6>
                        <h4 class="card__title mb-0 text-primary text-break">৳<?= format_bdt($overviewStats['total_cashin']) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-12">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-10">
                <div class="d-flex align-items-center gap-2 h-100">
                    <div class="card__icon icon-danger">
                        <span><i class="fa-light fa-arrow-up"></i></span>
                    </div>
                    <div class="card__title-wrap flex-grow-1 overflow-hidden">
                        <h6 class="card__sub-title mb-2 text-muted">Total Cash Out</h6>
                        <h4 class="card__title mb-0 text-danger text-break">৳<?= format_bdt($overviewStats['total_cashout']) ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-12">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-10">
                <div class="card__icon icon-success">
                    <span><i class="fa-light fa-wallet"></i></span>
                </div>
                <div class="card__title-wrap flex-grow-1 overflow-hidden">
                    <h6 class="card__sub-title mb-2 text-muted">Net Balance</h6>
                    <h4 class="card__title mb-0 text-break <?= $overviewStats['net_balance'] >= 0 ? 'text-primary' : 'text-danger' ?>">
                        ৳<?= format_bdt($overviewStats['net_balance']) ?>
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <div class="d-none col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-12">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-10">
                <div class="card__icon">
                    <span><i class="fa-light fa-badge-check"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Complete Tasks</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h3 class="card__title"><?= $completedTasks ?? '' ?></h3>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-12 mb-3">
        <div class="card__wrapper h-100 dashboard-card">
            <div class="d-flex align-items-center gap-2 h-100">
                <div class="card__icon icon-purple">
                    <span><i class="fa-light fa-building"></i></span>
                </div>
                <div class="card__title-wrap flex-grow-1 overflow-hidden">
                    <h6 class="card__sub-title mb-2 text-muted">Management Cash In</h6>
                    <h4 class="card__title mb-0 text-break">৳<?= format_bdt($statistics['management_cashin']) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-12 mb-3">
        <div class="card__wrapper h-100 dashboard-card">
            <div class="d-flex align-items-center gap-2 h-100">
                <div class="card__icon icon-teal">
                    <span><i class="fa-light fa-hand-holding-usd"></i></span>
                </div>
                <div class="card__title-wrap flex-grow-1 overflow-hidden">
                    <h6 class="card__sub-title mb-2 text-muted">Booking Money</h6>
                    <h4 class="card__title mb-0 text-break">৳<?= format_bdt($statistics['booking_money']) ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-12 mb-3">
        <div class="card__wrapper h-100 dashboard-card">
            <div class="d-flex align-items-center gap-2 h-100">
                <div class="card__icon icon-primary">
                    <span><i class="fa-light fa-money-bill-wave"></i></span>
                </div>
                <div class="card__title-wrap flex-grow-1 overflow-hidden">
                    <h6 class="card__sub-title mb-2 text-muted">Total Resitration Fee</h6>
                    <h4 class="card__title mb-0 text-break">৳<?= format_bdt($statistics['registration_money']) ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-12 mb-3">
        <div class="card__wrapper h-100 dashboard-card">
            <div class="d-flex align-items-center gap-2 h-100">
                <div class="card__icon icon-primary">
                    <span><i class="fa-sharp fa-regular fa-user"></i></span>
                </div>
                <div class="card__title-wrap flex-grow-1 overflow-hidden">
                    <h6 class="card__sub-title mb-2 text-muted">Total Employees</h6>
                    <h4 class="card__title mb-0 text-break"><?= $totalEmployee ?? '' ?></h4>
                </div>
            </div>
        </div>
    </div>


    <!-- Repeat similar structure for remaining cards -->
</div>

<!-- script in footer  -->
<div class="row">
    <div class="col-xxl-6 col-xl-12 col-lg-12 col-12 mb-3 ">
        <div class="card__wrapper height-equal">
            <div class="card__title-wrap d-flex flex-wrap gap-10 align-items-center justify-content-between mb-25">
                <h5 class="card__heading-title">Booking Overview</h5>
                <div class="card__tab">
                    <ul class="nav nav-pills" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-bar-year-tab" data-period="year" type="button">1Y</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pills-bar-month-tab" data-period="month" type="button">1M</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link " id="pills-bar-week-tab" data-period="week" type="button">1W</button>
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

    <!-- Charts Section -->

    
<div class="col-xxl-6 col-xl-12 col-lg-12 mb-3">
    <div class="card__wrapper height-equal">
        <div class="card__title-wrap d-flex align-items-center justify-content-between mb-3">
            <h5 class="card__heading-title">Transaction Overview</h5>
        </div>
        <div class="card__content">
            <div class="chart-container">
                <canvas id="transactionChart"></canvas>
            </div>
        </div>
    </div>
</div>


    <!-- project wise  -->
    <div class="d-none col-xxl-6 col-xl-12 col-lg-12 col-12 mb-3">
        <div class="card-bg card-border mb-20 h-100">
            <div class="card__wrapper style_two h-100 d-flex flex-column">

                <!-- Card Header -->
                <div class="card__title-wrap d-flex align-items-center justify-content-between">
                    <h5 class="card__heading-title">Project wise Flat Sale</h5>
                    <div class="dropdown">
                        <button>
                            <i class="fa-regular fa-ellipsis-vertical"></i>
                        </button>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="card__content flex-grow-1 d-flex align-items-center">
                    <div id="pieChart" style="width:100%; height:260px;"></div>
                </div>

            </div>
        </div>
    </div>

    <!-- project wise flat sale  -->
   


</div>

<!-- Booking Overview Section -->
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-0"> Booking Overview</h4>
            <p class="text-muted small mb-0">Complete booking and payment summary</p>
        </div>
    </div>


    <!-- Payment Methods & Recent Bookings -->
    <div class="row g-3">

        <!-- Project-wise Bookings (Top Projects) -->
        <div class="d-none col-xl-6 col-lg-6 col-12">
            <div class="card h-100">
                <div class="card-header border-bottom-0 pt-3">
                    <h6 class="mb-0">Top Projects</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div>
                            <h6 class="mb-1">Green Valley Residence</h6>
                            <small class="text-muted">45 bookings</small>
                        </div>
                        <span class="badge bg-primary rounded-pill">28.8%</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div>
                            <h6 class="mb-1">Skyline Tower</h6>
                            <small class="text-muted">38 bookings</small>
                        </div>
                        <span class="badge bg-success rounded-pill">24.4%</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div>
                            <h6 class="mb-1">Pearl Heights</h6>
                            <small class="text-muted">32 bookings</small>
                        </div>
                        <span class="badge bg-info rounded-pill">20.5%</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Others</h6>
                            <small class="text-muted">41 bookings</small>
                        </div>
                        <span class="badge bg-secondary rounded-pill">26.3%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings (All Bookings) -->
        <div class="col-xl-12 col-lg-12 col-12">
            <div class="card__wrapper h-100">
                <div class="card__heading-title  pt-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">All Bookings</h6>
                    <div class="d-flex gap-2">
                        <input type="text" class="form-control form-control-sm" placeholder="Search..." style="width: 200px;">
                        <select class="form-select form-select-sm" style="width: 150px;">
                            <option>All Status</option>
                            <option>Confirmed</option>
                            <option>Pending</option>
                            <option>Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="card__body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Customer</th>
                                    <th>Project</th>
                                    <th>Flat</th>
                                    <th>Agreement Value</th>
                                    <th>Paid Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>#BK-00156</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary bg-opacity-10 p-2 rounded me-2">
                                                <i class="fa-light fa-user text-primary"></i>
                                            </div>
                                            <div>
                                                <span class="fw-semibold">Karim Rahman</span>
                                                <small class="text-muted d-block">+880 1712-345678</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>Green Valley</td>
                                    <td><span class="badge bg-light text-dark">A-501</span></td>
                                    <td class="fw-semibold">৳ 45,00,000</td>
                                    <td class="text-success fw-semibold">৳ 15,00,000</td>
                                    <td><span class="badge bg-success">Confirmed</span></td>
                                    <td>Jan 18, 2026</td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-light" title="View Details">
                                            <i class="fa-light fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>#BK-00155</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary bg-opacity-10 p-2 rounded me-2">
                                                <i class="fa-light fa-user text-primary"></i>
                                            </div>
                                            <div>
                                                <span class="fw-semibold">Fatima Akter</span>
                                                <small class="text-muted d-block">+880 1812-345678</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>Skyline Tower</td>
                                    <td><span class="badge bg-light text-dark">B-302</span></td>
                                    <td class="fw-semibold">৳ 38,50,000</td>
                                    <td class="text-warning fw-semibold">৳ 5,00,000</td>
                                    <td><span class="badge bg-warning">Pending</span></td>
                                    <td>Jan 18, 2026</td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-light" title="View Details">
                                            <i class="fa-light fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>#BK-00154</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary bg-opacity-10 p-2 rounded me-2">
                                                <i class="fa-light fa-user text-primary"></i>
                                            </div>
                                            <div>
                                                <span class="fw-semibold">Ali Khan</span>
                                                <small class="text-muted d-block">+880 1912-345678</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>Pearl Heights</td>
                                    <td><span class="badge bg-light text-dark">C-101</span></td>
                                    <td class="fw-semibold">৳ 32,00,000</td>
                                    <td class="text-success fw-semibold">৳ 12,00,000</td>
                                    <td><span class="badge bg-success">Confirmed</span></td>
                                    <td>Jan 17, 2026</td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-light" title="View Details">
                                            <i class="fa-light fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>



</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> -->


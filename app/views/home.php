<?php
$page_title = "Home Dashboard";
$show_breadcrumb = false;
?>
<div class="row mb-2">
    <div class="btn__group mb-3">
        <a href="?period=daily" class="btn btn-sm <?= ($period ?? '') == 'daily' ? 'btn-primary' : 'btn-outline-secondary' ?>">Daily</a>
        <a href="?period=weekly" class="btn btn-sm <?= ($period ?? '') == 'weekly' ? 'btn-primary' : 'btn-outline-secondary' ?>">Weekly</a>
        <a href="?period=monthly" class="btn btn-sm <?= ($period ?? '') == 'monthly' ? 'btn-primary' : 'btn-outline-secondary' ?>">Monthly</a>
        <a href="?period=yearly" class="btn btn-sm <?= ($period ?? '') == 'yearly' ? 'btn-primary' : 'btn-outline-secondary' ?>">Yearly</a>
    </div>

</div>
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
                            </span>
                            Than <?= $period ? ucfirst($period) : 'Last Month' ?>
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
                    <h4 class="card__title mb-0 text-break"> <?= $totalEmployees ?> <?= $period ? "({$period})" : "" ?></h4>
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

    <!--Accounts Charts Section -->


    <div class="col-xxl-6 col-xl-12 col-lg-12 mb-3">
        <div class="card__wrapper height-equal">
            <div class="card__title-wrap d-flex flex-wrap gap-10 align-items-center justify-content-between mb-50">
                <h5 class="card__heading-title">Accounts Overview</h5>
                <div class="d-none d-flex align-items-center gap-2">
                    <label class="mb-0 fw-bold">View:</label>
                    <select id="viewTypeSelector" class="form-select" style="width: auto; min-width: 150px;">
                        <option value="cashin" <?= $viewType === 'cashin' ? 'selected' : '' ?>>Cash In</option>
                        <option value="cashout" <?= $viewType === 'cashout' ? 'selected' : '' ?>>Cash Out</option>
                    </select>
                </div>
                <div class="card__tab">
                    <ul class="nav nav-pills" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-bar-year-tab" data-bs-toggle="pill"
                                data-bs-target="#pills-bar-year" type="button" role="tab"
                                aria-controls="pills-bar-year" aria-selected="true">1Y</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-bar-month-tab" data-bs-toggle="pill"
                                data-bs-target="#pills-bar-month" type="button" role="tab"
                                aria-controls="pills-bar-month" aria-selected="false">1M</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pills-bar-week-tab"
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
                            <canvas id="transactionChartYearly" class="card__title"></canvas>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="pills-bar-month" role="tabpanel"
                        aria-labelledby="pills-bar-month-tab">
                        <div class="chart-container">
                            <canvas id="transactionChartMonthly" class="card__title"></canvas>
                        </div>
                    </div>
                    <div class="tab-pane fade show active" id="pills-bar-week" role="tabpanel"
                        aria-labelledby="pills-bar-week-tab">
                        <div class="chart-container">
                            <canvas id="transactionChartWeek" class="card__title"></canvas>
                        </div>
                    </div>
                </div>
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


            <!-- Recent Bookings (All Bookings) -->
            <div class="col-xl-6 col-lg-6 col-md-12 col-12 mt-2">
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
                                                        <span class="fw-semibold"><?= $item['lead_name'] ?? '' ?></span>
                                                        <small class="text-muted d-block"><?= $item['phone'] ?? '' ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= $item['project_name'] ?? '' ?></td>
                                            <td>
                                                <strong class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded bg-primary text-dark text-nowrap">
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
                                            <span class="badge bg-warning text-dark"><?= ucfirst($req['status']) ?></span>
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

                                        <div class="d-flex gap-2 flex-wrap">
                                            <!-- Full Approve -->
                                            <form method="POST" action="<?= $GLOBALS['base_url'] ?>/approve-request/<?= $req['id'] ?>" class="d-inline">
                                                <button class="btn btn-sm btn-success">Full Approve</button>
                                            </form>

                                            <!-- Partial Approve (Modal) -->
                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#partialApproveModal<?= $req['id'] ?>">Partial Approve</button>

                                            <!-- Reject -->
                                            <form method="POST" action="<?= $GLOBALS['base_url'] ?>/reject-request/<?= $req['id'] ?>" class="d-inline">
                                                <button class="btn btn-sm btn-danger">Reject</button>
                                            </form>
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

                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">No pending requests</p>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>





    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> -->

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const viewTypeSelector = document.getElementById('viewTypeSelector');

        viewTypeSelector.addEventListener('change', function() {
            window.location.href = '<?= $GLOBALS['base_url'] ?>/dashboard?view=' + this.value;
        });

        const dynamicChartLabels = <?= json_encode($chartLabels ?? []) ?>;
        const dynamicChartData = <?= json_encode($chartData ?? []) ?>;

        // Currency formatter
        const formatCurrency = (value) => '৳' + value.toLocaleString();

        // Modern chart color palette with NEW doughnut colors
        const colors = {
            primary: '#6366f1',
            success: '#10b981',
            danger: '#ef4444',
            warning: '#f59e0b',
            info: '#06b6d4',
            purple: '#a855f7',
            pink: '#ec4899',
            // New doughnut chart colors (modern gradient palette)
            doughnut: [
                '#8b5cf6', // Vibrant Purple
                '#f02345ff', // Bright Blue
                '#10b981', // Emerald Green
                '#f59e0b', // Amber Orange
                '#ef4444', // Red
                '#ec4899' // Pink
            ]
        };

        /* ==========================
            WEEKLY CHART (Animated Area Chart)
        ========================== */
        const ctxWeek = document.getElementById('transactionChartWeek').getContext('2d');
        const transactionChartWeek = new Chart(ctxWeek, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                        label: 'Cash In',
                        data: [31000, 40000, 28000, 51000, 42000, 82000, 56000],
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
                        data: [11000, 32000, 45000, 32000, 34000, 52000, 41000],
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
                // ADD ANIMATION HERE
                animation: {
                    duration: 2000,
                    easing: 'easeOutQuart',
                    onComplete: function() {
                        // Animation complete
                    }
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

        /* ==========================
            MONTHLY CHART (Animated Bar Chart)
        ========================== */
        const ctxMonth = document.getElementById('transactionChartMonthly').getContext('2d');
        const monthlyLabels = dynamicChartLabels.length ? dynamicChartLabels : ['Jan', 'Feb', 'Mar', 'Apr'];
        const monthlyData = dynamicChartData.length ? dynamicChartData : [120000, 155000, 142000, 168000];
        const monthlyColor = '<?= $viewType === "cashin" ? "#6366f1" : "#ef4444" ?>';

        const transactionChartMonthly = new Chart(ctxMonth, {
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
                // ADD ANIMATION HERE
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

        /* ==========================
            YEARLY CHART (Animated Line Chart)
        ========================== */
        const ctxYear = document.getElementById('transactionChartYearly').getContext('2d');
        const yearlyLabels = dynamicChartLabels.length ? dynamicChartLabels : ['2023', '2024', '2025'];
        const yearlyData = dynamicChartData.length ? dynamicChartData : [1250000, 1420000, 1380000];
        const yearlyColor = '<?= $viewType === "cashin" ? "#6366f1" : "#ef4444" ?>';

        const transactionChartYearly = new Chart(ctxYear, {
            type: 'line',
            data: {
                labels: yearlyLabels,
                datasets: [{
                    label: '<?= ucfirst($viewType) ?>',
                    data: yearlyData,
                    borderColor: yearlyColor,
                    backgroundColor: yearlyColor + '15',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 6,
                    pointBackgroundColor: yearlyColor,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 3,
                    pointHoverRadius: 8,
                    pointHoverBackgroundColor: yearlyColor,
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                // ADD ANIMATION HERE
                animation: {
                    duration: 2000,
                    easing: 'easeOutQuart',
                    onComplete: function() {
                        // Animation complete
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
        })
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

        .card__wrapper.height-equal {
            min-height: auto;
        }
    }
</style>
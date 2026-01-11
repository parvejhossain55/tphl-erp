<?php
// session_start();
// echo "<pre>";
// print_r($_SESSION['user']);
// echo "</pre>";
?>

<?php
$page_title = "Home Dashboard";
$show_breadcrumb = false;
?>
<div class="row g-20 align-items-stretch">
    <div class="col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-12">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-10">
                <div class="card__icon">
                    <span><i class="fa-sharp fa-regular fa-user"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Employees</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h3 class="card__title"><?= $totalEmployee ?? '' ?></h3>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-12">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-10">
                <div class="card__icon">
                    <span><i class="fa-sharp fa-regular fa-users"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Clients</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
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
    <div class="col-xxl-3  col-xl-4 col-lg-4 col-md-6 col-12">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-10">
                <div class="card__icon">
                    <span><i class="fa-sharp fa-regular fa-gear"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Projects</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h3 class="card__title"><?= $totalProjects ?? '' ?></h3>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-12">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-10">
                <div class="card__icon">
                    <span><i class="fa-light fa-badge-check"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Flat Booking</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h3 class="card__title"><?= $totalFlatsBooking ?? '' ?></h3>

                    </div>
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
                        <h4 class="card__title mb-0 text-success text-break">৳<?= format_bdt($overviewStats['total_cashin'])?></h4>
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
                        <h4 class="card__title mb-0 text-danger text-break">৳<?= format_bdt($overviewStats['total_cashout'])?></h4>
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
                    <h4 class="card__title mb-0 text-break <?= $overviewStats['net_balance'] >= 0 ? 'text-success' : 'text-danger' ?>">
                        ৳<?= format_bdt($overviewStats['net_balance'])?>
                    </h4>
                </div>
            </div>
        </div>
    </div>



    <div class="col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-12">
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
                <div class="card__icon icon-primary">
                    <span><i class="fa-light fa-money-bill-wave"></i></span>
                </div>
                <div class="card__title-wrap flex-grow-1 overflow-hidden">
                    <h6 class="card__sub-title mb-2 text-muted">Client Payments</h6>
                    <h4 class="card__title mb-0 text-break">৳<?= format_bdt($statistics['lead_payments'])?></h4>
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
                    <h4 class="card__title mb-0 text-break">৳<?= format_bdt($statistics['management_cashin'])?></h4>
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
                    <h4 class="card__title mb-0 text-break">৳<?= format_bdt($statistics['booking_money'])?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-12 mb-3">
        <div class="card__wrapper h-100 dashboard-card">
            <div class="d-flex align-items-center gap-2 h-100">
                <div class="card__icon icon-indigo">
                    <span><i class="fa-light fa-file-certificate"></i></span>
                </div>
                <div class="card__title-wrap flex-grow-1 overflow-hidden">
                    <h6 class="card__sub-title mb-2 text-muted">Registration Money</h6>
                    <h4 class="card__title mb-0 text-break">৳<?= format_bdt($statistics['registration_money'])?></h4>
                </div>
            </div>
        </div>
    </div>




    <div class="d-none col-xxl-3  col-lg-4 col-md-6 col-12">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-10">
                <div class="card__icon">
                    <span><i class="icon-tickets1"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Orders</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h3 class="card__title">55</h3>
                        <span class="card__desc style_two"><span class="price-increase"><i
                                    class="fa-light fa-arrow-up"></i> +2.15%</span> Than Last
                            Month</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- script in footer  -->
<div class="row">
    <div class="col-xxl-6 col-xl-12 col-lg-12 col-12 mb-3">
        <div class="card__wrapper height-equal">
            <div class="card__title-wrap d-flex flex-wrap gap-10 align-items-center justify-content-between mb-25">
                <h5 class="card__heading-title">Sales Overview</h5>
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

    <!-- project wise  -->
    <div class="col-xxl-6 col-xl-12 col-lg-12 col-12 mb-3">
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
    <!-- Charts Section -->
    <div class="d-none col-xxl-6 col-xl-6 col-lg-6">
        <div class="card__wrapper height-equal">
            <div
                class="card__title-wrap d-flex flex-wrap gap-10 align-items-center justify-content-between mb-25">
                <h5 class="card__heading-title">Transaction Overview</h5>
                <div class="card__tab">
                    <ul class="nav nav-pills" id="transaction-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="transaction-bar-year-tab" data-bs-toggle="pill"
                                data-bs-target="#transaction-bar-year" type="button" role="tab"
                                aria-controls="transaction-bar-year" aria-selected="true">1Y</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="transaction-bar-month-tab" data-bs-toggle="pill"
                                data-bs-target="#transaction-bar-month" type="button" role="tab"
                                aria-controls="transaction-bar-month" aria-selected="false">1M</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="transaction-bar-week-tab" data-bs-toggle="pill"
                                data-bs-target="#transaction-bar-week" type="button" role="tab"
                                aria-controls="transaction-bar-week" aria-selected="false">1W</button>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="tab-content" id="transaction-tabContent">
                <div class="tab-pane fade" id="transaction-bar-year" role="tabpanel" aria-labelledby="transaction-bar-year-tab">
                    <canvas id="transactionChartYearly" class="card__title"></canvas>
                </div>
                <div class="tab-pane fade" id="transaction-bar-month" role="tabpanel" aria-labelledby="transaction-bar-month-tab">
                    <canvas id="transactionChartMonthly" class="card__title"></canvas>
                </div>
                <div class="tab-pane fade show active" id="transaction-bar-week" role="tabpanel" aria-labelledby="transaction-bar-week-tab">
                    <canvas id="transactionChartWeek" class="card__title"></canvas>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="col-xxl-12 col-xl-12 col-lg-12 d-none">
    <div class="card__wrapper">
        <div class="card__title-wrap d-flex align-items-center justify-content-between mb-20">
            <h5 class="card__heading-title">Announcement</h5>
            <div class="card__dropdown">
                <div class="dropdown">
                    <button>
                        <i class="fa-regular fa-ellipsis-vertical"></i>
                    </button>
                    <div class="dropdown-list">
                        <a class="dropdown__item" href="javascript:void(0)">Action</a>
                        <a class="dropdown__item" href="javascript:void(0)">More Action</a>
                        <a class="dropdown__item" href="javascript:void(0)">Another Action</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="table__wrapper table-responsive">
            <table class="table mb-20 multiple_tables">
                <thead>
                    <tr class="table__title">
                        <th scope="col">Title</th>
                        <th scope="col">Start Date</th>
                        <th scope="col">End Date</th>
                        <th scope="col">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Annual Company Retreat</td>
                        <td>Jun 10, 2024</td>
                        <td>Jun 15, 2024</td>
                        <td>A week-long retreat for team building and strategy sessions.</td>
                    </tr>
                    <tr>
                        <td>Quarterly Business Review</td>
                        <td>Jul 01, 2024</td>
                        <td>Jul 02, 2024</td>
                        <td>Review of business performance for the past quarter.</td>
                    </tr>
                    <tr>
                        <td>Product Launch</td>
                        <td>Aug 15, 2024</td>
                        <td>Aug 15, 2024</td>
                        <td>Official launch event for the new product line.</td>
                    </tr>
                    <tr>
                        <td>Employee Training Program</td>
                        <td>Sep 05, 2024</td>
                        <td>Sep 10, 2024</td>
                        <td>Intensive training sessions for new employees.</td>
                    </tr>
                    <tr>
                        <td>End of Year Gala</td>
                        <td>Dec 20, 2024</td>
                        <td>Dec 20, 2024</td>
                        <td>Celebration event to close out the year.</td>
                    </tr>
                    <tr>
                        <td>End of Year Gala</td>
                        <td>Dec 20, 2024</td>
                        <td>Dec 20, 2024</td>
                        <td>Celebration event to close out the year.</td>
                    </tr>
                    <tr>
                        <td>Team Building Workshop</td>
                        <td>Oct 12, 2024</td>
                        <td>Oct 13, 2024</td>
                        <td>Workshop aimed at improving team collaboration and communication skills.
                        </td>
                    </tr>
                    <tr>
                        <td>Client Appreciation Event</td>
                        <td>Nov 05, 2024</td>
                        <td>Nov 05, 2024</td>
                        <td>Event to show appreciation for our valued clients.</td>
                    </tr>
                    <tr>
                        <td>Mid-Year Performance Review</td>
                        <td>Jul 15, 2024</td>
                        <td>Jul 16, 2024</td>
                        <td>Review of employee performance for the first half of the year.</td>
                    </tr>
                    <tr>
                        <td>Health and Wellness Fair</td>
                        <td>Sep 20, 2024</td>
                        <td>Sep 20, 2024</td>
                        <td>An event focused on promoting health and wellness among employees.</td>
                    </tr>
                    <tr>
                        <td>Technology Update Seminar</td>
                        <td>Aug 22, 2024</td>
                        <td>Aug 22, 2024</td>
                        <td>Seminar to discuss the latest updates and trends in technology.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        // Example data — তুমি চাইলে PHP থেকে pass করতে পারো
        const transactionYearLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        const transactionYearData = [1200, 1500, 1400, 1800, 2000, 1700];

        const transactionMonthLabels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
        const transactionMonthData = [300, 450, 400, 500];

        const transactionWeekLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        const transactionWeekData = [50, 70, 65, 80, 90, 60, 75];

        // Yearly Chart
        new Chart(document.getElementById('transactionChartYearly').getContext('2d'), {
            type: 'line',
            data: {
                labels: transactionYearLabels,
                datasets: [{
                    label: 'Transactions',
                    data: transactionYearData,
                    borderColor: '#ef4444',
                    backgroundColor: '#ef444415',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#ef4444'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Monthly Chart
        new Chart(document.getElementById('transactionChartMonthly').getContext('2d'), {
            type: 'line',
            data: {
                labels: transactionMonthLabels,
                datasets: [{
                    label: 'Transactions',
                    data: transactionMonthData,
                    borderColor: '#f59e0b',
                    backgroundColor: '#f59e0b15',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#f59e0b'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Weekly Chart
        new Chart(document.getElementById('transactionChartWeek').getContext('2d'), {
            type: 'line',
            data: {
                labels: transactionWeekLabels,
                datasets: [{
                    label: 'Transactions',
                    data: transactionWeekData,
                    borderColor: '#10b981',
                    backgroundColor: '#10b98115',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#10b981'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

    });
</script>
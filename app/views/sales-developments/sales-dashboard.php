
<div class="app__slide-wrapper">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 d-none">
        <div>
            <h4 class="fw-bold mb-1">Sales Dashboard</h4>
        </div>

    </div>

    <!-- sales team  wise performance cards   -->
    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-warning">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>
    <div>
        <h4 class="fw-bold mb-1">Sales Team Performance Card Search</h4>
    </div>

    <form method="GET" action="">
        <div class="row g-2 mb-3 align-items-end">

            <!-- Employee Select -->
            <div class="col-6 col-sm-6 col-md-3">
                <label for="executiveSelect" class="form-label">Select Sales Executive</label>



                <select id="executiveSelect" name="executive" class="form-select select2">
                    <option value="">-- Select Sales Executive --</option>
                    <?php foreach ($allSalesTeams as $team): ?>
                        <option value="<?= $team['id'] ?>" <?= ($executiveId == $team['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($team['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Month Select -->
            <div class="col-6 col-sm-6 col-md-3">
                <label for="periodSelect" class="form-label">Select Month</label>
                <select class="form-select" name="month" id="periodSelect">
                    <option value="">Month Select</option>
                    <?php foreach (range(1, 12) as $m):
                        $monthName = date('F', mktime(0, 0, 0, $m, 1));
                    ?>
                        <option value="<?= $m ?>" <?= ($month == $m) ? 'selected' : '' ?>>
                            <?= $monthName ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Year Select -->
            <div class="col-6 col-sm-6 col-md-2">
                <label for="yearSelect" class="form-label">Select Year</label>
                <select class="form-select" name="year" id="yearSelect">
                    <option value="">Year Select</option>
                    <?php for ($y = date('Y'); $y >= date('Y') - 10; $y--): ?>
                        <option value="<?= $y ?>" <?= ($year == $y) ? 'selected' : '' ?>>
                            <?= $y ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- Filter Button -->
            <div class="col-6 col-sm-6 col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    Filter
                </button>
            </div>

            <!-- Reset Button -->
            <div class="col-6 col-sm-6 col-md-2">
                <a href="<?= $GLOBALS['base_url'] ?>/sales-dashboard" class="btn btn-secondary w-100">
                    Reset
                </a>
            </div>

        </div>
    </form>

    <!-- Performance Cards -->
    <div class="row" id="performanceCards">
        <!-- Target tracking data -->
        <?php if ($showCards): ?>


            <!-- New Lead & Action tracking data -->
            <div class="col-6 col-md-4 mb-3 card-item">
                <div class="card__wrapper p-3 shadow-sm border-end border-primary border-4" style="border-right-width: 4px !important;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-primary fw-semibold">Total Leads Assigned</span>
                                <span class="fw-bold text-dark"><?= $singleData['total_leads_assigned'] ?? 0 ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-primary fw-semibold">Total Follow Up Yet</span>
                                <span class="fw-bold text-dark"><?= $singleData['total_follow_ups_yet'] ?? 0 ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-4 mb-3 card-item">
                <div class="card__wrapper p-3 shadow-sm border-end border-success border-4" style="border-right-width: 4px !important;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-primary fw-semibold">Today Total Leads Assigned</span>
                                <span class="fw-bold text-dark"><?= $singleData['today_leads_assigned'] ?? 0 ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-primary fw-semibold">Today Total Contacted</span>
                                <span class="fw-bold text-success"><?= $singleData['today_contacted'] ?? 0 ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-4 mb-3 card-item">
                <div class="card__wrapper p-3 shadow-sm border-end border-warning border-4" style="border-right-width: 4px !important;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-primary fw-semibold">Today Follow Up</span>
                                <span class="fw-bold text-dark"><?= $singleData['today_follow_ups'] ?? 0 ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-primary fw-semibold">Today Remaining Data</span>
                                <span class="fw-bold text-warning"><?= $singleData['today_remaining'] ?? 0 ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-4 mb-3 card-item">
                <div class="card__wrapper p-3 shadow-sm border-end border-primary border-4" style="border-right-width: 4px !important;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-primary fw-semibold">Site Visit Target</span>
                                <span class="fw-bold"><?= $singleData['site_visit_target'] ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-primary fw-semibold">Visit Completed</span>
                                <span class="fw-bold"><?= $singleData['visit_completed'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-4 mb-3 card-item">
                <div class="card__wrapper p-3 shadow-sm border-end border-primary border-4" style="border-right-width: 4px !important;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-primary fw-semibold">Booking Target</span>
                                <span class="fw-bold"><?= $singleData['booking_target'] ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-primary fw-semibold">Booking Target Complete</span>
                                <span class="fw-bold"><?= $singleData['booking_completed'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-4 mb-3 card-item">
                <div class="card__wrapper p-3 shadow-sm border-end border-primary border-4" style="border-right-width: 4px !important;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-primary fw-semibold">Booking Money Collected</span>
                                <span class="fw-bold"><?= $singleData['total_booking_money'] ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-primary fw-semibold">Total Flat Value</span>
                                <span class="fw-bold"><?= $singleData['total_flat_value'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>


    <!--END sales team  wise performance cards   -->


    <!-- KPI CARDS -->
    <div>
        <h4 class="fw-bold mb-3">Full Summery</h4>
    </div>
    <div class="row g-20 mb-4 align-items-stretch">

        <div class="col-xxl-3  col-md-4 col-6 mb-2">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-10">
                    <div class="card__icon">
                        <span><i class="fa-regular fa-users"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__title text-break text-primary mb-5">Total Leads</h6>
                        <h4 class="card__title"><?= $totalLeadsCount ?? '' ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3  col-md-4 col-6 mb-2">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-10">
                    <div class="card__icon">
                        <span><i class="fa-regular fa-user-plus"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__title text-break text-primary mb-10">New Leads Today</h6>
                        <h4 class="card__title"><?= $todayLeads ?? '' ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class=" d-none col-xxl-4 col-xl-4 col-md-4 col-6 mb-2">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-10">
                    <div class="card__icon">
                        <span><i class="fa-regular fa-bell"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__title text-break text-primary mb-10">Today Call Due For Add New Lead</h6>
                        <h4 class="card__title"><?= $followUpsDue ?? '' ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3  col-md-4 col-6 mb-2">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-10">
                    <div class="card__icon">
                        <span><i class="fa-regular fa-circle-check"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__title text-break text-primary mb-10">Today Follow Up</h6>
                        <h4 class="card__title"><?= $totalTodayFollowUps ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3  col-md-4 col-6 mb-2">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-10">
                    <div class="card__icon">
                        <span><i class="fa-regular fa-circle-check"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__title text-break text-primary mb-10">Total Sales Team Members</h6>
                        <h4 class="card__title"><?= $totalSalesTeams ?></h4>
                    </div>
                </div>
            </div>
        </div>


        <!-- REVENUE + CONVERSION -->
        <div class="col-xxl-3 col-xl-4 col-md-4 col-6 mb-2">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-10">
                    <div class="card__icon">
                        <span><i class="fa-regular fa-circle-check"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__title text-break text-primary mb-10">Total Flat Booking</h6>
                        <h4 class="card__title"><?= $onboardedClients ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- TOTAL PROJECT -->
        <div class="col-xxl-3 col-xl-4 col-md-4 col-6 mb-2">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-10">
                    <div class="card__icon">
                        <span><i class="fa-solid fa-building"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__title text-break text-primary mb-10">Total Project</h6>
                        <h4 class="card__title"><?= $totalProjects ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- BOOKING MONEY COLLECTED -->
        <div class="col-xxl-3  col-xl-4 col-md-4 col-6 mb-2">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-10">
                    <div class="card__icon">
                        <span><i class="fa-solid fa-bangladeshi-taka-sign"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__title text-break text-primary mb-10">Booking Money Collected</h6>
                        <h4 class="card__title">
                            <?= format_bdt($bookingCollected) ?>
                        </h4>
                    </div>
                </div>
            </div>
        </div>
        <!-- CONVERSION RATE -->
        <div class="col-xxl-3  col-xl-4 col-md-4 col-6 mb-2">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-10">
                    <div class="card__icon">
                        <span><i class="fa-solid fa-chart-line"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__title text-break text-primary mb-10">Conversion Rate</h6>
                        <h4 class="card__title">
                            <?= format_bdt($conversionRate) ?>%
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- sales overview  -->
    <div class="row g-20">
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
    </div>

    <!-- team performance  chart -->
    <div class="card__wrapper shadow-sm p-3">
        <div class="mb-2">
            <h5 class="text-center ">Sales Teams Booking Performance</h5>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-pills mb-3" id="bookingTabs">
            <li class="nav-item">
                <button class="nav-link" data-period="year1">Year</button>
            </li>
            <li class="nav-item">
                <button class="nav-link active" data-period="month1">Month</button>
            </li>
            <li class="nav-item d-none">
                <button class="nav-link" data-period="week1">Week</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-period="fullData">Full Data</button>
            </li>
        </ul>

        <!-- Chart -->
        <div id="monthlyPerformanceChart"></div>
    </div>


    <!-- Todays Follow up -->
    <div class="col-12 mt-3">
        <div class="card__wrapper">
            <div class="card__header d-flex justify-content-between align-items-center mb-3">
                <h5 class="card__title mb-0" id="transactionsTitle">
                    Today's Follow-ups
                </h5>

            </div>

            <div class="table__wrapper table-responsive">
                <table class="table mb-20">
                    <thead>
                        <tr class="table__title">
                            <th>S/L</th>
                            <th>Lead Name</th>
                            <th>Project</th>
                            <th>Flat</th>
                            <th>Follow-up Employee</th>
                            <th>Last Follow Up Date</th>
                            <th>Today Follow-up</th>
                            <th>Last Decussion Note</th>
                            <th>Today Follow Up Action</th>
                        </tr>
                    </thead>
                    <tbody id="">
                        <?php if (!empty($todayFollowUpData)): ?>
                            <?php foreach ($todayFollowUpData as $key => $followUp): ?>
                                <tr>
                                    <td><?= $key + 1 ?></td>
                                    <td><?= htmlspecialchars($followUp['full_name']) ?></td>
                                    <td><?= htmlspecialchars($followUp['project_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($followUp['flat_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($followUp['assigned_with_name'] ?? 'N/A') ?></td>
                                    <td><?= date('d M, Y H:i', strtotime($followUp['follow_up_date'] . ' ' . $followUp['follow_up_time'])) ?></td>
                                    <td><?= date('d M, Y H:i', strtotime($followUp['next_follow_up_date'] ?? 'N/A')) ?></td>
                                    <td><?= htmlspecialchars($followUp['discussion_note'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($followUp['next_action'] ?? 'N/A') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <div class="text-muted">
                                        No follow-ups found
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<script>
    document.getElementById('executiveSelect').addEventListener('change', function() {
        const selectedValue = this.value;
        const cards = document.querySelectorAll('.card-item');

        // Hide all cards initially
        cards.forEach(card => card.style.display = 'none');

        if (selectedValue) {
            // Show cards based on selected team
            // Example: show all cards for simplicity
            cards.forEach(card => card.style.display = 'block');

            // TODO: If you want team-wise filtering, you can add a data-team attribute to each card
        }
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {

        const weeklyData = <?= json_encode($weeklyData) ?>;
        const monthlyData = <?= json_encode($monthlyData) ?>;
        const yearlyData = <?= json_encode($yearlyData) ?>;
        const fullData = <?= json_encode($fullData) ?>;

        const dataMap = {
            week1: weeklyData,
            month1: monthlyData,
            year1: yearlyData,
            fullData: fullData
        };

        function getChartData(period) {
            const data = dataMap[period] || [];
            return {
                names: data.map(item => item.name),
                targets: data.map(item => parseInt(item.booking_target)),
                completed: data.map(item => parseInt(item.booking_completed))
            };
        }

        // Default period
        let currentPeriod = document.querySelector('#bookingTabs .nav-link.active')?.dataset.period || 'month1';
        let chartData = getChartData(currentPeriod);

        const chartOptions = {
            chart: {
                type: 'bar',
                height: 420,
                toolbar: {
                    show: false
                }
            },
            series: [{
                    name: 'Booking Target',
                    data: chartData.targets
                },
                {
                    name: 'Booking Completed',
                    data: chartData.completed
                }
            ],
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '50%',
                    borderRadius: 6
                }
            },
            dataLabels: {
                enabled: true
            },
            xaxis: {
                categories: chartData.names,
                labels: {
                    rotate: -20,
                    style: {
                        fontSize: '12px',
                        fontWeight: 600
                    }
                }
            },
            yaxis: {
                min: 0,
                title: {
                    text: 'Number of Bookings'
                }
            },
            colors: ['#5e4cdc', '#8e7eff'],
            legend: {
                position: 'top'
            },
            tooltip: {
                y: {
                    formatter: val => val + " bookings"
                }
            }
        };

        const chart = new ApexCharts(document.querySelector("#monthlyPerformanceChart"), chartOptions);
        chart.render();

        // Tab click
        document.querySelectorAll('#bookingTabs .nav-link').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('#bookingTabs .nav-link').forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                currentPeriod = this.dataset.period;
                const newData = getChartData(currentPeriod);

                chart.updateOptions({
                    series: [{
                            name: 'Booking Target',
                            data: newData.targets
                        },
                        {
                            name: 'Booking Completed',
                            data: newData.completed
                        }
                    ],
                    xaxis: {
                        categories: newData.names
                    }
                });
            });
        });

    });
</script>



<style>
    .card__title-wrap {
        min-width: 0;
    }


    .card__title {
        white-space: normal;
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    .card__wrapper {
        height: 100%;
    }
</style>
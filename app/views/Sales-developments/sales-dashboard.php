<div class="app__slide-wrapper p-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Sales Dashboard</h4>
            <strong class="text-muted">Performance</strong>
        </div>
        <div class="text-end">
            <h6 class="mb-0"><?= $_SESSION['user']['name'] ?></h6>
            <strong class="text-muted"><?= $_SESSION['user']['role_name'] ?></strong><br>
            <small class="text-muted"><?= date('l, F j, Y') ?></small>
        </div>

    </div>



    <!-- KPI CARDS -->
    <div class="row g-20 mb-4">

        <div class="col-xxl-3 col-xl-6 col-md-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon">
                        <span><i class="fa-regular fa-users"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Total Leads</h6>
                        <h3 class="card__title"><?= $totalLeadsCount ?? '' ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-6 col-md-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon">
                        <span><i class="fa-regular fa-user-plus"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">New Leads Today</h6>
                        <h3 class="card__title"><?= $todayLeads ?? '' ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-6 col-md-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon">
                        <span><i class="fa-regular fa-bell"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Follow-ups Due</h6>
                        <h3 class="card__title"><?= $followUpsDue ?? '' ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-6 col-md-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon">
                        <span><i class="fa-regular fa-circle-check"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Onboarded Clients</h6>
                        <h3 class="card__title"><?= $onboardedClients ?></h3>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- REVENUE + CONVERSION -->
    <div class="row g-20 mb-4">

        <div class="col-xl-6 col-md-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon">
                        <span><i class="fa-regular fa-indian-rupee-sign"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Booking Money Collected</h6>
                        <h3 class="card__title">BDT <?= $bookingCollected ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-md-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon">
                        <span><i class="fa-regular fa-chart-line"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Conversion Rate</h6>
                        <h3 class="card__title"><?php
                                                $formattedRate = number_format($conversionRate, 2); 
                                                echo $formattedRate . '%';
                                                ?></h3>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- CHART PLACEHOLDERS -->
    <div class="row g-20">

        <!-- SALES GRAPH CARD -->
        <div class="col-xl-8 col-lg-8 col-md-8">
            <div class="card__wrapper height-equal">
                <div class="card__title-wrap d-flex flex-wrap gap-10 align-items-center justify-content-between mb-25">
                    <h5 class="card__heading-title">Sales Overview</h5>
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
                                <button class="nav-link active" id="pills-bar-week-tab" data-bs-toggle="pill"
                                    data-bs-target="#pills-bar-week" type="button" role="tab"
                                    aria-controls="pills-bar-week" aria-selected="false">1W</button>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="card__content">
                    <div class="tab-content" id="pills-tabContent">
                        <div class="tab-pane fade" id="pills-bar-year" role="tabpanel"
                            aria-labelledby="pills-bar-year-tab">
                            <div class="card__line-chart">
                                <div id="salesChartYearly" style="height:250px;"></div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="pills-bar-month" role="tabpanel"
                            aria-labelledby="pills-bar-month-tab">
                            <div class="card__line-chart">
                                <div id="salesChartMonthly" style="height:250px;"></div>
                            </div>
                        </div>
                        <div class="tab-pane fade show active" id="pills-bar-week" role="tabpanel"
                            aria-labelledby="pills-bar-week-tab">
                            <div class="card__line-chart">
                                <div id="salesChartWeek" style="height:250px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card__wrapper">
                <h6 class="card__sub-title mb-15"> Weekly Activity</h6>
                <ul class="mb-0">
                    <li>Monday</li>
                    <li>Tuesday</li>
                    <li><strong>Wednesday (Today)</strong></li>
                </ul>
            </div>
        </div>

    </div>

</div>
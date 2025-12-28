<div class="app__slide-wrapper p-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Sales Dashboard</h4>
            <strong class="text-muted">Performance</strong>
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
                        <h6 class="card__sub-title mb-10">Total Flat Booking</h6>
                        <h3 class="card__title"><?= $onboardedClients ?></h3>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- REVENUE + CONVERSION -->
    <div class="row g-20 mb-4">

        <!-- TOTAL PROJECT -->
        <div class="col-xl-4 col-md-4">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon">
                        <span><i class="fa-solid fa-building"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Total Project</h6>
                        <h3 class="card__title"><?= $totalProjects ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- BOOKING MONEY COLLECTED -->
        <div class="col-xl-4 col-md-4">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon">
                        <span><i class="fa-solid fa-bangladeshi-taka-sign"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Booking Money Collected</h6>
                        <h3 class="card__title">
                            BDT <?= number_format($bookingCollected, 2) ?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- CONVERSION RATE -->
        <div class="col-xl-4 col-md-4">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon">
                        <span><i class="fa-solid fa-chart-line"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Conversion Rate</h6>
                        <h3 class="card__title">
                            <?= number_format($conversionRate, 2) ?>%
                        </h3>
                    </div>
                </div>
            </div>
        </div>

    </div>


</div>
<div class="app__slide-wrapper">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Sales Dashboard</h4>
        </div>

    </div>
    <!-- KPI CARDS -->
    <div class="row g-20 mb-4 align-items-stretch">

        <div class="col-xxl-3 col-xl-4 col-md-4 col-12 mb-2">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-10">
                    <div class="card__icon">
                        <span><i class="fa-regular fa-users"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__title text-break text-success mb-5">Total Leads</h6>
                        <h4 class="card__title"><?= $totalLeadsCount ?? '' ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-4 col-md-4 col-12 mb-2">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-10">
                    <div class="card__icon">
                        <span><i class="fa-regular fa-user-plus"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__title text-break text-success mb-10">New Leads Today</h6>
                        <h4 class="card__title"><?= $todayLeads ?? '' ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-4 col-md-4 col-12 mb-2">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-10">
                    <div class="card__icon">
                        <span><i class="fa-regular fa-bell"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__title text-break text-success mb-10">Follow-ups Due</h6>
                        <h4 class="card__title"><?= $followUpsDue ?? '' ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-4 col-md-4 col-12 mb-2">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-10">
                    <div class="card__icon">
                        <span><i class="fa-regular fa-circle-check"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__title text-break text-success mb-10">Today Follow Up</h6>
                        <h4 class="card__title"><?= $totalTodayFollowUps ?></h4>
                    </div>
                </div>
            </div>
        </div>


        <!-- REVENUE + CONVERSION -->
        <div class="col-xxl-3 col-xl-4 col-md-4 col-12 mb-2">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-10">
                    <div class="card__icon">
                        <span><i class="fa-regular fa-circle-check"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__title text-break text-success mb-10">Total Flat Booking</h6>
                        <h4 class="card__title"><?= $onboardedClients ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <!-- TOTAL PROJECT -->
        <div class="col-xxl-3 col-xl-4 col-md-4 col-12 mb-2">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-10">
                    <div class="card__icon">
                        <span><i class="fa-solid fa-building"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__title text-break text-success mb-10">Total Project</h6>
                        <h4 class="card__title"><?= $totalProjects ?></h4>
                    </div>
                </div>
            </div>
        </div>


        <!-- BOOKING MONEY COLLECTED -->
        <div class="col-xxl-3  col-xl-4 col-md-4 col-12 mb-2">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-10">
                    <div class="card__icon">
                        <span><i class="fa-solid fa-bangladeshi-taka-sign"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__title text-break text-success mb-10">Booking Money Collected</h6>
                        <h4 class="card__title">
                            <?= format_bdt($bookingCollected) ?>
                        </h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- CONVERSION RATE -->
        <div class="col-xxl-3  col-xl-4 col-md-4 col-12 mb-2">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-10">
                    <div class="card__icon">
                        <span><i class="fa-solid fa-chart-line"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__title text-break text-success mb-10">Conversion Rate</h6>
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
                            <th>Date</th>
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
                                    <td><?= date('d M, Y', strtotime($followUp['follow_up_date'])) ?></td>
                                    <td><?= htmlspecialchars($followUp['lead_name']) ?></td>
                                    <td><?= htmlspecialchars($followUp['project_name']) ?></td>
                                    <td><?= htmlspecialchars($followUp['flat_name']) ?></td>
                                    <td><?= htmlspecialchars($followUp['assigned_with_name']) ?></td>
                                    <td><?= date('d M, Y H:i', strtotime($followUp['follow_up_date'] . ' ' . $followUp['follow_up_time'])) ?></td>
                                    <td><?= date('d M, Y H:i', strtotime($followUp['next_follow_up_date'])) ?></td>
                                    <td><?= htmlspecialchars($followUp['discussion_note']) ?></td>
                                    <td><?= htmlspecialchars($followUp['next_action']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
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
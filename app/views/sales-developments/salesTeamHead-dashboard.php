<?php
//    print_r($_SESSION['user']);
?>

<div class="app__slide-wrapper">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
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
        <?php if (!empty($teamFilterType)): ?>
            <input type="hidden" name="team_filter_type" value="<?= htmlspecialchars($teamFilterType) ?>">
        <?php endif; ?>
        <?php if (!empty($teamYear)): ?>
            <input type="hidden" name="team_year" value="<?= htmlspecialchars($teamYear) ?>">
        <?php endif; ?>
        <?php if (!empty($teamStartMonth)): ?>
            <input type="hidden" name="team_start_month" value="<?= htmlspecialchars($teamStartMonth) ?>">
        <?php endif; ?>
        <?php if (!empty($teamEndMonth)): ?>
            <input type="hidden" name="team_end_month" value="<?= htmlspecialchars($teamEndMonth) ?>">
        <?php endif; ?>
        <div class="row g-2 mb-3 align-items-end">

            <!-- Employee Select -->
            <div class="col-12 col-md-3">
                <label for="executiveSelect" class="form-label">Select Sales Executive</label>
                <select id="executiveSelect" name="executive" class="form-select select2">
                    <option value="">-- Select Sales Executive --</option>
                    <?php foreach ($allSalesTeams as $team): 
                        $displayName = htmlspecialchars($team['full_name']);
                        $roleDisplay = htmlspecialchars($team['role_name'] ?? '');
                        
                        // If the role_id is 5 (Sales Manager), show their role as "Sales Team Leader"
                        if (($team['role_id'] ?? 0) == 5) {
                            $roleDisplay = "Sales Team Leader";
                        }
                        
                        $labelText = $displayName;
                        if (!empty($roleDisplay)) {
                            $labelText .= " (" . $roleDisplay . ")";
                        }
                    ?>
                        <option value="<?= $team['id'] ?>" <?= ($executiveId == $team['id']) ? 'selected' : '' ?>>
                            <?= $labelText ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Start Month Select -->
            <div class="col-6 col-md-2">
                <label for="startMonthSelect" class="form-label">Start Month</label>
                <select class="form-select" name="start_month" id="startMonthSelect">
                    <option value="">Select Month</option>
                    <?php foreach (range(1, 12) as $m):
                        $monthName = date('F', mktime(0, 0, 0, $m, 1));
                    ?>
                        <option value="<?= $m ?>" <?= ($start_month == $m) ? 'selected' : '' ?>>
                            <?= $monthName ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- End Month Select -->
            <div class="col-6 col-md-2">
                <label for="endMonthSelect" class="form-label">End Month</label>
                <select class="form-select" name="end_month" id="endMonthSelect">
                    <option value="">Select Month</option>
                    <?php foreach (range(1, 12) as $m):
                        $monthName = date('F', mktime(0, 0, 0, $m, 1));
                    ?>
                        <option value="<?= $m ?>" <?= ($end_month == $m) ? 'selected' : '' ?>>
                            <?= $monthName ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Year Select -->
            <div class="col-6 col-md-2">
                <label for="yearSelect" class="form-label">Select Year</label>
                <select class="form-select" name="year" id="yearSelect">
                    <option value="">Select Year</option>
                    <?php for ($y = 2025; $y <= 2035; $y++): ?>
                        <option value="<?= $y ?>" <?= ($year == $y) ? 'selected' : '' ?>>
                            <?= $y ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="col-12 col-md-3">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Filter
                    </button>
                    <a href="<?= $GLOBALS['base_url'] ?>/sales-team-head-dashboard" class="btn btn-secondary w-100 d-flex align-items-center justify-content-center">
                        <i class="fa-solid fa-rotate-left me-1"></i> Reset
                    </a>
                </div>
            </div>

        </div>
    </form>

    <!-- Performance Cards -->
    <div class="row mb-4" id="performanceCards">
        <!-- Target tracking data -->
        <?php if ($showCards): ?>


            <!-- New Lead & Action tracking data -->
            <div class="col-12 col-sm-6 col-md-4 mb-3 card-item">
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

            <div class="col-12 col-sm-6 col-md-4 mb-3 card-item">
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

            <div class="col-12 col-sm-6 col-md-4 mb-3 card-item">
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

            <div class="col-12 col-sm-6 col-md-4 mb-3 card-item">
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

            <div class="col-12 col-sm-6 col-md-4 mb-3 card-item">
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

            <div class="col-12 col-sm-6 col-md-4 mb-3 card-item">
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

    <!-- KPI CARDS -->
    <div>
        <h4 class="fw-bold mb-3">Full Summary</h4>
    </div>
    <div class="row g-20 mb-4 align-items-stretch">

        <div class="col-12 col-sm-6 col-md-4 col-xxl-3 mb-2">
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

        <div class="col-12 col-sm-6 col-md-4 col-xxl-3 mb-2">
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

        <div class="col-12 col-sm-6 col-md-4 col-xxl-3 mb-2">
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

        <div class="col-12 col-sm-6 col-md-4 col-xxl-3 mb-2">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-10">
                    <div class="card__icon">
                        <span><i class="fa-regular fa-circle-check"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__title text-break text-primary mb-10">Total Sales Team Members</h6>
                        <h4 class="card__title"><?= $totalSalesTeams ?? 0 ?></h4>
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
                        <h6 class="card__title text-break text-primary mb-10">Total Flat Booking</h6>
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
                        <h6 class="card__title text-break text-primary mb-10">Total Project</h6>
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
                        <h6 class="card__title text-break text-primary mb-10">Booking Money Collected</h6>
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
                                    <td><?= date('d M, Y', strtotime($followUp['follow_up_date'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars($followUp['full_name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($followUp['project_name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($followUp['flat_name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($followUp['assigned_with_name'] ?? '') ?></td>
                                    <td><?= date('d M, Y H:i', strtotime($followUp['follow_up_date'] ?? '' . ' ' . $followUp['follow_up_time'] ?? '')) ?></td>
                                    <td><?= date('d M, Y H:i', strtotime($followUp['next_follow_up_date'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars($followUp['discussion_note'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($followUp['next_action'] ?? '') ?></td>
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
    <!-- team performance  chart -->
    <div class="card__wrapper shadow-sm p-3 mt-4">
        <div class="mb-2">
            <h5 class="text-center ">Sales Teams Booking Performance</h5>
        </div>

        <!-- Search Filter for Sales Teams Booking Performance -->
        <?php
        $teamStartFilterYear = 2025;
        $teamEndFilterYear = 2035;
        $teamMonthsList = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December'
        ];
        ?>
        <form action="" method="get" class="mb-4">
            <?php if (!empty($executiveId)): ?>
                <input type="hidden" name="executive" value="<?= htmlspecialchars($executiveId) ?>">
            <?php endif; ?>
            <?php if (!empty($year)): ?>
                <input type="hidden" name="year" value="<?= htmlspecialchars($year) ?>">
            <?php endif; ?>
            <?php if (!empty($start_month)): ?>
                <input type="hidden" name="start_month" value="<?= htmlspecialchars($start_month) ?>">
            <?php endif; ?>
            <?php if (!empty($end_month)): ?>
                <input type="hidden" name="end_month" value="<?= htmlspecialchars($end_month) ?>">
            <?php endif; ?>

            <div class="row align-items-end g-2">
                <!-- Filter Type -->
                <div class="col-12 col-md-2" id="teamFilterTypeCol">
                    <div class="from__input-box">
                        <label class="form-label small fw-bold text-muted mb-2"><i class="fa-solid fa-filter me-1"></i> Filter Type</label>
                        <select class="form-control form-select" name="team_filter_type" id="teamFilterTypeSelect">
                            <option value="monthly" <?= ($teamFilterType === 'monthly') ? 'selected' : '' ?>>Monthly</option>
                            <option value="yearly" <?= ($teamFilterType === 'yearly') ? 'selected' : '' ?>>Yearly</option>
                        </select>
                    </div>
                </div>

                <!-- Year Select -->
                <div class="col-12 col-md-2" id="teamYearCol">
                    <div class="from__input-box">
                        <label class="form-label small fw-bold text-muted mb-2"><i class="fa-solid fa-calendar me-1"></i> Year</label>
                        <select class="form-control form-select" name="team_year">
                            <?php for ($y = $teamStartFilterYear; $y <= $teamEndFilterYear; $y++): ?>
                                <option value="<?= $y ?>" <?= ($teamYear == $y) ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <!-- Start Month -->
                <div class="col-12 col-md-2" id="teamStartMonthCol">
                    <div class="from__input-box">
                        <label class="form-label small fw-bold text-muted mb-2"><i class="fa-solid fa-calendar-day me-1"></i> Start Month</label>
                        <select class="form-control form-select" name="team_start_month">
                            <?php foreach ($teamMonthsList as $num => $name): ?>
                                <option value="<?= $num ?>" <?= ($teamStartMonth == $num) ? 'selected' : '' ?>><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- End Month -->
                <div class="col-12 col-md-2" id="teamEndMonthCol">
                    <div class="from__input-box">
                        <label class="form-label small fw-bold text-muted mb-2"><i class="fa-solid fa-calendar-day me-1"></i> End Month</label>
                        <select class="form-control form-select" name="team_end_month">
                            <?php foreach ($teamMonthsList as $num => $name): ?>
                                <option value="<?= $num ?>" <?= ($teamEndMonth == $num) ? 'selected' : '' ?>><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="col-12 col-md-4" id="teamActionButtonsCol">
                    <div class="from__input-box">
                        <label class="form-label small fw-bold text-muted mb-2 d-none d-md-block" style="visibility: hidden;">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fa-solid fa-magnifying-glass me-1"></i> Search
                            </button>
                            <a href="<?= $GLOBALS['base_url'] ?>/sales-team-head-dashboard" class="btn btn-secondary w-100 d-flex align-items-center justify-content-center">
                                <i class="fa-solid fa-rotate-left me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Tabs -->
        <ul class="nav nav-pills mb-3" id="bookingTabs">
            <li class="nav-item">
                <button class="nav-link <?= ($defaultTeamTab === 'year1') ? 'active' : '' ?>" data-period="year1">Year</button>
            </li>
            <li class="nav-item">
                <button class="nav-link <?= ($defaultTeamTab === 'month1') ? 'active' : '' ?>" data-period="month1">Month</button>
            </li>
            <li class="nav-item d-none">
                <button class="nav-link" data-period="week1">Week</button>
            </li>
            <li class="nav-item">
                <button class="nav-link <?= ($defaultTeamTab === 'fullData') ? 'active' : '' ?>" data-period="fullData">Full Data</button>
            </li>
        </ul>

        <!-- Chart -->
        <div id="monthlyPerformanceChart"></div>

        <!-- Summary Table -->
        <div class="mt-4 border-top pt-4">
            <h6 class="mb-3 fw-bold text-primary"><i class="fa-solid fa-list-check me-1"></i> Sales Teams Booking Summary List</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr style="font-size: 13px;">
                            <th>S/L</th>
                            <th>Sales Executive</th>
                            <th>Target Bookings</th>
                            <th>Completed Bookings</th>
                            <th>Total Flat Value</th>
                            <th>Total Booking Money</th>
                            <th>Total Registration Money</th>
                        </tr>
                    </thead>
                    <tbody id="teamPerformanceTableBody" style="font-size: 13px;">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Toggle Filter UI
        const teamFilterTypeSelect = document.getElementById('teamFilterTypeSelect');
        const teamStartMonthCol = document.getElementById('teamStartMonthCol');
        const teamEndMonthCol = document.getElementById('teamEndMonthCol');
        const teamFilterTypeCol = document.getElementById('teamFilterTypeCol');
        const teamYearCol = document.getElementById('teamYearCol');

        if (teamFilterTypeSelect && teamStartMonthCol && teamEndMonthCol && teamFilterTypeCol && teamYearCol) {
            function updateTeamFilterUI() {
                if (teamFilterTypeSelect.value === 'yearly') {
                    teamStartMonthCol.style.setProperty('display', 'none', 'important');
                    teamEndMonthCol.style.setProperty('display', 'none', 'important');
                    teamFilterTypeCol.className = 'col-12 col-md-4';
                    teamYearCol.className = 'col-12 col-md-4';
                } else {
                    teamStartMonthCol.style.setProperty('display', 'block', 'important');
                    teamEndMonthCol.style.setProperty('display', 'block', 'important');
                    teamFilterTypeCol.className = 'col-12 col-md-2';
                    teamYearCol.className = 'col-12 col-md-2';
                }
            }

            teamFilterTypeSelect.addEventListener('change', updateTeamFilterUI);
            updateTeamFilterUI();
        }

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

        function updateTable(period) {
            const data = dataMap[period] || [];
            const tbody = document.getElementById('teamPerformanceTableBody');
            if (!tbody) return;
            
            tbody.innerHTML = '';
            
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3">No bookings found for the selected period.</td></tr>';
                return;
            }
            
            const formatBDT = (val) => {
                return new Intl.NumberFormat('en-BD', { style: 'currency', currency: 'BDT', minimumFractionDigits: 0 }).format(val);
            };

            data.forEach((item, index) => {
                const tr = document.createElement('tr');
                tr.innerHTML = '<td>' + (index + 1) + '</td>' +
                    '<td class="fw-semibold">' + item.name + '</td>' +
                    '<td>' + item.booking_target + '</td>' +
                    '<td><span class="badge bg-success">' + item.booking_completed + '</span></td>' +
                    '<td class="fw-bold">' + formatBDT(item.total_flat_value) + '</td>' +
                    '<td class="text-primary fw-bold">' + formatBDT(item.total_booking_money) + '</td>' +
                    '<td class="text-info fw-bold">' + formatBDT(item.total_registration_money) + '</td>';
                tbody.appendChild(tr);
            });
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
                custom: function({series, seriesIndex, dataPointIndex, w}) {
                    const data = dataMap[currentPeriod][dataPointIndex];
                    if (!data) return '';
                    
                    const formatBDT = (val) => {
                        return new Intl.NumberFormat('en-BD', { style: 'currency', currency: 'BDT', minimumFractionDigits: 0 }).format(val);
                    };

                    return '<div class="p-3 bg-white border rounded shadow-sm" style="font-family: inherit; font-size: 13px;">' +
                        '<div class="fw-bold text-dark mb-2">' + data.name + '</div>' +
                        '<div class="mb-1"><span class="text-primary">•</span> Target: <strong>' + data.booking_target + '</strong></div>' +
                        '<div class="mb-2"><span class="text-success">•</span> Completed: <strong>' + data.booking_completed + '</strong></div>' +
                        '<div class="border-top my-2"></div>' +
                        '<div class="mb-1">Flat Value: <strong class="text-dark">' + formatBDT(data.total_flat_value) + '</strong></div>' +
                        '<div class="mb-1">Booking Money: <strong class="text-primary">' + formatBDT(data.total_booking_money) + '</strong></div>' +
                        '<div>Registration Money: <strong class="text-info">' + formatBDT(data.total_registration_money) + '</strong></div>' +
                        '</div>';
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

                // Update summary table
                updateTable(currentPeriod);
            });
        });

        // Initialize table
        updateTable(currentPeriod);
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
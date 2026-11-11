<?php
$page_title = "leads Page";
$page = 'All Onboarding Clints';
$addPage = 'Add Leads';
$addPage = true;
$show_breadcrumb = true;
?>


<div class="row  align-items-stretch">

    <div class="desktop-only w-100">
    <form action="" method="get">
        <div class="row mb-3 g-2 align-items-end">

            <!-- From Date -->
            <div class="col-xxl-2 col-xl-3 col-lg-3 col-md-6 col-12">
                <div class="card__wrapper h-100">
                    <div class="from__input-box">
                        <label class="form-label small fw-semibold text-muted mb-1" style="font-size: 11px;">From Date</label>
                        <input type="date" class="form-control" name="from_date" value="<?= htmlspecialchars($from_date ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- To Date -->
            <div class="col-xxl-2 col-xl-3 col-lg-3 col-md-6 col-12">
                <div class="card__wrapper h-100">
                    <div class="from__input-box">
                        <label class="form-label small fw-semibold text-muted mb-1" style="font-size: 11px;">To Date</label>
                        <input type="date" class="form-control" name="to_date" value="<?= htmlspecialchars($to_date ?? '') ?>">
                    </div>
                </div>
            </div>

            <?php
            $currentYear = date('Y');
            $startYear   = $currentYear - 10;
            $selectedYear = $_GET['year'] ?? '';
            ?>
            <!-- Year Select -->
            <div class="col-xxl-2 col-xl-3 col-lg-3 col-md-6 col-12">
                <div class="card__wrapper h-100">
                    <div class="from__input-box">
                        <label class="form-label small fw-semibold text-muted mb-1" style="font-size: 11px;">Year</label>
                        <select class="form-control" name="year">
                            <option value="">All Years</option>

                            <?php for ($year = $currentYear; $year >= $startYear; $year--): ?>
                                <option
                                    value="<?= $year ?>"
                                    <?= $selectedYear == $year ? 'selected' : '' ?>>
                                    <?= $year ?>
                                </option>
                            <?php endfor; ?>

                        </select>
                    </div>
                </div>
            </div>

            <?php
            $months = [
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
            $selectedMonth = $_GET['month'] ?? '';
            ?>

            <!-- Month Select -->
            <div class="col-xxl-2 col-xl-3 col-lg-3 col-md-6 col-12">
                <div class="card__wrapper h-100">
                    <div class="from__input-box">
                        <label class="form-label small fw-semibold text-muted mb-1" style="font-size: 11px;">Month</label>
                        <select class="form-control" name="month">
                            <option value="">All Months</option>
                            <?php foreach ($months as $id => $name): ?>

                                <option value="<?= $id ?>" <?= $selectedMonth == $id ? 'selected' : '' ?>>
                                    <?= $name ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                    </div>
                </div>
            </div>

            <!-- Project Select -->
            <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-6 col-12">
                <div class="card__wrapper h-100">
                    <div class="from__input-box">
                        <label class="form-label small fw-semibold text-muted mb-1" style="font-size: 11px;">Project</label>
                        <select class="form-control" name="project_id">
                            <option value="">All Projects</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?= $project['id'] ?>" <?= ($selected_project_id ?? '') == $project['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($project['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Sales Executive with Client Counts -->
            <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-6 col-12">
                <div class="card__wrapper h-100">
                    <div class="from__input-box">
                        <label class="form-label small fw-semibold text-muted mb-1" style="font-size: 11px;">Sales Executive</label>
                        <select class="form-control" name="sales_by_id">
                            <option value="">All Executives</option>
                            <?php if (!empty($salesPersonsWithCounts)): ?>
                                <?php foreach ($salesPersonsWithCounts as $sp): ?>
                                    <option value="<?= $sp['user_id'] ?>" <?= (!empty($selected_sales_by_id) && (int)$selected_sales_by_id === (int)$sp['user_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sp['sales_name']) ?> (<?= (int)$sp['client_count'] ?> <?= (int)$sp['client_count'] === 1 ? 'Client' : 'Clients' ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
            </div>

            <?php if (!empty($canViewAllTeams)): ?>
                <!-- Team Select (For Management / Head) -->
                <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-6 col-12">
                    <div class="card__wrapper h-100">
                        <div class="from__input-box">
                            <label class="form-label small fw-semibold text-muted mb-1" style="font-size: 11px;">Team</label>
                            <select class="form-control" name="team_id">
                                <option value="">All Teams</option>
                                <?php if (!empty($allTeams)): ?>
                                    <?php foreach ($allTeams as $team): ?>
                                        <option value="<?= $team['id'] ?>" <?= (!empty($teamIdForQuery) && (int)$teamIdForQuery === (int)$team['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($team['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Buttons -->
            <div class="col-xxl-2 col-xl-4 col-lg-4 col-md-6 col-12 ms-auto">
                <div class="card__wrapper h-100">
                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <button type="submit" class="btn btn-secondary w-100">
                            <i class="bi bi-search me-1"></i> Search
                        </button>
                        <a href="<?= $GLOBALS['base_url'] ?>/onboarding-clients" class="btn btn-outline-success w-100">
                            Reset
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Quick Sales Executive Filter Pills -->
    <?php if (!empty($salesPersonsWithCounts) && count($salesPersonsWithCounts) > 1): ?>
        <div class="col-12 mb-3">
            <div class="d-flex flex-wrap align-items-center gap-2 p-2 bg-white rounded border shadow-2xs">
                <span class="text-muted small fw-semibold d-flex align-items-center gap-1 ps-1">
                    <i class="bi bi-person-lines-fill text-primary"></i> Executives:
                </span>
                <?php 
                $baseParams = $_GET;
                unset($baseParams['sales_by_id']);
                $allExecsUrl = '?' . http_build_query($baseParams);
                ?>
                <a href="<?= htmlspecialchars($allExecsUrl) ?>" class="badge <?= empty($selected_sales_by_id) ? 'bg-primary text-white shadow-xs' : 'bg-light text-dark border' ?> text-decoration-none px-2.5 py-1.5" style="font-size: 11.5px; border-radius: 6px;">
                    All (<?= $leadCounts ?>)
                </a>
                <?php foreach ($salesPersonsWithCounts as $sp): ?>
                    <?php 
                    $isActiveSp = (!empty($selected_sales_by_id) && (int)$selected_sales_by_id === (int)$sp['user_id']);
                    $spParams = array_merge($_GET, ['sales_by_id' => $sp['user_id']]);
                    $spUrl = '?' . http_build_query($spParams);
                    ?>
                    <a href="<?= htmlspecialchars($spUrl) ?>" class="badge <?= $isActiveSp ? 'bg-primary text-white shadow-xs' : 'bg-light text-dark border' ?> text-decoration-none px-2.5 py-1.5 d-inline-flex align-items-center gap-1.5" style="font-size: 11.5px; border-radius: 6px;">
                        <span><?= htmlspecialchars($sp['sales_name']) ?></span>
                        <span class="badge <?= $isActiveSp ? 'bg-white text-primary fw-bold' : 'bg-secondary-subtle text-dark border' ?>" style="font-size: 10px; border-radius: 10px;"><?= $sp['client_count'] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Active Filter Notification Banner -->
    <?php if (!empty($selected_sales_by_id) || !empty($from_date) || !empty($to_date) || !empty($selectedYear) || !empty($selectedMonth) || !empty($selected_project_id)): ?>
        <div class="col-12 mb-3">
            <div class="alert alert-info py-2 px-3 mb-0 d-flex justify-content-between align-items-center flex-wrap gap-2" style="font-size: 12.5px; border-radius: 8px;">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <i class="bi bi-funnel-fill text-primary"></i>
                    <strong>Active Filters:</strong>
                    <?php if (!empty($selected_sales_person_name)): ?>
                        <span class="badge bg-white text-dark border"><i class="bi bi-person me-1"></i><?= htmlspecialchars($selected_sales_person_name) ?> (<?= $leadCounts ?> <?= $leadCounts === 1 ? 'Client' : 'Clients' ?>)</span>
                    <?php endif; ?>
                    <?php if (!empty($from_date) || !empty($to_date)): ?>
                        <span class="badge bg-white text-dark border"><i class="bi bi-calendar-range me-1"></i><?= htmlspecialchars($from_date ?? 'Start') ?> to <?= htmlspecialchars($to_date ?? 'Today') ?></span>
                    <?php elseif (!empty($selectedYear) || !empty($selectedMonth)): ?>
                        <span class="badge bg-white text-dark border"><i class="bi bi-calendar me-1"></i><?= !empty($selectedMonth) ? ($months[(int)$selectedMonth] ?? '') : '' ?> <?= $selectedYear ?></span>
                    <?php endif; ?>
                    <?php if (!empty($selected_project_id)): ?>
                        <?php 
                        $pName = '';
                        foreach ($projects as $p) { if ($p['id'] == $selected_project_id) { $pName = $p['name']; break; } }
                        ?>
                        <?php if ($pName): ?>
                            <span class="badge bg-white text-dark border"><i class="bi bi-building me-1"></i><?= htmlspecialchars($pName) ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                    <span class="text-muted">(<?= $leadCounts ?> total found)</span>
                </div>
                <a href="<?= $GLOBALS['base_url'] ?>/onboarding-clients" class="text-danger fw-semibold text-decoration-none small">
                    <i class="bi bi-x-circle me-1"></i>Clear All
                </a>
            </div>
        </div>
    <?php endif; ?>
    </div>


    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 multiple_tables">
                    <thead>
                        <tr class="table__title table__sort">
                            <th style="min-width:60px;">S/L</th> <!-- non-sticky first column -->
                            <th class="">Booking Confirm date</th>
                            <th class="th_1">Client Name</th>
                            <th>Phone</th>
                            <!-- <th>Alt Phone</th> -->
                            <th>Email</th>
                            <th>Project</th>
                            <th>Sales Executive</th>
                            <th>Unit/ Share</th>
                            <th>Share Price</th>
                            <th>Booking Money</th>
                            <th>Registration Money</th>
                            <th>Total Paid</th>

                            <th class="text-center ">Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">

                    
                        <?php foreach ($leads as $key => $lead): ?>
                            <tr>
                                <td><?= $key + 1 ?></td>
                                <td class=""><?= $lead['booking_pay_date'] ? formatDate($lead['booking_pay_date']) : '' ?></td>
                                <td class="td_1"><?= $lead['full_name'] ?? '' ?></td>
                                <td><?= $lead['phone'] ?? '' ?></td>
                                <td><?= $lead['email'] ?? '' ?></td>

                                <td>
                                    <?= $lead['project_name'] ?>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($lead['sales_agent_display'] ?? 'N/A') ?></div>
                                    <div class="mt-1 d-flex flex-wrap gap-1 align-items-center">
                                        <?php if (!empty($lead['sale_team_name'])): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 10px;" title="Team at the time of booking">
                                                <i class="bi bi-people-fill me-1"></i><?= htmlspecialchars($lead['sale_team_name']) ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php 
                                        $saleTid = !empty($lead['sale_team_id']) ? (int)$lead['sale_team_id'] : 0;
                                        $currTid = !empty($lead['current_team_id']) ? (int)$lead['current_team_id'] : 0;
                                        $isResigned = in_array(strtolower($lead['sales_employee_status'] ?? ''), ['resigned', 'terminated']);
                                        ?>
                                        <?php if ($saleTid > 0 && $currTid > 0 && $saleTid !== $currTid): ?>
                                            <span class="badge bg-warning-subtle text-dark border border-warning" style="font-size: 10px;" title="Switched team: currently in <?= htmlspecialchars($lead['current_team_name'] ?? '') ?>">
                                                <i class="bi bi-arrow-right me-0.5"></i>Now: <?= htmlspecialchars($lead['current_team_name'] ?? 'Other Team') ?>
                                            </span>
                                        <?php elseif ($isResigned): ?>
                                            <span class="badge bg-secondary-subtle text-muted border" style="font-size: 10px;" title="Salesperson has resigned/left">
                                                <i class="bi bi-person-x me-1"></i>Resigned
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?= $lead['flat_name'] ?> - <span><?= !empty($lead['sqft']) ? format_sqft($lead['sqft']) : '-' ?></span>
                                </td>
                                <td>
                                    <?= !empty($lead['price_value']) ? format_bdt($lead['price_value']) : '-' ?>
                                </td>
                                <td class="fw-semibold text-primary">
                                    <?= !empty($lead['booking_money']) ? format_bdt($lead['booking_money']) : '-' ?>
                                </td>
                                <td class="fw-semibold text-info">
                                    <?= !empty($lead['registration_money']) ? format_bdt($lead['registration_money']) : '-' ?>
                                </td>
                                <td class="fw-semibold text-success">
                                    <?= !empty($lead['amount']) ? format_bdt($lead['amount']) : '-' ?>
                                </td>

                                <td class="">
                                    <div class="d-flex align-items-center justify-content-start gap-10">

                                        <!-- View Button (Existing) -->
                                        <a class="table__icon download" href="<?= $GLOBALS['base_url'] ?>/view-lead/<?= $lead['lead_id'] ?>" title="View Lead">
                                            <i class="fa-regular fa-eye"></i>
                                        </a>
                                    </div>
                                </td>

                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<div class="mobile-only">
    <!-- Filter Toggle Button -->
    <button class="filter-button w-100 mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#mobileFilterCollapse" aria-expanded="false" aria-controls="mobileFilterCollapse">
        <i class="bi bi-funnel"></i> Filter & Sort
    </button>

    <!-- Collapsible Filter Section -->
    <div class="collapse" id="mobileFilterCollapse">
        <form action="" method="get" class="p-3 border rounded bg-white shadow-xs mb-3">
            <div class="d-flex flex-column gap-2">
                <!-- Custom Date Range -->
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label small text-muted mb-1" style="font-size: 11px;">From Date</label>
                        <input type="date" class="form-control form-control-sm" name="from_date" value="<?= htmlspecialchars($from_date ?? '') ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-muted mb-1" style="font-size: 11px;">To Date</label>
                        <input type="date" class="form-control form-control-sm" name="to_date" value="<?= htmlspecialchars($to_date ?? '') ?>">
                    </div>
                </div>

                <div class="row g-2">
                    <!-- Year Select -->
                    <div class="col-6">
                        <?php
                        $currentYear = date('Y');
                        $startYear = $currentYear - 10;
                        $selectedYear = $_GET['year'] ?? '';
                        ?>
                        <label class="form-label small text-muted mb-1" style="font-size: 11px;">Year</label>
                        <select class="form-select form-select-sm" name="year">
                            <option value="">All Years</option>
                            <?php for ($year = $currentYear; $year >= $startYear; $year--): ?>
                                <option value="<?= $year ?>" <?= $selectedYear == $year ? 'selected' : '' ?>><?= $year ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <!-- Month Select -->
                    <div class="col-6">
                        <?php
                        $months = [
                            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                        ];
                        $selectedMonth = $_GET['month'] ?? '';
                        ?>
                        <label class="form-label small text-muted mb-1" style="font-size: 11px;">Month</label>
                        <select class="form-select form-select-sm" name="month">
                            <option value="">All Months</option>
                            <?php foreach ($months as $id => $name): ?>
                                <option value="<?= $id ?>" <?= $selectedMonth == $id ? 'selected' : '' ?>><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Project Select -->
                <div>
                    <label class="form-label small text-muted mb-1" style="font-size: 11px;">Project</label>
                    <select class="form-select form-select-sm" name="project_id">
                        <option value="">All Projects</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>" <?= ($selected_project_id ?? '') == $project['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($project['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Sales Executive Select with Counts -->
                <div>
                    <label class="form-label small text-muted mb-1" style="font-size: 11px;">Sales Executive</label>
                    <select class="form-select form-select-sm" name="sales_by_id">
                        <option value="">All Executives</option>
                        <?php if (!empty($salesPersonsWithCounts)): ?>
                            <?php foreach ($salesPersonsWithCounts as $sp): ?>
                                <option value="<?= $sp['user_id'] ?>" <?= (!empty($selected_sales_by_id) && (int)$selected_sales_by_id === (int)$sp['user_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sp['sales_name']) ?> (<?= (int)$sp['client_count'] ?> <?= (int)$sp['client_count'] === 1 ? 'Client' : 'Clients' ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <?php if (!empty($canViewAllTeams)): ?>
                    <!-- Team Select -->
                    <div>
                        <label class="form-label small text-muted mb-1" style="font-size: 11px;">Team</label>
                        <select class="form-select form-select-sm" name="team_id">
                            <option value="">All Teams</option>
                            <?php if (!empty($allTeams)): ?>
                                <?php foreach ($allTeams as $team): ?>
                                    <option value="<?= $team['id'] ?>" <?= (!empty($teamIdForQuery) && (int)$teamIdForQuery === (int)$team['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($team['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="d-flex gap-2 flex-wrap w-100 mt-2">
                    <button type="submit" class="btn btn-secondary btn-sm flex-grow-1"><i class="bi bi-search me-1"></i> Search</button>
                    <a href="<?= $GLOBALS['base_url'] ?>/onboarding-clients" class="btn btn-outline-success btn-sm flex-grow-1">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <?php foreach ($leads as $key => $lead): ?>
        <!-- Lead Card -->
        <div class="lead-card">
            <!-- Booking Date & Name -->
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <div class="lead-name fw-bold"><?= $lead['full_name'] ?? '-' ?></div>
                    <div class="text-muted" style="font-size:0.85rem"><?= $lead['booking_pay_date'] ? formatDate($lead['booking_pay_date']) : '-' ?></div>
                </div>
            </div>

            <!-- Personal Info -->
            <div class="d-flex flex-wrap gap-2 mb-2">
                <span class="d-flex align-items-center gap-1"><i class="bi bi-telephone"></i> <?= $lead['phone'] ?? '-' ?></span>
                <span class="d-flex align-items-center gap-1"><i class="bi bi-envelope"></i> <?= $lead['email'] ?? '-' ?></span>
            </div>

            <!-- Project & Flat & Sales Agent -->
            <div class="d-flex flex-column gap-1 mb-2">
                <span class="text-truncate"><i class="bi bi-building me-1"></i> <?= $lead['project_name'] ?? '-' ?></span>
                <div class="d-flex flex-wrap gap-1 align-items-center">
                    <span class="text-truncate"><i class="bi bi-person me-1"></i> Sales: <?= htmlspecialchars($lead['sales_agent_display'] ?? 'N/A') ?></span>
                    <?php if (!empty($lead['sale_team_name'])): ?>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-0.5 px-1.5" style="font-size: 9px;" title="Team at sale time">
                            <i class="bi bi-people-fill me-0.5"></i><?= htmlspecialchars($lead['sale_team_name']) ?>
                        </span>
                    <?php endif; ?>
                    <?php 
                    $mSaleTid = !empty($lead['sale_team_id']) ? (int)$lead['sale_team_id'] : 0;
                    $mCurrTid = !empty($lead['current_team_id']) ? (int)$lead['current_team_id'] : 0;
                    $mIsResigned = in_array(strtolower($lead['sales_employee_status'] ?? ''), ['resigned', 'terminated']);
                    ?>
                    <?php if ($mSaleTid > 0 && $mCurrTid > 0 && $mSaleTid !== $mCurrTid): ?>
                        <span class="badge bg-warning-subtle text-dark border border-warning py-0.5 px-1.5" style="font-size: 9px;" title="Switched team: currently in <?= htmlspecialchars($lead['current_team_name'] ?? '') ?>">
                            <i class="bi bi-arrow-right me-0.5"></i>Now: <?= htmlspecialchars($lead['current_team_name'] ?? 'Other Team') ?>
                        </span>
                    <?php elseif ($mIsResigned): ?>
                        <span class="badge bg-secondary-subtle text-muted border py-0.5 px-1.5" style="font-size: 9px;">
                            <i class="bi bi-person-x me-0.5"></i>Resigned
                        </span>
                    <?php endif; ?>
                </div>
                <span class="text-truncate"><i class="bi bi-house me-1"></i> <?= $lead['flat_name'] ?? '-' ?> - <?= !empty($lead['sqft']) ? format_sqft($lead['sqft']) : '' ?></span>
            </div>

            <!-- Flat Value & Booking Amount -->
            <div class="d-flex flex-wrap gap-2 mb-1">
                <span class="project-badge ">Flat Value: <?= !empty($lead['price_value']) ? format_bdt($lead['price_value']) : '-' ?></span>
                <span class="project-badge text-primary">Booking Money: <?= !empty($lead['booking_money']) ? format_bdt($lead['booking_money']) : '-' ?></span>
                <span class="project-badge text-info">Registration: <?= !empty($lead['registration_money']) ? format_bdt($lead['registration_money']) : '-' ?></span>
                <span class="project-badge text-success">Total Paid: <?= !empty($lead['amount']) ? format_bdt($lead['amount']) : '-' ?></span>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- FAB Button -->
    <button class="fab-button d-none">
        <i class="bi bi-plus-lg"></i>
    </button>
</div>

</div>


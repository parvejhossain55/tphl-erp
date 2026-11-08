<?php
$page_title = "Leads Page";
$page = 'My Onboarding Projects';
$show_breadcrumb = true;
?>
<!-- TOP SUMMARY METRIC CARDS -->
<div class="row  mb-3 align-items-stretch">
    <!-- 1. Total Clients Card -->
    <div class="col-6 col-md-3">
        <div class="card__wrapper p-3 h-100 border-0 shadow-sm" style="border-radius: 12px; border-left: 4px solid #2563eb !important; background: #ffffff;">
            <div class="d-flex justify-content-between align-items-center">
                <div class="overflow-hidden pe-1">
                    <span class="text-uppercase fw-bold text-muted small" style="font-size: 11px; letter-spacing: 0.5px;">
                        <i class="fa-solid fa-users me-1 text-primary"></i> Total Clients
                    </span>
                    <h3 class="fw-bold mb-0 mt-1" style="color: #0f172a; font-size: 1.6rem;">
                        <?= number_format($totalClients ?? 0) ?>
                    </h3>
                    <span class="small text-muted" style="font-size: 11px;">Unique Onboarded Clients</span>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary flex-shrink-0" style="width: 44px; height: 44px; font-size: 1.25rem;">
                    <i class="fa-solid fa-user-check"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Total Units Sold Card -->
    <div class="col-6 col-md-3">
        <div class="card__wrapper p-3 h-100 border-0 shadow-sm" style="border-radius: 12px; border-left: 4px solid #7c3aed !important; background: #ffffff;">
            <div class="d-flex justify-content-between align-items-center">
                <div class="overflow-hidden pe-1">
                    <span class="text-uppercase fw-bold text-muted small" style="font-size: 11px; letter-spacing: 0.5px;">
                        <i class="fa-solid fa-building-circle-check me-1" style="color: #7c3aed;"></i> Total Units Sold
                    </span>
                    <h3 class="fw-bold mb-0 mt-1" style="color: #0f172a; font-size: 1.6rem;">
                        <?= number_format($totalUnitsSold ?? count($projects ?? [])) ?>
                    </h3>
                    <span class="small text-muted" style="font-size: 11px;">Total Booked Flats / Shares</span>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; font-size: 1.25rem; background: #f3e8ff; color: #7c3aed;">
                    <i class="fa-solid fa-hotel"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Total Sales Value Card -->
    <div class="col-6 col-md-3">
        <div class="card__wrapper p-3 h-100 border-0 shadow-sm" style="border-radius: 12px; border-left: 4px solid #059669 !important; background: #ffffff;">
            <div class="d-flex justify-content-between align-items-center">
                <div class="overflow-hidden pe-1">
                    <span class="text-uppercase fw-bold text-muted small" style="font-size: 11px; letter-spacing: 0.5px;">
                        <i class="fa-solid fa-hand-holding-dollar me-1 text-success"></i> Total Value
                    </span>
                    <h3 class="fw-bold mb-0 mt-1" style="color: #0f172a; font-size: 1.35rem;">
                        <?= format_bdt($totalPriceValue ?? 0) ?>
                    </h3>
                    <span class="small text-muted" style="font-size: 11px;">Combined Unit Prices</span>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-success-subtle text-success flex-shrink-0" style="width: 44px; height: 44px; font-size: 1.25rem;">
                    <i class="fa-solid fa-sack-dollar"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Total Collected Card -->
    <div class="col-6 col-md-3">
        <div class="card__wrapper p-3 h-100 border-0 shadow-sm" style="border-radius: 12px; border-left: 4px solid #0891b2 !important; background: #ffffff;">
            <div class="d-flex justify-content-between align-items-center">
                <div class="overflow-hidden pe-1">
                    <span class="text-uppercase fw-bold text-muted small" style="font-size: 11px; letter-spacing: 0.5px;">
                        <i class="fa-solid fa-money-bill-trend-up me-1 text-info"></i> Total Collected
                    </span>
                    <h3 class="fw-bold mb-0 mt-1" style="color: #0f172a; font-size: 1.35rem;">
                        <?= format_bdt($totalPaidAmount ?? 0) ?>
                    </h3>
                    <span class="small text-muted" style="font-size: 11px;">Booking + Reg Money Paid</span>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-info-subtle text-info flex-shrink-0" style="width: 44px; height: 44px; font-size: 1.25rem;">
                    <i class="fa-solid fa-receipt"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Informational Note Banner -->
<div class="alert alert-light border shadow-xs d-flex align-items-center justify-content-between flex-wrap gap-2 py-2 px-3 mb-3" style="border-radius: 8px; font-size: 12.5px; background: #ffffff;">
    <div class="text-muted d-flex align-items-center gap-2">
        <i class="fa-solid fa-circle-info text-primary fs-6"></i>
        <span><strong>Explain:</strong> One client can booked multiple units<strong>Total Units Sold</stromeans total unit booked by client and <strong>Total Clients</strong> means total unique clients.</span>
    </div>
    <?php if (!empty($targetUserName)): ?>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1.5" style="border-radius: 6px; font-size: 12px;">
            <i class="fa-solid fa-user-check me-1"></i> Executive: <strong><?= htmlspecialchars($targetUserName) ?></strong>
        </span>
    <?php endif; ?>
</div>

<!-- lead-select -->
<div class="row align-items-stretch">
    <div class="desktop-only">
        <form action="" method="get">
            <?php if (!empty($targetUserId) && $targetUserId != ($_SESSION['user']['id'] ?? 0)): ?>
                <input type="hidden" name="assigned_user" value="<?= (int)$targetUserId ?>">
            <?php endif; ?>
            <div class="row mb-3 align-items-end">

                <?php
                $currentYear = date('Y');
                $startYear   = $currentYear - 10;
                $selectedYear = $_GET['year'] ?? '';
                ?>
                <!-- Year Select -->
                <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-12">
                    <div class="card__wrapper h-100">
                        <div class="from__input-box">
                            <select class="form-control" name="year">
                                <option value="">Year Select</option>

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
                <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-12">
                    <div class="card__wrapper h-100">
                        <div class="from__input-box">
                            <select class="form-control" name="month">
                                <option value="">Month Select</option>
                                <?php foreach ($months as $id => $name): ?>

                                    <option value="<?= $id ?>" <?= $selectedMonth == $id ? 'selected' : '' ?>>
                                        <?= $name ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                        </div>
                    </div>
                </div>
                <!-- Buttons -->
                <div class="col-xxl-4 col-xl-12 col-lg-12 col-md-12 col-12">
                    <div class="card__wrapper h-100">
                        <div class="d-flex flex-column flex-sm-row gap-2">
                            <button type="submit" class="btn btn-secondary w-100 ">
                                Search Client
                            </button>
                            <a href="<?= $GLOBALS['base_url'] ?>/my-onboarding-projects<?= (!empty($targetUserId) && $targetUserId != ($_SESSION['user']['id'] ?? 0)) ? '?assigned_user=' . (int)$targetUserId : '' ?>" class="btn btn-success w-100">
                                Reset
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <div class="col-xxl-12">
            <div class="card__wrapper">
                <div class="table__wrapper table-responsive">
                    <table class="table mb-20 multiple_tables">
                        <thead>
                            <tr class="table__title table__sort">
                                <th style="min-width:60px;">S/L</th>
                                <th>Booking Date</th>
                                <th>Client Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Project</th>
                                <th>Unit / Share</th>
                                <th>Share Price</th>
                                <th>Booking Money</th>
                                <th>Registration Money</th>
                                <th>Total Paid</th>
                            </tr>
                        </thead>

                        <tbody class="table__body">
                            <?php if (!empty($projects)): ?>
                                <?php foreach ($projects as $key => $lead): ?>
                                    <tr>
                                        <td><?= $key + 1 ?></td>

                                        <td>
                                            <?= !empty($lead['booking_pay_date'])
                                                ? formatDate($lead['booking_pay_date'])
                                                : '-' ?>
                                        </td>

                                        <td class="fw-semibold">
                                            <?= esc($lead['full_name'] ?? '-') ?>
                                        </td>

                                        <td><?= esc($lead['phone'] ?? '-') ?></td>
                                        <td><?= esc($lead['email'] ?? '-') ?></td>

                                        <td>
                                            <?= esc($lead['project_name'] ?? '-') ?>
                                        </td>

                                        <td>
                                            <?= esc($lead['flat_name'] ?? '-') ?>
                                            <?php if (!empty($lead['sqft'])): ?>
                                                <small class="text-muted"> - <?= esc($lead['sqft']) ?> sqft</small>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?= !empty($lead['price_value'])
                                                ? format_bdt($lead['price_value'])
                                                : '-' ?>
                                        </td>

                                        <td class="fw-semibold text-primary">
                                            <?= !empty($lead['booking_money'])
                                                ? format_bdt($lead['booking_money'])
                                                : '-' ?>
                                        </td>

                                        <td class="fw-semibold text-info">
                                            <?= !empty($lead['registration_money'])
                                                ? format_bdt($lead['registration_money'])
                                                : '-' ?>
                                        </td>

                                        <td class="fw-semibold text-success">
                                            <?= !empty($lead['amount'])
                                                ? format_bdt($lead['amount'])
                                                : '-' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                           
                           
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="mobile-only">
        <!-- Filter & Sort Button -->

        <!-- Filter Toggle Button -->
        <button class="filter-button w-100 mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#mobileFilterCollapse" aria-expanded="false" aria-controls="mobileFilterCollapse">
            <i class="bi bi-funnel"></i> Filter & Sort
        </button>

        <!-- Collapsible Filter Section -->
        <div class="collapse" id="mobileFilterCollapse">
            <form action="" method="get" class="p-2 border rounded ">
                <div class="d-flex flex-column gap-2">

                    <!-- Year Select -->
                    <?php
                    $currentYear = date('Y');
                    $startYear   = $currentYear - 10;
                    $selectedYear = $slectedYear ?? '';
                    ?>
                    <select class="form-control form-select-sm" name="year">
                        <option value="">Year Select</option>
                        <?php for ($year = $currentYear; $year >= $startYear; $year--): ?>
                            <option value="<?= $year ?>" <?= $selectedYear == $year ? 'selected' : '' ?>><?= $year ?></option>
                        <?php endfor; ?>
                    </select>

                    <!-- Month Select -->
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
                    <select class="form-control form-select-sm" name="month">
                        <option value="">Month Select</option>
                        <?php foreach ($months as $id => $name): ?>
                            <option value="<?= $id ?>" <?= $selectedMonth == $id ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>



                    <!-- Action Buttons -->
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <button type="submit" class="btn btn-outline-secondary btn-sm flex-grow-1">Search</button>
                        <a href="<?= $GLOBALS['base_url'] ?>/lead-lists" class="btn btn-primary btn-sm flex-grow-1">Reset</a>

                    </div>

                </div>
            </form>
        </div>

        <?php if (!empty($projects)): ?>
            <?php foreach ($projects as $key => $lead): ?>

                <div class="lead-card mb-3 p-3 border rounded shadow-sm">

                    <!-- Top Row -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>#<?= $key + 1 ?></strong>

                        <span class="text-muted small">
                            <?= !empty($lead['booking_pay_date'])
                                ? formatDate($lead['booking_pay_date'])
                                : '-' ?>
                        </span>
                    </div>

                    <!-- Client Info -->
                    <div class="mb-2">
                        <div class="fw-semibold">
                            <?= esc($lead['full_name'] ?? '-') ?>
                        </div>
                        <div class="small text-muted">
                            📞 <?= esc($lead['phone'] ?? '-') ?>
                        </div>
                        <div class="small text-muted">
                            ✉ <?= esc($lead['email'] ?? '-') ?>
                        </div>
                    </div>

                    <!-- Project Info -->
                    <div class="mb-2">
                        <div>
                            <strong>Project:</strong>
                            <?= esc($lead['project_name'] ?? '-') ?>
                        </div>

                        <div class="small">
                            <strong>Unit:</strong>
                            <?= esc($lead['flat_name'] ?? '-') ?>
                            <?php if (!empty($lead['sqft'])): ?>
                                (<?= esc($lead['sqft']) ?> sqft)
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Price Info -->
                    <div class="d-flex justify-content-between mt-2 pt-2 border-top">
                        <div>
                            <div class="small text-muted">Share Price</div>
                            <div class="fw-semibold">
                                <?= !empty($lead['price_value'])
                                    ? format_bdt($lead['price_value'])
                                    : '-' ?>
                            </div>
                        </div>

                        <div>
                            <div class="small text-muted">Booking Money</div>
                            <div class="fw-semibold text-primary">
                                <?= !empty($lead['booking_money'])
                                    ? format_bdt($lead['booking_money'])
                                    : '-' ?>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-2 pt-2 border-top">
                        <div>
                            <div class="small text-muted">Registration</div>
                            <div class="fw-semibold text-info">
                                <?= !empty($lead['registration_money'])
                                    ? format_bdt($lead['registration_money'])
                                    : '-' ?>
                            </div>
                        </div>

                        <div class="text-end">
                            <div class="small text-muted">Total Paid</div>
                            <div class="fw-semibold text-success">
                                <?= !empty($lead['amount'])
                                    ? format_bdt($lead['amount'])
                                    : '-' ?>
                            </div>
                        </div>
                    </div>

                </div>

            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center text-muted py-4">
                No onboard projects found.
            </div>
        <?php endif; ?>
    </div>
</div>
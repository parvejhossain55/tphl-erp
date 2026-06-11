<?php
$page_title = "leads Page";
$page = 'All Onboarding Clints';
$addPage = 'Add Leads';
$addPage = true;
$show_breadcrumb = true;
?>


<div class="row lead-select align-items-stretch">

    <div class="desktop-only">
    <form action="" method="get">
        <div class="row  mb-3 align-items-end">

            <?php
            $currentYear = date('Y');
            $startYear   = $currentYear - 10;
            $selectedYear = $slectedYear ?? '';
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
                        <a href="<?= $GLOBALS['base_url'] ?>/onboarding-clients" class="btn btn-success  w-100">
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


                            <th style="min-width:60px;">S/L</th> <!-- non-sticky first column -->
                            <th class="">Sales Team</th>
                            <th class="th_1">Client Name</th>
                            <th>Phone</th>
                            <!-- <th>Alt Phone</th> -->
                            <th>Email</th>
                            <th>Project</th>
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
                                    <?= $lead['sales_team'] ?? '' ?>
                                    <?= $lead['project_name'] ?>
                                </td>
                                <td>
                                    <?= $lead['flat_name'] ?> - <span><?= $lead['sqft'] ?></span>
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
        <form action="" method="get" class="p-2 border rounded">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <!-- Year Select -->
                <?php
                $currentYear = date('Y');
                $startYear = $currentYear - 10;
                $selectedYear = $slectedYear ?? '';
                ?>
                <select class="form-select form-select-sm flex-grow-1" name="year">
                    <option value="">Year</option>
                    <?php for ($year = $currentYear; $year >= $startYear; $year--): ?>
                        <option value="<?= $year ?>" <?= $selectedYear == $year ? 'selected' : '' ?>><?= $year ?></option>
                    <?php endfor; ?>
                </select>

                <!-- Month Select -->
                <?php
                $months = [
                    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                ];
                $selectedMonth = $_GET['month'] ?? '';
                ?>
                <select class="form-select form-select-sm flex-grow-1" name="month">
                    <option value="">Month</option>
                    <?php foreach ($months as $id => $name): ?>
                        <option value="<?= $id ?>" <?= $selectedMonth == $id ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Action Buttons -->
                <div class="d-flex gap-2 flex-wrap w-100">
                    <button type="submit" class="btn btn-outline-secondary btn-sm flex-grow-1">Search</button>
                    <a href="<?= $GLOBALS['base_url'] ?>/lead-lists" class="btn btn-primary btn-sm flex-grow-1">Reset</a>
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

            <!-- Project & Flat -->
            <div class="d-flex flex-column gap-1 mb-2">
                <span class="text-truncate"><i class="bi bi-building me-1"></i> <?= $lead['project_name'] ?? '-' ?></span>
                <span class="text-truncate"><i class="bi bi-house me-1"></i> <?= $lead['flat_name'] ?? '-' ?> - <?= $lead['sqft'] ?? '' ?></span>
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


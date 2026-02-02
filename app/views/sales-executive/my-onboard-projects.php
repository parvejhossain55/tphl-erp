<?php
$page_title = "Leads Page";
$page = 'My Onboarding Projects';
$show_breadcrumb = true;
?>

<div class="row lead-select align-items-stretch">
    
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
                        <a href="<?= $GLOBALS['base_url'] ?>/my-onboarding-projects" class="btn btn-success  w-100">
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
                            <th>Paid Amount</th>
                            <th class="text-center">Action</th>
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

                                    <td class="fw-semibold text-success">
                                        <?= !empty($lead['amount']) 
                                            ? format_bdt($lead['amount']) 
                                            : '-' ?>
                                    </td>

                                    <td class="text-center">
                                        <a 
                                            class="table__icon download"
                                            href="<?= $GLOBALS['base_url'] ?>/view-lead/<?= $lead['lead_id'] ?>"
                                            title="View Lead"
                                        >
                                            <i class="fa-regular fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    No onboard projects found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

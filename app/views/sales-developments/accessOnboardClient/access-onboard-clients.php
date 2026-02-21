<?php
$page_title = "All Access Onboard Clients";
$page = 'All Access Onboard Clints';
$addPage = true;
$show_breadcrumb = true;
?>


<div class="row  align-items-stretch">


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
                        <a href="<?= $GLOBALS['base_url'] ?>/onboard-access-client-lists" class="btn btn-success  w-100">
                            Reset
                        </a>
                        <a href="<?= $GLOBALS['base_url'] ?>/create-onboard-client" class="btn btn-primary  w-100">
                            Add Access client
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
                            <th class="th_1">Client Name</th>
                            <th>Phone</th>
                            <!-- <th>Alt Phone</th> -->
                            <th>Email</th>
                            <th>Project</th>
                            <th>Unit/ Share</th>
                            <th>Booking amount pay</th>

                            <th class="text-center ">Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">

                        <?php foreach ($onboardAccessClients as $key => $client): ?>
                            <tr>
                                <td><?= $key + 1 ?></td>

                                <td class="td_1"><?= $client['client_name'] ?? '' ?></td>
                                <td><?= $client['client_phone'] ?? '' ?></td>
                                <td><?= $client['email'] ?? '' ?></td>

                                <td><?= $client['project_names'] ?? '' ?></td>
                                <td>
                                    <?= $client['flat_names'] ?? '' ?>
                                </td>

                                <td><?= format_bdt($client['total_paid']) ?></td>

                                <td class="">
                                    <div class="d-flex align-items-center justify-content-start gap-10">

                                        <!-- View Button (Existing) -->
                                        <a class="table__icon download" href="<?= $GLOBALS['base_url'] ?>/view-lead/<?= $client['lead_id'] ?>" title="View Lead">
                                            <i class="fa-regular fa-eye"></i>
                                        </a>
                                        <a class="table__icon edit" href="<?= $GLOBALS['base_url'] ?>/edit-onboard-client/<?= $client['lead_id'] ?>" title="Edit Lead">
                                            <i class="fa-sharp fa-light fa-pen"></i>
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
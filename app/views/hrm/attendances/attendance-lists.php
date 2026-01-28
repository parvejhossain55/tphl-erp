<?php
$page_title = "Attendence Page";
$page = 'Attendence';
$show_breadcrumb = true;

// include "../layouts/master.php";
?>


<!-- DATE FILTER -->


<form method="GET" class="mb-20">
    <div class="card__wrapper">
        <div class="row  align-items-end">

            <!-- Date -->
            <div class="col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-12">
                <div class="from__input-box">
                    <label class="form-label">Date</label>
                    <input type="date"
                        name="date"
                        class="form-control"
                        value="<?= $_GET['date'] ?? date('Y-m-d') ?>">
                </div>
            </div>

            <!-- Buttons -->
            <div class="col-xxl-9 col-xl-9 col-lg-8 col-md-6 col-12">
                <div class="d-flex flex-wrap align-items-center gap-2">

                    <button type="submit" class="btn btn-primary btn-sm px-5">
                        <i class="bi bi-search me-1"></i> Search
                    </button>

                    <a href="<?= $GLOBALS['base_url'] ?>/create-attendance"
                        class="btn btn-success btn-sm px-5">
                        <i class="bi bi-plus-circle me-1"></i> Add
                    </a>

                    <a href="<?= $GLOBALS['base_url'] ?>/download-excel?date=<?= $_GET['date'] ?? date('Y-m-d') ?>"
                        class="btn btn-success btn-sm px-5">
                        <i class="fa-solid fa-file-excel me-1"></i> Excel
                    </a>

                    <a href="<?= $GLOBALS['base_url'] ?>/download-pdf?date=<?= $_GET['date'] ?? date('Y-m-d') ?>"
                        class="btn btn-danger btn-sm px-5">
                        <i class="fa-solid fa-file-pdf me-1"></i> PDF
                    </a>

                    <a href="<?= $GLOBALS['base_url'] ?>/attendance-list"
                        class="btn btn-outline-secondary btn-sm px-3 ms-auto">
                        <i class="bi bi-arrow-clockwise me-1"></i> Reset
                    </a>

                </div>
            </div>

        </div>
    </div>
</form>



<div class="row">
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-sharp fa-regular fa-gear"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Employee</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h3 class="card__title"><?= $totalEmployee ?></h3>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-light fa-badge-check"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Present</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h3 class="card__title">
                            <p><?= $todaySummary['Present'] ?></p>
                        </h3>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-sharp fa-regular fa-user"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Half Day</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h3 class="card__title">
                            <p><?= $todaySummary['Half-Day'] ?></p>
                        </h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-sharp fa-regular fa-house-person-leave"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">On Leave Employee</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h3 class="card__title">
                            <p><?= $todaySummary['Absent'] ?></p>
                        </h3>

                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- table  -->
<div class="row">

    <h6 class="card__sub-title mb-15">
        Attendance – <?= date('d M Y', strtotime($selectedDate)) ?>
    </h6>
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="row">
                <div class="col-xxl-12">
                    <div class="table__wrapper table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Punch In</th>
                                    <th>Punch Out</th>
                                    <th class="d-none">Hours</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                                foreach ($attendances as $row):

                                    $punchIn  = ($row['punch_in_time'] && $row['punch_in_time'] != '0000-00-00 00:00:00')
                                        ? date('H:i', strtotime($row['punch_in_time'])) : '-';

                                    $punchOut = ($row['punch_out_time'] && $row['punch_out_time'] != '0000-00-00 00:00:00')
                                        ? date('H:i', strtotime($row['punch_out_time'])) : '-';

                                    $statusClass = match ($row['today_status']) {
                                        'Present'  => 'bg-success',
                                        'Leave'    => 'bg-warning text-dark',
                                        'Half Day' => 'bg-info',
                                        default    => 'bg-secondary',
                                    };
                                ?>
                                    <tr>
                                        <td><?= $i++ ?></td>
                                        <td><strong><?= $row['employee_name'] ?></strong></td>
                                        <td><strong><?= $row['department_name'] ?? 'N/A' ?></strong></td>
                                        <td><span class="badge <?= $statusClass ?>"><?= $row['today_status'] ?></span></td>
                                        <td><?= $punchIn ?></td>
                                        <td><?= $punchOut ?></td>
                                        <td class="d-none"><?= $row['working_hours'] ?? '-' ?></td>
                                        <!-- Actions -->
                                        <td>
                                            <div class="d-flex gap-2 justify-content-center">
                                                <a class="table__icon download"
                                                    href="<?= $GLOBALS['base_url'] ?>/show-attendance/<?= $row['employee_id'] ?>"
                                                    title="View attendance">
                                                    <i class="fa-regular fa-eye"></i>
                                                </a>

                                                <a class="table__icon edit"
                                                    href="<?= $GLOBALS['base_url'] ?>/edit-attendance/<?= $row['id'] ?>"
                                                    title="Edit attendance">
                                                    <i class="fa-sharp fa-light fa-pen"></i>
                                                </a>

                                                <button class="table__icon delete removeBtnCust"
                                                    data-name="<?= $row['employee_name'] ?>"
                                                    data-action="delete-attendance"
                                                    data-id="<?= $row['id'] ?>"
                                                    title="Delete attendance">
                                                    <i class="fa-regular fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
<script>
    const base_url = "<?= $GLOBALS['base_url'] ?>"
</script>
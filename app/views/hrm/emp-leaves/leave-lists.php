<?php
$page_title = "leave Page";
$page = 'Employee Leave';
$addPage = true;
$show_breadcrumb = true;

?>
<div class="breadcrumb__btn my-3">
    <a href="<?= $GLOBALS['base_url'] ?>/create-employee-leave" class="btn btn-primary">Add Leave</a>
</div>


<div class="row">
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6">
        <div class="card__wrapper">
            <div class="d-flex align-items-center gap-sm">
                <div class="card__icon">
                    <span><i class="fa-light fa-ban"></i></span>
                </div>
                <div class="card__title-wrap">
                    <h6 class="card__sub-title mb-10">Total Leave</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h3 class="card__title"></h3>
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
                    <h6 class="card__sub-title mb-10">Approve</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h3 class="card__title">2</h3>
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
                    <h6 class="card__sub-title mb-10">Rejected </h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h3 class="card__title">2</h3>
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
                    <h6 class="card__sub-title mb-10">Pending</h6>
                    <div class="d-flex flex-wrap align-items-end gap-10">
                        <h3 class="card__title">1</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20" id="dataTableDefualt">
                    <thead>
                        <tr class="table__title">
                            <th>Employee Name</th>
                            <th>Leave Type</th>
                            <th>Leave Duration</th>
                            <th>Days</th>
                            <th>Approved Days</th>
                            <th>Remaining Leave</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th class="d-none">Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php foreach ($leaveData as $row):
                            // Days calculation
                            $start = new DateTime($row['start_date']);
                            $end = new DateTime($row['end_date']);
                            $days = $start->diff($end)->days + 1; 
                            $remaining_days = $row['total_days'] - $row['approved_days'];
                         
                        ?>
                            <tr>
                                <td class="table__leave-name"><?= htmlspecialchars($row['employee_name']) ?></td>
                                <td class="table__leave-type"><?= htmlspecialchars($row['leave_type_name']) ?></td>
                                <td class="table__leave-duration"><?= formatDate($row['start_date']) ?> - <?= formatDate($row['end_date']) ?></td>
                                <td class="table__leave-days"><?= $days ?></td>
                                <td class="table__leave-approved">
                                    <?= ($row['approved_days'] ?? 0) > 0 ? $row['approved_days'] : 'Not Yet' ?>
                                </td>
                                <td><?= $remaining_days ?? 0 ?></td>
                                <td class="table__leave-reason">
                                    <?= htmlspecialchars($row['reason'] ?? 'N/A') ?>
                                </td>

                                <td >
                                    <?php
                                    //  STATUS OVERRIDE LOGIC
                                    if (($row['approved_days'] ?? 0) > 0) {
                                        $finalStatus = 'approved';
                                    } else {
                                        $finalStatus = $row['status'] ?? 'pending';
                                    }

                                    $statusClass = match ($finalStatus) {
                                        'approved' => 'bg-success',
                                        'rejected' => 'bg-danger',
                                        default => 'bg-warning',
                                    };

                                    $statusText = ucfirst($finalStatus);

                                    ?>
                                    <span class="bd-badge <?= $statusClass ?>">
                                        <?= $statusText ?>
                                    </span>
                                </td>

                                <td class="table__icon-box d-none">
                                    <div class="d-flex align-items-center justify-content-start gap-10">
                                        <a href="#" class="table__icon edit">
                                            <i class="fa-sharp fa-light fa-pen"></i>
                                        </a>
                                        <button class="removeBtn table__icon delete">
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
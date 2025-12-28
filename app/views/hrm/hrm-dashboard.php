<div class="app__slide-wrapper p-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">HR Dashboard</h4>
            <strong class="text-muted">Human Resources</strong>
        </div>
        
    </div>

    <!--  CARDS -->
    <div class="row g-20 mb-4">
        <div class="col-xxl-3 col-xl-6 col-md-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon"><span><i class="fa-regular fa-users"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Total Employees</h6>
                        <h3 class="card__title"><?= $totalEmployees ?? '' ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-6 col-md-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon"><span><i class="fa-regular fa-user-check"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Present Today</h6>
                        <h3 class="card__title">142</h3>
                        <small>91% attendance</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-6 col-md-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon"><span><i class="fa-regular fa-bed"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">On Leave</h6>
                        <h3 class="card__title">8</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-6 col-md-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon"><span><i class="fa-solid fa-bangladeshi-taka-sign"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Payroll </h6>
                        <h3 class="card__title">BDT 42.5L</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-20 mb-4">

        <!-- Today Assigned Tasks -->
        <div class="col-xxl-3 col-xl-6 col-md-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon">
                        <span><i class="fa-regular fa-clipboard-list"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Today Assigned Tasks</h6>
                        <h3 class="card__title"><?= $todayTasks ?? 0 ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Tasks -->
        <div class="col-xxl-3 col-xl-6 col-md-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon">
                        <span><i class="fa-regular fa-hourglass-half"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Pending Tasks</h6>
                        <h3 class="card__title"><?= $pendingTasks ?? 0 ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Completed Tasks -->
        <div class="col-xxl-3 col-xl-6 col-md-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon">
                        <span><i class="fa-regular fa-circle-check"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Completed Tasks</h6>
                        <h3 class="card__title"><?= $completedTasks ?? 0 ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overdue Tasks -->
        <div class="col-xxl-3 col-xl-6 col-md-6">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon">
                        <span><i class="fa-solid fa-triangle-exclamation"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Overdue Tasks</h6>
                        <h3 class="card__title"><?= $overdueTasks ?? 0 ?></h3>
                    </div>
                </div>
            </div>
        </div>

    </div>



    <!-- TODAY'S ATTENDANCE -->
    <div class="row g-20 mb-4 d-none">
        <div class="col-xl-12">
            <div class="card__wrapper">
                <h6 class="card__sub-title mb-15">Today's Attendance</h6>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Hours</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Ananya Das</td>
                                <td>Sales</td>
                                <td><span class="badge bg-success">Present</span></td>
                                <td>8h 30m</td>
                            </tr>
                            <tr>
                                <td>Rahul Verma</td>
                                <td>Sales</td>
                                <td><span class="badge bg-success">Present</span></td>
                                <td>7h 45m</td>
                            </tr>
                            <tr>
                                <td>Amit Patel</td>
                                <td>Operations</td>
                                <td><span class="badge bg-success">Present</span></td>
                                <td>9h 15m</td>
                            </tr>
                            <tr>
                                <td>Sneha Reddy</td>
                                <td>HR</td>
                                <td><span class="badge bg-warning text-dark">On Leave</span></td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>Karan Shah</td>
                                <td>Sales</td>
                                <td><span class="badge bg-success">Present</span></td>
                                <td>8h 00m</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- PENDING LEAVE REQUESTS -->
    <div class="row g-20 mb-4 d-none">
        <div class="col-xl-12">
            <div class="card__wrapper">
                <h6 class="card__sub-title mb-15">Pending Leave Requests</h6>
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Meera Iyer</strong><br>
                            <small>Sick Leave • 2 days</small>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-success me-1">Approve</button>
                            <button class="btn btn-sm btn-danger">Reject</button>
                        </div>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Vikram Singh</strong><br>
                            <small>Casual Leave • 1 day</small>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-success me-1">Approved</button>
                        </div>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Priya Menon</strong><br>
                            <small>Annual Leave • 5 days</small>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-success me-1">Approve</button>
                            <button class="btn btn-sm btn-danger">Reject</button>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
<?php
$maxEmployee = max(array_column($departmentEmpCount, 'total_employee'));
?>

<div class="row g-20 mb-4">
    <div class="col-xl-12">
        <div class="card__wrapper">
            <h6 class="card__sub-title mb-15">Department-wise Employee</h6>

            <?php foreach ($departmentEmpCount as $dept):
                $percent = $maxEmployee > 0
                    ? round(($dept['total_employee'] / $maxEmployee) * 100)
                    : 0;
            ?>
                <div class="mb-2">
                    <?= htmlspecialchars($dept['dept_name']) ?>
                    <span class="float-end"><?= $dept['total_employee'] ?></span>
                </div>

                <div class="progress mb-3">
                    <div class="progress-bar bg-primary" style="width:<?= $percent ?>%"></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>


    <!-- DEPARTMENT WISE STAFF -->


</div>
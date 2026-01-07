<div class="app__slide-wrapper">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">HR Dashboard</h4>
            <strong class="text-muted">Human Resources</strong>
        </div>
        
    </div>

    <!--  CARDS -->
    <div class="row g-20 mb-4">
        <div class="col-xxl-4 col-xl-4 col-md-6 col-sm-12 col-12">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon"><span><i class="fa-regular fa-users"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Total Employees</h6>
                        <h4 class="card__title text-break text-success mb-10"><?= $totalEmployees ?? '' ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-none col-xxl-3 col-xl-6 col-md-6 col-sm-12">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon"><span><i class="fa-regular fa-user-check"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Present Today</h6>
                        <h4 class="card__title text-break text-success mb-10">142</h4>
                        <small>91% attendance</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-none col-xxl-3 col-xl-6 col-md-6 col-sm-12">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon"><span><i class="fa-regular fa-bed"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">On Leave</h6>
                        <h4 class="card__title text-break text-success mb-10">8</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4 col-xl-4 col-md-6 col-sm-12 col-12">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon"><span><i class="fa-solid fa-bangladeshi-taka-sign"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Salary Paid </h6>
                        <h4 class="card__title text-break text-success mb-10"> &#2547; <?= $totalPaidSalary ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-4 col-xl-4 col-md-6 col-sm-12 col-12">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon"><span><i class="fa-solid fa-bangladeshi-taka-sign"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Unpaid Salary  </h6>
                        <h4 class="card__title text-break text-success mb-10"> &#2547; <?= $totalUnpaidSalary ?></h4>
                    </div>
                </div>
            </div>
        </div>



        <!-- Today Assigned Tasks -->
        <div class="col-xxl-4 col-xl-3 col-md-6 col-sm-12 col-12">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon">
                        <span><i class="fa-regular fa-clipboard-list"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Today Assigned Tasks</h6>
                        <h4 class="card__title text-break text-success mb-10"><?= $todayTasks ?? 0 ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Tasks -->
        <div class="col-xxl-4 col-xl-3 col-md-6 col-sm-12 col-12">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon">
                        <span><i class="fa-regular fa-hourglass-half"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Pending Tasks</h6>
                        <h4 class="card__title text-break text-success mb-10"><?= $pendingTasks ?? 0 ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Completed Tasks -->
        <div class="col-xxl-4 col-xl-3 col-md-6 col-sm-12 col-12">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon">
                        <span><i class="fa-regular fa-circle-check"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Completed Tasks</h6>
                        <h4 class="card__title text-break text-success mb-10"><?= $completedTasks ?? 0 ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overdue Tasks -->
        <div class="col-xxl-3 d-none col-xl-3 col-md-6 col-sm-12 col-12">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon">
                        <span><i class="fa-solid fa-triangle-exclamation"></i></span>
                    </div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Overdue Tasks</h6>
                        <h4 class="card__title text-break text-success mb-10"><?= $overdueTasks ?? 0 ?></h4>
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



  <!-- Department Wise Employees Card -->
<div class="col-xxl-12 col-12">
    <div class="card-bg card-border mb-20 h-100">
        <div class="card__wrapper style_two h-100 d-flex flex-column">

            <!-- Card Header -->
            <div class="card__title-wrap d-flex align-items-center justify-content-between">
                <h5 class="card__heading-title">Department Wise Employees</h5>
            </div>

            <!-- Card Body -->
            <div class="card__content flex-grow-1 d-flex align-items-center justify-content-center">
             <div id="deptPieChart" style="width:100%; height:<?= $chartHeight ?>px;"></div>
            </div>

        </div>
    </div>
</div>


</div>
<!-- ApexCharts Script -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const labels = <?= json_encode($deptLabels) ?>;
    const series = <?= json_encode($deptSeries) ?>;

    var options = {
        series: series,
        chart: { type: 'pie', height: <?= $chartHeight ?> },
        labels: labels,
        legend: { position: 'right', labels: { colors: 'rgb(178,185,187)' } },
        tooltip: {
            y: { formatter: function(value) { return value + " Employees"; } }
        },
        responsive: [{
            breakpoint: 768,
            options: { legend: { position: 'bottom' } }
        }]
    };

    new ApexCharts(document.querySelector("#deptPieChart"), options).render();
});
</script>
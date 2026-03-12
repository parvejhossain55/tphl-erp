<div class="app__slide-wrapper">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">HR Dashboard</h4>
            <strong class="text-muted">Human Resources</strong>
        </div>

    </div>
    <?php if (!empty($birthdays)): ?>
        <div class="birthday-notification-wrapper">
            <div class="birthday-card">
                <div class="cake-icon">
                    <div class="flame"></div>
                </div>
                <div class="birthday-content">
                    <span class="birthday-label">Birthday Today</span>
                    <ul class="birthday-names">
                        <?php foreach ($birthdays as $b): ?>
                            <li class="birthday-name">
                                <?= htmlspecialchars($b['full_name']) ?>
                                Department: <span><?= htmlspecialchars($b['dept_name']) ?>
                                </span></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="confetti-dots">
                    <span></span><span></span><span></span>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php
    // year & month from GET or current
    $filterYear  = $_GET['year']  ?? date('Y');
    $filterMonth = $_GET['month'] ?? date('n');

    // month name array
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

    // final label text
    $filterLabel = $months[(int)$filterMonth] . ' ' . $filterYear;
    ?>

    <form method="get" class="row g-2 mb-3">

        <div class="col-md-3 col-6">
            <select name="year" class="form-control">
                <?php
                $currentYear = date('Y');
                for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
                    $selected = ($_GET['year'] ?? $currentYear) == $y ? 'selected' : '';
                    echo "<option value='$y' $selected>$y</option>";
                }
                ?>
            </select>
        </div>

        <div class="col-md-3 col-6">
            <select name="month" class="form-control">
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
                $selectedMonth = $_GET['month'] ?? date('n');

                foreach ($months as $num => $name) {
                    $selected = $selectedMonth == $num ? 'selected' : '';
                    echo "<option value='$num' $selected>$name</option>";
                }
                ?>
            </select>
        </div>

        <div class="col-md-6 col-12">
            <div class="d-flex flex-column flex-sm-row gap-2">
                <button class="btn btn-secondary w-100">Filter Monthly Salary</button>
                <button type="reset" class="btn btn-success w-100">
                    Reset
                </button>
            </div>
        </div>

    </form>

    <!--  CARDS -->
    <div class="row align-items-stretch">
        <div class="col-xxl-3 col-xl-3 col-md-6 col-sm-12 col-12">
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

        <div class=" col-xxl-3 col-xl-3 col-md-6 col-sm-12">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon"><span><i class="fa-regular fa-user-check"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">
                            Today Present</h6>
                        <h4 class="card__title text-break text-success mb-10"><?= $presentCount ?? '' ?></h4>
                        <small><?= $persentPresents ?>% attendance</small>
                    </div>
                </div>
            </div>
        </div>


        <div class=" col-xxl-3 col-xl-6 col-md-6 col-sm-12">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon"><span><i class="fa-regular fa-bed"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">On Leave</h6>
                        <h4 class="card__title text-break text-success mb-10"><?= $absentCount ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class=" col-xxl-3 col-xl-6 col-md-6 col-sm-12">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon"><span><i class="fa-regular fa-bed"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Today Absent %</h6>
                        <h4 class="card__title text-break text-success mb-10"><?= $persentAbsents ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class=" col-xxl-3 col-xl-6 col-md-6 col-sm-12">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon"><span><i class="fa-regular fa-clock"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Today Late</h6>
                        <h4 class="card__title text-break text-success mb-10"><?= $lateCount ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class=" col-xxl-3 col-xl-6 col-md-6 col-sm-12">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon"><span><i class="fa-regular fa-clock"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Today Late</h6>
                        <h4 class="card__title text-break text-success mb-10"><?= $latePercent ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-3 col-md-6 col-sm-12 col-12">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon"><span><i class="fa-solid fa-bangladeshi-taka-sign"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Salary Paid (<?= $filterLabel ?>) </h6>
                        <h4 class="card__title text-break text-success mb-10"> &#2547; <?= $totalPaidSalary ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-xl-3 col-md-6 col-sm-12 col-12">
            <div class="card__wrapper">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon"><span><i class="fa-solid fa-bangladeshi-taka-sign"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Unpaid Salary (<?= $filterLabel ?>) </h6>
                        <h4 class="card__title text-break text-success mb-10"> &#2547; <?= $totalUnpaidSalary ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today Assigned Tasks -->
        <div class="d-none col-xxl-4 col-xl-3 col-md-6 col-sm-12 col-12">
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
        <div class="d-none col-xxl-4 col-xl-3 col-md-6 col-sm-12 col-12">
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
        <div class="d-none col-xxl-4 col-xl-3 col-md-6 col-sm-12 col-12">
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
    <div class="row g-20 mb-4 ">
        <div class="col-xl-12">
            <div class="card__wrapper">
                <h6 class="card__sub-title mb-15">Today's Attendance</h6>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>S/L</th>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Panch IN</th>
                                <th>Panch Out</th>
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
                                    <!-- Actions -->

                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xxl-6 col-xl-12 col-lg-12 col-12 mb-3">
            <div class="card__wrapper height-equal">
                <div class="card__title-wrap d-flex flex-wrap gap-10 align-items-center justify-content-between mb-25">
                    <h5 class="card__heading-title">Employee Attendance Overview</h5>

                    <div class="card__tab">
                        <ul class="nav nav-pills">
                            <li class="nav-item">
                                <button class="nav-link active" data-period="month">1M</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-period="week">1W</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-period="year">1Y</button>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card__content">
                    <div id="attendanceChart"></div>
                </div>
            </div>
        </div>


        <!-- Department Wise Employees Card -->
        <div class="col-xxl-6 col-12 mb-3">
            <div class="card-bg card-border mb-20 h-100">
                <div class="card__wrapper style_two h-100 d-flex flex-column height-equal">

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


<div  id="calendar-container">


    <div class="row mb-4 align-items-strech">
        <?php
        $month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
        $year  = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
        ?>
        <!-- ══ CALENDAR ══ -->
        <div class="col-lg-7 mb-3 mb-lg-0 d-flex">
            <div class="card__wrapper">
    
                <!-- Header -->
             <div class="cal-header">
        <div class="cal-title"><?= $monthName ?> <?= $year ?></div>
    
        <div class="cal-nav">
            <button class="cal-btn cal-nav-btn" data-dir="prev">
                <i class="fas fa-chevron-left"></i>
            </button>
    
            <button class="cal-btn cal-today"
                style="font-size:.65rem;font-weight:700;width:auto;padding:0 10px;">
                Today
            </button>
    
            <button class="cal-btn cal-nav-btn" data-dir="next">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
    
                <div class="cal-grid">
    
                    <!-- Day labels -->
                    <?php foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dayLabel): ?>
                        <div class="cal-day-label"><?= $dayLabel ?></div>
                    <?php endforeach; ?>
    
                    <!-- Empty cells before month starts -->
                    <?php for ($i = 0; $i < $firstDayOfWeek; $i++): ?>
                        <div class="cal-cell other-month"></div>
                    <?php endfor; ?>
    
                    <!-- Days of the month -->
                    <?php for ($day = 1; $day <= $daysInMonth; $day++):
                        $dateStr = sprintf("%04d-%02d-%02d", $year, $month, $day);
                        $classes = "cal-cell";
    
                        // Mark today
                        if ($dateStr == $today) $classes .= " today";
    
                        // Weekly holiday = Friday
                        $weekDay = date('w', strtotime($dateStr)); // 0=Sun .. 5=Fri
                        if ($weekDay == 5) $classes .= " weekend";
    
                        // Government holidays
                        if (isset($holidays[$day])) $classes .= " holiday";
                    ?>
                        <div class="<?= $classes ?>" title="<?= $holidays[$day] ?? '' ?>"><?= $day ?></div>
                    <?php endfor; ?>
    
                    <!-- Fill remaining cells to complete last week -->
                    <?php
                    $totalCells = $firstDayOfWeek + $daysInMonth;
                    $remaining = 7 - ($totalCells % 7);
                    if ($remaining < 7) {
                        for ($i = 0; $i < $remaining; $i++): ?>
                            <div class="cal-cell other-month"></div>
                    <?php endfor;
                    }
                    ?>
                </div><!-- /cal-grid -->
    
                <!-- Legend -->
                <div class="legend">
                    <div class="legend-item">
                        <div class="legend-dot" style="background:var(--today-bg)"></div>Today
                    </div>
                    <div class="legend-item">
                        <div class="legend-dot" style="background:var(--holiday)"></div>Government Holiday
                    </div>
                    <div class="legend-item">
                        <div class="legend-dot" style="background:var(--weekend)"></div>Weekly Holiday
                    </div>
                </div>
    
            </div>
        </div><!-- /col-lg-7 -->
    
        <!-- ══ HOLIDAY LIST ══ -->
        <div class="col-lg-5 d-flex">
            <div class="card__wrapper">
    
                <div class="section-title-holiday">
                    Government Holidays in 2026 <span><?= $holidayCount ?></span>
                </div>
    
                <ul class="holiday-list">
                    <?php foreach ($holidaysResult as $h):
                        $day = date('d', strtotime($h['holiday_date']));
                        $monthShort = date('M', strtotime($h['holiday_date']));
                    ?>
                        <li class="holiday-item">
                            <div class="holiday-date-box">
                                <div class="day"><?= $day ?></div>
                                <div class="month"><?= $monthShort ?></div>
                            </div>
                            <div class="holiday-info">
                                <strong><?= htmlspecialchars($h['title']) ?></strong>
                                <?php if (!empty($h['description'])): ?>
                                    <small><?= htmlspecialchars($h['description']) ?></small>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul><!-- /holiday-list -->
    
            </div>
        </div><!-- /col-lg-5 -->
    
    </div><!-- /row -->
</div>




    <!-- PENDING LEAVE REQUESTS -->
    <div class="row g-20 mb-4 ">
        <div class="col-xl-12">

            <div class="card__body">
                <div class="approval-scrollable" style="max-height: 500px; overflow-y:auto; padding:1rem;">

                    <?php if (!empty($pendingRequests)): ?>
                        <?php foreach ($pendingRequests as $req): ?>

                            <div class="border rounded p-3 mb-3 shadow-sm">

                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($req['department']) ?> Department</h6>
                                        <small class="text-muted d-block"><?= htmlspecialchars($req['title']) ?></small>
                                        <small class="text-muted">
                                            Requested: <?= date('d M Y', strtotime($req['created_at'])) ?>
                                        </small>
                                    </div>

                                    <span class="badge bg-warning text-dark">
                                        <?= ucfirst($req['status']) ?>
                                    </span>
                                </div>

                                <!-- Leave Meta -->
                                <div class="mb-2">
                                    <?php if ($req['source_table'] === 'employee_leaves'): ?>
                                        <?php
                                        $meta = json_decode($req['meta_data'], true);
                                        $requested_days = $meta['requested_days'] ?? 0;
                                        ?>
                                        <strong>Requested Days:</strong> <?= $requested_days ?>
                                    <?php endif; ?>
                                </div>

                                <?php renderApprovalContext($req); ?>

                                <!-- Buttons -->
                                <div class="d-flex gap-2 flex-wrap">

                                    <!-- Full Approve -->
                                    <form method="POST"
                                        action="<?= $GLOBALS['base_url'] ?>/approve-request/<?= $req['id'] ?>">
                                        <button class="btn btn-sm btn-success">
                                            Full Approve
                                        </button>
                                    </form>

                                    <!-- Partial -->
                                    <button class="btn btn-sm btn-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#partialApproveModal<?= $req['id'] ?>">
                                        Partial Approve
                                    </button>

                                    <!-- Reject -->
                                    <form method="POST"
                                        action="<?= $GLOBALS['base_url'] ?>/reject-request/<?= $req['id'] ?>">
                                        <button class="btn btn-sm btn-danger">
                                            Reject
                                        </button>
                                    </form>

                                </div>

                            </div>

                            <!-- ================= Partial Modal ================= -->
                            <div class="modal fade"
                                id="partialApproveModal<?= $req['id'] ?>"
                                tabindex="-1">

                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">

                                        <div class="modal-header">
                                            <h6 class="modal-title">Partial Approval</h6>
                                            <button type="button"
                                                class="btn-close"
                                                data-bs-dismiss="modal"></button>
                                        </div>

                                        <div class="modal-body">

                                            <form method="POST"
                                                action="<?= $GLOBALS['base_url'] ?>/partial-approve-request/<?= $req['id'] ?>">

                                                <?php if ($req['source_table'] === 'employee_leaves'): ?>
                                                    <?php
                                                    $meta = json_decode($req['meta_data'], true);
                                                    $requested_days = $meta['requested_days'] ?? 0;
                                                    ?>

                                                    <div class="mb-3">
                                                        <label class="form-label">Requested Days</label>
                                                        <input type="number"
                                                            class="form-control"
                                                            value="<?= $requested_days ?>"
                                                            disabled>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Approve Days</label>
                                                        <input type="number"
                                                            class="form-control"
                                                            name="approved_days"
                                                            required
                                                            max="<?= $requested_days ?>">
                                                    </div>
                                                <?php endif; ?>

                                                <div class="modal-footer">
                                                    <button class="btn btn-secondary"
                                                        data-bs-dismiss="modal">
                                                        Cancel
                                                    </button>

                                                    <button class="btn btn-success">
                                                        Approve
                                                    </button>
                                                </div>

                                            </form>

                                        </div>
                                    </div>
                                </div>
                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>
                        <p class="text-muted">No pending requests</p>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
    <?php
    $maxEmployee = max(array_column($departmentEmpCount, 'total_employee'));
    ?>



    <!-- Department Wise Employees Card -->
    <div class="d-none col-xxl-12 col-12">
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
    document.addEventListener("DOMContentLoaded", function() {
        const labels = <?= json_encode($deptLabels) ?>;
        const series = <?= json_encode($deptSeries) ?>;

        var options = {
            series: series,
            chart: {
                type: 'pie',
                height: <?= $chartHeight ?>
            },
            labels: labels,
            legend: {
                position: 'right',
                labels: {
                    colors: 'rgb(178,185,187)'
                }
            },
            tooltip: {
                y: {
                    formatter: function(value) {
                        return value + " Employees";
                    }
                }
            },
            responsive: [{
                breakpoint: 768,
                options: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }]
        };

        new ApexCharts(document.querySelector("#deptPieChart"), options).render();
    });
</script>
<script>
    document.addEventListener("DOMContentLoaded", function() {

        const attendanceData = {
            week: {
                labels: <?= json_encode(array_column($attendanceChart['week'], 'employee')) ?>,
                totals: <?= json_encode(array_column($attendanceChart['week'], 'present')) ?>,
                max: 5
            },
            month: {
                labels: <?= json_encode(array_column($attendanceChart['month'], 'employee')) ?>,
                totals: <?= json_encode(array_column($attendanceChart['month'], 'present')) ?>,
                max: 26
            },
            year: {
                labels: <?= json_encode(array_column($attendanceChart['year'], 'employee')) ?>,
                totals: <?= json_encode(array_column($attendanceChart['year'], 'present')) ?>,
                max: 312
            }
        };


        let currentPeriod = 'month';

        const options = {
            chart: {
                type: 'bar',
                height: 320,
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 6,
                    columnWidth: '45%',
                    distributed: true
                }
            },
            series: [{
                name: 'Present Days',
                data: attendanceData[currentPeriod].totals
            }],
            xaxis: {
                categories: attendanceData[currentPeriod].labels,
                labels: {
                    style: {
                        colors: '#1a6e40', // 👈 employee name color
                        fontSize: '12px',
                        fontWeight: 600
                    }
                }
            },

            yaxis: {
                max: attendanceData[currentPeriod].max,
                title: {
                    text: 'Total Working Days',
                    style: {
                        color: '#6c757d',
                        fontSize: '13px',
                        fontWeight: 600
                    }
                },
                labels: {
                    style: {
                        colors: '#23bb95', // 👈 left side numbers color
                        fontSize: '12px'
                    }
                }
            },

            colors: ['#1a6e40', '#23bb95', '#5cc6a7', '#9fe0c8'],
            dataLabels: {
                enabled: true
            },
            tooltip: {
                y: {
                    formatter: val => val + ' days'
                }
            }
        };

        const chart = new ApexCharts(
            document.querySelector("#attendanceChart"),
            options
        );
        chart.render();

        // Tab Click
        document.querySelectorAll('.nav-link').forEach(btn => {
            btn.addEventListener('click', function() {

                document.querySelectorAll('.nav-link')
                    .forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                currentPeriod = this.dataset.period;

                chart.updateOptions({
                    series: [{
                        data: attendanceData[currentPeriod].totals
                    }],
                    xaxis: {
                        categories: attendanceData[currentPeriod].labels
                    },
                    yaxis: {
                        max: attendanceData[currentPeriod].max
                    }
                });
            });
        });

    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const resetBtn = form.querySelector('button[type="reset"]');

        resetBtn.addEventListener('click', function(e) {
            e.preventDefault(); // avoid default reset
            // Clear all inputs
            form.querySelectorAll('input, select').forEach(function(el) {
                if (el.tagName === 'SELECT') el.selectedIndex = 0; // first option select
                else el.value = ''; // input clear
            });
        });
    });
</script>

<script>
   $(document).on("click", ".cal-nav-btn", function(){
    alert();

    let header = $(".cal-header");

    let month = parseInt(header.data("month"));
    let year  = parseInt(header.data("year"));

    let dir = $(this).data("dir");

    if(dir === "prev"){
        month--;
        if(month < 1){
            month = 12;
            year--;
        }
    }

    if(dir === "next"){
        month++;
        if(month > 12){
            month = 1;
            year++;
        }
    }

    loadCalendar(month, year);

});

function loadCalendar(month,year){

    $.ajax({
        url: "<?= $GLOBALS['base_url'] ?>calendar-load",
        type: "GET",
        data: {
            month: month,
            year: year
        },
        success: function(res){
            $("#calendar-container").html(res);
            console.log("info".res);
            
        }
    });

}
</script>
<div class="app__slide-wrapper">
<style>
    .attendance-scrollable {
        max-height: 400px;
        overflow-y: auto;
        position: relative;
    }
    .attendance-scrollable thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: #f8fafc !important; /* matches .table-light */
        box-shadow: inset 0 -1px 0 #cbd5e1; /* maintains cell border */
    }
    /* Custom sleek scrollbar design */
    .attendance-scrollable::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .attendance-scrollable::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    .attendance-scrollable::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .attendance-scrollable::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>


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

    <!-- Search & Date Filter Form -->
    <div class="card custom-card shadow-xs mb-4">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-4 col-12">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-calendar text-muted"></i></span>
                        <input type="date" id="filterDate" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="col-md-8 col-12 text-end">
                    <span class="text-muted fs-12 fw-semibold">Select date to fetch live Tipsoi attendance records. Default is Today.</span>
                </div>
            </div>
        </div>
    </div>

    <!--  CARDS -->
    <div class="row align-items-stretch mb-4 gy-3">
        <!-- Card 1: ERP Employees Count -->
        <div class="col-xxl-2 col-xl-4 col-md-6 col-12">
            <div class="card__wrapper border-top border-4 border-success-custom shadow-xs h-100">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon bg-success-light text-success" style="background: rgba(25, 135, 84, 0.1); color: #198754; width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-users"></i></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-1 text-muted fs-11">ERP Employees</h6>
                        <h4 class="card__title text-success mb-0" id="cardErpCount" style="color: #198754 !important; font-weight: 700;"><?= $totalEmployees ?? 0 ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Tipsoi Employees Count -->
        <div class="col-xxl-2 col-xl-4 col-md-6 col-12">
            <div class="card__wrapper border-top border-4 border-success-custom shadow-xs h-100">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon bg-success-light text-success" style="background: rgba(25, 135, 84, 0.1); color: #198754; width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-users-line"></i></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-1 text-muted fs-11">Tipsoi Employees</h6>
                        <h4 class="card__title text-success mb-0" id="cardTipsoiCount" style="color: #198754 !important; font-weight: 700;">0</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Present Count -->
        <div class="col-xxl-2 col-xl-4 col-md-6 col-12">
            <div class="card__wrapper border-top border-4 border-success-custom shadow-xs h-100" id="btnDashboardPresent" style="cursor: pointer;">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon bg-success-light text-success" style="background: rgba(25, 135, 84, 0.1); color: #198754; width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-user-check"></i></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-1 text-muted fs-11">Present</h6>
                        <h4 class="card__title text-success mb-0" id="cardPresentCount" style="color: #198754 !important; font-weight: 700;">0</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Absent Count -->
        <div class="col-xxl-2 col-xl-4 col-md-6 col-12">
            <div class="card__wrapper border-top border-4 border-danger-custom shadow-xs h-100" id="btnDashboardAbsent" style="cursor: pointer;">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon" style="background: rgba(220, 53, 69, 0.1); color: #dc3545; width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-user-xmark"></i></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-1 text-muted fs-11">Absent</h6>
                        <h4 class="card__title text-danger mb-0" id="cardAbsentCount" style="font-weight: 700;">0</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 5: Late Count -->
        <div class="col-xxl-2 col-xl-4 col-md-6 col-12">
            <div class="card__wrapper border-top border-4 border-warning-custom shadow-xs h-100" id="btnDashboardLate" style="cursor: pointer;">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107; width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-user-clock"></i></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-1 text-muted fs-11">Late</h6>
                        <h4 class="card__title text-warning mb-0" id="cardLateCount" style="font-weight: 700;">0</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 6: Leave Count -->
        <div class="col-xxl-2 col-xl-4 col-md-6 col-12">
            <div class="card__wrapper border-top border-4 border-info-custom shadow-xs h-100" id="btnDashboardLeave" style="cursor: pointer;">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon" style="background: rgba(13, 202, 240, 0.1); color: #0dcaf0; width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-bed"></i></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-1 text-muted fs-11">On Leave</h6>
                        <h4 class="card__title text-info mb-0" id="cardLeaveCount" style="font-weight: 700;">0</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DEPARTMENT WISE ATTENDANCE DONUTS CAROUSEL -->
    <div class="card custom-card shadow-xs mb-4 border-top border-4 border-success-custom">
        <div class="card-header py-3 border-bottom d-flex justify-content-between align-items-center bg-white">
            <h5 class="card-title fw-bold mb-0 text-success-custom fs-14" style="color: #198754 !important;">DEPARTMENT WISE ATTENDANCE</h5>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-light border" id="scrollLeftBtn" style="border-radius: 50%; width: 32px; height: 32px; padding: 0;">
                    <i class="fa-solid fa-angle-left"></i>
                </button>
                <button class="btn btn-sm btn-light border" id="scrollRightBtn" style="border-radius: 50%; width: 32px; height: 32px; padding: 0;">
                    <i class="fa-solid fa-angle-right"></i>
                </button>
            </div>
        </div>
        <div class="card-body p-3">
            <div class="d-flex overflow-auto gap-3 py-2 scroll-container" id="deptChartsContainer" style="scroll-behavior: smooth; -ms-overflow-style: none; scrollbar-width: none;">
                <div class="text-center w-100 py-4 text-muted">Loading department charts...</div>
            </div>
            
            <!-- Unified Legend -->
            <div class="d-flex flex-wrap justify-content-center gap-4 mt-4 pt-3 border-top fs-11 fw-semibold text-muted">
                <div class="d-flex align-items-center gap-2"><span style="background-color: #00897B; width: 12px; height: 12px; display: inline-block; border-radius: 2px;"></span>On Time</div>
                <div class="d-flex align-items-center gap-2"><span style="background-color: #E2B93B; width: 12px; height: 12px; display: inline-block; border-radius: 2px;"></span>Late</div>
                <div class="d-flex align-items-center gap-2"><span style="background-color: #E53935; width: 12px; height: 12px; display: inline-block; border-radius: 2px;"></span>Absent</div>
                <div class="d-flex align-items-center gap-2"><span style="background-color: #7CB342; width: 12px; height: 12px; display: inline-block; border-radius: 2px;"></span>On Leave</div>
                <div class="d-flex align-items-center gap-2"><span style="background-color: #B0BEC5; width: 12px; height: 12px; display: inline-block; border-radius: 2px;"></span>Holiday/Day Off</div>
                <div class="d-flex align-items-center gap-2"><span style="background-color: #8E24AA; width: 12px; height: 12px; display: inline-block; border-radius: 2px;"></span>Special Day</div>
            </div>
        </div>
    </div>

    <!-- DAILY ATTENDANCE DETAILED TABLE -->
    <div class="card custom-card shadow-xs border-top border-4 border-success-custom mb-4">
        <div class="card-header py-3 border-bottom bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-md-4 col-12">
                    <h5 class="card-title fw-bold mb-0 text-success-custom fs-14" style="color: #198754 !important;">Today's Attendance List</h5>
                </div>
                <!-- Search bar inside header for compact UI -->
                <div class="col-md-4 col-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" id="attSearchInput" class="form-control" placeholder="Search Employee by Name/ID/RFID">
                    </div>
                </div>
                <!-- Status filter in header -->
                <div class="col-md-4 col-6">
                    <select id="attStatusFilter" class="form-select form-select-sm fs-12">
                        <option value="">All Statuses</option>
                        <option value="Present">Present (On Time)</option>
                        <option value="Late">Late Only</option>
                        <option value="Absent">Absent Only</option>
                        <option value="Leave">On Leave</option>
                        <option value="Off Day">Off Day</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive attendance-scrollable">
                <table class="table table-hover table-striped align-middle mb-0 fs-12" id="dailyAttTable" style="border-color: #cbd5e1;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 5%;">S/L</th>
                            <th class="text-center" style="width: 8%;">ID</th>
                            <th>Employee Name</th>
                            <th>Department</th>
                            <th>Shift</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Punch In</th>
                            <th class="text-center">Punch Out</th>
                            <th class="text-center">Late</th>
                            <th class="text-center">Hours</th>
                        </tr>
                    </thead>
                    <tbody id="dailyAttTbody">
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <div class="spinner-border spinner-border-sm text-success-custom mb-2" role="status" style="color: #198754 !important;"></div>
                                <div class="fw-semibold text-muted">Fetching live logs from Tipsoi...</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <div id="calendar-container">


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

                        <div class="cal-nav d-none">
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

                        <!--  Empty cells BEFORE month starts -->
                        <?php for ($i = 0; $i < $firstDayOfWeek; $i++): ?>
                            <div class="cal-cell other-month"></div>
                        <?php endfor; ?>

                        <!-- Days of month -->
                        <?php for ($day = 1; $day <= $daysInMonth; $day++):

                            $dateStr = sprintf("%04d-%02d-%02d", $year, $month, $day);
                            $classes = "cal-cell";

                            // today
                            if ($dateStr == $today) $classes .= " today";

                            // friday weekly off
                            $weekDay = date('w', strtotime($dateStr));
                            if ($weekDay == 5) $classes .= " weekend";

                            // holiday
                            if (isset($holidays[$day])) $classes .= " holiday";

                            // tooltip
                            $title = isset($holidays[$day]) ? implode(', ', $holidays[$day]) : '';
                        ?>
                            <div class="<?= $classes ?>" title="<?= htmlspecialchars($title) ?>">
                                <?= $day ?>
                            </div>
                        <?php endfor; ?>

                        <!--  remaining cells -->
                        <?php
                        $totalCells = $firstDayOfWeek + $daysInMonth;
                        $remaining = (7 - ($totalCells % 7)) % 7;

                        for ($i = 0; $i < $remaining; $i++): ?>
                            <div class="cal-cell other-month"></div>
                        <?php endfor; ?>

                    </div>

                    <!-- Legend -->
                    <div class="legend">
                        <div class="legend-item">
                            <div class="legend-dot" style="background:var(--today-bg)"></div>Today
                        </div>
                        <div class="legend-item">
                            <div class="legend-dot" style="background:var(--holiday)"></div>Government Holiday
                        </div>
                        <div class="legend-item">
                            <div class="legend-dot" style="background:#d5593c"></div>Weekly Holiday
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

                            $start = strtotime($h['start_date']);
                            $end   = strtotime($h['end_date']);

                            $startDay = date('d', $start);
                            $endDay   = date('d', $end);

                            $startMonth = date('M', $start);
                            $endMonth   = date('M', $end);
                        ?>

                            <li class="holiday-item">
                                <div class="holiday-date-box">
                                    <div class="day">
                                        <?php if ($startDay == $endDay && $startMonth == $endMonth): ?>
                                            <?= $startDay ?>
                                        <?php else: ?>
                                            <?= $startDay ?> - <?= $endDay ?>
                                        <?php endif; ?>
                                    </div>

                                    <div class="month">
                                        <?php if ($startMonth == $endMonth): ?>
                                            <?= $startMonth ?>
                                        <?php else: ?>
                                            <?= $startMonth ?> - <?= $endMonth ?>
                                        <?php endif; ?>
                                    </div>
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
<?php ob_start(); ?>
document.addEventListener("DOMContentLoaded", function() {
    let allDailyRows = [];
    let activeCharts = [];

    // Horizontal Scroll Buttons
    const container = document.getElementById("deptChartsContainer");
    document.getElementById("scrollLeftBtn").addEventListener("click", () => {
        container.scrollBy({ left: -260, behavior: 'smooth' });
    });
    document.getElementById("scrollRightBtn").addEventListener("click", () => {
        container.scrollBy({ left: 260, behavior: 'smooth' });
    });

    // Date Filter Change
    const filterDateInput = document.getElementById("filterDate");
    filterDateInput.addEventListener("change", loadDashboardData);

    // Search and Status Filters
    const searchInput = document.getElementById("attSearchInput");
    const statusFilter = document.getElementById("attStatusFilter");

    searchInput.addEventListener("input", applyTableFilters);
    statusFilter.addEventListener("change", applyTableFilters);

    // Initial Load
    loadDashboardData();

    function loadDashboardData() {
        const selectedDate = filterDateInput.value;
        
        // Show Loading states
        document.getElementById("cardTipsoiCount").textContent = "Loading...";
        document.getElementById("cardPresentCount").textContent = "Loading...";
        document.getElementById("cardAbsentCount").textContent = "Loading...";
        document.getElementById("cardLateCount").textContent = "Loading...";
        document.getElementById("cardLeaveCount").textContent = "Loading...";

        document.getElementById("deptChartsContainer").innerHTML = `
            <div class="text-center w-100 py-4 text-muted">
                <div class="spinner-border spinner-border-sm text-success mb-2" role="status" style="color: #198754 !important;"></div>
                <div>Fetching live department charts...</div>
            </div>
        `;

        document.getElementById("dailyAttTbody").innerHTML = `
            <tr>
                <td colspan="10" class="text-center py-5">
                    <div class="spinner-border spinner-border-sm text-success mb-2" role="status" style="color: #198754 !important;"></div>
                    <div class="fw-semibold text-muted">Fetching live logs from Tipsoi...</div>
                </td>
            </tr>
        `;

        // Clear existing ApexCharts instances to prevent memory leaks
        activeCharts.forEach(c => {
            try { c.destroy(); } catch(e) {}
        });
        activeCharts = [];

        fetch(`<?= $GLOBALS['base_url'] ?>/api/employee/tipsoi-daily-data?date=${selectedDate}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || "Failed to load data");
                }

                allDailyRows = data.rows || [];

                // 1. Populate Cards
                document.getElementById("cardTipsoiCount").textContent = allDailyRows.length;
                document.getElementById("cardPresentCount").textContent = data.presentCount || 0;
                document.getElementById("cardAbsentCount").textContent = data.absentCount || 0;
                document.getElementById("cardLateCount").textContent = data.lateCount || 0;
                document.getElementById("cardLeaveCount").textContent = data.leaveCount || 0;

                // 2. Render Department Wise Donut Charts
                renderDepartmentCharts(allDailyRows);

                // 3. Render Daily Table
                applyTableFilters();
            })
            .catch(err => {
                console.error(err);
                const errMsg = `<div class="text-center w-100 py-4 text-danger fw-semibold">Error loading charts: ${err.message}</div>`;
                document.getElementById("deptChartsContainer").innerHTML = errMsg;
                document.getElementById("dailyAttTbody").innerHTML = `
                    <tr>
                        <td colspan="10" class="text-center py-4 text-danger fw-semibold">Error loading live attendance: ${err.message}</td>
                    </tr>
                `;
            });
    }

    function renderDepartmentCharts(rows) {
        const container = document.getElementById("deptChartsContainer");
        container.innerHTML = "";

        if (rows.length === 0) {
            container.innerHTML = `<div class="text-center w-100 py-4 text-muted">No department records available.</div>`;
            return;
        }

        // Group by department
        let deptMap = {};
        rows.forEach(row => {
            let dept = row.department || 'Unknown';
            if (!deptMap[dept]) {
                deptMap[dept] = {
                    onTime: 0,
                    late: 0,
                    absent: 0,
                    leave: 0,
                    offday: 0,
                    special: 0
                };
            }

            let status = row.status || 'Absent';
            if (status === 'Present') {
                deptMap[dept].onTime++;
            } else if (status === 'Late') {
                deptMap[dept].late++;
            } else if (status === 'Leave' || status === 'Leave Approval Pending') {
                deptMap[dept].leave++;
            } else if (status === 'Off Day' || status === 'Govt. Holiday') {
                deptMap[dept].offday++;
            } else if (status === 'Absent') {
                deptMap[dept].absent++;
            } else {
                deptMap[dept].special++;
            }
        });

        // Loop through each department and create card + chart
        let idx = 0;
        for (let deptName in deptMap) {
            idx++;
            let counts = deptMap[deptName];
            
            // Total employees in department
            let deptTotal = counts.onTime + counts.late + counts.absent + counts.leave + counts.offday + counts.special;
            if (deptTotal === 0) continue;

            const chartId = `chart-dept-${idx}`;
            
            // Append Card structure
            const cardHtml = `
                <div class="card shadow-xs border p-3 flex-shrink-0 text-center" style="width: 250px; background-color: #ffffff; border-radius: 8px;">
                    <div id="${chartId}" style="height: 180px;"></div>
                    <div class="fw-bold mt-2 text-dark fs-12 text-truncate" title="${deptName}">${deptName}</div>
                </div>
            `;
            container.innerHTML += cardHtml;
        }

        // Wait for DOM to update then render ApexCharts for each
        let chartIdx = 0;
        for (let deptName in deptMap) {
            chartIdx++;
            let counts = deptMap[deptName];
            let deptTotal = counts.onTime + counts.late + counts.absent + counts.leave + counts.offday + counts.special;
            if (deptTotal === 0) continue;

            const chartSelector = `#chart-dept-${chartIdx}`;
            
            // Series order matches legend: On Time, Late, Absent, On Leave, Holiday/Day Off, Special Day
            const series = [counts.onTime, counts.late, counts.absent, counts.leave, counts.offday, counts.special];
            
            // Colors: Teal, Yellow, Red, Green, Grey, Purple
            const colors = ['#00897B', '#E2B93B', '#E53935', '#7CB342', '#B0BEC5', '#8E24AA'];

            const options = {
                series: series,
                chart: {
                    type: 'donut',
                    height: 185,
                    sparkline: { enabled: true }
                },
                colors: colors,
                labels: ['On Time', 'Late', 'Absent', 'On Leave', 'Holiday/Day Off', 'Special Day'],
                dataLabels: { enabled: false },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '72%',
                            labels: {
                                show: true,
                                name: {
                                    show: true,
                                    fontSize: '9px',
                                    fontWeight: 700,
                                    color: '#64748b',
                                    offsetY: 20
                                },
                                value: {
                                    show: true,
                                    fontSize: '15px',
                                    fontWeight: 700,
                                    color: '#1e293b',
                                    offsetY: -15,
                                    formatter: val => val
                                },
                                total: {
                                    show: true,
                                    label: deptName.length > 15 ? deptName.substring(0, 12) + "..." : deptName,
                                    fontSize: '8px',
                                    fontWeight: 700,
                                    color: '#64748b',
                                    formatter: () => deptTotal
                                }
                            }
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: val => `${val} Employees`
                    }
                },
                legend: { show: false }
            };

            const chart = new ApexCharts(document.querySelector(chartSelector), options);
            chart.render();
            activeCharts.push(chart);
        }
    }

    function applyTableFilters() {
        const query = searchInput.value.toLowerCase().trim();
        const statusVal = statusFilter.value;

        const filtered = allDailyRows.filter(row => {
            const matchesSearch = !query || 
                                  String(row.name).toLowerCase().includes(query) ||
                                  String(row.id).toLowerCase().includes(query);

            let matchesStatus = true;
            if (statusVal === 'Present') {
                matchesStatus = (row.status === 'Present');
            } else if (statusVal === 'Late') {
                matchesStatus = (row.status === 'Late');
            } else if (statusVal === 'Absent') {
                matchesStatus = (row.status === 'Absent');
            } else if (statusVal === 'Leave') {
                matchesStatus = (row.status === 'Leave' || row.status === 'Leave Approval Pending');
            } else if (statusVal === 'Off Day') {
                matchesStatus = (row.status === 'Off Day' || row.status === 'Govt. Holiday');
            }

            return matchesSearch && matchesStatus;
        });

        renderDailyTable(filtered);
    }

    function renderDailyTable(rows) {
        const tbody = document.getElementById("dailyAttTbody");
        tbody.innerHTML = "";

        if (rows.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="10" class="text-center py-4 text-muted fs-13">No matching attendance logs found.</td>
                </tr>
            `;
            return;
        }

        rows.forEach((row, index) => {
            let statusBadge = "";
            if (row.status === 'Present') {
                statusBadge = `<span class="badge" style="background-color: rgba(0, 137, 123, 0.1); color: #00897B; border: 1px solid #00897B;">On Time</span>`;
            } else if (row.status === 'Late') {
                statusBadge = `<span class="badge" style="background-color: rgba(226, 185, 59, 0.1); color: #E2B93B; border: 1px solid #E2B93B;">Late</span>`;
            } else if (row.status === 'Absent') {
                statusBadge = `<span class="badge" style="background-color: rgba(229, 57, 53, 0.1); color: #E53935; border: 1px solid #E53935;">Absent</span>`;
            } else if (row.status === 'Leave' || row.status === 'Leave Approval Pending') {
                statusBadge = `<span class="badge" style="background-color: rgba(124, 179, 66, 0.1); color: #7CB342; border: 1px solid #7CB342;">Leave</span>`;
            } else if (row.status === 'Off Day' || row.status === 'Govt. Holiday') {
                statusBadge = `<span class="badge" style="background-color: rgba(176, 190, 197, 0.1); color: #B0BEC5; border: 1px solid #B0BEC5;">Off Day</span>`;
            } else {
                statusBadge = `<span class="badge" style="background-color: rgba(142, 36, 170, 0.1); color: #8E24AA; border: 1px solid #8E24AA;">Special</span>`;
            }

            tbody.innerHTML += `
                <tr>
                    <td class="text-center text-muted fw-semibold">${index + 1}</td>
                    <td class="text-center fw-bold text-dark">${row.id}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            ${row.photo ? `<img src="${row.photo}" class="rounded-circle me-2" style="width: 26px; height: 26px; object-fit: cover;">` : `<div class="bg-secondary text-white rounded-circle d-inline-flex align-items-center justify-content-center me-2" style="width: 26px; height: 26px; font-size: 10px; font-weight: 700;">${row.name.charAt(0)}</div>`}
                            <div>
                                <span class="fw-bold text-dark d-block">${row.name}</span>
                                <small class="text-muted d-block" style="font-size: 9px;">${row.designation}</small>
                            </div>
                        </div>
                    </td>
                    <td class="fw-semibold">${row.department}</td>
                    <td>${row.shift}</td>
                    <td class="text-center">${statusBadge}</td>
                    <td class="text-center fw-semibold text-success font-monospace">${row.entry}</td>
                    <td class="text-center fw-semibold text-danger font-monospace">${row.exit}</td>
                    <td class="text-center text-warning font-monospace">${row.late}</td>
                    <td class="text-center fw-bold text-primary font-monospace">${row.hours}</td>
                </tr>`;
        });
    }

    // Redirect to hrm-attendance-dashboard on card clicks with date and status filter params
    const dashboardCards = [
        { id: "btnDashboardPresent", status: "present" },
        { id: "btnDashboardAbsent", status: "absent" },
        { id: "btnDashboardLate", status: "late" },
        { id: "btnDashboardLeave", status: "leave" }
    ];

    dashboardCards.forEach(card => {
        const el = document.getElementById(card.id);
        if (el) {
            el.addEventListener("click", () => {
                const selectedDate = document.getElementById("filterDate").value;
                window.location.href = `<?= $GLOBALS['base_url'] ?>/hrm-attendance-dashboard?status=${card.status}&date=${selectedDate}`;
            });
        }
    });
});

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

// Calendar Navigation

    $(document).on("click", ".cal-nav-btn", function() {
        alert();

        let header = $(".cal-header");

        let month = parseInt(header.data("month"));
        let year = parseInt(header.data("year"));

        let dir = $(this).data("dir");

        if (dir === "prev") {
            month--;
            if (month < 1) {
                month = 12;
                year--;
            }
        }

        if (dir === "next") {
            month++;
            if (month > 12) {
                month = 1;
                year++;
            }
        }

        loadCalendar(month, year);

    });

    function loadCalendar(month, year) {

        $.ajax({
            url: "<?= $GLOBALS['base_url'] ?>calendar-load",
            type: "GET",
            data: {
                month: month,
                year: year
            },
            success: function(res) {
                $("#calendar-container").html(res);
                console.log("info".res);

            }
        });

    }
<?php $page_script = ob_get_clean(); ?>
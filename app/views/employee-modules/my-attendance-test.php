<?php
$page_title = "My Attendance (Test)";
$page = 'My Attendance (Test)';
$show_breadcrumb = true;
?>

<!-- Header Section with Month Filter -->
<div class="row align-items-center mb-25">
    <div class="col-md-7 col-12">
        <h4 class="mb-1 fw-bold text-dark d-flex align-items-center gap-2">
            <i class="fa-solid fa-calendar-check text-primary"></i>
            My Attendance 
            <span class="badge bg-success-subtle text-success border border-success-subtle fs-12 px-3 py-1 rounded-pill">
                Test Module
            </span>
        </h4>
        <p class="text-muted fs-13 mb-0">Direct attendance summary and daily punch history</p>
    </div>
    <div class="col-md-5 col-12 text-md-end mt-3 mt-md-0">
        <form method="GET" action="<?= $GLOBALS['base_url'] ?>/my-attendance-test" class="d-inline-flex align-items-center gap-2">
            <label class="form-label fw-bold text-muted fs-13 mb-0">Month:</label>
            <input type="month" name="month" class="form-control form-control-sm rounded-3 py-2" 
                   value="<?= htmlspecialchars($selectedMonth) ?>" onchange="this.form.submit()" style="max-width: 170px;">
        </form>
    </div>
</div>

<!-- EMPLOYEE INFO & MONTHLY SUMMARY CARDS -->
<div class="row g-3 mb-25">
    <!-- Employee Quick Profile Banner -->
    <div class="col-xxl-4 col-xl-12 col-12">
        <div class="card__wrapper border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-center">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" 
                     style="width: 52px; height: 52px; background: linear-gradient(135deg, #198754 0%, #20c997 100%); font-size: 20px;">
                    <?= strtoupper(substr($employee['full_name'] ?? 'U', 0, 1)) ?>
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($employee['full_name'] ?? '') ?></h5>
                    <div class="fs-13 text-muted">
                        <span class="badge bg-light text-primary border me-1">ID: <?= htmlspecialchars($employee['employee_id'] ?? '—') ?></span>
                        <?= htmlspecialchars($employee['designation_name'] ?? '') ?> • <?= htmlspecialchars($employee['department_name'] ?? '') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Stats Summary -->
    <div class="col-xxl-8 col-xl-12 col-12">
        <div class="row g-2">
            <div class="col-sm-2 col-4">
                <div class="card__wrapper border-0 shadow-sm rounded-4 p-3 bg-white text-center">
                    <span class="fs-11 fw-bold text-muted text-uppercase d-block mb-1">Days</span>
                    <h4 class="fw-bold mb-0 text-dark"><?= $summary['total_days'] ?></h4>
                </div>
            </div>
            <div class="col-sm-2 col-4">
                <div class="card__wrapper border-0 shadow-sm rounded-4 p-3 bg-white text-center border-bottom border-success border-4">
                    <span class="fs-11 fw-bold text-success text-uppercase d-block mb-1">Present</span>
                    <h4 class="fw-bold mb-0 text-success"><?= $summary['present'] ?></h4>
                </div>
            </div>
            <div class="col-sm-2 col-4">
                <div class="card__wrapper border-0 shadow-sm rounded-4 p-3 bg-white text-center border-bottom border-warning border-4">
                    <span class="fs-11 fw-bold text-warning text-uppercase d-block mb-1">Late</span>
                    <h4 class="fw-bold mb-0 text-warning"><?= $summary['late'] ?></h4>
                </div>
            </div>
            <div class="col-sm-2 col-4">
                <div class="card__wrapper border-0 shadow-sm rounded-4 p-3 bg-white text-center border-bottom border-danger border-4">
                    <span class="fs-11 fw-bold text-danger text-uppercase d-block mb-1">Absent</span>
                    <h4 class="fw-bold mb-0 text-danger"><?= $summary['absent'] ?></h4>
                </div>
            </div>
            <div class="col-sm-2 col-4">
                <div class="card__wrapper border-0 shadow-sm rounded-4 p-3 bg-white text-center border-bottom border-info border-4">
                    <span class="fs-11 fw-bold text-info text-uppercase d-block mb-1">Leave</span>
                    <h4 class="fw-bold mb-0 text-info"><?= $summary['leave'] ?></h4>
                </div>
            </div>
            <div class="col-sm-2 col-4">
                <div class="card__wrapper border-0 shadow-sm rounded-4 p-3 bg-white text-center border-bottom border-secondary border-4">
                    <span class="fs-11 fw-bold text-secondary text-uppercase d-block mb-1">Off-Day</span>
                    <h4 class="fw-bold mb-0 text-secondary"><?= $summary['off_days'] ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MONTHLY ATTENDANCE TABLE -->
<div class="card__wrapper border-0 shadow-sm rounded-4 p-4 bg-white">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-bold text-dark mb-0">
            <i class="fa-regular fa-calendar-days me-1 text-primary"></i> 
            Logs for <?= date('F Y', strtotime($selectedMonth . '-01')) ?>
        </h6>
        <span class="fs-12 text-muted">Daily Attendance Logs</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="text-secondary fs-12 text-uppercase fw-bold">
                    <th class="ps-3 py-3" style="width: 130px;">Date</th>
                    <th class="py-3">Day</th>
                    <th class="py-3">Shift</th>
                    <th class="py-3 text-center">Punch In</th>
                    <th class="py-3 text-center">Punch Out</th>
                    <th class="py-3 text-center">Late By</th>
                    <th class="py-3 text-center">Working Hours</th>
                    <th class="py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($calendarDays as $dayItem): 
                    $dateStr = $dayItem['date'];
                    $dayName = $dayItem['day'];
                    $rec = $dayItem['record'];
                    $status = $dayItem['status'];
                    $isToday = ($dateStr === date('Y-m-d'));

                    $rowBg = $isToday ? 'background-color: #f0fdf4;' : '';
                    if ($status === 'Day Off' || $status === 'Holiday') {
                        $rowBg = 'background-color: #f8fafc; opacity: 0.85;';
                    }

                    $badgeClass = 'bg-secondary';
                    if ($status === 'Present') $badgeClass = 'bg-success text-white';
                    elseif ($status === 'Late') $badgeClass = 'bg-warning text-dark';
                    elseif ($status === 'Half-Day') $badgeClass = 'bg-warning-subtle text-warning border border-warning';
                    elseif ($status === 'Absent') $badgeClass = 'bg-danger text-white';
                    elseif ($status === 'On Leave') $badgeClass = 'bg-info text-white';
                    elseif ($status === 'Day Off') $badgeClass = 'bg-secondary-subtle text-secondary border';
                    elseif ($status === 'Holiday') $badgeClass = 'bg-dark text-white';
                    elseif ($status === 'Upcoming') $badgeClass = 'bg-light text-muted border';

                    $inTimeDisplay = (!empty($rec['punch_in_time'])) ? date('h:i A', strtotime($rec['punch_in_time'])) : '—';
                    $outTimeDisplay = (!empty($rec['punch_out_time'])) ? date('h:i A', strtotime($rec['punch_out_time'])) : '—';
                ?>
                    <tr style="<?= $rowBg ?>">
                        <td class="ps-3 fw-bold fs-13 <?= $isToday ? 'text-success' : 'text-dark' ?>">
                            <?= date('d M, Y', strtotime($dateStr)) ?>
                            <?php if ($isToday): ?>
                                <span class="badge bg-success-subtle text-success fs-10 ms-1">Today</span>
                            <?php endif; ?>
                        </td>
                        <td class="fs-13 fw-semibold text-secondary">
                            <?= $dayName ?>
                        </td>
                        <td>
                            <?php 
                                $sName = $rec['shift_name'] ?? 'IT / Head Office Regular';
                                if ($sName === 'Friday OFF' || $sName === 'Saturday OFF') {
                                    $sName = 'Regular Shift';
                                }
                            ?>
                            <div class="fw-semibold text-dark fs-13"><?= htmlspecialchars($sName) ?></div>
                            <div class="fs-12 text-muted">
                                <?= !empty($rec['shift_start_time']) ? date('h:i A', strtotime($rec['shift_start_time'])) : '09:30 AM' ?>
                            </div>
                        </td>
                        <td class="text-center fw-bold text-dark fs-13">
                            <?= $inTimeDisplay ?>
                        </td>
                        <td class="text-center fw-bold text-dark fs-13">
                            <?= $outTimeDisplay ?>
                        </td>
                        <td class="text-center fs-13">
                            <?php if (!empty($rec['late_minutes']) && $rec['late_minutes'] > 0): ?>
                                <span class="badge bg-warning-subtle text-danger border border-warning-subtle fw-semibold px-2 py-1">
                                    +<?= $rec['late_minutes'] ?> mins
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center fw-semibold text-dark fs-13">
                            <?= htmlspecialchars($rec['working_hours'] ?? '—') ?>
                        </td>
                        <td class="text-center">
                            <span class="badge rounded-pill px-3 py-1 fs-12 fw-bold <?= $badgeClass ?>">
                                <?= htmlspecialchars($status) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

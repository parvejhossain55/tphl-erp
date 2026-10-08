<?php
$page_title = "My Attendance (Test)";
$page = 'My Attendance (Test)';
$show_breadcrumb = true;
?>

<!-- Alerts -->
<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 mb-20 shadow-sm" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($_SESSION['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['warning'])): ?>
    <div class="alert alert-warning alert-dismissible fade show rounded-3 mb-20 shadow-sm" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($_SESSION['warning']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['warning']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-20 shadow-sm" role="alert">
        <i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($_SESSION['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

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
        <p class="text-muted fs-13 mb-0">Direct attendance summary, daily punch history, and late waiver applications</p>
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
            <div class="mt-3 pt-2 border-top d-flex align-items-center justify-content-between text-muted fs-12">
                <span><i class="fa-solid fa-user-tie text-primary me-1"></i> Reporting Boss:</span>
                <strong class="text-dark"><?= !empty($employee['reporting_boss_name']) ? htmlspecialchars($employee['reporting_boss_name']) : 'Direct to HR' ?></strong>
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
                    <span class="fs-10 text-muted d-block mt-1">Calendar</span>
                </div>
            </div>
            <div class="col-sm-2 col-4">
                <div class="card__wrapper border-0 shadow-sm rounded-4 p-3 bg-white text-center border-bottom border-success border-4">
                    <span class="fs-11 fw-bold text-success text-uppercase d-block mb-1">Present</span>
                    <h4 class="fw-bold mb-0 text-success"><?= $summary['present'] ?></h4>
                    <span class="fs-10 text-success d-block mt-1">Attended</span>
                </div>
            </div>
            <div class="col-sm-2 col-4">
                <div class="card__wrapper border-0 shadow-sm rounded-4 p-3 bg-white text-center border-bottom border-warning border-4">
                    <span class="fs-11 fw-bold text-warning text-uppercase d-block mb-1">Late</span>
                    <h4 class="fw-bold mb-0 text-warning"><?= $summary['late'] ?></h4>
                    <?php if (!empty($summary['approved_late'])): ?>
                        <span class="fs-10 text-success fw-bold d-block mt-1" title="<?= $summary['approved_late'] ?> late days waived by HR">
                            <i class="fa-solid fa-check"></i> <?= $summary['approved_late'] ?> Waived
                        </span>
                    <?php else: ?>
                        <span class="fs-10 text-muted d-block mt-1">Pending</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-sm-2 col-4">
                <div class="card__wrapper border-0 shadow-sm rounded-4 p-3 bg-white text-center border-bottom border-danger border-4">
                    <span class="fs-11 fw-bold text-danger text-uppercase d-block mb-1">Absent</span>
                    <h4 class="fw-bold mb-0 text-danger"><?= $summary['absent'] ?></h4>
                    <span class="fs-10 text-danger d-block mt-1">Unexcused</span>
                </div>
            </div>
            <div class="col-sm-2 col-4">
                <div class="card__wrapper border-0 shadow-sm rounded-4 p-3 bg-white text-center border-bottom border-info border-4">
                    <span class="fs-11 fw-bold text-info text-uppercase d-block mb-1">Leave</span>
                    <h4 class="fw-bold mb-0 text-info"><?= $summary['leave'] ?></h4>
                    <span class="fs-10 text-info d-block mt-1">Approved</span>
                </div>
            </div>
            <div class="col-sm-2 col-4">
                <div class="card__wrapper border-0 shadow-sm rounded-4 p-3 bg-white text-center border-bottom border-secondary border-4">
                    <span class="fs-11 fw-bold text-secondary text-uppercase d-block mb-1">Off-Day</span>
                    <h4 class="fw-bold mb-0 text-secondary"><?= $summary['off_days'] ?></h4>
                    <span class="fs-10 text-secondary d-block mt-1">Weekend/Hol</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SUPERVISOR BANNER: Team Late Requests (if any) -->
<?php if (!empty($team_late_requests)): ?>
    <div class="card border-0 rounded-4 shadow-sm mb-25 overflow-hidden border-start border-warning border-4 bg-white">
        <div class="card-header bg-warning-subtle py-3 border-0 d-flex align-items-center justify-content-between">
            <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-warning"></i>
                Team Late Approval Requests (<?= count($team_late_requests) ?> Pending Your Recommendation)
            </h6>
            <a href="<?= $GLOBALS['base_url'] ?>/my-employees-test" class="btn btn-sm btn-outline-dark rounded-pill px-3 py-1 fs-12 fw-semibold">
                Go to Team Attendance <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light fs-12 text-uppercase text-secondary">
                        <tr>
                            <th class="ps-3 py-2">Employee</th>
                            <th class="py-2">Date</th>
                            <th class="py-2 text-center">Late By</th>
                            <th class="py-2">Reason</th>
                            <th class="py-2 text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($team_late_requests as $tReq): ?>
                            <tr>
                                <td class="ps-3 py-2">
                                    <div class="fw-bold text-dark fs-13"><?= htmlspecialchars($tReq['employee_name']) ?></div>
                                    <div class="fs-11 text-muted">ID: <?= htmlspecialchars($tReq['office_id'] ?? '—') ?> • <?= htmlspecialchars($tReq['designation_name'] ?? '') ?></div>
                                </td>
                                <td class="fs-12 fw-semibold text-secondary">
                                    <?= date('d M, Y', strtotime($tReq['attendance_date'])) ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-warning-subtle text-danger border border-warning-subtle fw-bold fs-11 px-2 py-1">
                                        +<?= $tReq['late_minutes'] ?> mins
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-primary border fs-11 fw-semibold mb-1"><?= htmlspecialchars($tReq['reason_type']) ?></span>
                                    <div class="fs-12 text-dark text-truncate" style="max-width: 250px;">
                                        <?= htmlspecialchars($tReq['reason_description'] ?: '—') ?>
                                    </div>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-inline-flex gap-2">
                                        <a href="<?= $GLOBALS['base_url'] ?>/supervisor-approve-late/<?= $tReq['id'] ?>" 
                                           class="btn btn-sm btn-success rounded-pill px-3 py-1 fs-11 fw-semibold"
                                           onclick="return confirm('Recommend and approve this late request to HR?')">
                                            <i class="fa-solid fa-check me-1"></i>Recommend
                                        </a>
                                        <a href="<?= $GLOBALS['base_url'] ?>/supervisor-reject-late/<?= $tReq['id'] ?>" 
                                           class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 fs-11 fw-semibold"
                                           onclick="return confirm('Reject this late request?')">
                                            <i class="fa-solid fa-xmark me-1"></i>Reject
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- MONTHLY ATTENDANCE TABLE -->
<div class="card__wrapper border-0 shadow-sm rounded-4 p-4 bg-white">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-bold text-dark mb-0">
            <i class="fa-regular fa-calendar-days me-1 text-primary"></i> 
            Logs for <?= date('F Y', strtotime($selectedMonth . '-01')) ?>
        </h6>
        <span class="fs-12 text-muted">Daily Attendance & Late Regularization</span>
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
                    <th class="py-3 text-center" style="min-width: 150px;">Late Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($calendarDays as $dayItem): 
                    $dateStr = $dayItem['date'];
                    $dayName = $dayItem['day'];
                    $rec = $dayItem['record'];
                    $status = $dayItem['status'];
                    $lateApp = $dayItem['late_approval'];
                    $isLateApproved = $dayItem['is_late_approved'];
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
                    $lateMinutes = (int)($rec['late_minutes'] ?? 0);
                    $isLateOccurrence = ($lateMinutes > 0 || $status === 'Late');
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
                            <?php if ($lateMinutes > 0): ?>
                                <span class="badge bg-warning-subtle text-danger border border-warning-subtle fw-semibold px-2 py-1">
                                    +<?= $lateMinutes ?> mins
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center fw-semibold text-dark fs-13">
                            <?= htmlspecialchars($rec['working_hours'] ?? '—') ?>
                        </td>
                        <td class="text-center">
                            <?php if ($isLateApproved): ?>
                                <span class="badge rounded-pill px-3 py-1 fs-12 fw-bold bg-success text-white" title="Late marked as Present (Waived)">
                                    <i class="fa-solid fa-check me-1"></i> Late (Waived)
                                </span>
                            <?php else: ?>
                                <span class="badge rounded-pill px-3 py-1 fs-12 fw-bold <?= $badgeClass ?>">
                                    <?= htmlspecialchars($status) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($isLateApproved): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fs-11 fw-bold">
                                    <i class="fa-solid fa-circle-check me-1"></i>Approved (Waived)
                                </span>
                            <?php elseif ($lateApp): ?>
                                <?php if ($lateApp['approval_status'] === 'pending_supervisor'): ?>
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1 rounded-pill fs-11 fw-semibold" title="Pending recommendation by your Reporting Boss">
                                            <i class="fa-regular fa-clock me-1 text-warning"></i>Pending Boss
                                        </span>
                                        <a href="<?= $GLOBALS['base_url'] ?>/cancel-late-approval-test/<?= $lateApp['id'] ?>" 
                                           class="btn btn-xs btn-outline-danger rounded-circle p-0 d-inline-flex align-items-center justify-content-center"
                                           style="width: 20px; height: 20px;"
                                           title="Cancel Application"
                                           onclick="return confirm('Do you want to cancel this late approval request?')">
                                            <i class="fa-solid fa-xmark fs-10"></i>
                                        </a>
                                    </div>
                                <?php elseif ($lateApp['approval_status'] === 'pending_hr'): ?>
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1 rounded-pill fs-11 fw-semibold" title="Recommended by Supervisor; awaiting HR final decision">
                                        <i class="fa-solid fa-user-check me-1"></i>Pending HR
                                    </span>
                                <?php elseif ($lateApp['approval_status'] === 'rejected'): ?>
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill fs-11 fw-semibold" 
                                              title="Rejected: <?= htmlspecialchars($lateApp['hr_remarks'] ?: ($lateApp['supervisor_remarks'] ?: 'Reason not accepted')) ?>">
                                            <i class="fa-solid fa-circle-xmark me-1"></i>Rejected
                                        </span>
                                        <button type="button" 
                                                class="btn btn-outline-warning btn-xs rounded-pill px-2 py-0 fs-10 btn-apply-late"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#applyLateModal" 
                                                data-date="<?= $dateStr ?>" 
                                                data-late="<?= $lateMinutes ?>" 
                                                data-in="<?= $inTimeDisplay ?>">
                                            Re-apply
                                        </button>
                                    </div>
                                <?php endif; ?>
                            <?php elseif ($isLateOccurrence): ?>
                                <button type="button" 
                                        class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fs-11 fw-semibold btn-apply-late shadow-sm"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#applyLateModal" 
                                        data-date="<?= $dateStr ?>" 
                                        data-late="<?= $lateMinutes ?>" 
                                        data-in="<?= $inTimeDisplay ?>">
                                    <i class="fa-solid fa-file-pen me-1"></i>Apply Waiver
                                </button>
                            <?php else: ?>
                                <span class="text-muted fs-12">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- LATE APPROVAL MODAL -->
<div class="modal fade" id="applyLateModal" tabindex="-1" aria-labelledby="applyLateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="fa-solid fa-clock-rotate-left fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark" id="applyLateModalLabel">Apply for Late Approval</h5>
                        <p class="fs-12 text-muted mb-0">Request waiver for late office arrival</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="<?= $GLOBALS['base_url'] ?>/apply-late-approval-test" method="POST">
                <input type="hidden" name="attendance_date" id="modalLateDateInput">
                
                <div class="modal-body pt-3">
                    <!-- Quick Info Pill -->
                    <div class="p-3 rounded-3 bg-light border mb-3">
                        <div class="row g-2">
                            <div class="col-6">
                                <span class="text-muted fs-11 d-block">Late Date:</span>
                                <strong class="text-dark fs-13" id="modalLateDateDisplay">—</strong>
                            </div>
                            <div class="col-6 text-end">
                                <span class="text-muted fs-11 d-block">Late Duration:</span>
                                <span class="badge bg-warning-subtle text-danger border border-warning-subtle fs-12 fw-bold" id="modalLateMinutesDisplay">
                                    +0 mins
                                </span>
                            </div>
                            <div class="col-12 mt-2 pt-2 border-top">
                                <span class="text-muted fs-11">Punch In Time:</span>
                                <strong class="text-dark fs-12 ms-1" id="modalLatePunchInDisplay">—</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Reason Type -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary fs-13 mb-1">
                            Reason Category <span class="text-danger">*</span>
                        </label>
                        <select name="reason_type" class="form-select rounded-3 py-2" required>
                            <option value="Official Duty">Official Duty / Field Work</option>
                            <option value="Client Meeting">Morning Client Meeting</option>
                            <option value="Heavy Traffic">Heavy Traffic / Road Block</option>
                            <option value="Transport Breakdown">Public Transport Breakdown</option>
                            <option value="Severe Weather">Severe Rain / Bad Weather</option>
                            <option value="Family Emergency">Family Emergency</option>
                            <option value="Medical / Health Issue">Medical / Health Issue</option>
                            <option value="Biometric Device Delay">Biometric / Device Delay</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <!-- Reason Description -->
                    <div class="mb-2">
                        <label class="form-label fw-bold text-secondary fs-13 mb-1">
                            Detailed Reason & Explanation <span class="text-danger">*</span>
                        </label>
                        <textarea name="reason_description" class="form-control rounded-3 py-2" rows="3" 
                                  placeholder="Please describe why you were late (e.g. client name, location, or emergency details)..." required></textarea>
                        <div class="form-text fs-11 text-muted">
                            This will be reviewed by your Reporting Boss and HR Admin.
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-3 py-2 fw-semibold fs-13" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 py-2 fw-semibold fs-13 shadow-sm">
                        <i class="fa-solid fa-paper-plane me-1"></i> Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Populate Modal data when clicking "Apply Waiver" or "Re-apply"
    document.querySelectorAll('.btn-apply-late').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var date = this.getAttribute('data-date');
            var late = this.getAttribute('data-late');
            var inTime = this.getAttribute('data-in');

            document.getElementById('modalLateDateInput').value = date;
            document.getElementById('modalLateDateDisplay').textContent = new Date(date).toLocaleDateString('en-GB', {
                day: '2-digit', month: 'short', year: 'numeric'
            });
            document.getElementById('modalLateMinutesDisplay').textContent = '+' + late + ' mins';
            document.getElementById('modalLatePunchInDisplay').textContent = inTime || '—';
        });
    });
});
</script>

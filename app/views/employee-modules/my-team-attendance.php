<?php
$page_title = "Team Employees Attendance";
$page = 'Team Employees';
$show_breadcrumb = true;
$isToday = ($selectedDate === date('Y-m-d'));
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

<!-- Header Section with Date Picker and Search -->
<div class="row align-items-center mb-25">
    <div class="col-lg-6 col-12">
        <h4 class="mb-1 fw-bold text-dark d-flex align-items-center gap-2">
            <i class="fa-solid fa-users text-primary"></i>
            Team Employees Attendance
            <span class="badge bg-success-subtle text-success border border-success-subtle fs-12 px-3 py-1 rounded-pill">
                Reporting Boss
            </span>
        </h4>
        <p class="text-muted fs-13 mb-0">Daily attendance and punch activity of team members reporting directly to you</p>
    </div>
    
    <div class="col-lg-6 col-12 text-lg-end mt-3 mt-lg-0">
        <form method="GET" action="<?= $GLOBALS['base_url'] ?>/my-employees-test" class="d-inline-flex align-items-center gap-2 flex-wrap justify-content-lg-end">
            <div class="d-flex align-items-center gap-1 bg-white border rounded-3 px-3 py-1 shadow-sm">
                <label class="form-label fw-bold text-muted fs-12 mb-0">Date:</label>
                <input type="date" name="date" class="form-control form-control-sm border-0 py-1" 
                       value="<?= htmlspecialchars($selectedDate) ?>" onchange="this.form.submit()" style="max-width: 140px;">
            </div>
            
            <div class="input-group input-group-sm shadow-sm" style="max-width: 200px;">
                <input type="text" name="search" class="form-control rounded-start-3" 
                       placeholder="Search name / ID..." value="<?= htmlspecialchars($search ?? '') ?>">
                <button class="btn btn-primary" type="submit">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </div>

            <?php if (!$isToday || !empty($search)): ?>
                <a href="<?= $GLOBALS['base_url'] ?>/my-employees-test" class="btn btn-sm btn-outline-secondary rounded-3 px-2 py-1">
                    <i class="fa-solid fa-rotate-left me-1"></i> Today
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- 4 Summary Metric Cards -->
<div class="row g-3 mb-25">
    <div class="col-xl-3 col-md-6 col-12">
        <div class="card border-0 rounded-4 shadow-sm p-3 h-100 bg-white border-bottom border-primary border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fs-12 fw-semibold text-uppercase">Direct Subordinates</span>
                    <h3 class="mb-0 fw-bold mt-1 text-primary"><?= number_format($summary['total_team']) ?></h3>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width: 48px; height: 48px;">
                    <i class="fa-solid fa-users fs-5"></i>
                </div>
            </div>
            <div class="mt-2 text-muted fs-11">Reporting directly to your account</div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 col-12">
        <div class="card border-0 rounded-4 shadow-sm p-3 h-100 bg-white border-bottom border-success border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fs-12 fw-semibold text-uppercase">Present <?= $isToday ? 'Today' : '' ?></span>
                    <h3 class="mb-0 fw-bold mt-1 text-success">
                        <?= number_format($summary['present']) ?>
                        <?php if ($summary['late'] > 0): ?>
                            <span class="fs-12 text-warning fw-normal">(<?= $summary['late'] ?> Late)</span>
                        <?php endif; ?>
                    </h3>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-success-subtle text-success" style="width: 48px; height: 48px;">
                    <i class="fa-solid fa-user-check fs-5"></i>
                </div>
            </div>
            <div class="mt-2 text-muted fs-11">
                <?php if (!empty($summary['approved_late'])): ?>
                    <span class="text-success fw-semibold"><i class="fa-solid fa-check"></i> <?= $summary['approved_late'] ?> Late Waived</span>
                <?php else: ?>
                    Punched in at office
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 col-12">
        <div class="card border-0 rounded-4 shadow-sm p-3 h-100 bg-white border-bottom border-danger border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fs-12 fw-semibold text-uppercase">Absent</span>
                    <h3 class="mb-0 fw-bold mt-1 text-danger"><?= number_format($summary['absent']) ?></h3>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-danger-subtle text-danger" style="width: 48px; height: 48px;">
                    <i class="fa-solid fa-user-xmark fs-5"></i>
                </div>
            </div>
            <div class="mt-2 text-muted fs-11">No punch records found</div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 col-12">
        <div class="card border-0 rounded-4 shadow-sm p-3 h-100 bg-white border-bottom border-info border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fs-12 fw-semibold text-uppercase">On Leave / Off-Day</span>
                    <h3 class="mb-0 fw-bold mt-1 text-info"><?= number_format($summary['leave'] + $summary['off_days']) ?></h3>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-info-subtle text-info" style="width: 48px; height: 48px;">
                    <i class="fa-solid fa-calendar-day fs-5"></i>
                </div>
            </div>
            <div class="mt-2 text-muted fs-11"><?= $summary['leave'] ?> Leave • <?= $summary['off_days'] ?> Day Off/Holiday</div>
        </div>
    </div>
</div>

<!-- PENDING TEAM LATE APPROVAL REQUESTS SECTION -->
<?php if (!empty($team_late_requests)): ?>
    <div class="card border-0 rounded-4 shadow-sm mb-25 overflow-hidden border-start border-warning border-4 bg-white">
        <div class="card-header bg-warning-subtle py-3 px-4 border-0 d-flex align-items-center justify-content-between">
            <div>
                <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-warning"></i>
                    Team Late Approval Requests
                    <span class="badge bg-warning text-dark rounded-pill fs-11">
                        <?= count($team_late_requests) ?> Pending Your Recommendation
                    </span>
                </h6>
                <span class="text-muted fs-12">Review employee reasons and recommend to HR for final waiver approval</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light fs-12 text-uppercase text-secondary fw-bold">
                        <tr>
                            <th class="ps-4 py-3">Employee</th>
                            <th class="py-3">Date</th>
                            <th class="py-3 text-center">Punch Time & Late</th>
                            <th class="py-3">Reason Category & Description</th>
                            <th class="py-3 text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($team_late_requests as $tReq): ?>
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" 
                                             style="width: 36px; height: 36px; background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%); font-size: 13px;">
                                            <?= strtoupper(substr($tReq['employee_name'] ?? 'U', 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark fs-13"><?= htmlspecialchars($tReq['employee_name']) ?></div>
                                            <div class="fs-11 text-muted">
                                                ID: <?= htmlspecialchars($tReq['office_id'] ?? '—') ?> • <?= htmlspecialchars($tReq['designation_name'] ?? '') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="fs-13 fw-semibold text-secondary">
                                    <?= date('d M, Y', strtotime($tReq['attendance_date'])) ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-warning-subtle text-danger border border-warning-subtle fw-bold fs-12 px-2 py-1">
                                        +<?= $tReq['late_minutes'] ?> mins late
                                    </span>
                                    <?php if (!empty($tReq['punch_in_time'])): ?>
                                        <div class="fs-11 text-muted mt-1">In: <?= date('h:i A', strtotime($tReq['punch_in_time'])) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-primary border fs-11 fw-semibold mb-1">
                                        <?= htmlspecialchars($tReq['reason_type']) ?>
                                    </span>
                                    <div class="fs-12 text-dark" style="max-width: 340px;">
                                        <?= nl2br(htmlspecialchars($tReq['reason_description'] ?: 'No explanation provided.')) ?>
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-inline-flex gap-2">
                                        <a href="<?= $GLOBALS['base_url'] ?>/supervisor-approve-late/<?= $tReq['id'] ?>" 
                                           class="btn btn-sm btn-success rounded-pill px-3 py-1 fs-12 fw-semibold shadow-sm"
                                           onclick="return confirm('Do you recommend and approve this late request to HR for final waiver?')">
                                            <i class="fa-solid fa-check me-1"></i>Recommend
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 fs-12 fw-semibold"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#rejectLateModal<?= $tReq['id'] ?>">
                                            <i class="fa-solid fa-xmark me-1"></i>Reject
                                        </button>
                                    </div>

                                    <!-- Reject Remarks Modal -->
                                    <div class="modal fade" id="rejectLateModal<?= $tReq['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-4 border-0 shadow text-start">
                                                <div class="modal-header border-0 pb-0">
                                                    <h5 class="modal-title fw-bold text-danger">Reject Late Approval Request</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="<?= $GLOBALS['base_url'] ?>/supervisor-reject-late/<?= $tReq['id'] ?>" method="POST">
                                                    <div class="modal-body py-3">
                                                        <p class="fs-13 text-muted mb-2">
                                                            Are you sure you want to reject the late approval request for <strong><?= htmlspecialchars($tReq['employee_name']) ?></strong> on <strong><?= date('d M, Y', strtotime($tReq['attendance_date'])) ?></strong>?
                                                        </p>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold text-secondary fs-12">Rejection Reason / Remarks (Optional):</label>
                                                            <textarea name="remarks" class="form-control rounded-3" rows="3" placeholder="Provide reason for rejection..."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-0 pt-0">
                                                        <button type="button" class="btn btn-outline-secondary rounded-3 px-3 py-2 fs-13" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger rounded-3 px-4 py-2 fs-13 fw-semibold">Confirm Reject</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
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

<!-- SUBORDINATES ATTENDANCE TABLE -->
<div class="card border-0 rounded-4 shadow-sm mb-25 bg-white">
    <div class="card-header bg-white border-0 pt-4 px-4 d-flex align-items-center justify-content-between">
        <div>
            <h5 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-clipboard-user me-2 text-primary"></i>
                Attendance Logs for <?= date('l, d M Y', strtotime($selectedDate)) ?>
            </h5>
            <span class="text-muted fs-13">Showing team members reporting to your profile</span>
        </div>
        <span class="badge bg-light text-muted border px-3 py-1 fs-12">
            Total: <?= count($subordinates) ?> Employee(s)
        </span>
    </div>

    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light fs-12 text-secondary text-uppercase fw-bold">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Employee</th>
                        <th>Department &amp; Shift</th>
                        <th class="text-center">Punch In</th>
                        <th class="text-center">Punch Out</th>
                        <th class="text-center">Working Hours</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Late Waiver</th>
                    </tr>
                </thead>
                <tbody class="fs-13">
                    <?php if (empty($subordinates)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-user-group fs-1 mb-3 d-block text-secondary opacity-50"></i>
                                <?php if (!empty($search)): ?>
                                    No reporting employees matched your search query.
                                <?php else: ?>
                                    <strong>You currently have no reporting employees assigned under you.</strong>
                                    <div class="fs-12 text-muted mt-1">If team members should report to you, HR can assign your user account as their Reporting Boss.</div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($subordinates as $i => $row): ?>
                            <?php
                            $status = $row['today_status'];
                            $badgeClass = match($status) {
                                'Present'  => 'bg-success text-white',
                                'Late'     => 'bg-warning text-dark',
                                'Half-Day' => 'bg-warning-subtle text-warning border border-warning',
                                'On Leave' => 'bg-info text-white',
                                'Day Off'  => 'bg-secondary-subtle text-secondary border',
                                'Holiday'  => 'bg-dark text-white',
                                default    => 'bg-danger text-white' // Absent
                            };

                            $inDisplay = (!empty($row['punch_in_time'])) ? date('h:i A', strtotime($row['punch_in_time'])) : '—';
                            $outDisplay = (!empty($row['punch_out_time'])) ? date('h:i A', strtotime($row['punch_out_time'])) : '—';
                            $inColor = (!empty($row['late_minutes']) && $row['late_minutes'] > 0) ? 'text-danger' : 'text-dark';
                            $isLateWaived = !empty($row['is_late_approved']);
                            ?>
                            <tr>
                                <td class="text-muted fw-semibold"><?= $i + 1 ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" 
                                             style="width: 38px; height: 38px; background: linear-gradient(135deg, #10b981 0%, #06b6d4 100%); font-size: 14px;">
                                            <?= strtoupper(substr($row['employee_name'] ?? 'U', 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($row['employee_name']) ?></div>
                                            <div class="text-muted fs-11">
                                                ID: <?= htmlspecialchars($row['office_id'] ?: '—') ?> • <?= htmlspecialchars($row['designation_name'] ?: '') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark fs-12"><?= htmlspecialchars($row['department_name'] ?: '—') ?></div>
                                    <div class="text-muted fs-11">
                                        <?= htmlspecialchars($row['shift_name'] ?: 'Regular Shift') ?>
                                        <?php if (!empty($row['shift_start_time'])): ?>
                                            (<?= date('h:i A', strtotime($row['shift_start_time'])) ?>)
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold <?= $inColor ?> fs-13"><?= $inDisplay ?></span>
                                    <?php if (!empty($row['late_minutes']) && $row['late_minutes'] > 0): ?>
                                        <span class="badge bg-warning-subtle text-danger border border-warning-subtle fs-10 d-block mt-1">
                                            +<?= $row['late_minutes'] ?>m late
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center fw-bold text-dark fs-13">
                                    <?= $outDisplay ?>
                                </td>
                                <td class="text-center fw-semibold text-dark fs-13">
                                    <?= htmlspecialchars($row['working_hours'] ?: '—') ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($isLateWaived): ?>
                                        <span class="badge rounded-pill px-3 py-1 fs-11 fw-bold text-uppercase bg-success text-white">
                                            Late (Waived)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill px-3 py-1 fs-11 fw-bold text-uppercase <?= $badgeClass ?>">
                                            <?= htmlspecialchars($status) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($isLateWaived): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1 fs-11 fw-bold">
                                            <i class="fa-solid fa-check me-1"></i>Waived by HR
                                        </span>
                                    <?php elseif (!empty($row['late_minutes']) && $row['late_minutes'] > 0): ?>
                                        <span class="badge bg-light text-muted border rounded-pill px-2 py-1 fs-11">
                                            Unwaived
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted fs-12">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

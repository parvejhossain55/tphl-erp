<?php
$page_title = "My Leaves (Test)";
$page = 'My Leaves (Test)';
$show_breadcrumb = true;
?>

<!-- Header Section with Title & Actions -->
<div class="row align-items-center mb-25">
    <div class="col-md-7 col-12">
        <h4 class="mb-1 fw-bold text-dark d-flex align-items-center gap-2">
            <i class="fa-solid fa-plane-departure text-primary"></i>
            My Leaves
            <span class="badge bg-success-subtle text-success border border-success-subtle fs-12 px-3 py-1 rounded-pill">
                Test Module
            </span>
        </h4>
        <p class="text-muted fs-13 mb-0">Check live leave quotas, track 2-level approvals, and apply for leaves</p>
    </div>
    <div class="col-md-5 col-12 text-md-end mt-3 mt-md-0">
        <button type="button" class="btn btn-primary px-4 py-2 fw-semibold rounded-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#selfLeaveModal">
            <i class="fa-solid fa-plus me-1"></i> Apply for Leave
        </button>
    </div>
</div>

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

<!-- Quick Employee Profile & Reporting Boss Banner -->
<div class="card__wrapper border-0 shadow-sm rounded-4 p-4 bg-white mb-25">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" 
                 style="width: 52px; height: 52px; background: linear-gradient(135deg, #10b981 0%, #06b6d4 100%); font-size: 20px;">
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
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill fs-12">
                <i class="fa-solid fa-user-tie text-primary me-1"></i>
                Reporting Boss: 
                <strong><?= !empty($employee['reporting_boss_name']) ? htmlspecialchars($employee['reporting_boss_name']) : 'Direct to HR' ?></strong>
            </span>
        </div>
    </div>
</div>

<!-- 4 Core Leave Category Balance Cards -->
<div class="row g-3 mb-25">
    <?php
    // Find Sick, Casual, Duty, LWP by name or ID
    $sick = null; $casual = null; $duty = null; $lwp = null;
    foreach ($balances as $b) {
        $n = strtolower($b['name']);
        if (str_contains($n, 'sick')) $sick = $b;
        elseif (str_contains($n, 'casual')) $casual = $b;
        elseif (str_contains($n, 'duty')) $duty = $b;
        elseif (str_contains($n, 'lwp') || str_contains($n, 'without pay')) $lwp = $b;
    }
    // Fallbacks if not mapped by name
    $sick = $sick ?? ($balances[2] ?? ['name' => 'Sick Leave', 'remaining_days' => 0, 'used_days' => 0, 'total_days' => 0]);
    $casual = $casual ?? ($balances[3] ?? ['name' => 'Casual Leave', 'remaining_days' => 0, 'used_days' => 0, 'total_days' => 0]);
    $duty = $duty ?? ($balances[4] ?? ['name' => 'Duty Leave', 'remaining_days' => 0, 'used_days' => 0, 'total_days' => 0]);
    $lwp = $lwp ?? ($balances[5] ?? ['name' => 'Leave Without Pay', 'remaining_days' => 0, 'used_days' => 0, 'total_days' => 0]);
    ?>

    <!-- Sick Leave Card -->
    <div class="col-xl-3 col-md-6 col-12">
        <div class="card border-0 rounded-4 shadow-sm p-3 h-100 bg-white border-bottom border-success border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fs-12 fw-semibold text-uppercase">Sick Leave</span>
                    <h3 class="mb-0 fw-bold mt-1 text-success"><?= $sick['remaining_days'] ?> <span class="fs-13 fw-normal text-muted">days left</span></h3>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-success-subtle text-success" style="width: 46px; height: 46px;">
                    <i class="fa-solid fa-notes-medical fs-5"></i>
                </div>
            </div>
            <div class="mt-3 fs-12 d-flex justify-content-between text-muted">
                <span>Quota: <strong><?= $sick['total_days'] ?></strong></span>
                <span>Used: <strong class="text-danger"><?= $sick['used_days'] ?></strong></span>
            </div>
        </div>
    </div>

    <!-- Casual Leave Card -->
    <div class="col-xl-3 col-md-6 col-12">
        <div class="card border-0 rounded-4 shadow-sm p-3 h-100 bg-white border-bottom border-primary border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fs-12 fw-semibold text-uppercase">Casual Leave</span>
                    <h3 class="mb-0 fw-bold mt-1 text-primary"><?= $casual['remaining_days'] ?> <span class="fs-13 fw-normal text-muted">days left</span></h3>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width: 46px; height: 46px;">
                    <i class="fa-solid fa-umbrella-beach fs-5"></i>
                </div>
            </div>
            <div class="mt-3 fs-12 d-flex justify-content-between text-muted">
                <span>Quota: <strong><?= $casual['total_days'] ?></strong></span>
                <span>Used: <strong class="text-danger"><?= $casual['used_days'] ?></strong></span>
            </div>
        </div>
    </div>

    <!-- Duty Leave Card (Comp-off) -->
    <div class="col-xl-3 col-md-6 col-12">
        <div class="card border-0 rounded-4 shadow-sm p-3 h-100 bg-white border-bottom border-info border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fs-12 fw-semibold text-uppercase">Duty Leave (Comp-off)</span>
                    <h3 class="mb-0 fw-bold mt-1 text-info"><?= $duty['remaining_days'] ?> <span class="fs-13 fw-normal text-muted">available</span></h3>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-info-subtle text-info" style="width: 46px; height: 46px;">
                    <i class="fa-solid fa-business-time fs-5"></i>
                </div>
            </div>
            <div class="mt-3 fs-12 d-flex justify-content-between text-muted">
                <span>Earned on Holidays: <strong><?= $duty['remaining_days'] + $duty['used_days'] ?></strong></span>
                <span>Used: <strong class="text-danger"><?= $duty['used_days'] ?></strong></span>
            </div>
        </div>
    </div>

    <!-- LWP Card (Leave Without Pay) -->
    <div class="col-xl-3 col-md-6 col-12">
        <div class="card border-0 rounded-4 shadow-sm p-3 h-100 bg-white border-bottom border-warning border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fs-12 fw-semibold text-uppercase">Leave Without Pay (LWP)</span>
                    <h3 class="mb-0 fw-bold mt-1 text-warning"><?= $lwp['used_days'] ?> <span class="fs-13 fw-normal text-muted">days taken</span></h3>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-warning-subtle text-warning" style="width: 46px; height: 46px;">
                    <i class="fa-solid fa-file-invoice-dollar fs-5"></i>
                </div>
            </div>
            <div class="mt-3 fs-12 text-muted">
                <span>Subject to payroll deduction</span>
            </div>
        </div>
    </div>
</div>

<!-- SUPERVISOR / TEAM APPROVALS (If current user has subordinate pending requests) -->
<?php if (!empty($team_requests)): ?>
    <div class="card border-0 rounded-4 shadow-sm mb-25 border-start border-primary border-4">
        <div class="card-header bg-white border-0 pt-4 px-4 d-flex align-items-center justify-content-between">
            <div>
                <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-users-gear text-primary"></i>
                    Team Leave Requests Awaiting Your Recommendation
                    <span class="badge bg-danger rounded-pill fs-11 px-2"><?= count($team_requests) ?></span>
                </h5>
                <p class="text-muted fs-13 mb-0">As reporting supervisor, approve or reject applications from your team before forwarding to HR.</p>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light fs-12 text-secondary text-uppercase">
                        <tr>
                            <th>Employee</th>
                            <th>Leave Type</th>
                            <th>Duration</th>
                            <th class="text-center">Working Days</th>
                            <th>Reason</th>
                            <th class="text-end">1-Click Action</th>
                        </tr>
                    </thead>
                    <tbody class="fs-13">
                        <?php foreach ($team_requests as $tr): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($tr['employee_name']) ?></div>
                                    <div class="text-muted fs-11">ID: <?= htmlspecialchars($tr['office_id'] ?: '—') ?> • <?= htmlspecialchars($tr['designation_name'] ?: '') ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border fw-semibold">
                                        <?= htmlspecialchars($tr['leave_type_name']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">
                                        <?= date('d M Y', strtotime($tr['start_date'])) ?>
                                        <i class="fa-solid fa-arrow-right-long text-muted mx-1 fs-11"></i>
                                        <?= date('d M Y', strtotime($tr['end_date'])) ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fs-12 fw-bold">
                                        <?= $tr['requested_days'] ?> day(s)
                                    </span>
                                </td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 220px;" title="<?= htmlspecialchars($tr['reason'] ?: 'No reason provided') ?>">
                                        <?= htmlspecialchars($tr['reason'] ?: '—') ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="<?= $GLOBALS['base_url'] ?>/supervisor-approve-leave/<?= $tr['id'] ?>" 
                                       class="btn btn-sm btn-success px-3 fw-semibold rounded-pill me-1"
                                       onclick="return confirm('Recommend and approve this leave request for <?= htmlspecialchars($tr['employee_name']) ?> to HR?');">
                                        <i class="fa-solid fa-check me-1"></i> Recommend
                                    </a>
                                    <a href="<?= $GLOBALS['base_url'] ?>/supervisor-reject-leave/<?= $tr['id'] ?>" 
                                       class="btn btn-sm btn-outline-danger px-3 fw-semibold rounded-pill"
                                       onclick="return confirm('Reject this leave request for <?= htmlspecialchars($tr['employee_name']) ?>? It will NOT go to HR.');">
                                        <i class="fa-solid fa-xmark me-1"></i> Reject
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- MY LEAVE APPLICATIONS TABLE -->
<div class="card border-0 rounded-4 shadow-sm mb-25">
    <div class="card-header bg-white border-0 pt-4 px-4 d-flex align-items-center justify-content-between">
        <h5 class="fw-bold text-dark mb-0">
            <i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>
            My Leave Applications
        </h5>
        <span class="badge bg-light text-muted border px-3 py-1 fs-12">Total: <?= count($my_leaves) ?></span>
    </div>

    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light fs-12 text-secondary text-uppercase">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Leave Type</th>
                        <th>Duration</th>
                        <th class="text-center">Days</th>
                        <th>Reason</th>
                        <th>1st Level (Supervisor)</th>
                        <th>2nd Level (HR)</th>
                        <th class="text-center">Overall Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody class="fs-13">
                    <?php if (empty($my_leaves)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-calendar-xmark fs-2 mb-2 d-block text-secondary opacity-50"></i>
                                No leave applications found. Click <strong>"Apply for Leave"</strong> to submit your first request.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($my_leaves as $i => $row): ?>
                            <?php
                            // Overall status badge
                            $ovStatus = strtolower($row['approval_status']);
                            $overallBadge = match($ovStatus) {
                                'approved'           => 'bg-success text-white',
                                'pending_supervisor' => 'bg-warning-subtle text-warning border border-warning-subtle',
                                'pending_hr'         => 'bg-info-subtle text-info border border-info-subtle',
                                'pending'            => 'bg-warning-subtle text-warning border border-warning-subtle',
                                'rejected'           => 'bg-danger text-white',
                                'cancelled'          => 'bg-secondary text-white',
                                default              => 'bg-light text-dark border'
                            };

                            // Supervisor status display
                            $supStatus = strtolower($row['supervisor_status'] ?? 'pending');
                            $supBadge = match($supStatus) {
                                'approved' => '<span class="badge bg-success-subtle text-success fs-11"><i class="fa-solid fa-check me-1"></i>Approved</span>',
                                'rejected' => '<span class="badge bg-danger-subtle text-danger fs-11"><i class="fa-solid fa-xmark me-1"></i>Rejected</span>',
                                default    => '<span class="badge bg-warning-subtle text-warning fs-11"><i class="fa-solid fa-hourglass-half me-1"></i>Pending</span>'
                            };
                            if (empty($row['reporting_user_id'])) {
                                $supBadge = '<span class="badge bg-light text-muted border fs-11">Bypassed (Direct HR)</span>';
                            }

                            // HR status display
                            $hrStatus = strtolower($row['hr_status'] ?? 'pending');
                            $hrBadge = match($hrStatus) {
                                'approved' => '<span class="badge bg-success-subtle text-success fs-11"><i class="fa-solid fa-check-double me-1"></i>Approved</span>',
                                'rejected' => '<span class="badge bg-danger-subtle text-danger fs-11"><i class="fa-solid fa-xmark me-1"></i>Rejected</span>',
                                default    => '<span class="badge bg-secondary-subtle text-secondary fs-11">Awaiting</span>'
                            };
                            ?>
                            <tr>
                                <td class="text-muted fw-semibold"><?= $i + 1 ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border fw-semibold">
                                        <?= htmlspecialchars($row['leave_type_name']) ?>
                                    </span>
                                    <?php if ($row['is_paid'] == 0): ?>
                                        <span class="badge bg-warning-subtle text-danger border border-warning-subtle fs-10 ms-1">Unpaid</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">
                                        <?= date('d M Y', strtotime($row['start_date'])) ?>
                                        <i class="fa-solid fa-arrow-right-long text-muted mx-1 fs-11"></i>
                                        <?= date('d M Y', strtotime($row['end_date'])) ?>
                                    </div>
                                    <div class="fs-11 text-muted">Applied: <?= date('d M Y', strtotime($row['created_at'])) ?></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fs-12 fw-bold">
                                        <?= $row['requested_days'] ?> day(s)
                                    </span>
                                </td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 170px;" title="<?= htmlspecialchars($row['reason'] ?: 'No reason provided') ?>">
                                        <?= htmlspecialchars($row['reason'] ?: '—') ?>
                                    </span>
                                </td>
                                <td>
                                    <?= $supBadge ?>
                                    <?php if (!empty($row['supervisor_name'])): ?>
                                        <div class="fs-11 text-muted mt-1"><?= htmlspecialchars($row['supervisor_name']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $hrBadge ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge rounded-pill px-3 py-1 fs-11 fw-bold text-uppercase <?= $overallBadge ?>">
                                        <?= str_replace('_', ' ', $row['approval_status']) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php if (in_array($ovStatus, ['pending_supervisor', 'pending_hr', 'pending'])): ?>
                                        <a href="<?= $GLOBALS['base_url'] ?>/cancel-leave-test/<?= $row['id'] ?>" 
                                           class="btn btn-sm btn-outline-danger px-3 rounded-pill fw-semibold"
                                           onclick="return confirm('Are you sure you want to cancel this leave application?');">
                                            <i class="fa-solid fa-trash-can me-1"></i> Cancel
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted fs-12">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- APPLY LEAVE MODAL -->
<div class="modal fade" id="selfLeaveModal" tabindex="-1" aria-labelledby="selfLeaveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <div>
                    <h5 class="modal-title fw-bold text-dark" id="selfLeaveModalLabel">
                        <i class="fa-solid fa-paper-plane text-primary me-2"></i>Apply for Leave
                    </h5>
                    <p class="text-muted fs-13 mb-0">2-level approval will be routed to your Reporting Boss, then HR.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form method="POST" action="<?= $GLOBALS['base_url'] ?>/apply-leave-test" id="selfLeaveForm">
                <div class="modal-body px-4 py-3">
                    
                    <!-- Reporting Boss Preview Notice -->
                    <div class="p-3 bg-light rounded-3 mb-3 border d-flex align-items-center gap-2 fs-13 text-muted">
                        <i class="fa-solid fa-circle-info text-primary fs-5"></i>
                        <div>
                            1st Approver: 
                            <strong class="text-dark">
                                <?= !empty($employee['reporting_boss_name']) ? htmlspecialchars($employee['reporting_boss_name']) : 'Direct to HR (No Reporting Boss)' ?>
                            </strong>
                        </div>
                    </div>

                    <!-- Leave Type Selection -->
                    <div class="mb-3">
                        <label class="form-label fw-bold fs-13 text-dark">Select Leave Category <span class="text-danger">*</span></label>
                        <select name="leave_type_id" id="self_leave_type_id" class="form-select rounded-3 py-2" required onchange="handleLeaveTypeChange()">
                            <option value="">-- Choose Leave Category --</option>
                            <?php foreach ($balances as $b): ?>
                                <option value="<?= $b['type_id'] ?>" 
                                        data-remaining="<?= $b['remaining_days'] ?>"
                                        data-paid="<?= $b['is_paid'] ?>">
                                    <?= htmlspecialchars($b['name']) ?> 
                                    (<?= $b['remaining_days'] ?> days remaining <?= $b['is_paid'] == 0 ? '• Unpaid' : '' ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="balanceAlertNote" class="form-text fs-12 mt-1"></div>
                    </div>

                    <!-- Date Selection -->
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold fs-13 text-dark">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="self_start_date" class="form-control rounded-3 py-2" required onchange="calculateEstimatedDays()">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold fs-13 text-dark">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" id="self_end_date" class="form-control rounded-3 py-2" required onchange="calculateEstimatedDays()">
                        </div>
                    </div>

                    <!-- Live Working Days Calc Note -->
                    <div id="workingDaysNote" class="alert alert-info py-2 px-3 rounded-3 fs-12 d-none mb-3">
                        <i class="fa-solid fa-calculator me-1"></i>
                        <span id="workingDaysText">Calculating working days...</span>
                    </div>

                    <!-- Reason -->
                    <div class="mb-2">
                        <label class="form-label fw-bold fs-13 text-dark">Reason for Leave <span class="text-danger">*</span></label>
                        <textarea name="reason" rows="3" class="form-control rounded-3" placeholder="State your reason for leave..." required></textarea>
                    </div>
                </div>

                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-4 fw-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">
                        <i class="fa-solid fa-paper-plane me-1"></i> Submit Application
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function handleLeaveTypeChange() {
    const sel = document.getElementById('self_leave_type_id');
    const opt = sel.options[sel.selectedIndex];
    const note = document.getElementById('balanceAlertNote');

    if (!opt || !opt.value) {
        note.innerHTML = '';
        return;
    }

    const rem = parseInt(opt.getAttribute('data-remaining') || '0');
    const isPaid = opt.getAttribute('data-paid') === '1';

    if (rem <= 0 && isPaid) {
        note.innerHTML = '<span class="text-warning"><i class="fa-solid fa-triangle-exclamation me-1"></i> Balance is 0. If you apply, it will automatically route to <strong>Leave Without Pay (LWP)</strong>.</span>';
    } else {
        note.innerHTML = `<span class="text-success"><i class="fa-solid fa-circle-check me-1"></i> ${rem} day(s) available in this category.</span>`;
    }
}

function calculateEstimatedDays() {
    const startVal = document.getElementById('self_start_date').value;
    const endVal = document.getElementById('self_end_date').value;
    const noteBox = document.getElementById('workingDaysNote');
    const noteText = document.getElementById('workingDaysText');

    if (!startVal || !endVal) {
        noteBox.classList.add('d-none');
        return;
    }

    const s = new Date(startVal);
    const e = new Date(endVal);

    if (e < s) {
        noteBox.classList.remove('d-none', 'alert-info');
        noteBox.classList.add('alert-danger');
        noteText.innerHTML = 'End date cannot be earlier than start date.';
        return;
    }

    // Client-side estimate: loop days and exclude Friday (day 5)
    let cur = new Date(s);
    let workingDays = 0;
    while (cur <= e) {
        const dayOfWeek = cur.getDay(); // 0 is Sunday, 5 is Friday
        if (dayOfWeek !== 5) {
            workingDays++;
        }
        cur.setDate(cur.getDate() + 1);
    }

    noteBox.classList.remove('d-none', 'alert-danger');
    noteBox.classList.add('alert-info');
    noteText.innerHTML = `Estimated: <strong>${workingDays} working day(s)</strong> (excluding Friday off-days).`;
}
</script>

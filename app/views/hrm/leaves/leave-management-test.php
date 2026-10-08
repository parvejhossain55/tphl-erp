<?php
$page_title = "Leave Management (Test)";
$page = 'Leave Management (Test)';
$show_breadcrumb = true;
?>

<!-- Header Section with Title & Actions -->
<div class="row align-items-center mb-25">
    <div class="col-md-7 col-12">
        <h4 class="mb-1 fw-bold text-dark d-flex align-items-center gap-2">
            <i class="fa-solid fa-plane-departure text-primary"></i>
            Leave Management
            <span class="badge bg-success-subtle text-success border border-success-subtle fs-12 px-2 py-1 rounded-pill">
                Test Module
            </span>
        </h4>
        <p class="text-muted fs-13 mb-0">Review employee leave requests, approve/reject with 1-click, and track balances</p>
    </div>
    <div class="col-md-5 col-12 text-md-end mt-3 mt-md-0">
        <button type="button" class="btn btn-primary px-4 fw-semibold rounded-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#assignLeaveModal">
            <i class="fa-solid fa-plus me-1"></i> Assign / Grant Leave
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

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-20 shadow-sm" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($_SESSION['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- 4 Summary Metric Cards -->
<div class="row g-3 mb-25">
    <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-12">
        <div class="card border-0 rounded-4 shadow-sm p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fs-12 fw-semibold text-uppercase">Pending Requests</span>
                    <h3 class="mb-0 fw-bold mt-1 text-warning"><?= number_format($pending_count) ?></h3>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-warning-subtle text-warning" style="width: 48px; height: 48px;">
                    <i class="fa-solid fa-hourglass-half fs-5"></i>
                </div>
            </div>
            <div class="mt-2 text-muted fs-11">Awaiting HR / Management review</div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-12">
        <div class="card border-0 rounded-4 shadow-sm p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fs-12 fw-semibold text-uppercase">Approved Leaves</span>
                    <h3 class="mb-0 fw-bold mt-1 text-success"><?= number_format($approved_count) ?></h3>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-success-subtle text-success" style="width: 48px; height: 48px;">
                    <i class="fa-solid fa-circle-check fs-5"></i>
                </div>
            </div>
            <div class="mt-2 text-muted fs-11">Deducted from employee quota</div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-12">
        <div class="card border-0 rounded-4 shadow-sm p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fs-12 fw-semibold text-uppercase">Today On Leave</span>
                    <h3 class="mb-0 fw-bold mt-1 text-primary"><?= number_format($today_on_leave_count) ?></h3>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width: 48px; height: 48px;">
                    <i class="fa-solid fa-user-clock fs-5"></i>
                </div>
            </div>
            <div class="mt-2 text-muted fs-11"><?= date('D, d M Y') ?> (Reflected in Attendance)</div>
        </div>
    </div>

    <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-12">
        <div class="card border-0 rounded-4 shadow-sm p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fs-12 fw-semibold text-uppercase">Rejected / Cancelled</span>
                    <h3 class="mb-0 fw-bold mt-1 text-danger"><?= number_format($rejected_count) ?></h3>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-danger-subtle text-danger" style="width: 48px; height: 48px;">
                    <i class="fa-solid fa-ban fs-5"></i>
                </div>
            </div>
            <div class="mt-2 text-muted fs-11">Balances refunded automatically</div>
        </div>
    </div>
</div>

<!-- Tabs: Applications List vs Leave Balances -->
<div class="card border-0 rounded-4 shadow-sm mb-25">
    <div class="card-header bg-white border-0 pt-3 px-4">
        <ul class="nav nav-pills card-header-pills gap-2" id="leaveTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active rounded-3 px-4 fw-semibold" id="applications-tab" data-bs-toggle="tab" data-bs-target="#applications-pane" type="button" role="tab">
                    <i class="fa-solid fa-list-check me-2"></i>Leave Applications (<?= count($leaves) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-3 px-4 fw-semibold" id="balances-tab" data-bs-toggle="tab" data-bs-target="#balances-pane" type="button" role="tab">
                    <i class="fa-solid fa-wallet me-2"></i>Employee Balances (<?= count($balances) ?>)
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body p-4">
        <div class="tab-content" id="leaveTabContent">

            <!-- TAB 1: APPLICATIONS LIST -->
            <div class="tab-pane fade show active" id="applications-pane" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="leaveApplicationsTable">
                        <thead class="table-light fs-13 text-secondary text-uppercase">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Leave Type</th>
                                <th>Duration</th>
                                <th class="text-center">Working Days</th>
                                <th>Reason</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="fs-13">
                            <?php if (empty($leaves)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-folder-open fs-2 mb-2 d-block text-secondary opacity-50"></i>
                                        No leave applications found. Click <strong>"Assign / Grant Leave"</strong> to record a leave.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($leaves as $idx => $row): ?>
                                    <?php
                                    $rawStatus = strtolower($row['approval_status']);
                                    $statusBadge = match($rawStatus) {
                                        'approved'           => 'bg-success-subtle text-success border border-success-subtle',
                                        'pending', 'pending_hr' => 'bg-warning-subtle text-warning border border-warning-subtle',
                                        'pending_supervisor' => 'bg-info-subtle text-info border border-info-subtle',
                                        'rejected'           => 'bg-danger-subtle text-danger border border-danger-subtle',
                                        'cancelled'          => 'bg-secondary-subtle text-secondary border',
                                        default              => 'bg-secondary-subtle text-secondary'
                                    };
                                    $statusLabel = match($rawStatus) {
                                        'pending_hr'         => 'Pending HR',
                                        'pending_supervisor' => 'Pending Boss',
                                        'approved'           => 'Approved',
                                        'rejected'           => 'Rejected',
                                        'cancelled'          => 'Cancelled',
                                        default              => ucfirst(str_replace('_', ' ', $row['approval_status']))
                                    };
                                    ?>
                                    <tr>
                                        <td class="text-muted fw-semibold"><?= $idx + 1 ?></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($row['employee_name']) ?></div>
                                            <div class="text-muted fs-11">ID: <?= htmlspecialchars($row['office_id'] ?: 'N/A') ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($row['department_name'] ?: '—') ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border fw-semibold">
                                                <?= htmlspecialchars($row['leave_type_name']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark">
                                                <?= date('d M Y', strtotime($row['start_date'])) ?>
                                                <i class="fa-solid fa-arrow-right-long text-muted mx-1 fs-11"></i>
                                                <?= date('d M Y', strtotime($row['end_date'])) ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fs-12 fw-bold">
                                                <?= $row['requested_days'] ?> day(s)
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-truncate d-inline-block" style="max-width: 180px;" title="<?= htmlspecialchars($row['reason'] ?: 'No reason provided') ?>">
                                                <?= htmlspecialchars($row['reason'] ?: '—') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge rounded-pill px-3 py-1 fs-11 fw-bold text-uppercase <?= $statusBadge ?>">
                                                <?= htmlspecialchars($statusLabel) ?>
                                            </span>
                                            <?php if (!empty($row['supervisor_name'])): ?>
                                                <div class="fs-10 text-muted mt-1" title="Recommended by Reporting Boss">
                                                    <i class="fa-solid fa-user-check text-success"></i> <?= htmlspecialchars($row['supervisor_name']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if (in_array($rawStatus, ['pending', 'pending_hr'])): ?>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="<?= $GLOBALS['base_url'] ?>/approve-leave-test/<?= $row['id'] ?>" 
                                                       class="btn btn-success px-3 fw-semibold rounded-start-3 shadow-sm"
                                                       onclick="return confirm('Are you sure you want to APPROVE this leave request for <?= htmlspecialchars($row['employee_name']) ?>?');">
                                                        <i class="fa-solid fa-check me-1"></i> Approve
                                                    </a>
                                                    <a href="<?= $GLOBALS['base_url'] ?>/reject-leave-test/<?= $row['id'] ?>" 
                                                       class="btn btn-outline-danger px-3 fw-semibold rounded-end-3"
                                                       onclick="return confirm('Are you sure you want to REJECT this leave request?');">
                                                        <i class="fa-solid fa-xmark me-1"></i> Reject
                                                    </a>
                                                </div>
                                            <?php elseif ($rawStatus === 'pending_supervisor'): ?>
                                                <span class="badge bg-light text-muted border fs-11" title="Waiting for 1st Tier Supervisor Recommendation">
                                                    <i class="fa-regular fa-clock me-1 text-warning"></i>Waiting Boss
                                                </span>
                                            <?php elseif ($rawStatus === 'approved'): ?>
                                                <a href="<?= $GLOBALS['base_url'] ?>/reject-leave-test/<?= $row['id'] ?>" 
                                                   class="btn btn-outline-danger btn-sm px-3 fw-semibold rounded-3"
                                                   onclick="return confirm('Rejecting this approved leave will REFUND the days back to the employee\'s balance. Proceed?');">
                                                    <i class="fa-solid fa-rotate-left me-1"></i> Cancel & Refund
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

            <!-- TAB 2: EMPLOYEE BALANCES -->
            <div class="tab-pane fade" id="balances-pane" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="leaveBalancesTable">
                        <thead class="table-light fs-13 text-secondary text-uppercase">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Employee Name</th>
                                <th>Department</th>
                                <th>Leave Type</th>
                                <th class="text-center">Yearly Quota</th>
                                <th class="text-center">Remaining Balance</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="fs-13">
                            <?php if (empty($balances)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        No balance records found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($balances as $bIdx => $b): ?>
                                    <?php 
                                    $quota = (int)$b['quota_days'];
                                    $rem   = (int)$b['remaining_days'];
                                    $pct   = $quota > 0 ? round(($rem / $quota) * 100) : 0;
                                    $colorClass = $rem > 5 ? 'text-success' : ($rem > 0 ? 'text-warning' : 'text-danger');
                                    ?>
                                    <tr>
                                        <td class="text-muted fw-semibold"><?= $bIdx + 1 ?></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($b['employee_name']) ?></div>
                                            <div class="text-muted fs-11">ID: <?= htmlspecialchars($b['office_id'] ?: 'N/A') ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($b['department_name'] ?: '—') ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border fw-semibold">
                                                <?= htmlspecialchars($b['leave_type_name']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center fw-semibold text-secondary">
                                            <?= $quota ?> days
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-bold fs-14 <?= $colorClass ?>">
                                                <?= $rem ?> days
                                            </span>
                                            <span class="text-muted fs-11">/ <?= $quota ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($rem > 0): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fs-11 rounded-pill">
                                                    Available
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 fs-11 rounded-pill">
                                                    Exhausted
                                                </span>
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
    </div>
</div>

<!-- MODAL: ASSIGN / GRANT LEAVE -->
<div class="modal fade" id="assignLeaveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="fa-solid fa-calendar-plus text-primary"></i>
                    Assign / Grant Employee Leave
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= $GLOBALS['base_url'] ?>/store-leave-test" method="POST" id="assignLeaveForm">
                <div class="modal-body py-4 px-4">
                    <div class="row g-3">
                        <!-- Employee Select -->
                        <div class="col-md-6 col-12">
                            <label class="form-label fw-bold fs-13 text-secondary">Select Employee <span class="text-danger">*</span></label>
                            <select name="employee_id" id="modalEmployeeSelect" class="form-select form-control select2 rounded-3 py-2" required>
                                <option value="">-- Choose Employee --</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?= $emp['id'] ?>">
                                        <?= htmlspecialchars($emp['full_name']) ?> (<?= htmlspecialchars($emp['office_id'] ?: 'No ID') ?>) - <?= htmlspecialchars($emp['department_name'] ?: 'General') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Leave Type Select -->
                        <div class="col-md-6 col-12">
                            <label class="form-label fw-bold fs-13 text-secondary">Leave Type <span class="text-danger">*</span></label>
                            <select name="leave_type_id" id="modalLeaveTypeSelect" class="form-select rounded-3 py-2" required>
                                <option value="">-- Choose Leave Type --</option>
                                <?php foreach ($leave_types as $lt): ?>
                                    <option value="<?= $lt['id'] ?>" data-total="<?= $lt['total_days'] ?>">
                                        <?= htmlspecialchars($lt['name']) ?> (<?= $lt['total_days'] ?> Days Quota)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="mt-1 fs-11 text-muted" id="balanceDisplayHint">
                                Select employee and leave type to view remaining days.
                            </div>
                        </div>

                        <!-- Start Date -->
                        <div class="col-md-6 col-12">
                            <label class="form-label fw-bold fs-13 text-secondary">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="modalStartDate" class="form-control rounded-3 py-2" required value="<?= date('Y-m-d') ?>">
                        </div>

                        <!-- End Date -->
                        <div class="col-md-6 col-12">
                            <label class="form-label fw-bold fs-13 text-secondary">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" id="modalEndDate" class="form-control rounded-3 py-2" required value="<?= date('Y-m-d') ?>">
                        </div>

                        <!-- Working Days Preview Info -->
                        <div class="col-12">
                            <div class="p-3 bg-light rounded-3 d-flex align-items-center justify-content-between border">
                                <div class="fs-12 text-secondary">
                                    <i class="fa-solid fa-circle-info text-primary me-1"></i>
                                    <strong>Calculated Leave Days:</strong> Weekly Day-Off (Friday) is automatically excluded from quota.
                                </div>
                                <div>
                                    <span class="badge bg-primary text-white fs-13 px-3 py-1 rounded-pill" id="calculatedDaysBadge">
                                        1 Working Day
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Initial Status -->
                        <div class="col-md-6 col-12">
                            <label class="form-label fw-bold fs-13 text-secondary">Approval Status</label>
                            <select name="approval_status" class="form-select rounded-3 py-2">
                                <option value="approved" selected>Approved (Immediate)</option>
                                <option value="pending">Pending (Awaiting Review)</option>
                            </select>
                            <div class="fs-11 text-muted mt-1">Direct HR assignment is approved immediately.</div>
                        </div>

                        <!-- Reason -->
                        <div class="col-md-6 col-12">
                            <label class="form-label fw-bold fs-13 text-secondary">Reason / Remarks</label>
                            <input type="text" name="reason" class="form-control rounded-3 py-2" placeholder="e.g. Medical emergency, Family function">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light px-4 rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-3 fw-bold shadow-sm" id="btnSubmitLeave">
                        <i class="fa-solid fa-save me-1"></i> Save Leave
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php ob_start(); ?>
(function() {
    function initLeaveManagement() {
        if (typeof jQuery === 'undefined') {
            setTimeout(initLeaveManagement, 50);
            return;
        }

        var $ = jQuery;

        // Auto-calculate working days excluding Fridays
        function updateDaysCalculation() {
            var startVal = $('#modalStartDate').val();
            var endVal = $('#modalEndDate').val();
            if (!startVal || !endVal) return;

            var dStart = new Date(startVal);
            var dEnd = new Date(endVal);

            if (dEnd < dStart) {
                $('#calculatedDaysBadge').text('End date is before start date!').removeClass('bg-primary').addClass('bg-danger');
                $('#btnSubmitLeave').prop('disabled', true);
                return;
            }

            var workingDays = 0;
            var cur = new Date(dStart);
            while (cur <= dEnd) {
                // Day 5 is Friday
                if (cur.getDay() !== 5) {
                    workingDays++;
                }
                cur.setDate(cur.getDate() + 1);
            }

            $('#btnSubmitLeave').prop('disabled', false);
            $('#calculatedDaysBadge')
                .text(workingDays + ' Working Day(s)')
                .removeClass('bg-danger')
                .addClass('bg-primary');
        }

        $('#modalStartDate, #modalEndDate').on('change', updateDaysCalculation);

        // Fetch balance when employee & leave type are selected
        function checkEmployeeBalance() {
            var empId = $('#modalEmployeeSelect').val();
            var typeId = $('#modalLeaveTypeSelect').val();
            if (!empId || !typeId) {
                $('#balanceDisplayHint').html('Select employee and leave type to view remaining days.');
                return;
            }

            $.ajax({
                url: '<?= $GLOBALS['base_url'] ?>/api/employee-leave-balances/' + encodeURIComponent(empId),
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (res && res.success && res.balances) {
                        var found = res.balances.find(function(b) {
                            return String(b.leave_type_id) === String(typeId);
                        });
                        if (found) {
                            var color = found.remaining_days > 0 ? 'text-success' : 'text-danger fw-bold';
                            $('#balanceDisplayHint').html(
                                'Remaining Balance: <span class="' + color + '">' + found.remaining_days + ' days</span> (out of ' + found.quota_days + ')'
                            );
                        } else {
                            $('#balanceDisplayHint').html('<span class="text-muted">Quota available: Standard policy</span>');
                        }
                    }
                }
            });
        }

        $(document).on('change select2:select', '#modalEmployeeSelect, #modalLeaveTypeSelect', function() {
            checkEmployeeBalance();
            updateDaysCalculation();
        });

        // Initialize calculation on load
        updateDaysCalculation();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLeaveManagement);
    } else {
        initLeaveManagement();
    }
})();
<?php $page_script = ob_get_clean(); ?>

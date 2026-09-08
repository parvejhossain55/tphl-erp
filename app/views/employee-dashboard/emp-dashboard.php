<?php
// ── Greeting based on time of day ─────────────────────────────────────────
$hour      = (int) date('H');
$greeting  = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$firstName = explode(' ', $employee['full_name'] ?? 'there')[0];

// ── Photo ─────────────────────────────────────────────────────────────────
$photoPath = !empty($employee['photo'])
    ? $GLOBALS['base_url'] . '/public/uploads/employees/' . htmlspecialchars($employee['photo'])
    : 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($employee['full_name'] ?? 'Emp') . '&backgroundColor=b6e3f4';
?>

<!-- ════ MAIN CONTENT ════ -->
<div class="app__slide-wrapper">

    <div class="section-header">
        <div class="section-title"><?= $greeting ?>, <?= htmlspecialchars($firstName) ?></div>
        <div class="section-sub">Here's your monthly summary for <?= htmlspecialchars($currentMonth ?? date('F Y')) ?></div>
    </div>

    <!-- ── ATTENDANCE STAT CARDS ─────────────────────────────────────── -->
    <div class="row align-items-stretch">
        <div class="col-xxl-3 col-xl-3 col-6">
            <div class="card__wrapper stat-card">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon"><span class="chip badge-present"><i class="bi bi-arrow-up"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Present Days</h6>
                        <h4 class="card__title text-break text-success mb-10" id="tipsoi-present-days">
                            <span class="spinner-border spinner-border-sm text-success" role="status" aria-hidden="true" style="width:1.1rem;height:1.1rem;border-width:2px;"></span>
                        </h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-6">
            <div class="card__wrapper stat-card">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon"><span class="chip badge-present"><i class="bi bi-clock-history" style="color:var(--accent4)"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Late Arrivals</h6>
                        <span class="chip badge-late"><i class="bi bi-dot"></i> Check-in &gt; 9:40 · <strong class="ms-1" id="tipsoi-late-days"><span class="spinner-border spinner-border-sm text-warning" role="status" aria-hidden="true" style="width:0.75rem;height:0.75rem;border-width:2px;"></span></strong></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-6">
            <div class="card__wrapper stat-card">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon"><span class="chip badge-present"><i class="bi bi-x-circle-fill" style="color:var(--accent5)"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Absent Days</h6>
                        <span class="chip badge-absent" id="tipsoi-absent-days"><span class="spinner-border spinner-border-sm text-danger" role="status" aria-hidden="true" style="width:0.75rem;height:0.75rem;border-width:2px;"></span></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-6">
            <div class="card__wrapper stat-card">
                <div class="d-flex align-items-center gap-sm">
                    <div class="card__icon"><span class="chip badge-present"><i class="bi bi-umbrella-fill" style="color:var(--accent2)"></i></span></div>
                    <div class="card__title-wrap">
                        <h6 class="card__sub-title mb-10">Leave Days Taken</h6>
                        <span class="chip badge-leave" id="tipsoi-leave-days"><span class="spinner-border spinner-border-sm text-info" role="status" aria-hidden="true" style="width:0.75rem;height:0.75rem;border-width:2px;"></span></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── ATTENDANCE CHART + LEAVE BALANCE ───────────────────────────── -->
    <div class="row mb-4">
        <!-- Attendance Bar Chart -->
        <div class="col-lg-7">
            <div class="stat-card">
                <?php
                $circumference = 188.5;
                $offset = $circumference - ($circumference * ($attendancePct ?? 0) / 100);
                ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div class="section-title">Monthly Attendance</div>
                        <div class="section-sub">Week-by-week breakdown</div>
                    </div>
                    <div class="ring-wrap">
                        <svg width="72" height="72" viewBox="0 0 72 72">
                            <circle cx="36" cy="36" r="30" fill="none" stroke="var(--border)" stroke-width="7" />
                            <circle cx="36" cy="36" r="30" fill="none" stroke="var(--accent3)" stroke-width="7"
                                stroke-dasharray="<?= $circumference ?>"
                                stroke-dashoffset="<?= $offset ?>"
                                stroke-linecap="round"
                                id="tipsoi-ring-circle" />
                        </svg>
                        <div class="ring-center">
                        <div class="ring-pct" id="tipsoi-ring-pct" style="color:var(--accent3)"><span class="spinner-border spinner-border-sm text-success" role="status" aria-hidden="true" style="width:1.2rem;height:1.2rem;border-width:2px;"></span></div>
                        <div class="ring-lbl">Rate</div>
                    </div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:.78rem;color:var(--muted)">Present</span>
                        <span style="font-size:.78rem;font-weight:600;color:var(--accent3)" id="tipsoi-bar-present-text"><?= $presentDays ?? 0 ?> days</span>
                    </div>
                    <div class="att-bar-wrap">
                        <div class="att-bar" id="tipsoi-bar-present" style="width:<?= $attendancePct ?? 0 ?>%;background:var(--accent3)"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:.78rem;color:var(--muted)">Late</span>
                        <span style="font-size:.78rem;font-weight:600;color:var(--accent4)" id="tipsoi-bar-late-text"><?= $lateDays ?? 0 ?> days</span>
                    </div>
                    <div class="att-bar-wrap">
                        <div class="att-bar" id="tipsoi-bar-late" style="width:<?= $latePct ?? 0 ?>%;background:var(--accent4)"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:.78rem;color:var(--muted)">Absent</span>
                        <span style="font-size:.78rem;font-weight:600;color:var(--accent5)" id="tipsoi-bar-absent-text"><?= $absentDays ?? 0 ?> days</span>
                    </div>
                    <div class="att-bar-wrap">
                        <div class="att-bar" id="tipsoi-bar-absent" style="width:<?= $absentPct ?? 0 ?>%;background:var(--accent5)"></div>
                    </div>
                </div>
                <div>
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:.78rem;color:var(--muted)">Leave</span>
                        <span style="font-size:.78rem;font-weight:600;color:var(--accent2)" id="tipsoi-bar-leave-text"><?= $leaveDays ?? 0 ?> days</span>
                    </div>
                    <div class="att-bar-wrap">
                        <div class="att-bar" id="tipsoi-bar-leave" style="width:<?= $leavePct ?? 0 ?>%;background:var(--accent2)"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leave Balance -->
        <div class="col-lg-5">
            <div class="stat-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="section-title mb-0">Leave Balance</div>
                    <span id="tipsoi-badge-status" class="badge bg-light text-muted fw-normal" style="font-size:0.7rem;"></span>
                </div>
                <div id="tipsoi-leave-balance-container">
                    <?php
                    $accentColors = ['var(--accent3)', 'var(--accent)', 'var(--accent2)', 'var(--accent4)', 'var(--accent5)'];
                    if (!empty($leaveBalances)):
                        foreach ($leaveBalances as $idx => $lb):
                            $color = $accentColors[$idx % count($accentColors)];
                    ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <div style="font-size:.72rem;color:var(--muted)"><?= htmlspecialchars($lb['name']) ?></div>
                                        <div style="font-size:.72rem;color:var(--muted)">Used <?= $lb['used'] ?> / Total <?= $lb['total_days'] ?></div>
                                    </div>
                                    <div style="text-align:right">
                                        <div style="font-size:1.3rem;font-weight:700;color:<?= $color ?>;font-family:'Space Mono',monospace"><?= $lb['remaining'] ?></div>
                                        <div style="font-size:.68rem;color:var(--muted)">remaining</div>
                                    </div>
                                </div>
                                <div class="att-bar-wrap">
                                    <div class="att-bar" style="width:<?= $lb['pct'] ?>%;background:<?= $color ?>"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div id="tipsoi-leave-spinner" class="text-center py-4">
                            <span class="spinner-border spinner-border-sm text-primary" role="status" style="width:1.2rem;height:1.2rem;border-width:2px;"></span>
                            <div class="text-muted mt-2" style="font-size:0.75rem;">Loading leave balances...</div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mt-3 p-3 rounded-3" style="background:rgba(79,140,255,.07);border:1px solid rgba(79,140,255,.2)">
                    <div style="font-size:.75rem;color:var(--muted)">Total Leave Taken (YTD)</div>
                    <div style="font-size:1.5rem;font-weight:700;color:var(--accent);font-family:'Space Mono',monospace">
                        <span id="tipsoi-total-used"><?= $totalUsed ?? 0 ?></span>
                        <span style="font-size:.9rem;font-weight:400;color:var(--muted)">/ <span id="tipsoi-total-allowed"><?= $totalAllowed ?? 0 ?></span> days</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── TASKS SUMMARY ──────────────────────────────────────────────── -->
    <div class="row">
        <div class="col-12">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <div class="section-title">My Tasks</div>
                        <div class="section-sub"><?= htmlspecialchars($currentMonth ?? date('F Y')) ?> · <?= $totalTasks ?? 0 ?> total tasks</div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="chip task-status-done"><?= $doneTasks ?? 0 ?> Done</span>
                        <span class="chip task-status-prog"><?= $progTasks ?? 0 ?> In Progress</span>
                        <span class="chip task-status-pend"><?= $pendTasks ?? 0 ?> Pending</span>
                    </div>
                </div>
                <div class="att-bar-wrap mb-4" style="height:12px;border-radius:8px">
                    <div class="att-bar" style="width:<?= $taskPct ?? 0 ?>%;background:linear-gradient(90deg,var(--accent3),var(--accent));border-radius:8px;height:100%"></div>
                </div>
                <!-- Task Cards -->
                <div class="row">
                    <?php if (!empty($pendingTasks)): ?>
                        <?php foreach (array_slice($pendingTasks, 0, 6) as $task): ?>
                            <?php
                            $priority   = strtolower($task['priority'] ?? 'low');
                            $status     = strtolower($task['status']   ?? 'pending');
                            $pClass     = match ($priority) {
                                'high' => 'priority-high',
                                'medium', 'med' => 'priority-med',
                                default => 'priority-low'
                            };
                            $pLabel     = ucfirst($priority);
                            $sClass     = match ($status) {
                                'completed' => 'task-status-done',
                                'in_progress' => 'task-status-prog',
                                default => 'task-status-pend'
                            };
                            $sLabel     = match ($status) {
                                'completed' => 'Done',
                                'in_progress' => 'In Progress',
                                default => 'Pending'
                            };
                            $dueDate    = !empty($task['deadline']) ? date('M d', strtotime($task['deadline'])) : 'N/A';
                            ?>
                            <div class="col-md-4 mb-3">
                                <div class="task-card">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <div class="task-title"><?= htmlspecialchars($task['title'] ?? 'Task') ?></div>
                                        <span class="task-priority <?= $pClass ?>"><?= $pLabel ?></span>
                                    </div>
                                    <div class="task-meta">
                                        <?= $task['description'] ?? '' ?>
                                        <?= !empty($task['deadline']) ? '· Due ' . $dueDate : '' ?>
                                    </div>
                                    <div class="mt-2 d-flex align-items-center justify-content-between flex-wrap gap-1">
                                        <span class="chip <?= $sClass ?>" style="font-size:.7rem"><?= $sLabel ?></span>
                                        <?php if (!empty($task['progress'])): ?>
                                            <span style="font-size:.72rem;color:var(--muted)"><?= (int)$task['progress'] ?>%</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center text-muted py-3" style="font-size:.88rem">
                            <i class="bi bi-check2-all me-1"></i> No pending tasks! All caught up.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══ PAGE: TASKS ══ -->
<div class="page" id="page-tasks">
    <div class="section-header">
        <div class="section-title">My Tasks</div>
        <div class="section-sub"><?= htmlspecialchars($currentMonth ?? date('F Y')) ?> · All assigned tasks</div>
    </div>
    <div class="row mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <div class="stat-value" style="color:var(--text)"><?= $totalTasks ?? 0 ?></div>
                <div class="stat-label">Total Tasks</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <div class="stat-value" style="color:var(--accent3)"><?= $doneTasks ?? 0 ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <div class="stat-value" style="color:var(--accent)"><?= $progTasks ?? 0 ?></div>
                <div class="stat-label">In Progress</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <div class="stat-value" style="color:var(--accent4)"><?= $pendTasks ?? 0 ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
    </div>
    <div class="row">
        <?php if (!empty($allTasks)): ?>
            <?php foreach ($allTasks as $task): ?>
                <?php
                $priority = strtolower($task['priority'] ?? 'low');
                $status   = strtolower($task['status']   ?? 'pending');
                $pClass   = match ($priority) {
                    'high' => 'priority-high',
                    'medium', 'med' => 'priority-med',
                    default => 'priority-low'
                };
                $pLabel   = ucfirst($priority);
                $sClass   = match ($status) {
                    'completed' => 'task-status-done',
                    'in_progress' => 'task-status-prog',
                    default => 'task-status-pend'
                };
                $sLabel   = match ($status) {
                    'completed' => 'Done',
                    'in_progress' => 'In Progress',
                    default => 'Pending'
                };
                $dueDate  = !empty($task['deadline']) ? date('M d', strtotime($task['deadline'])) : 'N/A';
                ?>
                <div class="col-md-4 mb-3">
                    <div class="task-card">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div class="task-title"><?= htmlspecialchars($task['title'] ?? 'Task') ?></div>
                            <span class="task-priority <?= $pClass ?>"><?= $pLabel ?></span>
                        </div>
                        <div class="task-meta">
                            <?= $task['description'] ?? '' ?>
                            <?= !empty($task['deadline']) ? ' · Due ' . $dueDate : '' ?>
                        </div>
                        <div class="mt-2 d-flex align-items-center justify-content-between flex-wrap gap-1">
                            <span class="chip <?= $sClass ?>" style="font-size:.7rem"><?= $sLabel ?></span>
                            <?php if (!empty($task['progress'])): ?>
                                <span style="font-size:.72rem;color:var(--muted)"><?= (int)$task['progress'] ?>% done</span>
                            <?php endif; ?>
                        </div>
                        <!-- Progress update form -->
                        <?php if ($status !== 'completed'): ?>
                            <button type="button" class="btn btn-outline-primary btn-sm w-100 mt-2" data-bs-toggle="modal" data-bs-target="#updateProgressModal-<?= (int)$task['id'] ?>">
                                <i class="bi bi-arrow-clockwise me-1"></i> Update Progress
                            </button>

                            <!-- Bootstrap Modal for progress update -->
                            <div class="modal fade" id="updateProgressModal-<?= (int)$task['id'] ?>" tabindex="-1" aria-labelledby="updateProgressModalLabel-<?= (int)$task['id'] ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title text-dark" id="updateProgressModalLabel-<?= (int)$task['id'] ?>">Update Progress: <?= htmlspecialchars($task['title'] ?? 'Task') ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="POST" action="<?= $GLOBALS['base_url'] ?>/update-task-progress-employee" enctype="multipart/form-data">
                                            <div class="modal-body text-start">
                                                <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label text-dark fw-semibold">Progress Percentage (%)</label>
                                                    <input type="number" name="progress" min="<?= (int)($task['progress'] ?? 0) ?>" max="100" value="<?= (int)($task['progress'] ?? 0) ?>" class="form-control" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label text-dark fw-semibold">Remarks</label>
                                                    <textarea name="remarks" class="form-control" rows="3" placeholder="What parts of this task have you completed?" required><?= htmlspecialchars($task['remarks'] ?? '') ?></textarea>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label text-dark fw-semibold">Attached Image (Optional)</label>
                                                    <input type="file" name="attachment_image" class="form-control" accept="image/*">
                                                    <?php if (!empty($task['attachment_image'])): ?>
                                                        <div class="mt-2">
                                                            <a href="<?= $GLOBALS['base_url'] ?>/public/uploads/tasks/<?= $task['attachment_image'] ?>" target="_blank" class="text-primary"><i class="bi bi-image me-1"></i>View Current Image</a>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center text-muted py-4">No tasks assigned yet.</div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    /* ── PAGE NAVIGATION ── */
    const titles = {
        dashboard: 'Dashboard Overview',
        attendance: 'Attendance Details',
        leaves: 'Leave Management',
        tasks: 'My Tasks',
        profile: 'My Profile'
    };

    function showPage(id, btn) {
        document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
        const page = document.getElementById('page-' + id);
        if (page) page.classList.add('active');
        const topbar = document.getElementById('topbar-title');
        if (topbar) topbar.textContent = titles[id] ?? id;
        if (btn) btn.classList.add('active');
        const sidebar = document.getElementById('sidebar');
        if (sidebar) sidebar.classList.remove('open');
    }

    /* ── TIPSOI ATTENDANCE & LEAVE DATA SYNC ── */
    document.addEventListener("DOMContentLoaded", function() {
        const currentMonth = "<?= date('Y-m') ?>";
        const baseUrl = "<?= $GLOBALS['base_url'] ?>";

        function restoreFallback() {
            const elPresent = document.getElementById("tipsoi-present-days");
            if (elPresent && elPresent.querySelector(".spinner-border")) elPresent.textContent = "<?= $presentDays ?? 0 ?>";

            const elLate = document.getElementById("tipsoi-late-days");
            if (elLate && elLate.querySelector(".spinner-border")) elLate.textContent = "<?= $lateDays ?? 0 ?>";

            const elAbsent = document.getElementById("tipsoi-absent-days");
            if (elAbsent && elAbsent.querySelector(".spinner-border")) elAbsent.textContent = "<?= $absentDays ?? 0 ?>";

            const elLeave = document.getElementById("tipsoi-leave-days");
            if (elLeave && elLeave.querySelector(".spinner-border")) elLeave.textContent = "<?= $leaveDays ?? 0 ?> Approved";

            const elRingPct = document.getElementById("tipsoi-ring-pct");
            if (elRingPct && elRingPct.querySelector(".spinner-border")) elRingPct.textContent = "<?= $attendancePct ?? 0 ?>%";

            const leaveSpinner = document.getElementById("tipsoi-leave-spinner");
            if (leaveSpinner) {
                leaveSpinner.innerHTML = '<div class="text-muted py-2" style="font-size:.85rem">No leave types configured.</div>';
            }
        }

        fetch(`${baseUrl}/api/employee/tipsoi-data?month=${currentMonth}`)
            .then(res => res.json())
            .then(res => {
                if (!res || !res.success || !res.isRegistered) {
                    restoreFallback();
                    return;
                }

                // Update Status Badge
                const statusBadge = document.getElementById("tipsoi-badge-status");
                if (statusBadge) {
                    statusBadge.className = "badge bg-success-subtle text-success fw-semibold";
                    statusBadge.innerHTML = '<i class="bi bi-check-circle me-1"></i>Tipsoi Connected';
                }

                // 1. Calculate Attendance Stats
                let present = 0, late = 0, absent = 0, leave = 0;
                if (res.attendanceSummary) {
                    const s = res.attendanceSummary;
                    present = parseInt(s.totalPresent || 0);
                    late    = parseInt(s.totalLateEntry || 0);
                    absent  = parseInt(s.totalAbsent || 0);
                    leave   = parseInt(s.totalLeave || 0);
                } else if (Array.isArray(res.attendance)) {
                    res.attendance.forEach(log => {
                        if (log.isPresent == 1) present++;
                        if (log.lateBy && log.lateBy !== '-' && log.lateBy !== '0') late++;
                        if (log.isLeave == 1) leave++;
                        if (log.isPresent != 1 && !log.isOffday && !log.isHoliday && !log.isLeave) absent++;
                    });
                }

                const total = present + late + absent + leave;
                const totalDays = total > 0 ? total : 1;
                const attPct    = Math.round((present / totalDays) * 100);
                const latePct   = Math.round((late / totalDays) * 100);
                const absentPct = Math.round((absent / totalDays) * 100);
                const leavePct  = Math.round((leave / totalDays) * 100);

                // Update Stat Cards
                const elPresent = document.getElementById("tipsoi-present-days");
                if (elPresent) elPresent.textContent = present;

                const elLate = document.getElementById("tipsoi-late-days");
                if (elLate) elLate.textContent = late;

                const elAbsent = document.getElementById("tipsoi-absent-days");
                if (elAbsent) elAbsent.textContent = absent;

                const elLeave = document.getElementById("tipsoi-leave-days");
                if (elLeave) elLeave.textContent = leave + " Approved";

                // Update Ring & Bars
                const elRingPct = document.getElementById("tipsoi-ring-pct");
                if (elRingPct) elRingPct.textContent = attPct + "%";

                const elRingCircle = document.getElementById("tipsoi-ring-circle");
                if (elRingCircle) {
                    const circum = 188.5;
                    const offset = circum - (circum * attPct / 100);
                    elRingCircle.style.strokeDashoffset = offset;
                }

                const barPresent = document.getElementById("tipsoi-bar-present");
                const barPresentTxt = document.getElementById("tipsoi-bar-present-text");
                if (barPresent) barPresent.style.width = attPct + "%";
                if (barPresentTxt) barPresentTxt.textContent = present + " days";

                const barLate = document.getElementById("tipsoi-bar-late");
                const barLateTxt = document.getElementById("tipsoi-bar-late-text");
                if (barLate) barLate.style.width = latePct + "%";
                if (barLateTxt) barLateTxt.textContent = late + " days";

                const barAbsent = document.getElementById("tipsoi-bar-absent");
                const barAbsentTxt = document.getElementById("tipsoi-bar-absent-text");
                if (barAbsent) barAbsent.style.width = absentPct + "%";
                if (barAbsentTxt) barAbsentTxt.textContent = absent + " days";

                const barLeave = document.getElementById("tipsoi-bar-leave");
                const barLeaveTxt = document.getElementById("tipsoi-bar-leave-text");
                if (barLeave) barLeave.style.width = leavePct + "%";
                if (barLeaveTxt) barLeaveTxt.textContent = leave + " days";

                // 2. Populate Leave Balances from Tipsoi
                if (Array.isArray(res.leaveBalances) && res.leaveBalances.length > 0) {
                    const leaveContainer = document.getElementById("tipsoi-leave-balance-container");
                    if (leaveContainer) {
                        let totalUsed = 0, totalAllowed = 0;
                        const accentColors = ['var(--accent3)', 'var(--accent)', 'var(--accent2)', 'var(--accent4)', 'var(--accent5)'];
                        let html = "";
                        res.leaveBalances.forEach((lb, idx) => {
                            const name = lb.leaveCategoryName || lb.name || "Leave";
                            const allowed = parseInt(lb.entitled !== undefined ? lb.entitled : (lb.allocation || lb.totalDays || 0));
                            const remaining = parseInt(lb.balance !== null && lb.balance !== undefined ? lb.balance : allowed);
                            const used = parseInt(lb.used !== undefined ? lb.used : Math.max(0, allowed - remaining));
                            const pct = allowed > 0 ? Math.round((used / allowed) * 100) : 0;
                            const color = accentColors[idx % accentColors.length];

                            totalUsed += used;
                            totalAllowed += allowed;

                            html += `
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <div style="font-size:.72rem;color:var(--muted)">${escapeHtml(name)}</div>
                                            <div style="font-size:.72rem;color:var(--muted)">Used ${used} / Total ${allowed}</div>
                                        </div>
                                        <div style="text-align:right">
                                            <div style="font-size:1.3rem;font-weight:700;color:${color};font-family:'Space Mono',monospace">${remaining}</div>
                                            <div style="font-size:.68rem;color:var(--muted)">remaining</div>
                                        </div>
                                    </div>
                                    <div class="att-bar-wrap">
                                        <div class="att-bar" style="width:${pct}%;background:${color}"></div>
                                    </div>
                                </div>
                            `;
                        });
                        leaveContainer.innerHTML = html;

                        const elTotalUsed = document.getElementById("tipsoi-total-used");
                        const elTotalAllowed = document.getElementById("tipsoi-total-allowed");
                        if (elTotalUsed) elTotalUsed.textContent = totalUsed;
                        if (elTotalAllowed) elTotalAllowed.textContent = totalAllowed;
                    }
                } else {
                    const leaveSpinner = document.getElementById("tipsoi-leave-spinner");
                    if (leaveSpinner) {
                        leaveSpinner.innerHTML = '<div class="text-muted py-2" style="font-size:.85rem">No leave records found.</div>';
                    }
                }
            })
            .catch(err => {
                restoreFallback();
                console.warn("Tipsoi sync notice:", err);
            });

        function escapeHtml(str) {
            const d = document.createElement('div');
            d.textContent = str || '';
            return d.innerHTML;
        }
    });
</script>
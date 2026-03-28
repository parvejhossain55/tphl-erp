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
                        <h4 class="card__title text-break text-success mb-10"><?= $presentDays ?? 0 ?></h4>
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
                        <span class="chip badge-late"><i class="bi bi-dot"></i> Check-in &gt; 9:15 · <strong class="ms-1"><?= $lateDays ?? 0 ?></strong></span>
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
                        <span class="chip badge-absent"><?= $absentDays ?? 0 ?></span>
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
                        <span class="chip badge-leave"><?= $leaveDays ?? 0 ?> Approved</span>
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
                                stroke-linecap="round" />
                        </svg>
                        <div class="ring-center">
                            <div class="ring-pct" style="color:var(--accent3)"><?= $attendancePct ?? 0 ?>%</div>
                            <div class="ring-lbl">Rate</div>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:.78rem;color:var(--muted)">Present</span>
                        <span style="font-size:.78rem;font-weight:600;color:var(--accent3)"><?= $presentDays ?? 0 ?> days</span>
                    </div>
                    <div class="att-bar-wrap">
                        <div class="att-bar" style="width:<?= $attendancePct ?? 0 ?>%;background:var(--accent3)"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:.78rem;color:var(--muted)">Late</span>
                        <span style="font-size:.78rem;font-weight:600;color:var(--accent4)"><?= $lateDays ?? 0 ?> days</span>
                    </div>
                    <div class="att-bar-wrap">
                        <div class="att-bar" style="width:<?= $latePct ?? 0 ?>%;background:var(--accent4)"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:.78rem;color:var(--muted)">Absent</span>
                        <span style="font-size:.78rem;font-weight:600;color:var(--accent5)"><?= $absentDays ?? 0 ?> days</span>
                    </div>
                    <div class="att-bar-wrap">
                        <div class="att-bar" style="width:<?= $absentPct ?? 0 ?>%;background:var(--accent5)"></div>
                    </div>
                </div>
                <div>
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:.78rem;color:var(--muted)">Leave</span>
                        <span style="font-size:.78rem;font-weight:600;color:var(--accent2)"><?= $leaveDays ?? 0 ?> days</span>
                    </div>
                    <div class="att-bar-wrap">
                        <div class="att-bar" style="width:<?= $leavePct ?? 0 ?>%;background:var(--accent2)"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leave Balance -->
        <div class="col-lg-5">
            <div class="stat-card h-100">
                <div class="section-title mb-3">Leave Balance</div>
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
                    <p class="text-muted" style="font-size:.85rem">No leave types configured.</p>
                <?php endif; ?>
                <div class="mt-3 p-3 rounded-3" style="background:rgba(79,140,255,.07);border:1px solid rgba(79,140,255,.2)">
                    <div style="font-size:.75rem;color:var(--muted)">Total Leave Taken (YTD)</div>
                    <div style="font-size:1.5rem;font-weight:700;color:var(--accent);font-family:'Space Mono',monospace">
                        <?= $totalUsed ?? 0 ?>
                        <span style="font-size:.9rem;font-weight:400;color:var(--muted)">/ <?= $totalAllowed ?? 0 ?> days</span>
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
                            <form method="POST" action="<?= $GLOBALS['base_url'] ?>/update-task-progress-employee" class="mt-2 d-flex gap-2 align-items-center">
                                <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>">
                                <input type="range" name="progress" min="0" max="100"
                                    value="<?= (int)($task['progress'] ?? 0) ?>"
                                    class="form-range flex-grow-1" style="height:4px">
                                <button type="submit" class="btn btn-outline-secondary btn-sm py-0" style="font-size:.7rem">Save</button>
                            </form>
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
</script>
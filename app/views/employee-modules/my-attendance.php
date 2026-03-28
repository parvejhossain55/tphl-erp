    <div class="row">
        <div class="section-header">
            <div class="section-title">Attendance Details</div>
            <div class="section-sub">
                Monthly attendance log
                <!-- Month picker -->
                <form method="GET" action="" class="d-inline-flex align-items-center gap-2 ms-3">
                    <input type="month" name="month"
                        class="form-control form-control-sm"
                        value="<?= htmlspecialchars($selectedMonth ?? date('Y-m')) ?>"
                        style="max-width:160px;">
                    <button type="submit" class="btn btn-primary btn-sm">Go</button>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-6 col-md-3">
                <div class="card__wrapper stat-card text-center">
                    <div class="stat-value" style="color:var(--accent3)"><?= (int)($summary['Present'] ?? 0) ?></div>
                    <div class="stat-label">Present</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card__wrapper stat-card text-center">
                    <div class="stat-value" style="color:var(--accent4)"><?= (int)($summary['Late'] ?? 0) ?></div>
                    <div class="stat-label">Late</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card__wrapper stat-card text-center">
                    <div class="stat-value" style="color:var(--accent5)"><?= (int)($summary['Absent'] ?? 0) ?></div>
                    <div class="stat-label">Absent</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card__wrapper stat-card text-center">
                    <div class="stat-value" style="color:var(--accent2)"><?= (int)($summary['On Leave'] ?? 0) ?></div>
                    <div class="stat-label">On Leave</div>
                </div>
            </div>
            <!-- Attendance Table -->
           
        </div>
         <div class="card__wrapper stat-card ">
                <div class="table-responsive">
                    <table class="leave-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Day</th>
                                <th>Check-In</th>
                                <th>Check-Out</th>
                                <th>Hours</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($attendanceData)): ?>
                                <?php foreach ($attendanceData as $row): ?>
                                    <?php
                                    $punchIn  = $row['punch_in_time'];
                                    $punchOut = $row['punch_out_time'];
    
                                    $checkIn  = ($punchIn  && $punchIn  !== '0000-00-00 00:00:00') ? date('h:i A', strtotime($punchIn))  : '-';
                                    $checkOut = ($punchOut && $punchOut !== '0000-00-00 00:00:00') ? date('h:i A', strtotime($punchOut)) : '-';
    
                                    $hours = '-';
                                    if (
                                        $punchIn && $punchOut
                                        && $punchIn  !== '0000-00-00 00:00:00'
                                        && $punchOut !== '0000-00-00 00:00:00'
                                    ) {
                                        $diff  = strtotime($punchOut) - strtotime($punchIn);
                                        $h     = floor($diff / 3600);
                                        $m     = floor(($diff % 3600) / 60);
                                        $hours = "{$h}h {$m}m";
                                    }
    
                                    $status = $row['today_status'] ?? '-';
                                    $badgeClass = match ($status) {
                                        'Present'  => 'badge-present',
                                        'Absent'   => 'badge-late',
                                        'Late'     => 'badge-leave',
                                        'On Leave' => 'badge-leave',
                                        default    => '',
                                    };
                                    ?>
                                    <tr>
                                        <td><?= !empty($row['attendance_date']) ? date('d M Y', strtotime($row['attendance_date'])) : '-' ?></td>
                                        <td><?= !empty($row['attendance_date']) ? date('D', strtotime($row['attendance_date'])) : '-' ?></td>
                                        <td><?= $checkIn ?></td>
                                        <td><?= $checkOut ?></td>
                                        <td><?= $hours ?></td>
                                        <td>
                                            <span class="leave-badge <?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No attendance records for <?= htmlspecialchars($selectedMonth ?? '') ?>.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

    </div>
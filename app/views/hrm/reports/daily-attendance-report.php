


<div class="row">

    <!-- ── Header ── -->
    <div class="report-header">
        <div class="brand">
            <div class="brand-text">
                <h1>Attendance Report</h1>
                <p>Human Resource Management System</p>
            </div>
        </div>

        <div class="report-meta d-flex gap-2">
            <div class="date-badge mb-2">
                &nbsp;<?= date('l, d F Y') ?>
            </div>
            <div><a href="<?= $GLOBALS['base_url'] ?>/export-daily-report?date=<?= htmlspecialchars($reportDate) ?>" class="btn btn-success">
                    Export CSV
                </a></div>
            <div><a href="<?= $GLOBALS['base_url'] ?>/export-daily-report-pdf?date=<?= htmlspecialchars($reportDate ?? date('Y-m-d')) ?>" class="btn btn-danger">
                    Export PDF
                </a></div>
        </div>
    </div>

    <!-- ── Stat Cards ── -->
    <div class="row mb-4">
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card__wrapper stat-card total">
                <div class="stat-label">Total Employee</div>
                <div class="stat-value"><?= $employeeCount ?? 0 ?></div>
                <div class="stat-sub">Active headcount</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card__wrapper stat-card present">
                <div class="stat-label">Present</div>
                <div class="stat-value"><?= $todaySummary['Present'] ?></div>
                <div class="stat-sub">On time today</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card__wrapper stat-card late">
                <div class="stat-label">Late</div>
                <div class="stat-value"><?= $todaySummary['Late'] ?></div>
                <div class="stat-sub">After 9:40 AM</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card__wrapper stat-card absent">
                <div class="stat-label">Absent</div>
                <div class="stat-value"><?= $todaySummary['Absent'] ?></div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card__wrapper stat-card leave">
                <div class="stat-label">On Leave</div>
                <div class="stat-value"><?= $todaySummary['On Leave'] ?></div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card__wrapper stat-card leave">
                <div class="stat-label">Day Off</div>
                <div class="stat-value"><?= $todaySummary['Day Off'] ?></div>
            </div>
        </div>
    </div>

    <!--  Employee Table ── -->
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table no-border-table mb-20 multiple_tables">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Department</th>
                            <th>Total Employee</th>
                            <th>Present</th>
                            <th>Late</th>
                            <th>Absent</th>
                            <th>Leave</th>
                            <th>Day Off</th>
                            <th>Attendance %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportData as $i => $row):
                            // Department-wise attendance % = (present / total) * 100
                            $attendancePercent = ($row['present_count'] / max($row['total_employee'], 1)) * 100;
                        ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($row['department_name']) ?></td>
                                <td><span class="bd-badge bg-primary"><?= $row['total_employee'] ?></span></td>
                                <td><span class="badge-status bs-present"><?= $row['present_count'] ?></span></td>
                                <td><span class="badge-status bs-late"><?= $row['late_count'] ?></span></td>
                                <td><span class="badge-status bs-absent"><?= $row['absent_count'] ?></span></td>
                                <td><span class="badge-status bs-leave"><?= $row['leave_count'] ?></td>
                                <td><span class="badge-status bs-dayoff"><?= $row['day_off_count'] ?></td>
                                <td><?= number_format($attendancePercent, 2) ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>
        </div>
    </div>
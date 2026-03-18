
<div class="row">

    <!-- ── Header ── -->
    <div class="report-header">
        <div class="brand">
            <div class="brand-text">
                <h1>Attendance (Job Card)</h1>
                <p>Employees Attendance Report</p>
            </div>
        </div>

        <div class="report-meta d-flex gap-2">
            <div>
                <a href="<?= $GLOBALS['base_url'] ?>/export-job-card-csv?employee_id=<?= htmlspecialchars($selectedEmployeeId) ?>&month=<?= htmlspecialchars($selectedMonth) ?>" class="btn btn-success <?= !$selectedEmployeeId ? 'disabled' : '' ?>">
                    Export CSV
                </a>
            </div>
            <div>
                <a href="<?= $GLOBALS['base_url'] ?>/export-job-card-pdf?employee_id=<?= htmlspecialchars($selectedEmployeeId) ?>&month=<?= htmlspecialchars($selectedMonth) ?>" class="btn btn-danger <?= !$selectedEmployeeId ? 'disabled' : '' ?>">
                    Export PDF
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="col-xxl-12 mb-3">
        <form method="GET" action="<?= $GLOBALS['base_url'] ?>/job-card-report">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label for="employee_id" class="form-label fw-bold">Select Employee <span class="text-danger">*</span></label>
                    <select name="employee_id" id="employee_id" class="form-control select2" required>
                        <option value="">-- Select Employee --</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['id'] ?>" <?= ($emp['id'] == $selectedEmployeeId) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($emp['full_name']) ?> (<?= htmlspecialchars($emp['id']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="month" class="form-label fw-bold">Select Month</label>
                    <input type="month" name="month" id="month" class="form-control" value="<?= htmlspecialchars($selectedMonth) ?>" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                    <a href="<?= $GLOBALS['base_url'] ?>/job-card-report" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <!--  Report Table ── -->
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <?php if ($selectedEmployeeId && empty($reportData)): ?>
                    <div class="alert alert-warning text-center">No attendance records found for this employee in the selected month.</div>
                <?php elseif ($selectedEmployeeId && !empty($reportData)): ?>
                    <table class="table no-border-table mb-20">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Punch In Time</th>
                                <th>Punch Out Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportData as $i => $row): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= date('d M Y (l)', strtotime($row['attendance_date'])) ?></td>
                                    <td><?= (!empty($row['punch_in_time']) && $row['punch_in_time'] != '0000-00-00 00:00:00') ? date('h:i A', strtotime($row['punch_in_time'])) : '--:--' ?></td>
                                    <td><?= (!empty($row['punch_out_time']) && $row['punch_out_time'] != '0000-00-00 00:00:00') ? date('h:i A', strtotime($row['punch_out_time'])) : '--:--' ?></td>
                                    <td>
                                        <?php if ($row['today_status'] === 'Present') :  ?>
                                            <span class="badge-status bs-present"><?= htmlspecialchars($row['today_status']) ?></span>
                                        <?php elseif ($row['today_status'] === 'Absent') : ?>
                                            <span class="badge-status bs-absent"><?= htmlspecialchars($row['today_status']) ?></span>
                                        <?php elseif ($row['today_status'] === 'Late') : ?>
                                            <span class="badge-status bs-late"><?= htmlspecialchars($row['today_status']) ?></span>
                                        <?php else : ?>
                                            <span class="badge-status bs-leave"><?= htmlspecialchars($row['today_status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info text-center">Please select an employee and month to generate the Job Card report.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

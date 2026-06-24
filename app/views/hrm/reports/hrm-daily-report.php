
<div class="row">

    <!-- ── Header ── -->
    <div class="report-header">
        <div class="brand">
            <div class="brand-text">
                <h1>Daily Report</h1>
                <p>Human Resource Management System</p>
            </div>
        </div>

        <div class="report-meta d-flex gap-2">
            <div>
                <?php 
                    $qs = "?date=" . htmlspecialchars($selectedDate);
                    if ($selectedDepartmentId) $qs .= "&department_id=" . htmlspecialchars($selectedDepartmentId);
                    if ($selectedEmployeeId) $qs .= "&employee_id=" . htmlspecialchars($selectedEmployeeId);
                ?>
                <a href="<?= $GLOBALS['base_url'] ?>/export-hrm-daily-csv<?= $qs ?>" class="btn btn-success">
                    Export CSV
                </a>
            </div>
            <div>
                <a href="<?= $GLOBALS['base_url'] ?>/export-hrm-daily-pdf<?= $qs ?>" class="btn btn-danger">
                    Export PDF
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="col-xxl-12 mb-3">
        <form method="GET" action="<?= $GLOBALS['base_url'] ?>/hrm-daily-report">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label for="date" class="form-label fw-bold">Select Date</label>
                    <input type="date" name="date" id="date" class="form-control" value="<?= htmlspecialchars($selectedDate) ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="department_id" class="form-label fw-bold">Department</label>
                    <select name="department_id" id="department_id" class="form-control select2">
                        <option value="">-- All Departments --</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>" <?= ($dept['id'] == $selectedDepartmentId) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="employee_id" class="form-label fw-bold">Employee</label>
                    <select name="employee_id" id="employee_id" class="form-control select2">
                        <option value="">-- All Employees --</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['id'] ?>" <?= ($emp['id'] == $selectedEmployeeId) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($emp['full_name']) ?> (<?= htmlspecialchars($emp['employee_id']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mt-3 mt-md-0">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                    <a href="<?= $GLOBALS['base_url'] ?>/hrm-daily-report" class="btn btn-secondary w-100 mt-2">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <!--  Report Table ── -->
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table no-border-table mb-20">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Employee Name</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>Punch In Time</th>
                            <th>Punch Out Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reportData)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No attendance data found for the selected criteria.</td>
                            </tr>
                        <?php endif; ?>
                        
                        <?php foreach ($reportData as $i => $row): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($row['emp_name']) ?></td>
                                <td><?= htmlspecialchars($row['dept_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($row['designation_name'] ?? 'N/A') ?></td>
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
            </div>
        </div>
    </div>
</div>

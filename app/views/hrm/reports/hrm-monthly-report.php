
<div class="row">

    <!-- ── Header ── -->
    <div class="report-header">
        <div class="brand">
            <div class="brand-text">
                <h1>Monthly Report</h1>
                <p>Human Resource Management System</p>
            </div>
        </div>

        <div class="report-meta d-flex gap-2">
            <div>
                <?php 
                    $qs = "?month=" . htmlspecialchars($selectedMonth);
                    if ($selectedDepartmentId) $qs .= "&department_id=" . htmlspecialchars($selectedDepartmentId);
                    if ($selectedEmployeeId) $qs .= "&employee_id=" . htmlspecialchars($selectedEmployeeId);
                ?>
                <a href="<?= $GLOBALS['base_url'] ?>/export-hrm-monthly-csv<?= $qs ?>" class="btn btn-success">
                    Export CSV
                </a>
            </div>
            <div>
                <a href="<?= $GLOBALS['base_url'] ?>/export-hrm-monthly-pdf<?= $qs ?>" class="btn btn-danger">
                    Export PDF
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="col-xxl-12 mb-3">
        <form method="GET" action="<?= $GLOBALS['base_url'] ?>/hrm-monthly-report">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label for="month" class="form-label fw-bold">Select Month</label>
                    <input type="month" name="month" id="month" class="form-control" value="<?= htmlspecialchars($selectedMonth) ?>" required>
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
                    <a href="<?= $GLOBALS['base_url'] ?>/hrm-monthly-report" class="btn btn-secondary w-100 mt-2">Reset</a>
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
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Late</th>
                            <th>Half-Day</th>
                            <th>Leave</th>
                            <th>Day Off</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reportData)): ?>
                            <tr>
                                <td colspan="10" class="text-center">No attendance data found for the selected criteria.</td>
                            </tr>
                        <?php endif; ?>
                        
                        <?php foreach ($reportData as $i => $row): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($row['emp_name']) ?></td>
                                <td><?= htmlspecialchars($row['dept_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($row['designation_name'] ?? 'N/A') ?></td>
                                <td><span class="badge-status bs-present"><?= (int)$row['total_present'] ?></span></td>
                                <td><span class="badge-status bs-absent"><?= (int)$row['total_absent'] ?></span></td>
                                <td><span class="badge-status bs-late"><?= (int)$row['total_late'] ?></span></td>
                                <td><span class="badge-status bg-info text-white"><?= (int)$row['total_half'] ?></span></td>
                                <td><span class="badge-status bs-leave"><?= (int)$row['total_leave'] ?></span></td>
                                <td><span class="badge-status bg-secondary text-white"><?= (int)$row['total_off'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

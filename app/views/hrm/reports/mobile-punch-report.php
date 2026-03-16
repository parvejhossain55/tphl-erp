
<div class="row">

    <!-- ── Header ── -->
    <div class="report-header">
        <div class="brand">
            <div class="brand-text">
                <h1>Mobile Punch Report</h1>
                <p>Human Resource Management System</p>
            </div>
        </div>

        <div class="report-meta d-flex gap-2">
            <div class="date-badge mb-2">
                &nbsp;<?= date('l, d F Y', strtotime($reportDate)) ?>
            </div>
            <div>
                <a href="<?= $GLOBALS['base_url'] ?>/export-mobile-punch-report?date=<?= htmlspecialchars($reportDate) ?>" class="btn btn-success">
                    Export CSV
                </a>
            </div>
            <div>
                <a href="<?= $GLOBALS['base_url'] ?>/export-mobile-punch-report-pdf?date=<?= htmlspecialchars($reportDate) ?>" class="btn btn-danger">
                    Export PDF
                </a>
            </div>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="col-xxl-12 mb-3">
        <form method="GET" action="<?= $GLOBALS['base_url'] ?>/mobile-punch-report">
            <div class="d-flex align-items-center gap-2">
                <label for="date" class="form-label mb-0 fw-bold">Select Date:</label>
                <input type="date" name="date" id="date" class="form-control" style="width: 200px;" value="<?= htmlspecialchars($reportDate) ?>">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>
    </div>

    <!--  Employee Table ── -->
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table no-border-table mb-20 multiple_tables">
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
                        <?php foreach ($reportData as $i => $row): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($row['emp_name']) ?></td>
                                <td><?= htmlspecialchars($row['dept_name']) ?></td>
                                <td><?= htmlspecialchars($row['designation_name']) ?></td>
                                <td><?= htmlspecialchars($row['punch_in_time']) ?: '--:--' ?></td>
                                <td><?= htmlspecialchars($row['punch_out_time']) ?: '--:--' ?></td>
                                <td><span class="badge-status bs-present"><?= htmlspecialchars($row['today_status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

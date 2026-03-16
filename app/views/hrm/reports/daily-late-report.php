<div class="row">

    <!-- ── Header ── -->
    <div class="report-header">
        <div class="brand">
            <div class="brand-text">
                <h1>Late Report</h1>
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


    <!--  Employee Table ── -->
    <div class="col-xxl-12">
        <!-- ── Daily Late Report ── -->
        <div class="col-xxl-12">
            <div class="card__wrapper">
                <div class="table__wrapper table-responsive">
                    <table class="table no-border-table mb-20 multiple_tables">
                        <thead>
                            <tr>
                                <th>SL</th>
                                <th>Emp ID</th>
                                <th>Employee Name</th>
                                <th>Designation</th>
                                <th>Department</th>
                                <th>Workplace</th>
                                <th>Status</th>
                                <th>Entry</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportData as $key => $data): ?>
                                <tr>
                                    <td><?= $key + 1 ?></td>
                                    <td><?= htmlspecialchars($data['emp_id'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($data['emp_name']) ?></td>
                                    <td><?= htmlspecialchars($data['designation_name']) ?></td>
                                    <td><?= htmlspecialchars($data['dept_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($data['workplace'] ?? 'N/A') ?></td>
                                    <td class=""><span class="badge-status bs-late"><?= htmlspecialchars($data['today_status'] ?? 'Absent') ?></span></td>
                                    <td><?= !empty($data['punch_in_time']) ? date('h:i A', strtotime($data['punch_in_time'])) : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
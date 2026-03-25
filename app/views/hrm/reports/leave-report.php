
<div class="row">

    <!-- ── Header ── -->
    <div class="report-header">
        <div class="brand">
            <div class="brand-text">
                <h1>Leave Report</h1>
                <p>Human Resource Management System</p>
            </div>
        </div>

        <div class="report-meta d-flex gap-2">
            <div class="date-badge mb-2">
                &nbsp;<?= date('l, d F Y') ?>
            </div>
            <div>
                <a href="<?= $GLOBALS['base_url'] ?>/export-leave-report" class="btn btn-success">
                    Export CSV
                </a>
            </div>
            <div>
                <a href="<?= $GLOBALS['base_url'] ?>/export-leave-report-pdf" class="btn btn-danger">
                    Export PDF
                </a>
            </div>
        </div>
    </div>


    <!--  Employee Leave Table ── -->
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table no-border-table mb-20 multiple_tables">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Employee Name</th>
                            <th>Leave Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Requested Days</th>
                            <th>Approved Days</th>
                            <th>Reason</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportData as $i => $row): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($row['employee_name']) ?></td>
                                <td><span class="bd-badge bg-primary"><?= htmlspecialchars($row['leave_type_name']) ?></span></td>
                                <td><?= date('d M Y', strtotime($row['start_date'])) ?></td>
                                <td><?= date('d M Y', strtotime($row['end_date'])) ?></td>
                                <td><span class="badge-status bs-present"><?= $row['requested_days'] ?></span></td>
                                <td><span class="badge-status bs-leave"><?= $row['approved_days'] ?? 0 ?></span></td>
                                <td><?= htmlspecialchars($row['reason']) ?></td>
                                <td>
                                    <?php if ($row['approval_status'] == 'approved') :  ?>
                                        <span class="badge-status bs-present"><?= ucfirst($row['approval_status']) ?></span>
                                    <?php elseif ($row['approval_status'] == 'rejected') : ?>
                                        <span class="badge-status bs-absent"><?= ucfirst($row['approval_status']) ?></span>
                                    <?php else : ?>
                                        <span class="badge-status bs-late"><?= ucfirst($row['approval_status']) ?></span>
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

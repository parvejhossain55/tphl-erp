<?php
$base_url = $GLOBALS['base_url'];
?>
<div class="container-fluid">
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h4 class="page-title fw-semibold fs-18 mb-0">Daily Progress Reports</h4>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Daily Reports</li>
            </ol>
        </div>
        <div class="my-auto">
            <a href="<?= $base_url ?>/con-daily-report-create" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> New Report
            </a>
        </div>
    </div>

    <div class="card custom-card">
        <div class="card-header">
            <h6 class="card-title mb-0"><i class="bi bi-journal-text me-2 text-primary"></i> Daily Progress Reports (DPR)</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="dprTable" class="table table-bordered table-hover text-nowrap">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Report Date</th>
                            <th>Project</th>
                            <th>Site Engineer</th>
                            <th>Work Done (Summary)</th>
                            <th>Progress %</th>
                            <th>Manpower</th>
                            <th>Weather</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($reports)): ?>
                            <?php foreach ($reports as $i => $r): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= date('d M Y', strtotime($r['report_date'])) ?></td>
                                    <td><?= htmlspecialchars($r['project_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($r['engineer_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars(mb_substr($r['work_done'], 0, 60)) ?>...</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height:8px;">
                                                <div class="progress-bar bg-success" style="width:<?= min($r['progress_percentage'],100) ?>%"></div>
                                            </div>
                                            <span class="small"><?= number_format($r['progress_percentage'],1) ?>%</span>
                                        </div>
                                    </td>
                                    <td><?= $r['manpower_count'] ?? '-' ?></td>
                                    <td><?= htmlspecialchars($r['weather_condition'] ?? '-') ?></td>
                                    <td>
                                        <a href="<?= $base_url ?>/con-daily-report/<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="text-center text-muted">No reports found. <a href="<?= $base_url ?>/con-daily-report-create">Add one</a></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function () {
    $('#dprTable').DataTable({ responsive: true, pageLength: 25, order: [[1, 'desc']] });
});
</script>

<?php
$base_url = $GLOBALS['base_url'];
$r = $report;
?>
<div class="container-fluid">
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h4 class="page-title fw-semibold fs-18 mb-0">DPR — <?= date('d M Y', strtotime($r['report_date'])) ?></h4>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-daily-reports">Daily Reports</a></li>
                <li class="breadcrumb-item active">Detail</li>
            </ol>
        </div>
        <a href="<?= $base_url ?>/con-daily-reports" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="row g-3 justify-content-center">
        <div class="col-xl-8">
            <div class="card custom-card">
                <div class="card-header bg-primary text-white">
                    <h6 class="card-title mb-0 text-white">
                        <i class="bi bi-journal-text me-2"></i>
                        Daily Progress Report — <?= date('d M Y', strtotime($r['report_date'])) ?>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded text-center">
                                <div class="text-muted small mb-1">Project</div>
                                <div class="fw-semibold"><?= htmlspecialchars($r['project_name'] ?? '-') ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded text-center">
                                <div class="text-muted small mb-1">Site Engineer</div>
                                <div class="fw-semibold"><?= htmlspecialchars($r['engineer_name'] ?? '-') ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded text-center">
                                <div class="text-muted small mb-1">Weather</div>
                                <div class="fw-semibold"><?= htmlspecialchars($r['weather_condition'] ?? '-') ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-success-subtle rounded text-center">
                                <div class="text-muted small mb-1">Progress %</div>
                                <div class="fs-2 fw-bold text-success"><?= number_format($r['progress_percentage'], 1) ?>%</div>
                                <div class="progress mt-2" style="height:10px;">
                                    <div class="progress-bar bg-success" style="width:<?= min($r['progress_percentage'],100) ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-info-subtle rounded text-center">
                                <div class="text-muted small mb-1">Manpower</div>
                                <div class="fs-2 fw-bold text-info"><?= $r['manpower_count'] ?? '-' ?></div>
                                <div class="text-muted small">Workers on site</div>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h6 class="fw-semibold mb-3">Work Done Today</h6>
                    <div class="p-3 bg-light rounded">
                        <?= nl2br(htmlspecialchars($r['work_done'])) ?>
                    </div>

                    <div class="mt-3 text-muted small">
                        Created by <strong><?= htmlspecialchars($r['created_by_name'] ?? '-') ?></strong>
                        on <?= date('d M Y H:i', strtotime($r['created_at'])) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

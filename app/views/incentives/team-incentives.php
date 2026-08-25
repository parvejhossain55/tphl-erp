<?php
$page_title = "Team Sales Incentives";
$page = 'Team Incentives';
$show_breadcrumb = true;
?>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-dark">
                <i class="fa-solid fa-users text-primary me-2"></i>Team Sales Incentives
            </h3>
            <p class="text-muted small mb-0">Track your team's total collection and your hierarchy commissions.</p>
        </div>
        <div>
            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fs-7 fw-semibold">
                <i class="fa-solid fa-user-tie me-1"></i> <?= ucwords(str_replace('_', ' ', $teamMetrics['role_type'])) ?> Portal
            </span>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-success">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-semibold">Total Team Collection (<?= date('F Y', strtotime($teamMetrics['month'] . '-01')) ?>)</span>
                        <div class="avatar avatar-sm bg-success-subtle text-success rounded-2">
                            <i class="fa-solid fa-money-bill-trend-up"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1 text-success">৳<?= number_format($teamMetrics['total_collection'], 2) ?></h3>
                    <span class="text-muted small">Based on verified payments</span>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-info">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-semibold">Threshold Rule</span>
                        <div class="avatar avatar-sm bg-info-subtle text-info rounded-2">
                            <i class="fa-solid fa-scale-balanced"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-1 text-dark">৳<?= number_format($teamMetrics['reward_amount'], 2) ?> per ৳<?= number_format($teamMetrics['threshold_amount'], 2) ?></h4>
                    <span class="text-muted small">Target multiplier rule</span>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-12 mb-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-primary">
                <div class="card-body p-4 position-relative overflow-hidden">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-semibold">Your Earned Hierarchy Commission</span>
                        <div class="avatar avatar-sm bg-primary-subtle text-primary rounded-2">
                            <i class="fa-solid fa-trophy"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1 text-primary">৳<?= number_format($teamMetrics['incentive_earned'], 2) ?></h3>
                    <span class="text-muted small">Achieved <?= $teamMetrics['slabs_hit'] ?> multiplier(s)</span>
                </div>
            </div>
        </div>
    </div>
</div>

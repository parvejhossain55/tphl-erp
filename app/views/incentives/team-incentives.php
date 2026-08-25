<?php
$page_title = "Team Sales Incentives";
$page = 'Team Incentives';
$show_breadcrumb = true;
?>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-dark">
                <i class="fa-solid fa-users text-primary me-2"></i><?= $teamMetrics['team_name'] ? htmlspecialchars($teamMetrics['team_name']) : 'Team Sales' ?> Incentives
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
                        <span class="text-muted fw-semibold">Team Target & Achievement (<?= date('F Y', strtotime($teamMetrics['month'] . '-01')) ?>)</span>
                        <div class="avatar avatar-sm bg-success-subtle text-success rounded-2">
                            <i class="fa-solid fa-bullseye"></i>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mb-1">
                        <div>
                            <span class="text-muted small d-block">Target: ৳<?= number_format($teamMetrics['team_target'] ?? 0, 2) ?></span>
                            <h3 class="fw-bold mb-0 text-success">৳<?= number_format($teamMetrics['team_achieved'] ?? 0, 2) ?></h3>
                        </div>
                        <h4 class="text-success mb-0"><?= number_format($teamMetrics['team_achievement_pct'] ?? 0, 2) ?>%</h4>
                    </div>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= min(100, $teamMetrics['team_achievement_pct'] ?? 0) ?>%;" aria-valuenow="<?= $teamMetrics['team_achievement_pct'] ?? 0 ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
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
                    <h4 class="fw-bold mb-1 text-dark">৳<?= number_format($teamMetrics['reward_amount'] ?? 0, 2) ?> per ৳<?= number_format($teamMetrics['threshold_amount'] ?? 0, 2) ?></h4>
                    <span class="text-muted small">Eligible Payout: <?= $teamMetrics['eligible_payout_pct'] ?? 0 ?>%</span>
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
                    <h3 class="fw-bold mb-1 text-primary">৳<?= number_format($teamMetrics['incentive_earned'] ?? 0, 2) ?></h3>
                    <span class="text-muted small">Achieved <?= $teamMetrics['slabs_hit'] ?? 0 ?> multiplier(s)</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Team Sales Breakdown Table -->
    <div class="card border-0 shadow-sm rounded-3 mt-4">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-list-check text-primary me-2"></i>This Month's Team Sales Breakdown
            </h5>
            <span class="badge bg-light text-dark border"><i class="fa-solid fa-calendar me-1"></i> <?= date('F Y', strtotime($teamMetrics['month'] . '-01')) ?></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted extra-small text-uppercase">
                        <tr>
                            <th class="ps-4">Booking Date</th>
                            <th>Sales Person</th>
                            <th>Project</th>
                            <th>Flat</th>
                            <th class="text-end pe-4">Land Share Value (Achievement)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($teamSalesBreakdown)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="fa-regular fa-folder-open fs-2 mb-2"></i>
                                    <p class="mb-0">No sales bookings found for this month.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($teamSalesBreakdown as $sale): ?>
                                <tr>
                                    <td class="ps-4">
                                        <span class="fw-semibold text-dark"><?= date('d M, Y', strtotime($sale['booking_date'])) ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar avatar-xs bg-primary-subtle text-primary rounded-circle fw-bold">
                                                <?= strtoupper(substr($sale['sales_person'], 0, 1)) ?>
                                            </div>
                                            <span class="fw-semibold text-dark"><?= htmlspecialchars($sale['sales_person']) ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($sale['project_name']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($sale['flat_name']) ?></span></td>
                                    <td class="text-end pe-4">
                                        <h6 class="fw-bold text-success mb-0">৳<?= number_format($sale['land_share_value'], 2) ?></h6>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

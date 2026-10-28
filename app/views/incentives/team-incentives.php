<?php
$page_title = "Team Sales Incentives";
$page = 'Team Incentives';
$show_breadcrumb = true;
$displayMonthName = date('F Y', strtotime(($selectedMonth ?? date('Y-m')) . '-01'));
?>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold mb-1 text-dark">
                <i class="fa-solid fa-users text-primary me-2"></i><?= !empty($teamMetrics['team_name']) ? htmlspecialchars($teamMetrics['team_name']) : 'Team Sales' ?> Incentives
            </h3>
            <p class="text-muted small mb-0">Track your team's monthly target achievements, member-wise collections, and your Team Leader commission.</p>
        </div>
        <div>
            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fs-7 fw-semibold">
                <i class="fa-solid fa-user-tie me-1"></i> <?= ucwords(str_replace('_', ' ', $teamMetrics['role_type'] ?? 'Team Leader')) ?> Portal
            </span>
        </div>
    </div>

    <!-- Filter Bar (Month & Team) -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-body p-3">
            <form method="GET" action="<?= $GLOBALS['base_url'] ?>/my-team-incentives" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1"><i class="fa-regular fa-calendar-days me-1 text-primary"></i>Month</label>
                    <input type="month" name="month" class="form-control form-control-sm" value="<?= htmlspecialchars($selectedMonth ?? date('Y-m')) ?>">
                </div>
                <?php if (!empty($isSalesHead) || !empty($teams)): ?>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1"><i class="fa-solid fa-users me-1 text-primary"></i>Team Filter</label>
                    <select name="team_id" class="form-select form-select-sm">
                        <option value="">All Teams (Company-Wide)</option>
                        <?php foreach ($teams as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= (!empty($selectedTeamId) && (int)$selectedTeamId === (int)$t['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold">
                        <i class="fa-solid fa-filter me-1"></i> Filter
                    </button>
                </div>
                <?php if (!empty($selectedTeamId) || (!empty($selectedMonth) && $selectedMonth !== date('Y-m'))): ?>
                <div class="col-md-2">
                    <a href="<?= $GLOBALS['base_url'] ?>/my-team-incentives" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="fa-solid fa-rotate-left me-1"></i> Reset
                    </a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Metrics Cards Row -->
    <div class="row mb-4">
        <!-- Team Target & Achievement -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-success">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-semibold small">Team Target & Achievement (<?= $displayMonthName ?>)</span>
                        <div class="avatar avatar-sm bg-success-subtle text-success rounded-2">
                            <i class="fa-solid fa-bullseye"></i>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mb-1">
                        <div>
                            <span class="text-muted small d-block">Target: ৳<?= number_format($teamMetrics['team_target'] ?? 0, 2) ?></span>
                            <h4 class="fw-bold mb-0 text-success">৳<?= number_format($teamMetrics['team_achieved'] ?? 0, 2) ?></h4>
                        </div>
                        <h5 class="text-success mb-0"><?= number_format($teamMetrics['team_achievement_pct'] ?? 0, 1) ?>%</h5>
                    </div>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= min(100, $teamMetrics['team_achievement_pct'] ?? 0) ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Team Money Collected -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-warning">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-semibold small">Total Money Collected</span>
                        <div class="avatar avatar-sm bg-warning-subtle text-warning rounded-2">
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-1 text-warning">৳<?= number_format($teamMetrics['team_collected'] ?? 0, 2) ?></h4>
                    <span class="text-muted small">Verified payments for team bookings in this cycle</span>
                </div>
            </div>
        </div>

        <!-- Qualified Sales Volume -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-info">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-semibold small">Qualified Sales Volume</span>
                        <div class="avatar avatar-sm bg-info-subtle text-info rounded-2">
                            <i class="fa-solid fa-user-check"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-1 text-dark">৳<?= number_format($teamMetrics['eligible_sales_volume'] ?? 0, 2) ?></h4>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-info-subtle text-info fw-semibold"><?= $teamMetrics['qualified_members_count'] ?? 0 ?> / <?= $teamMetrics['total_members_count'] ?? 0 ?> Qualified</span>
                        <span class="text-muted extra-small">Achieved target or ≥ 25L</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Team Leader Commission Earned -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-primary">
                <div class="card-body p-3 position-relative overflow-hidden">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-semibold small">Team Leader Commission</span>
                        <div class="avatar avatar-sm bg-primary-subtle text-primary rounded-2">
                            <i class="fa-solid fa-trophy"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-1 text-primary">৳<?= number_format($teamMetrics['incentive_earned'] ?? 0, 2) ?></h4>
                    <span class="text-muted small"><?= $teamMetrics['tl_rate'] ?? 0.05 ?>% of qualified members' sales</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Team Members Target, Achievement & Collection Table -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-users-gear text-primary me-2"></i>Team Members Performance & Targets (<?= $displayMonthName ?>)
            </h5>
            <span class="badge bg-light text-dark border">
                <i class="fa-solid fa-calendar me-1 text-primary"></i> <?= $displayMonthName ?>
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted extra-small text-uppercase">
                        <tr>
                            <th class="ps-4">Sales Person</th>
                            <th>Designation</th>
                            <th class="text-end">Target (৳)</th>
                            <th class="text-end">Achieved (Land Share)</th>
                            <th class="text-center">Achievement %</th>
                            <th class="text-end">Money Collected</th>
                            <th class="text-center">Qualification</th>
                            <th class="text-end">Member Incentive (৳)</th>
                            <th class="text-end">TL Commission (৳)</th>
                            <th class="text-center pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($teamMetrics['member_breakdown'])): ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="fa-regular fa-folder-open fs-2 mb-2"></i>
                                    <p class="mb-0">No team members found for this team.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($teamMetrics['member_breakdown'] as $mb): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar avatar-xs bg-primary-subtle text-primary rounded-circle fw-bold">
                                                <?= strtoupper(substr($mb['full_name'] ?? 'U', 0, 1)) ?>
                                            </div>
                                            <div>
                                                <span class="fw-bold text-dark d-block"><?= htmlspecialchars($mb['full_name'] ?? 'N/A') ?></span>
                                                <?php if (!empty($mb['employee_id'])): ?>
                                                    <span class="text-muted extra-small">ID: <?= htmlspecialchars($mb['employee_id']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="text-muted small"><?= htmlspecialchars($mb['designation'] ?? 'Executive') ?></span></td>
                                    <td class="text-end">
                                        <span class="fw-bold text-dark">৳<?= number_format($mb['target'] ?? 0, 2) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-semibold text-success">৳<?= number_format($mb['achieved_land_share'] ?? 0, 2) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $pct = (float)($mb['achievement_pct'] ?? 0);
                                        $badgeClass = ($pct >= 100) ? 'bg-success-subtle text-success' : (($pct >= 50) ? 'bg-info-subtle text-info' : 'bg-secondary-subtle text-secondary');
                                        ?>
                                        <span class="badge <?= $badgeClass ?> fw-bold px-2 py-1"><?= number_format($pct, 1) ?>%</span>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold text-warning">৳<?= number_format($mb['collected_amount'] ?? 0, 2) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($mb['is_qualified'])): ?>
                                            <span class="badge bg-success-subtle text-success fw-semibold"><i class="fa-solid fa-check me-1"></i> Qualified</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border fw-semibold">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold text-success">৳<?= number_format($mb['earned_incentive'] ?? 0, 2) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold text-primary">৳<?= number_format($mb['tl_contribution'] ?? 0, 2) ?></span>
                                    </td>
                                    <td class="text-center pe-4">
                                        <?php 
                                            $canSetTargetAction = in_array((int)($_SESSION['user']['role_id'] ?? 0), [1, 3, 6, 20, 30, 32, 33, 34, 35], true);
                                        ?>
                                        <?php if ($canSetTargetAction): ?>
                                            <a href="<?= $GLOBALS['base_url'] ?>/set-sales-target/<?= $mb['user_id'] ?>" class="btn btn-outline-primary btn-sm py-1 px-2" title="Set/Edit Target">
                                                <i class="fa-solid fa-pen-to-square me-1"></i> Target
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">View Only</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Team Sales Breakdown Table -->
    <div class="card border-0 shadow-sm rounded-3 mt-2">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-list-check text-primary me-2"></i>This Month's Team Sales Bookings
            </h5>
            <span class="badge bg-light text-dark border"><i class="fa-solid fa-calendar me-1"></i> <?= $displayMonthName ?></span>
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
                            <th class="text-end">Land Share Value</th>
                            <th class="text-end">Money Collected</th>
                            <th class="text-center pe-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($teamSalesBreakdown)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
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
                                                <?= strtoupper(substr($sale['sales_person'] ?? 'U', 0, 1)) ?>
                                            </div>
                                            <span class="fw-semibold text-dark"><?= htmlspecialchars($sale['sales_person'] ?? 'N/A') ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($sale['project_name'] ?? 'N/A') ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($sale['flat_name'] ?? 'N/A') ?></span></td>
                                    <td class="text-end">
                                        <h6 class="fw-bold text-success mb-0">৳<?= number_format($sale['land_share_value'] ?? 0, 2) ?></h6>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold text-warning">৳<?= number_format($sale['total_collected'] ?? 0, 2) ?></span>
                                    </td>
                                    <td class="text-center pe-4">
                                        <span class="badge bg-success-subtle text-success text-capitalize"><?= htmlspecialchars($sale['booking_status'] ?? 'Booked') ?></span>
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

<?php
$base_url = $GLOBALS['base_url'] ?? '/TPHL-ERP';
$displayMonthName = date('F Y', strtotime(($selectedMonth ?? date('Y-m')) . '-01'));
$cycleStartStr = !empty($monthlyStats['cycle_start']) ? date('d M, Y', strtotime($monthlyStats['cycle_start'])) : '26th of last month';
$cycleEndStr   = !empty($monthlyStats['cycle_end']) ? date('d M, Y', strtotime($monthlyStats['cycle_end'])) : '25th of this month';
$isTargetAchieved = !empty($monthlyStats['is_eligible']);
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold mb-1 text-dark">
                <i class="fa-solid fa-hand-holding-dollar text-primary me-2"></i>My Sales Incentives
            </h3>
            <p class="text-muted small mb-0">Track your monthly targets, sales performance, incentive calculations, and claims.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill fs-7 fw-semibold">
                <i class="fa-regular fa-calendar-days text-primary me-1"></i> Cycle: <?= $cycleStartStr ?> – <?= $cycleEndStr ?>
            </span>
            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fs-7 fw-semibold">
                <i class="fa-solid fa-user-tie me-1"></i> Sales Portal
            </span>
        </div>
    </div>

    <!-- Filter Bar (Month Selector) -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-body p-3">
            <form method="GET" action="<?= $base_url ?>/my-incentives" class="row g-2 align-items-end">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label small fw-semibold text-muted mb-1">
                        <i class="fa-regular fa-calendar-days me-1 text-primary"></i>Cycle Month
                    </label>
                    <input type="month" name="month" class="form-control form-control-sm" value="<?= htmlspecialchars($selectedMonth ?? date('Y-m')) ?>">
                </div>
                <div class="col-md-2 col-sm-3">
                    <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold">
                        <i class="fa-solid fa-filter me-1"></i> Filter
                    </button>
                </div>
                <?php if (!empty($selectedMonth) && $selectedMonth !== ($activeCycle ?? date('Y-m'))): ?>
                <div class="col-md-2 col-sm-3">
                    <a href="<?= $base_url ?>/my-incentives" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="fa-solid fa-rotate-left me-1"></i> Reset
                    </a>
                </div>
                <?php endif; ?>
                <div class="col-md-5 text-md-end text-muted small mt-2 mt-md-0">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                        <i class="fa-solid fa-circle-check me-1"></i> Cycle: 26th to 25th Cut-off Active
                    </span>
                </div>
            </form>
        </div>
    </div>

    <!-- Main Navigation Tabs: My Incentive & My Target -->
    <ul class="nav nav-pills mb-4 gap-2" id="incentiveMainTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold px-4 py-2 rounded-3 shadow-sm" id="tab-my-incentive-btn" data-bs-toggle="tab" data-bs-target="#tab-my-incentive" type="button" role="tab" aria-selected="true">
                <i class="fa-solid fa-hand-holding-dollar me-2"></i>My Incentive
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold px-4 py-2 rounded-3 shadow-sm bg-white text-dark border" id="tab-my-target-btn" data-bs-toggle="tab" data-bs-target="#tab-my-target" type="button" role="tab" aria-selected="false">
                <i class="fa-solid fa-bullseye me-2 text-danger"></i>My Target
            </button>
        </li>
        <?php if (!empty($isTeamLeader)): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold px-4 py-2 rounded-3 shadow-sm bg-white text-dark border" id="tab-team-gain-btn" data-bs-toggle="tab" data-bs-target="#tab-team-gain" type="button" role="tab" aria-selected="false">
                <i class="fa-solid fa-people-arrows me-2 text-warning"></i>From My Team Member Gain Incentive (0.05%)
            </button>
        </li>
        <?php endif; ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold px-4 py-2 rounded-3 shadow-sm bg-white text-dark border" id="tab-unassigned-split-btn" data-bs-toggle="tab" data-bs-target="#tab-unassigned-split" type="button" role="tab" aria-selected="false">
                <i class="fa-solid fa-arrows-split-up-and-left me-2 text-info"></i>Unassigned Project Incentive (70% - 30%)
            </button>
        </li>
    </ul>

    <div class="tab-content" id="incentiveMainTabsContent">
        <!-- TAB 1: MY INCENTIVE -->
        <div class="tab-pane fade show active" id="tab-my-incentive" role="tabpanel" aria-labelledby="tab-my-incentive-btn">

            <!-- Target Status Alert Banner -->
            <?php if ($isTargetAchieved): ?>
                <div class="alert alert-success border-0 shadow-sm rounded-3 d-flex align-items-center p-3 mb-4">
                    <div class="avatar avatar-md bg-success text-white rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center me-3" style="width: 44px; height: 44px; font-size: 20px;">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1 text-success">Target Filled Up / Qualified for Sales Incentive!</h6>
                        <span class="small text-light">You have achieved ৳<?= number_format($monthlyStats['total_achieved_land_share'], 2) ?> in land share sales this cycle (Minimum required: ৳25,00,000.00). All earned incentives are unlocked for claim.</span>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning border-0 shadow-sm rounded-3 d-flex align-items-center p-3 mb-4">
                    <div class="avatar avatar-md bg-warning text-dark rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center me-3" style="width: 44px; height: 44px; font-size: 20px;">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1 text-dark">Target In Progress (Minimum 25 Lakh Land Share Required)</h6>
                        <span class="small text-dark">Achieved so far: <strong>৳<?= number_format($monthlyStats['total_achieved_land_share'], 2) ?></strong>. Complete sales booking of at least ৳25 Lakh in land share to fill up your eligibility and unlock incentive claims.</span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Stat Summary Cards Row (Target, Achievement, Collection, Incentive) -->
            <div class="row mb-4">
                <!-- Target & Achievement (Month) -->
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-success">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted small fw-semibold">Target & Achieved (<?= $displayMonthName ?>)</span>
                                <div class="avatar avatar-sm bg-success-subtle text-success rounded-2">
                                    <i class="fa-solid fa-bullseye"></i>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-end mb-1">
                                <div>
                                    <span class="text-muted extra-small d-block">Target: ৳<?= number_format($monthlyStats['effective_target'] ?? 0, 2) ?></span>
                                    <h4 class="fw-bold mb-0 text-success">৳<?= number_format($monthlyStats['total_achieved_land_share'] ?? 0, 2) ?></h4>
                                </div>
                                <h5 class="text-success mb-0"><?= number_format($monthlyStats['achievement_pct'] ?? 0, 1) ?>%</h5>
                            </div>
                            <div class="progress mt-2" style="height: 6px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?= min(100, $monthlyStats['achievement_pct'] ?? 0) ?>%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Money Collected in Cycle -->
                <div class="col-xl-2 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-warning">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted small fw-semibold">Collected in Cycle</span>
                                <div class="avatar avatar-sm bg-warning-subtle text-warning rounded-2">
                                    <i class="fa-solid fa-hand-holding-dollar"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold mb-1 text-warning">৳<?= number_format($cycleCollected ?? 0, 2) ?></h4>
                            <span class="text-muted extra-small">Verified payments in cycle</span>
                        </div>
                    </div>
                </div>

                <!-- Earned Incentive (Cycle) -->
                <div class="col-xl-2 col-md-4 mb-3">
                    <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-primary">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted small fw-semibold">Earned Incentive</span>
                                <div class="avatar avatar-sm bg-primary-subtle text-primary rounded-2">
                                    <i class="fa-solid fa-chart-line"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold mb-1 text-primary">৳<?= number_format($monthlyStats['total_earned_incentive'] ?? 0, 2) ?></h4>
                            <span class="text-muted extra-small">Eligible in <?= $displayMonthName ?></span>
                        </div>
                    </div>
                </div>

                <!-- On Hold Incentive (Outside Designated 70%) -->
                <div class="col-xl-2 col-md-4 mb-3">
                    <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-secondary">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted small fw-semibold">On Hold (70% Share)</span>
                                <div class="avatar avatar-sm bg-secondary-subtle text-secondary rounded-2">
                                    <i class="fa-solid fa-pause"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold mb-1 text-secondary">৳<?= number_format($monthlyStats['total_on_hold_incentive'] ?? 0, 2) ?></h4>
                            <span class="text-muted extra-small">Outside designated project</span>
                        </div>
                    </div>
                </div>

                <!-- Available Wallet Balance -->
                <div class="col-xl-3 col-md-4 mb-3">
                    <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-info">
                        <div class="card-body p-3 position-relative overflow-hidden">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted small fw-semibold">Wallet Available</span>
                                <div class="avatar avatar-sm bg-info-subtle text-info rounded-2">
                                    <i class="fa-solid fa-wallet"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold mb-1 text-info">৳<?= number_format($walletSummary['available_balance'] ?? 0, 2) ?></h4>
                            <span class="text-muted extra-small">Approved amount ready for payout</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales & Incentive Claims Table Card -->
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom flex-wrap gap-2">
                            <div>
                                <h5 class="fw-bold mb-0 text-dark">
                                    <i class="fa-solid fa-list-check text-primary me-2"></i>My Project Sales & Incentive Eligibility (<?= $displayMonthName ?>)
                                </h5>
                                <span class="text-muted extra-small">Showing bookings within the active cycle (<?= $cycleStartStr ?> – <?= $cycleEndStr ?>). Once claimed or paid, bookings cannot be claimed again.</span>
                            </div>
                            <span class="badge bg-light text-dark border">
                                <i class="fa-regular fa-calendar-days text-primary me-1"></i> <?= $displayMonthName ?>
                            </span>
                        </div>

                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light text-muted extra-small text-uppercase">
                                        <tr>
                                            <th class="ps-4">Booking Ref</th>
                                            <th>Customer & Phone</th>
                                            <th>Project & Flat Unit</th>
                                            <th>Land Share Base</th>
                                            <th>Total Collected</th>
                                            <th>Incentive Rate (%)</th>
                                            <th>Incentive Amount</th>
                                            <th class="text-center">Booking Money</th>
                                            <th class="text-center">Registration</th>
                                            <th class="text-center">Claim Status</th>
                                            <th class="pe-4 text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($mySalesBookings)): ?>
                                            <tr>
                                                <td colspan="11" class="text-center py-4 text-muted">
                                                    <i class="fa-solid fa-folder-open fs-3 d-block mb-2"></i>
                                                    No sales bookings found assigned to your user account for this cycle.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($mySalesBookings as $sale): ?>
                                                <tr>
                                                    <!-- Booking Ref -->
                                                    <td class="ps-4">
                                                        <span class="fw-bold text-dark d-block"><?= htmlspecialchars($sale['booking_no']) ?></span>
                                                        <span class="badge bg-light text-muted border border-light-subtle extra-small">ID #<?= $sale['id'] ?></span>
                                                    </td>

                                                    <!-- Customer -->
                                                    <td>
                                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($sale['customer_name']) ?></div>
                                                        <span class="text-muted extra-small"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($sale['phone']) ?></span>
                                                    </td>

                                                    <!-- Project & Unit -->
                                                    <td>
                                                        <span class="fw-bold text-dark d-block"><?= htmlspecialchars($sale['project_name']) ?></span>
                                                        <span class="text-primary extra-small fw-semibold"><?= htmlspecialchars($sale['flat_name']) ?> (<?= $sale['sqft'] ?> sqft)</span>
                                                    </td>

                                                    <!-- Land Share (Base) & Flat Price -->
                                                    <td>
                                                        <span class="badge bg-primary-subtle text-primary fw-bold fs-7 mb-1" title="Land Share Value">
                                                            <i class="fa-solid fa-map-location-dot me-1"></i>৳<?= number_format($sale['land_share_value'], 2) ?>
                                                        </span>
                                                        <span class="text-muted extra-small d-block">Flat: ৳<?= number_format($sale['flat_price'], 2) ?></span>
                                                    </td>

                                                    <!-- Total Money Collected So Far -->
                                                    <td>
                                                        <span class="badge bg-success-subtle text-success fw-bold fs-7 px-2 py-1 border border-success-subtle">
                                                            ৳<?= number_format($sale['total_collected'], 2) ?>
                                                        </span>
                                                    </td>

                                                    <!-- Applied Rate -->
                                                    <td>
                                                        <span class="badge bg-info-subtle text-info fw-bold fs-7 mb-1 d-inline-block"><?= number_format($sale['incentive_percentage'], 2) ?>%</span>
                                                        <span class="extra-small text-muted d-block"><?= htmlspecialchars($sale['category_name'] ?? 'Standard Rate') ?></span>
                                                    </td>

                                                    <!-- Incentive Amount -->
                                                    <td>
                                                        <h6 class="fw-bold text-success mb-0">৳<?= number_format($sale['calculated_incentive'], 2) ?></h6>
                                                        <span class="text-muted extra-small">on Land Share</span>
                                                    </td>

                                                    <!-- Booking Money Badge -->
                                                    <td class="text-center">
                                                        <?php if ($sale['booking_completed']): ?>
                                                            <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 extra-small border border-success-subtle d-block mb-1">
                                                                <i class="fa-solid fa-circle-check me-1"></i>Paid & Verified
                                                            </span>
                                                            <span class="extra-small fw-bold text-success">৳<?= number_format($sale['booking_money_collected'], 2) ?></span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning-subtle text-warning rounded-pill px-3 py-1 extra-small border border-warning-subtle">
                                                                <i class="fa-solid fa-clock me-1"></i>Pending
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>

                                                    <!-- Registration Badge -->
                                                    <td class="text-center">
                                                        <?php if ($sale['registration_completed']): ?>
                                                            <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 extra-small border border-success-subtle">
                                                                <i class="fa-solid fa-file-signature me-1"></i>Completed
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-1 extra-small border border-danger-subtle">
                                                                <i class="fa-solid fa-hourglass-half me-1"></i>Incomplete
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>

                                                    <!-- Claim Status Badge -->
                                                    <td class="text-center">
                                                        <?php if (!empty($sale['already_claimed'])): ?>
                                                            <span class="badge bg-danger text-white rounded-pill px-3 py-1 extra-small shadow-sm" title="<?= htmlspecialchars($sale['remarks'] ?? 'Incentive already claimed for this flat unit') ?>">
                                                                <i class="fa-solid fa-ban me-1"></i>Duplicate (Ineligible)
                                                            </span>
                                                        <?php elseif (!empty($sale['is_on_hold']) && in_array($sale['claim_status'], ['eligible', 'ineligible'])): ?>
                                                            <span class="badge bg-warning text-dark rounded-pill px-3 py-1 extra-small shadow-sm" title="On Hold: Achieve assigned target / 25L to unlock">
                                                                <i class="fa-solid fa-pause me-1"></i>On Hold (70%)
                                                            </span>
                                                        <?php elseif ($sale['claim_status'] === 'eligible'): ?>
                                                            <span class="badge bg-primary text-white rounded-pill px-3 py-1 extra-small shadow-sm">
                                                                <i class="fa-solid fa-star me-1"></i>Eligible
                                                            </span>
                                                        <?php elseif ($sale['claim_status'] === 'claimed'): ?>
                                                            <span class="badge bg-warning text-dark rounded-pill px-3 py-1 extra-small">
                                                                <i class="fa-solid fa-paper-plane me-1"></i>Claimed (Pending Review)
                                                            </span>
                                                        <?php elseif ($sale['claim_status'] === 'approved'): ?>
                                                            <span class="badge bg-info text-white rounded-pill px-3 py-1 extra-small">
                                                                <i class="fa-solid fa-check-double me-1"></i>Approved (Pending Payout)
                                                            </span>
                                                        <?php elseif ($sale['claim_status'] === 'paid'): ?>
                                                            <span class="badge bg-success text-white rounded-pill px-3 py-1 extra-small">
                                                                <i class="fa-solid fa-shield-check me-1"></i>Paid & Settled
                                                            </span>
                                                        <?php elseif (empty($sale['booking_completed'])): ?>
                                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1 extra-small" title="<?= htmlspecialchars($sale['remarks'] ?: 'Booking Money pending verification by Accounts') ?>">
                                                                <i class="fa-solid fa-clock-rotate-left me-1"></i>Payment Verification Pending
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary text-white rounded-pill px-3 py-1 extra-small" title="<?= htmlspecialchars($sale['remarks'] ?? '') ?>">
                                                                <i class="fa-solid fa-lock me-1"></i>Not Eligible Yet
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>

                                                    <!-- Action Button -->
                                                    <td class="pe-4 text-end">
                                                        <?php if (!empty($sale['already_claimed'])): ?>
                                                            <button class="btn btn-sm btn-light text-danger rounded-2 disabled" disabled title="Incentive already claimed for this flat unit in another booking/month">
                                                                <i class="fa-solid fa-ban me-1"></i>Duplicate
                                                            </button>
                                                        <?php elseif ($sale['claim_status'] === 'eligible'): ?>
                                                            <button type="button" 
                                                                    class="btn btn-sm <?= !empty($sale['is_on_hold']) ? 'btn-warning' : 'btn-success' ?> rounded-2 px-3 shadow-sm btn-claim-modal" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#claimIncentiveModal"
                                                                    onclick="populateClaimModal(this)"
                                                                    data-id="<?= $sale['id'] ?>"
                                                                    data-booking="<?= htmlspecialchars($sale['booking_no']) ?>"
                                                                    data-project="<?= htmlspecialchars($sale['project_name']) ?> - <?= htmlspecialchars($sale['flat_name']) ?>"
                                                                    data-amount="৳<?= number_format($sale['calculated_incentive'], 2) ?>"
                                                                    title="<?= !empty($sale['is_on_hold']) ? '70% Seller Share (On Hold until own target achieved)' : 'Submit incentive claim' ?>">
                                                                <i class="fa-solid fa-paper-plane me-1"></i><?= !empty($sale['is_on_hold']) ? 'Claim (70% On Hold)' : 'Claim Incentive' ?>
                                                            </button>
                                                        <?php elseif ($sale['claim_status'] === 'claimed'): ?>
                                                            <button class="btn btn-sm btn-outline-warning rounded-2 disabled" disabled>
                                                                <i class="fa-solid fa-clock me-1"></i>Claimed
                                                            </button>
                                                        <?php elseif ($sale['claim_status'] === 'approved'): ?>
                                                            <button class="btn btn-sm btn-outline-info rounded-2 disabled" disabled>
                                                                <i class="fa-solid fa-check me-1"></i>Approved
                                                            </button>
                                                        <?php elseif ($sale['claim_status'] === 'paid'): ?>
                                                            <button class="btn btn-sm btn-outline-success rounded-2 disabled" disabled>
                                                                <i class="fa-solid fa-circle-check me-1"></i>Disbursed
                                                            </button>
                                                        <?php elseif (empty($sale['booking_completed'])): ?>
                                                            <button class="btn btn-sm btn-light text-warning rounded-2 disabled" disabled title="Waiting for Accounts to verify booking payment before claiming">
                                                                <i class="fa-solid fa-clock-rotate-left me-1"></i>Pending Verify
                                                            </button>
                                                        <?php else: ?>
                                                            <button class="btn btn-sm btn-light text-muted rounded-2 disabled" disabled title="<?= htmlspecialchars($sale['remarks'] ?: 'Complete Booking Money and Registration to claim') ?>">
                                                                <i class="fa-solid fa-lock me-1"></i>Locked
                                                            </button>
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
                </div>

                <?php if (!empty($monthlyStats['designated_credits'])): ?>
                <!-- Designated Passive Credits (30% Share) Card -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-3 border-start border-4 border-info">
                        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom flex-wrap gap-2">
                            <div>
                                <h5 class="fw-bold mb-0 text-dark">
                                    <i class="fa-solid fa-people-arrows text-info me-2"></i>Designated Project Passive Credits (30% Share)
                                </h5>
                                <span class="text-muted extra-small">Flats sold by colleagues in your assigned projects/flats. You receive 30% designated credit.</span>
                            </div>
                            <span class="badge bg-info-subtle text-info fw-semibold px-3 py-1">
                                <?= count($monthlyStats['designated_credits']) ?> Passive Sales
                            </span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light text-muted extra-small text-uppercase">
                                        <tr>
                                            <th class="ps-4">Booking Ref</th>
                                            <th>Sold By (Agent)</th>
                                            <th>Project & Flat</th>
                                            <th class="text-end">Land Share Value</th>
                                            <th class="text-end">Your 30% Credit</th>
                                            <th class="text-center pe-4">Credit Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($monthlyStats['designated_credits'] as $dc): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="fw-bold text-dark">BK-#<?= htmlspecialchars($dc['booking_id']) ?></span>
                                                </td>
                                                <td>
                                                    <span class="fw-semibold text-dark"><?= htmlspecialchars($dc['seller_name'] ?? 'Colleague') ?></span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-dark d-block"><?= htmlspecialchars($dc['project_name']) ?></span>
                                                    <span class="text-primary extra-small"><?= htmlspecialchars($dc['flat_name']) ?></span>
                                                </td>
                                                <td class="text-end">
                                                    <span class="text-muted">৳<?= number_format($dc['land_share_value'], 2) ?></span>
                                                </td>
                                                <td class="text-end">
                                                    <span class="fw-bold text-success">৳<?= number_format($dc['credit_amount'], 2) ?></span>
                                                </td>
                                                <td class="text-center pe-4">
                                                    <?php if (!empty($dc['is_eligible'])): ?>
                                                        <span class="badge bg-success-subtle text-success fw-semibold"><i class="fa-solid fa-check me-1"></i> Credited to Wallet</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning-subtle text-warning fw-semibold" title="Unlocks when you achieve your target or 25L"><i class="fa-solid fa-pause me-1"></i> On Hold</span>
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
                <?php endif; ?>

                <!-- Payout History Log Card -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
                            <h5 class="fw-bold mb-0 text-dark">
                                <i class="fa-solid fa-receipt text-success me-2"></i>Recent Payout & Wallet Credit History
                            </h5>
                            <span class="badge bg-light text-dark border">Total Disbursed Logs: <?= count($payoutHistory) ?></span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light text-muted extra-small text-uppercase">
                                        <tr>
                                            <th class="ps-4">Disbursement Date</th>
                                            <th>Booking Ref</th>
                                            <th>Payment Method</th>
                                            <th>Bank / Payment Details</th>
                                            <th>Transaction Ref</th>
                                            <th class="pe-4 text-end">Disbursed Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($payoutHistory)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-3 text-muted">No payout history recorded yet.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($payoutHistory as $payout): ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <span class="fw-semibold text-dark"><?= date('d M, Y', strtotime($payout['payment_date'])) ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary-subtle text-secondary fw-semibold"><?= htmlspecialchars($payout['disbursement_no']) ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info-subtle text-info fw-semibold"><?= htmlspecialchars($payout['payment_method']) ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-semibold text-dark"><?= htmlspecialchars($payout['bank_name']) ?></span>
                                                        <div class="extra-small text-muted"><?= htmlspecialchars($payout['notes']) ?></div>
                                                    </td>
                                                    <td>
                                                        <code class="text-dark bg-light px-2 py-1 rounded border"><?= htmlspecialchars($payout['transaction_ref']) ?></code>
                                                    </td>
                                                    <td class="pe-4 text-end">
                                                        <span class="fw-bold text-success">৳<?= number_format($payout['payment_amount'], 2) ?></span>
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

            </div>
        </div>

        <!-- TAB 2: MY TARGET -->
        <div class="tab-pane fade" id="tab-my-target" role="tabpanel" aria-labelledby="tab-my-target-btn">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom flex-wrap gap-2">
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="fa-solid fa-bullseye text-danger me-2"></i>My Assigned Targets & Achievement History
                        </h5>
                        <span class="text-muted extra-small">View your target breakdown, assigned projects & units, and fill-up progress.</span>
                    </div>
                    <span class="badge bg-primary-subtle text-primary border px-3 py-2 rounded-pill fw-semibold">
                        Total Targets Recorded: <?= !empty($myTargets) ? count($myTargets) : 0 ?>
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted extra-small text-uppercase">
                                <tr>
                                    <th class="ps-4">S/L</th>
                                    <th>Month & Period</th>
                                    <th>Category(s)</th>
                                    <th>Project(s) & Units</th>
                                    <th>Land Share Target</th>
                                    <th>Booking Target</th>
                                    <th>Site Visit Target</th>
                                    <th>Achieved Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($myTargets)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            <i class="fa-solid fa-bullseye fs-3 d-block mb-2"></i>
                                            No sales targets assigned to your account yet.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($myTargets as $idx => $tRow): ?>
                                        <?php
                                            $tMonth = date('Y-m', strtotime($tRow['month']));
                                            $isCurrentCycle = ($tMonth === ($selectedMonth ?? date('Y-m')));
                                            $achievedLandShare = $isCurrentCycle ? ($monthlyStats['total_achieved_land_share'] ?? 0) : null;
                                        ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-muted"><?= $idx + 1 ?></td>
                                            <td>
                                                <strong class="text-dark d-block"><?= date('F Y', strtotime($tRow['month'])) ?></strong>
                                                <?php if (!empty($tRow['from_date']) && !empty($tRow['to_date'])): ?>
                                                    <span class="badge bg-light text-primary border extra-small mt-1">
                                                        <i class="fa-regular fa-calendar-days me-1"></i><?= date('d M, Y', strtotime($tRow['from_date'])) ?> &rarr; <?= date('d M, Y', strtotime($tRow['to_date'])) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($tRow['category_names_display'] ?? ($tRow['category_name'] ?? 'All / None')) ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column align-items-start gap-1">
                                                    <?php 
                                                        if (!empty($tRow['project_names_display'])) {
                                                            echo '<div class="fw-bold text-primary" style="font-size: 13px;">' . count($tRow['project_names_display']) . ' project(s)</div>';
                                                        } else {
                                                            echo '<div class="text-muted" style="font-size: 13px;">Category-wide / All</div>';
                                                        }

                                                        if (!empty($tRow['flat_names_display'])) {
                                                            echo '<div class="fw-bold text-info" style="font-size: 13px;">' . count($tRow['flat_names_display']) . ' specific unit(s)</div>';
                                                        }
                                                        
                                                        if (!empty($tRow['project_names_display']) || !empty($tRow['flat_names_display'])) {
                                                            $projList = htmlspecialchars(json_encode($tRow['project_names_display']));
                                                            $flatList = htmlspecialchars(json_encode($tRow['flat_names_display']));
                                                            echo "<button type='button' class='btn btn-xs btn-light border fs-12 mt-1' onclick='viewTargetDetails(this)' data-projects='{$projList}' data-flats='{$flatList}'><i class='fa-regular fa-eye me-1'></i>View Units</button>";
                                                        }
                                                    ?>
                                                </div>
                                            </td>
                                            <td>
                                                <h6 class="fw-bold text-dark mb-0">৳<?= number_format($tRow['booking_value_target'], 2) ?></h6>
                                                <span class="text-muted extra-small">Land Share Value</span>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-dark"><?= (int)$tRow['booking_target'] ?></span>
                                                <span class="text-muted extra-small">units</span>
                                            </td>
                                            <td>
                                                <?= (int)$tRow['site_visit_target'] ?> visits
                                                <?php if (!empty($tRow['site_visit_leads_target'])): ?>
                                                    <span class="text-muted extra-small">/ <?= $tRow['site_visit_leads_target'] ?> leads</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($isCurrentCycle): ?>
                                                    <?php if ($isTargetAchieved): ?>
                                                        <span class="badge bg-success rounded-pill px-3 py-1 extra-small">
                                                            <i class="fa-solid fa-circle-check me-1"></i>Target Filled Up (Achieved)
                                                        </span>
                                                        <div class="extra-small text-success fw-bold mt-1">৳<?= number_format($achievedLandShare, 2) ?> Achieved</div>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark rounded-pill px-3 py-1 extra-small">
                                                            <i class="fa-solid fa-clock me-1"></i>In Progress
                                                        </span>
                                                        <div class="extra-small text-muted mt-1">৳<?= number_format($achievedLandShare, 2) ?> / ৳25,00,000 min</div>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1 extra-small border">
                                                        Past Target
                                                    </span>
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
        </div>

        <!-- TAB 3: FROM MY TEAM MEMBER GAIN INCENTIVE (0.05%) -->
        <?php if (!empty($isTeamLeader)): ?>
        <div class="tab-pane fade" id="tab-team-gain" role="tabpanel" aria-labelledby="tab-team-gain-btn">
            <!-- Header Summary Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-warning">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted small fw-semibold">TL Override Gained</span>
                                <div class="avatar avatar-sm bg-warning-subtle text-warning rounded-2">
                                    <i class="fa-solid fa-hand-holding-dollar"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold mb-0 text-dark">৳<?= number_format($teamMetrics['tl_gain_from_members'] ?? 0, 2) ?></h4>
                            <span class="text-muted extra-small">0.05% override from member sales</span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-primary">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted small fw-semibold">Sales Team</span>
                                <div class="avatar avatar-sm bg-primary-subtle text-primary rounded-2">
                                    <i class="fa-solid fa-users"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold mb-0 text-primary"><?= htmlspecialchars($teamMetrics['team_name'] ?? 'My Team') ?></h4>
                            <span class="text-muted extra-small"><?= count($teamMetrics['member_breakdown'] ?? []) ?> team members</span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-success">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted small fw-semibold">Qualified Members (≥ 25L)</span>
                                <div class="avatar avatar-sm bg-success-subtle text-success rounded-2">
                                    <i class="fa-solid fa-user-check"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold mb-0 text-success">
                                <?php 
                                $qualCount = 0;
                                if (!empty($teamMetrics['member_breakdown'])) {
                                    foreach ($teamMetrics['member_breakdown'] as $mb) {
                                        if (empty($mb['is_self']) && !empty($mb['is_qualified'])) $qualCount++;
                                    }
                                }
                                echo $qualCount;
                                ?>
                            </h4>
                            <span class="text-muted extra-small">Achieved ≥ ৳25 Lakh in cycle</span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-info">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted small fw-semibold">Override Policy</span>
                                <div class="avatar avatar-sm bg-info-subtle text-info rounded-2">
                                    <i class="fa-solid fa-percent"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold mb-0 text-info"><?= $teamMetrics['tl_rate'] ?? 0.05 ?>%</h4>
                            <span class="text-muted extra-small">On total member land share</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Team Members Breakdown Table Card -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="fa-solid fa-people-arrows text-warning me-2"></i>Team Members Performance & TL 0.05% Gain
                        </h5>
                        <span class="text-muted extra-small">When any team member achieves ≥ ৳25,00,000 in land share sales, you gain 0.05% override incentive from their sales.</span>
                    </div>
                    <span class="badge bg-warning-subtle text-dark border px-3 py-2 rounded-pill fw-semibold">
                        Cycle: <?= $displayMonthName ?>
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted extra-small text-uppercase">
                                <tr>
                                    <th class="ps-4">Team Member</th>
                                    <th>Designation</th>
                                    <th>Target Land Share</th>
                                    <th>Achieved Land Share</th>
                                    <th>25L Target Qualification</th>
                                    <th>Override Rate</th>
                                    <th class="pe-4 text-end">TL Incentive Gained</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $hasOtherMembers = false;
                                if (!empty($teamMetrics['member_breakdown'])):
                                    foreach ($teamMetrics['member_breakdown'] as $mb):
                                        if (!empty($mb['is_self'])) continue;
                                        $hasOtherMembers = true;
                                ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm bg-primary-subtle text-primary rounded-circle me-2 fw-bold d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 12px;">
                                                    <?= strtoupper(substr($mb['full_name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <span class="fw-bold text-dark d-block"><?= htmlspecialchars($mb['full_name']) ?></span>
                                                    <code class="extra-small text-muted">[<?= htmlspecialchars($mb['employee_id']) ?>]</code>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border extra-small"><?= htmlspecialchars($mb['designation']) ?></span>
                                        </td>
                                        <td>
                                            <span class="text-dark small fw-semibold">৳<?= number_format($mb['target'], 2) ?></span>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-dark small">৳<?= number_format($mb['achieved_land_share'], 2) ?></span>
                                            <div class="extra-small text-muted"><?= number_format($mb['achievement_pct'], 1) ?>% of target</div>
                                        </td>
                                        <td>
                                            <?php if (!empty($mb['is_qualified'])): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                    <i class="fa-solid fa-circle-check me-1"></i>Qualified (≥ 25L)
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1">
                                                    <i class="fa-solid fa-clock me-1"></i>Pending 25L Min
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info fw-bold">0.05%</span>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <?php if ($mb['tl_contribution'] > 0): ?>
                                                <span class="fw-bold text-success fs-6">+৳<?= number_format($mb['tl_contribution'], 2) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small">৳0.00</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php 
                                    endforeach;
                                endif;
                                if (!$hasOtherMembers):
                                ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="fa-solid fa-user-group mb-2 d-block fs-3 opacity-50"></i>
                                            No team members assigned under this team yet.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <?php if ($hasOtherMembers && ($teamMetrics['tl_gain_from_members'] ?? 0) > 0): ?>
                            <tfoot class="bg-light">
                                <tr>
                                    <th colspan="6" class="ps-4 text-end fw-bold text-dark">Total TL Override from Team Members:</th>
                                    <th class="pe-4 text-end fw-bold text-success fs-6">৳<?= number_format($teamMetrics['tl_gain_from_members'], 2) ?></th>
                                </tr>
                            </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- TAB 4: UNASSIGNED PROJECT INCENTIVE (70% - 30% SPLIT) -->
        <div class="tab-pane fade" id="tab-unassigned-split" role="tabpanel" aria-labelledby="tab-unassigned-split-btn">
            
            <!-- Information Banner -->
            <div class="alert alert-info border-0 shadow-sm rounded-3 p-3 mb-4 d-flex align-items-center">
                <i class="fa-solid fa-arrows-split-up-and-left fs-3 me-3 text-info"></i>
                <div class="small">
                    <strong>Cross-Project Incentive Split Policy:</strong>
                    <ul class="mb-0 ps-3 mt-1">
                        <li><strong>70% Seller Share:</strong> When you sell flats in projects not in your target, you earn 70% of the incentive. This remains <em>On Hold</em> until you achieve your own ৳25 Lakh minimum qualification, after which it unlocks for claim.</li>
                        <li><strong>30% Designated Share:</strong> When another sales executive sells flats in a project designated to your target, you earn 30% designated share.</li>
                    </ul>
                </div>
            </div>

            <!-- SECTION A: 30% DESIGNATED CREDITS (From Other Agents' Sales in My Project) -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="fa-solid fa-hand-holding-dollar text-success me-2"></i>My 30% Designated Credits (From Other Agents Selling in My Assigned Project)
                        </h6>
                        <span class="text-muted extra-small">Sales made by other executives in projects/flats assigned to your target.</span>
                    </div>
                    <?php 
                    $total30Earned = 0.00;
                    if (!empty($monthlyStats['designated_credits'])) {
                        foreach ($monthlyStats['designated_credits'] as $dc) {
                            $total30Earned += (float)($dc['credit_amount'] ?? 0);
                        }
                    }
                    ?>
                    <span class="badge bg-success-subtle text-success border px-3 py-2 rounded-pill fw-bold fs-7">
                        Total 30% Credits: ৳<?= number_format($total30Earned, 2) ?>
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted extra-small text-uppercase">
                                <tr>
                                    <th class="ps-4">Booking Ref</th>
                                    <th>Project & Flat</th>
                                    <th>Selling Sales Executive</th>
                                    <th>Total Land Share</th>
                                    <th>My 30% Designated Share</th>
                                    <th class="pe-4 text-end">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($monthlyStats['designated_credits'])): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="fa-solid fa-handshake-slash mb-2 d-block fs-3 opacity-50"></i>
                                            No other agents have sold units in your assigned projects this cycle.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($monthlyStats['designated_credits'] as $dc): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <span class="badge bg-light text-dark border">BK-<?= str_pad($dc['booking_id'], 5, '0', STR_PAD_LEFT) ?></span>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-dark d-block"><?= htmlspecialchars($dc['project_name']) ?></span>
                                                <span class="badge bg-primary-subtle text-primary extra-small"><?= htmlspecialchars($dc['flat_name']) ?></span>
                                            </td>
                                            <td>
                                                <i class="fa-solid fa-user-tie text-muted me-1"></i>
                                                <span class="fw-semibold text-dark"><?= htmlspecialchars($dc['seller_name']) ?></span>
                                            </td>
                                            <td>
                                                <span class="text-dark fw-semibold">৳<?= number_format($dc['land_share_value'], 2) ?></span>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-success fs-6">৳<?= number_format($dc['credit_amount'], 2) ?></span>
                                                <div class="extra-small text-muted">30% designated share</div>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <?php if (!empty($monthlyStats['is_eligible'])): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                        <i class="fa-solid fa-check-circle me-1"></i>Unlocked (≥ 25L)
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1">
                                                        <i class="fa-solid fa-lock me-1"></i>On Hold (Achieve 25L)
                                                    </span>
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

            <!-- SECTION B: 70% OUTSIDE SALES (Flats I Sold Outside My Designated Target) -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="fa-solid fa-route text-indigo me-2"></i>My 70% Outside Sales (Flats I Sold in Projects Not Assigned to Me)
                        </h6>
                        <span class="text-muted extra-small">Sales made outside your assigned project. You receive 70% share; 30% goes to the designated project holder.</span>
                    </div>
                    <?php 
                    $outsideBookings = [];
                    $total70Earned = 0.00;
                    if (!empty($monthlyStats['detailed_bookings'])) {
                        foreach ($monthlyStats['detailed_bookings'] as $dbk) {
                            if (empty($dbk['is_assigned']) || (isset($dbk['rate_type']) && strpos($dbk['rate_type'], 'Outside') !== false)) {
                                $outsideBookings[] = $dbk;
                                $total70Earned += (float)($dbk['seller_incentive'] ?? 0);
                            }
                        }
                    }
                    ?>
                    <span class="badge bg-indigo-subtle text-indigo border px-3 py-2 rounded-pill fw-bold fs-7">
                        Total 70% Share: ৳<?= number_format($total70Earned, 2) ?>
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted extra-small text-uppercase">
                                <tr>
                                    <th class="ps-4">Booking Ref</th>
                                    <th>Project & Flat</th>
                                    <th>Land Share Base</th>
                                    <th>Total Calculated</th>
                                    <th>My 70% Seller Share</th>
                                    <th>30% Designated Holder</th>
                                    <th class="pe-4 text-end">Qualification Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($outsideBookings)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="fa-solid fa-clipboard-check mb-2 d-block fs-3 opacity-50"></i>
                                            All your booked flats this cycle are within your assigned projects.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($outsideBookings as $obk): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <span class="badge bg-light text-dark border">BK-<?= str_pad($obk['booking_id'], 5, '0', STR_PAD_LEFT) ?></span>
                                                <div class="extra-small text-muted"><?= date('d M, Y', strtotime($obk['booking_date'])) ?></div>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-dark d-block"><?= htmlspecialchars($obk['project_name']) ?></span>
                                                <span class="badge bg-secondary-subtle text-secondary extra-small"><?= htmlspecialchars($obk['flat_name']) ?></span>
                                            </td>
                                            <td>
                                                <span class="text-dark fw-semibold">৳<?= number_format($obk['land_share_value'], 2) ?></span>
                                            </td>
                                            <td>
                                                <span class="text-muted small">৳<?= number_format(($obk['seller_incentive'] + $obk['designated_incentive']), 2) ?></span>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-dark fs-6">৳<?= number_format($obk['seller_incentive'], 2) ?></span>
                                                <div class="extra-small text-muted">70% seller share</div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <i class="fa-solid fa-user me-1 text-primary"></i>
                                                    <?= htmlspecialchars($obk['designated_user_name'] ?? 'Unassigned / Open') ?>
                                                </span>
                                                <div class="extra-small text-muted">30% share: ৳<?= number_format($obk['designated_incentive'], 2) ?></div>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <?php if (!empty($obk['is_on_hold'])): ?>
                                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1" title="Achieve your 25L qualification to release">
                                                        <i class="fa-solid fa-lock me-1 text-warning"></i>On Hold (Pending 25L)
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                        <i class="fa-solid fa-unlock me-1"></i>Released & Eligible
                                                    </span>
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

        </div>

    </div>
</div>

<!-- Modal: Claim Incentive -->
<div id="claimIncentiveModal" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
        <div class="modal-content border-0 shadow-lg">
            <form id="submitClaimForm">
                <input type="hidden" id="modalBookingId" name="booking_id" value="">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold text-white mb-0" id="claimIncentiveModalLabel">
                        <i class="fa-solid fa-paper-plane me-2"></i>Submit Sales Incentive Claim
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-white text-dark">
                    <div class="alert alert-info border-0 rounded-3 mb-3 d-flex align-items-center">
                        <i class="fa-solid fa-circle-info fs-4 me-3 text-info"></i>
                        <div class="small">
                            Submitting this claim will send an approval request to <strong>Management (Role 3) / Admin & Operation Head (Role 20)</strong>. Once approved, Accounts will disburse payment.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-bold">Booking Reference</label>
                        <input type="text" id="modalBookingNo" class="form-control bg-light fw-bold text-dark" value="" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-bold">Project & Flat Unit</label>
                        <input type="text" id="modalProjectUnit" class="form-control bg-light text-dark" value="" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-bold">Claim Incentive Amount</label>
                        <input type="text" id="modalIncentiveAmount" class="form-control bg-success-subtle text-success fw-bold fs-5" value="" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-bold">Preferred Payment Account / Remarks (Optional)</label>
                        <textarea class="form-control" id="modalRemarks" name="remarks" rows="3" placeholder="Enter bank account details or notes for approval..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary rounded-2" data-bs-dismiss="modal" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-2 px-4" id="btnSubmitClaim">
                        <i class="fa-solid fa-paper-plane me-1"></i> Submit Claim
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: View Target Details (Projects & Flats) -->
<div class="modal fade" id="viewTargetDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold text-dark mb-0">
                    <i class="fa-solid fa-city me-2 text-primary"></i>Assigned Projects & Flat Units
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <h6 class="fw-bold text-primary mb-2"><i class="fa-solid fa-building me-1"></i>Assigned Projects</h6>
                    <ul id="targetDetailsProjects" class="list-group list-group-flush border rounded-2">
                        <!-- Populated via JS -->
                    </ul>
                </div>
                <div>
                    <h6 class="fw-bold text-info mb-2"><i class="fa-solid fa-door-open me-1"></i>Specific Flats / Units</h6>
                    <ul id="targetDetailsFlats" class="list-group list-group-flush border rounded-2">
                        <!-- Populated via JS -->
                    </ul>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-sm btn-secondary rounded-2" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Global function to instantly populate claim modal fields
window.populateClaimModal = function(btn) {
    if (!btn) return;
    const el = (btn instanceof HTMLElement) ? btn : (btn[0] || btn);
    const id = el.getAttribute('data-id') || '';
    const booking = el.getAttribute('data-booking') || '';
    const project = el.getAttribute('data-project') || '';
    const amount = el.getAttribute('data-amount') || '';

    const elId = document.getElementById('modalBookingId');
    const elBooking = document.getElementById('modalBookingNo');
    const elProject = document.getElementById('modalProjectUnit');
    const elAmount = document.getElementById('modalIncentiveAmount');

    if (elId) elId.value = id;
    if (elBooking) elBooking.value = booking;
    if (elProject) elProject.value = project;
    if (elAmount) elAmount.value = amount;
};

// Global function to view target details (Projects & Flats)
window.viewTargetDetails = function(btn) {
    let projects = [];
    let flats = [];
    try {
        projects = JSON.parse(btn.getAttribute('data-projects') || "[]");
    } catch(e){}
    try {
        flats = JSON.parse(btn.getAttribute('data-flats') || "[]");
    } catch(e){}
    
    const projList = document.getElementById('targetDetailsProjects');
    const flatList = document.getElementById('targetDetailsFlats');
    
    if (projList) {
        projList.innerHTML = '';
        if (projects.length > 0) {
            projects.forEach(p => {
                const li = document.createElement('li');
                li.className = 'list-group-item py-2 px-3 small';
                li.innerHTML = '<i class="fa-solid fa-building me-2 text-muted"></i>' + p;
                projList.appendChild(li);
            });
        } else {
            projList.innerHTML = '<li class="list-group-item py-2 px-3 text-muted small">No specific projects (Category-wide / All)</li>';
        }
    }
    
    if (flatList) {
        flatList.innerHTML = '';
        if (flats.length > 0) {
            flats.forEach(f => {
                const li = document.createElement('li');
                li.className = 'list-group-item py-2 px-3 small';
                li.innerHTML = '<i class="fa-solid fa-home me-2 text-info"></i>' + f;
                flatList.appendChild(li);
            });
        } else {
            flatList.innerHTML = '<li class="list-group-item py-2 px-3 text-muted small">All units in assigned projects are included</li>';
        }
    }
    
    const modalEl = document.getElementById('viewTargetDetailsModal');
    if (modalEl) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modalObj = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modalObj.show();
        } else if (typeof $ !== 'undefined') {
            $('#viewTargetDetailsModal').modal('show');
        }
    }
};

document.addEventListener('DOMContentLoaded', function () {
    const claimModal = document.getElementById('claimIncentiveModal');
    
    // Safely move modal to body once on DOM load to prevent any z-index or stacking context clipping
    if (claimModal && claimModal.parentNode !== document.body) {
        document.body.appendChild(claimModal);
    }

    const viewTargetModal = document.getElementById('viewTargetDetailsModal');
    if (viewTargetModal && viewTargetModal.parentNode !== document.body) {
        document.body.appendChild(viewTargetModal);
    }

    // Bootstrap standard show.bs.modal listener
    if (claimModal) {
        claimModal.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget || document.querySelector('.btn-claim-modal:focus');
            if (btn) {
                window.populateClaimModal(btn);
            }
        });
    }

    // Vanilla JS click delegation
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-claim-modal');
        if (btn) {
            window.populateClaimModal(btn);
        }
    });

    // jQuery delegation if loaded
    if (typeof $ !== 'undefined') {
        $(document).on('click', '.btn-claim-modal', function () {
            window.populateClaimModal(this);
        });
        $('#claimIncentiveModal').on('show.bs.modal', function (event) {
            const btn = event.relatedTarget || this;
            if (btn) window.populateClaimModal(btn);
        });
    }

    // Form Submission
    const submitClaimForm = document.getElementById('submitClaimForm');
    if (submitClaimForm) {
        submitClaimForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitClaim');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Submitting...';
            }

            const formData = new FormData(this);
            fetch('<?= $base_url ?>/incentives/claim', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Submit Claim';
                }
                if (data.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Claim Submitted!',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: true
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        alert(data.message);
                        location.reload();
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Claim Failed',
                            text: data.message
                        });
                    } else {
                        alert('Error: ' + data.message);
                    }
                }
            })
            .catch(err => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Submit Claim';
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred submitting the claim: ' + err
                    });
                } else {
                    alert('An error occurred submitting the claim.');
                }
            });
        });
    }
});
</script>
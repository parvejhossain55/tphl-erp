<?php
$base_url = $GLOBALS['base_url'] ?? '/TPHL-ERP';
$displayMonthName = date('F Y', strtotime(($selectedMonth ?? date('Y-m')) . '-01'));
$cycleStartStr = !empty($monthlyStats['cycle_start']) ? date('d M, Y', strtotime($monthlyStats['cycle_start'])) : '26th of last month';
$cycleEndStr   = !empty($monthlyStats['cycle_end']) ? date('d M, Y', strtotime($monthlyStats['cycle_end'])) : '25th of this month';
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold mb-1 text-dark">
                <i class="fa-solid fa-hand-holding-dollar text-primary me-2"></i>My Sales Incentives
            </h3>
            <p class="text-muted small mb-0">Track your completed sales bookings, collected payments, incentive eligibility, and wallet disbursements.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill fs-7 fw-semibold">
                <i class="fa-regular fa-calendar-days text-primary me-1"></i> Cycle: <?= $cycleStartStr ?> – <?= $cycleEndStr ?>
            </span>
            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fs-7 fw-semibold">
                <i class="fa-solid fa-user-tie me-1"></i> Sales Executive Portal
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
                <?php if (!empty($selectedMonth) && $selectedMonth !== date('Y-m')): ?>
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

    <!-- Main Content Grid -->
    <div class="row g-4">
        <!-- Sales & Incentive Claims Table Card -->
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom flex-wrap gap-2">
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="fa-solid fa-list-check text-primary me-2"></i>My Project Sales & Incentive Eligibility (<?= $displayMonthName ?>)
                        </h5>
                        <span class="text-muted extra-small">Showing bookings within the active cycle (<?= $cycleStartStr ?> – <?= $cycleEndStr ?>). Claim becomes active when both Booking Money & Registration are complete.</span>
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
                                    <th>Land Share (Calculation Base)</th>
                                    <th>Total Collected</th>
                                    <th>Applied Rate (%)</th>
                                    <th>Incentive Amount</th>
                                    <th class="text-center">Booking Money Status</th>
                                    <th class="text-center">Registration Status</th>
                                    <th class="text-center">Claim Status</th>
                                    <th class="pe-4 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($mySalesBookings)): ?>
                                    <tr>
                                        <td colspan="11" class="text-center py-4 text-muted">
                                            <i class="fa-solid fa-folder-open fs-3 d-block mb-2"></i>
                                            No sales bookings found assigned to your user account.
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
                                                <span class="extra-small text-muted d-block"><?= htmlspecialchars($sale['category_name'] ?? 'Modality') ?></span>
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
                                                        <i class="fa-solid fa-check-double me-1"></i>Approved
                                                    </span>
                                                <?php elseif ($sale['claim_status'] === 'paid'): ?>
                                                    <span class="badge bg-success text-white rounded-pill px-3 py-1 extra-small">
                                                        <i class="fa-solid fa-shield-check me-1"></i>Paid & Settled
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
                                                            data-toggle="modal"
                                                            data-target="#claimIncentiveModal"
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
                                                        <i class="fa-solid fa-clock me-1"></i>Pending Review
                                                    </button>
                                                <?php elseif ($sale['claim_status'] === 'approved'): ?>
                                                    <button class="btn btn-sm btn-outline-info rounded-2 disabled" disabled>
                                                        <i class="fa-solid fa-check me-1"></i>Approved
                                                    </button>
                                                <?php elseif ($sale['claim_status'] === 'paid'): ?>
                                                    <button class="btn btn-sm btn-outline-success rounded-2 disabled" disabled>
                                                        <i class="fa-solid fa-circle-check me-1"></i>Disbursed
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
                            Submitting this claim will send an approval request to <strong>Management</strong>. Once approved, Accounts will disburse payment into your wallet.
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
                        <textarea class="form-control" id="modalRemarks" name="remarks" rows="3" placeholder="Enter bank account details or notes for Management approval..."></textarea>
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

<script>
// Global function to instantly populate modal fields
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

document.addEventListener('DOMContentLoaded', function () {
    const claimModal = document.getElementById('claimIncentiveModal');
    
    // Safely move modal to body once on DOM load to prevent any z-index or stacking context clipping
    if (claimModal && claimModal.parentNode !== document.body) {
        document.body.appendChild(claimModal);
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
